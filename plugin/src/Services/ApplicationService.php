<?php
/**
 * Application Service - Geschäftslogik für Bewerbungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use RecruitingPlaybook\Constants\ApplicationStatus;
use WP_Error;

/**
 * Service für Bewerbungs-Operationen
 */
class ApplicationService {

	/**
	 * Document Service
	 *
	 * @var DocumentService
	 */
	private DocumentService $document_service;

	/**
	 * Email Service
	 *
	 * @var EmailService
	 */
	private EmailService $email_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->document_service = new DocumentService();
		$this->email_service    = new EmailService();
	}

	/**
	 * Neue Bewerbung erstellen
	 *
	 * @param array $data Bewerbungsdaten.
	 * @return int|WP_Error Application ID oder Fehler.
	 */
	public function create( array $data ): int|WP_Error {
		global $wpdb;

		// 1. Kandidaten anlegen oder aktualisieren
		$candidate_id = $this->getOrCreateCandidate( $data );
		if ( is_wp_error( $candidate_id ) ) {
			return $candidate_id;
		}

		// 2. Bewerbung in DB speichern
		// DSGVO: Consent-Daten nur speichern wenn tatsächlich Einwilligung gegeben wurde
		$has_consent = ! empty( $data['privacy_consent'] );

		$application_data = [
			'job_id'              => (int) $data['job_id'],
			'candidate_id'        => $candidate_id,
			'status'              => ApplicationStatus::NEW,
			'cover_letter'        => $data['cover_letter'] ?? '',
			'source'              => 'website',
			'consent_privacy'     => $has_consent ? 1 : 0,
			'consent_privacy_at'  => $has_consent ? current_time( 'mysql' ) : null,
			'consent_ip'          => $has_consent ? ( $data['ip_address'] ?? '' ) : '',
			'created_at'          => current_time( 'mysql' ),
			'updated_at'          => current_time( 'mysql' ),
		];

		$table = $wpdb->prefix . 'rp_applications';
		$inserted = $wpdb->insert( $table, $application_data );

		if ( false === $inserted ) {
			return new WP_Error(
				'db_error',
				__( 'Bewerbung konnte nicht gespeichert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$application_id = (int) $wpdb->insert_id;

		// 3. Dateien verarbeiten
		if ( ! empty( $data['files'] ) ) {
			$file_result = $this->document_service->processUploads(
				$application_id,
				$data['files']
			);

			if ( is_wp_error( $file_result ) ) {
				// Bewerbung wurde erstellt, aber Dateien fehlgeschlagen
				$this->logActivity( $application_id, 'file_upload_failed', $file_result->get_error_message() );
			}
		}

		// 4. Activity Log
		$this->logActivity( $application_id, 'application_received', 'Neue Bewerbung eingegangen' );

		// 5. E-Mails versenden
		$this->email_service->sendApplicationReceived( $application_id );
		$this->email_service->sendApplicantConfirmation( $application_id );

		// 6. Hook für Erweiterungen
		do_action( 'rp_application_created', $application_id, $data );

		return $application_id;
	}

	/**
	 * Bewerbung abrufen
	 *
	 * @param int $id Application ID.
	 * @return array|null
	 */
	public function get( int $id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$application = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( ! $application ) {
			return null;
		}

		// Kandidaten-Daten laden
		$application['candidate'] = $this->getCandidate( (int) $application['candidate_id'] );

		// Job-Daten laden
		$job = get_post( (int) $application['job_id'] );
		if ( $job ) {
			$application['job'] = [
				'id'    => $job->ID,
				'title' => $job->post_title,
			];
		}

		// Dokumente laden
		$application['documents'] = $this->document_service->getByApplication( $id );

		return $application;
	}

	/**
	 * Bewerbungen auflisten
	 *
	 * @param array $args Filter-Argumente.
	 * @return array
	 */
	public function list( array $args = [] ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';
		$candidates_table = $wpdb->prefix . 'rp_candidates';

		$where = [ '1=1' ];
		$values = [];

		// Filter: Job ID
		if ( ! empty( $args['job_id'] ) ) {
			$where[] = 'a.job_id = %d';
			$values[] = (int) $args['job_id'];
		}

		// Filter: Status
		if ( ! empty( $args['status'] ) ) {
			$where[] = 'a.status = %s';
			$values[] = $args['status'];
		}

		// Filter: Suche
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = '(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)';
			$values[] = $search;
			$values[] = $search;
			$values[] = $search;
		}

		$where_clause = implode( ' AND ', $where );

		// Sortierung - Whitelist-basiert für SQL-Injection-Schutz.
		$allowed_orderby = [
			'date'   => 'a.created_at',
			'name'   => 'c.last_name',
			'status' => 'a.status',
		];
		$orderby_key = isset( $args['orderby'] ) ? sanitize_key( $args['orderby'] ) : 'date';
		$orderby     = $allowed_orderby[ $orderby_key ] ?? 'a.created_at';

		// Order direction - nur ASC oder DESC erlaubt.
		$order = isset( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// Pagination
		$per_page = min( max( (int) ( $args['per_page'] ?? 20 ), 1 ), 100 );
		$page = max( (int) ( $args['page'] ?? 1 ), 1 );
		$offset = ( $page - 1 ) * $per_page;

		// Gesamtzahl ermitteln
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} a
				LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id
				WHERE {$where_clause}",
				...$values
			)
		);

		// Daten abrufen
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, c.first_name, c.last_name, c.email, c.phone, c.salutation
				FROM {$table} a
				LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id
				WHERE {$where_clause}
				ORDER BY {$orderby} {$order}
				LIMIT %d OFFSET %d",
				...array_merge( $values, [ $per_page, $offset ] )
			),
			ARRAY_A
		);

		// Job-Titel hinzufügen
		foreach ( $results as &$row ) {
			$job = get_post( (int) $row['job_id'] );
			$row['job_title'] = $job ? $job->post_title : '';
		}

		return [
			'data' => $results,
			'meta' => [
				'total'        => $total,
				'per_page'     => $per_page,
				'current_page' => $page,
				'total_pages'  => (int) ceil( $total / $per_page ),
			],
		];
	}

	/**
	 * Status einer Bewerbung ändern
	 *
	 * @param int    $id     Application ID.
	 * @param string $status Neuer Status.
	 * @param string $note   Optionale Notiz.
	 * @return bool|WP_Error
	 */
	public function updateStatus( int $id, string $status, string $note = '' ): bool|WP_Error {
		global $wpdb;

		// Validieren
		$valid_statuses = [
			ApplicationStatus::NEW,
			ApplicationStatus::SCREENING,
			ApplicationStatus::INTERVIEW,
			ApplicationStatus::OFFER,
			ApplicationStatus::HIRED,
			ApplicationStatus::REJECTED,
			ApplicationStatus::WITHDRAWN,
		];

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return new WP_Error(
				'invalid_status',
				__( 'Ungültiger Status.', 'recruiting-playbook' )
			);
		}

		// Aktuellen Status prüfen
		$application = $this->get( $id );
		if ( ! $application ) {
			return new WP_Error(
				'not_found',
				__( 'Bewerbung nicht gefunden.', 'recruiting-playbook' )
			);
		}

		$old_status = $application['status'];

		// Status aktualisieren
		$table = $wpdb->prefix . 'rp_applications';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$table,
			[
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		if ( false === $updated ) {
			return new WP_Error(
				'db_error',
				__( 'Status konnte nicht aktualisiert werden.', 'recruiting-playbook' )
			);
		}

		// Activity Log
		$message = sprintf(
			/* translators: 1: old status, 2: new status */
			__( 'Status geändert von "%1$s" zu "%2$s"', 'recruiting-playbook' ),
			$this->getStatusLabel( $old_status ),
			$this->getStatusLabel( $status )
		);
		if ( $note ) {
			$message .= ': ' . $note;
		}

		$this->logActivity(
			$id,
			'status_changed',
			$message,
			[ 'status' => $old_status ],
			[ 'status' => $status ]
		);

		// E-Mails bei bestimmten Status-Änderungen
		if ( ApplicationStatus::REJECTED === $status ) {
			$this->email_service->sendRejectionEmail( $id );
		}

		// Hook für Erweiterungen
		do_action( 'rp_application_status_changed', $id, $status, $old_status );

		return true;
	}

	/**
	 * Kandidaten abrufen oder erstellen
	 *
	 * @param array $data Kandidaten-Daten.
	 * @return int|WP_Error Candidate ID.
	 */
	private function getOrCreateCandidate( array $data ): int|WP_Error {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_candidates';
		$email = strtolower( trim( $data['email'] ) );

		// Prüfen ob Kandidat bereits existiert
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE email = %s",
				$email
			)
		);

		if ( $existing ) {
			// Kandidaten-Daten aktualisieren
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				[
					'salutation'  => $data['salutation'] ?? '',
					'first_name'  => $data['first_name'],
					'last_name'   => $data['last_name'],
					'phone'       => $data['phone'] ?? '',
					'updated_at'  => current_time( 'mysql' ),
				],
				[ 'id' => $existing ],
				[ '%s', '%s', '%s', '%s', '%s' ],
				[ '%d' ]
			);

			return (int) $existing;
		}

		// Neuen Kandidaten erstellen
		$candidate_data = [
			'email'      => $email,
			'salutation' => $data['salutation'] ?? '',
			'first_name' => $data['first_name'],
			'last_name'  => $data['last_name'],
			'phone'      => $data['phone'] ?? '',
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		];

		$inserted = $wpdb->insert( $table, $candidate_data );

		if ( false === $inserted ) {
			return new WP_Error(
				'db_error',
				__( 'Kandidat konnte nicht erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Kandidaten-Daten abrufen
	 *
	 * @param int $id Candidate ID.
	 * @return array|null
	 */
	private function getCandidate( int $id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_candidates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Activity-Log Eintrag erstellen
	 *
	 * @param int    $application_id Application ID.
	 * @param string $action         Aktion.
	 * @param string $message        Nachricht.
	 * @param array  $old_value      Optionale alte Werte für Änderungen.
	 * @param array  $new_value      Optionale neue Werte für Änderungen.
	 */
	private function logActivity( int $application_id, string $action, string $message, array $old_value = [], array $new_value = [] ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_activity_log';

		$current_user = wp_get_current_user();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'object_type' => 'application',
				'object_id'   => $application_id,
				'action'      => $action,
				'user_id'     => get_current_user_id() ?: null,
				'user_name'   => $current_user->ID ? $current_user->display_name : null,
				'old_value'   => ! empty( $old_value ) ? wp_json_encode( $old_value ) : null,
				'new_value'   => ! empty( $new_value ) ? wp_json_encode( $new_value ) : null,
				'message'     => $message,
				'ip_address'  => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null,
				'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : null,
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Status-Label abrufen
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private function getStatusLabel( string $status ): string {
		$labels = [
			ApplicationStatus::NEW       => __( 'Neu', 'recruiting-playbook' ),
			ApplicationStatus::SCREENING => __( 'In Prüfung', 'recruiting-playbook' ),
			ApplicationStatus::INTERVIEW => __( 'Vorstellungsgespräch', 'recruiting-playbook' ),
			ApplicationStatus::OFFER     => __( 'Angebot', 'recruiting-playbook' ),
			ApplicationStatus::HIRED     => __( 'Eingestellt', 'recruiting-playbook' ),
			ApplicationStatus::REJECTED  => __( 'Abgelehnt', 'recruiting-playbook' ),
			ApplicationStatus::WITHDRAWN => __( 'Zurückgezogen', 'recruiting-playbook' ),
		];

		return $labels[ $status ] ?? $status;
	}
}

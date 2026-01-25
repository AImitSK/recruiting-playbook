<?php
/**
 * Talent Pool Service - Geschäftslogik für Talent-Pool
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;
use RecruitingPlaybook\Repositories\TalentPoolRepository;
use WP_Error;

/**
 * Service für Talent-Pool Operationen
 */
class TalentPoolService {

	/**
	 * Talent Pool Repository
	 *
	 * @var TalentPoolRepository
	 */
	private TalentPoolRepository $repository;

	/**
	 * Standard-Aufbewahrungsdauer in Monaten
	 */
	private const DEFAULT_RETENTION_MONTHS = 24;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new TalentPoolRepository();
	}

	/**
	 * Kandidat zum Talent-Pool hinzufügen
	 *
	 * @param int    $candidate_id Kandidaten-ID.
	 * @param string $reason       Grund für Aufnahme.
	 * @param string $tags         Komma-separierte Tags.
	 * @param string $expires_at   Ablaufdatum (optional).
	 * @return array|WP_Error
	 */
	public function add( int $candidate_id, string $reason = '', string $tags = '', ?string $expires_at = null ): array|WP_Error {
		// Prüfen ob bereits im Pool.
		if ( $this->repository->exists( $candidate_id ) ) {
			return new WP_Error(
				'already_exists',
				__( 'Kandidat ist bereits im Talent-Pool', 'recruiting-playbook' ),
				[ 'status' => 409 ]
			);
		}

		// Kandidat prüfen.
		global $wpdb;
		$candidates_table = Schema::getTables()['candidates'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$candidate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$candidates_table} WHERE id = %d",
				$candidate_id
			)
		);

		if ( ! $candidate ) {
			return new WP_Error(
				'not_found',
				__( 'Kandidat nicht gefunden', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Tags normalisieren.
		$normalized_tags = $this->normalizeTags( $tags );

		$entry_id = $this->repository->create( [
			'candidate_id' => $candidate_id,
			'added_by'     => get_current_user_id(),
			'reason'       => sanitize_textarea_field( $reason ),
			'tags'         => $normalized_tags,
			'expires_at'   => $expires_at,
		] );

		if ( ! $entry_id ) {
			return new WP_Error(
				'create_failed',
				__( 'Fehler beim Hinzufügen zum Talent-Pool', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		// Activity Log für alle Bewerbungen des Kandidaten.
		$this->logActivityForCandidate(
			$candidate_id,
			'talent_pool_added',
			__( 'Zum Talent-Pool hinzugefügt', 'recruiting-playbook' ),
			[
				'reason' => $reason,
				'tags'   => $normalized_tags,
			]
		);

		return $this->repository->findWithCandidate( $entry_id );
	}

	/**
	 * Kandidat aus Talent-Pool entfernen
	 *
	 * @param int $candidate_id Kandidaten-ID.
	 * @return bool|WP_Error
	 */
	public function remove( int $candidate_id ): bool|WP_Error {
		$entry = $this->repository->findByCandidate( $candidate_id );

		if ( ! $entry ) {
			return new WP_Error(
				'not_found',
				__( 'Kandidat nicht im Talent-Pool', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$this->repository->softDelete( (int) $entry['id'] );

		// Activity Log.
		$this->logActivityForCandidate(
			$candidate_id,
			'talent_pool_removed',
			__( 'Aus Talent-Pool entfernt', 'recruiting-playbook' )
		);

		return true;
	}

	/**
	 * Eintrag aktualisieren
	 *
	 * @param int   $candidate_id Kandidaten-ID.
	 * @param array $data         Update-Daten.
	 * @return array|WP_Error
	 */
	public function update( int $candidate_id, array $data ): array|WP_Error {
		$entry = $this->repository->findByCandidate( $candidate_id );

		if ( ! $entry ) {
			return new WP_Error(
				'not_found',
				__( 'Kandidat nicht im Talent-Pool', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$update_data = [];

		if ( isset( $data['reason'] ) ) {
			$update_data['reason'] = sanitize_textarea_field( $data['reason'] );
		}

		if ( isset( $data['tags'] ) ) {
			$update_data['tags'] = $this->normalizeTags( $data['tags'] );
		}

		if ( isset( $data['expires_at'] ) ) {
			$update_data['expires_at'] = sanitize_text_field( $data['expires_at'] );
		}

		if ( empty( $update_data ) ) {
			return $this->repository->findWithCandidate( (int) $entry['id'] );
		}

		$this->repository->update( (int) $entry['id'], $update_data );

		return $this->repository->findWithCandidate( (int) $entry['id'] );
	}

	/**
	 * Prüfen ob Kandidat im Pool ist
	 *
	 * @param int $candidate_id Kandidaten-ID.
	 * @return bool
	 */
	public function isInPool( int $candidate_id ): bool {
		return $this->repository->exists( $candidate_id );
	}

	/**
	 * Eintrag für Kandidat laden
	 *
	 * @param int $candidate_id Kandidaten-ID.
	 * @return array|null
	 */
	public function getByCandidate( int $candidate_id ): ?array {
		$entry = $this->repository->findByCandidate( $candidate_id );

		if ( ! $entry ) {
			return null;
		}

		return $this->repository->findWithCandidate( (int) $entry['id'] );
	}

	/**
	 * Talent-Pool Liste laden
	 *
	 * @param array $args Query-Argumente.
	 * @return array
	 */
	public function getList( array $args = [] ): array {
		return $this->repository->getList( $args );
	}

	/**
	 * Alle verwendeten Tags laden
	 *
	 * @return array
	 */
	public function getAllTags(): array {
		return $this->repository->getAllTags();
	}

	/**
	 * Abgelaufene Einträge löschen
	 *
	 * @return int Anzahl gelöschter Einträge.
	 */
	public function cleanupExpired(): int {
		return $this->repository->deleteExpired();
	}

	/**
	 * Erinnerungen vor Ablauf senden
	 *
	 * @param int $days_before Tage vor Ablauf.
	 * @return int Anzahl gesendeter Erinnerungen.
	 */
	public function sendExpiryReminders( int $days_before = 30 ): int {
		$expiring = $this->repository->getExpiring( $days_before );
		$count    = 0;

		foreach ( $expiring as $entry ) {
			// E-Mail an den HR-Verantwortlichen.
			$added_by = get_userdata( (int) $entry['added_by'] );
			if ( ! $added_by ) {
				continue;
			}

			$candidate_name = trim( "{$entry['first_name']} {$entry['last_name']}" );
			if ( empty( $candidate_name ) ) {
				$candidate_name = __( 'Unbekannt', 'recruiting-playbook' );
			}

			$subject = sprintf(
				/* translators: %s: Candidate name */
				__( '[Recruiting Playbook] Talent-Pool Eintrag läuft ab: %s', 'recruiting-playbook' ),
				$candidate_name
			);

			$message = sprintf(
				/* translators: 1: Candidate name, 2: Expiry date */
				__(
					"Der Talent-Pool Eintrag für %1\$s läuft am %2\$s ab.\n\nBitte prüfen Sie, ob Sie den Eintrag verlängern möchten.\n\nGrundinformationen:\n- E-Mail: %3\$s\n- Aufgenommen am: %4\$s",
					'recruiting-playbook'
				),
				$candidate_name,
				wp_date( get_option( 'date_format' ), strtotime( $entry['expires_at'] ) ),
				$entry['email'],
				wp_date( get_option( 'date_format' ), strtotime( $entry['created_at'] ) )
			);

			$sent = wp_mail( $added_by->user_email, $subject, $message );

			if ( $sent ) {
				$this->repository->markReminderSent( (int) $entry['id'] );
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Tags normalisieren
	 *
	 * @param string $tags Komma-separierte Tags.
	 * @return string
	 */
	private function normalizeTags( string $tags ): string {
		$tag_array = array_map( 'trim', explode( ',', $tags ) );
		$tag_array = array_filter( $tag_array );
		$tag_array = array_map( 'strtolower', $tag_array );
		$tag_array = array_map( 'sanitize_text_field', $tag_array );
		$tag_array = array_unique( $tag_array );
		sort( $tag_array );

		return implode( ',', $tag_array );
	}

	/**
	 * Activity Log für alle Bewerbungen eines Kandidaten
	 *
	 * @param int    $candidate_id Kandidaten-ID.
	 * @param string $action       Aktion.
	 * @param string $message      Nachricht.
	 * @param array  $meta         Meta-Daten.
	 */
	private function logActivityForCandidate( int $candidate_id, string $action, string $message, array $meta = [] ): void {
		global $wpdb;

		$applications_table = Schema::getTables()['applications'];
		$activity_table     = Schema::getTables()['activity_log'];

		// Alle Bewerbungen des Kandidaten finden.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$application_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$applications_table} WHERE candidate_id = %d",
				$candidate_id
			)
		);

		$current_user = wp_get_current_user();
		$ip_address   = $this->getClientIp();

		foreach ( $application_ids as $application_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$activity_table,
				[
					'object_type' => 'application',
					'object_id'   => $application_id,
					'action'      => $action,
					'user_id'     => get_current_user_id() ?: null,
					'user_name'   => $current_user->ID ? $current_user->display_name : null,
					'message'     => $message,
					'meta'        => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
					'ip_address'  => $ip_address,
					'created_at'  => current_time( 'mysql' ),
				],
				[ '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
			);
		}
	}

	/**
	 * Client-IP ermitteln
	 *
	 * @return string
	 */
	private function getClientIp(): string {
		$ip_keys = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}
}

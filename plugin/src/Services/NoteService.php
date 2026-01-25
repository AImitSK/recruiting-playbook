<?php
/**
 * Note Service - Geschäftslogik für Notizen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\NoteRepository;
use WP_Error;

/**
 * Service für Notizen-Operationen
 */
class NoteService {

	/**
	 * Note Repository
	 *
	 * @var NoteRepository
	 */
	private NoteRepository $repository;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new NoteRepository();
	}

	/**
	 * Notiz erstellen
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $content        Notiz-Inhalt.
	 * @param bool   $is_private     Private Notiz.
	 * @return array|WP_Error
	 */
	public function create( int $application_id, string $content, bool $is_private = false ): array|WP_Error {
		// Bewerbung laden für candidate_id.
		$application_service = new ApplicationService();
		$application         = $application_service->get( $application_id );

		if ( ! $application ) {
			return new WP_Error(
				'not_found',
				__( 'Bewerbung nicht gefunden', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$note_id = $this->repository->create( [
			'application_id' => $application_id,
			'candidate_id'   => (int) $application['candidate_id'],
			'user_id'        => get_current_user_id(),
			'content'        => wp_kses_post( $content ),
			'is_private'     => $is_private ? 1 : 0,
		] );

		if ( ! $note_id ) {
			return new WP_Error(
				'create_failed',
				__( 'Notiz konnte nicht erstellt werden', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		// Activity Log.
		$this->logActivity(
			$application_id,
			'note_added',
			__( 'Notiz hinzugefügt', 'recruiting-playbook' ),
			[
				'note_id' => $note_id,
				'preview' => wp_trim_words( wp_strip_all_tags( $content ), 20 ),
			]
		);

		return $this->repository->find( $note_id );
	}

	/**
	 * Notizen für Bewerbung laden
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	public function getForApplication( int $application_id ): array {
		$current_user_id = get_current_user_id();

		$notes = $this->repository->findByApplication( $application_id );

		// Private Notizen filtern (nur eigene anzeigen).
		return array_values( array_filter( $notes, function ( $note ) use ( $current_user_id ) {
			if ( $note['is_private'] && $note['user_id'] !== $current_user_id ) {
				return false;
			}
			return true;
		} ) );
	}

	/**
	 * Notizen für Kandidat laden
	 *
	 * @param int $candidate_id Kandidaten-ID.
	 * @return array
	 */
	public function getForCandidate( int $candidate_id ): array {
		$current_user_id = get_current_user_id();

		$notes = $this->repository->findByCandidate( $candidate_id );

		// Private Notizen filtern.
		return array_values( array_filter( $notes, function ( $note ) use ( $current_user_id ) {
			if ( $note['is_private'] && $note['user_id'] !== $current_user_id ) {
				return false;
			}
			return true;
		} ) );
	}

	/**
	 * Einzelne Notiz laden
	 *
	 * @param int $note_id Notiz-ID.
	 * @return array|WP_Error
	 */
	public function get( int $note_id ): array|WP_Error {
		$note = $this->repository->find( $note_id );

		if ( ! $note ) {
			return new WP_Error(
				'not_found',
				__( 'Notiz nicht gefunden', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Private Notiz nur für Autor sichtbar.
		if ( $note['is_private'] && $note['user_id'] !== get_current_user_id() ) {
			return new WP_Error(
				'forbidden',
				__( 'Keine Berechtigung', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return $note;
	}

	/**
	 * Notiz aktualisieren
	 *
	 * @param int    $note_id Notiz-ID.
	 * @param string $content Neuer Inhalt.
	 * @return array|WP_Error
	 */
	public function update( int $note_id, string $content ): array|WP_Error {
		$note = $this->repository->find( $note_id );

		if ( ! $note ) {
			return new WP_Error(
				'not_found',
				__( 'Notiz nicht gefunden', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Nur eigene Notizen bearbeiten (oder Admin).
		if ( $note['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'forbidden',
				__( 'Keine Berechtigung', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		$this->repository->update( $note_id, [
			'content' => wp_kses_post( $content ),
		] );

		// Activity Log.
		if ( $note['application_id'] ) {
			$this->logActivity(
				(int) $note['application_id'],
				'note_updated',
				__( 'Notiz bearbeitet', 'recruiting-playbook' ),
				[ 'note_id' => $note_id ]
			);
		}

		return $this->repository->find( $note_id );
	}

	/**
	 * Notiz löschen (Soft Delete)
	 *
	 * @param int $note_id Notiz-ID.
	 * @return bool|WP_Error
	 */
	public function delete( int $note_id ): bool|WP_Error {
		$note = $this->repository->find( $note_id );

		if ( ! $note ) {
			return new WP_Error(
				'not_found',
				__( 'Notiz nicht gefunden', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Nur eigene Notizen löschen (oder Admin).
		if ( $note['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'forbidden',
				__( 'Keine Berechtigung', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		$this->repository->softDelete( $note_id );

		// Activity Log.
		if ( $note['application_id'] ) {
			$this->logActivity(
				(int) $note['application_id'],
				'note_deleted',
				__( 'Notiz gelöscht', 'recruiting-playbook' ),
				[ 'note_id' => $note_id ]
			);
		}

		return true;
	}

	/**
	 * Anzahl Notizen für Bewerbung
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return int
	 */
	public function countForApplication( int $application_id ): int {
		return $this->repository->countByApplication( $application_id );
	}

	/**
	 * Activity-Log Eintrag erstellen
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $action         Aktion.
	 * @param string $message        Nachricht.
	 * @param array  $meta           Meta-Daten.
	 */
	private function logActivity( int $application_id, string $action, string $message, array $meta = [] ): void {
		global $wpdb;

		$table        = $wpdb->prefix . 'rp_activity_log';
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
				'message'     => $message,
				'meta'        => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
				'ip_address'  => $this->getClientIp(),
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
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

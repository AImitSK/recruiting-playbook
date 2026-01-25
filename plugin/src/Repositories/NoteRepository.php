<?php
/**
 * Note Repository - Datenzugriff für Notizen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für Notizen-Operationen
 */
class NoteRepository {

	/**
	 * Tabellen-Name
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->table = Schema::getTables()['notes'];
	}

	/**
	 * Notiz erstellen
	 *
	 * @param array $data Notiz-Daten.
	 * @return int|false Insert ID oder false bei Fehler.
	 */
	public function create( array $data ): int|false {
		global $wpdb;

		$defaults = [
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		];

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			$data,
			$this->getFormats( $data )
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Notiz finden
	 *
	 * @param int $id Notiz-ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$note = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL",
				$id
			),
			ARRAY_A
		);

		if ( ! $note ) {
			return null;
		}

		return $this->enrichNote( $note );
	}

	/**
	 * Notizen für Bewerbung laden
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	public function findByApplication( int $application_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$notes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE application_id = %d AND deleted_at IS NULL
				ORDER BY created_at DESC",
				$application_id
			),
			ARRAY_A
		);

		return array_map( [ $this, 'enrichNote' ], $notes );
	}

	/**
	 * Notizen für Kandidaten laden
	 *
	 * @param int $candidate_id Kandidaten-ID.
	 * @return array
	 */
	public function findByCandidate( int $candidate_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$notes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE candidate_id = %d AND deleted_at IS NULL
				ORDER BY created_at DESC",
				$candidate_id
			),
			ARRAY_A
		);

		return array_map( [ $this, 'enrichNote' ], $notes );
	}

	/**
	 * Notiz aktualisieren
	 *
	 * @param int   $id   Notiz-ID.
	 * @param array $data Update-Daten.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			$data,
			[ 'id' => $id ],
			$this->getFormats( $data ),
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Notiz soft-löschen
	 *
	 * @param int $id Notiz-ID.
	 * @return bool
	 */
	public function softDelete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			[
				'deleted_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Anzahl Notizen für Bewerbung
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return int
	 */
	public function countByApplication( int $application_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table}
				WHERE application_id = %d AND deleted_at IS NULL",
				$application_id
			)
		);
	}

	/**
	 * Notiz mit User-Daten anreichern
	 *
	 * @param array $note Notiz-Daten.
	 * @return array
	 */
	private function enrichNote( array $note ): array {
		$user = get_userdata( (int) $note['user_id'] );

		$note['author'] = $user ? [
			'id'     => $user->ID,
			'name'   => $user->display_name,
			'avatar' => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
		] : null;

		// Berechtigungen für aktuellen User.
		$current_user_id = get_current_user_id();
		$is_own_note     = (int) $note['user_id'] === $current_user_id;
		$is_admin        = current_user_can( 'manage_options' );

		$note['can_edit']   = $is_own_note || $is_admin;
		$note['can_delete'] = $is_own_note || $is_admin;

		// Typen konvertieren.
		$note['id']             = (int) $note['id'];
		$note['application_id'] = $note['application_id'] ? (int) $note['application_id'] : null;
		$note['candidate_id']   = (int) $note['candidate_id'];
		$note['user_id']        = (int) $note['user_id'];
		$note['is_private']     = (bool) $note['is_private'];

		return $note;
	}

	/**
	 * Format-Array für wpdb-Operationen
	 *
	 * @param array $data Daten.
	 * @return array
	 */
	private function getFormats( array $data ): array {
		$formats = [];

		foreach ( $data as $key => $value ) {
			if ( is_int( $value ) ) {
				$formats[] = '%d';
			} elseif ( is_float( $value ) ) {
				$formats[] = '%f';
			} else {
				$formats[] = '%s';
			}
		}

		return $formats;
	}
}

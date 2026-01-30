<?php
/**
 * Custom Fields Migration
 *
 * Migriert bestehende Bewerbungen zur neuen Custom Fields Struktur.
 *
 * @package RecruitingPlaybook\Database\Migrations
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Database\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Migration für Custom Fields
 */
class CustomFieldsMigration {

	/**
	 * Migrations-Version
	 */
	public const VERSION = '1.0.0';

	/**
	 * Option-Key für Migrations-Status
	 */
	private const OPTION_KEY = 'rp_custom_fields_migration';

	/**
	 * Batch-Größe für Migration
	 */
	private const BATCH_SIZE = 100;

	/**
	 * Prüfen ob Migration notwendig
	 *
	 * @return bool
	 */
	public static function needsMigration(): bool {
		$current_version = get_option( self::OPTION_KEY, '' );
		return version_compare( $current_version, self::VERSION, '<' );
	}

	/**
	 * Migration ausführen
	 *
	 * @return array{migrated: int, errors: array, completed: bool}
	 */
	public static function run(): array {
		global $wpdb;

		$result = [
			'migrated'  => 0,
			'errors'    => [],
			'completed' => false,
		];

		$table = $wpdb->prefix . 'rp_applications';

		// Anwendungen ohne custom_fields finden (NULL oder leer).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$applications = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, candidate_id, job_id, cover_letter, created_at
				FROM {$table}
				WHERE (custom_fields IS NULL OR custom_fields = '' OR custom_fields = '[]' OR custom_fields = '{}')
				AND deleted_at IS NULL
				LIMIT %d",
				self::BATCH_SIZE
			),
			ARRAY_A
		);

		if ( empty( $applications ) ) {
			// Migration abgeschlossen.
			update_option( self::OPTION_KEY, self::VERSION );
			$result['completed'] = true;
			return $result;
		}

		foreach ( $applications as $app ) {
			$migration_result = self::migrateApplication( (int) $app['id'], $app );

			if ( true === $migration_result ) {
				++$result['migrated'];
			} else {
				$result['errors'][] = [
					'id'    => $app['id'],
					'error' => $migration_result,
				];
			}
		}

		// Prüfen ob noch mehr zu migrieren ist.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$remaining = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table}
			WHERE (custom_fields IS NULL OR custom_fields = '' OR custom_fields = '[]' OR custom_fields = '{}')
			AND deleted_at IS NULL"
		);

		if ( 0 === (int) $remaining ) {
			update_option( self::OPTION_KEY, self::VERSION );
			$result['completed'] = true;
		}

		return $result;
	}

	/**
	 * Einzelne Bewerbung migrieren
	 *
	 * @param int   $application_id Bewerbungs-ID.
	 * @param array $app            Bewerbungsdaten.
	 * @return true|string True bei Erfolg, Fehlermeldung bei Fehler.
	 */
	private static function migrateApplication( int $application_id, array $app ): true|string {
		global $wpdb;

		$custom_fields = [];

		// Legacy-Daten aus Kandidat laden (falls vorhanden).
		$candidate_data = self::getLegacyCandidateData( (int) $app['candidate_id'] );
		if ( ! empty( $candidate_data ) ) {
			$custom_fields = array_merge( $custom_fields, $candidate_data );
		}

		// Falls keine Legacy-Daten vorhanden, leeres Object speichern.
		if ( empty( $custom_fields ) ) {
			$custom_fields = new \stdClass();
		}

		$json = wp_json_encode( $custom_fields, JSON_UNESCAPED_UNICODE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$wpdb->prefix . 'rp_applications',
			[
				'custom_fields' => $json,
				'updated_at'    => current_time( 'mysql' ),
			],
			[ 'id' => $application_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		if ( false === $updated ) {
			return $wpdb->last_error ?: 'Unknown database error';
		}

		// Aktivitätslog.
		do_action( 'rp_application_migrated', $application_id, $custom_fields );

		return true;
	}

	/**
	 * Legacy-Kandidatendaten abrufen
	 *
	 * Holt zusätzliche Felder aus dem Kandidaten-Datensatz,
	 * die eventuell in Custom Fields übernommen werden sollen.
	 *
	 * @param int $candidate_id Kandidaten-ID.
	 * @return array
	 */
	private static function getLegacyCandidateData( int $candidate_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$candidate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT notes, source FROM {$wpdb->prefix}rp_candidates WHERE id = %d",
				$candidate_id
			),
			ARRAY_A
		);

		if ( ! $candidate ) {
			return [];
		}

		$legacy_data = [];

		// Notes könnten zusätzliche Infos enthalten.
		if ( ! empty( $candidate['notes'] ) ) {
			$legacy_data['legacy_notes'] = $candidate['notes'];
		}

		return $legacy_data;
	}

	/**
	 * Migrations-Status abrufen
	 *
	 * @return array{version: string, needs_migration: bool, pending: int}
	 */
	public static function getStatus(): array {
		global $wpdb;

		$table           = $wpdb->prefix . 'rp_applications';
		$current_version = get_option( self::OPTION_KEY, '' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pending = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table}
			WHERE (custom_fields IS NULL OR custom_fields = '' OR custom_fields = '[]' OR custom_fields = '{}')
			AND deleted_at IS NULL"
		);

		return [
			'version'         => $current_version ?: 'nicht migriert',
			'target_version'  => self::VERSION,
			'needs_migration' => version_compare( $current_version, self::VERSION, '<' ),
			'pending'         => (int) $pending,
		];
	}

	/**
	 * Migration zurücksetzen (für Tests/Debug)
	 *
	 * @return bool
	 */
	public static function reset(): bool {
		return delete_option( self::OPTION_KEY );
	}
}

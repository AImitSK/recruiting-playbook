<?php
/**
 * Datenbank-Migrationen verwalten
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Database;

/**
 * Datenbank-Migrationen verwalten
 */
class Migrator {

	private const SCHEMA_VERSION = '1.1.0';
	private const SCHEMA_OPTION  = 'rp_db_version';

	/**
	 * Tabellen erstellen/aktualisieren
	 */
	public function createTables(): void {
		$current_version = get_option( self::SCHEMA_OPTION, '0' );

		// Nur wenn Update nötig.
		if ( version_compare( $current_version, self::SCHEMA_VERSION, '>=' ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Alle Tabellen erstellen/aktualisieren.
		dbDelta( Schema::getCandidatesTableSql() );
		dbDelta( Schema::getApplicationsTableSql() );
		dbDelta( Schema::getDocumentsTableSql() );
		dbDelta( Schema::getActivityLogTableSql() );

		// Version speichern.
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION );

		// Log erstellen.
		$this->log( 'Database migrated to version ' . self::SCHEMA_VERSION );
	}

	/**
	 * Tabellen existieren prüfen
	 *
	 * @return bool
	 */
	public function tablesExist(): bool {
		global $wpdb;

		$tables = Schema::getTables();

		foreach ( $tables as $table ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table
				)
			);

			if ( $result !== $table ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Tabellen löschen (für Deinstallation)
	 */
	public function dropTables(): void {
		global $wpdb;

		$tables = Schema::getTables();

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( self::SCHEMA_OPTION );
	}

	/**
	 * Migration loggen
	 *
	 * @param string $message Log message.
	 */
	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[Recruiting Playbook] ' . $message );
		}
	}
}

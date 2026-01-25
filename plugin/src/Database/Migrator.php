<?php
/**
 * Datenbank-Migrationen verwalten
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Datenbank-Migrationen verwalten
 */
class Migrator {

	private const SCHEMA_VERSION = '1.3.0';
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
		dbDelta( Schema::getNotesTableSql() );
		dbDelta( Schema::getRatingsTableSql() );
		dbDelta( Schema::getTalentPoolTableSql() );

		// Spezielle Migrationen für bestehende Installationen.
		$this->runMigrations( $current_version );

		// Version speichern.
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION );

		// Log erstellen.
		$this->log( 'Database migrated to version ' . self::SCHEMA_VERSION );
	}

	/**
	 * Spezielle Migrationen ausführen
	 *
	 * @param string $from_version Version von der migriert wird.
	 */
	private function runMigrations( string $from_version ): void {
		// Migration 1.2.0: kanban_position Spalte hinzufügen.
		if ( version_compare( $from_version, '1.2.0', '<' ) ) {
			$this->migrateToKanbanPosition();
		}

		// Migration 1.3.0: Activity Log meta Spalte + Pro-Features Tabellen.
		if ( version_compare( $from_version, '1.3.0', '<' ) ) {
			$this->migrateToActivityLogMeta();
			$this->migrateToApplicationsDeletedAt();
		}
	}

	/**
	 * Migration: kanban_position Spalte hinzufügen
	 */
	private function migrateToKanbanPosition(): void {
		global $wpdb;

		$table = Schema::getTables()['applications'];

		// Prüfen ob Spalte bereits existiert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table} LIKE %s",
				'kanban_position'
			)
		);

		if ( empty( $column_exists ) ) {
			// Spalte hinzufügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN kanban_position int(11) DEFAULT 0 AFTER status" );

			// Index hinzufügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX kanban_sort (status, kanban_position)" );

			$this->log( 'Added kanban_position column to applications table' );
		}
	}

	/**
	 * Migration: Activity Log meta Spalte hinzufügen
	 */
	private function migrateToActivityLogMeta(): void {
		global $wpdb;

		$table = Schema::getTables()['activity_log'];

		// Prüfen ob meta Spalte bereits existiert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table} LIKE %s",
				'meta'
			)
		);

		if ( empty( $column_exists ) ) {
			// Spalte hinzufügen nach message.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN meta longtext DEFAULT NULL AFTER message" );

			$this->log( 'Added meta column to activity_log table' );
		}
	}

	/**
	 * Migration: Applications deleted_at Spalte hinzufügen
	 */
	private function migrateToApplicationsDeletedAt(): void {
		global $wpdb;

		$table = Schema::getTables()['applications'];

		// Prüfen ob deleted_at Spalte bereits existiert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table} LIKE %s",
				'deleted_at'
			)
		);

		if ( empty( $column_exists ) ) {
			// Spalte hinzufügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN deleted_at datetime DEFAULT NULL AFTER updated_at" );

			// Index hinzufügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX deleted_at (deleted_at)" );

			$this->log( 'Added deleted_at column to applications table' );
		}
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

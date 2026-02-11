<?php
/**
 * System Status Service - Integritäts-Checks und Systemstatus
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Service für Systemstatus und Integritäts-Checks
 */
class SystemStatusService {

	/**
	 * Vollständigen Systemstatus abrufen
	 *
	 * @return array
	 */
	public function getStatus(): array {
		$checks = [
			'database'         => $this->checkDatabase(),
			'uploads'          => $this->checkUploads(),
			'cron'             => $this->checkCron(),
			'orphaned_data'    => $this->checkOrphanedData(),
			'license'          => $this->checkLicense(),
			'action_scheduler' => $this->checkActionScheduler(),
		];

		$overall_status = $this->determineOverallStatus( $checks );

		return [
			'status'          => $overall_status,
			'checks'          => $checks,
			'recommendations' => $this->getRecommendations( $checks ),
			'plugin_version'  => defined( 'RP_VERSION' ) ? RP_VERSION : '1.0.0',
			'php_version'     => PHP_VERSION,
			'wp_version'      => get_bloginfo( 'version' ),
			'checked_at'      => current_time( 'c' ),
		];
	}

	/**
	 * Datenbank-Tabellen prüfen
	 *
	 * @return array
	 */
	private function checkDatabase(): array {
		global $wpdb;

		$required_tables = [
			'candidates',
			'applications',
			'documents',
			'activity_log',
			'notes',
			'ratings',
			'talent_pool',
			'email_templates',
			'email_log',
			'signatures',
			'job_assignments',
			'stats_cache',
		];

		$tables = Schema::getTables();
		$missing = [];

		foreach ( $required_tables as $table_key ) {
			if ( ! isset( $tables[ $table_key ] ) ) {
				continue;
			}

			$full_name = $tables[ $table_key ];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $full_name )
			);

			if ( ! $exists ) {
				$missing[] = $table_key;
			}
		}

		if ( empty( $missing ) ) {
			return [
				'status'  => 'ok',
				'message' => __( 'All tables present', 'recruiting-playbook' ),
				'details' => [
					'tables_expected' => count( $required_tables ),
					'tables_found'    => count( $required_tables ),
				],
			];
		}

		return [
			'status'  => 'error',
			'message' => sprintf(
				/* translators: %d: Number of missing tables */
				__( '%d table(s) missing', 'recruiting-playbook' ),
				count( $missing )
			),
			'details' => [
				'tables_expected' => count( $required_tables ),
				'tables_found'    => count( $required_tables ) - count( $missing ),
				'missing'         => $missing,
			],
		];
	}

	/**
	 * Upload-Verzeichnis prüfen
	 *
	 * @return array
	 */
	private function checkUploads(): array {
		$upload_dir = wp_upload_dir();
		$rp_dir = $upload_dir['basedir'] . '/recruiting-playbook/';

		if ( ! file_exists( $rp_dir ) ) {
			// Versuchen zu erstellen.
			wp_mkdir_p( $rp_dir );
		}

		$writable = is_writable( $rp_dir );
		$files_count = 0;
		$total_size = 0;

		if ( is_dir( $rp_dir ) ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $rp_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
			);

			foreach ( $iterator as $file ) {
				if ( $file->isFile() ) {
					$files_count++;
					$total_size += $file->getSize();
				}
			}
		}

		return [
			'status'  => $writable ? 'ok' : 'error',
			'message' => $writable
				? __( 'Upload directory is writable', 'recruiting-playbook' )
				: __( 'Upload directory is not writable', 'recruiting-playbook' ),
			'details' => [
				'path'        => $rp_dir,
				'writable'    => $writable,
				'files_count' => $files_count,
				'total_size'  => size_format( $total_size ),
			],
		];
	}

	/**
	 * Cron-Jobs prüfen
	 *
	 * @return array
	 */
	private function checkCron(): array {
		$next_cleanup = wp_next_scheduled( 'rp_daily_cleanup' );
		$last_run = get_option( 'rp_last_cleanup_run', 0 );

		$cron_working = $next_cleanup > 0 || defined( 'DISABLE_WP_CRON' );

		return [
			'status'  => $cron_working ? 'ok' : 'warning',
			'message' => $cron_working
				? __( 'Cron jobs active', 'recruiting-playbook' )
				: __( 'Cron jobs not scheduled', 'recruiting-playbook' ),
			'details' => [
				'next_cleanup' => $next_cleanup ? gmdate( 'c', $next_cleanup ) : null,
				'last_run'     => $last_run ? gmdate( 'c', $last_run ) : null,
				'wp_cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			],
		];
	}

	/**
	 * Verwaiste Daten prüfen
	 *
	 * @return array
	 */
	private function checkOrphanedData(): array {
		global $wpdb;

		$tables = Schema::getTables();

		// Dokumente ohne zugehörige Bewerbung.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$orphaned_docs = (int) $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$tables['documents']} d
			LEFT JOIN {$tables['applications']} a ON d.application_id = a.id
			WHERE a.id IS NULL AND d.application_id IS NOT NULL"
		);

		// Bewerbungen ohne zugehörige Stelle.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$orphaned_apps = (int) $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$tables['applications']} a
			LEFT JOIN {$wpdb->posts} p ON a.job_id = p.ID
			WHERE p.ID IS NULL"
		);

		$total_orphaned = $orphaned_docs + $orphaned_apps;

		if ( $total_orphaned === 0 ) {
			return [
				'status'  => 'ok',
				'message' => __( 'No orphaned data', 'recruiting-playbook' ),
				'details' => [
					'orphaned_documents'    => 0,
					'orphaned_applications' => 0,
				],
			];
		}

		return [
			'status'  => 'warning',
			'message' => sprintf(
				/* translators: %d: Number of orphaned entries */
				__( '%d orphaned entries found', 'recruiting-playbook' ),
				$total_orphaned
			),
			'details' => [
				'orphaned_documents'    => $orphaned_docs,
				'orphaned_applications' => $orphaned_apps,
			],
		];
	}

	/**
	 * Lizenz-Status prüfen (nutzt Freemius)
	 *
	 * @return array
	 */
	private function checkLicense(): array {
		// Prüfen ob Freemius-Helper verfügbar ist.
		if ( ! function_exists( 'rp_tier' ) ) {
			return [
				'status'  => 'ok',
				'message' => __( 'Free version active', 'recruiting-playbook' ),
				'details' => [
					'type' => 'free',
				],
			];
		}

		$tier = rp_tier();

		if ( $tier === 'FREE' ) {
			return [
				'status'  => 'ok',
				'message' => __( 'Free version active', 'recruiting-playbook' ),
				'details' => [
					'type' => 'free',
				],
			];
		}

		// Freemius bietet automatisch Lizenz-Infos.
		$is_paying = function_exists( 'rp_fs' ) ? rp_fs()->is_paying() : false;

		return [
			'status'  => $is_paying ? 'ok' : 'warning',
			'message' => $is_paying
				? sprintf(
					/* translators: %s: License tier */
					__( '%s license active', 'recruiting-playbook' ),
					$tier
				)
				: __( 'License invalid or expired', 'recruiting-playbook' ),
			'details' => [
				'type'   => strtolower( $tier ),
				'valid'  => $is_paying,
				'domain' => wp_parse_url( home_url(), PHP_URL_HOST ),
			],
		];
	}

	/**
	 * Action Scheduler prüfen
	 *
	 * @return array
	 */
	private function checkActionScheduler(): array {
		// Prüfen ob Action Scheduler verfügbar ist.
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return [
				'status'  => 'warning',
				'message' => __( 'Action Scheduler not available', 'recruiting-playbook' ),
				'details' => [
					'available' => false,
				],
			];
		}

		// Pending Actions zählen.
		$pending = as_get_scheduled_actions(
			[
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 0,
				'group'    => 'recruiting-playbook',
			],
			'ids'
		);

		// Running Actions zählen.
		$running = as_get_scheduled_actions(
			[
				'status'   => \ActionScheduler_Store::STATUS_RUNNING,
				'per_page' => 0,
				'group'    => 'recruiting-playbook',
			],
			'ids'
		);

		// Failed Actions zählen.
		$failed = as_get_scheduled_actions(
			[
				'status'   => \ActionScheduler_Store::STATUS_FAILED,
				'per_page' => 0,
				'group'    => 'recruiting-playbook',
			],
			'ids'
		);

		$pending_count = is_array( $pending ) ? count( $pending ) : 0;
		$running_count = is_array( $running ) ? count( $running ) : 0;
		$failed_count = is_array( $failed ) ? count( $failed ) : 0;

		$status = 'ok';
		$message = __( 'Action Scheduler running', 'recruiting-playbook' );

		if ( $failed_count > 0 ) {
			$status = 'warning';
			$message = sprintf(
				/* translators: %d: Number of failed actions */
				__( '%d failed actions', 'recruiting-playbook' ),
				$failed_count
			);
		}

		return [
			'status'  => $status,
			'message' => $message,
			'details' => [
				'available' => true,
				'pending'   => $pending_count,
				'running'   => $running_count,
				'failed'    => $failed_count,
			],
		];
	}

	/**
	 * Gesamtstatus ermitteln
	 *
	 * @param array $checks Alle Checks.
	 * @return string
	 */
	private function determineOverallStatus( array $checks ): string {
		$has_error = false;
		$has_warning = false;

		foreach ( $checks as $check ) {
			if ( $check['status'] === 'error' ) {
				$has_error = true;
			} elseif ( $check['status'] === 'warning' ) {
				$has_warning = true;
			}
		}

		if ( $has_error ) {
			return 'unhealthy';
		}

		if ( $has_warning ) {
			return 'degraded';
		}

		return 'healthy';
	}

	/**
	 * Empfehlungen basierend auf Checks
	 *
	 * @param array $checks Alle Checks.
	 * @return array
	 */
	private function getRecommendations( array $checks ): array {
		$recommendations = [];

		// Datenbank-Probleme.
		if ( $checks['database']['status'] === 'error' ) {
			$recommendations[] = [
				'type'    => 'repair',
				'message' => __( 'Please deactivate and reactivate the plugin to create missing tables.', 'recruiting-playbook' ),
				'action'  => 'reactivate_plugin',
			];
		}

		// Upload-Verzeichnis nicht beschreibbar.
		if ( $checks['uploads']['status'] === 'error' ) {
			$recommendations[] = [
				'type'    => 'permission',
				'message' => __( 'Please set write permissions for the upload directory.', 'recruiting-playbook' ),
				'action'  => 'fix_permissions',
			];
		}

		// Verwaiste Daten.
		if ( $checks['orphaned_data']['status'] === 'warning' ) {
			$orphaned_docs = $checks['orphaned_data']['details']['orphaned_documents'] ?? 0;
			$orphaned_apps = $checks['orphaned_data']['details']['orphaned_applications'] ?? 0;

			if ( $orphaned_docs > 0 ) {
				$recommendations[] = [
					'type'    => 'cleanup',
					'message' => sprintf(
						/* translators: %d: Number of orphaned documents */
						__( '%d orphaned documents can be deleted', 'recruiting-playbook' ),
						$orphaned_docs
					),
					'action'  => 'cleanup_orphaned_documents',
				];
			}

			if ( $orphaned_apps > 0 ) {
				$recommendations[] = [
					'type'    => 'cleanup',
					'message' => sprintf(
						/* translators: %d: Number of orphaned applications */
						__( '%d orphaned applications can be deleted', 'recruiting-playbook' ),
						$orphaned_apps
					),
					'action'  => 'cleanup_orphaned_applications',
				];
			}
		}

		// Fehlgeschlagene Actions.
		if ( isset( $checks['action_scheduler']['details']['failed'] ) && $checks['action_scheduler']['details']['failed'] > 0 ) {
			$recommendations[] = [
				'type'    => 'review',
				'message' => __( 'Please review the failed background jobs.', 'recruiting-playbook' ),
				'action'  => 'review_failed_actions',
			];
		}

		return $recommendations;
	}

	/**
	 * Verwaiste Dokumente bereinigen
	 *
	 * @return int Anzahl gelöschter Einträge.
	 */
	public function cleanupOrphanedDocuments(): int {
		global $wpdb;

		$tables = Schema::getTables();

		// Verwaiste Dokumente finden.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$orphaned = $wpdb->get_results(
			"SELECT d.id, d.file_path
			FROM {$tables['documents']} d
			LEFT JOIN {$tables['applications']} a ON d.application_id = a.id
			WHERE a.id IS NULL AND d.application_id IS NOT NULL",
			ARRAY_A
		);

		$deleted = 0;
		foreach ( $orphaned as $doc ) {
			// Datei löschen falls vorhanden.
			if ( ! empty( $doc['file_path'] ) && file_exists( $doc['file_path'] ) ) {
				wp_delete_file( $doc['file_path'] );
			}

			// DB-Eintrag löschen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$tables['documents'],
				[ 'id' => $doc['id'] ],
				[ '%d' ]
			);
			$deleted++;
		}

		return $deleted;
	}

	/**
	 * Verwaiste Bewerbungen bereinigen
	 *
	 * @return int Anzahl gelöschter Einträge.
	 */
	public function cleanupOrphanedApplications(): int {
		global $wpdb;

		$tables = Schema::getTables();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			"DELETE a FROM {$tables['applications']} a
			LEFT JOIN {$wpdb->posts} p ON a.job_id = p.ID
			WHERE p.ID IS NULL"
		);

		return $result !== false ? $result : 0;
	}
}

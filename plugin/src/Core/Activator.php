<?php
/**
 * Plugin-Aktivierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Core;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Core\RoleManager;
use RecruitingPlaybook\Database\Migrator;
use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Plugin-Aktivierung
 */
class Activator {

	/**
	 * Bei Aktivierung ausführen
	 */
	public static function activate(): void {
		// 0. Legacy-Daten migrieren (rp_* → recpl_*) bevor irgendetwas anderes passiert.
		self::migrateLegacyPrefixes();

		// 1. Datenbank-Tabellen erstellen.
		$migrator = new Migrator();
		$migrator->createTables();

		// 2. Custom Post Type registrieren (für Rewrite Rules).
		$job_listing = new JobListing();
		$job_listing->register();

		// 3. Rewrite Rules flushen.
		flush_rewrite_rules();

		// 4. Standard-Optionen setzen.
		self::setDefaultOptions();

		// 5. Custom Rollen und Capabilities registrieren.
		RoleManager::register();

		// 6. Aktivierungs-Marker setzen (für Setup-Wizard).
		update_option( 'recpl_activation_redirect', true );

		// 7. Version speichern.
		update_option( 'recpl_version', RECPL_VERSION );
	}

	/**
	 * Legacy-Prefix-Migration: rp_* → recpl_*
	 *
	 * Migriert Options, Post-Meta, User-Meta von altem rp_-Prefix
	 * zu recpl_-Prefix. Idempotent (mehrfacher Aufruf schadet nicht).
	 *
	 * Hintergrund: WordPress.org verlangt min. 4 Zeichen Prefix.
	 * Vor v1.9.0 wurde rp_ (2 Zeichen) verwendet.
	 */
	public static function migrateLegacyPrefixes(): void {
		global $wpdb;

		// Options.
		$option_map = [
			'rp_settings'                   => 'recpl_settings',
			'rp_integrations'               => 'recpl_integrations',
			'rp_design_settings'            => 'recpl_design_settings',
			'rp_role_capabilities'          => 'recpl_role_capabilities',
			'rp_auto_email_settings'        => 'recpl_auto_email_settings',
			'rp_version'                    => 'recpl_version',
			'rp_activation_redirect'        => 'recpl_activation_redirect',
			'rp_wizard_completed'           => 'recpl_wizard_completed',
			'rp_employment_types_installed' => 'recpl_employment_types_installed',
			'rp_privacy_policy_version'     => 'recpl_privacy_policy_version',
			'rp_keep_data_on_uninstall'     => 'recpl_keep_data_on_uninstall',
			'rp_talent_pool_retention'      => 'recpl_talent_pool_retention',
			'rp_last_cleanup_run'           => 'recpl_last_cleanup_run',
			'rp_db_version'                 => 'recpl_db_version',
		];

		foreach ( $option_map as $old => $new ) {
			$old_value = get_option( $old, null );
			if ( null === $old_value ) {
				continue;
			}
			if ( false === get_option( $new, false ) ) {
				update_option( $new, $old_value );
			}
			delete_option( $old );
		}

		// Post-Meta: _rp_* → _recpl_* (Bulk-Update).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$wpdb->query(
			"UPDATE {$wpdb->postmeta}
			SET meta_key = CONCAT('_recpl_', SUBSTRING(meta_key, 5))
			WHERE meta_key LIKE '\\_rp\\_%'"
		);

		// User-Meta: rp_default_signature_id → recpl_default_signature_id.
		// Source-Key-Literal extrahiert, damit ein zukünftiger Bulk-Rename den Source nicht versehentlich umbenennt.
		$legacy_user_meta_key = 'rp' . '_default_signature_id';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$wpdb->usermeta,
			[ 'meta_key' => 'recpl_default_signature_id' ],
			[ 'meta_key' => $legacy_user_meta_key ]
		);

		// Alte Cron-Schedules abbestellen (werden bei Bedarf unter neuem Namen wiederhergestellt).
		foreach ( [ 'rp_slack_retry_cron', 'rp_teams_retry_cron' ] as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			while ( false !== $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
				$timestamp = wp_next_scheduled( $hook );
			}
		}
	}

	/**
	 * Standard-Optionen setzen
	 */
	private static function setDefaultOptions(): void {
		$defaults = [
			'recpl_settings' => [
				'company_name'       => get_bloginfo( 'name' ),
				'notification_email' => get_option( 'admin_email' ),
				'privacy_url'        => get_privacy_policy_url(),
				'jobs_per_page'      => 10,
				'jobs_slug'          => 'jobs',
				'enable_schema'      => true,
			],
		];

		foreach ( $defaults as $option => $value ) {
			if ( false === get_option( $option ) ) {
				add_option( $option, $value );
			}
		}

		// DSGVO: Datenschutzrichtlinien-Version (für Consent-Tracking).
		if ( false === get_option( 'recpl_privacy_policy_version' ) ) {
			add_option( 'recpl_privacy_policy_version', '1.0' );
		}
	}
}

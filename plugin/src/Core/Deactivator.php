<?php
/**
 * Plugin-Deaktivierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Core;

use RecruitingPlaybook\Core\RoleManager;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin-Deaktivierung
 */
class Deactivator {

	/**
	 * Bei Deaktivierung ausführen
	 */
	public static function deactivate(): void {
		// Rewrite Rules flushen.
		flush_rewrite_rules();

		// Geplante Tasks entfernen.
		self::clearScheduledTasks();

		// Custom Rollen und Capabilities entfernen.
		RoleManager::unregister();

		// Legacy Capabilities entfernen (für alte Installationen).
		self::removeCapabilities();
	}

	/**
	 * Geplante WP-Cron Tasks entfernen
	 */
	private static function clearScheduledTasks(): void {
		$hooks = [
			'rp_daily_cleanup',
			'rp_license_check',
		];

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Custom Capabilities entfernen
	 *
	 * Entfernt alle Plugin-spezifischen Capabilities von Rollen.
	 * Wichtig für saubere Deaktivierung und Sicherheit.
	 */
	private static function removeCapabilities(): void {
		$admin  = get_role( 'administrator' );
		$editor = get_role( 'editor' );

		// Alle Plugin-Capabilities (mit rp_ Präfix).
		$all_capabilities = [
			// Job Listing Capabilities.
			'edit_job_listing',
			'read_job_listing',
			'delete_job_listing',
			'edit_job_listings',
			'edit_others_job_listings',
			'publish_job_listings',
			'read_private_job_listings',
			'delete_job_listings',
			'delete_private_job_listings',
			'delete_published_job_listings',
			'delete_others_job_listings',
			'edit_private_job_listings',
			'edit_published_job_listings',
			// Recruiting Capabilities.
			'rp_manage_recruiting',
			'rp_view_applications',
			'rp_edit_applications',
			'rp_delete_applications',
			// Pro-Features: Applicant Management.
			'rp_view_notes',
			'rp_create_notes',
			'rp_edit_own_notes',
			'rp_edit_others_notes',
			'rp_delete_notes',
			'rp_rate_applications',
			'rp_manage_talent_pool',
			'rp_view_activity_log',
			// Pro-Features: E-Mail-System (CRUD-Granularität).
			'rp_read_email_templates',
			'rp_create_email_templates',
			'rp_edit_email_templates',
			'rp_delete_email_templates',
			'rp_send_emails',
			'rp_view_email_log',
			// Pro-Features: Rollen-Verwaltung.
			'rp_manage_roles',
			'rp_assign_jobs',
			// Legacy: rp_manage_email_templates (vor CRUD-Granularität).
			'rp_manage_email_templates',
			// Legacy Capabilities (ohne Präfix - für Migration).
			'manage_recruiting',
			'view_applications',
			'edit_applications',
			'delete_applications',
			'view_notes',
			'create_notes',
			'edit_own_notes',
			'edit_others_notes',
			'delete_notes',
			'rate_applications',
			'manage_talent_pool',
			'view_activity_log',
		];

		if ( $admin ) {
			foreach ( $all_capabilities as $cap ) {
				$admin->remove_cap( $cap );
			}
		}

		if ( $editor ) {
			foreach ( $all_capabilities as $cap ) {
				$editor->remove_cap( $cap );
			}
		}
	}
}

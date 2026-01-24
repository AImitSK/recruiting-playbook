<?php
/**
 * Plugin-Aktivierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Core;

defined( 'ABSPATH' ) || exit;

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

		// 5. Capabilities hinzufügen.
		self::addCapabilities();

		// 6. Aktivierungs-Marker setzen (für Setup-Wizard).
		update_option( 'rp_activation_redirect', true );

		// 7. Version speichern.
		update_option( 'rp_version', RP_VERSION );
	}

	/**
	 * Standard-Optionen setzen
	 */
	private static function setDefaultOptions(): void {
		$defaults = [
			'rp_settings' => [
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
	}

	/**
	 * Custom Capabilities hinzufügen
	 */
	private static function addCapabilities(): void {
		$admin = get_role( 'administrator' );

		if ( $admin ) {
			// Job Listing Capabilities.
			$capabilities = [
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
				'manage_recruiting',
				'view_applications',
				'edit_applications',
				'delete_applications',
			];

			foreach ( $capabilities as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}
}

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

}

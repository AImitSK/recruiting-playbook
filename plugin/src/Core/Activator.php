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
	 *
	 * Job Listing Capabilities (nur Admin):
	 * - edit_job_listing, read_job_listing, delete_job_listing, etc.
	 *
	 * Recruiting Capabilities (alle mit rp_ Präfix):
	 * - rp_manage_recruiting: Zugriff auf Recruiting-Dashboard
	 * - rp_view_applications: Bewerbungen anzeigen
	 * - rp_edit_applications: Bewerbungen bearbeiten
	 * - rp_delete_applications: Bewerbungen löschen
	 *
	 * Pro-Features: Applicant Management
	 * - rp_view_notes: Notizen lesen
	 * - rp_create_notes: Notizen erstellen
	 * - rp_edit_own_notes: Eigene Notizen bearbeiten
	 * - rp_edit_others_notes: Fremde Notizen bearbeiten (nur Admin)
	 * - rp_delete_notes: Notizen löschen
	 * - rp_rate_applications: Bewerbungen bewerten
	 * - rp_manage_talent_pool: Talent-Pool verwalten
	 * - rp_view_activity_log: Aktivitätslog einsehen
	 *
	 * Pro-Features: E-Mail-System (CRUD-Granularität)
	 * - rp_read_email_templates: E-Mail-Templates anzeigen/lesen
	 * - rp_create_email_templates: E-Mail-Templates erstellen
	 * - rp_edit_email_templates: E-Mail-Templates bearbeiten
	 * - rp_delete_email_templates: E-Mail-Templates löschen (nur Admin)
	 * - rp_send_emails: E-Mails an Bewerber senden
	 * - rp_view_email_log: E-Mail-Historie einsehen
	 */
	private static function addCapabilities(): void {
		$admin  = get_role( 'administrator' );
		$editor = get_role( 'editor' );

		// Job Listing Capabilities (nur Admin).
		$job_capabilities = [
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
		];

		// Recruiting Capabilities (Admin bekommt alle).
		// Alle Capabilities verwenden rp_ Präfix zur Vermeidung von Konflikten.
		$recruiting_capabilities = [
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
		];

		// Editor/Recruiter Capabilities (Subset).
		// Editor kann Templates lesen/bearbeiten (nicht erstellen/löschen), E-Mails senden und Log einsehen.
		$recruiter_capabilities = [
			'rp_view_applications',
			'rp_edit_applications',
			'rp_view_notes',
			'rp_create_notes',
			'rp_edit_own_notes',
			'rp_rate_applications',
			'rp_manage_talent_pool',
			'rp_view_activity_log',
			// E-Mail: Recruiter dürfen Templates lesen/bearbeiten, E-Mails senden und Log einsehen.
			// Kein Erstellen/Löschen von Templates - das bleibt Admin-Recht.
			'rp_read_email_templates',
			'rp_edit_email_templates',
			'rp_send_emails',
			'rp_view_email_log',
		];

		if ( $admin ) {
			foreach ( array_merge( $job_capabilities, $recruiting_capabilities ) as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		if ( $editor ) {
			foreach ( $recruiter_capabilities as $cap ) {
				$editor->add_cap( $cap );
			}
		}
	}
}

<?php
/**
 * Rollen-Verwaltung
 *
 * Registriert Custom Rollen für das Recruiting-System.
 * Pro-Feature: Benutzerrollen-Management
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Rollen-Verwaltung
 */
class RoleManager {

	/**
	 * Rollen bei Plugin-Aktivierung registrieren
	 */
	public static function register(): void {
		// Recruiter-Rolle erstellen.
		add_role(
			'rp_recruiter',
			__( 'Recruiter', 'recruiting-playbook' ),
			[
				'read'         => true,
				'upload_files' => true,
			]
		);

		// Hiring Manager-Rolle erstellen.
		add_role(
			'rp_hiring_manager',
			__( 'Hiring Manager', 'recruiting-playbook' ),
			[
				'read' => true,
			]
		);

		// Capabilities zuweisen.
		self::assignCapabilities();
	}

	/**
	 * Rollen bei Plugin-Deaktivierung entfernen
	 */
	public static function unregister(): void {
		// Custom Capabilities von Standard-Rollen entfernen.
		$admin  = get_role( 'administrator' );
		$editor = get_role( 'editor' );

		if ( $admin ) {
			foreach ( self::getAllCapabilities() as $cap ) {
				$admin->remove_cap( $cap );
			}
		}

		if ( $editor ) {
			foreach ( self::getAllCapabilities() as $cap ) {
				$editor->remove_cap( $cap );
			}
		}

		// Custom Rollen entfernen.
		remove_role( 'rp_recruiter' );
		remove_role( 'rp_hiring_manager' );
	}

	/**
	 * Capabilities basierend auf Konfiguration zuweisen
	 */
	public static function assignCapabilities(): void {
		$config = get_option( 'rp_role_capabilities', self::getDefaults() );

		// Capabilities für Custom Rollen zuweisen.
		foreach ( $config as $role_slug => $capabilities ) {
			$role = get_role( $role_slug );
			if ( ! $role ) {
				continue;
			}

			foreach ( $capabilities as $cap => $granted ) {
				if ( $granted ) {
					$role->add_cap( $cap );
				} else {
					$role->remove_cap( $cap );
				}
			}

			// Basis-Capability für Dashboard-Zugriff.
			if ( ! empty( $capabilities['rp_view_applications'] ) ) {
				$role->add_cap( 'rp_manage_recruiting' );
			}
		}

		// Administrator erhält IMMER alle Capabilities.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			// Recruiting Capabilities.
			foreach ( self::getAllCapabilities() as $cap ) {
				$admin->add_cap( $cap );
			}
			// Job Listing Capabilities für Custom Post Type.
			foreach ( self::getJobListingCapabilities() as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		// Editor erhält Recruiter-ähnliche Capabilities (Abwärtskompatibilität).
		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor_caps = [
				'rp_manage_recruiting',
				'rp_view_applications',
				'rp_edit_applications',
				'rp_view_notes',
				'rp_create_notes',
				'rp_edit_own_notes',
				'rp_rate_applications',
				'rp_manage_talent_pool',
				'rp_view_activity_log',
				'rp_read_email_templates',
				'rp_edit_email_templates',
				'rp_send_emails',
				'rp_view_email_log',
				'rp_view_stats',
				'rp_view_advanced_stats',
				'rp_export_data',
			];
			foreach ( $editor_caps as $cap ) {
				$editor->add_cap( $cap );
			}
		}
	}

	/**
	 * Alle verfügbaren Capabilities
	 *
	 * @return array<string>
	 */
	public static function getAllCapabilities(): array {
		return [
			// Basis.
			'rp_manage_recruiting',
			'rp_view_applications',
			'rp_edit_applications',
			'rp_delete_applications',

			// Notizen.
			'rp_view_notes',
			'rp_create_notes',
			'rp_edit_own_notes',
			'rp_edit_others_notes',
			'rp_delete_notes',

			// Bewertungen & Talent-Pool.
			'rp_rate_applications',
			'rp_manage_talent_pool',
			'rp_view_activity_log',

			// E-Mail.
			'rp_read_email_templates',
			'rp_create_email_templates',
			'rp_edit_email_templates',
			'rp_delete_email_templates',
			'rp_send_emails',
			'rp_view_email_log',

			// Rollen-Verwaltung (nur Admin).
			'rp_manage_roles',
			'rp_assign_jobs',

			// Reporting & Dashboard.
			'rp_view_stats',
			'rp_view_advanced_stats',
			'rp_export_data',
			'rp_view_system_status',
			'rp_run_cleanup',

			// Custom Fields & Forms (Pro-Feature).
			'rp_manage_forms',
		];
	}

	/**
	 * Job Listing Capabilities für Custom Post Type
	 *
	 * @return array<string>
	 */
	public static function getJobListingCapabilities(): array {
		return [
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
	}

	/**
	 * Standard-Konfiguration für Custom Rollen
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function getDefaults(): array {
		return [
			'rp_recruiter'       => [
				'rp_view_applications'      => true,
				'rp_edit_applications'      => true,
				'rp_delete_applications'    => false,
				'rp_view_notes'             => true,
				'rp_create_notes'           => true,
				'rp_edit_own_notes'         => true,
				'rp_edit_others_notes'      => false,
				'rp_delete_notes'           => false,
				'rp_rate_applications'      => true,
				'rp_manage_talent_pool'     => true,
				'rp_view_activity_log'      => true,
				'rp_read_email_templates'   => true,
				'rp_create_email_templates' => false,
				'rp_edit_email_templates'   => true,
				'rp_delete_email_templates' => false,
				'rp_send_emails'            => true,
				'rp_view_email_log'         => true,
				'rp_manage_roles'           => false,
				'rp_assign_jobs'            => false,
				'rp_view_stats'             => true,
				'rp_view_advanced_stats'    => true,
				'rp_export_data'            => true,
				'rp_view_system_status'     => false,
				'rp_run_cleanup'            => false,
				'rp_manage_forms'           => false,
			],
			'rp_hiring_manager'  => [
				'rp_view_applications'      => true,
				'rp_edit_applications'      => false,
				'rp_delete_applications'    => false,
				'rp_view_notes'             => true,
				'rp_create_notes'           => true,
				'rp_edit_own_notes'         => true,
				'rp_edit_others_notes'      => false,
				'rp_delete_notes'           => false,
				'rp_rate_applications'      => true,
				'rp_manage_talent_pool'     => false,
				'rp_view_activity_log'      => true,
				'rp_read_email_templates'   => true,
				'rp_create_email_templates' => false,
				'rp_edit_email_templates'   => false,
				'rp_delete_email_templates' => false,
				'rp_send_emails'            => false,
				'rp_view_email_log'         => false,
				'rp_manage_roles'           => false,
				'rp_assign_jobs'            => false,
				'rp_view_stats'             => true,
				'rp_view_advanced_stats'    => false,
				'rp_export_data'            => false,
				'rp_view_system_status'     => false,
				'rp_run_cleanup'            => false,
				'rp_manage_forms'           => false,
			],
		];
	}

	/**
	 * Capability-Gruppen für Admin UI
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function getCapabilityGroups(): array {
		return [
			'applications' => [
				'label'        => __( 'Applications', 'recruiting-playbook' ),
				'capabilities' => [
					'rp_view_applications'   => __( 'View applications', 'recruiting-playbook' ),
					'rp_edit_applications'   => __( 'Edit applications', 'recruiting-playbook' ),
					'rp_delete_applications' => __( 'Delete applications', 'recruiting-playbook' ),
				],
			],
			'notes'        => [
				'label'        => __( 'Notes', 'recruiting-playbook' ),
				'capabilities' => [
					'rp_view_notes'         => __( 'View notes', 'recruiting-playbook' ),
					'rp_create_notes'       => __( 'Create notes', 'recruiting-playbook' ),
					'rp_edit_own_notes'     => __( 'Edit own notes', 'recruiting-playbook' ),
					'rp_edit_others_notes'  => __( 'Edit others notes', 'recruiting-playbook' ),
					'rp_delete_notes'       => __( 'Delete notes', 'recruiting-playbook' ),
				],
			],
			'evaluation'   => [
				'label'        => __( 'Ratings & Talent Pool', 'recruiting-playbook' ),
				'capabilities' => [
					'rp_rate_applications'  => __( 'Rate applications', 'recruiting-playbook' ),
					'rp_manage_talent_pool' => __( 'Manage talent pool', 'recruiting-playbook' ),
					'rp_view_activity_log'  => __( 'View activity log', 'recruiting-playbook' ),
				],
			],
			'email'        => [
				'label'        => __( 'Email System', 'recruiting-playbook' ),
				'capabilities' => [
					'rp_read_email_templates'   => __( 'View templates', 'recruiting-playbook' ),
					'rp_create_email_templates' => __( 'Create templates', 'recruiting-playbook' ),
					'rp_edit_email_templates'   => __( 'Edit templates', 'recruiting-playbook' ),
					'rp_delete_email_templates' => __( 'Delete templates', 'recruiting-playbook' ),
					'rp_send_emails'            => __( 'Send emails', 'recruiting-playbook' ),
					'rp_view_email_log'         => __( 'View email history', 'recruiting-playbook' ),
				],
			],
			'admin'        => [
				'label'        => __( 'Administration', 'recruiting-playbook' ),
				'capabilities' => [
					'rp_manage_roles' => __( 'Manage roles', 'recruiting-playbook' ),
					'rp_assign_jobs'  => __( 'Assign jobs', 'recruiting-playbook' ),
				],
			],
			'reporting'    => [
				'label'        => __( 'Reporting & Dashboard', 'recruiting-playbook' ),
				'capabilities' => [
					'rp_view_stats'          => __( 'View statistics', 'recruiting-playbook' ),
					'rp_view_advanced_stats' => __( 'View advanced statistics', 'recruiting-playbook' ),
					'rp_export_data'         => __( 'Export data', 'recruiting-playbook' ),
					'rp_view_system_status'  => __( 'View system status', 'recruiting-playbook' ),
					'rp_run_cleanup'         => __( 'Run cleanup', 'recruiting-playbook' ),
				],
			],
			'forms'        => [
				'label'        => __( 'Form Builder', 'recruiting-playbook' ),
				'capabilities' => [
					'rp_manage_forms' => __( 'Manage form fields & templates', 'recruiting-playbook' ),
				],
			],
		];
	}

	/**
	 * Prüfen ob Benutzer eine Custom Rolle hat
	 *
	 * @param int|null $user_id User ID oder null für aktuellen User.
	 * @return bool
	 */
	public static function hasCustomRole( ?int $user_id = null ): bool {
		$user = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		return in_array( 'rp_recruiter', $user->roles, true )
			|| in_array( 'rp_hiring_manager', $user->roles, true );
	}

	/**
	 * Alle Benutzer mit Recruiting-Rollen abrufen
	 *
	 * @return array<\WP_User>
	 */
	public static function getRecruitingUsers(): array {
		$users = get_users(
			[
				'role__in' => [ 'administrator', 'editor', 'rp_recruiter', 'rp_hiring_manager' ],
				'orderby'  => 'display_name',
			]
		);

		return array_filter(
			$users,
			function ( $user ) {
				return $user->has_cap( 'rp_view_applications' );
			}
		);
	}
}

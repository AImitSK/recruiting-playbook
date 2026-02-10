<?php
/**
 * Dashboard Admin Page
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\EmailService;
use RecruitingPlaybook\Services\DocumentService;

/**
 * Dashboard Page Klasse
 */
class DashboardPage {

	/**
	 * Render the dashboard page
	 */
	public function render(): void {
		$data = $this->get_dashboard_data();

		// Localize data for React component.
		wp_localize_script(
			'rp-admin',
			'rpDashboardData',
			$data
		);

		?>
		<div class="wrap rp-admin">
			<div id="rp-dashboard-root"></div>
		</div>
		<?php
	}

	/**
	 * Get all dashboard data
	 *
	 * @return array<string, mixed>
	 */
	private function get_dashboard_data(): array {
		return [
			'stats'      => $this->get_stats(),
			'notices'    => $this->get_notices(),
			'tables'     => $this->get_table_status(),
			'systemInfo' => $this->get_system_info(),
			'logoUrl'    => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
		];
	}

	/**
	 * Get statistics
	 *
	 * @return array<string, int>
	 */
	private function get_stats(): array {
		global $wpdb;

		// Jobs zählen.
		$jobs_count  = wp_count_posts( 'job_listing' );
		$active_jobs = isset( $jobs_count->publish ) ? (int) $jobs_count->publish : 0;

		// Bewerbungen zählen.
		$applications_table = $wpdb->prefix . 'rp_applications';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $applications_table )
		) === $applications_table;

		$total_applications = 0;
		$new_applications   = 0;

		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total_applications = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$applications_table}" );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$new_applications = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$applications_table} WHERE status = %s", 'new' )
			);
		}

		return [
			'activeJobs'        => $active_jobs,
			'newApplications'   => $new_applications,
			'totalApplications' => $total_applications,
		];
	}

	/**
	 * Get warning notices
	 *
	 * @return array<int, array<string, string>>
	 */
	private function get_notices(): array {
		$notices = [];

		// SMTP-Konfiguration prüfen.
		$smtp_status = EmailService::checkSmtpConfig();
		if ( ! $smtp_status['configured'] ) {
			$notices[] = [
				'type'        => 'warning',
				'title'       => __( 'E-Mail-Konfiguration', 'recruiting-playbook' ),
				'message'     => $smtp_status['message'],
				'actionUrl'   => admin_url( 'plugin-install.php?s=smtp&tab=search&type=term' ),
				'actionLabel' => __( 'SMTP-Plugin suchen', 'recruiting-playbook' ),
			];
		}

		// Dokumentenschutz prüfen.
		$doc_protection = DocumentService::checkProtection();
		if ( 'nginx' === $doc_protection['server_type'] ) {
			$notices[] = [
				'type'        => 'warning',
				'title'       => __( 'Dokumentenschutz (Nginx)', 'recruiting-playbook' ),
				'message'     => $doc_protection['message'],
				'actionUrl'   => 'https://github.com/AImitSK/recruiting-playbook/wiki/Nginx-Security',
				'actionLabel' => __( 'Anleitung ansehen', 'recruiting-playbook' ),
			];
		}

		return $notices;
	}

	/**
	 * Get database table status
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_table_status(): array {
		global $wpdb;

		$required_tables = [
			'rp_candidates'   => __( 'Kandidaten', 'recruiting-playbook' ),
			'rp_applications' => __( 'Bewerbungen', 'recruiting-playbook' ),
			'rp_documents'    => __( 'Dokumente', 'recruiting-playbook' ),
			'rp_activity_log' => __( 'Aktivitätslog', 'recruiting-playbook' ),
		];

		$tables = [];

		foreach ( $required_tables as $table_suffix => $label ) {
			$table_name = $wpdb->prefix . $table_suffix;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
			) === $table_name;

			$tables[ $table_suffix ] = [
				'name'   => $table_name,
				'label'  => $label,
				'exists' => $exists,
			];
		}

		return $tables;
	}

	/**
	 * Get system info
	 *
	 * @return array<string, string>
	 */
	private function get_system_info(): array {
		return [
			'pluginVersion' => RP_VERSION,
			'phpVersion'    => PHP_VERSION,
			'wpVersion'     => get_bloginfo( 'version' ),
			'dbVersion'     => get_option( 'rp_db_version', __( 'nicht installiert', 'recruiting-playbook' ) ),
		];
	}
}

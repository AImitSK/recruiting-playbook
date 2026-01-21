<?php
/**
 * Admin-Menü Registrierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin;

use RecruitingPlaybook\Admin\Settings;

/**
 * Admin-Menü Registrierung
 */
class Menu {

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Menü registrieren
	 */
	public function register(): void {
		// Settings initialisieren.
		$this->settings = new Settings();
		$this->settings->register();

		// Hauptmenü.
		add_menu_page(
			__( 'Recruiting Playbook', 'recruiting-playbook' ),
			__( 'Recruiting', 'recruiting-playbook' ),
			'manage_options',
			'recruiting-playbook',
			[ $this, 'renderDashboard' ],
			'dashicons-groups',
			25
		);

		// Dashboard (ersetzt Hauptmenü-Eintrag).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Dashboard', 'recruiting-playbook' ),
			__( 'Dashboard', 'recruiting-playbook' ),
			'manage_options',
			'recruiting-playbook',
			[ $this, 'renderDashboard' ]
		);

		// Bewerbungen.
		add_submenu_page(
			'recruiting-playbook',
			__( 'Bewerbungen', 'recruiting-playbook' ),
			__( 'Bewerbungen', 'recruiting-playbook' ),
			'manage_options',
			'rp-applications',
			[ $this, 'renderApplications' ]
		);

		// Einstellungen.
		add_submenu_page(
			'recruiting-playbook',
			__( 'Einstellungen', 'recruiting-playbook' ),
			__( 'Einstellungen', 'recruiting-playbook' ),
			'manage_options',
			'rp-settings',
			[ $this, 'renderSettings' ]
		);
	}

	/**
	 * Dashboard rendern
	 */
	public function renderDashboard(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Recruiting Dashboard', 'recruiting-playbook' ) . '</h1>';

		// Status-Übersicht.
		$this->renderStatusCards();

		// Aktivitäts-Log Platzhalter.
		echo '<div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">';
		echo '<h2>' . esc_html__( 'System-Info', 'recruiting-playbook' ) . '</h2>';
		echo '<table class="widefat striped">';
		echo '<tr><td>Plugin Version</td><td><code>' . esc_html( RP_VERSION ) . '</code></td></tr>';
		echo '<tr><td>PHP Version</td><td><code>' . esc_html( PHP_VERSION ) . '</code></td></tr>';
		echo '<tr><td>WordPress Version</td><td><code>' . esc_html( get_bloginfo( 'version' ) ) . '</code></td></tr>';
		echo '<tr><td>Datenbank-Version</td><td><code>' . esc_html( get_option( 'rp_db_version', 'nicht installiert' ) ) . '</code></td></tr>';
		echo '</table>';
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Status-Karten rendern
	 */
	private function renderStatusCards(): void {
		global $wpdb;

		// Jobs zählen.
		$jobs_count = wp_count_posts( 'job_listing' );
		$active_jobs = isset( $jobs_count->publish ) ? $jobs_count->publish : 0;

		// Bewerbungen zählen.
		$applications_table = $wpdb->prefix . 'rp_applications';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$applications_table}'" ) === $applications_table;

		$total_applications = 0;
		$new_applications   = 0;

		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total_applications = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$applications_table}" );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$new_applications = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$applications_table} WHERE status = 'new'" );
		}

		?>
		<div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0;">
			<div class="card" style="flex: 1; min-width: 200px; padding: 20px;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Aktive Stellen', 'recruiting-playbook' ); ?></h3>
				<p style="font-size: 32px; font-weight: bold; margin: 0; color: #2271b1;"><?php echo esc_html( $active_jobs ); ?></p>
			</div>
			<div class="card" style="flex: 1; min-width: 200px; padding: 20px;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Neue Bewerbungen', 'recruiting-playbook' ); ?></h3>
				<p style="font-size: 32px; font-weight: bold; margin: 0; color: #00a32a;"><?php echo esc_html( $new_applications ); ?></p>
			</div>
			<div class="card" style="flex: 1; min-width: 200px; padding: 20px;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Gesamt Bewerbungen', 'recruiting-playbook' ); ?></h3>
				<p style="font-size: 32px; font-weight: bold; margin: 0; color: #787c82;"><?php echo esc_html( $total_applications ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Bewerbungen rendern
	 */
	public function renderApplications(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Bewerbungen', 'recruiting-playbook' ) . '</h1>';
		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'Die Bewerbungsverwaltung wird in Phase 1B implementiert.', 'recruiting-playbook' );
		echo '</p></div>';
		echo '<div id="rp-applications-app"></div>';
		echo '</div>';
	}

	/**
	 * Einstellungen rendern
	 */
	public function renderSettings(): void {
		$this->settings->renderPage();
	}
}

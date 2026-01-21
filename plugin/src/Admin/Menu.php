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

		// DB-Integritätscheck.
		$this->renderDbIntegrityCheck();

		// System-Info.
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
	 * DB-Integritätscheck rendern
	 */
	private function renderDbIntegrityCheck(): void {
		global $wpdb;

		// Erwartete Tabellen.
		$required_tables = [
			'rp_candidates'    => __( 'Kandidaten', 'recruiting-playbook' ),
			'rp_applications'  => __( 'Bewerbungen', 'recruiting-playbook' ),
			'rp_documents'     => __( 'Dokumente', 'recruiting-playbook' ),
			'rp_activity_log'  => __( 'Aktivitätslog', 'recruiting-playbook' ),
		];

		$missing_tables = [];
		$existing_tables = [];

		foreach ( $required_tables as $table_suffix => $label ) {
			$table_name = $wpdb->prefix . $table_suffix;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
			) === $table_name;

			if ( $exists ) {
				$existing_tables[ $table_suffix ] = $label;
			} else {
				$missing_tables[ $table_suffix ] = $label;
			}
		}

		$all_ok = empty( $missing_tables );
		$status_class = $all_ok ? 'notice-success' : 'notice-error';
		$status_icon = $all_ok ? '✓' : '✗';
		?>
		<div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
			<h2><?php esc_html_e( 'Datenbank-Integrität', 'recruiting-playbook' ); ?></h2>

			<?php if ( $all_ok ) : ?>
				<div class="notice notice-success inline" style="margin: 10px 0;">
					<p><strong><?php esc_html_e( 'Alle Datenbanktabellen sind vorhanden.', 'recruiting-playbook' ); ?></strong></p>
				</div>
			<?php else : ?>
				<div class="notice notice-error inline" style="margin: 10px 0;">
					<p>
						<strong><?php esc_html_e( 'Fehlende Tabellen gefunden!', 'recruiting-playbook' ); ?></strong><br>
						<?php esc_html_e( 'Bitte deaktivieren und reaktivieren Sie das Plugin, um die fehlenden Tabellen zu erstellen.', 'recruiting-playbook' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<table class="widefat striped" style="margin-top: 15px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Tabelle', 'recruiting-playbook' ); ?></th>
						<th><?php esc_html_e( 'Beschreibung', 'recruiting-playbook' ); ?></th>
						<th style="text-align: center;"><?php esc_html_e( 'Status', 'recruiting-playbook' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $required_tables as $table_suffix => $label ) : ?>
						<?php
						$is_existing = isset( $existing_tables[ $table_suffix ] );
						$icon = $is_existing ? '<span style="color: #00a32a;">✓</span>' : '<span style="color: #d63638;">✗</span>';
						$status_text = $is_existing
							? __( 'OK', 'recruiting-playbook' )
							: __( 'Fehlt', 'recruiting-playbook' );
						?>
						<tr>
							<td><code><?php echo esc_html( $wpdb->prefix . $table_suffix ); ?></code></td>
							<td><?php echo esc_html( $label ); ?></td>
							<td style="text-align: center;">
								<?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo esc_html( $status_text ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
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

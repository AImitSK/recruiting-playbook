<?php
/**
 * Admin-Menü Registrierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin;

use RecruitingPlaybook\Admin\Settings;
use RecruitingPlaybook\Admin\Pages\ApplicationList;
use RecruitingPlaybook\Admin\Pages\ApplicationDetail;
use RecruitingPlaybook\Admin\Export\BackupExporter;
use RecruitingPlaybook\Services\GdprService;
use RecruitingPlaybook\Services\DocumentService;
use RecruitingPlaybook\Services\EmailService;
use RecruitingPlaybook\Constants\ApplicationStatus;

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

		// Aktionen früh verarbeiten (vor Output).
		add_action( 'admin_init', [ $this, 'handleEarlyActions' ] );

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

		// Export.
		add_submenu_page(
			'recruiting-playbook',
			__( 'Export', 'recruiting-playbook' ),
			__( 'Export', 'recruiting-playbook' ),
			'manage_options',
			'rp-export',
			[ $this, 'renderExport' ]
		);

		// Bewerbung-Detailansicht (versteckt).
		add_submenu_page(
			'', // Versteckt (leerer String für PHP 8.1+ Kompatibilität).
			__( 'Bewerbung', 'recruiting-playbook' ),
			__( 'Bewerbung', 'recruiting-playbook' ),
			'manage_options',
			'rp-application-detail',
			[ $this, 'renderApplicationDetail' ]
		);
	}

	/**
	 * Aktionen früh verarbeiten (vor jeglichem Output)
	 */
	public function handleEarlyActions(): void {
		// Nur auf unserer Seite.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird unten für jede Aktion geprüft.
		if ( ! isset( $_GET['page'] ) || 'rp-applications' !== $_GET['page'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird unten für jede Aktion geprüft.
		if ( empty( $_GET['action'] ) || empty( $_GET['id'] ) ) {
			return;
		}

		// Berechtigung prüfen.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird unten für jede Aktion geprüft.
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird unten für jede Aktion geprüft.
		$id = absint( $_GET['id'] );

		// Status setzen.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird direkt danach geprüft.
		if ( 'set_status' === $action && ! empty( $_GET['status'] ) ) {
			check_admin_referer( 'rp_set_status_' . $id );

			$status = sanitize_text_field( wp_unslash( $_GET['status'] ) );

			if ( ! array_key_exists( $status, ApplicationStatus::getAll() ) ) {
				return;
			}

			global $wpdb;
			$table = $wpdb->prefix . 'rp_applications';

			$wpdb->update( $table, [ 'status' => $status ], [ 'id' => $id ] );

			// Logging.
			$log_table    = $wpdb->prefix . 'rp_activity_log';
			$current_user = wp_get_current_user();

			$wpdb->insert(
				$log_table,
				[
					'object_type' => 'application',
					'object_id'   => $id,
					'action'      => 'status_changed',
					'user_id'     => $current_user->ID,
					'user_name'   => $current_user->display_name,
					'new_value'   => $status,
					'created_at'  => current_time( 'mysql' ),
				]
			);

			wp_safe_redirect( admin_url( 'admin.php?page=rp-applications&updated=1' ) );
			exit;
		}

		// Daten exportieren (DSGVO).
		if ( 'export_data' === $action ) {
			check_admin_referer( 'rp_export_data_' . $id );

			$gdpr_service = new GdprService();
			$gdpr_service->downloadApplicationData( $id );
			// Kein exit hier, da downloadApplicationData selbst beendet.
		}

		// Löschen.
		if ( 'delete' === $action ) {
			check_admin_referer( 'rp_delete_' . $id );

			$gdpr_service = new GdprService();
			$gdpr_service->softDeleteApplication( $id );

			wp_safe_redirect( admin_url( 'admin.php?page=rp-applications&deleted=1' ) );
			exit;
		}
	}

	/**
	 * Dashboard rendern
	 */
	public function renderDashboard(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Recruiting Dashboard', 'recruiting-playbook' ) . '</h1>';

		// Sicherheits- und Konfigurationswarnungen.
		$this->renderSecurityNotices();

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
	 * Sicherheits- und Konfigurationswarnungen rendern
	 */
	private function renderSecurityNotices(): void {
		$notices = [];

		// SMTP-Konfiguration prüfen.
		$smtp_status = EmailService::checkSmtpConfig();
		if ( ! $smtp_status['configured'] ) {
			$notices[] = [
				'type'    => 'warning',
				'title'   => __( 'E-Mail-Konfiguration', 'recruiting-playbook' ),
				'message' => $smtp_status['message'],
				'action'  => sprintf(
					'<a href="%s" class="button button-small">%s</a>',
					esc_url( admin_url( 'plugin-install.php?s=smtp&tab=search&type=term' ) ),
					esc_html__( 'SMTP-Plugin suchen', 'recruiting-playbook' )
				),
			];
		}

		// Dokumentenschutz prüfen.
		$doc_protection = DocumentService::checkProtection();
		if ( 'nginx' === $doc_protection['server_type'] ) {
			$notices[] = [
				'type'    => 'warning',
				'title'   => __( 'Dokumentenschutz (Nginx)', 'recruiting-playbook' ),
				'message' => $doc_protection['message'],
				'action'  => sprintf(
					'<a href="%s" target="_blank" class="button button-small">%s</a>',
					'https://github.com/AImitSK/recruiting-playbook/wiki/Nginx-Security',
					esc_html__( 'Anleitung ansehen', 'recruiting-playbook' )
				),
			];
		}

		// Keine Warnungen vorhanden.
		if ( empty( $notices ) ) {
			return;
		}

		// Warnungen ausgeben.
		foreach ( $notices as $notice ) {
			$notice_class = 'notice-' . $notice['type'];
			?>
			<div class="notice <?php echo esc_attr( $notice_class ); ?> inline" style="margin: 10px 0 20px 0;">
				<p>
					<strong><?php echo esc_html( $notice['title'] ); ?>:</strong>
					<?php echo esc_html( $notice['message'] ); ?>
				</p>
				<?php if ( ! empty( $notice['action'] ) ) : ?>
					<p><?php echo $notice['action']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
			</div>
			<?php
		}
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
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Bewerbungen', 'recruiting-playbook' ) . '</h1>';

		// Export-Button.
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=rp-export' ) ) . '" class="page-title-action">';
		echo esc_html__( 'Exportieren', 'recruiting-playbook' );
		echo '</a>';

		echo '<hr class="wp-header-end">';

		// Status-Übersicht.
		$this->renderStatusCounts();

		// Liste rendern.
		$list_table = new ApplicationList();
		$list_table->prepare_items();

		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="rp-applications" />';
		$list_table->search_box( __( 'Suchen', 'recruiting-playbook' ), 'search' );
		$list_table->display();
		echo '</form>';

		echo '</div>';
	}

	/**
	 * Status-Zähler anzeigen
	 */
	private function renderStatusCounts(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
			OBJECT_K
		);

		$statuses = ApplicationStatus::getAll();
		$colors   = ApplicationStatus::getColors();

		echo '<ul class="subsubsub" style="margin-bottom: 15px;">';

		$links = [];
		$total = 0;

		foreach ( $statuses as $status => $label ) {
			$count  = isset( $counts[ $status ] ) ? (int) $counts[ $status ]->count : 0;
			$total += $count;

			$url = add_query_arg( 'status', $status, admin_url( 'admin.php?page=rp-applications' ) );

			$links[] = sprintf(
				'<li><a href="%s" style="color: %s;">%s</a> <span class="count">(%d)</span></li>',
				esc_url( $url ),
				esc_attr( $colors[ $status ] ),
				esc_html( $label ),
				$count
			);
		}

		// "Alle" Link am Anfang.
		array_unshift(
			$links,
			sprintf(
				'<li><a href="%s"><strong>%s</strong></a> <span class="count">(%d)</span> |</li>',
				esc_url( admin_url( 'admin.php?page=rp-applications' ) ),
				esc_html__( 'Alle', 'recruiting-playbook' ),
				$total
			)
		);

		echo implode( ' | ', $links );
		echo '</ul>';
		echo '<div class="clear"></div>';
	}

	/**
	 * Bewerbung-Detailansicht rendern
	 */
	public function renderApplicationDetail(): void {
		$detail_page = new ApplicationDetail();
		$detail_page->render();
	}

	/**
	 * Export-Seite rendern
	 */
	public function renderExport(): void {
		// Download-Aktion.
		if ( isset( $_POST['download_backup'] ) && check_admin_referer( 'rp_download_backup' ) ) {
			$exporter = new BackupExporter();
			$exporter->download();
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Daten exportieren', 'recruiting-playbook' ); ?></h1>

			<div class="card" style="max-width: 600px; padding: 20px;">
				<h2><?php esc_html_e( 'Vollständiger Backup', 'recruiting-playbook' ); ?></h2>
				<p>
					<?php esc_html_e( 'Exportiert alle Plugin-Daten als JSON-Datei:', 'recruiting-playbook' ); ?>
				</p>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Einstellungen', 'recruiting-playbook' ); ?></li>
					<li><?php esc_html_e( 'Stellen (inkl. Meta-Daten)', 'recruiting-playbook' ); ?></li>
					<li><?php esc_html_e( 'Taxonomien (Kategorien, Standorte, etc.)', 'recruiting-playbook' ); ?></li>
					<li><?php esc_html_e( 'Kandidaten', 'recruiting-playbook' ); ?></li>
					<li><?php esc_html_e( 'Bewerbungen', 'recruiting-playbook' ); ?></li>
					<li><?php esc_html_e( 'Dokument-Metadaten', 'recruiting-playbook' ); ?></li>
					<li><?php esc_html_e( 'Aktivitäts-Log (letzte 1000 Einträge)', 'recruiting-playbook' ); ?></li>
				</ul>

				<div class="notice notice-warning inline" style="margin: 15px 0;">
					<p>
						<strong><?php esc_html_e( 'Hinweis:', 'recruiting-playbook' ); ?></strong>
						<?php esc_html_e( 'Hochgeladene Dokumente (PDFs etc.) werden aus Datenschutzgründen nicht exportiert.', 'recruiting-playbook' ); ?>
					</p>
				</div>

				<form method="post">
					<?php wp_nonce_field( 'rp_download_backup' ); ?>
					<button type="submit" name="download_backup" class="button button-primary">
						<?php esc_html_e( 'Backup herunterladen', 'recruiting-playbook' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Einstellungen rendern
	 */
	public function renderSettings(): void {
		$this->settings->renderPage();
	}
}

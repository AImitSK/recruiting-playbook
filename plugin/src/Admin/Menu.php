<?php
/**
 * Admin-Menü Registrierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Admin\Settings;
use RecruitingPlaybook\Admin\Pages\ApplicationList;
use RecruitingPlaybook\Admin\Pages\ApplicationDetail;
use RecruitingPlaybook\Admin\Pages\ApplicationsPage;
use RecruitingPlaybook\Admin\Pages\DashboardPage;
use RecruitingPlaybook\Admin\Pages\KanbanBoard;
use RecruitingPlaybook\Admin\Pages\LicensePage;
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

		// Kanban-Board (Pro-Feature).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Kanban-Board', 'recruiting-playbook' ),
			$this->getKanbanMenuLabel(),
			'manage_options',
			'rp-kanban',
			[ $this, 'renderKanban' ]
		);

		// Talent-Pool (Pro-Feature).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Talent-Pool', 'recruiting-playbook' ),
			$this->getTalentPoolMenuLabel(),
			'manage_options',
			'rp-talent-pool',
			[ $this, 'renderTalentPool' ]
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

		// Lizenz.
		add_submenu_page(
			'recruiting-playbook',
			__( 'Lizenz', 'recruiting-playbook' ),
			$this->getLicenseMenuLabel(),
			'manage_options',
			'rp-license',
			[ $this, 'renderLicense' ]
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

		// Bulk-E-Mail-Seite (versteckt, Pro-Feature).
		add_submenu_page(
			'',
			__( 'Massen-E-Mail', 'recruiting-playbook' ),
			__( 'Massen-E-Mail', 'recruiting-playbook' ),
			'manage_options',
			'rp-bulk-email',
			[ $this, 'renderBulkEmail' ]
		);
	}

	/**
	 * Aktionen früh verarbeiten (vor jeglichem Output)
	 */
	public function handleEarlyActions(): void {
		// Backup-Download (muss vor jeglichem Output passieren).
		$this->handleBackupDownload();

		// Nur auf Bewerbungen-Seite.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird unten für jede Aktion geprüft.
		if ( ! isset( $_GET['page'] ) || 'rp-applications' !== $_GET['page'] ) {
			return;
		}

		// Bulk-Actions verarbeiten (POST).
		$this->handleBulkActions();

		// Einzelaktionen (GET) - nur wenn action und id vorhanden.
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

			// Alten Status für Hook abrufen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$old_status = $wpdb->get_var(
				$wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $id )
			);

			$wpdb->update( $table, [ 'status' => $status ], [ 'id' => $id ] );

			// Action für Auto-E-Mail und andere Hooks auslösen.
			if ( $old_status && $old_status !== $status ) {
				do_action( 'rp_application_status_changed', $id, $old_status, $status );
			}

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
	 * Bulk-Actions verarbeiten (vor Output)
	 */
	private function handleBulkActions(): void {
		// Bulk-Action aus Dropdown ermitteln.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird unten geprüft.
		$action = isset( $_POST['action'] ) && $_POST['action'] !== '-1'
			? sanitize_text_field( wp_unslash( $_POST['action'] ) )
			: ( isset( $_POST['action2'] ) && $_POST['action2'] !== '-1'
				? sanitize_text_field( wp_unslash( $_POST['action2'] ) )
				: '' );

		// Keine Bulk-Action oder keine IDs ausgewählt.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird unten geprüft.
		if ( empty( $action ) || empty( $_POST['application_ids'] ) ) {
			return;
		}

		// Berechtigung prüfen.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Nonce prüfen.
		check_admin_referer( 'bulk-bewerbungen' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- array_map mit absint sanitized.
		$ids = array_map( 'absint', wp_unslash( $_POST['application_ids'] ) );

		global $wpdb;
		$table        = $wpdb->prefix . 'rp_applications';
		$log_table    = $wpdb->prefix . 'rp_activity_log';
		$current_user = wp_get_current_user();

		switch ( $action ) {
			case 'bulk_screening':
				foreach ( $ids as $id ) {
					// Alten Status abrufen.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$old_status = $wpdb->get_var(
						$wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $id )
					);

					$wpdb->update( $table, [ 'status' => 'screening' ], [ 'id' => $id ] );
					$this->logStatusChange( $log_table, $id, 'screening', $current_user );

					// Action für Auto-E-Mail auslösen.
					if ( $old_status && 'screening' !== $old_status ) {
						do_action( 'rp_application_status_changed', $id, $old_status, 'screening' );
					}
				}
				break;

			case 'bulk_rejected':
				foreach ( $ids as $id ) {
					// Alten Status abrufen.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$old_status = $wpdb->get_var(
						$wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $id )
					);

					$wpdb->update( $table, [ 'status' => 'rejected' ], [ 'id' => $id ] );
					$this->logStatusChange( $log_table, $id, 'rejected', $current_user );

					// Action für Auto-E-Mail auslösen.
					if ( $old_status && 'rejected' !== $old_status ) {
						do_action( 'rp_application_status_changed', $id, $old_status, 'rejected' );
					}
				}
				break;

			case 'bulk_delete':
				$gdpr_service = new GdprService();
				foreach ( $ids as $id ) {
					$gdpr_service->softDeleteApplication( $id );
				}
				break;

			case 'bulk_email':
				// Pro-Feature Check.
				if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=rp-applications&error=pro_required' ) );
					exit;
				}

				// IDs in Transient speichern für Bulk-Email-Seite.
				set_transient( 'rp_bulk_email_ids_' . $current_user->ID, $ids, 300 ); // 5 Minuten gültig.
				wp_safe_redirect( admin_url( 'admin.php?page=rp-bulk-email' ) );
				exit;

			default:
				return; // Unbekannte Action - nicht redirecten.
		}

		wp_safe_redirect( admin_url( 'admin.php?page=rp-applications&bulk_updated=1' ) );
		exit;
	}

	/**
	 * Status-Änderung loggen (Helper für Bulk-Actions)
	 *
	 * @param string   $log_table    Log table name.
	 * @param int      $id           Application ID.
	 * @param string   $new_status   New status.
	 * @param \WP_User $current_user Current user.
	 */
	private function logStatusChange( string $log_table, int $id, string $new_status, \WP_User $current_user ): void {
		global $wpdb;

		$wpdb->insert(
			$log_table,
			[
				'object_type' => 'application',
				'object_id'   => $id,
				'action'      => 'status_changed',
				'user_id'     => $current_user->ID,
				'user_name'   => $current_user->display_name,
				'new_value'   => $new_status,
				'created_at'  => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Dashboard rendern
	 */
	public function renderDashboard(): void {
		$page = new DashboardPage();
		$page->render();
	}

	/**
	 * Bewerbungen rendern
	 */
	public function renderApplications(): void {
		$page = new ApplicationsPage();
		$page->render();
	}

	/**
	 * Bewerbung-Detailansicht rendern
	 */
	public function renderApplicationDetail(): void {
		$detail_page = new ApplicationDetail();
		$detail_page->render();
	}

	/**
	 * Backup-Download verarbeiten (vor Output)
	 */
	private function handleBackupDownload(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird unten geprüft.
		if ( ! isset( $_GET['page'] ) || 'rp-export' !== $_GET['page'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce wird direkt danach geprüft.
		if ( ! isset( $_POST['download_backup'] ) ) {
			return;
		}

		// Berechtigung prüfen.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Nonce prüfen.
		check_admin_referer( 'rp_download_backup' );

		// Download ausführen.
		$exporter = new BackupExporter();
		$exporter->download();
		// exit wird in download() aufgerufen.
	}

	/**
	 * Export-Seite rendern
	 */
	public function renderExport(): void {
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

	/**
	 * Lizenz-Seite rendern
	 */
	public function renderLicense(): void {
		$license_page = new LicensePage();
		$license_page->render();
	}

	/**
	 * Kanban-Board rendern
	 */
	public function renderKanban(): void {
		$kanban_page = new KanbanBoard();
		$kanban_page->render();
	}

	/**
	 * Kanban-Menü-Label mit Lock-Icon für Free-User
	 *
	 * @return string Menü-Label.
	 */
	private function getKanbanMenuLabel(): string {
		$label = __( 'Kanban-Board', 'recruiting-playbook' );

		// Lock-Icon für Free-User.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'kanban_board' ) ) {
			$label .= ' <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle; opacity: 0.7;"></span>';
		}

		return $label;
	}

	/**
	 * Lizenz-Menü-Label mit Tier-Badge
	 *
	 * @return string Menü-Label mit optionalem Badge.
	 */
	private function getLicenseMenuLabel(): string {
		$label = __( 'Lizenz', 'recruiting-playbook' );

		// Tier-Badge hinzufügen wenn Pro aktiv.
		if ( function_exists( 'rp_is_pro' ) && rp_is_pro() ) {
			$label .= ' <span class="update-plugins count-1" style="background: linear-gradient(to right, #2fac66, #36a9e1);"><span class="plugin-count">PRO</span></span>';
		}

		return $label;
	}

	/**
	 * Talent-Pool-Menü-Label mit Lock-Icon für Free-User
	 *
	 * @return string Menü-Label.
	 */
	private function getTalentPoolMenuLabel(): string {
		$label = __( 'Talent-Pool', 'recruiting-playbook' );

		// Lock-Icon für Free-User.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'advanced_applicant_management' ) ) {
			$label .= ' <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle; opacity: 0.7;"></span>';
		}

		return $label;
	}

	/**
	 * Talent-Pool-Seite rendern
	 */
	public function renderTalentPool(): void {
		$talent_pool_page = new Pages\TalentPoolPage();
		$talent_pool_page->render();
	}

	/**
	 * Bulk-E-Mail-Seite rendern
	 */
	public function renderBulkEmail(): void {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			wp_die(
				esc_html__( 'Massen-E-Mail erfordert Pro.', 'recruiting-playbook' ),
				esc_html__( 'Pro-Feature erforderlich', 'recruiting-playbook' ),
				[ 'response' => 403 ]
			);
		}

		$current_user = wp_get_current_user();
		$transient_key = 'rp_bulk_email_ids_' . $current_user->ID;
		$application_ids = get_transient( $transient_key );

		// Keine IDs vorhanden.
		if ( empty( $application_ids ) || ! is_array( $application_ids ) ) {
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Massen-E-Mail', 'recruiting-playbook' ); ?></h1>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Keine Bewerbungen ausgewählt. Bitte wählen Sie Bewerbungen in der Liste aus.', 'recruiting-playbook' ); ?></p>
				</div>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-applications' ) ); ?>" class="button">
						<?php esc_html_e( 'Zurück zur Liste', 'recruiting-playbook' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}

		// Templates laden.
		global $wpdb;
		$templates_table = $wpdb->prefix . 'rp_email_templates';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$templates = $wpdb->get_results(
			"SELECT id, name, subject FROM {$templates_table} WHERE is_active = 1 ORDER BY name ASC",
			ARRAY_A
		) ?: [];

		// Bewerber-Informationen laden.
		$applications_table = $wpdb->prefix . 'rp_applications';
		$candidates_table = $wpdb->prefix . 'rp_candidates';
		$placeholders = implode( ',', array_fill( 0, count( $application_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$recipients = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.id, c.first_name, c.last_name, c.email
				 FROM {$applications_table} a
				 LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id
				 WHERE a.id IN ({$placeholders})",
				...$application_ids
			),
			ARRAY_A
		) ?: [];

		// Formular wurde abgeschickt.
		if ( isset( $_POST['send_bulk_email'] ) ) {
			check_admin_referer( 'rp_bulk_email' );

			$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

			if ( ! $template_id ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Bitte wählen Sie ein Template aus.', 'recruiting-playbook' ) . '</p></div>';
			} else {
				$email_service = new EmailService();
				$success_count = 0;
				$error_count = 0;

				foreach ( $application_ids as $app_id ) {
					$result = $email_service->sendWithTemplate( $template_id, (int) $app_id );
					if ( false !== $result ) {
						$success_count++;

						// Aktivitäts-Log.
						$log_table = $wpdb->prefix . 'rp_activity_log';
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$wpdb->insert(
							$log_table,
							[
								'object_type' => 'application',
								'object_id'   => $app_id,
								'action'      => 'email_sent',
								'user_id'     => $current_user->ID,
								'user_name'   => $current_user->display_name,
								'message'     => sprintf( 'Bulk-E-Mail mit Template #%d', $template_id ),
								'created_at'  => current_time( 'mysql' ),
							]
						);
					} else {
						$error_count++;
					}
				}

				// Transient löschen.
				delete_transient( $transient_key );

				// Ergebnis anzeigen.
				?>
				<div class="wrap">
					<h1><?php esc_html_e( 'Massen-E-Mail', 'recruiting-playbook' ); ?></h1>
					<div class="notice notice-success">
						<p>
							<?php
							printf(
								/* translators: 1: success count, 2: error count */
								esc_html__( '%1$d E-Mails erfolgreich gesendet, %2$d fehlgeschlagen.', 'recruiting-playbook' ),
								$success_count,
								$error_count
							);
							?>
						</p>
					</div>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-applications' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Zurück zur Liste', 'recruiting-playbook' ); ?>
						</a>
					</p>
				</div>
				<?php
				return;
			}
		}

		// Formular anzeigen.
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Massen-E-Mail senden', 'recruiting-playbook' ); ?></h1>

			<div class="card" style="max-width: 800px; padding: 20px;">
				<h2>
					<?php
					printf(
						/* translators: %d: number of recipients */
						esc_html__( '%d Empfänger ausgewählt', 'recruiting-playbook' ),
						count( $recipients )
					);
					?>
				</h2>

				<table class="widefat striped" style="margin-bottom: 20px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'recruiting-playbook' ); ?></th>
							<th><?php esc_html_e( 'E-Mail', 'recruiting-playbook' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recipients as $recipient ) : ?>
							<tr>
								<td><?php echo esc_html( trim( $recipient['first_name'] . ' ' . $recipient['last_name'] ) ); ?></td>
								<td><?php echo esc_html( $recipient['email'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( empty( $templates ) ) : ?>
					<div class="notice notice-warning inline">
						<p>
							<?php esc_html_e( 'Keine E-Mail-Templates vorhanden.', 'recruiting-playbook' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates' ) ); ?>">
								<?php esc_html_e( 'Templates erstellen', 'recruiting-playbook' ); ?>
							</a>
						</p>
					</div>
				<?php else : ?>
					<form method="post">
						<?php wp_nonce_field( 'rp_bulk_email' ); ?>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="template_id"><?php esc_html_e( 'E-Mail-Template', 'recruiting-playbook' ); ?></label>
								</th>
								<td>
									<select name="template_id" id="template_id" class="regular-text" required>
										<option value=""><?php esc_html_e( '— Template wählen —', 'recruiting-playbook' ); ?></option>
										<?php foreach ( $templates as $template ) : ?>
											<option value="<?php echo esc_attr( $template['id'] ); ?>">
												<?php echo esc_html( $template['name'] ); ?>
												(<?php echo esc_html( $template['subject'] ); ?>)
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates' ) ); ?>" target="_blank">
											<?php esc_html_e( 'Templates verwalten', 'recruiting-playbook' ); ?>
										</a>
									</p>
								</td>
							</tr>
						</table>

						<div class="notice notice-warning inline" style="margin: 15px 0;">
							<p>
								<strong><?php esc_html_e( 'Achtung:', 'recruiting-playbook' ); ?></strong>
								<?php esc_html_e( 'Die E-Mails werden sofort an alle ausgewählten Empfänger gesendet.', 'recruiting-playbook' ); ?>
							</p>
						</div>

						<p class="submit">
							<button type="submit" name="send_bulk_email" class="button button-primary">
								<span class="dashicons dashicons-email-alt" style="margin-top: 3px;"></span>
								<?php
								printf(
									/* translators: %d: number of recipients */
									esc_html__( '%d E-Mails senden', 'recruiting-playbook' ),
									count( $recipients )
								);
								?>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-applications' ) ); ?>" class="button">
								<?php esc_html_e( 'Abbrechen', 'recruiting-playbook' ); ?>
							</a>
						</p>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

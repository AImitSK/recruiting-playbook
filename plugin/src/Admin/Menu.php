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
use RecruitingPlaybook\Admin\Pages\FormBuilderPage;
use RecruitingPlaybook\Admin\Pages\KanbanBoard;
use RecruitingPlaybook\Admin\Pages\ReportingPage;
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
	 * Constructor - Filter für Menü-Highlighting registrieren
	 */
	public function __construct() {
		// parent_file Filter - setzt auch $submenu_file.
		add_filter( 'parent_file', [ $this, 'filterParentFile' ] );
	}

	/**
	 * Menü registrieren
	 */
	public function register(): void {
		// Settings initialisieren.
		$this->settings = new Settings();
		$this->settings->register();

		// Aktionen früh verarbeiten (vor Output).
		add_action( 'admin_init', [ $this, 'handleEarlyActions' ] );

		// Hauptmenü → Bewerbungen als Startseite.
		add_menu_page(
			__( 'Recruiting Playbook', 'recruiting-playbook' ),
			__( 'Recruiting', 'recruiting-playbook' ),
			'rp_view_applications',
			'recruiting-playbook',
			[ $this, 'renderApplications' ],
			'dashicons-groups',
			25
		);

		// Bewerbungen (ersetzt Hauptmenü-Eintrag).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Applications', 'recruiting-playbook' ),
			__( 'Applications', 'recruiting-playbook' ),
			'rp_view_applications',
			'recruiting-playbook',
			[ $this, 'renderApplications' ]
		);

		// Kanban-Board (Pro-Feature).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Kanban Board', 'recruiting-playbook' ),
			$this->getKanbanMenuLabel(),
			'rp_view_applications',
			'rp-kanban',
			[ $this, 'renderKanban' ]
		);

		// Talent-Pool (Pro-Feature).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Talent Pool', 'recruiting-playbook' ),
			$this->getTalentPoolMenuLabel(),
			'rp_manage_talent_pool',
			'rp-talent-pool',
			[ $this, 'renderTalentPool' ]
		);

		// Reporting & Dashboard.
		add_submenu_page(
			'recruiting-playbook',
			__( 'Reports', 'recruiting-playbook' ),
			$this->getReportingMenuLabel(),
			'rp_view_stats',
			'rp-reporting',
			[ $this, 'renderReporting' ]
		);

		// Formular-Builder (Pro-Feature).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Form Builder', 'recruiting-playbook' ),
			$this->getFormBuilderMenuLabel(),
			'rp_manage_forms',
			'rp-form-builder',
			[ $this, 'renderFormBuilder' ]
		);

		// Einstellungen (Export ist jetzt als Tab integriert).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Settings', 'recruiting-playbook' ),
			__( 'Settings', 'recruiting-playbook' ),
			'manage_options',
			'rp-settings',
			[ $this, 'renderSettings' ]
		);

		// Bewerbung-Detailansicht (unter Parent registriert für Menü-Highlighting).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Application', 'recruiting-playbook' ),
			__( 'Application', 'recruiting-playbook' ),
			'rp_view_applications',
			'rp-application-detail',
			[ $this, 'renderApplicationDetail' ]
		);

		// Bulk-E-Mail-Seite (unter Parent registriert für Menü-Highlighting).
		add_submenu_page(
			'recruiting-playbook',
			__( 'Bulk Email', 'recruiting-playbook' ),
			__( 'Bulk Email', 'recruiting-playbook' ),
			'rp_send_emails',
			'rp-bulk-email',
			[ $this, 'renderBulkEmail' ]
		);

		// Versteckte Seiten aus Menü entfernen (aber Routing bleibt erhalten).
		add_action( 'admin_head', [ $this, 'hideSubmenuPages' ] );
	}

	/**
	 * Versteckte Submenu-Seiten aus der Navigation entfernen
	 */
	public function hideSubmenuPages(): void {
		remove_submenu_page( 'recruiting-playbook', 'rp-application-detail' );
		remove_submenu_page( 'recruiting-playbook', 'rp-bulk-email' );
	}

	/**
	 * Parent-File Filter für versteckte Unterseiten
	 *
	 * Setzt auch $submenu_file innerhalb des Filters (wichtig!).
	 *
	 * @param string $parent_file Aktuelles Parent-File.
	 * @return string Korrigiertes Parent-File.
	 */
	public function filterParentFile( $parent_file ) {
		global $submenu_file;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// Bewerbung-Detail → Recruiting-Menü, Bewerbungen-Submenu.
		if ( 'rp-application-detail' === $current_page ) {
			$submenu_file = 'recruiting-playbook';
			return 'recruiting-playbook';
		}

		// Bulk-E-Mail → Recruiting-Menü, Bewerbungen-Submenu.
		if ( 'rp-bulk-email' === $current_page ) {
			$submenu_file = 'recruiting-playbook';
			return 'recruiting-playbook';
		}

		return $parent_file;
	}

	/**
	 * Aktionen früh verarbeiten (vor jeglichem Output)
	 */
	public function handleEarlyActions(): void {
		// Backup-Download (muss vor jeglichem Output passieren).
		$this->handleBackupDownload();

		// Nur auf Bewerbungen-Seite.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce wird unten für jede Aktion geprüft.
		if ( ! isset( $_GET['page'] ) || 'recruiting-playbook' !== $_GET['page'] ) {
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
		if ( ! current_user_can( 'rp_edit_applications' ) ) {
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

			wp_safe_redirect( admin_url( 'admin.php?page=recruiting-playbook&updated=1' ) );
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

			wp_safe_redirect( admin_url( 'admin.php?page=recruiting-playbook&deleted=1' ) );
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
		if ( ! current_user_can( 'rp_edit_applications' ) ) {
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
					wp_safe_redirect( admin_url( 'admin.php?page=recruiting-playbook&error=pro_required' ) );
					exit;
				}

				// IDs in Transient speichern für Bulk-Email-Seite.
				set_transient( 'rp_bulk_email_ids_' . $current_user->ID, $ids, 300 ); // 5 Minuten gültig.
				wp_safe_redirect( admin_url( 'admin.php?page=rp-bulk-email' ) );
				exit;

			default:
				return; // Unbekannte Action - nicht redirecten.
		}

		wp_safe_redirect( admin_url( 'admin.php?page=recruiting-playbook&bulk_updated=1' ) );
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
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// Export ist jetzt in Settings integriert (früher rp-export).
		if ( ! in_array( $page, [ 'rp-export', 'rp-settings' ], true ) ) {
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
	 * Einstellungen rendern
	 */
	public function renderSettings(): void {
		$this->settings->renderPage();
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
		$label = __( 'Kanban Board', 'recruiting-playbook' );

		// Lock-Icon für Free-User.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'kanban_board' ) ) {
			$label .= ' <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle; opacity: 0.7;"></span>';
		}

		return $label;
	}

	/**
	 * Talent-Pool-Menü-Label mit Lock-Icon für Free-User
	 *
	 * @return string Menü-Label.
	 */
	private function getTalentPoolMenuLabel(): string {
		$label = __( 'Talent Pool', 'recruiting-playbook' );

		// Lock-Icon für Free-User.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'advanced_applicant_management' ) ) {
			$label .= ' <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle; opacity: 0.7;"></span>';
		}

		return $label;
	}

	/**
	 * Reporting-Menü-Label mit Lock-Icon für Free-User (Pro-Features)
	 *
	 * @return string Menü-Label.
	 */
	private function getReportingMenuLabel(): string {
		$label = __( 'Reports', 'recruiting-playbook' );

		// Lock-Icon für Free-User (erweiterte Features).
		if ( function_exists( 'rp_can' ) && ! rp_can( 'advanced_reporting' ) ) {
			$label .= ' <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle; opacity: 0.7;"></span>';
		}

		return $label;
	}

	/**
	 * Form Builder Menü-Label mit Lock-Icon für Free-User
	 *
	 * @return string Menü-Label.
	 */
	private function getFormBuilderMenuLabel(): string {
		$label = __( 'Form Builder', 'recruiting-playbook' );

		// Lock-Icon für Free-User (Custom Fields sind Pro-Feature).
		if ( function_exists( 'rp_can' ) && ! rp_can( 'custom_fields' ) ) {
			$label .= ' <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle; opacity: 0.7;"></span>';
		}

		return $label;
	}

	/**
	 * Reporting-Seite rendern
	 */
	public function renderReporting(): void {
		$reporting_page = new ReportingPage();
		$reporting_page->render();
	}

	/**
	 * Form Builder-Seite rendern
	 */
	public function renderFormBuilder(): void {
		$form_builder_page = new FormBuilderPage();
		$form_builder_page->enqueue_assets();
		$form_builder_page->render();
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
				esc_html__( 'Bulk email requires Pro.', 'recruiting-playbook' ),
				esc_html__( 'Pro feature required', 'recruiting-playbook' ),
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
				<h1><?php esc_html_e( 'Bulk Email', 'recruiting-playbook' ); ?></h1>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'No applications selected. Please select applications from the list.', 'recruiting-playbook' ); ?></p>
				</div>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) ); ?>" class="button">
						<?php esc_html_e( 'Back to list', 'recruiting-playbook' ); ?>
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
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Please select a template.', 'recruiting-playbook' ) . '</p></div>';
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
					<h1><?php esc_html_e( 'Bulk Email', 'recruiting-playbook' ); ?></h1>
					<div class="notice notice-success">
						<p>
							<?php
							printf(
								/* translators: 1: success count, 2: error count */
								esc_html__( '%1$d emails sent successfully, %2$d failed.', 'recruiting-playbook' ),
								$success_count,
								$error_count
							);
							?>
						</p>
					</div>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Back to list', 'recruiting-playbook' ); ?>
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
			<h1><?php esc_html_e( 'Send Bulk Email', 'recruiting-playbook' ); ?></h1>

			<div class="card" style="max-width: 800px; padding: 20px;">
				<h2>
					<?php
					printf(
						/* translators: %d: number of recipients */
						esc_html__( '%d recipients selected', 'recruiting-playbook' ),
						count( $recipients )
					);
					?>
				</h2>

				<table class="widefat striped" style="margin-bottom: 20px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'recruiting-playbook' ); ?></th>
							<th><?php esc_html_e( 'Email', 'recruiting-playbook' ); ?></th>
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
							<?php esc_html_e( 'No email templates available.', 'recruiting-playbook' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates' ) ); ?>">
								<?php esc_html_e( 'Create templates', 'recruiting-playbook' ); ?>
							</a>
						</p>
					</div>
				<?php else : ?>
					<form method="post">
						<?php wp_nonce_field( 'rp_bulk_email' ); ?>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="template_id"><?php esc_html_e( 'Email Template', 'recruiting-playbook' ); ?></label>
								</th>
								<td>
									<select name="template_id" id="template_id" class="regular-text" required>
										<option value=""><?php esc_html_e( '— Select template —', 'recruiting-playbook' ); ?></option>
										<?php foreach ( $templates as $template ) : ?>
											<option value="<?php echo esc_attr( $template['id'] ); ?>">
												<?php echo esc_html( $template['name'] ); ?>
												(<?php echo esc_html( $template['subject'] ); ?>)
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates' ) ); ?>" target="_blank">
											<?php esc_html_e( 'Manage templates', 'recruiting-playbook' ); ?>
										</a>
									</p>
								</td>
							</tr>
						</table>

						<div class="notice notice-warning inline" style="margin: 15px 0;">
							<p>
								<strong><?php esc_html_e( 'Warning:', 'recruiting-playbook' ); ?></strong>
								<?php esc_html_e( 'The emails will be sent immediately to all selected recipients.', 'recruiting-playbook' ); ?>
							</p>
						</div>

						<p class="submit">
							<button type="submit" name="send_bulk_email" class="button button-primary">
								<span class="dashicons dashicons-email-alt" style="margin-top: 3px;"></span>
								<?php
								printf(
									/* translators: %d: number of recipients */
									esc_html__( 'Send %d emails', 'recruiting-playbook' ),
									count( $recipients )
								);
								?>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) ); ?>" class="button">
								<?php esc_html_e( 'Cancel', 'recruiting-playbook' ); ?>
							</a>
						</p>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

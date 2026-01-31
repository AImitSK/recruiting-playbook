<?php
/**
 * Haupt-Plugin-Klasse (Singleton)
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Core;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\PostTypes\JobListing;
use RecruitingPlaybook\Taxonomies\JobCategory;
use RecruitingPlaybook\Taxonomies\JobLocation;
use RecruitingPlaybook\Taxonomies\EmploymentType;
use RecruitingPlaybook\Admin\Menu;
use RecruitingPlaybook\Admin\MetaBoxes\JobMeta;
use RecruitingPlaybook\Admin\MetaBoxes\JobCustomFieldsMeta;
use RecruitingPlaybook\Admin\SetupWizard\SetupWizard;
use RecruitingPlaybook\Admin\Pages\EmailSettingsPage;
use RecruitingPlaybook\Frontend\JobSchema;
use RecruitingPlaybook\Frontend\Shortcodes;
use RecruitingPlaybook\Api\ApplicationController;
use RecruitingPlaybook\Api\NoteController;
use RecruitingPlaybook\Api\RatingController;
use RecruitingPlaybook\Api\ActivityController;
use RecruitingPlaybook\Api\TalentPoolController;
use RecruitingPlaybook\Api\EmailTemplateController;
use RecruitingPlaybook\Api\EmailController;
use RecruitingPlaybook\Api\EmailLogController;
use RecruitingPlaybook\Api\SignatureController;
use RecruitingPlaybook\Api\SettingsController;
use RecruitingPlaybook\Api\LicenseController;
use RecruitingPlaybook\Api\RoleController;
use RecruitingPlaybook\Api\JobAssignmentController;
use RecruitingPlaybook\Api\StatsController;
use RecruitingPlaybook\Api\ExportController;
use RecruitingPlaybook\Api\SystemStatusController;
use RecruitingPlaybook\Api\FieldDefinitionController;
use RecruitingPlaybook\Api\FormTemplateController;
use RecruitingPlaybook\Api\FormConfigController;
use RecruitingPlaybook\Services\DocumentDownloadService;
use RecruitingPlaybook\Services\EmailQueueService;
use RecruitingPlaybook\Services\AutoEmailService;
use RecruitingPlaybook\Database\Migrator;
use RecruitingPlaybook\Database\Migrations\CustomFieldsMigration;
use RecruitingPlaybook\Licensing\LicenseManager;
use RecruitingPlaybook\Traits\Singleton;

/**
 * Haupt-Plugin-Klasse (Singleton)
 */
final class Plugin {

	use Singleton;

	/**
	 * Plugin initialisieren (called by Singleton trait)
	 */
	protected function init(): void {
		// Action Scheduler laden (muss früh passieren).
		$this->loadActionScheduler();

		// Lizenz-Helper-Funktionen laden.
		$this->loadLicenseHelpers();

		// Lizenz-Manager initialisieren.
		LicenseManager::get_instance();

		// Datenbank-Schema nur im Admin prüfen (Performance).
		if ( is_admin() ) {
			$this->maybeUpgradeDatabase();
		}

		// Internationalisierung laden.
		$this->loadI18n();

		// Post Types & Taxonomien registrieren.
		$this->registerPostTypes();
		$this->registerTaxonomies();

		// E-Mail Queue Service initialisieren (Pro-Feature).
		$this->initEmailQueueService();

		// Auto-E-Mail Service initialisieren (Pro-Feature).
		$this->initAutoEmailService();

		// Admin-Bereich.
		if ( is_admin() ) {
			$this->initAdmin();
		}

		// Frontend.
		if ( ! is_admin() ) {
			$this->initFrontend();
		}

		// REST API.
		add_action( 'rest_api_init', [ $this, 'registerRestRoutes' ] );

		// AJAX-Handler für Dokument-Downloads.
		DocumentDownloadService::registerAjaxHandler();

		// Assets laden.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontendAssets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
	}

	/**
	 * Datenbank-Schema prüfen und aktualisieren
	 */
	private function maybeUpgradeDatabase(): void {
		$migrator = new Migrator();
		$migrator->createTables();

		// Capabilities bei Version-Updates aktualisieren.
		$stored_version = get_option( 'rp_version', '0.0.0' );
		if ( version_compare( $stored_version, RP_VERSION, '<' ) ) {
			// Neue Capabilities zuweisen (z.B. rp_manage_forms).
			RoleManager::assignCapabilities();
			update_option( 'rp_version', RP_VERSION );
		}

		// Custom Fields Migration prüfen und ausführen (Pro-Feature).
		if ( function_exists( 'rp_can' ) && rp_can( 'custom_fields' ) ) {
			if ( CustomFieldsMigration::needsMigration() ) {
				// Migration im Hintergrund ausführen.
				add_action( 'admin_init', [ CustomFieldsMigration::class, 'run' ], 99 );
			}
		}
	}

	/**
	 * Action Scheduler laden
	 *
	 * Action Scheduler ist eine Bibliothek für zuverlässige asynchrone Aufgaben.
	 * Wird für E-Mail-Queue und andere Hintergrund-Jobs verwendet.
	 */
	private function loadActionScheduler(): void {
		$action_scheduler_file = RP_PLUGIN_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

		if ( ! file_exists( $action_scheduler_file ) ) {
			// Admin-Notice für fehlende Dependency.
			add_action( 'admin_notices', [ $this, 'showActionSchedulerMissingNotice' ] );
			return;
		}

		require_once $action_scheduler_file;

		// Verifizieren dass Action Scheduler funktioniert.
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			add_action( 'admin_notices', [ $this, 'showActionSchedulerBrokenNotice' ] );
		}
	}

	/**
	 * Admin-Notice: Action Scheduler fehlt
	 */
	public function showActionSchedulerMissingNotice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Recruiting Playbook:', 'recruiting-playbook' ); ?></strong>
				<?php
				printf(
					/* translators: %s: composer install command */
					esc_html__( 'Action Scheduler Bibliothek fehlt. Bitte führen Sie %s im Plugin-Verzeichnis aus.', 'recruiting-playbook' ),
					'<code>composer install</code>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Admin-Notice: Action Scheduler defekt
	 */
	public function showActionSchedulerBrokenNotice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Recruiting Playbook:', 'recruiting-playbook' ); ?></strong>
				<?php esc_html_e( 'Action Scheduler wurde geladen, aber die Funktionen sind nicht verfügbar. E-Mail-Queue möglicherweise nicht funktional.', 'recruiting-playbook' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * E-Mail Queue Service initialisieren
	 *
	 * Registriert Hooks für den Queue-basierten E-Mail-Versand.
	 * Pro-Feature: Nur aktiv wenn E-Mail-Templates Feature verfügbar ist.
	 */
	private function initEmailQueueService(): void {
		// Prüfen ob Feature verfügbar ist.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return;
		}

		$email_queue_service = new EmailQueueService();
		$email_queue_service->registerHooks();

		// Queue-Verarbeitung bei Aktivierung starten.
		add_action( 'init', [ $email_queue_service, 'scheduleQueueProcessing' ] );
	}

	/**
	 * Übersetzungen laden
	 *
	 * Hinweis: Bei WordPress.org gehosteten Plugins werden Übersetzungen
	 * seit WP 4.6 automatisch geladen. Diese Methode ist ein Platzhalter
	 * für zukünftige Erweiterungen.
	 */
	private function loadI18n(): void {
		// WordPress.org lädt Übersetzungen automatisch für gehostete Plugins.
		// Keine manuelle Initialisierung erforderlich.
	}

	/**
	 * Auto-E-Mail Service initialisieren
	 *
	 * Registriert Hooks für automatischen E-Mail-Versand bei Status-Änderungen.
	 * Pro-Feature: Nur aktiv wenn E-Mail-Templates Feature verfügbar ist.
	 */
	private function initAutoEmailService(): void {
		// Prüfen ob Feature verfügbar ist.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return;
		}

		$auto_email_service = new AutoEmailService();
		$auto_email_service->registerHooks();
	}

	/**
	 * Lizenz-Helper-Funktionen laden
	 */
	private function loadLicenseHelpers(): void {
		$helpers_file = RP_PLUGIN_DIR . 'src/Licensing/helpers.php';
		if ( file_exists( $helpers_file ) ) {
			require_once $helpers_file;
		}
	}

	/**
	 * Post Types registrieren
	 */
	private function registerPostTypes(): void {
		$job_listing = new JobListing();
		add_action( 'init', [ $job_listing, 'register' ] );
	}

	/**
	 * Taxonomien registrieren
	 */
	private function registerTaxonomies(): void {
		$job_category    = new JobCategory();
		$job_location    = new JobLocation();
		$employment_type = new EmploymentType();

		add_action( 'init', [ $job_category, 'register' ] );
		add_action( 'init', [ $job_location, 'register' ] );
		add_action( 'init', [ $employment_type, 'register' ] );
	}

	/**
	 * Admin-Bereich initialisieren
	 */
	private function initAdmin(): void {
		// Setup-Wizard initialisieren (muss vor Menü kommen).
		$wizard = new SetupWizard();
		$wizard->init();

		// Admin-Menü registrieren.
		$menu = new Menu();
		add_action( 'admin_menu', [ $menu, 'register' ] );

		// E-Mail-Templates Seite registrieren (eigenes Submenu).
		new EmailSettingsPage();

		// Meta-Boxen registrieren.
		$job_meta = new JobMeta();
		add_action( 'add_meta_boxes', [ $job_meta, 'register' ] );
		add_action( 'save_post_job_listing', [ $job_meta, 'save' ], 10, 2 );

		// Custom Fields Meta Box (Pro-Feature).
		$custom_fields_meta = new JobCustomFieldsMeta();
		add_action( 'add_meta_boxes', [ $custom_fields_meta, 'register' ] );
		add_action( 'save_post_job_listing', [ $custom_fields_meta, 'save' ], 10, 2 );

		// Aktivierungs-Redirect (Setup-Wizard).
		add_action( 'admin_init', [ $this, 'activationRedirect' ] );
	}

	/**
	 * Frontend initialisieren
	 */
	private function initFrontend(): void {
		// Template-Loader für CPT.
		add_filter( 'template_include', [ $this, 'loadTemplates' ] );

		// Google for Jobs Schema (JSON-LD).
		$job_schema = new JobSchema();
		$job_schema->init();

		// Shortcodes registrieren.
		$shortcodes = new Shortcodes();
		$shortcodes->register();

		// Security Headers für Plugin-Seiten.
		add_action( 'send_headers', [ $this, 'addSecurityHeaders' ] );
	}

	/**
	 * Security Headers hinzufügen
	 */
	public function addSecurityHeaders(): void {
		if ( ! is_post_type_archive( 'job_listing' ) && ! is_singular( 'job_listing' ) ) {
			return;
		}

		// Nur wenn Headers noch nicht gesendet wurden.
		if ( headers_sent() ) {
			return;
		}

		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}

	/**
	 * Custom Templates laden
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function loadTemplates( string $template ): string {
		if ( is_post_type_archive( 'job_listing' ) ) {
			$custom = locate_template( 'recruiting-playbook/archive-job_listing.php' );
			if ( $custom ) {
				return $custom;
			}
			return RP_PLUGIN_DIR . 'templates/archive-job_listing.php';
		}

		if ( is_singular( 'job_listing' ) ) {
			$custom = locate_template( 'recruiting-playbook/single-job_listing.php' );
			if ( $custom ) {
				return $custom;
			}
			return RP_PLUGIN_DIR . 'templates/single-job_listing.php';
		}

		return $template;
	}

	/**
	 * REST API Routen registrieren
	 */
	public function registerRestRoutes(): void {
		$application_controller = new ApplicationController();
		$application_controller->register_routes();

		// Pro-Feature Controller (Notes, Ratings, Timeline, Talent-Pool).
		$note_controller = new NoteController();
		$note_controller->register_routes();

		$rating_controller = new RatingController();
		$rating_controller->register_routes();

		$activity_controller = new ActivityController();
		$activity_controller->register_routes();

		$talent_pool_controller = new TalentPoolController();
		$talent_pool_controller->register_routes();

		// Pro-Feature Controller (E-Mail-System).
		$email_template_controller = new EmailTemplateController();
		$email_template_controller->register_routes();

		$email_controller = new EmailController();
		$email_controller->register_routes();

		$email_log_controller = new EmailLogController();
		$email_log_controller->register_routes();

		// Signature Controller (für E-Mail-Signaturen).
		$signature_controller = new SignatureController();
		$signature_controller->register_routes();

		// Settings Controller (für Firmendaten etc.).
		$settings_controller = new SettingsController();
		$settings_controller->register_routes();

		// License Controller.
		$license_controller = new LicenseController();
		$license_controller->register_routes();

		// User Roles Controllers (Pro-Feature).
		$role_controller = new RoleController();
		$role_controller->register_routes();

		$job_assignment_controller = new JobAssignmentController();
		$job_assignment_controller->register_routes();

		// Stats Controller (Reporting & Dashboard).
		$stats_controller = new StatsController();
		$stats_controller->register_routes();

		// Export Controller (CSV-Export - Pro-Feature).
		$export_controller = new ExportController();
		$export_controller->register_routes();

		// System Status Controller (Admin Only).
		$system_status_controller = new SystemStatusController();
		$system_status_controller->register_routes();

		// Custom Fields Builder Controllers (Pro-Feature).
		$field_definition_controller = new FieldDefinitionController();
		$field_definition_controller->register_routes();

		$form_template_controller = new FormTemplateController();
		$form_template_controller->register_routes();

		$form_config_controller = new FormConfigController();
		$form_config_controller->register_routes();
	}

	/**
	 * Nach Aktivierung zur Setup-Wizard Seite weiterleiten
	 */
	public function activationRedirect(): void {
		if ( get_option( 'rp_activation_redirect', false ) ) {
			delete_option( 'rp_activation_redirect' );

			// Nicht bei Multi-Aktivierung oder wenn Wizard bereits abgeschlossen.
			if ( isset( $_GET['activate-multi'] ) ) {
				return;
			}

			// Zum Wizard weiterleiten, wenn noch nicht abgeschlossen.
			if ( ! get_option( 'rp_wizard_completed', false ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=rp-setup-wizard' ) );
				exit;
			}

			// Sonst zum Dashboard.
			wp_safe_redirect( admin_url( 'admin.php?page=recruiting-playbook' ) );
			exit;
		}
	}

	/**
	 * Frontend-Assets laden
	 */
	public function enqueueFrontendAssets(): void {
		if ( ! is_post_type_archive( 'job_listing' ) && ! is_singular( 'job_listing' ) ) {
			return;
		}

		// CSS - Basis-Styles registrieren (auch wenn dist-Datei nicht existiert).
		wp_register_style( 'rp-frontend', false, [], RP_VERSION );
		wp_enqueue_style( 'rp-frontend' );

		// Kompiliertes CSS laden falls vorhanden.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/frontend.css';
		if ( file_exists( $css_file ) ) {
			wp_deregister_style( 'rp-frontend' );
			wp_enqueue_style(
				'rp-frontend',
				RP_PLUGIN_URL . 'assets/dist/css/frontend.css',
				[],
				RP_VERSION . '-' . filemtime( $css_file )
			);
		}

		// x-cloak CSS für Alpine.js - verhindert Flackern vor Initialisierung.
		wp_add_inline_style( 'rp-frontend', '[x-cloak] { display: none !important; }' );

		// Conversion Tracking (DataLayer Events für GTM).
		$tracking_file = RP_PLUGIN_DIR . 'assets/src/js/tracking.js';
		if ( file_exists( $tracking_file ) ) {
			wp_enqueue_script(
				'rp-tracking',
				RP_PLUGIN_URL . 'assets/src/js/tracking.js',
				[],
				RP_VERSION,
				true
			);

			// Debug-Modus für Tracking.
			if ( defined( 'RP_DEBUG_TRACKING' ) && RP_DEBUG_TRACKING ) {
				wp_add_inline_script( 'rp-tracking', 'window.RP_DEBUG_TRACKING = true;', 'before' );
			}
		}

		// Alpine.js Abhängigkeiten sammeln.
		$alpine_deps = [ 'rp-tracking' ];

		// Application Form JS - nur auf Einzelseiten, muss VOR Alpine.js geladen werden!
		// Registriert die Alpine-Komponente via 'alpine:init' Event.
		if ( is_singular( 'job_listing' ) ) {
			$form_file = RP_PLUGIN_DIR . 'assets/src/js/application-form.js';
			if ( file_exists( $form_file ) ) {
				wp_enqueue_script(
					'rp-application-form',
					RP_PLUGIN_URL . 'assets/src/js/application-form.js',
					[], // Keine Abhängigkeit zu Alpine - muss vorher laden!
					RP_VERSION,
					true
				);

				// Lokalisierung für das Formular.
				wp_localize_script(
					'rp-application-form',
					'rpForm',
					[
						'apiUrl' => rest_url( 'recruiting/v1/' ),
						'nonce'  => wp_create_nonce( 'wp_rest' ),
						'i18n'   => [
							'required'        => __( 'Dieses Feld ist erforderlich', 'recruiting-playbook' ),
							'invalidEmail'    => __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein', 'recruiting-playbook' ),
							'fileTooLarge'    => __( 'Die Datei ist zu groß (max. 10 MB)', 'recruiting-playbook' ),
							'invalidFileType' => __( 'Dateityp nicht erlaubt. Erlaubt: PDF, DOC, DOCX, JPG, PNG', 'recruiting-playbook' ),
							'privacyRequired' => __( 'Bitte stimmen Sie der Datenschutzerklärung zu', 'recruiting-playbook' ),
						],
					]
				);

				$alpine_deps[] = 'rp-application-form';
			}
		}

		// Alpine.js (lokal gebundelt) - muss NACH application-form.js geladen werden.
		// Das defer-Attribut sorgt dafür, dass Alpine nach DOM-Ready initialisiert.
		$alpine_file = RP_PLUGIN_DIR . 'assets/dist/js/alpine.min.js';
		if ( file_exists( $alpine_file ) ) {
			wp_enqueue_script(
				'rp-alpine',
				RP_PLUGIN_URL . 'assets/dist/js/alpine.min.js',
				$alpine_deps, // Abhängigkeit: application-form.js muss zuerst laden (falls vorhanden)
				'3.14.3',
				true
			);
		}

		// Defer-Attribut hinzufügen für Alpine.js (einmalig registrieren).
		static $filter_added = false;
		if ( ! $filter_added ) {
			add_filter(
				'script_loader_tag',
				function ( $tag, $handle ) {
					if ( 'rp-alpine' === $handle && false === strpos( $tag, 'defer' ) ) {
						return str_replace( ' src', ' defer src', $tag );
					}
					return $tag;
				},
				10,
				2
			);
			$filter_added = true;
		}

		// Frontend JS (optional, für zukünftige Erweiterungen).
		$js_file = RP_PLUGIN_DIR . 'assets/dist/js/frontend.js';
		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'rp-frontend',
				RP_PLUGIN_URL . 'assets/dist/js/frontend.js',
				[ 'rp-alpine' ],
				RP_VERSION,
				true
			);
		}
	}

	/**
	 * Admin-Assets laden
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueueAdminAssets( string $hook ): void {
		// Nur auf Plugin-Seiten laden.
		$is_plugin_page = str_starts_with( $hook, 'toplevel_page_recruiting' )
			|| str_starts_with( $hook, 'recruiting_page_' )
			|| str_starts_with( $hook, 'admin_page_rp-' ) // Versteckte Plugin-Seiten.
			|| 'job_listing' === get_post_type();

		if ( ! $is_plugin_page ) {
			return;
		}

		// Admin CSS.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/admin.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rp-admin',
				RP_PLUGIN_URL . 'assets/dist/css/admin.css',
				[],
				RP_VERSION
			);
		}

		// Admin JS.
		$js_file    = RP_PLUGIN_DIR . 'assets/dist/js/admin.js';
		$asset_file = RP_PLUGIN_DIR . 'assets/dist/js/admin.asset.php';

		if ( file_exists( $js_file ) ) {
			// Load dependencies from generated asset file.
			$asset = file_exists( $asset_file )
				? require $asset_file
				: [ 'dependencies' => [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ], 'version' => RP_VERSION ];

			wp_enqueue_script(
				'rp-admin',
				RP_PLUGIN_URL . 'assets/dist/js/admin.js',
				$asset['dependencies'],
				$asset['version'],
				true
			);

			wp_localize_script(
				'rp-admin',
				'rpAdmin',
				[
					'apiUrl'   => rest_url( 'recruiting/v1/' ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
					'adminUrl' => admin_url(),
				]
			);

			// Set translations for JS.
			wp_set_script_translations( 'rp-admin', 'recruiting-playbook', RP_PLUGIN_DIR . 'languages' );
		}

		// E-Mail Admin App Script (für Templates & Signaturen Seite).
		$this->enqueueEmailAdminAssets( $hook );
	}

	/**
	 * E-Mail Admin Assets laden
	 *
	 * @param string $hook Current admin page hook.
	 */
	private function enqueueEmailAdminAssets( string $hook ): void {
		// Nur auf E-Mail-Templates Seite laden.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'rp-email-templates' !== $page ) {
			return;
		}

		// Admin Email CSS.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/admin-email.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rp-admin-email',
				RP_PLUGIN_URL . 'assets/dist/css/admin-email.css',
				[ 'rp-admin' ],
				RP_VERSION
			);
		}

		// Admin Email JS.
		$js_file    = RP_PLUGIN_DIR . 'assets/dist/js/admin-email.js';
		$asset_file = RP_PLUGIN_DIR . 'assets/dist/js/admin-email.asset.php';

		if ( file_exists( $js_file ) ) {
			// Load dependencies from generated asset file.
			$asset = file_exists( $asset_file )
				? require $asset_file
				: [ 'dependencies' => [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ], 'version' => RP_VERSION ];

			wp_enqueue_script(
				'rp-admin-email',
				RP_PLUGIN_URL . 'assets/dist/js/admin-email.js',
				$asset['dependencies'],
				$asset['version'],
				true
			);

			// Lokalisierung für E-Mail-Admin.
			wp_localize_script(
				'rp-admin-email',
				'rpEmailData',
				[
					'apiUrl'  => rest_url( 'recruiting/v1/' ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'isAdmin' => current_user_can( 'manage_options' ),
					'userId'  => get_current_user_id(),
					'logoUrl' => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
					'i18n'    => [
						// Allgemein.
						'loading'        => __( 'Laden...', 'recruiting-playbook' ),
						'save'           => __( 'Speichern', 'recruiting-playbook' ),
						'saving'         => __( 'Speichern...', 'recruiting-playbook' ),
						'cancel'         => __( 'Abbrechen', 'recruiting-playbook' ),
						'delete'         => __( 'Löschen', 'recruiting-playbook' ),
						'edit'           => __( 'Bearbeiten', 'recruiting-playbook' ),
						'preview'        => __( 'Vorschau', 'recruiting-playbook' ),
						'name'           => __( 'Name', 'recruiting-playbook' ),
						'status'         => __( 'Status', 'recruiting-playbook' ),
						'actions'        => __( 'Aktionen', 'recruiting-playbook' ),
						'default'        => __( 'Standard', 'recruiting-playbook' ),
						'errorLoading'   => __( 'Fehler beim Laden', 'recruiting-playbook' ),
						'errorSaving'    => __( 'Fehler beim Speichern', 'recruiting-playbook' ),
						'errorDeleting'  => __( 'Fehler beim Löschen', 'recruiting-playbook' ),

						// Page.
						'pageTitle'  => __( 'E-Mail-Vorlagen & Signaturen', 'recruiting-playbook' ),

						// Tabs.
						'templates'  => __( 'Templates', 'recruiting-playbook' ),
						'signatures' => __( 'Signaturen', 'recruiting-playbook' ),

						// Templates.
						'newTemplate'       => __( 'Neues Template', 'recruiting-playbook' ),
						'editTemplate'      => __( 'Template bearbeiten', 'recruiting-playbook' ),
						'templateSaved'     => __( 'Template wurde gespeichert.', 'recruiting-playbook' ),
						'templateDeleted'   => __( 'Template wurde gelöscht.', 'recruiting-playbook' ),
						'templateDuplicated' => __( 'Template wurde dupliziert.', 'recruiting-playbook' ),
						'templateReset'     => __( 'Template wurde zurückgesetzt.', 'recruiting-playbook' ),
						'confirmDelete'     => __( 'Möchten Sie dieses Template wirklich löschen?', 'recruiting-playbook' ),
						'noTemplates'       => __( 'Keine Templates gefunden.', 'recruiting-playbook' ),
						'subject'           => __( 'Betreff', 'recruiting-playbook' ),
						'category'          => __( 'Kategorie', 'recruiting-playbook' ),
						'body'              => __( 'Inhalt', 'recruiting-playbook' ),
						'active'            => __( 'Aktiv', 'recruiting-playbook' ),
						'system'            => __( 'System', 'recruiting-playbook' ),
						'inactive'          => __( 'Inaktiv', 'recruiting-playbook' ),
						'allCategories'     => __( 'Alle Kategorien', 'recruiting-playbook' ),

						// Signaturen.
						'newSignature'            => __( 'Neue Signatur', 'recruiting-playbook' ),
						'editSignature'           => __( 'Signatur bearbeiten', 'recruiting-playbook' ),
						'mySignatures'            => __( 'Meine Signaturen', 'recruiting-playbook' ),
						'companySignature'        => __( 'Firmen-Signatur', 'recruiting-playbook' ),
						'editCompanySignature'    => __( 'Firmen-Signatur bearbeiten', 'recruiting-playbook' ),
						'signatureSaved'          => __( 'Signatur wurde gespeichert.', 'recruiting-playbook' ),
						'signatureDeleted'        => __( 'Signatur wurde gelöscht.', 'recruiting-playbook' ),
						'signatureSetDefault'     => __( 'Standard-Signatur wurde gesetzt.', 'recruiting-playbook' ),
						'setAsDefault'            => __( 'Als Standard setzen', 'recruiting-playbook' ),
						'noSignatures'            => __( 'Keine Signaturen vorhanden.', 'recruiting-playbook' ),
						'noCompanySignature'      => __( 'Keine Firmen-Signatur vorhanden.', 'recruiting-playbook' ),
						'confirmDeleteSignature'  => __( 'Möchten Sie diese Signatur wirklich löschen?', 'recruiting-playbook' ),
						'signatureContent'        => __( 'Signatur-Inhalt', 'recruiting-playbook' ),
						'signatureHint'           => __( 'Gestalten Sie Ihre E-Mail-Signatur mit Ihren Kontaktdaten.', 'recruiting-playbook' ),
						'companySignatureHint'    => __( 'Die Firmen-Signatur wird verwendet, wenn ein Benutzer keine eigene Signatur hat.', 'recruiting-playbook' ),
						'createSignatureHint'     => __( 'Erstellen Sie Ihre erste Signatur, um E-Mails zu personalisieren.', 'recruiting-playbook' ),
						'signaturePreviewHint'    => __( 'So wird Ihre Signatur in E-Mails aussehen:', 'recruiting-playbook' ),

						// Kategorien.
						'categories' => [
							'application'   => __( 'Bewerbung', 'recruiting-playbook' ),
							'status_change' => __( 'Statusänderung', 'recruiting-playbook' ),
							'interview'     => __( 'Interview', 'recruiting-playbook' ),
							'offer'         => __( 'Angebot', 'recruiting-playbook' ),
							'rejection'     => __( 'Absage', 'recruiting-playbook' ),
							'custom'        => __( 'Benutzerdefiniert', 'recruiting-playbook' ),
							'system'        => __( 'System', 'recruiting-playbook' ),
						],
					],
				]
			);

			// Set translations for JS.
			wp_set_script_translations( 'rp-admin-email', 'recruiting-playbook', RP_PLUGIN_DIR . 'languages' );
		}
	}

}

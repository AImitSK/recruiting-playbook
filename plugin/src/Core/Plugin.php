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
use RecruitingPlaybook\Admin\DashboardWidget;
use RecruitingPlaybook\Frontend\JobSchema;
use RecruitingPlaybook\Frontend\Shortcodes;
use RecruitingPlaybook\Api\JobController;
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
use RecruitingPlaybook\Api\RoleController;
use RecruitingPlaybook\Api\JobAssignmentController;
use RecruitingPlaybook\Api\StatsController;
use RecruitingPlaybook\Api\ExportController;
use RecruitingPlaybook\Api\SystemStatusController;
use RecruitingPlaybook\Api\FieldDefinitionController;
use RecruitingPlaybook\Api\FormTemplateController;
use RecruitingPlaybook\Api\MatchController;
use RecruitingPlaybook\Api\FormConfigController;
use RecruitingPlaybook\Api\WebhookController;
use RecruitingPlaybook\Api\ApiKeyController;
use RecruitingPlaybook\Api\AiAnalysisController;
use RecruitingPlaybook\Api\IntegrationController;
use RecruitingPlaybook\Integrations\Feed\XmlJobFeed;
use RecruitingPlaybook\Services\ApiKeyService;
use RecruitingPlaybook\Services\DocumentDownloadService;
use RecruitingPlaybook\Services\WebhookService;
use RecruitingPlaybook\Services\EmailQueueService;
use RecruitingPlaybook\Services\AutoEmailService;
use RecruitingPlaybook\Services\CssGeneratorService;
use RecruitingPlaybook\Blocks\BlockLoader;
use RecruitingPlaybook\Integrations\Avada\AvadaIntegration;
use RecruitingPlaybook\Integrations\Elementor\ElementorIntegration;
use RecruitingPlaybook\Integration\PolylangIntegration;
use RecruitingPlaybook\Database\Migrator;
use RecruitingPlaybook\Database\Migrations\CustomFieldsMigration;
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

		// Webhook-Hooks registrieren (Pro-Feature).
		$this->registerWebhookHooks();

		// API-Key Authentifizierung registrieren (Pro-Feature).
		$this->registerApiKeyAuth();

		// Admin-Bereich.
		if ( is_admin() ) {
			$this->initAdmin();
		}

		// Shortcodes immer registrieren (auch im Admin/AJAX-Kontext,
		// damit Fusion Builder Live-Preview Shortcodes rendern kann).
		$shortcodes = new Shortcodes();
		$shortcodes->register();

		// Frontend (Templates, Schema, etc. – nicht im Admin).
		if ( ! is_admin() ) {
			$this->initFrontend();
		}

		// Gutenberg Blocks (Pro-Feature).
		$this->initBlocks();

		// Avada Integration wird FRÜHER in recruiting-playbook.php registriert
		// (auf after_setup_theme, Priority 5, VOR FusionBuilder).

		// Elementor Integration (Pro-Feature).
		$this->initElementorIntegration();

		// Polylang Integration (Free-Feature).
		$this->initPolylangIntegration();

		// XML Job Feed (Free-Feature).
		$this->initXmlJobFeed();

		// REST API.
		add_action( 'rest_api_init', [ $this, 'registerRestRoutes' ] );

		// AJAX-Handler für Dokument-Downloads.
		DocumentDownloadService::registerAjaxHandler();

		// Assets laden.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontendAssets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );

		// Entferne WordPress Interactivity API Module auf Plugin-Seiten.
		// Diese Module verursachen JS-Fehler die Alpine.js blockieren.
		add_action(
			'wp_enqueue_scripts',
			function () {
				if ( is_post_type_archive( 'job_listing' ) || is_singular( 'job_listing' ) ) {
					// Deregistriere alle WordPress Script-Module.
					wp_deregister_script_module( '@wordpress/interactivity' );
					wp_deregister_script_module( '@wordpress/interactivity-router' );
					wp_deregister_script_module( '@wordpress/block-library/navigation/view' );
				}
			},
			1
		);

		// Fallback: Entferne Module-Scripts via Output Buffer falls Deregistrierung nicht wirkt.
		add_action(
			'template_redirect',
			function () {
				if ( is_post_type_archive( 'job_listing' ) || is_singular( 'job_listing' ) ) {
					ob_start(
						function ( $html ) {
							// Entferne alle script type="module" Tags.
							$html = preg_replace( '/<script[^>]*type=["\']module["\'][^>]*>.*?<\/script>/s', '', $html );
							return $html;
						}
					);
				}
			}
		);

		// Output Buffer beenden.
		add_action(
			'shutdown',
			function () {
				if ( is_post_type_archive( 'job_listing' ) || is_singular( 'job_listing' ) ) {
					if ( ob_get_level() > 0 ) {
						ob_end_flush();
					}
				}
			},
			0
		);
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
					esc_html__( 'Action Scheduler library is missing. Please run %s in the plugin directory.', 'recruiting-playbook' ),
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
				<?php esc_html_e( 'Action Scheduler was loaded, but its functions are not available. Email queue may not be functional.', 'recruiting-playbook' ); ?>
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
	 * Lädt Übersetzungsdateien aus dem languages/ Verzeichnis.
	 * Unterstützt .mo-Dateien (PHP) und .json-Dateien (JavaScript).
	 */
	private function loadI18n(): void {
		load_plugin_textdomain(
			'recruiting-playbook',
			false,
			dirname( plugin_basename( RP_PLUGIN_FILE ) ) . '/languages'
		);
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
	 * Webhook Event-Hooks registrieren
	 *
	 * Verbindet Application- und Job-Events mit dem WebhookService
	 * für asynchrone Benachrichtigung externer Systeme.
	 * Pro-Feature: Nur aktiv wenn Webhooks Feature verfügbar ist.
	 */
	private function registerWebhookHooks(): void {
		if ( function_exists( 'rp_can' ) && ! rp_can( 'webhooks' ) ) {
			return;
		}

		$service = new WebhookService();

		// Application Events.
		add_action( 'rp_application_created', [ $service, 'onApplicationCreated' ], 10, 2 );
		add_action( 'rp_application_status_changed', [ $service, 'onApplicationStatusChanged' ], 10, 3 );

		// Job Events (über WP-Hooks).
		add_action( 'transition_post_status', [ $service, 'onJobStatusTransition' ], 10, 3 );
		add_action( 'save_post_job_listing', [ $service, 'onJobSaved' ], 10, 3 );
		add_action( 'before_delete_post', [ $service, 'onJobDeleted' ], 10, 1 );

		// Action Scheduler Hook für asynchrone Delivery.
		add_action( 'rp_deliver_webhook', [ $service, 'deliver' ], 10, 1 );
	}

	/**
	 * API-Key Authentifizierung registrieren
	 *
	 * Ermöglicht Authentifizierung über X-Recruiting-API-Key Header
	 * oder api_key Query-Parameter. Pro-Feature.
	 */
	private function registerApiKeyAuth(): void {
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return;
		}

		add_filter( 'determine_current_user', [ $this, 'authenticateApiKey' ], 20 );
		add_filter( 'rest_pre_dispatch', [ $this, 'checkApiKeyRateLimit' ], 10, 3 );

		// Rate-Limit-Headers in Response setzen.
		add_filter(
			'rest_post_dispatch',
			function ( $response ) {
				if ( ! empty( $GLOBALS['rp_rate_limit_headers'] ) && $response instanceof \WP_REST_Response ) {
					foreach ( $GLOBALS['rp_rate_limit_headers'] as $header => $value ) {
						$response->header( $header, (string) $value );
					}
				}
				return $response;
			}
		);
	}

	/**
	 * API-Key aus Request extrahieren und User authentifizieren
	 *
	 * @param int|false $user_id Aktuelle User-ID.
	 * @return int|false User-ID.
	 */
	public function authenticateApiKey( $user_id ) {
		// Bereits authentifiziert → durchreichen.
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		// Nur auf REST-Requests reagieren.
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return $user_id;
		}

		// API-Key aus Header oder Query-Parameter extrahieren.
		$api_key = '';
		if ( ! empty( $_SERVER['HTTP_X_RECRUITING_API_KEY'] ) ) {
			$api_key = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_RECRUITING_API_KEY'] ) );
		} elseif ( ! empty( $_GET['api_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$api_key = sanitize_text_field( wp_unslash( $_GET['api_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( empty( $api_key ) ) {
			return $user_id;
		}

		$service  = new ApiKeyService();
		$key_data = $service->validateKey( $api_key );

		if ( ! $key_data ) {
			return $user_id;
		}

		// Key-Daten für spätere Permission-Prüfungen speichern.
		$GLOBALS['rp_authenticated_api_key'] = $key_data;

		// created_by als authentifizierten User zurückgeben.
		return (int) $key_data->created_by;
	}

	/**
	 * Rate Limiting für API-Key-authentifizierte Requests
	 *
	 * @param mixed            $result  Response.
	 * @param \WP_REST_Server  $server  REST Server.
	 * @param WP_REST_Request  $request Request.
	 * @return mixed Response oder WP_Error bei Rate Limit.
	 */
	public function checkApiKeyRateLimit( $result, $server, $request ) {
		// Nur wenn API-Key-Auth aktiv.
		if ( empty( $GLOBALS['rp_authenticated_api_key'] ) ) {
			return $result;
		}

		// Nur auf recruiting/v1/* Routen.
		$route = $request->get_route();
		if ( ! str_starts_with( $route, '/recruiting/v1/' ) ) {
			return $result;
		}

		$service    = new ApiKeyService();
		$key_data   = $GLOBALS['rp_authenticated_api_key'];
		$rate_check = $service->checkRateLimit( $key_data );

		// Rate-Limit-Headers für Response speichern.
		$GLOBALS['rp_rate_limit_headers'] = [
			'X-RateLimit-Limit'     => $rate_check['limit'],
			'X-RateLimit-Remaining' => $rate_check['remaining'],
			'X-RateLimit-Reset'     => $rate_check['reset'],
		];

		if ( ! $rate_check['allowed'] ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Rate limit exceeded. Please try again later.', 'recruiting-playbook' ),
				[
					'status' => 429,
					'retry_after' => $rate_check['reset'] - time(),
				]
			);
		}

		return $result;
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
		// Taxonomien könnten bereits früher registriert sein (Avada/Fusion Builder Kompatibilität).
		if ( ! taxonomy_exists( JobCategory::TAXONOMY ) ) {
			add_action( 'init', [ new JobCategory(), 'register' ] );
		}
		if ( ! taxonomy_exists( JobLocation::TAXONOMY ) ) {
			add_action( 'init', [ new JobLocation(), 'register' ] );
		}
		if ( ! taxonomy_exists( EmploymentType::TAXONOMY ) ) {
			add_action( 'init', [ new EmploymentType(), 'register' ] );
		}
	}

	/**
	 * Gutenberg Blocks initialisieren
	 *
	 * Pro-Feature: Registriert native WordPress-Blöcke für den Block-Editor.
	 * Blocks werden nur geladen wenn Pro-Lizenz aktiv ist.
	 */
	private function initBlocks(): void {
		$block_loader = new BlockLoader();
		$block_loader->register();
	}

	/**
	 * Elementor Integration initialisieren
	 *
	 * Pro-Feature: Registriert native Elementor Widgets.
	 * Widgets werden nur geladen wenn Pro-Lizenz und Elementor aktiv sind.
	 */
	private function initElementorIntegration(): void {
		// Nur laden wenn Elementor verfügbar ist.
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$elementor_integration = new ElementorIntegration();
		$elementor_integration->register();
	}

	/**
	 * XML Job Feed initialisieren
	 *
	 * Free-Feature: Stellt einen XML-Feed unter /feed/jobs/ bereit,
	 * den Jobbörsen automatisch einlesen können.
	 */
	private function initXmlJobFeed(): void {
		$settings = get_option( 'rp_integrations', [] );

		// Feed nur registrieren wenn aktiviert (Default: true).
		if ( isset( $settings['xml_feed_enabled'] ) && ! $settings['xml_feed_enabled'] ) {
			return;
		}

		$xml_feed = new XmlJobFeed();
		$xml_feed->register();
	}

	/**
	 * Polylang Integration initialisieren
	 *
	 * Free-Feature: Registriert Custom Post Types und Taxonomies für Polylang.
	 * Integration wird nur geladen wenn Polylang verfügbar ist.
	 */
	private function initPolylangIntegration(): void {
		// Nur laden wenn Polylang verfügbar ist.
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		$polylang_integration = new PolylangIntegration();
		$polylang_integration->register();
	}

	/**
	 * Avada / Fusion Builder Integration initialisieren
	 *
	 * Pro-Feature: Registriert native Fusion Builder Elements für Avada.
	 * Elements werden nur geladen wenn Pro-Lizenz und Avada aktiv sind.
	 */
	private function initAvadaIntegration(): void {
		// Nur laden wenn Avada/Fusion Builder verfügbar ist.
		if ( ! class_exists( 'FusionBuilder' ) ) {
			return;
		}

		$avada_integration = new AvadaIntegration();
		$avada_integration->register();
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

		// Dashboard-Widget registrieren.
		$dashboard_widget = new DashboardWidget();
		$dashboard_widget->register();

		// Aktivierungs-Redirect (Setup-Wizard).
		add_action( 'admin_init', [ $this, 'activationRedirect' ] );
	}

	/**
	 * Frontend initialisieren
	 */
	private function initFrontend(): void {
		// Template-Loader für CPT (hohe Priorität für Avada-Kompatibilität).
		add_filter( 'template_include', [ $this, 'loadTemplates' ], 99 );

		// Google for Jobs Schema (JSON-LD).
		$job_schema = new JobSchema();
		$job_schema->init();

		// Shortcodes werden bereits in init() registriert (auch für AJAX-Kontext).

		// Design & Branding CSS-Variablen werden über wp_add_inline_style geladen.
		// Siehe enqueueFrontendAssets() - dort werden sie an rp-frontend angehängt.
		// WICHTIG: Keine Lizenzprüfung - Design bleibt nach Ablauf erhalten.

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
		// Hauptarchiv für job_listing.
		if ( is_post_type_archive( 'job_listing' ) ) {
			$custom = locate_template( 'recruiting-playbook/archive-job_listing.php' );
			if ( $custom ) {
				return $custom;
			}
			return RP_PLUGIN_DIR . 'templates/archive-job_listing.php';
		}

		// Taxonomie-Archive (job_category, job_location, employment_type).
		// Verwenden das gleiche Template wie das Hauptarchiv.
		if ( is_tax( 'job_category' ) || is_tax( 'job_location' ) || is_tax( 'employment_type' ) ) {
			$custom = locate_template( 'recruiting-playbook/archive-job_listing.php' );
			if ( $custom ) {
				return $custom;
			}
			return RP_PLUGIN_DIR . 'templates/archive-job_listing.php';
		}

		// Einzelseiten für job_listing (inkl. Vorschau von Entwürfen).
		if ( is_singular( 'job_listing' ) || $this->isJobListingPreview() ) {
			$custom = locate_template( 'recruiting-playbook/single-job_listing.php' );
			if ( $custom ) {
				return $custom;
			}
			return RP_PLUGIN_DIR . 'templates/single-job_listing.php';
		}

		return $template;
	}

	/**
	 * Prüft ob eine Vorschau eines job_listing Posts angezeigt wird
	 *
	 * Fallback für Themes (z.B. Avada) die is_singular() in Previews
	 * nicht korrekt auflösen.
	 *
	 * @return bool
	 */
	private function isJobListingPreview(): bool {
		if ( ! is_preview() || ! is_user_logged_in() ) {
			return false;
		}

		$post_id = get_query_var( 'p' );
		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		return $post && 'job_listing' === $post->post_type && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * REST API Routen registrieren
	 */
	public function registerRestRoutes(): void {
		$job_controller = new JobController();
		$job_controller->register_routes();

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

		// Match Controller (KI-Matching - Pro Feature).
		$match_controller = new MatchController();
		$match_controller->register_routes();

		// Form Config Controller (Form Builder - Pro Feature).
		$form_config_controller = new FormConfigController();
		$form_config_controller->register_routes();

		// Webhook Controller (Pro-Feature).
		$webhook_controller = new WebhookController();
		$webhook_controller->register_routes();

		// API-Key Controller (Pro-Feature).
		$api_key_controller = new ApiKeyController();
		$api_key_controller->register_routes();

		// AI Analysis Controller (Pro Feature).
		$ai_analysis_controller = new AiAnalysisController();
		$ai_analysis_controller->register_routes();

		// Integration Settings Controller (Free + Pro Features).
		$integration_controller = new IntegrationController();
		$integration_controller->register_routes();
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
	 * Frontend-Assets registrieren und laden
	 *
	 * Assets werden immer registriert (für Shortcodes auf beliebigen Seiten).
	 * Auf Job-Archive und Einzelseiten werden sie automatisch geladen.
	 */
	public function enqueueFrontendAssets(): void {
		// CSS registrieren (für Shortcodes auf beliebigen Seiten).
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/frontend.css';
		if ( file_exists( $css_file ) ) {
			wp_register_style(
				'rp-frontend',
				RP_PLUGIN_URL . 'assets/dist/css/frontend.css',
				[],
				RP_VERSION . '-' . filemtime( $css_file )
			);
		} else {
			wp_register_style( 'rp-frontend', false, [], RP_VERSION );
		}

		// Design & Branding CSS-Variablen als Inline-Style anhängen.
		// WICHTIG: Keine Lizenzprüfung - Design bleibt nach Ablauf erhalten.
		$css_generator = new CssGeneratorService();
		$css           = $css_generator->generate_css();
		$inline_css    = '[x-cloak] { display: none !important; }';
		if ( ! empty( $css ) ) {
			$inline_css .= "\n" . $css;
		}
		wp_add_inline_style( 'rp-frontend', $inline_css );

		// Auf Job-Seiten automatisch laden, Shortcodes laden selbst via wp_enqueue_style().
		if ( is_post_type_archive( 'job_listing' ) || is_singular( 'job_listing' ) ) {
			wp_enqueue_style( 'rp-frontend' );
		}

		// Im Fusion Builder Editor immer laden (Shortcodes werden per AJAX gerendert).
		// Parent-Frame hat ?fb-edit, Preview-iframe hat ?builder=true.
		if ( isset( $_GET['fb-edit'] ) || isset( $_GET['builder'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style( 'rp-frontend' );
		}

		// Im Elementor Editor/Preview immer laden (Widgets nutzen do_shortcode() für Render).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) {
			wp_enqueue_style( 'rp-frontend' );
		}

		// Alpine.js Abhängigkeiten sammeln.
		$alpine_deps = [];

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

			// Google Ads Conversion Config (Pro).
			$integrations = get_option( 'rp_integrations', [] );
			if ( ! empty( $integrations['google_ads_enabled'] ) && ! empty( $integrations['google_ads_conversion_id'] ) ) {
				$ads_config = wp_json_encode( [
					'conversionId'    => sanitize_text_field( $integrations['google_ads_conversion_id'] ),
					'conversionLabel' => sanitize_text_field( $integrations['google_ads_conversion_label'] ?? '' ),
					'conversionValue' => $integrations['google_ads_conversion_value'] ?? '',
				] );
				wp_add_inline_script( 'rp-tracking', 'window.rpGoogleAdsConfig = ' . $ads_config . ';', 'before' );
			}

			// Tracking als Alpine-Dependency hinzufügen (nur wenn geladen).
			$alpine_deps[] = 'rp-tracking';
		}

		// Application Form JS - nur auf Einzelseiten, muss VOR Alpine.js geladen werden!
		// Registriert die Alpine-Komponente via 'alpine:init' Event.
		if ( is_singular( 'job_listing' ) ) {
			$form_file = RP_PLUGIN_DIR . 'assets/dist/js/application-form.js';
			if ( file_exists( $form_file ) ) {
				wp_enqueue_script(
					'rp-application-form',
					RP_PLUGIN_URL . 'assets/dist/js/application-form.js',
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
							'required'        => __( 'This field is required', 'recruiting-playbook' ),
							'invalidEmail'    => __( 'Please enter a valid email address', 'recruiting-playbook' ),
							'fileTooLarge'    => __( 'File is too large (max. 10 MB)', 'recruiting-playbook' ),
							'invalidFileType' => __( 'File type not allowed. Allowed: PDF, DOC, DOCX, JPG, PNG', 'recruiting-playbook' ),
							'privacyRequired' => __( 'Please agree to the privacy policy', 'recruiting-playbook' ),
						],
					]
				);

				$alpine_deps[] = 'rp-application-form';
			}
		}

		// Match-Modal JS & CSS (Pro Feature) - auf Archiv und Einzelseiten.
		if ( ( is_singular( 'job_listing' ) || is_post_type_archive( 'job_listing' ) ) && function_exists( 'rp_has_cv_matching' ) && rp_has_cv_matching() ) {
			// CSS.
			$match_css_file = RP_PLUGIN_DIR . 'assets/dist/css/match-modal.css';
			if ( file_exists( $match_css_file ) ) {
				wp_enqueue_style(
					'rp-match-modal',
					RP_PLUGIN_URL . 'assets/dist/css/match-modal.css',
					[ 'rp-frontend' ],
					RP_VERSION . '-' . filemtime( $match_css_file )
				);
			}

			// JS (muss vor Alpine.js laden).
			$match_js_file = RP_PLUGIN_DIR . 'assets/src/js/components/match-modal.js';
			if ( file_exists( $match_js_file ) ) {
				wp_enqueue_script(
					'rp-match-modal',
					RP_PLUGIN_URL . 'assets/src/js/components/match-modal.js',
					[], // Keine Abhängigkeit zu Alpine - muss vorher laden!
					RP_VERSION,
					true
				);

				// Lokalisierung.
				wp_localize_script(
					'rp-match-modal',
					'rpMatchConfig',
					[
						'endpoints' => [
							'analyze' => rest_url( 'recruiting/v1/match/analyze' ),
							'status'  => rest_url( 'recruiting/v1/match/status' ),
						],
						'nonce'     => wp_create_nonce( 'wp_rest' ),
						'i18n'      => [
							'error'           => __( 'An error occurred', 'recruiting-playbook' ),
							'analysisFailed'  => __( 'Analysis failed', 'recruiting-playbook' ),
							'timeout'         => __( 'The analysis is taking too long. Please try again later.', 'recruiting-playbook' ),
							'invalidFileType' => __( 'Please upload a PDF, JPG, PNG or DOCX file.', 'recruiting-playbook' ),
							'fileTooLarge'    => __( 'File is too large. Maximum: 10 MB.', 'recruiting-playbook' ),
							'resultLow'       => __( 'Low match', 'recruiting-playbook' ),
							'resultMedium'    => __( 'Partial match', 'recruiting-playbook' ),
							'resultHigh'      => __( 'Good match', 'recruiting-playbook' ),
						],
					]
				);

				$alpine_deps[] = 'rp-match-modal';
			}

			// Modal-Template wird durch den Shortcode [rp_ai_job_match] eingebunden.
			// Siehe Shortcodes::registerMatchModal().
		}

		// Frontend JS - MUSS VOR Alpine.js geladen werden für alpine:init Event.
		// Registriert rpFileUpload und andere Komponenten.
		$js_file = RP_PLUGIN_DIR . 'assets/dist/js/frontend.js';
		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'rp-frontend-js',
				RP_PLUGIN_URL . 'assets/dist/js/frontend.js',
				[], // Keine Abhängigkeit - muss VOR Alpine laden!
				RP_VERSION,
				true
			);
			$alpine_deps[] = 'rp-frontend-js';
		}

		// Alpine.js (lokal gebundelt) - muss NACH Komponenten-Scripts geladen werden.
		// WICHTIG: Kein defer! Komponenten müssen VOR Alpine.start() registriert sein.
		// Alpine startet automatisch via queueMicrotask nach dem Script-Load.
		// Siehe: https://github.com/alpinejs/alpine/discussions/3978
		$alpine_file = RP_PLUGIN_DIR . 'assets/dist/js/alpine.min.js';
		if ( file_exists( $alpine_file ) ) {
			wp_enqueue_script(
				'rp-alpine',
				RP_PLUGIN_URL . 'assets/dist/js/alpine.min.js',
				$alpine_deps, // Abhängigkeit: Komponenten-Scripts müssen zuerst laden
				'3.14.3',
				true // Im Footer laden - nach allen anderen Scripts
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
						// General.
						'loading'        => __( 'Loading...', 'recruiting-playbook' ),
						'save'           => __( 'Save', 'recruiting-playbook' ),
						'saving'         => __( 'Saving...', 'recruiting-playbook' ),
						'cancel'         => __( 'Cancel', 'recruiting-playbook' ),
						'delete'         => __( 'Delete', 'recruiting-playbook' ),
						'edit'           => __( 'Edit', 'recruiting-playbook' ),
						'preview'        => __( 'Preview', 'recruiting-playbook' ),
						'name'           => __( 'Name', 'recruiting-playbook' ),
						'status'         => __( 'Status', 'recruiting-playbook' ),
						'actions'        => __( 'Actions', 'recruiting-playbook' ),
						'default'        => __( 'Default', 'recruiting-playbook' ),
						'errorLoading'   => __( 'Error loading', 'recruiting-playbook' ),
						'errorSaving'    => __( 'Error saving', 'recruiting-playbook' ),
						'errorDeleting'  => __( 'Error deleting', 'recruiting-playbook' ),

						// Page.
						'pageTitle'  => __( 'Email Templates & Signatures', 'recruiting-playbook' ),

						// Tabs.
						'templates'  => __( 'Templates', 'recruiting-playbook' ),
						'signatures' => __( 'Signatures', 'recruiting-playbook' ),

						// Templates.
						'newTemplate'       => __( 'New Template', 'recruiting-playbook' ),
						'editTemplate'      => __( 'Edit Template', 'recruiting-playbook' ),
						'templateSaved'     => __( 'Template saved.', 'recruiting-playbook' ),
						'templateDeleted'   => __( 'Template deleted.', 'recruiting-playbook' ),
						'templateDuplicated' => __( 'Template duplicated.', 'recruiting-playbook' ),
						'templateReset'     => __( 'Template reset.', 'recruiting-playbook' ),
						'confirmDelete'     => __( 'Do you really want to delete this template?', 'recruiting-playbook' ),
						'noTemplates'       => __( 'No templates found.', 'recruiting-playbook' ),
						'subject'           => __( 'Subject', 'recruiting-playbook' ),
						'category'          => __( 'Category', 'recruiting-playbook' ),
						'body'              => __( 'Body', 'recruiting-playbook' ),
						'active'            => __( 'Active', 'recruiting-playbook' ),
						'system'            => __( 'System', 'recruiting-playbook' ),
						'inactive'          => __( 'Inactive', 'recruiting-playbook' ),
						'allCategories'     => __( 'All Categories', 'recruiting-playbook' ),

						// Signatures.
						'newSignature'            => __( 'New Signature', 'recruiting-playbook' ),
						'editSignature'           => __( 'Edit Signature', 'recruiting-playbook' ),
						'mySignatures'            => __( 'My Signatures', 'recruiting-playbook' ),
						'companySignature'        => __( 'Company Signature', 'recruiting-playbook' ),
						'editCompanySignature'    => __( 'Edit Company Signature', 'recruiting-playbook' ),
						'signatureSaved'          => __( 'Signature saved.', 'recruiting-playbook' ),
						'signatureDeleted'        => __( 'Signature deleted.', 'recruiting-playbook' ),
						'signatureSetDefault'     => __( 'Default signature set.', 'recruiting-playbook' ),
						'setAsDefault'            => __( 'Set as Default', 'recruiting-playbook' ),
						'noSignatures'            => __( 'No signatures available.', 'recruiting-playbook' ),
						'noCompanySignature'      => __( 'No company signature available.', 'recruiting-playbook' ),
						'confirmDeleteSignature'  => __( 'Do you really want to delete this signature?', 'recruiting-playbook' ),
						'signatureContent'        => __( 'Signature Content', 'recruiting-playbook' ),
						'signatureHint'           => __( 'Design your email signature with your contact details.', 'recruiting-playbook' ),
						'companySignatureHint'    => __( 'The company signature is used when a user does not have their own signature.', 'recruiting-playbook' ),
						'createSignatureHint'     => __( 'Create your first signature to personalize emails.', 'recruiting-playbook' ),
						'signaturePreviewHint'    => __( 'This is how your signature will appear in emails:', 'recruiting-playbook' ),

						// Categories.
						'categories' => [
							'application'   => __( 'Application', 'recruiting-playbook' ),
							'status_change' => __( 'Status Change', 'recruiting-playbook' ),
							'interview'     => __( 'Interview', 'recruiting-playbook' ),
							'offer'         => __( 'Offer', 'recruiting-playbook' ),
							'rejection'     => __( 'Rejection', 'recruiting-playbook' ),
							'custom'        => __( 'Custom', 'recruiting-playbook' ),
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

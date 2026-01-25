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
use RecruitingPlaybook\Admin\SetupWizard\SetupWizard;
use RecruitingPlaybook\Frontend\JobSchema;
use RecruitingPlaybook\Frontend\Shortcodes;
use RecruitingPlaybook\Api\ApplicationController;
use RecruitingPlaybook\Services\DocumentDownloadService;
use RecruitingPlaybook\Database\Migrator;
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

		// Meta-Boxen registrieren.
		$job_meta = new JobMeta();
		add_action( 'add_meta_boxes', [ $job_meta, 'register' ] );
		add_action( 'save_post_job_listing', [ $job_meta, 'save' ], 10, 2 );

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
		$js_file = RP_PLUGIN_DIR . 'assets/dist/js/admin.js';
		if ( file_exists( $js_file ) ) {
			wp_enqueue_script(
				'rp-admin',
				RP_PLUGIN_URL . 'assets/dist/js/admin.js',
				[ 'wp-element', 'wp-components', 'wp-api-fetch' ],
				RP_VERSION,
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
		}
	}

}

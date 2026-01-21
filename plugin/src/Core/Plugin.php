<?php
/**
 * Haupt-Plugin-Klasse (Singleton)
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Core;

use RecruitingPlaybook\PostTypes\JobListing;
use RecruitingPlaybook\Taxonomies\JobCategory;
use RecruitingPlaybook\Taxonomies\JobLocation;
use RecruitingPlaybook\Taxonomies\EmploymentType;
use RecruitingPlaybook\Admin\Menu;
use RecruitingPlaybook\Admin\MetaBoxes\JobMeta;

/**
 * Haupt-Plugin-Klasse (Singleton)
 */
final class Plugin {

	/**
	 * Singleton instance
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Private constructor for singleton
	 */
	private function __construct() {}

	/**
	 * Get singleton instance
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Plugin initialisieren
	 */
	public function init(): void {
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

		// Assets laden.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontendAssets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
	}

	/**
	 * Übersetzungen laden
	 */
	private function loadI18n(): void {
		load_plugin_textdomain(
			'recruiting-playbook',
			false,
			dirname( RP_PLUGIN_BASENAME ) . '/languages/'
		);
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
		// Für Phase 1B (Bewerbungsformular).
	}

	/**
	 * Nach Aktivierung zur Dashboard-Seite weiterleiten
	 */
	public function activationRedirect(): void {
		if ( get_option( 'rp_activation_redirect', false ) ) {
			delete_option( 'rp_activation_redirect' );

			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=recruiting-playbook' ) );
				exit;
			}
		}
	}

	/**
	 * Frontend-Assets laden
	 */
	public function enqueueFrontendAssets(): void {
		if ( ! is_post_type_archive( 'job_listing' ) && ! is_singular( 'job_listing' ) ) {
			return;
		}

		// CSS.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/frontend.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rp-frontend',
				RP_PLUGIN_URL . 'assets/dist/css/frontend.css',
				[],
				RP_VERSION
			);
		}

		// Alpine.js.
		$alpine_file = RP_PLUGIN_DIR . 'assets/dist/js/alpine.min.js';
		if ( file_exists( $alpine_file ) ) {
			wp_enqueue_script(
				'rp-alpine',
				RP_PLUGIN_URL . 'assets/dist/js/alpine.min.js',
				[],
				RP_VERSION,
				true
			);
		}

		// Frontend JS.
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

	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}

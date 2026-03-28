<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada;

defined( 'ABSPATH' ) || exit;

/**
 * Avada / Fusion Builder Integration
 *
 * Registers all Recruiting Playbook elements for Fusion Builder.
 * This is a Pro feature and requires an active Pro license.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class AvadaIntegration {

	/**
	 * Initialize integration
	 *
	 * @return void
	 */
	public function register(): void {
		// Fusion Builder muss aktiv sein.
		if ( ! class_exists( 'FusionBuilder' ) ) {
			return;
		}

		// Register elements (in $all_fusion_builder_elements)
		add_action( 'fusion_builder_before_init', [ $this, 'registerElements' ], 11 );

		// IMPORTANT: Re-add elements after filtering
		// Fusion Builder filters based on user settings,
		// but we want our elements to ALWAYS be available.
		add_filter( 'fusion_builder_all_elements', [ $this, 'ensureElementsAvailable' ], 20 );

		// Add element category
		add_filter( 'fusion_builder_element_categories', [ $this, 'addCategory' ] );

		// Load editor assets (Backend + Live Builder).
		add_action( 'fusion_builder_admin_scripts_hook', [ $this, 'enqueueEditorAssets' ] );
		add_action( 'fusion_builder_enqueue_live_scripts', [ $this, 'enqueueEditorAssets' ] );
	}

	/**
	 * Register elements
	 *
	 * @return void
	 */
	public function registerElements(): void {
		$loader = new ElementLoader();
		$loader->registerAll();
	}

	/**
	 * Ensures RP elements are available after filtering
	 *
	 * Fusion Builder filters elements based on user settings.
	 * Our elements should always be available (as long as Pro is active).
	 *
	 * @param array $elements Filtered elements.
	 * @return array Elements with RP elements.
	 */
	public function ensureElementsAvailable( array $elements ): array {
		global $all_fusion_builder_elements;

		// Get RP elements from the global list and add them.
		if ( ! empty( $all_fusion_builder_elements ) ) {
			foreach ( $all_fusion_builder_elements as $shortcode => $config ) {
				if ( strpos( $shortcode, 'rp_' ) === 0 && ! isset( $elements[ $shortcode ] ) ) {
					$elements[ $shortcode ] = $config;
				}
			}
		}

		return $elements;
	}

	/**
	 * Add category for element picker
	 *
	 * @param array $categories Existing categories.
	 * @return array Categories with Recruiting Playbook.
	 */
	public function addCategory( array $categories ): array {
		$categories['recruiting_playbook'] = esc_attr__( 'Recruiting Playbook', 'recruiting-playbook' );
		return $categories;
	}

	/**
	 * Load editor assets
	 *
	 * @return void
	 */
	public function enqueueEditorAssets(): void {
		// Frontend CSS is loaded in Plugin::enqueueFrontendAssets() via ?builder / ?fb-edit.
		wp_enqueue_style(
			'rp-avada-editor',
			RP_PLUGIN_URL . 'assets/css/avada-editor.css',
			[],
			RP_VERSION
		);

		// Load preview templates in footer (Backend + Live Builder).
		add_action( 'admin_footer', [ $this, 'outputPreviewTemplates' ], 99 );
		add_action( 'wp_footer', [ $this, 'outputPreviewTemplates' ], 99 );
	}

	/**
	 * Output preview templates for Fusion Builder
	 *
	 * Loads all Underscore.js preview templates into the footer,
	 * so the backend and live builder can use them for element preview.
	 *
	 * @return void
	 */
	public function outputPreviewTemplates(): void {
		$previews_dir = RP_PLUGIN_DIR . 'src/Integrations/Avada/previews/';

		if ( ! is_dir( $previews_dir ) ) {
			return;
		}

		$files = glob( $previews_dir . '*-preview.php' );

		if ( ! empty( $files ) ) {
			foreach ( $files as $file ) {
				include $file;
			}
		}
	}
}

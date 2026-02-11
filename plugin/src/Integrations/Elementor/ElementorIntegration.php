<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor Integration
 *
 * Registers all Recruiting Playbook Widgets for Elementor.
 * This is a Pro feature and requires an active Pro license.
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class ElementorIntegration {

	/**
	 * Initialize integration
	 */
	public function register(): void {
		// Pro feature check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'elementor_integration' ) ) {
			return;
		}

		// Elementor check.
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Minimum version check.
		if ( ! defined( 'ELEMENTOR_VERSION' ) || ! version_compare( ELEMENTOR_VERSION, '3.0.0', '>=' ) ) {
			return;
		}

		// Register widget category.
		add_action( 'elementor/elements/categories_registered', [ $this, 'registerCategory' ] );

		// Register widgets.
		add_action( 'elementor/widgets/register', [ $this, 'registerWidgets' ] );

		// Load editor assets.
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueueEditorAssets' ] );
	}

	/**
	 * Register widget category
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor Elements Manager.
	 */
	public function registerCategory( \Elementor\Elements_Manager $elements_manager ): void {
		$elements_manager->add_category(
			'recruiting-playbook',
			[
				'title' => esc_html__( 'Recruiting Playbook', 'recruiting-playbook' ),
				'icon'  => 'eicon-user-circle-o',
			]
		);
	}

	/**
	 * Register widgets
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor Widgets Manager.
	 */
	public function registerWidgets( \Elementor\Widgets_Manager $widgets_manager ): void {
		$loader = new WidgetLoader( $widgets_manager );
		$loader->registerAll();
	}

	/**
	 * Load editor assets
	 */
	public function enqueueEditorAssets(): void {
		wp_enqueue_style(
			'rp-elementor-editor',
			RP_PLUGIN_URL . 'assets/css/elementor-editor.css',
			[],
			RP_VERSION
		);
	}
}

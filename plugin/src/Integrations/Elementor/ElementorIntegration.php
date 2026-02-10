<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor Integration
 *
 * Registriert alle Recruiting Playbook Widgets für Elementor.
 * Dies ist ein Pro-Feature und erfordert eine aktive Pro-Lizenz.
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class ElementorIntegration {

	/**
	 * Integration initialisieren
	 */
	public function register(): void {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'elementor_integration' ) ) {
			return;
		}

		// Elementor Check.
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Minimum-Version Check.
		if ( ! defined( 'ELEMENTOR_VERSION' ) || ! version_compare( ELEMENTOR_VERSION, '3.0.0', '>=' ) ) {
			return;
		}

		// Widget-Kategorie registrieren.
		add_action( 'elementor/elements/categories_registered', [ $this, 'registerCategory' ] );

		// Widgets registrieren.
		add_action( 'elementor/widgets/register', [ $this, 'registerWidgets' ] );

		// Editor-Assets laden.
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueueEditorAssets' ] );
	}

	/**
	 * Widget-Kategorie registrieren
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
	 * Widgets registrieren
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor Widgets Manager.
	 */
	public function registerWidgets( \Elementor\Widgets_Manager $widgets_manager ): void {
		$loader = new WidgetLoader( $widgets_manager );
		$loader->registerAll();
	}

	/**
	 * Editor-Assets laden
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

<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada;

defined( 'ABSPATH' ) || exit;

/**
 * Avada / Fusion Builder Integration
 *
 * Registriert alle Recruiting Playbook Elements für den Fusion Builder.
 * Dies ist ein Pro-Feature und erfordert eine aktive Pro-Lizenz.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class AvadaIntegration {

	/**
	 * Integration initialisieren
	 *
	 * @return void
	 */
	public function register(): void {
		// Pro-Feature Check
		if ( function_exists( 'rp_can' ) && ! rp_can( 'avada_integration' ) ) {
			return;
		}

		// Fusion Builder Check
		if ( ! class_exists( 'FusionBuilder' ) ) {
			return;
		}

		// Elements registrieren
		add_action( 'fusion_builder_before_init', [ $this, 'registerElements' ], 11 );

		// Element-Kategorie hinzufügen
		add_filter( 'fusion_builder_element_categories', [ $this, 'addCategory' ] );

		// Editor-Assets laden
		add_action( 'fusion_builder_enqueue_scripts', [ $this, 'enqueueEditorAssets' ] );
	}

	/**
	 * Elements registrieren
	 *
	 * @return void
	 */
	public function registerElements(): void {
		$loader = new ElementLoader();
		$loader->registerAll();
	}

	/**
	 * Kategorie für Element-Picker hinzufügen
	 *
	 * @param array $categories Bestehende Kategorien.
	 * @return array Kategorien mit Recruiting Playbook.
	 */
	public function addCategory( array $categories ): array {
		$categories['recruiting_playbook'] = esc_attr__( 'Recruiting Playbook', 'recruiting-playbook' );
		return $categories;
	}

	/**
	 * Editor-Assets laden
	 *
	 * @return void
	 */
	public function enqueueEditorAssets(): void {
		wp_enqueue_style(
			'rp-avada-editor',
			RP_PLUGIN_URL . 'assets/css/avada-editor.css',
			[],
			RP_VERSION
		);
	}
}

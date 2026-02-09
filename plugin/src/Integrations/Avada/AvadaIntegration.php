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

		// Elements registrieren (in $all_fusion_builder_elements)
		add_action( 'fusion_builder_before_init', [ $this, 'registerElements' ], 11 );

		// WICHTIG: Elements nach dem Filtern wieder hinzufügen
		// Fusion Builder filtert basierend auf Benutzer-Einstellungen,
		// aber wir wollen unsere Elements IMMER verfügbar machen.
		add_filter( 'fusion_builder_all_elements', [ $this, 'ensureElementsAvailable' ], 20 );

		// Element-Kategorie hinzufügen
		add_filter( 'fusion_builder_element_categories', [ $this, 'addCategory' ] );

		// Editor-Assets laden (Backend + Live Builder).
		add_action( 'fusion_builder_admin_scripts_hook', [ $this, 'enqueueEditorAssets' ] );
		add_action( 'fusion_builder_enqueue_live_scripts', [ $this, 'enqueueEditorAssets' ] );
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
	 * Stellt sicher, dass RP-Elements nach dem Filtern verfügbar sind
	 *
	 * Fusion Builder filtert Elements basierend auf Benutzer-Einstellungen.
	 * Unsere Elements sollen aber immer verfügbar sein (solange Pro aktiv ist).
	 *
	 * @param array $elements Gefilterte Elements.
	 * @return array Elements mit RP-Elements.
	 */
	public function ensureElementsAvailable( array $elements ): array {
		global $all_fusion_builder_elements;

		// RP-Elements aus der globalen Liste holen und hinzufügen.
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
		// Frontend-CSS wird in Plugin::enqueueFrontendAssets() über ?builder / ?fb-edit geladen.
		wp_enqueue_style(
			'rp-avada-editor',
			RP_PLUGIN_URL . 'assets/css/avada-editor.css',
			[],
			RP_VERSION
		);

		// Preview-Templates im Footer laden (Backend + Live Builder).
		add_action( 'admin_footer', [ $this, 'outputPreviewTemplates' ], 99 );
		add_action( 'wp_footer', [ $this, 'outputPreviewTemplates' ], 99 );
	}

	/**
	 * Preview-Templates für den Fusion Builder ausgeben
	 *
	 * Lädt alle Underscore.js Preview-Templates in den Footer,
	 * damit der Backend- und Live-Builder sie für die Element-Vorschau nutzen kann.
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

<?php
/**
 * Gutenberg Block Loader
 *
 * Registriert alle Recruiting Playbook Blöcke für den Block-Editor.
 * Free-Feature: Verfügbar für alle Nutzer (Free & Pro).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Blocks;

use RecruitingPlaybook\Blocks\Patterns\PatternLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Block Loader Klasse
 */
class BlockLoader {

	/**
	 * Liste aller verfügbaren Blöcke
	 *
	 * @var array<string>
	 */
	private array $blocks = [
		'jobs',
		'job-search',
		'job-count',
		'featured-jobs',
		'latest-jobs',
		'job-categories',
		'application-form',
	];

	/**
	 * AI-Addon Blöcke (nur mit AI-Addon verfügbar)
	 *
	 * @var array<string>
	 */
	private array $ai_blocks = [
		'ai-job-finder',
		'ai-job-match',
	];

	/**
	 * Pattern Loader Instanz
	 *
	 * @var PatternLoader
	 */
	private PatternLoader $pattern_loader;

	/**
	 * Initialisierung
	 */
	public function register(): void {
		// Pro-Feature Check.
		if ( ! $this->canUseBlocks() ) {
			return;
		}

		add_action( 'init', [ $this, 'registerBlocks' ] );
		add_filter( 'block_categories_all', [ $this, 'registerCategory' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueueEditorAssets' ] );

		// Block-Patterns registrieren.
		$this->pattern_loader = new PatternLoader();
		$this->pattern_loader->register();
	}

	/**
	 * Prüfen ob Blöcke verwendet werden können
	 *
	 * Gutenberg Blocks sind ein Free-Feature und immer verfügbar.
	 * Nur Page Builder (Avada, Elementor, etc.) sind Pro-Features.
	 *
	 * @return bool Immer true - Blocks sind für alle verfügbar.
	 */
	private function canUseBlocks(): bool {
		return true;
	}

	/**
	 * Alle Blöcke registrieren
	 */
	public function registerBlocks(): void {
		// Standard-Blöcke registrieren.
		foreach ( $this->blocks as $block ) {
			$this->registerBlock( $block );
		}

		// AI-Blöcke nur wenn AI-Addon aktiv.
		if ( $this->hasAiAddon() ) {
			foreach ( $this->ai_blocks as $block ) {
				$this->registerBlock( $block );
			}
		}
	}

	/**
	 * Einzelnen Block registrieren
	 *
	 * @param string $block Block-Name (ohne Prefix).
	 */
	private function registerBlock( string $block ): void {
		$block_dir = RP_PLUGIN_DIR . "src/Blocks/Blocks/{$block}";

		// Block nur registrieren wenn block.json existiert.
		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		register_block_type( $block_dir );
	}

	/**
	 * Prüfen ob AI-Addon aktiv ist
	 *
	 * @return bool True wenn AI-Addon verfügbar.
	 */
	private function hasAiAddon(): bool {
		return function_exists( 'rp_has_cv_matching' ) && rp_has_cv_matching();
	}

	/**
	 * Block-Kategorie registrieren
	 *
	 * @param array                   $categories Vorhandene Kategorien.
	 * @param \WP_Block_Editor_Context $context    Block-Editor Kontext.
	 * @return array Erweiterte Kategorien.
	 */
	public function registerCategory( array $categories, $context ): array {
		return array_merge(
			[
				[
					'slug'  => 'recruiting-playbook',
					'title' => __( 'Recruiting Playbook', 'recruiting-playbook' ),
					'icon'  => 'businessperson',
				],
			],
			$categories
		);
	}

	/**
	 * Editor-Assets laden
	 */
	public function enqueueEditorAssets(): void {
		$asset_file = RP_PLUGIN_DIR . 'assets/dist/js/blocks.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		// Block-Editor JavaScript.
		wp_enqueue_script(
			'rp-blocks-editor',
			RP_PLUGIN_URL . 'assets/dist/js/blocks.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Lokalisierung für Blöcke.
		wp_set_script_translations(
			'rp-blocks-editor',
			'recruiting-playbook',
			RP_PLUGIN_DIR . 'languages'
		);

		// Block-Konfiguration.
		wp_localize_script(
			'rp-blocks-editor',
			'rpBlocksConfig',
			[
				'hasAiAddon'  => $this->hasAiAddon(),
				'restUrl'     => rest_url( 'recruiting/v1/' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'taxonomies'  => $this->getTaxonomiesData(),
				'pluginUrl'   => RP_PLUGIN_URL,
			]
		);

		// Editor-Styles.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/blocks-editor.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rp-blocks-editor',
				RP_PLUGIN_URL . 'assets/dist/css/blocks-editor.css',
				[],
				RP_VERSION . '-' . filemtime( $css_file )
			);
		}

		// Design & Branding CSS-Variablen auch im Editor laden.
		$this->enqueueDesignVariables();
	}

	/**
	 * Taxonomie-Daten für Blöcke vorbereiten
	 *
	 * @return array Taxonomie-Daten.
	 */
	private function getTaxonomiesData(): array {
		$taxonomies = [
			'job_category'    => [],
			'job_location'    => [],
			'employment_type' => [],
		];

		foreach ( array_keys( $taxonomies ) as $taxonomy ) {
			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				]
			);

			if ( ! is_wp_error( $terms ) ) {
				$taxonomies[ $taxonomy ] = array_map(
					function ( $term ) {
						return [
							'value' => $term->slug,
							'label' => $term->name,
							'count' => $term->count,
						];
					},
					$terms
				);
			}
		}

		return $taxonomies;
	}

	/**
	 * Design-Variablen im Editor laden
	 */
	private function enqueueDesignVariables(): void {
		// CssGeneratorService nutzen falls verfügbar.
		if ( class_exists( '\RecruitingPlaybook\Services\CssGeneratorService' ) ) {
			$css_generator = new \RecruitingPlaybook\Services\CssGeneratorService();
			$inline_css    = $css_generator->generate_css();

			if ( ! empty( $inline_css ) ) {
				wp_add_inline_style( 'rp-blocks-editor', $inline_css );
			}
		}
	}
}

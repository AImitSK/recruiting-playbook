<?php
/**
 * Gutenberg Block Loader
 *
 * Registers all Recruiting Playbook blocks for the block editor.
 * Free-Feature: Available for all users (Free & Pro).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Blocks;

use RecruitingPlaybook\Blocks\Patterns\PatternLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Block Loader Class
 */
class BlockLoader {

	/**
	 * List of all available blocks
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
	 * AI Blocks (only available with Pro)
	 *
	 * @var array<string>
	 */
	private array $ai_blocks = [
		'ai-job-finder',
		'ai-job-match',
	];

	/**
	 * Pattern Loader Instance
	 *
	 * @var PatternLoader
	 */
	private PatternLoader $pattern_loader;

	/**
	 * Initialization
	 */
	public function register(): void {
		// Pro-Feature Check.
		if ( ! $this->canUseBlocks() ) {
			return;
		}

		add_action( 'init', [ $this, 'registerBlocks' ] );
		add_filter( 'block_categories_all', [ $this, 'registerCategory' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueueEditorAssets' ] );

		// Register block patterns.
		$this->pattern_loader = new PatternLoader();
		$this->pattern_loader->register();
	}

	/**
	 * Check if blocks can be used
	 *
	 * Gutenberg Blocks are a Free-Feature and always available.
	 * Only Page Builders (Avada, Elementor, etc.) are Pro-Features.
	 *
	 * @return bool Always true - Blocks are available for all.
	 */
	private function canUseBlocks(): bool {
		return true;
	}

	/**
	 * Register all blocks
	 */
	public function registerBlocks(): void {
		// Register standard blocks.
		foreach ( $this->blocks as $block ) {
			$this->registerBlock( $block );
		}

		// AI blocks only if Pro is active.
		if ( $this->hasPro() ) {
			foreach ( $this->ai_blocks as $block ) {
				$this->registerBlock( $block );
			}
		}
	}

	/**
	 * Register a single block
	 *
	 * @param string $block Block name (without prefix).
	 */
	private function registerBlock( string $block ): void {
		$block_dir = RP_PLUGIN_DIR . "src/Blocks/Blocks/{$block}";

		// Only register block if block.json exists.
		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		register_block_type( $block_dir );
	}

	/**
	 * Check if Pro is active (includes AI features)
	 *
	 * @return bool True if Pro is available.
	 */
	private function hasPro(): bool {
		return function_exists( 'rp_is_pro' ) && rp_is_pro();
	}

	/**
	 * Register block category
	 *
	 * @param array                   $categories Existing categories.
	 * @param \WP_Block_Editor_Context $context    Block editor context.
	 * @return array Extended categories.
	 */
	public function registerCategory( array $categories, $context ): array {
		return array_merge(
			[
				[
					'slug'  => 'recruiting-playbook',
					'title' => __( 'Recruiting Playbook', 'recruiting-playbook' ),
				],
			],
			$categories
		);
	}

	/**
	 * Load editor assets
	 */
	public function enqueueEditorAssets(): void {
		$asset_file = RP_PLUGIN_DIR . 'assets/dist/js/blocks.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		// Block editor JavaScript.
		wp_enqueue_script(
			'rp-blocks-editor',
			RP_PLUGIN_URL . 'assets/dist/js/blocks.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Localization for blocks.
		wp_set_script_translations(
			'rp-blocks-editor',
			'recruiting-playbook',
			RP_PLUGIN_DIR . 'languages'
		);

		// Block configuration.
		wp_localize_script(
			'rp-blocks-editor',
			'rpBlocksConfig',
			[
				'isPro'       => $this->hasPro(),
				'restUrl'     => rest_url( 'recruiting/v1/' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'taxonomies'  => $this->getTaxonomiesData(),
				'pluginUrl'   => RP_PLUGIN_URL,
			]
		);

		// Editor styles.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/blocks-editor.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rp-blocks-editor',
				RP_PLUGIN_URL . 'assets/dist/css/blocks-editor.css',
				[],
				RP_VERSION . '-' . filemtime( $css_file )
			);
		}

		// Load design & branding CSS variables in the editor as well.
		$this->enqueueDesignVariables();
	}

	/**
	 * Prepare taxonomy data for blocks
	 *
	 * @return array Taxonomy data.
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
	 * Load design variables in the editor
	 */
	private function enqueueDesignVariables(): void {
		// Use CssGeneratorService if available.
		if ( class_exists( '\RecruitingPlaybook\Services\CssGeneratorService' ) ) {
			$css_generator = new \RecruitingPlaybook\Services\CssGeneratorService();
			$inline_css    = $css_generator->generate_css();

			if ( ! empty( $inline_css ) ) {
				wp_add_inline_style( 'rp-blocks-editor', $inline_css );
			}
		}
	}
}

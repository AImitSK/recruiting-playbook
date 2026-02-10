<?php
/**
 * Unit Tests für ElementorIntegration
 *
 * @package RecruitingPlaybook\Tests\Unit
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Integrations\Elementor;

use RecruitingPlaybook\Integrations\Elementor\ElementorIntegration;
use RecruitingPlaybook\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class ElementorIntegrationTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html_e' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
	}

	/**
	 * Test: register() registriert alle Hooks wenn Elementor aktiv ist.
	 */
	public function test_register_adds_hooks_when_elementor_active(): void {
		Functions\when( 'did_action' )->alias( function ( string $hook ) {
			return 'elementor/loaded' === $hook ? 1 : 0;
		} );

		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			define( 'ELEMENTOR_VERSION', '3.20.0' );
		}

		// rp_can nicht definiert → kein Pro-Check (immer erlaubt).
		$integration = new ElementorIntegration();
		$integration->register();

		self::assertNotFalse(
			has_action( 'elementor/elements/categories_registered', [ $integration, 'registerCategory' ] )
		);
		self::assertNotFalse(
			has_action( 'elementor/widgets/register', [ $integration, 'registerWidgets' ] )
		);
		self::assertNotFalse(
			has_action( 'elementor/editor/after_enqueue_styles', [ $integration, 'enqueueEditorAssets' ] )
		);
	}

	/**
	 * Test: register() macht nichts wenn Elementor nicht geladen ist.
	 */
	public function test_register_skips_when_elementor_not_loaded(): void {
		Functions\when( 'did_action' )->justReturn( 0 );

		$integration = new ElementorIntegration();
		$integration->register();

		self::assertFalse(
			has_action( 'elementor/elements/categories_registered', [ $integration, 'registerCategory' ] )
		);
	}

	/**
	 * Test: register() macht nichts wenn Pro-Feature nicht erlaubt.
	 */
	public function test_register_skips_when_pro_feature_disabled(): void {
		Functions\when( 'rp_can' )->justReturn( false );
		Functions\when( 'did_action' )->justReturn( 1 );

		$integration = new ElementorIntegration();
		$integration->register();

		self::assertFalse(
			has_action( 'elementor/widgets/register', [ $integration, 'registerWidgets' ] )
		);
	}

	/**
	 * Test: registerCategory() erstellt die Recruiting Playbook Kategorie.
	 */
	public function test_register_category(): void {
		$elements_manager = new \Elementor\Elements_Manager();
		$integration      = new ElementorIntegration();

		$integration->registerCategory( $elements_manager );

		$categories = $elements_manager->get_categories();
		self::assertArrayHasKey( 'recruiting-playbook', $categories );
		self::assertSame( 'Recruiting Playbook', $categories['recruiting-playbook']['title'] );
	}

	/**
	 * Test: registerWidgets() delegiert an WidgetLoader.
	 */
	public function test_register_widgets_delegates_to_loader(): void {
		Functions\when( 'get_terms' )->justReturn( [] );
		Functions\when( 'get_posts' )->justReturn( [] );

		$widgets_manager = new \Elementor\Widgets_Manager();
		$integration     = new ElementorIntegration();

		$integration->registerWidgets( $widgets_manager );

		$registered = $widgets_manager->get_registered();
		self::assertNotEmpty( $registered );
		self::assertArrayHasKey( 'rp-job-grid', $registered );
	}

	/**
	 * Test: enqueueEditorAssets() lädt das Editor-CSS.
	 */
	public function test_enqueue_editor_assets(): void {
		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with(
				'rp-elementor-editor',
				\Mockery::type( 'string' ),
				[],
				\Mockery::type( 'string' )
			);

		$integration = new ElementorIntegration();
		$integration->enqueueEditorAssets();
	}
}

<?php
/**
 * Unit Tests für WidgetLoader
 *
 * @package RecruitingPlaybook\Tests\Unit
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Integrations\Elementor;

use RecruitingPlaybook\Integrations\Elementor\WidgetLoader;
use RecruitingPlaybook\Tests\TestCase;
use Brain\Monkey\Functions;

class WidgetLoaderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html_e' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'get_terms' )->justReturn( [] );
		Functions\when( 'get_posts' )->justReturn( [] );
	}

	/**
	 * Test: Alle Core-Widgets (ohne AI) werden registriert.
	 */
	public function test_registers_all_core_widgets(): void {
		$widgets_manager = new \Elementor\Widgets_Manager();
		$loader          = new WidgetLoader( $widgets_manager );

		$loader->registerAll();

		$registered = $widgets_manager->get_registered();

		$expected_names = [
			'rp-job-grid',
			'rp-job-search',
			'rp-job-count',
			'rp-featured-jobs',
			'rp-latest-jobs',
			'rp-job-categories',
			'rp-application-form',
		];

		foreach ( $expected_names as $name ) {
			self::assertArrayHasKey( $name, $registered, "Widget '{$name}' sollte registriert sein." );
		}
	}

	/**
	 * Test: AI-Widgets werden NICHT registriert wenn rp_has_cv_matching fehlt.
	 */
	public function test_ai_widgets_skipped_without_ai_addon(): void {
		$widgets_manager = new \Elementor\Widgets_Manager();
		$loader          = new WidgetLoader( $widgets_manager );

		$loader->registerAll();

		$registered = $widgets_manager->get_registered();

		self::assertArrayNotHasKey( 'rp-ai-job-finder', $registered );
		self::assertArrayNotHasKey( 'rp-ai-job-match', $registered );
	}

	/**
	 * Test: AI-Widgets werden registriert wenn AI-Addon aktiv.
	 */
	public function test_ai_widgets_registered_with_ai_addon(): void {
		Functions\when( 'rp_has_cv_matching' )->justReturn( true );

		$widgets_manager = new \Elementor\Widgets_Manager();
		$loader          = new WidgetLoader( $widgets_manager );

		$loader->registerAll();

		$registered = $widgets_manager->get_registered();

		self::assertArrayHasKey( 'rp-ai-job-finder', $registered );
		self::assertArrayHasKey( 'rp-ai-job-match', $registered );
	}

	/**
	 * Test: AI-Widgets werden NICHT registriert wenn rp_has_cv_matching false.
	 */
	public function test_ai_widgets_skipped_when_cv_matching_disabled(): void {
		Functions\when( 'rp_has_cv_matching' )->justReturn( false );

		$widgets_manager = new \Elementor\Widgets_Manager();
		$loader          = new WidgetLoader( $widgets_manager );

		$loader->registerAll();

		$registered = $widgets_manager->get_registered();

		self::assertArrayNotHasKey( 'rp-ai-job-finder', $registered );
		self::assertArrayNotHasKey( 'rp-ai-job-match', $registered );
	}

	/**
	 * Test: Genau 7 Core-Widgets + 2 AI-Widgets = 9 total.
	 */
	public function test_total_widget_count_with_ai(): void {
		Functions\when( 'rp_has_cv_matching' )->justReturn( true );

		$widgets_manager = new \Elementor\Widgets_Manager();
		$loader          = new WidgetLoader( $widgets_manager );

		$loader->registerAll();

		self::assertCount( 9, $widgets_manager->get_registered() );
	}

	/**
	 * Test: Genau 7 Core-Widgets ohne AI-Addon.
	 */
	public function test_total_widget_count_without_ai(): void {
		// rp_has_cv_matching könnte durch vorherigen Test definiert sein.
		Functions\when( 'rp_has_cv_matching' )->justReturn( false );

		$widgets_manager = new \Elementor\Widgets_Manager();
		$loader          = new WidgetLoader( $widgets_manager );

		$loader->registerAll();

		self::assertCount( 7, $widgets_manager->get_registered() );
	}
}

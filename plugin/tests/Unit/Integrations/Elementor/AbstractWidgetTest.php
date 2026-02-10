<?php
/**
 * Unit Tests für AbstractWidget
 *
 * Testet die Basisklasse über eine konkrete Test-Implementierung.
 *
 * @package RecruitingPlaybook\Tests\Unit
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Integrations\Elementor;

use RecruitingPlaybook\Integrations\Elementor\Widgets\AbstractWidget;
use RecruitingPlaybook\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Konkrete Widget-Implementierung für Tests.
 */
class TestWidget extends AbstractWidget {

	public function get_name(): string {
		return 'rp-test-widget';
	}

	public function get_title(): string {
		return 'RP: Test Widget';
	}

	public function get_icon(): string {
		return 'eicon-test';
	}

	public function get_keywords(): array {
		return [ 'test' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_test';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'limit'    => 'limit',
			'category' => 'category',
			'title'    => 'title',
		];
	}

	protected function register_controls(): void {}

	/**
	 * Öffentlicher Zugriff auf build_shortcode() für Tests.
	 */
	public function public_build_shortcode(): string {
		return $this->build_shortcode();
	}

	/**
	 * Öffentlicher Zugriff auf render() für Tests.
	 */
	public function public_render(): void {
		$this->render();
	}

	/**
	 * Öffentlicher Zugriff auf getTaxonomyOptions() für Tests.
	 */
	public function public_get_taxonomy_options( string $taxonomy ): array {
		return $this->getTaxonomyOptions( $taxonomy );
	}

	/**
	 * Öffentlicher Zugriff auf getJobOptions() für Tests.
	 */
	public function public_get_job_options(): array {
		return $this->getJobOptions();
	}
}

/**
 * Widget ohne Mapping (leeres Mapping).
 */
class TestWidgetNoMapping extends AbstractWidget {

	public function get_name(): string {
		return 'rp-test-no-mapping';
	}

	protected function get_shortcode_name(): string {
		return 'rp_simple';
	}

	public function public_build_shortcode(): string {
		return $this->build_shortcode();
	}
}

class AbstractWidgetTest extends TestCase {

	private TestWidget $widget;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html_e' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'get_terms' )->justReturn( [] );
		Functions\when( 'get_posts' )->justReturn( [] );

		$this->widget = new TestWidget();
	}

	// ===================================================================
	// Kategorie & Style Dependencies
	// ===================================================================

	public function test_get_categories_returns_recruiting_playbook(): void {
		self::assertSame( [ 'recruiting-playbook' ], $this->widget->get_categories() );
	}

	public function test_get_style_depends_returns_rp_frontend(): void {
		self::assertSame( [ 'rp-frontend' ], $this->widget->get_style_depends() );
	}

	// ===================================================================
	// build_shortcode()
	// ===================================================================

	public function test_build_shortcode_without_settings(): void {
		$this->widget->set_settings( [] );

		$shortcode = $this->widget->public_build_shortcode();

		self::assertSame( '[rp_test]', $shortcode );
	}

	public function test_build_shortcode_with_settings(): void {
		$this->widget->set_settings( [
			'limit'    => '5',
			'category' => 'marketing',
			'title'    => 'Test Title',
		] );

		$shortcode = $this->widget->public_build_shortcode();

		self::assertStringContainsString( '[rp_test ', $shortcode );
		self::assertStringContainsString( 'limit="5"', $shortcode );
		self::assertStringContainsString( 'category="marketing"', $shortcode );
		self::assertStringContainsString( 'title="Test Title"', $shortcode );
		self::assertStringEndsWith( ']', $shortcode );
	}

	public function test_build_shortcode_ignores_empty_settings(): void {
		$this->widget->set_settings( [
			'limit'    => '5',
			'category' => '',
			'title'    => '',
		] );

		$shortcode = $this->widget->public_build_shortcode();

		self::assertStringContainsString( 'limit="5"', $shortcode );
		self::assertStringNotContainsString( 'category=', $shortcode );
		self::assertStringNotContainsString( 'title=', $shortcode );
	}

	public function test_build_shortcode_ignores_unmapped_settings(): void {
		$this->widget->set_settings( [
			'limit'          => '3',
			'unknown_option' => 'some_value',
		] );

		$shortcode = $this->widget->public_build_shortcode();

		self::assertStringContainsString( 'limit="3"', $shortcode );
		self::assertStringNotContainsString( 'unknown_option', $shortcode );
	}

	public function test_build_shortcode_no_mapping_returns_simple(): void {
		$widget = new TestWidgetNoMapping();
		$widget->set_settings( [ 'anything' => 'value' ] );

		$shortcode = $widget->public_build_shortcode();

		self::assertSame( '[rp_simple]', $shortcode );
	}

	// ===================================================================
	// render()
	// ===================================================================

	public function test_render_calls_do_shortcode(): void {
		$this->widget->set_settings( [ 'limit' => '3' ] );

		Functions\expect( 'do_shortcode' )
			->once()
			->with( \Mockery::on( function ( $arg ) {
				return str_contains( $arg, '[rp_test' ) && str_contains( $arg, 'limit="3"' );
			} ) )
			->andReturn( '<div>rendered</div>' );

		ob_start();
		$this->widget->public_render();
		$output = ob_get_clean();

		self::assertSame( '<div>rendered</div>', $output );
	}

	public function test_render_without_settings(): void {
		$this->widget->set_settings( [] );

		Functions\expect( 'do_shortcode' )
			->once()
			->with( '[rp_test]' )
			->andReturn( '<div>no params</div>' );

		ob_start();
		$this->widget->public_render();
		$output = ob_get_clean();

		self::assertSame( '<div>no params</div>', $output );
	}

	// ===================================================================
	// getTaxonomyOptions()
	// ===================================================================

	public function test_get_taxonomy_options_with_terms(): void {
		$term1 = (object) [ 'slug' => 'dev', 'name' => 'Entwicklung' ];
		$term2 = (object) [ 'slug' => 'hr', 'name' => 'Personal' ];

		Functions\when( 'get_terms' )->justReturn( [ $term1, $term2 ] );

		$widget  = new TestWidget();
		$options = $widget->public_get_taxonomy_options( 'job_category' );

		// Erster Eintrag: leerer Key = "Alle".
		self::assertArrayHasKey( '', $options );
		self::assertSame( '— Alle —', $options[''] );

		// Terms.
		self::assertArrayHasKey( 'dev', $options );
		self::assertSame( 'Entwicklung', $options['dev'] );
		self::assertArrayHasKey( 'hr', $options );
		self::assertSame( 'Personal', $options['hr'] );
	}

	public function test_get_taxonomy_options_with_wp_error(): void {
		Functions\when( 'get_terms' )->justReturn( new \WP_Error( 'invalid', 'Error' ) );

		$widget  = new TestWidget();
		$options = $widget->public_get_taxonomy_options( 'nonexistent' );

		self::assertCount( 1, $options );
		self::assertArrayHasKey( '', $options );
	}

	public function test_get_taxonomy_options_empty(): void {
		Functions\when( 'get_terms' )->justReturn( [] );

		$widget  = new TestWidget();
		$options = $widget->public_get_taxonomy_options( 'job_category' );

		self::assertCount( 1, $options );
	}

	// ===================================================================
	// getJobOptions()
	// ===================================================================

	public function test_get_job_options_with_jobs(): void {
		$job1 = (object) [ 'ID' => 10, 'post_title' => 'PHP Developer' ];
		$job2 = (object) [ 'ID' => 20, 'post_title' => 'Designer' ];

		Functions\when( 'get_posts' )->justReturn( [ $job1, $job2 ] );

		$widget  = new TestWidget();
		$options = $widget->public_get_job_options();

		self::assertArrayHasKey( '', $options );
		self::assertSame( '— Automatisch —', $options[''] );
		self::assertArrayHasKey( '10', $options );
		self::assertSame( 'PHP Developer', $options['10'] );
		self::assertArrayHasKey( '20', $options );
		self::assertSame( 'Designer', $options['20'] );
	}

	public function test_get_job_options_empty(): void {
		Functions\when( 'get_posts' )->justReturn( [] );

		$widget  = new TestWidget();
		$options = $widget->public_get_job_options();

		self::assertCount( 1, $options );
		self::assertArrayHasKey( '', $options );
	}

	// ===================================================================
	// content_template()
	// ===================================================================

	public function test_content_template_is_empty(): void {
		ob_start();
		// content_template ist protected, daher Reflection nutzen.
		$reflection = new \ReflectionMethod( $this->widget, 'content_template' );
		$reflection->setAccessible( true );
		$reflection->invoke( $this->widget );
		$output = ob_get_clean();

		self::assertEmpty( $output );
	}
}

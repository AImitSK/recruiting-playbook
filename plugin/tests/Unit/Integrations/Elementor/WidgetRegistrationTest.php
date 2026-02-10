<?php
/**
 * Unit Tests für alle konkreten Elementor Widgets
 *
 * Prüft Identität, Shortcode-Mapping und Konfiguration jedes Widgets.
 *
 * @package RecruitingPlaybook\Tests\Unit
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Integrations\Elementor;

use RecruitingPlaybook\Integrations\Elementor\Widgets\JobGrid;
use RecruitingPlaybook\Integrations\Elementor\Widgets\JobSearch;
use RecruitingPlaybook\Integrations\Elementor\Widgets\JobCount;
use RecruitingPlaybook\Integrations\Elementor\Widgets\FeaturedJobs;
use RecruitingPlaybook\Integrations\Elementor\Widgets\LatestJobs;
use RecruitingPlaybook\Integrations\Elementor\Widgets\JobCategories;
use RecruitingPlaybook\Integrations\Elementor\Widgets\ApplicationForm;
use RecruitingPlaybook\Integrations\Elementor\Widgets\AiJobFinder;
use RecruitingPlaybook\Integrations\Elementor\Widgets\AiJobMatch;
use RecruitingPlaybook\Tests\TestCase;
use Brain\Monkey\Functions;

class WidgetRegistrationTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html_e' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'get_terms' )->justReturn( [] );
		Functions\when( 'get_posts' )->justReturn( [] );
	}

	// ===================================================================
	// Widget-Definitionen: DataProvider mit erwarteten Werten
	// ===================================================================

	public static function widgetDefinitionsProvider(): array {
		return [
			'JobGrid' => [
				JobGrid::class,
				'rp-job-grid',
				'rp_jobs',
				[ 'limit', 'columns', 'category', 'location', 'type', 'featured', 'orderby', 'order' ],
			],
			'JobSearch' => [
				JobSearch::class,
				'rp-job-search',
				'rp_job_search',
				[ 'show_search', 'show_category', 'show_location', 'show_type', 'limit', 'columns' ],
			],
			'JobCount' => [
				JobCount::class,
				'rp-job-count',
				'rp_job_count',
				[ 'category', 'location', 'format', 'singular', 'zero' ],
			],
			'FeaturedJobs' => [
				FeaturedJobs::class,
				'rp-featured-jobs',
				'rp_featured_jobs',
				[ 'limit', 'columns', 'title', 'show_excerpt' ],
			],
			'LatestJobs' => [
				LatestJobs::class,
				'rp-latest-jobs',
				'rp_latest_jobs',
				[ 'limit', 'columns', 'title', 'category', 'show_excerpt' ],
			],
			'JobCategories' => [
				JobCategories::class,
				'rp-job-categories',
				'rp_job_categories',
				[ 'columns', 'show_count', 'hide_empty', 'orderby' ],
			],
			'ApplicationForm' => [
				ApplicationForm::class,
				'rp-application-form',
				'rp_application_form',
				[ 'job_id', 'title', 'show_job_title', 'show_progress' ],
			],
			'AiJobFinder' => [
				AiJobFinder::class,
				'rp-ai-job-finder',
				'rp_ai_job_finder',
				[ 'title', 'subtitle', 'limit' ],
			],
			'AiJobMatch' => [
				AiJobMatch::class,
				'rp-ai-job-match',
				'rp_ai_job_match',
				[ 'job_id', 'title', 'style' ],
			],
		];
	}

	/**
	 * Test: Jedes Widget hat den richtigen internen Namen.
	 *
	 * @dataProvider widgetDefinitionsProvider
	 */
	public function test_widget_name( string $class, string $expected_name ): void {
		$widget = new $class();

		self::assertSame( $expected_name, $widget->get_name() );
	}

	/**
	 * Test: Jedes Widget hat einen nicht-leeren Titel.
	 *
	 * @dataProvider widgetDefinitionsProvider
	 */
	public function test_widget_has_title( string $class ): void {
		$widget = new $class();

		self::assertNotEmpty( $widget->get_title() );
	}

	/**
	 * Test: Jedes Widget hat ein Icon.
	 *
	 * @dataProvider widgetDefinitionsProvider
	 */
	public function test_widget_has_icon( string $class ): void {
		$widget = new $class();

		self::assertNotEmpty( $widget->get_icon() );
		self::assertStringStartsWith( 'eicon-', $widget->get_icon() );
	}

	/**
	 * Test: Jedes Widget hat Keywords.
	 *
	 * @dataProvider widgetDefinitionsProvider
	 */
	public function test_widget_has_keywords( string $class ): void {
		$widget = new $class();

		self::assertNotEmpty( $widget->get_keywords() );
	}

	/**
	 * Test: Jedes Widget ist in der Kategorie 'recruiting-playbook'.
	 *
	 * @dataProvider widgetDefinitionsProvider
	 */
	public function test_widget_category( string $class ): void {
		$widget = new $class();

		self::assertSame( [ 'recruiting-playbook' ], $widget->get_categories() );
	}

	/**
	 * Test: Jedes Widget deklariert rp-frontend als Style-Dependency.
	 *
	 * @dataProvider widgetDefinitionsProvider
	 */
	public function test_widget_style_depends( string $class ): void {
		$widget = new $class();

		self::assertContains( 'rp-frontend', $widget->get_style_depends() );
	}

	/**
	 * Test: Shortcode-Mapping enthält die erwarteten Keys.
	 *
	 * @dataProvider widgetDefinitionsProvider
	 */
	public function test_widget_shortcode_mapping( string $class, string $name, string $shortcode, array $expected_keys ): void {
		$widget = new $class();

		// get_shortcode_mapping ist protected → Reflection.
		$reflection = new \ReflectionMethod( $widget, 'get_shortcode_mapping' );
		$reflection->setAccessible( true );
		$mapping = $reflection->invoke( $widget );

		foreach ( $expected_keys as $key ) {
			self::assertArrayHasKey( $key, $mapping, "Mapping-Key '{$key}' fehlt in {$class}." );
		}

		self::assertCount(
			count( $expected_keys ),
			$mapping,
			"Mapping-Anzahl stimmt nicht in {$class}."
		);
	}

	/**
	 * Test: Shortcode-Name ist korrekt.
	 *
	 * @dataProvider widgetDefinitionsProvider
	 */
	public function test_widget_shortcode_name( string $class, string $name, string $expected_shortcode ): void {
		$widget = new $class();

		$reflection = new \ReflectionMethod( $widget, 'get_shortcode_name' );
		$reflection->setAccessible( true );

		self::assertSame( $expected_shortcode, $reflection->invoke( $widget ) );
	}

	// ===================================================================
	// Widget-spezifische Tests
	// ===================================================================

	/**
	 * Test: JobGrid baut korrekten Shortcode.
	 */
	public function test_job_grid_builds_shortcode(): void {
		$widget = new JobGrid();
		$widget->set_settings( [
			'limit'    => '5',
			'columns'  => '3',
			'category' => 'marketing',
		] );

		$reflection = new \ReflectionMethod( $widget, 'build_shortcode' );
		$reflection->setAccessible( true );
		$shortcode = $reflection->invoke( $widget );

		self::assertStringContainsString( '[rp_jobs ', $shortcode );
		self::assertStringContainsString( 'limit="5"', $shortcode );
		self::assertStringContainsString( 'columns="3"', $shortcode );
		self::assertStringContainsString( 'category="marketing"', $shortcode );
	}

	/**
	 * Test: JobCount baut Shortcode mit Format-Attributen.
	 */
	public function test_job_count_builds_shortcode_with_format(): void {
		$widget = new JobCount();
		$widget->set_settings( [
			'format'   => '{count} Jobs',
			'singular' => '{count} Job',
			'zero'     => 'Keine Jobs',
		] );

		$reflection = new \ReflectionMethod( $widget, 'build_shortcode' );
		$reflection->setAccessible( true );
		$shortcode = $reflection->invoke( $widget );

		self::assertStringContainsString( 'format="{count} Jobs"', $shortcode );
		self::assertStringContainsString( 'singular="{count} Job"', $shortcode );
		self::assertStringContainsString( 'zero="Keine Jobs"', $shortcode );
	}

	/**
	 * Test: ApplicationForm baut Shortcode mit job_id.
	 */
	public function test_application_form_builds_shortcode_with_job_id(): void {
		$widget = new ApplicationForm();
		$widget->set_settings( [
			'job_id'         => '42',
			'title'          => 'Bewerben',
			'show_job_title' => 'true',
			'show_progress'  => '',
		] );

		$reflection = new \ReflectionMethod( $widget, 'build_shortcode' );
		$reflection->setAccessible( true );
		$shortcode = $reflection->invoke( $widget );

		self::assertStringContainsString( 'job_id="42"', $shortcode );
		self::assertStringContainsString( 'title="Bewerben"', $shortcode );
		self::assertStringContainsString( 'show_job_title="true"', $shortcode );
		self::assertStringNotContainsString( 'show_progress=', $shortcode );
	}

	/**
	 * Test: AiJobMatch baut korrekten Shortcode.
	 */
	public function test_ai_job_match_builds_shortcode(): void {
		$widget = new AiJobMatch();
		$widget->set_settings( [
			'job_id' => '7',
			'title'  => 'Passe ich?',
			'style'  => 'outline',
		] );

		$reflection = new \ReflectionMethod( $widget, 'build_shortcode' );
		$reflection->setAccessible( true );
		$shortcode = $reflection->invoke( $widget );

		self::assertStringContainsString( '[rp_ai_job_match ', $shortcode );
		self::assertStringContainsString( 'job_id="7"', $shortcode );
		self::assertStringContainsString( 'style="outline"', $shortcode );
	}

	/**
	 * Test: Widget-Namen sind alle eindeutig.
	 */
	public function test_all_widget_names_are_unique(): void {
		$classes = [
			JobGrid::class, JobSearch::class, JobCount::class,
			FeaturedJobs::class, LatestJobs::class, JobCategories::class,
			ApplicationForm::class, AiJobFinder::class, AiJobMatch::class,
		];

		$names = [];
		foreach ( $classes as $class ) {
			$widget  = new $class();
			$names[] = $widget->get_name();
		}

		self::assertCount( count( $names ), array_unique( $names ), 'Widget-Namen müssen eindeutig sein.' );
	}

	/**
	 * Test: Alle Widget-Namen beginnen mit 'rp-'.
	 */
	public function test_all_widget_names_start_with_rp(): void {
		$classes = [
			JobGrid::class, JobSearch::class, JobCount::class,
			FeaturedJobs::class, LatestJobs::class, JobCategories::class,
			ApplicationForm::class, AiJobFinder::class, AiJobMatch::class,
		];

		foreach ( $classes as $class ) {
			$widget = new $class();
			self::assertStringStartsWith( 'rp-', $widget->get_name(), "{$class}: Name muss mit 'rp-' beginnen." );
		}
	}
}

<?php
/**
 * ConversionService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\ConversionService;
use RecruitingPlaybook\Repositories\StatsRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den ConversionService
 */
class ConversionServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var ConversionService
	 */
	private ConversionService $service;

	/**
	 * Mock Repository
	 *
	 * @var StatsRepository|Mockery\MockInterface
	 */
	private $repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = Mockery::mock( StatsRepository::class );
		$this->service = new ConversionService( $this->repository );

		// WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
	}

	/**
	 * Test: calculate liefert korrekte Struktur
	 */
	public function test_calculate_returns_correct_structure(): void {
		$this->setupDefaultMocks();

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		$this->assertArrayHasKey( 'overall', $result );
		$this->assertArrayHasKey( 'funnel', $result );
		$this->assertArrayHasKey( 'by_source', $result );
		$this->assertArrayHasKey( 'trend', $result );
		$this->assertArrayHasKey( 'top_converting_jobs', $result );
	}

	/**
	 * Test: Overall Conversion-Rate wird korrekt berechnet
	 */
	public function test_overall_conversion_rate_calculation(): void {
		$this->repository
			->shouldReceive( 'countJobViews' )
			->andReturn( 1000 );

		$this->repository
			->shouldReceive( 'countApplications' )
			->andReturn( 50 );

		$this->repository
			->shouldReceive( 'countEvents' )
			->andReturn( 500 );

		$this->repository
			->shouldReceive( 'getApplicationsBySource' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getApplicationsTimeline' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		// 50 Bewerbungen / 1000 Views * 100 = 5%.
		$this->assertEquals( 5.0, $result['overall']['conversion_rate'] );
		$this->assertEquals( 1000, $result['overall']['views'] );
		$this->assertEquals( 50, $result['overall']['applications'] );
	}

	/**
	 * Test: Division durch Null wird abgefangen
	 */
	public function test_division_by_zero_handling(): void {
		$this->repository
			->shouldReceive( 'countJobViews' )
			->andReturn( 0 );

		$this->repository
			->shouldReceive( 'countApplications' )
			->andReturn( 0 );

		$this->repository
			->shouldReceive( 'countEvents' )
			->andReturn( 0 );

		$this->repository
			->shouldReceive( 'getApplicationsBySource' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getApplicationsTimeline' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		$this->assertEquals( 0, $result['overall']['conversion_rate'] );
	}

	/**
	 * Test: Funnel-Daten werden korrekt berechnet
	 */
	public function test_funnel_calculation(): void {
		$this->repository
			->shouldReceive( 'countEvents' )
			->with( 'job_list_viewed', Mockery::any() )
			->andReturn( 1000 );

		$this->repository
			->shouldReceive( 'countJobViews' )
			->andReturn( 500 );

		$this->repository
			->shouldReceive( 'countEvents' )
			->with( 'application_form_started', Mockery::any(), Mockery::any() )
			->andReturn( 100 );

		$this->repository
			->shouldReceive( 'countApplications' )
			->andReturn( 50 );

		$this->repository
			->shouldReceive( 'getApplicationsBySource' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getApplicationsTimeline' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		$this->assertEquals( 1000, $result['funnel']['job_list_views'] );
		$this->assertEquals( 500, $result['funnel']['job_detail_views'] );
		$this->assertEquals( 100, $result['funnel']['form_starts'] );
		$this->assertEquals( 50, $result['funnel']['form_completions'] );
	}

	/**
	 * Test: Conversion by Source wird sortiert nach Rate
	 */
	public function test_conversion_by_source_sorted(): void {
		$this->repository
			->shouldReceive( 'countJobViews' )
			->andReturn( 100 );

		$this->repository
			->shouldReceive( 'countApplications' )
			->andReturn( 20 );

		$this->repository
			->shouldReceive( 'countEvents' )
			->andReturn( 50 );

		$this->repository
			->shouldReceive( 'getApplicationsBySource' )
			->andReturn( [
				'website' => 10,
				'linkedin' => 5,
				'indeed' => 5,
			] );

		$this->repository
			->shouldReceive( 'getApplicationsTimeline' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		$this->assertIsArray( $result['by_source'] );
		$this->assertCount( 3, $result['by_source'] );

		// Erstes Element sollte höchste Conversion-Rate haben.
		$first = $result['by_source'][0];
		$this->assertArrayHasKey( 'source', $first );
		$this->assertArrayHasKey( 'conversion_rate', $first );
	}

	/**
	 * Test: getComparison liefert Vorperioden-Vergleich
	 */
	public function test_get_comparison(): void {
		$this->setupDefaultMocks();

		$result = $this->service->getComparison( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		$this->assertArrayHasKey( 'previous_period', $result );
		$this->assertArrayHasKey( 'conversion_rate', $result['previous_period'] );
		$this->assertArrayHasKey( 'change_percent', $result['previous_period'] );
	}

	/**
	 * Test: Top-konvertierende Jobs werden gefiltert (min. 10 Views)
	 */
	public function test_top_converting_jobs_filtered_by_min_views(): void {
		$this->repository
			->shouldReceive( 'countJobViews' )
			->andReturnUsing( function( $range, $job_id ) {
				return $job_id === 1 ? 100 : 5; // Job 1 hat genug Views, Job 2 nicht.
			} );

		$this->repository
			->shouldReceive( 'countApplications' )
			->andReturn( 10 );

		$this->repository
			->shouldReceive( 'countEvents' )
			->andReturn( 50 );

		$this->repository
			->shouldReceive( 'getApplicationsBySource' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getApplicationsTimeline' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->andReturn( [
				[ 'id' => 1, 'title' => 'Job A', 'applications' => 20 ],
				[ 'id' => 2, 'title' => 'Job B', 'applications' => 5 ],
			] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		// Nur Job 1 sollte in top_converting_jobs sein (>10 Views).
		$this->assertCount( 1, $result['top_converting_jobs'] );
		$this->assertEquals( 1, $result['top_converting_jobs'][0]['job_id'] );
	}

	/**
	 * Helper: Standard-Mocks einrichten
	 */
	private function setupDefaultMocks(): void {
		$this->repository
			->shouldReceive( 'countJobViews' )
			->andReturn( 100 );

		$this->repository
			->shouldReceive( 'countApplications' )
			->andReturn( 10 );

		$this->repository
			->shouldReceive( 'countEvents' )
			->andReturn( 50 );

		$this->repository
			->shouldReceive( 'getApplicationsBySource' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getApplicationsTimeline' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->andReturn( [] );
	}
}

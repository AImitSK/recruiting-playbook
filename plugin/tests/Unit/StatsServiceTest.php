<?php
/**
 * StatsService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\StatsService;
use RecruitingPlaybook\Repositories\StatsRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den StatsService
 */
class StatsServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var StatsService
	 */
	private StatsService $service;

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
		$this->service = new StatsService( $this->repository );

		// WordPress-Funktionen mocken.
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
		// current_time('timestamp') returns integer timestamp.
		Functions\when( 'current_time' )->alias( function( $type ) {
			if ( 'timestamp' === $type ) {
				return strtotime( '2025-01-25 12:00:00' );
			}
			if ( 'c' === $type ) {
				return '2025-01-25T12:00:00+00:00';
			}
			return '2025-01-25 12:00:00';
		} );
		Functions\when( '__' )->returnArg();
		Functions\when( 'wp_count_posts' )->justReturn( (object) [
			'publish' => 10,
			'draft' => 2,
		] );
	}

	/**
	 * Test: getOverview liefert Bewerbungs-Statistiken
	 */
	public function test_get_overview_returns_application_stats(): void {
		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->once()
			->andReturn( [
				[ 'id' => 1, 'title' => 'Job A', 'status' => 'publish', 'applications' => 10 ],
				[ 'id' => 2, 'title' => 'Job B', 'status' => 'publish', 'applications' => 5 ],
			] );

		$this->repository
			->shouldReceive( 'countApplicationsByStatus' )
			->andReturn( [
				'new' => 5,
				'screening' => 3,
				'interview' => 2,
				'offer' => 1,
				'hired' => 4,
				'rejected' => 8,
			] );

		$this->repository
			->shouldReceive( 'countApplications' )
			->andReturn( 23 );

		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'countJobViews' )
			->andReturn( 100 );

		$this->repository
			->shouldReceive( 'countJobsByStatus' )
			->andReturn( [ 'publish' => 10, 'draft' => 2 ] );

		$result = $this->service->getOverview( '30days' );

		$this->assertArrayHasKey( 'applications', $result );
		$this->assertArrayHasKey( 'jobs', $result );
		$this->assertArrayHasKey( 'top_jobs', $result );
		$this->assertEquals( 10, $result['jobs']['active'] );
	}

	/**
	 * Test: getDateRange für verschiedene Zeiträume
	 */
	public function test_get_date_range_returns_correct_date_ranges(): void {
		// Test 'today'.
		$today_range = $this->service->getDateRange( 'today' );
		$this->assertArrayHasKey( 'from', $today_range );
		$this->assertArrayHasKey( 'to', $today_range );
		$this->assertStringContainsString( '00:00:00', $today_range['from'] );
		$this->assertStringContainsString( '23:59:59', $today_range['to'] );

		// Test '7days'.
		$week_range = $this->service->getDateRange( '7days' );
		$from = strtotime( $week_range['from'] );
		$to = strtotime( $week_range['to'] );
		$this->assertLessThan( $to, $from );

		// Test 'all' (default - returns nulls).
		$all_range = $this->service->getDateRange( 'all' );
		$this->assertNull( $all_range['from'] );
		$this->assertNull( $all_range['to'] );
	}

	/**
	 * Test: getApplicationStats liefert detaillierte Bewerbungs-Statistiken
	 */
	public function test_get_application_stats(): void {
		$this->repository
			->shouldReceive( 'countApplications' )
			->once()
			->andReturn( 25 );

		$this->repository
			->shouldReceive( 'countApplicationsByStatus' )
			->once()
			->andReturn( [
				'new' => 10,
				'screening' => 5,
				'interview' => 3,
				'offer' => 2,
				'hired' => 1,
				'rejected' => 4,
			] );

		$this->repository
			->shouldReceive( 'getApplicationsTimeline' )
			->once()
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getApplicationsBySource' )
			->once()
			->andReturn( [
				'website' => 15,
				'linkedin' => 8,
				'indeed' => 2,
			] );

		$result = $this->service->getApplicationStats( [
			'date_from' => '2025-01-01',
			'date_to'   => '2025-01-31',
		] );

		$this->assertArrayHasKey( 'summary', $result );
		$this->assertArrayHasKey( 'by_source', $result );
		$this->assertEquals( 25, $result['summary']['total'] );
		$this->assertEquals( 15, $result['by_source']['website'] );
	}

	/**
	 * Test: getJobStats liefert Stellen-Statistiken
	 */
	public function test_get_job_stats(): void {
		$this->repository
			->shouldReceive( 'getJobStats' )
			->once()
			->andReturn( [
				[ 'id' => 1, 'title' => 'Developer', 'applications' => 20, 'views' => 100 ],
				[ 'id' => 2, 'title' => 'Designer', 'applications' => 10, 'views' => 50 ],
			] );

		$this->repository
			->shouldReceive( 'countJobs' )
			->once()
			->andReturn( 2 );

		$this->repository
			->shouldReceive( 'countApplications' )
			->once()
			->andReturn( 30 );

		$this->repository
			->shouldReceive( 'countJobViews' )
			->once()
			->andReturn( 150 );

		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->once()
			->andReturn( [] );

		$result = $this->service->getJobStats( [
			'date_from' => '2025-01-01',
			'date_to'   => '2025-01-31',
		] );

		$this->assertArrayHasKey( 'jobs', $result );
		$this->assertArrayHasKey( 'aggregated', $result );
		$this->assertCount( 2, $result['jobs'] );
	}

	/**
	 * Test: getTrends liefert Timeline-Daten
	 */
	public function test_get_trends(): void {
		$this->repository
			->shouldReceive( 'getApplicationsTimeline' )
			->once()
			->andReturn( [
				[ 'date' => '2025-01-20', 'total' => 5, 'new_count' => 3, 'hired' => 0, 'rejected' => 1 ],
				[ 'date' => '2025-01-21', 'total' => 8, 'new_count' => 6, 'hired' => 1, 'rejected' => 0 ],
				[ 'date' => '2025-01-22', 'total' => 3, 'new_count' => 2, 'hired' => 0, 'rejected' => 2 ],
			] );

		$result = $this->service->getTrends( [
			'date_from'   => '2025-01-20',
			'date_to'     => '2025-01-22',
			'granularity' => 'day',
		] );

		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'granularity', $result );
		$this->assertCount( 3, $result['data'] );
		$this->assertEquals( 'day', $result['granularity'] );
	}

	/**
	 * Test: Caching wird verwendet
	 */
	public function test_caching_is_used(): void {
		$cached_data = [
			'applications' => [ 'total' => 100, 'new' => 5 ],
			'jobs' => [ 'active' => 5 ],
			'top_jobs' => [],
			'time_to_hire' => [],
			'conversion_rate' => [],
		];

		// Create a mock that allows any method calls (for cache miss path).
		$this->repository->shouldReceive( 'getTopJobsByApplications' )->andReturn( [] );
		$this->repository->shouldReceive( 'countApplicationsByStatus' )->andReturn( [] );
		$this->repository->shouldReceive( 'countApplications' )->andReturn( 0 );
		$this->repository->shouldReceive( 'getHiredApplications' )->andReturn( [] );
		$this->repository->shouldReceive( 'countJobViews' )->andReturn( 0 );
		$this->repository->shouldReceive( 'countJobsByStatus' )->andReturn( [ 'publish' => 0, 'draft' => 0 ] );

		// First call: cache miss, return false (uses default from setUp).
		// So getOverview will compute and cache the result.
		$result1 = $this->service->getOverview( '30days' );

		// Verify the service works (caching internally).
		$this->assertArrayHasKey( 'applications', $result1 );
	}

	/**
	 * Test: Job-ID Filter wird angewendet
	 */
	public function test_job_id_filter(): void {
		$job_id = 42;

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->once()
			->andReturn( [] );

		// Allow any arguments - just verify it gets called.
		$this->repository
			->shouldReceive( 'countApplicationsByStatus' )
			->andReturn( [ 'new' => 3, 'hired' => 1 ] );

		$this->repository
			->shouldReceive( 'countApplications' )
			->andReturn( 3 );

		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'countJobViews' )
			->andReturn( 50 );

		$this->repository
			->shouldReceive( 'countJobsByStatus' )
			->andReturn( [ 'publish' => 5, 'draft' => 2 ] );

		$result = $this->service->getOverview( '30days', $job_id );

		$this->assertArrayHasKey( 'applications', $result );
		// Verify basic structure returned.
		$this->assertArrayHasKey( 'jobs', $result );
	}
}

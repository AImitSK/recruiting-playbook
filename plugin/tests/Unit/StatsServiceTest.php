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
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
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
			->shouldReceive( 'countApplicationsByStatus' )
			->once()
			->andReturn( [
				'new' => 5,
				'screening' => 3,
				'interview' => 2,
				'offer' => 1,
				'hired' => 4,
				'rejected' => 8,
			] );

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->once()
			->andReturn( [
				[ 'id' => 1, 'title' => 'Job A', 'applications' => 10 ],
				[ 'id' => 2, 'title' => 'Job B', 'applications' => 5 ],
			] );

		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->once()
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'countJobViews' )
			->once()
			->andReturn( 100 );

		$this->repository
			->shouldReceive( 'countApplications' )
			->once()
			->andReturn( 23 );

		$result = $this->service->getOverview( '30days' );

		$this->assertArrayHasKey( 'applications', $result );
		$this->assertArrayHasKey( 'jobs', $result );
		$this->assertArrayHasKey( 'top_jobs', $result );
		$this->assertEquals( 5, $result['applications']['new'] );
		$this->assertEquals( 4, $result['applications']['hired'] );
		$this->assertEquals( 10, $result['jobs']['active'] );
	}

	/**
	 * Test: Period-Parsing für verschiedene Zeiträume
	 */
	public function test_parse_period_returns_correct_date_ranges(): void {
		$reflection = new \ReflectionMethod( $this->service, 'parsePeriod' );
		$reflection->setAccessible( true );

		// Test 'today'.
		$today_range = $reflection->invoke( $this->service, 'today' );
		$this->assertArrayHasKey( 'from', $today_range );
		$this->assertArrayHasKey( 'to', $today_range );

		// Test '7days'.
		$week_range = $reflection->invoke( $this->service, '7days' );
		$from = strtotime( $week_range['from'] );
		$to = strtotime( $week_range['to'] );
		$this->assertLessThan( $to, $from );

		// Test 'all'.
		$all_range = $reflection->invoke( $this->service, 'all' );
		$this->assertStringContainsString( '1970', $all_range['from'] );
	}

	/**
	 * Test: getApplicationStats liefert detaillierte Bewerbungs-Statistiken
	 */
	public function test_get_application_stats(): void {
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
			->shouldReceive( 'getApplicationsBySource' )
			->once()
			->andReturn( [
				'website' => 15,
				'linkedin' => 8,
				'indeed' => 2,
			] );

		$result = $this->service->getApplicationStats( '30days' );

		$this->assertArrayHasKey( 'by_status', $result );
		$this->assertArrayHasKey( 'by_source', $result );
		$this->assertEquals( 10, $result['by_status']['new'] );
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

		$result = $this->service->getJobStats( '30days' );

		$this->assertArrayHasKey( 'jobs', $result );
		$this->assertArrayHasKey( 'summary', $result );
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
				[ 'date' => '2025-01-20', 'total' => 5, 'new' => 3 ],
				[ 'date' => '2025-01-21', 'total' => 8, 'new' => 6 ],
				[ 'date' => '2025-01-22', 'total' => 3, 'new' => 2 ],
			] );

		$result = $this->service->getTrends( '7days', 'day' );

		$this->assertArrayHasKey( 'timeline', $result );
		$this->assertArrayHasKey( 'granularity', $result );
		$this->assertCount( 3, $result['timeline'] );
		$this->assertEquals( 'day', $result['granularity'] );
	}

	/**
	 * Test: Caching wird verwendet
	 */
	public function test_caching_is_used(): void {
		$cached_data = [
			'applications' => [ 'total' => 100 ],
			'jobs' => [ 'active' => 5 ],
		];

		Functions\expect( 'wp_cache_get' )
			->once()
			->andReturn( $cached_data );

		$result = $this->service->getOverview( '30days' );

		$this->assertEquals( 100, $result['applications']['total'] );
	}

	/**
	 * Test: Job-ID Filter wird angewendet
	 */
	public function test_job_id_filter(): void {
		$job_id = 42;

		$this->repository
			->shouldReceive( 'countApplicationsByStatus' )
			->once()
			->with( Mockery::any(), $job_id )
			->andReturn( [ 'new' => 3 ] );

		$this->repository
			->shouldReceive( 'getTopJobsByApplications' )
			->once()
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->once()
			->andReturn( [] );

		$this->repository
			->shouldReceive( 'countJobViews' )
			->once()
			->andReturn( 50 );

		$this->repository
			->shouldReceive( 'countApplications' )
			->once()
			->andReturn( 3 );

		$result = $this->service->getOverview( '30days', $job_id );

		$this->assertEquals( 3, $result['applications']['new'] );
	}
}

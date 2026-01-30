<?php
/**
 * TimeToHireService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\TimeToHireService;
use RecruitingPlaybook\Repositories\StatsRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den TimeToHireService
 */
class TimeToHireServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var TimeToHireService
	 */
	private TimeToHireService $service;

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
		$this->service = new TimeToHireService( $this->repository );

		// WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( 'get_post' )->justReturn( (object) [
			'ID' => 1,
			'post_title' => 'Test Job',
			'post_status' => 'publish',
		] );
	}

	/**
	 * Test: calculate liefert korrekte Struktur
	 */
	public function test_calculate_returns_correct_structure(): void {
		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->once()
			->andReturn( [
				[
					'id' => 1,
					'created_at' => '2025-01-01 10:00:00',
					'hired_at' => '2025-01-15 10:00:00',
					'days_to_hire' => 14,
					'job_id' => 1,
					'job_title' => 'Developer',
				],
				[
					'id' => 2,
					'created_at' => '2025-01-05 10:00:00',
					'hired_at' => '2025-01-20 10:00:00',
					'days_to_hire' => 15,
					'job_id' => 1,
					'job_title' => 'Developer',
				],
			] );

		// Mock for stage transitions (called for each hired application).
		$this->repository
			->shouldReceive( 'getStatusTransitions' )
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		$this->assertArrayHasKey( 'overall', $result );
		$this->assertArrayHasKey( 'by_stage', $result );
		$this->assertArrayHasKey( 'by_job', $result );
		$this->assertArrayHasKey( 'trend', $result );
	}

	/**
	 * Test: Durchschnittliche Time-to-Hire wird korrekt berechnet
	 */
	public function test_average_time_to_hire_calculation(): void {
		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->once()
			->andReturn( [
				[
					'id' => 1,
					'created_at' => '2025-01-01 00:00:00',
					'hired_at' => '2025-01-11 00:00:00',
					'days_to_hire' => 10, // Pre-calculated by repository.
					'job_id' => 1,
					'job_title' => 'Job A',
				],
				[
					'id' => 2,
					'created_at' => '2025-01-01 00:00:00',
					'hired_at' => '2025-01-21 00:00:00',
					'days_to_hire' => 20, // Pre-calculated by repository.
					'job_id' => 1,
					'job_title' => 'Job A',
				],
			] );

		$this->repository
			->shouldReceive( 'getStatusTransitions' )
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		// Durchschnitt: (10 + 20) / 2 = 15 Tage.
		$this->assertEquals( 15, $result['overall']['average_days'] );
		$this->assertEquals( 10, $result['overall']['min_days'] );
		$this->assertEquals( 20, $result['overall']['max_days'] );
		$this->assertEquals( 2, $result['overall']['total_hires'] );
	}

	/**
	 * Test: Leere Ergebnisse werden korrekt behandelt
	 */
	public function test_empty_results(): void {
		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->once()
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		$this->assertEquals( 0, $result['overall']['average_days'] );
		$this->assertEquals( 0, $result['overall']['hired_count'] );
		$this->assertEmpty( $result['by_job'] );
	}

	/**
	 * Test: Gruppierung nach Job funktioniert
	 */
	public function test_grouping_by_job(): void {
		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->once()
			->andReturn( [
				[
					'id' => 1,
					'created_at' => '2025-01-01 00:00:00',
					'hired_at' => '2025-01-11 00:00:00',
					'days_to_hire' => 10,
					'job_id' => 1,
					'job_title' => 'Developer',
				],
				[
					'id' => 2,
					'created_at' => '2025-01-01 00:00:00',
					'hired_at' => '2025-01-06 00:00:00',
					'days_to_hire' => 5,
					'job_id' => 2,
					'job_title' => 'Designer',
				],
				[
					'id' => 3,
					'created_at' => '2025-01-01 00:00:00',
					'hired_at' => '2025-01-16 00:00:00',
					'days_to_hire' => 15,
					'job_id' => 1,
					'job_title' => 'Developer',
				],
			] );

		$this->repository
			->shouldReceive( 'getStatusTransitions' )
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ] );

		$this->assertCount( 2, $result['by_job'] );

		// Developer: (10 + 15) / 2 = 12.5 -> rounded to 13.
		$developer_stats = $this->findJobStats( $result['by_job'], 1 );
		$this->assertNotNull( $developer_stats );
		$this->assertEquals( 13, $developer_stats['average_days'] ); // Rounded.
		$this->assertEquals( 2, $developer_stats['hires'] );

		// Designer: 5 Tage.
		$designer_stats = $this->findJobStats( $result['by_job'], 2 );
		$this->assertNotNull( $designer_stats );
		$this->assertEquals( 5, $designer_stats['average_days'] );
		$this->assertEquals( 1, $designer_stats['hires'] );
	}

	/**
	 * Test: Job-ID Filter wird angewendet
	 */
	public function test_job_id_filter(): void {
		$job_id = 42;

		$this->repository
			->shouldReceive( 'getHiredApplications' )
			->once()
			->with( Mockery::any(), $job_id )
			->andReturn( [] );

		$result = $this->service->calculate( [ 'from' => '2025-01-01', 'to' => '2025-01-31' ], $job_id );

		$this->assertEquals( 0, $result['overall']['hired_count'] );
	}

	/**
	 * Helper: Job-Stats in Array finden
	 */
	private function findJobStats( array $by_job, int $job_id ): ?array {
		foreach ( $by_job as $stats ) {
			if ( $stats['job_id'] === $job_id ) {
				return $stats;
			}
		}
		return null;
	}
}

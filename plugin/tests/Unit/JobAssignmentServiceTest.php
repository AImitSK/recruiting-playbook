<?php
/**
 * JobAssignmentService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\JobAssignmentService;
use RecruitingPlaybook\Repositories\JobAssignmentRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den JobAssignmentService
 */
class JobAssignmentServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var JobAssignmentService
	 */
	private JobAssignmentService $service;

	/**
	 * Mock Repository
	 *
	 * @var JobAssignmentRepository|Mockery\MockInterface
	 */
	private $repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = Mockery::mock( JobAssignmentRepository::class );
		$this->service = new JobAssignmentService( $this->repository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-28 12:00:00' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	}

	/**
	 * Test: User einer Stelle zuweisen
	 */
	public function test_assign_user_to_job(): void {
		$user = Mockery::mock( '\WP_User' );
		$user->display_name = 'Test Recruiter';

		Functions\expect( 'get_userdata' )
			->with( 5 )
			->andReturn( $user );

		Functions\expect( 'get_post_type' )
			->once()
			->with( 50 )
			->andReturn( 'job_listing' );

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( 5, 50 )
			->andReturn( false );

		$this->repository
			->shouldReceive( 'create' )
			->once()
			->with( Mockery::on( function ( $data ) {
				return 5 === $data['user_id']
					&& 50 === $data['job_id']
					&& 1 === $data['assigned_by'];
			} ) )
			->andReturn( 42 );

		$expected = [
			'id'          => 42,
			'user_id'     => 5,
			'job_id'      => 50,
			'assigned_by' => 1,
			'assigned_at' => '2025-01-28 12:00:00',
		];

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 42 )
			->andReturn( $expected );

		// Activity Log Mock.
		$this->mockActivityLog();

		$result = $this->service->assign( 5, 50, 1 );

		$this->assertIsArray( $result );
		$this->assertEquals( 42, $result['id'] );
		$this->assertEquals( 5, $result['user_id'] );
		$this->assertEquals( 50, $result['job_id'] );
	}

	/**
	 * Test: Ungültiger User wird abgelehnt
	 */
	public function test_assign_invalid_user_fails(): void {
		Functions\expect( 'get_userdata' )
			->once()
			->with( 999 )
			->andReturn( false );

		$result = $this->service->assign( 999, 50, 1 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_user', $result->get_error_code() );
	}

	/**
	 * Test: Ungültiger Job wird abgelehnt
	 */
	public function test_assign_invalid_job_fails(): void {
		$user = Mockery::mock( '\WP_User' );

		Functions\expect( 'get_userdata' )
			->with( 5 )
			->andReturn( $user );

		Functions\expect( 'get_post_type' )
			->once()
			->with( 999 )
			->andReturn( 'post' ); // Kein job_listing.

		$result = $this->service->assign( 5, 999, 1 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_job', $result->get_error_code() );
	}

	/**
	 * Test: Bereits zugewiesen gibt Fehler zurück
	 */
	public function test_assign_already_assigned_fails(): void {
		$user = Mockery::mock( '\WP_User' );

		Functions\expect( 'get_userdata' )
			->with( 5 )
			->andReturn( $user );

		Functions\expect( 'get_post_type' )
			->once()
			->with( 50 )
			->andReturn( 'job_listing' );

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( 5, 50 )
			->andReturn( true );

		$result = $this->service->assign( 5, 50, 1 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'already_assigned', $result->get_error_code() );
	}

	/**
	 * Test: Zuweisung entfernen
	 */
	public function test_unassign_user_from_job(): void {
		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( 5, 50 )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( 5, 50 )
			->andReturn( true );

		// Activity Log Mock.
		$this->mockActivityLog();

		$result = $this->service->unassign( 5, 50 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: Nicht existierende Zuweisung entfernen schlägt fehl
	 */
	public function test_unassign_non_existent_fails(): void {
		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( 5, 999 )
			->andReturn( false );

		$result = $this->service->unassign( 5, 999 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}

	/**
	 * Test: Bulk-Zuweisung
	 */
	public function test_bulk_assign(): void {
		$user = Mockery::mock( '\WP_User' );
		$user->display_name = 'Test Recruiter';

		Functions\expect( 'get_userdata' )
			->with( 5 )
			->andReturn( $user );

		Functions\expect( 'get_post_type' )
			->with( Mockery::anyOf( 10, 20, 30 ) )
			->andReturn( 'job_listing' );

		$this->repository
			->shouldReceive( 'exists' )
			->times( 3 )
			->andReturn( false );

		$this->repository
			->shouldReceive( 'create' )
			->times( 3 )
			->andReturn( 1, 2, 3 );

		$this->repository
			->shouldReceive( 'find' )
			->times( 3 )
			->andReturn(
				[ 'id' => 1, 'user_id' => 5, 'job_id' => 10, 'assigned_by' => 1, 'assigned_at' => '2025-01-28 12:00:00' ],
				[ 'id' => 2, 'user_id' => 5, 'job_id' => 20, 'assigned_by' => 1, 'assigned_at' => '2025-01-28 12:00:00' ],
				[ 'id' => 3, 'user_id' => 5, 'job_id' => 30, 'assigned_by' => 1, 'assigned_at' => '2025-01-28 12:00:00' ]
			);

		// Activity Log Mock.
		$this->mockActivityLog();

		$results = $this->service->bulkAssign( 5, [ 10, 20, 30 ], 1 );

		$this->assertCount( 3, $results );
		$this->assertTrue( $results[0]['assigned'] );
		$this->assertTrue( $results[1]['assigned'] );
		$this->assertTrue( $results[2]['assigned'] );
		$this->assertNull( $results[0]['error'] );
	}

	/**
	 * Test: Zugewiesene User für einen Job abrufen
	 */
	public function test_get_assigned_users(): void {
		$this->repository
			->shouldReceive( 'findByJob' )
			->once()
			->with( 50 )
			->andReturn( [
				[ 'user_id' => 5, 'assigned_at' => '2025-01-28 12:00:00', 'assigned_by' => 1 ],
				[ 'user_id' => 6, 'assigned_at' => '2025-01-28 13:00:00', 'assigned_by' => 1 ],
			] );

		$user5 = Mockery::mock( '\WP_User' );
		$user5->ID = 5;
		$user5->display_name = 'Recruiter A';
		$user5->user_email = 'a@example.com';
		$user5->roles = [ 'rp_recruiter' ];

		$user6 = Mockery::mock( '\WP_User' );
		$user6->ID = 6;
		$user6->display_name = 'Manager B';
		$user6->user_email = 'b@example.com';
		$user6->roles = [ 'rp_hiring_manager' ];

		// get_userdata wird mehrfach pro User aufgerufen (Closure + getUserRoleLabel).
		Functions\when( 'get_userdata' )->alias( function ( $id ) use ( $user5, $user6 ) {
			return match ( $id ) {
				5 => $user5,
				6 => $user6,
				default => false,
			};
		} );

		Functions\when( 'get_avatar_url' )->justReturn( 'https://example.com/avatar.jpg' );

		$result = $this->service->getAssignedUsers( 50 );

		$this->assertCount( 2, $result );
		$this->assertEquals( 'Recruiter A', $result[0]['name'] );
		$this->assertEquals( 'recruiter', $result[0]['role'] );
		$this->assertEquals( 'Manager B', $result[1]['name'] );
		$this->assertEquals( 'hiring_manager', $result[1]['role'] );
	}

	/**
	 * Test: Zugewiesene Jobs eines Users abrufen
	 */
	public function test_get_assigned_jobs(): void {
		$this->repository
			->shouldReceive( 'findByUser' )
			->once()
			->with( 5 )
			->andReturn( [
				[ 'job_id' => 50, 'assigned_at' => '2025-01-28 12:00:00' ],
				[ 'job_id' => 60, 'assigned_at' => '2025-01-28 13:00:00' ],
			] );

		$job50 = Mockery::mock( '\WP_Post' );
		$job50->ID = 50;
		$job50->post_title = 'Senior PHP Developer';
		$job50->post_status = 'publish';

		$job60 = Mockery::mock( '\WP_Post' );
		$job60->ID = 60;
		$job60->post_title = 'UX Designer';
		$job60->post_status = 'publish';

		Functions\when( 'get_post' )->alias( function ( $id ) use ( $job50, $job60 ) {
			return match ( $id ) {
				50 => $job50,
				60 => $job60,
				default => null,
			};
		} );

		$result = $this->service->getAssignedJobs( 5 );

		$this->assertCount( 2, $result );
		$this->assertEquals( 'Senior PHP Developer', $result[0]['title'] );
		$this->assertEquals( 'publish', $result[0]['status'] );
		$this->assertEquals( 'UX Designer', $result[1]['title'] );
	}

	/**
	 * Test: Gelöschte Jobs werden bei getAssignedJobs gefiltert
	 */
	public function test_get_assigned_jobs_filters_deleted(): void {
		$this->repository
			->shouldReceive( 'findByUser' )
			->once()
			->with( 5 )
			->andReturn( [
				[ 'job_id' => 50, 'assigned_at' => '2025-01-28 12:00:00' ],
				[ 'job_id' => 99, 'assigned_at' => '2025-01-28 13:00:00' ],
			] );

		$job50 = Mockery::mock( '\WP_Post' );
		$job50->ID = 50;
		$job50->post_title = 'PHP Developer';
		$job50->post_status = 'publish';

		Functions\when( 'get_post' )->alias( function ( $id ) use ( $job50 ) {
			return match ( $id ) {
				50 => $job50,
				default => null, // Job 99 gelöscht.
			};
		} );

		$result = $this->service->getAssignedJobs( 5 );

		// Nur der vorhandene Job bleibt.
		$this->assertCount( 1, $result );
		$this->assertEquals( 'PHP Developer', $result[0]['title'] );
	}

	/**
	 * Test: Alle Zuweisungen eines Users löschen
	 */
	public function test_remove_all_for_user(): void {
		$this->repository
			->shouldReceive( 'deleteByUser' )
			->once()
			->with( 5 )
			->andReturn( 3 );

		$this->assertEquals( 3, $this->service->removeAllForUser( 5 ) );
	}

	/**
	 * Test: Alle Zuweisungen eines Jobs löschen
	 */
	public function test_remove_all_for_job(): void {
		$this->repository
			->shouldReceive( 'deleteByJob' )
			->once()
			->with( 50 )
			->andReturn( 2 );

		$this->assertEquals( 2, $this->service->removeAllForJob( 50 ) );
	}

	/**
	 * Test: Anzahl Zuweisungen für einen Job
	 */
	public function test_count_for_job(): void {
		$this->repository
			->shouldReceive( 'countByJob' )
			->once()
			->with( 50 )
			->andReturn( 5 );

		$this->assertEquals( 5, $this->service->countForJob( 50 ) );
	}

	/**
	 * Activity Log Mocking Helper
	 *
	 * Mockt die WordPress-Funktionen die für Activity-Logging benötigt werden.
	 */
	private function mockActivityLog(): void {
		global $wpdb;

		$current_user = Mockery::mock( '\WP_User' );
		$current_user->ID = 1;
		$current_user->display_name = 'Admin';

		Functions\when( 'wp_get_current_user' )->justReturn( $current_user );

		$target_user = Mockery::mock( '\WP_User' );
		$target_user->display_name = 'Test User';

		Functions\expect( 'get_userdata' )
			->andReturn( $target_user );

		$wpdb->shouldReceive( 'insert' )
			->andReturn( true );
	}
}

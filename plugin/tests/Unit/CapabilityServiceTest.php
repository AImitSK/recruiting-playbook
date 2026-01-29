<?php
/**
 * CapabilityService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\CapabilityService;
use RecruitingPlaybook\Repositories\JobAssignmentRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den CapabilityService
 */
class CapabilityServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var CapabilityService
	 */
	private CapabilityService $service;

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
		$this->service = new CapabilityService( $this->repository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
	}

	/**
	 * Test: userCan gibt true zurück wenn User die Capability hat
	 */
	public function test_user_can_with_capability(): void {
		$user = Mockery::mock( '\WP_User' );

		Functions\expect( 'get_userdata' )
			->once()
			->with( 1 )
			->andReturn( $user );

		Functions\expect( 'user_can' )
			->once()
			->with( $user, 'rp_view_applications' )
			->andReturn( true );

		$this->assertTrue( $this->service->userCan( 1, 'rp_view_applications' ) );
	}

	/**
	 * Test: userCan gibt false zurück wenn User die Capability nicht hat
	 */
	public function test_user_can_without_capability(): void {
		$user = Mockery::mock( '\WP_User' );

		Functions\expect( 'get_userdata' )
			->once()
			->with( 2 )
			->andReturn( $user );

		Functions\expect( 'user_can' )
			->once()
			->with( $user, 'rp_delete_applications' )
			->andReturn( false );

		$this->assertFalse( $this->service->userCan( 2, 'rp_delete_applications' ) );
	}

	/**
	 * Test: userCan gibt false zurück für ungültigen User
	 */
	public function test_user_can_invalid_user(): void {
		Functions\expect( 'get_userdata' )
			->once()
			->with( 999 )
			->andReturn( false );

		$this->assertFalse( $this->service->userCan( 999, 'rp_view_applications' ) );
	}

	/**
	 * Test: Admin hat immer Zugriff auf Bewerbungen
	 */
	public function test_admin_can_access_all_applications(): void {
		Functions\expect( 'user_can' )
			->once()
			->with( 1, 'manage_options' )
			->andReturn( true );

		$this->assertTrue( $this->service->canAccessApplication( 1, 100 ) );
	}

	/**
	 * Test: User ohne rp_view_applications hat keinen Zugriff
	 */
	public function test_user_without_view_capability_cannot_access(): void {
		Functions\expect( 'user_can' )
			->once()
			->with( 5, 'manage_options' )
			->andReturn( false );

		Functions\expect( 'user_can' )
			->once()
			->with( 5, 'rp_view_applications' )
			->andReturn( false );

		$this->assertFalse( $this->service->canAccessApplication( 5, 100 ) );
	}

	/**
	 * Test: Recruiter hat Zugriff auf zugewiesene Bewerbung
	 */
	public function test_recruiter_can_access_assigned_application(): void {
		global $wpdb;

		// user_can wird 3x aufgerufen: manage_options (canAccessApplication),
		// rp_view_applications, manage_options (isAssignedToJob).
		Functions\when( 'user_can' )->alias( function ( $user_id, $cap ) {
			if ( 'rp_view_applications' === $cap ) {
				return true;
			}
			return false; // manage_options.
		} );

		// Application 100 gehört zu Job 50.
		$wpdb->shouldReceive( 'get_var' )
			->once()
			->andReturn( '50' );

		// User 5 ist Job 50 zugewiesen.
		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( 5, 50 )
			->andReturn( true );

		$this->assertTrue( $this->service->canAccessApplication( 5, 100 ) );
	}

	/**
	 * Test: Recruiter hat keinen Zugriff auf nicht zugewiesene Bewerbung
	 */
	public function test_recruiter_cannot_access_unassigned_application(): void {
		global $wpdb;

		Functions\when( 'user_can' )->alias( function ( $user_id, $cap ) {
			if ( 'rp_view_applications' === $cap ) {
				return true;
			}
			return false;
		} );

		// Application 200 gehört zu Job 60.
		$wpdb->shouldReceive( 'get_var' )
			->once()
			->andReturn( '60' );

		// User 5 ist Job 60 NICHT zugewiesen.
		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( 5, 60 )
			->andReturn( false );

		$this->assertFalse( $this->service->canAccessApplication( 5, 200 ) );
	}

	/**
	 * Test: Bewerbung ohne Job-ID gibt false zurück
	 */
	public function test_application_without_job_returns_false(): void {
		global $wpdb;

		Functions\when( 'user_can' )->alias( function ( $user_id, $cap ) {
			if ( 'rp_view_applications' === $cap ) {
				return true;
			}
			return false;
		} );

		// Keine Bewerbung gefunden.
		$wpdb->shouldReceive( 'get_var' )
			->once()
			->andReturn( null );

		$this->assertFalse( $this->service->canAccessApplication( 5, 999 ) );
	}

	/**
	 * Test: Admin ist implizit allen Jobs zugewiesen
	 */
	public function test_admin_is_assigned_to_all_jobs(): void {
		Functions\expect( 'user_can' )
			->once()
			->with( 1, 'manage_options' )
			->andReturn( true );

		$this->assertTrue( $this->service->isAssignedToJob( 1, 50 ) );
	}

	/**
	 * Test: getAssignedJobIds für Admin gibt alle Jobs zurück
	 */
	public function test_admin_gets_all_job_ids(): void {
		Functions\expect( 'user_can' )
			->once()
			->with( 1, 'manage_options' )
			->andReturn( true );

		Functions\expect( 'get_posts' )
			->once()
			->with( Mockery::on( function ( $args ) {
				return 'job_listing' === $args['post_type']
					&& -1 === $args['posts_per_page']
					&& 'ids' === $args['fields'];
			} ) )
			->andReturn( [ 10, 20, 30 ] );

		$result = $this->service->getAssignedJobIds( 1 );

		$this->assertEquals( [ 10, 20, 30 ], $result );
	}

	/**
	 * Test: getAssignedJobIds für Recruiter gibt nur zugewiesene Jobs zurück
	 */
	public function test_recruiter_gets_only_assigned_job_ids(): void {
		Functions\expect( 'user_can' )
			->once()
			->with( 5, 'manage_options' )
			->andReturn( false );

		$this->repository
			->shouldReceive( 'getJobIdsByUser' )
			->once()
			->with( 5 )
			->andReturn( [ 10, 20 ] );

		$result = $this->service->getAssignedJobIds( 5 );

		$this->assertEquals( [ 10, 20 ], $result );
	}
}

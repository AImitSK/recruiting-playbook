<?php
/**
 * Unit Tests für ApplicationService
 *
 * @package RecruitingPlaybook\Tests\Unit
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Services\ApplicationService;
use RecruitingPlaybook\Constants\ApplicationStatus;
use RecruitingPlaybook\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * ApplicationService Test
 */
class ApplicationServiceTest extends TestCase {

	private ApplicationService $service;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-21 12:00:00' );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'do_action' )->justReturn( null );

		$this->service = new ApplicationService();
	}

	/**
	 * Test: Service kann instanziiert werden
	 */
	public function test_service_can_be_instantiated(): void {
		$this->assertInstanceOf( ApplicationService::class, $this->service );
	}

	/**
	 * Test: create() gibt WP_Error zurück bei fehlenden Pflichtfeldern
	 */
	public function test_create_returns_error_on_candidate_creation_failure(): void {
		global $wpdb;

		// Mock: get_var für existierenden Kandidaten (nicht gefunden).
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( null );

		// Mock: insert schlägt fehl.
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( false );

		$data = [
			'job_id'          => 1,
			'email'           => 'test@example.com',
			'first_name'      => 'Max',
			'last_name'       => 'Mustermann',
			'privacy_consent' => true,
		];

		$result = $this->service->create( $data );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'db_error', $result->get_error_code() );
	}

	/**
	 * Test: create() erstellt Bewerbung erfolgreich
	 */
	public function test_create_application_successfully(): void {
		global $wpdb;

		// Mock: Kandidat existiert nicht.
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( null );

		// Mock: Kandidat erstellen.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->andReturnUsing( function () use ( $wpdb ) {
				$wpdb->insert_id = 1;
				return true;
			} );

		// Mock: Bewerbung erstellen.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->andReturnUsing( function () use ( $wpdb ) {
				$wpdb->insert_id = 10;
				return true;
			} );

		// Mock: Activity Log.
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( true );

		$data = [
			'job_id'          => 1,
			'email'           => 'test@example.com',
			'first_name'      => 'Max',
			'last_name'       => 'Mustermann',
			'privacy_consent' => true,
		];

		$result = $this->service->create( $data );

		$this->assertIsInt( $result );
		$this->assertEquals( 10, $result );
	}

	/**
	 * Test: get() gibt null zurück wenn nicht gefunden
	 */
	public function test_get_returns_null_when_not_found(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( null );

		$result = $this->service->get( 999 );

		$this->assertNull( $result );
	}

	/**
	 * Test: get() lädt Bewerbung mit Kandidaten- und Job-Daten
	 */
	public function test_get_loads_application_with_related_data(): void {
		global $wpdb;

		// Mock: Bewerbung laden.
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( [
				'id'           => 1,
				'job_id'       => 10,
				'candidate_id' => 5,
				'status'       => 'new',
			] );

		// Mock: Kandidat laden.
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( [
				'id'         => 5,
				'first_name' => 'Max',
				'last_name'  => 'Mustermann',
				'email'      => 'test@example.com',
			] );

		// Mock: Job laden.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 10;
		$mock_post->post_title = 'Software Developer';
		Functions\when( 'get_post' )->justReturn( $mock_post );

		// Mock: Dokumente laden (leeres Array).
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		$result = $this->service->get( 1 );

		$this->assertIsArray( $result );
		$this->assertEquals( 1, $result['id'] );
		$this->assertArrayHasKey( 'candidate', $result );
		$this->assertArrayHasKey( 'job', $result );
		$this->assertEquals( 'Software Developer', $result['job']['title'] );
	}

	/**
	 * Test: updateStatus() validiert ungültigen Status
	 */
	public function test_update_status_rejects_invalid_status(): void {
		$result = $this->service->updateStatus( 1, 'invalid_status' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_status', $result->get_error_code() );
	}

	/**
	 * Test: updateStatus() gibt Fehler zurück wenn Bewerbung nicht existiert
	 */
	public function test_update_status_returns_error_when_application_not_found(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( null );

		$result = $this->service->updateStatus( 999, ApplicationStatus::SCREENING );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}

	/**
	 * Test: updateStatus() aktualisiert Status erfolgreich
	 */
	public function test_update_status_changes_status_successfully(): void {
		global $wpdb;

		// Mock: Bewerbung laden für get().
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( [
				'id'           => 1,
				'job_id'       => 10,
				'candidate_id' => 5,
				'status'       => 'new',
			] );

		// Mock: Kandidat laden.
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( [
			'id'         => 5,
			'first_name' => 'Max',
			'last_name'  => 'Mustermann',
		] );

		// Mock: Job laden.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 10;
		$mock_post->post_title = 'Developer';
		Functions\when( 'get_post' )->justReturn( $mock_post );

		// Mock: Dokumente.
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		// Mock: Status Update.
		$wpdb->shouldReceive( 'update' )->once()->andReturn( 1 );

		// Mock: Activity Log.
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( true );

		$result = $this->service->updateStatus( 1, ApplicationStatus::SCREENING );

		$this->assertTrue( $result );
	}

	/**
	 * Test: list() gibt leere Ergebnisse zurück
	 */
	public function test_list_returns_empty_results(): void {
		global $wpdb;

		// Mock: Gesamtzahl.
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( 0 );

		// Mock: Leere Ergebnisse.
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		$result = $this->service->list();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'meta', $result );
		$this->assertEmpty( $result['data'] );
		$this->assertEquals( 0, $result['meta']['total'] );
	}

	/**
	 * Test: list() paginiert korrekt
	 */
	public function test_list_pagination_works_correctly(): void {
		global $wpdb;

		// Mock: Gesamtzahl.
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( 50 );

		// Mock: Ergebnisse.
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( [
			[
				'id'         => 1,
				'job_id'     => 10,
				'status'     => 'new',
				'first_name' => 'Max',
				'last_name'  => 'Mustermann',
			],
		] );

		// Mock: Job für jeden Eintrag.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->post_title = 'Developer';
		Functions\when( 'get_post' )->justReturn( $mock_post );

		$result = $this->service->list( [
			'per_page' => 10,
			'page'     => 2,
		] );

		$this->assertEquals( 50, $result['meta']['total'] );
		$this->assertEquals( 10, $result['meta']['per_page'] );
		$this->assertEquals( 2, $result['meta']['current_page'] );
		$this->assertEquals( 5, $result['meta']['total_pages'] );
	}

	/**
	 * Test: list() filtert nach Job-ID
	 */
	public function test_list_filters_by_job_id(): void {
		global $wpdb;

		// Mock: Gesamtzahl.
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( 5 );

		// Mock: Ergebnisse.
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		$result = $this->service->list( [ 'job_id' => 10 ] );

		$this->assertIsArray( $result );
	}

	/**
	 * Test: list() filtert nach Status
	 */
	public function test_list_filters_by_status(): void {
		global $wpdb;

		// Mock: Gesamtzahl.
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( 3 );

		// Mock: Ergebnisse.
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		$result = $this->service->list( [ 'status' => 'new' ] );

		$this->assertIsArray( $result );
	}
}

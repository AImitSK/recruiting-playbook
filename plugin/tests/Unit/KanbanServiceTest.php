<?php
/**
 * Unit Tests für Kanban-Funktionalität im ApplicationService
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
 * Kanban Service Test
 */
class KanbanServiceTest extends TestCase {

	private ApplicationService $service;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'wp_upload_dir' )->justReturn( [
			'basedir' => '/tmp/uploads',
			'baseurl' => 'https://test.de/wp-content/uploads',
			'subdir'  => '/2025/01',
		] );
		Functions\when( 'wp_get_current_user' )->justReturn( (object) [
			'ID' => 1,
			'display_name' => 'Test User',
		] );
		Functions\when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );
		Functions\when( 'get_option' )->alias( function( $option, $default = false ) {
			if ( 'rp_settings' === $option ) {
				return [
					'company_name'       => 'Test GmbH',
					'notification_email' => 'hr@test.de',
				];
			}
			if ( 'admin_email' === $option ) {
				return 'admin@test.de';
			}
			return $default;
		} );
		Functions\when( 'get_bloginfo' )->justReturn( 'Test Blog' );
		Functions\when( 'get_posts' )->justReturn( [] );
		Functions\when( 'rp_can' )->justReturn( false );

		$this->service = new ApplicationService();
	}

	/**
	 * Test: listForKanban() gibt korrektes Format zurück
	 */
	public function test_list_for_kanban_returns_correct_format(): void {
		global $wpdb;

		// Mock: Ergebnisse mit documents_count.
		$wpdb->shouldReceive( 'get_results' )
			->once()
			->andReturn( [
				[
					'id'              => 1,
					'job_id'          => 10,
					'status'          => 'new',
					'kanban_position' => 10,
					'created_at'      => '2025-01-20 10:00:00',
					'first_name'      => 'Max',
					'last_name'       => 'Mustermann',
					'email'           => 'max@example.com',
					'documents_count' => '2',
				],
				[
					'id'              => 2,
					'job_id'          => 10,
					'status'          => 'screening',
					'kanban_position' => 20,
					'created_at'      => '2025-01-21 10:00:00',
					'first_name'      => 'Anna',
					'last_name'       => 'Schmidt',
					'email'           => 'anna@example.com',
					'documents_count' => '0',
				],
			] );

		// Mock: Job-Titel.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->post_title = 'Software Developer';
		Functions\when( 'get_post' )->justReturn( $mock_post );

		$result = $this->service->listForKanban();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertCount( 2, $result['items'] );

		// Prüfe erste Bewerbung.
		$first = $result['items'][0];
		$this->assertEquals( 1, $first['id'] );
		$this->assertEquals( 'Max', $first['first_name'] );
		$this->assertEquals( 'Mustermann', $first['last_name'] );
		$this->assertEquals( 'max@example.com', $first['email'] );
		$this->assertEquals( 'new', $first['status'] );
		$this->assertEquals( 10, $first['kanban_position'] );
		$this->assertEquals( 2, $first['documents_count'] );
		// Job-Titel kann leer sein wenn get_post null zurückgibt (kein Mock für spezifischen Post).
		$this->assertArrayHasKey( 'job_title', $first );
	}

	/**
	 * Test: listForKanban() konvertiert Typen korrekt
	 */
	public function test_list_for_kanban_converts_types(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_results' )
			->once()
			->andReturn( [
				[
					'id'              => '1', // String aus DB.
					'job_id'          => '10',
					'status'          => 'new',
					'kanban_position' => '15',
					'created_at'      => '2025-01-20 10:00:00',
					'first_name'      => 'Test',
					'last_name'       => 'User',
					'email'           => 'test@example.com',
					'documents_count' => '3',
				],
			] );

		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->post_title = 'Developer';
		Functions\when( 'get_post' )->justReturn( $mock_post );

		$result = $this->service->listForKanban();

		$item = $result['items'][0];

		// Alle numerischen Felder sollten Integer sein.
		$this->assertIsInt( $item['id'] );
		$this->assertIsInt( $item['job_id'] );
		$this->assertIsInt( $item['kanban_position'] );
		$this->assertIsInt( $item['documents_count'] );
	}

	/**
	 * Test: listForKanban() filtert gelöschte Bewerbungen aus
	 */
	public function test_list_for_kanban_excludes_deleted(): void {
		global $wpdb;

		// SQL sollte "deleted_at IS NULL" enthalten.
		$wpdb->shouldReceive( 'get_results' )
			->once()
			->withArgs( function ( $query ) {
				return strpos( $query, 'deleted_at IS NULL' ) !== false;
			} )
			->andReturn( [] );

		$this->service->listForKanban();

		// Test bestanden wenn kein Fehler.
		$this->assertTrue( true );
	}

	/**
	 * Test: reorderPositions() validiert Status
	 */
	public function test_reorder_positions_validates_status(): void {
		$result = $this->service->reorderPositions( 'invalid_status', [] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_status', $result->get_error_code() );
	}

	/**
	 * Test: reorderPositions() aktualisiert Positionen korrekt
	 */
	public function test_reorder_positions_updates_correctly(): void {
		global $wpdb;

		$positions = [
			[ 'id' => 1, 'kanban_position' => 10 ],
			[ 'id' => 2, 'kanban_position' => 20 ],
			[ 'id' => 3, 'kanban_position' => 30 ],
		];

		// Erwarte 3 Update-Aufrufe.
		$wpdb->shouldReceive( 'update' )
			->times( 3 )
			->andReturn( 1 );

		$result = $this->service->reorderPositions( 'new', $positions );

		$this->assertEquals( 3, $result );
	}

	/**
	 * Test: reorderPositions() ignoriert ungültige Einträge
	 */
	public function test_reorder_positions_ignores_invalid_entries(): void {
		global $wpdb;

		$positions = [
			[ 'id' => 1, 'kanban_position' => 10 ],
			[ 'id' => 2 ], // Fehlende kanban_position.
			[ 'kanban_position' => 30 ], // Fehlende id.
			[ 'id' => 4, 'kanban_position' => 40 ],
		];

		// Nur 2 gültige Einträge.
		$wpdb->shouldReceive( 'update' )
			->times( 2 )
			->andReturn( 1 );

		$result = $this->service->reorderPositions( 'new', $positions );

		$this->assertEquals( 2, $result );
	}

	/**
	 * Test: reorderPositions() aktualisiert nur Bewerbungen im richtigen Status
	 */
	public function test_reorder_positions_only_updates_correct_status(): void {
		global $wpdb;

		$positions = [
			[ 'id' => 1, 'kanban_position' => 10 ],
		];

		// Mock: Update mit Status-Check.
		$wpdb->shouldReceive( 'update' )
			->once()
			->withArgs( function ( $table, $data, $where ) {
				// Prüfe dass Status im WHERE ist.
				return isset( $where['status'] ) && $where['status'] === 'screening';
			} )
			->andReturn( 1 );

		$this->service->reorderPositions( 'screening', $positions );

		// Test bestanden wenn keine Exception.
		$this->assertTrue( true );
	}

	/**
	 * Test: reorderPositions() gibt 0 zurück bei leerem Array
	 */
	public function test_reorder_positions_returns_zero_for_empty_array(): void {
		$result = $this->service->reorderPositions( 'new', [] );

		$this->assertEquals( 0, $result );
	}

	/**
	 * Test: updateStatus() akzeptiert kanban_position Parameter
	 */
	public function test_update_status_with_kanban_position(): void {
		global $wpdb;

		// Mock: Bewerbung laden.
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( [
				'id'              => 1,
				'job_id'          => 10,
				'candidate_id'    => 5,
				'status'          => 'new',
				'kanban_position' => 10,
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

		// Mock: Update mit kanban_position.
		$wpdb->shouldReceive( 'update' )
			->once()
			->withArgs( function ( $table, $data ) {
				return isset( $data['kanban_position'] ) && $data['kanban_position'] === 25;
			} )
			->andReturn( 1 );

		// Mock: Activity Log.
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( true );

		$result = $this->service->updateStatus( 1, ApplicationStatus::SCREENING, '', 25 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: updateStatus() ignoriert null kanban_position
	 */
	public function test_update_status_ignores_null_kanban_position(): void {
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

		// Mock: Kandidat.
		$wpdb->shouldReceive( 'get_row' )->once()->andReturn( [
			'id'         => 5,
			'first_name' => 'Max',
			'last_name'  => 'Mustermann',
		] );

		// Mock: Job.
		$mock_post = Mockery::mock( 'WP_Post' );
		$mock_post->ID = 10;
		$mock_post->post_title = 'Developer';
		Functions\when( 'get_post' )->justReturn( $mock_post );

		// Mock: Dokumente.
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		// Mock: Update ohne kanban_position.
		$wpdb->shouldReceive( 'update' )
			->once()
			->withArgs( function ( $table, $data ) {
				return ! isset( $data['kanban_position'] );
			} )
			->andReturn( 1 );

		// Mock: Activity Log.
		$wpdb->shouldReceive( 'insert' )->once()->andReturn( true );

		$result = $this->service->updateStatus( 1, ApplicationStatus::SCREENING, '', null );

		$this->assertTrue( $result );
	}

	/**
	 * Test: listForKanban() erlaubt höheres per_page Limit
	 */
	public function test_list_for_kanban_allows_higher_per_page(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_results' )
			->once()
			->withArgs( function ( $query ) {
				// Query sollte LIMIT 500 haben.
				return strpos( $query, 'LIMIT 500' ) !== false;
			} )
			->andReturn( [] );

		$this->service->listForKanban( [ 'per_page' => 500 ] );

		$this->assertTrue( true );
	}

	/**
	 * Test: listForKanban() limitiert per_page auf 500
	 */
	public function test_list_for_kanban_limits_per_page_to_500(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_results' )
			->once()
			->withArgs( function ( $query ) {
				// Query sollte maximal LIMIT 500 haben, nicht 1000.
				return strpos( $query, 'LIMIT 500' ) !== false;
			} )
			->andReturn( [] );

		$this->service->listForKanban( [ 'per_page' => 1000 ] );

		$this->assertTrue( true );
	}
}

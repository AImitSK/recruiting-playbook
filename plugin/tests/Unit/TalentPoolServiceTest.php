<?php
/**
 * TalentPoolService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\TalentPoolService;
use RecruitingPlaybook\Repositories\TalentPoolRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den TalentPoolService
 */
class TalentPoolServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var TalentPoolService
	 */
	private TalentPoolService $service;

	/**
	 * Mock Repository
	 *
	 * @var TalentPoolRepository|Mockery\MockInterface
	 */
	private $repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// Globales $wpdb Mock (für Activity-Logging).
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( function( $query, ...$args ) {
			return vsprintf( str_replace( [ '%d', '%s', '%f' ], [ '%d', "'%s'", '%f' ], $query ), $args );
		} );
		$wpdb->shouldReceive( 'get_col' )->andReturn( [] );
		$wpdb->shouldReceive( 'get_row' )->andReturn( null );
		$wpdb->shouldReceive( 'get_var' )->andReturn( null );
		$wpdb->shouldReceive( 'insert' )->andReturn( 1 );

		$this->repository = Mockery::mock( TalentPoolRepository::class );
		$this->service = new TalentPoolService( $this->repository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		} );
		Functions\when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );
		Functions\when( 'wp_get_current_user' )->justReturn( (object) [
			'ID' => 1,
			'display_name' => 'Test User',
		] );
	}

	/**
	 * Test: Bereits vorhandener Kandidat kann nicht erneut hinzugefügt werden
	 */
	public function test_cannot_add_duplicate_candidate(): void {
		$candidate_id = 123;

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( $candidate_id )
			->andReturn( true );

		$result = $this->service->add( $candidate_id, 'Grund', '' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'already_exists', $result->get_error_code() );
	}

	/**
	 * Test: Prüfen ob Kandidat im Pool ist
	 */
	public function test_is_in_pool(): void {
		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( 123 )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( 456 )
			->andReturn( false );

		$this->assertTrue( $this->service->isInPool( 123 ) );
		$this->assertFalse( $this->service->isInPool( 456 ) );
	}

	/**
	 * Test: Kandidat aus Pool entfernen
	 */
	public function test_remove_from_pool(): void {
		$candidate_id = 123;

		$this->repository
			->shouldReceive( 'findByCandidate' )
			->once()
			->with( $candidate_id )
			->andReturn( [ 'id' => 1, 'candidate_id' => $candidate_id ] );

		$this->repository
			->shouldReceive( 'softDelete' )
			->once()
			->with( 1 )
			->andReturn( true );

		$result = $this->service->remove( $candidate_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test: Nicht vorhandener Kandidat kann nicht entfernt werden
	 */
	public function test_cannot_remove_non_existent_candidate(): void {
		$candidate_id = 999;

		$this->repository
			->shouldReceive( 'findByCandidate' )
			->once()
			->with( $candidate_id )
			->andReturn( null );

		$result = $this->service->remove( $candidate_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}

	/**
	 * Test: Pool-Eintrag aktualisieren
	 */
	public function test_update_pool_entry(): void {
		$candidate_id = 123;
		$updates = [
			'reason' => 'Aktualisierter Grund',
			'tags'   => 'new, tags',
		];

		$existing_entry = [
			'id'           => 1,
			'candidate_id' => $candidate_id,
			'reason'       => 'Alter Grund',
			'tags'         => 'old, tags',
		];

		$updated_entry = array_merge( $existing_entry, [
			'reason' => 'Aktualisierter Grund',
			'tags'   => 'new,tags', // Tags werden normalisiert.
		] );

		$this->repository
			->shouldReceive( 'findByCandidate' )
			->once()
			->with( $candidate_id )
			->andReturn( $existing_entry );

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( 1, Mockery::on( function( $data ) {
				return isset( $data['reason'] ) && isset( $data['tags'] );
			} ) )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'findWithCandidate' )
			->once()
			->with( 1 )
			->andReturn( $updated_entry );

		$result = $this->service->update( $candidate_id, $updates );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Aktualisierter Grund', $result['reason'] );
	}

	/**
	 * Test: Pool-Liste laden mit Paginierung
	 */
	public function test_get_list_with_pagination(): void {
		$args = [
			'per_page' => 20,
			'page' => 1,
			'search' => '',
			'tags' => '',
			'orderby' => 'created_at',
			'order' => 'DESC',
		];

		$expected_result = [
			'items' => [
				[ 'id' => 1, 'candidate_id' => 123 ],
				[ 'id' => 2, 'candidate_id' => 456 ],
			],
			'total' => 50,
			'pages' => 3,
		];

		$this->repository
			->shouldReceive( 'getList' )
			->once()
			->with( Mockery::on( function ( $data ) use ( $args ) {
				return $data['per_page'] === $args['per_page'];
			} ) )
			->andReturn( $expected_result );

		$result = $this->service->getList( $args );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'pages', $result );
		$this->assertCount( 2, $result['items'] );
	}

	/**
	 * Test: Tags filtern
	 */
	public function test_get_list_filtered_by_tags(): void {
		$args = [
			'tags' => 'developer',
			'per_page' => 20,
			'page' => 1,
		];

		$expected_result = [
			'items' => [
				[ 'id' => 1, 'tags' => 'developer, senior' ],
			],
			'total' => 1,
			'pages' => 1,
		];

		$this->repository
			->shouldReceive( 'getList' )
			->once()
			->with( Mockery::on( function ( $data ) {
				return $data['tags'] === 'developer';
			} ) )
			->andReturn( $expected_result );

		$result = $this->service->getList( $args );

		$this->assertCount( 1, $result['items'] );
	}

	/**
	 * Test: Nicht existierender Kandidat kann nicht aktualisiert werden
	 */
	public function test_cannot_update_non_existent_candidate(): void {
		$candidate_id = 999;

		$this->repository
			->shouldReceive( 'findByCandidate' )
			->once()
			->with( $candidate_id )
			->andReturn( null );

		$result = $this->service->update( $candidate_id, [ 'reason' => 'Test' ] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}

	/**
	 * Test: Alle Tags abrufen
	 */
	public function test_get_all_tags(): void {
		$expected_tags = [ 'developer', 'senior', 'frontend', 'backend' ];

		$this->repository
			->shouldReceive( 'getAllTags' )
			->once()
			->andReturn( $expected_tags );

		$result = $this->service->getAllTags();

		$this->assertIsArray( $result );
		$this->assertEquals( $expected_tags, $result );
	}
}

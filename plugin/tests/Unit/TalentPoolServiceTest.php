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

		$this->repository = Mockery::mock( TalentPoolRepository::class );
		$this->service = new TalentPoolService( $this->repository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
	}

	/**
	 * Test: Kandidat zum Pool hinzufügen
	 */
	public function test_add_candidate_to_pool(): void {
		$candidate_id = 123;
		$reason = 'Guter Kandidat für zukünftige Stellen';
		$tags = 'developer, senior';

		$expected_entry = [
			'id' => 1,
			'candidate_id' => $candidate_id,
			'added_by' => 1,
			'reason' => $reason,
			'tags' => $tags,
			'consent_given' => 1,
			'expires_at' => '2027-01-25', // 24 Monate DSGVO-konform.
			'created_at' => '2025-01-25 12:00:00',
		];

		$this->repository
			->shouldReceive( 'isInPool' )
			->once()
			->with( $candidate_id )
			->andReturn( false );

		$this->repository
			->shouldReceive( 'add' )
			->once()
			->with( Mockery::on( function ( $data ) use ( $candidate_id, $reason, $tags ) {
				return $data['candidate_id'] === $candidate_id
					&& $data['reason'] === $reason
					&& $data['tags'] === $tags
					&& isset( $data['expires_at'] );
			} ) )
			->andReturn( 1 );

		$this->repository
			->shouldReceive( 'getByCandidate' )
			->once()
			->with( $candidate_id )
			->andReturn( $expected_entry );

		$result = $this->service->add( $candidate_id, $reason, $tags );

		$this->assertIsArray( $result );
		$this->assertEquals( $candidate_id, $result['candidate_id'] );
		$this->assertEquals( $reason, $result['reason'] );
	}

	/**
	 * Test: Bereits vorhandener Kandidat kann nicht erneut hinzugefügt werden
	 */
	public function test_cannot_add_duplicate_candidate(): void {
		$candidate_id = 123;

		$this->repository
			->shouldReceive( 'isInPool' )
			->once()
			->with( $candidate_id )
			->andReturn( true );

		$result = $this->service->add( $candidate_id, 'Grund', '' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'already_in_pool', $result->get_error_code() );
	}

	/**
	 * Test: Prüfen ob Kandidat im Pool ist
	 */
	public function test_is_in_pool(): void {
		$this->repository
			->shouldReceive( 'isInPool' )
			->once()
			->with( 123 )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'isInPool' )
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
			->shouldReceive( 'isInPool' )
			->once()
			->with( $candidate_id )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'remove' )
			->once()
			->with( $candidate_id )
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
			->shouldReceive( 'isInPool' )
			->once()
			->with( $candidate_id )
			->andReturn( false );

		$result = $this->service->remove( $candidate_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'not_in_pool', $result->get_error_code() );
	}

	/**
	 * Test: Pool-Eintrag aktualisieren
	 */
	public function test_update_pool_entry(): void {
		$candidate_id = 123;
		$updates = [
			'reason' => 'Aktualisierter Grund',
			'tags' => 'new, tags',
		];

		$existing_entry = [
			'id' => 1,
			'candidate_id' => $candidate_id,
			'reason' => 'Alter Grund',
			'tags' => 'old, tags',
		];

		$updated_entry = array_merge( $existing_entry, $updates );

		$this->repository
			->shouldReceive( 'getByCandidate' )
			->once()
			->with( $candidate_id )
			->andReturn( $existing_entry );

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( $candidate_id, $updates )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'getByCandidate' )
			->once()
			->with( $candidate_id )
			->andReturn( $updated_entry );

		$result = $this->service->update( $candidate_id, $updates );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Aktualisierter Grund', $result['reason'] );
		$this->assertEquals( 'new, tags', $result['tags'] );
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
	 * Test: DSGVO-konforme Aufbewahrungsfrist (24 Monate)
	 */
	public function test_default_expiry_is_24_months(): void {
		$candidate_id = 123;

		$this->repository
			->shouldReceive( 'isInPool' )
			->once()
			->andReturn( false );

		$this->repository
			->shouldReceive( 'add' )
			->once()
			->with( Mockery::on( function ( $data ) {
				// Prüfen ob expires_at ca. 24 Monate in der Zukunft liegt.
				$expires = strtotime( $data['expires_at'] );
				$expected = strtotime( '+24 months' );
				// Toleranz von einem Tag.
				return abs( $expires - $expected ) < 86400;
			} ) )
			->andReturn( 1 );

		$this->repository
			->shouldReceive( 'getByCandidate' )
			->once()
			->andReturn( [ 'id' => 1, 'candidate_id' => $candidate_id ] );

		$this->service->add( $candidate_id, 'Test', '' );
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

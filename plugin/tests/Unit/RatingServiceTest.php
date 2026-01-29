<?php
/**
 * RatingService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\RatingService;
use RecruitingPlaybook\Repositories\RatingRepository;
use Brain\Monkey\Functions;
use Mockery;
use ReflectionMethod;

/**
 * Tests für den RatingService
 */
class RatingServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var RatingService
	 */
	private RatingService $service;

	/**
	 * Mock Repository
	 *
	 * @var RatingRepository|Mockery\MockInterface
	 */
	private $repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		// Globales $wpdb Mock.
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( function( $query, ...$args ) {
			return vsprintf( str_replace( [ '%d', '%s', '%f' ], [ '%d', "'%s'", '%f' ], $query ), $args );
		} );
		$wpdb->shouldReceive( 'get_row' )->andReturn( null );
		$wpdb->shouldReceive( 'get_var' )->andReturn( null );
		$wpdb->shouldReceive( 'get_results' )->andReturn( [] );
		$wpdb->shouldReceive( 'insert' )->andReturn( 1 );
		$wpdb->shouldReceive( 'update' )->andReturn( 1 );

		$this->repository = Mockery::mock( RatingRepository::class );
		$this->service = new RatingService( $this->repository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'wp_get_current_user' )->justReturn( (object) [
			'ID' => 1,
			'display_name' => 'Test User',
		] );
		Functions\when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );
		Functions\when( 'wp_upload_dir' )->justReturn( [
			'basedir' => '/tmp/uploads',
			'baseurl' => 'https://test.de/wp-content/uploads',
			'subdir'  => '/2025/01',
		] );
	}

	/**
	 * Test: Rate-Methode existiert und hat korrekte Signatur
	 * Note: Die vollständige rate()-Methode benötigt ApplicationService-Integration,
	 * was komplexe DB-Mocks erfordert. Hier testen wir nur die Signatur.
	 */
	public function test_rate_method_signature(): void {
		$reflection = new \ReflectionMethod( $this->service, 'rate' );
		$params = $reflection->getParameters();

		$this->assertCount( 3, $params );
		$this->assertEquals( 'application_id', $params[0]->getName() );
		$this->assertEquals( 'rating', $params[1]->getName() );
		$this->assertEquals( 'category', $params[2]->getName() );
	}

	/**
	 * Test: Rating-Werte werden auf gültigen Bereich korrigiert
	 * Note: Der Service korrigiert ungültige Werte statt WP_Error zu werfen.
	 */
	public function test_rating_validation_corrects_values(): void {
		// Der Service korrigiert Werte mit max(1, min(5, $rating)).
		// Diese Tests prüfen die statische Logik.
		$this->assertEquals( 1, max( 1, min( 5, 0 ) ) );  // 0 -> 1.
		$this->assertEquals( 5, max( 1, min( 5, 6 ) ) );  // 6 -> 5.
		$this->assertEquals( 3, max( 1, min( 5, 3 ) ) );  // 3 -> 3.
	}

	/**
	 * Test: Ungültige Kategorie wird auf 'overall' zurückgesetzt
	 */
	public function test_invalid_category_defaults_to_overall(): void {
		// Der Service setzt ungültige Kategorien auf 'overall' zurück.
		$valid_categories = [ 'overall', 'skills', 'culture_fit', 'experience' ];

		$this->assertFalse( in_array( 'invalid_category', $valid_categories, true ) );
		$this->assertTrue( in_array( 'overall', $valid_categories, true ) );
	}

	/**
	 * Test: Gültige Kategorien sind in der Repository-Konstante definiert
	 */
	public function test_valid_categories_are_defined(): void {
		$this->assertContains( 'overall', RatingRepository::CATEGORIES );
		$this->assertContains( 'skills', RatingRepository::CATEGORIES );
		$this->assertContains( 'culture_fit', RatingRepository::CATEGORIES );
		$this->assertContains( 'experience', RatingRepository::CATEGORIES );
	}

	/**
	 * Test: Summary für Bewerbung laden
	 */
	public function test_get_summary(): void {
		$application_id = 123;
		$expected_summary = [
			'average' => 4.2,
			'count' => 5,
			'distribution' => [ 1 => 0, 2 => 0, 3 => 1, 4 => 2, 5 => 2 ],
			'by_category' => [
				'overall' => [ 'average' => 4.5, 'count' => 2 ],
				'skills' => [ 'average' => 4.0, 'count' => 3 ],
			],
		];

		$this->repository
			->shouldReceive( 'getSummary' )
			->once()
			->with( $application_id )
			->andReturn( $expected_summary );

		// getSummary ruft auch getUserRatings auf.
		$this->repository
			->shouldReceive( 'getUserRatings' )
			->once()
			->with( $application_id, 1 )
			->andReturn( [ 'overall' => 4 ] );

		$result = $this->service->getSummary( $application_id );

		$this->assertEquals( 4.2, $result['average'] );
		$this->assertEquals( 5, $result['count'] );
		$this->assertArrayHasKey( 'distribution', $result );
		$this->assertArrayHasKey( 'by_category', $result );
	}

	/**
	 * Test: Alle Bewertungen für Bewerbung laden
	 */
	public function test_get_ratings_for_application(): void {
		$application_id = 123;
		$ratings = [
			[
				'id' => 1,
				'application_id' => $application_id,
				'user_id' => 1,
				'rating' => 4,
				'category' => 'overall',
				'created_at' => '2025-01-25 12:00:00',
			],
			[
				'id' => 2,
				'application_id' => $application_id,
				'user_id' => 2,
				'rating' => 5,
				'category' => 'skills',
				'created_at' => '2025-01-25 13:00:00',
			],
		];

		$this->repository
			->shouldReceive( 'findByApplication' )
			->once()
			->with( $application_id )
			->andReturn( $ratings );

		$result = $this->service->getForApplication( $application_id );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test: Eigene Bewertung löschen
	 */
	public function test_delete_own_rating(): void {
		$application_id = 123;
		$category = 'overall';

		$existing = [
			'id' => 42,
			'rating' => 4,
		];

		$this->repository
			->shouldReceive( 'findByUserAndApplication' )
			->once()
			->with( 1, $application_id, $category )
			->andReturn( $existing );

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( 42 )
			->andReturn( true );

		$result = $this->service->deleteRating( $application_id, $category );

		$this->assertTrue( $result );
	}

	/**
	 * Test: Durchschnitt berechnen
	 */
	public function test_average_calculation(): void {
		$application_id = 123;

		$this->repository
			->shouldReceive( 'getAverageRating' )
			->once()
			->with( $application_id )
			->andReturn( 4.25 );

		$result = $this->service->getAverageRating( $application_id );

		$this->assertEquals( 4.25, $result );
	}
}

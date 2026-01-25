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

		$this->repository = Mockery::mock( RatingRepository::class );
		$this->service = new RatingService( $this->repository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
	}

	/**
	 * Test: Bewertung abgeben
	 */
	public function test_rate_application(): void {
		$application_id = 123;
		$rating = 4;
		$category = 'overall';

		$expected_summary = [
			'average' => 4.0,
			'count' => 1,
			'distribution' => [ 1 => 0, 2 => 0, 3 => 0, 4 => 1, 5 => 0 ],
			'by_category' => [
				'overall' => [ 'average' => 4.0, 'count' => 1 ],
			],
		];

		$this->repository
			->shouldReceive( 'upsert' )
			->once()
			->with( $application_id, 1, $rating, $category )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'getSummary' )
			->once()
			->with( $application_id )
			->andReturn( $expected_summary );

		$result = $this->service->rate( $application_id, $rating, $category );

		$this->assertIsArray( $result );
		$this->assertEquals( 4.0, $result['average'] );
		$this->assertEquals( 1, $result['count'] );
	}

	/**
	 * Test: Ungültige Bewertung wird abgelehnt (zu niedrig)
	 */
	public function test_rate_with_invalid_rating_too_low(): void {
		$result = $this->service->rate( 123, 0, 'overall' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_rating', $result->get_error_code() );
	}

	/**
	 * Test: Ungültige Bewertung wird abgelehnt (zu hoch)
	 */
	public function test_rate_with_invalid_rating_too_high(): void {
		$result = $this->service->rate( 123, 6, 'overall' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_rating', $result->get_error_code() );
	}

	/**
	 * Test: Ungültige Kategorie wird abgelehnt
	 */
	public function test_rate_with_invalid_category(): void {
		$result = $this->service->rate( 123, 4, 'invalid_category' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_category', $result->get_error_code() );
	}

	/**
	 * Test: Alle gültigen Kategorien werden akzeptiert
	 *
	 * @dataProvider validCategoriesProvider
	 */
	public function test_valid_categories_are_accepted( string $category ): void {
		$this->repository
			->shouldReceive( 'upsert' )
			->once()
			->andReturn( true );

		$this->repository
			->shouldReceive( 'getSummary' )
			->once()
			->andReturn( [ 'average' => 4.0, 'count' => 1 ] );

		$result = $this->service->rate( 123, 4, $category );

		$this->assertIsArray( $result );
	}

	/**
	 * Data Provider: Gültige Kategorien
	 */
	public static function validCategoriesProvider(): array {
		return [
			'overall' => [ 'overall' ],
			'skills' => [ 'skills' ],
			'culture_fit' => [ 'culture_fit' ],
			'experience' => [ 'experience' ],
		];
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
			->shouldReceive( 'getForApplication' )
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

		$this->repository
			->shouldReceive( 'deleteByUserAndCategory' )
			->once()
			->with( $application_id, 1, $category )
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
			->shouldReceive( 'getAverage' )
			->once()
			->with( $application_id )
			->andReturn( 4.25 );

		$result = $this->service->getAverage( $application_id );

		$this->assertEquals( 4.25, $result );
	}
}

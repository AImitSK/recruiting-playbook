<?php
/**
 * FormConfigRepository Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Repositories;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Repositories\FormConfigRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für das FormConfigRepository
 */
class FormConfigRepositoryTest extends TestCase {

	/**
	 * Repository under test
	 *
	 * @var FormConfigRepository
	 */
	private FormConfigRepository $repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = new FormConfigRepository();

		// WordPress-Funktionen mocken.
		Functions\when( 'current_time' )->justReturn( '2024-01-15 10:00:00' );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	}

	/**
	 * Test: getDraft lädt Draft-Konfiguration aus Datenbank
	 */
	public function test_get_draft_loads_from_database(): void {
		global $wpdb;

		$row = [
			'id'          => 1,
			'config_type' => 'draft',
			'config_data' => json_encode( [
				'version' => 1,
				'steps'   => [],
			] ),
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( $row );

		$result = $this->repository->getDraft();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'config_data', $result );
		$this->assertEquals( 'draft', $result['config_type'] );
	}

	/**
	 * Test: getDraft gibt null zurück wenn kein Draft existiert
	 */
	public function test_get_draft_returns_null_when_not_found(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( null );

		$result = $this->repository->getDraft();

		$this->assertNull( $result );
	}

	/**
	 * Test: getPublished lädt veröffentlichte Konfiguration
	 */
	public function test_get_published_loads_from_database(): void {
		global $wpdb;

		$row = [
			'id'          => 2,
			'config_type' => 'published',
			'config_data' => json_encode( [
				'version' => 2,
				'steps'   => [],
			] ),
			'version'     => 2,
			'created_by'  => 1,
			'created_at'  => '2024-01-10 09:00:00',
			'updated_at'  => '2024-01-10 10:00:00',
		];

		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( $row );

		$result = $this->repository->getPublished();

		$this->assertIsArray( $result );
		$this->assertEquals( 'published', $result['config_type'] );
		$this->assertEquals( 2, $result['version'] );
	}

	/**
	 * Test: saveDraft erstellt neuen Draft wenn keiner existiert
	 */
	public function test_save_draft_inserts_when_no_draft_exists(): void {
		global $wpdb;

		$config = [
			'version' => 1,
			'steps'   => [
				[ 'id' => 'step_1', 'title' => 'Test' ],
			],
		];

		// Kein existierender Draft.
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( null );

		// Insert ausführen.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->andReturn( 1 );

		$result = $this->repository->saveDraft( $config, 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: saveDraft aktualisiert existierenden Draft
	 */
	public function test_save_draft_updates_existing_draft(): void {
		global $wpdb;

		$config = [
			'version' => 1,
			'steps'   => [],
		];

		$existing_draft = [
			'id'          => 1,
			'config_type' => 'draft',
			'config_data' => json_encode( [ 'version' => 1, 'steps' => [] ] ),
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		// Existierender Draft gefunden.
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( $existing_draft );

		// Update ausführen.
		$wpdb->shouldReceive( 'update' )
			->once()
			->andReturn( 1 );

		$result = $this->repository->saveDraft( $config, 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: publish kopiert Draft in Published
	 */
	public function test_publish_copies_draft_to_published(): void {
		global $wpdb;

		$draft = [
			'id'          => 1,
			'config_type' => 'draft',
			'config_data' => json_encode( [ 'version' => 1, 'steps' => [] ] ),
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		// Draft laden, getPublishedVersion (kein Published), getPublished.
		$wpdb->shouldReceive( 'get_row' )
			->times( 3 )
			->andReturn( $draft, null, null );

		// Published einfügen.
		$wpdb->shouldReceive( 'insert' )
			->once()
			->andReturn( 1 );

		// Draft-Version aktualisieren.
		$wpdb->shouldReceive( 'update' )
			->once()
			->andReturn( 1 );

		$result = $this->repository->publish( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: publish gibt false zurück wenn kein Draft existiert
	 */
	public function test_publish_returns_false_when_no_draft(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( null );

		$result = $this->repository->publish( 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test: discardDraft setzt Draft auf Published zurück
	 */
	public function test_discard_draft_resets_to_published(): void {
		global $wpdb;

		$published = [
			'id'          => 2,
			'config_type' => 'published',
			'config_data' => json_encode( [ 'version' => 1, 'steps' => [] ] ),
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		// Published laden.
		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( $published );

		// Draft aktualisieren.
		$wpdb->shouldReceive( 'update' )
			->once()
			->andReturn( 1 );

		$result = $this->repository->discardDraft();

		$this->assertTrue( $result );
	}

	/**
	 * Test: hasUnpublishedChanges gibt true zurück wenn Draft anders als Published
	 */
	public function test_has_unpublished_changes_returns_true_when_different(): void {
		global $wpdb;

		$draft = [
			'id'          => 1,
			'config_type' => 'draft',
			'config_data' => json_encode( [ 'version' => 1, 'steps' => [ 'modified' ] ] ),
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		$published = [
			'id'          => 2,
			'config_type' => 'published',
			'config_data' => json_encode( [ 'version' => 1, 'steps' => [] ] ),
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		$wpdb->shouldReceive( 'get_row' )
			->twice()
			->andReturn( $draft, $published );

		$result = $this->repository->hasUnpublishedChanges();

		$this->assertTrue( $result );
	}

	/**
	 * Test: hasUnpublishedChanges gibt false zurück wenn Draft gleich Published
	 */
	public function test_has_unpublished_changes_returns_false_when_same(): void {
		global $wpdb;

		$same_config = json_encode( [ 'version' => 1, 'steps' => [] ] );

		$draft = [
			'id'          => 1,
			'config_type' => 'draft',
			'config_data' => $same_config,
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		$published = [
			'id'          => 2,
			'config_type' => 'published',
			'config_data' => $same_config,
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		$wpdb->shouldReceive( 'get_row' )
			->twice()
			->andReturn( $draft, $published );

		$result = $this->repository->hasUnpublishedChanges();

		$this->assertFalse( $result );
	}

	/**
	 * Test: hasUnpublishedChanges gibt false zurück wenn Draft existiert aber Published nicht
	 *
	 * Anmerkung: Wenn keine Published-Version existiert, gibt es technisch gesehen
	 * keine "unveröffentlichten Änderungen" - es wurde noch nie veröffentlicht.
	 */
	public function test_has_unpublished_changes_returns_false_when_no_published(): void {
		global $wpdb;

		$draft = [
			'id'          => 1,
			'config_type' => 'draft',
			'config_data' => json_encode( [ 'version' => 1, 'steps' => [] ] ),
			'version'     => 1,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		$wpdb->shouldReceive( 'get_row' )
			->twice()
			->andReturn( $draft, null );

		$result = $this->repository->hasUnpublishedChanges();

		$this->assertFalse( $result );
	}

	/**
	 * Test: getPublishedVersion gibt korrekte Version zurück
	 */
	public function test_get_published_version_returns_version(): void {
		global $wpdb;

		$published = [
			'id'          => 2,
			'config_type' => 'published',
			'config_data' => json_encode( [] ),
			'version'     => 5,
			'created_by'  => 1,
			'created_at'  => '2024-01-15 09:00:00',
			'updated_at'  => '2024-01-15 10:00:00',
		];

		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( $published );

		$result = $this->repository->getPublishedVersion();

		$this->assertEquals( 5, $result );
	}

	/**
	 * Test: getPublishedVersion gibt null zurück wenn keine Published existiert
	 */
	public function test_get_published_version_returns_null_when_not_found(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( null );

		$result = $this->repository->getPublishedVersion();

		$this->assertNull( $result );
	}
}

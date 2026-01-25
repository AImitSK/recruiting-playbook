<?php
/**
 * NoteService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\NoteService;
use RecruitingPlaybook\Repositories\NoteRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den NoteService
 */
class NoteServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var NoteService
	 */
	private NoteService $service;

	/**
	 * Mock Repository
	 *
	 * @var NoteRepository|Mockery\MockInterface
	 */
	private $repository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->repository = Mockery::mock( NoteRepository::class );
		$this->service = new NoteService( $this->repository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
	}

	/**
	 * Test: Notiz erstellen
	 */
	public function test_create_note(): void {
		$application_id = 123;
		$content = '<p>Test-Notiz Inhalt</p>';
		$is_private = false;

		$expected_note = [
			'id' => 1,
			'application_id' => $application_id,
			'user_id' => 1,
			'content' => $content,
			'is_private' => 0,
			'created_at' => '2025-01-25 12:00:00',
		];

		$this->repository
			->shouldReceive( 'create' )
			->once()
			->with( Mockery::on( function ( $data ) use ( $application_id, $content ) {
				return $data['application_id'] === $application_id
					&& $data['content'] === $content
					&& $data['is_private'] === 0
					&& $data['user_id'] === 1;
			} ) )
			->andReturn( 1 );

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $expected_note );

		$result = $this->service->create( $application_id, $content, $is_private );

		$this->assertIsArray( $result );
		$this->assertEquals( $content, $result['content'] );
		$this->assertEquals( 1, $result['user_id'] );
		$this->assertEquals( 0, $result['is_private'] );
	}

	/**
	 * Test: Private Notiz erstellen
	 */
	public function test_create_private_note(): void {
		$application_id = 123;
		$content = 'Private Notiz';

		$expected_note = [
			'id' => 1,
			'application_id' => $application_id,
			'user_id' => 1,
			'content' => $content,
			'is_private' => 1,
			'created_at' => '2025-01-25 12:00:00',
		];

		$this->repository
			->shouldReceive( 'create' )
			->once()
			->with( Mockery::on( function ( $data ) {
				return $data['is_private'] === 1;
			} ) )
			->andReturn( 1 );

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 1 )
			->andReturn( $expected_note );

		$result = $this->service->create( $application_id, $content, true );

		$this->assertEquals( 1, $result['is_private'] );
	}

	/**
	 * Test: Leerer Inhalt wird abgelehnt
	 */
	public function test_create_note_empty_content_fails(): void {
		$result = $this->service->create( 123, '', false );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'empty_content', $result->get_error_code() );
	}

	/**
	 * Test: Notizen für Bewerbung laden
	 */
	public function test_get_notes_for_application(): void {
		$application_id = 123;
		$notes = [
			[
				'id' => 1,
				'application_id' => $application_id,
				'user_id' => 1,
				'content' => 'Notiz 1',
				'is_private' => 0,
				'created_at' => '2025-01-25 12:00:00',
			],
			[
				'id' => 2,
				'application_id' => $application_id,
				'user_id' => 2,
				'content' => 'Notiz 2',
				'is_private' => 0,
				'created_at' => '2025-01-25 13:00:00',
			],
		];

		$this->repository
			->shouldReceive( 'getForApplication' )
			->once()
			->with( $application_id )
			->andReturn( $notes );

		$result = $this->service->getForApplication( $application_id );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
	}

	/**
	 * Test: Eigene Notiz bearbeiten
	 */
	public function test_update_own_note(): void {
		$note_id = 1;
		$new_content = 'Aktualisierter Inhalt';

		$existing_note = [
			'id' => $note_id,
			'user_id' => 1, // Gleicher User wie get_current_user_id().
			'content' => 'Original',
			'is_private' => 0,
		];

		$updated_note = array_merge( $existing_note, [ 'content' => $new_content ] );

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( $note_id )
			->andReturn( $existing_note );

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( $note_id, Mockery::on( function ( $data ) use ( $new_content ) {
				return $data['content'] === $new_content;
			} ) )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( $note_id )
			->andReturn( $updated_note );

		$result = $this->service->update( $note_id, $new_content );

		$this->assertIsArray( $result );
		$this->assertEquals( $new_content, $result['content'] );
	}

	/**
	 * Test: Fremde Notiz bearbeiten wird abgelehnt (ohne Admin-Rechte)
	 */
	public function test_cannot_update_others_note_without_permission(): void {
		$note_id = 1;

		$existing_note = [
			'id' => $note_id,
			'user_id' => 999, // Anderer User.
			'content' => 'Original',
		];

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( $note_id )
			->andReturn( $existing_note );

		Functions\when( 'current_user_can' )->justReturn( false );

		$result = $this->service->update( $note_id, 'Gehackt!' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'forbidden', $result->get_error_code() );
	}

	/**
	 * Test: Notiz löschen (Soft Delete)
	 */
	public function test_soft_delete_note(): void {
		$note_id = 1;

		$existing_note = [
			'id' => $note_id,
			'user_id' => 1,
			'content' => 'Test',
		];

		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( $note_id )
			->andReturn( $existing_note );

		$this->repository
			->shouldReceive( 'softDelete' )
			->once()
			->with( $note_id )
			->andReturn( true );

		Functions\when( 'current_user_can' )->justReturn( true );

		$result = $this->service->delete( $note_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test: Nicht existierende Notiz kann nicht gelöscht werden
	 */
	public function test_delete_non_existent_note_fails(): void {
		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 999 )
			->andReturn( null );

		$result = $this->service->delete( 999 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
	}
}

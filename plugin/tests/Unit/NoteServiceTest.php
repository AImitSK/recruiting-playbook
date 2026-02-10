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

		// Globales $wpdb Mock (für Activity-Logging).
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

		$this->repository = Mockery::mock( NoteRepository::class );
		$this->service = new NoteService( $this->repository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
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
		Functions\when( 'wp_trim_words' )->alias( function( $text, $num_words = 55 ) {
			return implode( ' ', array_slice( explode( ' ', $text ), 0, $num_words ) );
		} );
		Functions\when( 'wp_strip_all_tags' )->alias( function( $string ) {
			return strip_tags( $string );
		} );
	}

	/**
	 * Test: Create-Methode existiert und hat korrekte Signatur
	 * Note: Die vollständige create()-Methode benötigt ApplicationService-Integration,
	 * was komplexe DB-Mocks erfordert. Hier testen wir nur die Signatur.
	 */
	public function test_create_method_signature(): void {
		$reflection = new \ReflectionMethod( $this->service, 'create' );
		$params = $reflection->getParameters();

		$this->assertCount( 3, $params );
		$this->assertEquals( 'application_id', $params[0]->getName() );
		$this->assertEquals( 'content', $params[1]->getName() );
		$this->assertEquals( 'is_private', $params[2]->getName() );
	}

	/**
	 * Test: countForApplication nutzt Repository
	 */
	public function test_count_for_application(): void {
		$application_id = 123;

		$this->repository
			->shouldReceive( 'countByApplication' )
			->once()
			->with( $application_id )
			->andReturn( 5 );

		$result = $this->service->countForApplication( $application_id );

		$this->assertEquals( 5, $result );
	}

	/**
	 * Test: get-Methode gibt WP_Error für nicht existierende Notiz
	 */
	public function test_get_non_existent_note_fails(): void {
		$this->repository
			->shouldReceive( 'find' )
			->once()
			->with( 999 )
			->andReturn( null );

		$result = $this->service->get( 999 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'not_found', $result->get_error_code() );
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
			->shouldReceive( 'findByApplication' )
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

<?php
/**
 * EmailQueueService Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Services\EmailQueueService;
use RecruitingPlaybook\Repositories\EmailLogRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für den EmailQueueService
 */
class EmailQueueServiceTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var EmailQueueService
	 */
	private EmailQueueService $service;

	/**
	 * Mock Repository
	 *
	 * @var EmailLogRepository|Mockery\MockInterface
	 */
	private $logRepository;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->logRepository = Mockery::mock( EmailLogRepository::class );
		$this->service = new EmailQueueService( $this->logRepository );

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );

		// Action Scheduler Funktionen mocken (nicht verfügbar im Test).
		Functions\when( 'as_enqueue_async_action' )->justReturn( 1 );
		Functions\when( 'as_schedule_single_action' )->justReturn( 1 );
		Functions\when( 'as_unschedule_all_actions' )->justReturn( null );
		Functions\when( 'as_has_scheduled_action' )->justReturn( false );
		Functions\when( 'as_schedule_recurring_action' )->justReturn( 1 );
	}

	/**
	 * Test: E-Mail zur Queue hinzufügen
	 */
	public function test_enqueue_email(): void {
		$email_data = [
			'application_id'  => 123,
			'candidate_id'    => 456,
			'template_id'     => 1,
			'recipient_email' => 'test@example.com',
			'recipient_name'  => 'Max Mustermann',
			'sender_email'    => 'hr@company.de',
			'sender_name'     => 'HR Team',
			'subject'         => 'Ihre Bewerbung',
			'body_html'       => '<p>Hallo Max,</p>',
			'body_text'       => 'Hallo Max,',
		];

		$this->logRepository
			->shouldReceive( 'create' )
			->once()
			->with( Mockery::on( function( $data ) {
				return $data['recipient_email'] === 'test@example.com'
					&& $data['status'] === 'pending';
			} ) )
			->andReturn( 1 );

		$result = $this->service->enqueue( $email_data );

		$this->assertEquals( 1, $result );
	}

	/**
	 * Test: E-Mail für späteren Versand planen
	 */
	public function test_schedule_email(): void {
		$email_data = [
			'recipient_email' => 'test@example.com',
			'sender_email'    => 'hr@company.de',
			'subject'         => 'Ihre Bewerbung',
			'body_html'       => '<p>Hallo,</p>',
		];

		$scheduled_at = '2025-02-01 10:00:00';

		$this->logRepository
			->shouldReceive( 'create' )
			->once()
			->with( Mockery::on( function( $data ) use ( $scheduled_at ) {
				return $data['scheduled_at'] === $scheduled_at;
			} ) )
			->andReturn( 1 );

		$result = $this->service->schedule( $email_data, $scheduled_at );

		$this->assertEquals( 1, $result );
	}

	/**
	 * Test: Geplante E-Mail stornieren
	 */
	public function test_cancel_scheduled_email(): void {
		$log = [
			'id'     => 1,
			'status' => 'pending',
		];

		$this->logRepository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $log );

		$this->logRepository
			->shouldReceive( 'updateStatus' )
			->with( 1, 'cancelled' )
			->andReturn( true );

		$result = $this->service->cancel( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test: Nicht-pending E-Mail kann nicht storniert werden
	 */
	public function test_cannot_cancel_non_pending_email(): void {
		$log = [
			'id'     => 1,
			'status' => 'sent', // Bereits gesendet.
		];

		$this->logRepository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $log );

		$result = $this->service->cancel( 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test: E-Mail erneut senden
	 */
	public function test_resend_email(): void {
		$original_log = [
			'id'              => 1,
			'application_id'  => 123,
			'candidate_id'    => 456,
			'template_id'     => 1,
			'recipient_email' => 'test@example.com',
			'recipient_name'  => 'Max Mustermann',
			'sender_email'    => 'hr@company.de',
			'sender_name'     => 'HR Team',
			'subject'         => 'Ihre Bewerbung',
			'body_html'       => '<p>Hallo,</p>',
			'body_text'       => 'Hallo,',
			'metadata'        => [],
		];

		$this->logRepository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $original_log );

		$this->logRepository
			->shouldReceive( 'create' )
			->once()
			->with( Mockery::on( function( $data ) {
				return isset( $data['metadata']['resent_from'] )
					&& $data['metadata']['resent_from'] === 1;
			} ) )
			->andReturn( 2 );

		$result = $this->service->resend( 1 );

		$this->assertEquals( 2, $result );
	}

	/**
	 * Test: Nicht existierende E-Mail kann nicht erneut gesendet werden
	 */
	public function test_resend_non_existent_returns_false(): void {
		$this->logRepository
			->shouldReceive( 'find' )
			->with( 999 )
			->andReturn( null );

		$result = $this->service->resend( 999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test: Einzelne E-Mail verarbeiten - erfolgreich
	 */
	public function test_process_single_email_success(): void {
		$log = [
			'id'              => 1,
			'status'          => 'pending',
			'recipient_email' => 'test@example.com',
			'sender_email'    => 'hr@company.de',
			'sender_name'     => 'HR Team',
			'subject'         => 'Test',
			'body_html'       => '<p>Test</p>',
			'metadata'        => [],
		];

		$this->logRepository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $log );

		$this->logRepository
			->shouldReceive( 'updateStatus' )
			->with( 1, 'queued' )
			->andReturn( true );

		// Mock wp_mail.
		Functions\when( 'wp_mail' )->justReturn( true );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'do_action' )->justReturn( null );

		$this->logRepository
			->shouldReceive( 'updateStatus' )
			->with( 1, 'sent' )
			->andReturn( true );

		// Service aufrufen.
		$this->service->processSingleEmail( 1 );

		// Keine Assertion nötig - Test prüft dass keine Exception geworfen wird.
		$this->assertTrue( true );
	}

	/**
	 * Test: Einzelne E-Mail verarbeiten - Retry bei Fehler
	 */
	public function test_process_single_email_retry_on_failure(): void {
		$log = [
			'id'              => 1,
			'status'          => 'pending',
			'recipient_email' => 'test@example.com',
			'sender_email'    => 'hr@company.de',
			'sender_name'     => 'HR Team',
			'subject'         => 'Test',
			'body_html'       => '<p>Test</p>',
			'metadata'        => [ 'retry_count' => 0 ],
		];

		$this->logRepository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $log );

		$this->logRepository
			->shouldReceive( 'updateStatus' )
			->with( 1, 'queued' )
			->andReturn( true );

		// Mock wp_mail - Fehler simulieren.
		Functions\when( 'wp_mail' )->justReturn( false );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'do_action' )->justReturn( null );

		// Retry sollte eingeplant werden.
		$this->logRepository
			->shouldReceive( 'update' )
			->once()
			->with( 1, Mockery::on( function( $data ) {
				return $data['status'] === 'pending'
					&& $data['metadata']['retry_count'] === 1;
			} ) )
			->andReturn( true );

		$this->service->processSingleEmail( 1 );

		$this->assertTrue( true );
	}

	/**
	 * Test: E-Mail versagt nach max. Versuchen
	 */
	public function test_process_single_email_fails_after_max_retries(): void {
		$log = [
			'id'              => 1,
			'status'          => 'pending',
			'recipient_email' => 'test@example.com',
			'sender_email'    => 'hr@company.de',
			'sender_name'     => 'HR Team',
			'subject'         => 'Test',
			'body_html'       => '<p>Test</p>',
			'metadata'        => [ 'retry_count' => 3 ], // Max erreicht.
		];

		$this->logRepository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $log );

		$this->logRepository
			->shouldReceive( 'updateStatus' )
			->with( 1, 'queued' )
			->andReturn( true );

		// Mock wp_mail - Fehler simulieren.
		Functions\when( 'wp_mail' )->justReturn( false );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'do_action' )->justReturn( null );

		// Sollte als failed markiert werden.
		$this->logRepository
			->shouldReceive( 'updateStatus' )
			->once()
			->with( 1, 'failed', Mockery::any() )
			->andReturn( true );

		$this->service->processSingleEmail( 1 );

		$this->assertTrue( true );
	}

	/**
	 * Test: Bereits gesendete E-Mail wird nicht erneut verarbeitet
	 */
	public function test_skip_already_sent_email(): void {
		$log = [
			'id'     => 1,
			'status' => 'sent', // Bereits gesendet.
		];

		$this->logRepository
			->shouldReceive( 'find' )
			->with( 1 )
			->andReturn( $log );

		// wp_mail sollte NICHT aufgerufen werden.
		Functions\when( 'wp_mail' )->alias( function() {
			throw new \Exception( 'wp_mail should not be called' );
		} );

		// Keine Exception = Test bestanden.
		$this->service->processSingleEmail( 1 );

		$this->assertTrue( true );
	}

	/**
	 * Test: Queue verarbeiten
	 */
	public function test_process_queue(): void {
		$pending = [
			[ 'id' => 1 ],
			[ 'id' => 2 ],
			[ 'id' => 3 ],
		];

		$this->logRepository
			->shouldReceive( 'getPendingForQueue' )
			->with( 50 ) // BATCH_SIZE.
			->andReturn( $pending );

		// Für jede E-Mail sollte scheduleEmail aufgerufen werden.
		// Da scheduleEmail intern as_enqueue_async_action aufruft, wird das gemockt.

		$this->service->processQueue();

		$this->assertTrue( true );
	}

	/**
	 * Test: Queue-Statistiken abrufen
	 */
	public function test_get_queue_stats(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_row' )
			->once()
			->andReturn( [
				'total'      => 100,
				'pending'    => 10,
				'processing' => 5,
				'sent'       => 80,
				'failed'     => 5,
			] );

		$stats = $this->service->getQueueStats();

		$this->assertIsArray( $stats );
		$this->assertEquals( 100, $stats['total'] );
		$this->assertEquals( 10, $stats['pending'] );
		$this->assertEquals( 80, $stats['sent'] );
	}

	/**
	 * Test: Action Scheduler Verfügbarkeit prüfen
	 */
	public function test_action_scheduler_available(): void {
		// as_enqueue_async_action ist gemockt und existiert.
		$result = $this->service->isActionSchedulerAvailable();

		$this->assertTrue( $result );
	}

	/**
	 * Test: Geplante E-Mails abrufen
	 */
	public function test_get_scheduled(): void {
		$scheduled = [
			[ 'id' => 1, 'scheduled_at' => '2025-02-01 10:00:00' ],
			[ 'id' => 2, 'scheduled_at' => '2025-02-02 10:00:00' ],
		];

		$this->logRepository
			->shouldReceive( 'getScheduled' )
			->with( [] )
			->andReturn( $scheduled );

		$result = $this->service->getScheduled();

		$this->assertCount( 2, $result );
	}

	/**
	 * Test: Enqueue gibt false zurück bei Repository-Fehler
	 */
	public function test_enqueue_returns_false_on_error(): void {
		$email_data = [
			'recipient_email' => 'test@example.com',
			'sender_email'    => 'hr@company.de',
			'subject'         => 'Test',
			'body_html'       => '<p>Test</p>',
		];

		$this->logRepository
			->shouldReceive( 'create' )
			->andReturn( false );

		$result = $this->service->enqueue( $email_data );

		$this->assertFalse( $result );
	}

	/**
	 * Test: Nicht existierende E-Mail stornieren gibt false
	 */
	public function test_cancel_non_existent_returns_false(): void {
		$this->logRepository
			->shouldReceive( 'find' )
			->with( 999 )
			->andReturn( null );

		$result = $this->service->cancel( 999 );

		$this->assertFalse( $result );
	}
}

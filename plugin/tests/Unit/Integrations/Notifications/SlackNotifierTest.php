<?php
/**
 * SlackNotifier Tests
 *
 * @package RecruitingPlaybook\Tests
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Tests\Unit\Integrations\Notifications;

use RecruitingPlaybook\Tests\TestCase;
use RecruitingPlaybook\Integrations\Notifications\SlackNotifier;
use RecruitingPlaybook\Services\ApplicationService;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests für SlackNotifier
 */
class SlackNotifierTest extends TestCase {

	/**
	 * Service under test
	 *
	 * @var SlackNotifier
	 */
	private SlackNotifier $notifier;

	/**
	 * Test settings
	 *
	 * @var array<string, mixed>
	 */
	private array $settings;

	/**
	 * Setup vor jedem Test
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->settings = [
			'slack_enabled'               => true,
			'slack_webhook_url'           => 'https://hooks.slack.com/services/T{WORKSPACE}/B{CHANNEL}/{TOKEN}',
			'slack_event_new_application' => true,
			'slack_event_status_changed'  => true,
			'slack_event_job_published'   => false,
		];

		$this->notifier = new SlackNotifier( $this->settings );

		// Standard WordPress-Funktionen mocken.
		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'admin_url' )->returnArg();
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/job/123' );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'delete_transient' )->justReturn( true );
	}

	/**
	 * Test: send() mit gültiger Webhook-URL und erfolgreicher Response
	 */
	public function test_send_success(): void {
		// Mock wp_remote_post für erfolgreiche Response.
		Functions\expect( 'wp_remote_post' )
			->once()
			->with(
				$this->settings['slack_webhook_url'],
				Mockery::on( function ( $args ) {
					return isset( $args['headers']['Content-Type'] )
						&& $args['headers']['Content-Type'] === 'application/json'
						&& isset( $args['timeout'] )
						&& $args['timeout'] === 15;
				} )
			)
			->andReturn( [
				'response' => [ 'code' => 200 ],
				'body'     => 'ok',
			] );

		Functions\expect( 'is_wp_error' )->once()->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 200 );

		$data = [
			'event'          => 'new_application',
			'candidate_name' => 'Max Mustermann',
			'job_title'      => 'Software Developer',
			'source'         => 'Website',
			'email'          => 'max@example.com',
			'phone'          => '+49123456789',
			'link'           => 'https://example.com/admin',
		];

		$result = $this->notifier->send( $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test: send() mit ungültiger Webhook-URL
	 */
	public function test_send_invalid_webhook_url(): void {
		$notifier = new SlackNotifier( [
			'slack_webhook_url' => 'https://evil.com/webhook',
		] );

		$data = [
			'event' => 'new_application',
		];

		$result = $notifier->send( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test: send() mit leerer Webhook-URL
	 */
	public function test_send_empty_webhook_url(): void {
		$notifier = new SlackNotifier( [
			'slack_webhook_url' => '',
		] );

		$data = [
			'event' => 'new_application',
		];

		$result = $notifier->send( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test: send() mit Rate Limit aktiv
	 */
	public function test_send_rate_limited(): void {
		// Rate Limit Transient existiert (vor 0 Sekunden gesetzt).
		Functions\expect( 'get_transient' )
			->once()
			->andReturn( time() );

		$data = [
			'event' => 'new_application',
		];

		$result = $this->notifier->send( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test: send() mit HTTP 429 (Rate Limit) Response - sollte Retry Queue nutzen
	 */
	public function test_send_http_429_adds_to_retry_queue(): void {
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( [
				'response' => [ 'code' => 429 ],
				'body'     => 'rate limited',
			] );

		Functions\expect( 'is_wp_error' )->once()->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 429 );

		// Retry Queue sollte erweitert werden.
		Functions\expect( 'get_transient' )
			->with( 'rp_slack_retry_queue' )
			->andReturn( [] );

		Functions\expect( 'set_transient' )
			->once()
			->with(
				'rp_slack_retry_queue',
				Mockery::on( function ( $queue ) {
					return is_array( $queue ) && count( $queue ) === 1;
				} ),
				3600
			)
			->andReturn( true );

		$data = [
			'event' => 'new_application',
		];

		$result = $this->notifier->send( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test: send() mit HTTP 400 (Bad Request) - permanenter Fehler, kein Retry
	 */
	public function test_send_http_400_no_retry(): void {
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( [
				'response' => [ 'code' => 400 ],
				'body'     => 'bad request',
			] );

		Functions\expect( 'is_wp_error' )->once()->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 400 );

		// get_transient sollte NICHT für Retry Queue aufgerufen werden.
		Functions\expect( 'get_transient' )
			->with( 'rp_slack_retry_queue' )
			->never();

		$data = [
			'event' => 'new_application',
		];

		$result = $this->notifier->send( $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test: onNewApplication() ruft send() mit korrekten Daten auf
	 */
	public function test_onNewApplication_calls_send(): void {
		// Mock ApplicationService.
		$application = (object) [
			'id'         => 123,
			'job_id'     => 456,
			'first_name' => 'Max',
			'last_name'  => 'Mustermann',
			'email'      => 'max@example.com',
			'phone'      => '+49123456789',
			'source'     => 'LinkedIn',
		];

		$job = (object) [
			'post_title' => 'Software Developer',
		];

		// Mock get_post für Job.
		Functions\expect( 'get_post' )
			->once()
			->with( 456 )
			->andReturn( $job );

		// Mock wp_remote_post (send() wird aufgerufen).
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( [
				'response' => [ 'code' => 200 ],
			] );

		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );

		// Mock ApplicationService::findById().
		$appServiceMock = Mockery::mock( 'overload:' . ApplicationService::class );
		$appServiceMock->shouldReceive( 'findById' )
			->once()
			->with( 123 )
			->andReturn( $application );

		// Event enabled check.
		$notifier = new SlackNotifier( [
			'slack_webhook_url'           => 'https://hooks.slack.com/services/T{WS}/B{CH}/{TKN}',
			'slack_event_new_application' => true,
		] );

		$notifier->onNewApplication( 123 );
	}

	/**
	 * Test: onNewApplication() macht nichts wenn Event deaktiviert
	 */
	public function test_onNewApplication_disabled_event(): void {
		$notifier = new SlackNotifier( [
			'slack_webhook_url'           => 'https://hooks.slack.com/services/T{WS}/B{CH}/{TKN}',
			'slack_event_new_application' => false, // Disabled!
		] );

		// send() sollte NICHT aufgerufen werden.
		Functions\expect( 'wp_remote_post' )->never();

		$notifier->onNewApplication( 123 );

		// Keine Exception = Test passed.
		$this->assertTrue( true );
	}

	/**
	 * Test: onStatusChanged() sendet korrekte Nachricht
	 */
	public function test_onStatusChanged_sends_notification(): void {
		// Mock Application.
		$application = (object) [
			'id'         => 123,
			'job_id'     => 456,
			'first_name' => 'Maria',
			'last_name'  => 'Weber',
			'email'      => 'maria@example.com',
		];

		$job = (object) [
			'post_title' => 'Pflegefachkraft',
		];

		Functions\expect( 'get_post' )->once()->andReturn( $job );
		Functions\expect( 'wp_remote_post' )->once()->andReturn( [ 'response' => [ 'code' => 200 ] ] );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );

		$appServiceMock = Mockery::mock( 'overload:' . ApplicationService::class );
		$appServiceMock->shouldReceive( 'findById' )->once()->andReturn( $application );

		$notifier = new SlackNotifier( [
			'slack_webhook_url'          => 'https://hooks.slack.com/services/T{WS}/B{CH}/{TKN}',
			'slack_event_status_changed' => true,
		] );

		$notifier->onStatusChanged( 123, 'new', 'screening' );

		// send() wurde aufgerufen = Success.
		$this->assertTrue( true );
	}

	/**
	 * Test: onJobPublished() sendet Nachricht
	 */
	public function test_onJobPublished_sends_notification(): void {
		$job = (object) [
			'ID'        => 789,
			'post_type' => 'job_listing',
			'post_title' => 'Backend Developer',
		];

		Functions\expect( 'get_post' )->once()->andReturn( $job );

		// Mock Taxonomien.
		Functions\expect( 'wp_get_post_terms' )
			->twice()
			->andReturn( [
				(object) [ 'name' => 'Berlin' ],
			] );

		Functions\expect( 'wp_remote_post' )->once()->andReturn( [ 'response' => [ 'code' => 200 ] ] );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );

		$notifier = new SlackNotifier( [
			'slack_webhook_url'         => 'https://hooks.slack.com/services/T{WS}/B{CH}/{TKN}',
			'slack_event_job_published' => true,
		] );

		$notifier->onJobPublished( 789 );

		$this->assertTrue( true );
	}

	/**
	 * Test: processRetryQueue() verarbeitet ausstehende Items
	 */
	public function test_processRetryQueue_processes_items(): void {
		$queue = [
			[
				'payload'    => [
					'text'   => 'Test',
					'blocks' => [],
				],
				'attempt'    => 1,
				'next_retry' => time() - 10, // In der Vergangenheit = ready.
			],
		];

		Functions\expect( 'get_transient' )
			->with( 'rp_slack_retry_queue' )
			->andReturn( $queue );

		Functions\expect( 'wp_remote_post' )->once()->andReturn( [ 'response' => [ 'code' => 200 ] ] );
		Functions\expect( 'is_wp_error' )->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->andReturn( 200 );

		// Queue sollte nach erfolgreichem Retry leer sein.
		Functions\expect( 'delete_transient' )
			->once()
			->with( 'rp_slack_retry_queue' )
			->andReturn( true );

		$this->notifier->processRetryQueue();

		$this->assertTrue( true );
	}

	/**
	 * Test: processRetryQueue() überspringt Items die noch nicht ready sind
	 */
	public function test_processRetryQueue_skips_not_ready_items(): void {
		$queue = [
			[
				'payload'    => [ 'text' => 'Test' ],
				'attempt'    => 1,
				'next_retry' => time() + 3600, // In der Zukunft.
			],
		];

		Functions\expect( 'get_transient' )
			->with( 'rp_slack_retry_queue' )
			->andReturn( $queue );

		// wp_remote_post sollte NICHT aufgerufen werden.
		Functions\expect( 'wp_remote_post' )->never();

		// Queue sollte mit dem Item wieder gespeichert werden.
		Functions\expect( 'set_transient' )
			->once()
			->with(
				'rp_slack_retry_queue',
				Mockery::on( function ( $remaining ) {
					return count( $remaining ) === 1;
				} ),
				3600
			)
			->andReturn( true );

		$this->notifier->processRetryQueue();

		$this->assertTrue( true );
	}

	/**
	 * Test: processRetryQueue() stoppt nach 3 Versuchen
	 */
	public function test_processRetryQueue_stops_after_max_retries(): void {
		$queue = [
			[
				'payload'    => [ 'text' => 'Test' ],
				'attempt'    => 3, // Max erreicht.
				'next_retry' => time() - 10,
			],
		];

		Functions\expect( 'get_transient' )
			->with( 'rp_slack_retry_queue' )
			->andReturn( $queue );

		// send() sollte NICHT aufgerufen werden.
		Functions\expect( 'wp_remote_post' )->never();

		// Queue sollte leer sein (Item verworfen).
		Functions\expect( 'delete_transient' )
			->once()
			->with( 'rp_slack_retry_queue' )
			->andReturn( true );

		$this->notifier->processRetryQueue();

		$this->assertTrue( true );
	}
}

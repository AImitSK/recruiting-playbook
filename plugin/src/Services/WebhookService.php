<?php
/**
 * Webhook-Dispatch und Delivery Service
 *
 * Verwaltet asynchrone Webhook-Zustellung mit HMAC-Signatur,
 * Retry-Logik (exponential Backoff) und Auto-Deaktivierung.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Webhook-Dispatch und Delivery Service
 */
class WebhookService {

	/**
	 * Maximale Anzahl Retry-Versuche
	 *
	 * @var int
	 */
	private const MAX_RETRIES = 3;

	/**
	 * Retry-Delays in Sekunden (exponential Backoff)
	 *
	 * @var array<int, int>
	 */
	private const RETRY_DELAYS = [
		1 => 60,    // 1 Minute.
		2 => 300,   // 5 Minuten.
		3 => 1800,  // 30 Minuten.
	];

	/**
	 * HTTP-Timeout in Sekunden
	 *
	 * @var int
	 */
	private const HTTP_TIMEOUT = 15;

	/**
	 * Event an alle aktiven Webhooks dispatchen
	 *
	 * @param string $event Event-Name (z.B. 'application.received').
	 * @param array  $data  Event-Daten.
	 */
	public function dispatch( string $event, array $data ): void {
		$webhooks = $this->getActiveWebhooksForEvent( $event );

		if ( empty( $webhooks ) ) {
			return;
		}

		foreach ( $webhooks as $webhook ) {
			$payload     = $this->buildPayload( $event, $data, 0 );
			$delivery_id = $this->createDelivery( (int) $webhook->id, $event, $payload );

			if ( $delivery_id && function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action( time(), 'rp_deliver_webhook', [ $delivery_id ], 'recruiting-playbook' );
			}
		}
	}

	/**
	 * Webhook-Delivery ausführen
	 *
	 * @param int $delivery_id Delivery-ID.
	 */
	public function deliver( int $delivery_id ): void {
		global $wpdb;

		$tables   = Schema::getTables();
		$del_tbl  = $tables['webhook_deliveries'];
		$hook_tbl = $tables['webhooks'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Delivery laden.
		$delivery = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$del_tbl} WHERE id = %d",
				$delivery_id
			)
		);

		if ( ! $delivery || 'pending' !== $delivery->status ) {
			return;
		}

		// Webhook laden.
		$webhook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$hook_tbl} WHERE id = %d",
				$delivery->webhook_id
			)
		);
		// phpcs:enable

		if ( ! $webhook ) {
			return;
		}

		// Payload mit korrekter delivery_id aufbauen.
		$payload_data = json_decode( $delivery->request_body, true );
		if ( ! is_array( $payload_data ) ) {
			$payload_data = [];
		}
		$payload_data['delivery_id'] = 'whd_' . $delivery_id;

		$payload_json = wp_json_encode( $payload_data, JSON_UNESCAPED_UNICODE );
		$signature    = 'sha256=' . hash_hmac( 'sha256', $payload_json, $webhook->secret );

		$headers = [
			'Content-Type'           => 'application/json',
			'X-Recruiting-Event'     => $delivery->event,
			'X-Recruiting-Delivery'  => 'whd_' . $delivery_id,
			'X-Recruiting-Signature' => $signature,
		];

		// Request senden.
		$start_time = microtime( true );

		$response = wp_remote_post(
			$delivery->request_url,
			[
				'body'      => $payload_json,
				'headers'   => $headers,
				'timeout'   => self::HTTP_TIMEOUT,
				'sslverify' => true,
			]
		);

		$elapsed_ms = (int) round( ( microtime( true ) - $start_time ) * 1000 );
		$now        = current_time( 'mysql' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( is_wp_error( $response ) ) {
			// Fehler.
			$wpdb->update(
				$del_tbl,
				[
					'request_headers'  => wp_json_encode( $headers ),
					'request_body'     => $payload_json,
					'status'           => 'failed',
					'error_message'    => $response->get_error_message(),
					'response_time_ms' => $elapsed_ms,
				],
				[ 'id' => $delivery_id ],
				[ '%s', '%s', '%s', '%s', '%d' ],
				[ '%d' ]
			);
			// phpcs:enable

			$this->handleFailure( (int) $webhook->id, $delivery_id, (int) $delivery->retry_count );
			return;
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_body    = wp_remote_retrieve_body( $response );
		$response_headers = wp_remote_retrieve_headers( $response );

		// Erfolg = 2xx Status.
		$is_success = $response_code >= 200 && $response_code < 300;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$del_tbl,
			[
				'request_headers'  => wp_json_encode( $headers ),
				'request_body'     => $payload_json,
				'response_code'    => $response_code,
				'response_headers' => wp_json_encode( $response_headers->getAll() ),
				'response_body'    => mb_substr( $response_body, 0, 65535 ),
				'response_time_ms' => $elapsed_ms,
				'status'           => $is_success ? 'success' : 'failed',
				'error_message'    => $is_success ? null : "HTTP {$response_code}",
			],
			[ 'id' => $delivery_id ],
			[ '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' ],
			[ '%d' ]
		);
		// phpcs:enable

		if ( $is_success ) {
			$this->handleSuccess( (int) $webhook->id );
		} else {
			$this->handleFailure( (int) $webhook->id, $delivery_id, (int) $delivery->retry_count );
		}
	}

	/**
	 * Erfolg am Webhook verbuchen
	 *
	 * @param int $webhook_id Webhook-ID.
	 */
	private function handleSuccess( int $webhook_id ): void {
		global $wpdb;

		$table = Schema::getTables()['webhooks'];
		$now   = current_time( 'mysql' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET success_count = success_count + 1,
					last_triggered_at = %s,
					last_success_at = %s
				WHERE id = %d",
				$now,
				$now,
				$webhook_id
			)
		);
		// phpcs:enable
	}

	/**
	 * Fehler am Webhook verbuchen und Retry planen
	 *
	 * @param int $webhook_id  Webhook-ID.
	 * @param int $delivery_id Delivery-ID.
	 * @param int $retry_count Bisherige Retry-Anzahl.
	 */
	private function handleFailure( int $webhook_id, int $delivery_id, int $retry_count ): void {
		global $wpdb;

		$table = Schema::getTables()['webhooks'];
		$now   = current_time( 'mysql' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET failure_count = failure_count + 1,
					last_triggered_at = %s,
					last_failure_at = %s
				WHERE id = %d",
				$now,
				$now,
				$webhook_id
			)
		);
		// phpcs:enable

		$this->scheduleRetry( $delivery_id, $retry_count );
	}

	/**
	 * Retry mit exponential Backoff planen
	 *
	 * Nach MAX_RETRIES Fehlversuchen wird der Webhook deaktiviert.
	 *
	 * @param int $delivery_id Delivery-ID.
	 * @param int $retry_count Bisherige Retry-Anzahl.
	 */
	private function scheduleRetry( int $delivery_id, int $retry_count ): void {
		global $wpdb;

		$next_retry = $retry_count + 1;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $next_retry > self::MAX_RETRIES ) {
			// Webhook deaktivieren nach zu vielen Fehlversuchen.
			$del_tbl = Schema::getTables()['webhook_deliveries'];

			$delivery = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT webhook_id FROM {$del_tbl} WHERE id = %d",
					$delivery_id
				)
			);

			if ( $delivery ) {
				$hook_tbl = Schema::getTables()['webhooks'];

				$wpdb->update(
					$hook_tbl,
					[ 'is_active' => 0 ],
					[ 'id' => $delivery->webhook_id ],
					[ '%d' ],
					[ '%d' ]
				);
			}

			return;
		}

		$delay         = self::RETRY_DELAYS[ $next_retry ] ?? 1800;
		$next_time     = time() + $delay;
		$del_tbl       = Schema::getTables()['webhook_deliveries'];
		$next_retry_at = gmdate( 'Y-m-d H:i:s', $next_time );

		// Delivery für Retry aktualisieren.
		$wpdb->update(
			$del_tbl,
			[
				'status'        => 'pending',
				'retry_count'   => $next_retry,
				'next_retry_at' => $next_retry_at,
			],
			[ 'id' => $delivery_id ],
			[ '%s', '%d', '%s' ],
			[ '%d' ]
		);
		// phpcs:enable

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( $next_time, 'rp_deliver_webhook', [ $delivery_id ], 'recruiting-playbook' );
		}
	}

	/**
	 * Payload für Webhook-Delivery aufbauen
	 *
	 * @param string $event       Event-Name.
	 * @param array  $data        Event-Daten.
	 * @param int    $delivery_id Delivery-ID (0 wenn noch nicht bekannt).
	 * @return array
	 */
	public function buildPayload( string $event, array $data, int $delivery_id = 0 ): array {
		return [
			'event'       => $event,
			'timestamp'   => gmdate( 'c' ),
			'delivery_id' => 'whd_' . $delivery_id,
			'data'        => $data,
		];
	}

	/**
	 * Aktive Webhooks für ein bestimmtes Event laden
	 *
	 * @param string $event Event-Name.
	 * @return array
	 */
	public function getActiveWebhooksForEvent( string $event ): array {
		global $wpdb;

		$table = Schema::getTables()['webhooks'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$webhooks = $wpdb->get_results(
			"SELECT * FROM {$table} WHERE is_active = 1"
		);
		// phpcs:enable

		if ( empty( $webhooks ) ) {
			return [];
		}

		// PHP-Filter: Nur Webhooks die dieses Event abonniert haben.
		return array_filter(
			$webhooks,
			function ( $webhook ) use ( $event ) {
				$events = json_decode( $webhook->events, true );
				return is_array( $events ) && in_array( $event, $events, true );
			}
		);
	}

	/**
	 * Delivery-Record erstellen
	 *
	 * @param int    $webhook_id Webhook-ID.
	 * @param string $event      Event-Name.
	 * @param array  $payload    Payload-Daten.
	 * @return int Delivery-ID (0 bei Fehler).
	 */
	public function createDelivery( int $webhook_id, string $event, array $payload ): int {
		global $wpdb;

		$tables = Schema::getTables();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Webhook-URL laden.
		$webhook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT url FROM {$tables['webhooks']} WHERE id = %d",
				$webhook_id
			)
		);

		if ( ! $webhook ) {
			return 0;
		}

		$now = current_time( 'mysql' );

		$wpdb->insert(
			$tables['webhook_deliveries'],
			[
				'webhook_id'   => $webhook_id,
				'event'        => $event,
				'request_url'  => $webhook->url,
				'request_body' => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE ),
				'status'       => 'pending',
				'created_at'   => $now,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
		// phpcs:enable

		return (int) $wpdb->insert_id;
	}

	/**
	 * Test-Ping an einen Webhook senden (synchron)
	 *
	 * @param int $webhook_id Webhook-ID.
	 * @return array{success: bool, response_code: int|null, response_time_ms: int, error: string|null}
	 */
	public function sendTestPing( int $webhook_id ): array {
		global $wpdb;

		$tables = Schema::getTables();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$webhook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$tables['webhooks']} WHERE id = %d",
				$webhook_id
			)
		);
		// phpcs:enable

		if ( ! $webhook ) {
			return [
				'success'          => false,
				'response_code'    => null,
				'response_time_ms' => 0,
				'error'            => 'Webhook not found',
			];
		}

		$payload                = $this->buildPayload( 'ping', [ 'message' => 'Test ping from Recruiting Playbook' ] );
		$delivery_id            = $this->createDelivery( $webhook_id, 'ping', $payload );
		$payload['delivery_id'] = 'whd_' . $delivery_id;

		$payload_json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
		$signature    = 'sha256=' . hash_hmac( 'sha256', $payload_json, $webhook->secret );

		$headers = [
			'Content-Type'           => 'application/json',
			'X-Recruiting-Event'     => 'ping',
			'X-Recruiting-Delivery'  => 'whd_' . $delivery_id,
			'X-Recruiting-Signature' => $signature,
		];

		$start_time = microtime( true );

		$response = wp_remote_post(
			$webhook->url,
			[
				'body'      => $payload_json,
				'headers'   => $headers,
				'timeout'   => self::HTTP_TIMEOUT,
				'sslverify' => true,
			]
		);

		$elapsed_ms = (int) round( ( microtime( true ) - $start_time ) * 1000 );

		if ( is_wp_error( $response ) ) {
			// Delivery als fehlgeschlagen markieren.
			if ( $delivery_id ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$tables['webhook_deliveries'],
					[
						'request_headers'  => wp_json_encode( $headers ),
						'request_body'     => $payload_json,
						'status'           => 'failed',
						'error_message'    => $response->get_error_message(),
						'response_time_ms' => $elapsed_ms,
					],
					[ 'id' => $delivery_id ],
					[ '%s', '%s', '%s', '%s', '%d' ],
					[ '%d' ]
				);
				// phpcs:enable
			}

			return [
				'success'          => false,
				'response_code'    => null,
				'response_time_ms' => $elapsed_ms,
				'error'            => $response->get_error_message(),
			];
		}

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_body    = wp_remote_retrieve_body( $response );
		$response_headers = wp_remote_retrieve_headers( $response );
		$is_success       = $response_code >= 200 && $response_code < 300;

		// Delivery aktualisieren.
		if ( $delivery_id ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$tables['webhook_deliveries'],
				[
					'request_headers'  => wp_json_encode( $headers ),
					'request_body'     => $payload_json,
					'response_code'    => $response_code,
					'response_headers' => wp_json_encode( $response_headers->getAll() ),
					'response_body'    => mb_substr( $response_body, 0, 65535 ),
					'response_time_ms' => $elapsed_ms,
					'status'           => $is_success ? 'success' : 'failed',
					'error_message'    => $is_success ? null : "HTTP {$response_code}",
				],
				[ 'id' => $delivery_id ],
				[ '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' ],
				[ '%d' ]
			);
			// phpcs:enable
		}

		return [
			'success'          => $is_success,
			'response_code'    => $response_code,
			'response_time_ms' => $elapsed_ms,
			'error'            => $is_success ? null : "HTTP {$response_code}",
		];
	}

	// =========================================================================
	// Event-Handler (registriert in Plugin.php)
	// =========================================================================

	/**
	 * Handler: Neue Bewerbung erstellt
	 *
	 * @param int   $application_id Bewerbungs-ID.
	 * @param array $data           Bewerbungsdaten.
	 */
	public function onApplicationCreated( int $application_id, array $data ): void {
		$this->dispatch( 'application.received', [ 'application' => $data ] );
	}

	/**
	 * Handler: Bewerbungsstatus geändert
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $old_status     Alter Status.
	 * @param string $new_status     Neuer Status.
	 */
	public function onApplicationStatusChanged( int $application_id, string $old_status, string $new_status ): void {
		$data = [
			'application_id' => $application_id,
			'old_status'     => $old_status,
			'new_status'     => $new_status,
		];

		$this->dispatch( 'application.status_changed', $data );

		if ( 'hired' === $new_status ) {
			$this->dispatch( 'application.hired', $data );
		}

		if ( 'rejected' === $new_status ) {
			$this->dispatch( 'application.rejected', $data );
		}
	}

	/**
	 * Handler: Job-Status-Transition (publish, archive etc.)
	 *
	 * @param string   $new_status Neuer Status.
	 * @param string   $old_status Alter Status.
	 * @param \WP_Post $post       Post-Objekt.
	 */
	public function onJobStatusTransition( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( 'job_listing' !== $post->post_type ) {
			return;
		}

		$job_data = [
			'job_id' => $post->ID,
			'title'  => $post->post_title,
		];

		if ( 'publish' === $new_status && 'publish' !== $old_status ) {
			$this->dispatch( 'job.published', $job_data );
		}

		if ( 'draft' === $new_status && 'publish' === $old_status ) {
			$this->dispatch( 'job.archived', $job_data );
		}
	}

	/**
	 * Handler: Job gespeichert (erstellt oder aktualisiert)
	 *
	 * @param int      $post_id Post-ID.
	 * @param \WP_Post $post    Post-Objekt.
	 * @param bool     $update  Ob es ein Update ist.
	 */
	public function onJobSaved( int $post_id, \WP_Post $post, bool $update ): void {
		// Autosaves und Revisionen ignorieren.
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$job_data = [
			'job_id' => $post_id,
			'title'  => $post->post_title,
		];

		if ( ! $update ) {
			$this->dispatch( 'job.created', $job_data );
		} else {
			$this->dispatch( 'job.updated', $job_data );
		}
	}

	/**
	 * Handler: Job gelöscht
	 *
	 * @param int $post_id Post-ID.
	 */
	public function onJobDeleted( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post || 'job_listing' !== $post->post_type ) {
			return;
		}

		$this->dispatch(
			'job.deleted',
			[
				'job_id' => $post_id,
				'title'  => $post->post_title,
			]
		);
	}
}

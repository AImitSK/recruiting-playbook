<?php
/**
 * Abstract Notification Service Base Class
 *
 * Basis-Klasse für alle Notification-Services (Slack, Teams, etc.).
 * Bietet gemeinsame Funktionalität für Fehlerbehandlung, Rate Limiting und Logging.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Notifications;

use RecruitingPlaybook\Services\ActivityService;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract NotificationService Class
 */
abstract class NotificationService {

	/**
	 * Integration settings
	 *
	 * @var array<string, mixed>
	 */
	protected array $settings;

	/**
	 * Activity Service für Logging
	 *
	 * @var ActivityService
	 */
	protected ActivityService $activity_service;

	/**
	 * Constructor
	 *
	 * @param array<string, mixed> $settings Integration settings.
	 */
	public function __construct( array $settings ) {
		$this->settings         = $settings;
		$this->activity_service = new ActivityService();
	}

	/**
	 * Send notification message
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return bool Success status.
	 */
	abstract public function send( array $data ): bool;

	/**
	 * Format message for specific service
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<string, mixed> Formatted message payload.
	 */
	abstract protected function formatMessage( array $data ): array;

	/**
	 * Check if rate limit allows sending
	 *
	 * Enforces 1 message/second rate limit.
	 *
	 * @return bool True if OK to send.
	 */
	protected function checkRateLimit(): bool {
		$key  = 'rp_' . static::class . '_last_send';
		$last = get_transient( $key );

		if ( $last && ( time() - (int) $last ) < 1 ) {
			// Rate limit: mindestens 1 Sekunde zwischen Nachrichten.
			return false;
		}

		set_transient( $key, time(), 10 );
		return true;
	}

	/**
	 * Log notification attempt
	 *
	 * @param string               $event   Event type (new_application, status_changed, etc.).
	 * @param bool                 $success Success status.
	 * @param array<string, mixed> $meta    Additional metadata.
	 */
	protected function log( string $event, bool $success, array $meta = [] ): void {
		$service_name = $this->getServiceName();

		$this->activity_service->log(
			[
				'object_type' => 'notification',
				'object_id'   => 0,
				'action'      => $success ? $service_name . '_notification' : $service_name . '_notification_failed',
				'message'     => sprintf(
					'%s notification %s: %s',
					ucfirst( $service_name ),
					$success ? 'sent' : 'failed',
					$event
				),
				'meta'        => array_merge(
					[
						'event'   => $event,
						'success' => $success,
						'service' => $service_name,
					],
					$meta
				),
			]
		);
	}

	/**
	 * Get service name (slack, teams, etc.)
	 *
	 * @return string Service name in lowercase.
	 */
	protected function getServiceName(): string {
		$class = get_class( $this );
		$parts = explode( '\\', $class );
		$name  = end( $parts );
		return strtolower( str_replace( 'Notifier', '', $name ) );
	}

	/**
	 * Add to retry queue
	 *
	 * @param array<string, mixed> $payload Payload to retry.
	 * @param int                  $attempt Current attempt number.
	 */
	protected function addToRetryQueue( array $payload, int $attempt = 1 ): void {
		$service = $this->getServiceName();
		$key     = 'rp_' . $service . '_retry_queue';

		$queue   = get_transient( $key ) ?: [];
		$queue[] = [
			'payload'    => $payload,
			'attempt'    => $attempt,
			'next_retry' => time() + ( $attempt * 30 ), // Exponential backoff: 30s, 60s, 90s.
		];

		set_transient( $key, $queue, HOUR_IN_SECONDS );
	}

	/**
	 * Process retry queue
	 *
	 * Called by WP Cron.
	 */
	public function processRetryQueue(): void {
		$service = $this->getServiceName();
		$key     = 'rp_' . $service . '_retry_queue';
		$queue   = get_transient( $key ) ?: [];

		if ( empty( $queue ) ) {
			return;
		}

		$remaining = [];
		$now       = time();

		foreach ( $queue as $item ) {
			if ( $item['next_retry'] > $now ) {
				// Noch nicht Zeit für Retry.
				$remaining[] = $item;
				continue;
			}

			if ( $item['attempt'] >= 3 ) {
				// Max retries erreicht.
				$this->log( 'retry_failed', false, [ 'max_retries' => true ] );
				continue;
			}

			// Retry senden.
			$success = $this->send( $item['payload'] );

			if ( ! $success ) {
				// Erneut in Queue.
				$this->addToRetryQueue( $item['payload'], $item['attempt'] + 1 );
			}
		}

		if ( ! empty( $remaining ) ) {
			set_transient( $key, $remaining, HOUR_IN_SECONDS );
		} else {
			delete_transient( $key );
		}
	}
}

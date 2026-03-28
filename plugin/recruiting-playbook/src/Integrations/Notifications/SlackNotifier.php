<?php
/**
 * Slack Notification Service
 *
 * Sendet Benachrichtigungen an Slack via Incoming Webhooks.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Notifications;

use RecruitingPlaybook\Services\ApplicationService;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Slack Notifier Class
 */
class SlackNotifier extends NotificationService {

	/**
	 * Event handler: New application created
	 *
	 * @param int $application_id Application ID.
	 */
	public function onNewApplication( int $application_id ): void {
		if ( ! $this->isEventEnabled( 'slack_event_new_application' ) ) {
			return;
		}

		$app_service = new ApplicationService();
		$application = $app_service->get( $application_id );

		if ( ! $application ) {
			return;
		}

		$job = get_post( $application['job_id'] );

		$data = [
			'event'          => 'new_application',
			'candidate_name' => $application['first_name'] . ' ' . $application['last_name'],
			'job_title'      => $job ? $job->post_title : __( 'Unknown Job', 'recruiting-playbook' ),
			'source'         => $application['source'] ?? 'Website',
			'email'          => $application['email'],
			'phone'          => $application['phone'] ?? '',
			'link'           => admin_url( 'admin.php?page=rp-applications&action=view&id=' . $application_id ),
		];

		$this->send( $data );
	}

	/**
	 * Event handler: Application status changed
	 *
	 * @param int    $application_id Application ID.
	 * @param string $old_status     Old status.
	 * @param string $new_status     New status.
	 */
	public function onStatusChanged( int $application_id, string $old_status, string $new_status ): void {
		if ( ! $this->isEventEnabled( 'slack_event_status_changed' ) ) {
			return;
		}

		$app_service = new ApplicationService();
		$application = $app_service->get( $application_id );

		if ( ! $application ) {
			return;
		}

		$job = get_post( $application['job_id'] );

		$data = [
			'event'          => 'status_changed',
			'candidate_name' => $application['first_name'] . ' ' . $application['last_name'],
			'job_title'      => $job ? $job->post_title : __( 'Unknown Job', 'recruiting-playbook' ),
			'old_status'     => $this->getStatusLabel( $old_status ),
			'new_status'     => $this->getStatusLabel( $new_status ),
			'link'           => admin_url( 'admin.php?page=rp-applications&action=view&id=' . $application_id ),
		];

		$this->send( $data );
	}

	/**
	 * Event handler: Job published
	 *
	 * @param int $job_id Job post ID.
	 */
	public function onJobPublished( int $job_id ): void {
		if ( ! $this->isEventEnabled( 'slack_event_job_published' ) ) {
			return;
		}

		$job = get_post( $job_id );

		if ( ! $job || 'job_listing' !== $job->post_type ) {
			return;
		}

		$locations = wp_get_post_terms( $job_id, 'job_location' );
		$types     = wp_get_post_terms( $job_id, 'employment_type' );

		$data = [
			'event'      => 'job_published',
			'job_title'  => $job->post_title,
			'location'   => ! empty( $locations ) ? $locations[0]->name : '',
			'employment' => ! empty( $types ) ? $types[0]->name : '',
			'link'       => get_permalink( $job_id ),
			'admin_link' => admin_url( 'post.php?post=' . $job_id . '&action=edit' ),
		];

		$this->send( $data );
	}

	/**
	 * Send notification to Slack
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return bool Success status.
	 */
	public function send( array $data ): bool {
		$webhook_url = $this->settings['slack_webhook_url'] ?? '';

		if ( empty( $webhook_url ) ) {
			return false;
		}

		if ( ! $this->validateWebhookUrl( $webhook_url ) ) {
			$this->log( $data['event'] ?? 'unknown', false, [ 'error' => 'invalid_webhook_url' ] );
			return false;
		}

		if ( ! $this->checkRateLimit() ) {
			$this->log( $data['event'] ?? 'unknown', false, [ 'error' => 'rate_limited' ] );
			return false;
		}

		$payload = $this->formatMessage( $data );

		return $this->sendWebhook( $webhook_url, $payload, $data['event'] ?? 'unknown' );
	}

	/**
	 * Format message as Slack Block Kit
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<string, mixed> Slack payload.
	 */
	protected function formatMessage( array $data ): array {
		$event  = $data['event'] ?? 'unknown';
		$blocks = $this->buildBlocks( $data );

		return [
			'text'   => $this->getFallbackText( $data ),
			'blocks' => $blocks,
		];
	}

	/**
	 * Build Block Kit blocks based on event
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<int, array<string, mixed>> Block Kit blocks.
	 */
	private function buildBlocks( array $data ): array {
		$event = $data['event'] ?? 'unknown';

		switch ( $event ) {
			case 'new_application':
				return $this->buildNewApplicationBlocks( $data );

			case 'status_changed':
				return $this->buildStatusChangedBlocks( $data );

			case 'job_published':
				return $this->buildJobPublishedBlocks( $data );

			default:
				return $this->buildTestBlocks( $data );
		}
	}

	/**
	 * Build blocks for new application notification
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<int, array<string, mixed>> Blocks.
	 */
	private function buildNewApplicationBlocks( array $data ): array {
		return [
			[
				'type' => 'section',
				'text' => [
					'type' => 'mrkdwn',
					'text' => '📋 *' . __( 'New Application', 'recruiting-playbook' ) . '*',
				],
			],
			[
				'type'   => 'section',
				'fields' => [
					[
						'type' => 'mrkdwn',
						'text' => '*' . __( 'Candidate', 'recruiting-playbook' ) . ":*\n" . $data['candidate_name'],
					],
					[
						'type' => 'mrkdwn',
						'text' => '*' . __( 'Job', 'recruiting-playbook' ) . ":*\n" . $data['job_title'],
					],
					[
						'type' => 'mrkdwn',
						'text' => '*' . __( 'Source', 'recruiting-playbook' ) . ":*\n" . $data['source'],
					],
					[
						'type' => 'mrkdwn',
						'text' => '*' . __( 'Email', 'recruiting-playbook' ) . ":*\n" . $data['email'],
					],
				],
			],
			[
				'type'     => 'actions',
				'elements' => [
					[
						'type'  => 'button',
						'text'  => [
							'type' => 'plain_text',
							'text' => __( 'View Application', 'recruiting-playbook' ),
						],
						'url'   => $data['link'],
						'style' => 'primary',
					],
				],
			],
		];
	}

	/**
	 * Build blocks for status changed notification
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<int, array<string, mixed>> Blocks.
	 */
	private function buildStatusChangedBlocks( array $data ): array {
		return [
			[
				'type' => 'section',
				'text' => [
					'type' => 'mrkdwn',
					'text' => '🔄 *' . __( 'Status Changed', 'recruiting-playbook' ) . '*',
				],
			],
			[
				'type'   => 'section',
				'fields' => [
					[
						'type' => 'mrkdwn',
						'text' => '*' . __( 'Candidate', 'recruiting-playbook' ) . ":*\n" . $data['candidate_name'],
					],
					[
						'type' => 'mrkdwn',
						'text' => '*' . __( 'Job', 'recruiting-playbook' ) . ":*\n" . $data['job_title'],
					],
					[
						'type' => 'mrkdwn',
						'text' => '*' . __( 'Status', 'recruiting-playbook' ) . ":*\n~" . $data['old_status'] . '~ → *' . $data['new_status'] . '*',
					],
				],
			],
			[
				'type'     => 'actions',
				'elements' => [
					[
						'type' => 'button',
						'text' => [
							'type' => 'plain_text',
							'text' => __( 'View Application', 'recruiting-playbook' ),
						],
						'url'  => $data['link'],
					],
				],
			],
		];
	}

	/**
	 * Build blocks for job published notification
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<int, array<string, mixed>> Blocks.
	 */
	private function buildJobPublishedBlocks( array $data ): array {
		$fields = [
			[
				'type' => 'mrkdwn',
				'text' => '*' . __( 'Title', 'recruiting-playbook' ) . ":*\n" . $data['job_title'],
			],
		];

		if ( ! empty( $data['location'] ) ) {
			$fields[] = [
				'type' => 'mrkdwn',
				'text' => '*' . __( 'Location', 'recruiting-playbook' ) . ":*\n" . $data['location'],
			];
		}

		if ( ! empty( $data['employment'] ) ) {
			$fields[] = [
				'type' => 'mrkdwn',
				'text' => '*' . __( 'Type', 'recruiting-playbook' ) . ":*\n" . $data['employment'],
			];
		}

		return [
			[
				'type' => 'section',
				'text' => [
					'type' => 'mrkdwn',
					'text' => '🆕 *' . __( 'New Job Published', 'recruiting-playbook' ) . '*',
				],
			],
			[
				'type'   => 'section',
				'fields' => $fields,
			],
			[
				'type'     => 'actions',
				'elements' => [
					[
						'type' => 'button',
						'text' => [
							'type' => 'plain_text',
							'text' => __( 'View Job', 'recruiting-playbook' ),
						],
						'url'  => $data['link'],
					],
					[
						'type' => 'button',
						'text' => [
							'type' => 'plain_text',
							'text' => __( 'Edit', 'recruiting-playbook' ),
						],
						'url'  => $data['admin_link'],
					],
				],
			],
		];
	}

	/**
	 * Build blocks for test message
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<int, array<string, mixed>> Blocks.
	 */
	private function buildTestBlocks( array $data ): array {
		return [
			[
				'type' => 'section',
				'text' => [
					'type' => 'mrkdwn',
					'text' => "✅ *" . __( 'Test Message', 'recruiting-playbook' ) . "*\n\n" .
							__( 'Slack integration is correctly configured!', 'recruiting-playbook' ),
				],
			],
		];
	}

	/**
	 * Send webhook request to Slack
	 *
	 * @param string               $webhook_url Slack webhook URL.
	 * @param array<string, mixed> $payload     Message payload.
	 * @param string               $event       Event type for logging.
	 * @return bool Success status.
	 */
	private function sendWebhook( string $webhook_url, array $payload, string $event ): bool {
		$response = wp_remote_post(
			$webhook_url,
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( $payload ),
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->log( $event, false, [ 'error' => $response->get_error_message() ] );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $code ) {
			$this->log( $event, true, [ 'http_code' => $code ] );
			return true;
		}

		// Error handling basierend auf HTTP Code.
		if ( 429 === $code || 500 === $code ) {
			// Retry für Rate Limit oder Server Error.
			$this->addToRetryQueue( $payload );
			$this->log( $event, false, [ 'http_code' => $code, 'retry' => true ] );
		} else {
			// Permanenter Fehler (400, 404, etc.) - kein Retry.
			$this->log( $event, false, [ 'http_code' => $code, 'retry' => false ] );
		}

		return false;
	}

	/**
	 * Validate Slack webhook URL
	 *
	 * @param string $url Webhook URL.
	 * @return bool True if valid.
	 */
	private function validateWebhookUrl( string $url ): bool {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$parsed = wp_parse_url( $url );

		if ( ! isset( $parsed['host'] ) || 'hooks.slack.com' !== $parsed['host'] ) {
			return false;
		}

		if ( ! isset( $parsed['path'] ) || ! str_starts_with( $parsed['path'], '/services/' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get fallback text for notification
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return string Fallback text.
	 */
	private function getFallbackText( array $data ): string {
		$event = $data['event'] ?? 'unknown';

		switch ( $event ) {
			case 'new_application':
				return sprintf(
					/* translators: 1: candidate name, 2: job title */
					__( 'New Application: %1$s for %2$s', 'recruiting-playbook' ),
					$data['candidate_name'],
					$data['job_title']
				);

			case 'status_changed':
				return sprintf(
					/* translators: 1: candidate name, 2: new status */
					__( 'Status Changed: %1$s → %2$s', 'recruiting-playbook' ),
					$data['candidate_name'],
					$data['new_status']
				);

			case 'job_published':
				return sprintf(
					/* translators: %s: job title */
					__( 'New Job Published: %s', 'recruiting-playbook' ),
					$data['job_title']
				);

			default:
				return __( 'Recruiting Playbook Notification', 'recruiting-playbook' );
		}
	}

	/**
	 * Get status label
	 *
	 * @param string $status Status code.
	 * @return string Translated label.
	 */
	private function getStatusLabel( string $status ): string {
		$labels = [
			'new'       => __( 'New', 'recruiting-playbook' ),
			'screening' => __( 'Screening', 'recruiting-playbook' ),
			'interview' => __( 'Interview', 'recruiting-playbook' ),
			'offer'     => __( 'Offer', 'recruiting-playbook' ),
			'hired'     => __( 'Hired', 'recruiting-playbook' ),
			'rejected'  => __( 'Rejected', 'recruiting-playbook' ),
			'withdrawn' => __( 'Withdrawn', 'recruiting-playbook' ),
		];

		return $labels[ $status ] ?? ucfirst( $status );
	}

	/**
	 * Check if event is enabled in settings
	 *
	 * @param string $event_key Settings key for event (e.g. 'slack_event_new_application').
	 * @return bool True if enabled.
	 */
	private function isEventEnabled( string $event_key ): bool {
		return ! empty( $this->settings[ $event_key ] );
	}
}

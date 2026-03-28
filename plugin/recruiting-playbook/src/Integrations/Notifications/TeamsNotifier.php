<?php
/**
 * Microsoft Teams Notification Service
 *
 * Sendet Benachrichtigungen an Microsoft Teams via Workflow Webhooks (Adaptive Cards).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Notifications;

use RecruitingPlaybook\Services\ApplicationService;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Teams Notifier Class
 */
class TeamsNotifier extends NotificationService {

	/**
	 * Event handler: New application created
	 *
	 * @param int $application_id Application ID.
	 */
	public function onNewApplication( int $application_id ): void {
		if ( ! $this->isEventEnabled( 'teams_event_new_application' ) ) {
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
		if ( ! $this->isEventEnabled( 'teams_event_status_changed' ) ) {
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
		if ( ! $this->isEventEnabled( 'teams_event_job_published' ) ) {
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
	 * Send notification to Microsoft Teams
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return bool Success status.
	 */
	public function send( array $data ): bool {
		$webhook_url = $this->settings['teams_webhook_url'] ?? '';

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
	 * Format message as Teams Adaptive Card
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<string, mixed> Teams payload.
	 */
	protected function formatMessage( array $data ): array {
		$adaptive_card = $this->buildAdaptiveCard( $data );

		return [
			'type'        => 'message',
			'attachments' => [
				[
					'contentType' => 'application/vnd.microsoft.card.adaptive',
					'content'     => $adaptive_card,
				],
			],
		];
	}

	/**
	 * Build Adaptive Card based on event
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<string, mixed> Adaptive Card JSON.
	 */
	public function buildAdaptiveCard( array $data ): array {
		$event = $data['event'] ?? 'unknown';

		switch ( $event ) {
			case 'new_application':
				return $this->buildNewApplicationCard( $data );

			case 'status_changed':
				return $this->buildStatusChangedCard( $data );

			case 'job_published':
				return $this->buildJobPublishedCard( $data );

			default:
				return $this->buildTestCard( $data );
		}
	}

	/**
	 * Build Adaptive Card for new application
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<string, mixed> Adaptive Card.
	 */
	private function buildNewApplicationCard( array $data ): array {
		return [
			'type'    => 'AdaptiveCard',
			'$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
			'version' => '1.4',
			'body'    => [
				[
					'type'   => 'TextBlock',
					'text'   => '📋 ' . __( 'New Application', 'recruiting-playbook' ),
					'weight' => 'bolder',
					'size'   => 'large',
					'color'  => 'accent',
				],
				[
					'type'  => 'FactSet',
					'facts' => [
						[
							'title' => __( 'Candidate', 'recruiting-playbook' ) . ':',
							'value' => $data['candidate_name'],
						],
						[
							'title' => __( 'Job', 'recruiting-playbook' ) . ':',
							'value' => $data['job_title'],
						],
						[
							'title' => __( 'Source', 'recruiting-playbook' ) . ':',
							'value' => $data['source'],
						],
						[
							'title' => __( 'Email', 'recruiting-playbook' ) . ':',
							'value' => $data['email'],
						],
					],
				],
			],
			'actions' => [
				[
					'type'  => 'Action.OpenUrl',
					'title' => __( 'View Application', 'recruiting-playbook' ),
					'url'   => $data['link'],
				],
			],
		];
	}

	/**
	 * Build Adaptive Card for status change
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<string, mixed> Adaptive Card.
	 */
	private function buildStatusChangedCard( array $data ): array {
		return [
			'type'    => 'AdaptiveCard',
			'$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
			'version' => '1.4',
			'body'    => [
				[
					'type'   => 'TextBlock',
					'text'   => '🔄 ' . __( 'Status Changed', 'recruiting-playbook' ),
					'weight' => 'bolder',
					'size'   => 'large',
					'color'  => 'warning',
				],
				[
					'type'  => 'FactSet',
					'facts' => [
						[
							'title' => __( 'Candidate', 'recruiting-playbook' ) . ':',
							'value' => $data['candidate_name'],
						],
						[
							'title' => __( 'Job', 'recruiting-playbook' ) . ':',
							'value' => $data['job_title'],
						],
						[
							'title' => __( 'Status', 'recruiting-playbook' ) . ':',
							'value' => $data['old_status'] . ' → ' . $data['new_status'],
						],
					],
				],
				[
					'type'    => 'TextBlock',
					'text'    => sprintf(
						/* translators: 1: old status, 2: new status */
						__( 'The application status has been changed from **%1$s** to **%2$s**.', 'recruiting-playbook' ),
						$data['old_status'],
						$data['new_status']
					),
					'wrap'    => true,
					'spacing' => 'medium',
				],
			],
			'actions' => [
				[
					'type'  => 'Action.OpenUrl',
					'title' => __( 'View Application', 'recruiting-playbook' ),
					'url'   => $data['link'],
				],
			],
		];
	}

	/**
	 * Build Adaptive Card for new job published
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<string, mixed> Adaptive Card.
	 */
	private function buildJobPublishedCard( array $data ): array {
		$facts = [
			[
				'title' => __( 'Title', 'recruiting-playbook' ) . ':',
				'value' => $data['job_title'],
			],
		];

		if ( ! empty( $data['location'] ) ) {
			$facts[] = [
				'title' => __( 'Location', 'recruiting-playbook' ) . ':',
				'value' => $data['location'],
			];
		}

		if ( ! empty( $data['employment'] ) ) {
			$facts[] = [
				'title' => __( 'Type', 'recruiting-playbook' ) . ':',
				'value' => $data['employment'],
			];
		}

		return [
			'type'    => 'AdaptiveCard',
			'$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
			'version' => '1.4',
			'body'    => [
				[
					'type'   => 'TextBlock',
					'text'   => '🆕 ' . __( 'New Job Published', 'recruiting-playbook' ),
					'weight' => 'bolder',
					'size'   => 'large',
					'color'  => 'good',
				],
				[
					'type'  => 'FactSet',
					'facts' => $facts,
				],
			],
			'actions' => [
				[
					'type'  => 'Action.OpenUrl',
					'title' => __( 'View Job', 'recruiting-playbook' ),
					'url'   => $data['link'],
				],
				[
					'type'  => 'Action.OpenUrl',
					'title' => __( 'Edit', 'recruiting-playbook' ),
					'url'   => $data['admin_link'],
				],
			],
		];
	}

	/**
	 * Build Adaptive Card for test message
	 *
	 * @param array<string, mixed> $data Message data.
	 * @return array<string, mixed> Adaptive Card.
	 */
	private function buildTestCard( array $data ): array {
		return [
			'type'    => 'AdaptiveCard',
			'$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
			'version' => '1.4',
			'body'    => [
				[
					'type'   => 'TextBlock',
					'text'   => '✅ ' . __( 'Test Message', 'recruiting-playbook' ),
					'weight' => 'bolder',
					'size'   => 'large',
				],
				[
					'type' => 'TextBlock',
					'text' => __( 'Microsoft Teams integration is correctly configured!', 'recruiting-playbook' ),
					'wrap' => true,
				],
			],
		];
	}

	/**
	 * Send webhook request to Microsoft Teams
	 *
	 * @param string               $webhook_url Teams workflow webhook URL.
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

		// Teams returns 202 Accepted on success (not 200).
		if ( 202 === $code ) {
			$this->log( $event, true, [ 'http_code' => $code ] );
			return true;
		}

		// Error handling basierend auf HTTP Code.
		if ( 429 === $code || 500 === $code || 502 === $code ) {
			// Retry für Rate Limit oder Server Error.
			$this->addToRetryQueue( $payload );
			$this->log( $event, false, [ 'http_code' => $code, 'retry' => true ] );
		} else {
			// Permanenter Fehler (400, 401, 404, etc.) - kein Retry.
			$this->log( $event, false, [ 'http_code' => $code, 'retry' => false ] );
		}

		return false;
	}

	/**
	 * Validate Teams workflow webhook URL
	 *
	 * @param string $url Webhook URL.
	 * @return bool True if valid.
	 */
	private function validateWebhookUrl( string $url ): bool {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$parsed = wp_parse_url( $url );

		// Muss *.logic.azure.com sein.
		if ( ! isset( $parsed['host'] ) || ! str_ends_with( $parsed['host'], '.logic.azure.com' ) ) {
			return false;
		}

		// Pfad muss /workflows/ enthalten.
		if ( ! isset( $parsed['path'] ) || ! str_contains( $parsed['path'], '/workflows/' ) ) {
			return false;
		}

		return true;
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
	 * @param string $event_key Settings key for event (e.g. 'teams_event_new_application').
	 * @return bool True if enabled.
	 */
	private function isEventEnabled( string $event_key ): bool {
		return ! empty( $this->settings[ $event_key ] );
	}
}

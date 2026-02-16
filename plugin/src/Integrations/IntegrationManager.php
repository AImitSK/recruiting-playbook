<?php
/**
 * Integration Manager
 *
 * Zentrale Verwaltung und Registrierung aller Integrationen (Slack, Teams, etc.).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Integrations;

use RecruitingPlaybook\Integrations\Notifications\SlackNotifier;

defined( 'ABSPATH' ) || exit;

/**
 * Integration Manager Class
 */
class IntegrationManager {

	/**
	 * Integration settings
	 *
	 * @var array<string, mixed>
	 */
	private array $settings;

	/**
	 * Slack Notifier instance
	 *
	 * @var SlackNotifier|null
	 */
	private ?SlackNotifier $slack_notifier = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = $this->loadSettings();
	}

	/**
	 * Register all integrations
	 *
	 * Hook into WordPress actions and initialize enabled integrations.
	 */
	public function register(): void {
		// Feature Check: Integrationen sind Pro-Feature.
		if ( ! function_exists( 'rp_can' ) || ! rp_can( 'integrations' ) ) {
			return;
		}

		// Slack Integration registrieren.
		$this->registerSlack();

		// Retry Cron Job registrieren.
		$this->registerCronJobs();
	}

	/**
	 * Register Slack integration
	 */
	private function registerSlack(): void {
		// Prüfen ob Slack aktiviert und konfiguriert ist.
		if ( empty( $this->settings['slack_enabled'] ) || empty( $this->settings['slack_webhook_url'] ) ) {
			return;
		}

		$this->slack_notifier = new SlackNotifier( $this->settings );

		// Event Hooks registrieren.
		if ( ! empty( $this->settings['slack_event_new_application'] ) ) {
			add_action( 'rp_application_created', [ $this->slack_notifier, 'onNewApplication' ], 10, 1 );
		}

		if ( ! empty( $this->settings['slack_event_status_changed'] ) ) {
			add_action( 'rp_application_status_changed', [ $this->slack_notifier, 'onStatusChanged' ], 10, 3 );
		}

		if ( ! empty( $this->settings['slack_event_job_published'] ) ) {
			add_action( 'publish_job_listing', [ $this->slack_notifier, 'onJobPublished' ], 10, 1 );
		}
	}

	/**
	 * Register WP Cron jobs for retry queue
	 */
	private function registerCronJobs(): void {
		// Slack Retry Cron.
		if ( ! wp_next_scheduled( 'rp_slack_retry_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'rp_slack_retry_cron' );
		}

		add_action( 'rp_slack_retry_cron', [ $this, 'processSlackRetryQueue' ] );
	}

	/**
	 * Process Slack retry queue
	 *
	 * Called by WP Cron hourly.
	 */
	public function processSlackRetryQueue(): void {
		if ( $this->slack_notifier ) {
			$this->slack_notifier->processRetryQueue();
		}
	}

	/**
	 * Load integration settings from WordPress options
	 *
	 * @return array<string, mixed> Settings array.
	 */
	private function loadSettings(): array {
		$defaults = [
			// Slack (Pro).
			'slack_enabled'                 => false,
			'slack_webhook_url'             => '',
			'slack_event_new_application'   => true,
			'slack_event_status_changed'    => true,
			'slack_event_job_published'     => false,
			'slack_event_deadline_reminder' => false,

			// Microsoft Teams (Pro).
			'teams_enabled'                 => false,
			'teams_webhook_url'             => '',
			'teams_event_new_application'   => true,
			'teams_event_status_changed'    => true,
			'teams_event_job_published'     => false,
			'teams_event_deadline_reminder' => false,

			// Google Ads Conversion (Pro).
			'google_ads_enabled'            => false,
			'google_ads_conversion_id'      => '',
			'google_ads_conversion_label'   => '',
			'google_ads_conversion_value'   => '',
		];

		$settings = get_option( 'rp_integrations', [] );

		return array_merge( $defaults, $settings );
	}

	/**
	 * Get Slack notifier instance
	 *
	 * For testing purposes.
	 *
	 * @return SlackNotifier|null
	 */
	public function getSlackNotifier(): ?SlackNotifier {
		return $this->slack_notifier;
	}
}

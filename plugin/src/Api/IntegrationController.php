<?php
/**
 * REST API Controller für Integrations-Settings
 *
 * Endpoints zum Laden und Speichern der Integrations-Einstellungen
 * sowie Test-Endpoints für Slack und Teams.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Integration Controller
 */
class IntegrationController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Option-Name
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'rp_integrations';

	/**
	 * Default-Werte
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULTS = [
		// Google for Jobs (Free).
		'google_jobs_enabled'           => true,
		'google_jobs_show_salary'       => true,
		'google_jobs_show_remote'       => true,
		'google_jobs_show_deadline'     => true,

		// XML Job Feed (Free).
		'xml_feed_enabled'              => true,
		'xml_feed_show_salary'          => true,
		'xml_feed_html_description'     => true,
		'xml_feed_max_items'            => 50,

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

	/**
	 * Routes registrieren
	 */
	public function register_routes(): void {
		// GET/POST Settings.
		register_rest_route(
			$this->namespace,
			'/settings/integrations',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
				],
			]
		);

		// POST Test Slack.
		register_rest_route(
			$this->namespace,
			'/integrations/slack/test',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'test_slack' ],
				'permission_callback' => [ $this, 'admin_permissions_check' ],
			]
		);

		// POST Test Teams.
		register_rest_route(
			$this->namespace,
			'/integrations/teams/test',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'test_teams' ],
				'permission_callback' => [ $this, 'admin_permissions_check' ],
			]
		);
	}

	/**
	 * Admin-Berechtigung prüfen
	 *
	 * @return bool|WP_Error
	 */
	public function admin_permissions_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * Einstellungen abrufen
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		$settings = get_option( self::OPTION_NAME, [] );
		$merged   = array_merge( self::DEFAULTS, is_array( $settings ) ? $settings : [] );

		return new WP_REST_Response( $merged, 200 );
	}

	/**
	 * Einstellungen speichern
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$data     = $request->get_json_params();
		$current  = get_option( self::OPTION_NAME, [] );
		$sanitized = [];

		// Boolean-Felder.
		$bool_fields = [
			'google_jobs_enabled',
			'google_jobs_show_salary',
			'google_jobs_show_remote',
			'google_jobs_show_deadline',
			'xml_feed_enabled',
			'xml_feed_show_salary',
			'xml_feed_html_description',
			'slack_enabled',
			'slack_event_new_application',
			'slack_event_status_changed',
			'slack_event_job_published',
			'slack_event_deadline_reminder',
			'teams_enabled',
			'teams_event_new_application',
			'teams_event_status_changed',
			'teams_event_job_published',
			'teams_event_deadline_reminder',
			'google_ads_enabled',
		];

		foreach ( $bool_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = (bool) $data[ $field ];
			}
		}

		// URL-Felder.
		if ( isset( $data['slack_webhook_url'] ) ) {
			$sanitized['slack_webhook_url'] = esc_url_raw( $data['slack_webhook_url'] );
		}
		if ( isset( $data['teams_webhook_url'] ) ) {
			$sanitized['teams_webhook_url'] = esc_url_raw( $data['teams_webhook_url'] );
		}

		// Google Ads Felder.
		if ( isset( $data['google_ads_conversion_id'] ) ) {
			$id = sanitize_text_field( $data['google_ads_conversion_id'] );
			// Nur AW- Prefix oder leer erlauben.
			if ( '' === $id || str_starts_with( $id, 'AW-' ) ) {
				$sanitized['google_ads_conversion_id'] = $id;
			}
		}
		if ( isset( $data['google_ads_conversion_label'] ) ) {
			$sanitized['google_ads_conversion_label'] = sanitize_text_field( $data['google_ads_conversion_label'] );
		}
		if ( isset( $data['google_ads_conversion_value'] ) ) {
			$value = $data['google_ads_conversion_value'];
			$sanitized['google_ads_conversion_value'] = '' === $value ? '' : (string) max( 0, (float) $value );
		}

		// Integer-Felder.
		if ( isset( $data['xml_feed_max_items'] ) ) {
			$sanitized['xml_feed_max_items'] = max( 1, min( 500, (int) $data['xml_feed_max_items'] ) );
		}
		// Merge mit bestehenden Werten.
		$merged = array_merge(
			is_array( $current ) ? $current : [],
			$sanitized
		);

		update_option( self::OPTION_NAME, $merged );

		return new WP_REST_Response(
			array_merge( self::DEFAULTS, $merged ),
			200
		);
	}

	/**
	 * Slack Test-Nachricht senden
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function test_slack(): WP_REST_Response|WP_Error {
		$settings = get_option( self::OPTION_NAME, [] );
		$url      = $settings['slack_webhook_url'] ?? '';

		if ( empty( $url ) ) {
			return new WP_Error(
				'no_webhook_url',
				__( 'No Slack webhook URL configured.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$payload = [
			'blocks' => [
				[
					'type' => 'section',
					'text' => [
						'type' => 'mrkdwn',
						'text' => "*Recruiting Playbook – Test*\n" . __( 'Slack integration is working!', 'recruiting-playbook' ),
					],
				],
			],
		];

		$response = wp_remote_post(
			$url,
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( $payload ),
				'timeout' => 10,
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'slack_error',
				$response->get_error_message(),
				[ 'status' => 502 ]
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new WP_Error(
				'slack_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Slack responded with status %d.', 'recruiting-playbook' ),
					$code
				),
				[ 'status' => 502 ]
			);
		}

		return new WP_REST_Response(
			[ 'message' => __( 'Test message sent successfully!', 'recruiting-playbook' ) ],
			200
		);
	}

	/**
	 * Teams Test-Nachricht senden
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function test_teams(): WP_REST_Response|WP_Error {
		$settings = get_option( self::OPTION_NAME, [] );
		$url      = $settings['teams_webhook_url'] ?? '';

		if ( empty( $url ) ) {
			return new WP_Error(
				'no_webhook_url',
				__( 'No Teams workflow URL configured.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$payload = [
			'type'        => 'message',
			'attachments' => [
				[
					'contentType' => 'application/vnd.microsoft.card.adaptive',
					'content'     => [
						'type'    => 'AdaptiveCard',
						'version' => '1.4',
						'body'    => [
							[
								'type'   => 'TextBlock',
								'text'   => 'Recruiting Playbook – Test',
								'weight' => 'bolder',
								'size'   => 'medium',
							],
							[
								'type' => 'TextBlock',
								'text' => __( 'Teams integration is working!', 'recruiting-playbook' ),
							],
						],
					],
				],
			],
		];

		$response = wp_remote_post(
			$url,
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( $payload ),
				'timeout' => 10,
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'teams_error',
				$response->get_error_message(),
				[ 'status' => 502 ]
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'teams_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Teams responded with status %d.', 'recruiting-playbook' ),
					$code
				),
				[ 'status' => 502 ]
			);
		}

		return new WP_REST_Response(
			[ 'message' => __( 'Test message sent successfully!', 'recruiting-playbook' ) ],
			200
		);
	}
}

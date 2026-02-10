<?php
/**
 * REST API Controller für Webhooks
 *
 * CRUD-Endpoints unter /recruiting/v1/webhooks mit Delivery-Log
 * und Test-Ping Funktionalität. Pro-Feature (webhooks).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;
use RecruitingPlaybook\Services\WebhookService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST API Controller für Webhooks
 */
class WebhookController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Resource base
	 *
	 * @var string
	 */
	protected $rest_base = 'webhooks';

	/**
	 * Erlaubte Webhook-Events
	 *
	 * @var array<string>
	 */
	const VALID_EVENTS = [
		'job.created',
		'job.published',
		'job.updated',
		'job.archived',
		'job.deleted',
		'application.received',
		'application.status_changed',
		'application.hired',
		'application.rejected',
		'application.exported',
		'application.deleted',
	];

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// CRUD.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
					'args'                => $this->get_create_item_args(),
				],
			]
		);

		// Einzelner Webhook (Update + Delete).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
					'args'                => $this->get_update_item_args(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Webhook-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Deliveries.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/deliveries',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_deliveries' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
					'args'                => [
						'id'       => [
							'description' => __( 'Webhook-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'per_page' => [
							'description' => __( 'Ergebnisse pro Seite', 'recruiting-playbook' ),
							'type'        => 'integer',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'page'     => [
							'description' => __( 'Seitennummer', 'recruiting-playbook' ),
							'type'        => 'integer',
							'default'     => 1,
							'minimum'     => 1,
						],
					],
				],
			]
		);

		// Test-Ping.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/test',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'test_webhook' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Webhook-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Berechtigungsprüfung: Admin + Pro-Feature
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function admin_permissions_check( $request ) {
		// Pro-Feature Gate.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'webhooks' ) ) {
			return new WP_Error(
				'rest_webhooks_pro_required',
				__( 'Webhooks erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung für Webhook-Verwaltung.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Webhooks auflisten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		global $wpdb;

		$table = Schema::getTables()['webhooks'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$webhooks = $wpdb->get_results(
			"SELECT * FROM {$table} ORDER BY created_at DESC"
		);
		// phpcs:enable

		$data = array_map( [ $this, 'prepare_webhook_response' ], $webhooks ?: [] );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Webhook erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		global $wpdb;

		$name   = sanitize_text_field( $request->get_param( 'name' ) );
		$url    = esc_url_raw( $request->get_param( 'url' ) );
		$events = $request->get_param( 'events' );
		$secret = $request->get_param( 'secret' );
		$active = $request->get_param( 'active' );

		// URL validieren.
		$url_error = $this->validate_webhook_url( $url );
		if ( is_wp_error( $url_error ) ) {
			return $url_error;
		}

		// Events validieren.
		$events_error = $this->validate_events( $events );
		if ( is_wp_error( $events_error ) ) {
			return $events_error;
		}

		// Secret auto-generieren wenn nicht übergeben.
		if ( empty( $secret ) ) {
			$secret = wp_generate_password( 32, false );
		}

		$table = Schema::getTables()['webhooks'];
		$now   = current_time( 'mysql' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$table,
			[
				'name'       => $name,
				'url'        => $url,
				'secret'     => $secret,
				'events'     => wp_json_encode( array_values( $events ) ),
				'is_active'  => ( null === $active || $active ) ? 1 : 0,
				'created_by' => get_current_user_id(),
				'created_at' => $now,
				'updated_at' => $now,
			],
			[ '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ]
		);

		$webhook_id = (int) $wpdb->insert_id;

		if ( ! $webhook_id ) {
			return new WP_Error(
				'rest_webhook_create_failed',
				__( 'Webhook konnte nicht erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$webhook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$webhook_id
			)
		);
		// phpcs:enable

		return new WP_REST_Response( $this->prepare_webhook_response( $webhook ), 201 );
	}

	/**
	 * Webhook aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		global $wpdb;

		$id    = (int) $request->get_param( 'id' );
		$table = Schema::getTables()['webhooks'];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Webhook laden.
		$webhook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$id
			)
		);

		// phpcs:enable

		if ( ! $webhook ) {
			return new WP_Error(
				'rest_webhook_not_found',
				__( 'Webhook nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$update_data    = [];
		$update_formats = [];

		// Name.
		$name = $request->get_param( 'name' );
		if ( null !== $name ) {
			$update_data['name'] = sanitize_text_field( $name );
			$update_formats[]    = '%s';
		}

		// URL.
		$url = $request->get_param( 'url' );
		if ( null !== $url ) {
			$url       = esc_url_raw( $url );
			$url_error = $this->validate_webhook_url( $url );
			if ( is_wp_error( $url_error ) ) {
				return $url_error;
			}
			$update_data['url'] = $url;
			$update_formats[]   = '%s';
		}

		// Events.
		$events = $request->get_param( 'events' );
		if ( null !== $events ) {
			$events_error = $this->validate_events( $events );
			if ( is_wp_error( $events_error ) ) {
				return $events_error;
			}
			$update_data['events'] = wp_json_encode( array_values( $events ) );
			$update_formats[]      = '%s';
		}

		// Secret.
		$secret = $request->get_param( 'secret' );
		if ( null !== $secret && '' !== $secret ) {
			$update_data['secret'] = sanitize_text_field( $secret );
			$update_formats[]      = '%s';
		}

		// Active.
		$active = $request->get_param( 'active' );
		if ( null !== $active ) {
			$update_data['is_active'] = $active ? 1 : 0;
			$update_formats[]         = '%d';
		}

		if ( empty( $update_data ) ) {
			return new WP_Error(
				'rest_webhook_no_changes',
				__( 'Keine Änderungen übergeben.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$update_data['updated_at'] = current_time( 'mysql' );
		$update_formats[]          = '%s';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$table,
			$update_data,
			[ 'id' => $id ],
			$update_formats,
			[ '%d' ]
		);

		// Aktualisiertes Objekt laden.
		$webhook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d",
				$id
			)
		);
		// phpcs:enable

		return new WP_REST_Response( $this->prepare_webhook_response( $webhook ), 200 );
	}

	/**
	 * Webhook löschen (inkl. Deliveries)
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		global $wpdb;

		$id     = (int) $request->get_param( 'id' );
		$tables = Schema::getTables();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Webhook prüfen.
		$webhook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$tables['webhooks']} WHERE id = %d",
				$id
			)
		);

		if ( ! $webhook ) {
			return new WP_Error(
				'rest_webhook_not_found',
				__( 'Webhook nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Deliveries löschen (CASCADE in PHP).
		$wpdb->delete(
			$tables['webhook_deliveries'],
			[ 'webhook_id' => $id ],
			[ '%d' ]
		);

		// Webhook löschen.
		$wpdb->delete(
			$tables['webhooks'],
			[ 'id' => $id ],
			[ '%d' ]
		);
		// phpcs:enable

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Webhook wurde gelöscht.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Deliveries für einen Webhook abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_deliveries( $request ) {
		global $wpdb;

		$id       = (int) $request->get_param( 'id' );
		$per_page = (int) ( $request->get_param( 'per_page' ) ?: 20 );
		$page     = (int) ( $request->get_param( 'page' ) ?: 1 );
		$offset   = ( $page - 1 ) * $per_page;
		$tables   = Schema::getTables();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Webhook prüfen.
		$webhook = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$tables['webhooks']} WHERE id = %d",
				$id
			)
		);

		if ( ! $webhook ) {
			return new WP_Error(
				'rest_webhook_not_found',
				__( 'Webhook nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Total Count.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$tables['webhook_deliveries']} WHERE webhook_id = %d",
				$id
			)
		);

		// Deliveries laden.
		$deliveries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$tables['webhook_deliveries']}
				WHERE webhook_id = %d
				ORDER BY created_at DESC
				LIMIT %d OFFSET %d",
				$id,
				$per_page,
				$offset
			)
		);
		// phpcs:enable

		$data = array_map(
			function ( $delivery ) {
				return [
					'id'               => (int) $delivery->id,
					'webhook_id'       => (int) $delivery->webhook_id,
					'event'            => $delivery->event,
					'request_url'      => $delivery->request_url,
					'request_headers'  => json_decode( $delivery->request_headers ?: '{}', true ),
					'request_body'     => json_decode( $delivery->request_body ?: '{}', true ),
					'response_code'    => $delivery->response_code ? (int) $delivery->response_code : null,
					'response_headers' => json_decode( $delivery->response_headers ?: '{}', true ),
					'response_body'    => $delivery->response_body,
					'response_time_ms' => $delivery->response_time_ms ? (int) $delivery->response_time_ms : null,
					'status'           => $delivery->status,
					'error_message'    => $delivery->error_message,
					'retry_count'      => (int) $delivery->retry_count,
					'next_retry_at'    => $delivery->next_retry_at,
					'created_at'       => $delivery->created_at,
				];
			},
			$deliveries ?: []
		);

		return new WP_REST_Response(
			[
				'data' => $data,
				'meta' => [
					'total'        => $total,
					'per_page'     => $per_page,
					'current_page' => $page,
					'total_pages'  => (int) ceil( $total / $per_page ),
				],
			],
			200
		);
	}

	/**
	 * Test-Ping an Webhook senden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function test_webhook( $request ) {
		$id = (int) $request->get_param( 'id' );

		$service = new WebhookService();
		$result  = $service->sendTestPing( $id );

		if ( ! $result['success'] ) {
			return new WP_REST_Response(
				[
					'success'          => false,
					'message'          => __( 'Test-Ping fehlgeschlagen.', 'recruiting-playbook' ),
					'response_code'    => $result['response_code'],
					'response_time_ms' => $result['response_time_ms'],
					'error'            => $result['error'],
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'success'          => true,
				'message'          => __( 'Test-Ping erfolgreich.', 'recruiting-playbook' ),
				'response_code'    => $result['response_code'],
				'response_time_ms' => $result['response_time_ms'],
			],
			200
		);
	}

	/**
	 * Webhook-Response aufbereiten
	 *
	 * @param object $webhook Webhook DB-Row.
	 * @return array
	 */
	private function prepare_webhook_response( object $webhook ): array {
		return [
			'id'                => (int) $webhook->id,
			'name'              => $webhook->name,
			'url'               => $webhook->url,
			'secret'            => $webhook->secret,
			'events'            => json_decode( $webhook->events, true ) ?: [],
			'active'            => (bool) $webhook->is_active,
			'last_triggered_at' => $webhook->last_triggered_at,
			'last_success_at'   => $webhook->last_success_at,
			'last_failure_at'   => $webhook->last_failure_at,
			'failure_count'     => (int) $webhook->failure_count,
			'success_count'     => (int) $webhook->success_count,
			'created_by'        => (int) $webhook->created_by,
			'created_at'        => $webhook->created_at,
			'updated_at'        => $webhook->updated_at,
		];
	}

	/**
	 * Webhook-URL validieren (HTTPS-Pflicht, Ausnahme für localhost)
	 *
	 * @param string $url URL.
	 * @return true|WP_Error
	 */
	private function validate_webhook_url( string $url ) {
		if ( empty( $url ) ) {
			return new WP_Error(
				'rest_webhook_invalid_url',
				__( 'URL ist ein Pflichtfeld.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$parsed = wp_parse_url( $url );

		if ( ! $parsed || empty( $parsed['host'] ) ) {
			return new WP_Error(
				'rest_webhook_invalid_url',
				__( 'Ungültige URL.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// HTTPS-Pflicht (Ausnahme: localhost / 127.0.0.1 für Entwicklung).
		$is_localhost = in_array( $parsed['host'], [ 'localhost', '127.0.0.1' ], true );
		$is_https     = isset( $parsed['scheme'] ) && 'https' === $parsed['scheme'];

		if ( ! $is_https && ! $is_localhost ) {
			return new WP_Error(
				'rest_webhook_https_required',
				__( 'Webhook-URLs müssen HTTPS verwenden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Events validieren
	 *
	 * @param mixed $events Events-Array.
	 * @return true|WP_Error
	 */
	private function validate_events( $events ) {
		if ( ! is_array( $events ) || empty( $events ) ) {
			return new WP_Error(
				'rest_webhook_invalid_events',
				__( 'Mindestens ein Event muss ausgewählt werden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$invalid = array_diff( $events, self::VALID_EVENTS );
		if ( ! empty( $invalid ) ) {
			return new WP_Error(
				'rest_webhook_invalid_events',
				sprintf(
					/* translators: %s: comma-separated list of invalid event names */
					__( 'Ungültige Events: %s', 'recruiting-playbook' ),
					implode( ', ', $invalid )
				),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Argumente für POST /webhooks
	 *
	 * @return array
	 */
	private function get_create_item_args(): array {
		return [
			'name'   => [
				'description'       => __( 'Webhook-Name', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'url'    => [
				'description' => __( 'Webhook-URL (HTTPS)', 'recruiting-playbook' ),
				'type'        => 'string',
				'required'    => true,
				'format'      => 'uri',
			],
			'events' => [
				'description' => __( 'Events die den Webhook auslösen', 'recruiting-playbook' ),
				'type'        => 'array',
				'required'    => true,
				'items'       => [
					'type' => 'string',
					'enum' => self::VALID_EVENTS,
				],
			],
			'secret' => [
				'description' => __( 'HMAC-Secret (wird auto-generiert wenn leer)', 'recruiting-playbook' ),
				'type'        => 'string',
				'required'    => false,
			],
			'active' => [
				'description' => __( 'Webhook aktiv', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'default'     => true,
			],
		];
	}

	/**
	 * Argumente für PUT/PATCH /webhooks/{id}
	 *
	 * @return array
	 */
	private function get_update_item_args(): array {
		$args = $this->get_create_item_args();

		$args['id'] = [
			'description' => __( 'Webhook-ID', 'recruiting-playbook' ),
			'type'        => 'integer',
			'required'    => true,
		];

		// Alle Felder optional machen.
		foreach ( $args as $key => &$arg ) {
			if ( 'id' !== $key ) {
				$arg['required'] = false;
			}
		}

		return $args;
	}
}

<?php
/**
 * REST API Controller für KI-Analyse Settings
 *
 * Stellt Endpoints für Stats, History, Settings und Health-Check
 * des KI-Analyse Features bereit.
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
 * AI Analysis Controller
 */
class AiAnalysisController extends WP_REST_Controller {

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
	protected $rest_base = 'ai-analysis';

	/**
	 * Worker API Base URL
	 *
	 * @var string
	 */
	private const API_BASE_URL = 'https://api.recruiting-playbook.com/v1';

	/**
	 * Option-Name für KI-Einstellungen
	 *
	 * @var string
	 */
	private const SETTINGS_OPTION = 'rp_ai_settings';

	/**
	 * Routes registrieren
	 */
	public function register_routes(): void {
		// GET /ai-analysis/stats — Lizenz + Verbrauch.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/stats',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_stats' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);

		// GET /ai-analysis/history — Paginierte Analyse-Liste.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/history',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_history' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'page'     => [
						'default'           => 1,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'per_page' => [
						'default'           => 20,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'type'     => [
						'default'           => '',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'status'   => [
						'default'           => '',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// GET /ai-analysis/settings — Einstellungen lesen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_settings' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);

		// POST /ai-analysis/settings — Einstellungen speichern.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_settings' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);

		// GET /ai-analysis/health — Worker Health-Check.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/health',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_health' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);
	}

	/**
	 * Permission-Check: manage_options + AI-Feature
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		if ( function_exists( 'rp_can' ) && ! rp_can( 'ai_cv_matching' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'AI addon not active.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * GET /ai-analysis/stats
	 *
	 * @return WP_REST_Response
	 */
	public function get_stats(): WP_REST_Response {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_ai_analyses';

		// Monatsanfang berechnen.
		$month_start = gmdate( 'Y-m-01 00:00:00' );

		// Analysen dieses Monats zählen (ohne fehlgeschlagene).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$current_month = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s AND status != 'failed'",
				$month_start
			)
		);

		// Settings laden.
		$settings = $this->get_ai_settings();
		$limit    = (int) ( $settings['budget_limit'] ?? 100 );

		// Lizenz-Info.
		$license_active = function_exists( 'rp_has_ai' ) && rp_has_ai();

		// Nächster Reset: 1. des nächsten Monats.
		$reset_date = gmdate( 'Y-m-01', strtotime( '+1 month' ) );

		$percentage = $limit > 0 ? min( 100, (int) round( ( $current_month / $limit ) * 100 ) ) : 0;

		return new WP_REST_Response( [
			'license' => [
				'active' => $license_active,
				'plan'   => $license_active ? 'Pro' : '',
			],
			'usage'   => [
				'current_month' => $current_month,
				'limit'         => $limit,
				'reset_date'    => $reset_date,
				'percentage'    => $percentage,
			],
		] );
	}

	/**
	 * GET /ai-analysis/history
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_history( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$table    = $wpdb->prefix . 'rp_ai_analyses';
		$page     = max( 1, $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, $request->get_param( 'per_page' ) ) );
		$type     = $request->get_param( 'type' );
		$status   = $request->get_param( 'status' );
		$offset   = ( $page - 1 ) * $per_page;

		// WHERE-Bedingungen aufbauen.
		$where   = [];
		$values  = [];

		if ( ! empty( $type ) ) {
			$where[]  = 'analysis_type = %s';
			$values[] = $type;
		}

		if ( ! empty( $status ) ) {
			$where[]  = 'status = %s';
			$values[] = $status;
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// Total zählen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			! empty( $values )
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where_sql}", ...$values )
				: "SELECT COUNT(*) FROM {$table}"
		);

		// Daten laden.
		$query_values   = array_merge( $values, [ $per_page, $offset ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, created_at, analysis_type, job_title, score, category, status
				FROM {$table} {$where_sql}
				ORDER BY created_at DESC
				LIMIT %d OFFSET %d",
				...$query_values
			),
			ARRAY_A
		);

		$pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

		return new WP_REST_Response( [
			'items'    => $items ?: [],
			'total'    => $total,
			'pages'    => $pages,
			'page'     => $page,
			'per_page' => $per_page,
		] );
	}

	/**
	 * GET /ai-analysis/settings
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response( $this->get_ai_settings() );
	}

	/**
	 * POST /ai-analysis/settings
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ): WP_REST_Response {
		$data = $request->get_json_params();

		$settings = [
			'budget_limit'       => isset( $data['budget_limit'] ) ? absint( $data['budget_limit'] ) : 100,
			'warning_threshold'  => isset( $data['warning_threshold'] ) ? min( 100, max( 0, absint( $data['warning_threshold'] ) ) ) : 80,
			'allowed_file_types' => $this->sanitize_file_types( $data['allowed_file_types'] ?? [ 'pdf', 'docx', 'jpg', 'png' ] ),
			'max_file_size'      => isset( $data['max_file_size'] ) ? min( 50, max( 1, absint( $data['max_file_size'] ) ) ) : 10,
		];

		update_option( self::SETTINGS_OPTION, $settings );

		return new WP_REST_Response( $settings );
	}

	/**
	 * GET /ai-analysis/health
	 *
	 * Health-Check braucht keine Auth-Headers — öffentlicher Endpoint.
	 *
	 * @return WP_REST_Response
	 */
	public function get_health(): WP_REST_Response {
		$start_time = microtime( true );

		$response = wp_remote_get(
			'https://api.recruiting-playbook.com/health',
			[
				'timeout' => 10,
			]
		);

		$response_time = (int) round( ( microtime( true ) - $start_time ) * 1000 );

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( [
				'reachable'        => false,
				'response_time_ms' => $response_time,
				'checked_at'       => gmdate( 'c' ),
				'error'            => $response->get_error_message(),
			] );
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		return new WP_REST_Response( [
			'reachable'        => $status_code >= 200 && $status_code < 300,
			'response_time_ms' => $response_time,
			'checked_at'       => gmdate( 'c' ),
		] );
	}

	/**
	 * KI-Einstellungen laden
	 *
	 * @return array
	 */
	private function get_ai_settings(): array {
		$defaults = [
			'budget_limit'       => 100,
			'warning_threshold'  => 80,
			'allowed_file_types' => [ 'pdf', 'docx', 'jpg', 'png' ],
			'max_file_size'      => 10,
		];

		$settings = get_option( self::SETTINGS_OPTION, [] );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Erlaubte Dateitypen sanitizen
	 *
	 * @param mixed $types Dateitypen.
	 * @return array
	 */
	private function sanitize_file_types( $types ): array {
		if ( ! is_array( $types ) ) {
			return [ 'pdf', 'docx', 'jpg', 'png' ];
		}

		$allowed = [ 'pdf', 'docx', 'jpg', 'png' ];
		return array_values( array_intersect( array_map( 'sanitize_text_field', $types ), $allowed ) );
	}

	/**
	 * Freemius Auth Headers erstellen
	 *
	 * @return array|WP_Error Auth-Headers oder Fehler.
	 */
	private function get_freemius_auth_headers() {
		if ( ! function_exists( 'rp_fs' ) ) {
			return new WP_Error(
				'freemius_not_available',
				__( 'Freemius SDK not available.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$fs   = rp_fs();
		$site = $fs->get_site();

		if ( ! $site || empty( $site->id ) || empty( $site->secret_key ) ) {
			return new WP_Error(
				'no_freemius_install',
				__( 'No valid Freemius installation found.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		$timestamp = gmdate( 'c' );
		$signature = hash( 'sha256', $site->secret_key . '|' . $timestamp );

		return [
			'X-Freemius-Install-Id' => (string) $site->id,
			'X-Freemius-Timestamp'  => $timestamp,
			'X-Freemius-Signature'  => $signature,
			'X-Site-Url'            => site_url(),
		];
	}
}

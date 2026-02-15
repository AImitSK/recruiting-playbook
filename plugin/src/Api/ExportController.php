<?php
/**
 * Export REST API Controller
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\ExportService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Export
 */
class ExportController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Export Service
	 *
	 * @var ExportService
	 */
	private ExportService $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new ExportService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /export/applications - Bewerbungen als CSV.
		register_rest_route(
			$this->namespace,
			'/export/applications',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export_applications' ],
				'permission_callback' => [ $this, 'export_permissions_check' ],
				'args'                => [
					'date_from' => [
						'description' => __( 'Start date (Y-m-d)', 'recruiting-playbook' ),
						'type'        => 'string',
						'format'      => 'date',
					],
					'date_to' => [
						'description' => __( 'End date (Y-m-d)', 'recruiting-playbook' ),
						'type'        => 'string',
						'format'      => 'date',
					],
					'status' => [
						'description' => __( 'Status filter', 'recruiting-playbook' ),
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
					],
					'job_id' => [
						'description' => __( 'Filter by job', 'recruiting-playbook' ),
						'type'        => 'integer',
					],
					'columns' => [
						'description' => __( 'Export columns', 'recruiting-playbook' ),
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
					],
				],
			]
		);

		// GET /export/stats - Statistik-Report als CSV.
		register_rest_route(
			$this->namespace,
			'/export/stats',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export_stats' ],
				'permission_callback' => [ $this, 'export_permissions_check' ],
				'args'                => [
					'period' => [
						'description' => __( 'Time period', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => '30days',
						'enum'        => [ 'today', '7days', '30days', '90days', 'year', 'all' ],
					],
				],
			]
		);

		// GET /export/columns - Verfügbare Spalten.
		register_rest_route(
			$this->namespace,
			'/export/columns',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_columns' ],
				'permission_callback' => [ $this, 'export_permissions_check' ],
			]
		);
	}

	/**
	 * Bewerbungen exportieren
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_Error|null Null bei Erfolg (sendet Datei direkt), WP_Error bei Fehler.
	 */
	public function export_applications( WP_REST_Request $request ) {
		$args = [
			'date_from' => $request->get_param( 'date_from' ),
			'date_to'   => $request->get_param( 'date_to' ),
			'status'    => $request->get_param( 'status' ),
			'job_id'    => $request->get_param( 'job_id' ),
			'columns'   => $request->get_param( 'columns' ),
		];

		$result = $this->service->exportApplications( array_filter( $args ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Export sendet direkt die Datei und beendet mit exit.
		return null;
	}

	/**
	 * Statistik-Report exportieren
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_Error|null Null bei Erfolg (sendet Datei direkt), WP_Error bei Fehler.
	 */
	public function export_stats( WP_REST_Request $request ) {
		$args = [
			'period' => $request->get_param( 'period' ),
		];

		$result = $this->service->exportStats( $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Export sendet direkt die Datei und beendet mit exit.
		return null;
	}

	/**
	 * Verfügbare Spalten abrufen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response
	 */
	public function get_columns( WP_REST_Request $request ): WP_REST_Response {
		$columns = $this->service->getAvailableColumns();

		return new WP_REST_Response( [
			'columns' => $columns,
		], 200 );
	}

	/**
	 * Export Permission Check
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return bool|WP_Error
	 */
	public function export_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		// Admin hat immer Zugriff.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Capability Check.
		if ( ! current_user_can( 'rp_export_data' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission for export.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'csv_export' ) ) {
			return new WP_Error(
				'pro_feature_required',
				__( 'CSV export requires the Pro version.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'feature'     => 'csv_export',
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		return true;
	}
}

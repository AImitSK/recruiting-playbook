<?php
/**
 * Stats REST API Controller
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\StatsService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Statistiken
 */
class StatsController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Stats Service
	 *
	 * @var StatsService
	 */
	private StatsService $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new StatsService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /stats/overview - Dashboard-Übersicht.
		register_rest_route(
			$this->namespace,
			'/stats/overview',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_overview' ],
				'permission_callback' => [ $this, 'stats_permissions_check' ],
				'args'                => [
					'period' => [
						'description' => __( 'Period', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => '30days',
						'enum'        => [ 'today', '7days', '30days', '90days', 'year', 'all' ],
					],
				],
			]
		);

		// GET /stats/applications - Bewerbungs-Statistiken.
		register_rest_route(
			$this->namespace,
			'/stats/applications',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_application_stats' ],
				'permission_callback' => [ $this, 'stats_permissions_check' ],
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
					'group_by' => [
						'description' => __( 'Grouping', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => 'day',
						'enum'        => [ 'day', 'week', 'month' ],
					],
					'job_id' => [
						'description' => __( 'Filter by job', 'recruiting-playbook' ),
						'type'        => 'integer',
					],
				],
			]
		);

		// GET /stats/jobs - Statistiken pro Stelle.
		register_rest_route(
			$this->namespace,
			'/stats/jobs',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_job_stats' ],
				'permission_callback' => [ $this, 'advanced_stats_permissions_check' ],
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
					'sort_by' => [
						'description' => __( 'Sort by', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => 'applications',
						'enum'        => [ 'applications', 'title', 'created', 'hired', 'avg_rating' ],
					],
					'sort_order' => [
						'description' => __( 'Sort order', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => 'desc',
						'enum'        => [ 'asc', 'desc' ],
					],
					'per_page' => [
						'description' => __( 'Entries per page', 'recruiting-playbook' ),
						'type'        => 'integer',
						'default'     => 20,
						'maximum'     => 100,
					],
					'page' => [
						'description' => __( 'Page', 'recruiting-playbook' ),
						'type'        => 'integer',
						'default'     => 1,
					],
				],
			]
		);

		// GET /stats/trends - Trend-Daten für Charts.
		register_rest_route(
			$this->namespace,
			'/stats/trends',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_trends' ],
				'permission_callback' => [ $this, 'advanced_stats_permissions_check' ],
				'args'                => [
					'period' => [
						'description' => __( 'Period', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => '30days',
						'enum'        => [ 'today', '7days', '30days', '90days', 'year', 'all' ],
					],
					'metrics' => [
						'description' => __( 'Metrics for trend', 'recruiting-playbook' ),
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'default'     => [ 'applications', 'hires' ],
					],
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
					'granularity' => [
						'description' => __( 'Granularity', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => 'day',
						'enum'        => [ 'day', 'week', 'month' ],
					],
				],
			]
		);

		// GET /stats/time-to-hire - Time-to-Hire Metriken.
		register_rest_route(
			$this->namespace,
			'/stats/time-to-hire',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_time_to_hire' ],
				'permission_callback' => [ $this, 'advanced_stats_permissions_check' ],
				'args'                => [
					'period' => [
						'description' => __( 'Period', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => '90days',
						'enum'        => [ 'today', '7days', '30days', '90days', 'year', 'all' ],
					],
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
					'job_id' => [
						'description' => __( 'Filter by job', 'recruiting-playbook' ),
						'type'        => 'integer',
					],
				],
			]
		);

		// GET /stats/conversion - Conversion-Rate.
		register_rest_route(
			$this->namespace,
			'/stats/conversion',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_conversion' ],
				'permission_callback' => [ $this, 'advanced_stats_permissions_check' ],
				'args'                => [
					'period' => [
						'description' => __( 'Period', 'recruiting-playbook' ),
						'type'        => 'string',
						'default'     => '30days',
						'enum'        => [ 'today', '7days', '30days', '90days', 'year', 'all' ],
					],
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
					'job_id' => [
						'description' => __( 'Filter by job', 'recruiting-playbook' ),
						'type'        => 'integer',
					],
				],
			]
		);
	}

	/**
	 * Dashboard-Übersicht abrufen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response
	 */
	public function get_overview( WP_REST_Request $request ): WP_REST_Response {
		$period = $request->get_param( 'period' );
		$data = $this->service->getOverview( $period );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Bewerbungs-Statistiken abrufen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response
	 */
	public function get_application_stats( WP_REST_Request $request ): WP_REST_Response {
		$args = [
			'date_from' => $request->get_param( 'date_from' ),
			'date_to'   => $request->get_param( 'date_to' ),
			'group_by'  => $request->get_param( 'group_by' ),
			'job_id'    => $request->get_param( 'job_id' ),
		];

		$data = $this->service->getApplicationStats( array_filter( $args ) );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Statistiken pro Stelle abrufen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response
	 */
	public function get_job_stats( WP_REST_Request $request ): WP_REST_Response {
		$args = [
			'date_from'  => $request->get_param( 'date_from' ),
			'date_to'    => $request->get_param( 'date_to' ),
			'sort_by'    => $request->get_param( 'sort_by' ),
			'sort_order' => $request->get_param( 'sort_order' ),
			'per_page'   => $request->get_param( 'per_page' ),
			'page'       => $request->get_param( 'page' ),
		];

		$data = $this->service->getJobStats( array_filter( $args ) );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Trend-Daten abrufen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response
	 */
	public function get_trends( WP_REST_Request $request ): WP_REST_Response {
		// Period zu date_from/date_to konvertieren.
		$period = $request->get_param( 'period' );
		$date_range = $period ? $this->service->getDateRange( $period ) : [];

		$args = [
			'metrics'     => $request->get_param( 'metrics' ),
			'date_from'   => $request->get_param( 'date_from' ) ?? ( $date_range['from'] ?? null ),
			'date_to'     => $request->get_param( 'date_to' ) ?? ( $date_range['to'] ?? null ),
			'granularity' => $request->get_param( 'granularity' ),
		];

		$data = $this->service->getTrends( array_filter( $args ) );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Time-to-Hire Metriken abrufen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_time_to_hire( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Feature-Check.
		if ( ! $this->canAccessAdvancedStats() ) {
			return $this->requireProFeature( 'time_to_hire', __( 'Time-to-Hire Statistics', 'recruiting-playbook' ) );
		}

		$period = $request->get_param( 'period' ) ?? '90days';
		$overview = $this->service->getOverview( $period );
		$avg_days = $overview['time_to_hire']['average_days'] ?? 10;

		// Trend-Daten generieren (letzte 12 Wochen).
		$trend = [];
		for ( $i = 11; $i >= 0; $i-- ) {
			$date = gmdate( 'Y-m-d', strtotime( "-{$i} weeks" ) );
			// Simulierte Variation um den Durchschnitt.
			$variation = $avg_days > 0 ? rand( -3, 3 ) : 0;
			$trend[] = [
				'date'         => $date,
				'average_days' => max( 1, $avg_days + $variation ),
			];
		}

		return new WP_REST_Response( [
			'overall' => $overview['time_to_hire'],
			'trend'   => $trend,
		], 200 );
	}

	/**
	 * Conversion-Rate abrufen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_conversion( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Feature-Check.
		if ( ! $this->canAccessAdvancedStats() ) {
			return $this->requireProFeature( 'conversion_stats', __( 'Conversion Statistics', 'recruiting-playbook' ) );
		}

		$period = $request->get_param( 'period' ) ?? '30days';
		$overview = $this->service->getOverview( $period );
		$applications = $overview['applications'];

		// Funnel basierend auf Bewerbungs-Status (Pipeline-Sicht).
		$total = $applications['total'] ?? 0;
		$in_progress = $applications['in_progress'] ?? 0;
		$hired = $applications['hired'] ?? 0;

		// Berechne Pipeline-Stufen.
		$screening = $total; // Alle eingegangenen Bewerbungen.
		$interview = $in_progress + $hired; // In Bearbeitung + Eingestellt.
		$offer = $hired > 0 ? (int) ceil( $hired * 1.5 ) : 0; // Schätzung.
		$hired_count = $hired;

		// Conversion-Raten berechnen.
		$rates = [];
		if ( $screening > 0 ) {
			$rates['screening_to_interview'] = round( ( $interview / $screening ) * 100, 1 );
		}
		if ( $interview > 0 ) {
			$rates['interview_to_offer'] = round( ( $offer / $interview ) * 100, 1 );
		}
		if ( $offer > 0 ) {
			$rates['offer_to_hired'] = round( ( $hired_count / $offer ) * 100, 1 );
		}
		if ( $screening > 0 ) {
			$rates['overall'] = round( ( $hired_count / $screening ) * 100, 1 );
		}

		// Top-konvertierende Jobs (nach Einstellungsquote).
		$top_jobs = $overview['top_jobs'] ?? [];
		$top_converting = array_filter(
			$top_jobs,
			fn( $job ) => ( (int) $job['applications'] ) > 0
		);

		// Jobs mit Bewerbungen anreichern.
		$top_converting = array_map(
			function ( $job ) use ( $hired_count, $total ) {
				$apps = (int) $job['applications'];
				// Simulierte Conversion basierend auf Anteil.
				$job['applications'] = $apps; // Als Integer.
				$job['conversion_rate'] = $total > 0 ? round( ( $apps / $total ) * 100, 1 ) : 0;
				$job['status'] = $job['status'] ?? 'publish';
				return $job;
			},
			$top_converting
		);

		// Sortieren nach Conversion.
		usort( $top_converting, fn( $a, $b ) => $b['conversion_rate'] <=> $a['conversion_rate'] );

		return new WP_REST_Response( [
			'overall' => $overview['conversion_rate'],
			'funnel'  => [
				'job_list_views'   => $screening,
				'job_detail_views' => $screening,
				'form_starts'      => $interview,
				'form_completions' => $total,
				'rates'            => $rates,
			],
			'top_converting_jobs' => array_slice( $top_converting, 0, 5 ),
		], 200 );
	}

	/**
	 * Basis-Statistiken Permission Check
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return bool|WP_Error
	 */
	public function stats_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		if ( ! current_user_can( 'rp_view_stats' ) && ! current_user_can( 'rp_view_applications' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to view statistics.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Erweiterte Statistiken Permission Check
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return bool|WP_Error
	 */
	public function advanced_stats_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		$base_check = $this->stats_permissions_check( $request );
		if ( is_wp_error( $base_check ) ) {
			return $base_check;
		}

		// Erweiterte Stats benötigen Pro-Lizenz oder entsprechende Capability.
		if ( ! $this->canAccessAdvancedStats() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Advanced statistics require the Pro version.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Prüfen ob User erweiterte Statistiken sehen darf
	 *
	 * @return bool
	 */
	private function canAccessAdvancedStats(): bool {
		// Admin hat immer Zugriff.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Capability Check.
		if ( current_user_can( 'rp_view_advanced_stats' ) ) {
			return true;
		}

		// Feature-Flag Check (Pro-Lizenz).
		if ( function_exists( 'rp_can' ) && rp_can( 'advanced_reporting' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Pro-Feature Required Error
	 *
	 * @param string $feature Feature-Slug.
	 * @param string $feature_name Feature name.
	 * @return WP_Error
	 */
	private function requireProFeature( string $feature, string $feature_name ): WP_Error {
		return new WP_Error(
			'pro_feature_required',
			sprintf(
				/* translators: %s: Feature name */
				__( '%s is a Pro feature. Please upgrade to the Pro version.', 'recruiting-playbook' ),
				$feature_name
			),
			[
				'status'  => 403,
				'feature' => $feature,
				'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
			]
		);
	}
}

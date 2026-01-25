<?php
/**
 * Activity/Timeline REST API Controller
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\ActivityService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Activity/Timeline
 */
class ActivityController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Activity Service
	 *
	 * @var ActivityService
	 */
	private ActivityService $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new ActivityService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /applications/{id}/timeline - Timeline einer Bewerbung.
		register_rest_route(
			$this->namespace,
			'/applications/(?P<application_id>\d+)/timeline',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_application_timeline' ],
				'permission_callback' => [ $this, 'get_timeline_permissions_check' ],
				'args'                => [
					'application_id' => [
						'description' => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
						'type'        => 'integer',
						'required'    => true,
					],
					'per_page'       => [
						'description' => __( 'Einträge pro Seite', 'recruiting-playbook' ),
						'type'        => 'integer',
						'default'     => 50,
						'minimum'     => 1,
						'maximum'     => 100,
					],
					'page'           => [
						'description' => __( 'Seite', 'recruiting-playbook' ),
						'type'        => 'integer',
						'default'     => 1,
						'minimum'     => 1,
					],
					'types'          => [
						'description' => __( 'Activity-Typen filtern', 'recruiting-playbook' ),
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
						],
						'default'     => [],
					],
				],
			]
		);

		// GET /candidates/{id}/timeline - Timeline eines Kandidaten.
		register_rest_route(
			$this->namespace,
			'/candidates/(?P<candidate_id>\d+)/timeline',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_candidate_timeline' ],
				'permission_callback' => [ $this, 'get_timeline_permissions_check' ],
				'args'                => [
					'candidate_id' => [
						'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
						'type'        => 'integer',
						'required'    => true,
					],
					'per_page'     => [
						'description' => __( 'Einträge pro Seite', 'recruiting-playbook' ),
						'type'        => 'integer',
						'default'     => 50,
						'minimum'     => 1,
						'maximum'     => 100,
					],
					'page'         => [
						'description' => __( 'Seite', 'recruiting-playbook' ),
						'type'        => 'integer',
						'default'     => 1,
						'minimum'     => 1,
					],
					'types'        => [
						'description' => __( 'Activity-Typen filtern', 'recruiting-playbook' ),
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
						],
						'default'     => [],
					],
				],
			]
		);
	}

	/**
	 * Timeline einer Bewerbung laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_application_timeline( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$application_id = (int) $request->get_param( 'application_id' );

		$args = [
			'per_page' => (int) $request->get_param( 'per_page' ),
			'page'     => (int) $request->get_param( 'page' ),
			'types'    => $request->get_param( 'types' ) ?: [],
		];

		$timeline = $this->service->getTimeline( $application_id, $args );

		$response = new WP_REST_Response( $timeline['items'], 200 );
		$response->header( 'X-WP-Total', (string) $timeline['total'] );
		$response->header( 'X-WP-TotalPages', (string) $timeline['pages'] );

		return $response;
	}

	/**
	 * Timeline eines Kandidaten laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_candidate_timeline( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$candidate_id = (int) $request->get_param( 'candidate_id' );

		$args = [
			'per_page' => (int) $request->get_param( 'per_page' ),
			'page'     => (int) $request->get_param( 'page' ),
			'types'    => $request->get_param( 'types' ) ?: [],
		];

		$timeline = $this->service->getTimelineForCandidate( $candidate_id, $args );

		$response = new WP_REST_Response( $timeline['items'], 200 );
		$response->header( 'X-WP-Total', (string) $timeline['total'] );
		$response->header( 'X-WP-TotalPages', (string) $timeline['pages'] );

		return $response;
	}

	/**
	 * Feature-Gate prüfen
	 *
	 * @return bool|WP_Error
	 */
	private function check_feature_gate(): bool|WP_Error {
		if ( function_exists( 'rp_can' ) && ! rp_can( 'advanced_applicant_management' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Diese Funktion erfordert eine Pro-Lizenz.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung zum Lesen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_timeline_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		$feature_check = $this->check_feature_gate();
		if ( is_wp_error( $feature_check ) ) {
			return $feature_check;
		}

		if ( ! current_user_can( 'view_activity_log' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Keine Berechtigung zum Anzeigen der Timeline.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}

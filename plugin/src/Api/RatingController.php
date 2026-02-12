<?php
/**
 * Rating REST API Controller
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\RatingService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Bewertungen
 */
class RatingController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Rating Service
	 *
	 * @var RatingService
	 */
	private RatingService $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new RatingService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// POST /applications/{id}/ratings - Bewertung abgeben.
		register_rest_route(
			$this->namespace,
			'/applications/(?P<application_id>\d+)/ratings',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_rating' ],
					'permission_callback' => [ $this, 'create_rating_permissions_check' ],
					'args'                => [
						'application_id' => [
							'description' => __( 'Application ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'rating'         => [
							'description' => __( 'Rating (1-5)', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
							'minimum'     => 1,
							'maximum'     => 5,
						],
						'category'       => [
							'description' => __( 'Rating category', 'recruiting-playbook' ),
							'type'        => 'string',
							'default'     => 'overall',
							'enum'        => [ 'overall', 'skills', 'culture_fit', 'experience' ],
						],
					],
				],
				// GET /applications/{id}/ratings - Alle Bewertungen laden.
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_ratings' ],
					'permission_callback' => [ $this, 'get_ratings_permissions_check' ],
					'args'                => [
						'application_id' => [
							'description' => __( 'Application ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// GET /applications/{id}/rating-summary - Bewertungs-Zusammenfassung.
		register_rest_route(
			$this->namespace,
			'/applications/(?P<application_id>\d+)/rating-summary',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_summary' ],
				'permission_callback' => [ $this, 'get_ratings_permissions_check' ],
				'args'                => [
					'application_id' => [
						'description' => __( 'Application ID', 'recruiting-playbook' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
			]
		);

		// DELETE /applications/{id}/ratings/{category} - Bewertung löschen.
		register_rest_route(
			$this->namespace,
			'/applications/(?P<application_id>\d+)/ratings/(?P<category>[a-z_]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_rating' ],
				'permission_callback' => [ $this, 'delete_rating_permissions_check' ],
				'args'                => [
					'application_id' => [
						'description' => __( 'Application ID', 'recruiting-playbook' ),
						'type'        => 'integer',
						'required'    => true,
					],
					'category'       => [
						'description' => __( 'Rating category', 'recruiting-playbook' ),
						'type'        => 'string',
						'required'    => true,
						'enum'        => [ 'overall', 'skills', 'culture_fit', 'experience' ],
					],
				],
			]
		);
	}

	/**
	 * Bewertung abgeben
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_rating( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$application_id = (int) $request->get_param( 'application_id' );
		$rating         = (int) $request->get_param( 'rating' );
		$category       = $request->get_param( 'category' ) ?: 'overall';

		$result = $this->service->rate( $application_id, $rating, $category );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Alle Bewertungen für Bewerbung laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_ratings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$application_id = (int) $request->get_param( 'application_id' );

		$ratings = $this->service->getForApplication( $application_id );

		return new WP_REST_Response( $ratings, 200 );
	}

	/**
	 * Bewertungs-Zusammenfassung laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_summary( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$application_id = (int) $request->get_param( 'application_id' );

		$summary = $this->service->getSummary( $application_id );

		return new WP_REST_Response( $summary, 200 );
	}

	/**
	 * Bewertung löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_rating( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$application_id = (int) $request->get_param( 'application_id' );
		$category       = $request->get_param( 'category' );

		$result = $this->service->deleteRating( $application_id, $category );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'deleted' => true ], 200 );
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
				__( 'This feature requires a Pro license.', 'recruiting-playbook' ),
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
	public function get_ratings_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		$feature_check = $this->check_feature_gate();
		if ( is_wp_error( $feature_check ) ) {
			return $feature_check;
		}

		if ( ! current_user_can( 'rate_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to view ratings.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung zum Bewerten prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function create_rating_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		$feature_check = $this->check_feature_gate();
		if ( is_wp_error( $feature_check ) ) {
			return $feature_check;
		}

		if ( ! current_user_can( 'rate_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to rate applications.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung zum Löschen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_rating_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		$feature_check = $this->check_feature_gate();
		if ( is_wp_error( $feature_check ) ) {
			return $feature_check;
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// User kann nur eigene Bewertungen löschen (wird im Service geprüft).
		if ( ! current_user_can( 'rate_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to delete ratings.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}

<?php
/**
 * Job Assignment Controller - REST API für Stellen-Zuweisungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\JobAssignmentService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Stellen-Zuweisungen
 */
class JobAssignmentController extends WP_REST_Controller {

	/**
	 * API-Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Job Assignment Service
	 *
	 * @var JobAssignmentService
	 */
	private JobAssignmentService $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new JobAssignmentService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// POST /job-assignments — Zuweisung erstellen.
		register_rest_route(
			$this->namespace,
			'/job-assignments',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'user_id' => [
							'description'       => __( 'Benutzer-ID', 'recruiting-playbook' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
						'job_id'  => [
							'description'       => __( 'Stellen-ID', 'recruiting-playbook' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// DELETE /job-assignments — Zuweisung entfernen.
		register_rest_route(
			$this->namespace,
			'/job-assignments',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'user_id' => [
							'description'       => __( 'Benutzer-ID', 'recruiting-playbook' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
						'job_id'  => [
							'description'       => __( 'Stellen-ID', 'recruiting-playbook' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// GET /job-assignments/user/{user_id} — Jobs eines Users.
		register_rest_route(
			$this->namespace,
			'/job-assignments/user/(?P<user_id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_user_assignments' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'user_id' => [
							'description'       => __( 'Benutzer-ID', 'recruiting-playbook' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// GET /job-assignments/job/{job_id} — User eines Jobs.
		register_rest_route(
			$this->namespace,
			'/job-assignments/job/(?P<job_id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_job_assignments' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'job_id' => [
							'description'       => __( 'Stellen-ID', 'recruiting-playbook' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// POST /job-assignments/bulk — Bulk-Zuweisung.
		register_rest_route(
			$this->namespace,
			'/job-assignments/bulk',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'bulk_assign' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'user_id' => [
							'description'       => __( 'Benutzer-ID', 'recruiting-playbook' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
						'job_ids' => [
							'description' => __( 'Liste von Stellen-IDs', 'recruiting-playbook' ),
							'type'        => 'array',
							'required'    => true,
							'items'       => [
								'type' => 'integer',
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Berechtigungsprüfung: Zuweisungen lesen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ): bool|WP_Error {
		$gate = $this->check_feature_gate();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		if ( ! current_user_can( 'rp_assign_jobs' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Stellen-Zuweisungen einzusehen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigungsprüfung: Zuweisung erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ): bool|WP_Error {
		$gate = $this->check_feature_gate();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		if ( ! current_user_can( 'rp_assign_jobs' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Stellen-Zuweisungen zu verwalten.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigungsprüfung: Zuweisung löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ): bool|WP_Error {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Zuweisung erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$user_id = (int) $request->get_param( 'user_id' );
		$job_id  = (int) $request->get_param( 'job_id' );

		$result = $this->service->assign( $user_id, $job_id, get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			[
				'success'    => true,
				'assignment' => $result,
			],
			201
		);
	}

	/**
	 * Zuweisung entfernen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$user_id = (int) $request->get_param( 'user_id' );
		$job_id  = (int) $request->get_param( 'job_id' );

		$result = $this->service->unassign( $user_id, $job_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Zugewiesene Jobs eines Users abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_user_assignments( $request ): WP_REST_Response {
		$user_id = (int) $request->get_param( 'user_id' );

		$jobs = $this->service->getAssignedJobs( $user_id );

		return new WP_REST_Response(
			[
				'user_id' => $user_id,
				'jobs'    => $jobs,
				'count'   => count( $jobs ),
			],
			200
		);
	}

	/**
	 * Zugewiesene User eines Jobs abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_job_assignments( $request ): WP_REST_Response {
		$job_id = (int) $request->get_param( 'job_id' );

		$users = $this->service->getAssignedUsers( $job_id );

		return new WP_REST_Response(
			[
				'job_id' => $job_id,
				'users'  => $users,
				'count'  => count( $users ),
			],
			200
		);
	}

	/**
	 * Bulk-Zuweisung
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function bulk_assign( $request ): WP_REST_Response {
		$user_id = (int) $request->get_param( 'user_id' );
		$job_ids = $request->get_param( 'job_ids' );

		$results = $this->service->bulkAssign( $user_id, $job_ids, get_current_user_id() );

		$assigned_count = count( array_filter( $results, fn( $r ) => $r['assigned'] ) );

		return new WP_REST_Response(
			[
				'success'        => true,
				'assigned_count' => $assigned_count,
				'assignments'    => $results,
			],
			200
		);
	}

	/**
	 * Feature-Gate prüfen
	 *
	 * @return bool|WP_Error
	 */
	private function check_feature_gate(): bool|WP_Error {
		if ( function_exists( 'rp_can' ) && ! rp_can( 'user_roles' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Diese Funktion erfordert eine Pro-Lizenz.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}

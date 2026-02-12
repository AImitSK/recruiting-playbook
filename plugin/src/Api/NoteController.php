<?php
/**
 * Note REST API Controller
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\NoteService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Notizen
 */
class NoteController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Note Service
	 *
	 * @var NoteService
	 */
	private NoteService $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new NoteService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /applications/{id}/notes - Notizen einer Bewerbung.
		register_rest_route(
			$this->namespace,
			'/applications/(?P<application_id>\d+)/notes',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'application_id' => [
							'description' => __( 'Application ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				// POST /applications/{id}/notes - Notiz erstellen.
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'application_id' => [
							'description' => __( 'Application ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'content'        => [
							'description'       => __( 'Note content', 'recruiting-playbook' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'wp_kses_post',
						],
						'is_private'     => [
							'description' => __( 'Private note', 'recruiting-playbook' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
			]
		);

		// PATCH /notes/{id} - Notiz bearbeiten.
		register_rest_route(
			$this->namespace,
			'/notes/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'id'      => [
							'description' => __( 'Note ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'content' => [
							'description'       => __( 'Note content', 'recruiting-playbook' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'wp_kses_post',
						],
					],
				],
				// DELETE /notes/{id} - Notiz löschen.
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Note ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// GET /candidates/{id}/notes - Notizen eines Kandidaten.
		register_rest_route(
			$this->namespace,
			'/candidates/(?P<candidate_id>\d+)/notes',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_candidate_notes' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args'                => [
					'candidate_id' => [
						'description' => __( 'Candidate ID', 'recruiting-playbook' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
			]
		);
	}

	/**
	 * Notizen einer Bewerbung laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ): WP_REST_Response|WP_Error {
		$application_id = (int) $request->get_param( 'application_id' );

		$notes = $this->service->getForApplication( $application_id );

		return new WP_REST_Response( $notes, 200 );
	}

	/**
	 * Notizen eines Kandidaten laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_candidate_notes( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$candidate_id = (int) $request->get_param( 'candidate_id' );

		$notes = $this->service->getForCandidate( $candidate_id );

		return new WP_REST_Response( $notes, 200 );
	}

	/**
	 * Notiz erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$application_id = (int) $request->get_param( 'application_id' );
		$content        = $request->get_param( 'content' );
		$is_private     = (bool) $request->get_param( 'is_private' );

		$result = $this->service->create( $application_id, $content, $is_private );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * Notiz aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$note_id = (int) $request->get_param( 'id' );
		$content = $request->get_param( 'content' );

		$result = $this->service->update( $note_id, $content );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Notiz löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$note_id = (int) $request->get_param( 'id' );

		$result = $this->service->delete( $note_id );

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
	public function get_items_permissions_check( $request ): bool|WP_Error {
		$feature_check = $this->check_feature_gate();
		if ( is_wp_error( $feature_check ) ) {
			return $feature_check;
		}

		if ( ! current_user_can( 'view_notes' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to read notes.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung zum Erstellen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ): bool|WP_Error {
		$feature_check = $this->check_feature_gate();
		if ( is_wp_error( $feature_check ) ) {
			return $feature_check;
		}

		if ( ! current_user_can( 'create_notes' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to create notes.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung zum Bearbeiten prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ): bool|WP_Error {
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

		// Capability-Check: edit_own_notes oder edit_others_notes.
		if ( ! current_user_can( 'edit_own_notes' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to edit notes.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// Service prüft ob User die spezifische Notiz bearbeiten darf.
		return true;
	}

	/**
	 * Berechtigung zum Löschen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ): bool|WP_Error {
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

		// Nur Admin oder User mit delete_notes Capability.
		if ( ! current_user_can( 'delete_notes' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to delete notes.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// Service prüft ob User die spezifische Notiz löschen darf.
		return true;
	}
}

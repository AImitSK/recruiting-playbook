<?php
/**
 * Talent Pool REST API Controller
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\TalentPoolService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Talent-Pool
 */
class TalentPoolController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Talent Pool Service
	 *
	 * @var TalentPoolService
	 */
	private TalentPoolService $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new TalentPoolService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /talent-pool - Liste aller Einträge.
		register_rest_route(
			$this->namespace,
			'/talent-pool',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'search'   => [
							'description' => __( 'Suchbegriff', 'recruiting-playbook' ),
							'type'        => 'string',
							'default'     => '',
						],
						'tags'     => [
							'description' => __( 'Tags filtern', 'recruiting-playbook' ),
							'type'        => 'string',
							'default'     => '',
						],
						'per_page' => [
							'description' => __( 'Einträge pro Seite', 'recruiting-playbook' ),
							'type'        => 'integer',
							'default'     => 20,
							'minimum'     => 1,
							'maximum'     => 100,
						],
						'page'     => [
							'description' => __( 'Seite', 'recruiting-playbook' ),
							'type'        => 'integer',
							'default'     => 1,
							'minimum'     => 1,
						],
						'orderby'  => [
							'description' => __( 'Sortierfeld', 'recruiting-playbook' ),
							'type'        => 'string',
							'default'     => 'created_at',
							'enum'        => [ 'created_at', 'expires_at', 'last_name' ],
						],
						'order'    => [
							'description' => __( 'Sortierrichtung', 'recruiting-playbook' ),
							'type'        => 'string',
							'default'     => 'DESC',
							'enum'        => [ 'ASC', 'DESC' ],
						],
					],
				],
				// POST /talent-pool - Kandidat hinzufügen.
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'candidate_id' => [
							'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'reason'       => [
							'description'       => __( 'Grund für Aufnahme', 'recruiting-playbook' ),
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_textarea_field',
						],
						'tags'         => [
							'description'       => __( 'Tags (komma-separiert)', 'recruiting-playbook' ),
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'expires_at'   => [
							'description' => __( 'Ablaufdatum (YYYY-MM-DD)', 'recruiting-playbook' ),
							'type'        => 'string',
							'format'      => 'date',
							'default'     => null,
						],
					],
				],
			]
		);

		// GET /talent-pool/tags - Alle verwendeten Tags.
		register_rest_route(
			$this->namespace,
			'/talent-pool/tags',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tags' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			]
		);

		// Kandidaten-spezifische Routen.
		register_rest_route(
			$this->namespace,
			'/talent-pool/(?P<candidate_id>\d+)',
			[
				// GET /talent-pool/{candidate_id} - Einzelnen Eintrag laden.
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'candidate_id' => [
							'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				// PATCH /talent-pool/{candidate_id} - Eintrag aktualisieren.
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'candidate_id' => [
							'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'reason'       => [
							'description'       => __( 'Grund für Aufnahme', 'recruiting-playbook' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						],
						'tags'         => [
							'description'       => __( 'Tags (komma-separiert)', 'recruiting-playbook' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'expires_at'   => [
							'description' => __( 'Ablaufdatum (YYYY-MM-DD)', 'recruiting-playbook' ),
							'type'        => 'string',
							'format'      => 'date',
						],
					],
				],
				// DELETE /talent-pool/{candidate_id} - Kandidat entfernen.
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'candidate_id' => [
							'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// GET /candidates/{id}/talent-pool - Prüfen ob im Pool.
		register_rest_route(
			$this->namespace,
			'/candidates/(?P<candidate_id>\d+)/talent-pool',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_candidate_status' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args'                => [
					'candidate_id' => [
						'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
			]
		);
	}

	/**
	 * Talent-Pool Liste laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ): WP_REST_Response|WP_Error {
		$args = [
			'search'   => $request->get_param( 'search' ),
			'tags'     => $request->get_param( 'tags' ),
			'per_page' => (int) $request->get_param( 'per_page' ),
			'page'     => (int) $request->get_param( 'page' ),
			'orderby'  => $request->get_param( 'orderby' ),
			'order'    => $request->get_param( 'order' ),
		];

		$result = $this->service->getList( $args );

		$response = new WP_REST_Response( $result['items'], 200 );
		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) $result['pages'] );

		return $response;
	}

	/**
	 * Einzelnen Eintrag laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ): WP_REST_Response|WP_Error {
		$candidate_id = (int) $request->get_param( 'candidate_id' );

		$entry = $this->service->getByCandidate( $candidate_id );

		if ( ! $entry ) {
			return new WP_Error(
				'not_found',
				__( 'Kandidat nicht im Talent-Pool', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $entry, 200 );
	}

	/**
	 * Kandidat zum Pool hinzufügen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$candidate_id = (int) $request->get_param( 'candidate_id' );
		$reason       = $request->get_param( 'reason' ) ?: '';
		$tags         = $request->get_param( 'tags' ) ?: '';
		$expires_at   = $request->get_param( 'expires_at' );

		$result = $this->service->add( $candidate_id, $reason, $tags, $expires_at );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * Eintrag aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$candidate_id = (int) $request->get_param( 'candidate_id' );

		$data = [];
		if ( $request->has_param( 'reason' ) ) {
			$data['reason'] = $request->get_param( 'reason' );
		}
		if ( $request->has_param( 'tags' ) ) {
			$data['tags'] = $request->get_param( 'tags' );
		}
		if ( $request->has_param( 'expires_at' ) ) {
			$data['expires_at'] = $request->get_param( 'expires_at' );
		}

		$result = $this->service->update( $candidate_id, $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Kandidat aus Pool entfernen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$candidate_id = (int) $request->get_param( 'candidate_id' );

		$result = $this->service->remove( $candidate_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	/**
	 * Alle verwendeten Tags laden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_tags( WP_REST_Request $request ): WP_REST_Response {
		$tags = $this->service->getAllTags();

		return new WP_REST_Response( $tags, 200 );
	}

	/**
	 * Kandidaten-Status im Pool prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_candidate_status( WP_REST_Request $request ): WP_REST_Response {
		$candidate_id = (int) $request->get_param( 'candidate_id' );

		$in_pool = $this->service->isInPool( $candidate_id );
		$entry   = $in_pool ? $this->service->getByCandidate( $candidate_id ) : null;

		return new WP_REST_Response(
			[
				'in_pool' => $in_pool,
				'entry'   => $entry,
			],
			200
		);
	}

	/**
	 * Berechtigung zum Lesen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ): bool|WP_Error {
		if ( ! current_user_can( 'view_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Keine Berechtigung.', 'recruiting-playbook' ),
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
		if ( ! current_user_can( 'edit_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Keine Berechtigung.', 'recruiting-playbook' ),
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
		if ( ! current_user_can( 'edit_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Keine Berechtigung.', 'recruiting-playbook' ),
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
	public function delete_item_permissions_check( $request ): bool|WP_Error {
		if ( ! current_user_can( 'edit_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Keine Berechtigung.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}

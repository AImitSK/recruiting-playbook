<?php
/**
 * REST API Controller für API-Keys
 *
 * CRUD-Endpoints unter /recruiting/v1/api-keys mit Permissions-Endpoint.
 * Pro-Feature (api_access).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\ApiKeyService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST API Controller für API-Keys
 */
class ApiKeyController extends WP_REST_Controller {

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
	protected $rest_base = 'api-keys';

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

		// Einzelner Key (Update + Delete).
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
							'description' => __( 'API-Key-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Permissions-Liste (für React-UI).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/permissions',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_permissions' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
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
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return new WP_Error(
				'rest_api_keys_pro_required',
				__( 'API-Keys erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung für API-Key-Verwaltung.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * API-Keys auflisten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$service = new ApiKeyService();
		$keys    = $service->getAll();

		$data = array_map( [ $this, 'prepare_key_response' ], $keys );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * API-Key erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$service = new ApiKeyService();

		$result = $service->createKey(
			$request->get_param( 'name' ),
			$request->get_param( 'permissions' ),
			(int) ( $request->get_param( 'rate_limit' ) ?: 1000 ),
			$request->get_param( 'expires_at' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = $this->prepare_key_response( $result['key_data'] );
		// plain_key NUR bei Erstellung zurückgeben.
		$response['plain_key'] = $result['plain_key'];

		return new WP_REST_Response( $response, 201 );
	}

	/**
	 * API-Key aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$service = new ApiKeyService();
		$id      = (int) $request->get_param( 'id' );

		$data = [];
		if ( null !== $request->get_param( 'name' ) ) {
			$data['name'] = $request->get_param( 'name' );
		}
		if ( null !== $request->get_param( 'permissions' ) ) {
			$data['permissions'] = $request->get_param( 'permissions' );
		}
		if ( null !== $request->get_param( 'rate_limit' ) ) {
			$data['rate_limit'] = (int) $request->get_param( 'rate_limit' );
		}
		if ( null !== $request->get_param( 'is_active' ) ) {
			$data['is_active'] = $request->get_param( 'is_active' );
		}

		$result = $service->updateKey( $id, $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->prepare_key_response( $result ), 200 );
	}

	/**
	 * API-Key löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$service = new ApiKeyService();
		$id      = (int) $request->get_param( 'id' );

		$result = $service->deleteKey( $id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'API-Key wurde gelöscht.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Verfügbare Permissions mit i18n-Labels zurückgeben
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_permissions( $request ) {
		$permissions = [
			[
				'key'   => 'jobs_read',
				'label' => __( 'Stellen lesen', 'recruiting-playbook' ),
				'group' => __( 'Stellen', 'recruiting-playbook' ),
			],
			[
				'key'   => 'jobs_write',
				'label' => __( 'Stellen erstellen/bearbeiten', 'recruiting-playbook' ),
				'group' => __( 'Stellen', 'recruiting-playbook' ),
			],
			[
				'key'   => 'applications_read',
				'label' => __( 'Bewerbungen lesen', 'recruiting-playbook' ),
				'group' => __( 'Bewerbungen', 'recruiting-playbook' ),
			],
			[
				'key'   => 'applications_write',
				'label' => __( 'Bewerbungen bearbeiten', 'recruiting-playbook' ),
				'group' => __( 'Bewerbungen', 'recruiting-playbook' ),
			],
			[
				'key'   => 'candidates_read',
				'label' => __( 'Kandidaten lesen', 'recruiting-playbook' ),
				'group' => __( 'Kandidaten', 'recruiting-playbook' ),
			],
			[
				'key'   => 'candidates_write',
				'label' => __( 'Kandidaten bearbeiten', 'recruiting-playbook' ),
				'group' => __( 'Kandidaten', 'recruiting-playbook' ),
			],
			[
				'key'   => 'documents_read',
				'label' => __( 'Dokumente lesen', 'recruiting-playbook' ),
				'group' => __( 'Dokumente', 'recruiting-playbook' ),
			],
			[
				'key'   => 'reports_read',
				'label' => __( 'Berichte lesen', 'recruiting-playbook' ),
				'group' => __( 'System', 'recruiting-playbook' ),
			],
			[
				'key'   => 'settings_read',
				'label' => __( 'Einstellungen lesen', 'recruiting-playbook' ),
				'group' => __( 'System', 'recruiting-playbook' ),
			],
			[
				'key'   => 'settings_write',
				'label' => __( 'Einstellungen bearbeiten', 'recruiting-playbook' ),
				'group' => __( 'System', 'recruiting-playbook' ),
			],
		];

		return new WP_REST_Response( $permissions, 200 );
	}

	/**
	 * Key-Response aufbereiten (OHNE plain_key)
	 *
	 * @param object $key Key DB-Row.
	 * @return array
	 */
	private function prepare_key_response( object $key ): array {
		return [
			'id'            => (int) $key->id,
			'name'          => $key->name,
			'key_prefix'    => $key->key_prefix,
			'key_hint'      => $key->key_hint,
			'permissions'   => json_decode( $key->permissions, true ) ?: [],
			'rate_limit'    => (int) $key->rate_limit,
			'last_used_at'  => $key->last_used_at,
			'request_count' => (int) $key->request_count,
			'created_by'    => (int) $key->created_by,
			'is_active'     => (bool) $key->is_active,
			'created_at'    => $key->created_at,
			'expires_at'    => $key->expires_at,
			'revoked_at'    => $key->revoked_at,
		];
	}

	/**
	 * Argumente für POST /api-keys
	 *
	 * @return array
	 */
	private function get_create_item_args(): array {
		return [
			'name'        => [
				'description'       => __( 'Key-Name', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'permissions' => [
				'description' => __( 'Berechtigungen', 'recruiting-playbook' ),
				'type'        => 'array',
				'required'    => true,
				'items'       => [
					'type' => 'string',
					'enum' => ApiKeyService::VALID_PERMISSIONS,
				],
			],
			'rate_limit'  => [
				'description' => __( 'Anfragen pro Stunde', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 1000,
				'minimum'     => 1,
				'maximum'     => 100000,
			],
			'expires_at'  => [
				'description' => __( 'Ablaufdatum (Y-m-d H:i:s)', 'recruiting-playbook' ),
				'type'        => 'string',
				'required'    => false,
				'default'     => null,
			],
		];
	}

	/**
	 * Argumente für PUT/PATCH /api-keys/{id}
	 *
	 * @return array
	 */
	private function get_update_item_args(): array {
		$args = $this->get_create_item_args();

		$args['id'] = [
			'description' => __( 'API-Key-ID', 'recruiting-playbook' ),
			'type'        => 'integer',
			'required'    => true,
		];

		$args['is_active'] = [
			'description' => __( 'Key aktiv', 'recruiting-playbook' ),
			'type'        => 'boolean',
			'required'    => false,
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

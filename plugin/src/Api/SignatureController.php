<?php
/**
 * REST API Controller für E-Mail-Signaturen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\SignatureService;
use RecruitingPlaybook\Repositories\SignatureRepository;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST API Controller für E-Mail-Signaturen
 */
class SignatureController extends WP_REST_Controller {

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
	protected $rest_base = 'signatures';

	/**
	 * Signature Service
	 *
	 * @var SignatureService
	 */
	private SignatureService $service;

	/**
	 * Signature Repository
	 *
	 * @var SignatureRepository
	 */
	private SignatureRepository $repository;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service    = new SignatureService();
		$this->repository = new SignatureRepository();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// Signaturen auflisten.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_create_item_args(),
				],
			]
		);

		// Firmen-Signaturen (muss vor /{id} Route kommen).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/company',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_company_signatures' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_company_signature' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
					'args'                => $this->get_company_signature_args(),
				],
			]
		);

		// Signatur-Optionen für Dropdown.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/options',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_options' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		// Vorschau rendern.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preview',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'preview' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'greeting'        => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'content'         => [
							'type'              => 'string',
							'sanitize_callback' => 'wp_kses_post',
						],
						'include_company' => [
							'type'    => 'boolean',
							'default' => true,
						],
					],
				],
			]
		);

		// Einzelne Signatur.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Signatur-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_update_item_args(),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Signatur-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Als Standard setzen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/default',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'set_default' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Signatur-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Signaturen auflisten
	 *
	 * Gibt die persönlichen Signaturen des aktuellen Users zurück.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$user_id    = get_current_user_id();
		$signatures = $this->repository->findByUser( $user_id );

		return new WP_REST_Response(
			[
				'signatures' => $signatures,
				'total'      => count( $signatures ),
			],
			200
		);
	}

	/**
	 * Einzelne Signatur abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$id        = (int) $request->get_param( 'id' );
		$signature = $this->repository->find( $id );

		if ( ! $signature ) {
			return new WP_Error(
				'rest_signature_not_found',
				__( 'Signatur nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $signature, 200 );
	}

	/**
	 * Neue Signatur erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$data = [
			'user_id'         => get_current_user_id(),
			'name'            => $request->get_param( 'name' ),
			'greeting'        => $request->get_param( 'greeting' ),
			'content'         => $request->get_param( 'content' ),
			'is_default'      => $request->get_param( 'is_default' ) ?? false,
			'include_company' => $request->get_param( 'include_company' ) ?? true,
		];

		$id = $this->service->create( $data );

		if ( false === $id ) {
			return new WP_Error(
				'rest_signature_create_failed',
				__( 'Signatur konnte nicht erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$signature = $this->repository->find( $id );

		return new WP_REST_Response(
			[
				'success'   => true,
				'message'   => __( 'Signatur wurde erstellt.', 'recruiting-playbook' ),
				'signature' => $signature,
			],
			201
		);
	}

	/**
	 * Signatur aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$data = array_filter(
			[
				'name'            => $request->get_param( 'name' ),
				'greeting'        => $request->get_param( 'greeting' ),
				'content'         => $request->get_param( 'content' ),
				'is_default'      => $request->get_param( 'is_default' ),
				'include_company' => $request->get_param( 'include_company' ),
			],
			fn( $v ) => null !== $v
		);

		if ( empty( $data ) ) {
			return new WP_Error(
				'rest_signature_no_data',
				__( 'Keine Daten zum Aktualisieren.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->service->update( $id, $data );

		if ( ! $result ) {
			return new WP_Error(
				'rest_signature_update_failed',
				__( 'Signatur konnte nicht aktualisiert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$signature = $this->repository->find( $id );

		return new WP_REST_Response(
			[
				'success'   => true,
				'message'   => __( 'Signatur wurde aktualisiert.', 'recruiting-playbook' ),
				'signature' => $signature,
			],
			200
		);
	}

	/**
	 * Signatur löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$result = $this->service->delete( $id );

		if ( ! $result ) {
			return new WP_Error(
				'rest_signature_delete_failed',
				__( 'Signatur konnte nicht gelöscht werden. Die Standard-Firmen-Signatur kann nicht gelöscht werden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Signatur wurde gelöscht.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Signatur als Standard setzen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_default( $request ) {
		$id        = (int) $request->get_param( 'id' );
		$signature = $this->repository->find( $id );

		if ( ! $signature ) {
			return new WP_Error(
				'rest_signature_not_found',
				__( 'Signatur nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$result = $this->repository->setDefault( $id, $signature['user_id'] );

		if ( ! $result ) {
			return new WP_Error(
				'rest_signature_set_default_failed',
				__( 'Signatur konnte nicht als Standard gesetzt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Signatur wurde als Standard gesetzt.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Firmen-Signaturen abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_company_signatures( $request ) {
		$signatures = $this->repository->findCompanySignatures();

		return new WP_REST_Response(
			[
				'signatures' => $signatures,
				'total'      => count( $signatures ),
			],
			200
		);
	}

	/**
	 * Neue Firmen-Signatur erstellen (Admin only)
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_company_signature( $request ) {
		$data = [
			'user_id'         => null, // Firmen-Signatur hat keine User-ID.
			'name'            => $request->get_param( 'name' ) ?: __( 'Firmen-Signatur', 'recruiting-playbook' ),
			'greeting'        => $request->get_param( 'greeting' ),
			'content'         => $request->get_param( 'content' ),
			'is_default'      => $request->get_param( 'is_default' ) ?? false,
			'include_company' => $request->get_param( 'include_company' ) ?? true,
		];

		$id = $this->service->create( $data );

		if ( false === $id ) {
			return new WP_Error(
				'rest_signature_create_failed',
				__( 'Firmen-Signatur konnte nicht erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$signature = $this->repository->find( $id );

		return new WP_REST_Response(
			[
				'success'   => true,
				'message'   => __( 'Firmen-Signatur wurde erstellt.', 'recruiting-playbook' ),
				'signature' => $signature,
			],
			201
		);
	}

	/**
	 * Signatur-Optionen für Dropdown abrufen
	 *
	 * Gibt alle Signaturen zurück die der User für E-Mails verwenden kann.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_options( $request ) {
		$user_id = get_current_user_id();
		$options = $this->service->getOptionsForUser( $user_id );

		return new WP_REST_Response(
			[
				'options' => $options,
			],
			200
		);
	}

	/**
	 * Signatur-Vorschau rendern
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function preview( $request ) {
		$data = [
			'greeting'        => $request->get_param( 'greeting' ) ?: '',
			'content'         => $request->get_param( 'content' ) ?: '',
			'include_company' => $request->get_param( 'include_company' ) ?? true,
		];

		$html = $this->service->renderPreview( $data );

		return new WP_REST_Response(
			[
				'html' => $html,
			],
			200
		);
	}

	/**
	 * Berechtigung für Auflisten prüfen
	 *
	 * Jeder eingeloggte User kann Signaturen auflisten.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie müssen angemeldet sein.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für Lesen einer Signatur prüfen
	 *
	 * User kann eigene Signaturen und Firmen-Signaturen lesen.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie müssen angemeldet sein.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		$id        = (int) $request->get_param( 'id' );
		$signature = $this->repository->find( $id );

		if ( ! $signature ) {
			return true; // 404 wird in get_item behandelt.
		}

		// Firmen-Signatur (user_id = null) darf jeder lesen.
		if ( null === $signature['user_id'] ) {
			return true;
		}

		// Eigene Signatur.
		if ( (int) $signature['user_id'] === get_current_user_id() ) {
			return true;
		}

		// Admin darf alles lesen.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sie haben keine Berechtigung, diese Signatur anzuzeigen.', 'recruiting-playbook' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Berechtigung für Erstellen prüfen
	 *
	 * Jeder eingeloggte User kann Signaturen erstellen.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie müssen angemeldet sein.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für Aktualisieren prüfen
	 *
	 * User kann nur eigene Signaturen bearbeiten.
	 * Firmen-Signaturen nur Admin.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie müssen angemeldet sein.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		$id        = (int) $request->get_param( 'id' );
		$signature = $this->repository->find( $id );

		if ( ! $signature ) {
			return true; // 404 wird im Handler behandelt.
		}

		// Firmen-Signatur (user_id = null) nur Admin.
		if ( null === $signature['user_id'] ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Nur Administratoren können Firmen-Signaturen bearbeiten.', 'recruiting-playbook' ),
					[ 'status' => 403 ]
				);
			}
			return true;
		}

		// Eigene Signatur.
		if ( (int) $signature['user_id'] === get_current_user_id() ) {
			return true;
		}

		// Admin darf alles bearbeiten.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sie haben keine Berechtigung, diese Signatur zu bearbeiten.', 'recruiting-playbook' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Berechtigung für Löschen prüfen
	 *
	 * User kann nur eigene Signaturen löschen.
	 * Firmen-Signaturen nur Admin (außer Standard-Firmen-Signatur).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie müssen angemeldet sein.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		$id        = (int) $request->get_param( 'id' );
		$signature = $this->repository->find( $id );

		if ( ! $signature ) {
			return true; // 404 wird im Handler behandelt.
		}

		// Firmen-Signatur (user_id = null) nur Admin.
		if ( null === $signature['user_id'] ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Nur Administratoren können Firmen-Signaturen löschen.', 'recruiting-playbook' ),
					[ 'status' => 403 ]
				);
			}
			return true;
		}

		// Eigene Signatur.
		if ( (int) $signature['user_id'] === get_current_user_id() ) {
			return true;
		}

		// Admin darf alles löschen.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sie haben keine Berechtigung, diese Signatur zu löschen.', 'recruiting-playbook' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Admin-Berechtigung prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function admin_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Nur Administratoren haben Zugriff auf diese Funktion.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Argumente für das Erstellen einer Signatur
	 *
	 * @return array
	 */
	private function get_create_item_args(): array {
		return [
			'name'            => [
				'description'       => __( 'Signatur-Name', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'greeting'        => [
				'description'       => __( 'Grußformel', 'recruiting-playbook' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'content'         => [
				'description'       => __( 'Signatur-Inhalt', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'wp_kses_post',
			],
			'is_default'      => [
				'description' => __( 'Als Standard setzen', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'default'     => false,
			],
			'include_company' => [
				'description' => __( 'Firmendaten anhängen', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'default'     => true,
			],
		];
	}

	/**
	 * Argumente für Firmen-Signatur (name optional)
	 *
	 * @return array
	 */
	private function get_company_signature_args(): array {
		return [
			'name'            => [
				'description'       => __( 'Signatur-Name', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'default'           => __( 'Firmen-Signatur', 'recruiting-playbook' ),
				'sanitize_callback' => 'sanitize_text_field',
			],
			'greeting'        => [
				'description'       => __( 'Grußformel', 'recruiting-playbook' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'content'         => [
				'description'       => __( 'Signatur-Inhalt', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'wp_kses_post',
			],
			'is_default'      => [
				'description' => __( 'Als Standard setzen', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'default'     => false,
			],
			'include_company' => [
				'description' => __( 'Firmendaten anhängen', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'default'     => true,
			],
		];
	}

	/**
	 * Argumente für das Aktualisieren einer Signatur
	 *
	 * @return array
	 */
	private function get_update_item_args(): array {
		$args = $this->get_create_item_args();

		// Alle Felder optional machen.
		foreach ( $args as $key => $arg ) {
			$args[ $key ]['required'] = false;
		}

		$args['id'] = [
			'description' => __( 'Signatur-ID', 'recruiting-playbook' ),
			'type'        => 'integer',
			'required'    => true,
		];

		return $args;
	}
}

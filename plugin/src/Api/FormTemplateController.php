<?php
/**
 * Form Template Controller - REST API für Formular-Vorlagen
 *
 * @package RecruitingPlaybook\Api
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FormTemplate;
use RecruitingPlaybook\Repositories\FormTemplateRepository;
use RecruitingPlaybook\Services\FormTemplateService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Formular-Vorlagen
 */
class FormTemplateController extends WP_REST_Controller {

	/**
	 * API-Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Resource base
	 *
	 * @var string
	 */
	protected $rest_base = 'form-templates';

	/**
	 * Repository
	 *
	 * @var FormTemplateRepository
	 */
	private FormTemplateRepository $repository;

	/**
	 * Service
	 *
	 * @var FormTemplateService
	 */
	private FormTemplateService $service;

	/**
	 * Konstruktor
	 */
	public function __construct() {
		$this->repository = new FormTemplateRepository();
		$this->service    = new FormTemplateService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /form-templates — Alle Templates abrufen.
		// POST /form-templates — Neues Template erstellen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		// GET /form-templates/default — Standard-Template abrufen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/default',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_default_template' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		// GET/PUT/DELETE /form-templates/{id} — Einzelnes Template.
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
							'description' => __( 'Template-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Template-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		// POST /form-templates/{id}/duplicate — Template duplizieren.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/duplicate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'duplicate_template' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'id'   => [
							'description' => __( 'Template-ID zum Duplizieren', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'name' => [
							'description' => __( 'Name für das neue Template', 'recruiting-playbook' ),
							'type'        => 'string',
						],
					],
				],
			]
		);

		// POST /form-templates/{id}/set-default — Als Standard setzen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/set-default',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'set_default_template' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Template-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Berechtigungsprüfung: Templates lesen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ): bool|WP_Error {
		$gate = $this->check_feature_gate();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		if ( ! current_user_can( 'rp_manage_forms' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Formular-Vorlagen zu verwalten.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigungsprüfung: Einzelnes Template lesen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ): bool|WP_Error {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Berechtigungsprüfung: Template erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ): bool|WP_Error {
		$gate = $this->check_feature_gate();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		if ( ! current_user_can( 'rp_manage_forms' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Formular-Vorlagen zu erstellen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigungsprüfung: Template aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ): bool|WP_Error {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Berechtigungsprüfung: Template löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ): bool|WP_Error {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Alle Templates abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$include_fields = $request->get_param( 'include_fields' ) ?? false;

		$templates = $this->repository->findAll();
		$data      = array_map(
			fn( $t ) => $this->prepare_item_for_response_array( $t, $include_fields ),
			$templates
		);

		return new WP_REST_Response( [ 'templates' => $data ], 200 );
	}

	/**
	 * Standard-Template abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_default_template( $request ): WP_REST_Response|WP_Error {
		$template = $this->repository->findDefault();

		if ( ! $template ) {
			return new WP_Error(
				'not_found',
				__( 'Kein Standard-Template definiert.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $this->prepare_item_for_response_array( $template, true ), 200 );
	}

	/**
	 * Einzelnes Template abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ): WP_REST_Response|WP_Error {
		$id       = (int) $request->get_param( 'id' );
		$template = $this->repository->find( $id );

		if ( ! $template ) {
			return new WP_Error(
				'not_found',
				__( 'Template nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $this->prepare_item_for_response_array( $template, true ), 200 );
	}

	/**
	 * Neues Template erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$result = $this->service->createTemplate( $request->get_params() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->prepare_item_for_response_array( $result, true ), 201 );
	}

	/**
	 * Template aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$result = $this->service->updateTemplate( $id, $request->get_params() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->prepare_item_for_response_array( $result, true ), 200 );
	}

	/**
	 * Template löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$result = $this->service->deleteTemplate( $id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'deleted' => true, 'id' => $id ], 200 );
	}

	/**
	 * Template duplizieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function duplicate_template( $request ): WP_REST_Response|WP_Error {
		$id       = (int) $request->get_param( 'id' );
		$new_name = $request->get_param( 'name' );

		$result = $this->service->duplicateTemplate( $id, $new_name );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->prepare_item_for_response_array( $result, true ), 201 );
	}

	/**
	 * Template als Standard setzen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_default_template( $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );

		$result = $this->repository->setDefault( $id );

		if ( ! $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Template konnte nicht als Standard gesetzt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$template = $this->repository->find( $id );

		return new WP_REST_Response( $this->prepare_item_for_response_array( $template, true ), 200 );
	}

	/**
	 * Template für Response vorbereiten
	 *
	 * @param FormTemplate $template       Template.
	 * @param bool         $include_fields Felder inkludieren.
	 * @return array Response-Daten.
	 */
	private function prepare_item_for_response_array( FormTemplate $template, bool $include_fields = false ): array {
		$data = [
			'id'          => $template->getId(),
			'name'        => $template->getName(),
			'description' => $template->getDescription(),
			'is_default'  => $template->isDefault(),
			'field_count' => $template->getFieldCount(),
			'usage_count' => $this->repository->getUsageCount( $template->getId() ),
			'created_by'  => $template->getCreator(),
			'created_at'  => $template->getCreatedAt(),
			'updated_at'  => $template->getUpdatedAt(),
		];

		if ( $include_fields ) {
			$fields         = $template->getFields();
			$data['fields'] = array_map(
				fn( $f ) => [
					'id'          => $f->getId(),
					'field_key'   => $f->getFieldKey(),
					'type'        => $f->getType(),
					'label'       => $f->getLabel(),
					'placeholder' => $f->getPlaceholder(),
					'description' => $f->getDescription(),
					'is_required' => $f->isRequired(),
					'is_system'   => $f->isSystem(),
					'position'    => $f->getPosition(),
					'options'     => $f->getOptions(),
					'validation'  => $f->getValidation(),
					'conditional' => $f->getConditional(),
					'settings'    => $f->getSettings(),
				],
				$fields
			);
		}

		return $data;
	}

	/**
	 * Collection-Parameter
	 *
	 * @return array Parameter-Schema.
	 */
	public function get_collection_params(): array {
		return [
			'include_fields' => [
				'description' => __( 'Felder in Response inkludieren', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'default'     => false,
			],
		];
	}

	/**
	 * Item-Schema
	 *
	 * @return array Schema.
	 */
	public function get_item_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'form_template',
			'type'       => 'object',
			'properties' => [
				'id'          => [
					'description' => __( 'Template-ID', 'recruiting-playbook' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'name'        => [
					'description' => __( 'Template-Name', 'recruiting-playbook' ),
					'type'        => 'string',
					'required'    => true,
				],
				'description' => [
					'description' => __( 'Beschreibung', 'recruiting-playbook' ),
					'type'        => 'string',
				],
				'is_default'  => [
					'description' => __( 'Standard-Template', 'recruiting-playbook' ),
					'type'        => 'boolean',
					'default'     => false,
				],
				'field_ids'   => [
					'description' => __( 'IDs der Felder in diesem Template', 'recruiting-playbook' ),
					'type'        => 'array',
					'items'       => [ 'type' => 'integer' ],
				],
			],
		];
	}

	/**
	 * Feature-Gate prüfen
	 *
	 * @return bool|WP_Error
	 */
	private function check_feature_gate(): bool|WP_Error {
		if ( function_exists( 'rp_can' ) && ! rp_can( 'custom_fields' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Custom Fields erfordert eine Pro-Lizenz.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}

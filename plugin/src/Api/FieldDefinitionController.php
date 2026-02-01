<?php
/**
 * Field Definition Controller - REST API für Felddefinitionen
 *
 * @package RecruitingPlaybook\Api
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;
use RecruitingPlaybook\Models\FieldDefinition;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Services\FieldDefinitionService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Felddefinitionen
 */
class FieldDefinitionController extends WP_REST_Controller {

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
	protected $rest_base = 'fields';

	/**
	 * Repository
	 *
	 * @var FieldDefinitionRepository
	 */
	private FieldDefinitionRepository $repository;

	/**
	 * Service
	 *
	 * @var FieldDefinitionService
	 */
	private FieldDefinitionService $service;

	/**
	 * Konstruktor
	 */
	public function __construct() {
		$this->repository = new FieldDefinitionRepository();
		$this->service    = new FieldDefinitionService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /fields/types — Alle verfügbaren Feldtypen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/types',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_field_types' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		// GET /fields — Alle Felddefinitionen abrufen.
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

		// GET /fields/system — System-Felder abrufen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/system',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_system_fields' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		// GET/PUT/DELETE /fields/{id} — Einzelnes Feld.
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
							'description' => __( 'Feld-ID', 'recruiting-playbook' ),
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
							'description' => __( 'Feld-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		// POST /fields/reorder — Felder neu sortieren.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reorder',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'reorder_fields' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'field_ids' => [
							'description' => __( 'Geordnete Liste der Feld-IDs', 'recruiting-playbook' ),
							'type'        => 'array',
							'items'       => [ 'type' => 'integer' ],
							'required'    => true,
						],
					],
				],
			]
		);

		// GET /fields/job/{job_id} — Felder für einen Job.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/job/(?P<job_id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_fields_for_job' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'job_id' => [
							'description' => __( 'Job-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// PUT /fields/job/{job_id} — Job-spezifische Felder speichern.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/job/(?P<job_id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'save_job_fields' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'job_id'   => [
							'description' => __( 'Job-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'field_ids' => [
							'description' => __( 'Liste der Feld-IDs für diesen Job', 'recruiting-playbook' ),
							'type'        => 'array',
							'items'       => [ 'type' => 'integer' ],
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Berechtigungsprüfung: Felder lesen
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
				__( 'Sie haben keine Berechtigung, Formularfelder zu verwalten.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigungsprüfung: Einzelnes Feld lesen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ): bool|WP_Error {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Berechtigungsprüfung: Feld erstellen
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
				__( 'Sie haben keine Berechtigung, Formularfelder zu erstellen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigungsprüfung: Feld aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ): bool|WP_Error {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Berechtigungsprüfung: Feld löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ): bool|WP_Error {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Alle verfügbaren Feldtypen abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_field_types( $request ): WP_REST_Response {
		$registry = FieldTypeRegistry::getInstance();

		return new WP_REST_Response(
			[
				'types'        => $registry->toArray(),
				'groups'       => $registry->getGroupLabels(),
				'type_keys'    => $registry->getTypeKeys(),
			],
			200
		);
	}

	/**
	 * Alle Felddefinitionen abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$template_id = $request->get_param( 'template_id' );
		$type        = $request->get_param( 'type' );
		$is_system   = $request->get_param( 'is_system' );

		if ( $template_id ) {
			$fields = $this->repository->findByTemplate( (int) $template_id );
		} else {
			$fields = $this->repository->findAll();
		}

		// Filter anwenden.
		if ( $type ) {
			$fields = array_filter( $fields, fn( $f ) => $f->getType() === $type );
		}

		if ( $is_system !== null ) {
			$is_system_bool = filter_var( $is_system, FILTER_VALIDATE_BOOLEAN );
			$fields         = array_filter( $fields, fn( $f ) => $f->isSystem() === $is_system_bool );
		}

		$data = array_map( [ $this, 'prepare_item_for_response_array' ], array_values( $fields ) );

		return new WP_REST_Response( [ 'fields' => $data ], 200 );
	}

	/**
	 * System-Felder abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_system_fields( $request ): WP_REST_Response {
		$fields = $this->repository->findSystemFields();
		$data   = array_map( [ $this, 'prepare_item_for_response_array' ], $fields );

		return new WP_REST_Response( [ 'fields' => $data ], 200 );
	}

	/**
	 * Einzelnes Feld abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ): WP_REST_Response|WP_Error {
		$id    = (int) $request->get_param( 'id' );
		$field = $this->repository->find( $id );

		if ( ! $field ) {
			return new WP_Error(
				'not_found',
				__( 'Feld nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $this->prepare_item_for_response_array( $field ), 200 );
	}

	/**
	 * Neues Feld erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$result = $this->service->create( $request->get_params() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->prepare_item_for_response_array( $result ), 201 );
	}

	/**
	 * Feld aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$result = $this->service->update( $id, $request->get_params() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->prepare_item_for_response_array( $result ), 200 );
	}

	/**
	 * Feld löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$id     = (int) $request->get_param( 'id' );
		$result = $this->service->delete( $id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'deleted' => true, 'id' => $id ], 200 );
	}

	/**
	 * Felder neu sortieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reorder_fields( $request ): WP_REST_Response|WP_Error {
		$field_ids = $request->get_param( 'field_ids' );

		if ( empty( $field_ids ) || ! is_array( $field_ids ) ) {
			return new WP_Error(
				'invalid_params',
				__( 'Ungültige Feld-IDs.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$this->repository->reorder( array_map( 'intval', $field_ids ) );

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Felder für einen Job abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_fields_for_job( $request ): WP_REST_Response|WP_Error {
		$job_id = (int) $request->get_param( 'job_id' );

		// Job existiert?
		if ( get_post_type( $job_id ) !== 'job_listing' ) {
			return new WP_Error(
				'not_found',
				__( 'Job nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$fields = $this->service->getFieldsForJob( $job_id );
		$data   = array_map( [ $this, 'prepare_item_for_response_array' ], $fields );

		return new WP_REST_Response( [ 'fields' => $data, 'job_id' => $job_id ], 200 );
	}

	/**
	 * Job-spezifische Felder speichern
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_job_fields( $request ): WP_REST_Response|WP_Error {
		$job_id    = (int) $request->get_param( 'job_id' );
		$field_ids = $request->get_param( 'field_ids' );

		// Job existiert?
		if ( get_post_type( $job_id ) !== 'job_listing' ) {
			return new WP_Error(
				'not_found',
				__( 'Job nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Feld-IDs als Post-Meta speichern.
		update_post_meta( $job_id, '_rp_custom_field_ids', array_map( 'intval', $field_ids ) );

		$fields = $this->service->getFieldsForJob( $job_id );
		$data   = array_map( [ $this, 'prepare_item_for_response_array' ], $fields );

		return new WP_REST_Response( [ 'fields' => $data, 'job_id' => $job_id ], 200 );
	}

	/**
	 * Feld für Response vorbereiten
	 *
	 * @param FieldDefinition $field Feld.
	 * @return array Response-Daten.
	 */
	private function prepare_item_for_response_array( FieldDefinition $field ): array {
		return [
			'id'          => $field->getId(),
			'field_key'   => $field->getFieldKey(),
			'type'        => $field->getFieldType(),
			'label'       => $field->getLabel(),
			'placeholder' => $field->getPlaceholder(),
			'description' => $field->getDescription(),
			'is_required' => $field->isRequired(),
			'is_system'   => $field->isSystem(),
			'position'    => $field->getPosition(),
			'options'     => $field->getOptions(),
			'validation'  => $field->getValidation(),
			'conditional' => $field->getConditional(),
			'settings'    => $field->getSettings(),
			'template_id' => $field->getTemplateId(),
			'job_id'      => $field->getJobId(),
			'created_at'  => $field->getCreatedAt(),
			'updated_at'  => $field->getUpdatedAt(),
		];
	}

	/**
	 * Collection-Parameter
	 *
	 * @return array Parameter-Schema.
	 */
	public function get_collection_params(): array {
		return [
			'template_id' => [
				'description' => __( 'Nach Template-ID filtern', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
			'type'        => [
				'description' => __( 'Nach Feldtyp filtern', 'recruiting-playbook' ),
				'type'        => 'string',
			],
			'is_system'   => [
				'description' => __( 'Nur System-Felder anzeigen', 'recruiting-playbook' ),
				'type'        => 'boolean',
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
			'title'      => 'field_definition',
			'type'       => 'object',
			'properties' => [
				'id'          => [
					'description' => __( 'Feld-ID', 'recruiting-playbook' ),
					'type'        => 'integer',
					'readonly'    => true,
				],
				'field_key'   => [
					'description' => __( 'Eindeutiger Feldschlüssel', 'recruiting-playbook' ),
					'type'        => 'string',
					'required'    => true,
					'pattern'     => '^[a-z][a-z0-9_]*$',
				],
				'type'        => [
					'description' => __( 'Feldtyp', 'recruiting-playbook' ),
					'type'        => 'string',
					'required'    => true,
					'enum'        => FieldTypeRegistry::getInstance()->getTypeKeys(),
				],
				'label'       => [
					'description' => __( 'Feld-Label', 'recruiting-playbook' ),
					'type'        => 'string',
					'required'    => true,
				],
				'placeholder' => [
					'description' => __( 'Placeholder-Text', 'recruiting-playbook' ),
					'type'        => 'string',
				],
				'description' => [
					'description' => __( 'Hilfetext', 'recruiting-playbook' ),
					'type'        => 'string',
				],
				'is_required' => [
					'description' => __( 'Pflichtfeld', 'recruiting-playbook' ),
					'type'        => 'boolean',
					'default'     => false,
				],
				'is_system'   => [
					'description' => __( 'System-Feld (nicht löschbar)', 'recruiting-playbook' ),
					'type'        => 'boolean',
					'readonly'    => true,
				],
				'position'    => [
					'description' => __( 'Sortierposition', 'recruiting-playbook' ),
					'type'        => 'integer',
				],
				'options'     => [
					'description' => __( 'Auswahloptionen (für Select/Radio/Checkbox)', 'recruiting-playbook' ),
					'type'        => 'array',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'value' => [ 'type' => 'string' ],
							'label' => [ 'type' => 'string' ],
						],
					],
				],
				'validation'  => [
					'description' => __( 'Validierungsregeln', 'recruiting-playbook' ),
					'type'        => 'object',
				],
				'conditional' => [
					'description' => __( 'Conditional Logic', 'recruiting-playbook' ),
					'type'        => 'object',
				],
				'settings'    => [
					'description' => __( 'Feldspezifische Einstellungen', 'recruiting-playbook' ),
					'type'        => 'object',
				],
				'template_id' => [
					'description' => __( 'Zugehörige Template-ID', 'recruiting-playbook' ),
					'type'        => 'integer',
				],
				'job_id'      => [
					'description' => __( 'Job-spezifisches Feld für diese Job-ID', 'recruiting-playbook' ),
					'type'        => 'integer',
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

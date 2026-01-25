<?php
/**
 * REST API Controller für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\EmailTemplateService;
use RecruitingPlaybook\Services\PlaceholderService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST API Controller für E-Mail-Templates
 */
class EmailTemplateController extends WP_REST_Controller {

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
	protected $rest_base = 'email-templates';

	/**
	 * Template Service
	 *
	 * @var EmailTemplateService
	 */
	private EmailTemplateService $template_service;

	/**
	 * Placeholder Service
	 *
	 * @var PlaceholderService
	 */
	private PlaceholderService $placeholder_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->template_service    = new EmailTemplateService();
		$this->placeholder_service = new PlaceholderService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// Templates auflisten
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
					'args'                => $this->get_create_item_args(),
				],
			]
		);

		// Einzelnes Template
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
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
					'args'                => $this->get_update_item_args(),
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
			]
		);

		// Template duplizieren
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/duplicate',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'duplicate_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'id'   => [
							'description' => __( 'Template-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'name' => [
							'description'       => __( 'Neuer Name für das Duplikat', 'recruiting-playbook' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);

		// Template auf Standard zurücksetzen
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/reset',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'reset_item' ],
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

		// Als Standard setzen
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/set-default',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'set_default' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'id'       => [
							'description' => __( 'Template-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'category' => [
							'description' => __( 'Kategorie für Standard-Template', 'recruiting-playbook' ),
							'type'        => 'string',
							'required'    => true,
							'enum'        => [ 'application', 'interview', 'offer', 'rejection', 'custom' ],
						],
					],
				],
			]
		);

		// Verfügbare Platzhalter abrufen
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/placeholders',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_placeholders' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		// Kategorien abrufen
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/categories',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_categories' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Templates auflisten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$args = [
			'category'  => $request->get_param( 'category' ),
			'is_active' => $request->get_param( 'is_active' ),
			'search'    => $request->get_param( 'search' ),
			'per_page'  => $request->get_param( 'per_page' ) ?: 20,
			'page'      => $request->get_param( 'page' ) ?: 1,
		];

		// Null-Werte entfernen.
		$args = array_filter( $args, fn( $v ) => null !== $v );

		$result = $this->template_service->getList( $args );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Einzelnes Template abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$template = $this->template_service->find( $id );

		if ( ! $template ) {
			return new WP_Error(
				'rest_template_not_found',
				__( 'Template nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Berechtigungen hinzufügen.
		$template['can_edit']   = ! $template['is_system'];
		$template['can_delete'] = ! $template['is_system'];

		return new WP_REST_Response( $template, 200 );
	}

	/**
	 * Neues Template erstellen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$category = $request->get_param( 'category' ) ?: 'custom';

		// System-Templates können nicht über API erstellt werden.
		if ( 'system' === $category ) {
			return new WP_Error(
				'rest_invalid_category',
				__( 'System-Templates können nicht manuell erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$data = [
			'name'      => $request->get_param( 'name' ),
			'subject'   => $request->get_param( 'subject' ),
			'body_html' => $request->get_param( 'body_html' ),
			'body_text' => $request->get_param( 'body_text' ),
			'category'  => $category,
			'is_active' => $request->get_param( 'is_active' ) ?? true,
			'settings'  => $request->get_param( 'settings' ) ?: [],
		];

		$result = $this->template_service->create( $data );

		if ( false === $result ) {
			return new WP_Error(
				'rest_template_create_failed',
				__( 'Template konnte nicht erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response(
			[
				'success'  => true,
				'message'  => __( 'Template wurde erstellt.', 'recruiting-playbook' ),
				'template' => $result,
			],
			201
		);
	}

	/**
	 * Template aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$data = array_filter(
			[
				'name'       => $request->get_param( 'name' ),
				'subject'    => $request->get_param( 'subject' ),
				'body_html'  => $request->get_param( 'body_html' ),
				'body_text'  => $request->get_param( 'body_text' ),
				'category'   => $request->get_param( 'category' ),
				'is_active'  => $request->get_param( 'is_active' ),
				'is_default' => $request->get_param( 'is_default' ),
				'settings'   => $request->get_param( 'settings' ),
			],
			fn( $v ) => null !== $v
		);

		if ( empty( $data ) ) {
			return new WP_Error(
				'rest_template_no_data',
				__( 'Keine Daten zum Aktualisieren.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->template_service->update( $id, $data );

		if ( false === $result ) {
			return new WP_Error(
				'rest_template_update_failed',
				__( 'Template konnte nicht aktualisiert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response(
			[
				'success'  => true,
				'message'  => __( 'Template wurde aktualisiert.', 'recruiting-playbook' ),
				'template' => $result,
			],
			200
		);
	}

	/**
	 * Template löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$result = $this->template_service->delete( $id );

		if ( ! $result ) {
			return new WP_Error(
				'rest_template_delete_failed',
				__( 'Template konnte nicht gelöscht werden. System-Templates können nicht gelöscht werden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Template wurde gelöscht.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Template duplizieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function duplicate_item( $request ) {
		$id       = (int) $request->get_param( 'id' );
		$new_name = $request->get_param( 'name' ) ?: '';

		$result = $this->template_service->duplicate( $id, $new_name );

		if ( false === $result ) {
			return new WP_Error(
				'rest_template_duplicate_failed',
				__( 'Template konnte nicht dupliziert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response(
			[
				'success'  => true,
				'message'  => __( 'Template wurde dupliziert.', 'recruiting-playbook' ),
				'template' => $result,
			],
			201
		);
	}

	/**
	 * Template auf Standard zurücksetzen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reset_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$result = $this->template_service->resetToDefault( $id );

		if ( ! $result ) {
			return new WP_Error(
				'rest_template_reset_failed',
				__( 'Template konnte nicht zurückgesetzt werden. Nur System-Templates können zurückgesetzt werden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$template = $this->template_service->find( $id );

		return new WP_REST_Response(
			[
				'success'  => true,
				'message'  => __( 'Template wurde auf Standard zurückgesetzt.', 'recruiting-playbook' ),
				'template' => $template,
			],
			200
		);
	}

	/**
	 * Template als Standard setzen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_default( $request ) {
		$id       = (int) $request->get_param( 'id' );
		$category = $request->get_param( 'category' );

		$result = $this->template_service->setAsDefault( $id, $category );

		if ( ! $result ) {
			return new WP_Error(
				'rest_template_set_default_failed',
				__( 'Template konnte nicht als Standard gesetzt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Template wurde als Standard gesetzt.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Verfügbare Platzhalter abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_placeholders( $request ) {
		$placeholders   = $this->placeholder_service->getAvailablePlaceholders();
		$preview_values = $this->placeholder_service->getPreviewValues();

		return new WP_REST_Response(
			[
				'groups'         => $placeholders,
				'preview_values' => $preview_values,
			],
			200
		);
	}

	/**
	 * Kategorien abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_categories( $request ) {
		$categories = $this->template_service->getCategories();

		return new WP_REST_Response( $categories, 200 );
	}

	/**
	 * Berechtigung für Auflisten/Lesen prüfen
	 *
	 * Prüft: 1. WordPress Capability, 2. Feature-Flag (Pro erforderlich).
	 * Die Reihenfolge ist wichtig: Capability (Security) vor Feature-Flag (Business-Logic).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		// Verwende Helper-Funktion für konsistente Prüfung.
		if ( function_exists( 'rp_check_feature_permission' ) ) {
			return rp_check_feature_permission(
				'email_templates',
				'rp_read_email_templates',
				'rest_email_templates_required',
				__( 'Sie haben keine Berechtigung, E-Mail-Templates anzuzeigen.', 'recruiting-playbook' )
			);
		}

		// Fallback falls Helper nicht verfügbar.
		// 1. Capability-Check (WordPress-Core-Security).
		if ( ! current_user_can( 'rp_read_email_templates' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, E-Mail-Templates anzuzeigen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// 2. Feature-Flag-Check (Business-Logic).
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return new WP_Error(
				'rest_email_templates_required',
				__( 'E-Mail-Templates erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für Erstellen prüfen
	 *
	 * Erfordert rp_create_email_templates Capability (nur Admin).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		// Verwende Helper-Funktion für konsistente Prüfung.
		if ( function_exists( 'rp_check_feature_permission' ) ) {
			return rp_check_feature_permission(
				'email_templates',
				'rp_create_email_templates',
				'rest_email_templates_required',
				__( 'Sie haben keine Berechtigung, E-Mail-Templates zu erstellen.', 'recruiting-playbook' )
			);
		}

		// Fallback falls Helper nicht verfügbar.
		if ( ! current_user_can( 'rp_create_email_templates' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, E-Mail-Templates zu erstellen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return new WP_Error(
				'rest_email_templates_required',
				__( 'E-Mail-Templates erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für Aktualisieren prüfen
	 *
	 * Erfordert rp_edit_email_templates Capability (Admin + Editor).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		// Verwende Helper-Funktion für konsistente Prüfung.
		if ( function_exists( 'rp_check_feature_permission' ) ) {
			return rp_check_feature_permission(
				'email_templates',
				'rp_edit_email_templates',
				'rest_email_templates_required',
				__( 'Sie haben keine Berechtigung, E-Mail-Templates zu bearbeiten.', 'recruiting-playbook' )
			);
		}

		// Fallback falls Helper nicht verfügbar.
		if ( ! current_user_can( 'rp_edit_email_templates' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, E-Mail-Templates zu bearbeiten.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return new WP_Error(
				'rest_email_templates_required',
				__( 'E-Mail-Templates erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für Löschen prüfen
	 *
	 * Erfordert rp_delete_email_templates Capability (nur Admin).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		// Verwende Helper-Funktion für konsistente Prüfung.
		if ( function_exists( 'rp_check_feature_permission' ) ) {
			return rp_check_feature_permission(
				'email_templates',
				'rp_delete_email_templates',
				'rest_email_templates_required',
				__( 'Sie haben keine Berechtigung, E-Mail-Templates zu löschen.', 'recruiting-playbook' )
			);
		}

		// Fallback falls Helper nicht verfügbar.
		if ( ! current_user_can( 'rp_delete_email_templates' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, E-Mail-Templates zu löschen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return new WP_Error(
				'rest_email_templates_required',
				__( 'E-Mail-Templates erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		return true;
	}

	/**
	 * Collection Parameter
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		return [
			'category'  => [
				'description' => __( 'Nach Kategorie filtern', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'system', 'application', 'interview', 'offer', 'rejection', 'custom' ],
			],
			'is_active' => [
				'description' => __( 'Nur aktive Templates', 'recruiting-playbook' ),
				'type'        => 'boolean',
			],
			'search'    => [
				'description'       => __( 'Suchbegriff', 'recruiting-playbook' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'per_page'  => [
				'description' => __( 'Ergebnisse pro Seite', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 20,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'page'      => [
				'description' => __( 'Seitennummer', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
		];
	}

	/**
	 * Argumente für das Erstellen eines Templates
	 *
	 * @return array
	 */
	private function get_create_item_args(): array {
		return [
			'name'      => [
				'description'       => __( 'Template-Name', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'subject'   => [
				'description'       => __( 'E-Mail-Betreff', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'body_html' => [
				'description'       => __( 'HTML-Inhalt', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'wp_kses_post',
			],
			'body_text' => [
				'description'       => __( 'Text-Inhalt', 'recruiting-playbook' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			],
			'category'  => [
				'description' => __( 'Kategorie', 'recruiting-playbook' ),
				'type'        => 'string',
				'default'     => 'custom',
				'enum'        => [ 'application', 'interview', 'offer', 'rejection', 'custom' ],
			],
			'is_active' => [
				'description' => __( 'Template aktiv', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'default'     => true,
			],
			'settings'  => [
				'description' => __( 'Zusätzliche Einstellungen', 'recruiting-playbook' ),
				'type'        => 'object',
			],
		];
	}

	/**
	 * Argumente für das Aktualisieren eines Templates
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
			'description' => __( 'Template-ID', 'recruiting-playbook' ),
			'type'        => 'integer',
			'required'    => true,
		];

		$args['is_default'] = [
			'description' => __( 'Als Standard-Template setzen', 'recruiting-playbook' ),
			'type'        => 'boolean',
		];

		return $args;
	}
}

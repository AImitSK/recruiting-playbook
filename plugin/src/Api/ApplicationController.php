<?php
/**
 * REST API Controller für Bewerbungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\ApplicationService;
use RecruitingPlaybook\Services\CapabilityService;
use RecruitingPlaybook\Services\SpamProtection;
use RecruitingPlaybook\Services\DocumentDownloadService;
use RecruitingPlaybook\Services\GdprService;
use RecruitingPlaybook\Constants\ApplicationStatus;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST API Controller für Bewerbungen
 */
class ApplicationController extends WP_REST_Controller {

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
	protected $rest_base = 'applications';

	/**
	 * Application Service
	 *
	 * @var ApplicationService
	 */
	private ApplicationService $application_service;

	/**
	 * Spam Protection
	 *
	 * @var SpamProtection
	 */
	private SpamProtection $spam_protection;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->application_service = new ApplicationService();
		$this->spam_protection     = new SpamProtection();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// Öffentlicher Endpunkt: Bewerbung einreichen
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => '__return_true', // Öffentlich zugänglich
					'args'                => $this->get_create_item_args(),
				],
			]
		);

		// Authentifizierter Endpunkt: Bewerbungen auflisten
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
			]
		);

		// Einzelne Bewerbung abrufen
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
							'description' => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Status ändern
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/status',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_status' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'id'              => [
							'description' => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'status'          => [
							'description' => __( 'Neuer Status', 'recruiting-playbook' ),
							'type'        => 'string',
							'required'    => true,
							'enum'        => [ 'new', 'screening', 'interview', 'offer', 'hired', 'rejected', 'withdrawn' ],
						],
						'note'            => [
							'description' => __( 'Notiz zur Statusänderung', 'recruiting-playbook' ),
							'type'        => 'string',
							'required'    => false,
						],
						'kanban_position' => [
							'description' => __( 'Position im Kanban-Board', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => false,
						],
					],
				],
			]
		);

		// Bewerbung löschen
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id'          => [
							'description' => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'hard_delete' => [
							'description' => __( 'Endgültig löschen', 'recruiting-playbook' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
			]
		);

		// Dokument-Download-URL generieren
		register_rest_route(
			$this->namespace,
			'/documents/(?P<id>[\d]+)/download-url',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_download_url' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Dokument-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Kandidaten-Datenauskunft (DSGVO)
		register_rest_route(
			$this->namespace,
			'/candidates/(?P<id>[\d]+)/export',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'export_candidate_data' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Statistiken
		register_rest_route(
			$this->namespace,
			'/stats',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_stats' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		// Kanban: Positionen neu sortieren (Batch-Update)
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reorder',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'reorder_applications' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'status'    => [
							'description' => __( 'Status/Spalte der zu sortierenden Bewerbungen', 'recruiting-playbook' ),
							'type'        => 'string',
							'required'    => true,
							'enum'        => [ 'new', 'screening', 'interview', 'offer', 'hired', 'rejected', 'withdrawn' ],
						],
						'positions' => [
							'description' => __( 'Array mit ID und neuer Position', 'recruiting-playbook' ),
							'type'        => 'array',
							'required'    => true,
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'id'              => [
										'type'     => 'integer',
										'required' => true,
									],
									'kanban_position' => [
										'type'     => 'integer',
										'required' => true,
									],
								],
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Argumente für das Erstellen einer Bewerbung
	 *
	 * @return array
	 */
	private function get_create_item_args(): array {
		return [
			'job_id'           => [
				'description'       => __( 'ID der Stelle', 'recruiting-playbook' ),
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_job_id' ],
			],
			'salutation'       => [
				'description' => __( 'Anrede', 'recruiting-playbook' ),
				'type'        => 'string',
				'required'    => false,
				'enum'        => [ 'Herr', 'Frau', 'Divers', '' ],
			],
			'first_name'       => [
				'description'       => __( 'Vorname', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => [ $this, 'validate_name' ],
			],
			'last_name'        => [
				'description'       => __( 'Nachname', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => [ $this, 'validate_name' ],
			],
			'email'            => [
				'description'       => __( 'E-Mail-Adresse', 'recruiting-playbook' ),
				'type'              => 'string',
				'format'            => 'email',
				'required'          => true,
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => [ $this, 'validate_email' ],
			],
			'phone'            => [
				'description'       => __( 'Telefonnummer', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'cover_letter'     => [
				'description'       => __( 'Anschreiben', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			],
			'message'          => [
				'description'       => __( 'Anschreiben (Alias)', 'recruiting-playbook' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			],
			'privacy_consent'  => [
				'description'       => __( 'Datenschutz-Einwilligung', 'recruiting-playbook' ),
				'type'              => 'boolean',
				'required'          => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => [ $this, 'validate_privacy_consent' ],
			],
			// Spam-Schutz Felder
			'_hp_field'        => [
				'description' => __( 'Honeypot-Feld', 'recruiting-playbook' ),
				'type'        => 'string',
				'required'    => false,
			],
			'_form_timestamp'  => [
				'description' => __( 'Formular-Zeitstempel', 'recruiting-playbook' ),
				'type'        => 'integer',
				'required'    => false,
			],
			// Custom Fields (Pro) - akzeptiert JSON-String oder Objekt
			'custom_fields'    => [
				'description'       => __( 'Custom Field Werte (Pro-Feature)', 'recruiting-playbook' ),
				'required'          => false,
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitize_custom_fields' ],
			],
		];
	}

	/**
	 * Neue Bewerbung erstellen (öffentlich)
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		// HINWEIS: Für öffentliche Bewerbungsformulare verzichten wir auf strikte Nonce-Prüfung.
		// Grund: Seiten-Caching macht Nonces schnell ungültig.
		// Sicherheit wird stattdessen durch Spam-Schutz (Honeypot, Timestamp) gewährleistet.
		// Optional: Nonce prüfen falls vorhanden (zusätzliche Sicherheit ohne Caching-Probleme).
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( $nonce && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			// Nonce wurde gesendet aber ist ungültig - wahrscheinlich gecacht/abgelaufen.
			// Wir loggen dies, aber blockieren nicht (Spam-Schutz ist primäre Sicherheit).
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Recruiting Playbook: Ungültiges Nonce bei Bewerbung - möglicherweise gecachte Seite.' );
			}
		}

		// Spam-Schutz prüfen
		$spam_check = $this->spam_protection->check( $request );
		if ( is_wp_error( $spam_check ) ) {
			return $spam_check;
		}

		// Dateien verarbeiten
		$files = $request->get_file_params();

		// Custom Fields extrahieren (alle Dateien die mit custom_ beginnen).
		$custom_files = [];
		foreach ( $files as $key => $file ) {
			if ( str_starts_with( $key, 'custom_' ) ) {
				$custom_files[ $key ] = $file;
				unset( $files[ $key ] );
			}
		}

		// Custom Fields: JSON-String dekodieren falls nötig (FormData sendet Strings).
		$custom_fields = $request->get_param( 'custom_fields' ) ?: [];
		if ( is_string( $custom_fields ) ) {
			$decoded = json_decode( $custom_fields, true );
			$custom_fields = is_array( $decoded ) ? $decoded : [];
		}

		// Bewerbung erstellen
		$result = $this->application_service->create( [
			'job_id'          => $request->get_param( 'job_id' ),
			'salutation'      => $request->get_param( 'salutation' ) ?: '',
			'first_name'      => $request->get_param( 'first_name' ),
			'last_name'       => $request->get_param( 'last_name' ),
			'email'           => $request->get_param( 'email' ),
			'phone'           => $request->get_param( 'phone' ) ?: '',
			'cover_letter'    => $request->get_param( 'cover_letter' ) ?: $request->get_param( 'message' ) ?: '',
			'privacy_consent' => $request->get_param( 'privacy_consent' ),
			'ip_address'      => $this->get_client_ip(),
			'user_agent'      => $request->get_header( 'user-agent' ) ?: '',
			'files'           => $files,
			'custom_fields'   => $custom_fields,
			'custom_files'    => $custom_files,
		] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Job-Titel für Conversion Tracking.
		$job       = get_post( $request->get_param( 'job_id' ) );
		$job_title = $job ? $job->post_title : '';

		return new WP_REST_Response(
			[
				'success'        => true,
				'message'        => __( 'Ihre Bewerbung wurde erfolgreich eingereicht. Sie erhalten in Kürze eine Bestätigung per E-Mail.', 'recruiting-playbook' ),
				'application_id' => $result,
				'job_title'      => $job_title,
			],
			201
		);
	}

	/**
	 * Bewerbungen auflisten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$context = $request->get_param( 'context' ) ?: 'view';

		$args = [
			'job_id'   => $request->get_param( 'job_id' ),
			'status'   => $request->get_param( 'status' ),
			'search'   => $request->get_param( 'search' ),
			'per_page' => $request->get_param( 'per_page' ) ?: 20,
			'page'     => $request->get_param( 'page' ) ?: 1,
			'orderby'  => $request->get_param( 'orderby' ) ?: 'date',
			'order'    => $request->get_param( 'order' ) ?: 'desc',
			'context'  => $context,
		];

		// Rollen-basierter Filter: Nicht-Admins sehen nur zugewiesene Stellen.
		if ( ! current_user_can( 'manage_options' ) ) {
			$capability_service        = new CapabilityService();
			$args['assigned_job_ids']  = $capability_service->getAssignedJobIds( get_current_user_id() );
		}

		// Kanban-Kontext: Spezielle Methode mit Dokumentenanzahl.
		if ( 'kanban' === $context ) {
			$result = $this->application_service->listForKanban( $args );
		} else {
			$result = $this->application_service->list( $args );
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Einzelne Bewerbung abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$application = $this->application_service->get( $id );

		if ( ! $application ) {
			return new WP_Error(
				'rest_application_not_found',
				__( 'Bewerbung nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $application, 200 );
	}

	/**
	 * Status einer Bewerbung ändern
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_status( $request ) {
		$id              = (int) $request->get_param( 'id' );
		$status          = $request->get_param( 'status' );
		$note            = $request->get_param( 'note' ) ?: '';
		$kanban_position = $request->get_param( 'kanban_position' );

		$result = $this->application_service->updateStatus( $id, $status, $note, $kanban_position );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Status wurde aktualisiert.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Berechtigung für Auflisten prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		// Pro-Feature: API-Zugang prüfen.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return new WP_Error(
				'rest_api_access_required',
				__( 'REST API Zugang erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		// Prüfe rp_view_applications ODER manage_options (Admin-Fallback).
		if ( ! current_user_can( 'rp_view_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Bewerbungen anzuzeigen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * Berechtigung für Einzelabfrage prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Berechtigung für Aktualisierung prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		// Pro-Feature: API-Zugang prüfen.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return new WP_Error(
				'rest_api_access_required',
				__( 'REST API Zugang erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		// Prüfe edit_applications ODER manage_options (Admin-Fallback).
		if ( ! current_user_can( 'edit_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Bewerbungen zu bearbeiten.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
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
			'job_id'   => [
				'description' => __( 'Nach Stelle filtern', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
			'status'   => [
				'description' => __( 'Nach Status filtern', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'new', 'screening', 'interview', 'offer', 'hired', 'rejected', 'withdrawn' ],
			],
			'search'   => [
				'description' => __( 'Suche in Name, E-Mail', 'recruiting-playbook' ),
				'type'        => 'string',
			],
			'per_page' => [
				'description' => __( 'Ergebnisse pro Seite', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 20,
				'minimum'     => 1,
				'maximum'     => 200,
			],
			'page'     => [
				'description' => __( 'Seitennummer', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'orderby'  => [
				'description' => __( 'Sortierfeld', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'date', 'name', 'status', 'kanban_position' ],
				'default'     => 'date',
			],
			'order'    => [
				'description' => __( 'Sortierrichtung', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'asc', 'desc' ],
				'default'     => 'desc',
			],
			'context'  => [
				'description' => __( 'Kontext für die Abfrage (kanban: mit Dokumentenanzahl)', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'view', 'kanban' ],
				'default'     => 'view',
			],
		];
	}

	/**
	 * Custom Fields sanitize
	 *
	 * Dekodiert JSON-Strings und stellt sicher, dass ein Array zurückgegeben wird.
	 *
	 * @param mixed $value Wert (JSON-String oder Array).
	 * @return array
	 */
	public function sanitize_custom_fields( $value ): array {
		// JSON-String dekodieren falls nötig.
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			return is_array( $decoded ) ? $decoded : [];
		}
		return is_array( $value ) ? $value : [];
	}

	/**
	 * Job-ID validieren
	 *
	 * @param mixed $value Wert.
	 * @return bool|WP_Error
	 */
	public function validate_job_id( $value ) {
		if ( ! is_numeric( $value ) || (int) $value <= 0 ) {
			return new WP_Error(
				'invalid_job_id',
				__( 'Ungültige Stellen-ID.', 'recruiting-playbook' )
			);
		}

		$post = get_post( (int) $value );
		if ( ! $post || 'job_listing' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_Error(
				'job_not_found',
				__( 'Die angegebene Stelle existiert nicht oder ist nicht verfügbar.', 'recruiting-playbook' )
			);
		}

		return true;
	}

	/**
	 * Name validieren
	 *
	 * @param mixed $value Wert.
	 * @return bool|WP_Error
	 */
	public function validate_name( $value ) {
		if ( empty( trim( $value ) ) ) {
			return new WP_Error(
				'invalid_name',
				__( 'Name darf nicht leer sein.', 'recruiting-playbook' )
			);
		}

		if ( strlen( $value ) > 100 ) {
			return new WP_Error(
				'name_too_long',
				__( 'Name ist zu lang (max. 100 Zeichen).', 'recruiting-playbook' )
			);
		}

		return true;
	}

	/**
	 * E-Mail validieren
	 *
	 * @param mixed $value Wert.
	 * @return bool|WP_Error
	 */
	public function validate_email( $value ) {
		if ( ! is_email( $value ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Ungültige E-Mail-Adresse.', 'recruiting-playbook' )
			);
		}

		return true;
	}

	/**
	 * Datenschutz-Einwilligung validieren
	 *
	 * @param mixed $value Wert.
	 * @return bool|WP_Error
	 */
	public function validate_privacy_consent( $value ) {
		if ( ! $value ) {
			return new WP_Error(
				'privacy_consent_required',
				__( 'Die Einwilligung zur Datenschutzerklärung ist erforderlich.', 'recruiting-playbook' )
			);
		}

		return true;
	}

	/**
	 * Client-IP ermitteln
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$ip_keys = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Bei X-Forwarded-For kann es mehrere IPs geben
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Bewerbung löschen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id          = (int) $request->get_param( 'id' );
		$hard_delete = (bool) $request->get_param( 'hard_delete' );

		$gdpr_service = new GdprService();

		if ( $hard_delete ) {
			$result = $gdpr_service->hardDeleteApplication( $id );
		} else {
			$result = $gdpr_service->softDeleteApplication( $id );
		}

		if ( ! $result ) {
			return new WP_Error(
				'rest_delete_failed',
				__( 'Bewerbung konnte nicht gelöscht werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => $hard_delete
					? __( 'Bewerbung wurde endgültig gelöscht.', 'recruiting-playbook' )
					: __( 'Bewerbung wurde gelöscht.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Berechtigung für Löschen prüfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		// Pro-Feature: API-Zugang prüfen.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'api_access' ) ) {
			return new WP_Error(
				'rest_api_access_required',
				__( 'REST API Zugang erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		// Prüfe delete_applications ODER manage_options (Admin-Fallback).
		if ( ! current_user_can( 'delete_applications' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Bewerbungen zu löschen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * Download-URL für Dokument generieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_download_url( $request ) {
		$document_id = (int) $request->get_param( 'id' );

		$url = DocumentDownloadService::generateDownloadUrl( $document_id );

		return new WP_REST_Response(
			[
				'url'        => $url,
				'expires_in' => 3600, // 1 Stunde
			],
			200
		);
	}

	/**
	 * Kandidaten-Daten exportieren (DSGVO)
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function export_candidate_data( $request ) {
		$candidate_id = (int) $request->get_param( 'id' );

		$gdpr_service = new GdprService();
		$data         = $gdpr_service->exportCandidateData( $candidate_id );

		if ( empty( $data ) ) {
			return new WP_Error(
				'rest_candidate_not_found',
				__( 'Kandidat nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Statistiken abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_stats( $request ) {
		global $wpdb;

		$applications_table = $wpdb->prefix . 'rp_applications';

		// Status-Verteilung
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$status_counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$applications_table} GROUP BY status",
			OBJECT_K
		);

		// Bewerbungen pro Tag (letzte 30 Tage)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$daily_counts = $wpdb->get_results(
			"SELECT DATE(created_at) as date, COUNT(*) as count
			 FROM {$applications_table}
			 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			 GROUP BY DATE(created_at)
			 ORDER BY date ASC"
		);

		// Top-Jobs nach Bewerbungen
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$top_jobs = $wpdb->get_results(
			"SELECT job_id, COUNT(*) as count
			 FROM {$applications_table}
			 GROUP BY job_id
			 ORDER BY count DESC
			 LIMIT 5"
		);

		foreach ( $top_jobs as &$job ) {
			$post       = get_post( $job->job_id );
			$job->title = $post ? $post->post_title : __( 'Gelöscht', 'recruiting-playbook' );
		}

		$total = 0;
		foreach ( $status_counts as $status ) {
			$total += (int) $status->count;
		}

		return new WP_REST_Response(
			[
				'status_counts' => $status_counts,
				'daily_counts'  => $daily_counts,
				'top_jobs'      => $top_jobs,
				'total'         => $total,
			],
			200
		);
	}

	/**
	 * Kanban: Positionen neu sortieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reorder_applications( $request ) {
		// CSRF-Schutz: Nonce validieren (Defense in Depth).
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Ungültiges Sicherheitstoken. Bitte laden Sie die Seite neu.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// Rate Limiting: Max 1 Request pro Sekunde pro User (DoS-Schutz).
		$user_id       = get_current_user_id();
		$transient_key = "rp_reorder_limit_{$user_id}";
		if ( get_transient( $transient_key ) ) {
			return new WP_Error(
				'too_many_requests',
				__( 'Zu viele Anfragen. Bitte warten Sie kurz.', 'recruiting-playbook' ),
				[ 'status' => 429 ]
			);
		}
		set_transient( $transient_key, true, 1 );

		$status    = $request->get_param( 'status' );
		$positions = $request->get_param( 'positions' );

		if ( empty( $positions ) || ! is_array( $positions ) ) {
			return new WP_Error(
				'invalid_positions',
				__( 'Ungültige Positionen.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$result = $this->application_service->reorderPositions( $status, $positions );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Positionen wurden aktualisiert.', 'recruiting-playbook' ),
				'updated' => $result,
			],
			200
		);
	}
}

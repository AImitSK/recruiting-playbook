<?php
/**
 * FormConfig Controller - REST API für Step-basierte Formular-Konfiguration
 *
 * @package RecruitingPlaybook\Api
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\FormConfigService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Formular-Konfiguration
 */
class FormConfigController extends WP_REST_Controller {

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
	protected $rest_base = 'form-builder';

	/**
	 * Service
	 *
	 * @var FormConfigService
	 */
	private FormConfigService $service;

	/**
	 * Konstruktor
	 */
	public function __construct() {
		$this->service = new FormConfigService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /form-builder/config — Builder-Daten abrufen (Draft + Felder + Status).
		// PUT /form-builder/config — Draft speichern.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/config',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_config' ],
					'permission_callback' => [ $this, 'get_config_permissions_check' ],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'save_config' ],
					'permission_callback' => [ $this, 'edit_config_permissions_check' ],
					'args'                => [
						'steps' => [
							'description' => __( 'Step-Konfiguration', 'recruiting-playbook' ),
							'type'        => 'array',
							'required'    => true,
						],
						'settings' => [
							'description' => __( 'Formular-Einstellungen', 'recruiting-playbook' ),
							'type'        => 'object',
						],
					],
				],
			]
		);

		// POST /form-builder/publish — Draft veröffentlichen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/publish',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'publish_config' ],
					'permission_callback' => [ $this, 'edit_config_permissions_check' ],
				],
			]
		);

		// POST /form-builder/discard — Draft verwerfen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/discard',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'discard_config' ],
					'permission_callback' => [ $this, 'edit_config_permissions_check' ],
				],
			]
		);

		// GET /form-builder/published — Veröffentlichte Konfiguration (für Frontend).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/published',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_published' ],
					'permission_callback' => '__return_true', // Öffentlich für Frontend.
				],
			]
		);

		// GET /form-builder/active-fields — Aktive Felder für ApplicantDetail.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/active-fields',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_active_fields' ],
					'permission_callback' => [ $this, 'get_config_permissions_check' ],
				],
			]
		);

		// POST /form-builder/reset — Auf Standard zurücksetzen.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reset',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'reset_config' ],
					'permission_callback' => [ $this, 'edit_config_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Berechtigungsprüfung: Konfiguration lesen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_config_permissions_check( $request ): bool|WP_Error {
		$gate = $this->check_feature_gate();

		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		if ( ! current_user_can( 'rp_manage_forms' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, den Formular-Builder zu nutzen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigungsprüfung: Konfiguration bearbeiten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function edit_config_permissions_check( $request ): bool|WP_Error {
		$gate = $this->check_feature_gate();

		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		if ( ! current_user_can( 'rp_manage_forms' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, die Formular-Konfiguration zu bearbeiten.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Builder-Daten abrufen
	 *
	 * Liefert Draft-Konfiguration + verfügbare Felder + Publish-Status.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_config( $request ): WP_REST_Response|WP_Error {
		try {
			$data = $this->service->getBuilderData();

			return new WP_REST_Response( $data, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'server_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Draft speichern
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_config( $request ): WP_REST_Response|WP_Error {
		try {
			$config = [
				'steps'    => $request->get_param( 'steps' ) ?? [],
				'settings' => $request->get_param( 'settings' ) ?? [],
				'version'  => $request->get_param( 'version' ) ?? 1,
			];

			$result = $this->service->saveDraft( $config );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return new WP_REST_Response(
				[
					'success'     => true,
					'message'     => __( 'Entwurf gespeichert.', 'recruiting-playbook' ),
					'has_changes' => $this->service->hasUnpublishedChanges(),
				],
				200
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'server_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Draft veröffentlichen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function publish_config( $request ): WP_REST_Response|WP_Error {
		try {
			$result = $this->service->publish();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return new WP_REST_Response(
				[
					'success'           => true,
					'message'           => __( 'Formular veröffentlicht.', 'recruiting-playbook' ),
					'published_version' => $this->service->getPublishedVersion(),
					'has_changes'       => false,
				],
				200
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'server_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Draft verwerfen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function discard_config( $request ): WP_REST_Response|WP_Error {
		try {
			$result = $this->service->discardDraft();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return new WP_REST_Response(
				[
					'success'     => true,
					'message'     => __( 'Änderungen verworfen.', 'recruiting-playbook' ),
					'draft'       => $this->service->getDraft(),
					'has_changes' => false,
				],
				200
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'server_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Veröffentlichte Konfiguration abrufen (für Frontend)
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_published( $request ): WP_REST_Response|WP_Error {
		try {
			$config = $this->service->getPublished();

			return new WP_REST_Response(
				[
					'config'  => $config,
					'version' => $this->service->getPublishedVersion(),
				],
				200
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'server_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Aktive (sichtbare) Felder abrufen
	 *
	 * Liefert alle in der Published-Konfiguration sichtbaren Felder
	 * mit ihren vollständigen Definitionen für die ApplicantDetail-Ansicht.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_active_fields( $request ): WP_REST_Response|WP_Error {
		try {
			$data = $this->service->getActiveFields();

			// Response-Validierung: Sicherstellen dass erwartete Struktur vorhanden ist.
			if ( ! is_array( $data ) || ! isset( $data['fields'], $data['system_fields'] ) ) {
				return new WP_Error(
					'invalid_data',
					__( 'Fehler beim Laden der aktiven Felder', 'recruiting-playbook' ),
					[ 'status' => 500 ]
				);
			}

			return new WP_REST_Response( $data, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'server_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Konfiguration auf Standard zurücksetzen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reset_config( $request ): WP_REST_Response|WP_Error {
		try {
			$result = $this->service->resetToDefault();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Neue Default-Konfiguration laden und zurückgeben.
			$data = $this->service->getBuilderData();

			return new WP_REST_Response(
				[
					'success'     => true,
					'message'     => __( 'Formular auf Standard zurückgesetzt.', 'recruiting-playbook' ),
					'draft'       => $data['draft'],
					'has_changes' => false,
				],
				200
			);
		} catch ( \Exception $e ) {
			return new WP_Error(
				'server_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
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
				__( 'Der Formular-Builder erfordert eine Pro-Lizenz.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}

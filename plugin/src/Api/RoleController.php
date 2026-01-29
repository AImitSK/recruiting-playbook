<?php
/**
 * Role Controller - REST API für Rollen-Verwaltung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Core\RoleManager;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Benutzerrollen
 */
class RoleController extends WP_REST_Controller {

	/**
	 * API-Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /roles — Alle Rollen abrufen.
		register_rest_route(
			$this->namespace,
			'/roles',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		// GET /roles/capabilities — Capability-Gruppen abrufen.
		register_rest_route(
			$this->namespace,
			'/roles/capabilities',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_capabilities' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		// PUT /roles/{slug} — Capabilities einer Rolle aktualisieren.
		register_rest_route(
			$this->namespace,
			'/roles/(?P<slug>[a-z_]+)',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'slug'         => [
							'description' => __( 'Rollen-Slug', 'recruiting-playbook' ),
							'type'        => 'string',
							'required'    => true,
						],
						'capabilities' => [
							'description' => __( 'Capability-Konfiguration', 'recruiting-playbook' ),
							'type'        => 'object',
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Berechtigungsprüfung: Rollen lesen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ): bool|WP_Error {
		$gate = $this->check_feature_gate();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		if ( ! current_user_can( 'rp_manage_roles' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, Rollen zu verwalten.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Berechtigungsprüfung: Rolle aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ): bool|WP_Error {
		$gate = $this->check_feature_gate();
		if ( is_wp_error( $gate ) ) {
			return $gate;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Nur Administratoren können Rollen-Berechtigungen ändern.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Alle Rollen abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$config    = get_option( 'rp_role_capabilities', RoleManager::getDefaults() );
		$roles     = [];
		$all_caps  = RoleManager::getAllCapabilities();

		foreach ( $config as $role_slug => $capabilities ) {
			$wp_role = get_role( $role_slug );
			if ( ! $wp_role ) {
				continue;
			}

			// User-Count für diese Rolle.
			$user_count = count(
				get_users( [
					'role'   => $role_slug,
					'fields' => 'ID',
				] )
			);

			// Nur rp_* Capabilities zurückgeben.
			$rp_caps = [];
			foreach ( $all_caps as $cap ) {
				$rp_caps[ $cap ] = ! empty( $capabilities[ $cap ] );
			}

			$roles[] = [
				'slug'         => $role_slug,
				'name'         => translate_user_role( $wp_role->name ),
				'capabilities' => $rp_caps,
				'user_count'   => $user_count,
			];
		}

		return new WP_REST_Response( [ 'roles' => $roles ], 200 );
	}

	/**
	 * Capability-Gruppen abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_capabilities( $request ): WP_REST_Response {
		$groups = RoleManager::getCapabilityGroups();

		return new WP_REST_Response( [ 'groups' => $groups ], 200 );
	}

	/**
	 * Capabilities einer Rolle aktualisieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$slug         = $request->get_param( 'slug' );
		$capabilities = $request->get_param( 'capabilities' );

		// Nur Custom Rollen erlauben.
		$allowed_roles = [ 'rp_recruiter', 'rp_hiring_manager' ];
		if ( ! in_array( $slug, $allowed_roles, true ) ) {
			return new WP_Error(
				'invalid_role',
				__( 'Nur Custom Recruiting-Rollen können bearbeitet werden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// Rolle muss existieren.
		$wp_role = get_role( $slug );
		if ( ! $wp_role ) {
			return new WP_Error(
				'not_found',
				__( 'Rolle nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Nur bekannte rp_* Capabilities akzeptieren.
		$all_caps    = RoleManager::getAllCapabilities();
		$valid_caps  = [];
		foreach ( $capabilities as $cap => $granted ) {
			if ( in_array( $cap, $all_caps, true ) ) {
				$valid_caps[ $cap ] = (bool) $granted;
			}
		}

		// Admin-only Capabilities können nicht an Custom-Rollen vergeben werden.
		$admin_only = [ 'rp_manage_roles', 'rp_assign_jobs' ];
		foreach ( $admin_only as $cap ) {
			$valid_caps[ $cap ] = false;
		}

		// Konfiguration speichern.
		$config          = get_option( 'rp_role_capabilities', RoleManager::getDefaults() );
		$config[ $slug ] = $valid_caps;
		update_option( 'rp_role_capabilities', $config );

		// WordPress-Rolle aktualisieren.
		RoleManager::assignCapabilities();

		return new WP_REST_Response(
			[
				'slug'         => $slug,
				'name'         => translate_user_role( $wp_role->name ),
				'capabilities' => $valid_caps,
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

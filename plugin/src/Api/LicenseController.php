<?php
/**
 * REST API Controller für Lizenzierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Licensing\LicenseManager;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST API Controller für Lizenzierung
 */
class LicenseController extends WP_REST_Controller {

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
	protected $rest_base = 'license';

	/**
	 * License Manager
	 *
	 * @var LicenseManager
	 */
	private LicenseManager $license_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->license_manager = LicenseManager::get_instance();
	}

	/**
	 * Register routes
	 */
	public function register_routes(): void {
		// GET /license - Get license status.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
			)
		);

		// POST /license/activate - Activate license.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'activate' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
					'args'                => array(
						'license_key' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => __( 'Der Lizenzschlüssel', 'recruiting-playbook' ),
						),
					),
				),
			)
		);

		// POST /license/deactivate - Deactivate license.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/deactivate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'deactivate' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Check admin permissions
	 *
	 * @return bool|WP_Error
	 */
	public function admin_permissions_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung für diese Aktion.', 'recruiting-playbook' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get license status
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		$status = $this->license_manager->get_status();

		return new WP_REST_Response( $status, 200 );
	}

	/**
	 * Activate license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function activate( WP_REST_Request $request ): WP_REST_Response {
		$license_key = $request->get_param( 'license_key' );
		$result      = $this->license_manager->activate( $license_key );

		if ( $result['success'] ) {
			$status = $this->license_manager->get_status();

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => $result['message'],
					'status'  => $status,
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => $result['message'],
				'error'   => $result['error'] ?? 'unknown_error',
			),
			400
		);
	}

	/**
	 * Deactivate license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function deactivate( WP_REST_Request $request ): WP_REST_Response {
		$result = $this->license_manager->deactivate();

		if ( $result['success'] ) {
			$status = $this->license_manager->get_status();

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => $result['message'],
					'status'  => $status,
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => $result['message'],
				'error'   => $result['error'] ?? 'unknown_error',
			),
			400
		);
	}
}

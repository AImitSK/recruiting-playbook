<?php
/**
 * System Status REST API Controller
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\SystemStatusService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API Controller für Systemstatus
 */
class SystemStatusController extends WP_REST_Controller {

	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = 'recruiting/v1';

	/**
	 * System Status Service
	 *
	 * @var SystemStatusService
	 */
	private SystemStatusService $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new SystemStatusService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// GET /system/status - Systemstatus abrufen.
		register_rest_route(
			$this->namespace,
			'/system/status',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => [ $this, 'admin_permissions_check' ],
			]
		);

		// POST /system/cleanup/documents - Verwaiste Dokumente bereinigen.
		register_rest_route(
			$this->namespace,
			'/system/cleanup/documents',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'cleanup_documents' ],
				'permission_callback' => [ $this, 'cleanup_permissions_check' ],
			]
		);

		// POST /system/cleanup/applications - Verwaiste Bewerbungen bereinigen.
		register_rest_route(
			$this->namespace,
			'/system/cleanup/applications',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'cleanup_applications' ],
				'permission_callback' => [ $this, 'cleanup_permissions_check' ],
			]
		);
	}

	/**
	 * Systemstatus abrufen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		$status = $this->service->getStatus();

		return new WP_REST_Response( $status, 200 );
	}

	/**
	 * Verwaiste Dokumente bereinigen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response
	 */
	public function cleanup_documents( WP_REST_Request $request ): WP_REST_Response {
		$deleted = $this->service->cleanupOrphanedDocuments();

		return new WP_REST_Response( [
			'success' => true,
			'deleted' => $deleted,
			'message' => sprintf(
				/* translators: %d: Number of deleted documents */
				__( '%d orphaned documents deleted', 'recruiting-playbook' ),
				$deleted
			),
		], 200 );
	}

	/**
	 * Verwaiste Bewerbungen bereinigen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return WP_REST_Response
	 */
	public function cleanup_applications( WP_REST_Request $request ): WP_REST_Response {
		$deleted = $this->service->cleanupOrphanedApplications();

		return new WP_REST_Response( [
			'success' => true,
			'deleted' => $deleted,
			'message' => sprintf(
				/* translators: %d: Number of deleted applications */
				__( '%d orphaned applications deleted', 'recruiting-playbook' ),
				$deleted
			),
		], 200 );
	}

	/**
	 * Admin Permission Check
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return bool|WP_Error
	 */
	public function admin_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		// Systemstatus ist nur für Admins oder User mit entsprechender Capability.
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'rp_view_system_status' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to view system status.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Cleanup Permission Check
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return bool|WP_Error
	 */
	public function cleanup_permissions_check( WP_REST_Request $request ): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'recruiting-playbook' ),
				[ 'status' => 401 ]
			);
		}

		// Cleanup-Aktionen nur für Admins oder User mit entsprechender Capability.
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'rp_run_cleanup' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'No permission to run cleanup.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}

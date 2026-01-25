<?php
/**
 * REST API Controller für E-Mail-Historie
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\EmailLogRepository;
use RecruitingPlaybook\Services\EmailQueueService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST API Controller für E-Mail-Historie
 */
class EmailLogController extends WP_REST_Controller {

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
	protected $rest_base = 'emails/log';

	/**
	 * Log Repository
	 *
	 * @var EmailLogRepository
	 */
	private EmailLogRepository $log_repository;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->log_repository = new EmailLogRepository();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// E-Mail-Log auflisten
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

		// Einzelner Log-Eintrag
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
							'description' => __( 'Log-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// E-Mail erneut senden
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/resend',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'resend_email' ],
					'permission_callback' => [ $this, 'resend_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Log-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// E-Mails einer Bewerbung
		register_rest_route(
			$this->namespace,
			'/applications/(?P<id>[\d]+)/emails',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_application_emails' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => array_merge(
						[
							'id' => [
								'description' => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
								'type'        => 'integer',
								'required'    => true,
							],
						],
						$this->get_collection_params()
					),
				],
			]
		);

		// E-Mails eines Kandidaten
		register_rest_route(
			$this->namespace,
			'/candidates/(?P<id>[\d]+)/emails',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_candidate_emails' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => array_merge(
						[
							'id' => [
								'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
								'type'        => 'integer',
								'required'    => true,
							],
						],
						$this->get_collection_params()
					),
				],
			]
		);

		// Geplante E-Mails
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/scheduled',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_scheduled' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
			]
		);
	}

	/**
	 * E-Mail-Log auflisten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$args = $this->buildQueryArgs( $request );

		$result = $this->log_repository->paginate( $args );

		// Daten anreichern.
		$result['items'] = array_map( [ $this, 'enrichLogEntry' ], $result['items'] );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Einzelner Log-Eintrag
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$id = (int) $request->get_param( 'id' );

		$log = $this->log_repository->find( $id );

		if ( ! $log ) {
			return new WP_Error(
				'rest_log_not_found',
				__( 'E-Mail-Log nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $this->enrichLogEntry( $log, true ), 200 );
	}

	/**
	 * E-Mail erneut senden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function resend_email( $request ) {
		$id = (int) $request->get_param( 'id' );

		$queue_service = new EmailQueueService();
		$new_log_id    = $queue_service->resend( $id );

		if ( false === $new_log_id ) {
			return new WP_Error(
				'rest_resend_failed',
				__( 'E-Mail konnte nicht erneut gesendet werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response(
			[
				'success'      => true,
				'message'      => __( 'E-Mail wurde erneut in die Warteschlange eingereiht.', 'recruiting-playbook' ),
				'email_log_id' => $new_log_id,
			],
			200
		);
	}

	/**
	 * E-Mails einer Bewerbung
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_application_emails( $request ) {
		$application_id = (int) $request->get_param( 'id' );

		$args                   = $this->buildQueryArgs( $request );
		$args['application_id'] = $application_id;

		$result = $this->log_repository->paginate( $args );

		// Daten anreichern.
		$result['items'] = array_map( [ $this, 'enrichLogEntry' ], $result['items'] );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * E-Mails eines Kandidaten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_candidate_emails( $request ) {
		$candidate_id = (int) $request->get_param( 'id' );

		$args                 = $this->buildQueryArgs( $request );
		$args['candidate_id'] = $candidate_id;

		$result = $this->log_repository->paginate( $args );

		// Daten anreichern.
		$result['items'] = array_map( [ $this, 'enrichLogEntry' ], $result['items'] );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Geplante E-Mails abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_scheduled( $request ) {
		$queue_service = new EmailQueueService();

		$args   = $this->buildQueryArgs( $request );
		$result = $queue_service->getScheduled( $args );

		// Daten anreichern.
		$result['items'] = array_map( [ $this, 'enrichLogEntry' ], $result['items'] ?? [] );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Berechtigung für Auflisten prüfen
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
				'rp_view_email_log',
				'rest_email_log_required',
				__( 'Sie haben keine Berechtigung, die E-Mail-Historie anzuzeigen.', 'recruiting-playbook' )
			);
		}

		// Fallback falls Helper nicht verfügbar.
		// 1. Capability-Check (WordPress-Core-Security).
		if ( ! current_user_can( 'rp_view_email_log' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, die E-Mail-Historie anzuzeigen.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// 2. Feature-Flag-Check (Business-Logic).
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return new WP_Error(
				'rest_email_log_required',
				__( 'E-Mail-Historie erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		return true;
	}

	/**
	 * Berechtigung für erneutes Senden prüfen
	 *
	 * Prüft: 1. WordPress Capability, 2. Feature-Flag (Pro erforderlich).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function resend_permissions_check( $request ) {
		// Verwende Helper-Funktion für konsistente Prüfung.
		if ( function_exists( 'rp_check_feature_permission' ) ) {
			return rp_check_feature_permission(
				'email_templates',
				'rp_send_emails',
				'rest_email_resend_required',
				__( 'Sie haben keine Berechtigung, E-Mails erneut zu senden.', 'recruiting-playbook' )
			);
		}

		// Fallback falls Helper nicht verfügbar.
		// 1. Capability-Check (WordPress-Core-Security).
		if ( ! current_user_can( 'rp_send_emails' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, E-Mails erneut zu senden.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// 2. Feature-Flag-Check (Business-Logic).
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return new WP_Error(
				'rest_email_resend_required',
				__( 'E-Mail erneut senden erfordert Pro.', 'recruiting-playbook' ),
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
			'per_page'       => [
				'description' => __( 'Ergebnisse pro Seite', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 20,
				'minimum'     => 1,
				'maximum'     => 100,
			],
			'page'           => [
				'description' => __( 'Seitennummer', 'recruiting-playbook' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			],
			'status'         => [
				'description' => __( 'Nach Status filtern', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'pending', 'queued', 'sent', 'failed', 'cancelled' ],
			],
			'application_id' => [
				'description' => __( 'Nach Bewerbung filtern', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
			'candidate_id'   => [
				'description' => __( 'Nach Kandidat filtern', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
			'template_id'    => [
				'description' => __( 'Nach Template filtern', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
			'date_from'      => [
				'description' => __( 'Datum von (Y-m-d)', 'recruiting-playbook' ),
				'type'        => 'string',
				'format'      => 'date',
			],
			'date_to'        => [
				'description' => __( 'Datum bis (Y-m-d)', 'recruiting-playbook' ),
				'type'        => 'string',
				'format'      => 'date',
			],
			'search'         => [
				'description'       => __( 'Suche in Betreff, Empfänger', 'recruiting-playbook' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'orderby'        => [
				'description' => __( 'Sortierfeld', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'created_at', 'sent_at', 'status' ],
				'default'     => 'created_at',
			],
			'order'          => [
				'description' => __( 'Sortierrichtung', 'recruiting-playbook' ),
				'type'        => 'string',
				'enum'        => [ 'asc', 'desc' ],
				'default'     => 'desc',
			],
		];
	}

	/**
	 * Query-Argumente aus Request aufbauen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array
	 */
	private function buildQueryArgs( WP_REST_Request $request ): array {
		$args = [
			'per_page'       => $request->get_param( 'per_page' ) ?: 20,
			'page'           => $request->get_param( 'page' ) ?: 1,
			'status'         => $request->get_param( 'status' ),
			'application_id' => $request->get_param( 'application_id' ),
			'candidate_id'   => $request->get_param( 'candidate_id' ),
			'template_id'    => $request->get_param( 'template_id' ),
			'date_from'      => $request->get_param( 'date_from' ),
			'date_to'        => $request->get_param( 'date_to' ),
			'search'         => $request->get_param( 'search' ),
			'orderby'        => $request->get_param( 'orderby' ) ?: 'created_at',
			'order'          => $request->get_param( 'order' ) ?: 'desc',
		];

		// Null-Werte entfernen.
		return array_filter( $args, fn( $v ) => null !== $v );
	}

	/**
	 * Log-Eintrag mit zusätzlichen Daten anreichern
	 *
	 * @param array $log          Log-Eintrag.
	 * @param bool  $include_body Body inkludieren.
	 * @return array
	 */
	private function enrichLogEntry( array $log, bool $include_body = false ): array {
		$enriched = [
			'id'        => (int) $log['id'],
			'recipient' => [
				'email' => $log['recipient_email'],
				'name'  => $log['recipient_name'],
			],
			'sender'    => [
				'email' => $log['sender_email'],
				'name'  => $log['sender_name'],
			],
			'subject'   => $log['subject'],
			'status'    => $log['status'],
		];

		// Body nur bei Einzelabfrage inkludieren.
		if ( $include_body ) {
			$enriched['body_html'] = $log['body_html'];
			$enriched['body_text'] = $log['body_text'];
		}

		// Bewerbungs-Info.
		if ( ! empty( $log['application_id'] ) ) {
			$enriched['application'] = [
				'id' => (int) $log['application_id'],
			];

			// Job-Titel laden falls verfügbar.
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$app_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT a.job_id, c.first_name, c.last_name
					 FROM {$wpdb->prefix}rp_applications a
					 LEFT JOIN {$wpdb->prefix}rp_candidates c ON a.candidate_id = c.id
					 WHERE a.id = %d",
					$log['application_id']
				),
				ARRAY_A
			);

			if ( $app_data ) {
				$job_post = get_post( $app_data['job_id'] );
				$enriched['application']['job_title']      = $job_post ? $job_post->post_title : '';
				$enriched['application']['candidate_name'] = trim( $app_data['first_name'] . ' ' . $app_data['last_name'] );
			}
		}

		// Template-Info.
		if ( ! empty( $log['template_id'] ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$template_name = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT name FROM {$wpdb->prefix}rp_email_templates WHERE id = %d",
					$log['template_id']
				)
			);

			$enriched['template'] = [
				'id'   => (int) $log['template_id'],
				'name' => $template_name ?: '',
			];
		}

		// Absender-Info (User).
		if ( ! empty( $log['sent_by'] ) ) {
			$user = get_userdata( $log['sent_by'] );
			$enriched['sent_by'] = [
				'id'   => (int) $log['sent_by'],
				'name' => $user ? $user->display_name : '',
			];
		}

		// Zeitstempel.
		$enriched['created_at']   = $log['created_at'];
		$enriched['scheduled_at'] = $log['scheduled_at'];
		$enriched['sent_at']      = $log['sent_at'];

		// Fehler-Info.
		if ( 'failed' === $log['status'] && ! empty( $log['error_message'] ) ) {
			$enriched['error_message'] = $log['error_message'];
		}

		// Retry-Info.
		if ( ! empty( $log['metadata']['retry_count'] ) ) {
			$enriched['retry_count'] = (int) $log['metadata']['retry_count'];
		}

		// Kann erneut gesendet werden?
		$enriched['can_resend'] = in_array( $log['status'], [ 'sent', 'failed' ], true );

		// Kann storniert werden?
		$enriched['can_cancel'] = 'pending' === $log['status'] && ! empty( $log['scheduled_at'] );

		return $enriched;
	}
}

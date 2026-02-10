<?php
/**
 * REST API Controller für E-Mail-Versand
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\EmailService;
use RecruitingPlaybook\Services\EmailTemplateService;
use RecruitingPlaybook\Services\EmailQueueService;
use RecruitingPlaybook\Services\PlaceholderService;
use RecruitingPlaybook\Services\ApplicationService;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST API Controller für E-Mail-Versand
 */
class EmailController extends WP_REST_Controller {

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
	protected $rest_base = 'emails';

	/**
	 * Email Service
	 *
	 * @var EmailService
	 */
	private EmailService $email_service;

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
	 * Application Service
	 *
	 * @var ApplicationService
	 */
	private ApplicationService $application_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->email_service       = new EmailService();
		$this->template_service    = new EmailTemplateService();
		$this->placeholder_service = new PlaceholderService();
		$this->application_service = new ApplicationService();
	}

	/**
	 * Routen registrieren
	 */
	public function register_routes(): void {
		// E-Mail senden
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/send',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'send_email' ],
					'permission_callback' => [ $this, 'send_email_permissions_check' ],
					'args'                => $this->get_send_args(),
				],
			]
		);

		// E-Mail-Vorschau
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preview',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'preview_email' ],
					'permission_callback' => [ $this, 'send_email_permissions_check' ],
					'args'                => $this->get_preview_args(),
				],
			]
		);

		// Geplante E-Mail stornieren
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/cancel',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'cancel_email' ],
					'permission_callback' => [ $this, 'send_email_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'E-Mail-Log-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Queue-Status
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/queue-stats',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_queue_stats' ],
					'permission_callback' => [ $this, 'send_email_permissions_check' ],
				],
			]
		);

		// Bulk-E-Mail senden
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/send-bulk',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'send_bulk_email' ],
					'permission_callback' => [ $this, 'send_email_permissions_check' ],
					'args'                => [
						'application_ids'  => [
							'description' => __( 'Array von Bewerbungs-IDs', 'recruiting-playbook' ),
							'type'        => 'array',
							'items'       => [ 'type' => 'integer' ],
							'required'    => true,
						],
						'template_id'      => [
							'description' => __( 'Template-ID', 'recruiting-playbook' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'custom_variables' => [
							'description' => __( 'Globale Platzhalter-Werte für alle E-Mails', 'recruiting-playbook' ),
							'type'        => 'object',
						],
						'send_immediately' => [
							'description' => __( 'Sofort senden (nicht in Queue)', 'recruiting-playbook' ),
							'type'        => 'boolean',
							'default'     => false,
						],
					],
				],
			]
		);
	}

	/**
	 * E-Mail senden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function send_email( $request ) {
		$application_id   = (int) $request->get_param( 'application_id' );
		$template_id      = $request->get_param( 'template_id' );
		$subject          = $request->get_param( 'subject' );
		$body             = $request->get_param( 'body' );
		$custom_variables = $request->get_param( 'custom_variables' ) ?: [];
		$send_immediately = (bool) $request->get_param( 'send_immediately' );
		$scheduled_at     = $request->get_param( 'scheduled_at' );
		$signature_id     = $request->get_param( 'signature_id' ); // null = auto, 0 = keine.

		// Bewerbung validieren.
		$application = $this->application_service->get( $application_id );
		if ( ! $application ) {
			return new WP_Error(
				'rest_application_not_found',
				__( 'Bewerbung nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Entweder Template-ID oder Subject+Body erforderlich.
		if ( empty( $template_id ) && ( empty( $subject ) || empty( $body ) ) ) {
			return new WP_Error(
				'rest_email_invalid_params',
				__( 'Entweder Template-ID oder Betreff und Inhalt erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$use_queue = ! $send_immediately;

		// Mit Template senden.
		if ( ! empty( $template_id ) ) {
			// Betreff/Body überschreiben wenn angegeben.
			if ( ! empty( $subject ) || ! empty( $body ) ) {
				$custom_variables['_override_subject'] = $subject;
				$custom_variables['_override_body']    = $body;
			}

			if ( $scheduled_at ) {
				$result = $this->email_service->scheduleEmail(
					(int) $template_id,
					$application_id,
					$scheduled_at,
					$custom_variables
				);
			} else {
				$result = $this->email_service->sendWithTemplate(
					(int) $template_id,
					$application_id,
					$custom_variables,
					$use_queue,
					$signature_id
				);
			}
		} else {
			// Custom E-Mail senden.
			$result = $this->email_service->sendCustomEmail(
				$application_id,
				$subject,
				$body,
				$use_queue,
				$signature_id
			);
		}

		if ( false === $result ) {
			return new WP_Error(
				'rest_email_send_failed',
				__( 'E-Mail konnte nicht gesendet werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$status  = $scheduled_at ? 'scheduled' : ( $use_queue ? 'queued' : 'sent' );
		$message = match ( $status ) {
			'scheduled' => __( 'E-Mail wurde für den Versand geplant.', 'recruiting-playbook' ),
			'queued'    => __( 'E-Mail wurde in die Warteschlange eingereiht.', 'recruiting-playbook' ),
			'sent'      => __( 'E-Mail wurde gesendet.', 'recruiting-playbook' ),
		};

		return new WP_REST_Response(
			[
				'success'      => true,
				'message'      => $message,
				'email_log_id' => is_int( $result ) ? $result : null,
				'status'       => $status,
				'scheduled_at' => $scheduled_at,
			],
			200
		);
	}

	/**
	 * E-Mail-Vorschau generieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function preview_email( $request ) {
		$application_id   = $request->get_param( 'application_id' );
		$template_id      = $request->get_param( 'template_id' );
		$subject          = $request->get_param( 'subject' );
		$body             = $request->get_param( 'body' );
		$custom_variables = $request->get_param( 'custom_variables' ) ?: [];

		// Ohne Bewerbung: Preview-Werte verwenden.
		if ( empty( $application_id ) ) {
			$preview_values = $this->placeholder_service->getPreviewValues();

			if ( ! empty( $template_id ) ) {
				$template = $this->template_service->find( (int) $template_id );
				if ( ! $template ) {
					return new WP_Error(
						'rest_template_not_found',
						__( 'Template nicht gefunden.', 'recruiting-playbook' ),
						[ 'status' => 404 ]
					);
				}
				$subject = $template['subject'];
				$body    = $template['body_html'];
			}

			$rendered_subject = $this->placeholder_service->renderPreview( $subject ?: '' );
			$rendered_body    = $this->placeholder_service->renderPreview( $body ?: '' );

			return new WP_REST_Response(
				[
					'recipient'      => [
						'email' => $preview_values['email'] ?? 'max.mustermann@example.com',
						'name'  => $preview_values['name'] ?? 'Max Mustermann',
					],
					'subject'        => $rendered_subject,
					'body_html'      => $rendered_body,
					'variables_used' => $this->getUsedVariables( $subject . ' ' . $body, $preview_values ),
					'is_preview'     => true,
				],
				200
			);
		}

		// Mit Bewerbung: Echte Daten verwenden.
		$application = $this->application_service->get( (int) $application_id );
		if ( ! $application ) {
			return new WP_Error(
				'rest_application_not_found',
				__( 'Bewerbung nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Template verwenden oder Custom-Inhalt.
		if ( ! empty( $template_id ) ) {
			$preview = $this->template_service->preview( (int) $template_id );
			if ( ! $preview ) {
				return new WP_Error(
					'rest_template_not_found',
					__( 'Template nicht gefunden.', 'recruiting-playbook' ),
					[ 'status' => 404 ]
				);
			}

			// Mit echten Daten rendern.
			$rendered = $this->template_service->render(
				(int) $template_id,
				$this->buildContext( $application, $custom_variables )
			);

			if ( ! $rendered ) {
				return new WP_Error(
					'rest_template_render_failed',
					__( 'Template konnte nicht gerendert werden.', 'recruiting-playbook' ),
					[ 'status' => 500 ]
				);
			}

			$subject = $rendered['subject'];
			$body    = $rendered['body_html'];
		} else {
			// Custom-Inhalt mit Platzhaltern ersetzen.
			$context = $this->buildContext( $application, $custom_variables );
			$values  = $this->placeholder_service->resolve( $context );

			$subject = $this->placeholder_service->replace( $subject ?: '', $context );
			$body    = $this->placeholder_service->replace( $body ?: '', $context );
		}

		$candidate = $application['candidate'] ?? [];

		return new WP_REST_Response(
			[
				'recipient'      => [
					'email' => $candidate['email'] ?? '',
					'name'  => trim( ( $candidate['first_name'] ?? '' ) . ' ' . ( $candidate['last_name'] ?? '' ) ),
				],
				'subject'        => $subject,
				'body_html'      => $body,
				'variables_used' => $this->getUsedVariables(
					( $request->get_param( 'subject' ) ?: '' ) . ' ' . ( $request->get_param( 'body' ) ?: '' ),
					$this->placeholder_service->resolve( $this->buildContext( $application, $custom_variables ) )
				),
				'is_preview'     => false,
			],
			200
		);
	}

	/**
	 * Geplante E-Mail stornieren
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function cancel_email( $request ) {
		$id = (int) $request->get_param( 'id' );

		$queue_service = new EmailQueueService();
		$result        = $queue_service->cancel( $id );

		if ( ! $result ) {
			return new WP_Error(
				'rest_email_cancel_failed',
				__( 'E-Mail konnte nicht storniert werden. Möglicherweise wurde sie bereits versendet.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Geplante E-Mail wurde storniert.', 'recruiting-playbook' ),
			],
			200
		);
	}

	/**
	 * Queue-Statistiken abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_queue_stats( $request ) {
		$queue_service = new EmailQueueService();
		$stats         = $queue_service->getQueueStats();

		return new WP_REST_Response( $stats, 200 );
	}

	/**
	 * Bulk-E-Mails senden
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function send_bulk_email( $request ) {
		$application_ids   = $request->get_param( 'application_ids' );
		$template_id       = (int) $request->get_param( 'template_id' );
		$custom_variables  = $request->get_param( 'custom_variables' ) ?: [];
		$send_immediately  = (bool) $request->get_param( 'send_immediately' );

		if ( empty( $application_ids ) || ! is_array( $application_ids ) ) {
			return new WP_Error(
				'rest_invalid_application_ids',
				__( 'Bewerbungs-IDs müssen als Array übergeben werden.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// Template validieren.
		$template = $this->template_service->find( $template_id );
		if ( ! $template ) {
			return new WP_Error(
				'rest_template_not_found',
				__( 'Template nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$results = [
			'total'       => count( $application_ids ),
			'queued'      => 0,
			'failed'      => 0,
			'errors'      => [],
			'email_log_ids' => [],
		];

		$use_queue = ! $send_immediately;

		// Signatur-ID aus Request (optional).
		$signature_id = $request->get_param( 'signature_id' );

		foreach ( $application_ids as $application_id ) {
			$application = $this->application_service->get( (int) $application_id );

			if ( ! $application ) {
				$results['failed']++;
				$results['errors'][] = [
					'application_id' => $application_id,
					'error'          => __( 'Bewerbung nicht gefunden', 'recruiting-playbook' ),
				];
				continue;
			}

			$result = $this->email_service->sendWithTemplate(
				$template_id,
				(int) $application_id,
				$custom_variables,
				$use_queue,
				$signature_id
			);

			if ( false === $result ) {
				$results['failed']++;
				$results['errors'][] = [
					'application_id' => $application_id,
					'error'          => __( 'E-Mail konnte nicht gesendet werden', 'recruiting-playbook' ),
				];
			} else {
				$results['queued']++;
				if ( is_int( $result ) ) {
					$results['email_log_ids'][] = $result;
				}
			}
		}

		$status = $results['failed'] === 0 ? 'success' : ( $results['queued'] > 0 ? 'partial' : 'failed' );

		return new WP_REST_Response(
			[
				'success' => $results['failed'] === 0,
				'status'  => $status,
				'message' => sprintf(
					/* translators: 1: number of queued emails, 2: number of failed emails */
					__( '%1$d E-Mails in Warteschlange, %2$d fehlgeschlagen.', 'recruiting-playbook' ),
					$results['queued'],
					$results['failed']
				),
				'results' => $results,
			],
			200
		);
	}

	/**
	 * Berechtigung für E-Mail-Versand prüfen
	 *
	 * Prüft: 1. WordPress Capability, 2. Feature-Flag (Pro erforderlich).
	 * Die Reihenfolge ist wichtig: Capability (Security) vor Feature-Flag (Business-Logic).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function send_email_permissions_check( $request ) {
		// Verwende Helper-Funktion für konsistente Prüfung.
		if ( function_exists( 'rp_check_feature_permission' ) ) {
			return rp_check_feature_permission(
				'email_templates',
				'rp_send_emails',
				'rest_email_send_required',
				__( 'Sie haben keine Berechtigung, E-Mails zu senden.', 'recruiting-playbook' )
			);
		}

		// Fallback falls Helper nicht verfügbar.
		// 1. Capability-Check (WordPress-Core-Security).
		if ( ! current_user_can( 'rp_send_emails' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sie haben keine Berechtigung, E-Mails zu senden.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// 2. Feature-Flag-Check (Business-Logic).
		if ( function_exists( 'rp_can' ) && ! rp_can( 'email_templates' ) ) {
			return new WP_Error(
				'rest_email_send_required',
				__( 'E-Mail-Versand erfordert Pro.', 'recruiting-playbook' ),
				[
					'status'      => 403,
					'upgrade_url' => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
				]
			);
		}

		return true;
	}

	/**
	 * Argumente für E-Mail-Versand
	 *
	 * @return array
	 */
	private function get_send_args(): array {
		return [
			'application_id'   => [
				'description' => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
				'type'        => 'integer',
				'required'    => true,
			],
			'template_id'      => [
				'description' => __( 'Template-ID (optional bei custom)', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
			'subject'          => [
				'description'       => __( 'Betreff (überschreibt Template)', 'recruiting-playbook' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'body'             => [
				'description'       => __( 'Inhalt (überschreibt Template)', 'recruiting-playbook' ),
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			],
			'custom_variables' => [
				'description' => __( 'Zusätzliche Platzhalter-Werte', 'recruiting-playbook' ),
				'type'        => 'object',
			],
			'send_immediately' => [
				'description' => __( 'Sofort senden (nicht in Queue)', 'recruiting-playbook' ),
				'type'        => 'boolean',
				'default'     => false,
			],
			'scheduled_at'     => [
				'description' => __( 'Geplanter Versandzeitpunkt (ISO 8601)', 'recruiting-playbook' ),
				'type'        => 'string',
				'format'      => 'date-time',
			],
			'signature_id'     => [
				'description' => __( 'Signatur-ID (null = automatisch, 0 = keine)', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
		];
	}

	/**
	 * Argumente für E-Mail-Vorschau
	 *
	 * @return array
	 */
	private function get_preview_args(): array {
		return [
			'application_id'   => [
				'description' => __( 'Bewerbungs-ID (optional für Preview-Daten)', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
			'template_id'      => [
				'description' => __( 'Template-ID', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
			'subject'          => [
				'description' => __( 'Betreff', 'recruiting-playbook' ),
				'type'        => 'string',
			],
			'body'             => [
				'description' => __( 'Inhalt', 'recruiting-playbook' ),
				'type'        => 'string',
			],
			'custom_variables' => [
				'description' => __( 'Zusätzliche Platzhalter-Werte', 'recruiting-playbook' ),
				'type'        => 'object',
			],
			'signature_id'     => [
				'description' => __( 'Signatur-ID für Vorschau', 'recruiting-playbook' ),
				'type'        => 'integer',
			],
		];
	}

	/**
	 * Kontext für Platzhalter-Ersetzung aufbauen
	 *
	 * @param array $application   Bewerbungs-Daten.
	 * @param array $custom_values Zusätzliche Werte.
	 * @return array
	 */
	private function buildContext( array $application, array $custom_values = [] ): array {
		$candidate = $application['candidate'] ?? [];
		$job       = [];

		// Job-Daten laden.
		if ( ! empty( $application['job_id'] ) ) {
			$job_post = get_post( $application['job_id'] );
			if ( $job_post ) {
				$job = [
					'title'           => $job_post->post_title,
					'location'        => get_post_meta( $job_post->ID, '_rp_location', true ) ?: '',
					'employment_type' => get_post_meta( $job_post->ID, '_rp_employment_type', true ) ?: '',
					'url'             => get_permalink( $job_post->ID ),
				];
			}
		}

		return [
			'application' => $application,
			'candidate'   => $candidate,
			'job'         => $job,
			'custom'      => $custom_values,
		];
	}

	/**
	 * Verwendete Variablen extrahieren
	 *
	 * @param string $text   Text mit Platzhaltern.
	 * @param array  $values Aufgelöste Werte.
	 * @return array
	 */
	private function getUsedVariables( string $text, array $values ): array {
		$placeholders = $this->placeholder_service->findPlaceholders( $text );
		$result       = [];

		foreach ( $placeholders as $key ) {
			$result[] = [
				'key'   => $key,
				'value' => $values[ $key ] ?? '',
			];
		}

		return $result;
	}
}

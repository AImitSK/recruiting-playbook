<?php
/**
 * REST API Controller für KI-Matching
 *
 * Proxy für die externe KI-Matching API.
 * Sendet Lebensläufe zur Anonymisierung und Analyse.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Api;

defined( 'ABSPATH' ) || exit;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Match Controller für KI-Matching Feature
 */
class MatchController extends WP_REST_Controller {

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
	protected $rest_base = 'match';

	/**
	 * API Base URL
	 *
	 * @var string
	 */
	private const API_BASE_URL = 'https://api.recruiting-playbook.de/v1';

	/**
	 * Routes registrieren
	 */
	public function register_routes(): void {
		// POST /match/analyze - Analyse starten
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/analyze',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'analyze' ],
				'permission_callback' => '__return_true', // Öffentlich für Bewerber
				'args'                => [
					'job_id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'description'       => __( 'ID der Stelle', 'recruiting-playbook' ),
					],
				],
			]
		);

		// GET /match/status/{id} - Status abrufen
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status/(?P<id>[a-f0-9-]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => __( 'Analyse-Job-ID', 'recruiting-playbook' ),
					],
				],
			]
		);
	}

	/**
	 * POST /match/analyze - Analyse starten
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function analyze( WP_REST_Request $request ) {
		// Feature-Check.
		if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
			return new WP_Error(
				'feature_not_available',
				__( 'CV-Matching erfordert das AI-Addon.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// File prüfen.
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new WP_Error(
				'missing_file',
				__( 'Bitte laden Sie eine Datei hoch.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$file   = $files['file'];
		$job_id = $request->get_param( 'job_id' );

		if ( ! $job_id ) {
			return new WP_Error(
				'missing_job_id',
				__( 'Job-ID erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// File-Validierung.
		$validation = $this->validate_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Job-Daten laden.
		$job = get_post( $job_id );
		if ( ! $job || 'job_listing' !== $job->post_type ) {
			return new WP_Error(
				'invalid_job',
				__( 'Stelle nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$job_data = $this->get_job_data( $job );

		// Freemius Auth Headers erstellen.
		$auth_headers = $this->get_freemius_auth_headers();

		if ( is_wp_error( $auth_headers ) ) {
			return $auth_headers;
		}

		// Request an externe API.
		$boundary = wp_generate_password( 24, false );
		$body     = $this->build_multipart_body( $boundary, $file, $job_data );

		$response = wp_remote_post(
			self::API_BASE_URL . '/analysis/upload',
			[
				'timeout' => 30,
				'headers' => array_merge(
					$auth_headers,
					[
						'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
					]
				),
				'body'    => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				__( 'Analyse-Service nicht erreichbar.', 'recruiting-playbook' ),
				[ 'status' => 503 ]
			);
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code >= 400 ) {
			return new WP_Error(
				$response_body['error'] ?? 'api_error',
				$response_body['message'] ?? __( 'Analyse fehlgeschlagen.', 'recruiting-playbook' ),
				[ 'status' => $status_code ]
			);
		}

		return new WP_REST_Response( $response_body, 202 );
	}

	/**
	 * GET /match/status/{id} - Status abrufen
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_status( WP_REST_Request $request ) {
		$analysis_id = $request->get_param( 'id' );

		$auth_headers = $this->get_freemius_auth_headers();

		if ( is_wp_error( $auth_headers ) ) {
			return $auth_headers;
		}

		$response = wp_remote_get(
			self::API_BASE_URL . '/analysis/' . $analysis_id,
			[
				'timeout' => 10,
				'headers' => $auth_headers,
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				__( 'Service nicht erreichbar.', 'recruiting-playbook' ),
				[ 'status' => 503 ]
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return new WP_REST_Response( $body );
	}

	/**
	 * Datei validieren
	 *
	 * @param array $file File-Array aus $_FILES.
	 * @return true|WP_Error
	 */
	private function validate_file( array $file ) {
		// Erlaubte MIME-Types.
		$allowed_types = [
			'application/pdf',
			'image/jpeg',
			'image/png',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		];

		if ( ! in_array( $file['type'], $allowed_types, true ) ) {
			return new WP_Error(
				'invalid_file_type',
				__( 'Bitte laden Sie eine PDF, JPG, PNG oder DOCX Datei hoch.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// Max 10MB.
		$max_size = 10 * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			return new WP_Error(
				'file_too_large',
				__( 'Die Datei ist zu groß. Maximum: 10 MB.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		// Upload-Fehler prüfen.
		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error(
				'upload_error',
				__( 'Fehler beim Hochladen der Datei.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Freemius Auth Headers erstellen
	 *
	 * Die API verwendet Freemius-basierte Authentifizierung:
	 * - X-Freemius-Install-Id: Installation ID
	 * - X-Freemius-Timestamp: ISO Timestamp
	 * - X-Freemius-Signature: SHA256(secret_key + '|' + timestamp)
	 * - X-Site-Url: WordPress Site URL
	 *
	 * @return array|WP_Error Auth-Headers oder Fehler.
	 */
	private function get_freemius_auth_headers() {
		// Freemius SDK prüfen.
		if ( ! function_exists( 'rp_fs' ) ) {
			return new WP_Error(
				'freemius_not_available',
				__( 'Freemius SDK nicht verfügbar.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		$fs = rp_fs();

		// Installation ID und Secret Key holen.
		$site = $fs->get_site();

		if ( ! $site || empty( $site->id ) || empty( $site->secret_key ) ) {
			return new WP_Error(
				'no_freemius_install',
				__( 'Keine gültige Freemius-Installation gefunden.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		$install_id = $site->id;
		$secret_key = $site->secret_key;

		// Timestamp und Signatur erstellen.
		$timestamp = gmdate( 'c' ); // ISO 8601 Format.
		$signature = hash( 'sha256', $secret_key . '|' . $timestamp );

		return [
			'X-Freemius-Install-Id' => (string) $install_id,
			'X-Freemius-Timestamp'  => $timestamp,
			'X-Freemius-Signature'  => $signature,
			'X-Site-Url'            => site_url(),
		];
	}

	/**
	 * Job-Daten für API aufbereiten
	 *
	 * @param \WP_Post $job Job-Post.
	 * @return array Job-Daten.
	 */
	private function get_job_data( \WP_Post $job ): array {
		$requirements = get_post_meta( $job->ID, '_rp_requirements', true ) ?: [];
		$nice_to_have = get_post_meta( $job->ID, '_rp_nice_to_have', true ) ?: [];

		return [
			'title'       => $job->post_title,
			'description' => wp_strip_all_tags( $job->post_content ),
			'requirements' => is_array( $requirements ) ? $requirements : [ $requirements ],
			'niceToHave'  => is_array( $nice_to_have ) ? $nice_to_have : [],
		];
	}

	/**
	 * Multipart Body bauen
	 *
	 * @param string $boundary Boundary-String.
	 * @param array  $file     File-Array.
	 * @param array  $job_data Job-Daten.
	 * @return string Multipart Body.
	 */
	private function build_multipart_body( string $boundary, array $file, array $job_data ): string {
		$body = '';

		// File.
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file['name']}\"\r\n";
		$body .= "Content-Type: {$file['type']}\r\n\r\n";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$body .= file_get_contents( $file['tmp_name'] ) . "\r\n";

		// Job Data.
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"jobData\"\r\n";
		$body .= "Content-Type: application/json\r\n\r\n";
		$body .= wp_json_encode( $job_data ) . "\r\n";

		$body .= "--{$boundary}--\r\n";

		return $body;
	}
}

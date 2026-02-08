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
	private const API_BASE_URL = 'https://api.recruiting-playbook.com/v1';

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

		// POST /match/job-finder - Multi-Job-Matching (Mode B)
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/job-finder',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'analyze_job_finder' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'limit' => [
						'default'           => 5,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) {
							return $value >= 1 && $value <= 10;
						},
						'description'       => __( 'Anzahl der Top-Matches (1-10)', 'recruiting-playbook' ),
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
	 * POST /match/job-finder - Multi-Job-Matching (Mode B)
	 *
	 * Analysiert einen Lebenslauf gegen ALLE aktiven Stellen.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function analyze_job_finder( WP_REST_Request $request ) {
		// Feature-Check.
		if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
			return new WP_Error(
				'feature_not_available',
				__( 'KI-Matching ist nicht verfügbar.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		// File prüfen.
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new WP_Error(
				'no_file',
				__( 'Keine Datei hochgeladen.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		$file = $files['file'];

		// File-Validierung.
		$validation = $this->validate_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// ALLE aktiven Jobs laden.
		$jobs = $this->get_all_active_jobs();
		if ( empty( $jobs ) ) {
			return new WP_Error(
				'no_jobs',
				__( 'Keine aktiven Stellen vorhanden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Limit aus Request.
		$limit = $request->get_param( 'limit' ) ?: 5;

		// An Worker senden.
		$result = $this->send_to_job_finder_api( $file, $jobs, $limit );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Alle aktiven Jobs laden
	 *
	 * @return array Array mit Job-Daten.
	 */
	private function get_all_active_jobs(): array {
		// Cache prüfen (5 Minuten).
		$cache_key = 'rp_active_jobs_for_matching';
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$posts = get_posts(
			[
				'post_type'      => 'job_listing',
				'post_status'    => 'publish',
				'posts_per_page' => 100, // Max 100 Jobs.
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$jobs = [];
		foreach ( $posts as $post ) {
			$requirements = $this->get_job_requirements( $post );
			$nice_to_have = get_post_meta( $post->ID, '_rp_nice_to_have', true ) ?: [];

			$jobs[] = [
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'url'          => get_permalink( $post->ID ),
				'applyUrl'     => get_permalink( $post->ID ) . '#apply',
				'description'  => wp_strip_all_tags( $post->post_content ),
				'requirements' => $requirements,
				'niceToHave'   => is_array( $nice_to_have ) ? $nice_to_have : [],
			];
		}

		// Cache setzen.
		set_transient( $cache_key, $jobs, 5 * MINUTE_IN_SECONDS );

		return $jobs;
	}

	/**
	 * An Job-Finder API senden
	 *
	 * @param array $file  Datei-Array.
	 * @param array $jobs  Array mit Job-Daten.
	 * @param int   $limit Anzahl Top-Matches.
	 * @return array|WP_Error
	 */
	private function send_to_job_finder_api( array $file, array $jobs, int $limit ) {
		$api_url = self::API_BASE_URL . '/analysis/job-finder';

		// Multipart-Body erstellen.
		$boundary = wp_generate_password( 24, false );
		$body     = $this->build_multipart_body_job_finder( $boundary, $file, $jobs, $limit );

		// Auth-Header.
		$auth_headers = $this->get_freemius_auth_headers();
		if ( is_wp_error( $auth_headers ) ) {
			return $auth_headers;
		}

		$headers = array_merge(
			$auth_headers,
			[ 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ]
		);

		$response = wp_remote_post(
			$api_url,
			[
				'timeout' => 30,
				'headers' => $headers,
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
				$response_body['code'] ?? 'api_error',
				$response_body['message'] ?? __( 'API-Fehler', 'recruiting-playbook' ),
				[ 'status' => $status_code ]
			);
		}

		return $response_body;
	}

	/**
	 * Multipart-Body für Job-Finder erstellen
	 *
	 * @param string $boundary Boundary-String.
	 * @param array  $file     Datei-Array.
	 * @param array  $jobs     Jobs-Array.
	 * @param int    $limit    Limit.
	 * @return string Multipart-Body.
	 */
	private function build_multipart_body_job_finder(
		string $boundary,
		array $file,
		array $jobs,
		int $limit
	): string {
		$body = '';

		// Datei.
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$file['name']}\"\r\n";
		$body .= "Content-Type: {$file['type']}\r\n\r\n";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$body .= file_get_contents( $file['tmp_name'] ) . "\r\n";

		// Jobs als JSON.
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"jobs\"\r\n";
		$body .= "Content-Type: application/json\r\n\r\n";
		$body .= wp_json_encode( $jobs ) . "\r\n";

		// Limit.
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"limit\"\r\n\r\n";
		$body .= $limit . "\r\n";

		$body .= "--{$boundary}--\r\n";

		return $body;
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

		$headers = [
			'X-Freemius-Install-Id' => (string) $install_id,
			'X-Freemius-Timestamp'  => $timestamp,
			'X-Freemius-Signature'  => $signature,
			'X-Site-Url'            => site_url(),
		];

		// KI-Addon Installationsdaten mitsenden (für Addon-Lizenz-Prüfung).
		if ( function_exists( 'rpk_fs' ) ) {
			$addon_site = rpk_fs()->get_site();
			if ( $addon_site && ! empty( $addon_site->id ) ) {
				$addon_signature                   = hash( 'sha256', $addon_site->secret_key . '|' . $timestamp );
				$headers['X-Freemius-Addon-Id']    = (string) $addon_site->id;
				$headers['X-Freemius-Addon-Sig']   = $addon_signature;
				$headers['X-Freemius-Addon-Slug']  = 'recruiting-playbook-ki';
			}
		}

		return $headers;
	}

	/**
	 * Job-Daten für API aufbereiten
	 *
	 * @param \WP_Post $job Job-Post.
	 * @return array Job-Daten.
	 */
	private function get_job_data( \WP_Post $job ): array {
		$requirements = $this->get_job_requirements( $job );
		$nice_to_have = get_post_meta( $job->ID, '_rp_nice_to_have', true ) ?: [];

		return [
			'title'        => $job->post_title,
			'description'  => wp_strip_all_tags( $job->post_content ),
			'requirements' => $requirements,
			'niceToHave'   => is_array( $nice_to_have ) ? $nice_to_have : [],
		];
	}

	/**
	 * Requirements für einen Job laden
	 *
	 * Prüft zuerst das Meta-Feld _rp_requirements. Wenn leer,
	 * werden die Anforderungen aus dem post_content extrahiert
	 * (Listeneinträge nach Überschriften wie "Ihr Profil", "Anforderungen" etc.).
	 *
	 * @param \WP_Post $job Job-Post.
	 * @return array Liste der Anforderungen.
	 */
	private function get_job_requirements( \WP_Post $job ): array {
		$requirements = get_post_meta( $job->ID, '_rp_requirements', true );

		if ( ! empty( $requirements ) ) {
			return is_array( $requirements ) ? $requirements : [ $requirements ];
		}

		// Fallback: Requirements aus dem post_content extrahieren.
		return $this->extract_requirements_from_content( $job->post_content );
	}

	/**
	 * Anforderungen aus HTML-Content extrahieren
	 *
	 * Sucht nach Überschriften wie "Ihr Profil", "Anforderungen", "Voraussetzungen"
	 * und extrahiert die darauf folgenden Listeneinträge.
	 *
	 * @param string $content HTML-Content.
	 * @return array Extrahierte Anforderungen.
	 */
	private function extract_requirements_from_content( string $content ): array {
		if ( empty( $content ) ) {
			return [];
		}

		// Überschriften, die auf Anforderungen hindeuten.
		$headings = [
			'Ihr Profil',
			'Anforderungen',
			'Voraussetzungen',
			'Was Sie mitbringen',
			'Das bringen Sie mit',
			'Qualifikationen',
			'Was wir erwarten',
		];

		$pattern = '<h[2-4][^>]*>\s*(?:' . implode( '|', array_map( 'preg_quote', $headings ) ) . ')\s*</h[2-4]>';

		// Abschnitt nach der passenden Überschrift bis zur nächsten Überschrift finden.
		if ( ! preg_match( '/' . $pattern . '\s*(.*?)(?=<h[2-4]|$)/is', $content, $match ) ) {
			return [];
		}

		// Listeneinträge (<li>) extrahieren.
		if ( ! preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', $match[1], $items ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map(
					function ( $item ) {
						return trim( wp_strip_all_tags( $item ) );
					},
					$items[1]
				)
			)
		);
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

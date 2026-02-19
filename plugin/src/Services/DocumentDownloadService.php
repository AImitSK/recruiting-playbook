<?php
/**
 * Sichere Dokument-Downloads mit Token-Validierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service für sichere Dokument-Downloads
 */
class DocumentDownloadService {

	/**
	 * Token-Gültigkeitsdauer in Sekunden (1 Stunde)
	 */
	private const TOKEN_EXPIRY = 3600;

	/**
	 * Max Downloads pro Stunde pro User
	 */
	private const RATE_LIMIT = 100;

	/**
	 * Erlaubte MIME-Types für Downloads (Whitelist)
	 *
	 * Enthält auch Varianten die von verschiedenen Systemen verwendet werden.
	 */
	private const ALLOWED_MIME_TYPES = [
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'image/jpeg',
		'image/jpg',       // Variante
		'image/pjpeg',     // Progressive JPEG (IE)
		'image/png',
		'image/x-png',     // Ältere Variante
	];

	/**
	 * Download-URL generieren
	 *
	 * @param int $document_id Document ID.
	 * @return string
	 */
	public static function generateDownloadUrl( int $document_id ): string {
		$token = self::generateToken( $document_id );

		return admin_url(
			sprintf(
				'admin-ajax.php?action=rp_download_document&id=%d&token=%s',
				$document_id,
				$token
			)
		);
	}

	/**
	 * Token generieren
	 *
	 * @param int $document_id Document ID.
	 * @return string
	 */
	private static function generateToken( int $document_id ): string {
		$user_id = get_current_user_id();
		$expiry  = time() + self::TOKEN_EXPIRY;

		$data = sprintf( '%d:%d:%d', $document_id, $user_id, $expiry );
		$hash = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );

		return base64_encode( $data . ':' . $hash );
	}

	/**
	 * Token validieren
	 *
	 * @param int    $document_id Document ID.
	 * @param string $token       Token string.
	 * @return bool
	 */
	public static function validateToken( int $document_id, string $token ): bool {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$decoded = base64_decode( $token );

		if ( ! $decoded || substr_count( $decoded, ':' ) !== 3 ) {
			self::logDebug( $document_id, 'token_decode_failed', [
				'decoded_empty'  => empty( $decoded ),
				'colon_count'    => $decoded ? substr_count( $decoded, ':' ) : 0,
			] );
			return false;
		}

		list( $token_doc_id, $token_user_id, $expiry, $hash ) = explode( ':', $decoded );

		// Dokument-ID prüfen.
		if ( (int) $token_doc_id !== $document_id ) {
			self::logDebug( $document_id, 'doc_id_mismatch', [
				'token_doc_id'    => (int) $token_doc_id,
				'expected_doc_id' => $document_id,
			] );
			return false;
		}

		// Ablauf prüfen.
		if ( (int) $expiry < time() ) {
			self::logDebug( $document_id, 'token_expired', [
				'expiry_time'  => (int) $expiry,
				'current_time' => time(),
				'expired_ago'  => time() - (int) $expiry,
			] );
			return false;
		}

		// User prüfen.
		if ( (int) $token_user_id !== get_current_user_id() ) {
			self::logDebug( $document_id, 'user_id_mismatch', [
				'token_user_id'   => (int) $token_user_id,
				'current_user_id' => get_current_user_id(),
			] );
			return false;
		}

		// Hash prüfen.
		$data          = sprintf( '%d:%d:%d', $token_doc_id, $token_user_id, $expiry );
		$expected_hash = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );

		if ( ! hash_equals( $expected_hash, $hash ) ) {
			self::logDebug( $document_id, 'hash_mismatch', [
				'token_hash_length'    => strlen( $hash ),
				'expected_hash_length' => strlen( $expected_hash ),
			] );
			return false;
		}

		return true;
	}

	/**
	 * Download ausführen
	 *
	 * @param int $document_id Document ID.
	 */
	public static function serveDownload( int $document_id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_documents';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$document = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $document_id ),
			ARRAY_A
		);

		if ( ! $document ) {
			wp_die( esc_html__( 'Document not found.', 'recruiting-playbook' ), '', [ 'response' => 404 ] );
		}

		// Path Traversal Protection: Prüfen dass Datei im erlaubten Verzeichnis liegt.
		$file_path   = $document['file_path'];
		$wp_upload   = wp_upload_dir();
		$allowed_dir = $wp_upload['basedir'] . '/recruiting-playbook/applications';

		$real_file       = realpath( $file_path );
		$real_allowed    = realpath( $allowed_dir );

		if ( ! $real_file || ! $real_allowed || strpos( $real_file, $real_allowed ) !== 0 ) {
			self::logDebug( $document_id, 'path_validation_failed', [
				'file_path'       => $file_path,
				'allowed_dir'     => $allowed_dir,
				'real_file'       => $real_file ?: 'FALSE',
				'real_allowed'    => $real_allowed ?: 'FALSE',
				'upload_basedir'  => $wp_upload['basedir'],
				'file_exists'     => file_exists( $file_path ),
				'dir_exists'      => is_dir( $allowed_dir ),
			] );
			self::logFailedAccess( $document_id, 'path_traversal_attempt' );
			wp_die( esc_html__( 'Invalid file path.', 'recruiting-playbook' ), '', [ 'response' => 403 ] );
		}

		if ( ! file_exists( $real_file ) ) {
			wp_die( esc_html__( 'File not found.', 'recruiting-playbook' ), '', [ 'response' => 404 ] );
		}

		// Symlink-Check: Verhindert Path Traversal über Symlinks.
		if ( is_link( $file_path ) || is_link( $real_file ) ) {
			self::logFailedAccess( $document_id, 'symlink_detected' );
			wp_die( esc_html__( 'Invalid file path.', 'recruiting-playbook' ), '', [ 'response' => 403 ] );
		}

		// MIME-Type ermitteln: Aus DB oder aus Datei falls leer.
		$file_type = $document['file_type'];
		if ( empty( $file_type ) ) {
			// Fallback: MIME-Type aus Datei ermitteln (für ältere Uploads ohne file_type).
			$file_type = self::detectMimeType( $real_file );
		}

		// MIME-Type Whitelist Validierung (Header Injection Schutz).
		if ( ! in_array( $file_type, self::ALLOWED_MIME_TYPES, true ) ) {
			self::logDebug( $document_id, 'invalid_mime_type', [
				'file_type'          => $file_type,
				'original_name'      => $document['original_name'] ?? '',
				'allowed_mime_types' => self::ALLOWED_MIME_TYPES,
			] );
			self::logFailedAccess( $document_id, 'invalid_mime_type' );
			wp_die( esc_html__( 'Invalid file type.', 'recruiting-playbook' ), '', [ 'response' => 403 ] );
		}

		// Download-Zähler erhöhen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET download_count = download_count + 1 WHERE id = %d",
				$document_id
			)
		);

		// Logging.
		self::logDownload( $document_id, (int) $document['application_id'] );

		// Filename für Header sanitizen (Header Injection Protection).
		$safe_filename = str_replace( array( "\r", "\n", '"', '/' , '\\' ), '', $document['original_name'] );
		$safe_filename = sanitize_file_name( $safe_filename );

		// Headers setzen (file_type bereits gegen Whitelist validiert).
		header( 'Content-Type: ' . $file_type );
		header( 'Content-Disposition: attachment; filename="' . $safe_filename . '"' );
		header( 'Content-Length: ' . $document['file_size'] );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Datei ausgeben.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $real_file );
		exit;
	}

	/**
	 * Download loggen
	 *
	 * @param int $document_id    Document ID.
	 * @param int $application_id Application ID.
	 */
	private static function logDownload( int $document_id, int $application_id ): void {
		global $wpdb;

		$log_table    = $wpdb->prefix . 'rp_activity_log';
		$current_user = wp_get_current_user();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$log_table,
			[
				'object_type' => 'application',
				'object_id'   => $application_id,
				'action'      => 'document_downloaded',
				'user_id'     => $current_user->ID,
				'user_name'   => $current_user->display_name,
				'message'     => sprintf( 'Document #%d downloaded', $document_id ),
				'ip_address'  => self::getClientIp(),
				'created_at'  => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Client-IP ermitteln
	 *
	 * @return string
	 */
	private static function getClientIp(): string {
		$headers = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// Bei X-Forwarded-For erste IP nehmen.
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * AJAX-Handler registrieren
	 */
	public static function registerAjaxHandler(): void {
		add_action( 'wp_ajax_rp_download_document', [ self::class, 'handleAjaxDownload' ] );
	}

	/**
	 * AJAX-Download verarbeiten
	 */
	public static function handleAjaxDownload(): void {
		// Parameter auslesen.
		$document_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$token       = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( ! $document_id || ! $token ) {
			self::logFailedAccess( $document_id, 'missing_params' );
			wp_die( esc_html__( 'Invalid request.', 'recruiting-playbook' ), '', [ 'response' => 400 ] );
		}

		// Token zuerst validieren (enthält User-ID Prüfung).
		if ( ! self::validateToken( $document_id, $token ) ) {
			self::logFailedAccess( $document_id, 'invalid_token' );
			wp_die( esc_html__( 'Download link expired or invalid.', 'recruiting-playbook' ), '', [ 'response' => 403 ] );
		}

		// Dann Berechtigung prüfen (rp_view_applications ODER manage_options als Fallback).
		if ( ! current_user_can( 'rp_view_applications' ) && ! current_user_can( 'manage_options' ) ) {
			self::logFailedAccess( $document_id, 'no_permission' );
			wp_die( esc_html__( 'No permission.', 'recruiting-playbook' ), '', [ 'response' => 403 ] );
		}

		// Rate Limiting: Max Downloads pro Stunde pro User.
		$user_id        = get_current_user_id();
		$transient_key  = 'rp_dl_limit_' . $user_id;
		$download_count = (int) get_transient( $transient_key );

		if ( $download_count >= self::RATE_LIMIT ) {
			self::logFailedAccess( $document_id, 'rate_limit_exceeded' );
			wp_die(
				esc_html__( 'Download limit reached. Please try again later.', 'recruiting-playbook' ),
				'',
				[ 'response' => 429 ]
			);
		}

		set_transient( $transient_key, $download_count + 1, HOUR_IN_SECONDS );

		// Download ausführen.
		self::serveDownload( $document_id );
	}

	/**
	 * Fehlgeschlagene Zugriffe loggen
	 *
	 * @param int    $document_id Document ID.
	 * @param string $reason      Grund für den Fehler.
	 */
	private static function logFailedAccess( int $document_id, string $reason ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'[Recruiting Playbook] Document download failed: ID=%d, Reason=%s, IP=%s, User=%d',
					$document_id,
					$reason,
					self::getClientIp(),
					get_current_user_id()
				)
			);
		}
	}

	/**
	 * MIME-Type aus Datei ermitteln
	 *
	 * Verwendet WordPress' wp_check_filetype_and_ext für sichere Detection.
	 *
	 * @param string $file_path Absoluter Pfad zur Datei.
	 * @return string MIME-Type oder leerer String.
	 */
	private static function detectMimeType( string $file_path ): string {
		// WordPress-Funktion für sichere MIME-Type Erkennung.
		$file_info = wp_check_filetype_and_ext( $file_path, basename( $file_path ) );

		if ( ! empty( $file_info['type'] ) ) {
			return $file_info['type'];
		}

		// Fallback: PHP finfo (nur wenn WordPress-Funktion fehlschlägt).
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			if ( $finfo ) {
				$mime = finfo_file( $finfo, $file_path );
				finfo_close( $finfo );
				if ( $mime ) {
					return $mime;
				}
			}
		}

		return '';
	}

	/**
	 * Debug-Logging für Token-Validierung
	 *
	 * @param int    $document_id Document ID.
	 * @param string $check       Welcher Check fehlgeschlagen ist.
	 * @param array  $details     Zusätzliche Details.
	 */
	private static function logDebug( int $document_id, string $check, array $details = [] ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$message = sprintf(
				'[Recruiting Playbook] Token validation failed: ID=%d, Check=%s, User=%d, IP=%s',
				$document_id,
				$check,
				get_current_user_id(),
				self::getClientIp()
			);

			if ( ! empty( $details ) ) {
				$message .= ', Details=' . wp_json_encode( $details );
			}

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $message );
		}
	}
}

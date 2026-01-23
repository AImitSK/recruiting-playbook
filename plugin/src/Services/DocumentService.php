<?php
/**
 * Document Service - Datei-Upload-Verarbeitung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use RecruitingPlaybook\Constants\DocumentType;
use WP_Error;

/**
 * Service für Dokument-Operationen
 */
class DocumentService {

	/**
	 * Erlaubte MIME-Types
	 *
	 * @var array
	 */
	private const ALLOWED_MIMES = [
		'application/pdf'                                                         => 'pdf',
		'application/msword'                                                      => 'doc',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
		'image/jpeg'                                                              => 'jpg',
		'image/png'                                                               => 'png',
	];

	/**
	 * Maximale Dateigröße in Bytes (10 MB)
	 *
	 * @var int
	 */
	private const MAX_FILE_SIZE = 10 * 1024 * 1024;

	/**
	 * Upload-Verzeichnis
	 *
	 * @var string
	 */
	private string $upload_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initUploadDir();
	}

	/**
	 * Upload-Verzeichnis initialisieren
	 */
	private function initUploadDir(): void {
		$wp_upload = wp_upload_dir();
		$this->upload_dir = $wp_upload['basedir'] . '/recruiting-playbook/applications';
	}

	/**
	 * Upload-Verzeichnis erstellen mit Sicherheitsdateien
	 */
	private function ensureUploadDir(): void {
		// Verzeichnis erstellen falls nicht vorhanden
		if ( ! file_exists( $this->upload_dir ) ) {
			wp_mkdir_p( $this->upload_dir );
		}

		// Robuste .htaccess für Apache (mehrere Syntaxvarianten für Kompatibilität)
		$htaccess = $this->upload_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$htaccess_content = <<<HTACCESS
# Recruiting Playbook - Dokumentenschutz
# Blockiert direkten Zugriff auf alle Dateien in diesem Verzeichnis

# Apache 2.4+
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>

# Apache 2.2 (Fallback)
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>

# Zusätzlicher Schutz: Verhindert PHP-Ausführung
<FilesMatch "\.ph(p[3-7]?|tml|ar)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</FilesMatch>

# Verhindert Directory Listing
Options -Indexes
HTACCESS;
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, $htaccess_content );
		}

		// Index.php erstellen (zusätzlicher Schutz gegen Directory Listing)
		$index = $this->upload_dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}

		// Nginx-Hinweis erstellen
		$nginx_readme = $this->upload_dir . '/NGINX_SECURITY.txt';
		if ( ! file_exists( $nginx_readme ) ) {
			$nginx_content = <<<NGINX
# WICHTIG für Nginx-Server:
# Die .htaccess wird von Nginx ignoriert!
#
# Fügen Sie folgende Regel in Ihre Nginx-Konfiguration ein:
#
# location ~* /wp-content/uploads/recruiting-playbook/ {
#     deny all;
#     return 403;
# }
#
# Alternativ in der Server-Block:
#
# location ~ ^/wp-content/uploads/recruiting-playbook/.*$ {
#     internal;
# }
NGINX;
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $nginx_readme, $nginx_content );
		}
	}

	/**
	 * Prüft ob der Dokumentenschutz funktioniert
	 *
	 * @return array Status und Nachricht
	 */
	public static function checkProtection(): array {
		$wp_upload = wp_upload_dir();
		$upload_dir = $wp_upload['basedir'] . '/recruiting-playbook/applications';
		$upload_url = $wp_upload['baseurl'] . '/recruiting-playbook/applications';

		$result = [
			'protected'   => true,
			'htaccess'    => false,
			'nginx_note'  => false,
			'message'     => '',
			'server_type' => '',
		];

		// Server-Typ erkennen
		$server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';

		if ( stripos( $server_software, 'nginx' ) !== false ) {
			$result['server_type'] = 'nginx';
		} elseif ( stripos( $server_software, 'apache' ) !== false ) {
			$result['server_type'] = 'apache';
		} else {
			$result['server_type'] = 'unknown';
		}

		// .htaccess prüfen
		$htaccess_file = $upload_dir . '/.htaccess';
		if ( file_exists( $htaccess_file ) ) {
			$result['htaccess'] = true;
		}

		// Nginx-Hinweis prüfen
		$nginx_file = $upload_dir . '/NGINX_SECURITY.txt';
		if ( file_exists( $nginx_file ) ) {
			$result['nginx_note'] = true;
		}

		// Warnung für Nginx-Server
		if ( 'nginx' === $result['server_type'] && ! $result['nginx_note'] ) {
			$result['protected'] = false;
			$result['message'] = __( 'Nginx-Server erkannt: Bitte konfigurieren Sie den Dokumentenschutz manuell (siehe NGINX_SECURITY.txt).', 'recruiting-playbook' );
		} elseif ( 'nginx' === $result['server_type'] ) {
			$result['message'] = __( 'Nginx-Server erkannt: Bitte stellen Sie sicher, dass die Nginx-Konfiguration den Dokumentenschutz enthält.', 'recruiting-playbook' );
		} elseif ( 'apache' === $result['server_type'] && $result['htaccess'] ) {
			$result['message'] = __( 'Apache-Server mit .htaccess-Schutz erkannt.', 'recruiting-playbook' );
		} else {
			$result['message'] = __( 'Dokumentenschutz eingerichtet. Bitte testen Sie den direkten Zugriff auf Dokumente.', 'recruiting-playbook' );
		}

		return $result;
	}

	/**
	 * Uploads verarbeiten
	 *
	 * @param int   $application_id Application ID.
	 * @param array $files          $_FILES array.
	 * @return array|WP_Error Array mit Document IDs oder Fehler.
	 */
	public function processUploads( int $application_id, array $files ): array|WP_Error {
		// Upload-Verzeichnis sicherstellen.
		$this->ensureUploadDir();

		$document_ids = [];
		$errors = [];

		// Verschiedene Datei-Felder verarbeiten
		$file_fields = [
			'resume'       => DocumentType::RESUME,
			'cover_letter' => DocumentType::COVER_LETTER,
			'documents'    => DocumentType::OTHER,
		];

		foreach ( $file_fields as $field => $type ) {
			if ( ! isset( $files[ $field ] ) ) {
				continue;
			}

			$file_data = $files[ $field ];

			// Mehrere Dateien in einem Feld
			if ( is_array( $file_data['name'] ) ) {
				foreach ( $file_data['name'] as $i => $name ) {
					if ( empty( $name ) || UPLOAD_ERR_NO_FILE === $file_data['error'][ $i ] ) {
						continue;
					}

					$single_file = [
						'name'     => $file_data['name'][ $i ],
						'type'     => $file_data['type'][ $i ],
						'tmp_name' => $file_data['tmp_name'][ $i ],
						'error'    => $file_data['error'][ $i ],
						'size'     => $file_data['size'][ $i ],
					];

					$result = $this->processFile( $application_id, $single_file, $type );
					if ( is_wp_error( $result ) ) {
						$errors[] = $result->get_error_message();
					} else {
						$document_ids[] = $result;
					}
				}
			} else {
				// Einzelne Datei
				if ( empty( $file_data['name'] ) || UPLOAD_ERR_NO_FILE === $file_data['error'] ) {
					continue;
				}

				$result = $this->processFile( $application_id, $file_data, $type );
				if ( is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
				} else {
					$document_ids[] = $result;
				}
			}
		}

		if ( ! empty( $errors ) && empty( $document_ids ) ) {
			return new WP_Error(
				'upload_failed',
				implode( ', ', $errors ),
				[ 'status' => 400 ]
			);
		}

		return $document_ids;
	}

	/**
	 * Einzelne Datei verarbeiten
	 *
	 * @param int    $application_id Application ID.
	 * @param array  $file           Datei-Array.
	 * @param string $type           Dokument-Typ.
	 * @return int|WP_Error Document ID oder Fehler.
	 */
	private function processFile( int $application_id, array $file, string $type ): int|WP_Error {
		// Upload-Fehler prüfen
		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error(
				'upload_error',
				$this->getUploadErrorMessage( $file['error'] )
			);
		}

		// Dateigröße prüfen
		if ( $file['size'] > self::MAX_FILE_SIZE ) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					/* translators: %s: max file size */
					__( 'Die Datei ist zu groß. Maximale Größe: %s', 'recruiting-playbook' ),
					size_format( self::MAX_FILE_SIZE )
				)
			);
		}

		// MIME-Type und Extension validieren.
		$mime_type = $this->validateMimeType( $file['tmp_name'], $file['name'] );
		if ( is_wp_error( $mime_type ) ) {
			return $mime_type;
		}

		// Dateiname sichern
		$extension = self::ALLOWED_MIMES[ $mime_type ] ?? 'dat';
		$safe_filename = $this->generateSafeFilename( $file['name'], $extension );

		// Application ID validieren.
		$app_id_safe = absint( $application_id );
		if ( $app_id_safe <= 0 ) {
			return new WP_Error(
				'invalid_app_id',
				__( 'Ungültige Bewerbungs-ID.', 'recruiting-playbook' )
			);
		}

		// Upload-Verzeichnis prüfen BEVOR Unterverzeichnis erstellt wird.
		$real_upload_dir = realpath( $this->upload_dir );
		if ( ! $real_upload_dir ) {
			return new WP_Error(
				'invalid_upload_dir',
				__( 'Upload-Verzeichnis nicht gefunden.', 'recruiting-playbook' )
			);
		}

		// Zielverzeichnis für diese Bewerbung.
		$app_dir = trailingslashit( $this->upload_dir ) . $app_id_safe;
		if ( ! file_exists( $app_dir ) ) {
			wp_mkdir_p( $app_dir );
		}

		// Path Traversal Schutz: Sicherstellen, dass Ziel innerhalb des Upload-Verzeichnisses liegt.
		// Symlink-Check: Verhindert Path Traversal über Symlinks.
		if ( is_link( $app_dir ) ) {
			return new WP_Error(
				'symlink_detected',
				__( 'Symlinks sind nicht erlaubt.', 'recruiting-playbook' )
			);
		}

		$real_app_dir = realpath( $app_dir );
		if ( ! $real_app_dir || strpos( $real_app_dir, $real_upload_dir ) !== 0 ) {
			return new WP_Error(
				'invalid_path',
				__( 'Ungültiger Zielpfad.', 'recruiting-playbook' )
			);
		}

		$destination = trailingslashit( $real_app_dir ) . $safe_filename;

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! @move_uploaded_file( $file['tmp_name'], $destination ) ) {
			return new WP_Error(
				'move_failed',
				__( 'Datei konnte nicht gespeichert werden.', 'recruiting-playbook' )
			);
		}

		// Dateiberechtigungen setzen (mit Error-Handling).
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- chmod kann auf manchen Systemen fehlschlagen
		if ( ! @chmod( $destination, 0640 ) ) {
			// Fehler loggen, aber Upload nicht abbrechen - Datei wurde bereits gespeichert.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( '[Recruiting Playbook] chmod failed for: %s', $destination ) );
			}
		}

		// In Datenbank speichern
		return $this->saveDocument( $application_id, [
			'filename'      => $safe_filename,
			'original_name' => sanitize_file_name( $file['name'] ),
			'mime_type'     => $mime_type,
			'size'          => $file['size'],
			'type'          => $type,
			'path'          => $destination,
		] );
	}

	/**
	 * MIME-Type validieren
	 *
	 * @param string $file_path     Dateipfad.
	 * @param string $original_name Ursprünglicher Dateiname für Extension-Check.
	 * @return string|WP_Error MIME-Type oder Fehler.
	 */
	private function validateMimeType( string $file_path, string $original_name = '' ): string|WP_Error {
		// PHP-Fileinfo verwenden
		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $file_path );
		finfo_close( $finfo );

		if ( ! array_key_exists( $mime_type, self::ALLOWED_MIMES ) ) {
			return new WP_Error(
				'invalid_file_type',
				sprintf(
					/* translators: %s: mime type */
					__( 'Der Dateityp "%s" ist nicht erlaubt. Erlaubt sind: PDF, DOC, DOCX, JPG, PNG', 'recruiting-playbook' ),
					$mime_type
				)
			);
		}

		// Optional: Extension-Mismatch-Check für zusätzliche Sicherheit.
		if ( ! empty( $original_name ) ) {
			$extension          = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );
			$expected_extension = self::ALLOWED_MIMES[ $mime_type ];

			// Erlaube jpg/jpeg als Varianten.
			$extension_variants = [
				'jpg'  => [ 'jpg', 'jpeg' ],
				'jpeg' => [ 'jpg', 'jpeg' ],
			];

			$allowed_extensions = $extension_variants[ $expected_extension ] ?? [ $expected_extension ];

			if ( ! in_array( $extension, $allowed_extensions, true ) ) {
				return new WP_Error(
					'extension_mismatch',
					__( 'Die Dateiendung stimmt nicht mit dem Dateiinhalt überein.', 'recruiting-playbook' )
				);
			}
		}

		return $mime_type;
	}

	/**
	 * Sicheren Dateinamen generieren
	 *
	 * @param string $original_name Ursprünglicher Name.
	 * @param string $extension     Dateiendung.
	 * @return string
	 */
	private function generateSafeFilename( string $original_name, string $extension ): string {
		// Hash aus Original-Namen und Zeit
		$hash = substr( md5( $original_name . microtime() ), 0, 12 );

		// Basis-Namen extrahieren und bereinigen
		$basename = pathinfo( $original_name, PATHINFO_FILENAME );
		$basename = sanitize_file_name( $basename );

		// Path Traversal Zeichen explizit entfernen (zusätzliche Sicherheit).
		$basename = str_replace( array( '..', '/', '\\' ), '', $basename );
		$basename = substr( $basename, 0, 50 ); // Max 50 Zeichen

		return sprintf( '%s_%s.%s', $basename, $hash, $extension );
	}

	/**
	 * Dokument in DB speichern
	 *
	 * @param int   $application_id Application ID.
	 * @param array $data           Dokument-Daten.
	 * @return int|WP_Error Document ID.
	 */
	private function saveDocument( int $application_id, array $data ): int|WP_Error {
		global $wpdb;

		// Candidate ID aus der Bewerbung holen
		$applications_table = $wpdb->prefix . 'rp_applications';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$candidate_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT candidate_id FROM {$applications_table} WHERE id = %d",
				$application_id
			)
		);

		$table = $wpdb->prefix . 'rp_documents';

		// Spaltennamen müssen mit Schema übereinstimmen
		$inserted = $wpdb->insert(
			$table,
			[
				'application_id' => $application_id,
				'candidate_id'   => (int) $candidate_id,
				'file_name'      => $data['filename'],
				'original_name'  => $data['original_name'],
				'file_path'      => $data['path'],
				'file_type'      => $data['mime_type'],
				'file_size'      => $data['size'],
				'file_hash'      => md5_file( $data['path'] ) ?: '',
				'document_type'  => $data['type'],
				'created_at'     => current_time( 'mysql' ),
			]
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'db_error',
				__( 'Dokument konnte nicht in der Datenbank gespeichert werden.', 'recruiting-playbook' )
			);
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Dokumente einer Bewerbung abrufen
	 *
	 * @param int $application_id Application ID.
	 * @return array
	 */
	public function getByApplication( int $application_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_documents';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, document_type as type, file_name as filename, original_name, file_type as mime_type, file_size as size, created_at
				FROM {$table}
				WHERE application_id = %d AND is_deleted = 0
				ORDER BY created_at ASC",
				$application_id
			),
			ARRAY_A
		);

		return $results ?: [];
	}

	/**
	 * Dokument abrufen
	 *
	 * @param int $document_id Document ID.
	 * @return array|null
	 */
	public function get( int $document_id ): ?array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_documents';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $document_id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		// Alias für Abwärtskompatibilität
		$row['path'] = $row['file_path'] ?? '';

		return $row;
	}

	/**
	 * Dokument löschen
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function delete( int $document_id ): bool {
		global $wpdb;

		$document = $this->get( $document_id );
		if ( ! $document ) {
			return false;
		}

		// Datei löschen
		$file_path = $document['file_path'] ?? $document['path'] ?? '';
		if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}

		// DB-Eintrag löschen
		$table = $wpdb->prefix . 'rp_documents';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->delete( $table, [ 'id' => $document_id ], [ '%d' ] );
	}

	/**
	 * Upload-Fehlermeldung abrufen
	 *
	 * @param int $error_code PHP Upload-Fehlercode.
	 * @return string
	 */
	private function getUploadErrorMessage( int $error_code ): string {
		$messages = [
			UPLOAD_ERR_INI_SIZE   => __( 'Die Datei überschreitet die maximal erlaubte Größe.', 'recruiting-playbook' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'Die Datei überschreitet die maximal erlaubte Größe.', 'recruiting-playbook' ),
			UPLOAD_ERR_PARTIAL    => __( 'Die Datei wurde nur teilweise hochgeladen.', 'recruiting-playbook' ),
			UPLOAD_ERR_NO_FILE    => __( 'Es wurde keine Datei hochgeladen.', 'recruiting-playbook' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'Temporäres Verzeichnis fehlt.', 'recruiting-playbook' ),
			UPLOAD_ERR_CANT_WRITE => __( 'Datei konnte nicht geschrieben werden.', 'recruiting-playbook' ),
			UPLOAD_ERR_EXTENSION  => __( 'Upload durch PHP-Erweiterung gestoppt.', 'recruiting-playbook' ),
		];

		return $messages[ $error_code ] ?? __( 'Unbekannter Upload-Fehler.', 'recruiting-playbook' );
	}
}

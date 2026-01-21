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
		if ( file_exists( $this->upload_dir ) ) {
			return;
		}

		// Verzeichnis erstellen
		wp_mkdir_p( $this->upload_dir );

		// .htaccess für Zugriffskontrolle erstellen
		$htaccess = $this->upload_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, "Deny from all\n" );
		}

		// Index.php erstellen
		$index = $this->upload_dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
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

		// MIME-Type validieren
		$mime_type = $this->validateMimeType( $file['tmp_name'] );
		if ( is_wp_error( $mime_type ) ) {
			return $mime_type;
		}

		// Dateiname sichern
		$extension = self::ALLOWED_MIMES[ $mime_type ] ?? 'dat';
		$safe_filename = $this->generateSafeFilename( $file['name'], $extension );

		// Zielverzeichnis für diese Bewerbung
		$app_dir = $this->upload_dir . '/' . $application_id;
		if ( ! file_exists( $app_dir ) ) {
			wp_mkdir_p( $app_dir );
		}

		// Datei verschieben
		$destination = $app_dir . '/' . $safe_filename;
		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			return new WP_Error(
				'move_failed',
				__( 'Datei konnte nicht gespeichert werden.', 'recruiting-playbook' )
			);
		}

		// Dateiberechtigungen setzen
		chmod( $destination, 0640 );

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
	 * @param string $file_path Dateipfad.
	 * @return string|WP_Error MIME-Type oder Fehler.
	 */
	private function validateMimeType( string $file_path ): string|WP_Error {
		// PHP-Fileinfo verwenden
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
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

		$table = $wpdb->prefix . 'rp_documents';

		$inserted = $wpdb->insert(
			$table,
			[
				'application_id' => $application_id,
				'type'           => $data['type'],
				'filename'       => $data['filename'],
				'original_name'  => $data['original_name'],
				'mime_type'      => $data['mime_type'],
				'size'           => $data['size'],
				'path'           => $data['path'],
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
				"SELECT id, type, filename, original_name, mime_type, size, created_at
				FROM {$table}
				WHERE application_id = %d
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
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $document_id ),
			ARRAY_A
		);
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
		if ( ! empty( $document['path'] ) && file_exists( $document['path'] ) ) {
			wp_delete_file( $document['path'] );
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

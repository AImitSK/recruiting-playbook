<?php
/**
 * Custom Field File Service
 *
 * Verarbeitet Datei-Uploads für Custom Fields.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Service für Custom Field Datei-Uploads
 */
class CustomFieldFileService {

	/**
	 * Standard MIME-Types
	 *
	 * @var array<string, string>
	 */
	private const DEFAULT_ALLOWED_TYPES = [
		'.pdf'  => 'application/pdf',
		'.doc'  => 'application/msword',
		'.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'.jpg'  => 'image/jpeg',
		'.jpeg' => 'image/jpeg',
		'.png'  => 'image/png',
	];

	/**
	 * Upload-Verzeichnis Basis
	 *
	 * @var string
	 */
	private string $upload_base;

	/**
	 * Document Service
	 *
	 * @var DocumentService
	 */
	private DocumentService $document_service;

	/**
	 * Konstruktor
	 *
	 * @param DocumentService|null $document_service Optional: Document-Service.
	 */
	public function __construct( ?DocumentService $document_service = null ) {
		$this->document_service = $document_service ?? new DocumentService();
		$this->initUploadBase();
	}

	/**
	 * Upload-Verzeichnis initialisieren
	 */
	private function initUploadBase(): void {
		$wp_upload         = wp_upload_dir();
		$this->upload_base = $wp_upload['basedir'] . '/recruiting-playbook/custom-fields';
	}

	/**
	 * Datei-Uploads für Custom Fields verarbeiten
	 *
	 * @param int               $application_id Bewerbungs-ID.
	 * @param FieldDefinition[] $fields         Felddefinitionen.
	 * @param array             $files          $_FILES Array.
	 * @return array<string, int[]>|WP_Error Mapping field_key => Document-IDs.
	 */
	public function processCustomFieldUploads( int $application_id, array $fields, array $files ): array|WP_Error {
		$this->ensureUploadDir( $application_id );

		$results = [];
		$errors  = [];

		foreach ( $fields as $field ) {
			if ( 'file' !== $field->getType() ) {
				continue;
			}

			$field_key = $field->getFieldKey();

			// Suche nach Dateien für dieses Feld.
			$field_files = $this->extractFieldFiles( $field_key, $files );

			if ( empty( $field_files ) ) {
				$results[ $field_key ] = [];
				continue;
			}

			// Validieren.
			$validation = $this->validateFieldFiles( $field, $field_files );
			if ( is_wp_error( $validation ) ) {
				$errors[ $field_key ] = $validation->get_error_message();
				continue;
			}

			// Hochladen.
			$uploaded = $this->uploadFieldFiles( $application_id, $field, $field_files );
			if ( is_wp_error( $uploaded ) ) {
				$errors[ $field_key ] = $uploaded->get_error_message();
				continue;
			}

			$results[ $field_key ] = $uploaded;
		}

		if ( ! empty( $errors ) && empty( array_filter( $results ) ) ) {
			return new WP_Error(
				'upload_failed',
				implode( '; ', $errors ),
				[ 'field_errors' => $errors ]
			);
		}

		return $results;
	}

	/**
	 * Dateien für ein bestimmtes Feld extrahieren
	 *
	 * @param string $field_key Feld-Schlüssel.
	 * @param array  $files     $_FILES Array.
	 * @return array[] Normalisierte Datei-Arrays.
	 */
	private function extractFieldFiles( string $field_key, array $files ): array {
		$result = [];

		// Direkte Zuordnung: field_key.
		if ( isset( $files[ $field_key ] ) ) {
			$result = array_merge( $result, $this->normalizeFiles( $files[ $field_key ] ) );
		}

		// Array-Zuordnung: field_key[].
		$array_key = $field_key . '[]';
		if ( isset( $files[ $array_key ] ) ) {
			$result = array_merge( $result, $this->normalizeFiles( $files[ $array_key ] ) );
		}

		return $result;
	}

	/**
	 * $_FILES Array normalisieren
	 *
	 * Konvertiert unterschiedliche $_FILES Formate in ein einheitliches Array.
	 *
	 * @param array $files Datei-Array aus $_FILES.
	 * @return array[] Normalisierte Datei-Arrays.
	 */
	private function normalizeFiles( array $files ): array {
		$result = [];

		// Multi-Upload Format.
		if ( isset( $files['name'] ) && is_array( $files['name'] ) ) {
			$count = count( $files['name'] );
			for ( $i = 0; $i < $count; $i++ ) {
				if ( empty( $files['name'][ $i ] ) || UPLOAD_ERR_NO_FILE === $files['error'][ $i ] ) {
					continue;
				}
				$result[] = [
					'name'     => $files['name'][ $i ],
					'type'     => $files['type'][ $i ],
					'tmp_name' => $files['tmp_name'][ $i ],
					'error'    => $files['error'][ $i ],
					'size'     => $files['size'][ $i ],
				];
			}
		} elseif ( isset( $files['name'] ) && ! empty( $files['name'] ) && UPLOAD_ERR_NO_FILE !== $files['error'] ) {
			// Single Upload Format.
			$result[] = $files;
		}

		return $result;
	}

	/**
	 * Dateien für ein Feld validieren
	 *
	 * @param FieldDefinition $field Felddefinition.
	 * @param array[]         $files Normalisierte Datei-Arrays.
	 * @return true|WP_Error True bei Erfolg.
	 */
	private function validateFieldFiles( FieldDefinition $field, array $files ): true|WP_Error {
		$settings   = $field->getSettings() ?? [];
		$validation = $field->getValidation() ?? [];

		$multiple       = ! empty( $settings['multiple'] ) || ( $settings['max_files'] ?? 1 ) > 1;
		$max_files      = (int) ( $validation['max_files'] ?? $settings['max_files'] ?? 5 );
		$min_files      = (int) ( $validation['min_files'] ?? 0 );
		$max_size_mb    = (int) ( $validation['max_file_size'] ?? 10 );
		$max_size_bytes = $max_size_mb * 1024 * 1024;
		$allowed_types  = $this->parseAllowedTypes( $validation['allowed_types'] ?? '.pdf,.doc,.docx,.jpg,.jpeg,.png' );

		// Anzahl prüfen.
		if ( ! $multiple && count( $files ) > 1 ) {
			return new WP_Error(
				'too_many_files',
				sprintf(
					/* translators: %s: Field label */
					__( 'Für %s ist nur eine Datei erlaubt.', 'recruiting-playbook' ),
					$field->getLabel()
				)
			);
		}

		if ( count( $files ) > $max_files ) {
			return new WP_Error(
				'max_files_exceeded',
				sprintf(
					/* translators: 1: Field label, 2: Max count */
					__( 'Für %1$s sind maximal %2$d Dateien erlaubt.', 'recruiting-playbook' ),
					$field->getLabel(),
					$max_files
				)
			);
		}

		if ( $field->isRequired() && count( $files ) < max( 1, $min_files ) ) {
			return new WP_Error(
				'min_files_required',
				sprintf(
					/* translators: %s: Field label */
					__( '%s ist ein Pflichtfeld.', 'recruiting-playbook' ),
					$field->getLabel()
				)
			);
		}

		// Jede Datei prüfen.
		foreach ( $files as $file ) {
			// Upload-Fehler.
			if ( UPLOAD_ERR_OK !== $file['error'] ) {
				return new WP_Error(
					'upload_error',
					sprintf(
						/* translators: %s: File name */
						__( 'Fehler beim Upload von %s.', 'recruiting-playbook' ),
						$file['name']
					)
				);
			}

			// Dateigröße.
			if ( $file['size'] > $max_size_bytes ) {
				return new WP_Error(
					'file_too_large',
					sprintf(
						/* translators: 1: File name, 2: Max size */
						__( 'Die Datei %1$s ist zu groß. Maximum: %2$d MB.', 'recruiting-playbook' ),
						$file['name'],
						$max_size_mb
					)
				);
			}

			// MIME-Typ / Extension.
			if ( ! $this->isAllowedFile( $file, $allowed_types ) ) {
				return new WP_Error(
					'invalid_file_type',
					sprintf(
						/* translators: 1: File name, 2: Allowed types */
						__( 'Der Dateityp von %1$s ist nicht erlaubt. Erlaubt: %2$s.', 'recruiting-playbook' ),
						$file['name'],
						implode( ', ', array_keys( $allowed_types ) )
					)
				);
			}
		}

		return true;
	}

	/**
	 * Erlaubte Typen aus Feld-Einstellungen parsen
	 *
	 * @param string $types Komma-getrennte Liste von Extensions.
	 * @return array<string, string> Extension => MIME-Type Mapping.
	 */
	private function parseAllowedTypes( string $types ): array {
		$result     = [];
		$extensions = array_map( 'trim', explode( ',', strtolower( $types ) ) );

		foreach ( $extensions as $ext ) {
			// Punkt hinzufügen falls nicht vorhanden.
			if ( 0 !== strpos( $ext, '.' ) ) {
				$ext = '.' . $ext;
			}

			if ( isset( self::DEFAULT_ALLOWED_TYPES[ $ext ] ) ) {
				$result[ $ext ] = self::DEFAULT_ALLOWED_TYPES[ $ext ];
			}
		}

		return ! empty( $result ) ? $result : self::DEFAULT_ALLOWED_TYPES;
	}

	/**
	 * Prüfen ob Datei erlaubt ist
	 *
	 * @param array                  $file          Datei-Array.
	 * @param array<string, string>  $allowed_types Erlaubte Typen.
	 * @return bool
	 */
	private function isAllowedFile( array $file, array $allowed_types ): bool {
		// Extension prüfen.
		$extension = '.' . strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! isset( $allowed_types[ $extension ] ) ) {
			// Prüfe Varianten (z.B. .jpg und .jpeg).
			$found = false;
			foreach ( array_keys( $allowed_types ) as $allowed_ext ) {
				if ( '.jpeg' === $allowed_ext && '.jpg' === $extension ) {
					$found = true;
					break;
				}
				if ( '.jpg' === $allowed_ext && '.jpeg' === $extension ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				return false;
			}
		}

		// MIME-Typ validieren (tatsächlicher Inhalt).
		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );

		return in_array( $mime_type, $allowed_types, true );
	}

	/**
	 * Dateien für ein Feld hochladen
	 *
	 * @param int             $application_id Bewerbungs-ID.
	 * @param FieldDefinition $field          Felddefinition.
	 * @param array[]         $files          Normalisierte Datei-Arrays.
	 * @return int[]|WP_Error Array von Document-IDs.
	 */
	private function uploadFieldFiles( int $application_id, FieldDefinition $field, array $files ): array|WP_Error {
		$document_ids = [];

		foreach ( $files as $file ) {
			$doc_id = $this->uploadSingleFile( $application_id, $field, $file );

			if ( is_wp_error( $doc_id ) ) {
				return $doc_id;
			}

			$document_ids[] = $doc_id;
		}

		return $document_ids;
	}

	/**
	 * Einzelne Datei hochladen
	 *
	 * @param int             $application_id Bewerbungs-ID.
	 * @param FieldDefinition $field          Felddefinition.
	 * @param array           $file           Datei-Array.
	 * @return int|WP_Error Document-ID.
	 */
	private function uploadSingleFile( int $application_id, FieldDefinition $field, array $file ): int|WP_Error {
		global $wpdb;

		// Sicheren Dateinamen generieren.
		$extension     = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$safe_filename = $this->generateSafeFilename( $file['name'], $extension );

		// Zielverzeichnis.
		$upload_dir = $this->upload_base . '/' . $application_id;
		$this->ensureUploadDir( $application_id );

		$destination = $upload_dir . '/' . $safe_filename;

		// Datei verschieben.
		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			return new WP_Error(
				'move_failed',
				sprintf(
					/* translators: %s: File name */
					__( 'Die Datei %s konnte nicht gespeichert werden.', 'recruiting-playbook' ),
					$file['name']
				)
			);
		}

		// Berechtigungen einschränken.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@chmod( $destination, 0640 );

		// MIME-Typ ermitteln.
		$finfo     = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $destination );
		finfo_close( $finfo );

		// In DB speichern.
		$table = $wpdb->prefix . 'rp_documents';

		// Candidate ID aus Application holen.
		$app_table    = $wpdb->prefix . 'rp_applications';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$candidate_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT candidate_id FROM {$app_table} WHERE id = %d",
				$application_id
			)
		);

		$inserted = $wpdb->insert(
			$table,
			[
				'application_id' => $application_id,
				'candidate_id'   => (int) $candidate_id,
				'file_name'      => $safe_filename,
				'original_name'  => sanitize_file_name( $file['name'] ),
				'file_path'      => $destination,
				'file_type'      => $mime_type,
				'file_size'      => $file['size'],
				'file_hash'      => md5_file( $destination ) ?: '',
				'document_type'  => 'custom_field',
				'metadata'       => wp_json_encode( [
					'field_key'   => $field->getFieldKey(),
					'field_label' => $field->getLabel(),
				] ),
				'created_at'     => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $inserted ) {
			// Datei löschen bei DB-Fehler.
			wp_delete_file( $destination );
			return new WP_Error(
				'db_error',
				__( 'Dokument konnte nicht in der Datenbank gespeichert werden.', 'recruiting-playbook' )
			);
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Sicheren Dateinamen generieren
	 *
	 * @param string $original_name Ursprünglicher Name.
	 * @param string $extension     Dateiendung.
	 * @return string
	 */
	private function generateSafeFilename( string $original_name, string $extension ): string {
		$hash     = substr( md5( $original_name . microtime() . wp_rand() ), 0, 12 );
		$basename = pathinfo( $original_name, PATHINFO_FILENAME );
		$basename = sanitize_file_name( $basename );
		$basename = str_replace( [ '..', '/', '\\' ], '', $basename );
		$basename = substr( $basename, 0, 50 );

		return sprintf( '%s_%s.%s', $basename, $hash, $extension );
	}

	/**
	 * Upload-Verzeichnis erstellen
	 *
	 * @param int $application_id Bewerbungs-ID.
	 */
	private function ensureUploadDir( int $application_id ): void {
		$dir = $this->upload_base . '/' . $application_id;

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// .htaccess erstellen.
		$htaccess = $this->upload_base . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$content = "# Recruiting Playbook - Custom Fields Dokumentenschutz\n\n";
			$content .= "<IfModule mod_authz_core.c>\n";
			$content .= "    Require all denied\n";
			$content .= "</IfModule>\n\n";
			$content .= "<IfModule !mod_authz_core.c>\n";
			$content .= "    Order deny,allow\n";
			$content .= "    Deny from all\n";
			$content .= "</IfModule>\n\n";
			$content .= "Options -Indexes\n";
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, $content );
		}

		// Index.php erstellen.
		$index = $this->upload_base . '/index.php';
		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Dokumente für Custom Field Werte abrufen
	 *
	 * @param array $document_ids Array von Document-IDs.
	 * @return array Dokument-Daten.
	 */
	public function getDocuments( array $document_ids ): array {
		if ( empty( $document_ids ) ) {
			return [];
		}

		global $wpdb;

		$table        = $wpdb->prefix . 'rp_documents';
		$ids          = array_map( 'absint', $document_ids );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders sind %d
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, document_type, file_name, original_name, file_type, file_size, created_at
				FROM {$table}
				WHERE id IN ({$placeholders}) AND is_deleted = 0",
				...$ids
			),
			ARRAY_A
		);

		if ( ! $results ) {
			return [];
		}

		// Download-URLs hinzufügen.
		foreach ( $results as &$doc ) {
			$doc['download_url'] = DocumentDownloadService::generateDownloadUrl( (int) $doc['id'] );
		}

		return $results;
	}

	/**
	 * Dokumente eines Custom Fields löschen
	 *
	 * @param array $document_ids Array von Document-IDs.
	 * @return int Anzahl gelöschter Dokumente.
	 */
	public function deleteDocuments( array $document_ids ): int {
		$deleted = 0;

		foreach ( $document_ids as $doc_id ) {
			$doc = $this->document_service->get( (int) $doc_id );
			if ( $doc && $this->document_service->delete( (int) $doc_id ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}
}

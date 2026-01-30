<?php
/**
 * File Field Type
 *
 * Datei-Upload-Feld mit Multi-Upload-Unterstützung.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * File Upload Feldtyp
 */
class FileField extends AbstractFieldType {

	/**
	 * Erlaubte MIME-Typen
	 *
	 * @var array<string, string>
	 */
	private const ALLOWED_MIME_TYPES = [
		'pdf'  => 'application/pdf',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'gif'  => 'image/gif',
		'txt'  => 'text/plain',
		'rtf'  => 'application/rtf',
		'odt'  => 'application/vnd.oasis.opendocument.text',
	];

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'file';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Datei-Upload', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'upload';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getGroup(): string {
		return 'special';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isFileUpload(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDefaultSettings(): array {
		return array_merge( parent::getDefaultSettings(), [
			'multiple'      => false,
			'max_files'     => 5,
			'accepted_mime' => [ 'pdf', 'doc', 'docx' ],
		] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return [
			[
				'key'         => 'max_file_size',
				'label'       => __( 'Max. Dateigröße (MB)', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 1,
				'max'         => 50,
				'placeholder' => '5',
			],
			[
				'key'         => 'min_files',
				'label'       => __( 'Min. Dateien', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 0,
				'placeholder' => '1',
				'description' => __( 'Nur für Multi-Upload', 'recruiting-playbook' ),
			],
			[
				'key'         => 'max_files',
				'label'       => __( 'Max. Dateien', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 1,
				'placeholder' => '5',
				'description' => __( 'Nur für Multi-Upload', 'recruiting-playbook' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function isEmpty( $value ): bool {
		if ( is_array( $value ) ) {
			return empty( $value );
		}
		return parent::isEmpty( $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, FieldDefinition $field, array $form_data = [] ): bool|WP_Error {
		// Bei Datei-Uploads prüfen wir $_FILES.
		$field_key = $field->getFieldKey();
		$label     = $field->getLabel();

		// Prüfen ob Dateien hochgeladen wurden.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$files = $_FILES[ $field_key ] ?? null;

		if ( $field->isRequired() && ( empty( $files ) || empty( $files['name'] ) || ( is_array( $files['name'] ) && empty( array_filter( $files['name'] ) ) ) ) ) {
			// Bei required: Prüfen ob bereits Dateien gespeichert sind.
			if ( empty( $value ) ) {
				return new WP_Error(
					'required',
					sprintf(
						/* translators: %s: Field label */
						__( '%s ist ein Pflichtfeld.', 'recruiting-playbook' ),
						$label
					)
				);
			}
		}

		if ( empty( $files ) || empty( $files['name'] ) ) {
			return true;
		}

		$settings       = $field->getSettings() ?? [];
		$validation     = $field->getValidation() ?? [];
		$multiple       = ! empty( $settings['multiple'] );
		$accepted_mime  = $settings['accepted_mime'] ?? [ 'pdf', 'doc', 'docx' ];
		$max_size_mb    = $validation['max_file_size'] ?? 5;
		$max_size_bytes = $max_size_mb * 1024 * 1024;

		// Files normalisieren.
		$file_list = $this->normalizeFiles( $files );

		// Anzahl prüfen.
		if ( ! $multiple && count( $file_list ) > 1 ) {
			return new WP_Error(
				'too_many_files',
				sprintf(
					/* translators: %s: Field label */
					__( 'Für %s ist nur eine Datei erlaubt.', 'recruiting-playbook' ),
					$label
				)
			);
		}

		if ( isset( $validation['max_files'] ) && count( $file_list ) > (int) $validation['max_files'] ) {
			return new WP_Error(
				'max_files',
				sprintf(
					/* translators: 1: Field label, 2: Maximum count */
					__( 'Für %1$s sind maximal %2$d Dateien erlaubt.', 'recruiting-playbook' ),
					$label,
					(int) $validation['max_files']
				)
			);
		}

		// Jede Datei validieren.
		foreach ( $file_list as $file ) {
			// Fehler beim Upload.
			if ( $file['error'] !== UPLOAD_ERR_OK ) {
				return new WP_Error(
					'upload_error',
					sprintf(
						/* translators: %s: File name */
						__( 'Fehler beim Upload von %s.', 'recruiting-playbook' ),
						$file['name']
					)
				);
			}

			// Dateigröße prüfen.
			if ( $file['size'] > $max_size_bytes ) {
				return new WP_Error(
					'file_too_large',
					sprintf(
						/* translators: 1: File name, 2: Maximum size in MB */
						__( 'Die Datei %1$s ist zu groß. Maximum: %2$d MB.', 'recruiting-playbook' ),
						$file['name'],
						$max_size_mb
					)
				);
			}

			// MIME-Typ prüfen.
			$mime_valid = $this->validateMimeType( $file, $accepted_mime );
			if ( is_wp_error( $mime_valid ) ) {
				return $mime_valid;
			}
		}

		return true;
	}

	/**
	 * MIME-Typ einer Datei validieren
	 *
	 * @param array    $file          Datei-Array.
	 * @param string[] $accepted_mime Erlaubte Dateiendungen.
	 * @return bool|WP_Error True wenn gültig.
	 */
	private function validateMimeType( array $file, array $accepted_mime ): bool|WP_Error {
		$file_info = wp_check_filetype( $file['name'] );
		$ext       = strtolower( $file_info['ext'] ?? '' );

		if ( ! in_array( $ext, $accepted_mime, true ) ) {
			$allowed_exts = implode( ', ', array_map( 'strtoupper', $accepted_mime ) );
			return new WP_Error(
				'invalid_file_type',
				sprintf(
					/* translators: 1: File name, 2: Allowed extensions */
					__( 'Der Dateityp von %1$s ist nicht erlaubt. Erlaubt: %2$s.', 'recruiting-playbook' ),
					$file['name'],
					$allowed_exts
				)
			);
		}

		return true;
	}

	/**
	 * Files-Array normalisieren
	 *
	 * @param array $files $_FILES Array.
	 * @return array[] Liste von Datei-Arrays.
	 */
	private function normalizeFiles( array $files ): array {
		$result = [];

		if ( is_array( $files['name'] ) ) {
			// Multi-Upload.
			$count = count( $files['name'] );
			for ( $i = 0; $i < $count; $i++ ) {
				if ( empty( $files['name'][ $i ] ) ) {
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
		} else {
			// Single Upload.
			if ( ! empty( $files['name'] ) ) {
				$result[] = $files;
			}
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, FieldDefinition $field ): mixed {
		// Datei-Uploads werden separat verarbeitet.
		// Der Wert hier ist die Liste der bereits gespeicherten Attachment-IDs.
		if ( $this->isEmpty( $value ) ) {
			return [];
		}

		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		// Nur gültige Attachment-IDs behalten.
		return array_filter(
			array_map( 'absint', $value ),
			function ( $id ) {
				return $id > 0 && get_post_type( $id ) === 'attachment';
			}
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '—';
		}

		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		$links = [];
		foreach ( $value as $attachment_id ) {
			$attachment_id = absint( $attachment_id );
			if ( $attachment_id <= 0 ) {
				continue;
			}

			$url      = wp_get_attachment_url( $attachment_id );
			$filename = basename( get_attached_file( $attachment_id ) );
			$filesize = size_format( filesize( get_attached_file( $attachment_id ) ) );

			if ( $url && $filename ) {
				$links[] = sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer" class="rp-file-link">%s <span class="rp-file-size">(%s)</span></a>',
					esc_url( $url ),
					esc_html( $filename ),
					esc_html( $filesize )
				);
			}
		}

		if ( empty( $links ) ) {
			return '—';
		}

		return implode( '<br>', $links );
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatExportValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '';
		}

		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		$urls = [];
		foreach ( $value as $attachment_id ) {
			$url = wp_get_attachment_url( absint( $attachment_id ) );
			if ( $url ) {
				$urls[] = $url;
			}
		}

		return implode( ', ', $urls );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$field_key     = $field->getFieldKey();
		$field_id      = 'rp-field-' . $field_key;
		$settings      = $field->getSettings() ?? [];
		$validation    = $field->getValidation() ?? [];
		$multiple      = ! empty( $settings['multiple'] );
		$accepted_mime = $settings['accepted_mime'] ?? [ 'pdf', 'doc', 'docx' ];
		$max_size_mb   = $validation['max_file_size'] ?? 5;

		// Accept-Attribut erstellen.
		$accept_parts = [];
		foreach ( $accepted_mime as $ext ) {
			if ( isset( self::ALLOWED_MIME_TYPES[ $ext ] ) ) {
				$accept_parts[] = self::ALLOWED_MIME_TYPES[ $ext ];
				$accept_parts[] = '.' . $ext;
			}
		}
		$accept_attr = implode( ',', array_unique( $accept_parts ) );

		$input_attrs = sprintf(
			'id="%s" name="%s%s" type="file" accept="%s"',
			esc_attr( $field_id ),
			esc_attr( $field_key ),
			$multiple ? '[]' : '',
			esc_attr( $accept_attr )
		);

		if ( $multiple ) {
			$input_attrs .= ' multiple';
		}

		if ( $field->isRequired() && empty( $value ) ) {
			$input_attrs .= ' required';
		}

		$html  = sprintf( '<div %s>', $wrapper_attrs );
		$html .= $this->renderLabel( $field );

		// Drag & Drop Zone.
		$html .= '<div class="rp-form__file-zone" x-data="rpFileUpload()">';
		$html .= sprintf(
			'<input %s class="rp-form__file-input" @change="handleFiles($event)" />',
			$input_attrs
		);
		$html .= '<div class="rp-form__file-dropzone" @dragover.prevent="dragover = true" @dragleave.prevent="dragover = false" @drop.prevent="handleDrop($event)" :class="{ \'is-dragover\': dragover }">';
		$html .= '<div class="rp-form__file-dropzone-content">';
		$html .= sprintf(
			'<span class="rp-form__file-icon">%s</span>',
			$this->getUploadIcon()
		);
		$html .= sprintf(
			'<span class="rp-form__file-text">%s</span>',
			esc_html__( 'Dateien hier ablegen oder klicken zum Auswählen', 'recruiting-playbook' )
		);
		$html .= sprintf(
			'<span class="rp-form__file-hint">%s: %s (max. %d MB)</span>',
			esc_html__( 'Erlaubte Formate', 'recruiting-playbook' ),
			esc_html( strtoupper( implode( ', ', $accepted_mime ) ) ),
			$max_size_mb
		);
		$html .= '</div></div>';

		// Dateiliste.
		$html .= '<ul class="rp-form__file-list" x-show="files.length > 0">';
		$html .= '<template x-for="(file, index) in files" :key="index">';
		$html .= '<li class="rp-form__file-item">';
		$html .= '<span class="rp-form__file-name" x-text="file.name"></span>';
		$html .= '<span class="rp-form__file-size" x-text="formatSize(file.size)"></span>';
		$html .= sprintf(
			'<button type="button" class="rp-form__file-remove" @click="removeFile(index)" aria-label="%s">&times;</button>',
			esc_attr__( 'Entfernen', 'recruiting-playbook' )
		);
		$html .= '</li></template></ul>';
		$html .= '</div>'; // .rp-form__file-zone

		// Bereits hochgeladene Dateien anzeigen.
		if ( ! empty( $value ) ) {
			$html .= '<div class="rp-form__file-existing">';
			$html .= sprintf( '<p class="rp-form__file-existing-label">%s:</p>', esc_html__( 'Bereits hochgeladen', 'recruiting-playbook' ) );
			$html .= $this->formatDisplayValue( $value, $field );
			$html .= '</div>';
		}

		$html .= $this->renderDescription( $field );
		$html .= $this->renderError( $field );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Upload-Icon SVG
	 *
	 * @return string SVG HTML.
	 */
	private function getUploadIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>';
	}
}

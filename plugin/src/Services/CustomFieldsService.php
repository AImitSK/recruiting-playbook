<?php
/**
 * Custom Fields Service
 *
 * Verarbeitet und speichert Custom Field Werte für Bewerbungen.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;
use WP_Error;

/**
 * Service für Custom Fields Verarbeitung
 */
class CustomFieldsService {

	/**
	 * Field Definition Service
	 *
	 * @var FieldDefinitionService
	 */
	private FieldDefinitionService $field_service;

	/**
	 * Form Validation Service
	 *
	 * @var FormValidationService
	 */
	private FormValidationService $validation_service;

	/**
	 * Custom Field File Service
	 *
	 * @var CustomFieldFileService
	 */
	private CustomFieldFileService $file_service;

	/**
	 * Conditional Logic Service
	 *
	 * @var ConditionalLogicService
	 */
	private ConditionalLogicService $conditional_service;

	/**
	 * Konstruktor
	 *
	 * @param FieldDefinitionService|null  $field_service       Optional.
	 * @param FormValidationService|null   $validation_service  Optional.
	 * @param CustomFieldFileService|null  $file_service        Optional.
	 * @param ConditionalLogicService|null $conditional_service Optional.
	 */
	public function __construct(
		?FieldDefinitionService $field_service = null,
		?FormValidationService $validation_service = null,
		?CustomFieldFileService $file_service = null,
		?ConditionalLogicService $conditional_service = null
	) {
		$this->field_service       = $field_service ?? new FieldDefinitionService();
		$this->validation_service  = $validation_service ?? new FormValidationService();
		$this->file_service        = $file_service ?? new CustomFieldFileService();
		$this->conditional_service = $conditional_service ?? new ConditionalLogicService();
	}

	/**
	 * Custom Fields für Bewerbung verarbeiten
	 *
	 * @param int   $job_id          Job-ID.
	 * @param int   $application_id  Bewerbungs-ID (0 für neue Bewerbung).
	 * @param array $data            Formular-Daten.
	 * @param array $files           $_FILES Array.
	 * @return array|WP_Error Verarbeitete Custom Fields oder Fehler.
	 */
	public function processCustomFields( int $job_id, int $application_id, array $data, array $files = [] ): array|WP_Error {
		// Felddefinitionen laden.
		$fields = $this->field_service->getFieldsForJob( $job_id );

		if ( empty( $fields ) ) {
			$fields = $this->field_service->getActiveFields();
		}

		// Nur aktivierte, nicht-System-Felder (System-Felder werden direkt verarbeitet).
		$custom_fields = array_filter( $fields, function ( FieldDefinition $field ) {
			return $field->isEnabled() && ! $field->isSystem();
		} );

		if ( empty( $custom_fields ) ) {
			return [];
		}

		// Validieren.
		$validation = $this->validateCustomFields( $custom_fields, $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Datei-Uploads verarbeiten (nur wenn application_id vorhanden).
		$file_mappings = [];
		if ( $application_id > 0 && ! empty( $files ) ) {
			$file_result = $this->file_service->processCustomFieldUploads(
				$application_id,
				$custom_fields,
				$files
			);

			if ( is_wp_error( $file_result ) ) {
				// Nur kritische Fehler weitergeben.
				if ( 'upload_failed' === $file_result->get_error_code() ) {
					return $file_result;
				}
			} else {
				$file_mappings = $file_result;
			}
		}

		// Werte sanitieren und zusammenstellen.
		$result = [];
		$registry = FieldTypeRegistry::getInstance();

		foreach ( $custom_fields as $field ) {
			$field_key = $field->getFieldKey();
			$field_type = $registry->get( $field->getType() );

			// Conditional Logic: Feld überspringen wenn nicht sichtbar.
			if ( ! $this->conditional_service->isFieldVisible( $field, $data ) ) {
				continue;
			}

			// Datei-Felder: Dokument-IDs aus Upload-Ergebnis.
			if ( 'file' === $field->getType() ) {
				$result[ $field_key ] = $file_mappings[ $field_key ] ?? [];
				continue;
			}

			// Normaler Wert.
			$value = $data[ $field_key ] ?? null;

			// Sanitizen.
			if ( $field_type ) {
				$value = $field_type->sanitize( $value, $field );
			} else {
				$value = $this->sanitizeValue( $value, $field );
			}

			$result[ $field_key ] = $value;
		}

		return $result;
	}

	/**
	 * Custom Fields validieren
	 *
	 * @param FieldDefinition[] $fields Felddefinitionen.
	 * @param array             $data   Formular-Daten.
	 * @return true|WP_Error True bei Erfolg.
	 */
	private function validateCustomFields( array $fields, array $data ): true|WP_Error {
		$errors = [];
		$registry = FieldTypeRegistry::getInstance();

		foreach ( $fields as $field ) {
			$field_key = $field->getFieldKey();

			// Conditional Logic: Feld überspringen wenn nicht sichtbar.
			if ( ! $this->conditional_service->isFieldVisible( $field, $data ) ) {
				continue;
			}

			$value = $data[ $field_key ] ?? null;
			$field_type = $registry->get( $field->getType() );

			// Datei-Felder werden separat validiert.
			if ( 'file' === $field->getType() ) {
				continue;
			}

			// Field-Type Validierung.
			if ( $field_type ) {
				$validation = $field_type->validate( $value, $field, $data );
				if ( is_wp_error( $validation ) ) {
					$errors[ $field_key ] = $validation->get_error_message();
				}
			} else {
				// Fallback: Nur Required-Check.
				if ( $field->isRequired() && $this->isEmpty( $value ) ) {
					$errors[ $field_key ] = sprintf(
						/* translators: %s: Field label */
						__( '%s ist ein Pflichtfeld.', 'recruiting-playbook' ),
						$field->getLabel()
					);
				}
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'validation_failed',
				__( 'Bitte korrigieren Sie die Fehler im Formular.', 'recruiting-playbook' ),
				[ 'field_errors' => $errors ]
			);
		}

		return true;
	}

	/**
	 * Wert sanitizen (Fallback)
	 *
	 * @param mixed           $value Wert.
	 * @param FieldDefinition $field Felddefinition.
	 * @return mixed Sanitierter Wert.
	 */
	private function sanitizeValue( mixed $value, FieldDefinition $field ): mixed {
		if ( null === $value ) {
			return '';
		}

		return match ( $field->getType() ) {
			'text', 'textarea' => sanitize_textarea_field( (string) $value ),
			'email'            => sanitize_email( (string) $value ),
			'url'              => esc_url_raw( (string) $value ),
			'number'           => is_numeric( $value ) ? (float) $value : 0,
			'checkbox'         => is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : (bool) $value,
			'select', 'radio'  => sanitize_text_field( (string) $value ),
			'date'             => sanitize_text_field( (string) $value ),
			default            => is_array( $value )
				? array_map( 'sanitize_text_field', $value )
				: sanitize_text_field( (string) $value ),
		};
	}

	/**
	 * Prüfen ob Wert leer ist
	 *
	 * @param mixed $value Wert.
	 * @return bool
	 */
	private function isEmpty( mixed $value ): bool {
		if ( null === $value || '' === $value ) {
			return true;
		}

		if ( is_array( $value ) ) {
			return empty( $value );
		}

		if ( is_string( $value ) ) {
			return '' === trim( $value );
		}

		return false;
	}

	/**
	 * Custom Fields in DB speichern
	 *
	 * @param int   $application_id Bewerbungs-ID.
	 * @param array $custom_fields  Custom Field Werte.
	 * @return bool
	 */
	public function saveCustomFields( int $application_id, array $custom_fields ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';
		$json  = wp_json_encode( $custom_fields, JSON_UNESCAPED_UNICODE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table,
			[
				'custom_fields' => $json,
				'updated_at'    => current_time( 'mysql' ),
			],
			[ 'id' => $application_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Custom Fields aus DB laden
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	public function getCustomFields( int $application_id ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$json = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT custom_fields FROM {$table} WHERE id = %d",
				$application_id
			)
		);

		if ( empty( $json ) ) {
			return [];
		}

		$data = json_decode( $json, true );

		return is_array( $data ) ? $data : [];
	}

	/**
	 * Custom Fields für Anzeige formatieren
	 *
	 * @param int   $application_id Bewerbungs-ID.
	 * @param int   $job_id         Job-ID.
	 * @return array Array von ['key' => '', 'label' => '', 'value' => '', 'type' => ''].
	 */
	public function getFormattedCustomFields( int $application_id, int $job_id ): array {
		$custom_fields = $this->getCustomFields( $application_id );

		if ( empty( $custom_fields ) ) {
			return [];
		}

		$fields = $this->field_service->getFieldsForJob( $job_id );
		if ( empty( $fields ) ) {
			$fields = $this->field_service->getActiveFields();
		}

		$registry = FieldTypeRegistry::getInstance();
		$result   = [];

		foreach ( $fields as $field ) {
			$field_key = $field->getFieldKey();

			// System-Felder überspringen.
			if ( $field->isSystem() ) {
				continue;
			}

			if ( ! array_key_exists( $field_key, $custom_fields ) ) {
				continue;
			}

			$value      = $custom_fields[ $field_key ];
			$field_type = $registry->get( $field->getType() );

			// Wert formatieren.
			$display_value = $value;
			if ( $field_type ) {
				$display_value = $field_type->formatDisplayValue( $value, $field );
			} elseif ( is_array( $value ) ) {
				$display_value = implode( ', ', $value );
			}

			$result[] = [
				'key'           => $field_key,
				'label'         => $field->getLabel(),
				'value'         => $value,
				'display_value' => $display_value,
				'type'          => $field->getType(),
			];
		}

		return $result;
	}

	/**
	 * Custom Fields für CSV-Export formatieren
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @param int $job_id         Job-ID.
	 * @return array Key-Value Paare für Export.
	 */
	public function getExportValues( int $application_id, int $job_id ): array {
		$custom_fields = $this->getCustomFields( $application_id );

		if ( empty( $custom_fields ) ) {
			return [];
		}

		$fields = $this->field_service->getFieldsForJob( $job_id );
		if ( empty( $fields ) ) {
			$fields = $this->field_service->getActiveFields();
		}

		$registry = FieldTypeRegistry::getInstance();
		$result   = [];

		foreach ( $fields as $field ) {
			$field_key = $field->getFieldKey();

			if ( $field->isSystem() ) {
				continue;
			}

			if ( ! array_key_exists( $field_key, $custom_fields ) ) {
				$result[ $field->getLabel() ] = '';
				continue;
			}

			$value      = $custom_fields[ $field_key ];
			$field_type = $registry->get( $field->getType() );

			// Wert für Export formatieren.
			$export_value = $value;
			if ( $field_type ) {
				$export_value = $field_type->formatExportValue( $value, $field );
			} elseif ( is_array( $value ) ) {
				$export_value = implode( '; ', $value );
			}

			$result[ $field->getLabel() ] = $export_value;
		}

		return $result;
	}
}

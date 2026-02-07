<?php
/**
 * Form Validation Service
 *
 * Validiert komplette Formulare mit Custom Fields.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;
use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Service für Formular-Validierung
 */
class FormValidationService {

	/**
	 * FieldTypeRegistry
	 *
	 * @var FieldTypeRegistry
	 */
	private FieldTypeRegistry $registry;

	/**
	 * Konstruktor
	 *
	 * @param FieldTypeRegistry|null $registry Optional: FieldTypeRegistry-Instanz.
	 */
	public function __construct( ?FieldTypeRegistry $registry = null ) {
		$this->registry = $registry ?? FieldTypeRegistry::getInstance();
	}

	/**
	 * Formular validieren
	 *
	 * @param array             $form_data Eingabedaten.
	 * @param FieldDefinition[] $fields    Felddefinitionen.
	 * @return true|WP_Error True bei Erfolg, WP_Error mit allen Fehlern.
	 */
	public function validate( array $form_data, array $fields ): bool|WP_Error {
		$errors = [];

		foreach ( $fields as $field ) {
			// Überschriften und Layout-Elemente überspringen.
			$field_type = $this->registry->get( $field->getFieldType() );
			if ( $field_type === null ) {
				continue;
			}

			// Layout-Elemente (wie Heading) überspringen.
			if ( $field_type->getGroup() === 'layout' ) {
				continue;
			}

			$field_key = $field->getFieldKey();

			// Conditional Logic prüfen.
			if ( ! $this->shouldValidateField( $field, $form_data ) ) {
				continue;
			}

			// Wert aus Formulardaten.
			$value = $form_data[ $field_key ] ?? null;

			// Validierung durchführen.
			$result = $field_type->validate( $value, $field, $form_data );

			if ( is_wp_error( $result ) ) {
				$errors[ $field_key ] = $result->get_error_message();
			}
		}

		if ( ! empty( $errors ) ) {
			$wp_error = new WP_Error( 'validation_failed', __( 'Validierung fehlgeschlagen.', 'recruiting-playbook' ) );

			foreach ( $errors as $field_key => $message ) {
				$wp_error->add( 'field_error', $message, [ 'field' => $field_key ] );
			}

			return $wp_error;
		}

		return true;
	}

	/**
	 * Prüfen ob ein Feld validiert werden soll (Conditional Logic)
	 *
	 * @param FieldDefinition $field     Felddefinition.
	 * @param array           $form_data Formulardaten.
	 * @return bool True wenn Feld validiert werden soll.
	 */
	private function shouldValidateField( FieldDefinition $field, array $form_data ): bool {
		$conditional = $field->getConditional();

		if ( empty( $conditional ) || empty( $conditional['field'] ) ) {
			return true;
		}

		$condition_field = $conditional['field'];
		$operator        = $conditional['operator'] ?? 'equals';
		$expected_value  = $conditional['value'] ?? '';
		$actual_value    = $form_data[ $condition_field ] ?? null;

		return $this->evaluateCondition( $actual_value, $operator, $expected_value );
	}

	/**
	 * Bedingung auswerten
	 *
	 * @param mixed  $actual   Tatsächlicher Wert.
	 * @param string $operator Operator.
	 * @param mixed  $expected Erwarteter Wert.
	 * @return bool True wenn Bedingung erfüllt.
	 */
	private function evaluateCondition( $actual, string $operator, $expected ): bool {
		switch ( $operator ) {
			case 'equals':
				return (string) $actual === (string) $expected;

			case 'not_equals':
				return (string) $actual !== (string) $expected;

			case 'contains':
				return is_string( $actual ) && str_contains( $actual, (string) $expected );

			case 'not_empty':
				return ! empty( $actual );

			case 'empty':
				return empty( $actual );

			case 'greater_than':
				return is_numeric( $actual ) && floatval( $actual ) > floatval( $expected );

			case 'less_than':
				return is_numeric( $actual ) && floatval( $actual ) < floatval( $expected );

			case 'in':
				$values = array_map( 'trim', explode( ',', (string) $expected ) );
				return in_array( (string) $actual, $values, true );

			default:
				return true;
		}
	}

	/**
	 * Formulardaten sanitizen
	 *
	 * @param array             $form_data Eingabedaten.
	 * @param FieldDefinition[] $fields    Felddefinitionen.
	 * @return array Sanitize Daten.
	 */
	public function sanitize( array $form_data, array $fields ): array {
		$sanitized = [];

		foreach ( $fields as $field ) {
			$field_type = $this->registry->get( $field->getFieldType() );
			if ( $field_type === null ) {
				continue;
			}

			// Layout-Elemente überspringen.
			if ( $field_type->getGroup() === 'layout' ) {
				continue;
			}

			$field_key = $field->getFieldKey();
			$value     = $form_data[ $field_key ] ?? null;

			// Conditional Logic prüfen - nur sanitizen wenn Feld aktiv.
			if ( ! $this->shouldValidateField( $field, $form_data ) ) {
				continue;
			}

			$sanitized[ $field_key ] = $field_type->sanitize( $value, $field );
		}

		return $sanitized;
	}

	/**
	 * Validierung und Sanitizing in einem Schritt
	 *
	 * @param array             $form_data Eingabedaten.
	 * @param FieldDefinition[] $fields    Felddefinitionen.
	 * @return array|WP_Error Sanitize Daten oder WP_Error.
	 */
	public function validateAndSanitize( array $form_data, array $fields ): array|WP_Error {
		$validation = $this->validate( $form_data, $fields );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		return $this->sanitize( $form_data, $fields );
	}

	/**
	 * Feldwerte für Anzeige formatieren
	 *
	 * @param array             $form_data Gespeicherte Daten.
	 * @param FieldDefinition[] $fields    Felddefinitionen.
	 * @return array<string, string> Formatierte Werte.
	 */
	public function formatForDisplay( array $form_data, array $fields ): array {
		$formatted = [];

		foreach ( $fields as $field ) {
			$field_type = $this->registry->get( $field->getFieldType() );
			if ( $field_type === null ) {
				continue;
			}

			// Layout-Elemente überspringen.
			if ( $field_type->getGroup() === 'layout' ) {
				continue;
			}

			$field_key = $field->getFieldKey();
			$value     = $form_data[ $field_key ] ?? null;

			$formatted[ $field_key ] = [
				'label' => $field->getLabel(),
				'value' => $field_type->formatDisplayValue( $value, $field ),
				'type'  => $field->getFieldType(),
			];
		}

		return $formatted;
	}

	/**
	 * Feldwerte für Export formatieren
	 *
	 * @param array             $form_data Gespeicherte Daten.
	 * @param FieldDefinition[] $fields    Felddefinitionen.
	 * @return array<string, string> Export-Werte.
	 */
	public function formatForExport( array $form_data, array $fields ): array {
		$export = [];

		foreach ( $fields as $field ) {
			$field_type = $this->registry->get( $field->getFieldType() );
			if ( $field_type === null ) {
				continue;
			}

			// Layout-Elemente überspringen.
			if ( $field_type->getGroup() === 'layout' ) {
				continue;
			}

			$field_key = $field->getFieldKey();
			$value     = $form_data[ $field_key ] ?? null;

			$export[ $field_key ] = $field_type->formatExportValue( $value, $field );
		}

		return $export;
	}

	/**
	 * Export-Header generieren
	 *
	 * @param FieldDefinition[] $fields Felddefinitionen.
	 * @return array<string, string> field_key => label.
	 */
	public function getExportHeaders( array $fields ): array {
		$headers = [];

		foreach ( $fields as $field ) {
			$field_type = $this->registry->get( $field->getFieldType() );
			if ( $field_type === null ) {
				continue;
			}

			// Layout-Elemente überspringen.
			if ( $field_type->getGroup() === 'layout' ) {
				continue;
			}

			$headers[ $field->getFieldKey() ] = $field->getLabel();
		}

		return $headers;
	}

	/**
	 * Verfügbare Conditional-Operatoren
	 *
	 * @return array<string, string> Operator => Label.
	 */
	public function getConditionalOperators(): array {
		return [
			'equals'       => __( 'ist gleich', 'recruiting-playbook' ),
			'not_equals'   => __( 'ist nicht gleich', 'recruiting-playbook' ),
			'contains'     => __( 'enthält', 'recruiting-playbook' ),
			'not_empty'    => __( 'ist ausgefüllt', 'recruiting-playbook' ),
			'empty'        => __( 'ist leer', 'recruiting-playbook' ),
			'greater_than' => __( 'ist größer als', 'recruiting-playbook' ),
			'less_than'    => __( 'ist kleiner als', 'recruiting-playbook' ),
			'in'           => __( 'ist einer von', 'recruiting-playbook' ),
		];
	}
}

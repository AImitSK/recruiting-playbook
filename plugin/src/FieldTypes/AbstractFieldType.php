<?php
/**
 * Abstract Field Type
 *
 * Basis-Implementierung für alle Feldtypen.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Contracts\FieldTypeInterface;
use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Abstrakte Basis-Klasse für Feldtypen
 */
abstract class AbstractFieldType implements FieldTypeInterface {

	/**
	 * Standard-Einstellungen zurückgeben
	 *
	 * @return array
	 */
	public function getDefaultSettings(): array {
		return [
			'width'     => 'full',
			'css_class' => '',
		];
	}

	/**
	 * Standard-Validierungsregeln zurückgeben
	 *
	 * @return array
	 */
	public function getDefaultValidation(): array {
		return [];
	}

	/**
	 * Prüfen ob Wert leer ist
	 *
	 * @param mixed $value Der zu prüfende Wert.
	 * @return bool
	 */
	public function isEmpty( $value ): bool {
		if ( $value === null ) {
			return true;
		}

		if ( is_string( $value ) && trim( $value ) === '' ) {
			return true;
		}

		if ( is_array( $value ) && empty( $value ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Wert für Anzeige formatieren (Standard)
	 *
	 * @param mixed           $value Der anzuzeigende Wert.
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return string
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '—';
		}

		return esc_html( (string) $value );
	}

	/**
	 * Wert für CSV-Export formatieren (Standard)
	 *
	 * @param mixed           $value Der zu exportierende Wert.
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return string
	 */
	public function formatExportValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '';
		}

		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}

		return (string) $value;
	}

	/**
	 * Prüfen ob dieser Feldtyp Optionen unterstützt
	 *
	 * @return bool
	 */
	public function supportsOptions(): bool {
		return false;
	}

	/**
	 * Prüfen ob dieser Feldtyp Datei-Uploads ist
	 *
	 * @return bool
	 */
	public function isFileUpload(): bool {
		return false;
	}

	/**
	 * Required-Validierung durchführen
	 *
	 * @param mixed           $value Der Wert.
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return true|WP_Error
	 */
	protected function validateRequired( $value, FieldDefinition $field ): bool|WP_Error {
		if ( $field->isRequired() && $this->isEmpty( $value ) ) {
			return new WP_Error(
				'required',
				sprintf(
					/* translators: %s: Field label */
					__( '%s ist ein Pflichtfeld.', 'recruiting-playbook' ),
					$field->getLabel()
				)
			);
		}

		return true;
	}

	/**
	 * Minimale Länge validieren
	 *
	 * @param string $value      Der Wert.
	 * @param int    $min_length Minimale Länge.
	 * @param string $label      Feld-Label.
	 * @return true|WP_Error
	 */
	protected function validateMinLength( string $value, int $min_length, string $label ): bool|WP_Error {
		if ( mb_strlen( $value ) < $min_length ) {
			return new WP_Error(
				'min_length',
				sprintf(
					/* translators: 1: Field label, 2: Minimum length */
					__( '%1$s muss mindestens %2$d Zeichen lang sein.', 'recruiting-playbook' ),
					$label,
					$min_length
				)
			);
		}

		return true;
	}

	/**
	 * Maximale Länge validieren
	 *
	 * @param string $value      Der Wert.
	 * @param int    $max_length Maximale Länge.
	 * @param string $label      Feld-Label.
	 * @return true|WP_Error
	 */
	protected function validateMaxLength( string $value, int $max_length, string $label ): bool|WP_Error {
		if ( mb_strlen( $value ) > $max_length ) {
			return new WP_Error(
				'max_length',
				sprintf(
					/* translators: 1: Field label, 2: Maximum length */
					__( '%1$s darf maximal %2$d Zeichen lang sein.', 'recruiting-playbook' ),
					$label,
					$max_length
				)
			);
		}

		return true;
	}

	/**
	 * Regex-Pattern validieren
	 *
	 * @param string $value           Der Wert.
	 * @param string $pattern         Regex-Pattern.
	 * @param string $label           Feld-Label.
	 * @param string $pattern_message Fehlermeldung.
	 * @return true|WP_Error
	 */
	protected function validatePattern( string $value, string $pattern, string $label, string $pattern_message = '' ): bool|WP_Error {
		if ( ! preg_match( '/' . $pattern . '/', $value ) ) {
			$message = ! empty( $pattern_message )
				? $pattern_message
				: sprintf(
					/* translators: %s: Field label */
					__( '%s hat ein ungültiges Format.', 'recruiting-playbook' ),
					$label
				);

			return new WP_Error( 'pattern', $message );
		}

		return true;
	}

	/**
	 * Wrapper-Attribute für Alpine.js generieren
	 *
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return string
	 */
	protected function getWrapperAttributes( FieldDefinition $field ): string {
		$attrs = [];

		// Conditional Logic x-show.
		if ( $field->hasConditional() ) {
			$attrs[] = sprintf( 'x-show="isFieldVisible(\'%s\')"', esc_attr( $field->getFieldKey() ) );
			$attrs[] = 'x-cloak';
		}

		// Width CSS-Klasse.
		$settings = $field->getSettings() ?? [];
		$width    = $settings['width'] ?? 'full';
		$attrs[]  = sprintf( 'class="rp-form__field rp-form__field--%s"', esc_attr( $width ) );

		// Custom CSS-Klasse.
		if ( ! empty( $settings['css_class'] ) ) {
			$attrs[] = sprintf( 'data-custom-class="%s"', esc_attr( $settings['css_class'] ) );
		}

		return implode( ' ', $attrs );
	}

	/**
	 * Input-Attribute generieren
	 *
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @param string          $type  Input-Typ.
	 * @return string
	 */
	protected function getInputAttributes( FieldDefinition $field, string $type = 'text' ): string {
		$attrs = [
			sprintf( 'type="%s"', esc_attr( $type ) ),
			sprintf( 'id="rp-field-%s"', esc_attr( $field->getFieldKey() ) ),
			sprintf( 'name="%s"', esc_attr( $field->getFieldKey() ) ),
			sprintf( 'x-model="formData.%s"', esc_attr( $field->getFieldKey() ) ),
		];

		// Placeholder.
		if ( $field->getPlaceholder() ) {
			$attrs[] = sprintf( 'placeholder="%s"', esc_attr( $field->getPlaceholder() ) );
		}

		// Required (dynamisch via Conditional Logic).
		if ( $field->hasConditional() ) {
			$attrs[] = sprintf( ':required="isFieldRequired(\'%s\')"', esc_attr( $field->getFieldKey() ) );
		} elseif ( $field->isRequired() ) {
			$attrs[] = 'required';
		}

		// Autocomplete.
		$settings = $field->getSettings() ?? [];
		if ( ! empty( $settings['autocomplete'] ) ) {
			$attrs[] = sprintf( 'autocomplete="%s"', esc_attr( $settings['autocomplete'] ) );
		}

		// Validation Feedback.
		$attrs[] = sprintf( '@blur="validateField(\'%s\')"', esc_attr( $field->getFieldKey() ) );

		return implode( ' ', $attrs );
	}

	/**
	 * Label rendern
	 *
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return string
	 */
	protected function renderLabel( FieldDefinition $field ): string {
		$required_mark = '';

		if ( $field->hasConditional() ) {
			$required_mark = sprintf(
				'<span class="rp-form__required" x-show="isFieldRequired(\'%s\')" x-cloak>*</span>',
				esc_attr( $field->getFieldKey() )
			);
		} elseif ( $field->isRequired() ) {
			$required_mark = '<span class="rp-form__required">*</span>';
		}

		return sprintf(
			'<label for="rp-field-%s" class="rp-form__label">%s%s</label>',
			esc_attr( $field->getFieldKey() ),
			esc_html( $field->getLabel() ),
			$required_mark
		);
	}

	/**
	 * Description rendern
	 *
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return string
	 */
	protected function renderDescription( FieldDefinition $field ): string {
		if ( ! $field->getDescription() ) {
			return '';
		}

		return sprintf(
			'<p class="rp-form__description">%s</p>',
			esc_html( $field->getDescription() )
		);
	}

	/**
	 * Error-Container rendern
	 *
	 * @param FieldDefinition $field Die Feld-Definition.
	 * @return string
	 */
	protected function renderError( FieldDefinition $field ): string {
		return sprintf(
			'<p class="rp-form__error" x-show="errors.%s" x-text="errors.%s" x-cloak></p>',
			esc_attr( $field->getFieldKey() ),
			esc_attr( $field->getFieldKey() )
		);
	}
}

<?php
/**
 * Phone Field Type
 *
 * Telefonnummer-Eingabefeld.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Telefon Feldtyp
 */
class PhoneField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'phone';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Phone', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'phone';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getGroup(): string {
		return 'text';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDefaultSettings(): array {
		return array_merge( parent::getDefaultSettings(), [
			'autocomplete' => 'tel',
		] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return [
			[
				'key'         => 'min_length',
				'label'       => __( 'Minimum length', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 0,
				'placeholder' => '6',
			],
			[
				'key'         => 'max_length',
				'label'       => __( 'Maximum length', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 1,
				'placeholder' => '20',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, FieldDefinition $field, array $form_data = [] ): bool|WP_Error {
		$required_check = $this->validateRequired( $value, $field );
		if ( is_wp_error( $required_check ) ) {
			return $required_check;
		}

		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		$value      = (string) $value;
		$validation = $field->getValidation() ?? [];
		$label      = $field->getLabel();

		// Check for valid phone number format (allowed: +, -, numbers, spaces, parentheses).
		$cleaned = preg_replace( '/[^0-9+]/', '', $value );
		if ( strlen( $cleaned ) < 6 ) {
			return new WP_Error(
				'invalid_phone',
				sprintf(
					/* translators: %s: Field label */
					__( '%s must be a valid phone number.', 'recruiting-playbook' ),
					$label
				)
			);
		}

		if ( isset( $validation['min_length'] ) && $validation['min_length'] > 0 ) {
			$min_check = $this->validateMinLength( $value, (int) $validation['min_length'], $label );
			if ( is_wp_error( $min_check ) ) {
				return $min_check;
			}
		}

		if ( isset( $validation['max_length'] ) && $validation['max_length'] > 0 ) {
			$max_check = $this->validateMaxLength( $value, (int) $validation['max_length'], $label );
			if ( is_wp_error( $max_check ) ) {
				return $max_check;
			}
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, FieldDefinition $field ): mixed {
		if ( $this->isEmpty( $value ) ) {
			return '';
		}

		// Keep only allowed characters: numbers, +, -, spaces, parentheses.
		$value = preg_replace( '/[^0-9+\-\s()\/]/', '', (string) $value );

		return trim( $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '—';
		}

		$phone = esc_html( $value );
		$tel   = preg_replace( '/[^0-9+]/', '', $value );

		return sprintf(
			'<a href="tel:%s">%s</a>',
			esc_attr( $tel ),
			$phone
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$input_attrs   = $this->getInputAttributes( $field, 'tel' );

		if ( $value !== null ) {
			$input_attrs .= sprintf( ' value="%s"', esc_attr( $value ) );
		}

		$html  = sprintf( '<div %s>', $wrapper_attrs );
		$html .= $this->renderLabel( $field );
		$html .= sprintf( '<input %s class="rp-form__input" />', $input_attrs );
		$html .= $this->renderDescription( $field );
		$html .= $this->renderError( $field );
		$html .= '</div>';

		return $html;
	}
}

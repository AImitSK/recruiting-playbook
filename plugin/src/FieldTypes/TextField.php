<?php
/**
 * Text Field Type
 *
 * Standard-Textfeld für einzeilige Eingaben.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Text Feldtyp
 */
class TextField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'text';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Textfeld', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'type';
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
			'autocomplete' => 'on',
		] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return [
			[
				'key'         => 'min_length',
				'label'       => __( 'Minimale Länge', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 0,
				'placeholder' => '0',
			],
			[
				'key'         => 'max_length',
				'label'       => __( 'Maximale Länge', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 1,
				'placeholder' => '255',
			],
			[
				'key'         => 'pattern',
				'label'       => __( 'Regex-Pattern', 'recruiting-playbook' ),
				'type'        => 'text',
				'placeholder' => '^[A-Za-z]+$',
			],
			[
				'key'         => 'pattern_message',
				'label'       => __( 'Pattern-Fehlermeldung', 'recruiting-playbook' ),
				'type'        => 'text',
				'placeholder' => __( 'Ungültiges Format', 'recruiting-playbook' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, FieldDefinition $field, array $form_data = [] ): bool|WP_Error {
		// Required-Check.
		$required_check = $this->validateRequired( $value, $field );
		if ( is_wp_error( $required_check ) ) {
			return $required_check;
		}

		// Wenn leer und nicht required, ist es gültig.
		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		$value      = (string) $value;
		$validation = $field->getValidation() ?? [];
		$label      = $field->getLabel();

		// Min Length.
		if ( isset( $validation['min_length'] ) && $validation['min_length'] > 0 ) {
			$min_check = $this->validateMinLength( $value, (int) $validation['min_length'], $label );
			if ( is_wp_error( $min_check ) ) {
				return $min_check;
			}
		}

		// Max Length.
		if ( isset( $validation['max_length'] ) && $validation['max_length'] > 0 ) {
			$max_check = $this->validateMaxLength( $value, (int) $validation['max_length'], $label );
			if ( is_wp_error( $max_check ) ) {
				return $max_check;
			}
		}

		// Pattern.
		if ( ! empty( $validation['pattern'] ) ) {
			$pattern_message = $validation['pattern_message'] ?? '';
			$pattern_check   = $this->validatePattern( $value, $validation['pattern'], $label, $pattern_message );
			if ( is_wp_error( $pattern_check ) ) {
				return $pattern_check;
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

		return sanitize_text_field( (string) $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$input_attrs   = $this->getInputAttributes( $field, 'text' );

		// Value hinzufügen wenn vorhanden.
		if ( $value !== null ) {
			$input_attrs .= sprintf( ' value="%s"', esc_attr( $value ) );
		}

		$validation = $field->getValidation() ?? [];

		// Validierungs-Attribute.
		if ( ! empty( $validation['min_length'] ) ) {
			$input_attrs .= sprintf( ' minlength="%d"', (int) $validation['min_length'] );
		}
		if ( ! empty( $validation['max_length'] ) ) {
			$input_attrs .= sprintf( ' maxlength="%d"', (int) $validation['max_length'] );
		}
		if ( ! empty( $validation['pattern'] ) ) {
			$input_attrs .= sprintf( ' pattern="%s"', esc_attr( $validation['pattern'] ) );
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

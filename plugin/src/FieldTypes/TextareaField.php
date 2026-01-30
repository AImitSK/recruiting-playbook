<?php
/**
 * Textarea Field Type
 *
 * Mehrzeiliges Textfeld.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Textarea Feldtyp
 */
class TextareaField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'textarea';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Textbereich', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'align-left';
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
			'rows' => 5,
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
				'placeholder' => '5000',
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

		return sanitize_textarea_field( (string) $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$settings      = $field->getSettings() ?? [];
		$validation    = $field->getValidation() ?? [];
		$rows          = $settings['rows'] ?? 5;

		$textarea_attrs = [
			sprintf( 'id="rp-field-%s"', esc_attr( $field->getFieldKey() ) ),
			sprintf( 'name="%s"', esc_attr( $field->getFieldKey() ) ),
			sprintf( 'x-model="formData.%s"', esc_attr( $field->getFieldKey() ) ),
			sprintf( 'rows="%d"', (int) $rows ),
		];

		if ( $field->getPlaceholder() ) {
			$textarea_attrs[] = sprintf( 'placeholder="%s"', esc_attr( $field->getPlaceholder() ) );
		}

		if ( $field->hasConditional() ) {
			$textarea_attrs[] = sprintf( ':required="isFieldRequired(\'%s\')"', esc_attr( $field->getFieldKey() ) );
		} elseif ( $field->isRequired() ) {
			$textarea_attrs[] = 'required';
		}

		if ( ! empty( $validation['min_length'] ) ) {
			$textarea_attrs[] = sprintf( 'minlength="%d"', (int) $validation['min_length'] );
		}
		if ( ! empty( $validation['max_length'] ) ) {
			$textarea_attrs[] = sprintf( 'maxlength="%d"', (int) $validation['max_length'] );
		}

		$textarea_attrs[] = sprintf( '@blur="validateField(\'%s\')"', esc_attr( $field->getFieldKey() ) );

		$html  = sprintf( '<div %s>', $wrapper_attrs );
		$html .= $this->renderLabel( $field );
		$html .= sprintf(
			'<textarea %s class="rp-form__textarea">%s</textarea>',
			implode( ' ', $textarea_attrs ),
			esc_textarea( $value ?? '' )
		);
		$html .= $this->renderDescription( $field );
		$html .= $this->renderError( $field );
		$html .= '</div>';

		return $html;
	}
}

<?php
/**
 * Select Field Type
 *
 * Dropdown selection field with optional free text option.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Select/Dropdown field type
 */
class SelectField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'select';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Dropdown', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'chevron-down';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getGroup(): string {
		return 'choice';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDefaultSettings(): array {
		return array_merge( parent::getDefaultSettings(), [
			'allow_other' => false,
		] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function supportsOptions(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return []; // Select has no additional validation rules.
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

		$value    = (string) $value;
		$options  = $field->getOptions() ?? [];
		$settings = $field->getSettings() ?? [];
		$label    = $field->getLabel();

		// If "Other" is allowed, we accept any value.
		if ( ! empty( $settings['allow_other'] ) ) {
			return true;
		}

		// Check if the value is in the options.
		$valid_values = array_column( $options, 'value' );
		if ( ! in_array( $value, $valid_values, true ) ) {
			return new WP_Error(
				'invalid_option',
				sprintf(
					/* translators: %s: Field label */
					__( '%s contains an invalid value.', 'recruiting-playbook' ),
					$label
				)
			);
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
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '—';
		}

		$options = $field->getOptions() ?? [];

		// Find label for the value.
		foreach ( $options as $option ) {
			if ( isset( $option['value'] ) && $option['value'] === $value ) {
				return esc_html( $option['label'] ?? $value );
			}
		}

		// If not found (e.g. "Other"), display the value directly.
		return esc_html( $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$field_key     = $field->getFieldKey();
		$field_id      = 'rp-field-' . $field_key;
		$options       = $field->getOptions() ?? [];
		$settings      = $field->getSettings() ?? [];
		$allow_other   = ! empty( $settings['allow_other'] );

		$select_attrs = sprintf(
			'id="%s" name="%s" x-model="formData.%s"',
			esc_attr( $field_id ),
			esc_attr( $field_key ),
			esc_attr( $field_key )
		);

		if ( $field->isRequired() ) {
			$select_attrs .= ' required';
		}

		$html  = sprintf( '<div %s>', $wrapper_attrs );
		$html .= $this->renderLabel( $field );

		if ( $allow_other ) {
			// With "Other" option: Show text field when "other" is selected.
			$html .= '<div x-data="{ showOther: false }">';
			$html .= sprintf(
				'<select %s class="rp-form__select" x-on:change="showOther = $event.target.value === \'__other__\'">',
				$select_attrs
			);
		} else {
			$html .= sprintf( '<select %s class="rp-form__select">', $select_attrs );
		}

		// Placeholder option.
		$placeholder = $field->getPlaceholder();
		if ( $placeholder ) {
			$html .= sprintf(
				'<option value="" disabled selected>%s</option>',
				esc_html( $placeholder )
			);
		} else {
			$html .= sprintf(
				'<option value="" disabled selected>%s</option>',
				esc_html__( 'Please select...', 'recruiting-playbook' )
			);
		}

		// Options.
		foreach ( $options as $option ) {
			$option_value = $option['value'] ?? '';
			$option_label = $option['label'] ?? $option_value;
			$selected     = ( $value !== null && $value === $option_value ) ? ' selected' : '';

			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $option_value ),
				$selected,
				esc_html( $option_label )
			);
		}

		// "Other" option.
		if ( $allow_other ) {
			$html .= sprintf(
				'<option value="__other__">%s</option>',
				esc_html__( 'Other...', 'recruiting-playbook' )
			);
		}

		$html .= '</select>';

		// Text field for "Other".
		if ( $allow_other ) {
			$html .= sprintf(
				'<input type="text" x-show="showOther" x-cloak x-model="formData.%s_other" class="rp-form__input rp-form__input--other" placeholder="%s" />',
				esc_attr( $field_key ),
				esc_attr__( 'Please specify...', 'recruiting-playbook' )
			);
			$html .= '</div>';
		}

		$html .= $this->renderDescription( $field );
		$html .= $this->renderError( $field );
		$html .= '</div>';

		return $html;
	}
}

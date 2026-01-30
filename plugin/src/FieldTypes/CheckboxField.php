<?php
/**
 * Checkbox Field Type
 *
 * Checkbox-Feld (einzeln oder mehrfach).
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Checkbox Feldtyp
 */
class CheckboxField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'checkbox';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Checkbox', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'check-square';
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
			'layout' => 'vertical', // vertical, horizontal, inline.
			'mode'   => 'single',   // single (boolean), multi (array).
		] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function supportsOptions(): bool {
		// Multi-Checkbox unterstützt Optionen, Single nicht.
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return [
			[
				'key'         => 'min_checked',
				'label'       => __( 'Minimum Auswahl', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 0,
				'placeholder' => '1',
				'description' => __( 'Nur für Multi-Checkbox', 'recruiting-playbook' ),
			],
			[
				'key'         => 'max_checked',
				'label'       => __( 'Maximum Auswahl', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 1,
				'placeholder' => '5',
				'description' => __( 'Nur für Multi-Checkbox', 'recruiting-playbook' ),
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
		$required_check = $this->validateRequired( $value, $field );
		if ( is_wp_error( $required_check ) ) {
			return $required_check;
		}

		if ( $this->isEmpty( $value ) ) {
			return true;
		}

		$settings   = $field->getSettings() ?? [];
		$validation = $field->getValidation() ?? [];
		$label      = $field->getLabel();
		$mode       = $settings['mode'] ?? 'single';

		// Single Checkbox: boolean.
		if ( $mode === 'single' ) {
			return true;
		}

		// Multi Checkbox: Array mit Werten.
		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		$options      = $field->getOptions() ?? [];
		$valid_values = array_column( $options, 'value' );

		// Prüfen ob alle Werte gültig sind.
		foreach ( $value as $v ) {
			if ( ! in_array( $v, $valid_values, true ) ) {
				return new WP_Error(
					'invalid_option',
					sprintf(
						/* translators: %s: Field label */
						__( '%s enthält einen ungültigen Wert.', 'recruiting-playbook' ),
						$label
					)
				);
			}
		}

		// Min Checked.
		if ( isset( $validation['min_checked'] ) && count( $value ) < (int) $validation['min_checked'] ) {
			return new WP_Error(
				'min_checked',
				sprintf(
					/* translators: 1: Field label, 2: Minimum count */
					__( 'Bitte wählen Sie mindestens %2$d Optionen für %1$s.', 'recruiting-playbook' ),
					$label,
					(int) $validation['min_checked']
				)
			);
		}

		// Max Checked.
		if ( isset( $validation['max_checked'] ) && count( $value ) > (int) $validation['max_checked'] ) {
			return new WP_Error(
				'max_checked',
				sprintf(
					/* translators: 1: Field label, 2: Maximum count */
					__( 'Bitte wählen Sie maximal %2$d Optionen für %1$s.', 'recruiting-playbook' ),
					$label,
					(int) $validation['max_checked']
				)
			);
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, FieldDefinition $field ): mixed {
		$settings = $field->getSettings() ?? [];
		$mode     = $settings['mode'] ?? 'single';

		if ( $mode === 'single' ) {
			// Boolean.
			return ! empty( $value );
		}

		// Multi Checkbox: Array.
		if ( $this->isEmpty( $value ) ) {
			return [];
		}

		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		$settings = $field->getSettings() ?? [];
		$mode     = $settings['mode'] ?? 'single';

		if ( $mode === 'single' ) {
			// Boolean als Ja/Nein.
			if ( $value ) {
				return '<span class="rp-badge rp-badge--success">' . esc_html__( 'Ja', 'recruiting-playbook' ) . '</span>';
			}
			return '<span class="rp-badge rp-badge--muted">' . esc_html__( 'Nein', 'recruiting-playbook' ) . '</span>';
		}

		// Multi Checkbox.
		if ( $this->isEmpty( $value ) ) {
			return '—';
		}

		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		$options = $field->getOptions() ?? [];
		$labels  = [];

		foreach ( $value as $v ) {
			foreach ( $options as $option ) {
				if ( isset( $option['value'] ) && $option['value'] === $v ) {
					$labels[] = esc_html( $option['label'] ?? $v );
					break;
				}
			}
		}

		if ( empty( $labels ) ) {
			return '—';
		}

		return implode( ', ', $labels );
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatExportValue( $value, FieldDefinition $field ): string {
		$settings = $field->getSettings() ?? [];
		$mode     = $settings['mode'] ?? 'single';

		if ( $mode === 'single' ) {
			return $value ? '1' : '0';
		}

		if ( is_array( $value ) ) {
			return implode( ', ', $value );
		}

		return (string) $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$settings = $field->getSettings() ?? [];
		$mode     = $settings['mode'] ?? 'single';

		if ( $mode === 'single' ) {
			return $this->renderSingleCheckbox( $field, $value );
		}

		return $this->renderMultiCheckbox( $field, $value );
	}

	/**
	 * Einzelne Checkbox rendern
	 *
	 * @param FieldDefinition $field Felddefinition.
	 * @param mixed           $value Aktueller Wert.
	 * @return string HTML.
	 */
	private function renderSingleCheckbox( FieldDefinition $field, $value ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$field_key     = $field->getFieldKey();
		$field_id      = 'rp-field-' . $field_key;
		$checked       = ! empty( $value ) ? ' checked' : '';

		$html  = sprintf( '<div %s>', $wrapper_attrs );
		$html .= '<label class="rp-form__checkbox-label rp-form__checkbox-label--single">';
		$html .= sprintf(
			'<input type="checkbox" id="%s" name="%s" value="1" x-model="formData.%s" class="rp-form__checkbox"%s%s />',
			esc_attr( $field_id ),
			esc_attr( $field_key ),
			esc_attr( $field_key ),
			$checked,
			$field->isRequired() ? ' required' : ''
		);
		$html .= sprintf( '<span class="rp-form__checkbox-text">%s', esc_html( $field->getLabel() ) );

		if ( $field->isRequired() ) {
			$html .= ' <span class="rp-form__required">*</span>';
		}

		$html .= '</span></label>';
		$html .= $this->renderDescription( $field );
		$html .= $this->renderError( $field );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Multi-Checkbox rendern
	 *
	 * @param FieldDefinition $field Felddefinition.
	 * @param mixed           $value Aktueller Wert.
	 * @return string HTML.
	 */
	private function renderMultiCheckbox( FieldDefinition $field, $value ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$field_key     = $field->getFieldKey();
		$options       = $field->getOptions() ?? [];
		$settings      = $field->getSettings() ?? [];
		$layout        = $settings['layout'] ?? 'vertical';
		$checked_vals  = is_array( $value ) ? $value : [];

		$layout_class = 'rp-form__checkbox-group--' . $layout;

		$html  = sprintf( '<div %s>', $wrapper_attrs );
		$html .= $this->renderLabel( $field );
		$html .= sprintf(
			'<div class="rp-form__checkbox-group %s" role="group" aria-labelledby="rp-label-%s">',
			esc_attr( $layout_class ),
			esc_attr( $field_key )
		);

		foreach ( $options as $index => $option ) {
			$option_value = $option['value'] ?? '';
			$option_label = $option['label'] ?? $option_value;
			$option_id    = sprintf( 'rp-field-%s-%d', $field_key, $index );
			$checked      = in_array( $option_value, $checked_vals, true ) ? ' checked' : '';

			$html .= '<label class="rp-form__checkbox-label">';
			$html .= sprintf(
				'<input type="checkbox" id="%s" name="%s[]" value="%s" class="rp-form__checkbox"%s />',
				esc_attr( $option_id ),
				esc_attr( $field_key ),
				esc_attr( $option_value ),
				$checked
			);
			$html .= sprintf( '<span class="rp-form__checkbox-text">%s</span>', esc_html( $option_label ) );
			$html .= '</label>';
		}

		$html .= '</div>'; // .rp-form__checkbox-group
		$html .= $this->renderDescription( $field );
		$html .= $this->renderError( $field );
		$html .= '</div>';

		return $html;
	}
}

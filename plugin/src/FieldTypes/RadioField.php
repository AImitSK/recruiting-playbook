<?php
/**
 * Radio Field Type
 *
 * Radio-Button Auswahlfeld.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Radio Feldtyp
 */
class RadioField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'radio';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Radio-Buttons', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'circle-dot';
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
			'layout'      => 'vertical', // vertical, horizontal, inline.
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
		return []; // Radio hat keine zusätzlichen Validierungsregeln.
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

		// Wenn "Sonstiges" erlaubt ist, akzeptieren wir jeden Wert.
		if ( ! empty( $settings['allow_other'] ) ) {
			return true;
		}

		// Prüfen ob der Wert in den Optionen enthalten ist.
		$valid_values = array_column( $options, 'value' );
		if ( ! in_array( $value, $valid_values, true ) ) {
			return new WP_Error(
				'invalid_option',
				sprintf(
					/* translators: %s: Field label */
					__( '%s enthält einen ungültigen Wert.', 'recruiting-playbook' ),
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

		// Label für den Wert finden.
		foreach ( $options as $option ) {
			if ( isset( $option['value'] ) && $option['value'] === $value ) {
				return esc_html( $option['label'] ?? $value );
			}
		}

		// Wenn nicht gefunden (z.B. "Sonstiges"), den Wert direkt anzeigen.
		return esc_html( $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$field_key     = $field->getFieldKey();
		$options       = $field->getOptions() ?? [];
		$settings      = $field->getSettings() ?? [];
		$layout        = $settings['layout'] ?? 'vertical';
		$allow_other   = ! empty( $settings['allow_other'] );

		$layout_class = 'rp-form__radio-group--' . $layout;

		$html  = sprintf( '<div %s>', $wrapper_attrs );
		$html .= $this->renderLabel( $field );

		if ( $allow_other ) {
			$html .= '<div x-data="{ showOther: false }">';
		}

		$html .= sprintf(
			'<div class="rp-form__radio-group %s" role="radiogroup" aria-labelledby="rp-label-%s">',
			esc_attr( $layout_class ),
			esc_attr( $field_key )
		);

		// Radio Optionen.
		foreach ( $options as $index => $option ) {
			$option_value = $option['value'] ?? '';
			$option_label = $option['label'] ?? $option_value;
			$option_id    = sprintf( 'rp-field-%s-%d', $field_key, $index );
			$checked      = ( $value !== null && $value === $option_value ) ? ' checked' : '';

			$html .= '<label class="rp-form__radio-label">';
			$html .= sprintf(
				'<input type="radio" id="%s" name="%s" value="%s" x-model="formData.%s" class="rp-form__radio"%s%s />',
				esc_attr( $option_id ),
				esc_attr( $field_key ),
				esc_attr( $option_value ),
				esc_attr( $field_key ),
				$checked,
				$allow_other ? ' x-on:change="showOther = false"' : ''
			);
			$html .= sprintf( '<span class="rp-form__radio-text">%s</span>', esc_html( $option_label ) );
			$html .= '</label>';
		}

		// "Sonstiges" Option.
		if ( $allow_other ) {
			$other_id = sprintf( 'rp-field-%s-other', $field_key );
			$html    .= '<label class="rp-form__radio-label">';
			$html    .= sprintf(
				'<input type="radio" id="%s" name="%s" value="__other__" x-model="formData.%s" class="rp-form__radio" x-on:change="showOther = true" />',
				esc_attr( $other_id ),
				esc_attr( $field_key ),
				esc_attr( $field_key )
			);
			$html .= sprintf(
				'<span class="rp-form__radio-text">%s</span>',
				esc_html__( 'Sonstiges', 'recruiting-playbook' )
			);
			$html .= '</label>';
		}

		$html .= '</div>'; // .rp-form__radio-group

		// Textfeld für "Sonstiges".
		if ( $allow_other ) {
			$html .= sprintf(
				'<input type="text" x-show="showOther" x-cloak x-model="formData.%s_other" class="rp-form__input rp-form__input--other" placeholder="%s" />',
				esc_attr( $field_key ),
				esc_attr__( 'Bitte angeben...', 'recruiting-playbook' )
			);
			$html .= '</div>'; // x-data wrapper
		}

		$html .= $this->renderDescription( $field );
		$html .= $this->renderError( $field );
		$html .= '</div>';

		return $html;
	}
}

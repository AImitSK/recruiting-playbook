<?php
/**
 * Number Field Type
 *
 * Zahlen-Eingabefeld mit Min/Max/Step.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Number Feldtyp
 */
class NumberField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'number';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Zahl', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'hash';
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
			'prefix' => '',
			'suffix' => '',
			'step'   => 1,
		] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return [
			[
				'key'         => 'min',
				'label'       => __( 'Minimalwert', 'recruiting-playbook' ),
				'type'        => 'number',
				'placeholder' => '0',
			],
			[
				'key'         => 'max',
				'label'       => __( 'Maximalwert', 'recruiting-playbook' ),
				'type'        => 'number',
				'placeholder' => '100',
			],
			[
				'key'         => 'step',
				'label'       => __( 'Schrittweite', 'recruiting-playbook' ),
				'type'        => 'number',
				'min'         => 0.01,
				'step'        => 'any',
				'placeholder' => '1',
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

		if ( ! is_numeric( $value ) ) {
			return new WP_Error(
				'not_numeric',
				sprintf(
					/* translators: %s: Field label */
					__( '%s muss eine Zahl sein.', 'recruiting-playbook' ),
					$field->getLabel()
				)
			);
		}

		$num_value  = (float) $value;
		$validation = $field->getValidation() ?? [];
		$label      = $field->getLabel();

		// Min.
		if ( isset( $validation['min'] ) && $num_value < (float) $validation['min'] ) {
			return new WP_Error(
				'min_value',
				sprintf(
					/* translators: 1: Field label, 2: Minimum value */
					__( '%1$s muss mindestens %2$s sein.', 'recruiting-playbook' ),
					$label,
					number_format_i18n( (float) $validation['min'] )
				)
			);
		}

		// Max.
		if ( isset( $validation['max'] ) && $num_value > (float) $validation['max'] ) {
			return new WP_Error(
				'max_value',
				sprintf(
					/* translators: 1: Field label, 2: Maximum value */
					__( '%1$s darf maximal %2$s sein.', 'recruiting-playbook' ),
					$label,
					number_format_i18n( (float) $validation['max'] )
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
			return null;
		}

		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$validation = $field->getValidation() ?? [];
		$step       = $validation['step'] ?? 1;

		// Bei Dezimalzahlen als float zurückgeben.
		if ( is_float( $step ) || strpos( (string) $step, '.' ) !== false ) {
			return (float) $value;
		}

		// Bei ganzen Zahlen als int.
		if ( floor( (float) $value ) == $value ) {
			return (int) $value;
		}

		return (float) $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) || ! is_numeric( $value ) ) {
			return '—';
		}

		$settings  = $field->getSettings() ?? [];
		$prefix    = $settings['prefix'] ?? '';
		$suffix    = $settings['suffix'] ?? '';
		$formatted = number_format_i18n( (float) $value );

		$result = '';
		if ( $prefix ) {
			$result .= esc_html( $prefix ) . ' ';
		}
		$result .= $formatted;
		if ( $suffix ) {
			$result .= ' ' . esc_html( $suffix );
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$input_attrs   = $this->getInputAttributes( $field, 'number' );
		$validation    = $field->getValidation() ?? [];
		$settings      = $field->getSettings() ?? [];

		if ( $value !== null ) {
			$input_attrs .= sprintf( ' value="%s"', esc_attr( $value ) );
		}

		if ( isset( $validation['min'] ) ) {
			$input_attrs .= sprintf( ' min="%s"', esc_attr( $validation['min'] ) );
		}
		if ( isset( $validation['max'] ) ) {
			$input_attrs .= sprintf( ' max="%s"', esc_attr( $validation['max'] ) );
		}
		if ( isset( $validation['step'] ) ) {
			$input_attrs .= sprintf( ' step="%s"', esc_attr( $validation['step'] ) );
		}

		$html  = sprintf( '<div %s>', $wrapper_attrs );
		$html .= $this->renderLabel( $field );

		// Input mit optionalem Prefix/Suffix.
		if ( ! empty( $settings['prefix'] ) || ! empty( $settings['suffix'] ) ) {
			$html .= '<div class="rp-form__input-group">';
			if ( ! empty( $settings['prefix'] ) ) {
				$html .= sprintf( '<span class="rp-form__input-prefix">%s</span>', esc_html( $settings['prefix'] ) );
			}
			$html .= sprintf( '<input %s class="rp-form__input" />', $input_attrs );
			if ( ! empty( $settings['suffix'] ) ) {
				$html .= sprintf( '<span class="rp-form__input-suffix">%s</span>', esc_html( $settings['suffix'] ) );
			}
			$html .= '</div>';
		} else {
			$html .= sprintf( '<input %s class="rp-form__input" />', $input_attrs );
		}

		$html .= $this->renderDescription( $field );
		$html .= $this->renderError( $field );
		$html .= '</div>';

		return $html;
	}
}

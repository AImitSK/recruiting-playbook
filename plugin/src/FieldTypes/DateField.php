<?php
/**
 * Date Field Type
 *
 * Datums-Eingabefeld.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Date Feldtyp
 */
class DateField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'date';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Datum', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'calendar';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getGroup(): string {
		return 'special';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDefaultSettings(): array {
		return array_merge( parent::getDefaultSettings(), [
			'date_format' => 'd.m.Y',
		] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return [
			[
				'key'         => 'min_date',
				'label'       => __( 'Frühestes Datum', 'recruiting-playbook' ),
				'type'        => 'text',
				'placeholder' => 'today, -18 years, 2000-01-01',
				'description' => __( 'Relatives Datum (z.B. "today", "-18 years") oder absolut (YYYY-MM-DD)', 'recruiting-playbook' ),
			],
			[
				'key'         => 'max_date',
				'label'       => __( 'Spätestes Datum', 'recruiting-playbook' ),
				'type'        => 'text',
				'placeholder' => 'today, +1 year, 2030-12-31',
				'description' => __( 'Relatives Datum (z.B. "today", "+1 year") oder absolut (YYYY-MM-DD)', 'recruiting-playbook' ),
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

		$value = (string) $value;
		$label = $field->getLabel();

		// Datumsformat prüfen (YYYY-MM-DD vom HTML5 date input).
		$timestamp = strtotime( $value );
		if ( $timestamp === false ) {
			return new WP_Error(
				'invalid_date',
				sprintf(
					/* translators: %s: Field label */
					__( '%s muss ein gültiges Datum sein.', 'recruiting-playbook' ),
					$label
				)
			);
		}

		$validation = $field->getValidation() ?? [];

		// Min Date.
		if ( ! empty( $validation['min_date'] ) ) {
			$min_date = $this->parseRelativeDate( $validation['min_date'] );
			if ( $min_date && $timestamp < $min_date ) {
				return new WP_Error(
					'min_date',
					sprintf(
						/* translators: 1: Field label, 2: Minimum date */
						__( '%1$s muss am oder nach dem %2$s sein.', 'recruiting-playbook' ),
						$label,
						wp_date( get_option( 'date_format', 'd.m.Y' ), $min_date )
					)
				);
			}
		}

		// Max Date.
		if ( ! empty( $validation['max_date'] ) ) {
			$max_date = $this->parseRelativeDate( $validation['max_date'] );
			if ( $max_date && $timestamp > $max_date ) {
				return new WP_Error(
					'max_date',
					sprintf(
						/* translators: 1: Field label, 2: Maximum date */
						__( '%1$s muss am oder vor dem %2$s sein.', 'recruiting-playbook' ),
						$label,
						wp_date( get_option( 'date_format', 'd.m.Y' ), $max_date )
					)
				);
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

		$value     = sanitize_text_field( (string) $value );
		$timestamp = strtotime( $value );

		if ( $timestamp === false ) {
			return '';
		}

		// Als ISO-Datum speichern.
		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '—';
		}

		$timestamp = strtotime( $value );
		if ( $timestamp === false ) {
			return esc_html( $value );
		}

		return wp_date( get_option( 'date_format', 'd.m.Y' ), $timestamp );
	}

	/**
	 * Relatives Datum parsen
	 *
	 * @param string $date_string Datumsstring (z.B. "today", "-18 years", "2000-01-01").
	 * @return int|false Timestamp oder false.
	 */
	private function parseRelativeDate( string $date_string ): int|false {
		// Absolutdatum (YYYY-MM-DD).
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_string ) ) {
			return strtotime( $date_string );
		}

		// Relatives Datum.
		return strtotime( $date_string );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$input_attrs   = $this->getInputAttributes( $field, 'date' );
		$validation    = $field->getValidation() ?? [];

		if ( $value !== null ) {
			$input_attrs .= sprintf( ' value="%s"', esc_attr( $value ) );
		}

		// Min/Max Date als HTML5-Attribute.
		if ( ! empty( $validation['min_date'] ) ) {
			$min_date = $this->parseRelativeDate( $validation['min_date'] );
			if ( $min_date ) {
				$input_attrs .= sprintf( ' min="%s"', gmdate( 'Y-m-d', $min_date ) );
			}
		}
		if ( ! empty( $validation['max_date'] ) ) {
			$max_date = $this->parseRelativeDate( $validation['max_date'] );
			if ( $max_date ) {
				$input_attrs .= sprintf( ' max="%s"', gmdate( 'Y-m-d', $max_date ) );
			}
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

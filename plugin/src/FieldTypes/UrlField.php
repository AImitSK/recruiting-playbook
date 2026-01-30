<?php
/**
 * URL Field Type
 *
 * URL/Link-Eingabefeld mit Validierung.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * URL Feldtyp
 */
class UrlField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'url';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'URL/Link', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'link';
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
			'autocomplete' => 'url',
		] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return []; // URL-Validierung ist immer aktiv.
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

		// URL-Format prüfen.
		if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
			return new WP_Error(
				'invalid_url',
				sprintf(
					/* translators: %s: Field label */
					__( '%s muss eine gültige URL sein.', 'recruiting-playbook' ),
					$field->getLabel()
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

		return esc_url_raw( (string) $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		if ( $this->isEmpty( $value ) ) {
			return '—';
		}

		$url = esc_url( $value );
		if ( empty( $url ) ) {
			return '—';
		}

		// Gekürzte Anzeige.
		$display = preg_replace( '#^https?://#', '', $url );
		$display = rtrim( $display, '/' );
		if ( strlen( $display ) > 50 ) {
			$display = substr( $display, 0, 47 ) . '...';
		}

		return sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			$url,
			esc_html( $display )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$wrapper_attrs = $this->getWrapperAttributes( $field );
		$input_attrs   = $this->getInputAttributes( $field, 'url' );

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

<?php
/**
 * Heading Field Type
 *
 * Heading/Section Title (display only, no input field).
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * Heading Field Type (Display-Only)
 */
class HeadingField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'heading';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'Heading', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'heading';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getGroup(): string {
		return 'layout';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDefaultSettings(): array {
		return [
			'level' => 'h3', // h2, h3, h4, h5, h6.
			'style' => 'default', // default, underline, accent.
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		return []; // No validation for display elements.
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, FieldDefinition $field, array $form_data = [] ): bool|WP_Error {
		// Headings have no input values.
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, FieldDefinition $field ): mixed {
		// Headings have no values.
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatDisplayValue( $value, FieldDefinition $field ): string {
		// Headings are not shown in data views.
		return '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function formatExportValue( $value, FieldDefinition $field ): string {
		// Headings are not exported.
		return '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$settings     = $field->getSettings() ?? [];
		$level        = $settings['level'] ?? 'h3';
		$style        = $settings['style'] ?? 'default';
		$label        = $field->getLabel();
		$description  = $field->getDescription();
		$conditional  = $field->getConditional();

		// Allowed heading levels.
		$allowed_levels = [ 'h2', 'h3', 'h4', 'h5', 'h6' ];
		if ( ! in_array( $level, $allowed_levels, true ) ) {
			$level = 'h3';
		}

		$wrapper_class = 'rp-form__heading rp-form__heading--' . $style;

		// Conditional Logic Attribute.
		$wrapper_attrs = sprintf( 'class="%s"', esc_attr( $wrapper_class ) );
		if ( ! empty( $conditional ) && ! empty( $conditional['field'] ) ) {
			$condition_expr = $this->buildConditionalExpression( $conditional );
			$wrapper_attrs .= sprintf( ' x-show="%s" x-cloak', esc_attr( $condition_expr ) );
		}

		$html = sprintf( '<div %s>', $wrapper_attrs );
		$html .= sprintf(
			'<%1$s class="rp-form__heading-text">%2$s</%1$s>',
			$level,
			esc_html( $label )
		);

		if ( $description ) {
			$html .= sprintf(
				'<p class="rp-form__heading-description">%s</p>',
				esc_html( $description )
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Build conditional expression for Alpine.js
	 *
	 * @param array $conditional Conditional configuration.
	 * @return string Alpine.js Expression.
	 */
	private function buildConditionalExpression( array $conditional ): string {
		$field    = $conditional['field'] ?? '';
		$operator = $conditional['operator'] ?? 'equals';
		$value    = $conditional['value'] ?? '';

		if ( empty( $field ) ) {
			return 'true';
		}

		$field_ref = "formData.{$field}";

		switch ( $operator ) {
			case 'equals':
				return sprintf( "%s === '%s'", $field_ref, addslashes( $value ) );

			case 'not_equals':
				return sprintf( "%s !== '%s'", $field_ref, addslashes( $value ) );

			case 'contains':
				return sprintf( "(%s || '').includes('%s')", $field_ref, addslashes( $value ) );

			case 'not_empty':
				return sprintf( "!!%s", $field_ref );

			case 'empty':
				return sprintf( "!%s", $field_ref );

			case 'greater_than':
				return sprintf( "parseFloat(%s || 0) > %s", $field_ref, floatval( $value ) );

			case 'less_than':
				return sprintf( "parseFloat(%s || 0) < %s", $field_ref, floatval( $value ) );

			case 'in':
				$values = array_map( 'trim', explode( ',', $value ) );
				$json   = wp_json_encode( $values );
				return sprintf( "%s.includes(%s)", $json, $field_ref );

			default:
				return 'true';
		}
	}
}

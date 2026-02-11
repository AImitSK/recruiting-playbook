<?php
/**
 * HTML Field Type
 *
 * Anzeigefeld für benutzerdefinierten HTML-Inhalt (Hinweistexte, Erklärungen).
 * Sammelt keine Daten, nur zur Anzeige.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use WP_Error;

/**
 * HTML Feldtyp
 */
class HtmlField extends AbstractFieldType {

	/**
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return 'html';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabel(): string {
		return __( 'HTML / Text Block', 'recruiting-playbook' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIcon(): string {
		return 'code';
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
			'content' => '',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAvailableValidationRules(): array {
		// HTML-Feld hat keine Validierungsregeln (Anzeigefeld).
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate( $value, FieldDefinition $field, array $form_data = [] ): bool|WP_Error {
		// Anzeigefelder sind immer gültig.
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( $value, FieldDefinition $field ): mixed {
		// Anzeigefelder sammeln keine Daten.
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( FieldDefinition $field, $value = null ): string {
		$settings = $field->getSettings() ?? [];
		$content  = $settings['content'] ?? '';

		if ( empty( $content ) ) {
			return '';
		}

		// HTML-Inhalt mit erlaubten Tags rendern.
		$allowed_tags = wp_kses_allowed_html( 'post' );

		$html  = '<div class="rp-form__html-content">';
		$html .= wp_kses( $content, $allowed_tags );
		$html .= '</div>';

		return $html;
	}
}

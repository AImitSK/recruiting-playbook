<?php
/**
 * Email Renderer Service - Rendert E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service zum Rendern von E-Mail-Templates
 */
class EmailRenderer {

	/**
	 * Template-Verzeichnis
	 */
	private const TEMPLATE_DIR = 'templates/emails/';

	/**
	 * Erlaubte Template-Dateien (Whitelist gegen Path Traversal)
	 */
	private const ALLOWED_TEMPLATES = [
		'base-layout.php',
		'application-received.php',
		'interview-invitation.php',
		'rejection.php',
		'offer-letter.php',
		'hr-new-application.php',
	];

	/**
	 * Free-Tier Templates (ohne Pro-Lizenz verfügbar)
	 */
	private const FREE_TEMPLATES = [
		'application-received',
		'hr-new-application',
	];

	/**
	 * Verfügbare System-Templates
	 */
	private const SYSTEM_TEMPLATES = [
		'application-received'  => 'application-received.php',
		'interview-invitation'  => 'interview-invitation.php',
		'rejection'             => 'rejection.php',
		'offer-letter'          => 'offer-letter.php',
		'hr-new-application'    => 'hr-new-application.php',
	];

	/**
	 * E-Mail rendern mit Base-Layout
	 *
	 * @param string $content      E-Mail-Inhalt (HTML).
	 * @param array  $placeholders Platzhalter-Werte.
	 * @param array  $options      Optionen (subject, logo_url, footer_text, etc.).
	 * @return string Vollständige HTML-E-Mail.
	 */
	public function render( string $content, array $placeholders = [], array $options = [] ): string {
		// Platzhalter im Content ersetzen.
		$content = $this->replacePlaceholders( $content, $placeholders );

		// Template-Variablen vorbereiten.
		$settings = get_option( 'rp_settings', [] );

		$subject         = $options['subject'] ?? '';
		$company         = $options['company'] ?? $settings['company_name'] ?? get_bloginfo( 'name' );
		$logo_url        = $options['logo_url'] ?? $settings['company_logo'] ?? '';
		$footer_text     = $options['footer_text'] ?? '';
		$unsubscribe_url = $options['unsubscribe_url'] ?? '';
		$primary_color   = $options['primary_color'] ?? apply_filters( 'rp_email_primary_color', '#0073aa' );
		$text_color      = $options['text_color'] ?? apply_filters( 'rp_email_text_color', '#333333' );

		// Base-Layout rendern.
		ob_start();
		include $this->getTemplatePath( 'base-layout.php' );
		$output = ob_get_clean();

		// Fallback bei ob_get_clean() Fehler.
		if ( false === $output ) {
			return '';
		}

		return $output;
	}

	/**
	 * Prüfen ob Template in aktueller Lizenz-Stufe erlaubt ist
	 *
	 * @param string $template_slug Template-Slug.
	 * @return bool True wenn erlaubt.
	 */
	private function isTemplateAllowed( string $template_slug ): bool {
		// Pro-Feature Check: Alle Templates erlaubt.
		if ( function_exists( 'rp_can' ) && rp_can( 'email_templates' ) ) {
			return true;
		}

		// Free-Tier: Nur Basis-Templates.
		return in_array( $template_slug, self::FREE_TEMPLATES, true );
	}

	/**
	 * System-Template rendern
	 *
	 * @param string $template_slug Template-Slug (z.B. 'application-received').
	 * @param array  $placeholders  Platzhalter-Werte.
	 * @param array  $options       Optionen für das Base-Layout.
	 * @return string|null Vollständige HTML-E-Mail oder null wenn Template nicht existiert.
	 */
	public function renderSystemTemplate( string $template_slug, array $placeholders = [], array $options = [] ): ?string {
		// Feature-Gate: Pro-Templates benötigen Lizenz.
		if ( ! $this->isTemplateAllowed( $template_slug ) ) {
			return null;
		}

		if ( ! isset( self::SYSTEM_TEMPLATES[ $template_slug ] ) ) {
			return null;
		}

		$template_file = self::SYSTEM_TEMPLATES[ $template_slug ];
		$template_path = $this->getTemplatePath( $template_file );

		if ( ! file_exists( $template_path ) ) {
			return null;
		}

		// Template-Content rendern.
		ob_start();
		include $template_path;
		$content = ob_get_clean();

		// Mit Base-Layout wrappen.
		return $this->render( $content, $placeholders, $options );
	}

	/**
	 * Nur den Content eines Templates rendern (ohne Base-Layout)
	 *
	 * @param string $template_slug Template-Slug.
	 * @param array  $placeholders  Platzhalter-Werte.
	 * @return string|null Content oder null.
	 */
	public function renderTemplateContent( string $template_slug, array $placeholders = [] ): ?string {
		if ( ! isset( self::SYSTEM_TEMPLATES[ $template_slug ] ) ) {
			return null;
		}

		$template_file = self::SYSTEM_TEMPLATES[ $template_slug ];
		$template_path = $this->getTemplatePath( $template_file );

		if ( ! file_exists( $template_path ) ) {
			return null;
		}

		ob_start();
		include $template_path;
		return ob_get_clean();
	}

	/**
	 * Benutzerdefinierten Content mit Base-Layout rendern
	 *
	 * @param string $body_html    HTML-Inhalt.
	 * @param array  $placeholders Platzhalter-Werte.
	 * @param array  $options      Layout-Optionen.
	 * @return string Vollständige HTML-E-Mail.
	 */
	public function renderCustomContent( string $body_html, array $placeholders = [], array $options = [] ): string {
		// Platzhalter im Content ersetzen.
		$content = $this->replacePlaceholders( $body_html, $placeholders );

		// Mit Base-Layout wrappen.
		return $this->render( $content, $placeholders, $options );
	}

	/**
	 * Plain-Text-Version aus HTML generieren
	 *
	 * @param string $html HTML-Inhalt.
	 * @return string Plain-Text-Version.
	 */
	public function htmlToPlainText( string $html ): string {
		// Links beibehalten.
		$text = preg_replace( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', '$2 ($1)', $html );

		// Line breaks einfügen.
		$text = preg_replace( '/<br\s*\/?>/i', "\n", $text );
		$text = preg_replace( '/<\/(p|div|h[1-6]|li|tr)>/i', "\n\n", $text );
		$text = preg_replace( '/<li[^>]*>/i', "• ", $text );

		// HTML-Tags entfernen.
		$text = wp_strip_all_tags( $text );

		// Mehrfache Leerzeilen reduzieren.
		$text = preg_replace( '/\n{3,}/', "\n\n", $text );

		// HTML-Entities dekodieren.
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		return trim( $text );
	}

	/**
	 * Verfügbare System-Templates abrufen
	 *
	 * @return array Array mit Template-Slugs und Info.
	 */
	public function getAvailableTemplates(): array {
		$templates = [];

		foreach ( self::SYSTEM_TEMPLATES as $slug => $file ) {
			$templates[ $slug ] = [
				'slug' => $slug,
				'file' => $file,
				'name' => $this->getTemplateName( $slug ),
			];
		}

		return $templates;
	}

	/**
	 * Template-Name aus Slug ableiten
	 *
	 * @param string $slug Template-Slug.
	 * @return string Menschenlesbarer Name.
	 */
	private function getTemplateName( string $slug ): string {
		$names = [
			'application-received'  => __( 'Application Confirmation', 'recruiting-playbook' ),
			'interview-invitation'  => __( 'Interview Invitation', 'recruiting-playbook' ),
			'rejection'             => __( 'Rejection', 'recruiting-playbook' ),
			'offer-letter'          => __( 'Job Offer', 'recruiting-playbook' ),
			'hr-new-application'    => __( 'HR: New Application', 'recruiting-playbook' ),
		];

		return $names[ $slug ] ?? $slug;
	}

	/**
	 * Template-Pfad ermitteln (mit Path Traversal Schutz)
	 *
	 * Prüft zuerst im Theme (für Overrides), dann im Plugin.
	 *
	 * @param string $template Template-Dateiname.
	 * @return string Vollständiger Pfad.
	 */
	private function getTemplatePath( string $template ): string {
		// Security: Basename extrahieren (verhindert ../ Path Traversal).
		$template = basename( $template );

		// Security: Nur .php Dateien erlauben.
		if ( ! str_ends_with( $template, '.php' ) ) {
			$template .= '.php';
		}

		// Security: Whitelist-Check gegen erlaubte Templates.
		if ( ! in_array( $template, self::ALLOWED_TEMPLATES, true ) ) {
			// Fallback auf Base-Layout bei ungültigem Template.
			return RP_PLUGIN_DIR . self::TEMPLATE_DIR . 'base-layout.php';
		}

		// Theme-Override prüfen.
		$theme_path = get_stylesheet_directory() . '/recruiting-playbook/emails/' . $template;
		if ( file_exists( $theme_path ) && is_file( $theme_path ) ) {
			// Security: Sicherstellen dass Pfad im erwarteten Verzeichnis liegt.
			$real_theme_path = realpath( $theme_path );
			$expected_dir    = realpath( get_stylesheet_directory() );

			if ( $real_theme_path && $expected_dir && str_starts_with( $real_theme_path, $expected_dir ) ) {
				return $theme_path;
			}
		}

		// Fallback: Plugin-Verzeichnis.
		return RP_PLUGIN_DIR . self::TEMPLATE_DIR . $template;
	}

	/**
	 * Platzhalter-Wert für HTML-Kontext escapen
	 *
	 * @param mixed $value Platzhalter-Wert.
	 * @return string Escaped Wert.
	 */
	private function escapePlaceholderValue( $value ): string {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		return esc_html( (string) $value );
	}

	/**
	 * Platzhalter im Text ersetzen (mit XSS-Schutz)
	 *
	 * @param string $text         Text mit Platzhaltern.
	 * @param array  $placeholders Platzhalter-Werte.
	 * @return string Text mit ersetzten Platzhaltern.
	 */
	private function replacePlaceholders( string $text, array $placeholders ): string {
		if ( empty( $placeholders ) ) {
			return $text;
		}

		foreach ( $placeholders as $key => $value ) {
			// Security: Wert für HTML escapen (XSS-Schutz).
			$escaped_value = $this->escapePlaceholderValue( $value );

			$text = str_replace( '{' . $key . '}', $escaped_value, $text );
		}

		return $text;
	}
}

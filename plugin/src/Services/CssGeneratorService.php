<?php
/**
 * CSS Generator Service - Generiert CSS Custom Properties aus Design-Einstellungen
 *
 * Wandelt die Design-Settings in CSS-Variablen um und gibt sie im Frontend aus.
 * Die Variablen werden im <head> als Inline-Style eingefügt.
 *
 * @package RecruitingPlaybook
 * @see docs/technical/design-branding-specification-v2.md
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service für CSS-Variablen Generierung
 */
class CssGeneratorService {

	/**
	 * Design Service Instanz
	 *
	 * @var DesignService
	 */
	private DesignService $design_service;

	/**
	 * Konstruktor
	 *
	 * @param DesignService|null $design_service Optional: DesignService Instanz.
	 */
	public function __construct( ?DesignService $design_service = null ) {
		$this->design_service = $design_service ?? new DesignService();
	}

	/**
	 * CSS-Variablen registrieren
	 *
	 * Registriert den wp_head Hook für die CSS-Ausgabe.
	 */
	public function register(): void {
		add_action( 'wp_head', [ $this, 'output_css_variables' ], 5 );
	}

	/**
	 * CSS-Variablen im <head> ausgeben
	 *
	 * Diese Methode wird vom wp_head Hook aufgerufen.
	 * WICHTIG: Prüft NICHT auf Pro-Lizenz - Design bleibt nach Ablauf erhalten.
	 */
	public function output_css_variables(): void {
		$css = $this->generate_css();

		if ( empty( $css ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS ist sicher generiert.
		echo '<style id="rp-design-variables">' . $css . '</style>' . "\n";
	}

	/**
	 * Komplettes CSS generieren
	 *
	 * @return string Generiertes CSS.
	 */
	public function generate_css(): string {
		$settings = $this->design_service->get_design_settings();
		$defaults = $this->design_service->get_defaults();

		$css_vars   = [];
		$css_rules  = [];

		// Primärfarbe (Theme oder Custom).
		$primary = $this->design_service->get_primary_color();
		$css_vars['--rp-color-primary'] = $primary;

		// Primärfarbe-Varianten.
		$css_vars['--rp-color-primary-hover'] = $this->design_service->adjust_color_brightness( $primary, -15 );
		$css_vars['--rp-color-primary-light'] = $this->design_service->hex_to_rgba( $primary, 0.15 );

		// HSL für Tailwind-Kompatibilität.
		$hsl = $this->design_service->hex_to_hsl( $primary );
		$css_vars['--primary'] = "{$hsl['h']} {$hsl['s']}% {$hsl['l']}%";

		// Card-Variablen.
		$css_vars = array_merge( $css_vars, $this->generate_card_variables( $settings ) );

		// Button-Variablen (nur bei Custom Design).
		if ( ! empty( $settings['button_use_custom_design'] ) ) {
			$css_vars = array_merge( $css_vars, $this->generate_button_variables( $settings, $primary ) );
		}

		// Typografie-Variablen (nur wenn von Defaults abweichend).
		$css_vars = array_merge( $css_vars, $this->generate_typography_variables( $settings, $defaults ) );

		// Link-Variablen.
		$css_vars = array_merge( $css_vars, $this->generate_link_variables( $settings, $primary ) );

		// Badge-Variablen.
		$css_vars = array_merge( $css_vars, $this->generate_badge_variables( $settings ) );

		// KI-Button-Variablen.
		$css_vars = array_merge( $css_vars, $this->generate_ai_button_variables( $settings, $primary ) );

		// CSS zusammenbauen.
		$css = ".rp-plugin {\n";
		foreach ( $css_vars as $name => $value ) {
			if ( null !== $value && '' !== $value ) {
				$css .= "  {$name}: {$value};\n";
			}
		}
		$css .= "}\n";

		// Button Custom Design: Wenn aktiv, Styles generieren die Theme überschreiben.
		if ( ! empty( $settings['button_use_custom_design'] ) ) {
			$css .= $this->generate_custom_button_css( $settings );
		}

		// Zusätzliche CSS-Regeln (Hover-Effekte etc.).
		$css .= $this->generate_additional_rules( $settings );

		return $css;
	}

	/**
	 * Card-Variablen generieren
	 *
	 * @param array $settings Design-Einstellungen.
	 * @return array CSS-Variablen.
	 */
	private function generate_card_variables( array $settings ): array {
		$vars = [];

		// Border-Radius.
		$vars['--rp-card-radius'] = $settings['card_border_radius'] . 'px';

		// Schatten.
		$shadow_values = [
			'none'   => 'none',
			'light'  => '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
			'medium' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
			'strong' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
		];
		$vars['--rp-card-shadow'] = $shadow_values[ $settings['card_shadow'] ] ?? $shadow_values['light'];

		// Border.
		if ( $settings['card_border_show'] ) {
			$vars['--rp-card-border'] = '1px solid ' . $settings['card_border_color'];
			$vars['--rp-card-border-color'] = $settings['card_border_color'];
		} else {
			$vars['--rp-card-border'] = 'none';
			$vars['--rp-card-border-color'] = 'transparent';
		}

		// Hintergrund.
		$vars['--rp-card-bg'] = $settings['card_background'];

		// Layout-Preset Padding.
		$padding_values = [
			'compact'  => '12px',
			'standard' => '20px',
			'spacious' => '32px',
		];
		$vars['--rp-card-padding'] = $padding_values[ $settings['card_layout_preset'] ] ?? '20px';

		return $vars;
	}

	/**
	 * Button-Variablen generieren
	 *
	 * @param array  $settings Design-Einstellungen.
	 * @param string $primary  Primärfarbe.
	 * @return array CSS-Variablen.
	 */
	private function generate_button_variables( array $settings, string $primary ): array {
		$vars = [];

		// Farben: Bei override_button_colors=false erben von Primärfarbe.
		if ( $settings['override_button_colors'] ) {
			$vars['--rp-btn-bg']         = $settings['button_bg_color'];
			$vars['--rp-btn-bg-hover']   = $settings['button_bg_color_hover'];
			$vars['--rp-btn-text']       = $settings['button_text_color'];
			$vars['--rp-btn-text-hover'] = $settings['button_text_color_hover'];
		} else {
			$vars['--rp-btn-bg']         = $primary;
			$vars['--rp-btn-bg-hover']   = $this->design_service->adjust_color_brightness( $primary, -15 );
			$vars['--rp-btn-text']       = '#ffffff';
			$vars['--rp-btn-text-hover'] = '#ffffff';
		}

		// Border-Radius.
		$vars['--rp-btn-radius'] = $settings['button_border_radius'] . 'px';

		// Border.
		if ( $settings['button_border_show'] ) {
			$vars['--rp-btn-border']       = $settings['button_border_width'] . 'px solid ' . $settings['button_border_color'];
			$vars['--rp-btn-border-width'] = $settings['button_border_width'] . 'px';
			$vars['--rp-btn-border-color'] = $settings['button_border_color'];
		} else {
			$vars['--rp-btn-border']       = 'none';
			$vars['--rp-btn-border-width'] = '0';
			$vars['--rp-btn-border-color'] = 'transparent';
		}

		// Größe (Padding).
		$size_values = [
			'small'  => '0.5rem 1rem',
			'medium' => '0.75rem 1.5rem',
			'large'  => '1rem 2rem',
		];
		$vars['--rp-btn-padding'] = $size_values[ $settings['button_size'] ] ?? $size_values['medium'];

		// Schatten.
		$shadow_values = [
			'none'   => 'none',
			'light'  => '0 1px 3px rgba(0,0,0,0.1)',
			'medium' => '0 4px 6px rgba(0,0,0,0.1)',
			'strong' => '0 10px 15px rgba(0,0,0,0.1)',
		];
		$vars['--rp-btn-shadow']       = $shadow_values[ $settings['button_shadow'] ] ?? 'none';
		$vars['--rp-btn-shadow-hover'] = $shadow_values[ $settings['button_shadow_hover'] ] ?? 'none';

		return $vars;
	}

	/**
	 * Typografie-Variablen generieren
	 *
	 * Nur wenn Werte von Defaults abweichen (Pro-Override-Pattern).
	 *
	 * @param array $settings Design-Einstellungen.
	 * @param array $defaults Default-Werte.
	 * @return array CSS-Variablen.
	 */
	private function generate_typography_variables( array $settings, array $defaults ): array {
		$vars = [];

		// Schriftgrößen nur bei Abweichung.
		$font_size_keys = [ 'font_size_h1', 'font_size_h2', 'font_size_h3', 'font_size_body', 'font_size_small' ];
		$css_var_map    = [
			'font_size_h1'    => '--rp-font-size-h1',
			'font_size_h2'    => '--rp-font-size-h2',
			'font_size_h3'    => '--rp-font-size-h3',
			'font_size_body'  => '--rp-font-size-body',
			'font_size_small' => '--rp-font-size-small',
		];

		foreach ( $font_size_keys as $key ) {
			if ( isset( $settings[ $key ] ) && $settings[ $key ] !== $defaults[ $key ] ) {
				$vars[ $css_var_map[ $key ] ] = $settings[ $key ] . 'rem';
			}
		}

		// Zeilenabstand nur bei Abweichung.
		if ( isset( $settings['line_height_heading'] ) && $settings['line_height_heading'] !== $defaults['line_height_heading'] ) {
			$vars['--rp-line-height-heading'] = (string) $settings['line_height_heading'];
		}
		if ( isset( $settings['line_height_body'] ) && $settings['line_height_body'] !== $defaults['line_height_body'] ) {
			$vars['--rp-line-height-body'] = (string) $settings['line_height_body'];
		}

		// Abstände (Stellenausschreibung).
		if ( isset( $settings['heading_margin_top'] ) && $settings['heading_margin_top'] !== $defaults['heading_margin_top'] ) {
			$vars['--rp-heading-margin-top'] = $settings['heading_margin_top'] . 'em';
		}
		if ( isset( $settings['heading_margin_bottom'] ) && $settings['heading_margin_bottom'] !== $defaults['heading_margin_bottom'] ) {
			$vars['--rp-heading-margin-bottom'] = $settings['heading_margin_bottom'] . 'em';
		}
		if ( isset( $settings['paragraph_spacing'] ) && $settings['paragraph_spacing'] !== $defaults['paragraph_spacing'] ) {
			$vars['--rp-paragraph-spacing'] = $settings['paragraph_spacing'] . 'em';
		}

		return $vars;
	}

	/**
	 * Link-Variablen generieren
	 *
	 * @param array  $settings Design-Einstellungen.
	 * @param string $primary  Primärfarbe.
	 * @return array CSS-Variablen.
	 */
	private function generate_link_variables( array $settings, string $primary ): array {
		$vars = [];

		// Farbe: Primär oder Custom.
		$vars['--rp-link-color'] = $settings['link_use_primary'] ? $primary : $settings['link_color'];

		// Decoration.
		$decoration_values = [
			'none'      => 'none',
			'underline' => 'underline',
			'hover'     => 'none',
		];
		$vars['--rp-link-decoration'] = $decoration_values[ $settings['link_decoration'] ] ?? 'underline';

		return $vars;
	}

	/**
	 * Badge-Variablen generieren
	 *
	 * @param array $settings Design-Einstellungen.
	 * @return array CSS-Variablen.
	 */
	private function generate_badge_variables( array $settings ): array {
		$vars    = [];
		$is_solid = 'solid' === $settings['badge_style'];

		// Badge: New.
		$vars['--rp-badge-new'] = $settings['badge_color_new'];
		if ( $is_solid ) {
			$vars['--rp-badge-new-bg']   = $settings['badge_color_new'];
			$vars['--rp-badge-new-text'] = '#ffffff';
		} else {
			$vars['--rp-badge-new-bg']   = $this->design_service->hex_to_rgba( $settings['badge_color_new'], 0.1 );
			$vars['--rp-badge-new-text'] = $this->design_service->adjust_color_brightness( $settings['badge_color_new'], -30 );
		}

		// Badge: Remote.
		$vars['--rp-badge-remote'] = $settings['badge_color_remote'];
		if ( $is_solid ) {
			$vars['--rp-badge-remote-bg']   = $settings['badge_color_remote'];
			$vars['--rp-badge-remote-text'] = '#ffffff';
		} else {
			$vars['--rp-badge-remote-bg']   = $this->design_service->hex_to_rgba( $settings['badge_color_remote'], 0.1 );
			$vars['--rp-badge-remote-text'] = $this->design_service->adjust_color_brightness( $settings['badge_color_remote'], -30 );
		}

		// Badge: Category.
		$vars['--rp-badge-category'] = $settings['badge_color_category'];
		if ( $is_solid ) {
			$vars['--rp-badge-category-bg']   = $settings['badge_color_category'];
			$vars['--rp-badge-category-text'] = '#ffffff';
		} else {
			$vars['--rp-badge-category-bg']   = $this->design_service->hex_to_rgba( $settings['badge_color_category'], 0.1 );
			$vars['--rp-badge-category-text'] = $this->design_service->adjust_color_brightness( $settings['badge_color_category'], -30 );
		}

		// Badge: Salary.
		$vars['--rp-badge-salary'] = $settings['badge_color_salary'];
		if ( $is_solid ) {
			$vars['--rp-badge-salary-bg']   = $settings['badge_color_salary'];
			$vars['--rp-badge-salary-text'] = '#ffffff';
		} else {
			$vars['--rp-badge-salary-bg']   = $this->design_service->hex_to_rgba( $settings['badge_color_salary'], 0.1 );
			$vars['--rp-badge-salary-text'] = $this->design_service->adjust_color_brightness( $settings['badge_color_salary'], -30 );
		}

		return $vars;
	}

	/**
	 * KI-Button-Variablen generieren
	 *
	 * @param array  $settings Design-Einstellungen.
	 * @param string $primary  Primärfarbe.
	 * @return array CSS-Variablen.
	 */
	private function generate_ai_button_variables( array $settings, string $primary ): array {
		$vars = [];

		// Radius.
		$vars['--rp-ai-btn-radius'] = $settings['ai_button_radius'] . 'px';

		// Stil-abhängige Werte.
		switch ( $settings['ai_button_style'] ) {
			case 'theme':
				// Erbt Primärfarbe.
				$vars['--rp-ai-btn-bg']   = $primary;
				$vars['--rp-ai-btn-text'] = '#ffffff';
				break;

			case 'preset':
				// Preset-Styles.
				$presets = $this->get_ai_button_presets();
				$preset  = $presets[ $settings['ai_button_preset'] ] ?? $presets['gradient'];

				$vars['--rp-ai-btn-bg']     = $preset['bg'];
				$vars['--rp-ai-btn-text']   = $preset['text'];
				$vars['--rp-ai-btn-shadow'] = $preset['shadow'] ?? 'none';
				if ( isset( $preset['border'] ) ) {
					$vars['--rp-ai-btn-border'] = $preset['border'];
				}
				break;

			case 'manual':
				// Eigene Farben.
				if ( $settings['ai_button_use_gradient'] ) {
					$vars['--rp-ai-btn-bg'] = sprintf(
						'linear-gradient(135deg, %s, %s)',
						$settings['ai_button_color_1'],
						$settings['ai_button_color_2']
					);
				} else {
					$vars['--rp-ai-btn-bg'] = $settings['ai_button_color_1'];
				}
				$vars['--rp-ai-btn-text'] = $settings['ai_button_text_color'];
				break;
		}

		return $vars;
	}

	/**
	 * KI-Button Presets
	 *
	 * @return array Preset-Definitionen.
	 */
	private function get_ai_button_presets(): array {
		return [
			'gradient' => [
				'bg'     => 'linear-gradient(135deg, #8b5cf6, #ec4899)',
				'text'   => '#ffffff',
				'shadow' => '0 4px 15px rgba(139, 92, 246, 0.3)',
			],
			'outline'  => [
				'bg'     => 'transparent',
				'text'   => '#8b5cf6',
				'border' => '2px solid #8b5cf6',
			],
			'minimal'  => [
				'bg'   => '#f3f4f6',
				'text' => '#374151',
			],
			'glow'     => [
				'bg'     => '#8b5cf6',
				'text'   => '#ffffff',
				'shadow' => '0 0 20px rgba(139, 92, 246, 0.5)',
			],
			'soft'     => [
				'bg'   => '#ede9fe',
				'text' => '#7c3aed',
			],
		];
	}

	/**
	 * Custom Button CSS generieren (überschreibt Theme-Buttons)
	 *
	 * Wird NUR ausgegeben wenn button_use_custom_design aktiviert ist.
	 *
	 * @param array $settings Design-Einstellungen.
	 * @return string CSS.
	 */
	private function generate_custom_button_css( array $settings ): string {
		$css = "\n/* Custom Button Design (überschreibt Theme) */\n";

		// Hauptbutton-Styles.
		$css .= ".rp-plugin .wp-element-button,\n";
		$css .= ".rp-plugin a.wp-element-button {\n";
		$css .= "  background-color: var(--rp-btn-bg) !important;\n";
		$css .= "  color: var(--rp-btn-text) !important;\n";
		$css .= "  border-radius: var(--rp-btn-radius) !important;\n";
		$css .= "  padding: var(--rp-btn-padding) !important;\n";
		$css .= "  border: var(--rp-btn-border) !important;\n";
		$css .= "  box-shadow: var(--rp-btn-shadow) !important;\n";
		$css .= "}\n";

		// Hover-Styles.
		$css .= ".rp-plugin .wp-element-button:hover,\n";
		$css .= ".rp-plugin a.wp-element-button:hover {\n";
		$css .= "  background-color: var(--rp-btn-bg-hover) !important;\n";
		$css .= "  color: var(--rp-btn-text-hover) !important;\n";
		$css .= "  box-shadow: var(--rp-btn-shadow-hover) !important;\n";
		$css .= "}\n";

		return $css;
	}

	/**
	 * Zusätzliche CSS-Regeln generieren
	 *
	 * Für Hover-Effekte, Link-Decoration etc.
	 *
	 * @param array $settings Design-Einstellungen.
	 * @return string CSS-Regeln.
	 */
	private function generate_additional_rules( array $settings ): string {
		$css = '';

		// Card Hover-Effekte.
		switch ( $settings['card_hover_effect'] ) {
			case 'lift':
				$css .= ".rp-plugin .rp-card:hover {\n";
				$css .= "  transform: translateY(-4px);\n";
				$css .= "  box-shadow: var(--rp-shadow-lg);\n";
				$css .= "}\n";
				break;

			case 'glow':
				$css .= ".rp-plugin .rp-card:hover {\n";
				$css .= "  box-shadow: 0 0 20px " . $this->design_service->hex_to_rgba( $this->design_service->get_primary_color(), 0.3 ) . ";\n";
				$css .= "}\n";
				break;

			case 'border':
				$css .= ".rp-plugin .rp-card:hover {\n";
				$css .= "  border-color: var(--rp-color-primary);\n";
				$css .= "}\n";
				break;
		}

		// Link Hover-Decoration.
		if ( 'hover' === $settings['link_decoration'] ) {
			$css .= ".rp-plugin .rp-job-content a:hover,\n";
			$css .= ".rp-plugin .rp-prose a:hover {\n";
			$css .= "  text-decoration: underline;\n";
			$css .= "}\n";
		}

		// Typografie für Stellenausschreibung.
		$css .= ".rp-plugin .rp-job-content h1,\n";
		$css .= ".rp-plugin .rp-job-content h2,\n";
		$css .= ".rp-plugin .rp-job-content h3 {\n";
		$css .= "  margin-top: var(--rp-heading-margin-top, 1.5em);\n";
		$css .= "  margin-bottom: var(--rp-heading-margin-bottom, 0.5em);\n";
		$css .= "  line-height: var(--rp-line-height-heading, 1.2);\n";
		$css .= "}\n";

		$css .= ".rp-plugin .rp-job-content p {\n";
		$css .= "  margin-bottom: var(--rp-paragraph-spacing, 1em);\n";
		$css .= "  line-height: var(--rp-line-height-body, 1.6);\n";
		$css .= "}\n";

		$css .= ".rp-plugin .rp-job-content a {\n";
		$css .= "  color: var(--rp-link-color);\n";
		$css .= "  text-decoration: var(--rp-link-decoration);\n";
		$css .= "}\n";

		return $css;
	}
}

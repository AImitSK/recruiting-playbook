<?php
/**
 * Design Service - Design & Branding Einstellungen verwalten
 *
 * Verwaltet alle Design-Einstellungen für Pro-User.
 * Die CSS-Variablen werden vom CssGeneratorService generiert.
 *
 * @package RecruitingPlaybook
 * @see docs/technical/design-branding-specification-v2.md
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service für Design & Branding Einstellungen
 */
class DesignService {

	/**
	 * Option-Name in der Datenbank
	 */
	private const OPTION_NAME = 'rp_design_settings';

	/**
	 * Gecachte Settings für Performance
	 *
	 * @var array|null
	 */
	private static ?array $cached_settings = null;

	/**
	 * Alle Design-Einstellungen abrufen
	 *
	 * Gibt gespeicherte Einstellungen zusammen mit Defaults zurück.
	 *
	 * @return array Merged Settings mit Defaults.
	 */
	public function get_design_settings(): array {
		if ( null !== self::$cached_settings ) {
			return self::$cached_settings;
		}

		$saved    = get_option( self::OPTION_NAME, [] );
		$defaults = $this->get_defaults();

		self::$cached_settings = wp_parse_args( $saved, $defaults );

		return self::$cached_settings;
	}

	/**
	 * Einzelne Einstellung abrufen
	 *
	 * @param string $key     Setting-Key.
	 * @param mixed  $default Fallback wenn nicht gesetzt (überschreibt Schema-Default).
	 * @return mixed Wert der Einstellung.
	 */
	public function get_setting( string $key, mixed $default = null ): mixed {
		$settings = $this->get_design_settings();

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		$defaults = $this->get_defaults();
		return $defaults[ $key ] ?? null;
	}

	/**
	 * Design-Einstellungen speichern
	 *
	 * Validiert und speichert nur geänderte Werte.
	 *
	 * @param array $new_settings Neue Einstellungen (partielle Updates möglich).
	 * @return bool Erfolg.
	 */
	public function save_design_settings( array $new_settings ): bool {
		$current   = get_option( self::OPTION_NAME, [] );
		$validated = $this->validate_settings( $new_settings );

		// Nur geänderte Werte mergen.
		$merged = array_merge( $current, $validated );

		// Cache invalidieren.
		self::$cached_settings = null;

		// update_option gibt false zurück wenn Wert unverändert - das ist kein Fehler.
		$result = update_option( self::OPTION_NAME, $merged );

		// Prüfen ob der Wert jetzt korrekt gespeichert ist.
		$saved = get_option( self::OPTION_NAME, [] );

		return $saved === $merged;
	}

	/**
	 * Alle Einstellungen auf Defaults zurücksetzen
	 *
	 * @return bool Erfolg.
	 */
	public function reset_to_defaults(): bool {
		self::$cached_settings = null;
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Primärfarbe ermitteln (Theme oder Custom)
	 *
	 * Berücksichtigt use_theme_colors Setting.
	 *
	 * @return string Hex-Farbe (#rrggbb).
	 */
	public function get_primary_color(): string {
		$settings = $this->get_design_settings();

		if ( $settings['use_theme_colors'] ) {
			return $this->get_theme_primary_color();
		}

		return $settings['primary_color'];
	}

	/**
	 * Primärfarbe vom Theme lesen
	 *
	 * Fallback-Kette:
	 * 1. Global Settings Farbpalette (theme.json) - primary/accent Slugs
	 * 2. WordPress Global Styles - Button-Hintergrund
	 * 3. Bekannte Theme Mod Namen
	 * 4. Default #2563eb
	 *
	 * @return string Hex-Farbe.
	 */
	public function get_theme_primary_color(): string {
		// 1. Global Settings Farbpalette (theme.json) - PRIORITÄT.
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$global_settings = wp_get_global_settings();
			$palette         = $global_settings['color']['palette']['theme'] ?? [];

			// Suche nach bekannten Primärfarben-Slugs (Reihenfolge = Priorität).
			$primary_slugs = [ 'primary', 'accent', 'contrast', 'secondary' ];

			foreach ( $primary_slugs as $slug ) {
				foreach ( $palette as $color_def ) {
					if ( isset( $color_def['slug'] ) && $slug === $color_def['slug'] ) {
						if ( $this->is_valid_hex_color( $color_def['color'] ) ) {
							return $color_def['color'];
						}
					}
				}
			}
		}

		// 2. WordPress Global Styles - Button-Hintergrundfarbe.
		if ( function_exists( 'wp_get_global_styles' ) ) {
			$global_styles = wp_get_global_styles();

			if ( ! empty( $global_styles['elements']['button']['color']['background'] ) ) {
				$color = $this->resolve_css_var( $global_styles['elements']['button']['color']['background'] );
				if ( $this->is_valid_hex_color( $color ) ) {
					return $color;
				}
			}
		}

		// 3. Bekannte Theme Mod Namen (Classic Themes).
		$theme_mod_names = [
			'primary_color',
			'accent_color',
			'link_color',
			'theme_color',
			'brand_color',
			'main_color',
		];

		foreach ( $theme_mod_names as $mod_name ) {
			$color = get_theme_mod( $mod_name, '' );
			if ( $this->is_valid_hex_color( $color ) ) {
				return $color;
			}
		}

		// 4. Fallback.
		return '#2563eb';
	}

	/**
	 * CSS-Variable zu Hex-Wert auflösen
	 *
	 * Wandelt z.B. "var(--wp--preset--color--primary)" in den Hex-Wert um.
	 *
	 * @param string $value CSS-Wert (kann var() oder direkt Hex sein).
	 * @return string Hex-Farbe oder ursprünglicher Wert.
	 */
	private function resolve_css_var( string $value ): string {
		// Direkte Hex-Farbe.
		if ( $this->is_valid_hex_color( $value ) ) {
			return $value;
		}

		// CSS Variable: var(--wp--preset--color--{slug}).
		if ( preg_match( '/var\(--wp--preset--color--([a-z0-9-]+)\)/', $value, $matches ) ) {
			$slug = $matches[1];

			if ( function_exists( 'wp_get_global_settings' ) ) {
				$global_settings = wp_get_global_settings();
				$palette         = $global_settings['color']['palette']['theme'] ?? [];

				foreach ( $palette as $color_def ) {
					if ( isset( $color_def['slug'] ) && $slug === $color_def['slug'] ) {
						return $color_def['color'];
					}
				}
			}
		}

		return $value;
	}

	/**
	 * Logo-URL ermitteln (Theme oder Custom)
	 *
	 * @return string|null Logo-URL oder null wenn keines gesetzt.
	 */
	public function get_logo_url(): ?string {
		$settings = $this->get_design_settings();

		if ( $settings['use_theme_logo'] ) {
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			if ( $custom_logo_id ) {
				$logo = wp_get_attachment_image_src( $custom_logo_id, 'full' );
				return $logo ? $logo[0] : null;
			}
			return null;
		}

		if ( $settings['custom_logo_id'] ) {
			$logo = wp_get_attachment_image_src( $settings['custom_logo_id'], 'full' );
			return $logo ? $logo[0] : null;
		}

		return null;
	}

	/**
	 * Default-Werte für alle Settings
	 *
	 * Struktur entspricht der Spezifikation Abschnitte 3.1-3.6.
	 *
	 * @return array Alle Defaults.
	 */
	public function get_defaults(): array {
		return [
			// ============================================
			// Tab: Branding (Abschnitt 3.1)
			// ============================================

			// Card: Farben.
			'use_theme_colors'         => true,
			'primary_color'            => '#2563eb',

			// Card: Logo.
			'use_theme_logo'           => true,
			'custom_logo_id'           => null,
			'logo_in_signature'        => false,
			'signature_logo_position'  => 'top', // top|bottom|left
			'signature_logo_max_height' => 60,    // 30-120px

			// Card: White-Label.
			'hide_branding'            => false,
			'hide_email_branding'      => false,

			// ============================================
			// Tab: Typografie (Abschnitt 3.2)
			// ============================================

			// Card: Schriftgrößen (rem).
			'font_size_h1'             => 2.25,   // 1.5-4 rem
			'font_size_h2'             => 1.875,  // 1.25-3 rem
			'font_size_h3'             => 1.5,    // 1-2.5 rem
			'font_size_body'           => 1.0,    // 0.875-1.25 rem
			'font_size_small'          => 0.875,  // 0.625-1 rem

			// Card: Zeilenabstand.
			'line_height_heading'      => 1.2,    // 1.0-1.5
			'line_height_body'         => 1.6,    // 1.3-2.0

			// Card: Abstände (em).
			'heading_margin_top'       => 1.5,    // 0.5-3 em
			'heading_margin_bottom'    => 0.5,    // 0.25-1.5 em
			'paragraph_spacing'        => 1.0,    // 0.5-2 em

			// Card: Links.
			'link_use_primary'         => true,
			'link_color'               => '#2563eb',
			'link_decoration'          => 'underline', // none|underline|hover

			// ============================================
			// Tab: Cards (Abschnitt 3.3)
			// ============================================

			// Card 1: Layout-Preset.
			'card_layout_preset'       => 'standard', // compact|standard|spacious

			// Card 2: Erscheinungsbild.
			'card_border_radius'       => 8,      // 0-24 px
			'card_shadow'              => 'light', // none|light|medium|strong
			'card_border_show'         => true,
			'card_border_color'        => '#e5e7eb',
			'card_background'          => '#ffffff',
			'card_hover_effect'        => 'lift', // none|lift|glow|border

			// ============================================
			// Tab: Buttons (Abschnitt 3.4)
			// ============================================

			// Card 1: Farben.
			'override_button_colors'   => false,
			'button_bg_color'          => '#2563eb',
			'button_bg_color_hover'    => '#1d4ed8',
			'button_text_color'        => '#ffffff',
			'button_text_color_hover'  => '#ffffff',

			// Card 2: Form & Effekte.
			'button_size'              => 'medium', // small|medium|large
			'button_border_radius'     => 6,        // 0-50 px
			'button_border_show'       => false,
			'button_border_color'      => '#2563eb',
			'button_border_width'      => 1,        // 1-5 px
			'button_shadow'            => 'none',   // none|light|medium|strong
			'button_shadow_hover'      => 'light',

			// ============================================
			// Tab: Job-Liste (Abschnitt 3.5)
			// ============================================

			// Card 1: Layout & Anzeige.
			'job_list_layout'          => 'grid',  // grid|list
			'job_list_columns'         => 3,       // 2|3|4
			'show_badges'              => true,
			'show_salary'              => true,
			'show_location'            => true,
			'show_employment_type'     => true,
			'show_deadline'            => false,

			// Card 2: Badge-Farben.
			'badge_style'              => 'light', // light|solid
			'badge_color_new'          => '#22c55e',
			'badge_color_remote'       => '#8b5cf6',
			'badge_color_category'     => '#6b7280',
			'badge_color_salary'       => '#2563eb',

			// ============================================
			// Tab: KI-Buttons (Abschnitt 3.6)
			// ============================================

			// Card: Globaler KI-Button Stil.
			'ai_button_style'          => 'preset', // theme|preset|manual

			// Card: Preset-Auswahl.
			'ai_button_preset'         => 'gradient', // gradient|outline|minimal|glow|soft

			// Card: Manuelle Farben.
			'ai_button_use_gradient'   => true,
			'ai_button_color_1'        => '#8b5cf6',
			'ai_button_color_2'        => '#ec4899',
			'ai_button_text_color'     => '#ffffff',
			'ai_button_radius'         => 8,        // 0-24 px

			// Card: Button-Texte (KI-Matching).
			'ai_match_button_text'     => 'KI-Matching starten',
			'ai_match_button_icon'     => 'sparkles', // sparkles|checkmark|star|lightning|target|user

			// Zukünftige KI-Buttons (reserviert).
			'ai_button_2_text'         => '',
			'ai_button_2_icon'         => 'sparkles',
			'ai_button_3_text'         => '',
			'ai_button_3_icon'         => 'sparkles',
		];
	}

	/**
	 * Schema für Validierung und UI-Generierung
	 *
	 * @return array Schema-Definition.
	 */
	public function get_schema(): array {
		return [
			// Branding - Farben.
			'use_theme_colors'         => [ 'type' => 'boolean' ],
			'primary_color'            => [ 'type' => 'color' ],

			// Branding - Logo.
			'use_theme_logo'           => [ 'type' => 'boolean' ],
			'custom_logo_id'           => [ 'type' => 'integer', 'nullable' => true ],
			'logo_in_signature'        => [ 'type' => 'boolean' ],
			'signature_logo_position'  => [ 'type' => 'select', 'options' => [ 'top', 'bottom', 'left' ] ],
			'signature_logo_max_height' => [ 'type' => 'slider', 'min' => 30, 'max' => 120 ],

			// Branding - White-Label.
			'hide_branding'            => [ 'type' => 'boolean' ],
			'hide_email_branding'      => [ 'type' => 'boolean' ],

			// Typografie - Schriftgrößen.
			'font_size_h1'             => [ 'type' => 'slider', 'min' => 1.5, 'max' => 4, 'step' => 0.125 ],
			'font_size_h2'             => [ 'type' => 'slider', 'min' => 1.25, 'max' => 3, 'step' => 0.125 ],
			'font_size_h3'             => [ 'type' => 'slider', 'min' => 1, 'max' => 2.5, 'step' => 0.125 ],
			'font_size_body'           => [ 'type' => 'slider', 'min' => 0.875, 'max' => 1.25, 'step' => 0.0625 ],
			'font_size_small'          => [ 'type' => 'slider', 'min' => 0.625, 'max' => 1, 'step' => 0.0625 ],

			// Typografie - Zeilenabstand.
			'line_height_heading'      => [ 'type' => 'slider', 'min' => 1.0, 'max' => 1.5, 'step' => 0.1 ],
			'line_height_body'         => [ 'type' => 'slider', 'min' => 1.3, 'max' => 2.0, 'step' => 0.1 ],

			// Typografie - Abstände.
			'heading_margin_top'       => [ 'type' => 'slider', 'min' => 0.5, 'max' => 3, 'step' => 0.25 ],
			'heading_margin_bottom'    => [ 'type' => 'slider', 'min' => 0.25, 'max' => 1.5, 'step' => 0.25 ],
			'paragraph_spacing'        => [ 'type' => 'slider', 'min' => 0.5, 'max' => 2, 'step' => 0.25 ],

			// Typografie - Links.
			'link_use_primary'         => [ 'type' => 'boolean' ],
			'link_color'               => [ 'type' => 'color' ],
			'link_decoration'          => [ 'type' => 'select', 'options' => [ 'none', 'underline', 'hover' ] ],

			// Cards - Layout.
			'card_layout_preset'       => [ 'type' => 'select', 'options' => [ 'compact', 'standard', 'spacious' ] ],

			// Cards - Erscheinungsbild.
			'card_border_radius'       => [ 'type' => 'slider', 'min' => 0, 'max' => 24 ],
			'card_shadow'              => [ 'type' => 'select', 'options' => [ 'none', 'light', 'medium', 'strong' ] ],
			'card_border_show'         => [ 'type' => 'boolean' ],
			'card_border_color'        => [ 'type' => 'color' ],
			'card_background'          => [ 'type' => 'color' ],
			'card_hover_effect'        => [ 'type' => 'select', 'options' => [ 'none', 'lift', 'glow', 'border' ] ],

			// Buttons - Farben.
			'override_button_colors'   => [ 'type' => 'boolean' ],
			'button_bg_color'          => [ 'type' => 'color' ],
			'button_bg_color_hover'    => [ 'type' => 'color' ],
			'button_text_color'        => [ 'type' => 'color' ],
			'button_text_color_hover'  => [ 'type' => 'color' ],

			// Buttons - Form.
			'button_size'              => [ 'type' => 'select', 'options' => [ 'small', 'medium', 'large' ] ],
			'button_border_radius'     => [ 'type' => 'slider', 'min' => 0, 'max' => 50 ],
			'button_border_show'       => [ 'type' => 'boolean' ],
			'button_border_color'      => [ 'type' => 'color' ],
			'button_border_width'      => [ 'type' => 'slider', 'min' => 1, 'max' => 5 ],
			'button_shadow'            => [ 'type' => 'select', 'options' => [ 'none', 'light', 'medium', 'strong' ] ],
			'button_shadow_hover'      => [ 'type' => 'select', 'options' => [ 'none', 'light', 'medium', 'strong' ] ],

			// Job-Liste.
			'job_list_layout'          => [ 'type' => 'select', 'options' => [ 'grid', 'list' ] ],
			'job_list_columns'         => [ 'type' => 'select', 'options' => [ 2, 3, 4 ] ],
			'show_badges'              => [ 'type' => 'boolean' ],
			'show_salary'              => [ 'type' => 'boolean' ],
			'show_location'            => [ 'type' => 'boolean' ],
			'show_employment_type'     => [ 'type' => 'boolean' ],
			'show_deadline'            => [ 'type' => 'boolean' ],

			// Badge-Farben.
			'badge_style'              => [ 'type' => 'select', 'options' => [ 'light', 'solid' ] ],
			'badge_color_new'          => [ 'type' => 'color' ],
			'badge_color_remote'       => [ 'type' => 'color' ],
			'badge_color_category'     => [ 'type' => 'color' ],
			'badge_color_salary'       => [ 'type' => 'color' ],

			// KI-Buttons.
			'ai_button_style'          => [ 'type' => 'select', 'options' => [ 'theme', 'preset', 'manual' ] ],
			'ai_button_preset'         => [ 'type' => 'select', 'options' => [ 'gradient', 'outline', 'minimal', 'glow', 'soft' ] ],
			'ai_button_use_gradient'   => [ 'type' => 'boolean' ],
			'ai_button_color_1'        => [ 'type' => 'color' ],
			'ai_button_color_2'        => [ 'type' => 'color' ],
			'ai_button_text_color'     => [ 'type' => 'color' ],
			'ai_button_radius'         => [ 'type' => 'slider', 'min' => 0, 'max' => 24 ],

			// KI-Button Texte.
			'ai_match_button_text'     => [ 'type' => 'string', 'max' => 50 ],
			'ai_match_button_icon'     => [ 'type' => 'select', 'options' => [ 'sparkles', 'checkmark', 'star', 'lightning', 'target', 'user' ] ],
			'ai_button_2_text'         => [ 'type' => 'string', 'max' => 50 ],
			'ai_button_2_icon'         => [ 'type' => 'select', 'options' => [ 'sparkles', 'checkmark', 'star', 'lightning', 'target', 'user' ] ],
			'ai_button_3_text'         => [ 'type' => 'string', 'max' => 50 ],
			'ai_button_3_icon'         => [ 'type' => 'select', 'options' => [ 'sparkles', 'checkmark', 'star', 'lightning', 'target', 'user' ] ],
		];
	}

	/**
	 * Settings validieren
	 *
	 * @param array $settings Zu validierende Settings.
	 * @return array Validierte Settings.
	 */
	private function validate_settings( array $settings ): array {
		$schema    = $this->get_schema();
		$defaults  = $this->get_defaults();
		$validated = [];

		foreach ( $settings as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) ) {
				continue; // Unbekannte Keys ignorieren.
			}

			$field = $schema[ $key ];
			$valid = $this->validate_field( $value, $field, $defaults[ $key ] ?? null );

			if ( null !== $valid ) {
				$validated[ $key ] = $valid;
			}
		}

		return $validated;
	}

	/**
	 * Einzelnes Feld validieren
	 *
	 * @param mixed      $value   Zu validierender Wert.
	 * @param array      $field   Feld-Schema.
	 * @param mixed|null $default Default-Wert.
	 * @return mixed|null Validierter Wert oder null bei Fehler.
	 */
	private function validate_field( mixed $value, array $field, mixed $default ): mixed {
		switch ( $field['type'] ) {
			case 'boolean':
				return (bool) $value;

			case 'color':
				return $this->is_valid_hex_color( $value ) ? $value : $default;

			case 'slider':
				$num = is_numeric( $value ) ? (float) $value : $default;
				$min = $field['min'] ?? PHP_FLOAT_MIN;
				$max = $field['max'] ?? PHP_FLOAT_MAX;
				return max( $min, min( $max, $num ) );

			case 'select':
				return in_array( $value, $field['options'], true ) ? $value : $default;

			case 'integer':
				if ( ( $field['nullable'] ?? false ) && ( null === $value || '' === $value ) ) {
					return null;
				}
				return is_numeric( $value ) ? (int) $value : $default;

			case 'string':
				$str = sanitize_text_field( (string) $value );
				$max = $field['max'] ?? 255;
				return mb_substr( $str, 0, $max );

			default:
				return $default;
		}
	}

	/**
	 * Hex-Farbwert validieren
	 *
	 * @param mixed $color Zu prüfender Wert.
	 * @return bool Ob gültiger Hex-Wert (#rgb oder #rrggbb).
	 */
	private function is_valid_hex_color( mixed $color ): bool {
		if ( ! is_string( $color ) ) {
			return false;
		}

		return 1 === preg_match( '/^#([0-9A-Fa-f]{3}){1,2}$/', $color );
	}

	/**
	 * Farbe aufhellen (für Hover-States)
	 *
	 * @param string $hex    Hex-Farbe.
	 * @param int    $percent Prozent (positiv = heller, negativ = dunkler).
	 * @return string Modifizierte Hex-Farbe.
	 */
	public function adjust_color_brightness( string $hex, int $percent ): string {
		$hex = ltrim( $hex, '#' );

		// Konvertiere 3-stellig zu 6-stellig.
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$rgb = [
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		];

		foreach ( $rgb as &$color ) {
			$adjustment = $color * $percent / 100;
			$color      = max( 0, min( 255, round( $color + $adjustment ) ) );
		}

		return sprintf( '#%02x%02x%02x', ...$rgb );
	}

	/**
	 * Farbe mit Opacity als rgba()
	 *
	 * @param string $hex     Hex-Farbe.
	 * @param float  $opacity Opacity (0-1).
	 * @return string rgba() Wert.
	 */
	public function hex_to_rgba( string $hex, float $opacity = 1.0 ): string {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		return "rgba({$r}, {$g}, {$b}, {$opacity})";
	}

	/**
	 * Hex-Farbe zu HSL konvertieren
	 *
	 * Benötigt für Tailwind-Kompatibilität.
	 *
	 * @param string $hex Hex-Farbe.
	 * @return array [h, s, l] als Prozent/Grad.
	 */
	public function hex_to_hsl( string $hex ): array {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
		$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
		$b = hexdec( substr( $hex, 4, 2 ) ) / 255;

		$max   = max( $r, $g, $b );
		$min   = min( $r, $g, $b );
		$delta = $max - $min;

		$l = ( $max + $min ) / 2;

		if ( 0 == $delta ) {
			$h = 0;
			$s = 0;
		} else {
			$s = $delta / ( 1 - abs( 2 * $l - 1 ) );

			switch ( $max ) {
				case $r:
					$h = 60 * fmod( ( ( $g - $b ) / $delta ), 6 );
					if ( $b > $g ) {
						$h += 360;
					}
					break;
				case $g:
					$h = 60 * ( ( $b - $r ) / $delta + 2 );
					break;
				case $b:
					$h = 60 * ( ( $r - $g ) / $delta + 4 );
					break;
			}
		}

		return [
			'h' => round( $h ),
			's' => round( $s * 100 ),
			'l' => round( $l * 100 ),
		];
	}
}

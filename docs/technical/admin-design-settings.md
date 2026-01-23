# Admin-Seite: Design & Branding (Pro)

## Übersicht

Die Design-Einstellungen erlauben Pro-Nutzern, das Erscheinungsbild des Plugins anzupassen, ohne CSS schreiben zu müssen.

**Menüpfad:** Recruiting → Einstellungen → Design

**Verfügbarkeit:**
| Feature | FREE | PRO |
|---------|:----:|:---:|
| Theme-Farben/Schriften erben | ✅ | ✅ |
| Alle anderen Design-Optionen | ❌ | ✅ |

---

## UI-Mockup

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Einstellungen › Design                                    [Speichern]  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─ BRANDING ──────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  Firmenlogo                                                          ││
│  │  ☑ Theme-Logo verwenden                                              ││
│  │  ○ Eigenes Logo hochladen  [Bild auswählen]                         ││
│  │                                                                      ││
│  │  Primärfarbe                                                         ││
│  │  ☑ Theme-Farbe verwenden                                             ││
│  │  ○ Eigene Farbe  [#2563eb] [■]                                      ││
│  │                                                                      ││
│  │  ☑ "Powered by Recruiting Playbook" ausblenden                       ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─ TYPOGRAFIE ────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  Schriftart                                                          ││
│  │  ☑ Vom Theme erben                                                   ││
│  │  ○ Eigene Schriftart  [System UI ▼]                                 ││
│  │                                                                      ││
│  │  Überschriften                                                       ││
│  │  ┌────────┬────────┬────────┬────────┬────────┬────────┐            ││
│  │  │   H1   │   H2   │   H3   │   H4   │   H5   │   H6   │            ││
│  │  ├────────┼────────┼────────┼────────┼────────┼────────┤            ││
│  │  │2.5 rem │ 2 rem  │1.5 rem │1.25rem │1.1 rem │ 1 rem  │            ││
│  │  └────────┴────────┴────────┴────────┴────────┴────────┘            ││
│  │                                                                      ││
│  │  Fließtext          [1 rem    ]                                      ││
│  │  Kleine Texte       [0.875 rem]                                      ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─ CARDS & CONTAINER ─────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  Eckenradius                                                         ││
│  │  [━━━━━━●━━━━━━━━━━] 8px                          (0px – 24px)      ││
│  │                                                                      ││
│  │  Schattenstärke                                                      ││
│  │  ○ Keine  ○ Leicht  ● Mittel  ○ Stark  ○ Extra stark               ││
│  │                                                                      ││
│  │  ☑ Rahmen anzeigen                                                   ││
│  │  Rahmenfarbe  [#e2e8f0] [■]                                         ││
│  │                                                                      ││
│  │  Vorschau:                                                           ││
│  │  ┌─────────────────────────────────────────┐                        ││
│  │  │  ┌─────────────────────────────────┐    │                        ││
│  │  │  │                                 │    │                        ││
│  │  │  │    Beispiel Job-Card            │    │                        ││
│  │  │  │    Standort · Vollzeit          │    │                        ││
│  │  │  │                                 │    │                        ││
│  │  │  └─────────────────────────────────┘    │                        ││
│  │  └─────────────────────────────────────────┘                        ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─ BUTTONS ───────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  ○ Theme-Button verwenden (WordPress .wp-element-button)            ││
│  │  ● Eigenes Button-Design                                             ││
│  │                                                                      ││
│  │  ┌─ Button-Einstellungen ───────────────────────────────────────┐   ││
│  │  │                                                               │   ││
│  │  │            NORMAL                    HOVER                    │   ││
│  │  │  ┌─────────────────────┐  ┌─────────────────────┐            │   ││
│  │  │  │ Hintergrund [#2563eb]│  │ Hintergrund [#1d4ed8]│           │   ││
│  │  │  │ Textfarbe   [#ffffff]│  │ Textfarbe   [#ffffff]│           │   ││
│  │  │  │ Rahmen      ☐        │  │ Rahmen      ☐        │           │   ││
│  │  │  │ Rahmenfarbe [#2563eb]│  │ Rahmenfarbe [#1d4ed8]│           │   ││
│  │  │  │ Schatten    ○ Keine  │  │ Schatten    ○ Keine  │           │   ││
│  │  │  │             ● Leicht │  │             ○ Leicht │           │   ││
│  │  │  │             ○ Mittel │  │             ● Mittel │           │   ││
│  │  │  │             ○ Stark  │  │             ○ Stark  │           │   ││
│  │  │  └─────────────────────┘  └─────────────────────┘            │   ││
│  │  │                                                               │   ││
│  │  │  Eckenradius  [━━━━●━━━━━━━━━━━━] 6px                        │   ││
│  │  │                                                               │   ││
│  │  └───────────────────────────────────────────────────────────────┘   ││
│  │                                                                      ││
│  │  Vorschau:  [    Jetzt bewerben    ]  →  [    Jetzt bewerben    ]   ││
│  │                    Normal                       Hover                ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─ JOB-LISTE ─────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │  Spalten (Desktop)     ○ 2 Spalten  ● 3 Spalten  ○ 4 Spalten       ││
│  │                                                                      ││
│  │  Anzeige-Optionen                                                    ││
│  │  ☑ Badges anzeigen (Neu, Dringend, etc.)                            ││
│  │  ☑ Gehalt/Stundenlohn anzeigen                                       ││
│  │  ☑ Standort anzeigen                                                 ││
│  │                                                                      ││
│  └──────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│                                        [Auf Standard zurücksetzen]       │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Datenstruktur

### Option: `rp_settings['design']`

```php
'design' => [
    // ═══════════════════════════════════════════════════════════════
    // BRANDING
    // ═══════════════════════════════════════════════════════════════
    'use_theme_color'      => true,           // bool: Theme-Primärfarbe nutzen
    'primary_color'        => '#2563eb',      // string: Custom Primärfarbe (hex)
    'use_theme_logo'       => true,           // bool: Theme-Logo nutzen
    'custom_logo_id'       => 0,              // int: Attachment ID für Custom Logo
    'hide_branding'        => false,          // bool: "Powered by" verstecken

    // ═══════════════════════════════════════════════════════════════
    // TYPOGRAFIE
    // ═══════════════════════════════════════════════════════════════
    'use_theme_font'       => true,           // bool: Theme-Schriftart erben
    'custom_font_family'   => 'system-ui, sans-serif', // string: CSS font-family
    'font_size_h1'         => '2.5rem',       // string: H1 Größe
    'font_size_h2'         => '2rem',         // string: H2 Größe
    'font_size_h3'         => '1.5rem',       // string: H3 Größe
    'font_size_h4'         => '1.25rem',      // string: H4 Größe
    'font_size_h5'         => '1.1rem',       // string: H5 Größe
    'font_size_h6'         => '1rem',         // string: H6 Größe
    'font_size_body'       => '1rem',         // string: Fließtext Größe
    'font_size_small'      => '0.875rem',     // string: Kleine Texte Größe

    // ═══════════════════════════════════════════════════════════════
    // CARDS & CONTAINER
    // ═══════════════════════════════════════════════════════════════
    'card_border_radius'   => '8px',          // string: 0px – 24px
    'card_shadow'          => 'medium',       // enum: none|light|medium|strong|extra
    'card_border_show'     => true,           // bool: Rahmen anzeigen
    'card_border_color'    => '#e2e8f0',      // string: Rahmenfarbe (hex)

    // ═══════════════════════════════════════════════════════════════
    // BUTTONS
    // ═══════════════════════════════════════════════════════════════
    'use_theme_button'     => true,           // bool: WordPress .wp-element-button
    'button' => [
        'bg_color'           => '#2563eb',    // string: Hintergrund Normal
        'bg_color_hover'     => '#1d4ed8',    // string: Hintergrund Hover
        'text_color'         => '#ffffff',    // string: Text Normal
        'text_color_hover'   => '#ffffff',    // string: Text Hover
        'border_show'        => false,        // bool: Rahmen anzeigen
        'border_color'       => '#2563eb',    // string: Rahmenfarbe Normal
        'border_color_hover' => '#1d4ed8',    // string: Rahmenfarbe Hover
        'shadow'             => 'light',      // enum: none|light|medium|strong
        'shadow_hover'       => 'medium',     // enum: none|light|medium|strong
        'border_radius'      => '6px',        // string: Eckenradius
    ],

    // ═══════════════════════════════════════════════════════════════
    // JOB-LISTE
    // ═══════════════════════════════════════════════════════════════
    'job_list_columns'     => 3,              // int: 2, 3 oder 4
    'show_badges'          => true,           // bool: Badges anzeigen
    'show_salary'          => true,           // bool: Gehalt anzeigen
    'show_location'        => true,           // bool: Standort anzeigen
]
```

---

## Schatten-Presets

| Wert | CSS `box-shadow` | Beschreibung |
|------|------------------|--------------|
| `none` | `none` | Kein Schatten |
| `light` | `0 1px 2px 0 rgb(0 0 0 / 0.05)` | Sehr subtil |
| `medium` | `0 4px 6px -1px rgb(0 0 0 / 0.1)` | Standard |
| `strong` | `0 10px 15px -3px rgb(0 0 0 / 0.1)` | Deutlich sichtbar |
| `extra` | `0 20px 25px -5px rgb(0 0 0 / 0.1)` | Sehr prominent |

---

## CSS-Output

Die Einstellungen werden als CSS Custom Properties in den `<head>` injiziert:

### PHP-Implementation

```php
<?php
// src/Frontend/DynamicStyles.php

namespace RecruitingPlaybook\Frontend;

class DynamicStyles {

    private array $shadow_map = [
        'none'   => 'none',
        'light'  => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        'medium' => '0 4px 6px -1px rgb(0 0 0 / 0.1)',
        'strong' => '0 10px 15px -3px rgb(0 0 0 / 0.1)',
        'extra'  => '0 20px 25px -5px rgb(0 0 0 / 0.1)',
    ];

    public function render(): string {
        $settings = get_option( 'rp_settings' );
        $design = $settings['design'] ?? [];

        // Defaults
        $design = wp_parse_args( $design, $this->get_defaults() );

        $css = ".rp-plugin {\n";

        // Typografie
        $css .= "    --rp-text-h1: {$design['font_size_h1']};\n";
        $css .= "    --rp-text-h2: {$design['font_size_h2']};\n";
        $css .= "    --rp-text-h3: {$design['font_size_h3']};\n";
        $css .= "    --rp-text-h4: {$design['font_size_h4']};\n";
        $css .= "    --rp-text-h5: {$design['font_size_h5']};\n";
        $css .= "    --rp-text-h6: {$design['font_size_h6']};\n";
        $css .= "    --rp-text-body: {$design['font_size_body']};\n";
        $css .= "    --rp-text-small: {$design['font_size_small']};\n";

        // Cards
        $css .= "    --rp-card-radius: {$design['card_border_radius']};\n";
        $css .= "    --rp-card-shadow: {$this->shadow_map[$design['card_shadow']]};\n";
        $css .= "    --rp-card-border-color: {$design['card_border_color']};\n";

        // Primärfarbe
        if ( ! $design['use_theme_color'] ) {
            $css .= "    --rp-color-primary: {$design['primary_color']};\n";
        }

        // Custom Buttons
        if ( ! $design['use_theme_button'] ) {
            $btn = $design['button'];
            $css .= "    --rp-btn-bg: {$btn['bg_color']};\n";
            $css .= "    --rp-btn-bg-hover: {$btn['bg_color_hover']};\n";
            $css .= "    --rp-btn-text: {$btn['text_color']};\n";
            $css .= "    --rp-btn-text-hover: {$btn['text_color_hover']};\n";
            $border = $btn['border_show'] ? $btn['border_color'] : 'transparent';
            $border_hover = $btn['border_show'] ? $btn['border_color_hover'] : 'transparent';
            $css .= "    --rp-btn-border: {$border};\n";
            $css .= "    --rp-btn-border-hover: {$border_hover};\n";
            $css .= "    --rp-btn-shadow: {$this->shadow_map[$btn['shadow']]};\n";
            $css .= "    --rp-btn-shadow-hover: {$this->shadow_map[$btn['shadow_hover']]};\n";
            $css .= "    --rp-btn-radius: {$btn['border_radius']};\n";
        }

        $css .= "}\n";

        return $css;
    }

    private function get_defaults(): array {
        return [
            'use_theme_color'    => true,
            'primary_color'      => '#2563eb',
            'font_size_h1'       => '2.5rem',
            'font_size_h2'       => '2rem',
            'font_size_h3'       => '1.5rem',
            'font_size_h4'       => '1.25rem',
            'font_size_h5'       => '1.1rem',
            'font_size_h6'       => '1rem',
            'font_size_body'     => '1rem',
            'font_size_small'    => '0.875rem',
            'card_border_radius' => '8px',
            'card_shadow'        => 'medium',
            'card_border_color'  => '#e2e8f0',
            'use_theme_button'   => true,
            'button' => [
                'bg_color'         => '#2563eb',
                'bg_color_hover'   => '#1d4ed8',
                'text_color'       => '#ffffff',
                'text_color_hover' => '#ffffff',
                'border_show'      => false,
                'border_color'     => '#2563eb',
                'border_color_hover' => '#1d4ed8',
                'shadow'           => 'light',
                'shadow_hover'     => 'medium',
                'border_radius'    => '6px',
            ],
        ];
    }
}
```

### Generiertes CSS (Beispiel)

```css
.rp-plugin {
    /* Typografie */
    --rp-text-h1: 2.5rem;
    --rp-text-h2: 2rem;
    --rp-text-h3: 1.5rem;
    --rp-text-h4: 1.25rem;
    --rp-text-h5: 1.1rem;
    --rp-text-h6: 1rem;
    --rp-text-body: 1rem;
    --rp-text-small: 0.875rem;

    /* Cards */
    --rp-card-radius: 8px;
    --rp-card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --rp-card-border-color: #e2e8f0;

    /* Custom Buttons (nur wenn use_theme_button = false) */
    --rp-btn-bg: #2563eb;
    --rp-btn-bg-hover: #1d4ed8;
    --rp-btn-text: #ffffff;
    --rp-btn-text-hover: #ffffff;
    --rp-btn-border: transparent;
    --rp-btn-border-hover: transparent;
    --rp-btn-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --rp-btn-shadow-hover: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --rp-btn-radius: 6px;
}
```

---

## Admin-Seite Implementation

### Menü-Registrierung

```php
// In src/Admin/Menu.php
add_submenu_page(
    'recruiting-playbook',
    __( 'Design', 'recruiting-playbook' ),
    __( 'Design', 'recruiting-playbook' ),
    'manage_options',
    'rp-design',
    [ $this, 'renderDesignPage' ]
);
```

### React-Komponente

Die Admin-Seite wird mit React (@wordpress/scripts) umgesetzt:

- Color Picker: `@wordpress/components` → `ColorPicker`
- Slider: `@wordpress/components` → `RangeControl`
- Toggle: `@wordpress/components` → `ToggleControl`
- Radio: `@wordpress/components` → `RadioControl`

### REST API Endpoint

```php
// GET/POST /wp-json/recruiting/v1/settings/design
register_rest_route(
    'recruiting/v1',
    '/settings/design',
    [
        [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_design_settings' ],
            'permission_callback' => [ $this, 'can_manage_settings' ],
        ],
        [
            'methods'  => 'POST',
            'callback' => [ $this, 'update_design_settings' ],
            'permission_callback' => [ $this, 'can_manage_settings' ],
        ],
    ]
);
```

---

## Validierung

### Server-Side (PHP)

```php
private function validate_design_settings( array $input ): array {
    $sanitized = [];

    // Farben: Hex-Format validieren
    $color_fields = [
        'primary_color',
        'card_border_color',
        'button.bg_color',
        'button.bg_color_hover',
        'button.text_color',
        'button.text_color_hover',
        'button.border_color',
        'button.border_color_hover',
    ];

    foreach ( $color_fields as $field ) {
        $value = $this->get_nested_value( $input, $field );
        if ( $value && ! preg_match( '/^#[a-fA-F0-9]{6}$/', $value ) ) {
            // Ungültige Farbe → Default
            $this->set_nested_value( $sanitized, $field, '#2563eb' );
        }
    }

    // Schriftgrößen: rem/px Format
    $font_fields = [
        'font_size_h1', 'font_size_h2', 'font_size_h3',
        'font_size_h4', 'font_size_h5', 'font_size_h6',
        'font_size_body', 'font_size_small',
    ];

    foreach ( $font_fields as $field ) {
        if ( isset( $input[ $field ] ) ) {
            if ( ! preg_match( '/^[\d.]+(rem|px|em)$/', $input[ $field ] ) ) {
                $sanitized[ $field ] = '1rem'; // Default
            }
        }
    }

    // Border-Radius: 0-24px
    if ( isset( $input['card_border_radius'] ) ) {
        $px = (int) $input['card_border_radius'];
        $sanitized['card_border_radius'] = max( 0, min( 24, $px ) ) . 'px';
    }

    // Schatten: Enum validieren
    $valid_shadows = [ 'none', 'light', 'medium', 'strong', 'extra' ];
    if ( isset( $input['card_shadow'] ) && ! in_array( $input['card_shadow'], $valid_shadows ) ) {
        $sanitized['card_shadow'] = 'medium';
    }

    // Spalten: 2, 3 oder 4
    if ( isset( $input['job_list_columns'] ) ) {
        $cols = (int) $input['job_list_columns'];
        $sanitized['job_list_columns'] = in_array( $cols, [ 2, 3, 4 ] ) ? $cols : 3;
    }

    return array_merge( $input, $sanitized );
}
```

---

## Live-Vorschau

Die Admin-Seite enthält Live-Vorschau-Elemente:

1. **Card-Vorschau**: Zeigt eine Beispiel-Job-Card mit aktuellen Einstellungen
2. **Button-Vorschau**: Zeigt Normal- und Hover-Zustand des Buttons

```jsx
// React-Komponente für Button-Vorschau
function ButtonPreview({ settings }) {
    const [isHover, setIsHover] = useState(false);

    const style = {
        backgroundColor: isHover ? settings.bg_color_hover : settings.bg_color,
        color: isHover ? settings.text_color_hover : settings.text_color,
        border: settings.border_show
            ? `1px solid ${isHover ? settings.border_color_hover : settings.border_color}`
            : 'none',
        boxShadow: SHADOW_MAP[isHover ? settings.shadow_hover : settings.shadow],
        borderRadius: settings.border_radius,
        padding: '0.75rem 1.5rem',
        cursor: 'pointer',
        transition: 'all 0.2s ease',
    };

    return (
        <button
            style={style}
            onMouseEnter={() => setIsHover(true)}
            onMouseLeave={() => setIsHover(false)}
        >
            Jetzt bewerben
        </button>
    );
}
```

---

## Feature-Flag

Die Design-Einstellungen sind nur für Pro-Nutzer verfügbar:

```php
// In der Admin-Seite
if ( ! rp_has_feature( 'design_settings' ) ) {
    // Upsell-Hinweis anzeigen
    echo '<div class="rp-upsell-notice">';
    echo '<p>' . __( 'Design-Anpassungen sind in der Pro-Version verfügbar.', 'recruiting-playbook' ) . '</p>';
    echo '<a href="..." class="button button-primary">' . __( 'Jetzt upgraden', 'recruiting-playbook' ) . '</a>';
    echo '</div>';
    return;
}
```

---

*Letzte Aktualisierung: Januar 2025*

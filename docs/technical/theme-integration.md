# Theme & Page Builder Integration

## Übersicht

Das Plugin bietet flexible Integrationsmöglichkeiten für verschiedene Themes und Page Builder:

| Methode | Beschreibung | Verfügbar |
|---------|--------------|-----------|
| **Automatische Seiten** | /jobs/ Archiv, /jobs/{slug}/ Einzelseite | MVP |
| **Shortcodes** | Universell, funktioniert überall | MVP |
| **Avada / Fusion Builder** | Native Elements | Pro ✅ |
| **Gutenberg Blocks** | WordPress Block Editor | Free ✅ |
| **Elementor Widgets** | Elementor Page Builder | Pro ✅ |
| **PHP Functions** | Für Theme-Entwickler | MVP |

```
┌─────────────────────────────────────────────────────────────────┐
│                   INTEGRATION LAYERS                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    PAGE BUILDERS                         │   │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐                     │   │
│  │  │  Avada  │ │Elementor│ │Gutenberg│                     │   │
│  │  │ Fusion  │ │ Widget  │ │  Block  │                     │   │
│  │  └────┬────┘ └────┬────┘ └────┬────┘                     │   │
│  └───────┼───────────┼───────────┼───────────────────────────┘   │
│          │           │           │                               │
│          └───────────┴─────┬─────┘                               │
│                            │                                    │
│                            ▼                                    │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                     SHORTCODES                           │   │
│  │         (Basis-Layer, wird von allen genutzt)           │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    PHP FUNCTIONS                         │   │
│  │              (Kern-Rendering-Logik)                      │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Verfügbare Elemente

| Element | Funktion | Shortcode |
|---------|----------|-----------|
| **Job Grid** | Stellen als Karten-Grid | `[rp_jobs]` |
| **Job Tabs** | Gefilterte Tabs nach Kategorie | `[rp_jobs_tabs]` |
| **Job Slider** | Stellen als Karussell | `[rp_jobs_slider]` |
| **Job Card** | Einzelne Job-Karte | `[rp_job_card]` |
| **Featured Jobs** | Top-Stellen (manuell markiert) | `[rp_featured_jobs]` |
| **Latest Jobs** | Neueste Stellen | `[rp_latest_jobs]` |
| **Job Search** | Suchformular mit Filtern | `[rp_job_search]` |
| **Application Form** | Bewerbungsformular | `[rp_application_form]` |
| **Job Counter** | "X offene Stellen" | `[rp_job_count]` |
| **Job Categories** | Kategorie-Liste/Grid | `[rp_job_categories]` |
| **AI Job-Match** | 🔥 KI-Qualifikationscheck | `[rp_ai_job_match]` |
| **AI Job-Finder** | 🔥 KI findet passende Stellen | `[rp_ai_job_finder]` |
| **AI Chancen-Check** | 🔥 Einstellungschancen berechnen | `[rp_ai_chance_check]` |

---

## Badges & Labels

### Verfügbare Badges

| Badge | Slug | Beschreibung | Vergabe |
|-------|------|--------------|---------|
| **Neu** | `new` | Neue Stelle | Automatisch (Zeitraum einstellbar) |
| **Top Bezahlung** | `top_pay` | Überdurchschnittliches Gehalt | Manuell |
| **Top Arbeitgeber** | `top_employer` | Premium-Kunde | Manuell |
| **Dringend** | `urgent` | Schnell zu besetzen | Manuell |
| **Remote** | `remote` | Homeoffice möglich | Manuell |
| **Befristet** | `limited` | Zeitlich begrenzt | Manuell |

### Admin-Einstellungen für "Neu"-Badge

```php
// Einstellbar im Admin: Wie lange gilt eine Stelle als "neu"?
$settings = [
    'badges' => [
        'new_days' => 14, // Tage, Standard: 14
    ],
];
```

### Badge-Logik

```php
<?php
// src/Services/BadgeService.php

namespace RecruitingPlaybook\Services;

class BadgeService {
    
    /**
     * Alle Badges für einen Job ermitteln
     */
    public function get_badges( int $job_id ): array {
        $badges = [];
        
        // Automatisch: "Neu"
        if ( $this->is_new( $job_id ) ) {
            $badges[] = [
                'slug'  => 'new',
                'label' => __( 'Neu', 'recruiting-playbook' ),
                'color' => 'green',
            ];
        }
        
        // Manuell gesetzte Badges
        $manual_badges = get_post_meta( $job_id, '_rp_badges', true ) ?: [];
        
        $badge_definitions = $this->get_badge_definitions();
        
        foreach ( $manual_badges as $slug ) {
            if ( isset( $badge_definitions[ $slug ] ) ) {
                $badges[] = $badge_definitions[ $slug ];
            }
        }
        
        return $badges;
    }
    
    /**
     * Prüft ob Job als "neu" gilt
     */
    private function is_new( int $job_id ): bool {
        $settings = get_option( 'rp_settings' );
        $new_days = $settings['badges']['new_days'] ?? 14;
        
        $publish_date = get_post_field( 'post_date', $job_id );
        $days_ago = ( time() - strtotime( $publish_date ) ) / DAY_IN_SECONDS;
        
        return $days_ago <= $new_days;
    }
    
    /**
     * Badge-Definitionen
     */
    private function get_badge_definitions(): array {
        return [
            'top_pay' => [
                'slug'  => 'top_pay',
                'label' => __( 'Top Bezahlung', 'recruiting-playbook' ),
                'color' => 'blue',
            ],
            'top_employer' => [
                'slug'  => 'top_employer',
                'label' => __( 'Top Arbeitgeber', 'recruiting-playbook' ),
                'color' => 'purple',
            ],
            'urgent' => [
                'slug'  => 'urgent',
                'label' => __( 'Dringend', 'recruiting-playbook' ),
                'color' => 'red',
            ],
            'remote' => [
                'slug'  => 'remote',
                'label' => __( 'Remote möglich', 'recruiting-playbook' ),
                'color' => 'teal',
            ],
            'limited' => [
                'slug'  => 'limited',
                'label' => __( 'Befristet', 'recruiting-playbook' ),
                'color' => 'orange',
            ],
        ];
    }
}
```

### Meta-Box für Badges im Editor

```php
<?php
// src/Admin/MetaBoxes/JobBadges.php

class JobBadges {
    
    public function render( WP_Post $post ): void {
        $current_badges = get_post_meta( $post->ID, '_rp_badges', true ) ?: [];
        $badge_service = new BadgeService();
        $definitions = $badge_service->get_badge_definitions();
        
        wp_nonce_field( 'rp_badges', 'rp_badges_nonce' );
        ?>
        <div class="rp-badges-metabox">
            <p class="description">
                <?php esc_html_e( 'Wählen Sie die Badges, die bei dieser Stelle angezeigt werden sollen.', 'recruiting-playbook' ); ?>
            </p>
            
            <div class="rp-badge-checkboxes">
                <?php foreach ( $definitions as $slug => $badge ) : ?>
                    <label class="rp-badge-option">
                        <input 
                            type="checkbox" 
                            name="rp_badges[]" 
                            value="<?php echo esc_attr( $slug ); ?>"
                            <?php checked( in_array( $slug, $current_badges, true ) ); ?>
                        >
                        <span class="rp-badge rp-badge--<?php echo esc_attr( $badge['color'] ); ?>">
                            <?php echo esc_html( $badge['label'] ); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <p class="rp-badge-auto-info">
                <span class="dashicons dashicons-info"></span>
                <?php printf(
                    esc_html__( 'Das Badge "Neu" wird automatisch für %d Tage angezeigt.', 'recruiting-playbook' ),
                    get_option( 'rp_settings' )['badges']['new_days'] ?? 14
                ); ?>
            </p>
        </div>
        <?php
    }
}
```

---

## 1. Shortcodes

### [rp_jobs] - Job Grid

Zeigt Stellen als Karten-Grid an.

```
[rp_jobs]
[rp_jobs limit="6" columns="3"]
[rp_jobs category="pflege" location="berlin"]
[rp_jobs featured="true" limit="3"]
[rp_jobs orderby="date" order="DESC"]
```

**Parameter:**

| Parameter | Beschreibung | Standard | Optionen |
|-----------|--------------|----------|----------|
| `limit` | Anzahl Stellen | 12 | Zahl oder -1 (alle) |
| `columns` | Spalten im Grid | 3 | 1, 2, 3, 4 |
| `category` | Nach Kategorie filtern | - | Slug oder ID |
| `location` | Nach Standort filtern | - | Slug oder ID |
| `employment_type` | Nach Beschäftigungsart | - | vollzeit, teilzeit, minijob |
| `featured` | Nur Featured Jobs | false | true/false |
| `badges` | Nur mit bestimmten Badges | - | top_pay, urgent, etc. |
| `exclude` | Job-IDs ausschließen | - | Komma-separiert |
| `orderby` | Sortierung | date | date, title, random, menu_order |
| `order` | Reihenfolge | DESC | ASC, DESC |
| `show_filters` | Filter anzeigen | false | true/false |
| `show_pagination` | Pagination anzeigen | true | true/false |
| `card_style` | Karten-Design | default | default, compact, detailed |

### [rp_jobs_tabs] - Gefilterte Tabs

Zeigt Stellen mit Tab-Navigation nach Kategorie.

```
[rp_jobs_tabs]
[rp_jobs_tabs categories="pflege,verwaltung,it"]
[rp_jobs_tabs show_all_tab="true" limit="10"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `categories` | Welche Kategorien als Tabs | Alle mit Jobs |
| `show_all_tab` | "Alle" Tab anzeigen | true |
| `limit` | Jobs pro Tab | 12 |
| `columns` | Spalten im Grid | 3 |
| `card_style` | Karten-Design | default |

### [rp_jobs_slider] - Karussell

Zeigt Stellen als Slider/Karussell.

```
[rp_jobs_slider]
[rp_jobs_slider limit="6" autoplay="true" speed="5000"]
[rp_jobs_slider featured="true" slides_to_show="3"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `limit` | Anzahl Stellen | 6 |
| `slides_to_show` | Sichtbare Slides | 3 |
| `slides_to_scroll` | Slides pro Scroll | 1 |
| `autoplay` | Automatisch wechseln | false |
| `speed` | Autoplay-Geschwindigkeit (ms) | 5000 |
| `dots` | Punkte-Navigation | true |
| `arrows` | Pfeile anzeigen | true |
| `featured` | Nur Featured Jobs | false |
| `category` | Nach Kategorie | - |

### [rp_featured_jobs] - Top-Stellen

Zeigt manuell als "Featured" markierte Stellen.

```
[rp_featured_jobs]
[rp_featured_jobs limit="3" columns="3"]
[rp_featured_jobs title="Top-Jobangebote" subtitle="Ausgewählte Stellen für Sie"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `limit` | Anzahl | 3 |
| `columns` | Spalten | 3 |
| `title` | Überschrift | - |
| `subtitle` | Untertitel | - |
| `layout` | Darstellung | grid |
| `card_style` | Karten-Design | featured |

### [rp_latest_jobs] - Neueste Stellen

Zeigt die neuesten Stellen (mit "Neu"-Badge).

```
[rp_latest_jobs]
[rp_latest_jobs limit="5" layout="list"]
[rp_latest_jobs title="Neue Stellenangebote"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `limit` | Anzahl | 5 |
| `days` | Max. Alter in Tagen | (aus Einstellungen) |
| `layout` | grid, list, compact | list |
| `title` | Überschrift | - |
| `show_date` | Datum anzeigen | true |

### [rp_job_card] - Einzelne Karte

Zeigt eine einzelne Job-Karte (z.B. in Textblöcken).

```
[rp_job_card id="123"]
[rp_job_card id="123" style="detailed"]
```

### [rp_job_search] - Suchformular

Suchformular mit Filtern.

```
[rp_job_search]
[rp_job_search show_category="true" show_location="true" show_type="true"]
[rp_job_search results_page="/stellenangebote/"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `show_keyword` | Freitext-Suche | true |
| `show_category` | Kategorie-Filter | true |
| `show_location` | Standort-Filter | true |
| `show_type` | Beschäftigungsart-Filter | true |
| `results_page` | Ergebnis-Seite URL | /jobs/ |
| `layout` | horizontal, vertical, inline | horizontal |
| `button_text` | Button-Text | "Stellen finden" |

### [rp_application_form] - Bewerbungsformular

Professionelles Bewerbungsformular.

```
[rp_application_form job_id="123"]
[rp_application_form job_id="123" layout="two-column"]
[rp_application_form] <!-- Auf Job-Einzelseite automatisch -->
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `job_id` | Job-ID | (automatisch auf Einzelseite) |
| `layout` | Formular-Layout | default |
| `show_cover_letter` | Anschreiben-Feld | true |
| `show_salary` | Gehaltsvorstellung | true |
| `show_start_date` | Frühestes Eintrittsdatum | true |
| `show_talent_pool` | Talent-Pool Checkbox | true |
| `redirect` | Weiterleitung nach Absenden | - |
| `success_message` | Erfolgs-Nachricht | (Standard) |

### [rp_job_count] - Stellen-Counter

Zeigt die Anzahl offener Stellen.

```
[rp_job_count] <!-- "12 offene Stellen" -->
[rp_job_count category="pflege"] <!-- "8 Stellen in Pflege" -->
[rp_job_count format="Wir haben aktuell {count} Karrieremöglichkeiten!"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `category` | Nach Kategorie | - |
| `location` | Nach Standort | - |
| `format` | Text-Format | "{count} offene Stellen" |
| `link` | Als Link zur Übersicht | true |
| `link_url` | Link-URL | /jobs/ |

### [rp_job_categories] - Kategorie-Übersicht

Grid/Liste aller Job-Kategorien.

```
[rp_job_categories]
[rp_job_categories layout="grid" columns="4" show_count="true"]
[rp_job_categories show_icon="true" show_description="true"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `layout` | grid, list | grid |
| `columns` | Spalten | 4 |
| `show_count` | Job-Anzahl anzeigen | true |
| `show_icon` | Kategorie-Icon | false |
| `show_description` | Beschreibung | false |
| `hide_empty` | Leere Kategorien ausblenden | true |

---

## 🔥 KI-Analyse Shortcodes (AI-Addon)

> **Hinweis:** Diese Shortcodes sind nur mit aktivem AI-Addon verfügbar.

### [rp_ai_job_match] - KI-Qualifikationscheck

Bewerber prüft seine Eignung für eine konkrete Stelle.

```
[rp_ai_job_match]
[rp_ai_job_match job_id="123"]
[rp_ai_job_match show_form_prefill="true"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `job_id` | Job-ID | (automatisch auf Einzelseite) |
| `title` | Überschrift | "KI-Qualifikationscheck" |
| `description` | Beschreibungstext | (Standard) |
| `show_form_prefill` | Daten ins Formular übernehmen | true |
| `button_text` | Button-Text | "Qualifikation prüfen" |
| `layout` | compact, default, detailed | default |

**Ausgabe:**
- Upload-Bereich für Lebenslauf + Zeugnisse
- Match-Score (0-100%)
- Erfüllte / Teilweise erfüllte / Fehlende Anforderungen
- Empfehlung & Tipps
- Button: "Jetzt bewerben" mit vorausgefülltem Formular

### [rp_ai_job_finder] - KI-Job-Finder

Bewerber findet passende Stellen aus allen Angeboten.

```
[rp_ai_job_finder]
[rp_ai_job_finder limit="5" show_apply_button="true"]
[rp_ai_job_finder title="Finden Sie Ihren Traumjob"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `limit` | Max. angezeigte Matches | 5 |
| `title` | Überschrift | "KI-Job-Finder" |
| `description` | Beschreibungstext | (Standard) |
| `show_apply_button` | Direkt-Bewerben Button | true |
| `show_reasons` | Match-Begründung anzeigen | true |
| `min_score` | Mindest-Score für Anzeige | 60 |
| `layout` | list, grid, cards | cards |
| `show_talent_pool` | Talent-Pool Option | true |

**Ausgabe:**
- Upload-Bereich für Lebenslauf
- Erkanntes Profil (Name, Beruf, Erfahrung, Skills)
- Top-Matches als Karten mit Score
- Pro Match: Begründung warum es passt
- Direkt-Bewerben Buttons
- Talent-Pool Option wenn keine Stelle passt

### [rp_ai_chance_check] - Einstellungschancen berechnen

Tiefenanalyse der Einstellungschancen für eine Stelle.

```
[rp_ai_chance_check]
[rp_ai_chance_check job_id="123"]
[rp_ai_chance_check show_tips="true" show_score_breakdown="true"]
```

**Parameter:**

| Parameter | Beschreibung | Standard |
|-----------|--------------|----------|
| `job_id` | Job-ID | (automatisch auf Einzelseite) |
| `title` | Überschrift | "Ihre Einstellungschancen" |
| `show_score_breakdown` | Detaillierte Punkteaufschlüsselung | true |
| `show_positive_factors` | Was FÜR Bewerber spricht | true |
| `show_negative_factors` | Was GEGEN Bewerber spricht | true |
| `show_tips` | Verbesserungstipps | true |
| `show_apply_button` | Bewerben-Button | true |

**Ausgabe:**
- Große Prozentanzeige (z.B. "87% Einstellungschance")
- Label (Sehr gut / Gut / Moderat / Gering)
- Was FÜR Sie spricht (mit Punkten)
- Was GEGEN Sie sprechen könnte
- Konkrete Tipps zur Verbesserung
- "Jetzt bewerben" Button

---

## 2. Avada / Fusion Builder Integration

### Verfügbare Fusion Elements

| Element | Beschreibung | Icon |
|---------|--------------|------|
| **RP: Job Grid** | Stellen-Grid mit Filtern | grid |
| **RP: Job Tabs** | Tab-Navigation nach Kategorie | folder |
| **RP: Job Slider** | Stellen-Karussell | slides |
| **RP: Featured Jobs** | Top-Stellen Showcase | star |
| **RP: Latest Jobs** | Neueste Stellen | clock |
| **RP: Job Search** | Suchformular | search |
| **RP: Application Form** | Bewerbungsformular | file-text |
| **RP: Job Counter** | Stellen-Zähler | hash |
| **RP: Job Categories** | Kategorie-Grid | layers |
| **RP: AI Job-Match** | 🔥 KI-Qualifikationscheck | cpu |
| **RP: AI Job-Finder** | 🔥 KI findet passende Jobs | search-plus |
| **RP: AI Chancen-Check** | 🔥 Einstellungschancen | chart-bar |

### Fusion Element: Job Grid

```php
<?php
// src/Integrations/Avada/Elements/JobGrid.php

namespace RecruitingPlaybook\Integrations\Avada\Elements;

if ( ! class_exists( 'Fusion_Element' ) ) {
    return;
}

class JobGrid extends \Fusion_Element {
    
    /**
     * Element Slug
     */
    public $shortcode_handle = 'rp_fusion_job_grid';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        add_shortcode( $this->shortcode_handle, [ $this, 'render' ] );
    }
    
    /**
     * Element Name
     */
    public function get_name() {
        return esc_attr__( 'RP: Job Grid', 'recruiting-playbook' );
    }
    
    /**
     * Element Icon
     */
    public function get_icon() {
        return 'fusiona-grid';
    }
    
    /**
     * Element Parameters
     */
    public function get_params() {
        return [
            [
                'type'        => 'range',
                'heading'     => esc_attr__( 'Anzahl Stellen', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Wie viele Stellen sollen angezeigt werden?', 'recruiting-playbook' ),
                'param_name'  => 'limit',
                'value'       => '12',
                'min'         => '1',
                'max'         => '50',
                'step'        => '1',
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
                'param_name'  => 'columns',
                'default'     => '3',
                'value'       => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
            ],
            [
                'type'        => 'multiple_select',
                'heading'     => esc_attr__( 'Kategorien', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Leer = alle Kategorien', 'recruiting-playbook' ),
                'param_name'  => 'category',
                'choices'     => $this->get_category_choices(),
            ],
            [
                'type'        => 'multiple_select',
                'heading'     => esc_attr__( 'Standorte', 'recruiting-playbook' ),
                'param_name'  => 'location',
                'choices'     => $this->get_location_choices(),
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Nur Featured', 'recruiting-playbook' ),
                'param_name'  => 'featured',
                'default'     => 'no',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'multiple_select',
                'heading'     => esc_attr__( 'Mit Badges', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Nur Stellen mit bestimmten Badges anzeigen', 'recruiting-playbook' ),
                'param_name'  => 'badges',
                'choices'     => $this->get_badge_choices(),
            ],
            [
                'type'        => 'select',
                'heading'     => esc_attr__( 'Sortierung', 'recruiting-playbook' ),
                'param_name'  => 'orderby',
                'default'     => 'date',
                'value'       => [
                    'date'       => esc_attr__( 'Datum', 'recruiting-playbook' ),
                    'title'      => esc_attr__( 'Titel', 'recruiting-playbook' ),
                    'random'     => esc_attr__( 'Zufällig', 'recruiting-playbook' ),
                    'menu_order' => esc_attr__( 'Manuelle Reihenfolge', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Reihenfolge', 'recruiting-playbook' ),
                'param_name'  => 'order',
                'default'     => 'DESC',
                'value'       => [
                    'DESC' => esc_attr__( 'Absteigend', 'recruiting-playbook' ),
                    'ASC'  => esc_attr__( 'Aufsteigend', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Karten-Style', 'recruiting-playbook' ),
                'param_name'  => 'card_style',
                'default'     => 'default',
                'value'       => [
                    'default'  => esc_attr__( 'Standard', 'recruiting-playbook' ),
                    'compact'  => esc_attr__( 'Kompakt', 'recruiting-playbook' ),
                    'detailed' => esc_attr__( 'Detailliert', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Filter anzeigen', 'recruiting-playbook' ),
                'param_name'  => 'show_filters',
                'default'     => 'no',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Pagination', 'recruiting-playbook' ),
                'param_name'  => 'show_pagination',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            // Styling
            [
                'type'        => 'colorpickeralpha',
                'heading'     => esc_attr__( 'Badge "Neu" Farbe', 'recruiting-playbook' ),
                'param_name'  => 'badge_new_color',
                'default'     => '',
                'group'       => esc_attr__( 'Design', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'dimension',
                'heading'     => esc_attr__( 'Karten-Abstand', 'recruiting-playbook' ),
                'param_name'  => 'card_gap',
                'value'       => [
                    'gap' => '20px',
                ],
                'group'       => esc_attr__( 'Design', 'recruiting-playbook' ),
            ],
        ];
    }
    
    /**
     * Render
     */
    public function render( $args, $content = '' ) {
        $defaults = [
            'limit'           => 12,
            'columns'         => 3,
            'category'        => '',
            'location'        => '',
            'featured'        => 'no',
            'badges'          => '',
            'orderby'         => 'date',
            'order'           => 'DESC',
            'card_style'      => 'default',
            'show_filters'    => 'no',
            'show_pagination' => 'yes',
        ];
        
        $args = shortcode_atts( $defaults, $args, $this->shortcode_handle );
        
        // An normalen Shortcode delegieren
        return do_shortcode( sprintf(
            '[rp_jobs limit="%d" columns="%d" category="%s" location="%s" featured="%s" badges="%s" orderby="%s" order="%s" card_style="%s" show_filters="%s" show_pagination="%s"]',
            $args['limit'],
            $args['columns'],
            $args['category'],
            $args['location'],
            $args['featured'] === 'yes' ? 'true' : 'false',
            $args['badges'],
            $args['orderby'],
            $args['order'],
            $args['card_style'],
            $args['show_filters'] === 'yes' ? 'true' : 'false',
            $args['show_pagination'] === 'yes' ? 'true' : 'false'
        ) );
    }
    
    /**
     * Kategorie-Optionen
     */
    private function get_category_choices(): array {
        $terms = get_terms( [
            'taxonomy'   => 'job_category',
            'hide_empty' => false,
        ] );
        
        $choices = [];
        foreach ( $terms as $term ) {
            $choices[ $term->slug ] = $term->name;
        }
        
        return $choices;
    }
    
    /**
     * Standort-Optionen
     */
    private function get_location_choices(): array {
        $terms = get_terms( [
            'taxonomy'   => 'job_location',
            'hide_empty' => false,
        ] );
        
        $choices = [];
        foreach ( $terms as $term ) {
            $choices[ $term->slug ] = $term->name;
        }
        
        return $choices;
    }
    
    /**
     * Badge-Optionen
     */
    private function get_badge_choices(): array {
        return [
            'top_pay'      => esc_attr__( 'Top Bezahlung', 'recruiting-playbook' ),
            'top_employer' => esc_attr__( 'Top Arbeitgeber', 'recruiting-playbook' ),
            'urgent'       => esc_attr__( 'Dringend', 'recruiting-playbook' ),
            'remote'       => esc_attr__( 'Remote möglich', 'recruiting-playbook' ),
        ];
    }
}

// Element registrieren
new JobGrid();
```

### Fusion Element: Job Tabs

```php
<?php
// src/Integrations/Avada/Elements/JobTabs.php

class JobTabs extends \Fusion_Element {
    
    public $shortcode_handle = 'rp_fusion_job_tabs';
    
    public function get_name() {
        return esc_attr__( 'RP: Job Tabs', 'recruiting-playbook' );
    }
    
    public function get_icon() {
        return 'fusiona-folder';
    }
    
    public function get_params() {
        return [
            [
                'type'        => 'multiple_select',
                'heading'     => esc_attr__( 'Kategorien als Tabs', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Leer = alle Kategorien mit Jobs', 'recruiting-playbook' ),
                'param_name'  => 'categories',
                'choices'     => $this->get_category_choices(),
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( '"Alle" Tab anzeigen', 'recruiting-playbook' ),
                'param_name'  => 'show_all_tab',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( '"Alle" Tab Text', 'recruiting-playbook' ),
                'param_name'  => 'all_tab_text',
                'default'     => esc_attr__( 'Alle', 'recruiting-playbook' ),
                'dependency'  => [
                    [
                        'element'  => 'show_all_tab',
                        'value'    => 'yes',
                        'operator' => '==',
                    ],
                ],
            ],
            [
                'type'        => 'range',
                'heading'     => esc_attr__( 'Jobs pro Tab', 'recruiting-playbook' ),
                'param_name'  => 'limit',
                'value'       => '12',
                'min'         => '1',
                'max'         => '50',
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
                'param_name'  => 'columns',
                'default'     => '3',
                'value'       => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Tab-Style', 'recruiting-playbook' ),
                'param_name'  => 'tab_style',
                'default'     => 'horizontal',
                'value'       => [
                    'horizontal' => esc_attr__( 'Horizontal', 'recruiting-playbook' ),
                    'vertical'   => esc_attr__( 'Vertikal', 'recruiting-playbook' ),
                    'pills'      => esc_attr__( 'Pills', 'recruiting-playbook' ),
                ],
                'group'       => esc_attr__( 'Design', 'recruiting-playbook' ),
            ],
        ];
    }
}
```

### Fusion Element: Featured Jobs

```php
<?php
// src/Integrations/Avada/Elements/FeaturedJobs.php

class FeaturedJobs extends \Fusion_Element {
    
    public $shortcode_handle = 'rp_fusion_featured_jobs';
    
    public function get_name() {
        return esc_attr__( 'RP: Featured Jobs', 'recruiting-playbook' );
    }
    
    public function get_icon() {
        return 'fusiona-star';
    }
    
    public function get_params() {
        return [
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                'param_name'  => 'title',
                'default'     => esc_attr__( 'Top-Jobangebote', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Untertitel', 'recruiting-playbook' ),
                'param_name'  => 'subtitle',
                'default'     => '',
            ],
            [
                'type'        => 'range',
                'heading'     => esc_attr__( 'Anzahl', 'recruiting-playbook' ),
                'param_name'  => 'limit',
                'value'       => '3',
                'min'         => '1',
                'max'         => '12',
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Layout', 'recruiting-playbook' ),
                'param_name'  => 'layout',
                'default'     => 'grid',
                'value'       => [
                    'grid'   => esc_attr__( 'Grid', 'recruiting-playbook' ),
                    'slider' => esc_attr__( 'Slider', 'recruiting-playbook' ),
                    'list'   => esc_attr__( 'Liste', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
                'param_name'  => 'columns',
                'default'     => '3',
                'value'       => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'dependency'  => [
                    [
                        'element'  => 'layout',
                        'value'    => 'grid',
                        'operator' => '==',
                    ],
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Karten-Style', 'recruiting-playbook' ),
                'param_name'  => 'card_style',
                'default'     => 'featured',
                'value'       => [
                    'default'  => esc_attr__( 'Standard', 'recruiting-playbook' ),
                    'featured' => esc_attr__( 'Featured', 'recruiting-playbook' ),
                    'minimal'  => esc_attr__( 'Minimal', 'recruiting-playbook' ),
                ],
            ],
        ];
    }
}
```

### Fusion Element: Latest Jobs

```php
<?php
// src/Integrations/Avada/Elements/LatestJobs.php

class LatestJobs extends \Fusion_Element {
    
    public $shortcode_handle = 'rp_fusion_latest_jobs';
    
    public function get_name() {
        return esc_attr__( 'RP: Neueste Stellen', 'recruiting-playbook' );
    }
    
    public function get_icon() {
        return 'fusiona-clock';
    }
    
    public function get_params() {
        return [
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                'param_name'  => 'title',
                'default'     => esc_attr__( 'Neue Stellenangebote', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'range',
                'heading'     => esc_attr__( 'Anzahl', 'recruiting-playbook' ),
                'param_name'  => 'limit',
                'value'       => '5',
                'min'         => '1',
                'max'         => '20',
            ],
            [
                'type'        => 'range',
                'heading'     => esc_attr__( 'Max. Alter (Tage)', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Nur Stellen anzeigen, die nicht älter sind als X Tage.', 'recruiting-playbook' ),
                'param_name'  => 'days',
                'value'       => '14',
                'min'         => '1',
                'max'         => '90',
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Layout', 'recruiting-playbook' ),
                'param_name'  => 'layout',
                'default'     => 'list',
                'value'       => [
                    'list'    => esc_attr__( 'Liste', 'recruiting-playbook' ),
                    'grid'    => esc_attr__( 'Grid', 'recruiting-playbook' ),
                    'compact' => esc_attr__( 'Kompakt', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Datum anzeigen', 'recruiting-playbook' ),
                'param_name'  => 'show_date',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'link_selector',
                'heading'     => esc_attr__( '"Alle anzeigen" Link', 'recruiting-playbook' ),
                'param_name'  => 'all_link',
                'default'     => '',
            ],
        ];
    }
}
```

### Fusion Element: Application Form

```php
<?php
// src/Integrations/Avada/Elements/ApplicationForm.php

class ApplicationForm extends \Fusion_Element {
    
    public $shortcode_handle = 'rp_fusion_application_form';
    
    public function get_name() {
        return esc_attr__( 'RP: Bewerbungsformular', 'recruiting-playbook' );
    }
    
    public function get_icon() {
        return 'fusiona-file-text';
    }
    
    public function get_params() {
        return [
            [
                'type'        => 'select',
                'heading'     => esc_attr__( 'Stelle auswählen', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Leer = automatisch auf Stellen-Einzelseite', 'recruiting-playbook' ),
                'param_name'  => 'job_id',
                'choices'     => $this->get_job_choices(),
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Layout', 'recruiting-playbook' ),
                'param_name'  => 'layout',
                'default'     => 'default',
                'value'       => [
                    'default'    => esc_attr__( 'Standard', 'recruiting-playbook' ),
                    'two-column' => esc_attr__( 'Zwei Spalten', 'recruiting-playbook' ),
                    'compact'    => esc_attr__( 'Kompakt', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Anschreiben-Feld', 'recruiting-playbook' ),
                'param_name'  => 'show_cover_letter',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Gehaltsvorstellung', 'recruiting-playbook' ),
                'param_name'  => 'show_salary',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Frühestes Eintrittsdatum', 'recruiting-playbook' ),
                'param_name'  => 'show_start_date',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Talent-Pool Checkbox', 'recruiting-playbook' ),
                'param_name'  => 'show_talent_pool',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Erfolgs-Nachricht', 'recruiting-playbook' ),
                'param_name'  => 'success_message',
                'default'     => '',
                'description' => esc_attr__( 'Leer = Standard-Nachricht', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'link_selector',
                'heading'     => esc_attr__( 'Weiterleitung nach Absenden', 'recruiting-playbook' ),
                'param_name'  => 'redirect',
                'default'     => '',
            ],
        ];
    }
    
    private function get_job_choices(): array {
        $jobs = get_posts( [
            'post_type'      => 'job_listing',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );
        
        $choices = [ '' => esc_attr__( '-- Automatisch --', 'recruiting-playbook' ) ];
        
        foreach ( $jobs as $job ) {
            $choices[ $job->ID ] = $job->post_title;
        }
        
        return $choices;
    }
}
```

### Fusion Element: AI Job-Match (Killer-Feature!)

```php
<?php
// src/Integrations/Avada/Elements/AIJobMatch.php

class AIJobMatch extends \Fusion_Element {
    
    public $shortcode_handle = 'rp_fusion_ai_job_match';
    
    public function get_name() {
        return esc_attr__( 'RP: AI Job-Match', 'recruiting-playbook' );
    }
    
    public function get_icon() {
        return 'fusiona-cpu';
    }
    
    public function get_params() {
        return [
            [
                'type'        => 'select',
                'heading'     => esc_attr__( 'Stelle auswählen', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Leer = automatisch auf Stellen-Einzelseite', 'recruiting-playbook' ),
                'param_name'  => 'job_id',
                'choices'     => $this->get_job_choices(),
            ],
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                'param_name'  => 'title',
                'default'     => esc_attr__( 'KI-Qualifikationscheck', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'textarea',
                'heading'     => esc_attr__( 'Beschreibung', 'recruiting-playbook' ),
                'param_name'  => 'description',
                'default'     => esc_attr__( 'Finden Sie in Sekunden heraus, ob diese Stelle zu Ihnen passt!', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Layout', 'recruiting-playbook' ),
                'param_name'  => 'layout',
                'default'     => 'default',
                'value'       => [
                    'compact'  => esc_attr__( 'Kompakt', 'recruiting-playbook' ),
                    'default'  => esc_attr__( 'Standard', 'recruiting-playbook' ),
                    'detailed' => esc_attr__( 'Detailliert', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Formular vorausfüllen', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Erkannte Daten ins Bewerbungsformular übernehmen', 'recruiting-playbook' ),
                'param_name'  => 'show_form_prefill',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Button-Text', 'recruiting-playbook' ),
                'param_name'  => 'button_text',
                'default'     => esc_attr__( 'Qualifikation prüfen', 'recruiting-playbook' ),
            ],
            // Design
            [
                'type'        => 'colorpickeralpha',
                'heading'     => esc_attr__( 'Hintergrundfarbe', 'recruiting-playbook' ),
                'param_name'  => 'background_color',
                'default'     => '',
                'group'       => esc_attr__( 'Design', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'colorpickeralpha',
                'heading'     => esc_attr__( 'Score-Farbe (Gut)', 'recruiting-playbook' ),
                'param_name'  => 'score_color_good',
                'default'     => '#22c55e',
                'group'       => esc_attr__( 'Design', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'colorpickeralpha',
                'heading'     => esc_attr__( 'Score-Farbe (Mittel)', 'recruiting-playbook' ),
                'param_name'  => 'score_color_medium',
                'default'     => '#eab308',
                'group'       => esc_attr__( 'Design', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'colorpickeralpha',
                'heading'     => esc_attr__( 'Score-Farbe (Niedrig)', 'recruiting-playbook' ),
                'param_name'  => 'score_color_low',
                'default'     => '#ef4444',
                'group'       => esc_attr__( 'Design', 'recruiting-playbook' ),
            ],
        ];
    }
    
    public function render( $args, $content = '' ) {
        // AI-Addon Prüfung
        if ( ! rp_has_ai() ) {
            return $this->render_upgrade_notice();
        }
        
        $defaults = [
            'job_id'           => '',
            'title'            => __( 'KI-Qualifikationscheck', 'recruiting-playbook' ),
            'description'      => __( 'Finden Sie in Sekunden heraus, ob diese Stelle zu Ihnen passt!', 'recruiting-playbook' ),
            'layout'           => 'default',
            'show_form_prefill' => 'yes',
            'button_text'      => __( 'Qualifikation prüfen', 'recruiting-playbook' ),
        ];
        
        $args = shortcode_atts( $defaults, $args, $this->shortcode_handle );
        
        return do_shortcode( sprintf(
            '[rp_ai_job_match job_id="%s" title="%s" description="%s" layout="%s" show_form_prefill="%s" button_text="%s"]',
            $args['job_id'],
            $args['title'],
            $args['description'],
            $args['layout'],
            $args['show_form_prefill'],
            $args['button_text']
        ) );
    }
    
    private function render_upgrade_notice(): string {
        return sprintf(
            '<div class="rp-ai-upgrade-notice">
                <p>%s</p>
                <a href="%s" class="button">%s</a>
            </div>',
            esc_html__( 'KI-Features benötigen das AI-Addon.', 'recruiting-playbook' ),
            esc_url( admin_url( 'admin.php?page=rp-settings&tab=license' ) ),
            esc_html__( 'Jetzt aktivieren', 'recruiting-playbook' )
        );
    }
    
    private function get_job_choices(): array {
        $jobs = get_posts( [
            'post_type'      => 'job_listing',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ] );
        
        $choices = [ '' => esc_attr__( '-- Automatisch --', 'recruiting-playbook' ) ];
        
        foreach ( $jobs as $job ) {
            $choices[ $job->ID ] = $job->post_title;
        }
        
        return $choices;
    }
}
```

### Fusion Element: AI Job-Finder

```php
<?php
// src/Integrations/Avada/Elements/AIJobFinder.php

class AIJobFinder extends \Fusion_Element {
    
    public $shortcode_handle = 'rp_fusion_ai_job_finder';
    
    public function get_name() {
        return esc_attr__( 'RP: AI Job-Finder', 'recruiting-playbook' );
    }
    
    public function get_icon() {
        return 'fusiona-search';
    }
    
    public function get_params() {
        return [
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                'param_name'  => 'title',
                'default'     => esc_attr__( 'KI-Job-Finder', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'textarea',
                'heading'     => esc_attr__( 'Beschreibung', 'recruiting-playbook' ),
                'param_name'  => 'description',
                'default'     => esc_attr__( 'Laden Sie Ihren Lebenslauf hoch und wir finden die perfekten Stellen für Sie!', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'range',
                'heading'     => esc_attr__( 'Max. Ergebnisse', 'recruiting-playbook' ),
                'param_name'  => 'limit',
                'value'       => '5',
                'min'         => '1',
                'max'         => '10',
            ],
            [
                'type'        => 'range',
                'heading'     => esc_attr__( 'Mindest-Score (%)', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Nur Jobs mit diesem Mindest-Match anzeigen', 'recruiting-playbook' ),
                'param_name'  => 'min_score',
                'value'       => '60',
                'min'         => '0',
                'max'         => '90',
                'step'        => '5',
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Layout', 'recruiting-playbook' ),
                'param_name'  => 'layout',
                'default'     => 'cards',
                'value'       => [
                    'list'  => esc_attr__( 'Liste', 'recruiting-playbook' ),
                    'grid'  => esc_attr__( 'Grid', 'recruiting-playbook' ),
                    'cards' => esc_attr__( 'Karten', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Match-Begründung anzeigen', 'recruiting-playbook' ),
                'param_name'  => 'show_reasons',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Direkt-Bewerben Button', 'recruiting-playbook' ),
                'param_name'  => 'show_apply_button',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Talent-Pool Option', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Zeigt Option zum Talent-Pool Beitritt wenn keine Stelle passt', 'recruiting-playbook' ),
                'param_name'  => 'show_talent_pool',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Button-Text', 'recruiting-playbook' ),
                'param_name'  => 'button_text',
                'default'     => esc_attr__( 'Passende Stellen finden', 'recruiting-playbook' ),
            ],
        ];
    }
}
```

### Fusion Element: AI Chancen-Check

```php
<?php
// src/Integrations/Avada/Elements/AIChanceCheck.php

class AIChanceCheck extends \Fusion_Element {
    
    public $shortcode_handle = 'rp_fusion_ai_chance_check';
    
    public function get_name() {
        return esc_attr__( 'RP: AI Chancen-Check', 'recruiting-playbook' );
    }
    
    public function get_icon() {
        return 'fusiona-bar-chart';
    }
    
    public function get_params() {
        return [
            [
                'type'        => 'select',
                'heading'     => esc_attr__( 'Stelle auswählen', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Leer = automatisch auf Stellen-Einzelseite', 'recruiting-playbook' ),
                'param_name'  => 'job_id',
                'choices'     => $this->get_job_choices(),
            ],
            [
                'type'        => 'textfield',
                'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                'param_name'  => 'title',
                'default'     => esc_attr__( 'Ihre Einstellungschancen', 'recruiting-playbook' ),
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Score-Aufschlüsselung', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Detaillierte Punkteverteilung anzeigen', 'recruiting-playbook' ),
                'param_name'  => 'show_score_breakdown',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Positive Faktoren', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Was FÜR den Bewerber spricht', 'recruiting-playbook' ),
                'param_name'  => 'show_positive_factors',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Negative Faktoren', 'recruiting-playbook' ),
                'description' => esc_attr__( 'Was GEGEN den Bewerber sprechen könnte', 'recruiting-playbook' ),
                'param_name'  => 'show_negative_factors',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Verbesserungstipps', 'recruiting-playbook' ),
                'param_name'  => 'show_tips',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Bewerben-Button', 'recruiting-playbook' ),
                'param_name'  => 'show_apply_button',
                'default'     => 'yes',
                'value'       => [
                    'yes' => esc_attr__( 'Ja', 'recruiting-playbook' ),
                    'no'  => esc_attr__( 'Nein', 'recruiting-playbook' ),
                ],
            ],
            // Design
            [
                'type'        => 'radio_button_set',
                'heading'     => esc_attr__( 'Score-Anzeige Stil', 'recruiting-playbook' ),
                'param_name'  => 'score_style',
                'default'     => 'circle',
                'value'       => [
                    'circle'  => esc_attr__( 'Kreis', 'recruiting-playbook' ),
                    'bar'     => esc_attr__( 'Balken', 'recruiting-playbook' ),
                    'number'  => esc_attr__( 'Nur Zahl', 'recruiting-playbook' ),
                ],
                'group'       => esc_attr__( 'Design', 'recruiting-playbook' ),
            ],
        ];
    }
    
    private function get_job_choices(): array {
        $jobs = get_posts( [
            'post_type'      => 'job_listing',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ] );
        
        $choices = [ '' => esc_attr__( '-- Automatisch --', 'recruiting-playbook' ) ];
        
        foreach ( $jobs as $job ) {
            $choices[ $job->ID ] = $job->post_title;
        }
        
        return $choices;
    }
}
```

### Avada Element Registration

```php
<?php
// src/Integrations/Avada/AvadaIntegration.php

namespace RecruitingPlaybook\Integrations\Avada;

class AvadaIntegration {
    
    public function init(): void {
        // Prüfen ob Avada/Fusion Builder aktiv ist
        if ( ! class_exists( 'FusionBuilder' ) ) {
            return;
        }
        
        // Elements registrieren
        add_action( 'fusion_builder_before_init', [ $this, 'register_elements' ] );
        
        // Element-Kategorie hinzufügen
        add_filter( 'fusion_builder_element_categories', [ $this, 'add_category' ] );
    }
    
    public function register_elements(): void {
        // Alle Element-Dateien laden
        require_once __DIR__ . '/Elements/JobGrid.php';
        require_once __DIR__ . '/Elements/JobTabs.php';
        require_once __DIR__ . '/Elements/FeaturedJobs.php';
        require_once __DIR__ . '/Elements/LatestJobs.php';
        require_once __DIR__ . '/Elements/JobSlider.php';
        require_once __DIR__ . '/Elements/JobSearch.php';
        require_once __DIR__ . '/Elements/ApplicationForm.php';
        require_once __DIR__ . '/Elements/JobCounter.php';
        require_once __DIR__ . '/Elements/JobCategories.php';
    }
    
    public function add_category( array $categories ): array {
        $categories['recruiting_playbook'] = [
            'title' => esc_attr__( 'Recruiting Playbook', 'recruiting-playbook' ),
            'icon'  => 'fusiona-users',
        ];
        
        return $categories;
    }
}
```

---

## 3. Job-Karten Templates

### Standard-Karte (wie bei Samaritano)

```php
<?php
// templates/partials/job-card.php

/**
 * Job Card Template
 * 
 * @var WP_Post $job
 * @var string $style - default, compact, detailed, featured
 */

$badge_service = new \RecruitingPlaybook\Services\BadgeService();
$badges = $badge_service->get_badges( $job->ID );

$location = get_post_meta( $job->ID, '_rp_address_city', true );
$employment_types = get_the_terms( $job->ID, 'employment_type' );
$excerpt = get_the_excerpt( $job );
?>

<article class="rp-job-card rp-job-card--<?php echo esc_attr( $style ); ?>">
    
    <!-- Badges -->
    <?php if ( ! empty( $badges ) ) : ?>
        <div class="rp-job-card__badges">
            <?php foreach ( $badges as $badge ) : ?>
                <span class="rp-badge rp-badge--<?php echo esc_attr( $badge['color'] ); ?>">
                    <?php echo esc_html( $badge['label'] ); ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Title -->
    <h3 class="rp-job-card__title">
        <a href="<?php echo esc_url( get_permalink( $job ) ); ?>">
            <?php echo esc_html( $job->post_title ); ?>
        </a>
    </h3>
    
    <!-- Meta -->
    <div class="rp-job-card__meta">
        <?php if ( $location ) : ?>
            <span class="rp-job-card__location">
                <svg class="rp-icon" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                <?php echo esc_html( $location ); ?>
            </span>
        <?php endif; ?>
        
        <?php if ( $employment_types ) : ?>
            <span class="rp-job-card__employment">
                <svg class="rp-icon" viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                <?php echo esc_html( implode( ', ', wp_list_pluck( $employment_types, 'name' ) ) ); ?>
            </span>
        <?php endif; ?>
    </div>
    
    <!-- Excerpt (nur bei default/detailed) -->
    <?php if ( in_array( $style, [ 'default', 'detailed' ], true ) && $excerpt ) : ?>
        <div class="rp-job-card__excerpt">
            <?php echo esc_html( wp_trim_words( $excerpt, 25 ) ); ?>
        </div>
    <?php endif; ?>
    
    <!-- Link -->
    <a href="<?php echo esc_url( get_permalink( $job ) ); ?>" class="rp-job-card__link">
        <?php esc_html_e( 'mehr Informationen', 'recruiting-playbook' ); ?>
        <svg class="rp-icon" viewBox="0 0 24 24"><path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/></svg>
    </a>
    
</article>
```

### Kompakte Karte (für Listen/Sidebar)

```php
<?php
// templates/partials/job-card-compact.php
?>

<article class="rp-job-card rp-job-card--compact">
    <div class="rp-job-card__content">
        <h4 class="rp-job-card__title">
            <a href="<?php echo esc_url( get_permalink( $job ) ); ?>">
                <?php echo esc_html( $job->post_title ); ?>
            </a>
        </h4>
        <span class="rp-job-card__location"><?php echo esc_html( $location ); ?></span>
    </div>
    
    <?php if ( ! empty( $badges ) ) : ?>
        <span class="rp-badge rp-badge--<?php echo esc_attr( $badges[0]['color'] ); ?> rp-badge--small">
            <?php echo esc_html( $badges[0]['label'] ); ?>
        </span>
    <?php endif; ?>
</article>
```

---

## 4. CSS Styling

```css
/* Job Card Styles */

.rp-job-card {
    --card-padding: var(--rp-spacing-lg);
    --card-radius: var(--rp-border-radius-lg);
    --card-shadow: var(--rp-shadow);
    --card-shadow-hover: var(--rp-shadow-lg);
    
    position: relative;
    display: flex;
    flex-direction: column;
    padding: var(--card-padding);
    background: var(--rp-surface);
    border: 1px solid var(--rp-border);
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    transition: box-shadow var(--rp-transition), transform var(--rp-transition);
}

.rp-job-card:hover {
    box-shadow: var(--card-shadow-hover);
    transform: translateY(-2px);
}

/* Badges */
.rp-job-card__badges {
    display: flex;
    flex-wrap: wrap;
    gap: var(--rp-spacing-xs);
    margin-bottom: var(--rp-spacing-md);
}

.rp-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25em 0.75em;
    font-size: var(--rp-font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    border-radius: var(--rp-border-radius-full);
}

.rp-badge--green {
    background: var(--rp-success-light);
    color: var(--rp-success);
}

.rp-badge--blue {
    background: var(--rp-info-light);
    color: var(--rp-info);
}

.rp-badge--purple {
    background: #f3e8ff;
    color: #7c3aed;
}

.rp-badge--red {
    background: var(--rp-error-light);
    color: var(--rp-error);
}

.rp-badge--orange {
    background: var(--rp-warning-light);
    color: var(--rp-warning);
}

.rp-badge--teal {
    background: #ccfbf1;
    color: #0d9488;
}

/* Title */
.rp-job-card__title {
    margin: 0 0 var(--rp-spacing-sm);
    font-size: var(--rp-font-size-xl);
    font-weight: 600;
    line-height: 1.3;
}

.rp-job-card__title a {
    color: var(--rp-text);
    text-decoration: none;
}

.rp-job-card__title a:hover {
    color: var(--rp-primary);
}

/* Meta */
.rp-job-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--rp-spacing-md);
    margin-bottom: var(--rp-spacing-md);
    color: var(--rp-text-muted);
    font-size: var(--rp-font-size-sm);
}

.rp-job-card__location,
.rp-job-card__employment {
    display: flex;
    align-items: center;
    gap: var(--rp-spacing-xs);
}

.rp-job-card__meta .rp-icon {
    width: 1em;
    height: 1em;
    fill: currentColor;
}

/* Excerpt */
.rp-job-card__excerpt {
    flex: 1;
    margin-bottom: var(--rp-spacing-lg);
    color: var(--rp-text-muted);
    font-size: var(--rp-font-size-sm);
    line-height: 1.6;
}

/* Link */
.rp-job-card__link {
    display: inline-flex;
    align-items: center;
    gap: var(--rp-spacing-xs);
    margin-top: auto;
    color: var(--rp-primary);
    font-weight: 500;
    text-decoration: none;
    transition: gap var(--rp-transition);
}

.rp-job-card__link:hover {
    gap: var(--rp-spacing-sm);
}

.rp-job-card__link .rp-icon {
    width: 1.25em;
    height: 1.25em;
    fill: currentColor;
}

/* Grid Layout */
.rp-jobs-grid {
    display: grid;
    gap: var(--rp-spacing-lg);
}

.rp-jobs-grid--cols-1 { grid-template-columns: 1fr; }
.rp-jobs-grid--cols-2 { grid-template-columns: repeat(2, 1fr); }
.rp-jobs-grid--cols-3 { grid-template-columns: repeat(3, 1fr); }
.rp-jobs-grid--cols-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 1024px) {
    .rp-jobs-grid--cols-4 { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .rp-jobs-grid--cols-3,
    .rp-jobs-grid--cols-4 { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 480px) {
    .rp-jobs-grid--cols-2,
    .rp-jobs-grid--cols-3,
    .rp-jobs-grid--cols-4 { grid-template-columns: 1fr; }
}

/* Tab Navigation */
.rp-jobs-tabs__nav {
    display: flex;
    flex-wrap: wrap;
    gap: var(--rp-spacing-sm);
    margin-bottom: var(--rp-spacing-xl);
    padding-bottom: var(--rp-spacing-md);
    border-bottom: 2px solid var(--rp-border);
}

.rp-jobs-tabs__tab {
    padding: var(--rp-spacing-sm) var(--rp-spacing-md);
    background: transparent;
    border: none;
    border-radius: var(--rp-border-radius);
    color: var(--rp-text-muted);
    font-weight: 500;
    cursor: pointer;
    transition: all var(--rp-transition);
}

.rp-jobs-tabs__tab:hover {
    background: var(--rp-background-alt);
    color: var(--rp-text);
}

.rp-jobs-tabs__tab--active {
    background: var(--rp-primary);
    color: var(--rp-primary-contrast);
}

.rp-jobs-tabs__tab--active:hover {
    background: var(--rp-primary-hover);
    color: var(--rp-primary-contrast);
}
```

---

## 5. PHP Helper Functions

Für Theme-Entwickler, die lieber PHP verwenden:

```php
<?php
// src/Functions/template-functions.php

/**
 * Jobs abfragen
 */
function rp_get_jobs( array $args = [] ): array {
    $defaults = [
        'limit'       => 12,
        'category'    => '',
        'location'    => '',
        'featured'    => false,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ];
    
    $args = wp_parse_args( $args, $defaults );
    
    $query_args = [
        'post_type'      => 'job_listing',
        'posts_per_page' => $args['limit'],
        'post_status'    => 'publish',
        'orderby'        => $args['orderby'],
        'order'          => $args['order'],
    ];
    
    // Taxonomie-Filter
    $tax_query = [];
    
    if ( ! empty( $args['category'] ) ) {
        $tax_query[] = [
            'taxonomy' => 'job_category',
            'field'    => 'slug',
            'terms'    => $args['category'],
        ];
    }
    
    if ( ! empty( $args['location'] ) ) {
        $tax_query[] = [
            'taxonomy' => 'job_location',
            'field'    => 'slug',
            'terms'    => $args['location'],
        ];
    }
    
    if ( ! empty( $tax_query ) ) {
        $query_args['tax_query'] = $tax_query;
    }
    
    // Featured Filter
    if ( $args['featured'] ) {
        $query_args['meta_query'] = [
            [
                'key'   => '_rp_featured',
                'value' => '1',
            ],
        ];
    }
    
    $query = new WP_Query( $query_args );
    
    return $query->posts;
}

/**
 * Job-Karte rendern
 */
function rp_render_job_card( WP_Post $job, string $style = 'default' ): void {
    $template = rp_locate_template( 'partials/job-card.php' );
    
    if ( $template ) {
        include $template;
    }
}

/**
 * Job-Grid rendern
 */
function rp_render_jobs( array $args = [] ): void {
    $jobs = rp_get_jobs( $args );
    $columns = $args['columns'] ?? 3;
    $style = $args['card_style'] ?? 'default';
    
    if ( empty( $jobs ) ) {
        echo '<p class="rp-no-jobs">' . esc_html__( 'Keine Stellen gefunden.', 'recruiting-playbook' ) . '</p>';
        return;
    }
    
    echo '<div class="rp-jobs-grid rp-jobs-grid--cols-' . esc_attr( $columns ) . '">';
    
    foreach ( $jobs as $job ) {
        rp_render_job_card( $job, $style );
    }
    
    echo '</div>';
}

/**
 * Bewerbungsformular rendern
 */
function rp_render_application_form( int $job_id = 0, array $args = [] ): void {
    if ( ! $job_id && is_singular( 'job_listing' ) ) {
        $job_id = get_the_ID();
    }
    
    $template = rp_locate_template( 'partials/application-form.php' );
    
    if ( $template ) {
        include $template;
    }
}

/**
 * Job-Anzahl
 */
function rp_get_job_count( array $args = [] ): int {
    $jobs = rp_get_jobs( array_merge( $args, [ 'limit' => -1 ] ) );
    return count( $jobs );
}
```

### Verwendung in Theme

```php
<?php
// In einem Theme-Template

// Alle Jobs als Grid
rp_render_jobs( [
    'limit'   => 6,
    'columns' => 3,
] );

// Nur Pflege-Jobs
rp_render_jobs( [
    'category' => 'pflege',
    'limit'    => 4,
] );

// Featured Jobs
$featured = rp_get_jobs( [ 'featured' => true, 'limit' => 3 ] );
foreach ( $featured as $job ) {
    rp_render_job_card( $job, 'featured' );
}

// Job-Counter
echo sprintf( 
    __( 'Wir haben aktuell %d offene Stellen!', 'theme' ), 
    rp_get_job_count() 
);
?>
```

---

## Zusammenfassung: Was ist wo verfügbar?

| Element | MVP | Pro | AI-Addon | Avada | Elementor | Gutenberg |
|---------|:---:|:---:|:--------:|:-----:|:---------:|:---------:|
| Job Grid | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Job Tabs | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Job Slider | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Featured Jobs | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Latest Jobs | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Job Search | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Application Form | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Job Counter | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Job Categories | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **🔥 AI Job-Match** | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |
| **🔥 AI Job-Finder** | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |
| **🔥 AI Chancen-Check** | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |

---

## Typische Seitenaufbauten

### Startseite (mit AI)

```
┌─────────────────────────────────────────────────────────────────┐
│  HERO                                                           │
│  "Finden Sie Ihren Traumjob in der Pflege"                     │
│  [Alle Stellen] [KI-Job-Finder]                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  🔥 KI-JOB-FINDER                                       │   │
│  │  "Laden Sie Ihren Lebenslauf hoch - wir finden          │   │
│  │   die perfekten Stellen für Sie!"                        │   │
│  │  [📄 Upload] [Passende Stellen finden]                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ── TOP-JOBANGEBOTE ──────────────────────────────────────     │
│                                                                 │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐                          │
│  │ Job 1   │ │ Job 2   │ │ Job 3   │                          │
│  └─────────┘ └─────────┘ └─────────┘                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Stellen-Einzelseite (mit AI)

```
┌─────────────────────────────────────────────────────────────────┐
│  [Badges] Fachkrankenpfleger Intensiv (m/w/d)                  │
│  📍 Bielefeld | ⏰ Vollzeit | 💰 ab 35€/Std                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Beschreibung, Aufgaben, Anforderungen, Benefits...            │
│                                                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  🔥 KI-QUALIFIKATIONSCHECK                              │   │
│  │                                                          │   │
│  │  Finden Sie heraus, ob diese Stelle zu Ihnen passt!     │   │
│  │                                                          │   │
│  │  [📄 Lebenslauf hochladen]                              │   │
│  │  [Qualifikation prüfen]                                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ── ODER DIREKT BEWERBEN ─────────────────────────────────     │
│                                                                 │
│  [Bewerbungsformular]                                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Karriere-Seite (mit AI)

```
┌─────────────────────────────────────────────────────────────────┐
│  # Karriere bei [Unternehmen]                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────────────┐  ┌───────────────────────┐         │
│  │  🔥 KI-JOB-FINDER     │  │  📊 KI-CHANCEN-CHECK  │         │
│  │                       │  │                        │         │
│  │  Passende Stellen     │  │  Einstellungschancen   │         │
│  │  automatisch finden   │  │  berechnen             │         │
│  │                       │  │                        │         │
│  │  [Start]              │  │  [Start]               │         │
│  └───────────────────────┘  └───────────────────────┘         │
│                                                                 │
│  ── ALLE STELLENANGEBOTE ─────────────────────────────────     │
│                                                                 │
│  [Filter-Tabs: Alle | Pflege | Verwaltung | ...]              │
│                                                                 │
│  [Job-Grid]                                                     │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

*Letzte Aktualisierung: Januar 2025*

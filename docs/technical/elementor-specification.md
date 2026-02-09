# Elementor Integration: Technische Spezifikation

> **Pro-Feature: Native Elementor Widgets**
> Alle Recruiting Playbook Shortcodes als native Elementor Widgets
>
> **Status:** Geplant
> **Priorität:** Hoch (zweitwichtigster Page Builder nach Avada)
> **Branch:** `feature/elementor-integration`

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Widget-Registrierung](#3-widget-registrierung)
4. [Widgets im Detail](#4-widgets-im-detail)
5. [Control-Typen](#5-control-typen)
6. [Editor-Vorschau](#6-editor-vorschau)
7. [Design & Branding Integration](#7-design--branding-integration)
8. [Testing](#8-testing)
9. [Implementierungs-Reihenfolge](#9-implementierungs-reihenfolge)

---

## 1. Übersicht

### Zielsetzung

Native Elementor Widgets bieten:
- **Drag & Drop** im Elementor Editor
- **Visuelle Parameter** mit Live-Preview im Editor-Panel
- **Inline-Vorschau** im Editor (via `render()` + `content_template()`)
- **Konsistente UX** mit anderen Elementor-Widgets
- **Kategorie-Gruppierung** für einfaches Auffinden im Widget-Panel

### Feature-Gating

```php
// Elementor Integration ist ein PRO-Feature
if ( ! function_exists( 'rp_can' ) || ! rp_can( 'elementor_integration' ) ) {
    return;
}

// Prüfen ob Elementor aktiv
if ( ! did_action( 'elementor/loaded' ) ) {
    return;
}
```

### Strategie: Shortcode-Wrapper

Die Elementor-Integration **wrapped die bestehenden Shortcodes** - kein neuer Render-Code nötig:

```
┌─────────────────────────────────────────────────────────────────┐
│                   INTEGRATION LAYERS                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    ELEMENTOR                             │   │
│  │     \Elementor\Widget_Base registriert Widgets           │   │
│  │                        │                                 │   │
│  │              render() → do_shortcode()                   │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                     SHORTCODES                           │   │
│  │         (Bestehende PHP-Render-Logik)                    │   │
│  │    rp_jobs, rp_job_search, rp_application_form, etc.     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Widget-Übersicht

| Elementor Widget | Shortcode | Beschreibung | Feature |
|------------------|-----------|--------------|---------|
| RP: Stellenliste | `rp_jobs` | Job-Grid mit Filtern | Pro |
| RP: Stellensuche | `rp_job_search` | Suchformular mit Ergebnissen | Pro |
| RP: Stellen-Zähler | `rp_job_count` | Dynamischer Counter | Pro |
| RP: Featured Jobs | `rp_featured_jobs` | Hervorgehobene Stellen | Pro |
| RP: Neueste Stellen | `rp_latest_jobs` | Aktuellste Jobs | Pro |
| RP: Job-Kategorien | `rp_job_categories` | Kategorie-Karten | Pro |
| RP: Bewerbungsformular | `rp_application_form` | Mehrstufiges Formular | Pro |
| RP: KI-Job-Finder | `rp_ai_job_finder` | CV-Upload + Matching | AI-Addon |
| RP: KI-Job-Match | `rp_ai_job_match` | "Passe ich?" Button | AI-Addon |

### User Stories

| Als | möchte ich | damit |
|-----|-----------|-------|
| Elementor-Nutzer | Stellenlisten per Drag & Drop einfügen | ich keine Shortcodes kennen muss |
| Content-Editor | Widget-Einstellungen visuell anpassen | ich sofort sehe wie es aussieht |
| Agentur | Recruiting Playbook in Elementor-Projekten nutzen | meine Kunden Jobs verwalten können |

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   └── Integrations/
│       └── Elementor/
│           ├── ElementorIntegration.php   # Loader, prüft Elementor-Aktivierung
│           ├── WidgetLoader.php           # Registriert alle Widgets
│           └── Widgets/
│               ├── AbstractWidget.php     # Basisklasse (extends Widget_Base)
│               ├── JobGrid.php            # RP: Stellenliste
│               ├── JobSearch.php          # RP: Stellensuche
│               ├── JobCount.php           # RP: Stellen-Zähler
│               ├── FeaturedJobs.php       # RP: Featured Jobs
│               ├── LatestJobs.php         # RP: Neueste Stellen
│               ├── JobCategories.php      # RP: Job-Kategorien
│               ├── ApplicationForm.php    # RP: Bewerbungsformular
│               ├── AiJobFinder.php        # RP: KI-Job-Finder
│               └── AiJobMatch.php         # RP: KI-Job-Match
│
├── assets/
│   └── src/
│       └── css/
│           └── elementor-editor.css       # Editor-spezifische Styles
│
└── languages/
    └── recruiting-playbook-de_DE.po       # Übersetzungen
```

### Abhängigkeiten

| Abhängigkeit | Beschreibung |
|--------------|--------------|
| Elementor | Ab Version 3.0 |
| Elementor Pro | Nicht erforderlich (Widgets funktionieren auch mit Free) |
| Recruiting Playbook Pro | Lizenz erforderlich |

### Integration in Plugin-Bootstrap

```php
// src/Core/Plugin.php

private function loadIntegrations(): void {
    // Elementor Integration
    if ( did_action( 'elementor/loaded' ) ) {
        $elementor = new \RecruitingPlaybook\Integrations\Elementor\ElementorIntegration();
        $elementor->register();
    }
}
```

---

## 3. Widget-Registrierung

### ElementorIntegration.php

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor Integration
 *
 * Registriert alle Recruiting Playbook Widgets für Elementor.
 */
class ElementorIntegration {

    /**
     * Integration initialisieren
     */
    public function register(): void {
        // Pro-Feature Check
        if ( ! function_exists( 'rp_can' ) || ! rp_can( 'elementor_integration' ) ) {
            return;
        }

        // Elementor Check
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        // Minimum-Version Check
        if ( ! version_compare( ELEMENTOR_VERSION, '3.0.0', '>=' ) ) {
            return;
        }

        // Widget-Kategorie registrieren
        add_action( 'elementor/elements/categories_registered', [ $this, 'registerCategory' ] );

        // Widgets registrieren
        add_action( 'elementor/widgets/register', [ $this, 'registerWidgets' ] );

        // Editor-Assets laden
        add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueueEditorAssets' ] );
    }

    /**
     * Widget-Kategorie registrieren
     */
    public function registerCategory( \Elementor\Elements_Manager $elements_manager ): void {
        $elements_manager->add_category(
            'recruiting-playbook',
            [
                'title' => esc_html__( 'Recruiting Playbook', 'recruiting-playbook' ),
                'icon'  => 'eicon-user-circle-o',
            ]
        );
    }

    /**
     * Widgets registrieren
     */
    public function registerWidgets( \Elementor\Widgets_Manager $widgets_manager ): void {
        $loader = new WidgetLoader( $widgets_manager );
        $loader->registerAll();
    }

    /**
     * Editor-Assets laden
     */
    public function enqueueEditorAssets(): void {
        wp_enqueue_style(
            'rp-elementor-editor',
            RP_PLUGIN_URL . 'assets/css/elementor-editor.css',
            [],
            RP_VERSION
        );
    }
}
```

### WidgetLoader.php

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Lädt und registriert alle Elementor Widgets
 */
class WidgetLoader {

    /**
     * Alle verfügbaren Widgets
     */
    private array $widgets = [
        'JobGrid',
        'JobSearch',
        'JobCount',
        'FeaturedJobs',
        'LatestJobs',
        'JobCategories',
        'ApplicationForm',
        'AiJobFinder',
        'AiJobMatch',
    ];

    private \Elementor\Widgets_Manager $widgets_manager;

    public function __construct( \Elementor\Widgets_Manager $widgets_manager ) {
        $this->widgets_manager = $widgets_manager;
    }

    /**
     * Alle Widgets registrieren
     */
    public function registerAll(): void {
        foreach ( $this->widgets as $widget ) {
            $this->registerWidget( $widget );
        }
    }

    /**
     * Einzelnes Widget registrieren
     */
    private function registerWidget( string $widget ): void {
        // AI-Widgets nur wenn AI-Addon aktiv
        if ( str_starts_with( $widget, 'Ai' ) ) {
            if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
                return;
            }
        }

        $class = __NAMESPACE__ . '\\Widgets\\' . $widget;

        if ( class_exists( $class ) ) {
            $this->widgets_manager->register( new $class() );
        }
    }
}
```

### Widget-Basisklasse

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Abstrakte Basisklasse für Elementor Widgets
 *
 * Alle RP-Widgets wrappen bestehende Shortcodes.
 */
abstract class AbstractWidget extends Widget_Base {

    /**
     * Widget-Kategorie
     */
    public function get_categories(): array {
        return [ 'recruiting-playbook' ];
    }

    /**
     * Shortcode-Name (wird von Subklassen definiert)
     */
    abstract protected function get_shortcode_name(): string;

    /**
     * Shortcode-Attribute aus Widget-Settings ableiten
     */
    protected function get_shortcode_atts(): array {
        $settings = $this->get_settings_for_display();
        $atts     = [];

        foreach ( $this->get_shortcode_mapping() as $setting_key => $shortcode_attr ) {
            if ( isset( $settings[ $setting_key ] ) && $settings[ $setting_key ] !== '' ) {
                $atts[ $shortcode_attr ] = $settings[ $setting_key ];
            }
        }

        return $atts;
    }

    /**
     * Mapping: Elementor Setting Key → Shortcode Attribute
     *
     * Subklassen überschreiben dies.
     * Default: Setting-Key = Shortcode-Attribut (1:1)
     */
    protected function get_shortcode_mapping(): array {
        return [];
    }

    /**
     * Shortcode-String zusammenbauen
     */
    protected function build_shortcode(): string {
        $name = $this->get_shortcode_name();
        $atts = $this->get_shortcode_atts();

        if ( empty( $atts ) ) {
            return "[{$name}]";
        }

        $pairs = [];
        foreach ( $atts as $key => $value ) {
            $pairs[] = sprintf( '%s="%s"', $key, esc_attr( (string) $value ) );
        }

        return "[{$name} " . implode( ' ', $pairs ) . ']';
    }

    /**
     * Frontend-Render: Shortcode ausführen
     */
    protected function render(): void {
        echo do_shortcode( $this->build_shortcode() );
    }

    /**
     * Editor-Vorschau (JS-Template)
     *
     * Zeigt eine Platzhalter-Box im Editor.
     * Override in Subklassen für spezifische Vorschau.
     */
    protected function content_template(): void {
        ?>
        <div class="rp-elementor-preview">
            <div class="rp-elementor-preview-icon">
                <i class="<?php echo esc_attr( $this->get_icon() ); ?>"></i>
            </div>
            <div class="rp-elementor-preview-title">
                <?php echo esc_html( $this->get_title() ); ?>
            </div>
            <div class="rp-elementor-preview-info">
                <?php esc_html_e( 'Vorschau wird im Frontend angezeigt.', 'recruiting-playbook' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Taxonomie-Optionen laden (für Controls)
     */
    protected function getTaxonomyOptions( string $taxonomy ): array {
        $terms   = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );
        $options = [ '' => esc_html__( '— Alle —', 'recruiting-playbook' ) ];

        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $options[ $term->slug ] = $term->name;
            }
        }

        return $options;
    }

    /**
     * Job-Optionen laden (für Controls)
     */
    protected function getJobOptions(): array {
        $jobs = get_posts( [
            'post_type'      => 'job_listing',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $options = [ '' => esc_html__( '— Automatisch —', 'recruiting-playbook' ) ];

        foreach ( $jobs as $job ) {
            $options[ (string) $job->ID ] = $job->post_title;
        }

        return $options;
    }
}
```

---

## 4. Widgets im Detail

### 4.1 RP: Stellenliste (JobGrid)

**Shortcode:** `rp_jobs`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class JobGrid extends AbstractWidget {

    public function get_name(): string {
        return 'rp-job-grid';
    }

    public function get_title(): string {
        return esc_html__( 'RP: Stellenliste', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-posts-grid';
    }

    public function get_keywords(): array {
        return [ 'jobs', 'stellen', 'grid', 'liste', 'recruiting' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_jobs';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'limit'    => 'limit',
            'columns'  => 'columns',
            'category' => 'category',
            'location' => 'location',
            'type'     => 'type',
            'featured' => 'featured',
            'orderby'  => 'orderby',
            'order'    => 'order',
        ];
    }

    protected function register_controls(): void {

        // --- Tab: Allgemein ---
        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'   => esc_html__( 'Anzahl Stellen', 'recruiting-playbook' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 10,
                'min'     => 1,
                'max'     => 50,
                'step'    => 1,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'   => esc_html__( 'Spalten', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '2',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
            ]
        );

        $this->end_controls_section();

        // --- Tab: Filter ---
        $this->start_controls_section(
            'section_filter',
            [
                'label' => esc_html__( 'Filter', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'category',
            [
                'label'   => esc_html__( 'Kategorie', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->getTaxonomyOptions( 'job_category' ),
            ]
        );

        $this->add_control(
            'location',
            [
                'label'   => esc_html__( 'Standort', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->getTaxonomyOptions( 'job_location' ),
            ]
        );

        $this->add_control(
            'type',
            [
                'label'   => esc_html__( 'Beschäftigungsart', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->getTaxonomyOptions( 'employment_type' ),
            ]
        );

        $this->add_control(
            'featured',
            [
                'label'        => esc_html__( 'Nur Featured', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => '',
            ]
        );

        $this->end_controls_section();

        // --- Tab: Sortierung ---
        $this->start_controls_section(
            'section_sorting',
            [
                'label' => esc_html__( 'Sortierung', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => esc_html__( 'Sortieren nach', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date'  => esc_html__( 'Datum', 'recruiting-playbook' ),
                    'title' => esc_html__( 'Titel', 'recruiting-playbook' ),
                    'rand'  => esc_html__( 'Zufällig', 'recruiting-playbook' ),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label'   => esc_html__( 'Reihenfolge', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => esc_html__( 'Absteigend', 'recruiting-playbook' ),
                    'ASC'  => esc_html__( 'Aufsteigend', 'recruiting-playbook' ),
                ],
            ]
        );

        $this->end_controls_section();
    }
}
```

---

### 4.2 RP: Stellensuche (JobSearch)

**Shortcode:** `rp_job_search`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class JobSearch extends AbstractWidget {

    public function get_name(): string {
        return 'rp-job-search';
    }

    public function get_title(): string {
        return esc_html__( 'RP: Stellensuche', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-search';
    }

    public function get_keywords(): array {
        return [ 'suche', 'search', 'jobs', 'stellen', 'filter' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_job_search';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'show_search'   => 'show_search',
            'show_category' => 'show_category',
            'show_location' => 'show_location',
            'show_type'     => 'show_type',
            'limit'         => 'limit',
            'columns'       => 'columns',
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label'        => esc_html__( 'Suchfeld anzeigen', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'show_category',
            [
                'label'        => esc_html__( 'Kategorie-Filter', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'show_location',
            [
                'label'        => esc_html__( 'Standort-Filter', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'show_type',
            [
                'label'        => esc_html__( 'Beschäftigungsart-Filter', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'     => esc_html__( 'Stellen pro Seite', 'recruiting-playbook' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 10,
                'min'       => 1,
                'max'       => 50,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'   => esc_html__( 'Spalten', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                ],
            ]
        );

        $this->end_controls_section();
    }
}
```

---

### 4.3 RP: Stellen-Zähler (JobCount)

**Shortcode:** `rp_job_count`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class JobCount extends AbstractWidget {

    public function get_name(): string {
        return 'rp-job-count';
    }

    public function get_title(): string {
        return esc_html__( 'RP: Stellen-Zähler', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-counter';
    }

    public function get_keywords(): array {
        return [ 'zähler', 'counter', 'anzahl', 'jobs', 'stellen' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_job_count';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'category' => 'category',
            'location' => 'location',
            'format'   => 'format',
            'singular' => 'singular',
            'zero'     => 'zero',
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'category',
            [
                'label'   => esc_html__( 'Kategorie', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->getTaxonomyOptions( 'job_category' ),
            ]
        );

        $this->add_control(
            'location',
            [
                'label'   => esc_html__( 'Standort', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->getTaxonomyOptions( 'job_location' ),
            ]
        );

        $this->add_control(
            'format',
            [
                'label'       => esc_html__( 'Format (Mehrzahl)', 'recruiting-playbook' ),
                'description' => esc_html__( 'Verwende {count} als Platzhalter.', 'recruiting-playbook' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '{count} offene Stellen',
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'singular',
            [
                'label'   => esc_html__( 'Format (Einzahl)', 'recruiting-playbook' ),
                'type'    => Controls_Manager::TEXT,
                'default' => '{count} offene Stelle',
            ]
        );

        $this->add_control(
            'zero',
            [
                'label'   => esc_html__( 'Format (Null)', 'recruiting-playbook' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'Keine offenen Stellen',
            ]
        );

        $this->end_controls_section();
    }
}
```

---

### 4.4 RP: Featured Jobs (FeaturedJobs)

**Shortcode:** `rp_featured_jobs`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class FeaturedJobs extends AbstractWidget {

    public function get_name(): string {
        return 'rp-featured-jobs';
    }

    public function get_title(): string {
        return esc_html__( 'RP: Featured Jobs', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-star';
    }

    public function get_keywords(): array {
        return [ 'featured', 'hervorgehoben', 'jobs', 'stellen', 'highlight' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_featured_jobs';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'limit'        => 'limit',
            'columns'      => 'columns',
            'title'        => 'title',
            'show_excerpt' => 'show_excerpt',
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'   => esc_html__( 'Anzahl', 'recruiting-playbook' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 3,
                'min'     => 1,
                'max'     => 12,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'   => esc_html__( 'Spalten', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => esc_html__( 'Überschrift', 'recruiting-playbook' ),
                'description' => esc_html__( 'Optional: Überschrift über den Featured Jobs.', 'recruiting-playbook' ),
                'type'    => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label'        => esc_html__( 'Auszug anzeigen', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->end_controls_section();
    }
}
```

---

### 4.5 RP: Neueste Stellen (LatestJobs)

**Shortcode:** `rp_latest_jobs`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class LatestJobs extends AbstractWidget {

    public function get_name(): string {
        return 'rp-latest-jobs';
    }

    public function get_title(): string {
        return esc_html__( 'RP: Neueste Stellen', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-clock-o';
    }

    public function get_keywords(): array {
        return [ 'neueste', 'latest', 'jobs', 'stellen', 'aktuell' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_latest_jobs';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'limit'        => 'limit',
            'columns'      => 'columns',
            'title'        => 'title',
            'category'     => 'category',
            'show_excerpt' => 'show_excerpt',
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'   => esc_html__( 'Anzahl', 'recruiting-playbook' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 5,
                'min'     => 1,
                'max'     => 20,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'       => esc_html__( 'Spalten', 'recruiting-playbook' ),
                'description' => esc_html__( '1 = Listendarstellung', 'recruiting-playbook' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => '1',
                'options'     => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                ],
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => esc_html__( 'Überschrift', 'recruiting-playbook' ),
                'type'    => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'category',
            [
                'label'   => esc_html__( 'Kategorie', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->getTaxonomyOptions( 'job_category' ),
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label'        => esc_html__( 'Auszug anzeigen', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => '',
            ]
        );

        $this->end_controls_section();
    }
}
```

---

### 4.6 RP: Job-Kategorien (JobCategories)

**Shortcode:** `rp_job_categories`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class JobCategories extends AbstractWidget {

    public function get_name(): string {
        return 'rp-job-categories';
    }

    public function get_title(): string {
        return esc_html__( 'RP: Job-Kategorien', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-folder';
    }

    public function get_keywords(): array {
        return [ 'kategorien', 'categories', 'jobs', 'bereiche' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_job_categories';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'columns'    => 'columns',
            'show_count' => 'show_count',
            'hide_empty' => 'hide_empty',
            'orderby'    => 'orderby',
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'   => esc_html__( 'Spalten', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '4',
                'options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
            ]
        );

        $this->add_control(
            'show_count',
            [
                'label'        => esc_html__( 'Anzahl anzeigen', 'recruiting-playbook' ),
                'description'  => esc_html__( 'Zeigt die Anzahl der Jobs pro Kategorie.', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'hide_empty',
            [
                'label'        => esc_html__( 'Leere verstecken', 'recruiting-playbook' ),
                'description'  => esc_html__( 'Kategorien ohne Jobs ausblenden.', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => esc_html__( 'Sortierung', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'name',
                'options' => [
                    'name'  => esc_html__( 'Name', 'recruiting-playbook' ),
                    'count' => esc_html__( 'Anzahl', 'recruiting-playbook' ),
                ],
            ]
        );

        $this->end_controls_section();
    }
}
```

---

### 4.7 RP: Bewerbungsformular (ApplicationForm)

**Shortcode:** `rp_application_form`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class ApplicationForm extends AbstractWidget {

    public function get_name(): string {
        return 'rp-application-form';
    }

    public function get_title(): string {
        return esc_html__( 'RP: Bewerbungsformular', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-form-horizontal';
    }

    public function get_keywords(): array {
        return [ 'bewerbung', 'formular', 'application', 'form', 'bewerben' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_application_form';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'job_id'        => 'job_id',
            'title'         => 'title',
            'show_job_title' => 'show_job_title',
            'show_progress' => 'show_progress',
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'job_id',
            [
                'label'       => esc_html__( 'Stelle', 'recruiting-playbook' ),
                'description' => esc_html__( 'Leer = automatisch erkennen (auf Stellenseiten).', 'recruiting-playbook' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => '',
                'options'     => $this->getJobOptions(),
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => esc_html__( 'Überschrift', 'recruiting-playbook' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'Jetzt bewerben',
            ]
        );

        $this->add_control(
            'show_job_title',
            [
                'label'        => esc_html__( 'Stellentitel anzeigen', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->add_control(
            'show_progress',
            [
                'label'        => esc_html__( 'Fortschrittsanzeige', 'recruiting-playbook' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
                'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
                'return_value' => 'true',
                'default'      => 'true',
            ]
        );

        $this->end_controls_section();
    }
}
```

---

### 4.8 RP: KI-Job-Finder (AiJobFinder)

**Shortcode:** `rp_ai_job_finder`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class AiJobFinder extends AbstractWidget {

    public function get_name(): string {
        return 'rp-ai-job-finder';
    }

    public function get_title(): string {
        return esc_html__( 'RP: KI-Job-Finder', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-search-bold';
    }

    public function get_keywords(): array {
        return [ 'ki', 'ai', 'job', 'finder', 'lebenslauf', 'cv', 'matching' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_ai_job_finder';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'title'    => 'title',
            'subtitle' => 'subtitle',
            'limit'    => 'limit',
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => esc_html__( 'Überschrift', 'recruiting-playbook' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'Finde deinen Traumjob',
            ]
        );

        $this->add_control(
            'subtitle',
            [
                'label'   => esc_html__( 'Untertitel', 'recruiting-playbook' ),
                'type'    => Controls_Manager::TEXT,
                'default' => '',
            ]
        );

        $this->add_control(
            'limit',
            [
                'label'       => esc_html__( 'Max. Vorschläge', 'recruiting-playbook' ),
                'description' => esc_html__( 'Maximale Anzahl der KI-Vorschläge.', 'recruiting-playbook' ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 5,
                'min'         => 1,
                'max'         => 10,
            ]
        );

        $this->end_controls_section();
    }
}
```

---

### 4.9 RP: KI-Job-Match (AiJobMatch)

**Shortcode:** `rp_ai_job_match`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class AiJobMatch extends AbstractWidget {

    public function get_name(): string {
        return 'rp-ai-job-match';
    }

    public function get_title(): string {
        return esc_html__( 'RP: KI-Job-Match', 'recruiting-playbook' );
    }

    public function get_icon(): string {
        return 'eicon-check-circle';
    }

    public function get_keywords(): array {
        return [ 'ki', 'ai', 'match', 'passe ich', 'kompatibilität' ];
    }

    protected function get_shortcode_name(): string {
        return 'rp_ai_job_match';
    }

    protected function get_shortcode_mapping(): array {
        return [
            'job_id' => 'job_id',
            'title'  => 'title',
            'style'  => 'style',
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section(
            'section_general',
            [
                'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'job_id',
            [
                'label'       => esc_html__( 'Stelle', 'recruiting-playbook' ),
                'description' => esc_html__( 'Leer = automatisch erkennen.', 'recruiting-playbook' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => '',
                'options'     => $this->getJobOptions(),
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => esc_html__( 'Button-Text', 'recruiting-playbook' ),
                'type'    => Controls_Manager::TEXT,
                'default' => 'Passe ich zu diesem Job?',
            ]
        );

        $this->add_control(
            'style',
            [
                'label'   => esc_html__( 'Button-Stil', 'recruiting-playbook' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    ''        => esc_html__( 'Standard', 'recruiting-playbook' ),
                    'outline' => esc_html__( 'Outline', 'recruiting-playbook' ),
                ],
            ]
        );

        $this->end_controls_section();
    }
}
```

---

## 5. Control-Typen

### Verfügbare Elementor Control-Typen

| Typ | Konstante | Beschreibung | Verwendung |
|-----|-----------|--------------|------------|
| Text | `Controls_Manager::TEXT` | Einzeiliges Textfeld | Überschriften, Labels |
| Textarea | `Controls_Manager::TEXTAREA` | Mehrzeiliges Textfeld | Beschreibungen |
| Number | `Controls_Manager::NUMBER` | Zahlenfeld mit Min/Max | Anzahl, Limits |
| Select | `Controls_Manager::SELECT` | Dropdown | Taxonomien, Optionen |
| Select2 | `Controls_Manager::SELECT2` | Durchsuchbares Multi-Select | Mehrfachauswahl |
| Switcher | `Controls_Manager::SWITCHER` | Toggle (Ja/Nein) | Boolean-Optionen |
| Slider | `Controls_Manager::SLIDER` | Slider mit Einheiten (px, em, %) | Größen, Abstände |
| Color | `Controls_Manager::COLOR` | Farbauswahl | Design-Farben |
| Media | `Controls_Manager::MEDIA` | Medien-Upload | Bilder |
| URL | `Controls_Manager::URL` | Link-Eingabe | URLs |
| Choose | `Controls_Manager::CHOOSE` | Icon-Button-Gruppe | Alignment, Auswahl |

### Mapping: Avada → Elementor

| Avada Param-Typ | Elementor Control | Anmerkung |
|-----------------|-------------------|-----------|
| `textfield` | `Controls_Manager::TEXT` | Direkte Entsprechung |
| `textarea` | `Controls_Manager::TEXTAREA` | Direkte Entsprechung |
| `range` | `Controls_Manager::NUMBER` | NUMBER mit `min`/`max` statt Range-Slider |
| `radio_button_set` | `Controls_Manager::SELECT` oder `SWITCHER` | SWITCHER für Ja/Nein, SELECT für Mehrfachauswahl |
| `select` | `Controls_Manager::SELECT` | Direkte Entsprechung |
| `colorpicker` | `Controls_Manager::COLOR` | Direkte Entsprechung |
| `upload` | `Controls_Manager::MEDIA` | Direkte Entsprechung |
| `link_selector` | `Controls_Manager::URL` | Direkte Entsprechung |
| `checkbox_button_set` | `Controls_Manager::SELECT2` (multiple) | SELECT2 mit `multiple => true` |

### Sections (Tabs)

Controls werden in Sections gruppiert, die als Accordion-Panels im Editor erscheinen:

```php
$this->start_controls_section(
    'section_general',
    [
        'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
        'tab'   => Controls_Manager::TAB_CONTENT, // Content-Tab
    ]
);

$this->add_control( 'limit', [ /* ... */ ] );

$this->end_controls_section();
```

Elementor bietet mehrere Standard-Tabs:
- `TAB_CONTENT` — Inhalt-Einstellungen (Standard)
- `TAB_STYLE` — Design-Einstellungen (Farben, Abstände)
- `TAB_ADVANCED` — Erweitert (automatisch von Elementor bereitgestellt)

---

## 6. Editor-Vorschau

### Render-Strategie

Elementor bietet zwei Render-Methoden:

| Methode | Kontext | Technologie | Beschreibung |
|---------|---------|-------------|--------------|
| `render()` | Frontend + Editor | PHP | Server-seitiges Rendering via `do_shortcode()` |
| `content_template()` | Editor (JS-Vorschau) | JavaScript/Underscore.js | Schnelle Client-seitige Vorschau |

### Ansatz: Server-Side Render

Da unsere Widgets auf Shortcodes basieren, nutzen wir primär `render()` für die Editor-Vorschau. Elementor führt bei Änderungen automatisch einen AJAX-Request aus, der `render()` aufruft.

```php
// In AbstractWidget — wird von allen Widgets geerbt
protected function render(): void {
    echo do_shortcode( $this->build_shortcode() );
}
```

### Fallback: content_template()

Für eine schnelle Platzhalter-Vorschau (bevor der Server-Render geladen wird) definiert `AbstractWidget` ein generisches Template:

```php
protected function content_template(): void {
    ?>
    <div class="rp-elementor-preview">
        <div class="rp-elementor-preview-icon">
            <i class="<?php echo esc_attr( $this->get_icon() ); ?>"></i>
        </div>
        <div class="rp-elementor-preview-title">
            <?php echo esc_html( $this->get_title() ); ?>
        </div>
        <div class="rp-elementor-preview-info">
            <?php esc_html_e( 'Vorschau wird im Frontend angezeigt.', 'recruiting-playbook' ); ?>
        </div>
    </div>
    <?php
}
```

> **Hinweis:** Wenn `render()` vorhanden ist, hat es im Editor Priorität über `content_template()`. Die JS-Vorschau dient nur als schneller Platzhalter.

### Editor-CSS

```css
/* assets/css/elementor-editor.css */

.rp-elementor-preview {
    padding: 20px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
}

.rp-elementor-preview-icon {
    font-size: 32px;
    color: #0073aa;
    margin-bottom: 10px;
}

.rp-elementor-preview-title {
    font-size: 16px;
    font-weight: 600;
    color: #23282d;
}

.rp-elementor-preview-info {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
```

---

## 7. Design & Branding Integration

### CSS-Variablen im Frontend

Die Design & Branding Einstellungen werden automatisch geladen, da die Shortcodes diese bereits nutzen.

### Elementor Global Styles

Die Widgets erben automatisch Elementors globale Typografie- und Farbschemata, da die Shortcode-Ausgabe standard-HTML verwendet.

Optional können Style-Controls für individuelle Anpassungen ergänzt werden:

```php
// Später: Style-Tab für RP-Widgets
$this->start_controls_section(
    'section_style',
    [
        'label' => esc_html__( 'Stil', 'recruiting-playbook' ),
        'tab'   => Controls_Manager::TAB_STYLE,
    ]
);

$this->add_control(
    'primary_color',
    [
        'label'     => esc_html__( 'Primärfarbe', 'recruiting-playbook' ),
        'type'      => Controls_Manager::COLOR,
        'selectors' => [
            '{{WRAPPER}} .rp-card' => 'border-color: {{VALUE}};',
        ],
    ]
);

$this->end_controls_section();
```

---

## 8. Testing

### Manuelle Tests

| Test | Beschreibung |
|------|--------------|
| Widget-Panel | Widgets erscheinen in Kategorie "Recruiting Playbook" |
| Drag & Drop | Widgets können eingefügt werden |
| Controls | Alle Einstellungen funktionieren |
| Editor-Vorschau | Server-Render zeigt korrektes Ergebnis |
| Frontend | Ausgabe ist identisch mit Shortcode |
| Responsive | Layout passt sich an (Elementor Responsive Controls) |
| Keywords | Widgets sind über Suchbegriffe findbar |

### PHPUnit-Tests

```php
// tests/Integrations/Elementor/ElementorIntegrationTest.php

class ElementorIntegrationTest extends TestCase {

    public function test_widgets_registered_when_elementor_active(): void {
        // Mock elementor/loaded action
        $this->assertTrue(
            has_action( 'elementor/widgets/register' )
        );
    }

    public function test_category_registered(): void {
        $this->assertTrue(
            has_action( 'elementor/elements/categories_registered' )
        );
    }

    public function test_widget_returns_correct_shortcode(): void {
        $widget = new \RecruitingPlaybook\Integrations\Elementor\Widgets\JobGrid();
        // Verify shortcode name
        $this->assertEquals( 'rp_jobs', $widget->get_shortcode_name() );
    }
}
```

---

## 9. Implementierungs-Reihenfolge

### Phase 1: Basis-Infrastruktur

1. [ ] **ElementorIntegration.php** — Loader und Checks
2. [ ] **WidgetLoader.php** — Widget-Registrierung
3. [ ] **AbstractWidget.php** — Basisklasse mit Shortcode-Wrapper
4. [ ] **Editor-CSS** — Grundlegende Styles

### Phase 2: Kern-Widgets

5. [ ] **JobGrid.php** — Stellenliste
6. [ ] **JobSearch.php** — Stellensuche
7. [ ] **JobCount.php** — Stellen-Zähler

### Phase 3: Ergänzende Widgets

8. [ ] **FeaturedJobs.php** — Featured Jobs
9. [ ] **LatestJobs.php** — Neueste Stellen
10. [ ] **JobCategories.php** — Kategorie-Übersicht

### Phase 4: Formular & AI

11. [ ] **ApplicationForm.php** — Bewerbungsformular
12. [ ] **AiJobFinder.php** — KI-Job-Finder (AI-Addon)
13. [ ] **AiJobMatch.php** — KI-Job-Match (AI-Addon)

### Phase 5: Polish

14. [ ] **Style-Controls** — TAB_STYLE Einstellungen (Farben, Abstände)
15. [ ] **Dokumentation** — Website-Dokumentation
16. [ ] **Testing** — Manuelle + automatisierte Tests

---

## Entscheidungen

| Frage | Entscheidung |
|-------|--------------|
| Eigener Render-Code? | **Nein** — Shortcode-Wrapper via `do_shortcode()` |
| Editor-Vorschau? | **Server-Side Render** (`render()`) — echte Shortcode-Ausgabe im Editor |
| JS-Vorschau (content_template)? | **Platzhalter** — generischer Placeholder aus AbstractWidget |
| Style-Controls (TAB_STYLE)? | **Später** — nicht für MVP |
| Elementor Pro erforderlich? | **Nein** — funktioniert auch mit Elementor Free |
| Minimum Elementor Version? | **3.0** — stabile Widget-API |

---

## Vergleich: Avada vs. Elementor API

| Konzept | Avada / Fusion Builder | Elementor |
|---------|----------------------|-----------|
| Element-Klasse | `fusion_builder_map()` Array | `\Elementor\Widget_Base` Klasse |
| Registrierung | `fusion_builder_before_init` Hook | `elementor/widgets/register` Hook |
| Parameter | `params` Array mit `type`, `param_name` | `register_controls()` mit `add_control()` |
| Gruppen/Tabs | `group` Key im Param | `start_controls_section()` / `end_controls_section()` |
| Ja/Nein | `radio_button_set` mit `true`/`false` | `Controls_Manager::SWITCHER` |
| Dropdown | `select` mit `value => label` | `Controls_Manager::SELECT` mit `options` |
| Zahlen-Slider | `range` mit `min`/`max` | `Controls_Manager::NUMBER` mit `min`/`max` |
| Kategorie | `fusion_builder_element_categories` Filter | `elementor/elements/categories_registered` Action |
| Icon-Prefix | `fusiona-` | `eicon-` |
| Preview | AJAX-Render (automatisch) | `render()` + `content_template()` |

---

## Referenzen

- [Elementor Widget Development](https://developers.elementor.com/docs/widgets/)
- [Elementor Controls](https://developers.elementor.com/docs/controls/)
- [Elementor Widget Categories](https://developers.elementor.com/docs/widgets/widget-categories/)
- [Bestehende Shortcode-Dokumentation](/docs/shortcodes)
- [Avada Fusion Builder Spezifikation](/docs/technical/avada-fusion-builder-specification.md)

---

*Erstellt: 9. Februar 2026*
*Status: Spezifikation fertig, Implementierung ausstehend*

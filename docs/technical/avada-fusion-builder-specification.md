# Avada / Fusion Builder Integration: Technische Spezifikation

> **Pro-Feature: Native Avada/Fusion Builder Elements**
> Alle Recruiting Playbook Shortcodes als native Fusion Builder Elements für Avada
>
> **Status:** Vollständig implementiert (Februar 2026)
> **Priorität:** MVP (höchste Priorität für Page Builder)
> **Branch:** `feature/avada-integration`

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Element-Registrierung](#3-element-registrierung)
4. [Elements im Detail](#4-elements-im-detail)
5. [Parameter-Typen](#5-parameter-typen)
6. [Editor-Vorschau](#6-editor-vorschau)
7. [Design & Branding Integration](#7-design--branding-integration)
8. [Testing](#8-testing)
9. [Implementierungs-Reihenfolge](#9-implementierungs-reihenfolge)

---

## 1. Übersicht

### Zielsetzung

Native Fusion Builder Elements bieten:
- **Drag & Drop** im Avada Live Builder
- **Visuelle Parameter** statt Shortcode-Syntax
- **AJAX-Vorschau** im Backend-Editor
- **Konsistente UX** mit anderen Avada-Elementen
- **Kategorie-Gruppierung** für einfaches Auffinden

### Feature-Gating

```php
// Avada Integration ist ein PRO-Feature
if ( ! function_exists( 'rp_can' ) || ! rp_can( 'avada_integration' ) ) {
    return;
}

// Prüfen ob Avada/Fusion Builder aktiv
if ( ! class_exists( 'FusionBuilder' ) ) {
    return;
}
```

### Strategie: Shortcode-Wrapper

Die Avada-Integration **wrapped die bestehenden Shortcodes** - kein neuer Render-Code nötig:

```
┌─────────────────────────────────────────────────────────────────┐
│                   INTEGRATION LAYERS                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              AVADA / FUSION BUILDER                      │   │
│  │         fusion_builder_map() registriert Elements        │   │
│  │                        │                                 │   │
│  │                        ▼                                 │   │
│  │              Shortcode-Aufruf                            │   │
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

### Element-Übersicht

| Fusion Element | Shortcode | Beschreibung | Feature |
|----------------|-----------|--------------|---------|
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
| Avada-Nutzer | Stellenlisten per Drag & Drop einfügen | ich keine Shortcodes kennen muss |
| Content-Editor | Element-Einstellungen visuell anpassen | ich sofort sehe wie es aussieht |
| Agentur | Recruiting Playbook in Avada-Projekten nutzen | meine Kunden Jobs verwalten können |

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   └── Integrations/
│       └── Avada/
│           ├── AvadaIntegration.php      # Loader, prüft Avada-Aktivierung
│           ├── ElementLoader.php          # Registriert alle Elements
│           └── Elements/
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
│           └── avada-editor.css           # Editor-spezifische Styles
│
└── languages/
    └── recruiting-playbook-de_DE.po       # Übersetzungen
```

### Abhängigkeiten

| Abhängigkeit | Beschreibung |
|--------------|--------------|
| Avada Theme | Ab Version 7.0 |
| Fusion Builder | Im Avada Theme enthalten |
| Recruiting Playbook Pro | Lizenz erforderlich |

### Integration in Plugin-Bootstrap

```php
// src/Core/Plugin.php

private function loadIntegrations(): void {
    // Avada/Fusion Builder Integration
    if ( class_exists( 'FusionBuilder' ) ) {
        $avada = new \RecruitingPlaybook\Integrations\Avada\AvadaIntegration();
        $avada->register();
    }
}
```

---

## 3. Element-Registrierung

### AvadaIntegration.php

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada;

defined( 'ABSPATH' ) || exit;

/**
 * Avada / Fusion Builder Integration
 *
 * Registriert alle Recruiting Playbook Elements für den Fusion Builder.
 */
class AvadaIntegration {

    /**
     * Integration initialisieren
     */
    public function register(): void {
        // Pro-Feature Check
        if ( ! function_exists( 'rp_can' ) || ! rp_can( 'avada_integration' ) ) {
            return;
        }

        // Fusion Builder Check
        if ( ! class_exists( 'FusionBuilder' ) ) {
            return;
        }

        // Elements registrieren
        add_action( 'fusion_builder_before_init', [ $this, 'registerElements' ], 11 );

        // Element-Kategorie hinzufügen
        add_filter( 'fusion_builder_element_categories', [ $this, 'addCategory' ] );

        // Editor-Assets laden
        add_action( 'fusion_builder_enqueue_scripts', [ $this, 'enqueueEditorAssets' ] );
    }

    /**
     * Elements registrieren
     */
    public function registerElements(): void {
        $loader = new ElementLoader();
        $loader->registerAll();
    }

    /**
     * Kategorie für Element-Picker hinzufügen
     */
    public function addCategory( array $categories ): array {
        $categories['recruiting_playbook'] = esc_attr__( 'Recruiting Playbook', 'recruiting-playbook' );
        return $categories;
    }

    /**
     * Editor-Assets laden
     */
    public function enqueueEditorAssets(): void {
        wp_enqueue_style(
            'rp-avada-editor',
            RP_PLUGIN_URL . 'assets/css/avada-editor.css',
            [],
            RP_VERSION
        );
    }
}
```

### ElementLoader.php

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada;

defined( 'ABSPATH' ) || exit;

/**
 * Lädt und registriert alle Fusion Builder Elements
 */
class ElementLoader {

    /**
     * Alle verfügbaren Elements
     */
    private array $elements = [
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

    /**
     * Alle Elements registrieren
     */
    public function registerAll(): void {
        foreach ( $this->elements as $element ) {
            $this->registerElement( $element );
        }
    }

    /**
     * Einzelnes Element registrieren
     */
    private function registerElement( string $element ): void {
        // AI-Elements nur wenn AI-Addon aktiv
        if ( str_starts_with( $element, 'Ai' ) ) {
            if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
                return;
            }
        }

        $class = __NAMESPACE__ . '\\Elements\\' . $element;

        if ( class_exists( $class ) ) {
            $instance = new $class();
            $instance->register();
        }
    }
}
```

### Element-Basisklasse

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * Abstrakte Basisklasse für Fusion Builder Elements
 */
abstract class AbstractElement {

    /**
     * Element-Konfiguration
     */
    abstract protected function getConfig(): array;

    /**
     * Element registrieren
     */
    public function register(): void {
        fusion_builder_map( $this->getConfig() );
    }

    /**
     * Taxonomie-Optionen laden
     */
    protected function getTaxonomyOptions( string $taxonomy ): array {
        $terms   = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ] );
        $options = [ '' => esc_attr__( '— Alle —', 'recruiting-playbook' ) ];

        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $options[ $term->slug ] = $term->name;
            }
        }

        return $options;
    }

    /**
     * Standard-Icon für RP-Elements
     */
    protected function getIcon(): string {
        return 'fusiona-users';
    }
}
```

---

## 4. Elements im Detail

### 4.1 RP: Stellenliste (JobGrid)

**Shortcode:** `rp_jobs`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class JobGrid extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: Stellenliste', 'recruiting-playbook' ),
            'shortcode'       => 'rp_jobs',
            'icon'            => 'fusiona-th-large',
            'preview'         => RP_PLUGIN_DIR . 'src/Integrations/Avada/previews/job-grid.php',
            'preview_id'      => 'rp-fusion-job-grid-preview',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_jobs',
            'inline_editor'   => false,
            'allow_generator' => true,

            'params' => [
                // Gruppe: Allgemein
                [
                    'type'        => 'range',
                    'heading'     => esc_attr__( 'Anzahl Stellen', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Wie viele Stellen sollen angezeigt werden?', 'recruiting-playbook' ),
                    'param_name'  => 'limit',
                    'value'       => '10',
                    'min'         => '1',
                    'max'         => '50',
                    'step'        => '1',
                    'group'       => esc_attr__( 'Allgemein', 'recruiting-playbook' ),
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Anzahl der Spalten im Grid.', 'recruiting-playbook' ),
                    'param_name'  => 'columns',
                    'default'     => '2',
                    'value'       => [
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                    ],
                    'group'       => esc_attr__( 'Allgemein', 'recruiting-playbook' ),
                ],

                // Gruppe: Filter
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Kategorie', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Nach Kategorie filtern.', 'recruiting-playbook' ),
                    'param_name'  => 'category',
                    'value'       => $this->getTaxonomyOptions( 'job_category' ),
                    'default'     => '',
                    'group'       => esc_attr__( 'Filter', 'recruiting-playbook' ),
                ],
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Standort', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Nach Standort filtern.', 'recruiting-playbook' ),
                    'param_name'  => 'location',
                    'value'       => $this->getTaxonomyOptions( 'job_location' ),
                    'default'     => '',
                    'group'       => esc_attr__( 'Filter', 'recruiting-playbook' ),
                ],
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Beschäftigungsart', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Nach Beschäftigungsart filtern.', 'recruiting-playbook' ),
                    'param_name'  => 'type',
                    'value'       => $this->getTaxonomyOptions( 'employment_type' ),
                    'default'     => '',
                    'group'       => esc_attr__( 'Filter', 'recruiting-playbook' ),
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Nur Featured', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Nur hervorgehobene Stellen anzeigen.', 'recruiting-playbook' ),
                    'param_name'  => 'featured',
                    'default'     => 'false',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                    'group'       => esc_attr__( 'Filter', 'recruiting-playbook' ),
                ],

                // Gruppe: Sortierung
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Sortieren nach', 'recruiting-playbook' ),
                    'param_name'  => 'orderby',
                    'default'     => 'date',
                    'value'       => [
                        'date'  => esc_attr__( 'Datum', 'recruiting-playbook' ),
                        'title' => esc_attr__( 'Titel', 'recruiting-playbook' ),
                        'rand'  => esc_attr__( 'Zufällig', 'recruiting-playbook' ),
                    ],
                    'group'       => esc_attr__( 'Sortierung', 'recruiting-playbook' ),
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
                    'group'       => esc_attr__( 'Sortierung', 'recruiting-playbook' ),
                ],
            ],
        ];
    }
}
```

---

### 4.2 RP: Stellensuche (JobSearch)

**Shortcode:** `rp_job_search`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class JobSearch extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: Stellensuche', 'recruiting-playbook' ),
            'shortcode'       => 'rp_job_search',
            'icon'            => 'fusiona-search',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_job_search',
            'allow_generator' => true,

            'params' => [
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Suchfeld anzeigen', 'recruiting-playbook' ),
                    'param_name'  => 'show_search',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Kategorie-Filter', 'recruiting-playbook' ),
                    'param_name'  => 'show_category',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Standort-Filter', 'recruiting-playbook' ),
                    'param_name'  => 'show_location',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Beschäftigungsart-Filter', 'recruiting-playbook' ),
                    'param_name'  => 'show_type',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
                [
                    'type'        => 'range',
                    'heading'     => esc_attr__( 'Stellen pro Seite', 'recruiting-playbook' ),
                    'param_name'  => 'limit',
                    'value'       => '10',
                    'min'         => '1',
                    'max'         => '50',
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
                    'param_name'  => 'columns',
                    'default'     => '1',
                    'value'       => [
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                    ],
                ],
            ],
        ];
    }
}
```

---

### 4.3 RP: Stellen-Zähler (JobCount)

**Shortcode:** `rp_job_count`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class JobCount extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: Stellen-Zähler', 'recruiting-playbook' ),
            'shortcode'       => 'rp_job_count',
            'icon'            => 'fusiona-counter-box',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_job_count',
            'allow_generator' => true,

            'params' => [
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Kategorie', 'recruiting-playbook' ),
                    'param_name'  => 'category',
                    'value'       => $this->getTaxonomyOptions( 'job_category' ),
                    'default'     => '',
                ],
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Standort', 'recruiting-playbook' ),
                    'param_name'  => 'location',
                    'value'       => $this->getTaxonomyOptions( 'job_location' ),
                    'default'     => '',
                ],
                [
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Format (Mehrzahl)', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Verwende {count} als Platzhalter.', 'recruiting-playbook' ),
                    'param_name'  => 'format',
                    'value'       => '{count} offene Stellen',
                ],
                [
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Format (Einzahl)', 'recruiting-playbook' ),
                    'param_name'  => 'singular',
                    'value'       => '{count} offene Stelle',
                ],
                [
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Format (Null)', 'recruiting-playbook' ),
                    'param_name'  => 'zero',
                    'value'       => 'Keine offenen Stellen',
                ],
            ],
        ];
    }
}
```

---

### 4.4 RP: Featured Jobs (FeaturedJobs)

**Shortcode:** `rp_featured_jobs`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class FeaturedJobs extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: Featured Jobs', 'recruiting-playbook' ),
            'shortcode'       => 'rp_featured_jobs',
            'icon'            => 'fusiona-star',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_featured_jobs',
            'allow_generator' => true,

            'params' => [
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
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Optional: Überschrift über den Featured Jobs.', 'recruiting-playbook' ),
                    'param_name'  => 'title',
                    'value'       => '',
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Auszug anzeigen', 'recruiting-playbook' ),
                    'param_name'  => 'show_excerpt',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
            ],
        ];
    }
}
```

---

### 4.5 RP: Neueste Stellen (LatestJobs)

**Shortcode:** `rp_latest_jobs`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class LatestJobs extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: Neueste Stellen', 'recruiting-playbook' ),
            'shortcode'       => 'rp_latest_jobs',
            'icon'            => 'fusiona-clock',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_latest_jobs',
            'allow_generator' => true,

            'params' => [
                [
                    'type'        => 'range',
                    'heading'     => esc_attr__( 'Anzahl', 'recruiting-playbook' ),
                    'param_name'  => 'limit',
                    'value'       => '5',
                    'min'         => '1',
                    'max'         => '20',
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
                    'description' => esc_attr__( '1 = Listendarstellung', 'recruiting-playbook' ),
                    'param_name'  => 'columns',
                    'default'     => '1',
                    'value'       => [
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                    ],
                ],
                [
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                    'param_name'  => 'title',
                    'value'       => '',
                ],
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Kategorie', 'recruiting-playbook' ),
                    'param_name'  => 'category',
                    'value'       => $this->getTaxonomyOptions( 'job_category' ),
                    'default'     => '',
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Auszug anzeigen', 'recruiting-playbook' ),
                    'param_name'  => 'show_excerpt',
                    'default'     => 'false',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
            ],
        ];
    }
}
```

---

### 4.6 RP: Job-Kategorien (JobCategories)

**Shortcode:** `rp_job_categories`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class JobCategories extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: Job-Kategorien', 'recruiting-playbook' ),
            'shortcode'       => 'rp_job_categories',
            'icon'            => 'fusiona-folder',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_job_categories',
            'allow_generator' => true,

            'params' => [
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Spalten', 'recruiting-playbook' ),
                    'param_name'  => 'columns',
                    'default'     => '4',
                    'value'       => [
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                    ],
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Anzahl anzeigen', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Zeigt die Anzahl der Jobs pro Kategorie.', 'recruiting-playbook' ),
                    'param_name'  => 'show_count',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Leere verstecken', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Kategorien ohne Jobs ausblenden.', 'recruiting-playbook' ),
                    'param_name'  => 'hide_empty',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Sortierung', 'recruiting-playbook' ),
                    'param_name'  => 'orderby',
                    'default'     => 'name',
                    'value'       => [
                        'name'  => esc_attr__( 'Name', 'recruiting-playbook' ),
                        'count' => esc_attr__( 'Anzahl', 'recruiting-playbook' ),
                    ],
                ],
            ],
        ];
    }
}
```

---

### 4.7 RP: Bewerbungsformular (ApplicationForm)

**Shortcode:** `rp_application_form`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class ApplicationForm extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: Bewerbungsformular', 'recruiting-playbook' ),
            'shortcode'       => 'rp_application_form',
            'icon'            => 'fusiona-file-text-o',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_application_form',
            'allow_generator' => true,

            'params' => [
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Stelle', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Leer = automatisch erkennen (auf Stellenseiten).', 'recruiting-playbook' ),
                    'param_name'  => 'job_id',
                    'value'       => $this->getJobOptions(),
                    'default'     => '',
                ],
                [
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                    'param_name'  => 'title',
                    'value'       => 'Jetzt bewerben',
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Stellentitel anzeigen', 'recruiting-playbook' ),
                    'param_name'  => 'show_job_title',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
                [
                    'type'        => 'radio_button_set',
                    'heading'     => esc_attr__( 'Fortschrittsanzeige', 'recruiting-playbook' ),
                    'param_name'  => 'show_progress',
                    'default'     => 'true',
                    'value'       => [
                        'true'  => esc_attr__( 'Ja', 'recruiting-playbook' ),
                        'false' => esc_attr__( 'Nein', 'recruiting-playbook' ),
                    ],
                ],
            ],
        ];
    }

    /**
     * Job-Optionen für Dropdown laden
     */
    private function getJobOptions(): array {
        $jobs = get_posts( [
            'post_type'      => 'job_listing',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $options = [ '' => esc_attr__( '— Automatisch —', 'recruiting-playbook' ) ];

        foreach ( $jobs as $job ) {
            $options[ (string) $job->ID ] = $job->post_title;
        }

        return $options;
    }
}
```

---

### 4.8 RP: KI-Job-Finder (AiJobFinder)

**Shortcode:** `rp_ai_job_finder`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class AiJobFinder extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: KI-Job-Finder', 'recruiting-playbook' ),
            'shortcode'       => 'rp_ai_job_finder',
            'icon'            => 'fusiona-search-plus',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_ai_job_finder',
            'allow_generator' => true,

            'params' => [
                [
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Überschrift', 'recruiting-playbook' ),
                    'param_name'  => 'title',
                    'value'       => 'Finde deinen Traumjob',
                ],
                [
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Untertitel', 'recruiting-playbook' ),
                    'param_name'  => 'subtitle',
                    'value'       => '',
                ],
                [
                    'type'        => 'range',
                    'heading'     => esc_attr__( 'Max. Vorschläge', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Maximale Anzahl der KI-Vorschläge.', 'recruiting-playbook' ),
                    'param_name'  => 'limit',
                    'value'       => '5',
                    'min'         => '1',
                    'max'         => '10',
                ],
            ],
        ];
    }
}
```

---

### 4.9 RP: KI-Job-Match (AiJobMatch)

**Shortcode:** `rp_ai_job_match`

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

class AiJobMatch extends AbstractElement {

    protected function getConfig(): array {
        return [
            'name'            => esc_attr__( 'RP: KI-Job-Match', 'recruiting-playbook' ),
            'shortcode'       => 'rp_ai_job_match',
            'icon'            => 'fusiona-check-circle-o',
            'help_url'        => 'https://developer.recruiting-playbook.dev/docs/shortcodes#rp_ai_job_match',
            'allow_generator' => true,

            'params' => [
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Stelle', 'recruiting-playbook' ),
                    'description' => esc_attr__( 'Leer = automatisch erkennen.', 'recruiting-playbook' ),
                    'param_name'  => 'job_id',
                    'value'       => $this->getJobOptions(),
                    'default'     => '',
                ],
                [
                    'type'        => 'textfield',
                    'heading'     => esc_attr__( 'Button-Text', 'recruiting-playbook' ),
                    'param_name'  => 'title',
                    'value'       => 'Passe ich zu diesem Job?',
                ],
                [
                    'type'        => 'select',
                    'heading'     => esc_attr__( 'Button-Stil', 'recruiting-playbook' ),
                    'param_name'  => 'style',
                    'default'     => '',
                    'value'       => [
                        ''        => esc_attr__( 'Standard', 'recruiting-playbook' ),
                        'outline' => esc_attr__( 'Outline', 'recruiting-playbook' ),
                    ],
                ],
            ],
        ];
    }

    private function getJobOptions(): array {
        $jobs = get_posts( [
            'post_type'      => 'job_listing',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
        ] );

        $options = [ '' => esc_attr__( '— Automatisch —', 'recruiting-playbook' ) ];

        foreach ( $jobs as $job ) {
            $options[ (string) $job->ID ] = $job->post_title;
        }

        return $options;
    }
}
```

---

## 5. Parameter-Typen

### Verfügbare Avada Parameter-Typen

| Typ | Beschreibung | Verwendung |
|-----|--------------|------------|
| `textfield` | Einzeiliges Textfeld | Überschriften, Labels |
| `textarea` | Mehrzeiliges Textfeld | Beschreibungen |
| `range` | Slider mit Min/Max | Anzahl, Spalten |
| `radio_button_set` | Button-Gruppe | Ja/Nein, Auswahl |
| `select` | Dropdown | Taxonomien, Optionen |
| `colorpicker` | Farbauswahl | Design-Farben |
| `colorpickeralpha` | Farbe mit Alpha | Transparenz |
| `upload` | Medien-Upload | Bilder |
| `link_selector` | Link-Auswahl | URLs |
| `checkbox_button_set` | Multi-Select Buttons | Mehrfachauswahl |

### Gruppen (Tabs)

Parameter können in Gruppen organisiert werden:

```php
[
    'type'       => 'textfield',
    'param_name' => 'title',
    'group'      => esc_attr__( 'Allgemein', 'recruiting-playbook' ),
],
[
    'type'       => 'select',
    'param_name' => 'category',
    'group'      => esc_attr__( 'Filter', 'recruiting-playbook' ),
],
```

---

## 6. Editor-Vorschau

### Standard: AJAX-Render

Avada rendert Shortcodes automatisch via AJAX im Backend-Editor. Dafür ist **kein zusätzlicher Code** nötig.

### Optional: Custom Preview

Für eine optimierte Editor-Vorschau kann eine Preview-Datei definiert werden:

```php
// In Element-Config:
'preview'    => RP_PLUGIN_DIR . 'src/Integrations/Avada/previews/job-grid.php',
'preview_id' => 'rp-fusion-job-grid-preview',
```

```php
// previews/job-grid.php
<script type="text/html" id="rp-fusion-job-grid-preview">
    <div class="rp-avada-preview">
        <div class="rp-avada-preview-icon">
            <span class="fusiona-th-large"></span>
        </div>
        <div class="rp-avada-preview-title">
            <?php esc_html_e( 'Stellenliste', 'recruiting-playbook' ); ?>
        </div>
        <div class="rp-avada-preview-info">
            {{ params.limit }} <?php esc_html_e( 'Stellen', 'recruiting-playbook' ); ?>,
            {{ params.columns }} <?php esc_html_e( 'Spalten', 'recruiting-playbook' ); ?>
        </div>
    </div>
</script>
```

### Editor-CSS

```css
/* assets/css/avada-editor.css */

.rp-avada-preview {
    padding: 20px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
}

.rp-avada-preview-icon {
    font-size: 32px;
    color: #0073aa;
    margin-bottom: 10px;
}

.rp-avada-preview-title {
    font-size: 16px;
    font-weight: 600;
    color: #23282d;
}

.rp-avada-preview-info {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}
```

---

## 7. Design & Branding Integration

### CSS-Variablen im Frontend

Die Design & Branding Einstellungen werden automatisch geladen, da die Shortcodes diese bereits nutzen.

### Avada Global Options

Optional können Avada Global Options für RP-Elements hinzugefügt werden:

```php
// Später: Global Options für Recruiting Playbook
add_filter( 'fusion_builder_option_sections', function( $sections ) {
    $sections['recruiting_playbook'] = [
        'label' => esc_html__( 'Recruiting Playbook', 'recruiting-playbook' ),
        'id'    => 'recruiting_playbook',
        'icon'  => 'fusiona-users',
        'fields' => [
            // Global Settings hier
        ],
    ];
    return $sections;
});
```

---

## 8. Testing

### Manuelle Tests

| Test | Beschreibung |
|------|--------------|
| Element-Picker | Elements erscheinen in Kategorie "Recruiting Playbook" |
| Drag & Drop | Elements können eingefügt werden |
| Parameter | Alle Einstellungen funktionieren |
| Vorschau | AJAX-Render zeigt korrektes Ergebnis |
| Frontend | Ausgabe ist identisch mit Shortcode |
| Responsive | Layout passt sich an |

### PHPUnit-Tests

```php
// tests/Integrations/Avada/AvadaIntegrationTest.php

class AvadaIntegrationTest extends TestCase {

    public function test_elements_registered_when_avada_active(): void {
        // Mock FusionBuilder class
        $this->assertTrue(
            has_action( 'fusion_builder_before_init' )
        );
    }

    public function test_category_added(): void {
        $integration = new AvadaIntegration();
        $categories  = $integration->addCategory( [] );

        $this->assertArrayHasKey( 'recruiting_playbook', $categories );
    }
}
```

---

## 9. Implementierungs-Reihenfolge

### Phase 1: Basis-Infrastruktur

1. [ ] **AvadaIntegration.php** - Loader und Checks
2. [ ] **ElementLoader.php** - Element-Registrierung
3. [ ] **AbstractElement.php** - Basisklasse
4. [ ] **Editor-CSS** - Grundlegende Styles

### Phase 2: Kern-Elements

5. [ ] **JobGrid.php** - Stellenliste
6. [ ] **JobSearch.php** - Stellensuche
7. [ ] **JobCount.php** - Stellen-Zähler

### Phase 3: Ergänzende Elements

8. [ ] **FeaturedJobs.php** - Featured Jobs
9. [ ] **LatestJobs.php** - Neueste Stellen
10. [ ] **JobCategories.php** - Kategorie-Übersicht

### Phase 4: Formular & AI

11. [ ] **ApplicationForm.php** - Bewerbungsformular
12. [ ] **AiJobFinder.php** - KI-Job-Finder (AI-Addon)
13. [ ] **AiJobMatch.php** - KI-Job-Match (AI-Addon)

### Phase 5: Polish

14. [ ] **Custom Previews** - Optimierte Editor-Vorschau
15. [ ] **Dokumentation** - Website-Dokumentation
16. [ ] **Testing** - Manuelle + automatisierte Tests

---

## Entscheidungen

| Frage | Entscheidung |
|-------|--------------|
| Eigene Fusion_Element-Klassen? | **Nein** - Shortcode-Wrapper ist einfacher |
| Live-Editor Vorschau? | **AJAX-Render** (Standard) - einfacher zu warten |
| Avada Global Options? | **Später** - nicht für MVP |
| Avada Patterns? | **Später** - nicht für MVP |

---

## Referenzen

- [Fusion Builder Sample Add-On](https://github.com/Theme-Fusion/Fusion-Builder-Sample-Add-On)
- [Avada Builder Hooks](https://avada.com/documentation/avada-builder-hooks-actions-and-filters/)
- [Bestehende Shortcode-Dokumentation](/docs/shortcodes)

---

*Erstellt: 8. Februar 2026*
*Status: Spezifikation fertig, Implementierung ausstehend*

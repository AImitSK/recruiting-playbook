# Gutenberg Blocks: Technische Spezifikation

> **Pro-Feature: Native Gutenberg-Integration**
> Alle Recruiting Playbook Shortcodes als native WordPress-Blöcke für den Block-Editor

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Block-Registrierung](#3-block-registrierung)
4. [Blöcke im Detail](#4-blöcke-im-detail)
5. [Gemeinsame Komponenten](#5-gemeinsame-komponenten)
6. [Server-Side Rendering](#6-server-side-rendering)
7. [Editor-Vorschau](#7-editor-vorschau)
8. [Design & Branding Integration](#8-design--branding-integration)
9. [Block-Patterns & Templates](#9-block-patterns--templates)
10. [Testing](#10-testing)
11. [Implementierungs-Reihenfolge](#11-implementierungs-reihenfolge)

---

## 1. Übersicht

### Zielsetzung

Native Gutenberg-Blöcke bieten:
- **Visuelles Editing** direkt im Block-Editor
- **Live-Vorschau** der Stellenanzeigen
- **InspectorControls** für alle Block-Attribute
- **Block-Patterns** für häufige Layouts
- **Theme-Integration** mit theme.json Support

### Feature-Gating

```php
// Gutenberg Blocks sind Pro-Feature
if ( ! rp_can( 'gutenberg_blocks' ) ) {
    // Blöcke werden nicht registriert
    // Shortcodes funktionieren weiterhin (Free)
    return;
}
```

### Block-Übersicht

| Block | Shortcode | Beschreibung |
|-------|-----------|--------------|
| `rp/jobs` | `[rp_jobs]` | Stellenliste mit Grid/Liste |
| `rp/job-search` | `[rp_job_search]` | Suche mit Filtern |
| `rp/job-count` | `[rp_job_count]` | Stellen-Zähler |
| `rp/featured-jobs` | `[rp_featured_jobs]` | Hervorgehobene Stellen |
| `rp/latest-jobs` | `[rp_latest_jobs]` | Neueste Stellen |
| `rp/job-categories` | `[rp_job_categories]` | Kategorie-Übersicht |
| `rp/application-form` | `[rp_application_form]` | Bewerbungsformular |
| `rp/ai-job-finder` | `[rp_ai_job_finder]` | KI-Job-Finder (AI-Addon) |
| `rp/ai-job-match` | `[rp_ai_job_match]` | KI-Job-Match (AI-Addon) |

### User Stories

| Als | möchte ich | damit |
|-----|-----------|-------|
| Content-Editor | Stellenlisten per Drag & Drop einfügen | ich keine Shortcodes kennen muss |
| Content-Editor | Block-Einstellungen visuell anpassen | ich sofort sehe wie es aussieht |
| Admin | Block-Patterns bereitstellen | meine Redakteure konsistente Layouts nutzen |
| Entwickler | Blöcke per theme.json stylen | sie zum Theme passen |

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   ├── Blocks/
│   │   ├── BlockLoader.php              # Block-Registrierung & Assets
│   │   ├── Blocks/
│   │   │   ├── JobsBlock.php            # Server-Side Render
│   │   │   ├── JobSearchBlock.php
│   │   │   ├── JobCountBlock.php
│   │   │   ├── FeaturedJobsBlock.php
│   │   │   ├── LatestJobsBlock.php
│   │   │   ├── JobCategoriesBlock.php
│   │   │   ├── ApplicationFormBlock.php
│   │   │   ├── AiJobFinderBlock.php
│   │   │   └── AiJobMatchBlock.php
│   │   │
│   │   └── Patterns/
│   │       └── PatternLoader.php        # Block-Patterns registrieren
│   │
│   └── Frontend/
│       └── Shortcodes/                  # Bestehende Shortcodes (weiterhin genutzt)
│
├── assets/
│   └── src/
│       ├── js/
│       │   └── blocks/
│       │       ├── index.js             # Entry Point (alle Blöcke registrieren)
│       │       ├── jobs/
│       │       │   ├── index.js         # Block-Registrierung
│       │       │   ├── edit.js          # Editor-Komponente
│       │       │   ├── save.js          # null (Dynamic Block)
│       │       │   └── inspector.js     # Sidebar-Einstellungen
│       │       ├── job-search/
│       │       │   ├── index.js
│       │       │   ├── edit.js
│       │       │   └── inspector.js
│       │       ├── job-count/
│       │       │   └── ...
│       │       ├── featured-jobs/
│       │       │   └── ...
│       │       ├── latest-jobs/
│       │       │   └── ...
│       │       ├── job-categories/
│       │       │   └── ...
│       │       ├── application-form/
│       │       │   └── ...
│       │       ├── ai-job-finder/
│       │       │   └── ...
│       │       ├── ai-job-match/
│       │       │   └── ...
│       │       │
│       │       └── components/          # Gemeinsame Komponenten
│       │           ├── PreviewWrapper.js
│       │           ├── TaxonomySelect.js
│       │           ├── ColumnsControl.js
│       │           ├── ProBadge.js
│       │           └── ServerSideRender.js
│       │
│       └── css/
│           └── blocks-editor.css        # Editor-spezifische Styles
│
├── build/                               # Kompilierte Block-Assets
│   └── blocks/
│       ├── index.js
│       ├── index.asset.php
│       └── style-index.css
│
└── block.json files (in src/Blocks/Blocks/):
    ├── jobs/block.json
    ├── job-search/block.json
    └── ...
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Block-API | WordPress Blocks API v3 |
| Build | @wordpress/scripts |
| Editor-Komponenten | @wordpress/block-editor, @wordpress/components |
| State | @wordpress/data |
| Server-Render | @wordpress/server-side-render |
| i18n | @wordpress/i18n |
| Styling | CSS + Editor-Variables |

### Build-Konfiguration

```json
// package.json (Ergänzungen)
{
  "scripts": {
    "build:blocks": "wp-scripts build --webpack-src-dir=assets/src/js/blocks --output-path=build/blocks",
    "start:blocks": "wp-scripts start --webpack-src-dir=assets/src/js/blocks --output-path=build/blocks"
  }
}
```

---

## 3. Block-Registrierung

### PHP: BlockLoader.php

```php
<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Blocks;

defined( 'ABSPATH' ) || exit;

class BlockLoader {

    private array $blocks = [
        'jobs',
        'job-search',
        'job-count',
        'featured-jobs',
        'latest-jobs',
        'job-categories',
        'application-form',
        'ai-job-finder',
        'ai-job-match',
    ];

    public function register(): void {
        // Pro-Feature Check
        if ( ! function_exists( 'rp_can' ) || ! rp_can( 'gutenberg_blocks' ) ) {
            return;
        }

        add_action( 'init', [ $this, 'registerBlocks' ] );
        add_filter( 'block_categories_all', [ $this, 'registerCategory' ] );
    }

    public function registerBlocks(): void {
        foreach ( $this->blocks as $block ) {
            $this->registerBlock( $block );
        }
    }

    private function registerBlock( string $block ): void {
        // AI-Blocks nur wenn AI-Addon aktiv
        if ( str_starts_with( $block, 'ai-' ) ) {
            if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
                return;
            }
        }

        $block_dir = RP_PLUGIN_DIR . "src/Blocks/Blocks/{$block}";

        if ( file_exists( $block_dir . '/block.json' ) ) {
            register_block_type( $block_dir );
        }
    }

    public function registerCategory( array $categories ): array {
        return array_merge(
            [
                [
                    'slug'  => 'recruiting-playbook',
                    'title' => __( 'Recruiting Playbook', 'recruiting-playbook' ),
                    'icon'  => 'businessperson',
                ],
            ],
            $categories
        );
    }
}
```

### block.json Beispiel (Jobs-Block)

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "rp/jobs",
    "version": "1.0.0",
    "title": "Stellenliste",
    "category": "recruiting-playbook",
    "description": "Zeigt eine Liste der Stellenanzeigen an.",
    "keywords": ["jobs", "stellen", "karriere", "recruiting"],
    "textdomain": "recruiting-playbook",
    "attributes": {
        "limit": {
            "type": "number",
            "default": 10
        },
        "columns": {
            "type": "number",
            "default": 2
        },
        "category": {
            "type": "string",
            "default": ""
        },
        "location": {
            "type": "string",
            "default": ""
        },
        "type": {
            "type": "string",
            "default": ""
        },
        "featured": {
            "type": "boolean",
            "default": false
        },
        "orderby": {
            "type": "string",
            "default": "date",
            "enum": ["date", "title", "rand"]
        },
        "order": {
            "type": "string",
            "default": "DESC",
            "enum": ["ASC", "DESC"]
        },
        "showExcerpt": {
            "type": "boolean",
            "default": true
        }
    },
    "supports": {
        "align": ["wide", "full"],
        "html": false,
        "spacing": {
            "margin": true,
            "padding": true
        },
        "color": {
            "background": true
        }
    },
    "editorScript": "file:../../../../build/blocks/index.js",
    "editorStyle": "file:../../../../build/blocks/style-index.css",
    "render": "file:./render.php"
}
```

---

## 4. Blöcke im Detail

### 4.1 rp/jobs - Stellenliste

**Zweck:** Hauptblock zur Anzeige von Stellenanzeigen

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `limit` | number | 10 | Anzahl der Stellen |
| `columns` | number | 2 | Spalten im Grid (1-4) |
| `category` | string | "" | Filter: Kategorie-Slug(s) |
| `location` | string | "" | Filter: Standort-Slug(s) |
| `type` | string | "" | Filter: Beschäftigungsart |
| `featured` | boolean | false | Nur hervorgehobene |
| `orderby` | string | "date" | Sortierung |
| `order` | string | "DESC" | Reihenfolge |
| `showExcerpt` | boolean | true | Auszug anzeigen |

**Inspector-Controls:**

```jsx
// jobs/inspector.js
import { __ } from '@wordpress/i18n';
import {
    PanelBody,
    RangeControl,
    SelectControl,
    ToggleControl
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import TaxonomySelect from '../components/TaxonomySelect';

export default function Inspector({ attributes, setAttributes }) {
    const { limit, columns, category, location, type, featured, orderby, order, showExcerpt } = attributes;

    return (
        <InspectorControls>
            <PanelBody title={__('Anzeige', 'recruiting-playbook')}>
                <RangeControl
                    label={__('Anzahl Stellen', 'recruiting-playbook')}
                    value={limit}
                    onChange={(value) => setAttributes({ limit: value })}
                    min={1}
                    max={50}
                />
                <RangeControl
                    label={__('Spalten', 'recruiting-playbook')}
                    value={columns}
                    onChange={(value) => setAttributes({ columns: value })}
                    min={1}
                    max={4}
                />
                <ToggleControl
                    label={__('Auszug anzeigen', 'recruiting-playbook')}
                    checked={showExcerpt}
                    onChange={(value) => setAttributes({ showExcerpt: value })}
                />
            </PanelBody>

            <PanelBody title={__('Filter', 'recruiting-playbook')} initialOpen={false}>
                <TaxonomySelect
                    label={__('Kategorie', 'recruiting-playbook')}
                    taxonomy="job_category"
                    value={category}
                    onChange={(value) => setAttributes({ category: value })}
                />
                <TaxonomySelect
                    label={__('Standort', 'recruiting-playbook')}
                    taxonomy="job_location"
                    value={location}
                    onChange={(value) => setAttributes({ location: value })}
                />
                <TaxonomySelect
                    label={__('Beschäftigungsart', 'recruiting-playbook')}
                    taxonomy="employment_type"
                    value={type}
                    onChange={(value) => setAttributes({ type: value })}
                />
                <ToggleControl
                    label={__('Nur Featured Jobs', 'recruiting-playbook')}
                    checked={featured}
                    onChange={(value) => setAttributes({ featured: value })}
                />
            </PanelBody>

            <PanelBody title={__('Sortierung', 'recruiting-playbook')} initialOpen={false}>
                <SelectControl
                    label={__('Sortieren nach', 'recruiting-playbook')}
                    value={orderby}
                    options={[
                        { label: __('Datum', 'recruiting-playbook'), value: 'date' },
                        { label: __('Titel', 'recruiting-playbook'), value: 'title' },
                        { label: __('Zufall', 'recruiting-playbook'), value: 'rand' },
                    ]}
                    onChange={(value) => setAttributes({ orderby: value })}
                />
                <SelectControl
                    label={__('Reihenfolge', 'recruiting-playbook')}
                    value={order}
                    options={[
                        { label: __('Absteigend', 'recruiting-playbook'), value: 'DESC' },
                        { label: __('Aufsteigend', 'recruiting-playbook'), value: 'ASC' },
                    ]}
                    onChange={(value) => setAttributes({ order: value })}
                />
            </PanelBody>
        </InspectorControls>
    );
}
```

---

### 4.2 rp/job-search - Stellensuche

**Zweck:** Suchformular mit Filtern und Ergebnisliste

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `showSearch` | boolean | true | Suchfeld anzeigen |
| `showCategory` | boolean | true | Kategorie-Dropdown |
| `showLocation` | boolean | true | Standort-Dropdown |
| `showType` | boolean | true | Beschäftigungsart-Dropdown |
| `limit` | number | 10 | Stellen pro Seite |
| `columns` | number | 1 | Spalten im Grid |

---

### 4.3 rp/job-count - Stellen-Zähler

**Zweck:** Dynamischer Zähler für Headlines

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `category` | string | "" | Filter: Kategorie |
| `location` | string | "" | Filter: Standort |
| `type` | string | "" | Filter: Beschäftigungsart |
| `format` | string | "{count} offene Stellen" | Ausgabeformat |
| `singular` | string | "{count} offene Stelle" | Text für 1 |
| `zero` | string | "Keine offenen Stellen" | Text für 0 |

**Besonderheit:** Inline-Block, kann in Überschriften/Paragraphen eingefügt werden.

```json
{
    "supports": {
        "html": false,
        "inserter": true,
        "__experimentalOnEnter": true
    },
    "parent": ["core/paragraph", "core/heading"]
}
```

---

### 4.4 rp/featured-jobs - Hervorgehobene Stellen

**Zweck:** Showcase für Top-Stellen

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `limit` | number | 3 | Anzahl |
| `columns` | number | 3 | Spalten |
| `title` | string | "" | Optionale Überschrift |
| `showExcerpt` | boolean | true | Auszug anzeigen |

---

### 4.5 rp/latest-jobs - Neueste Stellen

**Zweck:** Aktuellste Stellenanzeigen

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `limit` | number | 5 | Anzahl |
| `columns` | number | 1 | Spalten |
| `title` | string | "" | Optionale Überschrift |
| `category` | string | "" | Filter: Kategorie |
| `showExcerpt` | boolean | false | Auszug anzeigen |

---

### 4.6 rp/job-categories - Kategorie-Übersicht

**Zweck:** Klickbare Kategorie-Karten

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `columns` | number | 4 | Spalten (1-6) |
| `showCount` | boolean | true | Anzahl anzeigen |
| `hideEmpty` | boolean | true | Leere ausblenden |
| `orderby` | string | "name" | Sortierung (name, count) |

---

### 4.7 rp/application-form - Bewerbungsformular

**Zweck:** Bewerbungsformular für Stellenseiten

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `jobId` | number | 0 | Stellen-ID (0 = automatisch) |
| `title` | string | "Jetzt bewerben" | Überschrift |
| `showJobTitle` | boolean | true | Stellentitel anzeigen |
| `showProgress` | boolean | true | Fortschrittsanzeige |

**Besonderheit:** Job-Auswahl per SearchControl im Inspector.

---

### 4.8 rp/ai-job-finder - KI-Job-Finder (AI-Addon)

**Zweck:** Lebenslauf-Upload mit KI-Matching

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `title` | string | "Finde deinen Traumjob" | Überschrift |
| `subtitle` | string | "" | Untertitel |
| `limit` | number | 5 | Max. Vorschläge |

**Feature-Gating:**
```php
if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
    return '<div class="rp-upgrade-notice">AI-Addon erforderlich</div>';
}
```

---

### 4.9 rp/ai-job-match - KI-Job-Match (AI-Addon)

**Zweck:** "Passe ich zu diesem Job?" Button

**Attribute:**

| Attribut | Typ | Default | Beschreibung |
|----------|-----|---------|--------------|
| `jobId` | number | 0 | Stellen-ID (0 = automatisch) |
| `title` | string | "Passe ich zu diesem Job?" | Button-Text |
| `style` | string | "" | Button-Stil (outline) |

---

## 5. Gemeinsame Komponenten

### TaxonomySelect

Dropdown für WordPress-Taxonomien mit REST-API Fetch:

```jsx
// components/TaxonomySelect.js
import { useState, useEffect } from '@wordpress/element';
import { SelectControl, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export default function TaxonomySelect({ label, taxonomy, value, onChange, multiple = false }) {
    const [terms, setTerms] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        apiFetch({ path: `/wp/v2/${taxonomy}?per_page=100` })
            .then((data) => {
                setTerms(data.map(term => ({
                    label: term.name,
                    value: term.slug,
                })));
                setLoading(false);
            })
            .catch(() => setLoading(false));
    }, [taxonomy]);

    if (loading) {
        return <Spinner />;
    }

    return (
        <SelectControl
            label={label}
            value={value}
            options={[
                { label: '— Alle —', value: '' },
                ...terms,
            ]}
            onChange={onChange}
            multiple={multiple}
        />
    );
}
```

### PreviewWrapper

Wrapper für konsistente Editor-Vorschau:

```jsx
// components/PreviewWrapper.js
import { Placeholder, Spinner } from '@wordpress/components';

export default function PreviewWrapper({
    icon,
    label,
    isLoading,
    isEmpty,
    emptyMessage,
    children
}) {
    if (isLoading) {
        return (
            <Placeholder icon={icon} label={label}>
                <Spinner />
            </Placeholder>
        );
    }

    if (isEmpty) {
        return (
            <Placeholder icon={icon} label={label}>
                <p>{emptyMessage}</p>
            </Placeholder>
        );
    }

    return <div className="rp-block-preview">{children}</div>;
}
```

### ColumnsControl

Wiederverwendbare Spalten-Auswahl:

```jsx
// components/ColumnsControl.js
import { RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ColumnsControl({ value, onChange, min = 1, max = 4 }) {
    return (
        <RangeControl
            label={__('Spalten', 'recruiting-playbook')}
            value={value}
            onChange={onChange}
            min={min}
            max={max}
            marks={[
                { value: 1, label: '1' },
                { value: 2, label: '2' },
                { value: 3, label: '3' },
                { value: 4, label: '4' },
            ]}
        />
    );
}
```

---

## 6. Server-Side Rendering

Alle Blöcke nutzen **Dynamic Blocks** mit PHP-Rendering. Das ermöglicht:
- Wiederverwendung der bestehenden Shortcode-Logik
- Live-Daten bei jedem Page Load
- SEO-freundliches HTML

### render.php Beispiel (Jobs-Block)

```php
<?php
/**
 * Server-Side Render für rp/jobs Block
 *
 * @var array    $attributes Block-Attribute
 * @var string   $content    Inner Blocks (leer bei diesem Block)
 * @var WP_Block $block      Block-Instanz
 */

defined( 'ABSPATH' ) || exit;

// Attribute zu Shortcode-Attributen konvertieren
$shortcode_atts = [
    'limit'        => $attributes['limit'] ?? 10,
    'columns'      => $attributes['columns'] ?? 2,
    'category'     => $attributes['category'] ?? '',
    'location'     => $attributes['location'] ?? '',
    'type'         => $attributes['type'] ?? '',
    'featured'     => ! empty( $attributes['featured'] ) ? 'true' : 'false',
    'orderby'      => $attributes['orderby'] ?? 'date',
    'order'        => $attributes['order'] ?? 'DESC',
    'show_excerpt' => ! empty( $attributes['showExcerpt'] ) ? 'true' : 'false',
];

// Shortcode-Klasse nutzen
$shortcode = new \RecruitingPlaybook\Frontend\Shortcodes\JobsShortcode();
$output    = $shortcode->render( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen
$wrapper_attributes = get_block_wrapper_attributes( [
    'class' => 'rp-block-jobs',
] );

printf(
    '<div %s>%s</div>',
    $wrapper_attributes,
    $output
);
```

---

## 7. Editor-Vorschau

### ServerSideRender Komponente

```jsx
// jobs/edit.js
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import Inspector from './inspector';

export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps({
        className: 'rp-block-jobs-editor',
    });

    return (
        <>
            <Inspector attributes={attributes} setAttributes={setAttributes} />
            <div {...blockProps}>
                <ServerSideRender
                    block="rp/jobs"
                    attributes={attributes}
                    EmptyResponsePlaceholder={() => (
                        <p>Keine Stellen gefunden.</p>
                    )}
                />
            </div>
        </>
    );
}
```

### Editor-spezifische Styles

```css
/* blocks-editor.css */

/* Verhindere Klick-Interaktionen in der Vorschau */
.rp-block-jobs-editor .rp-card a,
.rp-block-jobs-editor .wp-element-button {
    pointer-events: none;
}

/* Zeige Bearbeitungs-Hinweis */
.rp-block-jobs-editor::after {
    content: 'Stellenliste (Klicken zum Bearbeiten)';
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.2s;
}

.rp-block-jobs-editor:hover::after {
    opacity: 1;
}

/* Block-Selektion */
.rp-block-jobs-editor.is-selected {
    outline: 2px solid var(--wp-admin-theme-color);
    outline-offset: 2px;
}
```

---

## 8. Design & Branding Integration

### CSS-Variablen im Editor

Die Design & Branding Einstellungen werden auch im Editor geladen:

```php
// BlockLoader.php
public function enqueueEditorAssets(): void {
    // Design-CSS auch im Editor laden
    $css_generator = new \RecruitingPlaybook\Services\CssGeneratorService();
    $inline_css    = $css_generator->generate();

    wp_add_inline_style( 'rp-blocks-editor', $inline_css );
}
```

### Theme.json Support

```json
// theme.json (Theme-Entwickler)
{
    "settings": {
        "blocks": {
            "rp/jobs": {
                "spacing": {
                    "padding": true,
                    "margin": true
                },
                "color": {
                    "background": true
                }
            }
        }
    }
}
```

---

## 9. Block-Patterns & Templates

### Block-Patterns

```php
// Patterns/PatternLoader.php
public function registerPatterns(): void {
    register_block_pattern(
        'recruiting-playbook/karriere-seite',
        [
            'title'       => __( 'Karriereseite', 'recruiting-playbook' ),
            'description' => __( 'Komplette Karriereseite mit Suche und Featured Jobs', 'recruiting-playbook' ),
            'categories'  => [ 'recruiting-playbook' ],
            'content'     => '<!-- wp:heading {"level":1} -->
                <h1>Karriere bei uns</h1>
                <!-- /wp:heading -->

                <!-- wp:rp/job-count {"format":"Aktuell {count} offene Stellen"} /-->

                <!-- wp:rp/featured-jobs {"title":"Top-Stellenangebote","columns":3} /-->

                <!-- wp:rp/job-search {"columns":2} /-->',
        ]
    );

    register_block_pattern(
        'recruiting-playbook/job-sidebar',
        [
            'title'       => __( 'Stellen-Sidebar', 'recruiting-playbook' ),
            'description' => __( 'Kompakte Stellenliste für Sidebars', 'recruiting-playbook' ),
            'categories'  => [ 'recruiting-playbook' ],
            'content'     => '<!-- wp:rp/latest-jobs {"limit":5,"columns":1,"showExcerpt":false} /-->',
        ]
    );
}
```

### Block-Template für Job-Archive

```php
// Optional: Template-Lock für Stellen-Archiv
add_filter( 'allowed_block_types_all', function( $allowed, $context ) {
    if ( $context->post && 'job_listing' === $context->post->post_type ) {
        // Nur bestimmte Blöcke erlauben
        return [
            'core/paragraph',
            'core/heading',
            'core/list',
            'rp/ai-job-match',
            'rp/application-form',
        ];
    }
    return $allowed;
}, 10, 2 );
```

---

## 10. Testing

### Jest-Tests für Blöcke

```javascript
// __tests__/blocks/jobs.test.js
import { render, screen } from '@testing-library/react';
import { registerBlockType } from '@wordpress/blocks';
import Edit from '../jobs/edit';

describe('Jobs Block', () => {
    it('renders inspector controls', () => {
        const attributes = { limit: 10, columns: 2 };
        const setAttributes = jest.fn();

        render(<Edit attributes={attributes} setAttributes={setAttributes} />);

        // Inspector Controls sollten gerendert werden
        expect(screen.getByText('Anzahl Stellen')).toBeInTheDocument();
        expect(screen.getByText('Spalten')).toBeInTheDocument();
    });

    it('calls setAttributes on limit change', () => {
        const attributes = { limit: 10, columns: 2 };
        const setAttributes = jest.fn();

        render(<Edit attributes={attributes} setAttributes={setAttributes} />);

        // Limit ändern
        // ... Test-Interaktion

        expect(setAttributes).toHaveBeenCalledWith({ limit: 5 });
    });
});
```

### PHPUnit-Tests für Server-Render

```php
// tests/Blocks/JobsBlockTest.php
class JobsBlockTest extends TestCase {

    public function test_block_is_registered(): void {
        $this->assertTrue( \WP_Block_Type_Registry::get_instance()->is_registered( 'rp/jobs' ) );
    }

    public function test_render_output(): void {
        $block = new \WP_Block( [
            'blockName' => 'rp/jobs',
            'attrs'     => [
                'limit'   => 5,
                'columns' => 2,
            ],
        ] );

        $output = $block->render();

        $this->assertStringContainsString( 'rp-plugin', $output );
        $this->assertStringContainsString( 'rp-grid', $output );
    }

    public function test_empty_results(): void {
        // Keine Jobs vorhanden
        $block = new \WP_Block( [
            'blockName' => 'rp/jobs',
            'attrs'     => [ 'category' => 'non-existent' ],
        ] );

        $output = $block->render();

        $this->assertStringContainsString( 'Aktuell keine offenen Stellen', $output );
    }
}
```

---

## 11. Implementierungs-Reihenfolge

### Phase 1: Basis-Infrastruktur

1. **BlockLoader.php** - Registrierung und Assets
2. **Build-Setup** - @wordpress/scripts Konfiguration
3. **Block-Kategorie** - "Recruiting Playbook"
4. **Gemeinsame Komponenten** - TaxonomySelect, ColumnsControl

### Phase 2: Kern-Blöcke

5. **rp/jobs** - Stellenliste (Hauptblock)
6. **rp/job-search** - Suche mit Filtern
7. **rp/job-count** - Stellen-Zähler

### Phase 3: Ergänzende Blöcke

8. **rp/featured-jobs** - Hervorgehobene Stellen
9. **rp/latest-jobs** - Neueste Stellen
10. **rp/job-categories** - Kategorie-Übersicht

### Phase 4: Formular & AI

11. **rp/application-form** - Bewerbungsformular
12. **rp/ai-job-finder** - KI-Job-Finder
13. **rp/ai-job-match** - KI-Job-Match

### Phase 5: Polish

14. **Block-Patterns** - Vorgefertigte Layouts
15. **Editor-Styles** - Konsistente Vorschau
16. **Dokumentation** - Block-Referenz

---

## Offene Fragen

| Frage | Vorschlag |
|-------|-----------|
| Sollen Blöcke auch in Free verfügbar sein? | Nein, Gutenberg Blocks = Pro-Feature |
| Wie mit Block-Transforms umgehen? | Shortcode → Block Transform anbieten |
| Server-Side Render Caching? | Transients für häufig genutzte Queries |
| Innerblocks für Job-Cards? | Nein, Server-Render ist konsistenter |

---

*Letzte Aktualisierung: 8. Februar 2026*

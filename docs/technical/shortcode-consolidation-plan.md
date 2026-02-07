# Shortcode Konsolidierung & Implementierung

> **Status:** In Planung
> **Priorität:** Hoch (Voraussetzung für Page Builder Integration)
> **Erstellt:** 7. Februar 2026

---

## 1. Ziele

1. **Einheitliche Shortcode-Struktur** - Klare Trennung zwischen öffentlichen und internen Shortcodes
2. **Design & Branding Kompatibilität** - Alle Shortcodes nutzen CSS-Variablen
3. **Vollständige Dokumentation** - Konsistente Doku in allen Dateien
4. **Vorbereitung Page Builder** - Shortcodes als Basis für Gutenberg/Elementor/Avada

---

## 2. Shortcode-Kategorisierung

### 2.1 Öffentliche Shortcodes (für Endanwender)

| Shortcode | Tier | Status | Beschreibung |
|-----------|------|:------:|--------------|
| `[rp_jobs]` | Free | ✅ Implementiert | Job-Liste mit Grid-Layout |
| `[rp_job_search]` | Free | ✅ Implementiert | Suchformular mit Filtern und Ergebnissen |
| `[rp_application_form]` | Free | 🔧 Refactor | Bewerbungsformular (auto-detect Form Builder) |
| `[rp_job_count]` | Pro | ❌ TODO | Stellen-Zähler für Headlines |
| `[rp_featured_jobs]` | Pro | ❌ TODO | Hervorgehobene Stellen |
| `[rp_latest_jobs]` | Pro | ❌ TODO | Neueste Stellen |
| `[rp_job_categories]` | Pro | ❌ TODO | Kategorie-Übersicht |
| `[rp_ai_job_finder]` | AI | ✅ Implementiert | KI-Job-Finder |

### 2.2 Interne Shortcodes (nicht dokumentieren)

| Shortcode | Verwendung |
|-----------|------------|
| `rp_custom_application_form` | Wird intern von `rp_application_form` aufgerufen |
| `rp_ai_job_match` | Automatisch in Job-Cards und Job-Detail eingebunden |

---

## 3. Implementierungsplan

### Phase 1: Refactoring (Priorität: Hoch)

#### 3.1 `rp_application_form` Zusammenführung

**Aktueller Zustand:**
- `rp_application_form` - Standard-Formular
- `rp_custom_application_form` - Form Builder Formular
- User muss wissen welchen Shortcode er verwenden soll

**Ziel:**
- Ein Shortcode: `rp_application_form`
- Automatische Erkennung ob Form Builder aktiv ist
- `rp_custom_application_form` wird intern oder deprecated

**Änderungen in `src/Frontend/Shortcodes.php`:**

```php
public function renderApplicationForm( $atts ): string {
    $atts = shortcode_atts( [
        'job_id'         => 0,
        'title'          => __( 'Jetzt bewerben', 'recruiting-playbook' ),
        'show_job_title' => true,
    ], $atts );

    // Job-ID ermitteln
    $job_id = $this->resolveJobId( $atts['job_id'] );

    if ( ! $job_id ) {
        return $this->renderError( __( 'Keine Stelle gefunden.', 'recruiting-playbook' ) );
    }

    // Auto-Detect: Form Builder aktiv?
    $form_config = $this->formConfigService->getPublishedConfig( $job_id );

    if ( $form_config && ! empty( $form_config['fields'] ) ) {
        // Form Builder Formular rendern
        return $this->renderFormBuilderForm( $job_id, $form_config, $atts );
    }

    // Standard-Formular rendern
    return $this->renderStandardForm( $job_id, $atts );
}
```

**Tasks:**
- [ ] `renderApplicationForm()` refactoren mit Auto-Detection
- [ ] `renderCustomApplicationForm()` als private Methode behalten
- [ ] Shortcode `rp_custom_application_form` als Alias registrieren (Backwards-Compat)
- [ ] Nach 1-2 Releases: Alias entfernen

---

### Phase 2: Neue Shortcodes (Priorität: Mittel)

#### 3.2 `[rp_job_count]` - Stellen-Zähler

**Attribute:**
| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `category` | Filter nach Kategorie-Slug | - |
| `location` | Filter nach Standort-Slug | - |
| `type` | Filter nach Beschäftigungsart | - |
| `format` | Ausgabeformat mit `{count}` Platzhalter | `{count} offene Stellen` |
| `singular` | Text für 1 Stelle | `{count} offene Stelle` |
| `zero` | Text für 0 Stellen | `Keine offenen Stellen` |

**Beispiele:**
```html
[rp_job_count]
<!-- Ausgabe: "12 offene Stellen" -->

[rp_job_count category="pflege"]
<!-- Ausgabe: "5 offene Stellen" -->

[rp_job_count format="Wir haben aktuell {count} Karrieremöglichkeiten!"]
<!-- Ausgabe: "Wir haben aktuell 12 Karrieremöglichkeiten!" -->

[rp_job_count zero="Aktuell keine Stellen - aber schauen Sie bald wieder vorbei!"]
```

**Implementierung:**
```php
public function renderJobCount( $atts ): string {
    $atts = shortcode_atts( [
        'category' => '',
        'location' => '',
        'type'     => '',
        'format'   => __( '{count} offene Stellen', 'recruiting-playbook' ),
        'singular' => __( '{count} offene Stelle', 'recruiting-playbook' ),
        'zero'     => __( 'Keine offenen Stellen', 'recruiting-playbook' ),
    ], $atts );

    $count = $this->getJobCount( $atts );

    if ( $count === 0 ) {
        return '<span class="rp-job-count rp-job-count--zero">' . esc_html( $atts['zero'] ) . '</span>';
    }

    $format = $count === 1 ? $atts['singular'] : $atts['format'];
    $text = str_replace( '{count}', number_format_i18n( $count ), $format );

    return '<span class="rp-job-count">' . esc_html( $text ) . '</span>';
}
```

**Aufwand:** ~1 Stunde

---

#### 3.3 `[rp_featured_jobs]` - Hervorgehobene Stellen

**Attribute:**
| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `limit` | Anzahl der Stellen | `3` |
| `columns` | Spalten im Grid | `3` |
| `title` | Überschrift (leer = keine) | - |
| `show_excerpt` | Auszug anzeigen | `true` |

**Beispiele:**
```html
[rp_featured_jobs]
[rp_featured_jobs limit="4" columns="2"]
[rp_featured_jobs title="Unsere Top-Stellenangebote"]
```

**Implementierung:**
Wrapper um `renderJobList()` mit `featured="true"` Filter.

```php
public function renderFeaturedJobs( $atts ): string {
    $atts = shortcode_atts( [
        'limit'        => 3,
        'columns'      => 3,
        'title'        => '',
        'show_excerpt' => true,
    ], $atts );

    // Als rp_jobs mit featured Filter rendern
    $jobs_atts = [
        'limit'        => $atts['limit'],
        'columns'      => $atts['columns'],
        'featured'     => 'true',
        'show_excerpt' => $atts['show_excerpt'],
    ];

    $output = '';

    if ( ! empty( $atts['title'] ) ) {
        $output .= '<h2 class="rp-featured-jobs__title">' . esc_html( $atts['title'] ) . '</h2>';
    }

    $output .= $this->renderJobList( $jobs_atts );

    return '<div class="rp-featured-jobs">' . $output . '</div>';
}
```

**Voraussetzung:** Meta-Feld `_rp_featured` für Jobs implementieren (Checkbox im Job-Editor).

**Aufwand:** ~1-2 Stunden

---

#### 3.4 `[rp_latest_jobs]` - Neueste Stellen

**Attribute:**
| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `limit` | Anzahl der Stellen | `5` |
| `columns` | Spalten (0 = Liste) | `0` |
| `title` | Überschrift | - |
| `category` | Filter nach Kategorie | - |
| `show_date` | Datum anzeigen | `true` |

**Beispiele:**
```html
[rp_latest_jobs]
[rp_latest_jobs limit="3" columns="3"]
[rp_latest_jobs title="Neu bei uns" category="it"]
```

**Implementierung:**
Wrapper um `renderJobList()` mit `orderby="date"` und optionalem Listen-Layout.

**Aufwand:** ~1 Stunde

---

#### 3.5 `[rp_job_categories]` - Kategorie-Übersicht

**Attribute:**
| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `columns` | Spalten im Grid | `4` |
| `show_count` | Anzahl pro Kategorie | `true` |
| `hide_empty` | Leere Kategorien verstecken | `true` |
| `orderby` | Sortierung (`name`, `count`) | `name` |

**Beispiele:**
```html
[rp_job_categories]
[rp_job_categories columns="3" show_count="true"]
[rp_job_categories orderby="count" hide_empty="true"]
```

**Implementierung:**
```php
public function renderJobCategories( $atts ): string {
    $atts = shortcode_atts( [
        'columns'    => 4,
        'show_count' => true,
        'hide_empty' => true,
        'orderby'    => 'name',
    ], $atts );

    $terms = get_terms( [
        'taxonomy'   => 'job_category',
        'hide_empty' => $atts['hide_empty'],
        'orderby'    => $atts['orderby'] === 'count' ? 'count' : 'name',
        'order'      => $atts['orderby'] === 'count' ? 'DESC' : 'ASC',
    ] );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }

    ob_start();
    ?>
    <div class="rp-job-categories rp-grid rp-grid-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
        <?php foreach ( $terms as $term ) : ?>
            <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="rp-job-category-card rp-card">
                <span class="rp-job-category-card__name"><?php echo esc_html( $term->name ); ?></span>
                <?php if ( $atts['show_count'] ) : ?>
                    <span class="rp-job-category-card__count"><?php echo esc_html( $term->count ); ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
```

**Aufwand:** ~2 Stunden

---

### Phase 3: Design & Branding Kompatibilität

#### 3.6 CSS-Variablen Audit

Für jeden Shortcode prüfen und dokumentieren:

| Shortcode | Card-Vars | Button-Vars | Badge-Vars | Typo-Vars |
|-----------|:---------:|:-----------:|:----------:|:---------:|
| `rp_jobs` | ⬜ | ⬜ | ⬜ | ⬜ |
| `rp_job_search` | ⬜ | ⬜ | ⬜ | ⬜ |
| `rp_application_form` | ⬜ | ⬜ | - | ⬜ |
| `rp_ai_job_finder` | ⬜ | ⬜ | ⬜ | ⬜ |
| `rp_job_count` | - | - | - | ⬜ |
| `rp_featured_jobs` | ⬜ | ⬜ | ⬜ | ⬜ |
| `rp_latest_jobs` | ⬜ | ⬜ | ⬜ | ⬜ |
| `rp_job_categories` | ⬜ | - | - | ⬜ |

**Zu prüfende CSS-Variablen:**

```css
/* Cards */
--rp-card-bg
--rp-card-border
--rp-card-radius
--rp-card-shadow
--rp-card-shadow-hover

/* Buttons */
--rp-btn-bg
--rp-btn-text
--rp-btn-radius
--rp-btn-padding

/* Badges */
--rp-badge-new-bg / --rp-badge-new-text
--rp-badge-remote-bg / --rp-badge-remote-text
--rp-badge-category-bg / --rp-badge-category-text
--rp-badge-salary-bg / --rp-badge-salary-text

/* Typografie */
--rp-font-size-h1 ... --rp-font-size-small
--rp-line-height-heading / --rp-line-height-body
```

---

### Phase 4: Dokumentation

#### 3.7 Zu aktualisierende Dateien

| Datei | Änderungen |
|-------|------------|
| `website/content/docs/shortcodes.mdx` | Alle öffentlichen Shortcodes dokumentieren |
| `docs/technical/theme-integration.md` | Sync mit implementierten Shortcodes |
| `docs/user-guide.md` | Shortcode-Beispiele aktualisieren |
| `docs/roadmap.md` | Status aktualisieren |

#### 3.8 Dokumentations-Template pro Shortcode

```markdown
## [rp_shortcode_name]

Kurze Beschreibung.

### Attribute

| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `attr1` | Was es tut | `default` |

### Beispiele

**Einfach:**
\`\`\`html
[rp_shortcode_name]
\`\`\`

**Mit Parametern:**
\`\`\`html
[rp_shortcode_name attr1="value"]
\`\`\`

### Hinweise

- Hinweis 1
- Hinweis 2
```

---

## 4. Testplan

### 4.1 Testseiten (bereits erstellt)

- [x] Übersicht: `/shortcode-tests/`
- [x] rp_jobs: `/shortcode-test-rp-jobs/`
- [x] rp_job_search: `/shortcode-test-rp-job-search/`
- [x] rp_application_form: `/shortcode-test-rp-application-form/`
- [x] rp_custom_application_form: `/shortcode-test-rp-custom-application-form/`
- [x] rp_ai_job_match: `/shortcode-test-rp-ai-job-match/`
- [x] rp_ai_job_finder: `/shortcode-test-rp-ai-job-finder/`

### 4.2 Zusätzliche Testseiten (nach Implementierung)

- [ ] rp_job_count: `/shortcode-test-rp-job-count/`
- [ ] rp_featured_jobs: `/shortcode-test-rp-featured-jobs/`
- [ ] rp_latest_jobs: `/shortcode-test-rp-latest-jobs/`
- [ ] rp_job_categories: `/shortcode-test-rp-job-categories/`

### 4.3 Design-Tests pro Shortcode

Für jeden Shortcode mit verschiedenen Design-Einstellungen testen:

1. **Card-Design:**
   - [ ] Preset: Kompakt
   - [ ] Preset: Standard
   - [ ] Preset: Großzügig
   - [ ] Custom Radius/Schatten/Rahmen

2. **Button-Design:**
   - [ ] Theme-Modus
   - [ ] Custom-Modus

3. **Badge-Stil:**
   - [ ] Hell
   - [ ] Ausgefüllt

4. **Primärfarbe:**
   - [ ] Theme-Farbe
   - [ ] Custom-Farbe

---

## 5. Zeitschätzung

| Phase | Tasks | Aufwand |
|-------|-------|---------|
| Phase 1: Refactoring | `rp_application_form` Zusammenführung | 2-3h |
| Phase 2: Neue Shortcodes | 4 Shortcodes implementieren | 5-6h |
| Phase 3: Design-Audit | CSS-Variablen prüfen & fixen | 3-4h |
| Phase 4: Dokumentation | Alle Docs aktualisieren | 2-3h |
| **Gesamt** | | **12-16h** |

---

## 6. Abhängigkeiten

### Voraussetzungen

- [x] Design & Branding System implementiert
- [x] CSS-Variablen in `CssGeneratorService`
- [ ] Meta-Feld `_rp_featured` für Jobs (für `rp_featured_jobs`)

### Nachfolgende Tasks

- [ ] Page Builder: Gutenberg Blocks
- [ ] Page Builder: Elementor Widgets
- [ ] Page Builder: Avada/Fusion Elements

---

## 7. Checkliste

### Phase 1: Refactoring
- [ ] `rp_application_form` mit Auto-Detection implementieren
- [ ] `rp_custom_application_form` als Alias behalten
- [ ] Testseiten aktualisieren
- [ ] Manueller Test beider Varianten

### Phase 2: Neue Shortcodes
- [ ] `rp_job_count` implementieren
- [ ] `rp_featured_jobs` implementieren
- [ ] `_rp_featured` Meta-Feld hinzufügen
- [ ] `rp_latest_jobs` implementieren
- [ ] `rp_job_categories` implementieren
- [ ] Testseiten für neue Shortcodes erstellen

### Phase 3: Design & Branding
- [ ] CSS-Audit für alle Shortcodes
- [ ] Fehlende CSS-Variablen ergänzen
- [ ] Testseiten mit verschiedenen Designs testen
- [ ] Bugs dokumentieren und fixen

### Phase 4: Dokumentation
- [ ] `website/content/docs/shortcodes.mdx` aktualisieren
- [ ] `docs/technical/theme-integration.md` synchronisieren
- [ ] Geplante aber nicht implementierte Shortcodes als "Coming Soon" markieren
- [ ] Interne Shortcodes aus öffentlicher Doku entfernen

---

*Erstellt: 7. Februar 2026*
*Letzte Aktualisierung: 7. Februar 2026*

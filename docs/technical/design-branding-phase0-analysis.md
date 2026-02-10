# Phase 0: Hardcodierte Werte Analyse

> **Datum**: 2026-02-05
> **Branch**: feature/design-branding
> **Zweck**: Identifikation aller Stellen, die von hardcodierten Werten auf CSS-Variablen umgestellt werden müssen

---

## 1. Zusammenfassung

Die Codebasis hat bereits eine **gute Grundstruktur** mit CSS Custom Properties in `main.css`. Die meisten Werte sind bereits als Design-Tokens definiert. Das Design & Branding Feature muss diese Tokens ergänzen und einen Mechanismus für Pro-User bereitstellen, um sie zu überschreiben.

**Aktueller Stand:**
- ✅ CSS Custom Properties vorhanden (`:root` in `main.css`)
- ✅ Tailwind mit `rp-` Prefix konfiguriert
- ✅ Templates verwenden CSS-Klassen statt Inline-Styles
- ⚠️ Einige hardcodierte Farben in Badge/Status-Komponenten
- ⚠️ Schriftgrößen-Override für Pro fehlt noch

---

## 2. Relevante Dateien

### Frontend (vom Design-Feature betroffen)
| Datei | Beschreibung |
|-------|-------------|
| `plugin/assets/src/css/main.css` | Haupt-CSS mit Design-Tokens |
| `plugin/assets/src/css/match-modal.css` | CV-Matching Modal |
| `plugin/assets/src/css/job-finder.css` | KI-Job-Finder |
| `plugin/templates/archive-job_listing.php` | Job-Liste |
| `plugin/templates/single-job_listing.php` | Einzelne Stelle |

### Nicht betroffen (bewusst ausgeschlossen)
| Datei | Begründung |
|-------|------------|
| `plugin/assets/src/css/admin*.css` | Admin-Backend, nicht vom Design-Feature gesteuert |

### Partiell betroffen (nur Primärfarbe)
| Datei | Beschreibung |
|-------|-------------|
| `plugin/templates/emails/partials/header.php` | E-Mail-Header verwendet Primärfarbe (Zeile 24) |

> **Hinweis**: E-Mail-Templates benötigen Inline-Styles für Client-Kompatibilität. Die Primärfarbe wird direkt aus `DesignService::get_design_settings()` gelesen, NICHT über CSS-Variablen.

---

## 3. Hardcodierte Werte in main.css

### 3.1 Farben (Design-Feature relevant)

| Zeile | Aktueller Wert | CSS-Variable | Design-Setting | Priorität |
|-------|---------------|--------------|----------------|-----------|
| 37 | `--rp-color-text-muted: #64748b` | Bereits Variable | - | Niedrig |
| 38 | `--rp-color-text-light: #94a3b8` | Bereits Variable | - | Niedrig |
| 41 | `#2563eb` (Fallback) | `--rp-color-primary` | `primary_color` (B-02) | **Hoch** |
| 44 | `--rp-color-primary-contrast: #ffffff` | Bereits Variable | Automatisch berechnet | Mittel |
| 48 | `--rp-color-surface: #ffffff` | Bereits Variable | `card_background` (C-05) | Mittel |
| 51 | `--rp-color-border: #e2e8f0` | Bereits Variable | `card_border_color` (C-04) | Mittel |
| 138 | `--primary: 217 91% 60%` (HSL) | Tailwind-Kompatibilität | Aus `primary_color` berechnen | **Hoch** |

### 3.2 Badge-Farben (Design-Feature relevant)

| Zeile | Klasse | Aktueller Wert | Design-Setting | CSS-Variable |
|-------|--------|---------------|----------------|--------------|
| 583-586 | `.rp-badge-gray` | `background: #f1f5f9` | `badge_color_category` (JL-11) | `--rp-badge-category` |
| 583-586 | `.rp-badge-gray` | `color: #475569` | Berechnet aus Badge-Stil | `--rp-badge-category-text` |
| 588-591 | `.rp-badge-primary` | `background: var(--rp-color-primary-light)` | Primärfarbe | - |
| 588-591 | `.rp-badge-primary` | `color: var(--rp-color-primary)` | Primärfarbe | - |
| 593-596 | `.rp-badge-success` | `background: var(--rp-color-success-light)` | `badge_color_new` (JL-09) | `--rp-badge-new` |
| 593-596 | `.rp-badge-success` | `color: #166534` | Berechnet | `--rp-badge-new-text` |
| 598-601 | `.rp-badge-warning` | `background: var(--rp-color-warning-light)` | - | - |
| 598-601 | `.rp-badge-warning` | `color: #92400e` | - | - |
| 603-606 | `.rp-badge-error` | `background: var(--rp-color-error-light)` | - | - |
| 603-606 | `.rp-badge-error` | `color: #991b1b` | - | - |

#### Badge-Textfarben-Berechnung (Phase 1)

Laut Spec (Abschnitt 3.5, JL-08) bestimmt `badge_style` die Textfarbe:
- **light** (Default): 10% Opacity Hintergrund, farbiger Text → Textfarbe = 60% dunklere Variante der Badge-Farbe
- **solid**: Voller Hintergrund, weißer Text → Textfarbe = `#ffffff`

```php
// CssGeneratorService - Beispiel-Logik
if ($settings['badge_style'] === 'solid') {
    $badge_text = '#ffffff';
    $badge_bg = $settings['badge_color_new'];
} else { // light
    $badge_text = $this->darken_color($settings['badge_color_new'], 60);
    $badge_bg = $this->add_opacity($settings['badge_color_new'], 0.1);
}
```

### 3.3 Feste Design-Tokens (NICHT ändern)

Diese Werte sind **bewusst fest** und schützen das Layout vor Theme-Konflikten:

| Kategorie | Zeilen | Kommentar |
|-----------|--------|-----------|
| Schriftgrößen | 71-79 | `--rp-text-xs` bis `--rp-text-5xl` - Basis bleibt fest |
| Line Heights | 82-88 | `--rp-leading-*` - Konsistenz wichtig |
| Spacing | 90-102 | `--rp-space-*` - Layout-Schutz |
| Border Radius | 105-112 | `--rp-radius-*` - Pro-User überschreibt nur Card-Radius |
| Shadows | 115-119 | `--rp-shadow-*` - Basis bleibt, Card-Shadow konfigurierbar |

---

## 4. Hardcodierte Werte in match-modal.css

| Zeile | Aktueller Wert | Ersetzen durch | Priorität |
|-------|---------------|----------------|-----------|
| 14 | `rgba(0, 0, 0, 0.5)` | OK (Overlay) | - |
| 187-189 | `--rp-error: #dc2626` (Fallback) | `var(--rp-color-error)` | Niedrig |
| 191-193 | `--rp-warning: #f59e0b` (Fallback) | `var(--rp-color-warning)` | Niedrig |
| 195-198 | `--rp-success: #10b981` (Fallback) | `var(--rp-color-success)` | Niedrig |

**Hinweis**: Diese verwenden bereits `var(--rp-...)` mit Fallbacks. Das ist korrekt.

**Prüfung für Phase 1**: Das Modal (`.rp-match-modal`) sollte Card-Design-Einstellungen erben:
- `card_border_radius` (C-01) → `.rp-match-modal { border-radius: var(--rp-card-radius, ...); }`
- `card_shadow` (C-02) → Box-Shadow vom Card-Design
- `card_background` (C-05) → Hintergrundfarbe

---

## 5. Hardcodierte Werte in job-finder.css

| Zeile | Aktueller Wert | Ersetzen durch | Priorität |
|-------|---------------|----------------|-----------|
| 133 | `rp-border-l-green-500` | Tailwind-Klasse OK | - |
| 137 | `rp-border-l-yellow-500` | Tailwind-Klasse OK | - |
| 141 | `rp-border-l-gray-400` | Tailwind-Klasse OK | - |
| 229-231 | `.rp-text-success/warning/error` | Tailwind-Klassen OK | - |

**Hinweis**: Diese verwenden Tailwind-Utility-Klassen, die auf die Theme-Konfiguration verweisen.

---

## 6. Templates Analyse

### archive-job_listing.php
- ✅ Verwendet durchgehend `rp-*` Klassen
- ✅ Kein problematischer Inline-Style
- ⚠️ Zeile 40: `style="max-width: var(--wp--style--global--wide-size, 1280px)"` - OK, Layout

### single-job_listing.php
- ✅ Verwendet durchgehend `rp-*` Klassen
- ✅ Kein problematischer Inline-Style
- ⚠️ Zeile 104: `style="max-width: var(--wp--style--global--wide-size, 1200px)"` - OK, Layout
- ⚠️ Zeile 262: Formular-Container `.rp-border`, `.rp-bg-white` - Sollte Card-Design erben

---

## 7. Erforderliche Änderungen für Phase 1

### 7.1 Neue CSS-Variablen hinzufügen (CssGeneratorService)

```css
/* Diese Variablen werden vom CssGeneratorService generiert wenn Pro-User Werte setzt */
.rp-plugin {
  /* Primärfarbe (überschreibt Fallback) */
  --rp-color-primary: #custom;

  /* Card-Design */
  --rp-card-radius: 8px;
  --rp-card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --rp-card-border-color: #e5e7eb;
  --rp-card-bg: #ffffff;

  /* Button-Design (nur wenn override_button_colors = true) */
  --rp-btn-bg: #custom;
  --rp-btn-bg-hover: #custom;
  --rp-btn-text: #ffffff;

  /* Badge-Farben */
  --rp-badge-new: #22c55e;
  --rp-badge-remote: #8b5cf6;
  --rp-badge-category: #6b7280;

  /* Schriftgrößen (Pro-Override) */
  --rp-font-size-h1: 2.5rem;
  --rp-font-size-h2: 2rem;
  /* ... */

  /* Link-Styling */
  --rp-link-color: var(--rp-color-primary);
  --rp-link-decoration: underline;
}
```

### 7.2 main.css Anpassungen

```css
/* Headings mit Pro-Override-Fallback */
.rp-plugin h1 {
  font-size: var(--rp-font-size-h1, var(--rp-text-4xl));
}
.rp-plugin h2 {
  font-size: var(--rp-font-size-h2, var(--rp-text-3xl));
}
/* ... */

/* Card-Komponente auf Variablen umstellen */
.rp-plugin .rp-card {
  border-radius: var(--rp-card-radius, var(--rp-radius-xl));
  box-shadow: var(--rp-card-shadow, var(--rp-shadow-md));
  border-color: var(--rp-card-border-color, var(--rp-color-border));
  background-color: var(--rp-card-bg, var(--rp-color-surface));
}

/* Badges auf Variablen umstellen */
.rp-plugin .rp-badge-primary {
  background-color: var(--rp-badge-category-bg, var(--rp-color-primary-light));
  color: var(--rp-badge-category, var(--rp-color-primary));
}
```

### 7.3 Formular-Container in Template anpassen

```php
<!-- single-job_listing.php Zeile 262 -->
<div id="apply-form" class="rp-card rp-mt-12" data-job-id="..." data-rp-application-form>
```

Statt manueller Border/Background-Klassen sollte `.rp-card` verwendet werden, damit die Formularbox das Card-Design erbt.

---

## 8. Kategorien-Checkliste

- [x] Primärfarbe und Varianten → Bereits als Variable, Override-Mechanismus fehlt
- [x] Textfarben (muted, light) → Bereits als Variablen definiert
- [x] Card-Styles → Teilweise Variablen, Ergänzung nötig
- [x] Button-Styles → Verwenden Primärfarbe, Override-Mechanismus fehlt
- [x] Badge-Farben → Hardcodiert, müssen auf Variablen umgestellt werden
- [x] Schriftgrößen → Feste Basis, Pro-Override-Fallback-Pattern nötig

---

## 9. Nächste Schritte (Phase 1)

1. **DesignService.php** erstellen mit allen Default-Werten aus der Spec
2. **CssGeneratorService.php** erstellen für CSS-Variablen-Generierung
3. **main.css** mit Fallback-Pattern für Pro-Override erweitern
4. **REST API Endpoint** für Settings GET/POST
5. **Template-Fix**: Formular-Container auf `.rp-card` umstellen

---

*Erstellt: 2026-02-05*
*Phase 0 abgeschlossen*

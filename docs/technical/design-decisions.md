# Design-Entscheidungen

Dieses Dokument dokumentiert alle wichtigen Design-Entscheidungen für das Frontend des Recruiting Playbook Plugins.

## Inhaltsverzeichnis

1. [CSS-Architektur](#1-css-architektur)
2. [Selektive Theme-Integration](#2-selektive-theme-integration)
3. [Tailwind CSS Konfiguration](#3-tailwind-css-konfiguration)
4. [Design Tokens](#4-design-tokens)
5. [Anleitung für Design-Änderungen](#5-anleitung-für-design-änderungen)
6. [Block Theme Kompatibilität](#6-block-theme-kompatibilität)
7. [Button-Strategie](#7-button-strategie)

---

## 1. CSS-Architektur

### Entscheidung: CSS Layers + Scoped Reset + Tailwind Prefix

**Datum:** Januar 2025

**Problem:**
WordPress-Themes überschreiben Plugin-Styles unkontrolliert (Button-Farben, Schriftgrößen, Margins).

**Lösung:**
```
┌─────────────────────────────────────────────────────────────┐
│                    CSS LAYERS (Priorität)                    │
├─────────────────────────────────────────────────────────────┤
│  @layer rp-reset      → Basis-Reset (niedrigste Priorität)  │
│  @layer rp-tokens     → CSS Custom Properties               │
│  @layer rp-base       → Grundlegende Element-Styles         │
│  @layer rp-components → Button, Input, Card, etc.           │
├─────────────────────────────────────────────────────────────┤
│  Tailwind Utilities   → UNLAYERED (höchste Priorität)       │
│                         Überschreibt Theme-CSS zuverlässig  │
└─────────────────────────────────────────────────────────────┘
```

**WICHTIG:** Tailwind Utilities sind bewusst NICHT in einem @layer!
Unlayered CSS hat höhere Priorität als layered Theme-CSS.

**Warum CSS Layers?**
- Kontrollierte Spezifität ohne `!important`
- Theme-Styles können nicht versehentlich überschreiben
- Klare Hierarchie für Entwickler

**Warum Scoped Reset?**
- Nur innerhalb `.rp-plugin` Container aktiv
- Neutralisiert Theme-Styles ohne globale Auswirkungen
- Garantiert konsistentes Basis-Styling

**Warum Tailwind Prefix (`rp-`)?**
- Keine Kollision mit Theme-Tailwind-Klassen
- Eindeutige Zuordnung zum Plugin
- Einfaches Debugging

---

## 2. Selektive Theme-Integration

### Entscheidung: Bestimmte Styles vom Theme erben, andere fest kontrollieren

**Datum:** Januar 2025

**Problem:**
Plugin soll sich ins Theme einfügen (keine Fremdkörper-Optik), aber Layout-kritische Werte dürfen nicht vom Theme überschrieben werden.

**Lösung: Zwei Kategorien von Design-Werten**

### INHERIT: Vom Theme übernehmen

Diese Werte werden vom Theme geerbt, damit das Plugin sich visuell einfügt:

| Variable | Beschreibung | Warum erben? |
|----------|--------------|--------------|
| `--rp-font-family` | Schriftart | Konsistenz mit Theme-Typografie |
| `--rp-color-text` | Textfarbe | Passt zur Theme-Farbpalette |
| `--rp-color-primary` | Primärfarbe | Akzentfarbe des Themes nutzen |
| `--rp-color-background` | Hintergrund | Nahtlose Integration |

**Technische Umsetzung:**
```css
:root {
    --rp-font-family: inherit;
    --rp-color-text: inherit;
    /* Primärfarbe: WordPress Theme-Variable → Fallback */
    --rp-color-primary: var(--wp--preset--color--primary, var(--wp--preset--color--vivid-cyan-blue, #2563eb));
    --rp-color-primary-hover: color-mix(in srgb, var(--rp-color-primary) 85%, black);
    --rp-color-primary-light: color-mix(in srgb, var(--rp-color-primary) 15%, white);
}
```

### CONTROL: Vom Plugin kontrollieren

Diese Werte werden **NIEMALS** vom Theme überschrieben:

| Variable | Beschreibung | Warum kontrollieren? |
|----------|--------------|---------------------|
| `--rp-text-xs` bis `--rp-text-5xl` | Schriftgrößen | Layout-Konsistenz |
| `--rp-space-1` bis `--rp-space-24` | Abstände | Formular-Layout |
| `--rp-radius-*` | Border-Radius | Einheitliche Komponenten |
| `--rp-leading-*` | Zeilenhöhen | Lesbarkeit |

**Technische Umsetzung:**
```css
:root {
    /* FEST - nicht überschreibbar */
    --rp-text-base: 1rem;
    --rp-space-4: 1rem;
    --rp-radius-md: 0.375rem;
}
```

---

## 3. Tailwind CSS Konfiguration

### Entscheidung: Prefix `rp-` für alle Tailwind-Klassen

**Datum:** Januar 2025

**Konfiguration:** `plugin/tailwind.config.js`

```javascript
module.exports = {
    prefix: 'rp-',
    // ...
}
```

**Konsequenzen:**
- Alle Klassen haben `rp-` Prefix: `rp-bg-white`, `rp-text-lg`, etc.
- Responsive Prefixes: `sm:rp-text-xl`, `lg:rp-grid-cols-3`
- State Variants: `hover:rp-bg-primary-hover`, `focus:rp-ring-2`

### Entscheidung: Preflight deaktiviert

```javascript
corePlugins: {
    preflight: false,
}
```

**Warum?**
- Preflight würde Theme-Basis-Styles zerstören (h1-h6, Typografie)
- Wir haben eigenen scoped Reset in `.rp-plugin`

---

## 4. Design Tokens

Alle Design-Werte sind als CSS Custom Properties in `plugin/assets/src/css/main.css` definiert.

### Farben

```css
/* Primärfarbe (automatisch vom WordPress Theme) */
--rp-color-primary: var(--wp--preset--color--primary, var(--wp--preset--color--vivid-cyan-blue, #2563eb));
--rp-color-primary-hover: color-mix(in srgb, var(--rp-color-primary) 85%, black);
--rp-color-primary-light: color-mix(in srgb, var(--rp-color-primary) 15%, white);

/* Status-Farben (fest) */
--rp-color-success: #22c55e;
--rp-color-warning: #f59e0b;
--rp-color-error: #ef4444;
--rp-color-info: #3b82f6;

/* Neutral (fest) */
--rp-color-border: #e2e8f0;
--rp-color-surface: #ffffff;
```

### Typografie

```css
/* Schriftgrößen (fest) */
--rp-text-xs: 0.75rem;    /* 12px */
--rp-text-sm: 0.875rem;   /* 14px */
--rp-text-base: 1rem;     /* 16px */
--rp-text-lg: 1.125rem;   /* 18px */
--rp-text-xl: 1.25rem;    /* 20px */
--rp-text-2xl: 1.5rem;    /* 24px */

/* Zeilenhöhen (fest) */
--rp-leading-tight: 1.25;
--rp-leading-normal: 1.5;
--rp-leading-relaxed: 1.625;
```

### Spacing

```css
/* Abstände (fest) */
--rp-space-1: 0.25rem;    /* 4px */
--rp-space-2: 0.5rem;     /* 8px */
--rp-space-4: 1rem;       /* 16px */
--rp-space-6: 1.5rem;     /* 24px */
--rp-space-8: 2rem;       /* 32px */
```

### Border Radius

```css
--rp-radius-sm: 0.125rem;
--rp-radius-md: 0.375rem;
--rp-radius-lg: 0.5rem;
--rp-radius-xl: 0.75rem;
--rp-radius-full: 9999px;
```

---

## 5. Anleitung für Design-Änderungen

### Farbe ändern

**Beispiel:** Primärfarbe von Blau auf Grün ändern

1. **CSS Custom Property anpassen** (`main.css`):
   ```css
   --rp-color-primary: #22c55e;
   --rp-color-primary-hover: #16a34a;
   --rp-color-primary-light: #dcfce7;
   ```

2. **Build ausführen:**
   ```bash
   cd plugin && npm run build
   ```

3. **Keine Template-Änderungen nötig!**

### Spacing anpassen

**Beispiel:** Mehr Abstand in Cards

1. **Komponenten-Style anpassen** (`main.css`):
   ```css
   .rp-plugin .rp-card {
       padding: var(--rp-space-8);  /* war: var(--rp-space-6) */
   }
   ```

### Neue Komponente hinzufügen

1. **In `@layer rp-components` hinzufügen** (`main.css`):
   ```css
   @layer rp-components {
       .rp-plugin .rp-alert {
           padding: var(--rp-space-4);
           border-radius: var(--rp-radius-md);
           /* ... */
       }
   }
   ```

2. **Im Template verwenden:**
   ```html
   <div class="rp-alert">Nachricht</div>
   ```

### Template-Style anpassen

**WICHTIG:** Verwende nur `rp-` prefixed Klassen!

```php
<!-- Richtig -->
<div class="rp-bg-white rp-p-6 rp-rounded-lg">

<!-- Falsch - wird nicht funktionieren -->
<div class="bg-white p-6 rounded-lg">
```

### Theme-Farbe überschreiben (für Themes)

Die Primärfarbe wird **automatisch** vom WordPress Theme übernommen via:
- `--wp--preset--color--primary` (wenn vom Theme definiert)
- `--wp--preset--color--vivid-cyan-blue` (WordPress Standard-Fallback)
- `#2563eb` (Hardcoded Fallback)

**NICHT überschreibbar:** Schriftgrößen, Spacing, Border-Radius

---

## 6. Block Theme Kompatibilität

### Entscheidung: Automatische Erkennung von Block Themes (FSE)

**Datum:** Januar 2025

**Problem:**
Block Themes (Full Site Editing) nutzen `block_template_part()` statt klassischer `header.php`/`footer.php`. Plugin-Templates müssen beide Szenarien unterstützen.

**Lösung:**
```php
if ( wp_is_block_theme() ) {
    // Block Theme: HTML-Struktur + block_template_part()
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <?php wp_head(); ?>
    </head>
    <body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div class="wp-site-blocks">
        <?php block_template_part( 'header' ); ?>
        <main class="wp-block-group">
    <?php
} else {
    // Classic Theme
    get_header();
}
```

---

## 7. Button-Strategie

### Entscheidung: Theme-native Buttons verwenden

**Datum:** Januar 2025

**Problem:**
Plugin-Buttons sahen wie Fremdkörper aus, da sie eigenes Styling hatten.

**Lösung:**
Für Buttons, die sich ins Theme einfügen sollen, die WordPress-Klasse `wp-element-button` verwenden:

```php
<a href="<?php the_permalink(); ?>" class="wp-element-button">
    <?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
</a>
```

**Wann `wp-element-button`?**
- Öffentliche Seiten (Archive, Single)
- Buttons die zum Theme passen sollen

**Wann `rp-btn rp-btn-primary`?**
- Admin-Bereich
- Formulare innerhalb des Plugins
- Wo konsistentes Plugin-Styling wichtig ist

---

## Changelog

| Datum | Änderung | Autor |
|-------|----------|-------|
| 2025-01-23 | Initial: CSS Layers, Scoped Reset, Tailwind Prefix | Claude |
| 2025-01-23 | Selektive Theme-Integration dokumentiert | Claude |
| 2025-01-23 | Tailwind Utilities aus @layer entfernt (höhere Priorität) | Claude |
| 2025-01-23 | Primärfarbe von WordPress Theme-Variablen | Claude |
| 2025-01-23 | Block Theme Kompatibilität (FSE) | Claude |
| 2025-01-23 | Button-Strategie: wp-element-button für Theme-Integration | Claude |

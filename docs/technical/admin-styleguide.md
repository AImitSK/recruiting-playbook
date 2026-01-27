# Admin UI Styleguide

Dieses Dokument definiert die visuellen Regeln für das WordPress Admin-Interface des Recruiting Playbook Plugins.

---

## Farben

### Primärfarben (Blau)

| Verwendung | Hex | Beispiel |
|------------|-----|----------|
| Primary Button | `#1d71b8` | Hauptaktionen |
| Primary Button Hover | `#36a9e1` | Hover-State |
| Primary Text | `#1d71b8` | Links, Secondary Button Text |
| Info Background | `#edf4f9` | Info-Boxen |
| Info Border | `#d1e3f0` | Info-Box Rahmen |

### Erfolgsfarben (Grün)

| Verwendung | Hex | Beispiel |
|------------|-----|----------|
| Success/Aktiv | `#2fac66` | Aktive Lizenz, Haken |
| Success Background | `#e6f5ec` | Success-Boxen |
| Success Border | `#c3e6d1` | Success-Box Rahmen |

### Neutrale Farben

| Verwendung | Hex | Beispiel |
|------------|-----|----------|
| Text | `#1f2937` | Haupttext |
| Text Muted | `#6b7280` | Sekundärtext |
| Border | `#e5e7eb` | Card-Rahmen |
| Background | `#ffffff` | Cards |
| Disabled Icons | `#d1d5db` | Inaktive Haken |

---

## Komponenten

### Status-Box (Alert)

Zeigt Lizenzstatus oder andere Statusmeldungen an.

**Aufbau:**
- Kein Icon
- Text links (16px, fett, Markenfarbe)
- Badge rechts (Markenfarbe als Hintergrund, weiß als Text)
- Vertikal zentriert (Flexbox)
- Linker Akzent-Rand (4px)

**Aktiv (Success):**
```css
background-color: #e6f5ec;
border-left: 4px solid #2fac66;
border: 1px solid #c3e6d1;
color: #2fac66;
```

**Inaktiv (Info):**
```css
background-color: #edf4f9;
border-left: 4px solid #1d71b8;
border: 1px solid #d1e3f0;
color: #1d71b8;
```

**Badge:**
```css
background-color: #2fac66 | #1d71b8;
color: #ffffff;
padding: 0.25rem 0.75rem;
border-radius: 9999px;
font-size: 0.75rem;
font-weight: 600;
```

---

### Buttons

**Primary Button:**
```css
background-color: #1d71b8;
color: #ffffff;
border: none;
border-radius: 0.375rem;
padding: 0.5rem 1rem;
font-weight: 500;
```

**Primary Button Hover:**
```css
background-color: #36a9e1;
```

**Secondary/Outline Button:**
```css
background-color: transparent;
color: #1d71b8;
border: 1px solid #1d71b8;
```

**Secondary Button Hover:**
```css
background-color: #f0f7fc;
```

---

### Cards

```css
background-color: #ffffff;
border: 1px solid #e5e7eb;
border-radius: 0.5rem;
box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
```

**Card Header:**
- Padding: 1.5rem
- Padding-Bottom: 0.75rem

**Card Content:**
- Padding: 1.5rem
- Padding-Top: 0.75rem

**Card Title:**
- Font-Size: 1.125rem
- Font-Weight: 600

---

### Tabellen

**Table Header:**
```css
padding: 0.75rem 1rem;
font-weight: 500;
font-size: 0.75rem;
color: #6b7280;
text-transform: uppercase;
letter-spacing: 0.05em;
background-color: #f9fafb;
border-bottom: 1px solid #e5e7eb;
```

**Table Cell:**
```css
padding: 0.75rem 1rem;
font-size: 0.875rem;
color: #1f2937;
border-bottom: 1px solid #e5e7eb;
```

**Table Row Hover:**
```css
background-color: #f9fafb;
transition: background-color 0.15s ease;
```

**Haken (Feature vorhanden):**
```css
color: #2fac66;
```

**X (Feature nicht vorhanden):**
```css
color: #d1d5db;
```

---

### Status Tabs

Horizontale Tab-Navigation für Filterung.

```css
/* Tab Container */
display: flex;
border-bottom: 1px solid #e5e7eb;
overflow-x: auto;

/* Tab Button */
padding: 0.5rem 1rem;
border: none;
background: transparent;
border-bottom: 2px solid transparent;
font-size: 0.875rem;
cursor: pointer;

/* Tab Button Active */
background: /* status bg color */;
border-bottom: 2px solid /* status color */;
color: /* status color */;
font-weight: 600;
```

**Tab Count Badge:**
```css
padding: 0.125rem 0.5rem;
border-radius: 9999px;
font-size: 0.75rem;
font-weight: 500;
/* Active: bg = status color, color = white */
/* Inactive: bg = #e5e7eb, color = #6b7280 */
```

---

### Status Badges (Bewerbungen)

| Status | Farbe | Hintergrund |
|--------|-------|-------------|
| Neu | `#2271b1` | `#e6f3ff` |
| In Prüfung | `#dba617` | `#fff8e6` |
| Interview | `#9b59b6` | `#f5e6ff` |
| Angebot | `#1e8cbe` | `#e6f5ff` |
| Eingestellt | `#2fac66` | `#e6f5ec` |
| Abgelehnt | `#d63638` | `#ffe6e6` |
| Zurückgezogen | `#787c82` | `#f0f0f0` |

**Badge Styling:**
```css
display: inline-flex;
align-items: center;
padding: 0.25rem 0.625rem;
border-radius: 9999px;
font-size: 0.75rem;
font-weight: 500;
```

---

### Pagination

```css
/* Container */
display: flex;
align-items: center;
justify-content: space-between;
padding: 0.75rem 1rem;
border-top: 1px solid #e5e7eb;
background-color: #f9fafb;

/* Info Text */
font-size: 0.875rem;
color: #6b7280;

/* Page Button */
padding: 0.375rem;
border: 1px solid #e5e7eb;
border-radius: 0.375rem;
background: #fff;

/* Page Button Disabled */
cursor: not-allowed;
opacity: 0.5;
```

---

### Promo-Box (Upgrade)

Gradient-Box für Upgrade-Aufforderungen.

```css
background: linear-gradient(to bottom right, #2fac66, #36a9e1);
color: #ffffff;
border-radius: 0.5rem;
padding: 1.5rem;
```

**Button in Promo-Box:**
```css
background-color: #ffffff;
color: #1d71b8;
```

---

### Dashboard Stat Cards

Statistik-Karten im shadcn/ui Dashboard-Stil.

**Aufbau:**
```
┌─────────────────────────────────┐
│ Label (klein, muted)    [Icon] │
│ 42                              │
│ Beschreibungstext               │
└─────────────────────────────────┘
```

**Header:**
```css
display: flex;
justify-content: space-between;
align-items: center;
padding: 1.5rem 1.5rem 0.5rem;
```

**Label:**
```css
font-size: 0.875rem;
font-weight: 500;
color: #6b7280;
```

**Icon:**
```css
width: 1rem;
height: 1rem;
color: #6b7280; /* oder Markenfarbe */
```

**Wert:**
```css
font-size: 2rem;
font-weight: 700;
line-height: 1.2;
color: #1f2937; /* oder Markenfarbe */
```

**Beschreibung:**
```css
font-size: 0.75rem;
color: #6b7280;
margin-top: 0.25rem;
```

**Farbvarianten:**
- Aktive Stellen: `#1d71b8` (Primary Blue)
- Neue Bewerbungen: `#2fac66` (Success Green)
- Gesamt: `#1f2937` (Neutral)

---

## Abstände

| Name | Wert | Verwendung |
|------|------|------------|
| xs | 0.25rem (4px) | Kleine Gaps |
| sm | 0.5rem (8px) | Icon-Text Gap |
| md | 0.75rem (12px) | Element-Abstände |
| lg | 1rem (16px) | Section-Abstände |
| xl | 1.5rem (24px) | Card-Padding |
| 2xl | 2rem (32px) | Große Abstände |

---

## Typografie

| Element | Größe | Gewicht |
|---------|-------|---------|
| Page Title | 1.5rem (24px) | 700 |
| Card Title | 1.125rem (18px) | 600 |
| Body | 1rem (16px) | 400 |
| Small | 0.875rem (14px) | 400 |
| Badge | 0.75rem (12px) | 600 |

---

## Logo

| Variante | Breite | Verwendung |
|----------|--------|------------|
| Logo (Icon + Text) | 150px | Seiten-Header |
| Icon only | 32px | Menü, Favicon |

**Dateien:**
- `assets/images/rp-logo.png`
- `assets/images/rp-icon.png`

---

## CSS-Variablen

Definiert in `.rp-admin`:

```css
--rp-brand-primary: #1d71b8;
--rp-brand-primary-hover: #36a9e1;
--rp-brand-success: #2fac66;
--rp-brand-success-bg: #e6f5ec;
--rp-brand-info-bg: #edf4f9;
```

---

## Navigation Badges

Badges im WordPress Admin-Menü (z.B. "PRO" neben Lizenz):

```css
background: linear-gradient(to right, #2fac66, #36a9e1);
color: #ffffff;
```

**Hinweis:** Verwendet den gleichen Gradient wie die Upgrade/Promo-Box.

---

## Regeln

1. **Keine Icons in Status-Boxen** - Text und Badge reichen aus
2. **Linker Akzent-Rand** bei Alert-Boxen (4px)
3. **Badges** immer mit Markenfarbe als Hintergrund, weiß als Text
4. **Navigation Badges** verwenden den Gradient (#2fac66 → #36a9e1)
5. **Buttons** verwenden die blaue Primärfarbe
6. **Erfolgs-Elemente** (Haken, aktive Status) verwenden Grün
7. **Vertikale Zentrierung** bei einzeiligen Boxen
8. **Konsistente Abstände** - siehe Abstands-Tabelle

---

*Zuletzt aktualisiert: Januar 2026*

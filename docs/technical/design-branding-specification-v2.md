# Design & Branding Specification v2

> **Status**: Draft - Ersetzt die alte `design-branding-specification.md`
> **Pro-Feature**: Nur mit aktiver Pro-Lizenz verfügbar
> **Referenz**: `docs/design-settings.md` für Settings-Struktur

---

## 1. Übersicht

### 1.1 Ziel

Der Design & Branding Tab ermöglicht Pro-Nutzern die visuelle Anpassung des Plugins an ihr Corporate Design. Alle Änderungen werden:

1. In Echtzeit in der **Live-Vorschau** angezeigt
2. Als **CSS Custom Properties** im Frontend ausgegeben
3. In der **WordPress-Datenbank** persistiert (`rp_design_settings`)

### 1.2 Architektur-Entscheidungen

| Aspekt | Entscheidung | Begründung |
|--------|--------------|------------|
| UI-Komponenten | **shadcn/ui** | Siehe `admin-ui-architecture.md` - keine @wordpress/components |
| Styling | Tailwind CSS mit `rp-` Prefix | WordPress-Kompatibilität |
| State | React useState + useSettings Hook | Konsistent mit anderen Settings |
| Persistierung | REST API → `wp_options` | Standard-Pattern im Plugin |

### 1.3 Farblogik-Architektur (WICHTIG)

```
┌─────────────────────────────────────────────────────────────────┐
│                     FARB-KASKADE                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  STUFE 1: Primärfarbe definieren                                │
│  ════════════════════════════════                               │
│                                                                 │
│  use_theme_colors: true ──→ Primärfarbe vom Theme               │
│  use_theme_colors: false ─→ Eigene Primärfarbe (primary_color)  │
│                                                                 │
│         ↓                                                       │
│         ↓  EINE Primärfarbe für alles                           │
│         ↓                                                       │
│                                                                 │
│  STUFE 2: Automatische Vererbung (Default)                      │
│  ═════════════════════════════════════════                      │
│                                                                 │
│  Primärfarbe wird automatisch verwendet für:                    │
│  ├── Button-Hintergrund                                         │
│  ├── H3-Überschriften (Akzentfarbe)                             │
│  ├── Links                                                      │
│  ├── Focus-Ringe                                                │
│  ├── Badges (Basis)                                             │
│  └── AI-Button (Default)                                        │
│                                                                 │
│  STUFE 3: Optionale Überschreibungen (Opt-In)                   │
│  ════════════════════════════════════════════                   │
│                                                                 │
│  Wer WILL, kann einzelne Elemente überschreiben:                │
│                                                                 │
│  override_button_colors: false (default)                        │
│     └── true → Eigene Button-Farben (button_bg_color etc.)      │
│                                                                 │
│  Badge-Farben: Immer individuell anpassbar                      │
│                                                                 │
│  AI-Button: Eigener Stil-Modus (theme/preset/manual)            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Vorteile dieser Architektur:**
- Primärfarbe ist immer die "Wahrheit"
- Konsistentes Design ohne Widersprüche
- Überschreibungen sind explizit opt-in
- Kein Szenario möglich wo Buttons anders aussehen als Rest

### 1.4 Vererbungs-Matrix

Die Design-Einstellungen beeinflussen folgende Elemente:

```
┌─────────────────────────────────────────────────────────────────┐
│                    BETROFFENE ELEMENTE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Job-Liste (Grid/Liste)                                      │
│  2. Job-Cards im Grid                                           │
│  3. Stellenausschreibung (Content der Detailseite)              │
│  4. Formularbox auf Detailseite                                 │
│  5. "Jetzt Bewerben" Button (Header der Detailseite)            │
│  6. KI-Buttons (aktuell 1, später 3)                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### Vererbungs-Regeln

| Einstellung | Job-Card | Stellen-Content | Formularbox | Buttons | "Jetzt Bewerben" | KI-Button |
|-------------|----------|-----------------|-------------|---------|------------------|-----------|
| **Card-Design** |
| `card_border_radius` | ✅ | - | ✅ | - | - | - |
| `card_shadow` | ✅ | - | ✅ | - | - | - |
| `card_border_*` | ✅ | - | ✅ | - | - | - |
| `card_background` | ✅ | - | ✅ | - | - | - |
| `card_hover_effect` | ✅ | - | ❌ | - | - | - |
| **Typografie** |
| `font_size_*` | ✅ | ✅ | ✅ | - | - | - |
| `line_height_*` | ✅ | ✅ | ✅ | - | - | - |
| `heading_margin_*` | - | ✅ | - | - | - | - |
| `paragraph_spacing` | - | ✅ | - | - | - | - |
| `link_*` | ✅ | ✅ | ✅ | - | - | - |
| **Button-Design** |
| Primärfarbe | - | - | - | ✅ | ✅ | ✅* |
| `button_*_color` | - | - | - | ✅ | ✅ | ❌ |
| `button_border_*` | - | - | - | ✅ | ✅ | ❌ |
| `button_size` | - | - | - | ✅ | ❌ | ❌ |
| `button_border_radius` | - | - | - | ✅ | ✅ | ❌ |
| `button_shadow*` | - | - | - | ✅ | ✅ | ❌ |

**Legende:**
- ✅ = Wird vererbt
- ❌ = Wird NICHT vererbt (eigene Werte)
- \* = Nur wenn `ai_button_style = theme`

#### Besondere Regeln

**Stellenausschreibung (Content):**
- Erbt alle Typografie-Einstellungen
- Heading-Abstände wirken NUR hier (nicht in Cards)
- Links im Content folgen Link-Styling

**Formularbox:**
- Erbt Card-Design (Radius, Schatten, Border, Hintergrund)
- Erbt NICHT Hover-Effekt (statisches Element)

**"Jetzt Bewerben" Button (Header):**
- Erbt Button-Farben und Radius
- Hat FESTE Größe (nicht konfigurierbar) → prominente Position erfordert konsistente Größe
- Grund: UX - dieser Button muss immer gut sichtbar und klickbar sein

**KI-Buttons:**
- Eigenes Styling-System (theme/preset/manual)
- Bei `theme`: Erbt nur Primärfarbe
- Bei `preset`/`manual`: Komplett unabhängig
- Vorbereitet für 3 KI-Buttons an verschiedenen Positionen

### 1.5 Datei-Struktur

```
plugin/assets/src/js/admin/settings/
├── tabs/
│   └── DesignTab.jsx              # Haupt-Container mit Sub-Tabs
├── components/
│   └── design/
│       ├── BrandingPanel.jsx      # Tab: Branding
│       ├── TypographyPanel.jsx    # Tab: Typografie
│       ├── CardsPanel.jsx         # Tab: Cards
│       ├── ButtonsPanel.jsx       # Tab: Buttons
│       ├── JobListPanel.jsx       # Tab: Job-Liste
│       ├── AiButtonPanel.jsx      # Tab: KI-Button
│       └── LivePreview.jsx        # Sidebar-Komponente
├── hooks/
│   └── useDesignSettings.js       # Design-spezifischer State-Hook
```

---

## 2. UI-Layout

### 2.1 Grundstruktur

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ [Allgemein] [Firmendaten] [Export] [Benutzerrollen] [Design & Branding]     │
├─────────────────────────────────────────────────────────────────────────────┤
│ [Branding] [Typografie] [Cards] [Buttons] [Job-Liste] [KI-Button]           │
├───────────────────────────────────────────────┬─────────────────────────────┤
│                                               │                             │
│  ┌─────────────────────────────────────────┐  │  ┌───────────────────────┐  │
│  │ Card: Einstellungen                     │  │  │ Live-Vorschau         │  │
│  │                                         │  │  │ Änderungen werden     │  │
│  │ [Setting 1]                             │  │  │ sofort angezeigt      │  │
│  │ [Setting 2]                             │  │  ├───────────────────────┤  │
│  │ [Setting 3]                             │  │  │ JOB-CARD              │  │
│  │                                         │  │  │ ┌─────────────────┐   │  │
│  └─────────────────────────────────────────┘  │  │ │ [Neu] [IT]      │   │  │
│                                               │  │ │ Senior Dev...   │   │  │
│  ┌─────────────────────────────────────────┐  │  │ │ Berlin Vollzeit │   │  │
│  │ Card: Weitere Einstellungen             │  │  │ │ 60k-80k [Btn]   │   │  │
│  │ ...                                     │  │  │ └─────────────────┘   │  │
│  └─────────────────────────────────────────┘  │  ├───────────────────────┤  │
│                                               │  │ BUTTONS               │  │
│                                               │  │ [Jetzt bewerben]      │  │
│                                               │  │ [Merken]              │  │
│                                               │  ├───────────────────────┤  │
│                                               │  │ TYPOGRAFIE            │  │
│                                               │  │ H1 Text               │  │
│                                               │  │ H2 Text               │  │
│                                               │  │ H3 Text               │  │
│                                               │  │ Fließtext             │  │
│                                               │  │ Kleiner Text          │  │
│                                               │  ├───────────────────────┤  │
│                                               │  │ PRIMÄRFARBE           │  │
│                                               │  │ [■] #2563eb           │  │
│                                               │  └───────────────────────┘  │
│                                               │                             │
├───────────────────────────────────────────────┴─────────────────────────────┤
│                                                    [Speichern]              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Container-Breiten

| Bereich | Breite | Tailwind-Klasse |
|---------|--------|-----------------|
| Gesamt-Container | 1100px | `rp-max-w-[1100px]` |
| Settings-Panel (links) | ~65% | `rp-flex-1` |
| Live-Vorschau (rechts) | 320px | `rp-w-80` |

### 2.3 UI-Design-Prinzip: Kompaktes Backend

> **WICHTIG**: Das Admin-UI soll kompakt und übersichtlich sein.

**Gruppierung statt Einzelkarten:**

```
❌ FALSCH - Jedes Setting eine eigene Card:
┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
│ Card: Eckenradius   │  │ Card: Schatten      │  │ Card: Rahmen        │
│ [Slider]            │  │ [RadioGroup]        │  │ [Switch] [Color]    │
└─────────────────────┘  └─────────────────────┘  └─────────────────────┘

✅ RICHTIG - Verwandte Settings gruppiert:
┌────────────────────────────────────────────────────────────────────────┐
│ Card: Erscheinungsbild                                                 │
│                                                                        │
│ Eckenradius      [─────●─────] 8px                                     │
│ Schatten         ○ Keiner  ● Leicht  ○ Mittel  ○ Stark                 │
│ Rahmen           [===ON===]  [■ #e5e7eb]                               │
│ Hintergrund      [■ #ffffff]                                           │
└────────────────────────────────────────────────────────────────────────┘
```

**Regeln:**
1. **Eine Card pro Themenbereich**, nicht pro Setting
2. **Inline-Layouts** wo möglich (Label + Control in einer Zeile)
3. **Bedingte Felder** einrücken statt verstecken (visueller Zusammenhang)
4. **Vertikaler Platz sparen** - User soll möglichst wenig scrollen

**Card-Gruppierung pro Tab:**

| Tab | Anzahl Cards | Gruppierung |
|-----|--------------|-------------|
| Branding | 2-3 | Farben, Logo, White-Label |
| Typografie | 2 | Schrift & Abstände, Links |
| Cards | 2 | Layout-Preset, Erscheinungsbild |
| Buttons | 2 | Farben, Form & Effekte |
| Job-Liste | 2 | Layout, Badges |
| KI-Buttons | 2 | Stil, Texte |

### 2.4 shadcn/ui Komponenten-Mapping

| Funktion | shadcn/ui Komponente | Notizen |
|----------|---------------------|---------|
| Sub-Tabs | `Tabs`, `TabsList`, `TabsTrigger`, `TabsContent` | Bereits vorhanden |
| Einstellungs-Cards | `Card`, `CardHeader`, `CardTitle`, `CardContent` | Bereits vorhanden |
| Switch (An/Aus) | `Switch` | Bereits vorhanden |
| Farbauswahl | **Eigene `ColorPicker`** | Muss erstellt werden |
| Range-Slider | **Eigene `Slider`** | Muss erstellt werden |
| Radio-Gruppe | **Eigene `RadioGroup`** | Muss erstellt werden |
| Button-Gruppe | **Eigene `ButtonGroup`** | Muss erstellt werden |
| Select | `Select` | Bereits vorhanden |
| Text-Input | `Input` | Bereits vorhanden |

---

## 3. Settings-Struktur

### 3.1 Tab: Branding

#### Card: Farben

| ID | Label | Key | Typ | Default | CSS-Variable |
|----|-------|-----|-----|---------|--------------|
| B-01 | Theme-Farben verwenden | `use_theme_colors` | Switch | `true` | - |
| B-02 | Primärfarbe | `primary_color` | ColorPicker | `#2563eb` | `--rp-primary-color` |

**Logik B-01**: Wenn aktiv, wird `primary_color` aus dem Theme gelesen (`get_theme_mod('primary_color')` oder Customizer). B-02 wird dann disabled/ausgegraut.

#### Card: Logo

| ID | Label | Key | Typ | Default | Notizen |
|----|-------|-----|-----|---------|---------|
| B-03 | Theme-Logo verwenden | `use_theme_logo` | Switch | `true` | - |
| B-04 | Eigenes Logo | `custom_logo_id` | MediaUpload | `null` | Attachment-ID |
| B-05 | Logo in Signatur anzeigen | `logo_in_signature` | Switch | `false` | E-Mail-Signatur |
| B-06 | Position | `signature_logo_position` | Select | `top` | top/bottom/left |
| B-07 | Max. Höhe | `signature_logo_max_height` | Slider | `60` | 30-120px |

**Logik B-03**: Wenn aktiv, wird Custom Logo ID aus Theme gelesen (`get_theme_mod('custom_logo')`). B-04 wird dann disabled.

**Logik B-05**: Aktiviert B-06 und B-07 nur wenn `true`.

#### Card: White-Label

| ID | Label | Key | Typ | Default | Notizen |
|----|-------|-----|-----|---------|---------|
| B-08 | Branding ausblenden | `hide_branding` | Switch | `false` | "Powered by RP" |
| B-09 | White-Label E-Mails | `hide_email_branding` | Switch | `false` | E-Mail-Footer |

---

### 3.2 Tab: Typografie

> Typografie-Einstellungen wirken auf: Job-Cards, **Stellenausschreibung (Content)**, Formularbox

#### Card: Schriftgrößen

| ID | Label | Key | Typ | Default | Range | CSS-Variable |
|----|-------|-----|-----|---------|-------|--------------|
| T-01 | H1 | `font_size_h1` | Slider | `2.25` | 1.5-4 rem | `--rp-font-size-h1` |
| T-02 | H2 | `font_size_h2` | Slider | `1.875` | 1.25-3 rem | `--rp-font-size-h2` |
| T-03 | H3 | `font_size_h3` | Slider | `1.5` | 1-2.5 rem | `--rp-font-size-h3` |
| T-04 | Text | `font_size_body` | Slider | `1` | 0.875-1.25 rem | `--rp-font-size-body` |
| T-05 | Klein | `font_size_small` | Slider | `0.875` | 0.625-1 rem | `--rp-font-size-small` |

#### Card: Zeilenabstand

| ID | Label | Key | Typ | Default | Range | CSS-Variable |
|----|-------|-----|-----|---------|-------|--------------|
| T-06 | Überschriften | `line_height_heading` | Slider | `1.2` | 1.0-1.5 | `--rp-line-height-heading` |
| T-07 | Fließtext | `line_height_body` | Slider | `1.6` | 1.3-2.0 | `--rp-line-height-body` |

#### Card: Abstände (Stellenausschreibung)

> Diese Einstellungen beeinflussen das Layout des Stellenausschreibungs-Contents.

| ID | Label | Key | Typ | Default | Range | CSS-Variable |
|----|-------|-----|-----|---------|-------|--------------|
| T-08 | Abstand über Überschriften | `heading_margin_top` | Slider | `1.5` | 0.5-3 em | `--rp-heading-margin-top` |
| T-09 | Abstand unter Überschriften | `heading_margin_bottom` | Slider | `0.5` | 0.25-1.5 em | `--rp-heading-margin-bottom` |
| T-10 | Absatz-Abstand | `paragraph_spacing` | Slider | `1` | 0.5-2 em | `--rp-paragraph-spacing` |

**Anwendung im Content:**
```
┌──────────────────────────────────────┐
│                                      │
│  ↕ heading_margin_top (1.5em)        │
│                                      │
│  ## Ihre Aufgaben                    │
│                                      │
│  ↕ heading_margin_bottom (0.5em)     │
│                                      │
│  Lorem ipsum dolor sit amet...       │
│                                      │
│  ↕ paragraph_spacing (1em)           │
│                                      │
│  Consectetur adipiscing elit...      │
│                                      │
└──────────────────────────────────────┘
```

#### Card: Links

| ID | Label | Key | Typ | Default | CSS-Variable |
|----|-------|-----|-----|---------|--------------|
| T-11 | Primärfarbe verwenden | `link_use_primary` | Switch | `true` | - |
| T-12 | Link-Farbe | `link_color` | ColorPicker | `#2563eb` | `--rp-link-color` |
| T-13 | Unterstreichung | `link_decoration` | RadioGroup | `underline` | `--rp-link-decoration` |

**Optionen T-13:**
- `none`: Keine Unterstreichung
- `underline`: Immer unterstrichen
- `hover`: Nur bei Hover unterstrichen

**Logik T-11**: Wenn `true`, wird `--rp-link-color` von `--rp-primary-color` übernommen. T-12 wird disabled.

**CSS-Output für Links:**
```css
.rp-job-content a,
.rp-card a {
  color: var(--rp-link-color);
  text-decoration: var(--rp-link-decoration);
}

/* Bei hover-only */
.rp-job-content a:hover {
  text-decoration: underline;
}
```

---

### 3.3 Tab: Cards

#### Card 1: Layout-Preset

| ID | Label | Key | Typ | Default | Optionen |
|----|-------|-----|-----|---------|----------|
| C-00 | Card-Layout | `card_layout_preset` | RadioGroup | `standard` | kompakt/standard/grosszuegig |

**Preset-Definitionen:**

| Aspekt | Kompakt | Standard | Großzügig |
|--------|---------|----------|-----------|
| Padding innen | 12px | 20px | 32px |
| Badge-Position | Inline mit Datum | Nach Datum | Eigene Zeile oben |
| Beschreibung | Ausgeblendet | 2-3 Zeilen | 4+ Zeilen |
| Tag-Layout | Inline, eine Zeile | Flex-wrap | Vertikal gestapelt |
| Button-Layout | Nebeneinander, klein | Nebeneinander, normal | Gestapelt, volle Breite |
| Button-Position | Rechts unten | Links unten | Zentriert unten |

#### Card 2: Erscheinungsbild

> Alle visuellen Card-Eigenschaften in einer kompakten Card.

| ID | Label | Key | Typ | Default | Optionen/Range | CSS-Variable |
|----|-------|-----|-----|---------|----------------|--------------|
| C-01 | Eckenradius | `card_border_radius` | Slider | `8` | 0-24 px | `--rp-card-radius` |
| C-02 | Schatten | `card_shadow` | RadioGroup | `light` | none/light/medium/strong | `--rp-card-shadow` |
| C-03 | Rahmen | `card_border_show` | Switch | `true` | - | - |
| C-04 | Rahmenfarbe | `card_border_color` | ColorPicker | `#e5e7eb` | (wenn C-03 aktiv) | `--rp-card-border-color` |
| C-05 | Hintergrund | `card_background` | ColorPicker | `#ffffff` | - | `--rp-card-bg` |
| C-06 | Hover-Effekt | `card_hover_effect` | RadioGroup | `lift` | none/lift/glow/border | - |

**Schatten-Werte**: none / light (`0 1px 3px`) / medium (`0 4px 6px`) / strong (`0 10px 25px`)

**Hover-Effekte**: none / lift (hochheben) / glow (Leuchten) / border (Rahmen färben)

---

### 3.4 Tab: Buttons

#### Card 1: Farben

| ID | Label | Key | Typ | Default | CSS-Variable |
|----|-------|-----|-----|---------|--------------|
| BTN-01 | Eigene Button-Farben | `override_button_colors` | Switch | `false` | - |
| BTN-02 | Hintergrund | `button_bg_color` | ColorPicker | `#2563eb` | `--rp-btn-bg` |
| BTN-03 | Hintergrund (Hover) | `button_bg_color_hover` | ColorPicker | `#1d4ed8` | `--rp-btn-bg-hover` |
| BTN-04 | Text | `button_text_color` | ColorPicker | `#ffffff` | `--rp-btn-text` |
| BTN-05 | Text (Hover) | `button_text_color_hover` | ColorPicker | `#ffffff` | `--rp-btn-text-hover` |

**Standard (BTN-01 = false)**: Buttons erben Primärfarbe, Hover 10% dunkler, Text weiß.

#### Card 2: Form & Effekte

| ID | Label | Key | Typ | Default | Optionen/Range | CSS-Variable |
|----|-------|-----|-----|---------|----------------|--------------|
| BTN-06 | Größe | `button_size` | ButtonGroup | `medium` | small/medium/large | `--rp-btn-padding` |
| BTN-07 | Eckenradius | `button_border_radius` | Slider | `6` | 0-50 px | `--rp-btn-radius` |
| BTN-08 | Rahmen | `button_border_show` | Switch | `false` | - | - |
| BTN-09 | Rahmenfarbe | `button_border_color` | ColorPicker | `#2563eb` | (wenn BTN-08) | `--rp-btn-border` |
| BTN-10 | Rahmenbreite | `button_border_width` | Slider | `1` | 1-5 px | `--rp-btn-border-width` |
| BTN-11 | Schatten | `button_shadow` | Select | `none` | none/light/medium/strong | `--rp-btn-shadow` |
| BTN-12 | Schatten (Hover) | `button_shadow_hover` | Select | `light` | none/light/medium/strong | `--rp-btn-shadow-hover` |

**Größen**: small (0.5rem 1rem) / medium (0.75rem 1.5rem) / large (1rem 2rem)

---

### 3.5 Tab: Job-Liste

#### Card 1: Layout & Anzeige

| ID | Label | Key | Typ | Default | Optionen |
|----|-------|-----|-----|---------|----------|
| JL-01 | Darstellung | `job_list_layout` | RadioGroup | `grid` | grid/list |
| JL-02 | Spaltenanzahl | `job_list_columns` | RadioGroup | `3` | 2/3/4 (nur bei grid) |
| JL-03 | Badges anzeigen | `show_badges` | Switch | `true` | - |
| JL-04 | Gehalt anzeigen | `show_salary` | Switch | `true` | - |
| JL-05 | Standort anzeigen | `show_location` | Switch | `true` | - |
| JL-06 | Beschäftigungsart | `show_employment_type` | Switch | `true` | - |
| JL-07 | Bewerbungsfrist | `show_deadline` | Switch | `false` | - |

#### Card 2: Badge-Farben

| ID | Label | Key | Typ | Default | CSS-Variable |
|----|-------|-----|-----|---------|--------------|
| JL-08 | Badge-Stil | `badge_style` | RadioGroup | `light` | light/solid |
| JL-09 | Neu | `badge_color_new` | ColorPicker | `#22c55e` | `--rp-badge-new` |
| JL-10 | Remote | `badge_color_remote` | ColorPicker | `#8b5cf6` | `--rp-badge-remote` |
| JL-11 | Kategorie | `badge_color_category` | ColorPicker | `#6b7280` | `--rp-badge-category` |
| JL-12 | Gehalt | `badge_color_salary` | ColorPicker | `#2563eb` | `--rp-badge-salary` |

**Badge-Stil**: light (10% Opacity Hintergrund, farbiger Text) / solid (voller Hintergrund, weißer Text)

---

### 3.6 Tab: KI-Buttons

> **Hinweis**: Dieses System ist für 3 KI-Buttons vorbereitet (aktuell 1 implementiert).
> Alle Buttons teilen sich das **globale Stil-System**, haben aber individuelle Texte.

#### Übersicht: KI-Button Positionen (Roadmap)

| Button | Position | Status |
|--------|----------|--------|
| KI-Matching | Job-Card / Detailseite | ✅ Implementiert |
| KI-Button 2 | *TBD* | 🔜 Geplant |
| KI-Button 3 | *TBD* | 🔜 Geplant |

#### Card: Globaler KI-Button Stil

| ID | Label | Key | Typ | Default | Optionen |
|----|-------|-----|-----|---------|----------|
| AI-01 | Stil-Modus | `ai_button_style` | RadioGroup | `preset` | theme/preset/manual |

**Stil-Modi:**
- `theme`: Erbt Primärfarbe → einheitlich mit anderen Buttons
- `preset`: Vordefinierte KI-Styles (empfohlen)
- `manual`: Volle Kontrolle über alle Farben

#### Card: Preset-Auswahl (nur bei style=preset)

| ID | Label | Key | Typ | Default | Optionen |
|----|-------|-----|-----|---------|----------|
| AI-02 | Design | `ai_button_preset` | RadioGroup | `gradient` | gradient/outline/minimal/glow/soft |

**Presets** (visuelle Vorschau im UI):

| Preset | Beschreibung |
|--------|--------------|
| `gradient` | Lila-Pink Verlauf, weiß Text, Schatten |
| `outline` | Transparent, lila Rahmen, lila Text |
| `minimal` | Grauer Hintergrund, dunkler Text |
| `glow` | Lila mit Glow-Effekt |
| `soft` | Helles Lila, lila Text |

#### Card: Manuelle Farben (nur bei style=manual)

**Kompakte Darstellung** - alle Farben in einer Card:

| ID | Label | Key | Typ | Default |
|----|-------|-----|-----|---------|
| AI-03 | Farbverlauf | `ai_button_use_gradient` | Switch | `true` |
| AI-04 | Farbe 1 | `ai_button_color_1` | ColorPicker | `#8b5cf6` |
| AI-05 | Farbe 2 | `ai_button_color_2` | ColorPicker | `#ec4899` |
| AI-06 | Textfarbe | `ai_button_text_color` | ColorPicker | `#ffffff` |
| AI-07 | Radius | `ai_button_radius` | Slider (0-24) | `8` |

**Logik AI-03**: Bei `false` wird nur `ai_button_color_1` als Hintergrund verwendet.

#### Card: Button-Texte (pro Button)

> Jeder KI-Button hat einen eigenen Text und Icon-Setting.

**KI-Matching Button:**

| ID | Label | Key | Typ | Default |
|----|-------|-----|-----|---------|
| AI-10 | Text | `ai_match_button_text` | Input | `KI-Matching starten` |
| AI-11 | Icon | `ai_match_button_icon` | Select | `sparkles` |

**Verfügbare Icons:** sparkles, checkmark, star, lightning, target, user

**KI-Button 2** *(zukünftig)*:

| ID | Label | Key | Typ | Default |
|----|-------|-----|-----|---------|
| AI-20 | Text | `ai_button_2_text` | Input | `TBD` |
| AI-21 | Icon | `ai_button_2_icon` | Select | `sparkles` |

**KI-Button 3** *(zukünftig)*:

| ID | Label | Key | Typ | Default |
|----|-------|-----|-----|---------|
| AI-30 | Text | `ai_button_3_text` | Input | `TBD` |
| AI-31 | Icon | `ai_button_3_icon` | Select | `sparkles` |

#### CSS-Variablen (generiert)

```css
/* Alle KI-Buttons teilen diese Variablen */
--rp-ai-btn-bg: ...;
--rp-ai-btn-text: ...;
--rp-ai-btn-radius: ...;
/* Bei Gradient */
--rp-ai-btn-gradient: linear-gradient(135deg, var(--color-1), var(--color-2));
```

---

## 4. Live-Vorschau Komponente

### 4.1 Struktur

```jsx
<div className="rp-w-80 rp-sticky rp-top-4">
  <Card>
    <CardHeader>
      <CardTitle>Live-Vorschau</CardTitle>
      <CardDescription>Änderungen werden sofort angezeigt</CardDescription>
    </CardHeader>
    <CardContent className="rp-space-y-6">
      {/* JOB-CARD Section */}
      <PreviewSection title="JOB-CARD">
        <JobCardPreview settings={settings} />
      </PreviewSection>

      {/* FORMULARBOX Section */}
      <PreviewSection title="FORMULARBOX">
        <FormBoxPreview settings={settings} />
      </PreviewSection>

      {/* BUTTONS Section */}
      <PreviewSection title="BUTTONS">
        <ButtonsPreview settings={settings} />
        {/* Inkl. "Jetzt Bewerben" mit fester Größe */}
      </PreviewSection>

      {/* KI-BUTTON Section */}
      <PreviewSection title="KI-BUTTON">
        <AiButtonPreview settings={settings} />
      </PreviewSection>

      {/* TYPOGRAFIE Section */}
      <PreviewSection title="TYPOGRAFIE">
        <TypographyPreview settings={settings} />
      </PreviewSection>

      {/* PRIMÄRFARBE Section */}
      <PreviewSection title="PRIMÄRFARBE">
        <ColorSwatchPreview color={settings.primary_color} />
      </PreviewSection>
    </CardContent>
  </Card>
</div>
```

### 4.2 Preview-Komponenten

#### JobCardPreview

Zeigt eine Mini-Job-Card mit:
- Badges (Neu, Kategorie) - verwendet Badge-Farben
- Titel "Senior Developer (m/w/d)"
- Location + Employment Type
- Kurze Beschreibung (truncated)
- Gehalt + Details-Button

Reagiert auf: Card-Einstellungen, Badge-Einstellungen, Button-Einstellungen, Typografie

#### FormBoxPreview

Zeigt eine Mini-Formularbox mit:
- Card-Rahmen (erbt von Card-Design)
- Überschrift "Jetzt bewerben"
- Placeholder für Formularfelder (Name, E-Mail)
- Submit-Button

Reagiert auf: Card-Einstellungen (Radius, Schatten, Border, Hintergrund), Button-Einstellungen

#### ButtonsPreview

Zeigt drei Buttons:
- "Jetzt bewerben" (Header-Größe, **feste Größe**)
- Primary: "Bewerben" (konfigurierbare Größe)
- Secondary/Outline: "Merken"

Reagiert auf: Button-Farben, Button-Radius, Button-Schatten
**Nicht auf**: Button-Größe beim "Jetzt bewerben" Header-Button

#### AiButtonPreview

Zeigt den KI-Button mit:
- Aktueller Preset oder manuelle Farben
- Icon + Text

Reagiert auf: Alle KI-Button-Einstellungen (ai_button_*)

#### TypographyPreview

Zeigt einen Mini-Stellenausschreibungs-Ausschnitt:

```
┌─────────────────────────────────┐
│                                 │
│  H2: Ihre Aufgaben              │  ← Schriftgröße, Zeilenabstand
│  ↕ heading_margin_bottom        │
│  Lorem ipsum dolor sit amet,    │  ← Fließtext, line-height
│  consectetur adipiscing elit.   │
│  ↕ paragraph_spacing            │
│  Mehr erfahren (Link)           │  ← Link-Farbe, Decoration
│                                 │
└─────────────────────────────────┘
```

Reagiert auf:
- Schriftgrößen (T-01 bis T-05)
- Zeilenabstand (T-06, T-07)
- Heading-Abstände (T-08, T-09)
- Absatz-Abstand (T-10)
- Link-Styling (T-11 bis T-13)

#### ColorSwatchPreview

Zeigt:
- Farbquadrat mit aktueller Primärfarbe
- Hex-Wert als Text

---

## 5. CSS-Output

### 5.1 Generierte CSS-Variablen

Die Settings werden als CSS Custom Properties im `<head>` ausgegeben:

```css
:root {
  /* Farben */
  --rp-primary-color: #2563eb;

  /* Typografie - Schriftgrößen */
  --rp-font-size-h1: 2.25rem;
  --rp-font-size-h2: 1.875rem;
  --rp-font-size-h3: 1.5rem;
  --rp-font-size-body: 1rem;
  --rp-font-size-small: 0.875rem;

  /* Typografie - Zeilenabstand */
  --rp-line-height-heading: 1.2;
  --rp-line-height-body: 1.6;

  /* Typografie - Abstände (Stellenausschreibung) */
  --rp-heading-margin-top: 1.5em;
  --rp-heading-margin-bottom: 0.5em;
  --rp-paragraph-spacing: 1em;

  /* Typografie - Links */
  --rp-link-color: #2563eb;  /* oder var(--rp-primary-color) */
  --rp-link-decoration: underline;

  /* Cards */
  --rp-card-radius: 8px;
  --rp-card-shadow: 0 1px 3px rgba(0,0,0,0.1);
  --rp-card-border-color: #e5e7eb;
  --rp-card-bg: #ffffff;

  /* Buttons */
  --rp-btn-bg: #2563eb;
  --rp-btn-bg-hover: #1d4ed8;
  --rp-btn-text: #ffffff;
  --rp-btn-text-hover: #ffffff;
  --rp-btn-border: transparent;
  --rp-btn-border-hover: transparent;
  --rp-btn-border-width: 0px;
  --rp-btn-radius: 6px;
  --rp-btn-shadow: none;
  --rp-btn-shadow-hover: 0 1px 3px rgba(0,0,0,0.1);

  /* Badges */
  --rp-badge-new: #22c55e;
  --rp-badge-remote: #8b5cf6;
  --rp-badge-category: #6b7280;
  --rp-badge-salary: #2563eb;

  /* AI Button */
  --rp-ai-btn-bg: linear-gradient(135deg, #8b5cf6, #ec4899);
  --rp-ai-btn-text: #ffffff;
  --rp-ai-btn-radius: 8px;
  --rp-ai-btn-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
}

/* Stellenausschreibung Content */
.rp-job-content h1,
.rp-job-content h2,
.rp-job-content h3 {
  margin-top: var(--rp-heading-margin-top);
  margin-bottom: var(--rp-heading-margin-bottom);
  line-height: var(--rp-line-height-heading);
}

.rp-job-content p {
  margin-bottom: var(--rp-paragraph-spacing);
  line-height: var(--rp-line-height-body);
}

.rp-job-content a {
  color: var(--rp-link-color);
  text-decoration: var(--rp-link-decoration);
}
```

### 5.2 PHP-Implementierung

```php
// src/Frontend/DesignStyles.php

class DesignStyles {
    public function outputCssVariables(): void {
        $settings = get_option('rp_design_settings', []);
        $defaults = $this->getDefaults();
        $merged = wp_parse_args($settings, $defaults);

        $css = ':root {' . PHP_EOL;

        // Primärfarbe
        $primary = $merged['use_theme_colors']
            ? $this->getThemePrimaryColor()
            : $merged['primary_color'];
        $css .= "  --rp-primary-color: {$primary};" . PHP_EOL;

        // ... weitere Variablen

        $css .= '}';

        wp_add_inline_style('rp-frontend', $css);
    }
}
```

---

## 6. Fallback-Verhalten

### 6.1 Theme-Integration

| Setting | Fallback wenn Theme keine Daten liefert |
|---------|----------------------------------------|
| `use_theme_colors` = true | Fallback auf `#2563eb` |
| `use_theme_logo` = true | Fallback auf Plugin-Logo oder kein Logo |
| `override_button_colors` = false | Buttons erben Primärfarbe (Theme oder Custom) |

### 6.2 Fehlende Settings

Wenn ein Setting nicht in der Datenbank existiert, wird der Default-Wert verwendet. Die `getDefaults()`-Methode liefert alle Defaults.

### 6.3 Ungültige Werte

| Typ | Validierung | Fallback |
|-----|-------------|----------|
| Color | Regex `/^#[0-9A-Fa-f]{6}$/` | Default-Farbe |
| Slider | `min <= value <= max` | Default oder min/max |
| Select/Radio | `in_array($value, $options)` | Default |
| Switch | `is_bool()` | `false` |

---

## 7. REST API

### 7.1 Endpoints

```
GET  /wp-json/recruiting/v1/settings/design
POST /wp-json/recruiting/v1/settings/design
```

### 7.2 Request/Response

```json
// GET Response
{
  "use_theme_colors": true,
  "primary_color": "#2563eb",
  "font_size_h1": 2.25,
  "card_border_radius": 8,
  // ... alle Settings
}

// POST Request
{
  "primary_color": "#dc2626",
  "card_border_radius": 12
}

// POST Response
{
  "success": true,
  "data": { /* merged settings */ }
}
```

---

## 8. Neue UI-Komponenten

Diese Komponenten müssen erstellt werden (existieren noch nicht in shadcn/ui):

### 8.1 ColorPicker

```jsx
// components/ui/color-picker.jsx

export function ColorPicker({ value, onChange, disabled }) {
  return (
    <div className="rp-flex rp-items-center rp-gap-3">
      <input
        type="color"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
        className="rp-w-10 rp-h-10 rp-rounded rp-border rp-cursor-pointer disabled:rp-opacity-50"
      />
      <Input
        value={value}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
        className="rp-w-28 rp-font-mono"
        placeholder="#000000"
      />
    </div>
  );
}
```

### 8.2 Slider

```jsx
// components/ui/slider.jsx

export function Slider({ value, onChange, min, max, step = 1, unit = '', disabled }) {
  return (
    <div className="rp-flex rp-items-center rp-gap-4">
      <input
        type="range"
        value={value}
        onChange={(e) => onChange(parseFloat(e.target.value))}
        min={min}
        max={max}
        step={step}
        disabled={disabled}
        className="rp-flex-1"
      />
      <span className="rp-w-16 rp-text-sm rp-text-muted-foreground rp-text-right">
        {value}{unit}
      </span>
    </div>
  );
}
```

### 8.3 RadioGroup

```jsx
// components/ui/radio-group.jsx

export function RadioGroup({ value, onChange, options, disabled }) {
  return (
    <div className="rp-flex rp-flex-wrap rp-gap-2">
      {options.map((option) => (
        <button
          key={option.value}
          type="button"
          onClick={() => onChange(option.value)}
          disabled={disabled}
          className={cn(
            "rp-px-3 rp-py-1.5 rp-text-sm rp-rounded-md rp-border rp-transition-colors",
            value === option.value
              ? "rp-bg-primary rp-text-primary-foreground rp-border-primary"
              : "rp-bg-background rp-border-input hover:rp-bg-accent",
            disabled && "rp-opacity-50 rp-cursor-not-allowed"
          )}
        >
          {option.label}
        </button>
      ))}
    </div>
  );
}
```

### 8.4 ButtonGroup

```jsx
// components/ui/button-group.jsx

export function ButtonGroup({ value, onChange, options, disabled }) {
  return (
    <div className="rp-inline-flex rp-rounded-md rp-border rp-border-input">
      {options.map((option, index) => (
        <button
          key={option.value}
          type="button"
          onClick={() => onChange(option.value)}
          disabled={disabled}
          className={cn(
            "rp-px-3 rp-py-1.5 rp-text-sm rp-transition-colors",
            index > 0 && "rp-border-l rp-border-input",
            value === option.value
              ? "rp-bg-primary rp-text-primary-foreground"
              : "rp-bg-background hover:rp-bg-accent",
            disabled && "rp-opacity-50 rp-cursor-not-allowed"
          )}
        >
          {option.label}
        </button>
      ))}
    </div>
  );
}
```

---

## 9. Test-Szenarien

### 9.1 Manuelle Tests (vor Implementation)

| # | Test | Erwartetes Ergebnis |
|---|------|---------------------|
| 1 | Primärfarbe ändern | Live-Vorschau zeigt neue Farbe in Buttons, H3, Badges, Links |
| 2 | Card-Radius auf 0 setzen | Job-Card wird eckig (keine Rundungen) |
| 3 | "Eigene Button-Farben" aktivieren | Button-Farb-Einstellungen (BTN-02 bis BTN-05) werden aktiv |
| 4 | Badge-Stil auf "solid" | Badges haben volle Hintergrundfarbe, weißen Text |
| 5 | AI-Button Preset wechseln | Vorschau zeigt neuen Preset-Stil |
| 6 | Primärfarbe ändern bei override_button_colors=false | Buttons ändern Farbe mit (Kaskade funktioniert) |
| 7 | Heading-Abstand oben erhöhen | Mehr Platz über H2/H3 in Stellenausschreibung |
| 8 | Zeilenabstand Fließtext auf 2.0 | Text in Vorschau wird luftiger |
| 9 | Link-Unterstreichung auf "hover" | Links ohne Unterstrich, bei Hover erscheint sie |
| 10 | Card-Layout auf "Kompakt" | Card wird kleiner, Badges inline, keine Beschreibung |
| 11 | Card-Layout auf "Großzügig" | Card wird größer, Buttons gestapelt, mehr Whitespace |

### 9.2 Edge Cases

| # | Szenario | Erwartetes Verhalten |
|---|----------|---------------------|
| 1 | Theme hat keine Primärfarbe | Fallback auf `#2563eb` |
| 2 | Ungültiger Hex-Wert eingegeben | Input wird rot, Wert nicht übernommen |
| 3 | Settings leer (neues Plugin) | Alle Defaults werden angewendet |
| 4 | Pro-Lizenz läuft ab | Tab verschwindet, Settings bleiben erhalten |
| 5 | Custom Primärfarbe + override_button_colors=false | Buttons haben Custom Primärfarbe (konsistent) |
| 6 | Theme-Farbe + override_button_colors=true | Eigene Button-Farben, unabhängig von Primärfarbe |

---

## 10. Implementation-Reihenfolge

### Phase 0: Hardcodierte Werte identifizieren (VOR der Implementation)

> **WICHTIG**: Nach dem Git-Reset auf den Stand vor der alten Implementation
> enthält die Codebasis hardcodierte Werte. Diese müssen ZUERST identifiziert
> und dokumentiert werden.

**Ziel**: Liste aller Stellen, die von hardcodierten Werten auf CSS-Variablen umgestellt werden müssen.

**Vorgehen**:

1. **CSS-Dateien durchsuchen** nach hardcodierten Werten:
   ```bash
   # Farben (Hex)
   grep -rn "#[0-9A-Fa-f]\{3,6\}" plugin/assets/src/css/ --include="*.css"

   # rgb/rgba
   grep -rn "rgb\|rgba" plugin/assets/src/css/ --include="*.css"

   # Pixel-Werte für Radien/Schatten
   grep -rn "border-radius\|box-shadow" plugin/assets/src/css/ --include="*.css"
   ```

2. **Templates durchsuchen** nach Inline-Styles:
   ```bash
   grep -rn "style=" plugin/templates/ --include="*.php"
   ```

3. **Für jeden Fund dokumentieren**:
   | Datei | Zeile | Aktueller Wert | Ersetzen durch |
   |-------|-------|----------------|----------------|
   | `main.css` | 42 | `#2563eb` | `var(--rp-color-primary)` |
   | `main.css` | 108 | `border-radius: 8px` | `var(--rp-card-radius)` |

4. **Kategorien prüfen**:
   - [ ] Primärfarbe und Varianten
   - [ ] Textfarben (muted, light)
   - [ ] Card-Styles (Radius, Schatten, Border, Background)
   - [ ] Button-Styles
   - [ ] Badge-Farben
   - [ ] Schriftgrößen (H1-H6, Body, Small)

**Ergebnis**: Tabelle mit allen Änderungen → wird in Phase 1 umgesetzt.

**Review**: `Prüfe Phase 0 mit dem design-branding-reviewer Agent`

---

### Phase 1: Backend-Grundlagen

- [ ] `src/Services/DesignService.php` - Settings-Management mit Defaults
- [ ] `src/Services/CssGeneratorService.php` - CSS-Variablen generieren
- [ ] REST API Endpoint `recruiting/v1/settings/design`
- [ ] CSS-Variablen in `main.css` einführen (aus Phase 0 Liste)
- [ ] Templates auf CSS-Klassen umstellen (aus Phase 0 Liste)
- [ ] **Schriftgrößen-Variablen mit Fallback** (siehe unten)

#### Schriftgrößen: Pro-Override mit Fallback

**Problem**: `main.css` hat feste Schriftgrößen (`--rp-text-4xl` etc.), die das Theme nicht sprengen.
Pro-User sollen diese aber überschreiben können.

**Lösung**: CSS-Fallback-Pattern

```css
/* main.css - Basis-Variablen (FEST, schützt vor Theme-Chaos) */
:root {
  --rp-text-4xl: 2.25rem;
  --rp-text-3xl: 1.875rem;
  --rp-text-2xl: 1.5rem;
  /* ... */
}

/* Headings mit Fallback auf Basis-Variablen */
.rp-plugin h1 { font-size: var(--rp-font-size-h1, var(--rp-text-4xl)); }
.rp-plugin h2 { font-size: var(--rp-font-size-h2, var(--rp-text-3xl)); }
.rp-plugin h3 { font-size: var(--rp-font-size-h3, var(--rp-text-2xl)); }
.rp-plugin h4 { font-size: var(--rp-font-size-h4, var(--rp-text-xl)); }
.rp-plugin h5 { font-size: var(--rp-font-size-h5, var(--rp-text-lg)); }
.rp-plugin .rp-text-body { font-size: var(--rp-font-size-body, var(--rp-text-base)); }
.rp-plugin .rp-text-small { font-size: var(--rp-font-size-small, var(--rp-text-sm)); }
```

**CssGeneratorService generiert** (nur wenn Custom-Werte gesetzt):
```css
.rp-plugin {
  --rp-font-size-h1: 2.5rem;
  --rp-font-size-h2: 2rem;
  /* ... */
}
```

**Verhalten**:
| Szenario | Ergebnis |
|----------|----------|
| Free-Version | Fallback greift → feste Basis-Werte |
| Pro ohne Custom | Fallback greift → feste Basis-Werte |
| Pro mit Custom | Custom-Variable überschreibt Fallback |

**Review**: `Prüfe Phase 1 mit dem design-branding-reviewer Agent`

### Phase 2: UI-Komponenten (shadcn/ui)

- [ ] `components/ui/color-picker.jsx`
- [ ] `components/ui/slider.jsx`
- [ ] `components/ui/radio-group.jsx`
- [ ] `components/ui/button-group.jsx`

**Review**: `Prüfe Phase 2 mit dem design-branding-reviewer Agent`

### Phase 3: Design-Tab Grundstruktur

- [ ] `settings/tabs/DesignTab.jsx` - Container mit Sub-Tabs
- [ ] `settings/components/design/LivePreview.jsx` - Sidebar
- [ ] `settings/hooks/useDesignSettings.js` - State-Hook

**Review**: `Prüfe Phase 3 mit dem design-branding-reviewer Agent`

### Phase 4: Panel-Komponenten

- [ ] `BrandingPanel.jsx` (Farben, Logo, White-Label)
- [ ] `TypographyPanel.jsx` (Schriftgrößen, Zeilenabstand, Abstände, Links)
- [ ] `CardsPanel.jsx` (Layout-Preset, Erscheinungsbild)
- [ ] `ButtonsPanel.jsx` (Farben, Form & Effekte)
- [ ] `JobListPanel.jsx` (Layout & Anzeige, Badge-Farben)
- [ ] `AiButtonPanel.jsx` (Stil, Texte)

**Review**: `Prüfe Phase 4 mit dem design-branding-reviewer Agent`

### Phase 5: Testing & Feinschliff

- [ ] Manuelle Tests (11 Szenarien aus Abschnitt 9.1)
- [ ] Edge Cases prüfen (6 Szenarien aus Abschnitt 9.2)
- [ ] Reset-Button testen
- [ ] Pro-Degradation testen (Lizenz deaktivieren)

**Final Review**: `Prüfe Phase 5 mit dem design-branding-reviewer Agent`

---

## 11. Entschiedene Fragen

| Frage | Entscheidung |
|-------|--------------|
| MediaUpload für Logo | WordPress Media Library |
| Speicher-Verhalten | Expliziter "Speichern"-Button (kein Auto-Save) |
| Reset-Funktion | **Ja** - "Alle Werte zurücksetzen" Button |
| Import/Export | Nein |

---

## 12. Freemius Pro-Lizenz Degradation

### Szenario: Pro-Lizenz läuft ab

| Komponente | Verhalten |
|------------|-----------|
| Design & Branding Tab | Verschwindet (isPro-Gate) |
| Settings in Datenbank | Bleiben erhalten |
| CSS-Variablen Output | **Weiterhin aktiv** |
| Anpasstes Design | Bleibt sichtbar |

### Architektur-Regel

```
┌──────────────────────┐     ┌──────────────────────┐
│  Settings UI (React) │     │  CSS Output (PHP)    │
├──────────────────────┤     ├──────────────────────┤
│  ✅ Prüft isPro      │     │  ❌ Prüft NICHT Pro  │
│                      │     │                      │
│  Tab nur sichtbar    │     │  Gibt IMMER CSS      │
│  wenn Pro aktiv      │     │  Variablen aus       │
└──────────────────────┘     └──────────────────────┘
```

**Begründung**:
- Design des Nutzers bleibt nach Ablauf erhalten
- Keine visuellen Störungen auf der Website
- Bei Reaktivierung sind alle Settings sofort wieder da

### Free-Version (ohne jemals Pro)

- Kein Zugang zum Design-Tab
- Nutzt Default-Werte (`use_theme_*` = true)
- Folgt dem Theme bei Farben, Logo, Buttons
- Schriftgrößen: Feste Fallback-Werte (schützt vor Theme-Chaos)

---

## 13. Theme-Folgen Prinzip

### Grundsatz

Das Plugin folgt dem Theme so weit wie möglich. Nur wo nötig werden eigene Werte verwendet.

### Defaults (Free & Pro bei Aktivierung)

| Einstellung | Default | Bedeutung |
|-------------|---------|-----------|
| `use_theme_colors` | `true` | Primärfarbe vom Theme |
| `use_theme_logo` | `true` | Logo vom Theme |
| `override_button_colors` | `false` | Buttons erben Primärfarbe (kein Override) |
| `use_theme_font` | `true` | Font-Family vom Theme |

**Wichtig**: Bei Pro-Aktivierung ändert sich optisch NICHTS, solange keine Custom-Einstellungen vorgenommen werden.

**Farbkaskade**: Primärfarbe (Theme oder Custom) → fließt automatisch in Buttons, H3, Links, Badges etc. Nur bei `override_button_colors = true` werden individuelle Button-Farben möglich.

### Ausnahme: Schriftgrößen

Schriftgrößen werden **NICHT** vom Theme übernommen, weil:
- Extreme Theme-Werte können das Card-Layout sprengen
- Konsistentes Erscheinungsbild in Job-Cards wichtig

**Lösung**: Feste Basis-Werte mit Pro-Override-Möglichkeit (siehe Phase 1, Abschnitt 10).

---

## 14. Phasen-Workflow (für jeden Phase wiederholen)

```
┌─────────────────────────────────────────────────────────────────┐
│                    WORKFLOW PRO PHASE                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐                                                │
│  │ 1. UMSETZEN │  Phase X implementieren                        │
│  └──────┬──────┘                                                │
│         ▼                                                       │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ 2. REVIEW   │  design-branding-reviewer Agent starten   │    │
│  │             │                                           │    │
│  │  Aufruf: "Prüfe Phase X mit design-branding-reviewer"   │    │
│  └──────┬──────────────────────────────────────────────────┘    │
│         ▼                                                       │
│  ┌─────────────┐                                                │
│  │ 3. FIXEN    │  ALLE Vorschläge umsetzen                      │
│  │             │  (außer nicht sinnvoll - begründen!)           │
│  └──────┬──────┘                                                │
│         ▼                                                       │
│  ┌─────────────┐                                                │
│  │ 4. DOCS     │  Dokumentation aktualisieren                   │
│  └──────┬──────┘                                                │
│         ▼                                                       │
│  ┌─────────────┐                                                │
│  │ 5. TESTS    │  Tests schreiben (wenn sinnvoll)               │
│  └──────┬──────┘                                                │
│         ▼                                                       │
│  ┌─────────────┐                                                │
│  │ 6. COMMIT   │  Phase X abschließen                           │
│  └──────┬──────┘                                                │
│         ▼                                                       │
│      PHASE X+1                                                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Schritt 2: design-branding-reviewer Agent

> **WICHTIG**: Verwende den spezialisierten `design-branding-reviewer` Agent!
> Dieser kennt die Spec im Detail und hat phasenspezifische Checklisten.

**Aufruf:**
```
Prüfe Phase X mit dem design-branding-reviewer Agent
```

**Der Agent prüft (phasenspezifisch):**

| Phase | Prüfungen |
|-------|-----------|
| 0 | Hardcodierte Werte vollständig identifiziert? |
| 1 | DesignService, CssGenerator, CSS-Variablen, Freemius-Logik |
| 2 | shadcn/ui, Tailwind rp-Prefix, UI-Komponenten |
| 3 | DesignTab, LivePreview, useDesignSettings |
| 4 | Kompaktes UI, bedingte Logik, Farbkaskade |
| 5 | Test-Szenarien, Edge Cases |

**Spezielle Prüfungen (immer):**
- [ ] Farbkaskade: Primärfarbe → Buttons, H3, Links
- [ ] Vererbungs-Matrix korrekt
- [ ] "Jetzt Bewerben" hat feste Größe
- [ ] KI-Buttons eigenes System
- [ ] Keine @wordpress/components
- [ ] Tailwind mit `rp-` Prefix

**Agent-Datei:** `.claude/agents/design-branding-reviewer.md`

### Schritt 4: Dokumentation aktualisieren

- [ ] Diese Spec aktualisieren falls Abweichungen
- [ ] Code-Kommentare wo nötig
- [ ] JSDoc für neue Funktionen/Komponenten

### Schritt 5: Tests (wenn sinnvoll)

| Phase | Tests sinnvoll? | Begründung |
|-------|-----------------|------------|
| Phase 0 | Nein | Nur Analyse |
| Phase 1 | Ja | DesignService, CssGeneratorService |
| Phase 2 | Nein | UI-Komponenten visuell testen |
| Phase 3 | Nein | Container-Struktur |
| Phase 4 | Nein | UI-Panels visuell testen |
| Phase 5 | - | Ist die Test-Phase |

---

## 15. Git-Workflow

### Vor Implementation (einmalig)

```bash
# 1. Commit vor der alten Implementation finden
git log --oneline feature/design-branding
# → d7c5152 ist VOR der Design & Branding Implementation

# 2. Auf diesen Stand zurücksetzen
git checkout feature/design-branding
git reset --hard d7c5152

# 3. Diese Spec in den Branch holen
git checkout feature/custom-fields -- docs/technical/design-branding-specification-v2.md

# 4. Phase 0 starten
```

### Nach jeder Phase

```bash
# Änderungen committen
git add .
git commit -m "Design & Branding: Phase X - [Beschreibung]"
```

### Branch-Strategie

- Arbeiten auf `feature/design-branding`
- Nach Fertigstellung: PR in `feature/pro`
- Dann: Merge in `main`

---

## 16. Checkliste für frischen Chat

Wenn du diese Spec in einem neuen Chat verwendest:

1. [ ] Lies diese komplette Spec
2. [ ] Prüfe aktuellen Git-Stand (`git log --oneline -5`)
3. [ ] Falls noch nicht resettet: Git-Reset durchführen (Abschnitt 15)
4. [ ] **Prüfe ob `design-branding-reviewer` Agent existiert** (`.claude/agents/`)
5. [ ] Phase 0 starten: Hardcodierte Werte identifizieren
6. [ ] Workflow pro Phase befolgen (Abschnitt 14)
7. [ ] **Nach JEDER Phase: `design-branding-reviewer` Agent aufrufen!**

### Review-Agent Verwendung

```
# Nach jeder Phase:
"Prüfe Phase X mit dem design-branding-reviewer Agent"

# Beispiele:
"Prüfe Phase 0 mit dem design-branding-reviewer Agent"
"Prüfe Phase 1 mit dem design-branding-reviewer Agent"
```

**Der Agent ist Pflicht** - keine Phase gilt als abgeschlossen ohne Agent-Review!

---

*Erstellt: Februar 2025*
*Version: 2.0*

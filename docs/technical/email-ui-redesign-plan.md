# E-Mail UI Redesign Plan

## Aktuelle Probleme (basierend auf Screenshots)

### 1. E-Mail-Verlauf Tabelle (tabelle.JPG)

| Problem | Beschreibung |
|---------|--------------|
| Doppelte Überschrift | "E-Mail-Verlauf" erscheint zweimal - einmal als Seiten-Titel, einmal als Card-Titel |
| Fehlender Card-Rahmen | Die Card hat keinen sichtbaren Border/Shadow - wirkt flach |
| Header abgeschnitten | "Aktionen" Column-Header ist am rechten Rand abgeschnitten |
| Keine Row-Hover-Effekte | Tabellenzeilen haben keinen visuellen Hover-Feedback |
| Kein shadcn/ui Dashboard-Look | Tabelle entspricht nicht dem Design von ui.shadcn.com/examples/dashboard |

### 2. E-Mail Composer (Email-1.JPG, Email-2.JPG)

| Problem | Beschreibung |
|---------|--------------|
| Dropdown-Overlay | Platzhalter-Dropdown überlappt den Content-Bereich statt neben dem Trigger zu erscheinen |
| Chaotisches Layout | Keine klare visuelle Hierarchie zwischen den Bereichen |
| Platzhalter-Sidebar Spacing | Zu viel vertikaler Abstand zwischen den Platzhalter-Einträgen (~50px pro Item) |
| Keine Card-Struktur | Der Composer ist nicht in einer ordentlichen Card eingefasst |
| Tab-Buttons | "Verfassen"/"Vorschau" sollten als echte Tabs gestylt sein |
| Fehlende Sektions-Trennung | Keine visuelle Trennung zwischen Template, Empfänger, Betreff, Nachricht |

### 3. Platzhalter-Sidebar (Email-3.JPG)

| Problem | Beschreibung |
|---------|--------------|
| Übermäßiger Abstand | Jeder Platzhalter-Eintrag hat ~50px Höhe - viel zu viel Whitespace |
| Keine Gruppierung sichtbar | Platzhalter sollten nach Kategorien gruppiert und visuell getrennt sein |
| Keine kompakte Darstellung | Liste sollte kompakter sein für bessere Übersicht |

---

## Ziel-Design: shadcn/ui Dashboard Style

Referenz: https://ui.shadcn.com/examples/dashboard

### Design-Prinzipien:
1. **Clean Cards** - Klare Borders, subtile Shadows
2. **Konsistente Abstände** - 16px/24px Grid-System
3. **Muted Headers** - Uppercase, kleine Schrift, graue Farbe
4. **Row Hover** - Subtiler Hover-Effekt auf Tabellenzeilen
5. **Kompakte Listen** - Weniger Whitespace, mehr Content sichtbar
6. **Klare Hierarchie** - Sektionen visuell getrennt

---

## Implementierungsplan

### Phase 1: E-Mail-Verlauf Tabelle

**Datei:** `plugin/assets/src/js/admin/email/components/EmailHistory.jsx`

#### 1.1 Card-Struktur korrigieren
```jsx
// VORHER: Doppelte Überschrift
<div>
  <h2>E-Mail-Verlauf</h2>  {/* Seiten-Titel */}
  <Card>
    <CardHeader>
      <CardTitle>E-Mail Verlauf</CardTitle>  {/* Card-Titel - REDUNDANT */}
    </CardHeader>
  </Card>
</div>

// NACHHER: Nur eine Überschrift in der Card
<Card>
  <CardHeader>
    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
      <div>
        <CardTitle>E-Mail Verlauf</CardTitle>
        <CardDescription>{count} E-Mails</CardDescription>
      </div>
      <Select>...</Select>
    </div>
  </CardHeader>
  <CardContent style={{ padding: 0 }}>
    <Table>...</Table>
  </CardContent>
</Card>
```

#### 1.2 Tabellen-Styling nach shadcn/ui
```jsx
// Table Header - Muted, Uppercase
<TableHead style={{
  height: '48px',
  padding: '12px 16px',
  fontSize: '12px',
  fontWeight: 500,
  textTransform: 'uppercase',
  letterSpacing: '0.05em',
  color: '#71717a',
  backgroundColor: '#fafafa',
  borderBottom: '1px solid #e5e7eb',
}}>

// Table Row - Mit Hover
<TableRow style={{
  borderBottom: '1px solid #e5e7eb',
  transition: 'background-color 150ms',
}}
onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#fafafa'}
onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
>
```

#### 1.3 Aktionen-Spalte fixieren
- Breite der Aktionen-Spalte explizit setzen
- `textAlign: 'right'` für Header UND Cells
- Buttons mit `variant="ghost"` und `size="icon"`

---

### Phase 2: E-Mail Composer Redesign

**Datei:** `plugin/assets/src/js/admin/email/components/EmailComposer.jsx`

#### 2.1 Layout-Struktur

```
┌─────────────────────────────────────────────────────────────────┐
│ E-Mail verfassen                    [Verfassen] [Vorschau]      │
├─────────────────────────────────────┬───────────────────────────┤
│                                     │                           │
│  ┌─────────────────────────────┐   │  Platzhalter              │
│  │ Template                    │   │  ┌─────────────────────┐  │
│  │ [Dropdown                 v]│   │  │ 🔍 Suchen...       │  │
│  └─────────────────────────────┘   │  └─────────────────────┘  │
│                                     │                           │
│  ┌─────────────────────────────┐   │  BEWERBER                 │
│  │ Empfänger                   │   │  ├ Anrede                  │
│  │ [email@example.com        ] │   │  ├ Vorname                 │
│  └─────────────────────────────┘   │  ├ Nachname                │
│                                     │  └ E-Mail                  │
│  ┌─────────────────────────────┐   │                           │
│  │ Betreff        [+Platzh.]   │   │  STELLE                   │
│  │ [                         ] │   │  ├ Stellentitel            │
│  └─────────────────────────────┘   │  ├ Arbeitsort              │
│                                     │  └ ...                     │
│  ┌─────────────────────────────┐   │                           │
│  │ Nachricht                   │   │  FIRMA                    │
│  │ ┌─────────────────────────┐ │   │  ├ Firmenname              │
│  │ │ B I ≡ 1. 🔗  ↩ ↪      │ │   │  └ ...                     │
│  │ ├─────────────────────────┤ │   │                           │
│  │ │                         │ │   │                           │
│  │ │ WYSIWYG Editor Content  │ │   │                           │
│  │ │                         │ │   │                           │
│  │ └─────────────────────────┘ │   │                           │
│  └─────────────────────────────┘   │                           │
│                                     │                           │
│  ┌─────────────────────────────┐   │                           │
│  │ ○ E-Mail zeitversetzt send. │   │                           │
│  │   [📅 Zeitpunkt wählen    ] │   │                           │
│  └─────────────────────────────┘   │                           │
│                                     │                           │
│  ─────────────────────────────────────────────────────────────  │
│                          [Abbrechen] [Senden]                   │
└─────────────────────────────────────────────────────────────────┘
```

#### 2.2 Komponenten-Struktur

```jsx
<Card>
  <CardHeader>
    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
      <CardTitle>E-Mail verfassen</CardTitle>
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="compose">Verfassen</TabsTrigger>
          <TabsTrigger value="preview">Vorschau</TabsTrigger>
        </TabsList>
      </Tabs>
    </div>
  </CardHeader>

  <CardContent>
    {activeTab === 'compose' ? (
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 280px', gap: '24px' }}>
        {/* Hauptbereich */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          <FormField label="Template">
            <Select>...</Select>
          </FormField>

          <FormField label="Empfänger">
            <Input type="email" />
          </FormField>

          <FormField label="Betreff" action={<PlaceholderButton />}>
            <Input />
          </FormField>

          <FormField label="Nachricht">
            <RichTextEditor />
          </FormField>

          <ScheduleToggle />
        </div>

        {/* Platzhalter-Sidebar */}
        <PlaceholderSidebar />
      </div>
    ) : (
      <EmailPreview />
    )}
  </CardContent>

  <CardFooter>
    <Button variant="outline">Abbrechen</Button>
    <Button>Senden</Button>
  </CardFooter>
</Card>
```

#### 2.3 Platzhalter-Popover Position Fix

Das "+Platzhalter" Button-Popover muss:
- `placement="bottom-start"` verwenden
- Nicht den Content-Bereich überlappen
- Z-Index korrekt setzen

```jsx
<Popover placement="bottom-start">
  <PopoverTrigger asChild>
    <Button variant="outline" size="sm">
      <Plus className="w-4 h-4 mr-1" />
      Platzhalter
    </Button>
  </PopoverTrigger>
  <PopoverContent style={{ width: '280px', maxHeight: '400px', overflow: 'auto' }}>
    {/* Platzhalter-Liste */}
  </PopoverContent>
</Popover>
```

---

### Phase 3: Platzhalter-Sidebar Redesign

**Datei:** `plugin/assets/src/js/admin/email/components/PlaceholderPicker.jsx`

#### 3.1 Kompaktere Darstellung

```jsx
// VORHER: ~50px pro Item
<li style={{ padding: '16px' }}>
  <span>{placeholder}</span>
</li>

// NACHHER: ~32px pro Item
<li style={{ padding: '6px 12px' }}>
  <button style={{
    display: 'flex',
    alignItems: 'center',
    gap: '8px',
    width: '100%',
    padding: '6px 8px',
    borderRadius: '4px',
    background: 'transparent',
    border: 'none',
    cursor: 'pointer',
    fontSize: '13px',
    transition: 'background-color 150ms',
  }}>
    <code style={{
      fontSize: '11px',
      padding: '2px 6px',
      backgroundColor: '#f4f4f5',
      borderRadius: '4px',
      fontFamily: 'monospace',
    }}>
      {`{${key}}`}
    </code>
    <span style={{ color: '#374151' }}>{label}</span>
  </button>
</li>
```

#### 3.2 Gruppen-Header

```jsx
<div style={{ marginBottom: '16px' }}>
  <h4 style={{
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase',
    letterSpacing: '0.05em',
    color: '#71717a',
    marginBottom: '8px',
    paddingLeft: '12px',
  }}>
    {groupLabel}
  </h4>
  <ul style={{ listStyle: 'none', margin: 0, padding: 0 }}>
    {items.map(item => <PlaceholderItem key={item.key} {...item} />)}
  </ul>
</div>
```

---

### Phase 4: WYSIWYG Editor Verbesserungen

**Datei:** `plugin/assets/src/js/admin/components/ui/rich-text-editor.jsx`

#### 4.1 Toolbar-Styling

```jsx
<div style={{
  display: 'flex',
  alignItems: 'center',
  gap: '4px',
  padding: '8px 12px',
  borderBottom: '1px solid #e5e7eb',
  backgroundColor: '#fafafa',
  borderTopLeftRadius: '8px',
  borderTopRightRadius: '8px',
}}>
  <ToolbarButton icon={Bold} title="Fett (Strg+B)" />
  <ToolbarButton icon={Italic} title="Kursiv (Strg+I)" />
  <ToolbarDivider />
  <ToolbarButton icon={List} title="Aufzählung" />
  <ToolbarButton icon={ListOrdered} title="Nummerierte Liste" />
  <ToolbarDivider />
  <ToolbarButton icon={Link} title="Link einfügen" />
  <div style={{ flex: 1 }} />
  <ToolbarButton icon={Undo} title="Rückgängig" />
  <ToolbarButton icon={Redo} title="Wiederholen" />
</div>
```

#### 4.2 Editor-Bereich

```jsx
<div
  contentEditable
  style={{
    minHeight: '250px',
    padding: '16px',
    outline: 'none',
    fontSize: '14px',
    lineHeight: 1.6,
    color: '#1f2937',
  }}
/>
```

---

## Zu erstellende/aktualisierenden Dateien

| Datei | Aktion | Beschreibung |
|-------|--------|--------------|
| `EmailHistory.jsx` | Aktualisieren | Card-Struktur, Tabellen-Styling |
| `EmailComposer.jsx` | Komplett neu | Neues Layout mit Grid, bessere Struktur |
| `PlaceholderPicker.jsx` | Aktualisieren | Kompaktere Darstellung, Gruppen-Styling |
| `RichTextEditor.jsx` | Aktualisieren | Toolbar-Styling verbessern |
| `EmailTab.jsx` | Prüfen | Sicherstellen dass kein doppelter Titel |

---

## Design-Tokens (zur Konsistenz)

```javascript
const designTokens = {
  // Spacing
  spacing: {
    xs: '4px',
    sm: '8px',
    md: '16px',
    lg: '24px',
    xl: '32px',
  },

  // Colors
  colors: {
    text: '#1f2937',
    textMuted: '#71717a',
    border: '#e5e7eb',
    background: '#ffffff',
    backgroundMuted: '#fafafa',
    primary: '#1d71b8',
  },

  // Typography
  fontSize: {
    xs: '11px',
    sm: '13px',
    base: '14px',
    lg: '16px',
  },

  // Border Radius
  radius: {
    sm: '4px',
    md: '8px',
    lg: '12px',
    full: '9999px',
  },
};
```

---

## Akzeptanzkriterien

- [ ] E-Mail-Verlauf Tabelle entspricht shadcn/ui Dashboard-Design
- [ ] Keine doppelten Überschriften
- [ ] Alle Spalten-Header vollständig sichtbar
- [ ] Row-Hover-Effekte funktionieren
- [ ] Email Composer hat klares, strukturiertes Layout
- [ ] Platzhalter-Dropdown überlappt nichts
- [ ] Platzhalter-Sidebar ist kompakt (max 32px pro Item)
- [ ] Platzhalter sind nach Kategorien gruppiert
- [ ] WYSIWYG Editor ist gut gestylt
- [ ] Responsive auf verschiedenen Bildschirmgrößen
- [ ] Konsistente Abstände und Farben

---

## Priorität

1. **Hoch**: Email Composer Layout (größte UX-Probleme)
2. **Hoch**: Platzhalter-Sidebar Spacing
3. **Mittel**: Tabellen-Styling
4. **Mittel**: Doppelte Überschriften entfernen
5. **Niedrig**: WYSIWYG Toolbar Feinschliff

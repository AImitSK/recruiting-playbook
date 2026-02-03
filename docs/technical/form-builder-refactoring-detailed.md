# Form Builder Refactoring - Detaillierter Implementierungsplan

## Übersicht

Dieses Dokument enthält den detaillierten Implementierungsplan für das Form Builder Refactoring basierend auf der Code-Analyse.

---

## Aktuelle Architektur

```
FormBuilder.jsx (Hauptkomponente)
├── useFormConfig.js        → Step-basierte Form-Struktur (Draft/Publish)
├── useFieldDefinitions.js  → Field Library (System + Custom)
│
├── Tab "Formular"
│   └── FormEditor.jsx
│       ├── SortableStep (Sub-Komponente)
│       │   ├── SortableFieldItem (draggable fields)
│       │   └── SystemFieldCard (nicht draggbar)
│       └── SystemFieldSettings Modals
│
├── Tab "Felder"
│   ├── FieldList.jsx
│   │   └── FieldListItem.jsx
│   └── FieldEditor.jsx (Sidebar)
│       ├── OptionsEditor.jsx
│       ├── ValidationEditor.jsx
│       └── ConditionalEditor.jsx
│
└── Tab "Vorschau"
    └── FormPreview.jsx
        └── FieldPreview.jsx
```

---

## Phase 1: Breiten-Icon zu Feld-Anzeige hinzufügen

### Ziel
Zeige die Feldbreite (volle/halbe Breite) direkt am Feld im FormEditor an.

### Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `FormEditor.jsx` | Breiten-Icon in `SortableFieldItem` hinzufügen |

### Implementierung

**1. SortableFieldItem erweitern (FormEditor.jsx:61-174)**

```jsx
// Aktuell: Zeile 139-147
{ fieldConfig.is_required ? (
  <Badge style={{ backgroundColor: '#ef4444', ... }}>Pflicht</Badge>
) : (
  <Badge variant="outline" ...>Optional</Badge>
)}

// NEU: Nach der Pflicht/Optional Badge hinzufügen:
{ /* Breiten-Icon */ }
<span
  style={{
    display: 'inline-flex',
    alignItems: 'center',
    gap: '1px',
    color: '#9ca3af',
    fontSize: '0.75rem',
    marginLeft: '0.25rem'
  }}
  title={ fieldConfig.width === 'half'
    ? __( 'Halbe Breite', 'recruiting-playbook' )
    : __( 'Volle Breite', 'recruiting-playbook' )
  }
>
  { fieldConfig.width === 'half' ? (
    <>
      <span style={{ width: '6px', height: '10px', backgroundColor: '#9ca3af', borderRadius: '1px' }} />
      <span style={{ width: '6px', height: '10px', backgroundColor: '#e5e7eb', borderRadius: '1px' }} />
    </>
  ) : (
    <>
      <span style={{ width: '6px', height: '10px', backgroundColor: '#9ca3af', borderRadius: '1px' }} />
      <span style={{ width: '6px', height: '10px', backgroundColor: '#9ca3af', borderRadius: '1px' }} />
    </>
  )}
</span>
```

### Akzeptanzkriterien
- [ ] Volle Breite zeigt zwei gefüllte Blöcke [██]
- [ ] Halbe Breite zeigt einen gefüllten und einen leeren Block [█ ]
- [ ] Tooltip zeigt "Volle Breite" / "Halbe Breite"
- [ ] Default (wenn width nicht gesetzt) = Volle Breite

---

## Phase 2: Edit-Button für Custom Fields hinzufügen

### Ziel
Custom Fields können direkt im FormEditor bearbeitet werden (ohne Felder Tab).

### Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `FormEditor.jsx` | Edit-Button in `SortableFieldItem`, Modal-State |
| `FieldEditor.jsx` | Als Modal umbauen (oder neue Komponente) |

### Implementierung

**1. Custom Field Erkennung**

Custom Fields erkennen wir über `is_system === false` in der FieldDefinition:

```jsx
// In SortableFieldItem
const isCustomField = fieldDef && !fieldDef.is_system;
```

**2. Edit-Button hinzufügen (FormEditor.jsx:150-171)**

```jsx
// Zwischen Breiten-Icon und Delete-Button:
{ isCustomField && (
  <Button
    variant="ghost"
    size="sm"
    onClick={ () => onEditField( stepId, fieldConfig.field_key ) }
    style={{ color: '#3b82f6' }}
    title={ __( 'Feld bearbeiten', 'recruiting-playbook' ) }
  >
    <Edit2 style={{ height: '1rem', width: '1rem' }} />
  </Button>
)}
```

**3. Props erweitern**

```jsx
// SortableFieldItem Props
function SortableFieldItem({
  fieldConfig,
  stepId,
  fieldDef,
  onRemove,
  onEditField,  // NEU
}) { ... }

// SortableStep muss onEditField durchreichen
// FormEditor muss State für editingField verwalten
```

**4. Modal State in FormEditor**

```jsx
// Neuer State
const [editingField, setEditingField] = useState(null);

// Handler
const handleEditField = (stepId, fieldKey) => {
  const fieldDef = getFieldDefinition(fieldKey);
  if (fieldDef && !fieldDef.is_system) {
    setEditingField(fieldDef);
  }
};

// Modal am Ende von FormEditor
{editingField && (
  <FieldEditorModal
    field={editingField}
    fieldTypes={fieldTypes}
    onUpdate={handleFieldUpdate}
    onClose={() => setEditingField(null)}
    i18n={i18n}
  />
)}
```

**5. FieldEditorModal erstellen (neue Komponente)**

Basiert auf `FieldEditor.jsx`, aber:
- Als Modal (mit Overlay)
- Ohne "Bedingte Anzeige" Tab
- Schließen-Button und Speichern-Button

```jsx
// components/FieldEditorModal.jsx
export default function FieldEditorModal({
  field,
  fieldTypes,
  onUpdate,
  onClose,
  i18n,
}) {
  // Inhalt von FieldEditor, aber:
  // - Nur Tabs: "Allgemein" und "Validierung"
  // - Modal-Wrapper mit Overlay
  // - Footer mit "Abbrechen" und "Speichern"
}
```

### Props-Kette

```
FormBuilder
  └── FormEditor
      ├── onEditField (neu)
      ├── fieldTypes (neu - für Modal)
      └── onFieldUpdate (neu - für Modal)
          └── SortableStep
              └── SortableFieldItem
                  └── onEditField
```

### Akzeptanzkriterien
- [ ] Edit-Button (✏️) erscheint nur bei Custom Fields
- [ ] Edit-Button öffnet Modal mit Feld-Einstellungen
- [ ] Modal zeigt: Label, Platzhalter, Pflichtfeld, Breite, Optionen (wenn applicable)
- [ ] Änderungen werden gespeichert und im Formular reflektiert
- [ ] System-Felder haben keinen Edit-Button

---

## Phase 3: FieldTypeSelector zu Dropdown umbauen

### Ziel
Neue Felder werden über ein Modal mit Dropdown (statt Grid) erstellt.

### Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `FieldTypeSelector.jsx` | Komplett umbauen zu Dropdown + Formular |

### Aktueller Stand (FieldTypeSelector.jsx)

```jsx
// Grid-Layout mit Karten pro Feldtyp
// Kategorien: basic, choice, advanced, layout
// Pro-Lock auf bestimmten Types
```

### Neue Struktur

```jsx
export default function FieldTypeSelector({
  fieldTypes,
  onSelect,
  onClose,
  isPro,
  i18n,
}) {
  const [selectedType, setSelectedType] = useState(null);
  const [fieldSettings, setFieldSettings] = useState({
    label: '',
    placeholder: '',
    is_required: false,
    width: 'full',
    options: [],
  });

  return (
    <Modal onClose={onClose}>
      <ModalHeader>
        {__('Neues Feld erstellen', 'recruiting-playbook')}
      </ModalHeader>

      <ModalBody>
        {/* Feldtyp Dropdown */}
        <Label>{__('Feldtyp', 'recruiting-playbook')}</Label>
        <Select
          value={selectedType}
          onChange={setSelectedType}
        >
          {Object.entries(fieldTypes).map(([type, config]) => (
            <SelectOption
              key={type}
              value={type}
              disabled={config.isPro && !isPro}
            >
              {config.icon} {config.label}
              {config.isPro && !isPro && <Lock />}
            </SelectOption>
          ))}
        </Select>

        {/* Einstellungen (nur wenn Typ gewählt) */}
        {selectedType && (
          <FieldSettingsForm
            type={selectedType}
            settings={fieldSettings}
            onChange={setFieldSettings}
            fieldTypes={fieldTypes}
            i18n={i18n}
          />
        )}
      </ModalBody>

      <ModalFooter>
        <Button variant="outline" onClick={onClose}>
          {__('Abbrechen', 'recruiting-playbook')}
        </Button>
        <Button
          onClick={() => onSelect(selectedType, fieldSettings)}
          disabled={!selectedType || !fieldSettings.label}
        >
          {__('Feld erstellen', 'recruiting-playbook')}
        </Button>
      </ModalFooter>
    </Modal>
  );
}
```

### FieldSettingsForm (Sub-Komponente)

```jsx
function FieldSettingsForm({ type, settings, onChange, fieldTypes, i18n }) {
  const hasOptions = ['select', 'radio', 'checkbox'].includes(type);

  return (
    <div className="field-settings-form">
      {/* Bezeichnung */}
      <div>
        <Label>{__('Bezeichnung', 'recruiting-playbook')}</Label>
        <Input
          value={settings.label}
          onChange={(e) => onChange({ ...settings, label: e.target.value })}
          placeholder={__('z.B. Anrede', 'recruiting-playbook')}
        />
      </div>

      {/* Platzhalter */}
      <div>
        <Label>{__('Platzhalter', 'recruiting-playbook')}</Label>
        <Input
          value={settings.placeholder}
          onChange={(e) => onChange({ ...settings, placeholder: e.target.value })}
        />
      </div>

      {/* Pflichtfeld */}
      <div>
        <Switch
          checked={settings.is_required}
          onChange={(checked) => onChange({ ...settings, is_required: checked })}
        />
        <Label>{__('Pflichtfeld', 'recruiting-playbook')}</Label>
      </div>

      {/* Breite */}
      <div>
        <Label>{__('Breite', 'recruiting-playbook')}</Label>
        <RadioGroup
          value={settings.width}
          onChange={(value) => onChange({ ...settings, width: value })}
        >
          <Radio value="full">{__('Volle Breite', 'recruiting-playbook')}</Radio>
          <Radio value="half">{__('Halbe Breite', 'recruiting-playbook')}</Radio>
        </RadioGroup>
      </div>

      {/* Optionen (nur für select/radio/checkbox) */}
      {hasOptions && (
        <div>
          <Label>{__('Optionen', 'recruiting-playbook')}</Label>
          <OptionsEditor
            options={settings.options}
            onChange={(options) => onChange({ ...settings, options })}
          />
        </div>
      )}
    </div>
  );
}
```

### Akzeptanzkriterien
- [ ] Dropdown zeigt alle Feldtypen mit Icons
- [ ] Pro-Features sind gesperrt wenn !isPro
- [ ] Nach Typauswahl erscheinen Einstellungen
- [ ] Optionen-Editor erscheint bei select/radio/checkbox
- [ ] "Feld erstellen" Button erstellt Feld mit allen Einstellungen
- [ ] Feld erscheint direkt im aktuellen Step

---

## Phase 4: Felder Tab entfernen, Modal-Flow implementieren

### Ziel
Nur noch 2 Tabs: "Formular" und "Vorschau". Felder werden direkt im Formular-Tab verwaltet.

### Betroffene Dateien

| Datei | Änderung |
|-------|----------|
| `FormBuilder.jsx` | Tab "Felder" entfernen, Modal-Logik integrieren |
| `FormEditor.jsx` | "+ Feld hinzufügen" öffnet FieldTypeSelector Modal |

### Änderungen in FormBuilder.jsx

**1. Imports bereinigen**

```jsx
// ENTFERNEN:
import FieldList from './components/FieldList';
// FieldEditor bleibt (wird in Modal verwendet)

// NEU:
import FieldEditorModal from './components/FieldEditorModal';
```

**2. Tabs reduzieren (ca. Zeile 200+)**

```jsx
// AKTUELL:
<TabsList>
  <TabsTrigger value="form">Formular</TabsTrigger>
  <TabsTrigger value="fields">Felder</TabsTrigger>
  <TabsTrigger value="preview">Vorschau</TabsTrigger>
</TabsList>

// NEU:
<TabsList>
  <TabsTrigger value="form">Formular</TabsTrigger>
  <TabsTrigger value="preview">Vorschau</TabsTrigger>
</TabsList>
```

**3. TabsContent für "fields" entfernen**

```jsx
// ENTFERNEN: Gesamter Block
<TabsContent value="fields">
  <div className="rp-form-builder__fields-layout">
    <FieldList ... />
    {selectedField && <FieldEditor ... />}
  </div>
</TabsContent>
```

**4. State-Management anpassen**

```jsx
// ENTFERNEN:
const [selectedField, setSelectedField] = useState(null);

// NEU (für Modals):
const [showFieldTypeSelector, setShowFieldTypeSelector] = useState(false);
const [fieldTypeSelectorStepId, setFieldTypeSelectorStepId] = useState(null);
const [editingFieldKey, setEditingFieldKey] = useState(null);
```

**5. Handler für FormEditor**

```jsx
// Feld hinzufügen (öffnet Modal)
const handleAddFieldClick = (stepId) => {
  setFieldTypeSelectorStepId(stepId);
  setShowFieldTypeSelector(true);
};

// Feld erstellen (aus Modal)
const handleCreateField = async (fieldType, settings) => {
  const newField = await createField({
    type: fieldType,
    field_key: `field_${Date.now()}`,
    label: settings.label,
    placeholder: settings.placeholder,
    is_required: settings.is_required,
    width: settings.width,
    settings: { options: settings.options },
  });

  if (newField && fieldTypeSelectorStepId) {
    await refreshAvailableFields();
    addFieldToStep(fieldTypeSelectorStepId, newField.field_key, {
      is_visible: true,
      is_required: settings.is_required,
      width: settings.width,
    });
  }

  setShowFieldTypeSelector(false);
  setFieldTypeSelectorStepId(null);
};

// Feld bearbeiten
const handleEditField = (fieldKey) => {
  setEditingFieldKey(fieldKey);
};

// Feld aktualisieren
const handleFieldUpdate = async (fieldId, updates) => {
  const success = await updateField(fieldId, updates);
  if (success) {
    await refreshAvailableFields();
  }
  return success;
};
```

**6. Props an FormEditor übergeben**

```jsx
<FormEditor
  // ... bestehende Props ...
  onAddFieldClick={handleAddFieldClick}  // NEU
  onEditField={handleEditField}           // NEU
  fieldTypes={fieldTypes}                 // NEU
/>
```

**7. Modals rendern**

```jsx
// Am Ende von FormBuilder, vor dem schließenden </div>

{/* Field Type Selector Modal */}
{showFieldTypeSelector && (
  <FieldTypeSelector
    fieldTypes={fieldTypes}
    onSelect={handleCreateField}
    onClose={() => {
      setShowFieldTypeSelector(false);
      setFieldTypeSelectorStepId(null);
    }}
    isPro={isPro}
    i18n={i18n}
  />
)}

{/* Field Editor Modal */}
{editingFieldKey && (
  <FieldEditorModal
    field={getFieldDefinition(editingFieldKey)}
    fieldTypes={fieldTypes}
    onUpdate={handleFieldUpdate}
    onClose={() => setEditingFieldKey(null)}
    i18n={i18n}
  />
)}
```

### Änderungen in FormEditor.jsx

**1. "+ Feld hinzufügen" Button anpassen**

```jsx
// AKTUELL (Zeile 530-538): Öffnet Dropdown mit existierenden Feldern
<Button onClick={() => setShowAddFieldFor(step.id)}>
  + Feld hinzufügen
</Button>

// NEU: Zeigt zwei Optionen
<div style={{ display: 'flex', gap: '0.5rem' }}>
  {/* Existierendes Feld hinzufügen */}
  <Button
    variant="outline"
    size="sm"
    onClick={() => setShowAddFieldFor(step.id)}
  >
    <Plus /> {__('Vorhandenes Feld', 'recruiting-playbook')}
  </Button>

  {/* Neues Feld erstellen */}
  <Button
    variant="outline"
    size="sm"
    onClick={() => onAddFieldClick && onAddFieldClick(step.id)}
  >
    <Plus /> {__('Neues Feld erstellen', 'recruiting-playbook')}
  </Button>
</div>
```

### Akzeptanzkriterien
- [ ] Nur 2 Tabs: "Formular" und "Vorschau"
- [ ] "Neues Feld erstellen" öffnet Modal mit Feldtyp-Auswahl
- [ ] Nach Erstellung erscheint Feld im aktuellen Step
- [ ] "Vorhandenes Feld" zeigt Dropdown mit ungenutzten Feldern
- [ ] Edit-Button auf Custom Fields öffnet Edit-Modal
- [ ] Alle CRUD-Operationen funktionieren weiterhin

---

## Phase 5: Bedingte Anzeige komplett entfernen

### Ziel
Komplette Entfernung der Conditional Logic Funktionalität.

### Betroffene Dateien

| Datei | Aktion |
|-------|--------|
| `ConditionalEditor.jsx` | LÖSCHEN |
| `FieldEditor.jsx` | Tab "Bedingte Anzeige" entfernen |
| `FieldEditorModal.jsx` | Kein Conditional Tab |
| `useFieldDefinitions.js` | conditional Felder ignorieren |
| `ConditionalLogicService.php` | LÖSCHEN |
| `CustomFieldsService.php` | Conditional Aufrufe entfernen |

### Änderungen

**1. FieldEditor.jsx anpassen**

```jsx
// ENTFERNEN: Import
import ConditionalEditor from './ConditionalEditor';

// ENTFERNEN: Zeile 92-97
const updateConditional = useCallback((conditional) => {
  setLocalField((prev) => ({ ...prev, conditional }));
}, []);

// ENTFERNEN: Tab "Bedingte Anzeige" (ca. Zeile 200+)
<TabsTrigger value="conditional">
  {i18n?.conditionalTab || __('Bedingte Anzeige', 'recruiting-playbook')}
</TabsTrigger>

// ENTFERNEN: TabsContent für conditional
<TabsContent value="conditional">
  <ConditionalEditor ... />
</TabsContent>
```

**2. useFieldDefinitions.js anpassen**

```jsx
// Bei createField/updateField: conditional Feld nicht mitsenden
const sanitizeField = (field) => {
  const { conditional, ...rest } = field;
  return rest;
};
```

**3. PHP Backend**

```php
// CustomFieldsService.php - ENTFERNEN:
// - Aufrufe zu ConditionalLogicService
// - isFieldVisible() Checks
// - Alle conditional_* Logik

// ConditionalLogicService.php - LÖSCHEN
// Die gesamte Datei kann entfernt werden
```

### Akzeptanzkriterien
- [ ] Kein "Bedingte Anzeige" Tab mehr sichtbar
- [ ] ConditionalEditor.jsx gelöscht
- [ ] ConditionalLogicService.php gelöscht
- [ ] Keine console Errors
- [ ] Formular funktioniert weiterhin

---

## Phase 6: Aufräumen - ungenutzte Dateien löschen

### Zu löschende Dateien

| Datei | Grund |
|-------|-------|
| `FieldList.jsx` | Felder-Tab entfernt |
| `FieldListItem.jsx` | Felder-Tab entfernt |
| `ConditionalEditor.jsx` | Bereits in Phase 5 gelöscht |

### Zu prüfende Dateien

| Datei | Prüfung |
|-------|---------|
| `FormBuilder.jsx` | Keine ungenutzten Imports |
| `useFieldDefinitions.js` | Wird noch für CRUD benötigt |
| `FieldEditor.jsx` | Wird für Modal benötigt |

### Aufräumschritte

**1. Imports bereinigen**

```bash
# In allen Dateien prüfen:
grep -r "FieldList" plugin/assets/src/js/admin/form-builder/
grep -r "FieldListItem" plugin/assets/src/js/admin/form-builder/
grep -r "ConditionalEditor" plugin/assets/src/js/admin/form-builder/
```

**2. Dateien löschen**

```bash
rm plugin/assets/src/js/admin/form-builder/components/FieldList.jsx
rm plugin/assets/src/js/admin/form-builder/components/FieldListItem.jsx
rm plugin/assets/src/js/admin/form-builder/components/ConditionalEditor.jsx
rm plugin/src/Services/ConditionalLogicService.php
```

**3. Tests anpassen**

Falls Tests für die gelöschten Komponenten existieren, diese ebenfalls entfernen oder anpassen.

### Akzeptanzkriterien
- [ ] Keine ungenutzten Dateien mehr vorhanden
- [ ] Keine ungenutzten Imports
- [ ] `npm run build` erfolgreich
- [ ] `composer phpcs` erfolgreich
- [ ] Keine Console Errors im Browser

---

## Zusammenfassung der Änderungen

### Neue Dateien

| Datei | Beschreibung |
|-------|--------------|
| `FieldEditorModal.jsx` | Modal-Version des FieldEditors |

### Geänderte Dateien

| Datei | Änderungen |
|-------|------------|
| `FormBuilder.jsx` | Tabs reduziert, Modal-Logik |
| `FormEditor.jsx` | Breiten-Icon, Edit-Button, Modal-Trigger |
| `FieldTypeSelector.jsx` | Dropdown statt Grid |
| `FieldEditor.jsx` | Conditional Tab entfernt |
| `useFieldDefinitions.js` | Conditional Felder ignorieren |
| `CustomFieldsService.php` | Conditional Aufrufe entfernen |

### Gelöschte Dateien

| Datei | Grund |
|-------|-------|
| `FieldList.jsx` | Nicht mehr benötigt |
| `FieldListItem.jsx` | Nicht mehr benötigt |
| `ConditionalEditor.jsx` | Feature entfernt |
| `ConditionalLogicService.php` | Feature entfernt |

---

## Risiken und Mitigationen

| Risiko | Mitigation |
|--------|------------|
| Datenverlust bei Migration | Conditional Daten bleiben in DB, werden nur ignoriert |
| Breaking Changes API | REST API bleibt kompatibel, conditional Felder optional |
| UX-Regression | Jede Phase einzeln testen vor nächster Phase |

---

## Testplan pro Phase

### Phase 1 Tests
1. Neues Feld mit voller Breite erstellen → Icon [██] sichtbar
2. Feld auf halbe Breite ändern → Icon [█ ] sichtbar
3. Hover zeigt korrekten Tooltip

### Phase 2 Tests
1. Custom Field im Formular hat Edit-Button
2. System Field hat keinen Edit-Button
3. Edit-Modal öffnet sich
4. Änderungen werden gespeichert

### Phase 3 Tests
1. Dropdown zeigt alle Feldtypen
2. Pro-Typen gesperrt wenn !isPro
3. Einstellungen erscheinen nach Typauswahl
4. Feld wird erstellt mit allen Einstellungen

### Phase 4 Tests
1. Nur 2 Tabs sichtbar
2. "Neues Feld erstellen" funktioniert
3. "Vorhandenes Feld hinzufügen" funktioniert
4. CRUD-Operationen funktionieren

### Phase 5 Tests
1. Kein Conditional Tab sichtbar
2. Formular funktioniert ohne Conditional Logic
3. Keine JavaScript Errors

### Phase 6 Tests
1. Build erfolgreich
2. Keine Console Errors
3. Alle Features funktionieren

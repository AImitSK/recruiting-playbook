# Custom Fields Refactoring-Plan

> **Von v1 zu v2**: Step-basierter Formular-Builder mit Draft/Publish

---

## Übersicht

### Ausgangslage (v1)
- Feld-Definitionen in DB ✅
- Admin-UI: Einfache Feldliste ✅
- Templates-System (ungenutzt) ✅
- **Frontend: Hart kodiert** ❌
- **Keine Verbindung Builder → Frontend** ❌

### Ziel (v2)
- Step-basierter Formular-Builder
- Draft/Publish-System
- Dynamisches Frontend-Rendering
- Live-Vorschau

---

## Phase 1: Datenbank & Backend (Tag 1)

### 1.1 Neue Tabelle erstellen ✅

**Datei:** `src/Database/Schema.php` (getFormConfigTableSql)

**Tasks:**
- [x] Migration in Schema.php integriert
- [x] Standard-Config beim Aktivieren einfügen

### 1.2 FormConfigService erstellen ✅

**Datei:** `src/Services/FormConfigService.php`

**Tasks:**
- [x] Service-Klasse erstellen
- [x] Repository für DB-Zugriff (`FormConfigRepository.php`)
- [x] Default-Config definieren
- [x] Unit-Tests schreiben (`tests/Unit/Services/FormConfigServiceTest.php`)

### 1.3 FormConfigController erstellen ✅

**Datei:** `src/Api/FormConfigController.php`

**Endpunkte:**
- [x] GET `/form-builder/config`
- [x] PUT `/form-builder/config`
- [x] POST `/form-builder/publish`
- [x] POST `/form-builder/discard`
- [x] GET `/form-builder/published`

**Tasks:**
- [x] Controller-Klasse erstellen
- [x] Routes registrieren
- [x] Permission-Checks (rp_manage_forms, manage_options)
- [x] Unit-Tests schreiben (`tests/Unit/Api/FormConfigControllerTest.php`)

---

## Phase 2: Frontend-Rendering (Tag 2) ✅

### 2.1 FormRenderService implementieren ✅

**Datei:** `src/Services/FormRenderService.php`

**Tasks:**
- [x] Service-Klasse erstellen
- [x] `render(int $job_id): string` Methode
- [x] Step-Rendering
- [x] Feld-Rendering mit Templates
- [x] Alpine.js Data-Preparation

### 2.2 Feld-Templates erstellen/anpassen ✅

**Verzeichnis:** `templates/fields/`

**Bestehende Templates:**
- [x] `field-text.php` - x-model hinzugefügt
- [x] `field-email.php` - x-model hinzugefügt
- [x] `field-textarea.php` - x-model hinzugefügt
- [x] `field-select.php` - x-model hinzugefügt
- [x] `field-checkbox.php` - x-model hinzugefügt
- [x] `field-file.php` - File-Handling implementiert
- [x] `field-phone.php` - x-model hinzugefügt
- [x] `field-privacy-consent.php` - spezielles Template

### 2.3 single-job_listing.php anpassen ✅

**Datei:** `templates/single-job_listing.php`

**Tasks:**
- [x] FormRenderService integriert
- [x] Dynamisches Rendering basierend auf Published-Config

### 2.4 Alpine.js anpassen ✅

**Datei:** `assets/src/js/application-form.js`

**Tasks:**
- [x] `applicationForm()` liest Config aus `window.rpFormConfig`
- [x] Dynamische Validierung basierend auf Config
- [x] Step-Navigation für variable Step-Anzahl
- [x] `validateField()`, `hasError()`, `getError()` Methoden

---

## Phase 3: Admin-UI Refactoring (Tag 3-4) ✅

### 3.1 Komponenten-Struktur ✅

```
assets/src/js/admin/form-builder/
├── FormBuilder.jsx              # ✅ Haupt-Container mit Tabs
├── components/
│   ├── FieldList.jsx            # ✅ Für Tab "Felder" (System/Custom Fields)
│   ├── FieldEditor.jsx          # ✅ Sidebar für Feld-Einstellungen
│   ├── FormEditor.jsx           # ✅ NEU - Step-basierter Editor
│   ├── FormPreview.jsx          # ✅ Dynamische Vorschau mit Steps
│   ├── FieldTypeSelector.jsx    # ✅ Modal für Feld-Typ Auswahl
│   ├── OptionsEditor.jsx        # ✅ Für Select-Optionen
│   ├── ValidationEditor.jsx     # ✅ Validierungsregeln
│   ├── ConditionalEditor.jsx    # ✅ Bedingte Logik
│   ├── FieldPreview.jsx         # ✅ Feld-Vorschau
│   └── TemplateManager.jsx      # 🔮 Reserviert für Pro-Features
├── hooks/
│   ├── useFormConfig.js         # ✅ NEU - Config laden/speichern
│   ├── useFieldDefinitions.js   # ✅ Bestehend
│   └── useFormTemplates.js      # 🔮 Reserviert für Pro-Features
```

### 3.2 FormBuilder.jsx refactoren ✅

**Tasks:**
- [x] Tabs: "Formular" | "Felder" | "Vorschau"
- [x] State für Draft-Config via useFormConfig
- [x] useFormConfig Hook eingebunden
- [x] Publish-Status und Version im Header
- [x] Veröffentlichen/Verwerfen Buttons

### 3.3 FormEditor.jsx erstellen ✅

**Tasks:**
- [x] Step-Liste rendern (regularSteps + finaleStep)
- [x] Expand/Collapse für Steps
- [x] "Neuen Step hinzufügen"
- [x] Step löschen (wenn deletable)
- [x] Feld hinzufügen/entfernen pro Step
- [x] Pflichtfeld-Toggle
- [x] Step-Titel inline editieren

### 3.4 Step-Rendering in FormEditor ✅

**Tasks:**
- [x] Step-Header mit Badge (Nummer oder "Finale")
- [x] Feld-Liste pro Step
- [x] "+ Feld hinzufügen" öffnet unused Fields Dropdown
- [x] Löschen-Button (wenn deletable)
- [x] Finale-Step mit grünem Rahmen

### 3.5 Feld-Items in FormEditor ✅

**Tasks:**
- [x] Drag-Handle (vorbereitet)
- [x] Feld-Label + Typ-Badge
- [x] Pflichtfeld-Badge
- [x] Required-Toggle Button
- [x] Entfernen-Button

### 3.6 FieldList.jsx (Tab "Felder") ✅

**Bestehendes Component weiterhin genutzt für:**
- [x] System-Felder anzeigen
- [x] Custom-Felder anzeigen (Pro)
- [x] Neues Feld erstellen (Pro)
- [x] Feld-Editor Sidebar

### 3.7 useFormConfig Hook ✅

**Datei:** `hooks/useFormConfig.js`
- [x] State: draft, steps, settings, availableFields, publishedVersion, hasChanges
- [x] Actions: fetchConfig, saveDraft (auto-save), publish, discardDraft
- [x] Step-Operations: addStep, updateStep, removeStep, reorderSteps
- [x] Field-Operations: addFieldToStep, removeFieldFromStep, updateFieldInStep
- [x] Helpers: getUnusedFields, getFieldDefinition

---

## Phase 4: Integration & Testing (Tag 5) ✅

### 4.1 Unit-Tests ✅

**Erstellte Test-Dateien:**
- [x] `tests/Unit/Services/FormConfigServiceTest.php`
- [x] `tests/Unit/Repositories/FormConfigRepositoryTest.php`
- [x] `tests/Unit/Api/FormConfigControllerTest.php`

### 4.2 Edge Cases (in Tests abgedeckt) ✅

- [x] Missing steps
- [x] Empty steps
- [x] Missing finale step
- [x] Missing email field
- [x] Missing privacy consent
- [x] Missing step ID/title
- [x] No changes to publish/discard

### 4.3 Migration ✅

- [x] Default-Config wird bei Aktivierung erstellt
- [x] Bestehende field_definitions bleiben erhalten
- [x] Templates-Feature bleibt für zukünftige Pro-Features

---

## Phase 5: Aufräumen (Tag 6) ✅

### 5.1 Alte Dateien ✅

**Entscheidung:** Behalten für zukünftige Features
- TemplateManager.jsx → 🔮 Pro-Feature geplant
- useFormTemplates.js → 🔮 Pro-Feature geplant
- FieldList.jsx → ✅ Weiterhin für Tab "Felder" genutzt

### 5.2 Dokumentation ✅

- [x] `custom-fields-refactoring-plan.md` aktualisiert
- [x] `custom-fields-specification-v2.md` ist aktuell
- [x] WordPress-Stubs erweitert (WP_REST_Response, WP_REST_Controller)

### 5.3 Build & Tests

- [ ] `npm run build` ausführen
- [ ] `composer test` ausführen
- [ ] Git-Commit erstellen

---

## Datei-Änderungen Übersicht

### Neue Dateien

| Datei | Beschreibung |
|-------|--------------|
| `src/Database/Migrations/FormConfigMigration.php` | DB-Migration |
| `src/Services/FormConfigService.php` | Config-Verwaltung |
| `src/Repositories/FormConfigRepository.php` | DB-Zugriff |
| `src/Api/FormConfigController.php` | REST-API |
| `assets/.../FieldLibrary.jsx` | Feld-Bibliothek |
| `assets/.../FormEditor.jsx` | Step-basierter Editor |
| `assets/.../StepContainer.jsx` | Step-Komponente |
| `assets/.../FieldItem.jsx` | Feld-Element |
| `assets/.../AddFieldModal.jsx` | Feld hinzufügen |
| `assets/.../PublishControls.jsx` | Publish-UI |
| `assets/.../hooks/useFormConfig.js` | Config-Hook |

### Zu ändernde Dateien

| Datei | Änderung |
|-------|----------|
| `src/Database/Schema.php` | Neue Tabelle |
| `src/Core/Activator.php` | Default-Config |
| `templates/single-job_listing.php` | FormRenderService nutzen |
| `assets/.../FormBuilder.jsx` | Neue Tab-Struktur |
| `assets/.../FormPreview.jsx` | Draft-Config nutzen |
| `assets/.../FieldEditor.jsx` | → FieldSidebar.jsx |
| `assets/src/js/frontend.js` | Dynamische Config |
| `templates/fields/*.php` | Alpine.js Bindings |

### Zu entfernende Dateien

| Datei | Grund |
|-------|-------|
| `TemplateManager.jsx` | Feature entfernt |
| `FieldList.jsx` | Ersetzt durch FormEditor |
| `FieldListItem.jsx` | Ersetzt durch FieldItem |
| `useFormTemplates.js` | Feature entfernt |
| `FormTemplateController.php` | Feature entfernt |
| `FormTemplateService.php` | Feature entfernt |
| `FormTemplateRepository.php` | Feature entfernt |
| `FormTemplate.php` (Model) | Feature entfernt |

---

## Zeitplan

| Phase | Dauer | Beschreibung |
|-------|-------|--------------|
| Phase 1 | 1 Tag | Backend: DB, Service, API |
| Phase 2 | 1 Tag | Frontend-Rendering |
| Phase 3 | 2 Tage | Admin-UI Refactoring |
| Phase 4 | 1 Tag | Testing & Bugfixes |
| Phase 5 | 0.5 Tag | Aufräumen & Doku |

**Gesamt: ~5-6 Tage**

---

## Risiken & Mitigationen

| Risiko | Wahrscheinlichkeit | Mitigation |
|--------|-------------------|------------|
| Drag & Drop komplex | Mittel | @dnd-kit ist gut dokumentiert |
| Frontend-Rendering Bugs | Hoch | Schrittweise testen, Fallback |
| Datenverlust bei Migration | Niedrig | Keine echten Daten zu migrieren |
| Performance bei vielen Feldern | Niedrig | Max. 20-30 Felder realistisch |

---

## Nächste Schritte

1. **Review**: Spezifikation & Plan mit Stakeholder besprechen
2. **Start Phase 1**: FormConfigService implementieren
3. **Tägliche Check-ins**: Fortschritt tracken

---

*Dokument-Version: 1.0*
*Erstellt: 2026-01-31*

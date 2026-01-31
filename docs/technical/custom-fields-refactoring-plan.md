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

### 1.1 Neue Tabelle erstellen

**Datei:** `src/Database/Migrations/FormConfigMigration.php`

```php
// Neue Migration für wp_rp_form_config
```

**Tasks:**
- [ ] Migration-Klasse erstellen
- [ ] Schema::addTable() in Schema.php
- [ ] Standard-Config beim Aktivieren einfügen

### 1.2 FormConfigService erstellen

**Datei:** `src/Services/FormConfigService.php`

```php
class FormConfigService {
    public function getDraft(): ?array;
    public function getPublished(): ?array;
    public function saveDraft(array $config): bool;
    public function publish(): bool;
    public function discardDraft(): bool;
    public function hasUnpublishedChanges(): bool;
    public function getDefaultConfig(): array;
}
```

**Tasks:**
- [ ] Service-Klasse erstellen
- [ ] Repository für DB-Zugriff
- [ ] Default-Config definieren
- [ ] Unit-Tests schreiben

### 1.3 FormConfigController erstellen

**Datei:** `src/Api/FormConfigController.php`

**Endpunkte:**
- [ ] GET `/form-builder/config`
- [ ] PUT `/form-builder/config`
- [ ] POST `/form-builder/publish`
- [ ] POST `/form-builder/discard`

**Tasks:**
- [ ] Controller-Klasse erstellen
- [ ] Routes registrieren
- [ ] Permission-Checks (manage_options)

---

## Phase 2: Frontend-Rendering (Tag 2)

### 2.1 FormRenderService implementieren

**Datei:** `src/Services/FormRenderService.php`

**Tasks:**
- [ ] Service-Klasse erstellen
- [ ] `render(int $job_id): string` Methode
- [ ] Step-Rendering
- [ ] Feld-Rendering mit Templates
- [ ] Alpine.js Data-Preparation

### 2.2 Feld-Templates erstellen/anpassen

**Verzeichnis:** `templates/fields/`

**Bestehende Templates prüfen:**
- [ ] `field-text.php` - x-model hinzufügen
- [ ] `field-email.php` - x-model hinzufügen
- [ ] `field-textarea.php` - x-model hinzufügen
- [ ] `field-select.php` - x-model hinzufügen
- [ ] `field-checkbox.php` - x-model hinzufügen
- [ ] `field-file.php` - File-Handling
- [ ] `field-phone.php` - x-model hinzufügen
- [ ] `field-date.php` - x-model hinzufügen

### 2.3 single-job_listing.php anpassen

**Datei:** `templates/single-job_listing.php`

**Änderung:**
```php
// ALT: Hart kodiertes Formular (300+ Zeilen)

// NEU:
$form_service = new FormRenderService();
echo $form_service->render( get_the_ID() );
```

**Tasks:**
- [ ] FormRenderService instanziieren
- [ ] Hart kodierten Formular-Code entfernen
- [ ] Service-Output einbinden

### 2.4 Alpine.js anpassen

**Datei:** `assets/src/js/frontend.js`

**Tasks:**
- [ ] `applicationForm(config)` anpassen für dynamische Config
- [ ] Dynamische Validierung basierend auf Config
- [ ] Step-Navigation für variable Step-Anzahl

---

## Phase 3: Admin-UI Refactoring (Tag 3-4)

### 3.1 Neue Komponenten-Struktur

```
assets/src/js/admin/form-builder/
├── FormBuilder.jsx              # Haupt-Container (ANPASSEN)
├── components/
│   ├── FieldLibrary.jsx         # NEU - Tab "Felder"
│   ├── FormEditor.jsx           # NEU - Tab "Formular-Builder"
│   ├── FormPreview.jsx          # ANPASSEN - Dynamische Vorschau
│   ├── StepContainer.jsx        # NEU - Step-Box
│   ├── FieldItem.jsx            # NEU - Draggable Feld
│   ├── FieldSidebar.jsx         # UMBENENNEN von FieldEditor.jsx
│   ├── AddFieldModal.jsx        # NEU - Feld hinzufügen Dialog
│   ├── PublishControls.jsx      # NEU - Speichern/Veröffentlichen
│   └── StatusIndicator.jsx      # NEU - "Unveröffentlichte Änderungen"
│   ├── FieldList.jsx            # ENTFERNEN
│   ├── FieldListItem.jsx        # ENTFERNEN
│   ├── FieldTypeSelector.jsx    # ANPASSEN → AddFieldModal
│   ├── TemplateManager.jsx      # ENTFERNEN
```

### 3.2 FormBuilder.jsx refactoren

**Tasks:**
- [ ] Tabs ändern: "Felder" | "Formular-Builder" | "Vorschau"
- [ ] State für Draft-Config
- [ ] useFormConfig Hook einbinden
- [ ] Publish-Status anzeigen

### 3.3 FormEditor.jsx erstellen (Hauptarbeit)

**Tasks:**
- [ ] Step-Liste rendern
- [ ] Drag & Drop zwischen Steps (@dnd-kit)
- [ ] "Neuen Step hinzufügen"
- [ ] Step löschen (außer Finale)
- [ ] Feld-Auswahl Sidebar-Integration

### 3.4 StepContainer.jsx erstellen

**Tasks:**
- [ ] Step-Header (Titel, editierbar)
- [ ] Feld-Liste mit Drag & Drop
- [ ] "+ Feld hinzufügen" Button
- [ ] Löschen-Button (wenn deletable)

### 3.5 FieldItem.jsx erstellen

**Tasks:**
- [ ] Drag-Handle
- [ ] Feld-Label + Typ-Icon
- [ ] Pflichtfeld-Badge
- [ ] Sichtbarkeit-Toggle
- [ ] Einstellungen-Button (öffnet Sidebar)
- [ ] Entfernen-Button

### 3.6 FieldLibrary.jsx erstellen

**Tasks:**
- [ ] System-Felder anzeigen
- [ ] Custom-Felder anzeigen (Pro)
- [ ] Neues Feld erstellen (Pro)
- [ ] Drag zum Builder ermöglichen

### 3.7 Hooks anpassen/erstellen

**useFormConfig.js (NEU):**
```javascript
export function useFormConfig() {
    const [draft, setDraft] = useState(null);
    const [published, setPublished] = useState(null);
    const [hasChanges, setHasChanges] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [isPublishing, setIsPublishing] = useState(false);

    // Load config
    // Save draft
    // Publish
    // Discard
}
```

---

## Phase 4: Integration & Testing (Tag 5)

### 4.1 E2E-Flow testen

- [ ] Formular-Builder öffnen
- [ ] Feld verschieben
- [ ] Speichern → Draft
- [ ] Vorschau prüfen
- [ ] Veröffentlichen
- [ ] Frontend prüfen (Job-Seite)

### 4.2 Edge Cases

- [ ] Leerer Step
- [ ] Step löschen mit Feldern
- [ ] Pflichtfeld entfernen
- [ ] Browser-Refresh mit ungespeicherten Änderungen
- [ ] Gleichzeitige Bearbeitung (2 Admins)

### 4.3 Migration bestehender Daten

- [ ] Prüfen ob field_definitions vorhanden
- [ ] Default-Config erstellen wenn keine existiert
- [ ] Templates-Daten ignorieren (nicht migrieren)

---

## Phase 5: Aufräumen (Tag 6)

### 5.1 Alte Dateien entfernen

- [ ] `TemplateManager.jsx` entfernen
- [ ] `FieldList.jsx` entfernen (ersetzt durch FormEditor)
- [ ] `useFormTemplates.js` entfernen
- [ ] `FormTemplateController.php` entfernen
- [ ] `FormTemplateService.php` entfernen
- [ ] `FormTemplateRepository.php` entfernen

### 5.2 Dokumentation

- [ ] `custom-fields-specification.md` → archivieren als `-v1.md`
- [ ] `custom-fields-specification-v2.md` → umbenennen zu `-specification.md`
- [ ] README aktualisieren
- [ ] Inline-Kommentare prüfen

### 5.3 Build & Deploy

- [ ] `npm run build` erfolgreich
- [ ] `composer phpcs` keine Fehler
- [ ] Git-Commit mit aussagekräftiger Message

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

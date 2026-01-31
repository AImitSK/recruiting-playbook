# Refactoring-Plan: Form Builder & Datenfluss

**Erstellt:** 2025-01-31
**Status:** Geplant
**Bezug:**
- [Datenfluss-Analyse](./data-flow-analysis.md)
- [Form Builder Spezifikation](./form-builder-specification.md)

---

## Übersicht

Dieser Plan adressiert die in der Datenfluss-Analyse identifizierten Probleme und implementiert die neue Form Builder Architektur.

### Ziele

1. **Single Source of Truth**: Alle Daten kommen aus dem Form Builder
2. **Dynamische Anzeige**: Frontend + Admin zeigen nur konfigurierte Felder
3. **Free/Pro Trennung**: Klare Feature-Abgrenzung
4. **Datenkonsistenz**: Behebung aller identifizierten Probleme

---

## Phase 1: Grundlagen & Kritische Fixes

**Priorität:** KRITISCH
**Geschätzte Komplexität:** Mittel

### 1.1 Datenbank-Fixes

#### Task 1.1.1: email_hash implementieren
**Problem:** P1 aus Datenfluss-Analyse
**Dateien:**
- `plugin/src/Services/ApplicationService.php`
- `plugin/src/Repositories/CandidateRepository.php`

**Änderungen:**
```php
// CandidateRepository::create() / update()
$data['email_hash'] = hash('sha256', strtolower(trim($data['email'])));
```

**Migration für bestehende Daten:**
```sql
UPDATE rp_candidates
SET email_hash = SHA2(LOWER(TRIM(email)), 256)
WHERE email_hash IS NULL;
```

#### Task 1.1.2: consent_privacy_version speichern
**Problem:** P2 aus Datenfluss-Analyse
**Dateien:**
- `plugin/src/Services/ApplicationService.php`
- WordPress Option: `rp_privacy_policy_version`

**Änderungen:**
```php
// ApplicationService::create()
$application_data['consent_privacy_version'] = get_option('rp_privacy_policy_version', '1.0');
```

---

### 1.2 Form Config Schema Update

#### Task 1.2.1: Schema erweitern
**Dateien:**
- `plugin/src/Services/FormConfigService.php`

**Neue Default-Konfiguration mit v2 Schema:**
- `system_fields` Array pro Step
- `is_removable` Flag pro Feld
- `file_upload` als System-Feld
- `summary` als System-Feld
- `privacy_consent` mit Settings

#### Task 1.2.2: Validierung anpassen
**Dateien:**
- `plugin/src/Services/FormConfigService.php`

**Neue Validierungsregeln:**
```php
const REQUIRED_FIELDS = ['first_name', 'last_name', 'email', 'privacy_consent'];

// Prüfung dass Pflichtfelder vorhanden sind
// Prüfung dass Finale-Step existiert
// Prüfung dass System-Felder nicht entfernt wurden
```

#### Task 1.2.3: Migration v1 → v2
**Dateien:**
- `plugin/src/Services/FormConfigService.php`

**Automatische Migration beim Laden:**
```php
public function getDraft(): array {
    $draft = $this->repository->getDraft();
    if ($draft && ($draft['config_data']['version'] ?? 1) < 2) {
        $draft['config_data'] = $this->migrateConfig($draft['config_data']);
        $this->repository->saveDraft($draft['config_data']);
    }
    return $draft['config_data'];
}
```

---

## Phase 2: Form Builder UI Refactoring

**Priorität:** HOCH
**Geschätzte Komplexität:** Hoch

### 2.1 React-Komponenten

#### Task 2.1.1: Pflichtfeld-Markierung
**Dateien:**
- `plugin/admin/src/components/FormBuilder/FieldCard.jsx`
- `plugin/admin/src/components/FormBuilder/FormEditor.jsx`

**Änderungen:**
```jsx
// FieldCard.jsx
const isRemovable = field.is_removable !== false;

<div className="field-card">
  {!isRemovable && <LockIcon className="lock-icon" />}
  <span>{field.label}</span>
  <SettingsButton onClick={openSettings} />
  {isRemovable && <DeleteButton onClick={onRemove} />}
</div>
```

#### Task 2.1.2: System-Felder rendern
**Dateien:**
- `plugin/admin/src/components/FormBuilder/StepEditor.jsx`
- Neue Komponente: `SystemFieldCard.jsx`

**Änderungen:**
- System-Felder aus `step.system_fields` rendern
- Eigene Einstellungs-Panels für file_upload, summary, privacy_consent
- Nicht drag-droppable, aber konfigurierbar

#### Task 2.1.3: Einstellungs-Panels für System-Felder
**Neue Dateien:**
- `plugin/admin/src/components/FormBuilder/Settings/FileUploadSettings.jsx`
- `plugin/admin/src/components/FormBuilder/Settings/SummarySettings.jsx`
- `plugin/admin/src/components/FormBuilder/Settings/PrivacyConsentSettings.jsx`

**FileUploadSettings:**
- Erlaubte Dateitypen (Checkboxen)
- Max. Dateigröße (Number Input)
- Max. Anzahl (Number Input)
- Hilfetext (Textarea)

**SummarySettings:**
- Titel (Text Input)
- Layout (Radio: 1-spaltig / 2-spaltig)
- Zusatztext (Textarea)
- Nur ausgefüllte Felder (Checkbox)

**PrivacyConsentSettings:**
- Checkbox-Text (Textarea mit Platzhalter {datenschutz_link})
- Link-Text (Text Input)
- Datenschutz-URL (Text Input oder Page Select)

#### Task 2.1.4: Free Version Overlay
**Dateien:**
- `plugin/admin/src/components/FormBuilder/FormBuilder.jsx`
- Neue Komponente: `ProFeatureOverlay.jsx`

**Änderungen:**
```jsx
// FormBuilder.jsx
const { canUseFormBuilder } = useFeatures();

return (
  <div className="form-builder">
    {!canUseFormBuilder && <ProFeatureOverlay />}
    <div className={!canUseFormBuilder ? 'disabled' : ''}>
      {/* Existing content */}
    </div>
  </div>
);
```

---

### 2.2 Hook-Anpassungen

#### Task 2.2.1: useFormConfig erweitern
**Dateien:**
- `plugin/admin/src/hooks/useFormConfig.js`

**Neue Funktionen:**
```javascript
// System-Feld Settings aktualisieren
const updateSystemFieldSettings = (stepId, fieldKey, settings) => {
  setDraft(prev => ({
    ...prev,
    steps: prev.steps.map(step =>
      step.id === stepId
        ? {
            ...step,
            system_fields: step.system_fields.map(sf =>
              sf.field_key === fieldKey
                ? { ...sf, settings: { ...sf.settings, ...settings } }
                : sf
            )
          }
        : step
    )
  }));
};

// Prüfen ob Feld entfernbar ist
const isFieldRemovable = (fieldKey) => {
  const requiredFields = ['first_name', 'last_name', 'email', 'privacy_consent'];
  return !requiredFields.includes(fieldKey);
};
```

---

## Phase 3: Frontend Formular Refactoring

**Priorität:** HOCH
**Geschätzte Komplexität:** Hoch

### 3.1 FormRenderService

#### Task 3.1.1: Dynamisches Rendering aus Config
**Dateien:**
- `plugin/src/Services/FormRenderService.php`

**Änderungen:**
- Keine hardcodierten Felder mehr
- `formData` aus Config generieren
- System-Felder (file_upload, summary) rendern
- Settings aus Config lesen

```php
// prepareAlpineData() - VORHER
'formData' => [
    'job_id' => $job_id,
    'first_name' => '',
    'last_name' => '',
    // ... hardcodiert
]

// prepareAlpineData() - NACHHER
'formData' => $this->buildFormDataFromConfig($config, $job_id)

private function buildFormDataFromConfig(array $config, int $job_id): array {
    $form_data = ['job_id' => $job_id];

    foreach ($config['steps'] as $step) {
        foreach ($step['fields'] ?? [] as $field) {
            if ($field['is_visible']) {
                $form_data[$field['field_key']] = $this->getDefaultValue($field);
            }
        }
    }

    return $form_data;
}
```

#### Task 3.1.2: System-Feld-Templates
**Neue Dateien:**
- `plugin/templates/fields/field-file-upload.php` (erweitert von field-file.php)
- `plugin/templates/fields/field-summary.php`
- `plugin/templates/fields/field-privacy-consent.php`

**field-summary.php:**
```php
<?php
$settings = $field['settings'] ?? [];
$layout = $settings['layout'] ?? 'two-column';
$title = $settings['title'] ?? __('Ihre Angaben im Überblick', 'recruiting-playbook');
$additional_text = $settings['additional_text'] ?? '';
?>

<div class="rp-summary-field" data-layout="<?php echo esc_attr($layout); ?>">
    <h3><?php echo esc_html($title); ?></h3>

    <?php if ($additional_text): ?>
        <p class="rp-summary-intro"><?php echo esc_html($additional_text); ?></p>
    <?php endif; ?>

    <div class="rp-summary-grid rp-summary-<?php echo esc_attr($layout); ?>"
         x-html="renderSummary()">
    </div>
</div>
```

**field-privacy-consent.php:**
```php
<?php
$settings = $field['settings'] ?? [];
$checkbox_text = $settings['checkbox_text'] ??
    __('Ich habe die {datenschutz_link} gelesen und stimme zu.', 'recruiting-playbook');
$link_text = $settings['link_text'] ?? __('Datenschutzerklärung', 'recruiting-playbook');
$privacy_url = $settings['privacy_url'] ?? get_privacy_policy_url();

// Platzhalter ersetzen
$link = sprintf('<a href="%s" target="_blank">%s</a>',
    esc_url($privacy_url),
    esc_html($link_text)
);
$label = str_replace('{datenschutz_link}', $link, $checkbox_text);
?>

<div class="rp-privacy-consent-field">
    <label class="rp-checkbox-label">
        <input type="checkbox"
               x-model="formData.privacy_consent"
               required>
        <span><?php echo wp_kses_post($label); ?></span>
    </label>
</div>
```

### 3.2 File Upload Fixes

#### Task 3.2.1: File-Dropzone Scope Fix
**Problem:** P3 aus Datenfluss-Analyse
**Dateien:**
- `plugin/templates/fields/field-file.php`

**Änderungen:**
```javascript
// VORHER: this.files (undefined im nested scope)
// NACHHER: Explizite Parent-Referenz via Alpine.js $data

x-data="{
  _files: [],
  _fieldKey: '<?php echo esc_js($field_key); ?>',

  init() {
    // Sichere Referenz auf Parent files
    this.$watch('$data.files', () => this.loadFromParent());
    this.loadFromParent();
  },

  loadFromParent() {
    const parentFiles = this.$data.files || { resume: null, documents: [] };
    // ...
  },

  syncToParent() {
    const parentFiles = this.$data.files || { resume: null, documents: [] };
    // Sync logic
  }
}"
```

#### Task 3.2.2: Separate File-Keys statt documents[]
**Problem:** P4 aus Datenfluss-Analyse
**Dateien:**
- `plugin/templates/fields/field-file.php`
- `plugin/assets/src/js/application-form.js`
- `plugin/src/Api/ApplicationController.php`
- `plugin/src/Services/DocumentService.php`

**Neue Struktur:**
```javascript
// VORHER
files: {
  resume: File,
  documents: [File, File]  // Vermischt alle Uploads
}

// NACHHER
files: {
  resume: [File],          // Immer Array
  documents: [File, File], // Optionale zusätzliche Dokumente
  custom_file_1: [File]    // Custom File-Felder mit eigenem Key
}
```

#### Task 3.2.3: File-Validierung hinzufügen
**Problem:** P5 aus Datenfluss-Analyse
**Dateien:**
- `plugin/assets/src/js/application-form.js`

**Änderungen:**
```javascript
validateField(fieldKey, rules) {
  // Bestehende Validierung...

  // NEU: File-Validierung
  if (rules.type === 'file') {
    const files = this.files[fieldKey] || [];

    if (rules.required && files.length === 0) {
      this.errors[fieldKey] = this.i18n.file_required || 'Bitte laden Sie eine Datei hoch.';
      return false;
    }

    // Optionale Min/Max Validierung
    if (rules.minFiles && files.length < rules.minFiles) {
      this.errors[fieldKey] = `Mindestens ${rules.minFiles} Datei(en) erforderlich.`;
      return false;
    }
  }

  return true;
}
```

---

## Phase 4: Admin-Anzeige Refactoring

**Priorität:** MITTEL
**Geschätzte Komplexität:** Mittel

### 4.1 Dynamische Bewerber-Details

#### Task 4.1.1: ApplicantDetail dynamisch machen
**Dateien:**
- `plugin/admin/src/components/Applicant/ApplicantDetail.jsx`
- Neue Datei: `plugin/admin/src/components/Applicant/DynamicFieldRenderer.jsx`

**Konzept:**
```jsx
// DynamicFieldRenderer.jsx
const DynamicFieldRenderer = ({ fields, data }) => {
  return (
    <div className="dynamic-fields">
      {fields.map(field => (
        <FieldDisplay
          key={field.field_key}
          field={field}
          value={data[field.field_key] || data.candidate?.[field.field_key]}
        />
      ))}
    </div>
  );
};

// FieldDisplay - rendert je nach Typ
const FieldDisplay = ({ field, value }) => {
  switch (field.field_type) {
    case 'email':
      return <EmailField label={field.label} value={value} />;
    case 'tel':
      return <PhoneField label={field.label} value={value} />;
    case 'file':
      return <FileField label={field.label} files={value} />;
    // ...
    default:
      return <TextField label={field.label} value={value} />;
  }
};
```

#### Task 4.1.2: API für aktive Felder
**Dateien:**
- `plugin/src/Api/FormConfigController.php`
- Neuer Endpunkt: `GET /form-builder/active-fields`

**Response:**
```json
{
  "fields": [
    {
      "field_key": "first_name",
      "field_type": "text",
      "label": "Vorname",
      "is_system": true
    },
    {
      "field_key": "custom_experience",
      "field_type": "select",
      "label": "Berufserfahrung",
      "is_system": false,
      "options": ["0-2 Jahre", "3-5 Jahre", "5+ Jahre"]
    }
  ]
}
```

### 4.2 Kanban-Verbesserungen

#### Task 4.2.1: Fehlende Daten in Kanban
**Problem:** P7 aus Datenfluss-Analyse
**Dateien:**
- `plugin/src/Services/ApplicationService.php`
- `plugin/src/Repositories/ApplicationRepository.php`

**Änderungen:**
```php
// listForKanban() - zusätzliche Daten
public function listForKanban(array $args): array {
    // Bestehende Query...

    // NEU: notes_count, rating, talent_pool
    $sql = "
        SELECT
            a.*,
            c.first_name, c.last_name, c.email,
            COUNT(DISTINCT d.id) as documents_count,
            COUNT(DISTINCT n.id) as notes_count,
            AVG(r.rating) as average_rating,
            CASE WHEN tp.id IS NOT NULL THEN 1 ELSE 0 END as in_talent_pool
        FROM {$this->table} a
        LEFT JOIN {$candidates} c ON a.candidate_id = c.id
        LEFT JOIN {$documents} d ON d.application_id = a.id
        LEFT JOIN {$notes} n ON n.application_id = a.id
        LEFT JOIN {$ratings} r ON r.application_id = a.id
        LEFT JOIN {$talent_pool} tp ON tp.candidate_id = c.id
        WHERE a.deleted_at IS NULL
        GROUP BY a.id
    ";
}
```

---

## Phase 5: Email-Platzhalter System

**Priorität:** MITTEL
**Geschätzte Komplexität:** Niedrig

### 5.1 PlaceholderService

#### Task 5.1.1: Zentrale Platzhalter-Verwaltung
**Neue Datei:**
- `plugin/src/Services/PlaceholderService.php`

```php
class PlaceholderService {

    private const PLACEHOLDERS = [
        // Kandidat (garantiert)
        '{vorname}' => ['source' => 'candidate', 'field' => 'first_name'],
        '{nachname}' => ['source' => 'candidate', 'field' => 'last_name'],
        '{name}' => ['source' => 'computed', 'method' => 'getFullName'],
        '{email}' => ['source' => 'candidate', 'field' => 'email'],
        '{anrede}' => ['source' => 'candidate', 'field' => 'salutation'],
        '{anrede_formal}' => ['source' => 'computed', 'method' => 'getFormalSalutation'],

        // Bewerbung
        '{bewerbung_id}' => ['source' => 'application', 'field' => 'id'],
        '{bewerbung_datum}' => ['source' => 'application', 'field' => 'created_at', 'format' => 'date'],
        '{bewerbung_status}' => ['source' => 'application', 'field' => 'status', 'translate' => true],

        // Stelle
        '{stelle}' => ['source' => 'job', 'field' => 'title'],
        '{stelle_ort}' => ['source' => 'job_meta', 'field' => 'location'],
        '{stelle_typ}' => ['source' => 'job_meta', 'field' => 'employment_type'],
        '{stelle_url}' => ['source' => 'computed', 'method' => 'getJobUrl'],

        // Firma
        '{firma}' => ['source' => 'option', 'option' => 'rp_company_name'],
        '{firma_adresse}' => ['source' => 'option', 'option' => 'rp_company_address'],
        '{firma_website}' => ['source' => 'computed', 'method' => 'getSiteUrl'],
    ];

    public function replace(string $template, array $context): string {
        $replacements = [];

        foreach (self::PLACEHOLDERS as $placeholder => $config) {
            $value = $this->resolveValue($config, $context);
            $replacements[$placeholder] = $value;
        }

        return strtr($template, $replacements);
    }

    public function getAvailablePlaceholders(): array {
        return array_keys(self::PLACEHOLDERS);
    }
}
```

#### Task 5.1.2: Integration in EmailService
**Dateien:**
- `plugin/src/Services/EmailService.php`

**Änderungen:**
```php
public function send(string $template_key, array $context): bool {
    $template = $this->getTemplate($template_key);

    $placeholder_service = new PlaceholderService();

    $subject = $placeholder_service->replace($template['subject'], $context);
    $body = $placeholder_service->replace($template['body'], $context);

    return wp_mail($context['to'], $subject, $body);
}
```

---

## Phase 6: Tests & Qualitätssicherung

**Priorität:** NIEDRIG (aber wichtig)
**Geschätzte Komplexität:** Mittel

### 6.1 Unit Tests

#### Task 6.1.1: FormConfigService Tests
- Validierung der Pflichtfelder
- Migration v1 → v2
- Default-Konfiguration

#### Task 6.1.2: PlaceholderService Tests
- Alle Platzhalter-Ersetzungen
- Edge Cases (fehlende Daten)

### 6.2 Integration Tests

#### Task 6.2.1: Form Builder → Frontend → Admin Flow
- Formular im Builder erstellen
- Im Frontend ausfüllen
- In Admin-Details prüfen

---

## Zusammenfassung: Aufgaben-Matrix

| Phase | Task | Priorität | Komplexität | Abhängigkeit |
|-------|------|-----------|-------------|--------------|
| 1 | 1.1.1 email_hash | KRITISCH | Niedrig | - |
| 1 | 1.1.2 consent_version | KRITISCH | Niedrig | - |
| 1 | 1.2.1 Schema v2 | KRITISCH | Mittel | - |
| 1 | 1.2.2 Validierung | KRITISCH | Niedrig | 1.2.1 |
| 1 | 1.2.3 Migration | KRITISCH | Mittel | 1.2.1 |
| 2 | 2.1.1 Pflichtfeld-UI | HOCH | Niedrig | 1.2.1 |
| 2 | 2.1.2 System-Felder | HOCH | Mittel | 1.2.1 |
| 2 | 2.1.3 Settings-Panels | HOCH | Mittel | 2.1.2 |
| 2 | 2.1.4 Free Overlay | HOCH | Niedrig | - |
| 2 | 2.2.1 Hook Update | HOCH | Niedrig | 1.2.1 |
| 3 | 3.1.1 Dynamisches Render | HOCH | Hoch | 1.2.1 |
| 3 | 3.1.2 System-Templates | HOCH | Mittel | 3.1.1 |
| 3 | 3.2.1 File Scope Fix | HOCH | Mittel | - |
| 3 | 3.2.2 Separate File-Keys | HOCH | Hoch | 3.2.1 |
| 3 | 3.2.3 File-Validierung | MITTEL | Niedrig | 3.2.1 |
| 4 | 4.1.1 Dynamische Details | MITTEL | Mittel | 1.2.1 |
| 4 | 4.1.2 Active Fields API | MITTEL | Niedrig | 1.2.1 |
| 4 | 4.2.1 Kanban Daten | MITTEL | Mittel | - |
| 5 | 5.1.1 PlaceholderService | MITTEL | Niedrig | - |
| 5 | 5.1.2 Email Integration | MITTEL | Niedrig | 5.1.1 |
| 6 | Tests | NIEDRIG | Mittel | Alle |

---

## Reihenfolge der Implementierung

### Sprint 1: Kritische Grundlagen
1. Task 1.1.1: email_hash
2. Task 1.1.2: consent_version
3. Task 1.2.1: Schema v2
4. Task 1.2.2: Validierung
5. Task 1.2.3: Migration

### Sprint 2: Form Builder UI
1. Task 2.1.1: Pflichtfeld-UI
2. Task 2.1.2: System-Felder
3. Task 2.1.3: Settings-Panels
4. Task 2.1.4: Free Overlay
5. Task 2.2.1: Hook Update

### Sprint 3: Frontend
1. Task 3.2.1: File Scope Fix
2. Task 3.2.2: Separate File-Keys
3. Task 3.1.1: Dynamisches Render
4. Task 3.1.2: System-Templates
5. Task 3.2.3: File-Validierung

### Sprint 4: Admin & Platzhalter
1. Task 4.1.1: Dynamische Details
2. Task 4.1.2: Active Fields API
3. Task 4.2.1: Kanban Daten
4. Task 5.1.1: PlaceholderService
5. Task 5.1.2: Email Integration

### Sprint 5: QA
1. Tests & Bug Fixes

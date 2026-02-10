# Datenfluss-Analyse: Recruiting Playbook Plugin

**Erstellt:** 2025-01-31
**Status:** Umfassende Analyse aller Datenflüsse
**Zweck:** Identifikation von Datenkonsistenzproblemen

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Form Builder Datenfluss](#2-form-builder-datenfluss)
3. [Frontend Formular Datenfluss](#3-frontend-formular-datenfluss)
4. [API und Speicherung](#4-api-und-speicherung)
5. [Admin-Anzeige](#5-admin-anzeige)
6. [Identifizierte Probleme](#6-identifizierte-probleme)
7. [Empfohlene Maßnahmen](#7-empfohlene-maßnahmen)

---

## 1. Übersicht

### Datenfluss-Diagramm (Gesamt)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            FORM BUILDER (Admin)                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐                │
│  │  Fields Tab  │────▶│  Formular    │────▶│  Vorschau    │                │
│  │              │     │  Tab         │     │  Tab         │                │
│  └──────┬───────┘     └──────┬───────┘     └──────────────┘                │
│         │                    │                                              │
│         ▼                    ▼                                              │
│  ┌──────────────┐     ┌──────────────┐                                     │
│  │ rp_field_    │     │ rp_form_     │                                     │
│  │ definitions  │     │ config       │                                     │
│  └──────────────┘     └──────────────┘                                     │
│         │                    │                                              │
└─────────┼────────────────────┼──────────────────────────────────────────────┘
          │                    │
          ▼                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         FRONTEND FORMULAR                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  FormRenderService.php                                                       │
│         │                                                                    │
│         ▼                                                                    │
│  ┌──────────────────────────────────────────────────────────────────┐      │
│  │ Alpine.js applicationForm                                         │      │
│  │  ├─ formData: {first_name, last_name, email, phone, ...}        │      │
│  │  ├─ files: {resume, documents[]}                                 │      │
│  │  └─ custom_fields: {...}                                         │      │
│  └──────────────────────────────────────────────────────────────────┘      │
│         │                                                                    │
│         ▼ POST /recruiting/v1/applications                                  │
└─────────────────────────────────────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         API & SPEICHERUNG                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ApplicationController.create_item()                                         │
│         │                                                                    │
│         ▼                                                                    │
│  ApplicationService.create()                                                 │
│         │                                                                    │
│         ├──▶ CandidateRepository ──▶ rp_candidates                         │
│         │                                                                    │
│         ├──▶ ApplicationRepository ──▶ rp_applications                      │
│         │                                                                    │
│         ├──▶ DocumentService ──▶ rp_documents + Filesystem                  │
│         │                                                                    │
│         └──▶ CustomFieldsService ──▶ rp_applications.custom_fields          │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ADMIN ANZEIGE                                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │ Bewerbungen  │  │ Detail-      │  │ Kanban       │  │ Talent       │   │
│  │ Liste        │  │ Seite        │  │ Board        │  │ Pool         │   │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │                           Reporting                                   │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Form Builder Datenfluss

### 2.1 Datenbank-Schema

#### rp_field_definitions
```sql
CREATE TABLE rp_field_definitions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    template_id BIGINT NULL,           -- NULL = global
    job_id BIGINT NULL,                -- NULL = global
    field_key VARCHAR(100) NOT NULL,   -- z.B. "first_name", "custom_123"
    field_type VARCHAR(50) NOT NULL,   -- text, email, select, file, etc.
    label VARCHAR(255),
    placeholder VARCHAR(255),
    description TEXT,
    options LONGTEXT,                  -- JSON für Select/Radio/Checkbox
    validation LONGTEXT,               -- JSON: {required, minLength, maxLength, ...}
    conditional LONGTEXT,              -- JSON: Bedingte Logik
    settings LONGTEXT,                 -- JSON: Zusätzliche Einstellungen
    position INT DEFAULT 0,
    is_required TINYINT(1) DEFAULT 0,
    is_system TINYINT(1) DEFAULT 0,    -- System-Felder nicht löschbar
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME NULL,          -- Soft Delete

    UNIQUE KEY field_key_template (field_key, template_id),
    UNIQUE KEY field_key_job (field_key, job_id)
);
```

**System-Felder (is_system = 1):**
- first_name, last_name, email, phone
- resume, message, privacy_consent

#### rp_form_config
```sql
CREATE TABLE rp_form_config (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    config_type VARCHAR(20) NOT NULL,  -- 'draft' oder 'published'
    config_data LONGTEXT NOT NULL,     -- JSON: Step-Konfiguration
    version INT DEFAULT 1,
    created_by BIGINT NULL,
    created_at DATETIME,
    updated_at DATETIME,

    UNIQUE KEY config_type (config_type)  -- Max 1 Draft + 1 Published
);
```

**config_data JSON-Struktur:**
```json
{
  "version": 1,
  "settings": {
    "showStepIndicator": true,
    "showStepTitles": true,
    "animateSteps": true
  },
  "steps": [
    {
      "id": "step_personal",
      "title": "Persönliche Daten",
      "position": 1,
      "deletable": false,
      "is_finale": false,
      "fields": [
        {
          "field_key": "first_name",
          "is_visible": true,
          "is_required": true
        }
      ]
    }
  ]
}
```

### 2.2 Default-Konfiguration

Die Standard-Konfiguration wird in `FormConfigService::getDefaultConfig()` definiert:

**Step 1: Persönliche Daten**
- first_name (required)
- last_name (required)
- email (required)
- phone (optional)

**Step 2: Dokumente**
- message (optional)
- resume (required)

**Step 3: Abschluss (Finale)**
- privacy_consent (required)

### 2.3 Draft/Published Workflow

```
Benutzer-Aktion         │ API-Endpunkt                  │ Repository-Methode
───────────────────────┼──────────────────────────────┼────────────────────────
Formular laden          │ GET /form-builder/config      │ getDraft()
Auto-Speichern (1.5s)   │ PUT /form-builder/config      │ saveDraft()
Veröffentlichen         │ POST /form-builder/publish    │ publish()
Änderungen verwerfen    │ POST /form-builder/discard    │ discardDraft()
```

### 2.4 Feld-Lade-Flow

```
FormBuilder.jsx (React)
  │
  ├── useFormConfig Hook
  │     └── GET /form-builder/config
  │           ├── FormConfigService.getDraft()
  │           ├── FormConfigService.getPublishedVersion()
  │           ├── FormConfigService.hasUnpublishedChanges()
  │           └── FormConfigService.getAvailableFields()  ⬅️ WAR FEHLERHAFT
  │                 └── FieldDefinitionRepository.findAll()
  │
  └── useFieldDefinitions Hook
        └── GET /fields
              └── FieldDefinitionController.get_items()
```

**⚠️ BEHOBEN:** `getAvailableFields()` verwendete `findSystemFields()` statt `findAll()`, wodurch Custom Fields nicht geladen wurden.

---

## 3. Frontend Formular Datenfluss

### 3.1 Rendering-Flow

```
FormRenderService::render($job_id)
  │
  ├── getPublished() ──▶ rp_form_config WHERE config_type='published'
  │
  ├── prepareAlpineData($config) ──▶ window.rpFormConfig = {...}
  │     │
  │     ├── formData: Initial-Werte für alle sichtbaren Felder
  │     ├── validation: Regeln pro Feld
  │     └── i18n: Übersetzungen
  │
  └── Render Steps via Templates
        │
        ├── field-text.php
        ├── field-email.php
        ├── field-textarea.php
        ├── field-select.php
        ├── field-checkbox.php
        ├── field-radio.php
        └── field-file.php  ⬅️ KOMPLEX
```

### 3.2 Alpine.js Datenstruktur

```javascript
// applicationForm Component
{
  step: 1,                    // Aktueller Step
  totalSteps: 3,              // Aus config.steps
  loading: false,
  submitted: false,
  error: null,

  formData: {
    job_id: 123,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    message: '',
    privacy_consent: false,
    // ... custom fields
  },

  files: {
    resume: null,             // Single File
    documents: []             // Array of Files
  },

  validationRules: {
    first_name: { required: true, minLength: 2 },
    email: { required: true, email: true },
    // ...
  },

  errors: {}                  // Validierungsfehler pro Feld
}
```

### 3.3 File-Upload-Handling

**field-file.php** verwendet einen verschachtelten Alpine.js Scope:

```html
<div x-data="{
  _files: [],           // Lokales File-Array
  _dragging: false,
  _error: null,

  syncToParent() {
    // ⚠️ PROBLEM: Resume vs. Documents Trennung
    if (this._fieldKey === 'resume') {
      this.files.resume = this._files[0] || null;
      this.files.documents = this._files.slice(1);
    } else {
      this.files.documents = this._files;
    }
  }
}">
```

### 3.4 Submission-Payload

```
POST /recruiting/v1/applications
Content-Type: multipart/form-data

FormData:
├── job_id: "123"
├── first_name: "Max"
├── last_name: "Mustermann"
├── email: "max@example.com"
├── phone: "+49..."
├── message: "..."
├── privacy_consent: "true"
├── _hp_field: ""              (Honeypot)
├── _form_timestamp: 1234567890
├── resume: [File]
├── documents[]: [File]
├── documents[]: [File]
└── custom_field_xyz: "..."
```

---

## 4. API und Speicherung

### 4.1 ApplicationController.create_item()

```php
// 1. Spam-Schutz prüfen
SpamProtection::check($_POST)

// 2. Dateien aus $_FILES extrahieren
$files = [
  'resume' => $_FILES['resume'],
  'documents' => $_FILES['documents']
]

// 3. ApplicationService aufrufen
ApplicationService::create([
  'job_id' => $request['job_id'],
  'first_name' => $request['first_name'],
  // ...
  'files' => $files
])
```

### 4.2 ApplicationService.create()

```
ApplicationService::create($data)
  │
  ├── 1. getOrCreateCandidate($data)
  │     │
  │     ├── SELECT * FROM rp_candidates WHERE email = ?
  │     │
  │     ├── Falls EXISTS: UPDATE mit neuen Daten
  │     │   ⚠️ FEHLT: email_hash wird nicht gesetzt!
  │     │
  │     └── Falls NOT EXISTS: INSERT neuer Kandidat
  │
  ├── 2. INSERT INTO rp_applications
  │     │
  │     ├── job_id, candidate_id
  │     ├── status = 'new'
  │     ├── cover_letter
  │     ├── consent_privacy, consent_privacy_at, consent_ip
  │     │   ⚠️ FEHLT: consent_privacy_version
  │     └── created_at, updated_at
  │
  ├── 3. DocumentService::processUploads()
  │     │
  │     ├── Für jede Datei:
  │     │   ├── wp_handle_upload()
  │     │   ├── Speichern in /uploads/recruiting-playbook/applications/{id}/
  │     │   └── INSERT INTO rp_documents
  │     │       ⚠️ POTENTIELL: Spalten-Namen-Mismatch
  │     │
  │     └── Return: document_ids
  │
  ├── 4. CustomFieldsService::processCustomFields() [Pro]
  │     │
  │     ├── Validierung der Custom Fields
  │     ├── File-Uploads für Custom File-Felder
  │     └── UPDATE rp_applications SET custom_fields = JSON
  │
  ├── 5. logActivity('application_received')
  │
  └── 6. EmailService::send()
        ├── sendApplicationReceived() → Admin
        └── sendApplicantConfirmation() → Bewerber
```

### 4.3 Datenbank-Tabellen

#### rp_applications
```sql
CREATE TABLE rp_applications (
    id BIGINT PRIMARY KEY,
    job_id BIGINT NOT NULL,
    candidate_id BIGINT NOT NULL,
    status VARCHAR(20) DEFAULT 'new',
    cover_letter LONGTEXT,
    source VARCHAR(50) DEFAULT 'website',
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    consent_privacy TINYINT(1),
    consent_privacy_at DATETIME,
    consent_ip VARCHAR(45),
    custom_fields JSON,               -- Pro-Feature
    kanban_position INT,              -- Für Kanban-Sortierung
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME NULL,         -- Soft Delete

    FOREIGN KEY (candidate_id) REFERENCES rp_candidates(id)
);
```

#### rp_candidates
```sql
CREATE TABLE rp_candidates (
    id BIGINT PRIMARY KEY,
    email VARCHAR(255) UNIQUE,
    email_hash VARCHAR(64) UNIQUE,    -- ⚠️ WIRD NICHT GESETZT
    salutation VARCHAR(20),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(50),
    created_at DATETIME,
    updated_at DATETIME,
    deleted_at DATETIME NULL
);
```

#### rp_documents
```sql
CREATE TABLE rp_documents (
    id BIGINT PRIMARY KEY,
    application_id BIGINT NOT NULL,
    candidate_id BIGINT,
    file_name VARCHAR(255),           -- stored_filename
    original_name VARCHAR(255),       -- original_filename
    file_path VARCHAR(500),           -- storage_path
    file_type VARCHAR(100),           -- MIME-Type
    file_size BIGINT,
    file_hash VARCHAR(64),            -- MD5
    document_type VARCHAR(30),        -- resume, cover_letter, other
    created_at DATETIME,

    FOREIGN KEY (application_id) REFERENCES rp_applications(id) ON DELETE CASCADE
);
```

---

## 5. Admin-Anzeige

### 5.1 Bewerbungen-Liste

**URL:** `/wp-admin/admin.php?page=rp-applications`

**API:** `GET /recruiting/v1/applications`

**Angezeigte Spalten:**
| Spalte | Quelle | Tabelle |
|--------|--------|---------|
| Bewerber | first_name + last_name | rp_candidates |
| Stelle | job_title | wp_posts |
| Status | status | rp_applications |
| Dokumente | COUNT(documents) | rp_documents |
| Eingegangen | created_at | rp_applications |

**Nicht angezeigt:**
- email, phone, salutation (geladen, aber nicht in UI)
- cover_letter, custom_fields (nicht geladen)
- consent_* (GDPR-intern)

### 5.2 Detail-Seite

**URL:** `/wp-admin/admin.php?page=rp-application-detail&id={id}`

**API:** `GET /recruiting/v1/applications/{id}`

**Tabs:**
1. **Details** - Kandidaten-Daten, Anschreiben, Custom Fields, Bewertung
2. **Dokumente** - Datei-Liste mit Vorschau/Download
3. **Notizen** - Activity Log für Notizen
4. **Verlauf** - Timeline aller Aktivitäten
5. **E-Mail** (Pro) - Direktes E-Mail-Senden

**Angezeigt:**
- Alle Kandidaten-Felder (name, email, phone, salutation)
- cover_letter (HTML-gerendert)
- custom_fields (Pro-Feature)
- Dokumente mit Download-URLs
- Activity Timeline

### 5.3 Kanban Board

**URL:** `/wp-admin/admin.php?page=rp-kanban`

**API:** `GET /recruiting/v1/applications?context=kanban`

**Kanban-Karten zeigen:**
| Feld | Status |
|------|--------|
| Avatar (Initialen) | ✓ Angezeigt |
| Name | ✓ Angezeigt |
| Email | ✓ Angezeigt |
| Stelle | ✓ Angezeigt |
| Eingegangen | ✓ Angezeigt |
| documents_count | ✓ Angezeigt |
| notes_count | ⚠️ FEHLT |
| average_rating | ⚠️ FEHLT |
| in_talent_pool | ⚠️ FEHLT |

### 5.4 Talent Pool

**URL:** `/wp-admin/admin.php?page=rp-talent-pool`

**Felder:**
- Kandidaten-Name, Email
- reason (Begründung)
- tags (Komma-getrennt)
- created_at, expires_at
- Expiry-Warnings

### 5.5 Reporting

**URL:** `/wp-admin/admin.php?page=rp-reporting`

**Tabs:**
1. **Übersicht** - Stats Cards, Trend-Chart, Top-Stellen
2. **Trends** (Pro) - Zeitverlauf-Analyse
3. **Stellen** - Job-Performance
4. **Conversion** (Pro) - Funnel-Analyse

---

## 6. Identifizierte Probleme

### 6.1 KRITISCH

#### P1: email_hash wird nicht gesetzt
**Ort:** `ApplicationService::getOrCreateCandidate()`
**Problem:** Schema erwartet `email_hash` (SHA256), wird aber nicht berechnet
**Auswirkung:** UNIQUE Constraint auf email_hash könnte Probleme verursachen
**Fix:**
```php
$candidate_data['email_hash'] = hash('sha256', strtolower(trim($email)));
```

#### P2: consent_privacy_version fehlt
**Ort:** `ApplicationService::create()`
**Problem:** DSGVO erfordert Nachverfolgung welcher Datenschutztext akzeptiert wurde
**Auswirkung:** Rechtliches Risiko
**Fix:**
```php
'consent_privacy_version' => get_option('rp_privacy_policy_version', '1.0')
```

#### P3: File-Dropzone Scope-Problem
**Ort:** `field-file.php`
**Problem:** Verschachtelter Alpine.js Scope hat keine garantierte Verbindung zum Parent
**Auswirkung:** Dateien könnten nicht zum Parent-Form synced werden
**Fix:** Explizite Parent-Referenz oder Restructuring

#### P4: Resume vs. Documents Vermischung
**Ort:** `field-file.php::syncToParent()`
**Problem:** Bei mehreren File-Feldern werden alle in `files.documents` gemischt
**Auswirkung:** Falsche Datei-Zuordnung
**Fix:** Separate `files[field_key]` statt gemeinsames `documents[]`

### 6.2 HOCH

#### P5: File-Validierung fehlt
**Ort:** `application-form.js::validateCurrentStep()`
**Problem:** Datei-Felder werden nicht validiert (required-Check fehlt)
**Auswirkung:** Leere Pflicht-Dateien werden akzeptiert
**Fix:** File-Validierung in `validateField()` hinzufügen

#### P6: Feld-Referenz ohne Fremdschlüssel
**Ort:** `rp_form_config.config_data`
**Problem:** field_key in Config referenziert möglicherweise gelöschtes Feld
**Auswirkung:** Formular-Rendering-Fehler
**Fix:** Validierung bei Config-Save + Cleanup bei Feld-Löschung

#### P7: Kanban fehlt wichtige Daten
**Ort:** `ApplicationService::listForKanban()`
**Problem:** notes_count, average_rating, in_talent_pool fehlen
**Auswirkung:** Unvollständige Kanban-Karten
**Fix:** Zusätzliche JOINs oder Sub-Queries

#### P8: Hidden Fields werden submitted
**Ort:** `application-form.js::submit()`
**Problem:** Felder mit Conditional Logic werden immer gesendet
**Auswirkung:** Inkonsistente Daten zwischen Frontend und Backend
**Fix:** Visibility-Check vor Submit (wie in rpCustomFieldsForm)

### 6.3 MITTEL

#### P9: Cover Letter ohne Längenbeschränkung
**Ort:** `ApplicationController::create_item()`
**Problem:** Keine maximale Länge validiert
**Auswirkung:** Potentielles DoS via riesige Eingaben
**Fix:** `validate_callback` mit Längenbeschränkung

#### P10: Phone ohne Format-Validierung
**Ort:** `ApplicationController::create_item()`
**Problem:** Jeder Text wird als Telefonnummer akzeptiert
**Auswirkung:** Ungültige Daten in DB
**Fix:** Phone-Format-Validierung

#### P11: File Error Propagation
**Ort:** `field-file.php`
**Problem:** Lokaler `_error` wird nicht zu Parent `errors` propagiert
**Auswirkung:** Datei-Fehler nicht sichtbar nach Submit
**Fix:** Error-Propagation zu Parent-Scope

#### P12: DocumentType Enum Mismatch
**Ort:** `DocumentService.php`
**Problem:** Konstanten-Werte könnten nicht mit Schema übereinstimmen
**Auswirkung:** Falsche document_type Werte
**Fix:** Verifizieren und synchronisieren

### 6.4 NIEDRIG

#### P13: Versions-Mismatch möglich
**Ort:** `FormConfigRepository::publish()`
**Problem:** DB-version und config_data.version könnten divergieren
**Auswirkung:** Inkonsistente Versionierung
**Fix:** Atomare Updates

#### P14: Kandidaten-Audit fehlt
**Ort:** `rp_candidates`
**Problem:** Keine created_by/updated_by Spalten
**Auswirkung:** Keine Nachverfolgung wer Änderungen gemacht hat
**Fix:** Audit-Spalten hinzufügen

---

## 7. Empfohlene Maßnahmen

### Sofort (KRITISCH)

1. **email_hash implementieren**
   - `ApplicationService::getOrCreateCandidate()` anpassen
   - Migration für bestehende Kandidaten

2. **consent_privacy_version speichern**
   - Feld in ApplicationService hinzufügen
   - Option für aktuelle Version erstellen

3. **File-Upload Scope refactoren**
   - Parent-Referenz explizit machen
   - Separate files[key] statt documents[]

### Kurzfristig (HOCH)

4. **File-Validierung hinzufügen**
   - `validateField()` für Datei-Typen erweitern
   - Required-Check für Dateien

5. **Kanban-Query erweitern**
   - notes_count, rating, talent_pool JOINen
   - Performance beachten (Caching?)

6. **Feld-Referenz-Validierung**
   - Bei Config-Save prüfen ob field_keys existieren
   - Bei Feld-Löschung aus Config entfernen

### Mittelfristig (MITTEL)

7. **Hidden Fields filtern**
   - Visibility-Check in applicationForm.submit()
   - Analog zu rpCustomFieldsForm

8. **Validierung erweitern**
   - Cover Letter Länge
   - Phone Format
   - Custom Field Typen

9. **Error Propagation**
   - File-Errors zu Parent propagieren
   - Einheitliches Error-Handling

---

## Anhang: Feld-Mapping Matrix

### Formular → API → Datenbank

| Frontend-Feld | API-Parameter | DB-Spalte | Tabelle |
|---------------|---------------|-----------|---------|
| first_name | first_name | first_name | rp_candidates |
| last_name | last_name | last_name | rp_candidates |
| email | email | email | rp_candidates |
| phone | phone | phone | rp_candidates |
| salutation | salutation | salutation | rp_candidates |
| message | cover_letter | cover_letter | rp_applications |
| privacy_consent | privacy_consent | consent_privacy | rp_applications |
| resume | resume (file) | - | rp_documents |
| documents[] | documents[] | - | rp_documents |
| custom_* | custom_fields | custom_fields (JSON) | rp_applications |

### Datenbank → Admin-Anzeige

| DB-Feld | Liste | Detail | Kanban | Reporting |
|---------|-------|--------|--------|-----------|
| first_name | ✓ | ✓ | ✓ | ✗ |
| last_name | ✓ | ✓ | ✓ | ✗ |
| email | ✗ | ✓ | ✓ | ✗ |
| phone | ✗ | ✓ | ✗ | ✗ |
| salutation | ✗ | ✓ | ✗ | ✗ |
| cover_letter | ✗ | ✓ | ✗ | ✗ |
| custom_fields | ✗ | ✓ (Pro) | ✗ | ✗ |
| documents | Anzahl | Liste | Anzahl | ✗ |
| status | ✓ | ✓ | ✓ | ✓ |
| created_at | ✓ | ✓ | ✓ | ✓ |
| notes_count | ✗ | ✓ | ⚠️ FEHLT | ✗ |
| rating | ✗ | ✓ | ⚠️ FEHLT | ✗ |
| consent_* | ✗ | ✗ | ✗ | ✗ |

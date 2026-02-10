# Erweiterte Formulare: Technische Spezifikation

> **Pro-Feature: Custom Fields Builder**
> Flexible Bewerbungsformulare mit benutzerdefinierten Feldern, Validierung und Conditional Logic

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Datenmodell](#3-datenmodell)
4. [REST API Endpunkte](#4-rest-api-endpunkte)
5. [Feldtypen](#5-feldtypen)
6. [Conditional Logic](#6-conditional-logic)
7. [Form Builder UI](#7-form-builder-ui)
8. [Frontend Rendering](#8-frontend-rendering)
9. [Validierung](#9-validierung)
10. [Datei-Uploads](#10-datei-uploads)
11. [Berechtigungen](#11-berechtigungen)
12. [Testing](#12-testing)
13. [Implementierungsplan](#13-implementierungsplan)

---

## 1. Übersicht

### Zielsetzung

Der Custom Fields Builder ermöglicht es Recruitern:
- **Benutzerdefinierte Felder** für Bewerbungsformulare zu erstellen
- **Verschiedene Feldtypen** (Text, Select, Checkbox, Date, etc.) zu nutzen
- **Pflichtfelder** und optionale Felder zu definieren
- **Conditional Logic** für dynamische Formulare einzusetzen
- **Mehrfach-Uploads** für Dokumente zu erlauben
- **Formulare pro Stelle** individuell anzupassen

### Feature-Gating

```php
// Pro-Feature Check
if ( ! rp_can( 'custom_fields' ) ) {
    rp_require_feature( 'custom_fields', 'Benutzerdefinierte Felder', 'PRO' );
}

// Conditional Logic ist ein separates Pro-Feature
if ( ! rp_can( 'conditional_logic' ) ) {
    rp_require_feature( 'conditional_logic', 'Bedingte Logik', 'PRO' );
}

// Basis-Formular (Name, E-Mail, Telefon, Nachricht, Datei) ist in Free verfügbar
```

### Feature-Matrix

| Feature | Free | Pro |
|---------|------|-----|
| Standard-Felder (Name, E-Mail, etc.) | ✅ | ✅ |
| Datei-Upload (1 Datei) | ✅ | ✅ |
| Mehrfach-Uploads | ❌ | ✅ |
| Benutzerdefinierte Felder | ❌ | ✅ |
| Feldtypen (Select, Radio, etc.) | ❌ | ✅ |
| Pflichtfeld-Konfiguration | ❌ | ✅ |
| Conditional Logic | ❌ | ✅ |
| Formular-Templates | ❌ | ✅ |
| Formular pro Stelle | ❌ | ✅ |

### User Stories

| Als | möchte ich | damit |
|-----|-----------|-------|
| Recruiter | ein Dropdown für "Wie haben Sie von uns erfahren?" hinzufügen | ich Recruiting-Kanäle tracken kann |
| HR-Manager | ein Pflichtfeld für Gehaltsvorstellung erstellen | ich Kandidaten vorfiltern kann |
| Recruiter | Felder nur anzeigen wenn eine bestimmte Antwort gegeben wurde | das Formular übersichtlich bleibt |
| Recruiter | mehrere Dokumente hochladen lassen | Bewerber Zeugnisse separat einreichen können |
| HR-Manager | verschiedene Formulare für verschiedene Stellen nutzen | jede Stelle passende Fragen hat |
| Recruiter | ein Formular-Template erstellen | ich es für mehrere Stellen wiederverwenden kann |

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   ├── Admin/
│   │   └── Pages/
│   │       └── FormBuilderPage.php        # Form Builder Admin-Seite
│   │
│   ├── Api/
│   │   ├── FieldDefinitionController.php  # REST API für Feld-Definitionen
│   │   ├── FormTemplateController.php     # REST API für Formular-Templates
│   │   └── FormSubmissionController.php   # Erweiterte Submission-Logik
│   │
│   ├── Services/
│   │   ├── FieldDefinitionService.php     # Feld-Definitionen Business Logic
│   │   ├── FormTemplateService.php        # Template-Verwaltung
│   │   ├── FormRenderService.php          # Frontend-Rendering
│   │   ├── FormValidationService.php      # Erweiterte Validierung
│   │   └── ConditionalLogicService.php    # Conditional Logic Engine
│   │
│   ├── Repositories/
│   │   ├── FieldDefinitionRepository.php  # Feld-Definitionen Data Access
│   │   └── FormTemplateRepository.php     # Templates Data Access
│   │
│   └── Models/
│       ├── FieldDefinition.php            # Feld-Definition Model
│       ├── FormTemplate.php               # Formular-Template Model
│       └── FieldValue.php                 # Gespeicherter Feldwert Model
│
├── assets/
│   └── src/
│       ├── js/
│       │   └── admin/
│       │       └── form-builder/
│       │           ├── index.jsx              # Entry Point
│       │           ├── FormBuilder.jsx        # Hauptkomponente
│       │           ├── FieldList.jsx          # Drag & Drop Feldliste
│       │           ├── FieldEditor.jsx        # Feld-Konfiguration
│       │           ├── FieldPreview.jsx       # Live-Vorschau
│       │           ├── ConditionalEditor.jsx  # Conditional Logic Editor
│       │           ├── TemplateManager.jsx    # Template-Verwaltung
│       │           ├── components/
│       │           │   ├── TextField.jsx
│       │           │   ├── TextareaField.jsx
│       │           │   ├── SelectField.jsx
│       │           │   ├── RadioField.jsx
│       │           │   ├── CheckboxField.jsx
│       │           │   ├── DateField.jsx
│       │           │   ├── FileField.jsx
│       │           │   ├── NumberField.jsx
│       │           │   ├── EmailField.jsx
│       │           │   ├── PhoneField.jsx
│       │           │   ├── UrlField.jsx
│       │           │   └── HeadingField.jsx
│       │           └── hooks/
│       │               ├── useFieldDefinitions.js
│       │               ├── useFormTemplate.js
│       │               └── useConditionalLogic.js
│       │
│       └── css/
│           └── admin-form-builder.css     # Form Builder Styles
│
└── templates/
    └── form/
        ├── field-text.php                 # Text-Feld Template
        ├── field-textarea.php             # Textarea Template
        ├── field-select.php               # Select Template
        ├── field-radio.php                # Radio Template
        ├── field-checkbox.php             # Checkbox Template
        ├── field-date.php                 # Date Template
        ├── field-file.php                 # File Template
        └── field-group.php                # Feldgruppe Template
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Form Builder UI | React 18 (@wordpress/element) |
| Drag & Drop | @dnd-kit/core, @dnd-kit/sortable |
| State Management | React Context + Custom Hooks |
| API-Kommunikation | @wordpress/api-fetch |
| Frontend Rendering | Alpine.js + PHP Templates |
| Validierung | Server-side (PHP) + Client-side (Alpine.js) |
| Styling | Tailwind CSS (rp- Prefix) |

### Architektur-Diagramm

```
┌─────────────────────────────────────────────────────────────────┐
│                        ADMIN (React)                             │
├─────────────────────────────────────────────────────────────────┤
│  FormBuilder.jsx                                                 │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────┐    │
│  │ FieldList   │  │ FieldEditor  │  │ FieldPreview        │    │
│  │ (Drag&Drop) │  │ (Config)     │  │ (Live)              │    │
│  └─────────────┘  └──────────────┘  └─────────────────────┘    │
│         │                │                    │                  │
│         └────────────────┼────────────────────┘                  │
│                          ▼                                       │
│                   useFieldDefinitions()                          │
└──────────────────────────┬──────────────────────────────────────┘
                           │ REST API
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BACKEND (PHP)                               │
├─────────────────────────────────────────────────────────────────┤
│  FieldDefinitionController                                       │
│         │                                                        │
│         ▼                                                        │
│  FieldDefinitionService ──► ConditionalLogicService              │
│         │                                                        │
│         ▼                                                        │
│  FieldDefinitionRepository                                       │
│         │                                                        │
│         ▼                                                        │
│  rp_field_definitions (DB)                                       │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND (Alpine.js)                          │
├─────────────────────────────────────────────────────────────────┤
│  FormRenderService.php ──► field-*.php Templates                 │
│         │                                                        │
│         ▼                                                        │
│  x-data="applicationForm()"                                      │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────┐    │
│  │ Conditional │  │ Validation   │  │ File Upload         │    │
│  │ Logic       │  │ (real-time)  │  │ (multi)             │    │
│  └─────────────┘  └──────────────┘  └─────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Datenmodell

### Neue Tabelle: `rp_field_definitions`

```sql
CREATE TABLE {$prefix}rp_field_definitions (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    template_id     bigint(20) unsigned DEFAULT NULL,
    job_id          bigint(20) unsigned DEFAULT NULL,
    field_key       varchar(100) NOT NULL,
    field_type      varchar(50) NOT NULL,
    label           varchar(255) NOT NULL,
    placeholder     varchar(255) DEFAULT NULL,
    description     text DEFAULT NULL,
    options         longtext DEFAULT NULL,
    validation      longtext DEFAULT NULL,
    conditional     longtext DEFAULT NULL,
    settings        longtext DEFAULT NULL,
    position        int(11) NOT NULL DEFAULT 0,
    is_required     tinyint(1) DEFAULT 0,
    is_system       tinyint(1) DEFAULT 0,
    is_active       tinyint(1) DEFAULT 1,
    created_at      datetime NOT NULL,
    updated_at      datetime NOT NULL,
    deleted_at      datetime DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY field_key_template (field_key, template_id),
    UNIQUE KEY field_key_job (field_key, job_id),
    KEY template_id (template_id),
    KEY job_id (job_id),
    KEY field_type (field_type),
    KEY position (position),
    KEY is_active (is_active)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `template_id` | bigint | FK zu rp_form_templates (NULL = globales Feld) |
| `job_id` | bigint | FK zu job_listing Post (NULL = Template-Feld) |
| `field_key` | varchar | Eindeutiger Schlüssel (z.B. "salary_expectation") |
| `field_type` | varchar | Feldtyp (text, select, checkbox, etc.) |
| `label` | varchar | Anzeigetext |
| `placeholder` | varchar | Platzhalter-Text |
| `description` | text | Hilfetext unter dem Feld |
| `options` | longtext | JSON: Optionen für Select/Radio/Checkbox |
| `validation` | longtext | JSON: Validierungsregeln |
| `conditional` | longtext | JSON: Conditional Logic Regeln |
| `settings` | longtext | JSON: Zusätzliche Einstellungen |
| `position` | int | Sortierreihenfolge |
| `is_required` | tinyint | Pflichtfeld |
| `is_system` | tinyint | System-Feld (nicht löschbar) |
| `is_active` | tinyint | Aktiv/Inaktiv |
| `created_at` | datetime | Erstellungsdatum |
| `updated_at` | datetime | Letzte Änderung |
| `deleted_at` | datetime | Soft Delete |

#### Options JSON-Struktur (Select/Radio/Checkbox)

```json
{
    "choices": [
        { "value": "website", "label": "Unternehmenswebsite" },
        { "value": "linkedin", "label": "LinkedIn" },
        { "value": "indeed", "label": "Indeed" },
        { "value": "referral", "label": "Empfehlung" },
        { "value": "other", "label": "Sonstiges" }
    ],
    "allow_other": true,
    "other_label": "Bitte angeben..."
}
```

#### Validation JSON-Struktur

```json
{
    "min_length": 10,
    "max_length": 500,
    "min": 30000,
    "max": 150000,
    "pattern": "^[0-9]+$",
    "pattern_message": "Bitte nur Zahlen eingeben",
    "allowed_extensions": ["pdf", "doc", "docx"],
    "max_file_size": 5242880,
    "custom_message": "Bitte geben Sie Ihre Gehaltsvorstellung an"
}
```

#### Conditional JSON-Struktur

```json
{
    "action": "show",
    "logic": "and",
    "conditions": [
        {
            "field": "employment_type",
            "operator": "equals",
            "value": "freelance"
        },
        {
            "field": "experience_years",
            "operator": "greater_than",
            "value": "5"
        }
    ]
}
```

#### Settings JSON-Struktur

```json
{
    "width": "full",
    "css_class": "highlight-field",
    "autocomplete": "off",
    "rows": 5,
    "date_format": "d.m.Y",
    "min_date": "today",
    "max_date": "+1 year",
    "multiple": true,
    "max_files": 5
}
```

### Neue Tabelle: `rp_form_templates`

```sql
CREATE TABLE {$prefix}rp_form_templates (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    name            varchar(255) NOT NULL,
    description     text DEFAULT NULL,
    is_default      tinyint(1) DEFAULT 0,
    settings        longtext DEFAULT NULL,
    created_by      bigint(20) unsigned NOT NULL,
    created_at      datetime NOT NULL,
    updated_at      datetime NOT NULL,
    deleted_at      datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY is_default (is_default),
    KEY created_by (created_by)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `name` | varchar | Template-Name |
| `description` | text | Beschreibung |
| `is_default` | tinyint | Standard-Template für neue Stellen |
| `settings` | longtext | JSON: Template-weite Einstellungen |
| `created_by` | bigint | FK zu wp_users |
| `created_at` | datetime | Erstellungsdatum |
| `updated_at` | datetime | Letzte Änderung |
| `deleted_at` | datetime | Soft Delete |

### Erweiterung: `rp_applications`

```sql
ALTER TABLE {$prefix}rp_applications
ADD COLUMN custom_fields longtext DEFAULT NULL AFTER message;
```

#### Custom Fields JSON-Struktur

```json
{
    "salary_expectation": "65000",
    "source": "linkedin",
    "source_other": null,
    "available_from": "2025-03-01",
    "willing_to_relocate": true,
    "languages": ["de", "en", "fr"]
}
```

### System-Felder (nicht löschbar)

| field_key | field_type | label | is_system |
|-----------|------------|-------|-----------|
| `first_name` | text | Vorname | 1 |
| `last_name` | text | Nachname | 1 |
| `email` | email | E-Mail | 1 |
| `phone` | phone | Telefon | 1 |
| `message` | textarea | Anschreiben | 1 |
| `resume` | file | Lebenslauf | 1 |
| `privacy_consent` | checkbox | Datenschutz | 1 |

---

## 4. REST API Endpunkte

### Field Definitions API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/field-definitions` | Alle Feld-Definitionen |
| GET | `/recruiting/v1/field-definitions/{id}` | Einzelne Definition |
| POST | `/recruiting/v1/field-definitions` | Neue Definition erstellen |
| PATCH | `/recruiting/v1/field-definitions/{id}` | Definition aktualisieren |
| DELETE | `/recruiting/v1/field-definitions/{id}` | Definition löschen |
| POST | `/recruiting/v1/field-definitions/reorder` | Reihenfolge ändern |
| GET | `/recruiting/v1/jobs/{id}/fields` | Felder einer Stelle |
| POST | `/recruiting/v1/jobs/{id}/fields` | Felder einer Stelle speichern |

#### GET /field-definitions

```php
register_rest_route(
    $this->namespace,
    '/field-definitions',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_definitions' ],
        'permission_callback' => [ $this, 'get_definitions_permissions_check' ],
        'args'                => [
            'template_id' => [
                'description' => __( 'Filter nach Template', 'recruiting-playbook' ),
                'type'        => 'integer',
            ],
            'job_id' => [
                'description' => __( 'Filter nach Stelle', 'recruiting-playbook' ),
                'type'        => 'integer',
            ],
            'include_system' => [
                'description' => __( 'System-Felder einschließen', 'recruiting-playbook' ),
                'type'        => 'boolean',
                'default'     => true,
            ],
            'active_only' => [
                'description' => __( 'Nur aktive Felder', 'recruiting-playbook' ),
                'type'        => 'boolean',
                'default'     => true,
            ],
        ],
    ]
);
```

#### Response Schema (Liste)

```json
{
    "fields": [
        {
            "id": 1,
            "field_key": "first_name",
            "field_type": "text",
            "label": "Vorname",
            "placeholder": "Max",
            "description": null,
            "options": null,
            "validation": {
                "min_length": 2,
                "max_length": 100
            },
            "conditional": null,
            "settings": {
                "width": "half",
                "autocomplete": "given-name"
            },
            "position": 1,
            "is_required": true,
            "is_system": true,
            "is_active": true
        },
        {
            "id": 10,
            "field_key": "salary_expectation",
            "field_type": "number",
            "label": "Gehaltsvorstellung (brutto/Jahr)",
            "placeholder": "z.B. 65000",
            "description": "Ihre Gehaltsvorstellung in Euro",
            "options": null,
            "validation": {
                "min": 20000,
                "max": 500000
            },
            "conditional": null,
            "settings": {
                "width": "half",
                "suffix": "€"
            },
            "position": 10,
            "is_required": false,
            "is_system": false,
            "is_active": true
        }
    ],
    "total": 12,
    "field_types": [
        "text", "textarea", "email", "phone", "number",
        "select", "radio", "checkbox", "date", "file",
        "url", "heading"
    ]
}
```

#### POST /field-definitions

```php
register_rest_route(
    $this->namespace,
    '/field-definitions',
    [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [ $this, 'create_definition' ],
        'permission_callback' => [ $this, 'create_definition_permissions_check' ],
        'args'                => [
            'field_key' => [
                'description'       => __( 'Eindeutiger Schlüssel', 'recruiting-playbook' ),
                'type'              => 'string',
                'required'          => true,
                'pattern'           => '^[a-z][a-z0-9_]*$',
                'sanitize_callback' => 'sanitize_key',
            ],
            'field_type' => [
                'description' => __( 'Feldtyp', 'recruiting-playbook' ),
                'type'        => 'string',
                'required'    => true,
                'enum'        => [
                    'text', 'textarea', 'email', 'phone', 'number',
                    'select', 'radio', 'checkbox', 'date', 'file',
                    'url', 'heading',
                ],
            ],
            'label' => [
                'description'       => __( 'Anzeigetext', 'recruiting-playbook' ),
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'template_id' => [
                'description' => __( 'Template-ID', 'recruiting-playbook' ),
                'type'        => 'integer',
            ],
            'job_id' => [
                'description' => __( 'Stellen-ID', 'recruiting-playbook' ),
                'type'        => 'integer',
            ],
            'placeholder' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'description' => [
                'type'              => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ],
            'options' => [
                'type' => 'object',
            ],
            'validation' => [
                'type' => 'object',
            ],
            'conditional' => [
                'type' => 'object',
            ],
            'settings' => [
                'type' => 'object',
            ],
            'is_required' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'position' => [
                'type'    => 'integer',
                'default' => 0,
            ],
        ],
    ]
);
```

### Form Templates API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/form-templates` | Alle Templates |
| GET | `/recruiting/v1/form-templates/{id}` | Einzelnes Template mit Feldern |
| POST | `/recruiting/v1/form-templates` | Neues Template erstellen |
| PATCH | `/recruiting/v1/form-templates/{id}` | Template aktualisieren |
| DELETE | `/recruiting/v1/form-templates/{id}` | Template löschen |
| POST | `/recruiting/v1/form-templates/{id}/duplicate` | Template duplizieren |
| POST | `/recruiting/v1/form-templates/{id}/set-default` | Als Standard setzen |

#### GET /form-templates/{id}

```json
{
    "id": 1,
    "name": "Standard-Bewerbung",
    "description": "Basis-Formular für alle Stellen",
    "is_default": true,
    "settings": {
        "submit_button_text": "Bewerbung absenden",
        "success_message": "Vielen Dank für Ihre Bewerbung!",
        "enable_file_upload": true,
        "max_files": 3
    },
    "fields": [
        { "id": 1, "field_key": "first_name", ... },
        { "id": 2, "field_key": "last_name", ... },
        ...
    ],
    "created_by": {
        "id": 1,
        "name": "Admin"
    },
    "created_at": "2025-01-25T10:00:00Z",
    "updated_at": "2025-01-25T10:00:00Z",
    "usage_count": 15
}
```

---

## 5. Feldtypen

### Verfügbare Feldtypen

| Typ | Beschreibung | Validierung | Beispiel |
|-----|--------------|-------------|----------|
| `text` | Einzeiliges Textfeld | min/max_length, pattern | Name, Titel |
| `textarea` | Mehrzeiliges Textfeld | min/max_length, rows | Anschreiben |
| `email` | E-Mail-Feld | E-Mail-Format | E-Mail-Adresse |
| `phone` | Telefon-Feld | Telefon-Format | +49 123 456789 |
| `number` | Zahlenfeld | min/max, step | Gehaltsvorstellung |
| `select` | Dropdown | choices, allow_other | Berufsfeld |
| `radio` | Radio-Buttons | choices | Ja/Nein Fragen |
| `checkbox` | Checkboxen | choices (multi), single | Sprachen, DSGVO |
| `date` | Datumsfeld | min_date, max_date | Verfügbar ab |
| `file` | Datei-Upload | extensions, max_size | Lebenslauf |
| `url` | URL-Feld | URL-Format | LinkedIn-Profil |
| `heading` | Überschrift/Trenner | - | Abschnitte trennen |

### Feldtyp-Konfiguration

#### Text-Feld

```php
class TextField implements FieldTypeInterface {
    public function getType(): string {
        return 'text';
    }

    public function getLabel(): string {
        return __( 'Textfeld', 'recruiting-playbook' );
    }

    public function getIcon(): string {
        return 'text';
    }

    public function getDefaultSettings(): array {
        return [
            'width'        => 'full',
            'autocomplete' => null,
        ];
    }

    public function getValidationRules(): array {
        return [
            'min_length' => [
                'type'    => 'integer',
                'label'   => __( 'Mindestlänge', 'recruiting-playbook' ),
                'default' => null,
            ],
            'max_length' => [
                'type'    => 'integer',
                'label'   => __( 'Maximallänge', 'recruiting-playbook' ),
                'default' => null,
            ],
            'pattern' => [
                'type'    => 'string',
                'label'   => __( 'Regex-Pattern', 'recruiting-playbook' ),
                'default' => null,
            ],
        ];
    }

    public function validate( $value, array $validation ): bool|WP_Error {
        if ( empty( $value ) ) {
            return true; // Required-Check separat
        }

        $length = mb_strlen( $value );

        if ( isset( $validation['min_length'] ) && $length < $validation['min_length'] ) {
            return new WP_Error(
                'min_length',
                sprintf(
                    __( 'Mindestens %d Zeichen erforderlich', 'recruiting-playbook' ),
                    $validation['min_length']
                )
            );
        }

        if ( isset( $validation['max_length'] ) && $length > $validation['max_length'] ) {
            return new WP_Error(
                'max_length',
                sprintf(
                    __( 'Maximal %d Zeichen erlaubt', 'recruiting-playbook' ),
                    $validation['max_length']
                )
            );
        }

        if ( isset( $validation['pattern'] ) && ! preg_match( '/' . $validation['pattern'] . '/', $value ) ) {
            return new WP_Error(
                'pattern',
                $validation['pattern_message'] ?? __( 'Ungültiges Format', 'recruiting-playbook' )
            );
        }

        return true;
    }

    public function sanitize( $value ): string {
        return sanitize_text_field( $value );
    }

    public function render( FieldDefinition $field, $value = null ): string {
        return rp_get_template(
            'form/field-text.php',
            [
                'field' => $field,
                'value' => $value,
            ]
        );
    }
}
```

#### Select-Feld

```php
class SelectField implements FieldTypeInterface {
    public function getType(): string {
        return 'select';
    }

    public function getLabel(): string {
        return __( 'Dropdown', 'recruiting-playbook' );
    }

    public function getIcon(): string {
        return 'list';
    }

    public function getDefaultSettings(): array {
        return [
            'width'       => 'full',
            'searchable'  => false,
            'placeholder' => __( 'Bitte wählen...', 'recruiting-playbook' ),
        ];
    }

    public function getOptionsSchema(): array {
        return [
            'choices' => [
                'type'    => 'array',
                'label'   => __( 'Auswahloptionen', 'recruiting-playbook' ),
                'items'   => [
                    'value' => [ 'type' => 'string', 'required' => true ],
                    'label' => [ 'type' => 'string', 'required' => true ],
                ],
            ],
            'allow_other' => [
                'type'    => 'boolean',
                'label'   => __( '"Sonstiges" erlauben', 'recruiting-playbook' ),
                'default' => false,
            ],
            'other_label' => [
                'type'    => 'string',
                'label'   => __( 'Label für "Sonstiges"', 'recruiting-playbook' ),
                'default' => __( 'Sonstiges', 'recruiting-playbook' ),
            ],
        ];
    }

    public function validate( $value, array $validation, array $options = [] ): bool|WP_Error {
        if ( empty( $value ) ) {
            return true;
        }

        $valid_values = array_column( $options['choices'] ?? [], 'value' );

        if ( ! empty( $options['allow_other'] ) ) {
            $valid_values[] = '_other';
        }

        if ( ! in_array( $value, $valid_values, true ) ) {
            return new WP_Error(
                'invalid_choice',
                __( 'Ungültige Auswahl', 'recruiting-playbook' )
            );
        }

        return true;
    }

    public function sanitize( $value ): string {
        return sanitize_text_field( $value );
    }
}
```

#### File-Feld

```php
class FileField implements FieldTypeInterface {
    public function getType(): string {
        return 'file';
    }

    public function getLabel(): string {
        return __( 'Datei-Upload', 'recruiting-playbook' );
    }

    public function getIcon(): string {
        return 'upload';
    }

    public function getDefaultSettings(): array {
        return [
            'width'      => 'full',
            'multiple'   => false,
            'max_files'  => 1,
            'accept'     => '.pdf,.doc,.docx',
            'drag_drop'  => true,
        ];
    }

    public function getValidationRules(): array {
        return [
            'allowed_extensions' => [
                'type'    => 'array',
                'label'   => __( 'Erlaubte Dateitypen', 'recruiting-playbook' ),
                'default' => [ 'pdf', 'doc', 'docx' ],
            ],
            'max_file_size' => [
                'type'    => 'integer',
                'label'   => __( 'Max. Dateigröße (Bytes)', 'recruiting-playbook' ),
                'default' => 10485760, // 10 MB
            ],
            'max_files' => [
                'type'    => 'integer',
                'label'   => __( 'Max. Anzahl Dateien', 'recruiting-playbook' ),
                'default' => 1,
            ],
        ];
    }

    public function validate( $files, array $validation ): bool|WP_Error {
        if ( empty( $files ) ) {
            return true;
        }

        $files = is_array( $files ) ? $files : [ $files ];
        $max_files = $validation['max_files'] ?? 1;

        if ( count( $files ) > $max_files ) {
            return new WP_Error(
                'too_many_files',
                sprintf(
                    __( 'Maximal %d Dateien erlaubt', 'recruiting-playbook' ),
                    $max_files
                )
            );
        }

        $allowed = $validation['allowed_extensions'] ?? [ 'pdf', 'doc', 'docx' ];
        $max_size = $validation['max_file_size'] ?? 10485760;

        foreach ( $files as $file ) {
            $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

            if ( ! in_array( $ext, $allowed, true ) ) {
                return new WP_Error(
                    'invalid_extension',
                    sprintf(
                        __( 'Dateityp .%s nicht erlaubt. Erlaubt: %s', 'recruiting-playbook' ),
                        $ext,
                        implode( ', ', $allowed )
                    )
                );
            }

            if ( $file['size'] > $max_size ) {
                return new WP_Error(
                    'file_too_large',
                    sprintf(
                        __( 'Datei zu groß. Maximum: %s', 'recruiting-playbook' ),
                        size_format( $max_size )
                    )
                );
            }
        }

        return true;
    }
}
```

---

## 6. Conditional Logic

### Operatoren

| Operator | Beschreibung | Feldtypen |
|----------|--------------|-----------|
| `equals` | Ist gleich | Alle |
| `not_equals` | Ist nicht gleich | Alle |
| `contains` | Enthält | text, textarea |
| `not_contains` | Enthält nicht | text, textarea |
| `starts_with` | Beginnt mit | text |
| `ends_with` | Endet mit | text |
| `greater_than` | Größer als | number, date |
| `less_than` | Kleiner als | number, date |
| `is_empty` | Ist leer | Alle |
| `is_not_empty` | Ist nicht leer | Alle |
| `is_checked` | Ist ausgewählt | checkbox |
| `is_not_checked` | Ist nicht ausgewählt | checkbox |

### Aktionen

| Aktion | Beschreibung |
|--------|--------------|
| `show` | Feld anzeigen wenn Bedingung erfüllt |
| `hide` | Feld ausblenden wenn Bedingung erfüllt |
| `require` | Feld wird Pflichtfeld wenn Bedingung erfüllt |
| `unrequire` | Feld wird optional wenn Bedingung erfüllt |
| `enable` | Feld aktivieren wenn Bedingung erfüllt |
| `disable` | Feld deaktivieren wenn Bedingung erfüllt |

### ConditionalLogicService

```php
class ConditionalLogicService {

    /**
     * Prüft ob ein Feld basierend auf Conditional Logic sichtbar ist
     */
    public function isFieldVisible( FieldDefinition $field, array $form_data ): bool {
        $conditional = $field->getConditional();

        if ( empty( $conditional ) ) {
            return true;
        }

        $action = $conditional['action'] ?? 'show';
        $result = $this->evaluateConditions( $conditional, $form_data );

        return match ( $action ) {
            'show' => $result,
            'hide' => ! $result,
            default => true,
        };
    }

    /**
     * Prüft ob ein Feld basierend auf Conditional Logic erforderlich ist
     */
    public function isFieldRequired( FieldDefinition $field, array $form_data ): bool {
        // Basis-Required-Status
        $required = $field->isRequired();

        $conditional = $field->getConditional();

        if ( empty( $conditional ) ) {
            return $required;
        }

        $action = $conditional['action'] ?? 'show';
        $result = $this->evaluateConditions( $conditional, $form_data );

        return match ( $action ) {
            'require' => $result ? true : $required,
            'unrequire' => $result ? false : $required,
            default => $required,
        };
    }

    /**
     * Evaluiert alle Bedingungen
     */
    private function evaluateConditions( array $conditional, array $form_data ): bool {
        $logic = $conditional['logic'] ?? 'and';
        $conditions = $conditional['conditions'] ?? [];

        if ( empty( $conditions ) ) {
            return true;
        }

        $results = array_map(
            fn( $condition ) => $this->evaluateSingleCondition( $condition, $form_data ),
            $conditions
        );

        return match ( $logic ) {
            'and' => ! in_array( false, $results, true ),
            'or'  => in_array( true, $results, true ),
            default => true,
        };
    }

    /**
     * Evaluiert eine einzelne Bedingung
     */
    private function evaluateSingleCondition( array $condition, array $form_data ): bool {
        $field_key = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $compare_value = $condition['value'] ?? '';

        $field_value = $form_data[ $field_key ] ?? null;

        return match ( $operator ) {
            'equals'         => $field_value === $compare_value,
            'not_equals'     => $field_value !== $compare_value,
            'contains'       => str_contains( (string) $field_value, $compare_value ),
            'not_contains'   => ! str_contains( (string) $field_value, $compare_value ),
            'starts_with'    => str_starts_with( (string) $field_value, $compare_value ),
            'ends_with'      => str_ends_with( (string) $field_value, $compare_value ),
            'greater_than'   => (float) $field_value > (float) $compare_value,
            'less_than'      => (float) $field_value < (float) $compare_value,
            'is_empty'       => empty( $field_value ),
            'is_not_empty'   => ! empty( $field_value ),
            'is_checked'     => (bool) $field_value === true,
            'is_not_checked' => (bool) $field_value === false,
            default          => true,
        };
    }

    /**
     * Generiert JavaScript für Client-Side Conditional Logic
     */
    public function generateClientScript( array $fields ): string {
        $conditions = [];

        foreach ( $fields as $field ) {
            $conditional = $field->getConditional();
            if ( ! empty( $conditional ) ) {
                $conditions[ $field->getFieldKey() ] = $conditional;
            }
        }

        if ( empty( $conditions ) ) {
            return '';
        }

        return sprintf(
            '<script>window.rpConditionalLogic = %s;</script>',
            wp_json_encode( $conditions )
        );
    }
}
```

### Alpine.js Conditional Logic Handler

```javascript
// In applicationForm() Alpine component
conditionalLogic: window.rpConditionalLogic || {},

isFieldVisible(fieldKey) {
    const condition = this.conditionalLogic[fieldKey];
    if (!condition) return true;

    const result = this.evaluateConditions(condition);
    return condition.action === 'show' ? result : !result;
},

evaluateConditions(condition) {
    const logic = condition.logic || 'and';
    const conditions = condition.conditions || [];

    if (conditions.length === 0) return true;

    const results = conditions.map(c => this.evaluateSingleCondition(c));

    return logic === 'and'
        ? results.every(r => r)
        : results.some(r => r);
},

evaluateSingleCondition(condition) {
    const fieldValue = this.formData[condition.field];
    const compareValue = condition.value;

    switch (condition.operator) {
        case 'equals':
            return fieldValue === compareValue;
        case 'not_equals':
            return fieldValue !== compareValue;
        case 'contains':
            return String(fieldValue).includes(compareValue);
        case 'greater_than':
            return parseFloat(fieldValue) > parseFloat(compareValue);
        case 'less_than':
            return parseFloat(fieldValue) < parseFloat(compareValue);
        case 'is_empty':
            return !fieldValue;
        case 'is_not_empty':
            return !!fieldValue;
        case 'is_checked':
            return fieldValue === true;
        case 'is_not_checked':
            return fieldValue !== true;
        default:
            return true;
    }
}
```

---

## 7. Form Builder UI

### React-Komponenten

#### FormBuilder.jsx (Hauptkomponente)

```jsx
import { useState, useCallback } from '@wordpress/element';
import { DndContext, closestCenter } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { FieldList } from './FieldList';
import { FieldEditor } from './FieldEditor';
import { FieldPreview } from './FieldPreview';
import { useFieldDefinitions } from './hooks/useFieldDefinitions';

export function FormBuilder({ templateId, jobId }) {
    const {
        fields,
        loading,
        error,
        addField,
        updateField,
        deleteField,
        reorderFields,
    } = useFieldDefinitions({ templateId, jobId });

    const [selectedFieldId, setSelectedFieldId] = useState(null);

    const selectedField = fields.find(f => f.id === selectedFieldId);

    const handleDragEnd = useCallback((event) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = fields.findIndex(f => f.id === active.id);
            const newIndex = fields.findIndex(f => f.id === over.id);
            reorderFields(oldIndex, newIndex);
        }
    }, [fields, reorderFields]);

    const handleAddField = useCallback((fieldType) => {
        addField({
            field_type: fieldType,
            label: getDefaultLabel(fieldType),
            field_key: generateFieldKey(fieldType),
        });
    }, [addField]);

    if (loading) {
        return <div className="rp-loading">Lädt...</div>;
    }

    if (error) {
        return <div className="rp-error">{error}</div>;
    }

    return (
        <div className="rp-form-builder">
            <div className="rp-form-builder__header">
                <h2>Formular-Builder</h2>
                <FieldTypeSelector onSelect={handleAddField} />
            </div>

            <div className="rp-form-builder__content">
                {/* Feldliste mit Drag & Drop */}
                <div className="rp-form-builder__fields">
                    <DndContext
                        collisionDetection={closestCenter}
                        onDragEnd={handleDragEnd}
                    >
                        <SortableContext
                            items={fields.map(f => f.id)}
                            strategy={verticalListSortingStrategy}
                        >
                            <FieldList
                                fields={fields}
                                selectedId={selectedFieldId}
                                onSelect={setSelectedFieldId}
                                onDelete={deleteField}
                            />
                        </SortableContext>
                    </DndContext>
                </div>

                {/* Feld-Editor */}
                <div className="rp-form-builder__editor">
                    {selectedField ? (
                        <FieldEditor
                            field={selectedField}
                            onUpdate={updateField}
                            allFields={fields}
                        />
                    ) : (
                        <div className="rp-form-builder__placeholder">
                            Wählen Sie ein Feld zum Bearbeiten
                        </div>
                    )}
                </div>

                {/* Live-Vorschau */}
                <div className="rp-form-builder__preview">
                    <h3>Vorschau</h3>
                    <FieldPreview fields={fields} />
                </div>
            </div>
        </div>
    );
}
```

#### FieldEditor.jsx

```jsx
import { useState, useEffect } from '@wordpress/element';
import { TextControl, ToggleControl, SelectControl } from '@wordpress/components';
import { ConditionalEditor } from './ConditionalEditor';

export function FieldEditor({ field, onUpdate, allFields }) {
    const [localField, setLocalField] = useState(field);

    useEffect(() => {
        setLocalField(field);
    }, [field]);

    const handleChange = (key, value) => {
        const updated = { ...localField, [key]: value };
        setLocalField(updated);
        onUpdate(field.id, { [key]: value });
    };

    const handleNestedChange = (parent, key, value) => {
        const parentData = localField[parent] || {};
        const updated = { ...parentData, [key]: value };
        handleChange(parent, updated);
    };

    return (
        <div className="rp-field-editor">
            <h3>Feld bearbeiten</h3>

            {/* Basis-Einstellungen */}
            <div className="rp-field-editor__section">
                <h4>Allgemein</h4>

                <TextControl
                    label="Feld-Schlüssel"
                    value={localField.field_key}
                    onChange={(v) => handleChange('field_key', v)}
                    disabled={localField.is_system}
                    help="Eindeutiger Bezeichner (nur Kleinbuchstaben, Zahlen, Unterstriche)"
                />

                <TextControl
                    label="Label"
                    value={localField.label}
                    onChange={(v) => handleChange('label', v)}
                />

                <TextControl
                    label="Platzhalter"
                    value={localField.placeholder || ''}
                    onChange={(v) => handleChange('placeholder', v)}
                />

                <TextControl
                    label="Hilfetext"
                    value={localField.description || ''}
                    onChange={(v) => handleChange('description', v)}
                    help="Wird unter dem Feld angezeigt"
                />

                <ToggleControl
                    label="Pflichtfeld"
                    checked={localField.is_required}
                    onChange={(v) => handleChange('is_required', v)}
                />

                <ToggleControl
                    label="Aktiv"
                    checked={localField.is_active}
                    onChange={(v) => handleChange('is_active', v)}
                />
            </div>

            {/* Optionen für Select/Radio/Checkbox */}
            {['select', 'radio', 'checkbox'].includes(localField.field_type) && (
                <div className="rp-field-editor__section">
                    <h4>Auswahloptionen</h4>
                    <OptionsEditor
                        options={localField.options || { choices: [] }}
                        onChange={(opts) => handleChange('options', opts)}
                    />
                </div>
            )}

            {/* Validierung */}
            <div className="rp-field-editor__section">
                <h4>Validierung</h4>
                <ValidationEditor
                    fieldType={localField.field_type}
                    validation={localField.validation || {}}
                    onChange={(v) => handleChange('validation', v)}
                />
            </div>

            {/* Conditional Logic */}
            <div className="rp-field-editor__section">
                <h4>Bedingte Logik</h4>
                <ConditionalEditor
                    conditional={localField.conditional}
                    availableFields={allFields.filter(f => f.id !== field.id)}
                    onChange={(c) => handleChange('conditional', c)}
                />
            </div>

            {/* Erweiterte Einstellungen */}
            <div className="rp-field-editor__section">
                <h4>Erweiterte Einstellungen</h4>

                <SelectControl
                    label="Breite"
                    value={localField.settings?.width || 'full'}
                    options={[
                        { value: 'full', label: 'Volle Breite' },
                        { value: 'half', label: 'Halbe Breite' },
                        { value: 'third', label: 'Drittel' },
                    ]}
                    onChange={(v) => handleNestedChange('settings', 'width', v)}
                />

                <TextControl
                    label="CSS-Klasse"
                    value={localField.settings?.css_class || ''}
                    onChange={(v) => handleNestedChange('settings', 'css_class', v)}
                />
            </div>
        </div>
    );
}
```

#### ConditionalEditor.jsx

```jsx
import { useState } from '@wordpress/element';
import { Button, SelectControl, TextControl } from '@wordpress/components';
import { Plus, Trash2 } from 'lucide-react';

const OPERATORS = [
    { value: 'equals', label: 'Ist gleich' },
    { value: 'not_equals', label: 'Ist nicht gleich' },
    { value: 'contains', label: 'Enthält' },
    { value: 'is_empty', label: 'Ist leer' },
    { value: 'is_not_empty', label: 'Ist nicht leer' },
    { value: 'greater_than', label: 'Größer als' },
    { value: 'less_than', label: 'Kleiner als' },
    { value: 'is_checked', label: 'Ist ausgewählt' },
    { value: 'is_not_checked', label: 'Ist nicht ausgewählt' },
];

const ACTIONS = [
    { value: 'show', label: 'Anzeigen' },
    { value: 'hide', label: 'Ausblenden' },
    { value: 'require', label: 'Pflichtfeld machen' },
    { value: 'unrequire', label: 'Optional machen' },
];

export function ConditionalEditor({ conditional, availableFields, onChange }) {
    const [enabled, setEnabled] = useState(!!conditional);
    const [config, setConfig] = useState(conditional || {
        action: 'show',
        logic: 'and',
        conditions: [],
    });

    const handleToggle = () => {
        if (enabled) {
            onChange(null);
        } else {
            onChange(config);
        }
        setEnabled(!enabled);
    };

    const handleConfigChange = (key, value) => {
        const updated = { ...config, [key]: value };
        setConfig(updated);
        if (enabled) {
            onChange(updated);
        }
    };

    const addCondition = () => {
        const conditions = [
            ...config.conditions,
            { field: '', operator: 'equals', value: '' },
        ];
        handleConfigChange('conditions', conditions);
    };

    const updateCondition = (index, updates) => {
        const conditions = config.conditions.map((c, i) =>
            i === index ? { ...c, ...updates } : c
        );
        handleConfigChange('conditions', conditions);
    };

    const removeCondition = (index) => {
        const conditions = config.conditions.filter((_, i) => i !== index);
        handleConfigChange('conditions', conditions);
    };

    const fieldOptions = availableFields.map(f => ({
        value: f.field_key,
        label: f.label,
    }));

    return (
        <div className="rp-conditional-editor">
            <ToggleControl
                label="Bedingte Logik aktivieren"
                checked={enabled}
                onChange={handleToggle}
            />

            {enabled && (
                <div className="rp-conditional-editor__config">
                    <div className="rp-conditional-editor__row">
                        <SelectControl
                            label="Aktion"
                            value={config.action}
                            options={ACTIONS}
                            onChange={(v) => handleConfigChange('action', v)}
                        />

                        <span className="rp-conditional-editor__label">
                            wenn
                        </span>

                        <SelectControl
                            label="Logik"
                            value={config.logic}
                            options={[
                                { value: 'and', label: 'alle Bedingungen' },
                                { value: 'or', label: 'eine Bedingung' },
                            ]}
                            onChange={(v) => handleConfigChange('logic', v)}
                        />

                        <span className="rp-conditional-editor__label">
                            erfüllt {config.logic === 'and' ? 'sind' : 'ist'}:
                        </span>
                    </div>

                    <div className="rp-conditional-editor__conditions">
                        {config.conditions.map((condition, index) => (
                            <div key={index} className="rp-conditional-editor__condition">
                                <SelectControl
                                    label="Feld"
                                    value={condition.field}
                                    options={[
                                        { value: '', label: 'Feld wählen...' },
                                        ...fieldOptions,
                                    ]}
                                    onChange={(v) => updateCondition(index, { field: v })}
                                />

                                <SelectControl
                                    label="Operator"
                                    value={condition.operator}
                                    options={OPERATORS}
                                    onChange={(v) => updateCondition(index, { operator: v })}
                                />

                                {!['is_empty', 'is_not_empty', 'is_checked', 'is_not_checked'].includes(condition.operator) && (
                                    <TextControl
                                        label="Wert"
                                        value={condition.value}
                                        onChange={(v) => updateCondition(index, { value: v })}
                                    />
                                )}

                                <Button
                                    icon={<Trash2 size={16} />}
                                    onClick={() => removeCondition(index)}
                                    isDestructive
                                    label="Bedingung entfernen"
                                />
                            </div>
                        ))}
                    </div>

                    <Button
                        icon={<Plus size={16} />}
                        onClick={addCondition}
                        variant="secondary"
                    >
                        Bedingung hinzufügen
                    </Button>
                </div>
            )}
        </div>
    );
}
```

---

## 8. Frontend Rendering

### FormRenderService

```php
class FormRenderService {

    private FieldDefinitionService $field_service;
    private ConditionalLogicService $conditional_service;

    public function __construct(
        FieldDefinitionService $field_service,
        ConditionalLogicService $conditional_service
    ) {
        $this->field_service = $field_service;
        $this->conditional_service = $conditional_service;
    }

    /**
     * Rendert das komplette Bewerbungsformular
     */
    public function render( int $job_id ): string {
        $fields = $this->field_service->getFieldsForJob( $job_id );

        if ( empty( $fields ) ) {
            return '<p>' . __( 'Kein Formular konfiguriert.', 'recruiting-playbook' ) . '</p>';
        }

        $output = '';

        // Conditional Logic Script
        $output .= $this->conditional_service->generateClientScript( $fields );

        // Formular-Start
        $output .= sprintf(
            '<form x-data="applicationForm(%d)" @submit.prevent="submit" class="rp-application-form" data-rp-application-form>',
            $job_id
        );

        // CSRF Token
        $output .= wp_nonce_field( 'rp_application_submit', 'rp_nonce', true, false );

        // Hidden Job ID
        $output .= sprintf( '<input type="hidden" name="job_id" value="%d">', $job_id );

        // Honeypot
        $output .= rp_get_honeypot_field();

        // Timestamp
        $output .= rp_get_timestamp_field();

        // Felder rendern
        foreach ( $fields as $field ) {
            $output .= $this->renderField( $field );
        }

        // Submit-Button
        $output .= $this->renderSubmitButton();

        // Formular-Ende
        $output .= '</form>';

        return $output;
    }

    /**
     * Rendert ein einzelnes Feld
     */
    private function renderField( FieldDefinition $field ): string {
        if ( ! $field->isActive() ) {
            return '';
        }

        $field_type = $this->field_service->getFieldType( $field->getFieldType() );

        if ( ! $field_type ) {
            return '';
        }

        $wrapper_attrs = $this->getWrapperAttributes( $field );

        $output = sprintf(
            '<div %s>',
            $this->buildAttributes( $wrapper_attrs )
        );

        // Label
        if ( $field->getFieldType() !== 'heading' ) {
            $output .= sprintf(
                '<label for="rp_%s" class="rp-form__label">%s%s</label>',
                esc_attr( $field->getFieldKey() ),
                esc_html( $field->getLabel() ),
                $field->isRequired() ? '<span class="rp-form__required">*</span>' : ''
            );
        }

        // Feld rendern
        $output .= $field_type->render( $field );

        // Hilfetext
        if ( $field->getDescription() ) {
            $output .= sprintf(
                '<p class="rp-form__description">%s</p>',
                wp_kses_post( $field->getDescription() )
            );
        }

        // Fehleranzeige
        $output .= sprintf(
            '<p x-show="errors.%s" x-text="errors.%s" class="rp-form__error"></p>',
            esc_attr( $field->getFieldKey() ),
            esc_attr( $field->getFieldKey() )
        );

        $output .= '</div>';

        return $output;
    }

    /**
     * Wrapper-Attribute für Conditional Logic
     */
    private function getWrapperAttributes( FieldDefinition $field ): array {
        $classes = [
            'rp-form__field',
            'rp-form__field--' . $field->getFieldType(),
            'rp-form__field--' . ( $field->getSettings()['width'] ?? 'full' ),
        ];

        if ( $field->getSettings()['css_class'] ?? '' ) {
            $classes[] = $field->getSettings()['css_class'];
        }

        $attrs = [
            'class' => implode( ' ', $classes ),
        ];

        // Conditional Logic x-show Attribut
        if ( $field->getConditional() ) {
            $attrs['x-show'] = sprintf( "isFieldVisible('%s')", $field->getFieldKey() );
            $attrs['x-transition'] = '';
        }

        return $attrs;
    }

    /**
     * Submit-Button rendern
     */
    private function renderSubmitButton(): string {
        return sprintf(
            '<div class="rp-form__submit">
                <button
                    type="submit"
                    class="rp-button rp-button--primary"
                    :disabled="submitting"
                >
                    <span x-show="!submitting">%s</span>
                    <span x-show="submitting">%s</span>
                </button>
            </div>',
            __( 'Bewerbung absenden', 'recruiting-playbook' ),
            __( 'Wird gesendet...', 'recruiting-playbook' )
        );
    }
}
```

### Feld-Template Beispiel (field-select.php)

```php
<?php
/**
 * Select-Feld Template
 *
 * @var FieldDefinition $field
 * @var mixed $value
 */

defined( 'ABSPATH' ) || exit;

$options = $field->getOptions();
$choices = $options['choices'] ?? [];
$settings = $field->getSettings();
$field_key = $field->getFieldKey();
?>

<select
    id="rp_<?php echo esc_attr( $field_key ); ?>"
    name="<?php echo esc_attr( $field_key ); ?>"
    x-model="formData.<?php echo esc_attr( $field_key ); ?>"
    class="rp-form__select"
    <?php echo $field->isRequired() ? ':required="isFieldRequired(\'' . esc_attr( $field_key ) . '\')"' : ''; ?>
    <?php echo ! empty( $settings['searchable'] ) ? 'x-ref="select" x-init="initSelect2($refs.select)"' : ''; ?>
>
    <?php if ( $field->getPlaceholder() ) : ?>
        <option value=""><?php echo esc_html( $field->getPlaceholder() ); ?></option>
    <?php endif; ?>

    <?php foreach ( $choices as $choice ) : ?>
        <option value="<?php echo esc_attr( $choice['value'] ); ?>">
            <?php echo esc_html( $choice['label'] ); ?>
        </option>
    <?php endforeach; ?>

    <?php if ( ! empty( $options['allow_other'] ) ) : ?>
        <option value="_other">
            <?php echo esc_html( $options['other_label'] ?? __( 'Sonstiges', 'recruiting-playbook' ) ); ?>
        </option>
    <?php endif; ?>
</select>

<?php if ( ! empty( $options['allow_other'] ) ) : ?>
    <div x-show="formData.<?php echo esc_attr( $field_key ); ?> === '_other'" x-transition class="rp-form__other-input">
        <input
            type="text"
            name="<?php echo esc_attr( $field_key ); ?>_other"
            x-model="formData.<?php echo esc_attr( $field_key ); ?>_other"
            placeholder="<?php echo esc_attr( $options['other_placeholder'] ?? __( 'Bitte angeben...', 'recruiting-playbook' ) ); ?>"
            class="rp-form__input"
        >
    </div>
<?php endif; ?>
```

---

## 9. Validierung

### FormValidationService

```php
class FormValidationService {

    private FieldDefinitionService $field_service;
    private ConditionalLogicService $conditional_service;
    private array $field_types;

    /**
     * Validiert eine komplette Formular-Submission
     */
    public function validate( array $data, int $job_id ): array {
        $fields = $this->field_service->getFieldsForJob( $job_id );
        $errors = [];

        foreach ( $fields as $field ) {
            // Skip inaktive Felder
            if ( ! $field->isActive() ) {
                continue;
            }

            // Skip nicht-sichtbare Felder (Conditional Logic)
            if ( ! $this->conditional_service->isFieldVisible( $field, $data ) ) {
                continue;
            }

            $field_key = $field->getFieldKey();
            $value = $data[ $field_key ] ?? null;

            // Required-Check (mit Conditional Logic)
            $is_required = $this->conditional_service->isFieldRequired( $field, $data );

            if ( $is_required && $this->isEmpty( $value ) ) {
                $errors[ $field_key ] = sprintf(
                    __( '%s ist ein Pflichtfeld', 'recruiting-playbook' ),
                    $field->getLabel()
                );
                continue;
            }

            // Skip weitere Validierung wenn leer
            if ( $this->isEmpty( $value ) ) {
                continue;
            }

            // Feldtyp-spezifische Validierung
            $field_type = $this->field_types[ $field->getFieldType() ] ?? null;

            if ( $field_type ) {
                $result = $field_type->validate(
                    $value,
                    $field->getValidation() ?? [],
                    $field->getOptions() ?? []
                );

                if ( is_wp_error( $result ) ) {
                    $errors[ $field_key ] = $result->get_error_message();
                }
            }
        }

        return $errors;
    }

    /**
     * Sanitisiert alle Formularwerte
     */
    public function sanitize( array $data, int $job_id ): array {
        $fields = $this->field_service->getFieldsForJob( $job_id );
        $sanitized = [];

        foreach ( $fields as $field ) {
            $field_key = $field->getFieldKey();

            if ( ! isset( $data[ $field_key ] ) ) {
                continue;
            }

            $field_type = $this->field_types[ $field->getFieldType() ] ?? null;

            if ( $field_type ) {
                $sanitized[ $field_key ] = $field_type->sanitize( $data[ $field_key ] );
            } else {
                $sanitized[ $field_key ] = sanitize_text_field( $data[ $field_key ] );
            }

            // "Other" Werte
            if ( isset( $data[ $field_key . '_other' ] ) ) {
                $sanitized[ $field_key . '_other' ] = sanitize_text_field( $data[ $field_key . '_other' ] );
            }
        }

        return $sanitized;
    }

    private function isEmpty( $value ): bool {
        if ( is_null( $value ) ) {
            return true;
        }

        if ( is_string( $value ) && trim( $value ) === '' ) {
            return true;
        }

        if ( is_array( $value ) && empty( $value ) ) {
            return true;
        }

        return false;
    }
}
```

---

## 10. Datei-Uploads

### Multi-Upload Konfiguration

```php
class FileUploadService {

    private const UPLOAD_DIR = 'rp-applications';

    /**
     * Verarbeitet Mehrfach-Uploads
     */
    public function processUploads( array $files, FieldDefinition $field ): array|WP_Error {
        $validation = $field->getValidation() ?? [];
        $settings = $field->getSettings() ?? [];

        $max_files = $settings['max_files'] ?? 1;
        $processed = [];

        // Normalisiere $_FILES Array für multiple uploads
        $normalized = $this->normalizeFiles( $files );

        if ( count( $normalized ) > $max_files ) {
            return new WP_Error(
                'too_many_files',
                sprintf(
                    __( 'Maximal %d Dateien erlaubt', 'recruiting-playbook' ),
                    $max_files
                )
            );
        }

        foreach ( $normalized as $file ) {
            // Validierung
            $field_type = new FileField();
            $valid = $field_type->validate( [ $file ], $validation );

            if ( is_wp_error( $valid ) ) {
                return $valid;
            }

            // Upload
            $result = $this->uploadFile( $file );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            $processed[] = $result;
        }

        return $processed;
    }

    /**
     * Einzelne Datei hochladen
     */
    private function uploadFile( array $file ): array|WP_Error {
        // Sichere Upload-Verzeichnis
        $upload_dir = wp_upload_dir();
        $target_dir = trailingslashit( $upload_dir['basedir'] ) . self::UPLOAD_DIR;

        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );

            // .htaccess für Sicherheit
            file_put_contents(
                $target_dir . '/.htaccess',
                "Options -Indexes\nDeny from all"
            );
        }

        // UUID-basierter Dateiname
        $uuid = wp_generate_uuid4();
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $new_filename = $uuid . '.' . $ext;
        $target_path = $target_dir . '/' . $new_filename;

        // Move uploaded file
        if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
            return new WP_Error(
                'upload_failed',
                __( 'Datei-Upload fehlgeschlagen', 'recruiting-playbook' )
            );
        }

        return [
            'uuid'          => $uuid,
            'original_name' => $file['name'],
            'filename'      => $new_filename,
            'path'          => $target_path,
            'size'          => $file['size'],
            'mime_type'     => $file['type'],
        ];
    }

    /**
     * Normalisiert $_FILES Array für multiple uploads
     */
    private function normalizeFiles( array $files ): array {
        if ( ! is_array( $files['name'] ) ) {
            return [ $files ];
        }

        $normalized = [];

        foreach ( $files['name'] as $index => $name ) {
            $normalized[] = [
                'name'     => $name,
                'type'     => $files['type'][ $index ],
                'tmp_name' => $files['tmp_name'][ $index ],
                'error'    => $files['error'][ $index ],
                'size'     => $files['size'][ $index ],
            ];
        }

        return $normalized;
    }
}
```

---

## 11. Berechtigungen

### Capabilities

| Capability | Beschreibung | Admin | Recruiter | Hiring Manager |
|------------|--------------|-------|-----------|----------------|
| `rp_manage_form_builder` | Formular-Builder verwalten | ✅ | ❌ | ❌ |
| `rp_edit_form_templates` | Templates bearbeiten | ✅ | ✅ | ❌ |
| `rp_delete_form_templates` | Templates löschen | ✅ | ❌ | ❌ |
| `rp_edit_job_forms` | Stellen-Formulare bearbeiten | ✅ | ✅ | ❌ |

### Permission Checks

```php
class FieldDefinitionController extends WP_REST_Controller {

    public function get_definitions_permissions_check( WP_REST_Request $request ): bool {
        // Jeder mit rp_view_applications kann Feld-Definitionen lesen
        return current_user_can( 'rp_view_applications' );
    }

    public function create_definition_permissions_check( WP_REST_Request $request ): bool {
        // Nur Pro-Nutzer mit entsprechender Berechtigung
        if ( ! rp_is_pro() ) {
            return false;
        }

        $job_id = $request->get_param( 'job_id' );

        if ( $job_id ) {
            return current_user_can( 'rp_edit_job_forms' )
                && rp_user_can_access_job( get_current_user_id(), $job_id );
        }

        return current_user_can( 'rp_edit_form_templates' );
    }

    public function delete_definition_permissions_check( WP_REST_Request $request ): bool {
        // System-Felder dürfen nicht gelöscht werden
        $field = $this->service->get( $request->get_param( 'id' ) );

        if ( ! $field || $field->isSystem() ) {
            return false;
        }

        return current_user_can( 'rp_delete_form_templates' );
    }
}
```

---

## 12. Testing

### Unit Tests

```php
class FieldDefinitionServiceTest extends TestCase {

    public function test_create_field_definition(): void {
        $service = new FieldDefinitionService( $this->repository );

        $result = $service->create( [
            'field_key'   => 'test_field',
            'field_type'  => 'text',
            'label'       => 'Test Field',
            'is_required' => true,
        ] );

        $this->assertIsArray( $result );
        $this->assertEquals( 'test_field', $result['field_key'] );
        $this->assertTrue( $result['is_required'] );
    }

    public function test_field_key_must_be_unique(): void {
        $service = new FieldDefinitionService( $this->repository );

        $service->create( [
            'field_key'  => 'duplicate_key',
            'field_type' => 'text',
            'label'      => 'First',
        ] );

        $result = $service->create( [
            'field_key'  => 'duplicate_key',
            'field_type' => 'text',
            'label'      => 'Second',
        ] );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertEquals( 'duplicate_key', $result->get_error_code() );
    }

    public function test_system_field_cannot_be_deleted(): void {
        $service = new FieldDefinitionService( $this->repository );

        $result = $service->delete( 1 ); // first_name is system field

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertEquals( 'cannot_delete_system', $result->get_error_code() );
    }

    public function test_validation_rules_are_applied(): void {
        $field = new FieldDefinition( [
            'field_key'  => 'salary',
            'field_type' => 'number',
            'validation' => [ 'min' => 20000, 'max' => 500000 ],
        ] );

        $field_type = new NumberField();

        // Valid value
        $this->assertTrue( $field_type->validate( 50000, $field->getValidation() ) );

        // Too low
        $result = $field_type->validate( 10000, $field->getValidation() );
        $this->assertInstanceOf( WP_Error::class, $result );

        // Too high
        $result = $field_type->validate( 600000, $field->getValidation() );
        $this->assertInstanceOf( WP_Error::class, $result );
    }
}

class ConditionalLogicServiceTest extends TestCase {

    public function test_field_visible_when_condition_met(): void {
        $service = new ConditionalLogicService();

        $field = new FieldDefinition( [
            'field_key'   => 'hourly_rate',
            'conditional' => [
                'action'     => 'show',
                'logic'      => 'and',
                'conditions' => [
                    [
                        'field'    => 'employment_type',
                        'operator' => 'equals',
                        'value'    => 'freelance',
                    ],
                ],
            ],
        ] );

        // Condition met
        $this->assertTrue(
            $service->isFieldVisible( $field, [ 'employment_type' => 'freelance' ] )
        );

        // Condition not met
        $this->assertFalse(
            $service->isFieldVisible( $field, [ 'employment_type' => 'fulltime' ] )
        );
    }

    public function test_or_logic_requires_one_condition(): void {
        $service = new ConditionalLogicService();

        $field = new FieldDefinition( [
            'field_key'   => 'remote_preference',
            'conditional' => [
                'action'     => 'show',
                'logic'      => 'or',
                'conditions' => [
                    [ 'field' => 'location', 'operator' => 'equals', 'value' => 'remote' ],
                    [ 'field' => 'location', 'operator' => 'equals', 'value' => 'hybrid' ],
                ],
            ],
        ] );

        // First condition met
        $this->assertTrue(
            $service->isFieldVisible( $field, [ 'location' => 'remote' ] )
        );

        // Second condition met
        $this->assertTrue(
            $service->isFieldVisible( $field, [ 'location' => 'hybrid' ] )
        );

        // No condition met
        $this->assertFalse(
            $service->isFieldVisible( $field, [ 'location' => 'onsite' ] )
        );
    }
}

class FormValidationServiceTest extends TestCase {

    public function test_required_field_validation(): void {
        $service = new FormValidationService( $this->field_service, $this->conditional_service );

        $errors = $service->validate(
            [ 'first_name' => '' ],
            123 // job_id
        );

        $this->assertArrayHasKey( 'first_name', $errors );
    }

    public function test_hidden_field_not_validated(): void {
        // Setup conditional logic to hide field
        $this->conditional_service
            ->expects( $this->once() )
            ->method( 'isFieldVisible' )
            ->willReturn( false );

        $service = new FormValidationService( $this->field_service, $this->conditional_service );

        $errors = $service->validate(
            [ 'hidden_field' => '' ],
            123
        );

        // Hidden required field should not cause error
        $this->assertArrayNotHasKey( 'hidden_field', $errors );
    }
}
```

### Test Coverage Ziele

| Bereich | Ziel |
|---------|------|
| FieldDefinitionService | 80% |
| ConditionalLogicService | 90% |
| FormValidationService | 85% |
| Feldtypen | 70% |
| REST API Endpoints | 60% |

---

## 13. Implementierungsplan

### Phase 1: Datenmodell & Backend (Woche 1)

| Tag | Aufgabe |
|-----|---------|
| 1-2 | Datenbank-Schema erstellen (rp_field_definitions, rp_form_templates) |
| 3 | FieldDefinition Model & Repository |
| 4 | FormTemplate Model & Repository |
| 5 | FieldDefinitionService Grundfunktionen |

**Deliverables:**
- ✅ Tabellen angelegt
- ✅ CRUD für Feld-Definitionen
- ✅ CRUD für Templates
- ✅ Unit Tests für Services

### Phase 2: Feldtypen & Validierung (Woche 2)

| Tag | Aufgabe |
|-----|---------|
| 1-2 | Feldtyp-Interface & Basis-Implementierungen (text, textarea, email) |
| 3 | Erweiterte Feldtypen (select, radio, checkbox, date) |
| 4 | File-Upload Feldtyp mit Multi-Upload |
| 5 | FormValidationService & Tests |

**Deliverables:**
- ✅ Alle 12 Feldtypen implementiert
- ✅ Validierung für jeden Typ
- ✅ Multi-File-Upload funktioniert
- ✅ Unit Tests für Feldtypen

### Phase 3: REST API (Woche 3)

| Tag | Aufgabe |
|-----|---------|
| 1-2 | FieldDefinitionController |
| 3 | FormTemplateController |
| 4 | Job-spezifische Felder-Endpoints |
| 5 | Berechtigungsprüfungen & Tests |

**Deliverables:**
- ✅ Alle REST Endpoints
- ✅ Permission Checks
- ✅ API Response Schema dokumentiert
- ✅ Postman Collection

### Phase 4: Conditional Logic (Woche 4)

| Tag | Aufgabe |
|-----|---------|
| 1-2 | ConditionalLogicService Backend |
| 3 | Client-Side Conditional Logic (Alpine.js) |
| 4 | Integration in Validierung |
| 5 | Tests & Edge Cases |

**Deliverables:**
- ✅ Alle Operatoren implementiert
- ✅ AND/OR Logik
- ✅ Client-Side Reaktivität
- ✅ Validierung respektiert Conditional Logic

### Phase 5: Form Builder UI (Woche 5)

| Tag | Aufgabe |
|-----|---------|
| 1 | FormBuilder Grundstruktur |
| 2 | FieldList mit Drag & Drop |
| 3 | FieldEditor Komponente |
| 4 | ConditionalEditor Komponente |
| 5 | FieldPreview & Live-Vorschau |

**Deliverables:**
- ✅ Drag & Drop funktioniert
- ✅ Alle Feld-Einstellungen editierbar
- ✅ Conditional Logic konfigurierbar
- ✅ Live-Vorschau aktualisiert sich

### Phase 6: Frontend Rendering (Woche 6)

| Tag | Aufgabe |
|-----|---------|
| 1-2 | FormRenderService |
| 3 | PHP Templates für alle Feldtypen |
| 4 | Alpine.js Integration (Conditional Logic, Validation) |
| 5 | Styling & Responsiveness |

**Deliverables:**
- ✅ Formulare werden korrekt gerendert
- ✅ Conditional Logic funktioniert im Frontend
- ✅ Client-Side Validierung
- ✅ Mobile-optimiert

### Phase 7: Integration & Testing (Woche 7)

| Tag | Aufgabe |
|-----|---------|
| 1-2 | Integration in Bewerbungs-Flow |
| 3 | Custom Fields in Application-Daten speichern |
| 4 | Custom Fields in Admin-Ansicht anzeigen |
| 5 | End-to-End Testing |

**Deliverables:**
- ✅ Bewerbungen mit Custom Fields
- ✅ Admin sieht alle Custom Field Werte
- ✅ Export enthält Custom Fields
- ✅ Alle Tests grün

---

## Anhang

### Migration Script

```php
class CreateCustomFieldsTables {

    public function up(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // rp_field_definitions
        $sql1 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_field_definitions (
            id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            template_id     bigint(20) unsigned DEFAULT NULL,
            job_id          bigint(20) unsigned DEFAULT NULL,
            field_key       varchar(100) NOT NULL,
            field_type      varchar(50) NOT NULL,
            label           varchar(255) NOT NULL,
            placeholder     varchar(255) DEFAULT NULL,
            description     text DEFAULT NULL,
            options         longtext DEFAULT NULL,
            validation      longtext DEFAULT NULL,
            conditional     longtext DEFAULT NULL,
            settings        longtext DEFAULT NULL,
            position        int(11) NOT NULL DEFAULT 0,
            is_required     tinyint(1) DEFAULT 0,
            is_system       tinyint(1) DEFAULT 0,
            is_active       tinyint(1) DEFAULT 1,
            created_at      datetime NOT NULL,
            updated_at      datetime NOT NULL,
            deleted_at      datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY field_key_template (field_key, template_id),
            UNIQUE KEY field_key_job (field_key, job_id),
            KEY template_id (template_id),
            KEY job_id (job_id),
            KEY field_type (field_type),
            KEY position (position),
            KEY is_active (is_active)
        ) {$charset_collate};";

        // rp_form_templates
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}rp_form_templates (
            id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name            varchar(255) NOT NULL,
            description     text DEFAULT NULL,
            is_default      tinyint(1) DEFAULT 0,
            settings        longtext DEFAULT NULL,
            created_by      bigint(20) unsigned NOT NULL,
            created_at      datetime NOT NULL,
            updated_at      datetime NOT NULL,
            deleted_at      datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY is_default (is_default),
            KEY created_by (created_by)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $sql1 );
        dbDelta( $sql2 );

        // Custom Fields Spalte zu rp_applications hinzufügen
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}rp_applications
            ADD COLUMN IF NOT EXISTS custom_fields longtext DEFAULT NULL AFTER message" );

        // System-Felder erstellen
        $this->createSystemFields();
    }

    private function createSystemFields(): void {
        $system_fields = [
            [
                'field_key'   => 'first_name',
                'field_type'  => 'text',
                'label'       => __( 'Vorname', 'recruiting-playbook' ),
                'is_required' => true,
                'is_system'   => true,
                'position'    => 1,
                'validation'  => [ 'min_length' => 2, 'max_length' => 100 ],
                'settings'    => [ 'width' => 'half', 'autocomplete' => 'given-name' ],
            ],
            [
                'field_key'   => 'last_name',
                'field_type'  => 'text',
                'label'       => __( 'Nachname', 'recruiting-playbook' ),
                'is_required' => true,
                'is_system'   => true,
                'position'    => 2,
                'validation'  => [ 'min_length' => 2, 'max_length' => 100 ],
                'settings'    => [ 'width' => 'half', 'autocomplete' => 'family-name' ],
            ],
            [
                'field_key'   => 'email',
                'field_type'  => 'email',
                'label'       => __( 'E-Mail', 'recruiting-playbook' ),
                'is_required' => true,
                'is_system'   => true,
                'position'    => 3,
                'settings'    => [ 'width' => 'half', 'autocomplete' => 'email' ],
            ],
            [
                'field_key'   => 'phone',
                'field_type'  => 'phone',
                'label'       => __( 'Telefon', 'recruiting-playbook' ),
                'is_required' => false,
                'is_system'   => true,
                'position'    => 4,
                'settings'    => [ 'width' => 'half', 'autocomplete' => 'tel' ],
            ],
            [
                'field_key'   => 'message',
                'field_type'  => 'textarea',
                'label'       => __( 'Anschreiben', 'recruiting-playbook' ),
                'is_required' => false,
                'is_system'   => true,
                'position'    => 5,
                'validation'  => [ 'max_length' => 5000 ],
                'settings'    => [ 'rows' => 6 ],
            ],
            [
                'field_key'   => 'resume',
                'field_type'  => 'file',
                'label'       => __( 'Lebenslauf', 'recruiting-playbook' ),
                'is_required' => true,
                'is_system'   => true,
                'position'    => 6,
                'validation'  => [
                    'allowed_extensions' => [ 'pdf', 'doc', 'docx' ],
                    'max_file_size'      => 10485760,
                ],
            ],
            [
                'field_key'   => 'privacy_consent',
                'field_type'  => 'checkbox',
                'label'       => __( 'Datenschutzerklärung', 'recruiting-playbook' ),
                'is_required' => true,
                'is_system'   => true,
                'position'    => 100,
                'options'     => [
                    'choices' => [
                        [
                            'value' => '1',
                            'label' => __( 'Ich habe die Datenschutzerklärung gelesen und stimme der Verarbeitung meiner Daten zu.', 'recruiting-playbook' ),
                        ],
                    ],
                ],
            ],
        ];

        $service = new FieldDefinitionService();

        foreach ( $system_fields as $field ) {
            $service->create( $field );
        }
    }
}
```

---

## Implementierungsstatus

> Stand: 30. Januar 2026 - **Feature vollständig implementiert**

### Abgeschlossene Phasen

| Phase | Status | Tests |
|-------|--------|-------|
| Phase 1: Datenmodell & Backend | ✅ Abgeschlossen | 12 Tests |
| Phase 2: Feldtypen & Validierung | ✅ Abgeschlossen | 16 Tests |
| Phase 3: REST API | ✅ Abgeschlossen | 10 Tests |
| Phase 4: Conditional Logic | ✅ Abgeschlossen | 25 Tests |
| Phase 5: Form Builder UI | ✅ Abgeschlossen | - |
| Phase 6: Frontend Rendering | ✅ Abgeschlossen | - |
| Phase 7: Integration & Testing | ✅ Abgeschlossen | 49 Tests |

### Implementierte Komponenten

**Backend (PHP):**
- `FieldDefinition` Model mit vollständiger Hydration
- `FieldDefinitionService` mit CRUD und Job-spezifischen Feldern
- `FormTemplateService` für Template-Verwaltung
- `CustomFieldsService` für Verarbeitung und Speicherung
- `CustomFieldFileService` für Multi-File-Uploads
- `FormValidationService` mit Conditional Logic Support
- `ConditionalLogicService` mit 11 Operatoren
- `FormRenderService` für dynamisches Rendering
- REST API Controller für alle Endpoints

**Frontend (React/Alpine.js):**
- `FormBuilder` React-Komponente mit Drag & Drop
- `useFieldDefinitions` Hook für State Management
- `FieldEditor`, `ConditionalEditor` Komponenten
- Alpine.js `rpCustomFieldsForm` für Conditional Logic
- 12 Feld-Templates mit Theme-Override Support

**Neue Dateien:**
- `plugin/src/Services/CustomFieldsService.php`
- `plugin/src/Services/CustomFieldFileService.php`
- `plugin/src/Database/Migrations/CustomFieldsMigration.php`
- `plugin/src/Admin/MetaBoxes/JobCustomFieldsMeta.php`
- `plugin/assets/src/js/admin/applicant/CustomFieldsPanel.jsx`

### Pro-Gating

Das Feature ist über folgende Checks geschützt:

```php
if ( ! function_exists( 'rp_can' ) || ! rp_can( 'custom_fields' ) ) {
    return; // Feature nicht verfügbar
}
```

**Betroffene Bereiche:**
- Form Builder Admin-Seite
- REST API Endpoints
- Job Custom Fields Meta Box
- Custom Form Shortcode (fällt auf Standard zurück)
- Migration

---

*Letzte Aktualisierung: 30. Januar 2026*

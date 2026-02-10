# Custom Fields & Formular-Builder: Technische Spezifikation v2

> **Pro-Feature: Formular-Builder**
> Step-basierter Bewerbungsformular-Editor mit Drag & Drop, Live-Vorschau und Draft/Publish-System

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Konzept & Workflow](#2-konzept--workflow)
3. [Datenmodell](#3-datenmodell)
4. [UI-Konzept](#4-ui-konzept)
5. [Frontend-Integration](#5-frontend-integration)
6. [REST API](#6-rest-api)
7. [Implementierungsdetails](#7-implementierungsdetails)
8. [Migration von v1](#8-migration-von-v1)

---

## 1. Übersicht

### Zielsetzung

Der Formular-Builder ermöglicht es Recruitern:
- **Mehrstufige Formulare** visuell zu gestalten (Step 1, Step 2, ... , Finale)
- **Felder per Drag & Drop** zu positionieren und zwischen Steps zu verschieben
- **Live-Vorschau** des Formulars vor Veröffentlichung
- **Draft/Publish-System** für sichere Änderungen ohne sofortige Auswirkung
- **Custom Fields** (Pro) für individuelle Fragen hinzuzufügen

### Feature-Matrix

| Feature | Free | Pro |
|---------|------|-----|
| Standard-Formular (3 Steps, feste Felder) | ✅ | ✅ |
| Felder aktivieren/deaktivieren | ✅ | ✅ |
| Felder umsortieren (Drag & Drop) | ❌ | ✅ |
| Benutzerdefinierte Felder erstellen | ❌ | ✅ |
| Steps hinzufügen/entfernen | ❌ | ✅ |
| Conditional Logic | ❌ | ✅ |
| Draft/Publish-System | ❌ | ✅ |
| Formular-Vorschau | ✅ | ✅ |

### Kernprinzipien

1. **WYSIWYG**: Was du im Builder siehst, ist was im Frontend erscheint
2. **Sicher**: Änderungen erst nach expliziter Veröffentlichung live
3. **Einfach**: Drag & Drop, keine Programmierung nötig
4. **Flexibel**: Von einfachen Anpassungen bis komplexe Formulare

---

## 2. Konzept & Workflow

### 2.1 Draft/Publish-System

```
┌─────────────────────────────────────────────────────────────────┐
│                        WORKFLOW                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   [Bearbeiten] ──► [Speichern] ──► [Vorschau] ──► [Veröffentl.] │
│        │               │               │               │         │
│        ▼               ▼               ▼               ▼         │
│   Änderungen      Draft in DB     Zeigt Draft     Draft → Live  │
│   im UI           gespeichert     im Preview      Frontend neu   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

**Zustände:**
- `draft` - Arbeitsversion, nur in Vorschau sichtbar
- `published` - Live-Version, im Frontend aktiv
- `has_unpublished_changes` - Flag wenn Draft ≠ Published

### 2.2 Benutzer-Workflow

1. **Formular öffnen** → Zeigt aktuellen Draft (oder Published wenn kein Draft)
2. **Änderungen machen** → Felder verschieben, hinzufügen, konfigurieren
3. **Speichern** → Draft wird in DB gespeichert
4. **Vorschau** → Tab zeigt Formular mit Draft-Konfiguration
5. **Veröffentlichen** → Draft wird zur Live-Version, Frontend aktualisiert

### 2.3 Free vs. Pro Workflow

**Free-User:**
- Sieht Standard-Formular (nicht editierbar)
- Kann einzelne Felder aktivieren/deaktivieren
- Keine Draft/Publish-Unterscheidung (Änderungen sofort live)

**Pro-User:**
- Voller Zugriff auf Formular-Builder
- Draft/Publish-System aktiv
- Kann Custom Fields erstellen und Steps verwalten

---

## 3. Datenmodell

### 3.1 Datenbankschema

#### Tabelle: `wp_rp_form_config`

Speichert die Formular-Konfiguration (Draft & Published).

```sql
CREATE TABLE wp_rp_form_config (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(50) NOT NULL,           -- 'draft' oder 'published'
    config_data LONGTEXT NOT NULL,             -- JSON mit Steps & Feldern
    version INT UNSIGNED DEFAULT 1,            -- Versionsnummer
    updated_by BIGINT UNSIGNED,                -- User ID
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Tabelle: `wp_rp_field_definitions` (bestehend, erweitert)

```sql
CREATE TABLE wp_rp_field_definitions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_key VARCHAR(100) NOT NULL UNIQUE,    -- Eindeutiger Schlüssel
    field_type VARCHAR(50) NOT NULL,           -- text, email, select, file, etc.
    label VARCHAR(255) NOT NULL,               -- Anzeige-Label
    placeholder VARCHAR(255),                  -- Platzhalter-Text
    description TEXT,                          -- Hilfetext
    options JSON,                              -- Für Select/Radio/Checkbox
    validation JSON,                           -- Validierungsregeln
    settings JSON,                             -- Weitere Einstellungen
    is_system TINYINT(1) DEFAULT 0,            -- System-Feld (nicht löschbar)
    is_active TINYINT(1) DEFAULT 1,            -- Feld verfügbar
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL                   -- Soft Delete
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.2 JSON-Struktur: config_data

```json
{
  "version": 2,
  "steps": [
    {
      "id": "step_1",
      "title": "Persönliche Daten",
      "description": "Ihre Kontaktdaten",
      "position": 1,
      "is_deletable": true,
      "fields": [
        {
          "field_key": "salutation",
          "position": 1,
          "width": "third",
          "is_required": false,
          "is_visible": true,
          "overrides": {}
        },
        {
          "field_key": "first_name",
          "position": 2,
          "width": "third",
          "is_required": true,
          "is_visible": true,
          "overrides": {
            "label": "Ihr Vorname"
          }
        },
        {
          "field_key": "last_name",
          "position": 3,
          "width": "third",
          "is_required": true,
          "is_visible": true
        },
        {
          "field_key": "email",
          "position": 4,
          "width": "half",
          "is_required": true,
          "is_visible": true
        },
        {
          "field_key": "phone",
          "position": 5,
          "width": "half",
          "is_required": false,
          "is_visible": true
        }
      ]
    },
    {
      "id": "step_2",
      "title": "Bewerbungsunterlagen",
      "description": "Laden Sie Ihre Dokumente hoch",
      "position": 2,
      "is_deletable": true,
      "fields": [
        {
          "field_key": "resume",
          "position": 1,
          "width": "full",
          "is_required": false,
          "is_visible": true
        },
        {
          "field_key": "documents",
          "position": 2,
          "width": "full",
          "is_required": false,
          "is_visible": true
        },
        {
          "field_key": "cover_letter",
          "position": 3,
          "width": "full",
          "is_required": false,
          "is_visible": true
        }
      ]
    },
    {
      "id": "step_finale",
      "title": "Datenschutz & Absenden",
      "description": "Prüfen und absenden",
      "position": 99,
      "is_deletable": false,
      "fields": [
        {
          "field_key": "summary",
          "position": 1,
          "width": "full",
          "is_system_ui": true
        },
        {
          "field_key": "privacy_consent",
          "position": 2,
          "width": "full",
          "is_required": true,
          "is_visible": true
        }
      ]
    }
  ],
  "settings": {
    "submit_button_text": "Bewerbung absenden",
    "success_message": "Vielen Dank für Ihre Bewerbung!"
  }
}
```

### 3.3 Standard-Felder (System-Felder)

Diese Felder werden bei Plugin-Aktivierung erstellt und können nicht gelöscht werden:

| field_key | field_type | Label | Pflicht (default) |
|-----------|------------|-------|-------------------|
| `salutation` | select | Anrede | Nein |
| `first_name` | text | Vorname | Ja |
| `last_name` | text | Nachname | Ja |
| `email` | email | E-Mail | Ja |
| `phone` | phone | Telefon | Nein |
| `resume` | file | Lebenslauf | Nein |
| `documents` | file_multiple | Weitere Dokumente | Nein |
| `cover_letter` | textarea | Anschreiben | Nein |
| `privacy_consent` | checkbox | Datenschutz-Einwilligung | Ja |

### 3.4 Breiten-Optionen

| Wert | CSS-Klasse | Beschreibung |
|------|------------|--------------|
| `full` | `rp-col-span-full` | Volle Breite (100%) |
| `half` | `rp-col-span-6` | Halbe Breite (50%) |
| `third` | `rp-col-span-4` | Ein Drittel (33%) |
| `two-thirds` | `rp-col-span-8` | Zwei Drittel (66%) |

---

## 4. UI-Konzept

### 4.1 Haupt-Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  [Logo]                                        Formular-Builder         │
├─────────────────────────────────────────────────────────────────────────┤
│  [Felder]  [Formular-Builder]  [Vorschau]                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─ FORMULAR-BUILDER ─────────────────────┐  ┌─ SIDEBAR ──────────────┐│
│  │                                         │  │                        ││
│  │  Status: ⚠️ Unveröffentlichte Änderungen│  │  Kein Feld ausgewählt  ││
│  │                                         │  │                        ││
│  │  ┌─ Schritt 1: Persönliche Daten ─────┐│  │  Wählen Sie ein Feld   ││
│  │  │ ≡ Anrede    │ ≡ Vorname │ ≡ Nachn. ││  │  aus der Liste.        ││
│  │  │ ≡ E-Mail              │ ≡ Telefon  ││  │                        ││
│  │  │                                    ││  └────────────────────────┘│
│  │  │ [+ Feld hinzufügen]                ││                            │
│  │  └────────────────────────────────────┘│                            │
│  │                                         │                            │
│  │  ┌─ Schritt 2: Unterlagen ────────────┐│                            │
│  │  │ ≡ Lebenslauf                       ││                            │
│  │  │ ≡ Weitere Dokumente                ││                            │
│  │  │ ≡ Anschreiben                      ││                            │
│  │  │ [+ Feld hinzufügen]                ││                            │
│  │  └────────────────────────────────────┘│                            │
│  │                                         │                            │
│  │  [+ Neuen Schritt hinzufügen]          │                            │
│  │                                         │                            │
│  │  ┌─ Finale: Datenschutz 🔒 ───────────┐│                            │
│  │  │ 📋 Zusammenfassung (automatisch)   ││                            │
│  │  │ ≡ Datenschutz-Einwilligung        ││                            │
│  │  └────────────────────────────────────┘│                            │
│  │                                         │                            │
│  │  [Speichern]  [Veröffentlichen ▼]      │                            │
│  │                                         │                            │
│  └─────────────────────────────────────────┘                            │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Feld-Element im Builder

```
┌─────────────────────────────────────────┐
│ ≡  Vorname                    ✓ ⚙️  ✕  │
│    [Pflichtfeld]                        │
└─────────────────────────────────────────┘
  │       │                      │  │  │
  │       │                      │  │  └── Aus Step entfernen
  │       │                      │  └───── Einstellungen (öffnet Sidebar)
  │       │                      └──────── Sichtbarkeit Toggle
  │       └─────────────────────────────── Feld-Label
  └─────────────────────────────────────── Drag Handle
```

### 4.3 Sidebar: Feld-Einstellungen

```
┌─ Feld bearbeiten ──────────────────────┐
│                                    ✕   │
├────────────────────────────────────────┤
│  [Allgemein] [Validierung] [Bedingt]   │
├────────────────────────────────────────┤
│                                        │
│  Feldschlüssel                         │
│  ┌──────────────────────────────────┐  │
│  │ first_name                    🔒 │  │
│  └──────────────────────────────────┘  │
│  System-Feld (nicht änderbar)          │
│                                        │
│  Label                                 │
│  ┌──────────────────────────────────┐  │
│  │ Vorname                          │  │
│  └──────────────────────────────────┘  │
│                                        │
│  Platzhalter                           │
│  ┌──────────────────────────────────┐  │
│  │ Ihr Vorname...                   │  │
│  └──────────────────────────────────┘  │
│                                        │
│  Breite                                │
│  ┌──────────────────────────────────┐  │
│  │ Halbe Breite (50%)            ▼  │  │
│  └──────────────────────────────────┘  │
│                                        │
│  ─────────────────────────────────     │
│  ☑ Pflichtfeld                         │
│  ☑ Sichtbar                            │
│                                        │
└────────────────────────────────────────┘
```

### 4.4 Tab "Felder" (Feld-Bibliothek)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  [Felder]  [Formular-Builder]  [Vorschau]                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─ System-Felder ────────────────────────────────────────────────────┐│
│  │                                                                     ││
│  │  [Anrede]  [Vorname]  [Nachname]  [E-Mail]  [Telefon]              ││
│  │  [Lebenslauf]  [Dokumente]  [Anschreiben]  [Datenschutz]           ││
│  │                                                                     ││
│  │  ℹ️ System-Felder können nicht gelöscht werden                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                         │
│  ┌─ Eigene Felder (Pro) ──────────────────────────────────────────────┐│
│  │                                                                     ││
│  │  [Hobbys]  [Geburtsdatum]  [Gehaltsvorstellung]                    ││
│  │                                                                     ││
│  │  [+ Neues Feld erstellen]                                          ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                         │
│  Klicken Sie auf ein Feld um es zu bearbeiten.                         │
│  Ziehen Sie Felder in den Formular-Builder um sie hinzuzufügen.        │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.5 Tab "Vorschau"

```
┌─────────────────────────────────────────────────────────────────────────┐
│  [Felder]  [Formular-Builder]  [Vorschau]                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─ Vorschau (Draft-Version) ─────────────────────────────────────────┐│
│  │                                                                     ││
│  │  ⚠️ Dies ist eine Vorschau. Änderungen sind noch nicht live.        ││
│  │                                                                     ││
│  │  ┌─────────────────────────────────────────────────────────────┐   ││
│  │  │                    Jetzt bewerben                            │   ││
│  │  │                                                              │   ││
│  │  │  Schritt 1 von 3                              [=====>    ]   │   ││
│  │  │                                                              │   ││
│  │  │  Persönliche Daten                                          │   ││
│  │  │  ┌─────────┐ ┌───────────────┐ ┌───────────────┐            │   ││
│  │  │  │ Anrede ▼│ │ Vorname *     │ │ Nachname *    │            │   ││
│  │  │  └─────────┘ └───────────────┘ └───────────────┘            │   ││
│  │  │  ┌─────────────────────┐ ┌─────────────────────┐            │   ││
│  │  │  │ E-Mail *            │ │ Telefon             │            │   ││
│  │  │  └─────────────────────┘ └─────────────────────┘            │   ││
│  │  │                                                              │   ││
│  │  │                                          [Weiter]            │   ││
│  │  └─────────────────────────────────────────────────────────────┘   ││
│  │                                                                     ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Frontend-Integration

### 5.1 Dynamisches Formular-Rendering

Das Frontend-Formular wird **dynamisch** aus der `form_config` (published) gerendert.

**Ablauf:**
```
1. Job-Seite wird aufgerufen
2. FormRenderService lädt published config
3. Für jeden Step: Render Step-Container
4. Für jedes Feld im Step: Lade FieldDefinition + Render Template
5. Alpine.js initialisiert Interaktivität
```

### 5.2 FormRenderService

```php
<?php
namespace RecruitingPlaybook\Services;

class FormRenderService {

    /**
     * Rendert das komplette Bewerbungsformular
     */
    public function render( int $job_id ): string {
        // Published Config laden
        $config = $this->getPublishedConfig();

        // Fallback auf Standard-Formular
        if ( ! $config ) {
            $config = $this->getDefaultConfig();
        }

        ob_start();

        // Alpine.js Data-Attribut mit Formular-Konfiguration
        echo '<div x-data="applicationForm(' . esc_attr( wp_json_encode( $this->prepareAlpineData( $config, $job_id ) ) ) . ')" x-cloak>';

        // Erfolgs-Meldung
        $this->renderSuccessMessage( $config );

        // Formular
        echo '<template x-if="!submitted"><div>';

        // Error-Anzeige
        $this->renderErrorMessage();

        // Progress-Bar
        $this->renderProgressBar( $config );

        // Form-Tag
        echo '<form @submit.prevent="submit">';

        // Spam-Protection
        echo SpamProtection::getHoneypotField();
        echo SpamProtection::getTimestampField();

        // Steps rendern
        foreach ( $config['steps'] as $index => $step ) {
            $this->renderStep( $step, $index + 1, count( $config['steps'] ) );
        }

        // Navigation
        $this->renderNavigation( $config );

        echo '</form>';
        echo '</div></template>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Rendert einen einzelnen Step
     */
    private function renderStep( array $step, int $step_number, int $total_steps ): void {
        $is_finale = $step['id'] === 'step_finale';

        echo '<div x-show="step === ' . $step_number . '" x-transition>';
        echo '<h3 class="rp-text-lg rp-font-semibold rp-mb-6">' . esc_html( $step['title'] ) . '</h3>';

        if ( ! empty( $step['description'] ) ) {
            echo '<p class="rp-text-gray-600 rp-mb-4">' . esc_html( $step['description'] ) . '</p>';
        }

        // Felder-Grid
        echo '<div class="rp-grid rp-grid-cols-12 rp-gap-4">';

        foreach ( $step['fields'] as $field_config ) {
            $this->renderField( $field_config );
        }

        echo '</div>'; // Grid Ende
        echo '</div>'; // Step Ende
    }

    /**
     * Rendert ein einzelnes Feld
     */
    private function renderField( array $field_config ): void {
        // Feld-Definition laden
        $field = $this->fieldRepository->findByKey( $field_config['field_key'] );

        if ( ! $field || ! $field_config['is_visible'] ) {
            return;
        }

        // Breite bestimmen
        $width_class = $this->getWidthClass( $field_config['width'] ?? 'full' );

        // Overrides anwenden (z.B. custom Label)
        $label = $field_config['overrides']['label'] ?? $field->getLabel();
        $placeholder = $field_config['overrides']['placeholder'] ?? $field->getPlaceholder();

        // Template laden
        $template_file = RP_PLUGIN_DIR . 'templates/fields/field-' . $field->getFieldType() . '.php';

        if ( file_exists( $template_file ) ) {
            echo '<div class="' . esc_attr( $width_class ) . '">';
            include $template_file;
            echo '</div>';
        }
    }
}
```

### 5.3 Feld-Templates

**Beispiel: `templates/fields/field-text.php`**

```php
<?php
/**
 * Text-Feld Template
 *
 * Verfügbare Variablen:
 * - $field: FieldDefinition Model
 * - $field_config: Array mit position, width, is_required, overrides
 * - $label: Finales Label (mit Overrides)
 * - $placeholder: Finaler Platzhalter
 */
defined( 'ABSPATH' ) || exit;

$field_key = $field->getFieldKey();
$is_required = $field_config['is_required'] ?? false;
?>

<div class="rp-form-field">
    <label for="<?php echo esc_attr( $field_key ); ?>" class="rp-label">
        <?php echo esc_html( $label ); ?>
        <?php if ( $is_required ) : ?>
            <span class="rp-text-error">*</span>
        <?php endif; ?>
    </label>

    <input
        type="text"
        id="<?php echo esc_attr( $field_key ); ?>"
        x-model="formData.<?php echo esc_attr( $field_key ); ?>"
        class="rp-input"
        :class="errors.<?php echo esc_attr( $field_key ); ?> ? 'rp-input-error' : ''"
        placeholder="<?php echo esc_attr( $placeholder ); ?>"
        <?php echo $is_required ? 'required' : ''; ?>
    >

    <p
        x-show="errors.<?php echo esc_attr( $field_key ); ?>"
        x-text="errors.<?php echo esc_attr( $field_key ); ?>"
        class="rp-error-text"
    ></p>

    <?php if ( $field->getDescription() ) : ?>
        <p class="rp-help-text"><?php echo esc_html( $field->getDescription() ); ?></p>
    <?php endif; ?>
</div>
```

### 5.4 Alpine.js Integration

```javascript
// assets/src/js/frontend.js

document.addEventListener('alpine:init', () => {
    Alpine.data('applicationForm', (config) => ({
        // State
        step: 1,
        totalSteps: config.steps.length,
        formData: config.initialData || {},
        files: {
            resume: null,
            documents: []
        },
        errors: {},
        loading: false,
        submitted: false,
        error: null,

        // Computed
        get progress() {
            return Math.round((this.step / this.totalSteps) * 100);
        },

        // Methods
        nextStep() {
            if (this.validateCurrentStep()) {
                this.step++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prevStep() {
            this.step--;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        validateCurrentStep() {
            const currentStepConfig = config.steps[this.step - 1];
            this.errors = {};
            let valid = true;

            for (const field of currentStepConfig.fields) {
                if (field.is_required && field.is_visible) {
                    const value = this.formData[field.field_key];
                    if (!value || (typeof value === 'string' && !value.trim())) {
                        this.errors[field.field_key] = config.i18n.required || 'Pflichtfeld';
                        valid = false;
                    }
                }
            }

            return valid;
        },

        async submit() {
            if (!this.validateCurrentStep()) return;

            this.loading = true;
            this.error = null;

            try {
                const formData = new FormData();
                formData.append('job_id', config.jobId);

                // Formular-Daten
                for (const [key, value] of Object.entries(this.formData)) {
                    formData.append(key, value);
                }

                // Dateien
                if (this.files.resume) {
                    formData.append('resume', this.files.resume);
                }
                this.files.documents.forEach((file, i) => {
                    formData.append(`documents[${i}]`, file);
                });

                const response = await fetch(config.submitUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-WP-Nonce': config.nonce
                    }
                });

                const result = await response.json();

                if (result.success) {
                    this.submitted = true;
                } else {
                    this.error = result.message || config.i18n.error;
                }
            } catch (e) {
                this.error = config.i18n.error || 'Ein Fehler ist aufgetreten.';
            } finally {
                this.loading = false;
            }
        },

        // File handling
        handleFileSelect(event, type) {
            const files = event.target.files;
            if (type === 'resume') {
                this.files.resume = files[0] || null;
            } else if (type === 'documents') {
                this.files.documents = [...this.files.documents, ...files].slice(0, 5);
            }
        },

        removeFile(type, index = null) {
            if (type === 'resume') {
                this.files.resume = null;
            } else if (type === 'documents' && index !== null) {
                this.files.documents.splice(index, 1);
            }
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    }));
});
```

---

## 6. REST API

### 6.1 Endpunkte

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/form-builder/config` | Aktuelle Config laden (Draft oder Published) |
| PUT | `/recruiting/v1/form-builder/config` | Config speichern (Draft) |
| POST | `/recruiting/v1/form-builder/publish` | Draft veröffentlichen |
| POST | `/recruiting/v1/form-builder/discard` | Draft verwerfen |
| GET | `/recruiting/v1/form-builder/preview` | Vorschau-Daten (Draft) |
| GET | `/recruiting/v1/fields` | Alle Feld-Definitionen |
| POST | `/recruiting/v1/fields` | Neues Feld erstellen |
| PUT | `/recruiting/v1/fields/{id}` | Feld aktualisieren |
| DELETE | `/recruiting/v1/fields/{id}` | Feld löschen (nur Custom) |

### 6.2 Config-Endpunkt

**GET `/recruiting/v1/form-builder/config`**

Response:
```json
{
  "draft": { /* config_data */ },
  "published": { /* config_data */ },
  "has_unpublished_changes": true,
  "last_published_at": "2026-01-31T10:00:00Z",
  "last_published_by": {
    "id": 1,
    "name": "Admin"
  }
}
```

**PUT `/recruiting/v1/form-builder/config`**

Request:
```json
{
  "steps": [ /* ... */ ],
  "settings": { /* ... */ }
}
```

Response:
```json
{
  "success": true,
  "draft": { /* saved config */ },
  "has_unpublished_changes": true
}
```

### 6.3 Publish-Endpunkt

**POST `/recruiting/v1/form-builder/publish`**

Response:
```json
{
  "success": true,
  "published": { /* config */ },
  "published_at": "2026-01-31T10:30:00Z",
  "has_unpublished_changes": false
}
```

---

## 7. Implementierungsdetails

### 7.1 React-Komponenten (Admin)

```
assets/src/js/admin/form-builder/
├── index.jsx                    # Entry Point
├── FormBuilder.jsx              # Haupt-Container mit Tabs
├── components/
│   ├── FieldLibrary.jsx         # Tab "Felder" - Feld-Bibliothek
│   ├── FormEditor.jsx           # Tab "Formular-Builder" - Step-Editor
│   ├── FormPreview.jsx          # Tab "Vorschau"
│   ├── StepContainer.jsx        # Einzelner Step im Editor
│   ├── FieldItem.jsx            # Feld-Element (draggable)
│   ├── FieldSidebar.jsx         # Sidebar mit Feld-Einstellungen
│   ├── AddFieldModal.jsx        # Modal zum Feld hinzufügen
│   ├── PublishButton.jsx        # Speichern/Veröffentlichen UI
│   └── StatusBadge.jsx          # "Unveröffentlichte Änderungen"
├── hooks/
│   ├── useFormConfig.js         # Config laden/speichern
│   ├── useFieldDefinitions.js   # Feld-Definitionen
│   └── useDragAndDrop.js        # DnD-Logik
└── utils/
    ├── defaultConfig.js         # Standard-Konfiguration
    └── validation.js            # Config-Validierung
```

### 7.2 PHP-Services

```
src/Services/
├── FormConfigService.php        # Config-Verwaltung (Draft/Publish)
├── FormRenderService.php        # Frontend-Rendering
├── FormValidationService.php    # Formular-Validierung bei Submit
└── FieldDefinitionService.php   # Feld-Definitionen (bestehend)
```

### 7.3 Drag & Drop

Verwendung von `@dnd-kit`:

```jsx
import { DndContext, closestCenter } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';

function StepContainer({ step, onFieldsReorder }) {
    const handleDragEnd = (event) => {
        const { active, over } = event;
        if (active.id !== over.id) {
            onFieldsReorder(step.id, active.id, over.id);
        }
    };

    return (
        <DndContext collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
            <SortableContext items={step.fields.map(f => f.field_key)} strategy={verticalListSortingStrategy}>
                {step.fields.map(field => (
                    <SortableFieldItem key={field.field_key} field={field} />
                ))}
            </SortableContext>
        </DndContext>
    );
}
```

---

## 8. Migration von v1

### 8.1 Was bleibt

- `wp_rp_field_definitions` Tabelle (erweitert)
- `FieldDefinition` Model
- `FieldDefinitionRepository`
- `FieldDefinitionService`
- REST API für Felder (`/recruiting/v1/fields`)

### 8.2 Was sich ändert

| Komponente | v1 | v2 |
|------------|----|----|
| UI-Konzept | Einfache Feldliste | Step-basierter Builder |
| Formular-Struktur | Keine (hart kodiert) | `wp_rp_form_config` Tabelle |
| Frontend-Rendering | `single-job_listing.php` (fest) | `FormRenderService` (dynamisch) |
| Templates-Feature | Mehrere Templates | Entfernt (ein Formular) |
| Speichern | Sofort live | Draft/Publish-System |

### 8.3 Migrations-Schritte

1. **Neue Tabelle erstellen**: `wp_rp_form_config`
2. **Standard-Config einfügen**: Default-Formular als Published
3. **FormRenderService implementieren**: Dynamisches Rendering
4. **Frontend-Template anpassen**: `single-job_listing.php` nutzt Service
5. **Admin-UI refactoren**: Neuer Formular-Builder
6. **Templates-Feature entfernen**: `wp_rp_form_templates` nicht mehr nötig
7. **Tests anpassen**: Neue Struktur testen

---

## Anhang

### A. Glossar

| Begriff | Beschreibung |
|---------|--------------|
| **Draft** | Arbeitsversion der Formular-Konfiguration |
| **Published** | Live-Version, die im Frontend angezeigt wird |
| **Step** | Ein Schritt im mehrstufigen Formular |
| **Field Definition** | Stammdaten eines Feldes (Typ, Label, Validierung) |
| **Field Config** | Verwendung eines Feldes im Formular (Position, Required, Overrides) |
| **System-Feld** | Vordefiniertes Feld, nicht löschbar |
| **Custom Field** | Benutzerdefiniertes Feld (Pro) |

### B. Offene Fragen

1. **Job-spezifische Formulare**: Soll jeder Job ein eigenes Formular haben können? (Phase 2?)
2. **Conditional Logic**: Wie tief soll die bedingte Logik gehen? (Phase 2?)
3. **Feld-Vorlagen**: Sollen häufig genutzte Felder als Vorlagen speicherbar sein?

---

*Dokument-Version: 2.0*
*Letzte Aktualisierung: 2026-01-31*

# Form Builder - Ziel-Spezifikation

**Erstellt:** 2025-01-31
**Status:** Geplant
**Bezug:** [Datenfluss-Analyse](./data-flow-analysis.md)

---

## 1. Übersicht

### 1.1 Ziel-Architektur

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         SINGLE SOURCE OF TRUTH                           │
│                                                                          │
│                    ┌─────────────────────────────┐                       │
│                    │      FORM BUILDER           │                       │
│                    │   (rp_form_config)          │                       │
│                    └─────────────────────────────┘                       │
│                                 │                                        │
│            ┌────────────────────┼────────────────────┐                  │
│            ▼                    ▼                    ▼                  │
│    ┌───────────────┐   ┌───────────────┐   ┌───────────────┐           │
│    │   Frontend    │   │    Admin      │   │    E-Mail     │           │
│    │   Formular    │   │   Details     │   │  Platzhalter  │           │
│    └───────────────┘   └───────────────┘   └───────────────┘           │
│                                                                          │
│    • Dynamisch          • Dynamisch          • Garantierte              │
│    • Aus Config         • Aus Config           Pflichtfelder            │
│    • Keine Hardcodes    • Zeigt nur aktive                              │
│                           Felder                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 1.2 Free vs. Pro Version

| Aspekt | Free Version | Pro Version |
|--------|--------------|-------------|
| Form Builder UI | Sichtbar, aber ausgegraut | Voll editierbar |
| Formular-Konfiguration | Standard (3 Steps, fest) | Anpassbar |
| Custom Fields | Nicht verfügbar | Unbegrenzt |
| Steps | 3 (nicht änderbar) | Beliebig viele |
| Frontend-Formular | ✅ Funktioniert | ✅ Funktioniert |
| Bewerbungen empfangen | ✅ Ja | ✅ Ja |

---

## 2. Feld-Kategorien

### 2.1 Pflichtfelder (Nicht entfernbar)

Diese Felder sind **immer** im Formular und können **nicht gelöscht** werden.
Sie haben keinen Löschen-Button (✕), sind aber verschiebbar und konfigurierbar.

| field_key | Label | Typ | Standard-Step | Begründung |
|-----------|-------|-----|---------------|------------|
| `first_name` | Vorname | text | 1 | Email-Platzhalter {vorname} |
| `last_name` | Nachname | text | 1 | Email-Platzhalter {nachname} |
| `email` | E-Mail | email | 1 | Email-Platzhalter {email}, Kontakt |
| `privacy_consent` | Datenschutz | checkbox | Finale | DSGVO-Pflicht |

**Technische Umsetzung:**
```php
// In FieldDefinition oder FormConfigService
const REQUIRED_FIELDS = ['first_name', 'last_name', 'email', 'privacy_consent'];

public function isFieldRemovable(string $field_key): bool {
    return !in_array($field_key, self::REQUIRED_FIELDS, true);
}
```

### 2.2 System-Felder (Hardcodiert, immer dabei)

Diese Felder erscheinen **nicht** in der "Verfügbare Felder"-Liste.
Sie sind fest in bestimmten Steps verankert.

| field_key | Label | Typ | Step | Eigenschaften |
|-----------|-------|-----|------|---------------|
| `file_upload` | Datei-Upload | file | 2 (Dokumente) | Immer vorhanden, konfigurierbar |
| `summary` | Zusammenfassung | summary | Finale | Zeigt alle Eingaben, konfigurierbar |

**Konfigurierbare Eigenschaften:**

**Datei-Upload:**
- Erlaubte Dateitypen (PDF, Word, Bilder)
- Maximale Dateigröße (Standard: 10 MB)
- Maximale Anzahl Dateien (Standard: 5)
- Hilfetext

**Zusammenfassung:**
- Titel
- Layout (1-spaltig / 2-spaltig)
- Zusatztext
- Nur ausgefüllte Felder anzeigen (ja/nein)

### 2.3 Optionale System-Felder

Diese Felder sind standardmäßig verfügbar, können aber entfernt werden.

| field_key | Label | Typ | Kann entfernt werden |
|-----------|-------|-----|---------------------|
| `salutation` | Anrede | select | ✅ Ja |
| `phone` | Telefon | tel | ✅ Ja |
| `message` | Nachricht | textarea | ✅ Ja |

### 2.4 Custom Fields (Pro)

Benutzerdefinierte Felder, erstellt im "Felder"-Tab.

| Eigenschaft | Beschreibung |
|-------------|--------------|
| field_key | Automatisch generiert: `field_{timestamp}` |
| field_type | text, textarea, email, tel, number, date, select, radio, checkbox, url |
| Löschbar | ✅ Ja |
| Verschiebbar | ✅ Ja |

---

## 3. Step-Struktur

### 3.1 Feste Steps

| Step | ID | Name | Löschbar | Besonderheiten |
|------|----|------|----------|----------------|
| 1 | `step_personal` | Persönliche Daten | ❌ Nein | Enthält Pflichtfelder |
| Finale | `step_finale` | Abschluss | ❌ Nein | Enthält Zusammenfassung + Datenschutz |

### 3.2 Optionale Steps

Zwischen Step 1 und Finale können beliebig viele Steps eingefügt werden.

**Standard-Konfiguration (Free + Pro Default):**

```
Step 1: Persönliche Daten (nicht löschbar)
├── Vorname (Pflicht, nicht entfernbar)
├── Nachname (Pflicht, nicht entfernbar)
├── E-Mail (Pflicht, nicht entfernbar)
└── Telefon (optional, entfernbar)

Step 2: Dokumente (löschbar)
├── Nachricht (optional, entfernbar)
└── Datei-Upload (System, immer vorhanden)

Step 3: Abschluss (nicht löschbar, Finale)
├── Zusammenfassung (System, immer vorhanden)
└── Datenschutz (Pflicht, nicht entfernbar)
```

---

## 4. Datenbank-Schema Anpassungen

### 4.1 rp_form_config.config_data (Ziel-Struktur)

```json
{
  "version": 2,
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
      "fields": [
        {
          "field_key": "first_name",
          "is_visible": true,
          "is_required": true,
          "is_removable": false,
          "width": "half",
          "settings": {
            "label": "Vorname",
            "placeholder": "Max"
          }
        },
        {
          "field_key": "last_name",
          "is_visible": true,
          "is_required": true,
          "is_removable": false,
          "width": "half",
          "settings": {
            "label": "Nachname",
            "placeholder": "Mustermann"
          }
        },
        {
          "field_key": "email",
          "is_visible": true,
          "is_required": true,
          "is_removable": false,
          "width": "full",
          "settings": {
            "label": "E-Mail-Adresse",
            "placeholder": "max@beispiel.de"
          }
        },
        {
          "field_key": "phone",
          "is_visible": true,
          "is_required": false,
          "is_removable": true,
          "width": "full",
          "settings": {
            "label": "Telefon",
            "placeholder": "+49..."
          }
        }
      ]
    },
    {
      "id": "step_documents",
      "title": "Dokumente",
      "position": 2,
      "deletable": true,
      "fields": [
        {
          "field_key": "message",
          "is_visible": true,
          "is_required": false,
          "is_removable": true,
          "width": "full",
          "settings": {
            "label": "Anschreiben / Nachricht",
            "placeholder": "Warum möchten Sie bei uns arbeiten?"
          }
        }
      ],
      "system_fields": [
        {
          "field_key": "file_upload",
          "type": "file_upload",
          "settings": {
            "label": "Dokumente hochladen",
            "help_text": "Lebenslauf und weitere Dokumente",
            "allowed_types": ["pdf", "doc", "docx"],
            "max_file_size": 10,
            "max_files": 5
          }
        }
      ]
    },
    {
      "id": "step_finale",
      "title": "Abschluss",
      "position": 999,
      "deletable": false,
      "is_finale": true,
      "fields": [],
      "system_fields": [
        {
          "field_key": "summary",
          "type": "summary",
          "settings": {
            "title": "Ihre Angaben im Überblick",
            "layout": "two-column",
            "additional_text": "Bitte prüfen Sie Ihre Angaben vor dem Absenden.",
            "show_only_filled": false
          }
        },
        {
          "field_key": "privacy_consent",
          "type": "privacy_consent",
          "is_removable": false,
          "settings": {
            "checkbox_text": "Ich habe die {datenschutz_link} gelesen und stimme der Verarbeitung meiner Daten zu.",
            "link_text": "Datenschutzerklärung",
            "privacy_url": "/datenschutz"
          }
        }
      ]
    }
  ]
}
```

### 4.2 Neue Konzepte

**system_fields Array:**
- Felder die nicht aus `rp_field_definitions` kommen
- Hardcodiert pro Step-Typ
- Haben eigene Settings

**is_removable Flag:**
- `true`: Feld kann aus Formular entfernt werden (hat ✕-Button)
- `false`: Feld ist permanent (kein ✕-Button)

---

## 5. Email-Platzhalter

### 5.1 Garantierte Platzhalter

Diese Platzhalter funktionieren **immer**, da die zugehörigen Felder Pflicht sind:

| Platzhalter | Quelle | Feld |
|-------------|--------|------|
| `{vorname}` | rp_candidates.first_name | first_name (Pflicht) |
| `{nachname}` | rp_candidates.last_name | last_name (Pflicht) |
| `{name}` | first_name + last_name | Kombination |
| `{email}` | rp_candidates.email | email (Pflicht) |
| `{anrede}` | rp_candidates.salutation | salutation (optional) |
| `{anrede_formal}` | "Sehr geehrte/r {anrede} {nachname}" | Berechnet |

### 5.2 Bewerbungs-Platzhalter

| Platzhalter | Quelle |
|-------------|--------|
| `{bewerbung_id}` | rp_applications.id |
| `{bewerbung_datum}` | rp_applications.created_at |
| `{bewerbung_status}` | rp_applications.status |

### 5.3 Stellen-Platzhalter

| Platzhalter | Quelle |
|-------------|--------|
| `{stelle}` | wp_posts.post_title (job_listing) |
| `{stelle_ort}` | Job Meta: location |
| `{stelle_typ}` | Job Meta: employment_type |
| `{stelle_url}` | get_permalink(job_id) |

### 5.4 Firmen-Platzhalter

| Platzhalter | Quelle |
|-------------|--------|
| `{firma}` | WordPress Option: rp_company_name |
| `{firma_adresse}` | WordPress Option: rp_company_address |
| `{firma_website}` | WordPress Option: site_url |

---

## 6. UI-Spezifikation

### 6.1 Form Builder Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  ◀ Einstellungen                                    Formular-Builder    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │  [Felder]        [Formular]        [Vorschau]                      │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ┌─────────────────────┐  ┌──────────────────────────────────────────┐ │
│  │ VERFÜGBARE FELDER   │  │  STEP 1: Persönliche Daten        [⚙️]   │ │
│  │                     │  │  ────────────────────────────────────── │ │
│  │ ┌─────────────────┐ │  │                                          │ │
│  │ │ 📝 Telefon      │ │  │  ┌──────────────┐  ┌──────────────┐     │ │
│  │ └─────────────────┘ │  │  │ 🔒 Vorname   │  │ 🔒 Nachname  │     │ │
│  │ ┌─────────────────┐ │  │  │ [⚙️]         │  │ [⚙️]         │     │ │
│  │ │ 💬 Nachricht    │ │  │  └──────────────┘  └──────────────┘     │ │
│  │ └─────────────────┘ │  │                                          │ │
│  │                     │  │  ┌─────────────────────────────────┐    │ │
│  │ ───────────────── │  │  │ 🔒 E-Mail                  [⚙️] │    │ │
│  │ CUSTOM FIELDS      │  │  └─────────────────────────────────┘    │ │
│  │ [+ Neues Feld]     │  │                                          │ │
│  │ ───────────────── │  │  ┌─────────────────────────────────┐    │ │
│  │                     │  │  │ 📝 Telefon            [⚙️] [✕] │    │ │
│  │ ┌─────────────────┐ │  │  └─────────────────────────────────┘    │ │
│  │ │ 📊 Erfahrung    │ │  │                                          │ │
│  │ └─────────────────┘ │  │  ┌──────────────────────────────────┐   │ │
│  │                     │  │  │ + Feld hierher ziehen             │   │ │
│  │                     │  │  └──────────────────────────────────┘   │ │
│  │                     │  │                                          │ │
│  │                     │  ├──────────────────────────────────────────┤ │
│  │                     │  │                                          │ │
│  │                     │  │  STEP 2: Dokumente          [⚙️] [🗑]   │ │
│  │                     │  │  ...                                     │ │
│  │                     │  │                                          │ │
│  │                     │  ├──────────────────────────────────────────┤ │
│  │                     │  │                                          │ │
│  │                     │  │  STEP 3: Abschluss (Finale)      [⚙️]   │ │
│  │                     │  │  ...                                     │ │
│  │                     │  │                                          │ │
│  └─────────────────────┘  └──────────────────────────────────────────┘ │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │  [+ Step hinzufügen]                                               │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│  ⚠️ Änderungen        [Änderungen verwerfen]     [Veröffentlichen]     │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Feld-Markierungen

| Symbol | Bedeutung | Aktionen |
|--------|-----------|----------|
| 🔒 | Pflichtfeld (nicht entfernbar) | Verschieben, Einstellungen |
| 📄 | System-Feld | Einstellungen (Position fest) |
| 📝 | Optionales Feld | Verschieben, Einstellungen, Löschen |
| [⚙️] | Einstellungen öffnen | - |
| [✕] | Feld entfernen | Nur bei optionalen Feldern |
| [🗑] | Step löschen | Nur bei optionalen Steps |

### 6.3 Free Version (Ausgegraut)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  ╔════════════════════════════════════════════════════════════════════╗ │
│  ║                                                                    ║ │
│  ║   🔒 FORMULAR-BUILDER (PRO)                                       ║ │
│  ║                                                                    ║ │
│  ║   In der kostenlosen Version ist das Standard-Formular aktiv.     ║ │
│  ║   Upgrade auf Pro um das Formular anzupassen.                     ║ │
│  ║                                                                    ║ │
│  ║                        [Jetzt upgraden]                           ║ │
│  ║                                                                    ║ │
│  ╚════════════════════════════════════════════════════════════════════╝ │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │  [Felder]        [Formular]        [Vorschau]                      │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                         (Ausgegraut)                             │   │
│  │                                                                   │   │
│  │   Das Standard-Formular wird angezeigt, aber alle Elemente       │   │
│  │   sind deaktiviert. Drag & Drop funktioniert nicht.              │   │
│  │                                                                   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Validierung

### 7.1 Speicher-Validierung (FormConfigService)

Beim Speichern der Konfiguration müssen folgende Regeln erfüllt sein:

```php
public function validateConfig(array $config): bool|WP_Error {

    // 1. Pflichtfelder müssen vorhanden und sichtbar sein
    $required_fields = ['first_name', 'last_name', 'email', 'privacy_consent'];
    $visible_fields = $this->getVisibleFieldKeys($config);

    foreach ($required_fields as $field) {
        if (!in_array($field, $visible_fields)) {
            return new WP_Error(
                'missing_required_field',
                sprintf('Das Feld "%s" ist erforderlich.', $field)
            );
        }
    }

    // 2. Mindestens ein Step
    if (empty($config['steps'])) {
        return new WP_Error('no_steps', 'Mindestens ein Step erforderlich.');
    }

    // 3. Finale-Step muss vorhanden sein
    $has_finale = false;
    foreach ($config['steps'] as $step) {
        if (!empty($step['is_finale'])) {
            $has_finale = true;
            break;
        }
    }

    if (!$has_finale) {
        return new WP_Error('no_finale', 'Ein Abschluss-Step ist erforderlich.');
    }

    // 4. Jeder Step braucht ID und Titel
    foreach ($config['steps'] as $index => $step) {
        if (empty($step['id']) || empty($step['title'])) {
            return new WP_Error('invalid_step', "Step {$index} ist ungültig.");
        }
    }

    return true;
}
```

### 7.2 Fehlermeldungen

| Fehler | Meldung |
|--------|---------|
| Pflichtfeld fehlt | "Das Feld 'Vorname' ist erforderlich und kann nicht entfernt werden." |
| Kein Finale-Step | "Ein Abschluss-Step ist erforderlich." |
| Step ohne Titel | "Bitte geben Sie einen Titel für Step X ein." |

---

## 8. Migration

### 8.1 Von aktueller zu neuer Struktur

```php
// Migration: config_data v1 → v2
public function migrateConfig(array $config): array {

    if (($config['version'] ?? 1) >= 2) {
        return $config;
    }

    // System-Felder hinzufügen
    foreach ($config['steps'] as &$step) {

        // Dokumente-Step: file_upload hinzufügen
        if ($step['id'] === 'step_documents') {
            $step['system_fields'] = [
                [
                    'field_key' => 'file_upload',
                    'type' => 'file_upload',
                    'settings' => [
                        'max_file_size' => 10,
                        'max_files' => 5,
                        'allowed_types' => ['pdf', 'doc', 'docx']
                    ]
                ]
            ];
        }

        // Finale-Step: summary hinzufügen
        if (!empty($step['is_finale'])) {
            $step['system_fields'] = [
                [
                    'field_key' => 'summary',
                    'type' => 'summary',
                    'settings' => [
                        'layout' => 'two-column'
                    ]
                ],
                [
                    'field_key' => 'privacy_consent',
                    'type' => 'privacy_consent',
                    'is_removable' => false,
                    'settings' => [
                        'privacy_url' => '/datenschutz'
                    ]
                ]
            ];

            // privacy_consent aus fields entfernen (jetzt in system_fields)
            $step['fields'] = array_filter($step['fields'], function($f) {
                return $f['field_key'] !== 'privacy_consent';
            });
        }

        // is_removable Flag hinzufügen
        foreach ($step['fields'] as &$field) {
            $field['is_removable'] = !in_array(
                $field['field_key'],
                ['first_name', 'last_name', 'email']
            );
        }
    }

    $config['version'] = 2;
    return $config;
}
```

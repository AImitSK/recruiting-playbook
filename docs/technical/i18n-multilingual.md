# Mehrsprachigkeit (i18n / l10n)

## Übersicht

Das Plugin muss Mehrsprachigkeit auf drei Ebenen unterstützen:

```
┌─────────────────────────────────────────────────────────────────┐
│                    MEHRSPRACHIGKEIT                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   BACKEND    │  │   FRONTEND   │  │   INHALTE    │          │
│  │   (Admin)    │  │  (Website)   │  │   (Stellen)  │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                 │                 │                   │
│  Plugin-UI in      Formulare,        Stellenanzeigen           │
│  Sprache des       Templates,        in mehreren               │
│  Nutzers           Buttons           Sprachen                  │
│         │                 │                 │                   │
│         ▼                 ▼                 ▼                   │
│  WordPress i18n    WordPress i18n    WPML / Polylang           │
│  .po/.mo Dateien   .po/.mo Dateien   Integration               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 1. Backend-Übersetzung (Admin-UI)

### Anforderung

- Disponenten in Deutschland arbeiten auf Deutsch
- Niederlassung in Polen braucht polnische UI
- Internationale Teams brauchen Englisch

### Umsetzung: WordPress Standard i18n

Alle Plugin-Strings werden übersetzbar gemacht:

```php
// Statt:
echo "Neue Stelle erstellen";

// So:
echo __('Neue Stelle erstellen', 'recruiting-playbook');

// Mit Platzhaltern:
echo sprintf(
    __('%d Bewerbungen eingegangen', 'recruiting-playbook'),
    $count
);

// Plural:
echo _n(
    '%d Bewerbung',
    '%d Bewerbungen',
    $count,
    'recruiting-playbook'
);
```

### Übersetzungsdateien

```
recruiting-playbook/
└── languages/
    ├── recruiting-playbook.pot          # Template (Quelle)
    ├── recruiting-playbook-de_DE.po     # Deutsch
    ├── recruiting-playbook-de_DE.mo     # Deutsch (kompiliert)
    ├── recruiting-playbook-en_US.po     # Englisch
    ├── recruiting-playbook-en_US.mo
    ├── recruiting-playbook-pl_PL.po     # Polnisch
    ├── recruiting-playbook-pl_PL.mo
    ├── recruiting-playbook-fr_FR.po     # Französisch
    ├── recruiting-playbook-fr_FR.mo
    ├── recruiting-playbook-nl_NL.po     # Niederländisch
    ├── recruiting-playbook-nl_NL.mo
    └── recruiting-playbook-uk_UA.po     # Ukrainisch (Pflege!)
        recruiting-playbook-uk_UA.mo
```

### Prioritäre Sprachen

| Sprache | Code | Priorität | Grund |
|---------|------|-----------|-------|
| Deutsch | de_DE | P0 | Hauptmarkt |
| Englisch | en_US | P0 | International, Fallback |
| Polnisch | pl_PL | P1 | Viele Pflegekräfte aus PL |
| Ukrainisch | uk_UA | P1 | Aktuelle Zuwanderung Pflege |
| Französisch | fr_FR | P2 | Schweiz, Frankreich |
| Niederländisch | nl_NL | P2 | Niederlande, Belgien |
| Rumänisch | ro_RO | P2 | Pflegekräfte aus RO |
| Türkisch | tr_TR | P3 | Deutschland |

### JavaScript-Strings

Für React/JS-Komponenten im Admin:

```javascript
// wp_localize_script oder wp_set_script_translations
const { __ } = wp.i18n;

const MyComponent = () => (
    <button>
        {__('Speichern', 'recruiting-playbook')}
    </button>
);
```

```php
// In PHP registrieren:
wp_set_script_translations(
    'recruiting-playbook-admin',
    'recruiting-playbook',
    plugin_dir_path(__FILE__) . 'languages'
);
```

---

## 2. Frontend-Übersetzung (Website)

### Übersetzbare Elemente

| Element | Beispiel DE | Beispiel EN |
|---------|-------------|-------------|
| Formular-Labels | "Vorname" | "First Name" |
| Buttons | "Jetzt bewerben" | "Apply Now" |
| Validierung | "Bitte E-Mail eingeben" | "Please enter email" |
| Status-Meldungen | "Bewerbung gesendet" | "Application submitted" |
| Filter | "Alle Standorte" | "All Locations" |
| Pagination | "Seite 1 von 5" | "Page 1 of 5" |

### Shortcode mit Sprachparameter

```php
// Automatisch (WordPress-Sprache):
[recruiting_jobs]

// Explizit:
[recruiting_jobs lang="en"]

// Für WPML/Polylang: automatische Erkennung
[recruiting_jobs] // Zeigt Jobs der aktuellen Sprache
```

### Template-Strings

```php
// templates/job-listing.php
<div class="job-card">
    <span class="job-type">
        <?php echo esc_html__('Vollzeit', 'recruiting-playbook'); ?>
    </span>
    <a href="<?php echo $apply_url; ?>" class="apply-btn">
        <?php echo esc_html__('Jetzt bewerben', 'recruiting-playbook'); ?>
    </a>
</div>
```

---

## 3. Inhalts-Übersetzung (Stellenanzeigen)

### Anforderung

Ein Unternehmen möchte dieselbe Stelle in mehreren Sprachen veröffentlichen:
- Pflegefachkraft (m/w/d) → Deutsch
- Nurse (m/f/d) → Englisch  
- Pielęgniarka (m/k/d) → Polnisch

### Lösung: WPML / Polylang Integration

Das Plugin nutzt **keine eigene Übersetzungslogik**, sondern integriert sich mit den etablierten WordPress-Lösungen:

#### WPML-Kompatibilität

```php
// Custom Post Type als übersetzbar registrieren
add_filter('wpml_is_translated_post_type', function($value, $post_type) {
    if ($post_type === 'job_listing') {
        return true;
    }
    return $value;
}, 10, 2);

// Custom Taxonomies
add_filter('wpml_is_translated_taxonomy', function($value, $taxonomy) {
    if (in_array($taxonomy, ['job_category', 'job_location'])) {
        return true;
    }
    return $value;
}, 10, 2);
```

WPML-Konfigurationsdatei `wpml-config.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<wpml-config>
    <custom-types>
        <custom-type translate="1">job_listing</custom-type>
        <custom-type translate="1">application</custom-type>
    </custom-types>
    
    <taxonomies>
        <taxonomy translate="1">job_category</taxonomy>
        <taxonomy translate="1">job_location</taxonomy>
        <taxonomy translate="1">employment_type</taxonomy>
    </taxonomies>
    
    <custom-fields>
        <custom-field action="translate">_job_description</custom-field>
        <custom-field action="translate">_job_requirements</custom-field>
        <custom-field action="translate">_job_benefits</custom-field>
        <custom-field action="copy">_job_salary_min</custom-field>
        <custom-field action="copy">_job_salary_max</custom-field>
        <custom-field action="copy">_job_application_deadline</custom-field>
    </custom-fields>
    
    <admin-texts>
        <key name="recruiting_playbook_settings">
            <key name="company_name" />
            <key name="default_email_footer" />
        </key>
    </admin-texts>
</wpml-config>
```

#### Polylang-Kompatibilität

```php
// Custom Post Types registrieren
add_filter('pll_get_post_types', function($post_types) {
    $post_types['job_listing'] = 'job_listing';
    return $post_types;
});

// Taxonomies registrieren
add_filter('pll_get_taxonomies', function($taxonomies) {
    $taxonomies['job_category'] = 'job_category';
    $taxonomies['job_location'] = 'job_location';
    return $taxonomies;
});
```

### Workflow für mehrsprachige Stellen

```
┌─────────────────────────────────────────────────────────────────┐
│ Stelle bearbeiten: Pflegefachkraft (m/w/d)                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Sprache: 🇩🇪 Deutsch ▼                                         │
│                                                                 │
│  Übersetzungen:                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 🇩🇪 Deutsch    ✓ Original    [Bearbeiten]              │   │
│  │ 🇬🇧 Englisch   ✓ Übersetzt   [Bearbeiten]              │   │
│  │ 🇵🇱 Polnisch   ○ Fehlt       [+ Übersetzen]            │   │
│  │ 🇺🇦 Ukrainisch ○ Fehlt       [+ Übersetzen]            │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  💡 Tipp: Mit AI-Addon können Übersetzungen automatisch        │
│     erstellt werden.                                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### AI-Addon: Automatische Übersetzung (Phase 3+)

```
┌─────────────────────────────────────────────────────────────────┐
│ 🤖 KI-Übersetzung                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Quellsprache: 🇩🇪 Deutsch                                      │
│  Zielsprachen:                                                  │
│    ☑ 🇬🇧 Englisch                                               │
│    ☑ 🇵🇱 Polnisch                                               │
│    ☐ 🇺🇦 Ukrainisch                                             │
│                                                                 │
│  Optionen:                                                      │
│    ○ Wörtlich übersetzen                                       │
│    ● An Zielmarkt anpassen (empfohlen)                         │
│                                                                 │
│  [Übersetzen] [Vorschau]                                       │
│                                                                 │
│  ⚠️ Übersetzungen werden als Entwurf gespeichert.              │
│     Bitte vor Veröffentlichung prüfen.                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Bewerbungsformulare

### Sprache des Formulars

Das Formular erscheint automatisch in der Sprache der Stellenanzeige:

```php
// Formular-Sprache ermitteln
function get_application_form_locale($job_id) {
    // WPML
    if (function_exists('wpml_get_language_information')) {
        $lang_info = wpml_get_language_information(null, $job_id);
        return $lang_info['locale'];
    }
    
    // Polylang
    if (function_exists('pll_get_post_language')) {
        return pll_get_post_language($job_id, 'locale');
    }
    
    // Fallback: WordPress-Sprache
    return get_locale();
}
```

### Mehrsprachige Formular-Felder

Custom Fields können pro Sprache unterschiedliche Labels haben:

```
Einstellungen → Formulare → Feldbezeichnungen

┌─────────────────────────────────────────────────────────────────┐
│ Feld: Frühester Eintrittstermin                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  🇩🇪 Deutsch:     [Frühester Eintrittstermin        ]          │
│  🇬🇧 Englisch:    [Earliest Start Date              ]          │
│  🇵🇱 Polnisch:    [Najwcześniejsza data rozpoczęcia ]          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### E-Mail-Vorlagen pro Sprache

```
Einstellungen → E-Mails → Eingangsbestätigung

┌─────────────────────────────────────────────────────────────────┐
│ Sprache: 🇩🇪 Deutsch ▼                                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Betreff:                                                       │
│  [Ihre Bewerbung bei {company_name}                  ]          │
│                                                                 │
│  Inhalt:                                                        │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Guten Tag {candidate_name},                             │   │
│  │                                                          │   │
│  │ vielen Dank für Ihre Bewerbung als {job_title}.         │   │
│  │ Wir haben Ihre Unterlagen erhalten und werden uns       │   │
│  │ zeitnah bei Ihnen melden.                               │   │
│  │                                                          │   │
│  │ Mit freundlichen Grüßen                                 │   │
│  │ {company_name}                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  [Speichern]  [Von andererer Sprache kopieren ▼]               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. API & Mehrsprachigkeit

### Jobs nach Sprache filtern

```
GET /wp-json/recruiting/v1/jobs?lang=de
GET /wp-json/recruiting/v1/jobs?lang=en
GET /wp-json/recruiting/v1/jobs?lang=pl
```

### Job mit allen Übersetzungen

```
GET /wp-json/recruiting/v1/jobs/123?include_translations=true
```

**Response:**

```json
{
  "id": 123,
  "title": "Pflegefachkraft (m/w/d)",
  "language": "de",
  "translations": {
    "en": {
      "id": 456,
      "title": "Nurse (m/f/d)",
      "url": "https://example.com/en/jobs/nurse/"
    },
    "pl": {
      "id": 789,
      "title": "Pielęgniarka (m/k/d)",
      "url": "https://example.com/pl/jobs/pielegniarka/"
    }
  }
}
```

### Bewerbung mit Sprachkontext

```json
{
  "id": 456,
  "job_id": 123,
  "application_language": "pl",
  "candidate": {
    "first_name": "Anna",
    "last_name": "Kowalska"
  }
}
```

---

## 6. RTL-Support (Right-to-Left)

Für arabische oder hebräische Benutzer (falls später relevant):

```css
/* Admin CSS */
.rtl .recruiting-dashboard {
    direction: rtl;
    text-align: right;
}

.rtl .recruiting-kanban {
    flex-direction: row-reverse;
}
```

```php
// RTL-Stylesheet laden
if (is_rtl()) {
    wp_enqueue_style(
        'recruiting-playbook-rtl',
        plugin_dir_url(__FILE__) . 'assets/css/admin-rtl.css'
    );
}
```

---

## 7. Übersetzungs-Workflow

### Für Plugin-Entwicklung

1. **Strings extrahieren:**
   ```bash
   wp i18n make-pot . languages/recruiting-playbook.pot
   ```

2. **Übersetzen:**
   - Manuell mit Poedit
   - Oder: translate.wordpress.org (wenn auf .org veröffentlicht)
   - Oder: Professionelle Übersetzer

3. **Kompilieren:**
   ```bash
   wp i18n make-mo languages/
   ```

### Für Kunden (Inhalte)

```
Empfohlener Workflow:

1. Stelle auf Deutsch erstellen
2. WPML/Polylang: "Übersetzen" klicken
3. Manuell übersetzen ODER AI-Addon nutzen
4. Review durch Muttersprachler
5. Veröffentlichen
```

---

## 8. Feature-Matrix: Mehrsprachigkeit

| Feature | FREE | PRO | AI-ADDON |
|---------|:----:|:---:|:--------:|
| Backend-UI übersetzt | ✅ | ✅ | ✅ |
| Frontend-UI übersetzt | ✅ | ✅ | ✅ |
| WPML-Kompatibilität | ✅ | ✅ | ✅ |
| Polylang-Kompatibilität | ✅ | ✅ | ✅ |
| E-Mail-Templates pro Sprache | ❌ | ✅ | ✅ |
| Custom Fields pro Sprache | ❌ | ✅ | ✅ |
| API: Sprach-Filter | ❌ | ✅ | ✅ |
| KI-Übersetzung | ❌ | ❌ | ✅ |

---

## 9. Priorisierung

### Phase 1 (MVP)

- [x] Alle Strings mit `__()` / `_e()` versehen
- [ ] .pot-Datei erstellen
- [ ] Deutsche Übersetzung (Basis)
- [ ] Englische Übersetzung
- [ ] WPML `wpml-config.xml`
- [ ] Polylang-Filter

### Phase 2 (Pro)

- [ ] Polnische Übersetzung
- [ ] Ukrainische Übersetzung
- [ ] E-Mail-Templates pro Sprache
- [ ] API-Sprach-Parameter
- [ ] JavaScript-Übersetzungen

### Phase 3 (AI-Addon)

- [ ] KI-Übersetzung von Stellen
- [ ] Weitere Sprachen nach Bedarf
- [ ] RTL-Support (falls Nachfrage)

---

## 10. Testing

### Checkliste

- [ ] Backend-UI in allen Sprachen testen
- [ ] Frontend mit WPML testen
- [ ] Frontend mit Polylang testen
- [ ] Formular-Validierung in allen Sprachen
- [ ] E-Mail-Versand in korrekter Sprache
- [ ] API mit `lang`-Parameter
- [ ] Fallback wenn Übersetzung fehlt
- [ ] Datumsformate (DE: 21.01.2025 vs EN: 01/21/2025)
- [ ] Zahlenformate (DE: 3.200,00 € vs EN: €3,200.00)

---

*Letzte Aktualisierung: Januar 2025*

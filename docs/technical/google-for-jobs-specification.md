# Google for Jobs – Spezifikation

> **Status:** Implementiert (Februar 2026)
> **Tier:** Free (in allen Plänen verfügbar)
> **Datei:** `plugin/src/Frontend/JobSchema.php`

---

## 1. Übersicht

Google for Jobs ist eine Funktion der Google-Suche, die Stellenanzeigen prominent
in einem eigenen Bereich der Suchergebnisse anzeigt. Die Integration erfolgt über
**JSON-LD Structured Data** (`JobPosting` Schema) im `<head>` jeder Stellenseite.

### Vorteile
- **Kostenlose Reichweite** – keine Kosten pro Klick
- **Prominente Platzierung** – eigener Bereich über den organischen Ergebnissen
- **Automatische Indexierung** – Google crawlt das Schema automatisch
- **Höhere CTR** – strukturierte Darstellung mit Gehalt, Standort, etc.

---

## 2. Technische Implementierung

### 2.1 Schema-Ausgabe

Das JSON-LD Schema wird über den `wp_head` Hook auf allen `job_listing` Einzelseiten
ausgegeben (Priority 5, damit es früh im `<head>` steht).

```php
// JobSchema.php
add_action( 'wp_head', [ $this, 'outputSchema' ], 5 );
```

### 2.2 Schema-Struktur

Vollständiges Beispiel der generierten JSON-LD Ausgabe:

```json
{
  "@context": "https://schema.org/",
  "@type": "JobPosting",
  "title": "Pflegefachkraft (m/w/d)",
  "description": "<p>Wir suchen eine engagierte Pflegefachkraft...</p>",
  "datePosted": "2026-02-10T10:00:00+00:00",
  "validThrough": "2026-03-31T23:59:59+00:00",
  "identifier": {
    "@type": "PropertyValue",
    "name": "Pflegedienst Muster GmbH",
    "value": "job-42"
  },
  "hiringOrganization": {
    "@type": "Organization",
    "name": "Pflegedienst Muster GmbH",
    "sameAs": "https://example.com",
    "logo": "https://example.com/wp-content/uploads/logo.png"
  },
  "employmentType": "FULL_TIME",
  "jobLocation": {
    "@type": "Place",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Berlin",
      "addressCountry": "DE"
    }
  },
  "jobLocationType": "TELECOMMUTE",
  "baseSalary": {
    "@type": "MonetaryAmount",
    "currency": "EUR",
    "value": {
      "@type": "QuantitativeValue",
      "unitText": "MONTH",
      "minValue": 3200,
      "maxValue": 4000
    }
  },
  "directApply": true
}
```

### 2.3 Feld-Mapping

| Schema-Feld | Datenquelle | Pflicht |
|-------------|-------------|---------|
| `title` | Post Title | Ja |
| `description` | Post Content (HTML, wp_kses_post) | Ja |
| `datePosted` | Post Date (ISO 8601) | Ja |
| `identifier` | Firmenname + `job-{ID}` | Ja |
| `hiringOrganization` | `rp_settings.company_name` / Blogname + Custom Logo | Ja |
| `validThrough` | `_rp_application_deadline` Meta | Nein* |
| `employmentType` | `employment_type` Taxonomy | Nein* |
| `jobLocation` | `job_location` Taxonomy | Nein* |
| `jobLocationType` | `_rp_remote_option` Meta (`full` = TELECOMMUTE) | Nein* |
| `baseSalary` | `_rp_salary_min/max/currency/period` Meta | Nein* |
| `directApply` | Immer `true` (Formular auf der Seite) | Nein |

*\*Empfohlen für besseres Ranking*

### 2.4 Beschäftigungsart-Mapping

Deutsche Taxonomy-Slugs werden auf Google Schema-Werte gemappt:

| Taxonomy-Slug | Google Schema |
|---------------|---------------|
| `vollzeit` | `FULL_TIME` |
| `teilzeit` | `PART_TIME` |
| `minijob` | `PART_TIME` |
| `ausbildung` | `INTERN` |
| `praktikum` | `INTERN` |
| `werkstudent` | `PART_TIME` |
| `freiberuflich` | `CONTRACTOR` |

Mehrere Beschäftigungsarten werden als Array ausgegeben (Duplikate entfernt).

### 2.5 Gehalt-Mapping

| Meta-Feld | Schema-Feld |
|-----------|-------------|
| `_rp_salary_min` | `value.minValue` |
| `_rp_salary_max` | `value.maxValue` |
| `_rp_salary_currency` | `currency` (Default: EUR) |
| `_rp_salary_period` | `value.unitText` (hour→HOUR, month→MONTH, year→YEAR) |
| `_rp_hide_salary` | Wenn `true`, wird `baseSalary` nicht ausgegeben |

---

## 3. Settings-Integration

### 3.1 Toggle im Integrationen-Tab

Google for Jobs kann über den Settings-Tab "Integrationen" gesteuert werden:

| Setting | Option-Key | Default | Beschreibung |
|---------|-----------|---------|--------------|
| Aktiviert | `google_jobs_enabled` | `true` | Schema-Ausgabe ein/aus |
| Gehalt anzeigen | `google_jobs_show_salary` | `true` | Gehalt im Schema |
| Remote kennzeichnen | `google_jobs_show_remote` | `true` | TELECOMMUTE im Schema |
| Bewerbungsfrist | `google_jobs_show_deadline` | `true` | validThrough im Schema |

Settings werden in `rp_integrations` Option gespeichert.

### 3.2 Abwärtskompatibilität

Die bestehende `rp_settings.enable_schema` Option wird weiterhin berücksichtigt.
Prüfreihenfolge:
1. `rp_integrations.google_jobs_enabled` (neue Option)
2. `rp_settings.enable_schema` (Legacy-Fallback)

---

## 4. Schema-Validierung

### 4.1 Programmatische Validierung

Die `JobSchema::validateSchema()` Methode prüft einzelne Jobs:

**Pflichtfeld-Fehler (errors):**
- Stellentitel fehlt
- Stellenbeschreibung fehlt
- Unternehmensname fehlt
- Bewerbungsfrist abgelaufen

**Warnungen (warnings):**
- Stellenbeschreibung < 100 Zeichen
- Kein Standort und kein Remote
- Keine Beschäftigungsart
- Kein Gehalt angegeben
- Keine Bewerbungsfrist

### 4.2 Batch-Validierung

`validateAllJobs()` validiert alle veröffentlichten Stellen und gibt
pro Job Titel, Fehler, Warnungen und Edit-Link zurück.

### 4.3 Externe Validierung

Zur Überprüfung der korrekten Ausgabe:
- **Google Rich Results Test:** https://search.google.com/test/rich-results
- **Schema.org Validator:** https://validator.schema.org/
- **Google Search Console:** Indexierungs-Status unter "Job Posting"

---

## 5. Datei-Übersicht

| Datei | Beschreibung |
|-------|-------------|
| `src/Frontend/JobSchema.php` | Schema-Generator + Validierung |
| `src/Api/IntegrationController.php` | REST-Endpoint für Settings |
| `assets/src/js/admin/settings/components/IntegrationSettings.jsx` | UI-Komponente |
| `assets/src/js/admin/settings/hooks/useIntegrations.js` | Settings-Hook |

---

## 6. Referenzen

- [Google Structured Data: JobPosting](https://developers.google.com/search/docs/appearance/structured-data/job-posting)
- [Schema.org: JobPosting](https://schema.org/JobPosting)
- [Google Rich Results Test](https://search.google.com/test/rich-results)

---

*Erstellt: 10. Februar 2026*

# XML Job Feed – Spezifikation

> **Status:** Implementiert (Februar 2026)
> **Tier:** Free (in allen Plänen verfügbar)
> **Datei:** `plugin/src/Integrations/Feed/XmlJobFeed.php`

---

## 1. Übersicht

Der XML Job Feed stellt alle veröffentlichten Stellenanzeigen als standardisierten
XML-Feed bereit. Jobbörsen wie Jooble, Talent.com, Adzuna und regionale Portale
können diesen Feed automatisch einlesen und die Stellen auf ihren Plattformen listen.

### Vorteile
- **Universelle Kompatibilität** – ein Feed für alle Jobbörsen
- **Automatische Aktualisierung** – neue/geänderte Stellen sofort verfügbar
- **Kein manuelles Einstellen** – Stellen erscheinen automatisch
- **Kostenlos** – in der Free-Version enthalten

### Feed-URL

```
https://example.com/feed/jobs/
```

---

## 2. XML-Format

Das Format orientiert sich an der Jooble-Spezifikation und ist kompatibel mit
den meisten Jobbörsen-Aggregatoren.

### 2.1 Vollständiges Beispiel

```xml
<?xml version="1.0" encoding="UTF-8"?>
<jobs>
  <job>
    <id>52</id>
    <link>https://example.com/stelle/pflegefachkraft-m-w-d-innere-medizin/</link>
    <name>Pflegefachkraft (m/w/d) – Innere Medizin</name>
    <region>Berlin</region>
    <description><![CDATA[<h2>Ihre Aufgaben</h2>...]]></description>
    <pubdate>2026-02-10</pubdate>
    <updated>2026-02-10</updated>
    <expire>2026-04-30</expire>
    <jobtype>full-time</jobtype>
    <salary>3.400–4.200 EUR/Monat</salary>
    <salary_min>3400</salary_min>
    <salary_max>4200</salary_max>
    <salary_currency>EUR</salary_currency>
    <company>Pflegedienst Muster GmbH</company>
    <category>Pflege</category>
    <remote>no</remote>
    <contact_email>bewerbung@example.com</contact_email>
  </job>
  <!-- ... weitere Stellen ... -->
</jobs>
```

### 2.2 Feld-Referenz

| XML-Feld | Datenquelle | Pflicht | Beschreibung |
|----------|-------------|---------|--------------|
| `id` | Post ID | Ja | Eindeutige Stellen-ID |
| `link` | Permalink | Ja | URL zur Stellenanzeige |
| `name` | Post Title | Ja | Stellentitel |
| `region` | `job_location` Taxonomy | Ja* | Standort |
| `description` | Post Content | Ja | Beschreibung (HTML in CDATA oder Plain Text) |
| `pubdate` | Post Date | Ja | Veröffentlichungsdatum (YYYY-MM-DD) |
| `updated` | Post Modified | Ja | Letzte Änderung (YYYY-MM-DD) |
| `expire` | `_rp_application_deadline` | Nein | Bewerbungsfrist (YYYY-MM-DD) |
| `jobtype` | `employment_type` Taxonomy | Nein | full-time, part-time, internship, freelance |
| `salary` | `_rp_salary_min/max` | Nein | Formatierte Gehaltsspanne |
| `salary_min` | `_rp_salary_min` | Nein | Mindestgehalt (numerisch) |
| `salary_max` | `_rp_salary_max` | Nein | Maximalgehalt (numerisch) |
| `salary_currency` | `_rp_salary_currency` | Nein | Währung (EUR, CHF, etc.) |
| `company` | `rp_settings.company_name` | Ja | Firmenname |
| `category` | `job_category` Taxonomy | Nein | Stellenkategorie |
| `remote` | `_rp_remote_option` | Nein | full, hybrid, no |
| `contact_email` | `_rp_contact_email` | Nein | Kontakt-E-Mail |

*\*Wenn kein Standort vorhanden, wird "Remote" ausgegeben bei Remote-Stellen*

### 2.3 Jobtype-Mapping

| Taxonomy-Slug | XML-Wert |
|---------------|----------|
| `vollzeit` | `full-time` |
| `teilzeit` | `part-time` |
| `minijob` | `part-time` |
| `ausbildung` | `internship` |
| `praktikum` | `internship` |
| `werkstudent` | `part-time` |
| `freiberuflich` | `freelance` |

---

## 3. Settings-Integration

### 3.1 Einstellungen im Integrationen-Tab

| Setting | Option-Key | Default | Beschreibung |
|---------|-----------|---------|--------------|
| Aktiviert | `xml_feed_enabled` | `true` | Feed ein/aus |
| Gehalt anzeigen | `xml_feed_show_salary` | `true` | Gehalt im Feed |
| HTML-Beschreibung | `xml_feed_html_description` | `true` | HTML (CDATA) oder Plain Text |
| Max. Stellen | `xml_feed_max_items` | `50` | Limit (1–500) |

Settings werden in `rp_integrations` Option gespeichert.

### 3.2 Feed-URL in der UI

Die IntegrationSettings-Komponente zeigt die Feed-URL mit Copy-Button an:
```
Feed-URL: https://example.com/feed/jobs/    [Kopieren]
```

---

## 4. Technische Implementierung

### 4.1 WordPress Custom Feed

Der Feed wird über `add_feed()` als WordPress Custom Feed registriert:

```php
add_feed( 'jobs', [ $this, 'render' ] );
```

Dies erzeugt die URL `/feed/jobs/`. WordPress kümmert sich um Rewrite Rules.

### 4.2 Caching

- **Transient-Cache:** `rp_xml_job_feed` mit 1 Stunde TTL
- Cache wird bei Post-Änderungen (`save_post_job_listing`) invalidiert
- `Content-Type: application/xml; charset=UTF-8`

### 4.3 Query

```php
$query = [
    'post_type'      => 'job_listing',
    'post_status'    => 'publish',
    'posts_per_page' => $max_items,  // aus Settings (Default: 50)
    'orderby'        => 'date',
    'order'          => 'DESC',
];
```

### 4.4 Deaktivierung

Wenn `xml_feed_enabled` = false:
- Feed-Route gibt 404 zurück
- Feed-URL wird in der UI nicht angezeigt

---

## 5. Kompatible Jobbörsen

| Jobbörse | Kompatibel | Anmerkung |
|----------|-----------|-----------|
| **Jooble** | Ja | Format basiert auf Jooble-Spec |
| **Talent.com** | Ja | Akzeptiert Standard-XML |
| **Adzuna** | Ja | Akzeptiert Standard-XML |
| **Regionale Portale** | Ja | Standard-XML wird weitgehend akzeptiert |
| **Indeed** | Nein | Stellt XML-Feeds am 01.04.2026 ein |

---

## 6. Datei-Übersicht

| Datei | Beschreibung |
|-------|-------------|
| `src/Integrations/Feed/XmlJobFeed.php` | Feed-Generator + Caching |
| `src/Api/IntegrationController.php` | REST-Endpoint für Settings |
| `assets/.../IntegrationSettings.jsx` | UI-Komponente (XML Feed Card) |
| `assets/.../useIntegrations.js` | Settings-Hook |

---

## 7. Referenzen

- [Jooble XML Feed Dokumentation](https://jooble.org/info/xml-feed)
- [Talent.com XML Feed Guide](https://www.talent.com/employers)

---

*Erstellt: 10. Februar 2026*

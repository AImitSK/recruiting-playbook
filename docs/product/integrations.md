# Integrationen & Europa-Strategie

## Übersicht

Für den Erfolg im europäischen Markt sind drei Bereiche entscheidend:

1. **DSGVO-Compliance** – Ohne das geht nichts
2. **Job-Portal-Anbindungen** – Reichweite für Stellenanzeigen
3. **HR-Software-Integrationen** – Einbettung in bestehende Workflows

```
┌─────────────────────────────────────────────────────────────────┐
│                    RECRUITING PLAYBOOK                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐  │
│  │  DSGVO   │    │   JOB    │    │    HR    │    │  KOMMU-  │  │
│  │ COMPLIANCE│    │ PORTALE  │    │ SOFTWARE │    │ NIKATION │  │
│  └────┬─────┘    └────┬─────┘    └────┬─────┘    └────┬─────┘  │
│       │               │               │               │         │
│  • Löschfristen  • Indeed       • DATEV         • E-Mail       │
│  • Consent       • StepStone    • Personio      • Outlook      │
│  • Export        • Arbeits-     • Sage          • Teams        │
│  • AVV             agentur      • Zevorix       • Slack        │
│                  • LinkedIn                                     │
│                  • Google Jobs                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 1. DSGVO-Compliance (Must-Have)

### Gesetzliche Anforderungen

| Anforderung | Umsetzung im Plugin | Priorität |
|-------------|---------------------|-----------|
| **Einwilligung** | Checkbox mit Timestamp, Link zu Datenschutzerklärung | P0 |
| **Zweckbindung** | Daten nur für Bewerbungsprozess nutzen | P0 |
| **Löschfristen** | Automatische Löschung nach X Monaten (konfigurierbar) | P0 |
| **Auskunftsrecht** | Export aller Daten eines Bewerbers (JSON/PDF) | P0 |
| **Recht auf Löschung** | Manuelles Löschen + Bestätigung | P0 |
| **Datenminimierung** | Nur notwendige Felder als Pflicht | P1 |
| **Auftragsverarbeitung** | AVV-Vorlagen für AI-Nutzung | P1 |

### Technische Umsetzung

#### Consent-Management

```
Bewerbungsformular:
┌─────────────────────────────────────────────────────────────┐
│ [x] Ich habe die Datenschutzerklärung gelesen und stimme   │
│     der Verarbeitung meiner Daten zum Zweck der Bewerbung  │
│     zu. *                                                   │
│                                                             │
│ [ ] Ich möchte in den Talent-Pool aufgenommen werden und   │
│     auch für zukünftige Stellen kontaktiert werden.        │
│     (optional)                                              │
└─────────────────────────────────────────────────────────────┘

Gespeichert wird:
- Consent-Text (versioniert)
- Timestamp
- IP-Adresse (optional, konfigurierbar)
- Checkboxen-Status
```

#### Automatische Löschfristen

```
Einstellungen:
┌─────────────────────────────────────────────────────────────┐
│ Bewerberdaten automatisch löschen nach:                     │
│                                                             │
│ Abgelehnte Bewerber:    [ 6 ] Monate  ▼                    │
│ Eingestellte Bewerber:  [ Nach Übernahme in HR-System ]    │
│ Talent-Pool:            [ 24 ] Monate ▼                    │
│                                                             │
│ [ ] E-Mail-Erinnerung vor Löschung senden                  │
│ [ ] Bewerber über Löschung informieren                     │
└─────────────────────────────────────────────────────────────┘
```

#### Datenexport (Auskunftsrecht)

Ein-Klick-Export pro Bewerber:
- Alle Stammdaten
- Alle hochgeladenen Dokumente
- Alle Notizen und Bewertungen
- Alle E-Mail-Kommunikation
- Consent-Historie

Format: ZIP mit JSON + Dokumenten oder PDF-Report

#### AVV für AI-Nutzung

Bei Nutzung des AI-Addons:
- Hinweis, dass Daten an API-Provider übermittelt werden
- Keine personenbezogenen Daten in AI-Prompts (nur Stellendaten)
- EU-Server für eigenen Proxy (wenn möglich)
- AVV-Vorlage zum Download für Kunden

### DSGVO-Features nach Tier

| Feature | FREE | PRO | AI |
|---------|:----:|:---:|:--:|
| Consent-Checkbox | ✅ | ✅ | ✅ |
| Löschfunktion (manuell) | ✅ | ✅ | ✅ |
| Automatische Löschfristen | ❌ | ✅ | ✅ |
| Datenexport (Bewerber) | ❌ | ✅ | ✅ |
| Consent-Protokoll | ❌ | ✅ | ✅ |
| AVV-Vorlagen | ❌ | ❌ | ✅ |

---

## 2. Job-Portal-Integrationen

### Priorität nach Reichweite (DACH-Fokus)

#### Tier 1: Must-Have (Phase 2)

| Portal | Typ | Integration |
|--------|-----|-------------|
| **Google for Jobs** | Aggregator | Schema.org Markup (kostenlos!) |
| **Indeed** | Portal | XML-Feed oder API |
| **LinkedIn** | Netzwerk | XML-Feed, API (eingeschränkt) |

#### Tier 2: Should-Have (Phase 3-4)

| Portal | Land | Integration |
|--------|------|-------------|
| **StepStone** | DE/AT | API (kostenpflichtig) |
| **Bundesagentur für Arbeit** | DE | HR-XML Format |
| **XING** | DACH | API (eingeschränkt) |
| **karriere.at** | AT | Feed |
| **jobs.ch** | CH | Feed |

#### Tier 3: Nice-to-Have (später)

- Kleinanzeigen (ehemals eBay)
- Monster
- Glassdoor
- Länderspezifische Portale (NL, FR, etc.)

### Technische Umsetzung

#### Google for Jobs (kostenlos, hohe Priorität!)

Schema.org JobPosting Markup automatisch generieren:

```json
{
  "@context": "https://schema.org/",
  "@type": "JobPosting",
  "title": "Pflegefachkraft (m/w/d)",
  "description": "...",
  "datePosted": "2025-01-20",
  "validThrough": "2025-03-20",
  "employmentType": "FULL_TIME",
  "hiringOrganization": {
    "@type": "Organization",
    "name": "Pflege Plus GmbH",
    "sameAs": "https://www.pflegeplus.de"
  },
  "jobLocation": {
    "@type": "Place",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Berlin",
      "addressCountry": "DE"
    }
  },
  "baseSalary": {
    "@type": "MonetaryAmount",
    "currency": "EUR",
    "value": {
      "@type": "QuantitativeValue",
      "minValue": 3200,
      "maxValue": 4000,
      "unitText": "MONTH"
    }
  }
}
```

#### XML-Feed für Portale

Standard-Endpoint: `example.com/job-feed/`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<jobs>
  <job>
    <id>123</id>
    <title>Pflegefachkraft (m/w/d)</title>
    <description><![CDATA[...]]></description>
    <url>https://example.com/jobs/pflegefachkraft/</url>
    <location>Berlin, Deutschland</location>
    <company>Pflege Plus GmbH</company>
    <employment_type>Vollzeit</employment_type>
    <salary_min>3200</salary_min>
    <salary_max>4000</salary_max>
    <salary_currency>EUR</salary_currency>
    <date_posted>2025-01-20</date_posted>
    <valid_through>2025-03-20</valid_through>
  </job>
</jobs>
```

#### Multiposting-Interface (Pro/Enterprise)

```
Stelle veröffentlichen auf:
┌─────────────────────────────────────────────────────────────┐
│ ☑ Eigene Website                          [Aktiv]          │
│ ☑ Google for Jobs (automatisch)           [Aktiv]          │
│ ☑ Indeed                                  [Konfigurieren]  │
│ ☐ StepStone                               [API-Key fehlt]  │
│ ☐ Bundesagentur für Arbeit                [Einrichten]     │
│ ☑ LinkedIn                                [Aktiv]          │
└─────────────────────────────────────────────────────────────┘
│ [Alle aktualisieren]  [Veröffentlichungsstatus prüfen]     │
└─────────────────────────────────────────────────────────────┘
```

### Integration nach Tier

| Feature | FREE | PRO | AI |
|---------|:----:|:---:|:--:|
| Google for Jobs Schema | ✅ | ✅ | ✅ |
| XML-Feed (generisch) | ❌ | ✅ | ✅ |
| Indeed Integration | ❌ | ✅ | ✅ |
| StepStone Integration | ❌ | Addon | Addon |
| Multiposting-Dashboard | ❌ | ✅ | ✅ |

---

## 3. HR-Software-Integrationen

### Marktübersicht DACH

#### Zeitarbeit / Personaldienstleister

| Software | Verbreitung | API | Priorität |
|----------|-------------|-----|-----------|
| **Zvoove (ehem. Landwehr)** | Sehr hoch (DE) | REST API | P1 |
| **L1** | Hoch | Ja | P2 |
| **Timecount** | Mittel | Begrenzt | P3 |
| **eGECKO** | Mittel | Ja | P3 |

#### Allgemeine HR-Software

| Software | Verbreitung | API | Priorität |
|----------|-------------|-----|-----------|
| **Personio** | Hoch (EU) | REST API | P1 |
| **DATEV** | Sehr hoch (DE) | DATEV Connect | P2 |
| **SAP SuccessFactors** | Enterprise | Ja | P3 |
| **Sage HR** | Mittel | REST API | P3 |
| **HeavenHR** | Startups | REST API | P3 |

#### Kommunikation & Produktivität

| Software | Integration | Priorität |
|----------|-------------|-----------|
| **Microsoft 365** | Graph API, Outlook, Teams | P1 |
| **Google Workspace** | Gmail, Calendar | P2 |
| **Slack** | Webhooks, App | P2 |
| **Calendly/Cal.com** | Interview-Scheduling | P2 |

### Integrations-Architektur

```
┌─────────────────────────────────────────────────────────────┐
│                  RECRUITING PLAYBOOK                        │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              INTEGRATION HUB                         │   │
│  │                                                      │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐          │   │
│  │  │ Webhooks │  │ REST API │  │  Zapier  │          │   │
│  │  │ (Push)   │  │ (Pull)   │  │  (n8n)   │          │   │
│  │  └────┬─────┘  └────┬─────┘  └────┬─────┘          │   │
│  └───────┼─────────────┼─────────────┼─────────────────┘   │
│          │             │             │                      │
└──────────┼─────────────┼─────────────┼──────────────────────┘
           │             │             │
           ▼             ▼             ▼
    ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
    │ Zvoove   │  │ Personio │  │ DATEV    │  │ Teams/   │
    │          │  │          │  │          │  │ Slack    │
    └──────────┘  └──────────┘  └──────────┘  └──────────┘
```

### Webhook-Events (Basis für alle Integrationen)

```
Verfügbare Events:
─────────────────────────────────────────────
job.created          Neue Stelle angelegt
job.published        Stelle veröffentlicht
job.updated          Stelle bearbeitet
job.archived         Stelle archiviert

application.received Neue Bewerbung eingegangen
application.updated  Bewerberstatus geändert
application.hired    Bewerber eingestellt
application.rejected Bewerber abgelehnt

candidate.exported   Bewerber exportiert
candidate.deleted    Bewerber gelöscht (DSGVO)
─────────────────────────────────────────────
```

Webhook-Payload Beispiel:

```json
{
  "event": "application.received",
  "timestamp": "2025-01-20T14:30:00Z",
  "data": {
    "application_id": 456,
    "job_id": 123,
    "job_title": "Pflegefachkraft (m/w/d)",
    "candidate": {
      "name": "Max Mustermann",
      "email": "max@example.com"
    }
  },
  "webhook_id": "wh_abc123"
}
```

### REST API Endpoints (Pro)

```
GET    /wp-json/recruiting/v1/jobs
GET    /wp-json/recruiting/v1/jobs/{id}
POST   /wp-json/recruiting/v1/jobs
PUT    /wp-json/recruiting/v1/jobs/{id}
DELETE /wp-json/recruiting/v1/jobs/{id}

GET    /wp-json/recruiting/v1/applications
GET    /wp-json/recruiting/v1/applications/{id}
PUT    /wp-json/recruiting/v1/applications/{id}/status
POST   /wp-json/recruiting/v1/applications/{id}/export

GET    /wp-json/recruiting/v1/reports/overview
GET    /wp-json/recruiting/v1/reports/time-to-hire
```

### Spezifische Integrationen

#### Zvoove (Zeitarbeit)

Wichtig für Pilotkunden!

```
Datenfluss:
1. Bewerber in Plugin eingestellt → Status "Hired"
2. Webhook triggert Zvoove-Export
3. Stammdaten werden in Zvoove angelegt
4. Mitarbeiter-ID wird zurückgeschrieben

Zu übertragende Felder:
- Stammdaten (Name, Adresse, Kontakt)
- Qualifikationen
- Verfügbarkeit
- Dokumente (optional)
```

#### Personio

```
Sync-Optionen:
- Eingestellte Bewerber → Personio Mitarbeiter
- Personio Stellen → Plugin (optional)
- Bidirektionaler Status-Sync

API: https://developer.personio.de/
```

#### DATEV (Export)

```
DATEV Lodas/Lohn & Gehalt Export:
- Stammdatensatz für neue Mitarbeiter
- Format: ASCII oder DATEV Connect Online
- Felder: Personalnummer, Name, Adresse, 
         Steuer-ID, SV-Nummer, Bankverbindung
         
Hinweis: DATEV-Integration ist komplex,
         eventuell über Partner lösen
```

### Integration nach Tier

| Feature | FREE | PRO | AI |
|---------|:----:|:---:|:--:|
| CSV/PDF Export | Basic | Erweitert | Erweitert |
| Webhooks | ❌ | ✅ | ✅ |
| REST API | ❌ | ✅ | ✅ |
| Zapier/Make | ❌ | ✅ | ✅ |
| Zvoove-Integration | ❌ | Addon | Addon |
| Personio-Integration | ❌ | Addon | Addon |
| DATEV-Export | ❌ | Addon | Addon |

---

## 4. Mehrsprachigkeit

### Sprachen (Priorität)

1. **Deutsch** – Hauptmarkt
2. **Englisch** – International, Tech-Unternehmen
3. **Französisch** – Schweiz, Frankreich
4. **Niederländisch** – Niederlande, Belgien
5. **Polnisch** – Viele Pflegekräfte aus PL

### Umsetzung

- WordPress i18n Standard (`.pot`/`.po` Dateien)
- Übersetzbare Strings im Backend UND Frontend
- WPML/Polylang kompatibel
- Mehrsprachige Stellenanzeigen (Pro)

---

## 5. Priorisierung & Roadmap

### Phase 1 (MVP) – Must-Have

- [x] DSGVO: Consent-Checkbox
- [ ] DSGVO: Manuelle Löschfunktion
- [ ] Google for Jobs Schema (automatisch)
- [ ] CSV-Export (Basic)

### Phase 2 (Pro) – Should-Have

- [ ] DSGVO: Automatische Löschfristen
- [ ] DSGVO: Datenexport pro Bewerber
- [ ] Webhooks (alle Events)
- [ ] REST API
- [ ] XML-Feed für Job-Portale
- [ ] Indeed Integration

### Phase 3 (AI + Addons) – Nice-to-Have

- [ ] Zvoove-Integration (Addon)
- [ ] Personio-Integration (Addon)
- [ ] DATEV-Export (Addon)
- [ ] Multiposting-Dashboard
- [ ] Mehrsprachigkeit

### Phase 4 (Scale) – Future

- [ ] StepStone API
- [ ] SAP SuccessFactors
- [ ] White-Label API
- [ ] Marketplace für Integrationen

---

## 6. Wettbewerbsvergleich

| Feature | Unser Plugin | WP Job Manager | Personio | JOIN |
|---------|:------------:|:--------------:|:--------:|:----:|
| WordPress-nativ | ✅ | ✅ | ❌ | ❌ |
| DSGVO-Tools | ✅ | Begrenzt | ✅ | ✅ |
| Google for Jobs | ✅ | Addon | ✅ | ✅ |
| Multiposting | Pro | Addon | ✅ | ✅ |
| Zeitarbeit-Software | Addon | ❌ | ❌ | ❌ |
| AI-Texte | ✅ | ❌ | ❌ | ❌ |
| Preis | ab 0€ | ab 0€ | ab 200€/M | ab 0€ |

**Unser USP:** WordPress-nativ + Zeitarbeit-Integrationen + AI

---

## 7. Offene Fragen

- [ ] Zvoove-API-Zugang für Entwicklung besorgen
- [ ] Indeed Partner werden? (bessere API-Konditionen)
- [ ] DATEV selbst entwickeln oder Partner?
- [ ] Welche Integrationen als separate Addons verkaufen?
- [ ] Hosting für Webhook-Relay nötig? (Zuverlässigkeit)

---

*Letzte Aktualisierung: Januar 2025*

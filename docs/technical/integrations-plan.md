# Integrationen: Planungsdokument

> **Status:** Planung
> **Erstellt:** 10. Februar 2026
> **Ziel:** Priorisierung und Umfang der Plugin-Integrationen festlegen

---

## Ausgangslage

Die bisherigen geplanten Integrationen (Zvoove, DATEV) sind sehr nischig.
Stattdessen sollen universelle Integrationen priorisiert werden, die **allen Nutzern** Mehrwert bringen.

### Bereits implementiert
- REST API (Jobs, Applications) ✅
- Webhooks (CRUD, HMAC-SHA256, async Delivery) ✅
- API-Key Management ✅
- E-Mail-System (SMTP, Templates, Signaturen) ✅

---

## 1. Integrationen im Detail

### 1.1 Google for Jobs (JSON-LD Schema)

| | |
|---|---|
| **Prioritat** | Kritisch - Must-Have |
| **Tier** | Free |
| **Komplexitat** | Niedrig |
| **Aufwand** | ~1 Tag |

**Was:** JSON-LD `JobPosting` Schema-Markup im `<head>` der Einzelseiten.
Google indexiert Stellen automatisch und zeigt sie prominent in der Suche.

**Pflichtfelder:**
- `title` - Jobtitel
- `description` - Stellenbeschreibung (HTML)
- `hiringOrganization` - Firmenname + Logo
- `jobLocation` - Standort (Adresse)
- `datePosted` - Veroffentlichungsdatum
- `validThrough` - Ablaufdatum
- `employmentType` - FULL_TIME, PART_TIME, etc.

**Empfohlene Felder (hohere CTR):**
- `baseSalary` - Gehaltsspanne
- `workHours`, `jobBenefits`
- `educationRequirements`, `experienceRequirements`
- `applicantLocationRequirements` (Remote-Jobs)

**Implementierung:**
```php
// In single-job.php Template:
// JSON-LD aus Job-Meta-Daten generieren und im <head> ausgeben
// Validierung: Google Search Console + Rich Results Test
```

**Datenquellen (bereits vorhanden):**
- Jobtitel → Post Title
- Beschreibung → Post Content
- Standort → `job_location` Taxonomy
- Beschaftigungsart → `employment_type` Taxonomy
- Gehalt → Job Meta (min/max)
- Bewerbungsfrist → Job Meta
- Firma → Plugin-Einstellungen (Firmendaten)

---

### 1.2 XML/RSS Job Feed

| | |
|---|---|
| **Prioritat** | Hoch |
| **Tier** | Free |
| **Komplexitat** | Niedrig |
| **Aufwand** | ~1-2 Tage |

**Was:** Standardisierter XML-Feed unter `/feed/jobs/` (oder `/jobs/feed/`),
den Jobborsen automatisch einlesen konnen.

**Unterstutzte Jobborsen uber XML-Feed:**
- Jooble (klare Spezifikation)
- Neuvoo/Talent.com
- Adzuna
- Kleinere regionale Jobborsen

**XML-Format (angelehnt an Jooble-Spezifikation):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<jobs>
  <job>
    <link>https://example.com/jobs/pflegefachkraft/</link>
    <name>Pflegefachkraft (m/w/d)</name>
    <region>Berlin</region>
    <description><![CDATA[Vollstandige Stellenbeschreibung...]]></description>
    <pubdate>10.02.2026</pubdate>
    <updated>10.02.2026</updated>
    <expire>10.03.2026</expire>
    <jobtype>full-time</jobtype>
    <salary>3200-4000 EUR</salary>
    <company>Pflegedienst Muster GmbH</company>
  </job>
</jobs>
```

**Implementierung:**
- Eigener WordPress Feed (`add_feed('jobs', ...)`)
- Alternativ: Custom REST Endpoint `/recruiting/v1/feed/xml`
- Nur aktive/veroffentlichte Stellen
- Caching (Transient, 1h)

**Admin-Einstellungen:**
- Feed aktivieren/deaktivieren
- Feed-URL anzeigen (zum Kopieren fur Jobborsen)

> **Hinweis Indeed:** Indeed stellt XML-Feeds zum 1. April 2026 ein
> und migriert auf eine neue API. Deshalb kein Indeed-spezifisches
> XML-Format implementieren.

---

### 1.3 Slack-Benachrichtigungen

| | |
|---|---|
| **Prioritat** | Mittel |
| **Tier** | Pro |
| **Komplexitat** | Niedrig |
| **Aufwand** | ~1 Tag |

**Was:** Benachrichtigungen in einen Slack-Channel bei wichtigen Events.

**Events:**
- Neue Bewerbung eingegangen
- Status-Anderung (z.B. Interview geplant)
- Neue Stelle veroffentlicht

**Technisch:**
- Slack Incoming Webhook URL (vom User konfiguriert)
- Einfacher HTTP POST mit JSON
- Keine OAuth, keine App-Installation notig
- Rate Limit: 1 Message/Sekunde

**Nachrichtenformat:**
```json
{
  "blocks": [
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Neue Bewerbung*\n*Bewerber:* Maria Weber\n*Stelle:* Pflegefachkraft (m/w/d)\n*Quelle:* Website"
      }
    },
    {
      "type": "actions",
      "elements": [
        {
          "type": "button",
          "text": { "type": "plain_text", "text": "Bewerbung ansehen" },
          "url": "https://example.com/wp-admin/..."
        }
      ]
    }
  ]
}
```

**Admin-Einstellungen:**
- Webhook-URL Eingabefeld
- Test-Nachricht senden Button
- Checkboxen: Welche Events benachrichtigen?

---

### 1.4 Microsoft Teams Benachrichtigungen

| | |
|---|---|
| **Prioritat** | Mittel |
| **Tier** | Pro |
| **Komplexitat** | Mittel |
| **Aufwand** | ~1-2 Tage |

**Was:** Benachrichtigungen in einen Teams-Channel.

> **Wichtig:** Office 365 Connectors werden am 30. April 2026 eingestellt.
> Neue Implementierung muss "Workflows for Microsoft Teams" nutzen.

**Technisch:**
- Teams Workflow Webhook URL (vom User konfiguriert)
- HTTP POST mit Adaptive Card JSON
- Kein OAuth notig

**Nachrichtenformat (Adaptive Card):**
```json
{
  "type": "message",
  "attachments": [{
    "contentType": "application/vnd.microsoft.card.adaptive",
    "content": {
      "type": "AdaptiveCard",
      "version": "1.4",
      "body": [
        { "type": "TextBlock", "text": "Neue Bewerbung", "weight": "bolder", "size": "medium" },
        { "type": "FactSet", "facts": [
          { "title": "Bewerber:", "value": "Maria Weber" },
          { "title": "Stelle:", "value": "Pflegefachkraft (m/w/d)" }
        ]}
      ],
      "actions": [
        { "type": "Action.OpenUrl", "title": "Bewerbung ansehen", "url": "..." }
      ]
    }
  }]
}
```

**Admin-Einstellungen:**
- Workflow-Webhook-URL Eingabefeld
- Test-Nachricht senden Button
- Checkboxen: Welche Events benachrichtigen?

**Implementierung:**
- Slack und Teams teilen sich eine gemeinsame `NotificationService` Klasse
- Nur das Nachrichtenformat unterscheidet sich
- Events werden uber bestehende WordPress Hooks ausgelost

---

### 1.5 Personio Sync (Add-on)

| | |
|---|---|
| **Prioritat** | Niedrig (Phase 4+) |
| **Tier** | Separates Add-on |
| **Komplexitat** | Mittel |
| **Aufwand** | ~3-5 Tage |

**Was:** Bidirektionaler Sync zwischen Recruiting Playbook und Personio.

**Sync-Richtungen:**
- **Personio → Plugin:** Jobs importieren, Anderungen synchronisieren
- **Plugin → Personio:** Bewerbungen ubertragen, Status-Updates

**Technisch:**
- REST API mit OAuth 2.0 (Client Credentials)
- `POST /auth/token` fur Access Token
- `GET /recruiting/positions` - Jobs abrufen
- `POST /recruiting/applications` - Bewerbungen senden
- Feld-Mapping konfigurierbar im Admin

**Warum Add-on:**
- Nur relevant fur Unternehmen die Personio bereits nutzen
- Erfordert Personio API-Credentials (kostenpflichtig)
- Wartungsaufwand durch API-Anderungen

---

### 1.6 Zvoove Integration (Add-on)

| | |
|---|---|
| **Prioritat** | Niedrig (bei Bedarf) |
| **Tier** | Separates Add-on |
| **Komplexitat** | Mittel-Hoch |
| **Aufwand** | ~5 Tage |

**Was:** Sync mit Zvoove (ehemals L1) fur Zeitarbeitsfirmen.

**Warum Add-on statt Kern:**
- Sehr spezifische Zielgruppe (Zeitarbeit/Personaldienstleister)
- Komplexe API, wenig offentliche Dokumentation
- Nur auf Nachfrage implementieren

---

## 2. Gestrichene Integrationen

| Integration | Grund |
|-------------|-------|
| **DATEV Export** | Kein ATS-Feature, gehort zur Lohnbuchhaltung |
| **Indeed XML Feed** | Wird am 1. April 2026 von Indeed eingestellt |
| **Glassdoor** | Keine offentliche API verfugbar |
| **Bundesagentur fur Arbeit** | Keine Job-Posting-API, nur Jobsuche |
| **Google/Outlook Calendar API** | Zu komplex (OAuth) |

---

## 3. Empfohlene Priorisierung

### Sofort umsetzen (vor Launch)

| # | Integration | Tier | Aufwand | Impact |
|---|------------|------|---------|--------|
| 1 | Google for Jobs (JSON-LD) | Free | ~1 Tag | Sehr hoch - kostenlose Reichweite |
| 2 | XML Job Feed | Free | ~1-2 Tage | Hoch - universeller Jobborsen-Connector |
| 3 | Slack Benachrichtigungen | Pro | ~1 Tag | Mittel - Team-Kommunikation |
| 4 | Teams Benachrichtigungen | Pro | ~1-2 Tage | Mittel - Team-Kommunikation |

**Gesamt: ~4-5 Tage Entwicklung**

### Spater (Add-ons, bei Nachfrage)

| # | Integration | Tier | Aufwand |
|---|------------|------|---------|
| 6 | Personio Sync | Add-on | ~3-5 Tage |
| 7 | Zvoove | Add-on | ~5 Tage |

---

## 4. Admin-UI: Settings-Tab "Integrationen"

### Einordnung im bestehenden Layout

```
Einstellungen (rp-settings)
├── Tab: Allgemein
├── Tab: Firmendaten
├── Tab: Export
├── Tab: Benutzerrollen        (Pro)
├── Tab: Design & Branding     (Pro)
├── Tab: Integrationen         ← NEU (Free + Pro gemischt)
├── Tab: API                   (Pro)
└── Tab: KI-Analyse            (Addon)
```

Der Tab steht vor "API", weil Integrationen fur alle Nutzer relevant sind
(Google for Jobs + XML Feed = Free), wahrend API nur Pro ist.

### Tab-Sichtbarkeit

```jsx
// Im SettingsPage.jsx - Tab ist IMMER sichtbar (Free + Pro Inhalte)
<TabsTrigger value="integrations">
    { __( 'Integrationen', 'recruiting-playbook' ) }
</TabsTrigger>
```

### maxWidth

```jsx
// Breiteres Layout wie API/KI-Tab (1100px statt 900px)
style={{ maxWidth: (activeTab === 'design' || activeTab === 'api'
    || activeTab === 'ai' || activeTab === 'integrations') ? '1100px' : '900px' }}
```

---

### 4.1 UI-Layout (Wireframe)

Der Tab besteht aus gestapelten Cards (wie ApiKeySettings / AiAnalysisSettings).
Jede Integration ist eine eigene Card mit Header, Toggle und Einstellungen.

```
┌─────────────────────────────────────────────────────────────────────┐
│  Integrationen                                                       │
│  Verbinden Sie Ihr Recruiting mit externen Diensten                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ 🔍 Google for Jobs                                     [ON]  │  │
│  │ ─────────────────────────────────────────────────────────     │  │
│  │ Stellen erscheinen automatisch in der Google-Jobsuche.       │  │
│  │                                                               │  │
│  │ Status:  ● Aktiv – Schema wird auf allen Stellen ausgegeben  │  │
│  │                                                               │  │
│  │ ┌─ Optionale Felder ──────────────────────────────────────┐  │  │
│  │ │ [x] Gehalt anzeigen (wenn vorhanden)                    │  │  │
│  │ │ [x] Remote-Option kennzeichnen                          │  │  │
│  │ │ [x] Bewerbungsfrist als validThrough setzen             │  │  │
│  │ └────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ 📡 XML Job Feed                                        [ON]  │  │
│  │ ─────────────────────────────────────────────────────────     │  │
│  │ Universeller Feed fur Jobborsen (Jooble, Talent.com, etc.)   │  │
│  │                                                               │  │
│  │ Feed-URL:                                                     │  │
│  │ ┌────────────────────────────────────────────────┐ [Kopie-  │  │
│  │ │ https://example.com/feed/jobs/                 │  ren]    │  │
│  │ └────────────────────────────────────────────────┘           │  │
│  │                                                               │  │
│  │ Stellen im Feed:  15 aktive Stellen                          │  │
│  │ Letzter Abruf:    Heute, 14:32 Uhr (3 Abrufe heute)         │  │
│  │                                                               │  │
│  │ ┌─ Feed-Optionen ────────────────────────────────────────┐  │  │
│  │ │ [x] Gehalt im Feed anzeigen                             │  │  │
│  │ │ [x] Beschreibung als HTML (statt Plain Text)            │  │  │
│  │ │ Max. Stellen im Feed: [50        ▼]                     │  │  │
│  │ └────────────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ 💬 Slack                                        🔒 Pro [OFF] │  │
│  │ ─────────────────────────────────────────────────────────     │  │
│  │ Benachrichtigungen bei neuen Bewerbungen und Statuswechseln. │  │
│  │                                                               │  │
│  │ Webhook-URL:                                                  │  │
│  │ ┌────────────────────────────────────────────────────────┐   │  │
│  │ │ https://hooks.slack.com/services/T.../B.../xxx         │   │  │
│  │ └────────────────────────────────────────────────────────┘   │  │
│  │                                                               │  │
│  │ Benachrichtigen bei:                                          │  │
│  │ [x] Neue Bewerbung eingegangen                                │  │
│  │ [x] Bewerbungsstatus geandert                                 │  │
│  │ [ ] Neue Stelle veroffentlicht                                │  │
│  │ [ ] Bewerbungsfrist lauft ab (3 Tage vorher)                  │  │
│  │                                                               │  │
│  │ Channel-Vorschau:                                             │  │
│  │ ┌──────────────────────────────────────────────────────┐     │  │
│  │ │ 📋 Neue Bewerbung                                    │     │  │
│  │ │ Bewerber: Maria Weber                                │     │  │
│  │ │ Stelle:   Pflegefachkraft (m/w/d)                    │     │  │
│  │ │ Quelle:   Website                                    │     │  │
│  │ │ [Bewerbung ansehen]                                  │     │  │
│  │ └──────────────────────────────────────────────────────┘     │  │
│  │                                                               │  │
│  │                          [Test-Nachricht senden]              │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │ 👥 Microsoft Teams                              🔒 Pro [OFF] │  │
│  │ ─────────────────────────────────────────────────────────     │  │
│  │ Benachrichtigungen in Microsoft Teams Channels.               │  │
│  │                                                               │  │
│  │ Workflow-Webhook-URL:                                         │  │
│  │ ┌────────────────────────────────────────────────────────┐   │  │
│  │ │ https://prod-xx.westeurope.logic.azure.com/workflows/  │   │  │
│  │ └────────────────────────────────────────────────────────┘   │  │
│  │                                                               │  │
│  │ ℹ️ Anleitung: Teams → Channel → ... → Workflows →            │  │
│  │    "Beim Empfang einer Teams-Webhookanforderung"              │  │
│  │                                                               │  │
│  │ Benachrichtigen bei:                                          │  │
│  │ [x] Neue Bewerbung eingegangen                                │  │
│  │ [x] Bewerbungsstatus geandert                                 │  │
│  │ [ ] Neue Stelle veroffentlicht                                │  │
│  │ [ ] Bewerbungsfrist lauft ab (3 Tage vorher)                  │  │
│  │                                                               │  │
│  │                          [Test-Nachricht senden]              │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                      │
│                                          [Speichern]                │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

### 4.2 Komponenten-Struktur (React)

```
plugin/assets/src/js/admin/settings/
├── components/
│   ├── IntegrationSettings.jsx        ← Hauptkomponente fur Tab
│   ├── integrations/
│   │   ├── GoogleJobsCard.jsx         ← Google for Jobs Card
│   │   ├── XmlFeedCard.jsx            ← XML Job Feed Card
│   │   ├── SlackCard.jsx              ← Slack Card
│   │   ├── TeamsCard.jsx              ← Teams Card
│   └── index.js
├── hooks/
│   └── useIntegrations.js             ← Settings laden/speichern
└── SettingsPage.jsx                   ← Tab hinzufugen
```

**Bestehende UI-Komponenten (wiederverwendet):**
- `Card`, `CardHeader`, `CardTitle`, `CardDescription`, `CardContent`
- `Switch` (fur Toggles)
- `Input` (fur URLs)
- `Button` (fur Test-Senden, Kopieren)
- `Badge` (fur Status: Aktiv/Inaktiv, Free/Pro)
- `Alert` (fur Hinweise/Anleitungen)
- `Label` (fur Checkbox-Labels)

---

### 4.3 Datenmodell (WordPress Options)

Alle Integrationseinstellungen in einer Option `rp_integrations`:

```php
$defaults = [
    // Google for Jobs (Free)
    'google_jobs_enabled'           => true,
    'google_jobs_show_salary'       => true,
    'google_jobs_show_remote'       => true,
    'google_jobs_show_deadline'     => true,

    // XML Job Feed (Free)
    'xml_feed_enabled'              => true,
    'xml_feed_show_salary'          => true,
    'xml_feed_html_description'     => true,
    'xml_feed_max_items'            => 50,

    // Slack (Pro)
    'slack_enabled'                 => false,
    'slack_webhook_url'             => '',
    'slack_event_new_application'   => true,
    'slack_event_status_changed'    => true,
    'slack_event_job_published'     => false,
    'slack_event_deadline_reminder' => false,

    // Microsoft Teams (Pro)
    'teams_enabled'                 => false,
    'teams_webhook_url'             => '',
    'teams_event_new_application'   => true,
    'teams_event_status_changed'    => true,
    'teams_event_job_published'     => false,
    'teams_event_deadline_reminder' => false,

];
```

**REST Endpoint:**
```
GET  /recruiting/v1/settings/integrations     → Einstellungen laden
POST /recruiting/v1/settings/integrations     → Einstellungen speichern
POST /recruiting/v1/integrations/slack/test   → Test-Nachricht senden
POST /recruiting/v1/integrations/teams/test   → Test-Nachricht senden
```

---

### 4.4 Backend-Architektur (PHP)

```
plugin/src/
├── Integrations/
│   ├── IntegrationManager.php         ← Registriert Hooks, ladt Settings
│   ├── GoogleJobs/
│   │   └── SchemaGenerator.php        ← JSON-LD Output im Frontend
│   ├── Feed/
│   │   └── XmlJobFeed.php             ← XML Feed Endpoint
│   ├── Notifications/
│   │   ├── NotificationService.php    ← Abstrakte Basis
│   │   ├── SlackNotifier.php          ← Slack Webhook POST
│   │   └── TeamsNotifier.php          ← Teams Adaptive Card POST
│   └── Calendar/
├── Api/
│   └── IntegrationController.php      ← REST Endpoints fur Settings + Test
```

**Hook-Registrierung (IntegrationManager):**

```php
class IntegrationManager {
    public function register(): void {
        $settings = get_option( 'rp_integrations', [] );

        // Google for Jobs: JSON-LD auf Einzelseiten
        if ( $settings['google_jobs_enabled'] ?? true ) {
            add_action( 'wp_head', [ new SchemaGenerator(), 'output' ] );
        }

        // XML Feed: Custom Feed registrieren
        if ( $settings['xml_feed_enabled'] ?? true ) {
            add_action( 'init', [ new XmlJobFeed(), 'registerFeed' ] );
        }

        // Slack Notifications
        if ( ! empty( $settings['slack_webhook_url'] ) && ( $settings['slack_enabled'] ?? false ) ) {
            $slack = new SlackNotifier( $settings );
            add_action( 'rp_application_created', [ $slack, 'onNewApplication' ] );
            add_action( 'rp_application_status_changed', [ $slack, 'onStatusChanged' ], 10, 3 );
            add_action( 'publish_job_listing', [ $slack, 'onJobPublished' ] );
        }

        // Teams Notifications (gleiche Hooks)
        if ( ! empty( $settings['teams_webhook_url'] ) && ( $settings['teams_enabled'] ?? false ) ) {
            $teams = new TeamsNotifier( $settings );
            add_action( 'rp_application_created', [ $teams, 'onNewApplication' ] );
            add_action( 'rp_application_status_changed', [ $teams, 'onStatusChanged' ], 10, 3 );
            add_action( 'publish_job_listing', [ $teams, 'onJobPublished' ] );
        }
    }
}
```

---

## 5. Feature-Gating Zusammenfassung

| Feature | Tier | Bedingung |
|---------|------|-----------|
| Google for Jobs (JSON-LD) | **Free** | Immer verfugbar |
| XML Job Feed | **Free** | Immer verfugbar |
| Slack Benachrichtigungen | **Pro** | `rp_can('integrations')` |
| Teams Benachrichtigungen | **Pro** | `rp_can('integrations')` |

Free-User sehen den Tab, aber Pro-Features sind ausgegraut mit Lock-Badge
und Upgrade-Hinweis (wie bei anderen Pro-Features).

---

## 6. Implementierungsreihenfolge

| Schritt | Was | Dateien |
|---------|-----|---------|
| 1 | Settings-Datenmodell + REST Endpoint | `IntegrationController.php` |
| 2 | React-Tab + `useIntegrations` Hook | `IntegrationSettings.jsx`, `useIntegrations.js` |
| 3 | Google for Jobs JSON-LD | `SchemaGenerator.php` |
| 4 | XML Job Feed | `XmlJobFeed.php` |
| 5 | Slack Notifier + Test-Endpoint | `SlackNotifier.php`, `SlackCard.jsx` |
| 6 | Teams Notifier + Test-Endpoint | `TeamsNotifier.php`, `TeamsCard.jsx` |

---

*Erstellt: 10. Februar 2026*

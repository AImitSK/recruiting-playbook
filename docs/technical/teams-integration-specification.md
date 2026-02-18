# Microsoft Teams Integration: Technische Spezifikation

> **Pro-Feature: Microsoft Teams Benachrichtigungen**
> Real-time Notifications für Bewerbungen und Recruiting-Events in Microsoft Teams

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Datenmodell](#3-datenmodell)
4. [REST API Endpunkte](#4-rest-api-endpunkte)
5. [Teams Webhook Integration](#5-teams-webhook-integration)
6. [Event-System](#6-event-system)
7. [Nachrichtenformate](#7-nachrichtenformate)
8. [Fehlerbehandlung & Retry-Logic](#8-fehlerbehandlung--retry-logic)
9. [Admin-UI Integration](#9-admin-ui-integration)
10. [Testing](#10-testing)
11. [Sicherheit](#11-sicherheit)

---

## 1. Übersicht

### Zielsetzung

Die Microsoft Teams Integration ermöglicht:
- **Real-time Benachrichtigungen** bei wichtigen Recruiting-Events
- **Team-Kommunikation** direkt in Teams Channels
- **Zentrale Übersicht** aller Bewerbungsaktivitäten
- **Direkt-Links** zu Bewerbungen im WordPress-Admin
- **Adaptive Cards** für rich formatted Notifications

### Feature-Gating

```php
// Pro-Feature Check
if ( ! rp_can( 'integrations' ) ) {
    rp_require_feature( 'integrations', 'Teams-Benachrichtigungen', 'PRO' );
}

// Feature verfügbar in:
// - PRO: ✅
// - FREE: ❌
```

**Feature-Flag Definition:**

Das Feature `'integrations'` muss in `FeatureFlags.php` und `helpers.php` registriert werden:

```php
// FeatureFlags.php: FEATURES Array
'FREE' => [
    'integrations' => false,
    // ...
],
'PRO' => [
    'integrations' => true,
    // ...
],

// helpers.php: Feature-Mapping
'integrations' => [
    'source' => 'parent',
    'plans'  => [ 'pro' ],
],
```

### User Stories

| Als | möchte ich | damit |
|-----|-----------|-------|
| Recruiter | bei neuer Bewerbung in Teams benachrichtigt werden | ich schnell reagieren kann |
| HR-Manager | Status-Änderungen in Teams sehen | das Team informiert ist |
| Team-Lead | neue Stellenausschreibungen in Teams teilen | das Recruiting-Team informiert ist |
| Recruiter | direkt aus Teams zur Bewerbung springen | ich schnellen Zugriff habe |

### Unterstützte Events

| Event | Auslöser | Standard |
|-------|----------|----------|
| **Neue Bewerbung** | `rp_application_created` | ✅ Aktiv |
| **Status-Änderung** | `rp_application_status_changed` | ✅ Aktiv |
| **Stelle veröffentlicht** | `publish_job_listing` | ❌ Inaktiv |
| **Bewerbungsfrist läuft ab** | Cron (3 Tage vorher) | ❌ Inaktiv |

### Wichtiger Hinweis: Office 365 Connectors Deprecation

> **⚠️ Office 365 Connectors werden am 30. April 2026 eingestellt!**
>
> Diese Implementierung nutzt **"Workflows for Microsoft Teams"** (Power Automate),
> NICHT die veralteten Office 365 Connectors.
>
> **Migration:** Bestehende Connectors müssen zu Workflows migriert werden.

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   ├── Integrations/
│   │   ├── IntegrationManager.php          # Zentrale Hook-Registrierung
│   │   └── Notifications/
│   │       ├── NotificationService.php     # Abstrakte Basis-Klasse
│   │       ├── SlackNotifier.php           # Slack (bereits vorhanden)
│   │       └── TeamsNotifier.php           # Teams-spezifische Implementierung
│   │
│   ├── Api/
│   │   └── IntegrationController.php       # REST API (Settings + Test)
│   │
│   └── Services/
│       └── HttpClient.php                  # HTTP-Wrapper für wp_remote_post
│
├── assets/
│   └── src/
│       └── js/
│           └── admin/
│               └── settings/
│                   ├── components/
│                   │   ├── IntegrationSettings.jsx      # Tab (bereits vorhanden)
│                   │   └── integrations/
│                   │       ├── SlackCard.jsx            # Slack (bereits vorhanden)
│                   │       └── TeamsCard.jsx            # Teams-Konfiguration
│                   └── hooks/
│                       └── useIntegrations.js           # Hook (bereits vorhanden)
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| HTTP Client | `wp_remote_post()` (WordPress HTTP API) |
| Webhook Format | Microsoft Teams Workflows (Adaptive Cards) |
| Event-System | WordPress Actions (`do_action`) |
| Retry-Mechanik | WordPress Transients + Cron |
| Logging | `error_log()` + Activity Log |
| Rate Limiting | 4 Nachrichten/Sekunde (Teams-Limit) |

### Klassendiagramm

```
┌─────────────────────────────────────┐
│   IntegrationManager                │
│   (Hook-Registrierung)              │
└────────────┬────────────────────────┘
             │ registriert
             ▼
┌─────────────────────────────────────┐
│   NotificationService (abstract)     │
│   + send(string $message)            │
│   + formatMessage(array $data)       │
└────────────┬────────────────────────┘
             │ extends
             ▼
┌─────────────────────────────────────┐
│   TeamsNotifier                      │
│   - webhook_url: string              │
│   - settings: array                  │
│   + onNewApplication(int $app_id)    │
│   + onStatusChanged(...)             │
│   + onJobPublished(int $job_id)      │
│   + sendWebhook(array $payload)      │
│   + buildAdaptiveCard(array $data)   │
└─────────────────────────────────────┘
```

---

## 3. Datenmodell

### WordPress Options

Alle Einstellungen werden in der Option `rp_integrations` gespeichert:

```php
$defaults = [
    // Microsoft Teams (Pro)
    'teams_enabled'                 => false,
    'teams_webhook_url'             => '',
    'teams_event_new_application'   => true,
    'teams_event_status_changed'    => true,
    'teams_event_job_published'     => false,
    'teams_event_deadline_reminder' => false,
];

// Gespeichert als:
update_option( 'rp_integrations', $settings );
```

### Keine neuen Datenbank-Tabellen

Die Teams-Integration benötigt keine eigenen Tabellen. Alle Daten werden über:
- **`rp_integrations` Option** (Settings)
- **WordPress Transients** (Retry-Queue)
- **`rp_activity_log` Tabelle** (Logging, bereits vorhanden)

---

## 4. REST API Endpunkte

### 4.1 Settings-Endpunkte

#### GET `/recruiting/v1/settings/integrations`

Lädt alle Integrations-Einstellungen (inkl. Teams).

**Response:**
```json
{
    "teams_enabled": true,
    "teams_webhook_url": "https://prod-xx.westeurope.logic.azure.com/workflows/...",
    "teams_event_new_application": true,
    "teams_event_status_changed": true,
    "teams_event_job_published": false,
    "teams_event_deadline_reminder": false
}
```

#### POST `/recruiting/v1/settings/integrations`

Speichert Integrations-Einstellungen.

**Request:**
```json
{
    "teams_enabled": true,
    "teams_webhook_url": "https://prod-xx.westeurope.logic.azure.com/workflows/...",
    "teams_event_new_application": true,
    "teams_event_status_changed": false
}
```

**Response:**
```json
{
    "success": true,
    "message": "Einstellungen gespeichert",
    "data": { /* vollständige Settings */ }
}
```

**Berechtigungen:** `manage_options` (Admin)

---

### 4.2 Test-Endpunkt

#### POST `/recruiting/v1/integrations/teams/test`

Sendet eine Test-Nachricht an den konfigurierten Teams-Workflow-Webhook.

**Request Body:** Leer

**Response (Erfolg):**
```json
{
    "success": true,
    "message": "Test-Nachricht erfolgreich gesendet!"
}
```

**Response (Fehler):**
```json
{
    "success": false,
    "message": "Webhook-URL ungültig oder nicht erreichbar",
    "error": "invalid_webhook_url"
}
```

**Berechtigungen:** `manage_options`

**Testinhalt (Adaptive Card):**
```json
{
    "type": "message",
    "attachments": [{
        "contentType": "application/vnd.microsoft.card.adaptive",
        "content": {
            "type": "AdaptiveCard",
            "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
            "version": "1.4",
            "body": [
                {
                    "type": "TextBlock",
                    "text": "✅ Test-Nachricht",
                    "weight": "bolder",
                    "size": "large"
                },
                {
                    "type": "TextBlock",
                    "text": "Die Microsoft Teams Integration ist korrekt konfiguriert!",
                    "wrap": true
                }
            ]
        }
    }]
}
```

---

## 5. Teams Webhook Integration

### 5.1 Webhook-URL Format

Teams Workflow Webhooks (Power Automate) haben folgendes Format:

```
https://prod-{region}.{environment}.logic.azure.com/workflows/{workflow-id}/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig={signature}
```

**Regionen:**
- `prod-xx` (z.B. `prod-45`, `prod-72`)
- Environments: `westeurope`, `northeurope`, `eastus`, etc.

**Beispiel:**
```
https://prod-45.westeurope.logic.azure.com/workflows/a1b2c3d4.../triggers/manual/paths/invoke?api-version=2016-06-01&sp=...&sig=...
```

### 5.2 Setup-Anleitung für User

**Workflow erstellen:**

1. Teams → Channel auswählen → `...` (Mehr Optionen)
2. **Workflows** → **Workflow erstellen**
3. Vorlage: **"Beim Empfang einer Teams-Webhookanforderung"**
4. Workflow-Name: `Recruiting Playbook Notifications`
5. **Workflow-URL kopieren**
6. In Recruiting Playbook → Einstellungen → Integrationen → Teams einfügen

### 5.3 HTTP-Request

**Methode:** `POST`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "type": "message",
    "attachments": [
        {
            "contentType": "application/vnd.microsoft.card.adaptive",
            "content": {
                "type": "AdaptiveCard",
                "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
                "version": "1.4",
                "body": [ /* Adaptive Card Elements */ ],
                "actions": [ /* Actions */ ]
            }
        }
    ]
}
```

### 5.4 Response-Codes

| Code | Bedeutung | Aktion |
|------|-----------|--------|
| `202` | Erfolg (Accepted) | Nachricht gesendet |
| `400` | Ungültige Payload | Fehler loggen, nicht wiederholen |
| `401` | Unauthorized | Webhook ungültig, nicht wiederholen |
| `404` | Workflow nicht gefunden | URL ungültig, nicht wiederholen |
| `429` | Rate Limit | 1 Sekunde warten, dann wiederholen |
| `500` | Server-Fehler | Nach 30s wiederholen (max. 3x) |

---

## 6. Event-System

### 6.1 WordPress Actions

Die Teams-Integration registriert sich für folgende Actions:

```php
// IntegrationManager.php
add_action( 'rp_application_created', [ $teams, 'onNewApplication' ], 10, 1 );
add_action( 'rp_application_status_changed', [ $teams, 'onStatusChanged' ], 10, 3 );
add_action( 'publish_job_listing', [ $teams, 'onJobPublished' ], 10, 1 );
add_action( 'rp_deadline_reminder', [ $teams, 'onDeadlineReminder' ], 10, 1 );
```

### 6.2 Event-Handler

#### `onNewApplication( int $application_id )`

Wird ausgelöst wenn eine neue Bewerbung eingeht.

**Daten:**
```php
$data = [
    'candidate_name' => 'Maria Weber',
    'job_title'      => 'Pflegefachkraft (m/w/d)',
    'source'         => 'Website',
    'email'          => 'maria@example.com',
    'phone'          => '+49 123 456789',
    'link'           => 'https://example.com/wp-admin/...',
];
```

#### `onStatusChanged( int $application_id, string $old_status, string $new_status )`

Wird bei Status-Änderung ausgelöst.

**Daten:**
```php
$data = [
    'candidate_name' => 'Maria Weber',
    'job_title'      => 'Pflegefachkraft (m/w/d)',
    'old_status'     => 'new',
    'new_status'     => 'interview',
    'link'           => 'https://example.com/wp-admin/...',
];
```

#### `onJobPublished( int $job_id )`

Wird ausgelöst wenn eine Stelle veröffentlicht wird.

**Daten:**
```php
$data = [
    'job_title'    => 'Pflegefachkraft (m/w/d)',
    'location'     => 'Berlin',
    'employment'   => 'Vollzeit',
    'link'         => 'https://example.com/jobs/pflegefachkraft/',
    'admin_link'   => 'https://example.com/wp-admin/post.php?post=123&action=edit',
];
```

---

## 7. Nachrichtenformate

### 7.1 Adaptive Cards Schema

Teams verwendet **Adaptive Cards** (nicht Block Kit wie Slack).

**Wichtig:**
- Version `1.4` verwenden (aktuellster Standard)
- Schema: `http://adaptivecards.io/schemas/adaptive-card.json`
- Maximale Größe: 28 KB pro Card

**Adaptive Card Designer:**
[adaptivecards.io/designer](https://adaptivecards.io/designer/)

### 7.2 Neue Bewerbung

```json
{
    "type": "message",
    "attachments": [{
        "contentType": "application/vnd.microsoft.card.adaptive",
        "content": {
            "type": "AdaptiveCard",
            "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
            "version": "1.4",
            "body": [
                {
                    "type": "TextBlock",
                    "text": "📋 Neue Bewerbung",
                    "weight": "bolder",
                    "size": "large",
                    "color": "accent"
                },
                {
                    "type": "FactSet",
                    "facts": [
                        {
                            "title": "Bewerber:",
                            "value": "Maria Weber"
                        },
                        {
                            "title": "Stelle:",
                            "value": "Pflegefachkraft (m/w/d)"
                        },
                        {
                            "title": "Quelle:",
                            "value": "Website"
                        },
                        {
                            "title": "E-Mail:",
                            "value": "maria@example.com"
                        }
                    ]
                }
            ],
            "actions": [
                {
                    "type": "Action.OpenUrl",
                    "title": "Bewerbung ansehen",
                    "url": "https://example.com/wp-admin/..."
                }
            ]
        }
    }]
}
```

### 7.3 Status-Änderung

```json
{
    "type": "message",
    "attachments": [{
        "contentType": "application/vnd.microsoft.card.adaptive",
        "content": {
            "type": "AdaptiveCard",
            "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
            "version": "1.4",
            "body": [
                {
                    "type": "TextBlock",
                    "text": "🔄 Status-Änderung",
                    "weight": "bolder",
                    "size": "large",
                    "color": "warning"
                },
                {
                    "type": "FactSet",
                    "facts": [
                        {
                            "title": "Bewerber:",
                            "value": "Maria Weber"
                        },
                        {
                            "title": "Stelle:",
                            "value": "Pflegefachkraft (m/w/d)"
                        },
                        {
                            "title": "Status:",
                            "value": "Neu → Interview"
                        }
                    ]
                },
                {
                    "type": "TextBlock",
                    "text": "Der Bewerbungsstatus wurde von **Neu** auf **Interview** geändert.",
                    "wrap": true,
                    "spacing": "medium"
                }
            ],
            "actions": [
                {
                    "type": "Action.OpenUrl",
                    "title": "Bewerbung ansehen",
                    "url": "https://example.com/wp-admin/..."
                }
            ]
        }
    }]
}
```

### 7.4 Neue Stelle veröffentlicht

```json
{
    "type": "message",
    "attachments": [{
        "contentType": "application/vnd.microsoft.card.adaptive",
        "content": {
            "type": "AdaptiveCard",
            "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
            "version": "1.4",
            "body": [
                {
                    "type": "TextBlock",
                    "text": "🆕 Neue Stelle veröffentlicht",
                    "weight": "bolder",
                    "size": "large",
                    "color": "good"
                },
                {
                    "type": "FactSet",
                    "facts": [
                        {
                            "title": "Titel:",
                            "value": "Pflegefachkraft (m/w/d)"
                        },
                        {
                            "title": "Standort:",
                            "value": "Berlin"
                        },
                        {
                            "title": "Art:",
                            "value": "Vollzeit"
                        }
                    ]
                }
            ],
            "actions": [
                {
                    "type": "Action.OpenUrl",
                    "title": "Stelle ansehen",
                    "url": "https://example.com/jobs/pflegefachkraft/"
                },
                {
                    "type": "Action.OpenUrl",
                    "title": "Bearbeiten",
                    "url": "https://example.com/wp-admin/post.php?post=123&action=edit"
                }
            ]
        }
    }]
}
```

### 7.5 Adaptive Card Best Practices

| Element | Verwendung |
|---------|-----------|
| **TextBlock** | Überschriften, Beschreibungen |
| **FactSet** | Key-Value Paare (Bewerber, Stelle, etc.) |
| **ColumnSet** | Multi-Column Layouts |
| **Action.OpenUrl** | Direkt-Links (immer als Primary Action) |
| **Color** | `accent` (blau), `good` (grün), `warning` (orange), `attention` (rot) |

---

## 8. Fehlerbehandlung & Retry-Logic

### 8.1 Retry-Strategie

| Fehler | Retry? | Delay | Max. Versuche |
|--------|--------|-------|---------------|
| `429 Rate Limit` | ✅ Ja | 1s | 3 |
| `500 Server Error` | ✅ Ja | 30s | 3 |
| `502 Bad Gateway` | ✅ Ja | 30s | 3 |
| `400 Bad Request` | ❌ Nein | - | - |
| `401 Unauthorized` | ❌ Nein | - | - |
| `404 Not Found` | ❌ Nein | - | - |
| Network Timeout | ✅ Ja | 10s | 2 |

### 8.2 Retry-Queue (Transients)

Bei temporären Fehlern wird die Nachricht in einem Transient gespeichert:

```php
$retry_queue = get_transient( 'rp_teams_retry_queue' ) ?: [];

$retry_queue[] = [
    'payload'    => $payload,
    'attempt'    => 1,
    'next_retry' => time() + 30,
];

set_transient( 'rp_teams_retry_queue', $retry_queue, HOUR_IN_SECONDS );
```

Ein WP-Cron Job (`rp_teams_retry_cron`) verarbeitet die Queue.

### 8.3 Logging

Alle Webhook-Requests werden geloggt:

```php
ActivityService::log( [
    'type'         => 'teams_notification',
    'description'  => 'Teams-Nachricht gesendet: Neue Bewerbung',
    'metadata'     => [
        'event'      => 'new_application',
        'app_id'     => 123,
        'success'    => true,
        'http_code'  => 202,
    ],
] );
```

Bei Fehlern:

```php
ActivityService::log( [
    'type'         => 'teams_notification_failed',
    'description'  => 'Teams-Nachricht fehlgeschlagen: Rate Limit',
    'metadata'     => [
        'event'      => 'new_application',
        'error'      => 'rate_limit_exceeded',
        'http_code'  => 429,
        'retry'      => true,
    ],
] );
```

---

## 9. Admin-UI Integration

### 9.1 Settings-Tab "Integrationen"

Die UI ist bereits vollständig in `IntegrationSettings.jsx` implementiert:

**Features:**
- ✅ Toggle für Teams aktivieren/deaktivieren
- ✅ Workflow-Webhook-URL Eingabefeld
- ✅ Event-Checkboxen (4 Events)
- ✅ Test-Nachricht senden Button
- ✅ Setup-Anleitung (Info-Alert)
- ✅ Success/Error-Alerts
- ✅ Pro-Badge & Feature-Lock für Free-User

### 9.2 Setup-Anleitung (UI)

```jsx
<Alert>
    <AlertTitle>ℹ️ Anleitung</AlertTitle>
    <AlertDescription>
        1. Teams → Channel → <strong>...</strong> (Mehr Optionen)
        <br/>
        2. <strong>Workflows</strong> → <strong>Workflow erstellen</strong>
        <br/>
        3. Vorlage: <em>"Beim Empfang einer Teams-Webhookanforderung"</em>
        <br/>
        4. Workflow-URL kopieren und hier einfügen
    </AlertDescription>
</Alert>
```

### 9.3 Webhook-URL Validierung

Frontend (React):
```jsx
const isValidTeamsWebhookUrl = (url) => {
    return url.includes('.logic.azure.com/workflows/');
};
```

Backend (PHP):
```php
private function validateWebhookUrl( string $url ): bool {
    if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return false;
    }

    $parsed = wp_parse_url( $url );

    // Muss *.logic.azure.com sein
    if ( ! str_ends_with( $parsed['host'], '.logic.azure.com' ) ) {
        return false;
    }

    // Pfad muss /workflows/ enthalten
    if ( ! str_contains( $parsed['path'], '/workflows/' ) ) {
        return false;
    }

    return true;
}
```

---

## 10. Testing

### 10.1 Unit Tests (PHPUnit)

```php
// tests/Integration/TeamsNotifierTest.php

class TeamsNotifierTest extends TestCase {
    public function test_builds_new_application_adaptive_card() {
        $notifier = new TeamsNotifier( [ 'teams_webhook_url' => 'https://...' ] );

        $card = $notifier->buildAdaptiveCard( [
            'event'          => 'new_application',
            'candidate_name' => 'Maria Weber',
            'job_title'      => 'Pflegefachkraft (m/w/d)',
        ] );

        $this->assertIsArray( $card );
        $this->assertEquals( 'AdaptiveCard', $card['type'] );
        $this->assertEquals( '1.4', $card['version'] );
        $this->assertArrayHasKey( 'body', $card );
        $this->assertArrayHasKey( 'actions', $card );
    }

    public function test_sends_webhook_request() {
        // Mock wp_remote_post
        Mockery::mock( 'alias:wp_remote_post' )
            ->shouldReceive( 'wp_remote_post' )
            ->once()
            ->andReturn( [ 'response' => [ 'code' => 202 ] ] );

        $notifier = new TeamsNotifier( [ 'teams_webhook_url' => 'https://...' ] );
        $result = $notifier->sendWebhook( [ 'type' => 'message' ] );

        $this->assertTrue( $result );
    }

    public function test_validates_webhook_url() {
        $notifier = new TeamsNotifier( [ 'teams_webhook_url' => '' ] );

        // Valid URL
        $valid = 'https://prod-45.westeurope.logic.azure.com/workflows/abc123/triggers/manual/paths/invoke?api-version=2016-06-01';
        $this->assertTrue( $notifier->validateWebhookUrl( $valid ) );

        // Invalid URLs
        $this->assertFalse( $notifier->validateWebhookUrl( 'https://example.com/webhook' ) );
        $this->assertFalse( $notifier->validateWebhookUrl( 'not-a-url' ) );
    }
}
```

### 10.2 Integration Tests

**Test-Plan:**

1. ✅ **Webhook-URL Validierung**
   - Gültige URL akzeptiert (*.logic.azure.com)
   - Ungültige URL abgelehnt
   - Nur Azure Logic Apps erlaubt

2. ✅ **Event-Auslösung**
   - Neue Bewerbung triggert Teams-Nachricht
   - Status-Änderung triggert Teams-Nachricht
   - Deaktivierte Events senden keine Nachricht

3. ✅ **Adaptive Cards**
   - Korrekte JSON-Struktur
   - Version 1.4
   - Valide FactSets, Actions

4. ✅ **Retry-Logic**
   - 429 triggert Retry
   - 500 triggert Retry
   - 400 triggert kein Retry

5. ✅ **Test-Button**
   - Sendet Test-Nachricht
   - Zeigt Success-Alert bei Erfolg
   - Zeigt Error-Alert bei Fehler

---

## 11. Sicherheit

### 11.1 Webhook-URL Speicherung

Webhook-URLs sind **sensibel** (enthalten Tokens/Signatures).

**Schutz:**
- Nur `manage_options` kann URLs speichern
- URLs werden **NICHT** in REST API Responses an Frontend ausgegeben (nur Platzhalter)
- URLs werden in `wp_options` gespeichert (nur Admin-Zugriff)

```php
// REST Response (Frontend)
$response = [
    'teams_webhook_url' => $this->maskWebhookUrl( $settings['teams_webhook_url'] ),
];

private function maskWebhookUrl( string $url ): string {
    if ( empty( $url ) ) {
        return '';
    }

    $parsed = wp_parse_url( $url );

    // Zeige nur Host + /workflows/***
    $path_parts = explode( '/', trim( $parsed['path'], '/' ) );
    if ( count( $path_parts ) > 2 ) {
        $path_parts[1] = str_repeat( '*', strlen( $path_parts[1] ) );
    }

    // Query-String vollständig maskieren
    return $parsed['scheme'] . '://' . $parsed['host'] . '/' . implode( '/', $path_parts ) . '?***';
}
```

### 11.2 SSRF-Schutz

Verhindere Server-Side Request Forgery:

```php
private function isAllowedWebhookUrl( string $url ): bool {
    $parsed = wp_parse_url( $url );

    // Nur *.logic.azure.com erlauben
    if ( ! str_ends_with( $parsed['host'], '.logic.azure.com' ) ) {
        return false;
    }

    // Keine lokalen IPs
    $ip = gethostbyname( $parsed['host'] );
    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
        return false;
    }

    return true;
}
```

### 11.3 Rate Limiting

Schütze vor zu vielen Webhook-Requests:

```php
private function checkRateLimit(): bool {
    $key = 'rp_teams_last_send';
    $last = get_transient( $key );

    // Teams Rate Limit: 4 Nachrichten/Sekunde
    // Sicherheitsabstand: 1 Nachricht/Sekunde
    if ( $last && ( time() - $last ) < 1 ) {
        return false;
    }

    set_transient( $key, time(), 10 );
    return true;
}
```

---

## Implementierungs-Checkliste

### Phase 1: Backend-Grundlage

- [ ] `IntegrationController.php` - Teams Test-Endpoint hinzufügen
- [ ] `TeamsNotifier.php` - Basis-Klasse erstellen (extends NotificationService)
- [ ] Webhook-URL Validierung (Azure Logic Apps)
- [ ] Webhook-URL Maskierung (Security)

### Phase 2: Event-Handler

- [ ] `TeamsNotifier::onNewApplication()`
- [ ] `TeamsNotifier::onStatusChanged()`
- [ ] `TeamsNotifier::onJobPublished()`
- [ ] `TeamsNotifier::buildAdaptiveCard()` - Message Formatting
- [ ] Adaptive Card Templates (3 Event-Typen)

### Phase 3: Webhook-Integration

- [ ] `TeamsNotifier::sendWebhook()` - HTTP POST
- [ ] Webhook-URL Validierung (SSRF Protection)
- [ ] Error Handling & Activity Logging
- [ ] Retry-Logic mit Exponential Backoff (30s, 60s, 90s)
- [ ] Rate Limiting (1 msg/sec)

### Phase 4: Integration Manager

- [ ] `IntegrationManager.php` - Teams Hook-Registrierung
- [ ] Feature-Flag Checks (`rp_can('integrations')`)
- [ ] Settings laden aus `rp_integrations` Option
- [ ] WP Cron-Job für Retry-Queue (hourly)

### Phase 5: Admin-UI

- [ ] `TeamsCard.jsx` - React-Komponente erstellen
- [ ] Workflow-Webhook-URL Eingabefeld
- [ ] Setup-Anleitung Alert
- [ ] Event-Checkboxen (4 Events)
- [ ] Test-Button Integration
- [ ] Pro-Badge & Feature-Lock

### Phase 6: Testing

- [ ] PHPUnit Tests für TeamsNotifier
- [ ] Webhook-URL Validierung Tests
- [ ] Adaptive Card JSON Struktur Tests
- [ ] Error-Szenarien testen (400, 429, 500)
- [ ] Test-Button manuell testen (echter Teams Channel)

---

## Unterschiede zu Slack

| Aspekt | Slack | Microsoft Teams |
|--------|-------|-----------------|
| **Nachrichtenformat** | Block Kit (Blocks) | Adaptive Cards |
| **Webhook-Typ** | Incoming Webhooks | Power Automate Workflows |
| **URL-Format** | `hooks.slack.com/services/...` | `*.logic.azure.com/workflows/...` |
| **Rate Limit** | 1 msg/sec | 4 msg/sec (aber 1/sec empfohlen) |
| **Success Code** | 200 OK | 202 Accepted |
| **Setup** | Slack App + Webhook | Teams Workflow (kein App nötig) |
| **Deprecation** | - | O365 Connectors bis 30.04.2026 |

---

## Referenzen

### Microsoft Teams Dokumentation

- [Adaptive Cards Designer](https://adaptivecards.io/designer/)
- [Adaptive Cards Schema](https://adaptivecards.io/explorer/)
- [Teams Workflows (Power Automate)](https://learn.microsoft.com/en-us/power-automate/teams/overview)
- [Webhook Trigger für Teams](https://learn.microsoft.com/en-us/power-automate/triggers-introduction)

### Office 365 Connectors Deprecation

- [Retirement Announcement](https://devblogs.microsoft.com/microsoft365dev/retirement-of-office-365-connectors-within-microsoft-teams/)
- Migration Deadline: **30. April 2026**
- Alternative: **Workflows for Microsoft Teams** (diese Implementierung)

---

*Erstellt: 17. Februar 2026*
*Status: ⏳ Spezifikation fertig, Implementierung ausstehend*

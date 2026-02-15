# Slack-Integration: Technische Spezifikation

> **Pro-Feature: Slack Benachrichtigungen**
> Real-time Notifications für Bewerbungen und Recruiting-Events in Slack

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Datenmodell](#3-datenmodell)
4. [REST API Endpunkte](#4-rest-api-endpunkte)
5. [Slack Webhook Integration](#5-slack-webhook-integration)
6. [Event-System](#6-event-system)
7. [Nachrichtenformate](#7-nachrichtenformate)
8. [Fehlerbehandlung & Retry-Logic](#8-fehlerbehandlung--retry-logic)
9. [Admin-UI Integration](#9-admin-ui-integration)
10. [Testing](#10-testing)
11. [Sicherheit](#11-sicherheit)

---

## 1. Übersicht

### Zielsetzung

Die Slack-Integration ermöglicht:
- **Real-time Benachrichtigungen** bei wichtigen Recruiting-Events
- **Team-Kommunikation** ohne E-Mail-Overhead
- **Zentrale Übersicht** aller Bewerbungsaktivitäten in Slack
- **Direkt-Links** zu Bewerbungen im WordPress-Admin

### Feature-Gating

```php
// Pro-Feature Check
if ( ! rp_can( 'integrations' ) ) {
    rp_require_feature( 'integrations', 'Slack-Benachrichtigungen', 'PRO' );
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
| Recruiter | bei neuer Bewerbung in Slack benachrichtigt werden | ich schnell reagieren kann |
| HR-Manager | Status-Änderungen in Slack sehen | das Team informiert ist |
| Team-Lead | neue Stellenausschreibungen in Slack teilen | das Recruiting-Team informiert ist |
| Recruiter | direkt aus Slack zur Bewerbung springen | ich schnellen Zugriff habe |

### Unterstützte Events

| Event | Auslöser | Standard |
|-------|----------|----------|
| **Neue Bewerbung** | `rp_application_created` | ✅ Aktiv |
| **Status-Änderung** | `rp_application_status_changed` | ✅ Aktiv |
| **Stelle veröffentlicht** | `publish_job_listing` | ❌ Inaktiv |
| **Bewerbungsfrist läuft ab** | Cron (3 Tage vorher) | ❌ Inaktiv |

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
│   │       ├── SlackNotifier.php           # Slack-spezifische Implementierung
│   │       └── TeamsNotifier.php           # Teams (für später)
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
│                   │       └── SlackCard.jsx            # Slack-Konfiguration
│                   └── hooks/
│                       └── useIntegrations.js           # Hook (bereits vorhanden)
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| HTTP Client | `wp_remote_post()` (WordPress HTTP API) |
| Webhook Format | Slack Incoming Webhooks (Block Kit) |
| Event-System | WordPress Actions (`do_action`) |
| Retry-Mechanik | WordPress Transients + Cron |
| Logging | `error_log()` + Activity Log |
| Rate Limiting | 1 Nachricht/Sekunde (Slack-Limit) |

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
│   SlackNotifier                      │
│   - webhook_url: string              │
│   - settings: array                  │
│   + onNewApplication(int $app_id)    │
│   + onStatusChanged(...)             │
│   + onJobPublished(int $job_id)      │
│   + sendWebhook(array $payload)      │
│   + buildBlocks(array $data)         │
└─────────────────────────────────────┘
```

---

## 3. Datenmodell

### WordPress Options

Alle Einstellungen werden in einer Option gespeichert:

```php
$defaults = [
    // Slack (Pro)
    'slack_enabled'                 => false,
    'slack_webhook_url'             => '',
    'slack_event_new_application'   => true,
    'slack_event_status_changed'    => true,
    'slack_event_job_published'     => false,
    'slack_event_deadline_reminder' => false,
];

// Gespeichert als:
update_option( 'rp_integrations', $settings );
```

### Keine neuen Datenbank-Tabellen

Die Slack-Integration benötigt keine eigenen Tabellen. Alle Daten werden über:
- **`rp_integrations` Option** (Settings)
- **WordPress Transients** (Retry-Queue)
- **`rp_activity_log` Tabelle** (Logging, bereits vorhanden)

---

## 4. REST API Endpunkte

### 4.1 Settings-Endpunkte

#### GET `/recruiting/v1/settings/integrations`

Lädt alle Integrations-Einstellungen.

**Response:**
```json
{
    "slack_enabled": true,
    "slack_webhook_url": "https://hooks.slack.com/services/T.../B.../xxx",
    "slack_event_new_application": true,
    "slack_event_status_changed": true,
    "slack_event_job_published": false,
    "slack_event_deadline_reminder": false
}
```

#### POST `/recruiting/v1/settings/integrations`

Speichert Integrations-Einstellungen.

**Request:**
```json
{
    "slack_enabled": true,
    "slack_webhook_url": "https://hooks.slack.com/services/T.../B.../xxx",
    "slack_event_new_application": true,
    "slack_event_status_changed": false
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

#### POST `/recruiting/v1/integrations/slack/test`

Sendet eine Test-Nachricht an den konfigurierten Slack-Webhook.

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

**Testinhalt:**
```json
{
    "blocks": [
        {
            "type": "section",
            "text": {
                "type": "mrkdwn",
                "text": "✅ *Test-Nachricht*\n\nDie Slack-Integration ist korrekt konfiguriert!"
            }
        }
    ]
}
```

---

## 5. Slack Webhook Integration

### 5.1 Webhook-URL Format

Slack Incoming Webhooks haben folgendes Format:

```
https://hooks.slack.com/services/T{WORKSPACE_ID}/B{CHANNEL_ID}/{TOKEN}
```

Beispiel:
```
https://hooks.slack.com/services/T{WORKSPACE}/B{CHANNEL}/{SECRET_TOKEN}
```

### 5.2 HTTP-Request

**Methode:** `POST`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "blocks": [ /* Block Kit Blocks */ ],
    "text": "Fallback-Text für Notifications"
}
```

### 5.3 Response-Codes

| Code | Bedeutung | Aktion |
|------|-----------|--------|
| `200` | Erfolg | Nachricht gesendet |
| `400` | Ungültige Payload | Fehler loggen, nicht wiederholen |
| `404` | Webhook nicht gefunden | URL ungültig, nicht wiederholen |
| `429` | Rate Limit | 1 Sekunde warten, dann wiederholen |
| `500` | Slack-Server-Fehler | Nach 30s wiederholen (max. 3x) |

---

## 6. Event-System

### 6.1 WordPress Actions

Die Slack-Integration registriert sich für folgende Actions:

```php
// IntegrationManager.php
add_action( 'rp_application_created', [ $slack, 'onNewApplication' ], 10, 1 );
add_action( 'rp_application_status_changed', [ $slack, 'onStatusChanged' ], 10, 3 );
add_action( 'publish_job_listing', [ $slack, 'onJobPublished' ], 10, 1 );
add_action( 'rp_deadline_reminder', [ $slack, 'onDeadlineReminder' ], 10, 1 );
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

### 7.1 Neue Bewerbung

```json
{
    "text": "Neue Bewerbung: Maria Weber für Pflegefachkraft (m/w/d)",
    "blocks": [
        {
            "type": "section",
            "text": {
                "type": "mrkdwn",
                "text": "📋 *Neue Bewerbung*"
            }
        },
        {
            "type": "section",
            "fields": [
                {
                    "type": "mrkdwn",
                    "text": "*Bewerber:*\nMaria Weber"
                },
                {
                    "type": "mrkdwn",
                    "text": "*Stelle:*\nPflegefachkraft (m/w/d)"
                },
                {
                    "type": "mrkdwn",
                    "text": "*Quelle:*\nWebsite"
                },
                {
                    "type": "mrkdwn",
                    "text": "*E-Mail:*\nmaria@example.com"
                }
            ]
        },
        {
            "type": "actions",
            "elements": [
                {
                    "type": "button",
                    "text": {
                        "type": "plain_text",
                        "text": "Bewerbung ansehen"
                    },
                    "url": "https://example.com/wp-admin/...",
                    "style": "primary"
                }
            ]
        }
    ]
}
```

### 7.2 Status-Änderung

```json
{
    "text": "Status geändert: Maria Weber → Interview",
    "blocks": [
        {
            "type": "section",
            "text": {
                "type": "mrkdwn",
                "text": "🔄 *Status-Änderung*"
            }
        },
        {
            "type": "section",
            "fields": [
                {
                    "type": "mrkdwn",
                    "text": "*Bewerber:*\nMaria Weber"
                },
                {
                    "type": "mrkdwn",
                    "text": "*Stelle:*\nPflegefachkraft (m/w/d)"
                },
                {
                    "type": "mrkdwn",
                    "text": "*Status:*\n~Neu~ → *Interview*"
                }
            ]
        },
        {
            "type": "actions",
            "elements": [
                {
                    "type": "button",
                    "text": {
                        "type": "plain_text",
                        "text": "Bewerbung ansehen"
                    },
                    "url": "https://example.com/wp-admin/..."
                }
            ]
        }
    ]
}
```

### 7.3 Neue Stelle veröffentlicht

```json
{
    "text": "Neue Stelle: Pflegefachkraft (m/w/d) in Berlin",
    "blocks": [
        {
            "type": "section",
            "text": {
                "type": "mrkdwn",
                "text": "🆕 *Neue Stelle veröffentlicht*"
            }
        },
        {
            "type": "section",
            "fields": [
                {
                    "type": "mrkdwn",
                    "text": "*Titel:*\nPflegefachkraft (m/w/d)"
                },
                {
                    "type": "mrkdwn",
                    "text": "*Standort:*\nBerlin"
                },
                {
                    "type": "mrkdwn",
                    "text": "*Art:*\nVollzeit"
                }
            ]
        },
        {
            "type": "actions",
            "elements": [
                {
                    "type": "button",
                    "text": {
                        "type": "plain_text",
                        "text": "Stelle ansehen"
                    },
                    "url": "https://example.com/jobs/pflegefachkraft/"
                },
                {
                    "type": "button",
                    "text": {
                        "type": "plain_text",
                        "text": "Bearbeiten"
                    },
                    "url": "https://example.com/wp-admin/post.php?post=123&action=edit"
                }
            ]
        }
    ]
}
```

---

## 8. Fehlerbehandlung & Retry-Logic

### 8.1 Retry-Strategie

| Fehler | Retry? | Delay | Max. Versuche |
|--------|--------|-------|---------------|
| `429 Rate Limit` | ✅ Ja | 1s | 3 |
| `500 Server Error` | ✅ Ja | 30s | 3 |
| `400 Bad Request` | ❌ Nein | - | - |
| `404 Not Found` | ❌ Nein | - | - |
| Network Timeout | ✅ Ja | 10s | 2 |

### 8.2 Retry-Queue (Transients)

Bei temporären Fehlern wird die Nachricht in einem Transient gespeichert:

```php
$retry_queue = get_transient( 'rp_slack_retry_queue' ) ?: [];

$retry_queue[] = [
    'payload'    => $payload,
    'attempt'    => 1,
    'next_retry' => time() + 30,
];

set_transient( 'rp_slack_retry_queue', $retry_queue, HOUR_IN_SECONDS );
```

Ein WP-Cron Job (`rp_slack_retry_cron`) verarbeitet die Queue.

### 8.3 Logging

Alle Webhook-Requests werden geloggt:

```php
ActivityService::log( [
    'type'         => 'slack_notification',
    'description'  => 'Slack-Nachricht gesendet: Neue Bewerbung',
    'metadata'     => [
        'event'      => 'new_application',
        'app_id'     => 123,
        'success'    => true,
        'http_code'  => 200,
    ],
] );
```

Bei Fehlern:

```php
ActivityService::log( [
    'type'         => 'slack_notification_failed',
    'description'  => 'Slack-Nachricht fehlgeschlagen: Rate Limit',
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
- ✅ Toggle für Slack aktivieren/deaktivieren
- ✅ Webhook-URL Eingabefeld
- ✅ Event-Checkboxen (4 Events)
- ✅ Test-Nachricht senden Button
- ✅ Success/Error-Alerts
- ✅ Pro-Badge & Feature-Lock für Free-User

### 9.2 Webhook-URL Validierung

Frontend (React):
```jsx
const isValidWebhookUrl = (url) => {
    return url.startsWith('https://hooks.slack.com/services/');
};
```

Backend (PHP):
```php
private function validateWebhookUrl( string $url ): bool {
    if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return false;
    }

    $parsed = wp_parse_url( $url );

    return $parsed['host'] === 'hooks.slack.com'
        && str_starts_with( $parsed['path'], '/services/' );
}
```

---

## 10. Testing

### 10.1 Unit Tests (PHPUnit)

```php
// tests/Integration/SlackNotifierTest.php

class SlackNotifierTest extends TestCase {
    public function test_builds_new_application_blocks() {
        $notifier = new SlackNotifier( [ 'slack_webhook_url' => 'https://...' ] );

        $blocks = $notifier->buildBlocks( [
            'event'          => 'new_application',
            'candidate_name' => 'Maria Weber',
            'job_title'      => 'Pflegefachkraft (m/w/d)',
        ] );

        $this->assertIsArray( $blocks );
        $this->assertCount( 3, $blocks );
        $this->assertEquals( 'section', $blocks[0]['type'] );
    }

    public function test_sends_webhook_request() {
        // Mock wp_remote_post
        Mockery::mock( 'alias:wp_remote_post' )
            ->shouldReceive( 'wp_remote_post' )
            ->once()
            ->andReturn( [ 'response' => [ 'code' => 200 ] ] );

        $notifier = new SlackNotifier( [ 'slack_webhook_url' => 'https://...' ] );
        $result = $notifier->sendWebhook( [ 'text' => 'Test' ] );

        $this->assertTrue( $result );
    }
}
```

### 10.2 Integration Tests

**Test-Plan:**

1. ✅ **Webhook-URL Validierung**
   - Gültige URL akzeptiert
   - Ungültige URL abgelehnt
   - Nur `hooks.slack.com` erlaubt

2. ✅ **Event-Auslösung**
   - Neue Bewerbung triggert Slack-Nachricht
   - Status-Änderung triggert Slack-Nachricht
   - Deaktivierte Events senden keine Nachricht

3. ✅ **Retry-Logic**
   - 429 triggert Retry
   - 500 triggert Retry
   - 400 triggert kein Retry

4. ✅ **Test-Button**
   - Sendet Test-Nachricht
   - Zeigt Success-Alert bei Erfolg
   - Zeigt Error-Alert bei Fehler

---

## 11. Sicherheit

### 11.1 Webhook-URL Speicherung

Webhook-URLs sind **sensibel** (enthalten Tokens).

**Schutz:**
- Nur `manage_options` kann URLs speichern
- URLs werden **NICHT** in REST API Responses an Frontend ausgegeben (nur Platzhalter)
- URLs werden in `wp_options` gespeichert (nur Admin-Zugriff)

```php
// REST Response (Frontend)
$response = [
    'slack_webhook_url' => $this->maskWebhookUrl( $settings['slack_webhook_url'] ),
];

private function maskWebhookUrl( string $url ): string {
    if ( empty( $url ) ) {
        return '';
    }

    $parsed = wp_parse_url( $url );
    $path = $parsed['path'];

    // Zeige nur ersten Teil: /services/T.../B.../***
    $parts = explode( '/', trim( $path, '/' ) );
    if ( count( $parts ) === 4 ) {
        $parts[3] = str_repeat( '*', strlen( $parts[3] ) );
    }

    return $parsed['scheme'] . '://' . $parsed['host'] . '/' . implode( '/', $parts );
}
```

### 11.2 SSRF-Schutz

Verhindere Server-Side Request Forgery:

```php
private function isAllowedWebhookUrl( string $url ): bool {
    $parsed = wp_parse_url( $url );

    // Nur hooks.slack.com erlauben
    if ( $parsed['host'] !== 'hooks.slack.com' ) {
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
    $key = 'rp_slack_last_send';
    $last = get_transient( $key );

    if ( $last && ( time() - $last ) < 1 ) {
        // Mindestens 1 Sekunde zwischen Nachrichten
        return false;
    }

    set_transient( $key, time(), 10 );
    return true;
}
```

---

## Implementierungs-Checkliste

### Phase 1: Backend-Grundlage ✅

- [x] `IntegrationController.php` - Settings GET/POST
- [x] `IntegrationController.php` - Test-Endpoint
- [ ] `SlackNotifier.php` - Basis-Klasse
- [ ] `NotificationService.php` - Abstract Base

### Phase 2: Event-Handler

- [ ] `SlackNotifier::onNewApplication()`
- [ ] `SlackNotifier::onStatusChanged()`
- [ ] `SlackNotifier::onJobPublished()`
- [ ] `SlackNotifier::buildBlocks()` - Message Formatting

### Phase 3: Webhook-Integration

- [ ] `SlackNotifier::sendWebhook()` - HTTP POST
- [ ] Webhook-URL Validierung
- [ ] Error Handling & Logging
- [ ] Retry-Logic mit Transients

### Phase 4: Integration Manager

- [ ] `IntegrationManager.php` - Hook-Registrierung
- [ ] Feature-Flag Checks
- [ ] Settings laden bei Plugin-Init
- [ ] Cron-Job für Retry-Queue

### Phase 5: Testing

- [ ] PHPUnit Tests für SlackNotifier
- [ ] Manual Testing mit echtem Slack-Workspace
- [ ] Error-Szenarien testen (404, 429, 500)
- [ ] Test-Button im Admin testen

---

*Erstellt: 15. Februar 2026*
*Branch: `slag`*
*Status: In Entwicklung*

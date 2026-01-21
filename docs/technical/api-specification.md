# REST API Spezifikation

## Übersicht

Das Plugin bietet eine vollständige REST API, die es externen Systemen ermöglicht:

- Stellenanzeigen zu lesen, erstellen und verwalten
- Bewerbungen abzurufen und Status zu aktualisieren
- Bewerber-Daten zu exportieren
- Events via Webhooks zu empfangen

**Basis-URL:** `https://example.com/wp-json/recruiting/v1/`

**Authentifizierung:** WordPress Application Passwords oder API-Keys (Pro)

---

## Authentifizierung

### Option 1: WordPress Application Passwords (Standard)

Seit WordPress 5.6 integriert. Einfachste Lösung.

```bash
# Beispiel mit curl
curl -X GET \
  https://example.com/wp-json/recruiting/v1/jobs \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx"
```

### Option 2: API-Key (Pro)

Dedizierte API-Keys im Plugin-Backend generierbar.

```bash
# Header-basiert
curl -X GET \
  https://example.com/wp-json/recruiting/v1/jobs \
  -H "X-Recruiting-API-Key: rp_live_abc123..."

# Oder als Query-Parameter
curl -X GET \
  "https://example.com/wp-json/recruiting/v1/jobs?api_key=rp_live_abc123..."
```

### API-Key-Verwaltung (Pro)

```
Plugin-Backend → Einstellungen → API

┌─────────────────────────────────────────────────────────────┐
│ API-Schlüssel                                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Name                    Schlüssel           Erstellt        │
│ ──────────────────────────────────────────────────────────  │
│ Eigenentwicklung        rp_live_abc1...     15.01.2025     │
│                         [Anzeigen] [Löschen]               │
│                                                             │
│ Test-System             rp_test_xyz9...     10.01.2025     │
│                         [Anzeigen] [Löschen]               │
│                                                             │
│ [+ Neuen API-Key erstellen]                                │
│                                                             │
│ Berechtigungen pro Key:                                    │
│ ☑ Jobs lesen        ☑ Jobs schreiben                       │
│ ☑ Bewerbungen lesen ☐ Bewerbungen schreiben                │
│ ☐ Einstellungen                                            │
└─────────────────────────────────────────────────────────────┘
```

---

## Endpoints

### Jobs (Stellenanzeigen)

#### Liste aller Stellen

```
GET /wp-json/recruiting/v1/jobs
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `status` | string | `draft`, `publish`, `archived` (default: `publish`) |
| `per_page` | int | Ergebnisse pro Seite (default: 10, max: 100) |
| `page` | int | Seitennummer |
| `search` | string | Volltextsuche in Titel/Beschreibung |
| `location` | string | Filter nach Standort |
| `employment_type` | string | `fulltime`, `parttime`, `minijob`, `temporary` |
| `orderby` | string | `date`, `title`, `modified` (default: `date`) |
| `order` | string | `asc`, `desc` (default: `desc`) |

**Response:**

```json
{
  "data": [
    {
      "id": 123,
      "title": "Pflegefachkraft (m/w/d)",
      "slug": "pflegefachkraft-mwd",
      "status": "publish",
      "description": "<p>Wir suchen...</p>",
      "description_plain": "Wir suchen...",
      "excerpt": "Wir suchen eine engagierte Pflegefachkraft...",
      "location": {
        "city": "Berlin",
        "postal_code": "10115",
        "country": "DE",
        "remote": false
      },
      "employment_type": "fulltime",
      "salary": {
        "min": 3200,
        "max": 4000,
        "currency": "EUR",
        "period": "month",
        "display": "3.200 € - 4.000 € / Monat"
      },
      "contact": {
        "name": "Maria Schmidt",
        "email": "jobs@example.com",
        "phone": "+49 30 123456"
      },
      "application_deadline": "2025-03-20",
      "start_date": "2025-04-01",
      "categories": ["Pflege", "Gesundheit"],
      "tags": ["Examiniert", "Schichtdienst"],
      "application_count": 12,
      "created_at": "2025-01-15T10:30:00Z",
      "updated_at": "2025-01-18T14:20:00Z",
      "published_at": "2025-01-15T12:00:00Z",
      "url": "https://example.com/jobs/pflegefachkraft-mwd/",
      "apply_url": "https://example.com/jobs/pflegefachkraft-mwd/#apply"
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 5
  }
}
```

#### Einzelne Stelle abrufen

```
GET /wp-json/recruiting/v1/jobs/{id}
```

**Response:** Einzelnes Job-Objekt (wie oben)

#### Neue Stelle erstellen

```
POST /wp-json/recruiting/v1/jobs
```

**Request Body:**

```json
{
  "title": "Pflegefachkraft (m/w/d)",
  "description": "<p>Ihre Aufgaben...</p>",
  "status": "draft",
  "location": {
    "city": "Berlin",
    "postal_code": "10115",
    "country": "DE"
  },
  "employment_type": "fulltime",
  "salary": {
    "min": 3200,
    "max": 4000,
    "currency": "EUR",
    "period": "month"
  },
  "application_deadline": "2025-03-20",
  "categories": ["Pflege"],
  "custom_fields": {
    "department": "Station 3",
    "required_experience": "2 Jahre"
  }
}
```

**Response:** Erstelltes Job-Objekt mit `id`

#### Stelle aktualisieren

```
PUT /wp-json/recruiting/v1/jobs/{id}
```

**Request Body:** Nur die zu ändernden Felder

```json
{
  "status": "publish",
  "salary": {
    "min": 3400,
    "max": 4200
  }
}
```

#### Stelle löschen

```
DELETE /wp-json/recruiting/v1/jobs/{id}
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `force` | bool | `true` = endgültig löschen, `false` = archivieren (default) |

---

### Applications (Bewerbungen)

#### Liste aller Bewerbungen

```
GET /wp-json/recruiting/v1/applications
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `job_id` | int | Filter nach Stelle |
| `status` | string | `new`, `screening`, `interview`, `offer`, `hired`, `rejected` |
| `per_page` | int | Ergebnisse pro Seite (default: 20, max: 100) |
| `page` | int | Seitennummer |
| `search` | string | Suche in Name, E-Mail |
| `date_from` | date | Bewerbungen ab Datum (ISO 8601) |
| `date_to` | date | Bewerbungen bis Datum |
| `orderby` | string | `date`, `name`, `status` |
| `order` | string | `asc`, `desc` |

**Response:**

```json
{
  "data": [
    {
      "id": 456,
      "job": {
        "id": 123,
        "title": "Pflegefachkraft (m/w/d)"
      },
      "status": "screening",
      "status_history": [
        {
          "status": "new",
          "changed_at": "2025-01-16T09:00:00Z",
          "changed_by": null
        },
        {
          "status": "screening",
          "changed_at": "2025-01-17T11:30:00Z",
          "changed_by": {
            "id": 5,
            "name": "Maria Schmidt"
          }
        }
      ],
      "candidate": {
        "salutation": "Herr",
        "first_name": "Max",
        "last_name": "Mustermann",
        "email": "max@example.com",
        "phone": "+49 170 1234567",
        "address": {
          "street": "Musterstraße 1",
          "postal_code": "10115",
          "city": "Berlin",
          "country": "DE"
        }
      },
      "documents": [
        {
          "id": 789,
          "type": "cv",
          "filename": "lebenslauf_mustermann.pdf",
          "mime_type": "application/pdf",
          "size": 245678,
          "url": "https://example.com/wp-json/recruiting/v1/applications/456/documents/789"
        },
        {
          "id": 790,
          "type": "certificate",
          "filename": "examen_zeugnis.pdf",
          "mime_type": "application/pdf",
          "size": 156234,
          "url": "https://example.com/wp-json/recruiting/v1/applications/456/documents/790"
        }
      ],
      "cover_letter": "Sehr geehrte Damen und Herren...",
      "custom_fields": {
        "earliest_start": "2025-04-01",
        "salary_expectation": "3500",
        "drivers_license": true
      },
      "rating": 4,
      "notes": [
        {
          "id": 101,
          "content": "Telefonat geführt, sehr motiviert",
          "author": {
            "id": 5,
            "name": "Maria Schmidt"
          },
          "created_at": "2025-01-17T14:00:00Z"
        }
      ],
      "consent": {
        "privacy_policy": true,
        "privacy_policy_version": "2025-01",
        "talent_pool": false,
        "consented_at": "2025-01-16T08:55:00Z",
        "ip_address": "192.168.1.1"
      },
      "source": "website",
      "created_at": "2025-01-16T09:00:00Z",
      "updated_at": "2025-01-17T14:00:00Z"
    }
  ],
  "meta": {
    "total": 156,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 8
  }
}
```

#### Einzelne Bewerbung abrufen

```
GET /wp-json/recruiting/v1/applications/{id}
```

#### Bewerbungsstatus ändern

```
PUT /wp-json/recruiting/v1/applications/{id}/status
```

**Request Body:**

```json
{
  "status": "interview",
  "note": "Einladung zum Vorstellungsgespräch am 25.01."
}
```

**Erlaubte Status-Werte:**

| Status | Beschreibung |
|--------|--------------|
| `new` | Neu eingegangen |
| `screening` | In Prüfung |
| `interview` | Zum Gespräch eingeladen |
| `offer` | Angebot unterbreitet |
| `hired` | Eingestellt |
| `rejected` | Abgelehnt |
| `withdrawn` | Vom Bewerber zurückgezogen |

#### Notiz hinzufügen

```
POST /wp-json/recruiting/v1/applications/{id}/notes
```

**Request Body:**

```json
{
  "content": "Zweites Gespräch mit Teamleitung vereinbart",
  "internal": true
}
```

#### Bewertung setzen

```
PUT /wp-json/recruiting/v1/applications/{id}/rating
```

**Request Body:**

```json
{
  "rating": 5,
  "criteria": {
    "qualification": 5,
    "experience": 4,
    "impression": 5
  }
}
```

#### Dokument abrufen

```
GET /wp-json/recruiting/v1/applications/{id}/documents/{document_id}
```

Gibt die Datei als Download zurück.

#### Bewerbung exportieren (DSGVO)

```
GET /wp-json/recruiting/v1/applications/{id}/export
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `format` | string | `json`, `pdf`, `zip` (default: `json`) |

Exportiert alle Daten eines Bewerbers inkl. Dokumente.

#### Bewerbung löschen (DSGVO)

```
DELETE /wp-json/recruiting/v1/applications/{id}
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `reason` | string | Löschgrund (für Protokoll) |
| `notify` | bool | Bewerber per E-Mail informieren (default: false) |

---

### Webhooks

#### Webhook registrieren

```
POST /wp-json/recruiting/v1/webhooks
```

**Request Body:**

```json
{
  "name": "Eigenentwicklung Sync",
  "url": "https://intern.example.com/api/recruiting-webhook",
  "events": [
    "application.received",
    "application.status_changed",
    "application.hired"
  ],
  "secret": "my-webhook-secret-123",
  "active": true
}
```

**Verfügbare Events:**

| Event | Beschreibung |
|-------|--------------|
| `job.created` | Neue Stelle angelegt |
| `job.published` | Stelle veröffentlicht |
| `job.updated` | Stelle bearbeitet |
| `job.archived` | Stelle archiviert |
| `job.deleted` | Stelle gelöscht |
| `application.received` | Neue Bewerbung |
| `application.status_changed` | Status geändert |
| `application.hired` | Bewerber eingestellt |
| `application.rejected` | Bewerber abgelehnt |
| `application.exported` | Daten exportiert |
| `application.deleted` | Bewerbung gelöscht |

#### Webhook-Liste abrufen

```
GET /wp-json/recruiting/v1/webhooks
```

#### Webhook löschen

```
DELETE /wp-json/recruiting/v1/webhooks/{id}
```

#### Webhook-Payload

Jeder Webhook-Request enthält:

**Headers:**

```
Content-Type: application/json
X-Recruiting-Event: application.received
X-Recruiting-Delivery: whd_abc123456
X-Recruiting-Signature: sha256=abc123...
```

**Body:**

```json
{
  "event": "application.received",
  "timestamp": "2025-01-20T14:30:00Z",
  "delivery_id": "whd_abc123456",
  "data": {
    "application": {
      "id": 456,
      "job_id": 123,
      "job_title": "Pflegefachkraft (m/w/d)",
      "status": "new",
      "candidate": {
        "first_name": "Max",
        "last_name": "Mustermann",
        "email": "max@example.com"
      },
      "created_at": "2025-01-20T14:30:00Z"
    }
  }
}
```

#### Signatur-Validierung

```php
// PHP-Beispiel
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RECRUITING_SIGNATURE'];
$secret = 'my-webhook-secret-123';

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected, $signature)) {
    // Webhook ist valide
}
```

```javascript
// Node.js-Beispiel
const crypto = require('crypto');

function verifyWebhook(payload, signature, secret) {
  const expected = 'sha256=' + 
    crypto.createHmac('sha256', secret)
          .update(payload)
          .digest('hex');
  
  return crypto.timingSafeEqual(
    Buffer.from(expected),
    Buffer.from(signature)
  );
}
```

---

### Reports (Berichte)

#### Übersicht

```
GET /wp-json/recruiting/v1/reports/overview
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `date_from` | date | Start-Datum |
| `date_to` | date | End-Datum |
| `job_id` | int | Filter nach Stelle (optional) |

**Response:**

```json
{
  "period": {
    "from": "2025-01-01",
    "to": "2025-01-31"
  },
  "jobs": {
    "total": 12,
    "active": 8,
    "new_this_period": 3
  },
  "applications": {
    "total": 156,
    "new_this_period": 45,
    "by_status": {
      "new": 12,
      "screening": 18,
      "interview": 8,
      "offer": 3,
      "hired": 4,
      "rejected": 110,
      "withdrawn": 1
    }
  },
  "conversion": {
    "application_to_screening": 0.72,
    "screening_to_interview": 0.44,
    "interview_to_offer": 0.38,
    "offer_to_hire": 0.80
  },
  "avg_time_to_hire_days": 23
}
```

#### Time-to-Hire

```
GET /wp-json/recruiting/v1/reports/time-to-hire
```

**Response:**

```json
{
  "overall": {
    "average_days": 23,
    "median_days": 19,
    "min_days": 5,
    "max_days": 67
  },
  "by_stage": {
    "new_to_screening": 2.3,
    "screening_to_interview": 5.1,
    "interview_to_offer": 8.7,
    "offer_to_hire": 6.9
  },
  "by_job": [
    {
      "job_id": 123,
      "job_title": "Pflegefachkraft (m/w/d)",
      "average_days": 18,
      "hires": 3
    }
  ],
  "trend": [
    { "month": "2024-11", "average_days": 28 },
    { "month": "2024-12", "average_days": 25 },
    { "month": "2025-01", "average_days": 23 }
  ]
}
```

---

## Fehlerbehandlung

### HTTP Status Codes

| Code | Bedeutung |
|------|-----------|
| `200` | Erfolg |
| `201` | Ressource erstellt |
| `204` | Erfolgreich, keine Daten |
| `400` | Ungültige Anfrage |
| `401` | Nicht authentifiziert |
| `403` | Keine Berechtigung |
| `404` | Nicht gefunden |
| `422` | Validierungsfehler |
| `429` | Rate Limit erreicht |
| `500` | Server-Fehler |

### Fehler-Response

```json
{
  "error": {
    "code": "validation_error",
    "message": "Die Anfrage enthält ungültige Daten",
    "details": [
      {
        "field": "email",
        "message": "Ungültige E-Mail-Adresse"
      },
      {
        "field": "salary.min",
        "message": "Muss eine positive Zahl sein"
      }
    ]
  }
}
```

### Fehler-Codes

| Code | Beschreibung |
|------|--------------|
| `authentication_required` | API-Key fehlt |
| `invalid_api_key` | Ungültiger API-Key |
| `insufficient_permissions` | Keine Berechtigung für diese Aktion |
| `resource_not_found` | Ressource nicht gefunden |
| `validation_error` | Validierungsfehler |
| `rate_limit_exceeded` | Zu viele Anfragen |
| `internal_error` | Server-Fehler |

---

## Rate Limiting

| Tier | Limit |
|------|-------|
| FREE | 100 Requests / Stunde |
| PRO | 1.000 Requests / Stunde |
| Enterprise | 10.000 Requests / Stunde |

**Response Headers:**

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 950
X-RateLimit-Reset: 1705760400
```

---

## Versionierung

Die API ist versioniert über den URL-Pfad:

```
/wp-json/recruiting/v1/...   # Aktuelle Version
/wp-json/recruiting/v2/...   # Zukünftige Version
```

Bei Breaking Changes wird eine neue Version eingeführt. Alte Versionen werden mindestens 12 Monate unterstützt.

---

## SDK & Code-Beispiele

### PHP

```php
<?php
// Composer: composer require recruiting-playbook/php-sdk

use RecruitingPlaybook\Client;

$client = new Client([
    'base_url' => 'https://example.com',
    'api_key' => 'rp_live_abc123...'
]);

// Alle Jobs abrufen
$jobs = $client->jobs()->list(['status' => 'publish']);

// Neue Bewerbung abrufen
$application = $client->applications()->get(456);

// Status ändern
$client->applications()->updateStatus(456, 'interview', [
    'note' => 'Einladung zum Gespräch'
]);
```

### JavaScript / Node.js

```javascript
// npm install recruiting-playbook

import { RecruitingClient } from 'recruiting-playbook';

const client = new RecruitingClient({
  baseUrl: 'https://example.com',
  apiKey: 'rp_live_abc123...'
});

// Alle offenen Stellen
const jobs = await client.jobs.list({ status: 'publish' });

// Webhook für neue Bewerbungen
const webhook = await client.webhooks.create({
  url: 'https://intern.example.com/webhook',
  events: ['application.received'],
  secret: 'my-secret'
});
```

### Python

```python
# pip install recruiting-playbook

from recruiting_playbook import Client

client = Client(
    base_url="https://example.com",
    api_key="rp_live_abc123..."
)

# Bewerbungen einer Stelle
applications = client.applications.list(job_id=123)

# Export für DSGVO
export = client.applications.export(456, format="zip")
```

### cURL

```bash
# Alle Jobs abrufen
curl -X GET \
  "https://example.com/wp-json/recruiting/v1/jobs" \
  -H "X-Recruiting-API-Key: rp_live_abc123..."

# Neue Stelle erstellen
curl -X POST \
  "https://example.com/wp-json/recruiting/v1/jobs" \
  -H "X-Recruiting-API-Key: rp_live_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Pflegefachkraft (m/w/d)",
    "status": "draft",
    "employment_type": "fulltime"
  }'

# Bewerbungsstatus ändern
curl -X PUT \
  "https://example.com/wp-json/recruiting/v1/applications/456/status" \
  -H "X-Recruiting-API-Key: rp_live_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "status": "hired",
    "note": "Vertrag unterschrieben"
  }'
```

---

## Änderungsprotokoll

### v1.0 (geplant)

- Initiale API-Version
- Jobs CRUD
- Applications CRUD
- Webhooks
- Reports

---

## Support

- **Dokumentation:** https://docs.recruiting-playbook.com/api
- **Status:** https://status.recruiting-playbook.com
- **Support:** api-support@recruiting-playbook.com

---

*Letzte Aktualisierung: Januar 2025*

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

### Kanban-Board Endpoints (Pro)

#### Bewerbungen für Kanban abrufen

```
GET /wp-json/recruiting/v1/applications?context=kanban
```

Optimierte Abfrage für das Kanban-Board mit zusätzlichen Feldern.

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `context` | string | `kanban` für optimierte Kanban-Daten |
| `per_page` | int | Ergebnisse pro Seite (default: 200, max: 500) |
| `job_id` | int | Filter nach Stelle |
| `status` | string | Filter nach Status |

**Response:**

```json
{
  "items": [
    {
      "id": 456,
      "job_id": 123,
      "job_title": "Software Developer (m/w/d)",
      "status": "screening",
      "kanban_position": 10,
      "first_name": "Max",
      "last_name": "Mustermann",
      "email": "max.mustermann@example.com",
      "created_at": "2025-01-20T10:30:00+00:00",
      "documents_count": 2
    }
  ]
}
```

#### Kanban-Positionen sortieren

```
POST /wp-json/recruiting/v1/applications/reorder
```

Batch-Update der Kanban-Positionen innerhalb einer Spalte.

**Request Body:**

```json
{
  "status": "screening",
  "positions": [
    { "id": 456, "kanban_position": 10 },
    { "id": 789, "kanban_position": 20 },
    { "id": 123, "kanban_position": 30 }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "message": "Positionen wurden aktualisiert.",
  "updated": 3
}
```

#### Status mit Position ändern

```
PATCH /wp-json/recruiting/v1/applications/{id}/status
```

Erweiterter Status-Endpunkt mit Kanban-Position.

**Request Body:**

```json
{
  "status": "interview",
  "kanban_position": 15,
  "note": "Status via Kanban-Board geändert"
}
```

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

### E-Mail-Templates

#### Liste aller Templates

```
GET /wp-json/recruiting/v1/email-templates
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `category` | string | Filter nach Kategorie: `application`, `rejection`, `interview`, `offer` |
| `is_active` | bool | Nur aktive Templates (default: true) |
| `search` | string | Suche in Name/Betreff |

**Response:**

```json
{
  "data": [
    {
      "id": 1,
      "slug": "application-confirmation",
      "name": "Eingangsbestätigung",
      "subject": "Ihre Bewerbung bei {firma}: {stelle}",
      "body_html": "<p>{anrede_formal},</p><p>vielen Dank für Ihre Bewerbung...</p>",
      "body_text": "{anrede_formal},\n\nvielen Dank für Ihre Bewerbung...",
      "category": "application",
      "is_active": true,
      "is_default": true,
      "is_system": true,
      "variables": ["anrede_formal", "vorname", "nachname", "stelle", "firma", "bewerbung_datum", "bewerbung_id"],
      "created_at": "2025-01-15T10:00:00Z",
      "updated_at": "2025-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "total": 9,
    "categories": ["application", "rejection", "interview", "offer"]
  }
}
```

#### Einzelnes Template abrufen

```
GET /wp-json/recruiting/v1/email-templates/{id}
```

#### Neues Template erstellen

```
POST /wp-json/recruiting/v1/email-templates
```

**Request Body:**

```json
{
  "name": "Absage Initiativbewerbung",
  "slug": "rejection-initiative",
  "subject": "Ihre Initiativbewerbung bei {firma}",
  "body_html": "<p>{anrede_formal},</p><p>vielen Dank für Ihre Initiativbewerbung...</p>",
  "category": "rejection",
  "is_active": true
}
```

#### Template aktualisieren

```
PUT /wp-json/recruiting/v1/email-templates/{id}
```

**Request Body:** Nur die zu ändernden Felder

#### Template löschen

```
DELETE /wp-json/recruiting/v1/email-templates/{id}
```

> **Hinweis:** System-Templates (`is_system: true`) können nicht gelöscht werden.

#### Verfügbare Platzhalter abrufen

```
GET /wp-json/recruiting/v1/email-templates/placeholders
```

**Response:**

```json
{
  "placeholders": {
    "candidate": [
      { "key": "anrede", "label": "Anrede", "example": "Herr" },
      { "key": "anrede_formal", "label": "Formelle Anrede", "example": "Sehr geehrter Herr Mustermann" },
      { "key": "vorname", "label": "Vorname", "example": "Max" },
      { "key": "nachname", "label": "Nachname", "example": "Mustermann" },
      { "key": "name", "label": "Vollständiger Name", "example": "Max Mustermann" },
      { "key": "email", "label": "E-Mail", "example": "max@example.com" },
      { "key": "telefon", "label": "Telefon", "example": "+49 170 1234567" }
    ],
    "application": [
      { "key": "bewerbung_id", "label": "Bewerbungs-ID", "example": "#2025-0042" },
      { "key": "bewerbung_datum", "label": "Bewerbungsdatum", "example": "15.01.2025" },
      { "key": "bewerbung_status", "label": "Bewerbungsstatus", "example": "In Prüfung" }
    ],
    "job": [
      { "key": "stelle", "label": "Stellentitel", "example": "Pflegefachkraft (m/w/d)" },
      { "key": "stelle_ort", "label": "Arbeitsort", "example": "Berlin" },
      { "key": "stelle_typ", "label": "Beschäftigungsart", "example": "Vollzeit" },
      { "key": "stelle_url", "label": "Link zur Stelle", "example": "https://example.com/jobs/..." }
    ],
    "company": [
      { "key": "firma", "label": "Firmenname", "example": "Muster GmbH" },
      { "key": "firma_adresse", "label": "Firmenadresse", "example": "Musterstraße 1, 10115 Berlin" },
      { "key": "firma_website", "label": "Website", "example": "https://example.com" }
    ]
  },
  "total": 17
}
```

> **Wichtig:** Die API stellt nur 17 echte Platzhalter bereit (7 Kandidat, 3 Bewerbung, 4 Stelle, 3 Firma). Pseudo-Variablen wie `{termin_datum}` oder `{absender_name}` wurden entfernt. Siehe [Breaking Changes](#breaking-changes).

#### Template-Kategorien abrufen

```
GET /wp-json/recruiting/v1/email-templates/categories
```

**Response:**

```json
{
  "categories": [
    { "slug": "application", "label": "Bewerbung", "count": 4 },
    { "slug": "rejection", "label": "Absage", "count": 1 },
    { "slug": "interview", "label": "Interview", "count": 2 },
    { "slug": "offer", "label": "Angebot", "count": 2 }
  ]
}
```

---

### E-Mail-Signaturen

> **Hinweis:** Signaturen sind immer user-spezifisch. Es gibt keine separate "Firmen-Signatur" als Datenbank-Eintrag. Wenn ein User keine Signatur hat, wird automatisch eine Signatur aus den Firmendaten generiert.

#### Liste aller Signaturen

```
GET /wp-json/recruiting/v1/signatures
```

Gibt alle Signaturen des aktuellen Benutzers zurück.

**Response:**

```json
{
  "signatures": [
    {
      "id": 1,
      "name": "Meine Signatur",
      "content": "Mit freundlichen Grüßen\n\nMax Mustermann\nPersonalreferent",
      "is_default": true,
      "user_id": 5,
      "created_at": "2025-01-15T10:00:00Z",
      "updated_at": "2025-01-15T10:00:00Z"
    },
    {
      "id": 2,
      "name": "Kurz",
      "content": "Beste Grüße, Max Mustermann",
      "is_default": false,
      "user_id": 5,
      "created_at": "2025-01-10T08:00:00Z",
      "updated_at": "2025-01-10T08:00:00Z"
    }
  ],
  "total": 2
}
```

#### Neue Signatur erstellen

```
POST /wp-json/recruiting/v1/signatures
```

**Request Body:**

```json
{
  "name": "Formell",
  "content": "Mit freundlichen Grüßen\n\nMax Mustermann\nHR Manager",
  "is_default": false
}
```

#### Signatur aktualisieren

```
PUT /wp-json/recruiting/v1/signatures/{id}
```

**Request Body:** Nur die zu ändernden Felder

> **Hinweis:** Benutzer können nur eigene Signaturen bearbeiten.

#### Signatur löschen

```
DELETE /wp-json/recruiting/v1/signatures/{id}
```

> **Hinweis:** Benutzer können nur eigene Signaturen löschen.

#### Signatur als Standard setzen

```
POST /wp-json/recruiting/v1/signatures/{id}/default
```

Setzt die angegebene Signatur als Standard für den aktuellen Benutzer.

**Response:**

```json
{
  "success": true,
  "message": "Signatur wurde als Standard gesetzt."
}
```

#### Signatur-Optionen für Dropdown

```
GET /wp-json/recruiting/v1/signatures/options
```

Gibt eine vereinfachte Liste für Dropdown-Auswahl zurück.

**Response:**

```json
{
  "options": [
    { "value": 1, "label": "Meine Signatur", "is_default": true },
    { "value": 2, "label": "Kurz", "is_default": false },
    { "value": 0, "label": "Keine Signatur", "is_default": false }
  ]
}
```

#### Signatur-Vorschau rendern

```
POST /wp-json/recruiting/v1/signatures/preview
```

**Request Body:**

```json
{
  "content": "Mit freundlichen Grüßen\n\nMax Mustermann\nHR Manager"
}
```

**Response:**

```json
{
  "html": "<div class=\"rp-signature\" style=\"margin-top: 20px;\">..."
}
```

---

### E-Mail-Versand

#### E-Mail senden

```
POST /wp-json/recruiting/v1/emails/send
```

**Request Body:**

```json
{
  "application_id": 456,
  "template_id": 1,
  "signature_id": 5,
  "subject": "Ihre Bewerbung bei Muster GmbH: Pflegefachkraft (m/w/d)",
  "body": "<p>Sehr geehrter Herr Mustermann,</p><p>vielen Dank für Ihre Bewerbung...</p>",
  "attachments": [789, 790]
}
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `application_id` | int | Ja | Bewerbungs-ID |
| `template_id` | int | Nein | Template-ID (optional, wenn body angegeben) |
| `signature_id` | int | Nein | Signatur-ID (null = Firmen-Signatur, 0 = keine) |
| `subject` | string | Ja | E-Mail-Betreff |
| `body` | string | Ja | E-Mail-Inhalt (HTML) |
| `attachments` | array | Nein | Array von Dokument-IDs |

**Response:**

```json
{
  "success": true,
  "message": "E-Mail wurde erfolgreich gesendet.",
  "email_log_id": 1234,
  "sent_at": "2025-01-20T14:30:00Z"
}
```

#### E-Mail-Vorschau

```
POST /wp-json/recruiting/v1/emails/preview
```

**Request Body:**

```json
{
  "application_id": 456,
  "template_id": 1,
  "signature_id": 5
}
```

**Response:**

```json
{
  "subject": "Ihre Bewerbung bei Muster GmbH: Pflegefachkraft (m/w/d)",
  "body_html": "<p>Sehr geehrter Herr Mustermann,</p>...<div class=\"rp-signature\">...</div>",
  "body_text": "Sehr geehrter Herr Mustermann,\n\n...",
  "recipient": {
    "name": "Max Mustermann",
    "email": "max@example.com"
  },
  "placeholders_used": ["anrede_formal", "stelle", "firma", "bewerbung_datum"]
}
```

#### E-Mail-Log abrufen

```
GET /wp-json/recruiting/v1/emails/log
```

**Query-Parameter:**

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `application_id` | int | Filter nach Bewerbung |
| `per_page` | int | Ergebnisse pro Seite (default: 20) |
| `page` | int | Seitennummer |

**Response:**

```json
{
  "data": [
    {
      "id": 1234,
      "application_id": 456,
      "template_id": 1,
      "template_name": "Eingangsbestätigung",
      "subject": "Ihre Bewerbung bei Muster GmbH: Pflegefachkraft (m/w/d)",
      "recipient_email": "max@example.com",
      "recipient_name": "Max Mustermann",
      "status": "sent",
      "sent_by": {
        "id": 5,
        "name": "Maria Schmidt"
      },
      "sent_at": "2025-01-20T14:30:00Z",
      "opened_at": null
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 20,
    "current_page": 1
  }
}
```

---

### Firmendaten (Einstellungen)

#### Firmendaten abrufen

```
GET /wp-json/recruiting/v1/settings/company
```

**Berechtigung:** `edit_posts` (alle eingeloggten Benutzer mit Bearbeitungsrechten)

**Response:**

```json
{
  "company": {
    "name": "Muster GmbH",
    "street": "Musterstraße 1",
    "zip": "10115",
    "city": "Berlin",
    "phone": "+49 30 123456",
    "website": "https://www.example.com",
    "email": "info@example.com",
    "sender_name": "HR Team Muster GmbH",
    "sender_email": "hr@example.com"
  }
}
```

#### Firmendaten aktualisieren

```
POST /wp-json/recruiting/v1/settings/company
```

**Berechtigung:** `manage_options` (nur Administratoren)

**Request Body:**

```json
{
  "name": "Muster GmbH",
  "street": "Musterstraße 1",
  "zip": "10115",
  "city": "Berlin",
  "phone": "+49 30 123456",
  "website": "https://www.example.com",
  "email": "info@example.com",
  "sender_name": "HR Team Muster GmbH",
  "sender_email": "hr@example.com"
}
```

| Feld | Typ | Pflicht | Beschreibung |
|------|-----|---------|--------------|
| `name` | string | Ja | Firmenname |
| `street` | string | Nein | Straße und Hausnummer |
| `zip` | string | Nein | Postleitzahl |
| `city` | string | Nein | Stadt |
| `phone` | string | Nein | Telefonnummer |
| `website` | string | Nein | Website-URL |
| `email` | string | Ja | Kontakt-E-Mail |
| `sender_name` | string | Nein | Standard-Absender Name |
| `sender_email` | string | Nein | Standard-Absender E-Mail |

**Validierung:**
- `name` ist Pflichtfeld
- `email` muss gültige E-Mail-Adresse sein
- `sender_email` muss gültige E-Mail-Adresse sein (wenn angegeben)
- `website` muss gültige URL sein (wenn angegeben)

---

### Benutzerrollen (Pro)

#### Alle Rollen abrufen

```
GET /wp-json/recruiting/v1/roles
```

**Berechtigung:** `rp_manage_roles` oder `manage_options`

**Response:**

```json
{
  "roles": [
    {
      "slug": "rp_recruiter",
      "name": "Recruiter",
      "capabilities": {
        "rp_view_applications": true,
        "rp_edit_applications": true,
        "rp_delete_applications": false,
        "rp_manage_roles": false,
        "rp_assign_jobs": false
      },
      "user_count": 3
    }
  ]
}
```

#### Capability-Gruppen abrufen

```
GET /wp-json/recruiting/v1/roles/capabilities
```

**Berechtigung:** `rp_manage_roles` oder `manage_options`

Gibt die verfügbaren Capabilities gruppiert nach Funktionsbereich zurück.

**Response:**

```json
{
  "groups": [
    {
      "label": "Bewerbungen",
      "capabilities": [
        "rp_view_applications",
        "rp_edit_applications",
        "rp_delete_applications"
      ]
    }
  ]
}
```

#### Rolle aktualisieren

```
PUT /wp-json/recruiting/v1/roles/{slug}
```

**Berechtigung:** `manage_options` (nur Administratoren)

Nur Custom Rollen (`rp_recruiter`, `rp_hiring_manager`) können bearbeitet werden. Admin-only Capabilities (`rp_manage_roles`, `rp_assign_jobs`) werden immer auf `false` gesetzt.

**Request Body:**

```json
{
  "capabilities": {
    "rp_view_applications": true,
    "rp_edit_applications": true,
    "rp_delete_applications": false,
    "rp_send_emails": true
  }
}
```

**Response:** Aktualisiertes Rollen-Objekt mit `slug`, `name` und `capabilities`.

---

### Stellen-Zuweisungen (Pro)

#### Zuweisung erstellen

```
POST /wp-json/recruiting/v1/job-assignments
```

**Berechtigung:** `rp_assign_jobs` oder `manage_options`

**Request Body:**

```json
{
  "user_id": 5,
  "job_id": 123
}
```

**Response (201):**

```json
{
  "success": true,
  "assignment": {
    "id": 42,
    "user_id": 5,
    "job_id": 123,
    "assigned_by": 1,
    "assigned_at": "2025-01-28T12:00:00"
  }
}
```

#### Zuweisung entfernen

```
DELETE /wp-json/recruiting/v1/job-assignments
```

**Berechtigung:** `rp_assign_jobs` oder `manage_options`

**Request Body:**

```json
{
  "user_id": 5,
  "job_id": 123
}
```

#### Zugewiesene Jobs eines Users abrufen

```
GET /wp-json/recruiting/v1/job-assignments/user/{user_id}
```

**Berechtigung:** `rp_assign_jobs` oder `manage_options`

**Response:**

```json
{
  "user_id": 5,
  "jobs": [
    {
      "id": 123,
      "title": "Pflegefachkraft (m/w/d)",
      "status": "publish",
      "assigned_at": "2025-01-28T12:00:00"
    }
  ],
  "count": 1
}
```

#### Zugewiesene User eines Jobs abrufen

```
GET /wp-json/recruiting/v1/job-assignments/job/{job_id}
```

**Berechtigung:** `rp_assign_jobs` oder `manage_options`

**Response:**

```json
{
  "job_id": 123,
  "users": [
    {
      "id": 5,
      "name": "Anna Müller",
      "email": "anna@example.com",
      "role": "recruiter",
      "avatar": "https://example.com/avatar.jpg",
      "assigned_at": "2025-01-28T12:00:00",
      "assigned_by": 1
    }
  ],
  "count": 1
}
```

#### Bulk-Zuweisung

```
POST /wp-json/recruiting/v1/job-assignments/bulk
```

**Berechtigung:** `rp_assign_jobs` oder `manage_options`

**Request Body:**

```json
{
  "user_id": 5,
  "job_ids": [123, 456, 789]
}
```

**Response:**

```json
{
  "success": true,
  "assigned_count": 3,
  "assignments": [
    { "job_id": 123, "assigned": true, "error": null },
    { "job_id": 456, "assigned": true, "error": null },
    { "job_id": 789, "assigned": true, "error": null }
  ]
}
```

---

### Form Builder (Pro)

Die Form Builder API ermöglicht die Konfiguration von Bewerbungsformularen mit einem Draft/Publish-Workflow.

#### Draft-Konfiguration laden

```
GET /wp-json/recruiting/v1/form-builder/config
```

**Berechtigung:** `rp_manage_forms` oder `manage_options`

**Response:**

```json
{
  "config": {
    "version": 2,
    "settings": {
      "showStepIndicator": true,
      "showStepTitles": true,
      "animateSteps": true
    },
    "steps": [...]
  },
  "has_unpublished_changes": true,
  "draft_version": 5,
  "published_version": 4,
  "last_published_at": "2026-02-01T10:00:00Z"
}
```

#### Draft speichern

```
PUT /wp-json/recruiting/v1/form-builder/config
```

**Request Body:**

```json
{
  "config": {
    "version": 2,
    "settings": {...},
    "steps": [...]
  }
}
```

#### Draft veröffentlichen

```
POST /wp-json/recruiting/v1/form-builder/publish
```

Validiert die Konfiguration und veröffentlicht sie. Ab diesem Moment wird die neue Konfiguration im Frontend verwendet.

**Response:**

```json
{
  "success": true,
  "message": "Formular wurde veröffentlicht.",
  "published_version": 5
}
```

#### Änderungen verwerfen

```
POST /wp-json/recruiting/v1/form-builder/discard
```

Setzt den Draft auf die veröffentlichte Version zurück.

#### Veröffentlichte Konfiguration (öffentlich)

```
GET /wp-json/recruiting/v1/form-builder/published
```

**Berechtigung:** Keine (öffentlich für Frontend)

Gibt die veröffentlichte Formular-Konfiguration für das Frontend zurück.

#### Aktive Felder abrufen

```
GET /wp-json/recruiting/v1/form-builder/active-fields
```

Gibt alle sichtbaren Felder für die Bewerberdetail-Ansicht zurück.

**Response:**

```json
{
  "fields": [
    {
      "field_key": "first_name",
      "label": "Vorname",
      "type": "text",
      "is_system": true
    },
    {
      "field_key": "salary_expectation",
      "label": "Gehaltsvorstellung",
      "type": "number",
      "is_system": false
    }
  ]
}
```

#### Auf Standard zurücksetzen

```
POST /wp-json/recruiting/v1/form-builder/reset
```

Setzt die Formular-Konfiguration auf die Standard-Konfiguration zurück.

---

## Breaking Changes

### Version 1.5.0: E-Mail-Platzhalter bereinigt

Die folgenden 17 Pseudo-Variablen wurden entfernt, da sie nicht automatisch aufgelöst werden können:

**Entfernte Variablen:**

| Gruppe | Entfernte Variablen |
|--------|---------------------|
| Interview/Termin | `{termin_datum}`, `{termin_uhrzeit}`, `{termin_ort}`, `{termin_teilnehmer}`, `{termin_dauer}` |
| Absender | `{absender_name}`, `{absender_email}`, `{absender_telefon}`, `{absender_position}` |
| Kontakt | `{kontakt_email}`, `{kontakt_telefon}`, `{kontakt_name}` |
| Angebot | `{start_datum}`, `{vertragsart}`, `{arbeitszeit}`, `{antwort_frist}` |

**Migration bestehender Templates:**

Templates die diese Variablen verwenden, zeigen nun `{variable_name}` als Text statt aufgelöster Werte. Ersetzen Sie diese durch:

1. **Feste Eingaben:** Ersetzen Sie `{termin_datum}` durch `___` als Platzhalter für manuelle Eingabe
2. **Signaturen:** Absender-Informationen werden jetzt über die Signatur-Funktion verwaltet

**Empfohlene Anpassung:**

```diff
- <p>Ihr Vorstellungsgespräch findet am {termin_datum} um {termin_uhrzeit} statt.</p>
+ <p>Ihr Vorstellungsgespräch findet am ___ um ___ statt.</p>

- <p>Mit freundlichen Grüßen<br>{absender_name}<br>{firma}</p>
+ <!-- Signatur wird automatisch angehängt -->
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
| `rest_forbidden` | Feature-Gate nicht erfüllt (z.B. Pro-Lizenz erforderlich) |
| `resource_not_found` | Ressource nicht gefunden |
| `validation_error` | Validierungsfehler |
| `rate_limit_exceeded` | Zu viele Anfragen |
| `internal_error` | Server-Fehler |
| `invalid_role` | Nur Custom Recruiting-Rollen können bearbeitet werden |
| `invalid_user` | Benutzer nicht gefunden |
| `invalid_job` | Post ist kein `job_listing` |
| `already_assigned` | Benutzer ist bereits dieser Stelle zugewiesen |
| `not_found` | Zuweisung/Rolle nicht gefunden |

### Feature-Gate (Pro-Endpoints)

Bestimmte Endpoints erfordern eine aktive Pro-Lizenz. Ohne gültige Lizenz liefern diese Endpoints:

```json
{
  "code": "rest_forbidden",
  "message": "Diese Funktion erfordert eine Pro-Lizenz.",
  "data": {
    "status": 403
  }
}
```

**Betroffene Endpoints:**
- `/roles` und `/roles/capabilities` — Benutzerrollen-Verwaltung
- `/roles/{slug}` — Capability-Konfiguration
- `/job-assignments/*` — Stellen-Zuweisungen

Die Feature-Gate-Prüfung erfolgt über die interne Funktion `rp_can('user_roles')`.

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

### v2.2.0 (Februar 2026)

- **Form Builder API (Pro)** - Formular-Konfiguration mit Draft/Publish-Workflow
  - `GET /form-builder/config` - Draft-Konfiguration laden
  - `PUT /form-builder/config` - Draft speichern
  - `POST /form-builder/publish` - Draft veröffentlichen
  - `POST /form-builder/discard` - Änderungen verwerfen
  - `GET /form-builder/published` - Veröffentlichte Konfiguration (öffentlich)
  - `GET /form-builder/active-fields` - Sichtbare Felder
  - `POST /form-builder/reset` - Auf Standard zurücksetzen
- **Step-basiertes Formular-System** - Multi-Step Formulare mit konfigurierbaren Steps
- **12 Feldtypen** - text, textarea, email, phone, number, select, radio, checkbox, date, file, url, heading, html
- **System-Felder** - file_upload, summary, privacy_consent
- **Live-Vorschau** - Responsive Vorschau (Desktop/Tablet/Mobile)

### v2.1.0 (Januar 2026)

- **Custom Fields API (Pro)** - Benutzerdefinierte Formularfelder verwalten (`/field-definitions`)
- **Form Templates API (Pro)** - Formular-Templates erstellen und verwalten (`/form-templates`)
- **Conditional Logic** - Bedingte Logik für dynamische Formulare
- **Multi-File-Upload** - Mehrere Dateien pro Feld hochladen
- **Job-spezifische Felder** - Formulare pro Stelle anpassen (`GET /jobs/{id}/fields`)
- **Feature-Gate** - Erfordert Pro-Lizenz (`custom_fields`)

### v2.0.0 (Januar 2026)

- **Benutzerrollen API (Pro)** - Rollen und Capabilities verwalten (`GET/PUT /roles`)
- **Stellen-Zuweisungen API (Pro)** - User zu Jobs zuweisen (`/job-assignments`)
- **Bulk-Zuweisung** - Mehrere Stellen einem User zuweisen (`POST /job-assignments/bulk`)
- **Capability-Gruppen** - Gruppierte Capabilities für Admin-UI (`GET /roles/capabilities`)
- **Feature-Gate** - Alle neuen Endpoints erfordern Pro-Lizenz (`user_roles`)

### v1.5.0 (Januar 2025)

- **E-Mail-Templates API** - Vollständige CRUD-Endpoints für Templates
- **Signaturen API** - Persönliche und Firmen-Signaturen verwalten
- **E-Mail-Versand API** - E-Mails senden mit Vorschau und Log
- **Firmendaten API** - Company Settings über REST verwalten
- **Breaking Change:** 17 Pseudo-Variablen entfernt (siehe [Breaking Changes](#breaking-changes))
- **Neue Template-Kategorien:** `rejection`, `interview`, `offer`
- **9 Standard-Templates** mit Signatur-Trennung

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

*Letzte Aktualisierung: Februar 2026*

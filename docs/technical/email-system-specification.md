# E-Mail-System Pro: Technische Spezifikation

> **Pro-Feature: Professionelles E-Mail-System**
> Template-Editor, Platzhalter, E-Mail-Historie und Queue-basierter Versand

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Datenmodell](#3-datenmodell)
4. [REST API Endpunkte](#4-rest-api-endpunkte)
5. [E-Mail-Templates](#5-e-mail-templates)
6. [Template-Editor](#6-template-editor)
7. [Platzhalter-System](#7-platzhalter-system)
8. [E-Mail-Versand](#8-e-mail-versand)
9. [E-Mail-Historie](#9-e-mail-historie)
10. [Queue & Action Scheduler](#10-queue--action-scheduler)
11. [Admin-Oberfläche](#11-admin-oberfläche)
12. [Berechtigungen](#12-berechtigungen)
13. [Testing](#13-testing)

---

## 1. Übersicht

### Zielsetzung

Das erweiterte E-Mail-System ermöglicht Recruitern:
- **Template-Editor** (WYSIWYG) für professionelle E-Mail-Vorlagen
- **Platzhalter** für personalisierte E-Mails ({vorname}, {stelle}, {firma})
- **Standard-Templates** für Eingangsbestätigung, Absage, Interview-Einladung
- **Manueller Versand** direkt aus dem Backend
- **E-Mail-Historie** für vollständige Nachverfolgung pro Bewerber
- **Queue-basierter Versand** via Action Scheduler für Zuverlässigkeit

### Feature-Gating

```php
// Pro-Feature Checks
if ( ! rp_can( 'email_templates' ) ) {
    rp_require_feature( 'email_templates', 'E-Mail-Templates', 'PRO' );
}

if ( ! rp_can( 'email_history' ) ) {
    rp_require_feature( 'email_history', 'E-Mail-Historie', 'PRO' );
}

// Free-Version: Nur Standard-E-Mails (Eingang, Bestätigung)
// Pro-Version: Template-Editor, alle Templates, Historie, Queue
```

### User Stories

| Als | möchte ich | damit |
|-----|-----------|-------|
| Recruiter | E-Mail-Templates anpassen | die Kommunikation zum Unternehmen passt |
| Recruiter | Platzhalter in E-Mails nutzen | ich personalisierte Nachrichten sende |
| HR-Manager | alle gesendeten E-Mails sehen | ich die Kommunikation nachvollziehen kann |
| Recruiter | E-Mails direkt aus dem Backend senden | ich effizient arbeiten kann |
| Recruiter | Interview-Einladungen mit Termin senden | der Prozess professionell wirkt |
| Admin | sehen ob E-Mails zugestellt wurden | ich Probleme erkennen kann |

### Bestehende Infrastruktur

Der `EmailService` (Phase 1) bietet bereits:
- Grundlegender Versand via `wp_mail()`
- Fallback-Templates (Eingang, Bestätigung, Absage)
- SMTP-Prüfung
- Hook `rp_email_sent` für Logging

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   ├── Admin/
│   │   └── Pages/
│   │       └── EmailSettingsPage.php      # Template-Verwaltung
│   │
│   ├── Api/
│   │   ├── EmailController.php            # REST API für E-Mail-Versand
│   │   ├── EmailTemplateController.php    # REST API für Templates
│   │   └── EmailLogController.php         # REST API für Historie
│   │
│   ├── Services/
│   │   ├── EmailService.php               # (erweitert) E-Mail-Versand
│   │   ├── EmailTemplateService.php       # Template-Verwaltung
│   │   ├── EmailQueueService.php          # Queue-Management
│   │   └── PlaceholderService.php         # Platzhalter-Ersetzung
│   │
│   ├── Repositories/
│   │   ├── EmailTemplateRepository.php    # Template Data Access
│   │   └── EmailLogRepository.php         # Log Data Access
│   │
│   ├── Models/
│   │   ├── EmailTemplate.php              # Template Model
│   │   └── EmailLogEntry.php              # Log Entry Model
│   │
│   └── Queue/
│       └── EmailQueueHandler.php          # Action Scheduler Handler
│
├── assets/
│   └── src/
│       ├── js/
│       │   └── admin/
│       │       └── email/
│       │           ├── index.jsx              # Entry Point
│       │           ├── TemplateList.jsx       # Template-Übersicht
│       │           ├── TemplateEditor.jsx     # WYSIWYG Editor
│       │           ├── PlaceholderPicker.jsx  # Platzhalter-Auswahl
│       │           ├── EmailComposer.jsx      # E-Mail verfassen
│       │           ├── EmailHistory.jsx       # Historie-Ansicht
│       │           ├── EmailPreview.jsx       # Vorschau-Modal
│       │           └── hooks/
│       │               ├── useTemplates.js
│       │               ├── useEmailSend.js
│       │               └── useEmailHistory.js
│       │
│       └── css/
│           └── admin-email.css                # E-Mail-Styles
│
└── templates/
    └── emails/
        ├── base-layout.php                    # Basis HTML-Layout
        ├── email-application-received.php     # HR: Neue Bewerbung
        ├── email-applicant-confirmation.php   # Bewerber: Bestätigung
        ├── email-rejection.php                # Bewerber: Absage
        ├── email-interview-invitation.php     # Bewerber: Interview
        ├── email-offer.php                    # Bewerber: Angebot
        └── email-custom.php                   # Benutzerdefiniert
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Frontend | React 18 (@wordpress/element) |
| Rich Text Editor | @wordpress/rich-text oder TipTap |
| State Management | React Context + Custom Hooks |
| API-Kommunikation | @wordpress/api-fetch |
| Queue | Action Scheduler (woocommerce/action-scheduler) |
| Styling | Tailwind CSS (rp- Prefix) |

### Komponenten-Diagramm

```
┌─────────────────────────────────────────────────────────────────┐
│                        Admin UI (React)                          │
├─────────────────┬─────────────────┬─────────────────────────────┤
│ TemplateEditor  │ EmailComposer   │ EmailHistory                │
│ - WYSIWYG       │ - Empfänger     │ - Liste                     │
│ - Platzhalter   │ - Template      │ - Filter                    │
│ - Vorschau      │ - Vorschau      │ - Details                   │
└────────┬────────┴────────┬────────┴──────────────┬──────────────┘
         │                 │                       │
         ▼                 ▼                       ▼
┌─────────────────────────────────────────────────────────────────┐
│                      REST API Layer                              │
│  /email-templates    /emails/send    /emails/log                │
└─────────────────────────────────────────────────────────────────┘
         │                 │                       │
         ▼                 ▼                       ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Service Layer                               │
│  EmailTemplateService  EmailService  EmailQueueService          │
│                        PlaceholderService                        │
└─────────────────────────────────────────────────────────────────┘
         │                 │                       │
         ▼                 ▼                       ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Repository Layer                              │
│  EmailTemplateRepository          EmailLogRepository            │
└─────────────────────────────────────────────────────────────────┘
         │                                         │
         ▼                                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Database                                   │
│  rp_email_templates              rp_email_log                   │
└─────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Action Scheduler                              │
│  rp_send_email (queued)    rp_send_bulk_email (batch)          │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Datenmodell

### Neue Tabelle: `rp_email_templates`

```sql
CREATE TABLE {$prefix}rp_email_templates (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    slug            varchar(100) NOT NULL,
    name            varchar(255) NOT NULL,
    subject         varchar(255) NOT NULL,
    body_html       longtext NOT NULL,
    body_text       longtext DEFAULT NULL,
    category        varchar(50) DEFAULT 'custom',
    is_active       tinyint(1) DEFAULT 1,
    is_default      tinyint(1) DEFAULT 0,
    variables       longtext DEFAULT NULL,
    settings        longtext DEFAULT NULL,
    created_by      bigint(20) unsigned DEFAULT NULL,
    created_at      datetime NOT NULL,
    updated_at      datetime NOT NULL,
    deleted_at      datetime DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug),
    KEY category (category),
    KEY is_active (is_active),
    KEY is_default (is_default),
    KEY deleted_at (deleted_at)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `slug` | varchar | Eindeutiger Identifier (z.B. "interview-invitation") |
| `name` | varchar | Anzeigename (z.B. "Interview-Einladung") |
| `subject` | varchar | E-Mail-Betreff (mit Platzhaltern) |
| `body_html` | longtext | HTML-Inhalt (mit Platzhaltern) |
| `body_text` | longtext | Plain-Text-Version (optional, wird generiert) |
| `category` | varchar | Kategorie: system, application, interview, offer, custom |
| `is_active` | tinyint | Template aktiv/inaktiv |
| `is_default` | tinyint | Standard-Template für diese Kategorie |
| `variables` | longtext | JSON: Verwendete Platzhalter |
| `settings` | longtext | JSON: Zusätzliche Einstellungen |
| `created_by` | bigint | FK zu wp_users |
| `created_at` | datetime | Erstellungsdatum |
| `updated_at` | datetime | Letzte Änderung |
| `deleted_at` | datetime | Soft Delete |

#### Standard-Templates (Seed Data)

| Slug | Name | Kategorie |
|------|------|-----------|
| `application-received` | Neue Bewerbung (HR) | system |
| `application-confirmation` | Bewerbungsbestätigung | application |
| `rejection-standard` | Absage (Standard) | application |
| `rejection-after-interview` | Absage nach Interview | application |
| `interview-invitation` | Interview-Einladung | interview |
| `interview-reminder` | Interview-Erinnerung | interview |
| `offer-letter` | Stellenangebot | offer |
| `offer-accepted` | Angebot angenommen | offer |

### Neue Tabelle: `rp_email_log`

```sql
CREATE TABLE {$prefix}rp_email_log (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    application_id  bigint(20) unsigned DEFAULT NULL,
    candidate_id    bigint(20) unsigned DEFAULT NULL,
    template_id     bigint(20) unsigned DEFAULT NULL,
    recipient_email varchar(255) NOT NULL,
    recipient_name  varchar(255) DEFAULT NULL,
    sender_email    varchar(255) NOT NULL,
    sender_name     varchar(255) DEFAULT NULL,
    subject         varchar(255) NOT NULL,
    body_html       longtext NOT NULL,
    body_text       longtext DEFAULT NULL,
    status          varchar(20) DEFAULT 'pending',
    error_message   text DEFAULT NULL,
    opened_at       datetime DEFAULT NULL,
    clicked_at      datetime DEFAULT NULL,
    metadata        longtext DEFAULT NULL,
    sent_by         bigint(20) unsigned DEFAULT NULL,
    scheduled_at    datetime DEFAULT NULL,
    sent_at         datetime DEFAULT NULL,
    created_at      datetime NOT NULL,
    PRIMARY KEY (id),
    KEY application_id (application_id),
    KEY candidate_id (candidate_id),
    KEY template_id (template_id),
    KEY recipient_email (recipient_email),
    KEY status (status),
    KEY sent_at (sent_at),
    KEY created_at (created_at)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `application_id` | bigint | FK zu rp_applications (optional) |
| `candidate_id` | bigint | FK zu rp_candidates (optional) |
| `template_id` | bigint | FK zu rp_email_templates (optional) |
| `recipient_email` | varchar | Empfänger E-Mail |
| `recipient_name` | varchar | Empfänger Name |
| `sender_email` | varchar | Absender E-Mail |
| `sender_name` | varchar | Absender Name |
| `subject` | varchar | Betreff (nach Platzhalter-Ersetzung) |
| `body_html` | longtext | HTML-Inhalt (nach Platzhalter-Ersetzung) |
| `body_text` | longtext | Plain-Text (nach Platzhalter-Ersetzung) |
| `status` | varchar | Status: pending, queued, sent, failed, opened, clicked |
| `error_message` | text | Fehlermeldung bei failed |
| `opened_at` | datetime | Zeitpunkt des Öffnens (Tracking-Pixel) |
| `clicked_at` | datetime | Zeitpunkt des Link-Klicks |
| `metadata` | longtext | JSON: Zusätzliche Daten |
| `sent_by` | bigint | FK zu wp_users (wer hat gesendet) |
| `scheduled_at` | datetime | Geplanter Versandzeitpunkt |
| `sent_at` | datetime | Tatsächlicher Versandzeitpunkt |
| `created_at` | datetime | Erstellungsdatum |

#### Status-Flow

```
pending → queued → sent → opened → clicked
                ↓
              failed
```

| Status | Beschreibung |
|--------|--------------|
| `pending` | E-Mail erstellt, noch nicht in Queue |
| `queued` | In Action Scheduler Queue |
| `sent` | Erfolgreich versendet |
| `failed` | Versand fehlgeschlagen |
| `opened` | E-Mail wurde geöffnet (Tracking-Pixel) |
| `clicked` | Link in E-Mail wurde geklickt |

---

## 4. REST API Endpunkte

### Email Templates API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/email-templates` | Alle Templates laden |
| GET | `/recruiting/v1/email-templates/{id}` | Einzelnes Template |
| POST | `/recruiting/v1/email-templates` | Neues Template erstellen |
| PATCH | `/recruiting/v1/email-templates/{id}` | Template aktualisieren |
| DELETE | `/recruiting/v1/email-templates/{id}` | Template löschen |
| POST | `/recruiting/v1/email-templates/{id}/duplicate` | Template duplizieren |
| POST | `/recruiting/v1/email-templates/{id}/reset` | Auf Standard zurücksetzen |

#### GET /email-templates

```php
register_rest_route(
    $this->namespace,
    '/email-templates',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_templates' ],
        'permission_callback' => [ $this, 'manage_templates_permissions_check' ],
        'args'                => [
            'category' => [
                'description' => __( 'Nach Kategorie filtern', 'recruiting-playbook' ),
                'type'        => 'string',
                'enum'        => [ 'system', 'application', 'interview', 'offer', 'custom' ],
            ],
            'is_active' => [
                'description' => __( 'Nur aktive Templates', 'recruiting-playbook' ),
                'type'        => 'boolean',
            ],
            'search' => [
                'description' => __( 'Suchbegriff', 'recruiting-playbook' ),
                'type'        => 'string',
            ],
        ],
    ]
);
```

#### Response Schema (Template)

```json
{
    "id": 1,
    "slug": "interview-invitation",
    "name": "Interview-Einladung",
    "subject": "Einladung zum Vorstellungsgespräch: {stelle}",
    "body_html": "<p>Guten Tag {anrede} {nachname},...</p>",
    "body_text": "Guten Tag {anrede} {nachname},...",
    "category": "interview",
    "is_active": true,
    "is_default": true,
    "variables": [
        "anrede", "vorname", "nachname", "stelle",
        "firma", "termin_datum", "termin_uhrzeit", "termin_ort"
    ],
    "settings": {
        "reply_to": "hr@company.com",
        "cc": "",
        "bcc": ""
    },
    "created_by": {
        "id": 1,
        "name": "Admin"
    },
    "created_at": "2025-01-20T10:00:00Z",
    "updated_at": "2025-01-25T14:30:00Z",
    "can_edit": true,
    "can_delete": false
}
```

#### POST /email-templates

```php
register_rest_route(
    $this->namespace,
    '/email-templates',
    [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [ $this, 'create_template' ],
        'permission_callback' => [ $this, 'manage_templates_permissions_check' ],
        'args'                => [
            'name' => [
                'description'       => __( 'Template-Name', 'recruiting-playbook' ),
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'subject' => [
                'description'       => __( 'E-Mail-Betreff', 'recruiting-playbook' ),
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'body_html' => [
                'description'       => __( 'HTML-Inhalt', 'recruiting-playbook' ),
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'wp_kses_post',
            ],
            'category' => [
                'description' => __( 'Kategorie', 'recruiting-playbook' ),
                'type'        => 'string',
                'default'     => 'custom',
                'enum'        => [ 'application', 'interview', 'offer', 'custom' ],
            ],
        ],
    ]
);
```

### Email Send API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| POST | `/recruiting/v1/emails/send` | E-Mail senden |
| POST | `/recruiting/v1/emails/preview` | Vorschau generieren |
| POST | `/recruiting/v1/emails/send-bulk` | Massen-E-Mail |

#### POST /emails/send

```php
register_rest_route(
    $this->namespace,
    '/emails/send',
    [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [ $this, 'send_email' ],
        'permission_callback' => [ $this, 'send_email_permissions_check' ],
        'args'                => [
            'application_id' => [
                'description' => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
                'type'        => 'integer',
                'required'    => true,
            ],
            'template_id' => [
                'description' => __( 'Template-ID (optional bei custom)', 'recruiting-playbook' ),
                'type'        => 'integer',
            ],
            'subject' => [
                'description' => __( 'Betreff (überschreibt Template)', 'recruiting-playbook' ),
                'type'        => 'string',
            ],
            'body' => [
                'description' => __( 'Inhalt (überschreibt Template)', 'recruiting-playbook' ),
                'type'        => 'string',
            ],
            'custom_variables' => [
                'description' => __( 'Zusätzliche Platzhalter-Werte', 'recruiting-playbook' ),
                'type'        => 'object',
            ],
            'send_immediately' => [
                'description' => __( 'Sofort senden (nicht in Queue)', 'recruiting-playbook' ),
                'type'        => 'boolean',
                'default'     => false,
            ],
            'scheduled_at' => [
                'description' => __( 'Geplanter Versandzeitpunkt', 'recruiting-playbook' ),
                'type'        => 'string',
                'format'      => 'date-time',
            ],
        ],
    ]
);
```

#### Request Body

```json
{
    "application_id": 123,
    "template_id": 5,
    "custom_variables": {
        "termin_datum": "28.01.2025",
        "termin_uhrzeit": "14:00 Uhr",
        "termin_ort": "Hauptgebäude, Raum 302"
    },
    "send_immediately": false
}
```

#### Response

```json
{
    "success": true,
    "message": "E-Mail wurde in die Warteschlange eingereiht",
    "email_log_id": 456,
    "status": "queued",
    "scheduled_at": null
}
```

#### POST /emails/preview

```php
register_rest_route(
    $this->namespace,
    '/emails/preview',
    [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [ $this, 'preview_email' ],
        'permission_callback' => [ $this, 'send_email_permissions_check' ],
        'args'                => [
            'application_id' => [
                'type'     => 'integer',
                'required' => true,
            ],
            'template_id' => [
                'type' => 'integer',
            ],
            'subject' => [
                'type' => 'string',
            ],
            'body' => [
                'type' => 'string',
            ],
            'custom_variables' => [
                'type' => 'object',
            ],
        ],
    ]
);
```

#### Preview Response

```json
{
    "recipient": {
        "email": "bewerber@example.com",
        "name": "Max Mustermann"
    },
    "subject": "Einladung zum Vorstellungsgespräch: Senior PHP Developer",
    "body_html": "<p>Guten Tag Herr Mustermann,...</p>",
    "body_text": "Guten Tag Herr Mustermann,...",
    "variables_used": [
        { "key": "anrede", "value": "Herr" },
        { "key": "nachname", "value": "Mustermann" },
        { "key": "stelle", "value": "Senior PHP Developer" }
    ]
}
```

### Email Log API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/emails/log` | Alle E-Mails (gefiltert) |
| GET | `/recruiting/v1/emails/log/{id}` | Einzelne E-Mail Details |
| GET | `/recruiting/v1/applications/{id}/emails` | E-Mails einer Bewerbung |
| GET | `/recruiting/v1/candidates/{id}/emails` | E-Mails eines Kandidaten |
| POST | `/recruiting/v1/emails/log/{id}/resend` | E-Mail erneut senden |

#### GET /emails/log

```php
register_rest_route(
    $this->namespace,
    '/emails/log',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_email_log' ],
        'permission_callback' => [ $this, 'view_email_log_permissions_check' ],
        'args'                => [
            'per_page' => [
                'type'    => 'integer',
                'default' => 20,
                'maximum' => 100,
            ],
            'page' => [
                'type'    => 'integer',
                'default' => 1,
            ],
            'status' => [
                'type' => 'string',
                'enum' => [ 'pending', 'queued', 'sent', 'failed', 'opened', 'clicked' ],
            ],
            'application_id' => [
                'type' => 'integer',
            ],
            'candidate_id' => [
                'type' => 'integer',
            ],
            'date_from' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'date_to' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'search' => [
                'type' => 'string',
            ],
        ],
    ]
);
```

#### Email Log Response

```json
{
    "items": [
        {
            "id": 456,
            "application": {
                "id": 123,
                "job_title": "Senior PHP Developer",
                "candidate_name": "Max Mustermann"
            },
            "recipient": {
                "email": "max@example.com",
                "name": "Max Mustermann"
            },
            "subject": "Einladung zum Vorstellungsgespräch",
            "template": {
                "id": 5,
                "name": "Interview-Einladung"
            },
            "status": "sent",
            "sent_by": {
                "id": 1,
                "name": "HR Manager"
            },
            "sent_at": "2025-01-25T14:30:00Z",
            "opened_at": "2025-01-25T15:45:00Z",
            "clicked_at": null
        }
    ],
    "total": 150,
    "pages": 8
}
```

---

## 5. E-Mail-Templates

### Standard-Templates

#### 1. Bewerbungsbestätigung (application-confirmation)

**Betreff:** `Ihre Bewerbung bei {firma}: {stelle}`

**Inhalt:**
```html
<p>{anrede_formal},</p>

<p>vielen Dank für Ihre Bewerbung als <strong>{stelle}</strong> bei {firma}!</p>

<p>Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig prüfen.
Sie erhalten von uns Rückmeldung, sobald wir Ihre Bewerbung geprüft haben.</p>

<p><strong>Ihre Bewerbung im Überblick:</strong></p>
<ul>
    <li>Position: {stelle}</li>
    <li>Eingegangen am: {bewerbung_datum}</li>
    <li>Referenznummer: {bewerbung_id}</li>
</ul>

<p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>

<p>Mit freundlichen Grüßen<br>
{absender_name}<br>
{firma}</p>
```

#### 2. Absage Standard (rejection-standard)

**Betreff:** `Ihre Bewerbung als {stelle}`

**Inhalt:**
```html
<p>{anrede_formal},</p>

<p>vielen Dank für Ihr Interesse an der Position <strong>{stelle}</strong>
und die Zeit, die Sie in Ihre Bewerbung investiert haben.</p>

<p>Nach sorgfältiger Prüfung müssen wir Ihnen leider mitteilen, dass wir uns
für andere Kandidaten entschieden haben, deren Profil besser zu unseren
aktuellen Anforderungen passt.</p>

<p>Diese Entscheidung ist keine Bewertung Ihrer Qualifikation. Wir ermutigen
Sie, sich bei passenden zukünftigen Stellenangeboten erneut zu bewerben.</p>

<p>Wir wünschen Ihnen für Ihre weitere berufliche Zukunft alles Gute und
viel Erfolg.</p>

<p>Mit freundlichen Grüßen<br>
{absender_name}<br>
{firma}</p>
```

#### 3. Interview-Einladung (interview-invitation)

**Betreff:** `Einladung zum Vorstellungsgespräch: {stelle}`

**Inhalt:**
```html
<p>{anrede_formal},</p>

<p>wir freuen uns, Ihnen mitteilen zu können, dass uns Ihre Bewerbung als
<strong>{stelle}</strong> überzeugt hat. Gerne möchten wir Sie persönlich
kennenlernen.</p>

<p><strong>Terminvorschlag:</strong></p>
<table>
    <tr>
        <td><strong>Datum:</strong></td>
        <td>{termin_datum}</td>
    </tr>
    <tr>
        <td><strong>Uhrzeit:</strong></td>
        <td>{termin_uhrzeit}</td>
    </tr>
    <tr>
        <td><strong>Ort:</strong></td>
        <td>{termin_ort}</td>
    </tr>
    <tr>
        <td><strong>Gesprächspartner:</strong></td>
        <td>{termin_teilnehmer}</td>
    </tr>
</table>

<p>Bitte bestätigen Sie uns den Termin oder teilen Sie uns mit, falls
Sie einen alternativen Termin benötigen.</p>

<p><strong>Bitte bringen Sie mit:</strong></p>
<ul>
    <li>Gültigen Personalausweis</li>
    <li>Aktuelle Zeugnisse (falls noch nicht eingereicht)</li>
</ul>

<p>Bei Fragen erreichen Sie uns unter {kontakt_telefon} oder per
E-Mail an {kontakt_email}.</p>

<p>Wir freuen uns auf das Gespräch mit Ihnen!</p>

<p>Mit freundlichen Grüßen<br>
{absender_name}<br>
{firma}</p>
```

#### 4. Stellenangebot (offer-letter)

**Betreff:** `Stellenangebot: {stelle} bei {firma}`

**Inhalt:**
```html
<p>{anrede_formal},</p>

<p>wir freuen uns sehr, Ihnen nach den positiven Gesprächen ein Angebot
für die Position <strong>{stelle}</strong> unterbreiten zu können!</p>

<p><strong>Eckdaten des Angebots:</strong></p>
<ul>
    <li>Position: {stelle}</li>
    <li>Startdatum: {start_datum}</li>
    <li>Vertragsart: {vertragsart}</li>
    <li>Arbeitszeit: {arbeitszeit}</li>
</ul>

<p>Die detaillierten Vertragsunterlagen erhalten Sie in Kürze per Post
oder als separaten Anhang.</p>

<p>Bitte teilen Sie uns Ihre Entscheidung bis zum <strong>{antwort_frist}</strong> mit.</p>

<p>Für Rückfragen stehen wir Ihnen selbstverständlich gerne zur Verfügung.</p>

<p>Mit freundlichen Grüßen<br>
{absender_name}<br>
{firma}</p>
```

### Template-Struktur (PHP)

```php
<?php
/**
 * E-Mail Template: Interview-Einladung
 *
 * Verfügbare Variablen:
 * - $recipient      : Empfänger-Daten (name, email)
 * - $application    : Bewerbungs-Daten
 * - $candidate      : Kandidaten-Daten
 * - $job            : Stellen-Daten
 * - $company        : Firmen-Daten
 * - $custom         : Benutzerdefinierte Variablen
 * - $placeholders   : Alle aufgelösten Platzhalter
 */

defined( 'ABSPATH' ) || exit;
?>

<?php include RP_PLUGIN_DIR . 'templates/emails/partials/header.php'; ?>

<tr>
    <td class="content">
        <p><?php echo esc_html( $placeholders['anrede_formal'] ); ?>,</p>

        <p>wir freuen uns, Ihnen mitteilen zu können, dass uns Ihre Bewerbung als
        <strong><?php echo esc_html( $placeholders['stelle'] ); ?></strong> überzeugt hat.</p>

        <!-- Termin-Box -->
        <table class="info-box">
            <tr>
                <td><strong><?php esc_html_e( 'Datum:', 'recruiting-playbook' ); ?></strong></td>
                <td><?php echo esc_html( $placeholders['termin_datum'] ?? '' ); ?></td>
            </tr>
            <!-- ... -->
        </table>
    </td>
</tr>

<?php include RP_PLUGIN_DIR . 'templates/emails/partials/footer.php'; ?>
```

---

## 6. Template-Editor

### React-Komponente: TemplateEditor

```jsx
/**
 * TemplateEditor.jsx - WYSIWYG E-Mail-Template-Editor
 */
import { useState, useCallback } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';
import { Button, TextControl, SelectControl, Panel, PanelBody } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { PlaceholderPicker } from './PlaceholderPicker';
import { EmailPreview } from './EmailPreview';

export function TemplateEditor({ template, onSave, onCancel }) {
    const [name, setName] = useState(template?.name || '');
    const [subject, setSubject] = useState(template?.subject || '');
    const [bodyHtml, setBodyHtml] = useState(template?.body_html || '');
    const [category, setCategory] = useState(template?.category || 'custom');
    const [showPreview, setShowPreview] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    // Platzhalter einfügen
    const insertPlaceholder = useCallback((placeholder) => {
        // Am Cursor einfügen oder ans Ende
        setBodyHtml(prev => prev + `{${placeholder}}`);
    }, []);

    // Template speichern
    const handleSave = async () => {
        setIsSaving(true);
        try {
            const endpoint = template?.id
                ? `/recruiting/v1/email-templates/${template.id}`
                : '/recruiting/v1/email-templates';

            const method = template?.id ? 'PATCH' : 'POST';

            const result = await apiFetch({
                path: endpoint,
                method,
                data: { name, subject, body_html: bodyHtml, category }
            });

            onSave(result);
        } catch (error) {
            console.error('Fehler beim Speichern:', error);
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="rp-template-editor">
            <div className="rp-template-editor__header">
                <h2>
                    {template?.id
                        ? __('Template bearbeiten', 'recruiting-playbook')
                        : __('Neues Template', 'recruiting-playbook')
                    }
                </h2>
            </div>

            <div className="rp-template-editor__content">
                {/* Linke Spalte: Editor */}
                <div className="rp-template-editor__main">
                    <TextControl
                        label={__('Template-Name', 'recruiting-playbook')}
                        value={name}
                        onChange={setName}
                        placeholder="z.B. Interview-Einladung"
                    />

                    <SelectControl
                        label={__('Kategorie', 'recruiting-playbook')}
                        value={category}
                        options={[
                            { value: 'application', label: 'Bewerbung' },
                            { value: 'interview', label: 'Interview' },
                            { value: 'offer', label: 'Angebot' },
                            { value: 'custom', label: 'Benutzerdefiniert' },
                        ]}
                        onChange={setCategory}
                    />

                    <TextControl
                        label={__('Betreff', 'recruiting-playbook')}
                        value={subject}
                        onChange={setSubject}
                        placeholder="Einladung zum Vorstellungsgespräch: {stelle}"
                        help={__('Platzhalter wie {stelle} werden automatisch ersetzt', 'recruiting-playbook')}
                    />

                    <div className="rp-template-editor__body">
                        <label>{__('E-Mail-Inhalt', 'recruiting-playbook')}</label>
                        <div className="rp-rich-text-wrapper">
                            <RichText
                                tagName="div"
                                value={bodyHtml}
                                onChange={setBodyHtml}
                                placeholder={__('E-Mail-Text eingeben...', 'recruiting-playbook')}
                                allowedFormats={[
                                    'core/bold',
                                    'core/italic',
                                    'core/link',
                                    'core/strikethrough',
                                ]}
                            />
                        </div>
                    </div>
                </div>

                {/* Rechte Spalte: Platzhalter */}
                <div className="rp-template-editor__sidebar">
                    <Panel>
                        <PanelBody title={__('Platzhalter', 'recruiting-playbook')} initialOpen>
                            <PlaceholderPicker onSelect={insertPlaceholder} />
                        </PanelBody>
                    </Panel>
                </div>
            </div>

            {/* Footer mit Actions */}
            <div className="rp-template-editor__footer">
                <Button variant="tertiary" onClick={onCancel}>
                    {__('Abbrechen', 'recruiting-playbook')}
                </Button>

                <Button variant="secondary" onClick={() => setShowPreview(true)}>
                    {__('Vorschau', 'recruiting-playbook')}
                </Button>

                <Button variant="primary" onClick={handleSave} isBusy={isSaving}>
                    {__('Speichern', 'recruiting-playbook')}
                </Button>
            </div>

            {/* Vorschau-Modal */}
            {showPreview && (
                <EmailPreview
                    subject={subject}
                    bodyHtml={bodyHtml}
                    onClose={() => setShowPreview(false)}
                />
            )}
        </div>
    );
}
```

### Editor-Features

| Feature | Beschreibung |
|---------|--------------|
| **WYSIWYG** | Visual Editor mit Formatierung (Bold, Italic, Links) |
| **Platzhalter-Picker** | Sidebar mit allen verfügbaren Platzhaltern |
| **Live-Vorschau** | Modal mit gerendeter E-Mail |
| **Syntax-Highlighting** | Platzhalter werden farblich hervorgehoben |
| **Auto-Save** | Entwurf automatisch speichern |
| **Version History** | Änderungen nachvollziehen (optional) |

---

## 7. Platzhalter-System

### Verfügbare Platzhalter

#### Kandidaten-Daten

| Platzhalter | Beschreibung | Beispiel |
|-------------|--------------|----------|
| `{anrede}` | Anrede | Herr / Frau |
| `{anrede_formal}` | Formelle Anrede | Sehr geehrter Herr Mustermann |
| `{vorname}` | Vorname | Max |
| `{nachname}` | Nachname | Mustermann |
| `{name}` | Vollständiger Name | Max Mustermann |
| `{email}` | E-Mail-Adresse | max@example.com |
| `{telefon}` | Telefonnummer | +49 123 456789 |

#### Bewerbungs-Daten

| Platzhalter | Beschreibung | Beispiel |
|-------------|--------------|----------|
| `{bewerbung_id}` | Bewerbungs-ID | #2025-0042 |
| `{bewerbung_datum}` | Eingangsdatum | 25.01.2025 |
| `{bewerbung_status}` | Aktueller Status | In Prüfung |

#### Stellen-Daten

| Platzhalter | Beschreibung | Beispiel |
|-------------|--------------|----------|
| `{stelle}` | Stellentitel | Senior PHP Developer |
| `{stelle_ort}` | Arbeitsort | Berlin |
| `{stelle_typ}` | Beschäftigungsart | Vollzeit |
| `{stelle_url}` | Link zur Stellenanzeige | https://... |

#### Firmen-Daten

| Platzhalter | Beschreibung | Beispiel |
|-------------|--------------|----------|
| `{firma}` | Firmenname | Muster GmbH |
| `{firma_adresse}` | Firmenadresse | Musterstr. 1, 12345 Berlin |
| `{firma_website}` | Website | https://muster.de |

#### Absender-Daten

| Platzhalter | Beschreibung | Beispiel |
|-------------|--------------|----------|
| `{absender_name}` | Name des Absenders | Maria Schmidt |
| `{absender_email}` | E-Mail des Absenders | m.schmidt@firma.de |
| `{absender_telefon}` | Telefon des Absenders | +49 30 12345-67 |
| `{absender_position}` | Position des Absenders | HR Manager |

#### Interview-Daten (nur für Interview-Templates)

| Platzhalter | Beschreibung | Beispiel |
|-------------|--------------|----------|
| `{termin_datum}` | Datum | 28.01.2025 |
| `{termin_uhrzeit}` | Uhrzeit | 14:00 Uhr |
| `{termin_ort}` | Ort/Adresse | Hauptgebäude, Raum 302 |
| `{termin_teilnehmer}` | Gesprächspartner | Herr Müller (Abteilungsleiter) |
| `{termin_dauer}` | Geschätzte Dauer | ca. 60 Minuten |

#### Angebots-Daten (nur für Offer-Templates)

| Platzhalter | Beschreibung | Beispiel |
|-------------|--------------|----------|
| `{start_datum}` | Eintrittsdatum | 01.03.2025 |
| `{vertragsart}` | Vertragsart | Unbefristet |
| `{arbeitszeit}` | Arbeitszeit | 40 Stunden/Woche |
| `{antwort_frist}` | Antwortfrist | 10.02.2025 |

#### Kontakt-Daten

| Platzhalter | Beschreibung | Beispiel |
|-------------|--------------|----------|
| `{kontakt_name}` | Ansprechpartner | Maria Schmidt |
| `{kontakt_email}` | Kontakt E-Mail | jobs@firma.de |
| `{kontakt_telefon}` | Kontakt Telefon | +49 30 12345-0 |

### PlaceholderService

```php
<?php
/**
 * Placeholder Service - Platzhalter-Ersetzung
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

class PlaceholderService {

    /**
     * Alle Platzhalter-Definitionen
     */
    private const PLACEHOLDERS = [
        // Kandidat
        'anrede' => [
            'label'    => 'Anrede',
            'group'    => 'candidate',
            'callback' => 'getAnrede',
        ],
        'anrede_formal' => [
            'label'    => 'Formelle Anrede',
            'group'    => 'candidate',
            'callback' => 'getAnredeFormal',
        ],
        'vorname' => [
            'label' => 'Vorname',
            'group' => 'candidate',
            'field' => 'first_name',
        ],
        'nachname' => [
            'label' => 'Nachname',
            'group' => 'candidate',
            'field' => 'last_name',
        ],
        // ... weitere Definitionen
    ];

    /**
     * Platzhalter in Text ersetzen
     *
     * @param string $text    Text mit Platzhaltern
     * @param array  $context Kontext-Daten (application, candidate, job, custom)
     * @return string
     */
    public function replace( string $text, array $context ): string {
        $placeholders = $this->resolve( $context );

        foreach ( $placeholders as $key => $value ) {
            $text = str_replace( "{{$key}}", $value, $text );
        }

        // Unbekannte Platzhalter entfernen oder markieren
        $text = preg_replace( '/\{[a-z_]+\}/', '', $text );

        return $text;
    }

    /**
     * Alle Platzhalter für Kontext auflösen
     *
     * @param array $context Kontext-Daten
     * @return array<string, string>
     */
    public function resolve( array $context ): array {
        $values = [];

        $application = $context['application'] ?? [];
        $candidate   = $context['candidate'] ?? [];
        $job         = $context['job'] ?? [];
        $custom      = $context['custom'] ?? [];

        // Kandidaten-Platzhalter
        $values['vorname']  = $candidate['first_name'] ?? '';
        $values['nachname'] = $candidate['last_name'] ?? '';
        $values['name']     = trim( $values['vorname'] . ' ' . $values['nachname'] );
        $values['email']    = $candidate['email'] ?? '';
        $values['telefon']  = $candidate['phone'] ?? '';

        // Anrede generieren
        $values['anrede'] = $this->getAnrede( $candidate );
        $values['anrede_formal'] = $this->getAnredeFormal( $candidate );

        // Bewerbungs-Platzhalter
        $values['bewerbung_id']     = $this->formatApplicationId( $application['id'] ?? 0 );
        $values['bewerbung_datum']  = $this->formatDate( $application['created_at'] ?? '' );
        $values['bewerbung_status'] = $this->translateStatus( $application['status'] ?? '' );

        // Stellen-Platzhalter
        $values['stelle']     = $job['title'] ?? '';
        $values['stelle_ort'] = $job['location'] ?? '';
        $values['stelle_typ'] = $job['employment_type'] ?? '';
        $values['stelle_url'] = $job['url'] ?? '';

        // Firmen-Platzhalter
        $settings = get_option( 'rp_settings', [] );
        $values['firma']         = $settings['company_name'] ?? get_bloginfo( 'name' );
        $values['firma_website'] = home_url();

        // Absender-Platzhalter
        $current_user = wp_get_current_user();
        $values['absender_name']  = $current_user->display_name ?? '';
        $values['absender_email'] = $current_user->user_email ?? '';

        // Kontakt
        $values['kontakt_email']   = $settings['notification_email'] ?? get_option( 'admin_email' );
        $values['kontakt_telefon'] = $settings['company_phone'] ?? '';

        // Custom-Platzhalter überschreiben
        $values = array_merge( $values, $custom );

        return array_map( 'strval', $values );
    }

    /**
     * Anrede generieren
     */
    private function getAnrede( array $candidate ): string {
        $salutation = $candidate['salutation'] ?? '';

        return match ( strtolower( $salutation ) ) {
            'herr'  => __( 'Herr', 'recruiting-playbook' ),
            'frau'  => __( 'Frau', 'recruiting-playbook' ),
            default => '',
        };
    }

    /**
     * Formelle Anrede generieren
     */
    private function getAnredeFormal( array $candidate ): string {
        $salutation = $candidate['salutation'] ?? '';
        $lastName   = $candidate['last_name'] ?? '';
        $firstName  = $candidate['first_name'] ?? '';

        if ( ! empty( $salutation ) && ! empty( $lastName ) ) {
            $prefix = strtolower( $salutation ) === 'herr'
                ? __( 'Sehr geehrter Herr', 'recruiting-playbook' )
                : __( 'Sehr geehrte Frau', 'recruiting-playbook' );

            return $prefix . ' ' . $lastName;
        }

        if ( ! empty( $firstName ) ) {
            return sprintf( __( 'Guten Tag %s', 'recruiting-playbook' ), $firstName );
        }

        return __( 'Guten Tag', 'recruiting-playbook' );
    }

    /**
     * Alle verfügbaren Platzhalter für Editor
     */
    public function getAvailablePlaceholders(): array {
        return [
            'candidate' => [
                'label'  => __( 'Kandidat', 'recruiting-playbook' ),
                'items'  => [
                    'anrede'        => __( 'Anrede', 'recruiting-playbook' ),
                    'anrede_formal' => __( 'Formelle Anrede', 'recruiting-playbook' ),
                    'vorname'       => __( 'Vorname', 'recruiting-playbook' ),
                    'nachname'      => __( 'Nachname', 'recruiting-playbook' ),
                    'name'          => __( 'Vollständiger Name', 'recruiting-playbook' ),
                    'email'         => __( 'E-Mail', 'recruiting-playbook' ),
                    'telefon'       => __( 'Telefon', 'recruiting-playbook' ),
                ],
            ],
            'application' => [
                'label' => __( 'Bewerbung', 'recruiting-playbook' ),
                'items' => [
                    'bewerbung_id'     => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
                    'bewerbung_datum'  => __( 'Eingangsdatum', 'recruiting-playbook' ),
                    'bewerbung_status' => __( 'Status', 'recruiting-playbook' ),
                ],
            ],
            'job' => [
                'label' => __( 'Stelle', 'recruiting-playbook' ),
                'items' => [
                    'stelle'     => __( 'Stellentitel', 'recruiting-playbook' ),
                    'stelle_ort' => __( 'Arbeitsort', 'recruiting-playbook' ),
                    'stelle_typ' => __( 'Beschäftigungsart', 'recruiting-playbook' ),
                    'stelle_url' => __( 'Stellen-URL', 'recruiting-playbook' ),
                ],
            ],
            'company' => [
                'label' => __( 'Firma', 'recruiting-playbook' ),
                'items' => [
                    'firma'         => __( 'Firmenname', 'recruiting-playbook' ),
                    'firma_website' => __( 'Website', 'recruiting-playbook' ),
                ],
            ],
            'sender' => [
                'label' => __( 'Absender', 'recruiting-playbook' ),
                'items' => [
                    'absender_name'  => __( 'Name', 'recruiting-playbook' ),
                    'absender_email' => __( 'E-Mail', 'recruiting-playbook' ),
                ],
            ],
            'interview' => [
                'label' => __( 'Interview', 'recruiting-playbook' ),
                'items' => [
                    'termin_datum'      => __( 'Datum', 'recruiting-playbook' ),
                    'termin_uhrzeit'    => __( 'Uhrzeit', 'recruiting-playbook' ),
                    'termin_ort'        => __( 'Ort', 'recruiting-playbook' ),
                    'termin_teilnehmer' => __( 'Teilnehmer', 'recruiting-playbook' ),
                    'termin_dauer'      => __( 'Dauer', 'recruiting-playbook' ),
                ],
            ],
            'offer' => [
                'label' => __( 'Angebot', 'recruiting-playbook' ),
                'items' => [
                    'start_datum'   => __( 'Startdatum', 'recruiting-playbook' ),
                    'vertragsart'   => __( 'Vertragsart', 'recruiting-playbook' ),
                    'arbeitszeit'   => __( 'Arbeitszeit', 'recruiting-playbook' ),
                    'antwort_frist' => __( 'Antwortfrist', 'recruiting-playbook' ),
                ],
            ],
        ];
    }
}
```

### PlaceholderPicker (React)

```jsx
/**
 * PlaceholderPicker.jsx - Platzhalter-Auswahl im Template-Editor
 */
import { useState } from '@wordpress/element';
import { Button, SearchControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const PLACEHOLDER_GROUPS = {
    candidate: {
        label: __('Kandidat', 'recruiting-playbook'),
        items: {
            anrede: __('Anrede', 'recruiting-playbook'),
            anrede_formal: __('Formelle Anrede', 'recruiting-playbook'),
            vorname: __('Vorname', 'recruiting-playbook'),
            nachname: __('Nachname', 'recruiting-playbook'),
            name: __('Vollständiger Name', 'recruiting-playbook'),
            email: __('E-Mail', 'recruiting-playbook'),
            telefon: __('Telefon', 'recruiting-playbook'),
        }
    },
    application: {
        label: __('Bewerbung', 'recruiting-playbook'),
        items: {
            bewerbung_id: __('Bewerbungs-ID', 'recruiting-playbook'),
            bewerbung_datum: __('Eingangsdatum', 'recruiting-playbook'),
            bewerbung_status: __('Status', 'recruiting-playbook'),
        }
    },
    job: {
        label: __('Stelle', 'recruiting-playbook'),
        items: {
            stelle: __('Stellentitel', 'recruiting-playbook'),
            stelle_ort: __('Arbeitsort', 'recruiting-playbook'),
            stelle_typ: __('Beschäftigungsart', 'recruiting-playbook'),
        }
    },
    company: {
        label: __('Firma', 'recruiting-playbook'),
        items: {
            firma: __('Firmenname', 'recruiting-playbook'),
            firma_website: __('Website', 'recruiting-playbook'),
        }
    },
    interview: {
        label: __('Interview', 'recruiting-playbook'),
        items: {
            termin_datum: __('Datum', 'recruiting-playbook'),
            termin_uhrzeit: __('Uhrzeit', 'recruiting-playbook'),
            termin_ort: __('Ort', 'recruiting-playbook'),
            termin_teilnehmer: __('Teilnehmer', 'recruiting-playbook'),
        }
    },
};

export function PlaceholderPicker({ onSelect }) {
    const [search, setSearch] = useState('');

    const filteredGroups = Object.entries(PLACEHOLDER_GROUPS)
        .map(([key, group]) => ({
            key,
            label: group.label,
            items: Object.entries(group.items)
                .filter(([placeholder, label]) =>
                    !search ||
                    placeholder.includes(search.toLowerCase()) ||
                    label.toLowerCase().includes(search.toLowerCase())
                )
        }))
        .filter(group => group.items.length > 0);

    return (
        <div className="rp-placeholder-picker">
            <SearchControl
                value={search}
                onChange={setSearch}
                placeholder={__('Platzhalter suchen...', 'recruiting-playbook')}
            />

            <div className="rp-placeholder-picker__groups">
                {filteredGroups.map(group => (
                    <div key={group.key} className="rp-placeholder-group">
                        <h4 className="rp-placeholder-group__title">
                            {group.label}
                        </h4>
                        <div className="rp-placeholder-group__items">
                            {group.items.map(([placeholder, label]) => (
                                <Button
                                    key={placeholder}
                                    variant="tertiary"
                                    className="rp-placeholder-item"
                                    onClick={() => onSelect(placeholder)}
                                    title={`{${placeholder}}`}
                                >
                                    <code className="rp-placeholder-code">
                                        {`{${placeholder}}`}
                                    </code>
                                    <span className="rp-placeholder-label">
                                        {label}
                                    </span>
                                </Button>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

---

## 8. E-Mail-Versand

### EmailService (erweitert)

```php
<?php
/**
 * Email Service - Erweiterter E-Mail-Versand
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use RecruitingPlaybook\Repositories\EmailLogRepository;
use RecruitingPlaybook\Repositories\EmailTemplateRepository;

class EmailService {

    private PlaceholderService $placeholders;
    private EmailLogRepository $logRepository;
    private EmailTemplateRepository $templateRepository;
    private EmailQueueService $queueService;

    public function __construct() {
        $this->placeholders       = new PlaceholderService();
        $this->logRepository      = new EmailLogRepository();
        $this->templateRepository = new EmailTemplateRepository();
        $this->queueService       = new EmailQueueService();
    }

    /**
     * E-Mail mit Template senden
     *
     * @param int   $applicationId    Bewerbungs-ID
     * @param int   $templateId       Template-ID
     * @param array $customVariables  Zusätzliche Platzhalter
     * @param array $options          Optionen (send_immediately, scheduled_at)
     * @return array Result mit email_log_id
     */
    public function sendWithTemplate(
        int $applicationId,
        int $templateId,
        array $customVariables = [],
        array $options = []
    ): array {
        // Template laden
        $template = $this->templateRepository->find( $templateId );
        if ( ! $template ) {
            return [
                'success' => false,
                'error'   => __( 'Template nicht gefunden', 'recruiting-playbook' ),
            ];
        }

        // Kontext aufbauen
        $context = $this->buildContext( $applicationId, $customVariables );
        if ( is_wp_error( $context ) ) {
            return [
                'success' => false,
                'error'   => $context->get_error_message(),
            ];
        }

        // Platzhalter ersetzen
        $subject  = $this->placeholders->replace( $template['subject'], $context );
        $bodyHtml = $this->placeholders->replace( $template['body_html'], $context );
        $bodyText = $this->generatePlainText( $bodyHtml );

        // In Basis-Layout einbetten
        $bodyHtml = $this->wrapInLayout( $bodyHtml, $context );

        // E-Mail-Log erstellen
        $logId = $this->logRepository->create( [
            'application_id'  => $applicationId,
            'candidate_id'    => $context['candidate']['id'] ?? null,
            'template_id'     => $templateId,
            'recipient_email' => $context['candidate']['email'],
            'recipient_name'  => $context['candidate']['name'],
            'sender_email'    => $this->getFromEmail(),
            'sender_name'     => $this->getFromName(),
            'subject'         => $subject,
            'body_html'       => $bodyHtml,
            'body_text'       => $bodyText,
            'status'          => 'pending',
            'sent_by'         => get_current_user_id(),
            'scheduled_at'    => $options['scheduled_at'] ?? null,
        ] );

        // Sofort senden oder in Queue
        if ( ! empty( $options['send_immediately'] ) ) {
            return $this->sendNow( $logId );
        }

        // In Queue einreihen
        $this->queueService->enqueue( $logId, $options['scheduled_at'] ?? null );

        return [
            'success'      => true,
            'email_log_id' => $logId,
            'status'       => 'queued',
            'message'      => __( 'E-Mail wurde in die Warteschlange eingereiht', 'recruiting-playbook' ),
        ];
    }

    /**
     * E-Mail sofort senden
     *
     * @param int $logId E-Mail-Log-ID
     * @return array
     */
    public function sendNow( int $logId ): array {
        $email = $this->logRepository->find( $logId );
        if ( ! $email ) {
            return [
                'success' => false,
                'error'   => __( 'E-Mail nicht gefunden', 'recruiting-playbook' ),
            ];
        }

        // Header
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $email['sender_name'], $email['sender_email'] ),
        ];

        // Senden
        $sent = wp_mail(
            $email['recipient_email'],
            $email['subject'],
            $email['body_html'],
            $headers
        );

        // Log aktualisieren
        $this->logRepository->update( $logId, [
            'status'  => $sent ? 'sent' : 'failed',
            'sent_at' => current_time( 'mysql' ),
            'error_message' => $sent ? null : __( 'wp_mail fehlgeschlagen', 'recruiting-playbook' ),
        ] );

        // Activity Log
        if ( $sent && $email['application_id'] ) {
            $this->logActivity( $email );
        }

        return [
            'success'      => $sent,
            'email_log_id' => $logId,
            'status'       => $sent ? 'sent' : 'failed',
            'message'      => $sent
                ? __( 'E-Mail wurde gesendet', 'recruiting-playbook' )
                : __( 'E-Mail konnte nicht gesendet werden', 'recruiting-playbook' ),
        ];
    }

    /**
     * Kontext für Platzhalter aufbauen
     */
    private function buildContext( int $applicationId, array $custom = [] ): array|\WP_Error {
        global $wpdb;

        $tables = \RecruitingPlaybook\Database\Schema::getTables();

        // Bewerbung + Kandidat laden
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, c.id as candidate_id, c.salutation, c.first_name, c.last_name, c.email, c.phone
                FROM {$tables['applications']} a
                LEFT JOIN {$tables['candidates']} c ON a.candidate_id = c.id
                WHERE a.id = %d AND a.deleted_at IS NULL",
                $applicationId
            ),
            ARRAY_A
        );

        if ( ! $row ) {
            return new \WP_Error( 'not_found', __( 'Bewerbung nicht gefunden', 'recruiting-playbook' ) );
        }

        // Job laden
        $job = get_post( (int) $row['job_id'] );

        return [
            'application' => [
                'id'         => $applicationId,
                'status'     => $row['status'],
                'created_at' => $row['created_at'],
            ],
            'candidate' => [
                'id'         => $row['candidate_id'],
                'salutation' => $row['salutation'],
                'first_name' => $row['first_name'],
                'last_name'  => $row['last_name'],
                'name'       => trim( $row['first_name'] . ' ' . $row['last_name'] ),
                'email'      => $row['email'],
                'phone'      => $row['phone'],
            ],
            'job' => [
                'id'              => $row['job_id'],
                'title'           => $job ? $job->post_title : '',
                'url'             => $job ? get_permalink( $job ) : '',
                'location'        => $job ? get_post_meta( $job->ID, '_rp_location', true ) : '',
                'employment_type' => $job ? get_post_meta( $job->ID, '_rp_employment_type', true ) : '',
            ],
            'custom' => $custom,
        ];
    }

    /**
     * HTML in Basis-Layout einbetten
     */
    private function wrapInLayout( string $content, array $context ): string {
        $settings = get_option( 'rp_settings', [] );
        $company  = $settings['company_name'] ?? get_bloginfo( 'name' );
        $logo     = $settings['company_logo'] ?? '';

        ob_start();
        include RP_PLUGIN_DIR . 'templates/emails/base-layout.php';
        return ob_get_clean();
    }

    /**
     * Plain-Text aus HTML generieren
     */
    private function generatePlainText( string $html ): string {
        // Links umwandeln
        $text = preg_replace( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', '$2 ($1)', $html );

        // Tags entfernen
        $text = wp_strip_all_tags( $text );

        // Mehrfache Leerzeilen reduzieren
        $text = preg_replace( '/\n{3,}/', "\n\n", $text );

        return trim( $text );
    }

    /**
     * Activity Log Eintrag
     */
    private function logActivity( array $email ): void {
        $activityService = new ActivityService();

        $activityService->log( [
            'object_id' => $email['application_id'],
            'action'    => 'email_sent',
            'message'   => sprintf(
                __( 'E-Mail gesendet: %s', 'recruiting-playbook' ),
                $email['subject']
            ),
            'meta'      => [
                'email_log_id' => $email['id'],
                'template_id'  => $email['template_id'],
                'recipient'    => $email['recipient_email'],
            ],
        ] );
    }
}
```

### EmailComposer (React)

```jsx
/**
 * EmailComposer.jsx - E-Mail verfassen und senden
 */
import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    SelectControl,
    TextControl,
    TextareaControl,
    CheckboxControl,
    DateTimePicker,
    Modal,
    Spinner
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { EmailPreview } from './EmailPreview';

export function EmailComposer({ applicationId, onSent, onCancel }) {
    const [templates, setTemplates] = useState([]);
    const [selectedTemplate, setSelectedTemplate] = useState(null);
    const [subject, setSubject] = useState('');
    const [body, setBody] = useState('');
    const [customVariables, setCustomVariables] = useState({});
    const [sendImmediately, setSendImmediately] = useState(true);
    const [scheduledAt, setScheduledAt] = useState(null);
    const [showPreview, setShowPreview] = useState(false);
    const [isSending, setIsSending] = useState(false);
    const [isLoading, setIsLoading] = useState(true);

    // Templates laden
    useEffect(() => {
        apiFetch({ path: '/recruiting/v1/email-templates?is_active=true' })
            .then(data => {
                setTemplates(data);
                setIsLoading(false);
            })
            .catch(console.error);
    }, []);

    // Template-Auswahl
    const handleTemplateChange = (templateId) => {
        const template = templates.find(t => t.id === parseInt(templateId));
        setSelectedTemplate(template);
        if (template) {
            setSubject(template.subject);
            setBody(template.body_html);
        }
    };

    // Custom Variable ändern
    const handleVariableChange = (key, value) => {
        setCustomVariables(prev => ({
            ...prev,
            [key]: value
        }));
    };

    // E-Mail senden
    const handleSend = async () => {
        setIsSending(true);
        try {
            const result = await apiFetch({
                path: '/recruiting/v1/emails/send',
                method: 'POST',
                data: {
                    application_id: applicationId,
                    template_id: selectedTemplate?.id,
                    subject: subject !== selectedTemplate?.subject ? subject : undefined,
                    body: body !== selectedTemplate?.body_html ? body : undefined,
                    custom_variables: customVariables,
                    send_immediately: sendImmediately,
                    scheduled_at: scheduledAt,
                }
            });

            onSent(result);
        } catch (error) {
            console.error('Fehler beim Senden:', error);
        } finally {
            setIsSending(false);
        }
    };

    // Interview-Variablen anzeigen?
    const showInterviewFields = selectedTemplate?.category === 'interview';
    const showOfferFields = selectedTemplate?.category === 'offer';

    if (isLoading) {
        return <Spinner />;
    }

    return (
        <div className="rp-email-composer">
            <div className="rp-email-composer__header">
                <h2>{__('E-Mail senden', 'recruiting-playbook')}</h2>
            </div>

            <div className="rp-email-composer__content">
                {/* Template-Auswahl */}
                <SelectControl
                    label={__('Template', 'recruiting-playbook')}
                    value={selectedTemplate?.id || ''}
                    options={[
                        { value: '', label: __('-- Template wählen --', 'recruiting-playbook') },
                        ...templates.map(t => ({
                            value: t.id,
                            label: t.name
                        }))
                    ]}
                    onChange={handleTemplateChange}
                />

                {/* Betreff */}
                <TextControl
                    label={__('Betreff', 'recruiting-playbook')}
                    value={subject}
                    onChange={setSubject}
                />

                {/* Interview-spezifische Felder */}
                {showInterviewFields && (
                    <div className="rp-email-composer__interview-fields">
                        <h4>{__('Interview-Details', 'recruiting-playbook')}</h4>
                        <TextControl
                            label={__('Datum', 'recruiting-playbook')}
                            value={customVariables.termin_datum || ''}
                            onChange={v => handleVariableChange('termin_datum', v)}
                            placeholder="z.B. 28.01.2025"
                        />
                        <TextControl
                            label={__('Uhrzeit', 'recruiting-playbook')}
                            value={customVariables.termin_uhrzeit || ''}
                            onChange={v => handleVariableChange('termin_uhrzeit', v)}
                            placeholder="z.B. 14:00 Uhr"
                        />
                        <TextControl
                            label={__('Ort', 'recruiting-playbook')}
                            value={customVariables.termin_ort || ''}
                            onChange={v => handleVariableChange('termin_ort', v)}
                            placeholder="z.B. Hauptgebäude, Raum 302"
                        />
                        <TextControl
                            label={__('Teilnehmer', 'recruiting-playbook')}
                            value={customVariables.termin_teilnehmer || ''}
                            onChange={v => handleVariableChange('termin_teilnehmer', v)}
                            placeholder="z.B. Herr Müller (Abteilungsleiter)"
                        />
                    </div>
                )}

                {/* Angebot-spezifische Felder */}
                {showOfferFields && (
                    <div className="rp-email-composer__offer-fields">
                        <h4>{__('Angebots-Details', 'recruiting-playbook')}</h4>
                        <TextControl
                            label={__('Startdatum', 'recruiting-playbook')}
                            value={customVariables.start_datum || ''}
                            onChange={v => handleVariableChange('start_datum', v)}
                        />
                        <TextControl
                            label={__('Vertragsart', 'recruiting-playbook')}
                            value={customVariables.vertragsart || ''}
                            onChange={v => handleVariableChange('vertragsart', v)}
                        />
                        <TextControl
                            label={__('Antwortfrist', 'recruiting-playbook')}
                            value={customVariables.antwort_frist || ''}
                            onChange={v => handleVariableChange('antwort_frist', v)}
                        />
                    </div>
                )}

                {/* Versand-Optionen */}
                <div className="rp-email-composer__options">
                    <CheckboxControl
                        label={__('Sofort senden', 'recruiting-playbook')}
                        checked={sendImmediately}
                        onChange={setSendImmediately}
                    />

                    {!sendImmediately && (
                        <div className="rp-email-composer__schedule">
                            <label>{__('Geplanter Versand', 'recruiting-playbook')}</label>
                            <DateTimePicker
                                currentDate={scheduledAt}
                                onChange={setScheduledAt}
                                is12Hour={false}
                            />
                        </div>
                    )}
                </div>
            </div>

            {/* Footer */}
            <div className="rp-email-composer__footer">
                <Button variant="tertiary" onClick={onCancel}>
                    {__('Abbrechen', 'recruiting-playbook')}
                </Button>

                <Button
                    variant="secondary"
                    onClick={() => setShowPreview(true)}
                    disabled={!selectedTemplate}
                >
                    {__('Vorschau', 'recruiting-playbook')}
                </Button>

                <Button
                    variant="primary"
                    onClick={handleSend}
                    isBusy={isSending}
                    disabled={!selectedTemplate}
                >
                    {sendImmediately
                        ? __('Jetzt senden', 'recruiting-playbook')
                        : __('Planen', 'recruiting-playbook')
                    }
                </Button>
            </div>

            {/* Vorschau-Modal */}
            {showPreview && (
                <Modal
                    title={__('E-Mail-Vorschau', 'recruiting-playbook')}
                    onRequestClose={() => setShowPreview(false)}
                    className="rp-email-preview-modal"
                >
                    <EmailPreview
                        applicationId={applicationId}
                        templateId={selectedTemplate?.id}
                        subject={subject}
                        customVariables={customVariables}
                    />
                </Modal>
            )}
        </div>
    );
}
```

---

## 9. E-Mail-Historie

### EmailHistory (React)

```jsx
/**
 * EmailHistory.jsx - E-Mail-Historie für Bewerbung/Kandidat
 */
import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner, Modal } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { formatDate } from '../utils/date';

const STATUS_LABELS = {
    pending: { label: __('Ausstehend', 'recruiting-playbook'), color: '#dba617' },
    queued: { label: __('In Warteschlange', 'recruiting-playbook'), color: '#2271b1' },
    sent: { label: __('Gesendet', 'recruiting-playbook'), color: '#00a32a' },
    failed: { label: __('Fehlgeschlagen', 'recruiting-playbook'), color: '#d63638' },
    opened: { label: __('Geöffnet', 'recruiting-playbook'), color: '#00a32a' },
    clicked: { label: __('Geklickt', 'recruiting-playbook'), color: '#00a32a' },
};

export function EmailHistory({ applicationId, candidateId }) {
    const [emails, setEmails] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [selectedEmail, setSelectedEmail] = useState(null);

    useEffect(() => {
        const path = applicationId
            ? `/recruiting/v1/applications/${applicationId}/emails`
            : `/recruiting/v1/candidates/${candidateId}/emails`;

        apiFetch({ path })
            .then(data => {
                setEmails(data.items);
                setIsLoading(false);
            })
            .catch(console.error);
    }, [applicationId, candidateId]);

    // E-Mail erneut senden
    const handleResend = async (emailId) => {
        try {
            await apiFetch({
                path: `/recruiting/v1/emails/log/${emailId}/resend`,
                method: 'POST'
            });
            // Liste neu laden
            // ...
        } catch (error) {
            console.error('Fehler beim erneuten Senden:', error);
        }
    };

    if (isLoading) {
        return <Spinner />;
    }

    if (emails.length === 0) {
        return (
            <div className="rp-email-history__empty">
                <p>{__('Noch keine E-Mails gesendet', 'recruiting-playbook')}</p>
            </div>
        );
    }

    return (
        <div className="rp-email-history">
            <h3>{__('E-Mail-Verlauf', 'recruiting-playbook')}</h3>

            <div className="rp-email-history__list">
                {emails.map(email => {
                    const status = STATUS_LABELS[email.status] || STATUS_LABELS.pending;

                    return (
                        <div
                            key={email.id}
                            className="rp-email-history__item"
                            onClick={() => setSelectedEmail(email)}
                        >
                            <div className="rp-email-history__item-header">
                                <span
                                    className="rp-email-history__status"
                                    style={{ backgroundColor: status.color }}
                                >
                                    {status.label}
                                </span>
                                <span className="rp-email-history__date">
                                    {formatDate(email.sent_at || email.created_at)}
                                </span>
                            </div>

                            <div className="rp-email-history__subject">
                                {email.subject}
                            </div>

                            <div className="rp-email-history__meta">
                                {email.template && (
                                    <span className="rp-email-history__template">
                                        {email.template.name}
                                    </span>
                                )}
                                {email.sent_by && (
                                    <span className="rp-email-history__sender">
                                        {__('von', 'recruiting-playbook')} {email.sent_by.name}
                                    </span>
                                )}
                            </div>

                            {email.status === 'failed' && (
                                <div className="rp-email-history__error">
                                    {email.error_message}
                                </div>
                            )}

                            {/* Tracking-Info */}
                            {email.opened_at && (
                                <div className="rp-email-history__tracking">
                                    ✓ {__('Geöffnet am', 'recruiting-playbook')} {formatDate(email.opened_at)}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Detail-Modal */}
            {selectedEmail && (
                <Modal
                    title={selectedEmail.subject}
                    onRequestClose={() => setSelectedEmail(null)}
                    className="rp-email-detail-modal"
                >
                    <div className="rp-email-detail">
                        <div className="rp-email-detail__header">
                            <p>
                                <strong>{__('An:', 'recruiting-playbook')}</strong> {selectedEmail.recipient_email}
                            </p>
                            <p>
                                <strong>{__('Von:', 'recruiting-playbook')}</strong> {selectedEmail.sender_email}
                            </p>
                            <p>
                                <strong>{__('Datum:', 'recruiting-playbook')}</strong> {formatDate(selectedEmail.sent_at || selectedEmail.created_at)}
                            </p>
                        </div>

                        <div
                            className="rp-email-detail__body"
                            dangerouslySetInnerHTML={{ __html: selectedEmail.body_html }}
                        />

                        <div className="rp-email-detail__actions">
                            {selectedEmail.status === 'failed' && (
                                <Button
                                    variant="primary"
                                    onClick={() => handleResend(selectedEmail.id)}
                                >
                                    {__('Erneut senden', 'recruiting-playbook')}
                                </Button>
                            )}
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
}
```

---

## 10. Queue & Action Scheduler

### Action Scheduler Integration

```php
<?php
/**
 * Email Queue Service - Action Scheduler Integration
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

class EmailQueueService {

    /**
     * Action Scheduler Hook
     */
    private const HOOK_SEND_EMAIL = 'rp_send_queued_email';
    private const HOOK_SEND_BULK  = 'rp_send_bulk_email';
    private const GROUP           = 'recruiting-playbook-emails';

    /**
     * E-Mail in Queue einreihen
     *
     * @param int         $emailLogId   E-Mail-Log-ID
     * @param string|null $scheduledAt  Geplanter Zeitpunkt (null = sofort)
     * @return int Action ID
     */
    public function enqueue( int $emailLogId, ?string $scheduledAt = null ): int {
        // Log-Status aktualisieren
        $repository = new \RecruitingPlaybook\Repositories\EmailLogRepository();
        $repository->update( $emailLogId, [ 'status' => 'queued' ] );

        // In Action Scheduler einreihen
        $timestamp = $scheduledAt
            ? strtotime( $scheduledAt )
            : time();

        $actionId = as_schedule_single_action(
            $timestamp,
            self::HOOK_SEND_EMAIL,
            [ 'email_log_id' => $emailLogId ],
            self::GROUP
        );

        return $actionId;
    }

    /**
     * Bulk-E-Mails in Queue einreihen
     *
     * @param array       $emailLogIds  Array von E-Mail-Log-IDs
     * @param int         $batchSize    Batch-Größe
     * @param string|null $scheduledAt  Geplanter Zeitpunkt
     * @return int Anzahl eingereihter Batches
     */
    public function enqueueBulk( array $emailLogIds, int $batchSize = 10, ?string $scheduledAt = null ): int {
        $batches = array_chunk( $emailLogIds, $batchSize );
        $count   = 0;

        $timestamp = $scheduledAt ? strtotime( $scheduledAt ) : time();

        foreach ( $batches as $index => $batch ) {
            as_schedule_single_action(
                $timestamp + ( $index * 60 ), // 1 Minute Abstand zwischen Batches
                self::HOOK_SEND_BULK,
                [ 'email_log_ids' => $batch ],
                self::GROUP
            );
            ++$count;
        }

        return $count;
    }

    /**
     * Hooks registrieren
     */
    public static function registerHooks(): void {
        add_action( self::HOOK_SEND_EMAIL, [ self::class, 'processQueuedEmail' ] );
        add_action( self::HOOK_SEND_BULK, [ self::class, 'processBulkEmail' ] );
    }

    /**
     * Einzelne E-Mail aus Queue verarbeiten
     *
     * @param int $emailLogId E-Mail-Log-ID
     */
    public static function processQueuedEmail( int $emailLogId ): void {
        $emailService = new EmailService();
        $result       = $emailService->sendNow( $emailLogId );

        // Bei Fehler: Retry einplanen
        if ( ! $result['success'] ) {
            $repository = new \RecruitingPlaybook\Repositories\EmailLogRepository();
            $email      = $repository->find( $emailLogId );

            $retryCount = (int) ( $email['metadata']['retry_count'] ?? 0 );

            if ( $retryCount < 3 ) {
                // Retry mit Backoff
                $delay = pow( 2, $retryCount ) * 60; // 1min, 2min, 4min

                as_schedule_single_action(
                    time() + $delay,
                    self::HOOK_SEND_EMAIL,
                    [ 'email_log_id' => $emailLogId ],
                    self::GROUP
                );

                $repository->update( $emailLogId, [
                    'metadata' => wp_json_encode( [
                        'retry_count' => $retryCount + 1,
                        'last_retry'  => current_time( 'mysql' ),
                    ] ),
                ] );
            }
        }
    }

    /**
     * Bulk-E-Mails verarbeiten
     *
     * @param array $emailLogIds Array von E-Mail-Log-IDs
     */
    public static function processBulkEmail( array $emailLogIds ): void {
        $emailService = new EmailService();

        foreach ( $emailLogIds as $emailLogId ) {
            $emailService->sendNow( $emailLogId );

            // Kurze Pause zwischen E-Mails
            usleep( 100000 ); // 100ms
        }
    }

    /**
     * Queue-Status abrufen
     *
     * @return array
     */
    public function getQueueStatus(): array {
        $pending = as_get_scheduled_actions( [
            'hook'   => self::HOOK_SEND_EMAIL,
            'status' => \ActionScheduler_Store::STATUS_PENDING,
            'group'  => self::GROUP,
        ], 'ids' );

        $running = as_get_scheduled_actions( [
            'hook'   => self::HOOK_SEND_EMAIL,
            'status' => \ActionScheduler_Store::STATUS_RUNNING,
            'group'  => self::GROUP,
        ], 'ids' );

        $failed = as_get_scheduled_actions( [
            'hook'   => self::HOOK_SEND_EMAIL,
            'status' => \ActionScheduler_Store::STATUS_FAILED,
            'group'  => self::GROUP,
            'date'   => as_get_datetime_object( '-24 hours' ),
        ], 'ids' );

        return [
            'pending' => count( $pending ),
            'running' => count( $running ),
            'failed'  => count( $failed ),
        ];
    }
}
```

### Composer-Abhängigkeit

```json
{
    "require": {
        "woocommerce/action-scheduler": "^3.7"
    }
}
```

---

## 11. Admin-Oberfläche

### Menü-Integration

```php
<?php
/**
 * Email Settings Page - Admin-Seite für E-Mail-Templates
 */

namespace RecruitingPlaybook\Admin\Pages;

class EmailSettingsPage {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'registerSubmenu' ] );
    }

    public function registerSubmenu(): void {
        add_submenu_page(
            'recruiting-playbook',
            __( 'E-Mail-Templates', 'recruiting-playbook' ),
            __( 'E-Mail-Templates', 'recruiting-playbook' ),
            'manage_options',
            'rp-email-templates',
            [ $this, 'render' ]
        );
    }

    public function render(): void {
        // React-App Container
        echo '<div id="rp-email-templates-app" class="wrap"></div>';

        // Assets einbinden
        wp_enqueue_script( 'rp-admin-email' );
        wp_enqueue_style( 'rp-admin-email' );

        // Lokalisierung
        wp_localize_script( 'rp-admin-email', 'rpEmailData', [
            'apiBase' => rest_url( 'recruiting/v1' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'isProActive' => rp_can( 'email_templates' ),
        ] );
    }
}
```

### E-Mail-Tab in Bewerber-Detail

Die E-Mail-Historie und der Composer werden in die bestehende ApplicantDetail-Komponente integriert:

```jsx
// In ApplicantDetail.jsx
import { EmailHistory } from '../email/EmailHistory';
import { EmailComposer } from '../email/EmailComposer';

// Tab hinzufügen
const tabs = [
    // ... existing tabs
    { key: 'emails', label: __('E-Mails', 'recruiting-playbook'), icon: 'email' },
];

// Tab-Content
{activeTab === 'emails' && (
    <div className="rp-applicant-detail__emails">
        <Button
            variant="primary"
            onClick={() => setShowEmailComposer(true)}
        >
            {__('E-Mail senden', 'recruiting-playbook')}
        </Button>

        <EmailHistory applicationId={applicationId} />

        {showEmailComposer && (
            <Modal
                title={__('E-Mail senden', 'recruiting-playbook')}
                onRequestClose={() => setShowEmailComposer(false)}
            >
                <EmailComposer
                    applicationId={applicationId}
                    onSent={() => {
                        setShowEmailComposer(false);
                        // Refresh history
                    }}
                    onCancel={() => setShowEmailComposer(false)}
                />
            </Modal>
        )}
    </div>
)}
```

---

## 12. Berechtigungen

### Capabilities

| Capability | Beschreibung | Rollen |
|------------|--------------|--------|
| `rp_manage_email_templates` | Templates erstellen/bearbeiten | Administrator |
| `rp_send_emails` | E-Mails versenden | Administrator, Recruiter |
| `rp_view_email_log` | E-Mail-Historie einsehen | Administrator, Recruiter |
| `rp_resend_emails` | E-Mails erneut senden | Administrator |

### Permission Checks

```php
/**
 * Template-Verwaltung
 */
public function manage_templates_permissions_check(): bool {
    return current_user_can( 'rp_manage_email_templates' );
}

/**
 * E-Mail-Versand
 */
public function send_email_permissions_check(): bool {
    return current_user_can( 'rp_send_emails' );
}

/**
 * E-Mail-Log einsehen
 */
public function view_email_log_permissions_check(): bool {
    return current_user_can( 'rp_view_email_log' );
}
```

---

## 13. Testing

### Unit Tests

#### EmailTemplateServiceTest

```php
<?php
/**
 * Tests für EmailTemplateService
 */

namespace RecruitingPlaybook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use RecruitingPlaybook\Services\EmailTemplateService;

class EmailTemplateServiceTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_create_template_with_valid_data(): void {
        Functions\when( 'sanitize_text_field' )->returnArg( 1 );
        Functions\when( 'wp_kses_post' )->returnArg( 1 );
        Functions\when( 'current_time' )->justReturn( '2025-01-25 12:00:00' );

        // Mock Repository
        $repository = $this->createMock( EmailTemplateRepository::class );
        $repository->expects( $this->once() )
            ->method( 'create' )
            ->willReturn( 1 );

        $service = new EmailTemplateService( $repository );

        $result = $service->create( [
            'name'      => 'Test Template',
            'subject'   => 'Test Subject',
            'body_html' => '<p>Test Body</p>',
            'category'  => 'custom',
        ] );

        $this->assertArrayHasKey( 'id', $result );
    }

    public function test_create_template_fails_without_required_fields(): void {
        $service = new EmailTemplateService();

        $result = $service->create( [
            'name' => 'Test Template',
            // Missing subject and body_html
        ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_fields', $result->get_error_code() );
    }
}
```

#### PlaceholderServiceTest

```php
<?php
/**
 * Tests für PlaceholderService
 */

namespace RecruitingPlaybook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use RecruitingPlaybook\Services\PlaceholderService;

class PlaceholderServiceTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_replace_single_placeholder(): void {
        $service = new PlaceholderService();

        $text    = 'Hallo {vorname}!';
        $context = [
            'candidate' => [ 'first_name' => 'Max' ],
        ];

        $result = $service->replace( $text, $context );

        $this->assertEquals( 'Hallo Max!', $result );
    }

    public function test_replace_multiple_placeholders(): void {
        $service = new PlaceholderService();

        $text    = '{anrede_formal}, Ihre Bewerbung als {stelle} ist eingegangen.';
        $context = [
            'candidate' => [
                'salutation' => 'Herr',
                'first_name' => 'Max',
                'last_name'  => 'Mustermann',
            ],
            'job' => [
                'title' => 'PHP Developer',
            ],
        ];

        Functions\when( '__' )
            ->justReturn( 'Sehr geehrter Herr' );

        $result = $service->replace( $text, $context );

        $this->assertStringContainsString( 'Mustermann', $result );
        $this->assertStringContainsString( 'PHP Developer', $result );
    }

    public function test_unknown_placeholders_are_removed(): void {
        $service = new PlaceholderService();

        $text    = 'Hallo {unknown_placeholder}!';
        $context = [];

        $result = $service->replace( $text, $context );

        $this->assertEquals( 'Hallo !', $result );
    }

    public function test_formal_greeting_with_salutation(): void {
        $service = new PlaceholderService();

        Functions\when( '__' )
            ->alias( function( $text ) {
                return match( $text ) {
                    'Sehr geehrter Herr' => 'Sehr geehrter Herr',
                    'Sehr geehrte Frau'  => 'Sehr geehrte Frau',
                    default              => $text,
                };
            } );

        $context = [
            'candidate' => [
                'salutation' => 'Herr',
                'last_name'  => 'Müller',
            ],
        ];

        $placeholders = $service->resolve( $context );

        $this->assertEquals( 'Sehr geehrter Herr Müller', $placeholders['anrede_formal'] );
    }

    public function test_formal_greeting_without_salutation(): void {
        $service = new PlaceholderService();

        Functions\when( '__' )
            ->justReturn( 'Guten Tag' );

        $context = [
            'candidate' => [
                'first_name' => 'Max',
            ],
        ];

        $placeholders = $service->resolve( $context );

        $this->assertStringContainsString( 'Guten Tag', $placeholders['anrede_formal'] );
    }
}
```

#### EmailQueueServiceTest

```php
<?php
/**
 * Tests für EmailQueueService
 */

namespace RecruitingPlaybook\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use RecruitingPlaybook\Services\EmailQueueService;

class EmailQueueServiceTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_enqueue_schedules_action(): void {
        Functions\when( 'as_schedule_single_action' )->justReturn( 123 );

        $repository = $this->createMock( EmailLogRepository::class );
        $repository->expects( $this->once() )
            ->method( 'update' )
            ->with( 1, [ 'status' => 'queued' ] );

        $service  = new EmailQueueService( $repository );
        $actionId = $service->enqueue( 1 );

        $this->assertEquals( 123, $actionId );
    }

    public function test_enqueue_with_scheduled_time(): void {
        $scheduledAt = '2025-01-26 14:00:00';

        Functions\when( 'as_schedule_single_action' )
            ->alias( function( $timestamp, $hook, $args, $group ) use ( $scheduledAt ) {
                $this->assertEquals( strtotime( $scheduledAt ), $timestamp );
                return 456;
            } );

        $repository = $this->createMock( EmailLogRepository::class );
        $repository->method( 'update' )->willReturn( true );

        $service  = new EmailQueueService( $repository );
        $actionId = $service->enqueue( 1, $scheduledAt );

        $this->assertEquals( 456, $actionId );
    }

    public function test_bulk_enqueue_creates_batches(): void {
        $callCount = 0;
        Functions\when( 'as_schedule_single_action' )
            ->alias( function() use ( &$callCount ) {
                return ++$callCount;
            } );

        $repository = $this->createMock( EmailLogRepository::class );
        $service    = new EmailQueueService( $repository );

        // 25 E-Mails, Batch-Größe 10 = 3 Batches
        $emailLogIds = range( 1, 25 );
        $result      = $service->enqueueBulk( $emailLogIds, 10 );

        $this->assertEquals( 3, $result );
    }
}
```

### Integration Tests

```php
<?php
/**
 * Integration Test für E-Mail-Versand
 */

namespace RecruitingPlaybook\Tests\Integration;

use WP_UnitTestCase;
use RecruitingPlaybook\Services\EmailService;
use RecruitingPlaybook\Services\EmailTemplateService;

class EmailIntegrationTest extends WP_UnitTestCase {

    private EmailService $emailService;
    private EmailTemplateService $templateService;
    private int $applicationId;
    private int $templateId;

    public function setUp(): void {
        parent::setUp();

        $this->emailService    = new EmailService();
        $this->templateService = new EmailTemplateService();

        // Test-Daten erstellen
        $this->createTestData();
    }

    private function createTestData(): void {
        global $wpdb;

        // Kandidat
        $wpdb->insert( $wpdb->prefix . 'rp_candidates', [
            'email'      => 'test@example.com',
            'first_name' => 'Test',
            'last_name'  => 'User',
            'salutation' => 'Herr',
        ] );
        $candidateId = $wpdb->insert_id;

        // Job
        $jobId = wp_insert_post( [
            'post_type'   => 'job_listing',
            'post_title'  => 'Test Developer',
            'post_status' => 'publish',
        ] );

        // Bewerbung
        $wpdb->insert( $wpdb->prefix . 'rp_applications', [
            'candidate_id' => $candidateId,
            'job_id'       => $jobId,
            'status'       => 'new',
        ] );
        $this->applicationId = $wpdb->insert_id;

        // Template
        $result = $this->templateService->create( [
            'name'      => 'Test Template',
            'subject'   => 'Betreff: {stelle}',
            'body_html' => '<p>Hallo {vorname} {nachname}</p>',
            'category'  => 'custom',
        ] );
        $this->templateId = $result['id'];
    }

    public function test_send_email_with_placeholders(): void {
        $result = $this->emailService->sendWithTemplate(
            $this->applicationId,
            $this->templateId,
            [],
            [ 'send_immediately' => true ]
        );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'sent', $result['status'] );

        // E-Mail-Log prüfen
        global $wpdb;
        $log = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rp_email_log WHERE id = %d",
                $result['email_log_id']
            ),
            ARRAY_A
        );

        $this->assertStringContainsString( 'Test Developer', $log['subject'] );
        $this->assertStringContainsString( 'Test User', $log['body_html'] );
    }

    public function test_email_appears_in_history(): void {
        // E-Mail senden
        $this->emailService->sendWithTemplate(
            $this->applicationId,
            $this->templateId,
            [],
            [ 'send_immediately' => true ]
        );

        // Historie abrufen
        $logRepository = new \RecruitingPlaybook\Repositories\EmailLogRepository();
        $history = $logRepository->findByApplication( $this->applicationId );

        $this->assertCount( 1, $history );
        $this->assertEquals( 'sent', $history[0]['status'] );
    }
}
```

### Test-Abdeckung

| Bereich | Ziel | Priorität |
|---------|------|-----------|
| PlaceholderService | 90% | Hoch |
| EmailTemplateService | 80% | Hoch |
| EmailQueueService | 70% | Mittel |
| REST API Endpoints | 60% | Mittel |
| React Components | 50% | Niedrig |

---

## Anhang

### A. Migration

```php
<?php
/**
 * Migration für E-Mail-System Tabellen
 */

namespace RecruitingPlaybook\Database\Migrations;

class CreateEmailTables {

    public function up(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        // rp_email_templates
        $sql = "CREATE TABLE {$wpdb->prefix}rp_email_templates (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            body_html longtext NOT NULL,
            body_text longtext DEFAULT NULL,
            category varchar(50) DEFAULT 'custom',
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            variables longtext DEFAULT NULL,
            settings longtext DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY category (category),
            KEY is_active (is_active),
            KEY is_default (is_default),
            KEY deleted_at (deleted_at)
        ) {$charset};";

        dbDelta( $sql );

        // rp_email_log
        $sql = "CREATE TABLE {$wpdb->prefix}rp_email_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            application_id bigint(20) unsigned DEFAULT NULL,
            candidate_id bigint(20) unsigned DEFAULT NULL,
            template_id bigint(20) unsigned DEFAULT NULL,
            recipient_email varchar(255) NOT NULL,
            recipient_name varchar(255) DEFAULT NULL,
            sender_email varchar(255) NOT NULL,
            sender_name varchar(255) DEFAULT NULL,
            subject varchar(255) NOT NULL,
            body_html longtext NOT NULL,
            body_text longtext DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            error_message text DEFAULT NULL,
            opened_at datetime DEFAULT NULL,
            clicked_at datetime DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            sent_by bigint(20) unsigned DEFAULT NULL,
            scheduled_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY application_id (application_id),
            KEY candidate_id (candidate_id),
            KEY template_id (template_id),
            KEY recipient_email (recipient_email),
            KEY status (status),
            KEY sent_at (sent_at),
            KEY created_at (created_at)
        ) {$charset};";

        dbDelta( $sql );

        // Standard-Templates einfügen
        $this->seedDefaultTemplates();
    }

    private function seedDefaultTemplates(): void {
        $templateService = new \RecruitingPlaybook\Services\EmailTemplateService();

        $defaults = [
            [
                'slug'       => 'application-confirmation',
                'name'       => 'Bewerbungsbestätigung',
                'subject'    => 'Ihre Bewerbung bei {firma}: {stelle}',
                'category'   => 'application',
                'is_default' => true,
                'body_html'  => $this->getDefaultConfirmationBody(),
            ],
            [
                'slug'       => 'rejection-standard',
                'name'       => 'Absage (Standard)',
                'subject'    => 'Ihre Bewerbung als {stelle}',
                'category'   => 'application',
                'is_default' => true,
                'body_html'  => $this->getDefaultRejectionBody(),
            ],
            [
                'slug'       => 'interview-invitation',
                'name'       => 'Interview-Einladung',
                'subject'    => 'Einladung zum Vorstellungsgespräch: {stelle}',
                'category'   => 'interview',
                'is_default' => true,
                'body_html'  => $this->getDefaultInterviewBody(),
            ],
        ];

        foreach ( $defaults as $template ) {
            $templateService->createSystemTemplate( $template );
        }
    }

    // Template-Inhalte...
}
```

### B. Checkliste Implementierung

- [ ] **Datenbank**
  - [ ] Migration für `rp_email_templates`
  - [ ] Migration für `rp_email_log`
  - [ ] Schema.php erweitern
  - [ ] Standard-Templates seeden

- [ ] **Backend Services**
  - [ ] EmailTemplateService
  - [ ] PlaceholderService
  - [ ] EmailQueueService
  - [ ] EmailService erweitern
  - [ ] EmailLogRepository
  - [ ] EmailTemplateRepository

- [ ] **REST API**
  - [ ] EmailTemplateController
  - [ ] EmailController
  - [ ] EmailLogController

- [ ] **Frontend**
  - [ ] TemplateList.jsx
  - [ ] TemplateEditor.jsx
  - [ ] PlaceholderPicker.jsx
  - [ ] EmailComposer.jsx
  - [ ] EmailHistory.jsx
  - [ ] EmailPreview.jsx
  - [ ] admin-email.css

- [ ] **Integration**
  - [ ] Action Scheduler einbinden
  - [ ] ApplicantDetail E-Mail-Tab
  - [ ] Menü-Eintrag
  - [ ] Feature-Gating

- [ ] **Testing**
  - [ ] PlaceholderServiceTest
  - [ ] EmailTemplateServiceTest
  - [ ] EmailQueueServiceTest
  - [ ] Integration Tests

---

*Erstellt: Januar 2025*
*Version: 1.0*

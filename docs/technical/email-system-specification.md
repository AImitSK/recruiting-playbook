# E-Mail-System Pro: Technische Spezifikation

> **Pro-Feature: Professionelles E-Mail-System**
> Template-Editor, Platzhalter, E-Mail-Historie und Queue-basierter Versand

---

> ⚠️ **WICHTIG: Konzept-Aktualisierung (Januar 2025)**
>
> Diese Spezifikation wird durch folgende Dokumente ergänzt/überschrieben:
>
> **→ [email-signature-specification.md](email-signature-specification.md)**
>
> Wesentliche Änderungen:
> - **Templates enthalten keine Signatur mehr** – Signatur wird separat verwaltet
> - **Platzhalter bereinigt** – 17 Pseudo-Variablen entfernt (termin_*, absender_*, kontakt_*, etc.)
> - **Neue Tab-Struktur**: E-Mail-Templates → [Vorlagen] [Signaturen] [Automatisierung]
> - **Firmendaten-Tab** unter Einstellungen
> - **Nur 3 automatisierbare E-Mails**: Eingangsbestätigung, Absage, Zurückgezogen
> - **Manuelle Templates mit Lücken** (`___`) statt Pseudo-Variablen

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Datenmodell](#3-datenmodell)
4. [Repository Layer](#4-repository-layer)
5. [REST API Endpunkte](#5-rest-api-endpunkte)
6. [E-Mail-Templates](#6-e-mail-templates)
7. [Template-Editor](#7-template-editor)
8. [Platzhalter-System](#8-platzhalter-system)
9. [E-Mail-Versand](#9-e-mail-versand)
10. [E-Mail-Historie](#10-e-mail-historie)
11. [Queue & Action Scheduler](#11-queue--action-scheduler)
12. [Admin-Oberfläche](#12-admin-oberfläche)
13. [Berechtigungen](#13-berechtigungen)
14. [Testing](#14-testing)

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
    is_system       tinyint(1) DEFAULT 0,
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
    KEY is_system (is_system),
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
| `is_system` | tinyint | System-Template (nicht löschbar, nur duplizierbar) |
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
    KEY scheduled_at (scheduled_at),
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

## 4. Repository Layer

Die Repository-Schicht kapselt den Datenbankzugriff für E-Mail-Templates und E-Mail-Logs. Alle Repositories folgen dem etablierten Pattern aus Phase 1 (siehe `NoteRepository`, `RatingRepository`).

### 4.1 EmailTemplateRepository

**Namespace:** `RecruitingPlaybook\Repositories\EmailTemplateRepository`

**Verantwortlichkeiten:**
- CRUD-Operationen für `rp_email_templates`
- Slug-Generierung und -Validierung
- Soft-Delete-Unterstützung
- Template-Duplizierung
- System-Template-Schutz

#### Konstruktor

```php
public function __construct()
```

Initialisiert Repository mit Tabellenname aus `Schema::getTables()['email_templates']`.

#### Methoden

##### create( array $data ): int|false

Erstellt neues E-Mail-Template.

**Parameter:**
- `$data['name']` (string, required) - Template-Name
- `$data['subject']` (string, required) - E-Mail-Betreff
- `$data['body_html']` (string, required) - HTML-Inhalt
- `$data['body_text']` (string, optional) - Plain-Text-Version
- `$data['category']` (string, default: 'custom') - Kategorie
- `$data['slug']` (string, optional) - Wird aus name generiert wenn leer
- `$data['variables']` (array, optional) - JSON: Verwendete Platzhalter
- `$data['settings']` (array, optional) - JSON: Zusätzliche Einstellungen
- `$data['is_active']` (int, default: 1) - Aktiv-Status
- `$data['is_default']` (int, default: 0) - Standard für Kategorie
- `$data['is_system']` (int, default: 0) - System-Template (nicht löschbar)

**Return:** Insert-ID oder `false` bei Fehler

**Beispiel:**
```php
$repository = new EmailTemplateRepository();
$id = $repository->create([
    'name' => 'Interview-Einladung',
    'subject' => 'Einladung zum Vorstellungsgespräch: {stelle}',
    'body_html' => '<p>Guten Tag {anrede} {nachname}...</p>',
    'category' => 'interview',
    'variables' => ['anrede', 'nachname', 'stelle', 'termin_datum'],
]);
```

##### find( int $id ): ?array

Lädt Template per ID. Ignoriert soft-deleted Templates.

**Return:** Angereichertes Template-Array oder `null` wenn nicht gefunden

**Enrichment:** Fügt hinzu:
- `created_by_user` - User-Objekt (id, name)
- `can_edit`, `can_delete` - Berechtigungs-Flags
- Type-Casting für id, booleans
- JSON-Dekodierung für variables, settings

##### findBySlug( string $slug ): ?array

Lädt Template per Slug.

**Return:** Template-Array oder `null`

##### findDefault( string $category ): ?array

Lädt Standard-Template für Kategorie (WHERE `is_default = 1` AND `is_active = 1`).

**Return:** Template-Array oder `null`

##### findByCategory( string $category ): array

Lädt alle aktiven Templates einer Kategorie.

**Return:** Array von Templates

##### getList( array $args = [] ): array

Lädt alle Templates mit Filterung und Sortierung.

**Args:**
- `category` (string) - Filter per Kategorie
- `is_active` (bool) - Nur aktive Templates
- `search` (string) - Suche in name und subject
- `orderby` (string, default: 'name') - Sortierung: name, category, created_at, updated_at
- `order` (string, default: 'ASC') - ASC oder DESC

**Return:** Array von Templates

##### update( int $id, array $data ): bool

Aktualisiert Template. Setzt automatisch `updated_at`.

**Return:** `true` bei Erfolg, `false` bei Fehler

##### softDelete( int $id ): bool

Soft-Delete für Template. **System-Templates (`is_system = 1`) können nicht gelöscht werden.**

**Return:** `true` bei Erfolg, `false` bei Fehler oder System-Template

##### duplicate( int $id, string $new_name = '' ): int|false

Dupliziert Template. Generiert automatisch Namen ("Name (Kopie)") wenn `$new_name` leer.

**Return:** Neue Template-ID oder `false`

##### slugExists( string $slug, ?int $exclude_id = null ): bool

Prüft ob Slug bereits existiert. Optional eine ID ausschließen (für Updates).

**Return:** `true` wenn Slug existiert

---

### 4.2 EmailLogRepository

**Namespace:** `RecruitingPlaybook\Repositories\EmailLogRepository`

**Verantwortlichkeiten:**
- CRUD-Operationen für `rp_email_log`
- E-Mail-Tracking (opened, clicked)
- Statistiken und Reporting
- Queue-Verwaltung

#### Methoden

##### create( array $data ): int|false

Erstellt Log-Eintrag für gesendete/geplante E-Mail.

**Parameter:**
- `$data['application_id']` (int, optional) - FK zu rp_applications
- `$data['candidate_id']` (int, optional) - FK zu rp_candidates
- `$data['template_id']` (int, optional) - FK zu rp_email_templates
- `$data['recipient_email']` (string, required) - Empfänger-E-Mail
- `$data['recipient_name']` (string, optional) - Empfänger-Name
- `$data['sender_email']` (string, required) - Absender-E-Mail
- `$data['sender_name']` (string, optional) - Absender-Name
- `$data['subject']` (string, required) - Betreff (nach Platzhalter-Ersetzung)
- `$data['body_html']` (string, required) - HTML-Inhalt (nach Ersetzung)
- `$data['body_text']` (string, optional) - Plain-Text
- `$data['status']` (string, default: 'pending') - Status: pending, queued, sent, failed, cancelled
- `$data['scheduled_at']` (string, optional) - Geplanter Versandzeitpunkt
- `$data['metadata']` (array, optional) - JSON: Zusätzliche Daten
- `$data['sent_by']` (int, optional) - FK zu wp_users

**Return:** Insert-ID oder `false`

##### find( int $id ): ?array

Lädt Log-Eintrag per ID.

**Enrichment:**
- `sent_by_user` - User-Objekt (id, name)
- `status_label`, `status_color` - UI-Daten für Status
- `can_cancel`, `can_resend` - Berechtigungs-Flags
- Type-Casting für alle IDs

##### findByApplication( int $application_id, array $args = [] ): array

Lädt alle E-Mails einer Bewerbung mit Paginierung.

**Args:**
- `per_page` (int, default: 20)
- `page` (int, default: 1)
- `status` (string, optional) - Filter per Status

**Return:**
```php
[
    'items' => [...],  // Array von Log-Einträgen
    'total' => 42,     // Gesamt-Anzahl
    'pages' => 3       // Anzahl Seiten
]
```

##### findByCandidate( int $candidate_id, array $args = [] ): array

Lädt alle E-Mails eines Kandidaten (über alle Bewerbungen). Gleiche Args/Return wie `findByApplication()`.

##### getList( array $args = [] ): array

Lädt alle Logs mit Filterung, Suche und Paginierung.

**Args:**
- `per_page`, `page` - Pagination
- `status` (string) - Filter per Status
- `search` (string) - Suche in recipient_email, recipient_name, subject
- `orderby` (string, default: 'created_at') - created_at, sent_at, status, recipient_email
- `order` (string, default: 'DESC')

**Return:** Paginiertes Array wie `findByApplication()`

##### getPendingForQueue( int $limit = 50 ): array

Lädt pending E-Mails für Queue-Verarbeitung.

**Bedingung:** `status = 'pending'` AND (`scheduled_at IS NULL` OR `scheduled_at <= NOW()`)

**Return:** Array von Log-Einträgen (max. `$limit`)

##### getScheduled( array $args = [] ): array

Lädt geplante E-Mails (`scheduled_at > NOW()` AND `status = 'pending'`).

**Return:** Paginiertes Array

##### update( int $id, array $data ): bool

Aktualisiert Log-Eintrag.

**Return:** `true` bei Erfolg

##### updateStatus( int $id, string $status, string $error = '' ): bool

Dedizierte Methode für Status-Aktualisierung.

**Verhalten:**
- Bei `status = 'sent'`: Setzt `sent_at` automatisch
- Bei `status = 'failed'`: Speichert `$error` in `error_message`

**Return:** `true` bei Erfolg

##### markAsOpened( int $id ): bool

Setzt `opened_at` Timestamp. Nur wenn noch nicht gesetzt (E-Mail-Tracking-Pixel).

**Return:** `true` bei Erfolg, `false` wenn bereits geöffnet

##### markAsClicked( int $id ): bool

Setzt `clicked_at` Timestamp. Setzt auch `opened_at` wenn noch nicht gesetzt.

**Return:** `true` bei Erfolg, `false` wenn bereits geklickt

##### cancelScheduled( int $id ): bool

Storniert geplante E-Mail (setzt `status = 'cancelled'`). Nur für `status = 'pending'`.

**Return:** `true` bei Erfolg, `false` wenn nicht pending

##### getStatistics( string $start_date, string $end_date ): array

Statistiken für Zeitraum.

**Parameter:**
- `$start_date` (string) - Format: 'Y-m-d'
- `$end_date` (string) - Format: 'Y-m-d'

**Return:**
```php
[
    'total' => 100,          // Gesamt-Anzahl
    'sent' => 95,            // Erfolgreich gesendet
    'failed' => 3,           // Fehlgeschlagen
    'pending' => 2,          // Ausstehend
    'cancelled' => 0,        // Storniert
    'opened' => 60,          // Geöffnet
    'clicked' => 25,         // Geklickt
    'open_rate' => 63.2,     // % (opened / sent)
    'click_rate' => 26.3,    // % (clicked / sent)
    'success_rate' => 95.0   // % (sent / total)
]
```

---

### 4.3 Pattern-Konventionen

Alle Repositories folgen diesen Konventionen:

**Constructor:**
```php
public function __construct() {
    $this->table = Schema::getTables()['table_name'];
}
```

**CRUD-Signaturen:**
- `create( array $data ): int|false`
- `find( int $id ): ?array`
- `update( int $id, array $data ): bool`
- `softDelete( int $id ): bool` (nur für Tabellen mit `deleted_at`)

**Enrichment-Pattern:**
```php
private function enrichTemplate( array $template ): array {
    // 1. JSON dekodieren
    // 2. User-Daten laden
    // 3. Berechtigungen prüfen
    // 4. Type-Casting
    return $template;
}
```

**Sicherheit:**
- Immer `$wpdb->prepare()` mit Platzhaltern
- `$wpdb->esc_like()` für LIKE-Queries
- `wp_json_encode()`/`json_decode()` für JSON-Felder
- `defined( 'ABSPATH' ) || exit;` in jeder Datei

**Type Safety:**
- `declare(strict_types=1);`
- Return-Type-Declarations für alle Methoden
- Parameter-Type-Hints konsequent nutzen

---

## 5. REST API Endpunkte

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
| POST | `/recruiting/v1/email-templates/{id}/set-default` | Als Standard für Kategorie setzen |
| GET | `/recruiting/v1/email-templates/placeholders` | Verfügbare Platzhalter laden |
| GET | `/recruiting/v1/email-templates/categories` | Template-Kategorien laden |

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
| POST | `/recruiting/v1/emails/{id}/cancel` | Geplante E-Mail stornieren |
| GET | `/recruiting/v1/emails/queue-stats` | Warteschlangen-Statistiken |

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
| GET | `/recruiting/v1/emails/log/scheduled` | Geplante E-Mails abrufen |
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

## 6. E-Mail-Templates

> ⚠️ **VERALTET** – Siehe [email-signature-specification.md](email-signature-specification.md) für die neuen Templates.
>
> **Wichtige Änderungen:**
> - Templates enthalten **keine Signatur mehr** (kein `{absender_name}`, kein "Mit freundlichen Grüßen")
> - Signatur wird **automatisch angehängt** basierend auf User-Auswahl
> - Interview/Angebots-Templates nutzen **Lücken (`___`)** statt Pseudo-Variablen
> - Neue Templates: `Aufnahme in Talent-Pool`, `Passende Stelle verfügbar`

### ~~Standard-Templates~~ (VERALTET)

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

## 7. Template-Editor

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

## 8. Platzhalter-System

> ⚠️ **VERALTET** – Siehe [email-signature-specification.md](email-signature-specification.md) für die bereinigte Platzhalter-Liste.
>
> **Entfernte Platzhalter:**
> - `{absender_*}` → Kommt aus Signatur
> - `{kontakt_*}` → Kommt aus Firmendaten
> - `{termin_*}` → Pseudo-Variable (manuelle Eingabe)
> - `{start_datum}`, `{vertragsart}`, `{arbeitszeit}`, `{antwort_frist}` → Pseudo-Variablen
>
> **Verbleibende echte Platzhalter (16 Stück):**
> - Bewerber: `{anrede}`, `{anrede_formal}`, `{vorname}`, `{nachname}`, `{name}`, `{email}`, `{telefon}`
> - Bewerbung: `{bewerbung_id}`, `{bewerbung_datum}`, `{bewerbung_status}`
> - Stelle: `{stelle}`, `{stelle_ort}`, `{stelle_typ}`, `{stelle_url}`
> - Firma: `{firma}`, `{firma_website}`

### ~~Verfügbare Platzhalter~~ (VERALTET)

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

    /**
     * Platzhalter nach Gruppen gruppiert abrufen
     * (Alternative Struktur für Frontend)
     */
    public function getPlaceholdersByGroup(): array {
        $result = [];
        foreach ( self::PLACEHOLDERS as $key => $config ) {
            $group = $config['group'];
            if ( ! isset( $result[ $group ] ) ) {
                $result[ $group ] = [];
            }
            $result[ $group ][ $key ] = $config['label'];
        }
        return $result;
    }

    /**
     * Preview-Werte für Template-Vorschau
     */
    public function getPreviewValues(): array {
        return [
            // Kandidat
            'anrede'          => 'Herr',
            'anrede_formal'   => 'Sehr geehrter Herr Mustermann',
            'vorname'         => 'Max',
            'nachname'        => 'Mustermann',
            'name'            => 'Max Mustermann',
            'email'           => 'max.mustermann@example.com',
            'telefon'         => '+49 123 456789',
            // Bewerbung
            'bewerbung_id'    => 'RP-2024-0001',
            'bewerbung_datum' => '15. Januar 2024',
            'bewerbung_status'=> 'In Prüfung',
            // Stelle
            'stelle'          => 'Senior Software Developer',
            'stelle_ort'      => 'München',
            'stelle_typ'      => 'Vollzeit',
            'stelle_url'      => 'https://example.com/jobs/senior-developer',
            // Firma
            'firma'           => 'Muster GmbH',
            'firma_website'   => 'https://example.com',
            // ... weitere Preview-Werte
        ];
    }

    /**
     * Template-Vorschau mit Preview-Werten rendern
     */
    public function renderPreview( string $text ): string {
        $preview_values = $this->getPreviewValues();

        foreach ( $preview_values as $key => $value ) {
            $text = str_replace( "{{$key}}", $value, $text );
        }

        // Unbekannte Platzhalter markieren
        return preg_replace(
            '/\{([a-z_]+)\}/',
            '<span class="unknown-placeholder">{$1}</span>',
            $text
        );
    }

    /**
     * Platzhalter in Text finden
     */
    public function findPlaceholders( string $text ): array {
        preg_match_all( '/\{([a-z_]+)\}/', $text, $matches );
        return array_unique( $matches[1] ?? [] );
    }

    /**
     * Prüfen ob Platzhalter gültig ist
     */
    public function isValidPlaceholder( string $key ): bool {
        return isset( self::PLACEHOLDERS[ $key ] );
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

## 9. E-Mail-Versand

### EmailService (erweitert)

Der bestehende `EmailService` wird um Pro-Features erweitert: Template-basierter Versand, Queue-Integration und Historie-Abruf.

**Architektur:** Lazy-Loading Pattern für Services (bessere Performance, nur bei Bedarf instanziiert).

```php
<?php
/**
 * Email Service - Erweiterter E-Mail-Versand
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use RecruitingPlaybook\Repositories\EmailLogRepository;

class EmailService {

    private string $from_email;
    private string $from_name;

    // Lazy-loaded Services
    private ?EmailTemplateService $templateService = null;
    private ?EmailQueueService $queueService = null;
    private ?PlaceholderService $placeholderService = null;
    private ?EmailLogRepository $logRepository = null;

    public function __construct() {
        $settings = get_option( 'rp_settings', [] );
        $this->from_email = $settings['notification_email'] ?? get_option( 'admin_email' );
        $this->from_name  = $settings['company_name'] ?? get_bloginfo( 'name' );
    }

    // Lazy-Loader für Services
    private function getTemplateService(): EmailTemplateService {
        return $this->templateService ??= new EmailTemplateService();
    }

    private function getQueueService(): EmailQueueService {
        return $this->queueService ??= new EmailQueueService();
    }

    private function getPlaceholderService(): PlaceholderService {
        return $this->placeholderService ??= new PlaceholderService();
    }

    private function getLogRepository(): EmailLogRepository {
        return $this->logRepository ??= new EmailLogRepository();
    }

    /**
     * E-Mail mit Template senden (Pro-Feature)
     *
     * @param int   $template_id    Template-ID
     * @param int   $application_id Bewerbungs-ID
     * @param array $custom_data    Zusätzliche Platzhalter-Daten
     * @param bool  $use_queue      Queue verwenden (true) oder direkt senden (false)
     * @return int|bool Log-ID bei Queue, true bei direktem Versand, false bei Fehler
     */
    public function sendWithTemplate(
        int $template_id,
        int $application_id,
        array $custom_data = [],
        bool $use_queue = true
    ): int|bool {
        // Pro-Feature Check
        if ( ! function_exists( 'rp_can' ) || ! rp_can( 'email_templates' ) ) {
            return false;
        }

        $application = $this->getApplicationData( $application_id );
        if ( ! $application ) {
            return false;
        }

        // Kontext für Platzhalter aufbauen
        $context = $this->buildContext( $application, $custom_data );

        // Template rendern
        $rendered = $this->getTemplateService()->render( $template_id, $context );
        if ( ! $rendered ) {
            return false;
        }

        $email_data = [
            'application_id'  => $application_id,
            'candidate_id'    => (int) $application['candidate_id'],
            'template_id'     => $template_id,
            'recipient_email' => $application['email'],
            'recipient_name'  => $application['candidate_name'],
            'sender_email'    => $this->from_email,
            'sender_name'     => $this->from_name,
            'subject'         => $rendered['subject'],
            'body_html'       => $rendered['body_html'],
            'body_text'       => $rendered['body_text'],
        ];

        if ( $use_queue && $this->getQueueService()->isActionSchedulerAvailable() ) {
            return $this->getQueueService()->enqueue( $email_data );
        }

        // Direkt senden
        $sent = $this->send( $email_data['recipient_email'], $email_data['subject'], $email_data['body_html'] );

        // In Log speichern
        $email_data['status'] = $sent ? 'sent' : 'failed';
        if ( $sent ) {
            $email_data['sent_at'] = current_time( 'mysql' );
        }
        $this->getLogRepository()->create( $email_data );

        return $sent;
    }

    /**
     * E-Mail per Template-Slug senden
     */
    public function sendWithTemplateSlug(
        string $template_slug,
        int $application_id,
        array $custom_data = [],
        bool $use_queue = true
    ): int|bool {
        $template = $this->getTemplateService()->findBySlug( $template_slug );
        if ( ! $template ) {
            return false;
        }
        return $this->sendWithTemplate( (int) $template['id'], $application_id, $custom_data, $use_queue );
    }

    /**
     * Benutzerdefinierte E-Mail senden (ohne Template)
     */
    public function sendCustomEmail(
        int $application_id,
        string $subject,
        string $body_html,
        bool $use_queue = true
    ): int|bool {
        $application = $this->getApplicationData( $application_id );
        if ( ! $application ) {
            return false;
        }

        $context = $this->buildContext( $application, [] );

        // Platzhalter ersetzen
        $subject   = $this->getPlaceholderService()->replace( $subject, $context );
        $body_html = $this->getPlaceholderService()->replace( $body_html, $context );

        $email_data = [
            'application_id'  => $application_id,
            'candidate_id'    => (int) $application['candidate_id'],
            'recipient_email' => $application['email'],
            'recipient_name'  => $application['candidate_name'],
            'sender_email'    => $this->from_email,
            'sender_name'     => $this->from_name,
            'subject'         => $subject,
            'body_html'       => $body_html,
            'body_text'       => wp_strip_all_tags( $body_html ),
        ];

        if ( $use_queue && $this->getQueueService()->isActionSchedulerAvailable() ) {
            return $this->getQueueService()->enqueue( $email_data );
        }

        $sent = $this->send( $email_data['recipient_email'], $email_data['subject'], $email_data['body_html'] );
        $email_data['status'] = $sent ? 'sent' : 'failed';
        if ( $sent ) {
            $email_data['sent_at'] = current_time( 'mysql' );
        }
        $this->getLogRepository()->create( $email_data );

        return $sent;
    }

    /**
     * E-Mail für späteren Versand planen
     */
    public function scheduleEmail(
        int $template_id,
        int $application_id,
        string $scheduled_at,
        array $custom_data = []
    ): int|false {
        if ( ! function_exists( 'rp_can' ) || ! rp_can( 'email_templates' ) ) {
            return false;
        }

        $application = $this->getApplicationData( $application_id );
        if ( ! $application ) {
            return false;
        }

        $context = $this->buildContext( $application, $custom_data );
        $rendered = $this->getTemplateService()->render( $template_id, $context );
        if ( ! $rendered ) {
            return false;
        }

        return $this->getQueueService()->schedule( [
            'application_id'  => $application_id,
            'candidate_id'    => (int) $application['candidate_id'],
            'template_id'     => $template_id,
            'recipient_email' => $application['email'],
            'recipient_name'  => $application['candidate_name'],
            'sender_email'    => $this->from_email,
            'sender_name'     => $this->from_name,
            'subject'         => $rendered['subject'],
            'body_html'       => $rendered['body_html'],
            'body_text'       => $rendered['body_text'],
        ], $scheduled_at );
    }

    /**
     * E-Mail-Historie für Bewerbung abrufen
     */
    public function getHistory( int $application_id, array $args = [] ): array {
        return $this->getLogRepository()->findByApplication( $application_id, $args );
    }

    /**
     * E-Mail-Historie für Kandidaten abrufen
     */
    public function getHistoryByCandidate( int $candidate_id, array $args = [] ): array {
        return $this->getLogRepository()->findByCandidate( $candidate_id, $args );
    }

    /**
     * Kontext für Platzhalter aufbauen
     */
    private function buildContext( array $application, array $custom_data ): array {
        $job = null;
        if ( ! empty( $application['job_id'] ) ) {
            $job_post = get_post( (int) $application['job_id'] );
            if ( $job_post ) {
                $job = [
                    'title'           => $job_post->post_title,
                    'url'             => get_permalink( $job_post ),
                    'location'        => get_post_meta( $job_post->ID, '_job_location', true ) ?: '',
                    'employment_type' => get_post_meta( $job_post->ID, '_employment_type', true ) ?: '',
                ];
            }
        }

        return [
            'application' => $application,
            'candidate'   => [
                'salutation'  => $application['salutation'] ?? '',
                'first_name'  => $application['first_name'] ?? '',
                'last_name'   => $application['last_name'] ?? '',
                'email'       => $application['email'] ?? '',
                'phone'       => $application['phone'] ?? '',
            ],
            'job'         => $job ?? [],
            'custom'      => $custom_data,
        ];
    }

    // ... bestehende send(), getApplicationData() etc. bleiben unverändert
}
```

### EmailTemplateService

Geschäftslogik für Template-Verwaltung, Rendering und Validierung.

```php
class EmailTemplateService {

    private EmailTemplateRepository $repository;
    private PlaceholderService $placeholderService;

    /**
     * Template erstellen
     */
    public function create( array $data ): array|false;

    /**
     * Template aktualisieren (System-Templates eingeschränkt)
     */
    public function update( int $id, array $data ): array|false;

    /**
     * Template löschen (System-Templates geschützt)
     */
    public function delete( int $id ): bool;

    /**
     * Template duplizieren
     */
    public function duplicate( int $id, string $new_name = '' ): array|false;

    /**
     * Template per ID laden
     */
    public function find( int $id ): ?array;

    /**
     * Template per Slug laden
     */
    public function findBySlug( string $slug ): ?array;

    /**
     * Standard-Template für Kategorie laden
     */
    public function getDefault( string $category ): ?array;

    /**
     * Template als Standard setzen
     */
    public function setAsDefault( int $id, string $category ): bool;

    /**
     * Template rendern mit Kontext
     * @return array{subject: string, body_html: string, body_text: string}|null
     */
    public function render( int $template_id, array $context ): ?array;

    /**
     * Template-Vorschau mit Preview-Daten
     * @return array{subject: string, body_html: string}|null
     */
    public function preview( int $template_id ): ?array;

    /**
     * System-Template auf Standard zurücksetzen
     */
    public function resetToDefault( int $id ): bool;

    /**
     * Verfügbare Kategorien
     */
    public function getCategories(): array {
        return [
            'application' => [ 'label' => 'Bewerbung', 'description' => 'Templates für Bewerbungsprozess' ],
            'interview'   => [ 'label' => 'Interview', 'description' => 'Templates für Intervieweinladungen' ],
            'offer'       => [ 'label' => 'Angebot', 'description' => 'Templates für Stellenangebote' ],
            'custom'      => [ 'label' => 'Benutzerdefiniert', 'description' => 'Eigene Templates' ],
        ];
    }
}
```

### Hinweis zur buildContext() Methode

Die `buildContext()` Methode erwartet ein bereits geladenes `$application` Array (aus `getApplicationData()`), nicht eine ID. Dies ermöglicht:
- Vermeidung doppelter DB-Abfragen
- Bessere Testbarkeit (Dependency Injection)
- Konsistenz mit bestehendem `getApplicationData()` Pattern

```php
// Verwendung:
$application = $this->getApplicationData( $application_id );
if ( ! $application ) {
    return false;
}
$context = $this->buildContext( $application, $custom_data );
```

---

### Alte Signatur (veraltet, nicht mehr gültig)

Die folgende Signatur war ursprünglich geplant, wurde aber zugunsten des oben beschriebenen Patterns verworfen:

```php
// VERALTET - Nicht implementiert!
// private function buildContext( int $applicationId, array $custom = [] ): array|\WP_Error {
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

## 10. E-Mail-Historie

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

## 11. Queue & Action Scheduler

### Action Scheduler Integration

```php
<?php
/**
 * Email Queue Service - Action Scheduler Integration
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use RecruitingPlaybook\Repositories\EmailLogRepository;

class EmailQueueService {

    /**
     * Action Scheduler Hooks
     */
    private const HOOK_SEND_EMAIL    = 'rp_send_queued_email';
    private const HOOK_PROCESS_QUEUE = 'rp_process_email_queue';

    /**
     * Konfiguration
     */
    private const MAX_RETRIES = 3;
    private const BATCH_SIZE  = 50;

    private EmailLogRepository $logRepository;

    public function __construct( ?EmailLogRepository $logRepository = null ) {
        $this->logRepository = $logRepository ?? new EmailLogRepository();
    }

    /**
     * E-Mail zur Queue hinzufügen
     *
     * Erstellt einen Log-Eintrag und plant den Versand.
     *
     * @param array $email_data E-Mail-Daten mit:
     *   - recipient_email (required)
     *   - sender_email (required)
     *   - subject (required)
     *   - body_html (required)
     *   - application_id, candidate_id, template_id (optional)
     *   - recipient_name, sender_name, body_text (optional)
     *   - scheduled_at (optional, für zeitversetzten Versand)
     *   - metadata (optional)
     * @return int|false Log-ID oder false bei Fehler
     */
    public function enqueue( array $email_data ): int|false {
        // Log-Eintrag erstellen
        $log_id = $this->logRepository->create( [
            'application_id'  => $email_data['application_id'] ?? null,
            'candidate_id'    => $email_data['candidate_id'] ?? null,
            'template_id'     => $email_data['template_id'] ?? null,
            'recipient_email' => $email_data['recipient_email'],
            'recipient_name'  => $email_data['recipient_name'] ?? '',
            'sender_email'    => $email_data['sender_email'],
            'sender_name'     => $email_data['sender_name'] ?? '',
            'subject'         => $email_data['subject'],
            'body_html'       => $email_data['body_html'],
            'body_text'       => $email_data['body_text'] ?? '',
            'status'          => 'pending',
            'scheduled_at'    => $email_data['scheduled_at'] ?? null,
            'metadata'        => $email_data['metadata'] ?? [],
        ] );

        if ( false === $log_id ) {
            return false;
        }

        // Action Scheduler Job erstellen
        $this->scheduleEmail( $log_id, $email_data['scheduled_at'] ?? null );

        return $log_id;
    }

    /**
     * E-Mail für späteren Versand planen
     */
    public function schedule( array $email_data, string $scheduled_at ): int|false {
        $email_data['scheduled_at'] = $scheduled_at;
        return $this->enqueue( $email_data );
    }

    /**
     * Geplante E-Mail stornieren
     */
    public function cancel( int $log_id ): bool {
        $log = $this->logRepository->find( $log_id );

        if ( ! $log || 'pending' !== $log['status'] ) {
            return false;
        }

        $this->unscheduleEmail( $log_id );
        return $this->logRepository->updateStatus( $log_id, 'cancelled' );
    }

    /**
     * E-Mail erneut senden
     */
    public function resend( int $log_id ): int|false {
        $log = $this->logRepository->find( $log_id );

        if ( ! $log ) {
            return false;
        }

        return $this->enqueue( [
            'application_id'  => $log['application_id'],
            'candidate_id'    => $log['candidate_id'],
            'template_id'     => $log['template_id'],
            'recipient_email' => $log['recipient_email'],
            'recipient_name'  => $log['recipient_name'],
            'sender_email'    => $log['sender_email'],
            'sender_name'     => $log['sender_name'],
            'subject'         => $log['subject'],
            'body_html'       => $log['body_html'],
            'body_text'       => $log['body_text'],
            'metadata'        => array_merge(
                $log['metadata'] ?? [],
                [ 'resent_from' => $log_id ]
            ),
        ] );
    }

    /**
     * Hooks registrieren
     */
    public function registerHooks(): void {
        add_action( self::HOOK_SEND_EMAIL, [ $this, 'processSingleEmail' ], 10, 1 );
        add_action( self::HOOK_PROCESS_QUEUE, [ $this, 'processQueue' ] );
    }

    /**
     * Einzelne E-Mail verarbeiten (Action Scheduler Callback)
     */
    public function processSingleEmail( int $log_id ): void {
        $log = $this->logRepository->find( $log_id );

        if ( ! $log || 'pending' !== $log['status'] ) {
            return;
        }

        $this->logRepository->updateStatus( $log_id, 'queued' );
        $result = $this->sendEmail( $log );

        if ( $result ) {
            $this->logRepository->updateStatus( $log_id, 'sent' );
            $this->logActivity( $log, 'sent' );
        } else {
            $retry_count = (int) ( $log['metadata']['retry_count'] ?? 0 );

            if ( $retry_count < self::MAX_RETRIES ) {
                // Exponential Backoff: 1, 2, 4 Minuten
                $delay = pow( 2, $retry_count ) * 60;
                $this->logRepository->update( $log_id, [
                    'status'   => 'pending',
                    'metadata' => array_merge(
                        $log['metadata'] ?? [],
                        [ 'retry_count' => $retry_count + 1 ]
                    ),
                ] );
                $this->scheduleEmail( $log_id, gmdate( 'Y-m-d H:i:s', time() + $delay ) );
            } else {
                $this->logRepository->updateStatus(
                    $log_id,
                    'failed',
                    __( 'Maximale Versuche erreicht', 'recruiting-playbook' )
                );
            }
        }
    }

    /**
     * Queue-Statistiken (letzte 24 Stunden)
     */
    public function getQueueStats(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'rp_email_log';

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM {$table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            ARRAY_A
        );

        return [
            'total'      => (int) ( $stats['total'] ?? 0 ),
            'pending'    => (int) ( $stats['pending'] ?? 0 ),
            'processing' => (int) ( $stats['processing'] ?? 0 ),
            'sent'       => (int) ( $stats['sent'] ?? 0 ),
            'failed'     => (int) ( $stats['failed'] ?? 0 ),
        ];
    }

    /**
     * Prüfen ob Action Scheduler verfügbar ist
     */
    public function isActionSchedulerAvailable(): bool {
        return function_exists( 'as_enqueue_async_action' );
    }

    /**
     * Queue-Verarbeitung starten (Cron-Job registrieren)
     */
    public function scheduleQueueProcessing(): void {
        if ( ! $this->isActionSchedulerAvailable() ) {
            return;
        }

        if ( false === as_has_scheduled_action( self::HOOK_PROCESS_QUEUE, [], 'recruiting-playbook' ) ) {
            as_schedule_recurring_action(
                time(),
                5 * MINUTE_IN_SECONDS,
                self::HOOK_PROCESS_QUEUE,
                [],
                'recruiting-playbook'
            );
        }
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

## 12. Admin-Oberfläche

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

## 13. Berechtigungen

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

## 14. Testing

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
            is_system tinyint(1) DEFAULT 0,
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
            KEY is_system (is_system),
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
            KEY scheduled_at (scheduled_at),
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

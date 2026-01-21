# Datenbank-Schema

## Übersicht

Das Plugin verwendet einen **Hybrid-Ansatz**:

| Daten | Speicherung | Grund |
|-------|-------------|-------|
| Jobs (Stellen) | WordPress Posts | WPML, SEO, Gutenberg |
| Bewerbungen | Custom Table | Performance, viele Einträge |
| Kandidaten | Custom Table | DSGVO, Talent-Pool, Wiederbewerber |
| Dokumente | Custom Table + Dateisystem | Datenschutz, nicht in Media Library |
| Aktivitäts-Log | Custom Table | Audit-Trail, Historie |
| Einstellungen | wp_options | WordPress-Standard |

```
┌─────────────────────────────────────────────────────────────────┐
│                     DATENBANK-ARCHITEKTUR                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  WORDPRESS STANDARD              CUSTOM TABLES                  │
│  ─────────────────               ─────────────                  │
│                                                                 │
│  ┌─────────────┐                ┌─────────────────────┐        │
│  │  wp_posts   │                │ rp_applications     │        │
│  │  (Jobs)     │◄──────────────►│                     │        │
│  └─────────────┘                └──────────┬──────────┘        │
│         │                                  │                    │
│         │                                  │                    │
│  ┌─────────────┐                ┌──────────▼──────────┐        │
│  │wp_postmeta  │                │ rp_candidates       │        │
│  │(Job-Felder) │                │                     │        │
│  └─────────────┘                └──────────┬──────────┘        │
│         │                                  │                    │
│         │                                  │                    │
│  ┌─────────────┐                ┌──────────▼──────────┐        │
│  │wp_terms     │                │ rp_documents        │        │
│  │(Kategorien) │                │                     │        │
│  └─────────────┘                └─────────────────────┘        │
│                                                                 │
│                                 ┌─────────────────────┐        │
│                                 │ rp_activity_log     │        │
│                                 │                     │        │
│                                 └─────────────────────┘        │
│                                                                 │
│                                 ┌─────────────────────┐        │
│                                 │ rp_api_keys         │        │
│                                 │                     │        │
│                                 └─────────────────────┘        │
│                                                                 │
│                                 ┌─────────────────────┐        │
│                                 │ rp_webhooks         │        │
│                                 │                     │        │
│                                 └─────────────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

Präfix: rp_ (recruiting playbook)
```

---

## 1. Jobs (Stellen) – WordPress Posts

### Post Type: `job_listing`

```php
register_post_type('job_listing', [
    'labels' => [...],
    'public' => true,
    'has_archive' => true,
    'rewrite' => ['slug' => 'jobs'],
    'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
    'show_in_rest' => true,  // Gutenberg
    'menu_icon' => 'dashicons-businessman',
]);
```

### Taxonomies

```php
// Kategorie (Pflege, Verwaltung, etc.)
register_taxonomy('job_category', 'job_listing', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'job-category'],
    'show_in_rest' => true,
]);

// Standort
register_taxonomy('job_location', 'job_listing', [
    'hierarchical' => true,
    'rewrite' => ['slug' => 'job-location'],
    'show_in_rest' => true,
]);

// Beschäftigungsart
register_taxonomy('employment_type', 'job_listing', [
    'hierarchical' => false,
    'rewrite' => ['slug' => 'employment-type'],
    'show_in_rest' => true,
]);
```

### Post Meta (wp_postmeta)

| Meta Key | Typ | Beschreibung |
|----------|-----|--------------|
| `_rp_salary_min` | int | Gehalt von |
| `_rp_salary_max` | int | Gehalt bis |
| `_rp_salary_currency` | string | Währung (EUR, CHF, etc.) |
| `_rp_salary_period` | string | hour, month, year |
| `_rp_salary_public` | bool | Gehalt anzeigen? |
| `_rp_application_deadline` | date | Bewerbungsfrist |
| `_rp_start_date` | date | Eintrittsdatum |
| `_rp_remote` | string | no, hybrid, full |
| `_rp_address_street` | string | Straße |
| `_rp_address_postal` | string | PLZ |
| `_rp_address_city` | string | Stadt |
| `_rp_address_country` | string | Land (ISO 3166-1) |
| `_rp_contact_user_id` | int | Ansprechpartner (User ID) |
| `_rp_contact_name` | string | Oder: Name manuell |
| `_rp_contact_email` | string | Kontakt-E-Mail |
| `_rp_contact_phone` | string | Kontakt-Telefon |
| `_rp_application_count` | int | Cache: Anzahl Bewerbungen |
| `_rp_custom_fields` | json | Zusätzliche Felder |

### Post Status

| Status | WordPress | Beschreibung |
|--------|-----------|--------------|
| Entwurf | `draft` | Nicht veröffentlicht |
| Aktiv | `publish` | Online, Bewerbungen möglich |
| Pausiert | `private` | Temporär offline |
| Archiviert | `rp_archived` | Custom Status, abgeschlossen |

```php
// Custom Post Status
register_post_status('rp_archived', [
    'label' => __('Archiviert', 'recruiting-playbook'),
    'public' => false,
    'exclude_from_search' => true,
    'show_in_admin_all_list' => true,
    'show_in_admin_status_list' => true,
]);
```

---

## 2. Kandidaten – Custom Table

### Tabelle: `{prefix}rp_candidates`

Speichert Personen unabhängig von einzelnen Bewerbungen (Wiederbewerber, Talent-Pool).

```sql
CREATE TABLE {prefix}rp_candidates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Stammdaten
    email VARCHAR(255) NOT NULL,
    email_hash VARCHAR(64) NOT NULL,  -- SHA256 für Duplikat-Check
    salutation VARCHAR(20),           -- Herr, Frau, Divers, NULL
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    mobile VARCHAR(50),
    
    -- Adresse
    address_street VARCHAR(255),
    address_postal VARCHAR(20),
    address_city VARCHAR(100),
    address_country VARCHAR(2),       -- ISO 3166-1 alpha-2
    
    -- Zusatzdaten
    date_of_birth DATE,
    nationality VARCHAR(2),
    
    -- Talent-Pool
    talent_pool TINYINT(1) DEFAULT 0,
    talent_pool_consent_at DATETIME,
    talent_pool_expires_at DATETIME,
    
    -- Tracking
    source VARCHAR(50),               -- website, indeed, referral, etc.
    source_detail VARCHAR(255),       -- Kampagne, Referrer-Name, etc.
    
    -- Metadaten
    custom_fields JSON,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME,              -- Soft Delete für DSGVO
    
    PRIMARY KEY (id),
    UNIQUE KEY email_hash (email_hash),
    KEY email (email),
    KEY last_name (last_name),
    KEY talent_pool (talent_pool),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Anmerkungen

- **email_hash:** Ermöglicht schnelle Duplikat-Prüfung ohne E-Mail im Klartext zu vergleichen
- **Soft Delete:** `deleted_at` statt echtem Löschen, für DSGVO-Nachweis
- **Talent-Pool:** Separate Einwilligung mit Ablaufdatum

---

## 3. Bewerbungen – Custom Table

### Tabelle: `{prefix}rp_applications`

```sql
CREATE TABLE {prefix}rp_applications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Verknüpfungen
    job_id BIGINT UNSIGNED NOT NULL,        -- wp_posts.ID
    candidate_id BIGINT UNSIGNED NOT NULL,  -- rp_candidates.id
    
    -- Status
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    status_changed_at DATETIME,
    status_changed_by BIGINT UNSIGNED,      -- wp_users.ID
    
    -- Bewerbungsdaten
    cover_letter LONGTEXT,
    salary_expectation INT UNSIGNED,
    earliest_start_date DATE,
    
    -- Bewertung
    rating TINYINT UNSIGNED,                -- 1-5
    rating_details JSON,                    -- { qualification: 4, experience: 3, ... }
    
    -- Quelle
    source VARCHAR(50),                     -- website, api, import
    source_url VARCHAR(500),                -- Referrer
    ip_address VARCHAR(45),                 -- IPv4 oder IPv6
    user_agent VARCHAR(500),
    
    -- Consent (DSGVO)
    consent_privacy TINYINT(1) NOT NULL DEFAULT 0,
    consent_privacy_version VARCHAR(20),
    consent_privacy_at DATETIME,
    consent_talent_pool TINYINT(1) DEFAULT 0,
    consent_talent_pool_at DATETIME,
    
    -- Kommunikation
    email_sent_count INT UNSIGNED DEFAULT 0,
    last_email_sent_at DATETIME,
    
    -- Metadaten
    custom_fields JSON,
    internal_notes TEXT,                    -- Nur für schnelle Notiz, Details in activity_log
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME,                    -- Soft Delete
    
    PRIMARY KEY (id),
    KEY job_id (job_id),
    KEY candidate_id (candidate_id),
    KEY status (status),
    KEY created_at (created_at),
    KEY job_status (job_id, status),
    
    CONSTRAINT fk_application_candidate 
        FOREIGN KEY (candidate_id) REFERENCES {prefix}rp_candidates(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Status-Werte

| Status | Beschreibung | Nächste mögliche Status |
|--------|--------------|-------------------------|
| `new` | Neu eingegangen | screening, rejected, withdrawn |
| `screening` | In Prüfung | interview, rejected, withdrawn |
| `interview` | Gespräch geplant/durchgeführt | offer, rejected, withdrawn |
| `offer` | Angebot unterbreitet | hired, rejected, withdrawn |
| `hired` | Eingestellt | - |
| `rejected` | Abgelehnt | - |
| `withdrawn` | Vom Bewerber zurückgezogen | - |

---

## 4. Dokumente – Custom Table + Dateisystem

### Tabelle: `{prefix}rp_documents`

```sql
CREATE TABLE {prefix}rp_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Verknüpfung
    application_id BIGINT UNSIGNED NOT NULL,
    candidate_id BIGINT UNSIGNED NOT NULL,
    
    -- Dokumentinfo
    type VARCHAR(30) NOT NULL,              -- cv, cover_letter, certificate, other
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,  -- UUID-basiert
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,     -- Bytes
    file_hash VARCHAR(64),                  -- SHA256 für Duplikate
    
    -- Speicherort
    storage_path VARCHAR(500) NOT NULL,     -- Relativer Pfad
    
    -- Metadaten
    title VARCHAR(255),                     -- Optional: Benutzerfreundlicher Name
    description TEXT,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    
    PRIMARY KEY (id),
    KEY application_id (application_id),
    KEY candidate_id (candidate_id),
    KEY type (type),
    
    CONSTRAINT fk_document_application 
        FOREIGN KEY (application_id) REFERENCES {prefix}rp_applications(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_document_candidate 
        FOREIGN KEY (candidate_id) REFERENCES {prefix}rp_candidates(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Dateisystem-Struktur

```
wp-content/
└── uploads/
    └── recruiting-playbook/
        └── documents/
            ├── .htaccess          # Direktzugriff blockieren
            ├── index.php          # Leer, verhindert Directory Listing
            └── 2025/
                └── 01/
                    ├── a1b2c3d4-e5f6-7890-abcd-ef1234567890.pdf
                    ├── b2c3d4e5-f6a7-8901-bcde-f12345678901.pdf
                    └── ...
```

### Sicherheit

```apache
# .htaccess in /documents/
Order Deny,Allow
Deny from all

# Oder für nginx (in Server-Config):
location ~* /uploads/recruiting-playbook/documents/ {
    deny all;
    return 403;
}
```

Dateien werden nur über PHP ausgeliefert mit Berechtigungsprüfung:

```php
// Download-Endpoint
// /wp-json/recruiting/v1/documents/{id}/download
function serve_document($document_id) {
    // 1. Berechtigung prüfen
    // 2. Datei laden
    // 3. Mit korrekten Headern ausliefern
}
```

---

## 5. Aktivitäts-Log – Custom Table

### Tabelle: `{prefix}rp_activity_log`

Vollständige Historie aller Änderungen (Audit-Trail).

```sql
CREATE TABLE {prefix}rp_activity_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Kontext
    object_type VARCHAR(30) NOT NULL,       -- job, application, candidate, document
    object_id BIGINT UNSIGNED NOT NULL,
    
    -- Aktion
    action VARCHAR(50) NOT NULL,            -- created, updated, status_changed, etc.
    
    -- Wer
    user_id BIGINT UNSIGNED,                -- wp_users.ID, NULL = System/API
    user_name VARCHAR(100),                 -- Cache für gelöschte User
    api_key_id BIGINT UNSIGNED,             -- Falls via API
    
    -- Was
    old_value JSON,
    new_value JSON,
    message TEXT,                           -- Menschenlesbare Beschreibung
    
    -- Metadaten
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    
    -- Timestamp
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    KEY object (object_type, object_id),
    KEY user_id (user_id),
    KEY action (action),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Beispiel-Einträge

```json
// Status-Änderung
{
    "object_type": "application",
    "object_id": 456,
    "action": "status_changed",
    "user_id": 5,
    "user_name": "Maria Schmidt",
    "old_value": { "status": "new" },
    "new_value": { "status": "screening" },
    "message": "Status geändert von 'Neu' zu 'In Prüfung'"
}

// Notiz hinzugefügt
{
    "object_type": "application",
    "object_id": 456,
    "action": "note_added",
    "user_id": 5,
    "new_value": { 
        "note": "Telefonat geführt, Termin für nächste Woche vereinbart" 
    },
    "message": "Notiz hinzugefügt"
}

// DSGVO-Löschung
{
    "object_type": "candidate",
    "object_id": 123,
    "action": "deleted_gdpr",
    "user_id": 1,
    "old_value": { "email_hash": "abc123...", "name": "Max M." },
    "message": "Kandidat auf Anfrage gelöscht (DSGVO Art. 17)"
}
```

---

## 6. API-Keys – Custom Table

### Tabelle: `{prefix}rp_api_keys`

```sql
CREATE TABLE {prefix}rp_api_keys (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Identifikation
    name VARCHAR(100) NOT NULL,
    key_prefix VARCHAR(10) NOT NULL,        -- rp_live_ oder rp_test_
    key_hash VARCHAR(64) NOT NULL,          -- SHA256 des Keys
    key_hint VARCHAR(10) NOT NULL,          -- Letzte 4 Zeichen
    
    -- Berechtigungen
    permissions JSON NOT NULL,              -- { jobs_read: true, jobs_write: false, ... }
    
    -- Limits
    rate_limit INT UNSIGNED DEFAULT 1000,   -- Requests pro Stunde
    
    -- Tracking
    last_used_at DATETIME,
    request_count BIGINT UNSIGNED DEFAULT 0,
    
    -- Verknüpfung
    created_by BIGINT UNSIGNED NOT NULL,    -- wp_users.ID
    
    -- Status
    is_active TINYINT(1) DEFAULT 1,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    revoked_at DATETIME,
    
    PRIMARY KEY (id),
    UNIQUE KEY key_hash (key_hash),
    KEY created_by (created_by),
    KEY is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Berechtigungen (JSON)

```json
{
    "jobs_read": true,
    "jobs_write": true,
    "applications_read": true,
    "applications_write": false,
    "candidates_read": true,
    "candidates_write": false,
    "documents_read": true,
    "reports_read": true,
    "settings_read": false,
    "settings_write": false
}
```

---

## 7. Webhooks – Custom Table

### Tabelle: `{prefix}rp_webhooks`

```sql
CREATE TABLE {prefix}rp_webhooks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Konfiguration
    name VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    secret VARCHAR(255) NOT NULL,
    events JSON NOT NULL,                   -- ["application.received", "application.hired"]
    
    -- Status
    is_active TINYINT(1) DEFAULT 1,
    
    -- Tracking
    last_triggered_at DATETIME,
    last_success_at DATETIME,
    last_failure_at DATETIME,
    failure_count INT UNSIGNED DEFAULT 0,
    success_count INT UNSIGNED DEFAULT 0,
    
    -- Verknüpfung
    created_by BIGINT UNSIGNED NOT NULL,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    KEY is_active (is_active),
    KEY created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabelle: `{prefix}rp_webhook_deliveries`

Log aller Webhook-Versuche (für Debugging).

```sql
CREATE TABLE {prefix}rp_webhook_deliveries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    webhook_id BIGINT UNSIGNED NOT NULL,
    event VARCHAR(50) NOT NULL,
    
    -- Request
    request_url VARCHAR(500) NOT NULL,
    request_headers JSON,
    request_body JSON,
    
    -- Response
    response_code SMALLINT UNSIGNED,
    response_headers JSON,
    response_body TEXT,
    response_time_ms INT UNSIGNED,
    
    -- Status
    status VARCHAR(20) NOT NULL,            -- pending, success, failed
    error_message TEXT,
    retry_count TINYINT UNSIGNED DEFAULT 0,
    next_retry_at DATETIME,
    
    -- Timestamp
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    KEY webhook_id (webhook_id),
    KEY status (status),
    KEY created_at (created_at),
    
    CONSTRAINT fk_delivery_webhook 
        FOREIGN KEY (webhook_id) REFERENCES {prefix}rp_webhooks(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 8. E-Mail-Templates – wp_options + Custom Table

### Einstellungen in wp_options

```php
// Option: rp_email_templates
[
    'application_received' => [
        'de_DE' => [
            'subject' => 'Ihre Bewerbung bei {company_name}',
            'body' => '...',
            'enabled' => true
        ],
        'en_US' => [
            'subject' => 'Your application at {company_name}',
            'body' => '...',
            'enabled' => true
        ]
    ],
    'application_rejected' => [...],
    'interview_invitation' => [...]
]
```

### E-Mail-Log (optional, Pro)

```sql
CREATE TABLE {prefix}rp_email_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Verknüpfung
    application_id BIGINT UNSIGNED,
    candidate_id BIGINT UNSIGNED,
    
    -- E-Mail
    template VARCHAR(50),
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body LONGTEXT,
    
    -- Status
    status VARCHAR(20) NOT NULL,            -- sent, failed, bounced
    error_message TEXT,
    
    -- Tracking
    opened_at DATETIME,
    clicked_at DATETIME,
    
    -- Metadaten
    sent_by BIGINT UNSIGNED,                -- wp_users.ID
    
    -- Timestamp
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    KEY application_id (application_id),
    KEY candidate_id (candidate_id),
    KEY status (status),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 9. Einstellungen – wp_options

```php
// Alle Plugin-Einstellungen unter einem Key
// Option: rp_settings
[
    'general' => [
        'company_name' => 'Muster GmbH',
        'company_logo' => 123,  // Attachment ID
        'default_currency' => 'EUR',
        'date_format' => 'd.m.Y',
    ],
    'jobs' => [
        'slug' => 'jobs',
        'archive_title' => 'Stellenangebote',
        'default_application_deadline_days' => 30,
    ],
    'applications' => [
        'auto_delete_rejected_days' => 180,
        'auto_delete_hired_days' => 0,  // 0 = nie
        'talent_pool_duration_months' => 24,
    ],
    'forms' => [
        'required_fields' => ['first_name', 'last_name', 'email', 'cv'],
        'optional_fields' => ['phone', 'cover_letter', 'salary_expectation'],
        'custom_fields' => [...],
        'max_file_size_mb' => 10,
        'allowed_file_types' => ['pdf', 'doc', 'docx'],
    ],
    'notifications' => [
        'admin_email' => 'hr@example.com',
        'notify_on_application' => true,
        'notify_on_status_change' => false,
    ],
    'api' => [
        'enabled' => true,
        'rate_limit_free' => 100,
        'rate_limit_pro' => 1000,
    ],
    'privacy' => [
        'privacy_policy_url' => '/datenschutz/',
        'consent_text' => '...',
        'consent_version' => '2025-01',
    ]
]
```

---

## 10. Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐
│    wp_posts     │       │   wp_postmeta   │
│  (job_listing)  │───────│                 │
│                 │       │                 │
│  ID             │       │  post_id        │
│  post_title     │       │  meta_key       │
│  post_content   │       │  meta_value     │
│  post_status    │       │                 │
└────────┬────────┘       └─────────────────┘
         │
         │ 1:n
         ▼
┌─────────────────┐       ┌─────────────────┐
│ rp_applications │───────│  rp_candidates  │
│                 │  n:1  │                 │
│  id             │       │  id             │
│  job_id ────────┘       │  email          │
│  candidate_id ──────────│  first_name     │
│  status                 │  last_name      │
│  cover_letter           │  phone          │
│  rating                 │  talent_pool    │
│  consent_*              │                 │
└────────┬────────┘       └────────┬────────┘
         │                         │
         │ 1:n                     │
         ▼                         │
┌─────────────────┐                │
│  rp_documents   │────────────────┘
│                 │      n:1
│  id             │
│  application_id │
│  candidate_id   │
│  type           │
│  stored_filename│
│  mime_type      │
└─────────────────┘

┌─────────────────┐       ┌─────────────────┐
│rp_activity_log  │       │   rp_api_keys   │
│                 │       │                 │
│  id             │       │  id             │
│  object_type    │       │  name           │
│  object_id      │       │  key_hash       │
│  action         │       │  permissions    │
│  user_id        │       │  rate_limit     │
│  old_value      │       │                 │
│  new_value      │       │                 │
└─────────────────┘       └─────────────────┘

┌─────────────────┐       ┌─────────────────────┐
│   rp_webhooks   │───────│rp_webhook_deliveries│
│                 │  1:n  │                     │
│  id             │       │  id                 │
│  name           │       │  webhook_id         │
│  url            │       │  event              │
│  events         │       │  response_code      │
│  secret         │       │  status             │
└─────────────────┘       └─────────────────────┘
```

---

## 11. Indizes & Performance

### Wichtige Abfragen und ihre Indizes

| Abfrage | Index |
|---------|-------|
| Alle Bewerbungen zu einer Stelle | `job_id` |
| Bewerbungen nach Status filtern | `job_status (job_id, status)` |
| Kandidat per E-Mail finden | `email_hash` (UNIQUE) |
| Wiederbewerber erkennen | `candidate_id` in applications |
| Talent-Pool abrufen | `talent_pool` in candidates |
| Activity Log pro Objekt | `object (object_type, object_id)` |
| Dokumente einer Bewerbung | `application_id` |

### Empfehlungen

- JSON-Felder nicht für häufige Suchen verwenden
- `deleted_at` Index nur wenn Soft-Delete häufig gefiltert wird
- Bei > 100.000 Bewerbungen: Partitionierung nach Jahr erwägen

---

## 12. Backup-Hinweise & Datenintegrität

### Problem: Custom Tables und Standard-Backups

> **Wichtig:** Viele WordPress-Backup-Plugins sichern nur Standard-Tabellen!

**Betroffene Tabellen:**
- `rp_applications`
- `rp_candidates`
- `rp_documents`
- `rp_activity_log`
- `rp_api_keys`
- `rp_webhooks`
- `rp_webhook_deliveries`
- `rp_email_log`

**Potenzielle Probleme:**

| Szenario | Risiko |
|----------|--------|
| **Partial Backup** | Plugin sichert nur `wp_posts` (Jobs) aber nicht Custom Tables (Bewerbungen) |
| **Partial Restore** | Jobs werden wiederhergestellt, Bewerbungen fehlen → Foreign Key Fehler |
| **Migration** | Umzug mit Standard-Tools verliert Custom Tables |
| **Plugin-Updates** | Backup vor Update enthält nicht alle Daten |

### Kompatibilität mit Backup-Plugins

| Plugin | Custom Tables | Empfehlung |
|--------|:-------------:|------------|
| **UpdraftPlus** | ✅ (wenn konfiguriert) | "Nicht-WP-Tabellen" aktivieren |
| **BackWPup** | ✅ | Automatisch alle Tabellen |
| **Duplicator** | ✅ | Vollständige DB-Kopie |
| **All-in-One WP Migration** | ✅ | Vollständige DB |
| **WP Migrate DB** | ⚠️ | Manuell Tabellen auswählen |
| **Jetpack Backup** | ⚠️ | Nur Standard-Tabellen |
| **ManageWP** | ⚠️ | Prüfung erforderlich |

### Eigene Export/Import-Funktion

```php
<?php
// src/Admin/BackupExporter.php

namespace RecruitingPlaybook\Admin;

class BackupExporter {

    private const TABLES = [
        'rp_candidates',
        'rp_applications',
        'rp_documents',
        'rp_activity_log',
        'rp_api_keys',
        'rp_webhooks',
        'rp_webhook_deliveries',
        'rp_email_log',
    ];

    /**
     * Vollständigen Export erstellen
     */
    public function export(): string {
        global $wpdb;

        $export = [
            'plugin_version' => RP_VERSION,
            'export_date'    => current_time( 'c' ),
            'site_url'       => site_url(),
            'tables'         => [],
            'jobs'           => [],
            'settings'       => get_option( 'rp_settings', [] ),
        ];

        // Custom Tables exportieren
        foreach ( self::TABLES as $table ) {
            $full_table = $wpdb->prefix . $table;

            if ( $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) === $full_table ) {
                $export['tables'][ $table ] = $wpdb->get_results(
                    "SELECT * FROM $full_table",
                    ARRAY_A
                );
            }
        }

        // Jobs (Post Type) exportieren
        $jobs = get_posts( [
            'post_type'   => 'job_listing',
            'post_status' => 'any',
            'numberposts' => -1,
        ] );

        foreach ( $jobs as $job ) {
            $export['jobs'][] = [
                'post'     => (array) $job,
                'meta'     => get_post_meta( $job->ID ),
                'terms'    => wp_get_object_terms( $job->ID, [ 'job_category', 'job_location', 'employment_type' ] ),
            ];
        }

        // JSON erstellen
        $json = wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

        // Datei speichern
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/recruiting-playbook/backups/';

        if ( ! is_dir( $backup_dir ) ) {
            wp_mkdir_p( $backup_dir );
            file_put_contents( $backup_dir . '.htaccess', "Deny from all\n" );
        }

        $filename = 'rp-backup-' . date( 'Y-m-d-His' ) . '.json';
        $filepath = $backup_dir . $filename;

        file_put_contents( $filepath, $json );

        return $filepath;
    }

    /**
     * Import aus Backup
     */
    public function import( string $filepath ): array {
        global $wpdb;

        $json = file_get_contents( $filepath );
        $data = json_decode( $json, true );

        if ( ! $data ) {
            return [ 'success' => false, 'error' => 'Invalid JSON' ];
        }

        $stats = [
            'tables_imported' => 0,
            'jobs_imported'   => 0,
            'errors'          => [],
        ];

        // Tabellen importieren (mit Truncate!)
        foreach ( $data['tables'] as $table => $rows ) {
            $full_table = $wpdb->prefix . $table;

            // Tabelle leeren
            $wpdb->query( "TRUNCATE TABLE $full_table" );

            // Daten einfügen
            foreach ( $rows as $row ) {
                $wpdb->insert( $full_table, $row );
            }

            $stats['tables_imported']++;
        }

        // Settings importieren
        if ( ! empty( $data['settings'] ) ) {
            update_option( 'rp_settings', $data['settings'] );
        }

        return [
            'success' => true,
            'stats'   => $stats,
        ];
    }
}
```

### Admin-Hinweis bei Backup-Plugins

```php
<?php
// Warnung anzeigen wenn problematisches Backup-Plugin erkannt

add_action( 'admin_notices', function() {
    // Nur auf Plugin-Seiten
    if ( ! isset( $_GET['page'] ) || strpos( $_GET['page'], 'recruiting' ) === false ) {
        return;
    }

    $problematic_plugins = [
        'jetpack/jetpack.php' => 'Jetpack Backup',
    ];

    foreach ( $problematic_plugins as $plugin => $name ) {
        if ( is_plugin_active( $plugin ) ) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e( 'Backup-Hinweis:', 'recruiting-playbook' ); ?></strong>
                    <?php printf(
                        esc_html__( '%s sichert möglicherweise nicht alle Plugin-Daten (Custom Tables). Nutzen Sie die integrierte Export-Funktion für vollständige Backups.', 'recruiting-playbook' ),
                        esc_html( $name )
                    ); ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-tools&tab=backup' ) ); ?>">
                        <?php esc_html_e( 'Zum Backup-Tool', 'recruiting-playbook' ); ?>
                    </a>
                </p>
            </div>
            <?php
            break;
        }
    }
} );
```

### Foreign Key Constraints bei Restore

**Problem:** Bei einem Partial Restore können Foreign Key Constraints verletzt werden.

**Beispiel:**
```sql
-- Application verweist auf Candidate ID 123
-- Aber Candidate 123 existiert nicht mehr nach Restore
-- → Error: Cannot add or update a child row: a foreign key constraint fails
```

**Lösung: Temporäres Deaktivieren der FK-Checks beim Import**

```php
<?php
// Vor Import
$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );

// Import durchführen
// ...

// Nach Import
$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );

// Integritätsprüfung
$orphaned = $wpdb->get_results(
    "SELECT a.id FROM {$wpdb->prefix}rp_applications a
     LEFT JOIN {$wpdb->prefix}rp_candidates c ON a.candidate_id = c.id
     WHERE c.id IS NULL"
);

if ( ! empty( $orphaned ) ) {
    // Verwaiste Bewerbungen melden oder löschen
}
```

### Empfehlung für Nutzer

```
┌─────────────────────────────────────────────────────────────┐
│                      BACKUP-EMPFEHLUNG                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Für vollständige Backups aller Recruiting-Daten:           │
│                                                              │
│  1. Nutzen Sie die integrierte Export-Funktion unter        │
│     Recruiting → Werkzeuge → Backup                         │
│                                                              │
│  2. ODER stellen Sie sicher, dass Ihr Backup-Plugin         │
│     alle Datenbank-Tabellen sichert:                        │
│     • rp_candidates                                          │
│     • rp_applications                                        │
│     • rp_documents                                           │
│     • rp_activity_log                                        │
│     • ... (alle rp_* Tabellen)                              │
│                                                              │
│  3. Vergessen Sie nicht das Dokumenten-Verzeichnis:         │
│     /wp-content/uploads/recruiting-playbook/                 │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 13. Datenbank-Integritätsprüfung

### Problem: Dateninkonsistenz nach Migrationen

Nach Domain-Umzügen oder fehlerhaften Backups können Inkonsistenzen auftreten:
- Jobs vorhanden, aber Bewerbungen fehlen
- Bewerbungen verweisen auf gelöschte Jobs
- Dokumente in DB, aber Dateien fehlen
- Kandidaten ohne zugehörige Bewerbungen

### Lösung: Automatischer Integritäts-Check

```php
<?php
// src/Admin/IntegrityChecker.php

namespace RecruitingPlaybook\Admin;

class IntegrityChecker {

    /**
     * Vollständige Integritätsprüfung durchführen
     */
    public function check(): IntegrityReport {
        $report = new IntegrityReport();

        // 1. Tabellen-Existenz prüfen
        $report->add_check( $this->check_tables_exist() );

        // 2. Foreign Key Konsistenz prüfen
        $report->add_check( $this->check_orphaned_applications() );
        $report->add_check( $this->check_orphaned_documents() );
        $report->add_check( $this->check_orphaned_candidates() );

        // 3. Dateisystem-Konsistenz prüfen
        $report->add_check( $this->check_document_files() );
        $report->add_check( $this->check_upload_directory() );

        // 4. Daten-Plausibilität
        $report->add_check( $this->check_data_plausibility() );

        return $report;
    }

    /**
     * Prüft ob alle benötigten Tabellen existieren
     */
    private function check_tables_exist(): CheckResult {
        global $wpdb;

        $required_tables = [
            'rp_candidates',
            'rp_applications',
            'rp_documents',
            'rp_activity_log',
            'rp_api_keys',
            'rp_webhooks',
            'rp_webhook_deliveries',
            'rp_email_log',
        ];

        $missing = [];
        foreach ( $required_tables as $table ) {
            $full_table = $wpdb->prefix . $table;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) !== $full_table ) {
                $missing[] = $table;
            }
        }

        if ( empty( $missing ) ) {
            return new CheckResult(
                'tables_exist',
                'success',
                sprintf( __( 'Alle %d Tabellen vorhanden', 'recruiting-playbook' ), count( $required_tables ) )
            );
        }

        return new CheckResult(
            'tables_exist',
            'error',
            sprintf( __( '%d Tabellen fehlen: %s', 'recruiting-playbook' ), count( $missing ), implode( ', ', $missing ) ),
            [ 'action' => 'recreate_tables', 'tables' => $missing ]
        );
    }

    /**
     * Prüft auf verwaiste Bewerbungen (Job gelöscht)
     */
    private function check_orphaned_applications(): CheckResult {
        global $wpdb;

        $orphaned = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_applications a
             LEFT JOIN {$wpdb->posts} p ON a.job_id = p.ID AND p.post_type = 'job_listing'
             WHERE p.ID IS NULL AND a.deleted_at IS NULL"
        );

        if ( $orphaned == 0 ) {
            return new CheckResult(
                'orphaned_applications',
                'success',
                __( 'Keine verwaisten Bewerbungen', 'recruiting-playbook' )
            );
        }

        return new CheckResult(
            'orphaned_applications',
            'warning',
            sprintf( __( '%d Bewerbungen verweisen auf gelöschte Jobs', 'recruiting-playbook' ), $orphaned ),
            [ 'action' => 'cleanup_orphaned', 'count' => $orphaned ]
        );
    }

    /**
     * Prüft ob Dokument-Dateien existieren
     */
    private function check_document_files(): CheckResult {
        global $wpdb;

        $documents = $wpdb->get_results(
            "SELECT id, stored_filename, stored_path FROM {$wpdb->prefix}rp_documents WHERE deleted_at IS NULL"
        );

        $missing = [];
        $upload_dir = wp_upload_dir();

        foreach ( $documents as $doc ) {
            $file_path = $upload_dir['basedir'] . '/' . $doc->stored_path;

            if ( ! file_exists( $file_path ) ) {
                $missing[] = $doc->id;
            }
        }

        if ( empty( $missing ) ) {
            return new CheckResult(
                'document_files',
                'success',
                sprintf( __( 'Alle %d Dokumentdateien vorhanden', 'recruiting-playbook' ), count( $documents ) )
            );
        }

        return new CheckResult(
            'document_files',
            'warning',
            sprintf( __( '%d Dokumente in DB, aber Dateien fehlen', 'recruiting-playbook' ), count( $missing ) ),
            [ 'action' => 'mark_missing_documents', 'ids' => $missing ]
        );
    }

    /**
     * Daten-Plausibilität prüfen
     */
    private function check_data_plausibility(): CheckResult {
        global $wpdb;

        $issues = [];

        // Jobs ohne Bewerbungen nach > 90 Tagen (ungewöhnlich)
        // Bewerbungen in der Zukunft erstellt
        // Kandidaten mit ungültigen E-Mails

        $future_apps = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rp_applications
             WHERE created_at > NOW()"
        );

        if ( $future_apps > 0 ) {
            $issues[] = sprintf( __( '%d Bewerbungen mit Datum in der Zukunft', 'recruiting-playbook' ), $future_apps );
        }

        if ( empty( $issues ) ) {
            return new CheckResult(
                'data_plausibility',
                'success',
                __( 'Daten-Plausibilität OK', 'recruiting-playbook' )
            );
        }

        return new CheckResult(
            'data_plausibility',
            'warning',
            implode( ', ', $issues ),
            [ 'action' => 'review_data' ]
        );
    }
}
```

### Admin-Dashboard Widget

```php
<?php
// Automatischer Check beim Laden der Plugin-Übersichtsseite

add_action( 'admin_init', function() {
    // Nur auf Recruiting-Seiten
    if ( ! isset( $_GET['page'] ) || strpos( $_GET['page'], 'recruiting' ) === false ) {
        return;
    }

    // Maximal 1x pro Tag prüfen
    $last_check = get_transient( 'rp_integrity_last_check' );
    if ( $last_check && ( time() - $last_check ) < DAY_IN_SECONDS ) {
        return;
    }

    $checker = new IntegrityChecker();
    $report = $checker->check();

    // Ergebnis cachen
    set_transient( 'rp_integrity_report', $report, DAY_IN_SECONDS );
    set_transient( 'rp_integrity_last_check', time(), DAY_IN_SECONDS );
} );
```

### Status-Widget im Dashboard

```
┌─────────────────────────────────────────────────────────────┐
│  SYSTEMSTATUS                              Letzte Prüfung:  │
│                                            vor 2 Stunden    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ✅ Datenbank-Tabellen: 8/8 vorhanden                      │
│  ✅ Dokumenten-Verzeichnis: beschreibbar                   │
│  ✅ Konsistenz: 47 Jobs ↔ 234 Bewerbungen                  │
│  ⚠️ Letztes Backup: vor 14 Tagen                          │
│                                                             │
│  [Backup erstellen]  [Erneut prüfen]                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘

BEI PROBLEMEN:
┌─────────────────────────────────────────────────────────────┐
│  SYSTEMSTATUS                              ⚠️ PROBLEME      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ✅ Datenbank-Tabellen: 8/8 vorhanden                      │
│  ❌ 3 Bewerbungen verweisen auf gelöschte Jobs             │
│  ❌ 7 Dokumente fehlen im Dateisystem                      │
│  ⚠️ Letztes Backup: KEIN BACKUP GEFUNDEN                  │
│                                                             │
│  EMPFEHLUNG:                                                │
│  Es wurden Dateninkonsistenzen gefunden. Dies kann nach    │
│  einem Domain-Umzug oder unvollständigen Backup auftreten. │
│                                                             │
│  [Probleme bereinigen]  [Backup erstellen]  [Ignorieren]   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Automatische Bereinigung (optional)

```php
<?php
// src/Admin/IntegrityFixer.php

namespace RecruitingPlaybook\Admin;

class IntegrityFixer {

    /**
     * Verwaiste Bewerbungen soft-deleten
     */
    public function fix_orphaned_applications(): int {
        global $wpdb;

        $result = $wpdb->query(
            "UPDATE {$wpdb->prefix}rp_applications a
             LEFT JOIN {$wpdb->posts} p ON a.job_id = p.ID
             SET a.deleted_at = NOW()
             WHERE p.ID IS NULL AND a.deleted_at IS NULL"
        );

        // Activity Log
        if ( $result > 0 ) {
            do_action( 'rp_integrity_fixed', 'orphaned_applications', $result );
        }

        return $result;
    }

    /**
     * Fehlende Dokumente markieren
     */
    public function mark_missing_documents(): int {
        // ... Implementation
    }
}
```

### Migration-Hinweis

```php
<?php
// Bei Major-Updates oder erkanntem Domain-Wechsel

add_action( 'admin_notices', function() {
    // Domain-Wechsel erkennen
    $stored_domain = get_option( 'rp_installed_domain' );
    $current_domain = parse_url( site_url(), PHP_URL_HOST );

    if ( $stored_domain && $stored_domain !== $current_domain ) {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e( 'Recruiting Playbook: Domain-Wechsel erkannt', 'recruiting-playbook' ); ?></strong><br>
                <?php printf(
                    esc_html__( 'Diese Website wurde von %s nach %s umgezogen.', 'recruiting-playbook' ),
                    '<code>' . esc_html( $stored_domain ) . '</code>',
                    '<code>' . esc_html( $current_domain ) . '</code>'
                ); ?>
            </p>
            <p>
                <?php esc_html_e( 'Bitte führen Sie eine Integritätsprüfung durch, um sicherzustellen, dass alle Daten korrekt übertragen wurden.', 'recruiting-playbook' ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-tools&tab=integrity' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Integritätsprüfung starten', 'recruiting-playbook' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-tools&action=dismiss_domain_notice' ) ); ?>" class="button">
                    <?php esc_html_e( 'Verstanden, nicht mehr anzeigen', 'recruiting-playbook' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
} );
```

---

## 14. Migration & Installation

### Installation

```php
// Bei Plugin-Aktivierung
register_activation_hook(__FILE__, 'rp_activate');

function rp_activate() {
    rp_create_tables();
    rp_set_default_options();
    rp_create_upload_directory();
    
    // Version speichern für spätere Migrationen
    update_option('rp_db_version', '1.0.0');
}
```

### Upgrade-Handling

```php
// Bei jedem Laden prüfen
function rp_check_db_version() {
    $current_version = get_option('rp_db_version', '0.0.0');
    $plugin_version = RP_VERSION;
    
    if (version_compare($current_version, $plugin_version, '<')) {
        rp_run_migrations($current_version, $plugin_version);
        update_option('rp_db_version', $plugin_version);
    }
}
add_action('plugins_loaded', 'rp_check_db_version');
```

### Deinstallation

```php
// Nur wenn "Daten löschen" aktiviert
register_uninstall_hook(__FILE__, 'rp_uninstall');

function rp_uninstall() {
    if (get_option('rp_delete_data_on_uninstall', false)) {
        rp_drop_tables();
        rp_delete_options();
        rp_delete_upload_directory();
        rp_delete_posts();  // job_listings
    }
}
```

---

## 13. DSGVO-Implementierung

### Löschung eines Kandidaten

```php
function rp_delete_candidate_gdpr($candidate_id, $reason = '') {
    global $wpdb;
    
    // 1. Alle Dokumente physisch löschen
    $documents = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}rp_documents WHERE candidate_id = %d",
        $candidate_id
    ));
    
    foreach ($documents as $doc) {
        unlink(rp_get_document_path($doc->stored_filename));
    }
    
    // 2. Soft-Delete: Daten anonymisieren
    $wpdb->update(
        "{$wpdb->prefix}rp_candidates",
        [
            'email' => 'deleted_' . $candidate_id . '@anonymized.local',
            'email_hash' => hash('sha256', 'deleted_' . $candidate_id),
            'first_name' => 'Gelöscht',
            'last_name' => 'Gelöscht',
            'phone' => null,
            'address_street' => null,
            // ... alle personenbezogenen Felder
            'deleted_at' => current_time('mysql')
        ],
        ['id' => $candidate_id]
    );
    
    // 3. Activity Log (ohne personenbezogene Daten)
    rp_log_activity('candidate', $candidate_id, 'deleted_gdpr', [
        'reason' => $reason,
        'deleted_by' => get_current_user_id()
    ]);
}
```

### Datenexport (Auskunftsrecht)

```php
function rp_export_candidate_data($candidate_id) {
    global $wpdb;
    
    $data = [
        'candidate' => $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_candidates WHERE id = %d",
            $candidate_id
        ), ARRAY_A),
        
        'applications' => $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_applications WHERE candidate_id = %d",
            $candidate_id
        ), ARRAY_A),
        
        'documents' => $wpdb->get_results($wpdb->prepare(
            "SELECT id, type, original_filename, created_at 
             FROM {$wpdb->prefix}rp_documents WHERE candidate_id = %d",
            $candidate_id
        ), ARRAY_A),
        
        'activity' => $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rp_activity_log 
             WHERE object_type = 'candidate' AND object_id = %d",
            $candidate_id
        ), ARRAY_A),
        
        'exported_at' => current_time('c')
    ];
    
    return $data;
}
```

---

*Letzte Aktualisierung: Januar 2025*

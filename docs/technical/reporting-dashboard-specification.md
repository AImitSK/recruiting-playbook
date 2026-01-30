# Reporting & Dashboard: Technische Spezifikation

> **Pro-Feature: Reporting & Analytics**
> Dashboard-Widgets, Statistiken, Time-to-Hire und CSV-Export für datengetriebenes Recruiting

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Datenmodell](#3-datenmodell)
4. [REST API Endpunkte](#4-rest-api-endpunkte)
5. [Dashboard-Widgets](#5-dashboard-widgets)
6. [Statistik-Berechnungen](#6-statistik-berechnungen)
7. [CSV-Export](#7-csv-export)
8. [Systemstatus-Widget](#8-systemstatus-widget)
9. [Frontend-Komponenten](#9-frontend-komponenten)
10. [Berechtigungen](#10-berechtigungen)
11. [Testing](#11-testing)
12. [Implementierungsplan](#12-implementierungsplan)

---

## 1. Übersicht

### Zielsetzung

Das Reporting & Dashboard ermöglicht Recruitern und HR-Managern:
- **Dashboard-Widgets** für schnellen Überblick über aktuelle Zahlen
- **Bewerbungen pro Stelle** analysieren
- **Bewerbungen pro Zeitraum** mit Trend-Erkennung
- **Time-to-Hire** messen (Durchschnittsdauer bis Einstellung)
- **Conversion-Rate** berechnen (Besucher → Bewerbung)
- **CSV-Export** für externe Auswertungen
- **Systemstatus** überwachen (Integritäts-Check)

### Feature-Gating

```php
// Dashboard-Widgets sind in Free verfügbar (Basis-Statistiken)
// Erweiterte Statistiken und Export sind Pro-Features

if ( ! rp_can( 'advanced_reporting' ) ) {
    rp_require_feature( 'advanced_reporting', 'Erweiterte Statistiken', 'PRO' );
}

if ( ! rp_can( 'csv_export' ) ) {
    rp_require_feature( 'csv_export', 'CSV-Export', 'PRO' );
}

// Systemstatus ist für alle Admins verfügbar
```

### Feature-Matrix

| Feature | Free | Pro |
|---------|------|-----|
| Dashboard-Widget (Basis) | ✅ | ✅ |
| Bewerbungen heute/Woche | ✅ | ✅ |
| Offene Stellen Anzahl | ✅ | ✅ |
| Bewerbungen pro Stelle | ❌ | ✅ |
| Time-to-Hire | ❌ | ✅ |
| Conversion-Rate | ❌ | ✅ |
| Trend-Diagramme | ❌ | ✅ |
| CSV-Export | ❌ | ✅ |
| Systemstatus-Widget | ✅ | ✅ |

### User Stories

| Als | möchte ich | damit |
|-----|-----------|-------|
| HR-Manager | auf einen Blick aktuelle Zahlen sehen | ich den Status meines Recruitings kenne |
| Recruiter | wissen wie viele Bewerbungen pro Stelle eingehen | ich Stellenanzeigen optimieren kann |
| HR-Manager | die Time-to-Hire kennen | ich Prozesse verbessern kann |
| Marketing | die Conversion-Rate sehen | ich Kampagnen-Erfolg messen kann |
| Recruiter | Daten als CSV exportieren | ich sie in Excel auswerten kann |
| Admin | den Systemstatus prüfen | ich Probleme früh erkenne |

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   ├── Admin/
│   │   ├── Dashboard/
│   │   │   ├── DashboardManager.php      # Widget-Registrierung
│   │   │   ├── StatsWidget.php           # Basis-Statistiken Widget
│   │   │   ├── SystemStatusWidget.php    # Systemstatus Widget
│   │   │   └── ReportingWidget.php       # Erweitertes Reporting Widget
│   │   │
│   │   └── Pages/
│   │       └── ReportingPage.php         # Vollständige Reporting-Seite
│   │
│   ├── Api/
│   │   ├── StatsController.php           # REST API für Statistiken
│   │   └── ExportController.php          # REST API für CSV-Export
│   │
│   ├── Services/
│   │   ├── StatsService.php              # Statistik-Berechnungen
│   │   ├── TimeToHireService.php         # Time-to-Hire Logik
│   │   ├── ConversionService.php         # Conversion-Rate Logik
│   │   ├── ExportService.php             # CSV-Export Service
│   │   └── SystemStatusService.php       # Integritäts-Checks
│   │
│   └── Repositories/
│       └── StatsRepository.php           # Optimierte Statistik-Queries
│
├── assets/
│   └── src/
│       ├── js/
│       │   └── admin/
│       │       └── reporting/
│       │           ├── index.jsx              # Entry Point
│       │           ├── ReportingDashboard.jsx # Hauptkomponente
│       │           ├── StatsCard.jsx          # Statistik-Karte
│       │           ├── TrendChart.jsx         # Trend-Diagramm
│       │           ├── ApplicationsChart.jsx  # Bewerbungen-Chart
│       │           ├── TimeToHireCard.jsx     # Time-to-Hire Anzeige
│       │           ├── ConversionFunnel.jsx   # Conversion-Trichter
│       │           ├── JobStatsTable.jsx      # Statistiken pro Stelle
│       │           ├── ExportButton.jsx       # CSV-Export Button
│       │           ├── SystemStatus.jsx       # Systemstatus-Anzeige
│       │           ├── DateRangePicker.jsx    # Zeitraum-Auswahl
│       │           └── hooks/
│       │               ├── useStats.js
│       │               ├── useTimeToHire.js
│       │               ├── useConversion.js
│       │               └── useExport.js
│       │
│       └── css/
│           └── admin-reporting.css            # Reporting-Styles
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Frontend | React 18 (@wordpress/element) |
| Charts | Recharts (leichtgewichtig, React-native) |
| State Management | React Context + Custom Hooks |
| API-Kommunikation | @wordpress/api-fetch |
| Datum/Zeit | date-fns (Tree-shakable) |
| CSV-Generierung | Server-side PHP (Streaming) |
| Styling | Tailwind CSS (rp- Prefix) |

### Datenfluss

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  React Frontend │────▶│   REST API       │────▶│  StatsService   │
│  (Dashboard)    │◀────│   Controller     │◀────│  (Caching)      │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                                          │
                                                          ▼
                                                 ┌─────────────────┐
                                                 │ StatsRepository │
                                                 │ (Optimized SQL) │
                                                 └─────────────────┘
                                                          │
                                                          ▼
                                                 ┌─────────────────┐
                                                 │    Database     │
                                                 │ (rp_* Tables)   │
                                                 └─────────────────┘
```

---

## 3. Datenmodell

### Keine neuen Tabellen erforderlich

Das Reporting nutzt die bestehenden Tabellen:
- `rp_applications` - Bewerbungsdaten
- `rp_candidates` - Kandidatendaten
- `rp_activity_log` - Für Timeline-basierte Auswertungen
- `wp_posts` (job_listing) - Stellenanzeigen

### Neue Tabelle: `rp_stats_cache` (Optional für Performance)

```sql
CREATE TABLE {$prefix}rp_stats_cache (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    cache_key       varchar(100) NOT NULL,
    cache_value     longtext NOT NULL,
    expires_at      datetime NOT NULL,
    created_at      datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY cache_key (cache_key),
    KEY expires_at (expires_at)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `cache_key` | varchar | Eindeutiger Cache-Schlüssel |
| `cache_value` | longtext | Serialisierte Statistik-Daten (JSON) |
| `expires_at` | datetime | Ablaufzeitpunkt |
| `created_at` | datetime | Erstellungsdatum |

### Indexe für Performance

```sql
-- Optimierte Indexe für Statistik-Queries
ALTER TABLE {$prefix}rp_applications
ADD INDEX idx_stats_status_date (status, created_at),
ADD INDEX idx_stats_job_status (job_id, status),
ADD INDEX idx_stats_hired_date (status, updated_at);

-- Index für Conversion-Berechnung (falls Job-Views getrackt werden)
ALTER TABLE {$prefix}rp_activity_log
ADD INDEX idx_activity_type_date (activity_type, created_at);
```

---

## 4. REST API Endpunkte

### Stats API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/stats/overview` | Dashboard-Übersicht |
| GET | `/recruiting/v1/stats/applications` | Bewerbungs-Statistiken |
| GET | `/recruiting/v1/stats/jobs` | Statistiken pro Stelle |
| GET | `/recruiting/v1/stats/time-to-hire` | Time-to-Hire Metriken |
| GET | `/recruiting/v1/stats/conversion` | Conversion-Rate |
| GET | `/recruiting/v1/stats/trends` | Trend-Daten für Charts |
| GET | `/recruiting/v1/system/status` | Systemstatus |

### Export API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/export/applications` | Bewerbungen als CSV |
| GET | `/recruiting/v1/export/candidates` | Kandidaten als CSV |
| GET | `/recruiting/v1/export/stats` | Statistik-Report als CSV |

---

### GET /stats/overview

Dashboard-Übersicht mit allen wichtigen Kennzahlen.

```php
register_rest_route(
    $this->namespace,
    '/stats/overview',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_overview' ],
        'permission_callback' => [ $this, 'stats_permissions_check' ],
        'args'                => [
            'period' => [
                'description' => __( 'Zeitraum', 'recruiting-playbook' ),
                'type'        => 'string',
                'default'     => '30days',
                'enum'        => [ 'today', '7days', '30days', '90days', 'year', 'all' ],
            ],
        ],
    ]
);
```

#### Response Schema

```json
{
    "applications": {
        "total": 156,
        "new": 23,
        "in_progress": 45,
        "hired": 12,
        "rejected": 76,
        "period_change": 15.3
    },
    "jobs": {
        "total": 18,
        "active": 12,
        "draft": 4,
        "expired": 2
    },
    "quick_stats": {
        "today": 5,
        "this_week": 28,
        "this_month": 89
    },
    "time_to_hire": {
        "average_days": 23,
        "median_days": 18,
        "period_change": -2.5
    },
    "conversion_rate": {
        "rate": 4.2,
        "views": 3714,
        "applications": 156,
        "period_change": 0.8
    },
    "top_jobs": [
        {
            "id": 123,
            "title": "Senior Developer",
            "applications": 34
        },
        {
            "id": 124,
            "title": "Project Manager",
            "applications": 28
        }
    ],
    "period": "30days",
    "generated_at": "2025-01-29T10:30:00Z"
}
```

---

### GET /stats/applications

Detaillierte Bewerbungs-Statistiken.

```php
register_rest_route(
    $this->namespace,
    '/stats/applications',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_application_stats' ],
        'permission_callback' => [ $this, 'stats_permissions_check' ],
        'args'                => [
            'date_from' => [
                'description' => __( 'Startdatum (Y-m-d)', 'recruiting-playbook' ),
                'type'        => 'string',
                'format'      => 'date',
            ],
            'date_to' => [
                'description' => __( 'Enddatum (Y-m-d)', 'recruiting-playbook' ),
                'type'        => 'string',
                'format'      => 'date',
            ],
            'group_by' => [
                'description' => __( 'Gruppierung', 'recruiting-playbook' ),
                'type'        => 'string',
                'default'     => 'day',
                'enum'        => [ 'day', 'week', 'month' ],
            ],
            'job_id' => [
                'description' => __( 'Filter nach Stelle', 'recruiting-playbook' ),
                'type'        => 'integer',
            ],
            'status' => [
                'description' => __( 'Filter nach Status', 'recruiting-playbook' ),
                'type'        => 'array',
                'items'       => [ 'type' => 'string' ],
            ],
        ],
    ]
);
```

#### Response Schema

```json
{
    "summary": {
        "total": 156,
        "by_status": {
            "new": 23,
            "screening": 18,
            "interview": 15,
            "offer": 4,
            "hired": 12,
            "rejected": 76,
            "withdrawn": 8
        }
    },
    "timeline": [
        {
            "date": "2025-01-01",
            "total": 8,
            "new": 8,
            "hired": 0,
            "rejected": 0
        },
        {
            "date": "2025-01-02",
            "total": 12,
            "new": 12,
            "hired": 1,
            "rejected": 3
        }
    ],
    "by_source": {
        "direct": 89,
        "indeed": 34,
        "linkedin": 22,
        "stepstone": 11
    },
    "filters_applied": {
        "date_from": "2025-01-01",
        "date_to": "2025-01-31",
        "group_by": "day"
    }
}
```

---

### GET /stats/jobs

Statistiken pro Stelle.

```php
register_rest_route(
    $this->namespace,
    '/stats/jobs',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_job_stats' ],
        'permission_callback' => [ $this, 'stats_permissions_check' ],
        'args'                => [
            'date_from' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'date_to' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'sort_by' => [
                'type'    => 'string',
                'default' => 'applications',
                'enum'    => [ 'applications', 'views', 'conversion', 'time_to_hire', 'title' ],
            ],
            'sort_order' => [
                'type'    => 'string',
                'default' => 'desc',
                'enum'    => [ 'asc', 'desc' ],
            ],
            'per_page' => [
                'type'    => 'integer',
                'default' => 20,
                'maximum' => 100,
            ],
            'page' => [
                'type'    => 'integer',
                'default' => 1,
            ],
        ],
    ]
);
```

#### Response Schema

```json
{
    "jobs": [
        {
            "id": 123,
            "title": "Senior Developer (m/w/d)",
            "status": "publish",
            "location": "Berlin",
            "department": "Engineering",
            "stats": {
                "applications_total": 34,
                "applications_new": 5,
                "applications_in_progress": 8,
                "applications_hired": 2,
                "applications_rejected": 19,
                "views": 892,
                "conversion_rate": 3.81,
                "avg_time_to_hire": 21,
                "avg_rating": 3.8
            },
            "funnel": {
                "new": 34,
                "screening": 28,
                "interview": 12,
                "offer": 3,
                "hired": 2
            },
            "created_at": "2024-12-15T10:00:00Z"
        }
    ],
    "total": 18,
    "pages": 1,
    "aggregated": {
        "total_applications": 156,
        "total_views": 4521,
        "avg_conversion_rate": 3.45,
        "avg_time_to_hire": 23
    }
}
```

---

### GET /stats/time-to-hire

Time-to-Hire Metriken.

```php
register_rest_route(
    $this->namespace,
    '/stats/time-to-hire',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_time_to_hire' ],
        'permission_callback' => [ $this, 'advanced_stats_permissions_check' ],
        'args'                => [
            'date_from' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'date_to' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'job_id' => [
                'type' => 'integer',
            ],
            'department' => [
                'type' => 'string',
            ],
        ],
    ]
);
```

#### Response Schema

```json
{
    "overall": {
        "average_days": 23,
        "median_days": 18,
        "min_days": 5,
        "max_days": 67,
        "total_hires": 12
    },
    "by_stage": {
        "new_to_screening": {
            "average_days": 2,
            "median_days": 1
        },
        "screening_to_interview": {
            "average_days": 7,
            "median_days": 5
        },
        "interview_to_offer": {
            "average_days": 10,
            "median_days": 8
        },
        "offer_to_hired": {
            "average_days": 4,
            "median_days": 3
        }
    },
    "trend": [
        {
            "month": "2024-10",
            "average_days": 28,
            "hires": 3
        },
        {
            "month": "2024-11",
            "average_days": 25,
            "hires": 4
        },
        {
            "month": "2024-12",
            "average_days": 22,
            "hires": 3
        },
        {
            "month": "2025-01",
            "average_days": 21,
            "hires": 2
        }
    ],
    "by_job": [
        {
            "job_id": 123,
            "job_title": "Senior Developer",
            "average_days": 18,
            "hires": 2
        },
        {
            "job_id": 124,
            "job_title": "Project Manager",
            "average_days": 31,
            "hires": 1
        }
    ],
    "comparison": {
        "previous_period": {
            "average_days": 26,
            "change_percent": -11.5
        }
    }
}
```

---

### GET /stats/conversion

Conversion-Rate (Views → Bewerbungen).

```php
register_rest_route(
    $this->namespace,
    '/stats/conversion',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_conversion_stats' ],
        'permission_callback' => [ $this, 'advanced_stats_permissions_check' ],
        'args'                => [
            'date_from' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'date_to' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'job_id' => [
                'type' => 'integer',
            ],
        ],
    ]
);
```

#### Response Schema

```json
{
    "overall": {
        "views": 4521,
        "unique_visitors": 3892,
        "applications": 156,
        "conversion_rate": 3.45,
        "unique_conversion_rate": 4.01
    },
    "funnel": {
        "job_list_views": 12450,
        "job_detail_views": 4521,
        "form_starts": 312,
        "form_completions": 156,
        "rates": {
            "list_to_detail": 36.3,
            "detail_to_form_start": 6.9,
            "form_start_to_complete": 50.0,
            "overall": 1.25
        }
    },
    "by_source": [
        {
            "source": "organic",
            "views": 2100,
            "applications": 89,
            "conversion_rate": 4.24
        },
        {
            "source": "indeed",
            "views": 1200,
            "applications": 34,
            "conversion_rate": 2.83
        },
        {
            "source": "linkedin",
            "views": 800,
            "applications": 22,
            "conversion_rate": 2.75
        }
    ],
    "trend": [
        {
            "date": "2025-01-01",
            "views": 145,
            "applications": 5,
            "conversion_rate": 3.45
        }
    ],
    "top_converting_jobs": [
        {
            "job_id": 125,
            "title": "Pflegefachkraft",
            "views": 320,
            "applications": 28,
            "conversion_rate": 8.75
        }
    ],
    "comparison": {
        "previous_period": {
            "conversion_rate": 3.12,
            "change_percent": 10.6
        }
    }
}
```

---

### GET /stats/trends

Trend-Daten für Charts.

```php
register_rest_route(
    $this->namespace,
    '/stats/trends',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_trends' ],
        'permission_callback' => [ $this, 'stats_permissions_check' ],
        'args'                => [
            'metrics' => [
                'description' => __( 'Metriken für Trend', 'recruiting-playbook' ),
                'type'        => 'array',
                'items'       => [ 'type' => 'string' ],
                'default'     => [ 'applications', 'hires' ],
                'enum'        => [ 'applications', 'views', 'hires', 'rejections', 'time_to_hire', 'conversion' ],
            ],
            'date_from' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'date_to' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'granularity' => [
                'type'    => 'string',
                'default' => 'day',
                'enum'    => [ 'day', 'week', 'month' ],
            ],
        ],
    ]
);
```

#### Response Schema

```json
{
    "data": [
        {
            "date": "2025-01-01",
            "applications": 8,
            "hires": 0,
            "views": 145,
            "conversion": 5.52
        },
        {
            "date": "2025-01-02",
            "applications": 12,
            "hires": 1,
            "views": 178,
            "conversion": 6.74
        }
    ],
    "summary": {
        "applications": {
            "total": 156,
            "avg_per_day": 5.2,
            "trend": "up",
            "trend_percent": 15.3
        },
        "hires": {
            "total": 12,
            "avg_per_day": 0.4,
            "trend": "stable",
            "trend_percent": 2.1
        }
    },
    "granularity": "day",
    "date_from": "2025-01-01",
    "date_to": "2025-01-31"
}
```

---

### GET /system/status

Systemstatus und Integritäts-Check.

```php
register_rest_route(
    $this->namespace,
    '/system/status',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_system_status' ],
        'permission_callback' => [ $this, 'admin_permissions_check' ],
    ]
);
```

#### Response Schema

```json
{
    "status": "healthy",
    "checks": {
        "database": {
            "status": "ok",
            "message": "Alle Tabellen vorhanden",
            "details": {
                "tables_expected": 8,
                "tables_found": 8
            }
        },
        "uploads": {
            "status": "ok",
            "message": "Upload-Verzeichnis beschreibbar",
            "details": {
                "path": "/wp-content/uploads/recruiting-playbook/",
                "writable": true,
                "files_count": 234,
                "total_size": "45.6 MB"
            }
        },
        "cron": {
            "status": "ok",
            "message": "Cron-Jobs aktiv",
            "details": {
                "next_cleanup": "2025-01-30T03:00:00Z",
                "last_run": "2025-01-29T03:00:00Z"
            }
        },
        "orphaned_data": {
            "status": "warning",
            "message": "3 verwaiste Dokumente gefunden",
            "details": {
                "orphaned_documents": 3,
                "orphaned_applications": 0
            }
        },
        "license": {
            "status": "ok",
            "message": "Pro-Lizenz aktiv",
            "details": {
                "type": "pro",
                "expires": "2026-01-15",
                "domain": "example.com"
            }
        },
        "action_scheduler": {
            "status": "ok",
            "message": "Action Scheduler läuft",
            "details": {
                "pending": 12,
                "running": 0,
                "failed": 0
            }
        }
    },
    "recommendations": [
        {
            "type": "cleanup",
            "message": "3 verwaiste Dokumente können gelöscht werden",
            "action": "cleanup_orphaned_documents"
        }
    ],
    "plugin_version": "1.2.0",
    "php_version": "8.1.12",
    "wp_version": "6.4.2",
    "checked_at": "2025-01-29T10:30:00Z"
}
```

---

### GET /export/applications

CSV-Export der Bewerbungen.

```php
register_rest_route(
    $this->namespace,
    '/export/applications',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'export_applications' ],
        'permission_callback' => [ $this, 'export_permissions_check' ],
        'args'                => [
            'date_from' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'date_to' => [
                'type'   => 'string',
                'format' => 'date',
            ],
            'status' => [
                'type'  => 'array',
                'items' => [ 'type' => 'string' ],
            ],
            'job_id' => [
                'type' => 'integer',
            ],
            'columns' => [
                'type'    => 'array',
                'items'   => [ 'type' => 'string' ],
                'default' => [ 'id', 'candidate_name', 'email', 'job_title', 'status', 'created_at' ],
            ],
            'format' => [
                'type'    => 'string',
                'default' => 'csv',
                'enum'    => [ 'csv' ],
            ],
        ],
    ]
);
```

#### Verfügbare Spalten

| Spalte | Beschreibung |
|--------|--------------|
| `id` | Bewerbungs-ID |
| `candidate_name` | Name des Bewerbers |
| `email` | E-Mail-Adresse |
| `phone` | Telefonnummer |
| `job_id` | Stellen-ID |
| `job_title` | Stellentitel |
| `status` | Aktueller Status |
| `rating` | Durchschnittliche Bewertung |
| `source` | Bewerbungsquelle |
| `created_at` | Bewerbungsdatum |
| `updated_at` | Letzte Änderung |
| `hired_at` | Einstellungsdatum (falls hired) |
| `time_in_process` | Tage im Prozess |

---

## 5. Dashboard-Widgets

### WordPress Dashboard Integration

```php
namespace RecruitingPlaybook\Admin\Dashboard;

class DashboardManager {

    public function __construct() {
        add_action( 'wp_dashboard_setup', [ $this, 'register_widgets' ] );
    }

    public function register_widgets(): void {
        // Basis-Widget (Free + Pro)
        wp_add_dashboard_widget(
            'rp_stats_widget',
            __( 'Recruiting Playbook', 'recruiting-playbook' ),
            [ $this, 'render_stats_widget' ],
            null,
            null,
            'normal',
            'high'
        );

        // Systemstatus-Widget (nur für Admins)
        if ( current_user_can( 'manage_options' ) ) {
            wp_add_dashboard_widget(
                'rp_system_status_widget',
                __( 'Recruiting Playbook - Systemstatus', 'recruiting-playbook' ),
                [ $this, 'render_system_status_widget' ],
                null,
                null,
                'side',
                'high'
            );
        }
    }

    public function render_stats_widget(): void {
        // React-Container für dynamisches Widget
        echo '<div id="rp-dashboard-widget" class="rp-dashboard-widget"></div>';

        // Localized Data
        wp_localize_script( 'rp-dashboard-widget', 'rpDashboardData', [
            'apiUrl'     => rest_url( 'recruiting/v1/stats' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'isPro'      => rp_is_pro(),
            'canExport'  => rp_can( 'csv_export' ),
            'reportingUrl' => admin_url( 'admin.php?page=rp-reporting' ),
        ] );
    }
}
```

### Widget-Layouts

#### Free-Version Widget

```
┌─────────────────────────────────────────────────────────┐
│  📊 Recruiting Playbook                                 │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │     23      │  │     12      │  │      5      │     │
│  │ Bewerbungen │  │   Aktive    │  │   Heute     │     │
│  │  (gesamt)   │  │   Stellen   │  │    neu      │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│                                                         │
│  Status-Übersicht:                                      │
│  ████████████░░░░░░░░  Neu (8)                         │
│  ██████░░░░░░░░░░░░░░  In Prüfung (5)                  │
│  ████░░░░░░░░░░░░░░░░  Interview (3)                   │
│  ██░░░░░░░░░░░░░░░░░░  Eingestellt (2)                 │
│                                                         │
│  [🔒 Erweiterte Statistiken freischalten (Pro)]        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

#### Pro-Version Widget

```
┌─────────────────────────────────────────────────────────┐
│  📊 Recruiting Playbook              [📈 Alle Reports] │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │     156     │  │    23 ⏱️    │  │   4.2% 📈   │     │
│  │ Bewerbungen │  │ Ø Time-to-  │  │ Conversion  │     │
│  │  +15% ▲     │  │   Hire      │  │    Rate     │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│                                                         │
│  Bewerbungen (letzte 30 Tage)                          │
│  ┌─────────────────────────────────────────────────┐   │
│  │     ╭─╮                                         │   │
│  │   ╭─╯ ╰─╮    ╭─╮                               │   │
│  │ ╭─╯     ╰────╯ ╰─╮  ╭────╮                     │   │
│  │─╯                ╰──╯    ╰─────                │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  Top-Stellen:                                          │
│  1. Senior Developer (34 Bewerbungen)                  │
│  2. Project Manager (28 Bewerbungen)                   │
│  3. Pflegefachkraft (22 Bewerbungen)                   │
│                                                         │
│  [📥 CSV Export]  [📊 Vollständiger Report]            │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 6. Statistik-Berechnungen

### StatsService

```php
namespace RecruitingPlaybook\Services;

class StatsService {

    private StatsRepository $repository;
    private CacheService $cache;

    private const CACHE_TTL = 300; // 5 Minuten

    public function __construct( StatsRepository $repository, CacheService $cache ) {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * Dashboard-Übersicht
     */
    public function get_overview( string $period = '30days' ): array {
        $cache_key = "stats_overview_{$period}";

        return $this->cache->remember( $cache_key, self::CACHE_TTL, function() use ( $period ) {
            $date_range = $this->get_date_range( $period );

            return [
                'applications'    => $this->get_application_summary( $date_range ),
                'jobs'            => $this->get_job_summary(),
                'quick_stats'     => $this->get_quick_stats(),
                'time_to_hire'    => $this->get_time_to_hire_summary( $date_range ),
                'conversion_rate' => $this->get_conversion_summary( $date_range ),
                'top_jobs'        => $this->repository->get_top_jobs_by_applications( 5, $date_range ),
                'period'          => $period,
                'generated_at'    => current_time( 'c' ),
            ];
        } );
    }

    /**
     * Bewerbungs-Zusammenfassung
     */
    private function get_application_summary( array $date_range ): array {
        $current = $this->repository->count_applications_by_status( $date_range );
        $previous = $this->repository->count_applications_by_status(
            $this->get_previous_period( $date_range )
        );

        $total_current = array_sum( $current );
        $total_previous = array_sum( $previous );

        return [
            'total'         => $total_current,
            'new'           => $current['new'] ?? 0,
            'in_progress'   => ( $current['screening'] ?? 0 ) + ( $current['interview'] ?? 0 ) + ( $current['offer'] ?? 0 ),
            'hired'         => $current['hired'] ?? 0,
            'rejected'      => $current['rejected'] ?? 0,
            'period_change' => $this->calculate_percentage_change( $total_previous, $total_current ),
        ];
    }

    /**
     * Prozentuale Änderung berechnen
     */
    private function calculate_percentage_change( int $previous, int $current ): float {
        if ( $previous === 0 ) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round( ( ( $current - $previous ) / $previous ) * 100, 1 );
    }

    /**
     * Datumsbereich aus Period-String
     */
    private function get_date_range( string $period ): array {
        $now = current_time( 'timestamp' );

        switch ( $period ) {
            case 'today':
                return [
                    'from' => gmdate( 'Y-m-d 00:00:00', $now ),
                    'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
                ];
            case '7days':
                return [
                    'from' => gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days', $now ) ),
                    'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
                ];
            case '30days':
                return [
                    'from' => gmdate( 'Y-m-d 00:00:00', strtotime( '-30 days', $now ) ),
                    'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
                ];
            case '90days':
                return [
                    'from' => gmdate( 'Y-m-d 00:00:00', strtotime( '-90 days', $now ) ),
                    'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
                ];
            case 'year':
                return [
                    'from' => gmdate( 'Y-01-01 00:00:00', $now ),
                    'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
                ];
            default: // 'all'
                return [
                    'from' => '1970-01-01 00:00:00',
                    'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
                ];
        }
    }
}
```

### TimeToHireService

```php
namespace RecruitingPlaybook\Services;

class TimeToHireService {

    private StatsRepository $repository;

    /**
     * Time-to-Hire berechnen
     *
     * Misst die Zeit von Bewerbungseingang bis Einstellung.
     */
    public function calculate( array $date_range, ?int $job_id = null ): array {
        $hired_applications = $this->repository->get_hired_applications( $date_range, $job_id );

        if ( empty( $hired_applications ) ) {
            return $this->empty_result();
        }

        $days = [];
        $by_stage = [
            'new_to_screening'      => [],
            'screening_to_interview' => [],
            'interview_to_offer'    => [],
            'offer_to_hired'        => [],
        ];

        foreach ( $hired_applications as $app ) {
            // Gesamtzeit
            $total_days = $this->calculate_days_between( $app->created_at, $app->hired_at );
            $days[] = $total_days;

            // Zeit pro Stage (aus Activity Log)
            $stages = $this->get_stage_transitions( $app->id );
            foreach ( $stages as $stage => $stage_days ) {
                if ( isset( $by_stage[ $stage ] ) ) {
                    $by_stage[ $stage ][] = $stage_days;
                }
            }
        }

        return [
            'overall' => [
                'average_days' => round( array_sum( $days ) / count( $days ), 1 ),
                'median_days'  => $this->calculate_median( $days ),
                'min_days'     => min( $days ),
                'max_days'     => max( $days ),
                'total_hires'  => count( $days ),
            ],
            'by_stage' => $this->calculate_stage_averages( $by_stage ),
        ];
    }

    /**
     * Median berechnen
     */
    private function calculate_median( array $values ): int {
        sort( $values );
        $count = count( $values );
        $middle = floor( $count / 2 );

        if ( $count % 2 === 0 ) {
            return (int) ( ( $values[ $middle - 1 ] + $values[ $middle ] ) / 2 );
        }

        return (int) $values[ $middle ];
    }

    /**
     * Tage zwischen zwei Daten
     */
    private function calculate_days_between( string $from, string $to ): int {
        $from_date = new \DateTime( $from );
        $to_date = new \DateTime( $to );

        return (int) $from_date->diff( $to_date )->days;
    }
}
```

### ConversionService

```php
namespace RecruitingPlaybook\Services;

class ConversionService {

    private StatsRepository $repository;

    /**
     * Conversion-Rate berechnen
     *
     * Views werden aus dem Activity Log gezählt (job_viewed Events)
     * oder alternativ über ein externes Analytics-Tool.
     */
    public function calculate( array $date_range, ?int $job_id = null ): array {
        $views = $this->repository->count_job_views( $date_range, $job_id );
        $applications = $this->repository->count_applications( $date_range, $job_id );

        $conversion_rate = $views > 0
            ? round( ( $applications / $views ) * 100, 2 )
            : 0;

        return [
            'overall' => [
                'views'           => $views,
                'applications'    => $applications,
                'conversion_rate' => $conversion_rate,
            ],
            'funnel' => $this->calculate_funnel( $date_range, $job_id ),
            'by_source' => $this->get_conversion_by_source( $date_range, $job_id ),
        ];
    }

    /**
     * Conversion Funnel
     */
    private function calculate_funnel( array $date_range, ?int $job_id ): array {
        // Diese Metriken erfordern Tracking-Events
        $job_list_views = $this->repository->count_events( 'job_list_viewed', $date_range );
        $job_detail_views = $this->repository->count_job_views( $date_range, $job_id );
        $form_starts = $this->repository->count_events( 'application_form_started', $date_range, $job_id );
        $form_completions = $this->repository->count_applications( $date_range, $job_id );

        return [
            'job_list_views'    => $job_list_views,
            'job_detail_views'  => $job_detail_views,
            'form_starts'       => $form_starts,
            'form_completions'  => $form_completions,
            'rates' => [
                'list_to_detail'         => $this->safe_percentage( $job_list_views, $job_detail_views ),
                'detail_to_form_start'   => $this->safe_percentage( $job_detail_views, $form_starts ),
                'form_start_to_complete' => $this->safe_percentage( $form_starts, $form_completions ),
                'overall'                => $this->safe_percentage( $job_list_views, $form_completions ),
            ],
        ];
    }

    private function safe_percentage( int $total, int $part ): float {
        return $total > 0 ? round( ( $part / $total ) * 100, 2 ) : 0;
    }
}
```

---

## 7. CSV-Export

### ExportService

```php
namespace RecruitingPlaybook\Services;

class ExportService {

    private const BATCH_SIZE = 500;

    /**
     * Bewerbungen exportieren (Streaming)
     */
    public function export_applications( array $args ): void {
        // Pro-Feature Check
        if ( ! rp_can( 'csv_export' ) ) {
            throw new FeatureNotAvailableException( 'csv_export' );
        }

        $filename = sprintf(
            'bewerbungen_%s.csv',
            gmdate( 'Y-m-d_His' )
        );

        // Headers für CSV-Download
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // Output Stream
        $output = fopen( 'php://output', 'w' );

        // BOM für Excel UTF-8 Kompatibilität
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

        // Header-Zeile
        $columns = $this->get_export_columns( $args['columns'] ?? [] );
        fputcsv( $output, array_values( $columns ), ';' );

        // Daten in Batches exportieren
        $offset = 0;
        do {
            $applications = $this->repository->get_applications_for_export(
                $args,
                self::BATCH_SIZE,
                $offset
            );

            foreach ( $applications as $app ) {
                $row = $this->format_row( $app, array_keys( $columns ) );
                fputcsv( $output, $row, ';' );
            }

            $offset += self::BATCH_SIZE;

            // Memory freigeben
            if ( function_exists( 'wp_cache_flush' ) ) {
                wp_cache_flush();
            }

        } while ( count( $applications ) === self::BATCH_SIZE );

        fclose( $output );
        exit;
    }

    /**
     * Export-Spalten mit deutschen Labels
     */
    private function get_export_columns( array $requested ): array {
        $available = [
            'id'              => 'ID',
            'candidate_name'  => 'Name',
            'email'           => 'E-Mail',
            'phone'           => 'Telefon',
            'job_id'          => 'Stellen-ID',
            'job_title'       => 'Stelle',
            'status'          => 'Status',
            'rating'          => 'Bewertung',
            'source'          => 'Quelle',
            'created_at'      => 'Bewerbungsdatum',
            'updated_at'      => 'Letzte Änderung',
            'hired_at'        => 'Einstellungsdatum',
            'time_in_process' => 'Tage im Prozess',
        ];

        if ( empty( $requested ) ) {
            return $available;
        }

        return array_intersect_key( $available, array_flip( $requested ) );
    }

    /**
     * Status-Labels (deutsch)
     */
    private function get_status_label( string $status ): string {
        $labels = [
            'new'       => 'Neu',
            'screening' => 'In Prüfung',
            'interview' => 'Interview',
            'offer'     => 'Angebot',
            'hired'     => 'Eingestellt',
            'rejected'  => 'Abgelehnt',
            'withdrawn' => 'Zurückgezogen',
        ];

        return $labels[ $status ] ?? $status;
    }
}
```

---

## 8. Systemstatus-Widget

### SystemStatusService

```php
namespace RecruitingPlaybook\Services;

class SystemStatusService {

    /**
     * Vollständigen Systemstatus abrufen
     */
    public function get_status(): array {
        $checks = [
            'database'         => $this->check_database(),
            'uploads'          => $this->check_uploads(),
            'cron'             => $this->check_cron(),
            'orphaned_data'    => $this->check_orphaned_data(),
            'license'          => $this->check_license(),
            'action_scheduler' => $this->check_action_scheduler(),
        ];

        $overall_status = $this->determine_overall_status( $checks );

        return [
            'status'          => $overall_status,
            'checks'          => $checks,
            'recommendations' => $this->get_recommendations( $checks ),
            'plugin_version'  => RP_VERSION,
            'php_version'     => PHP_VERSION,
            'wp_version'      => get_bloginfo( 'version' ),
            'checked_at'      => current_time( 'c' ),
        ];
    }

    /**
     * Datenbank-Tabellen prüfen
     */
    private function check_database(): array {
        global $wpdb;

        $required_tables = [
            'rp_applications',
            'rp_candidates',
            'rp_documents',
            'rp_activity_log',
            'rp_notes',
            'rp_ratings',
            'rp_talent_pool',
            'rp_email_log',
        ];

        $missing = [];
        foreach ( $required_tables as $table ) {
            $full_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var(
                $wpdb->prepare( "SHOW TABLES LIKE %s", $full_name )
            );
            if ( ! $exists ) {
                $missing[] = $table;
            }
        }

        if ( empty( $missing ) ) {
            return [
                'status'  => 'ok',
                'message' => __( 'Alle Tabellen vorhanden', 'recruiting-playbook' ),
                'details' => [
                    'tables_expected' => count( $required_tables ),
                    'tables_found'    => count( $required_tables ),
                ],
            ];
        }

        return [
            'status'  => 'error',
            'message' => sprintf(
                __( '%d Tabelle(n) fehlen', 'recruiting-playbook' ),
                count( $missing )
            ),
            'details' => [
                'tables_expected' => count( $required_tables ),
                'tables_found'    => count( $required_tables ) - count( $missing ),
                'missing'         => $missing,
            ],
        ];
    }

    /**
     * Upload-Verzeichnis prüfen
     */
    private function check_uploads(): array {
        $upload_dir = wp_upload_dir();
        $rp_dir = $upload_dir['basedir'] . '/recruiting-playbook/';

        if ( ! file_exists( $rp_dir ) ) {
            // Versuchen zu erstellen
            wp_mkdir_p( $rp_dir );
        }

        $writable = is_writable( $rp_dir );
        $files_count = 0;
        $total_size = 0;

        if ( is_dir( $rp_dir ) ) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator( $rp_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
            );
            foreach ( $files as $file ) {
                if ( $file->isFile() ) {
                    $files_count++;
                    $total_size += $file->getSize();
                }
            }
        }

        return [
            'status'  => $writable ? 'ok' : 'error',
            'message' => $writable
                ? __( 'Upload-Verzeichnis beschreibbar', 'recruiting-playbook' )
                : __( 'Upload-Verzeichnis nicht beschreibbar', 'recruiting-playbook' ),
            'details' => [
                'path'        => $rp_dir,
                'writable'    => $writable,
                'files_count' => $files_count,
                'total_size'  => size_format( $total_size ),
            ],
        ];
    }

    /**
     * Verwaiste Daten prüfen
     */
    private function check_orphaned_data(): array {
        global $wpdb;

        // Dokumente ohne zugehörige Bewerbung
        $orphaned_docs = (int) $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}rp_documents d
            LEFT JOIN {$wpdb->prefix}rp_applications a ON d.application_id = a.id
            WHERE a.id IS NULL AND d.application_id IS NOT NULL
        " );

        // Bewerbungen ohne zugehörige Stelle
        $orphaned_apps = (int) $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}rp_applications a
            LEFT JOIN {$wpdb->posts} p ON a.job_id = p.ID
            WHERE p.ID IS NULL
        " );

        $total_orphaned = $orphaned_docs + $orphaned_apps;

        if ( $total_orphaned === 0 ) {
            return [
                'status'  => 'ok',
                'message' => __( 'Keine verwaisten Daten', 'recruiting-playbook' ),
                'details' => [
                    'orphaned_documents'   => 0,
                    'orphaned_applications' => 0,
                ],
            ];
        }

        return [
            'status'  => 'warning',
            'message' => sprintf(
                __( '%d verwaiste Einträge gefunden', 'recruiting-playbook' ),
                $total_orphaned
            ),
            'details' => [
                'orphaned_documents'    => $orphaned_docs,
                'orphaned_applications' => $orphaned_apps,
            ],
        ];
    }

    /**
     * Gesamtstatus ermitteln
     */
    private function determine_overall_status( array $checks ): string {
        $has_error = false;
        $has_warning = false;

        foreach ( $checks as $check ) {
            if ( $check['status'] === 'error' ) {
                $has_error = true;
            } elseif ( $check['status'] === 'warning' ) {
                $has_warning = true;
            }
        }

        if ( $has_error ) {
            return 'unhealthy';
        }

        if ( $has_warning ) {
            return 'degraded';
        }

        return 'healthy';
    }
}
```

---

## 9. Frontend-Komponenten

### ReportingDashboard.jsx (Hauptkomponente)

```jsx
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { StatsCard } from './StatsCard';
import { TrendChart } from './TrendChart';
import { JobStatsTable } from './JobStatsTable';
import { TimeToHireCard } from './TimeToHireCard';
import { ConversionFunnel } from './ConversionFunnel';
import { ExportButton } from './ExportButton';
import { DateRangePicker } from './DateRangePicker';
import { useStats } from './hooks/useStats';

export function ReportingDashboard() {
    const [period, setPeriod] = useState('30days');
    const [dateRange, setDateRange] = useState(null);
    const { data, isLoading, error, refetch } = useStats(period, dateRange);

    const isPro = window.rpReportingData?.isPro ?? false;

    if (isLoading) {
        return <LoadingSpinner />;
    }

    if (error) {
        return <ErrorMessage message={error.message} onRetry={refetch} />;
    }

    return (
        <div className="rp-reporting-dashboard">
            {/* Header mit Zeitraum-Auswahl */}
            <div className="rp-reporting-header">
                <h1>{__('Reporting & Statistiken', 'recruiting-playbook')}</h1>
                <div className="rp-reporting-controls">
                    <DateRangePicker
                        period={period}
                        onPeriodChange={setPeriod}
                        dateRange={dateRange}
                        onDateRangeChange={setDateRange}
                    />
                    {isPro && <ExportButton dateRange={dateRange} period={period} />}
                </div>
            </div>

            {/* Quick Stats */}
            <div className="rp-stats-grid">
                <StatsCard
                    title={__('Bewerbungen', 'recruiting-playbook')}
                    value={data.applications.total}
                    change={data.applications.period_change}
                    icon="groups"
                />
                <StatsCard
                    title={__('Aktive Stellen', 'recruiting-playbook')}
                    value={data.jobs.active}
                    icon="briefcase"
                />
                <StatsCard
                    title={__('Eingestellt', 'recruiting-playbook')}
                    value={data.applications.hired}
                    icon="yes-alt"
                    color="green"
                />
                {isPro ? (
                    <>
                        <TimeToHireCard data={data.time_to_hire} />
                        <StatsCard
                            title={__('Conversion Rate', 'recruiting-playbook')}
                            value={`${data.conversion_rate.rate}%`}
                            change={data.conversion_rate.period_change}
                            icon="chart-line"
                        />
                    </>
                ) : (
                    <ProUpgradeCard feature="advanced_stats" />
                )}
            </div>

            {/* Trend Chart */}
            <div className="rp-chart-section">
                <h2>{__('Bewerbungen im Zeitverlauf', 'recruiting-playbook')}</h2>
                {isPro ? (
                    <TrendChart period={period} dateRange={dateRange} />
                ) : (
                    <ProUpgradeCard feature="charts" large />
                )}
            </div>

            {/* Conversion Funnel (Pro) */}
            {isPro && (
                <div className="rp-funnel-section">
                    <h2>{__('Conversion Funnel', 'recruiting-playbook')}</h2>
                    <ConversionFunnel data={data.conversion_rate.funnel} />
                </div>
            )}

            {/* Job Stats Table */}
            <div className="rp-table-section">
                <h2>{__('Statistiken pro Stelle', 'recruiting-playbook')}</h2>
                {isPro ? (
                    <JobStatsTable period={period} dateRange={dateRange} />
                ) : (
                    <ProUpgradeCard feature="job_stats" />
                )}
            </div>
        </div>
    );
}
```

### StatsCard.jsx

```jsx
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';

export function StatsCard({ title, value, change, icon, color = 'blue' }) {
    const changeClass = change > 0 ? 'rp-change-positive' : change < 0 ? 'rp-change-negative' : '';
    const changeIcon = change > 0 ? 'arrow-up-alt' : change < 0 ? 'arrow-down-alt' : 'minus';

    return (
        <div className={`rp-stats-card rp-stats-card--${color}`}>
            <div className="rp-stats-card__icon">
                <Dashicon icon={icon} />
            </div>
            <div className="rp-stats-card__content">
                <span className="rp-stats-card__value">{value}</span>
                <span className="rp-stats-card__title">{title}</span>
            </div>
            {change !== undefined && (
                <div className={`rp-stats-card__change ${changeClass}`}>
                    <Dashicon icon={changeIcon} />
                    <span>{Math.abs(change)}%</span>
                </div>
            )}
        </div>
    );
}
```

### TrendChart.jsx (mit Recharts)

```jsx
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Legend,
} from 'recharts';
import apiFetch from '@wordpress/api-fetch';

export function TrendChart({ period, dateRange }) {
    const [data, setData] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [metrics, setMetrics] = useState(['applications', 'hires']);

    useEffect(() => {
        const fetchTrends = async () => {
            setIsLoading(true);
            try {
                const params = new URLSearchParams({
                    metrics: metrics.join(','),
                    granularity: period === 'today' ? 'hour' : period === 'year' ? 'month' : 'day',
                });

                if (dateRange) {
                    params.append('date_from', dateRange.from);
                    params.append('date_to', dateRange.to);
                }

                const response = await apiFetch({
                    path: `/recruiting/v1/stats/trends?${params}`,
                });

                setData(response.data);
            } catch (error) {
                console.error('Failed to fetch trends:', error);
            } finally {
                setIsLoading(false);
            }
        };

        fetchTrends();
    }, [period, dateRange, metrics]);

    if (isLoading) {
        return <div className="rp-chart-loading">Lade...</div>;
    }

    return (
        <div className="rp-trend-chart">
            <div className="rp-chart-controls">
                <MetricToggle metrics={metrics} onChange={setMetrics} />
            </div>
            <ResponsiveContainer width="100%" height={300}>
                <LineChart data={data}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis
                        dataKey="date"
                        tickFormatter={(date) => formatDate(date, period)}
                    />
                    <YAxis />
                    <Tooltip
                        labelFormatter={(date) => formatDateLong(date)}
                        formatter={(value, name) => [value, getMetricLabel(name)]}
                    />
                    <Legend />
                    {metrics.includes('applications') && (
                        <Line
                            type="monotone"
                            dataKey="applications"
                            name={__('Bewerbungen', 'recruiting-playbook')}
                            stroke="#2271b1"
                            strokeWidth={2}
                            dot={false}
                        />
                    )}
                    {metrics.includes('hires') && (
                        <Line
                            type="monotone"
                            dataKey="hires"
                            name={__('Eingestellt', 'recruiting-playbook')}
                            stroke="#00a32a"
                            strokeWidth={2}
                            dot={false}
                        />
                    )}
                    {metrics.includes('views') && (
                        <Line
                            type="monotone"
                            dataKey="views"
                            name={__('Aufrufe', 'recruiting-playbook')}
                            stroke="#dba617"
                            strokeWidth={2}
                            dot={false}
                        />
                    )}
                </LineChart>
            </ResponsiveContainer>
        </div>
    );
}
```

### useStats.js Hook

```js
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export function useStats(period = '30days', dateRange = null) {
    const [data, setData] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchStats = useCallback(async () => {
        setIsLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams({ period });

            if (dateRange) {
                params.append('date_from', dateRange.from);
                params.append('date_to', dateRange.to);
            }

            const response = await apiFetch({
                path: `/recruiting/v1/stats/overview?${params}`,
            });

            setData(response);
        } catch (err) {
            setError(err);
        } finally {
            setIsLoading(false);
        }
    }, [period, dateRange]);

    useEffect(() => {
        fetchStats();
    }, [fetchStats]);

    return {
        data,
        isLoading,
        error,
        refetch: fetchStats,
    };
}
```

---

## 10. Berechtigungen

### Capability Matrix

| Capability | Admin | Recruiter | Hiring Manager |
|------------|-------|-----------|----------------|
| `rp_view_stats` | ✅ | ✅ | ✅ |
| `rp_view_advanced_stats` | ✅ | ✅ | ❌ |
| `rp_export_data` | ✅ | ✅ | ❌ |
| `rp_view_system_status` | ✅ | ❌ | ❌ |
| `rp_run_cleanup` | ✅ | ❌ | ❌ |

### Permission Checks

```php
namespace RecruitingPlaybook\Api;

class StatsController extends BaseController {

    public function stats_permissions_check( WP_REST_Request $request ): bool {
        return current_user_can( 'rp_view_stats' );
    }

    public function advanced_stats_permissions_check( WP_REST_Request $request ): bool {
        // Pro-Feature + Capability Check
        if ( ! rp_can( 'advanced_reporting' ) ) {
            return false;
        }

        return current_user_can( 'rp_view_advanced_stats' );
    }

    public function export_permissions_check( WP_REST_Request $request ): bool {
        // Pro-Feature + Capability Check
        if ( ! rp_can( 'csv_export' ) ) {
            return false;
        }

        return current_user_can( 'rp_export_data' );
    }

    public function admin_permissions_check( WP_REST_Request $request ): bool {
        return current_user_can( 'rp_view_system_status' );
    }
}
```

---

## 11. Testing

### PHPUnit Tests

```php
namespace RecruitingPlaybook\Tests\Services;

use RecruitingPlaybook\Services\StatsService;
use RecruitingPlaybook\Services\TimeToHireService;
use RecruitingPlaybook\Tests\TestCase;

class StatsServiceTest extends TestCase {

    private StatsService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = $this->container->get( StatsService::class );
    }

    /** @test */
    public function it_calculates_application_summary(): void {
        // Arrange
        $this->create_applications( 10, [ 'status' => 'new' ] );
        $this->create_applications( 5, [ 'status' => 'hired' ] );
        $this->create_applications( 3, [ 'status' => 'rejected' ] );

        // Act
        $overview = $this->service->get_overview( '30days' );

        // Assert
        $this->assertEquals( 18, $overview['applications']['total'] );
        $this->assertEquals( 10, $overview['applications']['new'] );
        $this->assertEquals( 5, $overview['applications']['hired'] );
        $this->assertEquals( 3, $overview['applications']['rejected'] );
    }

    /** @test */
    public function it_calculates_period_change_correctly(): void {
        // Arrange: 10 Bewerbungen vor 60 Tagen, 15 Bewerbungen letzte 30 Tage
        $this->create_applications( 10, [
            'created_at' => gmdate( 'Y-m-d', strtotime( '-45 days' ) ),
        ] );
        $this->create_applications( 15, [
            'created_at' => gmdate( 'Y-m-d', strtotime( '-15 days' ) ),
        ] );

        // Act
        $overview = $this->service->get_overview( '30days' );

        // Assert: 15 vs 10 = +50%
        $this->assertEquals( 50.0, $overview['applications']['period_change'] );
    }

    /** @test */
    public function it_caches_results(): void {
        // Arrange
        $this->create_applications( 5 );

        // Act
        $first_call = $this->service->get_overview( '30days' );
        $this->create_applications( 5 ); // Mehr Daten hinzufügen
        $second_call = $this->service->get_overview( '30days' );

        // Assert: Cache sollte gleiches Ergebnis liefern
        $this->assertEquals( $first_call['applications']['total'], $second_call['applications']['total'] );
    }
}

class TimeToHireServiceTest extends TestCase {

    private TimeToHireService $service;

    /** @test */
    public function it_calculates_average_time_to_hire(): void {
        // Arrange: Bewerbungen mit unterschiedlichen Hire-Zeiten
        $this->create_hired_application( days_to_hire: 10 );
        $this->create_hired_application( days_to_hire: 20 );
        $this->create_hired_application( days_to_hire: 30 );

        // Act
        $result = $this->service->calculate( $this->get_last_30_days() );

        // Assert
        $this->assertEquals( 20, $result['overall']['average_days'] );
        $this->assertEquals( 20, $result['overall']['median_days'] );
        $this->assertEquals( 10, $result['overall']['min_days'] );
        $this->assertEquals( 30, $result['overall']['max_days'] );
    }

    /** @test */
    public function it_returns_empty_result_when_no_hires(): void {
        // Arrange
        $this->create_applications( 5, [ 'status' => 'new' ] );

        // Act
        $result = $this->service->calculate( $this->get_last_30_days() );

        // Assert
        $this->assertEquals( 0, $result['overall']['total_hires'] );
        $this->assertNull( $result['overall']['average_days'] );
    }
}

class ExportServiceTest extends TestCase {

    /** @test */
    public function it_requires_pro_license_for_export(): void {
        // Arrange
        $this->disable_pro_features();
        $service = $this->container->get( ExportService::class );

        // Act & Assert
        $this->expectException( FeatureNotAvailableException::class );
        $service->export_applications( [] );
    }

    /** @test */
    public function it_exports_correct_csv_format(): void {
        // Arrange
        $this->enable_pro_features();
        $this->create_applications( 3 );

        // Act
        ob_start();
        $this->service->export_applications( [
            'columns' => [ 'id', 'candidate_name', 'status' ],
        ] );
        $output = ob_get_clean();

        // Assert
        $lines = explode( "\n", trim( $output ) );
        $this->assertCount( 4, $lines ); // Header + 3 Zeilen
        $this->assertStringContainsString( 'ID;Name;Status', $lines[0] );
    }
}

class SystemStatusServiceTest extends TestCase {

    /** @test */
    public function it_reports_healthy_when_all_checks_pass(): void {
        // Act
        $status = $this->service->get_status();

        // Assert
        $this->assertEquals( 'healthy', $status['status'] );
    }

    /** @test */
    public function it_detects_missing_tables(): void {
        // Arrange
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rp_notes" );

        // Act
        $status = $this->service->get_status();

        // Assert
        $this->assertEquals( 'error', $status['checks']['database']['status'] );
        $this->assertContains( 'rp_notes', $status['checks']['database']['details']['missing'] );
    }

    /** @test */
    public function it_detects_orphaned_documents(): void {
        // Arrange
        $this->create_orphaned_document();

        // Act
        $status = $this->service->get_status();

        // Assert
        $this->assertEquals( 'warning', $status['checks']['orphaned_data']['status'] );
        $this->assertEquals( 1, $status['checks']['orphaned_data']['details']['orphaned_documents'] );
    }
}
```

### Jest Tests (Frontend)

```js
// __tests__/StatsCard.test.jsx
import { render, screen } from '@testing-library/react';
import { StatsCard } from '../StatsCard';

describe('StatsCard', () => {
    it('displays value and title', () => {
        render(<StatsCard title="Bewerbungen" value={42} icon="groups" />);

        expect(screen.getByText('42')).toBeInTheDocument();
        expect(screen.getByText('Bewerbungen')).toBeInTheDocument();
    });

    it('shows positive change with up arrow', () => {
        render(<StatsCard title="Test" value={100} change={15.5} icon="chart-line" />);

        expect(screen.getByText('15.5%')).toBeInTheDocument();
        expect(screen.getByText('15.5%').closest('.rp-stats-card__change'))
            .toHaveClass('rp-change-positive');
    });

    it('shows negative change with down arrow', () => {
        render(<StatsCard title="Test" value={100} change={-10} icon="chart-line" />);

        expect(screen.getByText('10%')).toBeInTheDocument();
        expect(screen.getByText('10%').closest('.rp-stats-card__change'))
            .toHaveClass('rp-change-negative');
    });
});

// __tests__/useStats.test.js
import { renderHook, waitFor } from '@testing-library/react';
import { useStats } from '../hooks/useStats';

jest.mock('@wordpress/api-fetch');

describe('useStats', () => {
    it('fetches stats on mount', async () => {
        const mockData = { applications: { total: 50 } };
        apiFetch.mockResolvedValue(mockData);

        const { result } = renderHook(() => useStats('30days'));

        await waitFor(() => {
            expect(result.current.isLoading).toBe(false);
        });

        expect(result.current.data).toEqual(mockData);
    });

    it('refetches when period changes', async () => {
        const { result, rerender } = renderHook(
            ({ period }) => useStats(period),
            { initialProps: { period: '30days' } }
        );

        await waitFor(() => expect(result.current.isLoading).toBe(false));

        rerender({ period: '7days' });

        expect(result.current.isLoading).toBe(true);
    });
});
```

---

## 12. Implementierungsplan

### Phase 1: Backend-Grundlagen (2-3 Tage)

1. **StatsRepository** erstellen
   - Optimierte SQL-Queries für Statistiken
   - Index-Migrationen

2. **StatsService** implementieren
   - Basis-Statistiken (Counts, Summaries)
   - Caching-Logik

3. **REST API Controller**
   - `/stats/overview` Endpoint
   - Permission Checks

### Phase 2: Erweiterte Statistiken (2-3 Tage)

1. **TimeToHireService**
   - Berechnungslogik
   - Stage-Tracking

2. **ConversionService**
   - View-Tracking Integration
   - Funnel-Berechnung

3. **Weitere Endpoints**
   - `/stats/applications`
   - `/stats/jobs`
   - `/stats/time-to-hire`
   - `/stats/conversion`
   - `/stats/trends`

### Phase 3: Dashboard-Widgets (2 Tage)

1. **DashboardManager**
   - Widget-Registrierung
   - Script-Enqueueing

2. **React-Komponenten (Basis)**
   - StatsCard
   - Basic Layout

### Phase 4: Frontend-Reporting (3-4 Tage)

1. **ReportingPage**
   - Admin-Menü Integration
   - React Entry Point

2. **Chart-Komponenten**
   - TrendChart (Recharts)
   - ConversionFunnel

3. **Tabellen & Filter**
   - JobStatsTable
   - DateRangePicker

### Phase 5: Export & Systemstatus (2 Tage)

1. **ExportService**
   - CSV-Streaming
   - Spalten-Konfiguration

2. **SystemStatusService**
   - Integritäts-Checks
   - Recommendations

3. **SystemStatus-Widget**
   - Admin Dashboard Integration

### Phase 6: Testing & Polish (2 Tage)

1. **PHPUnit Tests**
   - Services
   - API Endpoints

2. **Jest Tests**
   - React-Komponenten
   - Hooks

3. **Dokumentation**
   - API-Dokumentation
   - User Guide

---

## Anhang: SQL-Queries für Statistiken

### Bewerbungen pro Status (optimiert)

```sql
SELECT
    status,
    COUNT(*) as count
FROM {$prefix}rp_applications
WHERE created_at BETWEEN %s AND %s
    AND deleted_at IS NULL
GROUP BY status;
```

### Time-to-Hire Berechnung

```sql
SELECT
    a.id,
    a.created_at,
    a.updated_at as hired_at,
    DATEDIFF(a.updated_at, a.created_at) as days_to_hire
FROM {$prefix}rp_applications a
WHERE a.status = 'hired'
    AND a.updated_at BETWEEN %s AND %s
    AND a.deleted_at IS NULL;
```

### Top-Stellen nach Bewerbungen

```sql
SELECT
    p.ID as job_id,
    p.post_title as title,
    COUNT(a.id) as application_count
FROM {$prefix}posts p
LEFT JOIN {$prefix}rp_applications a ON p.ID = a.job_id
WHERE p.post_type = 'job_listing'
    AND p.post_status = 'publish'
    AND (a.created_at BETWEEN %s AND %s OR a.created_at IS NULL)
GROUP BY p.ID
ORDER BY application_count DESC
LIMIT %d;
```

---

## 13. Implementierungshinweise

### Umgesetzte Features (Januar 2025)

| Feature | Status | Hinweis |
|---------|--------|---------|
| Stats-Dashboard (React-Seite) | ✅ | Vollständig implementiert |
| Bewerbungen pro Stelle | ✅ | JobStatsTable Komponente |
| Bewerbungen pro Zeitraum | ✅ | TrendChart mit Periodenauswahl |
| Time-to-Hire | ✅ | TimeToHireService |
| CSV-Export | ✅ | Bewerbungen & Statistiken |
| Systemstatus-Widget | ✅ | SystemStatus Komponente |
| Conversion-Rate | ❌ | **Entfernt** - View-Tracking nicht implementiert |

### Änderungen zur Spezifikation

1. **Conversion-Rate entfernt**: Die `tracking.js` Datei pusht Events nur an `window.dataLayer` für Google Tag Manager. Es gibt keine Server-seitige Speicherung von Job-Views in der `rp_activity_log` Tabelle. Die Spalten "Aufrufe" und "Conversion" wurden aus der JobStatsTable entfernt.

2. **ConversionFunnel**: Der Conversion-Tab im Reporting zeigt simulierte Daten basierend auf Bewerbungsstatus statt echten View-Daten.

### Zukünftige Erweiterung: View-Tracking

Um echte Conversion-Daten zu ermöglichen, müsste implementiert werden:

```php
// Beispiel: REST-Endpoint für View-Tracking
register_rest_route( 'recruiting/v1', '/track/view', [
    'methods'  => 'POST',
    'callback' => [ $this, 'track_job_view' ],
    'permission_callback' => '__return_true',
]);

// Activity Log Eintrag
$this->activity_service->log( [
    'action'      => 'job_viewed',
    'object_type' => 'job',
    'object_id'   => $job_id,
    'ip_hash'     => wp_hash( $_SERVER['REMOTE_ADDR'] ),
    'created_at'  => current_time( 'mysql' ),
] );
```

---

*Letzte Aktualisierung: 30. Januar 2025*

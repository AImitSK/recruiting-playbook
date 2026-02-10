# Benutzerrollen & Berechtigungen: Technische Spezifikation

> **Pro-Feature: Benutzerrollen-Management**
> Custom Rollen, granulare Berechtigungen und Stellen-Zuweisung für Team-Kollaboration

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Ist-Zustand Analyse](#2-ist-zustand-analyse)
3. [Architektur](#3-architektur)
4. [Datenmodell](#4-datenmodell)
5. [Rollen-Definition](#5-rollen-definition)
6. [Capabilities-System](#6-capabilities-system)
7. [Stellen-Zuweisung](#7-stellen-zuweisung)
8. [REST API Endpunkte](#8-rest-api-endpunkte)
9. [Admin UI](#9-admin-ui)
10. [Migration bestehender Daten](#10-migration-bestehender-daten)
11. [Testing](#11-testing)

---

## 1. Übersicht

### Zielsetzung

Das Benutzerrollen-System ermöglicht:
- **Custom Rollen** für unterschiedliche Recruiting-Aufgaben (Administrator, Recruiter, Hiring Manager)
- **Granulare Berechtigungen** für feinteilige Zugriffskontrolle
- **Stellen-Zuweisung** damit Recruiter nur "ihre" Bewerbungen sehen
- **Team-Kollaboration** durch klare Verantwortlichkeiten

### Feature-Gating

```php
// Pro-Feature Check
if ( ! rp_can( 'user_roles' ) ) {
    rp_require_feature( 'user_roles', 'Benutzerrollen', 'PRO' );
}

// In Free: Nur Administrator hat Zugriff (Standard WordPress)
// In Pro: Recruiter und Hiring Manager Rollen verfügbar
```

### User Stories

| Als | möchte ich | damit |
|-----|-----------|-------|
| Administrator | Rollen erstellen und Capabilities zuweisen | ich das Team flexibel einrichten kann |
| Administrator | Recruiter bestimmten Stellen zuweisen | sie nur relevante Bewerbungen sehen |
| Recruiter | nur meine zugewiesenen Bewerbungen sehen | ich fokussiert arbeiten kann |
| Hiring Manager | Bewerbungen für meine Abteilung lesen | ich am Prozess teilnehmen kann |
| Hiring Manager | Notizen und Bewertungen hinterlassen | mein Feedback dokumentiert ist |
| Administrator | alle Bewerbungen sehen | ich den Überblick behalte |

---

## 2. Ist-Zustand Analyse

### Bereits implementiert

| Komponente | Status | Details |
|------------|--------|---------|
| Custom Capabilities | ✅ Vorhanden | 20+ Capabilities mit `rp_` Präfix |
| Capability-Zuweisung | ✅ Vorhanden | Admin + Editor erhalten Capabilities bei Aktivierung |
| Permission Callbacks | ✅ Vorhanden | Alle API-Controller prüfen Capabilities |
| Feature-Flags | ✅ Vorhanden | `rp_can('user_roles')` für Pro-Gating |
| Audit-Trail | ✅ Vorhanden | Activity Log speichert user_id/user_name |

### Noch zu implementieren

| Komponente | Status | Beschreibung |
|------------|--------|--------------|
| Custom Rollen | ❌ Fehlt | Recruiter, Hiring Manager als WordPress-Rollen |
| Admin-Menü Differenzierung | ❌ Fehlt | Menüpunkte nach Rolle filtern (aktuell: `manage_options`) |
| Stellen-Zuweisung | ❌ Fehlt | User-zu-Job Zuordnung |
| Bewerber-Filterung | ❌ Fehlt | Recruiter sieht nur zugewiesene Bewerbungen |
| Rollen-Admin-UI | ❌ Fehlt | UI zur Rollen-Verwaltung |
| User-Stellen-Tabelle | ❌ Fehlt | Datenbank-Tabelle für Zuordnung |

### Bestehende Capabilities (Activator.php)

```php
// Basis-Capabilities
'rp_manage_recruiting'      // Dashboard-Zugriff
'rp_view_applications'      // Bewerbungen lesen
'rp_edit_applications'      // Bewerbungen bearbeiten
'rp_delete_applications'    // Bewerbungen löschen

// Notizen
'rp_view_notes'             // Notizen lesen
'rp_create_notes'           // Notizen erstellen
'rp_edit_own_notes'         // Eigene Notizen bearbeiten
'rp_edit_others_notes'      // Fremde Notizen bearbeiten (Admin)
'rp_delete_notes'           // Notizen löschen

// Bewertungen & Talent-Pool
'rp_rate_applications'      // Bewerbungen bewerten
'rp_manage_talent_pool'     // Talent-Pool verwalten
'rp_view_activity_log'      // Aktivitätslog einsehen

// E-Mail-System
'rp_read_email_templates'   // Templates lesen
'rp_create_email_templates' // Templates erstellen
'rp_edit_email_templates'   // Templates bearbeiten
'rp_delete_email_templates' // Templates löschen (Admin)
'rp_send_emails'            // E-Mails senden
'rp_view_email_log'         // E-Mail-Historie
```

---

## 3. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   ├── Admin/
│   │   └── Pages/
│   │       └── UserRolesPage.php         # Rollen-Verwaltung UI
│   │
│   ├── Api/
│   │   ├── RoleController.php            # REST API für Rollen
│   │   └── JobAssignmentController.php   # REST API für Stellen-Zuweisung
│   │
│   ├── Services/
│   │   ├── RoleService.php               # Rollen Business Logic
│   │   ├── CapabilityService.php         # Capability-Verwaltung
│   │   └── JobAssignmentService.php      # Stellen-Zuweisung Logic
│   │
│   ├── Repositories/
│   │   └── JobAssignmentRepository.php   # User-Job-Zuordnung Data Access
│   │
│   └── Core/
│       └── RoleManager.php               # Rollen-Registrierung bei Aktivierung
│
├── assets/
│   └── src/
│       └── js/
│           └── admin/
│               └── roles/
│                   ├── index.jsx              # Entry Point
│                   ├── RolesPage.jsx          # Hauptkomponente
│                   ├── RoleEditor.jsx         # Rolle bearbeiten
│                   ├── CapabilityMatrix.jsx   # Capability-Checkboxen
│                   ├── JobAssignment.jsx      # Stellen-Zuweisung
│                   └── hooks/
│                       ├── useRoles.js
│                       └── useJobAssignment.js
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Frontend | React 18 (@wordpress/element) |
| State Management | React Context + Custom Hooks |
| API-Kommunikation | @wordpress/api-fetch |
| Styling | Tailwind CSS + shadcn/ui |
| Rollen-System | WordPress Roles & Capabilities API |

---

## 4. Datenmodell

### Neue Tabelle: `rp_user_job_assignments`

```sql
CREATE TABLE {$prefix}rp_user_job_assignments (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id         bigint(20) unsigned NOT NULL,
    job_id          bigint(20) unsigned NOT NULL,
    assigned_by     bigint(20) unsigned NOT NULL,
    assigned_at     datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_job (user_id, job_id),
    KEY user_id (user_id),
    KEY job_id (job_id),
    KEY assigned_by (assigned_by)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `user_id` | bigint | FK zu wp_users (Recruiter/Hiring Manager) |
| `job_id` | bigint | FK zu wp_posts (job_listing) |
| `assigned_by` | bigint | FK zu wp_users (Admin der zugewiesen hat) |
| `assigned_at` | datetime | Zeitpunkt der Zuweisung |

### WordPress Options für Rollen-Konfiguration

```php
// Option: rp_role_capabilities
[
    'rp_recruiter' => [
        'rp_view_applications'      => true,
        'rp_edit_applications'      => true,
        'rp_delete_applications'    => false,
        'rp_view_notes'             => true,
        'rp_create_notes'           => true,
        'rp_edit_own_notes'         => true,
        'rp_edit_others_notes'      => false,
        'rp_delete_notes'           => false,
        'rp_rate_applications'      => true,
        'rp_manage_talent_pool'     => true,
        'rp_view_activity_log'      => true,
        'rp_read_email_templates'   => true,
        'rp_create_email_templates' => false,
        'rp_edit_email_templates'   => true,
        'rp_delete_email_templates' => false,
        'rp_send_emails'            => true,
        'rp_view_email_log'         => true,
    ],
    'rp_hiring_manager' => [
        'rp_view_applications'      => true,
        'rp_edit_applications'      => false,
        'rp_delete_applications'    => false,
        'rp_view_notes'             => true,
        'rp_create_notes'           => true,
        'rp_edit_own_notes'         => true,
        'rp_edit_others_notes'      => false,
        'rp_delete_notes'           => false,
        'rp_rate_applications'      => true,
        'rp_manage_talent_pool'     => false,
        'rp_view_activity_log'      => true,
        'rp_read_email_templates'   => true,
        'rp_create_email_templates' => false,
        'rp_edit_email_templates'   => false,
        'rp_delete_email_templates' => false,
        'rp_send_emails'            => false,
        'rp_view_email_log'         => false,
    ],
]
```

---

## 5. Rollen-Definition

### Standard-Rollen

| Rolle | Slug | Beschreibung | Basis |
|-------|------|--------------|-------|
| **Administrator** | `administrator` | Vollzugriff auf alle Funktionen | WordPress Standard |
| **Recruiter** | `rp_recruiter` | Bewerbungen verwalten, E-Mails senden | Custom Rolle |
| **Hiring Manager** | `rp_hiring_manager` | Lesen, Kommentieren, Bewerten | Custom Rolle |

### Rollen-Hierarchie

```
Administrator (Level 10)
    ├── Vollzugriff auf alle Recruiting-Funktionen
    ├── Kann Rollen und Capabilities verwalten
    ├── Kann Stellen-Zuweisungen vornehmen
    └── Sieht ALLE Bewerbungen (unabhängig von Zuweisung)

Recruiter (Level 5)
    ├── Kann Bewerbungen verwalten (zugewiesene Stellen)
    ├── Kann E-Mails senden
    ├── Kann Notizen erstellen/bearbeiten (eigene)
    ├── Kann Bewertungen abgeben
    ├── Kann Talent-Pool verwalten
    └── Sieht nur ZUGEWIESENE Bewerbungen

Hiring Manager (Level 2)
    ├── Kann Bewerbungen lesen (zugewiesene Stellen)
    ├── Kann Notizen erstellen (eigene)
    ├── Kann Bewertungen abgeben
    ├── Kann Timeline einsehen
    └── Sieht nur ZUGEWIESENE Bewerbungen
```

### RoleManager Klasse

```php
<?php
namespace RecruitingPlaybook\Core;

class RoleManager {

    /**
     * Rollen bei Plugin-Aktivierung registrieren
     */
    public static function register(): void {
        // Recruiter-Rolle
        add_role(
            'rp_recruiter',
            __( 'Recruiter', 'recruiting-playbook' ),
            [
                'read' => true,
                'upload_files' => true,
            ]
        );

        // Hiring Manager-Rolle
        add_role(
            'rp_hiring_manager',
            __( 'Hiring Manager', 'recruiting-playbook' ),
            [
                'read' => true,
            ]
        );

        // Capabilities zuweisen
        self::assignCapabilities();
    }

    /**
     * Rollen bei Plugin-Deaktivierung entfernen
     */
    public static function unregister(): void {
        remove_role( 'rp_recruiter' );
        remove_role( 'rp_hiring_manager' );
    }

    /**
     * Capabilities basierend auf Konfiguration zuweisen
     */
    public static function assignCapabilities(): void {
        $config = get_option( 'rp_role_capabilities', self::getDefaults() );

        foreach ( $config as $role_slug => $capabilities ) {
            $role = get_role( $role_slug );
            if ( ! $role ) {
                continue;
            }

            foreach ( $capabilities as $cap => $granted ) {
                if ( $granted ) {
                    $role->add_cap( $cap );
                } else {
                    $role->remove_cap( $cap );
                }
            }
        }

        // Administrator erhält IMMER alle Capabilities
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( self::getAllCapabilities() as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    /**
     * Alle verfügbaren Capabilities
     */
    public static function getAllCapabilities(): array {
        return [
            // Basis
            'rp_manage_recruiting',
            'rp_view_applications',
            'rp_edit_applications',
            'rp_delete_applications',

            // Notizen
            'rp_view_notes',
            'rp_create_notes',
            'rp_edit_own_notes',
            'rp_edit_others_notes',
            'rp_delete_notes',

            // Bewertungen & Talent-Pool
            'rp_rate_applications',
            'rp_manage_talent_pool',
            'rp_view_activity_log',

            // E-Mail
            'rp_read_email_templates',
            'rp_create_email_templates',
            'rp_edit_email_templates',
            'rp_delete_email_templates',
            'rp_send_emails',
            'rp_view_email_log',

            // Rollen-Verwaltung (nur Admin)
            'rp_manage_roles',
            'rp_assign_jobs',
        ];
    }

    /**
     * Standard-Konfiguration
     */
    public static function getDefaults(): array {
        return [
            'rp_recruiter' => [
                'rp_view_applications'      => true,
                'rp_edit_applications'      => true,
                'rp_delete_applications'    => false,
                'rp_view_notes'             => true,
                'rp_create_notes'           => true,
                'rp_edit_own_notes'         => true,
                'rp_edit_others_notes'      => false,
                'rp_delete_notes'           => false,
                'rp_rate_applications'      => true,
                'rp_manage_talent_pool'     => true,
                'rp_view_activity_log'      => true,
                'rp_read_email_templates'   => true,
                'rp_create_email_templates' => false,
                'rp_edit_email_templates'   => true,
                'rp_delete_email_templates' => false,
                'rp_send_emails'            => true,
                'rp_view_email_log'         => true,
                'rp_manage_roles'           => false,
                'rp_assign_jobs'            => false,
            ],
            'rp_hiring_manager' => [
                'rp_view_applications'      => true,
                'rp_edit_applications'      => false,
                'rp_delete_applications'    => false,
                'rp_view_notes'             => true,
                'rp_create_notes'           => true,
                'rp_edit_own_notes'         => true,
                'rp_edit_others_notes'      => false,
                'rp_delete_notes'           => false,
                'rp_rate_applications'      => true,
                'rp_manage_talent_pool'     => false,
                'rp_view_activity_log'      => true,
                'rp_read_email_templates'   => true,
                'rp_create_email_templates' => false,
                'rp_edit_email_templates'   => false,
                'rp_delete_email_templates' => false,
                'rp_send_emails'            => false,
                'rp_view_email_log'         => false,
                'rp_manage_roles'           => false,
                'rp_assign_jobs'            => false,
            ],
        ];
    }
}
```

---

## 6. Capabilities-System

### Capability-Gruppen

```php
/**
 * Capabilities nach Funktionsbereichen gruppiert
 */
const CAPABILITY_GROUPS = [
    'applications' => [
        'label' => 'Bewerbungen',
        'capabilities' => [
            'rp_view_applications'   => 'Bewerbungen anzeigen',
            'rp_edit_applications'   => 'Bewerbungen bearbeiten',
            'rp_delete_applications' => 'Bewerbungen löschen',
        ],
    ],
    'notes' => [
        'label' => 'Notizen',
        'capabilities' => [
            'rp_view_notes'         => 'Notizen lesen',
            'rp_create_notes'       => 'Notizen erstellen',
            'rp_edit_own_notes'     => 'Eigene Notizen bearbeiten',
            'rp_edit_others_notes'  => 'Fremde Notizen bearbeiten',
            'rp_delete_notes'       => 'Notizen löschen',
        ],
    ],
    'ratings' => [
        'label' => 'Bewertungen',
        'capabilities' => [
            'rp_rate_applications' => 'Bewerbungen bewerten',
        ],
    ],
    'talent_pool' => [
        'label' => 'Talent-Pool',
        'capabilities' => [
            'rp_manage_talent_pool' => 'Talent-Pool verwalten',
        ],
    ],
    'activity' => [
        'label' => 'Aktivitäten',
        'capabilities' => [
            'rp_view_activity_log' => 'Aktivitätslog einsehen',
        ],
    ],
    'email' => [
        'label' => 'E-Mail-System',
        'capabilities' => [
            'rp_read_email_templates'   => 'Templates anzeigen',
            'rp_create_email_templates' => 'Templates erstellen',
            'rp_edit_email_templates'   => 'Templates bearbeiten',
            'rp_delete_email_templates' => 'Templates löschen',
            'rp_send_emails'            => 'E-Mails senden',
            'rp_view_email_log'         => 'E-Mail-Historie',
        ],
    ],
    'admin' => [
        'label' => 'Administration',
        'capabilities' => [
            'rp_manage_recruiting' => 'Dashboard-Zugriff',
            'rp_manage_roles'      => 'Rollen verwalten',
            'rp_assign_jobs'       => 'Stellen zuweisen',
        ],
    ],
];
```

### Capability-Service

```php
<?php
namespace RecruitingPlaybook\Services;

class CapabilityService {

    /**
     * Prüfen ob User Capability hat
     */
    public function userCan( int $user_id, string $capability ): bool {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }

        return user_can( $user, $capability );
    }

    /**
     * Prüfen ob User Zugriff auf Bewerbung hat
     * (Berücksichtigt Stellen-Zuweisung)
     */
    public function canAccessApplication( int $user_id, int $application_id ): bool {
        // Admin hat immer Zugriff
        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        // Basis-Capability prüfen
        if ( ! user_can( $user_id, 'rp_view_applications' ) ) {
            return false;
        }

        // Job-ID der Bewerbung holen
        $application = $this->getApplication( $application_id );
        if ( ! $application ) {
            return false;
        }

        // Prüfen ob User dem Job zugewiesen ist
        return $this->isAssignedToJob( $user_id, $application->job_id );
    }

    /**
     * Prüfen ob User einer Stelle zugewiesen ist
     */
    public function isAssignedToJob( int $user_id, int $job_id ): bool {
        global $wpdb;

        // Admin ist implizit allen Stellen zugewiesen
        if ( user_can( $user_id, 'manage_options' ) ) {
            return true;
        }

        $table = $wpdb->prefix . 'rp_user_job_assignments';

        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND job_id = %d",
            $user_id,
            $job_id
        ) );

        return (int) $result > 0;
    }

    /**
     * Jobs eines Users abrufen
     */
    public function getAssignedJobs( int $user_id ): array {
        global $wpdb;

        // Admin sieht alle Jobs
        if ( user_can( $user_id, 'manage_options' ) ) {
            return get_posts( [
                'post_type'      => 'job_listing',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
            ] );
        }

        $table = $wpdb->prefix . 'rp_user_job_assignments';

        return $wpdb->get_col( $wpdb->prepare(
            "SELECT job_id FROM {$table} WHERE user_id = %d",
            $user_id
        ) );
    }
}
```

---

## 7. Stellen-Zuweisung

### JobAssignmentService

```php
<?php
namespace RecruitingPlaybook\Services;

class JobAssignmentService {

    private JobAssignmentRepository $repository;

    public function __construct( JobAssignmentRepository $repository ) {
        $this->repository = $repository;
    }

    /**
     * User einer Stelle zuweisen
     */
    public function assign( int $user_id, int $job_id, int $assigned_by ): bool {
        // Validierung
        if ( ! get_userdata( $user_id ) ) {
            throw new \InvalidArgumentException( 'User nicht gefunden' );
        }

        if ( get_post_type( $job_id ) !== 'job_listing' ) {
            throw new \InvalidArgumentException( 'Ungültige Stelle' );
        }

        // Bereits zugewiesen?
        if ( $this->repository->exists( $user_id, $job_id ) ) {
            return true;
        }

        return $this->repository->create( [
            'user_id'     => $user_id,
            'job_id'      => $job_id,
            'assigned_by' => $assigned_by,
        ] );
    }

    /**
     * Zuweisung entfernen
     */
    public function unassign( int $user_id, int $job_id ): bool {
        return $this->repository->delete( $user_id, $job_id );
    }

    /**
     * Alle Zuweisungen für eine Stelle abrufen
     */
    public function getAssignedUsers( int $job_id ): array {
        $assignments = $this->repository->findByJob( $job_id );

        return array_map( function ( $assignment ) {
            $user = get_userdata( $assignment['user_id'] );
            return [
                'id'          => $assignment['user_id'],
                'name'        => $user ? $user->display_name : 'Unbekannt',
                'email'       => $user ? $user->user_email : '',
                'role'        => $this->getUserRole( $assignment['user_id'] ),
                'assigned_at' => $assignment['assigned_at'],
                'assigned_by' => $assignment['assigned_by'],
            ];
        }, $assignments );
    }

    /**
     * Alle Stellen eines Users abrufen
     */
    public function getAssignedJobs( int $user_id ): array {
        $assignments = $this->repository->findByUser( $user_id );

        return array_map( function ( $assignment ) {
            $job = get_post( $assignment['job_id'] );
            return [
                'id'          => $assignment['job_id'],
                'title'       => $job ? $job->post_title : 'Gelöscht',
                'status'      => $job ? $job->post_status : 'unknown',
                'assigned_at' => $assignment['assigned_at'],
            ];
        }, $assignments );
    }

    /**
     * Bulk-Zuweisung
     */
    public function bulkAssign( int $user_id, array $job_ids, int $assigned_by ): int {
        $count = 0;
        foreach ( $job_ids as $job_id ) {
            if ( $this->assign( $user_id, $job_id, $assigned_by ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * User-Rolle ermitteln
     */
    private function getUserRole( int $user_id ): string {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return 'unknown';
        }

        if ( in_array( 'administrator', $user->roles, true ) ) {
            return 'administrator';
        }
        if ( in_array( 'rp_recruiter', $user->roles, true ) ) {
            return 'recruiter';
        }
        if ( in_array( 'rp_hiring_manager', $user->roles, true ) ) {
            return 'hiring_manager';
        }

        return $user->roles[0] ?? 'subscriber';
    }
}
```

### Bewerbungs-Filter nach Zuweisung

```php
/**
 * Filter für ApplicationRepository
 * Filtert Bewerbungen nach zugewiesenen Stellen
 */
public function findForUser( int $user_id, array $filters = [] ): array {
    $capability_service = new CapabilityService();

    // Admin sieht alle
    if ( user_can( $user_id, 'manage_options' ) ) {
        return $this->findAll( $filters );
    }

    // Zugewiesene Jobs holen
    $assigned_jobs = $capability_service->getAssignedJobs( $user_id );

    if ( empty( $assigned_jobs ) ) {
        return [];
    }

    // Filter auf zugewiesene Jobs beschränken
    $filters['job_id__in'] = $assigned_jobs;

    return $this->findAll( $filters );
}
```

---

## 8. REST API Endpunkte

### Rollen-Endpoints

```
GET    /wp-json/recruiting/v1/roles
POST   /wp-json/recruiting/v1/roles
GET    /wp-json/recruiting/v1/roles/{slug}
PUT    /wp-json/recruiting/v1/roles/{slug}
DELETE /wp-json/recruiting/v1/roles/{slug}
GET    /wp-json/recruiting/v1/roles/capabilities
```

### Stellen-Zuweisung Endpoints

```
GET    /wp-json/recruiting/v1/job-assignments
POST   /wp-json/recruiting/v1/job-assignments
DELETE /wp-json/recruiting/v1/job-assignments/{id}
GET    /wp-json/recruiting/v1/job-assignments/user/{user_id}
GET    /wp-json/recruiting/v1/job-assignments/job/{job_id}
POST   /wp-json/recruiting/v1/job-assignments/bulk
```

### API-Beispiele

#### Rollen abrufen

```
GET /wp-json/recruiting/v1/roles
```

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
        "rp_delete_applications": false
      },
      "user_count": 5
    },
    {
      "slug": "rp_hiring_manager",
      "name": "Hiring Manager",
      "capabilities": {
        "rp_view_applications": true,
        "rp_edit_applications": false,
        "rp_delete_applications": false
      },
      "user_count": 12
    }
  ]
}
```

#### User einer Stelle zuweisen

```
POST /wp-json/recruiting/v1/job-assignments
Content-Type: application/json

{
  "user_id": 5,
  "job_id": 123
}
```

**Response:**

```json
{
  "success": true,
  "assignment": {
    "id": 42,
    "user_id": 5,
    "job_id": 123,
    "assigned_at": "2025-01-28T14:30:00+01:00",
    "assigned_by": 1
  }
}
```

#### Bulk-Zuweisung

```
POST /wp-json/recruiting/v1/job-assignments/bulk
Content-Type: application/json

{
  "user_id": 5,
  "job_ids": [123, 124, 125]
}
```

**Response:**

```json
{
  "success": true,
  "assigned_count": 3,
  "assignments": [
    { "job_id": 123, "assigned": true },
    { "job_id": 124, "assigned": true },
    { "job_id": 125, "assigned": true }
  ]
}
```

---

## 9. Admin UI

### Menü-Struktur

```
Recruiting
├── Dashboard
├── Bewerbungen
├── Kanban-Board [Pro]
├── Talent-Pool [Pro]
├── E-Mail-Templates [Pro]
├── Einstellungen
│   ├── Allgemein
│   ├── Firmendaten
│   ├── Export
│   └── Benutzerrollen [Pro] ← NEU
└── Lizenz
```

### Benutzerrollen-Seite

```jsx
/**
 * RolesPage - Benutzerrollen-Verwaltung
 */
export function RolesPage() {
    const [activeTab, setActiveTab] = useState('roles');

    return (
        <div className="rp-admin">
            <header className="rp-admin-header">
                <img src={logoUrl} alt="Recruiting Playbook" />
                <div>
                    <h1>Benutzerrollen</h1>
                    <p>Rollen und Berechtigungen verwalten</p>
                </div>
            </header>

            <Tabs value={activeTab} onValueChange={setActiveTab}>
                <TabsList>
                    <TabsTrigger value="roles">Rollen</TabsTrigger>
                    <TabsTrigger value="assignments">Stellen-Zuweisung</TabsTrigger>
                </TabsList>

                <TabsContent value="roles">
                    <RolesList />
                </TabsContent>

                <TabsContent value="assignments">
                    <JobAssignments />
                </TabsContent>
            </Tabs>
        </div>
    );
}
```

### Capability-Matrix UI

```
┌─────────────────────────────────────────────────────────────────┐
│ Berechtigungen                                                   │
├─────────────────────────────────────────────────────────────────┤
│                           │ Admin │ Recruiter │ Hiring Manager  │
├───────────────────────────┼───────┼───────────┼─────────────────┤
│ BEWERBUNGEN               │       │           │                 │
│ ├─ Anzeigen               │  ✓    │    ✓      │       ✓         │
│ ├─ Bearbeiten             │  ✓    │    ✓      │       ○         │
│ └─ Löschen                │  ✓    │    ○      │       ○         │
├───────────────────────────┼───────┼───────────┼─────────────────┤
│ NOTIZEN                   │       │           │                 │
│ ├─ Lesen                  │  ✓    │    ✓      │       ✓         │
│ ├─ Erstellen              │  ✓    │    ✓      │       ✓         │
│ ├─ Eigene bearbeiten      │  ✓    │    ✓      │       ✓         │
│ ├─ Fremde bearbeiten      │  ✓    │    ○      │       ○         │
│ └─ Löschen                │  ✓    │    ○      │       ○         │
├───────────────────────────┼───────┼───────────┼─────────────────┤
│ E-MAIL                    │       │           │                 │
│ ├─ Templates lesen        │  ✓    │    ✓      │       ✓         │
│ ├─ Templates erstellen    │  ✓    │    ○      │       ○         │
│ ├─ Templates bearbeiten   │  ✓    │    ✓      │       ○         │
│ ├─ E-Mails senden         │  ✓    │    ✓      │       ○         │
│ └─ E-Mail-Log einsehen    │  ✓    │    ✓      │       ○         │
└─────────────────────────────────────────────────────────────────┘

✓ = Berechtigung erteilt
○ = Keine Berechtigung
```

### Stellen-Zuweisung UI

```
┌─────────────────────────────────────────────────────────────────┐
│ Stellen-Zuweisung                                                │
├─────────────────────────────────────────────────────────────────┤
│ Benutzer: [Dropdown: Maria Schmidt (Recruiter)     ▼]           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│ Zugewiesene Stellen (3):                                        │
│                                                                  │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ ☑ Senior PHP Developer                        [Entfernen]   │ │
│ │   Berlin · Aktiv seit 15.01.2025                            │ │
│ ├─────────────────────────────────────────────────────────────┤ │
│ │ ☑ UX Designer (m/w/d)                         [Entfernen]   │ │
│ │   Remote · Aktiv seit 20.01.2025                            │ │
│ ├─────────────────────────────────────────────────────────────┤ │
│ │ ☑ Marketing Manager                           [Entfernen]   │ │
│ │   München · Aktiv seit 22.01.2025                           │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│ Verfügbare Stellen:                                             │
│                                                                  │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ ☐ Pflegefachkraft                             [Zuweisen]    │ │
│ │   Hamburg · Aktiv seit 10.01.2025                           │ │
│ ├─────────────────────────────────────────────────────────────┤ │
│ │ ☐ Buchhalter (m/w/d)                          [Zuweisen]    │ │
│ │   Berlin · Entwurf                                          │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│ [Alle zuweisen]                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 10. Migration bestehender Daten

### Migrations-Schritte

```php
/**
 * Migration bei Plugin-Update auf Version mit Benutzerrollen
 */
class UserRolesMigration {

    public function run(): void {
        // 1. Neue Tabelle erstellen
        $this->createJobAssignmentsTable();

        // 2. Custom Rollen registrieren
        RoleManager::register();

        // 3. Bestehende Editors zu Recruitern migrieren (optional)
        $this->migrateEditors();

        // 4. Admins: Alle Jobs zuweisen (für Abwärtskompatibilität)
        // Nicht nötig - Admins haben implizit Zugriff auf alle Jobs
    }

    private function createJobAssignmentsTable(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_user_job_assignments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id         bigint(20) unsigned NOT NULL,
            job_id          bigint(20) unsigned NOT NULL,
            assigned_by     bigint(20) unsigned NOT NULL,
            assigned_at     datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_job (user_id, job_id),
            KEY user_id (user_id),
            KEY job_id (job_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private function migrateEditors(): void {
        // Optional: Editors automatisch zu Recruitern machen
        // Nur wenn gewünscht - kann auch manuell erfolgen
    }
}
```

---

## 11. Testing

### Unit Tests

```php
class CapabilityServiceTest extends WP_UnitTestCase {

    public function test_admin_can_access_all_applications(): void {
        $admin = $this->factory()->user->create( [ 'role' => 'administrator' ] );
        $application = $this->createApplication();

        $service = new CapabilityService();

        $this->assertTrue( $service->canAccessApplication( $admin, $application->id ) );
    }

    public function test_recruiter_can_only_access_assigned_jobs(): void {
        $recruiter = $this->factory()->user->create( [ 'role' => 'rp_recruiter' ] );

        $job1 = $this->createJob();
        $job2 = $this->createJob();

        $app1 = $this->createApplication( $job1 );
        $app2 = $this->createApplication( $job2 );

        // Nur Job 1 zuweisen
        $assignment_service = new JobAssignmentService( new JobAssignmentRepository() );
        $assignment_service->assign( $recruiter, $job1, 1 );

        $service = new CapabilityService();

        $this->assertTrue( $service->canAccessApplication( $recruiter, $app1->id ) );
        $this->assertFalse( $service->canAccessApplication( $recruiter, $app2->id ) );
    }

    public function test_hiring_manager_cannot_edit_applications(): void {
        $manager = $this->factory()->user->create( [ 'role' => 'rp_hiring_manager' ] );

        $this->assertTrue( user_can( $manager, 'rp_view_applications' ) );
        $this->assertFalse( user_can( $manager, 'rp_edit_applications' ) );
    }
}
```

### API Tests

```php
class JobAssignmentControllerTest extends WP_Test_REST_TestCase {

    public function test_admin_can_assign_users(): void {
        $admin = $this->factory()->user->create( [ 'role' => 'administrator' ] );
        $recruiter = $this->factory()->user->create( [ 'role' => 'rp_recruiter' ] );
        $job = $this->createJob();

        wp_set_current_user( $admin );

        $request = new WP_REST_Request( 'POST', '/recruiting/v1/job-assignments' );
        $request->set_body_params( [
            'user_id' => $recruiter,
            'job_id'  => $job,
        ] );

        $response = rest_do_request( $request );

        $this->assertEquals( 201, $response->get_status() );
        $this->assertTrue( $response->get_data()['success'] );
    }

    public function test_recruiter_cannot_assign_users(): void {
        $recruiter = $this->factory()->user->create( [ 'role' => 'rp_recruiter' ] );
        $other_recruiter = $this->factory()->user->create( [ 'role' => 'rp_recruiter' ] );
        $job = $this->createJob();

        wp_set_current_user( $recruiter );

        $request = new WP_REST_Request( 'POST', '/recruiting/v1/job-assignments' );
        $request->set_body_params( [
            'user_id' => $other_recruiter,
            'job_id'  => $job,
        ] );

        $response = rest_do_request( $request );

        $this->assertEquals( 403, $response->get_status() );
    }
}
```

---

## Zusammenfassung

### Implementierungs-Reihenfolge

| Phase | Aufgabe | Priorität |
|-------|---------|-----------|
| 1 | Datenbank-Tabelle `rp_user_job_assignments` | P0 |
| 2 | RoleManager + Custom Rollen registrieren | P0 |
| 3 | CapabilityService + JobAssignmentService | P1 |
| 4 | REST API Controller | P1 |
| 5 | Admin-Menü Capability-Checks anpassen | P1 |
| 6 | ApplicationRepository Filter anpassen | P1 |
| 7 | Admin UI (React + shadcn/ui) | P2 |
| 8 | Migration + Tests | P2 |

### Geschätzter Aufwand

| Komponente | Aufwand |
|------------|---------|
| Datenbank + Migration | ~2h |
| Services (Role, Capability, Assignment) | ~6h |
| REST API Controller | ~4h |
| Admin-Menü Anpassung | ~2h |
| Repository Filter | ~2h |
| React UI | ~8h |
| Tests | ~4h |
| **Gesamt** | **~28h** |

---

*Letzte Aktualisierung: 28. Januar 2025*

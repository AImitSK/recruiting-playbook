# Erweitertes Bewerbermanagement: Technische Spezifikation

> **Pro-Feature: Umfassendes Bewerbermanagement**
> Notizen, Bewertungen, Timeline und Talent-Pool für professionelles Recruiting

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Datenmodell](#3-datenmodell)
4. [REST API Endpunkte](#4-rest-api-endpunkte)
5. [Notizen-System](#5-notizen-system)
6. [Bewertungs-System](#6-bewertungs-system)
7. [Activity Log & Timeline](#7-activity-log--timeline)
8. [Talent-Pool](#8-talent-pool)
9. [Bewerber-Detailseite Integration](#9-bewerber-detailseite-integration)
10. [Kanban-Integration](#10-kanban-integration)
11. [Berechtigungen](#11-berechtigungen)
12. [Testing](#12-testing)

---

## 1. Übersicht

### Zielsetzung

Das erweiterte Bewerbermanagement ermöglicht Recruitern:
- **Notizen** zu jedem Bewerber zu hinterlegen (Team-Kollaboration)
- **Bewertungen** (Sterne) für schnelle Qualitätseinschätzung
- **Activity Log** für lückenlosen Audit-Trail
- **Timeline** für chronologische Übersicht aller Aktivitäten
- **Talent-Pool** für vielversprechende Kandidaten ohne passende Stelle

### Feature-Gating

```php
// Pro-Feature Checks
if ( ! rp_can( 'notes' ) ) {
    rp_require_feature( 'notes', 'Notizen', 'PRO' );
}

if ( ! rp_can( 'ratings' ) ) {
    rp_require_feature( 'ratings', 'Bewertungen', 'PRO' );
}

if ( ! rp_can( 'talent_pool' ) ) {
    rp_require_feature( 'talent_pool', 'Talent-Pool', 'PRO' );
}

// Activity Log ist auch in Free verfügbar (Basis-Einträge)
```

### User Stories

| Als | möchte ich | damit |
|-----|-----------|-------|
| Recruiter | Notizen zu Bewerbern hinterlegen | ich Feedback aus Interviews festhalten kann |
| Recruiter | Bewerber mit Sternen bewerten | ich schnell Top-Kandidaten identifiziere |
| HR-Manager | alle Aktivitäten sehen | ich den Bewerbungsprozess nachvollziehen kann |
| Recruiter | Bewerber im Talent-Pool speichern | ich sie für zukünftige Stellen kontaktieren kann |
| Hiring Manager | Notizen meiner Kollegen lesen | ich informiert ins Interview gehe |

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   ├── Admin/
│   │   └── Pages/
│   │       └── ApplicationDetail.php    # Erweiterte Detailseite
│   │
│   ├── Api/
│   │   ├── NoteController.php           # REST API für Notizen
│   │   ├── RatingController.php         # REST API für Bewertungen
│   │   ├── ActivityController.php       # REST API für Activity Log
│   │   └── TalentPoolController.php     # REST API für Talent-Pool
│   │
│   ├── Services/
│   │   ├── NoteService.php              # Notizen Business Logic
│   │   ├── RatingService.php            # Bewertungen Business Logic
│   │   ├── ActivityService.php          # Activity Log Service
│   │   └── TalentPoolService.php        # Talent-Pool Service
│   │
│   ├── Repositories/
│   │   ├── NoteRepository.php           # Notizen Data Access
│   │   ├── RatingRepository.php         # Bewertungen Data Access
│   │   └── TalentPoolRepository.php     # Talent-Pool Data Access
│   │
│   └── Models/
│       ├── Note.php                     # Notiz Model
│       ├── Rating.php                   # Bewertung Model
│       └── TalentPoolEntry.php          # Talent-Pool Model
│
├── assets/
│   └── src/
│       ├── js/
│       │   └── admin/
│       │       └── applicant/
│       │           ├── index.jsx            # Entry Point
│       │           ├── ApplicantDetail.jsx  # Hauptkomponente
│       │           ├── NotesPanel.jsx       # Notizen-Panel
│       │           ├── NoteEditor.jsx       # Notiz-Editor
│       │           ├── RatingStars.jsx      # Sterne-Bewertung
│       │           ├── Timeline.jsx         # Activity Timeline
│       │           ├── TimelineItem.jsx     # Timeline-Eintrag
│       │           ├── TalentPoolButton.jsx # Talent-Pool Toggle
│       │           └── hooks/
│       │               ├── useNotes.js
│       │               ├── useRating.js
│       │               ├── useTimeline.js
│       │               └── useTalentPool.js
│       │
│       └── css/
│           └── admin-applicant.css          # Komponenten-Styles
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Frontend | React 18 (@wordpress/element) |
| Rich Text Editor | @wordpress/rich-text oder TipTap |
| State Management | React Context + Custom Hooks |
| API-Kommunikation | @wordpress/api-fetch |
| Icons | Dashicons + Custom SVGs |
| Styling | Tailwind CSS (rp- Prefix) |

---

## 3. Datenmodell

### Neue Tabelle: `rp_notes`

```sql
CREATE TABLE {$prefix}rp_notes (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    application_id  bigint(20) unsigned NOT NULL,
    candidate_id    bigint(20) unsigned NOT NULL,
    user_id         bigint(20) unsigned NOT NULL,
    content         text NOT NULL,
    is_private      tinyint(1) DEFAULT 0,
    created_at      datetime NOT NULL,
    updated_at      datetime NOT NULL,
    deleted_at      datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY application_id (application_id),
    KEY candidate_id (candidate_id),
    KEY user_id (user_id),
    KEY created_at (created_at)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `application_id` | bigint | FK zu rp_applications (optional, NULL = Kandidaten-Notiz) |
| `candidate_id` | bigint | FK zu rp_candidates |
| `user_id` | bigint | FK zu wp_users (Autor) |
| `content` | text | Notiz-Inhalt (HTML erlaubt, sanitized) |
| `is_private` | tinyint | 1 = nur für Autor sichtbar |
| `created_at` | datetime | Erstellungsdatum |
| `updated_at` | datetime | Letzte Änderung |
| `deleted_at` | datetime | Soft Delete |

### Neue Tabelle: `rp_ratings`

```sql
CREATE TABLE {$prefix}rp_ratings (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    application_id  bigint(20) unsigned NOT NULL,
    user_id         bigint(20) unsigned NOT NULL,
    rating          tinyint(1) unsigned NOT NULL,
    category        varchar(50) DEFAULT 'overall',
    created_at      datetime NOT NULL,
    updated_at      datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY user_application (user_id, application_id, category),
    KEY application_id (application_id),
    KEY rating (rating)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `application_id` | bigint | FK zu rp_applications |
| `user_id` | bigint | FK zu wp_users (Bewerter) |
| `rating` | tinyint | 1-5 Sterne |
| `category` | varchar | Rating-Kategorie (overall, skills, culture_fit, experience) |
| `created_at` | datetime | Erstellungsdatum |
| `updated_at` | datetime | Letzte Änderung |

### Neue Tabelle: `rp_talent_pool`

```sql
CREATE TABLE {$prefix}rp_talent_pool (
    id              bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    candidate_id    bigint(20) unsigned NOT NULL,
    added_by        bigint(20) unsigned NOT NULL,
    reason          text,
    tags            varchar(255) DEFAULT NULL,
    expires_at      datetime NOT NULL,
    reminder_sent   tinyint(1) DEFAULT 0,
    created_at      datetime NOT NULL,
    updated_at      datetime NOT NULL,
    deleted_at      datetime DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY candidate_id (candidate_id),
    KEY added_by (added_by),
    KEY expires_at (expires_at),
    KEY tags (tags)
) {$charset_collate};
```

#### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | bigint | Primärschlüssel |
| `candidate_id` | bigint | FK zu rp_candidates (unique) |
| `added_by` | bigint | FK zu wp_users |
| `reason` | text | Grund für Aufnahme |
| `tags` | varchar | Komma-separierte Tags (z.B. "php,senior,remote") |
| `expires_at` | datetime | Ablaufdatum (Standard: created_at + 24 Monate) |
| `reminder_sent` | tinyint | 1 = Erinnerung vor Ablauf gesendet |
| `created_at` | datetime | Erstellungsdatum |
| `updated_at` | datetime | Letzte Änderung |
| `deleted_at` | datetime | Soft Delete |

### Erweiterung: `rp_activity_log`

Die bestehende Tabelle wird für detaillierteres Logging erweitert:

```sql
ALTER TABLE {$prefix}rp_activity_log
ADD COLUMN meta longtext DEFAULT NULL AFTER message,
ADD COLUMN ip_address varchar(45) DEFAULT NULL AFTER meta;
```

#### Activity Types

| Type | Beschreibung | Meta-Beispiel |
|------|--------------|---------------|
| `application_created` | Neue Bewerbung | `{"job_id": 123}` |
| `status_changed` | Status geändert | `{"from": "new", "to": "screening"}` |
| `note_added` | Notiz hinzugefügt | `{"note_id": 456, "preview": "..."}` |
| `note_updated` | Notiz bearbeitet | `{"note_id": 456}` |
| `note_deleted` | Notiz gelöscht | `{"note_id": 456}` |
| `rating_added` | Bewertung abgegeben | `{"rating": 4, "category": "overall"}` |
| `rating_updated` | Bewertung geändert | `{"from": 3, "to": 4}` |
| `email_sent` | E-Mail versendet | `{"template": "rejection", "to": "..."}` |
| `document_viewed` | Dokument angesehen | `{"document_id": 789}` |
| `document_downloaded` | Dokument heruntergeladen | `{"document_id": 789}` |
| `talent_pool_added` | Zum Talent-Pool hinzugefügt | `{"reason": "..."}` |
| `talent_pool_removed` | Aus Talent-Pool entfernt | `{}` |

---

## 4. REST API Endpunkte

### Notes API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/applications/{id}/notes` | Notizen einer Bewerbung |
| POST | `/recruiting/v1/applications/{id}/notes` | Neue Notiz erstellen |
| PATCH | `/recruiting/v1/notes/{id}` | Notiz bearbeiten |
| DELETE | `/recruiting/v1/notes/{id}` | Notiz löschen (Soft Delete) |
| GET | `/recruiting/v1/candidates/{id}/notes` | Alle Notizen eines Kandidaten |

#### POST /applications/{id}/notes

```php
register_rest_route(
    $this->namespace,
    '/applications/(?P<id>\d+)/notes',
    [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [ $this, 'create_note' ],
        'permission_callback' => [ $this, 'create_note_permissions_check' ],
        'args'                => [
            'content' => [
                'description' => __( 'Notiz-Inhalt', 'recruiting-playbook' ),
                'type'        => 'string',
                'required'    => true,
                'sanitize_callback' => 'wp_kses_post',
            ],
            'is_private' => [
                'description' => __( 'Private Notiz', 'recruiting-playbook' ),
                'type'        => 'boolean',
                'default'     => false,
            ],
        ],
    ]
);
```

#### Response Schema

```json
{
    "id": 123,
    "application_id": 456,
    "candidate_id": 789,
    "content": "<p>Sehr gutes Interview...</p>",
    "is_private": false,
    "author": {
        "id": 1,
        "name": "Max Recruiter",
        "avatar": "https://..."
    },
    "created_at": "2025-01-25T10:30:00Z",
    "updated_at": "2025-01-25T10:30:00Z",
    "can_edit": true,
    "can_delete": true
}
```

### Ratings API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/applications/{id}/ratings` | Alle Bewertungen |
| POST | `/recruiting/v1/applications/{id}/ratings` | Bewertung abgeben/aktualisieren |
| GET | `/recruiting/v1/applications/{id}/rating-summary` | Durchschnitt & Verteilung |

#### POST /applications/{id}/ratings

```php
register_rest_route(
    $this->namespace,
    '/applications/(?P<id>\d+)/ratings',
    [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [ $this, 'upsert_rating' ],
        'permission_callback' => [ $this, 'rate_permissions_check' ],
        'args'                => [
            'rating' => [
                'description' => __( 'Bewertung 1-5', 'recruiting-playbook' ),
                'type'        => 'integer',
                'required'    => true,
                'minimum'     => 1,
                'maximum'     => 5,
            ],
            'category' => [
                'description' => __( 'Bewertungs-Kategorie', 'recruiting-playbook' ),
                'type'        => 'string',
                'default'     => 'overall',
                'enum'        => [ 'overall', 'skills', 'culture_fit', 'experience' ],
            ],
        ],
    ]
);
```

#### Rating Summary Response

```json
{
    "average": 4.2,
    "count": 5,
    "distribution": {
        "1": 0,
        "2": 0,
        "3": 1,
        "4": 2,
        "5": 2
    },
    "by_category": {
        "overall": { "average": 4.2, "count": 5 },
        "skills": { "average": 4.5, "count": 3 },
        "culture_fit": { "average": 4.0, "count": 2 },
        "experience": { "average": 3.8, "count": 2 }
    },
    "user_rating": {
        "overall": 4,
        "skills": 5
    }
}
```

### Activity/Timeline API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/applications/{id}/timeline` | Timeline einer Bewerbung |
| GET | `/recruiting/v1/candidates/{id}/timeline` | Timeline eines Kandidaten |

#### GET /applications/{id}/timeline

```php
register_rest_route(
    $this->namespace,
    '/applications/(?P<id>\d+)/timeline',
    [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ $this, 'get_timeline' ],
        'permission_callback' => [ $this, 'view_application_permissions_check' ],
        'args'                => [
            'per_page' => [
                'description' => __( 'Einträge pro Seite', 'recruiting-playbook' ),
                'type'        => 'integer',
                'default'     => 50,
                'maximum'     => 100,
            ],
            'page' => [
                'description' => __( 'Seite', 'recruiting-playbook' ),
                'type'        => 'integer',
                'default'     => 1,
            ],
            'types' => [
                'description' => __( 'Activity-Types filtern', 'recruiting-playbook' ),
                'type'        => 'array',
                'items'       => [ 'type' => 'string' ],
            ],
        ],
    ]
);
```

#### Timeline Response

```json
{
    "items": [
        {
            "id": 123,
            "type": "status_changed",
            "message": "Status von \"Neu\" auf \"In Prüfung\" geändert",
            "meta": {
                "from": "new",
                "to": "screening"
            },
            "user": {
                "id": 1,
                "name": "Max Recruiter",
                "avatar": "https://..."
            },
            "created_at": "2025-01-25T10:30:00Z",
            "icon": "dashicons-update",
            "color": "#dba617"
        },
        {
            "id": 124,
            "type": "note_added",
            "message": "Notiz hinzugefügt",
            "meta": {
                "note_id": 456,
                "preview": "Sehr gutes Interview, Kandidat hat..."
            },
            "user": {
                "id": 1,
                "name": "Max Recruiter",
                "avatar": "https://..."
            },
            "created_at": "2025-01-25T11:00:00Z",
            "icon": "dashicons-edit",
            "color": "#2271b1"
        }
    ],
    "total": 15,
    "pages": 1
}
```

### Talent-Pool API

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/talent-pool` | Alle Talent-Pool Einträge |
| POST | `/recruiting/v1/talent-pool` | Kandidat hinzufügen |
| DELETE | `/recruiting/v1/talent-pool/{candidate_id}` | Kandidat entfernen |
| PATCH | `/recruiting/v1/talent-pool/{candidate_id}` | Eintrag aktualisieren |

#### POST /talent-pool

```php
register_rest_route(
    $this->namespace,
    '/talent-pool',
    [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [ $this, 'add_to_talent_pool' ],
        'permission_callback' => [ $this, 'manage_talent_pool_permissions_check' ],
        'args'                => [
            'candidate_id' => [
                'description' => __( 'Kandidaten-ID', 'recruiting-playbook' ),
                'type'        => 'integer',
                'required'    => true,
            ],
            'reason' => [
                'description' => __( 'Grund für Aufnahme', 'recruiting-playbook' ),
                'type'        => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            'tags' => [
                'description' => __( 'Tags (komma-separiert)', 'recruiting-playbook' ),
                'type'        => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'expires_at' => [
                'description' => __( 'Ablaufdatum', 'recruiting-playbook' ),
                'type'        => 'string',
                'format'      => 'date-time',
            ],
        ],
    ]
);
```

---

## 5. Notizen-System

### NoteService.php

```php
<?php
/**
 * Notizen Service
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use RecruitingPlaybook\Repositories\NoteRepository;

class NoteService {

    private NoteRepository $repository;

    public function __construct( NoteRepository $repository ) {
        $this->repository = $repository;
    }

    /**
     * Notiz erstellen
     *
     * @param int    $application_id Bewerbungs-ID.
     * @param string $content        Notiz-Inhalt.
     * @param bool   $is_private     Private Notiz.
     * @return array|WP_Error
     */
    public function create( int $application_id, string $content, bool $is_private = false ) {
        // Bewerbung laden für candidate_id
        $application = ( new ApplicationRepository() )->find( $application_id );
        if ( ! $application ) {
            return new \WP_Error( 'not_found', __( 'Bewerbung nicht gefunden', 'recruiting-playbook' ) );
        }

        $note_id = $this->repository->create( [
            'application_id' => $application_id,
            'candidate_id'   => $application['candidate_id'],
            'user_id'        => get_current_user_id(),
            'content'        => wp_kses_post( $content ),
            'is_private'     => $is_private ? 1 : 0,
            'created_at'     => current_time( 'mysql' ),
            'updated_at'     => current_time( 'mysql' ),
        ] );

        if ( ! $note_id ) {
            return new \WP_Error( 'create_failed', __( 'Notiz konnte nicht erstellt werden', 'recruiting-playbook' ) );
        }

        // Activity Log
        do_action( 'rp_activity_log', [
            'application_id' => $application_id,
            'type'           => 'note_added',
            'message'        => __( 'Notiz hinzugefügt', 'recruiting-playbook' ),
            'meta'           => [
                'note_id' => $note_id,
                'preview' => wp_trim_words( wp_strip_all_tags( $content ), 20 ),
            ],
        ] );

        return $this->repository->find( $note_id );
    }

    /**
     * Notizen für Bewerbung laden
     *
     * @param int $application_id Bewerbungs-ID.
     * @return array
     */
    public function get_for_application( int $application_id ): array {
        $current_user_id = get_current_user_id();

        $notes = $this->repository->find_by_application( $application_id );

        // Private Notizen filtern (nur eigene anzeigen)
        return array_filter( $notes, function( $note ) use ( $current_user_id ) {
            if ( $note['is_private'] && $note['user_id'] !== $current_user_id ) {
                return false;
            }
            return true;
        } );
    }

    /**
     * Notiz aktualisieren
     *
     * @param int    $note_id Notiz-ID.
     * @param string $content Neuer Inhalt.
     * @return array|WP_Error
     */
    public function update( int $note_id, string $content ) {
        $note = $this->repository->find( $note_id );

        if ( ! $note ) {
            return new \WP_Error( 'not_found', __( 'Notiz nicht gefunden', 'recruiting-playbook' ) );
        }

        // Nur eigene Notizen bearbeiten (oder Admin)
        if ( $note['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'forbidden', __( 'Keine Berechtigung', 'recruiting-playbook' ) );
        }

        $this->repository->update( $note_id, [
            'content'    => wp_kses_post( $content ),
            'updated_at' => current_time( 'mysql' ),
        ] );

        // Activity Log
        do_action( 'rp_activity_log', [
            'application_id' => $note['application_id'],
            'type'           => 'note_updated',
            'message'        => __( 'Notiz bearbeitet', 'recruiting-playbook' ),
            'meta'           => [ 'note_id' => $note_id ],
        ] );

        return $this->repository->find( $note_id );
    }

    /**
     * Notiz löschen (Soft Delete)
     *
     * @param int $note_id Notiz-ID.
     * @return bool|WP_Error
     */
    public function delete( int $note_id ) {
        $note = $this->repository->find( $note_id );

        if ( ! $note ) {
            return new \WP_Error( 'not_found', __( 'Notiz nicht gefunden', 'recruiting-playbook' ) );
        }

        // Nur eigene Notizen löschen (oder Admin)
        if ( $note['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'forbidden', __( 'Keine Berechtigung', 'recruiting-playbook' ) );
        }

        $this->repository->soft_delete( $note_id );

        // Activity Log
        do_action( 'rp_activity_log', [
            'application_id' => $note['application_id'],
            'type'           => 'note_deleted',
            'message'        => __( 'Notiz gelöscht', 'recruiting-playbook' ),
            'meta'           => [ 'note_id' => $note_id ],
        ] );

        return true;
    }
}
```

### NotesPanel.jsx

```jsx
/**
 * Notizen-Panel Komponente
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useNotes } from './hooks/useNotes';
import { NoteEditor } from './NoteEditor';

export function NotesPanel({ applicationId }) {
    const {
        notes,
        loading,
        error,
        addNote,
        updateNote,
        deleteNote,
    } = useNotes(applicationId);

    const [isAdding, setIsAdding] = useState(false);
    const [editingId, setEditingId] = useState(null);

    const handleAddNote = async (content, isPrivate) => {
        await addNote(content, isPrivate);
        setIsAdding(false);
    };

    const handleUpdateNote = async (noteId, content) => {
        await updateNote(noteId, content);
        setEditingId(null);
    };

    if (loading) {
        return (
            <div className="rp-notes-panel rp-loading">
                <span className="spinner is-active" />
            </div>
        );
    }

    return (
        <div className="rp-notes-panel">
            <div className="rp-notes-header">
                <h3>{__('Notizen', 'recruiting-playbook')}</h3>
                <span className="rp-notes-count">{notes.length}</span>
                <button
                    className="button button-primary"
                    onClick={() => setIsAdding(true)}
                    disabled={isAdding}
                >
                    <span className="dashicons dashicons-plus-alt2" />
                    {__('Neue Notiz', 'recruiting-playbook')}
                </button>
            </div>

            {error && (
                <div className="notice notice-error">
                    <p>{error}</p>
                </div>
            )}

            {isAdding && (
                <NoteEditor
                    onSave={handleAddNote}
                    onCancel={() => setIsAdding(false)}
                />
            )}

            <div className="rp-notes-list">
                {notes.length === 0 && !isAdding ? (
                    <div className="rp-notes-empty">
                        <span className="dashicons dashicons-format-aside" />
                        <p>{__('Noch keine Notizen vorhanden', 'recruiting-playbook')}</p>
                    </div>
                ) : (
                    notes.map(note => (
                        <div
                            key={note.id}
                            className={`rp-note ${note.is_private ? 'is-private' : ''}`}
                        >
                            {editingId === note.id ? (
                                <NoteEditor
                                    initialContent={note.content}
                                    onSave={(content) => handleUpdateNote(note.id, content)}
                                    onCancel={() => setEditingId(null)}
                                />
                            ) : (
                                <>
                                    <div className="rp-note-header">
                                        <img
                                            src={note.author.avatar}
                                            alt={note.author.name}
                                            className="rp-note-avatar"
                                        />
                                        <div className="rp-note-meta">
                                            <span className="rp-note-author">
                                                {note.author.name}
                                            </span>
                                            <span className="rp-note-date">
                                                {formatDate(note.created_at)}
                                            </span>
                                            {note.is_private && (
                                                <span
                                                    className="rp-note-private"
                                                    title={__('Private Notiz', 'recruiting-playbook')}
                                                >
                                                    <span className="dashicons dashicons-lock" />
                                                </span>
                                            )}
                                        </div>
                                        {note.can_edit && (
                                            <div className="rp-note-actions">
                                                <button
                                                    onClick={() => setEditingId(note.id)}
                                                    className="rp-note-edit"
                                                    title={__('Bearbeiten', 'recruiting-playbook')}
                                                >
                                                    <span className="dashicons dashicons-edit" />
                                                </button>
                                                {note.can_delete && (
                                                    <button
                                                        onClick={() => {
                                                            if (confirm(__('Notiz wirklich löschen?', 'recruiting-playbook'))) {
                                                                deleteNote(note.id);
                                                            }
                                                        }}
                                                        className="rp-note-delete"
                                                        title={__('Löschen', 'recruiting-playbook')}
                                                    >
                                                        <span className="dashicons dashicons-trash" />
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                    <div
                                        className="rp-note-content"
                                        dangerouslySetInnerHTML={{ __html: note.content }}
                                    />
                                </>
                            )}
                        </div>
                    ))
                )}
            </div>
        </div>
    );
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return __('Gerade eben', 'recruiting-playbook');
    if (minutes < 60) return sprintf(__('vor %d Min.', 'recruiting-playbook'), minutes);
    if (hours < 24) return sprintf(__('vor %d Std.', 'recruiting-playbook'), hours);
    if (days < 7) return sprintf(__('vor %d Tagen', 'recruiting-playbook'), days);

    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
```

### NoteEditor.jsx

```jsx
/**
 * Notiz-Editor Komponente
 */
import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export function NoteEditor({
    initialContent = '',
    onSave,
    onCancel,
    showPrivateOption = true,
}) {
    const [content, setContent] = useState(initialContent);
    const [isPrivate, setIsPrivate] = useState(false);
    const [saving, setSaving] = useState(false);
    const textareaRef = useRef(null);

    useEffect(() => {
        textareaRef.current?.focus();
    }, []);

    const handleSave = async () => {
        if (!content.trim()) return;

        setSaving(true);
        try {
            await onSave(content, isPrivate);
        } finally {
            setSaving(false);
        }
    };

    const handleKeyDown = (e) => {
        // Cmd/Ctrl + Enter zum Speichern
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            handleSave();
        }
        // Escape zum Abbrechen
        if (e.key === 'Escape') {
            onCancel();
        }
    };

    return (
        <div className="rp-note-editor">
            <textarea
                ref={textareaRef}
                className="rp-note-textarea"
                value={content}
                onChange={(e) => setContent(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder={__('Notiz eingeben...', 'recruiting-playbook')}
                rows={4}
                disabled={saving}
            />

            <div className="rp-note-editor-footer">
                {showPrivateOption && (
                    <label className="rp-note-private-toggle">
                        <input
                            type="checkbox"
                            checked={isPrivate}
                            onChange={(e) => setIsPrivate(e.target.checked)}
                            disabled={saving}
                        />
                        <span className="dashicons dashicons-lock" />
                        {__('Privat', 'recruiting-playbook')}
                    </label>
                )}

                <div className="rp-note-editor-actions">
                    <button
                        className="button"
                        onClick={onCancel}
                        disabled={saving}
                    >
                        {__('Abbrechen', 'recruiting-playbook')}
                    </button>
                    <button
                        className="button button-primary"
                        onClick={handleSave}
                        disabled={!content.trim() || saving}
                    >
                        {saving ? (
                            <span className="spinner is-active" />
                        ) : (
                            __('Speichern', 'recruiting-playbook')
                        )}
                    </button>
                </div>

                <span className="rp-note-editor-hint">
                    {__('Strg+Enter zum Speichern', 'recruiting-playbook')}
                </span>
            </div>
        </div>
    );
}
```

---

## 6. Bewertungs-System

### Bewertungs-Kategorien

| Kategorie | Beschreibung | Icon |
|-----------|--------------|------|
| `overall` | Gesamteindruck | ⭐ |
| `skills` | Fachliche Kompetenz | 💼 |
| `culture_fit` | Kulturelle Passung | 🤝 |
| `experience` | Relevante Erfahrung | 📊 |

### RatingStars.jsx

```jsx
/**
 * Sterne-Bewertung Komponente
 */
import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const CATEGORIES = {
    overall: {
        label: __('Gesamteindruck', 'recruiting-playbook'),
        icon: 'star-filled',
    },
    skills: {
        label: __('Fachkompetenz', 'recruiting-playbook'),
        icon: 'portfolio',
    },
    culture_fit: {
        label: __('Kulturelle Passung', 'recruiting-playbook'),
        icon: 'groups',
    },
    experience: {
        label: __('Erfahrung', 'recruiting-playbook'),
        icon: 'chart-line',
    },
};

export function RatingStars({
    applicationId,
    summary,
    userRatings,
    onRate,
    readonly = false,
    showCategories = false,
}) {
    const [hoverRating, setHoverRating] = useState({});
    const [activeCategory, setActiveCategory] = useState('overall');

    const handleRate = useCallback(async (category, rating) => {
        if (readonly) return;
        await onRate(category, rating);
    }, [onRate, readonly]);

    const renderStars = (category, currentRating, averageRating) => {
        const displayRating = hoverRating[category] ?? currentRating ?? 0;

        return (
            <div
                className="rp-rating-stars"
                onMouseLeave={() => setHoverRating(prev => ({ ...prev, [category]: null }))}
            >
                {[1, 2, 3, 4, 5].map(star => (
                    <button
                        key={star}
                        className={`rp-rating-star ${star <= displayRating ? 'is-filled' : ''} ${star <= averageRating ? 'is-average' : ''}`}
                        onClick={() => handleRate(category, star)}
                        onMouseEnter={() => !readonly && setHoverRating(prev => ({ ...prev, [category]: star }))}
                        disabled={readonly}
                        aria-label={sprintf(
                            __('%d von 5 Sternen', 'recruiting-playbook'),
                            star
                        )}
                    >
                        <span className={`dashicons dashicons-star-${star <= displayRating ? 'filled' : 'empty'}`} />
                    </button>
                ))}

                {averageRating > 0 && (
                    <span className="rp-rating-average">
                        {averageRating.toFixed(1)}
                        <span className="rp-rating-count">
                            ({summary?.count || 0})
                        </span>
                    </span>
                )}
            </div>
        );
    };

    // Einfache Ansicht (nur Gesamt)
    if (!showCategories) {
        return (
            <div className="rp-rating-simple">
                {renderStars(
                    'overall',
                    userRatings?.overall,
                    summary?.average
                )}
            </div>
        );
    }

    // Erweiterte Ansicht (alle Kategorien)
    return (
        <div className="rp-rating-detailed">
            <div className="rp-rating-tabs">
                {Object.entries(CATEGORIES).map(([key, config]) => (
                    <button
                        key={key}
                        className={`rp-rating-tab ${activeCategory === key ? 'is-active' : ''}`}
                        onClick={() => setActiveCategory(key)}
                    >
                        <span className={`dashicons dashicons-${config.icon}`} />
                        {config.label}
                    </button>
                ))}
            </div>

            <div className="rp-rating-content">
                {Object.entries(CATEGORIES).map(([key, config]) => (
                    <div
                        key={key}
                        className={`rp-rating-category ${activeCategory === key ? 'is-visible' : ''}`}
                    >
                        <div className="rp-rating-category-header">
                            <span className={`dashicons dashicons-${config.icon}`} />
                            <span className="rp-rating-category-label">
                                {config.label}
                            </span>
                        </div>
                        {renderStars(
                            key,
                            userRatings?.[key],
                            summary?.by_category?.[key]?.average
                        )}
                    </div>
                ))}
            </div>

            {/* Verteilung */}
            {summary?.distribution && (
                <div className="rp-rating-distribution">
                    <h4>{__('Verteilung', 'recruiting-playbook')}</h4>
                    {[5, 4, 3, 2, 1].map(star => {
                        const count = summary.distribution[star] || 0;
                        const percentage = summary.count > 0
                            ? (count / summary.count) * 100
                            : 0;

                        return (
                            <div key={star} className="rp-rating-bar">
                                <span className="rp-rating-bar-label">
                                    {star} <span className="dashicons dashicons-star-filled" />
                                </span>
                                <div className="rp-rating-bar-track">
                                    <div
                                        className="rp-rating-bar-fill"
                                        style={{ width: `${percentage}%` }}
                                    />
                                </div>
                                <span className="rp-rating-bar-count">
                                    {count}
                                </span>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
```

### Kanban-Card mit Rating

```jsx
// In KanbanCard.jsx - Rating-Anzeige hinzufügen
{application.average_rating > 0 && (
    <div className="rp-kanban-card-rating">
        <span className="dashicons dashicons-star-filled" />
        <span className="rp-kanban-card-rating-value">
            {application.average_rating.toFixed(1)}
        </span>
    </div>
)}
```

---

## 7. Activity Log & Timeline

### ActivityService.php

```php
<?php
/**
 * Activity Log Service
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

class ActivityService {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'rp_activity_log';
    }

    /**
     * Activity-Eintrag erstellen
     *
     * @param array $data Activity-Daten.
     * @return int|false
     */
    public function log( array $data ) {
        global $wpdb;

        $defaults = [
            'user_id'        => get_current_user_id(),
            'created_at'     => current_time( 'mysql' ),
            'ip_address'     => $this->get_client_ip(),
        ];

        $data = wp_parse_args( $data, $defaults );

        // Meta als JSON speichern
        if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
            $data['meta'] = wp_json_encode( $data['meta'] );
        }

        return $wpdb->insert( $this->table, $data );
    }

    /**
     * Timeline für Bewerbung laden
     *
     * @param int   $application_id Bewerbungs-ID.
     * @param array $args           Query-Argumente.
     * @return array
     */
    public function get_timeline( int $application_id, array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'per_page' => 50,
            'page'     => 1,
            'types'    => [],
        ];

        $args   = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        // Base Query
        $where = $wpdb->prepare( 'WHERE application_id = %d', $application_id );

        // Type-Filter
        if ( ! empty( $args['types'] ) ) {
            $placeholders = implode( ', ', array_fill( 0, count( $args['types'] ), '%s' ) );
            $where       .= $wpdb->prepare( " AND type IN ($placeholders)", $args['types'] );
        }

        // Total Count
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} {$where}" );

        // Items laden
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table}
                {$where}
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d",
                $args['per_page'],
                $offset
            ),
            ARRAY_A
        );

        // Items anreichern
        $enriched_items = array_map( [ $this, 'enrich_timeline_item' ], $items );

        return [
            'items' => $enriched_items,
            'total' => (int) $total,
            'pages' => ceil( $total / $args['per_page'] ),
        ];
    }

    /**
     * Timeline-Item anreichern
     *
     * @param array $item Raw-Item.
     * @return array
     */
    private function enrich_timeline_item( array $item ): array {
        // Meta parsen
        if ( ! empty( $item['meta'] ) ) {
            $item['meta'] = json_decode( $item['meta'], true );
        }

        // User-Daten
        $user = get_userdata( $item['user_id'] );
        $item['user'] = $user ? [
            'id'     => $user->ID,
            'name'   => $user->display_name,
            'avatar' => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
        ] : null;

        // Icon und Farbe basierend auf Type
        $type_config       = $this->get_type_config( $item['type'] );
        $item['icon']      = $type_config['icon'];
        $item['color']     = $type_config['color'];
        $item['category']  = $type_config['category'];

        return $item;
    }

    /**
     * Type-Konfiguration
     *
     * @param string $type Activity-Type.
     * @return array
     */
    private function get_type_config( string $type ): array {
        $configs = [
            'application_created' => [
                'icon'     => 'dashicons-plus-alt',
                'color'    => '#00a32a',
                'category' => 'application',
            ],
            'status_changed' => [
                'icon'     => 'dashicons-update',
                'color'    => '#dba617',
                'category' => 'status',
            ],
            'note_added' => [
                'icon'     => 'dashicons-edit',
                'color'    => '#2271b1',
                'category' => 'note',
            ],
            'note_updated' => [
                'icon'     => 'dashicons-edit',
                'color'    => '#2271b1',
                'category' => 'note',
            ],
            'note_deleted' => [
                'icon'     => 'dashicons-trash',
                'color'    => '#d63638',
                'category' => 'note',
            ],
            'rating_added' => [
                'icon'     => 'dashicons-star-filled',
                'color'    => '#f0b849',
                'category' => 'rating',
            ],
            'rating_updated' => [
                'icon'     => 'dashicons-star-half',
                'color'    => '#f0b849',
                'category' => 'rating',
            ],
            'email_sent' => [
                'icon'     => 'dashicons-email-alt',
                'color'    => '#9b59b6',
                'category' => 'email',
            ],
            'document_viewed' => [
                'icon'     => 'dashicons-visibility',
                'color'    => '#787c82',
                'category' => 'document',
            ],
            'document_downloaded' => [
                'icon'     => 'dashicons-download',
                'color'    => '#787c82',
                'category' => 'document',
            ],
            'talent_pool_added' => [
                'icon'     => 'dashicons-groups',
                'color'    => '#1e8cbe',
                'category' => 'talent_pool',
            ],
            'talent_pool_removed' => [
                'icon'     => 'dashicons-dismiss',
                'color'    => '#d63638',
                'category' => 'talent_pool',
            ],
        ];

        return $configs[ $type ] ?? [
            'icon'     => 'dashicons-info',
            'color'    => '#787c82',
            'category' => 'other',
        ];
    }

    /**
     * Client-IP ermitteln
     *
     * @return string
     */
    private function get_client_ip(): string {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // Bei mehreren IPs (X-Forwarded-For) erste nehmen
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '';
    }
}
```

### Timeline.jsx

```jsx
/**
 * Activity Timeline Komponente
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useTimeline } from './hooks/useTimeline';
import { TimelineItem } from './TimelineItem';

const CATEGORY_FILTERS = [
    { id: 'all', label: __('Alle', 'recruiting-playbook') },
    { id: 'status', label: __('Status', 'recruiting-playbook') },
    { id: 'note', label: __('Notizen', 'recruiting-playbook') },
    { id: 'rating', label: __('Bewertungen', 'recruiting-playbook') },
    { id: 'email', label: __('E-Mails', 'recruiting-playbook') },
    { id: 'document', label: __('Dokumente', 'recruiting-playbook') },
];

export function Timeline({ applicationId }) {
    const [filter, setFilter] = useState('all');
    const {
        items,
        loading,
        error,
        hasMore,
        loadMore,
        refresh,
    } = useTimeline(applicationId, filter);

    // Gruppieren nach Datum
    const groupedItems = groupByDate(items);

    if (loading && items.length === 0) {
        return (
            <div className="rp-timeline rp-loading">
                <span className="spinner is-active" />
            </div>
        );
    }

    return (
        <div className="rp-timeline">
            <div className="rp-timeline-header">
                <h3>{__('Verlauf', 'recruiting-playbook')}</h3>
                <button
                    className="rp-timeline-refresh"
                    onClick={refresh}
                    title={__('Aktualisieren', 'recruiting-playbook')}
                >
                    <span className="dashicons dashicons-update" />
                </button>
            </div>

            <div className="rp-timeline-filters">
                {CATEGORY_FILTERS.map(cat => (
                    <button
                        key={cat.id}
                        className={`rp-timeline-filter ${filter === cat.id ? 'is-active' : ''}`}
                        onClick={() => setFilter(cat.id)}
                    >
                        {cat.label}
                    </button>
                ))}
            </div>

            {error && (
                <div className="notice notice-error">
                    <p>{error}</p>
                </div>
            )}

            <div className="rp-timeline-content">
                {Object.entries(groupedItems).map(([date, dateItems]) => (
                    <div key={date} className="rp-timeline-group">
                        <div className="rp-timeline-date">
                            {formatDateHeader(date)}
                        </div>
                        <div className="rp-timeline-items">
                            {dateItems.map(item => (
                                <TimelineItem key={item.id} item={item} />
                            ))}
                        </div>
                    </div>
                ))}

                {items.length === 0 && (
                    <div className="rp-timeline-empty">
                        <span className="dashicons dashicons-clock" />
                        <p>{__('Noch keine Aktivitäten', 'recruiting-playbook')}</p>
                    </div>
                )}

                {hasMore && (
                    <button
                        className="rp-timeline-load-more button"
                        onClick={loadMore}
                        disabled={loading}
                    >
                        {loading ? (
                            <span className="spinner is-active" />
                        ) : (
                            __('Mehr laden', 'recruiting-playbook')
                        )}
                    </button>
                )}
            </div>
        </div>
    );
}

function groupByDate(items) {
    return items.reduce((groups, item) => {
        const date = item.created_at.split('T')[0];
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(item);
        return groups;
    }, {});
}

function formatDateHeader(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (dateString === today.toISOString().split('T')[0]) {
        return __('Heute', 'recruiting-playbook');
    }
    if (dateString === yesterday.toISOString().split('T')[0]) {
        return __('Gestern', 'recruiting-playbook');
    }

    return date.toLocaleDateString('de-DE', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
```

### TimelineItem.jsx

```jsx
/**
 * Timeline-Eintrag Komponente
 */
import { __ } from '@wordpress/i18n';

export function TimelineItem({ item }) {
    const time = new Date(item.created_at).toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
    });

    return (
        <div
            className="rp-timeline-item"
            style={{ '--item-color': item.color }}
        >
            <div className="rp-timeline-item-icon">
                <span className={`dashicons ${item.icon}`} />
            </div>

            <div className="rp-timeline-item-content">
                <div className="rp-timeline-item-header">
                    {item.user && (
                        <img
                            src={item.user.avatar}
                            alt={item.user.name}
                            className="rp-timeline-item-avatar"
                        />
                    )}
                    <span className="rp-timeline-item-message">
                        {item.user && (
                            <strong>{item.user.name}</strong>
                        )}
                        {' '}
                        {item.message}
                    </span>
                    <span className="rp-timeline-item-time">
                        {time}
                    </span>
                </div>

                {/* Kontextabhängige Details */}
                {item.type === 'status_changed' && item.meta && (
                    <div className="rp-timeline-item-detail rp-timeline-status-change">
                        <span className="rp-status-badge" data-status={item.meta.from}>
                            {getStatusLabel(item.meta.from)}
                        </span>
                        <span className="dashicons dashicons-arrow-right-alt" />
                        <span className="rp-status-badge" data-status={item.meta.to}>
                            {getStatusLabel(item.meta.to)}
                        </span>
                    </div>
                )}

                {item.type === 'note_added' && item.meta?.preview && (
                    <div className="rp-timeline-item-detail rp-timeline-note-preview">
                        <span className="dashicons dashicons-format-quote" />
                        {item.meta.preview}
                    </div>
                )}

                {item.type === 'rating_added' && item.meta && (
                    <div className="rp-timeline-item-detail rp-timeline-rating">
                        {[1, 2, 3, 4, 5].map(star => (
                            <span
                                key={star}
                                className={`dashicons dashicons-star-${star <= item.meta.rating ? 'filled' : 'empty'}`}
                            />
                        ))}
                    </div>
                )}

                {item.type === 'email_sent' && item.meta && (
                    <div className="rp-timeline-item-detail rp-timeline-email">
                        <span className="rp-email-template">
                            {item.meta.template}
                        </span>
                        <span className="rp-email-to">
                            → {item.meta.to}
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}

function getStatusLabel(status) {
    const labels = window.rpApplicant?.statusLabels || {
        new: __('Neu', 'recruiting-playbook'),
        screening: __('In Prüfung', 'recruiting-playbook'),
        interview: __('Interview', 'recruiting-playbook'),
        offer: __('Angebot', 'recruiting-playbook'),
        hired: __('Eingestellt', 'recruiting-playbook'),
        rejected: __('Abgelehnt', 'recruiting-playbook'),
        withdrawn: __('Zurückgezogen', 'recruiting-playbook'),
    };
    return labels[status] || status;
}
```

---

## 8. Talent-Pool

### Konzept

Der Talent-Pool speichert vielversprechende Kandidaten für zukünftige Stellen:

```
┌─────────────────────────────────────────────────────────────────────┐
│                         TALENT-POOL                                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │ Max Muster   │  │ Anna Schmidt │  │ Peter Meier  │               │
│  │ PHP, React   │  │ Design, UX   │  │ PM, Scrum    │               │
│  │ ⭐⭐⭐⭐⭐    │  │ ⭐⭐⭐⭐     │  │ ⭐⭐⭐⭐⭐    │               │
│  │ Läuft ab:    │  │ Läuft ab:    │  │ Läuft ab:    │               │
│  │ 25.01.2027   │  │ 15.03.2027   │  │ 01.06.2027   │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│                                                                      │
│  Filter: [Alle Tags ▼] [Suche...              ] [+ Neu]             │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### DSGVO-Konformität

- **Maximale Aufbewahrung**: 24 Monate (konfigurierbar)
- **Automatische Löschung**: Cron-Job für abgelaufene Einträge
- **Opt-In erforderlich**: Kandidat muss zustimmen (Checkbox bei Bewerbung oder nachträglich)
- **Erinnerung vor Ablauf**: 30 Tage vorher Benachrichtigung an HR

### TalentPoolService.php

```php
<?php
/**
 * Talent-Pool Service
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use RecruitingPlaybook\Repositories\TalentPoolRepository;

class TalentPoolService {

    private TalentPoolRepository $repository;

    /**
     * Standard-Aufbewahrungsdauer in Monaten
     */
    private const DEFAULT_RETENTION_MONTHS = 24;

    public function __construct( TalentPoolRepository $repository ) {
        $this->repository = $repository;
    }

    /**
     * Kandidat zum Talent-Pool hinzufügen
     *
     * @param int    $candidate_id Kandidaten-ID.
     * @param string $reason       Grund für Aufnahme.
     * @param string $tags         Komma-separierte Tags.
     * @param string $expires_at   Ablaufdatum (optional).
     * @return array|WP_Error
     */
    public function add( int $candidate_id, string $reason = '', string $tags = '', ?string $expires_at = null ) {
        // Prüfen ob bereits im Pool
        if ( $this->repository->exists( $candidate_id ) ) {
            return new \WP_Error(
                'already_exists',
                __( 'Kandidat ist bereits im Talent-Pool', 'recruiting-playbook' )
            );
        }

        // Ablaufdatum berechnen
        if ( ! $expires_at ) {
            $retention_months = (int) get_option( 'rp_talent_pool_retention', self::DEFAULT_RETENTION_MONTHS );
            $expires_at       = gmdate( 'Y-m-d H:i:s', strtotime( "+{$retention_months} months" ) );
        }

        // Tags normalisieren
        $normalized_tags = $this->normalize_tags( $tags );

        $entry_id = $this->repository->create( [
            'candidate_id' => $candidate_id,
            'added_by'     => get_current_user_id(),
            'reason'       => sanitize_textarea_field( $reason ),
            'tags'         => $normalized_tags,
            'expires_at'   => $expires_at,
            'created_at'   => current_time( 'mysql' ),
            'updated_at'   => current_time( 'mysql' ),
        ] );

        if ( ! $entry_id ) {
            return new \WP_Error(
                'create_failed',
                __( 'Fehler beim Hinzufügen zum Talent-Pool', 'recruiting-playbook' )
            );
        }

        // Activity Log für alle Bewerbungen des Kandidaten
        $applications = ( new ApplicationRepository() )->find_by_candidate( $candidate_id );
        foreach ( $applications as $app ) {
            do_action( 'rp_activity_log', [
                'application_id' => $app['id'],
                'type'           => 'talent_pool_added',
                'message'        => __( 'Zum Talent-Pool hinzugefügt', 'recruiting-playbook' ),
                'meta'           => [
                    'reason' => $reason,
                    'tags'   => $normalized_tags,
                ],
            ] );
        }

        return $this->repository->find_with_candidate( $entry_id );
    }

    /**
     * Kandidat aus Talent-Pool entfernen
     *
     * @param int $candidate_id Kandidaten-ID.
     * @return bool|WP_Error
     */
    public function remove( int $candidate_id ) {
        $entry = $this->repository->find_by_candidate( $candidate_id );

        if ( ! $entry ) {
            return new \WP_Error(
                'not_found',
                __( 'Kandidat nicht im Talent-Pool', 'recruiting-playbook' )
            );
        }

        $this->repository->soft_delete( $entry['id'] );

        // Activity Log
        $applications = ( new ApplicationRepository() )->find_by_candidate( $candidate_id );
        foreach ( $applications as $app ) {
            do_action( 'rp_activity_log', [
                'application_id' => $app['id'],
                'type'           => 'talent_pool_removed',
                'message'        => __( 'Aus Talent-Pool entfernt', 'recruiting-playbook' ),
            ] );
        }

        return true;
    }

    /**
     * Talent-Pool Liste laden
     *
     * @param array $args Query-Argumente.
     * @return array
     */
    public function get_list( array $args = [] ): array {
        $defaults = [
            'per_page' => 20,
            'page'     => 1,
            'search'   => '',
            'tags'     => [],
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        ];

        $args = wp_parse_args( $args, $defaults );

        return $this->repository->get_list( $args );
    }

    /**
     * Abgelaufene Einträge löschen
     *
     * @return int Anzahl gelöschter Einträge.
     */
    public function cleanup_expired(): int {
        return $this->repository->delete_expired();
    }

    /**
     * Erinnerungen vor Ablauf senden
     *
     * @param int $days_before Tage vor Ablauf.
     * @return int Anzahl gesendeter Erinnerungen.
     */
    public function send_expiry_reminders( int $days_before = 30 ): int {
        $expiring = $this->repository->get_expiring( $days_before );
        $count    = 0;

        foreach ( $expiring as $entry ) {
            // E-Mail an den HR-Verantwortlichen
            $added_by = get_userdata( $entry['added_by'] );
            if ( ! $added_by ) {
                continue;
            }

            $candidate = ( new CandidateRepository() )->find( $entry['candidate_id'] );
            $candidate_name = $candidate
                ? "{$candidate['first_name']} {$candidate['last_name']}"
                : __( 'Unbekannt', 'recruiting-playbook' );

            $subject = sprintf(
                __( '[Recruiting Playbook] Talent-Pool Eintrag läuft ab: %s', 'recruiting-playbook' ),
                $candidate_name
            );

            $message = sprintf(
                __( "Der Talent-Pool Eintrag für %s läuft am %s ab.\n\nBitte prüfen Sie, ob Sie den Eintrag verlängern möchten.", 'recruiting-playbook' ),
                $candidate_name,
                wp_date( get_option( 'date_format' ), strtotime( $entry['expires_at'] ) )
            );

            $sent = wp_mail( $added_by->user_email, $subject, $message );

            if ( $sent ) {
                $this->repository->mark_reminder_sent( $entry['id'] );
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Tags normalisieren
     *
     * @param string $tags Komma-separierte Tags.
     * @return string
     */
    private function normalize_tags( string $tags ): string {
        $tag_array = array_map( 'trim', explode( ',', $tags ) );
        $tag_array = array_filter( $tag_array );
        $tag_array = array_map( 'strtolower', $tag_array );
        $tag_array = array_unique( $tag_array );
        sort( $tag_array );

        return implode( ',', $tag_array );
    }
}
```

### TalentPoolButton.jsx

```jsx
/**
 * Talent-Pool Toggle Button
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useTalentPool } from './hooks/useTalentPool';

export function TalentPoolButton({ candidateId, inPool = false }) {
    const {
        isInPool,
        loading,
        addToPool,
        removeFromPool,
    } = useTalentPool(candidateId, inPool);

    const [showModal, setShowModal] = useState(false);
    const [reason, setReason] = useState('');
    const [tags, setTags] = useState('');

    const handleAdd = async () => {
        await addToPool(reason, tags);
        setShowModal(false);
        setReason('');
        setTags('');
    };

    const handleRemove = async () => {
        if (confirm(__('Kandidat wirklich aus dem Talent-Pool entfernen?', 'recruiting-playbook'))) {
            await removeFromPool();
        }
    };

    if (isInPool) {
        return (
            <button
                className="rp-talent-pool-btn is-in-pool"
                onClick={handleRemove}
                disabled={loading}
                title={__('Aus Talent-Pool entfernen', 'recruiting-playbook')}
            >
                <span className="dashicons dashicons-groups" />
                {__('Im Talent-Pool', 'recruiting-playbook')}
                {loading && <span className="spinner is-active" />}
            </button>
        );
    }

    return (
        <>
            <button
                className="rp-talent-pool-btn"
                onClick={() => setShowModal(true)}
                disabled={loading}
                title={__('Zum Talent-Pool hinzufügen', 'recruiting-playbook')}
            >
                <span className="dashicons dashicons-plus-alt" />
                {__('Talent-Pool', 'recruiting-playbook')}
            </button>

            {showModal && (
                <div className="rp-modal-overlay" onClick={() => setShowModal(false)}>
                    <div className="rp-modal" onClick={e => e.stopPropagation()}>
                        <div className="rp-modal-header">
                            <h3>{__('Zum Talent-Pool hinzufügen', 'recruiting-playbook')}</h3>
                            <button
                                className="rp-modal-close"
                                onClick={() => setShowModal(false)}
                            >
                                <span className="dashicons dashicons-no-alt" />
                            </button>
                        </div>

                        <div className="rp-modal-body">
                            <div className="rp-form-field">
                                <label htmlFor="rp-talent-reason">
                                    {__('Grund für Aufnahme', 'recruiting-playbook')}
                                </label>
                                <textarea
                                    id="rp-talent-reason"
                                    value={reason}
                                    onChange={e => setReason(e.target.value)}
                                    placeholder={__('z.B. Sehr guter Kandidat, aber aktuell keine passende Stelle...', 'recruiting-playbook')}
                                    rows={3}
                                />
                            </div>

                            <div className="rp-form-field">
                                <label htmlFor="rp-talent-tags">
                                    {__('Tags (komma-separiert)', 'recruiting-playbook')}
                                </label>
                                <input
                                    type="text"
                                    id="rp-talent-tags"
                                    value={tags}
                                    onChange={e => setTags(e.target.value)}
                                    placeholder={__('z.B. php, react, senior, remote', 'recruiting-playbook')}
                                />
                                <p className="rp-form-hint">
                                    {__('Tags helfen, Kandidaten später schneller zu finden.', 'recruiting-playbook')}
                                </p>
                            </div>

                            <div className="rp-form-field">
                                <p className="rp-gdpr-notice">
                                    <span className="dashicons dashicons-info" />
                                    {__('Der Eintrag wird nach 24 Monaten automatisch gelöscht (DSGVO).', 'recruiting-playbook')}
                                </p>
                            </div>
                        </div>

                        <div className="rp-modal-footer">
                            <button
                                className="button"
                                onClick={() => setShowModal(false)}
                            >
                                {__('Abbrechen', 'recruiting-playbook')}
                            </button>
                            <button
                                className="button button-primary"
                                onClick={handleAdd}
                                disabled={loading}
                            >
                                {loading ? (
                                    <span className="spinner is-active" />
                                ) : (
                                    __('Hinzufügen', 'recruiting-playbook')
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
```

### Cron-Job für Cleanup

```php
// In Plugin.php - register_hooks()
add_action( 'rp_talent_pool_cleanup', [ TalentPoolService::class, 'cleanup_expired' ] );
add_action( 'rp_talent_pool_reminders', [ TalentPoolService::class, 'send_expiry_reminders' ] );

// In Activator.php
if ( ! wp_next_scheduled( 'rp_talent_pool_cleanup' ) ) {
    wp_schedule_event( time(), 'daily', 'rp_talent_pool_cleanup' );
}
if ( ! wp_next_scheduled( 'rp_talent_pool_reminders' ) ) {
    wp_schedule_event( time(), 'daily', 'rp_talent_pool_reminders' );
}
```

---

## 9. Bewerber-Detailseite Integration

### Layout

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ ← Zurück zur Liste          Bewerbung #123                    [Talent-Pool] │
├───────────────────────────────────────────┬─────────────────────────────────┤
│                                           │                                 │
│  ┌─────────────────────────────────────┐  │  ┌─────────────────────────────┐│
│  │         KANDIDATEN-INFO              │  │  │       TIMELINE              ││
│  │                                       │  │  │                             ││
│  │  [Avatar]  Max Mustermann            │  │  │  Heute                      ││
│  │            max@example.com           │  │  │  ├─ 10:30 Status geändert   ││
│  │            +49 123 456789            │  │  │  │        Neu → In Prüfung  ││
│  │                                       │  │  │  ├─ 09:15 Notiz hinzugefügt││
│  │  Bewertung: ⭐⭐⭐⭐☆ (4.2)          │  │  │  │                           ││
│  │                                       │  │  │  Gestern                   ││
│  │  Status: [In Prüfung ▼]              │  │  │  ├─ 16:00 Bewerbung         ││
│  │                                       │  │  │         eingegangen        ││
│  │  Stelle: Senior Developer            │  │  │                             ││
│  │  Beworben: 25.01.2025                │  │  │  [Mehr laden]               ││
│  └─────────────────────────────────────┘  │  └─────────────────────────────┘│
│                                           │                                 │
│  ┌─────────────────────────────────────┐  │                                 │
│  │         DOKUMENTE                    │  │                                 │
│  │                                       │  │                                 │
│  │  📄 Lebenslauf.pdf     [Ansehen]     │  │                                 │
│  │  📄 Zeugnisse.pdf      [Ansehen]     │  │                                 │
│  └─────────────────────────────────────┘  │                                 │
│                                           │                                 │
│  ┌─────────────────────────────────────┐  │                                 │
│  │         NOTIZEN                      │  │                                 │
│  │                                       │  │                                 │
│  │  [+ Neue Notiz]                      │  │                                 │
│  │                                       │  │                                 │
│  │  Max Recruiter · vor 2 Std.          │  │                                 │
│  │  Sehr gutes Telefoninterview...      │  │                                 │
│  │                                       │  │                                 │
│  │  Anna HR · vor 1 Tag                 │  │                                 │
│  │  CV sieht vielversprechend aus...    │  │                                 │
│  └─────────────────────────────────────┘  │                                 │
│                                           │                                 │
└───────────────────────────────────────────┴─────────────────────────────────┘
```

### ApplicantDetail.jsx

```jsx
/**
 * Bewerber-Detailseite Hauptkomponente
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { NotesPanel } from './NotesPanel';
import { RatingStars } from './RatingStars';
import { Timeline } from './Timeline';
import { TalentPoolButton } from './TalentPoolButton';
import { useRating } from './hooks/useRating';

export function ApplicantDetail({ applicationId }) {
    const [application, setApplication] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const {
        summary: ratingSummary,
        userRatings,
        rate,
    } = useRating(applicationId);

    useEffect(() => {
        loadApplication();
    }, [applicationId]);

    const loadApplication = async () => {
        try {
            setLoading(true);
            const data = await apiFetch({
                path: `/recruiting/v1/applications/${applicationId}`,
            });
            setApplication(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleStatusChange = async (newStatus) => {
        try {
            await apiFetch({
                path: `/recruiting/v1/applications/${applicationId}/status`,
                method: 'PATCH',
                data: { status: newStatus },
            });
            setApplication(prev => ({ ...prev, status: newStatus }));
        } catch (err) {
            console.error('Status update failed:', err);
        }
    };

    if (loading) {
        return (
            <div className="rp-applicant-detail rp-loading">
                <span className="spinner is-active" />
                {__('Lade Bewerbung...', 'recruiting-playbook')}
            </div>
        );
    }

    if (error) {
        return (
            <div className="rp-applicant-detail rp-error">
                <div className="notice notice-error">
                    <p>{error}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="rp-applicant-detail">
            <div className="rp-applicant-header">
                <a href={window.rpApplicant.listUrl} className="rp-back-link">
                    <span className="dashicons dashicons-arrow-left-alt" />
                    {__('Zurück zur Liste', 'recruiting-playbook')}
                </a>

                <h1>
                    {__('Bewerbung', 'recruiting-playbook')} #{applicationId}
                </h1>

                <div className="rp-applicant-actions">
                    <TalentPoolButton
                        candidateId={application.candidate_id}
                        inPool={application.in_talent_pool}
                    />
                </div>
            </div>

            <div className="rp-applicant-layout">
                <div className="rp-applicant-main">
                    {/* Kandidaten-Info */}
                    <div className="rp-card rp-candidate-info">
                        <div className="rp-candidate-header">
                            <div className="rp-candidate-avatar">
                                {getInitials(application.first_name, application.last_name)}
                            </div>
                            <div className="rp-candidate-details">
                                <h2 className="rp-candidate-name">
                                    {application.first_name} {application.last_name}
                                </h2>
                                <div className="rp-candidate-contact">
                                    <a href={`mailto:${application.email}`}>
                                        <span className="dashicons dashicons-email" />
                                        {application.email}
                                    </a>
                                    {application.phone && (
                                        <a href={`tel:${application.phone}`}>
                                            <span className="dashicons dashicons-phone" />
                                            {application.phone}
                                        </a>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="rp-candidate-rating">
                            <RatingStars
                                applicationId={applicationId}
                                summary={ratingSummary}
                                userRatings={userRatings}
                                onRate={rate}
                                showCategories={true}
                            />
                        </div>

                        <div className="rp-candidate-meta">
                            <div className="rp-meta-item">
                                <label>{__('Status', 'recruiting-playbook')}</label>
                                <select
                                    value={application.status}
                                    onChange={(e) => handleStatusChange(e.target.value)}
                                    className="rp-status-select"
                                >
                                    {window.rpApplicant.statuses.map(status => (
                                        <option key={status.id} value={status.id}>
                                            {status.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="rp-meta-item">
                                <label>{__('Stelle', 'recruiting-playbook')}</label>
                                <a href={application.job_edit_url}>
                                    {application.job_title}
                                </a>
                            </div>
                            <div className="rp-meta-item">
                                <label>{__('Beworben am', 'recruiting-playbook')}</label>
                                <span>
                                    {new Date(application.created_at).toLocaleDateString('de-DE')}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Dokumente */}
                    {application.documents?.length > 0 && (
                        <div className="rp-card rp-documents">
                            <h3>{__('Dokumente', 'recruiting-playbook')}</h3>
                            <ul className="rp-document-list">
                                {application.documents.map(doc => (
                                    <li key={doc.id} className="rp-document-item">
                                        <span className="dashicons dashicons-media-document" />
                                        <span className="rp-document-name">{doc.filename}</span>
                                        <span className="rp-document-size">{formatFileSize(doc.size)}</span>
                                        <a
                                            href={doc.download_url}
                                            className="button button-small"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {__('Ansehen', 'recruiting-playbook')}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* Notizen */}
                    <div className="rp-card">
                        <NotesPanel applicationId={applicationId} />
                    </div>
                </div>

                <div className="rp-applicant-sidebar">
                    {/* Timeline */}
                    <div className="rp-card">
                        <Timeline applicationId={applicationId} />
                    </div>
                </div>
            </div>
        </div>
    );
}

function getInitials(firstName, lastName) {
    return `${firstName?.[0] || ''}${lastName?.[0] || ''}`.toUpperCase() || '?';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
```

---

## 10. Kanban-Integration

### Erweiterte Kanban-Card

Die Kanban-Card wird um Rating und Notiz-Indikator erweitert:

```jsx
// In KanbanCard.jsx erweitern

{/* Rating-Anzeige */}
{application.average_rating > 0 && (
    <div className="rp-kanban-card-rating">
        <span className="dashicons dashicons-star-filled" />
        <span>{application.average_rating.toFixed(1)}</span>
    </div>
)}

{/* Notiz-Indikator */}
{application.notes_count > 0 && (
    <div
        className="rp-kanban-card-notes"
        title={sprintf(
            __('%d Notizen', 'recruiting-playbook'),
            application.notes_count
        )}
    >
        <span className="dashicons dashicons-admin-comments" />
        <span>{application.notes_count}</span>
    </div>
)}

{/* Talent-Pool Badge */}
{application.in_talent_pool && (
    <div
        className="rp-kanban-card-talent-pool"
        title={__('Im Talent-Pool', 'recruiting-playbook')}
    >
        <span className="dashicons dashicons-groups" />
    </div>
)}
```

### API-Erweiterung für Kanban

```php
// In ApplicationService.php - get_for_kanban()
// Zusätzliche Felder laden

$results = $wpdb->get_results(
    "SELECT
        a.id,
        a.status,
        a.kanban_position,
        a.created_at,
        c.first_name,
        c.last_name,
        c.email,
        p.post_title as job_title,
        (SELECT COUNT(*) FROM {$wpdb->prefix}rp_documents d WHERE d.application_id = a.id) as documents_count,
        (SELECT AVG(rating) FROM {$wpdb->prefix}rp_ratings r WHERE r.application_id = a.id AND r.category = 'overall') as average_rating,
        (SELECT COUNT(*) FROM {$wpdb->prefix}rp_notes n WHERE n.application_id = a.id AND n.deleted_at IS NULL) as notes_count,
        (SELECT 1 FROM {$wpdb->prefix}rp_talent_pool tp WHERE tp.candidate_id = c.id AND tp.deleted_at IS NULL) as in_talent_pool
    FROM {$wpdb->prefix}rp_applications a
    LEFT JOIN {$wpdb->prefix}rp_candidates c ON a.candidate_id = c.id
    LEFT JOIN {$wpdb->prefix}posts p ON a.job_id = p.ID
    WHERE a.deleted_at IS NULL
    ORDER BY a.status, a.kanban_position ASC",
    ARRAY_A
);
```

---

## 11. Berechtigungen

### Neue Capabilities

| Capability | Beschreibung | Rollen |
|------------|--------------|--------|
| `view_notes` | Notizen anderer User lesen | Administrator, Recruiter, Hiring Manager |
| `create_notes` | Notizen erstellen | Administrator, Recruiter |
| `edit_own_notes` | Eigene Notizen bearbeiten | Administrator, Recruiter |
| `edit_others_notes` | Fremde Notizen bearbeiten | Administrator |
| `delete_notes` | Notizen löschen | Administrator |
| `rate_applications` | Bewerbungen bewerten | Administrator, Recruiter, Hiring Manager |
| `manage_talent_pool` | Talent-Pool verwalten | Administrator, Recruiter |
| `view_activity_log` | Activity Log einsehen | Administrator, Recruiter |

### Capability-Check

```php
// NoteController.php
public function create_note_permissions_check( WP_REST_Request $request ): bool {
    return current_user_can( 'create_notes' );
}

public function update_note_permissions_check( WP_REST_Request $request ): bool {
    $note_id = $request->get_param( 'id' );
    $note    = $this->repository->find( $note_id );

    if ( ! $note ) {
        return false;
    }

    // Eigene Notiz oder Admin
    if ( $note['user_id'] === get_current_user_id() ) {
        return current_user_can( 'edit_own_notes' );
    }

    return current_user_can( 'edit_others_notes' );
}
```

---

## 12. Testing

### Unit Tests (PHP)

```php
/**
 * NoteService Tests
 */
class NoteServiceTest extends WP_UnitTestCase {

    private NoteService $service;
    private int $application_id;
    private int $user_id;

    public function setUp(): void {
        parent::setUp();

        $this->user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $this->user_id );

        // Test-Bewerbung erstellen
        $this->application_id = $this->create_test_application();

        $this->service = new NoteService( new NoteRepository() );
    }

    public function test_create_note(): void {
        $note = $this->service->create(
            $this->application_id,
            'Test-Notiz Inhalt',
            false
        );

        $this->assertIsArray( $note );
        $this->assertEquals( 'Test-Notiz Inhalt', $note['content'] );
        $this->assertEquals( $this->user_id, $note['user_id'] );
        $this->assertEquals( 0, $note['is_private'] );
    }

    public function test_create_private_note(): void {
        $note = $this->service->create(
            $this->application_id,
            'Private Notiz',
            true
        );

        $this->assertEquals( 1, $note['is_private'] );
    }

    public function test_private_note_visibility(): void {
        // Als User 1 private Notiz erstellen
        $this->service->create( $this->application_id, 'Private', true );

        // Als User 2 Notizen laden
        $user2 = $this->factory->user->create( [ 'role' => 'editor' ] );
        wp_set_current_user( $user2 );

        $notes = $this->service->get_for_application( $this->application_id );

        // Private Notiz sollte nicht sichtbar sein
        $this->assertCount( 0, $notes );
    }

    public function test_update_own_note(): void {
        $note = $this->service->create( $this->application_id, 'Original', false );

        $updated = $this->service->update( $note['id'], 'Aktualisiert' );

        $this->assertEquals( 'Aktualisiert', $updated['content'] );
    }

    public function test_cannot_update_others_note(): void {
        $note = $this->service->create( $this->application_id, 'Original', false );

        // Als anderer User versuchen zu bearbeiten
        $user2 = $this->factory->user->create( [ 'role' => 'editor' ] );
        wp_set_current_user( $user2 );

        $result = $this->service->update( $note['id'], 'Gehackt!' );

        $this->assertWPError( $result );
        $this->assertEquals( 'forbidden', $result->get_error_code() );
    }

    public function test_soft_delete(): void {
        $note = $this->service->create( $this->application_id, 'Test', false );

        $result = $this->service->delete( $note['id'] );

        $this->assertTrue( $result );

        // Notiz sollte nicht mehr in Liste erscheinen
        $notes = $this->service->get_for_application( $this->application_id );
        $this->assertCount( 0, $notes );
    }
}
```

### Unit Tests (Jest)

```jsx
// __tests__/NotesPanel.test.jsx
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { NotesPanel } from '../NotesPanel';
import apiFetch from '@wordpress/api-fetch';

jest.mock('@wordpress/api-fetch');

describe('NotesPanel', () => {
    const mockNotes = [
        {
            id: 1,
            content: '<p>Test-Notiz</p>',
            is_private: false,
            author: { id: 1, name: 'Max', avatar: '' },
            created_at: new Date().toISOString(),
            can_edit: true,
            can_delete: true,
        },
    ];

    beforeEach(() => {
        apiFetch.mockReset();
    });

    it('renders notes list', async () => {
        apiFetch.mockResolvedValue(mockNotes);

        render(<NotesPanel applicationId={123} />);

        await waitFor(() => {
            expect(screen.getByText('Test-Notiz')).toBeInTheDocument();
        });
    });

    it('shows empty state', async () => {
        apiFetch.mockResolvedValue([]);

        render(<NotesPanel applicationId={123} />);

        await waitFor(() => {
            expect(screen.getByText('Noch keine Notizen vorhanden')).toBeInTheDocument();
        });
    });

    it('opens editor on button click', async () => {
        apiFetch.mockResolvedValue([]);

        render(<NotesPanel applicationId={123} />);

        await waitFor(() => {
            expect(screen.getByText('Neue Notiz')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByText('Neue Notiz'));

        expect(screen.getByPlaceholderText('Notiz eingeben...')).toBeInTheDocument();
    });

    it('creates note on save', async () => {
        apiFetch
            .mockResolvedValueOnce([]) // Initial load
            .mockResolvedValueOnce({ id: 2, content: 'Neue Notiz' }); // Create

        render(<NotesPanel applicationId={123} />);

        await waitFor(() => {
            fireEvent.click(screen.getByText('Neue Notiz'));
        });

        const textarea = screen.getByPlaceholderText('Notiz eingeben...');
        fireEvent.change(textarea, { target: { value: 'Neue Notiz' } });

        fireEvent.click(screen.getByText('Speichern'));

        await waitFor(() => {
            expect(apiFetch).toHaveBeenCalledWith(
                expect.objectContaining({
                    method: 'POST',
                    data: expect.objectContaining({ content: 'Neue Notiz' }),
                })
            );
        });
    });
});
```

### E2E Tests (Playwright)

```js
// e2e/applicant-management.spec.js
import { test, expect } from '@playwright/test';

test.describe('Applicant Management', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=rp-application&id=1');
    });

    test('can add note to application', async ({ page }) => {
        await page.click('text=Neue Notiz');
        await page.fill('textarea', 'E2E Test Notiz');
        await page.click('text=Speichern');

        await expect(page.locator('.rp-note-content')).toContainText('E2E Test Notiz');
    });

    test('can rate application', async ({ page }) => {
        // 4 Sterne klicken
        await page.click('.rp-rating-star:nth-child(4)');

        await expect(page.locator('.rp-rating-average')).toContainText('4');
    });

    test('can add to talent pool', async ({ page }) => {
        await page.click('text=Talent-Pool');
        await page.fill('#rp-talent-reason', 'Guter Kandidat');
        await page.fill('#rp-talent-tags', 'php, react');
        await page.click('.rp-modal text=Hinzufügen');

        await expect(page.locator('.rp-talent-pool-btn')).toHaveClass(/is-in-pool/);
    });

    test('timeline shows activities', async ({ page }) => {
        await expect(page.locator('.rp-timeline-item')).toHaveCount.greaterThan(0);
    });
});
```

---

## Deliverables

| Item | Beschreibung | Kriterium |
|------|--------------|-----------|
| Notizen-System | CRUD für Notizen | ✅ Private/öffentliche Notizen |
| Bewertungs-System | 1-5 Sterne mit Kategorien | ✅ Durchschnitt berechnet |
| Activity Log | Alle Aktionen geloggt | ✅ Vollständiger Audit-Trail |
| Timeline | Chronologische Ansicht | ✅ Filter funktioniert |
| Talent-Pool | Kandidaten speichern | ✅ DSGVO-konforme Aufbewahrung |
| Detailseite | Alle Features integriert | ✅ Übersichtliches Layout |
| Kanban-Integration | Ratings/Notizen in Cards | ✅ Indikatoren sichtbar |
| Berechtigungen | Capability-basiert | ✅ Rollen-Check funktioniert |
| API | REST-Endpunkte | ✅ Dokumentiert |
| Tests | Unit + E2E | ✅ 80% Coverage |

---

## Branch-Strategie

```
feature/pro
    └── feature/applicant-management
            ├── Commit 1: Datenbank-Schema + Repositories
            ├── Commit 2: Services + API Endpoints
            ├── Commit 3: React-Komponenten (Notizen)
            ├── Commit 4: React-Komponenten (Rating, Timeline)
            ├── Commit 5: Talent-Pool
            ├── Commit 6: Kanban-Integration
            └── Commit 7: Tests + Documentation
```

Nach Fertigstellung:
```
feature/applicant-management → feature/pro (Merge)
```

---

## Abhängigkeiten

| Abhängigkeit | Version | Zweck |
|--------------|---------|-------|
| @wordpress/element | ^5.0 | React Integration |
| @wordpress/api-fetch | ^6.0 | REST API Kommunikation |
| @wordpress/i18n | ^4.0 | Internationalisierung |

---

## Nächste Features

Nach erfolgreichem Abschluss des Bewerbermanagements:

→ **M2.3: E-Mail-System (Pro)**
- Template-Editor (WYSIWYG)
- Platzhalter für Bewerber-Daten
- E-Mail-Historie

→ **M2.4: Benutzerrollen**
- Recruiter, Hiring Manager Rollen
- Stellen-Zuweisung pro User

---

*Technische Spezifikation erstellt: Januar 2025*

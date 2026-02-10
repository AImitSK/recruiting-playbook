# Kanban-Board: Technische Spezifikation

> **Pro-Feature: Visuelles Bewerbermanagement**
> Drag-and-Drop Kanban-Board für effizientes Status-Tracking

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Architektur](#2-architektur)
3. [Datenmodell](#3-datenmodell)
4. [REST API Endpunkte](#4-rest-api-endpunkte)
5. [Admin-Seite Integration](#5-admin-seite-integration)
6. [React-Komponenten](#6-react-komponenten)
7. [Drag-and-Drop Implementierung](#7-drag-and-drop-implementierung)
8. [Echtzeit-Updates](#8-echtzeit-updates)
9. [Performance-Optimierung](#9-performance-optimierung)
10. [Barrierefreiheit](#10-barrierefreiheit)
11. [Testing](#11-testing)

---

## 1. Übersicht

### Zielsetzung

Das Kanban-Board ermöglicht Recruitern, Bewerbungen visuell nach Status zu organisieren und per Drag-and-Drop durch den Bewerbungsprozess zu führen.

### Feature-Gating

```php
// Nur für Pro-Lizenzen verfügbar
if ( ! rp_can( 'kanban_board' ) ) {
    // Upgrade-Hinweis anzeigen
    rp_require_feature( 'kanban_board', 'Kanban-Board', 'PRO' );
    return;
}
```

### Status-Spalten

```
┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│   NEU    │  │ PRÜFUNG  │  │INTERVIEW │  │ ANGEBOT  │  │EINGESTELLT│
│          │  │          │  │          │  │          │  │          │
│ ┌──────┐ │  │ ┌──────┐ │  │ ┌──────┐ │  │          │  │          │
│ │Card 1│ │  │ │Card 3│ │  │ │Card 5│ │  │          │  │          │
│ └──────┘ │  │ └──────┘ │  │ └──────┘ │  │          │  │          │
│ ┌──────┐ │  │ ┌──────┐ │  │          │  │          │  │          │
│ │Card 2│ │  │ │Card 4│ │  │          │  │          │  │          │
│ └──────┘ │  │ └──────┘ │  │          │  │          │  │          │
└──────────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘

┌──────────┐  ┌──────────┐
│ ABGELEHNT│  │ZURÜCKGEZ.│  (Collapsed by default)
└──────────┘  └──────────┘
```

### Bewerbungsstatus-Workflow

```
new → screening → interview → offer → hired
  ↓         ↓          ↓         ↓
  rejected (von jedem Status möglich)
  withdrawn (von jedem Status möglich)
```

---

## 2. Architektur

### Verzeichnisstruktur

```
plugin/
├── src/
│   ├── Admin/
│   │   └── Pages/
│   │       └── KanbanBoard.php          # Admin-Seite
│   │
│   └── Api/
│       └── KanbanController.php         # REST API (optional, erweitert ApplicationController)
│
├── assets/
│   └── src/
│       ├── js/
│       │   └── admin/
│       │       └── kanban/
│       │           ├── index.jsx        # Entry Point
│       │           ├── KanbanBoard.jsx  # Hauptkomponente
│       │           ├── KanbanColumn.jsx # Spalte
│       │           ├── KanbanCard.jsx   # Bewerber-Karte
│       │           ├── CardQuickView.jsx# Modal für Quick-View
│       │           ├── hooks/
│       │           │   ├── useApplications.js
│       │           │   └── useDragAndDrop.js
│       │           └── store/
│       │               └── kanbanStore.js
│       │
│       └── css/
│           └── admin-kanban.css         # Kanban-spezifische Styles
```

### Technologie-Stack

| Komponente | Technologie |
|------------|-------------|
| Frontend | React 18 (@wordpress/element) |
| State Management | @wordpress/data oder React Context |
| Drag-and-Drop | @dnd-kit/core (leichtgewichtig, a11y) |
| API-Kommunikation | @wordpress/api-fetch |
| Styling | Tailwind CSS (rp- Prefix) |

---

## 3. Datenmodell

### Bestehende Tabelle: `rp_applications`

Die Kanban-Ansicht nutzt die bestehende `rp_applications` Tabelle:

```sql
-- Relevante Felder für Kanban
id              bigint(20) unsigned
candidate_id    bigint(20) unsigned
job_id          bigint(20) unsigned
status          varchar(50)         -- 'new', 'screening', 'interview', 'offer', 'hired', 'rejected', 'withdrawn'
created_at      datetime
updated_at      datetime
```

### Erweiterung: Spalten-Reihenfolge

Für Drag-and-Drop innerhalb einer Spalte wird eine Position benötigt:

```sql
-- Migration hinzufügen zu Schema.php
ALTER TABLE {$prefix}rp_applications
ADD COLUMN kanban_position int(11) DEFAULT 0 AFTER status;
ADD INDEX kanban_sort (status, kanban_position);
```

### Kandidaten-Daten (Join)

```sql
-- Für Kartenansicht benötigt
SELECT
    a.id,
    a.status,
    a.kanban_position,
    a.created_at,
    c.first_name,
    c.last_name,
    c.email,
    p.post_title as job_title
FROM {$prefix}rp_applications a
JOIN {$prefix}rp_candidates c ON a.candidate_id = c.id
JOIN {$prefix}posts p ON a.job_id = p.ID
WHERE a.deleted_at IS NULL
ORDER BY a.status, a.kanban_position ASC
```

---

## 4. REST API Endpunkte

### Bestehende Endpunkte (ApplicationController)

Die bestehenden Endpunkte werden weiterverwendet:

| Methode | Endpunkt | Beschreibung |
|---------|----------|--------------|
| GET | `/recruiting/v1/applications` | Alle Bewerbungen laden |
| PATCH | `/recruiting/v1/applications/{id}/status` | Status ändern |

### Neuer Endpunkt: Bulk-Update für Sortierung

```php
// In ApplicationController.php oder neuer KanbanController.php

register_rest_route(
    $this->namespace,
    '/kanban/reorder',
    [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ $this, 'reorder_applications' ],
        'permission_callback' => [ $this, 'update_item_permissions_check' ],
        'args'                => [
            'moves' => [
                'description' => __( 'Array von Bewegungen', 'recruiting-playbook' ),
                'type'        => 'array',
                'required'    => true,
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'id'              => [ 'type' => 'integer' ],
                        'status'          => [ 'type' => 'string' ],
                        'kanban_position' => [ 'type' => 'integer' ],
                    ],
                ],
            ],
        ],
    ]
);
```

### Reorder-Logik

```php
/**
 * Bewerbungen neu sortieren
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
public function reorder_applications( WP_REST_Request $request ) {
    global $wpdb;

    $moves = $request->get_param( 'moves' );
    $table = $wpdb->prefix . 'rp_applications';

    $wpdb->query( 'START TRANSACTION' );

    try {
        foreach ( $moves as $move ) {
            $result = $wpdb->update(
                $table,
                [
                    'status'          => sanitize_text_field( $move['status'] ),
                    'kanban_position' => absint( $move['kanban_position'] ),
                    'updated_at'      => current_time( 'mysql' ),
                ],
                [ 'id' => absint( $move['id'] ) ],
                [ '%s', '%d', '%s' ],
                [ '%d' ]
            );

            if ( false === $result ) {
                throw new \Exception( 'Database update failed' );
            }

            // Activity Log
            do_action( 'rp_application_status_changed', $move['id'], $move['status'] );
        }

        $wpdb->query( 'COMMIT' );

        return new WP_REST_Response( [ 'success' => true ], 200 );

    } catch ( \Exception $e ) {
        $wpdb->query( 'ROLLBACK' );
        return new WP_Error( 'reorder_failed', $e->getMessage(), [ 'status' => 500 ] );
    }
}
```

---

## 5. Admin-Seite Integration

### KanbanBoard.php

```php
<?php
/**
 * Kanban-Board Admin-Seite
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Kanban-Board Seite
 */
class KanbanBoard {

    /**
     * Seite registrieren
     */
    public function register(): void {
        add_submenu_page(
            'recruiting-playbook',
            __( 'Kanban-Board', 'recruiting-playbook' ),
            __( 'Kanban-Board', 'recruiting-playbook' ),
            'view_applications',
            'rp-kanban',
            [ $this, 'render' ]
        );
    }

    /**
     * Seite rendern
     */
    public function render(): void {
        // Feature-Check
        if ( function_exists( 'rp_can' ) && ! rp_can( 'kanban_board' ) ) {
            $this->render_upgrade_notice();
            return;
        }

        // Assets laden
        $this->enqueue_assets();

        // React-Container
        echo '<div class="wrap rp-kanban-wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__( 'Kanban-Board', 'recruiting-playbook' ) . '</h1>';

        // Filter-Toolbar
        $this->render_toolbar();

        // React-Mount-Point
        echo '<div id="rp-kanban-root"></div>';
        echo '</div>';
    }

    /**
     * Upgrade-Hinweis für Free-User
     */
    private function render_upgrade_notice(): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Kanban-Board', 'recruiting-playbook' ) . '</h1>';

        if ( function_exists( 'rp_require_feature' ) ) {
            rp_require_feature( 'kanban_board', 'Kanban-Board', 'PRO' );
        }

        echo '</div>';
    }

    /**
     * Filter-Toolbar
     */
    private function render_toolbar(): void {
        $jobs = get_posts( [
            'post_type'      => 'job_listing',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        ?>
        <div class="rp-kanban-toolbar" id="rp-kanban-toolbar">
            <select id="rp-kanban-job-filter" class="rp-select">
                <option value=""><?php esc_html_e( 'Alle Stellen', 'recruiting-playbook' ); ?></option>
                <?php foreach ( $jobs as $job ) : ?>
                    <option value="<?php echo esc_attr( $job->ID ); ?>">
                        <?php echo esc_html( $job->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input
                type="search"
                id="rp-kanban-search"
                class="rp-search-input"
                placeholder="<?php esc_attr_e( 'Bewerber suchen...', 'recruiting-playbook' ); ?>"
            />
        </div>
        <?php
    }

    /**
     * Assets laden
     */
    private function enqueue_assets(): void {
        $asset_file = RP_PLUGIN_DIR . 'assets/dist/js/kanban.asset.php';
        $asset      = file_exists( $asset_file )
            ? require $asset_file
            : [ 'dependencies' => [], 'version' => RP_VERSION ];

        wp_enqueue_script(
            'rp-kanban',
            RP_PLUGIN_URL . 'assets/dist/js/kanban.js',
            array_merge( $asset['dependencies'], [ 'wp-element', 'wp-api-fetch', 'wp-i18n' ] ),
            $asset['version'],
            true
        );

        wp_set_script_translations( 'rp-kanban', 'recruiting-playbook' );

        wp_localize_script( 'rp-kanban', 'rpKanban', [
            'apiUrl'    => rest_url( 'recruiting/v1/' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'adminUrl'  => admin_url(),
            'statuses'  => $this->get_statuses(),
            'i18n'      => [
                'new'       => __( 'Neu', 'recruiting-playbook' ),
                'screening' => __( 'In Prüfung', 'recruiting-playbook' ),
                'interview' => __( 'Interview', 'recruiting-playbook' ),
                'offer'     => __( 'Angebot', 'recruiting-playbook' ),
                'hired'     => __( 'Eingestellt', 'recruiting-playbook' ),
                'rejected'  => __( 'Abgelehnt', 'recruiting-playbook' ),
                'withdrawn' => __( 'Zurückgezogen', 'recruiting-playbook' ),
            ],
        ] );

        wp_enqueue_style(
            'rp-kanban',
            RP_PLUGIN_URL . 'assets/dist/css/admin-kanban.css',
            [],
            RP_VERSION
        );
    }

    /**
     * Status-Konfiguration
     */
    private function get_statuses(): array {
        return [
            [
                'id'    => 'new',
                'label' => __( 'Neu', 'recruiting-playbook' ),
                'color' => '#2271b1',
            ],
            [
                'id'    => 'screening',
                'label' => __( 'In Prüfung', 'recruiting-playbook' ),
                'color' => '#dba617',
            ],
            [
                'id'    => 'interview',
                'label' => __( 'Interview', 'recruiting-playbook' ),
                'color' => '#9b59b6',
            ],
            [
                'id'    => 'offer',
                'label' => __( 'Angebot', 'recruiting-playbook' ),
                'color' => '#1e8cbe',
            ],
            [
                'id'    => 'hired',
                'label' => __( 'Eingestellt', 'recruiting-playbook' ),
                'color' => '#00a32a',
            ],
            [
                'id'        => 'rejected',
                'label'     => __( 'Abgelehnt', 'recruiting-playbook' ),
                'color'     => '#d63638',
                'collapsed' => true,
            ],
            [
                'id'        => 'withdrawn',
                'label'     => __( 'Zurückgezogen', 'recruiting-playbook' ),
                'color'     => '#787c82',
                'collapsed' => true,
            ],
        ];
    }
}
```

### Menu.php Erweiterung

```php
// In Menu.php - register() Methode ergänzen

// Kanban-Board (Pro-Feature)
if ( function_exists( 'rp_can' ) && rp_can( 'kanban_board' ) ) {
    $kanban = new KanbanBoard();
    $kanban->register();
} else {
    // Menüpunkt trotzdem anzeigen (mit Lock-Icon)
    add_submenu_page(
        'recruiting-playbook',
        __( 'Kanban-Board', 'recruiting-playbook' ),
        __( 'Kanban-Board', 'recruiting-playbook' ) . ' <span class="dashicons dashicons-lock" style="font-size:12px;"></span>',
        'view_applications',
        'rp-kanban',
        [ new KanbanBoard(), 'render' ]
    );
}
```

---

## 6. React-Komponenten

### index.jsx (Entry Point)

```jsx
/**
 * Kanban-Board Entry Point
 */
import { createRoot } from '@wordpress/element';
import { KanbanBoard } from './KanbanBoard';
import './styles.css';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('rp-kanban-root');

    if (container) {
        const root = createRoot(container);
        root.render(<KanbanBoard />);
    }
});
```

### KanbanBoard.jsx

```jsx
/**
 * Kanban-Board Hauptkomponente
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { KanbanColumn } from './KanbanColumn';
import { useApplications } from './hooks/useApplications';

export function KanbanBoard() {
    const {
        applications,
        loading,
        error,
        moveApplication,
        refetch
    } = useApplications();

    const [jobFilter, setJobFilter] = useState('');
    const [searchTerm, setSearchTerm] = useState('');

    // Sensoren für Drag-and-Drop
    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        }),
        useSensor(KeyboardSensor)
    );

    // Filter aus Toolbar synchronisieren
    useEffect(() => {
        const jobSelect = document.getElementById('rp-kanban-job-filter');
        const searchInput = document.getElementById('rp-kanban-search');

        if (jobSelect) {
            jobSelect.addEventListener('change', (e) => setJobFilter(e.target.value));
        }
        if (searchInput) {
            searchInput.addEventListener('input', (e) => setSearchTerm(e.target.value));
        }

        return () => {
            if (jobSelect) jobSelect.removeEventListener('change', () => {});
            if (searchInput) searchInput.removeEventListener('input', () => {});
        };
    }, []);

    // Gefilterte Bewerbungen
    const filteredApplications = applications.filter(app => {
        if (jobFilter && app.job_id !== parseInt(jobFilter)) {
            return false;
        }
        if (searchTerm) {
            const search = searchTerm.toLowerCase();
            const name = `${app.first_name} ${app.last_name}`.toLowerCase();
            const email = app.email.toLowerCase();
            if (!name.includes(search) && !email.includes(search)) {
                return false;
            }
        }
        return true;
    });

    // Nach Status gruppieren
    const columns = window.rpKanban.statuses.map(status => ({
        ...status,
        applications: filteredApplications
            .filter(app => app.status === status.id)
            .sort((a, b) => a.kanban_position - b.kanban_position),
    }));

    // Drag-End Handler
    const handleDragEnd = useCallback(async (event) => {
        const { active, over } = event;

        if (!over) return;

        const activeId = active.id;
        const overId = over.id;

        // Status aus Container-ID extrahieren
        const overStatus = over.data?.current?.status || over.id;
        const activeApp = applications.find(a => a.id === activeId);

        if (activeApp && activeApp.status !== overStatus) {
            await moveApplication(activeId, overStatus);
        }
    }, [applications, moveApplication]);

    if (loading) {
        return (
            <div className="rp-kanban-loading">
                <span className="spinner is-active"></span>
                {__('Lade Bewerbungen...', 'recruiting-playbook')}
            </div>
        );
    }

    if (error) {
        return (
            <div className="rp-kanban-error notice notice-error">
                <p>{error}</p>
                <button onClick={refetch} className="button">
                    {__('Erneut versuchen', 'recruiting-playbook')}
                </button>
            </div>
        );
    }

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
        >
            <div className="rp-kanban-board">
                {columns.map(column => (
                    <KanbanColumn
                        key={column.id}
                        status={column.id}
                        label={column.label}
                        color={column.color}
                        collapsed={column.collapsed}
                        applications={column.applications}
                    />
                ))}
            </div>
        </DndContext>
    );
}
```

### KanbanColumn.jsx

```jsx
/**
 * Kanban-Spalte
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDroppable } from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { KanbanCard } from './KanbanCard';

export function KanbanColumn({
    status,
    label,
    color,
    collapsed: initialCollapsed = false,
    applications
}) {
    const [isCollapsed, setIsCollapsed] = useState(initialCollapsed);

    const { setNodeRef, isOver } = useDroppable({
        id: status,
        data: { status },
    });

    const count = applications.length;

    return (
        <div
            className={`rp-kanban-column ${isCollapsed ? 'is-collapsed' : ''} ${isOver ? 'is-over' : ''}`}
            style={{ '--column-color': color }}
        >
            <div
                className="rp-kanban-column-header"
                onClick={() => setIsCollapsed(!isCollapsed)}
            >
                <span
                    className="rp-kanban-column-color"
                    style={{ backgroundColor: color }}
                />
                <h3 className="rp-kanban-column-title">
                    {label}
                </h3>
                <span className="rp-kanban-column-count">
                    {count}
                </span>
                <button
                    className="rp-kanban-collapse-btn"
                    aria-label={isCollapsed ? __('Aufklappen', 'recruiting-playbook') : __('Zuklappen', 'recruiting-playbook')}
                >
                    <span className={`dashicons dashicons-arrow-${isCollapsed ? 'down' : 'up'}-alt2`} />
                </button>
            </div>

            {!isCollapsed && (
                <div
                    ref={setNodeRef}
                    className="rp-kanban-column-content"
                >
                    <SortableContext
                        items={applications.map(a => a.id)}
                        strategy={verticalListSortingStrategy}
                    >
                        {applications.length === 0 ? (
                            <div className="rp-kanban-empty">
                                {__('Keine Bewerbungen', 'recruiting-playbook')}
                            </div>
                        ) : (
                            applications.map(app => (
                                <KanbanCard
                                    key={app.id}
                                    application={app}
                                />
                            ))
                        )}
                    </SortableContext>
                </div>
            )}
        </div>
    );
}
```

### KanbanCard.jsx

```jsx
/**
 * Kanban-Karte (Bewerber)
 */
import { __ } from '@wordpress/i18n';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

export function KanbanCard({ application }) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: application.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    const fullName = `${application.first_name} ${application.last_name}`;
    const initials = `${application.first_name[0]}${application.last_name[0]}`.toUpperCase();
    const daysAgo = Math.floor(
        (Date.now() - new Date(application.created_at).getTime()) / (1000 * 60 * 60 * 24)
    );

    const handleClick = (e) => {
        // Nicht öffnen wenn wir draggen
        if (isDragging) return;

        // Detail-Seite öffnen
        window.location.href = `${window.rpKanban.adminUrl}admin.php?page=rp-application&id=${application.id}`;
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`rp-kanban-card ${isDragging ? 'is-dragging' : ''}`}
            {...attributes}
            {...listeners}
            onClick={handleClick}
        >
            <div className="rp-kanban-card-header">
                <div className="rp-kanban-card-avatar">
                    {initials}
                </div>
                <div className="rp-kanban-card-info">
                    <div className="rp-kanban-card-name">
                        {fullName}
                    </div>
                    <div className="rp-kanban-card-email">
                        {application.email}
                    </div>
                </div>
            </div>

            <div className="rp-kanban-card-meta">
                <span className="rp-kanban-card-job" title={application.job_title}>
                    <span className="dashicons dashicons-businessman" />
                    {application.job_title}
                </span>
                <span className="rp-kanban-card-date">
                    <span className="dashicons dashicons-calendar-alt" />
                    {daysAgo === 0
                        ? __('Heute', 'recruiting-playbook')
                        : daysAgo === 1
                            ? __('Gestern', 'recruiting-playbook')
                            : sprintf(__('vor %d Tagen', 'recruiting-playbook'), daysAgo)
                    }
                </span>
            </div>

            {application.documents_count > 0 && (
                <div className="rp-kanban-card-documents">
                    <span className="dashicons dashicons-media-document" />
                    {application.documents_count}
                </div>
            )}
        </div>
    );
}
```

### hooks/useApplications.js

```jsx
/**
 * Custom Hook für Bewerbungen
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export function useApplications() {
    const [applications, setApplications] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchApplications = useCallback(async () => {
        try {
            setLoading(true);
            setError(null);

            const data = await apiFetch({
                path: '/recruiting/v1/applications?per_page=100&include_kanban=1',
            });

            setApplications(data.items || data);
        } catch (err) {
            setError(err.message || 'Fehler beim Laden');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchApplications();
    }, [fetchApplications]);

    const moveApplication = useCallback(async (id, newStatus, newPosition = null) => {
        // Optimistic Update
        setApplications(prev =>
            prev.map(app =>
                app.id === id
                    ? { ...app, status: newStatus }
                    : app
            )
        );

        try {
            await apiFetch({
                path: `/recruiting/v1/applications/${id}/status`,
                method: 'PATCH',
                data: {
                    status: newStatus,
                    kanban_position: newPosition,
                },
            });
        } catch (err) {
            // Rollback bei Fehler
            fetchApplications();
            throw err;
        }
    }, [fetchApplications]);

    return {
        applications,
        loading,
        error,
        moveApplication,
        refetch: fetchApplications,
    };
}
```

---

## 7. Drag-and-Drop Implementierung

### Bibliothek: @dnd-kit

Wir verwenden `@dnd-kit` statt `react-beautiful-dnd` weil:

- Aktiv gepflegt (react-beautiful-dnd ist deprecated)
- Bessere Accessibility (ARIA, Keyboard)
- Kleiner Bundle-Size
- Modularer Aufbau

### Installation

```bash
npm install @dnd-kit/core @dnd-kit/sortable @dnd-kit/utilities
```

### Collision Detection

```jsx
import { closestCenter, closestCorners, rectIntersection } from '@dnd-kit/core';

// closestCenter: Für vertikale Listen
// closestCorners: Für Cross-Container Drops
// rectIntersection: Für komplexe Layouts
```

### Keyboard-Navigation

```jsx
import { KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { sortableKeyboardCoordinates } from '@dnd-kit/sortable';

const sensors = useSensors(
    useSensor(PointerSensor, {
        activationConstraint: {
            distance: 8, // Mindest-Bewegung bevor Drag startet
        },
    }),
    useSensor(KeyboardSensor, {
        coordinateGetter: sortableKeyboardCoordinates,
    })
);
```

---

## 8. Echtzeit-Updates

### Polling (MVP)

```jsx
// In useApplications.js
useEffect(() => {
    const interval = setInterval(fetchApplications, 30000); // Alle 30s
    return () => clearInterval(interval);
}, [fetchApplications]);
```

### Server-Sent Events (Future)

```php
// Future: SSE Endpoint für Live-Updates
register_rest_route(
    'recruiting/v1',
    '/kanban/events',
    [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => [ $this, 'stream_events' ],
    ]
);
```

---

## 9. Performance-Optimierung

### Virtualisierung bei vielen Karten

```jsx
import { FixedSizeList } from 'react-window';

// Bei > 50 Karten pro Spalte virtualisieren
{applications.length > 50 ? (
    <FixedSizeList
        height={600}
        width="100%"
        itemCount={applications.length}
        itemSize={80}
    >
        {({ index, style }) => (
            <div style={style}>
                <KanbanCard application={applications[index]} />
            </div>
        )}
    </FixedSizeList>
) : (
    applications.map(app => <KanbanCard key={app.id} application={app} />)
)}
```

### Lazy Loading

```php
// Nur sichtbare Felder laden
public function get_kanban_applications(): array {
    global $wpdb;

    return $wpdb->get_results(
        "SELECT
            a.id,
            a.status,
            a.kanban_position,
            a.created_at,
            c.first_name,
            c.last_name,
            c.email,
            p.post_title as job_title,
            (SELECT COUNT(*) FROM {$wpdb->prefix}rp_documents d WHERE d.application_id = a.id) as documents_count
        FROM {$wpdb->prefix}rp_applications a
        JOIN {$wpdb->prefix}rp_candidates c ON a.candidate_id = c.id
        JOIN {$wpdb->prefix}posts p ON a.job_id = p.ID
        WHERE a.deleted_at IS NULL
        ORDER BY a.status, a.kanban_position ASC",
        ARRAY_A
    );
}
```

### Debounced Reorder

```jsx
import { useDebouncedCallback } from 'use-debounce';

const debouncedReorder = useDebouncedCallback(
    async (moves) => {
        await apiFetch({
            path: '/recruiting/v1/kanban/reorder',
            method: 'POST',
            data: { moves },
        });
    },
    500 // 500ms Debounce
);
```

---

## 10. Barrierefreiheit

### ARIA-Attribute

```jsx
<div
    role="listbox"
    aria-label={__('Kanban-Board', 'recruiting-playbook')}
>
    <div
        role="group"
        aria-label={columnLabel}
    >
        <div
            role="option"
            aria-selected={isDragging}
            aria-describedby={`card-${id}-description`}
        >
            <span id={`card-${id}-description`} className="screen-reader-text">
                {sprintf(
                    __('%s, Status: %s, Position %d von %d', 'recruiting-playbook'),
                    fullName,
                    statusLabel,
                    position,
                    total
                )}
            </span>
        </div>
    </div>
</div>
```

### Keyboard-Shortcuts

| Taste | Aktion |
|-------|--------|
| `Tab` | Zur nächsten Karte |
| `Space` / `Enter` | Karte auswählen/Drag starten |
| `Arrow Keys` | Karte verschieben |
| `Escape` | Drag abbrechen |

---

## 11. Testing

### Unit Tests (Jest)

```jsx
// __tests__/KanbanCard.test.jsx
import { render, screen } from '@testing-library/react';
import { KanbanCard } from '../KanbanCard';

describe('KanbanCard', () => {
    const mockApplication = {
        id: 1,
        first_name: 'Max',
        last_name: 'Mustermann',
        email: 'max@example.com',
        job_title: 'Developer',
        created_at: new Date().toISOString(),
        documents_count: 2,
    };

    it('renders applicant name', () => {
        render(<KanbanCard application={mockApplication} />);
        expect(screen.getByText('Max Mustermann')).toBeInTheDocument();
    });

    it('shows document count', () => {
        render(<KanbanCard application={mockApplication} />);
        expect(screen.getByText('2')).toBeInTheDocument();
    });
});
```

### E2E Tests (Playwright)

```js
// e2e/kanban.spec.js
import { test, expect } from '@playwright/test';

test.describe('Kanban Board', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/wp-admin/admin.php?page=rp-kanban');
    });

    test('can drag card to new column', async ({ page }) => {
        const card = page.locator('.rp-kanban-card').first();
        const targetColumn = page.locator('[data-status="screening"]');

        await card.dragTo(targetColumn);

        await expect(card).toHaveAttribute('data-status', 'screening');
    });

    test('shows upgrade notice for free users', async ({ page }) => {
        // Als Free-User einloggen
        await expect(page.locator('.rp-upgrade-prompt')).toBeVisible();
    });
});
```

---

## Deliverables

| Item | Beschreibung | Kriterium |
|------|--------------|-----------|
| Feature-Gate | `rp_can('kanban_board')` Check | ✅ Free-User sieht Upgrade-Hinweis |
| Admin-Seite | Menüpunkt + React-Container | ✅ Navigierbar |
| REST API | Reorder-Endpoint | ✅ Status-Updates funktionieren |
| Spalten | 7 Status-Spalten angezeigt | ✅ Alle Status sichtbar |
| Karten | Bewerber als Karten | ✅ Name, E-Mail, Job, Datum |
| Drag-and-Drop | Zwischen Spalten verschieben | ✅ Status wird aktualisiert |
| Filter | Nach Stelle + Suche | ✅ Filter funktionieren |
| Accessibility | Keyboard-Navigation | ✅ WCAG 2.1 AA |
| Performance | < 100 Karten flüssig | ✅ Keine Lags |
| Tests | Unit + E2E | ✅ 80% Coverage |

---

## Branch-Strategie

```
feature/pro
    └── feature/kanban-board
            ├── Commit 1: Admin-Seite + Feature-Gate
            ├── Commit 2: React-Setup + API
            ├── Commit 3: Drag-and-Drop
            ├── Commit 4: Styling + Accessibility
            └── Commit 5: Tests
```

Nach Fertigstellung:
```
feature/kanban-board → feature/pro (Merge)
```

---

## Nächste Features

Nach erfolgreichem Abschluss des Kanban-Boards:

→ **Email-Templates** (Pro-Feature)
- WYSIWYG-Editor für E-Mail-Vorlagen
- Platzhalter für Bewerber-Daten

→ **Benutzerrollen** (Pro-Feature)
- Recruiter, Hiring Manager Rollen
- Stellen-Zuweisung pro User

---

*Technische Spezifikation erstellt: Januar 2025*

# Phase 1C: Technische Spezifikation

> **Woche 5-6: Admin-Basics**
> HR kann Bewerbungen verwalten, Daten sind sicher

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [Bewerber-Listenansicht](#2-bewerber-listenansicht)
3. [Bewerber-Detailseite](#3-bewerber-detailseite)
4. [Dokument-Handling](#4-dokument-handling)
5. [Status-Management](#5-status-management)
6. [Backup-Export](#6-backup-export)
7. [DSGVO-Funktionen](#7-dsgvo-funktionen)
8. [REST API Erweiterungen](#8-rest-api-erweiterungen)
9. [Admin-Assets](#9-admin-assets)
10. [Deliverables](#10-deliverables)

---

## 1. Übersicht

### Ziele Phase 1C

| Ziel | Beschreibung |
|------|--------------|
| Bewerbungsverwaltung | HR kann alle Bewerbungen einsehen und verwalten |
| Dokumenten-Download | Sichere, token-basierte Downloads |
| Status-Workflow | Einfaches Status-Management mit Logging |
| Datenexport | Backup aller Plugin-Daten als JSON |
| DSGVO-Compliance | Löschfunktionen und Datenauskunft |

### Voraussetzungen (aus Phase 1A/1B)

- [x] Custom Post Type `job_listing`
- [x] Datenbank-Tabellen (`rp_applications`, `rp_candidates`, `rp_documents`, `rp_activity_log`)
- [x] REST API Endpoint für Bewerbungen
- [x] Dokument-Upload-System
- [x] E-Mail-Benachrichtigungen

### Neue Dateien

```
plugin/src/
├── Admin/
│   ├── Pages/
│   │   ├── ApplicationList.php      # WP_List_Table
│   │   └── ApplicationDetail.php    # Detailansicht
│   └── Export/
│       └── BackupExporter.php       # JSON-Export
├── Services/
│   ├── DocumentDownloadService.php  # Sichere Downloads
│   └── GdprService.php              # DSGVO-Funktionen
└── Api/
    └── ExportController.php         # Export-Endpoints
```

---

## 2. Bewerber-Listenansicht

### ApplicationList.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

use RecruitingPlaybook\Constants\ApplicationStatus;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Bewerbungen-Listenansicht (WP_List_Table)
 */
class ApplicationList extends \WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct([
            'singular' => __('Bewerbung', 'recruiting-playbook'),
            'plural'   => __('Bewerbungen', 'recruiting-playbook'),
            'ajax'     => false,
        ]);
    }

    /**
     * Spalten definieren
     */
    public function get_columns(): array {
        return [
            'cb'         => '<input type="checkbox" />',
            'applicant'  => __('Bewerber', 'recruiting-playbook'),
            'job'        => __('Stelle', 'recruiting-playbook'),
            'status'     => __('Status', 'recruiting-playbook'),
            'documents'  => __('Dokumente', 'recruiting-playbook'),
            'created_at' => __('Eingegangen', 'recruiting-playbook'),
            'actions'    => __('Aktionen', 'recruiting-playbook'),
        ];
    }

    /**
     * Sortierbare Spalten
     */
    public function get_sortable_columns(): array {
        return [
            'applicant'  => ['last_name', false],
            'job'        => ['job_id', false],
            'status'     => ['status', false],
            'created_at' => ['created_at', true], // Default: absteigend
        ];
    }

    /**
     * Bulk-Actions definieren
     */
    public function get_bulk_actions(): array {
        return [
            'bulk_screening' => __('Status: In Prüfung', 'recruiting-playbook'),
            'bulk_rejected'  => __('Status: Abgelehnt', 'recruiting-playbook'),
            'bulk_delete'    => __('Löschen', 'recruiting-playbook'),
        ];
    }

    /**
     * Daten vorbereiten
     */
    public function prepare_items(): void {
        global $wpdb;

        // Spalten
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        // Bulk Actions verarbeiten
        $this->process_bulk_action();

        // Daten laden
        $per_page     = $this->get_items_per_page('rp_applications_per_page', 20);
        $current_page = $this->get_pagenum();
        $offset       = ($current_page - 1) * $per_page;

        // Filter
        $where = $this->build_where_clause();

        // Sortierung
        $orderby = $this->get_orderby();
        $order   = $this->get_order();

        // Query
        $applications_table = $wpdb->prefix . 'rp_applications';
        $candidates_table   = $wpdb->prefix . 'rp_candidates';

        // Total Items
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_items = (int) $wpdb->get_var(
            "SELECT COUNT(a.id)
             FROM {$applications_table} a
             LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id
             {$where}"
        );

        // Items laden
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, c.first_name, c.last_name, c.email, c.phone
                 FROM {$applications_table} a
                 LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id
                 {$where}
                 ORDER BY {$orderby} {$order}
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        // Pagination
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    /**
     * WHERE-Klausel bauen
     */
    private function build_where_clause(): string {
        global $wpdb;

        $conditions = ['1=1'];

        // Status-Filter
        if (!empty($_GET['status'])) {
            $status = sanitize_text_field(wp_unslash($_GET['status']));
            $conditions[] = $wpdb->prepare('a.status = %s', $status);
        }

        // Job-Filter
        if (!empty($_GET['job_id'])) {
            $job_id = absint($_GET['job_id']);
            $conditions[] = $wpdb->prepare('a.job_id = %d', $job_id);
        }

        // Suche
        if (!empty($_GET['s'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field(wp_unslash($_GET['s']))) . '%';
            $conditions[] = $wpdb->prepare(
                '(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)',
                $search,
                $search,
                $search
            );
        }

        // Zeitraum-Filter
        if (!empty($_GET['date_from'])) {
            $date_from = sanitize_text_field(wp_unslash($_GET['date_from']));
            $conditions[] = $wpdb->prepare('DATE(a.created_at) >= %s', $date_from);
        }

        if (!empty($_GET['date_to'])) {
            $date_to = sanitize_text_field(wp_unslash($_GET['date_to']));
            $conditions[] = $wpdb->prepare('DATE(a.created_at) <= %s', $date_to);
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Sortierung ermitteln
     */
    private function get_orderby(): string {
        $allowed = ['last_name', 'job_id', 'status', 'created_at'];
        $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'created_at';

        return in_array($orderby, $allowed, true) ? "a.{$orderby}" : 'a.created_at';
    }

    private function get_order(): string {
        $order = isset($_GET['order']) ? strtoupper(sanitize_text_field(wp_unslash($_GET['order']))) : 'DESC';
        return in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';
    }

    /**
     * Checkbox-Spalte
     */
    public function column_cb($item): string {
        return sprintf(
            '<input type="checkbox" name="application_ids[]" value="%d" />',
            absint($item['id'])
        );
    }

    /**
     * Bewerber-Spalte
     */
    public function column_applicant($item): string {
        $name = esc_html(trim($item['first_name'] . ' ' . $item['last_name']));
        if (empty($name)) {
            $name = esc_html($item['email']);
        }

        $detail_url = admin_url(sprintf(
            'admin.php?page=rp-application-detail&id=%d',
            absint($item['id'])
        ));

        $output = sprintf('<strong><a href="%s">%s</a></strong>', esc_url($detail_url), $name);
        $output .= '<br><small>' . esc_html($item['email']) . '</small>';

        if (!empty($item['phone'])) {
            $output .= '<br><small>' . esc_html($item['phone']) . '</small>';
        }

        return $output;
    }

    /**
     * Stellen-Spalte
     */
    public function column_job($item): string {
        $job = get_post($item['job_id']);
        if (!$job) {
            return '<em>' . esc_html__('Gelöscht', 'recruiting-playbook') . '</em>';
        }

        return sprintf(
            '<a href="%s">%s</a>',
            esc_url(get_edit_post_link($job->ID)),
            esc_html($job->post_title)
        );
    }

    /**
     * Status-Spalte
     */
    public function column_status($item): string {
        $status = $item['status'];
        $labels = ApplicationStatus::getAll();
        $colors = ApplicationStatus::getColors();

        $label = $labels[$status] ?? $status;
        $color = $colors[$status] ?? '#787c82';

        return sprintf(
            '<span class="rp-status-badge" style="background-color: %s; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">%s</span>',
            esc_attr($color),
            esc_html($label)
        );
    }

    /**
     * Dokumente-Spalte
     */
    public function column_documents($item): string {
        global $wpdb;

        $documents_table = $wpdb->prefix . 'rp_documents';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$documents_table} WHERE application_id = %d",
            absint($item['id'])
        ));

        if ($count === 0) {
            return '<span class="dashicons dashicons-media-default" style="color: #ccc;"></span> 0';
        }

        return sprintf(
            '<span class="dashicons dashicons-media-document" style="color: #2271b1;"></span> %d',
            $count
        );
    }

    /**
     * Datum-Spalte
     */
    public function column_created_at($item): string {
        $date = strtotime($item['created_at']);
        $human_diff = human_time_diff($date, current_time('timestamp'));

        return sprintf(
            '<span title="%s">%s</span>',
            esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $date)),
            /* translators: %s: Human time difference */
            sprintf(esc_html__('vor %s', 'recruiting-playbook'), $human_diff)
        );
    }

    /**
     * Aktionen-Spalte
     */
    public function column_actions($item): string {
        $detail_url = admin_url(sprintf(
            'admin.php?page=rp-application-detail&id=%d',
            absint($item['id'])
        ));

        $actions = [];

        $actions[] = sprintf(
            '<a href="%s" class="button button-small">%s</a>',
            esc_url($detail_url),
            esc_html__('Ansehen', 'recruiting-playbook')
        );

        // Quick-Status-Buttons
        if ($item['status'] === 'new') {
            $screening_url = wp_nonce_url(
                admin_url(sprintf(
                    'admin.php?page=rp-applications&action=set_status&id=%d&status=screening',
                    absint($item['id'])
                )),
                'rp_set_status_' . $item['id']
            );

            $actions[] = sprintf(
                '<a href="%s" class="button button-small" style="background: #dba617; border-color: #dba617; color: white;">%s</a>',
                esc_url($screening_url),
                esc_html__('Prüfen', 'recruiting-playbook')
            );
        }

        return implode(' ', $actions);
    }

    /**
     * Keine Items Meldung
     */
    public function no_items(): void {
        esc_html_e('Keine Bewerbungen gefunden.', 'recruiting-playbook');
    }

    /**
     * Extra Tablenav (Filter)
     */
    protected function extra_tablenav($which): void {
        if ($which !== 'top') {
            return;
        }

        echo '<div class="alignleft actions">';

        // Status-Filter
        $current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        echo '<select name="status">';
        echo '<option value="">' . esc_html__('Alle Status', 'recruiting-playbook') . '</option>';
        foreach (ApplicationStatus::getAll() as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current_status, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';

        // Job-Filter
        $jobs = get_posts([
            'post_type'      => 'job_listing',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $current_job = isset($_GET['job_id']) ? absint($_GET['job_id']) : 0;
        echo '<select name="job_id">';
        echo '<option value="">' . esc_html__('Alle Stellen', 'recruiting-playbook') . '</option>';
        foreach ($jobs as $job) {
            printf(
                '<option value="%d" %s>%s</option>',
                $job->ID,
                selected($current_job, $job->ID, false),
                esc_html($job->post_title)
            );
        }
        echo '</select>';

        // Datum-Filter
        $date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to   = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

        echo '<input type="date" name="date_from" value="' . esc_attr($date_from) . '" placeholder="' . esc_attr__('Von', 'recruiting-playbook') . '" />';
        echo '<input type="date" name="date_to" value="' . esc_attr($date_to) . '" placeholder="' . esc_attr__('Bis', 'recruiting-playbook') . '" />';

        submit_button(__('Filtern', 'recruiting-playbook'), '', 'filter_action', false);

        echo '</div>';
    }

    /**
     * Bulk Actions verarbeiten
     */
    public function process_bulk_action(): void {
        $action = $this->current_action();

        if (!$action || empty($_REQUEST['application_ids'])) {
            return;
        }

        // Nonce prüfen
        check_admin_referer('bulk-' . $this->_args['plural']);

        $ids = array_map('absint', $_REQUEST['application_ids']);

        global $wpdb;
        $table = $wpdb->prefix . 'rp_applications';

        switch ($action) {
            case 'bulk_screening':
                foreach ($ids as $id) {
                    $wpdb->update($table, ['status' => 'screening'], ['id' => $id]);
                    $this->log_status_change($id, 'screening');
                }
                break;

            case 'bulk_rejected':
                foreach ($ids as $id) {
                    $wpdb->update($table, ['status' => 'rejected'], ['id' => $id]);
                    $this->log_status_change($id, 'rejected');
                }
                break;

            case 'bulk_delete':
                foreach ($ids as $id) {
                    $this->delete_application($id);
                }
                break;
        }

        // Redirect ohne Action-Parameter
        wp_safe_redirect(remove_query_arg(['action', 'action2', 'application_ids', '_wpnonce']));
        exit;
    }

    /**
     * Status-Änderung loggen
     */
    private function log_status_change(int $application_id, string $new_status): void {
        global $wpdb;

        $log_table = $wpdb->prefix . 'rp_activity_log';
        $current_user = wp_get_current_user();

        $wpdb->insert($log_table, [
            'object_type' => 'application',
            'object_id'   => $application_id,
            'action'      => 'status_changed',
            'user_id'     => $current_user->ID,
            'user_name'   => $current_user->display_name,
            'new_value'   => $new_status,
            'created_at'  => current_time('mysql'),
        ]);
    }

    /**
     * Bewerbung löschen (Soft-Delete)
     */
    private function delete_application(int $id): void {
        // Implementiert in GdprService
        $gdpr_service = new \RecruitingPlaybook\Services\GdprService();
        $gdpr_service->softDeleteApplication($id);
    }
}
```

### Integration in Menu.php

```php
// In Admin/Menu.php - renderApplications() aktualisieren

public function renderApplications(): void {
    // Einzelne Aktionen verarbeiten
    $this->processApplicationActions();

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">' . esc_html__('Bewerbungen', 'recruiting-playbook') . '</h1>';

    // Export-Button
    echo '<a href="' . esc_url(admin_url('admin.php?page=rp-export')) . '" class="page-title-action">';
    echo esc_html__('Exportieren', 'recruiting-playbook');
    echo '</a>';

    echo '<hr class="wp-header-end">';

    // Status-Übersicht
    $this->renderStatusCounts();

    // Liste rendern
    $list_table = new Pages\ApplicationList();
    $list_table->prepare_items();

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="rp-applications" />';
    $list_table->search_box(__('Suchen', 'recruiting-playbook'), 'search');
    $list_table->display();
    echo '</form>';

    echo '</div>';
}

/**
 * Status-Zähler anzeigen
 */
private function renderStatusCounts(): void {
    global $wpdb;

    $table = $wpdb->prefix . 'rp_applications';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $counts = $wpdb->get_results(
        "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
        OBJECT_K
    );

    $statuses = ApplicationStatus::getAll();
    $colors   = ApplicationStatus::getColors();

    echo '<ul class="subsubsub" style="margin-bottom: 15px;">';

    $links = [];
    $total = 0;

    foreach ($statuses as $status => $label) {
        $count = isset($counts[$status]) ? (int) $counts[$status]->count : 0;
        $total += $count;

        $url = add_query_arg('status', $status, admin_url('admin.php?page=rp-applications'));

        $links[] = sprintf(
            '<li><a href="%s" style="color: %s;">%s</a> <span class="count">(%d)</span></li>',
            esc_url($url),
            esc_attr($colors[$status]),
            esc_html($label),
            $count
        );
    }

    // "Alle" Link am Anfang
    array_unshift($links, sprintf(
        '<li><a href="%s"><strong>%s</strong></a> <span class="count">(%d)</span> |</li>',
        esc_url(admin_url('admin.php?page=rp-applications')),
        esc_html__('Alle', 'recruiting-playbook'),
        $total
    ));

    echo implode(' | ', $links);
    echo '</ul>';
    echo '<div class="clear"></div>';
}

/**
 * Einzelaktionen verarbeiten
 */
private function processApplicationActions(): void {
    if (empty($_GET['action']) || empty($_GET['id'])) {
        return;
    }

    $action = sanitize_text_field(wp_unslash($_GET['action']));
    $id     = absint($_GET['id']);

    if ($action === 'set_status' && !empty($_GET['status'])) {
        check_admin_referer('rp_set_status_' . $id);

        $status = sanitize_text_field(wp_unslash($_GET['status']));

        global $wpdb;
        $table = $wpdb->prefix . 'rp_applications';

        $wpdb->update($table, ['status' => $status], ['id' => $id]);

        // Logging
        $this->logStatusChange($id, $status);

        // Redirect
        wp_safe_redirect(admin_url('admin.php?page=rp-applications&updated=1'));
        exit;
    }
}
```

---

## 3. Bewerber-Detailseite

### ApplicationDetail.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

use RecruitingPlaybook\Constants\ApplicationStatus;
use RecruitingPlaybook\Constants\DocumentType;
use RecruitingPlaybook\Services\DocumentDownloadService;

/**
 * Detailansicht einer Bewerbung
 */
class ApplicationDetail {

    /**
     * Seite rendern
     */
    public function render(): void {
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        if (!$id) {
            wp_die(__('Keine Bewerbung angegeben.', 'recruiting-playbook'));
        }

        $application = $this->getApplication($id);

        if (!$application) {
            wp_die(__('Bewerbung nicht gefunden.', 'recruiting-playbook'));
        }

        // Status-Update verarbeiten
        $this->processStatusUpdate($id);

        // Kandidaten-Daten
        $candidate = $this->getCandidate($application['candidate_id']);
        $job = get_post($application['job_id']);
        $documents = $this->getDocuments($id);
        $activity_log = $this->getActivityLog($id);

        ?>
        <div class="wrap rp-application-detail">
            <h1>
                <?php
                printf(
                    /* translators: %s: Applicant name */
                    esc_html__('Bewerbung von %s', 'recruiting-playbook'),
                    esc_html($candidate['first_name'] . ' ' . $candidate['last_name'])
                );
                ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=rp-applications')); ?>" class="page-title-action">
                    <?php esc_html_e('Zurück zur Liste', 'recruiting-playbook'); ?>
                </a>
            </h1>

            <div class="rp-detail-grid">
                <!-- Hauptbereich -->
                <div class="rp-detail-main">
                    <!-- Kontaktdaten -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Kontaktdaten', 'recruiting-playbook'); ?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e('Name', 'recruiting-playbook'); ?></th>
                                    <td><?php echo esc_html($candidate['first_name'] . ' ' . $candidate['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('E-Mail', 'recruiting-playbook'); ?></th>
                                    <td>
                                        <a href="mailto:<?php echo esc_attr($candidate['email']); ?>">
                                            <?php echo esc_html($candidate['email']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php if (!empty($candidate['phone'])) : ?>
                                <tr>
                                    <th><?php esc_html_e('Telefon', 'recruiting-playbook'); ?></th>
                                    <td>
                                        <a href="tel:<?php echo esc_attr($candidate['phone']); ?>">
                                            <?php echo esc_html($candidate['phone']); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Anschreiben -->
                    <?php if (!empty($application['cover_letter'])) : ?>
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Anschreiben', 'recruiting-playbook'); ?></h2>
                        <div class="inside">
                            <div class="rp-cover-letter">
                                <?php echo wp_kses_post(nl2br($application['cover_letter'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Dokumente -->
                    <div class="postbox">
                        <h2 class="hndle">
                            <?php esc_html_e('Dokumente', 'recruiting-playbook'); ?>
                            <span class="count">(<?php echo count($documents); ?>)</span>
                        </h2>
                        <div class="inside">
                            <?php if (empty($documents)) : ?>
                                <p class="description">
                                    <?php esc_html_e('Keine Dokumente hochgeladen.', 'recruiting-playbook'); ?>
                                </p>
                            <?php else : ?>
                                <table class="widefat striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Dokument', 'recruiting-playbook'); ?></th>
                                            <th><?php esc_html_e('Typ', 'recruiting-playbook'); ?></th>
                                            <th><?php esc_html_e('Größe', 'recruiting-playbook'); ?></th>
                                            <th><?php esc_html_e('Aktion', 'recruiting-playbook'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documents as $doc) : ?>
                                            <?php
                                            $download_url = DocumentDownloadService::generateDownloadUrl($doc['id']);
                                            $type_labels = DocumentType::getAll();
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="dashicons dashicons-media-document"></span>
                                                    <?php echo esc_html($doc['original_name']); ?>
                                                </td>
                                                <td><?php echo esc_html($type_labels[$doc['type']] ?? $doc['type']); ?></td>
                                                <td><?php echo esc_html(size_format($doc['size'])); ?></td>
                                                <td>
                                                    <a href="<?php echo esc_url($download_url); ?>" class="button button-small">
                                                        <?php esc_html_e('Download', 'recruiting-playbook'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Aktivitäts-Log -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Verlauf', 'recruiting-playbook'); ?></h2>
                        <div class="inside">
                            <?php if (empty($activity_log)) : ?>
                                <p class="description">
                                    <?php esc_html_e('Keine Aktivitäten aufgezeichnet.', 'recruiting-playbook'); ?>
                                </p>
                            <?php else : ?>
                                <ul class="rp-activity-log">
                                    <?php foreach ($activity_log as $entry) : ?>
                                        <li>
                                            <span class="rp-log-time">
                                                <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($entry['created_at']))); ?>
                                            </span>
                                            <span class="rp-log-user">
                                                <?php echo esc_html($entry['user_name'] ?: __('System', 'recruiting-playbook')); ?>
                                            </span>
                                            <span class="rp-log-action">
                                                <?php echo esc_html($this->formatLogAction($entry)); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="rp-detail-sidebar">
                    <!-- Status -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Status', 'recruiting-playbook'); ?></h2>
                        <div class="inside">
                            <form method="post">
                                <?php wp_nonce_field('rp_update_status_' . $id); ?>
                                <input type="hidden" name="application_id" value="<?php echo esc_attr($id); ?>" />

                                <p>
                                    <select name="status" id="rp-status-select" style="width: 100%;">
                                        <?php foreach (ApplicationStatus::getAll() as $value => $label) : ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($application['status'], $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>

                                <p>
                                    <button type="submit" name="update_status" class="button button-primary" style="width: 100%;">
                                        <?php esc_html_e('Status aktualisieren', 'recruiting-playbook'); ?>
                                    </button>
                                </p>
                            </form>
                        </div>
                    </div>

                    <!-- Stelle -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Stelle', 'recruiting-playbook'); ?></h2>
                        <div class="inside">
                            <?php if ($job) : ?>
                                <p>
                                    <strong><?php echo esc_html($job->post_title); ?></strong>
                                </p>
                                <p>
                                    <a href="<?php echo esc_url(get_edit_post_link($job->ID)); ?>" class="button button-small">
                                        <?php esc_html_e('Bearbeiten', 'recruiting-playbook'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(get_permalink($job->ID)); ?>" class="button button-small" target="_blank">
                                        <?php esc_html_e('Ansehen', 'recruiting-playbook'); ?>
                                    </a>
                                </p>
                            <?php else : ?>
                                <p class="description">
                                    <?php esc_html_e('Stelle wurde gelöscht.', 'recruiting-playbook'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Meta-Daten -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('Details', 'recruiting-playbook'); ?></h2>
                        <div class="inside">
                            <p>
                                <strong><?php esc_html_e('Eingegangen:', 'recruiting-playbook'); ?></strong><br>
                                <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($application['created_at']))); ?>
                            </p>
                            <p>
                                <strong><?php esc_html_e('Letzte Änderung:', 'recruiting-playbook'); ?></strong><br>
                                <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($application['updated_at']))); ?>
                            </p>
                            <?php if (!empty($application['source_url'])) : ?>
                            <p>
                                <strong><?php esc_html_e('Quelle:', 'recruiting-playbook'); ?></strong><br>
                                <small><?php echo esc_url($application['source_url']); ?></small>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- DSGVO-Aktionen -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e('DSGVO', 'recruiting-playbook'); ?></h2>
                        <div class="inside">
                            <p>
                                <a href="<?php echo esc_url(wp_nonce_url(
                                    admin_url('admin.php?page=rp-applications&action=export_data&id=' . $id),
                                    'rp_export_data_' . $id
                                )); ?>" class="button button-small" style="width: 100%; text-align: center;">
                                    <?php esc_html_e('Daten exportieren', 'recruiting-playbook'); ?>
                                </a>
                            </p>
                            <p>
                                <a href="<?php echo esc_url(wp_nonce_url(
                                    admin_url('admin.php?page=rp-applications&action=delete&id=' . $id),
                                    'rp_delete_' . $id
                                )); ?>" class="button button-small button-link-delete" style="width: 100%; text-align: center;"
                                   onclick="return confirm('<?php esc_attr_e('Diese Bewerbung wirklich löschen?', 'recruiting-playbook'); ?>');">
                                    <?php esc_html_e('Bewerbung löschen', 'recruiting-playbook'); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .rp-detail-grid {
                display: grid;
                grid-template-columns: 1fr 300px;
                gap: 20px;
                margin-top: 20px;
            }
            .rp-detail-main .postbox,
            .rp-detail-sidebar .postbox {
                margin-bottom: 20px;
            }
            .rp-cover-letter {
                background: #f6f7f7;
                padding: 15px;
                border-radius: 4px;
                white-space: pre-wrap;
            }
            .rp-activity-log {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .rp-activity-log li {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            .rp-activity-log li:last-child {
                border-bottom: none;
            }
            .rp-log-time {
                color: #666;
                font-size: 12px;
            }
            .rp-log-user {
                font-weight: 500;
            }
            @media screen and (max-width: 960px) {
                .rp-detail-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }

    /**
     * Bewerbung laden
     */
    private function getApplication(int $id): ?array {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_applications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * Kandidat laden
     */
    private function getCandidate(int $id): ?array {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_candidates';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );
    }

    /**
     * Dokumente laden
     */
    private function getDocuments(int $application_id): array {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_documents';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE application_id = %d ORDER BY created_at ASC",
                $application_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Aktivitäts-Log laden
     */
    private function getActivityLog(int $application_id): array {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_activity_log';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE object_type = 'application' AND object_id = %d
                 ORDER BY created_at DESC
                 LIMIT 50",
                $application_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Status-Update verarbeiten
     */
    private function processStatusUpdate(int $id): void {
        if (!isset($_POST['update_status'])) {
            return;
        }

        check_admin_referer('rp_update_status_' . $id);

        $new_status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

        if (!array_key_exists($new_status, ApplicationStatus::getAll())) {
            return;
        }

        global $wpdb;

        // Alten Status holen
        $table = $wpdb->prefix . 'rp_applications';
        $old_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$table} WHERE id = %d",
            $id
        ));

        if ($old_status === $new_status) {
            return;
        }

        // Update
        $wpdb->update($table, ['status' => $new_status], ['id' => $id]);

        // Logging
        $log_table = $wpdb->prefix . 'rp_activity_log';
        $current_user = wp_get_current_user();

        $wpdb->insert($log_table, [
            'object_type' => 'application',
            'object_id'   => $id,
            'action'      => 'status_changed',
            'user_id'     => $current_user->ID,
            'user_name'   => $current_user->display_name,
            'old_value'   => $old_status,
            'new_value'   => $new_status,
            'created_at'  => current_time('mysql'),
        ]);

        // Success-Notice
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>';
            esc_html_e('Status wurde aktualisiert.', 'recruiting-playbook');
            echo '</p></div>';
        });
    }

    /**
     * Log-Aktion formatieren
     */
    private function formatLogAction(array $entry): string {
        $action = $entry['action'];

        switch ($action) {
            case 'status_changed':
                $old_label = ApplicationStatus::getAll()[$entry['old_value']] ?? $entry['old_value'];
                $new_label = ApplicationStatus::getAll()[$entry['new_value']] ?? $entry['new_value'];
                return sprintf(
                    /* translators: 1: Old status, 2: New status */
                    __('Status geändert: %1$s → %2$s', 'recruiting-playbook'),
                    $old_label,
                    $new_label
                );

            case 'created':
                return __('Bewerbung eingegangen', 'recruiting-playbook');

            case 'document_downloaded':
                return __('Dokument heruntergeladen', 'recruiting-playbook');

            case 'email_sent':
                return __('E-Mail gesendet', 'recruiting-playbook');

            default:
                return $action;
        }
    }
}
```

---

## 4. Dokument-Handling

### DocumentDownloadService.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

/**
 * Sichere Dokument-Downloads mit Token-Validierung
 */
class DocumentDownloadService {

    /**
     * Token-Gültigkeitsdauer in Sekunden (1 Stunde)
     */
    private const TOKEN_EXPIRY = 3600;

    /**
     * Download-URL generieren
     */
    public static function generateDownloadUrl(int $document_id): string {
        $token = self::generateToken($document_id);

        return admin_url(sprintf(
            'admin-ajax.php?action=rp_download_document&id=%d&token=%s',
            $document_id,
            $token
        ));
    }

    /**
     * Token generieren
     */
    private static function generateToken(int $document_id): string {
        $user_id = get_current_user_id();
        $expiry  = time() + self::TOKEN_EXPIRY;

        $data = sprintf('%d:%d:%d', $document_id, $user_id, $expiry);
        $hash = hash_hmac('sha256', $data, wp_salt('auth'));

        return base64_encode($data . ':' . $hash);
    }

    /**
     * Token validieren
     */
    public static function validateToken(int $document_id, string $token): bool {
        $decoded = base64_decode($token);

        if (!$decoded || substr_count($decoded, ':') !== 3) {
            return false;
        }

        list($token_doc_id, $token_user_id, $expiry, $hash) = explode(':', $decoded);

        // Dokument-ID prüfen
        if ((int) $token_doc_id !== $document_id) {
            return false;
        }

        // Ablauf prüfen
        if ((int) $expiry < time()) {
            return false;
        }

        // User prüfen
        if ((int) $token_user_id !== get_current_user_id()) {
            return false;
        }

        // Hash prüfen
        $data = sprintf('%d:%d:%d', $token_doc_id, $token_user_id, $expiry);
        $expected_hash = hash_hmac('sha256', $data, wp_salt('auth'));

        return hash_equals($expected_hash, $hash);
    }

    /**
     * Download ausführen
     */
    public static function serveDownload(int $document_id): void {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_documents';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $document = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $document_id),
            ARRAY_A
        );

        if (!$document || !file_exists($document['path'])) {
            wp_die(__('Dokument nicht gefunden.', 'recruiting-playbook'), 404);
        }

        // Download-Zähler erhöhen
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET download_count = download_count + 1 WHERE id = %d",
            $document_id
        ));

        // Logging
        self::logDownload($document_id);

        // Headers setzen
        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: attachment; filename="' . $document['original_name'] . '"');
        header('Content-Length: ' . $document['size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Datei ausgeben
        readfile($document['path']);
        exit;
    }

    /**
     * Download loggen
     */
    private static function logDownload(int $document_id): void {
        global $wpdb;

        // Dokument-Info holen für Application-ID
        $doc_table = $wpdb->prefix . 'rp_documents';
        $application_id = $wpdb->get_var($wpdb->prepare(
            "SELECT application_id FROM {$doc_table} WHERE id = %d",
            $document_id
        ));

        if (!$application_id) {
            return;
        }

        $log_table = $wpdb->prefix . 'rp_activity_log';
        $current_user = wp_get_current_user();

        $wpdb->insert($log_table, [
            'object_type' => 'application',
            'object_id'   => $application_id,
            'action'      => 'document_downloaded',
            'user_id'     => $current_user->ID,
            'user_name'   => $current_user->display_name,
            'context'     => wp_json_encode(['document_id' => $document_id]),
            'ip_address'  => self::getClientIp(),
            'created_at'  => current_time('mysql'),
        ]);
    }

    /**
     * Client-IP ermitteln
     */
    private static function getClientIp(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                // Bei X-Forwarded-For erste IP nehmen
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
```

### AJAX-Handler registrieren

```php
// In Plugin.php oder separater Klasse

add_action('wp_ajax_rp_download_document', function() {
    // Berechtigung prüfen
    if (!current_user_can('view_applications')) {
        wp_die(__('Keine Berechtigung.', 'recruiting-playbook'), 403);
    }

    $document_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';

    if (!$document_id || !$token) {
        wp_die(__('Ungültige Anfrage.', 'recruiting-playbook'), 400);
    }

    // Token validieren
    if (!DocumentDownloadService::validateToken($document_id, $token)) {
        wp_die(__('Download-Link abgelaufen oder ungültig.', 'recruiting-playbook'), 403);
    }

    // Download ausführen
    DocumentDownloadService::serveDownload($document_id);
});
```

---

## 5. Status-Management

### ApplicationStatus Konstanten erweitern

```php
<?php
// In src/Constants/ApplicationStatus.php

declare(strict_types=1);

namespace RecruitingPlaybook\Constants;

/**
 * Bewerbungs-Status Konstanten und Hilfsfunktionen
 */
class ApplicationStatus {

    public const NEW = 'new';
    public const SCREENING = 'screening';
    public const INTERVIEW = 'interview';
    public const OFFER = 'offer';
    public const HIRED = 'hired';
    public const REJECTED = 'rejected';
    public const WITHDRAWN = 'withdrawn';

    /**
     * Alle Status mit Labels
     */
    public static function getAll(): array {
        return [
            self::NEW       => __('Neu', 'recruiting-playbook'),
            self::SCREENING => __('In Prüfung', 'recruiting-playbook'),
            self::INTERVIEW => __('Interview', 'recruiting-playbook'),
            self::OFFER     => __('Angebot', 'recruiting-playbook'),
            self::HIRED     => __('Eingestellt', 'recruiting-playbook'),
            self::REJECTED  => __('Abgelehnt', 'recruiting-playbook'),
            self::WITHDRAWN => __('Zurückgezogen', 'recruiting-playbook'),
        ];
    }

    /**
     * Status-Farben
     */
    public static function getColors(): array {
        return [
            self::NEW       => '#2271b1',  // Blau
            self::SCREENING => '#dba617',  // Orange
            self::INTERVIEW => '#9b59b6',  // Lila
            self::OFFER     => '#1e8cbe',  // Hellblau
            self::HIRED     => '#00a32a',  // Grün
            self::REJECTED  => '#d63638',  // Rot
            self::WITHDRAWN => '#787c82',  // Grau
        ];
    }

    /**
     * Erlaubte Status-Übergänge
     */
    public static function getAllowedTransitions(): array {
        return [
            self::NEW => [
                self::SCREENING,
                self::REJECTED,
                self::WITHDRAWN,
            ],
            self::SCREENING => [
                self::INTERVIEW,
                self::REJECTED,
                self::WITHDRAWN,
            ],
            self::INTERVIEW => [
                self::OFFER,
                self::REJECTED,
                self::WITHDRAWN,
            ],
            self::OFFER => [
                self::HIRED,
                self::REJECTED,
                self::WITHDRAWN,
            ],
            self::HIRED => [
                // Final-Status
            ],
            self::REJECTED => [
                // Kann wiedereröffnet werden
                self::SCREENING,
            ],
            self::WITHDRAWN => [
                // Final-Status
            ],
        ];
    }

    /**
     * Prüfen ob Übergang erlaubt
     */
    public static function isTransitionAllowed(string $from, string $to): bool {
        // Administratoren können alles
        if (current_user_can('manage_options')) {
            return true;
        }

        $allowed = self::getAllowedTransitions()[$from] ?? [];
        return in_array($to, $allowed, true);
    }

    /**
     * Aktive Status (nicht abgeschlossen)
     */
    public static function getActiveStatuses(): array {
        return [
            self::NEW,
            self::SCREENING,
            self::INTERVIEW,
            self::OFFER,
        ];
    }

    /**
     * Abgeschlossene Status
     */
    public static function getClosedStatuses(): array {
        return [
            self::HIRED,
            self::REJECTED,
            self::WITHDRAWN,
        ];
    }
}
```

---

## 6. Backup-Export

### BackupExporter.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Export;

/**
 * Plugin-Daten als JSON exportieren
 */
class BackupExporter {

    /**
     * Vollständigen Backup erstellen
     */
    public function createBackup(): array {
        return [
            'meta' => $this->getMetaData(),
            'settings' => $this->getSettings(),
            'jobs' => $this->getJobs(),
            'taxonomies' => $this->getTaxonomies(),
            'candidates' => $this->getCandidates(),
            'applications' => $this->getApplications(),
            'documents' => $this->getDocumentsMeta(), // Nur Metadaten, keine Dateien
            'activity_log' => $this->getActivityLog(),
        ];
    }

    /**
     * Meta-Daten
     */
    private function getMetaData(): array {
        return [
            'plugin_version' => RP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'site_url' => get_site_url(),
            'export_date' => current_time('mysql'),
            'export_user' => wp_get_current_user()->user_login,
        ];
    }

    /**
     * Einstellungen
     */
    private function getSettings(): array {
        return [
            'rp_settings' => get_option('rp_settings', []),
            'rp_db_version' => get_option('rp_db_version', ''),
            'rp_employment_types_installed' => get_option('rp_employment_types_installed', false),
        ];
    }

    /**
     * Jobs exportieren
     */
    private function getJobs(): array {
        $jobs = get_posts([
            'post_type' => 'job_listing',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        $export = [];

        foreach ($jobs as $job) {
            $meta = get_post_meta($job->ID);

            // Nur rp_ Meta-Felder
            $rp_meta = [];
            foreach ($meta as $key => $value) {
                if (strpos($key, '_rp_') === 0) {
                    $rp_meta[$key] = maybe_unserialize($value[0]);
                }
            }

            $export[] = [
                'ID' => $job->ID,
                'post_title' => $job->post_title,
                'post_content' => $job->post_content,
                'post_excerpt' => $job->post_excerpt,
                'post_status' => $job->post_status,
                'post_date' => $job->post_date,
                'post_modified' => $job->post_modified,
                'meta' => $rp_meta,
                'taxonomies' => [
                    'job_category' => wp_get_post_terms($job->ID, 'job_category', ['fields' => 'names']),
                    'job_location' => wp_get_post_terms($job->ID, 'job_location', ['fields' => 'names']),
                    'employment_type' => wp_get_post_terms($job->ID, 'employment_type', ['fields' => 'names']),
                ],
            ];
        }

        return $export;
    }

    /**
     * Taxonomien exportieren
     */
    private function getTaxonomies(): array {
        $taxonomies = ['job_category', 'job_location', 'employment_type'];
        $export = [];

        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ]);

            $export[$taxonomy] = array_map(function ($term) {
                return [
                    'term_id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'description' => $term->description,
                    'parent' => $term->parent,
                ];
            }, $terms);
        }

        return $export;
    }

    /**
     * Kandidaten exportieren
     */
    private function getCandidates(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_candidates';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A) ?: [];
    }

    /**
     * Bewerbungen exportieren
     */
    private function getApplications(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_applications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A) ?: [];
    }

    /**
     * Dokument-Metadaten exportieren (ohne Dateien)
     */
    private function getDocumentsMeta(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_documents';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $documents = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A) ?: [];

        // Pfade entfernen aus Sicherheitsgründen
        foreach ($documents as &$doc) {
            unset($doc['path']);
        }

        return $documents;
    }

    /**
     * Aktivitäts-Log exportieren
     */
    private function getActivityLog(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_activity_log';

        // Nur die letzten 1000 Einträge
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 1000",
            ARRAY_A
        ) ?: [];
    }

    /**
     * Export als JSON-String
     */
    public function toJson(): string {
        return wp_json_encode($this->createBackup(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Export als Download
     */
    public function download(): void {
        $filename = sprintf(
            'recruiting-playbook-backup-%s.json',
            date('Y-m-d-His')
        );

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $this->toJson();
        exit;
    }
}
```

### Export-Seite in Menu.php

```php
// Neuer Menüpunkt
add_submenu_page(
    'recruiting-playbook',
    __('Export', 'recruiting-playbook'),
    __('Export', 'recruiting-playbook'),
    'manage_options',
    'rp-export',
    [$this, 'renderExport']
);

public function renderExport(): void {
    // Download-Aktion
    if (isset($_POST['download_backup']) && check_admin_referer('rp_download_backup')) {
        $exporter = new Export\BackupExporter();
        $exporter->download();
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Daten exportieren', 'recruiting-playbook'); ?></h1>

        <div class="card" style="max-width: 600px; padding: 20px;">
            <h2><?php esc_html_e('Vollständiger Backup', 'recruiting-playbook'); ?></h2>
            <p>
                <?php esc_html_e('Exportiert alle Plugin-Daten als JSON-Datei:', 'recruiting-playbook'); ?>
            </p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php esc_html_e('Einstellungen', 'recruiting-playbook'); ?></li>
                <li><?php esc_html_e('Stellen (inkl. Meta-Daten)', 'recruiting-playbook'); ?></li>
                <li><?php esc_html_e('Taxonomien (Kategorien, Standorte, etc.)', 'recruiting-playbook'); ?></li>
                <li><?php esc_html_e('Kandidaten', 'recruiting-playbook'); ?></li>
                <li><?php esc_html_e('Bewerbungen', 'recruiting-playbook'); ?></li>
                <li><?php esc_html_e('Dokument-Metadaten', 'recruiting-playbook'); ?></li>
                <li><?php esc_html_e('Aktivitäts-Log (letzte 1000 Einträge)', 'recruiting-playbook'); ?></li>
            </ul>

            <div class="notice notice-warning inline" style="margin: 15px 0;">
                <p>
                    <strong><?php esc_html_e('Hinweis:', 'recruiting-playbook'); ?></strong>
                    <?php esc_html_e('Hochgeladene Dokumente (PDFs etc.) werden aus Datenschutzgründen nicht exportiert.', 'recruiting-playbook'); ?>
                </p>
            </div>

            <form method="post">
                <?php wp_nonce_field('rp_download_backup'); ?>
                <button type="submit" name="download_backup" class="button button-primary">
                    <?php esc_html_e('Backup herunterladen', 'recruiting-playbook'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php
}
```

---

## 7. DSGVO-Funktionen

### GdprService.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use WP_Error;

/**
 * DSGVO-Funktionen: Löschen, Anonymisieren, Exportieren
 */
class GdprService {

    /**
     * Bewerbung soft-löschen
     */
    public function softDeleteApplication(int $application_id): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_applications';

        // Status auf "deleted" setzen
        $result = $wpdb->update(
            $table,
            [
                'status'     => 'deleted',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $application_id]
        );

        if ($result === false) {
            return false;
        }

        // Dokumente markieren
        $doc_table = $wpdb->prefix . 'rp_documents';
        $wpdb->update(
            $doc_table,
            [
                'is_deleted' => 1,
                'deleted_at' => current_time('mysql'),
            ],
            ['application_id' => $application_id]
        );

        // Logging
        $this->logAction($application_id, 'soft_deleted');

        return true;
    }

    /**
     * Bewerbung vollständig löschen (Hard Delete)
     */
    public function hardDeleteApplication(int $application_id): bool {
        global $wpdb;

        // Dokumente physisch löschen
        $this->deleteApplicationDocuments($application_id);

        // DB-Einträge löschen
        $applications_table = $wpdb->prefix . 'rp_applications';
        $documents_table    = $wpdb->prefix . 'rp_documents';
        $log_table          = $wpdb->prefix . 'rp_activity_log';

        $wpdb->delete($documents_table, ['application_id' => $application_id]);
        $wpdb->delete($log_table, ['object_type' => 'application', 'object_id' => $application_id]);
        $wpdb->delete($applications_table, ['id' => $application_id]);

        return true;
    }

    /**
     * Kandidat löschen (alle Bewerbungen)
     */
    public function deleteCandidate(int $candidate_id): bool {
        global $wpdb;

        // Alle Bewerbungen des Kandidaten holen
        $applications_table = $wpdb->prefix . 'rp_applications';
        $application_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$applications_table} WHERE candidate_id = %d",
            $candidate_id
        ));

        // Alle Bewerbungen löschen
        foreach ($application_ids as $app_id) {
            $this->hardDeleteApplication((int) $app_id);
        }

        // Kandidat löschen
        $candidates_table = $wpdb->prefix . 'rp_candidates';
        $wpdb->delete($candidates_table, ['id' => $candidate_id]);

        return true;
    }

    /**
     * Kandidaten-Daten anonymisieren
     */
    public function anonymizeCandidate(int $candidate_id): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_candidates';

        $result = $wpdb->update(
            $table,
            [
                'email'        => 'anonymized-' . $candidate_id . '@deleted.local',
                'first_name'   => 'Anonymisiert',
                'last_name'    => '',
                'phone'        => '',
                'address_street' => '',
                'address_city'   => '',
                'address_zip'    => '',
                'notes'        => '',
                'updated_at'   => current_time('mysql'),
            ],
            ['id' => $candidate_id]
        );

        // Bewerbungen anonymisieren
        $applications_table = $wpdb->prefix . 'rp_applications';
        $wpdb->update(
            $applications_table,
            [
                'cover_letter'  => '',
                'ip_address'    => '0.0.0.0',
                'user_agent'    => '',
                'custom_fields' => '',
            ],
            ['candidate_id' => $candidate_id]
        );

        // Dokumente löschen
        $application_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$applications_table} WHERE candidate_id = %d",
            $candidate_id
        ));

        foreach ($application_ids as $app_id) {
            $this->deleteApplicationDocuments((int) $app_id);
        }

        return $result !== false;
    }

    /**
     * Dokumente einer Bewerbung physisch löschen
     */
    private function deleteApplicationDocuments(int $application_id): void {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_documents';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $documents = $wpdb->get_results(
            $wpdb->prepare("SELECT path FROM {$table} WHERE application_id = %d", $application_id),
            ARRAY_A
        );

        foreach ($documents as $doc) {
            if (!empty($doc['path']) && file_exists($doc['path'])) {
                wp_delete_file($doc['path']);
            }
        }

        // DB-Einträge löschen
        $wpdb->delete($table, ['application_id' => $application_id]);
    }

    /**
     * Datenauskunft (DSGVO Art. 15)
     */
    public function exportCandidateData(int $candidate_id): array {
        global $wpdb;

        // Kandidat
        $candidates_table = $wpdb->prefix . 'rp_candidates';
        $candidate = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$candidates_table} WHERE id = %d", $candidate_id),
            ARRAY_A
        );

        if (!$candidate) {
            return [];
        }

        // Bewerbungen
        $applications_table = $wpdb->prefix . 'rp_applications';
        $applications = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$applications_table} WHERE candidate_id = %d", $candidate_id),
            ARRAY_A
        );

        // Dokumente (Metadaten)
        $documents_table = $wpdb->prefix . 'rp_documents';
        $documents = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT d.* FROM {$documents_table} d
                 JOIN {$applications_table} a ON d.application_id = a.id
                 WHERE a.candidate_id = %d",
                $candidate_id
            ),
            ARRAY_A
        );

        // Pfade aus Sicherheitsgründen entfernen
        foreach ($documents as &$doc) {
            unset($doc['path']);
        }

        // Aktivitäts-Log
        $log_table = $wpdb->prefix . 'rp_activity_log';
        $application_ids = array_column($applications, 'id');

        $activities = [];
        if (!empty($application_ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($application_ids), '%d'));
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $activities = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$log_table}
                     WHERE object_type = 'application' AND object_id IN ({$ids_placeholder})",
                    ...$application_ids
                ),
                ARRAY_A
            );
        }

        return [
            'export_date' => current_time('mysql'),
            'candidate' => $candidate,
            'applications' => $applications,
            'documents' => $documents,
            'activity_log' => $activities,
        ];
    }

    /**
     * Datenauskunft als JSON-Download
     */
    public function downloadCandidateData(int $candidate_id): void {
        $data = $this->exportCandidateData($candidate_id);

        if (empty($data)) {
            wp_die(__('Kandidat nicht gefunden.', 'recruiting-playbook'));
        }

        $filename = sprintf(
            'datenauskunft-kandidat-%d-%s.json',
            $candidate_id,
            date('Y-m-d')
        );

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Aktion loggen
     */
    private function logAction(int $application_id, string $action): void {
        global $wpdb;

        $table = $wpdb->prefix . 'rp_activity_log';
        $current_user = wp_get_current_user();

        $wpdb->insert($table, [
            'object_type' => 'application',
            'object_id'   => $application_id,
            'action'      => $action,
            'user_id'     => $current_user->ID,
            'user_name'   => $current_user->display_name,
            'created_at'  => current_time('mysql'),
        ]);
    }

    /**
     * Automatische Löschung alter Daten (DSGVO-Aufbewahrungsfrist)
     */
    public function cleanupOldData(int $months = 24): int {
        global $wpdb;

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$months} months"));
        $deleted_count = 0;

        // Soft-gelöschte Bewerbungen endgültig löschen
        $applications_table = $wpdb->prefix . 'rp_applications';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $old_applications = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$applications_table}
             WHERE status = 'deleted' AND updated_at < %s",
            $cutoff_date
        ));

        foreach ($old_applications as $app_id) {
            $this->hardDeleteApplication((int) $app_id);
            $deleted_count++;
        }

        return $deleted_count;
    }
}
```

---

## 8. REST API Erweiterungen

### Neue Endpoints für Phase 1C

```php
<?php
// In Api/ApplicationController.php erweitern

/**
 * Weitere Routen für Admin
 */
public function register_admin_routes(): void {
    // Bewerbungen abrufen (für React Admin)
    register_rest_route('recruiting/v1', '/admin/applications', [
        'methods'             => 'GET',
        'callback'            => [$this, 'get_applications'],
        'permission_callback' => [$this, 'check_admin_permission'],
        'args'                => [
            'page'     => ['type' => 'integer', 'default' => 1],
            'per_page' => ['type' => 'integer', 'default' => 20],
            'status'   => ['type' => 'string'],
            'job_id'   => ['type' => 'integer'],
            'search'   => ['type' => 'string'],
            'orderby'  => ['type' => 'string', 'default' => 'created_at'],
            'order'    => ['type' => 'string', 'default' => 'desc'],
        ],
    ]);

    // Einzelne Bewerbung
    register_rest_route('recruiting/v1', '/admin/applications/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => [$this, 'get_application'],
        'permission_callback' => [$this, 'check_admin_permission'],
    ]);

    // Status aktualisieren
    register_rest_route('recruiting/v1', '/admin/applications/(?P<id>\d+)/status', [
        'methods'             => 'PATCH',
        'callback'            => [$this, 'update_status'],
        'permission_callback' => [$this, 'check_admin_permission'],
        'args'                => [
            'status' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // Dokument-Download-URL generieren
    register_rest_route('recruiting/v1', '/admin/documents/(?P<id>\d+)/download-url', [
        'methods'             => 'GET',
        'callback'            => [$this, 'get_download_url'],
        'permission_callback' => [$this, 'check_admin_permission'],
    ]);

    // Datenauskunft (DSGVO)
    register_rest_route('recruiting/v1', '/admin/candidates/(?P<id>\d+)/export', [
        'methods'             => 'GET',
        'callback'            => [$this, 'export_candidate_data'],
        'permission_callback' => [$this, 'check_admin_permission'],
    ]);

    // Bewerbung löschen
    register_rest_route('recruiting/v1', '/admin/applications/(?P<id>\d+)', [
        'methods'             => 'DELETE',
        'callback'            => [$this, 'delete_application'],
        'permission_callback' => [$this, 'check_admin_permission'],
        'args'                => [
            'hard_delete' => ['type' => 'boolean', 'default' => false],
        ],
    ]);

    // Statistiken
    register_rest_route('recruiting/v1', '/admin/stats', [
        'methods'             => 'GET',
        'callback'            => [$this, 'get_stats'],
        'permission_callback' => [$this, 'check_admin_permission'],
    ]);
}

/**
 * Admin-Berechtigung prüfen
 */
public function check_admin_permission(): bool {
    return current_user_can('view_applications');
}

/**
 * Statistiken abrufen
 */
public function get_stats(\WP_REST_Request $request): \WP_REST_Response {
    global $wpdb;

    $applications_table = $wpdb->prefix . 'rp_applications';

    // Status-Verteilung
    $status_counts = $wpdb->get_results(
        "SELECT status, COUNT(*) as count FROM {$applications_table} GROUP BY status",
        OBJECT_K
    );

    // Bewerbungen pro Tag (letzte 30 Tage)
    $daily_counts = $wpdb->get_results(
        "SELECT DATE(created_at) as date, COUNT(*) as count
         FROM {$applications_table}
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at)
         ORDER BY date ASC"
    );

    // Top-Jobs nach Bewerbungen
    $top_jobs = $wpdb->get_results(
        "SELECT job_id, COUNT(*) as count
         FROM {$applications_table}
         GROUP BY job_id
         ORDER BY count DESC
         LIMIT 5"
    );

    foreach ($top_jobs as &$job) {
        $post = get_post($job->job_id);
        $job->title = $post ? $post->post_title : __('Gelöscht', 'recruiting-playbook');
    }

    return new \WP_REST_Response([
        'status_counts' => $status_counts,
        'daily_counts'  => $daily_counts,
        'top_jobs'      => $top_jobs,
        'total'         => array_sum(array_column((array) $status_counts, 'count')),
    ]);
}
```

---

## 9. Admin-Assets

### CSS für Listenansicht

```css
/* assets/src/css/admin-applications.css */

/* Status-Badge */
.rp-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    color: white;
}

/* Subsubsub Status-Links */
.subsubsub .count {
    color: #999;
}

/* Detailseite Grid */
.rp-detail-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    margin-top: 20px;
}

@media screen and (max-width: 960px) {
    .rp-detail-grid {
        grid-template-columns: 1fr;
    }
}

/* Postbox in Detailseite */
.rp-application-detail .postbox {
    margin-bottom: 20px;
}

.rp-application-detail .postbox .hndle {
    cursor: default;
}

/* Anschreiben-Box */
.rp-cover-letter {
    background: #f6f7f7;
    padding: 15px;
    border-radius: 4px;
    white-space: pre-wrap;
    font-family: inherit;
    line-height: 1.6;
}

/* Aktivitäts-Log */
.rp-activity-log {
    margin: 0;
    padding: 0;
    list-style: none;
}

.rp-activity-log li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.rp-activity-log li:last-child {
    border-bottom: none;
}

.rp-log-time {
    color: #666;
    font-size: 12px;
}

.rp-log-user {
    font-weight: 500;
}

.rp-log-action {
    color: #333;
}

/* Filter-Bereich */
.tablenav .alignleft.actions select,
.tablenav .alignleft.actions input[type="date"] {
    margin-right: 5px;
}

/* Dokumente-Tabelle */
.widefat .dashicons {
    vertical-align: middle;
    margin-right: 5px;
}

/* DSGVO-Buttons */
.button-link-delete {
    color: #b32d2e !important;
    border-color: #b32d2e !important;
}

.button-link-delete:hover {
    background: #b32d2e !important;
    color: white !important;
}
```

---

## 10. Deliverables

### Abnahmekriterien Phase 1C

| Item | Beschreibung | Kriterium |
|------|--------------|-----------|
| Listenansicht | Alle Bewerbungen in WP_List_Table | Filter, Sortierung, Suche funktionieren |
| Bulk-Actions | Mehrere Status gleichzeitig ändern | Status wird geändert, Log-Eintrag erstellt |
| Detailseite | Vollständige Bewerber-Ansicht | Kontakt, Anschreiben, Dokumente, Log |
| Dokument-Download | Sichere Token-basierte URLs | Downloads funktionieren, werden geloggt |
| Status-Management | Status ändern mit Logging | Verlauf wird aufgezeichnet |
| Backup-Export | JSON-Export aller Daten | Download als .json funktioniert |
| DSGVO Soft-Delete | Bewerbung als gelöscht markieren | Status "deleted", Dokumente markiert |
| DSGVO Datenauskunft | Export pro Kandidat | JSON-Download funktioniert |
| REST API Admin | Endpoints für React-Admin | GET/PATCH/DELETE funktionieren |
| Statistiken | Dashboard-Widget | Status-Verteilung, Zeitverläufe |

### Test-Checkliste

```bash
# Listenansicht testen
1. [ ] Bewerbungen werden angezeigt
2. [ ] Filter nach Status funktioniert
3. [ ] Filter nach Stelle funktioniert
4. [ ] Datum-Filter funktioniert
5. [ ] Suche nach Name/E-Mail funktioniert
6. [ ] Sortierung funktioniert
7. [ ] Pagination funktioniert
8. [ ] Bulk-Action: Status ändern
9. [ ] Bulk-Action: Löschen

# Detailseite testen
10. [ ] Alle Kontaktdaten werden angezeigt
11. [ ] Anschreiben wird angezeigt
12. [ ] Dokumente werden aufgelistet
13. [ ] Download-Links funktionieren
14. [ ] Status-Dropdown funktioniert
15. [ ] Aktivitäts-Log zeigt Verlauf

# DSGVO testen
16. [ ] Datenauskunft-Export funktioniert
17. [ ] Soft-Delete markiert Bewerbung
18. [ ] Dokumente werden bei Hard-Delete gelöscht

# Backup testen
19. [ ] JSON-Export enthält alle Daten
20. [ ] Download-Datei ist valides JSON
```

---

## Nächste Phase: Phase 1D

Nach erfolgreichem Abschluss von Phase 1C:

→ **Phase 1D: Polish & Pilot** (Woche 7-8)
- Setup-Wizard für Erstkonfiguration
- Google for Jobs Schema validieren
- Testing (PHPUnit, manuell, Cross-Browser)
- Pilotkunden-Installation
- Dokumentation
- Übersetzungen finalisieren

---

*Technische Spezifikation erstellt: Januar 2025*

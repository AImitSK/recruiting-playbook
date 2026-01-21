<?php
/**
 * Plugin Deinstallation
 *
 * Wird ausgeführt wenn Plugin über WordPress gelöscht wird.
 */

// Sicherheitscheck
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Option prüfen: Daten behalten?
$keep_data = get_option('rp_keep_data_on_uninstall', false);

if (!$keep_data) {
    global $wpdb;

    // 1. Custom Post Type Daten löschen
    $posts = get_posts([
        'post_type' => 'job_listing',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ]);

    foreach ($posts as $post_id) {
        wp_delete_post($post_id, true);
    }

    // 2. Taxonomie-Terms löschen
    $taxonomies = ['job_category', 'job_location', 'employment_type'];
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'ids',
        ]);

        if (!is_wp_error($terms)) {
            foreach ($terms as $term_id) {
                wp_delete_term($term_id, $taxonomy);
            }
        }
    }

    // 3. Custom Tables löschen
    $tables = [
        $wpdb->prefix . 'rp_candidates',
        $wpdb->prefix . 'rp_applications',
        $wpdb->prefix . 'rp_documents',
        $wpdb->prefix . 'rp_activity_log',
    ];

    foreach ($tables as $table) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // 4. Optionen löschen
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rp_%'");

    // 5. User Meta löschen
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'rp_%'");

    // 6. Post Meta löschen (falls noch übrig)
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_rp_%'");

    // 7. Upload-Ordner löschen
    $upload_dir = wp_upload_dir();
    $rp_upload_dir = $upload_dir['basedir'] . '/recruiting-playbook';

    if (is_dir($rp_upload_dir)) {
        // Rekursiv löschen
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rp_upload_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                wp_delete_file($file->getRealPath());
            }
        }
        rmdir($rp_upload_dir);
    }
}

// Rewrite Rules flushen
flush_rewrite_rules();

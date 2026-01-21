<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Core;

/**
 * Plugin-Aktivierung
 */
class Activator {

    /**
     * Bei Aktivierung ausführen
     */
    public static function activate(): void {
        // 1. Datenbank-Tabellen erstellen
        self::createTables();

        // 2. Standard-Optionen setzen
        self::setDefaultOptions();

        // 3. Aktivierungs-Marker setzen (für Setup-Wizard)
        update_option('rp_activation_redirect', true);

        // 4. Version speichern
        update_option('rp_version', RP_VERSION);

        // 5. Rewrite Rules flushen
        flush_rewrite_rules();
    }

    /**
     * Datenbank-Tabellen erstellen
     */
    private static function createTables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // rp_candidates
        $sql_candidates = "CREATE TABLE {$wpdb->prefix}rp_candidates (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            first_name varchar(100) DEFAULT '',
            last_name varchar(100) DEFAULT '',
            phone varchar(50) DEFAULT '',
            address_street varchar(255) DEFAULT '',
            address_city varchar(100) DEFAULT '',
            address_zip varchar(20) DEFAULT '',
            address_country varchar(100) DEFAULT 'Deutschland',
            source varchar(50) DEFAULT 'form',
            notes longtext DEFAULT '',
            gdpr_consent tinyint(1) DEFAULT 0,
            gdpr_consent_date datetime DEFAULT NULL,
            gdpr_consent_version varchar(20) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // rp_applications
        $sql_applications = "CREATE TABLE {$wpdb->prefix}rp_applications (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) unsigned NOT NULL,
            job_id bigint(20) unsigned NOT NULL,
            status varchar(50) DEFAULT 'new',
            cover_letter longtext DEFAULT '',
            custom_fields longtext DEFAULT '',
            source_url varchar(500) DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            user_agent varchar(500) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY candidate_id (candidate_id),
            KEY job_id (job_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // rp_documents
        $sql_documents = "CREATE TABLE {$wpdb->prefix}rp_documents (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            application_id bigint(20) unsigned NOT NULL,
            candidate_id bigint(20) unsigned NOT NULL,
            file_name varchar(255) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_type varchar(100) NOT NULL,
            file_size bigint(20) unsigned DEFAULT 0,
            file_hash varchar(64) DEFAULT '',
            document_type varchar(50) DEFAULT 'other',
            download_count int(11) DEFAULT 0,
            is_deleted tinyint(1) DEFAULT 0,
            deleted_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY application_id (application_id),
            KEY candidate_id (candidate_id),
            KEY document_type (document_type),
            KEY is_deleted (is_deleted)
        ) {$charset_collate};";

        // rp_activity_log
        $sql_activity = "CREATE TABLE {$wpdb->prefix}rp_activity_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            object_type varchar(50) NOT NULL,
            object_id bigint(20) unsigned NOT NULL,
            action varchar(100) NOT NULL,
            user_id bigint(20) unsigned DEFAULT 0,
            user_name varchar(100) DEFAULT '',
            old_value longtext DEFAULT '',
            new_value longtext DEFAULT '',
            context longtext DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object_type_id (object_type, object_id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql_candidates);
        dbDelta($sql_applications);
        dbDelta($sql_documents);
        dbDelta($sql_activity);

        // DB-Version speichern
        update_option('rp_db_version', '1.0.0');
    }

    /**
     * Standard-Optionen setzen
     */
    private static function setDefaultOptions(): void {
        $defaults = [
            'rp_settings' => [
                'company_name' => get_bloginfo('name'),
                'notification_email' => get_option('admin_email'),
                'privacy_url' => get_privacy_policy_url(),
                'jobs_per_page' => 10,
                'jobs_slug' => 'jobs',
                'enable_schema' => true,
            ],
        ];

        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}

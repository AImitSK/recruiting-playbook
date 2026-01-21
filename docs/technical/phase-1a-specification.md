# Phase 1A: Technische Spezifikation

> **Woche 1-2: Fundament**
> Solide technische Basis, Job-Verwaltung funktionsfähig

---

## Inhaltsverzeichnis

1. [Plugin-Grundstruktur](#1-plugin-grundstruktur)
2. [Verzeichnisstruktur](#2-verzeichnisstruktur)
3. [Core-Klassen](#3-core-klassen)
4. [Custom Post Type: job_listing](#4-custom-post-type-job_listing)
5. [Taxonomien](#5-taxonomien)
6. [Meta-Felder](#6-meta-felder)
7. [Datenbank-Tabellen](#7-datenbank-tabellen)
8. [Admin-Menü](#8-admin-menü)
9. [Build-Prozess](#9-build-prozess)
10. [Coding Standards](#10-coding-standards)

---

## 1. Plugin-Grundstruktur

### Hauptdatei: `recruiting-playbook.php`

```php
<?php
/**
 * Plugin Name: Recruiting Playbook
 * Plugin URI: https://recruiting-playbook.de/
 * Description: Professionelles Bewerbermanagement für WordPress
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: [Dein Name]
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recruiting-playbook
 * Domain Path: /languages
 */

declare(strict_types=1);

namespace RecruitingPlaybook;

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten
define('RP_VERSION', '1.0.0');
define('RP_PLUGIN_FILE', __FILE__);
define('RP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum Requirements
define('RP_MIN_PHP_VERSION', '8.0');
define('RP_MIN_WP_VERSION', '6.0');

// Autoloader
if (file_exists(RP_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once RP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Requirements Check
function rp_check_requirements(): bool {
    if (version_compare(PHP_VERSION, RP_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            printf(
                __('Recruiting Playbook requires PHP %s or higher. You are running PHP %s.', 'recruiting-playbook'),
                RP_MIN_PHP_VERSION,
                PHP_VERSION
            );
            echo '</p></div>';
        });
        return false;
    }

    global $wp_version;
    if (version_compare($wp_version, RP_MIN_WP_VERSION, '<')) {
        add_action('admin_notices', function() {
            global $wp_version;
            echo '<div class="notice notice-error"><p>';
            printf(
                __('Recruiting Playbook requires WordPress %s or higher. You are running WordPress %s.', 'recruiting-playbook'),
                RP_MIN_WP_VERSION,
                $wp_version
            );
            echo '</p></div>';
        });
        return false;
    }

    return true;
}

// Aktivierung
register_activation_hook(__FILE__, function() {
    if (!rp_check_requirements()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Plugin activation failed due to unmet requirements.', 'recruiting-playbook'));
    }

    require_once RP_PLUGIN_DIR . 'src/Core/Activator.php';
    Core\Activator::activate();
});

// Deaktivierung
register_deactivation_hook(__FILE__, function() {
    require_once RP_PLUGIN_DIR . 'src/Core/Deactivator.php';
    Core\Deactivator::deactivate();
});

// Plugin initialisieren
add_action('plugins_loaded', function() {
    if (!rp_check_requirements()) {
        return;
    }

    Core\Plugin::getInstance()->init();
});
```

### composer.json

```json
{
    "name": "recruiting-playbook/recruiting-playbook",
    "description": "Professionelles Bewerbermanagement für WordPress",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=8.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "brain/monkey": "^2.6",
        "wp-coding-standards/wpcs": "^3.0",
        "phpstan/phpstan": "^1.10"
    },
    "autoload": {
        "psr-4": {
            "RecruitingPlaybook\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "RecruitingPlaybook\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "phpcs": "phpcs --standard=WordPress",
        "phpcbf": "phpcbf --standard=WordPress",
        "phpstan": "phpstan analyse src/"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true
        }
    }
}
```

---

## 2. Verzeichnisstruktur

```
recruiting-playbook/
├── recruiting-playbook.php        # Hauptdatei
├── uninstall.php                  # Komplette Deinstallation
├── composer.json
├── package.json
├── tailwind.config.js
├── postcss.config.js
├── esbuild.config.js
│
├── src/                           # PHP Source (PSR-4)
│   ├── Core/
│   │   ├── Plugin.php             # Singleton, Bootstrap
│   │   ├── Activator.php          # Aktivierungslogik
│   │   ├── Deactivator.php        # Deaktivierungslogik
│   │   └── I18n.php               # Internationalisierung
│   │
│   ├── PostTypes/
│   │   └── JobListing.php         # CPT Registration
│   │
│   ├── Taxonomies/
│   │   ├── JobCategory.php
│   │   ├── JobLocation.php
│   │   └── EmploymentType.php
│   │
│   ├── Database/
│   │   ├── Schema.php             # Tabellen-Definitionen
│   │   ├── Migrator.php           # DB-Migrationen
│   │   └── Tables/
│   │       ├── CandidatesTable.php
│   │       ├── ApplicationsTable.php
│   │       ├── DocumentsTable.php
│   │       └── ActivityLogTable.php
│   │
│   ├── Admin/
│   │   ├── Menu.php               # Admin-Menü
│   │   ├── Pages/
│   │   │   ├── Dashboard.php
│   │   │   ├── Applications.php
│   │   │   └── Settings.php
│   │   └── MetaBoxes/
│   │       └── JobMeta.php        # Job Meta-Felder
│   │
│   └── Services/
│       └── JobService.php         # Business Logic
│
├── assets/
│   ├── src/                       # Unbundled Source
│   │   ├── css/
│   │   │   └── main.css           # Tailwind Input
│   │   └── js/
│   │       ├── admin.js           # Admin React
│   │       └── frontend.js        # Alpine.js
│   │
│   └── dist/                      # Bundled Output (gitignore)
│       ├── css/
│       └── js/
│
├── templates/                     # Theme-überschreibbare Templates
│   ├── archive-job_listing.php
│   └── single-job_listing.php
│
├── languages/
│   ├── recruiting-playbook.pot    # POT Template
│   └── recruiting-playbook-de_DE.po
│
├── tests/
│   ├── bootstrap.php
│   ├── Unit/
│   └── Integration/
│
└── .github/
    └── workflows/
        └── tests.yml              # GitHub Actions
```

---

## 3. Core-Klassen

### 3.1 Plugin.php (Singleton, Bootstrap)

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Core;

use RecruitingPlaybook\PostTypes\JobListing;
use RecruitingPlaybook\Taxonomies\{JobCategory, JobLocation, EmploymentType};
use RecruitingPlaybook\Admin\Menu;
use RecruitingPlaybook\Admin\MetaBoxes\JobMeta;

/**
 * Haupt-Plugin-Klasse (Singleton)
 */
final class Plugin {

    private static ?Plugin $instance = null;

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Plugin initialisieren
     */
    public function init(): void {
        // Internationalisierung laden
        $this->loadI18n();

        // Post Types & Taxonomien registrieren
        $this->registerPostTypes();
        $this->registerTaxonomies();

        // Admin-Bereich
        if (is_admin()) {
            $this->initAdmin();
        }

        // Frontend
        if (!is_admin()) {
            $this->initFrontend();
        }

        // REST API
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        // Assets laden
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    private function loadI18n(): void {
        load_plugin_textdomain(
            'recruiting-playbook',
            false,
            dirname(RP_PLUGIN_BASENAME) . '/languages/'
        );
    }

    private function registerPostTypes(): void {
        $jobListing = new JobListing();
        add_action('init', [$jobListing, 'register']);
    }

    private function registerTaxonomies(): void {
        $jobCategory = new JobCategory();
        $jobLocation = new JobLocation();
        $employmentType = new EmploymentType();

        add_action('init', [$jobCategory, 'register']);
        add_action('init', [$jobLocation, 'register']);
        add_action('init', [$employmentType, 'register']);
    }

    private function initAdmin(): void {
        $menu = new Menu();
        add_action('admin_menu', [$menu, 'register']);

        $jobMeta = new JobMeta();
        add_action('add_meta_boxes', [$jobMeta, 'register']);
        add_action('save_post_job_listing', [$jobMeta, 'save'], 10, 2);
    }

    private function initFrontend(): void {
        // Template-Loader für CPT
        add_filter('template_include', [$this, 'loadTemplates']);
    }

    /**
     * Custom Templates laden
     */
    public function loadTemplates(string $template): string {
        if (is_post_type_archive('job_listing')) {
            $custom = locate_template('recruiting-playbook/archive-job_listing.php');
            if ($custom) {
                return $custom;
            }
            return RP_PLUGIN_DIR . 'templates/archive-job_listing.php';
        }

        if (is_singular('job_listing')) {
            $custom = locate_template('recruiting-playbook/single-job_listing.php');
            if ($custom) {
                return $custom;
            }
            return RP_PLUGIN_DIR . 'templates/single-job_listing.php';
        }

        return $template;
    }

    public function registerRestRoutes(): void {
        // Für Phase 1B (Bewerbungsformular)
    }

    public function enqueueFrontendAssets(): void {
        if (!is_post_type_archive('job_listing') && !is_singular('job_listing')) {
            return;
        }

        wp_enqueue_style(
            'rp-frontend',
            RP_PLUGIN_URL . 'assets/dist/css/frontend.css',
            [],
            RP_VERSION
        );

        wp_enqueue_script(
            'rp-alpine',
            RP_PLUGIN_URL . 'assets/dist/js/alpine.min.js',
            [],
            RP_VERSION,
            true
        );

        wp_enqueue_script(
            'rp-frontend',
            RP_PLUGIN_URL . 'assets/dist/js/frontend.js',
            ['rp-alpine'],
            RP_VERSION,
            true
        );
    }

    public function enqueueAdminAssets(string $hook): void {
        // Nur auf Plugin-Seiten laden
        if (!str_starts_with($hook, 'toplevel_page_recruiting')
            && !str_starts_with($hook, 'recruiting_page_')
            && get_post_type() !== 'job_listing') {
            return;
        }

        wp_enqueue_style(
            'rp-admin',
            RP_PLUGIN_URL . 'assets/dist/css/admin.css',
            [],
            RP_VERSION
        );

        wp_enqueue_script(
            'rp-admin',
            RP_PLUGIN_URL . 'assets/dist/js/admin.js',
            ['wp-element', 'wp-components', 'wp-api-fetch'],
            RP_VERSION,
            true
        );

        wp_localize_script('rp-admin', 'rpAdmin', [
            'apiUrl' => rest_url('recruiting/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'adminUrl' => admin_url(),
        ]);
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}
```

### 3.2 Activator.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Core;

use RecruitingPlaybook\Database\Migrator;
use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Plugin-Aktivierung
 */
class Activator {

    /**
     * Bei Aktivierung ausführen
     */
    public static function activate(): void {
        // 1. Datenbank-Tabellen erstellen
        $migrator = new Migrator();
        $migrator->createTables();

        // 2. Custom Post Type registrieren (für Rewrite Rules)
        $jobListing = new JobListing();
        $jobListing->register();

        // 3. Rewrite Rules flushen
        flush_rewrite_rules();

        // 4. Standard-Optionen setzen
        self::setDefaultOptions();

        // 5. Aktivierungs-Marker setzen (für Setup-Wizard)
        update_option('rp_activation_redirect', true);

        // 6. Version speichern
        update_option('rp_version', RP_VERSION);
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
```

### 3.3 Deactivator.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Core;

/**
 * Plugin-Deaktivierung
 */
class Deactivator {

    /**
     * Bei Deaktivierung ausführen
     */
    public static function deactivate(): void {
        // Rewrite Rules flushen
        flush_rewrite_rules();

        // Geplante Tasks entfernen
        self::clearScheduledTasks();
    }

    /**
     * Geplante WP-Cron Tasks entfernen
     */
    private static function clearScheduledTasks(): void {
        $hooks = [
            'rp_daily_cleanup',
            'rp_license_check',
        ];

        foreach ($hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }
}
```

### 3.4 uninstall.php (Root-Verzeichnis)

```php
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
        foreach ($terms as $term_id) {
            wp_delete_term($term_id, $taxonomy);
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
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // 4. Optionen löschen
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rp_%'");

    // 5. User Meta löschen
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'rp_%'");

    // 6. Post Meta löschen (falls noch übrig)
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
                unlink($file->getRealPath());
            }
        }
        rmdir($rp_upload_dir);
    }
}

// Rewrite Rules flushen
flush_rewrite_rules();
```

---

## 4. Custom Post Type: job_listing

### JobListing.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\PostTypes;

/**
 * Custom Post Type: Stellenanzeigen
 */
class JobListing {

    public const POST_TYPE = 'job_listing';

    /**
     * CPT registrieren
     */
    public function register(): void {
        $labels = [
            'name'                  => __('Stellen', 'recruiting-playbook'),
            'singular_name'         => __('Stelle', 'recruiting-playbook'),
            'menu_name'             => __('Stellen', 'recruiting-playbook'),
            'name_admin_bar'        => __('Stelle', 'recruiting-playbook'),
            'add_new'               => __('Neue Stelle', 'recruiting-playbook'),
            'add_new_item'          => __('Neue Stelle erstellen', 'recruiting-playbook'),
            'new_item'              => __('Neue Stelle', 'recruiting-playbook'),
            'edit_item'             => __('Stelle bearbeiten', 'recruiting-playbook'),
            'view_item'             => __('Stelle ansehen', 'recruiting-playbook'),
            'all_items'             => __('Alle Stellen', 'recruiting-playbook'),
            'search_items'          => __('Stellen durchsuchen', 'recruiting-playbook'),
            'parent_item_colon'     => __('Übergeordnete Stelle:', 'recruiting-playbook'),
            'not_found'             => __('Keine Stellen gefunden.', 'recruiting-playbook'),
            'not_found_in_trash'    => __('Keine Stellen im Papierkorb.', 'recruiting-playbook'),
            'featured_image'        => __('Stellenbild', 'recruiting-playbook'),
            'set_featured_image'    => __('Stellenbild festlegen', 'recruiting-playbook'),
            'remove_featured_image' => __('Stellenbild entfernen', 'recruiting-playbook'),
            'use_featured_image'    => __('Als Stellenbild verwenden', 'recruiting-playbook'),
            'archives'              => __('Stellen-Archiv', 'recruiting-playbook'),
            'insert_into_item'      => __('In Stelle einfügen', 'recruiting-playbook'),
            'uploaded_to_this_item' => __('Zu dieser Stelle hochgeladen', 'recruiting-playbook'),
            'filter_items_list'     => __('Stellenliste filtern', 'recruiting-playbook'),
            'items_list_navigation' => __('Stellenlisten-Navigation', 'recruiting-playbook'),
            'items_list'            => __('Stellenliste', 'recruiting-playbook'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'recruiting-playbook', // Unter Plugin-Menü
            'query_var'          => true,
            'rewrite'            => [
                'slug'       => $this->getSlug(),
                'with_front' => false,
            ],
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-businessman',
            'supports'           => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'revisions',
                'custom-fields',
            ],
            'show_in_rest'       => true, // Gutenberg & REST API
            'rest_base'          => 'jobs',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'template'           => [], // Später: Block-Template
            'template_lock'      => false,
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * URL-Slug aus Einstellungen holen
     */
    private function getSlug(): string {
        $settings = get_option('rp_settings', []);
        return $settings['jobs_slug'] ?? 'jobs';
    }
}
```

### Registrierung der Capabilities (für spätere Benutzerrollen)

```php
<?php
// In Activator.php hinzufügen

/**
 * Custom Capabilities hinzufügen
 */
private static function addCapabilities(): void {
    $admin = get_role('administrator');

    if ($admin) {
        // Job Listing Capabilities
        $capabilities = [
            'edit_job_listing',
            'read_job_listing',
            'delete_job_listing',
            'edit_job_listings',
            'edit_others_job_listings',
            'publish_job_listings',
            'read_private_job_listings',
            'delete_job_listings',
            'delete_private_job_listings',
            'delete_published_job_listings',
            'delete_others_job_listings',
            'edit_private_job_listings',
            'edit_published_job_listings',
            // Recruiting Capabilities
            'manage_recruiting',
            'view_applications',
            'edit_applications',
            'delete_applications',
        ];

        foreach ($capabilities as $cap) {
            $admin->add_cap($cap);
        }
    }
}
```

---

## 5. Taxonomien

### JobCategory.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Taxonomies;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Taxonomy: Berufsfeld / Kategorie
 */
class JobCategory {

    public const TAXONOMY = 'job_category';

    public function register(): void {
        $labels = [
            'name'              => __('Berufsfelder', 'recruiting-playbook'),
            'singular_name'     => __('Berufsfeld', 'recruiting-playbook'),
            'search_items'      => __('Berufsfelder durchsuchen', 'recruiting-playbook'),
            'all_items'         => __('Alle Berufsfelder', 'recruiting-playbook'),
            'parent_item'       => __('Übergeordnetes Berufsfeld', 'recruiting-playbook'),
            'parent_item_colon' => __('Übergeordnetes Berufsfeld:', 'recruiting-playbook'),
            'edit_item'         => __('Berufsfeld bearbeiten', 'recruiting-playbook'),
            'update_item'       => __('Berufsfeld aktualisieren', 'recruiting-playbook'),
            'add_new_item'      => __('Neues Berufsfeld', 'recruiting-playbook'),
            'new_item_name'     => __('Neuer Berufsfeld-Name', 'recruiting-playbook'),
            'menu_name'         => __('Berufsfelder', 'recruiting-playbook'),
        ];

        $args = [
            'hierarchical'      => true, // Kategorien mit Eltern/Kind
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'berufsfeld'],
            'show_in_rest'      => true,
            'rest_base'         => 'job-categories',
        ];

        register_taxonomy(self::TAXONOMY, [JobListing::POST_TYPE], $args);
    }
}
```

### JobLocation.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Taxonomies;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Taxonomy: Standort
 */
class JobLocation {

    public const TAXONOMY = 'job_location';

    public function register(): void {
        $labels = [
            'name'              => __('Standorte', 'recruiting-playbook'),
            'singular_name'     => __('Standort', 'recruiting-playbook'),
            'search_items'      => __('Standorte durchsuchen', 'recruiting-playbook'),
            'all_items'         => __('Alle Standorte', 'recruiting-playbook'),
            'edit_item'         => __('Standort bearbeiten', 'recruiting-playbook'),
            'update_item'       => __('Standort aktualisieren', 'recruiting-playbook'),
            'add_new_item'      => __('Neuer Standort', 'recruiting-playbook'),
            'new_item_name'     => __('Neuer Standort-Name', 'recruiting-playbook'),
            'menu_name'         => __('Standorte', 'recruiting-playbook'),
        ];

        $args = [
            'hierarchical'      => false, // Flat wie Tags
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'standort'],
            'show_in_rest'      => true,
            'rest_base'         => 'job-locations',
        ];

        register_taxonomy(self::TAXONOMY, [JobListing::POST_TYPE], $args);
    }
}
```

### EmploymentType.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Taxonomies;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Taxonomy: Beschäftigungsart
 */
class EmploymentType {

    public const TAXONOMY = 'employment_type';

    public function register(): void {
        $labels = [
            'name'              => __('Beschäftigungsarten', 'recruiting-playbook'),
            'singular_name'     => __('Beschäftigungsart', 'recruiting-playbook'),
            'search_items'      => __('Beschäftigungsarten durchsuchen', 'recruiting-playbook'),
            'all_items'         => __('Alle Beschäftigungsarten', 'recruiting-playbook'),
            'edit_item'         => __('Beschäftigungsart bearbeiten', 'recruiting-playbook'),
            'update_item'       => __('Beschäftigungsart aktualisieren', 'recruiting-playbook'),
            'add_new_item'      => __('Neue Beschäftigungsart', 'recruiting-playbook'),
            'new_item_name'     => __('Neuer Name', 'recruiting-playbook'),
            'menu_name'         => __('Beschäftigungsarten', 'recruiting-playbook'),
        ];

        $args = [
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'beschaeftigung'],
            'show_in_rest'      => true,
            'rest_base'         => 'employment-types',
        ];

        register_taxonomy(self::TAXONOMY, [JobListing::POST_TYPE], $args);

        // Standard-Werte bei Aktivierung einfügen
        add_action('admin_init', [$this, 'insertDefaults']);
    }

    /**
     * Standard-Beschäftigungsarten einfügen
     */
    public function insertDefaults(): void {
        if (get_option('rp_employment_types_installed')) {
            return;
        }

        $defaults = [
            'vollzeit'    => __('Vollzeit', 'recruiting-playbook'),
            'teilzeit'    => __('Teilzeit', 'recruiting-playbook'),
            'minijob'     => __('Minijob', 'recruiting-playbook'),
            'ausbildung'  => __('Ausbildung', 'recruiting-playbook'),
            'praktikum'   => __('Praktikum', 'recruiting-playbook'),
            'werkstudent' => __('Werkstudent', 'recruiting-playbook'),
            'freiberuflich' => __('Freiberuflich', 'recruiting-playbook'),
        ];

        foreach ($defaults as $slug => $name) {
            if (!term_exists($slug, self::TAXONOMY)) {
                wp_insert_term($name, self::TAXONOMY, ['slug' => $slug]);
            }
        }

        update_option('rp_employment_types_installed', true);
    }
}
```

---

## 6. Meta-Felder

### JobMeta.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\MetaBoxes;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Meta-Felder für Stellenanzeigen
 */
class JobMeta {

    /**
     * Meta-Felder Definitionen
     */
    private const FIELDS = [
        '_rp_salary_min' => [
            'type' => 'number',
            'label' => 'Gehalt (Min)',
            'sanitize' => 'absint',
        ],
        '_rp_salary_max' => [
            'type' => 'number',
            'label' => 'Gehalt (Max)',
            'sanitize' => 'absint',
        ],
        '_rp_salary_currency' => [
            'type' => 'select',
            'label' => 'Währung',
            'options' => ['EUR', 'CHF', 'USD'],
            'default' => 'EUR',
        ],
        '_rp_salary_period' => [
            'type' => 'select',
            'label' => 'Gehaltszeitraum',
            'options' => [
                'hour' => 'Pro Stunde',
                'month' => 'Pro Monat',
                'year' => 'Pro Jahr',
            ],
            'default' => 'month',
        ],
        '_rp_hide_salary' => [
            'type' => 'checkbox',
            'label' => 'Gehalt nicht anzeigen',
            'sanitize' => 'boolval',
        ],
        '_rp_application_deadline' => [
            'type' => 'date',
            'label' => 'Bewerbungsfrist',
            'sanitize' => 'sanitize_text_field',
        ],
        '_rp_contact_person' => [
            'type' => 'text',
            'label' => 'Ansprechpartner',
            'sanitize' => 'sanitize_text_field',
        ],
        '_rp_contact_email' => [
            'type' => 'email',
            'label' => 'Kontakt E-Mail',
            'sanitize' => 'sanitize_email',
        ],
        '_rp_contact_phone' => [
            'type' => 'tel',
            'label' => 'Kontakt Telefon',
            'sanitize' => 'sanitize_text_field',
        ],
        '_rp_remote_option' => [
            'type' => 'select',
            'label' => 'Remote-Arbeit',
            'options' => [
                '' => 'Keine Angabe',
                'no' => 'Keine Remote-Arbeit',
                'hybrid' => 'Hybrid (teilweise Remote)',
                'full' => '100% Remote möglich',
            ],
        ],
        '_rp_start_date' => [
            'type' => 'text',
            'label' => 'Startdatum',
            'placeholder' => 'z.B. "Ab sofort" oder "01.04.2025"',
            'sanitize' => 'sanitize_text_field',
        ],
    ];

    /**
     * Meta Box registrieren
     */
    public function register(): void {
        add_meta_box(
            'rp_job_details',
            __('Stellen-Details', 'recruiting-playbook'),
            [$this, 'render'],
            JobListing::POST_TYPE,
            'normal',
            'high'
        );

        // REST API Meta registrieren
        foreach (self::FIELDS as $key => $field) {
            register_post_meta(JobListing::POST_TYPE, $key, [
                'show_in_rest' => true,
                'single' => true,
                'type' => $this->getRestType($field['type']),
            ]);
        }
    }

    /**
     * Meta Box rendern
     */
    public function render(\WP_Post $post): void {
        wp_nonce_field('rp_job_meta', 'rp_job_meta_nonce');

        echo '<div class="rp-meta-fields">';

        // Gehalt
        echo '<fieldset class="rp-fieldset">';
        echo '<legend>' . esc_html__('Gehalt', 'recruiting-playbook') . '</legend>';
        echo '<div class="rp-field-group">';
        $this->renderField('_rp_salary_min', $post);
        $this->renderField('_rp_salary_max', $post);
        $this->renderField('_rp_salary_currency', $post);
        $this->renderField('_rp_salary_period', $post);
        $this->renderField('_rp_hide_salary', $post);
        echo '</div>';
        echo '</fieldset>';

        // Kontakt
        echo '<fieldset class="rp-fieldset">';
        echo '<legend>' . esc_html__('Ansprechpartner', 'recruiting-playbook') . '</legend>';
        echo '<div class="rp-field-group">';
        $this->renderField('_rp_contact_person', $post);
        $this->renderField('_rp_contact_email', $post);
        $this->renderField('_rp_contact_phone', $post);
        echo '</div>';
        echo '</fieldset>';

        // Details
        echo '<fieldset class="rp-fieldset">';
        echo '<legend>' . esc_html__('Weitere Details', 'recruiting-playbook') . '</legend>';
        echo '<div class="rp-field-group">';
        $this->renderField('_rp_application_deadline', $post);
        $this->renderField('_rp_start_date', $post);
        $this->renderField('_rp_remote_option', $post);
        echo '</div>';
        echo '</fieldset>';

        echo '</div>';

        // Inline Styles
        echo '<style>
            .rp-meta-fields { display: grid; gap: 20px; }
            .rp-fieldset { border: 1px solid #ccd0d4; padding: 15px; }
            .rp-fieldset legend { font-weight: 600; padding: 0 10px; }
            .rp-field-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
            .rp-field { display: flex; flex-direction: column; gap: 5px; }
            .rp-field label { font-weight: 500; }
            .rp-field input, .rp-field select { width: 100%; }
            .rp-field-checkbox { flex-direction: row; align-items: center; }
            .rp-field-checkbox input { width: auto; margin-right: 8px; }
        </style>';
    }

    /**
     * Einzelnes Feld rendern
     */
    private function renderField(string $key, \WP_Post $post): void {
        $field = self::FIELDS[$key];
        $value = get_post_meta($post->ID, $key, true);
        $id = 'rp_' . ltrim($key, '_rp_');

        echo '<div class="rp-field' . ($field['type'] === 'checkbox' ? ' rp-field-checkbox' : '') . '">';

        switch ($field['type']) {
            case 'select':
                echo '<label for="' . esc_attr($id) . '">' . esc_html($field['label']) . '</label>';
                echo '<select name="' . esc_attr($key) . '" id="' . esc_attr($id) . '">';
                foreach ($field['options'] as $opt_value => $opt_label) {
                    $selected = ($value === $opt_value || ($value === '' && isset($field['default']) && $field['default'] === $opt_value));
                    echo '<option value="' . esc_attr($opt_value) . '"' . selected($selected, true, false) . '>';
                    echo esc_html(is_string($opt_label) ? $opt_label : $opt_value);
                    echo '</option>';
                }
                echo '</select>';
                break;

            case 'checkbox':
                echo '<input type="checkbox" name="' . esc_attr($key) . '" id="' . esc_attr($id) . '" value="1"' . checked($value, '1', false) . '>';
                echo '<label for="' . esc_attr($id) . '">' . esc_html($field['label']) . '</label>';
                break;

            default:
                echo '<label for="' . esc_attr($id) . '">' . esc_html($field['label']) . '</label>';
                echo '<input type="' . esc_attr($field['type']) . '" ';
                echo 'name="' . esc_attr($key) . '" ';
                echo 'id="' . esc_attr($id) . '" ';
                echo 'value="' . esc_attr($value) . '"';
                if (isset($field['placeholder'])) {
                    echo ' placeholder="' . esc_attr($field['placeholder']) . '"';
                }
                echo '>';
        }

        echo '</div>';
    }

    /**
     * Meta-Werte speichern
     */
    public function save(int $post_id, \WP_Post $post): void {
        // Nonce prüfen
        if (!isset($_POST['rp_job_meta_nonce']) ||
            !wp_verify_nonce($_POST['rp_job_meta_nonce'], 'rp_job_meta')) {
            return;
        }

        // Autosave überspringen
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Berechtigung prüfen
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Felder speichern
        foreach (self::FIELDS as $key => $field) {
            if (isset($_POST[$key])) {
                $value = $_POST[$key];

                // Sanitization
                if (isset($field['sanitize'])) {
                    $value = call_user_func($field['sanitize'], $value);
                } else {
                    $value = sanitize_text_field($value);
                }

                update_post_meta($post_id, $key, $value);
            } else {
                // Checkbox: nicht gesetzt = löschen
                if ($field['type'] === 'checkbox') {
                    delete_post_meta($post_id, $key);
                }
            }
        }
    }

    /**
     * REST API Typ ermitteln
     */
    private function getRestType(string $fieldType): string {
        return match ($fieldType) {
            'number' => 'integer',
            'checkbox' => 'boolean',
            default => 'string',
        };
    }
}
```

---

## 7. Datenbank-Tabellen

### Schema.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Database;

/**
 * Datenbank-Schema Definitionen
 */
class Schema {

    /**
     * Alle Tabellen-Namen
     */
    public static function getTables(): array {
        global $wpdb;

        return [
            'candidates'   => $wpdb->prefix . 'rp_candidates',
            'applications' => $wpdb->prefix . 'rp_applications',
            'documents'    => $wpdb->prefix . 'rp_documents',
            'activity_log' => $wpdb->prefix . 'rp_activity_log',
        ];
    }

    /**
     * SQL für rp_candidates
     */
    public static function getCandidatesTableSql(): string {
        global $wpdb;
        $table = self::getTables()['candidates'];
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
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
        ) {$charset};";
    }

    /**
     * SQL für rp_applications
     */
    public static function getApplicationsTableSql(): string {
        global $wpdb;
        $table = self::getTables()['applications'];
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
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
        ) {$charset};";
    }

    /**
     * SQL für rp_documents
     */
    public static function getDocumentsTableSql(): string {
        global $wpdb;
        $table = self::getTables()['documents'];
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
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
        ) {$charset};";
    }

    /**
     * SQL für rp_activity_log
     */
    public static function getActivityLogTableSql(): string {
        global $wpdb;
        $table = self::getTables()['activity_log'];
        $charset = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
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
        ) {$charset};";
    }
}
```

### Migrator.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Database;

/**
 * Datenbank-Migrationen verwalten
 */
class Migrator {

    private const SCHEMA_VERSION = '1.0.0';
    private const SCHEMA_OPTION = 'rp_db_version';

    /**
     * Tabellen erstellen/aktualisieren
     */
    public function createTables(): void {
        global $wpdb;

        $current_version = get_option(self::SCHEMA_OPTION, '0');

        // Nur wenn Update nötig
        if (version_compare($current_version, self::SCHEMA_VERSION, '>=')) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Alle Tabellen erstellen/aktualisieren
        dbDelta(Schema::getCandidatesTableSql());
        dbDelta(Schema::getApplicationsTableSql());
        dbDelta(Schema::getDocumentsTableSql());
        dbDelta(Schema::getActivityLogTableSql());

        // Version speichern
        update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION);

        // Log erstellen
        $this->log('Database migrated to version ' . self::SCHEMA_VERSION);
    }

    /**
     * Tabellen existieren prüfen
     */
    public function tablesExist(): bool {
        global $wpdb;

        $tables = Schema::getTables();

        foreach ($tables as $table) {
            $result = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            ));

            if ($result !== $table) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tabellen löschen (für Deinstallation)
     */
    public function dropTables(): void {
        global $wpdb;

        $tables = Schema::getTables();

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option(self::SCHEMA_OPTION);
    }

    /**
     * Migration loggen
     */
    private function log(string $message): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Recruiting Playbook] ' . $message);
        }
    }
}
```

### Status-Konstanten

```php
<?php
// Kann in src/Constants.php oder ähnlich

namespace RecruitingPlaybook;

/**
 * Plugin-Konstanten
 */
class ApplicationStatus {
    public const NEW = 'new';
    public const SCREENING = 'screening';
    public const INTERVIEW = 'interview';
    public const OFFER = 'offer';
    public const HIRED = 'hired';
    public const REJECTED = 'rejected';
    public const WITHDRAWN = 'withdrawn';

    public static function getAll(): array {
        return [
            self::NEW => __('Neu', 'recruiting-playbook'),
            self::SCREENING => __('In Prüfung', 'recruiting-playbook'),
            self::INTERVIEW => __('Interview', 'recruiting-playbook'),
            self::OFFER => __('Angebot', 'recruiting-playbook'),
            self::HIRED => __('Eingestellt', 'recruiting-playbook'),
            self::REJECTED => __('Abgelehnt', 'recruiting-playbook'),
            self::WITHDRAWN => __('Zurückgezogen', 'recruiting-playbook'),
        ];
    }

    public static function getColor(string $status): string {
        return match ($status) {
            self::NEW => '#2271b1',      // Blau
            self::SCREENING => '#dba617', // Orange
            self::INTERVIEW => '#9b59b6', // Lila
            self::OFFER => '#1e8cbe',     // Hellblau
            self::HIRED => '#00a32a',     // Grün
            self::REJECTED => '#d63638',  // Rot
            self::WITHDRAWN => '#787c82', // Grau
            default => '#787c82',
        };
    }
}

class DocumentType {
    public const RESUME = 'resume';
    public const COVER_LETTER = 'cover_letter';
    public const CERTIFICATE = 'certificate';
    public const REFERENCE = 'reference';
    public const PORTFOLIO = 'portfolio';
    public const OTHER = 'other';

    public static function getAll(): array {
        return [
            self::RESUME => __('Lebenslauf', 'recruiting-playbook'),
            self::COVER_LETTER => __('Anschreiben', 'recruiting-playbook'),
            self::CERTIFICATE => __('Zertifikat/Zeugnis', 'recruiting-playbook'),
            self::REFERENCE => __('Referenz', 'recruiting-playbook'),
            self::PORTFOLIO => __('Portfolio', 'recruiting-playbook'),
            self::OTHER => __('Sonstiges', 'recruiting-playbook'),
        ];
    }
}
```

---

## 8. Admin-Menü

### Menu.php

```php
<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Admin;

/**
 * Admin-Menü Registrierung
 */
class Menu {

    /**
     * Menü registrieren
     */
    public function register(): void {
        // Hauptmenü
        add_menu_page(
            __('Recruiting Playbook', 'recruiting-playbook'),
            __('Recruiting', 'recruiting-playbook'),
            'manage_recruiting',
            'recruiting-playbook',
            [$this, 'renderDashboard'],
            'dashicons-groups',
            25 // Position nach Kommentare
        );

        // Dashboard (ersetzt Hauptmenü-Eintrag)
        add_submenu_page(
            'recruiting-playbook',
            __('Dashboard', 'recruiting-playbook'),
            __('Dashboard', 'recruiting-playbook'),
            'manage_recruiting',
            'recruiting-playbook',
            [$this, 'renderDashboard']
        );

        // Bewerbungen
        add_submenu_page(
            'recruiting-playbook',
            __('Bewerbungen', 'recruiting-playbook'),
            __('Bewerbungen', 'recruiting-playbook'),
            'view_applications',
            'rp-applications',
            [$this, 'renderApplications']
        );

        // Einstellungen
        add_submenu_page(
            'recruiting-playbook',
            __('Einstellungen', 'recruiting-playbook'),
            __('Einstellungen', 'recruiting-playbook'),
            'manage_recruiting',
            'rp-settings',
            [$this, 'renderSettings']
        );
    }

    /**
     * Dashboard rendern
     */
    public function renderDashboard(): void {
        // React-Container
        echo '<div id="rp-dashboard-root" class="wrap">';
        echo '<h1>' . esc_html__('Recruiting Dashboard', 'recruiting-playbook') . '</h1>';
        echo '<div id="rp-dashboard-app"></div>';
        echo '</div>';
    }

    /**
     * Bewerbungen rendern
     */
    public function renderApplications(): void {
        echo '<div id="rp-applications-root" class="wrap">';
        echo '<h1>' . esc_html__('Bewerbungen', 'recruiting-playbook') . '</h1>';
        echo '<div id="rp-applications-app"></div>';
        echo '</div>';
    }

    /**
     * Einstellungen rendern
     */
    public function renderSettings(): void {
        echo '<div id="rp-settings-root" class="wrap">';
        echo '<h1>' . esc_html__('Einstellungen', 'recruiting-playbook') . '</h1>';
        echo '<div id="rp-settings-app"></div>';
        echo '</div>';
    }
}
```

---

## 9. Build-Prozess

### package.json

```json
{
    "name": "recruiting-playbook",
    "version": "1.0.0",
    "description": "WordPress Recruiting Plugin",
    "scripts": {
        "dev": "npm-run-all --parallel dev:*",
        "dev:css": "tailwindcss -i ./assets/src/css/main.css -o ./assets/dist/css/frontend.css --watch",
        "dev:admin-css": "tailwindcss -i ./assets/src/css/admin.css -o ./assets/dist/css/admin.css --watch",
        "dev:js": "esbuild assets/src/js/frontend.js --bundle --outfile=assets/dist/js/frontend.js --watch",
        "dev:admin-js": "wp-scripts start --webpack-src-dir=assets/src/js/admin --output-path=assets/dist/js",
        "build": "npm-run-all build:*",
        "build:css": "tailwindcss -i ./assets/src/css/main.css -o ./assets/dist/css/frontend.css --minify",
        "build:admin-css": "tailwindcss -i ./assets/src/css/admin.css -o ./assets/dist/css/admin.css --minify",
        "build:js": "esbuild assets/src/js/frontend.js --bundle --minify --outfile=assets/dist/js/frontend.js",
        "build:admin-js": "wp-scripts build --webpack-src-dir=assets/src/js/admin --output-path=assets/dist/js",
        "build:alpine": "cp node_modules/alpinejs/dist/cdn.min.js assets/dist/js/alpine.min.js",
        "lint:js": "wp-scripts lint-js",
        "lint:css": "wp-scripts lint-style",
        "test:js": "wp-scripts test-unit-js"
    },
    "dependencies": {
        "alpinejs": "^3.13.0"
    },
    "devDependencies": {
        "@wordpress/scripts": "^27.0.0",
        "autoprefixer": "^10.4.16",
        "esbuild": "^0.19.0",
        "npm-run-all": "^4.1.5",
        "postcss": "^8.4.31",
        "tailwindcss": "^3.3.5"
    }
}
```

### tailwind.config.js

```javascript
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.php',
        './src/**/*.php',
        './assets/src/js/**/*.js',
    ],
    // rp- Prefix um Konflikte mit Themes zu vermeiden
    prefix: 'rp-',
    theme: {
        extend: {
            colors: {
                'rp-primary': '#2271b1',
                'rp-primary-dark': '#135e96',
                'rp-success': '#00a32a',
                'rp-warning': '#dba617',
                'rp-error': '#d63638',
            },
        },
    },
    plugins: [],
    // WordPress Admin Styles nicht überschreiben
    corePlugins: {
        preflight: false,
    },
};
```

### postcss.config.js

```javascript
module.exports = {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
```

### assets/src/css/main.css

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/*
 * Frontend Styles für Recruiting Playbook
 * Prefix: rp-
 */

/* Job Listing Cards */
.rp-job-card {
    @apply rp-bg-white rp-rounded-lg rp-shadow-sm rp-border rp-border-gray-200 rp-p-6 rp-transition-shadow hover:rp-shadow-md;
}

.rp-job-title {
    @apply rp-text-xl rp-font-semibold rp-text-gray-900 rp-mb-2;
}

.rp-job-meta {
    @apply rp-flex rp-flex-wrap rp-gap-4 rp-text-sm rp-text-gray-600 rp-mb-4;
}

.rp-job-meta-item {
    @apply rp-flex rp-items-center rp-gap-1;
}

/* Buttons */
.rp-btn {
    @apply rp-inline-flex rp-items-center rp-justify-center rp-px-4 rp-py-2 rp-rounded-md rp-font-medium rp-transition-colors;
}

.rp-btn-primary {
    @apply rp-bg-rp-primary rp-text-white hover:rp-bg-rp-primary-dark;
}

.rp-btn-secondary {
    @apply rp-bg-gray-100 rp-text-gray-700 hover:rp-bg-gray-200;
}

/* Forms */
.rp-form-group {
    @apply rp-mb-4;
}

.rp-label {
    @apply rp-block rp-text-sm rp-font-medium rp-text-gray-700 rp-mb-1;
}

.rp-input {
    @apply rp-w-full rp-px-3 rp-py-2 rp-border rp-border-gray-300 rp-rounded-md rp-shadow-sm focus:rp-outline-none focus:rp-ring-2 focus:rp-ring-rp-primary focus:rp-border-transparent;
}

.rp-input-error {
    @apply rp-border-rp-error focus:rp-ring-rp-error;
}

.rp-error-message {
    @apply rp-text-sm rp-text-rp-error rp-mt-1;
}

/* Status Badges */
.rp-badge {
    @apply rp-inline-flex rp-items-center rp-px-2 rp-py-1 rp-rounded-full rp-text-xs rp-font-medium;
}

.rp-badge-new {
    @apply rp-bg-blue-100 rp-text-blue-800;
}

.rp-badge-screening {
    @apply rp-bg-yellow-100 rp-text-yellow-800;
}

.rp-badge-interview {
    @apply rp-bg-purple-100 rp-text-purple-800;
}

.rp-badge-hired {
    @apply rp-bg-green-100 rp-text-green-800;
}

.rp-badge-rejected {
    @apply rp-bg-red-100 rp-text-red-800;
}
```

### assets/src/js/frontend.js

```javascript
/**
 * Frontend JavaScript (Alpine.js)
 */

document.addEventListener('alpine:init', () => {
    // Job Filter Store
    Alpine.store('jobFilter', {
        category: '',
        location: '',
        employmentType: '',
        search: '',

        reset() {
            this.category = '';
            this.location = '';
            this.employmentType = '';
            this.search = '';
        },
    });

    // Job Listing Component
    Alpine.data('jobListing', () => ({
        jobs: [],
        loading: true,
        error: null,

        async init() {
            await this.fetchJobs();
        },

        async fetchJobs() {
            try {
                this.loading = true;
                const response = await fetch('/wp-json/wp/v2/jobs?_embed');
                if (!response.ok) throw new Error('Failed to fetch jobs');
                this.jobs = await response.json();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        get filteredJobs() {
            const filter = Alpine.store('jobFilter');
            return this.jobs.filter(job => {
                if (filter.search && !job.title.rendered.toLowerCase().includes(filter.search.toLowerCase())) {
                    return false;
                }
                // Weitere Filter hier...
                return true;
            });
        },
    }));
});

// Alpine.js ist via CDN/Bundle geladen
```

---

## 10. Coding Standards

### PHP Coding Standards

- **Standard:** WordPress Coding Standards (WPCS)
- **PHP Version:** 8.0+
- **Strict Types:** Immer aktivieren (`declare(strict_types=1);`)
- **Namespace:** `RecruitingPlaybook\`
- **Autoloading:** PSR-4

```bash
# Prüfen
composer phpcs

# Auto-Fix
composer phpcbf
```

### phpcs.xml

```xml
<?xml version="1.0"?>
<ruleset name="Recruiting Playbook">
    <description>WordPress Coding Standards für Recruiting Playbook</description>

    <file>./src</file>
    <file>./recruiting-playbook.php</file>
    <file>./uninstall.php</file>

    <exclude-pattern>./vendor/*</exclude-pattern>
    <exclude-pattern>./node_modules/*</exclude-pattern>
    <exclude-pattern>./assets/dist/*</exclude-pattern>

    <arg name="extensions" value="php"/>
    <arg name="colors"/>
    <arg value="sp"/>

    <rule ref="WordPress">
        <!-- Erlaube PSR-4 Dateinamen -->
        <exclude name="WordPress.Files.FileName"/>
        <!-- Erlaube short array syntax -->
        <exclude name="Generic.Arrays.DisallowShortArraySyntax"/>
    </rule>

    <!-- Minimum PHP Version -->
    <config name="minimum_supported_wp_version" value="6.0"/>
    <config name="testVersion" value="8.0-"/>
</ruleset>
```

### JavaScript/CSS Standards

- **JavaScript:** ESLint via @wordpress/scripts
- **CSS:** Stylelint via @wordpress/scripts

```bash
# JavaScript prüfen
npm run lint:js

# CSS prüfen
npm run lint:css
```

---

## Deliverables Phase 1A

| Item | Beschreibung | Kriterium |
|------|--------------|-----------|
| Plugin aktivierbar | Keine PHP Errors/Warnings | ✅ Aktivierung erfolgreich |
| Composer Setup | PSR-4 Autoloading funktioniert | ✅ Klassen laden |
| Build-Prozess | npm build erfolgreich | ✅ Assets in dist/ |
| CPT job_listing | In Admin sichtbar | ✅ Jobs erstellen möglich |
| Taxonomien | 3 Taxonomien registriert | ✅ In Job-Edit sichtbar |
| Meta-Felder | Alle 11 Felder speicherbar | ✅ Daten bleiben erhalten |
| DB-Tabellen | 4 Tabellen existieren | ✅ Struktur korrekt |
| Admin-Menü | 3 Menüpunkte | ✅ Navigierbar |
| Templates | Archive + Single laden | ✅ Frontend sichtbar |
| Frontend-Assets | Tailwind + Alpine.js | ✅ Styles angewendet |

---

## Nächste Phase: Phase 1B

Nach erfolgreichem Abschluss von Phase 1A:

→ **Phase 1B: Bewerbungs-Flow** (Woche 3-4)
- Bewerbungsformular (Alpine.js)
- REST API Endpoint
- Datei-Upload
- E-Mail-Benachrichtigungen
- SMTP-Konfigurationsprüfung

---

*Technische Spezifikation erstellt: Januar 2025*

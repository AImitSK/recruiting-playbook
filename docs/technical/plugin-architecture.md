# Plugin-Architektur

## Übersicht

Das Plugin folgt einer **objektorientierten Architektur** mit klarer Schichtentrennung:

```
┌─────────────────────────────────────────────────────────────────┐
│                      PLUGIN-ARCHITEKTUR                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                     ENTRY POINT                          │   │
│  │                recruiting-playbook.php                   │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                      CORE LAYER                          │   │
│  │           Plugin, Activator, Deactivator, I18n           │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│           ┌─────────────────┼─────────────────┐                │
│           ▼                 ▼                 ▼                │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐          │
│  │    ADMIN    │   │   PUBLIC    │   │     API     │          │
│  │   (React)   │   │    (PHP)    │   │   (REST)    │          │
│  └──────┬──────┘   └──────┬──────┘   └──────┬──────┘          │
│         │                 │                 │                   │
│         └─────────────────┼─────────────────┘                  │
│                           ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   SERVICE LAYER                          │   │
│  │    JobService, ApplicationService, CandidateService      │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                  REPOSITORY LAYER                        │   │
│  │  JobRepository, ApplicationRepository, CandidateRepo     │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    MODEL LAYER                           │   │
│  │         Job, Application, Candidate, Document            │   │
│  └──────────────────────────┬──────────────────────────────┘   │
│                             │                                   │
│                             ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                      DATABASE                            │   │
│  │            wp_posts + Custom Tables (rp_*)               │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Technische Entscheidungen

| Aspekt | Entscheidung |
|--------|--------------|
| Architektur | OOP ohne Framework |
| Autoloading | Composer PSR-4 |
| Admin-UI | React (@wordpress/scripts) |
| Namespace | `RecruitingPlaybook` |
| Coding Standards | WordPress Coding Standards |
| PHP-Version | 8.0+ |
| WordPress-Version | 6.0+ |

---

## Ordnerstruktur

```
recruiting-playbook/
│
├── recruiting-playbook.php       # Entry Point
├── uninstall.php                 # Cleanup bei Deinstallation
├── composer.json                 # Autoloading & Dependencies
├── package.json                  # NPM für Build-Prozess
├── webpack.config.js             # Asset-Bundling (optional)
│
├── assets/                       # Kompilierte Assets (nicht editieren)
│   ├── css/
│   │   ├── admin.css
│   │   └── public.css
│   ├── js/
│   │   ├── admin.js              # React-Bundle
│   │   └── public.js
│   └── images/
│
├── src/                          # PHP-Quellcode
│   │
│   ├── Core/                     # Kern-Funktionalität
│   │   ├── Plugin.php            # Hauptklasse, Bootstrap
│   │   ├── Activator.php         # Installation
│   │   ├── Deactivator.php       # Deaktivierung
│   │   ├── Uninstaller.php       # Deinstallation
│   │   ├── I18n.php              # Übersetzungen
│   │   ├── Assets.php            # CSS/JS Enqueueing
│   │   └── Database.php          # Tabellen erstellen/migrieren
│   │
│   ├── Admin/                    # WordPress-Admin
│   │   ├── AdminMenu.php         # Menü-Einträge
│   │   ├── AdminPages.php        # Seiten-Rendering
│   │   ├── MetaBoxes.php         # Job-Editor Meta-Boxen
│   │   ├── Settings.php          # Plugin-Einstellungen
│   │   ├── Dashboard.php         # Dashboard-Widget
│   │   └── Notices.php           # Admin-Benachrichtigungen
│   │
│   ├── Frontend/                 # Öffentliche Website
│   │   ├── Shortcodes.php        # [recruiting_jobs] etc.
│   │   ├── Blocks.php            # Gutenberg-Blöcke
│   │   ├── Templates.php         # Template-Loader
│   │   ├── Forms.php             # Bewerbungsformular
│   │   └── Assets.php            # Frontend CSS/JS
│   │
│   ├── Api/                      # REST API
│   │   ├── RestController.php    # Basis-Controller
│   │   ├── JobsController.php    # /jobs Endpoints
│   │   ├── ApplicationsController.php
│   │   ├── CandidatesController.php
│   │   ├── DocumentsController.php
│   │   ├── WebhooksController.php
│   │   ├── ReportsController.php
│   │   └── Authentication.php    # API-Key Validierung
│   │
│   ├── Services/                 # Business-Logik
│   │   ├── JobService.php
│   │   ├── ApplicationService.php
│   │   ├── CandidateService.php
│   │   ├── DocumentService.php
│   │   ├── EmailService.php
│   │   ├── WebhookService.php
│   │   ├── ExportService.php     # DSGVO-Export
│   │   └── AiService.php         # KI-Integration
│   │
│   ├── Repositories/             # Datenzugriff
│   │   ├── JobRepository.php
│   │   ├── ApplicationRepository.php
│   │   ├── CandidateRepository.php
│   │   ├── DocumentRepository.php
│   │   ├── ActivityLogRepository.php
│   │   ├── ApiKeyRepository.php
│   │   └── WebhookRepository.php
│   │
│   ├── Models/                   # Daten-Objekte
│   │   ├── Job.php
│   │   ├── Application.php
│   │   ├── Candidate.php
│   │   ├── Document.php
│   │   ├── ActivityLog.php
│   │   ├── ApiKey.php
│   │   ├── Webhook.php
│   │   └── WebhookDelivery.php
│   │
│   ├── PostTypes/                # Custom Post Types
│   │   ├── JobPostType.php
│   │   └── Taxonomies.php
│   │
│   ├── Hooks/                    # WordPress Hooks
│   │   ├── Actions.php           # add_action Handler
│   │   ├── Filters.php           # add_filter Handler
│   │   └── Ajax.php              # AJAX Handler
│   │
│   ├── Integrations/             # Drittanbieter
│   │   ├── WPML.php
│   │   ├── Polylang.php
│   │   └── GoogleJobs.php
│   │
│   ├── Utilities/                # Hilfsfunktionen
│   │   ├── Sanitizer.php         # Input-Bereinigung
│   │   ├── Validator.php         # Validierung
│   │   ├── Formatter.php         # Datum, Währung, etc.
│   │   ├── Logger.php            # Activity Logging
│   │   └── Encryption.php        # Verschlüsselung
│   │
│   └── Traits/                   # Wiederverwendbare Traits
│       ├── Singleton.php
│       └── HasTimestamps.php
│
├── templates/                    # PHP-Templates (überschreibbar)
│   ├── archive-job.php           # Job-Listing
│   ├── single-job.php            # Job-Einzelansicht
│   ├── partials/
│   │   ├── job-card.php
│   │   ├── job-filters.php
│   │   ├── application-form.php
│   │   └── application-success.php
│   └── emails/
│       ├── application-received.php
│       ├── application-rejected.php
│       └── interview-invitation.php
│
├── admin-ui/                     # React Source (Admin)
│   ├── src/
│   │   ├── index.js              # Entry Point
│   │   ├── App.jsx
│   │   ├── components/
│   │   │   ├── Dashboard/
│   │   │   ├── Applications/
│   │   │   │   ├── KanbanBoard.jsx
│   │   │   │   ├── ApplicationList.jsx
│   │   │   │   ├── ApplicationDetail.jsx
│   │   │   │   └── ApplicationCard.jsx
│   │   │   ├── Candidates/
│   │   │   ├── Settings/
│   │   │   └── common/
│   │   │       ├── Modal.jsx
│   │   │       ├── Spinner.jsx
│   │   │       └── Pagination.jsx
│   │   ├── hooks/
│   │   │   ├── useApplications.js
│   │   │   ├── useCandidates.js
│   │   │   └── useApi.js
│   │   ├── services/
│   │   │   └── api.js
│   │   └── styles/
│   │       └── admin.scss
│   └── package.json
│
├── languages/                    # Übersetzungen
│   ├── recruiting-playbook.pot
│   ├── recruiting-playbook-de_DE.po
│   └── recruiting-playbook-de_DE.mo
│
├── tests/                        # Tests
│   ├── phpunit/
│   │   ├── bootstrap.php
│   │   ├── Unit/
│   │   └── Integration/
│   └── jest/
│       └── components/
│
└── docs/                         # Dokumentation
    └── ...
```

---

## Hauptklassen

### 1. Plugin (Entry Point)

```php
<?php
/**
 * Plugin Name: Recruiting Playbook
 * Description: Professionelles Stellenausschreibungs- und Bewerbermanagement
 * Version: 1.0.0
 * Author: AImitSK
 * Text Domain: recruiting-playbook
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

namespace RecruitingPlaybook;

// Direktzugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Konstanten
define( 'RP_VERSION', '1.0.0' );
define( 'RP_PLUGIN_FILE', __FILE__ );
define( 'RP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader
require_once RP_PLUGIN_DIR . 'vendor/autoload.php';

// Plugin starten
function rp_init() {
    return Core\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'RecruitingPlaybook\\rp_init' );

// Aktivierung/Deaktivierung
register_activation_hook( __FILE__, [ Core\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Core\Deactivator::class, 'deactivate' ] );
```

### 2. Core\Plugin

```php
<?php

namespace RecruitingPlaybook\Core;

use RecruitingPlaybook\Admin\AdminMenu;
use RecruitingPlaybook\Admin\Settings;
use RecruitingPlaybook\Frontend\Shortcodes;
use RecruitingPlaybook\Frontend\Blocks;
use RecruitingPlaybook\Api\RestController;
use RecruitingPlaybook\PostTypes\JobPostType;
use RecruitingPlaybook\PostTypes\Taxonomies;
use RecruitingPlaybook\Hooks\Actions;
use RecruitingPlaybook\Hooks\Filters;

class Plugin {

    use \RecruitingPlaybook\Traits\Singleton;

    /**
     * Plugin initialisieren
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
    }

    /**
     * Abhängigkeiten laden
     */
    private function load_dependencies(): void {
        // Post Types & Taxonomies
        new JobPostType();
        new Taxonomies();
    }

    /**
     * Sprache setzen
     */
    private function set_locale(): void {
        $i18n = new I18n();
        add_action( 'plugins_loaded', [ $i18n, 'load_textdomain' ] );
    }

    /**
     * Admin-Hooks definieren
     */
    private function define_admin_hooks(): void {
        if ( ! is_admin() ) {
            return;
        }

        $admin_menu = new AdminMenu();
        add_action( 'admin_menu', [ $admin_menu, 'register_menus' ] );

        $settings = new Settings();
        add_action( 'admin_init', [ $settings, 'register_settings' ] );

        $assets = new Assets();
        add_action( 'admin_enqueue_scripts', [ $assets, 'enqueue_admin_assets' ] );
    }

    /**
     * Frontend-Hooks definieren
     */
    private function define_public_hooks(): void {
        $shortcodes = new Shortcodes();
        add_action( 'init', [ $shortcodes, 'register' ] );

        $blocks = new Blocks();
        add_action( 'init', [ $blocks, 'register' ] );

        $assets = new \RecruitingPlaybook\Frontend\Assets();
        add_action( 'wp_enqueue_scripts', [ $assets, 'enqueue' ] );
    }

    /**
     * API-Hooks definieren
     */
    private function define_api_hooks(): void {
        add_action( 'rest_api_init', [ RestController::class, 'register_routes' ] );
    }

    /**
     * Plugin-Version abrufen
     */
    public function get_version(): string {
        return RP_VERSION;
    }
}
```

### 3. Core\Activator

```php
<?php

namespace RecruitingPlaybook\Core;

class Activator {

    /**
     * Bei Aktivierung ausführen
     */
    public static function activate(): void {
        // Mindestanforderungen prüfen
        self::check_requirements();

        // Datenbank-Tabellen erstellen
        Database::create_tables();

        // Standard-Optionen setzen
        self::set_default_options();

        // Upload-Verzeichnis erstellen
        self::create_upload_directory();

        // Rollen & Capabilities
        self::add_capabilities();

        // Permalinks neu generieren
        flush_rewrite_rules();

        // Version speichern
        update_option( 'rp_version', RP_VERSION );
        update_option( 'rp_db_version', '1.0.0' );
    }

    /**
     * Mindestanforderungen prüfen
     */
    private static function check_requirements(): void {
        if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
            wp_die(
                __( 'Recruiting Playbook benötigt PHP 8.0 oder höher.', 'recruiting-playbook' ),
                __( 'Plugin-Aktivierung fehlgeschlagen', 'recruiting-playbook' ),
                [ 'back_link' => true ]
            );
        }

        if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
            wp_die(
                __( 'Recruiting Playbook benötigt WordPress 6.0 oder höher.', 'recruiting-playbook' ),
                __( 'Plugin-Aktivierung fehlgeschlagen', 'recruiting-playbook' ),
                [ 'back_link' => true ]
            );
        }
    }

    /**
     * Standard-Optionen setzen
     */
    private static function set_default_options(): void {
        $defaults = [
            'general' => [
                'company_name'     => get_bloginfo( 'name' ),
                'default_currency' => 'EUR',
            ],
            'applications' => [
                'auto_delete_rejected_days' => 180,
                'talent_pool_duration_months' => 24,
            ],
            'forms' => [
                'required_fields' => [ 'first_name', 'last_name', 'email', 'cv' ],
                'max_file_size_mb' => 10,
                'allowed_file_types' => [ 'pdf', 'doc', 'docx' ],
            ],
            'notifications' => [
                'admin_email' => get_option( 'admin_email' ),
                'notify_on_application' => true,
            ],
            'api' => [
                'enabled' => true,
            ],
        ];

        if ( ! get_option( 'rp_settings' ) ) {
            update_option( 'rp_settings', $defaults );
        }
    }

    /**
     * Upload-Verzeichnis erstellen
     */
    private static function create_upload_directory(): void {
        $upload_dir = wp_upload_dir();
        $rp_dir = $upload_dir['basedir'] . '/recruiting-playbook/documents';

        if ( ! file_exists( $rp_dir ) ) {
            wp_mkdir_p( $rp_dir );

            // .htaccess für Sicherheit
            $htaccess = $rp_dir . '/.htaccess';
            file_put_contents( $htaccess, "Order Deny,Allow\nDeny from all" );

            // index.php gegen Directory Listing
            file_put_contents( $rp_dir . '/index.php', '<?php // Silence is golden' );
        }
    }

    /**
     * Capabilities hinzufügen
     */
    private static function add_capabilities(): void {
        $admin = get_role( 'administrator' );
        
        if ( $admin ) {
            // Job Capabilities
            $admin->add_cap( 'edit_jobs' );
            $admin->add_cap( 'edit_others_jobs' );
            $admin->add_cap( 'publish_jobs' );
            $admin->add_cap( 'delete_jobs' );
            
            // Application Capabilities
            $admin->add_cap( 'view_applications' );
            $admin->add_cap( 'edit_applications' );
            $admin->add_cap( 'delete_applications' );
            
            // Settings
            $admin->add_cap( 'manage_recruiting_settings' );
        }
    }
}
```

### 4. Model-Beispiel: Application

```php
<?php

namespace RecruitingPlaybook\Models;

class Application {

    public int $id;
    public int $job_id;
    public int $candidate_id;
    public string $status;
    public ?string $cover_letter;
    public ?int $salary_expectation;
    public ?string $earliest_start_date;
    public ?int $rating;
    public array $rating_details;
    public array $custom_fields;
    public bool $consent_privacy;
    public ?string $consent_privacy_at;
    public bool $consent_talent_pool;
    public string $created_at;
    public string $updated_at;
    public ?string $deleted_at;

    // Verknüpfte Objekte
    public ?Job $job = null;
    public ?Candidate $candidate = null;
    public array $documents = [];

    /**
     * Erlaubte Status-Werte
     */
    public const STATUSES = [
        'new'       => 'Neu',
        'screening' => 'In Prüfung',
        'interview' => 'Interview',
        'offer'     => 'Angebot',
        'hired'     => 'Eingestellt',
        'rejected'  => 'Abgelehnt',
        'withdrawn' => 'Zurückgezogen',
    ];

    /**
     * Status-Übergänge
     */
    public const STATUS_TRANSITIONS = [
        'new'       => [ 'screening', 'rejected', 'withdrawn' ],
        'screening' => [ 'interview', 'rejected', 'withdrawn' ],
        'interview' => [ 'offer', 'rejected', 'withdrawn' ],
        'offer'     => [ 'hired', 'rejected', 'withdrawn' ],
        'hired'     => [],
        'rejected'  => [],
        'withdrawn' => [],
    ];

    /**
     * Aus Datenbank-Row erstellen
     */
    public static function from_row( object $row ): self {
        $application = new self();
        
        $application->id                  = (int) $row->id;
        $application->job_id              = (int) $row->job_id;
        $application->candidate_id        = (int) $row->candidate_id;
        $application->status              = $row->status;
        $application->cover_letter        = $row->cover_letter;
        $application->salary_expectation  = $row->salary_expectation ? (int) $row->salary_expectation : null;
        $application->earliest_start_date = $row->earliest_start_date;
        $application->rating              = $row->rating ? (int) $row->rating : null;
        $application->rating_details      = json_decode( $row->rating_details ?? '{}', true );
        $application->custom_fields       = json_decode( $row->custom_fields ?? '{}', true );
        $application->consent_privacy     = (bool) $row->consent_privacy;
        $application->consent_privacy_at  = $row->consent_privacy_at;
        $application->consent_talent_pool = (bool) $row->consent_talent_pool;
        $application->created_at          = $row->created_at;
        $application->updated_at          = $row->updated_at;
        $application->deleted_at          = $row->deleted_at;

        return $application;
    }

    /**
     * Kann Status wechseln?
     */
    public function can_transition_to( string $new_status ): bool {
        $allowed = self::STATUS_TRANSITIONS[ $this->status ] ?? [];
        return in_array( $new_status, $allowed, true );
    }

    /**
     * Status-Label
     */
    public function get_status_label(): string {
        return self::STATUSES[ $this->status ] ?? $this->status;
    }

    /**
     * Ist abgeschlossen?
     */
    public function is_closed(): bool {
        return in_array( $this->status, [ 'hired', 'rejected', 'withdrawn' ], true );
    }

    /**
     * Zu Array konvertieren (für API)
     */
    public function to_array(): array {
        return [
            'id'                  => $this->id,
            'job_id'              => $this->job_id,
            'candidate_id'        => $this->candidate_id,
            'status'              => $this->status,
            'status_label'        => $this->get_status_label(),
            'cover_letter'        => $this->cover_letter,
            'salary_expectation'  => $this->salary_expectation,
            'earliest_start_date' => $this->earliest_start_date,
            'rating'              => $this->rating,
            'rating_details'      => $this->rating_details,
            'custom_fields'       => $this->custom_fields,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
```

### 5. Repository-Beispiel: ApplicationRepository

```php
<?php

namespace RecruitingPlaybook\Repositories;

use RecruitingPlaybook\Models\Application;

class ApplicationRepository {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'rp_applications';
    }

    /**
     * Alle Bewerbungen abrufen
     */
    public function find_all( array $args = [] ): array {
        global $wpdb;

        $defaults = [
            'job_id'   => null,
            'status'   => null,
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = [ 'deleted_at IS NULL' ];
        $values = [];

        if ( $args['job_id'] ) {
            $where[] = 'job_id = %d';
            $values[] = $args['job_id'];
        }

        if ( $args['status'] ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_sql = implode( ' AND ', $where );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} 
             WHERE {$where_sql} 
             ORDER BY {$args['orderby']} {$args['order']}
             LIMIT %d OFFSET %d",
            array_merge( $values, [ $args['per_page'], $offset ] )
        );

        $rows = $wpdb->get_results( $sql );

        return array_map( [ Application::class, 'from_row' ], $rows );
    }

    /**
     * Einzelne Bewerbung abrufen
     */
    public function find_by_id( int $id ): ?Application {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL",
                $id
            )
        );

        return $row ? Application::from_row( $row ) : null;
    }

    /**
     * Bewerbung erstellen
     */
    public function create( array $data ): int {
        global $wpdb;

        $wpdb->insert(
            $this->table,
            [
                'job_id'                  => $data['job_id'],
                'candidate_id'            => $data['candidate_id'],
                'status'                  => $data['status'] ?? 'new',
                'cover_letter'            => $data['cover_letter'] ?? null,
                'salary_expectation'      => $data['salary_expectation'] ?? null,
                'earliest_start_date'     => $data['earliest_start_date'] ?? null,
                'consent_privacy'         => $data['consent_privacy'] ? 1 : 0,
                'consent_privacy_version' => $data['consent_privacy_version'] ?? null,
                'consent_privacy_at'      => current_time( 'mysql' ),
                'consent_talent_pool'     => ( $data['consent_talent_pool'] ?? false ) ? 1 : 0,
                'source'                  => $data['source'] ?? 'website',
                'ip_address'              => $data['ip_address'] ?? null,
                'custom_fields'           => json_encode( $data['custom_fields'] ?? [] ),
                'created_at'              => current_time( 'mysql' ),
                'updated_at'              => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        return $wpdb->insert_id;
    }

    /**
     * Status aktualisieren
     */
    public function update_status( int $id, string $status, int $changed_by = null ): bool {
        global $wpdb;

        return (bool) $wpdb->update(
            $this->table,
            [
                'status'            => $status,
                'status_changed_at' => current_time( 'mysql' ),
                'status_changed_by' => $changed_by,
                'updated_at'        => current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s', '%d', '%s' ],
            [ '%d' ]
        );
    }

    /**
     * Soft-Delete
     */
    public function delete( int $id ): bool {
        global $wpdb;

        return (bool) $wpdb->update(
            $this->table,
            [
                'deleted_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
    }

    /**
     * Anzahl abrufen
     */
    public function count( array $args = [] ): int {
        global $wpdb;

        $where = [ 'deleted_at IS NULL' ];
        $values = [];

        if ( ! empty( $args['job_id'] ) ) {
            $where[] = 'job_id = %d';
            $values[] = $args['job_id'];
        }

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $where_sql = implode( ' AND ', $where );

        if ( empty( $values ) ) {
            return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE {$where_sql}" );
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE {$where_sql}", $values )
        );
    }
}
```

### 6. Service-Beispiel: ApplicationService

```php
<?php

namespace RecruitingPlaybook\Services;

use RecruitingPlaybook\Models\Application;
use RecruitingPlaybook\Repositories\ApplicationRepository;
use RecruitingPlaybook\Repositories\CandidateRepository;
use RecruitingPlaybook\Repositories\ActivityLogRepository;
use RecruitingPlaybook\Utilities\Logger;

class ApplicationService {

    private ApplicationRepository $applications;
    private CandidateRepository $candidates;
    private ActivityLogRepository $activity_log;
    private EmailService $email_service;
    private WebhookService $webhook_service;

    public function __construct() {
        $this->applications   = new ApplicationRepository();
        $this->candidates     = new CandidateRepository();
        $this->activity_log   = new ActivityLogRepository();
        $this->email_service  = new EmailService();
        $this->webhook_service = new WebhookService();
    }

    /**
     * Neue Bewerbung einreichen
     */
    public function submit( array $data, array $files = [] ): Application {
        // 1. Kandidat erstellen oder finden
        $candidate_id = $this->candidates->find_or_create( [
            'email'      => $data['email'],
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'phone'      => $data['phone'] ?? null,
        ] );

        // 2. Bewerbung erstellen
        $application_id = $this->applications->create( [
            'job_id'              => $data['job_id'],
            'candidate_id'        => $candidate_id,
            'cover_letter'        => $data['cover_letter'] ?? null,
            'salary_expectation'  => $data['salary_expectation'] ?? null,
            'earliest_start_date' => $data['earliest_start_date'] ?? null,
            'consent_privacy'     => $data['consent_privacy'],
            'consent_talent_pool' => $data['consent_talent_pool'] ?? false,
            'custom_fields'       => $data['custom_fields'] ?? [],
            'source'              => $data['source'] ?? 'website',
            'ip_address'          => $_SERVER['REMOTE_ADDR'] ?? null,
        ] );

        // 3. Dokumente speichern
        if ( ! empty( $files ) ) {
            $document_service = new DocumentService();
            foreach ( $files as $type => $file ) {
                $document_service->upload( $application_id, $candidate_id, $type, $file );
            }
        }

        // 4. Activity Log
        $this->activity_log->log( 'application', $application_id, 'created', [
            'job_id' => $data['job_id'],
        ] );

        // 5. E-Mail-Benachrichtigungen
        $application = $this->applications->find_by_id( $application_id );
        $this->email_service->send_application_received( $application );
        $this->email_service->notify_admin_new_application( $application );

        // 6. Webhooks
        $this->webhook_service->trigger( 'application.received', $application );

        // 7. Application Count Cache aktualisieren
        $this->update_job_application_count( $data['job_id'] );

        return $application;
    }

    /**
     * Status ändern
     */
    public function change_status( int $application_id, string $new_status, ?string $note = null ): Application {
        $application = $this->applications->find_by_id( $application_id );

        if ( ! $application ) {
            throw new \InvalidArgumentException( 'Application not found' );
        }

        if ( ! $application->can_transition_to( $new_status ) ) {
            throw new \InvalidArgumentException( 
                sprintf( 'Cannot transition from %s to %s', $application->status, $new_status )
            );
        }

        $old_status = $application->status;

        // Status aktualisieren
        $this->applications->update_status( 
            $application_id, 
            $new_status, 
            get_current_user_id() 
        );

        // Activity Log
        $this->activity_log->log( 'application', $application_id, 'status_changed', [
            'old_status' => $old_status,
            'new_status' => $new_status,
            'note'       => $note,
        ] );

        // Webhooks
        $updated_application = $this->applications->find_by_id( $application_id );
        $this->webhook_service->trigger( 'application.status_changed', $updated_application, [
            'old_status' => $old_status,
        ] );

        // Spezifische Events
        if ( $new_status === 'hired' ) {
            $this->webhook_service->trigger( 'application.hired', $updated_application );
        } elseif ( $new_status === 'rejected' ) {
            $this->webhook_service->trigger( 'application.rejected', $updated_application );
        }

        return $updated_application;
    }

    /**
     * Bewerbung löschen (DSGVO)
     */
    public function delete_gdpr( int $application_id, string $reason = '' ): void {
        $application = $this->applications->find_by_id( $application_id );

        if ( ! $application ) {
            throw new \InvalidArgumentException( 'Application not found' );
        }

        // Dokumente löschen
        $document_service = new DocumentService();
        $document_service->delete_by_application( $application_id );

        // Soft-Delete
        $this->applications->delete( $application_id );

        // Activity Log (ohne personenbezogene Daten)
        $this->activity_log->log( 'application', $application_id, 'deleted_gdpr', [
            'reason' => $reason,
        ] );

        // Job Application Count aktualisieren
        $this->update_job_application_count( $application->job_id );
    }

    /**
     * Job Application Count Cache aktualisieren
     */
    private function update_job_application_count( int $job_id ): void {
        $count = $this->applications->count( [ 'job_id' => $job_id ] );
        update_post_meta( $job_id, '_rp_application_count', $count );
    }
}
```

### 7. REST API Controller

```php
<?php

namespace RecruitingPlaybook\Api;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use RecruitingPlaybook\Services\ApplicationService;
use RecruitingPlaybook\Repositories\ApplicationRepository;

class ApplicationsController extends WP_REST_Controller {

    protected $namespace = 'recruiting/v1';
    protected $rest_base = 'applications';

    private ApplicationService $service;
    private ApplicationRepository $repository;

    public function __construct() {
        $this->service    = new ApplicationService();
        $this->repository = new ApplicationRepository();
    }

    /**
     * Routen registrieren
     */
    public function register_routes(): void {
        // GET /applications
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_items' ],
                'permission_callback' => [ $this, 'get_items_permissions_check' ],
                'args'                => $this->get_collection_params(),
            ],
        ] );

        // GET /applications/{id}
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_item' ],
                'permission_callback' => [ $this, 'get_item_permissions_check' ],
            ],
        ] );

        // PUT /applications/{id}/status
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/status', [
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_status' ],
                'permission_callback' => [ $this, 'update_item_permissions_check' ],
                'args'                => [
                    'status' => [
                        'required'    => true,
                        'type'        => 'string',
                        'enum'        => array_keys( \RecruitingPlaybook\Models\Application::STATUSES ),
                    ],
                    'note' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ] );
    }

    /**
     * Berechtigung: Liste abrufen
     */
    public function get_items_permissions_check( WP_REST_Request $request ): bool {
        return Authentication::check_permission( $request, 'applications_read' );
    }

    /**
     * Berechtigung: Einzeln abrufen
     */
    public function get_item_permissions_check( WP_REST_Request $request ): bool {
        return Authentication::check_permission( $request, 'applications_read' );
    }

    /**
     * Berechtigung: Aktualisieren
     */
    public function update_item_permissions_check( WP_REST_Request $request ): bool {
        return Authentication::check_permission( $request, 'applications_write' );
    }

    /**
     * GET /applications
     */
    public function get_items( WP_REST_Request $request ): WP_REST_Response {
        $applications = $this->repository->find_all( [
            'job_id'   => $request->get_param( 'job_id' ),
            'status'   => $request->get_param( 'status' ),
            'per_page' => $request->get_param( 'per_page' ) ?? 20,
            'page'     => $request->get_param( 'page' ) ?? 1,
        ] );

        $total = $this->repository->count( [
            'job_id' => $request->get_param( 'job_id' ),
            'status' => $request->get_param( 'status' ),
        ] );

        $data = array_map( fn( $app ) => $app->to_array(), $applications );

        $response = new WP_REST_Response( [
            'data' => $data,
            'meta' => [
                'total'        => $total,
                'per_page'     => $request->get_param( 'per_page' ) ?? 20,
                'current_page' => $request->get_param( 'page' ) ?? 1,
                'total_pages'  => ceil( $total / ( $request->get_param( 'per_page' ) ?? 20 ) ),
            ],
        ] );

        return $response;
    }

    /**
     * GET /applications/{id}
     */
    public function get_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $application = $this->repository->find_by_id( $request->get_param( 'id' ) );

        if ( ! $application ) {
            return new WP_Error( 
                'not_found', 
                __( 'Bewerbung nicht gefunden', 'recruiting-playbook' ), 
                [ 'status' => 404 ] 
            );
        }

        return new WP_REST_Response( $application->to_array() );
    }

    /**
     * PUT /applications/{id}/status
     */
    public function update_status( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        try {
            $application = $this->service->change_status(
                $request->get_param( 'id' ),
                $request->get_param( 'status' ),
                $request->get_param( 'note' )
            );

            return new WP_REST_Response( $application->to_array() );

        } catch ( \InvalidArgumentException $e ) {
            return new WP_Error( 
                'invalid_request', 
                $e->getMessage(), 
                [ 'status' => 400 ] 
            );
        }
    }
}
```

---

## WordPress Hooks

### Eigene Actions

```php
// Nach Bewerbungseingang
do_action( 'rp_application_received', $application );

// Nach Status-Änderung
do_action( 'rp_application_status_changed', $application, $old_status, $new_status );

// Nach Einstellung
do_action( 'rp_application_hired', $application );

// Vor DSGVO-Löschung
do_action( 'rp_before_candidate_delete', $candidate_id );

// Nach DSGVO-Löschung
do_action( 'rp_after_candidate_delete', $candidate_id );
```

### Eigene Filters

```php
// Bewerbungsformular-Felder anpassen
$fields = apply_filters( 'rp_application_form_fields', $fields, $job_id );

// E-Mail-Empfänger anpassen
$recipients = apply_filters( 'rp_notification_recipients', $recipients, $application );

// Erlaubte Dateitypen
$types = apply_filters( 'rp_allowed_file_types', [ 'pdf', 'doc', 'docx' ] );

// Max. Dateigröße
$size = apply_filters( 'rp_max_file_size', 10 * 1024 * 1024 ); // 10 MB

// Job-Query anpassen
$query_args = apply_filters( 'rp_job_query_args', $query_args );

// API Response anpassen
$response = apply_filters( 'rp_api_application_response', $response, $application );
```

---

## Composer.json

```json
{
    "name": "aimitsk/recruiting-playbook",
    "description": "WordPress Recruiting Plugin",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "authors": [
        {
            "name": "AImitSK",
            "homepage": "https://github.com/AImitSK"
        }
    ],
    "require": {
        "php": ">=8.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5",
        "wp-coding-standards/wpcs": "^3.0",
        "phpcompatibility/phpcompatibility-wp": "*",
        "dealerdirect/phpcodesniffer-composer-installer": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "RecruitingPlaybook\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "RecruitingPlaybook\\Tests\\": "tests/phpunit/"
        }
    },
    "scripts": {
        "phpcs": "phpcs --standard=WordPress src/",
        "phpcbf": "phpcbf --standard=WordPress src/",
        "test": "phpunit"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true
        }
    }
}
```

---

## Package.json (Admin UI)

```json
{
    "name": "recruiting-playbook-admin",
    "version": "1.0.0",
    "private": true,
    "scripts": {
        "start": "wp-scripts start",
        "build": "wp-scripts build",
        "lint:js": "wp-scripts lint-js",
        "lint:css": "wp-scripts lint-style",
        "test": "wp-scripts test-unit-js"
    },
    "devDependencies": {
        "@wordpress/scripts": "^26.0.0"
    },
    "dependencies": {
        "@wordpress/api-fetch": "^6.0.0",
        "@wordpress/components": "^25.0.0",
        "@wordpress/data": "^9.0.0",
        "@wordpress/element": "^5.0.0",
        "@wordpress/i18n": "^4.0.0",
        "react-beautiful-dnd": "^13.1.1"
    }
}
```

---

## Build-Prozess

```bash
# PHP Dependencies installieren
composer install

# NPM Dependencies installieren (für Admin UI)
cd admin-ui && npm install

# Development
npm start

# Production Build
npm run build

# PHP Linting
composer phpcs

# PHP Linting Fix
composer phpcbf

# Tests
composer test
npm test
```

---

*Letzte Aktualisierung: Januar 2025*

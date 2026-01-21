<?php

declare(strict_types=1);

namespace RecruitingPlaybook\Core;

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

        // TODO: Post Types & Taxonomien registrieren (Phase 1A, Woche 2)
        // $this->registerPostTypes();
        // $this->registerTaxonomies();

        // Admin-Bereich
        if (is_admin()) {
            $this->initAdmin();
        }

        // Assets laden
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Übersetzungen laden
     */
    private function loadI18n(): void {
        load_plugin_textdomain(
            'recruiting-playbook',
            false,
            dirname(RP_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Admin-Bereich initialisieren
     */
    private function initAdmin(): void {
        // Admin-Menü registrieren
        add_action('admin_menu', [$this, 'registerAdminMenu']);

        // Aktivierungs-Redirect (Setup-Wizard)
        add_action('admin_init', [$this, 'activationRedirect']);
    }

    /**
     * Admin-Menü registrieren
     */
    public function registerAdminMenu(): void {
        // Hauptmenü
        add_menu_page(
            __('Recruiting Playbook', 'recruiting-playbook'),
            __('Recruiting', 'recruiting-playbook'),
            'manage_options',
            'recruiting-playbook',
            [$this, 'renderDashboard'],
            'dashicons-groups',
            25
        );

        // Dashboard (ersetzt Hauptmenü-Eintrag)
        add_submenu_page(
            'recruiting-playbook',
            __('Dashboard', 'recruiting-playbook'),
            __('Dashboard', 'recruiting-playbook'),
            'manage_options',
            'recruiting-playbook',
            [$this, 'renderDashboard']
        );

        // Einstellungen
        add_submenu_page(
            'recruiting-playbook',
            __('Einstellungen', 'recruiting-playbook'),
            __('Einstellungen', 'recruiting-playbook'),
            'manage_options',
            'rp-settings',
            [$this, 'renderSettings']
        );
    }

    /**
     * Dashboard rendern
     */
    public function renderDashboard(): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Recruiting Playbook', 'recruiting-playbook') . '</h1>';
        echo '<div class="notice notice-info"><p>';
        echo esc_html__('Willkommen! Das Plugin befindet sich in der Entwicklung (Phase 1A).', 'recruiting-playbook');
        echo '</p></div>';

        // Status-Übersicht
        echo '<div class="card" style="max-width: 600px; padding: 20px;">';
        echo '<h2>' . esc_html__('Entwicklungsstatus', 'recruiting-playbook') . '</h2>';
        echo '<table class="widefat striped">';
        echo '<tr><td>Plugin Version</td><td><code>' . esc_html(RP_VERSION) . '</code></td></tr>';
        echo '<tr><td>PHP Version</td><td><code>' . esc_html(PHP_VERSION) . '</code></td></tr>';
        echo '<tr><td>WordPress Version</td><td><code>' . esc_html(get_bloginfo('version')) . '</code></td></tr>';
        echo '<tr><td>Datenbank-Version</td><td><code>' . esc_html(get_option('rp_db_version', 'nicht installiert')) . '</code></td></tr>';
        echo '</table>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Einstellungen rendern
     */
    public function renderSettings(): void {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Einstellungen', 'recruiting-playbook') . '</h1>';
        echo '<p>' . esc_html__('Einstellungen werden in Phase 1B implementiert.', 'recruiting-playbook') . '</p>';
        echo '</div>';
    }

    /**
     * Nach Aktivierung zur Dashboard-Seite weiterleiten
     */
    public function activationRedirect(): void {
        if (get_option('rp_activation_redirect', false)) {
            delete_option('rp_activation_redirect');

            if (!isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=recruiting-playbook'));
                exit;
            }
        }
    }

    /**
     * Frontend-Assets laden
     */
    public function enqueueFrontendAssets(): void {
        // TODO: Nur auf relevanten Seiten laden (Phase 1A, Woche 2)
    }

    /**
     * Admin-Assets laden
     */
    public function enqueueAdminAssets(string $hook): void {
        // Nur auf Plugin-Seiten laden
        if (!str_contains($hook, 'recruiting')) {
            return;
        }

        // TODO: React Admin UI laden (Phase 1A)
    }

    // Prevent cloning and unserialization
    private function __clone() {}

    public function __wakeup(): void {
        throw new \Exception('Cannot unserialize singleton');
    }
}

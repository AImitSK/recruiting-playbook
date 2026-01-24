<?php
/**
 * Plugin Name: Recruiting Playbook
 * Plugin URI: https://recruiting-playbook.de/
 * Description: Professionelles Bewerbermanagement für WordPress
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Recruiting Playbook
 * Author URI: https://recruiting-playbook.de
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

/**
 * Requirements prüfen
 */
function rp_check_requirements(): bool {
    if (version_compare(PHP_VERSION, RP_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            printf(
                /* translators: 1: Required PHP version, 2: Current PHP version */
                esc_html__('Recruiting Playbook benötigt PHP %1$s oder höher. Sie nutzen PHP %2$s.', 'recruiting-playbook'),
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
                /* translators: 1: Required WP version, 2: Current WP version */
                esc_html__('Recruiting Playbook benötigt WordPress %1$s oder höher. Sie nutzen WordPress %2$s.', 'recruiting-playbook'),
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
        wp_die(esc_html__('Plugin-Aktivierung fehlgeschlagen. Anforderungen nicht erfüllt.', 'recruiting-playbook'));
    }

    require_once RP_PLUGIN_DIR . 'src/Core/Activator.php';
    Core\Activator::activate();
});

// Deaktivierung
register_deactivation_hook(__FILE__, function() {
    require_once RP_PLUGIN_DIR . 'src/Core/Deactivator.php';
    Core\Deactivator::deactivate();
});

// Plugin initialisieren (im init Hook mit Priorität 5 - vor Standard-Hooks)
add_action('init', function() {
    if (!rp_check_requirements()) {
        return;
    }

    // Autoloader muss vorhanden sein
    if (!class_exists('RecruitingPlaybook\Core\Plugin')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            esc_html_e('Recruiting Playbook: Bitte führen Sie "composer install" aus.', 'recruiting-playbook');
            echo '</p></div>';
        });
        return;
    }

    Core\Plugin::getInstance()->init();
}, 5);

<?php
/**
 * Prevent direct access.
 *
 * @package RecruitingPlaybook
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name: Recruiting Playbook
 * Plugin URI: https://recruiting-playbook.com/
 * Description: Professionelles Bewerbermanagement für WordPress
 * Version: 1.3.4
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Stefan Kühne, Peter Kühne
 * Author URI: https://sk-online-marketing.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recruiting-playbook
 * Domain Path: /languages
 *
  *
 * @package RecruitingPlaybook
 */

// Plugin-Konstanten (WordPress.org: min. 4 Zeichen Prefix).
define( 'RECPL_VERSION', '1.3.4' );
define( 'RECPL_PLUGIN_FILE', __FILE__ );
define( 'RECPL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RECPL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RECPL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'RECPL_MIN_PHP_VERSION', '8.0' );
define( 'RECPL_MIN_WP_VERSION', '6.0' );

// Backwards Compatibility Aliase (für bestehenden Code).
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define( 'RP_VERSION', RECPL_VERSION );
define( 'RP_PLUGIN_FILE', RECPL_PLUGIN_FILE );
define( 'RP_PLUGIN_DIR', RECPL_PLUGIN_DIR );
define( 'RP_PLUGIN_URL', RECPL_PLUGIN_URL );
define( 'RP_PLUGIN_BASENAME', RECPL_PLUGIN_BASENAME );
define( 'RP_MIN_PHP_VERSION', RECPL_MIN_PHP_VERSION );
define( 'RP_MIN_WP_VERSION', RECPL_MIN_WP_VERSION );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

// Bootstrap the plugin (namespace code in separate file for Freemius compatibility).
require_once __DIR__ . '/src/bootstrap.php';

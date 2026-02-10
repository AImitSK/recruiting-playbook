<?php
/**
 * Plugin Name: Recruiting Playbook KI
 * Plugin URI: https://recruiting-playbook.com/
 * Description: KI-Addon für Recruiting Playbook – KI-gestützte Stellenanzeigen, Textverbesserung und CV-Matching.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Stefan Kühne
 * Author URI: https://sk-online-marketing.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recruiting-playbook-ki
 * Domain Path: /languages
 *
 * @package RecruitingPlaybookKi
 */

// Direkten Zugriff verhindern.
defined( 'ABSPATH' ) || exit;

// Plugin-Konstanten.
define( 'RPK_VERSION', '1.0.0' );
define( 'RPK_PLUGIN_FILE', __FILE__ );
define( 'RPK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RPK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RPK_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! function_exists( 'rpk_fs' ) ) {
	/**
	 * Freemius SDK Helper für das KI-Addon
	 *
	 * @return \Freemius
	 */
	function rpk_fs() {
		global $rpk_fs;

		if ( ! isset( $rpk_fs ) ) {
			// Freemius SDK vom Parent-Plugin laden.
			if ( file_exists( dirname( dirname( __FILE__ ) ) . '/recruiting-playbook/freemius/start.php' ) ) {
				// Standard: SDK aus Parent-Plugin-Ordner (gleiche Ebene).
				require_once dirname( dirname( __FILE__ ) ) . '/recruiting-playbook/freemius/start.php';
			} elseif ( file_exists( dirname( dirname( __FILE__ ) ) . '/recruiting-playbook-premium/freemius/start.php' ) ) {
				// Premium-Parent-Plugin-Ordner.
				require_once dirname( dirname( __FILE__ ) ) . '/recruiting-playbook-premium/freemius/start.php';
			} elseif ( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/recruiting-playbook/freemius/start.php' ) ) {
				// Fallback für Symlink-Setups: WP_PLUGIN_DIR nutzen.
				require_once WP_PLUGIN_DIR . '/recruiting-playbook/freemius/start.php';
			} elseif ( defined( 'WP_PLUGIN_DIR' ) && file_exists( WP_PLUGIN_DIR . '/recruiting-playbook-premium/freemius/start.php' ) ) {
				require_once WP_PLUGIN_DIR . '/recruiting-playbook-premium/freemius/start.php';
			} elseif ( function_exists( 'fs_dynamic_init' ) ) {
				// SDK bereits vom Parent geladen.
			} elseif ( file_exists( dirname( __FILE__ ) . '/vendor/freemius/start.php' ) ) {
				// Eigene SDK-Kopie.
				require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
			}

			$rpk_fs = fs_dynamic_init(
				array(
					'id'                => '23996',
					'slug'              => 'recruiting-playbook-ki',
					'type'              => 'plugin',
					'public_key'        => 'pk_910d97dbb642dd13c0dd443c0c75c',
					'is_premium'        => true,
					'is_premium_only'   => true,
					'has_paid_plans'    => true,
					'wp_org_gatekeeper' => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
					'parent'            => array(
						'id'         => '23533',
						'slug'       => 'recruiting-playbook',
						'public_key' => 'pk_169f4df2b23e899b6b4f9c3df4548',
						'name'       => 'Recruiting Playbook',
					),
					'menu'              => array(
						'slug'    => 'recruiting-playbook-ki',
						'support' => false,
						'parent'  => array(
							'slug' => 'options-general.php',
						),
					),
				)
			);
		}

		return $rpk_fs;
	}
}

/**
 * Prüft ob das Parent-Plugin geladen und initialisiert ist
 *
 * @return bool
 */
function rpk_fs_is_parent_active_and_loaded() {
	return function_exists( 'rp_fs' );
}

/**
 * Prüft ob das Parent-Plugin aktiviert ist (auch wenn noch nicht geladen)
 *
 * Berücksichtigt Multisite-Netzwerkaktivierung.
 *
 * @return bool
 */
function rpk_fs_is_parent_active() {
	$active_plugins = get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
	}

	foreach ( $active_plugins as $basename ) {
		if ( 0 === strpos( $basename, 'recruiting-playbook/' ) ||
			0 === strpos( $basename, 'recruiting-playbook-premium/' )
		) {
			return true;
		}
	}

	return false;
}

/**
 * Freemius SDK initialisieren
 *
 * Wird aufgerufen wenn das Parent-Plugin bereit ist.
 */
function rpk_fs_init() {
	if ( rpk_fs_is_parent_active_and_loaded() ) {
		// Freemius initialisieren.
		rpk_fs();

		// Default-Währung auf EUR setzen (DACH-Markt).
		rpk_fs()->add_filter(
			'default_currency',
			function ( $currency ) {
				return 'eur';
			}
		);

		// Deutsche Übersetzungen für Freemius SDK.
		rpk_fs()->override_i18n(
			array(
				'activate'         => 'Aktivieren',
				'activate-license' => 'Lizenz aktivieren',
				'upgrade'          => 'Upgrade',
				'license'          => 'Lizenz',
				'cancel'           => 'Abbrechen',
				'ok'               => 'OK',
				'yes'              => 'Ja',
				'no'               => 'Nein',
			)
		);

		// Uninstall-Hook: Addon-spezifische Daten bereinigen.
		rpk_fs()->add_action( 'after_uninstall', 'rpk_fs_uninstall_cleanup' );

		// Signal, dass das Addon-SDK initialisiert wurde.
		do_action( 'rpk_fs_loaded' );

	} else {
		// Parent-Plugin ist nicht aktiv — Admin-Hinweis anzeigen.
		add_action( 'admin_notices', 'rpk_fs_parent_inactive_notice' );
	}
}

/**
 * Admin-Hinweis: Parent-Plugin nicht aktiv
 */
function rpk_fs_parent_inactive_notice() {
	if ( ! is_admin() ) {
		return;
	}

	$message = sprintf(
		/* translators: 1: Addon name, 2: Parent plugin name, 3: link URL */
		__( '<strong>%1$s</strong> benötigt das Plugin <strong>%2$s</strong>. Bitte <a href="%3$s">installieren und aktivieren</a> Sie es zuerst.', 'recruiting-playbook-ki' ),
		'Recruiting Playbook KI',
		'Recruiting Playbook',
		esc_url( admin_url( 'plugins.php' ) )
	);

	echo '<div class="notice notice-error"><p>' . wp_kses( $message, array( 'strong' => array(), 'a' => array( 'href' => array() ) ) ) . '</p></div>';
}

/**
 * Addon-Daten bei Deinstallation bereinigen
 *
 * Löscht nur Addon-spezifische Daten, nicht die des Parent-Plugins.
 */
function rpk_fs_uninstall_cleanup() {
	global $wpdb;

	// KI-Analyse-Tabelle leeren (gehört zum Addon).
	$table = $wpdb->prefix . 'rp_ai_analyses';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

	// Addon-spezifische Options löschen.
	delete_option( 'rp_ai_settings' );
}

// ─── Lade-Reihenfolge steuern ────────────────────────────────────────────────

if ( rpk_fs_is_parent_active_and_loaded() ) {
	// Parent bereits geladen → Addon sofort initialisieren.
	rpk_fs_init();
} elseif ( rpk_fs_is_parent_active() ) {
	// Parent aktiv aber noch nicht geladen → auf rp_fs_loaded warten.
	add_action( 'rp_fs_loaded', 'rpk_fs_init' );
} else {
	// Parent nicht aktiviert → trotzdem initialisieren für Aktivierung/Deinstallation.
	rpk_fs_init();
}

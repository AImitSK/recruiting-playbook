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
 */

// Direkten Zugriff verhindern.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rpk_fs' ) ) {
	// Create a helper function for easy SDK access.
	function rpk_fs() {
		global $rpk_fs;

		if ( ! isset( $rpk_fs ) ) {
			// Include Freemius SDK.
			if ( file_exists( dirname( dirname( __FILE__ ) ) . '/recruiting-playbook/freemius/start.php' ) ) {
				// Try to load SDK from parent plugin folder.
				require_once dirname( dirname( __FILE__ ) ) . '/recruiting-playbook/freemius/start.php';
			} elseif ( file_exists( dirname( dirname( __FILE__ ) ) . '/recruiting-playbook-premium/freemius/start.php' ) ) {
				// Try to load SDK from premium parent plugin folder.
				require_once dirname( dirname( __FILE__ ) ) . '/recruiting-playbook-premium/freemius/start.php';
			} else {
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
						'support' => false,
					),
				)
			);
		}

		return $rpk_fs;
	}
}

function rpk_fs_is_parent_active_and_loaded() {
	// Check if the parent's init SDK method exists.
	return function_exists( 'rp_fs' );
}

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

function rpk_fs_init() {
	if ( rpk_fs_is_parent_active_and_loaded() ) {
		// Init Freemius.
		rpk_fs();

		// Default-Währung auf EUR setzen (DACH-Markt).
		rpk_fs()->add_filter( 'default_currency', function ( $currency ) {
			return 'eur';
		} );

		// Signal that the add-on's SDK was initiated.
		do_action( 'rpk_fs_loaded' );

	} else {
		// Parent is inactive, add your error handling here.
	}
}

if ( rpk_fs_is_parent_active_and_loaded() ) {
	// If parent already included, init add-on.
	rpk_fs_init();
} elseif ( rpk_fs_is_parent_active() ) {
	// Init add-on only after the parent is loaded.
	add_action( 'rp_fs_loaded', 'rpk_fs_init' );
} else {
	// Even though the parent is not activated, execute add-on for activation / uninstall hooks.
	rpk_fs_init();
}

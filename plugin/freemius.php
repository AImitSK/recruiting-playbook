<?php
/**
 * Freemius SDK Integration
 *
 * Initialisiert das Freemius SDK für Lizenzierung und Updates.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Freemius Konfiguration
 *
 * Diese Werte müssen im Freemius Dashboard erstellt und hier eingetragen werden.
 * Anleitung: https://freemius.com/help/documentation/wordpress-sdk/integrating-freemius-sdk/
 */
define( 'RP_FREEMIUS_PLUGIN_ID', '23533' );
define( 'RP_FREEMIUS_PUBLIC_KEY', 'pk_169f4df2b23e899b6b4f9c3df4548' );

/**
 * Prüft ob Freemius konfiguriert ist
 *
 * @return bool
 */
function rp_fs_is_configured(): bool {
	return ! empty( RP_FREEMIUS_PLUGIN_ID ) && ! empty( RP_FREEMIUS_PUBLIC_KEY );
}

/**
 * Freemius SDK initialisieren
 *
 * @return \Freemius|null
 */
function rp_fs() {
	global $rp_fs;

	// Nicht initialisieren wenn nicht konfiguriert.
	if ( ! rp_fs_is_configured() ) {
		return null;
	}

	if ( ! isset( $rp_fs ) ) {
		// Freemius SDK laden.
		$sdk_path = RP_PLUGIN_DIR . 'vendor/freemius/wordpress-sdk/start.php';
		if ( ! file_exists( $sdk_path ) ) {
			return null;
		}
		require_once $sdk_path;

		$rp_fs = fs_dynamic_init(
			array(
				'id'                  => RP_FREEMIUS_PLUGIN_ID,
				'slug'                => 'recruiting-playbook',
				'type'                => 'plugin',
				'public_key'          => RP_FREEMIUS_PUBLIC_KEY,
				'is_premium'          => false,
				'premium_suffix'      => 'Pro',
				'has_addons'          => true,
				'has_paid_plans'      => true,
				'has_affiliation'     => 'selected',
				'menu'                => array(
					'slug'    => 'recruiting-playbook',
					'support' => false,
				),
				'is_live'             => true,
			)
		);
	}

	return $rp_fs;
}

// Freemius initialisieren (nur wenn konfiguriert).
if ( rp_fs_is_configured() ) {
	rp_fs();
	do_action( 'rp_fs_loaded' );
}

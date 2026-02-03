<?php
/**
 * Freemius SDK Integration
 *
 * Initialisiert das Freemius SDK für Lizenzierung und Updates.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'rp_fs' ) ) {
    // Create a helper function for easy SDK access.
    function rp_fs() {
        global $rp_fs;

        if ( ! isset( $rp_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';

            $rp_fs = fs_dynamic_init( array(
                'id'                  => '23533',
                'slug'                => 'recruiting-playbook',
                'type'                => 'plugin',
                'public_key'          => 'pk_169f4df2b23e899b6b4f9c3df4548',
                'is_premium'          => true,
                'has_premium_version' => true,
                'has_addons'          => true,  // AI-Addon ist separates Add-on.
                'has_paid_plans'      => true,
                'menu'                => array(
                    'slug'    => 'recruiting-playbook',
                    'support' => false,
                    'account' => true,  // Freemius Account-Seite im Menü aktivieren.
                ),
            ) );
        }

        return $rp_fs;
    }

    // Init Freemius.
    rp_fs();
    // Signal that SDK was initiated.
    do_action( 'rp_fs_loaded' );
}

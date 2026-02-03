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
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                // Automatically removed in the free version. If you're not using the
                // auto-generated free version, delete this line before uploading to wp.org.
                'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
                'menu'                => array(
                    'slug'           => 'recruiting-playbook',
                    'support'        => false,
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

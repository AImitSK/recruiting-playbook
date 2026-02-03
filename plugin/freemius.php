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
            // SDK is auto-loaded through Composer.

            $rp_fs = fs_dynamic_init( array(
                'id'                  => '23533',
                'slug'                => 'recruiting-playbook',
                'type'                => 'plugin',
                'public_key'          => 'pk_169f4df2b23e899b6b4f9c3df4548',
                'is_premium'          => true,  // Freemius generiert Free-Version automatisch beim Deployment.
                'has_premium_version' => true,  // Es gibt Premium-Pläne.
                'has_addons'          => false, // AI ist als Plan integriert, nicht als separates Add-on.
                'has_paid_plans'      => true,  // Paid Plans verfügbar (Pro, AI, Bundle).
                // Sicherheits-Token für WordPress.org - wird automatisch aus Free-Version entfernt.
                'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
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

    // Default-Währung auf EUR setzen.
    rp_fs()->add_filter( 'default_currency', function( $currency ) {
        return 'eur';
    });

    // Sprache auf Deutsch setzen für Checkout.
    rp_fs()->add_filter( 'checkout/parameters', function( $params ) {
        $params['locale'] = 'de_DE';
        $params['language'] = 'de';
        return $params;
    });

    // Locale Parameter zur Pricing-Seite URL hinzufügen.
    add_action( 'admin_init', function() {
        // Nur auf Pricing-Seite.
        if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'pricing' ) !== false ) {
            if ( ! isset( $_GET['language'] ) ) {
                $_GET['language'] = 'de';
            }
            if ( ! isset( $_GET['locale'] ) ) {
                $_GET['locale'] = 'de_DE';
            }
        }
    }, 1 );


    // Deutsche Übersetzungen für Freemius SDK Strings.
    rp_fs()->override_i18n( array(
        // Opt-in Dialog.
        'opt-in-connect'                    => 'Ja, ich bin dabei!',
        'skip'                              => 'Überspringen',
        'opt-in-skip'                       => 'Nicht jetzt',

        // Account Page.
        'account'                           => 'Konto',
        'plan'                              => 'Plan',
        'free'                              => 'Kostenlos',
        'activate'                          => 'Aktivieren',
        'change-plan'                       => 'Plan ändern',
        'upgrade'                           => 'Upgrade',
        'downgrade'                         => 'Downgrade',
        'cancel-subscription'               => 'Abo kündigen',
        'cancel-trial'                      => 'Testphase beenden',
        'renews-in'                         => 'Verlängert sich in %s',
        'expires-in'                        => 'Läuft ab in %s',
        'license'                           => 'Lizenz',
        'activate-license'                  => 'Lizenz aktivieren',
        'sync-license'                      => 'Lizenz synchronisieren',
        'deactivate-license'                => 'Lizenz deaktivieren',
        'name'                              => 'Name',
        'email'                             => 'E-Mail',
        'edit'                              => 'Bearbeiten',
        'update'                            => 'Aktualisieren',
        'delete'                            => 'Löschen',
        'cancel'                            => 'Abbrechen',
        'ok'                                => 'OK',
        'yes'                               => 'Ja',
        'no'                                => 'Nein',
        'save'                              => 'Speichern',
        'send'                              => 'Senden',
        'submit'                            => 'Absenden',
        'continue'                          => 'Weiter',

        // Contact.
        'contact'                           => 'Kontakt',
        'contact-us'                        => 'Kontaktiere uns',
        'send-feedback'                     => 'Feedback senden',
        'message'                           => 'Nachricht',
        'subject'                           => 'Betreff',

        // Misc.
        'version'                           => 'Version',
        'add-ons'                           => 'Add-ons',
        'bundle-plan'                       => 'Bundle-Plan',
        'premium-version'                   => 'Premium-Version',
        'free-version'                      => 'Kostenlose Version',
        'features'                          => 'Features',
        'all-features'                      => 'Alle Features',
    ) );

    // Uninstall Hook - ersetzt uninstall.php für korrektes Freemius Tracking.
    rp_fs()->add_action( 'after_uninstall', 'rp_fs_uninstall_cleanup' );

    // Signal that SDK was initiated.
    do_action( 'rp_fs_loaded' );
}

/**
 * Plugin-Daten bei Deinstallation bereinigen.
 *
 * Wird von Freemius nach dem Uninstall-Event aufgerufen.
 * Dadurch kann Freemius das Uninstall-Feedback erfassen.
 */
function rp_fs_uninstall_cleanup() {
    // Option prüfen: Daten behalten?
    $keep_data = get_option( 'rp_keep_data_on_uninstall', false );

    if ( $keep_data ) {
        return;
    }

    global $wpdb;

    // 1. Custom Post Type Daten löschen.
    $posts = get_posts(
        array(
            'post_type'      => 'job_listing',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        )
    );

    foreach ( $posts as $post_id ) {
        wp_delete_post( $post_id, true );
    }

    // 2. Taxonomie-Terms löschen.
    $taxonomies = array( 'job_category', 'job_location', 'employment_type' );
    foreach ( $taxonomies as $taxonomy ) {
        $terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'fields'     => 'ids',
            )
        );

        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term_id ) {
                wp_delete_term( $term_id, $taxonomy );
            }
        }
    }

    // 3. Custom Tables löschen.
    $tables = array(
        $wpdb->prefix . 'rp_candidates',
        $wpdb->prefix . 'rp_applications',
        $wpdb->prefix . 'rp_documents',
        $wpdb->prefix . 'rp_activity_log',
        $wpdb->prefix . 'rp_notes',
        $wpdb->prefix . 'rp_ratings',
        $wpdb->prefix . 'rp_talent_pool',
        $wpdb->prefix . 'rp_email_templates',
        $wpdb->prefix . 'rp_email_log',
        $wpdb->prefix . 'rp_signatures',
        $wpdb->prefix . 'rp_job_assignments',
        $wpdb->prefix . 'rp_stats_cache',
    );

    foreach ( $tables as $table ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
    }

    // 4. Optionen löschen.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rp_%'" );

    // 5. User Meta löschen.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'rp_%'" );

    // 6. Post Meta löschen (falls noch übrig).
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_rp_%'" );

    // 7. Upload-Ordner löschen.
    $upload_dir    = wp_upload_dir();
    $rp_upload_dir = $upload_dir['basedir'] . '/recruiting-playbook';

    if ( is_dir( $rp_upload_dir ) ) {
        // Rekursiv löschen.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $rp_upload_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ( $files as $file ) {
            if ( $file->isDir() ) {
                rmdir( $file->getRealPath() );
            } else {
                wp_delete_file( $file->getRealPath() );
            }
        }
        rmdir( $rp_upload_dir );
    }

    // Rewrite Rules flushen.
    flush_rewrite_rules();
}

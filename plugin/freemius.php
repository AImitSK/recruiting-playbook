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
                'is_premium'          => false, // Free-Version, keine Lizenz für Basis-Funktionen nötig.
                'has_premium_version' => true,  // Es gibt Premium-Pläne.
                'has_addons'          => false, // AI ist als Plan integriert, nicht als separates Add-on.
                'has_paid_plans'      => true,  // Paid Plans verfügbar (Pro, AI, Bundle).
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

    // Signal that SDK was initiated.
    do_action( 'rp_fs_loaded' );
}

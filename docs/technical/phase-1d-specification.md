# Phase 1D: Technische Spezifikation

> **Woche 7-8: Polish & Pilot**
> Plugin produktionsreif, erste Pilotkunden aktiv

---

## Inhaltsverzeichnis

1. [Setup-Wizard](#1-setup-wizard)
2. [Google for Jobs Schema](#2-google-for-jobs-schema)
3. [Shortcode-Erweiterungen](#3-shortcode-erweiterungen)
4. [Testing](#4-testing)
5. [Internationalisierung](#5-internationalisierung)
6. [Code-Review & Cleanup](#6-code-review--cleanup)
7. [Pilotkunden-Deployment](#7-pilotkunden-deployment)
8. [Dokumentation](#8-dokumentation)
9. [Deliverables & Checkliste](#9-deliverables--checkliste)

---

## 1. Setup-Wizard

### 1.1 Übersicht

Der Setup-Wizard führt neue Benutzer durch die Erstkonfiguration des Plugins.

**Schritte:**
1. Willkommen
2. Firmeninfo eingeben
3. E-Mail-Konfiguration + SMTP-Test
4. Erste Stelle erstellen (optional)
5. Fertig!

### 1.2 Dateistruktur

```
plugin/src/Admin/
├── SetupWizard/
│   ├── SetupWizard.php          # Hauptklasse
│   ├── Steps/
│   │   ├── WelcomeStep.php
│   │   ├── CompanyStep.php
│   │   ├── EmailStep.php
│   │   ├── FirstJobStep.php
│   │   └── CompleteStep.php
│   └── views/
│       └── wizard.php           # Template
```

### 1.3 SetupWizard.php

```php
<?php
/**
 * Setup-Wizard für Erstkonfiguration
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\SetupWizard;

/**
 * Setup-Wizard Klasse
 */
class SetupWizard {

    /**
     * Wizard-Schritte
     *
     * @var array
     */
    private array $steps = [];

    /**
     * Aktueller Schritt
     *
     * @var string
     */
    private string $current_step = '';

    /**
     * Constructor
     */
    public function __construct() {
        $this->steps = [
            'welcome' => [
                'name'    => __( 'Willkommen', 'recruiting-playbook' ),
                'handler' => [ $this, 'renderWelcome' ],
                'save'    => null,
            ],
            'company' => [
                'name'    => __( 'Firmendaten', 'recruiting-playbook' ),
                'handler' => [ $this, 'renderCompany' ],
                'save'    => [ $this, 'saveCompany' ],
            ],
            'email' => [
                'name'    => __( 'E-Mail', 'recruiting-playbook' ),
                'handler' => [ $this, 'renderEmail' ],
                'save'    => [ $this, 'saveEmail' ],
            ],
            'first_job' => [
                'name'    => __( 'Erste Stelle', 'recruiting-playbook' ),
                'handler' => [ $this, 'renderFirstJob' ],
                'save'    => [ $this, 'saveFirstJob' ],
            ],
            'complete' => [
                'name'    => __( 'Fertig', 'recruiting-playbook' ),
                'handler' => [ $this, 'renderComplete' ],
                'save'    => null,
            ],
        ];
    }

    /**
     * Wizard initialisieren
     */
    public function init(): void {
        // Prüfen ob Wizard angezeigt werden soll.
        if ( ! $this->shouldShowWizard() ) {
            return;
        }

        // Admin-Seite registrieren (versteckt).
        add_action( 'admin_menu', [ $this, 'registerPage' ] );

        // AJAX-Handler.
        add_action( 'wp_ajax_rp_wizard_save_step', [ $this, 'ajaxSaveStep' ] );
        add_action( 'wp_ajax_rp_wizard_skip', [ $this, 'ajaxSkipWizard' ] );
        add_action( 'wp_ajax_rp_send_test_email', [ $this, 'ajaxSendTestEmail' ] );
    }

    /**
     * Prüfen ob Wizard angezeigt werden soll
     *
     * @return bool
     */
    private function shouldShowWizard(): bool {
        // Wizard bereits abgeschlossen.
        if ( get_option( 'rp_wizard_completed', false ) ) {
            return false;
        }

        // Nur für Admins.
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Admin-Seite registrieren
     */
    public function registerPage(): void {
        add_submenu_page(
            null, // Versteckt.
            __( 'Setup-Wizard', 'recruiting-playbook' ),
            __( 'Setup-Wizard', 'recruiting-playbook' ),
            'manage_options',
            'rp-setup-wizard',
            [ $this, 'render' ]
        );
    }

    /**
     * Wizard rendern
     */
    public function render(): void {
        // Aktuellen Schritt ermitteln.
        $this->current_step = isset( $_GET['step'] )
            ? sanitize_key( $_GET['step'] )
            : 'welcome';

        if ( ! isset( $this->steps[ $this->current_step ] ) ) {
            $this->current_step = 'welcome';
        }

        // Assets laden.
        $this->enqueueAssets();

        // Template laden.
        include RP_PLUGIN_DIR . 'src/Admin/SetupWizard/views/wizard.php';
    }

    /**
     * Assets laden
     */
    private function enqueueAssets(): void {
        wp_enqueue_style(
            'rp-wizard',
            RP_PLUGIN_URL . 'assets/dist/css/wizard.css',
            [],
            RP_VERSION
        );

        wp_enqueue_script(
            'rp-wizard',
            RP_PLUGIN_URL . 'assets/dist/js/wizard.js',
            [ 'jquery' ],
            RP_VERSION,
            true
        );

        wp_localize_script(
            'rp-wizard',
            'rpWizard',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'rp_wizard_nonce' ),
                'i18n'    => [
                    'saving'       => __( 'Speichern...', 'recruiting-playbook' ),
                    'saved'        => __( 'Gespeichert!', 'recruiting-playbook' ),
                    'error'        => __( 'Ein Fehler ist aufgetreten.', 'recruiting-playbook' ),
                    'sendingEmail' => __( 'E-Mail wird gesendet...', 'recruiting-playbook' ),
                    'emailSent'    => __( 'Test-E-Mail wurde gesendet!', 'recruiting-playbook' ),
                    'emailFailed'  => __( 'E-Mail konnte nicht gesendet werden.', 'recruiting-playbook' ),
                ],
            ]
        );
    }

    /**
     * Fortschrittsleiste rendern
     */
    public function renderProgress(): void {
        $step_keys  = array_keys( $this->steps );
        $current_index = array_search( $this->current_step, $step_keys, true );
        ?>
        <ol class="rp-wizard-progress">
            <?php foreach ( $this->steps as $key => $step ) : ?>
                <?php
                $index = array_search( $key, $step_keys, true );
                $class = '';
                if ( $index < $current_index ) {
                    $class = 'completed';
                } elseif ( $index === $current_index ) {
                    $class = 'active';
                }
                ?>
                <li class="<?php echo esc_attr( $class ); ?>">
                    <span class="step-number"><?php echo esc_html( $index + 1 ); ?></span>
                    <span class="step-name"><?php echo esc_html( $step['name'] ); ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php
    }

    /**
     * Schritt 1: Willkommen
     */
    public function renderWelcome(): void {
        ?>
        <div class="rp-wizard-step">
            <h2><?php esc_html_e( 'Willkommen bei Recruiting Playbook!', 'recruiting-playbook' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Dieser Assistent hilft Ihnen, das Plugin in wenigen Minuten einzurichten.', 'recruiting-playbook' ); ?>
            </p>

            <div class="rp-wizard-features">
                <div class="feature">
                    <span class="dashicons dashicons-businessman"></span>
                    <h4><?php esc_html_e( 'Stellenanzeigen verwalten', 'recruiting-playbook' ); ?></h4>
                    <p><?php esc_html_e( 'Erstellen und verwalten Sie Ihre Stellenanzeigen direkt in WordPress.', 'recruiting-playbook' ); ?></p>
                </div>
                <div class="feature">
                    <span class="dashicons dashicons-email-alt"></span>
                    <h4><?php esc_html_e( 'Bewerbungen empfangen', 'recruiting-playbook' ); ?></h4>
                    <p><?php esc_html_e( 'Bewerber können sich direkt auf Ihrer Website bewerben.', 'recruiting-playbook' ); ?></p>
                </div>
                <div class="feature">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <h4><?php esc_html_e( 'Bewerbungen verwalten', 'recruiting-playbook' ); ?></h4>
                    <p><?php esc_html_e( 'Behalten Sie den Überblick über alle Bewerbungen.', 'recruiting-playbook' ); ?></p>
                </div>
            </div>

            <p class="rp-wizard-actions">
                <a href="<?php echo esc_url( $this->getStepUrl( 'company' ) ); ?>" class="button button-primary button-hero">
                    <?php esc_html_e( 'Los geht\'s!', 'recruiting-playbook' ); ?>
                </a>
                <button type="button" class="button button-link rp-skip-wizard">
                    <?php esc_html_e( 'Später einrichten', 'recruiting-playbook' ); ?>
                </button>
            </p>
        </div>
        <?php
    }

    /**
     * Schritt 2: Firmendaten
     */
    public function renderCompany(): void {
        $settings = get_option( 'rp_settings', [] );
        ?>
        <div class="rp-wizard-step">
            <h2><?php esc_html_e( 'Firmendaten', 'recruiting-playbook' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Diese Informationen werden in E-Mails und im Google for Jobs Schema verwendet.', 'recruiting-playbook' ); ?>
            </p>

            <form id="rp-wizard-company-form" class="rp-wizard-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="company_name"><?php esc_html_e( 'Firmenname', 'recruiting-playbook' ); ?> *</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="company_name"
                                   name="company_name"
                                   value="<?php echo esc_attr( $settings['company_name'] ?? get_bloginfo( 'name' ) ); ?>"
                                   class="regular-text"
                                   required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company_logo"><?php esc_html_e( 'Firmenlogo URL', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <input type="url"
                                   id="company_logo"
                                   name="company_logo"
                                   value="<?php echo esc_url( $settings['company_logo'] ?? '' ); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php esc_html_e( 'URL zu Ihrem Firmenlogo (für Google for Jobs).', 'recruiting-playbook' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company_street"><?php esc_html_e( 'Straße', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="company_street"
                                   name="company_street"
                                   value="<?php echo esc_attr( $settings['company_street'] ?? '' ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company_zip"><?php esc_html_e( 'PLZ', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="company_zip"
                                   name="company_zip"
                                   value="<?php echo esc_attr( $settings['company_zip'] ?? '' ); ?>"
                                   class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company_city"><?php esc_html_e( 'Stadt', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="company_city"
                                   name="company_city"
                                   value="<?php echo esc_attr( $settings['company_city'] ?? '' ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="company_country"><?php esc_html_e( 'Land', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <select id="company_country" name="company_country">
                                <option value="DE" <?php selected( $settings['company_country'] ?? 'DE', 'DE' ); ?>>Deutschland</option>
                                <option value="AT" <?php selected( $settings['company_country'] ?? '', 'AT' ); ?>>Österreich</option>
                                <option value="CH" <?php selected( $settings['company_country'] ?? '', 'CH' ); ?>>Schweiz</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="privacy_policy_url"><?php esc_html_e( 'Datenschutz-URL', 'recruiting-playbook' ); ?> *</label>
                        </th>
                        <td>
                            <input type="url"
                                   id="privacy_policy_url"
                                   name="privacy_policy_url"
                                   value="<?php echo esc_url( $settings['privacy_policy_url'] ?? get_privacy_policy_url() ); ?>"
                                   class="regular-text"
                                   required>
                            <p class="description">
                                <?php esc_html_e( 'Link zur Datenschutzerklärung für das Bewerbungsformular.', 'recruiting-playbook' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <input type="hidden" name="step" value="company">
                <?php wp_nonce_field( 'rp_wizard_nonce', 'nonce' ); ?>

                <p class="rp-wizard-actions">
                    <a href="<?php echo esc_url( $this->getStepUrl( 'welcome' ) ); ?>" class="button">
                        <?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
                    </a>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Firmendaten speichern
     *
     * @param array $data Formulardaten.
     * @return bool|WP_Error
     */
    public function saveCompany( array $data ): bool|\WP_Error {
        $settings = get_option( 'rp_settings', [] );

        $settings['company_name']      = sanitize_text_field( $data['company_name'] ?? '' );
        $settings['company_logo']      = esc_url_raw( $data['company_logo'] ?? '' );
        $settings['company_street']    = sanitize_text_field( $data['company_street'] ?? '' );
        $settings['company_zip']       = sanitize_text_field( $data['company_zip'] ?? '' );
        $settings['company_city']      = sanitize_text_field( $data['company_city'] ?? '' );
        $settings['company_country']   = sanitize_text_field( $data['company_country'] ?? 'DE' );
        $settings['privacy_policy_url'] = esc_url_raw( $data['privacy_policy_url'] ?? '' );

        update_option( 'rp_settings', $settings );

        return true;
    }

    /**
     * Schritt 3: E-Mail
     */
    public function renderEmail(): void {
        $settings    = get_option( 'rp_settings', [] );
        $smtp_status = \RecruitingPlaybook\Services\EmailService::checkSmtpConfig();
        ?>
        <div class="rp-wizard-step">
            <h2><?php esc_html_e( 'E-Mail-Einstellungen', 'recruiting-playbook' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Konfigurieren Sie, wie E-Mail-Benachrichtigungen versendet werden.', 'recruiting-playbook' ); ?>
            </p>

            <!-- SMTP-Status -->
            <div class="rp-smtp-status <?php echo $smtp_status['configured'] ? 'configured' : 'not-configured'; ?>">
                <?php if ( $smtp_status['configured'] ) : ?>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong><?php esc_html_e( 'SMTP konfiguriert', 'recruiting-playbook' ); ?></strong>
                    <p><?php echo esc_html( $smtp_status['message'] ); ?></p>
                <?php else : ?>
                    <span class="dashicons dashicons-warning"></span>
                    <strong><?php esc_html_e( 'Kein SMTP konfiguriert', 'recruiting-playbook' ); ?></strong>
                    <p><?php echo esc_html( $smtp_status['message'] ); ?></p>
                    <p>
                        <a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=smtp&tab=search&type=term' ) ); ?>"
                           target="_blank"
                           class="button button-secondary">
                            <?php esc_html_e( 'SMTP-Plugin installieren', 'recruiting-playbook' ); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <form id="rp-wizard-email-form" class="rp-wizard-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="notification_email"><?php esc_html_e( 'Benachrichtigungs-E-Mail', 'recruiting-playbook' ); ?> *</label>
                        </th>
                        <td>
                            <input type="email"
                                   id="notification_email"
                                   name="notification_email"
                                   value="<?php echo esc_attr( $settings['notification_email'] ?? get_option( 'admin_email' ) ); ?>"
                                   class="regular-text"
                                   required>
                            <p class="description">
                                <?php esc_html_e( 'An diese Adresse werden Benachrichtigungen über neue Bewerbungen gesendet.', 'recruiting-playbook' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sender_name"><?php esc_html_e( 'Absendername', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="sender_name"
                                   name="sender_name"
                                   value="<?php echo esc_attr( $settings['sender_name'] ?? $settings['company_name'] ?? '' ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                </table>

                <!-- Test-E-Mail -->
                <div class="rp-test-email-section">
                    <h3><?php esc_html_e( 'Test-E-Mail senden', 'recruiting-playbook' ); ?></h3>
                    <p>
                        <input type="email"
                               id="test_email_address"
                               placeholder="<?php esc_attr_e( 'E-Mail-Adresse', 'recruiting-playbook' ); ?>"
                               value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
                               class="regular-text">
                        <button type="button" id="rp-send-test-email" class="button">
                            <?php esc_html_e( 'Test senden', 'recruiting-playbook' ); ?>
                        </button>
                    </p>
                    <div id="rp-test-email-result"></div>
                </div>

                <input type="hidden" name="step" value="email">
                <?php wp_nonce_field( 'rp_wizard_nonce', 'nonce' ); ?>

                <p class="rp-wizard-actions">
                    <a href="<?php echo esc_url( $this->getStepUrl( 'company' ) ); ?>" class="button">
                        <?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
                    </a>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * E-Mail-Einstellungen speichern
     *
     * @param array $data Formulardaten.
     * @return bool|WP_Error
     */
    public function saveEmail( array $data ): bool|\WP_Error {
        $settings = get_option( 'rp_settings', [] );

        $settings['notification_email'] = sanitize_email( $data['notification_email'] ?? '' );
        $settings['sender_name']        = sanitize_text_field( $data['sender_name'] ?? '' );

        update_option( 'rp_settings', $settings );

        return true;
    }

    /**
     * Schritt 4: Erste Stelle
     */
    public function renderFirstJob(): void {
        ?>
        <div class="rp-wizard-step">
            <h2><?php esc_html_e( 'Erste Stelle erstellen', 'recruiting-playbook' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Erstellen Sie jetzt Ihre erste Stellenanzeige oder überspringen Sie diesen Schritt.', 'recruiting-playbook' ); ?>
            </p>

            <form id="rp-wizard-job-form" class="rp-wizard-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="job_title"><?php esc_html_e( 'Stellentitel', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="job_title"
                                   name="job_title"
                                   placeholder="<?php esc_attr_e( 'z.B. Pflegefachkraft (m/w/d)', 'recruiting-playbook' ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="job_location"><?php esc_html_e( 'Standort', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="job_location"
                                   name="job_location"
                                   placeholder="<?php esc_attr_e( 'z.B. Berlin', 'recruiting-playbook' ); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="job_type"><?php esc_html_e( 'Beschäftigungsart', 'recruiting-playbook' ); ?></label>
                        </th>
                        <td>
                            <select id="job_type" name="job_type">
                                <option value="FULL_TIME"><?php esc_html_e( 'Vollzeit', 'recruiting-playbook' ); ?></option>
                                <option value="PART_TIME"><?php esc_html_e( 'Teilzeit', 'recruiting-playbook' ); ?></option>
                                <option value="TEMPORARY"><?php esc_html_e( 'Befristet', 'recruiting-playbook' ); ?></option>
                                <option value="INTERN"><?php esc_html_e( 'Praktikum', 'recruiting-playbook' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <input type="hidden" name="step" value="first_job">
                <input type="hidden" name="skip_job" id="skip_job" value="0">
                <?php wp_nonce_field( 'rp_wizard_nonce', 'nonce' ); ?>

                <p class="rp-wizard-actions">
                    <a href="<?php echo esc_url( $this->getStepUrl( 'email' ) ); ?>" class="button">
                        <?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
                    </a>
                    <button type="button" class="button" id="rp-skip-job">
                        <?php esc_html_e( 'Überspringen', 'recruiting-playbook' ); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Stelle erstellen & Weiter', 'recruiting-playbook' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Erste Stelle speichern
     *
     * @param array $data Formulardaten.
     * @return bool|WP_Error
     */
    public function saveFirstJob( array $data ): bool|\WP_Error {
        // Überspringen wenn gewünscht.
        if ( ! empty( $data['skip_job'] ) ) {
            return true;
        }

        // Keine Daten eingegeben.
        if ( empty( $data['job_title'] ) ) {
            return true;
        }

        // Stelle erstellen.
        $post_id = wp_insert_post( [
            'post_type'    => 'job_listing',
            'post_title'   => sanitize_text_field( $data['job_title'] ),
            'post_status'  => 'draft',
            'post_content' => '',
        ] );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Standort als Taxonomie.
        if ( ! empty( $data['job_location'] ) ) {
            wp_set_object_terms( $post_id, sanitize_text_field( $data['job_location'] ), 'job_location' );
        }

        // Beschäftigungsart als Taxonomie.
        if ( ! empty( $data['job_type'] ) ) {
            $type_mapping = [
                'FULL_TIME'  => 'Vollzeit',
                'PART_TIME'  => 'Teilzeit',
                'TEMPORARY'  => 'Befristet',
                'INTERN'     => 'Praktikum',
            ];
            $type_name = $type_mapping[ $data['job_type'] ] ?? 'Vollzeit';
            wp_set_object_terms( $post_id, $type_name, 'employment_type' );
        }

        // Speichern für Redirect nach Wizard.
        set_transient( 'rp_wizard_created_job', $post_id, 60 );

        return true;
    }

    /**
     * Schritt 5: Fertig
     */
    public function renderComplete(): void {
        // Wizard als abgeschlossen markieren.
        update_option( 'rp_wizard_completed', true );

        $created_job_id = get_transient( 'rp_wizard_created_job' );
        delete_transient( 'rp_wizard_created_job' );
        ?>
        <div class="rp-wizard-step rp-wizard-complete">
            <span class="dashicons dashicons-yes-alt"></span>
            <h2><?php esc_html_e( 'Einrichtung abgeschlossen!', 'recruiting-playbook' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Recruiting Playbook ist jetzt einsatzbereit.', 'recruiting-playbook' ); ?>
            </p>

            <div class="rp-wizard-next-steps">
                <h3><?php esc_html_e( 'Nächste Schritte:', 'recruiting-playbook' ); ?></h3>
                <ul>
                    <?php if ( $created_job_id ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_edit_post_link( $created_job_id ) ); ?>">
                                <?php esc_html_e( 'Ihre erste Stelle vervollständigen', 'recruiting-playbook' ); ?>
                            </a>
                        </li>
                    <?php else : ?>
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=job_listing' ) ); ?>">
                                <?php esc_html_e( 'Erste Stelle erstellen', 'recruiting-playbook' ); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings' ) ); ?>">
                            <?php esc_html_e( 'Weitere Einstellungen konfigurieren', 'recruiting-playbook' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) ); ?>">
                            <?php esc_html_e( 'Zum Dashboard', 'recruiting-playbook' ); ?>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="rp-wizard-shortcodes">
                <h3><?php esc_html_e( 'Shortcodes für Ihre Seiten:', 'recruiting-playbook' ); ?></h3>
                <table class="widefat striped">
                    <tr>
                        <td><code>[rp_jobs]</code></td>
                        <td><?php esc_html_e( 'Zeigt alle Stellenanzeigen an', 'recruiting-playbook' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>[rp_application_form]</code></td>
                        <td><?php esc_html_e( 'Bewerbungsformular (auf Stellenseite)', 'recruiting-playbook' ); ?></td>
                    </tr>
                </table>
            </div>

            <p class="rp-wizard-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) ); ?>" class="button button-primary button-hero">
                    <?php esc_html_e( 'Zum Dashboard', 'recruiting-playbook' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * URL für einen Schritt generieren
     *
     * @param string $step Schritt-Key.
     * @return string
     */
    private function getStepUrl( string $step ): string {
        return add_query_arg(
            [
                'page' => 'rp-setup-wizard',
                'step' => $step,
            ],
            admin_url( 'admin.php' )
        );
    }

    /**
     * AJAX: Schritt speichern
     */
    public function ajaxSaveStep(): void {
        check_ajax_referer( 'rp_wizard_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Keine Berechtigung.', 'recruiting-playbook' ) ] );
        }

        $step = sanitize_key( $_POST['step'] ?? '' );

        if ( ! isset( $this->steps[ $step ] ) || ! $this->steps[ $step ]['save'] ) {
            wp_send_json_error( [ 'message' => __( 'Ungültiger Schritt.', 'recruiting-playbook' ) ] );
        }

        $result = call_user_func( $this->steps[ $step ]['save'], $_POST );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        // Nächsten Schritt ermitteln.
        $step_keys = array_keys( $this->steps );
        $current_index = array_search( $step, $step_keys, true );
        $next_step = $step_keys[ $current_index + 1 ] ?? 'complete';

        wp_send_json_success( [
            'next_url' => $this->getStepUrl( $next_step ),
        ] );
    }

    /**
     * AJAX: Wizard überspringen
     */
    public function ajaxSkipWizard(): void {
        check_ajax_referer( 'rp_wizard_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Keine Berechtigung.', 'recruiting-playbook' ) ] );
        }

        update_option( 'rp_wizard_completed', true );

        wp_send_json_success( [
            'redirect_url' => admin_url( 'admin.php?page=recruiting-playbook' ),
        ] );
    }

    /**
     * AJAX: Test-E-Mail senden
     */
    public function ajaxSendTestEmail(): void {
        check_ajax_referer( 'rp_wizard_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Keine Berechtigung.', 'recruiting-playbook' ) ] );
        }

        $email = sanitize_email( $_POST['email'] ?? '' );

        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Ungültige E-Mail-Adresse.', 'recruiting-playbook' ) ] );
        }

        $subject = __( 'Recruiting Playbook - Test-E-Mail', 'recruiting-playbook' );
        $message = sprintf(
            __( 'Dies ist eine Test-E-Mail von Recruiting Playbook.\n\nWenn Sie diese E-Mail erhalten, funktioniert der E-Mail-Versand korrekt.\n\nGesendet am: %s', 'recruiting-playbook' ),
            current_time( 'mysql' )
        );

        $sent = wp_mail( $email, $subject, $message );

        if ( $sent ) {
            wp_send_json_success( [ 'message' => __( 'Test-E-Mail wurde gesendet!', 'recruiting-playbook' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'E-Mail konnte nicht gesendet werden. Bitte prüfen Sie Ihre SMTP-Konfiguration.', 'recruiting-playbook' ) ] );
        }
    }
}
```

### 1.4 Wizard-Template (wizard.php)

```php
<?php
/**
 * Setup-Wizard Template
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e( 'Recruiting Playbook Setup', 'recruiting-playbook' ); ?></title>
    <?php wp_print_styles( 'rp-wizard' ); ?>
</head>
<body class="rp-wizard-body">
    <div class="rp-wizard-container">
        <div class="rp-wizard-header">
            <h1>
                <span class="dashicons dashicons-groups"></span>
                Recruiting Playbook
            </h1>
        </div>

        <?php $this->renderProgress(); ?>

        <div class="rp-wizard-content">
            <?php call_user_func( $this->steps[ $this->current_step ]['handler'] ); ?>
        </div>
    </div>

    <?php wp_print_scripts( 'rp-wizard' ); ?>
</body>
</html>
```

### 1.5 Wizard-CSS (wizard.css)

```css
/* Setup-Wizard Styles */
.rp-wizard-body {
    background: #f0f0f1;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.rp-wizard-container {
    max-width: 800px;
    margin: 40px auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.rp-wizard-header {
    background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
    color: #fff;
    padding: 30px;
    text-align: center;
}

.rp-wizard-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
}

.rp-wizard-header .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    vertical-align: middle;
    margin-right: 10px;
}

/* Progress Bar */
.rp-wizard-progress {
    display: flex;
    justify-content: space-between;
    padding: 0;
    margin: 0;
    list-style: none;
    background: #f6f7f7;
    border-bottom: 1px solid #e5e7eb;
}

.rp-wizard-progress li {
    flex: 1;
    text-align: center;
    padding: 15px 10px;
    position: relative;
    color: #787c82;
}

.rp-wizard-progress li.active {
    color: #2271b1;
    background: #fff;
    border-bottom: 3px solid #2271b1;
}

.rp-wizard-progress li.completed {
    color: #00a32a;
}

.rp-wizard-progress .step-number {
    display: inline-block;
    width: 24px;
    height: 24px;
    line-height: 24px;
    border-radius: 50%;
    background: #dcdcde;
    color: #fff;
    font-size: 12px;
    font-weight: bold;
    margin-right: 8px;
}

.rp-wizard-progress li.active .step-number {
    background: #2271b1;
}

.rp-wizard-progress li.completed .step-number {
    background: #00a32a;
}

.rp-wizard-progress .step-name {
    font-size: 13px;
}

/* Content */
.rp-wizard-content {
    padding: 40px;
}

.rp-wizard-step h2 {
    margin-top: 0;
    color: #1d2327;
}

.rp-wizard-step .description {
    color: #646970;
    font-size: 14px;
    margin-bottom: 30px;
}

/* Features Grid */
.rp-wizard-features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin: 30px 0;
}

.rp-wizard-features .feature {
    text-align: center;
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.rp-wizard-features .feature .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #2271b1;
    margin-bottom: 10px;
}

.rp-wizard-features .feature h4 {
    margin: 10px 0 5px;
}

.rp-wizard-features .feature p {
    margin: 0;
    color: #646970;
    font-size: 13px;
}

/* Form */
.rp-wizard-form .form-table th {
    width: 180px;
    padding: 15px 10px 15px 0;
}

.rp-wizard-form .form-table td {
    padding: 15px 10px;
}

/* SMTP Status */
.rp-smtp-status {
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.rp-smtp-status.configured {
    background: #d1fae5;
    border: 1px solid #10b981;
}

.rp-smtp-status.not-configured {
    background: #fef3c7;
    border: 1px solid #f59e0b;
}

.rp-smtp-status .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    vertical-align: middle;
    margin-right: 10px;
}

.rp-smtp-status.configured .dashicons {
    color: #10b981;
}

.rp-smtp-status.not-configured .dashicons {
    color: #f59e0b;
}

/* Test Email */
.rp-test-email-section {
    background: #f6f7f7;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.rp-test-email-section h3 {
    margin-top: 0;
}

/* Actions */
.rp-wizard-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.rp-wizard-actions .button-hero {
    padding: 12px 36px;
    font-size: 16px;
}

/* Complete Step */
.rp-wizard-complete {
    text-align: center;
}

.rp-wizard-complete > .dashicons {
    font-size: 80px;
    width: 80px;
    height: 80px;
    color: #00a32a;
    margin-bottom: 20px;
}

.rp-wizard-next-steps,
.rp-wizard-shortcodes {
    text-align: left;
    max-width: 500px;
    margin: 30px auto;
    background: #f6f7f7;
    padding: 20px;
    border-radius: 8px;
}

.rp-wizard-next-steps ul {
    margin: 0;
    padding-left: 20px;
}

.rp-wizard-next-steps li {
    margin-bottom: 10px;
}

/* Responsive */
@media screen and (max-width: 782px) {
    .rp-wizard-features {
        grid-template-columns: 1fr;
    }

    .rp-wizard-progress .step-name {
        display: none;
    }

    .rp-wizard-form .form-table th {
        display: block;
        width: 100%;
        padding-bottom: 5px;
    }

    .rp-wizard-form .form-table td {
        display: block;
        padding-top: 0;
    }
}
```

---

## 2. Google for Jobs Schema

### 2.1 JobSchema.php (Erweiterung)

Die bestehende `JobSchema.php` muss erweitert werden, um alle Google-Anforderungen zu erfüllen.

```php
<?php
/**
 * Google for Jobs Schema (JSON-LD)
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend;

/**
 * Job Schema Generator
 */
class JobSchema {

    /**
     * Initialisieren
     */
    public function init(): void {
        add_action( 'wp_head', [ $this, 'outputSchema' ], 1 );
    }

    /**
     * Schema ausgeben
     */
    public function outputSchema(): void {
        if ( ! is_singular( 'job_listing' ) ) {
            return;
        }

        $post = get_post();
        if ( ! $post ) {
            return;
        }

        $schema = $this->generateSchema( $post );

        if ( empty( $schema ) ) {
            return;
        }

        printf(
            '<script type="application/ld+json">%s</script>' . "\n",
            wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
        );
    }

    /**
     * Schema für einen Job generieren
     *
     * @param \WP_Post $post Job-Post.
     * @return array
     */
    public function generateSchema( \WP_Post $post ): array {
        $settings = get_option( 'rp_settings', [] );

        // Pflichtfelder prüfen.
        if ( empty( $post->post_title ) || empty( $post->post_content ) ) {
            return [];
        }

        // Basis-Schema.
        $schema = [
            '@context'      => 'https://schema.org/',
            '@type'         => 'JobPosting',
            'title'         => $post->post_title,
            'description'   => $this->formatDescription( $post->post_content ),
            'datePosted'    => get_the_date( 'c', $post ),
            'hiringOrganization' => $this->getHiringOrganization( $settings ),
            'jobLocation'   => $this->getJobLocation( $post, $settings ),
            'employmentType' => $this->getEmploymentType( $post ),
        ];

        // Bewerbungsfrist (optional).
        $deadline = get_post_meta( $post->ID, '_rp_application_deadline', true );
        if ( $deadline ) {
            $schema['validThrough'] = date( 'c', strtotime( $deadline ) );
        }

        // Gehalt (optional).
        $salary = $this->getSalary( $post );
        if ( $salary ) {
            $schema['baseSalary'] = $salary;
        }

        // Remote-Option (optional).
        $remote = get_post_meta( $post->ID, '_rp_remote_option', true );
        if ( 'full' === $remote ) {
            $schema['jobLocationType'] = 'TELECOMMUTE';
        }

        // Direktbewerbungs-URL.
        $schema['directApply'] = true;

        // Identifier (optional aber empfohlen).
        $schema['identifier'] = [
            '@type' => 'PropertyValue',
            'name'  => $settings['company_name'] ?? get_bloginfo( 'name' ),
            'value' => 'job-' . $post->ID,
        ];

        return $schema;
    }

    /**
     * Beschreibung formatieren
     *
     * @param string $content HTML-Content.
     * @return string
     */
    private function formatDescription( string $content ): string {
        // HTML in strukturierten Text umwandeln.
        $content = wp_strip_all_tags( $content );
        $content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
        $content = preg_replace( '/\s+/', ' ', $content );
        return trim( $content );
    }

    /**
     * Arbeitgeber-Informationen
     *
     * @param array $settings Plugin-Einstellungen.
     * @return array
     */
    private function getHiringOrganization( array $settings ): array {
        $org = [
            '@type' => 'Organization',
            'name'  => $settings['company_name'] ?? get_bloginfo( 'name' ),
            'sameAs' => home_url(),
        ];

        // Logo (optional aber empfohlen).
        if ( ! empty( $settings['company_logo'] ) ) {
            $org['logo'] = $settings['company_logo'];
        }

        return $org;
    }

    /**
     * Job-Standort
     *
     * @param \WP_Post $post     Job-Post.
     * @param array    $settings Plugin-Einstellungen.
     * @return array
     */
    private function getJobLocation( \WP_Post $post, array $settings ): array {
        // Standort aus Taxonomie.
        $location_terms = wp_get_post_terms( $post->ID, 'job_location', [ 'fields' => 'names' ] );
        $location_name  = ! empty( $location_terms ) ? $location_terms[0] : ( $settings['company_city'] ?? '' );

        // Remote-Arbeitsplatz.
        $remote = get_post_meta( $post->ID, '_rp_remote_option', true );
        if ( 'full' === $remote ) {
            return [
                '@type' => 'VirtualLocation',
            ];
        }

        // Physischer Standort.
        $location = [
            '@type'   => 'Place',
            'address' => [
                '@type'           => 'PostalAddress',
                'addressLocality' => $location_name,
                'addressCountry'  => $settings['company_country'] ?? 'DE',
            ],
        ];

        // Straße und PLZ hinzufügen wenn vorhanden.
        if ( ! empty( $settings['company_street'] ) ) {
            $location['address']['streetAddress'] = $settings['company_street'];
        }
        if ( ! empty( $settings['company_zip'] ) ) {
            $location['address']['postalCode'] = $settings['company_zip'];
        }

        return $location;
    }

    /**
     * Beschäftigungsart
     *
     * @param \WP_Post $post Job-Post.
     * @return array|string
     */
    private function getEmploymentType( \WP_Post $post ): array|string {
        $terms = wp_get_post_terms( $post->ID, 'employment_type', [ 'fields' => 'slugs' ] );

        if ( empty( $terms ) ) {
            return 'FULL_TIME';
        }

        // Mapping zu Google-Werten.
        $mapping = [
            'vollzeit'    => 'FULL_TIME',
            'teilzeit'    => 'PART_TIME',
            'minijob'     => 'PART_TIME',
            'befristet'   => 'TEMPORARY',
            'praktikum'   => 'INTERN',
            'ausbildung'  => 'INTERN',
            'werkstudent' => 'PART_TIME',
            'freelance'   => 'CONTRACTOR',
            'zeitarbeit'  => 'TEMPORARY',
        ];

        $types = [];
        foreach ( $terms as $term ) {
            $slug = sanitize_title( $term );
            if ( isset( $mapping[ $slug ] ) ) {
                $types[] = $mapping[ $slug ];
            }
        }

        return ! empty( $types ) ? array_unique( $types ) : 'FULL_TIME';
    }

    /**
     * Gehaltsinformationen
     *
     * @param \WP_Post $post Job-Post.
     * @return array|null
     */
    private function getSalary( \WP_Post $post ): ?array {
        // Gehalt verstecken?
        if ( get_post_meta( $post->ID, '_rp_hide_salary', true ) ) {
            return null;
        }

        $min      = get_post_meta( $post->ID, '_rp_salary_min', true );
        $max      = get_post_meta( $post->ID, '_rp_salary_max', true );
        $currency = get_post_meta( $post->ID, '_rp_salary_currency', true ) ?: 'EUR';
        $period   = get_post_meta( $post->ID, '_rp_salary_period', true ) ?: 'YEAR';

        if ( empty( $min ) && empty( $max ) ) {
            return null;
        }

        // Unit-Code für Zeitraum.
        $unit_mapping = [
            'HOUR'  => 'HOUR',
            'DAY'   => 'DAY',
            'WEEK'  => 'WEEK',
            'MONTH' => 'MONTH',
            'YEAR'  => 'YEAR',
        ];

        $salary = [
            '@type'    => 'MonetaryAmount',
            'currency' => strtoupper( $currency ),
            'value'    => [
                '@type'    => 'QuantitativeValue',
                'unitText' => $unit_mapping[ $period ] ?? 'YEAR',
            ],
        ];

        if ( $min && $max && $min !== $max ) {
            $salary['value']['minValue'] = (float) $min;
            $salary['value']['maxValue'] = (float) $max;
        } elseif ( $min ) {
            $salary['value']['value'] = (float) $min;
        } elseif ( $max ) {
            $salary['value']['value'] = (float) $max;
        }

        return $salary;
    }

    /**
     * Schema validieren (für Debug/Admin)
     *
     * @param \WP_Post $post Job-Post.
     * @return array Validierungsergebnis.
     */
    public function validateSchema( \WP_Post $post ): array {
        $errors   = [];
        $warnings = [];

        // Pflichtfelder.
        if ( empty( $post->post_title ) ) {
            $errors[] = __( 'Stellentitel fehlt', 'recruiting-playbook' );
        }

        if ( empty( $post->post_content ) ) {
            $errors[] = __( 'Stellenbeschreibung fehlt', 'recruiting-playbook' );
        }

        $settings = get_option( 'rp_settings', [] );

        if ( empty( $settings['company_name'] ) ) {
            $errors[] = __( 'Firmenname nicht konfiguriert', 'recruiting-playbook' );
        }

        // Empfehlungen.
        if ( empty( $settings['company_logo'] ) ) {
            $warnings[] = __( 'Firmenlogo fehlt (empfohlen für bessere Sichtbarkeit)', 'recruiting-playbook' );
        }

        $deadline = get_post_meta( $post->ID, '_rp_application_deadline', true );
        if ( empty( $deadline ) ) {
            $warnings[] = __( 'Bewerbungsfrist nicht angegeben (empfohlen)', 'recruiting-playbook' );
        }

        $location_terms = wp_get_post_terms( $post->ID, 'job_location', [ 'fields' => 'names' ] );
        if ( empty( $location_terms ) ) {
            $warnings[] = __( 'Standort nicht angegeben (empfohlen)', 'recruiting-playbook' );
        }

        return [
            'valid'    => empty( $errors ),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }
}
```

### 2.2 Schema-Validierung in Meta-Box

```php
/**
 * Google for Jobs Status in Meta-Box anzeigen
 */
public function renderGoogleJobsStatus( \WP_Post $post ): void {
    $schema    = new \RecruitingPlaybook\Frontend\JobSchema();
    $validation = $schema->validateSchema( $post );
    ?>
    <div class="rp-google-jobs-status">
        <h4>
            <span class="dashicons dashicons-google"></span>
            <?php esc_html_e( 'Google for Jobs', 'recruiting-playbook' ); ?>
        </h4>

        <?php if ( $validation['valid'] ) : ?>
            <p class="status-ok">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e( 'Schema ist gültig', 'recruiting-playbook' ); ?>
            </p>
        <?php else : ?>
            <p class="status-error">
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e( 'Schema-Probleme gefunden:', 'recruiting-playbook' ); ?>
            </p>
            <ul class="errors">
                <?php foreach ( $validation['errors'] as $error ) : ?>
                    <li><?php echo esc_html( $error ); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ( ! empty( $validation['warnings'] ) ) : ?>
            <p class="status-warning">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e( 'Empfehlungen:', 'recruiting-playbook' ); ?>
            </p>
            <ul class="warnings">
                <?php foreach ( $validation['warnings'] as $warning ) : ?>
                    <li><?php echo esc_html( $warning ); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p>
            <a href="https://search.google.com/test/rich-results?url=<?php echo urlencode( get_permalink( $post ) ); ?>"
               target="_blank"
               class="button button-small">
                <?php esc_html_e( 'Bei Google testen', 'recruiting-playbook' ); ?>
            </a>
        </p>
    </div>
    <?php
}
```

---

## 3. Shortcode-Erweiterungen

### 3.1 Job-Suche Shortcode

```php
/**
 * [rp_job_search] - Suchformular für Jobs
 *
 * @param array $atts Shortcode-Attribute.
 * @return string
 */
public function jobSearchShortcode( array $atts ): string {
    $atts = shortcode_atts(
        [
            'show_category' => 'true',
            'show_location' => 'true',
            'show_type'     => 'true',
            'button_text'   => __( 'Stellen suchen', 'recruiting-playbook' ),
            'action'        => '', // URL für Suchergebnisse.
        ],
        $atts,
        'rp_job_search'
    );

    $action = ! empty( $atts['action'] )
        ? esc_url( $atts['action'] )
        : get_post_type_archive_link( 'job_listing' );

    ob_start();
    ?>
    <form class="rp-job-search" method="get" action="<?php echo esc_url( $action ); ?>">
        <div class="rp-search-fields">
            <!-- Keyword-Suche -->
            <div class="rp-search-field rp-search-keyword">
                <label for="rp-search-q" class="screen-reader-text">
                    <?php esc_html_e( 'Suchbegriff', 'recruiting-playbook' ); ?>
                </label>
                <input type="text"
                       id="rp-search-q"
                       name="s"
                       placeholder="<?php esc_attr_e( 'Suchbegriff...', 'recruiting-playbook' ); ?>"
                       value="<?php echo esc_attr( get_query_var( 's' ) ); ?>">
            </div>

            <?php if ( 'true' === $atts['show_category'] ) : ?>
                <!-- Kategorie -->
                <div class="rp-search-field rp-search-category">
                    <label for="rp-search-cat" class="screen-reader-text">
                        <?php esc_html_e( 'Berufsfeld', 'recruiting-playbook' ); ?>
                    </label>
                    <?php
                    wp_dropdown_categories( [
                        'taxonomy'          => 'job_category',
                        'name'              => 'job_category',
                        'id'                => 'rp-search-cat',
                        'show_option_all'   => __( 'Alle Berufsfelder', 'recruiting-playbook' ),
                        'hide_empty'        => true,
                        'hierarchical'      => true,
                        'selected'          => get_query_var( 'job_category' ),
                        'value_field'       => 'slug',
                    ] );
                    ?>
                </div>
            <?php endif; ?>

            <?php if ( 'true' === $atts['show_location'] ) : ?>
                <!-- Standort -->
                <div class="rp-search-field rp-search-location">
                    <label for="rp-search-loc" class="screen-reader-text">
                        <?php esc_html_e( 'Standort', 'recruiting-playbook' ); ?>
                    </label>
                    <?php
                    wp_dropdown_categories( [
                        'taxonomy'          => 'job_location',
                        'name'              => 'job_location',
                        'id'                => 'rp-search-loc',
                        'show_option_all'   => __( 'Alle Standorte', 'recruiting-playbook' ),
                        'hide_empty'        => true,
                        'selected'          => get_query_var( 'job_location' ),
                        'value_field'       => 'slug',
                    ] );
                    ?>
                </div>
            <?php endif; ?>

            <?php if ( 'true' === $atts['show_type'] ) : ?>
                <!-- Beschäftigungsart -->
                <div class="rp-search-field rp-search-type">
                    <label for="rp-search-type" class="screen-reader-text">
                        <?php esc_html_e( 'Beschäftigungsart', 'recruiting-playbook' ); ?>
                    </label>
                    <?php
                    wp_dropdown_categories( [
                        'taxonomy'          => 'employment_type',
                        'name'              => 'employment_type',
                        'id'                => 'rp-search-type',
                        'show_option_all'   => __( 'Alle Beschäftigungsarten', 'recruiting-playbook' ),
                        'hide_empty'        => true,
                        'selected'          => get_query_var( 'employment_type' ),
                        'value_field'       => 'slug',
                    ] );
                    ?>
                </div>
            <?php endif; ?>

            <!-- Submit -->
            <div class="rp-search-field rp-search-submit">
                <input type="hidden" name="post_type" value="job_listing">
                <button type="submit" class="rp-btn rp-btn-primary">
                    <?php echo esc_html( $atts['button_text'] ); ?>
                </button>
            </div>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
```

### 3.2 Shortcode-Dokumentation

**Verfügbare Shortcodes:**

| Shortcode | Beschreibung | Parameter |
|-----------|--------------|-----------|
| `[rp_jobs]` | Zeigt Stellenliste | `limit`, `category`, `location`, `type`, `columns`, `style` |
| `[rp_job_search]` | Suchformular | `show_category`, `show_location`, `show_type`, `button_text`, `action` |
| `[rp_application_form]` | Bewerbungsformular | `job_id` (optional, sonst aktueller Post) |

**Beispiele:**

```html
<!-- Alle Jobs, 2 Spalten -->
[rp_jobs columns="2"]

<!-- Jobs aus Kategorie "Pflege", max 5 -->
[rp_jobs category="pflege" limit="5"]

<!-- Suchformular ohne Beschäftigungsart -->
[rp_job_search show_type="false"]

<!-- Bewerbungsformular für bestimmte Stelle -->
[rp_application_form job_id="123"]
```

---

## 4. Testing

### 4.1 PHPUnit-Tests

**Dateistruktur:**

```
plugin/tests/
├── bootstrap.php
├── phpunit.xml
└── Unit/
    ├── Services/
    │   ├── ApplicationServiceTest.php
    │   ├── EmailServiceTest.php
    │   └── DocumentServiceTest.php
    ├── Api/
    │   └── ApplicationControllerTest.php
    └── Frontend/
        └── JobSchemaTest.php
```

**ApplicationServiceTest.php:**

```php
<?php
/**
 * Tests für ApplicationService
 */

namespace RecruitingPlaybook\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use RecruitingPlaybook\Services\ApplicationService;

class ApplicationServiceTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testCreateApplicationReturnsIdOnSuccess(): void {
        global $wpdb;

        // Mock wpdb.
        $wpdb = \Mockery::mock( 'wpdb' );
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 42;

        $wpdb->shouldReceive( 'insert' )
             ->twice()
             ->andReturn( 1 );

        $wpdb->shouldReceive( 'get_var' )
             ->andReturn( null );

        // Mock WordPress functions.
        Functions\when( 'current_time' )->justReturn( '2025-01-01 12:00:00' );
        Functions\when( 'sanitize_email' )->returnArg();
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'wp_kses_post' )->returnArg();

        $service = new ApplicationService();

        $result = $service->create( [
            'job_id'       => 1,
            'first_name'   => 'Max',
            'last_name'    => 'Mustermann',
            'email'        => 'max@example.com',
            'phone'        => '0123456789',
            'cover_letter' => 'Test',
            'ip_address'   => '127.0.0.1',
            'user_agent'   => 'Test Agent',
            'files'        => [],
        ] );

        $this->assertEquals( 42, $result );
    }

    public function testCreateApplicationValidatesRequiredFields(): void {
        $service = new ApplicationService();

        $result = $service->create( [
            'job_id'     => 1,
            'first_name' => '',
            'last_name'  => 'Mustermann',
            'email'      => 'invalid',
        ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }
}
```

### 4.2 Manuelle Test-Checkliste

**Bewerbungs-Flow:**
- [ ] Bewerbungsformular wird angezeigt
- [ ] Validierung funktioniert (leere Pflichtfelder, ungültige E-Mail)
- [ ] Datei-Upload funktioniert (PDF, DOC, DOCX)
- [ ] Datei-Upload verweigert ungültige Typen
- [ ] Honeypot blockiert Spam
- [ ] Rate Limiting funktioniert
- [ ] Bestätigungs-E-Mail an Bewerber
- [ ] Benachrichtigungs-E-Mail an HR

**Admin-Bereich:**
- [ ] Bewerbungsliste zeigt alle Bewerbungen
- [ ] Filter funktionieren (Status, Stelle, Datum)
- [ ] Suche funktioniert
- [ ] Bulk-Aktionen funktionieren
- [ ] Status-Änderung funktioniert
- [ ] Dokument-Download funktioniert
- [ ] DSGVO-Export funktioniert
- [ ] Löschung funktioniert

**Frontend:**
- [ ] Job-Archiv zeigt alle Stellen
- [ ] Einzelne Stelle wird korrekt angezeigt
- [ ] Shortcodes funktionieren
- [ ] Responsive Design (Mobile, Tablet, Desktop)

**Cross-Browser:**
- [ ] Chrome (aktuell)
- [ ] Firefox (aktuell)
- [ ] Safari (aktuell)
- [ ] Edge (aktuell)

**Mobile:**
- [ ] iOS Safari
- [ ] Android Chrome

---

## 5. Internationalisierung

### 5.1 POT-Datei generieren

```bash
cd plugin
wp i18n make-pot . languages/recruiting-playbook.pot --domain=recruiting-playbook
```

### 5.2 Deutsche Übersetzung

**Datei:** `languages/recruiting-playbook-de_DE.po`

```po
# German translation for Recruiting Playbook
msgid ""
msgstr ""
"Project-Id-Version: Recruiting Playbook 1.0.0\n"
"Language: de_DE\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"

#: src/PostTypes/JobListing.php:25
msgid "Jobs"
msgstr "Stellen"

#: src/PostTypes/JobListing.php:26
msgid "Job"
msgstr "Stelle"

# ... weitere Übersetzungen
```

### 5.3 MO-Datei kompilieren

```bash
wp i18n make-mo languages/recruiting-playbook-de_DE.po
```

---

## 6. Code-Review & Cleanup

### 6.1 PHPCS-Check

```bash
cd plugin
composer phpcs
```

**Erwartete Ergebnisse:**
- Keine Errors
- Wenige Warnings (dokumentieren wenn unvermeidbar)

### 6.2 PHPStan-Check

```bash
cd plugin
composer phpstan
```

**Level:** 5 (mittlere Strenge)

### 6.3 Cleanup-Checkliste

- [ ] Keine `var_dump()` oder `print_r()` im Code
- [ ] Keine `error_log()` außer in Debug-Modus
- [ ] Alle `// TODO` Kommentare bearbeitet oder dokumentiert
- [ ] Keine auskommentierten Code-Blöcke
- [ ] Versionsnummer auf 1.0.0 gesetzt
- [ ] `readme.txt` aktualisiert
- [ ] Changelog geschrieben

---

## 7. Pilotkunden-Deployment

### 7.1 Voraussetzungen

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ oder MariaDB 10.3+
- HTTPS aktiviert
- SMTP-Plugin installiert (empfohlen)

### 7.2 Installations-Checkliste

```markdown
## Pre-Installation
- [ ] Backup erstellt
- [ ] WordPress-Version geprüft
- [ ] PHP-Version geprüft
- [ ] SMTP-Plugin installiert

## Installation
- [ ] Plugin hochgeladen
- [ ] Plugin aktiviert
- [ ] Setup-Wizard durchlaufen
- [ ] Test-E-Mail gesendet

## Konfiguration
- [ ] Firmendaten eingegeben
- [ ] Datenschutz-URL konfiguriert
- [ ] E-Mail-Empfänger konfiguriert
- [ ] Erste Stelle erstellt

## Testing
- [ ] Stelle im Frontend sichtbar
- [ ] Test-Bewerbung eingereicht
- [ ] E-Mail erhalten
- [ ] Bewerbung im Admin sichtbar

## Go-Live
- [ ] Stellenseiten verlinkt
- [ ] Google for Jobs Schema getestet
- [ ] Kontaktdaten für Support hinterlegt
```

### 7.3 Feedback-Formular

```markdown
## Feedback-Fragen für Pilotkunden

1. Wie einfach war die Installation? (1-5)
2. Wie intuitiv ist die Bedienung? (1-5)
3. Welche Features fehlen Ihnen am meisten?
4. Gab es technische Probleme? Wenn ja, welche?
5. Würden Sie das Plugin weiterempfehlen?
6. Sonstige Anmerkungen:
```

---

## 8. Dokumentation

### 8.1 Dokumentations-Struktur

```
docs/
├── installation.md       # Installation & Konfiguration
├── shortcodes.md         # Shortcode-Referenz
├── templates.md          # Template-Anpassung
├── hooks.md              # Actions & Filters
├── faq.md                # Häufige Fragen
└── troubleshooting.md    # Problemlösungen
```

### 8.2 Installation.md (Beispiel)

```markdown
# Installation

## Systemanforderungen

- WordPress 6.0 oder höher
- PHP 8.0 oder höher
- MySQL 5.7+ oder MariaDB 10.3+

## Installation

1. Plugin unter **Plugins → Installieren → Plugin hochladen** hochladen
2. Plugin aktivieren
3. Setup-Wizard durchlaufen

## SMTP-Konfiguration

Für zuverlässigen E-Mail-Versand empfehlen wir eines dieser Plugins:

- [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/)
- [Post SMTP](https://wordpress.org/plugins/post-smtp/)

## Erste Schritte

1. Unter **Recruiting → Stellen** eine neue Stelle erstellen
2. Stelle veröffentlichen
3. Optional: Shortcode `[rp_jobs]` auf einer Seite einfügen
```

---

## 9. Deliverables & Checkliste

### 9.1 Phase 1D Deliverables

| Deliverable | Status |
|-------------|--------|
| Setup-Wizard funktioniert | ⬜ |
| Google for Jobs Schema generiert | ⬜ |
| Alle Shortcodes dokumentiert | ⬜ |
| PHPUnit-Tests für kritische Services | ⬜ |
| Manuelle Tests abgeschlossen | ⬜ |
| Cross-Browser-Tests bestanden | ⬜ |
| Deutsche Übersetzung komplett | ⬜ |
| PHPCS ohne Errors | ⬜ |
| 2-3 Pilotkunden aktiv | ⬜ |
| Feedback eingearbeitet | ⬜ |
| Dokumentation vorhanden | ⬜ |
| Version 1.0.0 getaggt | ⬜ |

### 9.2 Abnahme-Checkliste

```markdown
## Finale Abnahme Phase 1

### Funktionalität
- [ ] Jobs erstellen, bearbeiten, löschen
- [ ] Jobs im Frontend anzeigen
- [ ] Bewerbungen empfangen
- [ ] E-Mails werden versendet
- [ ] Bewerbungen verwalten
- [ ] Status ändern
- [ ] Dokumente herunterladen
- [ ] Daten exportieren (Backup)
- [ ] Daten löschen (DSGVO)

### Qualität
- [ ] Keine kritischen Bugs
- [ ] Performance akzeptabel
- [ ] Responsive Design
- [ ] Barrierefreiheit (Basis)

### Dokumentation
- [ ] Installations-Anleitung
- [ ] Shortcode-Referenz
- [ ] FAQ

### Deployment
- [ ] Pilotkunden-Installation(en) erfolgreich
- [ ] Positives Feedback erhalten
- [ ] Kritische Bugs behoben
```

---

*Letzte Aktualisierung: Januar 2025*

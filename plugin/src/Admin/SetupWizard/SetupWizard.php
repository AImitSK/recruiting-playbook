<?php
/**
 * Setup-Wizard für Erstkonfiguration
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\SetupWizard;

use RecruitingPlaybook\Services\EmailService;

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
		// Admin-Seite registrieren (versteckt).
		add_action( 'admin_menu', [ $this, 'registerPage' ] );

		// AJAX-Handler.
		add_action( 'wp_ajax_rp_wizard_save_step', [ $this, 'ajaxSaveStep' ] );
		add_action( 'wp_ajax_rp_wizard_skip', [ $this, 'ajaxSkipWizard' ] );
		add_action( 'wp_ajax_rp_send_test_email', [ $this, 'ajaxSendTestEmail' ] );

		// Redirect nach Aktivierung.
		add_action( 'admin_init', [ $this, 'maybeRedirect' ] );
	}

	/**
	 * Bei Plugin-Aktivierung zum Wizard weiterleiten
	 */
	public function maybeRedirect(): void {
		// Prüfen ob Redirect gesetzt.
		if ( ! get_option( 'rp_activation_redirect', false ) ) {
			return;
		}

		// Redirect-Flag löschen.
		delete_option( 'rp_activation_redirect' );

		// Nicht bei Multisite-Aktivierung.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Wizard bereits abgeschlossen.
		if ( get_option( 'rp_wizard_completed', false ) ) {
			return;
		}

		// Zum Wizard weiterleiten.
		wp_safe_redirect( admin_url( 'admin.php?page=rp-setup-wizard' ) );
		exit;
	}

	/**
	 * Prüfen ob Wizard angezeigt werden soll
	 *
	 * @return bool
	 */
	public function shouldShowWizard(): bool {
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
			'', // Versteckt (leerer String für PHP 8.1+ Kompatibilität).
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
		$step_keys     = array_keys( $this->steps );
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
								<?php esc_html_e( 'URL zu Ihrem Firmenlogo (empfohlen fuer Google for Jobs).', 'recruiting-playbook' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="company_street"><?php esc_html_e( 'Strasse', 'recruiting-playbook' ); ?></label>
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
								<option value="AT" <?php selected( $settings['company_country'] ?? '', 'AT' ); ?>>Oesterreich</option>
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
								<?php esc_html_e( 'Link zur Datenschutzerklaerung fuer das Bewerbungsformular.', 'recruiting-playbook' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<input type="hidden" name="step" value="company">
				<?php wp_nonce_field( 'rp_wizard_nonce', 'nonce' ); ?>

				<p class="rp-wizard-actions">
					<a href="<?php echo esc_url( $this->getStepUrl( 'welcome' ) ); ?>" class="button">
						<?php esc_html_e( 'Zurueck', 'recruiting-playbook' ); ?>
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
	 * @return bool|\WP_Error
	 */
	public function saveCompany( array $data ): bool|\WP_Error {
		$settings = get_option( 'rp_settings', [] );

		$settings['company_name']       = sanitize_text_field( $data['company_name'] ?? '' );
		$settings['company_logo']       = esc_url_raw( $data['company_logo'] ?? '' );
		$settings['company_street']     = sanitize_text_field( $data['company_street'] ?? '' );
		$settings['company_zip']        = sanitize_text_field( $data['company_zip'] ?? '' );
		$settings['company_city']       = sanitize_text_field( $data['company_city'] ?? '' );
		$settings['company_country']    = sanitize_text_field( $data['company_country'] ?? 'DE' );
		$settings['privacy_policy_url'] = esc_url_raw( $data['privacy_policy_url'] ?? '' );

		update_option( 'rp_settings', $settings );

		return true;
	}

	/**
	 * Schritt 3: E-Mail
	 */
	public function renderEmail(): void {
		$settings    = get_option( 'rp_settings', [] );
		$smtp_status = EmailService::checkSmtpConfig();
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
								<?php esc_html_e( 'An diese Adresse werden Benachrichtigungen ueber neue Bewerbungen gesendet.', 'recruiting-playbook' ); ?>
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
						<?php esc_html_e( 'Zurueck', 'recruiting-playbook' ); ?>
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
	 * @return bool|\WP_Error
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
				<?php esc_html_e( 'Erstellen Sie jetzt Ihre erste Stellenanzeige oder ueberspringen Sie diesen Schritt.', 'recruiting-playbook' ); ?>
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
							<label for="job_type"><?php esc_html_e( 'Beschaeftigungsart', 'recruiting-playbook' ); ?></label>
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
						<?php esc_html_e( 'Zurueck', 'recruiting-playbook' ); ?>
					</a>
					<button type="button" class="button" id="rp-skip-job">
						<?php esc_html_e( 'Ueberspringen', 'recruiting-playbook' ); ?>
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
	 * @return bool|\WP_Error
	 */
	public function saveFirstJob( array $data ): bool|\WP_Error {
		// Ueberspringen wenn gewuenscht.
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

		// Beschaeftigungsart als Taxonomie.
		if ( ! empty( $data['job_type'] ) ) {
			$type_mapping = [
				'FULL_TIME'  => 'Vollzeit',
				'PART_TIME'  => 'Teilzeit',
				'TEMPORARY'  => 'Befristet',
				'INTERN'     => 'Praktikum',
			];
			$type_name    = $type_mapping[ $data['job_type'] ] ?? 'Vollzeit';
			wp_set_object_terms( $post_id, $type_name, 'employment_type' );
		}

		// Speichern fuer Redirect nach Wizard.
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
				<h3><?php esc_html_e( 'Naechste Schritte:', 'recruiting-playbook' ); ?></h3>
				<ul>
					<?php if ( $created_job_id ) : ?>
						<li>
							<a href="<?php echo esc_url( get_edit_post_link( $created_job_id ) ); ?>">
								<?php esc_html_e( 'Ihre erste Stelle vervollstaendigen', 'recruiting-playbook' ); ?>
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
				<h3><?php esc_html_e( 'Shortcodes fuer Ihre Seiten:', 'recruiting-playbook' ); ?></h3>
				<table class="widefat striped">
					<tr>
						<td><code>[rp_jobs]</code></td>
						<td><?php esc_html_e( 'Zeigt alle Stellenanzeigen an', 'recruiting-playbook' ); ?></td>
					</tr>
					<tr>
						<td><code>[rp_job_search]</code></td>
						<td><?php esc_html_e( 'Suchformular fuer Stellen', 'recruiting-playbook' ); ?></td>
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
	 * URL fuer einen Schritt generieren
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

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';

		if ( ! isset( $this->steps[ $step ] ) || ! is_callable( $this->steps[ $step ]['save'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Ungueltiger Schritt.', 'recruiting-playbook' ) ] );
		}

		// Sanitize POST data before passing to callback.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_data = wp_unslash( $_POST );
		$result    = call_user_func( $this->steps[ $step ]['save'], $post_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		// Naechsten Schritt ermitteln.
		$step_keys     = array_keys( $this->steps );
		$current_index = array_search( $step, $step_keys, true );
		$next_step     = $step_keys[ $current_index + 1 ] ?? 'complete';

		wp_send_json_success( [
			'next_url' => $this->getStepUrl( $next_step ),
		] );
	}

	/**
	 * AJAX: Wizard ueberspringen
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
			wp_send_json_error( [ 'message' => __( 'Ungueltige E-Mail-Adresse.', 'recruiting-playbook' ) ] );
		}

		$subject = __( 'Recruiting Playbook - Test-E-Mail', 'recruiting-playbook' );
		$message = sprintf(
			/* translators: %s: current date/time */
			__( "Dies ist eine Test-E-Mail von Recruiting Playbook.\n\nWenn Sie diese E-Mail erhalten, funktioniert der E-Mail-Versand korrekt.\n\nGesendet am: %s", 'recruiting-playbook' ),
			current_time( 'mysql' )
		);

		$sent = wp_mail( $email, $subject, $message );

		if ( $sent ) {
			wp_send_json_success( [ 'message' => __( 'Test-E-Mail wurde gesendet!', 'recruiting-playbook' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'E-Mail konnte nicht gesendet werden. Bitte pruefen Sie Ihre SMTP-Konfiguration.', 'recruiting-playbook' ) ] );
		}
	}
}

<?php
/**
 * Setup-Wizard für Erstkonfiguration
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Admin\SetupWizard;

defined( 'ABSPATH' ) || exit;

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
				'name'    => __( 'Welcome', 'recruiting-playbook' ),
				'handler' => [ $this, 'renderWelcome' ],
				'save'    => null,
			],
			'company' => [
				'name'    => __( 'Company Details', 'recruiting-playbook' ),
				'handler' => [ $this, 'renderCompany' ],
				'save'    => [ $this, 'saveCompany' ],
			],
			'email' => [
				'name'    => __( 'Email', 'recruiting-playbook' ),
				'handler' => [ $this, 'renderEmail' ],
				'save'    => [ $this, 'saveEmail' ],
			],
			'first_job' => [
				'name'    => __( 'First Job', 'recruiting-playbook' ),
				'handler' => [ $this, 'renderFirstJob' ],
				'save'    => [ $this, 'saveFirstJob' ],
			],
			'complete' => [
				'name'    => __( 'Complete', 'recruiting-playbook' ),
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

		// Assets auf der Wizard-Seite laden.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );

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
			__( 'Setup Wizard', 'recruiting-playbook' ),
			__( 'Setup Wizard', 'recruiting-playbook' ),
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

		// Template laden (rendert innerhalb des WP-Admin-Wrappers).
		include RP_PLUGIN_DIR . 'src/Admin/SetupWizard/views/wizard.php';
	}

	/**
	 * Assets auf der Wizard-Seite laden
	 *
	 * @param string $hook_suffix Admin-Seiten-Hook.
	 */
	public function enqueueAdminAssets( string $hook_suffix ): void {
		if ( 'admin_page_rp-setup-wizard' !== $hook_suffix ) {
			return;
		}

		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/wizard.css';
		$version  = file_exists( $css_file ) ? filemtime( $css_file ) : RP_VERSION;

		wp_enqueue_style(
			'rp-wizard',
			RP_PLUGIN_URL . 'assets/dist/css/wizard.css',
			[],
			$version
		);

		$js_file    = RP_PLUGIN_DIR . 'assets/dist/js/wizard.js';
		$js_version = file_exists( $js_file ) ? filemtime( $js_file ) : RP_VERSION;

		wp_enqueue_script(
			'rp-wizard',
			RP_PLUGIN_URL . 'assets/dist/js/wizard.js',
			[ 'jquery' ],
			$js_version,
			true
		);

		wp_localize_script(
			'rp-wizard',
			'rpWizard',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'rp_wizard_nonce' ),
				'i18n'    => [
					'saving'       => __( 'Saving...', 'recruiting-playbook' ),
					'saved'        => __( 'Saved!', 'recruiting-playbook' ),
					'error'        => __( 'An error occurred.', 'recruiting-playbook' ),
					'sendingEmail' => __( 'Sending email...', 'recruiting-playbook' ),
					'emailSent'    => __( 'Test email sent!', 'recruiting-playbook' ),
					'emailFailed'  => __( 'Email could not be sent.', 'recruiting-playbook' ),
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
					<span class="step-number">
						<?php if ( 'completed' === $class ) : ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
						<?php else : ?>
							<?php echo esc_html( $index + 1 ); ?>
						<?php endif; ?>
					</span>
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
			<h2><?php esc_html_e( 'Welcome to Recruiting Playbook!', 'recruiting-playbook' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'This wizard will help you set up the plugin in just a few minutes.', 'recruiting-playbook' ); ?>
			</p>

			<div class="rp-wizard-features">
				<div class="feature">
					<span class="feature-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
					</span>
					<h4><?php esc_html_e( 'Manage job listings', 'recruiting-playbook' ); ?></h4>
					<p><?php esc_html_e( 'Create and manage your job listings directly in WordPress.', 'recruiting-playbook' ); ?></p>
				</div>
				<div class="feature">
					<span class="feature-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
					</span>
					<h4><?php esc_html_e( 'Receive applications', 'recruiting-playbook' ); ?></h4>
					<p><?php esc_html_e( 'Candidates can apply directly on your website.', 'recruiting-playbook' ); ?></p>
				</div>
				<div class="feature">
					<span class="feature-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
					</span>
					<h4><?php esc_html_e( 'Manage applications', 'recruiting-playbook' ); ?></h4>
					<p><?php esc_html_e( 'Keep track of all applications.', 'recruiting-playbook' ); ?></p>
				</div>
			</div>

			<p class="rp-wizard-actions">
				<button type="button" class="button button-link rp-skip-wizard">
					<?php esc_html_e( 'Set up later', 'recruiting-playbook' ); ?>
				</button>
				<a href="<?php echo esc_url( $this->getStepUrl( 'company' ) ); ?>" class="button button-primary button-hero">
					<?php esc_html_e( 'Let\'s go!', 'recruiting-playbook' ); ?>
				</a>
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
			<h2><?php esc_html_e( 'Company Details', 'recruiting-playbook' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'This information will be used in emails and in the Google for Jobs schema.', 'recruiting-playbook' ); ?>
			</p>

			<form id="rp-wizard-company-form" class="rp-wizard-form">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="company_name"><?php esc_html_e( 'Company Name', 'recruiting-playbook' ); ?> *</label>
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
							<label for="company_logo"><?php esc_html_e( 'Company Logo URL', 'recruiting-playbook' ); ?></label>
						</th>
						<td>
							<input type="url"
								   id="company_logo"
								   name="company_logo"
								   value="<?php echo esc_url( $settings['company_logo'] ?? '' ); ?>"
								   class="regular-text">
							<p class="description">
								<?php esc_html_e( 'URL to your company logo (recommended for Google for Jobs).', 'recruiting-playbook' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="company_street"><?php esc_html_e( 'Street', 'recruiting-playbook' ); ?></label>
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
							<label for="company_zip"><?php esc_html_e( 'Postal Code', 'recruiting-playbook' ); ?></label>
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
							<label for="company_city"><?php esc_html_e( 'City', 'recruiting-playbook' ); ?></label>
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
							<label for="company_country"><?php esc_html_e( 'Country', 'recruiting-playbook' ); ?></label>
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
							<label for="privacy_policy_url"><?php esc_html_e( 'Privacy Policy URL', 'recruiting-playbook' ); ?> *</label>
						</th>
						<td>
							<input type="url"
								   id="privacy_policy_url"
								   name="privacy_policy_url"
								   value="<?php echo esc_url( $settings['privacy_policy_url'] ?? get_privacy_policy_url() ); ?>"
								   class="regular-text"
								   required>
							<p class="description">
								<?php esc_html_e( 'Link to the privacy policy for the application form.', 'recruiting-playbook' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<input type="hidden" name="step" value="company">
				<?php wp_nonce_field( 'rp_wizard_nonce', 'nonce' ); ?>

				<p class="rp-wizard-actions">
					<a href="<?php echo esc_url( $this->getStepUrl( 'welcome' ) ); ?>" class="button">
						<?php esc_html_e( 'Back', 'recruiting-playbook' ); ?>
					</a>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Continue', 'recruiting-playbook' ); ?>
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
			<h2><?php esc_html_e( 'Email Settings', 'recruiting-playbook' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure how email notifications will be sent.', 'recruiting-playbook' ); ?>
			</p>

			<!-- SMTP-Status -->
			<div class="rp-smtp-status <?php echo $smtp_status['configured'] ? 'configured' : 'not-configured'; ?>">
				<span class="status-icon">
					<?php if ( $smtp_status['configured'] ) : ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
					<?php else : ?>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
					<?php endif; ?>
				</span>
				<div class="status-content">
					<?php if ( $smtp_status['configured'] ) : ?>
						<strong><?php esc_html_e( 'SMTP configured', 'recruiting-playbook' ); ?></strong>
						<p><?php echo esc_html( $smtp_status['message'] ); ?></p>
					<?php else : ?>
						<strong><?php esc_html_e( 'No SMTP configured', 'recruiting-playbook' ); ?></strong>
						<p><?php echo esc_html( $smtp_status['message'] ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=smtp&tab=search&type=term' ) ); ?>"
						   target="_blank"
						   class="button button-secondary">
							<?php esc_html_e( 'Install SMTP plugin', 'recruiting-playbook' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<form id="rp-wizard-email-form" class="rp-wizard-form">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="notification_email"><?php esc_html_e( 'Notification Email', 'recruiting-playbook' ); ?> *</label>
						</th>
						<td>
							<input type="email"
								   id="notification_email"
								   name="notification_email"
								   value="<?php echo esc_attr( $settings['notification_email'] ?? get_option( 'admin_email' ) ); ?>"
								   class="regular-text"
								   required>
							<p class="description">
								<?php esc_html_e( 'Notifications about new applications will be sent to this address.', 'recruiting-playbook' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sender_name"><?php esc_html_e( 'Sender Name', 'recruiting-playbook' ); ?></label>
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
					<h3><?php esc_html_e( 'Send test email', 'recruiting-playbook' ); ?></h3>
					<p>
						<input type="email"
							   id="test_email_address"
							   placeholder="<?php esc_attr_e( 'Email address', 'recruiting-playbook' ); ?>"
							   value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
							   class="regular-text">
						<button type="button" id="rp-send-test-email" class="button">
							<?php esc_html_e( 'Send test', 'recruiting-playbook' ); ?>
						</button>
					</p>
					<div id="rp-test-email-result"></div>
				</div>

				<input type="hidden" name="step" value="email">
				<?php wp_nonce_field( 'rp_wizard_nonce', 'nonce' ); ?>

				<p class="rp-wizard-actions">
					<a href="<?php echo esc_url( $this->getStepUrl( 'company' ) ); ?>" class="button">
						<?php esc_html_e( 'Back', 'recruiting-playbook' ); ?>
					</a>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Continue', 'recruiting-playbook' ); ?>
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
			<h2><?php esc_html_e( 'Create first job', 'recruiting-playbook' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Create your first job listing now or skip this step.', 'recruiting-playbook' ); ?>
			</p>

			<form id="rp-wizard-job-form" class="rp-wizard-form">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="job_title"><?php esc_html_e( 'Job Title', 'recruiting-playbook' ); ?></label>
						</th>
						<td>
							<input type="text"
								   id="job_title"
								   name="job_title"
								   placeholder="<?php esc_attr_e( 'e.g. Senior Developer (m/f/d)', 'recruiting-playbook' ); ?>"
								   class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="job_location"><?php esc_html_e( 'Location', 'recruiting-playbook' ); ?></label>
						</th>
						<td>
							<input type="text"
								   id="job_location"
								   name="job_location"
								   placeholder="<?php esc_attr_e( 'e.g. Berlin', 'recruiting-playbook' ); ?>"
								   class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="job_type"><?php esc_html_e( 'Employment Type', 'recruiting-playbook' ); ?></label>
						</th>
						<td>
							<select id="job_type" name="job_type">
								<option value="FULL_TIME"><?php esc_html_e( 'Full-time', 'recruiting-playbook' ); ?></option>
								<option value="PART_TIME"><?php esc_html_e( 'Part-time', 'recruiting-playbook' ); ?></option>
								<option value="TEMPORARY"><?php esc_html_e( 'Temporary', 'recruiting-playbook' ); ?></option>
								<option value="INTERN"><?php esc_html_e( 'Internship', 'recruiting-playbook' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<input type="hidden" name="step" value="first_job">
				<input type="hidden" name="skip_job" id="skip_job" value="0">
				<?php wp_nonce_field( 'rp_wizard_nonce', 'nonce' ); ?>

				<p class="rp-wizard-actions">
					<a href="<?php echo esc_url( $this->getStepUrl( 'email' ) ); ?>" class="button">
						<?php esc_html_e( 'Back', 'recruiting-playbook' ); ?>
					</a>
					<button type="button" class="button" id="rp-skip-job">
						<?php esc_html_e( 'Skip', 'recruiting-playbook' ); ?>
					</button>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Create job & Continue', 'recruiting-playbook' ); ?>
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
			<span class="complete-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
			</span>
			<h2><?php esc_html_e( 'Setup complete!', 'recruiting-playbook' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Recruiting Playbook is now ready to use.', 'recruiting-playbook' ); ?>
			</p>

			<div class="rp-wizard-next-steps">
				<h3><?php esc_html_e( 'Next steps:', 'recruiting-playbook' ); ?></h3>
				<ul>
					<?php if ( $created_job_id ) : ?>
						<li>
							<a href="<?php echo esc_url( get_edit_post_link( $created_job_id ) ); ?>">
								<?php esc_html_e( 'Complete your first job', 'recruiting-playbook' ); ?>
							</a>
						</li>
					<?php else : ?>
						<li>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=job_listing' ) ); ?>">
								<?php esc_html_e( 'Create first job', 'recruiting-playbook' ); ?>
							</a>
						</li>
					<?php endif; ?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-settings' ) ); ?>">
							<?php esc_html_e( 'Configure additional settings', 'recruiting-playbook' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) ); ?>">
							<?php esc_html_e( 'Go to Dashboard', 'recruiting-playbook' ); ?>
						</a>
					</li>
				</ul>
			</div>

			<div class="rp-wizard-shortcodes">
				<h3><?php esc_html_e( 'Shortcodes for your pages:', 'recruiting-playbook' ); ?></h3>
				<table class="widefat striped">
					<tr>
						<td><code>[rp_jobs]</code></td>
						<td><?php esc_html_e( 'Displays all job listings', 'recruiting-playbook' ); ?></td>
					</tr>
					<tr>
						<td><code>[rp_job_search]</code></td>
						<td><?php esc_html_e( 'Search form for jobs', 'recruiting-playbook' ); ?></td>
					</tr>
					<tr>
						<td><code>[rp_application_form]</code></td>
						<td><?php esc_html_e( 'Application form (on job page)', 'recruiting-playbook' ); ?></td>
					</tr>
				</table>
			</div>

			<p class="rp-wizard-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=recruiting-playbook' ) ); ?>" class="button button-primary button-hero">
					<?php esc_html_e( 'Go to Dashboard', 'recruiting-playbook' ); ?>
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
			wp_send_json_error( [ 'message' => __( 'No permission.', 'recruiting-playbook' ) ] );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';

		if ( ! isset( $this->steps[ $step ] ) || ! is_callable( $this->steps[ $step ]['save'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid step.', 'recruiting-playbook' ) ] );
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
			wp_send_json_error( [ 'message' => __( 'No permission.', 'recruiting-playbook' ) ] );
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
			wp_send_json_error( [ 'message' => __( 'No permission.', 'recruiting-playbook' ) ] );
		}

		$email = sanitize_email( $_POST['email'] ?? '' );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid email address.', 'recruiting-playbook' ) ] );
		}

		$subject = __( 'Recruiting Playbook - Test Email', 'recruiting-playbook' );
		$message = sprintf(
			/* translators: %s: current date/time */
			__( "This is a test email from Recruiting Playbook.\n\nIf you receive this email, email delivery is working correctly.\n\nSent at: %s", 'recruiting-playbook' ),
			current_time( 'mysql' )
		);

		$sent = wp_mail( $email, $subject, $message );

		if ( $sent ) {
			wp_send_json_success( [ 'message' => __( 'Test email sent!', 'recruiting-playbook' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Email could not be sent. Please check your SMTP configuration.', 'recruiting-playbook' ) ] );
		}
	}
}

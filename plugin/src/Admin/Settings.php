<?php
/**
 * Admin Settings Seite
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Admin;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\EmailService;
use RecruitingPlaybook\Core\RoleManager;

/**
 * Settings-Seite im Admin
 */
class Settings {

	/**
	 * Option name
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'rp_settings';

	/**
	 * Registrieren
	 */
	public function register(): void {
		add_action( 'admin_init', [ $this, 'registerSettings' ] );
	}

	/**
	 * Settings registrieren
	 */
	public function registerSettings(): void {
		// Settings registrieren.
		register_setting(
			'rp_settings_group',
			self::OPTION_NAME,
			[
				'sanitize_callback' => [ $this, 'sanitizeSettings' ],
				'default'           => $this->getDefaults(),
			]
		);

		// Sektion: Allgemein.
		add_settings_section(
			'rp_general_section',
			__( 'General Settings', 'recruiting-playbook' ),
			[ $this, 'renderGeneralSection' ],
			'rp-settings'
		);

		// Feld: Benachrichtigungs-E-Mail.
		add_settings_field(
			'notification_email',
			__( 'Notification Email', 'recruiting-playbook' ),
			[ $this, 'renderEmailField' ],
			'rp-settings',
			'rp_general_section',
			[
				'label_for'   => 'notification_email',
				'description' => __( 'Email address for new applications.', 'recruiting-playbook' ),
			]
		);

		// Feld: Datenschutz-URL.
		add_settings_field(
			'privacy_url',
			__( 'Privacy Page', 'recruiting-playbook' ),
			[ $this, 'renderPageSelectField' ],
			'rp-settings',
			'rp_general_section',
			[
				'label_for'   => 'privacy_url',
				'description' => __( 'Page with privacy policy for the application form.', 'recruiting-playbook' ),
			]
		);

		// Sektion: Firmendaten.
		add_settings_section(
			'rp_company_section',
			__( 'Company Data', 'recruiting-playbook' ),
			[ $this, 'renderCompanySection' ],
			'rp-settings'
		);

		// Feld: Firmenname.
		add_settings_field(
			'company_name',
			__( 'Company Name', 'recruiting-playbook' ) . ' *',
			[ $this, 'renderTextField' ],
			'rp-settings',
			'rp_company_section',
			[
				'label_for'   => 'company_name',
				'description' => __( 'Displayed in schema, emails, and on the careers page.', 'recruiting-playbook' ),
				'required'    => true,
			]
		);

		// Feld: Straße.
		add_settings_field(
			'company_street',
			__( 'Street & Number', 'recruiting-playbook' ),
			[ $this, 'renderTextField' ],
			'rp-settings',
			'rp_company_section',
			[
				'label_for' => 'company_street',
			]
		);

		// Feld: PLZ & Stadt.
		add_settings_field(
			'company_city',
			__( 'Postal Code & City', 'recruiting-playbook' ),
			[ $this, 'renderZipCityField' ],
			'rp-settings',
			'rp_company_section',
			[
				'label_for' => 'company_zip',
			]
		);

		// Feld: Telefon.
		add_settings_field(
			'company_phone',
			__( 'Phone', 'recruiting-playbook' ),
			[ $this, 'renderTextField' ],
			'rp-settings',
			'rp_company_section',
			[
				'label_for'   => 'company_phone',
				'placeholder' => '+49 123 456789',
			]
		);

		// Feld: Website.
		add_settings_field(
			'company_website',
			__( 'Website', 'recruiting-playbook' ),
			[ $this, 'renderUrlField' ],
			'rp-settings',
			'rp_company_section',
			[
				'label_for'   => 'company_website',
				'placeholder' => 'https://www.example.com',
			]
		);

		// Feld: Kontakt-E-Mail.
		add_settings_field(
			'company_email',
			__( 'Contact Email', 'recruiting-playbook' ) . ' *',
			[ $this, 'renderEmailField' ],
			'rp-settings',
			'rp_company_section',
			[
				'label_for'   => 'company_email',
				'description' => __( 'General contact email of the company (for email signatures).', 'recruiting-playbook' ),
				'required'    => true,
			]
		);

		// Sektion: E-Mail-Absender.
		add_settings_section(
			'rp_sender_section',
			__( 'Default Sender', 'recruiting-playbook' ),
			[ $this, 'renderSenderSection' ],
			'rp-settings'
		);

		// Feld: Absender-Name.
		add_settings_field(
			'sender_name',
			__( 'Sender Name', 'recruiting-playbook' ),
			[ $this, 'renderTextField' ],
			'rp-settings',
			'rp_sender_section',
			[
				'label_for'   => 'sender_name',
				'description' => __( 'Name displayed as sender in emails.', 'recruiting-playbook' ),
				'placeholder' => __( 'HR Department', 'recruiting-playbook' ),
			]
		);

		// Feld: Absender-E-Mail.
		add_settings_field(
			'sender_email',
			__( 'Sender Email', 'recruiting-playbook' ),
			[ $this, 'renderEmailField' ],
			'rp-settings',
			'rp_sender_section',
			[
				'label_for'   => 'sender_email',
				'description' => __( 'Email address from which emails are sent.', 'recruiting-playbook' ),
				'placeholder' => 'jobs@example.com',
			]
		);

		// Sektion: Stellenanzeigen.
		add_settings_section(
			'rp_jobs_section',
			__( 'Job Listings', 'recruiting-playbook' ),
			[ $this, 'renderJobsSection' ],
			'rp-settings'
		);

		// Feld: Stellen pro Seite.
		add_settings_field(
			'jobs_per_page',
			__( 'Jobs Per Page', 'recruiting-playbook' ),
			[ $this, 'renderNumberField' ],
			'rp-settings',
			'rp_jobs_section',
			[
				'label_for' => 'jobs_per_page',
				'min'       => 1,
				'max'       => 50,
			]
		);

		// Feld: URL-Slug.
		add_settings_field(
			'jobs_slug',
			__( 'URL Slug', 'recruiting-playbook' ),
			[ $this, 'renderSlugField' ],
			'rp-settings',
			'rp_jobs_section',
			[
				'label_for'   => 'jobs_slug',
				'description' => __( 'URL path for the jobs overview (e.g., "jobs" for /jobs/).', 'recruiting-playbook' ),
			]
		);

		// Feld: Schema aktivieren.
		add_settings_field(
			'enable_schema',
			__( 'Google for Jobs Schema', 'recruiting-playbook' ),
			[ $this, 'renderCheckboxField' ],
			'rp-settings',
			'rp_jobs_section',
			[
				'label_for'   => 'enable_schema',
				'label'       => __( 'Enable JSON-LD schema for Google for Jobs', 'recruiting-playbook' ),
				'description' => __( 'Structured data for better visibility in Google.', 'recruiting-playbook' ),
			]
		);

		// Sektion: Pro-Features (nur wenn Pro-Lizenz vorhanden).
		if ( function_exists( 'rp_can' ) && rp_can( 'custom_branding' ) ) {
			add_settings_section(
				'rp_pro_section',
				__( 'Pro Settings', 'recruiting-playbook' ),
				[ $this, 'renderProSection' ],
				'rp-settings'
			);

			// Feld: E-Mail-Branding ausblenden.
			add_settings_field(
				'hide_email_branding',
				__( 'White-Label Emails', 'recruiting-playbook' ),
				[ $this, 'renderCheckboxField' ],
				'rp-settings',
				'rp_pro_section',
				[
					'label_for'   => 'hide_email_branding',
					'label'       => __( 'Hide "Sent via Recruiting Playbook" notice in emails', 'recruiting-playbook' ),
					'description' => __( 'Removes the branding notice from the footer of all emails.', 'recruiting-playbook' ),
				]
			);
		}

	}

	/**
	 * Standard-Werte
	 *
	 * @return array
	 */
	private function getDefaults(): array {
		return [
			// Allgemein.
			'notification_email'   => get_option( 'admin_email' ),
			'privacy_url'          => get_privacy_policy_url(),

			// Firmendaten.
			'company_name'         => get_bloginfo( 'name' ),
			'company_street'       => '',
			'company_zip'          => '',
			'company_city'         => '',
			'company_phone'        => '',
			'company_website'      => home_url(),
			'company_email'        => get_option( 'admin_email' ),

			// Standard-Absender.
			'sender_name'          => __( 'HR Department', 'recruiting-playbook' ),
			'sender_email'         => get_option( 'admin_email' ),

			// Stellenanzeigen.
			'jobs_per_page'        => 10,
			'jobs_slug'            => 'jobs',
			'enable_schema'        => true,

			// Pro-Features.
			'hide_email_branding'  => false,
			'disable_ai_features'  => false,
		];
	}

	/**
	 * Settings sanitizen
	 *
	 * @param array $input Input-Werte.
	 * @return array
	 */
	public function sanitizeSettings( array $input ): array {
		$output = [];

		// Allgemein.
		$output['notification_email'] = sanitize_email( $input['notification_email'] ?? '' );

		// privacy_url kommt als Page-ID von wp_dropdown_pages, muss in URL konvertiert werden.
		$privacy_page_id       = absint( $input['privacy_url'] ?? 0 );
		$output['privacy_url'] = $privacy_page_id ? get_permalink( $privacy_page_id ) : '';

		// Firmendaten.
		$output['company_name']    = sanitize_text_field( $input['company_name'] ?? '' );
		$output['company_street']  = sanitize_text_field( $input['company_street'] ?? '' );
		$output['company_zip']     = sanitize_text_field( $input['company_zip'] ?? '' );
		$output['company_city']    = sanitize_text_field( $input['company_city'] ?? '' );
		$output['company_phone']   = sanitize_text_field( $input['company_phone'] ?? '' );
		$output['company_website'] = esc_url_raw( $input['company_website'] ?? '' );
		$output['company_email']   = sanitize_email( $input['company_email'] ?? '' );

		// Standard-Absender.
		$output['sender_name']  = sanitize_text_field( $input['sender_name'] ?? '' );
		$output['sender_email'] = sanitize_email( $input['sender_email'] ?? '' );

		// Stellenanzeigen.
		$output['jobs_per_page'] = absint( $input['jobs_per_page'] ?? 10 );
		$output['jobs_slug']     = sanitize_title( $input['jobs_slug'] ?? 'jobs' );
		$output['enable_schema'] = ! empty( $input['enable_schema'] );

		// Pro-Features.
		$output['hide_email_branding'] = ! empty( $input['hide_email_branding'] );
		$output['disable_ai_features'] = ! empty( $input['disable_ai_features'] );

		// Slug-Änderung erfordert Rewrite-Flush.
		$old_settings = get_option( self::OPTION_NAME, [] );
		if ( ( $old_settings['jobs_slug'] ?? 'jobs' ) !== $output['jobs_slug'] ) {
			set_transient( 'rp_flush_rewrite_rules', true, 60 );
		}

		return $output;
	}

	/**
	 * Settings-Seite rendern
	 */
	public function renderPage(): void {
		// Rewrite Rules flushen falls nötig.
		if ( get_transient( 'rp_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			delete_transient( 'rp_flush_rewrite_rules' );
		}

		// Assets laden.
		$this->enqueueAssets();

		// React-Mount-Point.
		?>
		<div class="wrap">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Settings', 'recruiting-playbook' ); ?></h1>
			<hr class="wp-header-end">
			<div id="rp-settings-root">
				<div style="display: flex; align-items: center; justify-content: center; min-height: 300px; color: #6b7280;">
					<span class="spinner is-active" style="float: none; margin-right: 10px;"></span>
					<?php esc_html_e( 'Loading settings...', 'recruiting-playbook' ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Assets laden
	 */
	private function enqueueAssets(): void {
		// Pro-Status prüfen.
		$is_pro = function_exists( 'rp_can' ) && rp_can( 'custom_branding' );

		// Konfiguration für React.
		$data = [
			'logoUrl'     => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
			'homeUrl'     => home_url(),
			'exportUrl'   => admin_url( 'admin.php?page=rp-settings' ),
			'nonce'       => wp_create_nonce( 'rp_download_backup' ),
			'pages'       => $this->getPages(),
			'isPro'       => $is_pro,
			'upgradeUrl'  => function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '',
			'i18n'        => $this->getI18nStrings(),
		];

		// Pro-Feature: Benutzerrollen-Daten für React-UI.
		if ( $is_pro ) {
			$data['recruitingUsers'] = $this->getRecruitingUsers();
			$data['jobListings']     = $this->getJobListings();
		}

		wp_localize_script(
			'rp-admin',
			'rpSettingsData',
			$data
		);
	}

	/**
	 * Seiten für Dropdown laden
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getPages(): array {
		$posts = get_pages( [
			'post_status' => 'publish',
			'sort_column' => 'post_title',
			'sort_order'  => 'ASC',
		] );

		$pages = [];
		foreach ( $posts as $page ) {
			$pages[] = [
				'id'    => $page->ID,
				'title' => $page->post_title,
			];
		}

		return $pages;
	}

	/**
	 * I18n-Strings für JavaScript
	 *
	 * @return array<string, string>
	 */
	private function getI18nStrings(): array {
		return [
			'pageTitle'             => __( 'Settings', 'recruiting-playbook' ),
			'tabGeneral'            => __( 'General', 'recruiting-playbook' ),
			'tabCompany'            => __( 'Company Data', 'recruiting-playbook' ),
			'tabExport'             => __( 'Export', 'recruiting-playbook' ),

			// General Settings.
			'notifications'         => __( 'Notifications', 'recruiting-playbook' ),
			'notificationsDesc'     => __( 'Email notifications for new applications', 'recruiting-playbook' ),
			'notificationEmail'     => __( 'Notification Email', 'recruiting-playbook' ),
			'notificationEmailDesc' => __( 'Email address for new applications.', 'recruiting-playbook' ),
			'privacyPage'           => __( 'Privacy Page', 'recruiting-playbook' ),
			'privacyPageDesc'       => __( 'Page with privacy policy for the application form.', 'recruiting-playbook' ),
			'selectPage'            => __( '— Select Page —', 'recruiting-playbook' ),
			'jobListings'           => __( 'Job Listings', 'recruiting-playbook' ),
			'jobListingsDesc'       => __( 'Settings for job listings and careers page', 'recruiting-playbook' ),
			'jobsPerPage'           => __( 'Jobs Per Page', 'recruiting-playbook' ),
			'urlSlug'               => __( 'URL Slug', 'recruiting-playbook' ),
			'urlSlugDesc'           => __( 'URL path for the jobs overview.', 'recruiting-playbook' ),
			'googleForJobs'         => __( 'Google for Jobs Schema', 'recruiting-playbook' ),
			'googleForJobsDesc'     => __( 'JSON-LD schema for better visibility in Google', 'recruiting-playbook' ),

			// Company Settings.
			'companyData'           => __( 'Company Data', 'recruiting-playbook' ),
			'companyDataDesc'       => __( 'This data is used in email signatures and the Google for Jobs schema.', 'recruiting-playbook' ),
			'companyName'           => __( 'Company Name', 'recruiting-playbook' ),
			'companyNameDesc'       => __( 'Displayed in schema, emails, and on the careers page.', 'recruiting-playbook' ),
			'street'                => __( 'Street & Number', 'recruiting-playbook' ),
			'zip'                   => __( 'Postal Code', 'recruiting-playbook' ),
			'city'                  => __( 'City', 'recruiting-playbook' ),
			'phone'                 => __( 'Phone', 'recruiting-playbook' ),
			'website'               => __( 'Website', 'recruiting-playbook' ),
			'contactEmail'          => __( 'Contact Email', 'recruiting-playbook' ),
			'contactEmailDesc'      => __( 'General contact email of the company (for email signatures).', 'recruiting-playbook' ),
			'defaultSender'         => __( 'Default Sender', 'recruiting-playbook' ),
			'defaultSenderDesc'     => __( 'Default sender data for automatic and manual emails.', 'recruiting-playbook' ),
			'senderName'            => __( 'Sender Name', 'recruiting-playbook' ),
			'senderNameDesc'        => __( 'Name displayed as sender in emails.', 'recruiting-playbook' ),
			'senderEmail'           => __( 'Sender Email', 'recruiting-playbook' ),
			'senderEmailDesc'       => __( 'Email address from which emails are sent.', 'recruiting-playbook' ),
			'hrDepartment'          => __( 'HR Department', 'recruiting-playbook' ),

			// Export Settings.
			'fullBackup'            => __( 'Full Backup', 'recruiting-playbook' ),
			'fullBackupDesc'        => __( 'Exports all plugin data as JSON file', 'recruiting-playbook' ),
			'exportIncludes'        => __( 'The export includes:', 'recruiting-playbook' ),
			'settingsExport'        => __( 'Settings', 'recruiting-playbook' ),
			'jobsExport'            => __( 'Jobs (incl. metadata)', 'recruiting-playbook' ),
			'taxonomiesExport'      => __( 'Taxonomies (categories, locations, etc.)', 'recruiting-playbook' ),
			'candidatesExport'      => __( 'Candidates', 'recruiting-playbook' ),
			'applicationsExport'    => __( 'Applications', 'recruiting-playbook' ),
			'documentsExport'       => __( 'Document metadata', 'recruiting-playbook' ),
			'activityLogExport'     => __( 'Activity log (last 1000 entries)', 'recruiting-playbook' ),
			'note'                  => __( 'Note:', 'recruiting-playbook' ),
			'documentsNotIncluded'  => __( 'Uploaded documents (PDFs etc.) are not exported for privacy reasons.', 'recruiting-playbook' ),
			'downloadBackup'        => __( 'Download Backup', 'recruiting-playbook' ),
			'downloadStarted'       => __( 'Download has been started.', 'recruiting-playbook' ),
			'preparing'             => __( 'Preparing...', 'recruiting-playbook' ),

			// Pro Settings.
			'proSettings'           => __( 'Pro Settings', 'recruiting-playbook' ),
			'proSettingsDesc'       => __( 'Advanced settings for Pro users.', 'recruiting-playbook' ),
			'whiteLabel'            => __( 'White-Label Emails', 'recruiting-playbook' ),
			'whiteLabelDesc'        => __( 'Hide "Sent via Recruiting Playbook" notice in emails', 'recruiting-playbook' ),
			'disableAiFeatures'     => __( 'Disable AI Features', 'recruiting-playbook' ),
			'disableAiFeaturesDesc' => __( 'Hide AI matching buttons in job listings and cards', 'recruiting-playbook' ),

			// Common.
			'saveSettings'          => __( 'Save Settings', 'recruiting-playbook' ),
			'saving'                => __( 'Saving...', 'recruiting-playbook' ),
			'settingsSaved'         => __( 'Settings have been saved.', 'recruiting-playbook' ),
			'errorLoading'          => __( 'Error loading settings', 'recruiting-playbook' ),
			'errorSaving'           => __( 'Error saving', 'recruiting-playbook' ),
		];
	}

	/**
	 * SMTP-Hinweis rendern
	 */
	private function renderSmtpNotice(): void {
		$smtp_status = EmailService::checkSmtpConfig();

		$class = $smtp_status['configured'] ? 'notice-info' : 'notice-warning';
		?>
		<div class="notice <?php echo esc_attr( $class ); ?>" style="padding: 12px;">
			<p>
				<strong><?php esc_html_e( 'Email Configuration:', 'recruiting-playbook' ); ?></strong>
				<?php echo esc_html( $smtp_status['message'] ); ?>
			</p>
			<?php if ( ! $smtp_status['configured'] ) : ?>
				<p>
					<?php
					printf(
						/* translators: %s: link to WordPress.org plugins */
						esc_html__( 'Recommended SMTP plugins: %s', 'recruiting-playbook' ),
						'<a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank">WP Mail SMTP</a>, <a href="https://wordpress.org/plugins/post-smtp/" target="_blank">Post SMTP</a>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Allgemeine Sektion
	 */
	public function renderGeneralSection(): void {
		echo '<p>' . esc_html__( 'Basic settings for the Recruiting Playbook plugin.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * Jobs Sektion
	 */
	public function renderJobsSection(): void {
		echo '<p>' . esc_html__( 'Settings for job listings and careers page.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * Firmendaten Sektion
	 */
	public function renderCompanySection(): void {
		echo '<p>' . esc_html__( 'This data is used in email signatures and the Google for Jobs schema.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * Absender Sektion
	 */
	public function renderSenderSection(): void {
		echo '<p>' . esc_html__( 'Default sender data for automatic and manual emails.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * Pro-Features Sektion
	 */
	public function renderProSection(): void {
		echo '<p>' . esc_html__( 'Advanced settings for Pro users.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * Textfeld rendern
	 *
	 * @param array $args Argumente.
	 */
	public function renderTextField( array $args ): void {
		$settings    = get_option( self::OPTION_NAME, $this->getDefaults() );
		$id          = $args['label_for'];
		$value       = $settings[ $id ] ?? '';
		$required    = ! empty( $args['required'] );
		$placeholder = $args['placeholder'] ?? '';
		?>
		<input
			type="text"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			<?php if ( $placeholder ) : ?>
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
			<?php endif; ?>
			<?php if ( $required ) : ?>
				required
			<?php endif; ?>
		>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * E-Mail-Feld rendern
	 *
	 * @param array $args Argumente.
	 */
	public function renderEmailField( array $args ): void {
		$settings    = get_option( self::OPTION_NAME, $this->getDefaults() );
		$id          = $args['label_for'];
		$value       = $settings[ $id ] ?? '';
		$required    = ! empty( $args['required'] );
		$placeholder = $args['placeholder'] ?? '';
		?>
		<input
			type="email"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			<?php if ( $placeholder ) : ?>
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
			<?php endif; ?>
			<?php if ( $required ) : ?>
				required
			<?php endif; ?>
		>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * URL-Feld rendern
	 *
	 * @param array $args Argumente.
	 */
	public function renderUrlField( array $args ): void {
		$settings    = get_option( self::OPTION_NAME, $this->getDefaults() );
		$id          = $args['label_for'];
		$value       = $settings[ $id ] ?? '';
		$placeholder = $args['placeholder'] ?? '';
		?>
		<input
			type="url"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			<?php if ( $placeholder ) : ?>
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
			<?php endif; ?>
		>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * PLZ & Stadt Feld rendern (zwei Felder in einer Zeile)
	 *
	 * @param array $args Argumente.
	 */
	public function renderZipCityField( array $args ): void {
		$settings = get_option( self::OPTION_NAME, $this->getDefaults() );
		$zip      = $settings['company_zip'] ?? '';
		$city     = $settings['company_city'] ?? '';
		?>
		<input
			type="text"
			id="company_zip"
			name="<?php echo esc_attr( self::OPTION_NAME . '[company_zip]' ); ?>"
			value="<?php echo esc_attr( $zip ); ?>"
			class="small-text"
			placeholder="<?php esc_attr_e( 'Postal Code', 'recruiting-playbook' ); ?>"
			style="width: 80px; margin-right: 8px;"
		>
		<input
			type="text"
			id="company_city"
			name="<?php echo esc_attr( self::OPTION_NAME . '[company_city]' ); ?>"
			value="<?php echo esc_attr( $city ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'City', 'recruiting-playbook' ); ?>"
			style="width: 200px;"
		>
		<?php
	}

	/**
	 * Zahlenfeld rendern
	 *
	 * @param array $args Argumente.
	 */
	public function renderNumberField( array $args ): void {
		$settings = get_option( self::OPTION_NAME, $this->getDefaults() );
		$id = $args['label_for'];
		$value = $settings[ $id ] ?? 10;
		?>
		<input
			type="number"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			min="<?php echo esc_attr( $args['min'] ?? 1 ); ?>"
			max="<?php echo esc_attr( $args['max'] ?? 100 ); ?>"
			class="small-text"
		>
		<?php
	}

	/**
	 * Slug-Feld rendern
	 *
	 * @param array $args Argumente.
	 */
	public function renderSlugField( array $args ): void {
		$settings = get_option( self::OPTION_NAME, $this->getDefaults() );
		$id = $args['label_for'];
		$value = $settings[ $id ] ?? 'jobs';
		?>
		<code><?php echo esc_html( home_url( '/' ) ); ?></code>
		<input
			type="text"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			pattern="[a-z0-9-]+"
		>
		<code>/</code>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Checkbox rendern
	 *
	 * @param array $args Argumente.
	 */
	public function renderCheckboxField( array $args ): void {
		$settings = get_option( self::OPTION_NAME, $this->getDefaults() );
		$id = $args['label_for'];
		$checked = ! empty( $settings[ $id ] );
		?>
		<label>
			<input
				type="checkbox"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( self::OPTION_NAME . '[' . $id . ']' ); ?>"
				value="1"
				<?php checked( $checked ); ?>
			>
			<?php echo esc_html( $args['label'] ?? '' ); ?>
		</label>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Seiten-Auswahl rendern
	 *
	 * @param array $args Argumente.
	 */
	public function renderPageSelectField( array $args ): void {
		$settings = get_option( self::OPTION_NAME, $this->getDefaults() );
		$id = $args['label_for'];
		$value = $settings[ $id ] ?? '';

		wp_dropdown_pages( [
			'name'             => esc_attr( self::OPTION_NAME . '[' . $id . ']' ),
			'id'               => esc_attr( $id ),
			'selected'         => absint( url_to_postid( $value ) ),
			'show_option_none' => esc_html__( '— Select Page —', 'recruiting-playbook' ),
		] );

		if ( ! empty( $args['description'] ) ) :
			?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
			<?php
		endif;
	}

	/**
	 * Recruiting-User für Stellen-Zuweisung laden
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getRecruitingUsers(): array {
		$users = RoleManager::getRecruitingUsers();
		$result = [];

		foreach ( $users as $user ) {
			$role_label = 'Recruiter';
			if ( in_array( 'rp_hiring_manager', (array) $user->roles, true ) ) {
				$role_label = 'Hiring Manager';
			} elseif ( in_array( 'administrator', (array) $user->roles, true ) ) {
				$role_label = 'Administrator';
			}

			$result[] = [
				'id'   => $user->ID,
				'name' => $user->display_name,
				'role' => $role_label,
			];
		}

		return $result;
	}

	/**
	 * Alle Job-Listings für Stellen-Zuweisung laden
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getJobListings(): array {
		$posts = get_posts( [
			'post_type'      => 'job_listing',
			'posts_per_page' => -1,
			'post_status'    => [ 'publish', 'draft' ],
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$jobs = [];
		foreach ( $posts as $post ) {
			$jobs[] = [
				'id'     => $post->ID,
				'title'  => $post->post_title,
				'status' => $post->post_status,
			];
		}

		return $jobs;
	}
}

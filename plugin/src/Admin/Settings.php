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
			__( 'Allgemeine Einstellungen', 'recruiting-playbook' ),
			[ $this, 'renderGeneralSection' ],
			'rp-settings'
		);

		// Feld: Benachrichtigungs-E-Mail.
		add_settings_field(
			'notification_email',
			__( 'Benachrichtigungs-E-Mail', 'recruiting-playbook' ),
			[ $this, 'renderEmailField' ],
			'rp-settings',
			'rp_general_section',
			[
				'label_for'   => 'notification_email',
				'description' => __( 'E-Mail-Adresse für neue Bewerbungen.', 'recruiting-playbook' ),
			]
		);

		// Feld: Datenschutz-URL.
		add_settings_field(
			'privacy_url',
			__( 'Datenschutz-Seite', 'recruiting-playbook' ),
			[ $this, 'renderPageSelectField' ],
			'rp-settings',
			'rp_general_section',
			[
				'label_for'   => 'privacy_url',
				'description' => __( 'Seite mit Datenschutzerklärung für das Bewerbungsformular.', 'recruiting-playbook' ),
			]
		);

		// Sektion: Firmendaten.
		add_settings_section(
			'rp_company_section',
			__( 'Firmendaten', 'recruiting-playbook' ),
			[ $this, 'renderCompanySection' ],
			'rp-settings'
		);

		// Feld: Firmenname.
		add_settings_field(
			'company_name',
			__( 'Firmenname', 'recruiting-playbook' ) . ' *',
			[ $this, 'renderTextField' ],
			'rp-settings',
			'rp_company_section',
			[
				'label_for'   => 'company_name',
				'description' => __( 'Wird im Schema, E-Mails und auf der Karriereseite angezeigt.', 'recruiting-playbook' ),
				'required'    => true,
			]
		);

		// Feld: Straße.
		add_settings_field(
			'company_street',
			__( 'Straße & Hausnummer', 'recruiting-playbook' ),
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
			__( 'PLZ & Stadt', 'recruiting-playbook' ),
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
			__( 'Telefon', 'recruiting-playbook' ),
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
			__( 'Kontakt-E-Mail', 'recruiting-playbook' ) . ' *',
			[ $this, 'renderEmailField' ],
			'rp-settings',
			'rp_company_section',
			[
				'label_for'   => 'company_email',
				'description' => __( 'Allgemeine Kontakt-E-Mail der Firma (für E-Mail-Signaturen).', 'recruiting-playbook' ),
				'required'    => true,
			]
		);

		// Sektion: E-Mail-Absender.
		add_settings_section(
			'rp_sender_section',
			__( 'Standard-Absender', 'recruiting-playbook' ),
			[ $this, 'renderSenderSection' ],
			'rp-settings'
		);

		// Feld: Absender-Name.
		add_settings_field(
			'sender_name',
			__( 'Absender-Name', 'recruiting-playbook' ),
			[ $this, 'renderTextField' ],
			'rp-settings',
			'rp_sender_section',
			[
				'label_for'   => 'sender_name',
				'description' => __( 'Name, der als Absender in E-Mails angezeigt wird.', 'recruiting-playbook' ),
				'placeholder' => __( 'Personalabteilung', 'recruiting-playbook' ),
			]
		);

		// Feld: Absender-E-Mail.
		add_settings_field(
			'sender_email',
			__( 'Absender-E-Mail', 'recruiting-playbook' ),
			[ $this, 'renderEmailField' ],
			'rp-settings',
			'rp_sender_section',
			[
				'label_for'   => 'sender_email',
				'description' => __( 'E-Mail-Adresse, von der E-Mails gesendet werden.', 'recruiting-playbook' ),
				'placeholder' => 'jobs@example.com',
			]
		);

		// Sektion: Stellenanzeigen.
		add_settings_section(
			'rp_jobs_section',
			__( 'Stellenanzeigen', 'recruiting-playbook' ),
			[ $this, 'renderJobsSection' ],
			'rp-settings'
		);

		// Feld: Stellen pro Seite.
		add_settings_field(
			'jobs_per_page',
			__( 'Stellen pro Seite', 'recruiting-playbook' ),
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
			__( 'URL-Slug', 'recruiting-playbook' ),
			[ $this, 'renderSlugField' ],
			'rp-settings',
			'rp_jobs_section',
			[
				'label_for'   => 'jobs_slug',
				'description' => __( 'URL-Pfad für die Stellenübersicht (z.B. "jobs" für /jobs/).', 'recruiting-playbook' ),
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
				'label'       => __( 'JSON-LD Schema für Google for Jobs aktivieren', 'recruiting-playbook' ),
				'description' => __( 'Strukturierte Daten für bessere Sichtbarkeit in Google.', 'recruiting-playbook' ),
			]
		);

		// Sektion: E-Mail.
		add_settings_section(
			'rp_email_section',
			__( 'E-Mail-Einstellungen', 'recruiting-playbook' ),
			[ $this, 'renderEmailSection' ],
			'rp-settings'
		);

		// Feld: Automatische Absage-E-Mails.
		add_settings_field(
			'auto_rejection_email',
			__( 'Automatische Absagen', 'recruiting-playbook' ),
			[ $this, 'renderCheckboxField' ],
			'rp-settings',
			'rp_email_section',
			[
				'label_for'   => 'auto_rejection_email',
				'label'       => __( 'Automatische Absage-E-Mail bei Status "Abgelehnt"', 'recruiting-playbook' ),
				'description' => __( 'Bewerber erhalten automatisch eine E-Mail, wenn ihr Status auf "Abgelehnt" geändert wird.', 'recruiting-playbook' ),
			]
		);
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
			'sender_name'          => __( 'Personalabteilung', 'recruiting-playbook' ),
			'sender_email'         => get_option( 'admin_email' ),

			// Stellenanzeigen.
			'jobs_per_page'        => 10,
			'jobs_slug'            => 'jobs',
			'enable_schema'        => true,

			// E-Mail.
			'auto_rejection_email' => false,
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

		// E-Mail.
		$output['auto_rejection_email'] = ! empty( $input['auto_rejection_email'] );

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

		$settings = get_option( self::OPTION_NAME, $this->getDefaults() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Recruiting Playbook Einstellungen', 'recruiting-playbook' ); ?></h1>

			<?php $this->renderSmtpNotice(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'rp_settings_group' );
				do_settings_sections( 'rp-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
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
				<strong><?php esc_html_e( 'E-Mail-Konfiguration:', 'recruiting-playbook' ); ?></strong>
				<?php echo esc_html( $smtp_status['message'] ); ?>
			</p>
			<?php if ( ! $smtp_status['configured'] ) : ?>
				<p>
					<?php
					printf(
						/* translators: %s: link to WordPress.org plugins */
						esc_html__( 'Empfohlene SMTP-Plugins: %s', 'recruiting-playbook' ),
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
		echo '<p>' . esc_html__( 'Grundlegende Einstellungen für das Recruiting Playbook Plugin.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * Jobs Sektion
	 */
	public function renderJobsSection(): void {
		echo '<p>' . esc_html__( 'Einstellungen für Stellenanzeigen und die Karriereseite.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * E-Mail Sektion
	 */
	public function renderEmailSection(): void {
		echo '<p>' . esc_html__( 'Einstellungen für E-Mail-Benachrichtigungen.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * Firmendaten Sektion
	 */
	public function renderCompanySection(): void {
		echo '<p>' . esc_html__( 'Diese Daten werden in E-Mail-Signaturen und im Google for Jobs Schema verwendet.', 'recruiting-playbook' ) . '</p>';
	}

	/**
	 * Absender Sektion
	 */
	public function renderSenderSection(): void {
		echo '<p>' . esc_html__( 'Standard-Absenderdaten für automatische und manuelle E-Mails.', 'recruiting-playbook' ) . '</p>';
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
			placeholder="<?php esc_attr_e( 'PLZ', 'recruiting-playbook' ); ?>"
			style="width: 80px; margin-right: 8px;"
		>
		<input
			type="text"
			id="company_city"
			name="<?php echo esc_attr( self::OPTION_NAME . '[company_city]' ); ?>"
			value="<?php echo esc_attr( $city ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Stadt', 'recruiting-playbook' ); ?>"
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
			'show_option_none' => esc_html__( '— Seite auswählen —', 'recruiting-playbook' ),
		] );

		if ( ! empty( $args['description'] ) ) :
			?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
			<?php
		endif;
	}
}

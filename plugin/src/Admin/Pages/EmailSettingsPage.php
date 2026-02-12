<?php
/**
 * Email Settings Page - Admin-Seite für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\EmailTemplateService;
use RecruitingPlaybook\Services\PlaceholderService;
use RecruitingPlaybook\Services\AutoEmailService;
use RecruitingPlaybook\Repositories\EmailTemplateRepository;

/**
 * Admin-Seite für E-Mail-Templates
 */
class EmailSettingsPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'registerSubmenu' ] );
		add_action( 'admin_init', [ $this, 'handleActions' ] );
	}

	/**
	 * Submenu registrieren
	 */
	public function registerSubmenu(): void {
		add_submenu_page(
			'recruiting-playbook',
			__( 'Email Templates', 'recruiting-playbook' ),
			$this->getMenuLabel(),
			'manage_options',
			'rp-email-templates',
			[ $this, 'render' ]
		);
	}

	/**
	 * Menü-Label mit Lock-Icon für Free-User
	 *
	 * @return string Menü-Label.
	 */
	private function getMenuLabel(): string {
		$label = __( 'Email Templates', 'recruiting-playbook' );

		// Lock-Icon für Free-User.
		if ( ! function_exists( 'rp_can' ) || ! rp_can( 'email_templates' ) ) {
			$label .= ' <span class="dashicons dashicons-lock" style="font-size: 12px; width: 12px; height: 12px; vertical-align: middle; opacity: 0.7;"></span>';
		}

		return $label;
	}

	/**
	 * Formular-Aktionen verarbeiten
	 */
	public function handleActions(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'rp-email-templates' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Template speichern.
		if ( isset( $_POST['rp_save_template'] ) ) {
			check_admin_referer( 'rp_save_template' );
			$this->saveTemplate();
		}

		// Template löschen.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['template_id'] ) ) {
			$template_id = absint( $_GET['template_id'] );
			check_admin_referer( 'rp_delete_template_' . $template_id );
			$this->deleteTemplate( $template_id );
		}

		// Auto-E-Mail-Einstellungen speichern.
		if ( isset( $_POST['rp_save_auto_email'] ) ) {
			check_admin_referer( 'rp_save_auto_email' );
			$this->saveAutoEmailSettings();
		}
	}

	/**
	 * Template speichern
	 */
	private function saveTemplate(): void {
		$repository = new EmailTemplateRepository();
		$service    = new EmailTemplateService( $repository, new PlaceholderService() );

		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;

		$data = [
			'name'      => isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '',
			'slug'      => isset( $_POST['template_slug'] ) ? sanitize_title( wp_unslash( $_POST['template_slug'] ) ) : '',
			'subject'   => isset( $_POST['template_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['template_subject'] ) ) : '',
			'body_html' => isset( $_POST['template_body'] ) ? wp_kses_post( wp_unslash( $_POST['template_body'] ) ) : '',
			'category'  => isset( $_POST['template_category'] ) ? sanitize_text_field( wp_unslash( $_POST['template_category'] ) ) : 'custom',
			'is_active' => isset( $_POST['template_active'] ) ? 1 : 0,
		];

		if ( $template_id > 0 ) {
			$service->update( $template_id, $data );
			$message = 'updated';
		} else {
			$service->create( $data );
			$message = 'created';
		}

		wp_safe_redirect( admin_url( 'admin.php?page=rp-email-templates&message=' . $message ) );
		exit;
	}

	/**
	 * Template löschen
	 *
	 * @param int $template_id Template ID.
	 */
	private function deleteTemplate( int $template_id ): void {
		$repository = new EmailTemplateRepository();
		$service    = new EmailTemplateService( $repository, new PlaceholderService() );

		$service->delete( $template_id );

		wp_safe_redirect( admin_url( 'admin.php?page=rp-email-templates&message=deleted' ) );
		exit;
	}

	/**
	 * Seite rendern
	 */
	public function render(): void {
		// Pro-Feature-Check.
		$is_pro = function_exists( 'rp_can' ) && rp_can( 'email_templates' );

		if ( ! $is_pro ) {
			$this->renderUpgradeNotice();
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'templates';

		echo '<div class="wrap rp-email-page">';

		// Erfolgsmeldungen.
		$this->renderMessages();

		// Auto-E-Mail Tab (legacy PHP) vs. React App.
		if ( 'auto-email' === $tab && 'list' === $action ) {
			// WordPress-Standard-Header für Legacy-Seiten.
			echo '<h1 class="wp-heading-inline">' . esc_html__( 'Email Templates & Signatures', 'recruiting-playbook' ) . '</h1>';
			echo '<hr class="wp-header-end">';
			$this->renderTabs( $tab );
			$this->renderAutoEmailSettings();
		} elseif ( 'new' === $action || 'edit' === $action ) {
			// WordPress-Standard-Header für Legacy-Formulare.
			echo '<h1 class="wp-heading-inline">' . esc_html__( 'Email Templates & Signatures', 'recruiting-playbook' ) . '</h1>';
			echo '<hr class="wp-header-end">';
			$this->renderForm( $template_id );
		} else {
			// React App für Templates & Signaturen - keine PHP-Überschrift (React App hat eigene).
			$this->renderList();
		}

		echo '</div>';
	}

	/**
	 * Tabs rendern
	 *
	 * @param string $current_tab Aktueller Tab.
	 */
	private function renderTabs( string $current_tab ): void {
		$tabs = [
			'templates'  => __( 'Templates', 'recruiting-playbook' ),
			'auto-email' => __( 'Automatic Emails', 'recruiting-playbook' ),
		];

		echo '<nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">';

		foreach ( $tabs as $tab_id => $tab_label ) {
			$url   = admin_url( 'admin.php?page=rp-email-templates&tab=' . $tab_id );
			$class = ( $tab_id === $current_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';

			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( $url ),
				esc_attr( $class ),
				esc_html( $tab_label )
			);
		}

		echo '</nav>';
	}

	/**
	 * Erfolgsmeldungen rendern
	 */
	private function renderMessages(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['message'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message = sanitize_text_field( wp_unslash( $_GET['message'] ) );

		$messages = [
			'created'        => __( 'Template created.', 'recruiting-playbook' ),
			'updated'        => __( 'Template updated.', 'recruiting-playbook' ),
			'deleted'        => __( 'Template deleted.', 'recruiting-playbook' ),
			'settings_saved' => __( 'Settings saved.', 'recruiting-playbook' ),
		];

		if ( isset( $messages[ $message ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $message ] ) . '</p></div>';
		}
	}

	/**
	 * Template-Liste rendern (React App)
	 */
	private function renderList(): void {
		// React App Container - die React App übernimmt Templates und Signaturen.
		?>
		<div id="rp-email-templates-app" style="margin-top: 20px;">
			<div style="display: flex; justify-content: center; align-items: center; padding: 60px;">
				<span class="spinner is-active" style="float: none;"></span>
				<span style="margin-left: 10px;"><?php esc_html_e( 'Loading...', 'recruiting-playbook' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Template-Formular rendern
	 *
	 * @param int $template_id Template ID (0 für neu).
	 */
	private function renderForm( int $template_id ): void {
		$template = null;

		if ( $template_id > 0 ) {
			$repository = new EmailTemplateRepository();
			$template   = $repository->find( $template_id );
		}

		$is_system = $template && ! empty( $template['is_system'] );
		$is_new    = empty( $template );

		$categories = [
			'application' => __( 'Application', 'recruiting-playbook' ),
			'interview'   => __( 'Interview', 'recruiting-playbook' ),
			'offer'       => __( 'Offer', 'recruiting-playbook' ),
			'rejection'   => __( 'Rejection', 'recruiting-playbook' ),
			'custom'      => __( 'Custom', 'recruiting-playbook' ),
		];

		?>
		<div style="max-width: 900px;">
		<form method="post" action="">
			<?php wp_nonce_field( 'rp_save_template' ); ?>
			<input type="hidden" name="template_id" value="<?php echo esc_attr( $template_id ); ?>">

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="template_name"><?php esc_html_e( 'Name', 'recruiting-playbook' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<input type="text" name="template_name" id="template_name" class="regular-text"
							value="<?php echo esc_attr( $template['name'] ?? '' ); ?>"
							<?php echo $is_system ? 'readonly' : 'required'; ?>>
						<?php if ( $is_system ) : ?>
							<p class="description"><?php esc_html_e( 'System templates cannot be renamed.', 'recruiting-playbook' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>

				<?php if ( ! $is_system ) : ?>
				<tr>
					<th scope="row">
						<label for="template_slug"><?php esc_html_e( 'Slug', 'recruiting-playbook' ); ?></label>
					</th>
					<td>
						<input type="text" name="template_slug" id="template_slug" class="regular-text"
							value="<?php echo esc_attr( $template['slug'] ?? '' ); ?>">
						<p class="description"><?php esc_html_e( 'Unique identifier (optional, will be auto-generated).', 'recruiting-playbook' ); ?></p>
					</td>
				</tr>
				<?php endif; ?>

				<tr>
					<th scope="row">
						<label for="template_category"><?php esc_html_e( 'Category', 'recruiting-playbook' ); ?></label>
					</th>
					<td>
						<select name="template_category" id="template_category" <?php echo $is_system ? 'disabled' : ''; ?>>
							<?php foreach ( $categories as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $template['category'] ?? 'custom', $key ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="template_subject"><?php esc_html_e( 'Subject', 'recruiting-playbook' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<input type="text" name="template_subject" id="template_subject" class="large-text"
							value="<?php echo esc_attr( $template['subject'] ?? '' ); ?>" required>
						<p class="description"><?php esc_html_e( 'Placeholders like {first_name} or {job_title} can be used.', 'recruiting-playbook' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="template_body"><?php esc_html_e( 'Content (HTML)', 'recruiting-playbook' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<?php
						wp_editor(
							$template['body_html'] ?? '',
							'template_body',
							[
								'media_buttons' => false,
								'textarea_rows' => 15,
								'teeny'         => false,
							]
						);
						?>
						<p class="description"><?php esc_html_e( 'HTML content of the email. Placeholders will be automatically replaced.', 'recruiting-playbook' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'recruiting-playbook' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="template_active" value="1" <?php checked( $template['is_active'] ?? true ); ?>>
							<?php esc_html_e( 'Template is active', 'recruiting-playbook' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="rp_save_template" class="button button-primary" value="<?php esc_attr_e( 'Save Template', 'recruiting-playbook' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'recruiting-playbook' ); ?></a>
			</p>
		</form>

		<div class="card" style="max-width: 400px; margin-top: 20px; padding: 15px;">
			<h3><?php esc_html_e( 'Insert Placeholders', 'recruiting-playbook' ); ?></h3>
			<?php $this->renderPlaceholderList(); ?>
		</div>
		</div><!-- .max-width container -->
		<?php
	}

	/**
	 * Platzhalter-Liste rendern
	 */
	private function renderPlaceholderList(): void {
		$placeholder_service = new PlaceholderService();
		$placeholders        = $placeholder_service->getAvailablePlaceholders();

		echo '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 12px;">';

		foreach ( $placeholders as $key => $info ) {
			$code = '<code style="cursor: pointer; background: #f0f0f0; padding: 2px 5px;" onclick="navigator.clipboard.writeText(\'{' . esc_attr( $key ) . '}\').then(() => alert(\'Copied!\'))" title="' . esc_attr__( 'Click to copy', 'recruiting-playbook' ) . '">{' . esc_html( $key ) . '}</code>';
			echo '<div>' . $code . ' <small style="color: #666;">' . esc_html( $info['label'] ?? $key ) . '</small></div>';
		}

		echo '</div>';
	}

	/**
	 * Upgrade-Hinweis für Free-Version
	 */
	private function renderUpgradeNotice(): void {
		$upgrade_url = function_exists( 'rp_upgrade_url' ) ? rp_upgrade_url( 'PRO' ) : '#';

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Email Templates', 'recruiting-playbook' ) . '</h1>';
		echo '<div class="notice notice-info">';
		echo '<p>';
		echo esc_html__( 'Email Templates are a Pro feature. Upgrade to create and manage custom email templates.', 'recruiting-playbook' );
		echo '</p>';
		echo '<p>';
		echo '<a href="' . esc_url( $upgrade_url ) . '" class="button button-primary">';
		echo esc_html__( 'Upgrade to Pro', 'recruiting-playbook' );
		echo '</a>';
		echo '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Auto-E-Mail Einstellungen rendern
	 */
	private function renderAutoEmailSettings(): void {
		$auto_email_service = new AutoEmailService();
		$settings           = $auto_email_service->getSettings();
		$statuses           = AutoEmailService::getAvailableStatuses();

		// Templates laden.
		$repository = new EmailTemplateRepository();
		$templates  = $repository->getList( [ 'include_inactive' => false ] );

		?>
		<div class="card" style="max-width: 900px; padding: 20px;">
			<h2><?php esc_html_e( 'Automatic Emails on Status Changes', 'recruiting-playbook' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure which emails are sent automatically when an application status changes.', 'recruiting-playbook' ); ?>
			</p>

			<form method="post">
				<?php wp_nonce_field( 'rp_save_auto_email' ); ?>

				<table class="widefat striped" style="margin-top: 20px;">
					<thead>
						<tr>
							<th style="width: 5%;"><?php esc_html_e( 'Active', 'recruiting-playbook' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'On Status', 'recruiting-playbook' ); ?></th>
							<th style="width: 50%;"><?php esc_html_e( 'Email Template', 'recruiting-playbook' ); ?></th>
							<th style="width: 25%;"><?php esc_html_e( 'Delay', 'recruiting-playbook' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $statuses as $status_key => $status_label ) : ?>
							<?php
							$status_settings = $settings[ $status_key ] ?? [];
							$is_enabled      = ! empty( $status_settings['enabled'] );
							$template_id     = (int) ( $status_settings['template_id'] ?? 0 );
							$delay           = (int) ( $status_settings['delay'] ?? 0 );
							?>
							<tr>
								<td>
									<input type="checkbox"
									       name="auto_email[<?php echo esc_attr( $status_key ); ?>][enabled]"
									       value="1"
									       <?php checked( $is_enabled ); ?>>
								</td>
								<td>
									<strong><?php echo esc_html( $status_label ); ?></strong>
									<p class="description" style="margin: 0;">
										<?php
										printf(
											/* translators: %s: status name */
											esc_html__( 'When status is set to "%s"', 'recruiting-playbook' ),
											$status_label
										);
										?>
									</p>
								</td>
								<td>
									<select name="auto_email[<?php echo esc_attr( $status_key ); ?>][template_id]" style="width: 100%;">
										<option value=""><?php esc_html_e( '— No Template —', 'recruiting-playbook' ); ?></option>
										<?php foreach ( $templates as $template ) : ?>
											<option value="<?php echo esc_attr( $template['id'] ); ?>" <?php selected( $template_id, (int) $template['id'] ); ?>>
												<?php echo esc_html( $template['name'] ); ?>
												(<?php echo esc_html( $template['subject'] ?? '' ); ?>)
											</option>
										<?php endforeach; ?>
									</select>
								</td>
								<td>
									<select name="auto_email[<?php echo esc_attr( $status_key ); ?>][delay]" style="width: 100%;">
										<option value="0" <?php selected( $delay, 0 ); ?>><?php esc_html_e( 'Immediately', 'recruiting-playbook' ); ?></option>
										<option value="5" <?php selected( $delay, 5 ); ?>><?php esc_html_e( '5 minutes', 'recruiting-playbook' ); ?></option>
										<option value="15" <?php selected( $delay, 15 ); ?>><?php esc_html_e( '15 minutes', 'recruiting-playbook' ); ?></option>
										<option value="30" <?php selected( $delay, 30 ); ?>><?php esc_html_e( '30 minutes', 'recruiting-playbook' ); ?></option>
										<option value="60" <?php selected( $delay, 60 ); ?>><?php esc_html_e( '1 hour', 'recruiting-playbook' ); ?></option>
										<option value="120" <?php selected( $delay, 120 ); ?>><?php esc_html_e( '2 hours', 'recruiting-playbook' ); ?></option>
										<option value="1440" <?php selected( $delay, 1440 ); ?>><?php esc_html_e( '24 hours', 'recruiting-playbook' ); ?></option>
									</select>
									<p class="description" style="margin: 0; font-size: 11px;">
										<?php esc_html_e( 'Wait time before sending', 'recruiting-playbook' ); ?>
									</p>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div class="notice notice-info inline" style="margin-top: 20px;">
					<p>
						<strong><?php esc_html_e( 'Tip:', 'recruiting-playbook' ); ?></strong>
						<?php esc_html_e( 'A delay gives you time to correct accidental status changes before the email is sent.', 'recruiting-playbook' ); ?>
					</p>
				</div>

				<p class="submit">
					<button type="submit" name="rp_save_auto_email" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'recruiting-playbook' ); ?>
					</button>
				</p>
			</form>
		</div>

		<?php if ( empty( $templates ) ) : ?>
			<div class="notice notice-warning inline" style="margin-top: 20px; max-width: 900px;">
				<p>
					<?php esc_html_e( 'No email templates available.', 'recruiting-playbook' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates&action=new' ) ); ?>">
						<?php esc_html_e( 'Create a template first.', 'recruiting-playbook' ); ?>
					</a>
				</p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Auto-E-Mail Einstellungen speichern
	 */
	private function saveAutoEmailSettings(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Wird in AutoEmailService sanitiert
		$settings = isset( $_POST['auto_email'] ) ? wp_unslash( $_POST['auto_email'] ) : [];

		$auto_email_service = new AutoEmailService();
		$auto_email_service->saveSettings( $settings );

		wp_safe_redirect( admin_url( 'admin.php?page=rp-email-templates&tab=auto-email&message=settings_saved' ) );
		exit;
	}
}

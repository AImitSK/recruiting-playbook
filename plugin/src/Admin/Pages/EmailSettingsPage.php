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
			__( 'E-Mail-Templates', 'recruiting-playbook' ),
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
		$label = __( 'E-Mail-Templates', 'recruiting-playbook' );

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

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'E-Mail-Templates', 'recruiting-playbook' ) . '</h1>';

		if ( 'list' === $action ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=rp-email-templates&action=new' ) ) . '" class="page-title-action">';
			echo esc_html__( 'Neues Template', 'recruiting-playbook' );
			echo '</a>';
		}

		echo '<hr class="wp-header-end">';

		// Erfolgsmeldungen.
		$this->renderMessages();

		switch ( $action ) {
			case 'new':
			case 'edit':
				$this->renderForm( $template_id );
				break;
			default:
				$this->renderList();
		}

		echo '</div>';
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
			'created' => __( 'Template wurde erstellt.', 'recruiting-playbook' ),
			'updated' => __( 'Template wurde aktualisiert.', 'recruiting-playbook' ),
			'deleted' => __( 'Template wurde gelöscht.', 'recruiting-playbook' ),
		];

		if ( isset( $messages[ $message ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $message ] ) . '</p></div>';
		}
	}

	/**
	 * Template-Liste rendern
	 */
	private function renderList(): void {
		$repository = new EmailTemplateRepository();
		$templates  = $repository->getList( [ 'include_inactive' => true ] );

		$categories = [
			'application' => __( 'Bewerbung', 'recruiting-playbook' ),
			'interview'   => __( 'Interview', 'recruiting-playbook' ),
			'offer'       => __( 'Angebot', 'recruiting-playbook' ),
			'rejection'   => __( 'Absage', 'recruiting-playbook' ),
			'custom'      => __( 'Benutzerdefiniert', 'recruiting-playbook' ),
		];

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" style="width: 25%;"><?php esc_html_e( 'Name', 'recruiting-playbook' ); ?></th>
					<th scope="col" style="width: 30%;"><?php esc_html_e( 'Betreff', 'recruiting-playbook' ); ?></th>
					<th scope="col" style="width: 15%;"><?php esc_html_e( 'Kategorie', 'recruiting-playbook' ); ?></th>
					<th scope="col" style="width: 10%;"><?php esc_html_e( 'Typ', 'recruiting-playbook' ); ?></th>
					<th scope="col" style="width: 10%;"><?php esc_html_e( 'Status', 'recruiting-playbook' ); ?></th>
					<th scope="col" style="width: 10%;"><?php esc_html_e( 'Aktionen', 'recruiting-playbook' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $templates ) ) : ?>
					<tr>
						<td colspan="6"><?php esc_html_e( 'Keine Templates gefunden.', 'recruiting-playbook' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $templates as $template ) : ?>
						<?php
						$is_system = ! empty( $template['is_system'] );
						$is_active = ! empty( $template['is_active'] );
						$category  = $template['category'] ?? 'custom';
						?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates&action=edit&template_id=' . $template['id'] ) ); ?>">
										<?php echo esc_html( $template['name'] ); ?>
									</a>
								</strong>
								<?php if ( $is_system ) : ?>
									<span class="dashicons dashicons-lock" title="<?php esc_attr_e( 'System-Template', 'recruiting-playbook' ); ?>" style="color: #999; font-size: 14px;"></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $template['subject'] ?? '' ); ?></td>
							<td><?php echo esc_html( $categories[ $category ] ?? $category ); ?></td>
							<td>
								<?php if ( $is_system ) : ?>
									<span class="dashicons dashicons-admin-site" style="color: #2271b1;"></span>
									<?php esc_html_e( 'System', 'recruiting-playbook' ); ?>
								<?php else : ?>
									<span class="dashicons dashicons-admin-users" style="color: #72aee6;"></span>
									<?php esc_html_e( 'Custom', 'recruiting-playbook' ); ?>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $is_active ) : ?>
									<span style="color: #00a32a;">● <?php esc_html_e( 'Aktiv', 'recruiting-playbook' ); ?></span>
								<?php else : ?>
									<span style="color: #999;">○ <?php esc_html_e( 'Inaktiv', 'recruiting-playbook' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates&action=edit&template_id=' . $template['id'] ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Bearbeiten', 'recruiting-playbook' ); ?>
								</a>
								<?php if ( ! $is_system ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=rp-email-templates&action=delete&template_id=' . $template['id'] ), 'rp_delete_template_' . $template['id'] ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Wirklich löschen?', 'recruiting-playbook' ); ?>');">
										<?php esc_html_e( 'Löschen', 'recruiting-playbook' ); ?>
									</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<div class="card" style="max-width: 600px; margin-top: 20px; padding: 15px;">
			<h3><?php esc_html_e( 'Verfügbare Platzhalter', 'recruiting-playbook' ); ?></h3>
			<p><?php esc_html_e( 'Diese Platzhalter können in E-Mail-Templates verwendet werden:', 'recruiting-playbook' ); ?></p>
			<?php $this->renderPlaceholderList(); ?>
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
			'application' => __( 'Bewerbung', 'recruiting-playbook' ),
			'interview'   => __( 'Interview', 'recruiting-playbook' ),
			'offer'       => __( 'Angebot', 'recruiting-playbook' ),
			'rejection'   => __( 'Absage', 'recruiting-playbook' ),
			'custom'      => __( 'Benutzerdefiniert', 'recruiting-playbook' ),
		];

		?>
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
							<p class="description"><?php esc_html_e( 'System-Templates können nicht umbenannt werden.', 'recruiting-playbook' ); ?></p>
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
						<p class="description"><?php esc_html_e( 'Eindeutiger Bezeichner (optional, wird automatisch generiert).', 'recruiting-playbook' ); ?></p>
					</td>
				</tr>
				<?php endif; ?>

				<tr>
					<th scope="row">
						<label for="template_category"><?php esc_html_e( 'Kategorie', 'recruiting-playbook' ); ?></label>
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
						<label for="template_subject"><?php esc_html_e( 'Betreff', 'recruiting-playbook' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<input type="text" name="template_subject" id="template_subject" class="large-text"
							value="<?php echo esc_attr( $template['subject'] ?? '' ); ?>" required>
						<p class="description"><?php esc_html_e( 'Platzhalter wie {vorname} oder {stelle} können verwendet werden.', 'recruiting-playbook' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="template_body"><?php esc_html_e( 'Inhalt (HTML)', 'recruiting-playbook' ); ?> <span class="required">*</span></label>
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
						<p class="description"><?php esc_html_e( 'HTML-Inhalt der E-Mail. Platzhalter werden automatisch ersetzt.', 'recruiting-playbook' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Status', 'recruiting-playbook' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="template_active" value="1" <?php checked( $template['is_active'] ?? true ); ?>>
							<?php esc_html_e( 'Template ist aktiv', 'recruiting-playbook' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="rp_save_template" class="button button-primary" value="<?php esc_attr_e( 'Template speichern', 'recruiting-playbook' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-email-templates' ) ); ?>" class="button"><?php esc_html_e( 'Abbrechen', 'recruiting-playbook' ); ?></a>
			</p>
		</form>

		<div class="card" style="max-width: 400px; margin-top: 20px; padding: 15px;">
			<h3><?php esc_html_e( 'Platzhalter einfügen', 'recruiting-playbook' ); ?></h3>
			<?php $this->renderPlaceholderList(); ?>
		</div>
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
			$code = '<code style="cursor: pointer; background: #f0f0f0; padding: 2px 5px;" onclick="navigator.clipboard.writeText(\'{' . esc_attr( $key ) . '}\').then(() => alert(\'Kopiert!\'))" title="' . esc_attr__( 'Klicken zum Kopieren', 'recruiting-playbook' ) . '">{' . esc_html( $key ) . '}</code>';
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
		echo '<h1>' . esc_html__( 'E-Mail-Templates', 'recruiting-playbook' ) . '</h1>';
		echo '<div class="notice notice-info">';
		echo '<p>';
		echo esc_html__( 'E-Mail-Templates sind ein Pro-Feature. Upgraden Sie, um benutzerdefinierte E-Mail-Vorlagen zu erstellen und zu verwalten.', 'recruiting-playbook' );
		echo '</p>';
		echo '<p>';
		echo '<a href="' . esc_url( $upgrade_url ) . '" class="button button-primary">';
		echo esc_html__( 'Auf Pro upgraden', 'recruiting-playbook' );
		echo '</a>';
		echo '</p>';
		echo '</div>';
		echo '</div>';
	}
}

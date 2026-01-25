<?php
/**
 * License Admin Page
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Licensing\LicenseManager;

/**
 * License Page Klasse
 */
class LicensePage {

	/**
	 * Render the license page
	 */
	public function render(): void {
		$license_manager = LicenseManager::get_instance();
		$status          = $license_manager->get_status();

		// Enqueue styles.
		$this->enqueue_styles();

		// Handle form submission.
		$this->handle_form_submission();

		?>
		<div class="wrap rp-license-page">
			<h1><?php esc_html_e( 'Lizenz', 'recruiting-playbook' ); ?></h1>

			<?php $this->render_notices(); ?>

			<div class="rp-license-card">
				<?php $this->render_status_section( $status ); ?>
				<?php $this->render_activation_form( $status ); ?>
				<?php $this->render_upgrade_section( $status ); ?>
			</div>

			<?php $this->render_feature_comparison(); ?>
		</div>
		<?php
	}

	/**
	 * Enqueue license page styles
	 */
	private function enqueue_styles(): void {
		wp_enqueue_style(
			'rp-admin-license',
			RP_PLUGIN_URL . 'assets/dist/css/admin-license.css',
			array(),
			RP_VERSION
		);
	}

	/**
	 * Handle form submission
	 */
	private function handle_form_submission(): void {
		if ( ! isset( $_POST['rp_license_action'] ) ) {
			return;
		}

		if ( ! check_admin_referer( 'rp_license_action', 'rp_license_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$license_manager = LicenseManager::get_instance();
		$action          = sanitize_text_field( wp_unslash( $_POST['rp_license_action'] ) );

		if ( 'activate' === $action ) {
			$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

			if ( empty( $license_key ) ) {
				add_settings_error(
					'rp_license',
					'empty_key',
					__( 'Bitte geben Sie einen Lizenzschlüssel ein.', 'recruiting-playbook' ),
					'error'
				);
				return;
			}

			$result = $license_manager->activate( $license_key );

			if ( $result['success'] ) {
				add_settings_error(
					'rp_license',
					'activated',
					$result['message'],
					'success'
				);
			} else {
				add_settings_error(
					'rp_license',
					$result['error'] ?? 'error',
					$result['message'],
					'error'
				);
			}
		} elseif ( 'deactivate' === $action ) {
			$result = $license_manager->deactivate();

			if ( $result['success'] ) {
				add_settings_error(
					'rp_license',
					'deactivated',
					$result['message'],
					'success'
				);
			} else {
				add_settings_error(
					'rp_license',
					$result['error'] ?? 'error',
					$result['message'],
					'error'
				);
			}
		}
	}

	/**
	 * Render notices
	 */
	private function render_notices(): void {
		settings_errors( 'rp_license' );
	}

	/**
	 * Render status section
	 *
	 * @param array<string, mixed> $status License status.
	 */
	private function render_status_section( array $status ): void {
		$tier_class = strtolower( $status['tier'] );
		?>
		<div class="rp-license-status rp-license-status--<?php echo esc_attr( $tier_class ); ?>">
			<h2>
				<?php if ( $status['is_valid'] ) : ?>
					<span class="dashicons dashicons-yes-alt"></span>
				<?php else : ?>
					<span class="dashicons dashicons-warning"></span>
				<?php endif; ?>
				<?php echo esc_html( $status['message'] ); ?>
			</h2>

			<?php if ( $status['is_active'] ) : ?>
				<p>
					<strong><?php esc_html_e( 'Aktiviert am:', 'recruiting-playbook' ); ?></strong>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), $status['activated_at'] ) ); ?>
				</p>

				<?php if ( ! empty( $status['expires_at'] ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'Gültig bis:', 'recruiting-playbook' ); ?></strong>
						<?php echo esc_html( date_i18n( get_option( 'date_format' ), $status['expires_at'] ) ); ?>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $status['is_offline'] ) ) : ?>
					<p class="rp-offline-notice">
						<span class="dashicons dashicons-cloud"></span>
						<?php esc_html_e( 'Offline-Modus: Lizenzserver nicht erreichbar. Lizenz wird lokal zwischengespeichert.', 'recruiting-playbook' ); ?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render activation form
	 *
	 * @param array<string, mixed> $status License status.
	 */
	private function render_activation_form( array $status ): void {
		?>
		<form method="post">
			<?php wp_nonce_field( 'rp_license_action', 'rp_license_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="license_key">
							<?php esc_html_e( 'Lizenzschlüssel', 'recruiting-playbook' ); ?>
						</label>
					</th>
					<td>
						<input
							type="text"
							id="license_key"
							name="license_key"
							class="regular-text"
							placeholder="RP-PRO-XXXX-XXXX-XXXX-XXXX-XXXX"
							style="text-transform: uppercase;"
						>
						<p class="description">
							<?php esc_html_e( 'Geben Sie Ihren Lizenzschlüssel ein, um Pro-Features freizuschalten.', 'recruiting-playbook' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" name="rp_license_action" value="activate" class="button button-primary">
					<?php esc_html_e( 'Lizenz aktivieren', 'recruiting-playbook' ); ?>
				</button>

				<?php if ( $status['is_active'] ) : ?>
					<button type="submit" name="rp_license_action" value="deactivate" class="button">
						<?php esc_html_e( 'Lizenz deaktivieren', 'recruiting-playbook' ); ?>
					</button>
				<?php endif; ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Render upgrade section
	 *
	 * @param array<string, mixed> $status License status.
	 */
	private function render_upgrade_section( array $status ): void {
		if ( 'FREE' !== $status['tier'] ) {
			return;
		}
		?>
		<div class="rp-upgrade-box">
			<h3><?php esc_html_e( 'Upgrade auf Pro', 'recruiting-playbook' ); ?></h3>
			<p><?php esc_html_e( 'Schalten Sie alle Features frei und nutzen Sie das volle Potenzial:', 'recruiting-playbook' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'Kanban-Board für Bewerbungsmanagement', 'recruiting-playbook' ); ?></li>
				<li><?php esc_html_e( 'REST API & Webhooks für Integrationen', 'recruiting-playbook' ); ?></li>
				<li><?php esc_html_e( 'Anpassbare E-Mail-Templates', 'recruiting-playbook' ); ?></li>
				<li><?php esc_html_e( 'Erweiterte Statistiken & Reports', 'recruiting-playbook' ); ?></li>
				<li><?php esc_html_e( 'Design-Anpassungen & Custom Branding', 'recruiting-playbook' ); ?></li>
				<li><?php esc_html_e( 'Benutzerrollen & Berechtigungen', 'recruiting-playbook' ); ?></li>
			</ul>
			<a href="<?php echo esc_url( $status['upgrade_url'] ); ?>" class="button button-hero" target="_blank">
				<?php esc_html_e( 'Jetzt upgraden', 'recruiting-playbook' ); ?> &rarr;
			</a>
		</div>
		<?php
	}

	/**
	 * Render feature comparison table
	 */
	private function render_feature_comparison(): void {
		$features = array(
			'unlimited_jobs'               => __( 'Unbegrenzte Stellenanzeigen', 'recruiting-playbook' ),
			'application_list'             => __( 'Bewerberliste', 'recruiting-playbook' ),
			'kanban_board'                 => __( 'Kanban-Board', 'recruiting-playbook' ),
			'advanced_applicant_management' => __( 'Erweitertes Bewerbermanagement', 'recruiting-playbook' ),
			'email_templates'              => __( 'E-Mail-Templates', 'recruiting-playbook' ),
			'api_access'                   => __( 'REST API Zugang', 'recruiting-playbook' ),
			'webhooks'                     => __( 'Webhooks', 'recruiting-playbook' ),
			'design_settings'              => __( 'Design-Einstellungen', 'recruiting-playbook' ),
			'user_roles'                   => __( 'Benutzerrollen', 'recruiting-playbook' ),
			'ai_job_generation'            => __( 'KI-Stellenanzeigen', 'recruiting-playbook' ),
			'ai_text_improvement'          => __( 'KI-Textverbesserung', 'recruiting-playbook' ),
		);

		$tiers = array(
			'FREE'     => __( 'Free', 'recruiting-playbook' ),
			'PRO'      => __( 'Pro', 'recruiting-playbook' ),
			'AI_ADDON' => __( 'AI Addon', 'recruiting-playbook' ),
			'BUNDLE'   => __( 'Bundle', 'recruiting-playbook' ),
		);
		?>
		<div class="rp-feature-table">
			<h2><?php esc_html_e( 'Feature-Vergleich', 'recruiting-playbook' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Feature', 'recruiting-playbook' ); ?></th>
						<?php foreach ( $tiers as $tier_label ) : ?>
							<th style="text-align: center;"><?php echo esc_html( $tier_label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $features as $feature_key => $feature_label ) : ?>
						<tr>
							<td><?php echo esc_html( $feature_label ); ?></td>
							<?php foreach ( array_keys( $tiers ) as $tier_key ) : ?>
								<?php
								$tier_features = \RecruitingPlaybook\Licensing\FeatureFlags::get_tier_features( $tier_key );
								$has_feature   = ! empty( $tier_features[ $feature_key ] );
								?>
								<td style="text-align: center;">
									<?php if ( $has_feature ) : ?>
										<span class="dashicons dashicons-yes"></span>
									<?php else : ?>
										<span class="dashicons dashicons-no"></span>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}

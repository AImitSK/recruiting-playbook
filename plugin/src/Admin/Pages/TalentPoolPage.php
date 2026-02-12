<?php
/**
 * Talent-Pool Übersichtsseite
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Talent-Pool Übersichtsseite (Pro-Feature)
 */
class TalentPoolPage {

	/**
	 * Prüft ob Pro-Features verfügbar sind
	 *
	 * @return bool
	 */
	private function hasProFeatures(): bool {
		return function_exists( 'rp_can' ) && rp_can( 'advanced_applicant_management' );
	}

	/**
	 * Assets laden
	 */
	public function enqueue_assets(): void {
		// CSS für Talent-Pool.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/admin-talent-pool.css';
		if ( file_exists( $css_file ) ) {
			wp_enqueue_style(
				'rp-talent-pool',
				RP_PLUGIN_URL . 'assets/dist/css/admin-talent-pool.css',
				[ 'rp-admin' ],
				RP_VERSION
			);
		}

		// Konfiguration für React (Script rp-admin wird bereits von Plugin.php geladen).
		wp_localize_script(
			'rp-admin',
			'rpTalentPool',
				[
					'apiUrl'            => rest_url( 'recruiting/v1/' ),
					'nonce'             => wp_create_nonce( 'wp_rest' ),
					'logoUrl'           => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
					'applicationsUrl'   => admin_url( 'admin.php?page=recruiting-playbook' ),
					'applicationUrl'    => admin_url( 'admin.php?page=rp-application-detail&id=' ),
					'i18n'              => [
						'title'                => __( 'Talent Pool', 'recruiting-playbook' ),
						'subtitle'             => __( 'Promising candidates for future positions', 'recruiting-playbook' ),
						'loading'              => __( 'Loading talent pool...', 'recruiting-playbook' ),
						'emptyPool'            => __( 'The talent pool is still empty.', 'recruiting-playbook' ),
						'emptyPoolHint'        => __( 'Add promising candidates from the application detail page to the talent pool.', 'recruiting-playbook' ),
						'goToApplications'     => __( 'Go to Applications', 'recruiting-playbook' ),
						'search'               => __( 'Search candidates...', 'recruiting-playbook' ),
						'filterByTags'         => __( 'Filter by tags', 'recruiting-playbook' ),
						'allTags'              => __( 'All tags', 'recruiting-playbook' ),
						'candidate'            => __( 'Candidate', 'recruiting-playbook' ),
						'candidates'           => __( 'Candidates', 'recruiting-playbook' ),
						'addedOn'              => __( 'Added on', 'recruiting-playbook' ),
						'expiresOn'            => __( 'Expires on', 'recruiting-playbook' ),
						'reason'               => __( 'Reason', 'recruiting-playbook' ),
						'tags'                 => __( 'Tags', 'recruiting-playbook' ),
						'noTags'               => __( 'No tags', 'recruiting-playbook' ),
						'viewApplication'      => __( 'View application', 'recruiting-playbook' ),
						'removeFromPool'       => __( 'Remove from pool', 'recruiting-playbook' ),
						'confirmRemove'        => __( 'Really remove candidate from talent pool?', 'recruiting-playbook' ),
						'removed'              => __( 'Candidate has been removed from talent pool.', 'recruiting-playbook' ),
						'errorRemoving'        => __( 'Error removing from talent pool.', 'recruiting-playbook' ),
						'errorLoading'         => __( 'Error loading talent pool.', 'recruiting-playbook' ),
						'retry'                => __( 'Retry', 'recruiting-playbook' ),
						'expiresSoon'          => __( 'Expires soon', 'recruiting-playbook' ),
						'expired'              => __( 'Expired', 'recruiting-playbook' ),
						'expiresInDays'        => __( 'Expires in %d days', 'recruiting-playbook' ),
						'total'                => __( 'Total', 'recruiting-playbook' ),
						'perPage'              => __( 'per page', 'recruiting-playbook' ),
						'page'                 => __( 'Page', 'recruiting-playbook' ),
						'of'                   => __( 'of', 'recruiting-playbook' ),
						'previous'             => __( 'Previous', 'recruiting-playbook' ),
						'next'                 => __( 'Next', 'recruiting-playbook' ),
						'edit'                 => __( 'Edit', 'recruiting-playbook' ),
						'save'                 => __( 'Save', 'recruiting-playbook' ),
						'cancel'               => __( 'Cancel', 'recruiting-playbook' ),
						'saved'                => __( 'Changes saved.', 'recruiting-playbook' ),
						'errorSaving'          => __( 'Error saving.', 'recruiting-playbook' ),
						'gdprNotice'           => __( 'GDPR notice: Candidates are automatically removed from the pool after expiration.', 'recruiting-playbook' ),
					],
				]
			);
	}

	/**
	 * Seite rendern
	 */
	public function render(): void {
		// Feature-Gate prüfen.
		if ( ! $this->hasProFeatures() ) {
			$this->renderUpgradeNotice();
			return;
		}

		// Assets laden.
		$this->enqueue_assets();

		?>
		<div id="rp-talent-pool-root">
			<div style="display: flex; align-items: center; justify-content: center; min-height: 300px; color: #6b7280;">
				<span class="spinner is-active" style="float: none; margin-right: 10px;"></span>
				<?php esc_html_e( 'Loading talent pool...', 'recruiting-playbook' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Upgrade-Hinweis für Free-User rendern
	 */
	private function renderUpgradeNotice(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Talent Pool', 'recruiting-playbook' ); ?></h1>

			<div class="rp-pro-feature-notice">
				<div class="rp-pro-feature-notice__icon">
					<span class="dashicons dashicons-groups"></span>
				</div>
				<div class="rp-pro-feature-notice__content">
					<h2><?php esc_html_e( 'Talent Pool is a Pro feature', 'recruiting-playbook' ); ?></h2>
					<p>
						<?php
						esc_html_e(
							'With the Talent Pool you can save promising candidates for future positions. This way you always have access to qualified talent when a new position needs to be filled.',
							'recruiting-playbook'
						);
						?>
					</p>
					<ul class="rp-pro-feature-notice__features">
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Organize candidates with tags', 'recruiting-playbook' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'GDPR-compliant storage with automatic expiration', 'recruiting-playbook' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Fast search and filtering', 'recruiting-playbook' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Add notes and reasons', 'recruiting-playbook' ); ?>
						</li>
					</ul>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-license' ) ); ?>" class="button button-primary button-hero">
							<?php esc_html_e( 'Upgrade to Pro now', 'recruiting-playbook' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
}

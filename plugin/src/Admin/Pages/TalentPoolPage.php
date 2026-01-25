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

		// React-Komponenten.
		$js_file    = RP_PLUGIN_DIR . 'assets/dist/js/index.js';
		$asset_file = RP_PLUGIN_DIR . 'assets/dist/js/index.asset.php';

		if ( file_exists( $js_file ) && file_exists( $asset_file ) ) {
			$assets = include $asset_file;

			wp_enqueue_script(
				'rp-talent-pool',
				RP_PLUGIN_URL . 'assets/dist/js/index.js',
				$assets['dependencies'] ?? [ 'wp-element', 'wp-api-fetch', 'wp-i18n' ],
				$assets['version'] ?? RP_VERSION,
				true
			);

			wp_set_script_translations( 'rp-talent-pool', 'recruiting-playbook' );

			// Konfiguration für React.
			wp_localize_script(
				'rp-talent-pool',
				'rpTalentPool',
				[
					'apiUrl'            => rest_url( 'recruiting/v1/' ),
					'nonce'             => wp_create_nonce( 'wp_rest' ),
					'applicationsUrl'   => admin_url( 'admin.php?page=rp-applications' ),
					'applicationUrl'    => admin_url( 'admin.php?page=rp-application-detail&id=' ),
					'i18n'              => [
						'title'                => __( 'Talent-Pool', 'recruiting-playbook' ),
						'subtitle'             => __( 'Vielversprechende Kandidaten für zukünftige Stellen', 'recruiting-playbook' ),
						'loading'              => __( 'Lade Talent-Pool...', 'recruiting-playbook' ),
						'emptyPool'            => __( 'Der Talent-Pool ist noch leer.', 'recruiting-playbook' ),
						'emptyPoolHint'        => __( 'Fügen Sie vielversprechende Kandidaten aus der Bewerbungsdetailseite zum Talent-Pool hinzu.', 'recruiting-playbook' ),
						'goToApplications'     => __( 'Zu den Bewerbungen', 'recruiting-playbook' ),
						'search'               => __( 'Kandidaten suchen...', 'recruiting-playbook' ),
						'filterByTags'         => __( 'Nach Tags filtern', 'recruiting-playbook' ),
						'allTags'              => __( 'Alle Tags', 'recruiting-playbook' ),
						'candidate'            => __( 'Kandidat', 'recruiting-playbook' ),
						'candidates'           => __( 'Kandidaten', 'recruiting-playbook' ),
						'addedOn'              => __( 'Hinzugefügt am', 'recruiting-playbook' ),
						'expiresOn'            => __( 'Läuft ab am', 'recruiting-playbook' ),
						'reason'               => __( 'Begründung', 'recruiting-playbook' ),
						'tags'                 => __( 'Tags', 'recruiting-playbook' ),
						'noTags'               => __( 'Keine Tags', 'recruiting-playbook' ),
						'viewApplication'      => __( 'Bewerbung anzeigen', 'recruiting-playbook' ),
						'removeFromPool'       => __( 'Aus Pool entfernen', 'recruiting-playbook' ),
						'confirmRemove'        => __( 'Kandidat wirklich aus dem Talent-Pool entfernen?', 'recruiting-playbook' ),
						'removed'              => __( 'Kandidat wurde aus dem Talent-Pool entfernt.', 'recruiting-playbook' ),
						'errorRemoving'        => __( 'Fehler beim Entfernen aus dem Talent-Pool.', 'recruiting-playbook' ),
						'errorLoading'         => __( 'Fehler beim Laden des Talent-Pools.', 'recruiting-playbook' ),
						'retry'                => __( 'Erneut versuchen', 'recruiting-playbook' ),
						'expiresSoon'          => __( 'Läuft bald ab', 'recruiting-playbook' ),
						'expired'              => __( 'Abgelaufen', 'recruiting-playbook' ),
						'expiresInDays'        => __( 'Läuft ab in %d Tagen', 'recruiting-playbook' ),
						'total'                => __( 'Gesamt', 'recruiting-playbook' ),
						'perPage'              => __( 'pro Seite', 'recruiting-playbook' ),
						'page'                 => __( 'Seite', 'recruiting-playbook' ),
						'of'                   => __( 'von', 'recruiting-playbook' ),
						'previous'             => __( 'Vorherige', 'recruiting-playbook' ),
						'next'                 => __( 'Nächste', 'recruiting-playbook' ),
						'edit'                 => __( 'Bearbeiten', 'recruiting-playbook' ),
						'save'                 => __( 'Speichern', 'recruiting-playbook' ),
						'cancel'               => __( 'Abbrechen', 'recruiting-playbook' ),
						'saved'                => __( 'Änderungen gespeichert.', 'recruiting-playbook' ),
						'errorSaving'          => __( 'Fehler beim Speichern.', 'recruiting-playbook' ),
						'gdprNotice'           => __( 'DSGVO-Hinweis: Kandidaten werden nach Ablauf automatisch aus dem Pool entfernt.', 'recruiting-playbook' ),
					],
				]
			);
		}
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
		<div class="wrap rp-talent-pool-wrap">
			<div id="rp-talent-pool-root">
				<div class="rp-talent-pool rp-talent-pool--loading">
					<div class="rp-talent-pool__loading">
						<span class="spinner is-active"></span>
						<?php esc_html_e( 'Lade Talent-Pool...', 'recruiting-playbook' ); ?>
					</div>
				</div>
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
			<h1><?php esc_html_e( 'Talent-Pool', 'recruiting-playbook' ); ?></h1>

			<div class="rp-pro-feature-notice">
				<div class="rp-pro-feature-notice__icon">
					<span class="dashicons dashicons-groups"></span>
				</div>
				<div class="rp-pro-feature-notice__content">
					<h2><?php esc_html_e( 'Talent-Pool ist ein Pro-Feature', 'recruiting-playbook' ); ?></h2>
					<p>
						<?php
						esc_html_e(
							'Mit dem Talent-Pool können Sie vielversprechende Kandidaten für zukünftige Stellen vormerken. So haben Sie immer Zugriff auf qualifizierte Talente, wenn eine neue Position zu besetzen ist.',
							'recruiting-playbook'
						);
						?>
					</p>
					<ul class="rp-pro-feature-notice__features">
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Kandidaten mit Tags organisieren', 'recruiting-playbook' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'DSGVO-konforme Aufbewahrung mit automatischem Ablauf', 'recruiting-playbook' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Schnelle Suche und Filterung', 'recruiting-playbook' ); ?>
						</li>
						<li>
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Notizen und Begründungen hinterlegen', 'recruiting-playbook' ); ?>
						</li>
					</ul>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=rp-license' ) ); ?>" class="button button-primary button-hero">
							<?php esc_html_e( 'Jetzt auf Pro upgraden', 'recruiting-playbook' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
}

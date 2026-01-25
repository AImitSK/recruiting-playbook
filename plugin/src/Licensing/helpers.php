<?php
/**
 * Globale Helper-Funktionen für Lizenz-System
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Licensing\LicenseManager;
use RecruitingPlaybook\Licensing\FeatureFlags;

/**
 * Prüft ob ein Feature verfügbar ist
 *
 * @param string $feature Feature-Name.
 * @return mixed Feature-Wert (bool, string, int) oder false.
 *
 * @example
 * if ( rp_can( 'kanban_board' ) ) { ... }
 * $max = rp_can( 'max_jobs' ); // -1 (unlimited)
 */
function rp_can( string $feature ): mixed {
	static $flags = null;

	if ( null === $flags ) {
		$license_manager = LicenseManager::get_instance();
		$tier            = $license_manager->get_tier();
		$flags           = new FeatureFlags( $tier );
	}

	return $flags->get( $feature );
}

/**
 * Gibt aktuellen Lizenz-Tier zurück
 *
 * @return string Tier-Name (FREE, PRO, AI_ADDON, BUNDLE).
 */
function rp_tier(): string {
	$license_manager = LicenseManager::get_instance();
	return $license_manager->get_tier();
}

/**
 * Prüft ob Pro-Lizenz aktiv ist
 *
 * @return bool True wenn PRO oder BUNDLE.
 */
function rp_is_pro(): bool {
	return in_array( rp_tier(), array( 'PRO', 'BUNDLE' ), true );
}

/**
 * Prüft ob AI-Addon aktiv ist
 *
 * @return bool True wenn AI_ADDON oder BUNDLE.
 */
function rp_has_ai(): bool {
	return in_array( rp_tier(), array( 'AI_ADDON', 'BUNDLE' ), true );
}

/**
 * Gibt Upgrade-URL zurück
 *
 * @param string|null $tier Optional: Spezifischer Tier für Deep-Link.
 * @return string Upgrade-URL.
 */
function rp_upgrade_url( ?string $tier = null ): string {
	$license_manager = LicenseManager::get_instance();
	return $license_manager->get_upgrade_url( $tier );
}

/**
 * Prüft ob Lizenz gültig ist
 *
 * @return bool True wenn Lizenz gültig (oder FREE).
 */
function rp_license_is_valid(): bool {
	$tier = rp_tier();

	if ( 'FREE' === $tier ) {
		return true;
	}

	$license_manager = LicenseManager::get_instance();
	return $license_manager->is_valid();
}

/**
 * Gibt Lizenzstatus für Admin zurück
 *
 * @return array<string, mixed> Status-Array.
 */
function rp_license_status(): array {
	$license_manager = LicenseManager::get_instance();
	return $license_manager->get_status();
}

/**
 * Gibt alle Features für aktuellen Tier zurück
 *
 * @return array<string, mixed> Feature-Array.
 */
function rp_features(): array {
	$tier  = rp_tier();
	$flags = new FeatureFlags( $tier );
	return $flags->all();
}

/**
 * Zeigt Upgrade-Hinweis wenn Feature nicht verfügbar
 *
 * @param string $feature      Feature-Name.
 * @param string $feature_name Anzeigename des Features.
 * @param string $required_tier Benötigter Tier (PRO, AI_ADDON, BUNDLE).
 * @return bool True wenn Feature verfügbar, false wenn Hinweis gezeigt wurde.
 */
function rp_require_feature( string $feature, string $feature_name, string $required_tier = 'PRO' ): bool {
	if ( rp_can( $feature ) ) {
		return true;
	}

	// Upgrade-Hinweis anzeigen.
	$tier_labels = array(
		'PRO'      => 'Pro',
		'AI_ADDON' => 'AI Addon',
		'BUNDLE'   => 'Pro + AI Bundle',
	);

	printf(
		'<div class="rp-upgrade-prompt">
			<div class="rp-upgrade-prompt__icon">
				<span class="dashicons dashicons-lock"></span>
			</div>
			<div class="rp-upgrade-prompt__content">
				<h4>%s</h4>
				<p>%s</p>
				<a href="%s" class="button button-primary" target="_blank">%s</a>
			</div>
		</div>',
		esc_html(
			sprintf(
				/* translators: 1: feature name, 2: tier name */
				__( '%1$s erfordert %2$s', 'recruiting-playbook' ),
				$feature_name,
				$tier_labels[ $required_tier ] ?? $required_tier
			)
		),
		esc_html__( 'Upgraden Sie, um diese Funktion freizuschalten.', 'recruiting-playbook' ),
		esc_url( rp_upgrade_url( $required_tier ) ),
		esc_html__( 'Mehr erfahren', 'recruiting-playbook' )
	);

	return false;
}

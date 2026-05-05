<?php
/**
 * Lizenz/Plugin-Helper (Free-Build kompatibel)
 *
 * Diese Datei ist im Free- UND Pro-Build vorhanden. Sie enthält ausschließlich
 * Funktionen, die in beiden Builds sinnvoll sind:
 * - Tier-Status (in Free-Build immer FREE).
 * - Lizenz-Status-API für UI.
 * - API-Key-Helper (No-Op-fallback bei fehlendem Pro-Service).
 * - Externer Pricing-Link (für „Erfahre mehr"-Knopf — gemäß WP.org erlaubt).
 *
 * Pro-spezifische Feature-Gates befinden sich in einer separaten Datei
 * (pro-helpers.php), welche via @fs_premium_only aus dem Free-Build entfernt wird.
 *
 * WordPress.org-Compliance: Free-Build enthält keine Pro-Feature-Mappings
 * und keine Lock-Box-UI (Plugin-Guideline 5: Trialware verboten).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Gibt den aktuellen Lizenz-Tier zurück
 *
 * Im Free-Build immer 'FREE'. Pro-Build überschreibt durch pro-helpers.php-Implementation,
 * die zuerst Freemius checkt.
 *
 * @return string Tier-Name ('FREE' oder 'PRO').
 */
function recpl_tier(): string {
	if ( function_exists( 'recpl_fs' ) && ( recpl_fs()->is_paying() || recpl_fs()->is_trial() ) ) {
		if ( recpl_fs()->is_plan( 'pro' ) ) {
			return 'PRO';
		}
	}
	return 'FREE';
}

/**
 * Externer Pricing-Link (Freemius-Upgrade-Page)
 *
 * Erlaubt von WP.org als externer Verweis auf separate Pro-Version.
 * Wird im Account-Bereich für „Get Pro"-Buttons genutzt.
 *
 * @param string|null $tier Optional Tier für Deep-Link.
 * @return string Upgrade-URL.
 */
function recpl_upgrade_url( ?string $tier = null ): string {
	if ( ! function_exists( 'recpl_fs' ) ) {
		return 'https://recruiting-playbook.com/pricing/';
	}
	return recpl_fs()->get_upgrade_url();
}

/**
 * Prüft ob Lizenz gültig ist
 *
 * Im Free-Build immer true (Free hat „immer gültige Lizenz").
 *
 * @return bool
 */
function recpl_license_is_valid(): bool {
	$tier = recpl_tier();
	if ( 'FREE' === $tier ) {
		return true;
	}
	if ( ! function_exists( 'recpl_fs' ) ) {
		return false;
	}
	return recpl_fs()->is_paying();
}

/**
 * Gibt Lizenzstatus für Admin-UI zurück
 *
 * Liefert für das Admin-React-UI den aktuellen Lizenzstatus.
 * Im Free-Build immer Free-Status.
 *
 * @return array<string, mixed>
 */
function recpl_license_status(): array {
	$tier = recpl_tier();

	$tier_labels = [
		'FREE' => 'Free',
		'PRO'  => 'Pro',
	];

	if ( ! function_exists( 'recpl_fs' ) || 'FREE' === $tier ) {
		return [
			'tier'        => 'FREE',
			'is_active'   => false,
			'is_valid'    => true,
			'message'     => __( 'Free version', 'recruiting-playbook' ),
			'upgrade_url' => recpl_upgrade_url(),
		];
	}

	$is_paying = recpl_fs()->is_paying();
	return [
		'tier'        => $tier,
		'is_active'   => $is_paying,
		'is_valid'    => $is_paying,
		'message'     => $is_paying
			? sprintf(
				/* translators: %s: tier name */
				__( '%s license active', 'recruiting-playbook' ),
				$tier_labels[ $tier ] ?? $tier
			)
			: __( 'License invalid or expired.', 'recruiting-playbook' ),
		'upgrade_url' => recpl_upgrade_url(),
	];
}

/**
 * Gibt API-Key-Daten des aktuellen Requests zurück
 *
 * Im Free-Build gibt es keine API-Key-Authentifizierung — Funktion liefert null.
 *
 * @return object|null
 */
function recpl_get_api_key_data(): ?object {
	return $GLOBALS['recpl_authenticated_api_key'] ?? null;
}

/**
 * Prüft ob der aktuelle API-Key eine bestimmte Permission hat
 *
 * Bei WordPress-Auth (kein API-Key) wird immer true zurückgegeben.
 * Im Free-Build wird kein ApiKeyService verwendet → immer true für WP-Auth.
 *
 * @param string $permission Permission-String.
 * @return bool
 */
function recpl_api_key_can( string $permission ): bool {
	$key_data = recpl_get_api_key_data();
	if ( ! $key_data ) {
		return true;
	}
	if ( class_exists( '\\RecruitingPlaybook\\Services\\ApiKeyService' ) ) {
		$service = new \RecruitingPlaybook\Services\ApiKeyService();
		return $service->hasPermission( $key_data, $permission );
	}
	return true;
}

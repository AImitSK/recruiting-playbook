<?php
/**
 * Globale Helper-Funktionen für Lizenz-System (Freemius)
 *
 * Diese Funktionen nutzen Freemius für die Lizenzierung statt
 * des eigenen LicenseManager. Die FeatureFlags-Klasse dient
 * weiterhin als Feature-Referenz.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Licensing\FeatureFlags;

/**
 * Feature-Mapping: Welche Features gehören zu welchem Plan/Addon
 *
 * Quelle: 'parent' = Parent-Plugin Plan, 'addon' = KI-Addon Lizenz.
 *
 * @return array<string, array{source: string, plans: array<string>}> Feature => Config-Array
 */
function rp_get_feature_plan_mapping(): array {
	return [
		// Pro Features (Parent-Plugin Plan).
		'kanban_board'                  => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'advanced_applicant_management' => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'email_templates'               => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'custom_fields'                 => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'api_access'                    => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'webhooks'                      => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'advanced_reporting'            => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'csv_export'                    => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'design_settings'               => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'custom_branding'               => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'user_roles'                    => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'avada_integration'             => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'gutenberg_blocks'              => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],
		'priority_support'              => [
			'source' => 'parent',
			'plans'  => [ 'pro' ],
		],

		// AI Features (KI-Addon Lizenz).
		'ai_job_generation'             => [ 'source' => 'addon' ],
		'ai_text_improvement'           => [ 'source' => 'addon' ],
		'ai_templates'                  => [ 'source' => 'addon' ],
		'ai_cv_matching'                => [ 'source' => 'addon' ],

		// Free Features (immer verfügbar).
		'create_jobs'                   => [ 'source' => 'free' ],
		'unlimited_jobs'                => [ 'source' => 'free' ],
		'application_list'              => [ 'source' => 'free' ],
	];
}

/**
 * Prüft ob ein Feature verfügbar ist
 *
 * Unterscheidet zwischen Parent-Plan-Features (Pro) und Addon-Features (KI).
 *
 * @param string $feature Feature-Name.
 * @return mixed Feature-Wert (bool, string, int) oder false.
 *
 * @example
 * if ( rp_can( 'kanban_board' ) ) { ... }
 * if ( rp_can( 'ai_cv_matching' ) ) { ... }
 */
function rp_can( string $feature ): mixed {
	// Development Mode: Alle Features aktiviert.
	// Setze RP_DEV_MODE in wp-config.php: define( 'RP_DEV_MODE', true );
	if ( defined( 'RP_DEV_MODE' ) && RP_DEV_MODE === true ) {
		// Für detaillierte Werte (max_jobs etc.) trotzdem FeatureFlags nutzen.
		$tier  = 'BUNDLE'; // Höchster Tier mit allen Features.
		$flags = new FeatureFlags( $tier );
		$value = $flags->get( $feature );
		return $value !== false ? $value : true;
	}

	$mapping = rp_get_feature_plan_mapping();

	// Feature in Mapping definiert.
	if ( isset( $mapping[ $feature ] ) ) {
		$config = $mapping[ $feature ];

		// Free Features sind immer verfügbar.
		if ( 'free' === $config['source'] ) {
			return true;
		}

		// AI Features → KI-Addon Lizenz prüfen.
		if ( 'addon' === $config['source'] ) {
			return rp_has_ai();
		}

		// Parent Features → Parent-Plan prüfen.
		if ( 'parent' === $config['source'] ) {
			if ( ! function_exists( 'rp_fs' ) ) {
				return false;
			}

			if ( ! rp_fs()->is_paying() && ! rp_fs()->is_trial() ) {
				return false;
			}

			foreach ( $config['plans'] as $plan ) {
				if ( rp_fs()->is_plan( $plan ) ) {
					return true;
				}
			}

			return false;
		}
	}

	// Fallback: FeatureFlags-Klasse für detaillierte Werte (z.B. max_jobs, reporting level).
	$tier  = rp_tier();
	$flags = new FeatureFlags( $tier );
	return $flags->get( $feature );
}

/**
 * Gibt aktuellen Lizenz-Tier zurück
 *
 * Nur Parent-Plan-basiert (FREE oder PRO).
 * KI-Features werden separat über rp_has_ai() geprüft.
 *
 * @return string Tier-Name (FREE, PRO).
 */
function rp_tier(): string {
	if ( function_exists( 'rp_fs' ) && ( rp_fs()->is_paying() || rp_fs()->is_trial() ) ) {
		if ( rp_fs()->is_plan( 'pro' ) ) {
			return 'PRO';
		}
	}

	return 'FREE';
}

/**
 * Prüft ob Pro-Lizenz aktiv ist
 *
 * @return bool True wenn Pro-Plan auf dem Parent-Plugin aktiv.
 */
function rp_is_pro(): bool {
	if ( ! function_exists( 'rp_fs' ) ) {
		return false;
	}
	return ( rp_fs()->is_paying() || rp_fs()->is_trial() ) && rp_fs()->is_plan( 'pro' );
}

/**
 * Prüft ob KI-Addon aktiv und lizenziert ist
 *
 * Prüft die Addon-eigene Freemius-Instanz (rpk_fs) statt Parent-Pläne.
 *
 * @return bool True wenn KI-Addon installiert und Lizenz aktiv.
 */
function rp_has_ai(): bool {
	if ( ! function_exists( 'rpk_fs' ) ) {
		return false;
	}
	return rpk_fs()->is_paying() || rpk_fs()->is_trial();
}

/**
 * Prüft ob CV-Matching verfügbar ist
 *
 * @return bool True wenn KI-Addon aktiv und lizenziert.
 */
function rp_has_cv_matching(): bool {
	return rp_can( 'ai_cv_matching' ) === true;
}

/**
 * Gibt Upgrade-URL zurück (Freemius Pricing Page)
 *
 * @param string|null $tier Optional: Spezifischer Tier für Deep-Link.
 * @return string Upgrade-URL.
 */
function rp_upgrade_url( ?string $tier = null ): string {
	if ( ! function_exists( 'rp_fs' ) ) {
		return 'https://recruiting-playbook.com/pricing/';
	}
	return rp_fs()->get_upgrade_url();
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

	if ( ! function_exists( 'rp_fs' ) ) {
		return false;
	}

	// Freemius prüft automatisch die Lizenzgültigkeit.
	return rp_fs()->is_paying();
}

/**
 * Gibt Lizenzstatus für Admin zurück
 *
 * @return array<string, mixed> Status-Array.
 */
function rp_license_status(): array {
	$tier = rp_tier();

	$tier_labels = [
		'FREE' => 'Free',
		'PRO'  => 'Pro',
	];

	if ( ! function_exists( 'rp_fs' ) || 'FREE' === $tier ) {
		return [
			'tier'        => 'FREE',
			'has_ai'      => rp_has_ai(),
			'is_active'   => false,
			'is_valid'    => true,
			'message'     => __( 'Kostenlose Version', 'recruiting-playbook' ),
			'upgrade_url' => rp_upgrade_url(),
		];
	}

	$is_paying = rp_fs()->is_paying();

	return [
		'tier'        => $tier,
		'has_ai'      => rp_has_ai(),
		'is_active'   => $is_paying,
		'is_valid'    => $is_paying,
		'message'     => $is_paying
			? sprintf(
				/* translators: %s: tier name */
				__( '%s Lizenz aktiv', 'recruiting-playbook' ),
				$tier_labels[ $tier ] ?? $tier
			)
			: __( 'Lizenz ungültig oder abgelaufen.', 'recruiting-playbook' ),
		'upgrade_url' => rp_upgrade_url(),
	];
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
 * Prüft ob User ein Feature nutzen darf (Capability + Feature-Flag)
 *
 * Kombiniert WordPress-Capability-Check mit Feature-Flag-Prüfung.
 * Die Reihenfolge ist wichtig: Zuerst Capability (Security), dann Feature-Flag (Business-Logic).
 *
 * @param string $feature    Feature-Name für rp_can() (z.B. 'email_templates').
 * @param string $capability WordPress Capability (z.B. 'rp_manage_email_templates').
 * @return bool True wenn User Capability hat UND Feature verfügbar ist.
 *
 * @example
 * if ( ! rp_user_can_use_feature( 'email_templates', 'rp_manage_email_templates' ) ) {
 *     return new WP_Error( 'forbidden', 'Keine Berechtigung', [ 'status' => 403 ] );
 * }
 */
function rp_user_can_use_feature( string $feature, string $capability ): bool {
	// 1. Capability-Check (WordPress-Core-Security).
	if ( ! current_user_can( $capability ) ) {
		return false;
	}

	// 2. Feature-Flag-Check (Business-Logic).
	if ( ! rp_can( $feature ) ) {
		return false;
	}

	return true;
}

/**
 * Prüft Capability und Feature-Flag und gibt WP_Error zurück bei Fehler
 *
 * Convenience-Funktion für REST API Permission Callbacks.
 * Gibt true zurück bei Erfolg, WP_Error bei fehlender Berechtigung.
 *
 * @param string $feature       Feature-Name für rp_can().
 * @param string $capability    WordPress Capability.
 * @param string $error_code    WP_Error Code.
 * @param string $error_message Fehlermeldung.
 * @return bool|\WP_Error True bei Erfolg, WP_Error bei Fehler.
 */
function rp_check_feature_permission( string $feature, string $capability, string $error_code, string $error_message ) {
	// 1. Capability-Check (WordPress-Core-Security).
	if ( ! current_user_can( $capability ) ) {
		return new \WP_Error(
			'rest_forbidden',
			$error_message,
			[ 'status' => 403 ]
		);
	}

	// 2. Feature-Flag-Check (Business-Logic).
	if ( ! rp_can( $feature ) ) {
		$mapping          = rp_get_feature_plan_mapping();
		$is_addon_feature = isset( $mapping[ $feature ] ) && 'addon' === $mapping[ $feature ]['source'];

		return new \WP_Error(
			$error_code,
			$is_addon_feature
				? __( 'Diese Funktion erfordert das KI-Addon.', 'recruiting-playbook' )
				: __( 'Diese Funktion erfordert Pro.', 'recruiting-playbook' ),
			[
				'status'      => 403,
				'upgrade_url' => rp_upgrade_url( $is_addon_feature ? 'KI' : 'PRO' ),
			]
		);
	}

	return true;
}

/**
 * Zeigt Upgrade-Hinweis wenn Feature nicht verfügbar
 *
 * Prüft ob ein Feature verfügbar ist und zeigt bei Nicht-Verfügbarkeit
 * automatisch einen Upgrade-Hinweis an. Nützlich für Feature-Gating in
 * Admin-Seiten und Formularen.
 *
 * @param string $feature       Feature-Name (z.B. 'kanban_board', 'api_access').
 * @param string $feature_name  Anzeigename des Features für den Benutzer.
 * @param string $required_tier Benötigter Tier (PRO) oder 'KI' für Addon-Features.
 * @return bool True wenn Feature verfügbar und Code fortgesetzt werden kann,
 *              false wenn Upgrade-Hinweis angezeigt wurde.
 *
 * @example Feature-Check mit Upgrade-Hinweis
 * ```php
 * // Am Anfang einer Feature-spezifischen Admin-Seite
 * if ( ! rp_require_feature( 'kanban_board', 'Kanban-Board', 'PRO' ) ) {
 *     return; // Upgrade-Hinweis wurde angezeigt, Funktion beenden
 * }
 *
 * // Feature ist verfügbar, normalen Code ausführen
 * render_kanban_board();
 * ```
 *
 * @example Inline-Feature-Check
 * ```php
 * <div class="feature-section">
 *     <?php if ( rp_require_feature( 'api_access', 'REST API', 'PRO' ) ) : ?>
 *         <!-- API-Einstellungen hier -->
 *     <?php endif; ?>
 * </div>
 * ```
 */
function rp_require_feature( string $feature, string $feature_name, string $required_tier = 'PRO' ): bool {
	if ( rp_can( $feature ) ) {
		return true;
	}

	// Upgrade-Hinweis anzeigen.
	$tier_labels = [
		'PRO' => 'Pro',
		'KI'  => 'KI-Addon',
	];

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

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
 * Feature-Mapping: Welche Features gehören zu welchem Plan
 *
 * @return array<string, array<string>> Feature => Plan-Array
 */
function rp_get_feature_plan_mapping(): array {
	return [
		// Pro Features.
		'kanban_board'                  => [ 'pro', 'bundle' ],
		'advanced_applicant_management' => [ 'pro', 'bundle' ],
		'email_templates'               => [ 'pro', 'bundle' ],
		'custom_fields'                 => [ 'pro', 'bundle' ],
		'api_access'                    => [ 'pro', 'bundle' ],
		'webhooks'                      => [ 'pro', 'bundle' ],
		'advanced_reporting'            => [ 'pro', 'bundle' ],
		'csv_export'                    => [ 'pro', 'bundle' ],
		'design_settings'               => [ 'pro', 'bundle' ],
		'custom_branding'               => [ 'pro', 'bundle' ],
		'user_roles'                    => [ 'pro', 'bundle' ],
		'avada_integration'             => [ 'pro', 'bundle' ],
		'gutenberg_blocks'              => [ 'pro', 'bundle' ],
		'priority_support'              => [ 'pro', 'ai_addon', 'bundle' ],

		// AI Features.
		'ai_job_generation'             => [ 'ai_addon', 'bundle' ],
		'ai_text_improvement'           => [ 'ai_addon', 'bundle' ],
		'ai_templates'                  => [ 'ai_addon', 'bundle' ],
		'ai_cv_matching'                => [ 'ai_addon', 'bundle' ],

		// Free Features (immer verfügbar).
		'create_jobs'                   => [ 'free', 'pro', 'ai_addon', 'bundle' ],
		'unlimited_jobs'                => [ 'free', 'pro', 'ai_addon', 'bundle' ],
		'application_list'              => [ 'free', 'pro', 'ai_addon', 'bundle' ],
	];
}

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

	// Feature in Mapping definiert → Plan-basierte Prüfung.
	if ( isset( $mapping[ $feature ] ) ) {
		$required_plans = $mapping[ $feature ];

		// Free Features sind immer verfügbar.
		if ( in_array( 'free', $required_plans, true ) ) {
			return true;
		}

		// Freemius Plan Check.
		if ( ! function_exists( 'rp_fs' ) ) {
			return false;
		}

		// User muss zahlen (oder Trial haben) UND auf passendem Plan sein.
		if ( ! rp_fs()->is_paying() && ! rp_fs()->is_trial() ) {
			return false;
		}

		foreach ( $required_plans as $plan ) {
			// is_plan ohne zweiten Parameter: true wenn Plan ODER höher.
			if ( rp_fs()->is_plan( $plan ) ) {
				return true;
			}
		}

		return false;
	}

	// Fallback: FeatureFlags-Klasse für detaillierte Werte (z.B. max_jobs, reporting level).
	$tier  = rp_tier();
	$flags = new FeatureFlags( $tier );
	return $flags->get( $feature );
}

/**
 * Gibt aktuellen Lizenz-Tier zurück
 *
 * @return string Tier-Name (FREE, PRO, AI_ADDON, BUNDLE).
 */
function rp_tier(): string {
	if ( ! function_exists( 'rp_fs' ) ) {
		return 'FREE';
	}

	// User muss zahlen oder Trial haben.
	if ( ! rp_fs()->is_paying() && ! rp_fs()->is_trial() ) {
		return 'FREE';
	}

	// Plan-Hierarchie: Bundle > Pro > AI_Addon > Free.
	// is_plan ohne zweiten Parameter prüft auch höhere Pläne.
	if ( rp_fs()->is_plan( 'bundle', true ) ) {
		return 'BUNDLE';
	}
	if ( rp_fs()->is_plan( 'pro', true ) ) {
		return 'PRO';
	}
	if ( rp_fs()->is_plan( 'ai_addon', true ) ) {
		return 'AI_ADDON';
	}

	return 'FREE';
}

/**
 * Prüft ob Pro-Lizenz aktiv ist
 *
 * @return bool True wenn PRO oder BUNDLE.
 */
function rp_is_pro(): bool {
	if ( ! function_exists( 'rp_fs' ) ) {
		return false;
	}
	return rp_fs()->is_plan( 'pro', true ) || rp_fs()->is_plan( 'bundle', true );
}

/**
 * Prüft ob AI-Addon aktiv ist
 *
 * @return bool True wenn AI_ADDON oder BUNDLE.
 */
function rp_has_ai(): bool {
	if ( ! function_exists( 'rp_fs' ) ) {
		return false;
	}
	return rp_fs()->is_plan( 'ai_addon', true ) || rp_fs()->is_plan( 'bundle', true );
}

/**
 * Prüft ob CV-Matching verfügbar ist
 *
 * @return bool True wenn ai_cv_matching Feature aktiv (AI_ADDON oder BUNDLE).
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
		'FREE'     => 'Free',
		'PRO'      => 'Pro',
		'AI_ADDON' => 'AI Addon',
		'BUNDLE'   => 'Pro + AI Bundle',
	];

	if ( ! function_exists( 'rp_fs' ) || 'FREE' === $tier ) {
		return [
			'tier'        => 'FREE',
			'is_active'   => false,
			'is_valid'    => true,
			'message'     => __( 'Kostenlose Version', 'recruiting-playbook' ),
			'upgrade_url' => rp_upgrade_url(),
		];
	}

	$is_paying = rp_fs()->is_paying();

	return [
		'tier'        => $tier,
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
		return new \WP_Error(
			$error_code,
			__( 'Diese Funktion erfordert Pro.', 'recruiting-playbook' ),
			[
				'status'      => 403,
				'upgrade_url' => rp_upgrade_url( 'PRO' ),
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
 * @param string $required_tier Benötigter Tier (PRO, AI_ADDON, BUNDLE).
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
		'PRO'      => 'Pro',
		'AI_ADDON' => 'AI Addon',
		'BUNDLE'   => 'Pro + AI Bundle',
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

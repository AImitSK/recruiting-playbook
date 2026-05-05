<?php
/**
 * Pro-only Lizenz-Gating-Helper
 *
 * Diese Datei wird von Freemius via @fs_premium_only physisch aus dem
 * Free-Build entfernt. Sie enthält ausschließlich Pro-spezifische
 * Feature-Gating-Logik (Lock-Boxen, Plan-Mapping, Permission-Checks für Pro-Features).
 *
 * Entscheidung: Diese Funktionen MÜSSEN aus dem Free-Build raus, weil
 * WordPress.org Trialware (lokal vorhandener, gesperrter Code) verbietet
 * (Plugin-Guideline 5).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Licensing\FeatureFlags;

/**
 * Feature-Mapping: Welche Features gehören zu welchem Plan
 *
 * @return array<string, array{source: string, plans?: array<string>}>
 */
function recpl_get_feature_plan_mapping(): array {
	return [
		// Pro Features (inkl. KI).
		'kanban_board'                  => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'advanced_applicant_management' => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'email_templates'               => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'custom_fields'                 => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'api_access'                    => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'webhooks'                      => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'integrations'                  => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'advanced_reporting'            => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'csv_export'                    => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'design_settings'               => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'custom_branding'               => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'user_roles'                    => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'avada_integration'             => [ 'source' => 'free' ],
		'gutenberg_blocks'              => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'priority_support'              => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'ai_job_generation'             => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'ai_text_improvement'           => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'ai_templates'                  => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],
		'ai_cv_matching'                => [ 'source' => 'parent', 'plans' => [ 'pro' ] ],

		// Free Features.
		'create_jobs'      => [ 'source' => 'free' ],
		'unlimited_jobs'   => [ 'source' => 'free' ],
		'application_list' => [ 'source' => 'free' ],
	];
}

/**
 * Prüft ob ein Feature verfügbar ist (Pro-Build)
 *
 * @param string $feature Feature-Name.
 * @return mixed Feature-Wert oder false.
 */
function recpl_can( string $feature ): mixed {
	if ( defined( 'RECPL_DEV_MODE' ) && RECPL_DEV_MODE === true ) {
		$flags = new FeatureFlags( 'PRO' );
		$value = $flags->get( $feature );
		return $value !== false ? $value : true;
	}

	$mapping = recpl_get_feature_plan_mapping();

	if ( isset( $mapping[ $feature ] ) ) {
		$config = $mapping[ $feature ];

		if ( 'free' === $config['source'] ) {
			return true;
		}

		if ( 'parent' === $config['source'] ) {
			if ( ! function_exists( 'recpl_fs' ) ) {
				return false;
			}
			if ( ! recpl_fs()->is_paying() && ! recpl_fs()->is_trial() ) {
				return false;
			}
			foreach ( $config['plans'] ?? [] as $plan ) {
				if ( recpl_fs()->is_plan( $plan ) ) {
					return true;
				}
			}
			return false;
		}
	}

	$flags = new FeatureFlags( recpl_tier() );
	return $flags->get( $feature );
}

/**
 * Prüft ob Pro-Lizenz aktiv ist
 *
 * @return bool
 */
function recpl_is_pro(): bool {
	if ( ! function_exists( 'recpl_fs' ) ) {
		return false;
	}
	return ( recpl_fs()->is_paying() || recpl_fs()->is_trial() ) && recpl_fs()->is_plan( 'pro' );
}

/**
 * Prüft ob KI-Features verfügbar sind
 *
 * @return bool
 */
function recpl_has_ai(): bool {
	return recpl_is_pro();
}

/**
 * Prüft ob CV-Matching verfügbar ist
 *
 * @return bool
 */
function recpl_has_cv_matching(): bool {
	if ( recpl_can( 'ai_cv_matching' ) !== true ) {
		return false;
	}
	$settings = get_option( 'recpl_settings', [] );
	if ( ! empty( $settings['disable_ai_features'] ) ) {
		return false;
	}
	return true;
}

/**
 * Capability + Feature-Flag combined
 *
 * @param string $feature    Feature-Name.
 * @param string $capability WordPress Capability.
 * @return bool
 */
function recpl_user_can_use_feature( string $feature, string $capability ): bool {
	if ( ! current_user_can( $capability ) ) {
		return false;
	}
	if ( ! recpl_can( $feature ) ) {
		return false;
	}
	return true;
}

/**
 * REST Permission-Callback Helper für Pro-Features
 *
 * @param string $feature       Feature-Name.
 * @param string $capability    Capability.
 * @param string $error_code    WP_Error code.
 * @param string $error_message Fehlermeldung.
 * @return bool|\WP_Error
 */
function recpl_check_feature_permission( string $feature, string $capability, string $error_code, string $error_message ) {
	if ( ! current_user_can( $capability ) ) {
		return new \WP_Error( 'rest_forbidden', $error_message, [ 'status' => 403 ] );
	}
	if ( ! recpl_can( $feature ) ) {
		return new \WP_Error(
			$error_code,
			__( 'This feature requires Pro.', 'recruiting-playbook' ),
			[ 'status' => 403, 'upgrade_url' => recpl_upgrade_url( 'PRO' ) ]
		);
	}
	return true;
}

/**
 * Lock-Box für Pro-Features (Pro-Build only)
 *
 * @param string $feature       Feature-Name.
 * @param string $feature_name  Anzeigename.
 * @param string $required_tier Tier-Name.
 * @return bool
 */
function recpl_require_feature( string $feature, string $feature_name, string $required_tier = 'PRO' ): bool {
	if ( recpl_can( $feature ) ) {
		return true;
	}

	$upgrade_url = esc_url( recpl_upgrade_url( $required_tier ) );
	$title       = esc_html(
		sprintf(
			/* translators: %s: feature name */
			__( '%s is a Pro feature', 'recruiting-playbook' ),
			$feature_name
		)
	);
	$description = esc_html__( 'Upgrade to Pro to unlock this feature. You can compare plans and pricing on the upgrade page.', 'recruiting-playbook' );
	$button_text = esc_html__( 'Upgrade to Pro', 'recruiting-playbook' );

	echo '<div style="display:flex;align-items:flex-start;gap:16px;padding:24px;background:linear-gradient(135deg,#f0f6fc 0%,#fff 100%);border:1px solid #c3d9ed;border-radius:8px;margin-top:20px;">';
	echo '<div style="flex-shrink:0;width:48px;height:48px;background:#2271b1;border-radius:50%;display:flex;align-items:center;justify-content:center;">';
	echo '<span class="dashicons dashicons-lock" style="font-size:24px;width:24px;height:24px;color:#fff;"></span>';
	echo '</div>';
	echo '<div>';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<h3 style="margin:0 0 8px 0;font-size:16px;color:#1d2327;">' . $title . '</h3>';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p style="margin:0 0 16px 0;color:#50575e;font-size:14px;line-height:1.5;">' . $description . '</p>';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<a href="' . $upgrade_url . '" class="button button-primary button-hero">' . $button_text . '</a>';
	echo '</div>';
	echo '</div>';

	return false;
}

/**
 * Gibt alle Features für aktuellen Tier zurück (Pro-Build)
 *
 * @return array<string, mixed>
 */
function recpl_features(): array {
	$flags = new FeatureFlags( recpl_tier() );
	return $flags->all();
}

<?php
/**
 * Plugin Bootstrap
 *
 * Enthält den namespace-basierten Code für das Plugin.
 * Wird von recruiting-playbook.php geladen.
 *
 * @package RecruitingPlaybook
 */

namespace RecruitingPlaybook;

defined( 'ABSPATH' ) || exit;

// Freemius SDK: Premium/Free Version Handling
if ( function_exists( '\recpl_fs' ) ) {
	\recpl_fs()->set_basename( true, RECPL_PLUGIN_FILE );
} else {
	// Autoloader
	if ( file_exists( RECPL_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
		require_once RECPL_PLUGIN_DIR . 'vendor/autoload.php';
	}

	// Freemius SDK initialisieren (für Lizenzierung & Updates).
	if ( file_exists( RECPL_PLUGIN_DIR . 'freemius.php' ) ) {
		require_once RECPL_PLUGIN_DIR . 'freemius.php';
	}

	/**
	 * Requirements prüfen
	 */
	function rp_check_requirements(): bool {
		if ( version_compare( PHP_VERSION, RECPL_MIN_PHP_VERSION, '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>';
					printf(
					/* translators: 1: Required PHP version, 2: Current PHP version */
						esc_html__( 'Recruiting Playbook benötigt PHP %1$s oder höher. Sie nutzen PHP %2$s.', 'recruiting-playbook' ),
						esc_html( RECPL_MIN_PHP_VERSION ),
						esc_html( PHP_VERSION )
					);
					echo '</p></div>';
				}
			);
			return false;
		}

		global $wp_version;
		if ( version_compare( $wp_version, RECPL_MIN_WP_VERSION, '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					global $wp_version;
					echo '<div class="notice notice-error"><p>';
					printf(
					/* translators: 1: Required WP version, 2: Current WP version */
						esc_html__( 'Recruiting Playbook benötigt WordPress %1$s oder höher. Sie nutzen WordPress %2$s.', 'recruiting-playbook' ),
						esc_html( RECPL_MIN_WP_VERSION ),
						esc_html( $wp_version )
					);
					echo '</p></div>';
				}
			);
			return false;
		}

		return true;
	}

	// Aktivierung
	register_activation_hook(
		RECPL_PLUGIN_FILE,
		function () {
			if ( ! rp_check_requirements() ) {
				deactivate_plugins( plugin_basename( RECPL_PLUGIN_FILE ) );
				wp_die( esc_html__( 'Plugin-Aktivierung fehlgeschlagen. Anforderungen nicht erfüllt.', 'recruiting-playbook' ) );
			}

			require_once RECPL_PLUGIN_DIR . 'src/Core/Activator.php';
			Core\Activator::activate();
		}
	);

	// Deaktivierung
	register_deactivation_hook(
		RECPL_PLUGIN_FILE,
		function () {
			require_once RECPL_PLUGIN_DIR . 'src/Core/Deactivator.php';
			Core\Deactivator::deactivate();
		}
	);

	// Avada/Fusion Builder Integration FRÜH registrieren.
	// MUSS vor 'after_setup_theme' Priority 10 laufen, wo FusionBuilder startet!
	add_action(
		'after_setup_theme',
		function () {
			// Nur wenn Autoloader verfügbar ist.
			if ( ! class_exists( 'RecruitingPlaybook\\Integrations\\Avada\\AvadaIntegration' ) ) {
				return;
			}

			// Taxonomien VOR Fusion Builder registrieren, damit getTaxonomyOptions()
			// in den Element-Konfigurationen die Terms laden kann.
			// (Normalerweise erst bei init:10, aber Fusion Builder braucht sie bei after_setup_theme:10.)
			if ( class_exists( 'FusionBuilder' ) ) {
				( new \RecruitingPlaybook\Taxonomies\JobCategory() )->register();
				( new \RecruitingPlaybook\Taxonomies\JobLocation() )->register();
				( new \RecruitingPlaybook\Taxonomies\EmploymentType() )->register();
			}

			// Avada Integration registrieren (Hook auf fusion_builder_before_init).
			$avada_integration = new \RecruitingPlaybook\Integrations\Avada\AvadaIntegration();
			$avada_integration->register();
		},
		5
	); // Priority 5 = VOR FusionBuilder (Priority 10)

	// Plugin initialisieren (im init Hook mit Priorität 5 - vor Standard-Hooks)
	add_action(
		'init',
		function () {
			if ( ! rp_check_requirements() ) {
				return;
			}

			// Autoloader muss vorhanden sein
			if ( ! class_exists( 'RecruitingPlaybook\Core\Plugin' ) ) {
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-error"><p>';
						esc_html_e( 'Recruiting Playbook: Bitte führen Sie "composer install" aus.', 'recruiting-playbook' );
						echo '</p></div>';
					}
				);
				return;
			}

			Core\Plugin::get_instance();
		},
		5
	);

} // End else block for Freemius SDK

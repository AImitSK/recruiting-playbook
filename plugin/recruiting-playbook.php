<?php
/**
 * Plugin Name: Recruiting Playbook (Premium)
 * Plugin URI: https://recruiting-playbook.com/
 * Description: Professionelles Bewerbermanagement für WordPress
 * Version: 1.2.17
 * Update URI: https://api.freemius.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Stefan Kühne, Peter Kühne
 * Author URI: https://sk-online-marketing.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: recruiting-playbook
 * Domain Path: /languages
 *
 * @fs_premium_only
 *   /src/Integrations/Elementor/,
 *   /src/Blocks/,
 *   /src/FieldTypes/,
 *   /src/Admin/Pages/KanbanBoard.php,
 *   /src/Admin/Pages/TalentPoolPage.php,
 *   /src/Admin/Pages/ReportingPage.php,
 *   /src/Admin/Pages/EmailSettingsPage.php,
 *   /src/Admin/Pages/FormBuilderPage.php,
 *   /src/Admin/MetaBoxes/JobCustomFieldsMeta.php,
 *   /src/Services/TalentPoolService.php,
 *   /src/Services/NoteService.php,
 *   /src/Services/RatingService.php,
 *   /src/Services/EmailTemplateService.php,
 *   /src/Services/EmailRenderer.php,
 *   /src/Services/EmailService.php,
 *   /src/Services/AutoEmailService.php,
 *   /src/Services/EmailQueueService.php,
 *   /src/Services/CustomFieldsService.php,
 *   /src/Services/CustomFieldFileService.php,
 *   /src/Services/FieldDefinitionService.php,
 *   /src/Services/FormConfigService.php,
 *   /src/Services/FormTemplateService.php,
 *   /src/Services/ApiKeyService.php,
 *   /src/Services/WebhookService.php,
 *   /src/Services/ConversionService.php,
 *   /src/Services/TimeToHireService.php,
 *   /src/Services/StatsService.php,
 *   /src/Services/ExportService.php,
 *   /src/Services/SignatureService.php,
 *   /src/Services/JobAssignmentService.php,
 *   /src/Services/PlaceholderService.php,
 *   /src/Services/SystemStatusService.php,
 *   /src/Services/ActivityService.php,
 *   /src/Api/NoteController.php,
 *   /src/Api/RatingController.php,
 *   /src/Api/ActivityController.php,
 *   /src/Api/TalentPoolController.php,
 *   /src/Api/EmailTemplateController.php,
 *   /src/Api/EmailController.php,
 *   /src/Api/EmailLogController.php,
 *   /src/Api/SignatureController.php,
 *   /src/Api/RoleController.php,
 *   /src/Api/JobAssignmentController.php,
 *   /src/Api/StatsController.php,
 *   /src/Api/ExportController.php,
 *   /src/Api/SystemStatusController.php,
 *   /src/Api/FieldDefinitionController.php,
 *   /src/Api/FormTemplateController.php,
 *   /src/Api/MatchController.php,
 *   /src/Api/FormConfigController.php,
 *   /src/Api/WebhookController.php,
 *   /src/Api/ApiKeyController.php,
 *   /src/Api/AiAnalysisController.php,
 *   /src/Repositories/NoteRepository.php,
 *   /src/Repositories/RatingRepository.php,
 *   /src/Repositories/TalentPoolRepository.php,
 *   /src/Repositories/EmailTemplateRepository.php,
 *   /src/Repositories/EmailLogRepository.php,
 *   /src/Repositories/FieldDefinitionRepository.php,
 *   /src/Repositories/FormConfigRepository.php,
 *   /src/Repositories/FormTemplateRepository.php,
 *   /src/Repositories/JobAssignmentRepository.php,
 *   /src/Repositories/SignatureRepository.php,
 *   /src/Repositories/StatsRepository.php,
 *   /src/Models/FieldDefinition.php,
 *   /src/Models/FormTemplate.php,
 *   /src/Models/FieldValue.php,
 *   /src/Database/Migrations/CustomFieldsMigration.php,
 *   /src/Integrations/Avada/Elements/AiJobFinder.php,
 *   /src/Integrations/Avada/Elements/AiJobMatch.php,
 *   /assets/dist/js/admin-email.js,
 *   /assets/dist/js/admin-email.asset.php,
 *   /assets/dist/js/admin-form-builder.js,
 *   /assets/dist/js/admin-form-builder.asset.php,
 *   /assets/dist/js/blocks.js,
 *   /assets/dist/js/blocks.asset.php,
 *   /assets/dist/js/custom-fields-form.js,
 *   /assets/dist/js/Blocks/,
 *   /assets/dist/css/admin-email.css,
 *   /assets/dist/css/admin-form-builder.css,
 *   /assets/dist/css/admin-kanban.css,
 *   /assets/dist/css/admin-talent-pool.css,
 *   /assets/dist/css/blocks-editor.css,
 *   /assets/dist/css/custom-fields.css,
 *   /assets/dist/css/match-modal.css,
 *   /assets/dist/css/job-finder.css,
 *   /assets/src/js/components/match-modal.js,
 *   /assets/src/js/components/job-finder.js,
 *   /assets/css/elementor-editor.css,
 */

declare(strict_types=1);

namespace RecruitingPlaybook;

// Direkten Zugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Freemius SDK: Premium/Free Version Handling
if ( function_exists( '\rp_fs' ) ) {
	\rp_fs()->set_basename( true, __FILE__ );
} else {
	// Plugin-Konstanten
	define( 'RP_VERSION', '1.2.17' );
	define( 'RP_PLUGIN_FILE', __FILE__ );
	define( 'RP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'RP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'RP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

	// Minimum Requirements
	define( 'RP_MIN_PHP_VERSION', '8.0' );
	define( 'RP_MIN_WP_VERSION', '6.0' );

	// Autoloader
	if ( file_exists( RP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
		require_once RP_PLUGIN_DIR . 'vendor/autoload.php';
	}

	// Freemius SDK initialisieren (für Lizenzierung & Updates).
	if ( file_exists( RP_PLUGIN_DIR . 'freemius.php' ) ) {
		require_once RP_PLUGIN_DIR . 'freemius.php';
	}

	/**
	 * Requirements prüfen
	 */
	function rp_check_requirements(): bool {
		if ( version_compare( PHP_VERSION, RP_MIN_PHP_VERSION, '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>';
					printf(
					/* translators: 1: Required PHP version, 2: Current PHP version */
						esc_html__( 'Recruiting Playbook benötigt PHP %1$s oder höher. Sie nutzen PHP %2$s.', 'recruiting-playbook' ),
						esc_html( RP_MIN_PHP_VERSION ),
						esc_html( PHP_VERSION )
					);
					echo '</p></div>';
				}
			);
			return false;
		}

		global $wp_version;
		if ( version_compare( $wp_version, RP_MIN_WP_VERSION, '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					global $wp_version;
					echo '<div class="notice notice-error"><p>';
					printf(
					/* translators: 1: Required WP version, 2: Current WP version */
						esc_html__( 'Recruiting Playbook benötigt WordPress %1$s oder höher. Sie nutzen WordPress %2$s.', 'recruiting-playbook' ),
						esc_html( RP_MIN_WP_VERSION ),
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
		__FILE__,
		function () {
			if ( ! rp_check_requirements() ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( esc_html__( 'Plugin-Aktivierung fehlgeschlagen. Anforderungen nicht erfüllt.', 'recruiting-playbook' ) );
			}

			require_once RP_PLUGIN_DIR . 'src/Core/Activator.php';
			Core\Activator::activate();
		}
	);

	// Deaktivierung
	register_deactivation_hook(
		__FILE__,
		function () {
			require_once RP_PLUGIN_DIR . 'src/Core/Deactivator.php';
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

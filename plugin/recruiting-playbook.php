<?php
/**
 * Prevent direct access.
 *
 * @package RecruitingPlaybook
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name: Recruiting Playbook
 * Plugin URI: https://recruiting-playbook.com/
 * Description: Professionelles Bewerbermanagement für WordPress
 * Version: 1.2.37
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
 *
 * @package RecruitingPlaybook
 */

// Plugin-Konstanten (WordPress.org: min. 4 Zeichen Prefix).
define( 'RECPL_VERSION', '1.2.34' );
define( 'RECPL_PLUGIN_FILE', __FILE__ );
define( 'RECPL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RECPL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RECPL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'RECPL_MIN_PHP_VERSION', '8.0' );
define( 'RECPL_MIN_WP_VERSION', '6.0' );

// Backwards Compatibility Aliase (für bestehenden Code).
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define( 'RP_VERSION', RECPL_VERSION );
define( 'RP_PLUGIN_FILE', RECPL_PLUGIN_FILE );
define( 'RP_PLUGIN_DIR', RECPL_PLUGIN_DIR );
define( 'RP_PLUGIN_URL', RECPL_PLUGIN_URL );
define( 'RP_PLUGIN_BASENAME', RECPL_PLUGIN_BASENAME );
define( 'RP_MIN_PHP_VERSION', RECPL_MIN_PHP_VERSION );
define( 'RP_MIN_WP_VERSION', RECPL_MIN_WP_VERSION );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

// Bootstrap the plugin (namespace code in separate file for Freemius compatibility).
require_once __DIR__ . '/src/bootstrap.php';

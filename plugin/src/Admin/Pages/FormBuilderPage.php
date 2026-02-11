<?php
/**
 * Form Builder Admin Page
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;
use RecruitingPlaybook\Services\FieldDefinitionService;
use RecruitingPlaybook\Services\FormTemplateService;

/**
 * Form Builder Page Klasse
 */
class FormBuilderPage {

	/**
	 * Render the form builder page
	 */
	public function render(): void {
		// Pro-Feature Check.
		$is_pro         = function_exists( 'rp_is_pro' ) && rp_is_pro();
		$can_manage     = current_user_can( 'rp_manage_forms' );

		// Field Type Registry für verfügbare Typen.
		$registry     = FieldTypeRegistry::getInstance();
		$field_types  = [];

		foreach ( $registry->getAll() as $type_key => $field_type ) {
			$field_types[ $type_key ] = [
				'key'              => $type_key,
				'label'            => $field_type->getLabel(),
				'icon'             => $field_type->getIcon(),
				'group'            => $field_type->getGroup(),
				'defaultSettings'  => $field_type->getDefaultSettings(),
				'validationRules'  => $field_type->getAvailableValidationRules(),
				'supportsOptions'  => $field_type->supportsOptions(),
				'isFileUpload'     => $field_type->isFileUpload(),
			];
		}

		// Aktuelle Felder laden.
		$field_service    = new FieldDefinitionService();
		$current_fields   = $field_service->getAllFields();
		$system_fields    = $field_service->getSystemFields();

		// Templates laden.
		$template_service = new FormTemplateService();
		$templates        = $template_service->getAll();
		$default_template = $template_service->getDefault();

		// Localize data for React component.
		// Use array_values() to ensure numeric-indexed arrays for JavaScript.
		wp_localize_script(
			'rp-admin-form-builder',
			'rpFormBuilderData',
			[
				'isPro'           => $is_pro,
				'canManage'       => $can_manage,
				'fieldTypes'      => $field_types,
				'currentFields'   => array_values( array_map( fn( $f ) => $f->toArray(), $current_fields ) ),
				'systemFields'    => array_values( array_map( fn( $f ) => $f->toArray(), $system_fields ) ),
				'templates'       => array_values( array_map( fn( $t ) => $t->toArray(), $templates ) ),
				'defaultTemplate' => $default_template ? $default_template->toArray() : null,
				'restNamespace'   => 'recruiting/v1',
				'restNonce'       => wp_create_nonce( 'wp_rest' ),
				'upgradeUrl'      => admin_url( 'admin.php?page=rp-license' ),
				'logoUrl'         => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
				'i18n'            => $this->get_translations(),
			]
		);

		?>
		<div class="wrap">
			<div id="rp-form-builder-root"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_assets(): void {
		$asset_file = RP_PLUGIN_DIR . 'assets/dist/js/admin-form-builder.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;
		} else {
			$asset = [
				'dependencies' => [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ],
				'version'      => RP_VERSION,
			];
		}

		wp_enqueue_script(
			'rp-admin-form-builder',
			RP_PLUGIN_URL . 'assets/dist/js/admin-form-builder.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Load admin.css first for Tailwind utilities and CSS variables.
		wp_enqueue_style(
			'rp-admin',
			RP_PLUGIN_URL . 'assets/dist/css/admin.css',
			[ 'wp-components' ],
			$asset['version']
		);

		wp_enqueue_style(
			'rp-admin-form-builder',
			RP_PLUGIN_URL . 'assets/dist/css/admin-form-builder.css',
			[ 'rp-admin' ],
			$asset['version']
		);
	}

	/**
	 * Get translations for JavaScript
	 *
	 * @return array<string, string>
	 */
	private function get_translations(): array {
		return [
			// Page.
			'pageTitle'              => __( 'Form Builder', 'recruiting-playbook' ),
			'pageDescription'        => __( 'Manage application form fields and templates', 'recruiting-playbook' ),

			// Tabs.
			'tabFields'              => __( 'Fields', 'recruiting-playbook' ),
			'tabTemplates'           => __( 'Templates', 'recruiting-playbook' ),
			'tabPreview'             => __( 'Preview', 'recruiting-playbook' ),
			'tabSettings'            => __( 'Settings', 'recruiting-playbook' ),

			// Field List.
			'fieldList'              => __( 'Field List', 'recruiting-playbook' ),
			'systemFields'           => __( 'System Fields', 'recruiting-playbook' ),
			'customFields'           => __( 'Custom Fields', 'recruiting-playbook' ),
			'addField'               => __( 'Add Field', 'recruiting-playbook' ),
			'noFields'               => __( 'No fields available', 'recruiting-playbook' ),
			'dragToReorder'          => __( 'Drag to reorder', 'recruiting-playbook' ),

			// Field Types.
			'selectFieldType'        => __( 'Select field type', 'recruiting-playbook' ),
			'categoryBasic'          => __( 'Basic Fields', 'recruiting-playbook' ),
			'categoryChoice'         => __( 'Choice Fields', 'recruiting-playbook' ),
			'categoryAdvanced'       => __( 'Advanced Fields', 'recruiting-playbook' ),
			'categoryLayout'         => __( 'Layout Elements', 'recruiting-playbook' ),

			// Field Editor.
			'editField'              => __( 'Edit Field', 'recruiting-playbook' ),
			'fieldKey'               => __( 'Field Key', 'recruiting-playbook' ),
			'fieldKeyHelp'           => __( 'Unique identifier (no special characters)', 'recruiting-playbook' ),
			'fieldLabel'             => __( 'Label', 'recruiting-playbook' ),
			'fieldPlaceholder'       => __( 'Placeholder', 'recruiting-playbook' ),
			'fieldDescription'       => __( 'Description', 'recruiting-playbook' ),
			'fieldRequired'          => __( 'Required', 'recruiting-playbook' ),
			'fieldEnabled'           => __( 'Enabled', 'recruiting-playbook' ),
			'fieldWidth'             => __( 'Width', 'recruiting-playbook' ),
			'widthFull'              => __( 'Full Width', 'recruiting-playbook' ),
			'widthHalf'              => __( 'Half Width', 'recruiting-playbook' ),
			'widthThird'             => __( 'One Third', 'recruiting-playbook' ),
			'widthTwoThirds'         => __( 'Two Thirds', 'recruiting-playbook' ),

			// Validation.
			'validation'             => __( 'Validation', 'recruiting-playbook' ),
			'minLength'              => __( 'Minimum Length', 'recruiting-playbook' ),
			'maxLength'              => __( 'Maximum Length', 'recruiting-playbook' ),
			'minValue'               => __( 'Minimum Value', 'recruiting-playbook' ),
			'maxValue'               => __( 'Maximum Value', 'recruiting-playbook' ),
			'pattern'                => __( 'Regex Pattern', 'recruiting-playbook' ),
			'customError'            => __( 'Error Message', 'recruiting-playbook' ),

			// Options.
			'options'                => __( 'Options', 'recruiting-playbook' ),
			'addOption'              => __( 'Add Option', 'recruiting-playbook' ),
			'optionValue'            => __( 'Value', 'recruiting-playbook' ),
			'optionLabel'            => __( 'Label', 'recruiting-playbook' ),
			'removeOption'           => __( 'Remove Option', 'recruiting-playbook' ),
			'defaultValue'           => __( 'Default Value', 'recruiting-playbook' ),
			'allowOther'             => __( 'Allow "Other"', 'recruiting-playbook' ),

			// Conditional Logic.
			'conditional'            => __( 'Conditional Display', 'recruiting-playbook' ),
			'conditionalEnable'      => __( 'Enable conditional display', 'recruiting-playbook' ),
			'conditionalHelp'        => __( 'Show this field only when...', 'recruiting-playbook' ),
			'showWhen'               => __( 'Show when', 'recruiting-playbook' ),
			'hideWhen'               => __( 'Hide when', 'recruiting-playbook' ),
			'conditionField'         => __( 'Field', 'recruiting-playbook' ),
			'conditionOperator'      => __( 'Operator', 'recruiting-playbook' ),
			'conditionValue'         => __( 'Value', 'recruiting-playbook' ),
			'conditionLogic'         => __( 'Logic', 'recruiting-playbook' ),
			'conditionAnd'           => __( 'AND', 'recruiting-playbook' ),
			'conditionOr'            => __( 'OR', 'recruiting-playbook' ),
			'addCondition'           => __( 'Add Condition', 'recruiting-playbook' ),
			'removeCondition'        => __( 'Remove Condition', 'recruiting-playbook' ),

			// Operators.
			'opEquals'               => __( 'equals', 'recruiting-playbook' ),
			'opNotEquals'            => __( 'does not equal', 'recruiting-playbook' ),
			'opContains'             => __( 'contains', 'recruiting-playbook' ),
			'opNotContains'          => __( 'does not contain', 'recruiting-playbook' ),
			'opEmpty'                => __( 'is empty', 'recruiting-playbook' ),
			'opNotEmpty'             => __( 'is not empty', 'recruiting-playbook' ),
			'opChecked'              => __( 'is checked', 'recruiting-playbook' ),
			'opNotChecked'           => __( 'is not checked', 'recruiting-playbook' ),
			'opGreaterThan'          => __( 'greater than', 'recruiting-playbook' ),
			'opLessThan'             => __( 'less than', 'recruiting-playbook' ),
			'opStartsWith'           => __( 'starts with', 'recruiting-playbook' ),
			'opEndsWith'             => __( 'ends with', 'recruiting-playbook' ),

			// File Upload Settings.
			'fileSettings'           => __( 'File Settings', 'recruiting-playbook' ),
			'allowedTypes'           => __( 'Allowed File Types', 'recruiting-playbook' ),
			'maxFileSize'            => __( 'Maximum File Size (MB)', 'recruiting-playbook' ),
			'maxFiles'               => __( 'Maximum Number of Files', 'recruiting-playbook' ),
			'dragDropText'           => __( 'Drag files here', 'recruiting-playbook' ),

			// Templates.
			'templates'              => __( 'Form Templates', 'recruiting-playbook' ),
			'createTemplate'         => __( 'Create Template', 'recruiting-playbook' ),
			'editTemplate'           => __( 'Edit Template', 'recruiting-playbook' ),
			'duplicateTemplate'      => __( 'Duplicate Template', 'recruiting-playbook' ),
			'deleteTemplate'         => __( 'Delete Template', 'recruiting-playbook' ),
			'templateName'           => __( 'Template Name', 'recruiting-playbook' ),
			'templateDescription'    => __( 'Description', 'recruiting-playbook' ),
			'templateIsDefault'      => __( 'Default Template', 'recruiting-playbook' ),
			'templateSetDefault'     => __( 'Set as Default', 'recruiting-playbook' ),
			'defaultTemplateInfo'    => __( 'The default template will be used for new jobs', 'recruiting-playbook' ),
			'noTemplates'            => __( 'No templates available', 'recruiting-playbook' ),
			'templateFields'         => __( 'Template Fields', 'recruiting-playbook' ),
			'selectFields'           => __( 'Select Fields', 'recruiting-playbook' ),

			// Preview.
			'preview'                => __( 'Form Preview', 'recruiting-playbook' ),
			'previewDescription'     => __( 'This is how the application form will look', 'recruiting-playbook' ),
			'previewMode'            => __( 'View', 'recruiting-playbook' ),
			'previewDesktop'         => __( 'Desktop', 'recruiting-playbook' ),
			'previewTablet'          => __( 'Tablet', 'recruiting-playbook' ),
			'previewMobile'          => __( 'Mobile', 'recruiting-playbook' ),

			// Actions.
			'save'                   => __( 'Save', 'recruiting-playbook' ),
			'cancel'                 => __( 'Cancel', 'recruiting-playbook' ),
			'delete'                 => __( 'Delete', 'recruiting-playbook' ),
			'duplicate'              => __( 'Duplicate', 'recruiting-playbook' ),
			'edit'                   => __( 'Edit', 'recruiting-playbook' ),
			'close'                  => __( 'Close', 'recruiting-playbook' ),
			'confirmDelete'          => __( 'Really delete?', 'recruiting-playbook' ),
			'confirmDeleteField'     => __( 'Do you really want to delete this field? Existing data will be preserved.', 'recruiting-playbook' ),
			'confirmDeleteTemplate'  => __( 'Do you really want to delete this template?', 'recruiting-playbook' ),

			// Status Messages.
			'saving'                 => __( 'Saving...', 'recruiting-playbook' ),
			'saved'                  => __( 'Saved', 'recruiting-playbook' ),
			'saveError'              => __( 'Error saving', 'recruiting-playbook' ),
			'loading'                => __( 'Loading...', 'recruiting-playbook' ),
			'error'                  => __( 'Error', 'recruiting-playbook' ),
			'success'                => __( 'Success', 'recruiting-playbook' ),
			'fieldCreated'           => __( 'Field created', 'recruiting-playbook' ),
			'fieldUpdated'           => __( 'Field updated', 'recruiting-playbook' ),
			'fieldDeleted'           => __( 'Field deleted', 'recruiting-playbook' ),
			'orderUpdated'           => __( 'Order updated', 'recruiting-playbook' ),
			'templateCreated'        => __( 'Template created', 'recruiting-playbook' ),
			'templateUpdated'        => __( 'Template updated', 'recruiting-playbook' ),
			'templateDeleted'        => __( 'Template deleted', 'recruiting-playbook' ),

			// Warnings.
			'systemFieldWarning'     => __( 'System fields cannot be deleted', 'recruiting-playbook' ),
			'requiredFieldWarning'   => __( 'Required fields must be filled out', 'recruiting-playbook' ),
			'duplicateKeyWarning'    => __( 'This field key already exists', 'recruiting-playbook' ),
			'invalidKeyWarning'      => __( 'Invalid field key (only letters, numbers, underscores)', 'recruiting-playbook' ),

			// Pro Features.
			'proFeature'             => __( 'Pro Feature', 'recruiting-playbook' ),
			'proRequired'            => __( 'This feature requires Pro', 'recruiting-playbook' ),
			'upgradeToPro'           => __( 'Upgrade to Pro', 'recruiting-playbook' ),
			'conditionalLogicPro'    => __( 'Conditional Logic (Pro)', 'recruiting-playbook' ),
			'advancedFieldsPro'      => __( 'Advanced Field Types (Pro)', 'recruiting-playbook' ),
			'multipleTemplatesPro'   => __( 'Multiple Templates (Pro)', 'recruiting-playbook' ),

			// Help.
			'help'                   => __( 'Help', 'recruiting-playbook' ),
			'documentation'          => __( 'Documentation', 'recruiting-playbook' ),
			'fieldKeyTip'            => __( 'The field key is used for the API and export', 'recruiting-playbook' ),
			'conditionalTip'         => __( 'Conditional display allows you to show/hide fields based on other field values', 'recruiting-playbook' ),
			'templateTip'            => __( 'Templates enable different form configurations for different jobs', 'recruiting-playbook' ),
		];
	}
}

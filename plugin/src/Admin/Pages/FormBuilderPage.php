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
				'i18n'            => $this->get_translations(),
			]
		);

		?>
		<div class="wrap rp-admin rp-form-builder">
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

		wp_enqueue_style(
			'rp-admin-form-builder',
			RP_PLUGIN_URL . 'assets/dist/css/admin-form-builder.css',
			[ 'wp-components' ],
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
			'pageTitle'              => __( 'Formular-Builder', 'recruiting-playbook' ),
			'pageDescription'        => __( 'Bewerbungsformular-Felder und Templates verwalten', 'recruiting-playbook' ),

			// Tabs.
			'tabFields'              => __( 'Felder', 'recruiting-playbook' ),
			'tabTemplates'           => __( 'Templates', 'recruiting-playbook' ),
			'tabPreview'             => __( 'Vorschau', 'recruiting-playbook' ),
			'tabSettings'            => __( 'Einstellungen', 'recruiting-playbook' ),

			// Field List.
			'fieldList'              => __( 'Feld-Liste', 'recruiting-playbook' ),
			'systemFields'           => __( 'System-Felder', 'recruiting-playbook' ),
			'customFields'           => __( 'Eigene Felder', 'recruiting-playbook' ),
			'addField'               => __( 'Feld hinzufügen', 'recruiting-playbook' ),
			'noFields'               => __( 'Keine Felder vorhanden', 'recruiting-playbook' ),
			'dragToReorder'          => __( 'Ziehen zum Sortieren', 'recruiting-playbook' ),

			// Field Types.
			'selectFieldType'        => __( 'Feldtyp wählen', 'recruiting-playbook' ),
			'categoryBasic'          => __( 'Basis-Felder', 'recruiting-playbook' ),
			'categoryChoice'         => __( 'Auswahl-Felder', 'recruiting-playbook' ),
			'categoryAdvanced'       => __( 'Erweiterte Felder', 'recruiting-playbook' ),
			'categoryLayout'         => __( 'Layout-Elemente', 'recruiting-playbook' ),

			// Field Editor.
			'editField'              => __( 'Feld bearbeiten', 'recruiting-playbook' ),
			'fieldKey'               => __( 'Feldschlüssel', 'recruiting-playbook' ),
			'fieldKeyHelp'           => __( 'Eindeutiger Bezeichner (keine Sonderzeichen)', 'recruiting-playbook' ),
			'fieldLabel'             => __( 'Label', 'recruiting-playbook' ),
			'fieldPlaceholder'       => __( 'Platzhalter', 'recruiting-playbook' ),
			'fieldDescription'       => __( 'Beschreibung', 'recruiting-playbook' ),
			'fieldRequired'          => __( 'Pflichtfeld', 'recruiting-playbook' ),
			'fieldEnabled'           => __( 'Aktiviert', 'recruiting-playbook' ),
			'fieldWidth'             => __( 'Breite', 'recruiting-playbook' ),
			'widthFull'              => __( 'Volle Breite', 'recruiting-playbook' ),
			'widthHalf'              => __( 'Halbe Breite', 'recruiting-playbook' ),
			'widthThird'             => __( 'Drittel', 'recruiting-playbook' ),
			'widthTwoThirds'         => __( 'Zwei Drittel', 'recruiting-playbook' ),

			// Validation.
			'validation'             => __( 'Validierung', 'recruiting-playbook' ),
			'minLength'              => __( 'Minimale Länge', 'recruiting-playbook' ),
			'maxLength'              => __( 'Maximale Länge', 'recruiting-playbook' ),
			'minValue'               => __( 'Minimalwert', 'recruiting-playbook' ),
			'maxValue'               => __( 'Maximalwert', 'recruiting-playbook' ),
			'pattern'                => __( 'Regex-Pattern', 'recruiting-playbook' ),
			'customError'            => __( 'Fehlermeldung', 'recruiting-playbook' ),

			// Options.
			'options'                => __( 'Optionen', 'recruiting-playbook' ),
			'addOption'              => __( 'Option hinzufügen', 'recruiting-playbook' ),
			'optionValue'            => __( 'Wert', 'recruiting-playbook' ),
			'optionLabel'            => __( 'Bezeichnung', 'recruiting-playbook' ),
			'removeOption'           => __( 'Option entfernen', 'recruiting-playbook' ),
			'defaultValue'           => __( 'Standardwert', 'recruiting-playbook' ),
			'allowOther'             => __( '"Sonstiges" erlauben', 'recruiting-playbook' ),

			// Conditional Logic.
			'conditional'            => __( 'Bedingte Anzeige', 'recruiting-playbook' ),
			'conditionalEnable'      => __( 'Bedingte Anzeige aktivieren', 'recruiting-playbook' ),
			'conditionalHelp'        => __( 'Dieses Feld nur anzeigen, wenn...', 'recruiting-playbook' ),
			'showWhen'               => __( 'Anzeigen wenn', 'recruiting-playbook' ),
			'hideWhen'               => __( 'Ausblenden wenn', 'recruiting-playbook' ),
			'conditionField'         => __( 'Feld', 'recruiting-playbook' ),
			'conditionOperator'      => __( 'Operator', 'recruiting-playbook' ),
			'conditionValue'         => __( 'Wert', 'recruiting-playbook' ),
			'conditionLogic'         => __( 'Verknüpfung', 'recruiting-playbook' ),
			'conditionAnd'           => __( 'UND', 'recruiting-playbook' ),
			'conditionOr'            => __( 'ODER', 'recruiting-playbook' ),
			'addCondition'           => __( 'Bedingung hinzufügen', 'recruiting-playbook' ),
			'removeCondition'        => __( 'Bedingung entfernen', 'recruiting-playbook' ),

			// Operators.
			'opEquals'               => __( 'ist gleich', 'recruiting-playbook' ),
			'opNotEquals'            => __( 'ist nicht gleich', 'recruiting-playbook' ),
			'opContains'             => __( 'enthält', 'recruiting-playbook' ),
			'opNotContains'          => __( 'enthält nicht', 'recruiting-playbook' ),
			'opEmpty'                => __( 'ist leer', 'recruiting-playbook' ),
			'opNotEmpty'             => __( 'ist nicht leer', 'recruiting-playbook' ),
			'opChecked'              => __( 'ist aktiviert', 'recruiting-playbook' ),
			'opNotChecked'           => __( 'ist nicht aktiviert', 'recruiting-playbook' ),
			'opGreaterThan'          => __( 'größer als', 'recruiting-playbook' ),
			'opLessThan'             => __( 'kleiner als', 'recruiting-playbook' ),
			'opStartsWith'           => __( 'beginnt mit', 'recruiting-playbook' ),
			'opEndsWith'             => __( 'endet mit', 'recruiting-playbook' ),

			// File Upload Settings.
			'fileSettings'           => __( 'Datei-Einstellungen', 'recruiting-playbook' ),
			'allowedTypes'           => __( 'Erlaubte Dateitypen', 'recruiting-playbook' ),
			'maxFileSize'            => __( 'Maximale Dateigröße (MB)', 'recruiting-playbook' ),
			'maxFiles'               => __( 'Maximale Anzahl Dateien', 'recruiting-playbook' ),
			'dragDropText'           => __( 'Dateien hierher ziehen', 'recruiting-playbook' ),

			// Templates.
			'templates'              => __( 'Formular-Templates', 'recruiting-playbook' ),
			'createTemplate'         => __( 'Template erstellen', 'recruiting-playbook' ),
			'editTemplate'           => __( 'Template bearbeiten', 'recruiting-playbook' ),
			'duplicateTemplate'      => __( 'Template duplizieren', 'recruiting-playbook' ),
			'deleteTemplate'         => __( 'Template löschen', 'recruiting-playbook' ),
			'templateName'           => __( 'Template-Name', 'recruiting-playbook' ),
			'templateDescription'    => __( 'Beschreibung', 'recruiting-playbook' ),
			'templateIsDefault'      => __( 'Standard-Template', 'recruiting-playbook' ),
			'templateSetDefault'     => __( 'Als Standard setzen', 'recruiting-playbook' ),
			'defaultTemplateInfo'    => __( 'Das Standard-Template wird für neue Stellen verwendet', 'recruiting-playbook' ),
			'noTemplates'            => __( 'Keine Templates vorhanden', 'recruiting-playbook' ),
			'templateFields'         => __( 'Template-Felder', 'recruiting-playbook' ),
			'selectFields'           => __( 'Felder auswählen', 'recruiting-playbook' ),

			// Preview.
			'preview'                => __( 'Formular-Vorschau', 'recruiting-playbook' ),
			'previewDescription'     => __( 'So wird das Bewerbungsformular aussehen', 'recruiting-playbook' ),
			'previewMode'            => __( 'Ansicht', 'recruiting-playbook' ),
			'previewDesktop'         => __( 'Desktop', 'recruiting-playbook' ),
			'previewTablet'          => __( 'Tablet', 'recruiting-playbook' ),
			'previewMobile'          => __( 'Mobil', 'recruiting-playbook' ),

			// Actions.
			'save'                   => __( 'Speichern', 'recruiting-playbook' ),
			'cancel'                 => __( 'Abbrechen', 'recruiting-playbook' ),
			'delete'                 => __( 'Löschen', 'recruiting-playbook' ),
			'duplicate'              => __( 'Duplizieren', 'recruiting-playbook' ),
			'edit'                   => __( 'Bearbeiten', 'recruiting-playbook' ),
			'close'                  => __( 'Schließen', 'recruiting-playbook' ),
			'confirmDelete'          => __( 'Wirklich löschen?', 'recruiting-playbook' ),
			'confirmDeleteField'     => __( 'Möchten Sie dieses Feld wirklich löschen? Vorhandene Daten bleiben erhalten.', 'recruiting-playbook' ),
			'confirmDeleteTemplate'  => __( 'Möchten Sie dieses Template wirklich löschen?', 'recruiting-playbook' ),

			// Status Messages.
			'saving'                 => __( 'Speichern...', 'recruiting-playbook' ),
			'saved'                  => __( 'Gespeichert', 'recruiting-playbook' ),
			'saveError'              => __( 'Fehler beim Speichern', 'recruiting-playbook' ),
			'loading'                => __( 'Laden...', 'recruiting-playbook' ),
			'error'                  => __( 'Fehler', 'recruiting-playbook' ),
			'success'                => __( 'Erfolgreich', 'recruiting-playbook' ),
			'fieldCreated'           => __( 'Feld erstellt', 'recruiting-playbook' ),
			'fieldUpdated'           => __( 'Feld aktualisiert', 'recruiting-playbook' ),
			'fieldDeleted'           => __( 'Feld gelöscht', 'recruiting-playbook' ),
			'orderUpdated'           => __( 'Reihenfolge aktualisiert', 'recruiting-playbook' ),
			'templateCreated'        => __( 'Template erstellt', 'recruiting-playbook' ),
			'templateUpdated'        => __( 'Template aktualisiert', 'recruiting-playbook' ),
			'templateDeleted'        => __( 'Template gelöscht', 'recruiting-playbook' ),

			// Warnings.
			'systemFieldWarning'     => __( 'System-Felder können nicht gelöscht werden', 'recruiting-playbook' ),
			'requiredFieldWarning'   => __( 'Pflichtfelder müssen ausgefüllt werden', 'recruiting-playbook' ),
			'duplicateKeyWarning'    => __( 'Dieser Feldschlüssel existiert bereits', 'recruiting-playbook' ),
			'invalidKeyWarning'      => __( 'Ungültiger Feldschlüssel (nur Buchstaben, Zahlen, Unterstriche)', 'recruiting-playbook' ),

			// Pro Features.
			'proFeature'             => __( 'Pro-Feature', 'recruiting-playbook' ),
			'proRequired'            => __( 'Diese Funktion erfordert Pro', 'recruiting-playbook' ),
			'upgradeToPro'           => __( 'Auf Pro upgraden', 'recruiting-playbook' ),
			'conditionalLogicPro'    => __( 'Bedingte Logik (Pro)', 'recruiting-playbook' ),
			'advancedFieldsPro'      => __( 'Erweiterte Feldtypen (Pro)', 'recruiting-playbook' ),
			'multipleTemplatesPro'   => __( 'Mehrere Templates (Pro)', 'recruiting-playbook' ),

			// Help.
			'help'                   => __( 'Hilfe', 'recruiting-playbook' ),
			'documentation'          => __( 'Dokumentation', 'recruiting-playbook' ),
			'fieldKeyTip'            => __( 'Der Feldschlüssel wird für die API und den Export verwendet', 'recruiting-playbook' ),
			'conditionalTip'         => __( 'Mit bedingter Anzeige können Sie Felder basierend auf anderen Feldwerten ein-/ausblenden', 'recruiting-playbook' ),
			'templateTip'            => __( 'Templates ermöglichen verschiedene Formular-Konfigurationen für unterschiedliche Stellen', 'recruiting-playbook' ),
		];
	}
}

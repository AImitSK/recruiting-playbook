<?php
/**
 * Form Render Service
 *
 * Rendert Bewerbungsformulare mit dynamischen Feldern.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use RecruitingPlaybook\FieldTypes\FieldTypeRegistry;

/**
 * Service zum Rendern von Formularen
 */
class FormRenderService {

	/**
	 * FieldDefinitionService
	 *
	 * @var FieldDefinitionService
	 */
	private FieldDefinitionService $field_service;

	/**
	 * FormTemplateService
	 *
	 * @var FormTemplateService
	 */
	private FormTemplateService $template_service;

	/**
	 * ConditionalScriptGenerator
	 *
	 * @var ConditionalScriptGenerator
	 */
	private ConditionalScriptGenerator $conditional_generator;

	/**
	 * Konstruktor
	 *
	 * @param FieldDefinitionService|null     $field_service         Optional: Field-Service.
	 * @param FormTemplateService|null        $template_service      Optional: Template-Service.
	 * @param ConditionalScriptGenerator|null $conditional_generator Optional: Conditional-Generator.
	 */
	public function __construct(
		?FieldDefinitionService $field_service = null,
		?FormTemplateService $template_service = null,
		?ConditionalScriptGenerator $conditional_generator = null
	) {
		$this->field_service         = $field_service ?? new FieldDefinitionService();
		$this->template_service      = $template_service ?? new FormTemplateService();
		$this->conditional_generator = $conditional_generator ?? new ConditionalScriptGenerator();
	}

	/**
	 * Formular für Job rendern
	 *
	 * @param int   $job_id       Job-ID.
	 * @param array $initial_data Initiale Formulardaten.
	 * @return string HTML des Formulars.
	 */
	public function renderForm( int $job_id, array $initial_data = [] ): string {
		$fields = $this->field_service->getFieldsForJob( $job_id );

		if ( empty( $fields ) ) {
			$fields = $this->field_service->getActiveFields();
		}

		// Nur aktivierte Felder.
		$fields = array_filter( $fields, fn( $f ) => $f->isEnabled() );

		// Nach Sortierung ordnen.
		usort( $fields, fn( $a, $b ) => $a->getSortOrder() <=> $b->getSortOrder() );

		return $this->renderFields( $fields, $job_id, $initial_data );
	}

	/**
	 * Felder rendern
	 *
	 * @param FieldDefinition[] $fields       Felddefinitionen.
	 * @param int               $job_id       Job-ID.
	 * @param array             $initial_data Initiale Daten.
	 * @return string HTML der Felder.
	 */
	public function renderFields( array $fields, int $job_id, array $initial_data = [] ): string {
		$output = '';

		// Conditional Logic CSS.
		$output .= $this->conditional_generator->generateHiddenStyles();

		// Alpine.js Komponente und Konfiguration.
		$output .= $this->renderAlpineConfig( $fields, $job_id, $initial_data );

		// Formular-Container.
		$output .= '<div class="rp-form rp-custom-fields-form" x-data="rpCustomFieldsForm()" x-cloak>';

		// Felder-Grid.
		$output .= '<div class="rp-form__fields rp-grid rp-grid-cols-1 md:rp-grid-cols-2 rp-gap-4">';

		foreach ( $fields as $field ) {
			$output .= $this->renderField( $field, $initial_data );
		}

		$output .= '</div>'; // .rp-form__fields
		$output .= '</div>'; // .rp-form

		return $output;
	}

	/**
	 * Einzelnes Feld rendern
	 *
	 * @param FieldDefinition $field        Felddefinition.
	 * @param array           $initial_data Initiale Daten.
	 * @return string HTML des Feldes.
	 */
	public function renderField( FieldDefinition $field, array $initial_data = [] ): string {
		$registry  = FieldTypeRegistry::getInstance();
		$fieldType = $registry->get( $field->getType() );

		if ( ! $fieldType ) {
			return '';
		}

		// Field wrapper.
		$wrapper_classes = $this->getFieldWrapperClasses( $field );
		$x_show          = $this->conditional_generator->generateXShow( $field );
		$x_show_attr     = $x_show ? sprintf( ' x-show="%s"', esc_attr( $x_show ) ) : '';

		$output = sprintf(
			'<div class="%s" data-field-key="%s"%s>',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			esc_attr( $field->getFieldKey() ),
			$x_show_attr
		);

		// Template laden.
		$template_file = $this->getFieldTemplate( $field->getType() );

		if ( file_exists( $template_file ) ) {
			ob_start();
			$this->includeFieldTemplate( $template_file, $field, $initial_data );
			$output .= ob_get_clean();
		} else {
			// Fallback: Standard-Rendering.
			$output .= $this->renderFieldDefault( $field, $initial_data );
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Field Template einbinden
	 *
	 * @param string          $template_file Pfad zum Template.
	 * @param FieldDefinition $field         Felddefinition.
	 * @param array           $initial_data  Initiale Daten.
	 */
	private function includeFieldTemplate( string $template_file, FieldDefinition $field, array $initial_data ): void {
		// Variablen für Template bereitstellen.
		$field_key   = $field->getFieldKey();
		$field_type  = $field->getType();
		$label       = $field->getLabel();
		$placeholder = $field->getPlaceholder();
		$description = $field->getDescription();
		$is_required = $field->isRequired();
		$settings    = $field->getSettings() ?? [];
		$validation  = $field->getValidation() ?? [];
		$value       = $initial_data[ $field_key ] ?? '';

		// x-model für Alpine.js.
		$x_model = sprintf( 'formData.%s', $field_key );

		// Include Template.
		include $template_file;
	}

	/**
	 * Standard-Feldrendering (Fallback)
	 *
	 * @param FieldDefinition $field        Felddefinition.
	 * @param array           $initial_data Initiale Daten.
	 * @return string HTML.
	 */
	private function renderFieldDefault( FieldDefinition $field, array $initial_data ): string {
		$field_key   = $field->getFieldKey();
		$label       = $field->getLabel();
		$is_required = $field->isRequired();
		$value       = $initial_data[ $field_key ] ?? '';

		$output = '';

		// Label.
		$output .= sprintf(
			'<label class="rp-label" for="rp-field-%s">%s%s</label>',
			esc_attr( $field_key ),
			esc_html( $label ),
			$is_required ? ' <span class="rp-text-error">*</span>' : ''
		);

		// Input.
		$output .= sprintf(
			'<input type="text" id="rp-field-%1$s" name="%1$s" x-model="formData.%1$s" class="rp-input" :class="errors.%1$s ? \'rp-input-error\' : \'\'"%2$s>',
			esc_attr( $field_key ),
			$is_required ? ' required' : ''
		);

		// Error.
		$output .= sprintf(
			'<p x-show="errors.%1$s" x-text="errors.%1$s" class="rp-error-text"></p>',
			esc_attr( $field_key )
		);

		return $output;
	}

	/**
	 * Template-Pfad für Feldtyp ermitteln
	 *
	 * @param string $field_type Feldtyp.
	 * @return string Pfad zum Template.
	 */
	private function getFieldTemplate( string $field_type ): string {
		// Theme kann Templates überschreiben.
		$theme_template = get_stylesheet_directory() . '/recruiting-playbook/fields/field-' . $field_type . '.php';

		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}

		// Plugin-Template.
		return RP_PLUGIN_DIR . 'templates/fields/field-' . $field_type . '.php';
	}

	/**
	 * Wrapper-Klassen für Feld ermitteln
	 *
	 * @param FieldDefinition $field Felddefinition.
	 * @return string[] CSS-Klassen.
	 */
	private function getFieldWrapperClasses( FieldDefinition $field ): array {
		$classes = [
			'rp-form__field',
			'rp-form__field--' . $field->getType(),
		];

		// Breite.
		$settings = $field->getSettings() ?? [];
		$width    = $settings['width'] ?? 'full';

		switch ( $width ) {
			case 'half':
				$classes[] = 'md:rp-col-span-1';
				break;
			case 'third':
				$classes[] = 'md:rp-col-span-1';
				break;
			case 'two-thirds':
				$classes[] = 'md:rp-col-span-2';
				break;
			default:
				$classes[] = 'rp-col-span-full';
		}

		// Pflichtfeld.
		if ( $field->isRequired() ) {
			$classes[] = 'rp-form__field--required';
		}

		// System-Feld.
		if ( $field->isSystem() ) {
			$classes[] = 'rp-form__field--system';
		}

		return $classes;
	}

	/**
	 * Alpine.js Konfiguration rendern
	 *
	 * @param FieldDefinition[] $fields       Felder.
	 * @param int               $job_id       Job-ID.
	 * @param array             $initial_data Initiale Daten.
	 * @return string Script-Tag.
	 */
	private function renderAlpineConfig( array $fields, int $job_id, array $initial_data ): string {
		// FormData-Objekt aufbauen.
		$form_data = [ 'job_id' => $job_id ];

		foreach ( $fields as $field ) {
			$key              = $field->getFieldKey();
			$form_data[ $key ] = $initial_data[ $key ] ?? $this->getDefaultValue( $field );
		}

		// Validierungsregeln sammeln.
		$validation_rules = [];
		foreach ( $fields as $field ) {
			$rules = [];

			if ( $field->isRequired() ) {
				$rules['required'] = true;
			}

			$validation = $field->getValidation() ?? [];

			if ( ! empty( $validation['min_length'] ) ) {
				$rules['minLength'] = (int) $validation['min_length'];
			}
			if ( ! empty( $validation['max_length'] ) ) {
				$rules['maxLength'] = (int) $validation['max_length'];
			}
			if ( ! empty( $validation['pattern'] ) ) {
				$rules['pattern'] = $validation['pattern'];
			}
			if ( ! empty( $validation['min_value'] ) ) {
				$rules['min'] = (float) $validation['min_value'];
			}
			if ( ! empty( $validation['max_value'] ) ) {
				$rules['max'] = (float) $validation['max_value'];
			}

			// Feldtyp-spezifische Regeln.
			switch ( $field->getType() ) {
				case 'email':
					$rules['email'] = true;
					break;
				case 'url':
					$rules['url'] = true;
					break;
				case 'phone':
					$rules['phone'] = true;
					break;
			}

			if ( ! empty( $rules ) ) {
				$validation_rules[ $field->getFieldKey() ] = $rules;
			}
		}

		// Conditional Config.
		$conditional_config = $this->conditional_generator->generateConfig( $fields );

		// Config-Objekt.
		$config = [
			'formData'    => $form_data,
			'validation'  => $validation_rules,
			'conditional' => $conditional_config,
			'i18n'        => $this->getI18n(),
		];

		$json = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT );

		return sprintf(
			'<script>window.rpCustomFieldsConfig = %s;</script>',
			$json
		);
	}

	/**
	 * Standard-Wert für Feld ermitteln
	 *
	 * @param FieldDefinition $field Felddefinition.
	 * @return mixed Standard-Wert.
	 */
	private function getDefaultValue( FieldDefinition $field ): mixed {
		$settings = $field->getSettings() ?? [];

		// Expliziter Default-Wert.
		if ( isset( $settings['default_value'] ) ) {
			return $settings['default_value'];
		}

		// Typ-basierter Default.
		return match ( $field->getType() ) {
			'checkbox' => ( $settings['mode'] ?? 'single' ) === 'multi' ? [] : false,
			'file'     => [],
			'number'   => null,
			'select', 'radio' => '',
			default    => '',
		};
	}

	/**
	 * Übersetzungen für Frontend
	 *
	 * @return array<string, string>
	 */
	private function getI18n(): array {
		return [
			'required'           => __( 'Dieses Feld ist erforderlich', 'recruiting-playbook' ),
			'invalidEmail'       => __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein', 'recruiting-playbook' ),
			'invalidUrl'         => __( 'Bitte geben Sie eine gültige URL ein', 'recruiting-playbook' ),
			'invalidPhone'       => __( 'Bitte geben Sie eine gültige Telefonnummer ein', 'recruiting-playbook' ),
			'minLength'          => __( 'Mindestens %d Zeichen erforderlich', 'recruiting-playbook' ),
			'maxLength'          => __( 'Maximal %d Zeichen erlaubt', 'recruiting-playbook' ),
			'minValue'           => __( 'Der Wert muss mindestens %s sein', 'recruiting-playbook' ),
			'maxValue'           => __( 'Der Wert darf höchstens %s sein', 'recruiting-playbook' ),
			'patternMismatch'    => __( 'Das Format ist ungültig', 'recruiting-playbook' ),
			'fileTooLarge'       => __( 'Die Datei ist zu groß (max. %d MB)', 'recruiting-playbook' ),
			'invalidFileType'    => __( 'Dieser Dateityp ist nicht erlaubt', 'recruiting-playbook' ),
			'maxFilesExceeded'   => __( 'Maximal %d Dateien erlaubt', 'recruiting-playbook' ),
			'selectOption'       => __( 'Bitte wählen...', 'recruiting-playbook' ),
			'otherOptionLabel'   => __( 'Sonstiges', 'recruiting-playbook' ),
			'dragDropText'       => __( 'Datei hierher ziehen oder klicken', 'recruiting-playbook' ),
			'removeFile'         => __( 'Datei entfernen', 'recruiting-playbook' ),
		];
	}

	/**
	 * Felder-HTML für bestimmten Step rendern
	 *
	 * @param FieldDefinition[] $fields       Alle Felder.
	 * @param int               $step         Schritt-Nummer (1-basiert).
	 * @param array             $initial_data Initiale Daten.
	 * @return string HTML.
	 */
	public function renderFieldsForStep( array $fields, int $step, array $initial_data = [] ): string {
		// Felder nach Step gruppieren (basierend auf settings.step oder Position).
		$step_fields = array_filter( $fields, function ( $field ) use ( $step ) {
			$settings   = $field->getSettings() ?? [];
			$field_step = $settings['step'] ?? 1;
			return (int) $field_step === $step;
		} );

		$output = '';
		foreach ( $step_fields as $field ) {
			$output .= $this->renderField( $field, $initial_data );
		}

		return $output;
	}

	/**
	 * Formular-HTML für Wizard rendern (Multi-Step)
	 *
	 * @param int   $job_id       Job-ID.
	 * @param int   $total_steps  Anzahl Steps.
	 * @param array $initial_data Initiale Daten.
	 * @return string HTML.
	 */
	public function renderWizardForm( int $job_id, int $total_steps = 3, array $initial_data = [] ): string {
		$fields = $this->field_service->getFieldsForJob( $job_id );

		if ( empty( $fields ) ) {
			$fields = $this->field_service->getActiveFields();
		}

		$fields = array_filter( $fields, fn( $f ) => $f->isEnabled() );
		usort( $fields, fn( $a, $b ) => $a->getSortOrder() <=> $b->getSortOrder() );

		$output = '';

		// Config.
		$output .= $this->renderAlpineConfig( $fields, $job_id, $initial_data );
		$output .= $this->conditional_generator->generateHiddenStyles();

		// Wizard Container.
		$output .= '<div class="rp-wizard" x-data="rpWizardForm()">';

		for ( $step = 1; $step <= $total_steps; $step++ ) {
			$output .= sprintf(
				'<div class="rp-wizard__step" x-show="step === %d" x-transition>',
				$step
			);
			$output .= $this->renderFieldsForStep( $fields, $step, $initial_data );
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}
}

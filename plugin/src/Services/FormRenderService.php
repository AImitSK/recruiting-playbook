<?php
/**
 * Form Render Service
 *
 * Rendert Bewerbungsformulare basierend auf der Step-Konfiguration.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use RecruitingPlaybook\Services\SpamProtection;

/**
 * Service zum Rendern von Formularen
 */
class FormRenderService {

	/**
	 * FormConfigService
	 *
	 * @var FormConfigService
	 */
	private FormConfigService $config_service;

	/**
	 * FieldDefinitionRepository
	 *
	 * @var FieldDefinitionRepository
	 */
	private FieldDefinitionRepository $field_repository;

	/**
	 * Konstruktor
	 *
	 * @param FormConfigService|null         $config_service   Optional: Config-Service.
	 * @param FieldDefinitionRepository|null $field_repository Optional: Field-Repository.
	 */
	public function __construct(
		?FormConfigService $config_service = null,
		?FieldDefinitionRepository $field_repository = null
	) {
		$this->config_service   = $config_service ?? new FormConfigService();
		$this->field_repository = $field_repository ?? new FieldDefinitionRepository();
	}

	/**
	 * Vollständiges Formular rendern
	 *
	 * Lädt die Published-Konfiguration und rendert ein Step-basiertes Formular.
	 *
	 * @param int $job_id Job-ID.
	 * @return string HTML des Formulars.
	 */
	public function render( int $job_id ): string {
		$config = $this->config_service->getPublished();

		if ( empty( $config['steps'] ) ) {
			return $this->renderFallbackForm( $job_id );
		}

		// Feld-Definitionen laden.
		$field_definitions = $this->loadFieldDefinitions();

		// Alpine.js Daten vorbereiten.
		$alpine_data = $this->prepareAlpineData( $config, $field_definitions, $job_id );

		$output = '';

		// Alpine.js Konfiguration ausgeben.
		$output .= sprintf(
			'<script>window.rpFormConfig = %s;</script>',
			wp_json_encode( $alpine_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE )
		);

		// Alpine.js Container mit applicationForm Komponente.
		$output .= '<div x-data="applicationForm" x-cloak>';

		// Erfolgs-Meldung.
		$output .= $this->renderSuccessMessage();

		// Formular-Template (nur wenn nicht submitted).
		$output .= '<template x-if="!submitted"><div>';

		// Fehler-Meldung.
		$output .= $this->renderErrorMessage();

		// Fortschrittsanzeige.
		$output .= $this->renderProgressBar( count( $config['steps'] ) );

		// Formular-Start mit Spam-Schutz.
		$output .= '<form @submit.prevent="submit">';
		$output .= SpamProtection::getHoneypotField();
		$output .= SpamProtection::getTimestampField();

		// Steps rendern.
		foreach ( $config['steps'] as $index => $step ) {
			$output .= $this->renderStep( $step, $index, $field_definitions, $config );
		}

		// Navigation.
		$output .= $this->renderNavigation( count( $config['steps'] ) );

		$output .= '</form>';
		$output .= '</div></template>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Erfolgs-Meldung rendern
	 *
	 * @return string HTML.
	 */
	private function renderSuccessMessage(): string {
		return '<template x-if="submitted">
			<div class="rp-text-center rp-py-12">
				<svg class="rp-w-16 rp-h-16 rp-text-success rp-mx-auto rp-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
				</svg>
				<h3 class="rp-text-xl rp-font-semibold rp-text-gray-900 rp-mb-2">' . esc_html__( 'Bewerbung erfolgreich gesendet!', 'recruiting-playbook' ) . '</h3>
				<p class="rp-text-gray-600">' . esc_html__( 'Vielen Dank für Ihre Bewerbung. Sie erhalten in Kürze eine Bestätigung per E-Mail.', 'recruiting-playbook' ) . '</p>
			</div>
		</template>';
	}

	/**
	 * Fehler-Meldung rendern
	 *
	 * @return string HTML.
	 */
	private function renderErrorMessage(): string {
		return '<div x-show="error" x-cloak class="rp-bg-error-light rp-border rp-border-error rp-rounded-md rp-p-4 rp-mb-6">
			<p class="rp-text-error rp-text-sm" x-text="error"></p>
		</div>';
	}

	/**
	 * Fortschrittsanzeige rendern
	 *
	 * @param int $total_steps Anzahl der Steps.
	 * @return string HTML.
	 */
	private function renderProgressBar( int $total_steps ): string {
		return '<div class="rp-mb-8">
			<div class="rp-flex rp-justify-between rp-text-sm rp-text-gray-600 rp-mb-2">
				<span>' . esc_html__( 'Schritt', 'recruiting-playbook' ) . ' <span x-text="step"></span> ' . esc_html__( 'von', 'recruiting-playbook' ) . ' <span x-text="totalSteps"></span></span>
				<span x-text="progress + \'%\'"></span>
			</div>
			<div class="rp-h-2 rp-bg-gray-200 rp-rounded-full rp-overflow-hidden">
				<div class="rp-h-full rp-bg-primary rp-transition-all rp-duration-300" :style="\'width: \' + progress + \'%\'"></div>
			</div>
		</div>';
	}

	/**
	 * Einzelnen Step rendern
	 *
	 * @param array $step              Step-Konfiguration.
	 * @param int   $index             Step-Index (0-basiert).
	 * @param array $field_definitions Feld-Definitionen.
	 * @param array $config            Gesamte Formular-Konfiguration.
	 * @return string HTML.
	 */
	private function renderStep( array $step, int $index, array $field_definitions, array $config ): string {
		$step_num = $index + 1;

		$output = sprintf(
			'<div x-show="step === %d" x-transition>',
			$step_num
		);

		// Step-Titel.
		if ( ! empty( $step['title'] ) ) {
			$output .= sprintf(
				'<h3 class="rp-text-lg rp-font-semibold rp-text-gray-900 rp-mb-6">%s</h3>',
				esc_html( $step['title'] )
			);
		}

		// Felder-Grid.
		$output .= '<div class="rp-space-y-4">';

		// Felder des Steps rendern.
		if ( ! empty( $step['fields'] ) ) {
			foreach ( $step['fields'] as $field_config ) {
				if ( empty( $field_config['is_visible'] ) ) {
					continue;
				}

				$field_key = $field_config['field_key'] ?? '';

				if ( ! isset( $field_definitions[ $field_key ] ) ) {
					continue;
				}

				$field_def = $field_definitions[ $field_key ];

				// Override required aus Step-Config.
				$is_required = $field_config['is_required'] ?? $field_def['is_required'] ?? false;

				$output .= $this->renderField( $field_def, $field_key, $is_required );
			}
		}

		$output .= '</div>'; // .rp-space-y-4
		$output .= '</div>'; // step div

		return $output;
	}

	/**
	 * Einzelnes Feld rendern
	 *
	 * @param array  $field_def   Feld-Definition.
	 * @param string $field_key   Feld-Schlüssel.
	 * @param bool   $is_required Pflichtfeld.
	 * @return string HTML.
	 */
	private function renderField( array $field_def, string $field_key, bool $is_required ): string {
		$field_type = $field_def['field_type'] ?? 'text';
		$settings   = $field_def['settings'] ?? [];
		$width      = $settings['width'] ?? 'full';

		// Width-Klasse.
		$width_class = match ( $width ) {
			'half'       => 'md:rp-col-span-1',
			'third'      => 'md:rp-col-span-1',
			'two-thirds' => 'md:rp-col-span-2',
			default      => 'rp-col-span-full',
		};

		// Template-Datei laden.
		$template_path = $this->getFieldTemplatePath( $field_type );

		if ( ! file_exists( $template_path ) ) {
			$template_path = $this->getFieldTemplatePath( 'text' );
		}

		// Template-Variablen.
		$label       = $field_def['label'] ?? ucfirst( $field_key );
		$placeholder = $field_def['placeholder'] ?? '';
		$description = $field_def['description'] ?? '';
		$validation  = $field_def['validation'] ?? [];
		$options     = $field_def['options'] ?? [];

		// x-model Binding.
		$x_model = sprintf( 'formData.%s', $field_key );

		ob_start();

		// Field-Wrapper.
		printf(
			'<div class="rp-form-field rp-form-field--%s %s" data-field="%s">',
			esc_attr( $field_type ),
			esc_attr( $width_class ),
			esc_attr( $field_key )
		);

		// Template einbinden.
		include $template_path;

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Navigation rendern (Zurück / Weiter / Absenden)
	 *
	 * @param int $total_steps Anzahl der Steps.
	 * @return string HTML.
	 */
	private function renderNavigation( int $total_steps ): string {
		$output = '<div class="rp-flex rp-justify-between rp-items-center rp-mt-8 rp-pt-6 rp-border-t rp-border-gray-200">';

		// Zurück-Button (nicht auf Step 1).
		$output .= sprintf(
			'<button type="button" x-show="step > 1" @click="prevStep" class="wp-element-button is-style-outline">%s</button>',
			esc_html__( 'Zurück', 'recruiting-playbook' )
		);

		// Spacer für Step 1.
		$output .= '<div x-show="step === 1"></div>';

		// Weiter-Button (nicht auf letztem Step).
		$output .= sprintf(
			'<button type="button" x-show="step < totalSteps" @click="nextStep" class="wp-element-button">%s</button>',
			esc_html__( 'Weiter', 'recruiting-playbook' )
		);

		// Absenden-Button (nur auf letztem Step).
		$output .= sprintf(
			'<button type="submit" x-show="step === totalSteps" :disabled="loading" class="wp-element-button disabled:rp-opacity-50 disabled:rp-cursor-not-allowed">
				<span x-show="!loading">%s</span>
				<span x-show="loading">%s</span>
			</button>',
			esc_html__( 'Bewerbung absenden', 'recruiting-playbook' ),
			esc_html__( 'Wird gesendet...', 'recruiting-playbook' )
		);

		$output .= '</div>';

		return $output;
	}

	/**
	 * Alpine.js Daten vorbereiten
	 *
	 * @param array $config            Formular-Konfiguration.
	 * @param array $field_definitions Feld-Definitionen.
	 * @param int   $job_id            Job-ID.
	 * @return array Alpine.js Daten.
	 */
	private function prepareAlpineData( array $config, array $field_definitions, int $job_id ): array {
		$form_data        = [ 'job_id' => $job_id ];
		$validation_rules = [];

		// Alle sichtbaren Felder aus den Steps extrahieren.
		foreach ( $config['steps'] as $step ) {
			if ( empty( $step['fields'] ) ) {
				continue;
			}

			foreach ( $step['fields'] as $field_config ) {
				if ( empty( $field_config['is_visible'] ) ) {
					continue;
				}

				$field_key = $field_config['field_key'] ?? '';

				if ( ! isset( $field_definitions[ $field_key ] ) ) {
					continue;
				}

				$field_def = $field_definitions[ $field_key ];

				// Default-Wert.
				$form_data[ $field_key ] = $this->getDefaultValue( $field_def );

				// Validierungsregeln.
				$rules = [];

				if ( ! empty( $field_config['is_required'] ) ) {
					$rules['required'] = true;
				}

				$validation = $field_def['validation'] ?? [];

				if ( ! empty( $validation['min_length'] ) ) {
					$rules['minLength'] = (int) $validation['min_length'];
				}
				if ( ! empty( $validation['max_length'] ) ) {
					$rules['maxLength'] = (int) $validation['max_length'];
				}

				// Feldtyp-spezifische Regeln.
				$field_type = $field_def['field_type'] ?? 'text';
				if ( $field_type === 'email' ) {
					$rules['email'] = true;
				}
				if ( $field_type === 'phone' ) {
					$rules['phone'] = true;
				}

				if ( ! empty( $rules ) ) {
					$validation_rules[ $field_key ] = $rules;
				}
			}
		}

		return [
			'steps'      => count( $config['steps'] ),
			'formData'   => $form_data,
			'validation' => $validation_rules,
			'i18n'       => $this->getI18nStrings(),
		];
	}

	/**
	 * Feld-Definitionen als Key-Value-Array laden
	 *
	 * @return array<string, array>
	 */
	private function loadFieldDefinitions(): array {
		$fields = $this->field_repository->findSystemFields();
		$result = [];

		foreach ( $fields as $field ) {
			$result[ $field->getFieldKey() ] = [
				'field_key'   => $field->getFieldKey(),
				'field_type'  => $field->getFieldType(),
				'label'       => $field->getLabel(),
				'placeholder' => $field->getPlaceholder(),
				'description' => $field->getDescription(),
				'is_required' => $field->isRequired(),
				'is_system'   => $field->isSystem(),
				'settings'    => $field->getSettings(),
				'validation'  => $field->getValidation(),
				'options'     => $field->getOptions(),
			];
		}

		return $result;
	}

	/**
	 * Standard-Wert für Feldtyp ermitteln
	 *
	 * @param array $field_def Feld-Definition.
	 * @return mixed Standard-Wert.
	 */
	private function getDefaultValue( array $field_def ): mixed {
		$field_type = $field_def['field_type'] ?? 'text';

		return match ( $field_type ) {
			'checkbox' => false,
			'file'     => [],
			'number'   => null,
			default    => '',
		};
	}

	/**
	 * Pfad zur Feld-Template-Datei
	 *
	 * @param string $field_type Feldtyp.
	 * @return string Template-Pfad.
	 */
	private function getFieldTemplatePath( string $field_type ): string {
		// Theme kann Templates überschreiben.
		$theme_template = get_stylesheet_directory() . '/recruiting-playbook/fields/field-' . $field_type . '.php';

		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}

		return RP_PLUGIN_DIR . 'templates/fields/field-' . $field_type . '.php';
	}

	/**
	 * Übersetzungen für Frontend
	 *
	 * @return array<string, string>
	 */
	private function getI18nStrings(): array {
		return [
			'required'        => __( 'Dieses Feld ist erforderlich', 'recruiting-playbook' ),
			'invalidEmail'    => __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein', 'recruiting-playbook' ),
			'invalidPhone'    => __( 'Bitte geben Sie eine gültige Telefonnummer ein', 'recruiting-playbook' ),
			'minLength'       => __( 'Mindestens %d Zeichen erforderlich', 'recruiting-playbook' ),
			'maxLength'       => __( 'Maximal %d Zeichen erlaubt', 'recruiting-playbook' ),
			'fileTooLarge'    => __( 'Die Datei ist zu groß (max. %d MB)', 'recruiting-playbook' ),
			'invalidFileType' => __( 'Dieser Dateityp ist nicht erlaubt', 'recruiting-playbook' ),
		];
	}

	/**
	 * Fallback-Formular rendern (wenn keine Konfiguration vorhanden)
	 *
	 * @param int $job_id Job-ID.
	 * @return string HTML.
	 */
	private function renderFallbackForm( int $job_id ): string {
		return sprintf(
			'<div class="rp-form-error">%s</div>',
			esc_html__( 'Formular konnte nicht geladen werden. Bitte kontaktieren Sie den Administrator.', 'recruiting-playbook' )
		);
	}
}

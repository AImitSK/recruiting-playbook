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
	 * @param int  $job_id          Job-ID.
	 * @param bool $include_wrapper Wenn false, werden nur die Steps ohne Alpine-Wrapper gerendert.
	 * @return string HTML des Formulars.
	 */
	public function render( int $job_id, bool $include_wrapper = true ): string {
		$config = $this->config_service->getPublished();

		if ( empty( $config['steps'] ) ) {
			return $this->renderFallbackForm( $job_id );
		}

		// Feld-Definitionen laden.
		$field_definitions = $this->loadFieldDefinitions();

		$output = '';

		if ( $include_wrapper ) {
			// Alpine.js Daten vorbereiten.
			$alpine_data = $this->prepareAlpineData( $config, $field_definitions, $job_id );

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
			$output .= '<form x-on:submit.prevent="submit" novalidate>';
			$output .= SpamProtection::getHoneypotField();
			$output .= SpamProtection::getTimestampField();
		}

		// Steps rendern.
		foreach ( $config['steps'] as $index => $step ) {
			$output .= $this->renderStep( $step, $index, $field_definitions, $config );
		}

		if ( $include_wrapper ) {
			// Navigation.
			$output .= $this->renderNavigation( count( $config['steps'] ) );

			$output .= '</form>';
			$output .= '</div></template>';
			$output .= '</div>';
		}

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
		$step_num   = $index + 1;
		$is_finale  = ! empty( $step['is_finale'] );

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

		// Felder-Grid mit 2 Spalten für Breiten-Klassen.
		$output .= '<div class="rp-grid rp-grid-cols-1 md:rp-grid-cols-2 rp-gap-4">';

		// Reguläre Felder des Steps rendern.
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

		$output .= '</div>'; // Grid Ende

		// System-Felder des Steps rendern (file_upload, summary, privacy_consent).
		if ( ! empty( $step['system_fields'] ) ) {
			foreach ( $step['system_fields'] as $system_field ) {
				$output .= $this->renderSystemField( $system_field, $config, $field_definitions );
			}
		}

		$output .= '</div>'; // step div

		return $output;
	}

	/**
	 * System-Feld rendern
	 *
	 * System-Felder sind file_upload, summary und privacy_consent.
	 *
	 * @param array $system_field      System-Feld-Konfiguration.
	 * @param array $config            Gesamte Formular-Konfiguration.
	 * @param array $field_definitions Feld-Definitionen.
	 * @return string HTML.
	 */
	private function renderSystemField( array $system_field, array $config, array $field_definitions ): string {
		$field_key = $system_field['field_key'] ?? '';
		$type      = $system_field['type'] ?? $field_key;
		$settings  = $system_field['settings'] ?? [];

		return match ( $type ) {
			'file_upload'     => $this->renderFileUploadSystemField( $settings ),
			'summary'         => $this->renderSummarySystemField( $settings, $config, $field_definitions ),
			'privacy_consent' => $this->renderPrivacyConsentSystemField( $settings ),
			default           => '',
		};
	}

	/**
	 * File-Upload System-Feld rendern
	 *
	 * @param array $settings System-Feld-Einstellungen.
	 * @return string HTML.
	 */
	private function renderFileUploadSystemField( array $settings ): string {
		$label         = $settings['label'] ?? __( 'Bewerbungsunterlagen', 'recruiting-playbook' );
		$help_text     = $settings['help_text'] ?? '';
		$allowed_types = $settings['allowed_types'] ?? [ 'pdf', 'doc', 'docx' ];
		$max_file_size = $settings['max_file_size'] ?? 10;
		$max_files     = $settings['max_files'] ?? 5;

		// Sicherstellen, dass allowed_types ein Array ist (kann als kommaseparierter String gespeichert sein).
		if ( is_string( $allowed_types ) ) {
			$allowed_types = array_map( 'trim', explode( ',', $allowed_types ) );
		}

		// Allowed types als Accept-String formatieren.
		$accept = '.' . implode( ',.', $allowed_types );

		// Konfiguration für Alpine-Komponente.
		$component_config = [
			'maxFiles'         => $max_files,
			'maxSize'          => $max_file_size,
			'allowedTypes'     => $allowed_types,
			'errorMaxFiles'    => sprintf( __( 'Maximal %d Dateien erlaubt', 'recruiting-playbook' ), $max_files ),
			'errorInvalidType' => sprintf( __( 'Ungültiger Dateityp. Erlaubt: %s', 'recruiting-playbook' ), strtoupper( implode( ', ', $allowed_types ) ) ),
			'errorTooLarge'    => sprintf( __( 'Datei zu groß (max. %d MB)', 'recruiting-playbook' ), $max_file_size ),
		];

		ob_start();
		?>
		<div
			class="rp-system-field rp-system-field--file-upload rp-mt-6"
			@files-updated.stop="files.documents = $event.detail.files"
		>
			<label class="rp-label">
				<?php echo esc_html( $label ); ?>
			</label>

			<?php if ( $help_text ) : ?>
				<p class="rp-text-sm rp-text-gray-600 rp-mb-3"><?php echo esc_html( $help_text ); ?></p>
			<?php endif; ?>

			<div
				x-data="rpFileUpload(<?php echo esc_attr( wp_json_encode( $component_config ) ); ?>)"
				class="rp-file-upload"
			>
				<!-- Dropzone -->
				<div
					@dragover.prevent="dragging = true"
					@dragleave.prevent="dragging = false"
					@drop.prevent="handleDrop($event)"
					:class="{ 'rp-border-primary rp-bg-primary-light': dragging, 'rp-border-success rp-bg-success-light': files.length > 0 }"
					class="rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-lg rp-p-6 rp-text-center rp-cursor-pointer rp-transition-colors"
				>
					<template x-if="files.length === 0">
						<div>
							<svg class="rp-w-10 rp-h-10 rp-text-gray-400 rp-mx-auto rp-mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
							</svg>
							<p class="rp-text-gray-600 rp-mb-2">
								<?php esc_html_e( 'Datei hierher ziehen oder', 'recruiting-playbook' ); ?>
							</p>
							<label class="rp-text-primary hover:rp-text-primary-hover rp-font-medium rp-cursor-pointer">
								<?php esc_html_e( 'Datei auswählen', 'recruiting-playbook' ); ?>
								<input
									type="file"
									@change="handleSelect($event)"
									accept="<?php echo esc_attr( $accept ); ?>"
									multiple
									class="rp-hidden"
								>
							</label>
							<p class="rp-text-xs rp-text-gray-400 rp-mt-2">
								<?php
								printf(
									/* translators: 1: allowed types, 2: max file size */
									esc_html__( '%1$s (max. %2$d MB pro Datei)', 'recruiting-playbook' ),
									esc_html( strtoupper( implode( ', ', $allowed_types ) ) ),
									esc_html( $max_file_size )
								);
								?>
								<br>
								<?php
								printf(
									/* translators: %d: max number of files */
									esc_html__( 'Maximal %d Dateien', 'recruiting-playbook' ),
									esc_html( $max_files )
								);
								?>
							</p>
						</div>
					</template>

					<!-- Hochgeladene Dateien -->
					<template x-if="files.length > 0">
						<div class="rp-space-y-2">
							<template x-for="(file, index) in files" :key="index">
								<div class="rp-flex rp-items-center rp-justify-between rp-bg-white rp-rounded rp-px-3 rp-py-2 rp-border rp-border-gray-200">
									<div class="rp-flex rp-items-center rp-gap-3">
										<svg class="rp-w-5 rp-h-5 rp-text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
										</svg>
										<div class="rp-text-left">
											<p class="rp-text-sm rp-font-medium rp-text-gray-900" x-text="file.name"></p>
											<p class="rp-text-xs rp-text-gray-500" x-text="formatSize(file.size)"></p>
										</div>
									</div>
									<button
										type="button"
										@click="removeFile(index)"
										class="rp-p-1 rp-text-error hover:rp-bg-error-light rp-rounded"
										title="<?php esc_attr_e( 'Datei entfernen', 'recruiting-playbook' ); ?>"
									>
										<svg class="rp-w-4 rp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
										</svg>
									</button>
								</div>
							</template>

							<label class="rp-inline-block rp-text-sm rp-text-primary hover:rp-text-primary-hover rp-cursor-pointer rp-mt-2">
								+ <?php esc_html_e( 'Weitere Datei hinzufügen', 'recruiting-playbook' ); ?>
								<input
									type="file"
									@change="handleSelect($event)"
									accept="<?php echo esc_attr( $accept ); ?>"
									class="rp-hidden"
								>
							</label>
						</div>
					</template>
				</div>

				<!-- Fehler -->
				<p x-show="error" x-text="error" class="rp-error-text rp-mt-2"></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Summary System-Feld rendern
	 *
	 * @param array $settings          System-Feld-Einstellungen.
	 * @param array $config            Formular-Konfiguration.
	 * @param array $field_definitions Feld-Definitionen.
	 * @return string HTML.
	 */
	private function renderSummarySystemField( array $settings, array $config, array $field_definitions ): string {
		// Unterstütze beide Key-Varianten für Abwärtskompatibilität.
		$label             = $settings['title'] ?? $settings['label'] ?? __( 'Ihre Angaben im Überblick', 'recruiting-playbook' );
		$show_header       = $settings['show_header'] ?? true;
		$show_step_titles  = $settings['show_step_titles'] ?? true;
		$show_edit_buttons = $settings['show_edit_buttons'] ?? true;
		$help_text         = $settings['additional_text'] ?? $settings['help_text'] ?? '';

		return $this->renderSummary( $config, $field_definitions, $label, $show_header, $show_step_titles, $show_edit_buttons, $help_text );
	}

	/**
	 * Privacy Consent System-Feld rendern
	 *
	 * @param array $settings System-Feld-Einstellungen.
	 * @return string HTML.
	 */
	private function renderPrivacyConsentSystemField( array $settings ): string {
		// Unterstütze beide Key-Varianten für Abwärtskompatibilität.
		$consent_text      = $settings['checkbox_text'] ?? $settings['consent_text'] ?? __( 'Ich habe die {datenschutz_link} gelesen und stimme der Verarbeitung meiner Daten zu.', 'recruiting-playbook' );
		$privacy_link_text = $settings['link_text'] ?? $settings['privacy_link_text'] ?? __( 'Datenschutzerklärung', 'recruiting-playbook' );
		$privacy_url       = $settings['privacy_url'] ?? get_privacy_policy_url();
		$error_message     = $settings['error_message'] ?? __( 'Sie müssen der Datenschutzerklärung zustimmen.', 'recruiting-playbook' );
		$help_text         = $settings['help_text'] ?? '';

		// SECURITY FIX: Text vor und nach dem Platzhalter separat escapen.
		// Damit wird XSS verhindert, während der sichere Link eingefügt wird.
		$link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" class="rp-text-primary hover:rp-underline">%s</a>',
			esc_url( $privacy_url ),
			esc_html( $privacy_link_text )
		);

		// Text in Teile aufteilen - unterstütze beide Platzhalter-Varianten.
		// Zuerst {datenschutz_link} versuchen, dann {privacy_link}.
		$parts = explode( '{datenschutz_link}', $consent_text, 2 );
		if ( count( $parts ) === 1 ) {
			// {datenschutz_link} nicht gefunden, versuche {privacy_link}.
			$parts = explode( '{privacy_link}', $consent_text, 2 );
		}

		if ( count( $parts ) === 2 ) {
			$consent_html = esc_html( $parts[0] ) . $link . esc_html( $parts[1] );
		} else {
			// Kein Platzhalter vorhanden - gesamten Text escapen.
			$consent_html = esc_html( $consent_text );
		}

		ob_start();
		?>
		<div class="rp-system-field rp-system-field--privacy-consent rp-mt-6">
			<label class="rp-flex rp-items-start rp-gap-3 rp-cursor-pointer">
				<input
					type="checkbox"
					x-model="formData.privacy_consent"
					class="rp-mt-1 rp-h-4 rp-w-4 rp-text-primary rp-border-gray-300 rp-rounded focus:rp-ring-primary"
					required
				>
				<span class="rp-text-sm rp-text-gray-700">
					<?php echo wp_kses_post( $consent_html ); ?>
					<span class="rp-text-error">*</span>
				</span>
			</label>

			<?php if ( $help_text ) : ?>
				<p class="rp-text-xs rp-text-gray-500 rp-mt-2 rp-ml-7"><?php echo esc_html( $help_text ); ?></p>
			<?php endif; ?>

			<p x-show="errors.privacy_consent" class="rp-error-text rp-mt-2 rp-ml-7">
				<?php echo esc_html( $error_message ); ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
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

		// Width-Klasse für 2-Spalten-Grid.
		$width_class = match ( $width ) {
			'half'       => 'rp-col-span-1',
			'third'      => 'rp-col-span-1',
			'two-thirds' => 'rp-col-span-2 md:rp-col-span-2',
			default      => 'rp-col-span-2 md:rp-col-span-2',
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
	 * Zusammenfassung der Eingaben rendern
	 *
	 * @param array  $config             Formular-Konfiguration.
	 * @param array  $field_definitions  Feld-Definitionen.
	 * @param string $label              Überschrift.
	 * @param bool   $show_header        Überschrift anzeigen.
	 * @param bool   $show_step_titles   Schritt-Titel anzeigen.
	 * @param bool   $show_edit_buttons  Bearbeiten-Buttons anzeigen.
	 * @param string $help_text          Hilfetext.
	 * @return string HTML.
	 */
	private function renderSummary(
		array $config,
		array $field_definitions,
		string $label = '',
		bool $show_header = true,
		bool $show_step_titles = true,
		bool $show_edit_buttons = true,
		string $help_text = ''
	): string {
		$label = $label ?: __( 'Ihre Angaben', 'recruiting-playbook' );

		$output = '<div class="rp-summary rp-bg-gray-50 rp-rounded-lg rp-p-4 rp-mb-6">';

		if ( $show_header ) {
			$output .= sprintf(
				'<h4 class="rp-font-medium rp-text-gray-900 rp-mb-4">%s</h4>',
				esc_html( $label )
			);
		}

		if ( $help_text ) {
			$output .= sprintf(
				'<p class="rp-text-sm rp-text-gray-600 rp-mb-4">%s</p>',
				esc_html( $help_text )
			);
		}

		$output .= '<dl class="rp-space-y-2 rp-text-sm">';

		// Alle Felder durchgehen (außer finale Felder wie privacy_consent).
		foreach ( $config['steps'] as $step ) {
			// Finale-Step überspringen.
			if ( ! empty( $step['is_finale'] ) ) {
				continue;
			}

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

				$field_def  = $field_definitions[ $field_key ];
				$field_type = $field_def['field_type'] ?? 'text';
				$label      = $field_def['label'] ?? ucfirst( str_replace( '_', ' ', $field_key ) );

				// Skip file fields in summary (handled separately).
				if ( 'file' === $field_type ) {
					$output .= $this->renderFileSummaryItem( $label, $field_key );
					continue;
				}

				// Skip display-only fields (no data collected).
				if ( in_array( $field_type, [ 'html', 'heading' ], true ) ) {
					continue;
				}

				// Skip empty textarea fields.
				if ( 'textarea' === $field_type ) {
					$output .= $this->renderSummaryItem( $label, $field_key, true );
					continue;
				}

				$output .= $this->renderSummaryItem( $label, $field_key );
			}
		}

		// Hochgeladene Dateien aus dem file_upload System-Feld anzeigen (files.documents).
		$output .= $this->renderUploadedFilesSummary( $config );

		$output .= '</dl>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Hochgeladene Dateien aus dem file_upload System-Feld in der Zusammenfassung anzeigen
	 *
	 * @param array $config Formular-Konfiguration.
	 * @return string HTML.
	 */
	private function renderUploadedFilesSummary( array $config ): string {
		// Prüfen ob file_upload System-Feld in irgendeinem Step vorhanden ist.
		$has_file_upload   = false;
		$file_upload_label = __( 'Bewerbungsunterlagen', 'recruiting-playbook' );

		foreach ( $config['steps'] as $step ) {
			if ( empty( $step['system_fields'] ) ) {
				continue;
			}

			foreach ( $step['system_fields'] as $sf ) {
				if ( ( $sf['field_key'] ?? '' ) === 'file_upload' ) {
					$has_file_upload = true;
					// Label aus Settings übernehmen wenn vorhanden.
					if ( ! empty( $sf['settings']['label'] ) ) {
						$file_upload_label = $sf['settings']['label'];
					}
					break 2;
				}
			}
		}

		if ( ! $has_file_upload ) {
			return '';
		}

		// Dateiliste mit x-for rendern (zeigt alle hochgeladenen Dateien).
		return sprintf(
			'<div class="rp-flex rp-flex-col sm:rp-flex-row sm:rp-gap-2" x-show="files.documents && files.documents.length > 0">
				<dt class="rp-text-gray-500 sm:rp-w-32 rp-flex-shrink-0">%s:</dt>
				<dd class="rp-text-gray-900">
					<template x-for="(doc, index) in files.documents" :key="index">
						<span class="rp-block" x-text="doc.name"></span>
					</template>
				</dd>
			</div>',
			esc_html( $file_upload_label )
		);
	}

	/**
	 * Einzelnes Summary-Item rendern
	 *
	 * @param string $label       Label.
	 * @param string $field_key   Feld-Schlüssel.
	 * @param bool   $is_optional Optionales Feld (nur anzeigen wenn ausgefüllt).
	 * @return string HTML.
	 */
	private function renderSummaryItem( string $label, string $field_key, bool $is_optional = false ): string {
		// SECURITY FIX: Field Key validieren um JavaScript Injection zu verhindern.
		// Nur alphanumerische Zeichen und Unterstriche erlauben.
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $field_key ) ) {
			// Bei ungültigem Feld-Key leeren String zurückgeben.
			return '';
		}

		$x_show = $is_optional ? sprintf( ' x-show="formData.%s"', esc_attr( $field_key ) ) : '';

		return sprintf(
			'<div class="rp-flex rp-flex-col sm:rp-flex-row sm:rp-gap-2"%s>
				<dt class="rp-text-gray-500 sm:rp-w-32 rp-flex-shrink-0">%s:</dt>
				<dd class="rp-text-gray-900 rp-break-all" x-text="formData.%s || \'-\'"></dd>
			</div>',
			$x_show,
			esc_html( $label ),
			esc_attr( $field_key )
		);
	}

	/**
	 * Datei-Summary-Item rendern
	 *
	 * @param string $label     Label.
	 * @param string $field_key Feld-Schlüssel.
	 * @return string HTML.
	 */
	private function renderFileSummaryItem( string $label, string $field_key ): string {
		// Für resume-Feld (einzelne Datei).
		if ( 'resume' === $field_key || 'lebenslauf' === $field_key ) {
			return sprintf(
				'<div class="rp-flex rp-flex-col sm:rp-flex-row sm:rp-gap-2" x-show="files.resume">
					<dt class="rp-text-gray-500 sm:rp-w-32 rp-flex-shrink-0">%s:</dt>
					<dd class="rp-text-gray-900" x-text="files.resume?.name || \'-\'"></dd>
				</div>',
				esc_html( $label )
			);
		}

		// Für documents-Feld (mehrere Dateien).
		return sprintf(
			'<div class="rp-flex rp-flex-col sm:rp-flex-row sm:rp-gap-2" x-show="files.documents?.length > 0">
				<dt class="rp-text-gray-500 sm:rp-w-32 rp-flex-shrink-0">%s:</dt>
				<dd class="rp-text-gray-900">
					<template x-for="doc in files.documents" :key="doc.name">
						<span class="rp-block" x-text="doc.name"></span>
					</template>
				</dd>
			</div>',
			esc_html( $label )
		);
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
			'<button type="button" x-show="step > 1" x-on:click="prevStep" class="wp-element-button is-style-outline">%s</button>',
			esc_html__( 'Zurück', 'recruiting-playbook' )
		);

		// Spacer für Step 1.
		$output .= '<div x-show="step === 1"></div>';

		// Weiter-Button (nicht auf letztem Step).
		$output .= sprintf(
			'<button type="button" x-show="step < totalSteps" x-on:click="nextStep" class="wp-element-button">%s</button>',
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
				if ( $field_type === 'url' ) {
					$rules['url'] = true;
				}

				if ( ! empty( $rules ) ) {
					$validation_rules[ $field_key ] = $rules;
				}
			}

			// System-Felder verarbeiten.
			if ( ! empty( $step['system_fields'] ) ) {
				foreach ( $step['system_fields'] as $system_field ) {
					$field_key = $system_field['field_key'] ?? '';

					switch ( $field_key ) {
						case 'privacy_consent':
							$form_data['privacy_consent']        = false;
							$validation_rules['privacy_consent'] = [ 'required' => true ];
							break;
						// file_upload wird über files-Objekt gehandhabt (unten).
					}
				}
			}
		}

		// Files-Objekt für Datei-Uploads initialisieren.
		$has_file_upload = false;
		foreach ( $config['steps'] as $step ) {
			if ( ! empty( $step['system_fields'] ) ) {
				foreach ( $step['system_fields'] as $sf ) {
					if ( ( $sf['field_key'] ?? '' ) === 'file_upload' ) {
						$has_file_upload = true;
						break 2;
					}
				}
			}
		}

		$files = [];
		if ( $has_file_upload ) {
			$files = [
				'documents' => [],
			];
		}

		return [
			'steps'      => count( $config['steps'] ),
			'formData'   => $form_data,
			'files'      => $files,
			'validation' => $validation_rules,
			'i18n'       => $this->getI18nStrings(),
		];
	}

	/**
	 * Feld-Definitionen als Key-Value-Array laden
	 *
	 * Lädt alle aktiven Felder (System- und Custom-Felder).
	 *
	 * @return array<string, array>
	 */
	private function loadFieldDefinitions(): array {
		$fields = $this->field_repository->findAll( [ 'active_only' => true ] );
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
		// SECURITY FIX: Path Traversal verhindern.
		// Nur alphanumerische Zeichen und Unterstriche erlauben.
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $field_type ) ) {
			// Bei ungültigem Feldtyp auf 'text' zurückfallen.
			$field_type = 'text';
		}

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

	/**
	 * Konfiguration für rpCustomFieldsForm Component vorbereiten
	 *
	 * @param int $job_id Job-ID.
	 * @return array Konfiguration für Alpine.js Component.
	 */
	public function getCustomFieldsConfig( int $job_id ): array {
		$config            = $this->config_service->getPublished();
		$field_definitions = $this->loadFieldDefinitions();
		$fields            = [];
		$steps             = [];
		$initial_data      = [ 'job_id' => $job_id ];

		// Steps und Felder aus Konfiguration extrahieren.
		if ( ! empty( $config['steps'] ) ) {
			foreach ( $config['steps'] as $step_index => $step ) {
				$step_fields = [];

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

						// Feld zur Liste hinzufügen.
						$field_data = [
							'field_key'    => $field_key,
							'type'         => $field_def['field_type'],
							'label'        => $field_def['label'],
							'placeholder'  => $field_def['placeholder'] ?? '',
							'description'  => $field_def['description'] ?? '',
							'is_required'  => $field_config['is_required'] ?? $field_def['is_required'] ?? false,
							'validation'   => $field_def['validation'] ?? [],
							'settings'     => $field_def['settings'] ?? [],
							'options'      => $field_def['options'] ?? [],
							'step_id'      => $step['id'] ?? $step_index,
						];

						$fields[]      = $field_data;
						$step_fields[] = $field_key;

						// Initial-Wert setzen.
						$initial_data[ $field_key ] = $this->getDefaultValue( $field_def );
					}
				}

				$steps[] = [
					'id'     => $step['id'] ?? $step_index,
					'title'  => $step['title'] ?? '',
					'fields' => $step_fields,
				];
			}
		}

		return [
			'fields'      => $fields,
			'steps'       => $steps,
			'initialData' => $initial_data,
		];
	}
}

<?php
/**
 * FormConfig Service
 *
 * Geschäftslogik für die Step-basierte Formular-Konfiguration.
 * Verwaltet Draft/Published Workflow und Validierung.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\FormConfigRepository;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use WP_Error;

/**
 * Service für Formular-Konfiguration
 */
class FormConfigService {

	/**
	 * Pflichtfelder, die nicht entfernt werden können.
	 *
	 * Diese Felder sind für E-Mail-Platzhalter und DSGVO-Compliance erforderlich.
	 */
	public const REQUIRED_FIELDS = [ 'first_name', 'last_name', 'email', 'privacy_consent' ];

	/**
	 * Repository
	 *
	 * @var FormConfigRepository
	 */
	private FormConfigRepository $repository;

	/**
	 * FieldDefinition Repository
	 *
	 * @var FieldDefinitionRepository
	 */
	private FieldDefinitionRepository $field_repository;

	/**
	 * Constructor
	 *
	 * @param FormConfigRepository|null      $repository       FormConfig Repository.
	 * @param FieldDefinitionRepository|null $field_repository FieldDefinition Repository.
	 */
	public function __construct(
		?FormConfigRepository $repository = null,
		?FieldDefinitionRepository $field_repository = null
	) {
		$this->repository       = $repository ?? new FormConfigRepository();
		$this->field_repository = $field_repository ?? new FieldDefinitionRepository();
	}

	/**
	 * Draft-Konfiguration laden
	 *
	 * Lädt die Draft-Version oder erstellt eine Standard-Konfiguration.
	 * Migriert automatisch v1-Konfigurationen zu v2.
	 *
	 * @return array
	 */
	public function getDraft(): array {
		$draft = $this->repository->getDraft();

		if ( $draft ) {
			// Automatisch migrieren falls v1.
			$config = $this->migrateConfig( $draft['config_data'] );
			// Sicherstellen dass System-Felder vorhanden sind.
			return $this->ensureRequiredSystemFields( $config );
		}

		// Fallback auf Default-Konfiguration.
		return $this->getDefaultConfig();
	}

	/**
	 * Published-Konfiguration laden
	 *
	 * Lädt die veröffentlichte Version oder erstellt eine Standard-Konfiguration.
	 * Migriert automatisch v1-Konfigurationen zu v2.
	 *
	 * @return array
	 */
	public function getPublished(): array {
		$published = $this->repository->getPublished();

		if ( $published ) {
			// Automatisch migrieren falls v1.
			$config = $this->migrateConfig( $published['config_data'] );
			// Sicherstellen dass System-Felder vorhanden sind.
			return $this->ensureRequiredSystemFields( $config );
		}

		// Fallback auf Default-Konfiguration.
		return $this->getDefaultConfig();
	}

	/**
	 * Draft-Konfiguration speichern
	 *
	 * @param array $config Konfigurationsdaten.
	 * @return true|WP_Error
	 */
	public function saveDraft( array $config ): bool|WP_Error {
		// System-Felder sicherstellen bevor Validierung.
		$config = $this->ensureRequiredSystemFields( $config );

		// Validierung.
		$validation = $this->validateConfig( $config );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Benutzer-ID ermitteln.
		$user_id = get_current_user_id();

		$result = $this->repository->saveDraft( $config, $user_id ?: null );

		if ( ! $result ) {
			return new WP_Error(
				'save_failed',
				__( 'Konfiguration konnte nicht gespeichert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Draft veröffentlichen
	 *
	 * @return true|WP_Error
	 */
	public function publish(): bool|WP_Error {
		// Draft laden und validieren.
		$draft = $this->getDraft();

		$validation = $this->validateConfig( $draft );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Prüfen ob Änderungen vorhanden.
		if ( ! $this->hasUnpublishedChanges() ) {
			return new WP_Error(
				'no_changes',
				__( 'Keine Änderungen zum Veröffentlichen vorhanden.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		// Benutzer-ID ermitteln.
		$user_id = get_current_user_id();

		$result = $this->repository->publish( $user_id ?: null );

		if ( ! $result ) {
			return new WP_Error(
				'publish_failed',
				__( 'Konfiguration konnte nicht veröffentlicht werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Draft verwerfen
	 *
	 * Setzt den Draft auf den Stand der Published-Version zurück.
	 *
	 * @return true|WP_Error
	 */
	public function discardDraft(): bool|WP_Error {
		if ( ! $this->hasUnpublishedChanges() ) {
			return new WP_Error(
				'no_changes',
				__( 'Keine Änderungen zum Verwerfen vorhanden.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		$result = $this->repository->discardDraft();

		if ( ! $result ) {
			return new WP_Error(
				'discard_failed',
				__( 'Änderungen konnten nicht verworfen werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Konfiguration auf Standard zurücksetzen
	 *
	 * Löscht Draft und Published und speichert/veröffentlicht die Default-Konfiguration.
	 *
	 * @return true|WP_Error
	 */
	public function resetToDefault(): bool|WP_Error {
		// Alte Konfiguration löschen.
		$result = $this->repository->resetToDefault();

		if ( ! $result ) {
			return new WP_Error(
				'reset_failed',
				__( 'Konfiguration konnte nicht zurückgesetzt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		// Default-Konfiguration als Draft speichern.
		$default_config = $this->getDefaultConfig();
		$save_result    = $this->saveDraft( $default_config );

		if ( is_wp_error( $save_result ) ) {
			return $save_result;
		}

		// Sofort veröffentlichen, damit das Frontend die Konfiguration hat.
		// Direkt über Repository, um die "no changes" Prüfung zu umgehen.
		$user_id        = get_current_user_id();
		$publish_result = $this->repository->publish( $user_id ?: null );

		if ( ! $publish_result ) {
			return new WP_Error(
				'publish_failed',
				__( 'Default-Konfiguration konnte nicht veröffentlicht werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Prüfen ob unveröffentlichte Änderungen existieren
	 *
	 * @return bool
	 */
	public function hasUnpublishedChanges(): bool {
		return $this->repository->hasUnpublishedChanges();
	}

	/**
	 * Veröffentlichte Version abrufen
	 *
	 * @return int
	 */
	public function getPublishedVersion(): int {
		return $this->repository->getPublishedVersion() ?? 1;
	}

	/**
	 * Konfiguration validieren
	 *
	 * @param array $config Konfigurationsdaten.
	 * @return true|WP_Error
	 */
	public function validateConfig( array $config ): bool|WP_Error {
		// Steps-Array muss vorhanden sein.
		if ( ! isset( $config['steps'] ) || ! is_array( $config['steps'] ) ) {
			return new WP_Error(
				'missing_steps',
				__( 'Formular-Konfiguration muss Steps enthalten.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		// Mindestens ein Step erforderlich.
		if ( empty( $config['steps'] ) ) {
			return new WP_Error(
				'empty_steps',
				__( 'Mindestens ein Step ist erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		// Finale-Step prüfen.
		$has_finale = false;
		foreach ( $config['steps'] as $step ) {
			if ( ! empty( $step['is_finale'] ) ) {
				$has_finale = true;
				break;
			}
		}

		if ( ! $has_finale ) {
			return new WP_Error(
				'missing_finale',
				__( 'Ein Finale-Step ist erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		// Jeden Step validieren.
		foreach ( $config['steps'] as $index => $step ) {
			$step_validation = $this->validateStep( $step, $index );

			if ( is_wp_error( $step_validation ) ) {
				return $step_validation;
			}
		}

		// Pflichtfelder prüfen (E-Mail-Platzhalter und DSGVO).
		$visible_fields = $this->getVisibleFieldKeys( $config );

		foreach ( self::REQUIRED_FIELDS as $required_field ) {
			// privacy_consent wird jetzt als system_field geprüft.
			if ( 'privacy_consent' === $required_field ) {
				if ( ! $this->hasPrivacyConsentSystemField( $config ) ) {
					return new WP_Error(
						'missing_privacy_consent',
						__( 'Das Datenschutz-Feld ist erforderlich und muss sichtbar sein.', 'recruiting-playbook' ),
						[ 'status' => 422 ]
					);
				}
				continue;
			}

			if ( ! in_array( $required_field, $visible_fields, true ) ) {
				return new WP_Error(
					'missing_required_field',
					sprintf(
						/* translators: %s: Field name */
						__( 'Das Feld "%s" ist erforderlich und muss sichtbar sein.', 'recruiting-playbook' ),
						$required_field
					),
					[ 'status' => 422 ]
				);
			}
		}

		return true;
	}

	/**
	 * Step validieren
	 *
	 * @param array $step  Step-Daten.
	 * @param int   $index Step-Index.
	 * @return true|WP_Error
	 */
	private function validateStep( array $step, int $index ): bool|WP_Error {
		// ID erforderlich.
		if ( empty( $step['id'] ) ) {
			return new WP_Error(
				'missing_step_id',
				sprintf(
					/* translators: %d: Step number */
					__( 'Step %d: ID ist erforderlich.', 'recruiting-playbook' ),
					$index + 1
				),
				[ 'status' => 422 ]
			);
		}

		// Title erforderlich.
		if ( empty( $step['title'] ) ) {
			return new WP_Error(
				'missing_step_title',
				sprintf(
					/* translators: %d: Step number */
					__( 'Step %d: Titel ist erforderlich.', 'recruiting-playbook' ),
					$index + 1
				),
				[ 'status' => 422 ]
			);
		}

		// Fields muss Array sein (kann leer sein).
		if ( isset( $step['fields'] ) && ! is_array( $step['fields'] ) ) {
			return new WP_Error(
				'invalid_step_fields',
				sprintf(
					/* translators: %d: Step number */
					__( 'Step %d: Felder müssen ein Array sein.', 'recruiting-playbook' ),
					$index + 1
				),
				[ 'status' => 422 ]
			);
		}

		return true;
	}

	/**
	 * Alle sichtbaren Feld-Keys aus der Konfiguration extrahieren
	 *
	 * @param array $config Konfiguration.
	 * @return array<string>
	 */
	private function getVisibleFieldKeys( array $config ): array {
		$field_keys = [];

		foreach ( $config['steps'] as $step ) {
			if ( empty( $step['fields'] ) || ! is_array( $step['fields'] ) ) {
				continue;
			}

			foreach ( $step['fields'] as $field ) {
				if ( ! empty( $field['is_visible'] ) && ! empty( $field['field_key'] ) ) {
					$field_keys[] = $field['field_key'];
				}
			}
		}

		return $field_keys;
	}

	/**
	 * Prüfen ob privacy_consent als System-Feld im Finale-Step vorhanden ist
	 *
	 * @param array $config Konfiguration.
	 * @return bool
	 */
	private function hasPrivacyConsentSystemField( array $config ): bool {
		foreach ( $config['steps'] as $step ) {
			if ( empty( $step['is_finale'] ) ) {
				continue;
			}

			if ( empty( $step['system_fields'] ) || ! is_array( $step['system_fields'] ) ) {
				return false;
			}

			foreach ( $step['system_fields'] as $system_field ) {
				if ( 'privacy_consent' === ( $system_field['field_key'] ?? '' ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Prüfen ob ein Feld entfernt werden kann
	 *
	 * Pflichtfelder (first_name, last_name, email, privacy_consent) können nicht entfernt werden.
	 *
	 * @param string $field_key Feld-Key.
	 * @return bool True wenn entfernbar, false wenn Pflichtfeld.
	 */
	public function isFieldRemovable( string $field_key ): bool {
		return ! in_array( $field_key, self::REQUIRED_FIELDS, true );
	}

	/**
	 * Standard-Konfiguration laden (Schema v2)
	 *
	 * Struktur:
	 * - version: 2
	 * - system_fields: Hardcodierte Felder pro Step (file_upload, summary, privacy_consent)
	 * - is_removable: Flag ob Feld entfernt werden kann (false für Pflichtfelder)
	 *
	 * @return array
	 */
	public function getDefaultConfig(): array {
		return [
			'version'  => 2,
			'settings' => [
				'showStepIndicator' => true,
				'showStepTitles'    => true,
				'animateSteps'      => true,
			],
			'steps'    => [
				[
					'id'        => 'step_personal',
					'title'     => __( 'Persönliche Daten', 'recruiting-playbook' ),
					'position'  => 1,
					'deletable' => false,
					'fields'    => [
						[
							'field_key'    => 'first_name',
							'is_visible'   => true,
							'is_required'  => true,
							'is_removable' => false,
							'width'        => 'half',
							'settings'     => [
								'label'       => __( 'Vorname', 'recruiting-playbook' ),
								'placeholder' => 'Max',
							],
						],
						[
							'field_key'    => 'last_name',
							'is_visible'   => true,
							'is_required'  => true,
							'is_removable' => false,
							'width'        => 'half',
							'settings'     => [
								'label'       => __( 'Nachname', 'recruiting-playbook' ),
								'placeholder' => 'Mustermann',
							],
						],
						[
							'field_key'    => 'email',
							'is_visible'   => true,
							'is_required'  => true,
							'is_removable' => false,
							'width'        => 'full',
							'settings'     => [
								'label'       => __( 'E-Mail-Adresse', 'recruiting-playbook' ),
								'placeholder' => 'max@beispiel.de',
							],
						],
						[
							'field_key'    => 'phone',
							'is_visible'   => true,
							'is_required'  => false,
							'is_removable' => true,
							'width'        => 'full',
							'settings'     => [
								'label'       => __( 'Telefon', 'recruiting-playbook' ),
								'placeholder' => '+49...',
							],
						],
					],
				],
				[
					'id'            => 'step_documents',
					'title'         => __( 'Dokumente', 'recruiting-playbook' ),
					'position'      => 2,
					'deletable'     => true,
					'fields'        => [
						[
							'field_key'    => 'message',
							'is_visible'   => true,
							'is_required'  => false,
							'is_removable' => true,
							'width'        => 'full',
							'settings'     => [
								'label'       => __( 'Anschreiben / Nachricht', 'recruiting-playbook' ),
								'placeholder' => __( 'Warum möchten Sie bei uns arbeiten?', 'recruiting-playbook' ),
							],
						],
					],
					'system_fields' => [
						[
							'field_key' => 'file_upload',
							'type'      => 'file_upload',
							'settings'  => [
								'label'         => __( 'Bewerbungsunterlagen', 'recruiting-playbook' ),
								'help_text'     => __( 'PDF, Word - max. 10 MB pro Datei', 'recruiting-playbook' ),
								'allowed_types' => [ 'pdf', 'doc', 'docx' ],
								'max_file_size' => 10,
								'max_files'     => 5,
							],
						],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => __( 'Abschluss', 'recruiting-playbook' ),
					'position'      => 999,
					'deletable'     => false,
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[
							'field_key' => 'summary',
							'type'      => 'summary',
							'settings'  => [
								'title'           => __( 'Ihre Angaben im Überblick', 'recruiting-playbook' ),
								'layout'          => 'two-column',
								'additional_text' => __( 'Bitte prüfen Sie Ihre Angaben vor dem Absenden.', 'recruiting-playbook' ),
								'show_only_filled' => false,
							],
						],
						[
							'field_key'    => 'privacy_consent',
							'type'         => 'privacy_consent',
							'is_removable' => false,
							'settings'     => [
								'checkbox_text' => __( 'Ich habe die {datenschutz_link} gelesen und stimme der Verarbeitung meiner Daten zu.', 'recruiting-playbook' ),
								'link_text'     => __( 'Datenschutzerklärung', 'recruiting-playbook' ),
								'privacy_url'   => get_privacy_policy_url() ?: '/datenschutz',
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Konfiguration von v1 auf v2 migrieren
	 *
	 * Fügt system_fields und is_removable Flags hinzu.
	 *
	 * @param array $config Alte Konfiguration.
	 * @return array Migrierte Konfiguration.
	 */
	public function migrateConfig( array $config ): array {
		// Bereits v2 oder höher - keine Migration nötig.
		if ( ( $config['version'] ?? 1 ) >= 2 ) {
			return $config;
		}

		// Steps durchgehen und migrieren.
		foreach ( $config['steps'] as &$step ) {
			// Dokumente-Step: file_upload als System-Feld hinzufügen.
			if ( 'step_documents' === $step['id'] ) {
				$step['system_fields'] = [
					[
						'field_key' => 'file_upload',
						'type'      => 'file_upload',
						'settings'  => [
							'label'         => __( 'Bewerbungsunterlagen', 'recruiting-playbook' ),
							'help_text'     => __( 'PDF, Word - max. 10 MB pro Datei', 'recruiting-playbook' ),
							'allowed_types' => [ 'pdf', 'doc', 'docx' ],
							'max_file_size' => 10,
							'max_files'     => 5,
						],
					],
				];

				// resume-Feld aus fields entfernen (wird durch file_upload ersetzt).
				$step['fields'] = array_values(
					array_filter(
						$step['fields'] ?? [],
						function ( $field ) {
							return 'resume' !== ( $field['field_key'] ?? '' );
						}
					)
				);
			}

			// Finale-Step: summary und privacy_consent als System-Felder.
			if ( ! empty( $step['is_finale'] ) ) {
				$step['system_fields'] = [
					[
						'field_key' => 'summary',
						'type'      => 'summary',
						'settings'  => [
							'title'            => __( 'Ihre Angaben im Überblick', 'recruiting-playbook' ),
							'layout'           => 'two-column',
							'additional_text'  => __( 'Bitte prüfen Sie Ihre Angaben vor dem Absenden.', 'recruiting-playbook' ),
							'show_only_filled' => false,
						],
					],
					[
						'field_key'    => 'privacy_consent',
						'type'         => 'privacy_consent',
						'is_removable' => false,
						'settings'     => [
							'checkbox_text' => __( 'Ich habe die {datenschutz_link} gelesen und stimme der Verarbeitung meiner Daten zu.', 'recruiting-playbook' ),
							'link_text'     => __( 'Datenschutzerklärung', 'recruiting-playbook' ),
							'privacy_url'   => get_privacy_policy_url() ?: '/datenschutz',
						],
					],
				];

				// privacy_consent aus fields entfernen (jetzt in system_fields).
				$step['fields'] = array_values(
					array_filter(
						$step['fields'] ?? [],
						function ( $field ) {
							return 'privacy_consent' !== ( $field['field_key'] ?? '' );
						}
					)
				);
			}

			// is_removable Flag für alle Felder hinzufügen.
			if ( ! empty( $step['fields'] ) ) {
				foreach ( $step['fields'] as &$field ) {
					if ( ! isset( $field['is_removable'] ) ) {
						$field['is_removable'] = $this->isFieldRemovable( $field['field_key'] ?? '' );
					}

					// Default width hinzufügen.
					if ( ! isset( $field['width'] ) ) {
						$field['width'] = 'full';
						// Vorname/Nachname nebeneinander.
						if ( in_array( $field['field_key'] ?? '', [ 'first_name', 'last_name' ], true ) ) {
							$field['width'] = 'half';
						}
					}
				}
			}
		}

		// Version auf 2 setzen.
		$config['version'] = 2;

		return $config;
	}

	/**
	 * Sicherstellen dass erforderliche System-Felder vorhanden sind
	 *
	 * Diese Methode stellt sicher, dass der Finale-Step immer die
	 * erforderlichen System-Felder (summary, privacy_consent) enthält.
	 *
	 * @param array $config Konfiguration.
	 * @return array Korrigierte Konfiguration.
	 */
	private function ensureRequiredSystemFields( array $config ): array {
		if ( empty( $config['steps'] ) || ! is_array( $config['steps'] ) ) {
			return $config;
		}

		foreach ( $config['steps'] as &$step ) {
			// Nur Finale-Step prüfen.
			if ( empty( $step['is_finale'] ) ) {
				continue;
			}

			// system_fields Array initialisieren falls nicht vorhanden.
			if ( ! isset( $step['system_fields'] ) || ! is_array( $step['system_fields'] ) ) {
				$step['system_fields'] = [];
			}

			// Prüfen ob summary vorhanden ist.
			$has_summary = false;
			foreach ( $step['system_fields'] as $sf ) {
				if ( 'summary' === ( $sf['field_key'] ?? '' ) ) {
					$has_summary = true;
					break;
				}
			}

			// Summary hinzufügen falls nicht vorhanden.
			if ( ! $has_summary ) {
				array_unshift(
					$step['system_fields'],
					[
						'field_key' => 'summary',
						'type'      => 'summary',
						'settings'  => [
							'title'            => __( 'Ihre Angaben im Überblick', 'recruiting-playbook' ),
							'layout'           => 'two-column',
							'additional_text'  => __( 'Bitte prüfen Sie Ihre Angaben vor dem Absenden.', 'recruiting-playbook' ),
							'show_only_filled' => false,
						],
					]
				);
			}

			// Prüfen ob privacy_consent vorhanden ist.
			$has_privacy = false;
			foreach ( $step['system_fields'] as $sf ) {
				if ( 'privacy_consent' === ( $sf['field_key'] ?? '' ) ) {
					$has_privacy = true;
					break;
				}
			}

			// privacy_consent hinzufügen falls nicht vorhanden.
			if ( ! $has_privacy ) {
				$step['system_fields'][] = [
					'field_key'    => 'privacy_consent',
					'type'         => 'privacy_consent',
					'is_removable' => false,
					'settings'     => [
						'checkbox_text' => __( 'Ich habe die {datenschutz_link} gelesen und stimme der Verarbeitung meiner Daten zu.', 'recruiting-playbook' ),
						'link_text'     => __( 'Datenschutzerklärung', 'recruiting-playbook' ),
						'privacy_url'   => function_exists( 'get_privacy_policy_url' ) ? ( get_privacy_policy_url() ?: '/datenschutz' ) : '/datenschutz',
					],
				];
			}

			// privacy_consent aus fields entfernen (wird über system_fields gerendert).
			if ( isset( $step['fields'] ) && is_array( $step['fields'] ) ) {
				$step['fields'] = array_values(
					array_filter(
						$step['fields'],
						fn( $f ) => ( $f['field_key'] ?? '' ) !== 'privacy_consent'
					)
				);
			}
		}

		return $config;
	}

	/**
	 * Verfügbare Felder für den Builder laden
	 *
	 * Lädt alle Felder (System + Custom) mit ihren vollständigen Definitionen.
	 * Filtert Felder heraus, die jetzt über system_fields im Finale-Step gehandhabt werden.
	 *
	 * @return array
	 */
	public function getAvailableFields(): array {
		// Load ALL fields (system + custom), not just system fields.
		$fields = $this->field_repository->findAll();

		// Felder die jetzt über system_fields gehandhabt werden (nicht mehr als normale Felder anzeigen).
		$system_field_keys = [ 'privacy_consent' ];

		// Felder filtern.
		$filtered = array_filter(
			$fields,
			fn( $field ) => ! in_array( $field->getFieldKey(), $system_field_keys, true )
		);

		// array_values() um numerische Indizes zu erhalten (wichtig für JSON-Array statt Objekt).
		return array_values(
			array_map(
				function ( $field ) {
					return [
						'field_key'   => $field->getFieldKey(),
						'field_type'  => $field->getFieldType(),
						'label'       => $field->getLabel(),
						'placeholder' => $field->getPlaceholder(),
						'description' => $field->getDescription(),
						'is_required' => $field->isRequired(),
						'is_system'   => $field->isSystem(),
						'settings'    => $field->getSettings(),
						'validation'  => $field->getValidation(),
					];
				},
				$filtered
			)
		);
	}

	/**
	 * Vollständige Builder-Daten laden
	 *
	 * Liefert Draft-Konfiguration + verfügbare Felder + Status.
	 *
	 * @return array
	 */
	public function getBuilderData(): array {
		return [
			'draft'             => $this->getDraft(),
			'published_version' => $this->getPublishedVersion(),
			'has_changes'       => $this->hasUnpublishedChanges(),
			'available_fields'  => $this->getAvailableFields(),
		];
	}

	/**
	 * Aktive (sichtbare) Felder aus der Published-Konfiguration laden
	 *
	 * Liefert alle sichtbaren Felder mit ihren vollständigen Definitionen
	 * sowie System-Felder separat für die ApplicantDetail-Ansicht.
	 *
	 * @return array{fields: array, system_fields: array}
	 */
	public function getActiveFields(): array {
		// Cache-Key basierend auf Published-Version.
		$cache_key = 'rp_active_fields_v' . $this->getPublishedVersion();
		$cached    = wp_cache_get( $cache_key, 'recruiting_playbook' );

		if ( false !== $cached ) {
			return $cached;
		}

		$config = $this->getPublished();

		// Alle Field-Definitionen laden für vollständige Daten.
		$field_definitions = [];
		foreach ( $this->field_repository->findAll() as $field ) {
			$field_definitions[ $field->getFieldKey() ] = [
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

		$fields        = [];
		$system_fields = [];

		// Felder aus Steps extrahieren.
		foreach ( $config['steps'] ?? [] as $step ) {
			// Steps müssen Arrays sein.
			if ( ! is_array( $step ) ) {
				continue;
			}

			// Reguläre Felder.
			if ( ! empty( $step['fields'] ) && is_array( $step['fields'] ) ) {
				foreach ( $step['fields'] as $field_config ) {
					// Field-Config muss Array sein.
					if ( ! is_array( $field_config ) ) {
						continue;
					}

					// Nur sichtbare Felder.
					if ( empty( $field_config['is_visible'] ) ) {
						continue;
					}

					$field_key = $field_config['field_key'] ?? '';

					if ( empty( $field_key ) || ! isset( $field_definitions[ $field_key ] ) ) {
						continue;
					}

					$definition = $field_definitions[ $field_key ];

					// Label-Validierung: nur Strings akzeptieren.
					$custom_label = $field_config['settings']['label'] ?? null;
					$label        = is_string( $custom_label ) ? $custom_label : $definition['label'];

					$fields[] = [
						'field_key'   => $field_key,
						'field_type'  => $definition['field_type'],
						'label'       => $label,
						'is_system'   => $definition['is_system'],
						'is_required' => $field_config['is_required'] ?? $definition['is_required'],
						'options'     => $definition['options'],
						'step_id'     => $step['id'] ?? null,
					];
				}
			}

			// System-Felder (file_upload, summary, privacy_consent).
			if ( ! empty( $step['system_fields'] ) && is_array( $step['system_fields'] ) ) {
				foreach ( $step['system_fields'] as $system_field ) {
					// System-Field-Config muss Array sein.
					if ( ! is_array( $system_field ) ) {
						continue;
					}

					$field_key = $system_field['field_key'] ?? '';
					$type      = $system_field['type'] ?? $field_key;
					$settings  = is_array( $system_field['settings'] ?? null ) ? $system_field['settings'] : [];

					// Label-Validierung.
					$system_label = $settings['label'] ?? null;
					$label        = is_string( $system_label ) ? $system_label : ucfirst( str_replace( '_', ' ', $field_key ) );

					$system_fields[] = [
						'field_key' => $field_key,
						'type'      => $type,
						'label'     => $label,
						'settings'  => $settings,
						'step_id'   => $step['id'] ?? null,
					];
				}
			}
		}

		$result = [
			'fields'        => $fields,
			'system_fields' => $system_fields,
		];

		// 5 Minuten cachen.
		wp_cache_set( $cache_key, $result, 'recruiting_playbook', 300 );

		return $result;
	}
}

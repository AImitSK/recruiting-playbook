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
	 *
	 * @return array
	 */
	public function getDraft(): array {
		$draft = $this->repository->getDraft();

		if ( $draft ) {
			return $draft['config_data'];
		}

		// Fallback auf Default-Konfiguration.
		return $this->getDefaultConfig();
	}

	/**
	 * Published-Konfiguration laden
	 *
	 * Lädt die veröffentlichte Version oder erstellt eine Standard-Konfiguration.
	 *
	 * @return array
	 */
	public function getPublished(): array {
		$published = $this->repository->getPublished();

		if ( $published ) {
			return $published['config_data'];
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

		// Pflichtfelder prüfen (mind. E-Mail für Bewerbung).
		$required_fields = $this->getRequiredFieldKeys( $config );

		if ( ! in_array( 'email', $required_fields, true ) ) {
			return new WP_Error(
				'missing_email_field',
				__( 'Das E-Mail-Feld ist erforderlich und muss sichtbar sein.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		if ( ! in_array( 'privacy_consent', $required_fields, true ) ) {
			return new WP_Error(
				'missing_privacy_consent',
				__( 'Das Datenschutz-Feld ist erforderlich und muss sichtbar sein.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
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
	private function getRequiredFieldKeys( array $config ): array {
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
	 * Standard-Konfiguration laden
	 *
	 * @return array
	 */
	public function getDefaultConfig(): array {
		return [
			'version'  => 1,
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
							'field_key'   => 'first_name',
							'is_visible'  => true,
							'is_required' => true,
						],
						[
							'field_key'   => 'last_name',
							'is_visible'  => true,
							'is_required' => true,
						],
						[
							'field_key'   => 'email',
							'is_visible'  => true,
							'is_required' => true,
						],
						[
							'field_key'   => 'phone',
							'is_visible'  => true,
							'is_required' => false,
						],
					],
				],
				[
					'id'        => 'step_documents',
					'title'     => __( 'Dokumente', 'recruiting-playbook' ),
					'position'  => 2,
					'deletable' => true,
					'fields'    => [
						[
							'field_key'   => 'message',
							'is_visible'  => true,
							'is_required' => false,
						],
						[
							'field_key'   => 'resume',
							'is_visible'  => true,
							'is_required' => true,
						],
					],
				],
				[
					'id'        => 'step_finale',
					'title'     => __( 'Abschluss', 'recruiting-playbook' ),
					'position'  => 999,
					'deletable' => false,
					'is_finale' => true,
					'fields'    => [
						[
							'field_key'   => 'privacy_consent',
							'is_visible'  => true,
							'is_required' => true,
						],
					],
				],
			],
		];
	}

	/**
	 * Verfügbare Felder für den Builder laden
	 *
	 * Lädt alle Felder (System + Custom) mit ihren vollständigen Definitionen.
	 *
	 * @return array
	 */
	public function getAvailableFields(): array {
		// Load ALL fields (system + custom), not just system fields
		$fields = $this->field_repository->findAll();

		return array_map(
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
			$fields
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
}

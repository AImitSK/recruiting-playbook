<?php
/**
 * FormTemplate Service
 *
 * Geschäftslogik für Formular-Templates im Custom Fields Builder.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FormTemplate;
use RecruitingPlaybook\Repositories\FormTemplateRepository;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use WP_Error;

/**
 * Service für Formular-Templates
 */
class FormTemplateService {

	/**
	 * Template Repository
	 *
	 * @var FormTemplateRepository
	 */
	private FormTemplateRepository $repository;

	/**
	 * Field Definition Repository
	 *
	 * @var FieldDefinitionRepository
	 */
	private FieldDefinitionRepository $field_repository;

	/**
	 * Constructor
	 *
	 * @param FormTemplateRepository|null    $repository       Repository-Instanz.
	 * @param FieldDefinitionRepository|null $field_repository Field Repository.
	 */
	public function __construct(
		?FormTemplateRepository $repository = null,
		?FieldDefinitionRepository $field_repository = null
	) {
		$this->repository       = $repository ?? new FormTemplateRepository();
		$this->field_repository = $field_repository ?? new FieldDefinitionRepository();
	}

	/**
	 * Template laden
	 *
	 * @param int  $id          Template ID.
	 * @param bool $with_fields Mit Feldern laden.
	 * @return FormTemplate|null
	 */
	public function get( int $id, bool $with_fields = false ): ?FormTemplate {
		$template = $this->repository->find( $id );

		if ( $template && $with_fields ) {
			$fields = $this->field_repository->findByTemplate( $id );
			$template->setFields( $fields );
		}

		return $template;
	}

	/**
	 * Standard-Template laden
	 *
	 * @param bool $with_fields Mit Feldern laden.
	 * @return FormTemplate|null
	 */
	public function getDefault( bool $with_fields = false ): ?FormTemplate {
		$template = $this->repository->findDefault();

		if ( $template && $with_fields ) {
			$fields = $this->field_repository->findByTemplate( $template->getId() );
			$template->setFields( $fields );
		}

		return $template;
	}

	/**
	 * Alle Templates laden
	 *
	 * @param bool $with_usage_count Mit Nutzungsanzahl.
	 * @return array<FormTemplate>
	 */
	public function getAll( bool $with_usage_count = true ): array {
		return $this->repository->findAll( $with_usage_count );
	}

	/**
	 * Template erstellen
	 *
	 * @param array $data Template-Daten.
	 * @return FormTemplate|WP_Error
	 */
	public function create( array $data ): FormTemplate|WP_Error {
		// Validierung.
		$validation = $this->validateTemplateData( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Prüfen ob Name bereits existiert.
		if ( $this->repository->nameExists( $data['name'] ) ) {
			return new WP_Error(
				'duplicate_name',
				__( 'Ein Template mit diesem Namen existiert bereits.', 'recruiting-playbook' ),
				[ 'status' => 409 ]
			);
		}

		// Template erstellen.
		$template = $this->repository->create( $data );

		if ( ! $template ) {
			return new WP_Error(
				'create_failed',
				__( 'Template konnte nicht erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		// Wenn als Standard markiert, andere Standards entfernen.
		if ( ! empty( $data['is_default'] ) ) {
			$this->repository->setDefault( $template->getId() );
		}

		return $template;
	}

	/**
	 * Template aktualisieren
	 *
	 * @param int   $id   Template ID.
	 * @param array $data Update-Daten.
	 * @return FormTemplate|WP_Error
	 */
	public function update( int $id, array $data ): FormTemplate|WP_Error {
		$existing = $this->repository->find( $id );

		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'Template nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Name-Validierung bei Änderung.
		if ( isset( $data['name'] ) && $data['name'] !== $existing->getName() ) {
			if ( $this->repository->nameExists( $data['name'], $id ) ) {
				return new WP_Error(
					'duplicate_name',
					__( 'Ein Template mit diesem Namen existiert bereits.', 'recruiting-playbook' ),
					[ 'status' => 409 ]
				);
			}
		}

		$template = $this->repository->update( $id, $data );

		if ( ! $template ) {
			return new WP_Error(
				'update_failed',
				__( 'Template konnte nicht aktualisiert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		// Wenn als Standard markiert, andere Standards entfernen.
		if ( ! empty( $data['is_default'] ) && ! $existing->isDefault() ) {
			$this->repository->setDefault( $template->getId() );
		}

		return $template;
	}

	/**
	 * Template löschen
	 *
	 * @param int $id Template ID.
	 * @return true|WP_Error
	 */
	public function delete( int $id ): bool|WP_Error {
		$existing = $this->repository->find( $id );

		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'Template nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Prüfen ob Template in Verwendung ist.
		$usage_count = $this->repository->getUsageCount( $id );
		if ( $usage_count > 0 ) {
			return new WP_Error(
				'template_in_use',
				sprintf(
					/* translators: %d: Number of jobs using the template */
					__( 'Template kann nicht gelöscht werden. Es wird von %d Stelle(n) verwendet.', 'recruiting-playbook' ),
					$usage_count
				),
				[ 'status' => 409, 'usage_count' => $usage_count ]
			);
		}

		// Standard-Template kann nicht gelöscht werden wenn andere existieren.
		if ( $existing->isDefault() ) {
			$all_templates = $this->repository->findAll();
			if ( count( $all_templates ) > 1 ) {
				return new WP_Error(
					'cannot_delete_default',
					__( 'Das Standard-Template kann nicht gelöscht werden. Setzen Sie zuerst ein anderes Template als Standard.', 'recruiting-playbook' ),
					[ 'status' => 409 ]
				);
			}
		}

		$result = $this->repository->delete( $id );

		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Template konnte nicht gelöscht werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Template duplizieren
	 *
	 * @param int    $id       Template ID.
	 * @param string $new_name Neuer Name (optional).
	 * @return FormTemplate|WP_Error
	 */
	public function duplicate( int $id, string $new_name = '' ): FormTemplate|WP_Error {
		$existing = $this->repository->find( $id );

		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'Template nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Neuen Namen generieren wenn nicht angegeben.
		if ( empty( $new_name ) ) {
			$new_name = $this->generateUniqueName( $existing->getName() );
		}

		// Prüfen ob Name bereits existiert.
		if ( $this->repository->nameExists( $new_name ) ) {
			return new WP_Error(
				'duplicate_name',
				__( 'Ein Template mit diesem Namen existiert bereits.', 'recruiting-playbook' ),
				[ 'status' => 409 ]
			);
		}

		$template = $this->repository->duplicate( $id, $new_name );

		if ( ! $template ) {
			return new WP_Error(
				'duplicate_failed',
				__( 'Template konnte nicht dupliziert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return $template;
	}

	/**
	 * Template als Standard setzen
	 *
	 * @param int $id Template ID.
	 * @return true|WP_Error
	 */
	public function setDefault( int $id ): bool|WP_Error {
		$existing = $this->repository->find( $id );

		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'Template nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		if ( $existing->isDefault() ) {
			// Bereits Standard, nichts zu tun.
			return true;
		}

		$result = $this->repository->setDefault( $id );

		if ( ! $result ) {
			return new WP_Error(
				'set_default_failed',
				__( 'Template konnte nicht als Standard gesetzt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Nutzungsanzahl ermitteln
	 *
	 * @param int $id Template ID.
	 * @return int
	 */
	public function getUsageCount( int $id ): int {
		return $this->repository->getUsageCount( $id );
	}

	/**
	 * Template-Daten validieren
	 *
	 * @param array $data Template-Daten.
	 * @return true|WP_Error
	 */
	private function validateTemplateData( array $data ): bool|WP_Error {
		if ( empty( $data['name'] ) ) {
			return new WP_Error(
				'missing_name',
				__( 'Template-Name ist erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		if ( strlen( $data['name'] ) > 255 ) {
			return new WP_Error(
				'name_too_long',
				__( 'Template-Name darf maximal 255 Zeichen lang sein.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		return true;
	}

	/**
	 * Eindeutigen Namen generieren
	 *
	 * @param string $base_name Basis-Name.
	 * @return string
	 */
	private function generateUniqueName( string $base_name ): string {
		$new_name = sprintf(
			/* translators: %s: Original template name */
			__( '%s (Kopie)', 'recruiting-playbook' ),
			$base_name
		);

		$counter = 1;
		while ( $this->repository->nameExists( $new_name ) ) {
			++$counter;
			$new_name = sprintf(
				/* translators: 1: Original template name, 2: Copy number */
				__( '%1$s (Kopie %2$d)', 'recruiting-playbook' ),
				$base_name,
				$counter
			);
		}

		return $new_name;
	}
}

<?php
/**
 * FieldDefinition Service
 *
 * Geschäftslogik für Feld-Definitionen im Custom Fields Builder.
 *
 * @package RecruitingPlaybook\Services
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Models\FieldDefinition;
use RecruitingPlaybook\Repositories\FieldDefinitionRepository;
use WP_Error;

/**
 * Service für Feld-Definitionen
 */
class FieldDefinitionService {

	/**
	 * Repository
	 *
	 * @var FieldDefinitionRepository
	 */
	private FieldDefinitionRepository $repository;

	/**
	 * Erlaubte Feldtypen
	 *
	 * @var array<string>
	 */
	private const ALLOWED_FIELD_TYPES = [
		'text',
		'textarea',
		'email',
		'phone',
		'number',
		'select',
		'radio',
		'checkbox',
		'date',
		'file',
		'url',
		'heading',
	];

	/**
	 * Constructor
	 *
	 * @param FieldDefinitionRepository|null $repository Repository-Instanz.
	 */
	public function __construct( ?FieldDefinitionRepository $repository = null ) {
		$this->repository = $repository ?? new FieldDefinitionRepository();
	}

	/**
	 * Feld-Definition laden
	 *
	 * @param int $id Field ID.
	 * @return FieldDefinition|null
	 */
	public function get( int $id ): ?FieldDefinition {
		return $this->repository->find( $id );
	}

	/**
	 * Alle Felder für einen Job laden (kombiniert System + Global Custom + Template + Job-spezifisch)
	 *
	 * @param int $job_id Job ID.
	 * @return array<FieldDefinition>
	 */
	public function getFieldsForJob( int $job_id ): array {
		// System-Felder laden (Basisfelder).
		$system_fields = $this->repository->findSystemFields();

		// Globale Custom Fields laden (aus Custom Fields Builder).
		$global_custom_fields = $this->repository->findGlobalCustomFields();

		// Template-ID für diesen Job ermitteln.
		$template_id = get_post_meta( $job_id, '_rp_form_template_id', true );

		// Template-Felder laden falls vorhanden.
		$template_fields = [];
		if ( $template_id ) {
			$template_fields = $this->repository->findByTemplate( (int) $template_id );
		}

		// Job-spezifische Felder/Overrides laden.
		$job_fields = $this->repository->findByJob( $job_id );

		// Felder zusammenführen (System + Global Custom + Template + Job).
		$merged = $this->mergeFields( $system_fields, $global_custom_fields, $template_fields, $job_fields );

		// Job-spezifische Custom Fields Konfiguration anwenden (aus Meta Box).
		$merged = $this->applyJobCustomFieldsConfig( $job_id, $merged );

		return $merged;
	}

	/**
	 * Job-spezifische Custom Fields Konfiguration anwenden
	 *
	 * @param int                  $job_id Job ID.
	 * @param array<FieldDefinition> $fields Felder.
	 * @return array<FieldDefinition>
	 */
	private function applyJobCustomFieldsConfig( int $job_id, array $fields ): array {
		// Pro-Feature Check.
		if ( ! function_exists( 'rp_can' ) || ! rp_can( 'custom_fields' ) ) {
			return $fields;
		}

		// Meta-Box Konfiguration prüfen.
		$override = get_post_meta( $job_id, '_rp_custom_fields_override', true );

		if ( ! $override ) {
			return $fields; // Standard-Konfiguration verwenden.
		}

		$config = get_post_meta( $job_id, '_rp_custom_fields_config', true );

		if ( ! $config ) {
			return $fields;
		}

		$config_array = json_decode( $config, true );

		if ( ! is_array( $config_array ) ) {
			return $fields;
		}

		// Felder filtern basierend auf Konfiguration.
		foreach ( $fields as $field ) {
			$field_key = $field->getFieldKey();

			// System-Felder nicht ändern.
			if ( $field->isSystem() ) {
				continue;
			}

			// Wenn Feld in Konfiguration vorhanden, Status übernehmen.
			if ( isset( $config_array[ $field_key ] ) ) {
				// Wir müssen die enabled-Property ändern.
				// Da FieldDefinition immutable ist, erstellen wir ein neues mit geklonten Daten.
				$reflection = new \ReflectionProperty( $field, 'is_enabled' );
				$reflection->setAccessible( true );
				$reflection->setValue( $field, (bool) $config_array[ $field_key ] );
			}
		}

		return $fields;
	}

	/**
	 * Felder für ein Template laden
	 *
	 * @param int  $template_id Template ID.
	 * @param bool $active_only Nur aktive Felder.
	 * @return array<FieldDefinition>
	 */
	public function getFieldsForTemplate( int $template_id, bool $active_only = true ): array {
		// System-Felder + Template-Felder.
		$system_fields   = $this->repository->findSystemFields( $active_only );
		$template_fields = $this->repository->findByTemplate( $template_id, $active_only );

		return $this->mergeFields( $system_fields, $template_fields );
	}

	/**
	 * System-Felder laden
	 *
	 * @return array<FieldDefinition>
	 */
	public function getSystemFields(): array {
		return $this->repository->findSystemFields();
	}

	/**
	 * Alle Felder laden (System + Custom)
	 *
	 * @param bool $active_only Nur aktive Felder.
	 * @return array<FieldDefinition>
	 */
	public function getAllFields( bool $active_only = false ): array {
		return $this->repository->findAll( [
			'template_id'    => null,
			'job_id'         => null,
			'include_system' => true,
			'active_only'    => $active_only,
		] );
	}

	/**
	 * Nur aktive Felder laden (für Formulare)
	 *
	 * @return array<FieldDefinition>
	 */
	public function getActiveFields(): array {
		return $this->getAllFields( true );
	}

	/**
	 * Feld-Definition erstellen
	 *
	 * @param array $data Feld-Daten.
	 * @return FieldDefinition|WP_Error
	 */
	public function create( array $data ): FieldDefinition|WP_Error {
		// Map 'type' to 'field_type' for API compatibility.
		if ( isset( $data['type'] ) && ! isset( $data['field_type'] ) ) {
			$data['field_type'] = $data['type'];
		}
		unset( $data['type'] ); // Remove original API field.

		// Map 'is_enabled' to 'is_active' for API compatibility.
		if ( isset( $data['is_enabled'] ) && ! isset( $data['is_active'] ) ) {
			$data['is_active'] = $data['is_enabled'] ? 1 : 0;
		}
		unset( $data['is_enabled'] ); // Remove original API field.

		// Remove ID if sent (auto-generated).
		unset( $data['id'] );

		// Validierung.
		$validation = $this->validateFieldData( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Field Key validieren.
		if ( ! $this->isValidFieldKey( $data['field_key'] ) ) {
			return new WP_Error(
				'invalid_field_key',
				__( 'Ungültiger Feld-Schlüssel. Erlaubt sind Kleinbuchstaben, Zahlen und Unterstriche.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		// Prüfen ob Field Key bereits existiert.
		$template_id = $data['template_id'] ?? null;
		$job_id      = $data['job_id'] ?? null;

		if ( $this->repository->fieldKeyExists( $data['field_key'], $template_id, $job_id ) ) {
			return new WP_Error(
				'duplicate_field_key',
				__( 'Ein Feld mit diesem Schlüssel existiert bereits.', 'recruiting-playbook' ),
				[ 'status' => 409 ]
			);
		}

		// Position ermitteln wenn nicht angegeben.
		if ( ! isset( $data['position'] ) ) {
			$data['position'] = $this->repository->getNextPosition( $template_id, $job_id );
		}

		// Feld erstellen.
		$field = $this->repository->create( $data );

		if ( ! $field ) {
			return new WP_Error(
				'create_failed',
				__( 'Feld konnte nicht erstellt werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return $field;
	}

	/**
	 * Feld-Definition aktualisieren
	 *
	 * @param int   $id   Field ID.
	 * @param array $data Update-Daten.
	 * @return FieldDefinition|WP_Error
	 */
	public function update( int $id, array $data ): FieldDefinition|WP_Error {
		$existing = $this->repository->find( $id );

		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'Feld nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Map 'type' to 'field_type' for API compatibility.
		if ( isset( $data['type'] ) && ! isset( $data['field_type'] ) ) {
			$data['field_type'] = $data['type'];
		}
		unset( $data['type'] ); // Remove original API field.

		// Map 'is_enabled' to 'is_active' for API compatibility.
		if ( isset( $data['is_enabled'] ) && ! isset( $data['is_active'] ) ) {
			$data['is_active'] = $data['is_enabled'] ? 1 : 0;
		}
		unset( $data['is_enabled'] ); // Remove original API field.

		// Extract options from settings if present (Frontend sends settings.options).
		if ( isset( $data['settings']['options'] ) && ! isset( $data['options'] ) ) {
			$data['options'] = $data['settings']['options'];
			unset( $data['settings']['options'] );
		}

		// Remove ID from update data (cannot be changed).
		unset( $data['id'] );

		// System-Felder: field_key und field_type dürfen nicht geändert werden.
		if ( $existing->isSystem() ) {
			unset( $data['field_key'], $data['field_type'], $data['is_system'] );
		}

		// Field Key Validierung bei Änderung.
		if ( isset( $data['field_key'] ) ) {
			if ( ! $this->isValidFieldKey( $data['field_key'] ) ) {
				return new WP_Error(
					'invalid_field_key',
					__( 'Ungültiger Feld-Schlüssel.', 'recruiting-playbook' ),
					[ 'status' => 422 ]
				);
			}

			// Duplikat-Check.
			if ( $this->repository->fieldKeyExists(
				$data['field_key'],
				$existing->getTemplateId(),
				$existing->getJobId(),
				$id
			) ) {
				return new WP_Error(
					'duplicate_field_key',
					__( 'Ein Feld mit diesem Schlüssel existiert bereits.', 'recruiting-playbook' ),
					[ 'status' => 409 ]
				);
			}
		}

		// Feldtyp-Validierung bei Änderung.
		if ( isset( $data['field_type'] ) && ! $this->isValidFieldType( $data['field_type'] ) ) {
			return new WP_Error(
				'invalid_field_type',
				__( 'Ungültiger Feldtyp.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		$field = $this->repository->update( $id, $data );

		if ( ! $field ) {
			return new WP_Error(
				'update_failed',
				__( 'Feld konnte nicht aktualisiert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return $field;
	}

	/**
	 * Feld-Definition löschen
	 *
	 * @param int $id Field ID.
	 * @return true|WP_Error
	 */
	public function delete( int $id ): bool|WP_Error {
		$existing = $this->repository->find( $id );

		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'Feld nicht gefunden.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// System-Felder können nicht gelöscht werden.
		if ( $existing->isSystem() ) {
			return new WP_Error(
				'cannot_delete_system_field',
				__( 'System-Felder können nicht gelöscht werden.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		$field_key = $existing->getFieldKey();

		// Feld aus Formular-Konfiguration entfernen (Draft + Published).
		$this->removeFieldFromFormConfig( $field_key );

		$result = $this->repository->delete( $id );

		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Feld konnte nicht gelöscht werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Feld aus Formular-Konfiguration entfernen
	 *
	 * Entfernt ein Feld aus Draft und Published Config, wenn es dort verwendet wird.
	 *
	 * @param string $field_key Field Key zu entfernen.
	 */
	private function removeFieldFromFormConfig( string $field_key ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_form_config';

		// Beide Configs laden und aktualisieren.
		foreach ( [ 'draft', 'published' ] as $config_type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, config_data FROM {$table} WHERE config_type = %s",
					$config_type
				)
			);

			if ( ! $row || empty( $row->config_data ) ) {
				continue;
			}

			$config  = json_decode( $row->config_data, true );
			$changed = false;

			if ( ! empty( $config['steps'] ) && is_array( $config['steps'] ) ) {
				foreach ( $config['steps'] as &$step ) {
					if ( ! empty( $step['fields'] ) && is_array( $step['fields'] ) ) {
						$original_count  = count( $step['fields'] );
						$step['fields']  = array_values(
							array_filter(
								$step['fields'],
								fn( $f ) => ( $f['field_key'] ?? '' ) !== $field_key
							)
						);
						if ( count( $step['fields'] ) !== $original_count ) {
							$changed = true;
						}
					}
				}
				unset( $step );
			}

			// Config speichern wenn geändert.
			if ( $changed ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table,
					[ 'config_data' => wp_json_encode( $config ) ],
					[ 'id' => $row->id ],
					[ '%s' ],
					[ '%d' ]
				);
			}
		}
	}

	/**
	 * Felder neu sortieren
	 *
	 * @param array $positions Array von [id => position].
	 * @return true|WP_Error
	 */
	public function reorder( array $positions ): bool|WP_Error {
		if ( empty( $positions ) ) {
			return new WP_Error(
				'empty_positions',
				__( 'Keine Positionen angegeben.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		$result = $this->repository->reorder( $positions );

		if ( ! $result ) {
			return new WP_Error(
				'reorder_failed',
				__( 'Reihenfolge konnte nicht geändert werden.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		return true;
	}

	/**
	 * Prüfen ob Field Key gültig ist
	 *
	 * @param string $field_key Field Key.
	 * @return bool
	 */
	public function isValidFieldKey( string $field_key ): bool {
		// Nur Kleinbuchstaben, Zahlen und Unterstriche, 2-50 Zeichen.
		return (bool) preg_match( '/^[a-z][a-z0-9_]{1,49}$/', $field_key );
	}

	/**
	 * Prüfen ob Feldtyp gültig ist
	 *
	 * @param string $field_type Field Type.
	 * @return bool
	 */
	public function isValidFieldType( string $field_type ): bool {
		return in_array( $field_type, self::ALLOWED_FIELD_TYPES, true );
	}

	/**
	 * Verfügbare Feldtypen laden
	 *
	 * @return array
	 */
	public function getAvailableFieldTypes(): array {
		return [
			[
				'type'  => 'text',
				'label' => __( 'Textfeld', 'recruiting-playbook' ),
				'icon'  => 'type',
				'group' => 'text',
			],
			[
				'type'  => 'textarea',
				'label' => __( 'Textbereich', 'recruiting-playbook' ),
				'icon'  => 'align-left',
				'group' => 'text',
			],
			[
				'type'  => 'email',
				'label' => __( 'E-Mail', 'recruiting-playbook' ),
				'icon'  => 'mail',
				'group' => 'text',
			],
			[
				'type'  => 'phone',
				'label' => __( 'Telefon', 'recruiting-playbook' ),
				'icon'  => 'phone',
				'group' => 'text',
			],
			[
				'type'  => 'number',
				'label' => __( 'Zahl', 'recruiting-playbook' ),
				'icon'  => 'hash',
				'group' => 'text',
			],
			[
				'type'  => 'url',
				'label' => __( 'URL/Link', 'recruiting-playbook' ),
				'icon'  => 'link',
				'group' => 'text',
			],
			[
				'type'  => 'select',
				'label' => __( 'Dropdown', 'recruiting-playbook' ),
				'icon'  => 'chevron-down',
				'group' => 'choice',
			],
			[
				'type'  => 'radio',
				'label' => __( 'Radio-Buttons', 'recruiting-playbook' ),
				'icon'  => 'circle-dot',
				'group' => 'choice',
			],
			[
				'type'  => 'checkbox',
				'label' => __( 'Checkbox', 'recruiting-playbook' ),
				'icon'  => 'check-square',
				'group' => 'choice',
			],
			[
				'type'  => 'date',
				'label' => __( 'Datum', 'recruiting-playbook' ),
				'icon'  => 'calendar',
				'group' => 'special',
			],
			[
				'type'  => 'file',
				'label' => __( 'Datei-Upload', 'recruiting-playbook' ),
				'icon'  => 'paperclip',
				'group' => 'special',
			],
			[
				'type'  => 'heading',
				'label' => __( 'Überschrift', 'recruiting-playbook' ),
				'icon'  => 'heading',
				'group' => 'layout',
			],
		];
	}

	/**
	 * Feld-Daten validieren
	 *
	 * @param array $data Feld-Daten.
	 * @return true|WP_Error
	 */
	private function validateFieldData( array $data ): bool|WP_Error {
		// Pflichtfelder prüfen.
		if ( empty( $data['field_key'] ) ) {
			return new WP_Error(
				'missing_field_key',
				__( 'Feld-Schlüssel ist erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		if ( empty( $data['field_type'] ) ) {
			return new WP_Error(
				'missing_field_type',
				__( 'Feldtyp ist erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		if ( empty( $data['label'] ) ) {
			return new WP_Error(
				'missing_label',
				__( 'Label ist erforderlich.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		// Feldtyp validieren.
		if ( ! $this->isValidFieldType( $data['field_type'] ) ) {
			return new WP_Error(
				'invalid_field_type',
				__( 'Ungültiger Feldtyp.', 'recruiting-playbook' ),
				[ 'status' => 422 ]
			);
		}

		return true;
	}

	/**
	 * Felder zusammenführen (System + Global Custom + Template + Job)
	 *
	 * Felder werden in dieser Reihenfolge zusammengeführt (spätere überschreiben frühere):
	 * 1. System-Felder (Basisfelder)
	 * 2. Globale Custom Fields (aus Custom Fields Builder)
	 * 3. Template-Felder (falls Template zugewiesen)
	 * 4. Job-spezifische Felder (Overrides)
	 *
	 * @param array<FieldDefinition> ...$field_arrays Field arrays in merge order.
	 * @return array<FieldDefinition>
	 */
	private function mergeFields( array ...$field_arrays ): array {
		$merged = [];

		// Alle Feld-Arrays der Reihe nach zusammenführen.
		foreach ( $field_arrays as $fields ) {
			foreach ( $fields as $field ) {
				$merged[ $field->getFieldKey() ] = $field;
			}
		}

		// Nach Position sortieren.
		$result = array_values( $merged );
		usort( $result, fn( FieldDefinition $a, FieldDefinition $b ) => $a->getPosition() <=> $b->getPosition() );

		return $result;
	}
}

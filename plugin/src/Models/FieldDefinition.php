<?php
/**
 * FieldDefinition Model
 *
 * Repräsentiert eine Feld-Definition für benutzerdefinierte Bewerbungsformulare.
 *
 * @package RecruitingPlaybook\Models
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Models;

defined( 'ABSPATH' ) || exit;

/**
 * FieldDefinition Model
 */
class FieldDefinition {

	/**
	 * Field ID
	 *
	 * @var int
	 */
	private int $id = 0;

	/**
	 * Template ID (null = globales Feld)
	 *
	 * @var int|null
	 */
	private ?int $template_id = null;

	/**
	 * Job ID (null = Template-Feld)
	 *
	 * @var int|null
	 */
	private ?int $job_id = null;

	/**
	 * Eindeutiger Feld-Schlüssel
	 *
	 * @var string
	 */
	private string $field_key = '';

	/**
	 * Feldtyp (text, select, file, etc.)
	 *
	 * @var string
	 */
	private string $field_type = 'text';

	/**
	 * Anzeigelabel
	 *
	 * @var string
	 */
	private string $label = '';

	/**
	 * Platzhalter-Text
	 *
	 * @var string|null
	 */
	private ?string $placeholder = null;

	/**
	 * Hilfetext/Beschreibung
	 *
	 * @var string|null
	 */
	private ?string $description = null;

	/**
	 * Optionen für Select/Radio/Checkbox (JSON)
	 *
	 * @var array|null
	 */
	private ?array $options = null;

	/**
	 * Validierungsregeln (JSON)
	 *
	 * @var array|null
	 */
	private ?array $validation = null;

	/**
	 * Conditional Logic Regeln (JSON)
	 *
	 * @var array|null
	 */
	private ?array $conditional = null;

	/**
	 * Zusätzliche Einstellungen (JSON)
	 *
	 * @var array|null
	 */
	private ?array $settings = null;

	/**
	 * Position/Sortierreihenfolge
	 *
	 * @var int
	 */
	private int $position = 0;

	/**
	 * Pflichtfeld
	 *
	 * @var bool
	 */
	private bool $is_required = false;

	/**
	 * System-Feld (nicht löschbar)
	 *
	 * @var bool
	 */
	private bool $is_system = false;

	/**
	 * Feld aktiv
	 *
	 * @var bool
	 */
	private bool $is_active = true;

	/**
	 * Erstellungsdatum
	 *
	 * @var string|null
	 */
	private ?string $created_at = null;

	/**
	 * Aktualisierungsdatum
	 *
	 * @var string|null
	 */
	private ?string $updated_at = null;

	/**
	 * Löschdatum (Soft Delete)
	 *
	 * @var string|null
	 */
	private ?string $deleted_at = null;

	/**
	 * Constructor
	 *
	 * @param array $data Optionale Initialisierungsdaten.
	 */
	public function __construct( array $data = [] ) {
		if ( ! empty( $data ) ) {
			$this->hydrate( $data );
		}
	}

	/**
	 * Model aus Array befüllen
	 *
	 * @param array $data Daten aus Datenbank.
	 * @return self
	 */
	public function hydrate( array $data ): self {
		if ( isset( $data['id'] ) ) {
			$this->id = (int) $data['id'];
		}
		if ( array_key_exists( 'template_id', $data ) ) {
			$this->template_id = $data['template_id'] !== null ? (int) $data['template_id'] : null;
		}
		if ( array_key_exists( 'job_id', $data ) ) {
			$this->job_id = $data['job_id'] !== null ? (int) $data['job_id'] : null;
		}
		if ( isset( $data['field_key'] ) ) {
			$this->field_key = (string) $data['field_key'];
		}
		if ( isset( $data['field_type'] ) ) {
			$this->field_type = (string) $data['field_type'];
		}
		if ( isset( $data['label'] ) ) {
			$this->label = (string) $data['label'];
		}
		if ( array_key_exists( 'placeholder', $data ) ) {
			$this->placeholder = $data['placeholder'] !== null ? (string) $data['placeholder'] : null;
		}
		if ( array_key_exists( 'description', $data ) ) {
			$this->description = $data['description'] !== null ? (string) $data['description'] : null;
		}
		if ( array_key_exists( 'options', $data ) ) {
			$this->options = $this->decodeJson( $data['options'] );
		}
		if ( array_key_exists( 'validation', $data ) ) {
			$this->validation = $this->decodeJson( $data['validation'] );
		}
		if ( array_key_exists( 'conditional', $data ) ) {
			$this->conditional = $this->decodeJson( $data['conditional'] );
		}
		if ( array_key_exists( 'settings', $data ) ) {
			$this->settings = $this->decodeJson( $data['settings'] );
		}
		if ( isset( $data['position'] ) ) {
			$this->position = (int) $data['position'];
		}
		if ( isset( $data['is_required'] ) ) {
			$this->is_required = (bool) $data['is_required'];
		}
		if ( isset( $data['is_system'] ) ) {
			$this->is_system = (bool) $data['is_system'];
		}
		if ( isset( $data['is_active'] ) ) {
			$this->is_active = (bool) $data['is_active'];
		}
		if ( isset( $data['created_at'] ) ) {
			$this->created_at = (string) $data['created_at'];
		}
		if ( isset( $data['updated_at'] ) ) {
			$this->updated_at = (string) $data['updated_at'];
		}
		if ( array_key_exists( 'deleted_at', $data ) ) {
			$this->deleted_at = $data['deleted_at'] !== null ? (string) $data['deleted_at'] : null;
		}

		return $this;
	}

	/**
	 * JSON-String dekodieren
	 *
	 * @param mixed $value JSON-String oder bereits Array.
	 * @return array|null
	 */
	private function decodeJson( $value ): ?array {
		if ( $value === null ) {
			return null;
		}
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && ! empty( $value ) ) {
			$decoded = json_decode( $value, true );
			return is_array( $decoded ) ? $decoded : null;
		}
		return null;
	}

	// -------------------------------------------------------------------------
	// Getters
	// -------------------------------------------------------------------------

	/**
	 * Get ID
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Get Template ID
	 *
	 * @return int|null
	 */
	public function getTemplateId(): ?int {
		return $this->template_id;
	}

	/**
	 * Get Job ID
	 *
	 * @return int|null
	 */
	public function getJobId(): ?int {
		return $this->job_id;
	}

	/**
	 * Get Field Key
	 *
	 * @return string
	 */
	public function getFieldKey(): string {
		return $this->field_key;
	}

	/**
	 * Get Field Type
	 *
	 * @return string
	 */
	public function getFieldType(): string {
		return $this->field_type;
	}

	/**
	 * Get Label
	 *
	 * @return string
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * Get Placeholder
	 *
	 * @return string|null
	 */
	public function getPlaceholder(): ?string {
		return $this->placeholder;
	}

	/**
	 * Get Description
	 *
	 * @return string|null
	 */
	public function getDescription(): ?string {
		return $this->description;
	}

	/**
	 * Get Options
	 *
	 * Falls die Optionen in der Haupt-options-Spalte leer sind,
	 * wird auf settings.options als Fallback zurückgegriffen.
	 *
	 * @return array|null
	 */
	public function getOptions(): ?array {
		// Direkte options-Spalte prüfen.
		if ( ! empty( $this->options ) ) {
			return $this->options;
		}

		// Fallback: settings.options (Frontend speichert dort).
		if ( ! empty( $this->settings['options'] ) && is_array( $this->settings['options'] ) ) {
			return $this->settings['options'];
		}

		return $this->options;
	}

	/**
	 * Get Validation rules
	 *
	 * @return array|null
	 */
	public function getValidation(): ?array {
		return $this->validation;
	}

	/**
	 * Get Conditional logic
	 *
	 * @return array|null
	 */
	public function getConditional(): ?array {
		return $this->conditional;
	}

	/**
	 * Get Settings
	 *
	 * @return array|null
	 */
	public function getSettings(): ?array {
		return $this->settings;
	}

	/**
	 * Get Position
	 *
	 * @return int
	 */
	public function getPosition(): int {
		return $this->position;
	}

	/**
	 * Get Created At
	 *
	 * @return string|null
	 */
	public function getCreatedAt(): ?string {
		return $this->created_at;
	}

	/**
	 * Get Updated At
	 *
	 * @return string|null
	 */
	public function getUpdatedAt(): ?string {
		return $this->updated_at;
	}

	/**
	 * Get Deleted At
	 *
	 * @return string|null
	 */
	public function getDeletedAt(): ?string {
		return $this->deleted_at;
	}

	// -------------------------------------------------------------------------
	// Boolean Getters
	// -------------------------------------------------------------------------

	/**
	 * Is Required
	 *
	 * @return bool
	 */
	public function isRequired(): bool {
		return $this->is_required;
	}

	/**
	 * Is System Field
	 *
	 * @return bool
	 */
	public function isSystem(): bool {
		return $this->is_system;
	}

	/**
	 * Is Active
	 *
	 * @return bool
	 */
	public function isActive(): bool {
		return $this->is_active;
	}

	/**
	 * Is Deleted (Soft Delete)
	 *
	 * @return bool
	 */
	public function isDeleted(): bool {
		return $this->deleted_at !== null;
	}

	/**
	 * Has Conditional Logic
	 *
	 * @return bool
	 */
	public function hasConditional(): bool {
		return ! empty( $this->conditional );
	}

	// -------------------------------------------------------------------------
	// Setters
	// -------------------------------------------------------------------------

	/**
	 * Set Template ID
	 *
	 * @param int|null $template_id Template ID.
	 * @return self
	 */
	public function setTemplateId( ?int $template_id ): self {
		$this->template_id = $template_id;
		return $this;
	}

	/**
	 * Set Job ID
	 *
	 * @param int|null $job_id Job ID.
	 * @return self
	 */
	public function setJobId( ?int $job_id ): self {
		$this->job_id = $job_id;
		return $this;
	}

	/**
	 * Set Field Key
	 *
	 * @param string $field_key Field Key.
	 * @return self
	 */
	public function setFieldKey( string $field_key ): self {
		$this->field_key = $field_key;
		return $this;
	}

	/**
	 * Set Field Type
	 *
	 * @param string $field_type Field Type.
	 * @return self
	 */
	public function setFieldType( string $field_type ): self {
		$this->field_type = $field_type;
		return $this;
	}

	/**
	 * Set Label
	 *
	 * @param string $label Label.
	 * @return self
	 */
	public function setLabel( string $label ): self {
		$this->label = $label;
		return $this;
	}

	/**
	 * Set Placeholder
	 *
	 * @param string|null $placeholder Placeholder.
	 * @return self
	 */
	public function setPlaceholder( ?string $placeholder ): self {
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 * Set Description
	 *
	 * @param string|null $description Description.
	 * @return self
	 */
	public function setDescription( ?string $description ): self {
		$this->description = $description;
		return $this;
	}

	/**
	 * Set Options
	 *
	 * @param array|null $options Options.
	 * @return self
	 */
	public function setOptions( ?array $options ): self {
		$this->options = $options;
		return $this;
	}

	/**
	 * Set Validation
	 *
	 * @param array|null $validation Validation rules.
	 * @return self
	 */
	public function setValidation( ?array $validation ): self {
		$this->validation = $validation;
		return $this;
	}

	/**
	 * Set Conditional
	 *
	 * @param array|null $conditional Conditional logic.
	 * @return self
	 */
	public function setConditional( ?array $conditional ): self {
		$this->conditional = $conditional;
		return $this;
	}

	/**
	 * Set Settings
	 *
	 * @param array|null $settings Settings.
	 * @return self
	 */
	public function setSettings( ?array $settings ): self {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * Set Position
	 *
	 * @param int $position Position.
	 * @return self
	 */
	public function setPosition( int $position ): self {
		$this->position = $position;
		return $this;
	}

	/**
	 * Set Required
	 *
	 * @param bool $is_required Required flag.
	 * @return self
	 */
	public function setRequired( bool $is_required ): self {
		$this->is_required = $is_required;
		return $this;
	}

	/**
	 * Set Active
	 *
	 * @param bool $is_active Active flag.
	 * @return self
	 */
	public function setActive( bool $is_active ): self {
		$this->is_active = $is_active;
		return $this;
	}

	// -------------------------------------------------------------------------
	// Conversion
	// -------------------------------------------------------------------------

	/**
	 * Model zu Array für API-Response
	 *
	 * @return array
	 */
	public function toArray(): array {
		return [
			'id'          => $this->id,
			'template_id' => $this->template_id,
			'job_id'      => $this->job_id,
			'field_key'   => $this->field_key,
			'field_type'  => $this->field_type,
			'label'       => $this->label,
			'placeholder' => $this->placeholder,
			'description' => $this->description,
			'options'     => $this->options,
			'validation'  => $this->validation,
			'conditional' => $this->conditional,
			'settings'    => $this->settings,
			'position'    => $this->position,
			'is_required' => $this->is_required,
			'is_system'   => $this->is_system,
			'is_active'   => $this->is_active,
			'created_at'  => $this->created_at,
			'updated_at'  => $this->updated_at,
		];
	}

	/**
	 * Model zu Array für Datenbank-Insert/Update
	 *
	 * @return array
	 */
	public function toDbArray(): array {
		$data = [
			'template_id' => $this->template_id,
			'job_id'      => $this->job_id,
			'field_key'   => $this->field_key,
			'field_type'  => $this->field_type,
			'label'       => $this->label,
			'placeholder' => $this->placeholder,
			'description' => $this->description,
			'options'     => $this->options !== null ? wp_json_encode( $this->options ) : null,
			'validation'  => $this->validation !== null ? wp_json_encode( $this->validation ) : null,
			'conditional' => $this->conditional !== null ? wp_json_encode( $this->conditional ) : null,
			'settings'    => $this->settings !== null ? wp_json_encode( $this->settings ) : null,
			'position'    => $this->position,
			'is_required' => $this->is_required ? 1 : 0,
			'is_system'   => $this->is_system ? 1 : 0,
			'is_active'   => $this->is_active ? 1 : 0,
		];

		return $data;
	}

	/**
	 * Statische Factory-Methode aus Array
	 *
	 * @param array $data Datenbank-Daten.
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		return new self( $data );
	}
}

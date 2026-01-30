<?php
/**
 * FormTemplate Model
 *
 * Repräsentiert ein Formular-Template für wiederverwendbare Feld-Konfigurationen.
 *
 * @package RecruitingPlaybook\Models
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Models;

defined( 'ABSPATH' ) || exit;

/**
 * FormTemplate Model
 */
class FormTemplate {

	/**
	 * Template ID
	 *
	 * @var int
	 */
	private int $id = 0;

	/**
	 * Template Name
	 *
	 * @var string
	 */
	private string $name = '';

	/**
	 * Beschreibung
	 *
	 * @var string|null
	 */
	private ?string $description = null;

	/**
	 * Ist Standard-Template
	 *
	 * @var bool
	 */
	private bool $is_default = false;

	/**
	 * Template-Einstellungen (JSON)
	 *
	 * @var array|null
	 */
	private ?array $settings = null;

	/**
	 * Erstellt von (User ID)
	 *
	 * @var int
	 */
	private int $created_by = 0;

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
	 * Zugehörige Felder (lazy loaded)
	 *
	 * @var array<FieldDefinition>|null
	 */
	private ?array $fields = null;

	/**
	 * Anzahl der Jobs die dieses Template nutzen
	 *
	 * @var int|null
	 */
	private ?int $usage_count = null;

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
		if ( isset( $data['name'] ) ) {
			$this->name = (string) $data['name'];
		}
		if ( array_key_exists( 'description', $data ) ) {
			$this->description = $data['description'] !== null ? (string) $data['description'] : null;
		}
		if ( isset( $data['is_default'] ) ) {
			$this->is_default = (bool) $data['is_default'];
		}
		if ( array_key_exists( 'settings', $data ) ) {
			$this->settings = $this->decodeJson( $data['settings'] );
		}
		if ( isset( $data['created_by'] ) ) {
			$this->created_by = (int) $data['created_by'];
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
		if ( isset( $data['usage_count'] ) ) {
			$this->usage_count = (int) $data['usage_count'];
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
	 * Get Name
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
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
	 * Is Default Template
	 *
	 * @return bool
	 */
	public function isDefault(): bool {
		return $this->is_default;
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
	 * Get specific setting value
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function getSetting( string $key, $default = null ) {
		return $this->settings[ $key ] ?? $default;
	}

	/**
	 * Get Created By (User ID)
	 *
	 * @return int
	 */
	public function getCreatedBy(): int {
		return $this->created_by;
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

	/**
	 * Is Deleted
	 *
	 * @return bool
	 */
	public function isDeleted(): bool {
		return $this->deleted_at !== null;
	}

	/**
	 * Get Fields (lazy loaded)
	 *
	 * @return array<FieldDefinition>
	 */
	public function getFields(): array {
		return $this->fields ?? [];
	}

	/**
	 * Set Fields
	 *
	 * @param array<FieldDefinition> $fields Fields.
	 * @return self
	 */
	public function setFields( array $fields ): self {
		$this->fields = $fields;
		return $this;
	}

	/**
	 * Get Usage Count
	 *
	 * @return int
	 */
	public function getUsageCount(): int {
		return $this->usage_count ?? 0;
	}

	/**
	 * Set Usage Count
	 *
	 * @param int $count Usage count.
	 * @return self
	 */
	public function setUsageCount( int $count ): self {
		$this->usage_count = $count;
		return $this;
	}

	/**
	 * Get Creator User Data
	 *
	 * @return array|null
	 */
	public function getCreator(): ?array {
		if ( $this->created_by <= 0 ) {
			return null;
		}

		$user = get_userdata( $this->created_by );
		if ( ! $user ) {
			return null;
		}

		return [
			'id'     => $user->ID,
			'name'   => $user->display_name,
			'avatar' => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
		];
	}

	// -------------------------------------------------------------------------
	// Setters
	// -------------------------------------------------------------------------

	/**
	 * Set Name
	 *
	 * @param string $name Name.
	 * @return self
	 */
	public function setName( string $name ): self {
		$this->name = $name;
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
	 * Set Default
	 *
	 * @param bool $is_default Is Default.
	 * @return self
	 */
	public function setDefault( bool $is_default ): self {
		$this->is_default = $is_default;
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
	 * Set Created By
	 *
	 * @param int $user_id User ID.
	 * @return self
	 */
	public function setCreatedBy( int $user_id ): self {
		$this->created_by = $user_id;
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
		$data = [
			'id'          => $this->id,
			'name'        => $this->name,
			'description' => $this->description,
			'is_default'  => $this->is_default,
			'settings'    => $this->settings,
			'created_by'  => $this->getCreator(),
			'created_at'  => $this->created_at,
			'updated_at'  => $this->updated_at,
			'usage_count' => $this->getUsageCount(),
		];

		// Felder hinzufügen wenn geladen.
		if ( $this->fields !== null ) {
			$data['fields'] = array_map(
				fn( FieldDefinition $field ) => $field->toArray(),
				$this->fields
			);
		}

		return $data;
	}

	/**
	 * Model zu Array für Datenbank-Insert/Update
	 *
	 * @return array
	 */
	public function toDbArray(): array {
		return [
			'name'        => $this->name,
			'description' => $this->description,
			'is_default'  => $this->is_default ? 1 : 0,
			'settings'    => $this->settings !== null ? wp_json_encode( $this->settings ) : null,
			'created_by'  => $this->created_by,
		];
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

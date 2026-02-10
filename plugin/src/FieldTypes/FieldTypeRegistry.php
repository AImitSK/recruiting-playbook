<?php
/**
 * Field Type Registry
 *
 * Verwaltet alle verfügbaren Feldtypen.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Contracts\FieldTypeInterface;

/**
 * Registry für Feldtypen
 */
class FieldTypeRegistry {

	/**
	 * Singleton-Instanz
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registrierte Feldtypen
	 *
	 * @var array<string, FieldTypeInterface>
	 */
	private array $types = [];

	/**
	 * Ob Standard-Typen registriert sind
	 *
	 * @var bool
	 */
	private bool $defaults_registered = false;

	/**
	 * Singleton-Zugriff
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Konstruktor (privat für Singleton)
	 */
	private function __construct() {
		// Standard-Typen werden lazy registriert.
	}

	/**
	 * Standard-Feldtypen registrieren
	 *
	 * @return void
	 */
	private function registerDefaults(): void {
		if ( $this->defaults_registered ) {
			return;
		}

		$default_types = [
			// Text Gruppe.
			new TextField(),
			new TextareaField(),
			new EmailField(),
			new PhoneField(),
			new UrlField(),

			// Choice Gruppe.
			new SelectField(),
			new RadioField(),
			new CheckboxField(),

			// Special Gruppe.
			new DateField(),

			// Layout Gruppe.
			new HtmlField(),
		];

		foreach ( $default_types as $type ) {
			$this->register( $type );
		}

		$this->defaults_registered = true;

		/**
		 * Aktion zum Registrieren weiterer Feldtypen.
		 *
		 * @param FieldTypeRegistry $registry Die Registry-Instanz.
		 */
		do_action( 'recruiting_playbook_register_field_types', $this );
	}

	/**
	 * Feldtyp registrieren
	 *
	 * @param FieldTypeInterface $type Feldtyp-Instanz.
	 * @return self Für Method Chaining.
	 */
	public function register( FieldTypeInterface $type ): self {
		$this->types[ $type->getType() ] = $type;
		return $this;
	}

	/**
	 * Feldtyp deregistrieren
	 *
	 * @param string $type_key Typ-Schlüssel.
	 * @return self Für Method Chaining.
	 */
	public function deregister( string $type_key ): self {
		unset( $this->types[ $type_key ] );
		return $this;
	}

	/**
	 * Feldtyp abrufen
	 *
	 * @param string $type_key Typ-Schlüssel.
	 * @return FieldTypeInterface|null Feldtyp oder null.
	 */
	public function get( string $type_key ): ?FieldTypeInterface {
		$this->registerDefaults();
		return $this->types[ $type_key ] ?? null;
	}

	/**
	 * Prüfen ob Feldtyp existiert
	 *
	 * @param string $type_key Typ-Schlüssel.
	 * @return bool True wenn Typ existiert.
	 */
	public function has( string $type_key ): bool {
		$this->registerDefaults();
		return isset( $this->types[ $type_key ] );
	}

	/**
	 * Alle Feldtypen abrufen
	 *
	 * @return array<string, FieldTypeInterface> Alle Typen.
	 */
	public function getAll(): array {
		$this->registerDefaults();
		return $this->types;
	}

	/**
	 * Alle Feldtypen als Array für REST API
	 *
	 * @return array[] Typen als Array.
	 */
	public function toArray(): array {
		$this->registerDefaults();

		$result = [];
		foreach ( $this->types as $key => $type ) {
			$result[ $key ] = [
				'type'             => $type->getType(),
				'label'            => $type->getLabel(),
				'icon'             => $type->getIcon(),
				'group'            => $type->getGroup(),
				'supportsOptions'  => $type->supportsOptions(),
				'isFileUpload'     => $type->isFileUpload(),
				'defaultSettings'  => $type->getDefaultSettings(),
				'validationRules'  => $type->getAvailableValidationRules(),
			];
		}

		return $result;
	}

	/**
	 * Feldtypen nach Gruppe gruppieren
	 *
	 * @return array<string, FieldTypeInterface[]> Typen nach Gruppe.
	 */
	public function getByGroup(): array {
		$this->registerDefaults();

		$groups = [
			'text'    => [],
			'choice'  => [],
			'special' => [],
			'layout'  => [],
		];

		foreach ( $this->types as $type ) {
			$group = $type->getGroup();
			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = [];
			}
			$groups[ $group ][] = $type;
		}

		return $groups;
	}

	/**
	 * Gruppen-Labels abrufen
	 *
	 * @return array<string, string> Gruppe => Label.
	 */
	public function getGroupLabels(): array {
		return [
			'text'    => __( 'Text', 'recruiting-playbook' ),
			'choice'  => __( 'Auswahl', 'recruiting-playbook' ),
			'special' => __( 'Spezial', 'recruiting-playbook' ),
			'layout'  => __( 'Layout', 'recruiting-playbook' ),
		];
	}

	/**
	 * Typ-Schlüssel aller registrierten Typen
	 *
	 * @return string[] Liste der Typ-Schlüssel.
	 */
	public function getTypeKeys(): array {
		$this->registerDefaults();
		return array_keys( $this->types );
	}

	/**
	 * Registry für Tests zurücksetzen
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->types               = [];
		$this->defaults_registered = false;
	}

	/**
	 * Singleton-Instanz für Tests zurücksetzen
	 *
	 * @return void
	 */
	public static function resetInstance(): void {
		self::$instance = null;
	}
}

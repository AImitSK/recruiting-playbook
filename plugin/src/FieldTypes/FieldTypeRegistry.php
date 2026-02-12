<?php
/**
 * Field Type Registry
 *
 * Manages all available field types.
 *
 * @package RecruitingPlaybook\FieldTypes
 */

declare(strict_types=1);

namespace RecruitingPlaybook\FieldTypes;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Contracts\FieldTypeInterface;

/**
 * Registry for field types
 */
class FieldTypeRegistry {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered field types
	 *
	 * @var array<string, FieldTypeInterface>
	 */
	private array $types = [];

	/**
	 * Whether default types are registered
	 *
	 * @var bool
	 */
	private bool $defaults_registered = false;

	/**
	 * Singleton access
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
	 * Constructor (private for singleton)
	 */
	private function __construct() {
		// Default types are lazy registered.
	}

	/**
	 * Register default field types
	 *
	 * @return void
	 */
	private function registerDefaults(): void {
		if ( $this->defaults_registered ) {
			return;
		}

		$default_types = [
			// Text group.
			new TextField(),
			new TextareaField(),
			new EmailField(),
			new PhoneField(),
			new UrlField(),

			// Choice group.
			new SelectField(),
			new RadioField(),
			new CheckboxField(),

			// Special group.
			new DateField(),

			// Layout group.
			new HtmlField(),
		];

		foreach ( $default_types as $type ) {
			$this->register( $type );
		}

		$this->defaults_registered = true;

		/**
		 * Action to register additional field types.
		 *
		 * @param FieldTypeRegistry $registry The registry instance.
		 */
		do_action( 'recruiting_playbook_register_field_types', $this );
	}

	/**
	 * Register field type
	 *
	 * @param FieldTypeInterface $type Field type instance.
	 * @return self For method chaining.
	 */
	public function register( FieldTypeInterface $type ): self {
		$this->types[ $type->getType() ] = $type;
		return $this;
	}

	/**
	 * Deregister field type
	 *
	 * @param string $type_key Type key.
	 * @return self For method chaining.
	 */
	public function deregister( string $type_key ): self {
		unset( $this->types[ $type_key ] );
		return $this;
	}

	/**
	 * Get field type
	 *
	 * @param string $type_key Type key.
	 * @return FieldTypeInterface|null Field type or null.
	 */
	public function get( string $type_key ): ?FieldTypeInterface {
		$this->registerDefaults();
		return $this->types[ $type_key ] ?? null;
	}

	/**
	 * Check if field type exists
	 *
	 * @param string $type_key Type key.
	 * @return bool True if type exists.
	 */
	public function has( string $type_key ): bool {
		$this->registerDefaults();
		return isset( $this->types[ $type_key ] );
	}

	/**
	 * Get all field types
	 *
	 * @return array<string, FieldTypeInterface> All types.
	 */
	public function getAll(): array {
		$this->registerDefaults();
		return $this->types;
	}

	/**
	 * Get all field types as array for REST API
	 *
	 * @return array[] Types as array.
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
	 * Group field types by group
	 *
	 * @return array<string, FieldTypeInterface[]> Types by group.
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
	 * Get group labels
	 *
	 * @return array<string, string> Group => Label.
	 */
	public function getGroupLabels(): array {
		return [
			'text'    => __( 'Text', 'recruiting-playbook' ),
			'choice'  => __( 'Choice', 'recruiting-playbook' ),
			'special' => __( 'Special', 'recruiting-playbook' ),
			'layout'  => __( 'Layout', 'recruiting-playbook' ),
		];
	}

	/**
	 * Get type keys of all registered types
	 *
	 * @return string[] List of type keys.
	 */
	public function getTypeKeys(): array {
		$this->registerDefaults();
		return array_keys( $this->types );
	}

	/**
	 * Reset registry for tests
	 *
	 * @return void
	 */
	public function reset(): void {
		$this->types               = [];
		$this->defaults_registered = false;
	}

	/**
	 * Reset singleton instance for tests
	 *
	 * @return void
	 */
	public static function resetInstance(): void {
		self::$instance = null;
	}
}

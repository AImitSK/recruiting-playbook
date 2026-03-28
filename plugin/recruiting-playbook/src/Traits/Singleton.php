<?php
/**
 * Singleton Trait
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton Trait for classes that should only have one instance
 */
trait Singleton {

	/**
	 * Singleton instance
	 *
	 * @var static|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return static
	 */
	public static function get_instance(): static {
		if ( null === self::$instance ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for singleton
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the instance (override in child classes)
	 */
	protected function init(): void {
		// Override in child classes if needed.
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}

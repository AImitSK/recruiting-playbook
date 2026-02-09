<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada;

defined( 'ABSPATH' ) || exit;

/**
 * Lädt und registriert alle Fusion Builder Elements
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class ElementLoader {

	/**
	 * Alle verfügbaren Elements
	 *
	 * @var array<string>
	 */
	private array $elements = [
		'JobGrid',
		'JobSearch',
		'JobCount',
		'FeaturedJobs',
		'LatestJobs',
		'JobCategories',
		'ApplicationForm',
		'AiJobFinder',
		'AiJobMatch',
	];

	/**
	 * Alle Elements registrieren
	 *
	 * @return void
	 */
	public function registerAll(): void {
		foreach ( $this->elements as $element ) {
			$this->registerElement( $element );
		}
	}

	/**
	 * Einzelnes Element registrieren
	 *
	 * @param string $element Element-Klassenname.
	 * @return void
	 */
	private function registerElement( string $element ): void {
		$class = __NAMESPACE__ . '\\Elements\\' . $element;

		if ( class_exists( $class ) ) {
			$instance = new $class();
			$instance->register();
		}
	}
}

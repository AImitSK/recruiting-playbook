<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Lädt und registriert alle Elementor Widgets
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class WidgetLoader {

	/**
	 * Alle verfügbaren Widgets
	 *
	 * @var array<string>
	 */
	private array $widgets = [
		'JobGrid',
		'JobSearch',
		'JobCount',
		'FeaturedJobs',
		'LatestJobs',
		'JobCategories',
		'AiJobFinder',
		'AiJobMatch',
	];

	/**
	 * Elementor Widgets Manager
	 */
	private \Elementor\Widgets_Manager $widgets_manager;

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor Widgets Manager.
	 */
	public function __construct( \Elementor\Widgets_Manager $widgets_manager ) {
		$this->widgets_manager = $widgets_manager;
	}

	/**
	 * Alle Widgets registrieren
	 */
	public function registerAll(): void {
		foreach ( $this->widgets as $widget ) {
			$this->registerWidget( $widget );
		}
	}

	/**
	 * Einzelnes Widget registrieren
	 *
	 * @param string $widget Widget-Klassenname.
	 */
	private function registerWidget( string $widget ): void {
		// AI-Widgets nur wenn Pro aktiv.
		if ( str_starts_with( $widget, 'Ai' ) ) {
			if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
				return;
			}
		}

		$class = __NAMESPACE__ . '\\Widgets\\' . $widget;

		if ( class_exists( $class ) ) {
			$this->widgets_manager->register( new $class() );
		}
	}
}

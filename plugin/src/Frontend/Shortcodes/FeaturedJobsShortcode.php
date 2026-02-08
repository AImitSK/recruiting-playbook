<?php
/**
 * Shortcode: [rp_featured_jobs]
 *
 * Zeigt hervorgehobene Stellen an (featured).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Featured Jobs Shortcode Handler
 */
class FeaturedJobsShortcode {

	/**
	 * Shortcode registrieren
	 */
	public function register(): void {
		add_shortcode( 'rp_featured_jobs', [ $this, 'render' ] );
	}

	/**
	 * Shortcode rendern
	 *
	 * Attribute:
	 * - limit: Anzahl der Stellen (default: 3)
	 * - columns: Spalten im Grid (1-4, default: 3)
	 * - title: Optionale Überschrift
	 * - show_excerpt: Auszug anzeigen (true/false, default: true)
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			[
				'limit'        => 3,
				'columns'      => 3,
				'title'        => '',
				'show_excerpt' => 'true',
			],
			$atts,
			'rp_featured_jobs'
		);

		$output = '';

		if ( ! empty( $atts['title'] ) ) {
			$output .= '<h2 class="rp-featured-jobs__title rp-text-2xl rp-font-bold rp-text-gray-900 rp-mb-6">' . esc_html( $atts['title'] ) . '</h2>';
		}

		// Jobs-Shortcode mit featured Filter aufrufen.
		$jobs_shortcode = new JobsShortcode();
		$output        .= $jobs_shortcode->render(
			[
				'limit'        => $atts['limit'],
				'columns'      => $atts['columns'],
				'featured'     => 'true',
				'show_excerpt' => $atts['show_excerpt'],
			]
		);

		return '<div class="rp-featured-jobs">' . $output . '</div>';
	}
}

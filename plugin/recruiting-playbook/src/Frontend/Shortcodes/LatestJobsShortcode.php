<?php
/**
 * Shortcode: [rp_latest_jobs]
 *
 * Zeigt die neuesten Stellen an.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Latest Jobs Shortcode Handler
 */
class LatestJobsShortcode {

	/**
	 * Shortcode registrieren
	 */
	public function register(): void {
		add_shortcode( 'rp_latest_jobs', [ $this, 'render' ] );
	}

	/**
	 * Shortcode rendern
	 *
	 * Attribute:
	 * - limit: Anzahl der Stellen (default: 5)
	 * - columns: Spalten im Grid (0 = Liste, 1-4, default: 0)
	 * - title: Optionale Überschrift
	 * - category: Filter nach Kategorie-Slug
	 * - show_date: Datum anzeigen (true/false, default: true)
	 * - show_excerpt: Auszug anzeigen (true/false, default: false für Liste)
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			[
				'limit'        => 5,
				'columns'      => 0,
				'title'        => '',
				'category'     => '',
				'show_date'    => 'true',
				'show_excerpt' => 'false',
			],
			$atts,
			'rp_latest_jobs'
		);

		$columns = absint( $atts['columns'] );

		$output = '';

		if ( ! empty( $atts['title'] ) ) {
			$output .= '<h2 class="rp-latest-jobs__title rp-text-2xl rp-font-bold rp-text-gray-900 rp-mb-6">' . esc_html( $atts['title'] ) . '</h2>';
		}

		// Jobs-Shortcode mit entsprechenden Parametern aufrufen.
		$jobs_atts = [
			'limit'        => $atts['limit'],
			'columns'      => max( 1, $columns ),
			'orderby'      => 'date',
			'order'        => 'DESC',
			'show_excerpt' => $atts['show_excerpt'],
		];

		if ( ! empty( $atts['category'] ) ) {
			$jobs_atts['category'] = $atts['category'];
		}

		$jobs_shortcode = new JobsShortcode();
		$output        .= $jobs_shortcode->render( $jobs_atts );

		return '<div class="rp-latest-jobs">' . $output . '</div>';
	}
}

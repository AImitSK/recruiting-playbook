<?php
/**
 * Shortcode: [rp_job_count]
 *
 * Zeigt die Anzahl der verfügbaren Stellen an.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Job Count Shortcode Handler
 */
class JobCountShortcode {

	/**
	 * Shortcode registrieren
	 */
	public function register(): void {
		add_shortcode( 'rp_job_count', [ $this, 'render' ] );
	}

	/**
	 * Shortcode rendern
	 *
	 * Attribute:
	 * - category: Filter nach Kategorie-Slug
	 * - location: Filter nach Standort-Slug
	 * - type: Filter nach Beschäftigungsart-Slug
	 * - format: Ausgabeformat mit {count} Platzhalter (default: "{count} offene Stellen")
	 * - singular: Text für 1 Stelle (default: "{count} offene Stelle")
	 * - zero: Text für 0 Stellen (default: "Keine offenen Stellen")
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			[
				'category' => '',
				'location' => '',
				'type'     => '',
				'format'   => '',
				'singular' => '',
				'zero'     => '',
			],
			$atts,
			'rp_job_count'
		);

		// Defaults anwenden wenn leer (Fusion Builder sendet leere Strings für ungesetzte Werte).
		if ( '' === $atts['format'] ) {
			$atts['format'] = __( '{count} offene Stellen', 'recruiting-playbook' );
		}
		if ( '' === $atts['singular'] ) {
			$atts['singular'] = __( '{count} offene Stelle', 'recruiting-playbook' );
		}
		if ( '' === $atts['zero'] ) {
			$atts['zero'] = __( 'Keine offenen Stellen', 'recruiting-playbook' );
		}

		// Query-Args aufbauen.
		$query_args = [
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		// Taxonomy-Filter.
		$tax_query = [];

		if ( ! empty( $atts['category'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'job_category',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			];
		}

		if ( ! empty( $atts['location'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'job_location',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['location'] ) ),
			];
		}

		if ( ! empty( $atts['type'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'employment_type',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['type'] ) ),
			];
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation']   = 'AND';
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$query = new \WP_Query( $query_args );
		$count = $query->found_posts;

		// Text bestimmen.
		if ( 0 === $count ) {
			$text = $atts['zero'];
		} elseif ( 1 === $count ) {
			$text = str_replace( '{count}', number_format_i18n( $count ), $atts['singular'] );
		} else {
			$text = str_replace( '{count}', number_format_i18n( $count ), $atts['format'] );
		}

		$class = 0 === $count ? 'rp-job-count rp-job-count--zero' : 'rp-job-count';
		$span  = '<span class="' . esc_attr( $class ) . '">' . esc_html( $text ) . '</span>';

		// Fusion Builder Live Editor rendert Shortcodes per AJAX.
		// Block-Wrapper nötig, damit der Live Editor das Element verwalten kann.
		if ( wp_doing_ajax() && defined( 'FUSION_BUILDER_VERSION' ) ) {
			return '<div class="rp-plugin">' . $span . '</div>';
		}

		return $span;
	}
}

<?php
/**
 * Shortcode: [rp_job_categories]
 *
 * Zeigt alle Job-Kategorien als klickbare Karten an.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Job Categories Shortcode Handler
 */
class JobCategoriesShortcode {

	/**
	 * Shortcode registrieren
	 */
	public function register(): void {
		add_shortcode( 'rp_job_categories', [ $this, 'render' ] );
	}

	/**
	 * Shortcode rendern
	 *
	 * Attribute:
	 * - columns: Spalten im Grid (1-6, default: 4)
	 * - show_count: Anzahl pro Kategorie anzeigen (true/false, default: true)
	 * - hide_empty: Leere Kategorien verstecken (true/false, default: true)
	 * - orderby: Sortierung (name, count, default: name)
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function render( $atts ): string {
		$this->enqueueAssets();

		$atts = shortcode_atts(
			[
				'columns'    => 4,
				'show_count' => 'true',
				'hide_empty' => 'true',
				'orderby'    => 'name',
			],
			$atts,
			'rp_job_categories'
		);

		$show_count = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );
		$hide_empty = filter_var( $atts['hide_empty'], FILTER_VALIDATE_BOOLEAN );
		$columns    = min( 6, max( 1, absint( $atts['columns'] ) ) );

		$terms = get_terms(
			[
				'taxonomy'   => 'job_category',
				'hide_empty' => $hide_empty,
				'orderby'    => 'count' === $atts['orderby'] ? 'count' : 'name',
				'order'      => 'count' === $atts['orderby'] ? 'DESC' : 'ASC',
			]
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="rp-plugin">
			<div class="rp-job-categories rp-grid rp-grid-cols-1 sm:rp-grid-cols-2 md:rp-grid-cols-<?php echo esc_attr( $columns ); ?> rp-gap-4">
				<?php foreach ( $terms as $term ) : ?>
					<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="rp-job-category-card rp-card rp-p-4 rp-flex rp-items-center rp-justify-between rp-transition-shadow hover:rp-shadow-md">
						<span class="rp-job-category-card__name rp-font-medium rp-text-gray-900"><?php echo esc_html( $term->name ); ?></span>
						<?php if ( $show_count ) : ?>
							<span class="rp-job-category-card__count rp-badge rp-badge-gray"><?php echo esc_html( number_format_i18n( $term->count ) ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Assets laden
	 */
	private function enqueueAssets(): void {
		wp_enqueue_style( 'rp-frontend' );
		wp_enqueue_script( 'rp-frontend' );
	}
}

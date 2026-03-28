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
	 * - layout: Darstellung (grid/list, default: grid)
	 * - columns: Spalten im Grid (1-6, default: 4) — nur bei layout=grid
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
				'layout'     => 'grid',
				'columns'    => 4,
				'show_count' => 'true',
				'hide_empty' => 'true',
				'orderby'    => 'name',
			],
			$atts,
			'rp_job_categories'
		);

		$layout     = in_array( $atts['layout'], [ 'grid', 'list' ], true ) ? $atts['layout'] : 'grid';
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

		if ( 'list' === $layout ) {
			$this->renderList( $terms, $show_count );
		} else {
			$this->renderGrid( $terms, $columns, $show_count );
		}

		return ob_get_clean();
	}

	/**
	 * Grid-Layout rendern
	 *
	 * @param \WP_Term[] $terms      Kategorien.
	 * @param int        $columns    Spaltenanzahl.
	 * @param bool       $show_count Anzahl anzeigen.
	 */
	private function renderGrid( array $terms, int $columns, bool $show_count ): void {
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
	}

	/**
	 * List-Layout rendern
	 *
	 * @param \WP_Term[] $terms      Kategorien.
	 * @param bool       $show_count Anzahl anzeigen.
	 */
	private function renderList( array $terms, bool $show_count ): void {
		?>
		<div class="rp-plugin">
			<div class="rp-job-categories-list">
				<?php foreach ( $terms as $term ) : ?>
					<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="rp-job-category-list-item">
						<span class="rp-job-category-list-item__name"><?php echo esc_html( $term->name ); ?></span>
						<?php if ( $show_count ) : ?>
							<span class="rp-job-category-list-item__count rp-badge rp-badge-gray"><?php echo esc_html( number_format_i18n( $term->count ) ); ?></span>
						<?php endif; ?>
						<span class="rp-job-category-list-item__icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8l4 4-4 4"/><path d="M8 12h8"/></svg>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Assets laden
	 */
	private function enqueueAssets(): void {
		wp_enqueue_style( 'rp-frontend' );
		wp_enqueue_script( 'rp-frontend' );
	}
}

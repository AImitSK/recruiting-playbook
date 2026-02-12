<?php
/**
 * Shortcode: [rp_jobs]
 *
 * Zeigt eine Liste von Stellenanzeigen an.
 * Design und Layout kommen aus den Design & Branding Einstellungen.
 * Spaltenanzahl kann per Attribut überschrieben werden.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Jobs Shortcode Handler
 */
class JobsShortcode {

	/**
	 * Shortcode registrieren
	 */
	public function register(): void {
		add_shortcode( 'rp_jobs', [ $this, 'render' ] );
	}

	/**
	 * Shortcode rendern
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function render( $atts ): string {
		$this->enqueueAssets();

		$atts = shortcode_atts(
			[
				'limit'    => 10,
				'category' => '',
				'location' => '',
				'type'     => '',
				'featured' => '',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'columns'  => '', // Leer = aus Design-Einstellungen.
			],
			$atts,
			'rp_jobs'
		);

		// Query-Argumente aufbauen.
		$args = [
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
		];

		// Featured-Filter.
		if ( filter_var( $atts['featured'], FILTER_VALIDATE_BOOLEAN ) ) {
			$args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'     => '_rp_featured',
					'value'   => '1',
					'compare' => '=',
				],
			];
		}

		// Taxonomie-Filter.
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
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return $this->renderEmpty();
		}

		// Design-Einstellungen laden (identisch mit archive-job_listing.php).
		$design_settings = get_option( 'rp_design_settings', [] );

		// Card-Preset.
		$card_preset = $design_settings['card_layout_preset'] ?? 'standard';
		$card_class  = 'rp-card rp-card--' . esc_attr( $card_preset );

		// Job-Liste Settings.
		$job_list_layout      = $design_settings['job_list_layout'] ?? 'grid';
		$job_list_columns     = (int) ( $design_settings['job_list_columns'] ?? 2 );
		$show_badges          = $design_settings['show_badges'] ?? true;
		$show_location        = $design_settings['show_location'] ?? true;
		$show_employment_type = $design_settings['show_employment_type'] ?? true;
		$show_salary          = $design_settings['show_salary'] ?? true;
		$show_deadline        = $design_settings['show_deadline'] ?? false;

		// Shortcode-Attribut überschreibt Design-Einstellung für Spalten.
		if ( '' !== $atts['columns'] ) {
			$job_list_columns = max( 1, min( 4, absint( $atts['columns'] ) ) );
		}

		// Grid-Klassen basierend auf Spaltenanzahl (identisch mit archive-job_listing.php).
		$grid_classes = 'rp-mt-10 rp-gap-6 sm:rp-mt-16';
		if ( 'list' === $job_list_layout ) {
			$grid_classes .= ' rp-flex rp-flex-col';
		} else {
			$grid_classes .= ' rp-grid rp-grid-cols-1';
			switch ( $job_list_columns ) {
				case 2:
					$grid_classes .= ' md:rp-grid-cols-2';
					break;
				case 3:
					$grid_classes .= ' md:rp-grid-cols-2 lg:rp-grid-cols-3';
					break;
				case 4:
					$grid_classes .= ' md:rp-grid-cols-2 lg:rp-grid-cols-4';
					break;
			}
		}

		// Cache-Priming.
		$job_ids = wp_list_pluck( $query->posts, 'ID' );
		update_meta_cache( 'post', $job_ids );
		update_object_term_cache( $job_ids, 'job_listing' );

		ob_start();
		?>
		<div class="rp-plugin">
			<div class="<?php echo esc_attr( $grid_classes ); ?>">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();

					// Variablen für das Partial setzen.
					include RP_PLUGIN_DIR . 'templates/partials/job-card.php';

				endwhile;
				?>
			</div>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Leere Ergebnisse rendern
	 *
	 * @return string HTML-Ausgabe.
	 */
	private function renderEmpty(): string {
		return '<div class="rp-plugin">
			<div class="rp-text-center rp-py-12 rp-bg-gray-50 rp-rounded-lg">
				<svg class="rp-mx-auto rp-h-12 rp-w-12 rp-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
				</svg>
				<h2 class="rp-mt-2 rp-text-lg rp-font-semibold rp-text-gray-900">' .
					esc_html__( 'No open positions at the moment', 'recruiting-playbook' ) .
				'</h2>
				<p class="rp-mt-1 rp-text-sm rp-text-gray-500">' .
					esc_html__( 'Please check back later or contact us for unsolicited applications.', 'recruiting-playbook' ) .
				'</p>
			</div>
		</div>';
	}

	/**
	 * Assets laden
	 */
	private function enqueueAssets(): void {
		wp_enqueue_style( 'rp-frontend' );
		wp_enqueue_script( 'rp-frontend' );
	}
}

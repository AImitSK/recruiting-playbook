<?php
/**
 * Shortcode: [rp_job_search]
 *
 * Stellensuche mit Filtern.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Job Search Shortcode Handler
 */
class JobSearchShortcode {

	/**
	 * Shortcode registrieren
	 */
	public function register(): void {
		add_shortcode( 'rp_job_search', [ $this, 'render' ] );
	}

	/**
	 * Shortcode rendern
	 *
	 * Attribute:
	 * - show_search: Suchfeld anzeigen (true/false, default: true)
	 * - show_category: Kategorie-Filter anzeigen (true/false, default: true)
	 * - show_location: Standort-Filter anzeigen (true/false, default: true)
	 * - show_type: Beschäftigungsart-Filter anzeigen (true/false, default: true)
	 * - limit: Stellen pro Seite (default: 10)
	 * - columns: Spalten im Grid (1-4, default: 1)
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function render( $atts ): string {
		$this->enqueueAssets();

		$atts = shortcode_atts(
			[
				'show_search'   => 'true',
				'show_category' => 'true',
				'show_location' => 'true',
				'show_type'     => 'true',
				'limit'         => 10,
				'columns'       => 1,
			],
			$atts,
			'rp_job_search'
		);

		$show_search   = filter_var( $atts['show_search'], FILTER_VALIDATE_BOOLEAN );
		$show_category = filter_var( $atts['show_category'], FILTER_VALIDATE_BOOLEAN );
		$show_location = filter_var( $atts['show_location'], FILTER_VALIDATE_BOOLEAN );
		$show_type     = filter_var( $atts['show_type'], FILTER_VALIDATE_BOOLEAN );
		$columns       = max( 1, min( 4, absint( $atts['columns'] ) ) );
		$per_page      = absint( $atts['limit'] );

		// Design-Einstellungen laden.
		$design_settings = get_option( 'rp_design_settings', [] );

		// Card-Preset.
		$card_preset = $design_settings['card_layout_preset'] ?? 'standard';
		$card_class  = 'rp-card rp-card--' . esc_attr( $card_preset );

		// Job-Liste Settings.
		$job_list_layout  = $design_settings['job_list_layout'] ?? 'grid';
		$show_badges      = $design_settings['show_badges'] ?? true;
		$show_location_badge    = $design_settings['show_location'] ?? true;
		$show_employment_type   = $design_settings['show_employment_type'] ?? true;
		$show_salary      = $design_settings['show_salary'] ?? true;
		$show_deadline    = $design_settings['show_deadline'] ?? false;

		// Filter-Werte aus GET-Parametern.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only search form
		$search   = isset( $_GET['rp_search'] ) ? sanitize_text_field( wp_unslash( $_GET['rp_search'] ) ) : '';
		$category = isset( $_GET['rp_category'] ) ? sanitize_text_field( wp_unslash( $_GET['rp_category'] ) ) : '';
		$location = isset( $_GET['rp_location'] ) ? sanitize_text_field( wp_unslash( $_GET['rp_location'] ) ) : '';
		$type     = isset( $_GET['rp_type'] ) ? sanitize_text_field( wp_unslash( $_GET['rp_type'] ) ) : '';
		$paged    = isset( $_GET['rp_page'] ) ? absint( $_GET['rp_page'] ) : 1;
		// phpcs:enable

		// Taxonomien laden.
		$categories = get_terms(
			[
				'taxonomy'   => 'job_category',
				'hide_empty' => true,
			]
		);

		$locations = get_terms(
			[
				'taxonomy'   => 'job_location',
				'hide_empty' => true,
			]
		);

		$types = get_terms(
			[
				'taxonomy'   => 'employment_type',
				'hide_empty' => true,
			]
		);

		// Query aufbauen.
		$args = [
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		];

		// Suche.
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Taxonomie-Filter.
		$tax_query = [];

		if ( ! empty( $category ) ) {
			$tax_query[] = [
				'taxonomy' => 'job_category',
				'field'    => 'slug',
				'terms'    => $category,
			];
		}

		if ( ! empty( $location ) ) {
			$tax_query[] = [
				'taxonomy' => 'job_location',
				'field'    => 'slug',
				'terms'    => $location,
			];
		}

		if ( ! empty( $type ) ) {
			$tax_query[] = [
				'taxonomy' => 'employment_type',
				'field'    => 'slug',
				'terms'    => $type,
			];
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$query = new \WP_Query( $args );

		// Grid-Klassen basierend auf Spaltenanzahl.
		$grid_classes = 'rp-mt-6 rp-gap-6';
		if ( 'list' === $job_list_layout ) {
			$grid_classes .= ' rp-flex rp-flex-col';
		} else {
			$grid_classes .= ' rp-grid rp-grid-cols-1';
			switch ( $columns ) {
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
		if ( $query->have_posts() ) {
			$job_ids = wp_list_pluck( $query->posts, 'ID' );
			update_meta_cache( 'post', $job_ids );
			update_object_term_cache( $job_ids, 'job_listing' );
		}

		ob_start();
		?>
		<div class="rp-plugin">
			<!-- Suchformular -->
			<form class="rp-bg-gray-50 rp-p-6 rp-rounded-lg rp-mb-6 rp-border rp-border-gray-200" method="get" action="">
				<div class="rp-grid rp-gap-4 sm:rp-grid-cols-2 lg:rp-grid-cols-4">
					<?php if ( $show_search ) : ?>
						<div>
							<label class="rp-label"><?php esc_html_e( 'Suche', 'recruiting-playbook' ); ?></label>
							<input
								type="text"
								name="rp_search"
								value="<?php echo esc_attr( $search ); ?>"
								placeholder="<?php esc_attr_e( 'Stichwort, Jobtitel...', 'recruiting-playbook' ); ?>"
								class="rp-input"
							>
						</div>
					<?php endif; ?>

					<?php if ( $show_category && ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
						<div>
							<label class="rp-label"><?php esc_html_e( 'Berufsfeld', 'recruiting-playbook' ); ?></label>
							<select name="rp_category" class="rp-input rp-select">
								<option value=""><?php esc_html_e( 'Alle Berufsfelder', 'recruiting-playbook' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $category, $cat->slug ); ?>>
										<?php echo esc_html( $cat->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<?php if ( $show_location && ! empty( $locations ) && ! is_wp_error( $locations ) ) : ?>
						<div>
							<label class="rp-label"><?php esc_html_e( 'Standort', 'recruiting-playbook' ); ?></label>
							<select name="rp_location" class="rp-input rp-select">
								<option value=""><?php esc_html_e( 'Alle Standorte', 'recruiting-playbook' ); ?></option>
								<?php foreach ( $locations as $loc ) : ?>
									<option value="<?php echo esc_attr( $loc->slug ); ?>" <?php selected( $location, $loc->slug ); ?>>
										<?php echo esc_html( $loc->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

					<?php if ( $show_type && ! empty( $types ) && ! is_wp_error( $types ) ) : ?>
						<div>
							<label class="rp-label"><?php esc_html_e( 'Beschäftigungsart', 'recruiting-playbook' ); ?></label>
							<select name="rp_type" class="rp-input rp-select">
								<option value=""><?php esc_html_e( 'Alle Arten', 'recruiting-playbook' ); ?></option>
								<?php foreach ( $types as $t ) : ?>
									<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $type, $t->slug ); ?>>
										<?php echo esc_html( $t->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>
				</div>

				<div class="rp-mt-4 rp-flex rp-gap-3 rp-flex-wrap">
					<button type="submit" class="wp-element-button">
						<?php esc_html_e( 'Suchen', 'recruiting-playbook' ); ?>
					</button>
					<?php if ( $search || $category || $location || $type ) : ?>
						<a href="<?php echo esc_url( remove_query_arg( [ 'rp_search', 'rp_category', 'rp_location', 'rp_type', 'rp_page' ] ) ); ?>" class="wp-element-button is-style-outline">
							<?php esc_html_e( 'Filter zurücksetzen', 'recruiting-playbook' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</form>

			<!-- Ergebniszähler -->
			<div class="rp-mb-4 rp-text-gray-500 rp-text-sm">
				<?php
				printf(
					/* translators: %d: Number of jobs found */
					esc_html( _n( '%d Stelle gefunden', '%d Stellen gefunden', $query->found_posts, 'recruiting-playbook' ) ),
					$query->found_posts
				);
				?>
			</div>

			<!-- Ergebnisse -->
			<?php if ( $query->have_posts() ) : ?>
				<div class="<?php echo esc_attr( $grid_classes ); ?>">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();

						// Job-Card Partial einbinden.
						include RP_PLUGIN_DIR . 'templates/partials/job-card.php';

					endwhile;
					?>
				</div>

				<?php
				// Pagination.
				$total_pages = $query->max_num_pages;
				if ( $total_pages > 1 ) :
					$current_url = remove_query_arg( 'rp_page' );
					?>
					<nav class="rp-mt-8 rp-flex rp-justify-center rp-gap-2 rp-flex-wrap">
						<?php if ( $paged > 1 ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'rp_page', $paged - 1, $current_url ) ); ?>" class="wp-element-button is-style-outline">
								&laquo; <?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
							</a>
						<?php endif; ?>

						<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
							<?php if ( $i === $paged ) : ?>
								<span class="wp-element-button"><?php echo esc_html( $i ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( add_query_arg( 'rp_page', $i, $current_url ) ); ?>" class="wp-element-button is-style-outline">
									<?php echo esc_html( $i ); ?>
								</a>
							<?php endif; ?>
						<?php endfor; ?>

						<?php if ( $paged < $total_pages ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'rp_page', $paged + 1, $current_url ) ); ?>" class="wp-element-button is-style-outline">
								<?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?> &raquo;
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<div class="rp-text-center rp-py-12 rp-bg-gray-50 rp-rounded-lg">
					<svg class="rp-mx-auto rp-h-12 rp-w-12 rp-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
					</svg>
					<p class="rp-mt-4 rp-text-gray-500">
						<?php esc_html_e( 'Keine passenden Stellen gefunden. Bitte versuchen Sie andere Suchkriterien.', 'recruiting-playbook' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		wp_reset_postdata();

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

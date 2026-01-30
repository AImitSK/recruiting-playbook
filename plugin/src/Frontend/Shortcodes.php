<?php
/**
 * Shortcodes für Recruiting Playbook
 *
 * Design-Pattern:
 * - Alle Shortcodes verwenden .rp-plugin Container
 * - Buttons: wp-element-button (Theme-Farbe)
 * - Inputs: .rp-input, .rp-select, .rp-label Klassen
 * - Cards: .rp-card Klasse
 * - Badges: .rp-badge rp-badge-gray
 * - Grid: rp-grid mit rp-grid-cols-* Klassen
 * - Spacing: rp-mt-*, rp-mb-*, rp-p-* etc.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Frontend;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Services\SpamProtection;

/**
 * Shortcode-Handler
 *
 * Verfügbare Shortcodes:
 * - [rp_jobs] - Stellenliste
 * - [rp_job_search] - Stellensuche mit Filtern
 * - [rp_application_form] - Bewerbungsformular
 */
class Shortcodes {

	/**
	 * Shortcodes registrieren
	 */
	public function register(): void {
		add_shortcode( 'rp_jobs', [ $this, 'renderJobList' ] );
		add_shortcode( 'rp_job_search', [ $this, 'renderJobSearch' ] );
		add_shortcode( 'rp_application_form', [ $this, 'renderApplicationForm' ] );
	}

	/**
	 * Stellenliste rendern
	 *
	 * Attribute:
	 * - limit: Anzahl Stellen (default: 10)
	 * - category: Filter nach Kategorie-Slug
	 * - location: Filter nach Standort-Slug
	 * - type: Filter nach Beschäftigungsart-Slug
	 * - orderby: Sortierung (date, title, rand)
	 * - order: ASC oder DESC
	 * - columns: Spalten im Grid (1-4, default: 1)
	 * - show_excerpt: Auszug anzeigen (true/false)
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function renderJobList( $atts ): string {
		$this->enqueueAssets();

		$atts = shortcode_atts(
			[
				'limit'        => 10,
				'category'     => '',
				'location'     => '',
				'type'         => '',
				'orderby'      => 'date',
				'order'        => 'DESC',
				'columns'      => 1,
				'show_excerpt' => 'true',
			],
			$atts,
			'rp_jobs'
		);

		// Query-Argumente aufbauen
		$args = [
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
		];

		// Taxonomie-Filter
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
			$args['tax_query'] = $tax_query;
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<div class="rp-plugin">
				<div class="rp-text-center rp-py-12 rp-bg-gray-50 rp-rounded-lg">
					<p class="rp-text-gray-500">' . esc_html__( 'Aktuell keine offenen Stellen verfügbar.', 'recruiting-playbook' ) . '</p>
				</div>
			</div>';
		}

		$columns      = max( 1, min( 4, absint( $atts['columns'] ) ) );
		$show_excerpt = filter_var( $atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN );

		// Grid-Klasse basierend auf Spaltenanzahl
		$grid_class = 'rp-grid rp-gap-6';
		if ( $columns >= 2 ) {
			$grid_class .= ' md:rp-grid-cols-2';
		}
		if ( $columns >= 3 ) {
			$grid_class .= ' lg:rp-grid-cols-3';
		}
		if ( $columns >= 4 ) {
			$grid_class .= ' xl:rp-grid-cols-4';
		}

		ob_start();
		?>
		<div class="rp-plugin">
			<div class="<?php echo esc_attr( $grid_class ); ?>">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$locations  = get_the_terms( get_the_ID(), 'job_location' );
					$types      = get_the_terms( get_the_ID(), 'employment_type' );
					$categories = get_the_terms( get_the_ID(), 'job_category' );
					$remote     = get_post_meta( get_the_ID(), '_rp_remote_option', true );
					?>
					<article class="rp-card rp-relative rp-transition-all hover:rp-shadow-lg">
						<a href="<?php the_permalink(); ?>" class="rp-absolute rp-inset-0 rp-rounded-xl" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
							<span class="rp-sr-only"><?php the_title(); ?></span>
						</a>

						<div class="rp-flex rp-items-center rp-gap-4 rp-text-xs">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="rp-text-gray-500">
								<?php echo esc_html( get_the_date( 'M d, Y' ) ); ?>
							</time>

							<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
								<span class="rp-badge rp-badge-gray rp-relative rp-z-10">
									<?php echo esc_html( $categories[0]->name ); ?>
								</span>
							<?php endif; ?>
						</div>

						<div>
							<h3 class="rp-mt-3 rp-text-lg rp-leading-6 rp-font-semibold rp-text-gray-900">
								<?php the_title(); ?>
							</h3>

							<?php if ( $show_excerpt && has_excerpt() ) : ?>
								<p class="rp-mt-5 rp-line-clamp-3 rp-text-sm rp-leading-6 rp-text-gray-600">
									<?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '...' ) ); ?>
								</p>
							<?php endif; ?>
						</div>

						<div class="rp-relative rp-mt-8 rp-flex rp-items-center rp-justify-between rp-text-xs">
							<div class="rp-flex rp-items-center rp-gap-2 rp-flex-wrap">
								<?php if ( $locations && ! is_wp_error( $locations ) ) : ?>
									<span class="rp-badge rp-badge-gray">
										<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
										</svg>
										<?php echo esc_html( $locations[0]->name ); ?>
									</span>
								<?php endif; ?>

								<?php if ( $types && ! is_wp_error( $types ) ) : ?>
									<span class="rp-badge rp-badge-gray">
										<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
										</svg>
										<?php echo esc_html( $types[0]->name ); ?>
									</span>
								<?php endif; ?>

								<?php if ( $remote && 'no' !== $remote ) : ?>
									<span class="rp-badge rp-badge-gray">
										<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
										</svg>
										<?php echo 'full' === $remote ? esc_html__( 'Remote', 'recruiting-playbook' ) : esc_html__( 'Hybrid', 'recruiting-playbook' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<a href="<?php the_permalink(); ?>" class="wp-element-button rp-relative rp-z-20">
								<?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
							</a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Stellensuche mit Filtern rendern
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
	public function renderJobSearch( $atts ): string {
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
			$args['tax_query'] = $tax_query;
		}

		$query = new \WP_Query( $args );

		// Grid-Klasse
		$grid_class = 'rp-grid rp-gap-6';
		if ( $columns >= 2 ) {
			$grid_class .= ' md:rp-grid-cols-2';
		}
		if ( $columns >= 3 ) {
			$grid_class .= ' lg:rp-grid-cols-3';
		}
		if ( $columns >= 4 ) {
			$grid_class .= ' xl:rp-grid-cols-4';
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
				<div class="<?php echo esc_attr( $grid_class ); ?>">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$job_locations = get_the_terms( get_the_ID(), 'job_location' );
						$job_types     = get_the_terms( get_the_ID(), 'employment_type' );
						$job_cats      = get_the_terms( get_the_ID(), 'job_category' );
						$remote        = get_post_meta( get_the_ID(), '_rp_remote_option', true );
						$deadline      = get_post_meta( get_the_ID(), '_rp_application_deadline', true );
						?>
						<article class="rp-card rp-relative rp-transition-all hover:rp-shadow-lg">
							<a href="<?php the_permalink(); ?>" class="rp-absolute rp-inset-0 rp-rounded-xl" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
								<span class="rp-sr-only"><?php the_title(); ?></span>
							</a>

							<div class="rp-flex rp-items-center rp-gap-4 rp-text-xs">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="rp-text-gray-500">
									<?php echo esc_html( get_the_date( 'M d, Y' ) ); ?>
								</time>

								<?php if ( $job_cats && ! is_wp_error( $job_cats ) ) : ?>
									<span class="rp-badge rp-badge-gray rp-relative rp-z-10">
										<?php echo esc_html( $job_cats[0]->name ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div>
								<h3 class="rp-mt-3 rp-text-lg rp-leading-6 rp-font-semibold rp-text-gray-900">
									<?php the_title(); ?>
								</h3>

								<?php if ( $deadline ) : ?>
									<p class="rp-mt-2 rp-text-xs rp-text-gray-400">
										<?php
										printf(
											/* translators: %s: Application deadline date */
											esc_html__( 'Bewerbungsfrist: %s', 'recruiting-playbook' ),
											esc_html( date_i18n( get_option( 'date_format' ), strtotime( $deadline ) ) )
										);
										?>
									</p>
								<?php endif; ?>

								<?php if ( has_excerpt() ) : ?>
									<p class="rp-mt-4 rp-line-clamp-3 rp-text-sm rp-leading-6 rp-text-gray-600">
										<?php echo esc_html( wp_trim_words( get_the_excerpt(), 25, '...' ) ); ?>
									</p>
								<?php endif; ?>
							</div>

							<div class="rp-relative rp-mt-6 rp-flex rp-items-center rp-justify-between rp-text-xs">
								<div class="rp-flex rp-items-center rp-gap-2 rp-flex-wrap">
									<?php if ( $job_locations && ! is_wp_error( $job_locations ) ) : ?>
										<span class="rp-badge rp-badge-gray">
											<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
											</svg>
											<?php echo esc_html( $job_locations[0]->name ); ?>
										</span>
									<?php endif; ?>

									<?php if ( $job_types && ! is_wp_error( $job_types ) ) : ?>
										<span class="rp-badge rp-badge-gray">
											<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
											</svg>
											<?php echo esc_html( $job_types[0]->name ); ?>
										</span>
									<?php endif; ?>

									<?php if ( $remote && 'no' !== $remote ) : ?>
										<span class="rp-badge rp-badge-gray">
											<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
											</svg>
											<?php echo 'full' === $remote ? esc_html__( 'Remote', 'recruiting-playbook' ) : esc_html__( 'Hybrid', 'recruiting-playbook' ); ?>
										</span>
									<?php endif; ?>
								</div>

								<a href="<?php the_permalink(); ?>" class="wp-element-button rp-relative rp-z-20">
									<?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
								</a>
							</div>
						</article>
					<?php endwhile; ?>
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
	 * Bewerbungsformular rendern
	 *
	 * Attribute:
	 * - job_id: ID der Stelle (required)
	 * - title: Überschrift (default: "Jetzt bewerben")
	 * - show_job_title: Job-Titel anzeigen (true/false)
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function renderApplicationForm( $atts ): string {
		$atts = shortcode_atts(
			[
				'job_id'         => 0,
				'title'          => __( 'Jetzt bewerben', 'recruiting-playbook' ),
				'show_job_title' => 'true',
			],
			$atts,
			'rp_application_form'
		);

		$job_id = absint( $atts['job_id'] );

		// Wenn keine job_id, versuche aktuelle Stelle zu verwenden
		if ( ! $job_id && is_singular( 'job_listing' ) ) {
			$job_id = get_the_ID();
		}

		// Validieren
		if ( ! $job_id ) {
			return '<div class="rp-plugin">
				<div class="rp-bg-error-light rp-border rp-border-error rp-rounded-md rp-p-4 rp-text-error">
					' . esc_html__( 'Fehler: Keine Stelle angegeben. Bitte job_id Attribut setzen.', 'recruiting-playbook' ) . '
				</div>
			</div>';
		}

		$job = get_post( $job_id );
		if ( ! $job || 'job_listing' !== $job->post_type || 'publish' !== $job->post_status ) {
			return '<div class="rp-plugin">
				<div class="rp-bg-error-light rp-border rp-border-error rp-rounded-md rp-p-4 rp-text-error">
					' . esc_html__( 'Fehler: Die angegebene Stelle existiert nicht oder ist nicht verfügbar.', 'recruiting-playbook' ) . '
				</div>
			</div>';
		}

		// Assets laden
		$this->enqueueAssets();
		$this->enqueueFormAssets();

		$show_job_title = filter_var( $atts['show_job_title'], FILTER_VALIDATE_BOOLEAN );

		ob_start();
		?>
		<div class="rp-plugin">
			<div class="rp-rounded-lg rp-p-6 md:rp-p-8 rp-border rp-border-gray-200" data-job-id="<?php echo esc_attr( $job_id ); ?>" data-rp-application-form>
				<?php if ( ! empty( $atts['title'] ) ) : ?>
					<h2 class="rp-text-2xl rp-font-bold rp-text-gray-900 rp-mb-2"><?php echo esc_html( $atts['title'] ); ?></h2>
				<?php endif; ?>

				<?php if ( $show_job_title ) : ?>
					<p class="rp-text-gray-600 rp-mb-6">
						<?php
						printf(
							/* translators: %s: Job title */
							esc_html__( 'Bewerbung für: %s', 'recruiting-playbook' ),
							'<strong>' . esc_html( $job->post_title ) . '</strong>'
						);
						?>
					</p>
				<?php endif; ?>

				<?php echo $this->getFormHtml( $job_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Formular-HTML generieren (wiederverwendbar)
	 *
	 * @param int $job_id Job ID.
	 * @return string HTML.
	 */
	private function getFormHtml( int $job_id ): string {
		ob_start();
		?>
		<div x-data="applicationForm" x-cloak>
			<!-- Erfolgs-Meldung -->
			<template x-if="submitted">
				<div class="rp-text-center rp-py-12">
					<svg class="rp-w-16 rp-h-16 rp-text-success rp-mx-auto rp-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
					</svg>
					<h3 class="rp-text-xl rp-font-semibold rp-text-gray-900 rp-mb-2"><?php esc_html_e( 'Bewerbung erfolgreich gesendet!', 'recruiting-playbook' ); ?></h3>
					<p class="rp-text-gray-600"><?php esc_html_e( 'Vielen Dank für Ihre Bewerbung. Sie erhalten in Kürze eine Bestätigung per E-Mail.', 'recruiting-playbook' ); ?></p>
				</div>
			</template>

			<!-- Formular -->
			<template x-if="!submitted">
				<div>
					<!-- Fehler-Meldung -->
					<div x-show="error" x-cloak class="rp-bg-error-light rp-border rp-border-error rp-rounded-md rp-p-4 rp-mb-6">
						<p class="rp-text-error rp-text-sm" x-text="error"></p>
					</div>

					<!-- Fortschrittsanzeige -->
					<div class="rp-mb-8">
						<div class="rp-flex rp-justify-between rp-text-sm rp-text-gray-600 rp-mb-2">
							<span><?php esc_html_e( 'Schritt', 'recruiting-playbook' ); ?> <span x-text="step"></span> <?php esc_html_e( 'von', 'recruiting-playbook' ); ?> <span x-text="totalSteps"></span></span>
							<span x-text="progress + '%'"></span>
						</div>
						<div class="rp-h-2 rp-bg-gray-200 rp-rounded-full rp-overflow-hidden">
							<div class="rp-h-full rp-bg-primary rp-transition-all rp-duration-300" :style="'width: ' + progress + '%'"></div>
						</div>
					</div>

					<form @submit.prevent="submit">
						<?php echo SpamProtection::getHoneypotField(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo SpamProtection::getTimestampField(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

						<!-- Schritt 1: Persönliche Daten -->
						<div x-show="step === 1" x-transition>
							<h3 class="rp-text-lg rp-font-semibold rp-text-gray-900 rp-mb-6"><?php esc_html_e( 'Persönliche Daten', 'recruiting-playbook' ); ?></h3>

							<div class="rp-space-y-4">
								<!-- Anrede, Vorname & Nachname -->
								<div class="rp-grid rp-grid-cols-1 sm:rp-grid-cols-5 rp-gap-4">
									<!-- Anrede (1/5) -->
									<div class="sm:rp-col-span-1">
										<label class="rp-label"><?php esc_html_e( 'Anrede', 'recruiting-playbook' ); ?></label>
										<select x-model="formData.salutation" class="rp-input rp-select">
											<option value=""><?php esc_html_e( 'Bitte wählen', 'recruiting-playbook' ); ?></option>
											<option value="Herr"><?php esc_html_e( 'Herr', 'recruiting-playbook' ); ?></option>
											<option value="Frau"><?php esc_html_e( 'Frau', 'recruiting-playbook' ); ?></option>
											<option value="Divers"><?php esc_html_e( 'Divers', 'recruiting-playbook' ); ?></option>
										</select>
									</div>
									<!-- Vorname (2/5) -->
									<div class="sm:rp-col-span-2">
										<label class="rp-label">
											<?php esc_html_e( 'Vorname', 'recruiting-playbook' ); ?> <span class="rp-text-error">*</span>
										</label>
										<input type="text" x-model="formData.first_name"
											class="rp-input"
											:class="errors.first_name ? 'rp-input-error' : ''"
											required>
										<p x-show="errors.first_name" x-text="errors.first_name" class="rp-error-text"></p>
									</div>
									<!-- Nachname (2/5) -->
									<div class="sm:rp-col-span-2">
										<label class="rp-label">
											<?php esc_html_e( 'Nachname', 'recruiting-playbook' ); ?> <span class="rp-text-error">*</span>
										</label>
										<input type="text" x-model="formData.last_name"
											class="rp-input"
											:class="errors.last_name ? 'rp-input-error' : ''"
											required>
										<p x-show="errors.last_name" x-text="errors.last_name" class="rp-error-text"></p>
									</div>
								</div>

								<!-- E-Mail & Telefon -->
								<div class="rp-grid rp-grid-cols-1 sm:rp-grid-cols-2 rp-gap-4">
									<!-- E-Mail -->
									<div>
										<label class="rp-label">
											<?php esc_html_e( 'E-Mail-Adresse', 'recruiting-playbook' ); ?> <span class="rp-text-error">*</span>
										</label>
										<input type="email" x-model="formData.email"
											class="rp-input"
											:class="errors.email ? 'rp-input-error' : ''"
											required>
										<p x-show="errors.email" x-text="errors.email" class="rp-error-text"></p>
									</div>
									<!-- Telefon -->
									<div>
										<label class="rp-label"><?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?></label>
										<input type="tel" x-model="formData.phone" class="rp-input">
									</div>
								</div>
							</div>
						</div>

						<!-- Schritt 2: Dokumente -->
						<div x-show="step === 2" x-transition>
							<h3 class="rp-text-lg rp-font-semibold rp-text-gray-900 rp-mb-6"><?php esc_html_e( 'Bewerbungsunterlagen', 'recruiting-playbook' ); ?></h3>

							<!-- Lebenslauf -->
							<div class="rp-mb-6">
								<label class="rp-label rp-mb-2"><?php esc_html_e( 'Lebenslauf', 'recruiting-playbook' ); ?></label>
								<div
									@dragover.prevent="$el.classList.add('rp-border-primary', 'rp-bg-primary-light')"
									@dragleave.prevent="$el.classList.remove('rp-border-primary', 'rp-bg-primary-light')"
									@drop.prevent="$el.classList.remove('rp-border-primary', 'rp-bg-primary-light'); handleDrop($event, 'resume')"
									class="rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-lg rp-p-6 rp-text-center rp-cursor-pointer rp-transition-colors"
									:class="files.resume ? 'rp-border-success rp-bg-success-light' : ''"
								>
									<template x-if="!files.resume">
										<div>
											<svg class="rp-w-10 rp-h-10 rp-text-gray-400 rp-mx-auto rp-mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
											</svg>
											<p class="rp-text-gray-600 rp-mb-2"><?php esc_html_e( 'Datei hierher ziehen oder', 'recruiting-playbook' ); ?></p>
											<label class="rp-text-primary hover:rp-text-primary-hover rp-font-medium rp-cursor-pointer">
												<?php esc_html_e( 'Datei auswählen', 'recruiting-playbook' ); ?>
												<input type="file" @change="handleFileSelect($event, 'resume')" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="rp-hidden">
											</label>
											<p class="rp-text-xs rp-text-gray-400 rp-mt-2"><?php esc_html_e( 'PDF, DOC, DOCX, JPG, PNG (max. 10 MB)', 'recruiting-playbook' ); ?></p>
										</div>
									</template>
									<template x-if="files.resume">
										<div class="rp-flex rp-items-center rp-justify-between">
											<div class="rp-flex rp-items-center rp-gap-3">
												<svg class="rp-w-6 rp-h-6 rp-text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
												</svg>
												<div class="rp-text-left">
													<p class="rp-font-medium rp-text-gray-900" x-text="files.resume.name"></p>
													<p class="rp-text-xs rp-text-gray-500" x-text="formatFileSize(files.resume.size)"></p>
												</div>
											</div>
											<button type="button" @click="removeFile('resume')" class="rp-p-1 rp-text-error hover:rp-text-error">
												<svg class="rp-w-5 rp-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
												</svg>
											</button>
										</div>
									</template>
								</div>
								<p x-show="errors.resume" x-text="errors.resume" class="rp-error-text"></p>
							</div>

							<!-- Weitere Dokumente -->
							<div class="rp-mb-6">
								<label class="rp-label rp-mb-2"><?php esc_html_e( 'Weitere Dokumente (Zeugnisse, Zertifikate)', 'recruiting-playbook' ); ?></label>
								<div
									@dragover.prevent
									@drop.prevent="handleDrop($event, 'documents')"
									class="rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-lg rp-p-6 rp-text-center"
								>
									<label class="rp-text-primary hover:rp-text-primary-hover rp-font-medium rp-cursor-pointer">
										<?php esc_html_e( 'Dateien auswählen', 'recruiting-playbook' ); ?>
										<input type="file" @change="handleFileSelect($event, 'documents')" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple class="rp-hidden">
									</label>
									<p class="rp-text-xs rp-text-gray-400 rp-mt-2"><?php esc_html_e( 'Mehrere Dateien möglich (max. 5 Dateien, je 10 MB)', 'recruiting-playbook' ); ?></p>
								</div>

								<!-- Hochgeladene Dateien -->
								<template x-if="files.documents.length > 0">
									<ul class="rp-mt-3 rp-space-y-2">
										<template x-for="(file, index) in files.documents" :key="index">
											<li class="rp-flex rp-items-center rp-justify-between rp-px-3 rp-py-2 rp-border rp-border-gray-200 rp-rounded">
												<span class="rp-text-sm rp-text-gray-700" x-text="file.name"></span>
												<button type="button" @click="removeFile('documents', index)" class="rp-p-1 rp-text-error hover:rp-text-error">
													<svg class="rp-w-4 rp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
														<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
													</svg>
												</button>
											</li>
										</template>
									</ul>
								</template>
							</div>

							<!-- Anschreiben -->
							<div>
								<label class="rp-label"><?php esc_html_e( 'Anschreiben / Nachricht', 'recruiting-playbook' ); ?></label>
								<textarea x-model="formData.cover_letter" rows="4"
									class="rp-input rp-textarea"
									placeholder="<?php esc_attr_e( 'Optional: Fügen Sie ein kurzes Anschreiben hinzu...', 'recruiting-playbook' ); ?>"></textarea>
							</div>
						</div>

						<!-- Schritt 3: Datenschutz & Absenden -->
						<div x-show="step === 3" x-transition>
							<h3 class="rp-text-lg rp-font-semibold rp-text-gray-900 rp-mb-6"><?php esc_html_e( 'Datenschutz & Absenden', 'recruiting-playbook' ); ?></h3>

							<!-- Zusammenfassung -->
							<div class="rp-border rp-border-gray-200 rp-rounded-lg rp-p-5 rp-mb-6">
								<h4 class="rp-font-semibold rp-text-gray-900 rp-mb-3"><?php esc_html_e( 'Ihre Angaben', 'recruiting-playbook' ); ?></h4>
								<dl class="rp-text-sm rp-space-y-2">
									<div class="rp-flex">
										<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'Name:', 'recruiting-playbook' ); ?></dt>
										<dd class="rp-text-gray-900" x-text="formData.salutation + ' ' + formData.first_name + ' ' + formData.last_name"></dd>
									</div>
									<div class="rp-flex">
										<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'E-Mail:', 'recruiting-playbook' ); ?></dt>
										<dd class="rp-text-gray-900" x-text="formData.email"></dd>
									</div>
									<template x-if="formData.phone">
										<div class="rp-flex">
											<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'Telefon:', 'recruiting-playbook' ); ?></dt>
											<dd class="rp-text-gray-900" x-text="formData.phone"></dd>
										</div>
									</template>
									<template x-if="files.resume">
										<div class="rp-flex">
											<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'Lebenslauf:', 'recruiting-playbook' ); ?></dt>
											<dd class="rp-text-gray-900" x-text="files.resume.name"></dd>
										</div>
									</template>
									<template x-if="files.documents.length > 0">
										<div class="rp-flex">
											<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'Dokumente:', 'recruiting-playbook' ); ?></dt>
											<dd class="rp-text-gray-900" x-text="files.documents.length + ' Datei(en)'"></dd>
										</div>
									</template>
								</dl>
							</div>

							<!-- Datenschutz-Checkbox -->
							<div class="rp-mb-6">
								<label class="rp-flex rp-items-start rp-gap-3 rp-cursor-pointer">
									<input type="checkbox" x-model="formData.privacy_consent" class="rp-checkbox rp-mt-1">
									<span class="rp-text-sm rp-leading-relaxed">
										<span class="rp-text-error">*</span>
										<?php
										$privacy_url = get_privacy_policy_url();
										if ( $privacy_url ) {
											$privacy_link = sprintf(
												'<a href="%s" target="_blank" class="rp-underline">%s</a>',
												esc_url( $privacy_url ),
												esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' )
											);
										} else {
											$privacy_link = esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' );
										}
										echo wp_kses(
											sprintf(
												/* translators: %s: privacy policy link */
												__( 'Ich habe die %s gelesen und stimme der Verarbeitung meiner Daten zum Zweck der Bewerbung zu.', 'recruiting-playbook' ),
												$privacy_link
											),
											[
												'a' => [
													'href'   => [],
													'target' => [],
													'class'  => [],
												],
											]
										);
										?>
									</span>
								</label>
								<p x-show="errors.privacy_consent" x-text="errors.privacy_consent" class="rp-error-text rp-mt-2"></p>
							</div>
						</div>

						<!-- Navigation -->
						<div class="rp-flex rp-justify-between rp-items-center rp-mt-8 rp-pt-6 rp-border-t rp-border-gray-200">
							<button
								type="button"
								x-show="step > 1"
								@click="prevStep"
								class="wp-element-button is-style-outline"
							>
								<?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
							</button>
							<div x-show="step === 1"></div>

							<button
								x-show="step < totalSteps"
								type="button"
								@click="nextStep"
								class="wp-element-button"
							>
								<?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?>
							</button>

							<button
								x-show="step === totalSteps"
								type="submit"
								:disabled="loading"
								class="wp-element-button disabled:rp-opacity-50 disabled:rp-cursor-not-allowed"
							>
								<span x-show="!loading"><?php esc_html_e( 'Bewerbung absenden', 'recruiting-playbook' ); ?></span>
								<span x-show="loading"><?php esc_html_e( 'Wird gesendet...', 'recruiting-playbook' ); ?></span>
							</button>
						</div>
					</form>
				</div>
			</template>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Basis-Assets laden (CSS)
	 */
	private function enqueueAssets(): void {
		// Frontend CSS laden (falls noch nicht geladen).
		if ( ! wp_style_is( 'rp-frontend', 'enqueued' ) ) {
			$css_file = RP_PLUGIN_DIR . 'assets/dist/css/frontend.css';
			if ( file_exists( $css_file ) ) {
				wp_enqueue_style(
					'rp-frontend',
					RP_PLUGIN_URL . 'assets/dist/css/frontend.css',
					[],
					RP_VERSION . '-' . filemtime( $css_file )
				);
			}
		}

		// x-cloak CSS für Alpine.js.
		wp_add_inline_style( 'rp-frontend', '[x-cloak] { display: none !important; }' );
	}

	/**
	 * Custom Fields Form Assets laden
	 *
	 * Lädt CSS und JS für dynamische Formulare mit Custom Fields.
	 */
	public function enqueueCustomFieldsAssets(): void {
		// Basis-Assets.
		$this->enqueueAssets();

		// Custom Fields CSS.
		$css_file = RP_PLUGIN_DIR . 'assets/dist/css/custom-fields.css';
		if ( file_exists( $css_file ) && ! wp_style_is( 'rp-custom-fields', 'enqueued' ) ) {
			wp_enqueue_style(
				'rp-custom-fields',
				RP_PLUGIN_URL . 'assets/dist/css/custom-fields.css',
				[ 'rp-frontend' ],
				RP_VERSION . '-' . filemtime( $css_file )
			);
		}

		// Tracking JS.
		$tracking_file = RP_PLUGIN_DIR . 'assets/src/js/tracking.js';
		if ( file_exists( $tracking_file ) && ! wp_script_is( 'rp-tracking', 'enqueued' ) ) {
			wp_enqueue_script(
				'rp-tracking',
				RP_PLUGIN_URL . 'assets/src/js/tracking.js',
				[],
				RP_VERSION,
				true
			);
		}

		// Custom Fields Form JS.
		$form_file = RP_PLUGIN_DIR . 'assets/dist/js/custom-fields-form.js';
		if ( file_exists( $form_file ) && ! wp_script_is( 'rp-custom-fields-form', 'enqueued' ) ) {
			wp_enqueue_script(
				'rp-custom-fields-form',
				RP_PLUGIN_URL . 'assets/dist/js/custom-fields-form.js',
				[ 'rp-tracking' ],
				RP_VERSION . '-' . filemtime( $form_file ),
				true
			);

			// Lokalisierung.
			wp_localize_script(
				'rp-custom-fields-form',
				'rpForm',
				[
					'apiUrl' => rest_url( 'recruiting/v1/' ),
					'nonce'  => wp_create_nonce( 'wp_rest' ),
					'i18n'   => [
						'required'        => __( 'Dieses Feld ist erforderlich', 'recruiting-playbook' ),
						'invalidEmail'    => __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein', 'recruiting-playbook' ),
						'invalidUrl'      => __( 'Bitte geben Sie eine gültige URL ein', 'recruiting-playbook' ),
						'invalidPhone'    => __( 'Bitte geben Sie eine gültige Telefonnummer ein', 'recruiting-playbook' ),
						'invalidNumber'   => __( 'Bitte geben Sie eine gültige Zahl ein', 'recruiting-playbook' ),
						'invalidDate'     => __( 'Bitte geben Sie ein gültiges Datum ein', 'recruiting-playbook' ),
						'minLength'       => __( 'Mindestens {min} Zeichen erforderlich', 'recruiting-playbook' ),
						'maxLength'       => __( 'Maximal {max} Zeichen erlaubt', 'recruiting-playbook' ),
						'numberMin'       => __( 'Mindestwert: {min}', 'recruiting-playbook' ),
						'numberMax'       => __( 'Maximalwert: {max}', 'recruiting-playbook' ),
						'dateMin'         => __( 'Datum muss nach {date} liegen', 'recruiting-playbook' ),
						'dateMax'         => __( 'Datum muss vor {date} liegen', 'recruiting-playbook' ),
						'fileTooLarge'    => __( 'Die Datei ist zu groß (max. {size} MB)', 'recruiting-playbook' ),
						'invalidFileType' => __( 'Dateityp nicht erlaubt', 'recruiting-playbook' ),
						'fileRequired'    => __( 'Bitte laden Sie eine Datei hoch', 'recruiting-playbook' ),
						'maxFilesReached' => __( 'Maximal {max} Dateien erlaubt', 'recruiting-playbook' ),
						'minSelections'   => __( 'Bitte wählen Sie mindestens {min} Optionen', 'recruiting-playbook' ),
						'maxSelections'   => __( 'Bitte wählen Sie maximal {max} Optionen', 'recruiting-playbook' ),
						'patternMismatch' => __( 'Das Format ist ungültig', 'recruiting-playbook' ),
						'privacyRequired' => __( 'Bitte stimmen Sie der Datenschutzerklärung zu', 'recruiting-playbook' ),
					],
				]
			);
		}

		// Alpine.js.
		$alpine_deps = [ 'rp-tracking' ];
		if ( wp_script_is( 'rp-custom-fields-form', 'enqueued' ) ) {
			$alpine_deps[] = 'rp-custom-fields-form';
		}

		$alpine_file = RP_PLUGIN_DIR . 'assets/dist/js/alpine.min.js';
		if ( ! wp_script_is( 'rp-alpine', 'enqueued' ) && file_exists( $alpine_file ) ) {
			wp_enqueue_script(
				'rp-alpine',
				RP_PLUGIN_URL . 'assets/dist/js/alpine.min.js',
				$alpine_deps,
				'3.14.3',
				true
			);

			add_filter(
				'script_loader_tag',
				function ( $tag, $handle ) {
					if ( 'rp-alpine' === $handle && false === strpos( $tag, 'defer' ) ) {
						return str_replace( ' src', ' defer src', $tag );
					}
					return $tag;
				},
				10,
				2
			);
		}
	}

	/**
	 * Form-Assets laden (Alpine.js + Application Form JS)
	 */
	private function enqueueFormAssets(): void {
		// Tracking JS.
		$tracking_file = RP_PLUGIN_DIR . 'assets/src/js/tracking.js';
		if ( file_exists( $tracking_file ) && ! wp_script_is( 'rp-tracking', 'enqueued' ) ) {
			wp_enqueue_script(
				'rp-tracking',
				RP_PLUGIN_URL . 'assets/src/js/tracking.js',
				[],
				RP_VERSION,
				true
			);
		}

		// Alpine.js Abhängigkeiten.
		$alpine_deps = [ 'rp-tracking' ];

		// Application Form JS - muss VOR Alpine.js geladen werden.
		$form_file = RP_PLUGIN_DIR . 'assets/src/js/application-form.js';
		if ( file_exists( $form_file ) && ! wp_script_is( 'rp-application-form', 'enqueued' ) ) {
			wp_enqueue_script(
				'rp-application-form',
				RP_PLUGIN_URL . 'assets/src/js/application-form.js',
				[], // Keine Abhängigkeit zu Alpine - muss vorher laden!
				RP_VERSION,
				true
			);

			// Lokalisierung für das Formular.
			wp_localize_script(
				'rp-application-form',
				'rpForm',
				[
					'apiUrl' => rest_url( 'recruiting/v1/' ),
					'nonce'  => wp_create_nonce( 'wp_rest' ),
					'i18n'   => [
						'required'        => __( 'Dieses Feld ist erforderlich', 'recruiting-playbook' ),
						'invalidEmail'    => __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein', 'recruiting-playbook' ),
						'fileTooLarge'    => __( 'Die Datei ist zu groß (max. 10 MB)', 'recruiting-playbook' ),
						'invalidFileType' => __( 'Dateityp nicht erlaubt. Erlaubt: PDF, DOC, DOCX, JPG, PNG', 'recruiting-playbook' ),
						'privacyRequired' => __( 'Bitte stimmen Sie der Datenschutzerklärung zu', 'recruiting-playbook' ),
					],
				]
			);

			$alpine_deps[] = 'rp-application-form';
		}

		// Alpine.js (lokal gebundelt) - muss NACH application-form.js geladen werden.
		$alpine_file = RP_PLUGIN_DIR . 'assets/dist/js/alpine.min.js';
		if ( ! wp_script_is( 'rp-alpine', 'enqueued' ) && file_exists( $alpine_file ) ) {
			wp_enqueue_script(
				'rp-alpine',
				RP_PLUGIN_URL . 'assets/dist/js/alpine.min.js',
				$alpine_deps,
				'3.14.3',
				true
			);

			// Defer-Attribut hinzufügen.
			add_filter(
				'script_loader_tag',
				function ( $tag, $handle ) {
					if ( 'rp-alpine' === $handle && false === strpos( $tag, 'defer' ) ) {
						return str_replace( ' src', ' defer src', $tag );
					}
					return $tag;
				},
				10,
				2
			);
		}
	}
}

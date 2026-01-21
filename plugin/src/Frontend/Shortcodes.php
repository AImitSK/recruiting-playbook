<?php
/**
 * Shortcodes für Recruiting Playbook
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend;

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
			return sprintf(
				'<div class="rp-no-jobs" style="text-align: center; padding: 40px 20px; background: #f9fafb; border-radius: 8px;">
					<p style="color: #6b7280;">%s</p>
				</div>',
				esc_html__( 'Aktuell keine offenen Stellen verfügbar.', 'recruiting-playbook' )
			);
		}

		$columns      = max( 1, min( 4, absint( $atts['columns'] ) ) );
		$show_excerpt = filter_var( $atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN );

		ob_start();
		?>
		<div class="rp-jobs-shortcode" style="display: grid; gap: 20px; grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$locations = get_the_terms( get_the_ID(), 'job_location' );
				$types     = get_the_terms( get_the_ID(), 'employment_type' );
				$remote    = get_post_meta( get_the_ID(), '_rp_remote_option', true );
				?>
				<article class="rp-job-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
					<h3 class="rp-job-title" style="font-size: 1.125rem; font-weight: 600; margin: 0 0 12px 0;">
						<a href="<?php the_permalink(); ?>" style="color: inherit; text-decoration: none;">
							<?php the_title(); ?>
						</a>
					</h3>

					<div class="rp-job-meta" style="display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.875rem; color: #6b7280; margin-bottom: 12px;">
						<?php if ( $locations && ! is_wp_error( $locations ) ) : ?>
							<span><?php echo esc_html( $locations[0]->name ); ?></span>
						<?php endif; ?>

						<?php if ( $types && ! is_wp_error( $types ) ) : ?>
							<span><?php echo esc_html( $types[0]->name ); ?></span>
						<?php endif; ?>

						<?php if ( $remote && 'no' !== $remote ) : ?>
							<span style="color: #059669;">
								<?php echo 'full' === $remote ? esc_html__( 'Remote', 'recruiting-playbook' ) : esc_html__( 'Hybrid', 'recruiting-playbook' ); ?>
							</span>
						<?php endif; ?>
					</div>

					<?php if ( $show_excerpt && has_excerpt() ) : ?>
						<div class="rp-job-excerpt" style="color: #4b5563; margin-bottom: 12px; line-height: 1.5;">
							<?php the_excerpt(); ?>
						</div>
					<?php endif; ?>

					<a href="<?php the_permalink(); ?>" class="rp-btn" style="display: inline-block; padding: 8px 16px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem;">
						<?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
					</a>
				</article>
			<?php endwhile; ?>
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
		$search   = isset( $_GET['rp_search'] ) ? sanitize_text_field( wp_unslash( $_GET['rp_search'] ) ) : '';
		$category = isset( $_GET['rp_category'] ) ? sanitize_text_field( wp_unslash( $_GET['rp_category'] ) ) : '';
		$location = isset( $_GET['rp_location'] ) ? sanitize_text_field( wp_unslash( $_GET['rp_location'] ) ) : '';
		$type     = isset( $_GET['rp_type'] ) ? sanitize_text_field( wp_unslash( $_GET['rp_type'] ) ) : '';
		$paged    = isset( $_GET['rp_page'] ) ? absint( $_GET['rp_page'] ) : 1;

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

		ob_start();
		?>
		<div class="rp-job-search-wrapper">
			<!-- Suchformular -->
			<form class="rp-job-search-form" method="get" action="" style="background: #f9fafb; padding: 24px; border-radius: 8px; margin-bottom: 24px;">
				<div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
					<?php if ( $show_search ) : ?>
						<div class="rp-search-field">
							<label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.875rem;">
								<?php esc_html_e( 'Suche', 'recruiting-playbook' ); ?>
							</label>
							<input
								type="text"
								name="rp_search"
								value="<?php echo esc_attr( $search ); ?>"
								placeholder="<?php esc_attr_e( 'Stichwort, Jobtitel...', 'recruiting-playbook' ); ?>"
								style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;"
							>
						</div>
					<?php endif; ?>

					<?php if ( $show_category && ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
						<div class="rp-category-field">
							<label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.875rem;">
								<?php esc_html_e( 'Berufsfeld', 'recruiting-playbook' ); ?>
							</label>
							<select name="rp_category" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
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
						<div class="rp-location-field">
							<label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.875rem;">
								<?php esc_html_e( 'Standort', 'recruiting-playbook' ); ?>
							</label>
							<select name="rp_location" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
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
						<div class="rp-type-field">
							<label style="display: block; font-weight: 500; margin-bottom: 4px; font-size: 0.875rem;">
								<?php esc_html_e( 'Beschäftigungsart', 'recruiting-playbook' ); ?>
							</label>
							<select name="rp_type" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
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

				<div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
					<button type="submit" style="padding: 10px 24px; background: #2271b1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
						<?php esc_html_e( 'Suchen', 'recruiting-playbook' ); ?>
					</button>
					<?php if ( $search || $category || $location || $type ) : ?>
						<a href="<?php echo esc_url( remove_query_arg( [ 'rp_search', 'rp_category', 'rp_location', 'rp_type', 'rp_page' ] ) ); ?>" style="padding: 10px 24px; background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; font-weight: 500;">
							<?php esc_html_e( 'Filter zurücksetzen', 'recruiting-playbook' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</form>

			<!-- Ergebniszähler -->
			<div class="rp-job-search-meta" style="margin-bottom: 16px; color: #6b7280; font-size: 0.875rem;">
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
				<div class="rp-job-search-results" style="display: grid; gap: 20px; grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$job_locations = get_the_terms( get_the_ID(), 'job_location' );
						$job_types     = get_the_terms( get_the_ID(), 'employment_type' );
						$remote        = get_post_meta( get_the_ID(), '_rp_remote_option', true );
						$deadline      = get_post_meta( get_the_ID(), '_rp_application_deadline', true );
						?>
						<article class="rp-job-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; transition: box-shadow 0.2s;">
							<h3 class="rp-job-title" style="font-size: 1.125rem; font-weight: 600; margin: 0 0 12px 0;">
								<a href="<?php the_permalink(); ?>" style="color: inherit; text-decoration: none;">
									<?php the_title(); ?>
								</a>
							</h3>

							<div class="rp-job-meta" style="display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.875rem; color: #6b7280; margin-bottom: 12px;">
								<?php if ( $job_locations && ! is_wp_error( $job_locations ) ) : ?>
									<span style="display: inline-flex; align-items: center; gap: 4px;">
										<svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
										</svg>
										<?php echo esc_html( $job_locations[0]->name ); ?>
									</span>
								<?php endif; ?>

								<?php if ( $job_types && ! is_wp_error( $job_types ) ) : ?>
									<span style="display: inline-flex; align-items: center; gap: 4px;">
										<svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
										</svg>
										<?php echo esc_html( $job_types[0]->name ); ?>
									</span>
								<?php endif; ?>

								<?php if ( $remote && 'no' !== $remote ) : ?>
									<span style="color: #059669; display: inline-flex; align-items: center; gap: 4px;">
										<svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
										</svg>
										<?php echo 'full' === $remote ? esc_html__( 'Remote', 'recruiting-playbook' ) : esc_html__( 'Hybrid', 'recruiting-playbook' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<?php if ( $deadline ) : ?>
								<div class="rp-job-deadline" style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 12px;">
									<?php
									printf(
										/* translators: %s: Application deadline date */
										esc_html__( 'Bewerbungsfrist: %s', 'recruiting-playbook' ),
										esc_html( date_i18n( get_option( 'date_format' ), strtotime( $deadline ) ) )
									);
									?>
								</div>
							<?php endif; ?>

							<?php if ( has_excerpt() ) : ?>
								<div class="rp-job-excerpt" style="color: #4b5563; margin-bottom: 12px; line-height: 1.5; font-size: 0.875rem;">
									<?php the_excerpt(); ?>
								</div>
							<?php endif; ?>

							<a href="<?php the_permalink(); ?>" class="rp-btn" style="display: inline-block; padding: 8px 16px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem;">
								<?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
							</a>
						</article>
					<?php endwhile; ?>
				</div>

				<?php
				// Pagination.
				$total_pages = $query->max_num_pages;
				if ( $total_pages > 1 ) :
					$current_url = remove_query_arg( 'rp_page' );
					?>
					<nav class="rp-job-pagination" style="margin-top: 24px; display: flex; justify-content: center; gap: 8px;">
						<?php if ( $paged > 1 ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'rp_page', $paged - 1, $current_url ) ); ?>" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; text-decoration: none; color: #374151;">
								&laquo; <?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
							</a>
						<?php endif; ?>

						<?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
							<?php if ( $i === $paged ) : ?>
								<span style="padding: 8px 12px; background: #2271b1; color: #fff; border-radius: 4px;"><?php echo esc_html( $i ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( add_query_arg( 'rp_page', $i, $current_url ) ); ?>" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; text-decoration: none; color: #374151;">
									<?php echo esc_html( $i ); ?>
								</a>
							<?php endif; ?>
						<?php endfor; ?>

						<?php if ( $paged < $total_pages ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'rp_page', $paged + 1, $current_url ) ); ?>" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; text-decoration: none; color: #374151;">
								<?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?> &raquo;
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<div class="rp-no-jobs" style="text-align: center; padding: 40px 20px; background: #f9fafb; border-radius: 8px;">
					<p style="color: #6b7280; margin: 0;">
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
			return sprintf(
				'<div class="rp-form-error" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px 16px; color: #dc2626;">
					%s
				</div>',
				esc_html__( 'Fehler: Keine Stelle angegeben. Bitte job_id Attribut setzen.', 'recruiting-playbook' )
			);
		}

		$job = get_post( $job_id );
		if ( ! $job || 'job_listing' !== $job->post_type || 'publish' !== $job->post_status ) {
			return sprintf(
				'<div class="rp-form-error" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px 16px; color: #dc2626;">
					%s
				</div>',
				esc_html__( 'Fehler: Die angegebene Stelle existiert nicht oder ist nicht verfügbar.', 'recruiting-playbook' )
			);
		}

		// Assets laden
		$this->enqueueFormAssets();

		$show_job_title = filter_var( $atts['show_job_title'], FILTER_VALIDATE_BOOLEAN );

		ob_start();
		?>
		<div class="rp-application-form-shortcode" data-job-id="<?php echo esc_attr( $job_id ); ?>" style="padding: 30px; background: #f9fafb; border-radius: 8px;">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h2 style="font-size: 1.5rem; margin: 0 0 8px 0;"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $show_job_title ) : ?>
				<p style="color: #6b7280; margin: 0 0 24px 0;">
					<?php
					printf(
						/* translators: %s: Job title */
						esc_html__( 'Bewerbung für: %s', 'recruiting-playbook' ),
						'<strong>' . esc_html( $job->post_title ) . '</strong>'
					);
					?>
				</p>
			<?php endif; ?>

			<?php echo $this->getFormHtml( $job_id ); ?>
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
				<div class="rp-form-success" style="text-align: center; padding: 40px 20px;">
					<svg style="width: 64px; height: 64px; color: #00a32a; margin: 0 auto 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
					</svg>
					<h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 8px;"><?php esc_html_e( 'Bewerbung erfolgreich gesendet!', 'recruiting-playbook' ); ?></h3>
					<p style="color: #6b7280;"><?php esc_html_e( 'Vielen Dank für Ihre Bewerbung. Sie erhalten in Kürze eine Bestätigung per E-Mail.', 'recruiting-playbook' ); ?></p>
				</div>
			</template>

			<!-- Formular -->
			<template x-if="!submitted">
				<div>
					<!-- Fehler-Meldung -->
					<div x-show="error" x-cloak class="rp-form-error" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; color: #dc2626;">
						<span x-text="error"></span>
					</div>

					<!-- Fortschrittsanzeige -->
					<div class="rp-form-progress" style="margin-bottom: 30px;">
						<div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.875rem;">
							<span><?php esc_html_e( 'Schritt', 'recruiting-playbook' ); ?> <span x-text="step"></span> <?php esc_html_e( 'von', 'recruiting-playbook' ); ?> <span x-text="totalSteps"></span></span>
							<span x-text="progress + '%'"></span>
						</div>
						<div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
							<div style="height: 100%; background: #2271b1; transition: width 0.3s;" :style="'width: ' + progress + '%'"></div>
						</div>
					</div>

					<form @submit.prevent="submit">
						<?php echo SpamProtection::getHoneypotField(); ?>
						<?php echo SpamProtection::getTimestampField(); ?>

						<!-- Schritt 1: Persönliche Daten -->
						<div x-show="step === 1" x-transition>
							<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Persönliche Daten', 'recruiting-playbook' ); ?></h3>

							<div style="display: grid; gap: 16px;">
								<div class="rp-form-field">
									<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Anrede', 'recruiting-playbook' ); ?></label>
									<select x-model="formData.salutation" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
										<option value=""><?php esc_html_e( 'Bitte wählen', 'recruiting-playbook' ); ?></option>
										<option value="Herr"><?php esc_html_e( 'Herr', 'recruiting-playbook' ); ?></option>
										<option value="Frau"><?php esc_html_e( 'Frau', 'recruiting-playbook' ); ?></option>
										<option value="Divers"><?php esc_html_e( 'Divers', 'recruiting-playbook' ); ?></option>
									</select>
								</div>

								<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
									<div class="rp-form-field">
										<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Vorname', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
										<input type="text" x-model="formData.first_name" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;" required>
										<span x-show="errors.first_name" x-text="errors.first_name" style="color: #dc2626; font-size: 0.875rem;"></span>
									</div>
									<div class="rp-form-field">
										<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Nachname', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
										<input type="text" x-model="formData.last_name" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;" required>
										<span x-show="errors.last_name" x-text="errors.last_name" style="color: #dc2626; font-size: 0.875rem;"></span>
									</div>
								</div>

								<div class="rp-form-field">
									<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'E-Mail-Adresse', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
									<input type="email" x-model="formData.email" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;" required>
									<span x-show="errors.email" x-text="errors.email" style="color: #dc2626; font-size: 0.875rem;"></span>
								</div>

								<div class="rp-form-field">
									<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?></label>
									<input type="tel" x-model="formData.phone" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
								</div>
							</div>
						</div>

						<!-- Schritt 2: Dokumente -->
						<div x-show="step === 2" x-transition>
							<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Bewerbungsunterlagen', 'recruiting-playbook' ); ?></h3>

							<div class="rp-form-field" style="margin-bottom: 20px;">
								<label style="display: block; font-weight: 500; margin-bottom: 8px;"><?php esc_html_e( 'Lebenslauf', 'recruiting-playbook' ); ?></label>
								<div
									@dragover.prevent
									@drop.prevent="handleDrop($event, 'resume')"
									style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 24px; text-align: center;"
									:style="files.resume ? 'border-color: #00a32a; background: #f0fdf4;' : ''"
								>
									<template x-if="!files.resume">
										<div>
											<p style="margin: 0 0 8px 0; color: #6b7280;"><?php esc_html_e( 'Datei hierher ziehen oder', 'recruiting-playbook' ); ?></p>
											<label style="color: #2271b1; cursor: pointer; font-weight: 500;">
												<?php esc_html_e( 'Datei auswählen', 'recruiting-playbook' ); ?>
												<input type="file" @change="handleFileSelect($event, 'resume')" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;">
											</label>
											<p style="margin: 8px 0 0 0; font-size: 0.75rem; color: #9ca3af;"><?php esc_html_e( 'PDF, DOC, DOCX, JPG, PNG (max. 10 MB)', 'recruiting-playbook' ); ?></p>
										</div>
									</template>
									<template x-if="files.resume">
										<div style="display: flex; align-items: center; justify-content: space-between;">
											<span x-text="files.resume.name"></span>
											<button type="button" @click="removeFile('resume')" style="color: #dc2626; background: none; border: none; cursor: pointer;">&times;</button>
										</div>
									</template>
								</div>
							</div>

							<div class="rp-form-field">
								<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Anschreiben / Nachricht', 'recruiting-playbook' ); ?></label>
								<textarea x-model="formData.cover_letter" rows="5" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; resize: vertical;"></textarea>
							</div>
						</div>

						<!-- Schritt 3: Datenschutz -->
						<div x-show="step === 3" x-transition>
							<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Datenschutz & Absenden', 'recruiting-playbook' ); ?></h3>

							<div class="rp-form-field" style="margin-bottom: 20px;">
								<label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
									<input type="checkbox" x-model="formData.privacy_consent" style="width: 20px; height: 20px; margin-top: 2px;">
									<span style="font-size: 0.875rem; line-height: 1.5;">
										<?php
										$privacy_url = get_privacy_policy_url();
										printf(
											/* translators: %s: privacy policy link */
											esc_html__( 'Ich habe die %s gelesen und stimme der Verarbeitung meiner Daten zu. *', 'recruiting-playbook' ),
											$privacy_url ? '<a href="' . esc_url( $privacy_url ) . '" target="_blank" style="color: #2271b1;">' . esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' ) . '</a>' : esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' )
										);
										?>
									</span>
								</label>
								<span x-show="errors.privacy_consent" x-text="errors.privacy_consent" style="color: #dc2626; font-size: 0.875rem; display: block; margin-top: 4px;"></span>
							</div>
						</div>

						<!-- Navigation -->
						<div style="display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
							<button type="button" x-show="step > 1" @click="prevStep" style="padding: 12px 24px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">
								<?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
							</button>
							<div x-show="step === 1"></div>

							<button x-show="step < totalSteps" type="button" @click="nextStep" style="padding: 12px 24px; background: #2271b1; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
								<?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?>
							</button>

							<button x-show="step === totalSteps" type="submit" :disabled="loading" style="padding: 12px 24px; background: #00a32a; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
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
	 * Form-Assets laden
	 */
	private function enqueueFormAssets(): void {
		// Alpine.js
		if ( ! wp_script_is( 'rp-alpine', 'enqueued' ) ) {
			wp_enqueue_script(
				'rp-alpine',
				'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
				[],
				'3.14.3',
				[ 'strategy' => 'defer' ]
			);
		}

		// Application Form JS
		if ( ! wp_script_is( 'rp-application-form', 'enqueued' ) ) {
			wp_enqueue_script(
				'rp-application-form',
				RP_PLUGIN_URL . 'assets/src/js/application-form.js',
				[ 'rp-alpine' ],
				RP_VERSION,
				true
			);

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
		}

		// x-cloak Style
		wp_add_inline_style( 'wp-block-library', '[x-cloak] { display: none !important; }' );
	}
}

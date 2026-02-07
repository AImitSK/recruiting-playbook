<?php
/**
 * Template: Stellen-Archiv
 *
 * Dieses Template kann im Theme überschrieben werden:
 * theme/recruiting-playbook/archive-job_listing.php
 *
 * Kompatibel mit Classic und Block Themes (FSE).
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

/*
 * Block Theme Detection & Header
 * Block Themes (FSE) benötigen block_template_part() statt get_header()
 */
if ( wp_is_block_theme() ) {
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div class="wp-site-blocks">
		<?php block_template_part( 'header' ); ?>
		<main class="wp-block-group">
	<?php
} else {
	get_header();
}
?>

<div class="rp-plugin rp-py-8 sm:rp-py-12">
	<div class="rp-mx-auto rp-px-4 sm:rp-px-6 lg:rp-px-8" style="max-width: var(--wp--style--global--wide-size, 1280px);">
		<div>

			<h2 class="rp-text-4xl rp-font-semibold rp-tracking-tight rp-text-gray-900 sm:rp-text-5xl">
				<?php
				$settings = get_option( 'rp_settings', [] );
				$company  = $settings['company_name'] ?? get_bloginfo( 'name' );
				printf(
					/* translators: %s: Company name */
					esc_html__( 'Karriere bei %s', 'recruiting-playbook' ),
					esc_html( $company )
				);
				?>
			</h2>

			<p class="rp-mt-2 rp-text-lg rp-leading-8 rp-text-gray-600">
				<?php esc_html_e( 'Entdecken Sie unsere aktuellen Stellenangebote und werden Sie Teil unseres Teams.', 'recruiting-playbook' ); ?>
			</p>

			<?php if ( have_posts() ) : ?>

				<?php
				/*
				 * Cache-Priming: Lade alle Meta-Daten und Taxonomien in einem Query
				 * statt N+1 Queries im Loop. Verbessert Performance bei vielen Jobs.
				 */
				$job_ids = wp_list_pluck( $wp_query->posts, 'ID' );
				update_meta_cache( 'post', $job_ids );
				update_object_term_cache( $job_ids, 'job_listing' );

				// Design-Einstellungen laden.
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

				// Grid-Klassen basierend auf Spaltenanzahl.
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
				?>

				<div class="<?php echo esc_attr( $grid_classes ); ?>">

					<?php
					while ( have_posts() ) :
						the_post();
						?>

						<article class="<?php echo esc_attr( $card_class ); ?> rp-relative rp-transition-all hover:rp-shadow-lg">

							<a href="<?php the_permalink(); ?>" class="rp-absolute rp-inset-0 rp-rounded-xl" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
								<span class="rp-sr-only"><?php the_title(); ?></span>
							</a>

							<div class="rp-flex rp-items-center rp-gap-4 rp-text-xs">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="rp-text-gray-500">
									<?php echo esc_html( get_the_date( 'M d, Y' ) ); ?>
								</time>

								<?php
								// Kategorie Badge
								$categories = get_the_terms( get_the_ID(), 'job_category' );
								if ( $categories && ! is_wp_error( $categories ) ) :
									?>
									<span class="rp-badge rp-badge-gray rp-relative rp-z-10 hover:rp-bg-gray-200">
										<?php echo esc_html( $categories[0]->name ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div>
								<h3 class="rp-mt-3 rp-text-lg rp-leading-6 rp-font-semibold rp-text-gray-900">
									<?php the_title(); ?>
								</h3>

								<?php if ( has_excerpt() ) : ?>
									<p class="rp-mt-5 rp-line-clamp-3 rp-text-sm rp-leading-6 rp-text-gray-600">
										<?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '...' ) ); ?>
									</p>
								<?php endif; ?>
							</div>

							<!-- Tags -->
							<?php if ( $show_badges ) : ?>
							<div class="rp-relative rp-mt-4 rp-flex rp-flex-wrap rp-items-center rp-gap-2 rp-text-xs">
								<?php
								// Standort
								if ( $show_location ) :
									$locations = get_the_terms( get_the_ID(), 'job_location' );
									if ( $locations && ! is_wp_error( $locations ) ) :
										?>
										<span class="rp-badge rp-badge-gray rp-badge-location">
											<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
											</svg>
											<?php echo esc_html( $locations[0]->name ); ?>
										</span>
									<?php endif; ?>
								<?php endif; ?>

								<?php
								// Beschäftigungsart
								if ( $show_employment_type ) :
									$types = get_the_terms( get_the_ID(), 'employment_type' );
									if ( $types && ! is_wp_error( $types ) ) :
										?>
										<span class="rp-badge rp-badge-gray rp-badge-employment">
											<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
											</svg>
											<?php echo esc_html( $types[0]->name ); ?>
										</span>
									<?php endif; ?>
								<?php endif; ?>

								<?php
								// Remote
								$remote = get_post_meta( get_the_ID(), '_rp_remote_option', true );
								if ( $remote && 'no' !== $remote ) :
									$remote_labels = [
										'hybrid' => __( 'Hybrid', 'recruiting-playbook' ),
										'full'   => __( 'Remote', 'recruiting-playbook' ),
									];
									?>
									<span class="rp-badge rp-badge-gray rp-badge-remote">
										<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
										</svg>
										<?php echo esc_html( $remote_labels[ $remote ] ?? $remote ); ?>
									</span>
								<?php endif; ?>

								<?php
								// Gehalt
								if ( $show_salary ) :
									$salary = get_post_meta( get_the_ID(), '_rp_salary_range', true );
									if ( $salary ) :
										?>
										<span class="rp-badge rp-badge-salary">
											<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
											</svg>
											<?php echo esc_html( $salary ); ?>
										</span>
									<?php endif; ?>
								<?php endif; ?>

								<?php
								// Bewerbungsfrist
								if ( $show_deadline ) :
									$deadline = get_post_meta( get_the_ID(), '_rp_application_deadline', true );
									if ( $deadline ) :
										?>
										<span class="rp-badge rp-badge-gray rp-badge-deadline">
											<svg class="rp-h-3 rp-w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
												<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
											</svg>
											<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $deadline ) ) ); ?>
										</span>
									<?php endif; ?>
								<?php endif; ?>
							</div>
							<?php endif; ?>

							<!-- Buttons -->
							<div class="rp-card-buttons rp-relative rp-mt-4 rp-flex rp-flex-wrap rp-gap-2" x-data>
								<a href="<?php the_permalink(); ?>" class="wp-element-button rp-relative rp-z-20">
									<?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
								</a>

								<?php if ( function_exists( 'rp_has_cv_matching' ) && rp_has_cv_matching() ) : ?>
									<div class="rp-relative rp-z-20" @click.stop>
										<?php
										echo do_shortcode(
											sprintf(
												'[rp_ai_job_match job_id="%d" title="%s" style="outline"]',
												get_the_ID(),
												esc_attr__( 'Passe ich zu diesem Job?', 'recruiting-playbook' )
											)
										);
										?>
									</div>
								<?php endif; ?>
							</div>

						</article>

					<?php endwhile; ?>

				</div>

				<?php
				// Pagination
				the_posts_pagination(
					[
						'mid_size'  => 2,
						'prev_text' => __( '&laquo; Zurück', 'recruiting-playbook' ),
						'next_text' => __( 'Weiter &raquo;', 'recruiting-playbook' ),
					]
				);
				?>

			<?php else : ?>

				<div class="rp-mt-10 rp-text-center rp-py-12">
					<svg class="rp-mx-auto rp-h-12 rp-w-12 rp-text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
					</svg>
					<h2 class="rp-mt-2 rp-text-lg rp-font-semibold rp-text-gray-900">
						<?php esc_html_e( 'Aktuell keine offenen Stellen', 'recruiting-playbook' ); ?>
					</h2>
					<p class="rp-mt-1 rp-text-sm rp-text-gray-500">
						<?php esc_html_e( 'Schauen Sie später wieder vorbei oder kontaktieren Sie uns für Initiativbewerbungen.', 'recruiting-playbook' ); ?>
					</p>
				</div>

			<?php endif; ?>

		</div>
	</div>
</div>

<?php
/*
 * Block Theme Detection & Footer
 */
if ( wp_is_block_theme() ) {
	?>
		</main>
		<?php block_template_part( 'footer' ); ?>
	</div>
	<?php wp_footer(); ?>
	</body>
	</html>
	<?php
} else {
	get_footer();
}

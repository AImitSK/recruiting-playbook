<?php
/**
 * Template Partial: Job Card
 *
 * Wird verwendet von:
 * - archive-job_listing.php
 * - Shortcodes: rp_jobs, rp_job_search, rp_featured_jobs, rp_latest_jobs
 *
 * Erwartete Variablen:
 * - $card_class (string): CSS-Klassen für die Card (z.B. 'rp-card rp-card--standard')
 * - $show_badges (bool): Badges anzeigen
 * - $show_location (bool): Standort-Badge anzeigen
 * - $show_employment_type (bool): Beschäftigungsart-Badge anzeigen
 * - $show_salary (bool): Gehalt-Badge anzeigen
 * - $show_deadline (bool): Bewerbungsfrist-Badge anzeigen
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;
?>

<article class="<?php echo esc_attr( $card_class ); ?> rp-relative rp-transition-all hover:rp-shadow-lg">

	<a href="<?php the_permalink(); ?>" class="rp-absolute rp-inset-0 rp-rounded-xl" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
		<span class="rp-sr-only"><?php the_title(); ?></span>
	</a>

	<div class="rp-flex rp-items-center rp-gap-4 rp-text-xs rp-flex-wrap">
		<?php
		// "Neu" Badge - Jobs die weniger als X Tage alt sind.
		$new_days    = apply_filters( 'rp_new_job_days', 14 );
		$post_date   = get_the_date( 'U' );
		$days_ago    = floor( ( time() - $post_date ) / DAY_IN_SECONDS );
		$is_new      = $days_ago <= $new_days;

		if ( $is_new ) :
			?>
			<span class="rp-badge rp-badge-new rp-relative rp-z-10">
				<?php esc_html_e( 'Neu', 'recruiting-playbook' ); ?>
			</span>
		<?php endif; ?>

		<?php
		// "Featured" Badge.
		$is_featured = get_post_meta( get_the_ID(), '_rp_featured', true );
		if ( $is_featured ) :
			?>
			<span class="rp-badge rp-badge-featured rp-relative rp-z-10">
				<svg class="rp-h-3 rp-w-3" fill="currentColor" viewBox="0 0 20 20">
					<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
				</svg>
				<?php esc_html_e( 'Top-Job', 'recruiting-playbook' ); ?>
			</span>
		<?php endif; ?>

		<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" class="rp-text-gray-500">
			<?php echo esc_html( get_the_date( 'M d, Y' ) ); ?>
		</time>

		<?php
		// Kategorie Badge.
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

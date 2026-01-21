<?php
/**
 * Template: Stellen-Archiv
 *
 * Dieses Template kann im Theme überschrieben werden:
 * theme/recruiting-playbook/archive-job_listing.php
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="rp-jobs-archive">
	<div class="rp-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">

		<header class="rp-archive-header" style="margin-bottom: 30px;">
			<h1 class="rp-archive-title" style="font-size: 2rem; margin-bottom: 10px;">
				<?php
				$settings = get_option( 'rp_settings', [] );
				$company  = $settings['company_name'] ?? get_bloginfo( 'name' );
				printf(
					/* translators: %s: Company name */
					esc_html__( 'Karriere bei %s', 'recruiting-playbook' ),
					esc_html( $company )
				);
				?>
			</h1>
			<p class="rp-archive-description" style="color: #666;">
				<?php esc_html_e( 'Entdecken Sie unsere aktuellen Stellenangebote und werden Sie Teil unseres Teams.', 'recruiting-playbook' ); ?>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>

			<div class="rp-jobs-grid" style="display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));">

				<?php
				while ( have_posts() ) :
					the_post();
					?>

					<article <?php post_class( 'rp-job-card' ); ?> style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; transition: box-shadow 0.2s;">

						<h2 class="rp-job-title" style="font-size: 1.25rem; font-weight: 600; margin: 0 0 12px 0;">
							<a href="<?php the_permalink(); ?>" style="color: inherit; text-decoration: none;">
								<?php the_title(); ?>
							</a>
						</h2>

						<div class="rp-job-meta" style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.875rem; color: #6b7280; margin-bottom: 16px;">

							<?php
							// Standort.
							$locations = get_the_terms( get_the_ID(), 'job_location' );
							if ( $locations && ! is_wp_error( $locations ) ) :
								?>
								<span class="rp-job-meta-item">
									<svg style="width: 16px; height: 16px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
									</svg>
									<?php echo esc_html( $locations[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php
							// Beschäftigungsart.
							$types = get_the_terms( get_the_ID(), 'employment_type' );
							if ( $types && ! is_wp_error( $types ) ) :
								?>
								<span class="rp-job-meta-item">
									<svg style="width: 16px; height: 16px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
									</svg>
									<?php echo esc_html( $types[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php
							// Remote.
							$remote = get_post_meta( get_the_ID(), '_rp_remote_option', true );
							if ( $remote && 'no' !== $remote ) :
								$remote_labels = [
									'hybrid' => __( 'Hybrid', 'recruiting-playbook' ),
									'full'   => __( 'Remote', 'recruiting-playbook' ),
								];
								?>
								<span class="rp-job-meta-item" style="color: #059669;">
									<svg style="width: 16px; height: 16px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
									</svg>
									<?php echo esc_html( $remote_labels[ $remote ] ?? $remote ); ?>
								</span>
							<?php endif; ?>

						</div>

						<?php if ( has_excerpt() ) : ?>
							<div class="rp-job-excerpt" style="color: #4b5563; margin-bottom: 16px; line-height: 1.5;">
								<?php the_excerpt(); ?>
							</div>
						<?php endif; ?>

						<a href="<?php the_permalink(); ?>" class="rp-btn rp-btn-primary" style="display: inline-flex; align-items: center; padding: 10px 20px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 500; transition: background 0.2s;">
							<?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
							<svg style="width: 16px; height: 16px; margin-left: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
							</svg>
						</a>

					</article>

				<?php endwhile; ?>

			</div>

			<?php
			// Pagination.
			the_posts_pagination(
				[
					'mid_size'  => 2,
					'prev_text' => __( '&laquo; Zurück', 'recruiting-playbook' ),
					'next_text' => __( 'Weiter &raquo;', 'recruiting-playbook' ),
				]
			);
			?>

		<?php else : ?>

			<div class="rp-no-jobs" style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 8px;">
				<svg style="width: 64px; height: 64px; color: #9ca3af; margin-bottom: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
				</svg>
				<h2 style="font-size: 1.5rem; margin-bottom: 8px;"><?php esc_html_e( 'Aktuell keine offenen Stellen', 'recruiting-playbook' ); ?></h2>
				<p style="color: #6b7280;"><?php esc_html_e( 'Schauen Sie später wieder vorbei oder kontaktieren Sie uns für Initiativbewerbungen.', 'recruiting-playbook' ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</div>

<?php
get_footer();

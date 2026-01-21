<?php
/**
 * Template: Einzelne Stelle
 *
 * Dieses Template kann im Theme überschrieben werden:
 * theme/recruiting-playbook/single-job_listing.php
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	// Meta-Daten laden.
	$salary_min      = get_post_meta( get_the_ID(), '_rp_salary_min', true );
	$salary_max      = get_post_meta( get_the_ID(), '_rp_salary_max', true );
	$salary_currency = get_post_meta( get_the_ID(), '_rp_salary_currency', true ) ?: 'EUR';
	$salary_period   = get_post_meta( get_the_ID(), '_rp_salary_period', true ) ?: 'month';
	$hide_salary     = get_post_meta( get_the_ID(), '_rp_hide_salary', true );
	$deadline        = get_post_meta( get_the_ID(), '_rp_application_deadline', true );
	$contact_person  = get_post_meta( get_the_ID(), '_rp_contact_person', true );
	$contact_email   = get_post_meta( get_the_ID(), '_rp_contact_email', true );
	$contact_phone   = get_post_meta( get_the_ID(), '_rp_contact_phone', true );
	$remote_option   = get_post_meta( get_the_ID(), '_rp_remote_option', true );
	$start_date      = get_post_meta( get_the_ID(), '_rp_start_date', true );

	// Taxonomien.
	$locations  = get_the_terms( get_the_ID(), 'job_location' );
	$types      = get_the_terms( get_the_ID(), 'employment_type' );
	$categories = get_the_terms( get_the_ID(), 'job_category' );

	// Gehalt formatieren.
	$salary_display = '';
	if ( ! $hide_salary && ( $salary_min || $salary_max ) ) {
		$period_labels = [
			'hour'  => __( '/Std.', 'recruiting-playbook' ),
			'month' => __( '/Monat', 'recruiting-playbook' ),
			'year'  => __( '/Jahr', 'recruiting-playbook' ),
		];
		$period_label  = $period_labels[ $salary_period ] ?? '';

		if ( $salary_min && $salary_max ) {
			$salary_display = number_format( (float) $salary_min, 0, ',', '.' ) . ' - ' . number_format( (float) $salary_max, 0, ',', '.' ) . ' ' . $salary_currency . $period_label;
		} elseif ( $salary_min ) {
			$salary_display = __( 'Ab ', 'recruiting-playbook' ) . number_format( (float) $salary_min, 0, ',', '.' ) . ' ' . $salary_currency . $period_label;
		} elseif ( $salary_max ) {
			$salary_display = __( 'Bis ', 'recruiting-playbook' ) . number_format( (float) $salary_max, 0, ',', '.' ) . ' ' . $salary_currency . $period_label;
		}
	}

	// Remote Labels.
	$remote_labels = [
		'no'     => __( 'Vor Ort', 'recruiting-playbook' ),
		'hybrid' => __( 'Hybrid (teilweise Remote)', 'recruiting-playbook' ),
		'full'   => __( '100% Remote möglich', 'recruiting-playbook' ),
	];
	?>

	<article <?php post_class( 'rp-job-single' ); ?>>
		<div class="rp-container" style="max-width: 1000px; margin: 0 auto; padding: 20px;">

			<!-- Zurück-Link -->
			<nav class="rp-breadcrumb" style="margin-bottom: 20px;">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'job_listing' ) ); ?>" style="color: #2271b1; text-decoration: none;">
					&larr; <?php esc_html_e( 'Alle Stellen', 'recruiting-playbook' ); ?>
				</a>
			</nav>

			<div class="rp-job-layout" style="display: grid; gap: 30px; grid-template-columns: 1fr 300px;">

				<!-- Hauptinhalt -->
				<div class="rp-job-content">

					<header class="rp-job-header" style="margin-bottom: 30px;">
						<h1 class="rp-job-title" style="font-size: 2rem; font-weight: 700; margin: 0 0 16px 0;">
							<?php the_title(); ?>
						</h1>

						<div class="rp-job-meta" style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.875rem; color: #6b7280;">

							<?php if ( $locations && ! is_wp_error( $locations ) ) : ?>
								<span class="rp-job-meta-item" style="display: flex; align-items: center;">
									<svg style="width: 18px; height: 18px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
									</svg>
									<?php echo esc_html( $locations[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $types && ! is_wp_error( $types ) ) : ?>
								<span class="rp-job-meta-item" style="display: flex; align-items: center;">
									<svg style="width: 18px; height: 18px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
									</svg>
									<?php echo esc_html( $types[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $remote_option && isset( $remote_labels[ $remote_option ] ) ) : ?>
								<span class="rp-job-meta-item" style="display: flex; align-items: center; <?php echo 'no' !== $remote_option ? 'color: #059669;' : ''; ?>">
									<svg style="width: 18px; height: 18px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
									</svg>
									<?php echo esc_html( $remote_labels[ $remote_option ] ); ?>
								</span>
							<?php endif; ?>

						</div>
					</header>

					<!-- Stellenbeschreibung -->
					<div class="rp-job-description" style="line-height: 1.7; color: #374151;">
						<?php the_content(); ?>
					</div>

				</div>

				<!-- Sidebar -->
				<aside class="rp-job-sidebar">

					<!-- Jetzt bewerben -->
					<div class="rp-sidebar-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 20px;">
						<a href="#rp-apply-form" class="rp-btn rp-btn-primary" style="display: block; width: 100%; padding: 14px 20px; background: #2271b1; color: #fff; text-align: center; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 1rem;">
							<?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?>
						</a>

						<?php if ( $deadline ) : ?>
							<p style="margin-top: 12px; font-size: 0.875rem; color: #6b7280; text-align: center;">
								<?php
								printf(
									/* translators: %s: Application deadline */
									esc_html__( 'Bewerbungsfrist: %s', 'recruiting-playbook' ),
									esc_html( date_i18n( get_option( 'date_format' ), strtotime( $deadline ) ) )
								);
								?>
							</p>
						<?php endif; ?>
					</div>

					<!-- Details -->
					<div class="rp-sidebar-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 20px;">
						<h3 style="font-size: 1rem; font-weight: 600; margin: 0 0 16px 0;"><?php esc_html_e( 'Details', 'recruiting-playbook' ); ?></h3>

						<dl style="margin: 0; font-size: 0.875rem;">

							<?php if ( $salary_display ) : ?>
								<dt style="font-weight: 500; color: #374151;"><?php esc_html_e( 'Gehalt', 'recruiting-playbook' ); ?></dt>
								<dd style="margin: 0 0 12px 0; color: #6b7280;"><?php echo esc_html( $salary_display ); ?></dd>
							<?php endif; ?>

							<?php if ( $start_date ) : ?>
								<dt style="font-weight: 500; color: #374151;"><?php esc_html_e( 'Startdatum', 'recruiting-playbook' ); ?></dt>
								<dd style="margin: 0 0 12px 0; color: #6b7280;"><?php echo esc_html( $start_date ); ?></dd>
							<?php endif; ?>

							<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
								<dt style="font-weight: 500; color: #374151;"><?php esc_html_e( 'Berufsfeld', 'recruiting-playbook' ); ?></dt>
								<dd style="margin: 0 0 12px 0; color: #6b7280;"><?php echo esc_html( $categories[0]->name ); ?></dd>
							<?php endif; ?>

						</dl>
					</div>

					<!-- Kontakt -->
					<?php if ( $contact_person || $contact_email || $contact_phone ) : ?>
						<div class="rp-sidebar-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
							<h3 style="font-size: 1rem; font-weight: 600; margin: 0 0 16px 0;"><?php esc_html_e( 'Ansprechpartner', 'recruiting-playbook' ); ?></h3>

							<?php if ( $contact_person ) : ?>
								<p style="margin: 0 0 8px 0; font-weight: 500;"><?php echo esc_html( $contact_person ); ?></p>
							<?php endif; ?>

							<?php if ( $contact_email ) : ?>
								<p style="margin: 0 0 4px 0; font-size: 0.875rem;">
									<a href="mailto:<?php echo esc_attr( $contact_email ); ?>" style="color: #2271b1; text-decoration: none;">
										<?php echo esc_html( $contact_email ); ?>
									</a>
								</p>
							<?php endif; ?>

							<?php if ( $contact_phone ) : ?>
								<p style="margin: 0; font-size: 0.875rem;">
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $contact_phone ) ); ?>" style="color: #2271b1; text-decoration: none;">
										<?php echo esc_html( $contact_phone ); ?>
									</a>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

				</aside>

			</div>

			<!-- Bewerbungsformular Platzhalter -->
			<div id="rp-apply-form" class="rp-apply-section" style="margin-top: 40px; padding: 40px; background: #f9fafb; border-radius: 8px;">
				<h2 style="font-size: 1.5rem; margin: 0 0 16px 0;"><?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?></h2>
				<p style="color: #6b7280;">
					<?php esc_html_e( 'Das Bewerbungsformular wird in Phase 1B implementiert.', 'recruiting-playbook' ); ?>
				</p>
			</div>

		</div>
	</article>

	<?php
endwhile;

get_footer();

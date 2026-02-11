<?php
/**
 * Template: Einzelne Stelle
 *
 * Dieses Template kann im Theme überschrieben werden:
 * theme/recruiting-playbook/single-job_listing.php
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

	// Tracking-Daten vorbereiten.
	$tracking_category = ( $categories && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';
	$tracking_location = ( $locations && ! is_wp_error( $locations ) ) ? $locations[0]->name : '';
	$tracking_type     = ( $types && ! is_wp_error( $types ) ) ? $types[0]->name : '';
	?>

	<!-- Conversion Tracking Data (GTM DataLayer) -->
	<div
		data-rp-tracking
		data-rp-job-id="<?php echo esc_attr( get_the_ID() ); ?>"
		data-rp-job-title="<?php echo esc_attr( get_the_title() ); ?>"
		data-rp-job-category="<?php echo esc_attr( $tracking_category ); ?>"
		data-rp-job-location="<?php echo esc_attr( $tracking_location ); ?>"
		data-rp-employment-type="<?php echo esc_attr( $tracking_type ); ?>"
		style="display: none;"
		aria-hidden="true"
	></div>

	<article <?php post_class( 'rp-plugin' ); ?>>
		<div class="rp-mx-auto rp-px-4 rp-py-8" style="max-width: var(--wp--style--global--wide-size, 1200px);">

			<!-- Zurück-Link -->
			<nav class="rp-mb-6">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'job_listing' ) ); ?>" class="rp-no-underline">
					&larr; <?php esc_html_e( 'Alle Stellen', 'recruiting-playbook' ); ?>
				</a>
			</nav>

			<div class="rp-grid rp-grid-cols-1 lg:rp-grid-cols-3 rp-gap-8">

				<!-- Hauptinhalt -->
				<div class="lg:rp-col-span-2">

					<header class="rp-mb-8">
						<h1 class="rp-mb-4">
							<?php the_title(); ?>
						</h1>

						<div class="rp-flex rp-flex-wrap rp-gap-2 rp-text-xs">

							<?php if ( $locations && ! is_wp_error( $locations ) ) : ?>
								<span class="rp-badge rp-badge-gray">
									<svg class="rp-w-3.5 rp-h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
									</svg>
									<?php echo esc_html( $locations[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $types && ! is_wp_error( $types ) ) : ?>
								<span class="rp-badge rp-badge-gray">
									<svg class="rp-w-3.5 rp-h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
									</svg>
									<?php echo esc_html( $types[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $remote_option && isset( $remote_labels[ $remote_option ] ) ) : ?>
								<span class="rp-badge rp-badge-gray">
									<svg class="rp-w-3.5 rp-h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
									</svg>
									<?php echo esc_html( $remote_labels[ $remote_option ] ); ?>
								</span>
							<?php endif; ?>

						</div>
					</header>

					<!-- Stellenbeschreibung -->
					<div class="rp-job-description rp-job-content rp-prose rp-max-w-none">
						<?php the_content(); ?>
					</div>

				</div>

				<!-- Sidebar -->
				<aside class="rp-space-y-6 lg:rp-sticky lg:rp-top-8">

					<!-- Jetzt bewerben Button -->
					<div>
						<a href="#apply-form" class="wp-element-button rp-block rp-w-full rp-py-3 rp-text-center rp-no-underline">
							<?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?>
						</a>
						<?php if ( $deadline ) : ?>
							<p class="rp-mt-2 rp-text-sm rp-text-gray-500 rp-text-center">
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

					<!-- KI-Job-Match Button (automatisch, wenn Feature aktiv) -->
					<?php if ( function_exists( 'rp_has_cv_matching' ) && rp_has_cv_matching() ) : ?>
						<div class="rp-mt-4">
							<?php echo do_shortcode( '[rp_ai_job_match style="outline" class="rp-w-full"]' ); ?>
						</div>
					<?php endif; ?>

					<!-- Details -->
					<div>
						<h3 class="rp-mb-4"><?php esc_html_e( 'Details', 'recruiting-playbook' ); ?></h3>

						<dl class="rp-divide-y rp-divide-gray-200 rp-text-sm">
							<?php if ( $salary_display ) : ?>
								<div class="rp-flex rp-justify-between rp-py-3">
									<dt class="rp-font-medium"><?php esc_html_e( 'Gehalt', 'recruiting-playbook' ); ?></dt>
									<dd><?php echo esc_html( $salary_display ); ?></dd>
								</div>
							<?php endif; ?>

							<?php if ( $start_date ) : ?>
								<div class="rp-flex rp-justify-between rp-py-3">
									<dt class="rp-font-medium"><?php esc_html_e( 'Startdatum', 'recruiting-playbook' ); ?></dt>
									<dd><?php echo esc_html( $start_date ); ?></dd>
								</div>
							<?php endif; ?>

							<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
								<div class="rp-flex rp-justify-between rp-py-3">
									<dt class="rp-font-medium"><?php esc_html_e( 'Berufsfeld', 'recruiting-playbook' ); ?></dt>
									<dd><?php echo esc_html( $categories[0]->name ); ?></dd>
								</div>
							<?php endif; ?>
						</dl>
					</div>

					<!-- Kontakt -->
					<?php if ( $contact_person || $contact_email || $contact_phone ) : ?>
						<div>
							<h3 class="rp-mb-4"><?php esc_html_e( 'Ansprechpartner', 'recruiting-playbook' ); ?></h3>

							<dl class="rp-divide-y rp-divide-gray-200 rp-text-sm">
								<?php if ( $contact_person ) : ?>
									<div class="rp-flex rp-justify-between rp-py-3">
										<dt class="rp-font-medium"><?php esc_html_e( 'Name', 'recruiting-playbook' ); ?></dt>
										<dd><?php echo esc_html( $contact_person ); ?></dd>
									</div>
								<?php endif; ?>

								<?php if ( $contact_email ) : ?>
									<div class="rp-flex rp-justify-between rp-py-3">
										<dt class="rp-font-medium"><?php esc_html_e( 'E-Mail', 'recruiting-playbook' ); ?></dt>
										<dd>
											<a href="mailto:<?php echo esc_attr( $contact_email ); ?>" class="rp-underline">
												<?php echo esc_html( $contact_email ); ?>
											</a>
										</dd>
									</div>
								<?php endif; ?>

								<?php if ( $contact_phone ) : ?>
									<div class="rp-flex rp-justify-between rp-py-3">
										<dt class="rp-font-medium"><?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?></dt>
										<dd>
											<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $contact_phone ) ); ?>" class="rp-underline">
												<?php echo esc_html( $contact_phone ); ?>
											</a>
										</dd>
									</div>
								<?php endif; ?>
							</dl>
						</div>
					<?php endif; ?>

				</aside>

			</div>

			<!-- Bewerbungsformular - verwendet .rp-card für Design-System-Konsistenz -->
			<div id="apply-form" class="rp-card rp-mt-12 rp-overflow-hidden" data-job-id="<?php echo esc_attr( get_the_ID() ); ?>" data-rp-application-form>
				<h2 class="rp-text-2xl rp-font-bold rp-text-gray-900 rp-mb-6"><?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?></h2>

				<?php
				// Formular dynamisch aus FormConfigService rendern.
				try {
					$form_render_service = new \RecruitingPlaybook\Services\FormRenderService();
					echo $form_render_service->render( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} catch ( \Throwable $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( '[Recruiting Playbook] Form render error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
					echo '<p class="rp-text-gray-500">' . esc_html__( 'Das Bewerbungsformular konnte nicht geladen werden.', 'recruiting-playbook' ) . '</p>';
				}
				?>
			</div>

		</div>
	</article>

	<?php
endwhile;

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

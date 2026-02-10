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
				if ( is_tax( 'job_category' ) ) {
					$term = get_queried_object();
					printf(
						/* translators: %s: Category name */
						esc_html__( 'Jobs: %s', 'recruiting-playbook' ),
						esc_html( $term->name )
					);
				} elseif ( is_tax( 'job_location' ) ) {
					$term = get_queried_object();
					printf(
						/* translators: %s: Location name */
						esc_html__( 'Jobs in %s', 'recruiting-playbook' ),
						esc_html( $term->name )
					);
				} elseif ( is_tax( 'employment_type' ) ) {
					$term = get_queried_object();
					printf(
						/* translators: %s: Employment type */
						esc_html__( 'Jobs: %s', 'recruiting-playbook' ),
						esc_html( $term->name )
					);
				} else {
					$settings = get_option( 'rp_settings', [] );
					$company  = $settings['company_name'] ?? get_bloginfo( 'name' );
					printf(
						/* translators: %s: Company name */
						esc_html__( 'Karriere bei %s', 'recruiting-playbook' ),
						esc_html( $company )
					);
				}
				?>
			</h2>

			<p class="rp-mt-2 rp-text-lg rp-leading-8 rp-text-gray-600">
				<?php
				if ( is_tax() ) {
					$term = get_queried_object();
					if ( $term->description ) {
						echo esc_html( $term->description );
					} else {
						esc_html_e( 'Entdecken Sie unsere aktuellen Stellenangebote in diesem Bereich.', 'recruiting-playbook' );
					}
				} else {
					esc_html_e( 'Entdecken Sie unsere aktuellen Stellenangebote und werden Sie Teil unseres Teams.', 'recruiting-playbook' );
				}
				?>
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

						// Job-Card Partial einbinden.
						include RP_PLUGIN_DIR . 'templates/partials/job-card.php';

					endwhile;
					?>

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

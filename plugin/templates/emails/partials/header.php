<?php
/**
 * E-Mail Header Partial
 *
 * Zeigt das Firmenlogo und optional einen Header-Text.
 *
 * Verfügbare Variablen (vom Parent-Template):
 * - $company       : Firmenname
 * - $logo_url      : URL zum Firmenlogo
 * - $primary_color : Primärfarbe für das Design
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

// Variablen mit Fallbacks.
$company       = $company ?? get_bloginfo( 'name' );
$logo_url      = $logo_url ?? '';
$primary_color = $primary_color ?? '#0073aa';
?>
<!-- Header -->
<tr>
	<td style="background-color: <?php echo esc_attr( $primary_color ); ?>; padding: 30px 40px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
		<?php if ( ! empty( $logo_url ) ) : ?>
			<img
				src="<?php echo esc_url( $logo_url ); ?>"
				alt="<?php echo esc_attr( $company ); ?>"
				width="180"
				style="max-width: 180px; height: auto; display: block; margin: 0 auto; border: 0;"
			>
		<?php else : ?>
			<h1 style="margin: 0; padding: 0; color: #ffffff; font-size: 24px; font-weight: 600; letter-spacing: -0.5px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
				<?php echo esc_html( $company ); ?>
			</h1>
		<?php endif; ?>
	</td>
</tr>
<!-- /Header -->

<?php
/**
 * E-Mail Footer Partial
 *
 * Zeigt Footer mit Firmeninfos und optionalem Abmelde-Link.
 *
 * Verfügbare Variablen (vom Parent-Template):
 * - $company         : Firmenname
 * - $footer_text     : Zusätzlicher Footer-Text
 * - $unsubscribe_url : Abmelde-URL (optional)
 * - $primary_color   : Primärfarbe für das Design
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

// Variablen mit Fallbacks.
$company         = $company ?? get_bloginfo( 'name' );
$footer_text     = $footer_text ?? '';
$unsubscribe_url = $unsubscribe_url ?? '';
$primary_color   = $primary_color ?? '#0073aa';

// Firmen-Einstellungen laden.
$settings     = get_option( 'rp_settings', [] );
$contact_info = [];

if ( ! empty( $settings['company_address'] ) ) {
	$contact_info[] = $settings['company_address'];
}
if ( ! empty( $settings['company_phone'] ) ) {
	$contact_info[] = $settings['company_phone'];
}
if ( ! empty( $settings['company_email'] ) ) {
	$contact_info[] = $settings['company_email'];
}
?>
<!-- Footer -->
<?php
$settings            = get_option( 'rp_settings', [] );
$hide_branding       = ! empty( $settings['hide_email_branding'] ) && function_exists( 'rp_can' ) && rp_can( 'custom_branding' );
$recruiting_url      = 'https://recruiting-playbook.de';
?>
<?php if ( ! $hide_branding || ! empty( $unsubscribe_url ) ) : ?>
<tr>
	<td style="background-color: #f8f9fa; padding: 30px 40px; text-align: left; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

		<?php if ( ! $hide_branding ) : ?>
			<!-- Versand-Hinweis mit Link -->
			<p style="margin: 0; color: #adb5bd; font-size: 11px; line-height: 1.4;">
				<?php
				printf(
					/* translators: %s: Link to Recruiting Playbook website */
					esc_html__( 'Versand über %s', 'recruiting-playbook' ),
					'<a href="' . esc_url( $recruiting_url ) . '" style="color: #adb5bd; text-decoration: underline;">Recruiting Playbook</a>'
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $unsubscribe_url ) ) : ?>
			<!-- Abmelde-Link -->
			<p style="margin: <?php echo $hide_branding ? '0' : '10px 0 0 0'; ?>;">
				<a
					href="<?php echo esc_url( $unsubscribe_url ); ?>"
					style="color: #6c757d; font-size: 11px; text-decoration: underline;"
				>
					<?php esc_html_e( 'E-Mail-Benachrichtigungen abbestellen', 'recruiting-playbook' ); ?>
				</a>
			</p>
		<?php endif; ?>

	</td>
</tr>
<?php endif; ?>
<!-- /Footer -->

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
<tr>
	<td style="background-color: #f8f9fa; padding: 30px 40px; text-align: center; border-top: 1px solid #e9ecef;">

		<!-- Firmenname -->
		<p style="margin: 0 0 15px 0; color: #6c757d; font-size: 14px; font-weight: 600;">
			<?php echo esc_html( $company ); ?>
		</p>

		<?php if ( ! empty( $contact_info ) ) : ?>
			<!-- Kontaktinformationen -->
			<p style="margin: 0 0 15px 0; color: #6c757d; font-size: 13px; line-height: 1.5;">
				<?php echo esc_html( implode( ' | ', $contact_info ) ); ?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $footer_text ) ) : ?>
			<!-- Zusätzlicher Footer-Text -->
			<p style="margin: 0 0 15px 0; color: #6c757d; font-size: 13px; line-height: 1.5;">
				<?php echo esc_html( $footer_text ); ?>
			</p>
		<?php endif; ?>

		<!-- Rechtlicher Hinweis -->
		<p style="margin: 20px 0 0 0; color: #adb5bd; font-size: 11px; line-height: 1.4;">
			<?php
			printf(
				/* translators: %s: Company name */
				esc_html__( 'Diese E-Mail wurde automatisch von %s versendet.', 'recruiting-playbook' ),
				esc_html( $company )
			);
			?>
			<br>
			<?php esc_html_e( 'Bitte antworten Sie nicht auf diese E-Mail.', 'recruiting-playbook' ); ?>
		</p>

		<?php if ( ! empty( $unsubscribe_url ) ) : ?>
			<!-- Abmelde-Link -->
			<p style="margin: 15px 0 0 0;">
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
<!-- /Footer -->

<?php
/**
 * E-Mail Template: Eingangsbestätigung
 *
 * Wird automatisch an Bewerber gesendet, wenn eine neue Bewerbung eingeht.
 *
 * Verfügbare Platzhalter:
 * - {vorname}        : Vorname des Bewerbers
 * - {nachname}       : Nachname des Bewerbers
 * - {anrede}         : Informelle Anrede (z.B. "Herr Müller")
 * - {anrede_formal}  : Formelle Anrede (z.B. "Sehr geehrter Herr Müller")
 * - {stelle}         : Stellenbezeichnung
 * - {firma}          : Firmenname
 * - {kontakt_email}  : Kontakt-E-Mail
 * - {kontakt_telefon}: Kontakttelefon
 * - {bewerbungsdatum}: Datum der Bewerbung
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

// Diese Variablen werden vom EmailService übergeben.
$placeholders = $placeholders ?? [];
?>
<p><?php echo esc_html( $placeholders['anrede_formal'] ?? 'Sehr geehrte Bewerberin, sehr geehrter Bewerber' ); ?>,</p>

<p>
	<?php
	printf(
		/* translators: 1: Position name, 2: Company name */
		esc_html__( 'vielen Dank für Ihre Bewerbung als %1$s bei %2$s.', 'recruiting-playbook' ),
		'<strong>' . esc_html( $placeholders['stelle'] ?? '' ) . '</strong>',
		esc_html( $placeholders['firma'] ?? '' )
	);
	?>
</p>

<p>
	<?php esc_html_e( 'Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig prüfen. Sie erhalten in Kürze weitere Informationen zum Stand Ihrer Bewerbung.', 'recruiting-playbook' ); ?>
</p>

<!-- Info-Box -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0; background-color: #f8f9fa; border-radius: 6px; border-left: 4px solid #0073aa;">
	<tr>
		<td style="padding: 20px;">
			<p style="margin: 0 0 10px 0; font-weight: 600; color: #333;">
				<?php esc_html_e( 'Ihre Bewerbung im Überblick:', 'recruiting-playbook' ); ?>
			</p>
			<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="font-size: 14px;">
				<tr>
					<td style="padding: 5px 15px 5px 0; color: #6c757d;"><?php esc_html_e( 'Position:', 'recruiting-playbook' ); ?></td>
					<td style="padding: 5px 0; color: #333;"><?php echo esc_html( $placeholders['stelle'] ?? '' ); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px 15px 5px 0; color: #6c757d;"><?php esc_html_e( 'Eingangsdatum:', 'recruiting-playbook' ); ?></td>
					<td style="padding: 5px 0; color: #333;"><?php echo esc_html( $placeholders['bewerbungsdatum'] ?? '' ); ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<p>
	<?php esc_html_e( 'Bei Fragen stehen wir Ihnen gerne zur Verfügung:', 'recruiting-playbook' ); ?>
</p>

<ul style="margin: 15px 0; padding-left: 20px;">
	<?php if ( ! empty( $placeholders['kontakt_email'] ) ) : ?>
		<li style="margin-bottom: 5px;">
			<?php esc_html_e( 'E-Mail:', 'recruiting-playbook' ); ?>
			<a href="mailto:<?php echo esc_attr( $placeholders['kontakt_email'] ); ?>" style="color: #0073aa; text-decoration: none;">
				<?php echo esc_html( $placeholders['kontakt_email'] ); ?>
			</a>
		</li>
	<?php endif; ?>
	<?php if ( ! empty( $placeholders['kontakt_telefon'] ) ) : ?>
		<li style="margin-bottom: 5px;">
			<?php esc_html_e( 'Telefon:', 'recruiting-playbook' ); ?>
			<?php echo esc_html( $placeholders['kontakt_telefon'] ); ?>
		</li>
	<?php endif; ?>
</ul>

<p>
	<?php esc_html_e( 'Mit freundlichen Grüßen', 'recruiting-playbook' ); ?><br>
	<?php echo esc_html( $placeholders['absender_name'] ?? $placeholders['firma'] ?? '' ); ?>
</p>

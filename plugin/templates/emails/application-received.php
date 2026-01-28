<?php
/**
 * E-Mail Template: Eingangsbestätigung
 *
 * Wird automatisch an Bewerber gesendet, wenn eine neue Bewerbung eingeht.
 *
 * Verfügbare Platzhalter (werden automatisch ersetzt):
 * - {anrede_formal}    : Formelle Anrede (z.B. "Sehr geehrter Herr Müller")
 * - {stelle}           : Stellenbezeichnung
 * - {firma}            : Firmenname
 * - {bewerbung_datum}  : Datum der Bewerbung
 *
 * Kontaktdaten werden automatisch aus der Signatur übernommen.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$placeholders = $placeholders ?? [];
?>
<p><?php echo esc_html( $placeholders['anrede_formal'] ?? __( 'Sehr geehrte Bewerberin, sehr geehrter Bewerber', 'recruiting-playbook' ) ); ?>,</p>

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
					<td style="padding: 5px 0; color: #333;"><?php echo esc_html( $placeholders['bewerbung_datum'] ?? date_i18n( get_option( 'date_format' ) ) ); ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<p>
	<?php esc_html_e( 'Bei Fragen stehen wir Ihnen gerne zur Verfügung.', 'recruiting-playbook' ); ?>
</p>
<?php // Signatur mit Kontaktdaten wird automatisch vom EmailService angehängt. ?>

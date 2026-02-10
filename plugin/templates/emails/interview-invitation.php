<?php
/**
 * E-Mail Template: Interview-Einladung
 *
 * Wird an Bewerber gesendet, um sie zu einem Vorstellungsgespräch einzuladen.
 *
 * Verfügbare Platzhalter (werden automatisch ersetzt):
 * - {anrede_formal}  : Formelle Anrede (z.B. "Sehr geehrter Herr Mustermann")
 * - {stelle}         : Stellenbezeichnung
 * - {firma}          : Firmenname
 *
 * Manuelle Felder (mit ____ markiert, vom User auszufüllen):
 * - Datum, Uhrzeit, Ort, Ansprechpartner
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
		/* translators: %s: Position name */
		esc_html__( 'wir freuen uns, Ihnen mitteilen zu können, dass Ihre Bewerbung als %s uns überzeugt hat.', 'recruiting-playbook' ),
		'<strong>' . esc_html( $placeholders['stelle'] ?? '' ) . '</strong>'
	);
	?>
</p>

<p>
	<?php esc_html_e( 'Gerne würden wir Sie persönlich kennenlernen und laden Sie herzlich zu einem Vorstellungsgespräch ein.', 'recruiting-playbook' ); ?>
</p>

<!-- Termin-Box -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0; background-color: #e8f4fd; border-radius: 6px; border-left: 4px solid #0073aa;">
	<tr>
		<td style="padding: 25px;">
			<p style="margin: 0 0 15px 0; font-weight: 600; color: #0073aa; font-size: 16px;">
				<?php esc_html_e( 'Termindetails', 'recruiting-playbook' ); ?>
			</p>
			<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="font-size: 15px;">
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #6c757d; vertical-align: top;">
						<strong><?php esc_html_e( 'Datum:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #333;">
						____
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #6c757d; vertical-align: top;">
						<strong><?php esc_html_e( 'Uhrzeit:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #333;">
						____
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #6c757d; vertical-align: top;">
						<strong><?php esc_html_e( 'Ort:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #333;">
						____
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #6c757d; vertical-align: top;">
						<strong><?php esc_html_e( 'Ansprechpartner:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #333;">
						____
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<p>
	<?php esc_html_e( 'Bitte bestätigen Sie uns den Termin oder teilen Sie uns mit, falls Sie einen alternativen Termin benötigen.', 'recruiting-playbook' ); ?>
</p>

<p>
	<strong><?php esc_html_e( 'Bitte bringen Sie mit:', 'recruiting-playbook' ); ?></strong>
</p>
<ul style="margin: 10px 0 20px 0; padding-left: 20px;">
	<li style="margin-bottom: 8px;"><?php esc_html_e( 'Gültigen Personalausweis oder Reisepass', 'recruiting-playbook' ); ?></li>
	<li style="margin-bottom: 8px;"><?php esc_html_e( 'Aktuelle Zeugnisse (falls noch nicht eingereicht)', 'recruiting-playbook' ); ?></li>
	<li style="margin-bottom: 8px;"><?php esc_html_e( 'Gegebenenfalls Arbeitsproben oder Portfolio', 'recruiting-playbook' ); ?></li>
</ul>

<p>
	<?php esc_html_e( 'Wir freuen uns auf das Gespräch mit Ihnen!', 'recruiting-playbook' ); ?>
</p>
<?php // Signatur wird automatisch vom EmailService angehängt. ?>

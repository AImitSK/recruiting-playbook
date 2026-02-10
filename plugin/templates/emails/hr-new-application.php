<?php
/**
 * E-Mail Template: HR-Benachrichtigung (Neue Bewerbung)
 *
 * Wird an HR-Verantwortliche gesendet, wenn eine neue Bewerbung eingeht.
 *
 * Verfügbare Platzhalter:
 * - {bewerber_name}    : Vollständiger Name des Bewerbers
 * - {bewerber_email}   : E-Mail des Bewerbers
 * - {stelle}           : Stellenbezeichnung
 * - {firma}            : Firmenname
 * - {bewerbungsdatum}  : Datum der Bewerbung
 * - {bewerbung_link}   : Link zur Bewerbung im Backend
 * - {bewerbung_id}     : ID der Bewerbung
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$placeholders = $placeholders ?? [];
?>
<p><?php esc_html_e( 'Hallo,', 'recruiting-playbook' ); ?></p>

<p>
	<?php
	printf(
		/* translators: %s: Position name */
		esc_html__( 'eine neue Bewerbung für die Position %s ist eingegangen.', 'recruiting-playbook' ),
		'<strong>' . esc_html( $placeholders['stelle'] ?? '' ) . '</strong>'
	);
	?>
</p>

<!-- Bewerber-Info Box -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0; background-color: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
	<tr>
		<td style="padding: 25px;">
			<p style="margin: 0 0 15px 0; font-weight: 600; color: #856404; font-size: 16px;">
				<?php esc_html_e( 'Bewerberinformationen', 'recruiting-playbook' ); ?>
			</p>
			<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="font-size: 15px;">
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #856404; vertical-align: top;">
						<strong><?php esc_html_e( 'Name:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #856404;">
						<?php echo esc_html( $placeholders['bewerber_name'] ?? '' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #856404; vertical-align: top;">
						<strong><?php esc_html_e( 'E-Mail:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #856404;">
						<a href="mailto:<?php echo esc_attr( $placeholders['bewerber_email'] ?? '' ); ?>" style="color: #856404;">
							<?php echo esc_html( $placeholders['bewerber_email'] ?? '' ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #856404; vertical-align: top;">
						<strong><?php esc_html_e( 'Position:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #856404;">
						<?php echo esc_html( $placeholders['stelle'] ?? '' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #856404; vertical-align: top;">
						<strong><?php esc_html_e( 'Eingangsdatum:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #856404;">
						<?php echo esc_html( $placeholders['bewerbungsdatum'] ?? '' ); ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php if ( ! empty( $placeholders['bewerbung_link'] ) ) : ?>
<!-- CTA Button -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 25px 0;">
	<tr>
		<td style="border-radius: 6px; background-color: #0073aa;">
			<a
				href="<?php echo esc_url( $placeholders['bewerbung_link'] ); ?>"
				style="display: inline-block; padding: 14px 28px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 15px;"
			>
				<?php esc_html_e( 'Bewerbung ansehen', 'recruiting-playbook' ); ?>
			</a>
		</td>
	</tr>
</table>
<?php endif; ?>

<p style="color: #6c757d; font-size: 14px;">
	<?php
	printf(
		/* translators: %s: Application ID */
		esc_html__( 'Bewerbungs-ID: %s', 'recruiting-playbook' ),
		esc_html( $placeholders['bewerbung_id'] ?? '' )
	);
	?>
</p>

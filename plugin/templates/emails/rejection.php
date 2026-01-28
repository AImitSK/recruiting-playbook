<?php
/**
 * E-Mail Template: Absage
 *
 * Wird an Bewerber gesendet, um ihnen eine Absage mitzuteilen.
 *
 * Verfügbare Platzhalter:
 * - {vorname}        : Vorname des Bewerbers
 * - {nachname}       : Nachname des Bewerbers
 * - {anrede}         : Informelle Anrede
 * - {anrede_formal}  : Formelle Anrede
 * - {stelle}         : Stellenbezeichnung
 * - {firma}          : Firmenname
 * - {absender_name}  : Name des Absenders
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
		esc_html__( 'vielen Dank für Ihr Interesse an der Position %1$s bei %2$s und die Zeit, die Sie in Ihre Bewerbung investiert haben.', 'recruiting-playbook' ),
		'<strong>' . esc_html( $placeholders['stelle'] ?? '' ) . '</strong>',
		esc_html( $placeholders['firma'] ?? '' )
	);
	?>
</p>

<p>
	<?php esc_html_e( 'Nach sorgfältiger Prüfung aller eingegangenen Bewerbungen müssen wir Ihnen leider mitteilen, dass wir uns für andere Kandidaten entschieden haben, deren Profile noch besser zu den aktuellen Anforderungen der Position passen.', 'recruiting-playbook' ); ?>
</p>

<p>
	<?php esc_html_e( 'Diese Entscheidung ist uns nicht leicht gefallen und stellt keine Bewertung Ihrer fachlichen Qualifikationen dar.', 'recruiting-playbook' ); ?>
</p>

<!-- Aufmunternde Box -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0; background-color: #f8f9fa; border-radius: 6px; border-left: 4px solid #6c757d;">
	<tr>
		<td style="padding: 20px;">
			<p style="margin: 0; color: #495057; font-size: 14px; line-height: 1.6;">
				<?php esc_html_e( 'Wir speichern Ihre Unterlagen gerne in unserem Talent-Pool und kommen auf Sie zurück, falls eine passende Position frei wird – natürlich nur mit Ihrem Einverständnis.', 'recruiting-playbook' ); ?>
			</p>
		</td>
	</tr>
</table>

<p>
	<?php esc_html_e( 'Wir wünschen Ihnen für Ihre weitere berufliche Zukunft alles Gute und viel Erfolg.', 'recruiting-playbook' ); ?>
</p>
<?php // Signatur wird automatisch vom EmailService angehängt. ?>

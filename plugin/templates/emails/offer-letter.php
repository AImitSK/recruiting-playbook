<?php
/**
 * E-Mail Template: Stellenangebot
 *
 * Wird an Bewerber gesendet, um ihnen ein Stellenangebot zu unterbreiten.
 *
 * Verfügbare Platzhalter (werden automatisch ersetzt):
 * - {anrede_formal}  : Formelle Anrede (z.B. "Sehr geehrter Herr Mustermann")
 * - {stelle}         : Stellenbezeichnung
 * - {firma}          : Firmenname
 *
 * Manuelle Felder (mit ____ markiert, vom User auszufüllen):
 * - Startdatum, Vertragsart, Arbeitszeit, Antwortfrist
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
		esc_html__( 'wir freuen uns sehr, Ihnen nach den positiven Gesprächen ein Angebot für die Position %s unterbreiten zu können!', 'recruiting-playbook' ),
		'<strong>' . esc_html( $placeholders['stelle'] ?? '' ) . '</strong>'
	);
	?>
</p>

<p>
	<?php esc_html_e( 'Sie haben uns in den Gesprächen überzeugt und wir sind davon überzeugt, dass Sie eine wertvolle Bereicherung für unser Team sein werden.', 'recruiting-playbook' ); ?>
</p>

<!-- Angebot-Box -->
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 25px 0; background-color: #d4edda; border-radius: 6px; border-left: 4px solid #28a745;">
	<tr>
		<td style="padding: 25px;">
			<p style="margin: 0 0 15px 0; font-weight: 600; color: #155724; font-size: 16px;">
				<?php esc_html_e( 'Eckdaten des Angebots', 'recruiting-playbook' ); ?>
			</p>
			<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="font-size: 15px;">
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #155724; vertical-align: top;">
						<strong><?php esc_html_e( 'Position:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #155724;">
						<?php echo esc_html( $placeholders['stelle'] ?? '' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #155724; vertical-align: top;">
						<strong><?php esc_html_e( 'Startdatum:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #155724;">
						____
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #155724; vertical-align: top;">
						<strong><?php esc_html_e( 'Vertragsart:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #155724;">
						____
					</td>
				</tr>
				<tr>
					<td style="padding: 8px 20px 8px 0; color: #155724; vertical-align: top;">
						<strong><?php esc_html_e( 'Arbeitszeit:', 'recruiting-playbook' ); ?></strong>
					</td>
					<td style="padding: 8px 0; color: #155724;">
						____
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<p>
	<?php esc_html_e( 'Die detaillierten Vertragsunterlagen erhalten Sie in Kürze per Post oder als separaten Anhang.', 'recruiting-playbook' ); ?>
</p>

<p>
	<?php
	printf(
		/* translators: %s: Response deadline placeholder */
		esc_html__( 'Bitte teilen Sie uns Ihre Entscheidung bis zum %s mit.', 'recruiting-playbook' ),
		'<strong>____</strong>'
	);
	?>
</p>

<p>
	<?php esc_html_e( 'Für Rückfragen stehen wir Ihnen selbstverständlich gerne zur Verfügung.', 'recruiting-playbook' ); ?>
</p>

<p>
	<?php esc_html_e( 'Wir freuen uns darauf, Sie bald in unserem Team willkommen zu heißen!', 'recruiting-playbook' ); ?>
</p>
<?php // Signatur wird automatisch vom EmailService angehängt. ?>

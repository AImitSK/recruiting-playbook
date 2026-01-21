<?php
/**
 * Email Service - E-Mail-Benachrichtigungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

/**
 * Service für E-Mail-Versand
 */
class EmailService {

	/**
	 * E-Mail-Absender
	 *
	 * @var string
	 */
	private string $from_email;

	/**
	 * E-Mail-Absendername
	 *
	 * @var string
	 */
	private string $from_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		$settings = get_option( 'rp_settings', [] );

		$this->from_email = $settings['notification_email'] ?? get_option( 'admin_email' );
		$this->from_name  = $settings['company_name'] ?? get_bloginfo( 'name' );
	}

	/**
	 * E-Mail an HR senden: Neue Bewerbung eingegangen
	 *
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public function sendApplicationReceived( int $application_id ): bool {
		$application = $this->getApplicationData( $application_id );
		if ( ! $application ) {
			return false;
		}

		$settings = get_option( 'rp_settings', [] );
		$to = $settings['notification_email'] ?? get_option( 'admin_email' );

		$subject = sprintf(
			/* translators: 1: Applicant name, 2: Job title */
			__( '[Neue Bewerbung] %1$s für %2$s', 'recruiting-playbook' ),
			$application['candidate_name'],
			$application['job_title']
		);

		$message = $this->renderTemplate( 'email-application-received', [
			'application'    => $application,
			'admin_url'      => admin_url( 'admin.php?page=rp-applications&id=' . $application_id ),
			'company_name'   => $this->from_name,
		] );

		return $this->send( $to, $subject, $message );
	}

	/**
	 * E-Mail an Bewerber senden: Bestätigung
	 *
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public function sendApplicantConfirmation( int $application_id ): bool {
		$application = $this->getApplicationData( $application_id );
		if ( ! $application ) {
			return false;
		}

		$to = $application['email'];

		$subject = sprintf(
			/* translators: %s: Job title */
			__( 'Bewerbungseingang bestätigt: %s', 'recruiting-playbook' ),
			$application['job_title']
		);

		$message = $this->renderTemplate( 'email-applicant-confirmation', [
			'application'  => $application,
			'company_name' => $this->from_name,
			'job_url'      => get_permalink( $application['job_id'] ),
		] );

		return $this->send( $to, $subject, $message );
	}

	/**
	 * E-Mail an Bewerber senden: Absage
	 *
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public function sendRejectionEmail( int $application_id ): bool {
		$settings = get_option( 'rp_settings', [] );

		// Prüfen ob automatische Absage-E-Mails aktiviert sind
		if ( empty( $settings['auto_rejection_email'] ) ) {
			return false;
		}

		$application = $this->getApplicationData( $application_id );
		if ( ! $application ) {
			return false;
		}

		$to = $application['email'];

		$subject = sprintf(
			/* translators: %s: Job title */
			__( 'Ihre Bewerbung: %s', 'recruiting-playbook' ),
			$application['job_title']
		);

		$message = $this->renderTemplate( 'email-rejection', [
			'application'  => $application,
			'company_name' => $this->from_name,
		] );

		return $this->send( $to, $subject, $message );
	}

	/**
	 * E-Mail senden
	 *
	 * @param string $to      Empfänger.
	 * @param string $subject Betreff.
	 * @param string $message Nachricht (HTML).
	 * @param array  $headers Optionale Header.
	 * @return bool
	 */
	public function send( string $to, string $subject, string $message, array $headers = [] ): bool {
		// Standard-Header setzen
		$default_headers = [
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $this->from_name, $this->from_email ),
		];

		$headers = array_merge( $default_headers, $headers );

		// Filter für Erweiterungen
		$message = apply_filters( 'rp_email_content', $message, $to, $subject );

		// E-Mail versenden
		$sent = wp_mail( $to, $subject, $message, $headers );

		// Logging
		$this->logEmail( $to, $subject, $sent );

		return $sent;
	}

	/**
	 * Bewerbungsdaten für E-Mail abrufen
	 *
	 * @param int $application_id Application ID.
	 * @return array|null
	 */
	private function getApplicationData( int $application_id ): ?array {
		global $wpdb;

		$app_table = $wpdb->prefix . 'rp_applications';
		$cand_table = $wpdb->prefix . 'rp_candidates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT a.*, c.salutation, c.first_name, c.last_name, c.email, c.phone
				FROM {$app_table} a
				LEFT JOIN {$cand_table} c ON a.candidate_id = c.id
				WHERE a.id = %d",
				$application_id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		// Job-Daten hinzufügen
		$job = get_post( (int) $row['job_id'] );

		return [
			'id'             => $application_id,
			'job_id'         => $row['job_id'],
			'job_title'      => $job ? $job->post_title : '',
			'salutation'     => $row['salutation'] ?? '',
			'first_name'     => $row['first_name'],
			'last_name'      => $row['last_name'],
			'candidate_name' => trim( $row['first_name'] . ' ' . $row['last_name'] ),
			'email'          => $row['email'],
			'phone'          => $row['phone'] ?? '',
			'cover_letter'   => $row['cover_letter'] ?? '',
			'created_at'     => $row['created_at'],
		];
	}

	/**
	 * E-Mail-Template rendern
	 *
	 * @param string $template Template-Name.
	 * @param array  $data     Daten für Template.
	 * @return string
	 */
	private function renderTemplate( string $template, array $data ): string {
		// Template-Pfad
		$template_file = RP_PLUGIN_DIR . 'templates/emails/' . $template . '.php';

		// Fallback auf einfaches Template wenn nicht vorhanden
		if ( ! file_exists( $template_file ) ) {
			return $this->getFallbackTemplate( $template, $data );
		}

		// Template rendern
		ob_start();
		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include $template_file;
		return ob_get_clean();
	}

	/**
	 * Fallback-Template generieren
	 *
	 * @param string $template Template-Name.
	 * @param array  $data     Daten.
	 * @return string
	 */
	private function getFallbackTemplate( string $template, array $data ): string {
		$app = $data['application'] ?? [];
		$company = $data['company_name'] ?? '';

		$styles = '
			body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; }
			.container { max-width: 600px; margin: 0 auto; padding: 20px; }
			.header { border-bottom: 2px solid #2271b1; padding-bottom: 20px; margin-bottom: 20px; }
			.footer { border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 30px; font-size: 12px; color: #6b7280; }
			.btn { display: inline-block; padding: 12px 24px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; }
		';

		switch ( $template ) {
			case 'email-application-received':
				$content = sprintf(
					'<h2>%s</h2>
					<p>%s</p>
					<table style="width:100%%; border-collapse:collapse; margin:20px 0;">
						<tr><td style="padding:8px 0;"><strong>%s:</strong></td><td>%s</td></tr>
						<tr><td style="padding:8px 0;"><strong>%s:</strong></td><td>%s</td></tr>
						<tr><td style="padding:8px 0;"><strong>%s:</strong></td><td><a href="mailto:%s">%s</a></td></tr>
						<tr><td style="padding:8px 0;"><strong>%s:</strong></td><td>%s</td></tr>
					</table>
					<p><a href="%s" class="btn">%s</a></p>',
					__( 'Neue Bewerbung eingegangen', 'recruiting-playbook' ),
					sprintf(
						/* translators: %s: Job title */
						__( 'Eine neue Bewerbung für die Stelle "%s" ist eingegangen.', 'recruiting-playbook' ),
						esc_html( $app['job_title'] ?? '' )
					),
					__( 'Name', 'recruiting-playbook' ),
					esc_html( $app['candidate_name'] ?? '' ),
					__( 'Stelle', 'recruiting-playbook' ),
					esc_html( $app['job_title'] ?? '' ),
					__( 'E-Mail', 'recruiting-playbook' ),
					esc_attr( $app['email'] ?? '' ),
					esc_html( $app['email'] ?? '' ),
					__( 'Eingegangen am', 'recruiting-playbook' ),
					esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $app['created_at'] ?? '' ) ) ),
					esc_url( $data['admin_url'] ?? '' ),
					__( 'Bewerbung ansehen', 'recruiting-playbook' )
				);
				break;

			case 'email-applicant-confirmation':
				$greeting = ! empty( $app['salutation'] )
					? sprintf( __( 'Guten Tag %s %s', 'recruiting-playbook' ), $app['salutation'], $app['last_name'] )
					: sprintf( __( 'Guten Tag %s', 'recruiting-playbook' ), $app['first_name'] );

				$content = sprintf(
					'<h2>%s</h2>
					<p>%s,</p>
					<p>%s</p>
					<p><strong>%s:</strong> %s</p>
					<p>%s</p>
					<p>%s</p>
					<p>%s<br>%s</p>',
					__( 'Bewerbungseingang bestätigt', 'recruiting-playbook' ),
					$greeting,
					__( 'vielen Dank für Ihre Bewerbung! Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig prüfen.', 'recruiting-playbook' ),
					__( 'Stelle', 'recruiting-playbook' ),
					esc_html( $app['job_title'] ?? '' ),
					__( 'Sie erhalten von uns Rückmeldung, sobald wir Ihre Bewerbung geprüft haben.', 'recruiting-playbook' ),
					__( 'Bei Fragen stehen wir Ihnen gerne zur Verfügung.', 'recruiting-playbook' ),
					__( 'Mit freundlichen Grüßen', 'recruiting-playbook' ),
					esc_html( $company )
				);
				break;

			case 'email-rejection':
				$greeting = ! empty( $app['salutation'] )
					? sprintf( __( 'Guten Tag %s %s', 'recruiting-playbook' ), $app['salutation'], $app['last_name'] )
					: sprintf( __( 'Guten Tag %s', 'recruiting-playbook' ), $app['first_name'] );

				$content = sprintf(
					'<h2>%s</h2>
					<p>%s,</p>
					<p>%s</p>
					<p>%s</p>
					<p>%s</p>
					<p>%s<br>%s</p>',
					__( 'Ihre Bewerbung', 'recruiting-playbook' ),
					$greeting,
					sprintf(
						/* translators: %s: Job title */
						__( 'vielen Dank für Ihr Interesse an der Position "%s" und die Zeit, die Sie in Ihre Bewerbung investiert haben.', 'recruiting-playbook' ),
						esc_html( $app['job_title'] ?? '' )
					),
					__( 'Nach sorgfältiger Prüfung müssen wir Ihnen leider mitteilen, dass wir uns für andere Kandidaten entschieden haben, deren Profil besser zu unseren aktuellen Anforderungen passt.', 'recruiting-playbook' ),
					__( 'Wir wünschen Ihnen für Ihre weitere berufliche Zukunft alles Gute und viel Erfolg.', 'recruiting-playbook' ),
					__( 'Mit freundlichen Grüßen', 'recruiting-playbook' ),
					esc_html( $company )
				);
				break;

			default:
				$content = '';
		}

		return sprintf(
			'<!DOCTYPE html>
			<html>
			<head>
				<meta charset="UTF-8">
				<style>%s</style>
			</head>
			<body>
				<div class="container">
					<div class="header">
						<strong>%s</strong>
					</div>
					%s
					<div class="footer">
						<p>%s</p>
					</div>
				</div>
			</body>
			</html>',
			$styles,
			esc_html( $company ),
			$content,
			sprintf(
				/* translators: %s: Company name */
				__( 'Diese E-Mail wurde automatisch von %s versendet.', 'recruiting-playbook' ),
				esc_html( $company )
			)
		);
	}

	/**
	 * E-Mail loggen
	 *
	 * @param string $to      Empfänger.
	 * @param string $subject Betreff.
	 * @param bool   $sent    Erfolgreich gesendet.
	 */
	private function logEmail( string $to, string $subject, bool $sent ): void {
		// In WP-Debug-Log schreiben
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Recruiting Playbook] Email %s - To: %s, Subject: %s',
					$sent ? 'sent' : 'FAILED',
					$to,
					$subject
				)
			);
		}

		// Hook für optionales DB-Logging (Pro-Feature)
		do_action( 'rp_email_sent', $to, $subject, $sent );
	}

	/**
	 * SMTP-Konfiguration prüfen
	 *
	 * @return array Status der E-Mail-Konfiguration.
	 */
	public static function checkSmtpConfig(): array {
		$result = [
			'configured' => false,
			'message'    => '',
		];

		// Prüfen ob SMTP-Plugin aktiv ist
		$smtp_plugins = [
			'wp-mail-smtp/wp_mail_smtp.php',
			'post-smtp/postman-smtp.php',
			'smtp-mailer/main.php',
			'easy-wp-smtp/easy-wp-smtp.php',
		];

		foreach ( $smtp_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				$result['configured'] = true;
				$result['message'] = __( 'SMTP-Plugin erkannt. E-Mails sollten zuverlässig zugestellt werden.', 'recruiting-playbook' );
				return $result;
			}
		}

		// Prüfen ob wp_mail überschrieben wurde
		if ( has_filter( 'wp_mail' ) || has_filter( 'phpmailer_init' ) ) {
			$result['configured'] = true;
			$result['message'] = __( 'E-Mail-Konfiguration erkannt.', 'recruiting-playbook' );
			return $result;
		}

		// Keine SMTP-Konfiguration gefunden
		$result['message'] = __( 'Keine SMTP-Konfiguration erkannt. Wir empfehlen die Installation eines SMTP-Plugins für zuverlässigen E-Mail-Versand.', 'recruiting-playbook' );

		return $result;
	}
}

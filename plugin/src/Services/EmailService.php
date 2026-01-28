<?php
/**
 * Email Service - E-Mail-Benachrichtigungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\EmailLogRepository;

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
	 * Template Service
	 *
	 * @var EmailTemplateService|null
	 */
	private ?EmailTemplateService $templateService = null;

	/**
	 * Queue Service
	 *
	 * @var EmailQueueService|null
	 */
	private ?EmailQueueService $queueService = null;

	/**
	 * Placeholder Service
	 *
	 * @var PlaceholderService|null
	 */
	private ?PlaceholderService $placeholderService = null;

	/**
	 * Log Repository
	 *
	 * @var EmailLogRepository|null
	 */
	private ?EmailLogRepository $logRepository = null;

	/**
	 * Signature Service
	 *
	 * @var SignatureService|null
	 */
	private ?SignatureService $signatureService = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$settings = get_option( 'rp_settings', [] );

		$this->from_email = $settings['notification_email'] ?? get_option( 'admin_email' );
		$this->from_name  = $settings['company_name'] ?? get_bloginfo( 'name' );
	}

	/**
	 * Template Service lazy-laden
	 *
	 * @return EmailTemplateService
	 */
	private function getTemplateService(): EmailTemplateService {
		if ( null === $this->templateService ) {
			$this->templateService = new EmailTemplateService();
		}
		return $this->templateService;
	}

	/**
	 * Queue Service lazy-laden
	 *
	 * @return EmailQueueService
	 */
	private function getQueueService(): EmailQueueService {
		if ( null === $this->queueService ) {
			$this->queueService = new EmailQueueService();
		}
		return $this->queueService;
	}

	/**
	 * Placeholder Service lazy-laden
	 *
	 * @return PlaceholderService
	 */
	private function getPlaceholderService(): PlaceholderService {
		if ( null === $this->placeholderService ) {
			$this->placeholderService = new PlaceholderService();
		}
		return $this->placeholderService;
	}

	/**
	 * Log Repository lazy-laden
	 *
	 * @return EmailLogRepository
	 */
	private function getLogRepository(): EmailLogRepository {
		if ( null === $this->logRepository ) {
			$this->logRepository = new EmailLogRepository();
		}
		return $this->logRepository;
	}

	/**
	 * Signature Service lazy-laden
	 *
	 * @return SignatureService
	 */
	private function getSignatureService(): SignatureService {
		if ( null === $this->signatureService ) {
			$this->signatureService = new SignatureService();
		}
		return $this->signatureService;
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

		// Firmen-Signatur anhängen (automatische E-Mail ohne spezifischen Absender).
		// user_id = 0 bedeutet explizit "kein User", nutzt nur Firmen-Fallback.
		$message = $this->appendSignature( $message, null, 0 );

		return $this->send( $to, $subject, $message );
	}

	/**
	 * E-Mail an Bewerber senden: Absage
	 *
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public function sendRejectionEmail( int $application_id ): bool {
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

		// Signatur des eingeloggten Users anhängen (HR-Mitarbeiter löst Absage aus).
		$message = $this->appendSignature( $message, null, null );

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
	 * E-Mail mit Template senden (Pro-Feature)
	 *
	 * @param int      $template_id    Template-ID.
	 * @param int      $application_id Bewerbungs-ID.
	 * @param array    $custom_data    Zusätzliche Platzhalter-Daten.
	 * @param bool     $use_queue      Queue verwenden.
	 * @param int|null $signature_id   Signatur-ID (null = automatisch, 0 = keine).
	 * @return int|bool Log-ID bei Queue, true bei direktem Versand, false bei Fehler.
	 */
	public function sendWithTemplate( int $template_id, int $application_id, array $custom_data = [], bool $use_queue = true, ?int $signature_id = null ): int|bool {
		// Pro-Feature Check.
		if ( ! function_exists( 'rp_can' ) || ! rp_can( 'email_templates' ) ) {
			return false;
		}

		$application = $this->getApplicationData( $application_id );
		if ( ! $application ) {
			return false;
		}

		// Kontext für Platzhalter aufbauen.
		$context = $this->buildContext( $application, $custom_data );

		// Template rendern.
		$rendered = $this->getTemplateService()->render( $template_id, $context );
		if ( ! $rendered ) {
			return false;
		}

		// Signatur anhängen (außer wenn explizit 0 = keine Signatur).
		$body_html = $rendered['body_html'];
		if ( 0 !== $signature_id ) {
			$body_html = $this->appendSignature( $body_html, $signature_id );
		}

		$email_data = [
			'application_id'  => $application_id,
			'candidate_id'    => (int) $application['candidate_id'],
			'template_id'     => $template_id,
			'recipient_email' => $application['email'],
			'recipient_name'  => $application['candidate_name'],
			'sender_email'    => $this->from_email,
			'sender_name'     => $this->from_name,
			'subject'         => $rendered['subject'],
			'body_html'       => $body_html,
			'body_text'       => wp_strip_all_tags( $body_html ),
		];

		if ( $use_queue && $this->getQueueService()->isActionSchedulerAvailable() ) {
			return $this->getQueueService()->enqueue( $email_data );
		}

		// Direkt senden.
		$sent = $this->send( $email_data['recipient_email'], $email_data['subject'], $email_data['body_html'] );

		// In Log speichern.
		if ( $sent ) {
			$email_data['status'] = 'sent';
			$email_data['sent_at'] = current_time( 'mysql' );
		} else {
			$email_data['status'] = 'failed';
		}
		$this->getLogRepository()->create( $email_data );

		return $sent;
	}

	/**
	 * E-Mail mit Template-Slug senden
	 *
	 * @param string   $template_slug  Template-Slug.
	 * @param int      $application_id Bewerbungs-ID.
	 * @param array    $custom_data    Zusätzliche Platzhalter-Daten.
	 * @param bool     $use_queue      Queue verwenden.
	 * @param int|null $signature_id   Signatur-ID (null = automatisch, 0 = keine).
	 * @return int|bool Log-ID bei Queue, true bei direktem Versand, false bei Fehler.
	 */
	public function sendWithTemplateSlug( string $template_slug, int $application_id, array $custom_data = [], bool $use_queue = true, ?int $signature_id = null ): int|bool {
		$template = $this->getTemplateService()->findBySlug( $template_slug );

		if ( ! $template ) {
			return false;
		}

		return $this->sendWithTemplate( (int) $template['id'], $application_id, $custom_data, $use_queue, $signature_id );
	}

	/**
	 * Benutzerdefinierte E-Mail senden (ohne Template)
	 *
	 * @param int      $application_id Bewerbungs-ID.
	 * @param string   $subject        Betreff.
	 * @param string   $body_html      HTML-Inhalt.
	 * @param bool     $use_queue      Queue verwenden.
	 * @param int|null $signature_id   Signatur-ID (null = automatisch, 0 = keine).
	 * @return int|bool Log-ID bei Queue, true bei direktem Versand, false bei Fehler.
	 */
	public function sendCustomEmail( int $application_id, string $subject, string $body_html, bool $use_queue = true, ?int $signature_id = null ): int|bool {
		$application = $this->getApplicationData( $application_id );
		if ( ! $application ) {
			return false;
		}

		// Kontext für Platzhalter.
		$context = $this->buildContext( $application, [] );

		// Platzhalter ersetzen.
		$subject   = $this->getPlaceholderService()->replace( $subject, $context );
		$body_html = $this->getPlaceholderService()->replace( $body_html, $context );

		// Signatur anhängen (außer wenn explizit 0 = keine Signatur).
		if ( 0 !== $signature_id ) {
			$body_html = $this->appendSignature( $body_html, $signature_id );
		}

		$email_data = [
			'application_id'  => $application_id,
			'candidate_id'    => (int) $application['candidate_id'],
			'recipient_email' => $application['email'],
			'recipient_name'  => $application['candidate_name'],
			'sender_email'    => $this->from_email,
			'sender_name'     => $this->from_name,
			'subject'         => $subject,
			'body_html'       => $body_html,
			'body_text'       => wp_strip_all_tags( $body_html ),
		];

		if ( $use_queue && $this->getQueueService()->isActionSchedulerAvailable() ) {
			return $this->getQueueService()->enqueue( $email_data );
		}

		// Direkt senden.
		$sent = $this->send( $email_data['recipient_email'], $email_data['subject'], $email_data['body_html'] );

		// In Log speichern.
		$email_data['status'] = $sent ? 'sent' : 'failed';
		if ( $sent ) {
			$email_data['sent_at'] = current_time( 'mysql' );
		}
		$this->getLogRepository()->create( $email_data );

		return $sent;
	}

	/**
	 * E-Mail für späteren Versand planen
	 *
	 * @param int    $template_id    Template-ID.
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $scheduled_at   Geplanter Zeitpunkt (Y-m-d H:i:s).
	 * @param array  $custom_data    Zusätzliche Platzhalter-Daten.
	 * @return int|false Log-ID oder false bei Fehler.
	 */
	public function scheduleEmail( int $template_id, int $application_id, string $scheduled_at, array $custom_data = [] ): int|false {
		// Pro-Feature Check.
		if ( ! function_exists( 'rp_can' ) || ! rp_can( 'email_templates' ) ) {
			return false;
		}

		$application = $this->getApplicationData( $application_id );
		if ( ! $application ) {
			return false;
		}

		// Kontext für Platzhalter.
		$context = $this->buildContext( $application, $custom_data );

		// Template rendern.
		$rendered = $this->getTemplateService()->render( $template_id, $context );
		if ( ! $rendered ) {
			return false;
		}

		return $this->getQueueService()->schedule( [
			'application_id'  => $application_id,
			'candidate_id'    => (int) $application['candidate_id'],
			'template_id'     => $template_id,
			'recipient_email' => $application['email'],
			'recipient_name'  => $application['candidate_name'],
			'sender_email'    => $this->from_email,
			'sender_name'     => $this->from_name,
			'subject'         => $rendered['subject'],
			'body_html'       => $rendered['body_html'],
			'body_text'       => $rendered['body_text'],
		], $scheduled_at );
	}

	/**
	 * E-Mail-Historie für Bewerbung abrufen
	 *
	 * @param int   $application_id Bewerbungs-ID.
	 * @param array $args           Query-Argumente.
	 * @return array
	 */
	public function getHistory( int $application_id, array $args = [] ): array {
		return $this->getLogRepository()->findByApplication( $application_id, $args );
	}

	/**
	 * E-Mail-Historie für Kandidaten abrufen
	 *
	 * @param int   $candidate_id Kandidaten-ID.
	 * @param array $args         Query-Argumente.
	 * @return array
	 */
	public function getHistoryByCandidate( int $candidate_id, array $args = [] ): array {
		return $this->getLogRepository()->findByCandidate( $candidate_id, $args );
	}

	/**
	 * Kontext für Platzhalter aufbauen
	 *
	 * @param array $application Bewerbungs-Daten.
	 * @param array $custom_data Zusätzliche Daten.
	 * @return array
	 */
	private function buildContext( array $application, array $custom_data ): array {
		$job = null;
		if ( ! empty( $application['job_id'] ) ) {
			$job_post = get_post( (int) $application['job_id'] );
			if ( $job_post ) {
				$job = [
					'title'           => $job_post->post_title,
					'url'             => get_permalink( $job_post ),
					'location'        => get_post_meta( $job_post->ID, '_job_location', true ) ?: '',
					'employment_type' => get_post_meta( $job_post->ID, '_employment_type', true ) ?: '',
				];
			}
		}

		return [
			'application' => $application,
			'candidate'   => [
				'salutation'  => $application['salutation'] ?? '',
				'first_name'  => $application['first_name'] ?? '',
				'last_name'   => $application['last_name'] ?? '',
				'email'       => $application['email'] ?? '',
				'phone'       => $application['phone'] ?? '',
			],
			'job'         => $job ?? [],
			'custom'      => $custom_data,
		];
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
			'candidate_id'   => $row['candidate_id'],
			'job_id'         => $row['job_id'],
			'job_title'      => $job ? $job->post_title : '',
			'status'         => $row['status'] ?? 'new',
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
			body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
			.container { max-width: 600px; margin: 0 auto; padding: 20px; text-align: left; }
			.header { border-bottom: 2px solid #2271b1; padding-bottom: 20px; margin-bottom: 20px; text-align: left; }
			.content { padding: 20px 0; text-align: left; }
			.footer { padding-top: 20px; margin-top: 30px; font-size: 12px; color: #6b7280; text-align: left; }
			a { color: #2271b1; }
			.btn { display: inline-block; padding: 12px 24px; background: #2271b1; color: #fff !important; text-decoration: none; border-radius: 4px; }
			table { border-collapse: collapse; }
			td { padding: 8px; }
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
					/* translators: 1: Salutation (e.g. Herr/Frau), 2: Last name */
					? sprintf( __( 'Guten Tag %1$s %2$s', 'recruiting-playbook' ), $app['salutation'], $app['last_name'] )
					/* translators: %s: First name */
					: sprintf( __( 'Guten Tag %s', 'recruiting-playbook' ), $app['first_name'] );

				$content = sprintf(
					'<h2>%s</h2>
					<p>%s,</p>
					<p>%s</p>
					<p><strong>%s:</strong> %s</p>
					<p>%s</p>
					<p>%s</p>',
					__( 'Bewerbungseingang bestätigt', 'recruiting-playbook' ),
					$greeting,
					__( 'vielen Dank für Ihre Bewerbung! Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig prüfen.', 'recruiting-playbook' ),
					__( 'Stelle', 'recruiting-playbook' ),
					esc_html( $app['job_title'] ?? '' ),
					__( 'Sie erhalten von uns Rückmeldung, sobald wir Ihre Bewerbung geprüft haben.', 'recruiting-playbook' ),
					__( 'Bei Fragen stehen wir Ihnen gerne zur Verfügung.', 'recruiting-playbook' )
				);
				break;

			case 'email-rejection':
				$greeting = ! empty( $app['salutation'] )
					/* translators: 1: Salutation (e.g. Herr/Frau), 2: Last name */
					? sprintf( __( 'Guten Tag %1$s %2$s', 'recruiting-playbook' ), $app['salutation'], $app['last_name'] )
					/* translators: %s: First name */
					: sprintf( __( 'Guten Tag %s', 'recruiting-playbook' ), $app['first_name'] );

				$content = sprintf(
					'<h2>%s</h2>
					<p>%s,</p>
					<p>%s</p>
					<p>%s</p>
					<p>%s</p>',
					__( 'Ihre Bewerbung', 'recruiting-playbook' ),
					$greeting,
					sprintf(
						/* translators: %s: Job title */
						__( 'vielen Dank für Ihr Interesse an der Position "%s" und die Zeit, die Sie in Ihre Bewerbung investiert haben.', 'recruiting-playbook' ),
						esc_html( $app['job_title'] ?? '' )
					),
					__( 'Nach sorgfältiger Prüfung müssen wir Ihnen leider mitteilen, dass wir uns für andere Kandidaten entschieden haben, deren Profil besser zu unseren aktuellen Anforderungen passt.', 'recruiting-playbook' ),
					__( 'Wir wünschen Ihnen für Ihre weitere berufliche Zukunft alles Gute und viel Erfolg.', 'recruiting-playbook' )
				);
				break;

			default:
				$content = '';
		}

		// Branding-Hinweis (Pro-User können es abschalten).
		$settings      = get_option( 'rp_settings', [] );
		$hide_branding = ! empty( $settings['hide_email_branding'] ) && function_exists( 'rp_can' ) && rp_can( 'custom_branding' );
		$footer_html   = '';

		if ( ! $hide_branding ) {
			$footer_html = sprintf(
				'<p>%s</p>',
				sprintf(
					/* translators: %s: Link to Recruiting Playbook website */
					__( 'Versand über %s', 'recruiting-playbook' ),
					'<a href="https://recruiting-playbook.de" style="color: #6b7280; text-decoration: underline;">Recruiting Playbook</a>'
				)
			);
		}

		return sprintf(
			'<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>%s</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<strong>%s</strong>
		</div>
		<div class="content">
			%s
		</div>
		<div class="footer">
			%s
		</div>
	</div>
</body>
</html>',
			$styles,
			esc_html( $company ),
			$content,
			$footer_html
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
	 * Signatur an E-Mail-Body anhängen
	 *
	 * Verwendet die Fallback-Kette:
	 * 1. Explizit angegebene Signatur (wenn $signature_id gesetzt)
	 * 2. User-Default-Signatur (wenn $user_id > 0)
	 * 3. Firmen-Signatur (aus DB)
	 * 4. Auto-generierte Signatur aus Firmendaten
	 *
	 * @param string   $body_html     E-Mail-Body ohne Signatur.
	 * @param int|null $signature_id  Signatur-ID (null = automatisch).
	 * @param int|null $user_id       User-ID für Fallback:
	 *                                - null = aktueller User (für manuelle E-Mails)
	 *                                - 0 = kein User, nur Firmen-Signatur (für automatische E-Mails)
	 *                                - >0 = spezifischer User.
	 * @return string E-Mail-Body mit Signatur.
	 */
	private function appendSignature( string $body_html, ?int $signature_id = null, ?int $user_id = null ): string {
		// User-ID für Fallback bestimmen.
		// null = aktueller User, 0 = explizit kein User (automatische E-Mails).
		if ( null === $user_id ) {
			$user_id = get_current_user_id() ?: null;
		}

		// Signatur mit Fallback-Kette rendern.
		$signature = $this->getSignatureService()->renderWithFallback( $signature_id, $user_id );

		if ( empty( $signature ) ) {
			return $body_html;
		}

		// Signatur innerhalb des Content-Containers einfügen (vor dem schließenden </div> des Content).
		// Suche nach </div> vor dem Footer und füge Signatur davor ein.
		if ( preg_match( '/<\/div>\s*<div class="footer">/i', $body_html ) ) {
			return preg_replace(
				'/(<\/div>)(\s*<div class="footer">)/i',
				$signature . "\n\t\t$1$2",
				$body_html,
				1
			);
		}

		// Alternative: Vor dem Footer-Div einfügen.
		if ( preg_match( '/<div class="footer">/i', $body_html ) ) {
			return preg_replace(
				'/(<div class="footer">)/i',
				$signature . "\n\t\t$1",
				$body_html,
				1
			);
		}

		// Fallback: Vor </body> einfügen.
		if ( stripos( $body_html, '</body>' ) !== false ) {
			return str_ireplace( '</body>', $signature . "\n</body>", $body_html );
		}

		// Letzter Fallback: Am Ende anhängen.
		return $body_html . $signature;
	}

	/**
	 * Firmen-Signatur für automatische E-Mails anhängen
	 *
	 * Automatische E-Mails (Eingangsbestätigung, Absage etc.) verwenden
	 * immer die Firmen-Signatur, da kein konkreter Absender-User existiert.
	 *
	 * @param string $body_html E-Mail-Body ohne Signatur.
	 * @return string E-Mail-Body mit Firmen-Signatur.
	 */
	private function appendCompanySignature( string $body_html ): string {
		// null, 0 = keine explizite Signatur, explizit kein User → nur Firmen-Signatur.
		return $this->appendSignature( $body_html, null, 0 );
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

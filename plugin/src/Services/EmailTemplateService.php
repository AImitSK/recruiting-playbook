<?php
/**
 * Email Template Service - Geschäftslogik für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\EmailTemplateRepository;

/**
 * Service für E-Mail-Template Operationen
 */
class EmailTemplateService {

	/**
	 * Template Repository
	 *
	 * @var EmailTemplateRepository
	 */
	private EmailTemplateRepository $repository;

	/**
	 * Placeholder Service
	 *
	 * @var PlaceholderService
	 */
	private PlaceholderService $placeholderService;

	/**
	 * Constructor
	 *
	 * @param EmailTemplateRepository|null $repository         Optional repository.
	 * @param PlaceholderService|null      $placeholderService Optional placeholder service.
	 */
	public function __construct(
		?EmailTemplateRepository $repository = null,
		?PlaceholderService $placeholderService = null
	) {
		$this->repository         = $repository ?? new EmailTemplateRepository();
		$this->placeholderService = $placeholderService ?? new PlaceholderService();
	}

	/**
	 * Template erstellen
	 *
	 * @param array $data Template-Daten.
	 * @return array|false Template oder false bei Fehler.
	 */
	public function create( array $data ): array|false {
		// Validierung.
		$validation = $this->validate( $data );
		if ( is_wp_error( $validation ) ) {
			return false;
		}

		// Platzhalter aus Inhalt extrahieren.
		$variables = array_unique( array_merge(
			$this->placeholderService->findPlaceholders( $data['subject'] ?? '' ),
			$this->placeholderService->findPlaceholders( $data['body_html'] ?? '' )
		) );

		$data['variables'] = $variables;

		// Plain-Text generieren wenn nicht vorhanden.
		if ( empty( $data['body_text'] ) && ! empty( $data['body_html'] ) ) {
			$data['body_text'] = $this->htmlToText( $data['body_html'] );
		}

		$id = $this->repository->create( $data );

		if ( false === $id ) {
			return false;
		}

		return $this->repository->find( $id );
	}

	/**
	 * Template aktualisieren
	 *
	 * @param int   $id   Template-ID.
	 * @param array $data Update-Daten.
	 * @return array|false Template oder false bei Fehler.
	 */
	public function update( int $id, array $data ): array|false {
		$template = $this->repository->find( $id );

		if ( ! $template ) {
			return false;
		}

		// System-Templates können nur eingeschränkt bearbeitet werden.
		if ( $template['is_system'] ) {
			// Nur bestimmte Felder erlaubt.
			$allowed = [ 'subject', 'body_html', 'body_text', 'is_active' ];
			$data    = array_intersect_key( $data, array_flip( $allowed ) );
		}

		// Validierung.
		$validation = $this->validate( array_merge( $template, $data ), $id );
		if ( is_wp_error( $validation ) ) {
			return false;
		}

		// Platzhalter aktualisieren.
		if ( isset( $data['subject'] ) || isset( $data['body_html'] ) ) {
			$subject   = $data['subject'] ?? $template['subject'];
			$body_html = $data['body_html'] ?? $template['body_html'];

			$data['variables'] = array_unique( array_merge(
				$this->placeholderService->findPlaceholders( $subject ),
				$this->placeholderService->findPlaceholders( $body_html )
			) );
		}

		// Plain-Text aktualisieren.
		if ( isset( $data['body_html'] ) && empty( $data['body_text'] ) ) {
			$data['body_text'] = $this->htmlToText( $data['body_html'] );
		}

		$result = $this->repository->update( $id, $data );

		if ( ! $result ) {
			return false;
		}

		return $this->repository->find( $id );
	}

	/**
	 * Template löschen
	 *
	 * @param int $id Template-ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		$template = $this->repository->find( $id );

		if ( ! $template ) {
			return false;
		}

		// System-Templates können nicht gelöscht werden.
		if ( $template['is_system'] ) {
			return false;
		}

		return $this->repository->softDelete( $id );
	}

	/**
	 * Template duplizieren
	 *
	 * @param int    $id       Template-ID.
	 * @param string $new_name Optionaler neuer Name.
	 * @return array|false Neues Template oder false.
	 */
	public function duplicate( int $id, string $new_name = '' ): array|false {
		$new_id = $this->repository->duplicate( $id, $new_name );

		if ( false === $new_id ) {
			return false;
		}

		return $this->repository->find( $new_id );
	}

	/**
	 * Template per ID laden
	 *
	 * @param int $id Template-ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		return $this->repository->find( $id );
	}

	/**
	 * Template per Slug laden
	 *
	 * @param string $slug Template-Slug.
	 * @return array|null
	 */
	public function findBySlug( string $slug ): ?array {
		return $this->repository->findBySlug( $slug );
	}

	/**
	 * Standard-Template für Kategorie laden
	 *
	 * @param string $category Kategorie.
	 * @return array|null
	 */
	public function getDefault( string $category ): ?array {
		return $this->repository->findDefault( $category );
	}

	/**
	 * Alle Templates laden
	 *
	 * @param array $args Query-Argumente.
	 * @return array
	 */
	public function getList( array $args = [] ): array {
		return $this->repository->getList( $args );
	}

	/**
	 * Templates nach Kategorie laden
	 *
	 * @param string $category Kategorie.
	 * @return array
	 */
	public function getByCategory( string $category ): array {
		return $this->repository->findByCategory( $category );
	}

	/**
	 * Template als Standard setzen
	 *
	 * @param int    $id       Template-ID.
	 * @param string $category Kategorie.
	 * @return bool
	 */
	public function setAsDefault( int $id, string $category ): bool {
		global $wpdb;

		$template = $this->repository->find( $id );

		if ( ! $template ) {
			return false;
		}

		// Alle anderen Templates der Kategorie als nicht-default markieren.
		$table = $wpdb->prefix . 'rp_email_templates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			[ 'is_default' => 0 ],
			[ 'category' => $category ],
			[ '%d' ],
			[ '%s' ]
		);

		// Dieses Template als default setzen.
		return $this->repository->update( $id, [ 'is_default' => 1 ] );
	}

	/**
	 * Template rendern mit Kontext
	 *
	 * @param int   $template_id Template-ID.
	 * @param array $context     Kontext-Daten.
	 * @return array{subject: string, body_html: string, body_text: string}|null
	 */
	public function render( int $template_id, array $context ): ?array {
		$template = $this->repository->find( $template_id );

		if ( ! $template ) {
			return null;
		}

		return $this->renderTemplate( $template, $context );
	}

	/**
	 * Template per Slug rendern
	 *
	 * @param string $slug    Template-Slug.
	 * @param array  $context Kontext-Daten.
	 * @return array{subject: string, body_html: string, body_text: string}|null
	 */
	public function renderBySlug( string $slug, array $context ): ?array {
		$template = $this->repository->findBySlug( $slug );

		if ( ! $template ) {
			return null;
		}

		return $this->renderTemplate( $template, $context );
	}

	/**
	 * Template-Vorschau generieren
	 *
	 * @param int $template_id Template-ID.
	 * @return array{subject: string, body_html: string}|null
	 */
	public function preview( int $template_id ): ?array {
		$template = $this->repository->find( $template_id );

		if ( ! $template ) {
			return null;
		}

		return [
			'subject'   => $this->placeholderService->renderPreview( $template['subject'] ),
			'body_html' => $this->placeholderService->renderPreview( $template['body_html'] ),
		];
	}

	/**
	 * Template auf Standard zurücksetzen
	 *
	 * Setzt ein Template auf den Standardinhalt zurück, wenn ein
	 * passendes System-Template existiert. Funktioniert auch für
	 * Templates die noch nicht als is_system markiert sind.
	 *
	 * @param int $id Template-ID.
	 * @return bool
	 */
	public function resetToDefault( int $id ): bool {
		$template = $this->repository->find( $id );

		if ( ! $template ) {
			return false;
		}

		// Original-Template aus Migrator laden (per Slug).
		$defaults = $this->getDefaultTemplateContent( $template['slug'] ?? '' );

		if ( ! $defaults ) {
			return false;
		}

		// Template zurücksetzen und als System-Template markieren.
		$defaults['is_system'] = 1;

		return $this->repository->update( $id, $defaults );
	}

	/**
	 * Verfügbare Template-Kategorien
	 *
	 * @return array
	 */
	public function getCategories(): array {
		return [
			'application' => [
				'label'       => __( 'Application', 'recruiting-playbook' ),
				'description' => __( 'Templates for application process', 'recruiting-playbook' ),
			],
			'interview'   => [
				'label'       => __( 'Interview', 'recruiting-playbook' ),
				'description' => __( 'Templates for interview invitations', 'recruiting-playbook' ),
			],
			'offer'       => [
				'label'       => __( 'Offer', 'recruiting-playbook' ),
				'description' => __( 'Templates for job offers', 'recruiting-playbook' ),
			],
			'custom'      => [
				'label'       => __( 'Custom', 'recruiting-playbook' ),
				'description' => __( 'Custom templates', 'recruiting-playbook' ),
			],
		];
	}

	/**
	 * Template rendern
	 *
	 * @param array $template Template-Daten.
	 * @param array $context  Kontext-Daten.
	 * @return array{subject: string, body_html: string, body_text: string}
	 */
	private function renderTemplate( array $template, array $context ): array {
		$subject   = $this->placeholderService->replace( $template['subject'], $context );
		$body_html = $this->placeholderService->replace( $template['body_html'], $context );
		$body_text = ! empty( $template['body_text'] )
			? $this->placeholderService->replace( $template['body_text'], $context )
			: $this->htmlToText( $body_html );

		// E-Mail-Wrapper anwenden.
		$body_html = $this->wrapHtml( $body_html );

		return [
			'subject'   => $subject,
			'body_html' => $body_html,
			'body_text' => $body_text,
		];
	}

	/**
	 * HTML zu Plain-Text konvertieren
	 *
	 * @param string $html HTML-Inhalt.
	 * @return string
	 */
	private function htmlToText( string $html ): string {
		// Zeilenumbrüche für Block-Elemente.
		$html = preg_replace( '/<\/(p|div|h[1-6]|tr|li)>/i', "\n", $html );
		$html = preg_replace( '/<br\s*\/?>/i', "\n", $html );
		$html = preg_replace( '/<\/td>/i', "\t", $html );

		// Links beibehalten.
		$html = preg_replace( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', '$2 ($1)', $html );

		// HTML-Tags entfernen.
		$text = wp_strip_all_tags( $html );

		// Mehrfache Leerzeilen reduzieren.
		$text = preg_replace( '/\n{3,}/', "\n\n", $text );

		return trim( $text );
	}

	/**
	 * HTML-Wrapper für E-Mails
	 *
	 * @param string $content Inhalt.
	 * @return string
	 */
	private function wrapHtml( string $content ): string {
		$settings     = get_option( 'rp_settings', [] );
		$company_name = $settings['company_name'] ?? get_bloginfo( 'name' );

		// Pro-Feature: Branding ausblenden.
		$hide_branding   = ! empty( $settings['hide_email_branding'] ) && function_exists( 'rp_can' ) && rp_can( 'custom_branding' );
		$recruiting_url  = 'https://recruiting-playbook.de';

		$styles = '
			body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
			.container { max-width: 600px; margin: 0 auto; padding: 20px; }
			.header { border-bottom: 2px solid #2271b1; padding-bottom: 20px; margin-bottom: 20px; }
			.content { padding: 20px 0; }
			.footer { padding-top: 20px; margin-top: 30px; font-size: 12px; color: #6b7280; }
			a { color: #2271b1; }
			.btn { display: inline-block; padding: 12px 24px; background: #2271b1; color: #fff !important; text-decoration: none; border-radius: 4px; }
			table { border-collapse: collapse; }
			td { padding: 8px; }
		';

		// Footer-Inhalt erstellen (nur Branding-Zeile wenn sichtbar).
		$footer_content = '';
		if ( ! $hide_branding ) {
			$footer_content = sprintf(
				'<p style="margin: 0; color: #adb5bd; font-size: 11px;">%s</p>',
				sprintf(
					/* translators: %s: Link to Recruiting Playbook website */
					esc_html__( 'Sent via %s', 'recruiting-playbook' ),
					'<a href="' . esc_url( $recruiting_url ) . '" style="color: #adb5bd; text-decoration: underline;">Recruiting Playbook</a>'
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
			esc_html( $company_name ),
			$content,
			$footer_content
		);
	}

	/**
	 * Template validieren
	 *
	 * @param array    $data       Template-Daten.
	 * @param int|null $exclude_id Auszuschließende ID (für Updates).
	 * @return true|\WP_Error
	 */
	private function validate( array $data, ?int $exclude_id = null ): true|\WP_Error {
		$errors = new \WP_Error();

		// Name erforderlich.
		if ( empty( $data['name'] ) ) {
			$errors->add( 'name_required', __( 'Name is required.', 'recruiting-playbook' ) );
		}

		// Subject erforderlich.
		if ( empty( $data['subject'] ) ) {
			$errors->add( 'subject_required', __( 'Subject is required.', 'recruiting-playbook' ) );
		}

		// Body erforderlich.
		if ( empty( $data['body_html'] ) ) {
			$errors->add( 'body_required', __( 'Content is required.', 'recruiting-playbook' ) );
		}

		// Slug-Eindeutigkeit prüfen.
		if ( ! empty( $data['slug'] ) ) {
			if ( $this->repository->slugExists( $data['slug'], $exclude_id ) ) {
				$errors->add( 'slug_exists', __( 'This slug already exists.', 'recruiting-playbook' ) );
			}
		}

		// Kategorie validieren.
		$valid_categories = array_keys( $this->getCategories() );
		if ( ! empty( $data['category'] ) && ! in_array( $data['category'], $valid_categories, true ) ) {
			$errors->add( 'invalid_category', __( 'Invalid category.', 'recruiting-playbook' ) );
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Standard-Template-Inhalte für Reset
	 *
	 * Templates enthalten keine Signaturen - diese werden beim Versand angehängt.
	 * Muss mit Migrator::getDefaultTemplates() synchron gehalten werden.
	 *
	 * @param string $slug Template-Slug.
	 * @return array|null
	 */
	private function getDefaultTemplateContent( string $slug ): ?array {
		$defaults = [
			// === AUTOMATISIERBARE TEMPLATES ===
			'application-confirmation' => [
				'subject'   => __( 'Your application at {firma}: {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>thank you for your application as <strong>{stelle}</strong> at {firma}!</p>

<p>We have received your documents and will review them carefully. You will receive feedback from us as soon as we have reviewed your application.</p>

<p><strong>Your application overview:</strong></p>
<ul>
<li>Position: {stelle}</li>
<li>Received on: {bewerbung_datum}</li>
<li>Reference number: {bewerbung_id}</li>
</ul>

<p>If you have any questions, please do not hesitate to contact us.</p>',
			],
			'rejection-standard'       => [
				'subject'   => __( 'Your application as {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>thank you for your interest in the position <strong>{stelle}</strong> and the time you invested in your application.</p>

<p>After careful consideration, we regret to inform you that we have decided to proceed with other candidates whose profile better matches our current requirements.</p>

<p>This decision is not an evaluation of your qualifications. We encourage you to apply again for suitable future job openings.</p>

<p>We wish you all the best and much success for your future career.</p>',
			],
			'application-withdrawn'    => [
				'subject'   => __( 'Confirmation: Application withdrawn', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>we confirm receipt of your message that you would like to withdraw your application as <strong>{stelle}</strong>.</p>

<p>We have removed your application from our system accordingly and will delete your documents in accordance with the applicable data protection regulations.</p>

<p>We regret your decision, but of course we respect it. Should you be interested in working with {firma} in the future, we would be happy to receive a new application.</p>

<p>We wish you all the best for your further career path!</p>',
			],
			'talent-pool-added'        => [
				'subject'   => __( 'Welcome to the talent pool of {firma}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>thank you for your interest in {firma}!</p>

<p>Although we currently do not have a suitable position for you, we were impressed by your profile. With your consent, we have therefore added you to our talent pool.</p>

<p><strong>What does this mean for you?</strong></p>
<ul>
<li>We will contact you as soon as a position matching your profile becomes available</li>
<li>You will receive exclusive information about new career opportunities</li>
<li>Your data will be treated confidentially in accordance with our privacy policy</li>
</ul>

<p>You can update your profile at any time or revoke your inclusion in the talent pool.</p>

<p>We look forward to a possible future collaboration!</p>',
			],

			// === MANUELLE TEMPLATES (mit ____ Lücken) ===
			'interview-invitation'     => [
				'subject'   => __( 'Interview invitation: {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>we are pleased to inform you that we were impressed by your application as <strong>{stelle}</strong>. We would like to meet you in person.</p>

<p><strong>Proposed date:</strong></p>
<table style="border-collapse: collapse; margin: 16px 0;">
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Date:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Time:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Location:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Interviewer:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
</table>

<p>Please confirm the appointment or let us know if you need an alternative date.</p>

<p><strong>Please bring:</strong></p>
<ul>
<li>Valid ID card</li>
<li>Current certificates (if not already submitted)</li>
</ul>

<p>We look forward to talking to you!</p>',
			],
			'interview-reminder'       => [
				'subject'   => __( 'Reminder: Your interview on ____', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>we would like to remind you of your upcoming interview for the position <strong>{stelle}</strong>.</p>

<p><strong>Appointment:</strong></p>
<table style="border-collapse: collapse; margin: 16px 0;">
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Date:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Time:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Location:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
</table>

<p><strong>Please remember:</strong></p>
<ul>
<li>Bring a valid ID card</li>
<li>Arrive on time</li>
</ul>

<p>If you cannot attend the appointment, please cancel in time.</p>

<p>We look forward to talking to you!</p>',
			],
			'offer-letter'             => [
				'subject'   => __( 'Job offer: {stelle} at {firma}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>we are very pleased to be able to offer you the position <strong>{stelle}</strong> following our positive discussions!</p>

<p><strong>Key details of the offer:</strong></p>
<ul>
<li>Position: {stelle}</li>
<li>Start date: ____</li>
<li>Contract type: ____</li>
<li>Working hours: ____</li>
</ul>

<p>You will receive the detailed contract documents shortly by mail or as a separate attachment.</p>

<p>Please let us know your decision by <strong>____</strong>.</p>

<p>If you have any questions, please do not hesitate to contact us.</p>',
			],
			'contract-sent'            => [
				'subject'   => __( 'Your contract documents for {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>as discussed, we are sending you the contract documents for your employment as <strong>{stelle}</strong> at {firma}.</p>

<p><strong>The documents include:</strong></p>
<ul>
<li>Employment contract (2 copies)</li>
<li>____</li>
</ul>

<p><strong>Next steps:</strong></p>
<ol>
<li>Please review the documents carefully</li>
<li>Sign both copies of the contract</li>
<li>Return one signed copy to us by <strong>____</strong></li>
</ol>

<p>If you have any questions about the documents, please do not hesitate to contact us.</p>

<p>We look forward to working together!</p>',
			],
			'talent-pool-matching-job' => [
				'subject'   => __( 'New position at {firma}: {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>you are in our talent pool, and we have found a position that might match your profile!</p>

<p><strong>The position:</strong></p>
<ul>
<li><strong>{stelle}</strong></li>
<li>Location: {stelle_ort}</li>
</ul>

<p>We think this position could be interesting for you based on your qualifications and experience.</p>

<p><strong>Interested?</strong></p>
<p>If you are interested in this position, simply reply to this email or apply directly via our career page:</p>
<p><a href="{stelle_url}">{stelle_url}</a></p>

<p>If this position is not right for you, you will remain in our talent pool and we will inform you about future opportunities.</p>

<p>We would be happy to hear from you!</p>',
			],
		];

		return $defaults[ $slug ] ?? null;
	}
}

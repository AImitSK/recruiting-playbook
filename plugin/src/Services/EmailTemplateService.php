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
				'label'       => __( 'Bewerbung', 'recruiting-playbook' ),
				'description' => __( 'Templates für Bewerbungsprozess', 'recruiting-playbook' ),
			],
			'interview'   => [
				'label'       => __( 'Interview', 'recruiting-playbook' ),
				'description' => __( 'Templates für Intervieweinladungen', 'recruiting-playbook' ),
			],
			'offer'       => [
				'label'       => __( 'Angebot', 'recruiting-playbook' ),
				'description' => __( 'Templates für Stellenangebote', 'recruiting-playbook' ),
			],
			'custom'      => [
				'label'       => __( 'Benutzerdefiniert', 'recruiting-playbook' ),
				'description' => __( 'Eigene Templates', 'recruiting-playbook' ),
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
					esc_html__( 'Versand über %s', 'recruiting-playbook' ),
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
			$errors->add( 'name_required', __( 'Name ist erforderlich.', 'recruiting-playbook' ) );
		}

		// Subject erforderlich.
		if ( empty( $data['subject'] ) ) {
			$errors->add( 'subject_required', __( 'Betreff ist erforderlich.', 'recruiting-playbook' ) );
		}

		// Body erforderlich.
		if ( empty( $data['body_html'] ) ) {
			$errors->add( 'body_required', __( 'Inhalt ist erforderlich.', 'recruiting-playbook' ) );
		}

		// Slug-Eindeutigkeit prüfen.
		if ( ! empty( $data['slug'] ) ) {
			if ( $this->repository->slugExists( $data['slug'], $exclude_id ) ) {
				$errors->add( 'slug_exists', __( 'Dieser Slug existiert bereits.', 'recruiting-playbook' ) );
			}
		}

		// Kategorie validieren.
		$valid_categories = array_keys( $this->getCategories() );
		if ( ! empty( $data['category'] ) && ! in_array( $data['category'], $valid_categories, true ) ) {
			$errors->add( 'invalid_category', __( 'Ungültige Kategorie.', 'recruiting-playbook' ) );
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
				'subject'   => __( 'Ihre Bewerbung bei {firma}: {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>vielen Dank für Ihre Bewerbung als <strong>{stelle}</strong> bei {firma}!</p>

<p>Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig prüfen. Sie erhalten von uns Rückmeldung, sobald wir Ihre Bewerbung geprüft haben.</p>

<p><strong>Ihre Bewerbung im Überblick:</strong></p>
<ul>
<li>Position: {stelle}</li>
<li>Eingegangen am: {bewerbung_datum}</li>
<li>Referenznummer: {bewerbung_id}</li>
</ul>

<p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>',
			],
			'rejection-standard'       => [
				'subject'   => __( 'Ihre Bewerbung als {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>vielen Dank für Ihr Interesse an der Position <strong>{stelle}</strong> und die Zeit, die Sie in Ihre Bewerbung investiert haben.</p>

<p>Nach sorgfältiger Prüfung müssen wir Ihnen leider mitteilen, dass wir uns für andere Kandidaten entschieden haben, deren Profil besser zu unseren aktuellen Anforderungen passt.</p>

<p>Diese Entscheidung ist keine Bewertung Ihrer Qualifikation. Wir ermutigen Sie, sich bei passenden zukünftigen Stellenangeboten erneut zu bewerben.</p>

<p>Wir wünschen Ihnen für Ihre weitere berufliche Zukunft alles Gute und viel Erfolg.</p>',
			],
			'application-withdrawn'    => [
				'subject'   => __( 'Bestätigung: Bewerbung zurückgezogen', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>hiermit bestätigen wir den Eingang Ihrer Mitteilung, dass Sie Ihre Bewerbung als <strong>{stelle}</strong> zurückziehen möchten.</p>

<p>Wir haben Ihre Bewerbung entsprechend aus unserem System entfernt und werden Ihre Unterlagen gemäß den geltenden Datenschutzbestimmungen löschen.</p>

<p>Wir bedauern Ihre Entscheidung, respektieren sie aber selbstverständlich. Sollten Sie in Zukunft Interesse an einer Zusammenarbeit mit {firma} haben, freuen wir uns über eine erneute Bewerbung.</p>

<p>Wir wünschen Ihnen für Ihren weiteren beruflichen Weg alles Gute!</p>',
			],
			'talent-pool-added'        => [
				'subject'   => __( 'Willkommen im Talent-Pool von {firma}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>vielen Dank für Ihr Interesse an {firma}!</p>

<p>Obwohl wir aktuell keine passende Position für Sie haben, waren wir von Ihrem Profil überzeugt. Mit Ihrer Zustimmung haben wir Sie daher in unseren Talent-Pool aufgenommen.</p>

<p><strong>Was bedeutet das für Sie?</strong></p>
<ul>
<li>Wir werden Sie kontaktieren, sobald eine zu Ihrem Profil passende Stelle frei wird</li>
<li>Sie erhalten exklusive Informationen über neue Karrieremöglichkeiten</li>
<li>Ihre Daten werden gemäß unseren Datenschutzrichtlinien vertraulich behandelt</li>
</ul>

<p>Sie können Ihr Profil jederzeit aktualisieren oder die Aufnahme im Talent-Pool widerrufen.</p>

<p>Wir freuen uns auf eine mögliche zukünftige Zusammenarbeit!</p>',
			],

			// === MANUELLE TEMPLATES (mit ____ Lücken) ===
			'interview-invitation'     => [
				'subject'   => __( 'Einladung zum Vorstellungsgespräch: {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>wir freuen uns, Ihnen mitteilen zu können, dass uns Ihre Bewerbung als <strong>{stelle}</strong> überzeugt hat. Gerne möchten wir Sie persönlich kennenlernen.</p>

<p><strong>Terminvorschlag:</strong></p>
<table style="border-collapse: collapse; margin: 16px 0;">
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Datum:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Uhrzeit:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Ort:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Gesprächspartner:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
</table>

<p>Bitte bestätigen Sie uns den Termin oder teilen Sie uns mit, falls Sie einen alternativen Termin benötigen.</p>

<p><strong>Bitte bringen Sie mit:</strong></p>
<ul>
<li>Gültigen Personalausweis</li>
<li>Aktuelle Zeugnisse (falls noch nicht eingereicht)</li>
</ul>

<p>Wir freuen uns auf das Gespräch mit Ihnen!</p>',
			],
			'interview-reminder'       => [
				'subject'   => __( 'Erinnerung: Ihr Vorstellungsgespräch am ____', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>wir möchten Sie an Ihr bevorstehendes Vorstellungsgespräch für die Position <strong>{stelle}</strong> erinnern.</p>

<p><strong>Termin:</strong></p>
<table style="border-collapse: collapse; margin: 16px 0;">
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Datum:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Uhrzeit:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Ort:</strong></td>
<td style="padding: 8px 0;">____</td>
</tr>
</table>

<p><strong>Bitte denken Sie daran:</strong></p>
<ul>
<li>Gültigen Personalausweis mitbringen</li>
<li>Pünktlich erscheinen</li>
</ul>

<p>Falls Sie den Termin nicht wahrnehmen können, bitten wir um rechtzeitige Absage.</p>

<p>Wir freuen uns auf das Gespräch mit Ihnen!</p>',
			],
			'offer-letter'             => [
				'subject'   => __( 'Stellenangebot: {stelle} bei {firma}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>wir freuen uns sehr, Ihnen nach den positiven Gesprächen ein Angebot für die Position <strong>{stelle}</strong> unterbreiten zu können!</p>

<p><strong>Eckdaten des Angebots:</strong></p>
<ul>
<li>Position: {stelle}</li>
<li>Startdatum: ____</li>
<li>Vertragsart: ____</li>
<li>Arbeitszeit: ____</li>
</ul>

<p>Die detaillierten Vertragsunterlagen erhalten Sie in Kürze per Post oder als separaten Anhang.</p>

<p>Bitte teilen Sie uns Ihre Entscheidung bis zum <strong>____</strong> mit.</p>

<p>Für Rückfragen stehen wir Ihnen selbstverständlich gerne zur Verfügung.</p>',
			],
			'contract-sent'            => [
				'subject'   => __( 'Ihre Vertragsunterlagen für {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>wie besprochen, übersenden wir Ihnen anbei die Vertragsunterlagen für Ihre Anstellung als <strong>{stelle}</strong> bei {firma}.</p>

<p><strong>Die Unterlagen umfassen:</strong></p>
<ul>
<li>Arbeitsvertrag (2 Exemplare)</li>
<li>____</li>
</ul>

<p><strong>Nächste Schritte:</strong></p>
<ol>
<li>Bitte prüfen Sie die Unterlagen sorgfältig</li>
<li>Unterschreiben Sie beide Vertragsexemplare</li>
<li>Senden Sie ein unterschriebenes Exemplar bis zum <strong>____</strong> an uns zurück</li>
</ol>

<p>Sollten Sie Fragen zu den Unterlagen haben, stehen wir Ihnen gerne zur Verfügung.</p>

<p>Wir freuen uns auf die Zusammenarbeit!</p>',
			],
			'talent-pool-matching-job' => [
				'subject'   => __( 'Neue Stelle bei {firma}: {stelle}', 'recruiting-playbook' ),
				'body_html' => '<p>{anrede_formal},</p>

<p>Sie befinden sich in unserem Talent-Pool, und wir haben eine Stelle gefunden, die zu Ihrem Profil passen könnte!</p>

<p><strong>Die Position:</strong></p>
<ul>
<li><strong>{stelle}</strong></li>
<li>Standort: {stelle_ort}</li>
</ul>

<p>Wir denken, dass diese Stelle aufgrund Ihrer Qualifikationen und Erfahrungen interessant für Sie sein könnte.</p>

<p><strong>Interesse?</strong></p>
<p>Wenn Sie an dieser Position interessiert sind, antworten Sie einfach auf diese E-Mail oder bewerben Sie sich direkt über unsere Karriereseite:</p>
<p><a href="{stelle_url}">{stelle_url}</a></p>

<p>Falls die Stelle nicht das Richtige für Sie ist, bleiben Sie weiterhin in unserem Talent-Pool und wir informieren Sie über zukünftige Möglichkeiten.</p>

<p>Wir würden uns freuen, von Ihnen zu hören!</p>',
			],
		];

		return $defaults[ $slug ] ?? null;
	}
}

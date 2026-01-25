<?php
/**
 * Placeholder Service - Platzhalter-Ersetzung für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service für Platzhalter-Ersetzung in E-Mail-Templates
 */
class PlaceholderService {

	/**
	 * Platzhalter-Gruppen mit Labels
	 */
	private const GROUPS = [
		'candidate'   => 'Kandidat',
		'application' => 'Bewerbung',
		'job'         => 'Stelle',
		'company'     => 'Firma',
		'sender'      => 'Absender',
		'interview'   => 'Interview',
		'offer'       => 'Angebot',
		'contact'     => 'Kontakt',
	];

	/**
	 * Platzhalter-Definitionen
	 *
	 * @var array
	 */
	private array $definitions;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->definitions = $this->getDefinitions();
	}

	/**
	 * Platzhalter in Text ersetzen
	 *
	 * @param string $text    Text mit Platzhaltern.
	 * @param array  $context Kontext-Daten (application, candidate, job, custom).
	 * @return string
	 */
	public function replace( string $text, array $context ): string {
		$placeholders = $this->resolve( $context );

		foreach ( $placeholders as $key => $value ) {
			$text = str_replace( "{{$key}}", $value, $text );
		}

		// Unbekannte Platzhalter entfernen.
		$text = preg_replace( '/\{[a-z_]+\}/', '', $text );

		return $text;
	}

	/**
	 * Alle Platzhalter für Kontext auflösen
	 *
	 * @param array $context Kontext-Daten.
	 * @return array<string, string>
	 */
	public function resolve( array $context ): array {
		$values = [];

		$application = $context['application'] ?? [];
		$candidate   = $context['candidate'] ?? [];
		$job         = $context['job'] ?? [];
		$custom      = $context['custom'] ?? [];
		$settings    = get_option( 'rp_settings', [] );

		// Kandidaten-Platzhalter.
		$values['vorname']       = $candidate['first_name'] ?? '';
		$values['nachname']      = $candidate['last_name'] ?? '';
		$values['name']          = trim( $values['vorname'] . ' ' . $values['nachname'] );
		$values['email']         = $candidate['email'] ?? '';
		$values['telefon']       = $candidate['phone'] ?? '';
		$values['anrede']        = $this->getAnrede( $candidate );
		$values['anrede_formal'] = $this->getAnredeFormal( $candidate );

		// Bewerbungs-Platzhalter.
		$values['bewerbung_id']     = $this->formatApplicationId( (int) ( $application['id'] ?? 0 ) );
		$values['bewerbung_datum']  = $this->formatDate( $application['created_at'] ?? '' );
		$values['bewerbung_status'] = $this->translateStatus( $application['status'] ?? '' );

		// Stellen-Platzhalter.
		$values['stelle']     = $job['title'] ?? '';
		$values['stelle_ort'] = $job['location'] ?? '';
		$values['stelle_typ'] = $job['employment_type'] ?? '';
		$values['stelle_url'] = $job['url'] ?? '';

		// Firmen-Platzhalter.
		$values['firma']         = $settings['company_name'] ?? get_bloginfo( 'name' );
		$values['firma_adresse'] = $settings['company_address'] ?? '';
		$values['firma_website'] = home_url();

		// Absender-Platzhalter.
		$current_user             = wp_get_current_user();
		$values['absender_name']  = $current_user->display_name ?? '';
		$values['absender_email'] = $current_user->user_email ?? '';
		$values['absender_telefon']  = get_user_meta( $current_user->ID, 'phone', true ) ?: '';
		$values['absender_position'] = get_user_meta( $current_user->ID, 'job_title', true ) ?: '';

		// Kontakt-Platzhalter.
		$values['kontakt_name']    = $settings['contact_name'] ?? $values['absender_name'];
		$values['kontakt_email']   = $settings['notification_email'] ?? get_option( 'admin_email' );
		$values['kontakt_telefon'] = $settings['company_phone'] ?? '';

		// Interview-Platzhalter (aus custom).
		$values['termin_datum']      = $custom['termin_datum'] ?? '';
		$values['termin_uhrzeit']    = $custom['termin_uhrzeit'] ?? '';
		$values['termin_ort']        = $custom['termin_ort'] ?? '';
		$values['termin_teilnehmer'] = $custom['termin_teilnehmer'] ?? '';
		$values['termin_dauer']      = $custom['termin_dauer'] ?? '';

		// Angebots-Platzhalter (aus custom).
		$values['start_datum']   = $custom['start_datum'] ?? '';
		$values['vertragsart']   = $custom['vertragsart'] ?? '';
		$values['arbeitszeit']   = $custom['arbeitszeit'] ?? '';
		$values['antwort_frist'] = $custom['antwort_frist'] ?? '';

		// Custom-Platzhalter überschreiben.
		foreach ( $custom as $key => $value ) {
			if ( ! isset( $values[ $key ] ) ) {
				$values[ $key ] = $value;
			}
		}

		return array_map( 'strval', $values );
	}

	/**
	 * Alle verfügbaren Platzhalter mit Definitionen abrufen
	 *
	 * @return array
	 */
	public function getAvailablePlaceholders(): array {
		return $this->definitions;
	}

	/**
	 * Platzhalter nach Gruppen gruppiert abrufen
	 *
	 * @return array
	 */
	public function getPlaceholdersByGroup(): array {
		$grouped = [];

		foreach ( self::GROUPS as $key => $label ) {
			$grouped[ $key ] = [
				'label'        => __( $label, 'recruiting-playbook' ),
				'placeholders' => [],
			];
		}

		foreach ( $this->definitions as $placeholder => $definition ) {
			$group = $definition['group'] ?? 'custom';
			if ( isset( $grouped[ $group ] ) ) {
				$grouped[ $group ]['placeholders'][ $placeholder ] = $definition;
			}
		}

		// Leere Gruppen entfernen.
		return array_filter( $grouped, fn( $g ) => ! empty( $g['placeholders'] ) );
	}

	/**
	 * Vorschau-Werte für Platzhalter generieren
	 *
	 * @return array<string, string>
	 */
	public function getPreviewValues(): array {
		return [
			'vorname'            => 'Max',
			'nachname'           => 'Mustermann',
			'name'               => 'Max Mustermann',
			'email'              => 'max.mustermann@example.com',
			'telefon'            => '+49 123 456789',
			'anrede'             => 'Herr',
			'anrede_formal'      => 'Sehr geehrter Herr Mustermann',
			'bewerbung_id'       => '#2025-0042',
			'bewerbung_datum'    => date_i18n( get_option( 'date_format' ) ),
			'bewerbung_status'   => __( 'In Prüfung', 'recruiting-playbook' ),
			'stelle'             => 'Senior PHP Developer',
			'stelle_ort'         => 'Berlin',
			'stelle_typ'         => 'Vollzeit',
			'stelle_url'         => home_url( '/jobs/senior-php-developer/' ),
			'firma'              => get_option( 'rp_settings', [] )['company_name'] ?? get_bloginfo( 'name' ),
			'firma_adresse'      => 'Musterstraße 1, 12345 Berlin',
			'firma_website'      => home_url(),
			'absender_name'      => wp_get_current_user()->display_name ?? 'HR Team',
			'absender_email'     => wp_get_current_user()->user_email ?? get_option( 'admin_email' ),
			'absender_telefon'   => '+49 30 12345-67',
			'absender_position'  => 'HR Manager',
			'kontakt_name'       => 'Maria Schmidt',
			'kontakt_email'      => get_option( 'admin_email' ),
			'kontakt_telefon'    => '+49 30 12345-0',
			'termin_datum'       => date_i18n( get_option( 'date_format' ), strtotime( '+7 days' ) ),
			'termin_uhrzeit'     => '14:00 Uhr',
			'termin_ort'         => 'Hauptgebäude, Raum 302',
			'termin_teilnehmer'  => 'Herr Müller (Abteilungsleiter)',
			'termin_dauer'       => 'ca. 60 Minuten',
			'start_datum'        => date_i18n( get_option( 'date_format' ), strtotime( '+1 month' ) ),
			'vertragsart'        => 'Unbefristet',
			'arbeitszeit'        => '40 Stunden/Woche',
			'antwort_frist'      => date_i18n( get_option( 'date_format' ), strtotime( '+14 days' ) ),
		];
	}

	/**
	 * Text mit Vorschau-Werten rendern
	 *
	 * @param string $text Text mit Platzhaltern.
	 * @return string
	 */
	public function renderPreview( string $text ): string {
		$preview_values = $this->getPreviewValues();

		foreach ( $preview_values as $key => $value ) {
			$text = str_replace( "{{$key}}", $value, $text );
		}

		return $text;
	}

	/**
	 * Platzhalter im Text finden
	 *
	 * @param string $text Text mit Platzhaltern.
	 * @return array Liste der gefundenen Platzhalter.
	 */
	public function findPlaceholders( string $text ): array {
		preg_match_all( '/\{([a-z_]+)\}/', $text, $matches );
		return array_unique( $matches[1] ?? [] );
	}

	/**
	 * Prüfen ob Platzhalter gültig ist
	 *
	 * @param string $placeholder Platzhalter-Name.
	 * @return bool
	 */
	public function isValidPlaceholder( string $placeholder ): bool {
		return isset( $this->definitions[ $placeholder ] );
	}

	/**
	 * Anrede generieren
	 *
	 * @param array $candidate Kandidaten-Daten.
	 * @return string
	 */
	private function getAnrede( array $candidate ): string {
		$salutation = $candidate['salutation'] ?? '';

		switch ( strtolower( $salutation ) ) {
			case 'herr':
			case 'mr':
			case 'mr.':
				return __( 'Herr', 'recruiting-playbook' );
			case 'frau':
			case 'mrs':
			case 'mrs.':
			case 'ms':
			case 'ms.':
				return __( 'Frau', 'recruiting-playbook' );
			default:
				return '';
		}
	}

	/**
	 * Formelle Anrede generieren
	 *
	 * @param array $candidate Kandidaten-Daten.
	 * @return string
	 */
	private function getAnredeFormal( array $candidate ): string {
		$salutation = $candidate['salutation'] ?? '';
		$last_name  = $candidate['last_name'] ?? '';

		if ( empty( $last_name ) ) {
			return __( 'Guten Tag', 'recruiting-playbook' );
		}

		switch ( strtolower( $salutation ) ) {
			case 'herr':
			case 'mr':
			case 'mr.':
				return sprintf( __( 'Sehr geehrter Herr %s', 'recruiting-playbook' ), $last_name );
			case 'frau':
			case 'mrs':
			case 'mrs.':
			case 'ms':
			case 'ms.':
				return sprintf( __( 'Sehr geehrte Frau %s', 'recruiting-playbook' ), $last_name );
			default:
				return sprintf( __( 'Guten Tag %s', 'recruiting-playbook' ), $last_name );
		}
	}

	/**
	 * Bewerbungs-ID formatieren
	 *
	 * @param int $id Bewerbungs-ID.
	 * @return string
	 */
	private function formatApplicationId( int $id ): string {
		if ( 0 === $id ) {
			return '';
		}
		return sprintf( '#%s-%04d', gmdate( 'Y' ), $id );
	}

	/**
	 * Datum formatieren
	 *
	 * @param string $date Datum.
	 * @return string
	 */
	private function formatDate( string $date ): string {
		if ( empty( $date ) ) {
			return '';
		}
		return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
	}

	/**
	 * Status übersetzen
	 *
	 * @param string $status Status-Key.
	 * @return string
	 */
	private function translateStatus( string $status ): string {
		$statuses = [
			'new'       => __( 'Neu', 'recruiting-playbook' ),
			'screening' => __( 'In Prüfung', 'recruiting-playbook' ),
			'interview' => __( 'Interview', 'recruiting-playbook' ),
			'offer'     => __( 'Angebot', 'recruiting-playbook' ),
			'hired'     => __( 'Eingestellt', 'recruiting-playbook' ),
			'rejected'  => __( 'Abgesagt', 'recruiting-playbook' ),
			'withdrawn' => __( 'Zurückgezogen', 'recruiting-playbook' ),
		];

		return $statuses[ $status ] ?? $status;
	}

	/**
	 * Platzhalter-Definitionen
	 *
	 * @return array
	 */
	private function getDefinitions(): array {
		return [
			// Kandidat.
			'anrede'             => [
				'label'       => __( 'Anrede', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Herr / Frau', 'recruiting-playbook' ),
			],
			'anrede_formal'      => [
				'label'       => __( 'Formelle Anrede', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Sehr geehrter Herr Mustermann', 'recruiting-playbook' ),
			],
			'vorname'            => [
				'label'       => __( 'Vorname', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Vorname des Kandidaten', 'recruiting-playbook' ),
			],
			'nachname'           => [
				'label'       => __( 'Nachname', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Nachname des Kandidaten', 'recruiting-playbook' ),
			],
			'name'               => [
				'label'       => __( 'Vollständiger Name', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Vor- und Nachname', 'recruiting-playbook' ),
			],
			'email'              => [
				'label'       => __( 'E-Mail', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'E-Mail-Adresse des Kandidaten', 'recruiting-playbook' ),
			],
			'telefon'            => [
				'label'       => __( 'Telefon', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Telefonnummer des Kandidaten', 'recruiting-playbook' ),
			],

			// Bewerbung.
			'bewerbung_id'       => [
				'label'       => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
				'group'       => 'application',
				'description' => __( 'Referenznummer der Bewerbung', 'recruiting-playbook' ),
			],
			'bewerbung_datum'    => [
				'label'       => __( 'Bewerbungsdatum', 'recruiting-playbook' ),
				'group'       => 'application',
				'description' => __( 'Eingangsdatum der Bewerbung', 'recruiting-playbook' ),
			],
			'bewerbung_status'   => [
				'label'       => __( 'Bewerbungsstatus', 'recruiting-playbook' ),
				'group'       => 'application',
				'description' => __( 'Aktueller Status der Bewerbung', 'recruiting-playbook' ),
			],

			// Stelle.
			'stelle'             => [
				'label'       => __( 'Stellentitel', 'recruiting-playbook' ),
				'group'       => 'job',
				'description' => __( 'Titel der Stelle', 'recruiting-playbook' ),
			],
			'stelle_ort'         => [
				'label'       => __( 'Arbeitsort', 'recruiting-playbook' ),
				'group'       => 'job',
				'description' => __( 'Standort der Stelle', 'recruiting-playbook' ),
			],
			'stelle_typ'         => [
				'label'       => __( 'Beschäftigungsart', 'recruiting-playbook' ),
				'group'       => 'job',
				'description' => __( 'Vollzeit, Teilzeit, etc.', 'recruiting-playbook' ),
			],
			'stelle_url'         => [
				'label'       => __( 'Stellen-URL', 'recruiting-playbook' ),
				'group'       => 'job',
				'description' => __( 'Link zur Stellenanzeige', 'recruiting-playbook' ),
			],

			// Firma.
			'firma'              => [
				'label'       => __( 'Firmenname', 'recruiting-playbook' ),
				'group'       => 'company',
				'description' => __( 'Name des Unternehmens', 'recruiting-playbook' ),
			],
			'firma_adresse'      => [
				'label'       => __( 'Firmenadresse', 'recruiting-playbook' ),
				'group'       => 'company',
				'description' => __( 'Adresse des Unternehmens', 'recruiting-playbook' ),
			],
			'firma_website'      => [
				'label'       => __( 'Firmenwebsite', 'recruiting-playbook' ),
				'group'       => 'company',
				'description' => __( 'Website des Unternehmens', 'recruiting-playbook' ),
			],

			// Absender.
			'absender_name'      => [
				'label'       => __( 'Absender Name', 'recruiting-playbook' ),
				'group'       => 'sender',
				'description' => __( 'Name des aktuellen Benutzers', 'recruiting-playbook' ),
			],
			'absender_email'     => [
				'label'       => __( 'Absender E-Mail', 'recruiting-playbook' ),
				'group'       => 'sender',
				'description' => __( 'E-Mail des aktuellen Benutzers', 'recruiting-playbook' ),
			],
			'absender_telefon'   => [
				'label'       => __( 'Absender Telefon', 'recruiting-playbook' ),
				'group'       => 'sender',
				'description' => __( 'Telefon des aktuellen Benutzers', 'recruiting-playbook' ),
			],
			'absender_position'  => [
				'label'       => __( 'Absender Position', 'recruiting-playbook' ),
				'group'       => 'sender',
				'description' => __( 'Position des aktuellen Benutzers', 'recruiting-playbook' ),
			],

			// Kontakt.
			'kontakt_name'       => [
				'label'       => __( 'Kontakt Name', 'recruiting-playbook' ),
				'group'       => 'contact',
				'description' => __( 'Ansprechpartner', 'recruiting-playbook' ),
			],
			'kontakt_email'      => [
				'label'       => __( 'Kontakt E-Mail', 'recruiting-playbook' ),
				'group'       => 'contact',
				'description' => __( 'Kontakt E-Mail-Adresse', 'recruiting-playbook' ),
			],
			'kontakt_telefon'    => [
				'label'       => __( 'Kontakt Telefon', 'recruiting-playbook' ),
				'group'       => 'contact',
				'description' => __( 'Kontakt Telefonnummer', 'recruiting-playbook' ),
			],

			// Interview.
			'termin_datum'       => [
				'label'       => __( 'Termin Datum', 'recruiting-playbook' ),
				'group'       => 'interview',
				'description' => __( 'Datum des Interviews', 'recruiting-playbook' ),
			],
			'termin_uhrzeit'     => [
				'label'       => __( 'Termin Uhrzeit', 'recruiting-playbook' ),
				'group'       => 'interview',
				'description' => __( 'Uhrzeit des Interviews', 'recruiting-playbook' ),
			],
			'termin_ort'         => [
				'label'       => __( 'Termin Ort', 'recruiting-playbook' ),
				'group'       => 'interview',
				'description' => __( 'Ort/Adresse des Interviews', 'recruiting-playbook' ),
			],
			'termin_teilnehmer'  => [
				'label'       => __( 'Gesprächspartner', 'recruiting-playbook' ),
				'group'       => 'interview',
				'description' => __( 'Teilnehmer am Interview', 'recruiting-playbook' ),
			],
			'termin_dauer'       => [
				'label'       => __( 'Termin Dauer', 'recruiting-playbook' ),
				'group'       => 'interview',
				'description' => __( 'Geschätzte Dauer', 'recruiting-playbook' ),
			],

			// Angebot.
			'start_datum'        => [
				'label'       => __( 'Eintrittsdatum', 'recruiting-playbook' ),
				'group'       => 'offer',
				'description' => __( 'Gewünschtes Eintrittsdatum', 'recruiting-playbook' ),
			],
			'vertragsart'        => [
				'label'       => __( 'Vertragsart', 'recruiting-playbook' ),
				'group'       => 'offer',
				'description' => __( 'Befristet/Unbefristet', 'recruiting-playbook' ),
			],
			'arbeitszeit'        => [
				'label'       => __( 'Arbeitszeit', 'recruiting-playbook' ),
				'group'       => 'offer',
				'description' => __( 'Wöchentliche Arbeitszeit', 'recruiting-playbook' ),
			],
			'antwort_frist'      => [
				'label'       => __( 'Antwortfrist', 'recruiting-playbook' ),
				'group'       => 'offer',
				'description' => __( 'Frist für die Rückmeldung', 'recruiting-playbook' ),
			],
		];
	}
}

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
	 *
	 * Nur echte Variablen die aus der Datenbank gefüllt werden.
	 * Pseudo-Variablen (termin_*, absender_*, kontakt_*, Angebot) wurden entfernt.
	 *
	 * @see docs/technical/email-signature-specification.md
	 */
	private const GROUPS = [
		'candidate'   => 'Kandidat',
		'application' => 'Bewerbung',
		'job'         => 'Stelle',
		'company'     => 'Firma',
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
		// SECURITY: Längenbegrenzung (max 50 Zeichen) um ReDoS zu verhindern.
		$text = preg_replace( '/\{[a-z_]{1,50}\}/', '', $text );

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

		// Firmen-Platzhalter (aus flacher rp_settings Struktur).
		$values['firma']         = $settings['company_name'] ?? get_bloginfo( 'name' );
		$values['firma_adresse'] = $this->formatCompanyAddress( $settings );
		$values['firma_website'] = $settings['company_website'] ?? home_url();

		// Custom-Platzhalter überschreiben (für Erweiterbarkeit).
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
	 * Nur echte Variablen die automatisch aus der Datenbank gefüllt werden.
	 *
	 * @return array<string, string>
	 */
	public function getPreviewValues(): array {
		$settings = get_option( 'rp_settings', [] );
		$company  = $settings['company'] ?? [];

		return [
			// Kandidat.
			'anrede'           => 'Herr',
			'anrede_formal'    => 'Sehr geehrter Herr Mustermann',
			'vorname'          => 'Max',
			'nachname'         => 'Mustermann',
			'name'             => 'Max Mustermann',
			'email'            => 'max.mustermann@example.com',
			'telefon'          => '+49 123 456789',

			// Bewerbung.
			'bewerbung_id'     => '#2025-0042',
			'bewerbung_datum'  => date_i18n( get_option( 'date_format' ) ),
			'bewerbung_status' => __( 'In Prüfung', 'recruiting-playbook' ),

			// Stelle.
			'stelle'           => 'Senior PHP Developer',
			'stelle_ort'       => 'Berlin',
			'stelle_typ'       => 'Vollzeit',
			'stelle_url'       => home_url( '/jobs/senior-php-developer/' ),

			// Firma.
			'firma'            => $company['name'] ?? $settings['company_name'] ?? get_bloginfo( 'name' ),
			'firma_adresse'    => 'Musterstraße 1, 12345 Berlin',
			'firma_website'    => $company['website'] ?? home_url(),
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
	 * Template validieren und ungültige Platzhalter zurückgeben
	 *
	 * @param string $template Template-Text.
	 * @return array{valid: bool, invalid: array, found: array}
	 */
	public function validateTemplate( string $template ): array {
		$found   = $this->findPlaceholders( $template );
		$invalid = [];

		foreach ( $found as $placeholder ) {
			if ( ! $this->isValidPlaceholder( $placeholder ) ) {
				$invalid[] = $placeholder;
			}
		}

		return [
			'valid'   => empty( $invalid ),
			'invalid' => $invalid,
			'found'   => $found,
		];
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
	 * Nur echte Variablen die automatisch aus der Datenbank gefüllt werden.
	 * Pseudo-Variablen (termin_*, absender_*, kontakt_*, Angebot) wurden entfernt.
	 *
	 * Für Interview-Einladungen, Angebote etc. verwenden Templates stattdessen
	 * Lücken-Text (___) den der User manuell ausfüllt.
	 *
	 * @see docs/technical/email-signature-specification.md
	 *
	 * @return array
	 */
	private function getDefinitions(): array {
		return [
			// Kandidat (7 Platzhalter).
			'anrede'           => [
				'label'       => __( 'Anrede', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Herr / Frau', 'recruiting-playbook' ),
			],
			'anrede_formal'    => [
				'label'       => __( 'Formelle Anrede', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Sehr geehrter Herr Mustermann', 'recruiting-playbook' ),
			],
			'vorname'          => [
				'label'       => __( 'Vorname', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Vorname des Kandidaten', 'recruiting-playbook' ),
			],
			'nachname'         => [
				'label'       => __( 'Nachname', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Nachname des Kandidaten', 'recruiting-playbook' ),
			],
			'name'             => [
				'label'       => __( 'Vollständiger Name', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Vor- und Nachname', 'recruiting-playbook' ),
			],
			'email'            => [
				'label'       => __( 'E-Mail', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'E-Mail-Adresse des Kandidaten', 'recruiting-playbook' ),
			],
			'telefon'          => [
				'label'       => __( 'Telefon', 'recruiting-playbook' ),
				'group'       => 'candidate',
				'description' => __( 'Telefonnummer des Kandidaten', 'recruiting-playbook' ),
			],

			// Bewerbung (3 Platzhalter).
			'bewerbung_id'     => [
				'label'       => __( 'Bewerbungs-ID', 'recruiting-playbook' ),
				'group'       => 'application',
				'description' => __( 'Referenznummer der Bewerbung', 'recruiting-playbook' ),
			],
			'bewerbung_datum'  => [
				'label'       => __( 'Bewerbungsdatum', 'recruiting-playbook' ),
				'group'       => 'application',
				'description' => __( 'Eingangsdatum der Bewerbung', 'recruiting-playbook' ),
			],
			'bewerbung_status' => [
				'label'       => __( 'Bewerbungsstatus', 'recruiting-playbook' ),
				'group'       => 'application',
				'description' => __( 'Aktueller Status der Bewerbung', 'recruiting-playbook' ),
			],

			// Stelle (4 Platzhalter).
			'stelle'           => [
				'label'       => __( 'Stellentitel', 'recruiting-playbook' ),
				'group'       => 'job',
				'description' => __( 'Titel der Stelle', 'recruiting-playbook' ),
			],
			'stelle_ort'       => [
				'label'       => __( 'Arbeitsort', 'recruiting-playbook' ),
				'group'       => 'job',
				'description' => __( 'Standort der Stelle', 'recruiting-playbook' ),
			],
			'stelle_typ'       => [
				'label'       => __( 'Beschäftigungsart', 'recruiting-playbook' ),
				'group'       => 'job',
				'description' => __( 'Vollzeit, Teilzeit, etc.', 'recruiting-playbook' ),
			],
			'stelle_url'       => [
				'label'       => __( 'Stellen-URL', 'recruiting-playbook' ),
				'group'       => 'job',
				'description' => __( 'Link zur Stellenanzeige', 'recruiting-playbook' ),
			],

			// Firma (3 Platzhalter).
			'firma'            => [
				'label'       => __( 'Firmenname', 'recruiting-playbook' ),
				'group'       => 'company',
				'description' => __( 'Name des Unternehmens', 'recruiting-playbook' ),
			],
			'firma_adresse'    => [
				'label'       => __( 'Firmenadresse', 'recruiting-playbook' ),
				'group'       => 'company',
				'description' => __( 'Adresse des Unternehmens', 'recruiting-playbook' ),
			],
			'firma_website'    => [
				'label'       => __( 'Firmenwebsite', 'recruiting-playbook' ),
				'group'       => 'company',
				'description' => __( 'Website des Unternehmens', 'recruiting-playbook' ),
			],
		];
	}

	/**
	 * Firmenadresse formatieren
	 *
	 * @param array $company Firmendaten aus rp_settings['company'].
	 * @return string Formatierte Adresse.
	 */
	/**
	 * Firmenadresse formatieren
	 *
	 * @param array $settings Plugin-Einstellungen mit company_* Feldern.
	 * @return string Formatierte Adresse.
	 */
	private function formatCompanyAddress( array $settings ): string {
		$parts = [];

		if ( ! empty( $settings['company_street'] ) ) {
			$parts[] = $settings['company_street'];
		}

		$city_parts = [];
		if ( ! empty( $settings['company_zip'] ) ) {
			$city_parts[] = $settings['company_zip'];
		}
		if ( ! empty( $settings['company_city'] ) ) {
			$city_parts[] = $settings['company_city'];
		}
		if ( ! empty( $city_parts ) ) {
			$parts[] = implode( ' ', $city_parts );
		}

		return implode( ', ', $parts );
	}
}

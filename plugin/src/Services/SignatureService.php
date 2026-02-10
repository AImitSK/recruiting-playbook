<?php
/**
 * Signature Service - E-Mail-Signaturen verwalten und rendern
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\SignatureRepository;

/**
 * Service für E-Mail-Signaturen
 */
class SignatureService {

	/**
	 * Signatur Repository
	 *
	 * @var SignatureRepository|null
	 */
	private ?SignatureRepository $repository = null;

	/**
	 * Repository lazy-laden
	 *
	 * @return SignatureRepository
	 */
	private function getRepository(): SignatureRepository {
		if ( null === $this->repository ) {
			$this->repository = new SignatureRepository();
		}
		return $this->repository;
	}

	/**
	 * Signatur rendern
	 *
	 * Rendert eine Signatur mit optionalem Firmenblock.
	 *
	 * @param int $signature_id Signatur-ID.
	 * @return string Gerenderte Signatur (HTML).
	 */
	public function render( int $signature_id ): string {
		$signature = $this->getRepository()->find( $signature_id );

		if ( ! $signature ) {
			return $this->renderMinimalSignature();
		}

		return $this->renderSignatureContent( $signature );
	}

	/**
	 * Signatur mit Fallback-Kette auflösen und rendern
	 *
	 * Fallback-Kette:
	 * 1. Explizit gewählte Signatur (wenn $signature_id gesetzt)
	 * 2. User-Default-Signatur (wenn $user_id gesetzt)
	 * 3. Firmen-Signatur
	 * 4. Minimale Signatur
	 *
	 * @param int|null $signature_id Gewählte Signatur-ID (null = Auto).
	 * @param int|null $user_id      User-ID für Fallback (null = Firmen-Signatur).
	 * @return string Gerenderte Signatur (HTML).
	 */
	public function renderWithFallback( ?int $signature_id = null, ?int $user_id = null ): string {
		// Explizit gewählte Signatur.
		if ( $signature_id ) {
			$signature = $this->getRepository()->find( $signature_id );
			if ( $signature ) {
				return $this->renderSignatureContent( $signature );
			}
		}

		// User-Default-Signatur.
		if ( $user_id ) {
			$signature = $this->getDefaultForUser( $user_id );
			if ( $signature ) {
				return $this->renderSignatureContent( $signature );
			}
		}

		// Firmen-Signatur.
		$company_signature = $this->getRepository()->findCompanyDefault();
		if ( $company_signature ) {
			return $this->renderSignatureContent( $company_signature );
		}

		// Minimale Signatur als letzter Fallback.
		return $this->renderMinimalSignature();
	}

	/**
	 * Firmen-Kontaktdaten-Block rendern (Tel, Web, E-Mail)
	 *
	 * Wird innerhalb der Signatur verwendet, OHNE Trennlinie.
	 *
	 * @return string HTML-Block mit Kontaktdaten.
	 */
	public function renderCompanyContactBlock(): string {
		$company = $this->getCompanyData();

		if ( empty( $company['name'] ) ) {
			return '';
		}

		$html = '<div class="rp-signature-contact" style="margin-top: 30px; font-size: 14px; color: #333;">';

		// Firmenname.
		$html .= '<p style="margin: 0 0 5px 0;"><strong>' . esc_html( $company['name'] ) . '</strong></p>';

		// Telefon.
		if ( ! empty( $company['phone'] ) ) {
			$html .= '<p style="margin: 0 0 2px 0;">Tel.: ' . esc_html( $company['phone'] ) . '</p>';
		}

		// Website.
		if ( ! empty( $company['website'] ) ) {
			$html .= '<p style="margin: 0 0 2px 0;">Web: <a href="' . esc_url( $company['website'] ) . '" style="color: #0073aa;">' . esc_url( $company['website'] ) . '</a></p>';
		}

		// E-Mail.
		if ( ! empty( $company['email'] ) ) {
			$html .= '<p style="margin: 0;">E-Mail: <a href="mailto:' . esc_attr( $company['email'] ) . '" style="color: #0073aa;">' . esc_html( $company['email'] ) . '</a></p>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Firmen-Footer mit Trennlinie rendern
	 *
	 * Format: _______________________________________________________________
	 *         {Firmenname} · {Adresse} · {PLZ Ort}
	 *
	 * Wird am Ende JEDER Signatur angehängt.
	 *
	 * @return string HTML-Block mit Trennlinie und Firmenadresse.
	 */
	public function renderCompanyFooter(): string {
		$company = $this->getCompanyData();

		if ( empty( $company['name'] ) ) {
			return '';
		}

		// Adresszeile zusammenbauen: Firmenname · Straße · PLZ Ort
		$parts = [];
		$parts[] = $company['name'];

		if ( ! empty( $company['street'] ) ) {
			$parts[] = $company['street'];
		}

		$location = trim( ( $company['zip'] ?? '' ) . ' ' . ( $company['city'] ?? '' ) );
		if ( ! empty( $location ) ) {
			$parts[] = $location;
		}

		$address_line = implode( ' · ', array_map( 'esc_html', $parts ) );

		$html = '<div class="rp-signature-footer" style="margin-top: 30px; font-size: 12px; color: #6b7280;">';
		$html .= '<p style="margin: 0; border-top: 1px solid #ccc; padding-top: 15px;">' . $address_line . '</p>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Minimale Signatur rendern (Firmen-Signatur als Fallback)
	 *
	 * Wird verwendet wenn KEINE Signatur als Standard gesetzt ist.
	 * Format:
	 *   Mit freundlichen Grüßen
	 *
	 *   Ihr {Firmenname} Team
	 *
	 *   {Firmenname}
	 *   Tel.: {Telefon}
	 *   Web: {Website}
	 *   E-Mail: {E-Mail}
	 *
	 *   _______________________________________________________________
	 *   {Firmenname} · {Adresse} · {PLZ Ort}
	 *
	 * @return string HTML mit vollständiger Firmen-Signatur.
	 */
	public function renderMinimalSignature(): string {
		$company = $this->getCompanyData();

		// Firmenname bestimmen.
		$company_name = $company['name'] ?? get_bloginfo( 'name' );

		$html = '<div class="rp-signature-minimal" style="margin-top: 35px;">';

		// Grußformel.
		$html .= '<p style="margin: 0;">' . esc_html__( 'Mit freundlichen Grüßen', 'recruiting-playbook' ) . '</p>';

		// "Ihr {Firma} Team".
		$html .= '<p style="margin: 15px 0 0 0;">';
		$html .= sprintf(
			/* translators: %s: Company name */
			esc_html__( 'Ihr %s Team', 'recruiting-playbook' ),
			esc_html( $company_name )
		);
		$html .= '</p>';

		$html .= '</div>';

		// Kontaktdaten-Block.
		$html .= $this->renderCompanyContactBlock();

		// Firmen-Footer mit Trennlinie.
		$html .= $this->renderCompanyFooter();

		return $html;
	}

	/**
	 * Standard-Signatur für User abrufen
	 *
	 * @param int $user_id User-ID.
	 * @return array|null Signatur-Daten oder null.
	 */
	public function getDefaultForUser( int $user_id ): ?array {
		// Erst in User-Meta nachsehen.
		$default_id = get_user_meta( $user_id, 'rp_default_signature_id', true );

		if ( $default_id ) {
			$signature = $this->getRepository()->find( (int) $default_id );
			// Prüfen ob Signatur existiert, dem User gehört UND als Standard markiert ist.
			if ( $signature && (int) $signature['user_id'] === $user_id && ! empty( $signature['is_default'] ) ) {
				return $signature;
			}
		}

		// Fallback: is_default Flag in Tabelle.
		return $this->getRepository()->findDefaultForUser( $user_id );
	}

	/**
	 * Alle verfügbaren Signaturen für User abrufen
	 *
	 * Gibt persönliche Signaturen + Firmen-Signaturen zurück.
	 *
	 * @param int $user_id User-ID.
	 * @return array Array mit Signatur-Optionen.
	 */
	public function getOptionsForUser( int $user_id ): array {
		$options = [];

		// Persönliche Signaturen.
		$personal = $this->getRepository()->findByUser( $user_id );
		foreach ( $personal as $sig ) {
			$options[] = [
				'id'         => $sig['id'],
				'name'       => $sig['name'],
				'type'       => 'personal',
				'is_default' => $sig['is_default'],
			];
		}

		// Firmen-Signaturen (für alle User sichtbar).
		$company = $this->getRepository()->findCompanySignatures();
		foreach ( $company as $sig ) {
			$options[] = [
				'id'         => $sig['id'],
				'name'       => $sig['name'] . ' ' . __( '(Firma)', 'recruiting-playbook' ),
				'type'       => 'company',
				'is_default' => $sig['is_default'],
			];
		}

		// Option für "Keine Signatur".
		$options[] = [
			'id'         => 0,
			'name'       => __( 'Keine Signatur', 'recruiting-playbook' ),
			'type'       => 'none',
			'is_default' => false,
		];

		return $options;
	}

	/**
	 * Signatur erstellen
	 *
	 * @param array $data Signatur-Daten.
	 * @return int|false Insert-ID oder false bei Fehler.
	 */
	public function create( array $data ): int|false {
		$data = $this->sanitizeSignatureData( $data );

		$id = $this->getRepository()->create( $data );

		if ( $id && ! empty( $data['is_default'] ) ) {
			// User-Meta aktualisieren wenn Default.
			$user_id = $data['user_id'] ?? get_current_user_id();
			if ( $user_id ) {
				update_user_meta( $user_id, 'rp_default_signature_id', $id );
			}
		}

		return $id;
	}

	/**
	 * Signatur aktualisieren
	 *
	 * @param int   $id   Signatur-ID.
	 * @param array $data Update-Daten.
	 * @return bool Erfolg.
	 */
	public function update( int $id, array $data ): bool {
		$data = $this->sanitizeSignatureData( $data );

		$result = $this->getRepository()->update( $id, $data );

		if ( $result && ! empty( $data['is_default'] ) ) {
			// User-Meta aktualisieren wenn Default.
			$signature = $this->getRepository()->find( $id );
			if ( $signature && $signature['user_id'] ) {
				update_user_meta( $signature['user_id'], 'rp_default_signature_id', $id );
			}
		}

		return $result;
	}

	/**
	 * Signatur löschen
	 *
	 * @param int $id Signatur-ID.
	 * @return bool Erfolg.
	 */
	public function delete( int $id ): bool {
		$signature = $this->getRepository()->find( $id );

		if ( ! $signature ) {
			return false;
		}

		// User-Meta aufräumen wenn es die Default-Signatur war.
		if ( $signature['user_id'] && $signature['is_default'] ) {
			delete_user_meta( $signature['user_id'], 'rp_default_signature_id' );
		}

		return $this->getRepository()->delete( $id );
	}

	/**
	 * Signatur-Inhalt rendern
	 *
	 * Rendert eine benutzerdefinierte Signatur mit Grußformel, Inhalt
	 * und anschließend IMMER den Firmen-Footer mit Trennlinie.
	 *
	 * Format:
	 *   [Grußformel]
	 *   [Signatur-Inhalt]
	 *
	 *   _______________________________________________________________
	 *   {Firmenname} · {Adresse} · {PLZ Ort}
	 *
	 * @param array $signature Signatur-Daten.
	 * @return string HTML.
	 */
	private function renderSignatureContent( array $signature ): string {
		$html = '<div class="rp-signature" style="margin-top: 20px;">';

		// Grußformel.
		if ( ! empty( $signature['greeting'] ) ) {
			$html .= '<p style="margin: 0 0 10px 0;">' . esc_html( $signature['greeting'] ) . '</p>';
		}

		// Signatur-Inhalt (erlaubt einfaches HTML).
		if ( ! empty( $signature['content'] ) ) {
			$html .= '<div class="rp-signature-content" style="margin: 0;">';
			$html .= wp_kses_post( nl2br( $signature['content'] ) );
			$html .= '</div>';
		}

		$html .= '</div>';

		// Firmen-Footer mit Trennlinie wird IMMER angehängt.
		$html .= $this->renderCompanyFooter();

		return $html;
	}

	/**
	 * Firmendaten aus Plugin-Einstellungen laden
	 *
	 * @return array Firmendaten (normalisiert zu name, street, zip, city, phone, website, email).
	 */
	private function getCompanyData(): array {
		$settings = get_option( 'rp_settings', [] );

		// E-Mail: Priorität company_email > notification_email > admin_email.
		$email = $settings['company_email']
			?? $settings['notification_email']
			?? get_option( 'admin_email' );

		return [
			'name'    => $settings['company_name'] ?? get_bloginfo( 'name' ),
			'street'  => $settings['company_street'] ?? '',
			'zip'     => $settings['company_zip'] ?? '',
			'city'    => $settings['company_city'] ?? '',
			'phone'   => $settings['company_phone'] ?? '',
			'website' => $settings['company_website'] ?? home_url(),
			'email'   => $email,
		];
	}

	/**
	 * Signatur-Daten bereinigen
	 *
	 * @param array $data Rohe Eingabedaten.
	 * @return array Bereinigte Daten.
	 */
	private function sanitizeSignatureData( array $data ): array {
		$sanitized = [];

		if ( isset( $data['name'] ) ) {
			$sanitized['name'] = sanitize_text_field( $data['name'] );
		}

		if ( isset( $data['greeting'] ) ) {
			$sanitized['greeting'] = sanitize_text_field( $data['greeting'] );
		}

		if ( isset( $data['content'] ) ) {
			// Erlaubt einfaches HTML in Signaturen.
			$sanitized['content'] = wp_kses_post( $data['content'] );
		}

		if ( isset( $data['user_id'] ) ) {
			$sanitized['user_id'] = $data['user_id'] ? absint( $data['user_id'] ) : null;
		}

		if ( isset( $data['is_default'] ) ) {
			$sanitized['is_default'] = (int) (bool) $data['is_default'];
		}

		if ( isset( $data['include_company'] ) ) {
			$sanitized['include_company'] = (int) (bool) $data['include_company'];
		}

		return $sanitized;
	}

	/**
	 * Vorschau einer Signatur rendern
	 *
	 * @param array $signature_data Signatur-Daten (ohne DB-Eintrag).
	 * @return string HTML-Vorschau.
	 */
	public function renderPreview( array $signature_data ): string {
		$signature_data['include_company'] = $signature_data['include_company'] ?? true;

		return $this->renderSignatureContent( $signature_data );
	}
}

<?php
/**
 * License Manager
 *
 * Verwaltet Lizenzschlüssel, Validierung und Tier-Zuordnung.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Licensing;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Traits\Singleton;

/**
 * License Manager Klasse (Singleton)
 */
class LicenseManager {

	use Singleton;

	/**
	 * Option key for license data
	 */
	private const OPTION_KEY = 'rp_license';

	/**
	 * Transient key for license cache
	 */
	private const CACHE_KEY = 'rp_license_cache';

	/**
	 * Transient key for integrity signature
	 */
	private const INTEGRITY_KEY = 'rp_license_integrity';

	/**
	 * Transient key for lock mechanism
	 */
	private const LOCK_KEY = 'rp_license_check_lock';

	/**
	 * Lock duration in seconds
	 */
	private const LOCK_DURATION = 30;

	/**
	 * Cache duration in seconds (24 hours)
	 */
	private const CACHE_DURATION = DAY_IN_SECONDS;

	/**
	 * Grace period for offline mode (7 days)
	 */
	private const GRACE_PERIOD = 604800;

	/**
	 * Cached license data
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $license_data = null;

	/**
	 * Initialize (called by Singleton trait)
	 */
	protected function init(): void {
		// Register AJAX handlers for admin.
		add_action( 'wp_ajax_rp_activate_license', array( $this, 'ajax_activate' ) );
		add_action( 'wp_ajax_rp_deactivate_license', array( $this, 'ajax_deactivate' ) );
		add_action( 'wp_ajax_rp_check_license', array( $this, 'ajax_check' ) );
	}

	/**
	 * Lizenzschlüssel aktivieren
	 *
	 * @param string $license_key Der Lizenzschlüssel.
	 * @return array{success: bool, tier?: string, error?: string, message: string}
	 */
	public function activate( string $license_key ): array {
		// 1. Format validieren.
		if ( ! $this->validate_format( $license_key ) ) {
			return array(
				'success' => false,
				'error'   => 'invalid_format',
				'message' => __( 'Ungültiges Lizenzschlüssel-Format.', 'recruiting-playbook' ),
			);
		}

		// 2. Checksum validieren.
		if ( ! $this->validate_checksum( $license_key ) ) {
			return array(
				'success' => false,
				'error'   => 'invalid_checksum',
				'message' => __( 'Lizenzschlüssel-Prüfsumme ungültig.', 'recruiting-playbook' ),
			);
		}

		// 3. Remote-Validierung (Phase 1: Offline, Phase 2: Server).
		$validation = $this->validate_remote( $license_key );

		if ( ! $validation['success'] ) {
			return $validation;
		}

		// 4. Lokal speichern.
		$license_data = array(
			'key'          => $license_key,
			'tier'         => $validation['tier'],
			'domain'       => $this->get_domain(),
			'activated_at' => time(),
			'expires_at'   => $validation['expires_at'] ?? null,
			'last_check'   => time(),
		);

		update_option( self::OPTION_KEY, $license_data );

		// 5. Integritätssignatur speichern.
		$signature = $this->create_integrity_signature( $license_data );
		update_option( self::INTEGRITY_KEY, $signature );

		// 5b. Sofort verifizieren, dass Speicherung korrekt war.
		if ( ! $this->verify_integrity( $license_data ) ) {
			// Speicherung fehlgeschlagen - aufräumen.
			delete_option( self::OPTION_KEY );
			delete_option( self::INTEGRITY_KEY );

			return array(
				'success' => false,
				'error'   => 'integrity_check_failed',
				'message' => __( 'Lizenz konnte nicht sicher gespeichert werden. Bitte versuchen Sie es erneut.', 'recruiting-playbook' ),
			);
		}

		// 6. Cache leeren.
		delete_transient( self::CACHE_KEY );

		$this->license_data = $license_data;

		// 7. Action für andere Komponenten.
		do_action( 'rp_license_activated', $license_data );

		return array(
			'success' => true,
			'tier'    => $validation['tier'],
			'message' => __( 'Lizenz erfolgreich aktiviert.', 'recruiting-playbook' ),
		);
	}

	/**
	 * Lizenz deaktivieren
	 *
	 * @return array{success: bool, error?: string, message: string}
	 */
	public function deactivate(): array {
		$license_data = get_option( self::OPTION_KEY );

		if ( empty( $license_data['key'] ) ) {
			return array(
				'success' => false,
				'error'   => 'no_license',
				'message' => __( 'Keine aktive Lizenz gefunden.', 'recruiting-playbook' ),
			);
		}

		// Remote-Deaktivierung (Phase 2).
		$this->deactivate_remote( $license_data['key'] );

		// Lokal löschen.
		delete_option( self::OPTION_KEY );
		delete_option( self::INTEGRITY_KEY );
		delete_transient( self::CACHE_KEY );

		$this->license_data = null;

		// Action für andere Komponenten.
		do_action( 'rp_license_deactivated' );

		return array(
			'success' => true,
			'message' => __( 'Lizenz deaktiviert.', 'recruiting-playbook' ),
		);
	}

	/**
	 * Aktuellen Tier abrufen
	 *
	 * @return string Tier-Name (FREE, PRO, AI_ADDON, BUNDLE).
	 */
	public function get_tier(): string {
		$license = $this->get_license();

		if ( ! $license || ! $this->is_valid() ) {
			return 'FREE';
		}

		return $license['tier'] ?? 'FREE';
	}

	/**
	 * Lizenzdaten abrufen
	 *
	 * @return array<string, mixed>|null Lizenzdaten oder null.
	 */
	public function get_license(): ?array {
		if ( null !== $this->license_data ) {
			return $this->license_data;
		}

		$data = get_option( self::OPTION_KEY, null );

		// get_option gibt false zurück wenn Option nicht existiert oder ungültig ist.
		$this->license_data = is_array( $data ) ? $data : null;

		return $this->license_data;
	}

	/**
	 * Prüft ob Lizenz gültig ist
	 *
	 * @return bool True wenn gültig.
	 */
	public function is_valid(): bool {
		$license = $this->get_license();

		if ( ! $license || empty( $license['key'] ) ) {
			return false;
		}

		// Domain-Check.
		if ( $license['domain'] !== $this->get_domain() ) {
			return false;
		}

		// Integritäts-Check.
		if ( ! $this->verify_integrity( $license ) ) {
			do_action( 'rp_license_tampering_detected', $license );

			// Bei Manipulation sofort alle Lizenzdaten löschen.
			delete_option( self::OPTION_KEY );
			delete_option( self::INTEGRITY_KEY );
			delete_transient( self::CACHE_KEY );
			$this->license_data = null;

			return false;
		}

		// Ablauf-Check (für Subscriptions wie AI_ADDON).
		if ( ! empty( $license['expires_at'] ) && $license['expires_at'] < time() ) {
			return false;
		}

		// Cache-Check.
		$cache = get_transient( self::CACHE_KEY );

		if ( false !== $cache ) {
			return $cache['valid'] ?? false;
		}

		// Remote-Check wenn Cache abgelaufen.
		return $this->check_and_cache();
	}

	/**
	 * Remote-Validierung mit Caching
	 *
	 * @return bool True wenn gültig.
	 */
	private function check_and_cache(): bool {
		$license = $this->get_license();

		if ( ! $license ) {
			return false;
		}

		// Lock-Mechanismus: Verhindert Race Conditions bei gleichzeitigen Requests.
		if ( get_transient( self::LOCK_KEY ) ) {
			// Ein anderer Request führt bereits den Check durch.
			// Kurz warten und Cache erneut prüfen.
			usleep( 500000 ); // 0.5 Sekunden.
			$cache = get_transient( self::CACHE_KEY );
			if ( false !== $cache ) {
				return $cache['valid'] ?? false;
			}
			// Fallback: Lizenz als gültig annehmen während Lock aktiv.
			return true;
		}

		// Lock setzen.
		set_transient( self::LOCK_KEY, true, self::LOCK_DURATION );

		// Remote-Check versuchen.
		$validation = $this->validate_remote( $license['key'] );

		if ( $validation['success'] ) {
			// Erfolg: 24h Cache.
			set_transient(
				self::CACHE_KEY,
				array(
					'valid'      => true,
					'checked_at' => time(),
				),
				self::CACHE_DURATION
			);

			// Last check aktualisieren.
			$license['last_check'] = time();
			update_option( self::OPTION_KEY, $license );

			// Lock freigeben.
			delete_transient( self::LOCK_KEY );

			return true;
		}

		// Server nicht erreichbar: Grace Period.
		if ( 'server_unreachable' === $validation['error'] ) {
			$last_check = $license['last_check'] ?? 0;

			if ( ( time() - $last_check ) < self::GRACE_PERIOD ) {
				// Innerhalb Grace Period: Noch gültig.
				set_transient(
					self::CACHE_KEY,
					array(
						'valid'      => true,
						'offline'    => true,
						'checked_at' => time(),
					),
					HOUR_IN_SECONDS
				);

				// Lock freigeben.
				delete_transient( self::LOCK_KEY );

				return true;
			}

			// Grace Period abgelaufen.
			set_transient(
				self::CACHE_KEY,
				array(
					'valid'      => false,
					'offline'    => true,
					'checked_at' => time(),
				),
				HOUR_IN_SECONDS
			);

			// Lock freigeben.
			delete_transient( self::LOCK_KEY );

			return false;
		}

		// Lizenz ungültig.
		set_transient(
			self::CACHE_KEY,
			array(
				'valid'      => false,
				'checked_at' => time(),
			),
			self::CACHE_DURATION
		);

		// Lock freigeben.
		delete_transient( self::LOCK_KEY );

		return false;
	}

	/**
	 * Schlüssel-Format validieren
	 *
	 * @param string $key Lizenzschlüssel.
	 * @return bool True wenn Format gültig.
	 */
	private function validate_format( string $key ): bool {
		// Format: RP-{TIER}-{XXXX}-{XXXX}-{XXXX}-{XXXX}-{XXXX}
		$pattern = '/^RP-(PRO|AI|BUNDLE)-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/';
		return (bool) preg_match( $pattern, strtoupper( $key ) );
	}

	/**
	 * Checksum validieren
	 *
	 * Verwendet HMAC-SHA256 für kryptographisch sichere Validierung.
	 *
	 * @param string $key Lizenzschlüssel.
	 * @return bool True wenn Checksum gültig.
	 */
	private function validate_checksum( string $key ): bool {
		$key = strtoupper( $key );

		// Letzte 4 Zeichen = Checksum.
		$checksum = substr( $key, -4 );
		$payload  = substr( $key, 0, -5 ); // Ohne "-XXXX".

		// HMAC-SHA256 Checksum (erste 4 Hex-Zeichen).
		$secret     = $this->get_license_secret();
		$calculated = strtoupper( substr( hash_hmac( 'sha256', $payload, $secret ), 0, 4 ) );

		return hash_equals( $checksum, $calculated );
	}

	/**
	 * License Secret für Checksum-Berechnung
	 *
	 * Kann über Konstante RP_LICENSE_SECRET in wp-config.php definiert werden.
	 *
	 * @return string Secret für HMAC.
	 */
	private function get_license_secret(): string {
		if ( defined( 'RP_LICENSE_SECRET' ) && RP_LICENSE_SECRET ) {
			return RP_LICENSE_SECRET;
		}

		// Fallback für Entwicklung (sollte in Produktion immer definiert sein).
		return 'rp-default-license-secret-change-in-production';
	}

	/**
	 * Tier aus Schlüssel extrahieren
	 *
	 * @param string $key Lizenzschlüssel.
	 * @return string Tier-Name.
	 */
	private function extract_tier( string $key ): string {
		$key = strtoupper( $key );

		if ( str_starts_with( $key, 'RP-PRO-' ) ) {
			return 'PRO';
		}
		if ( str_starts_with( $key, 'RP-AI-' ) ) {
			return 'AI_ADDON';
		}
		if ( str_starts_with( $key, 'RP-BUNDLE-' ) ) {
			return 'BUNDLE';
		}
		return 'FREE';
	}

	/**
	 * Domain ermitteln
	 *
	 * @return string Domain ohne Protokoll.
	 */
	private function get_domain(): string {
		$site_url = get_site_url();
		$parsed   = wp_parse_url( $site_url );
		return $parsed['host'] ?? '';
	}

	/**
	 * Remote-Validierung
	 *
	 * Phase 1: Offline-Validierung (nur Format + Checksum)
	 * Phase 2: Server-Validierung (später)
	 *
	 * @param string $key Lizenzschlüssel.
	 * @return array{success: bool, tier?: string, expires_at?: int|null, error?: string, message?: string}
	 */
	private function validate_remote( string $key ): array {
		// ──────────────────────────────────────────────────────.
		// PHASE 1: Offline-Validierung.
		// Später durch echte API ersetzen.
		// ──────────────────────────────────────────────────────.

		if ( ! $this->validate_format( $key ) ) {
			return array(
				'success' => false,
				'error'   => 'invalid_key',
				'message' => __( 'Ungültiger Lizenzschlüssel.', 'recruiting-playbook' ),
			);
		}

		if ( ! $this->validate_checksum( $key ) ) {
			return array(
				'success' => false,
				'error'   => 'invalid_checksum',
				'message' => __( 'Lizenzschlüssel-Prüfsumme ungültig.', 'recruiting-playbook' ),
			);
		}

		// Tier extrahieren.
		$tier = $this->extract_tier( $key );

		// Ablaufdatum für Subscriptions.
		$expires_at = null;
		if ( 'AI_ADDON' === $tier ) {
			$expires_at = strtotime( '+1 year' );
		}

		return array(
			'success'    => true,
			'tier'       => $tier,
			'expires_at' => $expires_at,
		);

		// ──────────────────────────────────────────────────────.
		// PHASE 2: Echte API (später implementieren).
		// ──────────────────────────────────────────────────────.
		/*
		$response = wp_remote_post(
			'https://api.recruiting-playbook.com/v1/license/validate',
			array(
				'timeout' => 15,
				'body'    => array(
					'license_key'    => $key,
					'domain'         => $this->get_domain(),
					'plugin_version' => RP_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => 'server_unreachable',
				'message' => __( 'Lizenzserver nicht erreichbar.', 'recruiting-playbook' ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return $body;
		*/
	}

	/**
	 * Remote-Deaktivierung (Phase 2)
	 *
	 * @param string $key Lizenzschlüssel.
	 */
	private function deactivate_remote( string $key ): void {
		// PHASE 2: API-Call.
		/*
		wp_remote_post(
			'https://api.recruiting-playbook.com/v1/license/deactivate',
			array(
				'body' => array(
					'license_key' => $key,
					'domain'      => $this->get_domain(),
				),
			)
		);
		*/
	}

	/**
	 * Integritätssignatur erstellen
	 *
	 * @param array<string, mixed> $license_data Lizenzdaten.
	 * @return string HMAC-Signatur.
	 */
	private function create_integrity_signature( array $license_data ): string {
		$payload = wp_json_encode(
			array(
				'key'    => $license_data['key'],
				'tier'   => $license_data['tier'],
				'domain' => $license_data['domain'],
			)
		);

		return hash_hmac( 'sha256', $payload, $this->get_integrity_secret() );
	}

	/**
	 * Integrität der Lizenzdaten verifizieren
	 *
	 * @param array<string, mixed> $license_data Lizenzdaten.
	 * @return bool True wenn Integrität OK.
	 */
	private function verify_integrity( array $license_data ): bool {
		$stored_signature = get_option( self::INTEGRITY_KEY, '' );

		if ( empty( $stored_signature ) ) {
			return false;
		}

		$expected = $this->create_integrity_signature( $license_data );

		return hash_equals( $expected, $stored_signature );
	}

	/**
	 * Secret für Integritätssignatur
	 *
	 * @return string Hash aus WordPress-spezifischen Konstanten.
	 */
	private function get_integrity_secret(): string {
		$parts = array(
			defined( 'NONCE_KEY' ) ? NONCE_KEY : 'fallback-nonce',
			defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : 'fallback-auth',
			DB_NAME,
			site_url(),
		);

		return hash( 'sha256', implode( '|', $parts ) );
	}

	/**
	 * Lizenzstatus für Admin-Anzeige
	 *
	 * @return array<string, mixed> Status-Informationen.
	 */
	public function get_status(): array {
		$license = $this->get_license();

		if ( ! $license ) {
			return array(
				'tier'        => 'FREE',
				'is_active'   => false,
				'is_valid'    => true,
				'message'     => __( 'Kostenlose Version', 'recruiting-playbook' ),
				'upgrade_url' => $this->get_upgrade_url(),
			);
		}

		$is_valid   = $this->is_valid();
		$cache      = get_transient( self::CACHE_KEY );
		$is_offline = $cache['offline'] ?? false;

		$tier_labels = array(
			'PRO'      => 'Pro',
			'AI_ADDON' => 'AI Addon',
			'BUNDLE'   => 'Pro + AI Bundle',
		);

		$status = array(
			'tier'         => $license['tier'],
			'is_active'    => true,
			'is_valid'     => $is_valid,
			'activated_at' => $license['activated_at'],
			'expires_at'   => $license['expires_at'],
			'domain'       => $license['domain'],
			'is_offline'   => $is_offline,
			'upgrade_url'  => $this->get_upgrade_url(),
		);

		if ( ! $is_valid ) {
			$status['message'] = __( 'Lizenz ungültig oder abgelaufen.', 'recruiting-playbook' );
		} elseif ( $is_offline ) {
			$status['message'] = __( 'Offline-Modus (Lizenzserver nicht erreichbar)', 'recruiting-playbook' );
		} else {
			$status['message'] = sprintf(
				/* translators: %s: tier name */
				__( '%s Lizenz aktiv', 'recruiting-playbook' ),
				$tier_labels[ $license['tier'] ] ?? $license['tier']
			);
		}

		return $status;
	}

	/**
	 * Upgrade-URL abrufen
	 *
	 * @param string|null $tier Optional: Spezifischer Tier.
	 * @return string Upgrade-URL.
	 */
	public function get_upgrade_url( ?string $tier = null ): string {
		$base = 'https://recruiting-playbook.com/pricing/';

		if ( $tier ) {
			$base = add_query_arg( 'tier', strtolower( $tier ), $base );
		}

		return $base;
	}

	/**
	 * AJAX: Lizenz aktivieren
	 */
	public function ajax_activate(): void {
		check_ajax_referer( 'rp_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'recruiting-playbook' ) ) );
		}

		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Bitte geben Sie einen Lizenzschlüssel ein.', 'recruiting-playbook' ) ) );
		}

		$result = $this->activate( $license_key );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Lizenz deaktivieren
	 */
	public function ajax_deactivate(): void {
		check_ajax_referer( 'rp_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'recruiting-playbook' ) ) );
		}

		$result = $this->deactivate();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Lizenzstatus prüfen
	 */
	public function ajax_check(): void {
		check_ajax_referer( 'rp_license_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'recruiting-playbook' ) ) );
		}

		wp_send_json_success( $this->get_status() );
	}
}

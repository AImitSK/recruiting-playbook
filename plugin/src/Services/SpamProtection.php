<?php
/**
 * Spam Protection Service
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

use WP_REST_Request;
use WP_Error;

/**
 * Spam-Schutz für Bewerbungsformulare
 *
 * Implementiert mehrere Schichten:
 * - Honeypot (verstecktes Feld)
 * - Time-Check (Mindestzeit zum Ausfüllen)
 * - Rate Limiting (max. Bewerbungen pro IP/Zeit)
 */
class SpamProtection {

	/**
	 * Mindestzeit in Sekunden für das Ausfüllen des Formulars
	 *
	 * @var int
	 */
	private const MIN_FORM_TIME = 5;

	/**
	 * Maximale Bewerbungen pro IP pro Stunde
	 *
	 * @var int
	 */
	private const MAX_APPLICATIONS_PER_HOUR = 5;

	/**
	 * Spam-Prüfung durchführen
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return true|WP_Error True bei Erfolg, WP_Error bei Spam.
	 */
	public function check( WP_REST_Request $request ): true|WP_Error {
		// 1. Honeypot prüfen
		$honeypot_result = $this->checkHoneypot( $request );
		if ( is_wp_error( $honeypot_result ) ) {
			$this->logSpamAttempt( 'honeypot', $request );
			return $honeypot_result;
		}

		// 2. Time-Check prüfen
		$time_result = $this->checkFormTime( $request );
		if ( is_wp_error( $time_result ) ) {
			$this->logSpamAttempt( 'time_check', $request );
			return $time_result;
		}

		// 3. Rate Limiting prüfen
		$rate_result = $this->checkRateLimit( $request );
		if ( is_wp_error( $rate_result ) ) {
			$this->logSpamAttempt( 'rate_limit', $request );
			return $rate_result;
		}

		return true;
	}

	/**
	 * Honeypot-Feld prüfen
	 *
	 * Das Honeypot-Feld ist ein verstecktes Feld, das Menschen nicht ausfüllen,
	 * aber Bots automatisch befüllen.
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return true|WP_Error
	 */
	private function checkHoneypot( WP_REST_Request $request ): true|WP_Error {
		$honeypot = $request->get_param( '_hp_field' );

		// Wenn das Feld ausgefüllt ist, ist es wahrscheinlich ein Bot
		if ( ! empty( $honeypot ) ) {
			return new WP_Error(
				'spam_detected',
				__( 'Ihre Anfrage wurde als potentieller Spam erkannt.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Formular-Zeit prüfen
	 *
	 * Bots füllen Formulare oft in weniger als einer Sekunde aus.
	 * Echte Benutzer brauchen mindestens einige Sekunden.
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return true|WP_Error
	 */
	private function checkFormTime( WP_REST_Request $request ): true|WP_Error {
		$timestamp = $request->get_param( '_form_timestamp' );

		// Wenn kein Timestamp, überspringen (optionale Prüfung)
		if ( empty( $timestamp ) ) {
			return true;
		}

		$form_time = time() - (int) $timestamp;

		// Wenn das Formular zu schnell ausgefüllt wurde
		if ( $form_time < self::MIN_FORM_TIME ) {
			return new WP_Error(
				'submission_too_fast',
				__( 'Bitte nehmen Sie sich etwas mehr Zeit zum Ausfüllen des Formulars.', 'recruiting-playbook' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Rate Limiting prüfen
	 *
	 * Begrenzt die Anzahl der Bewerbungen pro IP-Adresse.
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return true|WP_Error
	 */
	private function checkRateLimit( WP_REST_Request $request ): true|WP_Error {
		$ip = $this->getClientIp( $request );

		if ( empty( $ip ) ) {
			return true; // Kann IP nicht ermitteln, überspringen
		}

		$transient_key = 'rp_rate_limit_' . md5( $ip );
		$attempts = (int) get_transient( $transient_key );

		if ( $attempts >= self::MAX_APPLICATIONS_PER_HOUR ) {
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Sie haben die maximale Anzahl an Bewerbungen erreicht. Bitte versuchen Sie es später erneut.', 'recruiting-playbook' ),
				[ 'status' => 429 ]
			);
		}

		// Counter erhöhen
		set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Client-IP ermitteln
	 *
	 * @param WP_REST_Request $request Request-Objekt.
	 * @return string
	 */
	private function getClientIp( WP_REST_Request $request ): string {
		$ip_keys = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Bei X-Forwarded-For kann es mehrere IPs geben
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Spam-Versuch loggen
	 *
	 * @param string          $type    Art des Spam-Versuchs.
	 * @param WP_REST_Request $request Request-Objekt.
	 */
	private function logSpamAttempt( string $type, WP_REST_Request $request ): void {
		$ip = $this->getClientIp( $request );
		$user_agent = $request->get_header( 'user-agent' ) ?: '';

		// In Error-Log schreiben
		error_log(
			sprintf(
				'[Recruiting Playbook] Spam blocked - Type: %s, IP: %s, UA: %s, Job: %d',
				$type,
				$ip,
				substr( $user_agent, 0, 100 ),
				$request->get_param( 'job_id' ) ?: 0
			)
		);

		// Optional: In DB loggen für Statistiken
		do_action( 'rp_spam_blocked', $type, $ip, $request );
	}

	/**
	 * Honeypot-Feld HTML generieren
	 *
	 * @return string HTML für das Honeypot-Feld.
	 */
	public static function getHoneypotField(): string {
		// CSS-Klasse und Feldname leicht obfuskiert um smarte Bots zu täuschen
		return sprintf(
			'<div class="rp-website-field" style="position:absolute;left:-9999px;height:0;overflow:hidden;" aria-hidden="true">
				<label for="rp_hp_website">%s</label>
				<input type="text" name="_hp_field" id="rp_hp_website" tabindex="-1" autocomplete="off" value="">
			</div>',
			esc_html__( 'Website (nicht ausfüllen)', 'recruiting-playbook' )
		);
	}

	/**
	 * Timestamp-Feld HTML generieren
	 *
	 * @return string HTML für das Timestamp-Feld.
	 */
	public static function getTimestampField(): string {
		return sprintf(
			'<input type="hidden" name="_form_timestamp" value="%d">',
			time()
		);
	}
}

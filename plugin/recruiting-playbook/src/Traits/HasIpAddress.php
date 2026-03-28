<?php
/**
 * IP-Adress-Trait für DSGVO-konforme Anonymisierung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait für IP-Adress-Handling mit DSGVO-konformer Anonymisierung
 */
trait HasIpAddress {

	/**
	 * Client-IP ermitteln und anonymisieren (DSGVO-konform)
	 *
	 * @return string Anonymisierte IP-Adresse
	 */
	protected function getAnonymizedClientIp(): string {
		$ip = $this->getRawClientIp();

		if ( empty( $ip ) ) {
			return '';
		}

		return $this->anonymizeIp( $ip );
	}

	/**
	 * Rohe Client-IP ermitteln (nicht anonymisiert)
	 *
	 * @return string IP-Adresse
	 */
	protected function getRawClientIp(): string {
		// Reihenfolge: Proxy-Headers zuerst, dann REMOTE_ADDR.
		$headers = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_REAL_IP',        // Nginx Proxy.
			'HTTP_X_FORWARDED_FOR',  // Standard Proxy.
			'REMOTE_ADDR',           // Direkt.
		];

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

				// X-Forwarded-For kann mehrere IPs enthalten (komma-separiert).
				if ( strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}

				// Validieren.
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * IP-Adresse anonymisieren (DSGVO-konform)
	 *
	 * IPv4: Letztes Oktett auf 0 setzen (192.168.1.123 -> 192.168.1.0)
	 * IPv6: Letzte 80 Bits auf 0 setzen
	 *
	 * @param string $ip IP-Adresse.
	 * @return string Anonymisierte IP-Adresse
	 */
	protected function anonymizeIp( string $ip ): string {
		if ( empty( $ip ) ) {
			return '';
		}

		// IPv4.
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			// Letztes Oktett auf 0 setzen.
			return preg_replace( '/\.\d+$/', '.0', $ip );
		}

		// IPv6.
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			// Adresse in Binary umwandeln.
			$packed = inet_pton( $ip );

			if ( $packed === false ) {
				return '';
			}

			// Letzte 80 Bits (10 Bytes) auf 0 setzen.
			// Behalte die ersten 48 Bits (6 Bytes).
			$mask   = str_repeat( "\xff", 6 ) . str_repeat( "\x00", 10 );
			$masked = $packed & $mask;

			return inet_ntop( $masked );
		}

		return '';
	}
}

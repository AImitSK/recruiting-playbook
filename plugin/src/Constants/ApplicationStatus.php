<?php
/**
 * Bewerbungs-Status Konstanten
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Constants;

/**
 * Bewerbungs-Status
 */
class ApplicationStatus {

	public const NEW       = 'new';
	public const SCREENING = 'screening';
	public const INTERVIEW = 'interview';
	public const OFFER     = 'offer';
	public const HIRED     = 'hired';
	public const REJECTED  = 'rejected';
	public const WITHDRAWN = 'withdrawn';

	/**
	 * Alle Status mit Labels
	 *
	 * @return array<string, string>
	 */
	public static function getAll(): array {
		return [
			self::NEW       => __( 'Neu', 'recruiting-playbook' ),
			self::SCREENING => __( 'In Prüfung', 'recruiting-playbook' ),
			self::INTERVIEW => __( 'Interview', 'recruiting-playbook' ),
			self::OFFER     => __( 'Angebot', 'recruiting-playbook' ),
			self::HIRED     => __( 'Eingestellt', 'recruiting-playbook' ),
			self::REJECTED  => __( 'Abgelehnt', 'recruiting-playbook' ),
			self::WITHDRAWN => __( 'Zurückgezogen', 'recruiting-playbook' ),
		];
	}

	/**
	 * Farbe für Status
	 *
	 * @param string $status Status key.
	 * @return string Hex color.
	 */
	public static function getColor( string $status ): string {
		return match ( $status ) {
			self::NEW       => '#2271b1',
			self::SCREENING => '#dba617',
			self::INTERVIEW => '#9b59b6',
			self::OFFER     => '#1e8cbe',
			self::HIRED     => '#00a32a',
			self::REJECTED  => '#d63638',
			self::WITHDRAWN => '#787c82',
			default         => '#787c82',
		};
	}
}

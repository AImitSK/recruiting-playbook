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
	public const DELETED   = 'deleted';

	/**
	 * Alle Status mit Labels (inkl. gelöscht)
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
			self::DELETED   => __( 'Gelöscht', 'recruiting-playbook' ),
		];
	}

	/**
	 * Sichtbare Status (ohne gelöscht) für normale Ansicht
	 *
	 * @return array<string, string>
	 */
	public static function getVisible(): array {
		$all = self::getAll();
		unset( $all[ self::DELETED ] );
		return $all;
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
			self::DELETED   => '#a7aaad',
			default         => '#787c82',
		};
	}

	/**
	 * Alle Status-Farben
	 *
	 * @return array<string, string>
	 */
	public static function getColors(): array {
		return [
			self::NEW       => '#2271b1',
			self::SCREENING => '#dba617',
			self::INTERVIEW => '#9b59b6',
			self::OFFER     => '#1e8cbe',
			self::HIRED     => '#00a32a',
			self::REJECTED  => '#d63638',
			self::WITHDRAWN => '#787c82',
			self::DELETED   => '#a7aaad',
		];
	}

	/**
	 * Erlaubte Status-Übergänge
	 *
	 * @return array<string, array<string>>
	 */
	public static function getAllowedTransitions(): array {
		return [
			self::NEW => [
				self::SCREENING,
				self::REJECTED,
				self::WITHDRAWN,
			],
			self::SCREENING => [
				self::INTERVIEW,
				self::REJECTED,
				self::WITHDRAWN,
			],
			self::INTERVIEW => [
				self::OFFER,
				self::REJECTED,
				self::WITHDRAWN,
			],
			self::OFFER => [
				self::HIRED,
				self::REJECTED,
				self::WITHDRAWN,
			],
			self::HIRED     => [],
			self::REJECTED  => [ self::SCREENING ],
			self::WITHDRAWN => [],
		];
	}

	/**
	 * Prüfen ob Übergang erlaubt
	 *
	 * @param string $from Aktueller Status.
	 * @param string $to   Neuer Status.
	 * @return bool
	 */
	public static function isTransitionAllowed( string $from, string $to ): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$allowed = self::getAllowedTransitions()[ $from ] ?? [];
		return in_array( $to, $allowed, true );
	}

	/**
	 * Aktive Status (nicht abgeschlossen)
	 *
	 * @return array<string>
	 */
	public static function getActiveStatuses(): array {
		return [
			self::NEW,
			self::SCREENING,
			self::INTERVIEW,
			self::OFFER,
		];
	}

	/**
	 * Abgeschlossene Status
	 *
	 * @return array<string>
	 */
	public static function getClosedStatuses(): array {
		return [
			self::HIRED,
			self::REJECTED,
			self::WITHDRAWN,
		];
	}
}

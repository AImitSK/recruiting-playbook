<?php
/**
 * Dokument-Typen Konstanten
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Constants;

/**
 * Dokument-Typen
 */
class DocumentType {

	public const RESUME       = 'resume';
	public const COVER_LETTER = 'cover_letter';
	public const CERTIFICATE  = 'certificate';
	public const REFERENCE    = 'reference';
	public const PORTFOLIO    = 'portfolio';
	public const OTHER        = 'other';

	/**
	 * Alle Typen mit Labels
	 *
	 * @return array<string, string>
	 */
	public static function getAll(): array {
		return [
			self::RESUME       => __( 'Lebenslauf', 'recruiting-playbook' ),
			self::COVER_LETTER => __( 'Anschreiben', 'recruiting-playbook' ),
			self::CERTIFICATE  => __( 'Zertifikat/Zeugnis', 'recruiting-playbook' ),
			self::REFERENCE    => __( 'Referenz', 'recruiting-playbook' ),
			self::PORTFOLIO    => __( 'Portfolio', 'recruiting-playbook' ),
			self::OTHER        => __( 'Sonstiges', 'recruiting-playbook' ),
		];
	}
}

<?php
/**
 * Dokument-Typen Konstanten
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Constants;

defined( 'ABSPATH' ) || exit;

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
			self::RESUME       => __( 'Resume', 'recruiting-playbook' ),
			self::COVER_LETTER => __( 'Cover Letter', 'recruiting-playbook' ),
			self::CERTIFICATE  => __( 'Certificate', 'recruiting-playbook' ),
			self::REFERENCE    => __( 'Reference', 'recruiting-playbook' ),
			self::PORTFOLIO    => __( 'Portfolio', 'recruiting-playbook' ),
			self::OTHER        => __( 'Other', 'recruiting-playbook' ),
		];
	}
}

<?php
/**
 * Feature Flags basierend auf Lizenz-Tier
 *
 * Diese Klasse dient als Referenz für Feature-Definitionen und detaillierte
 * Feature-Werte (z.B. max_jobs, reporting level). Die Hauptlogik für
 * Feature-Checks erfolgt über die Freemius-basierten Helper-Funktionen
 * in helpers.php (rp_can(), rp_tier(), etc.).
 *
 * @package RecruitingPlaybook
 * @see helpers.php für die Freemius-Integration
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Licensing;

defined( 'ABSPATH' ) || exit;

/**
 * Feature Flags Klasse
 *
 * Verwaltet welche Features pro Lizenz-Tier verfügbar sind.
 * Wird primär für detaillierte Feature-Werte verwendet (z.B. max_jobs = -1).
 * Für boolean Feature-Checks wird rp_can() aus helpers.php empfohlen.
 */
class FeatureFlags {

	/**
	 * Feature-Definitionen pro Tier
	 */
	private const FEATURES = [
		'FREE' => [
			'create_jobs'                   => true,
			'unlimited_jobs'                => true,
			'max_jobs'                      => -1,
			'application_list'              => true,
			'kanban_board'                  => false,
			'advanced_applicant_management' => false,  // Notizen, Bewertungen, Timeline, Talent-Pool.
			'application_status'            => 'basic',
			'user_roles'                    => false,
			'email_templates'               => false,
			'custom_fields'                 => false,   // Formular-Builder, Custom Fields.
			'api_access'                    => false,
			'webhooks'                      => false,
			'integrations'                  => false,   // Slack, Teams, etc.
			'reporting'                     => 'basic',
			'advanced_reporting'            => false,  // Time-to-Hire, Conversion, Trends.
			'csv_export'                    => false,
			'design_settings'               => false,
			'avada_integration'             => false,   // Avada/Fusion Builder Elements.
			'gutenberg_blocks'              => false,   // Native Gutenberg Blocks.
			'ai_job_generation'             => false,
			'ai_text_improvement'           => false,
			'ai_templates'                  => false,
			'custom_branding'               => false,
			'priority_support'              => false,
		],
		'PRO'  => [
			'create_jobs'                   => true,
			'unlimited_jobs'                => true,
			'max_jobs'                      => -1,
			'application_list'              => true,
			'kanban_board'                  => true,
			'advanced_applicant_management' => true,  // Notizen, Bewertungen, Timeline, Talent-Pool.
			'application_status'            => 'full',
			'user_roles'                    => true,
			'email_templates'               => true,
			'custom_fields'                 => true,   // Formular-Builder, Custom Fields.
			'api_access'                    => true,
			'webhooks'                      => true,
			'integrations'                  => true,   // Slack, Teams, etc.
			'reporting'                     => 'full',
			'advanced_reporting'            => true,   // Time-to-Hire, Conversion, Trends.
			'csv_export'                    => true,
			'design_settings'               => true,
			'avada_integration'             => true,   // Avada/Fusion Builder Elements.
			'gutenberg_blocks'              => true,   // Native Gutenberg Blocks.
			'ai_job_generation'             => false,
			'ai_text_improvement'           => false,
			'ai_templates'                  => false,
			'custom_branding'               => true,
			'priority_support'              => true,
		],
	];

	/**
	 * Aktueller Tier
	 *
	 * @var string
	 */
	private string $tier;

	/**
	 * Constructor
	 *
	 * @param string $tier Lizenz-Tier (FREE, PRO).
	 */
	public function __construct( string $tier = 'FREE' ) {
		$this->tier = isset( self::FEATURES[ $tier ] ) ? $tier : 'FREE';
	}

	/**
	 * Feature-Wert abrufen
	 *
	 * @param string $feature Feature-Name.
	 * @return mixed Feature-Wert oder false wenn nicht vorhanden.
	 */
	public function get( string $feature ): mixed {
		return self::FEATURES[ $this->tier ][ $feature ] ?? false;
	}

	/**
	 * Boolean-Check ob Feature verfügbar ist
	 *
	 * @param string $feature Feature-Name.
	 * @return bool True wenn Feature verfügbar.
	 */
	public function can( string $feature ): bool {
		$value = $this->get( $feature );
		return (bool) $value;
	}

	/**
	 * Alle Features für aktuellen Tier abrufen
	 *
	 * @return array<string, mixed> Alle Features.
	 */
	public function all(): array {
		return self::FEATURES[ $this->tier ] ?? self::FEATURES['FREE'];
	}

	/**
	 * Tier setzen
	 *
	 * @param string $tier Neuer Tier.
	 */
	public function set_tier( string $tier ): void {
		if ( isset( self::FEATURES[ $tier ] ) ) {
			$this->tier = $tier;
		}
	}

	/**
	 * Aktuellen Tier abrufen
	 *
	 * @return string Aktueller Tier.
	 */
	public function get_tier(): string {
		return $this->tier;
	}

	/**
	 * Alle verfügbaren Tiers abrufen
	 *
	 * @return array<string> Liste der Tier-Namen.
	 */
	public static function get_all_tiers(): array {
		return array_keys( self::FEATURES );
	}

	/**
	 * Feature-Definition für einen Tier abrufen
	 *
	 * @param string $tier Tier-Name.
	 * @return array<string, mixed>|null Features oder null.
	 */
	public static function get_tier_features( string $tier ): ?array {
		return self::FEATURES[ $tier ] ?? null;
	}
}

<?php
/**
 * Polylang Integration
 *
 * Registriert Custom Post Types und Taxonomies für Polylang-Übersetzungen.
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Polylang Integration Klasse
 */
class PolylangIntegration {

	/**
	 * Registriert die Polylang-Hooks
	 */
	public function register(): void {
		// Nur wenn Polylang verfügbar ist.
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}

		// Custom Post Types für Polylang registrieren.
		add_filter( 'pll_get_post_types', [ $this, 'registerPostTypes' ] );

		// Taxonomies für Polylang registrieren.
		add_filter( 'pll_get_taxonomies', [ $this, 'registerTaxonomies' ] );

		// Meta-Felder synchronisieren (optional).
		add_filter( 'pll_copy_post_metas', [ $this, 'copyPostMetas' ] );
	}

	/**
	 * Registriert Custom Post Types für Polylang
	 *
	 * @param array $post_types Array der Post Types.
	 * @return array
	 */
	public function registerPostTypes( array $post_types ): array {
		// job_listing als übersetzbar registrieren.
		$post_types['job_listing'] = 'job_listing';

		return $post_types;
	}

	/**
	 * Registriert Taxonomies für Polylang
	 *
	 * @param array $taxonomies Array der Taxonomies.
	 * @return array
	 */
	public function registerTaxonomies( array $taxonomies ): array {
		// Recruiting Playbook Taxonomies registrieren.
		$taxonomies['job_category']    = 'job_category';
		$taxonomies['job_location']    = 'job_location';
		$taxonomies['employment_type'] = 'employment_type';

		return $taxonomies;
	}

	/**
	 * Definiert welche Meta-Felder beim Duplizieren kopiert werden sollen
	 *
	 * @param array $metas Array der zu kopierenden Meta-Keys.
	 * @return array
	 */
	public function copyPostMetas( array $metas ): array {
		// Technische Felder kopieren (Zahlen, Daten, etc.).
		$copy_metas = [
			'_job_salary_min',
			'_job_salary_max',
			'_job_salary_currency',
			'_job_salary_period',
			'_job_salary_hidden',
			'_job_application_deadline',
			'_job_contact_email',
			'_job_contact_phone',
			'_job_remote_option',
			'_job_start_date',
		];

		// Hinweis: Text-Felder (_job_description, _job_requirements, etc.)
		// sollten NICHT kopiert werden, sondern manuell übersetzt werden.

		return array_merge( $metas, $copy_metas );
	}
}

<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * Abstrakte Basisklasse für Fusion Builder Elements
 *
 * Alle Recruiting Playbook Fusion Builder Elements erben von dieser Klasse.
 * Sie stellt gemeinsame Funktionalität bereit und definiert die Schnittstelle.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
abstract class AbstractElement {

	/**
	 * Element-Konfiguration abrufen
	 *
	 * @return array<string, mixed> Fusion Builder Element-Konfiguration.
	 */
	abstract protected function getConfig(): array;

	/**
	 * Element bei Fusion Builder registrieren
	 *
	 * @return void
	 */
	public function register(): void {
		if ( function_exists( 'fusion_builder_map' ) ) {
			$config = $this->getConfig();

			// Kategorie automatisch hinzufügen falls nicht gesetzt.
			if ( ! isset( $config['element_category'] ) ) {
				$config['element_category'] = 'recruiting_playbook';
			}

			fusion_builder_map( $config );
		}
	}

	/**
	 * Taxonomie-Optionen für Dropdown laden
	 *
	 * @param string $taxonomy Taxonomy-Name (z.B. 'job_category').
	 * @return array<string, string> Optionen als slug => name.
	 */
	protected function getTaxonomyOptions( string $taxonomy ): array {
		$terms   = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);
		$options = [ '' => esc_attr__( '— Alle —', 'recruiting-playbook' ) ];

		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Job-Optionen für Dropdown laden
	 *
	 * @return array<string, string> Optionen als ID => Titel.
	 */
	protected function getJobOptions(): array {
		$jobs = get_posts(
			[
				'post_type'      => 'job_listing',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$options = [ '' => esc_attr__( '— Automatisch —', 'recruiting-playbook' ) ];

		foreach ( $jobs as $job ) {
			$options[ (string) $job->ID ] = $job->post_title;
		}

		return $options;
	}

	/**
	 * Standard-Icon für RP-Elements
	 *
	 * @return string Fusiona Icon-Klasse.
	 */
	protected function getIcon(): string {
		return 'fusiona-users';
	}

	/**
	 * Hilfs-URL zur Dokumentation
	 *
	 * @param string $shortcode Shortcode-Name.
	 * @return string URL zur Dokumentation.
	 */
	protected function getHelpUrl( string $shortcode ): string {
		return 'https://developer.recruiting-playbook.dev/docs/shortcodes#' . $shortcode;
	}
}

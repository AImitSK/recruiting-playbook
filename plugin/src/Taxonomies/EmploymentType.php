<?php
/**
 * Taxonomy: Beschäftigungsart
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Taxonomies;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Taxonomy: Beschäftigungsart
 */
class EmploymentType {

	public const TAXONOMY = 'employment_type';

	/**
	 * Taxonomie registrieren
	 */
	public function register(): void {
		$labels = [
			'name'              => __( 'Beschäftigungsarten', 'recruiting-playbook' ),
			'singular_name'     => __( 'Beschäftigungsart', 'recruiting-playbook' ),
			'search_items'      => __( 'Beschäftigungsarten durchsuchen', 'recruiting-playbook' ),
			'all_items'         => __( 'Alle Beschäftigungsarten', 'recruiting-playbook' ),
			'edit_item'         => __( 'Beschäftigungsart bearbeiten', 'recruiting-playbook' ),
			'update_item'       => __( 'Beschäftigungsart aktualisieren', 'recruiting-playbook' ),
			'add_new_item'      => __( 'Neue Beschäftigungsart', 'recruiting-playbook' ),
			'new_item_name'     => __( 'Neuer Name', 'recruiting-playbook' ),
			'menu_name'         => __( 'Beschäftigungsarten', 'recruiting-playbook' ),
		];

		$args = [
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'beschaeftigung' ],
			'show_in_rest'      => true,
			'rest_base'         => 'employment-types',
		];

		register_taxonomy( self::TAXONOMY, [ JobListing::POST_TYPE ], $args );

		// Standard-Werte bei Aktivierung einfügen.
		add_action( 'admin_init', [ $this, 'insertDefaults' ] );
	}

	/**
	 * Standard-Beschäftigungsarten einfügen
	 */
	public function insertDefaults(): void {
		if ( get_option( 'rp_employment_types_installed' ) ) {
			return;
		}

		$defaults = [
			'vollzeit'      => __( 'Vollzeit', 'recruiting-playbook' ),
			'teilzeit'      => __( 'Teilzeit', 'recruiting-playbook' ),
			'minijob'       => __( 'Minijob', 'recruiting-playbook' ),
			'ausbildung'    => __( 'Ausbildung', 'recruiting-playbook' ),
			'praktikum'     => __( 'Praktikum', 'recruiting-playbook' ),
			'werkstudent'   => __( 'Werkstudent', 'recruiting-playbook' ),
			'freiberuflich' => __( 'Freiberuflich', 'recruiting-playbook' ),
		];

		foreach ( $defaults as $slug => $name ) {
			if ( ! term_exists( $slug, self::TAXONOMY ) ) {
				wp_insert_term( $name, self::TAXONOMY, [ 'slug' => $slug ] );
			}
		}

		update_option( 'rp_employment_types_installed', true );
	}
}

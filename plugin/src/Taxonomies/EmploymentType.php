<?php
/**
 * Taxonomy: Beschäftigungsart
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Taxonomies;

defined( 'ABSPATH' ) || exit;

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
			'name'              => __( 'Employment Types', 'recruiting-playbook' ),
			'singular_name'     => __( 'Employment Type', 'recruiting-playbook' ),
			'search_items'      => __( 'Search Employment Types', 'recruiting-playbook' ),
			'all_items'         => __( 'All Employment Types', 'recruiting-playbook' ),
			'edit_item'         => __( 'Edit Employment Type', 'recruiting-playbook' ),
			'update_item'       => __( 'Update Employment Type', 'recruiting-playbook' ),
			'add_new_item'      => __( 'New Employment Type', 'recruiting-playbook' ),
			'new_item_name'     => __( 'New Name', 'recruiting-playbook' ),
			'menu_name'         => __( 'Employment Types', 'recruiting-playbook' ),
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
			'vollzeit'      => __( 'Full-time', 'recruiting-playbook' ),
			'teilzeit'      => __( 'Part-time', 'recruiting-playbook' ),
			'minijob'       => __( 'Minijob', 'recruiting-playbook' ),
			'ausbildung'    => __( 'Apprenticeship', 'recruiting-playbook' ),
			'praktikum'     => __( 'Internship', 'recruiting-playbook' ),
			'werkstudent'   => __( 'Working Student', 'recruiting-playbook' ),
			'freiberuflich' => __( 'Freelance', 'recruiting-playbook' ),
		];

		foreach ( $defaults as $slug => $name ) {
			if ( ! term_exists( $slug, self::TAXONOMY ) ) {
				wp_insert_term( $name, self::TAXONOMY, [ 'slug' => $slug ] );
			}
		}

		update_option( 'rp_employment_types_installed', true );
	}
}

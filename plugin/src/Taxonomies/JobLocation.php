<?php
/**
 * Taxonomy: Standort
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Taxonomies;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Taxonomy: Standort
 */
class JobLocation {

	public const TAXONOMY = 'job_location';

	/**
	 * Taxonomie registrieren
	 */
	public function register(): void {
		$labels = [
			'name'              => __( 'Standorte', 'recruiting-playbook' ),
			'singular_name'     => __( 'Standort', 'recruiting-playbook' ),
			'search_items'      => __( 'Standorte durchsuchen', 'recruiting-playbook' ),
			'all_items'         => __( 'Alle Standorte', 'recruiting-playbook' ),
			'edit_item'         => __( 'Standort bearbeiten', 'recruiting-playbook' ),
			'update_item'       => __( 'Standort aktualisieren', 'recruiting-playbook' ),
			'add_new_item'      => __( 'Neuer Standort', 'recruiting-playbook' ),
			'new_item_name'     => __( 'Neuer Standort-Name', 'recruiting-playbook' ),
			'menu_name'         => __( 'Standorte', 'recruiting-playbook' ),
		];

		$args = [
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'standort' ],
			'show_in_rest'      => true,
			'rest_base'         => 'job-locations',
		];

		register_taxonomy( self::TAXONOMY, [ JobListing::POST_TYPE ], $args );
	}
}

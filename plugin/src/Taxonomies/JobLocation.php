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
			'name'              => __( 'Locations', 'recruiting-playbook' ),
			'singular_name'     => __( 'Location', 'recruiting-playbook' ),
			'search_items'      => __( 'Search Locations', 'recruiting-playbook' ),
			'all_items'         => __( 'All Locations', 'recruiting-playbook' ),
			'edit_item'         => __( 'Edit Location', 'recruiting-playbook' ),
			'update_item'       => __( 'Update Location', 'recruiting-playbook' ),
			'add_new_item'      => __( 'New Location', 'recruiting-playbook' ),
			'new_item_name'     => __( 'New Location Name', 'recruiting-playbook' ),
			'menu_name'         => __( 'Locations', 'recruiting-playbook' ),
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

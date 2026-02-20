<?php
/**
 * Taxonomy: Job Category
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Taxonomies;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Taxonomy: Job Category
 */
class JobCategory {

	public const TAXONOMY = 'job_category';

	/**
	 * Register taxonomy
	 */
	public function register(): void {
		$labels = [
			'name'              => __( 'Job Categories', 'recruiting-playbook' ),
			'singular_name'     => __( 'Job Category', 'recruiting-playbook' ),
			'search_items'      => __( 'Search Job Categories', 'recruiting-playbook' ),
			'all_items'         => __( 'All Job Categories', 'recruiting-playbook' ),
			'parent_item'       => __( 'Parent Job Category', 'recruiting-playbook' ),
			'parent_item_colon' => __( 'Parent Job Category:', 'recruiting-playbook' ),
			'edit_item'         => __( 'Edit Job Category', 'recruiting-playbook' ),
			'update_item'       => __( 'Update Job Category', 'recruiting-playbook' ),
			'add_new_item'      => __( 'New Job Category', 'recruiting-playbook' ),
			'new_item_name'     => __( 'New Job Category Name', 'recruiting-playbook' ),
			'menu_name'         => __( 'Job Categories', 'recruiting-playbook' ),
		];

		$args = [
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => 'recruiting-playbook',
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'berufsfeld' ],
			'show_in_rest'      => true,
			'rest_base'         => 'job-categories',
		];

		register_taxonomy( self::TAXONOMY, [ JobListing::POST_TYPE ], $args );
	}
}

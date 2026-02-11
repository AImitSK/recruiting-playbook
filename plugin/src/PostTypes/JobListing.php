<?php
/**
 * Custom Post Type: Stellenanzeigen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Custom Post Type: Stellenanzeigen
 */
class JobListing {

	public const POST_TYPE = 'job_listing';

	/**
	 * CPT registrieren
	 */
	public function register(): void {
		$labels = [
			'name'                  => __( 'Jobs', 'recruiting-playbook' ),
			'singular_name'         => __( 'Job', 'recruiting-playbook' ),
			'menu_name'             => __( 'Jobs', 'recruiting-playbook' ),
			'name_admin_bar'        => __( 'Job', 'recruiting-playbook' ),
			'add_new'               => __( 'Add New', 'recruiting-playbook' ),
			'add_new_item'          => __( 'Add New Job', 'recruiting-playbook' ),
			'new_item'              => __( 'New Job', 'recruiting-playbook' ),
			'edit_item'             => __( 'Edit Job', 'recruiting-playbook' ),
			'view_item'             => __( 'View Job', 'recruiting-playbook' ),
			'all_items'             => __( 'All Jobs', 'recruiting-playbook' ),
			'search_items'          => __( 'Search Jobs', 'recruiting-playbook' ),
			'parent_item_colon'     => __( 'Parent Job:', 'recruiting-playbook' ),
			'not_found'             => __( 'No jobs found.', 'recruiting-playbook' ),
			'not_found_in_trash'    => __( 'No jobs found in trash.', 'recruiting-playbook' ),
			'featured_image'        => __( 'Job Image', 'recruiting-playbook' ),
			'set_featured_image'    => __( 'Set job image', 'recruiting-playbook' ),
			'remove_featured_image' => __( 'Remove job image', 'recruiting-playbook' ),
			'use_featured_image'    => __( 'Use as job image', 'recruiting-playbook' ),
			'archives'              => __( 'Job Archives', 'recruiting-playbook' ),
			'insert_into_item'      => __( 'Insert into job', 'recruiting-playbook' ),
			'uploaded_to_this_item' => __( 'Uploaded to this job', 'recruiting-playbook' ),
			'filter_items_list'     => __( 'Filter jobs list', 'recruiting-playbook' ),
			'items_list_navigation' => __( 'Jobs list navigation', 'recruiting-playbook' ),
			'items_list'            => __( 'Jobs list', 'recruiting-playbook' ),
		];

		$args = [
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => 'recruiting-playbook',
			'query_var'             => true,
			'rewrite'               => [
				'slug'       => $this->getSlug(),
				'with_front' => false,
			],
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'has_archive'           => true,
			'hierarchical'          => false,
			'menu_position'         => null,
			'menu_icon'             => 'dashicons-businessman',
			'supports'              => [
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'revisions',
				'custom-fields',
			],
			'show_in_rest'          => true,
			'rest_base'             => 'jobs',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'template'              => [],
			'template_lock'         => false,
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * URL-Slug aus Einstellungen holen
	 */
	private function getSlug(): string {
		$settings = get_option( 'rp_settings', [] );
		return $settings['jobs_slug'] ?? 'jobs';
	}
}

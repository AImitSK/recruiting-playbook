<?php
/**
 * Custom Post Type: Stellenanzeigen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\PostTypes;

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
			'name'                  => __( 'Stellen', 'recruiting-playbook' ),
			'singular_name'         => __( 'Stelle', 'recruiting-playbook' ),
			'menu_name'             => __( 'Stellen', 'recruiting-playbook' ),
			'name_admin_bar'        => __( 'Stelle', 'recruiting-playbook' ),
			'add_new'               => __( 'Neue Stelle', 'recruiting-playbook' ),
			'add_new_item'          => __( 'Neue Stelle erstellen', 'recruiting-playbook' ),
			'new_item'              => __( 'Neue Stelle', 'recruiting-playbook' ),
			'edit_item'             => __( 'Stelle bearbeiten', 'recruiting-playbook' ),
			'view_item'             => __( 'Stelle ansehen', 'recruiting-playbook' ),
			'all_items'             => __( 'Alle Stellen', 'recruiting-playbook' ),
			'search_items'          => __( 'Stellen durchsuchen', 'recruiting-playbook' ),
			'parent_item_colon'     => __( 'Übergeordnete Stelle:', 'recruiting-playbook' ),
			'not_found'             => __( 'Keine Stellen gefunden.', 'recruiting-playbook' ),
			'not_found_in_trash'    => __( 'Keine Stellen im Papierkorb.', 'recruiting-playbook' ),
			'featured_image'        => __( 'Stellenbild', 'recruiting-playbook' ),
			'set_featured_image'    => __( 'Stellenbild festlegen', 'recruiting-playbook' ),
			'remove_featured_image' => __( 'Stellenbild entfernen', 'recruiting-playbook' ),
			'use_featured_image'    => __( 'Als Stellenbild verwenden', 'recruiting-playbook' ),
			'archives'              => __( 'Stellen-Archiv', 'recruiting-playbook' ),
			'insert_into_item'      => __( 'In Stelle einfügen', 'recruiting-playbook' ),
			'uploaded_to_this_item' => __( 'Zu dieser Stelle hochgeladen', 'recruiting-playbook' ),
			'filter_items_list'     => __( 'Stellenliste filtern', 'recruiting-playbook' ),
			'items_list_navigation' => __( 'Stellenlisten-Navigation', 'recruiting-playbook' ),
			'items_list'            => __( 'Stellenliste', 'recruiting-playbook' ),
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

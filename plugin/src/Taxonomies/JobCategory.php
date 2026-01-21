<?php
/**
 * Taxonomy: Berufsfeld / Kategorie
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Taxonomies;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Taxonomy: Berufsfeld / Kategorie
 */
class JobCategory {

	public const TAXONOMY = 'job_category';

	/**
	 * Taxonomie registrieren
	 */
	public function register(): void {
		$labels = [
			'name'              => __( 'Berufsfelder', 'recruiting-playbook' ),
			'singular_name'     => __( 'Berufsfeld', 'recruiting-playbook' ),
			'search_items'      => __( 'Berufsfelder durchsuchen', 'recruiting-playbook' ),
			'all_items'         => __( 'Alle Berufsfelder', 'recruiting-playbook' ),
			'parent_item'       => __( 'Übergeordnetes Berufsfeld', 'recruiting-playbook' ),
			'parent_item_colon' => __( 'Übergeordnetes Berufsfeld:', 'recruiting-playbook' ),
			'edit_item'         => __( 'Berufsfeld bearbeiten', 'recruiting-playbook' ),
			'update_item'       => __( 'Berufsfeld aktualisieren', 'recruiting-playbook' ),
			'add_new_item'      => __( 'Neues Berufsfeld', 'recruiting-playbook' ),
			'new_item_name'     => __( 'Neuer Berufsfeld-Name', 'recruiting-playbook' ),
			'menu_name'         => __( 'Berufsfelder', 'recruiting-playbook' ),
		];

		$args = [
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'berufsfeld' ],
			'show_in_rest'      => true,
			'rest_base'         => 'job-categories',
		];

		register_taxonomy( self::TAXONOMY, [ JobListing::POST_TYPE ], $args );
	}
}

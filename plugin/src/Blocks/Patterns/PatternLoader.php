<?php
/**
 * Block Pattern Loader
 *
 * Registriert vorgefertigte Block-Patterns für Karriereseiten.
 * Free-Feature: Verfügbar für alle Nutzer (Free & Pro).
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Blocks\Patterns;

defined( 'ABSPATH' ) || exit;

/**
 * Pattern Loader Klasse
 */
class PatternLoader {

	/**
	 * Initialisierung
	 *
	 * Block-Patterns sind ein Free-Feature (wie Gutenberg Blocks).
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'registerPatternCategory' ] );
		add_action( 'init', [ $this, 'registerPatterns' ] );
	}

	/**
	 * Pattern-Kategorie registrieren
	 */
	public function registerPatternCategory(): void {
		register_block_pattern_category(
			'recruiting-playbook',
			[
				'label'       => __( 'Recruiting Playbook', 'recruiting-playbook' ),
				'description' => __( 'Pre-made layouts for career pages', 'recruiting-playbook' ),
			]
		);
	}

	/**
	 * Alle Patterns registrieren
	 */
	public function registerPatterns(): void {
		$this->registerKarriereseitePattern();
		$this->registerJobSidebarPattern();
		$this->registerFeaturedJobsHeroPattern();
		$this->registerJobSearchFullPattern();
		$this->registerCategoryGridPattern();
	}

	/**
	 * Pattern: Komplette Karriereseite
	 */
	private function registerKarriereseitePattern(): void {
		register_block_pattern(
			'recruiting-playbook/karriereseite',
			[
				'title'       => __( 'Career Page', 'recruiting-playbook' ),
				'description' => __( 'Complete career page with hero, featured jobs and job search.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'career', 'jobs', 'positions', 'hero' ],
				'blockTypes'  => [ 'core/post-content' ],
				'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">

<!-- wp:heading {"level":1,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<h1 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Careers at our company', 'recruiting-playbook' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
<p style="margin-bottom:var(--wp--preset--spacing--40)">' . esc_html__( 'Join our team and shape the future with us.', 'recruiting-playbook' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:rp/job-count {"format":"Currently {count} open positions","singular":"Currently {count} open position","zero":"Currently no open positions"} /-->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__( 'Top Job Listings', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:rp/featured-jobs {"limit":3,"columns":3} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__( 'All Job Listings', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:rp/job-search {"showSearch":true,"showCategory":true,"showLocation":true,"showType":true,"columns":2} /-->

</div>
<!-- /wp:group -->',
			]
		);
	}

	/**
	 * Pattern: Stellen-Sidebar
	 */
	private function registerJobSidebarPattern(): void {
		register_block_pattern(
			'recruiting-playbook/job-sidebar',
			[
				'title'       => __( 'Job Sidebar', 'recruiting-playbook' ),
				'description' => __( 'Compact job list for sidebars and widgets.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'sidebar', 'widget', 'list', 'compact' ],
				'blockTypes'  => [ 'core/widget-area' ],
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

<!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<h3 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Current Positions', 'recruiting-playbook' ) . '</h3>
<!-- /wp:heading -->

<!-- wp:rp/latest-jobs {"limit":5,"columns":1,"showExcerpt":false} /-->

</div>
<!-- /wp:group -->',
			]
		);
	}

	/**
	 * Pattern: Featured Jobs Hero
	 */
	private function registerFeaturedJobsHeroPattern(): void {
		register_block_pattern(
			'recruiting-playbook/featured-jobs-hero',
			[
				'title'       => __( 'Featured Jobs Hero', 'recruiting-playbook' ),
				'description' => __( 'Large display of top job listings.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'hero', 'featured', 'highlight', 'top' ],
				'content'     => '<!-- wp:cover {"dimRatio":80,"overlayColor":"primary","minHeight":400,"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);min-height:400px">
<span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-80 has-background-dim"></span>
<div class="wp-block-cover__inner-container">

<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="color:#ffffff">' . esc_html__( 'Our Top Job Listings', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"}}} -->
<p class="has-text-align-center" style="color:#ffffff">' . esc_html__( 'Discover our outstanding career opportunities.', 'recruiting-playbook' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:rp/featured-jobs {"limit":3,"columns":3,"showExcerpt":true} /-->

</div>
</div>
<!-- /wp:cover -->',
			]
		);
	}

	/**
	 * Pattern: Vollständige Stellensuche
	 */
	private function registerJobSearchFullPattern(): void {
		register_block_pattern(
			'recruiting-playbook/job-search-full',
			[
				'title'       => __( 'Complete Job Search', 'recruiting-playbook' ),
				'description' => __( 'Job search with all filters and job counter.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'search', 'filter', 'complete' ],
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"backgroundColor":"tertiary"} -->
<div class="wp-block-group has-tertiary-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">

<!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--10)">' . esc_html__( 'Open Positions', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<p style="margin-bottom:var(--wp--preset--spacing--30)"><!-- wp:rp/job-count {"format":"{count} positions available","singular":"{count} position available","zero":"No positions available"} /--></p>
<!-- /wp:paragraph -->

<!-- wp:rp/job-search {"showSearch":true,"showCategory":true,"showLocation":true,"showType":true,"limit":12,"columns":3} /-->

</div>
<!-- /wp:group -->',
			]
		);
	}

	/**
	 * Pattern: Kategorie-Grid
	 */
	private function registerCategoryGridPattern(): void {
		register_block_pattern(
			'recruiting-playbook/category-grid',
			[
				'title'       => __( 'Category Overview', 'recruiting-playbook' ),
				'description' => __( 'Overview of all job categories as cards.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'categories', 'fields', 'overview' ],
				'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">

<!-- wp:heading {"level":2,"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__( 'Discover Career Fields', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Find positions in your field.', 'recruiting-playbook' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:rp/job-categories {"columns":4,"showCount":true,"hideEmpty":true,"orderby":"count"} /-->

</div>
<!-- /wp:group -->',
			]
		);
	}
}

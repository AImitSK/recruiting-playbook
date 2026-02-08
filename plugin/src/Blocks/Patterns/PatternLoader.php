<?php
/**
 * Block Pattern Loader
 *
 * Registriert vorgefertigte Block-Patterns für Karriereseiten.
 * Pro-Feature: Nur verfügbar mit aktiver Pro-Lizenz.
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
	 */
	public function register(): void {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'gutenberg_blocks' ) ) {
			return;
		}

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
				'description' => __( 'Vorgefertigte Layouts für Karriereseiten', 'recruiting-playbook' ),
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
				'title'       => __( 'Karriereseite', 'recruiting-playbook' ),
				'description' => __( 'Komplette Karriereseite mit Hero, Featured Jobs und Stellensuche.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'karriere', 'jobs', 'stellen', 'hero' ],
				'blockTypes'  => [ 'core/post-content' ],
				'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">

<!-- wp:heading {"level":1,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<h1 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Karriere bei uns', 'recruiting-playbook' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
<p style="margin-bottom:var(--wp--preset--spacing--40)">' . esc_html__( 'Werden Sie Teil unseres Teams und gestalten Sie die Zukunft mit uns.', 'recruiting-playbook' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:rp/job-count {"format":"Aktuell {count} offene Stellen","singular":"Aktuell {count} offene Stelle","zero":"Aktuell keine offenen Stellen"} /-->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__( 'Top-Stellenangebote', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:rp/featured-jobs {"limit":3,"columns":3} /-->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">' . esc_html__( 'Alle Stellenangebote', 'recruiting-playbook' ) . '</h2>
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
				'title'       => __( 'Stellen-Sidebar', 'recruiting-playbook' ),
				'description' => __( 'Kompakte Stellenliste für Sidebars und Widgets.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'sidebar', 'widget', 'liste', 'kompakt' ],
				'blockTypes'  => [ 'core/widget-area' ],
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">

<!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<h3 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Aktuelle Stellen', 'recruiting-playbook' ) . '</h3>
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
				'description' => __( 'Große Darstellung der Top-Stellenangebote.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'hero', 'featured', 'highlight', 'top' ],
				'content'     => '<!-- wp:cover {"dimRatio":80,"overlayColor":"primary","minHeight":400,"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);min-height:400px">
<span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-80 has-background-dim"></span>
<div class="wp-block-cover__inner-container">

<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="color:#ffffff">' . esc_html__( 'Unsere Top-Stellenangebote', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"}}} -->
<p class="has-text-align-center" style="color:#ffffff">' . esc_html__( 'Entdecken Sie unsere herausragenden Karrieremöglichkeiten.', 'recruiting-playbook' ) . '</p>
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
				'title'       => __( 'Stellensuche Vollständig', 'recruiting-playbook' ),
				'description' => __( 'Stellensuche mit allen Filtern und Stellen-Zähler.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'suche', 'filter', 'vollständig' ],
				'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"backgroundColor":"tertiary"} -->
<div class="wp-block-group has-tertiary-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">

<!-- wp:heading {"level":2,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--10)">' . esc_html__( 'Offene Stellen', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<p style="margin-bottom:var(--wp--preset--spacing--30)"><!-- wp:rp/job-count {"format":"{count} Positionen verfügbar","singular":"{count} Position verfügbar","zero":"Keine Positionen verfügbar"} /--></p>
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
				'title'       => __( 'Kategorie-Übersicht', 'recruiting-playbook' ),
				'description' => __( 'Übersicht aller Job-Kategorien als Karten.', 'recruiting-playbook' ),
				'categories'  => [ 'recruiting-playbook' ],
				'keywords'    => [ 'kategorien', 'berufsfelder', 'übersicht' ],
				'content'     => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">

<!-- wp:heading {"level":2,"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">' . esc_html__( 'Berufsfelder entdecken', 'recruiting-playbook' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Finden Sie Stellen in Ihrem Fachbereich.', 'recruiting-playbook' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:rp/job-categories {"columns":4,"showCount":true,"hideEmpty":true,"orderby":"count"} /-->

</div>
<!-- /wp:group -->',
			]
		);
	}
}

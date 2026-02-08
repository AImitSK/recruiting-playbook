<?php
/**
 * Server-Side Render für rp/featured-jobs Block
 *
 * @var array    $attributes Block-Attribute.
 * @var string   $content    Inner Blocks (leer bei diesem Block).
 * @var WP_Block $block      Block-Instanz.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

// Attribute zu Shortcode-Attributen konvertieren.
$shortcode_atts = [
	'limit'        => $attributes['limit'] ?? 3,
	'columns'      => $attributes['columns'] ?? 3,
	'title'        => $attributes['title'] ?? '',
	'show_excerpt' => ! empty( $attributes['showExcerpt'] ) ? 'true' : 'false',
];

// Shortcode-Klasse nutzen.
$shortcode = new \RecruitingPlaybook\Frontend\Shortcodes\FeaturedJobsShortcode();
$output    = $shortcode->render( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-featured-jobs',
] );

printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$output
);

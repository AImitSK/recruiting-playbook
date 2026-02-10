<?php
/**
 * Server-Side Render für rp/latest-jobs Block
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
	'limit'        => $attributes['limit'] ?? 5,
	'columns'      => $attributes['columns'] ?? 1,
	'title'        => $attributes['title'] ?? '',
	'category'     => $attributes['category'] ?? '',
	'show_excerpt' => ! empty( $attributes['showExcerpt'] ) ? 'true' : 'false',
];

// Shortcode-Klasse nutzen.
$shortcode = new \RecruitingPlaybook\Frontend\Shortcodes\LatestJobsShortcode();
$output    = $shortcode->render( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-latest-jobs',
] );

printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$output
);

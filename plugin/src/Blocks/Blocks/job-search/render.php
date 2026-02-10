<?php
/**
 * Server-Side Render für rp/job-search Block
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
	'show_search'   => ! empty( $attributes['showSearch'] ) ? 'true' : 'false',
	'show_category' => ! empty( $attributes['showCategory'] ) ? 'true' : 'false',
	'show_location' => ! empty( $attributes['showLocation'] ) ? 'true' : 'false',
	'show_type'     => ! empty( $attributes['showType'] ) ? 'true' : 'false',
	'limit'         => $attributes['limit'] ?? 10,
	'columns'       => $attributes['columns'] ?? 1,
];

// Shortcode-Klasse nutzen.
$shortcode = new \RecruitingPlaybook\Frontend\Shortcodes\JobSearchShortcode();
$output    = $shortcode->render( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-job-search',
] );

printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$output
);

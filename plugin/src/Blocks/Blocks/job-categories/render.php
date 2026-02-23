<?php
/**
 * Server-Side Render for rp/job-categories Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner Blocks (empty for this block).
 * @var WP_Block $block      Block instance.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

// Convert attributes to shortcode attributes.
$shortcode_atts = [
	'layout'     => $attributes['layout'] ?? 'grid',
	'columns'    => $attributes['columns'] ?? 4,
	'show_count' => ! empty( $attributes['showCount'] ) ? 'true' : 'false',
	'hide_empty' => ! empty( $attributes['hideEmpty'] ) ? 'true' : 'false',
	'orderby'    => $attributes['orderby'] ?? 'name',
];

// Use shortcode class.
$shortcode = new \RecruitingPlaybook\Frontend\Shortcodes\JobCategoriesShortcode();
$output    = $shortcode->render( $shortcode_atts );

// If no categories are available.
if ( empty( trim( $output ) ) ) {
	$output = '<p class="rp-block-empty">' . esc_html__( 'No categories available.', 'recruiting-playbook' ) . '</p>';
}

// Block wrapper with Gutenberg classes.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-job-categories',
] );

printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$output
);

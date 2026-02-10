<?php
/**
 * Server-Side Render für rp/jobs Block
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
	'limit'    => $attributes['limit'] ?? 10,
	'columns'  => $attributes['columns'] ?? 2,
	'category' => $attributes['category'] ?? '',
	'location' => $attributes['location'] ?? '',
	'type'     => $attributes['type'] ?? '',
	'featured' => ! empty( $attributes['featured'] ) ? 'true' : 'false',
	'orderby'  => $attributes['orderby'] ?? 'date',
	'order'    => $attributes['order'] ?? 'DESC',
];

// Shortcode-Klasse nutzen.
$shortcode = new \RecruitingPlaybook\Frontend\Shortcodes\JobsShortcode();
$output    = $shortcode->render( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-jobs',
] );

printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$output
);

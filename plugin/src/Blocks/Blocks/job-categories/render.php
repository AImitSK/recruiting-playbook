<?php
/**
 * Server-Side Render für rp/job-categories Block
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
	'columns'    => $attributes['columns'] ?? 4,
	'show_count' => ! empty( $attributes['showCount'] ) ? 'true' : 'false',
	'hide_empty' => ! empty( $attributes['hideEmpty'] ) ? 'true' : 'false',
	'orderby'    => $attributes['orderby'] ?? 'name',
];

// Shortcode-Klasse nutzen.
$shortcode = new \RecruitingPlaybook\Frontend\Shortcodes\JobCategoriesShortcode();
$output    = $shortcode->render( $shortcode_atts );

// Falls keine Kategorien vorhanden sind.
if ( empty( trim( $output ) ) ) {
	$output = '<p class="rp-block-empty">' . esc_html__( 'Keine Kategorien vorhanden.', 'recruiting-playbook' ) . '</p>';
}

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-job-categories',
] );

printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$output
);

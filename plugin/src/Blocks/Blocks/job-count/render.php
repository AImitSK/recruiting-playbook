<?php
/**
 * Server-Side Render für rp/job-count Block
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
	'category' => $attributes['category'] ?? '',
	'location' => $attributes['location'] ?? '',
	'type'     => $attributes['type'] ?? '',
	'format'   => $attributes['format'] ?? __( '{count} open positions', 'recruiting-playbook' ),
	'singular' => $attributes['singular'] ?? __( '{count} open position', 'recruiting-playbook' ),
	'zero'     => $attributes['zero'] ?? __( 'No open positions', 'recruiting-playbook' ),
];

// Shortcode-Klasse nutzen.
$shortcode = new \RecruitingPlaybook\Frontend\Shortcodes\JobCountShortcode();
$output    = $shortcode->render( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-job-count',
] );

printf(
	'<span %s>%s</span>',
	$wrapper_attributes,
	$output
);

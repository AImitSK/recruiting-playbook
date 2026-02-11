<?php
/**
 * Server-Side Render für rp/application-form Block
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
	'job_id'         => $attributes['jobId'] ?? 0,
	'title'          => $attributes['title'] ?? __( 'Apply now', 'recruiting-playbook' ),
	'show_job_title' => ! empty( $attributes['showJobTitle'] ) ? 'true' : 'false',
	'show_progress'  => ! empty( $attributes['showProgress'] ) ? 'true' : 'false',
];

// Shortcodes-Klasse nutzen.
$shortcodes = new \RecruitingPlaybook\Frontend\Shortcodes();
$output     = $shortcodes->renderApplicationForm( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-application-form',
] );

printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$output
);

<?php
/**
 * Server-Side Render für rp/ai-job-match Block
 *
 * @var array    $attributes Block-Attribute.
 * @var string   $content    Inner Blocks (leer bei diesem Block).
 * @var WP_Block $block      Block-Instanz.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables from parent scope

// Attribute zu Shortcode-Attributen konvertieren.
$shortcode_atts = [
	'job_id' => $attributes['jobId'] ?? 0,
	'title'  => $attributes['title'] ?? __( 'Am I a good fit for this job?', 'recruiting-playbook' ),
	'style'  => $attributes['style'] ?? '',
];

// Shortcodes-Klasse nutzen.
$shortcodes = new \RecruitingPlaybook\Frontend\Shortcodes();
$output     = $shortcodes->renderAiJobMatch( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'rp-block-ai-job-match',
	]
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $wrapper_attributes is safe from get_block_wrapper_attributes()
printf( '<div %s>%s</div>', $wrapper_attributes, wp_kses_post( $output ) );

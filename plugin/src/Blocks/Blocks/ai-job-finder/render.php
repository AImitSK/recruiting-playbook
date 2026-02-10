<?php
/**
 * Server-Side Render für rp/ai-job-finder Block
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
	'title'    => $attributes['title'] ?? __( 'Finde deinen Traumjob', 'recruiting-playbook' ),
	'subtitle' => $attributes['subtitle'] ?? __( 'Lade deinen Lebenslauf hoch und entdecke passende Stellen.', 'recruiting-playbook' ),
	'limit'    => $attributes['limit'] ?? 5,
];

// Shortcodes-Klasse nutzen.
$shortcodes = new \RecruitingPlaybook\Frontend\Shortcodes();
$output     = $shortcodes->renderAiJobFinder( $shortcode_atts );

// Block-Wrapper mit Gutenberg-Klassen.
$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'rp-block-ai-job-finder',
] );

printf(
	'<div %s>%s</div>',
	$wrapper_attributes,
	$output
);

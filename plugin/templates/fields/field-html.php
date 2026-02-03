<?php
/**
 * Field Template: HTML Content Block (Layout Element)
 *
 * Display-only field for showing formatted HTML content (hints, explanations).
 * Does not collect any data.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$content = $settings['content'] ?? '';

if ( empty( $content ) ) {
	return;
}

// Sanitize HTML content with allowed tags.
$allowed_tags = wp_kses_allowed_html( 'post' );
?>

<div class="rp-form__html-content rp-text-gray-700 rp-text-sm">
	<?php echo wp_kses( $content, $allowed_tags ); ?>
</div>

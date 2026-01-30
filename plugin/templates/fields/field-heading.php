<?php
/**
 * Field Template: Heading (Layout Element)
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$level = $settings['level'] ?? 'h3';
$size  = $settings['size'] ?? 'medium';

// Heading-Tag validieren.
$allowed_levels = [ 'h2', 'h3', 'h4', 'h5', 'h6' ];
if ( ! in_array( $level, $allowed_levels, true ) ) {
	$level = 'h3';
}

// Size-Klasse.
$size_classes = [
	'small'  => 'rp-text-base rp-font-medium',
	'medium' => 'rp-text-lg rp-font-semibold',
	'large'  => 'rp-text-xl rp-font-bold',
];
$size_class   = $size_classes[ $size ] ?? $size_classes['medium'];
?>

<<?php echo esc_html( $level ); ?> class="rp-form__heading <?php echo esc_attr( $size_class ); ?> rp-text-gray-900 rp-mb-2">
	<?php echo esc_html( $label ); ?>
</<?php echo esc_html( $level ); ?>>

<?php if ( $description ) : ?>
	<p class="rp-form__heading-description rp-text-gray-600 rp-text-sm">
		<?php echo esc_html( $description ); ?>
	</p>
<?php endif; ?>

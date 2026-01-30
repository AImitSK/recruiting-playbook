<?php
/**
 * Field Template: Number
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$step = $settings['step'] ?? 1;
?>

<label class="rp-label" for="rp-field-<?php echo esc_attr( $field_key ); ?>">
	<?php echo esc_html( $label ); ?>
	<?php if ( $is_required ) : ?>
		<span class="rp-text-error">*</span>
	<?php endif; ?>
</label>

<input
	type="number"
	id="rp-field-<?php echo esc_attr( $field_key ); ?>"
	name="<?php echo esc_attr( $field_key ); ?>"
	x-model="<?php echo esc_attr( $x_model ); ?>"
	class="rp-input"
	:class="errors.<?php echo esc_attr( $field_key ); ?> ? 'rp-input-error' : ''"
	<?php if ( $placeholder ) : ?>
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
	<?php endif; ?>
	<?php if ( $is_required ) : ?>
		required
	<?php endif; ?>
	<?php if ( isset( $validation['min_value'] ) ) : ?>
		min="<?php echo esc_attr( $validation['min_value'] ); ?>"
	<?php endif; ?>
	<?php if ( isset( $validation['max_value'] ) ) : ?>
		max="<?php echo esc_attr( $validation['max_value'] ); ?>"
	<?php endif; ?>
	step="<?php echo esc_attr( $step ); ?>"
>

<?php if ( $description ) : ?>
	<p class="rp-field-description"><?php echo esc_html( $description ); ?></p>
<?php endif; ?>

<p x-show="errors.<?php echo esc_attr( $field_key ); ?>" x-text="errors.<?php echo esc_attr( $field_key ); ?>" class="rp-error-text"></p>

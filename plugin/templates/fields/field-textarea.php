<?php
/**
 * Field Template: Textarea
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$rows = $settings['rows'] ?? 4;
?>

<label class="rp-label" for="rp-field-<?php echo esc_attr( $field_key ); ?>">
	<?php echo esc_html( $label ); ?>
	<?php if ( $is_required ) : ?>
		<span class="rp-text-error">*</span>
	<?php endif; ?>
</label>

<textarea
	id="rp-field-<?php echo esc_attr( $field_key ); ?>"
	name="<?php echo esc_attr( $field_key ); ?>"
	x-model="<?php echo esc_attr( $x_model ); ?>"
	class="rp-input rp-textarea"
	:class="errors.<?php echo esc_attr( $field_key ); ?> ? 'rp-input-error' : ''"
	rows="<?php echo esc_attr( $rows ); ?>"
	<?php if ( $placeholder ) : ?>
		placeholder="<?php echo esc_attr( $placeholder ); ?>"
	<?php endif; ?>
	<?php if ( $is_required ) : ?>
		required
	<?php endif; ?>
	<?php if ( ! empty( $validation['min_length'] ) ) : ?>
		minlength="<?php echo esc_attr( $validation['min_length'] ); ?>"
	<?php endif; ?>
	<?php if ( ! empty( $validation['max_length'] ) ) : ?>
		maxlength="<?php echo esc_attr( $validation['max_length'] ); ?>"
	<?php endif; ?>
></textarea>

<?php if ( $description ) : ?>
	<p class="rp-field-description"><?php echo esc_html( $description ); ?></p>
<?php endif; ?>

<?php if ( ! empty( $validation['max_length'] ) ) : ?>
	<p class="rp-field-counter rp-text-xs rp-text-gray-500">
		<span x-text="(<?php echo esc_attr( $x_model ); ?> || '').length"></span> / <?php echo esc_html( $validation['max_length'] ); ?>
	</p>
<?php endif; ?>

<p x-show="errors.<?php echo esc_attr( $field_key ); ?>" x-text="errors.<?php echo esc_attr( $field_key ); ?>" class="rp-error-text"></p>

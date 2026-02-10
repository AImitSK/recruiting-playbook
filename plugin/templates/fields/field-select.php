<?php
/**
 * Field Template: Select
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$options     = $settings['options'] ?? [];
$allow_other = $settings['allow_other'] ?? false;
?>

<label class="rp-label" for="rp-field-<?php echo esc_attr( $field_key ); ?>">
	<?php echo esc_html( $label ); ?>
	<?php if ( $is_required ) : ?>
		<span class="rp-text-error">*</span>
	<?php endif; ?>
</label>

<select
	id="rp-field-<?php echo esc_attr( $field_key ); ?>"
	name="<?php echo esc_attr( $field_key ); ?>"
	x-model="<?php echo esc_attr( $x_model ); ?>"
	class="rp-input rp-select"
	:class="errors.<?php echo esc_attr( $field_key ); ?> ? 'rp-input-error' : ''"
	<?php if ( $is_required ) : ?>
		required
	<?php endif; ?>
>
	<option value="">
		<?php echo esc_html( $placeholder ?: __( 'Bitte wählen...', 'recruiting-playbook' ) ); ?>
	</option>
	<?php foreach ( $options as $option ) : ?>
		<option value="<?php echo esc_attr( $option['value'] ?? $option['label'] ); ?>">
			<?php echo esc_html( $option['label'] ); ?>
		</option>
	<?php endforeach; ?>
	<?php if ( $allow_other ) : ?>
		<option value="__other__">
			<?php esc_html_e( 'Sonstiges', 'recruiting-playbook' ); ?>
		</option>
	<?php endif; ?>
</select>

<?php if ( $allow_other ) : ?>
	<div x-show="<?php echo esc_attr( $x_model ); ?> === '__other__'" x-cloak class="rp-mt-2">
		<input
			type="text"
			x-model="formData.<?php echo esc_attr( $field_key ); ?>_other"
			class="rp-input"
			placeholder="<?php esc_attr_e( 'Bitte angeben...', 'recruiting-playbook' ); ?>"
		>
	</div>
<?php endif; ?>

<?php if ( $description ) : ?>
	<p class="rp-field-description"><?php echo esc_html( $description ); ?></p>
<?php endif; ?>

<p x-show="errors.<?php echo esc_attr( $field_key ); ?>" x-text="errors.<?php echo esc_attr( $field_key ); ?>" class="rp-error-text"></p>

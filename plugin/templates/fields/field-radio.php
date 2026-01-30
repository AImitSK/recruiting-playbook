<?php
/**
 * Field Template: Radio
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$options     = $settings['options'] ?? [];
$layout      = $settings['layout'] ?? 'vertical';
$allow_other = $settings['allow_other'] ?? false;

$layout_class = 'inline' === $layout ? 'rp-flex rp-flex-wrap rp-gap-4' : 'rp-space-y-2';
?>

<fieldset>
	<legend class="rp-label">
		<?php echo esc_html( $label ); ?>
		<?php if ( $is_required ) : ?>
			<span class="rp-text-error">*</span>
		<?php endif; ?>
	</legend>

	<div class="<?php echo esc_attr( $layout_class ); ?> rp-mt-2">
		<?php foreach ( $options as $index => $option ) : ?>
			<label class="rp-flex rp-items-center rp-gap-2 rp-cursor-pointer">
				<input
					type="radio"
					name="<?php echo esc_attr( $field_key ); ?>"
					value="<?php echo esc_attr( $option['value'] ?? $option['label'] ); ?>"
					x-model="<?php echo esc_attr( $x_model ); ?>"
					class="rp-radio"
					<?php if ( $is_required && 0 === $index ) : ?>
						required
					<?php endif; ?>
				>
				<span class="rp-text-sm"><?php echo esc_html( $option['label'] ); ?></span>
			</label>
		<?php endforeach; ?>

		<?php if ( $allow_other ) : ?>
			<label class="rp-flex rp-items-center rp-gap-2 rp-cursor-pointer">
				<input
					type="radio"
					name="<?php echo esc_attr( $field_key ); ?>"
					value="__other__"
					x-model="<?php echo esc_attr( $x_model ); ?>"
					class="rp-radio"
				>
				<span class="rp-text-sm"><?php esc_html_e( 'Sonstiges', 'recruiting-playbook' ); ?></span>
			</label>
		<?php endif; ?>
	</div>

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
</fieldset>

<?php if ( $description ) : ?>
	<p class="rp-field-description"><?php echo esc_html( $description ); ?></p>
<?php endif; ?>

<p x-show="errors.<?php echo esc_attr( $field_key ); ?>" x-text="errors.<?php echo esc_attr( $field_key ); ?>" class="rp-error-text"></p>

<?php
/**
 * Field Template: Checkbox
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$mode           = $settings['mode'] ?? 'single';
$options        = $settings['options'] ?? [];
$layout         = $settings['layout'] ?? 'vertical';
$checkbox_label = $settings['checkbox_label'] ?? $label;

$layout_class = 'inline' === $layout ? 'rp-flex rp-flex-wrap rp-gap-4' : 'rp-space-y-2';
?>

<?php if ( 'multi' === $mode && ! empty( $options ) ) : ?>
	<!-- Multi-Checkbox -->
	<fieldset>
		<legend class="rp-label">
			<?php echo esc_html( $label ); ?>
			<?php if ( $is_required ) : ?>
				<span class="rp-text-error">*</span>
			<?php endif; ?>
		</legend>

		<div class="<?php echo esc_attr( $layout_class ); ?> rp-mt-2">
			<?php foreach ( $options as $option ) : ?>
				<label class="rp-flex rp-items-center rp-gap-2 rp-cursor-pointer">
					<input
						type="checkbox"
						value="<?php echo esc_attr( $option['value'] ?? $option['label'] ); ?>"
						x-model="<?php echo esc_attr( $x_model ); ?>"
						class="rp-checkbox"
					>
					<span class="rp-text-sm"><?php echo esc_html( $option['label'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</fieldset>
<?php else : ?>
	<!-- Single Checkbox -->
	<label class="rp-flex rp-items-start rp-gap-3 rp-cursor-pointer">
		<input
			type="checkbox"
			id="rp-field-<?php echo esc_attr( $field_key ); ?>"
			name="<?php echo esc_attr( $field_key ); ?>"
			x-model="<?php echo esc_attr( $x_model ); ?>"
			class="rp-checkbox rp-mt-1"
			<?php if ( $is_required ) : ?>
				required
			<?php endif; ?>
		>
		<span class="rp-text-sm rp-leading-relaxed">
			<?php if ( $is_required ) : ?>
				<span class="rp-text-error">*</span>
			<?php endif; ?>
			<?php echo wp_kses_post( $checkbox_label ); ?>
		</span>
	</label>
<?php endif; ?>

<?php if ( $description && 'multi' === $mode ) : ?>
	<p class="rp-field-description"><?php echo esc_html( $description ); ?></p>
<?php endif; ?>

<p x-show="errors.<?php echo esc_attr( $field_key ); ?>" x-text="errors.<?php echo esc_attr( $field_key ); ?>" class="rp-error-text"></p>

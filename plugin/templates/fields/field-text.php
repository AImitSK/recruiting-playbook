<?php
/**
 * Field Template: Text
 *
 * Verfügbare Variablen:
 * - $field_key   (string) Eindeutiger Feldschlüssel
 * - $field_type  (string) Feldtyp
 * - $label       (string) Label
 * - $placeholder (string) Placeholder
 * - $description (string) Beschreibung/Hilfetext
 * - $is_required (bool)   Pflichtfeld
 * - $settings    (array)  Zusätzliche Einstellungen
 * - $validation  (array)  Validierungsregeln
 * - $value       (mixed)  Aktueller Wert
 * - $x_model     (string) Alpine.js x-model Binding
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;
?>

<label class="rp-label" for="rp-field-<?php echo esc_attr( $field_key ); ?>">
	<?php echo esc_html( $label ); ?>
	<?php if ( $is_required ) : ?>
		<span class="rp-text-error">*</span>
	<?php endif; ?>
</label>

<input
	type="text"
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
	<?php if ( ! empty( $validation['min_length'] ) ) : ?>
		minlength="<?php echo esc_attr( $validation['min_length'] ); ?>"
	<?php endif; ?>
	<?php if ( ! empty( $validation['max_length'] ) ) : ?>
		maxlength="<?php echo esc_attr( $validation['max_length'] ); ?>"
	<?php endif; ?>
	<?php if ( ! empty( $validation['pattern'] ) ) : ?>
		pattern="<?php echo esc_attr( $validation['pattern'] ); ?>"
	<?php endif; ?>
>

<?php if ( $description ) : ?>
	<p class="rp-field-description"><?php echo esc_html( $description ); ?></p>
<?php endif; ?>

<p x-show="errors.<?php echo esc_attr( $field_key ); ?>" x-text="errors.<?php echo esc_attr( $field_key ); ?>" class="rp-error-text"></p>

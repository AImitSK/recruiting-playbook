<?php
/**
 * Field Template: File Upload
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

$max_files     = $settings['max_files'] ?? 5;
$max_file_size = $validation['max_file_size'] ?? 10; // MB
$allowed_types = $validation['allowed_types'] ?? '.pdf,.doc,.docx,.jpg,.jpeg,.png';
$multiple      = ( $settings['multiple'] ?? false ) || $max_files > 1;

// Akzeptierte MIME-Types für Input.
$accept = $allowed_types;
?>

<label class="rp-label">
	<?php echo esc_html( $label ); ?>
	<?php if ( $is_required ) : ?>
		<span class="rp-text-error">*</span>
	<?php endif; ?>
</label>

<div
	x-data="rpFileUpload('<?php echo esc_attr( $field_key ); ?>', { maxFiles: <?php echo esc_attr( $max_files ); ?>, maxSize: <?php echo esc_attr( $max_file_size ); ?>, multiple: <?php echo $multiple ? 'true' : 'false'; ?> })"
	class="rp-file-upload"
>
	<!-- Dropzone -->
	<div
		@dragover.prevent="isDragging = true"
		@dragleave.prevent="isDragging = false"
		@drop.prevent="handleDrop($event)"
		:class="{ 'rp-border-primary rp-bg-primary-light': isDragging, 'rp-border-success rp-bg-success-light': files.length > 0 }"
		class="rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-lg rp-p-6 rp-text-center rp-cursor-pointer rp-transition-colors"
	>
		<template x-if="files.length === 0">
			<div>
				<svg class="rp-w-10 rp-h-10 rp-text-gray-400 rp-mx-auto rp-mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
				</svg>
				<p class="rp-text-gray-600 rp-mb-2">
					<?php esc_html_e( 'Datei hierher ziehen oder', 'recruiting-playbook' ); ?>
				</p>
				<label class="rp-text-primary hover:rp-text-primary-hover rp-font-medium rp-cursor-pointer">
					<?php esc_html_e( 'Datei auswählen', 'recruiting-playbook' ); ?>
					<input
						type="file"
						@change="handleSelect($event)"
						accept="<?php echo esc_attr( $accept ); ?>"
						<?php if ( $multiple ) : ?>
							multiple
						<?php endif; ?>
						class="rp-hidden"
					>
				</label>
				<p class="rp-text-xs rp-text-gray-400 rp-mt-2">
					<?php
					printf(
						/* translators: 1: allowed types, 2: max file size */
						esc_html__( '%1$s (max. %2$d MB)', 'recruiting-playbook' ),
						esc_html( str_replace( ',', ', ', $allowed_types ) ),
						esc_html( $max_file_size )
					);
					?>
					<?php if ( $multiple ) : ?>
						<br>
						<?php
						printf(
							/* translators: %d: max number of files */
							esc_html__( 'Maximal %d Dateien', 'recruiting-playbook' ),
							esc_html( $max_files )
						);
						?>
					<?php endif; ?>
				</p>
			</div>
		</template>

		<!-- Hochgeladene Dateien -->
		<template x-if="files.length > 0">
			<div class="rp-space-y-2">
				<template x-for="(file, index) in files" :key="index">
					<div class="rp-flex rp-items-center rp-justify-between rp-bg-white rp-rounded rp-px-3 rp-py-2 rp-border rp-border-gray-200">
						<div class="rp-flex rp-items-center rp-gap-3">
							<svg class="rp-w-5 rp-h-5 rp-text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
							</svg>
							<div class="rp-text-left">
								<p class="rp-text-sm rp-font-medium rp-text-gray-900" x-text="file.name"></p>
								<p class="rp-text-xs rp-text-gray-500" x-text="formatSize(file.size)"></p>
							</div>
						</div>
						<button
							type="button"
							@click="removeFile(index)"
							class="rp-p-1 rp-text-error hover:rp-bg-error-light rp-rounded"
							title="<?php esc_attr_e( 'Datei entfernen', 'recruiting-playbook' ); ?>"
						>
							<svg class="rp-w-4 rp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
							</svg>
						</button>
					</div>
				</template>

				<?php if ( $multiple ) : ?>
					<label class="rp-inline-block rp-text-sm rp-text-primary hover:rp-text-primary-hover rp-cursor-pointer rp-mt-2">
						+ <?php esc_html_e( 'Weitere Datei hinzufügen', 'recruiting-playbook' ); ?>
						<input
							type="file"
							@change="handleSelect($event)"
							accept="<?php echo esc_attr( $accept ); ?>"
							class="rp-hidden"
						>
					</label>
				<?php endif; ?>
			</div>
		</template>
	</div>

	<!-- Fehler -->
	<p x-show="error" x-text="error" class="rp-error-text rp-mt-2"></p>
</div>

<?php if ( $description ) : ?>
	<p class="rp-field-description"><?php echo esc_html( $description ); ?></p>
<?php endif; ?>

<p x-show="errors.<?php echo esc_attr( $field_key ); ?>" x-text="errors.<?php echo esc_attr( $field_key ); ?>" class="rp-error-text"></p>

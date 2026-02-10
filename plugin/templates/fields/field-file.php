<?php
/**
 * Field Template: File Upload
 *
 * Uses inline Alpine.js scope to avoid data collision with parent form.
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

// Generate unique scope ID to avoid conflicts.
$scope_id = 'fileUpload_' . esc_attr( $field_key );
?>

<label class="rp-label">
	<?php echo esc_html( $label ); ?>
	<?php if ( $is_required ) : ?>
		<span class="rp-text-error">*</span>
	<?php endif; ?>
</label>

<div
	x-data="{
		_files: [],
		_dragging: false,
		_error: null,
		_maxFiles: <?php echo esc_attr( $max_files ); ?>,
		_maxSize: <?php echo esc_attr( $max_file_size ); ?> * 1024 * 1024,
		_multiple: <?php echo $multiple ? 'true' : 'false'; ?>,
		_fieldKey: '<?php echo esc_attr( $field_key ); ?>',

		init() {
			// Sync with parent form (applicationForm)
			if (typeof this.files !== 'undefined' && this.files !== null) {
				// Check for resume/lebenslauf field
				if (this._fieldKey === 'resume' || this._fieldKey === 'lebenslauf') {
					// Combine resume and documents into _files array
					const existingFiles = [];
					if (this.files.resume) {
						existingFiles.push(this.files.resume);
					}
					if (this.files.documents && this.files.documents.length > 0) {
						existingFiles.push(...this.files.documents);
					}
					this._files = existingFiles;
				} else if (this.files.documents) {
					this._files = this.files.documents;
				}
			}
		},

		syncToParent() {
			if (typeof this.files !== 'undefined' && this.files !== null) {
				if (this._fieldKey === 'resume' || this._fieldKey === 'lebenslauf') {
					// First file goes to resume, rest go to documents
					this.files.resume = this._files.length > 0 ? this._files[0] : null;
					this.files.documents = this._files.length > 1 ? this._files.slice(1) : [];
				} else {
					this.files.documents = this._files;
				}
			}
		},

		handleSelect(event) {
			this.addFiles(event.target.files);
			event.target.value = '';
		},

		handleDrop(event) {
			this._dragging = false;
			this.addFiles(event.dataTransfer.files);
		},

		addFiles(fileList) {
			this._error = null;

			for (const file of fileList) {
				if (this._files.length >= this._maxFiles) {
					this._error = 'Maximal ' + this._maxFiles + ' Dateien erlaubt';
					break;
				}

				if (file.size > this._maxSize) {
					this._error = 'Datei zu groß (max. ' + (this._maxSize / 1024 / 1024) + ' MB)';
					continue;
				}

				if (this._multiple) {
					this._files.push(file);
				} else {
					this._files = [file];
				}
			}

			this.syncToParent();
			this.clearParentError();
		},

		removeFile(index) {
			this._files.splice(index, 1);
			this.syncToParent();
		},

		clearParentError() {
			if (typeof this.errors !== 'undefined' && this.errors[this._fieldKey]) {
				delete this.errors[this._fieldKey];
			}
		},

		formatSize(bytes) {
			if (bytes < 1024) return bytes + ' B';
			if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
			return (bytes / 1024 / 1024).toFixed(1) + ' MB';
		}
	}"
	class="rp-file-upload"
>
	<!-- Dropzone -->
	<div
		x-on:dragover.prevent="_dragging = true"
		x-on:dragleave.prevent="_dragging = false"
		x-on:drop.prevent="handleDrop($event)"
		:class="{ 'rp-border-primary rp-bg-primary-light': _dragging, 'rp-border-success rp-bg-success-light': _files.length > 0 }"
		class="rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-lg rp-p-6 rp-text-center rp-cursor-pointer rp-transition-colors"
	>
		<template x-if="_files.length === 0">
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
						x-on:change="handleSelect($event)"
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
		<template x-if="_files.length > 0">
			<div class="rp-space-y-2">
				<template x-for="(file, index) in _files" :key="index">
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
							x-on:click="removeFile(index)"
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
							x-on:change="handleSelect($event)"
							accept="<?php echo esc_attr( $accept ); ?>"
							class="rp-hidden"
						>
					</label>
				<?php endif; ?>
			</div>
		</template>
	</div>

	<!-- Fehler -->
	<p x-show="_error" x-text="_error" class="rp-error-text rp-mt-2"></p>
</div>

<?php if ( $description ) : ?>
	<p class="rp-field-description"><?php echo esc_html( $description ); ?></p>
<?php endif; ?>

<p x-show="errors.<?php echo esc_attr( $field_key ); ?>" x-text="errors.<?php echo esc_attr( $field_key ); ?>" class="rp-error-text"></p>

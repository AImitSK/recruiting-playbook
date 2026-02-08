<?php
/**
 * Match Modal Template
 *
 * Wird einmal am Ende der Seite eingefügt (im Footer).
 * Verwendet Alpine.js für Interaktivität.
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

// Nur anzeigen wenn Feature verfügbar.
if ( ! function_exists( 'rp_has_cv_matching' ) || ! rp_has_cv_matching() ) {
	return;
}
?>

<div
	x-data="matchModal"
	x-show="isOpen"
	x-cloak
	class="rp-match-modal-overlay"
	@click.self="close()"
	@open-match-modal.window="open($event.detail.jobId, $event.detail.jobTitle)"
	x-transition:enter="rp-transition rp-ease-out rp-duration-200"
	x-transition:enter-start="rp-opacity-0"
	x-transition:enter-end="rp-opacity-100"
	x-transition:leave="rp-transition rp-ease-in rp-duration-150"
	x-transition:leave-start="rp-opacity-100"
	x-transition:leave-end="rp-opacity-0"
>
	<div
		class="rp-match-modal"
		role="dialog"
		aria-modal="true"
		aria-labelledby="match-modal-title"
		@click.stop
		x-transition:enter="rp-transition rp-ease-out rp-duration-200"
		x-transition:enter-start="rp-opacity-0 rp-scale-95"
		x-transition:enter-end="rp-opacity-100 rp-scale-100"
		x-transition:leave="rp-transition rp-ease-in rp-duration-150"
		x-transition:leave-start="rp-opacity-100 rp-scale-100"
		x-transition:leave-end="rp-opacity-0 rp-scale-95"
	>
		<!-- Header -->
		<div class="rp-match-modal__header">
			<h2 id="match-modal-title" class="rp-match-modal__title">
				<?php esc_html_e( 'Passe ich zu diesem Job?', 'recruiting-playbook' ); ?>
			</h2>
			<button type="button" class="rp-match-modal__close" @click.stop="close()">
				<span class="rp-sr-only"><?php esc_html_e( 'Schließen', 'recruiting-playbook' ); ?></span>
				<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
				</svg>
			</button>
		</div>

		<!-- Body -->
		<div class="rp-match-modal__body">

			<!-- Job-Titel -->
			<p class="rp-match-modal__job-title">
				<strong><?php esc_html_e( 'Stelle:', 'recruiting-playbook' ); ?></strong>
				<span x-text="jobTitle"></span>
			</p>

			<!-- Status: Idle (Upload) -->
			<template x-if="status === 'idle'">
				<div>
					<!-- Datenschutz-Hinweis -->
					<div class="rp-match-info-box">
						<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
						</svg>
						<div>
							<strong><?php esc_html_e( 'Datenschutz', 'recruiting-playbook' ); ?></strong>
							<p><?php esc_html_e( 'Ihre persönlichen Daten (Name, Adresse, etc.) werden automatisch entfernt. Nur Ihre beruflichen Qualifikationen werden analysiert. Nach der Analyse werden alle Daten sofort gelöscht.', 'recruiting-playbook' ); ?></p>
						</div>
					</div>

					<!-- Upload Zone -->
					<div
						class="rp-match-upload-zone"
						:class="{
							'rp-match-upload-zone--dragging': isDragging,
							'rp-match-upload-zone--has-file': file
						}"
						@dragover="handleDragOver($event)"
						@dragleave="handleDragLeave()"
						@drop="handleDrop($event)"
					>
						<template x-if="!file">
							<div class="rp-match-upload-zone__empty">
								<svg class="rp-match-upload-zone__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
								</svg>
								<p class="rp-match-upload-zone__text">
									<?php esc_html_e( 'Lebenslauf hier ablegen', 'recruiting-playbook' ); ?>
								</p>
								<p class="rp-match-upload-zone__hint">
									<?php esc_html_e( 'oder', 'recruiting-playbook' ); ?>
								</p>
								<label class="rp-match-file-label">
									<?php esc_html_e( 'Datei auswählen', 'recruiting-playbook' ); ?>
									<input
										type="file"
										class="rp-match-file-input"
										accept=".pdf,.jpg,.jpeg,.png,.docx"
										@change="handleFileSelect($event)"
									>
								</label>
								<p class="rp-match-upload-zone__formats">
									<?php esc_html_e( 'PDF, JPG, PNG oder DOCX (max. 10 MB)', 'recruiting-playbook' ); ?>
								</p>
							</div>
						</template>

						<template x-if="file">
							<div class="rp-match-upload-zone__file">
								<svg class="rp-match-upload-zone__file-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
								</svg>
								<div class="rp-match-upload-zone__file-info">
									<span class="rp-match-upload-zone__file-name" x-text="fileName"></span>
									<span class="rp-match-upload-zone__file-size" x-text="fileSize"></span>
								</div>
								<button type="button" class="rp-match-upload-zone__remove" @click="removeFile()">
									<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
									</svg>
								</button>
							</div>
						</template>
					</div>

					<!-- Error -->
					<template x-if="error">
						<div class="rp-match-error-box" x-text="error"></div>
					</template>

					<!-- Submit Button -->
					<button
						type="button"
						class="rp-match-submit-btn"
						:disabled="!file"
						@click="startAnalysis()"
					>
						<?php esc_html_e( 'Analyse starten', 'recruiting-playbook' ); ?>
					</button>
				</div>
			</template>

			<!-- Status: Uploading / Processing -->
			<template x-if="status === 'uploading' || status === 'processing'">
				<div class="rp-match-processing">
					<div class="rp-match-processing__spinner"></div>
					<p class="rp-match-processing__text">
						<span x-show="status === 'uploading'"><?php esc_html_e( 'Dokument wird hochgeladen...', 'recruiting-playbook' ); ?></span>
						<span x-show="status === 'processing'"><?php esc_html_e( 'Analyse läuft...', 'recruiting-playbook' ); ?></span>
					</p>
					<div class="rp-match-progress">
						<div class="rp-match-progress__bar" :style="{ width: progress + '%' }"></div>
					</div>
					<p class="rp-match-processing__hint">
						<?php esc_html_e( 'Dies kann einige Sekunden dauern.', 'recruiting-playbook' ); ?>
					</p>
				</div>
			</template>

			<!-- Status: Completed -->
			<template x-if="status === 'completed' && result">
				<div class="rp-match-result" :class="resultColor">
					<div class="rp-match-result__score">
						<span class="rp-match-result__score-value" x-text="result.score + '%'"></span>
						<div class="rp-match-result__score-bar">
							<div class="rp-match-result__score-fill" :style="{ width: result.score + '%' }"></div>
						</div>
					</div>

					<p class="rp-match-result__category" x-text="resultLabel"></p>
					<p class="rp-match-result__message" x-text="result.message"></p>

					<div class="rp-match-result__actions">
						<a
							:href="'<?php echo esc_url( home_url( '/' ) ); ?>?p=' + jobId + '#apply-form'"
							class="wp-element-button"
						>
							<?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?>
						</a>
						<button type="button" class="rp-btn--secondary" @click="reset()">
							<?php esc_html_e( 'Neue Analyse', 'recruiting-playbook' ); ?>
						</button>
					</div>
				</div>
			</template>

			<!-- Status: Error -->
			<template x-if="status === 'error'">
				<div class="rp-match-error">
					<svg class="rp-match-error__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
					</svg>
					<p class="rp-match-error__text" x-text="error"></p>
					<button type="button" class="rp-btn--secondary" @click="reset()">
						<?php esc_html_e( 'Erneut versuchen', 'recruiting-playbook' ); ?>
					</button>
				</div>
			</template>

		</div>
	</div>
</div>

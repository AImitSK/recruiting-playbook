<?php
/**
 * Template: Einzelne Stelle
 *
 * Dieses Template kann im Theme überschrieben werden:
 * theme/recruiting-playbook/single-job_listing.php
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	// Meta-Daten laden.
	$salary_min      = get_post_meta( get_the_ID(), '_rp_salary_min', true );
	$salary_max      = get_post_meta( get_the_ID(), '_rp_salary_max', true );
	$salary_currency = get_post_meta( get_the_ID(), '_rp_salary_currency', true ) ?: 'EUR';
	$salary_period   = get_post_meta( get_the_ID(), '_rp_salary_period', true ) ?: 'month';
	$hide_salary     = get_post_meta( get_the_ID(), '_rp_hide_salary', true );
	$deadline        = get_post_meta( get_the_ID(), '_rp_application_deadline', true );
	$contact_person  = get_post_meta( get_the_ID(), '_rp_contact_person', true );
	$contact_email   = get_post_meta( get_the_ID(), '_rp_contact_email', true );
	$contact_phone   = get_post_meta( get_the_ID(), '_rp_contact_phone', true );
	$remote_option   = get_post_meta( get_the_ID(), '_rp_remote_option', true );
	$start_date      = get_post_meta( get_the_ID(), '_rp_start_date', true );

	// Taxonomien.
	$locations  = get_the_terms( get_the_ID(), 'job_location' );
	$types      = get_the_terms( get_the_ID(), 'employment_type' );
	$categories = get_the_terms( get_the_ID(), 'job_category' );

	// Gehalt formatieren.
	$salary_display = '';
	if ( ! $hide_salary && ( $salary_min || $salary_max ) ) {
		$period_labels = [
			'hour'  => __( '/Std.', 'recruiting-playbook' ),
			'month' => __( '/Monat', 'recruiting-playbook' ),
			'year'  => __( '/Jahr', 'recruiting-playbook' ),
		];
		$period_label  = $period_labels[ $salary_period ] ?? '';

		if ( $salary_min && $salary_max ) {
			$salary_display = number_format( (float) $salary_min, 0, ',', '.' ) . ' - ' . number_format( (float) $salary_max, 0, ',', '.' ) . ' ' . $salary_currency . $period_label;
		} elseif ( $salary_min ) {
			$salary_display = __( 'Ab ', 'recruiting-playbook' ) . number_format( (float) $salary_min, 0, ',', '.' ) . ' ' . $salary_currency . $period_label;
		} elseif ( $salary_max ) {
			$salary_display = __( 'Bis ', 'recruiting-playbook' ) . number_format( (float) $salary_max, 0, ',', '.' ) . ' ' . $salary_currency . $period_label;
		}
	}

	// Remote Labels.
	$remote_labels = [
		'no'     => __( 'Vor Ort', 'recruiting-playbook' ),
		'hybrid' => __( 'Hybrid (teilweise Remote)', 'recruiting-playbook' ),
		'full'   => __( '100% Remote möglich', 'recruiting-playbook' ),
	];
	?>

	<article <?php post_class( 'rp-job-single' ); ?>>
		<div class="rp-container" style="max-width: 1000px; margin: 0 auto; padding: 20px;">

			<!-- Zurück-Link -->
			<nav class="rp-breadcrumb" style="margin-bottom: 20px;">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'job_listing' ) ); ?>" style="color: #2271b1; text-decoration: none;">
					&larr; <?php esc_html_e( 'Alle Stellen', 'recruiting-playbook' ); ?>
				</a>
			</nav>

			<div class="rp-job-layout" style="display: grid; gap: 30px; grid-template-columns: 1fr 300px;">

				<!-- Hauptinhalt -->
				<div class="rp-job-content">

					<header class="rp-job-header" style="margin-bottom: 30px;">
						<h1 class="rp-job-title" style="font-size: 2rem; font-weight: 700; margin: 0 0 16px 0;">
							<?php the_title(); ?>
						</h1>

						<div class="rp-job-meta" style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.875rem; color: #6b7280;">

							<?php if ( $locations && ! is_wp_error( $locations ) ) : ?>
								<span class="rp-job-meta-item" style="display: flex; align-items: center;">
									<svg style="width: 18px; height: 18px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
									</svg>
									<?php echo esc_html( $locations[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $types && ! is_wp_error( $types ) ) : ?>
								<span class="rp-job-meta-item" style="display: flex; align-items: center;">
									<svg style="width: 18px; height: 18px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
									</svg>
									<?php echo esc_html( $types[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $remote_option && isset( $remote_labels[ $remote_option ] ) ) : ?>
								<span class="rp-job-meta-item" style="display: flex; align-items: center; <?php echo 'no' !== $remote_option ? 'color: #059669;' : ''; ?>">
									<svg style="width: 18px; height: 18px; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
									</svg>
									<?php echo esc_html( $remote_labels[ $remote_option ] ); ?>
								</span>
							<?php endif; ?>

						</div>
					</header>

					<!-- Stellenbeschreibung -->
					<div class="rp-job-description" style="line-height: 1.7; color: #374151;">
						<?php the_content(); ?>
					</div>

				</div>

				<!-- Sidebar -->
				<aside class="rp-job-sidebar">

					<!-- Jetzt bewerben -->
					<div class="rp-sidebar-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 20px;">
						<a href="#rp-apply-form" class="rp-btn rp-btn-primary" style="display: block; width: 100%; padding: 14px 20px; background: #2271b1; color: #fff; text-align: center; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 1rem;">
							<?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?>
						</a>

						<?php if ( $deadline ) : ?>
							<p style="margin-top: 12px; font-size: 0.875rem; color: #6b7280; text-align: center;">
								<?php
								printf(
									/* translators: %s: Application deadline */
									esc_html__( 'Bewerbungsfrist: %s', 'recruiting-playbook' ),
									esc_html( date_i18n( get_option( 'date_format' ), strtotime( $deadline ) ) )
								);
								?>
							</p>
						<?php endif; ?>
					</div>

					<!-- Details -->
					<div class="rp-sidebar-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 20px;">
						<h3 style="font-size: 1rem; font-weight: 600; margin: 0 0 16px 0;"><?php esc_html_e( 'Details', 'recruiting-playbook' ); ?></h3>

						<dl style="margin: 0; font-size: 0.875rem;">

							<?php if ( $salary_display ) : ?>
								<dt style="font-weight: 500; color: #374151;"><?php esc_html_e( 'Gehalt', 'recruiting-playbook' ); ?></dt>
								<dd style="margin: 0 0 12px 0; color: #6b7280;"><?php echo esc_html( $salary_display ); ?></dd>
							<?php endif; ?>

							<?php if ( $start_date ) : ?>
								<dt style="font-weight: 500; color: #374151;"><?php esc_html_e( 'Startdatum', 'recruiting-playbook' ); ?></dt>
								<dd style="margin: 0 0 12px 0; color: #6b7280;"><?php echo esc_html( $start_date ); ?></dd>
							<?php endif; ?>

							<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
								<dt style="font-weight: 500; color: #374151;"><?php esc_html_e( 'Berufsfeld', 'recruiting-playbook' ); ?></dt>
								<dd style="margin: 0 0 12px 0; color: #6b7280;"><?php echo esc_html( $categories[0]->name ); ?></dd>
							<?php endif; ?>

						</dl>
					</div>

					<!-- Kontakt -->
					<?php if ( $contact_person || $contact_email || $contact_phone ) : ?>
						<div class="rp-sidebar-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
							<h3 style="font-size: 1rem; font-weight: 600; margin: 0 0 16px 0;"><?php esc_html_e( 'Ansprechpartner', 'recruiting-playbook' ); ?></h3>

							<?php if ( $contact_person ) : ?>
								<p style="margin: 0 0 8px 0; font-weight: 500;"><?php echo esc_html( $contact_person ); ?></p>
							<?php endif; ?>

							<?php if ( $contact_email ) : ?>
								<p style="margin: 0 0 4px 0; font-size: 0.875rem;">
									<a href="mailto:<?php echo esc_attr( $contact_email ); ?>" style="color: #2271b1; text-decoration: none;">
										<?php echo esc_html( $contact_email ); ?>
									</a>
								</p>
							<?php endif; ?>

							<?php if ( $contact_phone ) : ?>
								<p style="margin: 0; font-size: 0.875rem;">
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $contact_phone ) ); ?>" style="color: #2271b1; text-decoration: none;">
										<?php echo esc_html( $contact_phone ); ?>
									</a>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

				</aside>

			</div>

			<!-- Bewerbungsformular -->
			<div id="rp-apply-form" class="rp-apply-section" style="margin-top: 40px; padding: 40px; background: #f9fafb; border-radius: 8px;" data-job-id="<?php echo esc_attr( get_the_ID() ); ?>">
				<h2 style="font-size: 1.5rem; margin: 0 0 24px 0;"><?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?></h2>

				<div x-data="applicationForm" x-cloak>
					<!-- Erfolgs-Meldung -->
					<template x-if="submitted">
						<div class="rp-form-success" style="text-align: center; padding: 40px 20px;">
							<svg style="width: 64px; height: 64px; color: #00a32a; margin: 0 auto 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
							</svg>
							<h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 8px;"><?php esc_html_e( 'Bewerbung erfolgreich gesendet!', 'recruiting-playbook' ); ?></h3>
							<p style="color: #6b7280;"><?php esc_html_e( 'Vielen Dank für Ihre Bewerbung. Sie erhalten in Kürze eine Bestätigung per E-Mail.', 'recruiting-playbook' ); ?></p>
						</div>
					</template>

					<!-- Formular -->
					<template x-if="!submitted">
						<div>
							<!-- Fehler-Meldung -->
							<div x-show="error" x-cloak class="rp-form-error" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; color: #dc2626;">
								<span x-text="error"></span>
							</div>

							<!-- Fortschrittsanzeige -->
							<div class="rp-form-progress" style="margin-bottom: 30px;">
								<div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.875rem;">
									<span><?php esc_html_e( 'Schritt', 'recruiting-playbook' ); ?> <span x-text="step"></span> <?php esc_html_e( 'von', 'recruiting-playbook' ); ?> <span x-text="totalSteps"></span></span>
									<span x-text="progress + '%'"></span>
								</div>
								<div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
									<div style="height: 100%; background: #2271b1; transition: width 0.3s;" :style="'width: ' + progress + '%'"></div>
								</div>
							</div>

							<form @submit.prevent="submit">
								<?php echo \RecruitingPlaybook\Services\SpamProtection::getHoneypotField(); ?>
								<?php echo \RecruitingPlaybook\Services\SpamProtection::getTimestampField(); ?>

								<!-- Schritt 1: Persönliche Daten -->
								<div x-show="step === 1" x-transition>
									<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Persönliche Daten', 'recruiting-playbook' ); ?></h3>

									<div style="display: grid; gap: 16px;">
										<!-- Anrede -->
										<div class="rp-form-field">
											<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Anrede', 'recruiting-playbook' ); ?></label>
											<select x-model="formData.salutation" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
												<option value=""><?php esc_html_e( 'Bitte wählen', 'recruiting-playbook' ); ?></option>
												<option value="Herr"><?php esc_html_e( 'Herr', 'recruiting-playbook' ); ?></option>
												<option value="Frau"><?php esc_html_e( 'Frau', 'recruiting-playbook' ); ?></option>
												<option value="Divers"><?php esc_html_e( 'Divers', 'recruiting-playbook' ); ?></option>
											</select>
										</div>

										<!-- Vorname & Nachname -->
										<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
											<div class="rp-form-field">
												<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Vorname', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
												<input type="text" x-model="formData.first_name" :class="{'rp-field-error': errors.first_name}" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;" required>
												<span x-show="errors.first_name" x-text="errors.first_name" style="color: #dc2626; font-size: 0.875rem;"></span>
											</div>
											<div class="rp-form-field">
												<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Nachname', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
												<input type="text" x-model="formData.last_name" :class="{'rp-field-error': errors.last_name}" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;" required>
												<span x-show="errors.last_name" x-text="errors.last_name" style="color: #dc2626; font-size: 0.875rem;"></span>
											</div>
										</div>

										<!-- E-Mail -->
										<div class="rp-form-field">
											<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'E-Mail-Adresse', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
											<input type="email" x-model="formData.email" :class="{'rp-field-error': errors.email}" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;" required>
											<span x-show="errors.email" x-text="errors.email" style="color: #dc2626; font-size: 0.875rem;"></span>
										</div>

										<!-- Telefon -->
										<div class="rp-form-field">
											<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?></label>
											<input type="tel" x-model="formData.phone" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
										</div>
									</div>
								</div>

								<!-- Schritt 2: Dokumente -->
								<div x-show="step === 2" x-transition>
									<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Bewerbungsunterlagen', 'recruiting-playbook' ); ?></h3>

									<!-- Lebenslauf -->
									<div class="rp-form-field" style="margin-bottom: 20px;">
										<label style="display: block; font-weight: 500; margin-bottom: 8px;"><?php esc_html_e( 'Lebenslauf', 'recruiting-playbook' ); ?></label>
										<div
											@dragover.prevent="$el.classList.add('rp-dropzone-active')"
											@dragleave.prevent="$el.classList.remove('rp-dropzone-active')"
											@drop.prevent="$el.classList.remove('rp-dropzone-active'); handleDrop($event, 'resume')"
											style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 24px; text-align: center; cursor: pointer; transition: border-color 0.2s;"
											:style="files.resume ? 'border-color: #00a32a; background: #f0fdf4;' : ''"
										>
											<template x-if="!files.resume">
												<div>
													<svg style="width: 40px; height: 40px; color: #9ca3af; margin: 0 auto 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
														<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
													</svg>
													<p style="margin: 0 0 8px 0; color: #6b7280;"><?php esc_html_e( 'Datei hierher ziehen oder', 'recruiting-playbook' ); ?></p>
													<label style="color: #2271b1; cursor: pointer; font-weight: 500;">
														<?php esc_html_e( 'Datei auswählen', 'recruiting-playbook' ); ?>
														<input type="file" @change="handleFileSelect($event, 'resume')" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;">
													</label>
													<p style="margin: 8px 0 0 0; font-size: 0.75rem; color: #9ca3af;"><?php esc_html_e( 'PDF, DOC, DOCX, JPG, PNG (max. 10 MB)', 'recruiting-playbook' ); ?></p>
												</div>
											</template>
											<template x-if="files.resume">
												<div style="display: flex; align-items: center; justify-content: space-between;">
													<div style="display: flex; align-items: center; gap: 12px;">
														<svg style="width: 24px; height: 24px; color: #00a32a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
														</svg>
														<div style="text-align: left;">
															<p style="margin: 0; font-weight: 500;" x-text="files.resume.name"></p>
															<p style="margin: 0; font-size: 0.75rem; color: #6b7280;" x-text="formatFileSize(files.resume.size)"></p>
														</div>
													</div>
													<button type="button" @click="removeFile('resume')" style="padding: 4px; color: #dc2626; background: none; border: none; cursor: pointer;">
														<svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
														</svg>
													</button>
												</div>
											</template>
										</div>
										<span x-show="errors.resume" x-text="errors.resume" style="color: #dc2626; font-size: 0.875rem;"></span>
									</div>

									<!-- Weitere Dokumente -->
									<div class="rp-form-field" style="margin-bottom: 20px;">
										<label style="display: block; font-weight: 500; margin-bottom: 8px;"><?php esc_html_e( 'Weitere Dokumente (Zeugnisse, Zertifikate)', 'recruiting-playbook' ); ?></label>
										<div
											@dragover.prevent
											@drop.prevent="handleDrop($event, 'documents')"
											style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 24px; text-align: center;"
										>
											<label style="color: #2271b1; cursor: pointer; font-weight: 500;">
												<?php esc_html_e( 'Dateien auswählen', 'recruiting-playbook' ); ?>
												<input type="file" @change="handleFileSelect($event, 'documents')" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple style="display: none;">
											</label>
											<p style="margin: 8px 0 0 0; font-size: 0.75rem; color: #9ca3af;"><?php esc_html_e( 'Mehrere Dateien möglich (max. 5 Dateien, je 10 MB)', 'recruiting-playbook' ); ?></p>
										</div>

										<!-- Hochgeladene Dateien -->
										<template x-if="files.documents.length > 0">
											<ul style="list-style: none; padding: 0; margin: 12px 0 0 0;">
												<template x-for="(file, index) in files.documents" :key="index">
													<li style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #f9fafb; border-radius: 4px; margin-bottom: 4px;">
														<span x-text="file.name" style="font-size: 0.875rem;"></span>
														<button type="button" @click="removeFile('documents', index)" style="padding: 4px; color: #dc2626; background: none; border: none; cursor: pointer;">
															<svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
															</svg>
														</button>
													</li>
												</template>
											</ul>
										</template>
									</div>

									<!-- Anschreiben -->
									<div class="rp-form-field">
										<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Anschreiben / Nachricht', 'recruiting-playbook' ); ?></label>
										<textarea x-model="formData.cover_letter" rows="5" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; resize: vertical;" placeholder="<?php esc_attr_e( 'Optional: Fügen Sie ein kurzes Anschreiben hinzu...', 'recruiting-playbook' ); ?>"></textarea>
									</div>
								</div>

								<!-- Schritt 3: Datenschutz & Absenden -->
								<div x-show="step === 3" x-transition>
									<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Datenschutz & Absenden', 'recruiting-playbook' ); ?></h3>

									<!-- Zusammenfassung -->
									<div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
										<h4 style="font-size: 1rem; font-weight: 600; margin: 0 0 12px 0;"><?php esc_html_e( 'Ihre Angaben', 'recruiting-playbook' ); ?></h4>
										<dl style="margin: 0; font-size: 0.875rem;">
											<div style="display: flex; padding: 4px 0;">
												<dt style="width: 120px; color: #6b7280;"><?php esc_html_e( 'Name:', 'recruiting-playbook' ); ?></dt>
												<dd style="margin: 0;" x-text="formData.salutation + ' ' + formData.first_name + ' ' + formData.last_name"></dd>
											</div>
											<div style="display: flex; padding: 4px 0;">
												<dt style="width: 120px; color: #6b7280;"><?php esc_html_e( 'E-Mail:', 'recruiting-playbook' ); ?></dt>
												<dd style="margin: 0;" x-text="formData.email"></dd>
											</div>
											<template x-if="formData.phone">
												<div style="display: flex; padding: 4px 0;">
													<dt style="width: 120px; color: #6b7280;"><?php esc_html_e( 'Telefon:', 'recruiting-playbook' ); ?></dt>
													<dd style="margin: 0;" x-text="formData.phone"></dd>
												</div>
											</template>
											<template x-if="files.resume">
												<div style="display: flex; padding: 4px 0;">
													<dt style="width: 120px; color: #6b7280;"><?php esc_html_e( 'Lebenslauf:', 'recruiting-playbook' ); ?></dt>
													<dd style="margin: 0;" x-text="files.resume.name"></dd>
												</div>
											</template>
											<template x-if="files.documents.length > 0">
												<div style="display: flex; padding: 4px 0;">
													<dt style="width: 120px; color: #6b7280;"><?php esc_html_e( 'Dokumente:', 'recruiting-playbook' ); ?></dt>
													<dd style="margin: 0;" x-text="files.documents.length + ' Datei(en)'"></dd>
												</div>
											</template>
										</dl>
									</div>

									<!-- Datenschutz-Checkbox -->
									<div class="rp-form-field" style="margin-bottom: 20px;">
										<label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
											<input type="checkbox" x-model="formData.privacy_consent" style="width: 20px; height: 20px; margin-top: 2px; accent-color: #2271b1;">
											<span style="font-size: 0.875rem; line-height: 1.5;">
												<?php
												$privacy_url = get_privacy_policy_url();
												printf(
													/* translators: %s: privacy policy link */
													esc_html__( 'Ich habe die %s gelesen und stimme der Verarbeitung meiner Daten zum Zweck der Bewerbung zu. *', 'recruiting-playbook' ),
													$privacy_url ? '<a href="' . esc_url( $privacy_url ) . '" target="_blank" style="color: #2271b1;">' . esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' ) . '</a>' : esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' )
												);
												?>
											</span>
										</label>
										<span x-show="errors.privacy_consent" x-text="errors.privacy_consent" style="color: #dc2626; font-size: 0.875rem; display: block; margin-top: 4px;"></span>
									</div>
								</div>

								<!-- Navigation -->
								<div style="display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
									<button
										type="button"
										x-show="step > 1"
										@click="prevStep"
										style="padding: 12px 24px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 500; cursor: pointer;"
									>
										<?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
									</button>
									<div x-show="step === 1"></div>

									<button
										x-show="step < totalSteps"
										type="button"
										@click="nextStep"
										style="padding: 12px 24px; background: #2271b1; color: #fff; border: none; border-radius: 6px; font-weight: 500; cursor: pointer;"
									>
										<?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?>
									</button>

									<button
										x-show="step === totalSteps"
										type="submit"
										:disabled="loading"
										style="padding: 12px 24px; background: #00a32a; color: #fff; border: none; border-radius: 6px; font-weight: 500; cursor: pointer;"
										:style="loading ? 'opacity: 0.7; cursor: wait;' : ''"
									>
										<span x-show="!loading"><?php esc_html_e( 'Bewerbung absenden', 'recruiting-playbook' ); ?></span>
										<span x-show="loading"><?php esc_html_e( 'Wird gesendet...', 'recruiting-playbook' ); ?></span>
									</button>
								</div>
							</form>
						</div>
					</template>
				</div>
			</div>

		</div>
	</article>

	<?php
endwhile;

get_footer();

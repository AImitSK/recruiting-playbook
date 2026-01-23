<?php
/**
 * Template: Einzelne Stelle
 *
 * Dieses Template kann im Theme überschrieben werden:
 * theme/recruiting-playbook/single-job_listing.php
 *
 * Kompatibel mit Classic und Block Themes (FSE).
 *
 * @package RecruitingPlaybook
 */

defined( 'ABSPATH' ) || exit;

/*
 * Block Theme Detection & Header
 * Block Themes (FSE) benötigen block_template_part() statt get_header()
 */
if ( wp_is_block_theme() ) {
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div class="wp-site-blocks">
		<?php block_template_part( 'header' ); ?>
		<main class="wp-block-group">
	<?php
} else {
	get_header();
}

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

	// Tracking-Daten vorbereiten.
	$tracking_category = ( $categories && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';
	$tracking_location = ( $locations && ! is_wp_error( $locations ) ) ? $locations[0]->name : '';
	$tracking_type     = ( $types && ! is_wp_error( $types ) ) ? $types[0]->name : '';
	?>

	<!-- Conversion Tracking Data (GTM DataLayer) -->
	<div
		data-rp-tracking
		data-rp-job-id="<?php echo esc_attr( get_the_ID() ); ?>"
		data-rp-job-title="<?php echo esc_attr( get_the_title() ); ?>"
		data-rp-job-category="<?php echo esc_attr( $tracking_category ); ?>"
		data-rp-job-location="<?php echo esc_attr( $tracking_location ); ?>"
		data-rp-employment-type="<?php echo esc_attr( $tracking_type ); ?>"
		style="display: none;"
		aria-hidden="true"
	></div>

	<article <?php post_class( 'rp-plugin' ); ?>>
		<div class="rp-mx-auto rp-px-4 rp-py-8" style="max-width: var(--wp--style--global--wide-size, 1200px);">

			<!-- Zurück-Link -->
			<nav class="rp-mb-6">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'job_listing' ) ); ?>" class="rp-no-underline">
					&larr; <?php esc_html_e( 'Alle Stellen', 'recruiting-playbook' ); ?>
				</a>
			</nav>

			<div class="rp-grid rp-grid-cols-1 lg:rp-grid-cols-3 rp-gap-8">

				<!-- Hauptinhalt -->
				<div class="lg:rp-col-span-2">

					<header class="rp-mb-8">
						<h1 class="rp-mb-4">
							<?php the_title(); ?>
						</h1>

						<div class="rp-flex rp-flex-wrap rp-gap-2 rp-text-xs">

							<?php if ( $locations && ! is_wp_error( $locations ) ) : ?>
								<span class="rp-badge rp-badge-gray">
									<svg class="rp-w-3.5 rp-h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
									</svg>
									<?php echo esc_html( $locations[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $types && ! is_wp_error( $types ) ) : ?>
								<span class="rp-badge rp-badge-gray">
									<svg class="rp-w-3.5 rp-h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
									</svg>
									<?php echo esc_html( $types[0]->name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $remote_option && isset( $remote_labels[ $remote_option ] ) ) : ?>
								<span class="rp-badge rp-badge-gray">
									<svg class="rp-w-3.5 rp-h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
									</svg>
									<?php echo esc_html( $remote_labels[ $remote_option ] ); ?>
								</span>
							<?php endif; ?>

						</div>
					</header>

					<!-- Stellenbeschreibung -->
					<div class="rp-job-description rp-prose rp-max-w-none">
						<?php the_content(); ?>
					</div>

				</div>

				<!-- Sidebar -->
				<aside class="rp-space-y-6 lg:rp-sticky lg:rp-top-8">

					<!-- Jetzt bewerben Button -->
					<div>
						<a href="#apply-form" class="wp-element-button rp-block rp-w-full rp-py-3 rp-text-center rp-no-underline">
							<?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?>
						</a>
						<?php if ( $deadline ) : ?>
							<p class="rp-mt-2 rp-text-sm rp-text-gray-500 rp-text-center">
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
					<div>
						<h3 class="rp-mb-4"><?php esc_html_e( 'Details', 'recruiting-playbook' ); ?></h3>

						<dl class="rp-divide-y rp-divide-gray-200 rp-text-sm">
							<?php if ( $salary_display ) : ?>
								<div class="rp-flex rp-justify-between rp-py-3">
									<dt class="rp-font-medium"><?php esc_html_e( 'Gehalt', 'recruiting-playbook' ); ?></dt>
									<dd><?php echo esc_html( $salary_display ); ?></dd>
								</div>
							<?php endif; ?>

							<?php if ( $start_date ) : ?>
								<div class="rp-flex rp-justify-between rp-py-3">
									<dt class="rp-font-medium"><?php esc_html_e( 'Startdatum', 'recruiting-playbook' ); ?></dt>
									<dd><?php echo esc_html( $start_date ); ?></dd>
								</div>
							<?php endif; ?>

							<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
								<div class="rp-flex rp-justify-between rp-py-3">
									<dt class="rp-font-medium"><?php esc_html_e( 'Berufsfeld', 'recruiting-playbook' ); ?></dt>
									<dd><?php echo esc_html( $categories[0]->name ); ?></dd>
								</div>
							<?php endif; ?>
						</dl>
					</div>

					<!-- Kontakt -->
					<?php if ( $contact_person || $contact_email || $contact_phone ) : ?>
						<div>
							<h3 class="rp-mb-4"><?php esc_html_e( 'Ansprechpartner', 'recruiting-playbook' ); ?></h3>

							<dl class="rp-divide-y rp-divide-gray-200 rp-text-sm">
								<?php if ( $contact_person ) : ?>
									<div class="rp-flex rp-justify-between rp-py-3">
										<dt class="rp-font-medium"><?php esc_html_e( 'Name', 'recruiting-playbook' ); ?></dt>
										<dd><?php echo esc_html( $contact_person ); ?></dd>
									</div>
								<?php endif; ?>

								<?php if ( $contact_email ) : ?>
									<div class="rp-flex rp-justify-between rp-py-3">
										<dt class="rp-font-medium"><?php esc_html_e( 'E-Mail', 'recruiting-playbook' ); ?></dt>
										<dd>
											<a href="mailto:<?php echo esc_attr( $contact_email ); ?>" class="rp-underline">
												<?php echo esc_html( $contact_email ); ?>
											</a>
										</dd>
									</div>
								<?php endif; ?>

								<?php if ( $contact_phone ) : ?>
									<div class="rp-flex rp-justify-between rp-py-3">
										<dt class="rp-font-medium"><?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?></dt>
										<dd>
											<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $contact_phone ) ); ?>" class="rp-underline">
												<?php echo esc_html( $contact_phone ); ?>
											</a>
										</dd>
									</div>
								<?php endif; ?>
							</dl>
						</div>
					<?php endif; ?>

				</aside>

			</div>

			<!-- Bewerbungsformular -->
			<div id="apply-form" class="rp-mt-12 rp-rounded-lg rp-p-6 md:rp-p-8 rp-border rp-border-gray-200" data-job-id="<?php echo esc_attr( get_the_ID() ); ?>" data-rp-application-form>
				<h2 class="rp-text-2xl rp-font-bold rp-text-gray-900 rp-mb-6"><?php esc_html_e( 'Jetzt bewerben', 'recruiting-playbook' ); ?></h2>

				<div x-data="applicationForm" x-cloak>
					<!-- Erfolgs-Meldung -->
					<template x-if="submitted">
						<div class="rp-text-center rp-py-12">
							<svg class="rp-w-16 rp-h-16 rp-text-success rp-mx-auto rp-mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
							</svg>
							<h3 class="rp-text-xl rp-font-semibold rp-text-gray-900 rp-mb-2"><?php esc_html_e( 'Bewerbung erfolgreich gesendet!', 'recruiting-playbook' ); ?></h3>
							<p class="rp-text-gray-600"><?php esc_html_e( 'Vielen Dank für Ihre Bewerbung. Sie erhalten in Kürze eine Bestätigung per E-Mail.', 'recruiting-playbook' ); ?></p>
						</div>
					</template>

					<!-- Formular -->
					<template x-if="!submitted">
						<div>
							<!-- Fehler-Meldung -->
							<div x-show="error" x-cloak class="rp-bg-error-light rp-border rp-border-error rp-rounded-md rp-p-4 rp-mb-6">
								<p class="rp-text-error rp-text-sm" x-text="error"></p>
							</div>

							<!-- Fortschrittsanzeige -->
							<div class="rp-mb-8">
								<div class="rp-flex rp-justify-between rp-text-sm rp-text-gray-600 rp-mb-2">
									<span><?php esc_html_e( 'Schritt', 'recruiting-playbook' ); ?> <span x-text="step"></span> <?php esc_html_e( 'von', 'recruiting-playbook' ); ?> <span x-text="totalSteps"></span></span>
									<span x-text="progress + '%'"></span>
								</div>
								<div class="rp-h-2 rp-bg-gray-200 rp-rounded-full rp-overflow-hidden">
									<div class="rp-h-full rp-bg-primary rp-transition-all rp-duration-300" :style="'width: ' + progress + '%'"></div>
								</div>
							</div>

							<form @submit.prevent="submit">
								<?php echo \RecruitingPlaybook\Services\SpamProtection::getHoneypotField(); ?>
								<?php echo \RecruitingPlaybook\Services\SpamProtection::getTimestampField(); ?>

								<!-- Schritt 1: Persönliche Daten -->
								<div x-show="step === 1" x-transition>
									<h3 class="rp-text-lg rp-font-semibold rp-text-gray-900 rp-mb-6"><?php esc_html_e( 'Persönliche Daten', 'recruiting-playbook' ); ?></h3>

									<div class="rp-space-y-4">
										<!-- Anrede, Vorname & Nachname -->
										<div class="rp-grid rp-grid-cols-1 sm:rp-grid-cols-5 rp-gap-4">
											<!-- Anrede (1/5) -->
											<div class="sm:rp-col-span-1">
												<label class="rp-label"><?php esc_html_e( 'Anrede', 'recruiting-playbook' ); ?></label>
												<select x-model="formData.salutation" class="rp-input rp-select">
													<option value=""><?php esc_html_e( 'Bitte wählen', 'recruiting-playbook' ); ?></option>
													<option value="Herr"><?php esc_html_e( 'Herr', 'recruiting-playbook' ); ?></option>
													<option value="Frau"><?php esc_html_e( 'Frau', 'recruiting-playbook' ); ?></option>
													<option value="Divers"><?php esc_html_e( 'Divers', 'recruiting-playbook' ); ?></option>
												</select>
											</div>
											<!-- Vorname (2/5) -->
											<div class="sm:rp-col-span-2">
												<label class="rp-label">
													<?php esc_html_e( 'Vorname', 'recruiting-playbook' ); ?> <span class="rp-text-error">*</span>
												</label>
												<input type="text" x-model="formData.first_name"
													class="rp-input"
													:class="errors.first_name ? 'rp-input-error' : ''"
													required>
												<p x-show="errors.first_name" x-text="errors.first_name" class="rp-error-text"></p>
											</div>
											<!-- Nachname (2/5) -->
											<div class="sm:rp-col-span-2">
												<label class="rp-label">
													<?php esc_html_e( 'Nachname', 'recruiting-playbook' ); ?> <span class="rp-text-error">*</span>
												</label>
												<input type="text" x-model="formData.last_name"
													class="rp-input"
													:class="errors.last_name ? 'rp-input-error' : ''"
													required>
												<p x-show="errors.last_name" x-text="errors.last_name" class="rp-error-text"></p>
											</div>
										</div>

										<!-- E-Mail & Telefon -->
										<div class="rp-grid rp-grid-cols-1 sm:rp-grid-cols-2 rp-gap-4">
											<!-- E-Mail -->
											<div>
												<label class="rp-label">
													<?php esc_html_e( 'E-Mail-Adresse', 'recruiting-playbook' ); ?> <span class="rp-text-error">*</span>
												</label>
												<input type="email" x-model="formData.email"
													class="rp-input"
													:class="errors.email ? 'rp-input-error' : ''"
													required>
												<p x-show="errors.email" x-text="errors.email" class="rp-error-text"></p>
											</div>
											<!-- Telefon -->
											<div>
												<label class="rp-label"><?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?></label>
												<input type="tel" x-model="formData.phone" class="rp-input">
											</div>
										</div>
									</div>
								</div>

								<!-- Schritt 2: Dokumente -->
								<div x-show="step === 2" x-transition>
									<h3 class="rp-text-lg rp-font-semibold rp-text-gray-900 rp-mb-6"><?php esc_html_e( 'Bewerbungsunterlagen', 'recruiting-playbook' ); ?></h3>

									<!-- Lebenslauf -->
									<div class="rp-mb-6">
										<label class="rp-label rp-mb-2"><?php esc_html_e( 'Lebenslauf', 'recruiting-playbook' ); ?></label>
										<div
											@dragover.prevent="$el.classList.add('rp-border-primary', 'rp-bg-primary-light')"
											@dragleave.prevent="$el.classList.remove('rp-border-primary', 'rp-bg-primary-light')"
											@drop.prevent="$el.classList.remove('rp-border-primary', 'rp-bg-primary-light'); handleDrop($event, 'resume')"
											class="rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-lg rp-p-6 rp-text-center rp-cursor-pointer rp-transition-colors"
											:class="files.resume ? 'rp-border-success rp-bg-success-light' : ''"
										>
											<template x-if="!files.resume">
												<div>
													<svg class="rp-w-10 rp-h-10 rp-text-gray-400 rp-mx-auto rp-mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
														<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
													</svg>
													<p class="rp-text-gray-600 rp-mb-2"><?php esc_html_e( 'Datei hierher ziehen oder', 'recruiting-playbook' ); ?></p>
													<label class="rp-text-primary hover:rp-text-primary-hover rp-font-medium rp-cursor-pointer">
														<?php esc_html_e( 'Datei auswählen', 'recruiting-playbook' ); ?>
														<input type="file" @change="handleFileSelect($event, 'resume')" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="rp-hidden">
													</label>
													<p class="rp-text-xs rp-text-gray-400 rp-mt-2"><?php esc_html_e( 'PDF, DOC, DOCX, JPG, PNG (max. 10 MB)', 'recruiting-playbook' ); ?></p>
												</div>
											</template>
											<template x-if="files.resume">
												<div class="rp-flex rp-items-center rp-justify-between">
													<div class="rp-flex rp-items-center rp-gap-3">
														<svg class="rp-w-6 rp-h-6 rp-text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
														</svg>
														<div class="rp-text-left">
															<p class="rp-font-medium rp-text-gray-900" x-text="files.resume.name"></p>
															<p class="rp-text-xs rp-text-gray-500" x-text="formatFileSize(files.resume.size)"></p>
														</div>
													</div>
													<button type="button" @click="removeFile('resume')" class="rp-p-1 rp-text-error hover:rp-text-error">
														<svg class="rp-w-5 rp-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
														</svg>
													</button>
												</div>
											</template>
										</div>
										<p x-show="errors.resume" x-text="errors.resume" class="rp-error-text"></p>
									</div>

									<!-- Weitere Dokumente -->
									<div class="rp-mb-6">
										<label class="rp-label rp-mb-2"><?php esc_html_e( 'Weitere Dokumente (Zeugnisse, Zertifikate)', 'recruiting-playbook' ); ?></label>
										<div
											@dragover.prevent
											@drop.prevent="handleDrop($event, 'documents')"
											class="rp-border-2 rp-border-dashed rp-border-gray-300 rp-rounded-lg rp-p-6 rp-text-center"
										>
											<label class="rp-text-primary hover:rp-text-primary-hover rp-font-medium rp-cursor-pointer">
												<?php esc_html_e( 'Dateien auswählen', 'recruiting-playbook' ); ?>
												<input type="file" @change="handleFileSelect($event, 'documents')" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple class="rp-hidden">
											</label>
											<p class="rp-text-xs rp-text-gray-400 rp-mt-2"><?php esc_html_e( 'Mehrere Dateien möglich (max. 5 Dateien, je 10 MB)', 'recruiting-playbook' ); ?></p>
										</div>

										<!-- Hochgeladene Dateien -->
										<template x-if="files.documents.length > 0">
											<ul class="rp-mt-3 rp-space-y-2">
												<template x-for="(file, index) in files.documents" :key="index">
													<li class="rp-flex rp-items-center rp-justify-between rp-px-3 rp-py-2 rp-border rp-border-gray-200 rp-rounded">
														<span class="rp-text-sm rp-text-gray-700" x-text="file.name"></span>
														<button type="button" @click="removeFile('documents', index)" class="rp-p-1 rp-text-error hover:rp-text-error">
															<svg class="rp-w-4 rp-h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
															</svg>
														</button>
													</li>
												</template>
											</ul>
										</template>
									</div>

									<!-- Anschreiben -->
									<div>
										<label class="rp-label"><?php esc_html_e( 'Anschreiben / Nachricht', 'recruiting-playbook' ); ?></label>
										<textarea x-model="formData.cover_letter" rows="4"
											class="rp-input rp-textarea"
											placeholder="<?php esc_attr_e( 'Optional: Fügen Sie ein kurzes Anschreiben hinzu...', 'recruiting-playbook' ); ?>"></textarea>
									</div>
								</div>

								<!-- Schritt 3: Datenschutz & Absenden -->
								<div x-show="step === 3" x-transition>
									<h3 class="rp-text-lg rp-font-semibold rp-text-gray-900 rp-mb-6"><?php esc_html_e( 'Datenschutz & Absenden', 'recruiting-playbook' ); ?></h3>

									<!-- Zusammenfassung -->
									<div class="rp-border rp-border-gray-200 rp-rounded-lg rp-p-5 rp-mb-6">
										<h4 class="rp-font-semibold rp-text-gray-900 rp-mb-3"><?php esc_html_e( 'Ihre Angaben', 'recruiting-playbook' ); ?></h4>
										<dl class="rp-text-sm rp-space-y-2">
											<div class="rp-flex">
												<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'Name:', 'recruiting-playbook' ); ?></dt>
												<dd class="rp-text-gray-900" x-text="formData.salutation + ' ' + formData.first_name + ' ' + formData.last_name"></dd>
											</div>
											<div class="rp-flex">
												<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'E-Mail:', 'recruiting-playbook' ); ?></dt>
												<dd class="rp-text-gray-900" x-text="formData.email"></dd>
											</div>
											<template x-if="formData.phone">
												<div class="rp-flex">
													<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'Telefon:', 'recruiting-playbook' ); ?></dt>
													<dd class="rp-text-gray-900" x-text="formData.phone"></dd>
												</div>
											</template>
											<template x-if="files.resume">
												<div class="rp-flex">
													<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'Lebenslauf:', 'recruiting-playbook' ); ?></dt>
													<dd class="rp-text-gray-900" x-text="files.resume.name"></dd>
												</div>
											</template>
											<template x-if="files.documents.length > 0">
												<div class="rp-flex">
													<dt class="rp-w-28 rp-text-gray-500"><?php esc_html_e( 'Dokumente:', 'recruiting-playbook' ); ?></dt>
													<dd class="rp-text-gray-900" x-text="files.documents.length + ' Datei(en)'"></dd>
												</div>
											</template>
										</dl>
									</div>

									<!-- Datenschutz-Checkbox -->
									<div class="rp-mb-6">
										<label class="rp-flex rp-items-start rp-gap-3 rp-cursor-pointer">
											<input type="checkbox" x-model="formData.privacy_consent" class="rp-checkbox rp-mt-1">
											<span class="rp-text-sm rp-leading-relaxed">
												<span class="rp-text-error">*</span>
												<?php
												$privacy_url = get_privacy_policy_url();
												printf(
													/* translators: %s: privacy policy link */
													esc_html__( 'Ich habe die %s gelesen und stimme der Verarbeitung meiner Daten zum Zweck der Bewerbung zu.', 'recruiting-playbook' ),
													$privacy_url ? '<a href="' . esc_url( $privacy_url ) . '" target="_blank" class="rp-underline">' . esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' ) . '</a>' : esc_html__( 'Datenschutzerklärung', 'recruiting-playbook' )
												);
												?>
											</span>
										</label>
										<p x-show="errors.privacy_consent" x-text="errors.privacy_consent" class="rp-error-text rp-mt-2"></p>
									</div>
								</div>

								<!-- Navigation -->
								<div class="rp-flex rp-justify-between rp-items-center rp-mt-8 rp-pt-6 rp-border-t rp-border-gray-200">
									<button
										type="button"
										x-show="step > 1"
										@click="prevStep"
										class="wp-element-button is-style-outline"
									>
										<?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
									</button>
									<div x-show="step === 1"></div>

									<button
										x-show="step < totalSteps"
										type="button"
										@click="nextStep"
										class="wp-element-button"
									>
										<?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?>
									</button>

									<button
										x-show="step === totalSteps"
										type="submit"
										:disabled="loading"
										class="wp-element-button disabled:rp-opacity-50 disabled:rp-cursor-not-allowed"
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

/*
 * Block Theme Detection & Footer
 */
if ( wp_is_block_theme() ) {
	?>
		</main>
		<?php block_template_part( 'footer' ); ?>
	</div>
	<?php wp_footer(); ?>
	</body>
	</html>
	<?php
} else {
	get_footer();
}

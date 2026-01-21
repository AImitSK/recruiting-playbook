<?php
/**
 * Shortcodes für Recruiting Playbook
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Frontend;

use RecruitingPlaybook\Services\SpamProtection;

/**
 * Shortcode-Handler
 *
 * Verfügbare Shortcodes:
 * - [rp_jobs] - Stellenliste
 * - [rp_application_form] - Bewerbungsformular
 */
class Shortcodes {

	/**
	 * Shortcodes registrieren
	 */
	public function register(): void {
		add_shortcode( 'rp_jobs', [ $this, 'renderJobList' ] );
		add_shortcode( 'rp_application_form', [ $this, 'renderApplicationForm' ] );
	}

	/**
	 * Stellenliste rendern
	 *
	 * Attribute:
	 * - limit: Anzahl Stellen (default: 10)
	 * - category: Filter nach Kategorie-Slug
	 * - location: Filter nach Standort-Slug
	 * - type: Filter nach Beschäftigungsart-Slug
	 * - orderby: Sortierung (date, title, rand)
	 * - order: ASC oder DESC
	 * - columns: Spalten im Grid (1-4, default: 1)
	 * - show_excerpt: Auszug anzeigen (true/false)
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function renderJobList( $atts ): string {
		$atts = shortcode_atts(
			[
				'limit'        => 10,
				'category'     => '',
				'location'     => '',
				'type'         => '',
				'orderby'      => 'date',
				'order'        => 'DESC',
				'columns'      => 1,
				'show_excerpt' => 'true',
			],
			$atts,
			'rp_jobs'
		);

		// Query-Argumente aufbauen
		$args = [
			'post_type'      => 'job_listing',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC',
		];

		// Taxonomie-Filter
		$tax_query = [];

		if ( ! empty( $atts['category'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'job_category',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			];
		}

		if ( ! empty( $atts['location'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'job_location',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['location'] ) ),
			];
		}

		if ( ! empty( $atts['type'] ) ) {
			$tax_query[] = [
				'taxonomy' => 'employment_type',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['type'] ) ),
			];
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return sprintf(
				'<div class="rp-no-jobs" style="text-align: center; padding: 40px 20px; background: #f9fafb; border-radius: 8px;">
					<p style="color: #6b7280;">%s</p>
				</div>',
				esc_html__( 'Aktuell keine offenen Stellen verfügbar.', 'recruiting-playbook' )
			);
		}

		$columns      = max( 1, min( 4, absint( $atts['columns'] ) ) );
		$show_excerpt = filter_var( $atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN );

		ob_start();
		?>
		<div class="rp-jobs-shortcode" style="display: grid; gap: 20px; grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$locations = get_the_terms( get_the_ID(), 'job_location' );
				$types     = get_the_terms( get_the_ID(), 'employment_type' );
				$remote    = get_post_meta( get_the_ID(), '_rp_remote_option', true );
				?>
				<article class="rp-job-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
					<h3 class="rp-job-title" style="font-size: 1.125rem; font-weight: 600; margin: 0 0 12px 0;">
						<a href="<?php the_permalink(); ?>" style="color: inherit; text-decoration: none;">
							<?php the_title(); ?>
						</a>
					</h3>

					<div class="rp-job-meta" style="display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.875rem; color: #6b7280; margin-bottom: 12px;">
						<?php if ( $locations && ! is_wp_error( $locations ) ) : ?>
							<span><?php echo esc_html( $locations[0]->name ); ?></span>
						<?php endif; ?>

						<?php if ( $types && ! is_wp_error( $types ) ) : ?>
							<span><?php echo esc_html( $types[0]->name ); ?></span>
						<?php endif; ?>

						<?php if ( $remote && 'no' !== $remote ) : ?>
							<span style="color: #059669;">
								<?php echo 'full' === $remote ? esc_html__( 'Remote', 'recruiting-playbook' ) : esc_html__( 'Hybrid', 'recruiting-playbook' ); ?>
							</span>
						<?php endif; ?>
					</div>

					<?php if ( $show_excerpt && has_excerpt() ) : ?>
						<div class="rp-job-excerpt" style="color: #4b5563; margin-bottom: 12px; line-height: 1.5;">
							<?php the_excerpt(); ?>
						</div>
					<?php endif; ?>

					<a href="<?php the_permalink(); ?>" class="rp-btn" style="display: inline-block; padding: 8px 16px; background: #2271b1; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem;">
						<?php esc_html_e( 'Mehr erfahren', 'recruiting-playbook' ); ?>
					</a>
				</article>
			<?php endwhile; ?>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Bewerbungsformular rendern
	 *
	 * Attribute:
	 * - job_id: ID der Stelle (required)
	 * - title: Überschrift (default: "Jetzt bewerben")
	 * - show_job_title: Job-Titel anzeigen (true/false)
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string HTML-Ausgabe.
	 */
	public function renderApplicationForm( $atts ): string {
		$atts = shortcode_atts(
			[
				'job_id'         => 0,
				'title'          => __( 'Jetzt bewerben', 'recruiting-playbook' ),
				'show_job_title' => 'true',
			],
			$atts,
			'rp_application_form'
		);

		$job_id = absint( $atts['job_id'] );

		// Wenn keine job_id, versuche aktuelle Stelle zu verwenden
		if ( ! $job_id && is_singular( 'job_listing' ) ) {
			$job_id = get_the_ID();
		}

		// Validieren
		if ( ! $job_id ) {
			return sprintf(
				'<div class="rp-form-error" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px 16px; color: #dc2626;">
					%s
				</div>',
				esc_html__( 'Fehler: Keine Stelle angegeben. Bitte job_id Attribut setzen.', 'recruiting-playbook' )
			);
		}

		$job = get_post( $job_id );
		if ( ! $job || 'job_listing' !== $job->post_type || 'publish' !== $job->post_status ) {
			return sprintf(
				'<div class="rp-form-error" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px 16px; color: #dc2626;">
					%s
				</div>',
				esc_html__( 'Fehler: Die angegebene Stelle existiert nicht oder ist nicht verfügbar.', 'recruiting-playbook' )
			);
		}

		// Assets laden
		$this->enqueueFormAssets();

		$show_job_title = filter_var( $atts['show_job_title'], FILTER_VALIDATE_BOOLEAN );

		ob_start();
		?>
		<div class="rp-application-form-shortcode" data-job-id="<?php echo esc_attr( $job_id ); ?>" style="padding: 30px; background: #f9fafb; border-radius: 8px;">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h2 style="font-size: 1.5rem; margin: 0 0 8px 0;"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $show_job_title ) : ?>
				<p style="color: #6b7280; margin: 0 0 24px 0;">
					<?php
					printf(
						/* translators: %s: Job title */
						esc_html__( 'Bewerbung für: %s', 'recruiting-playbook' ),
						'<strong>' . esc_html( $job->post_title ) . '</strong>'
					);
					?>
				</p>
			<?php endif; ?>

			<?php echo $this->getFormHtml( $job_id ); ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Formular-HTML generieren (wiederverwendbar)
	 *
	 * @param int $job_id Job ID.
	 * @return string HTML.
	 */
	private function getFormHtml( int $job_id ): string {
		ob_start();
		?>
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
						<?php echo SpamProtection::getHoneypotField(); ?>
						<?php echo SpamProtection::getTimestampField(); ?>

						<!-- Schritt 1: Persönliche Daten -->
						<div x-show="step === 1" x-transition>
							<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Persönliche Daten', 'recruiting-playbook' ); ?></h3>

							<div style="display: grid; gap: 16px;">
								<div class="rp-form-field">
									<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Anrede', 'recruiting-playbook' ); ?></label>
									<select x-model="formData.salutation" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
										<option value=""><?php esc_html_e( 'Bitte wählen', 'recruiting-playbook' ); ?></option>
										<option value="Herr"><?php esc_html_e( 'Herr', 'recruiting-playbook' ); ?></option>
										<option value="Frau"><?php esc_html_e( 'Frau', 'recruiting-playbook' ); ?></option>
										<option value="Divers"><?php esc_html_e( 'Divers', 'recruiting-playbook' ); ?></option>
									</select>
								</div>

								<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
									<div class="rp-form-field">
										<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Vorname', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
										<input type="text" x-model="formData.first_name" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;" required>
										<span x-show="errors.first_name" x-text="errors.first_name" style="color: #dc2626; font-size: 0.875rem;"></span>
									</div>
									<div class="rp-form-field">
										<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Nachname', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
										<input type="text" x-model="formData.last_name" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;" required>
										<span x-show="errors.last_name" x-text="errors.last_name" style="color: #dc2626; font-size: 0.875rem;"></span>
									</div>
								</div>

								<div class="rp-form-field">
									<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'E-Mail-Adresse', 'recruiting-playbook' ); ?> <span style="color: #dc2626;">*</span></label>
									<input type="email" x-model="formData.email" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;" required>
									<span x-show="errors.email" x-text="errors.email" style="color: #dc2626; font-size: 0.875rem;"></span>
								</div>

								<div class="rp-form-field">
									<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Telefon', 'recruiting-playbook' ); ?></label>
									<input type="tel" x-model="formData.phone" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
								</div>
							</div>
						</div>

						<!-- Schritt 2: Dokumente -->
						<div x-show="step === 2" x-transition>
							<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Bewerbungsunterlagen', 'recruiting-playbook' ); ?></h3>

							<div class="rp-form-field" style="margin-bottom: 20px;">
								<label style="display: block; font-weight: 500; margin-bottom: 8px;"><?php esc_html_e( 'Lebenslauf', 'recruiting-playbook' ); ?></label>
								<div
									@dragover.prevent
									@drop.prevent="handleDrop($event, 'resume')"
									style="border: 2px dashed #d1d5db; border-radius: 8px; padding: 24px; text-align: center;"
									:style="files.resume ? 'border-color: #00a32a; background: #f0fdf4;' : ''"
								>
									<template x-if="!files.resume">
										<div>
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
											<span x-text="files.resume.name"></span>
											<button type="button" @click="removeFile('resume')" style="color: #dc2626; background: none; border: none; cursor: pointer;">&times;</button>
										</div>
									</template>
								</div>
							</div>

							<div class="rp-form-field">
								<label style="display: block; font-weight: 500; margin-bottom: 4px;"><?php esc_html_e( 'Anschreiben / Nachricht', 'recruiting-playbook' ); ?></label>
								<textarea x-model="formData.cover_letter" rows="5" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; resize: vertical;"></textarea>
							</div>
						</div>

						<!-- Schritt 3: Datenschutz -->
						<div x-show="step === 3" x-transition>
							<h3 style="font-size: 1.125rem; font-weight: 600; margin: 0 0 20px 0;"><?php esc_html_e( 'Datenschutz & Absenden', 'recruiting-playbook' ); ?></h3>

							<div class="rp-form-field" style="margin-bottom: 20px;">
								<label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
									<input type="checkbox" x-model="formData.privacy_consent" style="width: 20px; height: 20px; margin-top: 2px;">
									<span style="font-size: 0.875rem; line-height: 1.5;">
										<?php
										$privacy_url = get_privacy_policy_url();
										printf(
											/* translators: %s: privacy policy link */
											esc_html__( 'Ich habe die %s gelesen und stimme der Verarbeitung meiner Daten zu. *', 'recruiting-playbook' ),
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
							<button type="button" x-show="step > 1" @click="prevStep" style="padding: 12px 24px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">
								<?php esc_html_e( 'Zurück', 'recruiting-playbook' ); ?>
							</button>
							<div x-show="step === 1"></div>

							<button x-show="step < totalSteps" type="button" @click="nextStep" style="padding: 12px 24px; background: #2271b1; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
								<?php esc_html_e( 'Weiter', 'recruiting-playbook' ); ?>
							</button>

							<button x-show="step === totalSteps" type="submit" :disabled="loading" style="padding: 12px 24px; background: #00a32a; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
								<span x-show="!loading"><?php esc_html_e( 'Bewerbung absenden', 'recruiting-playbook' ); ?></span>
								<span x-show="loading"><?php esc_html_e( 'Wird gesendet...', 'recruiting-playbook' ); ?></span>
							</button>
						</div>
					</form>
				</div>
			</template>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Form-Assets laden
	 */
	private function enqueueFormAssets(): void {
		// Alpine.js
		if ( ! wp_script_is( 'rp-alpine', 'enqueued' ) ) {
			wp_enqueue_script(
				'rp-alpine',
				'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
				[],
				'3.14.3',
				[ 'strategy' => 'defer' ]
			);
		}

		// Application Form JS
		if ( ! wp_script_is( 'rp-application-form', 'enqueued' ) ) {
			wp_enqueue_script(
				'rp-application-form',
				RP_PLUGIN_URL . 'assets/src/js/application-form.js',
				[ 'rp-alpine' ],
				RP_VERSION,
				true
			);

			wp_localize_script(
				'rp-application-form',
				'rpForm',
				[
					'apiUrl' => rest_url( 'recruiting/v1/' ),
					'nonce'  => wp_create_nonce( 'wp_rest' ),
					'i18n'   => [
						'required'        => __( 'Dieses Feld ist erforderlich', 'recruiting-playbook' ),
						'invalidEmail'    => __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein', 'recruiting-playbook' ),
						'fileTooLarge'    => __( 'Die Datei ist zu groß (max. 10 MB)', 'recruiting-playbook' ),
						'invalidFileType' => __( 'Dateityp nicht erlaubt. Erlaubt: PDF, DOC, DOCX, JPG, PNG', 'recruiting-playbook' ),
						'privacyRequired' => __( 'Bitte stimmen Sie der Datenschutzerklärung zu', 'recruiting-playbook' ),
					],
				]
			);
		}

		// x-cloak Style
		wp_add_inline_style( 'wp-block-library', '[x-cloak] { display: none !important; }' );
	}
}

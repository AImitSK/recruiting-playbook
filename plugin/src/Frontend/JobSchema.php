<?php
/**
 * Google for Jobs Schema (JSON-LD)
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Frontend;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\PostTypes\JobListing;

/**
 * Generiert JSON-LD Schema für Google for Jobs
 *
 * @see https://developers.google.com/search/docs/appearance/structured-data/job-posting
 */
class JobSchema {

	/**
	 * Schema-Ausgabe initialisieren
	 */
	public function init(): void {
		add_action( 'wp_head', [ $this, 'outputSchema' ], 5 );
	}

	/**
	 * JSON-LD Schema im Head ausgeben
	 */
	public function outputSchema(): void {
		// Nur auf Einzelseiten von Stellenanzeigen.
		if ( ! is_singular( JobListing::POST_TYPE ) ) {
			return;
		}

		$post = get_post();

		// Kein Schema für Entwürfe/Previews - nur veröffentlichte Posts.
		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		// Prüfen ob Schema aktiviert ist (Integrations-Settings > Legacy-Fallback).
		$integrations = get_option( 'rp_integrations', [] );
		if ( isset( $integrations['google_jobs_enabled'] ) ) {
			if ( ! $integrations['google_jobs_enabled'] ) {
				return;
			}
		} else {
			// Legacy-Fallback: rp_settings.enable_schema.
			$settings = get_option( 'rp_settings', [] );
			if ( isset( $settings['enable_schema'] ) && ! $settings['enable_schema'] ) {
				return;
			}
		}

		$schema = $this->buildSchema( $post );

		if ( empty( $schema ) ) {
			return;
		}

		echo "\n<!-- Recruiting Playbook: Google for Jobs Schema -->\n";
		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		echo "\n</script>\n";
	}

	/**
	 * Schema-Daten für einen Job erstellen
	 *
	 * @param \WP_Post $post Job Post.
	 * @return array Schema data.
	 */
	public function buildSchema( \WP_Post $post ): array {
		$settings     = get_option( 'rp_settings', [] );
		$integrations = get_option( 'rp_integrations', [] );

		// Veröffentlichungsdatum ermitteln (Fallback auf aktuelles Datum für Entwürfe).
		$post_time = get_post_time( 'U', true, $post );
		$date_posted = $post_time ? gmdate( 'c', $post_time ) : gmdate( 'c' );

		// Pflichtfelder.
		$schema = [
			'@context'      => 'https://schema.org/',
			'@type'         => 'JobPosting',
			'title'         => get_the_title( $post ),
			'description'   => $this->getDescription( $post ),
			'datePosted'    => $date_posted,
			'identifier'    => [
				'@type' => 'PropertyValue',
				'name'  => $settings['company_name'] ?? get_bloginfo( 'name' ),
				'value' => 'job-' . $post->ID,
			],
			'hiringOrganization' => $this->getOrganization( $settings ),
		];

		// Bewerbungsfrist (steuerbar über google_jobs_show_deadline).
		$show_deadline = $integrations['google_jobs_show_deadline'] ?? true;
		if ( $show_deadline ) {
			$deadline = get_post_meta( $post->ID, '_rp_application_deadline', true );
			if ( $deadline ) {
				$deadline_timestamp = strtotime( $deadline . ' 23:59:59' );
				if ( $deadline_timestamp ) {
					$schema['validThrough'] = gmdate( 'c', $deadline_timestamp );
				}
			}
		}

		// Beschäftigungsart.
		$employment_type = $this->getEmploymentType( $post->ID );
		if ( $employment_type ) {
			$schema['employmentType'] = $employment_type;
		}

		// Standort.
		$location = $this->getJobLocation( $post->ID );
		if ( $location ) {
			$schema['jobLocation'] = $location;
		}

		// Remote-Option (steuerbar über google_jobs_show_remote).
		$show_remote = $integrations['google_jobs_show_remote'] ?? true;
		if ( $show_remote ) {
			$remote = get_post_meta( $post->ID, '_rp_remote_option', true );
			if ( 'full' === $remote ) {
				$schema['jobLocationType'] = 'TELECOMMUTE';
			}
		}

		// Gehalt (steuerbar über google_jobs_show_salary).
		$show_salary = $integrations['google_jobs_show_salary'] ?? true;
		if ( $show_salary ) {
			$salary = $this->getSalary( $post->ID );
			if ( $salary ) {
				$schema['baseSalary'] = $salary;
			}
		}

		// Direktbewerbung URL.
		$schema['directApply'] = true;

		return $schema;
	}

	/**
	 * Job-Beschreibung für Schema
	 *
	 * @param \WP_Post $post Job Post.
	 * @return string HTML description.
	 */
	private function getDescription( \WP_Post $post ): string {
		$content = apply_filters( 'the_content', $post->post_content );
		// HTML erlaubt, aber Script-Tags entfernen.
		$content = wp_kses_post( $content );
		return $content;
	}

	/**
	 * Organisation für Schema
	 *
	 * @param array $settings Plugin settings.
	 * @return array Organization schema.
	 */
	private function getOrganization( array $settings ): array {
		$org = [
			'@type' => 'Organization',
			'name'  => $settings['company_name'] ?? get_bloginfo( 'name' ),
			'sameAs' => home_url(),
		];

		// Logo hinzufügen falls Custom Logo existiert.
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
			if ( $logo_url ) {
				$org['logo'] = $logo_url;
			}
		}

		return $org;
	}

	/**
	 * Beschäftigungsart für Schema
	 *
	 * Google akzeptiert: FULL_TIME, PART_TIME, CONTRACTOR, TEMPORARY, INTERN, VOLUNTEER, PER_DIEM, OTHER
	 *
	 * @param int $post_id Post ID.
	 * @return string|array|null Employment type(s).
	 */
	private function getEmploymentType( int $post_id ) {
		$terms = get_the_terms( $post_id, 'employment_type' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return null;
		}

		// Mapping von deutschen Slugs zu Google Schema-Werten.
		$mapping = [
			'vollzeit'      => 'FULL_TIME',
			'teilzeit'      => 'PART_TIME',
			'minijob'       => 'PART_TIME',
			'ausbildung'    => 'INTERN',
			'praktikum'     => 'INTERN',
			'werkstudent'   => 'PART_TIME',
			'freiberuflich' => 'CONTRACTOR',
		];

		$types = [];
		foreach ( $terms as $term ) {
			if ( isset( $mapping[ $term->slug ] ) ) {
				$types[] = $mapping[ $term->slug ];
			}
		}

		// Duplikate entfernen.
		$types = array_unique( $types );

		if ( empty( $types ) ) {
			return null;
		}

		// Einzelner Wert oder Array.
		return count( $types ) === 1 ? $types[0] : $types;
	}

	/**
	 * Standort für Schema
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Location schema.
	 */
	private function getJobLocation( int $post_id ): ?array {
		$terms = get_the_terms( $post_id, 'job_location' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			// Remote-only Job ohne physischen Standort.
			$remote = get_post_meta( $post_id, '_rp_remote_option', true );
			if ( 'full' === $remote ) {
				return null; // jobLocationType: TELECOMMUTE wird gesetzt.
			}
			return null;
		}

		$location = $terms[0];

		// Basis-Location mit Ortsname.
		// Hinweis: Für vollständige Adressen müssten zusätzliche Term-Meta gespeichert werden.
		return [
			'@type'   => 'Place',
			'address' => [
				'@type'           => 'PostalAddress',
				'addressLocality' => $location->name,
				'addressCountry'  => 'DE',
			],
		];
	}

	/**
	 * Schema für einen Job validieren
	 *
	 * Prüft ob alle erforderlichen Felder für Google for Jobs vorhanden sind.
	 *
	 * @param int|\WP_Post $post Job Post oder ID.
	 * @return array{valid: bool, errors: array, warnings: array}
	 */
	public function validateSchema( $post ): array {
		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post instanceof \WP_Post ) {
			return [
				'valid'    => false,
				'errors'   => [ __( 'Invalid post', 'recruiting-playbook' ) ],
				'warnings' => [],
			];
		}

		$errors   = [];
		$warnings = [];
		$settings = get_option( 'rp_settings', [] );

		// Pflichtfelder prüfen.
		// 1. Titel.
		if ( empty( $post->post_title ) ) {
			$errors[] = __( 'Job title is missing', 'recruiting-playbook' );
		}

		// 2. Beschreibung.
		if ( empty( $post->post_content ) ) {
			$errors[] = __( 'Job description is missing', 'recruiting-playbook' );
		} elseif ( strlen( wp_strip_all_tags( $post->post_content ) ) < 100 ) {
			$warnings[] = __( 'Job description is very short (minimum 100 characters recommended)', 'recruiting-playbook' );
		}

		// 3. Veröffentlichungsdatum (automatisch durch WordPress).

		// 4. Unternehmen.
		if ( empty( $settings['company_name'] ) && empty( get_bloginfo( 'name' ) ) ) {
			$errors[] = __( 'Company name is missing (in plugin settings or WordPress settings)', 'recruiting-playbook' );
		}

		// Empfohlene Felder prüfen.
		// 5. Standort oder Remote.
		$locations = get_the_terms( $post->ID, 'job_location' );
		$remote    = get_post_meta( $post->ID, '_rp_remote_option', true );

		if ( ( ! $locations || is_wp_error( $locations ) ) && 'full' !== $remote ) {
			$warnings[] = __( 'Location is missing (recommended for better ranking)', 'recruiting-playbook' );
		}

		// 6. Beschäftigungsart.
		$employment_types = get_the_terms( $post->ID, 'employment_type' );
		if ( ! $employment_types || is_wp_error( $employment_types ) ) {
			$warnings[] = __( 'Employment type is missing (full-time, part-time, etc.)', 'recruiting-playbook' );
		}

		// 7. Gehalt.
		$hide_salary = get_post_meta( $post->ID, '_rp_hide_salary', true );
		$salary_min  = get_post_meta( $post->ID, '_rp_salary_min', true );
		$salary_max  = get_post_meta( $post->ID, '_rp_salary_max', true );

		if ( ! $hide_salary && ! $salary_min && ! $salary_max ) {
			$warnings[] = __( 'Salary is missing (important for Google for Jobs ranking)', 'recruiting-playbook' );
		}

		// 8. Bewerbungsfrist.
		$deadline = get_post_meta( $post->ID, '_rp_application_deadline', true );
		if ( ! $deadline ) {
			$warnings[] = __( 'Application deadline is missing', 'recruiting-playbook' );
		} else {
			$deadline_timestamp = strtotime( $deadline );
			if ( $deadline_timestamp && $deadline_timestamp < time() ) {
				$errors[] = __( 'Application deadline has expired', 'recruiting-playbook' );
			}
		}

		// Ergebnis.
		return [
			'valid'    => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
		];
	}

	/**
	 * Schema-Validation für alle veröffentlichten Jobs
	 *
	 * @return array Array mit Post-IDs und deren Validierungsergebnissen.
	 */
	public function validateAllJobs(): array {
		$jobs = get_posts(
			[
				'post_type'      => JobListing::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		$results = [];
		foreach ( $jobs as $job_id ) {
			$validation = $this->validateSchema( $job_id );
			$results[ $job_id ] = [
				'title'      => get_the_title( $job_id ),
				'valid'      => $validation['valid'],
				'errors'     => $validation['errors'],
				'warnings'   => $validation['warnings'],
				'edit_link'  => get_edit_post_link( $job_id, 'raw' ),
			];
		}

		return $results;
	}

	/**
	 * Gehalt für Schema
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Salary schema.
	 */
	private function getSalary( int $post_id ): ?array {
		$hide_salary = get_post_meta( $post_id, '_rp_hide_salary', true );
		if ( $hide_salary ) {
			return null;
		}

		$min      = get_post_meta( $post_id, '_rp_salary_min', true );
		$max      = get_post_meta( $post_id, '_rp_salary_max', true );
		$currency = get_post_meta( $post_id, '_rp_salary_currency', true ) ?: 'EUR';
		$period   = get_post_meta( $post_id, '_rp_salary_period', true ) ?: 'month';

		if ( ! $min && ! $max ) {
			return null;
		}

		// Mapping zu Schema.org unitText.
		$unit_mapping = [
			'hour'  => 'HOUR',
			'month' => 'MONTH',
			'year'  => 'YEAR',
		];

		$salary = [
			'@type'    => 'MonetaryAmount',
			'currency' => $currency,
			'value'    => [
				'@type'    => 'QuantitativeValue',
				'unitText' => $unit_mapping[ $period ] ?? 'MONTH',
			],
		];

		if ( $min && $max ) {
			$salary['value']['minValue'] = (float) $min;
			$salary['value']['maxValue'] = (float) $max;
		} elseif ( $min ) {
			$salary['value']['value'] = (float) $min;
		} elseif ( $max ) {
			$salary['value']['value'] = (float) $max;
		}

		return $salary;
	}
}

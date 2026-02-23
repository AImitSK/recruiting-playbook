<?php
/**
 * Plugin-Daten als JSON exportieren
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Admin\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Backup-Export Klasse
 */
class BackupExporter {

	/**
	 * Vollständigen Backup erstellen
	 *
	 * @return array
	 */
	public function createBackup(): array {
		return [
			'meta'              => $this->getMetaData(),
			'settings'          => $this->getSettings(),
			'jobs'              => $this->getJobs(),
			'taxonomies'        => $this->getTaxonomies(),
			'candidates'        => $this->getCandidates(),
			'applications'      => $this->getApplications(),
			'documents'         => $this->getDocumentsMeta(),
			'notes'             => $this->getNotes(),
			'ratings'           => $this->getRatings(),
			'talent_pool'       => $this->getTalentPool(),
			'email_templates'   => $this->getEmailTemplates(),
			'signatures'        => $this->getSignatures(),
			'field_definitions' => $this->getFieldDefinitions(),
			'form_templates'    => $this->getFormTemplates(),
			'form_config'       => $this->getFormConfig(),
			'webhooks'          => $this->getWebhooks(),
			'job_assignments'   => $this->getJobAssignments(),
			'email_log'         => $this->getEmailLog(),
			'ai_analyses'       => $this->getAiAnalyses(),
			'activity_log'      => $this->getActivityLog(),
		];
	}

	/**
	 * Meta-Daten
	 *
	 * @return array
	 */
	private function getMetaData(): array {
		return [
			'backup_format_version' => '2.0',
			'plugin_version'        => RP_VERSION,
			'wp_version'            => get_bloginfo( 'version' ),
			'php_version'           => PHP_VERSION,
			'site_url'              => get_site_url(),
			'export_date'           => current_time( 'mysql' ),
			'export_user'           => wp_get_current_user()->user_login,
		];
	}

	/**
	 * Einstellungen
	 *
	 * @return array
	 */
	private function getSettings(): array {
		return [
			'rp_settings'                   => get_option( 'rp_settings', [] ),
			'rp_db_version'                 => get_option( 'rp_db_version', '' ),
			'rp_employment_types_installed' => get_option( 'rp_employment_types_installed', false ),
		];
	}

	/**
	 * Jobs exportieren
	 *
	 * @return array
	 */
	private function getJobs(): array {
		$jobs = get_posts(
			[
				'post_type'      => 'job_listing',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			]
		);

		$export = [];

		foreach ( $jobs as $job ) {
			$meta = get_post_meta( $job->ID );

			// Nur rp_ Meta-Felder.
			$rp_meta = [];
			foreach ( $meta as $key => $value ) {
				if ( strpos( $key, '_rp_' ) === 0 ) {
					$rp_meta[ $key ] = maybe_unserialize( $value[0] );
				}
			}

			$export[] = [
				'ID'            => $job->ID,
				'post_title'    => $job->post_title,
				'post_content'  => $job->post_content,
				'post_excerpt'  => $job->post_excerpt,
				'post_status'   => $job->post_status,
				'post_date'     => $job->post_date,
				'post_modified' => $job->post_modified,
				'meta'          => $rp_meta,
				'taxonomies'    => [
					'job_category'    => wp_get_post_terms( $job->ID, 'job_category', [ 'fields' => 'names' ] ),
					'job_location'    => wp_get_post_terms( $job->ID, 'job_location', [ 'fields' => 'names' ] ),
					'employment_type' => wp_get_post_terms( $job->ID, 'employment_type', [ 'fields' => 'names' ] ),
				],
			];
		}

		return $export;
	}

	/**
	 * Taxonomien exportieren
	 *
	 * @return array
	 */
	private function getTaxonomies(): array {
		$taxonomies = [ 'job_category', 'job_location', 'employment_type' ];
		$export     = [];

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				]
			);

			if ( is_wp_error( $terms ) ) {
				$export[ $taxonomy ] = [];
				continue;
			}

			$export[ $taxonomy ] = array_map(
				function ( $term ) {
					return [
						'term_id'     => $term->term_id,
						'name'        => $term->name,
						'slug'        => $term->slug,
						'description' => $term->description,
						'parent'      => $term->parent,
					];
				},
				$terms
			);
		}

		return $export;
	}

	/**
	 * Kandidaten exportieren
	 *
	 * Hinweis: Tabellennamen sind hardcoded (rp_candidates), nur $wpdb->prefix ist dynamisch.
	 * Da prefix von WordPress kontrolliert wird, ist dies sicher.
	 *
	 * @return array
	 */
	private function getCandidates(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_candidates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded, only prefix is dynamic
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Bewerbungen exportieren
	 *
	 * @return array
	 */
	private function getApplications(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Dokument-Metadaten exportieren (ohne Dateien)
	 *
	 * @return array
	 */
	private function getDocumentsMeta(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_documents';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		$documents = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];

		// Pfade entfernen aus Sicherheitsgründen.
		foreach ( $documents as &$doc ) {
			unset( $doc['file_path'] );
		}

		return $documents;
	}

	/**
	 * Aktivitäts-Log exportieren
	 *
	 * @return array
	 */
	private function getActivityLog(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_activity_log';

		// Nur die letzten 1000 Einträge.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded, LIMIT is constant
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", 1000 ),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Notizen exportieren
	 *
	 * @return array
	 */
	private function getNotes(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_notes';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Bewertungen exportieren
	 *
	 * @return array
	 */
	private function getRatings(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_ratings';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Talent-Pool exportieren
	 *
	 * @return array
	 */
	private function getTalentPool(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_talent_pool';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * E-Mail-Vorlagen exportieren
	 *
	 * @return array
	 */
	private function getEmailTemplates(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_email_templates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * E-Mail-Signaturen exportieren
	 *
	 * @return array
	 */
	private function getSignatures(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_signatures';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Feld-Definitionen exportieren
	 *
	 * @return array
	 */
	private function getFieldDefinitions(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_field_definitions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Formular-Vorlagen exportieren
	 *
	 * @return array
	 */
	private function getFormTemplates(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_form_templates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Formular-Konfigurationen exportieren
	 *
	 * @return array
	 */
	private function getFormConfig(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_form_config';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Webhooks exportieren
	 *
	 * @return array
	 */
	private function getWebhooks(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_webhooks';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Job-Zuweisungen exportieren
	 *
	 * @return array
	 */
	private function getJobAssignments(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_user_job_assignments';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * E-Mail-Log exportieren (limitiert auf 5000)
	 *
	 * @return array
	 */
	private function getEmailLog(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_email_log';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded, LIMIT is constant
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", 5000 ),
			ARRAY_A
		) ?: [];
	}

	/**
	 * AI-Analysen exportieren
	 *
	 * @return array
	 */
	private function getAiAnalyses(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_ai_analyses';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is hardcoded
		return $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A ) ?: [];
	}

	/**
	 * Export als JSON-String
	 *
	 * @return string
	 */
	public function toJson(): string {
		return wp_json_encode( $this->createBackup(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Export als Download
	 */
	public function download(): void {
		$filename = sprintf(
			'recruiting-playbook-backup-%s.json',
			gmdate( 'Y-m-d-His' )
		);

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $this->toJson(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}

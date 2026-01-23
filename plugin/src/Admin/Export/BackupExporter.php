<?php
/**
 * Plugin-Daten als JSON exportieren
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Export;

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
			'meta'         => $this->getMetaData(),
			'settings'     => $this->getSettings(),
			'jobs'         => $this->getJobs(),
			'taxonomies'   => $this->getTaxonomies(),
			'candidates'   => $this->getCandidates(),
			'applications' => $this->getApplications(),
			'documents'    => $this->getDocumentsMeta(),
			'activity_log' => $this->getActivityLog(),
		];
	}

	/**
	 * Meta-Daten
	 *
	 * @return array
	 */
	private function getMetaData(): array {
		return [
			'plugin_version' => RP_VERSION,
			'wp_version'     => get_bloginfo( 'version' ),
			'php_version'    => PHP_VERSION,
			'site_url'       => get_site_url(),
			'export_date'    => current_time( 'mysql' ),
			'export_user'    => wp_get_current_user()->user_login,
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

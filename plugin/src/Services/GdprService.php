<?php
/**
 * DSGVO-Funktionen: Löschen, Anonymisieren, Exportieren
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Service für DSGVO-Funktionen
 */
class GdprService {

	/**
	 * Bewerbung soft-löschen
	 *
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public function softDeleteApplication( int $application_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';

		$result = $wpdb->update(
			$table,
			[
				'status'     => 'deleted',
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'id' => $application_id ]
		);

		if ( false === $result ) {
			return false;
		}

		// Dokumente markieren.
		$doc_table = $wpdb->prefix . 'rp_documents';
		$wpdb->update(
			$doc_table,
			[
				'is_deleted' => 1,
				'deleted_at' => current_time( 'mysql' ),
			],
			[ 'application_id' => $application_id ]
		);

		// Logging.
		$this->logAction( $application_id, 'soft_deleted' );

		return true;
	}

	/**
	 * Bewerbung vollständig löschen (Hard Delete)
	 *
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public function hardDeleteApplication( int $application_id ): bool {
		global $wpdb;

		// Dokumente physisch löschen.
		$this->deleteApplicationDocuments( $application_id );

		// DB-Einträge löschen.
		$applications_table = $wpdb->prefix . 'rp_applications';
		$documents_table    = $wpdb->prefix . 'rp_documents';
		$log_table          = $wpdb->prefix . 'rp_activity_log';

		$wpdb->delete( $documents_table, [ 'application_id' => $application_id ] );
		$wpdb->delete(
			$log_table,
			[
				'object_type' => 'application',
				'object_id'   => $application_id,
			]
		);
		$wpdb->delete( $applications_table, [ 'id' => $application_id ] );

		return true;
	}

	/**
	 * Kandidat löschen (alle Bewerbungen)
	 *
	 * @param int $candidate_id Candidate ID.
	 * @return bool
	 */
	public function deleteCandidate( int $candidate_id ): bool {
		global $wpdb;

		// Alle Bewerbungen des Kandidaten holen.
		$applications_table = $wpdb->prefix . 'rp_applications';
		$application_ids    = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$applications_table} WHERE candidate_id = %d",
				$candidate_id
			)
		);

		// Alle Bewerbungen löschen.
		foreach ( $application_ids as $app_id ) {
			$this->hardDeleteApplication( (int) $app_id );
		}

		// Kandidat löschen.
		$candidates_table = $wpdb->prefix . 'rp_candidates';
		$wpdb->delete( $candidates_table, [ 'id' => $candidate_id ] );

		return true;
	}

	/**
	 * Kandidaten-Daten anonymisieren
	 *
	 * @param int $candidate_id Candidate ID.
	 * @return bool
	 */
	public function anonymizeCandidate( int $candidate_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_candidates';

		$result = $wpdb->update(
			$table,
			[
				'email'          => 'anonymized-' . $candidate_id . '@deleted.local',
				'first_name'     => 'Anonymized',
				'last_name'      => '',
				'phone'          => '',
				'address_street' => '',
				'address_city'   => '',
				'address_zip'    => '',
				'notes'          => '',
				'updated_at'     => current_time( 'mysql' ),
			],
			[ 'id' => $candidate_id ]
		);

		// Bewerbungen anonymisieren.
		$applications_table = $wpdb->prefix . 'rp_applications';
		$wpdb->update(
			$applications_table,
			[
				'cover_letter'  => '',
				'ip_address'    => '0.0.0.0',
				'user_agent'    => '',
				'custom_fields' => '',
			],
			[ 'candidate_id' => $candidate_id ]
		);

		// Dokumente löschen.
		$application_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$applications_table} WHERE candidate_id = %d",
				$candidate_id
			)
		);

		foreach ( $application_ids as $app_id ) {
			$this->deleteApplicationDocuments( (int) $app_id );
		}

		return false !== $result;
	}

	/**
	 * Dokumente einer Bewerbung physisch löschen
	 *
	 * @param int $application_id Application ID.
	 */
	private function deleteApplicationDocuments( int $application_id ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_documents';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$documents = $wpdb->get_results(
			$wpdb->prepare( "SELECT file_path FROM {$table} WHERE application_id = %d", $application_id ),
			ARRAY_A
		);

		foreach ( $documents as $doc ) {
			if ( ! empty( $doc['file_path'] ) && file_exists( $doc['file_path'] ) ) {
				wp_delete_file( $doc['file_path'] );

				// Prüfen ob Datei tatsächlich gelöscht wurde (für DSGVO-Audit).
				if ( file_exists( $doc['file_path'] ) ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log(
						sprintf(
							'[Recruiting Playbook] GDPR: Failed to delete file: %s',
							$doc['file_path']
						)
					);
				}
			}
		}

		// DB-Einträge löschen.
		$wpdb->delete( $table, [ 'application_id' => $application_id ] );
	}

	/**
	 * Datenauskunft (DSGVO Art. 15)
	 *
	 * @param int $candidate_id Candidate ID.
	 * @return array
	 */
	public function exportCandidateData( int $candidate_id ): array {
		global $wpdb;

		// Kandidat.
		$candidates_table = $wpdb->prefix . 'rp_candidates';
		$candidate        = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$candidates_table} WHERE id = %d", $candidate_id ),
			ARRAY_A
		);

		if ( ! $candidate ) {
			return [];
		}

		// Bewerbungen.
		$applications_table = $wpdb->prefix . 'rp_applications';
		$applications       = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$applications_table} WHERE candidate_id = %d", $candidate_id ),
			ARRAY_A
		);

		// Dokumente (Metadaten).
		$documents_table = $wpdb->prefix . 'rp_documents';
		$documents       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.* FROM {$documents_table} d
				 JOIN {$applications_table} a ON d.application_id = a.id
				 WHERE a.candidate_id = %d",
				$candidate_id
			),
			ARRAY_A
		);

		// Pfade aus Sicherheitsgründen entfernen.
		foreach ( $documents as &$doc ) {
			unset( $doc['path'] );
		}

		// Aktivitäts-Log.
		$log_table       = $wpdb->prefix . 'rp_activity_log';
		$application_ids = array_column( $applications ?: [], 'id' );

		$activities = [];
		if ( ! empty( $application_ids ) ) {
			$ids_placeholder = implode( ',', array_fill( 0, count( $application_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$activities = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$log_table}
					 WHERE object_type = 'application' AND object_id IN ({$ids_placeholder})",
					...$application_ids
				),
				ARRAY_A
			);
		}

		return [
			'export_date'  => current_time( 'mysql' ),
			'candidate'    => $candidate,
			'applications' => $applications ?: [],
			'documents'    => $documents ?: [],
			'activity_log' => $activities ?: [],
		];
	}

	/**
	 * Datenauskunft als JSON-Download
	 *
	 * @param int $candidate_id Candidate ID.
	 */
	public function downloadCandidateData( int $candidate_id ): void {
		$data = $this->exportCandidateData( $candidate_id );

		if ( empty( $data ) ) {
			wp_die( esc_html__( 'Candidate not found.', 'recruiting-playbook' ) );
		}

		$filename = sprintf(
			'datenauskunft-kandidat-%d-%s.json',
			$candidate_id,
			gmdate( 'Y-m-d' )
		);

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );

		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Datenauskunft für Bewerbung
	 *
	 * @param int $application_id Application ID.
	 */
	public function downloadApplicationData( int $application_id ): void {
		global $wpdb;

		$applications_table = $wpdb->prefix . 'rp_applications';
		$application        = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$applications_table} WHERE id = %d", $application_id ),
			ARRAY_A
		);

		if ( ! $application ) {
			wp_die( esc_html__( 'Application not found.', 'recruiting-playbook' ) );
		}

		$data = $this->exportCandidateData( (int) $application['candidate_id'] );

		$filename = sprintf(
			'datenauskunft-bewerbung-%d-%s.json',
			$application_id,
			gmdate( 'Y-m-d' )
		);

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );

		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Aktion loggen
	 *
	 * @param int    $application_id Application ID.
	 * @param string $action         Action name.
	 */
	private function logAction( int $application_id, string $action ): void {
		global $wpdb;

		$table        = $wpdb->prefix . 'rp_activity_log';
		$current_user = wp_get_current_user();

		$wpdb->insert(
			$table,
			[
				'object_type' => 'application',
				'object_id'   => $application_id,
				'action'      => $action,
				'user_id'     => $current_user->ID,
				'user_name'   => $current_user->display_name,
				'created_at'  => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Automatische Löschung alter Daten (DSGVO-Aufbewahrungsfrist)
	 *
	 * @param int $months Anzahl Monate.
	 * @return int Anzahl gelöschter Bewerbungen.
	 */
	public function cleanupOldData( int $months = 24 ): int {
		global $wpdb;

		$cutoff_date   = gmdate( 'Y-m-d H:i:s', strtotime( "-{$months} months" ) );
		$deleted_count = 0;

		// Soft-gelöschte Bewerbungen endgültig löschen.
		$applications_table = $wpdb->prefix . 'rp_applications';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$old_applications = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$applications_table}
				 WHERE status = 'deleted' AND updated_at < %s",
				$cutoff_date
			)
		);

		foreach ( $old_applications as $app_id ) {
			$this->hardDeleteApplication( (int) $app_id );
			++$deleted_count;
		}

		return $deleted_count;
	}
}

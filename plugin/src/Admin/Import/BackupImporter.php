<?php
/**
 * Plugin-Daten aus JSON importieren
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Backup-Import Klasse
 */
class BackupImporter {

	/**
	 * ID-Mapper
	 *
	 * @var IdMapper
	 */
	private IdMapper $mapper;

	/**
	 * Import-Ergebnis
	 *
	 * @var ImportResult
	 */
	private ImportResult $result;

	/**
	 * Import-Optionen
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->mapper = new IdMapper();
		$this->result = new ImportResult();
	}

	/**
	 * Backup-JSON validieren
	 *
	 * @param string $json JSON-String.
	 * @return array|\WP_Error Parsed data oder Fehler.
	 */
	public function validateBackup( string $json ) {
		$data = json_decode( $json, true );

		if ( null === $data || JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error( 'invalid_json', __( 'The file does not contain valid JSON.', 'recruiting-playbook' ) );
		}

		if ( empty( $data['meta'] ) || empty( $data['meta']['plugin_version'] ) ) {
			return new \WP_Error( 'invalid_backup', __( 'The file is not a valid Recruiting Playbook backup.', 'recruiting-playbook' ) );
		}

		$required_keys = [ 'meta', 'settings' ];
		foreach ( $required_keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				return new \WP_Error(
					'missing_key',
					/* translators: %s: key name */
					sprintf( __( 'Required key "%s" is missing in the backup file.', 'recruiting-playbook' ), $key )
				);
			}
		}

		return $data;
	}

	/**
	 * Import durchführen
	 *
	 * @param array $data    Backup-Daten.
	 * @param array $options Import-Optionen.
	 * @return ImportResult
	 */
	public function import( array $data, array $options = [] ): ImportResult {
		$this->options = wp_parse_args(
			$options,
			[
				'settings_mode'       => 'skip',       // skip, merge, overwrite
				'duplicate_candidates' => 'skip',      // skip, update
				'duplicate_jobs'      => 'skip',       // skip, update
				'import_activity_log' => false,
				'import_email_log'    => false,
			]
		);

		// Import-Reihenfolge (Abhängigkeitskette).
		$this->importSettings( $data['settings'] ?? [] );
		$this->importFieldDefinitions( $data['field_definitions'] ?? [] );
		$this->importFormTemplates( $data['form_templates'] ?? [] );
		$this->importFormConfig( $data['form_config'] ?? [] );
		$this->importEmailTemplates( $data['email_templates'] ?? [] );
		$this->importSignatures( $data['signatures'] ?? [] );
		$this->importWebhooks( $data['webhooks'] ?? [] );
		$this->importTaxonomies( $data['taxonomies'] ?? [] );
		$this->importJobs( $data['jobs'] ?? [] );
		$this->importCandidates( $data['candidates'] ?? [] );
		$this->importApplications( $data['applications'] ?? [] );
		$this->importDocuments( $data['documents'] ?? [] );
		$this->importNotes( $data['notes'] ?? [] );
		$this->importRatings( $data['ratings'] ?? [] );
		$this->importTalentPool( $data['talent_pool'] ?? [] );
		$this->importJobAssignments( $data['job_assignments'] ?? [] );
		$this->importAiAnalyses( $data['ai_analyses'] ?? [] );

		if ( $this->options['import_activity_log'] ) {
			$this->importActivityLog( $data['activity_log'] ?? [] );
		}

		if ( $this->options['import_email_log'] ) {
			$this->importEmailLog( $data['email_log'] ?? [] );
		}

		return $this->result;
	}

	/**
	 * Einstellungen importieren
	 *
	 * @param array $settings Einstellungen.
	 */
	private function importSettings( array $settings ): void {
		if ( empty( $settings ) ) {
			return;
		}

		$mode = $this->options['settings_mode'];

		if ( 'skip' === $mode ) {
			$this->result->addSkipped( 'settings' );
			return;
		}

		$rp_settings = $settings['rp_settings'] ?? [];

		if ( empty( $rp_settings ) ) {
			$this->result->addSkipped( 'settings' );
			return;
		}

		if ( 'overwrite' === $mode ) {
			update_option( 'rp_settings', $rp_settings );
			$this->result->addUpdated( 'settings' );
		} elseif ( 'merge' === $mode ) {
			$current = get_option( 'rp_settings', [] );
			$merged  = array_merge( $current, $rp_settings );
			update_option( 'rp_settings', $merged );
			$this->result->addUpdated( 'settings' );
		}
	}

	/**
	 * Feld-Definitionen importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importFieldDefinitions( array $rows ): void {
		$this->importGenericTable( $rows, 'rp_field_definitions', 'field_definitions', 'field_key' );
	}

	/**
	 * Formular-Vorlagen importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importFormTemplates( array $rows ): void {
		$this->importGenericTable( $rows, 'rp_form_templates', 'form_templates', 'slug' );
	}

	/**
	 * Formular-Konfigurationen importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importFormConfig( array $rows ): void {
		$this->importGenericTable( $rows, 'rp_form_config', 'form_config' );
	}

	/**
	 * E-Mail-Vorlagen importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importEmailTemplates( array $rows ): void {
		$this->importGenericTable( $rows, 'rp_email_templates', 'email_templates', 'slug' );
	}

	/**
	 * Signaturen importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importSignatures( array $rows ): void {
		$this->importGenericTable( $rows, 'rp_signatures', 'signatures' );
	}

	/**
	 * Webhooks importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importWebhooks( array $rows ): void {
		$this->importGenericTable( $rows, 'rp_webhooks', 'webhooks', 'url' );
	}

	/**
	 * Taxonomien importieren
	 *
	 * @param array $taxonomies Taxonomie-Daten.
	 */
	private function importTaxonomies( array $taxonomies ): void {
		if ( empty( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy => $terms ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				$this->result->addWarning(
					/* translators: %s: taxonomy name */
					sprintf( __( 'Taxonomy "%s" does not exist, skipped.', 'recruiting-playbook' ), $taxonomy )
				);
				continue;
			}

			foreach ( $terms as $term_data ) {
				$existing = get_term_by( 'slug', $term_data['slug'], $taxonomy );

				if ( $existing ) {
					$this->mapper->add( 'term', (int) $term_data['term_id'], (int) $existing->term_id );
					$this->result->addSkipped( 'taxonomies' );
					continue;
				}

				$new_term = wp_insert_term(
					$term_data['name'],
					$taxonomy,
					[
						'slug'        => $term_data['slug'],
						'description' => $term_data['description'] ?? '',
					]
				);

				if ( is_wp_error( $new_term ) ) {
					$this->result->addWarning(
						/* translators: 1: term name, 2: error message */
						sprintf( __( 'Could not create term "%1$s": %2$s', 'recruiting-playbook' ), $term_data['name'], $new_term->get_error_message() )
					);
					continue;
				}

				$this->mapper->add( 'term', (int) $term_data['term_id'], (int) $new_term['term_id'] );
				$this->result->addCreated( 'taxonomies' );
			}
		}
	}

	/**
	 * Jobs importieren
	 *
	 * @param array $jobs Job-Daten.
	 */
	private function importJobs( array $jobs ): void {
		if ( empty( $jobs ) ) {
			return;
		}

		foreach ( $jobs as $job ) {
			$old_id = (int) $job['ID'];

			// Duplikat-Check per Titel + post_date.
			if ( 'skip' === $this->options['duplicate_jobs'] ) {
				$existing = get_posts(
					[
						'post_type'   => 'job_listing',
						'title'       => $job['post_title'],
						'post_status' => 'any',
						'numberposts' => 1,
					]
				);

				if ( ! empty( $existing ) ) {
					$this->mapper->add( 'job', $old_id, $existing[0]->ID );
					$this->result->addSkipped( 'jobs' );
					continue;
				}
			}

			$post_data = [
				'post_type'    => 'job_listing',
				'post_title'   => $job['post_title'],
				'post_content' => $job['post_content'] ?? '',
				'post_excerpt' => $job['post_excerpt'] ?? '',
				'post_status'  => $job['post_status'] ?? 'draft',
				'post_date'    => $job['post_date'] ?? '',
			];

			$new_id = wp_insert_post( $post_data, true );

			if ( is_wp_error( $new_id ) ) {
				$this->result->addWarning(
					/* translators: 1: job title, 2: error */
					sprintf( __( 'Could not import job "%1$s": %2$s', 'recruiting-playbook' ), $job['post_title'], $new_id->get_error_message() )
				);
				continue;
			}

			$this->mapper->add( 'job', $old_id, $new_id );

			// Meta-Felder.
			if ( ! empty( $job['meta'] ) ) {
				foreach ( $job['meta'] as $key => $value ) {
					update_post_meta( $new_id, $key, $value );
				}
			}

			// Taxonomien zuweisen.
			if ( ! empty( $job['taxonomies'] ) ) {
				foreach ( $job['taxonomies'] as $taxonomy => $term_names ) {
					if ( ! taxonomy_exists( $taxonomy ) || empty( $term_names ) ) {
						continue;
					}
					wp_set_object_terms( $new_id, $term_names, $taxonomy );
				}
			}

			$this->result->addCreated( 'jobs' );
		}
	}

	/**
	 * Kandidaten importieren
	 *
	 * @param array $candidates Kandidaten-Daten.
	 */
	private function importCandidates( array $candidates ): void {
		if ( empty( $candidates ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rp_candidates';

		foreach ( $candidates as $candidate ) {
			$old_id = (int) $candidate['id'];

			// Deduplizierung per E-Mail.
			if ( ! empty( $candidate['email'] ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$existing = $wpdb->get_row(
					$wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s", $candidate['email'] ),
					ARRAY_A
				);

				if ( $existing ) {
					$this->mapper->add( 'candidate', $old_id, (int) $existing['id'] );

					if ( 'update' === $this->options['duplicate_candidates'] ) {
						$update_data = $candidate;
						unset( $update_data['id'] );
						$wpdb->update( $table, $update_data, [ 'id' => (int) $existing['id'] ] );
						$this->result->addUpdated( 'candidates' );
					} else {
						$this->result->addSkipped( 'candidates' );
					}
					continue;
				}
			}

			$insert_data = $candidate;
			unset( $insert_data['id'] );

			$wpdb->insert( $table, $insert_data );
			$new_id = (int) $wpdb->insert_id;

			if ( $new_id ) {
				$this->mapper->add( 'candidate', $old_id, $new_id );
				$this->result->addCreated( 'candidates' );
			} else {
				$this->result->addWarning(
					/* translators: %s: email */
					sprintf( __( 'Could not import candidate "%s".', 'recruiting-playbook' ), $candidate['email'] ?? '?' )
				);
			}
		}
	}

	/**
	 * Bewerbungen importieren
	 *
	 * @param array $applications Bewerbungs-Daten.
	 */
	private function importApplications( array $applications ): void {
		if ( empty( $applications ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rp_applications';

		foreach ( $applications as $app ) {
			$old_id = (int) $app['id'];

			$insert_data = $app;
			unset( $insert_data['id'] );

			// IDs mappen.
			if ( ! empty( $insert_data['candidate_id'] ) ) {
				$new_candidate_id = $this->mapper->get( 'candidate', (int) $insert_data['candidate_id'] );
				if ( $new_candidate_id ) {
					$insert_data['candidate_id'] = $new_candidate_id;
				} else {
					$this->result->addWarning(
						/* translators: %d: application ID */
						sprintf( __( 'Application #%d: Candidate not found, skipped.', 'recruiting-playbook' ), $old_id )
					);
					$this->result->addSkipped( 'applications' );
					continue;
				}
			}

			if ( ! empty( $insert_data['job_id'] ) ) {
				$new_job_id = $this->mapper->get( 'job', (int) $insert_data['job_id'] );
				if ( $new_job_id ) {
					$insert_data['job_id'] = $new_job_id;
				} else {
					$this->result->addWarning(
						/* translators: %d: application ID */
						sprintf( __( 'Application #%d: Job not found, skipped.', 'recruiting-playbook' ), $old_id )
					);
					$this->result->addSkipped( 'applications' );
					continue;
				}
			}

			$wpdb->insert( $table, $insert_data );
			$new_id = (int) $wpdb->insert_id;

			if ( $new_id ) {
				$this->mapper->add( 'application', $old_id, $new_id );
				$this->result->addCreated( 'applications' );
			} else {
				$this->result->addSkipped( 'applications' );
			}
		}
	}

	/**
	 * Dokument-Metadaten importieren
	 *
	 * @param array $documents Dokument-Daten.
	 */
	private function importDocuments( array $documents ): void {
		if ( empty( $documents ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rp_documents';

		foreach ( $documents as $doc ) {
			$insert_data = $doc;
			unset( $insert_data['id'] );

			// application_id mappen.
			if ( ! empty( $insert_data['application_id'] ) ) {
				$new_app_id = $this->mapper->get( 'application', (int) $insert_data['application_id'] );
				if ( $new_app_id ) {
					$insert_data['application_id'] = $new_app_id;
				} else {
					$this->result->addSkipped( 'documents' );
					continue;
				}
			}

			$wpdb->insert( $table, $insert_data );

			if ( $wpdb->insert_id ) {
				$this->result->addCreated( 'documents' );
			} else {
				$this->result->addSkipped( 'documents' );
			}
		}
	}

	/**
	 * Notizen importieren
	 *
	 * @param array $notes Notiz-Daten.
	 */
	private function importNotes( array $notes ): void {
		$this->importMappedTable( $notes, 'rp_notes', 'notes', 'application_id', 'application' );
	}

	/**
	 * Bewertungen importieren
	 *
	 * @param array $ratings Bewertungs-Daten.
	 */
	private function importRatings( array $ratings ): void {
		$this->importMappedTable( $ratings, 'rp_ratings', 'ratings', 'application_id', 'application' );
	}

	/**
	 * Talent-Pool importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importTalentPool( array $rows ): void {
		$this->importMappedTable( $rows, 'rp_talent_pool', 'talent_pool', 'candidate_id', 'candidate' );
	}

	/**
	 * Job-Zuweisungen importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importJobAssignments( array $rows ): void {
		if ( empty( $rows ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rp_user_job_assignments';

		foreach ( $rows as $row ) {
			$insert_data = $row;
			unset( $insert_data['id'] );

			// job_id mappen.
			if ( ! empty( $insert_data['job_id'] ) ) {
				$new_job_id = $this->mapper->get( 'job', (int) $insert_data['job_id'] );
				if ( $new_job_id ) {
					$insert_data['job_id'] = $new_job_id;
				} else {
					$this->result->addSkipped( 'job_assignments' );
					continue;
				}
			}

			// user_id prüfen.
			if ( ! empty( $insert_data['user_id'] ) && ! get_user_by( 'id', (int) $insert_data['user_id'] ) ) {
				$this->result->addWarning(
					/* translators: %d: user ID */
					sprintf( __( 'Job assignment: User #%d does not exist, skipped.', 'recruiting-playbook' ), (int) $insert_data['user_id'] )
				);
				$this->result->addSkipped( 'job_assignments' );
				continue;
			}

			$wpdb->insert( $table, $insert_data );

			if ( $wpdb->insert_id ) {
				$this->result->addCreated( 'job_assignments' );
			} else {
				$this->result->addSkipped( 'job_assignments' );
			}
		}
	}

	/**
	 * AI-Analysen importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importAiAnalyses( array $rows ): void {
		if ( empty( $rows ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rp_ai_analyses';

		foreach ( $rows as $row ) {
			$insert_data = $row;
			unset( $insert_data['id'] );

			// job_id mappen.
			if ( ! empty( $insert_data['job_id'] ) ) {
				$new_job_id = $this->mapper->get( 'job', (int) $insert_data['job_id'] );
				if ( $new_job_id ) {
					$insert_data['job_id'] = $new_job_id;
				} else {
					$this->result->addSkipped( 'ai_analyses' );
					continue;
				}
			}

			// application_id mappen (falls vorhanden).
			if ( ! empty( $insert_data['application_id'] ) ) {
				$new_app_id = $this->mapper->get( 'application', (int) $insert_data['application_id'] );
				if ( $new_app_id ) {
					$insert_data['application_id'] = $new_app_id;
				} else {
					$this->result->addSkipped( 'ai_analyses' );
					continue;
				}
			}

			$wpdb->insert( $table, $insert_data );

			if ( $wpdb->insert_id ) {
				$this->result->addCreated( 'ai_analyses' );
			} else {
				$this->result->addSkipped( 'ai_analyses' );
			}
		}
	}

	/**
	 * Aktivitäts-Log importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importActivityLog( array $rows ): void {
		if ( empty( $rows ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rp_activity_log';

		foreach ( $rows as $row ) {
			$insert_data = $row;
			unset( $insert_data['id'] );

			// object_id mappen je nach Typ.
			if ( ! empty( $insert_data['object_type'] ) && ! empty( $insert_data['object_id'] ) ) {
				$type_map = [
					'application' => 'application',
					'job'         => 'job',
					'candidate'   => 'candidate',
				];

				$map_type = $type_map[ $insert_data['object_type'] ] ?? null;
				if ( $map_type ) {
					$new_id = $this->mapper->get( $map_type, (int) $insert_data['object_id'] );
					if ( $new_id ) {
						$insert_data['object_id'] = $new_id;
					}
				}
			}

			$wpdb->insert( $table, $insert_data );

			if ( $wpdb->insert_id ) {
				$this->result->addCreated( 'activity_log' );
			} else {
				$this->result->addSkipped( 'activity_log' );
			}
		}
	}

	/**
	 * E-Mail-Log importieren
	 *
	 * @param array $rows Daten.
	 */
	private function importEmailLog( array $rows ): void {
		if ( empty( $rows ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rp_email_log';

		foreach ( $rows as $row ) {
			$insert_data = $row;
			unset( $insert_data['id'] );

			// application_id mappen (falls vorhanden).
			if ( ! empty( $insert_data['application_id'] ) ) {
				$new_app_id = $this->mapper->get( 'application', (int) $insert_data['application_id'] );
				if ( $new_app_id ) {
					$insert_data['application_id'] = $new_app_id;
				}
			}

			$wpdb->insert( $table, $insert_data );

			if ( $wpdb->insert_id ) {
				$this->result->addCreated( 'email_log' );
			} else {
				$this->result->addSkipped( 'email_log' );
			}
		}
	}

	/**
	 * Generische Tabelle importieren (standalone, ohne Abhängigkeit)
	 *
	 * @param array       $rows       Daten.
	 * @param string      $table_name Tabellenname (ohne Prefix).
	 * @param string      $type       Typ für Statistik.
	 * @param string|null $unique_key Spalte für Duplikat-Erkennung.
	 */
	private function importGenericTable( array $rows, string $table_name, string $type, ?string $unique_key = null ): void {
		if ( empty( $rows ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . $table_name;

		foreach ( $rows as $row ) {
			$old_id = (int) ( $row['id'] ?? 0 );

			// Duplikat-Check per unique_key.
			if ( $unique_key && ! empty( $row[ $unique_key ] ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$existing = $wpdb->get_row(
					$wpdb->prepare( "SELECT id FROM {$table} WHERE {$unique_key} = %s LIMIT 1", $row[ $unique_key ] ),
					ARRAY_A
				);

				if ( $existing ) {
					if ( $old_id ) {
						$this->mapper->add( $type, $old_id, (int) $existing['id'] );
					}
					$this->result->addSkipped( $type );
					continue;
				}
			}

			$insert_data = $row;
			unset( $insert_data['id'] );

			$wpdb->insert( $table, $insert_data );
			$new_id = (int) $wpdb->insert_id;

			if ( $new_id ) {
				if ( $old_id ) {
					$this->mapper->add( $type, $old_id, $new_id );
				}
				$this->result->addCreated( $type );
			} else {
				$this->result->addSkipped( $type );
			}
		}
	}

	/**
	 * Tabelle mit gemappter Fremdschlüssel-Spalte importieren
	 *
	 * @param array  $rows        Daten.
	 * @param string $table_name  Tabellenname (ohne Prefix).
	 * @param string $type        Typ für Statistik.
	 * @param string $fk_column   Fremdschlüssel-Spalte.
	 * @param string $fk_map_type Mapper-Typ für die FK-Spalte.
	 */
	private function importMappedTable( array $rows, string $table_name, string $type, string $fk_column, string $fk_map_type ): void {
		if ( empty( $rows ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . $table_name;

		foreach ( $rows as $row ) {
			$insert_data = $row;
			unset( $insert_data['id'] );

			// FK mappen.
			if ( ! empty( $insert_data[ $fk_column ] ) ) {
				$new_fk_id = $this->mapper->get( $fk_map_type, (int) $insert_data[ $fk_column ] );
				if ( $new_fk_id ) {
					$insert_data[ $fk_column ] = $new_fk_id;
				} else {
					$this->result->addSkipped( $type );
					continue;
				}
			}

			$wpdb->insert( $table, $insert_data );

			if ( $wpdb->insert_id ) {
				$this->result->addCreated( $type );
			} else {
				$this->result->addSkipped( $type );
			}
		}
	}
}

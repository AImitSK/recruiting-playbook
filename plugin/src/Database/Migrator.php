<?php
/**
 * Datenbank-Migrationen verwalten
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Datenbank-Migrationen verwalten
 */
class Migrator {

	private const SCHEMA_VERSION = '2.3.0';
	private const SCHEMA_OPTION  = 'rp_db_version';

	/**
	 * Tabellen erstellen/aktualisieren
	 */
	public function createTables(): void {
		$current_version = get_option( self::SCHEMA_OPTION, '0' );
		$needs_migration = version_compare( $current_version, self::SCHEMA_VERSION, '<' );

		// Schema-Migrationen nur wenn nötig.
		if ( $needs_migration ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			// Alle Tabellen erstellen/aktualisieren.
			dbDelta( Schema::getCandidatesTableSql() );
			dbDelta( Schema::getApplicationsTableSql() );
			dbDelta( Schema::getDocumentsTableSql() );
			dbDelta( Schema::getActivityLogTableSql() );
			dbDelta( Schema::getNotesTableSql() );
			dbDelta( Schema::getRatingsTableSql() );
			dbDelta( Schema::getTalentPoolTableSql() );
			dbDelta( Schema::getEmailTemplatesTableSql() );
			dbDelta( Schema::getEmailLogTableSql() );
			dbDelta( Schema::getSignaturesTableSql() );
			dbDelta( Schema::getJobAssignmentsTableSql() );
			dbDelta( Schema::getStatsCacheTableSql() );
			dbDelta( Schema::getFieldDefinitionsTableSql() );
			dbDelta( Schema::getFormTemplatesTableSql() );
			dbDelta( Schema::getFormConfigTableSql() );
			dbDelta( Schema::getWebhooksTableSql() );
			dbDelta( Schema::getWebhookDeliveriesTableSql() );
			dbDelta( Schema::getApiKeysTableSql() );
			dbDelta( Schema::getAiAnalysesTableSql() );

			// Spezielle Migrationen für bestehende Installationen.
			$this->runMigrations( $current_version );

			// Version speichern.
			update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION );

			// Log erstellen.
			$this->log( 'Database migrated to version ' . self::SCHEMA_VERSION );
		}

		// IMMER Default-Daten prüfen (auch ohne Schema-Änderung).
		// Die Seed-Funktionen prüfen selbst ob Daten bereits existieren.
		$this->seedDefaultEmailTemplates();
		$this->seedDefaultCompanySignature();
		$this->seedSystemFields();
		$this->seedDefaultFormConfig();
	}

	/**
	 * Spezielle Migrationen ausführen
	 *
	 * @param string $from_version Version von der migriert wird.
	 */
	private function runMigrations( string $from_version ): void {
		// Migration 1.2.0: kanban_position Spalte hinzufügen.
		if ( version_compare( $from_version, '1.2.0', '<' ) ) {
			$this->migrateToKanbanPosition();
		}

		// Migration 1.3.0: Activity Log meta Spalte + Pro-Features Tabellen.
		if ( version_compare( $from_version, '1.3.0', '<' ) ) {
			$this->migrateToActivityLogMeta();
			$this->migrateToApplicationsDeletedAt();
		}

		// Migration 1.4.0: E-Mail-System Tabellen + Standard-Templates.
		if ( version_compare( $from_version, '1.4.0', '<' ) ) {
			$this->seedDefaultEmailTemplates();
		}

		// Migration 1.5.0: Signaturen-Tabelle + Default Firmen-Signatur.
		if ( version_compare( $from_version, '1.5.0', '<' ) ) {
			$this->seedDefaultCompanySignature();
		}

		// Migration 1.5.4: System-Templates mit is_system=0 korrigieren.
		if ( version_compare( $from_version, '1.5.4', '<' ) ) {
			$this->migrateFixSystemTemplatesFlag();
		}

		// Migration 1.8.0: Custom Fields Builder Tabellen + System-Felder.
		if ( version_compare( $from_version, '1.8.0', '<' ) ) {
			$this->seedSystemFields();
		}

		// Migration 1.9.0: Step-basierter Form Builder mit Draft/Publish.
		if ( version_compare( $from_version, '1.9.0', '<' ) ) {
			$this->seedDefaultFormConfig();
		}

		// Migration 2.0.0: email_hash für bestehende Kandidaten berechnen.
		if ( version_compare( $from_version, '2.0.0', '<' ) ) {
			$this->migrateEmailHash();
		}

		// Migration 2.0.1: Doppelte System-Felder entfernen (resume, privacy_consent).
		// Diese werden durch die grünen Spezialfelder im Form Builder ersetzt.
		if ( version_compare( $from_version, '2.0.1', '<' ) ) {
			$this->migrateRemoveDuplicateSystemFields();
			$this->migrateFormConfigToV2();
		}

		// Migration 2.0.2: phone und message zu Custom Fields machen (editierbar).
		if ( version_compare( $from_version, '2.0.2', '<' ) ) {
			$this->migratePhoneMessageToCustomFields();
		}
	}

	/**
	 * Migration: kanban_position Spalte hinzufügen
	 */
	private function migrateToKanbanPosition(): void {
		global $wpdb;

		$table = Schema::getTables()['applications'];

		// Prüfen ob Spalte bereits existiert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table} LIKE %s",
				'kanban_position'
			)
		);

		if ( empty( $column_exists ) ) {
			// Spalte hinzufügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN kanban_position int(11) DEFAULT 0 AFTER status" );

			// Index hinzufügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX kanban_sort (status, kanban_position)" );

			$this->log( 'Added kanban_position column to applications table' );
		}
	}

	/**
	 * Migration: Activity Log meta Spalte hinzufügen
	 */
	private function migrateToActivityLogMeta(): void {
		global $wpdb;

		$table = Schema::getTables()['activity_log'];

		// Prüfen ob meta Spalte bereits existiert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table} LIKE %s",
				'meta'
			)
		);

		if ( empty( $column_exists ) ) {
			// Spalte hinzufügen nach message.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN meta longtext DEFAULT NULL AFTER message" );

			$this->log( 'Added meta column to activity_log table' );
		}
	}

	/**
	 * Migration: Applications deleted_at Spalte hinzufügen
	 */
	private function migrateToApplicationsDeletedAt(): void {
		global $wpdb;

		$table = Schema::getTables()['applications'];

		// Prüfen ob deleted_at Spalte bereits existiert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table} LIKE %s",
				'deleted_at'
			)
		);

		if ( empty( $column_exists ) ) {
			// Spalte hinzufügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN deleted_at datetime DEFAULT NULL AFTER updated_at" );

			// Index hinzufügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD INDEX deleted_at (deleted_at)" );

			$this->log( 'Added deleted_at column to applications table' );
		}
	}

	/**
	 * Migration: email_hash für bestehende Kandidaten berechnen
	 *
	 * Setzt email_hash = SHA256(LOWER(TRIM(email))) für alle Kandidaten ohne email_hash.
	 */
	private function migrateEmailHash(): void {
		global $wpdb;

		$table = Schema::getTables()['candidates'];

		// Alle Kandidaten ohne email_hash aktualisieren.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$updated = $wpdb->query(
			"UPDATE {$table} SET email_hash = SHA2(LOWER(TRIM(email)), 256) WHERE email_hash IS NULL OR email_hash = ''"
		);

		if ( $updated > 0 ) {
			$this->log( 'Migrated email_hash for ' . $updated . ' candidates' );
		}
	}

	/**
	 * Migration: Doppelte System-Felder entfernen
	 *
	 * Entfernt 'resume' und 'privacy_consent' aus den System-Feldern,
	 * da diese durch die grünen Spezialfelder im Form Builder ersetzt werden:
	 * - 'file_upload' (ersetzt 'resume' / Bewerbungsunterlagen)
	 * - 'privacy_consent' im system_fields Array (ersetzt das DB-System-Feld)
	 */
	private function migrateRemoveDuplicateSystemFields(): void {
		global $wpdb;

		$table = Schema::getTables()['field_definitions'];

		// Felder entfernen die jetzt durch Spezialfelder ersetzt werden.
		$fields_to_remove = [ 'resume', 'privacy_consent' ];

		foreach ( $fields_to_remove as $field_key ) {
			// Nur globale System-Felder löschen (keine Template/Job-spezifischen).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->delete(
				$table,
				[
					'field_key' => $field_key,
					'is_system' => 1,
				],
				[ '%s', '%d' ]
			);

			if ( $deleted > 0 ) {
				$this->log( 'Removed duplicate system field: ' . $field_key );
			}
		}
	}

	/**
	 * Migration: FormConfig auf v2 Format migrieren
	 *
	 * Aktualisiert bestehende FormConfig-Einträge:
	 * - Entfernt 'resume' aus fields, ersetzt durch 'file_upload' in system_fields
	 * - Entfernt 'privacy_consent' aus fields, verschiebt nach system_fields
	 * - Fügt 'summary' zu system_fields im Finale-Step hinzu
	 */
	private function migrateFormConfigToV2(): void {
		global $wpdb;

		$table = Schema::getTables()['form_config'];

		// Beide Konfigurationen (draft und published) holen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$configs = $wpdb->get_results(
			"SELECT id, config_type, config_data, version FROM {$table}",
			ARRAY_A
		);

		if ( empty( $configs ) ) {
			return;
		}

		$updated = 0;

		foreach ( $configs as $config_row ) {
			$config_data = json_decode( $config_row['config_data'], true );

			if ( empty( $config_data ) || ! is_array( $config_data ) ) {
				continue;
			}

			// Bereits v2 - überspringen.
			if ( ( $config_data['version'] ?? 1 ) >= 2 ) {
				continue;
			}

			// Steps migrieren.
			if ( empty( $config_data['steps'] ) ) {
				continue;
			}

			$migrated = false;

			foreach ( $config_data['steps'] as &$step ) {
				// Dokumente-Step: resume -> file_upload.
				if ( 'step_documents' === $step['id'] ) {
					// system_fields initialisieren falls nicht vorhanden.
					if ( ! isset( $step['system_fields'] ) || ! is_array( $step['system_fields'] ) ) {
						$step['system_fields'] = [];
					}

					// file_upload hinzufügen falls noch nicht vorhanden.
					$has_file_upload = false;
					foreach ( $step['system_fields'] as $sf ) {
						if ( 'file_upload' === ( $sf['field_key'] ?? '' ) ) {
							$has_file_upload = true;
							break;
						}
					}

					if ( ! $has_file_upload ) {
						$step['system_fields'][] = [
							'field_key' => 'file_upload',
							'type'      => 'file_upload',
							'settings'  => [
								'label'         => __( 'Bewerbungsunterlagen', 'recruiting-playbook' ),
								'help_text'     => __( 'PDF, Word - max. 10 MB pro Datei', 'recruiting-playbook' ),
								'allowed_types' => 'pdf,doc,docx',
								'max_file_size' => 10,
								'max_files'     => 5,
								'is_required'   => true,
							],
						];
						$migrated = true;
					}

					// resume aus fields entfernen.
					if ( ! empty( $step['fields'] ) ) {
						$original_count      = count( $step['fields'] );
						$step['fields'] = array_values(
							array_filter(
								$step['fields'],
								function ( $field ) {
									return 'resume' !== ( $field['field_key'] ?? '' );
								}
							)
						);
						if ( count( $step['fields'] ) < $original_count ) {
							$migrated = true;
						}
					}
				}

				// Finale-Step: summary und privacy_consent als system_fields.
				if ( ! empty( $step['is_finale'] ) ) {
					// system_fields initialisieren falls nicht vorhanden.
					if ( ! isset( $step['system_fields'] ) || ! is_array( $step['system_fields'] ) ) {
						$step['system_fields'] = [];
					}

					// summary hinzufügen falls noch nicht vorhanden.
					$has_summary = false;
					foreach ( $step['system_fields'] as $sf ) {
						if ( 'summary' === ( $sf['field_key'] ?? '' ) ) {
							$has_summary = true;
							break;
						}
					}

					if ( ! $has_summary ) {
						// summary am Anfang einfügen.
						array_unshift( $step['system_fields'], [
							'field_key' => 'summary',
							'type'      => 'summary',
							'settings'  => [
								'title'            => __( 'Ihre Angaben im Überblick', 'recruiting-playbook' ),
								'layout'           => 'two-column',
								'additional_text'  => __( 'Bitte prüfen Sie Ihre Angaben vor dem Absenden.', 'recruiting-playbook' ),
								'show_only_filled' => false,
							],
						] );
						$migrated = true;
					}

					// privacy_consent in system_fields hinzufügen falls noch nicht vorhanden.
					$has_privacy = false;
					foreach ( $step['system_fields'] as $sf ) {
						if ( 'privacy_consent' === ( $sf['field_key'] ?? '' ) ) {
							$has_privacy = true;
							break;
						}
					}

					if ( ! $has_privacy ) {
						$step['system_fields'][] = [
							'field_key'    => 'privacy_consent',
							'type'         => 'privacy_consent',
							'is_removable' => false,
							'settings'     => [
								'checkbox_text' => __( 'Ich habe die {datenschutz_link} gelesen und stimme der Verarbeitung meiner Daten zu.', 'recruiting-playbook' ),
								'link_text'     => __( 'Datenschutzerklärung', 'recruiting-playbook' ),
							],
						];
						$migrated = true;
					}

					// privacy_consent aus fields entfernen.
					if ( ! empty( $step['fields'] ) ) {
						$original_count      = count( $step['fields'] );
						$step['fields'] = array_values(
							array_filter(
								$step['fields'],
								function ( $field ) {
									return 'privacy_consent' !== ( $field['field_key'] ?? '' );
								}
							)
						);
						if ( count( $step['fields'] ) < $original_count ) {
							$migrated = true;
						}
					}
				}
			}

			// Nur speichern wenn Änderungen gemacht wurden.
			if ( $migrated ) {
				$config_data['version'] = 2;
				$new_json               = wp_json_encode( $config_data, JSON_UNESCAPED_UNICODE );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table,
					[
						'config_data' => $new_json,
						'updated_at'  => current_time( 'mysql' ),
					],
					[ 'id' => $config_row['id'] ],
					[ '%s', '%s' ],
					[ '%d' ]
				);

				++$updated;
			}
		}

		if ( $updated > 0 ) {
			$this->log( 'Migrated ' . $updated . ' form config(s) to v2 format' );
		}
	}

	/**
	 * Migration: System-Templates Flag korrigieren
	 *
	 * Setzt is_system=1 für alle Templates mit bekannten System-Slugs.
	 */
	private function migrateFixSystemTemplatesFlag(): void {
		global $wpdb;

		$table = Schema::getTables()['email_templates'];

		$system_slugs = [
			'application-confirmation',
			'rejection-standard',
			'application-withdrawn',
			'talent-pool-added',
			'interview-invitation',
			'interview-reminder',
			'offer-letter',
			'contract-sent',
			'talent-pool-matching-job',
		];

		$placeholders = implode( ', ', array_fill( 0, count( $system_slugs ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET is_system = 1 WHERE slug IN ({$placeholders}) AND is_system = 0",
				...$system_slugs
			)
		);

		if ( $updated > 0 ) {
			$this->log( 'Fixed is_system flag for ' . $updated . ' templates' );
		}
	}

	/**
	 * Migration: Standard E-Mail-Templates einfügen
	 *
	 * Fügt fehlende System-Templates ein (prüft jeden Slug einzeln).
	 */
	private function seedDefaultEmailTemplates(): void {
		global $wpdb;

		$table     = Schema::getTables()['email_templates'];
		$now       = current_time( 'mysql' );
		$templates = $this->getDefaultTemplates();
		$inserted  = 0;

		foreach ( $templates as $template ) {
			// Prüfen ob dieses Template (per Slug) bereits existiert.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE slug = %s",
					$template['slug']
				)
			);

			if ( $exists > 0 ) {
				continue;
			}

			// Template einfügen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table,
				[
					'slug'       => $template['slug'],
					'name'       => $template['name'],
					'subject'    => $template['subject'],
					'body_html'  => $template['body_html'],
					'body_text'  => wp_strip_all_tags( $template['body_html'] ),
					'category'   => $template['category'],
					'is_active'  => 1,
					'is_default' => 1,
					'is_system'  => 1,
					'variables'  => wp_json_encode( $template['variables'] ?? [] ),
					'settings'   => wp_json_encode( [] ),
					'created_at' => $now,
					'updated_at' => $now,
				],
				[ '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s' ]
			);

			++$inserted;
		}

		if ( $inserted > 0 ) {
			$this->log( 'Seeded ' . $inserted . ' missing email templates' );
		}
	}

	/**
	 * Migration: Default Firmen-Signatur erstellen
	 */
	private function seedDefaultCompanySignature(): void {
		global $wpdb;

		$table = Schema::getTables()['signatures'];
		$now   = current_time( 'mysql' );

		// Prüfen ob bereits eine Firmen-Signatur existiert (user_id = NULL).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE user_id IS NULL"
		);

		if ( $exists > 0 ) {
			return;
		}

		// Firmenname aus Einstellungen oder Blog-Name.
		$settings     = get_option( 'rp_settings', [] );
		$company_name = $settings['company']['name'] ?? $settings['general']['company_name'] ?? get_bloginfo( 'name' );

		// Default Firmen-Signatur erstellen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'user_id'         => null,
				'name'            => __( 'Firmen-Signatur', 'recruiting-playbook' ),
				'greeting'        => __( 'Mit freundlichen Grüßen', 'recruiting-playbook' ),
				'content'         => sprintf(
					"%s\n%s",
					__( 'Ihr HR Team', 'recruiting-playbook' ),
					$company_name
				),
				'is_default'      => 1,
				'include_company' => 1,
				'created_at'      => $now,
				'updated_at'      => $now,
			],
			[ null, '%s', '%s', '%s', '%d', '%d', '%s', '%s' ]
		);

		$this->log( 'Created default company signature' );
	}

	/**
	 * Migration: Standard System-Felder für Bewerbungsformulare erstellen
	 *
	 * System-Felder können nicht gelöscht werden und bilden das Basis-Formular.
	 */
	private function seedSystemFields(): void {
		global $wpdb;

		$table    = Schema::getTables()['field_definitions'];
		$now      = current_time( 'mysql' );
		$inserted = 0;

		$system_fields = [
			[
				'field_key'   => 'first_name',
				'field_type'  => 'text',
				'label'       => __( 'Vorname', 'recruiting-playbook' ),
				'placeholder' => __( 'Max', 'recruiting-playbook' ),
				'is_required' => 1,
				'is_system'   => 1,
				'position'    => 1,
				'validation'  => wp_json_encode( [ 'min_length' => 2, 'max_length' => 100 ] ),
				'settings'    => wp_json_encode( [ 'width' => 'half', 'autocomplete' => 'given-name' ] ),
			],
			[
				'field_key'   => 'last_name',
				'field_type'  => 'text',
				'label'       => __( 'Nachname', 'recruiting-playbook' ),
				'placeholder' => __( 'Mustermann', 'recruiting-playbook' ),
				'is_required' => 1,
				'is_system'   => 1,
				'position'    => 2,
				'validation'  => wp_json_encode( [ 'min_length' => 2, 'max_length' => 100 ] ),
				'settings'    => wp_json_encode( [ 'width' => 'half', 'autocomplete' => 'family-name' ] ),
			],
			[
				'field_key'   => 'email',
				'field_type'  => 'email',
				'label'       => __( 'E-Mail', 'recruiting-playbook' ),
				'placeholder' => __( 'max.mustermann@beispiel.de', 'recruiting-playbook' ),
				'is_required' => 1,
				'is_system'   => 1,
				'position'    => 3,
				'settings'    => wp_json_encode( [ 'width' => 'half', 'autocomplete' => 'email' ] ),
			],
			[
				'field_key'   => 'phone',
				'field_type'  => 'phone',
				'label'       => __( 'Telefon', 'recruiting-playbook' ),
				'placeholder' => __( '+49 123 456789', 'recruiting-playbook' ),
				'is_required' => 0,
				'is_system'   => 0, // Custom Field (pre-installed, editierbar)
				'position'    => 4,
				'settings'    => wp_json_encode( [ 'width' => 'half', 'autocomplete' => 'tel' ] ),
			],
			[
				'field_key'   => 'message',
				'field_type'  => 'textarea',
				'label'       => __( 'Anschreiben', 'recruiting-playbook' ),
				'placeholder' => __( 'Warum möchten Sie bei uns arbeiten?', 'recruiting-playbook' ),
				'description' => __( 'Optional: Schreiben Sie uns, warum Sie sich für diese Stelle interessieren.', 'recruiting-playbook' ),
				'is_required' => 0,
				'is_system'   => 0, // Custom Field (pre-installed, editierbar)
				'position'    => 5,
				'validation'  => wp_json_encode( [ 'max_length' => 5000 ] ),
				'settings'    => wp_json_encode( [ 'rows' => 6 ] ),
			],
			// Hinweis: 'resume' und 'privacy_consent' werden NICHT mehr als System-Felder erstellt.
			// Diese werden durch die grünen Spezialfelder im Form Builder ersetzt:
			// - 'file_upload' (Bewerbungsunterlagen)
			// - 'privacy_consent' (Datenschutz-Zustimmung)
		];

		foreach ( $system_fields as $field ) {
			// Prüfen ob Feld bereits existiert (per field_key für globale System-Felder).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE field_key = %s AND template_id IS NULL AND job_id IS NULL",
					$field['field_key']
				)
			);

			if ( $exists > 0 ) {
				continue;
			}

			// Feld einfügen.
			$data = array_merge(
				$field,
				[
					'is_active'  => 1,
					'created_at' => $now,
					'updated_at' => $now,
				]
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $table, $data );
			++$inserted;
		}

		if ( $inserted > 0 ) {
			$this->log( 'Seeded ' . $inserted . ' system fields for custom fields builder' );
		}
	}

	/**
	 * Migration: Standard-Formular-Konfiguration erstellen
	 *
	 * Erstellt die initiale Step-basierte Formular-Konfiguration
	 * mit Draft und Published Einträgen.
	 */
	private function seedDefaultFormConfig(): void {
		global $wpdb;

		$table = Schema::getTables()['form_config'];
		$now   = current_time( 'mysql' );

		// Prüfen ob bereits eine Konfiguration existiert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE config_type IN (%s, %s)",
				'draft',
				'published'
			)
		);

		if ( $exists > 0 ) {
			return;
		}

		// Standard-Konfiguration mit Steps (v2 Format mit system_fields).
		$default_config = [
			'version'  => 2,
			'settings' => [
				'showStepIndicator' => true,
				'showStepTitles'    => true,
				'animateSteps'      => true,
			],
			'steps'    => [
				[
					'id'        => 'step_personal',
					'title'     => __( 'Persönliche Daten', 'recruiting-playbook' ),
					'position'  => 1,
					'deletable' => false,
					'fields'    => [
						[
							'field_key'    => 'first_name',
							'is_visible'   => true,
							'is_required'  => true,
							'is_removable' => false,
						],
						[
							'field_key'    => 'last_name',
							'is_visible'   => true,
							'is_required'  => true,
							'is_removable' => false,
						],
						[
							'field_key'    => 'email',
							'is_visible'   => true,
							'is_required'  => true,
							'is_removable' => false,
						],
						[
							'field_key'   => 'phone',
							'is_visible'  => true,
							'is_required' => false,
						],
					],
					'system_fields' => [],
				],
				[
					'id'        => 'step_documents',
					'title'     => __( 'Dokumente', 'recruiting-playbook' ),
					'position'  => 2,
					'deletable' => false,
					'fields'    => [
						[
							'field_key'   => 'message',
							'is_visible'  => true,
							'is_required' => false,
						],
					],
					'system_fields' => [
						[
							'field_key' => 'file_upload',
							'type'      => 'file_upload',
							'settings'  => [
								'label'         => __( 'Bewerbungsunterlagen', 'recruiting-playbook' ),
								'help_text'     => __( 'PDF, Word - max. 10 MB pro Datei', 'recruiting-playbook' ),
								'allowed_types' => 'pdf,doc,docx',
								'max_file_size' => 10,
								'max_files'     => 5,
								'is_required'   => true,
							],
						],
					],
				],
				[
					'id'            => 'step_finale',
					'title'         => __( 'Abschluss', 'recruiting-playbook' ),
					'position'      => 999,
					'deletable'     => false,
					'is_finale'     => true,
					'fields'        => [],
					'system_fields' => [
						[
							'field_key' => 'summary',
							'type'      => 'summary',
							'settings'  => [
								'title'            => __( 'Ihre Angaben im Überblick', 'recruiting-playbook' ),
								'layout'           => 'two-column',
								'additional_text'  => __( 'Bitte prüfen Sie Ihre Angaben vor dem Absenden.', 'recruiting-playbook' ),
								'show_only_filled' => false,
							],
						],
						[
							'field_key'    => 'privacy_consent',
							'type'         => 'privacy_consent',
							'is_removable' => false,
							'settings'     => [
								'checkbox_text' => __( 'Ich habe die {datenschutz_link} gelesen und stimme der Verarbeitung meiner Daten zu.', 'recruiting-playbook' ),
								'link_text'     => __( 'Datenschutzerklärung', 'recruiting-playbook' ),
							],
						],
					],
				],
			],
		];

		$config_json = wp_json_encode( $default_config, JSON_UNESCAPED_UNICODE );

		// Published-Version erstellen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'config_type' => 'published',
				'config_data' => $config_json,
				'version'     => 1,
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			[ '%s', '%s', '%d', '%s', '%s' ]
		);

		// Draft-Version erstellen (identisch mit published).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'config_type' => 'draft',
				'config_data' => $config_json,
				'version'     => 1,
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			[ '%s', '%s', '%d', '%s', '%s' ]
		);

		$this->log( 'Seeded default form configuration' );
	}

	/**
	 * Standard E-Mail-Templates definieren
	 *
	 * Templates enthalten nur Text-Inhalt, keine Signatur.
	 * Signaturen werden separat verwaltet und beim Versand angehängt.
	 *
	 * Automatisierbare Templates (ohne Lücken): application-confirmation, rejection-standard, application-withdrawn
	 * Manuelle Templates (mit ___ Lücken): interview-invitation, offer-letter, etc.
	 *
	 * @see docs/technical/email-signature-specification.md
	 * @return array
	 */
	private function getDefaultTemplates(): array {
		return [
			// === AUTOMATISIERBARE TEMPLATES (nur echte Platzhalter) ===
			[
				'slug'      => 'application-confirmation',
				'name'      => __( 'Eingangsbestätigung', 'recruiting-playbook' ),
				'subject'   => __( 'Ihre Bewerbung bei {firma}: {stelle}', 'recruiting-playbook' ),
				'category'  => 'application',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma', 'bewerbung_datum', 'bewerbung_id' ],
				'body_html' => $this->getConfirmationTemplateHtml(),
			],
			[
				'slug'      => 'rejection-standard',
				'name'      => __( 'Absage', 'recruiting-playbook' ),
				'subject'   => __( 'Ihre Bewerbung als {stelle}', 'recruiting-playbook' ),
				'category'  => 'rejection',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma' ],
				'body_html' => $this->getRejectionTemplateHtml(),
			],
			[
				'slug'      => 'application-withdrawn',
				'name'      => __( 'Bewerbung zurückgezogen', 'recruiting-playbook' ),
				'subject'   => __( 'Bestätigung: Bewerbung zurückgezogen', 'recruiting-playbook' ),
				'category'  => 'application',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma' ],
				'body_html' => $this->getWithdrawnTemplateHtml(),
			],
			[
				'slug'      => 'talent-pool-added',
				'name'      => __( 'Aufnahme in Talent-Pool', 'recruiting-playbook' ),
				'subject'   => __( 'Willkommen im Talent-Pool von {firma}', 'recruiting-playbook' ),
				'category'  => 'application',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'firma' ],
				'body_html' => $this->getTalentPoolAddedTemplateHtml(),
			],

			// === MANUELLE TEMPLATES (mit ___ Lücken für Eingaben) ===
			[
				'slug'      => 'interview-invitation',
				'name'      => __( 'Interview-Einladung', 'recruiting-playbook' ),
				'subject'   => __( 'Einladung zum Vorstellungsgespräch: {stelle}', 'recruiting-playbook' ),
				'category'  => 'interview',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma' ],
				'body_html' => $this->getInterviewTemplateHtml(),
			],
			[
				'slug'      => 'interview-reminder',
				'name'      => __( 'Interview-Erinnerung', 'recruiting-playbook' ),
				'subject'   => __( 'Erinnerung: Ihr Vorstellungsgespräch am ___', 'recruiting-playbook' ),
				'category'  => 'interview',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma' ],
				'body_html' => $this->getInterviewReminderTemplateHtml(),
			],
			[
				'slug'      => 'offer-letter',
				'name'      => __( 'Stellenangebot', 'recruiting-playbook' ),
				'subject'   => __( 'Stellenangebot: {stelle} bei {firma}', 'recruiting-playbook' ),
				'category'  => 'offer',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma' ],
				'body_html' => $this->getOfferTemplateHtml(),
			],
			[
				'slug'      => 'contract-sent',
				'name'      => __( 'Vertragsunterlagen', 'recruiting-playbook' ),
				'subject'   => __( 'Ihre Vertragsunterlagen für {stelle}', 'recruiting-playbook' ),
				'category'  => 'offer',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma' ],
				'body_html' => $this->getContractSentTemplateHtml(),
			],
			[
				'slug'      => 'talent-pool-matching-job',
				'name'      => __( 'Passende Stelle gefunden', 'recruiting-playbook' ),
				'subject'   => __( 'Neue Stelle bei {firma}: {stelle}', 'recruiting-playbook' ),
				'category'  => 'application',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'stelle_ort', 'stelle_url', 'firma' ],
				'body_html' => $this->getTalentPoolMatchingJobTemplateHtml(),
			],
		];
	}

	/**
	 * Eingangsbestätigung Template HTML (automatisierbar)
	 *
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getConfirmationTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>vielen Dank für Ihre Bewerbung als <strong>{stelle}</strong> bei {firma}!</p>

<p>Wir haben Ihre Unterlagen erhalten und werden diese sorgfältig prüfen. Sie erhalten von uns Rückmeldung, sobald wir Ihre Bewerbung geprüft haben.</p>

<p><strong>Ihre Bewerbung im Überblick:</strong></p>
<ul>
<li>Position: {stelle}</li>
<li>Eingegangen am: {bewerbung_datum}</li>
<li>Referenznummer: {bewerbung_id}</li>
</ul>

<p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>';
	}

	/**
	 * Absage Template HTML (automatisierbar)
	 *
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getRejectionTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>vielen Dank für Ihr Interesse an der Position <strong>{stelle}</strong> und die Zeit, die Sie in Ihre Bewerbung investiert haben.</p>

<p>Nach sorgfältiger Prüfung müssen wir Ihnen leider mitteilen, dass wir uns für andere Kandidaten entschieden haben, deren Profil besser zu unseren aktuellen Anforderungen passt.</p>

<p>Diese Entscheidung ist keine Bewertung Ihrer Qualifikation. Wir ermutigen Sie, sich bei passenden zukünftigen Stellenangeboten erneut zu bewerben.</p>

<p>Wir wünschen Ihnen für Ihre weitere berufliche Zukunft alles Gute und viel Erfolg.</p>';
	}

	/**
	 * Interview-Einladung Template HTML (manuell)
	 *
	 * Enthält ___ Platzhalter für manuelle Eingaben.
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getInterviewTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>wir freuen uns, Ihnen mitteilen zu können, dass uns Ihre Bewerbung als <strong>{stelle}</strong> überzeugt hat. Gerne möchten wir Sie persönlich kennenlernen.</p>

<p><strong>Terminvorschlag:</strong></p>
<table style="border-collapse: collapse; margin: 16px 0;">
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Datum:</strong></td>
<td style="padding: 8px 0;">___</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Uhrzeit:</strong></td>
<td style="padding: 8px 0;">___</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Ort:</strong></td>
<td style="padding: 8px 0;">___</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Gesprächspartner:</strong></td>
<td style="padding: 8px 0;">___</td>
</tr>
</table>

<p>Bitte bestätigen Sie uns den Termin oder teilen Sie uns mit, falls Sie einen alternativen Termin benötigen.</p>

<p><strong>Bitte bringen Sie mit:</strong></p>
<ul>
<li>Gültigen Personalausweis</li>
<li>Aktuelle Zeugnisse (falls noch nicht eingereicht)</li>
</ul>

<p>Wir freuen uns auf das Gespräch mit Ihnen!</p>';
	}

	/**
	 * Stellenangebot Template HTML (manuell)
	 *
	 * Enthält ___ Platzhalter für manuelle Eingaben.
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getOfferTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>wir freuen uns sehr, Ihnen nach den positiven Gesprächen ein Angebot für die Position <strong>{stelle}</strong> unterbreiten zu können!</p>

<p><strong>Eckdaten des Angebots:</strong></p>
<ul>
<li>Position: {stelle}</li>
<li>Startdatum: ___</li>
<li>Vertragsart: ___</li>
<li>Arbeitszeit: ___</li>
</ul>

<p>Die detaillierten Vertragsunterlagen erhalten Sie in Kürze per Post oder als separaten Anhang.</p>

<p>Bitte teilen Sie uns Ihre Entscheidung bis zum <strong>___</strong> mit.</p>

<p>Für Rückfragen stehen wir Ihnen selbstverständlich gerne zur Verfügung.</p>';
	}

	/**
	 * Bewerbung zurückgezogen Template HTML (automatisierbar)
	 *
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getWithdrawnTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>hiermit bestätigen wir den Eingang Ihrer Mitteilung, dass Sie Ihre Bewerbung als <strong>{stelle}</strong> zurückziehen möchten.</p>

<p>Wir haben Ihre Bewerbung entsprechend aus unserem System entfernt und werden Ihre Unterlagen gemäß den geltenden Datenschutzbestimmungen löschen.</p>

<p>Wir bedauern Ihre Entscheidung, respektieren sie aber selbstverständlich. Sollten Sie in Zukunft Interesse an einer Zusammenarbeit mit {firma} haben, freuen wir uns über eine erneute Bewerbung.</p>

<p>Wir wünschen Ihnen für Ihren weiteren beruflichen Weg alles Gute!</p>';
	}

	/**
	 * Talent-Pool Aufnahme Template HTML (automatisierbar)
	 *
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getTalentPoolAddedTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>vielen Dank für Ihr Interesse an {firma}!</p>

<p>Obwohl wir aktuell keine passende Position für Sie haben, waren wir von Ihrem Profil überzeugt. Mit Ihrer Zustimmung haben wir Sie daher in unseren Talent-Pool aufgenommen.</p>

<p><strong>Was bedeutet das für Sie?</strong></p>
<ul>
<li>Wir werden Sie kontaktieren, sobald eine zu Ihrem Profil passende Stelle frei wird</li>
<li>Sie erhalten exklusive Informationen über neue Karrieremöglichkeiten</li>
<li>Ihre Daten werden gemäß unseren Datenschutzrichtlinien vertraulich behandelt</li>
</ul>

<p>Sie können Ihr Profil jederzeit aktualisieren oder die Aufnahme im Talent-Pool widerrufen.</p>

<p>Wir freuen uns auf eine mögliche zukünftige Zusammenarbeit!</p>';
	}

	/**
	 * Interview-Erinnerung Template HTML (manuell)
	 *
	 * Enthält ___ Platzhalter für manuelle Eingaben.
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getInterviewReminderTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>wir möchten Sie an Ihr bevorstehendes Vorstellungsgespräch für die Position <strong>{stelle}</strong> erinnern.</p>

<p><strong>Termin:</strong></p>
<table style="border-collapse: collapse; margin: 16px 0;">
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Datum:</strong></td>
<td style="padding: 8px 0;">___</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Uhrzeit:</strong></td>
<td style="padding: 8px 0;">___</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Ort:</strong></td>
<td style="padding: 8px 0;">___</td>
</tr>
</table>

<p><strong>Bitte denken Sie daran:</strong></p>
<ul>
<li>Gültigen Personalausweis mitbringen</li>
<li>Pünktlich erscheinen</li>
</ul>

<p>Falls Sie den Termin nicht wahrnehmen können, bitten wir um rechtzeitige Absage.</p>

<p>Wir freuen uns auf das Gespräch mit Ihnen!</p>';
	}

	/**
	 * Vertragsunterlagen Template HTML (manuell)
	 *
	 * Enthält ___ Platzhalter für manuelle Eingaben.
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getContractSentTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>wie besprochen, übersenden wir Ihnen anbei die Vertragsunterlagen für Ihre Anstellung als <strong>{stelle}</strong> bei {firma}.</p>

<p><strong>Die Unterlagen umfassen:</strong></p>
<ul>
<li>Arbeitsvertrag (2 Exemplare)</li>
<li>___</li>
</ul>

<p><strong>Nächste Schritte:</strong></p>
<ol>
<li>Bitte prüfen Sie die Unterlagen sorgfältig</li>
<li>Unterschreiben Sie beide Vertragsexemplare</li>
<li>Senden Sie ein unterschriebenes Exemplar bis zum <strong>___</strong> an uns zurück</li>
</ol>

<p>Sollten Sie Fragen zu den Unterlagen haben, stehen wir Ihnen gerne zur Verfügung.</p>

<p>Wir freuen uns auf die Zusammenarbeit!</p>';
	}

	/**
	 * Talent-Pool: Passende Stelle gefunden Template HTML (automatisierbar)
	 *
	 * Keine Signatur enthalten - wird beim Versand angehängt.
	 *
	 * @return string
	 */
	private function getTalentPoolMatchingJobTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>Sie befinden sich in unserem Talent-Pool, und wir haben eine Stelle gefunden, die zu Ihrem Profil passen könnte!</p>

<p><strong>Die Position:</strong></p>
<ul>
<li><strong>{stelle}</strong></li>
<li>Standort: {stelle_ort}</li>
</ul>

<p>Wir denken, dass diese Stelle aufgrund Ihrer Qualifikationen und Erfahrungen interessant für Sie sein könnte.</p>

<p><strong>Interesse?</strong></p>
<p>Wenn Sie an dieser Position interessiert sind, antworten Sie einfach auf diese E-Mail oder bewerben Sie sich direkt über unsere Karriereseite:</p>
<p><a href="{stelle_url}">{stelle_url}</a></p>

<p>Falls die Stelle nicht das Richtige für Sie ist, bleiben Sie weiterhin in unserem Talent-Pool und wir informieren Sie über zukünftige Möglichkeiten.</p>

<p>Wir würden uns freuen, von Ihnen zu hören!</p>';
	}

	/**
	 * Tabellen existieren prüfen
	 *
	 * @return bool
	 */
	public function tablesExist(): bool {
		global $wpdb;

		$tables = Schema::getTables();

		foreach ( $tables as $table ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table
				)
			);

			if ( $result !== $table ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Migration 2.0.2: phone und message zu Custom Fields machen
	 *
	 * Diese Felder waren vorher System-Felder (nicht editierbar).
	 * Jetzt werden sie zu Custom Fields (editierbar mit Zahnrad-Icon).
	 */
	private function migratePhoneMessageToCustomFields(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_field_definitions';

		// Prüfen ob Tabelle existiert.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
		if ( ! $table_exists ) {
			return;
		}

		// phone und message auf is_system = 0 setzen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET is_system = 0 WHERE field_key IN (%s, %s)",
				'phone',
				'message'
			)
		);

		if ( $updated > 0 ) {
			$this->log( "Migrated phone and message to custom fields ({$updated} rows updated)" );
		}
	}

	/**
	 * Tabellen löschen (für Deinstallation)
	 */
	public function dropTables(): void {
		global $wpdb;

		$tables = Schema::getTables();

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( self::SCHEMA_OPTION );
	}

	/**
	 * Migration loggen
	 *
	 * @param string $message Log message.
	 */
	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[Recruiting Playbook] ' . $message );
		}
	}
}

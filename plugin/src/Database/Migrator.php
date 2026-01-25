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

	private const SCHEMA_VERSION = '1.4.0';
	private const SCHEMA_OPTION  = 'rp_db_version';

	/**
	 * Tabellen erstellen/aktualisieren
	 */
	public function createTables(): void {
		$current_version = get_option( self::SCHEMA_OPTION, '0' );

		// Nur wenn Update nötig.
		if ( version_compare( $current_version, self::SCHEMA_VERSION, '>=' ) ) {
			return;
		}

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

		// Spezielle Migrationen für bestehende Installationen.
		$this->runMigrations( $current_version );

		// Version speichern.
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION );

		// Log erstellen.
		$this->log( 'Database migrated to version ' . self::SCHEMA_VERSION );
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
	 * Migration: Standard E-Mail-Templates einfügen
	 */
	private function seedDefaultEmailTemplates(): void {
		global $wpdb;

		$table = Schema::getTables()['email_templates'];
		$now   = current_time( 'mysql' );

		// Prüfen ob bereits Templates existieren.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $count > 0 ) {
			return;
		}

		$templates = $this->getDefaultTemplates();

		foreach ( $templates as $template ) {
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
		}

		$this->log( 'Seeded ' . count( $templates ) . ' default email templates' );
	}

	/**
	 * Standard E-Mail-Templates definieren
	 *
	 * @return array
	 */
	private function getDefaultTemplates(): array {
		return [
			[
				'slug'      => 'application-confirmation',
				'name'      => __( 'Bewerbungsbestätigung', 'recruiting-playbook' ),
				'subject'   => __( 'Ihre Bewerbung bei {firma}: {stelle}', 'recruiting-playbook' ),
				'category'  => 'application',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma', 'bewerbung_datum', 'bewerbung_id' ],
				'body_html' => $this->getConfirmationTemplateHtml(),
			],
			[
				'slug'      => 'rejection-standard',
				'name'      => __( 'Absage (Standard)', 'recruiting-playbook' ),
				'subject'   => __( 'Ihre Bewerbung als {stelle}', 'recruiting-playbook' ),
				'category'  => 'application',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma' ],
				'body_html' => $this->getRejectionTemplateHtml(),
			],
			[
				'slug'      => 'interview-invitation',
				'name'      => __( 'Interview-Einladung', 'recruiting-playbook' ),
				'subject'   => __( 'Einladung zum Vorstellungsgespräch: {stelle}', 'recruiting-playbook' ),
				'category'  => 'interview',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma', 'termin_datum', 'termin_uhrzeit', 'termin_ort', 'termin_teilnehmer', 'kontakt_email', 'kontakt_telefon' ],
				'body_html' => $this->getInterviewTemplateHtml(),
			],
			[
				'slug'      => 'offer-letter',
				'name'      => __( 'Stellenangebot', 'recruiting-playbook' ),
				'subject'   => __( 'Stellenangebot: {stelle} bei {firma}', 'recruiting-playbook' ),
				'category'  => 'offer',
				'variables' => [ 'anrede_formal', 'vorname', 'nachname', 'stelle', 'firma', 'start_datum', 'vertragsart', 'arbeitszeit', 'antwort_frist' ],
				'body_html' => $this->getOfferTemplateHtml(),
			],
		];
	}

	/**
	 * Bewerbungsbestätigung Template HTML
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

<p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>

<p>Mit freundlichen Grüßen<br>
{absender_name}<br>
{firma}</p>';
	}

	/**
	 * Absage Template HTML
	 *
	 * @return string
	 */
	private function getRejectionTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>vielen Dank für Ihr Interesse an der Position <strong>{stelle}</strong> und die Zeit, die Sie in Ihre Bewerbung investiert haben.</p>

<p>Nach sorgfältiger Prüfung müssen wir Ihnen leider mitteilen, dass wir uns für andere Kandidaten entschieden haben, deren Profil besser zu unseren aktuellen Anforderungen passt.</p>

<p>Diese Entscheidung ist keine Bewertung Ihrer Qualifikation. Wir ermutigen Sie, sich bei passenden zukünftigen Stellenangeboten erneut zu bewerben.</p>

<p>Wir wünschen Ihnen für Ihre weitere berufliche Zukunft alles Gute und viel Erfolg.</p>

<p>Mit freundlichen Grüßen<br>
{absender_name}<br>
{firma}</p>';
	}

	/**
	 * Interview-Einladung Template HTML
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
<td style="padding: 8px 0;">{termin_datum}</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Uhrzeit:</strong></td>
<td style="padding: 8px 0;">{termin_uhrzeit}</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Ort:</strong></td>
<td style="padding: 8px 0;">{termin_ort}</td>
</tr>
<tr>
<td style="padding: 8px 16px 8px 0;"><strong>Gesprächspartner:</strong></td>
<td style="padding: 8px 0;">{termin_teilnehmer}</td>
</tr>
</table>

<p>Bitte bestätigen Sie uns den Termin oder teilen Sie uns mit, falls Sie einen alternativen Termin benötigen.</p>

<p><strong>Bitte bringen Sie mit:</strong></p>
<ul>
<li>Gültigen Personalausweis</li>
<li>Aktuelle Zeugnisse (falls noch nicht eingereicht)</li>
</ul>

<p>Bei Fragen erreichen Sie uns unter {kontakt_telefon} oder per E-Mail an {kontakt_email}.</p>

<p>Wir freuen uns auf das Gespräch mit Ihnen!</p>

<p>Mit freundlichen Grüßen<br>
{absender_name}<br>
{firma}</p>';
	}

	/**
	 * Stellenangebot Template HTML
	 *
	 * @return string
	 */
	private function getOfferTemplateHtml(): string {
		return '<p>{anrede_formal},</p>

<p>wir freuen uns sehr, Ihnen nach den positiven Gesprächen ein Angebot für die Position <strong>{stelle}</strong> unterbreiten zu können!</p>

<p><strong>Eckdaten des Angebots:</strong></p>
<ul>
<li>Position: {stelle}</li>
<li>Startdatum: {start_datum}</li>
<li>Vertragsart: {vertragsart}</li>
<li>Arbeitszeit: {arbeitszeit}</li>
</ul>

<p>Die detaillierten Vertragsunterlagen erhalten Sie in Kürze per Post oder als separaten Anhang.</p>

<p>Bitte teilen Sie uns Ihre Entscheidung bis zum <strong>{antwort_frist}</strong> mit.</p>

<p>Für Rückfragen stehen wir Ihnen selbstverständlich gerne zur Verfügung.</p>

<p>Mit freundlichen Grüßen<br>
{absender_name}<br>
{firma}</p>';
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

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

	private const SCHEMA_VERSION = '1.5.0';
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
		dbDelta( Schema::getSignaturesTableSql() );

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

		// Migration 1.5.0: Signaturen-Tabelle + Default Firmen-Signatur.
		if ( version_compare( $from_version, '1.5.0', '<' ) ) {
			$this->seedDefaultCompanySignature();
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
	 * Migration: Default Firmen-Signatur erstellen
	 */
	private function seedDefaultCompanySignature(): void {
		global $wpdb;

		$table = Schema::getTables()['signatures'];
		$now   = current_time( 'mysql' );

		// Prüfen ob bereits eine Firmen-Signatur existiert (user_id = NULL).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id IS NULL",
				[]
			)
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

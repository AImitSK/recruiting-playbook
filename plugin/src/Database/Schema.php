<?php
/**
 * Datenbank-Schema Definitionen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Datenbank-Schema Definitionen
 */
class Schema {

	/**
	 * Alle Tabellen-Namen
	 *
	 * @return array<string, string>
	 */
	public static function getTables(): array {
		global $wpdb;

		return [
			'candidates'        => $wpdb->prefix . 'rp_candidates',
			'applications'      => $wpdb->prefix . 'rp_applications',
			'documents'         => $wpdb->prefix . 'rp_documents',
			'activity_log'      => $wpdb->prefix . 'rp_activity_log',
			'notes'             => $wpdb->prefix . 'rp_notes',
			'ratings'           => $wpdb->prefix . 'rp_ratings',
			'talent_pool'       => $wpdb->prefix . 'rp_talent_pool',
			'email_templates'   => $wpdb->prefix . 'rp_email_templates',
			'email_log'         => $wpdb->prefix . 'rp_email_log',
			'signatures'        => $wpdb->prefix . 'rp_signatures',
			'job_assignments'   => $wpdb->prefix . 'rp_user_job_assignments',
			'stats_cache'       => $wpdb->prefix . 'rp_stats_cache',
			'field_definitions' => $wpdb->prefix . 'rp_field_definitions',
			'form_templates'    => $wpdb->prefix . 'rp_form_templates',
		];
	}

	/**
	 * SQL für rp_candidates
	 *
	 * @return string
	 */
	public static function getCandidatesTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['candidates'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(255) NOT NULL,
			salutation varchar(20) DEFAULT '',
			first_name varchar(100) DEFAULT '',
			last_name varchar(100) DEFAULT '',
			phone varchar(50) DEFAULT '',
			address_street varchar(255) DEFAULT '',
			address_city varchar(100) DEFAULT '',
			address_zip varchar(20) DEFAULT '',
			address_country varchar(100) DEFAULT 'Deutschland',
			source varchar(50) DEFAULT 'form',
			notes longtext DEFAULT '',
			gdpr_consent tinyint(1) DEFAULT 0,
			gdpr_consent_date datetime DEFAULT NULL,
			gdpr_consent_version varchar(20) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY email (email),
			KEY created_at (created_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_applications
	 *
	 * @return string
	 */
	public static function getApplicationsTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['applications'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			candidate_id bigint(20) unsigned NOT NULL,
			job_id bigint(20) unsigned NOT NULL,
			status varchar(50) DEFAULT 'new',
			kanban_position int(11) DEFAULT 0,
			cover_letter longtext DEFAULT '',
			custom_fields longtext DEFAULT '',
			source varchar(50) DEFAULT 'website',
			source_url varchar(500) DEFAULT '',
			consent_privacy tinyint(1) DEFAULT 0,
			consent_privacy_at datetime DEFAULT NULL,
			consent_ip varchar(45) DEFAULT '',
			ip_address varchar(45) DEFAULT '',
			user_agent varchar(500) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY candidate_id (candidate_id),
			KEY job_id (job_id),
			KEY status (status),
			KEY kanban_sort (status, kanban_position),
			KEY created_at (created_at),
			KEY deleted_at (deleted_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_documents
	 *
	 * @return string
	 */
	public static function getDocumentsTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['documents'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			application_id bigint(20) unsigned NOT NULL,
			candidate_id bigint(20) unsigned NOT NULL,
			file_name varchar(255) NOT NULL,
			original_name varchar(255) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_type varchar(100) NOT NULL,
			file_size bigint(20) unsigned DEFAULT 0,
			file_hash varchar(64) DEFAULT '',
			document_type varchar(50) DEFAULT 'other',
			download_count int(11) DEFAULT 0,
			is_deleted tinyint(1) DEFAULT 0,
			deleted_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY application_id (application_id),
			KEY candidate_id (candidate_id),
			KEY document_type (document_type),
			KEY is_deleted (is_deleted)
		) {$charset};";
	}

	/**
	 * SQL für rp_activity_log
	 *
	 * @return string
	 */
	public static function getActivityLogTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['activity_log'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			object_type varchar(50) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			action varchar(100) NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			user_name varchar(100) DEFAULT NULL,
			old_value longtext DEFAULT NULL,
			new_value longtext DEFAULT NULL,
			message longtext DEFAULT NULL,
			meta longtext DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(500) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY object_type_id (object_type, object_id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_notes
	 *
	 * @return string
	 */
	public static function getNotesTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['notes'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			application_id bigint(20) unsigned DEFAULT NULL,
			candidate_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			content longtext NOT NULL,
			is_private tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY application_id (application_id),
			KEY candidate_id (candidate_id),
			KEY user_id (user_id),
			KEY created_at (created_at),
			KEY deleted_at (deleted_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_ratings
	 *
	 * @return string
	 */
	public static function getRatingsTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['ratings'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			application_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			rating tinyint(1) unsigned NOT NULL,
			category varchar(50) DEFAULT 'overall',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY user_application_category (user_id, application_id, category),
			KEY application_id (application_id),
			KEY rating (rating),
			KEY category (category)
		) {$charset};";
	}

	/**
	 * SQL für rp_talent_pool
	 *
	 * @return string
	 */
	public static function getTalentPoolTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['talent_pool'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			candidate_id bigint(20) unsigned NOT NULL,
			added_by bigint(20) unsigned NOT NULL,
			reason longtext DEFAULT NULL,
			tags varchar(255) DEFAULT NULL,
			expires_at datetime NOT NULL,
			reminder_sent tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY candidate_id (candidate_id),
			KEY added_by (added_by),
			KEY expires_at (expires_at),
			KEY tags (tags),
			KEY deleted_at (deleted_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_email_templates
	 *
	 * @return string
	 */
	public static function getEmailTemplatesTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['email_templates'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			slug varchar(100) NOT NULL,
			name varchar(255) NOT NULL,
			subject varchar(255) NOT NULL,
			body_html longtext NOT NULL,
			body_text longtext DEFAULT NULL,
			category varchar(50) DEFAULT 'custom',
			is_active tinyint(1) DEFAULT 1,
			is_default tinyint(1) DEFAULT 0,
			is_system tinyint(1) DEFAULT 0,
			variables longtext DEFAULT NULL,
			settings longtext DEFAULT NULL,
			created_by bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY category (category),
			KEY is_active (is_active),
			KEY is_default (is_default),
			KEY is_system (is_system),
			KEY deleted_at (deleted_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_email_log
	 *
	 * @return string
	 */
	public static function getEmailLogTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['email_log'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			application_id bigint(20) unsigned DEFAULT NULL,
			candidate_id bigint(20) unsigned DEFAULT NULL,
			template_id bigint(20) unsigned DEFAULT NULL,
			recipient_email varchar(255) NOT NULL,
			recipient_name varchar(255) DEFAULT NULL,
			sender_email varchar(255) NOT NULL,
			sender_name varchar(255) DEFAULT NULL,
			subject varchar(255) NOT NULL,
			body_html longtext NOT NULL,
			body_text longtext DEFAULT NULL,
			status varchar(20) DEFAULT 'pending',
			error_message text DEFAULT NULL,
			opened_at datetime DEFAULT NULL,
			clicked_at datetime DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			sent_by bigint(20) unsigned DEFAULT NULL,
			scheduled_at datetime DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY application_id (application_id),
			KEY candidate_id (candidate_id),
			KEY template_id (template_id),
			KEY recipient_email (recipient_email),
			KEY status (status),
			KEY sent_at (sent_at),
			KEY scheduled_at (scheduled_at),
			KEY created_at (created_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_signatures
	 *
	 * @return string
	 */
	public static function getSignaturesTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['signatures'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT NULL,
			name varchar(100) NOT NULL,
			greeting varchar(255) DEFAULT NULL,
			content text NOT NULL,
			is_default tinyint(1) DEFAULT 0,
			include_company tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY is_default (user_id, is_default)
		) {$charset};";
	}

	/**
	 * SQL für rp_user_job_assignments
	 *
	 * Speichert Zuweisungen von Benutzern zu Stellen.
	 * Ermöglicht Recruitern nur ihre zugewiesenen Bewerbungen zu sehen.
	 *
	 * @return string
	 */
	public static function getJobAssignmentsTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['job_assignments'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			job_id bigint(20) unsigned NOT NULL,
			assigned_by bigint(20) unsigned NOT NULL,
			assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_job (user_id, job_id),
			KEY user_id (user_id),
			KEY job_id (job_id),
			KEY assigned_by (assigned_by)
		) {$charset};";
	}

	/**
	 * SQL für rp_stats_cache
	 *
	 * Caching-Tabelle für berechnete Statistiken.
	 *
	 * @return string
	 */
	public static function getStatsCacheTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['stats_cache'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cache_key varchar(100) NOT NULL,
			cache_value longtext NOT NULL,
			expires_at datetime NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY cache_key (cache_key),
			KEY expires_at (expires_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_field_definitions
	 *
	 * Speichert benutzerdefinierte Feld-Definitionen für Bewerbungsformulare.
	 * Pro-Feature: Custom Fields Builder.
	 *
	 * @return string
	 */
	public static function getFieldDefinitionsTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['field_definitions'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			template_id bigint(20) unsigned DEFAULT NULL,
			job_id bigint(20) unsigned DEFAULT NULL,
			field_key varchar(100) NOT NULL,
			field_type varchar(50) NOT NULL,
			label varchar(255) NOT NULL,
			placeholder varchar(255) DEFAULT NULL,
			description text DEFAULT NULL,
			options longtext DEFAULT NULL,
			validation longtext DEFAULT NULL,
			conditional longtext DEFAULT NULL,
			settings longtext DEFAULT NULL,
			position int(11) NOT NULL DEFAULT 0,
			is_required tinyint(1) DEFAULT 0,
			is_system tinyint(1) DEFAULT 0,
			is_active tinyint(1) DEFAULT 1,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY field_key_template (field_key, template_id),
			UNIQUE KEY field_key_job (field_key, job_id),
			KEY template_id (template_id),
			KEY job_id (job_id),
			KEY field_type (field_type),
			KEY position (position),
			KEY is_active (is_active),
			KEY is_system (is_system),
			KEY deleted_at (deleted_at)
		) {$charset};";
	}

	/**
	 * SQL für rp_form_templates
	 *
	 * Speichert Formular-Templates für wiederverwendbare Feld-Konfigurationen.
	 * Pro-Feature: Custom Fields Builder.
	 *
	 * @return string
	 */
	public static function getFormTemplatesTableSql(): string {
		global $wpdb;
		$table   = self::getTables()['form_templates'];
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text DEFAULT NULL,
			is_default tinyint(1) DEFAULT 0,
			settings longtext DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY is_default (is_default),
			KEY created_by (created_by),
			KEY deleted_at (deleted_at)
		) {$charset};";
	}
}

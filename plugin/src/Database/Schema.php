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
			'candidates'   => $wpdb->prefix . 'rp_candidates',
			'applications' => $wpdb->prefix . 'rp_applications',
			'documents'    => $wpdb->prefix . 'rp_documents',
			'activity_log' => $wpdb->prefix . 'rp_activity_log',
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
			PRIMARY KEY (id),
			KEY candidate_id (candidate_id),
			KEY job_id (job_id),
			KEY status (status),
			KEY kanban_sort (status, kanban_position),
			KEY created_at (created_at)
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
}

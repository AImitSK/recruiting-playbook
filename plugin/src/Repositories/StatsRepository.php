<?php
/**
 * Stats Repository - Optimierte Statistik-Queries
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für Statistik-Operationen
 */
class StatsRepository {

	/**
	 * Tabellen-Namen
	 *
	 * @var array
	 */
	private array $tables;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->tables = Schema::getTables();
	}

	/**
	 * Bewerbungen nach Status zählen
	 *
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array<string, int>
	 */
	public function countApplicationsByStatus( array $date_range, ?int $job_id = null ): array {
		global $wpdb;

		$table = $this->tables['applications'];

		$where = 'deleted_at IS NULL';
		$params = [];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where .= ' AND created_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		if ( $job_id ) {
			$where .= ' AND job_id = %d';
			$params[] = $job_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$params
				? $wpdb->prepare(
					"SELECT status, COUNT(*) as count FROM {$table} WHERE {$where} GROUP BY status",
					...$params
				)
				: "SELECT status, COUNT(*) as count FROM {$table} WHERE {$where} GROUP BY status",
			ARRAY_A
		);

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = (int) $row['count'];
		}

		return $counts;
	}

	/**
	 * Gesamtzahl Bewerbungen
	 *
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return int
	 */
	public function countApplications( array $date_range, ?int $job_id = null ): int {
		global $wpdb;

		$table = $this->tables['applications'];

		$where = 'deleted_at IS NULL';
		$params = [];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where .= ' AND created_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		if ( $job_id ) {
			$where .= ' AND job_id = %d';
			$params[] = $job_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$params
				? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params )
				: "SELECT COUNT(*) FROM {$table} WHERE {$where}"
		);
	}

	/**
	 * Top-Stellen nach Bewerbungen
	 *
	 * @param int $limit Anzahl Ergebnisse.
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @return array
	 */
	public function getTopJobsByApplications( int $limit, array $date_range ): array {
		global $wpdb;

		$apps_table = $this->tables['applications'];
		$posts_table = $wpdb->posts;

		$where_apps = 'a.deleted_at IS NULL';
		$params = [];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where_apps .= ' AND a.created_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					p.ID as id,
					p.post_title as title,
					COUNT(a.id) as applications
				FROM {$posts_table} p
				LEFT JOIN {$apps_table} a ON p.ID = a.job_id AND {$where_apps}
				WHERE p.post_type = 'job_listing'
					AND p.post_status = 'publish'
				GROUP BY p.ID
				ORDER BY applications DESC
				LIMIT %d",
				...$params
			),
			ARRAY_A
		);
	}

	/**
	 * Eingestellte Bewerbungen (für Time-to-Hire)
	 *
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array
	 */
	public function getHiredApplications( array $date_range, ?int $job_id = null ): array {
		global $wpdb;

		$table = $this->tables['applications'];

		$where = "status = 'hired' AND deleted_at IS NULL";
		$params = [];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where .= ' AND updated_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		if ( $job_id ) {
			$where .= ' AND job_id = %d';
			$params[] = $job_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$params
				? $wpdb->prepare(
					"SELECT
						id,
						job_id,
						created_at,
						updated_at as hired_at,
						DATEDIFF(updated_at, created_at) as days_to_hire
					FROM {$table}
					WHERE {$where}
					ORDER BY updated_at DESC",
					...$params
				)
				: "SELECT
					id,
					job_id,
					created_at,
					updated_at as hired_at,
					DATEDIFF(updated_at, created_at) as days_to_hire
				FROM {$table}
				WHERE {$where}
				ORDER BY updated_at DESC",
			ARRAY_A
		);
	}

	/**
	 * Job-Views zählen (aus Activity Log)
	 *
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return int
	 */
	public function countJobViews( array $date_range, ?int $job_id = null ): int {
		global $wpdb;

		$table = $this->tables['activity_log'];

		$where = "action = 'job_viewed' AND object_type = 'job'";
		$params = [];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where .= ' AND created_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		if ( $job_id ) {
			$where .= ' AND object_id = %d';
			$params[] = $job_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$params
				? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params )
				: "SELECT COUNT(*) FROM {$table} WHERE {$where}"
		);
	}

	/**
	 * Events zählen (aus Activity Log)
	 *
	 * @param string $action Event-Typ.
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @param int|null $object_id Optional: Filter nach Objekt-ID.
	 * @return int
	 */
	public function countEvents( string $action, array $date_range, ?int $object_id = null ): int {
		global $wpdb;

		$table = $this->tables['activity_log'];

		$where = 'action = %s';
		$params = [ $action ];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where .= ' AND created_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		if ( $object_id ) {
			$where .= ' AND object_id = %d';
			$params[] = $object_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params )
		);
	}

	/**
	 * Bewerbungen nach Zeitraum gruppiert
	 *
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @param string $group_by Gruppierung: 'day', 'week', 'month'.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array
	 */
	public function getApplicationsTimeline( array $date_range, string $group_by = 'day', ?int $job_id = null ): array {
		global $wpdb;

		$table = $this->tables['applications'];

		$date_format = match ( $group_by ) {
			'week'  => '%Y-%u',
			'month' => '%Y-%m',
			default => '%Y-%m-%d',
		};

		$where = 'deleted_at IS NULL';
		$params = [];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where .= ' AND created_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		if ( $job_id ) {
			$where .= ' AND job_id = %d';
			$params[] = $job_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$params
				? $wpdb->prepare(
					"SELECT
						DATE_FORMAT(created_at, '{$date_format}') as date,
						COUNT(*) as total,
						SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
						SUM(CASE WHEN status = 'hired' THEN 1 ELSE 0 END) as hired,
						SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
					FROM {$table}
					WHERE {$where}
					GROUP BY DATE_FORMAT(created_at, '{$date_format}')
					ORDER BY date ASC",
					...$params
				)
				: "SELECT
					DATE_FORMAT(created_at, '{$date_format}') as date,
					COUNT(*) as total,
					SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
					SUM(CASE WHEN status = 'hired' THEN 1 ELSE 0 END) as hired,
					SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
				FROM {$table}
				WHERE {$where}
				GROUP BY DATE_FORMAT(created_at, '{$date_format}')
				ORDER BY date ASC",
			ARRAY_A
		);
	}

	/**
	 * Statistiken pro Stelle
	 *
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @param string $sort_by Sortierung: 'applications', 'title', 'created'.
	 * @param string $sort_order Reihenfolge: 'asc', 'desc'.
	 * @param int $limit Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	public function getJobStats(
		array $date_range,
		string $sort_by = 'applications',
		string $sort_order = 'desc',
		int $limit = 20,
		int $offset = 0
	): array {
		global $wpdb;

		$apps_table = $this->tables['applications'];
		$ratings_table = $this->tables['ratings'];
		$posts_table = $wpdb->posts;

		$where_apps = 'a.deleted_at IS NULL';
		$params = [];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where_apps .= ' AND a.created_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		// Sortierung validieren.
		$valid_sorts = [ 'applications', 'title', 'created', 'hired', 'avg_rating' ];
		$sort_by = in_array( $sort_by, $valid_sorts, true ) ? $sort_by : 'applications';
		$sort_order = strtoupper( $sort_order ) === 'ASC' ? 'ASC' : 'DESC';

		$order_by = match ( $sort_by ) {
			'title'      => 'p.post_title',
			'created'    => 'p.post_date',
			'hired'      => 'hired_count',
			'avg_rating' => 'avg_rating',
			default      => 'applications_total',
		};

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$jobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					p.ID as id,
					p.post_title as title,
					p.post_status as status,
					p.post_date as created_at,
					COUNT(DISTINCT a.id) as applications_total,
					SUM(CASE WHEN a.status = 'new' THEN 1 ELSE 0 END) as new_count,
					SUM(CASE WHEN a.status IN ('screening', 'interview', 'offer') THEN 1 ELSE 0 END) as in_progress,
					SUM(CASE WHEN a.status = 'hired' THEN 1 ELSE 0 END) as hired_count,
					SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
					AVG(r.rating) as avg_rating
				FROM {$posts_table} p
				LEFT JOIN {$apps_table} a ON p.ID = a.job_id AND {$where_apps}
				LEFT JOIN {$ratings_table} r ON a.id = r.application_id AND r.category = 'overall'
				WHERE p.post_type = 'job_listing'
				GROUP BY p.ID
				ORDER BY {$order_by} {$sort_order}
				LIMIT %d OFFSET %d",
				...$params
			),
			ARRAY_A
		);

		// Typen konvertieren.
		return array_map(
			function ( $job ) {
				return [
					'id'                 => (int) $job['id'],
					'title'              => $job['title'],
					'status'             => $job['status'],
					'created_at'         => $job['created_at'],
					'stats'              => [
						'applications_total' => (int) $job['applications_total'],
						'new'                => (int) $job['new_count'],
						'in_progress'        => (int) $job['in_progress'],
						'hired'              => (int) $job['hired_count'],
						'rejected'           => (int) $job['rejected_count'],
						'avg_rating'         => $job['avg_rating'] ? round( (float) $job['avg_rating'], 1 ) : null,
					],
				];
			},
			$jobs
		);
	}

	/**
	 * Gesamtzahl Jobs
	 *
	 * @return int
	 */
	public function countJobs(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'job_listing'"
		);
	}

	/**
	 * Jobs nach Status zählen
	 *
	 * @return array<string, int>
	 */
	public function countJobsByStatus(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			"SELECT post_status as status, COUNT(*) as count
			FROM {$wpdb->posts}
			WHERE post_type = 'job_listing'
			GROUP BY post_status",
			ARRAY_A
		);

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = (int) $row['count'];
		}

		return $counts;
	}

	/**
	 * Bewerbungen nach Quelle
	 *
	 * @param array $date_range Array mit 'from' und 'to' Datum.
	 * @return array
	 */
	public function getApplicationsBySource( array $date_range ): array {
		global $wpdb;

		$table = $this->tables['applications'];

		$where = 'deleted_at IS NULL';
		$params = [];

		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) ) {
			$where .= ' AND created_at BETWEEN %s AND %s';
			$params[] = $date_range['from'];
			$params[] = $date_range['to'];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$params
				? $wpdb->prepare(
					"SELECT
						COALESCE(source, 'direct') as source,
						COUNT(*) as count
					FROM {$table}
					WHERE {$where}
					GROUP BY source
					ORDER BY count DESC",
					...$params
				)
				: "SELECT
					COALESCE(source, 'direct') as source,
					COUNT(*) as count
				FROM {$table}
				WHERE {$where}
				GROUP BY source
				ORDER BY count DESC",
			ARRAY_A
		);

		$by_source = [];
		foreach ( $results as $row ) {
			$by_source[ $row['source'] ] = (int) $row['count'];
		}

		return $by_source;
	}

	/**
	 * Status-Übergänge für Time-to-Hire Berechnung
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	public function getStatusTransitions( int $application_id ): array {
		global $wpdb;

		$table = $this->tables['activity_log'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					action,
					old_value,
					new_value,
					created_at
				FROM {$table}
				WHERE object_type = 'application'
					AND object_id = %d
					AND action = 'status_changed'
				ORDER BY created_at ASC",
				$application_id
			),
			ARRAY_A
		);
	}

	/**
	 * Bewerbungen für Export laden
	 *
	 * @param array $args Filter-Argumente.
	 * @param int $limit Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	public function getApplicationsForExport( array $args, int $limit, int $offset ): array {
		global $wpdb;

		$apps_table = $this->tables['applications'];
		$candidates_table = $this->tables['candidates'];
		$posts_table = $wpdb->posts;

		$where = 'a.deleted_at IS NULL';
		$params = [];

		if ( ! empty( $args['date_from'] ) ) {
			$where .= ' AND a.created_at >= %s';
			$params[] = $args['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where .= ' AND a.created_at <= %s';
			$params[] = $args['date_to'] . ' 23:59:59';
		}

		if ( ! empty( $args['status'] ) && is_array( $args['status'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['status'] ), '%s' ) );
			$where .= " AND a.status IN ({$placeholders})";
			$params = array_merge( $params, $args['status'] );
		}

		if ( ! empty( $args['job_id'] ) ) {
			$where .= ' AND a.job_id = %d';
			$params[] = $args['job_id'];
		}

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					a.id,
					CONCAT(c.first_name, ' ', c.last_name) as candidate_name,
					c.email,
					c.phone,
					a.job_id,
					p.post_title as job_title,
					a.status,
					a.source,
					a.created_at,
					a.updated_at,
					CASE WHEN a.status = 'hired' THEN a.updated_at ELSE NULL END as hired_at,
					DATEDIFF(NOW(), a.created_at) as time_in_process
				FROM {$apps_table} a
				JOIN {$candidates_table} c ON a.candidate_id = c.id
				LEFT JOIN {$posts_table} p ON a.job_id = p.ID
				WHERE {$where}
				ORDER BY a.created_at DESC
				LIMIT %d OFFSET %d",
				...$params
			),
			ARRAY_A
		);
	}

	/**
	 * Gesamtzahl für Export (für Pagination)
	 *
	 * @param array $args Filter-Argumente.
	 * @return int
	 */
	public function countApplicationsForExport( array $args ): int {
		global $wpdb;

		$apps_table = $this->tables['applications'];

		$where = 'deleted_at IS NULL';
		$params = [];

		if ( ! empty( $args['date_from'] ) ) {
			$where .= ' AND created_at >= %s';
			$params[] = $args['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where .= ' AND created_at <= %s';
			$params[] = $args['date_to'] . ' 23:59:59';
		}

		if ( ! empty( $args['status'] ) && is_array( $args['status'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['status'] ), '%s' ) );
			$where .= " AND status IN ({$placeholders})";
			$params = array_merge( $params, $args['status'] );
		}

		if ( ! empty( $args['job_id'] ) ) {
			$where .= ' AND job_id = %d';
			$params[] = $args['job_id'];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$params
				? $wpdb->prepare( "SELECT COUNT(*) FROM {$apps_table} WHERE {$where}", ...$params )
				: "SELECT COUNT(*) FROM {$apps_table} WHERE {$where}"
		);
	}
}

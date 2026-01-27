<?php
/**
 * Applications List Admin Page
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Constants\ApplicationStatus;

/**
 * Applications Page Klasse
 */
class ApplicationsPage {

	/**
	 * Render the applications page
	 */
	public function render(): void {
		$data = $this->get_page_data();

		// Localize data for React component.
		wp_localize_script(
			'rp-admin',
			'rpApplicationsData',
			$data
		);

		?>
		<div class="wrap rp-admin">
			<div id="rp-applications-root"></div>
		</div>
		<?php
	}

	/**
	 * Get all page data
	 *
	 * @return array<string, mixed>
	 */
	private function get_page_data(): array {
		$per_page     = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$status       = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$job_id       = isset( $_GET['job_id'] ) ? absint( $_GET['job_id'] ) : 0;
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		return [
			'applications'  => $this->get_applications( $current_page, $per_page, $status, $job_id, $search ),
			'statusCounts'  => $this->get_status_counts(),
			'jobs'          => $this->get_jobs(),
			'total'         => $this->get_total_count( $status, $job_id, $search ),
			'currentPage'   => $current_page,
			'perPage'       => $per_page,
			'hasApiAccess'  => $this->has_api_access(),
			'logoUrl'       => RP_PLUGIN_URL . 'assets/images/rp-logo.png',
			'adminUrl'      => admin_url(),
			'nonce'         => wp_create_nonce( 'rp_set_status_' ),
		];
	}

	/**
	 * Check if user has API access (Pro feature)
	 *
	 * @return bool
	 */
	private function has_api_access(): bool {
		return function_exists( 'rp_can' ) && rp_can( 'api_access' );
	}

	/**
	 * Get applications with filters
	 *
	 * @param int    $page    Current page.
	 * @param int    $per_page Items per page.
	 * @param string $status  Status filter.
	 * @param int    $job_id  Job filter.
	 * @param string $search  Search term.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_applications( int $page, int $per_page, string $status, int $job_id, string $search ): array {
		global $wpdb;

		$applications_table = $wpdb->prefix . 'rp_applications';
		$candidates_table   = $wpdb->prefix . 'rp_candidates';
		$documents_table    = $wpdb->prefix . 'rp_documents';

		// Check if tables exist.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $applications_table )
		) === $applications_table;

		if ( ! $table_exists ) {
			return [];
		}

		$offset = ( $page - 1 ) * $per_page;

		// Simple query without search (search requires candidate join).
		if ( empty( $search ) ) {
			// Build simple WHERE clause.
			$where_parts = [];

			if ( ! empty( $status ) && array_key_exists( $status, ApplicationStatus::getAll() ) ) {
				$where_parts[] = $wpdb->prepare( 'status = %s', $status );
			} else {
				$where_parts[] = "status != 'deleted'";
			}

			if ( $job_id > 0 ) {
				$where_parts[] = $wpdb->prepare( 'job_id = %d', $job_id );
			}

			$where_sql = implode( ' AND ', $where_parts );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, job_id, candidate_id, status, created_at
					FROM {$applications_table}
					WHERE {$where_sql}
					ORDER BY created_at DESC
					LIMIT %d OFFSET %d",
					$per_page,
					$offset
				),
				ARRAY_A
			);
		} else {
			// Query with search - needs candidate join.
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';

			$where_parts = [];
			if ( ! empty( $status ) && array_key_exists( $status, ApplicationStatus::getAll() ) ) {
				$where_parts[] = $wpdb->prepare( 'a.status = %s', $status );
			} else {
				$where_parts[] = "a.status != 'deleted'";
			}

			if ( $job_id > 0 ) {
				$where_parts[] = $wpdb->prepare( 'a.job_id = %d', $job_id );
			}

			$where_parts[] = $wpdb->prepare(
				'(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)',
				$search_like,
				$search_like,
				$search_like
			);

			$where_sql = implode( ' AND ', $where_parts );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT a.id, a.job_id, a.candidate_id, a.status, a.created_at
					FROM {$applications_table} a
					LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id
					WHERE {$where_sql}
					ORDER BY a.created_at DESC
					LIMIT %d OFFSET %d",
					$per_page,
					$offset
				),
				ARRAY_A
			);
		}

		if ( empty( $results ) ) {
			return [];
		}

		// Fetch candidate data separately.
		$candidate_ids = array_filter( array_unique( array_column( $results, 'candidate_id' ) ) );
		$candidates    = [];

		if ( ! empty( $candidate_ids ) ) {
			$ids_placeholder = implode( ',', array_fill( 0, count( $candidate_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$candidate_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, first_name, last_name, email, phone FROM {$candidates_table} WHERE id IN ({$ids_placeholder})",
					...$candidate_ids
				),
				ARRAY_A
			);

			foreach ( $candidate_rows as $row ) {
				$candidates[ $row['id'] ] = $row;
			}
		}

		// Fetch document counts.
		$app_ids          = array_column( $results, 'id' );
		$document_counts  = [];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$docs_table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $documents_table )
		) === $documents_table;

		if ( $docs_table_exists && ! empty( $app_ids ) ) {
			$ids_placeholder = implode( ',', array_fill( 0, count( $app_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$doc_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT application_id, COUNT(*) as count FROM {$documents_table} WHERE application_id IN ({$ids_placeholder}) GROUP BY application_id",
					...$app_ids
				),
				ARRAY_A
			);

			foreach ( $doc_rows as $row ) {
				$document_counts[ $row['application_id'] ] = (int) $row['count'];
			}
		}

		// Combine all data.
		$applications = [];
		foreach ( $results as $row ) {
			// Add job title.
			$job              = get_post( $row['job_id'] );
			$row['job_title'] = $job ? $job->post_title : '';

			// Add candidate data.
			$candidate_id = $row['candidate_id'] ?? null;
			if ( $candidate_id && isset( $candidates[ $candidate_id ] ) ) {
				$row['first_name'] = $candidates[ $candidate_id ]['first_name'];
				$row['last_name']  = $candidates[ $candidate_id ]['last_name'];
				$row['email']      = $candidates[ $candidate_id ]['email'];
				$row['phone']      = $candidates[ $candidate_id ]['phone'];
			} else {
				$row['first_name'] = '';
				$row['last_name']  = '';
				$row['email']      = __( 'Unbekannt', 'recruiting-playbook' );
				$row['phone']      = '';
			}

			// Add document count.
			$row['documents_count'] = $document_counts[ $row['id'] ] ?? 0;

			$applications[] = $row;
		}

		return $applications;
	}

	/**
	 * Get total count with filters
	 *
	 * @param string $status Status filter.
	 * @param int    $job_id Job filter.
	 * @param string $search Search term.
	 * @return int
	 */
	private function get_total_count( string $status, int $job_id, string $search ): int {
		global $wpdb;

		$applications_table = $wpdb->prefix . 'rp_applications';
		$candidates_table   = $wpdb->prefix . 'rp_candidates';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $applications_table )
		) === $applications_table;

		if ( ! $table_exists ) {
			return 0;
		}

		// Simple query without search.
		if ( empty( $search ) ) {
			$where_parts = [];

			if ( ! empty( $status ) && array_key_exists( $status, ApplicationStatus::getAll() ) ) {
				$where_parts[] = $wpdb->prepare( 'status = %s', $status );
			} else {
				$where_parts[] = "status != 'deleted'";
			}

			if ( $job_id > 0 ) {
				$where_parts[] = $wpdb->prepare( 'job_id = %d', $job_id );
			}

			$where_sql = implode( ' AND ', $where_parts );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$applications_table} WHERE {$where_sql}"
			);
		}

		// Query with search - needs candidate join.
		$search_like = '%' . $wpdb->esc_like( $search ) . '%';

		$where_parts = [];
		if ( ! empty( $status ) && array_key_exists( $status, ApplicationStatus::getAll() ) ) {
			$where_parts[] = $wpdb->prepare( 'a.status = %s', $status );
		} else {
			$where_parts[] = "a.status != 'deleted'";
		}

		if ( $job_id > 0 ) {
			$where_parts[] = $wpdb->prepare( 'a.job_id = %d', $job_id );
		}

		$where_parts[] = $wpdb->prepare(
			'(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)',
			$search_like,
			$search_like,
			$search_like
		);

		$where_sql = implode( ' AND ', $where_parts );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			"SELECT COUNT(a.id) FROM {$applications_table} a LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id WHERE {$where_sql}"
		);
	}

	/**
	 * Get status counts
	 *
	 * @return array<string, int>
	 */
	private function get_status_counts(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'rp_applications';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		) === $table;

		if ( ! $table_exists ) {
			return [];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
			ARRAY_A
		);

		$counts = [];
		foreach ( $results as $row ) {
			$counts[ $row['status'] ] = (int) $row['count'];
		}

		return $counts;
	}

	/**
	 * Get available jobs
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_jobs(): array {
		$posts = get_posts(
			[
				'post_type'      => 'job_listing',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$jobs = [];
		foreach ( $posts as $post ) {
			$jobs[] = [
				'id'    => $post->ID,
				'title' => $post->post_title,
			];
		}

		return $jobs;
	}
}

<?php
/**
 * Bewerbungen-Listenansicht (WP_List_Table)
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);


namespace RecruitingPlaybook\Admin\Pages;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Constants\ApplicationStatus;
use RecruitingPlaybook\Services\CapabilityService;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Bewerbungen-Listenansicht
 */
class ApplicationList extends \WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Application', 'recruiting-playbook' ),
				'plural'   => __( 'Applications', 'recruiting-playbook' ),
				'ajax'     => false,
			]
		);
	}

	/**
	 * Spalten definieren
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return [
			'cb'         => '<input type="checkbox" />',
			'applicant'  => __( 'Applicant', 'recruiting-playbook' ),
			'job'        => __( 'Job', 'recruiting-playbook' ),
			'status'     => __( 'Status', 'recruiting-playbook' ),
			'documents'  => __( 'Documents', 'recruiting-playbook' ),
			'created_at' => __( 'Received', 'recruiting-playbook' ),
			'actions'    => __( 'Actions', 'recruiting-playbook' ),
		];
	}

	/**
	 * Sortierbare Spalten
	 *
	 * @return array<string, array>
	 */
	public function get_sortable_columns(): array {
		return [
			'applicant'  => [ 'last_name', false ],
			'job'        => [ 'job_id', false ],
			'status'     => [ 'status', false ],
			'created_at' => [ 'created_at', true ],
		];
	}

	/**
	 * Bulk-Actions definieren
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions(): array {
		$actions = [
			'bulk_screening' => __( 'Status: Screening', 'recruiting-playbook' ),
			'bulk_rejected'  => __( 'Status: Rejected', 'recruiting-playbook' ),
			'bulk_delete'    => __( 'Delete', 'recruiting-playbook' ),
		];

		// Pro-Feature: Bulk-E-Mail.
		if ( function_exists( 'rp_can' ) && rp_can( 'email_templates' ) ) {
			$actions['bulk_email'] = __( '✉️ Send Email', 'recruiting-playbook' );
		}

		return $actions;
	}

	/**
	 * Daten vorbereiten
	 */
	public function prepare_items(): void {
		global $wpdb;

		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'rp_applications_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$where_data = $this->build_where_clause();
		$orderby    = $this->get_orderby();
		$order      = $this->get_order();

		$applications_table = $wpdb->prefix . 'rp_applications';
		$candidates_table   = $wpdb->prefix . 'rp_candidates';

		// Count Query mit sicheren Prepared Statements
		if ( empty( $where_data['values'] ) ) {
			// Keine Filter - einfache Query
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total_items = (int) $wpdb->get_var(
				"SELECT COUNT(a.id)
				 FROM {$applications_table} a
				 LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id"
			);
		} else {
			// Mit Filtern - Prepared Statement
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total_items = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(a.id)
					 FROM {$applications_table} a
					 LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id
					 WHERE {$where_data['clause']}",
					...$where_data['values']
				)
			);
		}

		// Data Query mit sicheren Prepared Statements
		$query_values = array_merge( $where_data['values'], [ $per_page, $offset ] );
		$where_sql    = empty( $where_data['values'] ) ? '1=1' : $where_data['clause'];

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, c.first_name, c.last_name, c.email, c.phone
				 FROM {$applications_table} a
				 LEFT JOIN {$candidates_table} c ON a.candidate_id = c.id
				 WHERE {$where_sql}
				 ORDER BY {$orderby} {$order}
				 LIMIT %d OFFSET %d",
				...$query_values
			),
			ARRAY_A
		);

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * WHERE-Klausel bauen (sicher mit Prepared Statements)
	 *
	 * @return array{clause: string, values: array}
	 */
	private function build_where_clause(): array {
		$conditions = [];
		$values     = [];

		// Status-Filter.
		if ( ! empty( $_GET['status'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_GET['status'] ) ), ApplicationStatus::getAll() ) ) {
			$conditions[] = 'a.status = %s';
			$values[]     = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		} else {
			// Gelöschte Bewerbungen standardmäßig ausblenden.
			$conditions[] = 'a.status != %s';
			$values[]     = 'deleted';
		}

		// Job-Filter.
		if ( ! empty( $_GET['job_id'] ) ) {
			$conditions[] = 'a.job_id = %d';
			$values[]     = absint( $_GET['job_id'] );
		}

		// Suche.
		if ( ! empty( $_GET['s'] ) ) {
			global $wpdb;
			$search       = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) . '%';
			$conditions[] = '(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)';
			$values[]     = $search;
			$values[]     = $search;
			$values[]     = $search;
		}

		// Zugewiesene Stellen (Rollen-basiert).
		if ( ! current_user_can( 'manage_options' ) ) {
			$capability_service = new CapabilityService();
			$assigned_job_ids   = $capability_service->getAssignedJobIds( get_current_user_id() );

			if ( empty( $assigned_job_ids ) ) {
				$conditions[] = '1=0'; // Keine Zuweisungen → keine Ergebnisse.
			} else {
				$placeholders = implode( ',', array_fill( 0, count( $assigned_job_ids ), '%d' ) );
				$conditions[] = "a.job_id IN ({$placeholders})";
				$values       = array_merge( $values, array_map( 'intval', $assigned_job_ids ) );
			}
		}

		// Datum von.
		if ( ! empty( $_GET['date_from'] ) ) {
			$conditions[] = 'DATE(a.created_at) >= %s';
			$values[]     = sanitize_text_field( wp_unslash( $_GET['date_from'] ) );
		}

		// Datum bis.
		if ( ! empty( $_GET['date_to'] ) ) {
			$conditions[] = 'DATE(a.created_at) <= %s';
			$values[]     = sanitize_text_field( wp_unslash( $_GET['date_to'] ) );
		}

		// Klausel zusammenbauen
		$clause = empty( $conditions ) ? '1=1' : implode( ' AND ', $conditions );

		return [
			'clause' => $clause,
			'values' => $values,
		];
	}

	/**
	 * Sortierung ermitteln
	 *
	 * @return string
	 */
	private function get_orderby(): string {
		$allowed = [ 'last_name', 'job_id', 'status', 'created_at' ];
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';

		return in_array( $orderby, $allowed, true ) ? "a.{$orderby}" : 'a.created_at';
	}

	/**
	 * Sortierrichtung ermitteln
	 *
	 * @return string
	 */
	private function get_order(): string {
		$order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
		return in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';
	}

	/**
	 * Checkbox-Spalte
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="application_ids[]" value="%d" />',
			absint( $item['id'] )
		);
	}

	/**
	 * Bewerber-Spalte
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_applicant( $item ): string {
		$name = esc_html( trim( $item['first_name'] . ' ' . $item['last_name'] ) );
		if ( empty( trim( $name ) ) ) {
			$name = esc_html( $item['email'] );
		}

		$detail_url = admin_url(
			sprintf(
				'admin.php?page=rp-application-detail&id=%d',
				absint( $item['id'] )
			)
		);

		$output  = sprintf( '<strong><a href="%s">%s</a></strong>', esc_url( $detail_url ), $name );
		$output .= '<br><small>' . esc_html( $item['email'] ) . '</small>';

		if ( ! empty( $item['phone'] ) ) {
			$output .= '<br><small>' . esc_html( $item['phone'] ) . '</small>';
		}

		return $output;
	}

	/**
	 * Stellen-Spalte
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_job( $item ): string {
		$job = get_post( $item['job_id'] );
		if ( ! $job ) {
			return '<em>' . esc_html__( 'Deleted', 'recruiting-playbook' ) . '</em>';
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_post_link( $job->ID ) ),
			esc_html( $job->post_title )
		);
	}

	/**
	 * Status-Spalte
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_status( $item ): string {
		$status = $item['status'];
		$labels = ApplicationStatus::getAll();
		$colors = ApplicationStatus::getColors();

		$label = $labels[ $status ] ?? $status;
		$color = $colors[ $status ] ?? '#787c82';

		return sprintf(
			'<span class="rp-status-badge" style="background-color: %s; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">%s</span>',
			esc_attr( $color ),
			esc_html( $label )
		);
	}

	/**
	 * Dokumente-Spalte
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_documents( $item ): string {
		global $wpdb;

		$documents_table = $wpdb->prefix . 'rp_documents';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$documents_table} WHERE application_id = %d",
				absint( $item['id'] )
			)
		);

		if ( 0 === $count ) {
			return '<span class="dashicons dashicons-media-default" style="color: #ccc;"></span> 0';
		}

		return sprintf(
			'<span class="dashicons dashicons-media-document" style="color: #2271b1;"></span> %d',
			$count
		);
	}

	/**
	 * Datum-Spalte
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_created_at( $item ): string {
		$date       = strtotime( $item['created_at'] );
		$human_diff = human_time_diff( $date, current_time( 'timestamp' ) );

		return sprintf(
			'<span title="%s">%s</span>',
			esc_attr( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date ) ),
			/* translators: %s: Human time difference */
			sprintf( esc_html__( '%s ago', 'recruiting-playbook' ), $human_diff )
		);
	}

	/**
	 * Aktionen-Spalte
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_actions( $item ): string {
		$detail_url = admin_url(
			sprintf(
				'admin.php?page=rp-application-detail&id=%d',
				absint( $item['id'] )
			)
		);

		$actions   = [];
		$actions[] = sprintf(
			'<a href="%s" class="button button-small">%s</a>',
			esc_url( $detail_url ),
			esc_html__( 'View', 'recruiting-playbook' )
		);

		if ( 'new' === $item['status'] ) {
			$screening_url = wp_nonce_url(
				admin_url(
					sprintf(
						'admin.php?page=recruiting-playbook&action=set_status&id=%d&status=screening',
						absint( $item['id'] )
					)
				),
				'rp_set_status_' . $item['id']
			);

			$actions[] = sprintf(
				'<a href="%s" class="button button-small" style="background: #dba617; border-color: #dba617; color: white;">%s</a>',
				esc_url( $screening_url ),
				esc_html__( 'Screen', 'recruiting-playbook' )
			);
		}

		if ( 'deleted' !== $item['status'] ) {
			$delete_url = wp_nonce_url(
				admin_url(
					sprintf(
						'admin.php?page=recruiting-playbook&action=delete&id=%d',
						absint( $item['id'] )
					)
				),
				'rp_delete_' . $item['id']
			);

			$actions[] = sprintf(
				'<a href="%s" class="button button-small" style="color: #b32d2e; border-color: #b32d2e;" onclick="return confirm(\'%s\');" title="%s"><span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; vertical-align: text-bottom;"></span></a>',
				esc_url( $delete_url ),
				esc_js( __( 'Are you sure you want to delete this application?', 'recruiting-playbook' ) ),
				esc_attr__( 'Delete', 'recruiting-playbook' )
			);
		}

		return implode( ' ', $actions );
	}

	/**
	 * Keine Items Meldung
	 */
	public function no_items(): void {
		esc_html_e( 'No applications found.', 'recruiting-playbook' );
	}

	/**
	 * Extra Tablenav (Filter)
	 *
	 * @param string $which Position (top/bottom).
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		echo '<div class="alignleft actions">';

		// Status-Filter.
		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		echo '<select name="status">';
		echo '<option value="">' . esc_html__( 'All Statuses', 'recruiting-playbook' ) . '</option>';
		foreach ( ApplicationStatus::getVisible() as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $current_status, $value, false ),
				esc_html( $label )
			);
		}
		// Gelöschte als separate Option.
		printf(
			'<option value="deleted" %s>%s</option>',
			selected( $current_status, 'deleted', false ),
			esc_html__( '🗑️ Deleted', 'recruiting-playbook' )
		);
		echo '</select>';

		// Job-Filter.
		$jobs = get_posts(
			[
				'post_type'      => 'job_listing',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$current_job = isset( $_GET['job_id'] ) ? absint( $_GET['job_id'] ) : 0;
		echo '<select name="job_id">';
		echo '<option value="">' . esc_html__( 'All Jobs', 'recruiting-playbook' ) . '</option>';
		foreach ( $jobs as $job ) {
			printf(
				'<option value="%d" %s>%s</option>',
				$job->ID,
				selected( $current_job, $job->ID, false ),
				esc_html( $job->post_title )
			);
		}
		echo '</select>';

		// Datum-Filter.
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

		printf(
			'<input type="date" name="date_from" value="%s" placeholder="%s" />',
			esc_attr( $date_from ),
			esc_attr__( 'From', 'recruiting-playbook' )
		);
		printf(
			'<input type="date" name="date_to" value="%s" placeholder="%s" />',
			esc_attr( $date_to ),
			esc_attr__( 'To', 'recruiting-playbook' )
		);

		submit_button( __( 'Filter', 'recruiting-playbook' ), '', 'filter_action', false );

		echo '</div>';
	}

	/**
	 * Bulk Actions verarbeiten
	 *
	 * Hinweis: Die eigentliche Verarbeitung erfolgt in Menu::handleBulkActions()
	 * VOR dem Seitenrendering, damit der Redirect funktioniert.
	 * Diese Methode ist nur noch für die WP_List_Table Kompatibilität vorhanden.
	 */
	public function process_bulk_action(): void {
		// Bulk-Actions werden in Menu::handleBulkActions() verarbeitet.
		// Diese Methode muss existieren, wird aber nicht mehr verwendet.
	}

}

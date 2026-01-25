<?php
/**
 * Email Log Repository - Datenzugriff für E-Mail-Log
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für E-Mail-Log-Operationen
 */
class EmailLogRepository {

	/**
	 * Tabellen-Name
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->table = Schema::getTables()['email_log'];
	}

	/**
	 * Log-Eintrag erstellen
	 *
	 * @param array $data Log-Daten.
	 * @return int|false Insert ID oder false bei Fehler.
	 */
	public function create( array $data ): int|false {
		global $wpdb;

		$now = current_time( 'mysql' );

		$defaults = [
			'status'     => 'pending',
			'sent_by'    => get_current_user_id() ?: null,
			'created_at' => $now,
		];

		$data = wp_parse_args( $data, $defaults );

		// Metadata als JSON.
		if ( isset( $data['metadata'] ) && is_array( $data['metadata'] ) ) {
			$data['metadata'] = wp_json_encode( $data['metadata'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			$data,
			$this->getFormats( $data )
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Log-Eintrag finden
	 *
	 * @param int $id Log-ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$log = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! $log ) {
			return null;
		}

		return $this->enrichLog( $log );
	}

	/**
	 * Logs für Bewerbung laden
	 *
	 * @param int   $application_id Bewerbungs-ID.
	 * @param array $args           Query-Argumente.
	 * @return array
	 */
	public function findByApplication( int $application_id, array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'status'   => null,
		];

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Base Query.
		$where  = [ 'application_id = %d' ];
		$values = [ $application_id ];

		// Status Filter.
		if ( $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		$where_clause = implode( ' AND ', $where );

		// Total Count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}",
				...$values
			)
		);

		// Items laden.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE {$where_clause}
				ORDER BY created_at DESC
				LIMIT %d OFFSET %d",
				...array_merge( $values, [ $args['per_page'], $offset ] )
			),
			ARRAY_A
		);

		return [
			'items' => array_map( [ $this, 'enrichLog' ], $logs ?: [] ),
			'total' => $total,
			'pages' => (int) ceil( $total / $args['per_page'] ),
		];
	}

	/**
	 * Logs für Kandidaten laden
	 *
	 * @param int   $candidate_id Kandidaten-ID.
	 * @param array $args         Query-Argumente.
	 * @return array
	 */
	public function findByCandidate( int $candidate_id, array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'status'   => null,
		];

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Base Query.
		$where  = [ 'candidate_id = %d' ];
		$values = [ $candidate_id ];

		// Status Filter.
		if ( $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		$where_clause = implode( ' AND ', $where );

		// Total Count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}",
				...$values
			)
		);

		// Items laden.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE {$where_clause}
				ORDER BY created_at DESC
				LIMIT %d OFFSET %d",
				...array_merge( $values, [ $args['per_page'], $offset ] )
			),
			ARRAY_A
		);

		return [
			'items' => array_map( [ $this, 'enrichLog' ], $logs ?: [] ),
			'total' => $total,
			'pages' => (int) ceil( $total / $args['per_page'] ),
		];
	}

	/**
	 * Alle Logs mit Paginierung laden
	 *
	 * @param array $args Query-Argumente.
	 * @return array
	 */
	public function getList( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'status'   => null,
			'search'   => null,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		];

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Base Query.
		$where  = [ '1=1' ];
		$values = [];

		// Status Filter.
		if ( $args['status'] ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		// Search.
		if ( $args['search'] ) {
			$search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(recipient_email LIKE %s OR recipient_name LIKE %s OR subject LIKE %s)';
			$values[] = $search;
			$values[] = $search;
			$values[] = $search;
		}

		$where_clause = implode( ' AND ', $where );

		// Order.
		$orderby = in_array( $args['orderby'], [ 'created_at', 'sent_at', 'status', 'recipient_email' ], true )
			? $args['orderby']
			: 'created_at';
		$order = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// Total Count.
		$count_sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}";
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
		}

		// Items laden.
		$sql = "SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$logs = $wpdb->get_results(
				$wpdb->prepare( $sql, ...array_merge( $values, [ $args['per_page'], $offset ] ) ),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$logs = $wpdb->get_results(
				$wpdb->prepare( $sql, $args['per_page'], $offset ),
				ARRAY_A
			);
		}

		return [
			'items' => array_map( [ $this, 'enrichLog' ], $logs ?: [] ),
			'total' => $total,
			'pages' => (int) ceil( $total / $args['per_page'] ),
		];
	}

	/**
	 * Pending E-Mails für Queue laden
	 *
	 * @param int $limit Maximale Anzahl.
	 * @return array
	 */
	public function getPendingForQueue( int $limit = 50 ): array {
		global $wpdb;

		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE status = 'pending'
				AND (scheduled_at IS NULL OR scheduled_at <= %s)
				ORDER BY scheduled_at ASC, created_at ASC
				LIMIT %d",
				$now,
				$limit
			),
			ARRAY_A
		);

		return array_map( [ $this, 'enrichLog' ], $logs ?: [] );
	}

	/**
	 * Scheduled E-Mails laden
	 *
	 * @param array $args Query-Argumente.
	 * @return array
	 */
	public function getScheduled( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'per_page' => 20,
			'page'     => 1,
		];

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		$now    = current_time( 'mysql' );

		// Total Count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table}
				WHERE status = 'pending' AND scheduled_at > %s",
				$now
			)
		);

		// Items laden.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE status = 'pending' AND scheduled_at > %s
				ORDER BY scheduled_at ASC
				LIMIT %d OFFSET %d",
				$now,
				$args['per_page'],
				$offset
			),
			ARRAY_A
		);

		return [
			'items' => array_map( [ $this, 'enrichLog' ], $logs ?: [] ),
			'total' => $total,
			'pages' => (int) ceil( $total / $args['per_page'] ),
		];
	}

	/**
	 * Log-Eintrag aktualisieren
	 *
	 * @param int   $id   Log-ID.
	 * @param array $data Update-Daten.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		// Metadata als JSON.
		if ( isset( $data['metadata'] ) && is_array( $data['metadata'] ) ) {
			$data['metadata'] = wp_json_encode( $data['metadata'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			$data,
			[ 'id' => $id ],
			$this->getFormats( $data ),
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Status aktualisieren
	 *
	 * @param int    $id     Log-ID.
	 * @param string $status Neuer Status.
	 * @param string $error  Optionale Fehlermeldung.
	 * @return bool
	 */
	public function updateStatus( int $id, string $status, string $error = '' ): bool {
		$data = [ 'status' => $status ];

		if ( 'sent' === $status ) {
			$data['sent_at'] = current_time( 'mysql' );
		}

		if ( 'failed' === $status && $error ) {
			$data['error_message'] = $error;
		}

		return $this->update( $id, $data );
	}

	/**
	 * Als geöffnet markieren
	 *
	 * @param int $id Log-ID.
	 * @return bool
	 */
	public function markAsOpened( int $id ): bool {
		global $wpdb;

		// Nur wenn noch nicht geöffnet.
		$log = $this->find( $id );
		if ( ! $log || $log['opened_at'] ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			[ 'opened_at' => current_time( 'mysql' ) ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Als geklickt markieren
	 *
	 * @param int $id Log-ID.
	 * @return bool
	 */
	public function markAsClicked( int $id ): bool {
		global $wpdb;

		// Nur wenn noch nicht geklickt.
		$log = $this->find( $id );
		if ( ! $log || $log['clicked_at'] ) {
			return false;
		}

		$data = [ 'clicked_at' => current_time( 'mysql' ) ];

		// Auch als geöffnet markieren wenn noch nicht geschehen.
		if ( ! $log['opened_at'] ) {
			$data['opened_at'] = current_time( 'mysql' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			$data,
			[ 'id' => $id ],
			array_fill( 0, count( $data ), '%s' ),
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Geplante E-Mail stornieren
	 *
	 * @param int $id Log-ID.
	 * @return bool
	 */
	public function cancelScheduled( int $id ): bool {
		$log = $this->find( $id );

		// Nur pending E-Mails können storniert werden.
		if ( ! $log || 'pending' !== $log['status'] ) {
			return false;
		}

		return $this->updateStatus( $id, 'cancelled' );
	}

	/**
	 * Statistiken für Zeitraum
	 *
	 * @param string $start_date Start-Datum (Y-m-d).
	 * @param string $end_date   End-Datum (Y-m-d).
	 * @return array
	 */
	public function getStatistics( string $start_date, string $end_date ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) as total,
					SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
					SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
					SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
					SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
					SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
					SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked
				FROM {$this->table}
				WHERE created_at >= %s AND created_at < %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			),
			ARRAY_A
		);

		// Raten berechnen.
		$sent = (int) ( $stats['sent'] ?? 0 );

		return [
			'total'        => (int) ( $stats['total'] ?? 0 ),
			'sent'         => $sent,
			'failed'       => (int) ( $stats['failed'] ?? 0 ),
			'pending'      => (int) ( $stats['pending'] ?? 0 ),
			'cancelled'    => (int) ( $stats['cancelled'] ?? 0 ),
			'opened'       => (int) ( $stats['opened'] ?? 0 ),
			'clicked'      => (int) ( $stats['clicked'] ?? 0 ),
			'open_rate'    => $sent > 0 ? round( ( (int) $stats['opened'] / $sent ) * 100, 1 ) : 0,
			'click_rate'   => $sent > 0 ? round( ( (int) $stats['clicked'] / $sent ) * 100, 1 ) : 0,
			'success_rate' => ( (int) $stats['total'] ) > 0
				? round( ( $sent / (int) $stats['total'] ) * 100, 1 )
				: 0,
		];
	}

	/**
	 * Log mit zusätzlichen Daten anreichern
	 *
	 * @param array $log Log-Daten.
	 * @return array
	 */
	private function enrichLog( array $log ): array {
		// JSON parsen.
		if ( ! empty( $log['metadata'] ) ) {
			$log['metadata'] = json_decode( $log['metadata'], true ) ?: [];
		} else {
			$log['metadata'] = [];
		}

		// Sender laden.
		if ( $log['sent_by'] ) {
			$user             = get_userdata( (int) $log['sent_by'] );
			$log['sent_by_user'] = $user ? [
				'id'   => $user->ID,
				'name' => $user->display_name,
			] : null;
		} else {
			$log['sent_by_user'] = null;
		}

		// Status-Konfiguration.
		$status_config       = $this->getStatusConfig( $log['status'] );
		$log['status_label'] = $status_config['label'];
		$log['status_color'] = $status_config['color'];

		// Typen konvertieren.
		$log['id']             = (int) $log['id'];
		$log['application_id'] = $log['application_id'] ? (int) $log['application_id'] : null;
		$log['candidate_id']   = $log['candidate_id'] ? (int) $log['candidate_id'] : null;
		$log['template_id']    = $log['template_id'] ? (int) $log['template_id'] : null;
		$log['sent_by']        = $log['sent_by'] ? (int) $log['sent_by'] : null;

		// Berechtigungen.
		$is_admin           = current_user_can( 'manage_options' );
		$log['can_cancel']  = $is_admin && 'pending' === $log['status'];
		$log['can_resend']  = $is_admin && in_array( $log['status'], [ 'sent', 'failed' ], true );

		return $log;
	}

	/**
	 * Status-Konfiguration
	 *
	 * @param string $status Status.
	 * @return array
	 */
	private function getStatusConfig( string $status ): array {
		$configs = [
			'pending'   => [
				'label' => __( 'Ausstehend', 'recruiting-playbook' ),
				'color' => '#dba617',
			],
			'sent'      => [
				'label' => __( 'Gesendet', 'recruiting-playbook' ),
				'color' => '#00a32a',
			],
			'failed'    => [
				'label' => __( 'Fehlgeschlagen', 'recruiting-playbook' ),
				'color' => '#d63638',
			],
			'cancelled' => [
				'label' => __( 'Storniert', 'recruiting-playbook' ),
				'color' => '#787c82',
			],
		];

		return $configs[ $status ] ?? [
			'label' => $status,
			'color' => '#787c82',
		];
	}

	/**
	 * Format-Array für wpdb-Operationen
	 *
	 * @param array $data Daten.
	 * @return array
	 */
	private function getFormats( array $data ): array {
		$formats = [];

		foreach ( $data as $value ) {
			if ( is_int( $value ) ) {
				$formats[] = '%d';
			} elseif ( is_float( $value ) ) {
				$formats[] = '%f';
			} elseif ( is_null( $value ) ) {
				$formats[] = '%s';
			} else {
				$formats[] = '%s';
			}
		}

		return $formats;
	}
}

<?php
/**
 * Activity Service - Geschäftslogik für Activity Log
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Service für Activity Log Operationen
 */
class ActivityService {

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
		$this->table = Schema::getTables()['activity_log'];
	}

	/**
	 * Activity-Eintrag erstellen
	 *
	 * @param array $data Activity-Daten.
	 * @return int|false
	 */
	public function log( array $data ): int|false {
		global $wpdb;

		$current_user = wp_get_current_user();

		$defaults = [
			'object_type' => 'application',
			'user_id'     => get_current_user_id() ?: null,
			'user_name'   => $current_user->ID ? $current_user->display_name : null,
			'created_at'  => current_time( 'mysql' ),
			'ip_address'  => $this->getClientIp(),
		];

		$data = wp_parse_args( $data, $defaults );

		// Meta als JSON speichern.
		if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
			$data['meta'] = wp_json_encode( $data['meta'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $this->table, $data );

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Timeline für Bewerbung laden
	 *
	 * @param int   $application_id Bewerbungs-ID.
	 * @param array $args           Query-Argumente.
	 * @return array
	 */
	public function getTimeline( int $application_id, array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'per_page' => 50,
			'page'     => 1,
			'types'    => [],
		];

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Base Query.
		$where  = [ 'object_type = %s', 'object_id = %d' ];
		$values = [ 'application', $application_id ];

		// Type-Filter.
		if ( ! empty( $args['types'] ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $args['types'] ), '%s' ) );
			$where[]      = "action IN ($placeholders)";
			$values       = array_merge( $values, $args['types'] );
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
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE {$where_clause}
				ORDER BY created_at DESC
				LIMIT %d OFFSET %d",
				...array_merge( $values, [ $args['per_page'], $offset ] )
			),
			ARRAY_A
		);

		// Items anreichern.
		$enriched_items = array_map( [ $this, 'enrichTimelineItem' ], $items );

		return [
			'items' => $enriched_items,
			'total' => $total,
			'pages' => (int) ceil( $total / $args['per_page'] ),
		];
	}

	/**
	 * Timeline für Kandidat laden
	 *
	 * @param int   $candidate_id Kandidaten-ID.
	 * @param array $args         Query-Argumente.
	 * @return array
	 */
	public function getTimelineForCandidate( int $candidate_id, array $args = [] ): array {
		global $wpdb;

		$applications_table = Schema::getTables()['applications'];

		$defaults = [
			'per_page' => 50,
			'page'     => 1,
			'types'    => [],
		];

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Alle Bewerbungen des Kandidaten finden.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$application_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$applications_table} WHERE candidate_id = %d",
				$candidate_id
			)
		);

		if ( empty( $application_ids ) ) {
			return [
				'items' => [],
				'total' => 0,
				'pages' => 0,
			];
		}

		$placeholders = implode( ', ', array_fill( 0, count( $application_ids ), '%d' ) );

		// Base Query.
		$where  = [ 'object_type = %s', "object_id IN ($placeholders)" ];
		$values = array_merge( [ 'application' ], $application_ids );

		// Type-Filter.
		if ( ! empty( $args['types'] ) ) {
			$type_placeholders = implode( ', ', array_fill( 0, count( $args['types'] ), '%s' ) );
			$where[]           = "action IN ($type_placeholders)";
			$values            = array_merge( $values, $args['types'] );
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
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE {$where_clause}
				ORDER BY created_at DESC
				LIMIT %d OFFSET %d",
				...array_merge( $values, [ $args['per_page'], $offset ] )
			),
			ARRAY_A
		);

		// Items anreichern.
		$enriched_items = array_map( [ $this, 'enrichTimelineItem' ], $items );

		return [
			'items' => $enriched_items,
			'total' => $total,
			'pages' => (int) ceil( $total / $args['per_page'] ),
		];
	}

	/**
	 * Timeline-Item anreichern
	 *
	 * @param array $item Raw-Item.
	 * @return array
	 */
	private function enrichTimelineItem( array $item ): array {
		// Meta parsen.
		if ( ! empty( $item['meta'] ) ) {
			$item['meta'] = json_decode( $item['meta'], true );
		}

		// User-Daten.
		if ( $item['user_id'] ) {
			$user         = get_userdata( (int) $item['user_id'] );
			$item['user'] = $user ? [
				'id'     => $user->ID,
				'name'   => $user->display_name,
				'avatar' => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
			] : null;
		} else {
			$item['user'] = null;
		}

		// Icon und Farbe basierend auf Type.
		$type_config      = $this->getTypeConfig( $item['action'] );
		$item['icon']     = $type_config['icon'];
		$item['color']    = $type_config['color'];
		$item['category'] = $type_config['category'];

		// Typen konvertieren.
		$item['id']        = (int) $item['id'];
		$item['object_id'] = (int) $item['object_id'];
		$item['user_id']   = $item['user_id'] ? (int) $item['user_id'] : null;

		return $item;
	}

	/**
	 * Type-Konfiguration
	 *
	 * @param string $type Activity-Type.
	 * @return array
	 */
	private function getTypeConfig( string $type ): array {
		$configs = [
			'application_received'  => [
				'icon'     => 'dashicons-plus-alt',
				'color'    => '#00a32a',
				'category' => 'application',
			],
			'status_changed'        => [
				'icon'     => 'dashicons-update',
				'color'    => '#dba617',
				'category' => 'status',
			],
			'note_added'            => [
				'icon'     => 'dashicons-edit',
				'color'    => '#2271b1',
				'category' => 'note',
			],
			'note_updated'          => [
				'icon'     => 'dashicons-edit',
				'color'    => '#2271b1',
				'category' => 'note',
			],
			'note_deleted'          => [
				'icon'     => 'dashicons-trash',
				'color'    => '#d63638',
				'category' => 'note',
			],
			'rating_added'          => [
				'icon'     => 'dashicons-star-filled',
				'color'    => '#f0b849',
				'category' => 'rating',
			],
			'rating_updated'        => [
				'icon'     => 'dashicons-star-half',
				'color'    => '#f0b849',
				'category' => 'rating',
			],
			'email_sent'            => [
				'icon'     => 'dashicons-email-alt',
				'color'    => '#9b59b6',
				'category' => 'email',
			],
			'document_viewed'       => [
				'icon'     => 'dashicons-visibility',
				'color'    => '#787c82',
				'category' => 'document',
			],
			'document_downloaded'   => [
				'icon'     => 'dashicons-download',
				'color'    => '#787c82',
				'category' => 'document',
			],
			'talent_pool_added'     => [
				'icon'     => 'dashicons-groups',
				'color'    => '#1e8cbe',
				'category' => 'talent_pool',
			],
			'talent_pool_removed'   => [
				'icon'     => 'dashicons-dismiss',
				'color'    => '#d63638',
				'category' => 'talent_pool',
			],
			'file_upload_failed'    => [
				'icon'     => 'dashicons-warning',
				'color'    => '#d63638',
				'category' => 'error',
			],
		];

		return $configs[ $type ] ?? [
			'icon'     => 'dashicons-info',
			'color'    => '#787c82',
			'category' => 'other',
		];
	}

	/**
	 * Client-IP ermitteln
	 *
	 * @return string
	 */
	private function getClientIp(): string {
		$ip_keys = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}
}

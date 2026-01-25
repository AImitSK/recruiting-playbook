<?php
/**
 * Talent Pool Repository - Datenzugriff für Talent-Pool
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für Talent-Pool-Operationen
 */
class TalentPoolRepository {

	/**
	 * Tabellen-Name
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Standard-Aufbewahrungsdauer in Monaten
	 */
	private const DEFAULT_RETENTION_MONTHS = 24;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->table = Schema::getTables()['talent_pool'];
	}

	/**
	 * Kandidat zum Talent-Pool hinzufügen
	 *
	 * @param array $data Pool-Daten.
	 * @return int|false Insert ID oder false bei Fehler.
	 */
	public function create( array $data ): int|false {
		global $wpdb;

		// Ablaufdatum berechnen falls nicht gesetzt.
		if ( empty( $data['expires_at'] ) ) {
			$retention_months   = (int) get_option( 'rp_talent_pool_retention', self::DEFAULT_RETENTION_MONTHS );
			$data['expires_at'] = gmdate( 'Y-m-d H:i:s', strtotime( "+{$retention_months} months" ) );
		}

		$defaults = [
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		];

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			$data,
			$this->getFormats( $data )
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Eintrag finden
	 *
	 * @param int $id Eintrag-ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL",
				$id
			),
			ARRAY_A
		);

		return $entry ?: null;
	}

	/**
	 * Eintrag mit Kandidaten-Daten finden
	 *
	 * @param int $id Eintrag-ID.
	 * @return array|null
	 */
	public function findWithCandidate( int $id ): ?array {
		global $wpdb;

		$candidates_table = Schema::getTables()['candidates'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT tp.*, c.first_name, c.last_name, c.email, c.phone
				FROM {$this->table} tp
				LEFT JOIN {$candidates_table} c ON tp.candidate_id = c.id
				WHERE tp.id = %d AND tp.deleted_at IS NULL",
				$id
			),
			ARRAY_A
		);

		if ( ! $entry ) {
			return null;
		}

		return $this->enrichEntry( $entry );
	}

	/**
	 * Eintrag für Kandidat finden
	 *
	 * @param int $candidate_id Kandidaten-ID.
	 * @return array|null
	 */
	public function findByCandidate( int $candidate_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE candidate_id = %d AND deleted_at IS NULL",
				$candidate_id
			),
			ARRAY_A
		);

		return $entry ?: null;
	}

	/**
	 * Prüfen ob Kandidat im Pool ist
	 *
	 * @param int $candidate_id Kandidaten-ID.
	 * @return bool
	 */
	public function exists( int $candidate_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table}
				WHERE candidate_id = %d AND deleted_at IS NULL",
				$candidate_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Talent-Pool Liste laden
	 *
	 * @param array $args Query-Argumente.
	 * @return array
	 */
	public function getList( array $args = [] ): array {
		global $wpdb;

		$candidates_table = Schema::getTables()['candidates'];

		$defaults = [
			'per_page' => 20,
			'page'     => 1,
			'search'   => '',
			'tags'     => [],
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );

		$where  = [ 'tp.deleted_at IS NULL' ];
		$values = [];

		// Suche.
		if ( ! empty( $args['search'] ) ) {
			$search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)';
			$values[] = $search;
			$values[] = $search;
			$values[] = $search;
		}

		// Tags-Filter.
		if ( ! empty( $args['tags'] ) ) {
			$tag_conditions = [];
			foreach ( (array) $args['tags'] as $tag ) {
				$tag_conditions[] = 'tp.tags LIKE %s';
				$values[]         = '%' . $wpdb->esc_like( trim( $tag ) ) . '%';
			}
			$where[] = '(' . implode( ' OR ', $tag_conditions ) . ')';
		}

		$where_clause = implode( ' AND ', $where );

		// Sortierung - Whitelist.
		$allowed_orderby = [
			'created_at' => 'tp.created_at',
			'expires_at' => 'tp.expires_at',
			'name'       => 'c.last_name',
		];
		$orderby = $allowed_orderby[ $args['orderby'] ] ?? 'tp.created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// Pagination.
		$per_page = min( max( (int) $args['per_page'], 1 ), 100 );
		$page     = max( (int) $args['page'], 1 );
		$offset   = ( $page - 1 ) * $per_page;

		// Total Count.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			$values
				? $wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} tp
					LEFT JOIN {$candidates_table} c ON tp.candidate_id = c.id
					WHERE {$where_clause}",
					...$values
				)
				: "SELECT COUNT(*) FROM {$this->table} tp
					LEFT JOIN {$candidates_table} c ON tp.candidate_id = c.id
					WHERE {$where_clause}"
		);

		// Daten laden.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tp.*, c.first_name, c.last_name, c.email, c.phone
				FROM {$this->table} tp
				LEFT JOIN {$candidates_table} c ON tp.candidate_id = c.id
				WHERE {$where_clause}
				ORDER BY {$orderby} {$order}
				LIMIT %d OFFSET %d",
				...array_merge( $values, [ $per_page, $offset ] )
			),
			ARRAY_A
		);

		return [
			'items' => array_map( [ $this, 'enrichEntry' ], $items ),
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
		];
	}

	/**
	 * Eintrag aktualisieren
	 *
	 * @param int   $id   Eintrag-ID.
	 * @param array $data Update-Daten.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

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
	 * Eintrag soft-löschen
	 *
	 * @param int $id Eintrag-ID.
	 * @return bool
	 */
	public function softDelete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			[
				'deleted_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Abgelaufene Einträge löschen
	 *
	 * @return int Anzahl gelöschter Einträge.
	 */
	public function deleteExpired(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table}
				SET deleted_at = %s, updated_at = %s
				WHERE expires_at < %s AND deleted_at IS NULL",
				current_time( 'mysql' ),
				current_time( 'mysql' ),
				current_time( 'mysql' )
			)
		);

		return (int) $result;
	}

	/**
	 * Bald ablaufende Einträge laden
	 *
	 * @param int $days_before Tage vor Ablauf.
	 * @return array
	 */
	public function getExpiring( int $days_before = 30 ): array {
		global $wpdb;

		$candidates_table = Schema::getTables()['candidates'];

		$expires_before = gmdate( 'Y-m-d H:i:s', strtotime( "+{$days_before} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tp.*, c.first_name, c.last_name, c.email
				FROM {$this->table} tp
				LEFT JOIN {$candidates_table} c ON tp.candidate_id = c.id
				WHERE tp.expires_at <= %s
				AND tp.expires_at > %s
				AND tp.reminder_sent = 0
				AND tp.deleted_at IS NULL",
				$expires_before,
				current_time( 'mysql' )
			),
			ARRAY_A
		);
	}

	/**
	 * Reminder als gesendet markieren
	 *
	 * @param int $id Eintrag-ID.
	 * @return bool
	 */
	public function markReminderSent( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			[
				'reminder_sent' => 1,
				'updated_at'    => current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%d', '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Alle verwendeten Tags laden
	 *
	 * @return array
	 */
	public function getAllTags(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tags_raw = $wpdb->get_col(
			"SELECT DISTINCT tags FROM {$this->table} WHERE tags IS NOT NULL AND tags != '' AND deleted_at IS NULL"
		);

		$all_tags = [];
		foreach ( $tags_raw as $tags_string ) {
			$tags = array_map( 'trim', explode( ',', $tags_string ) );
			$all_tags = array_merge( $all_tags, $tags );
		}

		$all_tags = array_unique( array_filter( $all_tags ) );
		sort( $all_tags );

		return $all_tags;
	}

	/**
	 * Eintrag anreichern
	 *
	 * @param array $entry Eintrag-Daten.
	 * @return array
	 */
	private function enrichEntry( array $entry ): array {
		// User-Daten laden.
		$user = get_userdata( (int) $entry['added_by'] );

		$entry['added_by_user'] = $user ? [
			'id'     => $user->ID,
			'name'   => $user->display_name,
			'avatar' => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
		] : null;

		// Tags als Array.
		$entry['tags_array'] = $entry['tags']
			? array_map( 'trim', explode( ',', $entry['tags'] ) )
			: [];

		// Ablauf-Status berechnen.
		$expires_at      = strtotime( $entry['expires_at'] );
		$now             = time();
		$days_until      = (int) ceil( ( $expires_at - $now ) / DAY_IN_SECONDS );
		$entry['is_expired'] = $days_until < 0;
		$entry['is_expiring_soon'] = $days_until >= 0 && $days_until <= 30;
		$entry['days_until_expiry'] = max( 0, $days_until );

		// Typen konvertieren.
		$entry['id']            = (int) $entry['id'];
		$entry['candidate_id']  = (int) $entry['candidate_id'];
		$entry['added_by']      = (int) $entry['added_by'];
		$entry['reminder_sent'] = (bool) $entry['reminder_sent'];

		return $entry;
	}

	/**
	 * Format-Array für wpdb-Operationen
	 *
	 * @param array $data Daten.
	 * @return array
	 */
	private function getFormats( array $data ): array {
		$formats = [];

		foreach ( $data as $key => $value ) {
			if ( is_int( $value ) ) {
				$formats[] = '%d';
			} elseif ( is_float( $value ) ) {
				$formats[] = '%f';
			} else {
				$formats[] = '%s';
			}
		}

		return $formats;
	}
}

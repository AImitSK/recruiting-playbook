<?php
/**
 * FieldDefinition Repository
 *
 * Data Access Layer für Feld-Definitionen.
 *
 * @package RecruitingPlaybook\Repositories
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;
use RecruitingPlaybook\Models\FieldDefinition;

/**
 * Repository für Feld-Definitionen
 */
class FieldDefinitionRepository {

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
		$this->table = Schema::getTables()['field_definitions'];
	}

	/**
	 * Feld-Definition finden
	 *
	 * @param int $id Field ID.
	 * @return FieldDefinition|null
	 */
	public function find( int $id ): ?FieldDefinition {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL",
				$id
			),
			ARRAY_A
		);

		return $row ? FieldDefinition::fromArray( $row ) : null;
	}

	/**
	 * Feld-Definition nach Key finden
	 *
	 * @param string   $field_key   Field Key.
	 * @param int|null $template_id Template ID.
	 * @param int|null $job_id      Job ID.
	 * @return FieldDefinition|null
	 */
	public function findByKey( string $field_key, ?int $template_id = null, ?int $job_id = null ): ?FieldDefinition {
		global $wpdb;

		$where = [ 'field_key = %s', 'deleted_at IS NULL' ];
		$args  = [ $field_key ];

		if ( $template_id !== null ) {
			$where[] = 'template_id = %d';
			$args[]  = $template_id;
		} else {
			$where[] = 'template_id IS NULL';
		}

		if ( $job_id !== null ) {
			$where[] = 'job_id = %d';
			$args[]  = $job_id;
		} else {
			$where[] = 'job_id IS NULL';
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE {$where_sql}",
				...$args
			),
			ARRAY_A
		);

		return $row ? FieldDefinition::fromArray( $row ) : null;
	}

	/**
	 * Alle Feld-Definitionen für ein Template laden
	 *
	 * @param int  $template_id Template ID.
	 * @param bool $active_only Nur aktive Felder.
	 * @return array<FieldDefinition>
	 */
	public function findByTemplate( int $template_id, bool $active_only = true ): array {
		global $wpdb;

		$where = [ 'template_id = %d', 'deleted_at IS NULL' ];
		$args  = [ $template_id ];

		if ( $active_only ) {
			$where[] = 'is_active = 1';
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE {$where_sql} ORDER BY position ASC",
				...$args
			),
			ARRAY_A
		);

		return array_map( fn( $row ) => FieldDefinition::fromArray( $row ), $rows ?? [] );
	}

	/**
	 * Alle Feld-Definitionen für einen Job laden
	 *
	 * @param int  $job_id      Job ID.
	 * @param bool $active_only Nur aktive Felder.
	 * @return array<FieldDefinition>
	 */
	public function findByJob( int $job_id, bool $active_only = true ): array {
		global $wpdb;

		$where = [ 'job_id = %d', 'deleted_at IS NULL' ];
		$args  = [ $job_id ];

		if ( $active_only ) {
			$where[] = 'is_active = 1';
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE {$where_sql} ORDER BY position ASC",
				...$args
			),
			ARRAY_A
		);

		return array_map( fn( $row ) => FieldDefinition::fromArray( $row ), $rows ?? [] );
	}

	/**
	 * System-Felder laden (globale Felder ohne Template/Job)
	 *
	 * @param bool $active_only Nur aktive Felder.
	 * @return array<FieldDefinition>
	 */
	public function findSystemFields( bool $active_only = true ): array {
		global $wpdb;

		$where = [ 'is_system = 1', 'template_id IS NULL', 'job_id IS NULL', 'deleted_at IS NULL' ];

		if ( $active_only ) {
			$where[] = 'is_active = 1';
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT * FROM {$this->table} WHERE {$where_sql} ORDER BY position ASC",
			ARRAY_A
		);

		return array_map( fn( $row ) => FieldDefinition::fromArray( $row ), $rows ?? [] );
	}

	/**
	 * Globale Custom Fields laden (nicht-System, ohne Template/Job)
	 *
	 * Diese Felder wurden im Custom Fields Builder erstellt und gelten
	 * für alle Jobs, sofern nicht anders konfiguriert.
	 *
	 * @param bool $active_only Nur aktive Felder.
	 * @return array<FieldDefinition>
	 */
	public function findGlobalCustomFields( bool $active_only = true ): array {
		global $wpdb;

		// Note: Accept both NULL and 0 for template_id/job_id as "global".
		// Frontend may save 0 instead of NULL for new custom fields.
		$where = [
			'is_system = 0',
			'(template_id IS NULL OR template_id = 0)',
			'(job_id IS NULL OR job_id = 0)',
			'deleted_at IS NULL',
		];

		if ( $active_only ) {
			$where[] = 'is_active = 1';
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT * FROM {$this->table} WHERE {$where_sql} ORDER BY position ASC",
			ARRAY_A
		);

		return array_map( fn( $row ) => FieldDefinition::fromArray( $row ), $rows ?? [] );
	}

	/**
	 * Alle Feld-Definitionen laden
	 *
	 * @param array $args Query-Argumente.
	 * @return array<FieldDefinition>
	 */
	public function findAll( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'template_id'    => null,
			'job_id'         => null,
			'include_system' => true,
			'active_only'    => true,
		];

		$args = wp_parse_args( $args, $defaults );

		$where = [ 'deleted_at IS NULL' ];
		$params = [];

		if ( $args['template_id'] !== null ) {
			$where[]  = 'template_id = %d';
			$params[] = $args['template_id'];
		}

		if ( $args['job_id'] !== null ) {
			$where[]  = 'job_id = %d';
			$params[] = $args['job_id'];
		}

		if ( ! $args['include_system'] ) {
			$where[] = 'is_system = 0';
		}

		if ( $args['active_only'] ) {
			$where[] = 'is_active = 1';
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT * FROM {$this->table} WHERE {$where_sql} ORDER BY position ASC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = empty( $params )
			? $wpdb->get_results( $sql, ARRAY_A )
			: $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return array_map( fn( $row ) => FieldDefinition::fromArray( $row ), $rows ?? [] );
	}

	/**
	 * Erlaubte Spalten für die Datenbank
	 *
	 * @var array<string>
	 */
	private const ALLOWED_COLUMNS = [
		'template_id',
		'job_id',
		'field_key',
		'field_type',
		'label',
		'placeholder',
		'description',
		'options',
		'validation',
		'conditional',
		'settings',
		'position',
		'is_required',
		'is_system',
		'is_active',
		'created_at',
		'updated_at',
		'deleted_at',
	];

	/**
	 * Feld-Definition erstellen
	 *
	 * @param array $data Feld-Daten.
	 * @return FieldDefinition|false
	 */
	public function create( array $data ): FieldDefinition|false {
		global $wpdb;

		$now = current_time( 'mysql' );

		$defaults = [
			'position'    => 0,
			'is_required' => 0,
			'is_system'   => 0,
			'is_active'   => 1,
			'created_at'  => $now,
			'updated_at'  => $now,
		];

		$data = wp_parse_args( $data, $defaults );

		// JSON-Felder kodieren.
		foreach ( [ 'options', 'validation', 'conditional', 'settings' ] as $json_field ) {
			if ( isset( $data[ $json_field ] ) && is_array( $data[ $json_field ] ) ) {
				$data[ $json_field ] = wp_json_encode( $data[ $json_field ] );
			}
		}

		// Boolean-Werte in Integer umwandeln.
		foreach ( [ 'is_required', 'is_system', 'is_active' ] as $bool_field ) {
			if ( isset( $data[ $bool_field ] ) ) {
				$data[ $bool_field ] = $data[ $bool_field ] ? 1 : 0;
			}
		}

		// Nur erlaubte Spalten durchlassen.
		$data = $this->filterAllowedColumns( $data );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			$data,
			$this->getFormats( $data )
		);

		if ( $result === false ) {
			return false;
		}

		return $this->find( (int) $wpdb->insert_id );
	}

	/**
	 * Feld-Definition aktualisieren
	 *
	 * @param int   $id   Field ID.
	 * @param array $data Update-Daten.
	 * @return FieldDefinition|false
	 */
	public function update( int $id, array $data ): FieldDefinition|false {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		// JSON-Felder kodieren.
		foreach ( [ 'options', 'validation', 'conditional', 'settings' ] as $json_field ) {
			if ( isset( $data[ $json_field ] ) && is_array( $data[ $json_field ] ) ) {
				$data[ $json_field ] = wp_json_encode( $data[ $json_field ] );
			}
		}

		// Boolean-Werte in Integer umwandeln.
		foreach ( [ 'is_required', 'is_system', 'is_active' ] as $bool_field ) {
			if ( isset( $data[ $bool_field ] ) ) {
				$data[ $bool_field ] = $data[ $bool_field ] ? 1 : 0;
			}
		}

		// Nur erlaubte Spalten durchlassen.
		$data = $this->filterAllowedColumns( $data );

		// Wenn keine Daten zum Aktualisieren übrig sind, abbrechen.
		if ( empty( $data ) || ( count( $data ) === 1 && isset( $data['updated_at'] ) ) ) {
			// Nur updated_at - nichts zu aktualisieren, aber kein Fehler.
			return $this->find( $id );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			$data,
			[ 'id' => $id ],
			$this->getFormats( $data ),
			[ '%d' ]
		);

		if ( $result === false ) {
			// Log detailed error for debugging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'FieldDefinitionRepository::update failed - wpdb error: ' . $wpdb->last_error );
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( 'FieldDefinitionRepository::update data: ' . print_r( $data, true ) );
			}
			return false;
		}

		return $this->find( $id );
	}

	/**
	 * Feld-Definition löschen (Soft Delete)
	 *
	 * @param int $id Field ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
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

		return $result !== false;
	}

	/**
	 * Feld-Definition dauerhaft löschen
	 *
	 * @param int $id Field ID.
	 * @return bool
	 */
	public function forceDelete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			[ 'id' => $id ],
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Felder neu sortieren
	 *
	 * @param array $positions Array von [id => position].
	 * @return bool
	 */
	public function reorder( array $positions ): bool {
		global $wpdb;

		$now = current_time( 'mysql' );

		foreach ( $positions as $id => $position ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$this->table,
				[
					'position'   => (int) $position,
					'updated_at' => $now,
				],
				[ 'id' => (int) $id ],
				[ '%d', '%s' ],
				[ '%d' ]
			);
		}

		return true;
	}

	/**
	 * Prüfen ob Field Key existiert
	 *
	 * @param string   $field_key   Field Key.
	 * @param int|null $template_id Template ID.
	 * @param int|null $job_id      Job ID.
	 * @param int|null $exclude_id  ID zum Ausschließen (für Updates).
	 * @return bool
	 */
	public function fieldKeyExists( string $field_key, ?int $template_id = null, ?int $job_id = null, ?int $exclude_id = null ): bool {
		global $wpdb;

		$where = [ 'field_key = %s', 'deleted_at IS NULL' ];
		$args  = [ $field_key ];

		if ( $template_id !== null ) {
			$where[] = 'template_id = %d';
			$args[]  = $template_id;
		} else {
			$where[] = 'template_id IS NULL';
		}

		if ( $job_id !== null ) {
			$where[] = 'job_id = %d';
			$args[]  = $job_id;
		} else {
			$where[] = 'job_id IS NULL';
		}

		if ( $exclude_id !== null ) {
			$where[] = 'id != %d';
			$args[]  = $exclude_id;
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE {$where_sql}",
				...$args
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Nächste Position ermitteln
	 *
	 * @param int|null $template_id Template ID.
	 * @param int|null $job_id      Job ID.
	 * @return int
	 */
	public function getNextPosition( ?int $template_id = null, ?int $job_id = null ): int {
		global $wpdb;

		$where = [ 'deleted_at IS NULL' ];
		$args  = [];

		if ( $template_id !== null ) {
			$where[] = 'template_id = %d';
			$args[]  = $template_id;
		} else {
			$where[] = 'template_id IS NULL';
		}

		if ( $job_id !== null ) {
			$where[] = 'job_id = %d';
			$args[]  = $job_id;
		} else {
			$where[] = 'job_id IS NULL';
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$max = empty( $args )
			? $wpdb->get_var( "SELECT MAX(position) FROM {$this->table} WHERE {$where_sql}" )
			: $wpdb->get_var( $wpdb->prepare( "SELECT MAX(position) FROM {$this->table} WHERE {$where_sql}", ...$args ) );

		return (int) $max + 1;
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
			// Boolean-Werte als Integer behandeln.
			if ( is_bool( $value ) ) {
				$formats[] = '%d';
			} elseif ( is_int( $value ) ) {
				$formats[] = '%d';
			} elseif ( is_float( $value ) ) {
				$formats[] = '%f';
			} else {
				// Strings und null als String behandeln.
				$formats[] = '%s';
			}
		}

		return $formats;
	}

	/**
	 * Nur erlaubte Spalten durchlassen
	 *
	 * @param array $data Daten.
	 * @return array Gefilterte Daten.
	 */
	private function filterAllowedColumns( array $data ): array {
		return array_intersect_key( $data, array_flip( self::ALLOWED_COLUMNS ) );
	}
}

<?php
/**
 * FormTemplate Repository
 *
 * Data Access Layer für Formular-Templates.
 *
 * @package RecruitingPlaybook\Repositories
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;
use RecruitingPlaybook\Models\FormTemplate;

/**
 * Repository für Formular-Templates
 */
class FormTemplateRepository {

	/**
	 * Tabellen-Name
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Field Definitions Tabellen-Name
	 *
	 * @var string
	 */
	private string $fields_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		$tables             = Schema::getTables();
		$this->table        = $tables['form_templates'];
		$this->fields_table = $tables['field_definitions'];
	}

	/**
	 * Template finden
	 *
	 * @param int $id Template ID.
	 * @return FormTemplate|null
	 */
	public function find( int $id ): ?FormTemplate {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL",
				$id
			),
			ARRAY_A
		);

		return $row ? FormTemplate::fromArray( $row ) : null;
	}

	/**
	 * Standard-Template finden
	 *
	 * @return FormTemplate|null
	 */
	public function findDefault(): ?FormTemplate {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			"SELECT * FROM {$this->table} WHERE is_default = 1 AND deleted_at IS NULL LIMIT 1",
			ARRAY_A
		);

		return $row ? FormTemplate::fromArray( $row ) : null;
	}

	/**
	 * Alle Templates laden
	 *
	 * @param bool $with_usage_count Mit Nutzungsanzahl.
	 * @return array<FormTemplate>
	 */
	public function findAll( bool $with_usage_count = false ): array {
		global $wpdb;

		if ( $with_usage_count ) {
			// Mit Nutzungsanzahl (Jobs die dieses Template verwenden).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				"SELECT t.*,
					(SELECT COUNT(DISTINCT pm.post_id)
					 FROM {$wpdb->postmeta} pm
					 WHERE pm.meta_key = '_rp_form_template_id'
					 AND pm.meta_value = t.id) as usage_count
				FROM {$this->table} t
				WHERE t.deleted_at IS NULL
				ORDER BY t.is_default DESC, t.name ASC",
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				"SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY is_default DESC, name ASC",
				ARRAY_A
			);
		}

		return array_map( fn( $row ) => FormTemplate::fromArray( $row ), $rows );
	}

	/**
	 * Template erstellen
	 *
	 * @param array $data Template-Daten.
	 * @return FormTemplate|false
	 */
	public function create( array $data ): FormTemplate|false {
		global $wpdb;

		$now = current_time( 'mysql' );

		$defaults = [
			'is_default'  => 0,
			'created_by'  => get_current_user_id(),
			'created_at'  => $now,
			'updated_at'  => $now,
		];

		$data = wp_parse_args( $data, $defaults );

		// Settings als JSON kodieren.
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data['settings'] = wp_json_encode( $data['settings'] );
		}

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
	 * Template aktualisieren
	 *
	 * @param int   $id   Template ID.
	 * @param array $data Update-Daten.
	 * @return FormTemplate|false
	 */
	public function update( int $id, array $data ): FormTemplate|false {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		// Settings als JSON kodieren.
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data['settings'] = wp_json_encode( $data['settings'] );
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
			return false;
		}

		return $this->find( $id );
	}

	/**
	 * Template löschen (Soft Delete)
	 *
	 * @param int $id Template ID.
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
				'is_default' => 0, // Kann nicht mehr Default sein.
			],
			[ 'id' => $id ],
			[ '%s', '%s', '%d' ],
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Template als Standard setzen
	 *
	 * @param int $id Template ID.
	 * @return bool
	 */
	public function setDefault( int $id ): bool {
		global $wpdb;

		$now = current_time( 'mysql' );

		// Alle anderen Templates auf nicht-default setzen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$this->table,
			[
				'is_default' => 0,
				'updated_at' => $now,
			],
			[ 'is_default' => 1 ],
			[ '%d', '%s' ],
			[ '%d' ]
		);

		// Dieses Template als Default setzen.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			[
				'is_default' => 1,
				'updated_at' => $now,
			],
			[ 'id' => $id ],
			[ '%d', '%s' ],
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Template duplizieren
	 *
	 * @param int    $id       Template ID.
	 * @param string $new_name Neuer Name.
	 * @return FormTemplate|false
	 */
	public function duplicate( int $id, string $new_name = '' ): FormTemplate|false {
		global $wpdb;

		$original = $this->find( $id );
		if ( ! $original ) {
			return false;
		}

		$now = current_time( 'mysql' );

		// Neuen Namen generieren.
		if ( empty( $new_name ) ) {
			$new_name = sprintf(
				/* translators: %s: Original template name */
				__( '%s (Kopie)', 'recruiting-playbook' ),
				$original->getName()
			);
		}

		// Template kopieren.
		$template_data = [
			'name'        => $new_name,
			'description' => $original->getDescription(),
			'is_default'  => 0, // Kopie ist nie Standard.
			'settings'    => $original->getSettings() ? wp_json_encode( $original->getSettings() ) : null,
			'created_by'  => get_current_user_id(),
			'created_at'  => $now,
			'updated_at'  => $now,
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			$template_data,
			$this->getFormats( $template_data )
		);

		if ( $result === false ) {
			return false;
		}

		$new_template_id = (int) $wpdb->insert_id;

		// Felder des Templates kopieren.
		$this->duplicateFields( $id, $new_template_id );

		return $this->find( $new_template_id );
	}

	/**
	 * Felder eines Templates kopieren
	 *
	 * @param int $source_template_id Quell-Template ID.
	 * @param int $target_template_id Ziel-Template ID.
	 */
	private function duplicateFields( int $source_template_id, int $target_template_id ): void {
		global $wpdb;

		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$fields = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->fields_table}
				WHERE template_id = %d AND deleted_at IS NULL",
				$source_template_id
			),
			ARRAY_A
		);

		foreach ( $fields as $field ) {
			unset( $field['id'] );
			$field['template_id'] = $target_template_id;
			$field['created_at']  = $now;
			$field['updated_at']  = $now;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$this->fields_table,
				$field,
				$this->getFormats( $field )
			);
		}
	}

	/**
	 * Nutzungsanzahl ermitteln (Jobs die dieses Template verwenden)
	 *
	 * @param int $id Template ID.
	 * @return int
	 */
	public function getUsageCount( int $id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
				WHERE meta_key = '_rp_form_template_id' AND meta_value = %s",
				(string) $id
			)
		);

		return (int) $count;
	}

	/**
	 * Prüfen ob Name existiert
	 *
	 * @param string   $name       Template Name.
	 * @param int|null $exclude_id ID zum Ausschließen (für Updates).
	 * @return bool
	 */
	public function nameExists( string $name, ?int $exclude_id = null ): bool {
		global $wpdb;

		$where = [ 'name = %s', 'deleted_at IS NULL' ];
		$args  = [ $name ];

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
				$formats[] = null;
			} else {
				$formats[] = '%s';
			}
		}

		return $formats;
	}
}

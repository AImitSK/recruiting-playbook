<?php
/**
 * Email Template Repository - Datenzugriff für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für E-Mail-Template-Operationen
 */
class EmailTemplateRepository {

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
		$this->table = Schema::getTables()['email_templates'];
	}

	/**
	 * Template erstellen
	 *
	 * @param array $data Template-Daten.
	 * @return int|false Insert ID oder false bei Fehler.
	 */
	public function create( array $data ): int|false {
		global $wpdb;

		$now = current_time( 'mysql' );

		$defaults = [
			'category'   => 'custom',
			'is_active'  => 1,
			'is_default' => 0,
			'is_system'  => 0,
			'created_by' => get_current_user_id() ?: null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		$data = wp_parse_args( $data, $defaults );

		// Slug generieren wenn nicht vorhanden.
		if ( empty( $data['slug'] ) && ! empty( $data['name'] ) ) {
			$data['slug'] = $this->generateSlug( $data['name'] );
		}

		// Variables als JSON.
		if ( isset( $data['variables'] ) && is_array( $data['variables'] ) ) {
			$data['variables'] = wp_json_encode( $data['variables'] );
		}

		// Settings als JSON.
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$data['settings'] = wp_json_encode( $data['settings'] );
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
	 * Template finden
	 *
	 * @param int $id Template-ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$template = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d AND deleted_at IS NULL",
				$id
			),
			ARRAY_A
		);

		if ( ! $template ) {
			return null;
		}

		return $this->enrichTemplate( $template );
	}

	/**
	 * Template per Slug finden
	 *
	 * @param string $slug Template-Slug.
	 * @return array|null
	 */
	public function findBySlug( string $slug ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$template = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE slug = %s AND deleted_at IS NULL",
				$slug
			),
			ARRAY_A
		);

		if ( ! $template ) {
			return null;
		}

		return $this->enrichTemplate( $template );
	}

	/**
	 * Standard-Template für Kategorie finden
	 *
	 * @param string $category Kategorie.
	 * @return array|null
	 */
	public function findDefault( string $category ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$template = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE category = %s AND is_default = 1 AND is_active = 1 AND deleted_at IS NULL
				LIMIT 1",
				$category
			),
			ARRAY_A
		);

		if ( ! $template ) {
			return null;
		}

		return $this->enrichTemplate( $template );
	}

	/**
	 * Alle Templates laden
	 *
	 * @param array $args Query-Argumente.
	 * @return array
	 */
	public function getList( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'category'  => null,
			'is_active' => null,
			'search'    => null,
			'orderby'   => 'name',
			'order'     => 'ASC',
		];

		$args = wp_parse_args( $args, $defaults );

		// Base Query.
		$where  = [ 'deleted_at IS NULL' ];
		$values = [];

		// Category Filter.
		if ( $args['category'] ) {
			$where[]  = 'category = %s';
			$values[] = $args['category'];
		}

		// Active Filter.
		if ( null !== $args['is_active'] ) {
			$where[]  = 'is_active = %d';
			$values[] = $args['is_active'] ? 1 : 0;
		}

		// Search.
		if ( $args['search'] ) {
			$search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(name LIKE %s OR subject LIKE %s)';
			$values[] = $search;
			$values[] = $search;
		}

		$where_clause = implode( ' AND ', $where );

		// Order.
		$orderby = in_array( $args['orderby'], [ 'name', 'category', 'created_at', 'updated_at' ], true )
			? $args['orderby']
			: 'name';
		$order = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

		// Query.
		$sql = "SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY {$orderby} {$order}";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$templates = $wpdb->get_results( $wpdb->prepare( $sql, ...$values ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$templates = $wpdb->get_results( $sql, ARRAY_A );
		}

		return array_map( [ $this, 'enrichTemplate' ], $templates ?: [] );
	}

	/**
	 * Templates nach Kategorie laden
	 *
	 * @param string $category Kategorie.
	 * @return array
	 */
	public function findByCategory( string $category ): array {
		return $this->getList( [ 'category' => $category, 'is_active' => true ] );
	}

	/**
	 * Template aktualisieren
	 *
	 * @param int   $id   Template-ID.
	 * @param array $data Update-Daten.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		// Variables als JSON.
		if ( isset( $data['variables'] ) && is_array( $data['variables'] ) ) {
			$data['variables'] = wp_json_encode( $data['variables'] );
		}

		// Settings als JSON.
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

		return false !== $result;
	}

	/**
	 * Template soft-löschen
	 *
	 * @param int $id Template-ID.
	 * @return bool
	 */
	public function softDelete( int $id ): bool {
		global $wpdb;

		// System-Templates können nicht gelöscht werden.
		$template = $this->find( $id );
		if ( $template && $template['is_system'] ) {
			return false;
		}

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
	 * Template duplizieren
	 *
	 * @param int    $id       Template-ID.
	 * @param string $new_name Neuer Name.
	 * @return int|false Neue Template-ID oder false.
	 */
	public function duplicate( int $id, string $new_name = '' ): int|false {
		$template = $this->find( $id );

		if ( ! $template ) {
			return false;
		}

		// Neuen Namen generieren.
		if ( empty( $new_name ) ) {
			$new_name = $template['name'] . ' ' . __( '(Copy)', 'recruiting-playbook' );
		}

		return $this->create( [
			'name'      => $new_name,
			'subject'   => $template['subject'],
			'body_html' => $template['body_html'],
			'body_text' => $template['body_text'],
			'category'  => $template['category'],
			'is_active' => 1,
			'is_default' => 0,
			'is_system' => 0,
			'variables' => $template['variables'],
			'settings'  => $template['settings'],
		] );
	}

	/**
	 * Prüfen ob Slug existiert
	 *
	 * @param string   $slug       Slug.
	 * @param int|null $exclude_id Auszuschließende ID.
	 * @return bool
	 */
	public function slugExists( string $slug, ?int $exclude_id = null ): bool {
		global $wpdb;

		$sql    = "SELECT COUNT(*) FROM {$this->table} WHERE slug = %s AND deleted_at IS NULL";
		$values = [ $slug ];

		if ( $exclude_id ) {
			$sql     .= ' AND id != %d';
			$values[] = $exclude_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$values ) ) > 0;
	}

	/**
	 * Slug aus Name generieren
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private function generateSlug( string $name ): string {
		$slug = sanitize_title( $name );
		$base = $slug;
		$i    = 1;

		while ( $this->slugExists( $slug ) ) {
			$slug = $base . '-' . $i;
			++$i;
		}

		return $slug;
	}

	/**
	 * Template mit zusätzlichen Daten anreichern
	 *
	 * @param array $template Template-Daten.
	 * @return array
	 */
	private function enrichTemplate( array $template ): array {
		// JSON parsen.
		if ( ! empty( $template['variables'] ) ) {
			$template['variables'] = json_decode( $template['variables'], true ) ?: [];
		} else {
			$template['variables'] = [];
		}

		if ( ! empty( $template['settings'] ) ) {
			$template['settings'] = json_decode( $template['settings'], true ) ?: [];
		} else {
			$template['settings'] = [];
		}

		// Ersteller laden.
		if ( $template['created_by'] ) {
			$user = get_userdata( (int) $template['created_by'] );
			$template['created_by_user'] = $user ? [
				'id'   => $user->ID,
				'name' => $user->display_name,
			] : null;
		} else {
			$template['created_by_user'] = null;
		}

		// Berechtigungen.
		$is_admin = current_user_can( 'manage_options' );
		$template['can_edit']   = $is_admin;
		$template['can_delete'] = $is_admin && ! $template['is_system'];

		// Typen konvertieren.
		$template['id']         = (int) $template['id'];
		$template['is_active']  = (bool) $template['is_active'];
		$template['is_default'] = (bool) $template['is_default'];
		$template['is_system']  = (bool) $template['is_system'];
		$template['created_by'] = $template['created_by'] ? (int) $template['created_by'] : null;

		return $template;
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

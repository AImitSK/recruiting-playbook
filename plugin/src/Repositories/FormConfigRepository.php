<?php
/**
 * FormConfig Repository
 *
 * Data Access Layer für die Step-basierte Formular-Konfiguration.
 * Verwaltet Draft und Published Versionen.
 *
 * @package RecruitingPlaybook\Repositories
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für Formular-Konfiguration
 */
class FormConfigRepository {

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
		$this->table = Schema::getTables()['form_config'];
	}

	/**
	 * Draft-Konfiguration laden
	 *
	 * @return array|null
	 */
	public function getDraft(): ?array {
		return $this->getByType( 'draft' );
	}

	/**
	 * Published-Konfiguration laden
	 *
	 * @return array|null
	 */
	public function getPublished(): ?array {
		return $this->getByType( 'published' );
	}

	/**
	 * Konfiguration nach Typ laden
	 *
	 * @param string $type Config type ('draft' or 'published').
	 * @return array|null
	 */
	private function getByType( string $type ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE config_type = %s",
				$type
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return [
			'id'          => (int) $row['id'],
			'config_type' => $row['config_type'],
			'config_data' => json_decode( $row['config_data'], true ),
			'version'     => (int) $row['version'],
			'created_by'  => $row['created_by'] ? (int) $row['created_by'] : null,
			'created_at'  => $row['created_at'],
			'updated_at'  => $row['updated_at'],
		];
	}

	/**
	 * Draft speichern
	 *
	 * @param array    $config_data Konfigurationsdaten.
	 * @param int|null $user_id     Benutzer-ID.
	 * @return bool
	 */
	public function saveDraft( array $config_data, ?int $user_id = null ): bool {
		global $wpdb;

		$now = current_time( 'mysql' );

		// Prüfen ob Draft existiert.
		$existing = $this->getDraft();

		$data = [
			'config_data' => wp_json_encode( $config_data, JSON_UNESCAPED_UNICODE ),
			'updated_at'  => $now,
		];

		if ( $user_id ) {
			$data['created_by'] = $user_id;
		}

		if ( $existing ) {
			// Update.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$this->table,
				$data,
				[ 'config_type' => 'draft' ],
				$this->getFormats( $data ),
				[ '%s' ]
			);
		} else {
			// Insert.
			$data['config_type'] = 'draft';
			$data['version']     = 1;
			$data['created_at']  = $now;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert(
				$this->table,
				$data,
				$this->getFormats( $data )
			);
		}

		return $result !== false;
	}

	/**
	 * Draft veröffentlichen
	 *
	 * Kopiert den Draft in die Published-Version und erhöht die Version.
	 *
	 * @param int|null $user_id Benutzer-ID.
	 * @return bool
	 */
	public function publish( ?int $user_id = null ): bool {
		global $wpdb;

		$draft = $this->getDraft();

		if ( ! $draft ) {
			return false;
		}

		$now         = current_time( 'mysql' );
		$new_version = ( $this->getPublishedVersion() ?? 0 ) + 1;

		// Update config_data with new version number.
		$config_data            = $draft['config_data'];
		$config_data['version'] = $new_version;

		$data = [
			'config_data' => wp_json_encode( $config_data, JSON_UNESCAPED_UNICODE ),
			'version'     => $new_version,
			'updated_at'  => $now,
		];

		if ( $user_id ) {
			$data['created_by'] = $user_id;
		}

		// Prüfen ob Published existiert.
		$published = $this->getPublished();

		if ( $published ) {
			// Update.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$this->table,
				$data,
				[ 'config_type' => 'published' ],
				$this->getFormats( $data ),
				[ '%s' ]
			);
		} else {
			// Insert.
			$data['config_type'] = 'published';
			$data['created_at']  = $now;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert(
				$this->table,
				$data,
				$this->getFormats( $data )
			);
		}

		if ( $result === false ) {
			return false;
		}

		// Draft-Version auch aktualisieren.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$this->table,
			[
				'config_data' => wp_json_encode( $config_data, JSON_UNESCAPED_UNICODE ),
				'version'     => $new_version,
				'updated_at'  => $now,
			],
			[ 'config_type' => 'draft' ],
			[ '%s', '%d', '%s' ],
			[ '%s' ]
		);

		return true;
	}

	/**
	 * Draft verwerfen
	 *
	 * Setzt den Draft auf den Stand der Published-Version zurück.
	 *
	 * @return bool
	 */
	public function discardDraft(): bool {
		global $wpdb;

		$published = $this->getPublished();

		if ( ! $published ) {
			return false;
		}

		$now = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			[
				'config_data' => wp_json_encode( $published['config_data'], JSON_UNESCAPED_UNICODE ),
				'version'     => $published['version'],
				'updated_at'  => $now,
			],
			[ 'config_type' => 'draft' ],
			[ '%s', '%d', '%s' ],
			[ '%s' ]
		);

		return $result !== false;
	}

	/**
	 * Prüfen ob unveröffentlichte Änderungen existieren
	 *
	 * @return bool
	 */
	public function hasUnpublishedChanges(): bool {
		$draft     = $this->getDraft();
		$published = $this->getPublished();

		// Kein Draft vorhanden → keine Änderungen.
		if ( ! $draft ) {
			return false;
		}

		// Draft vorhanden aber kein Published → es gibt Änderungen!
		if ( ! $published ) {
			return true;
		}

		// Beide vorhanden → JSON-Vergleich der Konfigurationsdaten.
		$draft_json     = wp_json_encode( $draft['config_data'] );
		$published_json = wp_json_encode( $published['config_data'] );

		return $draft_json !== $published_json;
	}

	/**
	 * Published-Version abrufen
	 *
	 * @return int|null
	 */
	public function getPublishedVersion(): ?int {
		$published = $this->getPublished();

		return $published ? $published['version'] : null;
	}

	/**
	 * Konfiguration auf Standard zurücksetzen
	 *
	 * Löscht Draft und Published, sodass beim nächsten Laden
	 * die Default-Konfiguration verwendet wird.
	 *
	 * @return bool
	 */
	public function resetToDefault(): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE config_type IN (%s, %s)",
				'draft',
				'published'
			)
		);

		return $result !== false;
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

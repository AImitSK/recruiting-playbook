<?php
/**
 * Signature Repository - Datenzugriff für E-Mail-Signaturen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für Signatur-Operationen
 */
class SignatureRepository {

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
		$this->table = Schema::getTables()['signatures'];
	}

	/**
	 * Signatur erstellen
	 *
	 * @param array $data Signatur-Daten.
	 * @return int|false Insert ID oder false bei Fehler.
	 */
	public function create( array $data ): int|false {
		global $wpdb;

		$now = current_time( 'mysql' );

		$defaults = [
			'user_id'         => get_current_user_id() ?: null,
			'is_default'      => 0,
			'include_company' => 1,
			'created_at'      => $now,
			'updated_at'      => $now,
		];

		$data = wp_parse_args( $data, $defaults );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			$data,
			$this->getFormats( $data )
		);

		if ( false === $result ) {
			return false;
		}

		$insert_id = (int) $wpdb->insert_id;

		// Wenn is_default = 1, andere Signaturen des Users auf 0 setzen.
		if ( ! empty( $data['is_default'] ) ) {
			$this->clearOtherDefaults( $insert_id, $data['user_id'] );
		}

		return $insert_id;
	}

	/**
	 * Signatur finden
	 *
	 * @param int $id Signatur-ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$signature = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! $signature ) {
			return null;
		}

		return $this->enrichSignature( $signature );
	}

	/**
	 * Alle Signaturen eines Users laden
	 *
	 * @param int $user_id User-ID.
	 * @return array
	 */
	public function findByUser( int $user_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$signatures = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE user_id = %d
				ORDER BY is_default DESC, name ASC",
				$user_id
			),
			ARRAY_A
		);

		return array_map( [ $this, 'enrichSignature' ], $signatures ?: [] );
	}

	/**
	 * Standard-Signatur für User finden
	 *
	 * @param int $user_id User-ID.
	 * @return array|null
	 */
	public function findDefaultForUser( int $user_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$signature = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE user_id = %d AND is_default = 1
				LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		if ( ! $signature ) {
			return null;
		}

		return $this->enrichSignature( $signature );
	}

	/**
	 * Firmen-Signatur finden (user_id = NULL)
	 *
	 * @return array|null
	 */
	public function findCompanyDefault(): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$signature = $wpdb->get_row(
			"SELECT * FROM {$this->table}
			WHERE user_id IS NULL AND is_default = 1
			LIMIT 1",
			ARRAY_A
		);

		if ( ! $signature ) {
			// Fallback: Irgendeine Firmen-Signatur.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$signature = $wpdb->get_row(
				"SELECT * FROM {$this->table}
				WHERE user_id IS NULL
				ORDER BY created_at ASC
				LIMIT 1",
				ARRAY_A
			);
		}

		if ( ! $signature ) {
			return null;
		}

		return $this->enrichSignature( $signature );
	}

	/**
	 * Alle Firmen-Signaturen laden (für Admin)
	 *
	 * @return array
	 */
	public function findCompanySignatures(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$signatures = $wpdb->get_results(
			"SELECT * FROM {$this->table}
			WHERE user_id IS NULL
			ORDER BY is_default DESC, name ASC",
			ARRAY_A
		);

		return array_map( [ $this, 'enrichSignature' ], $signatures ?: [] );
	}

	/**
	 * Signatur aktualisieren
	 *
	 * @param int   $id   Signatur-ID.
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

		if ( false === $result ) {
			return false;
		}

		// Wenn is_default = 1, andere Signaturen auf 0 setzen.
		if ( ! empty( $data['is_default'] ) ) {
			$signature = $this->find( $id );
			if ( $signature ) {
				$this->clearOtherDefaults( $id, $signature['user_id'] );
			}
		}

		return true;
	}

	/**
	 * Signatur löschen
	 *
	 * @param int $id Signatur-ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		// Firmen-Default-Signatur kann nicht gelöscht werden.
		$signature = $this->find( $id );
		if ( $signature && null === $signature['user_id'] && $signature['is_default'] ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			[ 'id' => $id ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Signatur als Standard setzen
	 *
	 * @param int      $id      Signatur-ID.
	 * @param int|null $user_id User-ID (null für Firmen-Signatur).
	 * @return bool
	 */
	public function setDefault( int $id, ?int $user_id ): bool {
		// Erst alle anderen auf 0 setzen.
		$this->clearOtherDefaults( $id, $user_id );

		// Dann diese auf 1 setzen.
		return $this->update( $id, [ 'is_default' => 1 ] );
	}

	/**
	 * Alle anderen Default-Flags für User zurücksetzen
	 *
	 * @param int      $exclude_id Auszuschließende Signatur-ID.
	 * @param int|null $user_id    User-ID (null für Firmen-Signatur).
	 */
	private function clearOtherDefaults( int $exclude_id, ?int $user_id ): void {
		global $wpdb;

		if ( null === $user_id ) {
			// Firmen-Signaturen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$this->table}
					SET is_default = 0, updated_at = %s
					WHERE user_id IS NULL AND id != %d",
					current_time( 'mysql' ),
					$exclude_id
				)
			);
		} else {
			// User-Signaturen.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$this->table}
					SET is_default = 0, updated_at = %s
					WHERE user_id = %d AND id != %d",
					current_time( 'mysql' ),
					$user_id,
					$exclude_id
				)
			);
		}
	}

	/**
	 * Signatur mit zusätzlichen Daten anreichern
	 *
	 * @param array $signature Signatur-Daten.
	 * @return array
	 */
	private function enrichSignature( array $signature ): array {
		// User-Daten laden wenn User-Signatur.
		if ( $signature['user_id'] ) {
			$user = get_userdata( (int) $signature['user_id'] );
			$signature['user'] = $user ? [
				'id'   => $user->ID,
				'name' => $user->display_name,
			] : null;
		} else {
			$signature['user'] = null;
		}

		// Berechtigungen.
		$current_user = get_current_user_id();
		$is_admin     = current_user_can( 'manage_options' );
		$is_owner     = (int) $signature['user_id'] === $current_user;

		$signature['can_edit']   = $is_admin || $is_owner;
		$signature['can_delete'] = ( $is_admin || $is_owner ) && ! ( null === $signature['user_id'] && $signature['is_default'] );

		// Typ: 'personal' oder 'company'.
		$signature['type'] = null === $signature['user_id'] ? 'company' : 'personal';

		// Typen konvertieren.
		$signature['id']              = (int) $signature['id'];
		$signature['user_id']         = $signature['user_id'] ? (int) $signature['user_id'] : null;
		$signature['is_default']      = (bool) $signature['is_default'];
		$signature['include_company'] = (bool) $signature['include_company'];

		return $signature;
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
				$formats[] = null; // NULL bleibt NULL.
			} else {
				$formats[] = '%s';
			}
		}

		return $formats;
	}
}

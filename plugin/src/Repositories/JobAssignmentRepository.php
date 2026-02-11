<?php
/**
 * Job Assignment Repository - Datenzugriff für Stellen-Zuweisungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für User-Job-Zuweisungen
 */
class JobAssignmentRepository {

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
		$this->table = Schema::getTables()['job_assignments'];
	}

	/**
	 * Zuweisung erstellen
	 *
	 * @param array $data Zuweisungs-Daten (user_id, job_id, assigned_by).
	 * @return int|false Insert ID oder false bei Fehler.
	 */
	public function create( array $data ): int|false {
		global $wpdb;

		$defaults = [
			'assigned_at' => current_time( 'mysql' ),
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
	 * Prüfen ob Zuweisung existiert
	 *
	 * @param int $user_id User-ID.
	 * @param int $job_id  Job-ID.
	 * @return bool
	 */
	public function exists( int $user_id, int $job_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d AND job_id = %d",
				$user_id,
				$job_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Zuweisung nach ID finden
	 *
	 * @param int $id Zuweisungs-ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		return $row ? $this->castTypes( $row ) : null;
	}

	/**
	 * Zuweisungen für einen User laden
	 *
	 * @param int $user_id User-ID.
	 * @return array
	 */
	public function findByUser( int $user_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY assigned_at DESC",
				$user_id
			),
			ARRAY_A
		);

		return array_map( [ $this, 'castTypes' ], $rows ?? [] );
	}

	/**
	 * Zuweisungen für einen Job laden
	 *
	 * @param int $job_id Job-ID.
	 * @return array
	 */
	public function findByJob( int $job_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE job_id = %d ORDER BY assigned_at DESC",
				$job_id
			),
			ARRAY_A
		);

		return array_map( [ $this, 'castTypes' ], $rows ?? [] );
	}

	/**
	 * Job-IDs für einen User laden
	 *
	 * @param int $user_id User-ID.
	 * @return array<int> Liste von Job-IDs.
	 */
	public function getJobIdsByUser( int $user_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT job_id FROM {$this->table} WHERE user_id = %d",
				$user_id
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * Zuweisung löschen (per user_id + job_id)
	 *
	 * @param int $user_id User-ID.
	 * @param int $job_id  Job-ID.
	 * @return bool
	 */
	public function delete( int $user_id, int $job_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			[
				'user_id' => $user_id,
				'job_id'  => $job_id,
			],
			[ '%d', '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Alle Zuweisungen eines Users löschen
	 *
	 * @param int $user_id User-ID.
	 * @return int Anzahl gelöschter Zeilen.
	 */
	public function deleteByUser( int $user_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			[ 'user_id' => $user_id ],
			[ '%d' ]
		);

		return false !== $result ? (int) $result : 0;
	}

	/**
	 * Alle Zuweisungen eines Jobs löschen
	 *
	 * @param int $job_id Job-ID.
	 * @return int Anzahl gelöschter Zeilen.
	 */
	public function deleteByJob( int $job_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			[ 'job_id' => $job_id ],
			[ '%d' ]
		);

		return false !== $result ? (int) $result : 0;
	}

	/**
	 * Anzahl Zuweisungen für einen Job
	 *
	 * @param int $job_id Job-ID.
	 * @return int
	 */
	public function countByJob( int $job_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE job_id = %d",
				$job_id
			)
		);
	}

	/**
	 * Typen casten
	 *
	 * @param array $row Datenbank-Zeile.
	 * @return array
	 */
	private function castTypes( array $row ): array {
		$row['id']          = (int) $row['id'];
		$row['user_id']     = (int) $row['user_id'];
		$row['job_id']      = (int) $row['job_id'];
		$row['assigned_by'] = (int) $row['assigned_by'];

		return $row;
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

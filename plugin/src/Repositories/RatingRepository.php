<?php
/**
 * Rating Repository - Datenzugriff für Bewertungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Repositories;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;

/**
 * Repository für Bewertungs-Operationen
 */
class RatingRepository {

	/**
	 * Tabellen-Name
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Erlaubte Kategorien
	 *
	 * @var array
	 */
	public const CATEGORIES = [ 'overall', 'skills', 'culture_fit', 'experience' ];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->table = Schema::getTables()['ratings'];
	}

	/**
	 * Bewertung erstellen oder aktualisieren (Upsert)
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param int    $user_id        User-ID.
	 * @param int    $rating         Bewertung (1-5).
	 * @param string $category       Kategorie.
	 * @return int|false Rating ID oder false bei Fehler.
	 */
	public function upsert( int $application_id, int $user_id, int $rating, string $category = 'overall' ): int|false {
		global $wpdb;

		// Kategorie validieren.
		if ( ! in_array( $category, self::CATEGORIES, true ) ) {
			$category = 'overall';
		}

		// Rating validieren (1-5).
		$rating = max( 1, min( 5, $rating ) );

		// Prüfen ob bereits existiert.
		$existing = $this->findByUserAndApplication( $user_id, $application_id, $category );

		if ( $existing ) {
			// Update.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$this->table,
				[
					'rating'     => $rating,
					'updated_at' => current_time( 'mysql' ),
				],
				[ 'id' => $existing['id'] ],
				[ '%d', '%s' ],
				[ '%d' ]
			);

			return false !== $result ? (int) $existing['id'] : false;
		}

		// Insert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			[
				'application_id' => $application_id,
				'user_id'        => $user_id,
				'rating'         => $rating,
				'category'       => $category,
				'created_at'     => current_time( 'mysql' ),
				'updated_at'     => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s' ]
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Bewertung finden
	 *
	 * @param int $id Rating-ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rating = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		return $rating ?: null;
	}

	/**
	 * Bewertung für User und Bewerbung finden
	 *
	 * @param int    $user_id        User-ID.
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $category       Kategorie.
	 * @return array|null
	 */
	public function findByUserAndApplication( int $user_id, int $application_id, string $category = 'overall' ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rating = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE user_id = %d AND application_id = %d AND category = %s",
				$user_id,
				$application_id,
				$category
			),
			ARRAY_A
		);

		return $rating ?: null;
	}

	/**
	 * Alle Bewertungen für Bewerbung laden
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	public function findByApplication( int $application_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ratings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE application_id = %d
				ORDER BY category, created_at DESC",
				$application_id
			),
			ARRAY_A
		);

		return array_map( [ $this, 'enrichRating' ], $ratings );
	}

	/**
	 * Bewertungen des aktuellen Users für Bewerbung
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @param int $user_id        User-ID.
	 * @return array Assoziatives Array [category => rating].
	 */
	public function getUserRatings( int $application_id, int $user_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ratings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, rating FROM {$this->table}
				WHERE application_id = %d AND user_id = %d",
				$application_id,
				$user_id
			),
			ARRAY_A
		);

		$result = [];
		foreach ( $ratings as $rating ) {
			$result[ $rating['category'] ] = (int) $rating['rating'];
		}

		return $result;
	}

	/**
	 * Bewertungs-Zusammenfassung für Bewerbung
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	public function getSummary( int $application_id ): array {
		global $wpdb;

		// Gesamt-Durchschnitt (nur overall).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$overall = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT AVG(rating) as average, COUNT(*) as count
				FROM {$this->table}
				WHERE application_id = %d AND category = 'overall'",
				$application_id
			),
			ARRAY_A
		);

		// Verteilung (nur overall).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$distribution_raw = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT rating, COUNT(*) as count
				FROM {$this->table}
				WHERE application_id = %d AND category = 'overall'
				GROUP BY rating",
				$application_id
			),
			ARRAY_A
		);

		$distribution = [
			'1' => 0,
			'2' => 0,
			'3' => 0,
			'4' => 0,
			'5' => 0,
		];
		foreach ( $distribution_raw as $row ) {
			$distribution[ $row['rating'] ] = (int) $row['count'];
		}

		// Pro Kategorie.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_category_raw = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, AVG(rating) as average, COUNT(*) as count
				FROM {$this->table}
				WHERE application_id = %d
				GROUP BY category",
				$application_id
			),
			ARRAY_A
		);

		$by_category = [];
		foreach ( $by_category_raw as $row ) {
			$by_category[ $row['category'] ] = [
				'average' => round( (float) $row['average'], 1 ),
				'count'   => (int) $row['count'],
			];
		}

		return [
			'average'      => $overall['average'] ? round( (float) $overall['average'], 1 ) : 0,
			'count'        => (int) $overall['count'],
			'distribution' => $distribution,
			'by_category'  => $by_category,
		];
	}

	/**
	 * Durchschnittliche Bewertung für Bewerbung (nur overall)
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return float
	 */
	public function getAverageRating( int $application_id ): float {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$average = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(rating) FROM {$this->table}
				WHERE application_id = %d AND category = 'overall'",
				$application_id
			)
		);

		return $average ? round( (float) $average, 1 ) : 0.0;
	}

	/**
	 * Bewertung löschen
	 *
	 * @param int $id Rating-ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			[ 'id' => $id ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Bewertung mit User-Daten anreichern
	 *
	 * @param array $rating Rating-Daten.
	 * @return array
	 */
	private function enrichRating( array $rating ): array {
		$user = get_userdata( (int) $rating['user_id'] );

		$rating['user'] = $user ? [
			'id'     => $user->ID,
			'name'   => $user->display_name,
			'avatar' => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
		] : null;

		// Typen konvertieren.
		$rating['id']             = (int) $rating['id'];
		$rating['application_id'] = (int) $rating['application_id'];
		$rating['user_id']        = (int) $rating['user_id'];
		$rating['rating']         = (int) $rating['rating'];

		return $rating;
	}
}

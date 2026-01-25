<?php
/**
 * Rating Service - Geschäftslogik für Bewertungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\RatingRepository;
use WP_Error;

/**
 * Service für Bewertungs-Operationen
 */
class RatingService {

	/**
	 * Rating Repository
	 *
	 * @var RatingRepository
	 */
	private RatingRepository $repository;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new RatingRepository();
	}

	/**
	 * Bewertung abgeben oder aktualisieren
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param int    $rating         Bewertung (1-5).
	 * @param string $category       Kategorie.
	 * @return array|WP_Error
	 */
	public function rate( int $application_id, int $rating, string $category = 'overall' ): array|WP_Error {
		// Bewerbung prüfen.
		$application_service = new ApplicationService();
		$application         = $application_service->get( $application_id );

		if ( ! $application ) {
			return new WP_Error(
				'not_found',
				__( 'Bewerbung nicht gefunden', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Rating validieren.
		$rating = max( 1, min( 5, $rating ) );

		// Kategorie validieren.
		if ( ! in_array( $category, RatingRepository::CATEGORIES, true ) ) {
			$category = 'overall';
		}

		$user_id = get_current_user_id();

		// Prüfen ob bereits existiert (für Activity Log).
		$existing = $this->repository->findByUserAndApplication( $user_id, $application_id, $category );

		$rating_id = $this->repository->upsert( $application_id, $user_id, $rating, $category );

		if ( ! $rating_id ) {
			return new WP_Error(
				'rating_failed',
				__( 'Bewertung konnte nicht gespeichert werden', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		// Activity Log.
		if ( $existing ) {
			$this->logActivity(
				$application_id,
				'rating_updated',
				__( 'Bewertung geändert', 'recruiting-playbook' ),
				[
					'from'     => (int) $existing['rating'],
					'to'       => $rating,
					'category' => $category,
				]
			);
		} else {
			$this->logActivity(
				$application_id,
				'rating_added',
				__( 'Bewertung abgegeben', 'recruiting-playbook' ),
				[
					'rating'   => $rating,
					'category' => $category,
				]
			);
		}

		return $this->getSummary( $application_id );
	}

	/**
	 * Bewertungs-Zusammenfassung laden
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	public function getSummary( int $application_id ): array {
		$summary = $this->repository->getSummary( $application_id );

		// User-Ratings hinzufügen.
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$summary['user_rating'] = $this->repository->getUserRatings( $application_id, $user_id );
		}

		return $summary;
	}

	/**
	 * Alle Bewertungen für Bewerbung laden
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	public function getForApplication( int $application_id ): array {
		return $this->repository->findByApplication( $application_id );
	}

	/**
	 * Durchschnittliche Bewertung für Bewerbung
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return float
	 */
	public function getAverageRating( int $application_id ): float {
		return $this->repository->getAverageRating( $application_id );
	}

	/**
	 * User-Bewertungen für Bewerbung
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @param int $user_id        User-ID.
	 * @return array
	 */
	public function getUserRatings( int $application_id, int $user_id ): array {
		return $this->repository->getUserRatings( $application_id, $user_id );
	}

	/**
	 * Bewertung löschen
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $category       Kategorie.
	 * @return bool|WP_Error
	 */
	public function deleteRating( int $application_id, string $category = 'overall' ): bool|WP_Error {
		$user_id  = get_current_user_id();
		$existing = $this->repository->findByUserAndApplication( $user_id, $application_id, $category );

		if ( ! $existing ) {
			return new WP_Error(
				'not_found',
				__( 'Bewertung nicht gefunden', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		return $this->repository->delete( (int) $existing['id'] );
	}

	/**
	 * Activity-Log Eintrag erstellen
	 *
	 * @param int    $application_id Bewerbungs-ID.
	 * @param string $action         Aktion.
	 * @param string $message        Nachricht.
	 * @param array  $meta           Meta-Daten.
	 */
	private function logActivity( int $application_id, string $action, string $message, array $meta = [] ): void {
		global $wpdb;

		$table        = $wpdb->prefix . 'rp_activity_log';
		$current_user = wp_get_current_user();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'object_type' => 'application',
				'object_id'   => $application_id,
				'action'      => $action,
				'user_id'     => get_current_user_id() ?: null,
				'user_name'   => $current_user->ID ? $current_user->display_name : null,
				'message'     => $message,
				'meta'        => ! empty( $meta ) ? wp_json_encode( $meta ) : null,
				'ip_address'  => $this->getClientIp(),
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
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

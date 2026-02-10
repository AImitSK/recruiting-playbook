<?php
/**
 * Capability Service - Berechtigungsprüfungen mit Stellen-Zuweisung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Database\Schema;
use RecruitingPlaybook\Repositories\JobAssignmentRepository;

/**
 * Service für Berechtigungsprüfungen
 *
 * Berücksichtigt Stellen-Zuweisungen: Recruiter und Hiring Manager
 * sehen nur Bewerbungen für ihre zugewiesenen Stellen.
 * Administratoren haben immer Vollzugriff.
 */
class CapabilityService {

	/**
	 * Job Assignment Repository
	 *
	 * @var JobAssignmentRepository
	 */
	private JobAssignmentRepository $repository;

	/**
	 * Constructor
	 *
	 * @param JobAssignmentRepository|null $repository Optional für Testing.
	 */
	public function __construct( ?JobAssignmentRepository $repository = null ) {
		$this->repository = $repository ?? new JobAssignmentRepository();
	}

	/**
	 * Prüfen ob User eine Capability hat
	 *
	 * @param int    $user_id    User-ID.
	 * @param string $capability Capability-Name.
	 * @return bool
	 */
	public function userCan( int $user_id, string $capability ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		return user_can( $user, $capability );
	}

	/**
	 * Prüfen ob User Zugriff auf eine Bewerbung hat
	 *
	 * Admins haben immer Zugriff. Andere User benötigen:
	 * 1. Die Capability rp_view_applications
	 * 2. Eine Zuweisung zum Job der Bewerbung
	 *
	 * @param int $user_id        User-ID.
	 * @param int $application_id Bewerbungs-ID.
	 * @return bool
	 */
	public function canAccessApplication( int $user_id, int $application_id ): bool {
		// Admin hat immer Zugriff.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Basis-Capability prüfen.
		if ( ! user_can( $user_id, 'rp_view_applications' ) ) {
			return false;
		}

		// Job-ID der Bewerbung holen.
		$job_id = $this->getJobIdForApplication( $application_id );
		if ( ! $job_id ) {
			return false;
		}

		// Prüfen ob User dem Job zugewiesen ist.
		return $this->isAssignedToJob( $user_id, $job_id );
	}

	/**
	 * Prüfen ob User einer Stelle zugewiesen ist
	 *
	 * Admins sind implizit allen Stellen zugewiesen.
	 *
	 * @param int $user_id User-ID.
	 * @param int $job_id  Job-ID.
	 * @return bool
	 */
	public function isAssignedToJob( int $user_id, int $job_id ): bool {
		// Admin ist implizit allen Stellen zugewiesen.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		return $this->repository->exists( $user_id, $job_id );
	}

	/**
	 * Zugewiesene Job-IDs eines Users abrufen
	 *
	 * Admins erhalten alle veröffentlichten Jobs.
	 *
	 * @param int $user_id User-ID.
	 * @return array<int> Liste von Job-IDs.
	 */
	public function getAssignedJobIds( int $user_id ): array {
		// Admin sieht alle Jobs.
		if ( user_can( $user_id, 'manage_options' ) ) {
			$jobs = get_posts( [
				'post_type'      => 'job_listing',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			] );

			return array_map( 'intval', $jobs );
		}

		return $this->repository->getJobIdsByUser( $user_id );
	}

	/**
	 * Job-ID für eine Bewerbung ermitteln
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return int|null Job-ID oder null.
	 */
	private function getJobIdForApplication( int $application_id ): ?int {
		global $wpdb;

		$table = Schema::getTables()['applications'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$job_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT job_id FROM {$table} WHERE id = %d",
				$application_id
			)
		);

		return $job_id ? (int) $job_id : null;
	}
}

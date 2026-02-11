<?php
/**
 * Job Assignment Service - Geschäftslogik für Stellen-Zuweisungen
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\JobAssignmentRepository;
use RecruitingPlaybook\Traits\HasIpAddress;
use WP_Error;

/**
 * Service für Stellen-Zuweisungen
 */
class JobAssignmentService {

	use HasIpAddress;

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
	 * User einer Stelle zuweisen
	 *
	 * @param int $user_id     User-ID.
	 * @param int $job_id      Job-ID.
	 * @param int $assigned_by User-ID des Zuweisenden.
	 * @return array|WP_Error Zuweisungs-Daten oder Fehler.
	 */
	public function assign( int $user_id, int $job_id, int $assigned_by ): array|WP_Error {
		// User validieren.
		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'invalid_user',
				__( 'User not found.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Job validieren.
		if ( 'job_listing' !== get_post_type( $job_id ) ) {
			return new WP_Error(
				'invalid_job',
				__( 'Invalid job.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		// Bereits zugewiesen?
		if ( $this->repository->exists( $user_id, $job_id ) ) {
			return new WP_Error(
				'already_assigned',
				__( 'User is already assigned to this job.', 'recruiting-playbook' ),
				[ 'status' => 409 ]
			);
		}

		$id = $this->repository->create( [
			'user_id'     => $user_id,
			'job_id'      => $job_id,
			'assigned_by' => $assigned_by,
		] );

		if ( ! $id ) {
			return new WP_Error(
				'create_failed',
				__( 'Assignment could not be created.', 'recruiting-playbook' ),
				[ 'status' => 500 ]
			);
		}

		// Activity Log.
		$this->logActivity( $job_id, $user_id, 'job_assigned' );

		return $this->repository->find( $id );
	}

	/**
	 * Zuweisung entfernen
	 *
	 * @param int $user_id User-ID.
	 * @param int $job_id  Job-ID.
	 * @return true|WP_Error
	 */
	public function unassign( int $user_id, int $job_id ): true|WP_Error {
		if ( ! $this->repository->exists( $user_id, $job_id ) ) {
			return new WP_Error(
				'not_found',
				__( 'Assignment not found.', 'recruiting-playbook' ),
				[ 'status' => 404 ]
			);
		}

		$this->repository->delete( $user_id, $job_id );

		// Activity Log.
		$this->logActivity( $job_id, $user_id, 'job_unassigned' );

		return true;
	}

	/**
	 * Mehrere Stellen auf einmal zuweisen
	 *
	 * @param int   $user_id     User-ID.
	 * @param array $job_ids     Liste von Job-IDs.
	 * @param int   $assigned_by User-ID des Zuweisenden.
	 * @return array Ergebnis pro Job.
	 */
	public function bulkAssign( int $user_id, array $job_ids, int $assigned_by ): array {
		$results = [];

		foreach ( $job_ids as $job_id ) {
			$job_id = (int) $job_id;
			$result = $this->assign( $user_id, $job_id, $assigned_by );

			$results[] = [
				'job_id'   => $job_id,
				'assigned' => ! is_wp_error( $result ),
				'error'    => is_wp_error( $result ) ? $result->get_error_message() : null,
			];
		}

		return $results;
	}

	/**
	 * Alle zugewiesenen User für einen Job abrufen
	 *
	 * @param int $job_id Job-ID.
	 * @return array Liste der User mit Detailinformationen.
	 */
	public function getAssignedUsers( int $job_id ): array {
		$assignments = $this->repository->findByJob( $job_id );

		return array_filter( array_map( function ( array $assignment ): ?array {
			$user = get_userdata( $assignment['user_id'] );
			if ( ! $user ) {
				return null;
			}

			return [
				'id'          => $user->ID,
				'name'        => $user->display_name,
				'email'       => $user->user_email,
				'role'        => $this->getUserRoleLabel( $user->ID ),
				'avatar'      => get_avatar_url( $user->ID, [ 'size' => 32 ] ),
				'assigned_at' => $assignment['assigned_at'],
				'assigned_by' => $assignment['assigned_by'],
			];
		}, $assignments ) );
	}

	/**
	 * Alle zugewiesenen Jobs eines Users abrufen
	 *
	 * @param int $user_id User-ID.
	 * @return array Liste der Jobs mit Detailinformationen.
	 */
	public function getAssignedJobs( int $user_id ): array {
		$assignments = $this->repository->findByUser( $user_id );

		return array_filter( array_map( function ( array $assignment ): ?array {
			$job = get_post( $assignment['job_id'] );
			if ( ! $job ) {
				return null;
			}

			return [
				'id'          => $job->ID,
				'title'       => $job->post_title,
				'status'      => $job->post_status,
				'assigned_at' => $assignment['assigned_at'],
			];
		}, $assignments ) );
	}

	/**
	 * Anzahl Zuweisungen für einen Job
	 *
	 * @param int $job_id Job-ID.
	 * @return int
	 */
	public function countForJob( int $job_id ): int {
		return $this->repository->countByJob( $job_id );
	}

	/**
	 * Alle Zuweisungen eines Users löschen
	 *
	 * Wird z.B. beim Löschen eines Users aufgerufen.
	 *
	 * @param int $user_id User-ID.
	 * @return int Anzahl gelöschter Zuweisungen.
	 */
	public function removeAllForUser( int $user_id ): int {
		return $this->repository->deleteByUser( $user_id );
	}

	/**
	 * Alle Zuweisungen eines Jobs löschen
	 *
	 * Wird z.B. beim Löschen einer Stelle aufgerufen.
	 *
	 * @param int $job_id Job-ID.
	 * @return int Anzahl gelöschter Zuweisungen.
	 */
	public function removeAllForJob( int $job_id ): int {
		return $this->repository->deleteByJob( $job_id );
	}

	/**
	 * Rollen-Label für einen User ermitteln
	 *
	 * @param int $user_id User-ID.
	 * @return string Rollen-Slug.
	 */
	private function getUserRoleLabel( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return 'unknown';
		}

		if ( in_array( 'administrator', $user->roles, true ) ) {
			return 'administrator';
		}
		if ( in_array( 'rp_recruiter', $user->roles, true ) ) {
			return 'recruiter';
		}
		if ( in_array( 'rp_hiring_manager', $user->roles, true ) ) {
			return 'hiring_manager';
		}

		return $user->roles[0] ?? 'subscriber';
	}

	/**
	 * Activity-Log Eintrag für Stellen-Zuweisung erstellen
	 *
	 * @param int    $job_id      Job-ID.
	 * @param int    $target_user Betroffener User.
	 * @param string $action      Aktion (job_assigned / job_unassigned).
	 */
	private function logActivity( int $job_id, int $target_user, string $action ): void {
		global $wpdb;

		$table        = $wpdb->prefix . 'rp_activity_log';
		$current_user = wp_get_current_user();
		$target        = get_userdata( $target_user );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			[
				'object_type' => 'job_listing',
				'object_id'   => $job_id,
				'action'      => $action,
				'user_id'     => get_current_user_id() ?: null,
				'user_name'   => $current_user->ID ? $current_user->display_name : null,
				'message'     => sprintf(
					/* translators: %s: user display name */
					'job_assigned' === $action
						? __( '%s assigned to job', 'recruiting-playbook' )
						: __( '%s removed from job', 'recruiting-playbook' ),
					$target ? $target->display_name : __( 'Unknown', 'recruiting-playbook' )
				),
				'meta'        => wp_json_encode( [
					'target_user_id' => $target_user,
					'job_id'         => $job_id,
				] ),
				'ip_address'  => $this->getAnonymizedClientIp(),
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}
}

<?php
/**
 * Time-to-Hire Service - Geschäftslogik für Time-to-Hire Berechnung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\StatsRepository;

/**
 * Service für Time-to-Hire Berechnungen
 */
class TimeToHireService {

	/**
	 * Stats Repository
	 *
	 * @var StatsRepository
	 */
	private StatsRepository $repository;

	/**
	 * Constructor
	 *
	 * @param StatsRepository|null $repository Repository-Instanz (für Tests).
	 */
	public function __construct( ?StatsRepository $repository = null ) {
		$this->repository = $repository ?? new StatsRepository();
	}

	/**
	 * Time-to-Hire berechnen
	 *
	 * @param array $date_range Zeitraum mit 'from' und 'to'.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array
	 */
	public function calculate( array $date_range, ?int $job_id = null ): array {
		$hired_applications = $this->repository->getHiredApplications( $date_range, $job_id );

		if ( empty( $hired_applications ) ) {
			return $this->emptyResult();
		}

		$days = [];
		$by_stage = [
			'new_to_screening'       => [],
			'screening_to_interview' => [],
			'interview_to_offer'     => [],
			'offer_to_hired'         => [],
		];

		foreach ( $hired_applications as $app ) {
			// Gesamtzeit.
			$total_days = (int) $app['days_to_hire'];
			$days[] = $total_days;

			// Zeit pro Stage (aus Activity Log).
			$stages = $this->getStageTransitions( (int) $app['id'] );
			foreach ( $stages as $stage => $stage_days ) {
				if ( isset( $by_stage[ $stage ] ) && $stage_days > 0 ) {
					$by_stage[ $stage ][] = $stage_days;
				}
			}
		}

		return [
			'overall'  => [
				'average_days' => (int) round( array_sum( $days ) / count( $days ) ),
				'median_days'  => $this->calculateMedian( $days ),
				'min_days'     => min( $days ),
				'max_days'     => max( $days ),
				'total_hires'  => count( $days ),
			],
			'by_stage' => $this->calculateStageAverages( $by_stage ),
			'trend'    => $this->calculateTrend( $hired_applications ),
			'by_job'   => $this->getByJob( $hired_applications ),
		];
	}

	/**
	 * Leeres Ergebnis
	 *
	 * @return array
	 */
	private function emptyResult(): array {
		return [
			'overall' => [
				'average_days' => null,
				'median_days'  => null,
				'min_days'     => null,
				'max_days'     => null,
				'total_hires'  => 0,
			],
			'by_stage' => [
				'new_to_screening'       => [ 'average_days' => null, 'median_days' => null ],
				'screening_to_interview' => [ 'average_days' => null, 'median_days' => null ],
				'interview_to_offer'     => [ 'average_days' => null, 'median_days' => null ],
				'offer_to_hired'         => [ 'average_days' => null, 'median_days' => null ],
			],
			'trend'    => [],
			'by_job'   => [],
		];
	}

	/**
	 * Stage-Übergänge aus Activity Log
	 *
	 * @param int $application_id Bewerbungs-ID.
	 * @return array
	 */
	private function getStageTransitions( int $application_id ): array {
		$transitions = $this->repository->getStatusTransitions( $application_id );

		$result = [
			'new_to_screening'       => 0,
			'screening_to_interview' => 0,
			'interview_to_offer'     => 0,
			'offer_to_hired'         => 0,
		];

		$stage_mapping = [
			'new_screening'       => 'new_to_screening',
			'screening_interview' => 'screening_to_interview',
			'interview_offer'     => 'interview_to_offer',
			'offer_hired'         => 'offer_to_hired',
		];

		$prev_date = null;
		$prev_status = null;

		foreach ( $transitions as $transition ) {
			$from = $transition['old_value'] ?? '';
			$to = $transition['new_value'] ?? '';
			$date = $transition['created_at'];

			$key = "{$from}_{$to}";
			if ( isset( $stage_mapping[ $key ] ) && $prev_date ) {
				$days = $this->calculateDaysBetween( $prev_date, $date );
				$result[ $stage_mapping[ $key ] ] = $days;
			}

			$prev_date = $date;
			$prev_status = $to;
		}

		return $result;
	}

	/**
	 * Durchschnitte pro Stage berechnen
	 *
	 * @param array $by_stage Stage-Daten.
	 * @return array
	 */
	private function calculateStageAverages( array $by_stage ): array {
		$result = [];

		foreach ( $by_stage as $stage => $days_array ) {
			if ( empty( $days_array ) ) {
				$result[ $stage ] = [
					'average_days' => null,
					'median_days'  => null,
				];
			} else {
				$result[ $stage ] = [
					'average_days' => (int) round( array_sum( $days_array ) / count( $days_array ) ),
					'median_days'  => $this->calculateMedian( $days_array ),
				];
			}
		}

		return $result;
	}

	/**
	 * Trend über Monate berechnen
	 *
	 * @param array $hired_applications Einstellungen.
	 * @return array
	 */
	private function calculateTrend( array $hired_applications ): array {
		$by_month = [];

		foreach ( $hired_applications as $app ) {
			$month = substr( $app['hired_at'], 0, 7 ); // YYYY-MM
			if ( ! isset( $by_month[ $month ] ) ) {
				$by_month[ $month ] = [
					'days'  => [],
					'hires' => 0,
				];
			}
			$by_month[ $month ]['days'][] = (int) $app['days_to_hire'];
			$by_month[ $month ]['hires']++;
		}

		$result = [];
		foreach ( $by_month as $month => $data ) {
			$result[] = [
				'month'        => $month,
				'average_days' => (int) round( array_sum( $data['days'] ) / count( $data['days'] ) ),
				'hires'        => $data['hires'],
			];
		}

		// Nach Monat sortieren.
		usort( $result, fn( $a, $b ) => strcmp( $a['month'], $b['month'] ) );

		return $result;
	}

	/**
	 * Time-to-Hire nach Job gruppieren
	 *
	 * @param array $hired_applications Einstellungen.
	 * @return array
	 */
	private function getByJob( array $hired_applications ): array {
		$by_job = [];

		foreach ( $hired_applications as $app ) {
			$job_id = (int) $app['job_id'];
			if ( ! isset( $by_job[ $job_id ] ) ) {
				$by_job[ $job_id ] = [
					'days'  => [],
					'hires' => 0,
				];
			}
			$by_job[ $job_id ]['days'][] = (int) $app['days_to_hire'];
			$by_job[ $job_id ]['hires']++;
		}

		$result = [];
		foreach ( $by_job as $job_id => $data ) {
			$job = get_post( $job_id );
			$result[] = [
				'job_id'       => $job_id,
				'job_title'    => $job ? $job->post_title : __( 'Unknown', 'recruiting-playbook' ),
				'average_days' => (int) round( array_sum( $data['days'] ) / count( $data['days'] ) ),
				'hires'        => $data['hires'],
			];
		}

		// Nach Anzahl Hires sortieren.
		usort( $result, fn( $a, $b ) => $b['hires'] - $a['hires'] );

		return $result;
	}

	/**
	 * Median berechnen
	 *
	 * @param array $values Werte.
	 * @return int
	 */
	private function calculateMedian( array $values ): int {
		if ( empty( $values ) ) {
			return 0;
		}

		sort( $values );
		$count = count( $values );
		$middle = (int) floor( $count / 2 );

		if ( $count % 2 === 0 ) {
			return (int) ( ( $values[ $middle - 1 ] + $values[ $middle ] ) / 2 );
		}

		return (int) $values[ $middle ];
	}

	/**
	 * Tage zwischen zwei Daten berechnen
	 *
	 * @param string $from Start-Datum.
	 * @param string $to End-Datum.
	 * @return int
	 */
	private function calculateDaysBetween( string $from, string $to ): int {
		$from_date = new \DateTime( $from );
		$to_date = new \DateTime( $to );

		return (int) $from_date->diff( $to_date )->days;
	}
}

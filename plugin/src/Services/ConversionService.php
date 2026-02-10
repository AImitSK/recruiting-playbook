<?php
/**
 * Conversion Service - Geschäftslogik für Conversion-Rate Berechnung
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\StatsRepository;

/**
 * Service für Conversion-Rate Berechnungen
 */
class ConversionService {

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
	 * Conversion-Rate berechnen
	 *
	 * @param array $date_range Zeitraum mit 'from' und 'to'.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array
	 */
	public function calculate( array $date_range, ?int $job_id = null ): array {
		$views = $this->repository->countJobViews( $date_range, $job_id );
		$applications = $this->repository->countApplications( $date_range, $job_id );

		$conversion_rate = $views > 0
			? round( ( $applications / $views ) * 100, 2 )
			: 0;

		return [
			'overall'   => [
				'views'           => $views,
				'applications'    => $applications,
				'conversion_rate' => $conversion_rate,
			],
			'funnel'    => $this->calculateFunnel( $date_range, $job_id ),
			'by_source' => $this->getConversionBySource( $date_range, $job_id ),
			'trend'     => $this->calculateTrend( $date_range, $job_id ),
			'top_converting_jobs' => $this->getTopConvertingJobs( $date_range, 5 ),
		];
	}

	/**
	 * Conversion Funnel berechnen
	 *
	 * @param array $date_range Zeitraum.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array
	 */
	private function calculateFunnel( array $date_range, ?int $job_id ): array {
		// Event-basierte Metriken aus Activity Log.
		$job_list_views = $this->repository->countEvents( 'job_list_viewed', $date_range );
		$job_detail_views = $this->repository->countJobViews( $date_range, $job_id );
		$form_starts = $this->repository->countEvents( 'application_form_started', $date_range, $job_id );
		$form_completions = $this->repository->countApplications( $date_range, $job_id );

		return [
			'job_list_views'    => $job_list_views,
			'job_detail_views'  => $job_detail_views,
			'form_starts'       => $form_starts,
			'form_completions'  => $form_completions,
			'rates'             => [
				'list_to_detail'         => $this->safePercentage( $job_list_views, $job_detail_views ),
				'detail_to_form_start'   => $this->safePercentage( $job_detail_views, $form_starts ),
				'form_start_to_complete' => $this->safePercentage( $form_starts, $form_completions ),
				'overall'                => $this->safePercentage( $job_list_views, $form_completions ),
			],
		];
	}

	/**
	 * Conversion nach Quelle
	 *
	 * @param array $date_range Zeitraum.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array
	 */
	private function getConversionBySource( array $date_range, ?int $job_id ): array {
		$by_source = $this->repository->getApplicationsBySource( $date_range );

		$result = [];
		foreach ( $by_source as $source => $applications ) {
			// Views pro Quelle aus Activity Log (falls verfügbar).
			// Fallback: Views anteilig basierend auf Bewerbungen.
			$total_views = $this->repository->countJobViews( $date_range, $job_id );
			$total_apps = array_sum( $by_source );

			$estimated_views = $total_apps > 0
				? (int) round( ( $applications / $total_apps ) * $total_views )
				: 0;

			$result[] = [
				'source'          => $source,
				'views'           => $estimated_views,
				'applications'    => $applications,
				'conversion_rate' => $this->safePercentage( $estimated_views, $applications ),
			];
		}

		// Nach Conversion-Rate sortieren.
		usort( $result, fn( $a, $b ) => $b['conversion_rate'] <=> $a['conversion_rate'] );

		return $result;
	}

	/**
	 * Conversion-Trend berechnen
	 *
	 * @param array $date_range Zeitraum.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array
	 */
	private function calculateTrend( array $date_range, ?int $job_id ): array {
		$timeline = $this->repository->getApplicationsTimeline( $date_range, 'day', $job_id );

		$result = [];
		foreach ( $timeline as $row ) {
			// Views pro Tag (falls verfügbar).
			$day_range = [
				'from' => $row['date'] . ' 00:00:00',
				'to'   => $row['date'] . ' 23:59:59',
			];
			$views = $this->repository->countJobViews( $day_range, $job_id );
			$applications = (int) $row['total'];

			$result[] = [
				'date'            => $row['date'],
				'views'           => $views,
				'applications'    => $applications,
				'conversion_rate' => $this->safePercentage( $views, $applications ),
			];
		}

		return $result;
	}

	/**
	 * Top-konvertierende Jobs
	 *
	 * @param array $date_range Zeitraum.
	 * @param int $limit Anzahl Ergebnisse.
	 * @return array
	 */
	private function getTopConvertingJobs( array $date_range, int $limit ): array {
		$jobs = $this->repository->getTopJobsByApplications( 50, $date_range );

		$result = [];
		foreach ( $jobs as $job ) {
			$views = $this->repository->countJobViews( $date_range, (int) $job['id'] );
			$applications = (int) $job['applications'];

			if ( $views > 10 ) { // Mindestens 10 Views für aussagekräftige Rate.
				$result[] = [
					'job_id'          => (int) $job['id'],
					'title'           => $job['title'],
					'views'           => $views,
					'applications'    => $applications,
					'conversion_rate' => $this->safePercentage( $views, $applications ),
				];
			}
		}

		// Nach Conversion-Rate sortieren.
		usort( $result, fn( $a, $b ) => $b['conversion_rate'] <=> $a['conversion_rate'] );

		return array_slice( $result, 0, $limit );
	}

	/**
	 * Sichere Prozentberechnung
	 *
	 * @param int $total Gesamtzahl.
	 * @param int $part Teilmenge.
	 * @return float
	 */
	private function safePercentage( int $total, int $part ): float {
		return $total > 0 ? round( ( $part / $total ) * 100, 2 ) : 0;
	}

	/**
	 * Vergleich mit Vorperiode
	 *
	 * @param array $date_range Aktueller Zeitraum.
	 * @param int|null $job_id Optional: Filter nach Stelle.
	 * @return array
	 */
	public function getComparison( array $date_range, ?int $job_id = null ): array {
		// Vorherige Periode berechnen.
		$from = strtotime( $date_range['from'] ?? '-30 days' );
		$to = strtotime( $date_range['to'] ?? 'now' );
		$duration = $to - $from;

		$previous_range = [
			'from' => gmdate( 'Y-m-d H:i:s', $from - $duration - 1 ),
			'to'   => gmdate( 'Y-m-d H:i:s', $from - 1 ),
		];

		$current = $this->calculate( $date_range, $job_id );
		$previous = $this->calculate( $previous_range, $job_id );

		$current_rate = $current['overall']['conversion_rate'];
		$previous_rate = $previous['overall']['conversion_rate'];

		$change_percent = $previous_rate > 0
			? round( ( ( $current_rate - $previous_rate ) / $previous_rate ) * 100, 1 )
			: ( $current_rate > 0 ? 100.0 : 0.0 );

		return [
			'previous_period' => [
				'conversion_rate' => $previous_rate,
				'change_percent'  => $change_percent,
			],
		];
	}
}

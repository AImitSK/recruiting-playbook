<?php
/**
 * Stats Service - Geschäftslogik für Statistiken
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\StatsRepository;

/**
 * Service für Statistik-Operationen
 */
class StatsService {

	/**
	 * Stats Repository
	 *
	 * @var StatsRepository
	 */
	private StatsRepository $repository;

	/**
	 * Cache TTL in Sekunden (5 Minuten)
	 */
	private const CACHE_TTL = 300;

	/**
	 * Cache-Gruppe
	 */
	private const CACHE_GROUP = 'rp_stats';

	/**
	 * Constructor
	 *
	 * @param StatsRepository|null $repository Repository-Instanz (für Tests).
	 */
	public function __construct( ?StatsRepository $repository = null ) {
		$this->repository = $repository ?? new StatsRepository();
	}

	/**
	 * Dashboard-Übersicht abrufen
	 *
	 * @param string $period Zeitraum (today, 7days, 30days, 90days, year, all).
	 * @return array
	 */
	public function getOverview( string $period = '30days' ): array {
		$cache_key = "overview_{$period}";
		$cached = $this->getCache( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$date_range = $this->getDateRange( $period );
		$previous_range = $this->getPreviousPeriod( $date_range, $period );

		$data = [
			'applications'    => $this->getApplicationSummary( $date_range, $previous_range ),
			'jobs'            => $this->getJobSummary(),
			'quick_stats'     => $this->getQuickStats(),
			'time_to_hire'    => $this->getTimeToHireSummary( $date_range ),
			'conversion_rate' => $this->getConversionSummary( $date_range ),
			'top_jobs'        => $this->repository->getTopJobsByApplications( 5, $date_range ),
			'period'          => $period,
			'generated_at'    => current_time( 'c' ),
		];

		$this->setCache( $cache_key, $data );

		return $data;
	}

	/**
	 * Bewerbungs-Zusammenfassung
	 *
	 * @param array $date_range Aktueller Zeitraum.
	 * @param array $previous_range Vorheriger Zeitraum.
	 * @return array
	 */
	private function getApplicationSummary( array $date_range, array $previous_range ): array {
		$current = $this->repository->countApplicationsByStatus( $date_range );
		$previous = $this->repository->countApplicationsByStatus( $previous_range );

		$total_current = array_sum( $current );
		$total_previous = array_sum( $previous );

		$in_progress = ( $current['screening'] ?? 0 )
			+ ( $current['interview'] ?? 0 )
			+ ( $current['offer'] ?? 0 );

		return [
			'total'         => $total_current,
			'new'           => $current['new'] ?? 0,
			'in_progress'   => $in_progress,
			'hired'         => $current['hired'] ?? 0,
			'rejected'      => $current['rejected'] ?? 0,
			'withdrawn'     => $current['withdrawn'] ?? 0,
			'period_change' => $this->calculatePercentageChange( $total_previous, $total_current ),
		];
	}

	/**
	 * Job-Zusammenfassung
	 *
	 * @return array
	 */
	private function getJobSummary(): array {
		$by_status = $this->repository->countJobsByStatus();

		return [
			'total'   => array_sum( $by_status ),
			'active'  => $by_status['publish'] ?? 0,
			'draft'   => $by_status['draft'] ?? 0,
			'pending' => $by_status['pending'] ?? 0,
			'expired' => $by_status['expired'] ?? 0,
		];
	}

	/**
	 * Schnellstatistiken (heute, Woche, Monat)
	 *
	 * @return array
	 */
	private function getQuickStats(): array {
		$today = $this->getDateRange( 'today' );
		$week = $this->getDateRange( '7days' );
		$month = $this->getDateRange( '30days' );

		return [
			'today'      => $this->repository->countApplications( $today ),
			'this_week'  => $this->repository->countApplications( $week ),
			'this_month' => $this->repository->countApplications( $month ),
		];
	}

	/**
	 * Time-to-Hire Zusammenfassung
	 *
	 * @param array $date_range Zeitraum.
	 * @return array
	 */
	private function getTimeToHireSummary( array $date_range ): array {
		$hired = $this->repository->getHiredApplications( $date_range );

		if ( empty( $hired ) ) {
			return [
				'average_days'  => null,
				'median_days'   => null,
				'total_hires'   => 0,
				'period_change' => 0,
			];
		}

		$days = array_map( fn( $h ) => (int) $h['days_to_hire'], $hired );

		return [
			'average_days'  => (int) round( array_sum( $days ) / count( $days ) ),
			'median_days'   => $this->calculateMedian( $days ),
			'total_hires'   => count( $hired ),
			'period_change' => 0, // TODO: Vergleich mit Vorperiode
		];
	}

	/**
	 * Conversion-Rate Zusammenfassung
	 *
	 * @param array $date_range Zeitraum.
	 * @return array
	 */
	private function getConversionSummary( array $date_range ): array {
		$views = $this->repository->countJobViews( $date_range );
		$applications = $this->repository->countApplications( $date_range );

		$rate = $views > 0
			? round( ( $applications / $views ) * 100, 2 )
			: 0;

		return [
			'rate'          => $rate,
			'views'         => $views,
			'applications'  => $applications,
			'period_change' => 0, // TODO: Vergleich mit Vorperiode
		];
	}

	/**
	 * Detaillierte Bewerbungs-Statistiken
	 *
	 * @param array $args Filter-Argumente.
	 * @return array
	 */
	public function getApplicationStats( array $args = [] ): array {
		$date_range = [
			'from' => $args['date_from'] ?? null,
			'to'   => $args['date_to'] ?? null,
		];

		$group_by = $args['group_by'] ?? 'day';
		$job_id = $args['job_id'] ?? null;

		return [
			'summary'         => [
				'total'     => $this->repository->countApplications( $date_range, $job_id ),
				'by_status' => $this->repository->countApplicationsByStatus( $date_range, $job_id ),
			],
			'timeline'        => $this->repository->getApplicationsTimeline( $date_range, $group_by, $job_id ),
			'by_source'       => $this->repository->getApplicationsBySource( $date_range ),
			'filters_applied' => [
				'date_from' => $date_range['from'],
				'date_to'   => $date_range['to'],
				'group_by'  => $group_by,
				'job_id'    => $job_id,
			],
		];
	}

	/**
	 * Statistiken pro Stelle
	 *
	 * @param array $args Filter-Argumente.
	 * @return array
	 */
	public function getJobStats( array $args = [] ): array {
		$date_range = [
			'from' => $args['date_from'] ?? null,
			'to'   => $args['date_to'] ?? null,
		];

		$sort_by = $args['sort_by'] ?? 'applications';
		$sort_order = $args['sort_order'] ?? 'desc';
		$per_page = min( (int) ( $args['per_page'] ?? 20 ), 100 );
		$page = max( (int) ( $args['page'] ?? 1 ), 1 );
		$offset = ( $page - 1 ) * $per_page;

		$jobs = $this->repository->getJobStats( $date_range, $sort_by, $sort_order, $per_page, $offset );
		$total = $this->repository->countJobs();

		return [
			'jobs'       => $jobs,
			'total'      => $total,
			'pages'      => (int) ceil( $total / $per_page ),
			'aggregated' => $this->getAggregatedJobStats( $date_range ),
		];
	}

	/**
	 * Aggregierte Job-Statistiken
	 *
	 * @param array $date_range Zeitraum.
	 * @return array
	 */
	private function getAggregatedJobStats( array $date_range ): array {
		$total_apps = $this->repository->countApplications( $date_range );
		$total_views = $this->repository->countJobViews( $date_range );
		$hired = $this->repository->getHiredApplications( $date_range );

		$avg_time_to_hire = null;
		if ( ! empty( $hired ) ) {
			$days = array_map( fn( $h ) => (int) $h['days_to_hire'], $hired );
			$avg_time_to_hire = (int) round( array_sum( $days ) / count( $days ) );
		}

		return [
			'total_applications' => $total_apps,
			'total_views'        => $total_views,
			'avg_conversion_rate' => $total_views > 0
				? round( ( $total_apps / $total_views ) * 100, 2 )
				: 0,
			'avg_time_to_hire'   => $avg_time_to_hire,
		];
	}

	/**
	 * Trend-Daten für Charts
	 *
	 * @param array $args Filter-Argumente.
	 * @return array
	 */
	public function getTrends( array $args = [] ): array {
		$date_range = [
			'from' => $args['date_from'] ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) ) . ' 00:00:00',
			'to'   => $args['date_to'] ?? gmdate( 'Y-m-d' ) . ' 23:59:59',
		];

		$granularity = $args['granularity'] ?? 'day';
		$metrics = $args['metrics'] ?? [ 'applications', 'hires' ];

		$timeline = $this->repository->getApplicationsTimeline( $date_range, $granularity );

		// Daten formatieren.
		$data = array_map(
			function ( $row ) use ( $metrics ) {
				$item = [ 'date' => $row['date'] ];

				if ( in_array( 'applications', $metrics, true ) ) {
					$item['applications'] = (int) $row['total'];
				}
				if ( in_array( 'hires', $metrics, true ) ) {
					$item['hires'] = (int) $row['hired'];
				}
				if ( in_array( 'rejections', $metrics, true ) ) {
					$item['rejections'] = (int) $row['rejected'];
				}

				return $item;
			},
			$timeline
		);

		return [
			'data'        => $data,
			'summary'     => $this->calculateTrendSummary( $timeline, $metrics ),
			'granularity' => $granularity,
			'date_from'   => $date_range['from'],
			'date_to'     => $date_range['to'],
		];
	}

	/**
	 * Trend-Zusammenfassung berechnen
	 *
	 * @param array $timeline Timeline-Daten.
	 * @param array $metrics Metriken.
	 * @return array
	 */
	private function calculateTrendSummary( array $timeline, array $metrics ): array {
		$summary = [];
		$days = count( $timeline );

		if ( in_array( 'applications', $metrics, true ) ) {
			$totals = array_map( fn( $r ) => (int) $r['total'], $timeline );
			$total = array_sum( $totals );
			$avg = $days > 0 ? round( $total / $days, 1 ) : 0;

			// Trend berechnen (erste vs. zweite Hälfte).
			$mid = (int) floor( $days / 2 );
			$first_half = array_sum( array_slice( $totals, 0, $mid ) );
			$second_half = array_sum( array_slice( $totals, $mid ) );
			$trend_percent = $this->calculatePercentageChange( $first_half, $second_half );

			$summary['applications'] = [
				'total'         => $total,
				'avg_per_day'   => $avg,
				'trend'         => $trend_percent > 5 ? 'up' : ( $trend_percent < -5 ? 'down' : 'stable' ),
				'trend_percent' => $trend_percent,
			];
		}

		if ( in_array( 'hires', $metrics, true ) ) {
			$totals = array_map( fn( $r ) => (int) $r['hired'], $timeline );
			$total = array_sum( $totals );

			$summary['hires'] = [
				'total'       => $total,
				'avg_per_day' => $days > 0 ? round( $total / $days, 1 ) : 0,
			];
		}

		return $summary;
	}

	/**
	 * Prozentuale Änderung berechnen
	 *
	 * @param int|float $previous Vorheriger Wert.
	 * @param int|float $current Aktueller Wert.
	 * @return float
	 */
	private function calculatePercentageChange( int|float $previous, int|float $current ): float {
		if ( $previous == 0 ) {
			return $current > 0 ? 100.0 : 0.0;
		}

		return round( ( ( $current - $previous ) / $previous ) * 100, 1 );
	}

	/**
	 * Median berechnen
	 *
	 * @param array $values Werte.
	 * @return int
	 */
	private function calculateMedian( array $values ): int {
		sort( $values );
		$count = count( $values );
		$middle = (int) floor( $count / 2 );

		if ( $count % 2 === 0 ) {
			return (int) ( ( $values[ $middle - 1 ] + $values[ $middle ] ) / 2 );
		}

		return (int) $values[ $middle ];
	}

	/**
	 * Datumsbereich aus Period-String
	 *
	 * @param string $period Zeitraum.
	 * @return array
	 */
	public function getDateRange( string $period ): array {
		$now = current_time( 'timestamp' );

		return match ( $period ) {
			'today' => [
				'from' => gmdate( 'Y-m-d 00:00:00', $now ),
				'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
			],
			'7days' => [
				'from' => gmdate( 'Y-m-d 00:00:00', strtotime( '-6 days', $now ) ),
				'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
			],
			'30days' => [
				'from' => gmdate( 'Y-m-d 00:00:00', strtotime( '-29 days', $now ) ),
				'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
			],
			'90days' => [
				'from' => gmdate( 'Y-m-d 00:00:00', strtotime( '-89 days', $now ) ),
				'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
			],
			'year' => [
				'from' => gmdate( 'Y-01-01 00:00:00', $now ),
				'to'   => gmdate( 'Y-m-d 23:59:59', $now ),
			],
			default => [
				'from' => null,
				'to'   => null,
			],
		};
	}

	/**
	 * Vorherigen Zeitraum berechnen
	 *
	 * @param array $current_range Aktueller Zeitraum.
	 * @param string $period Period-String.
	 * @return array
	 */
	private function getPreviousPeriod( array $current_range, string $period ): array {
		if ( empty( $current_range['from'] ) || empty( $current_range['to'] ) ) {
			return [ 'from' => null, 'to' => null ];
		}

		$from = strtotime( $current_range['from'] );
		$to = strtotime( $current_range['to'] );
		$duration = $to - $from;

		return [
			'from' => gmdate( 'Y-m-d H:i:s', $from - $duration - 1 ),
			'to'   => gmdate( 'Y-m-d H:i:s', $from - 1 ),
		];
	}

	/**
	 * Cache-Wert abrufen
	 *
	 * @param string $key Cache-Key.
	 * @return mixed|false
	 */
	private function getCache( string $key ): mixed {
		return wp_cache_get( $key, self::CACHE_GROUP );
	}

	/**
	 * Cache-Wert setzen
	 *
	 * @param string $key Cache-Key.
	 * @param mixed $value Wert.
	 * @return bool
	 */
	private function setCache( string $key, mixed $value ): bool {
		return wp_cache_set( $key, $value, self::CACHE_GROUP, self::CACHE_TTL );
	}

	/**
	 * Cache invalidieren
	 *
	 * @return void
	 */
	public function invalidateCache(): void {
		wp_cache_delete( 'overview_today', self::CACHE_GROUP );
		wp_cache_delete( 'overview_7days', self::CACHE_GROUP );
		wp_cache_delete( 'overview_30days', self::CACHE_GROUP );
		wp_cache_delete( 'overview_90days', self::CACHE_GROUP );
		wp_cache_delete( 'overview_year', self::CACHE_GROUP );
		wp_cache_delete( 'overview_all', self::CACHE_GROUP );
	}
}

<?php
/**
 * Export Service - CSV-Export für Bewerbungen und Statistiken
 *
 * @package RecruitingPlaybook
 */

declare(strict_types=1);

namespace RecruitingPlaybook\Services;

defined( 'ABSPATH' ) || exit;

use RecruitingPlaybook\Repositories\StatsRepository;
use WP_Error;

/**
 * Service für CSV-Export
 */
class ExportService {

	/**
	 * Batch-Größe für Streaming
	 */
	private const BATCH_SIZE = 500;

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
	 * Bewerbungen exportieren (Streaming)
	 *
	 * @param array $args Filter-Argumente.
	 * @return void|WP_Error
	 */
	public function exportApplications( array $args ): void|WP_Error {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'csv_export' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'CSV-Export erfordert die Pro-Version.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		$filename = sprintf(
			'bewerbungen_%s.csv',
			gmdate( 'Y-m-d_His' )
		);

		// Headers für CSV-Download.
		$this->sendDownloadHeaders( $filename );

		// Output Stream.
		$output = fopen( 'php://output', 'w' );

		// BOM für Excel UTF-8 Kompatibilität.
		fwrite( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Header-Zeile.
		$columns = $this->getExportColumns( $args['columns'] ?? [] );
		fputcsv( $output, array_values( $columns ), ';' );

		// Daten in Batches exportieren.
		$offset = 0;
		do {
			$applications = $this->repository->getApplicationsForExport(
				$args,
				self::BATCH_SIZE,
				$offset
			);

			foreach ( $applications as $app ) {
				$row = $this->formatRow( $app, array_keys( $columns ) );
				fputcsv( $output, $row, ';' );
			}

			$offset += self::BATCH_SIZE;

			// Memory freigeben.
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}

		} while ( count( $applications ) === self::BATCH_SIZE );

		fclose( $output );
		exit;
	}

	/**
	 * Statistik-Report exportieren
	 *
	 * @param array $args Filter-Argumente.
	 * @return void|WP_Error
	 */
	public function exportStats( array $args ): void|WP_Error {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'csv_export' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'CSV-Export erfordert die Pro-Version.', 'recruiting-playbook' ),
				[ 'status' => 403 ]
			);
		}

		$filename = sprintf(
			'statistik-report_%s.csv',
			gmdate( 'Y-m-d_His' )
		);

		$this->sendDownloadHeaders( $filename );

		$output = fopen( 'php://output', 'w' );
		fwrite( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		$stats_service = new StatsService( $this->repository );
		$period = $args['period'] ?? '30days';

		// Übersicht exportieren.
		$overview = $stats_service->getOverview( $period );

		// Abschnitt: Zusammenfassung.
		fputcsv( $output, [ __( 'ZUSAMMENFASSUNG', 'recruiting-playbook' ) ], ';' );
		fputcsv( $output, [ '' ], ';' );

		fputcsv( $output, [
			__( 'Kennzahl', 'recruiting-playbook' ),
			__( 'Wert', 'recruiting-playbook' ),
		], ';' );

		fputcsv( $output, [ __( 'Bewerbungen (gesamt)', 'recruiting-playbook' ), $overview['applications']['total'] ], ';' );
		fputcsv( $output, [ __( 'Neue Bewerbungen', 'recruiting-playbook' ), $overview['applications']['new'] ], ';' );
		fputcsv( $output, [ __( 'In Bearbeitung', 'recruiting-playbook' ), $overview['applications']['in_progress'] ], ';' );
		fputcsv( $output, [ __( 'Eingestellt', 'recruiting-playbook' ), $overview['applications']['hired'] ], ';' );
		fputcsv( $output, [ __( 'Abgelehnt', 'recruiting-playbook' ), $overview['applications']['rejected'] ], ';' );
		fputcsv( $output, [ __( 'Aktive Stellen', 'recruiting-playbook' ), $overview['jobs']['active'] ], ';' );

		if ( isset( $overview['time_to_hire']['average_days'] ) ) {
			fputcsv( $output, [ __( 'Ø Time-to-Hire (Tage)', 'recruiting-playbook' ), $overview['time_to_hire']['average_days'] ], ';' );
		}

		if ( isset( $overview['conversion_rate']['rate'] ) ) {
			fputcsv( $output, [ __( 'Conversion-Rate (%)', 'recruiting-playbook' ), $overview['conversion_rate']['rate'] ], ';' );
		}

		fputcsv( $output, [ '' ], ';' );
		fputcsv( $output, [ '' ], ';' );

		// Abschnitt: Top-Stellen.
		fputcsv( $output, [ __( 'TOP-STELLEN NACH BEWERBUNGEN', 'recruiting-playbook' ) ], ';' );
		fputcsv( $output, [ '' ], ';' );

		fputcsv( $output, [
			__( 'Stelle', 'recruiting-playbook' ),
			__( 'Bewerbungen', 'recruiting-playbook' ),
		], ';' );

		foreach ( $overview['top_jobs'] as $job ) {
			fputcsv( $output, [
				$job['title'],
				$job['applications'],
			], ';' );
		}

		fputcsv( $output, [ '' ], ';' );
		fputcsv( $output, [ sprintf( __( 'Exportiert am: %s', 'recruiting-playbook' ), gmdate( 'd.m.Y H:i' ) ) ], ';' );

		fclose( $output );
		exit;
	}

	/**
	 * HTTP-Headers für Download senden
	 *
	 * @param string $filename Dateiname.
	 */
	private function sendDownloadHeaders( string $filename ): void {
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	}

	/**
	 * Export-Spalten mit deutschen Labels
	 *
	 * @param array $requested Angeforderte Spalten.
	 * @return array<string, string>
	 */
	private function getExportColumns( array $requested ): array {
		$available = [
			'id'              => __( 'ID', 'recruiting-playbook' ),
			'candidate_name'  => __( 'Name', 'recruiting-playbook' ),
			'email'           => __( 'E-Mail', 'recruiting-playbook' ),
			'phone'           => __( 'Telefon', 'recruiting-playbook' ),
			'job_id'          => __( 'Stellen-ID', 'recruiting-playbook' ),
			'job_title'       => __( 'Stelle', 'recruiting-playbook' ),
			'status'          => __( 'Status', 'recruiting-playbook' ),
			'source'          => __( 'Quelle', 'recruiting-playbook' ),
			'created_at'      => __( 'Bewerbungsdatum', 'recruiting-playbook' ),
			'updated_at'      => __( 'Letzte Änderung', 'recruiting-playbook' ),
			'hired_at'        => __( 'Einstellungsdatum', 'recruiting-playbook' ),
			'time_in_process' => __( 'Tage im Prozess', 'recruiting-playbook' ),
		];

		if ( empty( $requested ) ) {
			return $available;
		}

		return array_intersect_key( $available, array_flip( $requested ) );
	}

	/**
	 * Zeile formatieren
	 *
	 * @param array $application Bewerbungs-Daten.
	 * @param array $columns Spalten.
	 * @return array
	 */
	private function formatRow( array $application, array $columns ): array {
		$row = [];

		foreach ( $columns as $column ) {
			$value = $application[ $column ] ?? '';

			// Spezielle Formatierungen.
			switch ( $column ) {
				case 'status':
					$value = $this->getStatusLabel( $value );
					break;

				case 'created_at':
				case 'updated_at':
				case 'hired_at':
					if ( $value ) {
						$value = gmdate( 'd.m.Y H:i', strtotime( $value ) );
					}
					break;

				case 'source':
					$value = $this->getSourceLabel( $value );
					break;
			}

			$row[] = $value;
		}

		return $row;
	}

	/**
	 * Status-Label (deutsch)
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private function getStatusLabel( string $status ): string {
		$labels = [
			'new'       => __( 'Neu', 'recruiting-playbook' ),
			'screening' => __( 'In Prüfung', 'recruiting-playbook' ),
			'interview' => __( 'Interview', 'recruiting-playbook' ),
			'offer'     => __( 'Angebot', 'recruiting-playbook' ),
			'hired'     => __( 'Eingestellt', 'recruiting-playbook' ),
			'rejected'  => __( 'Abgelehnt', 'recruiting-playbook' ),
			'withdrawn' => __( 'Zurückgezogen', 'recruiting-playbook' ),
		];

		return $labels[ $status ] ?? $status;
	}

	/**
	 * Quellen-Label
	 *
	 * @param string $source Quelle.
	 * @return string
	 */
	private function getSourceLabel( string $source ): string {
		$labels = [
			'website'  => __( 'Website', 'recruiting-playbook' ),
			'direct'   => __( 'Direkt', 'recruiting-playbook' ),
			'indeed'   => 'Indeed',
			'linkedin' => 'LinkedIn',
			'stepstone' => 'StepStone',
			'xing'     => 'XING',
		];

		return $labels[ $source ] ?? ucfirst( $source );
	}

	/**
	 * Verfügbare Spalten abrufen
	 *
	 * @return array
	 */
	public function getAvailableColumns(): array {
		return [
			[
				'key'      => 'id',
				'label'    => __( 'ID', 'recruiting-playbook' ),
				'default'  => true,
			],
			[
				'key'      => 'candidate_name',
				'label'    => __( 'Name', 'recruiting-playbook' ),
				'default'  => true,
			],
			[
				'key'      => 'email',
				'label'    => __( 'E-Mail', 'recruiting-playbook' ),
				'default'  => true,
			],
			[
				'key'      => 'phone',
				'label'    => __( 'Telefon', 'recruiting-playbook' ),
				'default'  => false,
			],
			[
				'key'      => 'job_title',
				'label'    => __( 'Stelle', 'recruiting-playbook' ),
				'default'  => true,
			],
			[
				'key'      => 'status',
				'label'    => __( 'Status', 'recruiting-playbook' ),
				'default'  => true,
			],
			[
				'key'      => 'source',
				'label'    => __( 'Quelle', 'recruiting-playbook' ),
				'default'  => false,
			],
			[
				'key'      => 'created_at',
				'label'    => __( 'Bewerbungsdatum', 'recruiting-playbook' ),
				'default'  => true,
			],
			[
				'key'      => 'updated_at',
				'label'    => __( 'Letzte Änderung', 'recruiting-playbook' ),
				'default'  => false,
			],
			[
				'key'      => 'hired_at',
				'label'    => __( 'Einstellungsdatum', 'recruiting-playbook' ),
				'default'  => false,
			],
			[
				'key'      => 'time_in_process',
				'label'    => __( 'Tage im Prozess', 'recruiting-playbook' ),
				'default'  => false,
			],
		];
	}
}

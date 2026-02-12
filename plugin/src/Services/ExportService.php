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
	 * Custom Fields Service
	 *
	 * @var CustomFieldsService|null
	 */
	private ?CustomFieldsService $custom_fields_service = null;

	/**
	 * Constructor
	 *
	 * @param StatsRepository|null $repository Repository-Instanz (für Tests).
	 */
	public function __construct( ?StatsRepository $repository = null ) {
		$this->repository = $repository ?? new StatsRepository();
	}

	/**
	 * CustomFieldsService lazy-loading
	 *
	 * @return CustomFieldsService
	 */
	private function getCustomFieldsService(): CustomFieldsService {
		if ( null === $this->custom_fields_service ) {
			$this->custom_fields_service = new CustomFieldsService();
		}
		return $this->custom_fields_service;
	}

	/**
	 * Bewerbungen exportieren (Streaming)
	 *
	 * @param array $args Filter-Argumente.
	 * @return WP_Error|null WP_Error bei Fehler, bei Erfolg wird exit aufgerufen.
	 */
	public function exportApplications( array $args ) {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'csv_export' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'CSV export requires the Pro version.', 'recruiting-playbook' ),
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

		// Header-Zeile (Standard-Spalten).
		$columns = $this->getExportColumns( $args['columns'] ?? [] );

		// Custom Fields Header ermitteln (Pro-Feature).
		$custom_field_headers = [];
		$include_custom_fields = function_exists( 'rp_can' ) && rp_can( 'custom_fields' );
		if ( $include_custom_fields ) {
			$custom_field_headers = $this->getCustomFieldHeaders( $args['job_id'] ?? null );
		}

		// Header-Zeile schreiben.
		$header_row = array_values( $columns );
		if ( ! empty( $custom_field_headers ) ) {
			$header_row = array_merge( $header_row, array_values( $custom_field_headers ) );
		}
		fputcsv( $output, $header_row, ';' );

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

				// Custom Fields hinzufügen.
				if ( $include_custom_fields && ! empty( $custom_field_headers ) ) {
					$custom_values = $this->getCustomFieldValues(
						(int) ( $app['id'] ?? 0 ),
						(int) ( $app['job_id'] ?? 0 ),
						array_keys( $custom_field_headers )
					);
					$row = array_merge( $row, $custom_values );
				}

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
	 * @return WP_Error|null WP_Error bei Fehler, bei Erfolg wird exit aufgerufen.
	 */
	public function exportStats( array $args ) {
		// Pro-Feature Check.
		if ( function_exists( 'rp_can' ) && ! rp_can( 'csv_export' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'CSV export requires the Pro version.', 'recruiting-playbook' ),
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
		fputcsv( $output, [ __( 'SUMMARY', 'recruiting-playbook' ) ], ';' );
		fputcsv( $output, [ '' ], ';' );

		fputcsv( $output, [
			__( 'Metric', 'recruiting-playbook' ),
			__( 'Value', 'recruiting-playbook' ),
		], ';' );

		fputcsv( $output, [ __( 'Applications (total)', 'recruiting-playbook' ), $overview['applications']['total'] ], ';' );
		fputcsv( $output, [ __( 'New applications', 'recruiting-playbook' ), $overview['applications']['new'] ], ';' );
		fputcsv( $output, [ __( 'In progress', 'recruiting-playbook' ), $overview['applications']['in_progress'] ], ';' );
		fputcsv( $output, [ __( 'Hired', 'recruiting-playbook' ), $overview['applications']['hired'] ], ';' );
		fputcsv( $output, [ __( 'Rejected', 'recruiting-playbook' ), $overview['applications']['rejected'] ], ';' );
		fputcsv( $output, [ __( 'Active jobs', 'recruiting-playbook' ), $overview['jobs']['active'] ], ';' );

		if ( isset( $overview['time_to_hire']['average_days'] ) ) {
			fputcsv( $output, [ __( 'Avg. Time-to-Hire (days)', 'recruiting-playbook' ), $overview['time_to_hire']['average_days'] ], ';' );
		}

		if ( isset( $overview['conversion_rate']['rate'] ) ) {
			fputcsv( $output, [ __( 'Conversion Rate (%)', 'recruiting-playbook' ), $overview['conversion_rate']['rate'] ], ';' );
		}

		fputcsv( $output, [ '' ], ';' );
		fputcsv( $output, [ '' ], ';' );

		// Abschnitt: Top-Stellen.
		fputcsv( $output, [ __( 'TOP JOBS BY APPLICATIONS', 'recruiting-playbook' ) ], ';' );
		fputcsv( $output, [ '' ], ';' );

		fputcsv( $output, [
			__( 'Job', 'recruiting-playbook' ),
			__( 'Applications', 'recruiting-playbook' ),
		], ';' );

		foreach ( $overview['top_jobs'] as $job ) {
			fputcsv( $output, [
				$job['title'],
				$job['applications'],
			], ';' );
		}

		fputcsv( $output, [ '' ], ';' );
		fputcsv( $output, [ sprintf( __( 'Exported on: %s', 'recruiting-playbook' ), gmdate( 'd.m.Y H:i' ) ) ], ';' );

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
			'email'           => __( 'Email', 'recruiting-playbook' ),
			'phone'           => __( 'Phone', 'recruiting-playbook' ),
			'job_id'          => __( 'Job ID', 'recruiting-playbook' ),
			'job_title'       => __( 'Job', 'recruiting-playbook' ),
			'status'          => __( 'Status', 'recruiting-playbook' ),
			'source'          => __( 'Source', 'recruiting-playbook' ),
			'created_at'      => __( 'Application date', 'recruiting-playbook' ),
			'updated_at'      => __( 'Last modified', 'recruiting-playbook' ),
			'hired_at'        => __( 'Hired date', 'recruiting-playbook' ),
			'time_in_process' => __( 'Days in process', 'recruiting-playbook' ),
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
			'new'       => __( 'New', 'recruiting-playbook' ),
			'screening' => __( 'Screening', 'recruiting-playbook' ),
			'interview' => __( 'Interview', 'recruiting-playbook' ),
			'offer'     => __( 'Offer', 'recruiting-playbook' ),
			'hired'     => __( 'Hired', 'recruiting-playbook' ),
			'rejected'  => __( 'Rejected', 'recruiting-playbook' ),
			'withdrawn' => __( 'Withdrawn', 'recruiting-playbook' ),
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
			'direct'   => __( 'Direct', 'recruiting-playbook' ),
			'indeed'   => 'Indeed',
			'linkedin' => 'LinkedIn',
			'stepstone' => 'StepStone',
			'xing'     => 'XING',
		];

		return $labels[ $source ] ?? ucfirst( $source );
	}

	/**
	 * Custom Field Headers ermitteln
	 *
	 * @param int|null $job_id Optional: Filter nach Job-ID.
	 * @return array<string, string> Field-Key => Label.
	 */
	private function getCustomFieldHeaders( ?int $job_id = null ): array {
		$field_service = new FieldDefinitionService();

		// Felder für spezifischen Job oder alle aktiven Felder.
		if ( $job_id ) {
			$fields = $field_service->getFieldsForJob( $job_id );
		} else {
			$fields = $field_service->getActiveFields();
		}

		$headers = [];

		foreach ( $fields as $field ) {
			// System-Felder und Headings überspringen.
			if ( $field->isSystem() || 'heading' === $field->getFieldType() ) {
				continue;
			}

			// Nur aktivierte Felder.
			if ( ! $field->isActive() ) {
				continue;
			}

			$headers[ $field->getFieldKey() ] = $field->getLabel();
		}

		return $headers;
	}

	/**
	 * Custom Field Werte für Export abrufen
	 *
	 * @param int   $application_id Bewerbungs-ID.
	 * @param int   $job_id         Job-ID.
	 * @param array $field_keys     Feld-Keys in der gewünschten Reihenfolge.
	 * @return array Werte in der Reihenfolge der Field-Keys.
	 */
	private function getCustomFieldValues( int $application_id, int $job_id, array $field_keys ): array {
		if ( $application_id <= 0 || $job_id <= 0 ) {
			return array_fill( 0, count( $field_keys ), '' );
		}

		$export_values = $this->getCustomFieldsService()->getExportValues( $application_id, $job_id );

		// Werte in der richtigen Reihenfolge zurückgeben.
		$result = [];
		foreach ( $field_keys as $key ) {
			// Label-basiert oder Key-basiert suchen.
			$value = '';
			foreach ( $export_values as $label => $val ) {
				// Versuche exakte Key-Übereinstimmung oder Label-Übereinstimmung.
				if ( $label === $key || $this->normalizeKey( $label ) === $this->normalizeKey( $key ) ) {
					$value = $val;
					break;
				}
			}
			$result[] = $value;
		}

		return $result;
	}

	/**
	 * Key normalisieren für Vergleich
	 *
	 * @param string $key Key oder Label.
	 * @return string
	 */
	private function normalizeKey( string $key ): string {
		return strtolower( str_replace( [ ' ', '-', '_' ], '', $key ) );
	}

	/**
	 * Verfügbare Spalten abrufen
	 *
	 * @return array
	 */
	public function getAvailableColumns(): array {
		$standard_columns = [
			[
				'key'      => 'id',
				'label'    => __( 'ID', 'recruiting-playbook' ),
				'default'  => true,
				'group'    => 'standard',
			],
			[
				'key'      => 'candidate_name',
				'label'    => __( 'Name', 'recruiting-playbook' ),
				'default'  => true,
				'group'    => 'standard',
			],
			[
				'key'      => 'email',
				'label'    => __( 'Email', 'recruiting-playbook' ),
				'default'  => true,
				'group'    => 'standard',
			],
			[
				'key'      => 'phone',
				'label'    => __( 'Phone', 'recruiting-playbook' ),
				'default'  => false,
				'group'    => 'standard',
			],
			[
				'key'      => 'job_title',
				'label'    => __( 'Job', 'recruiting-playbook' ),
				'default'  => true,
				'group'    => 'standard',
			],
			[
				'key'      => 'status',
				'label'    => __( 'Status', 'recruiting-playbook' ),
				'default'  => true,
				'group'    => 'standard',
			],
			[
				'key'      => 'source',
				'label'    => __( 'Source', 'recruiting-playbook' ),
				'default'  => false,
				'group'    => 'standard',
			],
			[
				'key'      => 'created_at',
				'label'    => __( 'Application date', 'recruiting-playbook' ),
				'default'  => true,
				'group'    => 'standard',
			],
			[
				'key'      => 'updated_at',
				'label'    => __( 'Last modified', 'recruiting-playbook' ),
				'default'  => false,
				'group'    => 'standard',
			],
			[
				'key'      => 'hired_at',
				'label'    => __( 'Hired date', 'recruiting-playbook' ),
				'default'  => false,
				'group'    => 'standard',
			],
			[
				'key'      => 'time_in_process',
				'label'    => __( 'Days in process', 'recruiting-playbook' ),
				'default'  => false,
				'group'    => 'standard',
			],
		];

		// Custom Fields als verfügbare Spalten hinzufügen (Pro-Feature).
		if ( function_exists( 'rp_can' ) && rp_can( 'custom_fields' ) ) {
			$custom_field_headers = $this->getCustomFieldHeaders();

			foreach ( $custom_field_headers as $key => $label ) {
				$standard_columns[] = [
					'key'     => 'custom_' . $key,
					'label'   => $label,
					'default' => false,
					'group'   => 'custom',
				];
			}
		}

		return $standard_columns;
	}
}

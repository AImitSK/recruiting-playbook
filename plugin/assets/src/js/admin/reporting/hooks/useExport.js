/**
 * Custom Hook für CSV-Export
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook für CSV-Export-Funktionalität
 *
 * @return {Object} Export state und Funktionen
 */
export function useExport() {
	const [ columns, setColumns ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ columnsLoading, setColumnsLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	/**
	 * Verfügbare Spalten laden
	 */
	const fetchColumns = useCallback( async () => {
		try {
			setColumnsLoading( true );

			const data = await apiFetch( {
				path: '/recruiting/v1/export/columns',
			} );

			setColumns( data.columns || [] );
		} catch ( err ) {
			console.error( 'Error fetching export columns:', err );
			// Fallback columns
			setColumns( [
				{ key: 'id', label: 'ID', default: true },
				{ key: 'candidate_name', label: 'Name', default: true },
				{ key: 'email', label: 'E-Mail', default: true },
				{ key: 'job_title', label: 'Stelle', default: true },
				{ key: 'status', label: 'Status', default: true },
				{ key: 'created_at', label: 'Datum', default: true },
			] );
		} finally {
			setColumnsLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchColumns();
	}, [ fetchColumns ] );

	/**
	 * Bewerbungen als CSV exportieren
	 *
	 * @param {Object} options Export-Optionen
	 * @param {string} options.dateFrom Startdatum
	 * @param {string} options.dateTo Enddatum
	 * @param {Array} options.status Status-Filter
	 * @param {number} options.jobId Stellen-Filter
	 * @param {Array} options.columns Spalten-Auswahl
	 */
	const exportApplications = useCallback( async ( options = {} ) => {
		try {
			setLoading( true );
			setError( null );

			const params = new URLSearchParams();

			if ( options.dateFrom ) {
				params.append( 'date_from', options.dateFrom );
			}
			if ( options.dateTo ) {
				params.append( 'date_to', options.dateTo );
			}
			if ( options.status?.length ) {
				options.status.forEach( ( s ) => params.append( 'status[]', s ) );
			}
			if ( options.jobId ) {
				params.append( 'job_id', options.jobId );
			}
			if ( options.columns?.length ) {
				options.columns.forEach( ( c ) => params.append( 'columns[]', c ) );
			}

			// Direkter Download via window.location
			const baseUrl = window.rpAdmin?.apiUrl || '/wp-json/recruiting/v1/';
			const nonce = window.rpAdmin?.nonce || '';

			const url = `${ baseUrl }export/applications?${ params.toString() }&_wpnonce=${ nonce }`;
			window.location.href = url;
		} catch ( err ) {
			console.error( 'Error exporting applications:', err );
			setError( err.message || 'Export fehlgeschlagen' );
		} finally {
			setLoading( false );
		}
	}, [] );

	/**
	 * Statistik-Report als CSV exportieren
	 *
	 * @param {string} period Zeitraum
	 */
	const exportStats = useCallback( async ( period = '30days' ) => {
		try {
			setLoading( true );
			setError( null );

			const baseUrl = window.rpAdmin?.apiUrl || '/wp-json/recruiting/v1/';
			const nonce = window.rpAdmin?.nonce || '';

			const url = `${ baseUrl }export/stats?period=${ period }&_wpnonce=${ nonce }`;
			window.location.href = url;
		} catch ( err ) {
			console.error( 'Error exporting stats:', err );
			setError( err.message || 'Export fehlgeschlagen' );
		} finally {
			setLoading( false );
		}
	}, [] );

	return {
		columns,
		columnsLoading,
		loading,
		error,
		exportApplications,
		exportStats,
	};
}

export default useExport;

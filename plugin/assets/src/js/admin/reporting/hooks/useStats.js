/**
 * Custom Hook für Statistiken
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook zum Laden von Statistiken
 *
 * @param {string} period Zeitraum (today, 7days, 30days, 90days, year, all)
 * @param {number|null} jobId Optional: Filter nach Stelle
 * @return {Object} Stats state und Funktionen
 */
export function useStats( period = '30days', jobId = null ) {
	const [ overview, setOverview ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	/**
	 * Übersicht-Statistiken laden
	 */
	const fetchOverview = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const params = new URLSearchParams( { period } );
			if ( jobId ) {
				params.append( 'job_id', jobId );
			}

			const data = await apiFetch( {
				path: `/recruiting/v1/stats/overview?${ params.toString() }`,
			} );

			setOverview( data );
		} catch ( err ) {
			console.error( 'Error fetching stats overview:', err );
			setError( err.message || 'Fehler beim Laden der Statistiken' );
		} finally {
			setLoading( false );
		}
	}, [ period, jobId ] );

	useEffect( () => {
		fetchOverview();
	}, [ fetchOverview ] );

	return {
		overview,
		loading,
		error,
		refetch: fetchOverview,
	};
}

/**
 * Hook zum Laden von Bewerbungs-Statistiken
 *
 * @param {string} period Zeitraum
 * @param {number|null} jobId Optional: Filter nach Stelle
 * @return {Object} Application stats
 */
export function useApplicationStats( period = '30days', jobId = null ) {
	const [ stats, setStats ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	const fetchStats = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const params = new URLSearchParams( { period } );
			if ( jobId ) {
				params.append( 'job_id', jobId );
			}

			const data = await apiFetch( {
				path: `/recruiting/v1/stats/applications?${ params.toString() }`,
			} );

			setStats( data );
		} catch ( err ) {
			console.error( 'Error fetching application stats:', err );
			setError( err.message || 'Fehler beim Laden' );
		} finally {
			setLoading( false );
		}
	}, [ period, jobId ] );

	useEffect( () => {
		fetchStats();
	}, [ fetchStats ] );

	return {
		stats,
		loading,
		error,
		refetch: fetchStats,
	};
}

/**
 * Hook zum Laden von Job-Statistiken
 *
 * @param {string} period Zeitraum
 * @return {Object} Job stats
 */
export function useJobStats( period = '30days' ) {
	const [ stats, setStats ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	const fetchStats = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: `/recruiting/v1/stats/jobs?period=${ period }`,
			} );

			setStats( data );
		} catch ( err ) {
			console.error( 'Error fetching job stats:', err );
			setError( err.message || 'Fehler beim Laden' );
		} finally {
			setLoading( false );
		}
	}, [ period ] );

	useEffect( () => {
		fetchStats();
	}, [ fetchStats ] );

	return {
		stats,
		loading,
		error,
		refetch: fetchStats,
	};
}

/**
 * Hook zum Laden von Trend-Daten
 *
 * @param {string} period Zeitraum
 * @param {string} granularity Zeiteinheit (day, week, month)
 * @param {number|null} jobId Optional: Filter nach Stelle
 * @return {Object} Trend data
 */
export function useTrends( period = '30days', granularity = 'day', jobId = null ) {
	const [ trends, setTrends ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	const fetchTrends = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const params = new URLSearchParams( { period, granularity } );
			if ( jobId ) {
				params.append( 'job_id', jobId );
			}

			const data = await apiFetch( {
				path: `/recruiting/v1/stats/trends?${ params.toString() }`,
			} );

			setTrends( data );
		} catch ( err ) {
			console.error( 'Error fetching trends:', err );
			setError( err.message || 'Fehler beim Laden' );
		} finally {
			setLoading( false );
		}
	}, [ period, granularity, jobId ] );

	useEffect( () => {
		fetchTrends();
	}, [ fetchTrends ] );

	return {
		trends,
		loading,
		error,
		refetch: fetchTrends,
	};
}

export default useStats;

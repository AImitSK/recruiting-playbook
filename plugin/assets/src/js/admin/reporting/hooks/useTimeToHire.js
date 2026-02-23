/**
 * Custom Hook für Time-to-Hire Statistiken
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Hook zum Laden von Time-to-Hire Statistiken
 *
 * @param {string} period Zeitraum (today, 7days, 30days, 90days, year, all)
 * @param {number|null} jobId Optional: Filter nach Stelle
 * @return {Object} Time-to-hire state
 */
export function useTimeToHire( period = '30days', jobId = null ) {
	const [ data, setData ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	const fetchData = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const params = new URLSearchParams( { period } );
			if ( jobId ) {
				params.append( 'job_id', jobId );
			}

			const result = await apiFetch( {
				path: `/recruiting/v1/stats/time-to-hire?${ params.toString() }`,
			} );

			setData( result );
		} catch ( err ) {
			console.error( 'Error fetching time-to-hire:', err );
			setError( err.message || __( 'Error loading time-to-hire data', 'recruiting-playbook' ) );
		} finally {
			setLoading( false );
		}
	}, [ period, jobId ] );

	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	// Berechnete Werte
	const averageDays = data?.overall?.average_days || 0;
	const minDays = data?.overall?.min_days || 0;
	const maxDays = data?.overall?.max_days || 0;
	const hiredCount = data?.overall?.hired_count || 0;
	const byStage = data?.by_stage || {};
	const byJob = data?.by_job || [];
	const trend = data?.trend || [];

	return {
		data,
		averageDays,
		minDays,
		maxDays,
		hiredCount,
		byStage,
		byJob,
		trend,
		loading,
		error,
		refetch: fetchData,
	};
}

export default useTimeToHire;

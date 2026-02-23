/**
 * Custom Hook für Conversion-Rate Statistiken
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Hook zum Laden von Conversion-Rate Statistiken
 *
 * @param {string} period Zeitraum (today, 7days, 30days, 90days, year, all)
 * @param {number|null} jobId Optional: Filter nach Stelle
 * @return {Object} Conversion state
 */
export function useConversion( period = '30days', jobId = null ) {
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
				path: `/recruiting/v1/stats/conversion?${ params.toString() }`,
			} );

			setData( result );
		} catch ( err ) {
			console.error( 'Error fetching conversion:', err );
			setError( err.message || __( 'Error loading conversion data', 'recruiting-playbook' ) );
		} finally {
			setLoading( false );
		}
	}, [ period, jobId ] );

	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	// Berechnete Werte
	const overallRate = data?.overall?.conversion_rate || 0;
	const views = data?.overall?.views || 0;
	const applications = data?.overall?.applications || 0;
	const funnel = data?.funnel || {};
	const bySource = data?.by_source || [];
	const trend = data?.trend || [];
	const topJobs = data?.top_converting_jobs || [];

	return {
		data,
		overallRate,
		views,
		applications,
		funnel,
		bySource,
		trend,
		topJobs,
		loading,
		error,
		refetch: fetchData,
	};
}

export default useConversion;

/**
 * Custom Hook für Systemstatus
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook zum Laden des Systemstatus
 *
 * @return {Object} System status state und Funktionen
 */
export function useSystemStatus() {
	const [ status, setStatus ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ cleanupLoading, setCleanupLoading ] = useState( false );

	/**
	 * Systemstatus laden
	 */
	const fetchStatus = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: '/recruiting/v1/system/status',
			} );

			setStatus( data );
		} catch ( err ) {
			console.error( 'Error fetching system status:', err );
			setError( err.message || 'Fehler beim Laden des Systemstatus' );
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchStatus();
	}, [ fetchStatus ] );

	/**
	 * Verwaiste Dokumente bereinigen
	 */
	const cleanupDocuments = useCallback( async () => {
		try {
			setCleanupLoading( true );

			const result = await apiFetch( {
				path: '/recruiting/v1/system/cleanup/documents',
				method: 'POST',
			} );

			// Status neu laden nach Cleanup
			await fetchStatus();

			return result;
		} catch ( err ) {
			console.error( 'Error cleaning up documents:', err );
			throw err;
		} finally {
			setCleanupLoading( false );
		}
	}, [ fetchStatus ] );

	/**
	 * Verwaiste Bewerbungen bereinigen
	 */
	const cleanupApplications = useCallback( async () => {
		try {
			setCleanupLoading( true );

			const result = await apiFetch( {
				path: '/recruiting/v1/system/cleanup/applications',
				method: 'POST',
			} );

			// Status neu laden nach Cleanup
			await fetchStatus();

			return result;
		} catch ( err ) {
			console.error( 'Error cleaning up applications:', err );
			throw err;
		} finally {
			setCleanupLoading( false );
		}
	}, [ fetchStatus ] );

	return {
		status,
		loading,
		error,
		cleanupLoading,
		cleanupDocuments,
		cleanupApplications,
		refetch: fetchStatus,
	};
}

export default useSystemStatus;

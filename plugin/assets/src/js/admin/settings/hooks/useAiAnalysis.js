/**
 * Custom Hook für KI-Analyse Settings
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Hook zum Laden und Verwalten der KI-Analyse Daten
 *
 * @return {Object} State und Funktionen
 */
export function useAiAnalysis() {
	const [ stats, setStats ] = useState( null );
	const [ health, setHealth ] = useState( null );
	const [ settings, setSettings ] = useState( null );
	const [ history, setHistory ] = useState( { items: [], total: 0, pages: 0, page: 1, per_page: 20 } );
	const [ historyPage, setHistoryPage ] = useState( 1 );
	const [ historyFilters, setHistoryFilters ] = useState( { type: '', status: '' } );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ healthLoading, setHealthLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	const isMountedRef = useRef( true );

	/**
	 * History laden
	 */
	const fetchHistory = useCallback( async ( page = 1, filters = {} ) => {
		try {
			const params = new URLSearchParams( {
				page: String( page ),
				per_page: '20',
			} );
			if ( filters.type ) {
				params.set( 'type', filters.type );
			}
			if ( filters.status ) {
				params.set( 'status', filters.status );
			}

			const data = await apiFetch( {
				path: `/recruiting/v1/ai-analysis/history?${ params.toString() }`,
			} );

			if ( isMountedRef.current ) {
				setHistory( data );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || __( 'Error loading history', 'recruiting-playbook' ) );
			}
		}
	}, [] );

	/**
	 * Initiales Laden
	 */
	const fetchInitialData = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const [ statsData, settingsData, historyData ] = await Promise.all( [
				apiFetch( { path: '/recruiting/v1/ai-analysis/stats' } ),
				apiFetch( { path: '/recruiting/v1/ai-analysis/settings' } ),
				apiFetch( { path: '/recruiting/v1/ai-analysis/history?page=1&per_page=20' } ),
			] );

			if ( isMountedRef.current ) {
				setStats( statsData );
				setSettings( settingsData );
				setHistory( historyData );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || __( 'Error loading AI analysis data', 'recruiting-playbook' ) );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [] );

	useEffect( () => {
		isMountedRef.current = true;
		fetchInitialData();

		return () => {
			isMountedRef.current = false;
		};
	}, [ fetchInitialData ] );

	/**
	 * History bei Seiten-/Filterwechsel neu laden
	 */
	useEffect( () => {
		if ( ! loading ) {
			fetchHistory( historyPage, historyFilters );
		}
	}, [ historyPage, historyFilters, fetchHistory, loading ] );

	/**
	 * Health-Check ausführen
	 */
	const fetchHealth = useCallback( async () => {
		try {
			setHealthLoading( true );

			const data = await apiFetch( {
				path: '/recruiting/v1/ai-analysis/health',
			} );

			if ( isMountedRef.current ) {
				setHealth( data );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setHealth( {
					reachable: false,
					response_time_ms: 0,
					checked_at: new Date().toISOString(),
					error: err?.message,
				} );
			}
		} finally {
			if ( isMountedRef.current ) {
				setHealthLoading( false );
			}
		}
	}, [] );

	/**
	 * Einstellungen speichern
	 */
	const saveSettings = useCallback( async ( data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/ai-analysis/settings',
				method: 'POST',
				data,
			} );

			if ( isMountedRef.current ) {
				setSettings( result );
			}

			return true;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || __( 'Error saving', 'recruiting-playbook' ) );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [] );

	return {
		stats,
		health,
		settings,
		history,
		historyFilters,
		historyPage,
		loading,
		saving,
		healthLoading,
		error,
		setError,
		setHistoryPage,
		setHistoryFilters,
		fetchHealth,
		saveSettings,
	};
}

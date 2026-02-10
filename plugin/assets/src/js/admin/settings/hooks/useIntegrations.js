/**
 * Custom Hook für Integrations-Settings
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Default-Werte für alle Integrationen
 */
const DEFAULTS = {
	// Google for Jobs (Free)
	google_jobs_enabled: true,
	google_jobs_show_salary: true,
	google_jobs_show_remote: true,
	google_jobs_show_deadline: true,

	// XML Job Feed (Free)
	xml_feed_enabled: true,
	xml_feed_show_salary: true,
	xml_feed_html_description: true,
	xml_feed_max_items: 50,

	// Slack (Pro)
	slack_enabled: false,
	slack_webhook_url: '',
	slack_event_new_application: true,
	slack_event_status_changed: true,
	slack_event_job_published: false,
	slack_event_deadline_reminder: false,

	// Microsoft Teams (Pro)
	teams_enabled: false,
	teams_webhook_url: '',
	teams_event_new_application: true,
	teams_event_status_changed: true,
	teams_event_job_published: false,
	teams_event_deadline_reminder: false,

};

/**
 * Hook zum Laden und Verwalten der Integrations-Einstellungen
 *
 * @return {Object} State und Funktionen
 */
export function useIntegrations() {
	const [ settings, setSettings ] = useState( DEFAULTS );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ testing, setTesting ] = useState( null ); // 'slack' | 'teams' | null
	const [ testResult, setTestResult ] = useState( null );
	const [ error, setError ] = useState( null );

	const isMountedRef = useRef( true );

	/**
	 * Einstellungen laden
	 */
	const fetchSettings = useCallback( async () => {
		try {
			setLoading( true );
			const data = await apiFetch( {
				path: '/recruiting/v1/settings/integrations',
			} );

			if ( isMountedRef.current ) {
				setSettings( { ...DEFAULTS, ...data } );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler beim Laden der Einstellungen' );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
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

			const response = await apiFetch( {
				path: '/recruiting/v1/settings/integrations',
				method: 'POST',
				data,
			} );

			if ( isMountedRef.current ) {
				setSettings( { ...DEFAULTS, ...response } );
			}

			return true;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || 'Fehler beim Speichern' );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [] );

	/**
	 * Einzelne Einstellung aktualisieren (lokal)
	 */
	const updateSetting = useCallback( ( key, value ) => {
		setSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	}, [] );

	/**
	 * Test-Nachricht senden
	 *
	 * @param {string} service 'slack' oder 'teams'
	 */
	const sendTestMessage = useCallback( async ( service ) => {
		try {
			setTesting( service );
			setTestResult( null );

			const response = await apiFetch( {
				path: `/recruiting/v1/integrations/${ service }/test`,
				method: 'POST',
			} );

			if ( isMountedRef.current ) {
				setTestResult( { service, success: true, message: response?.message } );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setTestResult( {
					service,
					success: false,
					message: err?.message || 'Test fehlgeschlagen',
				} );
			}
		} finally {
			if ( isMountedRef.current ) {
				setTesting( null );
			}
		}
	}, [] );

	/**
	 * Initialer Load
	 */
	useEffect( () => {
		isMountedRef.current = true;
		fetchSettings();

		return () => {
			isMountedRef.current = false;
		};
	}, [ fetchSettings ] );

	return {
		settings,
		loading,
		saving,
		testing,
		testResult,
		error,
		setError,
		setTestResult,
		updateSetting,
		saveSettings,
		sendTestMessage,
	};
}

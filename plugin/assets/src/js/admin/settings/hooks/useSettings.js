/**
 * Custom Hook für Plugin-Einstellungen
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Hook zum Laden und Verwalten der Plugin-Einstellungen
 *
 * @return {Object} Settings state und Funktionen
 */
export function useSettings() {
	const [ settings, setSettings ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	// i18n Nachrichten
	const i18n = window.rpSettingsData?.i18n || {};

	// Refs für Cleanup und Mount-Status
	const abortControllerRef = useRef( null );
	const isMountedRef = useRef( true );

	/**
	 * Einstellungen vom Server laden
	 */
	const fetchSettings = useCallback( async () => {
		// Vorherigen Request abbrechen
		if ( abortControllerRef.current ) {
			abortControllerRef.current.abort();
		}
		abortControllerRef.current = new AbortController();

		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: '/recruiting/v1/settings',
				signal: abortControllerRef.current.signal,
			} );

			if ( isMountedRef.current ) {
				setSettings( data );
			}
		} catch ( err ) {
			if ( err?.name === 'AbortError' ) {
				return;
			}
			if ( isMountedRef.current ) {
				setError( i18n.errorLoading || __( 'Error loading settings', 'recruiting-playbook' ) );
				console.error( 'Error fetching settings:', err );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [ i18n.errorLoading ] );

	// Initial laden und Cleanup
	useEffect( () => {
		isMountedRef.current = true;
		fetchSettings();

		return () => {
			isMountedRef.current = false;
			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}
		};
	}, [ fetchSettings ] );

	/**
	 * Einstellungen speichern
	 *
	 * @param {Object} data Zu speichernde Einstellungen (partial update)
	 * @return {boolean} Erfolg
	 */
	const saveSettings = useCallback( async ( data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/settings',
				method: 'POST',
				data,
			} );

			if ( ! isMountedRef.current ) {
				return false;
			}

			// Lokalen State aktualisieren
			setSettings( ( prev ) => ( {
				...prev,
				...data,
			} ) );

			return result.success || true;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return false;
			}

			const errorMessage = err?.message || i18n.errorSaving || __( 'Error saving settings', 'recruiting-playbook' );
			setError( errorMessage );
			console.error( 'Error saving settings:', err );
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ i18n.errorSaving ] );

	/**
	 * Einzelnes Setting aktualisieren (nur lokal)
	 *
	 * @param {string} key   Setting-Key
	 * @param {*}      value Neuer Wert
	 */
	const updateSetting = useCallback( ( key, value ) => {
		setSettings( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	}, [] );

	/**
	 * Mehrere Settings aktualisieren (nur lokal)
	 *
	 * @param {Object} updates Key-Value Paare
	 */
	const updateSettings = useCallback( ( updates ) => {
		setSettings( ( prev ) => ( {
			...prev,
			...updates,
		} ) );
	}, [] );

	return {
		settings,
		loading,
		saving,
		error,
		setError,
		fetchSettings,
		saveSettings,
		updateSetting,
		updateSettings,
		refetch: fetchSettings,
	};
}

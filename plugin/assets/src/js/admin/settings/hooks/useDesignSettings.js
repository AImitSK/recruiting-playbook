/**
 * Custom Hook für Design & Branding Einstellungen
 *
 * Lädt und verwaltet die Design-Settings separat von den allgemeinen Settings.
 * Unterstützt Live-Vorschau durch lokalen State.
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook zum Laden und Verwalten der Design-Einstellungen
 *
 * @return {Object} Design settings state und Funktionen
 */
export function useDesignSettings() {
	const [ settings, setSettings ] = useState( null );
	const [ schema, setSchema ] = useState( null );
	const [ defaults, setDefaults ] = useState( null );
	const [ meta, setMeta ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ isDirty, setIsDirty ] = useState( false );

	// Original settings für Dirty-Check
	const originalSettingsRef = useRef( null );

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
				path: '/recruiting/v1/settings/design',
				signal: abortControllerRef.current.signal,
			} );

			if ( isMountedRef.current ) {
				setSettings( data.settings );
				setSchema( data.schema );
				setDefaults( data.defaults );
				setMeta( data.meta );
				originalSettingsRef.current = JSON.stringify( data.settings );
				setIsDirty( false );
			}
		} catch ( err ) {
			if ( err?.name === 'AbortError' ) {
				return;
			}
			if ( isMountedRef.current ) {
				setError( 'Fehler beim Laden der Design-Einstellungen' );
				console.error( 'Error fetching design settings:', err );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [] );

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
	 * Dirty-Check: Hat sich etwas geändert?
	 */
	useEffect( () => {
		if ( settings && originalSettingsRef.current ) {
			const currentJson = JSON.stringify( settings );
			setIsDirty( currentJson !== originalSettingsRef.current );
		}
	}, [ settings ] );

	/**
	 * Einstellungen speichern
	 *
	 * @return {boolean} Erfolg
	 */
	const saveSettings = useCallback( async () => {
		if ( ! settings ) {
			return false;
		}

		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/settings/design',
				method: 'POST',
				data: settings,
			} );

			if ( ! isMountedRef.current ) {
				return false;
			}

			if ( result.settings ) {
				setSettings( result.settings );
				originalSettingsRef.current = JSON.stringify( result.settings );
			}
			setIsDirty( false );

			return true;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return false;
			}

			const errorMessage = err?.message || 'Fehler beim Speichern';
			setError( errorMessage );
			console.error( 'Error saving design settings:', err );
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ settings ] );

	/**
	 * Einstellungen zurücksetzen
	 *
	 * @return {boolean} Erfolg
	 */
	const resetSettings = useCallback( async () => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/settings/design',
				method: 'DELETE',
			} );

			if ( ! isMountedRef.current ) {
				return false;
			}

			if ( result.settings ) {
				setSettings( result.settings );
				originalSettingsRef.current = JSON.stringify( result.settings );
			}
			setIsDirty( false );

			return true;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return false;
			}

			const errorMessage = err?.message || 'Fehler beim Zurücksetzen';
			setError( errorMessage );
			console.error( 'Error resetting design settings:', err );
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [] );

	/**
	 * Einzelnes Setting aktualisieren (nur lokal für Live-Vorschau)
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

	/**
	 * Änderungen verwerfen (zurück zum Server-Stand)
	 */
	const discardChanges = useCallback( () => {
		if ( originalSettingsRef.current ) {
			setSettings( JSON.parse( originalSettingsRef.current ) );
			setIsDirty( false );
		}
	}, [] );

	/**
	 * Berechnete Primärfarbe (Theme oder Custom)
	 */
	const computedPrimaryColor = useMemo( () => {
		if ( ! settings ) {
			return '#2563eb';
		}
		if ( settings.use_theme_colors && meta?.primary_color_computed ) {
			return meta.primary_color_computed;
		}
		return settings.primary_color || '#2563eb';
	}, [ settings, meta ] );

	/**
	 * Berechnete Logo-URL (Theme oder Custom)
	 */
	const computedLogoUrl = useMemo( () => {
		if ( ! settings ) {
			return null;
		}
		if ( settings.use_theme_logo && meta?.logo_url_computed ) {
			return meta.logo_url_computed;
		}
		// Custom Logo würde hier über eine weitere API geladen werden
		return meta?.logo_url_computed || null;
	}, [ settings, meta ] );

	return {
		// State
		settings,
		schema,
		defaults,
		meta,
		loading,
		saving,
		error,
		isDirty,

		// Computed values
		computedPrimaryColor,
		computedLogoUrl,

		// Actions
		setError,
		fetchSettings,
		saveSettings,
		resetSettings,
		updateSetting,
		updateSettings,
		discardChanges,
		refetch: fetchSettings,
	};
}

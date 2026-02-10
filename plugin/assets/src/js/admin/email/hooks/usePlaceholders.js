/**
 * Custom Hook für Platzhalter
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { handleApiError, replacePlaceholders as replacePlaceholdersUtil } from '../utils';

/**
 * Hook zum Laden der verfügbaren Platzhalter
 *
 * @return {Object} Placeholders state und Funktionen
 */
export function usePlaceholders() {
	const [ placeholders, setPlaceholders ] = useState( {} );
	const [ previewValues, setPreviewValues ] = useState( {} );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	// Stabile Referenzen
	const i18n = window.rpEmailData?.i18n || {};
	const errorLoadingMsg = i18n.errorLoading || 'Fehler beim Laden der Platzhalter';

	// Refs für Cleanup und Mount-Status
	const abortControllerRef = useRef( null );
	const isMountedRef = useRef( true );

	/**
	 * Platzhalter vom Server laden
	 */
	const fetchPlaceholders = useCallback( async () => {
		// Vorherigen Request abbrechen
		if ( abortControllerRef.current ) {
			abortControllerRef.current.abort();
		}
		abortControllerRef.current = new AbortController();

		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: '/recruiting/v1/email-templates/placeholders',
				signal: abortControllerRef.current.signal,
			} );

			// Nur State setzen wenn noch mounted
			if ( isMountedRef.current ) {
				setPlaceholders( data.groups || {} );
				setPreviewValues( data.preview_values || {} );
			}
		} catch ( err ) {
			// AbortError explizit ignorieren
			if ( err?.name === 'AbortError' ) {
				return;
			}
			if ( isMountedRef.current && ! handleApiError( err, setError, errorLoadingMsg ) ) {
				console.error( 'Error fetching placeholders:', err );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [ errorLoadingMsg ] );

	// Initial laden und Cleanup
	useEffect( () => {
		isMountedRef.current = true;
		fetchPlaceholders();

		return () => {
			isMountedRef.current = false;
			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}
		};
	}, [ fetchPlaceholders ] );

	/**
	 * Platzhalter in Text ersetzen (für Vorschau)
	 *
	 * Verwendet die zentrale Utility-Funktion mit XSS-Schutz.
	 *
	 * @param {string} text Text mit Platzhaltern
	 * @return {string} Text mit ersetzten Platzhaltern (HTML-escaped)
	 */
	const replacePlaceholders = useCallback( ( text ) => {
		return replacePlaceholdersUtil( text, previewValues );
	}, [ previewValues ] );

	/**
	 * Alle Platzhalter-Schlüssel als flaches Array
	 *
	 * @return {string[]} Platzhalter-Schlüssel
	 */
	const getAllPlaceholderKeys = useCallback( () => {
		const keys = [];

		Object.values( placeholders ).forEach( ( group ) => {
			if ( group.items ) {
				Object.keys( group.items ).forEach( ( key ) => keys.push( key ) );
			}
		} );

		return keys;
	}, [ placeholders ] );

	return {
		placeholders,
		previewValues,
		loading,
		error,
		fetchPlaceholders,
		replacePlaceholders,
		getAllPlaceholderKeys,
		refetch: fetchPlaceholders,
	};
}

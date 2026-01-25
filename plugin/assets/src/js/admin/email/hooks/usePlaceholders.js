/**
 * Custom Hook für Platzhalter
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { handleApiError } from '../utils';

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

	// AbortController Ref
	const abortControllerRef = useRef( null );

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

			setPlaceholders( data.groups || {} );
			setPreviewValues( data.preview_values || {} );
		} catch ( err ) {
			if ( ! handleApiError( err, setError, errorLoadingMsg ) ) {
				console.error( 'Error fetching placeholders:', err );
			}
		} finally {
			setLoading( false );
		}
	}, [ errorLoadingMsg ] );

	// Initial laden
	useEffect( () => {
		fetchPlaceholders();

		return () => {
			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}
		};
	}, [ fetchPlaceholders ] );

	/**
	 * Platzhalter in Text ersetzen (für Vorschau)
	 *
	 * @param {string} text Text mit Platzhaltern
	 * @return {string} Text mit ersetzten Platzhaltern
	 */
	const replacePlaceholders = useCallback( ( text ) => {
		if ( ! text ) {
			return '';
		}

		let result = text;

		Object.entries( previewValues ).forEach( ( [ key, value ] ) => {
			const regex = new RegExp( `\\{${ key }\\}`, 'g' );
			result = result.replace( regex, value );
		} );

		return result;
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

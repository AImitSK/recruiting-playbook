/**
 * Custom Hook für Platzhalter
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

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

	const i18n = window.rpEmailData?.i18n || {};

	/**
	 * Platzhalter vom Server laden
	 */
	const fetchPlaceholders = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: '/recruiting/v1/email-templates/placeholders',
			} );

			setPlaceholders( data.groups || {} );
			setPreviewValues( data.preview_values || {} );
		} catch ( err ) {
			console.error( 'Error fetching placeholders:', err );
			setError( err.message || i18n.errorLoading || 'Fehler beim Laden der Platzhalter' );
		} finally {
			setLoading( false );
		}
	}, [ i18n.errorLoading ] );

	// Initial laden
	useEffect( () => {
		fetchPlaceholders();
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

/**
 * Custom Hook für E-Mail-Signaturen
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { handleApiError } from '../utils';

/**
 * Hook zum Laden und Verwalten von E-Mail-Signaturen
 *
 * @return {Object} Signatures state und Funktionen
 */
export function useSignatures() {
	const [ signatures, setSignatures ] = useState( [] );
	const [ companySignature, setCompanySignature ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	// i18n Nachrichten
	const i18n = window.rpEmailData?.i18n || {};
	const errorLoadingMsg = i18n.errorLoading || 'Fehler beim Laden der Signaturen';
	const errorSavingMsg = i18n.errorSaving || 'Fehler beim Speichern';
	const errorDeletingMsg = i18n.errorDeleting || 'Fehler beim Löschen';

	// Refs für Cleanup und Mount-Status
	const abortControllerRef = useRef( null );
	const isMountedRef = useRef( true );

	/**
	 * Signaturen vom Server laden
	 */
	const fetchSignatures = useCallback( async () => {
		// Vorherigen Request abbrechen
		if ( abortControllerRef.current ) {
			abortControllerRef.current.abort();
		}
		abortControllerRef.current = new AbortController();

		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: '/recruiting/v1/signatures',
				signal: abortControllerRef.current.signal,
			} );

			// Nur State setzen wenn noch mounted
			if ( isMountedRef.current ) {
				setSignatures( data.items || data || [] );
			}
		} catch ( err ) {
			// AbortError explizit ignorieren
			if ( err?.name === 'AbortError' ) {
				return;
			}
			if ( isMountedRef.current && ! handleApiError( err, setError, errorLoadingMsg ) ) {
				console.error( 'Error fetching signatures:', err );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [ errorLoadingMsg ] );

	/**
	 * Firmen-Signatur laden (nur für Admins)
	 */
	const fetchCompanySignature = useCallback( async () => {
		try {
			const data = await apiFetch( {
				path: '/recruiting/v1/signatures/company',
			} );

			if ( isMountedRef.current ) {
				setCompanySignature( data.signature || data || null );
			}
		} catch ( err ) {
			// 404 bedeutet keine Firmen-Signatur vorhanden
			if ( err?.code === 'rest_no_route' || err?.data?.status === 404 ) {
				if ( isMountedRef.current ) {
					setCompanySignature( null );
				}
				return;
			}
			console.error( 'Error fetching company signature:', err );
		}
	}, [] );

	// Initial laden und Cleanup
	useEffect( () => {
		isMountedRef.current = true;
		fetchSignatures();
		fetchCompanySignature();

		return () => {
			isMountedRef.current = false;
			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}
		};
	}, [ fetchSignatures, fetchCompanySignature ] );

	/**
	 * Signatur erstellen
	 *
	 * @param {Object} data Signatur-Daten
	 * @return {Object|null} Erstellte Signatur oder null
	 */
	const createSignature = useCallback( async ( data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/signatures',
				method: 'POST',
				data,
			} );

			if ( ! isMountedRef.current ) {
				return null;
			}

			const newSignature = result.signature || result;

			// Optimistic Update
			setSignatures( ( prev ) => [ newSignature, ...prev ] );

			return newSignature;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorSavingMsg ) ) {
				console.error( 'Error creating signature:', err );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ errorSavingMsg ] );

	/**
	 * Signatur aktualisieren
	 *
	 * @param {number} id   Signatur-ID
	 * @param {Object} data Aktualisierte Daten
	 * @return {Object|null} Aktualisierte Signatur oder null
	 */
	const updateSignature = useCallback( async ( id, data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: `/recruiting/v1/signatures/${ id }`,
				method: 'PUT',
				data,
			} );

			if ( ! isMountedRef.current ) {
				return null;
			}

			const updatedSignature = result.signature || result;

			// Optimistic Update
			setSignatures( ( prev ) =>
				prev.map( ( s ) => ( s.id === id ? updatedSignature : s ) )
			);

			return updatedSignature;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorSavingMsg ) ) {
				console.error( 'Error updating signature:', err );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ errorSavingMsg ] );

	/**
	 * Signatur löschen
	 *
	 * @param {number} id Signatur-ID
	 * @return {boolean} Erfolg
	 */
	const deleteSignature = useCallback( async ( id ) => {
		const previousSignatures = [ ...signatures ];

		try {
			setSaving( true );
			setError( null );

			// Optimistic Update
			setSignatures( ( prev ) => prev.filter( ( s ) => s.id !== id ) );

			await apiFetch( {
				path: `/recruiting/v1/signatures/${ id }`,
				method: 'DELETE',
			} );

			return true;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return false;
			}
			// Rollback
			setSignatures( previousSignatures );

			if ( ! handleApiError( err, setError, errorDeletingMsg ) ) {
				console.error( 'Error deleting signature:', err );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ signatures, errorDeletingMsg ] );

	/**
	 * Signatur als Standard setzen
	 *
	 * @param {number} id Signatur-ID
	 * @return {boolean} Erfolg
	 */
	const setDefaultSignature = useCallback( async ( id ) => {
		try {
			setSaving( true );
			setError( null );

			await apiFetch( {
				path: `/recruiting/v1/signatures/${ id }/default`,
				method: 'POST',
			} );

			if ( ! isMountedRef.current ) {
				return false;
			}

			// Update: Markiere die neue Standard-Signatur
			setSignatures( ( prev ) =>
				prev.map( ( s ) => ( {
					...s,
					is_default: s.id === id,
				} ) )
			);

			return true;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return false;
			}
			if ( ! handleApiError( err, setError, errorSavingMsg ) ) {
				console.error( 'Error setting default signature:', err );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ errorSavingMsg ] );

	/**
	 * Firmen-Signatur aktualisieren (nur für Admins)
	 *
	 * @param {Object} data Signatur-Daten
	 * @return {Object|null} Aktualisierte Signatur oder null
	 */
	const updateCompanySignature = useCallback( async ( data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/signatures/company',
				method: 'POST',
				data,
			} );

			if ( ! isMountedRef.current ) {
				return null;
			}

			const updatedSignature = result.signature || result;
			setCompanySignature( updatedSignature );

			return updatedSignature;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorSavingMsg ) ) {
				console.error( 'Error updating company signature:', err );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ errorSavingMsg ] );

	/**
	 * Signatur-Vorschau generieren
	 *
	 * @param {Object} data Signatur-Daten für Vorschau
	 * @return {string|null} HTML-Vorschau oder null
	 */
	const previewSignature = useCallback( async ( data ) => {
		try {
			const result = await apiFetch( {
				path: '/recruiting/v1/signatures/preview',
				method: 'POST',
				data,
			} );

			return result.html || null;
		} catch ( err ) {
			console.error( 'Error generating signature preview:', err );
			return null;
		}
	}, [] );

	/**
	 * Signatur-Optionen für Dropdown laden
	 *
	 * @return {Array} Signatur-Optionen [{value, label}]
	 */
	const getSignatureOptions = useCallback( async () => {
		try {
			const result = await apiFetch( {
				path: '/recruiting/v1/signatures/options',
			} );

			return result.options || [];
		} catch ( err ) {
			console.error( 'Error fetching signature options:', err );
			return [];
		}
	}, [] );

	/**
	 * Default-Signatur finden
	 *
	 * @return {Object|null} Default-Signatur oder null
	 */
	const getDefaultSignature = useCallback( () => {
		return signatures.find( ( s ) => s.is_default ) || signatures[ 0 ] || null;
	}, [ signatures ] );

	return {
		signatures,
		companySignature,
		loading,
		error,
		saving,
		fetchSignatures,
		fetchCompanySignature,
		createSignature,
		updateSignature,
		deleteSignature,
		setDefaultSignature,
		updateCompanySignature,
		previewSignature,
		getSignatureOptions,
		getDefaultSignature,
		refetch: fetchSignatures,
	};
}

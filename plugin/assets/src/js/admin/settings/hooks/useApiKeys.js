/**
 * Custom Hook für API-Key-Verwaltung
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Hook zum Laden und Verwalten der API-Keys
 *
 * @return {Object} API-Keys state und Funktionen
 */
export function useApiKeys() {
	const [ keys, setKeys ] = useState( [] );
	const [ permissions, setPermissions ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );

	const isMountedRef = useRef( true );

	/**
	 * Keys und Permissions laden
	 */
	const fetchKeys = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const [ keysData, permsData ] = await Promise.all( [
				apiFetch( { path: '/recruiting/v1/api-keys' } ),
				apiFetch( { path: '/recruiting/v1/api-keys/permissions' } ),
			] );

			if ( isMountedRef.current ) {
				setKeys( Array.isArray( keysData ) ? keysData : [] );
				setPermissions( Array.isArray( permsData ) ? permsData : [] );
			}
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || __( 'Error loading API keys', 'recruiting-playbook' ) );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [] );

	useEffect( () => {
		isMountedRef.current = true;
		fetchKeys();

		return () => {
			isMountedRef.current = false;
		};
	}, [ fetchKeys ] );

	/**
	 * Neuen Key erstellen
	 *
	 * @param {Object} data Key-Daten (name, permissions, rate_limit, expires_at).
	 * @return {Object|null} Erstellter Key mit plain_key oder null bei Fehler.
	 */
	const createKey = useCallback( async ( data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/api-keys',
				method: 'POST',
				data,
			} );

			if ( isMountedRef.current ) {
				// Key ohne plain_key zur Liste hinzufuegen.
				const { plain_key, ...keyData } = result; // eslint-disable-line no-unused-vars
				setKeys( ( prev ) => [ keyData, ...prev ] );
			}

			return result;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || __( 'Error creating', 'recruiting-playbook' ) );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [] );

	/**
	 * Key aktualisieren
	 *
	 * @param {number} id   Key-ID.
	 * @param {Object} data Felder zum Aktualisieren.
	 * @return {boolean} Erfolg.
	 */
	const updateKey = useCallback( async ( id, data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: `/recruiting/v1/api-keys/${ id }`,
				method: 'PUT',
				data,
			} );

			if ( isMountedRef.current ) {
				setKeys( ( prev ) =>
					prev.map( ( key ) =>
						key.id === id ? result : key
					)
				);
			}

			return true;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || __( 'Error updating', 'recruiting-playbook' ) );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [] );

	/**
	 * Key loeschen
	 *
	 * @param {number} id Key-ID.
	 * @return {boolean} Erfolg.
	 */
	const deleteKey = useCallback( async ( id ) => {
		try {
			setSaving( true );
			setError( null );

			await apiFetch( {
				path: `/recruiting/v1/api-keys/${ id }`,
				method: 'DELETE',
			} );

			if ( isMountedRef.current ) {
				setKeys( ( prev ) => prev.filter( ( key ) => key.id !== id ) );
			}

			return true;
		} catch ( err ) {
			if ( isMountedRef.current ) {
				setError( err?.message || __( 'Error deleting', 'recruiting-playbook' ) );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [] );

	return {
		keys,
		permissions,
		loading,
		saving,
		error,
		setError,
		createKey,
		updateKey,
		deleteKey,
		refetch: fetchKeys,
	};
}

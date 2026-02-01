/**
 * useActiveFields Hook
 *
 * Lädt die aktiven (sichtbaren) Felder aus der Published Form-Konfiguration.
 * Verwendet Caching um unnötige API-Anfragen zu vermeiden.
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Globaler Cache für aktive Felder (bleibt über Re-Renders hinweg erhalten)
let fieldsCache = null;
let cacheTimestamp = null;
const CACHE_DURATION = 5 * 60 * 1000; // 5 Minuten

/**
 * Hook zum Laden der aktiven Formularfelder
 *
 * @param {Object}  options              Hook-Optionen
 * @param {boolean} options.includeSystem System-Felder einbeziehen (default: true)
 * @param {boolean} options.forceRefresh  Cache ignorieren und neu laden (default: false)
 * @returns {Object} { fields, systemFields, loading, error, refresh }
 */
export function useActiveFields( { includeSystem = true, forceRefresh = false } = {} ) {
	const [ fields, setFields ] = useState( [] );
	const [ systemFields, setSystemFields ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const mountedRef = useRef( true );
	const currentRequestRef = useRef( 0 ); // RACE CONDITION FIX: Tracking der aktuellsten Request-ID

	const loadFields = useCallback( async ( ignoreCache = false ) => {
		// Cache prüfen
		const now = Date.now();
		if (
			! ignoreCache &&
			fieldsCache &&
			cacheTimestamp &&
			now - cacheTimestamp < CACHE_DURATION
		) {
			setFields( fieldsCache.fields || [] );
			setSystemFields( fieldsCache.system_fields || [] );
			setLoading( false );
			return;
		}

		// RACE CONDITION FIX: Unique Request-ID generieren
		const requestId = Date.now();
		currentRequestRef.current = requestId;

		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: '/recruiting/v1/form-builder/active-fields',
			} );

			// RACE CONDITION FIX: Nur aktualisieren wenn dieser Request noch der aktuellste ist
			if ( currentRequestRef.current !== requestId ) {
				return; // Neuerer Request läuft bereits
			}

			// Cache aktualisieren
			fieldsCache = data;
			cacheTimestamp = Date.now();

			if ( mountedRef.current ) {
				setFields( data.fields || [] );
				setSystemFields( data.system_fields || [] );
			}
		} catch ( err ) {
			console.error( 'Error loading active fields:', err );
			// RACE CONDITION FIX: Nur Fehler setzen wenn Request noch aktuell
			if ( currentRequestRef.current === requestId && mountedRef.current ) {
				setError( err.message || 'Fehler beim Laden der Felder' );
			}
		} finally {
			// RACE CONDITION FIX: Nur Loading-State ändern wenn Request noch aktuell
			if ( currentRequestRef.current === requestId && mountedRef.current ) {
				setLoading( false );
			}
		}
	}, [] );

	// Initial laden
	useEffect( () => {
		mountedRef.current = true;
		loadFields( forceRefresh );

		return () => {
			mountedRef.current = false;
		};
	}, [ loadFields, forceRefresh ] );

	// Refresh-Funktion
	const refresh = useCallback( () => {
		loadFields( true );
	}, [ loadFields ] );

	// Alle Felder zusammenführen wenn gewünscht
	const allFields = includeSystem
		? [
				...fields,
				...systemFields.map( ( sf ) => ( {
					field_key: sf.field_key,
					field_type: sf.type,
					label: sf.label,
					is_system: true,
					is_required: sf.field_key === 'privacy_consent',
				} ) ),
		  ]
		: fields;

	return {
		fields,
		systemFields,
		allFields,
		loading,
		error,
		refresh,
	};
}

/**
 * Cache-Invalidierung (z.B. nach Config-Publish)
 */
export function invalidateActiveFieldsCache() {
	fieldsCache = null;
	cacheTimestamp = null;
}

export default useActiveFields;

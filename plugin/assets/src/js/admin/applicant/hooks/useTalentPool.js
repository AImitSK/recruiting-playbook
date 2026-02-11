/**
 * Custom Hook für Talent-Pool
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook zum Verwalten des Talent-Pool Status eines Kandidaten
 *
 * @param {number}  candidateId Kandidaten-ID
 * @param {boolean} initialInPool Initial im Pool?
 * @return {Object} Talent-Pool state und Funktionen
 */
export function useTalentPool( candidateId, initialInPool = false ) {
	const [ isInPool, setIsInPool ] = useState( initialInPool );
	const [ entry, setEntry ] = useState( null );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	/**
	 * Status vom Server laden
	 */
	const fetchStatus = useCallback( async () => {
		if ( ! candidateId ) {
			return;
		}

		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: `/recruiting/v1/candidates/${ candidateId }/talent-pool`,
			} );

			setIsInPool( data.in_pool );
			setEntry( data.entry );
		} catch ( err ) {
			console.error( 'Error fetching talent pool status:', err );
			setError(
				err.message ||
				window.rpApplicant?.i18n?.errorLoadingTalentPool ||
				'Error loading talent pool status'
			);
		} finally {
			setLoading( false );
		}
	}, [ candidateId ] );

	// Initial laden
	useEffect( () => {
		if ( candidateId && initialInPool === undefined ) {
			fetchStatus();
		}
	}, [ candidateId, initialInPool, fetchStatus ] );

	/**
	 * Kandidat zum Talent-Pool hinzufügen
	 *
	 * @param {string} reason    Grund für Aufnahme
	 * @param {string} tags      Komma-separierte Tags
	 * @param {string} expiresAt Ablaufdatum (optional)
	 * @return {boolean} Erfolg
	 */
	const addToPool = useCallback(
		async ( reason = '', tags = '', expiresAt = null ) => {
			if ( ! candidateId ) {
				return false;
			}

			try {
				setLoading( true );
				setError( null );

				const data = {
					candidate_id: candidateId,
					reason,
					tags,
				};

				if ( expiresAt ) {
					data.expires_at = expiresAt;
				}

				const newEntry = await apiFetch( {
					path: '/recruiting/v1/talent-pool',
					method: 'POST',
					data,
				} );

				setIsInPool( true );
				setEntry( newEntry );

				return true;
			} catch ( err ) {
				console.error( 'Error adding to talent pool:', err );

				// Spezifische Fehlermeldung für "bereits im Pool"
				if ( err.code === 'already_exists' ) {
					setIsInPool( true );
					setError( null );
					return true;
				}

				setError(
					err.message ||
					window.rpApplicant?.i18n?.errorAddingToTalentPool ||
					'Error adding to talent pool'
				);
				return false;
			} finally {
				setLoading( false );
			}
		},
		[ candidateId ]
	);

	/**
	 * Kandidat aus Talent-Pool entfernen
	 *
	 * @return {boolean} Erfolg
	 */
	const removeFromPool = useCallback( async () => {
		if ( ! candidateId ) {
			return false;
		}

		// Vorherigen Zustand für Rollback speichern
		const previousInPool = isInPool;
		const previousEntry = entry;

		try {
			setLoading( true );
			setError( null );

			// Optimistic Update
			setIsInPool( false );
			setEntry( null );

			await apiFetch( {
				path: `/recruiting/v1/talent-pool/${ candidateId }`,
				method: 'DELETE',
			} );

			return true;
		} catch ( err ) {
			console.error( 'Error removing from talent pool:', err );

			// Rollback bei Fehler
			setIsInPool( previousInPool );
			setEntry( previousEntry );

			setError(
				err.message ||
				window.rpApplicant?.i18n?.errorRemovingFromTalentPool ||
				'Error removing from talent pool'
			);
			return false;
		} finally {
			setLoading( false );
		}
	}, [ candidateId, isInPool, entry ] );

	/**
	 * Eintrag aktualisieren
	 *
	 * @param {Object} data Update-Daten (reason, tags, expires_at)
	 * @return {boolean} Erfolg
	 */
	const updateEntry = useCallback(
		async ( data ) => {
			if ( ! candidateId || ! isInPool ) {
				return false;
			}

			try {
				setLoading( true );
				setError( null );

				const updatedEntry = await apiFetch( {
					path: `/recruiting/v1/talent-pool/${ candidateId }`,
					method: 'PATCH',
					data,
				} );

				setEntry( updatedEntry );

				return true;
			} catch ( err ) {
				console.error( 'Error updating talent pool entry:', err );
				setError(
					err.message ||
					window.rpApplicant?.i18n?.errorUpdatingTalentPool ||
					'Error updating talent pool entry'
				);
				return false;
			} finally {
				setLoading( false );
			}
		},
		[ candidateId, isInPool ]
	);

	return {
		isInPool,
		entry,
		loading,
		error,
		addToPool,
		removeFromPool,
		updateEntry,
		refetch: fetchStatus,
	};
}

/**
 * Hook für Talent-Pool Liste
 *
 * @param {Object} initialArgs Query-Argumente
 * @return {Object} Liste state und Funktionen
 */
export function useTalentPoolList( initialArgs = {} ) {
	const [ items, setItems ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ total, setTotal ] = useState( 0 );
	const [ totalPages, setTotalPages ] = useState( 0 );
	const [ args, setArgs ] = useState( {
		page: 1,
		per_page: 20,
		search: '',
		tags: '',
		orderby: 'created_at',
		order: 'DESC',
		...initialArgs,
	} );

	/**
	 * Liste vom Server laden
	 */
	const fetchList = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const queryParams = new URLSearchParams();
			Object.entries( args ).forEach( ( [ key, value ] ) => {
				if ( value !== '' && value !== null ) {
					queryParams.append( key, value );
				}
			} );

			const response = await apiFetch( {
				path: `/recruiting/v1/talent-pool?${ queryParams.toString() }`,
				parse: false,
			} );

			const data = await response.json();

			// Pagination aus Headers
			setTotal( parseInt( response.headers.get( 'X-WP-Total' ) || '0', 10 ) );
			setTotalPages( parseInt( response.headers.get( 'X-WP-TotalPages' ) || '0', 10 ) );
			setItems( Array.isArray( data ) ? data : [] );
		} catch ( err ) {
			console.error( 'Error fetching talent pool list:', err );
			setError(
				err.message ||
				window.rpApplicant?.i18n?.errorLoadingTalentPoolList ||
				'Error loading talent pool list'
			);
		} finally {
			setLoading( false );
		}
	}, [ args ] );

	// Bei Args-Änderung neu laden
	useEffect( () => {
		fetchList();
	}, [ fetchList ] );

	/**
	 * Suche aktualisieren
	 *
	 * @param {string} search Suchbegriff
	 */
	const setSearch = useCallback( ( search ) => {
		setArgs( ( prev ) => ( { ...prev, search, page: 1 } ) );
	}, [] );

	/**
	 * Tags-Filter aktualisieren
	 *
	 * @param {string} tags Tags
	 */
	const setTags = useCallback( ( tags ) => {
		setArgs( ( prev ) => ( { ...prev, tags, page: 1 } ) );
	}, [] );

	/**
	 * Seite ändern
	 *
	 * @param {number} page Seite
	 */
	const setPage = useCallback( ( page ) => {
		setArgs( ( prev ) => ( { ...prev, page } ) );
	}, [] );

	/**
	 * Sortierung ändern
	 *
	 * @param {string} orderby Sortierfeld
	 * @param {string} order   Sortierrichtung
	 */
	const setOrder = useCallback( ( orderby, order = 'DESC' ) => {
		setArgs( ( prev ) => ( { ...prev, orderby, order, page: 1 } ) );
	}, [] );

	return {
		items,
		loading,
		error,
		total,
		totalPages,
		page: args.page,
		search: args.search,
		tags: args.tags,
		setSearch,
		setTags,
		setPage,
		setOrder,
		refetch: fetchList,
	};
}

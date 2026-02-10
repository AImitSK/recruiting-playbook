/**
 * Custom Hook für Timeline/Activity Log
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook zum Laden und Verwalten der Timeline
 *
 * @param {number} applicationId Bewerbungs-ID
 * @param {string} filter        Kategorie-Filter ('all' oder spezifische Kategorie)
 * @return {Object} Timeline state und Funktionen
 */
export function useTimeline( applicationId, filter = 'all' ) {
	const [ items, setItems ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 0 );
	const [ total, setTotal ] = useState( 0 );

	/**
	 * Kategorie-Filter in API-Types umwandeln
	 *
	 * @param {string} categoryFilter Kategorie-Filter
	 * @return {Array} API-Type-Array
	 */
	const getTypesFromFilter = ( categoryFilter ) => {
		const typeMap = {
			status: [ 'status_changed', 'application_received' ],
			note: [ 'note_added', 'note_updated', 'note_deleted' ],
			rating: [ 'rating_added', 'rating_updated' ],
			email: [ 'email_sent' ],
			document: [ 'document_viewed', 'document_downloaded' ],
			talent_pool: [ 'talent_pool_added', 'talent_pool_removed' ],
		};

		if ( categoryFilter === 'all' || ! typeMap[ categoryFilter ] ) {
			return [];
		}

		return typeMap[ categoryFilter ];
	};

	/**
	 * Timeline vom Server laden
	 *
	 * @param {number}  pageNum      Seite
	 * @param {boolean} append       An existierende Items anhängen?
	 */
	const fetchTimeline = useCallback(
		async ( pageNum = 1, append = false ) => {
			if ( ! applicationId ) {
				setLoading( false );
				return;
			}

			try {
				setLoading( true );
				setError( null );

				const types = getTypesFromFilter( filter );
				let path = `/recruiting/v1/applications/${ applicationId }/timeline?page=${ pageNum }&per_page=20`;

				// WordPress REST API erwartet Array-Syntax: types[]=value1&types[]=value2
				if ( types.length > 0 ) {
					const typesParam = types.map( ( t ) => `types[]=${ encodeURIComponent( t ) }` ).join( '&' );
					path += `&${ typesParam }`;
				}

				const response = await apiFetch( {
					path,
					parse: false, // Um Headers zu erhalten
				} );

				const data = await response.json();

				// Pagination aus Headers lesen
				const totalFromHeader = parseInt(
					response.headers.get( 'X-WP-Total' ) || '0',
					10
				);
				const pagesFromHeader = parseInt(
					response.headers.get( 'X-WP-TotalPages' ) || '0',
					10
				);

				setTotal( totalFromHeader );
				setTotalPages( pagesFromHeader );
				setPage( pageNum );

				if ( append ) {
					setItems( ( prev ) => [ ...prev, ...( Array.isArray( data ) ? data : [] ) ] );
				} else {
					setItems( Array.isArray( data ) ? data : [] );
				}
			} catch ( err ) {
				console.error( 'Error fetching timeline:', err );
				setError(
					err.message ||
					window.rpApplicant?.i18n?.errorLoadingTimeline ||
					'Fehler beim Laden der Timeline'
				);
			} finally {
				setLoading( false );
			}
		},
		[ applicationId, filter ]
	);

	// Initial laden und bei Filter-Änderung neu laden
	useEffect( () => {
		setItems( [] );
		setPage( 1 );
		fetchTimeline( 1, false );
	}, [ fetchTimeline ] );

	/**
	 * Mehr Einträge laden
	 */
	const loadMore = useCallback( async () => {
		if ( page < totalPages && ! loading ) {
			await fetchTimeline( page + 1, true );
		}
	}, [ page, totalPages, loading, fetchTimeline ] );

	/**
	 * Timeline aktualisieren
	 */
	const refresh = useCallback( async () => {
		setItems( [] );
		setPage( 1 );
		await fetchTimeline( 1, false );
	}, [ fetchTimeline ] );

	return {
		items,
		loading,
		error,
		total,
		page,
		totalPages,
		hasMore: page < totalPages,
		loadMore,
		refresh,
		refetch: refresh,
	};
}

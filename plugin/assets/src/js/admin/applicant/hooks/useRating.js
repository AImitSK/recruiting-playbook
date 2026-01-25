/**
 * Custom Hook für Bewertungen
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook zum Laden und Verwalten von Bewertungen
 *
 * @param {number} applicationId Bewerbungs-ID
 * @return {Object} Rating state und Funktionen
 */
export function useRating( applicationId ) {
	const [ summary, setSummary ] = useState( null );
	const [ userRatings, setUserRatings ] = useState( {} );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	/**
	 * Bewertungs-Zusammenfassung vom Server laden
	 */
	const fetchSummary = useCallback( async () => {
		if ( ! applicationId ) {
			setLoading( false );
			return;
		}

		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: `/recruiting/v1/applications/${ applicationId }/rating-summary`,
			} );

			setSummary( data );
			setUserRatings( data.user_rating || {} );
		} catch ( err ) {
			console.error( 'Error fetching rating summary:', err );
			setError(
				err.message ||
				window.rpApplicant?.i18n?.errorLoadingRating ||
				'Fehler beim Laden der Bewertung'
			);
		} finally {
			setLoading( false );
		}
	}, [ applicationId ] );

	// Initial laden
	useEffect( () => {
		fetchSummary();
	}, [ fetchSummary ] );

	/**
	 * Bewertung abgeben
	 *
	 * @param {string} category Kategorie (overall, skills, culture_fit, experience)
	 * @param {number} rating   Bewertung (1-5)
	 * @return {boolean} Erfolg
	 */
	const rate = useCallback(
		async ( category, rating ) => {
			if ( ! applicationId || rating < 1 || rating > 5 ) {
				return false;
			}

			// Vorherige Werte für Rollback speichern
			const previousUserRatings = { ...userRatings };
			const previousSummary = summary ? { ...summary } : null;

			try {
				setSaving( true );
				setError( null );

				// Optimistic Update für User-Rating
				setUserRatings( ( prev ) => ( {
					...prev,
					[ category ]: rating,
				} ) );

				const newSummary = await apiFetch( {
					path: `/recruiting/v1/applications/${ applicationId }/ratings`,
					method: 'POST',
					data: {
						rating,
						category,
					},
				} );

				setSummary( newSummary );
				setUserRatings( newSummary.user_rating || {} );

				return true;
			} catch ( err ) {
				console.error( 'Error rating:', err );

				// Rollback bei Fehler
				setUserRatings( previousUserRatings );
				if ( previousSummary ) {
					setSummary( previousSummary );
				}

				setError(
					err.message ||
					window.rpApplicant?.i18n?.errorRating ||
					'Fehler beim Bewerten'
				);
				return false;
			} finally {
				setSaving( false );
			}
		},
		[ applicationId, userRatings, summary ]
	);

	/**
	 * Bewertung löschen
	 *
	 * @param {string} category Kategorie
	 * @return {boolean} Erfolg
	 */
	const deleteRating = useCallback(
		async ( category ) => {
			if ( ! applicationId || ! category ) {
				return false;
			}

			// Vorherige Werte für Rollback speichern
			const previousUserRatings = { ...userRatings };

			try {
				setSaving( true );
				setError( null );

				// Optimistic Update
				setUserRatings( ( prev ) => {
					const updated = { ...prev };
					delete updated[ category ];
					return updated;
				} );

				await apiFetch( {
					path: `/recruiting/v1/applications/${ applicationId }/ratings/${ category }`,
					method: 'DELETE',
				} );

				// Zusammenfassung neu laden
				await fetchSummary();

				return true;
			} catch ( err ) {
				console.error( 'Error deleting rating:', err );

				// Rollback bei Fehler
				setUserRatings( previousUserRatings );

				setError(
					err.message ||
					window.rpApplicant?.i18n?.errorDeletingRating ||
					'Fehler beim Löschen der Bewertung'
				);
				return false;
			} finally {
				setSaving( false );
			}
		},
		[ applicationId, userRatings, fetchSummary ]
	);

	return {
		summary,
		userRatings,
		loading,
		error,
		saving,
		rate,
		deleteRating,
		refetch: fetchSummary,
	};
}

/**
 * Custom Hook for ratings
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook for loading and managing ratings
 *
 * @param {number} applicationId Application ID
 * @return {Object} Rating state and functions
 */
export function useRating( applicationId ) {
	const [ summary, setSummary ] = useState( null );
	const [ userRatings, setUserRatings ] = useState( {} );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	/**
	 * Load rating summary from server
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
				'Error loading rating'
			);
		} finally {
			setLoading( false );
		}
	}, [ applicationId ] );

	// Initial load
	useEffect( () => {
		fetchSummary();
	}, [ fetchSummary ] );

	/**
	 * Submit a rating
	 *
	 * @param {string} category Category (overall, skills, culture_fit, experience)
	 * @param {number} rating   Rating (1-5)
	 * @return {boolean} Success
	 */
	const rate = useCallback(
		async ( category, rating ) => {
			if ( ! applicationId || rating < 1 || rating > 5 ) {
				return false;
			}

			// Save previous values for rollback
			const previousUserRatings = { ...userRatings };
			const previousSummary = summary ? { ...summary } : null;

			try {
				setSaving( true );
				setError( null );

				// Optimistic Update for user rating
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

				// Rollback on error
				setUserRatings( previousUserRatings );
				if ( previousSummary ) {
					setSummary( previousSummary );
				}

				setError(
					err.message ||
					window.rpApplicant?.i18n?.errorRating ||
					'Error rating'
				);
				return false;
			} finally {
				setSaving( false );
			}
		},
		[ applicationId, userRatings, summary ]
	);

	/**
	 * Delete a rating
	 *
	 * @param {string} category Category
	 * @return {boolean} Success
	 */
	const deleteRating = useCallback(
		async ( category ) => {
			if ( ! applicationId || ! category ) {
				return false;
			}

			// Save previous values for rollback
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

				// Reload summary
				await fetchSummary();

				return true;
			} catch ( err ) {
				console.error( 'Error deleting rating:', err );

				// Rollback on error
				setUserRatings( previousUserRatings );

				setError(
					err.message ||
					window.rpApplicant?.i18n?.errorDeletingRating ||
					'Error deleting rating'
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

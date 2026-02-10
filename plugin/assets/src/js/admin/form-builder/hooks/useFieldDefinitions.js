/**
 * useFieldDefinitions Hook
 *
 * React hook for managing field definitions via REST API.
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Custom hook for field definitions management
 *
 * @return {Object} Field definitions state and actions
 */
export function useFieldDefinitions() {
	const [ fields, setFields ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	const config = window.rpFormBuilderData || {};
	const namespace = config.restNamespace || 'recruiting/v1';

	// Separate fields into system and custom
	const systemFields = fields.filter( ( f ) => f.is_system );
	const customFields = fields.filter( ( f ) => ! f.is_system );

	/**
	 * Fetch all fields from API
	 */
	const fetchFields = useCallback( async () => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `${ namespace }/fields`,
				method: 'GET',
			} );

			// API returns { fields: [...] }
			setFields( response?.fields || response || [] );
		} catch ( err ) {
			setError( err.message || 'Failed to load fields' );
			console.error( 'Failed to fetch fields:', err );
		} finally {
			setIsLoading( false );
		}
	}, [ namespace ] );

	/**
	 * Create a new field
	 *
	 * @param {Object} fieldData Field data to create
	 * @return {Object|null} Created field or null on error
	 */
	const createField = useCallback(
		async ( fieldData ) => {
			try {
				const response = await apiFetch( {
					path: `${ namespace }/fields`,
					method: 'POST',
					data: fieldData,
				} );

				if ( response ) {
					setFields( ( prev ) => [ ...prev, response ] );
					return response;
				}
			} catch ( err ) {
				setError( err.message || 'Failed to create field' );
				console.error( 'Failed to create field:', err );
			}
			return null;
		},
		[ namespace ]
	);

	/**
	 * Update an existing field
	 *
	 * @param {number} fieldId Field ID to update
	 * @param {Object} updates  Field data updates
	 * @return {boolean} Success status
	 */
	const updateField = useCallback(
		async ( fieldId, updates ) => {
			try {
				const response = await apiFetch( {
					path: `${ namespace }/fields/${ fieldId }`,
					method: 'PUT',
					data: updates,
				} );

				if ( response ) {
					setFields( ( prev ) =>
						prev.map( ( f ) =>
							f.id === fieldId ? { ...f, ...response } : f
						)
					);
					return true;
				}
			} catch ( err ) {
				setError( err.message || 'Failed to update field' );
				console.error( 'Failed to update field:', err );
			}
			return false;
		},
		[ namespace ]
	);

	/**
	 * Delete a field
	 *
	 * @param {number} fieldId Field ID to delete
	 * @return {boolean} Success status
	 */
	const deleteField = useCallback(
		async ( fieldId ) => {
			try {
				await apiFetch( {
					path: `${ namespace }/fields/${ fieldId }`,
					method: 'DELETE',
				} );

				setFields( ( prev ) => prev.filter( ( f ) => f.id !== fieldId ) );
				return true;
			} catch ( err ) {
				setError( err.message || 'Failed to delete field' );
				console.error( 'Failed to delete field:', err );
			}
			return false;
		},
		[ namespace ]
	);

	/**
	 * Reorder fields
	 *
	 * @param {number[]} orderedIds Array of field IDs in new order
	 * @return {boolean} Success status
	 */
	const reorderFields = useCallback(
		async ( orderedIds ) => {
			try {
				await apiFetch( {
					path: `${ namespace }/fields/reorder`,
					method: 'POST',
					data: { field_ids: orderedIds },
				} );

				// Update local state to reflect new order
				setFields( ( prev ) => {
					const fieldMap = {};
					prev.forEach( ( f ) => {
						fieldMap[ f.id ] = f;
					} );

					return orderedIds
						.map( ( id, index ) => ( {
							...fieldMap[ id ],
							sort_order: index,
						} ) )
						.filter( Boolean );
				} );

				return true;
			} catch ( err ) {
				setError( err.message || 'Failed to reorder fields' );
				console.error( 'Failed to reorder fields:', err );
			}
			return false;
		},
		[ namespace ]
	);

	/**
	 * Get field by ID
	 *
	 * @param {number} fieldId Field ID
	 * @return {Object|null} Field object or null
	 */
	const getFieldById = useCallback(
		( fieldId ) => {
			return fields.find( ( f ) => f.id === fieldId ) || null;
		},
		[ fields ]
	);

	/**
	 * Get field by key
	 *
	 * @param {string} fieldKey Field key
	 * @return {Object|null} Field object or null
	 */
	const getFieldByKey = useCallback(
		( fieldKey ) => {
			return fields.find( ( f ) => f.field_key === fieldKey ) || null;
		},
		[ fields ]
	);

	// Load fields on mount
	useEffect( () => {
		// Use pre-loaded data if available
		if ( config.currentFields && config.currentFields.length > 0 ) {
			setFields( config.currentFields );
			setIsLoading( false );
		} else {
			fetchFields();
		}
	}, [] );

	return {
		fields,
		systemFields,
		customFields,
		isLoading,
		error,
		createField,
		updateField,
		deleteField,
		reorderFields,
		getFieldById,
		getFieldByKey,
		refetch: fetchFields,
	};
}

export default useFieldDefinitions;

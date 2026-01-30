/**
 * useFormTemplates Hook
 *
 * React hook for managing form templates via REST API.
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Custom hook for form templates management
 *
 * @return {Object} Form templates state and actions
 */
export function useFormTemplates() {
	const [ templates, setTemplates ] = useState( [] );
	const [ defaultTemplate, setDefaultTemplateState ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	const config = window.rpFormBuilderData || {};
	const namespace = config.restNamespace || 'recruiting/v1';

	/**
	 * Fetch all templates from API
	 */
	const fetchTemplates = useCallback( async () => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `${ namespace }/form-templates`,
				method: 'GET',
			} );

			// API returns { templates: [...] }
			const templateList = response?.templates || response || [];
			setTemplates( templateList );

			// Find default template
			const defaultTpl = templateList.find?.( ( t ) => t.is_default );
			setDefaultTemplateState( defaultTpl || null );
		} catch ( err ) {
			setError( err.message || 'Failed to load templates' );
			console.error( 'Failed to fetch templates:', err );
		} finally {
			setIsLoading( false );
		}
	}, [ namespace ] );

	/**
	 * Create a new template
	 *
	 * @param {Object} templateData Template data to create
	 * @return {Object|null} Created template or null on error
	 */
	const createTemplate = useCallback(
		async ( templateData ) => {
			try {
				const response = await apiFetch( {
					path: `${ namespace }/form-templates`,
					method: 'POST',
					data: templateData,
				} );

				if ( response ) {
					setTemplates( ( prev ) => [ ...prev, response ] );

					if ( response.is_default ) {
						setDefaultTemplateState( response );
					}

					return response;
				}
			} catch ( err ) {
				setError( err.message || 'Failed to create template' );
				console.error( 'Failed to create template:', err );
			}
			return null;
		},
		[ namespace ]
	);

	/**
	 * Update an existing template
	 *
	 * @param {number} templateId Template ID to update
	 * @param {Object} updates    Template data updates
	 * @return {boolean} Success status
	 */
	const updateTemplate = useCallback(
		async ( templateId, updates ) => {
			try {
				const response = await apiFetch( {
					path: `${ namespace }/form-templates/${ templateId }`,
					method: 'PUT',
					data: updates,
				} );

				if ( response ) {
					setTemplates( ( prev ) =>
						prev.map( ( t ) =>
							t.id === templateId ? { ...t, ...response } : t
						)
					);

					if ( response.is_default ) {
						setDefaultTemplateState( response );
					}

					return true;
				}
			} catch ( err ) {
				setError( err.message || 'Failed to update template' );
				console.error( 'Failed to update template:', err );
			}
			return false;
		},
		[ namespace ]
	);

	/**
	 * Delete a template
	 *
	 * @param {number} templateId Template ID to delete
	 * @return {boolean} Success status
	 */
	const deleteTemplate = useCallback(
		async ( templateId ) => {
			try {
				await apiFetch( {
					path: `${ namespace }/form-templates/${ templateId }`,
					method: 'DELETE',
				} );

				setTemplates( ( prev ) =>
					prev.filter( ( t ) => t.id !== templateId )
				);

				if ( defaultTemplate?.id === templateId ) {
					setDefaultTemplateState( null );
				}

				return true;
			} catch ( err ) {
				setError( err.message || 'Failed to delete template' );
				console.error( 'Failed to delete template:', err );
			}
			return false;
		},
		[ namespace, defaultTemplate ]
	);

	/**
	 * Set a template as default
	 *
	 * @param {number} templateId Template ID to set as default
	 * @return {boolean} Success status
	 */
	const setDefaultTemplate = useCallback(
		async ( templateId ) => {
			try {
				const response = await apiFetch( {
					path: `${ namespace }/form-templates/${ templateId }/set-default`,
					method: 'POST',
				} );

				if ( response ) {
					// Update all templates to reflect new default
					setTemplates( ( prev ) =>
						prev.map( ( t ) => ( {
							...t,
							is_default: t.id === templateId,
						} ) )
					);

					setDefaultTemplateState(
						templates.find( ( t ) => t.id === templateId ) || response
					);

					return true;
				}
			} catch ( err ) {
				setError( err.message || 'Failed to set default template' );
				console.error( 'Failed to set default template:', err );
			}
			return false;
		},
		[ namespace, templates ]
	);

	/**
	 * Duplicate a template
	 *
	 * @param {number} templateId Template ID to duplicate
	 * @return {Object|null} Duplicated template or null on error
	 */
	const duplicateTemplate = useCallback(
		async ( templateId ) => {
			try {
				const response = await apiFetch( {
					path: `${ namespace }/form-templates/${ templateId }/duplicate`,
					method: 'POST',
				} );

				if ( response ) {
					setTemplates( ( prev ) => [ ...prev, response ] );
					return response;
				}
			} catch ( err ) {
				setError( err.message || 'Failed to duplicate template' );
				console.error( 'Failed to duplicate template:', err );
			}
			return null;
		},
		[ namespace ]
	);

	/**
	 * Get template by ID
	 *
	 * @param {number} templateId Template ID
	 * @return {Object|null} Template object or null
	 */
	const getTemplateById = useCallback(
		( templateId ) => {
			return templates.find( ( t ) => t.id === templateId ) || null;
		},
		[ templates ]
	);

	// Load templates on mount
	useEffect( () => {
		// Use pre-loaded data if available
		if ( config.templates && config.templates.length > 0 ) {
			setTemplates( config.templates );
			setDefaultTemplateState( config.defaultTemplate || null );
			setIsLoading( false );
		} else {
			fetchTemplates();
		}
	}, [] );

	return {
		templates,
		defaultTemplate,
		isLoading,
		error,
		createTemplate,
		updateTemplate,
		deleteTemplate,
		setDefaultTemplate,
		duplicateTemplate,
		getTemplateById,
		refetch: fetchTemplates,
	};
}

export default useFormTemplates;

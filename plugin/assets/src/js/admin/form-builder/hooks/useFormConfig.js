/**
 * useFormConfig Hook
 *
 * React hook for managing step-based form configuration via REST API.
 * Implements Draft/Publish workflow with optimistic updates.
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Generate unique ID for new steps
 *
 * @return {string} Unique step ID
 */
const generateStepId = () => `step_${ Date.now() }_${ Math.random().toString( 36 ).substr( 2, 9 ) }`;

/**
 * Custom hook for form configuration management
 *
 * @return {Object} Form config state and actions
 */
export function useFormConfig() {
	// Configuration state
	const [ draft, setDraft ] = useState( null );
	const [ availableFields, setAvailableFields ] = useState( [] );
	const [ publishedVersion, setPublishedVersion ] = useState( 1 );
	const [ hasChanges, setHasChanges ] = useState( false );

	// UI state
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isPublishing, setIsPublishing ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ successMessage, setSuccessMessage ] = useState( null );

	// Debounce timer for auto-save
	const saveTimerRef = useRef( null );

	const config = window.rpFormBuilderData || {};
	const namespace = config.restNamespace || 'recruiting/v1';

	/**
	 * Get steps from draft (excluding finale step for sorting)
	 */
	const regularSteps = draft?.steps?.filter( ( s ) => ! s.is_finale ) || [];
	const finaleStep = draft?.steps?.find( ( s ) => s.is_finale ) || null;

	/**
	 * Get settings from draft
	 */
	const settings = draft?.settings || {};

	/**
	 * Clear success message after delay
	 */
	const showSuccess = useCallback( ( message ) => {
		setSuccessMessage( message );
		setTimeout( () => setSuccessMessage( null ), 3000 );
	}, [] );

	/**
	 * Fetch config from API
	 */
	const fetchConfig = useCallback( async () => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `${ namespace }/form-builder/config`,
				method: 'GET',
			} );

			setDraft( response.draft || null );
			setAvailableFields( response.available_fields || [] );
			setPublishedVersion( response.published_version || 1 );
			setHasChanges( response.has_changes || false );
		} catch ( err ) {
			setError( err.message || __( 'Error loading configuration', 'recruiting-playbook' ) );
			console.error( 'Failed to fetch form config:', err );
		} finally {
			setIsLoading( false );
		}
	}, [ namespace ] );

	/**
	 * Refresh available fields without affecting draft state
	 *
	 * Call this after creating/deleting fields in FieldDefinitions
	 * to keep the availableFields list in sync.
	 */
	const refreshAvailableFields = useCallback( async () => {
		try {
			// Add cache-busting timestamp to prevent stale data
			const response = await apiFetch( {
				path: `${ namespace }/form-builder/config?_=${ Date.now() }`,
				method: 'GET',
			} );

			setAvailableFields( response.available_fields || [] );
		} catch ( err ) {
			console.error( 'Failed to refresh available fields:', err );
		}
	}, [ namespace ] );

	/**
	 * Save draft to API
	 *
	 * @param {Object} configData Optional config data, uses current draft if not provided
	 * @return {boolean} Success status
	 */
	const saveDraft = useCallback(
		async ( configData = null ) => {
			const dataToSave = configData || draft;
			if ( ! dataToSave ) {
				return false;
			}

			setIsSaving( true );
			setError( null );

			try {
				const response = await apiFetch( {
					path: `${ namespace }/form-builder/config`,
					method: 'PUT',
					data: {
						steps: dataToSave.steps,
						settings: dataToSave.settings,
						version: dataToSave.version,
					},
				} );

				setHasChanges( response.has_changes ?? true );
				showSuccess( __( 'Draft saved', 'recruiting-playbook' ) );
				return true;
			} catch ( err ) {
				setError( err.message || __( 'Error saving', 'recruiting-playbook' ) );
				console.error( 'Failed to save draft:', err );
				return false;
			} finally {
				setIsSaving( false );
			}
		},
		[ namespace, draft, showSuccess ]
	);

	/**
	 * Debounced save - auto-saves after changes
	 */
	const debouncedSave = useCallback(
		( newDraft ) => {
			if ( saveTimerRef.current ) {
				clearTimeout( saveTimerRef.current );
			}

			saveTimerRef.current = setTimeout( () => {
				saveDraft( newDraft );
			}, 1500 );
		},
		[ saveDraft ]
	);

	/**
	 * Update draft and trigger auto-save
	 *
	 * @param {Object|Function} updater New draft or updater function
	 */
	const updateDraft = useCallback(
		( updater ) => {
			setDraft( ( prev ) => {
				const newDraft =
					typeof updater === 'function' ? updater( prev ) : updater;
				setHasChanges( true );
				debouncedSave( newDraft );
				return newDraft;
			} );
		},
		[ debouncedSave ]
	);

	/**
	 * Publish draft
	 *
	 * @return {boolean} Success status
	 */
	const publish = useCallback( async () => {
		// Cancel any pending saves first
		if ( saveTimerRef.current ) {
			clearTimeout( saveTimerRef.current );
		}

		// Save current draft before publishing
		await saveDraft();

		setIsPublishing( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `${ namespace }/form-builder/publish`,
				method: 'POST',
			} );

			setPublishedVersion( response.published_version || publishedVersion + 1 );
			setHasChanges( false );
			showSuccess( __( 'Form published', 'recruiting-playbook' ) );
			return true;
		} catch ( err ) {
			setError( err.message || __( 'Error publishing', 'recruiting-playbook' ) );
			console.error( 'Failed to publish:', err );
			return false;
		} finally {
			setIsPublishing( false );
		}
	}, [ namespace, publishedVersion, saveDraft, showSuccess ] );

	/**
	 * Discard draft changes
	 *
	 * @return {boolean} Success status
	 */
	const discardDraft = useCallback( async () => {
		// Cancel any pending saves
		if ( saveTimerRef.current ) {
			clearTimeout( saveTimerRef.current );
		}

		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `${ namespace }/form-builder/discard`,
				method: 'POST',
			} );

			setDraft( response.draft || null );
			setHasChanges( false );
			showSuccess( __( 'Changes discarded', 'recruiting-playbook' ) );
			return true;
		} catch ( err ) {
			setError( err.message || __( 'Error discarding', 'recruiting-playbook' ) );
			console.error( 'Failed to discard draft:', err );
			return false;
		} finally {
			setIsLoading( false );
		}
	}, [ namespace, showSuccess ] );

	/**
	 * Reset to default configuration
	 *
	 * @return {boolean} Success status
	 */
	const resetToDefault = useCallback( async () => {
		// Cancel any pending saves
		if ( saveTimerRef.current ) {
			clearTimeout( saveTimerRef.current );
		}

		// Confirmation dialog
		const confirmed = window.confirm(
			__( 'Do you really want to reset the form to default settings? All customizations will be lost.', 'recruiting-playbook' )
		);

		if ( ! confirmed ) {
			return false;
		}

		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `${ namespace }/form-builder/reset`,
				method: 'POST',
			} );

			setDraft( response.draft || null );
			setHasChanges( false );
			setPublishedVersion( 1 );
			showSuccess( __( 'Form reset to defaults', 'recruiting-playbook' ) );
			return true;
		} catch ( err ) {
			setError( err.message || __( 'Error resetting', 'recruiting-playbook' ) );
			console.error( 'Failed to reset config:', err );
			return false;
		} finally {
			setIsLoading( false );
		}
	}, [ namespace, showSuccess ] );

	/**
	 * Add a new step
	 *
	 * @param {Object} stepData Optional step data
	 * @return {string} New step ID
	 */
	const addStep = useCallback(
		( stepData = {} ) => {
			const newStepId = generateStepId();
			const maxPosition = Math.max(
				...regularSteps.map( ( s ) => s.position || 0 ),
				0
			);

			const newStep = {
				id: newStepId,
				title: stepData.title || __( 'New Step', 'recruiting-playbook' ),
				position: maxPosition + 1,
				deletable: true,
				is_finale: false,
				fields: [],
				...stepData,
			};

			updateDraft( ( prev ) => ( {
				...prev,
				steps: [
					...( prev?.steps?.filter( ( s ) => ! s.is_finale ) || [] ),
					newStep,
					...( prev?.steps?.filter( ( s ) => s.is_finale ) || [] ),
				],
			} ) );

			return newStepId;
		},
		[ regularSteps, updateDraft ]
	);

	/**
	 * Update a step
	 *
	 * @param {string} stepId   Step ID to update
	 * @param {Object} updates  Step updates
	 */
	const updateStep = useCallback(
		( stepId, updates ) => {
			updateDraft( ( prev ) => ( {
				...prev,
				steps: prev?.steps?.map( ( step ) =>
					step.id === stepId ? { ...step, ...updates } : step
				),
			} ) );
		},
		[ updateDraft ]
	);

	/**
	 * Remove a step
	 *
	 * @param {string} stepId Step ID to remove
	 * @return {boolean} Success status
	 */
	const removeStep = useCallback(
		( stepId ) => {
			const step = draft?.steps?.find( ( s ) => s.id === stepId );

			if ( ! step || ! step.deletable || step.is_finale ) {
				setError( __( 'This step cannot be deleted', 'recruiting-playbook' ) );
				return false;
			}

			updateDraft( ( prev ) => ( {
				...prev,
				steps: prev?.steps?.filter( ( s ) => s.id !== stepId ),
			} ) );

			return true;
		},
		[ draft, updateDraft ]
	);

	/**
	 * Reorder steps
	 *
	 * @param {string[]} orderedIds Array of step IDs in new order (without finale)
	 */
	const reorderSteps = useCallback(
		( orderedIds ) => {
			updateDraft( ( prev ) => {
				const stepMap = {};
				prev?.steps?.forEach( ( s ) => {
					stepMap[ s.id ] = s;
				} );

				const reorderedRegular = orderedIds
					.map( ( id, index ) => ( {
						...stepMap[ id ],
						position: index + 1,
					} ) )
					.filter( Boolean );

				const finale = prev?.steps?.filter( ( s ) => s.is_finale ) || [];

				return {
					...prev,
					steps: [ ...reorderedRegular, ...finale ],
				};
			} );
		},
		[ updateDraft ]
	);

	/**
	 * Add a field to a step
	 *
	 * @param {string} stepId   Step ID
	 * @param {string} fieldKey Field key to add
	 * @param {Object} options  Field options (is_visible, is_required)
	 */
	const addFieldToStep = useCallback(
		( stepId, fieldKey, options = {} ) => {
			updateDraft( ( prev ) => ( {
				...prev,
				steps: prev?.steps?.map( ( step ) => {
					if ( step.id !== stepId ) {
						return step;
					}

					// Check if field already exists in this step
					const exists = step.fields?.some(
						( f ) => f.field_key === fieldKey
					);
					if ( exists ) {
						return step;
					}

					return {
						...step,
						fields: [
							...( step.fields || [] ),
							{
								field_key: fieldKey,
								is_visible: options.is_visible ?? true,
								is_required: options.is_required ?? false,
							},
						],
					};
				} ),
			} ) );
		},
		[ updateDraft ]
	);

	/**
	 * Remove a field from a step
	 *
	 * Prevents removal of non-removable fields (first_name, last_name, email, privacy_consent).
	 *
	 * @param {string} stepId   Step ID
	 * @param {string} fieldKey Field key to remove
	 * @return {boolean} Success status
	 */
	const removeFieldFromStep = useCallback(
		( stepId, fieldKey ) => {
			// Find the field to check if it's removable
			const step = draft?.steps?.find( ( s ) => s.id === stepId );
			const field = step?.fields?.find( ( f ) => f.field_key === fieldKey );

			// Block removal of non-removable fields
			if ( field && field.is_removable === false ) {
				setError( 'Dieses Pflichtfeld kann nicht entfernt werden' );
				return false;
			}

			updateDraft( ( prev ) => ( {
				...prev,
				steps: prev?.steps?.map( ( s ) => {
					if ( s.id !== stepId ) {
						return s;
					}

					return {
						...s,
						fields: s.fields?.filter(
							( f ) => f.field_key !== fieldKey
						),
					};
				} ),
			} ) );

			return true;
		},
		[ draft, updateDraft ]
	);

	/**
	 * Update a field within a step
	 *
	 * @param {string} stepId   Step ID
	 * @param {string} fieldKey Field key to update
	 * @param {Object} updates  Field updates
	 */
	const updateFieldInStep = useCallback(
		( stepId, fieldKey, updates ) => {
			updateDraft( ( prev ) => ( {
				...prev,
				steps: prev?.steps?.map( ( step ) => {
					if ( step.id !== stepId ) {
						return step;
					}

					return {
						...step,
						fields: step.fields?.map( ( field ) =>
							field.field_key === fieldKey
								? { ...field, ...updates }
								: field
						),
					};
				} ),
			} ) );
		},
		[ updateDraft ]
	);

	/**
	 * Update a system field within a step
	 *
	 * System fields (file_upload, summary, privacy_consent) have their own
	 * settings that can be configured.
	 *
	 * @param {string} stepId   Step ID
	 * @param {string} fieldKey System field key to update
	 * @param {Object} updates  Settings updates
	 */
	const updateSystemFieldInStep = useCallback(
		( stepId, fieldKey, updates ) => {
			updateDraft( ( prev ) => ( {
				...prev,
				steps: prev?.steps?.map( ( step ) => {
					if ( step.id !== stepId ) {
						return step;
					}

					// Ensure system_fields exists and is an array
					if ( ! step.system_fields || ! Array.isArray( step.system_fields ) ) {
						return step;
					}

					return {
						...step,
						system_fields: step.system_fields.map( ( systemField ) =>
							systemField.field_key === fieldKey
								? {
										...systemField,
										settings: {
											...( systemField.settings || {} ),
											...updates,
										},
								  }
								: systemField
						),
					};
				} ),
			} ) );
		},
		[ updateDraft ]
	);

	/**
	 * Move a field between steps
	 *
	 * @param {string} fromStepId  Source step ID
	 * @param {string} toStepId    Target step ID
	 * @param {string} fieldKey    Field key to move
	 * @param {number} targetIndex Position in target step
	 */
	const moveFieldBetweenSteps = useCallback(
		( fromStepId, toStepId, fieldKey, targetIndex = -1 ) => {
			updateDraft( ( prev ) => {
				// Find the field in source step
				const sourceStep = prev?.steps?.find( ( s ) => s.id === fromStepId );
				const fieldToMove = sourceStep?.fields?.find(
					( f ) => f.field_key === fieldKey
				);

				if ( ! fieldToMove ) {
					return prev;
				}

				return {
					...prev,
					steps: prev?.steps?.map( ( step ) => {
						if ( step.id === fromStepId ) {
							// Remove from source
							return {
								...step,
								fields: step.fields?.filter(
									( f ) => f.field_key !== fieldKey
								),
							};
						}

						if ( step.id === toStepId ) {
							// Add to target
							const newFields = [ ...( step.fields || [] ) ];
							const insertAt =
								targetIndex === -1 ? newFields.length : targetIndex;
							newFields.splice( insertAt, 0, fieldToMove );
							return {
								...step,
								fields: newFields,
							};
						}

						return step;
					} ),
				};
			} );
		},
		[ updateDraft ]
	);

	/**
	 * Reorder fields within a step
	 *
	 * @param {string}   stepId     Step ID
	 * @param {string[]} fieldKeys  Ordered field keys
	 */
	const reorderFieldsInStep = useCallback(
		( stepId, fieldKeys ) => {
			updateDraft( ( prev ) => ( {
				...prev,
				steps: prev?.steps?.map( ( step ) => {
					if ( step.id !== stepId ) {
						return step;
					}

					const fieldMap = {};
					step.fields?.forEach( ( f ) => {
						fieldMap[ f.field_key ] = f;
					} );

					return {
						...step,
						fields: fieldKeys
							.map( ( key ) => fieldMap[ key ] )
							.filter( Boolean ),
					};
				} ),
			} ) );
		},
		[ updateDraft ]
	);

	/**
	 * Update form settings
	 *
	 * @param {Object} newSettings Settings to merge
	 */
	const updateSettings = useCallback(
		( newSettings ) => {
			updateDraft( ( prev ) => ( {
				...prev,
				settings: {
					...( prev?.settings || {} ),
					...newSettings,
				},
			} ) );
		},
		[ updateDraft ]
	);

	/**
	 * Get all fields currently used in any step
	 *
	 * @return {string[]} Array of field keys
	 */
	const getUsedFieldKeys = useCallback( () => {
		const usedKeys = new Set();
		draft?.steps?.forEach( ( step ) => {
			step.fields?.forEach( ( field ) => {
				usedKeys.add( field.field_key );
			} );
		} );
		return Array.from( usedKeys );
	}, [ draft ] );

	/**
	 * Get available fields that are not yet used
	 *
	 * @return {Object[]} Array of unused field definitions
	 */
	const getUnusedFields = useCallback( () => {
		const usedKeys = getUsedFieldKeys();
		return availableFields.filter( ( f ) => ! usedKeys.includes( f.field_key ) );
	}, [ availableFields, getUsedFieldKeys ] );

	/**
	 * Get field definition by key
	 *
	 * @param {string} fieldKey Field key
	 * @return {Object|null} Field definition or null
	 */
	const getFieldDefinition = useCallback(
		( fieldKey ) => {
			return availableFields.find( ( f ) => f.field_key === fieldKey ) || null;
		},
		[ availableFields ]
	);

	// Load config on mount
	useEffect( () => {
		fetchConfig();

		// Cleanup debounce timer on unmount
		return () => {
			if ( saveTimerRef.current ) {
				clearTimeout( saveTimerRef.current );
			}
		};
	}, [] );

	return {
		// State
		draft,
		steps: draft?.steps || [],
		regularSteps,
		finaleStep,
		settings,
		availableFields,
		publishedVersion,
		hasChanges,

		// UI state
		isLoading,
		isSaving,
		isPublishing,
		error,
		successMessage,

		// Actions
		fetchConfig,
		refreshAvailableFields,
		saveDraft,
		publish,
		discardDraft,
		resetToDefault,

		// Step operations
		addStep,
		updateStep,
		removeStep,
		reorderSteps,

		// Field operations
		addFieldToStep,
		removeFieldFromStep,
		updateFieldInStep,
		updateSystemFieldInStep,
		moveFieldBetweenSteps,
		reorderFieldsInStep,

		// Settings
		updateSettings,

		// Helpers
		getUsedFieldKeys,
		getUnusedFields,
		getFieldDefinition,
	};
}

export default useFormConfig;

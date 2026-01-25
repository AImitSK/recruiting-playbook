/**
 * Custom Hook für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { handleApiError } from '../utils';

/**
 * Hook zum Laden und Verwalten von E-Mail-Templates
 *
 * @param {Object} options         Optionen
 * @param {string} options.category Kategorie-Filter
 * @return {Object} Templates state und Funktionen
 */
export function useTemplates( options = {} ) {
	const [ templates, setTemplates ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );

	// Stabile Referenzen für Dependencies
	const category = options?.category;
	const i18n = window.rpEmailData?.i18n || {};
	const errorLoadingMsg = i18n.errorLoading || 'Fehler beim Laden der Templates';
	const errorSavingMsg = i18n.errorSaving || 'Fehler beim Speichern';
	const errorDeletingMsg = i18n.errorDeleting || 'Fehler beim Löschen';

	// Refs für Cleanup und Mount-Status
	const abortControllerRef = useRef( null );
	const isMountedRef = useRef( true );

	/**
	 * Templates vom Server laden
	 */
	const fetchTemplates = useCallback( async () => {
		// Vorherigen Request abbrechen
		if ( abortControllerRef.current ) {
			abortControllerRef.current.abort();
		}
		abortControllerRef.current = new AbortController();

		try {
			setLoading( true );
			setError( null );

			let path = '/recruiting/v1/email-templates';
			const params = new URLSearchParams();

			if ( category ) {
				params.append( 'category', category );
			}

			if ( params.toString() ) {
				path += '?' + params.toString();
			}

			const data = await apiFetch( {
				path,
				signal: abortControllerRef.current.signal,
			} );

			// Nur State setzen wenn noch mounted
			if ( isMountedRef.current ) {
				setTemplates( data.items || data || [] );
			}
		} catch ( err ) {
			// AbortError explizit ignorieren
			if ( err?.name === 'AbortError' ) {
				return;
			}
			if ( isMountedRef.current && ! handleApiError( err, setError, errorLoadingMsg ) ) {
				console.error( 'Error fetching templates:', err );
			}
		} finally {
			if ( isMountedRef.current ) {
				setLoading( false );
			}
		}
	}, [ category, errorLoadingMsg ] );

	// Initial laden und Cleanup
	useEffect( () => {
		isMountedRef.current = true;
		fetchTemplates();

		return () => {
			isMountedRef.current = false;
			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}
		};
	}, [ fetchTemplates ] );

	/**
	 * Template erstellen
	 *
	 * @param {Object} data Template-Daten
	 * @return {Object|null} Erstelltes Template oder null
	 */
	const createTemplate = useCallback( async ( data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: '/recruiting/v1/email-templates',
				method: 'POST',
				data,
			} );

			if ( ! isMountedRef.current ) {
				return null;
			}

			const newTemplate = result.template || result;

			// Optimistic Update
			setTemplates( ( prev ) => [ newTemplate, ...prev ] );

			return newTemplate;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorSavingMsg ) ) {
				console.error( 'Error creating template:', err );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ errorSavingMsg ] );

	/**
	 * Template aktualisieren
	 *
	 * @param {number} id   Template-ID
	 * @param {Object} data Aktualisierte Daten
	 * @return {Object|null} Aktualisiertes Template oder null
	 */
	const updateTemplate = useCallback( async ( id, data ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: `/recruiting/v1/email-templates/${ id }`,
				method: 'PATCH',
				data,
			} );

			if ( ! isMountedRef.current ) {
				return null;
			}

			const updatedTemplate = result.template || result;

			// Optimistic Update
			setTemplates( ( prev ) =>
				prev.map( ( t ) => ( t.id === id ? updatedTemplate : t ) )
			);

			return updatedTemplate;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorSavingMsg ) ) {
				console.error( 'Error updating template:', err );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ errorSavingMsg ] );

	/**
	 * Template löschen
	 *
	 * @param {number} id Template-ID
	 * @return {boolean} Erfolg
	 */
	const deleteTemplate = useCallback( async ( id ) => {
		const previousTemplates = [ ...templates ];

		try {
			setSaving( true );
			setError( null );

			// Optimistic Update
			setTemplates( ( prev ) => prev.filter( ( t ) => t.id !== id ) );

			await apiFetch( {
				path: `/recruiting/v1/email-templates/${ id }`,
				method: 'DELETE',
			} );

			return true;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return false;
			}
			// Rollback
			setTemplates( previousTemplates );

			if ( ! handleApiError( err, setError, errorDeletingMsg ) ) {
				console.error( 'Error deleting template:', err );
			}
			return false;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ templates, errorDeletingMsg ] );

	/**
	 * Template duplizieren
	 *
	 * @param {number} id      Template-ID
	 * @param {string} newName Neuer Name (optional)
	 * @return {Object|null} Dupliziertes Template oder null
	 */
	const duplicateTemplate = useCallback( async ( id, newName = '' ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: `/recruiting/v1/email-templates/${ id }/duplicate`,
				method: 'POST',
				data: { name: newName },
			} );

			if ( ! isMountedRef.current ) {
				return null;
			}

			const newTemplate = result.template || result;

			// Optimistic Update
			setTemplates( ( prev ) => [ newTemplate, ...prev ] );

			return newTemplate;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorSavingMsg ) ) {
				console.error( 'Error duplicating template:', err );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ errorSavingMsg ] );

	/**
	 * Template auf Standard zurücksetzen
	 *
	 * @param {number} id Template-ID
	 * @return {Object|null} Zurückgesetztes Template oder null
	 */
	const resetTemplate = useCallback( async ( id ) => {
		try {
			setSaving( true );
			setError( null );

			const result = await apiFetch( {
				path: `/recruiting/v1/email-templates/${ id }/reset`,
				method: 'POST',
			} );

			if ( ! isMountedRef.current ) {
				return null;
			}

			const resetTpl = result.template || result;

			// Optimistic Update
			setTemplates( ( prev ) =>
				prev.map( ( t ) => ( t.id === id ? resetTpl : t ) )
			);

			return resetTpl;
		} catch ( err ) {
			if ( ! isMountedRef.current ) {
				return null;
			}
			if ( ! handleApiError( err, setError, errorSavingMsg ) ) {
				console.error( 'Error resetting template:', err );
			}
			return null;
		} finally {
			if ( isMountedRef.current ) {
				setSaving( false );
			}
		}
	}, [ errorSavingMsg ] );

	return {
		templates,
		loading,
		error,
		saving,
		fetchTemplates,
		createTemplate,
		updateTemplate,
		deleteTemplate,
		duplicateTemplate,
		resetTemplate,
		refetch: fetchTemplates,
	};
}

/**
 * Custom Hook für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

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

	const i18n = window.rpEmailData?.i18n || {};

	/**
	 * Templates vom Server laden
	 */
	const fetchTemplates = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			let path = '/recruiting/v1/email-templates';
			const params = new URLSearchParams();

			if ( options.category ) {
				params.append( 'category', options.category );
			}

			if ( params.toString() ) {
				path += '?' + params.toString();
			}

			const data = await apiFetch( { path } );

			setTemplates( data.items || data || [] );
		} catch ( err ) {
			console.error( 'Error fetching templates:', err );
			setError( err.message || i18n.errorLoading || 'Fehler beim Laden der Templates' );
		} finally {
			setLoading( false );
		}
	}, [ options.category, i18n.errorLoading ] );

	// Initial laden
	useEffect( () => {
		fetchTemplates();
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

			const newTemplate = result.template || result;

			// Optimistic Update
			setTemplates( ( prev ) => [ newTemplate, ...prev ] );

			return newTemplate;
		} catch ( err ) {
			console.error( 'Error creating template:', err );
			setError( err.message || i18n.errorSaving || 'Fehler beim Speichern' );
			return null;
		} finally {
			setSaving( false );
		}
	}, [ i18n.errorSaving ] );

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

			const updatedTemplate = result.template || result;

			// Optimistic Update
			setTemplates( ( prev ) =>
				prev.map( ( t ) => ( t.id === id ? updatedTemplate : t ) )
			);

			return updatedTemplate;
		} catch ( err ) {
			console.error( 'Error updating template:', err );
			setError( err.message || i18n.errorSaving || 'Fehler beim Speichern' );
			return null;
		} finally {
			setSaving( false );
		}
	}, [ i18n.errorSaving ] );

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
			console.error( 'Error deleting template:', err );

			// Rollback
			setTemplates( previousTemplates );

			setError( err.message || i18n.errorDeleting || 'Fehler beim Löschen' );
			return false;
		} finally {
			setSaving( false );
		}
	}, [ templates, i18n.errorDeleting ] );

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

			const newTemplate = result.template || result;

			// Optimistic Update
			setTemplates( ( prev ) => [ newTemplate, ...prev ] );

			return newTemplate;
		} catch ( err ) {
			console.error( 'Error duplicating template:', err );
			setError( err.message || i18n.errorSaving || 'Fehler beim Duplizieren' );
			return null;
		} finally {
			setSaving( false );
		}
	}, [ i18n.errorSaving ] );

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

			const resetTpl = result.template || result;

			// Optimistic Update
			setTemplates( ( prev ) =>
				prev.map( ( t ) => ( t.id === id ? resetTpl : t ) )
			);

			return resetTpl;
		} catch ( err ) {
			console.error( 'Error resetting template:', err );
			setError( err.message || i18n.errorSaving || 'Fehler beim Zurücksetzen' );
			return null;
		} finally {
			setSaving( false );
		}
	}, [ i18n.errorSaving ] );

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

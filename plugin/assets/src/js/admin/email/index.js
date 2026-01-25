/**
 * E-Mail Templates Admin App
 *
 * Entry Point für die E-Mail-Templates Verwaltung
 *
 * @package RecruitingPlaybook
 */

import { render, useState, useCallback } from '@wordpress/element';
import { Notice } from '@wordpress/components';

import { TemplateList, TemplateEditor, ErrorBoundary } from './components';
import { useTemplates, usePlaceholders } from './hooks';

/**
 * Haupt-App Komponente
 *
 * @return {JSX.Element} App
 */
function EmailTemplatesApp() {
	const [ view, setView ] = useState( 'list' ); // 'list' | 'edit' | 'create'
	const [ selectedTemplate, setSelectedTemplate ] = useState( null );
	const [ notification, setNotification ] = useState( null );

	const i18n = window.rpEmailData?.i18n || {};

	// Hooks
	const {
		templates,
		loading: templatesLoading,
		error: templatesError,
		saving,
		createTemplate,
		updateTemplate,
		deleteTemplate,
		duplicateTemplate,
		resetTemplate,
	} = useTemplates();

	const {
		placeholders,
		previewValues,
	} = usePlaceholders();

	/**
	 * Benachrichtigung anzeigen
	 *
	 * @param {string} message Nachricht
	 * @param {string} type    Typ ('success' | 'error')
	 */
	const showNotification = useCallback( ( message, type = 'success' ) => {
		setNotification( { message, type } );
		setTimeout( () => setNotification( null ), 3000 );
	}, [] );

	/**
	 * Template auswählen
	 *
	 * @param {Object} template Template
	 */
	const handleSelect = useCallback( ( template ) => {
		setSelectedTemplate( template );
		setView( 'edit' );
	}, [] );

	/**
	 * Neues Template erstellen
	 */
	const handleCreate = useCallback( () => {
		setSelectedTemplate( null );
		setView( 'create' );
	}, [] );

	/**
	 * Template speichern
	 *
	 * @param {Object} data Template-Daten
	 */
	const handleSave = useCallback( async ( data ) => {
		let result;

		if ( selectedTemplate?.id ) {
			result = await updateTemplate( selectedTemplate.id, data );
		} else {
			result = await createTemplate( data );
		}

		if ( result ) {
			showNotification( i18n.templateSaved || 'Template wurde gespeichert.' );
			setView( 'list' );
			setSelectedTemplate( null );
		}
	}, [ selectedTemplate, updateTemplate, createTemplate, showNotification, i18n.templateSaved ] );

	/**
	 * Template löschen
	 *
	 * @param {number} id Template-ID
	 */
	const handleDelete = useCallback( async ( id ) => {
		const success = await deleteTemplate( id );

		if ( success ) {
			showNotification( i18n.templateDeleted || 'Template wurde gelöscht.' );
		}
	}, [ deleteTemplate, showNotification, i18n.templateDeleted ] );

	/**
	 * Template duplizieren
	 *
	 * @param {number} id Template-ID
	 */
	const handleDuplicate = useCallback( async ( id ) => {
		const result = await duplicateTemplate( id );

		if ( result ) {
			showNotification( i18n.templateDuplicated || 'Template wurde dupliziert.' );
		}
	}, [ duplicateTemplate, showNotification, i18n.templateDuplicated ] );

	/**
	 * Template zurücksetzen
	 *
	 * @param {number} id Template-ID
	 */
	const handleReset = useCallback( async ( id ) => {
		const result = await resetTemplate( id );

		if ( result ) {
			showNotification( i18n.templateReset || 'Template wurde zurückgesetzt.' );
		}
	}, [ resetTemplate, showNotification, i18n.templateReset ] );

	/**
	 * Abbrechen
	 */
	const handleCancel = useCallback( () => {
		setView( 'list' );
		setSelectedTemplate( null );
	}, [] );

	return (
		<div className="rp-email-templates-app">
			{ notification && (
				<Notice
					status={ notification.type }
					isDismissible={ true }
					onRemove={ () => setNotification( null ) }
					className="rp-notification"
				>
					{ notification.message }
				</Notice>
			) }

			{ view === 'list' && (
				<TemplateList
					templates={ templates }
					loading={ templatesLoading }
					error={ templatesError }
					onSelect={ handleSelect }
					onDelete={ handleDelete }
					onDuplicate={ handleDuplicate }
					onReset={ handleReset }
					onCreate={ handleCreate }
				/>
			) }

			{ ( view === 'edit' || view === 'create' ) && (
				<TemplateEditor
					template={ selectedTemplate }
					placeholders={ placeholders }
					previewValues={ previewValues }
					saving={ saving }
					error={ templatesError }
					onSave={ handleSave }
					onCancel={ handleCancel }
				/>
			) }
		</div>
	);
}

// App initialisieren
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'rp-email-templates-app' );

	if ( container ) {
		render(
			<ErrorBoundary>
				<EmailTemplatesApp />
			</ErrorBoundary>,
			container
		);
	}
} );

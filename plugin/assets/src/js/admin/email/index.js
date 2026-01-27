/**
 * E-Mail Templates Admin App
 *
 * Entry Point für die E-Mail-Templates und Signaturen Verwaltung
 *
 * @package RecruitingPlaybook
 */

import { render, useState, useCallback, useRef, useEffect } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { FileText, PenTool } from 'lucide-react';

import {
	TemplateList,
	TemplateEditor,
	SignatureList,
	SignatureEditor,
	ErrorBoundary,
} from './components';
import { useTemplates, useSignatures, usePlaceholders } from './hooks';

/**
 * Tab-Button Komponente
 *
 * @param {Object}   props          Props
 * @param {boolean}  props.active   Aktiv?
 * @param {Function} props.onClick  Click-Handler
 * @param {Object}   props.icon     Icon-Komponente
 * @param {string}   props.children Label
 * @return {JSX.Element} Tab-Button
 */
function TabButton( { active, onClick, icon: Icon, children } ) {
	return (
		<button
			type="button"
			onClick={ onClick }
			style={ {
				display: 'flex',
				alignItems: 'center',
				gap: '0.5rem',
				padding: '0.75rem 1.25rem',
				border: 'none',
				borderBottom: active ? '2px solid #1d71b8' : '2px solid transparent',
				backgroundColor: 'transparent',
				color: active ? '#1d71b8' : '#6b7280',
				fontWeight: active ? 600 : 400,
				fontSize: '0.875rem',
				cursor: 'pointer',
				transition: 'all 0.15s ease',
			} }
		>
			{ Icon && <Icon style={ { width: '1rem', height: '1rem' } } /> }
			{ children }
		</button>
	);
}

/**
 * Haupt-App Komponente
 *
 * @return {JSX.Element} App
 */
function EmailTemplatesApp() {
	// Hauptnavigation: Templates oder Signaturen
	const [ mainTab, setMainTab ] = useState( 'templates' ); // 'templates' | 'signatures'

	// Template-Views
	const [ templateView, setTemplateView ] = useState( 'list' ); // 'list' | 'edit' | 'create'
	const [ selectedTemplate, setSelectedTemplate ] = useState( null );

	// Signatur-Views
	const [ signatureView, setSignatureView ] = useState( 'list' ); // 'list' | 'edit' | 'create' | 'company'
	const [ selectedSignature, setSelectedSignature ] = useState( null );

	const [ notification, setNotification ] = useState( null );

	// Ref für Notification-Timeout (Memory Leak Prevention)
	const notificationTimeoutRef = useRef( null );

	const i18n = window.rpEmailData?.i18n || {};
	const isAdmin = window.rpEmailData?.isAdmin || false;

	// Template Hooks
	const {
		templates,
		loading: templatesLoading,
		error: templatesError,
		saving: templatesSaving,
		createTemplate,
		updateTemplate,
		deleteTemplate,
		duplicateTemplate,
		resetTemplate,
	} = useTemplates();

	// Signatur Hooks
	const {
		signatures,
		companySignature,
		loading: signaturesLoading,
		error: signaturesError,
		saving: signaturesSaving,
		createSignature,
		updateSignature,
		deleteSignature,
		setDefaultSignature,
		updateCompanySignature,
		previewSignature,
	} = useSignatures();

	const {
		placeholders,
		previewValues,
	} = usePlaceholders();

	// Cleanup bei Unmount
	useEffect( () => {
		return () => {
			if ( notificationTimeoutRef.current ) {
				clearTimeout( notificationTimeoutRef.current );
			}
		};
	}, [] );

	/**
	 * Benachrichtigung anzeigen
	 *
	 * @param {string} message Nachricht
	 * @param {string} type    Typ ('success' | 'error')
	 */
	const showNotification = useCallback( ( message, type = 'success' ) => {
		// Vorheriges Timeout aufräumen
		if ( notificationTimeoutRef.current ) {
			clearTimeout( notificationTimeoutRef.current );
		}

		setNotification( { message, type } );

		notificationTimeoutRef.current = setTimeout( () => {
			setNotification( null );
			notificationTimeoutRef.current = null;
		}, 3000 );
	}, [] );

	// ==================== Template Handlers ====================

	/**
	 * Template auswählen
	 *
	 * @param {Object} template Template
	 */
	const handleTemplateSelect = useCallback( ( template ) => {
		setSelectedTemplate( template );
		setTemplateView( 'edit' );
	}, [] );

	/**
	 * Neues Template erstellen
	 */
	const handleTemplateCreate = useCallback( () => {
		setSelectedTemplate( null );
		setTemplateView( 'create' );
	}, [] );

	/**
	 * Template speichern
	 *
	 * @param {Object} data Template-Daten
	 */
	const handleTemplateSave = useCallback( async ( data ) => {
		let result;

		if ( selectedTemplate?.id ) {
			result = await updateTemplate( selectedTemplate.id, data );
		} else {
			result = await createTemplate( data );
		}

		if ( result ) {
			showNotification( i18n.templateSaved || 'Template wurde gespeichert.' );
			setTemplateView( 'list' );
			setSelectedTemplate( null );
		}
	}, [ selectedTemplate, updateTemplate, createTemplate, showNotification, i18n.templateSaved ] );

	/**
	 * Template löschen
	 *
	 * @param {number} id Template-ID
	 */
	const handleTemplateDelete = useCallback( async ( id ) => {
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
	const handleTemplateDuplicate = useCallback( async ( id ) => {
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
	const handleTemplateReset = useCallback( async ( id ) => {
		const result = await resetTemplate( id );

		if ( result ) {
			showNotification( i18n.templateReset || 'Template wurde zurückgesetzt.' );
		}
	}, [ resetTemplate, showNotification, i18n.templateReset ] );

	/**
	 * Template-Bearbeitung abbrechen
	 */
	const handleTemplateCancel = useCallback( () => {
		setTemplateView( 'list' );
		setSelectedTemplate( null );
	}, [] );

	// ==================== Signatur Handlers ====================

	/**
	 * Signatur auswählen
	 *
	 * @param {Object} signature Signatur
	 */
	const handleSignatureSelect = useCallback( ( signature ) => {
		setSelectedSignature( signature );
		setSignatureView( 'edit' );
	}, [] );

	/**
	 * Neue Signatur erstellen
	 */
	const handleSignatureCreate = useCallback( () => {
		setSelectedSignature( null );
		setSignatureView( 'create' );
	}, [] );

	/**
	 * Firmen-Signatur bearbeiten
	 */
	const handleCompanySignatureEdit = useCallback( () => {
		setSelectedSignature( companySignature );
		setSignatureView( 'company' );
	}, [ companySignature ] );

	/**
	 * Signatur speichern
	 *
	 * @param {Object} data Signatur-Daten
	 */
	const handleSignatureSave = useCallback( async ( data ) => {
		let result;

		if ( signatureView === 'company' ) {
			result = await updateCompanySignature( data );
		} else if ( selectedSignature?.id ) {
			result = await updateSignature( selectedSignature.id, data );
		} else {
			result = await createSignature( data );
		}

		if ( result ) {
			showNotification( i18n.signatureSaved || 'Signatur wurde gespeichert.' );
			setSignatureView( 'list' );
			setSelectedSignature( null );
		}
	}, [ signatureView, selectedSignature, updateSignature, createSignature, updateCompanySignature, showNotification, i18n.signatureSaved ] );

	/**
	 * Signatur löschen
	 *
	 * @param {number} id Signatur-ID
	 */
	const handleSignatureDelete = useCallback( async ( id ) => {
		const success = await deleteSignature( id );

		if ( success ) {
			showNotification( i18n.signatureDeleted || 'Signatur wurde gelöscht.' );
		}
	}, [ deleteSignature, showNotification, i18n.signatureDeleted ] );

	/**
	 * Signatur als Standard setzen
	 *
	 * @param {number} id Signatur-ID
	 */
	const handleSetDefaultSignature = useCallback( async ( id ) => {
		const success = await setDefaultSignature( id );

		if ( success ) {
			showNotification( i18n.signatureSetDefault || 'Standard-Signatur wurde gesetzt.' );
		}
	}, [ setDefaultSignature, showNotification, i18n.signatureSetDefault ] );

	/**
	 * Signatur-Bearbeitung abbrechen
	 */
	const handleSignatureCancel = useCallback( () => {
		setSignatureView( 'list' );
		setSelectedSignature( null );
	}, [] );

	// ==================== Render ====================

	/**
	 * Tab wechseln
	 *
	 * @param {string} tab Tab-Name
	 */
	const handleTabChange = useCallback( ( tab ) => {
		setMainTab( tab );
		// View zurücksetzen beim Tab-Wechsel
		if ( tab === 'templates' ) {
			setTemplateView( 'list' );
			setSelectedTemplate( null );
		} else {
			setSignatureView( 'list' );
			setSelectedSignature( null );
		}
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

			{ /* Tab-Navigation */ }
			<div
				className="rp-email-tabs"
				style={ {
					display: 'flex',
					borderBottom: '1px solid #e5e7eb',
					marginBottom: '1.5rem',
				} }
			>
				<TabButton
					active={ mainTab === 'templates' }
					onClick={ () => handleTabChange( 'templates' ) }
					icon={ FileText }
				>
					{ i18n.templates || 'Templates' }
				</TabButton>
				<TabButton
					active={ mainTab === 'signatures' }
					onClick={ () => handleTabChange( 'signatures' ) }
					icon={ PenTool }
				>
					{ i18n.signatures || 'Signaturen' }
				</TabButton>
			</div>

			{ /* Templates Tab */ }
			{ mainTab === 'templates' && (
				<>
					{ templateView === 'list' && (
						<TemplateList
							templates={ templates }
							loading={ templatesLoading }
							error={ templatesError }
							onSelect={ handleTemplateSelect }
							onDelete={ handleTemplateDelete }
							onDuplicate={ handleTemplateDuplicate }
							onReset={ handleTemplateReset }
							onCreate={ handleTemplateCreate }
						/>
					) }

					{ ( templateView === 'edit' || templateView === 'create' ) && (
						<TemplateEditor
							template={ selectedTemplate }
							placeholders={ placeholders }
							previewValues={ previewValues }
							saving={ templatesSaving }
							error={ templatesError }
							onSave={ handleTemplateSave }
							onCancel={ handleTemplateCancel }
						/>
					) }
				</>
			) }

			{ /* Signaturen Tab */ }
			{ mainTab === 'signatures' && (
				<>
					{ signatureView === 'list' && (
						<SignatureList
							signatures={ signatures }
							companySignature={ companySignature }
							loading={ signaturesLoading }
							error={ signaturesError }
							isAdmin={ isAdmin }
							onSelect={ handleSignatureSelect }
							onDelete={ handleSignatureDelete }
							onSetDefault={ handleSetDefaultSignature }
							onCreate={ handleSignatureCreate }
							onEditCompany={ handleCompanySignatureEdit }
						/>
					) }

					{ ( signatureView === 'edit' || signatureView === 'create' ) && (
						<SignatureEditor
							signature={ selectedSignature }
							isCompany={ false }
							saving={ signaturesSaving }
							error={ signaturesError }
							onSave={ handleSignatureSave }
							onCancel={ handleSignatureCancel }
							onPreview={ previewSignature }
						/>
					) }

					{ signatureView === 'company' && (
						<SignatureEditor
							signature={ companySignature }
							isCompany={ true }
							saving={ signaturesSaving }
							error={ signaturesError }
							onSave={ handleSignatureSave }
							onCancel={ handleSignatureCancel }
							onPreview={ previewSignature }
						/>
					) }
				</>
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

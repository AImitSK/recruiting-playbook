/**
 * E-Mail Templates Admin App
 *
 * Entry Point für die E-Mail-Templates und Signaturen Verwaltung
 *
 * @package RecruitingPlaybook
 */

import { render, useState, useCallback, useRef, useEffect } from '@wordpress/element';
import { Alert, AlertDescription } from '../components/ui/alert';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/ui/tabs';
import { Spinner } from '../components/ui/spinner';

import {
	TemplateList,
	TemplateEditor,
	SignatureList,
	SignatureEditor,
	AutomationSettings,
	ErrorBoundary,
} from './components';
import { useTemplates, useSignatures, usePlaceholders } from './hooks';

/**
 * Haupt-App Komponente
 *
 * @return {JSX.Element} App
 */
function EmailTemplatesApp() {
	// Hauptnavigation: Templates oder Signaturen
	const [ mainTab, setMainTab ] = useState( 'templates' );

	// Template-Views
	const [ templateView, setTemplateView ] = useState( 'list' );
	const [ selectedTemplate, setSelectedTemplate ] = useState( null );

	// Signatur-Views
	const [ signatureView, setSignatureView ] = useState( 'list' );
	const [ selectedSignature, setSelectedSignature ] = useState( null );

	const [ notification, setNotification ] = useState( null );

	// Ref für Notification-Timeout
	const notificationTimeoutRef = useRef( null );

	const i18n = window.rpEmailData?.i18n || {};
	const isAdmin = window.rpEmailData?.isAdmin || false;
	const logoUrl = window.rpEmailData?.logoUrl || '';

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
		loading: signaturesLoading,
		error: signaturesError,
		saving: signaturesSaving,
		createSignature,
		updateSignature,
		deleteSignature,
		setDefaultSignature,
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
	 */
	const showNotification = useCallback( ( message, type = 'success' ) => {
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

	const handleTemplateSelect = useCallback( ( template ) => {
		setSelectedTemplate( template );
		setTemplateView( 'edit' );
	}, [] );

	const handleTemplateCreate = useCallback( () => {
		setSelectedTemplate( null );
		setTemplateView( 'create' );
	}, [] );

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

	const handleTemplateDelete = useCallback( async ( id ) => {
		const success = await deleteTemplate( id );

		if ( success ) {
			showNotification( i18n.templateDeleted || 'Template wurde gelöscht.' );
		}
	}, [ deleteTemplate, showNotification, i18n.templateDeleted ] );

	const handleTemplateDuplicate = useCallback( async ( id ) => {
		const result = await duplicateTemplate( id );

		if ( result ) {
			showNotification( i18n.templateDuplicated || 'Template wurde dupliziert.' );
		}
	}, [ duplicateTemplate, showNotification, i18n.templateDuplicated ] );

	const handleTemplateReset = useCallback( async ( id ) => {
		const result = await resetTemplate( id );

		if ( result ) {
			showNotification( i18n.templateReset || 'Template wurde zurückgesetzt.' );
		}
	}, [ resetTemplate, showNotification, i18n.templateReset ] );

	const handleTemplateCancel = useCallback( () => {
		setTemplateView( 'list' );
		setSelectedTemplate( null );
	}, [] );

	// ==================== Signatur Handlers ====================

	const handleSignatureSelect = useCallback( ( signature ) => {
		setSelectedSignature( signature );
		setSignatureView( 'edit' );
	}, [] );

	const handleSignatureCreate = useCallback( () => {
		setSelectedSignature( null );
		setSignatureView( 'create' );
	}, [] );

	const handleSignatureSave = useCallback( async ( data ) => {
		let result;

		if ( selectedSignature?.id ) {
			result = await updateSignature( selectedSignature.id, data );
		} else {
			result = await createSignature( data );
		}

		if ( result ) {
			showNotification( i18n.signatureSaved || 'Signatur wurde gespeichert.' );
			setSignatureView( 'list' );
			setSelectedSignature( null );
		}
	}, [ selectedSignature, updateSignature, createSignature, showNotification, i18n.signatureSaved ] );

	const handleSignatureDelete = useCallback( async ( id ) => {
		const success = await deleteSignature( id );

		if ( success ) {
			showNotification( i18n.signatureDeleted || 'Signatur wurde gelöscht.' );
		}
	}, [ deleteSignature, showNotification, i18n.signatureDeleted ] );

	const handleSetDefaultSignature = useCallback( async ( id ) => {
		const success = await setDefaultSignature( id );

		if ( success ) {
			showNotification( i18n.signatureSetDefault || 'Standard-Signatur wurde gesetzt.' );
		}
	}, [ setDefaultSignature, showNotification, i18n.signatureSetDefault ] );

	const handleSignatureCancel = useCallback( () => {
		setSignatureView( 'list' );
		setSelectedSignature( null );
	}, [] );

	// ==================== Render ====================

	const handleTabChange = useCallback( ( tab ) => {
		setMainTab( tab );
		if ( tab === 'templates' ) {
			setTemplateView( 'list' );
			setSelectedTemplate( null );
		} else {
			setSignatureView( 'list' );
			setSelectedSignature( null );
		}
	}, [] );

	// Loading state
	if ( templatesLoading && signaturesLoading ) {
		return (
			<div style={ { display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '3rem' } }>
				<Spinner size="lg" />
			</div>
		);
	}

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '1400px' } }>
				{ /* Header: Logo links, Titel rechts, Unterkante ausgerichtet */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img src={ logoUrl } alt="Recruiting Playbook" style={ { width: '150px', height: 'auto' } } />
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ i18n.pageTitle || 'E-Mail-Vorlagen & Signaturen' }
					</h1>
				</div>

				{ notification && (
					<Alert
						variant={ notification.type === 'error' ? 'destructive' : 'default' }
						style={ {
							marginBottom: '1rem',
							backgroundColor: notification.type === 'success' ? '#e6f5ec' : undefined,
							borderColor: notification.type === 'success' ? '#2fac66' : undefined,
						} }
					>
						<AlertDescription>{ notification.message }</AlertDescription>
					</Alert>
				) }

				<Tabs value={ mainTab } onValueChange={ handleTabChange }>
					<TabsList>
						<TabsTrigger value="templates">
							{ i18n.templates || 'Templates' }
						</TabsTrigger>
						<TabsTrigger value="signatures">
							{ i18n.signatures || 'Signaturen' }
						</TabsTrigger>
						<TabsTrigger value="automation">
							{ i18n.automation || 'Automatisierung' }
						</TabsTrigger>
					</TabsList>

					<TabsContent value="templates">
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
					</TabsContent>

					<TabsContent value="signatures">
						{ signatureView === 'list' && (
							<SignatureList
								signatures={ signatures }
								loading={ signaturesLoading }
								error={ signaturesError }
								onSelect={ handleSignatureSelect }
								onDelete={ handleSignatureDelete }
								onSetDefault={ handleSetDefaultSignature }
								onCreate={ handleSignatureCreate }
							/>
						) }

						{ ( signatureView === 'edit' || signatureView === 'create' ) && (
							<SignatureEditor
								signature={ selectedSignature }
								saving={ signaturesSaving }
								error={ signaturesError }
								onSave={ handleSignatureSave }
								onCancel={ handleSignatureCancel }
								onPreview={ previewSignature }
							/>
						) }
					</TabsContent>

					<TabsContent value="automation">
						<AutomationSettings templates={ templates } />
					</TabsContent>
				</Tabs>
			</div>
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

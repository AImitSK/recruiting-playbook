/**
 * FormBuilder Main Component
 *
 * Main entry point for the Form Builder React application.
 * Implements step-based form configuration with Draft/Publish workflow.
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../components/ui/tabs';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../components/ui/card';
import { Alert, AlertDescription } from '../components/ui/alert';
import { Button } from '../components/ui/button';
import { Spinner } from '../components/ui/spinner';
import { Lock, AlertCircle, CheckCircle2 } from 'lucide-react';

import FieldList from './components/FieldList';
import FieldEditor from './components/FieldEditor';
import FieldTypeSelector from './components/FieldTypeSelector';
import FormPreview from './components/FormPreview';
import FormEditor from './components/FormEditor';
import FreeVersionOverlay from './components/FreeVersionOverlay';
import { useFormConfig } from './hooks/useFormConfig';
import { useFieldDefinitions } from './hooks/useFieldDefinitions';

/**
 * FormBuilder component
 */
export default function FormBuilder() {
	const config = window.rpFormBuilderData || {};
	const { isPro, canManage, fieldTypes, i18n, upgradeUrl, logoUrl } = config;

	const [ activeTab, setActiveTab ] = useState( 'form' );
	const [ selectedField, setSelectedField ] = useState( null );
	const [ showFieldTypeSelector, setShowFieldTypeSelector ] = useState( false );

	// Form configuration (step-based)
	const {
		draft,
		steps,
		regularSteps,
		finaleStep,
		settings,
		availableFields,
		publishedVersion,
		hasChanges,
		isLoading: configLoading,
		isSaving,
		isPublishing,
		error: configError,
		successMessage,
		publish,
		discardDraft,
		addStep,
		updateStep,
		removeStep,
		reorderSteps,
		addFieldToStep,
		removeFieldFromStep,
		updateFieldInStep,
		updateSystemFieldInStep,
		moveFieldBetweenSteps,
		reorderFieldsInStep,
		updateSettings,
		getUnusedFields,
		getFieldDefinition,
	} = useFormConfig();

	// Field definitions (for "Felder" tab - creating custom fields)
	const {
		fields,
		systemFields,
		customFields,
		isLoading: fieldsLoading,
		error: fieldsError,
		createField,
		updateField,
		deleteField,
		reorderFields,
	} = useFieldDefinitions();

	// Permission check
	if ( ! canManage ) {
		return (
			<div className="rp-form-builder__no-access">
				<Card>
					<CardContent className="pt-6">
						<div style={ { display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '1rem', padding: '2rem 0', textAlign: 'center' } }>
							<Lock style={ { height: '3rem', width: '3rem', color: '#9ca3af' } } />
							<h2 style={ { fontSize: '1.25rem', fontWeight: 600, margin: 0 } }>
								{ i18n?.noPermission || __( 'Keine Berechtigung', 'recruiting-playbook' ) }
							</h2>
							<p style={ { color: '#4b5563', margin: 0 } }>
								{ i18n?.noPermissionText || __( 'Sie haben keine Berechtigung, den Formular-Builder zu verwenden.', 'recruiting-playbook' ) }
							</p>
						</div>
					</CardContent>
				</Card>
			</div>
		);
	}

	// Handle field selection (for editing in sidebar)
	const handleFieldSelect = ( field ) => {
		setSelectedField( field );
	};

	// Handle field creation (for "Felder" tab)
	const handleAddField = () => {
		setShowFieldTypeSelector( true );
	};

	// Handle field type selection
	const handleFieldTypeSelect = async ( fieldType ) => {
		setShowFieldTypeSelector( false );

		const newField = await createField( {
			type: fieldType,
			field_key: `field_${ Date.now() }`,
			label: fieldTypes[ fieldType ]?.label || fieldType,
			is_required: false,
			is_enabled: true,
			is_system: false,
			sort_order: customFields.length,
		} );

		if ( newField ) {
			setSelectedField( newField );
		}
	};

	// Handle field update
	const handleFieldUpdate = async ( fieldId, updates ) => {
		const success = await updateField( fieldId, updates );
		if ( success ) {
			setSelectedField( ( prev ) =>
				prev?.id === fieldId ? { ...prev, ...updates } : prev
			);
		}
		return success;
	};

	// Handle field deletion
	const handleFieldDelete = async ( fieldId ) => {
		const success = await deleteField( fieldId );
		if ( success && selectedField?.id === fieldId ) {
			setSelectedField( null );
		}
		return success;
	};

	// Handle field reorder
	const handleFieldReorder = async ( orderedIds ) => {
		return await reorderFields( orderedIds );
	};

	// Close field editor
	const handleCloseEditor = () => {
		setSelectedField( null );
	};

	// Publish handler
	const handlePublish = async () => {
		await publish();
	};

	// Discard handler
	const handleDiscard = async () => {
		if ( window.confirm( __( 'Möchten Sie alle Änderungen verwerfen?', 'recruiting-playbook' ) ) ) {
			await discardDraft();
		}
	};

	// Loading state
	if ( configLoading ) {
		return (
			<div className="rp-admin" style={ { padding: '20px 0' } }>
				<div style={ { display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '200px' } }>
					<Spinner />
				</div>
			</div>
		);
	}

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '1200px' } }>
				{ /* Header */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '1.5rem' } }>
					<div style={ { display: 'flex', alignItems: 'flex-end', gap: '1rem' } }>
						{ logoUrl && (
							<img src={ logoUrl } alt="Recruiting Playbook" style={ { width: '150px', height: 'auto' } } />
						) }
						<div>
							<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
								{ i18n?.pageTitle || __( 'Formular-Builder', 'recruiting-playbook' ) }
							</h1>
							<p style={ { margin: '0.25rem 0 0', fontSize: '0.875rem', color: '#6b7280' } }>
								{ __( 'Version', 'recruiting-playbook' ) } { publishedVersion }
								{ hasChanges && (
									<span style={ { marginLeft: '0.5rem', color: '#f59e0b' } }>
										({ __( 'ungespeicherte Änderungen', 'recruiting-playbook' ) })
									</span>
								) }
							</p>
						</div>
					</div>

					{ /* Publish Controls */ }
					<div style={ { display: 'flex', gap: '0.5rem', alignItems: 'center' } }>
						{ isSaving && (
							<span style={ { fontSize: '0.875rem', color: '#6b7280' } }>
								{ __( 'Speichern...', 'recruiting-playbook' ) }
							</span>
						) }

						{ successMessage && (
							<span style={ { display: 'flex', alignItems: 'center', gap: '0.25rem', fontSize: '0.875rem', color: '#10b981' } }>
								<CheckCircle2 style={ { height: '1rem', width: '1rem' } } />
								{ successMessage }
							</span>
						) }

						{ hasChanges && (
							<Button
								variant="outline"
								size="sm"
								onClick={ handleDiscard }
								disabled={ isPublishing }
							>
								{ __( 'Verwerfen', 'recruiting-playbook' ) }
							</Button>
						) }

						<Button
							onClick={ handlePublish }
							disabled={ ! hasChanges || isPublishing }
							size="sm"
						>
							{ isPublishing
								? __( 'Veröffentlichen...', 'recruiting-playbook' )
								: __( 'Veröffentlichen', 'recruiting-playbook' )
							}
						</Button>
					</div>
				</div>

				{ /* Pro Upgrade Notice */ }
				{ ! isPro && (
					<Alert className="mb-4 border-amber-200 bg-amber-50">
						<AlertCircle className="h-4 w-4 text-amber-600" />
						<AlertDescription className="flex items-center justify-between">
							<span>
								{ i18n?.proRequired || __( 'Custom Fields sind ein Pro-Feature. System-Felder können bearbeitet werden.', 'recruiting-playbook' ) }
							</span>
							<Button
								variant="outline"
								size="sm"
								onClick={ () => window.location.href = upgradeUrl }
								className="ml-4"
							>
								{ i18n?.upgradeToPro || __( 'Auf Pro upgraden', 'recruiting-playbook' ) }
							</Button>
						</AlertDescription>
					</Alert>
				) }

				{ /* Error Display */ }
				{ ( configError || fieldsError ) && (
					<Alert variant="destructive" className="mb-4">
						<AlertCircle className="h-4 w-4" />
						<AlertDescription>
							{ configError || fieldsError }
						</AlertDescription>
					</Alert>
				) }

				{ /* Main Tabs */ }
				<Tabs value={ activeTab } onValueChange={ setActiveTab }>
					<TabsList className="mb-4">
						<TabsTrigger value="form">
							{ i18n?.tabForm || __( 'Formular', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="fields">
							{ i18n?.tabFields || __( 'Felder', 'recruiting-playbook' ) }
							{ ! isPro && <Lock className="ml-1 h-3 w-3" /> }
						</TabsTrigger>
						<TabsTrigger value="preview">
							{ i18n?.tabPreview || __( 'Vorschau', 'recruiting-playbook' ) }
						</TabsTrigger>
					</TabsList>

					{ /* Form Tab - Step-based Form Editor */ }
					<TabsContent value="form" className="mt-0">
						<div style={ { position: 'relative' } }>
							<FormEditor
								steps={ steps }
								regularSteps={ regularSteps }
								finaleStep={ finaleStep }
								availableFields={ availableFields }
								addStep={ isPro ? addStep : undefined }
								updateStep={ isPro ? updateStep : undefined }
								removeStep={ isPro ? removeStep : undefined }
								reorderSteps={ isPro ? reorderSteps : undefined }
								addFieldToStep={ isPro ? addFieldToStep : undefined }
								removeFieldFromStep={ isPro ? removeFieldFromStep : undefined }
								updateFieldInStep={ isPro ? updateFieldInStep : undefined }
								updateSystemFieldInStep={ isPro ? updateSystemFieldInStep : undefined }
								moveFieldBetweenSteps={ isPro ? moveFieldBetweenSteps : undefined }
								reorderFieldsInStep={ isPro ? reorderFieldsInStep : undefined }
								getUnusedFields={ getUnusedFields }
								getFieldDefinition={ getFieldDefinition }
								i18n={ i18n }
							/>
							{ ! isPro && (
								<FreeVersionOverlay
									upgradeUrl={ upgradeUrl }
									i18n={ i18n }
								/>
							) }
						</div>
					</TabsContent>

					{ /* Fields Tab - Field Library */ }
					<TabsContent value="fields" className="mt-0">
						<div className="rp-form-builder__content rp-grid rp-grid-cols-1 lg:rp-grid-cols-2 rp-gap-6">
							<div>
								<FieldList
									systemFields={ systemFields }
									customFields={ customFields }
									selectedFieldId={ selectedField?.id }
									onFieldSelect={ handleFieldSelect }
									onFieldReorder={ handleFieldReorder }
									onAddField={ handleAddField }
									isLoading={ fieldsLoading }
									isPro={ isPro }
									i18n={ i18n }
								/>
							</div>
							<div>
								{ selectedField ? (
									<FieldEditor
										field={ selectedField }
										fieldTypes={ fieldTypes }
										allFields={ fields }
										onUpdate={ handleFieldUpdate }
										onDelete={ handleFieldDelete }
										onClose={ handleCloseEditor }
										isPro={ isPro }
										i18n={ i18n }
									/>
								) : (
									<Card>
										<CardHeader>
											<CardTitle>
												{ i18n?.editField || __( 'Feld bearbeiten', 'recruiting-playbook' ) }
											</CardTitle>
											<CardDescription>
												{ i18n?.selectFieldToEdit || __( 'Wählen Sie ein Feld aus der Liste, um es zu bearbeiten.', 'recruiting-playbook' ) }
											</CardDescription>
										</CardHeader>
									</Card>
								) }
							</div>
						</div>
					</TabsContent>

					{ /* Preview Tab */ }
					<TabsContent value="preview" className="mt-0">
						<FormPreview
							steps={ steps }
							availableFields={ availableFields }
							settings={ settings }
							fieldTypes={ fieldTypes }
							i18n={ i18n }
						/>
					</TabsContent>
				</Tabs>

				{ /* Field Type Selector Modal */ }
				{ showFieldTypeSelector && (
					<FieldTypeSelector
						fieldTypes={ fieldTypes }
						onSelect={ handleFieldTypeSelect }
						onClose={ () => setShowFieldTypeSelector( false ) }
						isPro={ isPro }
						i18n={ i18n }
					/>
				) }
			</div>
		</div>
	);
}

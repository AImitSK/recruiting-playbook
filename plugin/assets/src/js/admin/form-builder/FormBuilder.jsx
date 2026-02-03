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

import FieldEditor from './components/FieldEditor';
import FieldEditorModal from './components/FieldEditorModal';
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
	const [ fieldTypeSelectorStepId, setFieldTypeSelectorStepId ] = useState( null );
	const [ editingFieldKey, setEditingFieldKey ] = useState( null );

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
		getFieldDefinition,
		refreshAvailableFields,
		resetToDefault,
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

	// Handle create field for a specific step (opens FieldTypeSelector modal)
	const handleCreateFieldForStep = ( stepId ) => {
		setFieldTypeSelectorStepId( stepId );
		setShowFieldTypeSelector( true );
	};

	// Handle field type selection - creates field and optionally adds to step
	const handleFieldTypeSelect = async ( fieldType, fieldSettings = {} ) => {
		const targetStepId = fieldTypeSelectorStepId;
		setShowFieldTypeSelector( false );
		setFieldTypeSelectorStepId( null );

		const newField = await createField( {
			type: fieldType,
			field_key: `field_${ Date.now() }`,
			label: fieldSettings.label || fieldTypes[ fieldType ]?.label || fieldType,
			placeholder: fieldSettings.placeholder || '',
			description: fieldSettings.description || '',
			is_required: fieldSettings.is_required || false,
			is_enabled: true,
			is_system: false,
			sort_order: customFields.length,
			settings: {
				width: fieldSettings.width || 'full',
				options: fieldSettings.options || [],
			},
		} );

		if ( newField ) {
			// Refresh availableFields to include the new field
			await refreshAvailableFields();

			// If we have a target step, add the field to it
			if ( targetStepId && addFieldToStep ) {
				addFieldToStep( targetStepId, newField.field_key, {
					is_visible: true,
					is_required: fieldSettings.is_required || false,
					width: fieldSettings.width || 'full',
				} );
			}
		}
	};

	// Handle field update
	const handleFieldUpdate = async ( fieldId, updates ) => {
		const success = await updateField( fieldId, updates );
		if ( success ) {
			setSelectedField( ( prev ) =>
				prev?.id === fieldId ? { ...prev, ...updates } : prev
			);
			// Refresh availableFields to reflect updated field properties in the Formular tab
			await refreshAvailableFields();
		}
		return success;
	};

	// Handle field deletion
	const handleFieldDelete = async ( fieldId ) => {
		const success = await deleteField( fieldId );
		if ( success ) {
			if ( selectedField?.id === fieldId ) {
				setSelectedField( null );
			}
			// Refresh availableFields to remove the deleted field from the Formular tab
			await refreshAvailableFields();
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

	// Handle edit field (from FormEditor)
	const handleEditField = ( fieldKey ) => {
		setEditingFieldKey( fieldKey );
	};

	// Handle edit field update (from FieldEditorModal)
	const handleEditFieldUpdate = async ( fieldId, updates ) => {
		const success = await updateField( fieldId, updates );
		if ( success ) {
			await refreshAvailableFields();
		}
		return success;
	};

	// Handle edit field delete (from FieldEditorModal)
	const handleEditFieldDelete = async ( fieldId ) => {
		const success = await deleteField( fieldId );
		if ( success ) {
			setEditingFieldKey( null );
			await refreshAvailableFields();
		}
		return success;
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
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img src={ logoUrl } alt="Recruiting Playbook" style={ { width: '150px', height: 'auto' } } />
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ i18n?.pageTitle || __( 'Formular-Builder', 'recruiting-playbook' ) }
					</h1>
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
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' } }>
						<TabsList>
							<TabsTrigger value="form">
								{ i18n?.tabForm || __( 'Formular', 'recruiting-playbook' ) }
							</TabsTrigger>
							<TabsTrigger value="preview">
								{ i18n?.tabPreview || __( 'Vorschau', 'recruiting-playbook' ) }
							</TabsTrigger>
						</TabsList>

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
								getFieldDefinition={ getFieldDefinition }
								onResetToDefault={ resetToDefault }
								onEditField={ handleEditField }
								onCreateField={ handleCreateFieldForStep }
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

				{ /* Field Editor Modal (for editing custom fields from FormEditor) */ }
				{ editingFieldKey && (
					<FieldEditorModal
						field={ getFieldDefinition( editingFieldKey ) }
						fieldTypes={ fieldTypes }
						onUpdate={ handleEditFieldUpdate }
						onDelete={ handleEditFieldDelete }
						onClose={ () => setEditingFieldKey( null ) }
						i18n={ i18n }
					/>
				) }
			</div>
		</div>
	);
}

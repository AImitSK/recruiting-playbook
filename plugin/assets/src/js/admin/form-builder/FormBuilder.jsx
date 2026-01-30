/**
 * FormBuilder Main Component
 *
 * Main entry point for the Form Builder React application.
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../components/ui/tabs';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../components/ui/card';
import { Alert, AlertDescription } from '../components/ui/alert';
import { Button } from '../components/ui/button';
import { Lock, AlertCircle } from 'lucide-react';

import FieldList from './components/FieldList';
import FieldEditor from './components/FieldEditor';
import FieldTypeSelector from './components/FieldTypeSelector';
import TemplateManager from './components/TemplateManager';
import FormPreview from './components/FormPreview';
import { useFieldDefinitions } from './hooks/useFieldDefinitions';
import { useFormTemplates } from './hooks/useFormTemplates';

/**
 * FormBuilder component
 */
export default function FormBuilder() {
	const config = window.rpFormBuilderData || {};
	const { isPro, canManage, fieldTypes, i18n, upgradeUrl } = config;

	const [ activeTab, setActiveTab ] = useState( 'fields' );
	const [ selectedField, setSelectedField ] = useState( null );
	const [ showFieldTypeSelector, setShowFieldTypeSelector ] = useState( false );

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
		refetch: refetchFields,
	} = useFieldDefinitions();

	const {
		templates,
		defaultTemplate,
		isLoading: templatesLoading,
		error: templatesError,
		createTemplate,
		updateTemplate,
		deleteTemplate,
		setDefaultTemplate,
		duplicateTemplate,
		refetch: refetchTemplates,
	} = useFormTemplates();

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

	// Handle field selection
	const handleFieldSelect = ( field ) => {
		setSelectedField( field );
	};

	// Handle field creation
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

	const logoUrl = config.logoUrl || '';

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '900px' } }>
				{ /* Header: Logo links, Titel rechts */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img src={ logoUrl } alt="Recruiting Playbook" style={ { width: '150px', height: 'auto' } } />
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ i18n?.pageTitle || __( 'Formular-Builder', 'recruiting-playbook' ) }
					</h1>
				</div>

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

			{ ( fieldsError || templatesError ) && (
				<Alert variant="destructive" className="mb-4">
					<AlertCircle className="h-4 w-4" />
					<AlertDescription>
						{ fieldsError || templatesError }
					</AlertDescription>
				</Alert>
			) }

			<Tabs value={ activeTab } onValueChange={ setActiveTab }>
				<TabsList className="mb-4">
					<TabsTrigger value="fields">
						{ i18n?.tabFields || __( 'Felder', 'recruiting-playbook' ) }
					</TabsTrigger>
					<TabsTrigger value="templates">
						{ i18n?.tabTemplates || __( 'Templates', 'recruiting-playbook' ) }
						{ ! isPro && <Lock className="ml-1 h-3 w-3" /> }
					</TabsTrigger>
					<TabsTrigger value="preview">
						{ i18n?.tabPreview || __( 'Vorschau', 'recruiting-playbook' ) }
					</TabsTrigger>
				</TabsList>

				<TabsContent value="fields" className="mt-0">
					<div className="rp-form-builder__content rp-grid rp-grid-cols-1 lg:rp-grid-cols-3 rp-gap-6">
						<div className="lg:rp-col-span-2">
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
						<div className="lg:rp-col-span-1">
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

				<TabsContent value="templates" className="mt-0">
					{ ! isPro ? (
						<Card>
							<CardContent className="pt-6">
								<div style={ { display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '1rem', padding: '2rem 0', textAlign: 'center' } }>
									<Lock style={ { height: '3rem', width: '3rem', color: '#9ca3af' } } />
									<h2 style={ { fontSize: '1.25rem', fontWeight: 600, margin: 0 } }>
										{ i18n?.multipleTemplatesPro || __( 'Mehrere Templates (Pro)', 'recruiting-playbook' ) }
									</h2>
									<p style={ { color: '#4b5563', maxWidth: '28rem', margin: 0 } }>
										{ i18n?.templatesProText || __( 'Mit Pro können Sie mehrere Formular-Templates erstellen und verschiedenen Stellen zuweisen.', 'recruiting-playbook' ) }
									</p>
									<Button onClick={ () => window.location.href = upgradeUrl }>
										{ i18n?.upgradeToPro || __( 'Auf Pro upgraden', 'recruiting-playbook' ) }
									</Button>
								</div>
							</CardContent>
						</Card>
					) : (
						<TemplateManager
							templates={ templates }
							defaultTemplate={ defaultTemplate }
							fields={ fields }
							onCreate={ createTemplate }
							onUpdate={ updateTemplate }
							onDelete={ deleteTemplate }
							onSetDefault={ setDefaultTemplate }
							onDuplicate={ duplicateTemplate }
							isLoading={ templatesLoading }
							i18n={ i18n }
						/>
					) }
				</TabsContent>

				<TabsContent value="preview" className="mt-0">
					<FormPreview
						fields={ fields }
						fieldTypes={ fieldTypes }
						i18n={ i18n }
					/>
				</TabsContent>
			</Tabs>

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

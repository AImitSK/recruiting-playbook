/**
 * FieldEditor Component
 *
 * Editor panel for field properties, validation, and conditional logic.
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Textarea } from '../../components/ui/textarea';
import { Switch } from '../../components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../components/ui/tabs';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Select, SelectOption } from '../../components/ui/select';
import { Spinner } from '../../components/ui/spinner';
import { X, Trash2, Lock, AlertCircle } from 'lucide-react';
import OptionsEditor from './OptionsEditor';
import ValidationEditor from './ValidationEditor';


/**
 * FieldEditor component
 *
 * @param {Object} props Component props
 * @param {Object} props.field       Field definition to edit
 * @param {Object} props.fieldTypes  Available field types
 * @param {Array}  props.allFields   All field definitions
 * @param {Function} props.onUpdate    Update handler
 * @param {Function} props.onDelete    Delete handler
 * @param {Function} props.onClose     Close handler
 * @param {boolean} props.isPro       Pro feature access
 * @param {Object} props.i18n         Translations
 */
export default function FieldEditor( {
	field,
	fieldTypes,
	allFields,
	onUpdate,
	onDelete,
	onClose,
	isPro,
	i18n,
} ) {
	const [ localField, setLocalField ] = useState( field );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( 'general' );
	const [ hasChanges, setHasChanges ] = useState( false );
	const [ confirmDelete, setConfirmDelete ] = useState( false );
	const [ error, setError ] = useState( null );

	// Reset local state when field changes
	useEffect( () => {
		setLocalField( field );
		setHasChanges( false );
		setError( null );
	}, [ field.id ] );

	// Track changes
	useEffect( () => {
		const changed = JSON.stringify( localField ) !== JSON.stringify( field );
		setHasChanges( changed );
	}, [ localField, field ] );

	// Update local field value
	const updateLocalField = useCallback( ( updates ) => {
		setLocalField( ( prev ) => ( { ...prev, ...updates } ) );
	}, [] );

	// Update nested settings
	const updateSettings = useCallback( ( key, value ) => {
		setLocalField( ( prev ) => ( {
			...prev,
			settings: { ...( prev.settings || {} ), [ key ]: value },
		} ) );
	}, [] );

	// Update validation rules
	const updateValidation = useCallback( ( validation ) => {
		setLocalField( ( prev ) => ( {
			...prev,
			validation,
		} ) );
	}, [] );

	// Save changes
	const handleSave = async () => {
		if ( ! hasChanges || isSaving ) {
			return;
		}

		setIsSaving( true );
		setError( null );

		try {
			const success = await onUpdate( field.id, localField );
			if ( ! success ) {
				setError( i18n?.saveError || __( 'Error saving', 'recruiting-playbook' ) );
			}
		} catch ( err ) {
			setError( err.message );
		} finally {
			setIsSaving( false );
		}
	};

	// Delete field
	const handleDelete = async () => {
		if ( field.is_system ) {
			return;
		}

		setIsSaving( true );
		await onDelete( field.id );
		setIsSaving( false );
		setConfirmDelete( false );
	};

	// Field type config
	const fieldTypeConfig = fieldTypes[ field.type ] || {};
	const hasOptions = [ 'select', 'radio', 'checkbox' ].includes( field.type );
	const hasValidation = ! [ 'heading' ].includes( field.type );

	return (
		<Card className="rp-field-editor">
			<CardHeader className="pb-3">
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
					<CardTitle style={ { fontSize: '1.125rem', display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						{ i18n?.editField || __( 'Edit field', 'recruiting-playbook' ) }
						{ field.is_system && <Lock style={ { height: '1rem', width: '1rem', color: '#9ca3af' } } /> }
					</CardTitle>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						{ hasChanges && (
							<span style={ { fontSize: '0.75rem', color: '#d97706' } }>
								{ i18n?.unsavedChanges || __( 'Unsaved', 'recruiting-playbook' ) }
							</span>
						) }
						{ isSaving && <Spinner size="small" /> }
						<Button variant="ghost" size="sm" onClick={ onClose }>
							<X style={ { height: '1rem', width: '1rem' } } />
						</Button>
					</div>
				</div>
			</CardHeader>

			<CardContent className="pt-0">
				{ error && (
					<Alert variant="destructive" className="mb-4">
						<AlertCircle className="h-4 w-4" />
						<AlertDescription>{ error }</AlertDescription>
					</Alert>
				) }

				<Tabs value={ activeTab } onValueChange={ setActiveTab }>
					<TabsList style={ { marginBottom: '1rem', width: '100%', display: 'grid', gridTemplateColumns: hasValidation ? 'repeat(2, 1fr)' : '1fr' } }>
						<TabsTrigger value="general">
							{ i18n?.general || __( 'General', 'recruiting-playbook' ) }
						</TabsTrigger>
						{ hasValidation && (
							<TabsTrigger value="validation">
								{ i18n?.validation || __( 'Validation', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
					</TabsList>

					<TabsContent value="general" style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
						{ /* Field Key */ }
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="field_key">
								{ i18n?.fieldKey || __( 'Field key', 'recruiting-playbook' ) }
							</Label>
							<Input
								id="field_key"
								value={ localField.field_key || '' }
								onChange={ ( e ) =>
									updateLocalField( { field_key: e.target.value.replace( /[^a-z0-9_]/gi, '' ).toLowerCase() } )
								}
								placeholder="field_name"
								disabled={ field.is_system }
							/>
							<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
								{ i18n?.fieldKeyHelp || __( 'Unique identifier (no special characters)', 'recruiting-playbook' ) }
							</p>
						</div>

						{ /* Label */ }
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="label">
								{ i18n?.fieldLabel || __( 'Label', 'recruiting-playbook' ) }
							</Label>
							<Input
								id="label"
								value={ localField.label || '' }
								onChange={ ( e ) => updateLocalField( { label: e.target.value } ) }
								placeholder={ i18n?.labelPlaceholder || __( 'Field label', 'recruiting-playbook' ) }
							/>
						</div>

						{ /* Placeholder (for input fields) */ }
						{ [ 'text', 'textarea', 'email', 'phone', 'number', 'url' ].includes( field.type ) && (
							<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
								<Label htmlFor="placeholder">
									{ i18n?.fieldPlaceholder || __( 'Placeholder', 'recruiting-playbook' ) }
								</Label>
								<Input
									id="placeholder"
									value={ localField.placeholder || '' }
									onChange={ ( e ) => updateLocalField( { placeholder: e.target.value } ) }
									placeholder={ i18n?.placeholderHelp || __( 'Placeholder text...', 'recruiting-playbook' ) }
								/>
							</div>
						) }

						{ /* Description */ }
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="description">
								{ i18n?.fieldDescription || __( 'Description', 'recruiting-playbook' ) }
							</Label>
							<Textarea
								id="description"
								value={ localField.description || '' }
								onChange={ ( e ) => updateLocalField( { description: e.target.value } ) }
								placeholder={ i18n?.descriptionHelp || __( 'Help text for the field', 'recruiting-playbook' ) }
								rows={ 2 }
							/>
						</div>

						{ /* Width */ }
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label>{ i18n?.fieldWidth || __( 'Width', 'recruiting-playbook' ) }</Label>
							<Select
								value={ localField.settings?.width || 'full' }
								onChange={ ( e ) => updateSettings( 'width', e.target.value ) }
							>
								<SelectOption value="full">{ i18n?.widthFull || __( 'Full width', 'recruiting-playbook' ) }</SelectOption>
								<SelectOption value="half">{ i18n?.widthHalf || __( 'Half width', 'recruiting-playbook' ) }</SelectOption>
							</Select>
						</div>

						{ /* Options for select/radio/checkbox */ }
						{ hasOptions && (
							<OptionsEditor
								options={ localField.settings?.options || [] }
								onChange={ ( options ) => updateSettings( 'options', options ) }
								fieldType={ field.type }
								i18n={ i18n }
							/>
						) }

						{ /* Required & Enabled toggles */ }
						<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', paddingTop: '1rem', borderTop: '1px solid #e5e7eb' } }>
							<div style={ { display: 'flex', alignItems: 'center', gap: '0.75rem' } }>
								<Switch
									id="is_required"
									checked={ localField.is_required }
									onCheckedChange={ ( checked ) => updateLocalField( { is_required: checked } ) }
								/>
								<Label htmlFor="is_required" style={ { cursor: 'pointer' } }>
									{ i18n?.fieldRequired || __( 'Required field', 'recruiting-playbook' ) }
								</Label>
							</div>

							<div style={ { display: 'flex', alignItems: 'center', gap: '0.75rem' } }>
								<Switch
									id="is_enabled"
									checked={ localField.is_enabled }
									onCheckedChange={ ( checked ) => updateLocalField( { is_enabled: checked } ) }
								/>
								<Label htmlFor="is_enabled" style={ { cursor: 'pointer' } }>
									{ i18n?.fieldEnabled || __( 'Enabled', 'recruiting-playbook' ) }
								</Label>
							</div>
						</div>
					</TabsContent>

					{ hasValidation && (
						<TabsContent value="validation">
							<ValidationEditor
								validation={ localField.validation || {} }
								onChange={ updateValidation }
								fieldType={ field.type }
								i18n={ i18n }
							/>
						</TabsContent>
					) }
				</Tabs>

				{ /* Actions */ }
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', paddingTop: '1rem', marginTop: '1rem', borderTop: '1px solid #e5e7eb' } }>
					{ ! field.is_system ? (
						<>
							{ confirmDelete ? (
								<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
									<span style={ { fontSize: '0.875rem', color: '#dc2626' } }>
										{ i18n?.confirmDelete || __( 'Really delete?', 'recruiting-playbook' ) }
									</span>
									<Button
										variant="destructive"
										size="sm"
										onClick={ handleDelete }
										disabled={ isSaving }
									>
										{ i18n?.yes || __( 'Yes', 'recruiting-playbook' ) }
									</Button>
									<Button
										variant="outline"
										size="sm"
										onClick={ () => setConfirmDelete( false ) }
									>
										{ i18n?.no || __( 'No', 'recruiting-playbook' ) }
									</Button>
								</div>
							) : (
								<Button
									variant="ghost"
									size="sm"
									onClick={ () => setConfirmDelete( true ) }
									style={ { color: '#dc2626' } }
								>
									<Trash2 style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } />
									{ i18n?.delete || __( 'Delete', 'recruiting-playbook' ) }
								</Button>
							) }
						</>
					) : (
						<span style={ { fontSize: '0.75rem', color: '#6b7280' } }>
							{ i18n?.systemFieldWarning || __( 'System fields cannot be deleted', 'recruiting-playbook' ) }
						</span>
					) }

					<Button
						onClick={ handleSave }
						disabled={ ! hasChanges || isSaving }
					>
						{ isSaving
							? ( i18n?.saving || __( 'Saving...', 'recruiting-playbook' ) )
							: ( i18n?.save || __( 'Save', 'recruiting-playbook' ) )
						}
					</Button>
				</div>
			</CardContent>
		</Card>
	);
}

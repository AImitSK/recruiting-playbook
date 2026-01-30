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
import ConditionalEditor from './ConditionalEditor';

/**
 * Debounce helper
 *
 * @param {Function} func     Function to debounce
 * @param {number}   wait     Wait time in ms
 * @return {Function} Debounced function
 */
function useDebounce( value, delay ) {
	const [ debouncedValue, setDebouncedValue ] = useState( value );

	useEffect( () => {
		const handler = setTimeout( () => {
			setDebouncedValue( value );
		}, delay );

		return () => clearTimeout( handler );
	}, [ value, delay ] );

	return debouncedValue;
}

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

	// Debounced field value for auto-save
	const debouncedField = useDebounce( localField, 1000 );

	// Auto-save on debounced changes
	useEffect( () => {
		if ( hasChanges && debouncedField.id === field.id ) {
			handleSave();
		}
	}, [ debouncedField ] );

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

	// Update conditional logic
	const updateConditional = useCallback( ( conditional ) => {
		setLocalField( ( prev ) => ( {
			...prev,
			conditional,
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
				setError( i18n?.saveError || __( 'Fehler beim Speichern', 'recruiting-playbook' ) );
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
	const hasConditional = ! [ 'heading' ].includes( field.type );

	return (
		<Card className="rp-field-editor">
			<CardHeader className="pb-3">
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
					<CardTitle style={ { fontSize: '1.125rem', display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						{ i18n?.editField || __( 'Feld bearbeiten', 'recruiting-playbook' ) }
						{ field.is_system && <Lock style={ { height: '1rem', width: '1rem', color: '#9ca3af' } } /> }
					</CardTitle>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						{ hasChanges && (
							<span style={ { fontSize: '0.75rem', color: '#d97706' } }>
								{ i18n?.unsavedChanges || __( 'Ungespeichert', 'recruiting-playbook' ) }
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
					<TabsList style={ { marginBottom: '1rem', width: '100%', display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)' } }>
						<TabsTrigger value="general">
							{ i18n?.general || __( 'Allgemein', 'recruiting-playbook' ) }
						</TabsTrigger>
						{ hasValidation && (
							<TabsTrigger value="validation">
								{ i18n?.validation || __( 'Validierung', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
						{ hasConditional && (
							<TabsTrigger value="conditional" style={ { display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
								{ i18n?.conditional || __( 'Bedingt', 'recruiting-playbook' ) }
								{ ! isPro && <Lock style={ { height: '0.75rem', width: '0.75rem' } } /> }
							</TabsTrigger>
						) }
					</TabsList>

					<TabsContent value="general" style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
						{ /* Field Key */ }
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="field_key">
								{ i18n?.fieldKey || __( 'Feldschlüssel', 'recruiting-playbook' ) }
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
								{ i18n?.fieldKeyHelp || __( 'Eindeutiger Bezeichner (nur Kleinbuchstaben, Zahlen, Unterstriche)', 'recruiting-playbook' ) }
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
								placeholder={ i18n?.labelPlaceholder || __( 'Feld-Bezeichnung', 'recruiting-playbook' ) }
							/>
						</div>

						{ /* Placeholder (for input fields) */ }
						{ [ 'text', 'textarea', 'email', 'phone', 'number', 'url' ].includes( field.type ) && (
							<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
								<Label htmlFor="placeholder">
									{ i18n?.fieldPlaceholder || __( 'Platzhalter', 'recruiting-playbook' ) }
								</Label>
								<Input
									id="placeholder"
									value={ localField.placeholder || '' }
									onChange={ ( e ) => updateLocalField( { placeholder: e.target.value } ) }
									placeholder={ i18n?.placeholderHelp || __( 'Platzhaltertext...', 'recruiting-playbook' ) }
								/>
							</div>
						) }

						{ /* Description */ }
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="description">
								{ i18n?.fieldDescription || __( 'Beschreibung', 'recruiting-playbook' ) }
							</Label>
							<Textarea
								id="description"
								value={ localField.description || '' }
								onChange={ ( e ) => updateLocalField( { description: e.target.value } ) }
								placeholder={ i18n?.descriptionHelp || __( 'Hilfetext für das Feld', 'recruiting-playbook' ) }
								rows={ 2 }
							/>
						</div>

						{ /* Width */ }
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label>{ i18n?.fieldWidth || __( 'Breite', 'recruiting-playbook' ) }</Label>
							<Select
								value={ localField.settings?.width || 'full' }
								onChange={ ( e ) => updateSettings( 'width', e.target.value ) }
							>
								<SelectOption value="full">{ i18n?.widthFull || __( 'Volle Breite', 'recruiting-playbook' ) }</SelectOption>
								<SelectOption value="half">{ i18n?.widthHalf || __( 'Halbe Breite', 'recruiting-playbook' ) }</SelectOption>
								<SelectOption value="third">{ i18n?.widthThird || __( 'Ein Drittel', 'recruiting-playbook' ) }</SelectOption>
								<SelectOption value="two-thirds">{ i18n?.widthTwoThirds || __( 'Zwei Drittel', 'recruiting-playbook' ) }</SelectOption>
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
									{ i18n?.fieldRequired || __( 'Pflichtfeld', 'recruiting-playbook' ) }
								</Label>
							</div>

							<div style={ { display: 'flex', alignItems: 'center', gap: '0.75rem' } }>
								<Switch
									id="is_enabled"
									checked={ localField.is_enabled }
									onCheckedChange={ ( checked ) => updateLocalField( { is_enabled: checked } ) }
								/>
								<Label htmlFor="is_enabled" style={ { cursor: 'pointer' } }>
									{ i18n?.fieldEnabled || __( 'Aktiviert', 'recruiting-playbook' ) }
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

					{ hasConditional && (
						<TabsContent value="conditional">
							{ isPro ? (
								<ConditionalEditor
									conditional={ localField.conditional || {} }
									onChange={ updateConditional }
									allFields={ allFields.filter( ( f ) => f.id !== field.id ) }
									i18n={ i18n }
								/>
							) : (
								<div style={ { textAlign: 'center', padding: '2rem 0' } }>
									<Lock style={ { height: '2rem', width: '2rem', margin: '0 auto 0.5rem', color: '#9ca3af' } } />
									<p style={ { fontSize: '0.875rem', color: '#4b5563', margin: 0 } }>
										{ i18n?.conditionalLogicPro || __( 'Bedingte Logik ist ein Pro-Feature', 'recruiting-playbook' ) }
									</p>
								</div>
							) }
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
										{ i18n?.confirmDelete || __( 'Wirklich löschen?', 'recruiting-playbook' ) }
									</span>
									<Button
										variant="destructive"
										size="sm"
										onClick={ handleDelete }
										disabled={ isSaving }
									>
										{ i18n?.yes || __( 'Ja', 'recruiting-playbook' ) }
									</Button>
									<Button
										variant="outline"
										size="sm"
										onClick={ () => setConfirmDelete( false ) }
									>
										{ i18n?.no || __( 'Nein', 'recruiting-playbook' ) }
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
									{ i18n?.delete || __( 'Löschen', 'recruiting-playbook' ) }
								</Button>
							) }
						</>
					) : (
						<span style={ { fontSize: '0.75rem', color: '#6b7280' } }>
							{ i18n?.systemFieldWarning || __( 'System-Felder können nicht gelöscht werden', 'recruiting-playbook' ) }
						</span>
					) }

					<Button
						onClick={ handleSave }
						disabled={ ! hasChanges || isSaving }
					>
						{ isSaving
							? ( i18n?.saving || __( 'Speichern...', 'recruiting-playbook' ) )
							: ( i18n?.save || __( 'Speichern', 'recruiting-playbook' ) )
						}
					</Button>
				</div>
			</CardContent>
		</Card>
	);
}

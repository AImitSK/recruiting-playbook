/**
 * FieldEditorModal Component
 *
 * Modal dialog for editing custom field properties.
 * Simplified version of FieldEditor without conditional logic tab.
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Textarea } from '../../components/ui/textarea';
import { Switch } from '../../components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../../components/ui/tabs';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Select, SelectOption } from '../../components/ui/select';
import { Spinner } from '../../components/ui/spinner';
import { X, Trash2, AlertCircle } from 'lucide-react';
import OptionsEditor from './OptionsEditor';
import ValidationEditor from './ValidationEditor';

/**
 * FieldEditorModal component
 *
 * @param {Object}   props            Component props
 * @param {Object}   props.field      Field definition to edit
 * @param {Object}   props.fieldTypes Available field types
 * @param {Function} props.onUpdate   Update handler
 * @param {Function} props.onDelete   Delete handler (optional)
 * @param {Function} props.onClose    Close handler
 * @param {Object}   props.i18n       Translations
 */
export default function FieldEditorModal( {
	field,
	fieldTypes,
	onUpdate,
	onDelete,
	onClose,
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
	}, [ field?.id ] );

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
			if ( success ) {
				onClose();
			} else {
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
		if ( ! onDelete ) {
			return;
		}

		setIsSaving( true );
		await onDelete( field.id );
		setIsSaving( false );
		setConfirmDelete( false );
		onClose();
	};

	// Close on Escape key
	useEffect( () => {
		const handleKeyDown = ( e ) => {
			if ( e.key === 'Escape' ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', handleKeyDown );
		return () => document.removeEventListener( 'keydown', handleKeyDown );
	}, [ onClose ] );

	// Field type config (API returns field_type, not type)
	const fieldType = field?.field_type || field?.type;
	const fieldTypeConfig = fieldTypes?.[ fieldType ] || {};
	const hasOptions = [ 'select', 'radio', 'checkbox' ].includes( fieldType );
	const hasValidation = ! [ 'heading' ].includes( fieldType );

	if ( ! field ) {
		return null;
	}

	return (
		<div
			className="rp-field-editor-modal__overlay"
			style={ {
				position: 'fixed',
				inset: 0,
				backgroundColor: 'rgba(0, 0, 0, 0.5)',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				zIndex: 100000,
			} }
			onClick={ ( e ) => {
				if ( e.target === e.currentTarget ) {
					onClose();
				}
			} }
		>
			<div
				className="rp-field-editor-modal"
				style={ {
					backgroundColor: 'white',
					borderRadius: '0.5rem',
					boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
					width: '100%',
					maxWidth: '32rem',
					maxHeight: '90vh',
					display: 'flex',
					flexDirection: 'column',
					overflow: 'hidden',
				} }
			>
				{ /* Header */ }
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						padding: '1rem 1.5rem',
						borderBottom: '1px solid #e5e7eb',
					} }
				>
					<h2 style={ { margin: 0, fontSize: '1.125rem', fontWeight: 600 } }>
						{ i18n?.editField || __( 'Feld bearbeiten', 'recruiting-playbook' ) }
						{ field.label && (
							<span style={ { fontWeight: 400, color: '#6b7280' } }>
								: { field.label }
							</span>
						) }
					</h2>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						{ hasChanges && (
							<span style={ { fontSize: '0.75rem', color: '#d97706' } }>
								{ i18n?.unsavedChanges || __( 'Ungespeichert', 'recruiting-playbook' ) }
							</span>
						) }
						{ isSaving && <Spinner size="small" /> }
						<Button variant="ghost" size="sm" onClick={ onClose }>
							<X style={ { height: '1.25rem', width: '1.25rem' } } />
						</Button>
					</div>
				</div>

				{ /* Content */ }
				<div
					style={ {
						padding: '1.5rem',
						overflowY: 'auto',
						flex: 1,
					} }
				>
					{ error && (
						<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
							<AlertCircle style={ { height: '1rem', width: '1rem' } } />
							<AlertDescription>{ error }</AlertDescription>
						</Alert>
					) }

					<Tabs value={ activeTab } onValueChange={ setActiveTab }>
						<TabsList
							style={ {
								marginBottom: '1rem',
								width: '100%',
								display: 'grid',
								gridTemplateColumns: hasValidation ? 'repeat(2, 1fr)' : '1fr',
							} }
						>
							<TabsTrigger value="general">
								{ i18n?.general || __( 'Allgemein', 'recruiting-playbook' ) }
							</TabsTrigger>
							{ hasValidation && (
								<TabsTrigger value="validation">
									{ i18n?.validation || __( 'Validierung', 'recruiting-playbook' ) }
								</TabsTrigger>
							) }
						</TabsList>

						<TabsContent value="general" style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
							{ /* Label */ }
							<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
								<Label htmlFor="label">
									{ i18n?.fieldLabel || __( 'Bezeichnung', 'recruiting-playbook' ) }
								</Label>
								<Input
									id="label"
									value={ localField.label || '' }
									onChange={ ( e ) => updateLocalField( { label: e.target.value } ) }
									placeholder={ i18n?.labelPlaceholder || __( 'Feld-Bezeichnung', 'recruiting-playbook' ) }
								/>
							</div>

							{ /* Placeholder (for input fields) */ }
							{ [ 'text', 'textarea', 'email', 'phone', 'number', 'url' ].includes( fieldType ) && (
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
									<SelectOption value="full">
										{ i18n?.widthFull || __( 'Volle Breite', 'recruiting-playbook' ) }
									</SelectOption>
									<SelectOption value="half">
										{ i18n?.widthHalf || __( 'Halbe Breite', 'recruiting-playbook' ) }
									</SelectOption>
								</Select>
							</div>

							{ /* Options for select/radio/checkbox */ }
							{ hasOptions && (
								<OptionsEditor
									options={ localField.settings?.options || [] }
									onChange={ ( options ) => updateSettings( 'options', options ) }
									fieldType={ fieldType }
									i18n={ i18n }
								/>
							) }

							{ /* Required toggle */ }
							<div
								style={ {
									display: 'flex',
									alignItems: 'center',
									gap: '0.75rem',
									paddingTop: '1rem',
									borderTop: '1px solid #e5e7eb',
								} }
							>
								<Switch
									id="is_required"
									checked={ localField.is_required }
									onCheckedChange={ ( checked ) => updateLocalField( { is_required: checked } ) }
								/>
								<Label htmlFor="is_required" style={ { cursor: 'pointer' } }>
									{ i18n?.fieldRequired || __( 'Pflichtfeld', 'recruiting-playbook' ) }
								</Label>
							</div>
						</TabsContent>

						{ hasValidation && (
							<TabsContent value="validation">
								<ValidationEditor
									validation={ localField.validation || {} }
									onChange={ updateValidation }
									fieldType={ fieldType }
									i18n={ i18n }
								/>
							</TabsContent>
						) }
					</Tabs>
				</div>

				{ /* Footer */ }
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						padding: '1rem 1.5rem',
						borderTop: '1px solid #e5e7eb',
						backgroundColor: '#f9fafb',
					} }
				>
					{ onDelete ? (
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
						<div />
					) }

					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<Button variant="outline" onClick={ onClose }>
							{ i18n?.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
						</Button>
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
				</div>
			</div>
		</div>
	);
}

/**
 * TemplateManager Component
 *
 * Management interface for form templates.
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Textarea } from '../../components/ui/textarea';
import { Switch } from '../../components/ui/switch';
import { Badge } from '../../components/ui/badge';
import { Spinner } from '../../components/ui/spinner';
import {
	Plus,
	Trash2,
	Copy,
	Edit2,
	Star,
	Check,
	X,
	FileText,
} from 'lucide-react';

/**
 * TemplateManager component
 *
 * @param {Object} props Component props
 * @param {Array}  props.templates       Template list
 * @param {Object} props.defaultTemplate Default template
 * @param {Array}  props.fields          Available fields
 * @param {Function} props.onCreate        Create handler
 * @param {Function} props.onUpdate        Update handler
 * @param {Function} props.onDelete        Delete handler
 * @param {Function} props.onSetDefault    Set default handler
 * @param {Function} props.onDuplicate     Duplicate handler
 * @param {boolean} props.isLoading       Loading state
 * @param {Object} props.i18n            Translations
 */
export default function TemplateManager( {
	templates = [],
	defaultTemplate,
	fields = [],
	onCreate,
	onUpdate,
	onDelete,
	onSetDefault,
	onDuplicate,
	isLoading,
	i18n,
} ) {
	const [ editingTemplate, setEditingTemplate ] = useState( null );
	const [ showCreateForm, setShowCreateForm ] = useState( false );
	const [ confirmDelete, setConfirmDelete ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );

	// New template form state
	const [ newTemplate, setNewTemplate ] = useState( {
		name: '',
		description: '',
		field_ids: [],
	} );

	// Handle create
	const handleCreate = async () => {
		if ( ! newTemplate.name ) {
			return;
		}

		setIsSaving( true );
		const result = await onCreate( newTemplate );
		setIsSaving( false );

		if ( result ) {
			setShowCreateForm( false );
			setNewTemplate( { name: '', description: '', field_ids: [] } );
		}
	};

	// Handle update
	const handleUpdate = async () => {
		if ( ! editingTemplate || ! editingTemplate.name ) {
			return;
		}

		setIsSaving( true );
		const result = await onUpdate( editingTemplate.id, editingTemplate );
		setIsSaving( false );

		if ( result ) {
			setEditingTemplate( null );
		}
	};

	// Handle delete
	const handleDelete = async ( templateId ) => {
		setIsSaving( true );
		await onDelete( templateId );
		setIsSaving( false );
		setConfirmDelete( null );
	};

	// Handle set default
	const handleSetDefault = async ( templateId ) => {
		setIsSaving( true );
		await onSetDefault( templateId );
		setIsSaving( false );
	};

	// Handle duplicate
	const handleDuplicate = async ( templateId ) => {
		setIsSaving( true );
		await onDuplicate( templateId );
		setIsSaving( false );
	};

	// Toggle field in template
	const toggleFieldInTemplate = useCallback( ( fieldId, template, setTemplate ) => {
		const currentIds = template.field_ids || [];
		const newIds = currentIds.includes( fieldId )
			? currentIds.filter( ( id ) => id !== fieldId )
			: [ ...currentIds, fieldId ];
		setTemplate( { ...template, field_ids: newIds } );
	}, [] );

	if ( isLoading ) {
		return (
			<Card>
				<CardContent style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '3rem 0' } }>
					<Spinner />
				</CardContent>
			</Card>
		);
	}

	return (
		<div className="rp-template-manager" style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
			{ /* Header with create button */ }
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
				<div>
					<h2 style={ { fontSize: '1.125rem', fontWeight: 600, margin: '0 0 0.25rem 0' } }>
						{ i18n?.templates || __( 'Formular-Templates', 'recruiting-playbook' ) }
					</h2>
					<p style={ { fontSize: '0.875rem', color: '#4b5563', margin: 0 } }>
						{ i18n?.templatesDescription || __( 'Erstellen Sie verschiedene Formular-Konfigurationen für unterschiedliche Stellen', 'recruiting-playbook' ) }
					</p>
				</div>
				<Button onClick={ () => setShowCreateForm( true ) } disabled={ showCreateForm }>
					<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } />
					{ i18n?.createTemplate || __( 'Template erstellen', 'recruiting-playbook' ) }
				</Button>
			</div>

			{ /* Create Form */ }
			{ showCreateForm && (
				<Card className="border-blue-200 bg-blue-50">
					<CardHeader className="pb-3">
						<CardTitle className="text-base">
							{ i18n?.newTemplate || __( 'Neues Template', 'recruiting-playbook' ) }
						</CardTitle>
					</CardHeader>
					<CardContent style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="new_name">
								{ i18n?.templateName || __( 'Name', 'recruiting-playbook' ) }
							</Label>
							<Input
								id="new_name"
								value={ newTemplate.name }
								onChange={ ( e ) => setNewTemplate( { ...newTemplate, name: e.target.value } ) }
								placeholder={ i18n?.templateNamePlaceholder || __( 'z.B. Entwickler-Bewerbung', 'recruiting-playbook' ) }
							/>
						</div>

						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="new_description">
								{ i18n?.templateDescription || __( 'Beschreibung', 'recruiting-playbook' ) }
							</Label>
							<Textarea
								id="new_description"
								value={ newTemplate.description }
								onChange={ ( e ) => setNewTemplate( { ...newTemplate, description: e.target.value } ) }
								placeholder={ i18n?.templateDescriptionPlaceholder || __( 'Optionale Beschreibung...', 'recruiting-playbook' ) }
								rows={ 2 }
							/>
						</div>

						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label>{ i18n?.selectFields || __( 'Felder auswählen', 'recruiting-playbook' ) }</Label>
							<div style={ { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.5rem', maxHeight: '10rem', overflowY: 'auto', padding: '0.5rem', backgroundColor: '#fff', borderRadius: '0.25rem', border: '1px solid #e5e7eb' } }>
								{ fields.map( ( field ) => (
									<label
										key={ field.id }
										style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', fontSize: '0.875rem' } }
									>
										<input
											type="checkbox"
											checked={ newTemplate.field_ids.includes( field.id ) }
											onChange={ () => toggleFieldInTemplate( field.id, newTemplate, setNewTemplate ) }
											style={ { height: '1rem', width: '1rem' } }
										/>
										<span style={ { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }>{ field.label }</span>
									</label>
								) ) }
							</div>
						</div>

						<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', paddingTop: '0.5rem' } }>
							<Button
								variant="outline"
								onClick={ () => {
									setShowCreateForm( false );
									setNewTemplate( { name: '', description: '', field_ids: [] } );
								} }
							>
								{ i18n?.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
							</Button>
							<Button onClick={ handleCreate } disabled={ ! newTemplate.name || isSaving }>
								{ isSaving ? <Spinner size="small" /> : <Check style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } /> }
								{ i18n?.create || __( 'Erstellen', 'recruiting-playbook' ) }
							</Button>
						</div>
					</CardContent>
				</Card>
			) }

			{ /* Templates List */ }
			{ templates.length === 0 && ! showCreateForm ? (
				<Card>
					<CardContent style={ { padding: '3rem 0', textAlign: 'center' } }>
						<FileText style={ { height: '3rem', width: '3rem', margin: '0 auto 1rem', color: '#9ca3af' } } />
						<h3 style={ { fontSize: '1.125rem', fontWeight: 500, marginBottom: '0.5rem' } }>
							{ i18n?.noTemplates || __( 'Keine Templates vorhanden', 'recruiting-playbook' ) }
						</h3>
						<p style={ { color: '#4b5563', marginBottom: '1rem' } }>
							{ i18n?.noTemplatesDescription || __( 'Erstellen Sie Ihr erstes Template, um verschiedene Formular-Konfigurationen zu speichern.', 'recruiting-playbook' ) }
						</p>
						<Button onClick={ () => setShowCreateForm( true ) }>
							<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } />
							{ i18n?.createFirstTemplate || __( 'Erstes Template erstellen', 'recruiting-playbook' ) }
						</Button>
					</CardContent>
				</Card>
			) : (
				<div style={ { display: 'grid', gap: '1rem' } }>
					{ templates.map( ( template ) => {
						const isEditing = editingTemplate?.id === template.id;
						const isDeleting = confirmDelete === template.id;
						const isDefault = template.is_default;

						return (
							<Card key={ template.id } className={ isDefault ? 'border-blue-300' : '' }>
								<CardContent className="p-4">
									{ isEditing ? (
										// Edit mode
										<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
											<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
												<Label>{ i18n?.templateName || __( 'Name', 'recruiting-playbook' ) }</Label>
												<Input
													value={ editingTemplate.name }
													onChange={ ( e ) =>
														setEditingTemplate( { ...editingTemplate, name: e.target.value } )
													}
												/>
											</div>

											<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
												<Label>{ i18n?.templateDescription || __( 'Beschreibung', 'recruiting-playbook' ) }</Label>
												<Textarea
													value={ editingTemplate.description || '' }
													onChange={ ( e ) =>
														setEditingTemplate( { ...editingTemplate, description: e.target.value } )
													}
													rows={ 2 }
												/>
											</div>

											<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
												<Label>{ i18n?.selectFields || __( 'Felder', 'recruiting-playbook' ) }</Label>
												<div style={ { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.5rem', maxHeight: '10rem', overflowY: 'auto', padding: '0.5rem', backgroundColor: '#f9fafb', borderRadius: '0.25rem', border: '1px solid #e5e7eb' } }>
													{ fields.map( ( field ) => (
														<label
															key={ field.id }
															style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', fontSize: '0.875rem' } }
														>
															<input
																type="checkbox"
																checked={ ( editingTemplate.field_ids || [] ).includes( field.id ) }
																onChange={ () =>
																	toggleFieldInTemplate( field.id, editingTemplate, setEditingTemplate )
																}
																style={ { height: '1rem', width: '1rem' } }
															/>
															<span style={ { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }>{ field.label }</span>
														</label>
													) ) }
												</div>
											</div>

											<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.5rem' } }>
												<Button variant="outline" onClick={ () => setEditingTemplate( null ) }>
													{ i18n?.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
												</Button>
												<Button onClick={ handleUpdate } disabled={ isSaving }>
													{ isSaving ? <Spinner size="small" /> : <Check style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } /> }
													{ i18n?.save || __( 'Speichern', 'recruiting-playbook' ) }
												</Button>
											</div>
										</div>
									) : (
										// View mode
										<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
											<div style={ { flex: 1 } }>
												<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
													<h3 style={ { fontWeight: 500, margin: 0 } }>{ template.name }</h3>
													{ isDefault && (
														<Badge style={ { backgroundColor: '#dbeafe', color: '#1d4ed8' } }>
															<Star style={ { height: '0.75rem', width: '0.75rem', marginRight: '0.25rem' } } />
															{ i18n?.default || __( 'Standard', 'recruiting-playbook' ) }
														</Badge>
													) }
												</div>
												{ template.description && (
													<p style={ { fontSize: '0.875rem', color: '#4b5563', marginTop: '0.25rem' } }>{ template.description }</p>
												) }
												<p style={ { fontSize: '0.75rem', color: '#6b7280', marginTop: '0.5rem' } }>
													{ ( template.field_ids || [] ).length } { i18n?.fields || __( 'Felder', 'recruiting-playbook' ) }
												</p>
											</div>

											<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
												{ isDeleting ? (
													<>
														<span style={ { fontSize: '0.875rem', color: '#dc2626', marginRight: '0.5rem' } }>
															{ i18n?.confirmDelete || __( 'Wirklich löschen?', 'recruiting-playbook' ) }
														</span>
														<Button
															variant="destructive"
															size="sm"
															onClick={ () => handleDelete( template.id ) }
															disabled={ isSaving }
														>
															{ i18n?.yes || __( 'Ja', 'recruiting-playbook' ) }
														</Button>
														<Button
															variant="outline"
															size="sm"
															onClick={ () => setConfirmDelete( null ) }
														>
															{ i18n?.no || __( 'Nein', 'recruiting-playbook' ) }
														</Button>
													</>
												) : (
													<>
														{ ! isDefault && (
															<Button
																variant="outline"
																size="sm"
																onClick={ () => handleSetDefault( template.id ) }
																disabled={ isSaving }
																title={ i18n?.setAsDefault || __( 'Als Standard setzen', 'recruiting-playbook' ) }
															>
																<Star style={ { height: '1rem', width: '1rem' } } />
															</Button>
														) }
														<Button
															variant="outline"
															size="sm"
															onClick={ () => handleDuplicate( template.id ) }
															disabled={ isSaving }
															title={ i18n?.duplicate || __( 'Duplizieren', 'recruiting-playbook' ) }
														>
															<Copy style={ { height: '1rem', width: '1rem' } } />
														</Button>
														<Button
															variant="outline"
															size="sm"
															onClick={ () => setEditingTemplate( template ) }
															title={ i18n?.edit || __( 'Bearbeiten', 'recruiting-playbook' ) }
														>
															<Edit2 style={ { height: '1rem', width: '1rem' } } />
														</Button>
														{ ! isDefault && (
															<Button
																variant="ghost"
																size="sm"
																onClick={ () => setConfirmDelete( template.id ) }
																style={ { color: '#ef4444' } }
																title={ i18n?.delete || __( 'Löschen', 'recruiting-playbook' ) }
															>
																<Trash2 style={ { height: '1rem', width: '1rem' } } />
															</Button>
														) }
													</>
												) }
											</div>
										</div>
									) }
								</CardContent>
							</Card>
						);
					} ) }
				</div>
			) }

			{ /* Help text */ }
			<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
				{ i18n?.templateTip || __( 'Templates ermöglichen verschiedene Formular-Konfigurationen für unterschiedliche Stellen. Das Standard-Template wird für neue Stellen verwendet.', 'recruiting-playbook' ) }
			</p>
		</div>
	);
}

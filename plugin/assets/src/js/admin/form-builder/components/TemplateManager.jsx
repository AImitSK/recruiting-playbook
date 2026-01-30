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
				<CardContent className="flex items-center justify-center py-12">
					<Spinner />
				</CardContent>
			</Card>
		);
	}

	return (
		<div className="rp-template-manager space-y-6">
			{ /* Header with create button */ }
			<div className="flex items-center justify-between">
				<div>
					<h2 className="text-lg font-semibold">
						{ i18n?.templates || __( 'Formular-Templates', 'recruiting-playbook' ) }
					</h2>
					<p className="text-sm text-gray-600">
						{ i18n?.templatesDescription || __( 'Erstellen Sie verschiedene Formular-Konfigurationen für unterschiedliche Stellen', 'recruiting-playbook' ) }
					</p>
				</div>
				<Button onClick={ () => setShowCreateForm( true ) } disabled={ showCreateForm }>
					<Plus className="h-4 w-4 mr-1" />
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
					<CardContent className="space-y-4">
						<div className="space-y-2">
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

						<div className="space-y-2">
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

						<div className="space-y-2">
							<Label>{ i18n?.selectFields || __( 'Felder auswählen', 'recruiting-playbook' ) }</Label>
							<div className="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 bg-white rounded border">
								{ fields.map( ( field ) => (
									<label
										key={ field.id }
										className="flex items-center gap-2 cursor-pointer text-sm"
									>
										<input
											type="checkbox"
											checked={ newTemplate.field_ids.includes( field.id ) }
											onChange={ () => toggleFieldInTemplate( field.id, newTemplate, setNewTemplate ) }
											className="h-4 w-4"
										/>
										<span className="truncate">{ field.label }</span>
									</label>
								) ) }
							</div>
						</div>

						<div className="flex justify-end gap-2 pt-2">
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
								{ isSaving ? <Spinner size="small" /> : <Check className="h-4 w-4 mr-1" /> }
								{ i18n?.create || __( 'Erstellen', 'recruiting-playbook' ) }
							</Button>
						</div>
					</CardContent>
				</Card>
			) }

			{ /* Templates List */ }
			{ templates.length === 0 && ! showCreateForm ? (
				<Card>
					<CardContent className="py-12 text-center">
						<FileText className="h-12 w-12 mx-auto text-gray-400 mb-4" />
						<h3 className="text-lg font-medium mb-2">
							{ i18n?.noTemplates || __( 'Keine Templates vorhanden', 'recruiting-playbook' ) }
						</h3>
						<p className="text-gray-600 mb-4">
							{ i18n?.noTemplatesDescription || __( 'Erstellen Sie Ihr erstes Template, um verschiedene Formular-Konfigurationen zu speichern.', 'recruiting-playbook' ) }
						</p>
						<Button onClick={ () => setShowCreateForm( true ) }>
							<Plus className="h-4 w-4 mr-1" />
							{ i18n?.createFirstTemplate || __( 'Erstes Template erstellen', 'recruiting-playbook' ) }
						</Button>
					</CardContent>
				</Card>
			) : (
				<div className="grid gap-4">
					{ templates.map( ( template ) => {
						const isEditing = editingTemplate?.id === template.id;
						const isDeleting = confirmDelete === template.id;
						const isDefault = template.is_default;

						return (
							<Card key={ template.id } className={ isDefault ? 'border-blue-300' : '' }>
								<CardContent className="p-4">
									{ isEditing ? (
										// Edit mode
										<div className="space-y-4">
											<div className="space-y-2">
												<Label>{ i18n?.templateName || __( 'Name', 'recruiting-playbook' ) }</Label>
												<Input
													value={ editingTemplate.name }
													onChange={ ( e ) =>
														setEditingTemplate( { ...editingTemplate, name: e.target.value } )
													}
												/>
											</div>

											<div className="space-y-2">
												<Label>{ i18n?.templateDescription || __( 'Beschreibung', 'recruiting-playbook' ) }</Label>
												<Textarea
													value={ editingTemplate.description || '' }
													onChange={ ( e ) =>
														setEditingTemplate( { ...editingTemplate, description: e.target.value } )
													}
													rows={ 2 }
												/>
											</div>

											<div className="space-y-2">
												<Label>{ i18n?.selectFields || __( 'Felder', 'recruiting-playbook' ) }</Label>
												<div className="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 bg-gray-50 rounded border">
													{ fields.map( ( field ) => (
														<label
															key={ field.id }
															className="flex items-center gap-2 cursor-pointer text-sm"
														>
															<input
																type="checkbox"
																checked={ ( editingTemplate.field_ids || [] ).includes( field.id ) }
																onChange={ () =>
																	toggleFieldInTemplate( field.id, editingTemplate, setEditingTemplate )
																}
																className="h-4 w-4"
															/>
															<span className="truncate">{ field.label }</span>
														</label>
													) ) }
												</div>
											</div>

											<div className="flex justify-end gap-2">
												<Button variant="outline" onClick={ () => setEditingTemplate( null ) }>
													{ i18n?.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
												</Button>
												<Button onClick={ handleUpdate } disabled={ isSaving }>
													{ isSaving ? <Spinner size="small" /> : <Check className="h-4 w-4 mr-1" /> }
													{ i18n?.save || __( 'Speichern', 'recruiting-playbook' ) }
												</Button>
											</div>
										</div>
									) : (
										// View mode
										<div className="flex items-center justify-between">
											<div className="flex-1">
												<div className="flex items-center gap-2">
													<h3 className="font-medium">{ template.name }</h3>
													{ isDefault && (
														<Badge className="bg-blue-100 text-blue-700">
															<Star className="h-3 w-3 mr-1" />
															{ i18n?.default || __( 'Standard', 'recruiting-playbook' ) }
														</Badge>
													) }
												</div>
												{ template.description && (
													<p className="text-sm text-gray-600 mt-1">{ template.description }</p>
												) }
												<p className="text-xs text-gray-500 mt-2">
													{ ( template.field_ids || [] ).length } { i18n?.fields || __( 'Felder', 'recruiting-playbook' ) }
												</p>
											</div>

											<div className="flex items-center gap-2">
												{ isDeleting ? (
													<>
														<span className="text-sm text-red-600 mr-2">
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
																<Star className="h-4 w-4" />
															</Button>
														) }
														<Button
															variant="outline"
															size="sm"
															onClick={ () => handleDuplicate( template.id ) }
															disabled={ isSaving }
															title={ i18n?.duplicate || __( 'Duplizieren', 'recruiting-playbook' ) }
														>
															<Copy className="h-4 w-4" />
														</Button>
														<Button
															variant="outline"
															size="sm"
															onClick={ () => setEditingTemplate( template ) }
															title={ i18n?.edit || __( 'Bearbeiten', 'recruiting-playbook' ) }
														>
															<Edit2 className="h-4 w-4" />
														</Button>
														{ ! isDefault && (
															<Button
																variant="ghost"
																size="sm"
																onClick={ () => setConfirmDelete( template.id ) }
																className="text-red-500 hover:text-red-700 hover:bg-red-50"
																title={ i18n?.delete || __( 'Löschen', 'recruiting-playbook' ) }
															>
																<Trash2 className="h-4 w-4" />
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
			<p className="text-xs text-gray-500">
				{ i18n?.templateTip || __( 'Templates ermöglichen verschiedene Formular-Konfigurationen für unterschiedliche Stellen. Das Standard-Template wird für neue Stellen verwendet.', 'recruiting-playbook' ) }
			</p>
		</div>
	);
}

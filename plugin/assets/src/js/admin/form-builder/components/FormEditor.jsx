/**
 * FormEditor Component
 *
 * Step-based form editor for configuring application form structure.
 * Shows visual step boxes with draggable fields.
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	DndContext,
	closestCenter,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
	DragOverlay,
} from '@dnd-kit/core';
import {
	arrayMove,
	SortableContext,
	sortableKeyboardCoordinates,
	verticalListSortingStrategy,
	useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Card, CardContent, CardHeader } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Badge } from '../../components/ui/badge';
import {
	Plus,
	GripVertical,
	Trash2,
	ChevronDown,
	ChevronUp,
	Flag,
	Edit2,
	X,
	Check,
} from 'lucide-react';
import { Input } from '../../components/ui/input';

/**
 * SortableFieldItem - Draggable field within a step
 */
function SortableFieldItem( {
	fieldConfig,
	stepId,
	fieldDef,
	onToggleRequired,
	onRemove,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( {
		id: `${ stepId }:${ fieldConfig.field_key }`,
		data: {
			type: 'field',
			stepId,
			fieldKey: fieldConfig.field_key,
		},
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
	};

	const label = fieldDef?.label || fieldConfig.field_key;
	const fieldType = fieldDef?.field_type || 'text';

	return (
		<div
			ref={ setNodeRef }
			style={ {
				...style,
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				padding: '0.5rem 0.75rem',
				backgroundColor: isDragging ? '#dbeafe' : '#f9fafb',
				borderRadius: '0.375rem',
				border: isDragging ? '1px solid #3b82f6' : '1px solid #e5e7eb',
			} }
		>
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
				<button
					type="button"
					style={ {
						cursor: 'grab',
						color: '#9ca3af',
						padding: '0.25rem',
						background: 'none',
						border: 'none',
						display: 'flex',
						alignItems: 'center',
					} }
					{ ...attributes }
					{ ...listeners }
				>
					<GripVertical style={ { height: '1rem', width: '1rem' } } />
				</button>
				<span style={ { fontWeight: 500 } }>{ label }</span>
				<Badge variant="outline" style={ { fontSize: '0.75rem' } }>
					{ fieldType }
				</Badge>
				{ fieldConfig.is_required && (
					<Badge style={ { backgroundColor: '#ef4444', fontSize: '0.75rem' } }>
						{ __( 'Pflicht', 'recruiting-playbook' ) }
					</Badge>
				) }
			</div>
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
				<Button
					variant="ghost"
					size="sm"
					onClick={ () => onToggleRequired( stepId, fieldConfig.field_key, fieldConfig.is_required ) }
					title={ fieldConfig.is_required ? __( 'Optional machen', 'recruiting-playbook' ) : __( 'Pflichtfeld machen', 'recruiting-playbook' ) }
				>
					{ fieldConfig.is_required ? '*' : 'opt' }
				</Button>
				<Button
					variant="ghost"
					size="sm"
					onClick={ () => onRemove( stepId, fieldConfig.field_key ) }
					style={ { color: '#ef4444' } }
				>
					<X style={ { height: '1rem', width: '1rem' } } />
				</Button>
			</div>
		</div>
	);
}

/**
 * SortableStep - Draggable step card
 */
function SortableStep( {
	step,
	index,
	isFinale,
	isExpanded,
	isEditingTitle,
	editingTitle,
	setEditingTitle,
	onToggleExpanded,
	onStartEditingTitle,
	onSaveTitle,
	onCancelEditingTitle,
	onRemove,
	onToggleRequired,
	onRemoveField,
	onAddField,
	showAddFieldFor,
	setShowAddFieldFor,
	unusedFields,
	getFieldDefinition,
	sensors,
	onFieldDragEnd,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( {
		id: step.id,
		disabled: isFinale,
		data: {
			type: 'step',
			stepId: step.id,
		},
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
		marginBottom: '1rem',
		border: isFinale ? '2px solid #10b981' : isDragging ? '2px solid #3b82f6' : undefined,
	};

	const stepFields = step.fields || [];
	const visibleFields = stepFields.filter( ( f ) => f.is_visible );
	const fieldIds = visibleFields.map( ( f ) => `${ step.id }:${ f.field_key }` );

	return (
		<Card
			ref={ setNodeRef }
			style={ style }
			className="rp-form-editor__step"
		>
			<CardHeader style={ { padding: '0.75rem 1rem' } }>
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', flex: 1 } }>
						{ /* Drag handle */ }
						{ ! isFinale && (
							<button
								type="button"
								style={ {
									cursor: 'grab',
									color: '#9ca3af',
									padding: '0.25rem',
									background: 'none',
									border: 'none',
									display: 'flex',
									alignItems: 'center',
								} }
								{ ...attributes }
								{ ...listeners }
							>
								<GripVertical style={ { height: '1.25rem', width: '1.25rem' } } />
							</button>
						) }

						{ /* Step number badge */ }
						<Badge
							variant={ isFinale ? 'default' : 'outline' }
							style={ isFinale ? { backgroundColor: '#10b981' } : {} }
						>
							{ isFinale ? (
								<>
									<Flag style={ { height: '0.75rem', width: '0.75rem', marginRight: '0.25rem' } } />
									{ __( 'Finale', 'recruiting-playbook' ) }
								</>
							) : (
								<>{ index + 1 }</>
							) }
						</Badge>

						{ /* Step title (editable) */ }
						{ isEditingTitle ? (
							<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', flex: 1 } }>
								<Input
									value={ editingTitle }
									onChange={ ( e ) => setEditingTitle( e.target.value ) }
									onKeyDown={ ( e ) => {
										if ( e.key === 'Enter' ) {
											onSaveTitle();
										}
										if ( e.key === 'Escape' ) {
											onCancelEditingTitle();
										}
									} }
									autoFocus
									style={ { height: '2rem', flex: 1 } }
								/>
								<Button variant="ghost" size="sm" onClick={ onSaveTitle }>
									<Check style={ { height: '1rem', width: '1rem' } } />
								</Button>
								<Button variant="ghost" size="sm" onClick={ onCancelEditingTitle }>
									<X style={ { height: '1rem', width: '1rem' } } />
								</Button>
							</div>
						) : (
							<button
								type="button"
								onClick={ () => onStartEditingTitle( step ) }
								style={ {
									background: 'none',
									border: 'none',
									cursor: 'pointer',
									display: 'flex',
									alignItems: 'center',
									gap: '0.5rem',
									padding: '0.25rem',
									borderRadius: '0.25rem',
								} }
								className="hover:bg-gray-100"
							>
								<span style={ { fontWeight: 600, fontSize: '1rem' } }>
									{ step.title }
								</span>
								<Edit2 style={ { height: '0.875rem', width: '0.875rem', color: '#9ca3af' } } />
							</button>
						) }
					</div>

					<div style={ { display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
						{ /* Field count */ }
						<Badge variant="secondary" style={ { marginRight: '0.5rem' } }>
							{ visibleFields.length } { __( 'Felder', 'recruiting-playbook' ) }
						</Badge>

						{ /* Delete button (only for deletable steps) */ }
						{ step.deletable && (
							<Button
								variant="ghost"
								size="sm"
								onClick={ () => {
									if ( window.confirm( __( 'Schritt löschen?', 'recruiting-playbook' ) ) ) {
										onRemove( step.id );
									}
								} }
								style={ { color: '#ef4444' } }
							>
								<Trash2 style={ { height: '1rem', width: '1rem' } } />
							</Button>
						) }

						{ /* Expand/collapse button */ }
						<Button
							variant="ghost"
							size="sm"
							onClick={ () => onToggleExpanded( step.id ) }
						>
							{ isExpanded ? (
								<ChevronUp style={ { height: '1rem', width: '1rem' } } />
							) : (
								<ChevronDown style={ { height: '1rem', width: '1rem' } } />
							) }
						</Button>
					</div>
				</div>
			</CardHeader>

			{ /* Step content (fields) */ }
			{ isExpanded && (
				<CardContent style={ { padding: '0.75rem 1rem', paddingTop: 0, borderTop: '1px solid #e5e7eb' } }>
					{ /* Fields list with DnD */ }
					<DndContext
						sensors={ sensors }
						collisionDetection={ closestCenter }
						onDragEnd={ ( event ) => onFieldDragEnd( event, step.id ) }
					>
						<SortableContext
							items={ fieldIds }
							strategy={ verticalListSortingStrategy }
						>
							<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem', marginTop: '0.75rem' } }>
								{ visibleFields.length === 0 ? (
									<div style={ { textAlign: 'center', padding: '1rem', color: '#9ca3af', backgroundColor: '#f9fafb', borderRadius: '0.5rem', border: '2px dashed #e5e7eb' } }>
										<p style={ { margin: 0, fontSize: '0.875rem' } }>
											{ __( 'Keine Felder in diesem Schritt', 'recruiting-playbook' ) }
										</p>
									</div>
								) : (
									visibleFields.map( ( fieldConfig ) => (
										<SortableFieldItem
											key={ fieldConfig.field_key }
											fieldConfig={ fieldConfig }
											stepId={ step.id }
											fieldDef={ getFieldDefinition ? getFieldDefinition( fieldConfig.field_key ) : null }
											onToggleRequired={ onToggleRequired }
											onRemove={ onRemoveField }
										/>
									) )
								) }
							</div>
						</SortableContext>
					</DndContext>

					{ /* Add field button */ }
					<div style={ { marginTop: '0.75rem' } }>
						{ showAddFieldFor === step.id ? (
							<div style={ { position: 'relative' } }>
								<div style={ { display: 'flex', flexWrap: 'wrap', gap: '0.5rem', padding: '0.75rem', backgroundColor: '#f3f4f6', borderRadius: '0.5rem' } }>
									{ unusedFields.length === 0 ? (
										<span style={ { color: '#6b7280', fontSize: '0.875rem' } }>
											{ __( 'Alle Felder sind bereits verwendet', 'recruiting-playbook' ) }
										</span>
									) : (
										unusedFields.map( ( field ) => (
											<Button
												key={ field.field_key }
												variant="outline"
												size="sm"
												onClick={ () => onAddField( step.id, field.field_key ) }
											>
												<Plus style={ { height: '0.875rem', width: '0.875rem', marginRight: '0.25rem' } } />
												{ field.label }
											</Button>
										) )
									) }
								</div>
								<Button
									variant="ghost"
									size="sm"
									onClick={ () => setShowAddFieldFor( null ) }
									style={ { position: 'absolute', top: '0.25rem', right: '0.25rem' } }
								>
									<X style={ { height: '1rem', width: '1rem' } } />
								</Button>
							</div>
						) : (
							<Button
								variant="outline"
								size="sm"
								onClick={ () => setShowAddFieldFor( step.id ) }
								style={ { width: '100%' } }
							>
								<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.5rem' } } />
								{ __( 'Feld hinzufügen', 'recruiting-playbook' ) }
							</Button>
						) }
					</div>
				</CardContent>
			) }
		</Card>
	);
}

/**
 * FormEditor component
 *
 * @param {Object}   props                       Component props
 * @param {Array}    props.steps                 All steps
 * @param {Array}    props.regularSteps          Regular steps (non-finale)
 * @param {Object}   props.finaleStep            Finale step
 * @param {Array}    props.availableFields       Available field definitions
 * @param {Function} props.addStep               Add new step handler
 * @param {Function} props.updateStep            Update step handler
 * @param {Function} props.removeStep            Remove step handler
 * @param {Function} props.reorderSteps          Reorder steps handler
 * @param {Function} props.addFieldToStep        Add field to step handler
 * @param {Function} props.removeFieldFromStep   Remove field from step handler
 * @param {Function} props.updateFieldInStep     Update field in step handler
 * @param {Function} props.moveFieldBetweenSteps Move field between steps handler
 * @param {Function} props.reorderFieldsInStep   Reorder fields in step handler
 * @param {Function} props.getUnusedFields       Get unused fields handler
 * @param {Function} props.getFieldDefinition    Get field definition handler
 * @param {Object}   props.i18n                  Translations
 */
export default function FormEditor( {
	steps = [],
	regularSteps = [],
	finaleStep = null,
	availableFields = [],
	addStep,
	updateStep,
	removeStep,
	reorderSteps,
	addFieldToStep,
	removeFieldFromStep,
	updateFieldInStep,
	moveFieldBetweenSteps,
	reorderFieldsInStep,
	getUnusedFields,
	getFieldDefinition,
	i18n = {},
} ) {
	const [ expandedSteps, setExpandedSteps ] = useState( {} );
	const [ editingStepId, setEditingStepId ] = useState( null );
	const [ editingTitle, setEditingTitle ] = useState( '' );
	const [ showAddFieldFor, setShowAddFieldFor ] = useState( null );
	const [ activeStepId, setActiveStepId ] = useState( null );

	// Sensors for drag and drop
	const sensors = useSensors(
		useSensor( PointerSensor, {
			activationConstraint: {
				distance: 8,
			},
		} ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	// Toggle step expansion
	const toggleStepExpanded = ( stepId ) => {
		setExpandedSteps( ( prev ) => ( {
			...prev,
			[ stepId ]: ! prev[ stepId ],
		} ) );
	};

	// Start editing step title
	const startEditingTitle = ( step ) => {
		setEditingStepId( step.id );
		setEditingTitle( step.title );
	};

	// Save step title
	const saveStepTitle = () => {
		if ( editingStepId && editingTitle.trim() ) {
			updateStep( editingStepId, { title: editingTitle.trim() } );
		}
		setEditingStepId( null );
		setEditingTitle( '' );
	};

	// Cancel editing
	const cancelEditingTitle = () => {
		setEditingStepId( null );
		setEditingTitle( '' );
	};

	// Handle add step
	const handleAddStep = () => {
		const newStepId = addStep( {
			title: __( 'Neuer Schritt', 'recruiting-playbook' ),
		} );
		if ( newStepId ) {
			setExpandedSteps( ( prev ) => ( { ...prev, [ newStepId ]: true } ) );
		}
	};

	// Handle add field to step
	const handleAddField = ( stepId, fieldKey ) => {
		addFieldToStep( stepId, fieldKey, { is_visible: true, is_required: false } );
		setShowAddFieldFor( null );
	};

	// Handle remove field from step
	const handleRemoveField = ( stepId, fieldKey ) => {
		removeFieldFromStep( stepId, fieldKey );
	};

	// Handle toggle field required
	const handleToggleRequired = ( stepId, fieldKey, currentValue ) => {
		updateFieldInStep( stepId, fieldKey, { is_required: ! currentValue } );
	};

	// Handle step drag end
	const handleStepDragEnd = ( event ) => {
		const { active, over } = event;

		if ( ! over || active.id === over.id ) {
			setActiveStepId( null );
			return;
		}

		const oldIndex = regularSteps.findIndex( ( s ) => s.id === active.id );
		const newIndex = regularSteps.findIndex( ( s ) => s.id === over.id );

		if ( oldIndex !== -1 && newIndex !== -1 ) {
			const newOrder = arrayMove( regularSteps, oldIndex, newIndex );
			const orderedIds = newOrder.map( ( s ) => s.id );
			reorderSteps( orderedIds );
		}

		setActiveStepId( null );
	};

	// Handle field drag end within a step
	const handleFieldDragEnd = ( event, stepId ) => {
		const { active, over } = event;

		if ( ! over || active.id === over.id ) {
			return;
		}

		// Parse the field IDs (format: "stepId:fieldKey")
		const [ , activeFieldKey ] = active.id.split( ':' );
		const [ , overFieldKey ] = over.id.split( ':' );

		const step = steps.find( ( s ) => s.id === stepId );
		if ( ! step ) return;

		const visibleFields = ( step.fields || [] ).filter( ( f ) => f.is_visible );
		const oldIndex = visibleFields.findIndex( ( f ) => f.field_key === activeFieldKey );
		const newIndex = visibleFields.findIndex( ( f ) => f.field_key === overFieldKey );

		if ( oldIndex !== -1 && newIndex !== -1 ) {
			const newOrder = arrayMove( visibleFields, oldIndex, newIndex );
			const orderedKeys = newOrder.map( ( f ) => f.field_key );
			reorderFieldsInStep( stepId, orderedKeys );
		}
	};

	// Get unused fields for the add field dropdown
	const unusedFields = getUnusedFields ? getUnusedFields() : [];

	// Step IDs for sortable context (only regular steps, not finale)
	const stepIds = regularSteps.map( ( s ) => s.id );

	return (
		<div className="rp-form-editor">
			{ /* Regular steps with DnD */ }
			<DndContext
				sensors={ sensors }
				collisionDetection={ closestCenter }
				onDragStart={ ( event ) => setActiveStepId( event.active.id ) }
				onDragEnd={ handleStepDragEnd }
			>
				<SortableContext
					items={ stepIds }
					strategy={ verticalListSortingStrategy }
				>
					<div className="rp-form-editor__steps">
						{ regularSteps.map( ( step, index ) => (
							<SortableStep
								key={ step.id }
								step={ step }
								index={ index }
								isFinale={ false }
								isExpanded={ expandedSteps[ step.id ] ?? true }
								isEditingTitle={ editingStepId === step.id }
								editingTitle={ editingTitle }
								setEditingTitle={ setEditingTitle }
								onToggleExpanded={ toggleStepExpanded }
								onStartEditingTitle={ startEditingTitle }
								onSaveTitle={ saveStepTitle }
								onCancelEditingTitle={ cancelEditingTitle }
								onRemove={ removeStep }
								onToggleRequired={ handleToggleRequired }
								onRemoveField={ handleRemoveField }
								onAddField={ handleAddField }
								showAddFieldFor={ showAddFieldFor }
								setShowAddFieldFor={ setShowAddFieldFor }
								unusedFields={ unusedFields }
								getFieldDefinition={ getFieldDefinition }
								sensors={ sensors }
								onFieldDragEnd={ handleFieldDragEnd }
							/>
						) ) }
					</div>
				</SortableContext>

				{ /* Drag overlay for steps */ }
				<DragOverlay>
					{ activeStepId ? (
						<Card style={ { opacity: 0.8, boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)' } }>
							<CardHeader style={ { padding: '0.75rem 1rem' } }>
								<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
									<GripVertical style={ { height: '1.25rem', width: '1.25rem', color: '#9ca3af' } } />
									<Badge variant="outline">
										{ regularSteps.findIndex( ( s ) => s.id === activeStepId ) + 1 }
									</Badge>
									<span style={ { fontWeight: 600 } }>
										{ regularSteps.find( ( s ) => s.id === activeStepId )?.title }
									</span>
								</div>
							</CardHeader>
						</Card>
					) : null }
				</DragOverlay>
			</DndContext>

			{ /* Add step button */ }
			<div style={ { marginBottom: '1rem' } }>
				<Button
					variant="outline"
					onClick={ handleAddStep }
					style={ { width: '100%', borderStyle: 'dashed' } }
				>
					<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.5rem' } } />
					{ __( 'Schritt hinzufügen', 'recruiting-playbook' ) }
				</Button>
			</div>

			{ /* Finale step (not draggable) */ }
			{ finaleStep && (
				<div className="rp-form-editor__finale">
					<SortableStep
						step={ finaleStep }
						index={ regularSteps.length }
						isFinale={ true }
						isExpanded={ expandedSteps[ finaleStep.id ] ?? true }
						isEditingTitle={ editingStepId === finaleStep.id }
						editingTitle={ editingTitle }
						setEditingTitle={ setEditingTitle }
						onToggleExpanded={ toggleStepExpanded }
						onStartEditingTitle={ startEditingTitle }
						onSaveTitle={ saveStepTitle }
						onCancelEditingTitle={ cancelEditingTitle }
						onRemove={ removeStep }
						onToggleRequired={ handleToggleRequired }
						onRemoveField={ handleRemoveField }
						onAddField={ handleAddField }
						showAddFieldFor={ showAddFieldFor }
						setShowAddFieldFor={ setShowAddFieldFor }
						unusedFields={ unusedFields }
						getFieldDefinition={ getFieldDefinition }
						sensors={ sensors }
						onFieldDragEnd={ handleFieldDragEnd }
					/>
				</div>
			) }

			{ /* Help text */ }
			<div style={ { marginTop: '1rem', padding: '1rem', backgroundColor: '#f0f9ff', borderRadius: '0.5rem', border: '1px solid #bae6fd' } }>
				<p style={ { margin: 0, fontSize: '0.875rem', color: '#0369a1' } }>
					<strong>{ __( 'Tipps:', 'recruiting-playbook' ) }</strong>
					{ ' ' }
					{ __( 'Klicken Sie auf den Titel, um ihn zu bearbeiten. Ziehen Sie Schritte und Felder per Drag & Drop, um sie neu anzuordnen. Der Finale-Schritt ist immer der letzte und enthält typischerweise die Datenschutz-Zustimmung.', 'recruiting-playbook' ) }
				</p>
			</div>
		</div>
	);
}

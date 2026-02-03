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
	Lock,
	FileText,
	Upload,
	ListChecks,
	Shield,
	Settings,
	RotateCcw,
	Type,
	AlignLeft,
	Mail,
	Phone,
	Hash,
	List,
	Circle,
	CheckSquare,
	Calendar,
	Link,
	Heading,
	Square,
	Columns2,
} from 'lucide-react';
import { Input } from '../../components/ui/input';
import FileUploadSettings from './SystemFieldSettings/FileUploadSettings';
import SummarySettings from './SystemFieldSettings/SummarySettings';
import PrivacyConsentSettings from './SystemFieldSettings/PrivacyConsentSettings';

/**
 * Icon mapping for field types
 */
const fieldTypeIcons = {
	text: Type,
	textarea: AlignLeft,
	email: Mail,
	phone: Phone,
	number: Hash,
	select: List,
	radio: Circle,
	checkbox: CheckSquare,
	date: Calendar,
	file: Upload,
	url: Link,
	heading: Heading,
};

/**
 * Label mapping for field types
 */
const fieldTypeLabels = {
	text: __( 'Text', 'recruiting-playbook' ),
	textarea: __( 'Textbereich', 'recruiting-playbook' ),
	email: __( 'E-Mail', 'recruiting-playbook' ),
	phone: __( 'Telefon', 'recruiting-playbook' ),
	number: __( 'Zahl', 'recruiting-playbook' ),
	select: __( 'Auswahl', 'recruiting-playbook' ),
	radio: __( 'Radio', 'recruiting-playbook' ),
	checkbox: __( 'Checkbox', 'recruiting-playbook' ),
	date: __( 'Datum', 'recruiting-playbook' ),
	file: __( 'Datei', 'recruiting-playbook' ),
	url: __( 'URL', 'recruiting-playbook' ),
	heading: __( 'Überschrift', 'recruiting-playbook' ),
};

/**
 * SortableFieldItem - Draggable field within a step
 *
 * Shows Lock icon for non-removable fields (required fields like first_name, last_name, email)
 * and Delete button only for removable fields.
 */
function SortableFieldItem( {
	fieldConfig,
	stepId,
	fieldDef,
	onRemove,
	onEditField,
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

	// Check if field is removable (default to true for backwards compatibility)
	const isRemovable = fieldConfig.is_removable !== false;

	// Check if field is a custom field (not a system field)
	const isCustomField = fieldDef && ! fieldDef.is_system;

	// Get field type icon (API returns field_type, not type)
	const fieldType = fieldDef?.field_type || 'text';
	const FieldTypeIcon = fieldTypeIcons[ fieldType ] || Type;

	return (
		<div
			ref={ setNodeRef }
			style={ {
				...style,
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				padding: '0.5rem 0.75rem',
				backgroundColor: isDragging ? '#dbeafe' : ( isRemovable ? '#f9fafb' : '#fef3c7' ),
				borderRadius: '0.375rem',
				border: isDragging ? '1px solid #3b82f6' : ( isRemovable ? '1px solid #e5e7eb' : '1px solid #fcd34d' ),
			} }
		>
			{ /* Left side: Drag handle, Label, Required/Optional badge */ }
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
				<button
					type="button"
					aria-label={ __( 'Feld per Drag & Drop verschieben', 'recruiting-playbook' ) }
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

				{ /* Required/Optional badge */ }
				{ fieldConfig.is_required ? (
					<Badge style={ { backgroundColor: '#ef4444', color: 'white', fontSize: '0.625rem', padding: '0.125rem 0.375rem', lineHeight: 1 } }>
						{ __( 'Pflicht', 'recruiting-playbook' ) }
					</Badge>
				) : (
					<Badge variant="outline" style={ { backgroundColor: '#f3f4f6', color: '#6b7280', fontSize: '0.625rem', padding: '0.125rem 0.375rem', lineHeight: 1, borderColor: '#e5e7eb' } }>
						{ __( 'Optional', 'recruiting-playbook' ) }
					</Badge>
				) }
			</div>

			{ /* Right side: Type badge, Width badge, Action buttons */ }
			<div style={ { display: 'flex', alignItems: 'center', gap: '0' } }>
				{ /* Group 1: Badges (fixed width, left-aligned) */ }
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						gap: '0.5rem',
						width: '13rem',
						justifyContent: 'flex-start',
					} }
				>
					{ /* Field type badge with icon + text */ }
					<span
						style={ {
							display: 'inline-flex',
							alignItems: 'center',
							gap: '0.375rem',
							padding: '0.125rem 0.5rem',
							backgroundColor: 'white',
							border: '1px solid #e4e4e7',
							borderRadius: '9999px',
							fontSize: '0.75rem',
							fontWeight: 500,
							color: '#71717a',
							whiteSpace: 'nowrap',
						} }
					>
						<FieldTypeIcon style={ { height: '0.75rem', width: '0.75rem' } } />
						{ fieldTypeLabels[ fieldType ] || fieldType }
					</span>

					{ /* Width badge with icon + text */ }
					<span
						style={ {
							display: 'inline-flex',
							alignItems: 'center',
							gap: '0.375rem',
							padding: '0.125rem 0.5rem',
							backgroundColor: 'white',
							border: '1px solid #e4e4e7',
							borderRadius: '9999px',
							fontSize: '0.75rem',
							fontWeight: 500,
							color: '#71717a',
							whiteSpace: 'nowrap',
						} }
					>
						{ fieldConfig.width === 'half' ? (
							<>
								<Columns2 style={ { height: '0.75rem', width: '0.75rem' } } />
								{ __( 'Halbe Spalte', 'recruiting-playbook' ) }
							</>
						) : (
							<>
								<Square style={ { height: '0.75rem', width: '0.75rem' } } />
								{ __( 'Volle Spalte', 'recruiting-playbook' ) }
							</>
						) }
					</span>
				</div>

				{ /* Group 2: Action buttons (Settings, Delete/Lock) - fixed width for alignment */ }
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'flex-end',
						gap: '0.25rem',
						marginLeft: '0.75rem',
						width: '3.5rem',
					} }
				>
					{ /* Settings button only for custom fields */ }
					{ isCustomField && onEditField && (
						<Button
							variant="ghost"
							size="sm"
							onClick={ () => onEditField( fieldConfig.field_key ) }
							title={ __( 'Feld bearbeiten', 'recruiting-playbook' ) }
							style={ { padding: '0.25rem', height: 'auto', minHeight: 'auto' } }
						>
							<Settings style={ { height: '1rem', width: '1rem' } } />
						</Button>
					) }

					{ /* Delete button or Lock icon */ }
					{ isRemovable ? (
						<button
							type="button"
							onClick={ () => onRemove( stepId, fieldConfig.field_key ) }
							style={ {
								background: 'none',
								border: 'none',
								padding: '0.25rem',
								cursor: 'pointer',
								color: '#374151',
								display: 'flex',
								alignItems: 'center',
								borderRadius: '0.25rem',
							} }
							className="hover:bg-gray-100"
							title={ __( 'Feld entfernen', 'recruiting-playbook' ) }
						>
							<X style={ { height: '1rem', width: '1rem' } } />
						</button>
					) : (
						<div
							style={ {
								padding: '0.25rem',
								color: '#d97706',
								display: 'flex',
								alignItems: 'center',
							} }
							title={ __( 'Pflichtfeld - kann nicht entfernt werden', 'recruiting-playbook' ) }
						>
							<Lock style={ { height: '1rem', width: '1rem' } } />
						</div>
					) }
				</div>
			</div>
		</div>
	);
}

/**
 * Icon mapping for system field types
 */
const systemFieldIcons = {
	file_upload: Upload,
	summary: ListChecks,
	privacy_consent: Shield,
};

/**
 * Label mapping for system field types
 */
const systemFieldLabels = {
	file_upload: __( 'Datei-Upload', 'recruiting-playbook' ),
	summary: __( 'Zusammenfassung', 'recruiting-playbook' ),
	privacy_consent: __( 'Datenschutz-Zustimmung', 'recruiting-playbook' ),
};

/**
 * SystemFieldCard - Non-draggable system field within a step
 *
 * System fields are hardcoded (file_upload, summary, privacy_consent) and cannot be
 * moved or deleted. They only have a settings button.
 */
function SystemFieldCard( {
	systemField,
	stepId,
	onOpenSettings,
} ) {
	const IconComponent = systemFieldIcons[ systemField.field_key ] || FileText;
	const label = systemField.settings?.label || systemFieldLabels[ systemField.field_key ] || systemField.field_key;

	return (
		<div
			style={ {
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				padding: '0.5rem 0.75rem',
				backgroundColor: '#f0fdf4',
				borderRadius: '0.375rem',
				border: '1px solid #86efac',
			} }
		>
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
				{ /* Static icon instead of drag handle */ }
				<div
					style={ {
						color: '#22c55e',
						padding: '0.25rem',
						display: 'flex',
						alignItems: 'center',
					} }
				>
					<IconComponent style={ { height: '1rem', width: '1rem' } } />
				</div>

				<span style={ { fontWeight: 500 } }>{ label }</span>

				<Badge
					variant="outline"
					style={ { fontSize: '0.75rem', backgroundColor: '#dcfce7', borderColor: '#86efac', color: '#166534' } }
				>
					{ __( 'System', 'recruiting-playbook' ) }
				</Badge>
			</div>

			<div style={ { display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
				{ /* Settings button */ }
				<Button
					variant="ghost"
					size="sm"
					onClick={ () => onOpenSettings && onOpenSettings( stepId, systemField ) }
					title={ __( 'Einstellungen', 'recruiting-playbook' ) }
				>
					<Settings style={ { height: '1rem', width: '1rem' } } />
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
	onRemoveField,
	onEditField,
	onCreateField,
	getFieldDefinition,
	onOpenSystemFieldSettings,
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
	const systemFields = step.system_fields || [];
	const totalFieldCount = visibleFields.length + systemFields.length;

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
								aria-label={ __( 'Schritt per Drag & Drop verschieben', 'recruiting-playbook' ) }
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
						{ /* Field count (including system fields) */ }
						<Badge variant="secondary" style={ { marginRight: '0.5rem' } }>
							{ totalFieldCount } { __( 'Felder', 'recruiting-playbook' ) }
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
											onRemove={ onRemoveField }
											onEditField={ onEditField }
										/>
									) )
								) }
							</div>
						</SortableContext>
					</DndContext>

					{ /* System fields (non-draggable) */ }
					{ systemFields.length > 0 && (
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem', marginTop: '0.75rem' } }>
							<div style={ { fontSize: '0.75rem', fontWeight: 600, color: '#6b7280', textTransform: 'uppercase', marginBottom: '0.25rem' } }>
								{ __( 'System-Felder', 'recruiting-playbook' ) }
							</div>
							{ systemFields.map( ( systemField ) => (
								<SystemFieldCard
									key={ systemField.field_key }
									systemField={ systemField }
									stepId={ step.id }
									onOpenSettings={ onOpenSystemFieldSettings }
								/>
							) ) }
						</div>
					) }

					{ /* Add field button */ }
					{ onCreateField && (
						<div style={ { marginTop: '0.75rem' } }>
							<Button
								variant="outline"
								size="sm"
								onClick={ () => onCreateField( step.id ) }
								style={ { width: '100%' } }
							>
								<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.5rem' } } />
								{ __( 'Neues Feld erstellen', 'recruiting-playbook' ) }
							</Button>
						</div>
					) }
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
 * @param {Function} props.removeFieldFromStep     Remove field from step handler
 * @param {Function} props.updateFieldInStep       Update field in step handler
 * @param {Function} props.updateSystemFieldInStep Update system field in step handler
 * @param {Function} props.moveFieldBetweenSteps   Move field between steps handler
 * @param {Function} props.reorderFieldsInStep     Reorder fields in step handler
 * @param {Function} props.getFieldDefinition      Get field definition handler
 * @param {Function} props.onResetToDefault        Reset to default handler
 * @param {Object}   props.i18n                    Translations
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
	updateSystemFieldInStep,
	moveFieldBetweenSteps,
	reorderFieldsInStep,
	getFieldDefinition,
	onResetToDefault,
	onEditField,
	onCreateField,
	i18n = {},
} ) {
	const [ expandedSteps, setExpandedSteps ] = useState( {} );
	const [ editingStepId, setEditingStepId ] = useState( null );
	const [ editingTitle, setEditingTitle ] = useState( '' );
	const [ activeStepId, setActiveStepId ] = useState( null );
	// System field editing state: { stepId, systemField }
	const [ editingSystemField, setEditingSystemField ] = useState( null );

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
		if ( editingStepId && editingTitle.trim() && updateStep ) {
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
		if ( ! addStep ) {
			return;
		}
		const newStepId = addStep( {
			title: __( 'Neuer Schritt', 'recruiting-playbook' ),
		} );
		if ( newStepId ) {
			setExpandedSteps( ( prev ) => ( { ...prev, [ newStepId ]: true } ) );
		}
	};

	// Handle remove field from step
	const handleRemoveField = ( stepId, fieldKey ) => {
		if ( ! removeFieldFromStep ) {
			return;
		}
		removeFieldFromStep( stepId, fieldKey );
	};

	// Handle open system field settings
	const handleOpenSystemFieldSettings = ( stepId, systemField ) => {
		setEditingSystemField( { stepId, systemField } );
	};

	// Handle save system field settings
	const handleSaveSystemFieldSettings = ( newSettings ) => {
		if ( editingSystemField && updateSystemFieldInStep ) {
			updateSystemFieldInStep(
				editingSystemField.stepId,
				editingSystemField.systemField.field_key,
				newSettings
			);
		}
		setEditingSystemField( null );
	};

	// Handle close system field settings
	const handleCloseSystemFieldSettings = () => {
		setEditingSystemField( null );
	};

	// Handle step drag end
	const handleStepDragEnd = ( event ) => {
		const { active, over } = event;

		if ( ! over || active.id === over.id ) {
			setActiveStepId( null );
			return;
		}

		if ( ! reorderSteps ) {
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

		if ( ! reorderFieldsInStep ) {
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
								onRemoveField={ handleRemoveField }
								onEditField={ onEditField }
								onCreateField={ onCreateField }
								getFieldDefinition={ getFieldDefinition }
								onOpenSystemFieldSettings={ handleOpenSystemFieldSettings }
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
						onRemoveField={ handleRemoveField }
						onEditField={ onEditField }
						onCreateField={ onCreateField }
						getFieldDefinition={ getFieldDefinition }
						onOpenSystemFieldSettings={ handleOpenSystemFieldSettings }
						sensors={ sensors }
						onFieldDragEnd={ handleFieldDragEnd }
					/>
				</div>
			) }

			{ /* Reset to Default Button */ }
			{ onResetToDefault && (
				<div style={ { marginTop: '1.5rem' } }>
					<Button
						variant="outline"
						onClick={ onResetToDefault }
						style={ { color: '#6b7280' } }
					>
						<RotateCcw style={ { height: '1rem', width: '1rem', marginRight: '0.5rem' } } />
						{ __( 'Formular zurücksetzen', 'recruiting-playbook' ) }
					</Button>
				</div>
			) }

			{ /* System Field Settings Modals */ }
			{ editingSystemField?.systemField?.field_key === 'file_upload' && (
				<FileUploadSettings
					settings={ editingSystemField.systemField.settings || {} }
					onSave={ handleSaveSystemFieldSettings }
					onClose={ handleCloseSystemFieldSettings }
				/>
			) }
			{ editingSystemField?.systemField?.field_key === 'summary' && (
				<SummarySettings
					settings={ editingSystemField.systemField.settings || {} }
					onSave={ handleSaveSystemFieldSettings }
					onClose={ handleCloseSystemFieldSettings }
				/>
			) }
			{ editingSystemField?.systemField?.field_key === 'privacy_consent' && (
				<PrivacyConsentSettings
					settings={ editingSystemField.systemField.settings || {} }
					onSave={ handleSaveSystemFieldSettings }
					onClose={ handleCloseSystemFieldSettings }
				/>
			) }
		</div>
	);
}

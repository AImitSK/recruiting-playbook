/**
 * FieldList Component
 *
 * Displays the list of form fields with drag & drop reordering.
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
} from '@dnd-kit/core';
import {
	arrayMove,
	SortableContext,
	sortableKeyboardCoordinates,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Spinner } from '../../components/ui/spinner';
import { Plus, Lock } from 'lucide-react';
import FieldListItem from './FieldListItem';

/**
 * FieldList component
 *
 * @param {Object} props Component props
 * @param {Array}  props.systemFields    System field definitions
 * @param {Array}  props.customFields    Custom field definitions
 * @param {number} props.selectedFieldId Selected field ID
 * @param {Function} props.onFieldSelect   Field selection handler
 * @param {Function} props.onFieldReorder  Field reorder handler
 * @param {Function} props.onAddField      Add field handler
 * @param {boolean} props.isLoading       Loading state
 * @param {boolean} props.isPro           Pro feature access
 * @param {Object} props.i18n             Translations
 */
export default function FieldList( {
	systemFields = [],
	customFields = [],
	selectedFieldId,
	onFieldSelect,
	onFieldReorder,
	onAddField,
	isLoading,
	isPro,
	i18n,
} ) {
	const [ isSaving, setIsSaving ] = useState( false );

	const sensors = useSensors(
		useSensor( PointerSensor ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	// Handle drag end
	const handleDragEnd = async ( event ) => {
		const { active, over } = event;

		if ( active.id !== over?.id ) {
			// Combine all fields for reordering
			const allFields = [ ...systemFields, ...customFields ];
			const oldIndex = allFields.findIndex( ( f ) => f.id === active.id );
			const newIndex = allFields.findIndex( ( f ) => f.id === over.id );

			if ( oldIndex !== -1 && newIndex !== -1 ) {
				const newOrder = arrayMove( allFields, oldIndex, newIndex );
				const orderedIds = newOrder.map( ( f ) => f.id );

				setIsSaving( true );
				await onFieldReorder( orderedIds );
				setIsSaving( false );
			}
		}
	};

	// Get all field IDs for sortable context
	const allFieldIds = [ ...systemFields, ...customFields ].map( ( f ) => f.id );

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
		<div className="rp-field-list" style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
			{ /* System Fields */ }
			<Card>
				<CardHeader style={ { paddingBottom: '0.75rem' } }>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
						<CardTitle style={ { fontSize: '1.125rem' } }>
							{ i18n?.systemFields || __( 'System-Felder', 'recruiting-playbook' ) }
						</CardTitle>
						<span style={ { fontSize: '0.75rem', color: '#6b7280', backgroundColor: '#f3f4f6', padding: '0.25rem 0.5rem', borderRadius: '0.25rem' } }>
							{ systemFields.length } { __( 'Felder', 'recruiting-playbook' ) }
						</span>
					</div>
				</CardHeader>
				<CardContent style={ { paddingTop: 0 } }>
					{ systemFields.length === 0 ? (
						<p style={ { color: '#6b7280', fontSize: '0.875rem', padding: '1rem 0', textAlign: 'center', margin: 0 } }>
							{ i18n?.noSystemFields || __( 'Keine System-Felder vorhanden', 'recruiting-playbook' ) }
						</p>
					) : (
						<DndContext
							sensors={ sensors }
							collisionDetection={ closestCenter }
							onDragEnd={ handleDragEnd }
						>
							<SortableContext
								items={ allFieldIds }
								strategy={ verticalListSortingStrategy }
							>
								<ul style={ { listStyle: 'none', margin: 0, padding: 0, display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									{ systemFields.map( ( field ) => (
										<FieldListItem
											key={ field.id }
											field={ field }
											isSelected={ selectedFieldId === field.id }
											onSelect={ () => onFieldSelect( field ) }
											isSystem={ true }
											i18n={ i18n }
										/>
									) ) }
								</ul>
							</SortableContext>
						</DndContext>
					) }
				</CardContent>
			</Card>

			{ /* Custom Fields */ }
			<Card>
				<CardHeader style={ { paddingBottom: '0.75rem' } }>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
						<CardTitle style={ { fontSize: '1.125rem', display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
							{ i18n?.customFields || __( 'Eigene Felder', 'recruiting-playbook' ) }
							{ ! isPro && <Lock style={ { height: '1rem', width: '1rem', color: '#9ca3af' } } /> }
						</CardTitle>
						<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
							<span style={ { fontSize: '0.75rem', color: '#6b7280', backgroundColor: '#f3f4f6', padding: '0.25rem 0.5rem', borderRadius: '0.25rem' } }>
								{ customFields.length } { __( 'Felder', 'recruiting-playbook' ) }
							</span>
							{ isPro && (
								<Button
									size="sm"
									onClick={ onAddField }
									disabled={ isSaving }
								>
									<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } />
									{ i18n?.addField || __( 'Feld hinzufügen', 'recruiting-playbook' ) }
								</Button>
							) }
						</div>
					</div>
				</CardHeader>
				<CardContent style={ { paddingTop: 0 } }>
					{ ! isPro ? (
						<div style={ { textAlign: 'center', padding: '2rem 0', color: '#6b7280' } }>
							<Lock style={ { height: '2rem', width: '2rem', margin: '0 auto 0.5rem', color: '#9ca3af' } } />
							<p style={ { fontSize: '0.875rem', margin: 0 } }>
								{ i18n?.customFieldsPro || __( 'Custom Fields sind ein Pro-Feature', 'recruiting-playbook' ) }
							</p>
						</div>
					) : customFields.length === 0 ? (
						<div style={ { textAlign: 'center', padding: '2rem 0' } }>
							<p style={ { color: '#6b7280', fontSize: '0.875rem', marginBottom: '1rem' } }>
								{ i18n?.noCustomFields || __( 'Noch keine eigenen Felder erstellt', 'recruiting-playbook' ) }
							</p>
							<Button variant="outline" onClick={ onAddField }>
								<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } />
								{ i18n?.addFirstField || __( 'Erstes Feld erstellen', 'recruiting-playbook' ) }
							</Button>
						</div>
					) : (
						<DndContext
							sensors={ sensors }
							collisionDetection={ closestCenter }
							onDragEnd={ handleDragEnd }
						>
							<SortableContext
								items={ allFieldIds }
								strategy={ verticalListSortingStrategy }
							>
								<ul style={ { listStyle: 'none', margin: 0, padding: 0, display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									{ customFields.map( ( field ) => (
										<FieldListItem
											key={ field.id }
											field={ field }
											isSelected={ selectedFieldId === field.id }
											onSelect={ () => onFieldSelect( field ) }
											isSystem={ false }
											i18n={ i18n }
										/>
									) ) }
								</ul>
							</SortableContext>
						</DndContext>
					) }
				</CardContent>
			</Card>

			{ isSaving && (
				<div style={ { position: 'fixed', bottom: '1rem', right: '1rem', backgroundColor: '#fff', boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)', borderRadius: '0.5rem', padding: '0.5rem 1rem', display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
					<Spinner size="small" />
					<span style={ { fontSize: '0.875rem' } }>
						{ i18n?.saving || __( 'Speichern...', 'recruiting-playbook' ) }
					</span>
				</div>
			) }
		</div>
	);
}

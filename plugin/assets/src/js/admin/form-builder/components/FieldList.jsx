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
				<CardContent className="flex items-center justify-center py-12">
					<Spinner />
				</CardContent>
			</Card>
		);
	}

	return (
		<div className="rp-field-list space-y-6">
			{ /* System Fields */ }
			<Card>
				<CardHeader className="pb-3">
					<div className="flex items-center justify-between">
						<CardTitle className="text-lg">
							{ i18n?.systemFields || __( 'System-Felder', 'recruiting-playbook' ) }
						</CardTitle>
						<span className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
							{ systemFields.length } { __( 'Felder', 'recruiting-playbook' ) }
						</span>
					</div>
				</CardHeader>
				<CardContent className="pt-0">
					{ systemFields.length === 0 ? (
						<p className="text-gray-500 text-sm py-4 text-center">
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
								<ul className="space-y-2">
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
				<CardHeader className="pb-3">
					<div className="flex items-center justify-between">
						<CardTitle className="text-lg flex items-center gap-2">
							{ i18n?.customFields || __( 'Eigene Felder', 'recruiting-playbook' ) }
							{ ! isPro && <Lock className="h-4 w-4 text-gray-400" /> }
						</CardTitle>
						<div className="flex items-center gap-2">
							<span className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
								{ customFields.length } { __( 'Felder', 'recruiting-playbook' ) }
							</span>
							{ isPro && (
								<Button
									size="sm"
									onClick={ onAddField }
									disabled={ isSaving }
								>
									<Plus className="h-4 w-4 mr-1" />
									{ i18n?.addField || __( 'Feld hinzufügen', 'recruiting-playbook' ) }
								</Button>
							) }
						</div>
					</div>
				</CardHeader>
				<CardContent className="pt-0">
					{ ! isPro ? (
						<div className="text-center py-8 text-gray-500">
							<Lock className="h-8 w-8 mx-auto mb-2 text-gray-400" />
							<p className="text-sm">
								{ i18n?.customFieldsPro || __( 'Custom Fields sind ein Pro-Feature', 'recruiting-playbook' ) }
							</p>
						</div>
					) : customFields.length === 0 ? (
						<div className="text-center py-8">
							<p className="text-gray-500 text-sm mb-4">
								{ i18n?.noCustomFields || __( 'Noch keine eigenen Felder erstellt', 'recruiting-playbook' ) }
							</p>
							<Button variant="outline" onClick={ onAddField }>
								<Plus className="h-4 w-4 mr-1" />
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
								<ul className="space-y-2">
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
				<div className="fixed bottom-4 right-4 bg-white shadow-lg rounded-lg px-4 py-2 flex items-center gap-2">
					<Spinner size="small" />
					<span className="text-sm">
						{ i18n?.saving || __( 'Speichern...', 'recruiting-playbook' ) }
					</span>
				</div>
			) }
		</div>
	);
}

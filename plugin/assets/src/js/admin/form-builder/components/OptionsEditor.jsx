/**
 * OptionsEditor Component
 *
 * Editor for select, radio, and checkbox field options.
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
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
	useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { GripVertical, Plus, Trash2 } from 'lucide-react';

/**
 * Single option item with drag handle
 *
 * @param {Object} props Component props
 */
function OptionItem( { option, index, onChange, onRemove, i18n } ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: option.id || index } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
	};

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			className={ `
				flex items-center gap-2 p-2 bg-gray-50 rounded border border-gray-200
				${ isDragging ? 'shadow-lg z-10' : '' }
			` }
		>
			<button
				className="cursor-grab text-gray-400 hover:text-gray-600 p-1"
				{ ...attributes }
				{ ...listeners }
			>
				<GripVertical className="h-4 w-4" />
			</button>

			<Input
				value={ option.value || '' }
				onChange={ ( e ) => onChange( index, 'value', e.target.value ) }
				placeholder={ i18n?.optionValue || __( 'Wert', 'recruiting-playbook' ) }
				className="flex-1"
			/>

			<Input
				value={ option.label || '' }
				onChange={ ( e ) => onChange( index, 'label', e.target.value ) }
				placeholder={ i18n?.optionLabel || __( 'Bezeichnung', 'recruiting-playbook' ) }
				className="flex-1"
			/>

			<Button
				variant="ghost"
				size="sm"
				onClick={ () => onRemove( index ) }
				className="text-red-500 hover:text-red-700 hover:bg-red-50"
			>
				<Trash2 className="h-4 w-4" />
			</Button>
		</div>
	);
}

/**
 * OptionsEditor component
 *
 * @param {Object} props Component props
 * @param {Array}  props.options   Array of options
 * @param {Function} props.onChange  Change handler
 * @param {string} props.fieldType Field type (select, radio, checkbox)
 * @param {Object} props.i18n       Translations
 */
export default function OptionsEditor( { options = [], onChange, fieldType, i18n } ) {
	const sensors = useSensors(
		useSensor( PointerSensor ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	// Ensure options have IDs for DnD
	const optionsWithIds = options.map( ( opt, idx ) => ( {
		...opt,
		id: opt.id || `option-${ idx }`,
	} ) );

	// Add new option
	const handleAdd = useCallback( () => {
		const newOption = {
			id: `option-${ Date.now() }`,
			value: '',
			label: '',
		};
		onChange( [ ...options, newOption ] );
	}, [ options, onChange ] );

	// Remove option
	const handleRemove = useCallback(
		( index ) => {
			const newOptions = options.filter( ( _, idx ) => idx !== index );
			onChange( newOptions );
		},
		[ options, onChange ]
	);

	// Update option
	const handleOptionChange = useCallback(
		( index, key, value ) => {
			const newOptions = options.map( ( opt, idx ) =>
				idx === index ? { ...opt, [ key ]: value } : opt
			);
			onChange( newOptions );
		},
		[ options, onChange ]
	);

	// Handle drag end
	const handleDragEnd = ( event ) => {
		const { active, over } = event;

		if ( active.id !== over?.id ) {
			const oldIndex = optionsWithIds.findIndex( ( o ) => o.id === active.id );
			const newIndex = optionsWithIds.findIndex( ( o ) => o.id === over.id );

			if ( oldIndex !== -1 && newIndex !== -1 ) {
				const newOrder = arrayMove( options, oldIndex, newIndex );
				onChange( newOrder );
			}
		}
	};

	return (
		<div className="rp-options-editor space-y-3">
			<div className="flex items-center justify-between">
				<Label>{ i18n?.options || __( 'Optionen', 'recruiting-playbook' ) }</Label>
				<Button variant="outline" size="sm" onClick={ handleAdd }>
					<Plus className="h-4 w-4 mr-1" />
					{ i18n?.addOption || __( 'Option hinzufügen', 'recruiting-playbook' ) }
				</Button>
			</div>

			{ optionsWithIds.length === 0 ? (
				<div className="text-center py-4 text-gray-500 text-sm border-2 border-dashed rounded">
					{ i18n?.noOptions || __( 'Noch keine Optionen. Fügen Sie mindestens eine hinzu.', 'recruiting-playbook' ) }
				</div>
			) : (
				<DndContext
					sensors={ sensors }
					collisionDetection={ closestCenter }
					onDragEnd={ handleDragEnd }
				>
					<SortableContext
						items={ optionsWithIds.map( ( o ) => o.id ) }
						strategy={ verticalListSortingStrategy }
					>
						<div className="space-y-2">
							{ optionsWithIds.map( ( option, index ) => (
								<OptionItem
									key={ option.id }
									option={ option }
									index={ index }
									onChange={ handleOptionChange }
									onRemove={ handleRemove }
									i18n={ i18n }
								/>
							) ) }
						</div>
					</SortableContext>
				</DndContext>
			) }

			<p className="text-xs text-gray-500">
				{ i18n?.optionsHelp || __( 'Ziehen Sie Optionen zum Sortieren. Der Wert wird gespeichert, die Bezeichnung wird angezeigt.', 'recruiting-playbook' ) }
			</p>
		</div>
	);
}

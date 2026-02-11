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
			style={ {
				...style,
				display: 'flex',
				alignItems: 'center',
				gap: '0.5rem',
				padding: '0.5rem',
				backgroundColor: '#f9fafb',
				borderRadius: '0.25rem',
				border: '1px solid #e5e7eb',
				boxShadow: isDragging ? '0 10px 15px -3px rgba(0, 0, 0, 0.1)' : 'none',
				zIndex: isDragging ? 10 : 'auto',
			} }
		>
			<button
				style={ { cursor: 'grab', color: '#9ca3af', padding: '0.25rem', background: 'none', border: 'none' } }
				{ ...attributes }
				{ ...listeners }
			>
				<GripVertical style={ { height: '1rem', width: '1rem' } } />
			</button>

			<Input
				value={ option.value || '' }
				onChange={ ( e ) => onChange( index, 'value', e.target.value ) }
				placeholder={ i18n?.optionValue || __( 'Value', 'recruiting-playbook' ) }
				className="flex-1"
			/>

			<Input
				value={ option.label || '' }
				onChange={ ( e ) => onChange( index, 'label', e.target.value ) }
				placeholder={ i18n?.optionLabel || __( 'Label', 'recruiting-playbook' ) }
				className="flex-1"
			/>

			<Button
				variant="ghost"
				size="sm"
				onClick={ () => onRemove( index ) }
				style={ { color: '#ef4444' } }
			>
				<Trash2 style={ { height: '1rem', width: '1rem' } } />
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
		<div className="rp-options-editor" style={ { display: 'flex', flexDirection: 'column', gap: '0.75rem' } }>
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
				<Label>{ i18n?.options || __( 'Options', 'recruiting-playbook' ) }</Label>
				<Button variant="outline" size="sm" onClick={ handleAdd }>
					<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } />
					{ i18n?.addOption || __( 'Add option', 'recruiting-playbook' ) }
				</Button>
			</div>

			{ optionsWithIds.length === 0 ? (
				<div style={ { textAlign: 'center', padding: '1rem 0', color: '#6b7280', fontSize: '0.875rem', border: '2px dashed #e5e7eb', borderRadius: '0.25rem' } }>
					{ i18n?.noOptions || __( 'No options yet. Add at least one.', 'recruiting-playbook' ) }
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
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
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

			<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
				{ i18n?.optionsHelp || __( 'Drag options to sort. The value is saved, the label is displayed.', 'recruiting-playbook' ) }
			</p>
		</div>
	);
}

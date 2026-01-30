/**
 * FieldListItem Component
 *
 * Single field item in the field list with drag handle.
 *
 * @package RecruitingPlaybook
 */

import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { __ } from '@wordpress/i18n';
import { Badge } from '../../components/ui/badge';
import {
	GripVertical,
	Type,
	AlignLeft,
	Mail,
	Phone,
	Hash,
	List,
	Circle,
	CheckSquare,
	Calendar,
	Upload,
	Link,
	Heading,
	Lock,
	Eye,
	EyeOff,
} from 'lucide-react';

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
 * FieldListItem component
 *
 * @param {Object} props Component props
 * @param {Object} props.field      Field definition
 * @param {boolean} props.isSelected Whether field is selected
 * @param {Function} props.onSelect   Selection handler
 * @param {boolean} props.isSystem   Whether this is a system field
 * @param {Object} props.i18n       Translations
 */
export default function FieldListItem( {
	field,
	isSelected,
	onSelect,
	isSystem,
	i18n,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: field.id } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
	};

	const IconComponent = fieldTypeIcons[ field.type ] || Type;

	return (
		<li
			ref={ setNodeRef }
			style={ {
				...style,
				display: 'flex',
				alignItems: 'center',
				gap: '0.75rem',
				padding: '0.75rem',
				borderRadius: '0.5rem',
				border: isSelected ? '1px solid #3b82f6' : '1px solid #e5e7eb',
				cursor: 'pointer',
				transition: 'all 0.15s',
				backgroundColor: isSelected ? '#eff6ff' : '#fff',
				boxShadow: isDragging ? '0 10px 15px -3px rgba(0, 0, 0, 0.1)' : 'none',
				zIndex: isDragging ? 10 : 'auto',
			} }
			onClick={ onSelect }
		>
			{ /* Drag Handle */ }
			<button
				className="rp-field-list-item__handle"
				style={ { cursor: 'grab', color: '#9ca3af', padding: '0.25rem', background: 'none', border: 'none' } }
				{ ...attributes }
				{ ...listeners }
			>
				<GripVertical style={ { height: '1rem', width: '1rem' } } />
			</button>

			{ /* Field Icon */ }
			<div className="rp-field-list-item__icon" style={ { flexShrink: 0 } }>
				<IconComponent style={ { height: '1rem', width: '1rem', color: '#6b7280' } } />
			</div>

			{ /* Field Info */ }
			<div className="rp-field-list-item__info" style={ { flex: 1, minWidth: 0 } }>
				<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
					<span style={ { fontWeight: 500, fontSize: '0.875rem', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }>
						{ field.label }
					</span>
					{ isSystem && (
						<Lock style={ { height: '0.75rem', width: '0.75rem', color: '#9ca3af', flexShrink: 0 } } />
					) }
				</div>
				<div style={ { fontSize: '0.75rem', color: '#6b7280', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }>
					{ field.field_key }
				</div>
			</div>

			{ /* Field Badges */ }
			<div className="rp-field-list-item__badges" style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', flexShrink: 0 } }>
				{ field.is_required && (
					<Badge variant="secondary" style={ { fontSize: '0.75rem' } }>
						{ i18n?.required || __( 'Pflicht', 'recruiting-playbook' ) }
					</Badge>
				) }
				{ field.is_enabled ? (
					<Eye style={ { height: '1rem', width: '1rem', color: '#22c55e' } } title={ i18n?.enabled || __( 'Aktiviert', 'recruiting-playbook' ) } />
				) : (
					<EyeOff style={ { height: '1rem', width: '1rem', color: '#9ca3af' } } title={ i18n?.disabled || __( 'Deaktiviert', 'recruiting-playbook' ) } />
				) }
			</div>

			{ /* Field Type Badge */ }
			<Badge variant="outline" style={ { fontSize: '0.75rem', flexShrink: 0, textTransform: 'capitalize' } }>
				{ field.type }
			</Badge>
		</li>
	);
}

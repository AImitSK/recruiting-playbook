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
			style={ style }
			className={ `
				rp-field-list-item
				flex items-center gap-3 p-3 rounded-lg border cursor-pointer
				transition-colors duration-150
				${ isSelected
		? 'border-blue-500 bg-blue-50'
		: 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
}
				${ isDragging ? 'shadow-lg z-10' : '' }
			` }
			onClick={ onSelect }
		>
			{ /* Drag Handle */ }
			<button
				className="rp-field-list-item__handle cursor-grab text-gray-400 hover:text-gray-600 p-1"
				{ ...attributes }
				{ ...listeners }
			>
				<GripVertical className="h-4 w-4" />
			</button>

			{ /* Field Icon */ }
			<div className="rp-field-list-item__icon flex-shrink-0">
				<IconComponent className="h-4 w-4 text-gray-500" />
			</div>

			{ /* Field Info */ }
			<div className="rp-field-list-item__info flex-1 min-w-0">
				<div className="flex items-center gap-2">
					<span className="font-medium text-sm truncate">
						{ field.label }
					</span>
					{ isSystem && (
						<Lock className="h-3 w-3 text-gray-400 flex-shrink-0" />
					) }
				</div>
				<div className="text-xs text-gray-500 truncate">
					{ field.field_key }
				</div>
			</div>

			{ /* Field Badges */ }
			<div className="rp-field-list-item__badges flex items-center gap-2 flex-shrink-0">
				{ field.is_required && (
					<Badge variant="secondary" className="text-xs">
						{ i18n?.required || __( 'Pflicht', 'recruiting-playbook' ) }
					</Badge>
				) }
				{ field.is_enabled ? (
					<Eye className="h-4 w-4 text-green-500" title={ i18n?.enabled || __( 'Aktiviert', 'recruiting-playbook' ) } />
				) : (
					<EyeOff className="h-4 w-4 text-gray-400" title={ i18n?.disabled || __( 'Deaktiviert', 'recruiting-playbook' ) } />
				) }
			</div>

			{ /* Field Type Badge */ }
			<Badge variant="outline" className="text-xs flex-shrink-0 capitalize">
				{ field.type }
			</Badge>
		</li>
	);
}

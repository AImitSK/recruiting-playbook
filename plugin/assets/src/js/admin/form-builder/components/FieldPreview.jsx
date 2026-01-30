/**
 * FieldPreview Component
 *
 * Preview rendering for a single form field.
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { Input } from '../../components/ui/input';
import { Textarea } from '../../components/ui/textarea';
import { Label } from '../../components/ui/label';
import { Badge } from '../../components/ui/badge';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '../../components/ui/select';
import { Upload } from 'lucide-react';

/**
 * Get width class based on field settings
 *
 * @param {string} width Width setting
 * @param {string} viewMode Current view mode
 * @return {string} CSS class
 */
function getWidthClass( width, viewMode ) {
	// On mobile, all fields are full width
	if ( viewMode === 'mobile' ) {
		return 'col-span-full';
	}

	switch ( width ) {
		case 'half':
			return 'col-span-1';
		case 'third':
			return viewMode === 'tablet' ? 'col-span-1' : 'col-span-1';
		case 'two-thirds':
			return 'col-span-2';
		default:
			return 'col-span-full';
	}
}

/**
 * FieldPreview component
 *
 * @param {Object} props Component props
 * @param {Object} props.field     Field definition
 * @param {Object} props.fieldType Field type configuration
 * @param {string} props.viewMode  Current view mode (desktop/tablet/mobile)
 */
export default function FieldPreview( { field, fieldType, viewMode = 'desktop' } ) {
	const width = field.settings?.width || 'full';
	const widthClass = getWidthClass( width, viewMode );

	// Render heading differently
	if ( field.type === 'heading' ) {
		const level = field.settings?.level || 'h3';
		const HeadingTag = level;

		return (
			<div className={ `rp-field-preview rp-field-preview--heading ${ widthClass }` }>
				<HeadingTag className="font-semibold text-lg">
					{ field.label }
				</HeadingTag>
				{ field.description && (
					<p className="text-sm text-gray-600 mt-1">{ field.description }</p>
				) }
			</div>
		);
	}

	// Field label with required indicator
	const renderLabel = () => (
		<Label className="flex items-center gap-2">
			{ field.label }
			{ field.is_required && (
				<Badge variant="destructive" className="text-xs px-1 py-0">
					*
				</Badge>
			) }
		</Label>
	);

	// Render input based on field type
	const renderInput = () => {
		const options = field.settings?.options || [];

		switch ( field.type ) {
			case 'text':
			case 'email':
			case 'phone':
			case 'url':
				return (
					<Input
						type={ field.type === 'phone' ? 'tel' : field.type }
						placeholder={ field.placeholder || '' }
						disabled
					/>
				);

			case 'number':
				return (
					<Input
						type="number"
						placeholder={ field.placeholder || '' }
						min={ field.validation?.min_value }
						max={ field.validation?.max_value }
						disabled
					/>
				);

			case 'textarea':
				return (
					<Textarea
						placeholder={ field.placeholder || '' }
						rows={ field.settings?.rows || 4 }
						disabled
					/>
				);

			case 'date':
				return (
					<Input
						type="date"
						disabled
					/>
				);

			case 'select':
				return (
					<Select disabled>
						<SelectTrigger>
							<SelectValue placeholder={ field.placeholder || __( 'Bitte wählen...', 'recruiting-playbook' ) } />
						</SelectTrigger>
						<SelectContent>
							{ options.map( ( opt, idx ) => (
								<SelectItem key={ idx } value={ opt.value || opt.label }>
									{ opt.label }
								</SelectItem>
							) ) }
						</SelectContent>
					</Select>
				);

			case 'radio':
				return (
					<div className={ `space-y-2 ${ field.settings?.layout === 'inline' ? 'flex flex-wrap gap-4' : '' }` }>
						{ options.map( ( opt, idx ) => (
							<label key={ idx } className="flex items-center gap-2 cursor-not-allowed opacity-70">
								<input
									type="radio"
									name={ field.field_key }
									value={ opt.value || opt.label }
									disabled
									className="h-4 w-4"
								/>
								<span>{ opt.label }</span>
							</label>
						) ) }
					</div>
				);

			case 'checkbox':
				// Single checkbox vs multi
				if ( field.settings?.mode === 'multi' && options.length > 0 ) {
					return (
						<div className={ `space-y-2 ${ field.settings?.layout === 'inline' ? 'flex flex-wrap gap-4' : '' }` }>
							{ options.map( ( opt, idx ) => (
								<label key={ idx } className="flex items-center gap-2 cursor-not-allowed opacity-70">
									<input
										type="checkbox"
										value={ opt.value || opt.label }
										disabled
										className="h-4 w-4"
									/>
									<span>{ opt.label }</span>
								</label>
							) ) }
						</div>
					);
				}
				return (
					<label className="flex items-center gap-2 cursor-not-allowed opacity-70">
						<input type="checkbox" disabled className="h-4 w-4" />
						<span>{ field.settings?.checkbox_label || field.label }</span>
					</label>
				);

			case 'file':
				return (
					<div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center bg-gray-50">
						<Upload className="h-8 w-8 mx-auto text-gray-400 mb-2" />
						<p className="text-sm text-gray-600">
							{ __( 'Dateien hierher ziehen oder klicken zum Auswählen', 'recruiting-playbook' ) }
						</p>
						{ field.validation?.allowed_types && (
							<p className="text-xs text-gray-500 mt-1">
								{ __( 'Erlaubte Typen:', 'recruiting-playbook' ) } { field.validation.allowed_types }
							</p>
						) }
						{ field.validation?.max_file_size && (
							<p className="text-xs text-gray-500">
								{ __( 'Max. Größe:', 'recruiting-playbook' ) } { field.validation.max_file_size } MB
							</p>
						) }
					</div>
				);

			default:
				return (
					<Input
						placeholder={ field.placeholder || '' }
						disabled
					/>
				);
		}
	};

	return (
		<div className={ `rp-field-preview rp-field-preview--${ field.type } ${ widthClass } space-y-2` }>
			{ /* Don't show label for checkbox single mode */ }
			{ ! ( field.type === 'checkbox' && field.settings?.mode !== 'multi' ) && renderLabel() }

			{ renderInput() }

			{ field.description && (
				<p className="text-xs text-gray-500">{ field.description }</p>
			) }
		</div>
	);
}

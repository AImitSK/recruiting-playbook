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
import { Select, SelectOption } from '../../components/ui/select';
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
				<HeadingTag style={ { fontWeight: 600, fontSize: '1.125rem', margin: 0 } }>
					{ field.label }
				</HeadingTag>
				{ field.description && (
					<p style={ { fontSize: '0.875rem', color: '#4b5563', marginTop: '0.25rem' } }>{ field.description }</p>
				) }
			</div>
		);
	}

	// Field label with required indicator
	const renderLabel = () => (
		<Label style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
			{ field.label }
			{ field.is_required && (
				<Badge variant="destructive" style={ { fontSize: '0.75rem', padding: '0 0.25rem' } }>
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
					<Select disabled defaultValue="">
						<SelectOption value="">
							{ field.placeholder || __( 'Bitte wählen...', 'recruiting-playbook' ) }
						</SelectOption>
						{ options.map( ( opt, idx ) => (
							<SelectOption key={ idx } value={ opt.value || opt.label }>
								{ opt.label }
							</SelectOption>
						) ) }
					</Select>
				);

			case 'radio':
				return (
					<div style={ field.settings?.layout === 'inline' ? { display: 'flex', flexWrap: 'wrap', gap: '1rem' } : { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						{ options.map( ( opt, idx ) => (
							<label key={ idx } style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'not-allowed', opacity: 0.7 } }>
								<input
									type="radio"
									name={ field.field_key }
									value={ opt.value || opt.label }
									disabled
									style={ { height: '1rem', width: '1rem' } }
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
						<div style={ field.settings?.layout === 'inline' ? { display: 'flex', flexWrap: 'wrap', gap: '1rem' } : { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							{ options.map( ( opt, idx ) => (
								<label key={ idx } style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'not-allowed', opacity: 0.7 } }>
									<input
										type="checkbox"
										value={ opt.value || opt.label }
										disabled
										style={ { height: '1rem', width: '1rem' } }
									/>
									<span>{ opt.label }</span>
								</label>
							) ) }
						</div>
					);
				}
				return (
					<label style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'not-allowed', opacity: 0.7 } }>
						<input type="checkbox" disabled style={ { height: '1rem', width: '1rem' } } />
						<span>{ field.settings?.checkbox_label || field.label }</span>
					</label>
				);

			case 'file':
				return (
					<div style={ { border: '2px dashed #d1d5db', borderRadius: '0.5rem', padding: '1.5rem', textAlign: 'center', backgroundColor: '#f9fafb' } }>
						<Upload style={ { height: '2rem', width: '2rem', margin: '0 auto 0.5rem', color: '#9ca3af' } } />
						<p style={ { fontSize: '0.875rem', color: '#4b5563', margin: 0 } }>
							{ __( 'Dateien hierher ziehen oder klicken zum Auswählen', 'recruiting-playbook' ) }
						</p>
						{ field.validation?.allowed_types && (
							<p style={ { fontSize: '0.75rem', color: '#6b7280', marginTop: '0.25rem' } }>
								{ __( 'Erlaubte Typen:', 'recruiting-playbook' ) } { field.validation.allowed_types }
							</p>
						) }
						{ field.validation?.max_file_size && (
							<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
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
		<div className={ `rp-field-preview rp-field-preview--${ field.type } ${ widthClass }` } style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
			{ /* Don't show label for checkbox single mode */ }
			{ ! ( field.type === 'checkbox' && field.settings?.mode !== 'multi' ) && renderLabel() }

			{ renderInput() }

			{ field.description && (
				<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>{ field.description }</p>
			) }
		</div>
	);
}

/**
 * DynamicFieldRenderer Component
 *
 * Reusable component for dynamically displaying form fields.
 * Renders fields based on their type (text, email, phone, file, select, etc.)
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import {
	FileText,
	Link,
	Calendar,
	Hash,
	Mail,
	Phone,
	CheckSquare,
	Eye,
	Download,
	Copy,
} from 'lucide-react';
import { Button } from '../ui/button';

/**
 * Icon mapping for field types
 */
const FIELD_TYPE_ICONS = {
	text: null,
	textarea: null,
	email: Mail,
	phone: Phone,
	tel: Phone,
	url: Link,
	number: Hash,
	date: Calendar,
	select: null,
	radio: null,
	checkbox: CheckSquare,
	file: FileText,
	heading: null,
};

/**
 * Clipboard function
 * @param text
 */
function copyToClipboard( text ) {
	navigator.clipboard.writeText( text );
}

/**
 * Email field component
 * @param root0
 * @param root0.value
 * @param root0.showCopy
 */
function EmailField( { value, showCopy = true } ) {
	if ( ! value ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				gap: '0.375rem',
			} }
		>
			<a
				href={ `mailto:${ value }` }
				style={ {
					color: '#1d71b8',
					textDecoration: 'none',
					fontSize: '0.875rem',
				} }
			>
				{ value }
			</a>
			{ showCopy && (
				<button
					type="button"
					onClick={ () => copyToClipboard( value ) }
					title={ __( 'Copy', 'recruiting-playbook' ) }
					style={ {
						padding: '0.25rem',
						background: 'none',
						border: 'none',
						cursor: 'pointer',
						color: '#9ca3af',
						borderRadius: '0.25rem',
					} }
					onMouseEnter={ ( e ) =>
						( e.currentTarget.style.color = '#6b7280' )
					}
					onMouseLeave={ ( e ) =>
						( e.currentTarget.style.color = '#9ca3af' )
					}
				>
					<Copy style={ { width: '0.875rem', height: '0.875rem' } } />
				</button>
			) }
		</span>
	);
}

/**
 * Phone field component
 * @param root0
 * @param root0.value
 * @param root0.showCopy
 */
function PhoneField( { value, showCopy = true } ) {
	if ( ! value ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				gap: '0.375rem',
			} }
		>
			<a
				href={ `tel:${ value }` }
				style={ {
					color: '#1d71b8',
					textDecoration: 'none',
					fontSize: '0.875rem',
				} }
			>
				{ value }
			</a>
			{ showCopy && (
				<button
					type="button"
					onClick={ () => copyToClipboard( value ) }
					title={ __( 'Copy', 'recruiting-playbook' ) }
					style={ {
						padding: '0.25rem',
						background: 'none',
						border: 'none',
						cursor: 'pointer',
						color: '#9ca3af',
						borderRadius: '0.25rem',
					} }
					onMouseEnter={ ( e ) =>
						( e.currentTarget.style.color = '#6b7280' )
					}
					onMouseLeave={ ( e ) =>
						( e.currentTarget.style.color = '#9ca3af' )
					}
				>
					<Copy style={ { width: '0.875rem', height: '0.875rem' } } />
				</button>
			) }
		</span>
	);
}

/**
 * URL field component
 *
 * SECURITY: Validates that URLs begin with http:// or https://
 * to block javascript: and other dangerous protocols.
 * @param root0
 * @param root0.value
 */
function UrlField( { value } ) {
	if ( ! value ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	// SECURITY FIX: Only allow http/https URLs to prevent XSS.
	const sanitizedUrl = /^https?:\/\//i.test( value ) ? value : '';

	if ( ! sanitizedUrl ) {
		// Invalid protocol - display as text only, not as link.
		return (
			<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>
				{ value }
			</span>
		);
	}

	return (
		<a
			href={ sanitizedUrl }
			target="_blank"
			rel="noopener noreferrer"
			style={ {
				color: '#1d71b8',
				textDecoration: 'none',
				fontSize: '0.875rem',
			} }
		>
			{ sanitizedUrl }
		</a>
	);
}

/**
 * File field component
 * @param root0
 * @param root0.value
 */
function FileField( { value } ) {
	if ( ! value || ( Array.isArray( value ) && value.length === 0 ) ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	// Convert single file to array
	const files = Array.isArray( value ) ? value : [ value ];

	return (
		<div
			style={ {
				display: 'flex',
				flexDirection: 'column',
				gap: '0.5rem',
			} }
		>
			{ files.map( ( file, index ) => (
				<div
					key={ file.id || index }
					style={ {
						display: 'inline-flex',
						alignItems: 'center',
						gap: '0.5rem',
						fontSize: '0.875rem',
					} }
				>
					<FileText
						style={ {
							width: '0.875rem',
							height: '0.875rem',
							color: '#6b7280',
						} }
					/>
					<span>
						{ file.filename || file.name || `File ${ index + 1 }` }
					</span>
					{ file.view_url && (
						<Button
							variant="ghost"
							size="sm"
							asChild
							style={ { padding: '0.25rem' } }
						>
							<a
								href={ file.view_url }
								target="_blank"
								rel="noopener noreferrer"
								title={ __( 'View', 'recruiting-playbook' ) }
							>
								<Eye
									style={ {
										width: '0.75rem',
										height: '0.75rem',
									} }
								/>
							</a>
						</Button>
					) }
					{ file.download_url && (
						<Button
							variant="ghost"
							size="sm"
							asChild
							style={ { padding: '0.25rem' } }
						>
							<a
								href={ file.download_url }
								download
								title={ __(
									'Download',
									'recruiting-playbook'
								) }
							>
								<Download
									style={ {
										width: '0.75rem',
										height: '0.75rem',
									} }
								/>
							</a>
						</Button>
					) }
				</div>
			) ) }
		</div>
	);
}

/**
 * Checkbox field component
 * @param root0
 * @param root0.value
 */
function CheckboxField( { value } ) {
	if ( typeof value === 'boolean' ) {
		return (
			<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>
				{ value
					? __( 'Yes', 'recruiting-playbook' )
					: __( 'No', 'recruiting-playbook' ) }
			</span>
		);
	}

	if ( Array.isArray( value ) ) {
		return (
			<span
				style={ {
					color: value.length > 0 ? '#1f2937' : '#9ca3af',
					fontSize: '0.875rem',
				} }
			>
				{ value.length > 0 ? value.join( ', ' ) : '-' }
			</span>
		);
	}

	return <span style={ { color: '#9ca3af', fontSize: '0.875rem' } }>-</span>;
}

/**
 * Choice field component (Select, Radio)
 * @param root0
 * @param root0.value
 * @param root0.options
 */
function ChoiceField( { value, options } ) {
	if ( ! value && value !== 0 ) {
		return (
			<span style={ { color: '#9ca3af', fontSize: '0.875rem' } }>-</span>
		);
	}

	// Determine display value from options if possible
	let displayValue = value;

	if ( options && Array.isArray( options ) ) {
		const selectedOption = options.find( ( opt ) => {
			const optValue = typeof opt === 'object' ? opt.value : opt;
			return optValue === value;
		} );

		if ( selectedOption ) {
			displayValue =
				typeof selectedOption === 'object'
					? selectedOption.label
					: selectedOption;
		}
	}

	if ( Array.isArray( value ) ) {
		displayValue = value.join( ', ' );
	}

	return (
		<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>
			{ displayValue }
		</span>
	);
}

/**
 * Text field component (Default)
 * @param root0
 * @param root0.value
 * @param root0.multiline
 */
function TextField( { value, multiline = false } ) {
	if ( ! value && value !== 0 ) {
		return (
			<span style={ { color: '#9ca3af', fontSize: '0.875rem' } }>-</span>
		);
	}

	const style = {
		color: '#1f2937',
		fontSize: '0.875rem',
		...( multiline && { whiteSpace: 'pre-wrap' } ),
	};

	return <span style={ style }>{ String( value ) }</span>;
}

/**
 * Display single field
 * @param root0
 * @param root0.field
 * @param root0.value
 * @param root0.showLabel
 * @param root0.showIcon
 * @param root0.labelWidth
 */
export function FieldDisplay( {
	field,
	value,
	showLabel = true,
	showIcon = true,
	labelWidth = 140,
} ) {
	const { field_key, field_type, label, options, is_required } = field;
	const Icon = FIELD_TYPE_ICONS[ field_type ];

	// Hide empty optional fields
	const isEmpty =
		value === null ||
		value === undefined ||
		value === '' ||
		( Array.isArray( value ) && value.length === 0 );

	if ( isEmpty && ! is_required ) {
		return null;
	}

	// Render field content based on type
	const renderValue = () => {
		switch ( field_type ) {
			case 'email':
				return <EmailField value={ value } />;
			case 'phone':
			case 'tel':
				return <PhoneField value={ value } />;
			case 'url':
				return <UrlField value={ value } />;
			case 'file':
				return <FileField value={ value } />;
			case 'checkbox':
				return <CheckboxField value={ value } />;
			case 'select':
			case 'radio':
				return <ChoiceField value={ value } options={ options } />;
			case 'textarea':
				return <TextField value={ value } multiline />;
			default:
				return <TextField value={ value } />;
		}
	};

	if ( ! showLabel ) {
		return renderValue();
	}

	return (
		<div
			style={ {
				display: 'flex',
				padding: '0.5rem 0',
				borderBottom: '1px solid #f3f4f6',
			} }
		>
			<span
				style={ {
					color: '#6b7280',
					fontSize: '0.875rem',
					width: `${ labelWidth }px`,
					flexShrink: 0,
					display: 'flex',
					alignItems: 'center',
					gap: '0.375rem',
				} }
			>
				{ showIcon && Icon && (
					<Icon style={ { width: '0.875rem', height: '0.875rem' } } />
				) }
				{ label }
			</span>
			<div style={ { flex: 1, minWidth: 0 } }>{ renderValue() }</div>
		</div>
	);
}

/**
 * DynamicFieldRenderer main component
 *
 * @param {Object}  props                   Component props
 * @param {Array}   props.fields            Array of field definitions from the API
 * @param {Object}  props.data              Data object with field values (key => value)
 * @param {boolean} props.hideEmptyOptional Hide empty optional fields (default: true)
 * @param {boolean} props.showIcons         Display icons (default: true)
 * @param {number}  props.labelWidth        Label width in pixels (default: 140)
 * @param {string}  props.layout            Layout: 'single' or 'two-column' (default: 'single')
 */
export function DynamicFieldRenderer( {
	fields,
	data,
	hideEmptyOptional = true,
	showIcons = true,
	labelWidth = 140,
	layout = 'single',
} ) {
	if ( ! fields || ! Array.isArray( fields ) || fields.length === 0 ) {
		return null;
	}

	// Filter fields when hideEmptyOptional is active
	const visibleFields = hideEmptyOptional
		? fields.filter( ( field ) => {
				const value = data[ field.field_key ];
				const isEmpty =
					value === null ||
					value === undefined ||
					value === '' ||
					( Array.isArray( value ) && value.length === 0 );
				return ! isEmpty || field.is_required;
		  } )
		: fields;

	if ( visibleFields.length === 0 ) {
		return (
			<div
				style={ {
					color: '#6b7280',
					fontSize: '0.875rem',
					padding: '1rem 0',
					textAlign: 'center',
				} }
			>
				{ __( 'No data available', 'recruiting-playbook' ) }
			</div>
		);
	}

	const gridStyle =
		layout === 'two-column'
			? { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0 2rem' }
			: { display: 'grid', gridTemplateColumns: '1fr', gap: '0' };

	return (
		<div style={ gridStyle }>
			{ visibleFields.map( ( field ) => (
				<FieldDisplay
					key={ field.field_key }
					field={ field }
					value={ data[ field.field_key ] }
					showIcon={ showIcons }
					labelWidth={ labelWidth }
				/>
			) ) }
		</div>
	);
}

export default DynamicFieldRenderer;

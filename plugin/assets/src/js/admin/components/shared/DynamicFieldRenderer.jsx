/**
 * DynamicFieldRenderer Component
 *
 * Wiederverwendbare Komponente zur dynamischen Anzeige von Formularfeldern.
 * Rendert Felder basierend auf ihrem Typ (text, email, phone, file, select, etc.)
 *
 * @package RecruitingPlaybook
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
 * Icon-Mapping für Feldtypen
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
 * Clipboard-Funktion
 */
function copyToClipboard( text ) {
	navigator.clipboard.writeText( text );
}

/**
 * Email-Feld Komponente
 */
function EmailField( { value, showCopy = true } ) {
	if ( ! value ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	return (
		<span style={ { display: 'inline-flex', alignItems: 'center', gap: '0.375rem' } }>
			<a
				href={ `mailto:${ value }` }
				style={ { color: '#1d71b8', textDecoration: 'none', fontSize: '0.875rem' } }
			>
				{ value }
			</a>
			{ showCopy && (
				<button
					type="button"
					onClick={ () => copyToClipboard( value ) }
					title={ __( 'Kopieren', 'recruiting-playbook' ) }
					style={ {
						padding: '0.25rem',
						background: 'none',
						border: 'none',
						cursor: 'pointer',
						color: '#9ca3af',
						borderRadius: '0.25rem',
					} }
					onMouseEnter={ ( e ) => ( e.currentTarget.style.color = '#6b7280' ) }
					onMouseLeave={ ( e ) => ( e.currentTarget.style.color = '#9ca3af' ) }
				>
					<Copy style={ { width: '0.875rem', height: '0.875rem' } } />
				</button>
			) }
		</span>
	);
}

/**
 * Phone-Feld Komponente
 */
function PhoneField( { value, showCopy = true } ) {
	if ( ! value ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	return (
		<span style={ { display: 'inline-flex', alignItems: 'center', gap: '0.375rem' } }>
			<a
				href={ `tel:${ value }` }
				style={ { color: '#1d71b8', textDecoration: 'none', fontSize: '0.875rem' } }
			>
				{ value }
			</a>
			{ showCopy && (
				<button
					type="button"
					onClick={ () => copyToClipboard( value ) }
					title={ __( 'Kopieren', 'recruiting-playbook' ) }
					style={ {
						padding: '0.25rem',
						background: 'none',
						border: 'none',
						cursor: 'pointer',
						color: '#9ca3af',
						borderRadius: '0.25rem',
					} }
					onMouseEnter={ ( e ) => ( e.currentTarget.style.color = '#6b7280' ) }
					onMouseLeave={ ( e ) => ( e.currentTarget.style.color = '#9ca3af' ) }
				>
					<Copy style={ { width: '0.875rem', height: '0.875rem' } } />
				</button>
			) }
		</span>
	);
}

/**
 * URL-Feld Komponente
 *
 * SECURITY: Validiert dass URLs mit http:// oder https:// beginnen
 * um javascript: und andere gefährliche Protokolle zu blockieren.
 */
function UrlField( { value } ) {
	if ( ! value ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	// SECURITY FIX: Nur http/https URLs erlauben um XSS zu verhindern.
	const sanitizedUrl = /^https?:\/\//i.test( value ) ? value : '';

	if ( ! sanitizedUrl ) {
		// Ungültiges Protokoll - nur Text anzeigen, nicht als Link.
		return <span style={ { color: '#1f2937', fontSize: '0.875rem' } }>{ value }</span>;
	}

	return (
		<a
			href={ sanitizedUrl }
			target="_blank"
			rel="noopener noreferrer"
			style={ { color: '#1d71b8', textDecoration: 'none', fontSize: '0.875rem' } }
		>
			{ sanitizedUrl }
		</a>
	);
}

/**
 * File-Feld Komponente
 */
function FileField( { value } ) {
	if ( ! value || ( Array.isArray( value ) && value.length === 0 ) ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	// Einzelne Datei in Array konvertieren
	const files = Array.isArray( value ) ? value : [ value ];

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
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
					<FileText style={ { width: '0.875rem', height: '0.875rem', color: '#6b7280' } } />
					<span>{ file.filename || file.name || `Datei ${ index + 1 }` }</span>
					{ file.view_url && (
						<Button variant="ghost" size="sm" asChild style={ { padding: '0.25rem' } }>
							<a href={ file.view_url } target="_blank" rel="noopener noreferrer" title={ __( 'Ansehen', 'recruiting-playbook' ) }>
								<Eye style={ { width: '0.75rem', height: '0.75rem' } } />
							</a>
						</Button>
					) }
					{ file.download_url && (
						<Button variant="ghost" size="sm" asChild style={ { padding: '0.25rem' } }>
							<a href={ file.download_url } download title={ __( 'Herunterladen', 'recruiting-playbook' ) }>
								<Download style={ { width: '0.75rem', height: '0.75rem' } } />
							</a>
						</Button>
					) }
				</div>
			) ) }
		</div>
	);
}

/**
 * Checkbox-Feld Komponente
 */
function CheckboxField( { value } ) {
	if ( typeof value === 'boolean' ) {
		return (
			<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>
				{ value ? __( 'Ja', 'recruiting-playbook' ) : __( 'Nein', 'recruiting-playbook' ) }
			</span>
		);
	}

	if ( Array.isArray( value ) ) {
		return (
			<span style={ { color: value.length > 0 ? '#1f2937' : '#9ca3af', fontSize: '0.875rem' } }>
				{ value.length > 0 ? value.join( ', ' ) : '-' }
			</span>
		);
	}

	return <span style={ { color: '#9ca3af', fontSize: '0.875rem' } }>-</span>;
}

/**
 * Choice-Feld Komponente (Select, Radio)
 */
function ChoiceField( { value, options } ) {
	if ( ! value && value !== 0 ) {
		return <span style={ { color: '#9ca3af', fontSize: '0.875rem' } }>-</span>;
	}

	// Display-Value aus Options ermitteln wenn möglich
	let displayValue = value;

	if ( options && Array.isArray( options ) ) {
		const selectedOption = options.find( ( opt ) => {
			const optValue = typeof opt === 'object' ? opt.value : opt;
			return optValue === value;
		} );

		if ( selectedOption ) {
			displayValue = typeof selectedOption === 'object' ? selectedOption.label : selectedOption;
		}
	}

	if ( Array.isArray( value ) ) {
		displayValue = value.join( ', ' );
	}

	return <span style={ { color: '#1f2937', fontSize: '0.875rem' } }>{ displayValue }</span>;
}

/**
 * Text-Feld Komponente (Default)
 */
function TextField( { value, multiline = false } ) {
	if ( ! value && value !== 0 ) {
		return <span style={ { color: '#9ca3af', fontSize: '0.875rem' } }>-</span>;
	}

	const style = {
		color: '#1f2937',
		fontSize: '0.875rem',
		...(multiline && { whiteSpace: 'pre-wrap' }),
	};

	return <span style={ style }>{ String( value ) }</span>;
}

/**
 * Einzelnes Feld anzeigen
 */
export function FieldDisplay( { field, value, showLabel = true, showIcon = true, labelWidth = 140 } ) {
	const { field_key, field_type, label, options, is_required } = field;
	const Icon = FIELD_TYPE_ICONS[ field_type ];

	// Leere optionale Felder ausblenden
	const isEmpty = value === null || value === undefined || value === '' ||
		( Array.isArray( value ) && value.length === 0 );

	if ( isEmpty && ! is_required ) {
		return null;
	}

	// Feld-Inhalt basierend auf Typ rendern
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
		<div style={ { display: 'flex', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
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
				{ showIcon && Icon && <Icon style={ { width: '0.875rem', height: '0.875rem' } } /> }
				{ label }
			</span>
			<div style={ { flex: 1, minWidth: 0 } }>
				{ renderValue() }
			</div>
		</div>
	);
}

/**
 * DynamicFieldRenderer Hauptkomponente
 *
 * @param {Object}  props                   Komponenten-Props
 * @param {Array}   props.fields            Array mit Feld-Definitionen aus der API
 * @param {Object}  props.data              Daten-Objekt mit Feld-Werten (key => value)
 * @param {boolean} props.hideEmptyOptional Leere optionale Felder ausblenden (default: true)
 * @param {boolean} props.showIcons         Icons anzeigen (default: true)
 * @param {number}  props.labelWidth        Breite des Labels in Pixel (default: 140)
 * @param {string}  props.layout            Layout: 'single' oder 'two-column' (default: 'single')
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

	// Felder filtern wenn hideEmptyOptional aktiv
	const visibleFields = hideEmptyOptional
		? fields.filter( ( field ) => {
				const value = data[ field.field_key ];
				const isEmpty = value === null || value === undefined || value === '' ||
					( Array.isArray( value ) && value.length === 0 );
				return ! isEmpty || field.is_required;
		  } )
		: fields;

	if ( visibleFields.length === 0 ) {
		return (
			<div style={ { color: '#6b7280', fontSize: '0.875rem', padding: '1rem 0', textAlign: 'center' } }>
				{ __( 'Keine Daten vorhanden', 'recruiting-playbook' ) }
			</div>
		);
	}

	const gridStyle = layout === 'two-column'
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

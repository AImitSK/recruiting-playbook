/**
 * Custom Fields Panel Component
 *
 * Displays custom fields of an application in the admin detail view.
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
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card';
import { Button } from '../components/ui/button';

/**
 * Icon mapping for field types
 */
const FIELD_TYPE_ICONS = {
	text: null,
	textarea: null,
	email: Mail,
	phone: Phone,
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
 * Render individual field
 */
function FieldValue( { field } ) {
	const { type, label, value, display_value } = field;
	const Icon = FIELD_TYPE_ICONS[ type ];

	// Skip display-only fields (html, heading renders as section header)
	if ( type === 'html' ) {
		return null;
	}

	// Heading is rendered as section header
	if ( type === 'heading' ) {
		return (
			<div style={ { gridColumn: '1 / -1', marginTop: '0.5rem' } }>
				<h4 style={ {
					fontSize: '0.9375rem',
					fontWeight: 600,
					color: '#1f2937',
					borderBottom: '1px solid #e5e7eb',
					paddingBottom: '0.5rem',
					marginBottom: '0.5rem'
				} }>
					{ label }
				</h4>
			</div>
		);
	}

	// Display empty values
	const isEmpty = value === null || value === '' || ( Array.isArray( value ) && value.length === 0 );

	// File type has special display
	if ( type === 'file' ) {
		return (
			<div style={ { display: 'flex', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
				<span style={ {
					color: '#6b7280',
					fontSize: '0.875rem',
					width: '140px',
					flexShrink: 0,
					display: 'flex',
					alignItems: 'center',
					gap: '0.375rem'
				} }>
					{ Icon && <Icon style={ { width: '0.875rem', height: '0.875rem' } } /> }
					{ label }
				</span>
				<div style={ { color: '#1f2937', fontSize: '0.875rem', flex: 1 } }>
					{ isEmpty ? (
						<span style={ { color: '#9ca3af' } }>-</span>
					) : (
						<FileList files={ value } />
					) }
				</div>
			</div>
		);
	}

	// Standard display
	return (
		<div style={ { display: 'flex', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
			<span style={ {
				color: '#6b7280',
				fontSize: '0.875rem',
				width: '140px',
				flexShrink: 0,
				display: 'flex',
				alignItems: 'center',
				gap: '0.375rem'
			} }>
				{ Icon && <Icon style={ { width: '0.875rem', height: '0.875rem' } } /> }
				{ label }
			</span>
			<span style={ { color: isEmpty ? '#9ca3af' : '#1f2937', fontSize: '0.875rem' } }>
				{ isEmpty ? '-' : formatValue( type, value, display_value ) }
			</span>
		</div>
	);
}

/**
 * Render file list
 */
function FileList( { files } ) {
	if ( ! Array.isArray( files ) || files.length === 0 ) {
		return <span style={ { color: '#9ca3af' } }>-</span>;
	}

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
			{ files.map( ( file, index ) => (
				<div
					key={ file.id || index }
					style={ {
						display: 'inline-flex',
						alignItems: 'center',
						gap: '0.5rem',
						fontSize: '0.875rem'
					} }
				>
					<FileText style={ { width: '0.875rem', height: '0.875rem', color: '#6b7280' } } />
					<span>{ file.filename || file.name || `File ${ index + 1 }` }</span>
					{ file.view_url && (
						<Button variant="ghost" size="sm" asChild style={ { padding: '0.25rem' } }>
							<a href={ file.view_url } target="_blank" rel="noopener noreferrer" title={ __( 'View', 'recruiting-playbook' ) }>
								<Eye style={ { width: '0.75rem', height: '0.75rem' } } />
							</a>
						</Button>
					) }
					{ file.download_url && (
						<Button variant="ghost" size="sm" asChild style={ { padding: '0.25rem' } }>
							<a href={ file.download_url } download title={ __( 'Download', 'recruiting-playbook' ) }>
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
 * Format value based on field type
 */
function formatValue( type, value, displayValue ) {
	// Display value has priority if present
	if ( displayValue !== undefined && displayValue !== null && displayValue !== value ) {
		return displayValue;
	}

	switch ( type ) {
		case 'url':
			return value ? (
				<a
					href={ value }
					target="_blank"
					rel="noopener noreferrer"
					style={ { color: '#1d71b8', textDecoration: 'none' } }
				>
					{ value }
				</a>
			) : '-';

		case 'email':
			return value ? (
				<a
					href={ `mailto:${ value }` }
					style={ { color: '#1d71b8', textDecoration: 'none' } }
				>
					{ value }
				</a>
			) : '-';

		case 'phone':
			return value ? (
				<a
					href={ `tel:${ value }` }
					style={ { color: '#1d71b8', textDecoration: 'none' } }
				>
					{ value }
				</a>
			) : '-';

		case 'checkbox':
			if ( typeof value === 'boolean' ) {
				return value ? __( 'Yes', 'recruiting-playbook' ) : __( 'No', 'recruiting-playbook' );
			}
			if ( Array.isArray( value ) ) {
				return value.join( ', ' ) || '-';
			}
			return value || '-';

		case 'textarea':
			return value ? (
				<span style={ { whiteSpace: 'pre-wrap' } }>{ value }</span>
			) : '-';

		default:
			if ( Array.isArray( value ) ) {
				return value.join( ', ' ) || '-';
			}
			return String( value || '' ) || '-';
	}
}

/**
 * CustomFieldsPanel main component
 */
export function CustomFieldsPanel( { customFields, showHeader = true } ) {
	// No custom fields present
	if ( ! customFields || ! Array.isArray( customFields ) || customFields.length === 0 ) {
		return null;
	}

	const content = (
		<div style={ { display: 'grid', gridTemplateColumns: '1fr', gap: '0' } }>
			{ customFields.map( ( field, index ) => (
				<FieldValue key={ field.key || index } field={ field } />
			) ) }
		</div>
	);

	if ( ! showHeader ) {
		return content;
	}

	return (
		<Card>
			<CardHeader style={ { paddingBottom: 0 } }>
				<CardTitle>{ __( 'Additional Information', 'recruiting-playbook' ) }</CardTitle>
			</CardHeader>
			<CardContent style={ { padding: '1rem 1.5rem' } }>
				{ content }
			</CardContent>
		</Card>
	);
}

export default CustomFieldsPanel;

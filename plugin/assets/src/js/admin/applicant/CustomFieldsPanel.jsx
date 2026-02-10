/**
 * Custom Fields Panel Component
 *
 * Zeigt Custom Fields einer Bewerbung in der Admin-Detailansicht an.
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
 * Icon-Mapping für Feldtypen
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
 * Einzelnes Feld rendern
 */
function FieldValue( { field } ) {
	const { type, label, value, display_value } = field;
	const Icon = FIELD_TYPE_ICONS[ type ];

	// Skip display-only fields (html, heading renders as section header)
	if ( type === 'html' ) {
		return null;
	}

	// Heading wird als Überschrift gerendert
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

	// Leere Werte anzeigen
	const isEmpty = value === null || value === '' || ( Array.isArray( value ) && value.length === 0 );

	// File-Typ hat spezielle Darstellung
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

	// Standard-Darstellung
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
 * Dateiliste rendern
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
 * Wert formatieren basierend auf Feldtyp
 */
function formatValue( type, value, displayValue ) {
	// Display-Value hat Priorität wenn vorhanden
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
				return value ? __( 'Ja', 'recruiting-playbook' ) : __( 'Nein', 'recruiting-playbook' );
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
 * CustomFieldsPanel Hauptkomponente
 */
export function CustomFieldsPanel( { customFields, showHeader = true } ) {
	// Keine Custom Fields vorhanden
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
				<CardTitle>{ __( 'Zusätzliche Angaben', 'recruiting-playbook' ) }</CardTitle>
			</CardHeader>
			<CardContent style={ { padding: '1rem 1.5rem' } }>
				{ content }
			</CardContent>
		</Card>
	);
}

export default CustomFieldsPanel;

/**
 * SystemFieldPreview Component
 *
 * Preview rendering for system fields (summary, privacy_consent, file_upload).
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { Badge } from '../../components/ui/badge';
import { Upload, FileText, CheckSquare, ClipboardList } from 'lucide-react';

/**
 * SystemFieldPreview component
 *
 * @param {Object} props            Component props
 * @param {Object} props.systemField System field configuration
 * @param {string} props.viewMode   Current view mode (desktop/tablet/mobile)
 */
export default function SystemFieldPreview( { systemField, viewMode = 'desktop' } ) {
	const { type, field_key, settings = {} } = systemField;

	// Full width style for system fields
	const widthStyle = { gridColumn: 'span 2 / span 2' };

	// Render based on system field type
	switch ( type ) {
		case 'summary':
			return renderSummaryPreview( settings, widthStyle );

		case 'privacy_consent':
			return renderPrivacyConsentPreview( settings, widthStyle );

		case 'file_upload':
			return renderFileUploadPreview( settings, widthStyle );

		default:
			return (
				<div style={ { ...widthStyle, padding: '1rem', backgroundColor: '#fef3c7', borderRadius: '0.5rem' } }>
					<p style={ { margin: 0, color: '#92400e' } }>
						{ __( 'Unbekanntes Systemfeld:', 'recruiting-playbook' ) } { type }
					</p>
				</div>
			);
	}
}

/**
 * Render summary field preview
 */
function renderSummaryPreview( settings, widthStyle ) {
	const title = settings.title || settings.label || __( 'Ihre Angaben im Überblick', 'recruiting-playbook' );
	const additionalText = settings.additional_text || settings.help_text || '';

	return (
		<div
			className="rp-system-field-preview rp-system-field-preview--summary"
			style={ {
				...widthStyle,
				backgroundColor: '#f0fdf4',
				border: '1px solid #86efac',
				borderRadius: '0.5rem',
				padding: '1rem',
			} }
		>
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' } }>
				<ClipboardList style={ { height: '1.25rem', width: '1.25rem', color: '#16a34a' } } />
				<span style={ { fontWeight: 600, color: '#166534' } }>{ title }</span>
				<Badge
					variant="outline"
					style={ { marginLeft: 'auto', backgroundColor: '#dcfce7', color: '#166534', border: '1px solid #86efac' } }
				>
					{ __( 'Systemfeld', 'recruiting-playbook' ) }
				</Badge>
			</div>

			<div style={ { backgroundColor: 'white', borderRadius: '0.375rem', padding: '1rem', border: '1px dashed #d1d5db' } }>
				<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem', color: '#6b7280', fontSize: '0.875rem' } }>
					<div style={ { display: 'flex', justifyContent: 'space-between', padding: '0.25rem 0', borderBottom: '1px solid #f3f4f6' } }>
						<span>{ __( 'Vorname:', 'recruiting-playbook' ) }</span>
						<span style={ { color: '#9ca3af' } }>{ __( '(wird angezeigt)', 'recruiting-playbook' ) }</span>
					</div>
					<div style={ { display: 'flex', justifyContent: 'space-between', padding: '0.25rem 0', borderBottom: '1px solid #f3f4f6' } }>
						<span>{ __( 'Nachname:', 'recruiting-playbook' ) }</span>
						<span style={ { color: '#9ca3af' } }>{ __( '(wird angezeigt)', 'recruiting-playbook' ) }</span>
					</div>
					<div style={ { display: 'flex', justifyContent: 'space-between', padding: '0.25rem 0' } }>
						<span>{ __( 'E-Mail:', 'recruiting-playbook' ) }</span>
						<span style={ { color: '#9ca3af' } }>{ __( '(wird angezeigt)', 'recruiting-playbook' ) }</span>
					</div>
					<p style={ { textAlign: 'center', color: '#9ca3af', fontSize: '0.75rem', margin: '0.5rem 0 0' } }>
						{ __( '... alle eingegebenen Felder werden hier zusammengefasst', 'recruiting-playbook' ) }
					</p>
				</div>
			</div>

			{ additionalText && (
				<p style={ { fontSize: '0.75rem', color: '#6b7280', marginTop: '0.5rem', marginBottom: 0 } }>
					{ additionalText }
				</p>
			) }
		</div>
	);
}

/**
 * Render privacy consent field preview
 */
function renderPrivacyConsentPreview( settings, widthStyle ) {
	// Support both key variants
	const consentText = settings.checkbox_text || settings.consent_text || __( 'Ich habe die {datenschutz_link} gelesen und stimme zu.', 'recruiting-playbook' );
	const linkText = settings.link_text || settings.privacy_link_text || __( 'Datenschutzerklärung', 'recruiting-playbook' );

	// Replace placeholder with link text
	let displayText = consentText
		.replace( '{datenschutz_link}', linkText )
		.replace( '{privacy_link}', linkText );

	return (
		<div
			className="rp-system-field-preview rp-system-field-preview--privacy-consent"
			style={ {
				...widthStyle,
				backgroundColor: '#f0fdf4',
				border: '1px solid #86efac',
				borderRadius: '0.5rem',
				padding: '1rem',
			} }
		>
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' } }>
				<CheckSquare style={ { height: '1.25rem', width: '1.25rem', color: '#16a34a' } } />
				<span style={ { fontWeight: 600, color: '#166534' } }>{ __( 'Datenschutz-Einwilligung', 'recruiting-playbook' ) }</span>
				<Badge
					variant="outline"
					style={ { marginLeft: 'auto', backgroundColor: '#dcfce7', color: '#166534', border: '1px solid #86efac' } }
				>
					{ __( 'Pflichtfeld', 'recruiting-playbook' ) }
				</Badge>
			</div>

			<label style={ { display: 'flex', alignItems: 'flex-start', gap: '0.75rem', cursor: 'not-allowed', opacity: 0.8 } }>
				<input
					type="checkbox"
					disabled
					style={ { height: '1.25rem', width: '1.25rem', marginTop: '0.125rem' } }
				/>
				<span style={ { fontSize: '0.875rem', lineHeight: '1.5' } }>
					{ displayText }
				</span>
			</label>
		</div>
	);
}

/**
 * Render file upload field preview
 */
function renderFileUploadPreview( settings, widthStyle ) {
	const label = settings.label || __( 'Bewerbungsunterlagen', 'recruiting-playbook' );
	const allowedTypes = settings.allowed_types || 'pdf,doc,docx';
	const maxSize = settings.max_file_size || 10;
	const isRequired = settings.is_required !== false;

	return (
		<div
			className="rp-system-field-preview rp-system-field-preview--file-upload"
			style={ {
				...widthStyle,
				backgroundColor: '#f0fdf4',
				border: '1px solid #86efac',
				borderRadius: '0.5rem',
				padding: '1rem',
			} }
		>
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' } }>
				<FileText style={ { height: '1.25rem', width: '1.25rem', color: '#16a34a' } } />
				<span style={ { fontWeight: 600, color: '#166534' } }>{ label }</span>
				{ isRequired && (
					<Badge
						variant="outline"
						style={ { marginLeft: 'auto', backgroundColor: '#dcfce7', color: '#166534', border: '1px solid #86efac' } }
					>
						{ __( 'Pflichtfeld', 'recruiting-playbook' ) }
					</Badge>
				) }
				{ ! isRequired && (
					<Badge
						variant="outline"
						style={ { marginLeft: 'auto', backgroundColor: '#dcfce7', color: '#166534', border: '1px solid #86efac' } }
					>
						{ __( 'Systemfeld', 'recruiting-playbook' ) }
					</Badge>
				) }
			</div>

			<div style={ { border: '2px dashed #86efac', borderRadius: '0.5rem', padding: '1.5rem', textAlign: 'center', backgroundColor: 'white' } }>
				<Upload style={ { height: '2rem', width: '2rem', margin: '0 auto 0.5rem', color: '#16a34a' } } />
				<p style={ { fontSize: '0.875rem', color: '#4b5563', margin: 0 } }>
					{ __( 'Dateien hierher ziehen oder klicken zum Auswählen', 'recruiting-playbook' ) }
				</p>
				<p style={ { fontSize: '0.75rem', color: '#6b7280', marginTop: '0.5rem', marginBottom: 0 } }>
					{ __( 'Erlaubte Typen:', 'recruiting-playbook' ) } { allowedTypes }
					{ ' • ' }
					{ __( 'Max.', 'recruiting-playbook' ) } { maxSize } MB
				</p>
			</div>
		</div>
	);
}

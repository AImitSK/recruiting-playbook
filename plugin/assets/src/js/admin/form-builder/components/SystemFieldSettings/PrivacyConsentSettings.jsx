/**
 * PrivacyConsentSettings Component
 *
 * Settings panel for the privacy_consent system field.
 * Allows configuration of consent text, privacy policy link, and help text.
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Textarea } from '../../../components/ui/textarea';
import { X, Shield, ExternalLink } from 'lucide-react';

/**
 * PrivacyConsentSettings component
 *
 * @param {Object}   props           Component props
 * @param {Object}   props.settings  Current settings
 * @param {Function} props.onSave    Save handler
 * @param {Function} props.onClose   Close handler
 */
export default function PrivacyConsentSettings( { settings = {}, onSave, onClose } ) {
	// Local state for form values
	const [ label, setLabel ] = useState(
		settings.label || __( 'Privacy Consent', 'recruiting-playbook' )
	);
	const [ consentText, setConsentText ] = useState(
		settings.consent_text ||
		__( 'I have read the {privacy_link} and agree to the processing of my data.', 'recruiting-playbook' )
	);
	const [ privacyLinkText, setPrivacyLinkText ] = useState(
		settings.privacy_link_text || __( 'Privacy Policy', 'recruiting-playbook' )
	);
	const [ privacyUrl, setPrivacyUrl ] = useState( settings.privacy_url || '' );
	const [ errorMessage, setErrorMessage ] = useState(
		settings.error_message || __( 'You must agree to the privacy policy.', 'recruiting-playbook' )
	);
	const [ helpText, setHelpText ] = useState( settings.help_text || '' );

	// Handle save
	const handleSave = () => {
		onSave( {
			label,
			consent_text: consentText,
			privacy_link_text: privacyLinkText,
			privacy_url: privacyUrl,
			error_message: errorMessage,
			help_text: helpText,
		} );
	};

	/**
	 * Render preview text with safe link insertion (no dangerouslySetInnerHTML)
	 */
	const renderPreviewText = () => {
		const parts = consentText.split( '{privacy_link}' );

		return (
			<>
				{ parts[ 0 ] }
				{ privacyUrl ? (
					<a
						href={ privacyUrl }
						target="_blank"
						rel="noopener noreferrer"
						style={ { color: '#3b82f6', textDecoration: 'underline' } }
					>
						{ privacyLinkText }
					</a>
				) : (
					<span style={ { color: '#3b82f6', textDecoration: 'underline' } }>
						{ privacyLinkText }
					</span>
				) }
				{ parts[ 1 ] || '' }
			</>
		);
	};

	return (
		<div
			style={ {
				position: 'fixed',
				inset: 0,
				backgroundColor: 'rgba(0, 0, 0, 0.5)',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				zIndex: 100,
			} }
			onClick={ ( e ) => {
				if ( e.target === e.currentTarget ) {
					onClose();
				}
			} }
		>
			<Card style={ { width: '100%', maxWidth: '550px', maxHeight: '90vh', overflow: 'auto' } }>
				<CardHeader style={ { display: 'flex', flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' } }>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<Shield style={ { height: '1.25rem', width: '1.25rem', color: '#8b5cf6' } } />
						<div>
							<CardTitle>{ __( 'Privacy Consent Settings', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>{ __( 'Configure the privacy notice', 'recruiting-playbook' ) }</CardDescription>
						</div>
					</div>
					<Button variant="ghost" size="sm" onClick={ onClose }>
						<X style={ { height: '1rem', width: '1rem' } } />
					</Button>
				</CardHeader>

				<CardContent style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
					{ /* Label */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="privacy-label">{ __( 'Label (internal)', 'recruiting-playbook' ) }</Label>
						<Input
							id="privacy-label"
							value={ label }
							onChange={ ( e ) => setLabel( e.target.value ) }
							placeholder={ __( 'Privacy Consent', 'recruiting-playbook' ) }
						/>
					</div>

					{ /* Consent Text */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="consent-text">{ __( 'Consent Text', 'recruiting-playbook' ) }</Label>
						<Textarea
							id="consent-text"
							value={ consentText }
							onChange={ ( e ) => setConsentText( e.target.value ) }
							placeholder={ __( 'I have read the {privacy_link}...', 'recruiting-playbook' ) }
							rows={ 3 }
						/>
						<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
							{ __( 'Use {privacy_link} as a placeholder for the link.', 'recruiting-playbook' ) }
						</p>
					</div>

					{ /* Privacy Link Settings */ }
					<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' } }>
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="privacy-link-text">{ __( 'Link Text', 'recruiting-playbook' ) }</Label>
							<Input
								id="privacy-link-text"
								value={ privacyLinkText }
								onChange={ ( e ) => setPrivacyLinkText( e.target.value ) }
								placeholder={ __( 'Privacy Policy', 'recruiting-playbook' ) }
							/>
						</div>
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="privacy-url">
								{ __( 'Link URL', 'recruiting-playbook' ) }
								<ExternalLink style={ { height: '0.75rem', width: '0.75rem', marginLeft: '0.25rem', display: 'inline' } } />
							</Label>
							<Input
								id="privacy-url"
								type="url"
								value={ privacyUrl }
								onChange={ ( e ) => setPrivacyUrl( e.target.value ) }
								placeholder="https://example.com/privacy"
							/>
						</div>
					</div>

					{ /* Preview */ }
					<div style={ { padding: '0.75rem', backgroundColor: '#f9fafb', borderRadius: '0.5rem', border: '1px solid #e5e7eb' } }>
						<Label style={ { fontSize: '0.75rem', color: '#6b7280', marginBottom: '0.5rem', display: 'block' } }>
							{ __( 'Preview:', 'recruiting-playbook' ) }
						</Label>
						<div style={ { display: 'flex', alignItems: 'flex-start', gap: '0.5rem' } }>
							<input type="checkbox" disabled style={ { marginTop: '0.25rem' } } />
							<span style={ { fontSize: '0.875rem' } }>
								{ renderPreviewText() }
							</span>
						</div>
					</div>

					{ /* Error Message */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="error-message">{ __( 'Error Message', 'recruiting-playbook' ) }</Label>
						<Input
							id="error-message"
							value={ errorMessage }
							onChange={ ( e ) => setErrorMessage( e.target.value ) }
							placeholder={ __( 'You must agree to the privacy policy.', 'recruiting-playbook' ) }
						/>
						<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
							{ __( 'Displayed when the checkbox is not checked.', 'recruiting-playbook' ) }
						</p>
					</div>

					{ /* Help Text */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="help-text">{ __( 'Help Text (optional)', 'recruiting-playbook' ) }</Label>
						<Textarea
							id="help-text"
							value={ helpText }
							onChange={ ( e ) => setHelpText( e.target.value ) }
							placeholder={ __( 'Additional note below the checkbox...', 'recruiting-playbook' ) }
							rows={ 2 }
						/>
					</div>

					{ /* Info Box */ }
					<div style={ { padding: '0.75rem', backgroundColor: '#fef3c7', borderRadius: '0.5rem', border: '1px solid #fcd34d' } }>
						<p style={ { margin: 0, fontSize: '0.875rem', color: '#92400e' } }>
							<strong>{ __( 'Note:', 'recruiting-playbook' ) }</strong>{ ' ' }
							{ __( 'Privacy consent is a required field and cannot be removed. The consent version is saved automatically.', 'recruiting-playbook' ) }
						</p>
					</div>

					{ /* Action Buttons */ }
					<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', paddingTop: '0.5rem', borderTop: '1px solid #e5e7eb' } }>
						<Button variant="outline" onClick={ onClose }>
							{ __( 'Cancel', 'recruiting-playbook' ) }
						</Button>
						<Button onClick={ handleSave }>
							{ __( 'Save', 'recruiting-playbook' ) }
						</Button>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

/**
 * EmailComposer - Component for composing emails
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

import { Button } from '../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Select } from '../../components/ui/select';
import { Switch } from '../../components/ui/switch';
import { Label } from '../../components/ui/label';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Spinner } from '../../components/ui/spinner';
import { RichTextEditor } from '../../components/ui/rich-text-editor';

import { PlaceholderPicker } from './PlaceholderPicker';
import { EmailPreview } from './EmailPreview';
import { replacePlaceholders } from '../utils';

/**
 * EmailComposer Component
 *
 * @param {Object}   props               Props
 * @param {Array}    props.templates     Available templates
 * @param {Array}    props.signatures    Available signatures
 * @param {Object}   props.placeholders  Available placeholders
 * @param {Object}   props.previewValues Preview values for placeholders
 * @param {Object}   props.recipient     Recipient data
 * @param {number}   props.applicationId Application ID
 * @param {boolean}  props.sending       Sending status
 * @param {string}   props.error         Error message
 * @param {Function} props.onSend        Callback when sending
 * @param {Function} props.onCancel      Callback when canceling
 * @return {JSX.Element} Component
 */
export function EmailComposer( {
	templates = [],
	signatures = [],
	placeholders = {},
	previewValues = {},
	recipient = {},
	applicationId = null,
	sending = false,
	error = null,
	onSend,
	onCancel,
} ) {
	const [ selectedTemplate, setSelectedTemplate ] = useState( '' );
	const [ selectedSignature, setSelectedSignature ] = useState( '' );
	const [ formData, setFormData ] = useState( {
		to: recipient.email || '',
		subject: '',
		body: '',
	} );
	const [ activeTab, setActiveTab ] = useState( 'compose' );
	const [ validationErrors, setValidationErrors ] = useState( {} );

	const i18n = window.rpEmailData?.i18n || {};

	// Preselect default signature
	useEffect( () => {
		if ( signatures.length > 0 && ! selectedSignature ) {
			const defaultSig = signatures.find( ( s ) => s.is_default );
			if ( defaultSig ) {
				setSelectedSignature( String( defaultSig.id ) );
			} else if ( signatures[ 0 ] ) {
				setSelectedSignature( String( signatures[ 0 ].id ) );
			}
		}
	}, [ signatures, selectedSignature ] );

	// Update recipient email when recipient changes
	useEffect( () => {
		if ( recipient.email ) {
			setFormData( ( prev ) => ( { ...prev, to: recipient.email } ) );
		}
	}, [ recipient.email ] );

	/**
	 * Select template and fill fields
	 *
	 * @param {string} templateId Template ID
	 */
	const handleTemplateSelect = useCallback( ( templateId ) => {
		setSelectedTemplate( templateId );

		if ( ! templateId ) {
			return;
		}

		const template = templates.find( ( t ) => String( t.id ) === templateId );
		if ( template ) {
			setFormData( ( prev ) => ( {
				...prev,
				subject: template.subject || '',
				body: template.body_html || template.body || '',
			} ) );
		}
	}, [ templates ] );

	/**
	 * Update field
	 *
	 * @param {string} field Field name
	 * @param {*}      value Value
	 */
	const updateField = useCallback( ( field, value ) => {
		setFormData( ( prev ) => ( { ...prev, [ field ]: value } ) );

		setValidationErrors( ( prev ) => {
			if ( ! prev[ field ] ) {
				return prev;
			}
			const newErrors = { ...prev };
			delete newErrors[ field ];
			return newErrors;
		} );
	}, [] );

	/**
	 * Replace placeholders (for preview)
	 *
	 * @param {string} text Text
	 * @return {string} Text with replaced placeholders
	 */
	const getPreviewText = useCallback( ( text ) => {
		return replacePlaceholders( text, previewValues );
	}, [ previewValues ] );

	/**
	 * Validate form
	 *
	 * @return {boolean} Valid
	 */
	const validate = useCallback( () => {
		const errors = {};

		const MAX_SUBJECT_LENGTH = 255;
		const MAX_BODY_LENGTH = 50000;

		const emailRegex = /^[a-zA-Z0-9._%+-]{1,64}@[a-zA-Z0-9.-]{1,255}\.[a-zA-Z]{2,}$/;

		if ( ! formData.to.trim() ) {
			errors.to = i18n.recipientRequired || __( 'Recipient is required', 'recruiting-playbook' );
		} else if ( ! emailRegex.test( formData.to.trim() ) ) {
			errors.to = i18n.invalidEmail || __( 'Invalid email address', 'recruiting-playbook' );
		}

		if ( ! formData.subject.trim() ) {
			errors.subject = i18n.subjectRequired || __( 'Subject is required', 'recruiting-playbook' );
		} else if ( formData.subject.length > MAX_SUBJECT_LENGTH ) {
			errors.subject = `${ i18n.subjectTooLong || __( 'Subject too long', 'recruiting-playbook' ) } (max. ${ MAX_SUBJECT_LENGTH })`;
		}

		if ( ! formData.body.trim() ) {
			errors.body = i18n.bodyRequired || __( 'Content is required', 'recruiting-playbook' );
		} else if ( formData.body.length > MAX_BODY_LENGTH ) {
			errors.body = `${ i18n.bodyTooLong || __( 'Content too long', 'recruiting-playbook' ) } (max. ${ MAX_BODY_LENGTH })`;
		}

		setValidationErrors( errors );
		return Object.keys( errors ).length === 0;
	}, [ formData, i18n ] );

	/**
	 * Send email
	 */
	const handleSend = useCallback( () => {
		if ( ! validate() ) {
			return;
		}

		const emailData = {
			to: formData.to,
			subject: formData.subject,
			body: formData.body,
			template_id: selectedTemplate || null,
			application_id: applicationId,
			send_immediately: true,
		};

		// Only send signature_id if explicitly chosen (otherwise backend uses company signature)
		if ( selectedSignature ) {
			emailData.signature_id = parseInt( selectedSignature, 10 );
		}

		if ( onSend ) {
			onSend( emailData );
		}
	}, [ formData, selectedTemplate, selectedSignature, applicationId, validate, onSend ] );

	return (
		<div className="rp-email-composer">
			{ error && (
				<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			<Card>
				<CardHeader>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
						<CardTitle>{ i18n.composeEmail || __( 'Compose Email', 'recruiting-playbook' ) }</CardTitle>
						<div style={ { display: 'flex', gap: '0.5rem' } }>
							<Button
								variant={ activeTab === 'compose' ? 'default' : 'outline' }
								size="sm"
								onClick={ () => setActiveTab( 'compose' ) }
							>
								{ i18n.compose || __( 'Compose', 'recruiting-playbook' ) }
							</Button>
							<Button
								variant={ activeTab === 'preview' ? 'default' : 'outline' }
								size="sm"
								onClick={ () => setActiveTab( 'preview' ) }
							>
								{ i18n.preview || __( 'Preview', 'recruiting-playbook' ) }
							</Button>
						</div>
					</div>
				</CardHeader>

				<CardContent>
					{ activeTab === 'compose' ? (
						<div style={ { display: 'grid', gridTemplateColumns: '1fr 280px', gap: '1.5rem' } }>
							<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
								{ /* Template and signature in one row */ }
								<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' } }>
									{ /* Template */ }
									<div>
										<Label htmlFor="template">{ i18n.template || __( 'Template', 'recruiting-playbook' ) }</Label>
										<Select
											id="template"
											value={ selectedTemplate }
											onChange={ ( e ) => handleTemplateSelect( e.target.value ) }
										>
											<option value="">{ i18n.selectTemplate || __( '-- Select template --', 'recruiting-playbook' ) }</option>
											{ templates.map( ( t ) => (
												<option key={ t.id } value={ String( t.id ) }>{ t.name }</option>
											) ) }
										</Select>
									</div>

									{ /* Signature */ }
									<div>
										<Label htmlFor="signature">
											{ i18n.signature || __( 'Signature', 'recruiting-playbook' ) }
										</Label>
										<Select
											id="signature"
											value={ selectedSignature }
											onChange={ ( e ) => setSelectedSignature( e.target.value ) }
										>
											<option value="">{ i18n.companySignature || __( 'Company Signature (Default)', 'recruiting-playbook' ) }</option>
											{ signatures.map( ( s ) => (
												<option key={ s.id } value={ String( s.id ) }>
													{ s.name }{ s.is_default ? ` (${ i18n.default || 'Default' })` : '' }
												</option>
											) ) }
										</Select>
									</div>
								</div>

								{ /* Recipient */ }
								<div>
									<Label htmlFor="recipient">{ i18n.recipient || __( 'Recipient', 'recruiting-playbook' ) }</Label>
									<Input
										id="recipient"
										type="email"
										value={ formData.to }
										onChange={ ( e ) => updateField( 'to', e.target.value ) }
										style={ validationErrors.to ? { borderColor: '#d63638' } : {} }
									/>
									{ validationErrors.to && (
										<p style={ { color: '#d63638', fontSize: '0.75rem', marginTop: '0.25rem' } }>
											{ validationErrors.to }
										</p>
									) }
								</div>

								{ /* Subject */ }
								<div>
									<Label htmlFor="subject">{ i18n.subject || __( 'Subject', 'recruiting-playbook' ) }</Label>
									<Input
										id="subject"
										type="text"
										value={ formData.subject }
										onChange={ ( e ) => updateField( 'subject', e.target.value ) }
										style={ validationErrors.subject ? { borderColor: '#d63638' } : {} }
									/>
									{ validationErrors.subject && (
										<p style={ { color: '#d63638', fontSize: '0.75rem', marginTop: '0.25rem' } }>
											{ validationErrors.subject }
										</p>
									) }
								</div>

								{ /* Message - WYSIWYG Editor */ }
								<div>
									<Label htmlFor="body">{ i18n.message || __( 'Message', 'recruiting-playbook' ) }</Label>
									<RichTextEditor
										value={ formData.body }
										onChange={ ( value ) => updateField( 'body', value ) }
										placeholder={ i18n.messagePlaceholder || __( 'Enter message...', 'recruiting-playbook' ) }
										style={ validationErrors.body ? { borderColor: '#d63638' } : {} }
									/>
									{ validationErrors.body && (
										<p style={ { color: '#d63638', fontSize: '0.75rem', marginTop: '0.25rem' } }>
											{ validationErrors.body }
										</p>
									) }
								</div>
							</div>

							{ /* Sidebar with placeholders */ }
							<PlaceholderPicker placeholders={ placeholders } />
						</div>
					) : (
						<EmailPreview
							subject={ getPreviewText( formData.subject ) }
							body={ getPreviewText( formData.body ) }
							recipient={ formData.to }
						/>
					) }

					{ /* Actions */ }
					<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.75rem', marginTop: '1.5rem', paddingTop: '1.5rem', borderTop: '1px solid #e5e7eb' } }>
						<Button variant="outline" onClick={ onCancel } disabled={ sending }>
							{ i18n.cancel || __( 'Cancel', 'recruiting-playbook' ) }
						</Button>
						<Button onClick={ handleSend } disabled={ sending }>
							{ sending ? (
								<>
									<Spinner size="sm" style={ { marginRight: '0.5rem' } } />
									{ i18n.sending || __( 'Sending...', 'recruiting-playbook' ) }
								</>
							) : (
								i18n.send || __( 'Send', 'recruiting-playbook' )
							) }
						</Button>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

EmailComposer.propTypes = {
	templates: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.number.isRequired,
			name: PropTypes.string,
			subject: PropTypes.string,
			body: PropTypes.string,
		} )
	),
	signatures: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.number.isRequired,
			name: PropTypes.string,
			is_default: PropTypes.bool,
		} )
	),
	placeholders: PropTypes.object,
	previewValues: PropTypes.object,
	recipient: PropTypes.shape( {
		email: PropTypes.string,
		name: PropTypes.string,
	} ),
	applicationId: PropTypes.number,
	sending: PropTypes.bool,
	error: PropTypes.string,
	onSend: PropTypes.func.isRequired,
	onCancel: PropTypes.func.isRequired,
};

/**
 * EmailComposer - Komponente zum Verfassen von E-Mails
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Calendar } from 'lucide-react';
import { DateTimePicker, Popover } from '@wordpress/components';

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
 * EmailComposer Komponente
 *
 * @param {Object}   props               Props
 * @param {Array}    props.templates     Verfügbare Templates
 * @param {Object}   props.placeholders  Verfügbare Platzhalter
 * @param {Object}   props.previewValues Preview-Werte für Platzhalter
 * @param {Object}   props.recipient     Empfänger-Daten
 * @param {number}   props.applicationId Bewerbungs-ID
 * @param {boolean}  props.sending       Sende-Status
 * @param {string}   props.error         Fehlermeldung
 * @param {Function} props.onSend        Callback beim Senden
 * @param {Function} props.onCancel      Callback beim Abbrechen
 * @return {JSX.Element} Komponente
 */
export function EmailComposer( {
	templates = [],
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
	const [ formData, setFormData ] = useState( {
		to: recipient.email || '',
		subject: '',
		body: '',
	} );
	const [ scheduleEnabled, setScheduleEnabled ] = useState( false );
	const [ scheduledAt, setScheduledAt ] = useState( null );
	const [ showSchedulePicker, setShowSchedulePicker ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( 'compose' );
	const [ validationErrors, setValidationErrors ] = useState( {} );

	const i18n = window.rpEmailData?.i18n || {};

	// Empfänger-E-Mail aktualisieren wenn sich recipient ändert
	useEffect( () => {
		if ( recipient.email ) {
			setFormData( ( prev ) => ( { ...prev, to: recipient.email } ) );
		}
	}, [ recipient.email ] );

	/**
	 * Template auswählen und Felder füllen
	 *
	 * @param {string} templateId Template-ID
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
	 * Feld aktualisieren
	 *
	 * @param {string} field Feldname
	 * @param {*}      value Wert
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
	 * Platzhalter ersetzen (für Vorschau)
	 *
	 * @param {string} text Text
	 * @return {string} Text mit ersetzten Platzhaltern
	 */
	const getPreviewText = useCallback( ( text ) => {
		return replacePlaceholders( text, previewValues );
	}, [ previewValues ] );

	/**
	 * Formular validieren
	 *
	 * @return {boolean} Gültig
	 */
	const validate = useCallback( () => {
		const errors = {};

		const MAX_SUBJECT_LENGTH = 255;
		const MAX_BODY_LENGTH = 50000;
		const MAX_SCHEDULE_DAYS = 365;

		const emailRegex = /^[a-zA-Z0-9._%+-]{1,64}@[a-zA-Z0-9.-]{1,255}\.[a-zA-Z]{2,}$/;

		if ( ! formData.to.trim() ) {
			errors.to = i18n.recipientRequired || __( 'Empfänger ist erforderlich', 'recruiting-playbook' );
		} else if ( ! emailRegex.test( formData.to.trim() ) ) {
			errors.to = i18n.invalidEmail || __( 'Ungültige E-Mail-Adresse', 'recruiting-playbook' );
		}

		if ( ! formData.subject.trim() ) {
			errors.subject = i18n.subjectRequired || __( 'Betreff ist erforderlich', 'recruiting-playbook' );
		} else if ( formData.subject.length > MAX_SUBJECT_LENGTH ) {
			errors.subject = `${ i18n.subjectTooLong || __( 'Betreff zu lang', 'recruiting-playbook' ) } (max. ${ MAX_SUBJECT_LENGTH })`;
		}

		if ( ! formData.body.trim() ) {
			errors.body = i18n.bodyRequired || __( 'Inhalt ist erforderlich', 'recruiting-playbook' );
		} else if ( formData.body.length > MAX_BODY_LENGTH ) {
			errors.body = `${ i18n.bodyTooLong || __( 'Inhalt zu lang', 'recruiting-playbook' ) } (max. ${ MAX_BODY_LENGTH })`;
		}

		if ( scheduleEnabled ) {
			if ( ! scheduledAt ) {
				errors.scheduledAt = i18n.scheduleRequired || __( 'Sendezeitpunkt ist erforderlich', 'recruiting-playbook' );
			} else {
				const scheduleDate = new Date( scheduledAt );
				const now = new Date();
				const maxDate = new Date();
				maxDate.setDate( maxDate.getDate() + MAX_SCHEDULE_DAYS );

				if ( scheduleDate <= now ) {
					errors.scheduledAt = i18n.schedulePastError || __( 'Sendezeitpunkt muss in der Zukunft liegen', 'recruiting-playbook' );
				} else if ( scheduleDate > maxDate ) {
					errors.scheduledAt = `${ i18n.scheduleTooFar || __( 'Sendezeitpunkt zu weit in der Zukunft', 'recruiting-playbook' ) } (max. ${ MAX_SCHEDULE_DAYS } Tage)`;
				}
			}
		}

		setValidationErrors( errors );
		return Object.keys( errors ).length === 0;
	}, [ formData, scheduleEnabled, scheduledAt, i18n ] );

	/**
	 * E-Mail senden
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
		};

		if ( scheduleEnabled && scheduledAt ) {
			emailData.scheduled_at = scheduledAt;
		}

		if ( onSend ) {
			onSend( emailData );
		}
	}, [ formData, selectedTemplate, applicationId, scheduleEnabled, scheduledAt, validate, onSend ] );

	/**
	 * Sendezeitpunkt formatieren
	 *
	 * @param {string} date ISO-Datum
	 * @return {string} Formatiertes Datum
	 */
	const formatScheduledDate = ( date ) => {
		if ( ! date ) {
			return '';
		}

		return new Date( date ).toLocaleString( 'de-DE', {
			dateStyle: 'medium',
			timeStyle: 'short',
		} );
	};

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
						<CardTitle>{ i18n.composeEmail || __( 'E-Mail verfassen', 'recruiting-playbook' ) }</CardTitle>
						<div style={ { display: 'flex', gap: '0.5rem' } }>
							<Button
								variant={ activeTab === 'compose' ? 'default' : 'outline' }
								size="sm"
								onClick={ () => setActiveTab( 'compose' ) }
							>
								{ i18n.compose || __( 'Verfassen', 'recruiting-playbook' ) }
							</Button>
							<Button
								variant={ activeTab === 'preview' ? 'default' : 'outline' }
								size="sm"
								onClick={ () => setActiveTab( 'preview' ) }
							>
								{ i18n.preview || __( 'Vorschau', 'recruiting-playbook' ) }
							</Button>
						</div>
					</div>
				</CardHeader>

				<CardContent>
					{ activeTab === 'compose' ? (
						<div style={ { display: 'grid', gridTemplateColumns: '1fr 280px', gap: '1.5rem' } }>
							<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
								{ /* Template */ }
								<div>
									<Label htmlFor="template">{ i18n.template || __( 'Template', 'recruiting-playbook' ) }</Label>
									<Select
										id="template"
										value={ selectedTemplate }
										onChange={ ( e ) => handleTemplateSelect( e.target.value ) }
									>
										<option value="">{ i18n.selectTemplate || __( '-- Template auswählen --', 'recruiting-playbook' ) }</option>
										{ templates.map( ( t ) => (
											<option key={ t.id } value={ String( t.id ) }>{ t.name }</option>
										) ) }
									</Select>
								</div>

								{ /* Empfänger */ }
								<div>
									<Label htmlFor="recipient">{ i18n.recipient || __( 'Empfänger', 'recruiting-playbook' ) }</Label>
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

								{ /* Betreff */ }
								<div>
									<Label htmlFor="subject">{ i18n.subject || __( 'Betreff', 'recruiting-playbook' ) }</Label>
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

								{ /* Nachricht - WYSIWYG Editor */ }
								<div>
									<Label htmlFor="body">{ i18n.message || __( 'Nachricht', 'recruiting-playbook' ) }</Label>
									<RichTextEditor
										value={ formData.body }
										onChange={ ( value ) => updateField( 'body', value ) }
										placeholder={ i18n.messagePlaceholder || __( 'Nachricht eingeben...', 'recruiting-playbook' ) }
										style={ validationErrors.body ? { borderColor: '#d63638' } : {} }
									/>
									{ validationErrors.body && (
										<p style={ { color: '#d63638', fontSize: '0.75rem', marginTop: '0.25rem' } }>
											{ validationErrors.body }
										</p>
									) }
								</div>

								{ /* Zeitversetzt senden */ }
								<div style={ { display: 'flex', alignItems: 'center', gap: '0.75rem', paddingTop: '0.5rem' } }>
									<Switch
										id="schedule"
										checked={ scheduleEnabled }
										onCheckedChange={ setScheduleEnabled }
									/>
									<Label htmlFor="schedule" style={ { marginBottom: 0, cursor: 'pointer' } }>
										{ i18n.scheduleEmail || __( 'E-Mail zeitversetzt senden', 'recruiting-playbook' ) }
									</Label>
								</div>

								{ scheduleEnabled && (
									<div style={ { marginLeft: '3rem' } }>
										<Button
											variant="outline"
											onClick={ () => setShowSchedulePicker( ! showSchedulePicker ) }
											style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }
										>
											<Calendar style={ { width: '1rem', height: '1rem' } } />
											{ scheduledAt
												? formatScheduledDate( scheduledAt )
												: ( i18n.selectDateTime || __( 'Zeitpunkt wählen', 'recruiting-playbook' ) )
											}
										</Button>

										{ showSchedulePicker && (
											<Popover
												onClose={ () => setShowSchedulePicker( false ) }
												placement="bottom-start"
											>
												<DateTimePicker
													currentDate={ scheduledAt }
													onChange={ ( date ) => {
														setScheduledAt( date );
														setShowSchedulePicker( false );
													} }
													is12Hour={ false }
												/>
											</Popover>
										) }

										{ validationErrors.scheduledAt && (
											<p style={ { color: '#d63638', fontSize: '0.75rem', marginTop: '0.5rem' } }>
												{ validationErrors.scheduledAt }
											</p>
										) }
									</div>
								) }
							</div>

							{ /* Sidebar mit Platzhaltern */ }
							<PlaceholderPicker placeholders={ placeholders } />
						</div>
					) : (
						<EmailPreview
							subject={ getPreviewText( formData.subject ) }
							body={ getPreviewText( formData.body ) }
							recipient={ formData.to }
						/>
					) }

					{ /* Aktionen */ }
					<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.75rem', marginTop: '1.5rem', paddingTop: '1.5rem', borderTop: '1px solid #e5e7eb' } }>
						<Button variant="outline" onClick={ onCancel } disabled={ sending }>
							{ i18n.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
						</Button>
						<Button onClick={ handleSend } disabled={ sending }>
							{ sending ? (
								<>
									<Spinner size="sm" style={ { marginRight: '0.5rem' } } />
									{ i18n.sending || __( 'Senden...', 'recruiting-playbook' ) }
								</>
							) : scheduleEnabled ? (
								i18n.scheduleEmail || __( 'Planen', 'recruiting-playbook' )
							) : (
								i18n.send || __( 'Senden', 'recruiting-playbook' )
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

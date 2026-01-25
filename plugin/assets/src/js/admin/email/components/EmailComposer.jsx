/**
 * EmailComposer - Komponente zum Verfassen von E-Mails
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect, useMemo } from '@wordpress/element';
import PropTypes from 'prop-types';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	TextControl,
	TextareaControl,
	SelectControl,
	ToggleControl,
	Notice,
	Spinner,
	Flex,
	FlexItem,
	DateTimePicker,
	Popover,
} from '@wordpress/components';
import { calendar } from '@wordpress/icons';
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

	// Template-Optionen (mit useMemo für Performance)
	const selectTemplateLabel = i18n.selectTemplate || '-- Template auswählen --';
	const templateOptions = useMemo( () => [
		{ value: '', label: selectTemplateLabel },
		...templates.map( ( t ) => ( { value: String( t.id ), label: t.name } ) ),
	], [ templates, selectTemplateLabel ] );

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
				body: template.body || '',
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

		// Validierungsfehler entfernen (ohne validationErrors als Dependency)
		setValidationErrors( ( prev ) => {
			if ( ! prev[ field ] ) {
				return prev; // Keine Änderung nötig
			}
			const newErrors = { ...prev };
			delete newErrors[ field ];
			return newErrors;
		} );
	}, [] ); // Keine Dependencies - verwendet nur setState callbacks

	/**
	 * Platzhalter in Feld einfügen
	 *
	 * @param {string} placeholder Platzhalter
	 * @param {string} field       Zielfeld
	 */
	const insertPlaceholder = useCallback( ( placeholder, field = 'body' ) => {
		const placeholderText = `{${ placeholder }}`;

		setFormData( ( prev ) => {
			const currentValue = prev[ field ] || '';
			return {
				...prev,
				[ field ]: currentValue + placeholderText,
			};
		} );
	}, [] );

	/**
	 * Platzhalter ersetzen (für Vorschau)
	 * Verwendet die zentrale Utility-Funktion mit XSS-Schutz.
	 *
	 * @param {string} text Text
	 * @return {string} Text mit ersetzten Platzhaltern (HTML-escaped)
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

		// Konstanten für Längen-Limits
		const MAX_SUBJECT_LENGTH = 255;
		const MAX_BODY_LENGTH = 50000;
		const MAX_SCHEDULE_DAYS = 365;

		// E-Mail-Validierung mit ReDoS-sicherer Regex (Längenlimits)
		// Local part: max 64 Zeichen, Domain: max 255 Zeichen
		const emailRegex = /^[a-zA-Z0-9._%+-]{1,64}@[a-zA-Z0-9.-]{1,255}\.[a-zA-Z]{2,}$/;

		if ( ! formData.to.trim() ) {
			errors.to = i18n.recipientRequired || 'Empfänger ist erforderlich';
		} else if ( ! emailRegex.test( formData.to.trim() ) ) {
			errors.to = i18n.invalidEmail || 'Ungültige E-Mail-Adresse';
		}

		if ( ! formData.subject.trim() ) {
			errors.subject = i18n.subjectRequired || 'Betreff ist erforderlich';
		} else if ( formData.subject.length > MAX_SUBJECT_LENGTH ) {
			errors.subject = `${ i18n.subjectTooLong || 'Betreff zu lang' } (max. ${ MAX_SUBJECT_LENGTH })`;
		}

		if ( ! formData.body.trim() ) {
			errors.body = i18n.bodyRequired || 'Inhalt ist erforderlich';
		} else if ( formData.body.length > MAX_BODY_LENGTH ) {
			errors.body = `${ i18n.bodyTooLong || 'Inhalt zu lang' } (max. ${ MAX_BODY_LENGTH })`;
		}

		// Schedule-Validierung
		if ( scheduleEnabled ) {
			if ( ! scheduledAt ) {
				errors.scheduledAt = i18n.scheduleRequired || 'Sendezeitpunkt ist erforderlich';
			} else {
				const scheduleDate = new Date( scheduledAt );
				const now = new Date();
				const maxDate = new Date();
				maxDate.setDate( maxDate.getDate() + MAX_SCHEDULE_DAYS );

				if ( scheduleDate <= now ) {
					errors.scheduledAt = i18n.schedulePastError || 'Sendezeitpunkt muss in der Zukunft liegen';
				} else if ( scheduleDate > maxDate ) {
					errors.scheduledAt = `${ i18n.scheduleTooFar || 'Sendezeitpunkt zu weit in der Zukunft' } (max. ${ MAX_SCHEDULE_DAYS } Tage)`;
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
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<Flex>
						<FlexItem>
							<h2>{ i18n.composeEmail || 'E-Mail verfassen' }</h2>
						</FlexItem>
						<FlexItem>
							<div className="rp-email-composer__tabs">
								<Button
									variant={ activeTab === 'compose' ? 'primary' : 'secondary' }
									onClick={ () => setActiveTab( 'compose' ) }
								>
									{ i18n.compose || 'Verfassen' }
								</Button>
								<Button
									variant={ activeTab === 'preview' ? 'primary' : 'secondary' }
									onClick={ () => setActiveTab( 'preview' ) }
								>
									{ i18n.preview || 'Vorschau' }
								</Button>
							</div>
						</FlexItem>
					</Flex>
				</CardHeader>

				<CardBody>
					{ activeTab === 'compose' ? (
						<div className="rp-email-composer__form">
							<div className="rp-email-composer__main">
								<SelectControl
									label={ i18n.template || 'Template' }
									value={ selectedTemplate }
									options={ templateOptions }
									onChange={ handleTemplateSelect }
								/>

								<TextControl
									label={ i18n.recipient || 'Empfänger' }
									type="email"
									value={ formData.to }
									onChange={ ( value ) => updateField( 'to', value ) }
									help={ validationErrors.to }
									className={ validationErrors.to ? 'has-error' : '' }
								/>

								<div className="rp-email-composer__subject-row">
									<TextControl
										label={ i18n.subject || 'Betreff' }
										value={ formData.subject }
										onChange={ ( value ) => updateField( 'subject', value ) }
										help={ validationErrors.subject }
										className={ validationErrors.subject ? 'has-error' : '' }
									/>
									<PlaceholderPicker
										placeholders={ placeholders }
										onSelect={ ( ph ) => insertPlaceholder( ph, 'subject' ) }
										buttonLabel={ i18n.insertPlaceholder || 'Platzhalter' }
										compact
									/>
								</div>

								<div className="rp-email-composer__body-row">
									<TextareaControl
										label={ i18n.message || 'Nachricht' }
										value={ formData.body }
										onChange={ ( value ) => updateField( 'body', value ) }
										rows={ 12 }
										help={ validationErrors.body }
										className={ validationErrors.body ? 'has-error' : '' }
									/>
								</div>

								<div className="rp-email-composer__schedule">
									<ToggleControl
										label={ i18n.scheduleEmail || 'E-Mail zeitversetzt senden' }
										checked={ scheduleEnabled }
										onChange={ setScheduleEnabled }
									/>

									{ scheduleEnabled && (
										<div className="rp-email-composer__schedule-picker">
											<Button
												icon={ calendar }
												variant="secondary"
												onClick={ () => setShowSchedulePicker( ! showSchedulePicker ) }
											>
												{ scheduledAt
													? formatScheduledDate( scheduledAt )
													: ( i18n.selectDateTime || 'Zeitpunkt wählen' )
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
												<p className="rp-email-composer__error">
													{ validationErrors.scheduledAt }
												</p>
											) }
										</div>
									) }
								</div>
							</div>

							<div className="rp-email-composer__sidebar">
								<PlaceholderPicker
									placeholders={ placeholders }
									onSelect={ ( ph ) => insertPlaceholder( ph, 'body' ) }
									showSearch
								/>
							</div>
						</div>
					) : (
						<EmailPreview
							subject={ getPreviewText( formData.subject ) }
							body={ getPreviewText( formData.body ) }
							recipient={ formData.to }
						/>
					) }

					<div className="rp-email-composer__actions">
						<Button variant="secondary" onClick={ onCancel } disabled={ sending }>
							{ i18n.cancel || 'Abbrechen' }
						</Button>
						<Button variant="primary" onClick={ handleSend } disabled={ sending }>
							{ sending ? (
								<>
									<Spinner />
									{ i18n.sending || 'Senden...' }
								</>
							) : scheduleEnabled ? (
								i18n.scheduleEmail || 'Planen'
							) : (
								i18n.send || 'Senden'
							) }
						</Button>
					</div>
				</CardBody>
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

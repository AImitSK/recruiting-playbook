/**
 * EmailComposer - Komponente zum Verfassen von E-Mails
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
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

	// Template-Optionen
	const templateOptions = [
		{ value: '', label: i18n.selectTemplate || '-- Template auswählen --' },
		...templates.map( ( t ) => ( { value: String( t.id ), label: t.name } ) ),
	];

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

		// Validierungsfehler entfernen
		if ( validationErrors[ field ] ) {
			setValidationErrors( ( prev ) => {
				const newErrors = { ...prev };
				delete newErrors[ field ];
				return newErrors;
			} );
		}
	}, [ validationErrors ] );

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
	 *
	 * @param {string} text Text
	 * @return {string} Text mit ersetzten Platzhaltern
	 */
	const replacePlaceholders = useCallback( ( text ) => {
		if ( ! text ) {
			return '';
		}

		let result = text;

		Object.entries( previewValues ).forEach( ( [ key, value ] ) => {
			const regex = new RegExp( `\\{${ key }\\}`, 'g' );
			result = result.replace( regex, value );
		} );

		return result;
	}, [ previewValues ] );

	/**
	 * Formular validieren
	 *
	 * @return {boolean} Gültig
	 */
	const validate = useCallback( () => {
		const errors = {};

		if ( ! formData.to.trim() ) {
			errors.to = i18n.recipientRequired || 'Empfänger ist erforderlich';
		} else if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( formData.to ) ) {
			errors.to = i18n.invalidEmail || 'Ungültige E-Mail-Adresse';
		}

		if ( ! formData.subject.trim() ) {
			errors.subject = i18n.subjectRequired || 'Betreff ist erforderlich';
		}

		if ( ! formData.body.trim() ) {
			errors.body = i18n.bodyRequired || 'Inhalt ist erforderlich';
		}

		if ( scheduleEnabled && ! scheduledAt ) {
			errors.scheduledAt = i18n.scheduleRequired || 'Sendezeitpunkt ist erforderlich';
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
							subject={ replacePlaceholders( formData.subject ) }
							body={ replacePlaceholders( formData.body ) }
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

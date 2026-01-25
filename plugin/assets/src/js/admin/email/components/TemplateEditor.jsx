/**
 * TemplateEditor - Editor für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
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
} from '@wordpress/components';
import { PlaceholderPicker } from './PlaceholderPicker';
import { EmailPreview } from './EmailPreview';

/**
 * TemplateEditor Komponente
 *
 * @param {Object}   props              Props
 * @param {Object}   props.template     Template-Daten (null für neues Template)
 * @param {Object}   props.placeholders Verfügbare Platzhalter
 * @param {Object}   props.previewValues Preview-Werte für Platzhalter
 * @param {boolean}  props.saving       Speicher-Status
 * @param {string}   props.error        Fehlermeldung
 * @param {Function} props.onSave       Callback beim Speichern
 * @param {Function} props.onCancel     Callback beim Abbrechen
 * @return {JSX.Element} Komponente
 */
export function TemplateEditor( {
	template = null,
	placeholders = {},
	previewValues = {},
	saving = false,
	error = null,
	onSave,
	onCancel,
} ) {
	const [ formData, setFormData ] = useState( {
		name: '',
		subject: '',
		body: '',
		category: 'custom',
		is_active: true,
	} );
	const [ activeTab, setActiveTab ] = useState( 'edit' );
	const [ validationErrors, setValidationErrors ] = useState( {} );

	const i18n = window.rpEmailData?.i18n || {};
	const categories = i18n.categories || {};
	const isNew = ! template?.id;
	const isSystem = template?.is_system || false;

	// Kategorie-Optionen (ohne System für neue Templates)
	const categoryOptions = Object.entries( categories )
		.filter( ( [ value ] ) => isNew ? value !== 'system' : true )
		.map( ( [ value, label ] ) => ( { value, label } ) );

	// Template-Daten laden
	useEffect( () => {
		if ( template ) {
			setFormData( {
				name: template.name || '',
				subject: template.subject || '',
				body: template.body || '',
				category: template.category || 'custom',
				is_active: template.is_active !== false,
			} );
		} else {
			setFormData( {
				name: '',
				subject: '',
				body: '',
				category: 'custom',
				is_active: true,
			} );
		}
		setValidationErrors( {} );
	}, [ template ] );

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
	 * @param {string} field       Zielfeld ('subject' oder 'body')
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
	 * Formular validieren
	 *
	 * @return {boolean} Gültig
	 */
	const validate = useCallback( () => {
		const errors = {};

		if ( ! formData.name.trim() ) {
			errors.name = i18n.nameRequired || 'Name ist erforderlich';
		}

		if ( ! formData.subject.trim() ) {
			errors.subject = i18n.subjectRequired || 'Betreff ist erforderlich';
		}

		if ( ! formData.body.trim() ) {
			errors.body = i18n.bodyRequired || 'Inhalt ist erforderlich';
		}

		setValidationErrors( errors );
		return Object.keys( errors ).length === 0;
	}, [ formData, i18n ] );

	/**
	 * Speichern
	 */
	const handleSave = useCallback( () => {
		if ( ! validate() ) {
			return;
		}

		if ( onSave ) {
			onSave( formData );
		}
	}, [ formData, validate, onSave ] );

	/**
	 * Platzhalter im Text ersetzen (für Vorschau)
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

	return (
		<div className="rp-template-editor">
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<Flex>
						<FlexItem>
							<h2>
								{ isNew
									? ( i18n.newTemplate || 'Neues Template' )
									: ( i18n.editTemplate || 'Template bearbeiten' )
								}
							</h2>
						</FlexItem>
						<FlexItem>
							<div className="rp-template-editor__tabs">
								<Button
									variant={ activeTab === 'edit' ? 'primary' : 'secondary' }
									onClick={ () => setActiveTab( 'edit' ) }
								>
									{ i18n.edit || 'Bearbeiten' }
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
					{ activeTab === 'edit' ? (
						<div className="rp-template-editor__form">
							<div className="rp-template-editor__main">
								<TextControl
									label={ i18n.name || 'Name' }
									value={ formData.name }
									onChange={ ( value ) => updateField( 'name', value ) }
									disabled={ isSystem }
									help={ validationErrors.name }
									className={ validationErrors.name ? 'has-error' : '' }
								/>

								<Flex>
									<FlexItem isBlock>
										<SelectControl
											label={ i18n.category || 'Kategorie' }
											value={ formData.category }
											options={ categoryOptions }
											onChange={ ( value ) => updateField( 'category', value ) }
											disabled={ isSystem }
										/>
									</FlexItem>
									<FlexItem>
										<ToggleControl
											label={ i18n.active || 'Aktiv' }
											checked={ formData.is_active }
											onChange={ ( value ) => updateField( 'is_active', value ) }
										/>
									</FlexItem>
								</Flex>

								<div className="rp-template-editor__subject-row">
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

								<div className="rp-template-editor__body-row">
									<TextareaControl
										label={ i18n.body || 'Inhalt' }
										value={ formData.body }
										onChange={ ( value ) => updateField( 'body', value ) }
										rows={ 15 }
										help={ validationErrors.body }
										className={ validationErrors.body ? 'has-error' : '' }
									/>
								</div>
							</div>

							<div className="rp-template-editor__sidebar">
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
						/>
					) }

					<div className="rp-template-editor__actions">
						<Button variant="secondary" onClick={ onCancel } disabled={ saving }>
							{ i18n.cancel || 'Abbrechen' }
						</Button>
						<Button variant="primary" onClick={ handleSave } disabled={ saving }>
							{ saving ? (
								<>
									<Spinner />
									{ i18n.saving || 'Speichern...' }
								</>
							) : (
								i18n.save || 'Speichern'
							) }
						</Button>
					</div>
				</CardBody>
			</Card>
		</div>
	);
}

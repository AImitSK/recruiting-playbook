/**
 * TemplateEditor - Editor für E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Button } from '../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Select, SelectOption } from '../../components/ui/select';
import { Switch } from '../../components/ui/switch';
import { Label } from '../../components/ui/label';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Spinner } from '../../components/ui/spinner';
import { RichTextEditor } from '../../components/ui/rich-text-editor';
import { PlaceholderPicker } from './PlaceholderPicker';
import { EmailPreview } from './EmailPreview';
import { replacePlaceholders } from '../utils';

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

		// Konstanten für Längen-Limits
		const MAX_NAME_LENGTH = 100;
		const MAX_SUBJECT_LENGTH = 255;
		const MAX_BODY_LENGTH = 50000;

		if ( ! formData.name.trim() ) {
			errors.name = i18n.nameRequired || 'Name ist erforderlich';
		} else if ( formData.name.length > MAX_NAME_LENGTH ) {
			errors.name = `${ i18n.nameTooLong || 'Name zu lang' } (max. ${ MAX_NAME_LENGTH })`;
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
	 * Verwendet die zentrale Utility-Funktion mit XSS-Schutz.
	 *
	 * @param {string} text Text
	 * @return {string} Text mit ersetzten Platzhaltern (HTML-escaped)
	 */
	const getPreviewText = useCallback( ( text ) => {
		return replacePlaceholders( text, previewValues );
	}, [ previewValues ] );

	return (
		<div className="rp-template-editor">
			{ error && (
				<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			<Card>
				<CardHeader>
					<div
						style={ {
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
							flexWrap: 'wrap',
							gap: '1rem',
						} }
					>
						<CardTitle>
							{ isNew
								? ( i18n.newTemplate || 'Neues Template' )
								: ( i18n.editTemplate || 'Template bearbeiten' )
							}
						</CardTitle>
						<div
							className="rp-template-editor__tabs"
							style={ { display: 'flex', gap: '0.25rem' } }
						>
							<Button
								variant={ activeTab === 'edit' ? 'default' : 'outline' }
								size="sm"
								onClick={ () => setActiveTab( 'edit' ) }
							>
								{ i18n.edit || 'Bearbeiten' }
							</Button>
							<Button
								variant={ activeTab === 'preview' ? 'default' : 'outline' }
								size="sm"
								onClick={ () => setActiveTab( 'preview' ) }
							>
								{ i18n.preview || 'Vorschau' }
							</Button>
						</div>
					</div>
				</CardHeader>

				<CardContent>
					{ activeTab === 'edit' ? (
						<div
							className="rp-template-editor__form"
							style={ {
								display: 'grid',
								gridTemplateColumns: '1fr 280px',
								gap: '1.5rem',
							} }
						>
							<div className="rp-template-editor__main">
								<div style={ { marginBottom: '1rem' } }>
									<Label htmlFor="template-name">{ i18n.name || 'Name' }</Label>
									<Input
										id="template-name"
										value={ formData.name }
										onChange={ ( e ) => updateField( 'name', e.target.value ) }
										disabled={ isSystem }
										style={ validationErrors.name ? { borderColor: '#dc2626' } : {} }
									/>
									{ validationErrors.name && (
										<p style={ { color: '#dc2626', fontSize: '0.875rem', marginTop: '0.25rem' } }>
											{ validationErrors.name }
										</p>
									) }
								</div>

								<div
									style={ {
										display: 'grid',
										gridTemplateColumns: '1fr auto',
										gap: '1rem',
										marginBottom: '1rem',
									} }
								>
									<div>
										<Label htmlFor="template-category">{ i18n.category || 'Kategorie' }</Label>
										<Select
											id="template-category"
											value={ formData.category }
											onChange={ ( e ) => updateField( 'category', e.target.value ) }
											disabled={ isSystem }
										>
											{ categoryOptions.map( ( { value, label } ) => (
												<SelectOption key={ value } value={ value }>{ label }</SelectOption>
											) ) }
										</Select>
									</div>
									<div style={ { display: 'flex', alignItems: 'flex-end', gap: '0.5rem', paddingBottom: '0.25rem' } }>
										<Switch
											id="template-active"
											checked={ formData.is_active }
											onCheckedChange={ ( value ) => updateField( 'is_active', value ) }
										/>
										<Label htmlFor="template-active" style={ { marginBottom: 0 } }>
											{ i18n.active || 'Aktiv' }
										</Label>
									</div>
								</div>

								<div className="rp-template-editor__subject-row" style={ { marginBottom: '1rem' } }>
									<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '0.5rem' } }>
										<Label htmlFor="template-subject" style={ { marginBottom: 0 } }>
											{ i18n.subject || 'Betreff' }
										</Label>
										<PlaceholderPicker
											placeholders={ placeholders }
											onSelect={ ( ph ) => insertPlaceholder( ph, 'subject' ) }
											buttonLabel={ i18n.insertPlaceholder || 'Platzhalter' }
											compact
										/>
									</div>
									<Input
										id="template-subject"
										value={ formData.subject }
										onChange={ ( e ) => updateField( 'subject', e.target.value ) }
										style={ validationErrors.subject ? { borderColor: '#dc2626' } : {} }
									/>
									{ validationErrors.subject && (
										<p style={ { color: '#dc2626', fontSize: '0.875rem', marginTop: '0.25rem' } }>
											{ validationErrors.subject }
										</p>
									) }
								</div>

								<div className="rp-template-editor__body-row">
									<Label htmlFor="template-body">{ i18n.body || 'Inhalt' }</Label>
									<RichTextEditor
										value={ formData.body }
										onChange={ ( value ) => updateField( 'body', value ) }
										placeholder={ i18n.bodyPlaceholder || 'Inhalt eingeben...' }
										minHeight="300px"
										style={ validationErrors.body ? { borderColor: '#dc2626' } : {} }
									/>
									{ validationErrors.body && (
										<p style={ { color: '#dc2626', fontSize: '0.875rem', marginTop: '0.25rem' } }>
											{ validationErrors.body }
										</p>
									) }
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
							subject={ getPreviewText( formData.subject ) }
							body={ getPreviewText( formData.body ) }
						/>
					) }

					<div
						className="rp-template-editor__actions"
						style={ {
							display: 'flex',
							justifyContent: 'flex-end',
							gap: '0.5rem',
							marginTop: '1.5rem',
							paddingTop: '1.5rem',
							borderTop: '1px solid #e5e7eb',
						} }
					>
						<Button variant="outline" onClick={ onCancel } disabled={ saving }>
							{ i18n.cancel || 'Abbrechen' }
						</Button>
						<Button onClick={ handleSave } disabled={ saving }>
							{ saving ? (
								<>
									<Spinner size="sm" style={ { marginRight: '0.5rem' } } />
									{ i18n.saving || 'Speichern...' }
								</>
							) : (
								i18n.save || 'Speichern'
							) }
						</Button>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

TemplateEditor.propTypes = {
	template: PropTypes.shape( {
		id: PropTypes.number,
		name: PropTypes.string,
		subject: PropTypes.string,
		body: PropTypes.string,
		category: PropTypes.string,
		is_active: PropTypes.bool,
		is_system: PropTypes.bool,
	} ),
	placeholders: PropTypes.object,
	previewValues: PropTypes.object,
	saving: PropTypes.bool,
	error: PropTypes.string,
	onSave: PropTypes.func.isRequired,
	onCancel: PropTypes.func.isRequired,
};

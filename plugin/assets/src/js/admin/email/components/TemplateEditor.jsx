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
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../../components/ui/tabs';
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
	const categories = i18n.categories || {
		application: 'Bewerbung',
		rejection: 'Absage',
		interview: 'Interview',
		offer: 'Angebot',
		custom: 'Benutzerdefiniert',
	};
	const isNew = ! template?.id;
	const isSystem = template?.is_system || false;

	// Template-Daten laden
	useEffect( () => {
		if ( template ) {
			setFormData( {
				name: template.name || '',
				subject: template.subject || '',
				body: template.body_html || template.body || '',
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
	 * Formular validieren
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
			onSave( {
				name: formData.name,
				subject: formData.subject,
				body_html: formData.body,
				category: formData.category,
				is_active: formData.is_active,
			} );
		}
	}, [ formData, validate, onSave ] );

	/**
	 * Platzhalter im Text ersetzen (für Vorschau)
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
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<CardTitle>
							{ isNew
								? ( i18n.newTemplate || 'Neues Template' )
								: ( i18n.editTemplate || 'Template bearbeiten' )
							}
						</CardTitle>
					</div>
				</CardHeader>

				<CardContent>
					<Tabs value={ activeTab } onValueChange={ setActiveTab }>
						<TabsList style={ { marginBottom: '1.5rem' } }>
							<TabsTrigger value="edit">
								{ i18n.edit || 'Bearbeiten' }
							</TabsTrigger>
							<TabsTrigger value="preview">
								{ i18n.preview || 'Vorschau' }
							</TabsTrigger>
						</TabsList>

						<TabsContent value="edit">
							<div style={ { display: 'grid', gridTemplateColumns: '1fr 280px', gap: '1.5rem' } }>
								{ /* Hauptbereich */ }
								<div>
									{ /* Name */ }
									<div style={ { marginBottom: '1rem' } }>
										<Label htmlFor="template-name" style={ { display: 'block', marginBottom: '0.5rem' } }>
											{ i18n.name || 'Name' }
										</Label>
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

									{ /* Kategorie + Aktiv */ }
									<div style={ { display: 'grid', gridTemplateColumns: '1fr auto', gap: '1rem', marginBottom: '1rem', alignItems: 'end' } }>
										<div>
											<Label htmlFor="template-category" style={ { display: 'block', marginBottom: '0.5rem' } }>
												{ i18n.category || 'Kategorie' }
											</Label>
											<Select
												id="template-category"
												value={ formData.category }
												onChange={ ( e ) => updateField( 'category', e.target.value ) }
												disabled={ isSystem }
												style={ { width: '100%' } }
											>
												{ Object.entries( categories ).map( ( [ value, label ] ) => (
													<SelectOption key={ value } value={ value }>{ label }</SelectOption>
												) ) }
											</Select>
										</div>
										<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', paddingBottom: '0.5rem' } }>
											<Switch
												id="template-active"
												checked={ formData.is_active }
												onCheckedChange={ ( value ) => updateField( 'is_active', value ) }
											/>
											<Label htmlFor="template-active" style={ { marginBottom: 0, cursor: 'pointer' } }>
												{ i18n.active || 'Aktiv' }
											</Label>
										</div>
									</div>

									{ /* Betreff */ }
									<div style={ { marginBottom: '1rem' } }>
										<Label htmlFor="template-subject" style={ { display: 'block', marginBottom: '0.5rem' } }>
											{ i18n.subject || 'Betreff' }
										</Label>
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

									{ /* Inhalt */ }
									<div>
										<Label htmlFor="template-body" style={ { display: 'block', marginBottom: '0.5rem' } }>
											{ i18n.body || 'Inhalt' }
										</Label>
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

								{ /* Sidebar: Platzhalter */ }
								<div>
									<PlaceholderPicker placeholders={ placeholders } />
								</div>
							</div>
						</TabsContent>

						<TabsContent value="preview">
							<EmailPreview
								subject={ getPreviewText( formData.subject ) }
								body={ getPreviewText( formData.body ) }
							/>
						</TabsContent>
					</Tabs>

					{ /* Actions */ }
					<div style={ {
						display: 'flex',
						justifyContent: 'flex-end',
						gap: '0.75rem',
						marginTop: '1.5rem',
						paddingTop: '1.5rem',
						borderTop: '1px solid #e5e7eb',
					} }>
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
		body_html: PropTypes.string,
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

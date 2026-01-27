/**
 * SignatureEditor - Editor für E-Mail-Signaturen
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Building2 } from 'lucide-react';
import { Button } from '../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Switch } from '../../components/ui/switch';
import { Label } from '../../components/ui/label';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Spinner } from '../../components/ui/spinner';
import { RichTextEditor } from '../../components/ui/rich-text-editor';

/**
 * SignatureEditor Komponente
 *
 * @param {Object}   props              Props
 * @param {Object}   props.signature    Signatur-Daten (null für neue Signatur)
 * @param {boolean}  props.isCompany    Firmen-Signatur?
 * @param {boolean}  props.saving       Speicher-Status
 * @param {string}   props.error        Fehlermeldung
 * @param {Function} props.onSave       Callback beim Speichern
 * @param {Function} props.onCancel     Callback beim Abbrechen
 * @param {Function} props.onPreview    Callback für Vorschau-Generierung
 * @return {JSX.Element} Komponente
 */
export function SignatureEditor( {
	signature = null,
	isCompany = false,
	saving = false,
	error = null,
	onSave,
	onCancel,
	onPreview,
} ) {
	const [ formData, setFormData ] = useState( {
		name: '',
		body: '',
		is_default: false,
	} );
	const [ activeTab, setActiveTab ] = useState( 'edit' );
	const [ validationErrors, setValidationErrors ] = useState( {} );
	const [ previewHtml, setPreviewHtml ] = useState( '' );
	const [ previewLoading, setPreviewLoading ] = useState( false );

	const i18n = window.rpEmailData?.i18n || {};
	const isNew = ! signature?.id;

	// Signatur-Daten laden
	useEffect( () => {
		if ( signature ) {
			setFormData( {
				name: signature.name || '',
				body: signature.body || '',
				is_default: signature.is_default || false,
			} );
		} else {
			setFormData( {
				name: '',
				body: '',
				is_default: false,
			} );
		}
		setValidationErrors( {} );
		setPreviewHtml( '' );
	}, [ signature ] );

	/**
	 * Feld aktualisieren
	 *
	 * @param {string} field Feldname
	 * @param {*}      value Wert
	 */
	const updateField = useCallback( ( field, value ) => {
		setFormData( ( prev ) => ( { ...prev, [ field ]: value } ) );

		// Validierungsfehler entfernen
		setValidationErrors( ( prev ) => {
			if ( ! prev[ field ] ) {
				return prev;
			}
			const newErrors = { ...prev };
			delete newErrors[ field ];
			return newErrors;
		} );

		// Vorschau zurücksetzen wenn Body geändert
		if ( field === 'body' ) {
			setPreviewHtml( '' );
		}
	}, [] );

	/**
	 * Formular validieren
	 *
	 * @return {boolean} Gültig
	 */
	const validate = useCallback( () => {
		const errors = {};

		const MAX_NAME_LENGTH = 100;
		const MAX_BODY_LENGTH = 10000;

		if ( ! isCompany && ! formData.name.trim() ) {
			errors.name = i18n.nameRequired || 'Name ist erforderlich';
		} else if ( formData.name.length > MAX_NAME_LENGTH ) {
			errors.name = `${ i18n.nameTooLong || 'Name zu lang' } (max. ${ MAX_NAME_LENGTH })`;
		}

		if ( ! formData.body.trim() ) {
			errors.body = i18n.bodyRequired || 'Signatur-Inhalt ist erforderlich';
		} else if ( formData.body.length > MAX_BODY_LENGTH ) {
			errors.body = `${ i18n.bodyTooLong || 'Inhalt zu lang' } (max. ${ MAX_BODY_LENGTH })`;
		}

		setValidationErrors( errors );
		return Object.keys( errors ).length === 0;
	}, [ formData, i18n, isCompany ] );

	/**
	 * Speichern
	 */
	const handleSave = useCallback( () => {
		if ( ! validate() ) {
			return;
		}

		if ( onSave ) {
			// Für Firmen-Signatur keinen Namen senden
			const dataToSave = isCompany
				? { body: formData.body }
				: formData;
			onSave( dataToSave );
		}
	}, [ formData, validate, onSave, isCompany ] );

	/**
	 * Vorschau generieren
	 */
	const generatePreview = useCallback( async () => {
		if ( ! onPreview || ! formData.body ) {
			return;
		}

		setPreviewLoading( true );
		try {
			const html = await onPreview( { body: formData.body } );
			setPreviewHtml( html || formData.body );
		} catch ( err ) {
			console.error( 'Preview error:', err );
			setPreviewHtml( formData.body );
		} finally {
			setPreviewLoading( false );
		}
	}, [ formData.body, onPreview ] );

	/**
	 * Tab wechseln
	 *
	 * @param {string} tab Tab-Name
	 */
	const handleTabChange = useCallback( ( tab ) => {
		setActiveTab( tab );
		if ( tab === 'preview' && ! previewHtml ) {
			generatePreview();
		}
	}, [ previewHtml, generatePreview ] );

	return (
		<div className="rp-signature-editor">
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
						<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
							{ isCompany && <Building2 style={ { width: '1.25rem', height: '1.25rem' } } /> }
							{ isCompany
								? ( i18n.editCompanySignature || 'Firmen-Signatur bearbeiten' )
								: isNew
									? ( i18n.newSignature || 'Neue Signatur' )
									: ( i18n.editSignature || 'Signatur bearbeiten' )
							}
						</CardTitle>
						<div
							className="rp-signature-editor__tabs"
							style={ { display: 'flex', gap: '0.25rem' } }
						>
							<Button
								variant={ activeTab === 'edit' ? 'default' : 'outline' }
								size="sm"
								onClick={ () => handleTabChange( 'edit' ) }
							>
								{ i18n.edit || 'Bearbeiten' }
							</Button>
							<Button
								variant={ activeTab === 'preview' ? 'default' : 'outline' }
								size="sm"
								onClick={ () => handleTabChange( 'preview' ) }
							>
								{ i18n.preview || 'Vorschau' }
							</Button>
						</div>
					</div>
				</CardHeader>

				<CardContent>
					{ activeTab === 'edit' ? (
						<div className="rp-signature-editor__form">
							{ ! isCompany && (
								<div style={ { marginBottom: '1rem' } }>
									<Label htmlFor="signature-name">{ i18n.name || 'Name' }</Label>
									<Input
										id="signature-name"
										value={ formData.name }
										onChange={ ( e ) => updateField( 'name', e.target.value ) }
										placeholder={ i18n.signatureNamePlaceholder || 'z.B. "Formelle Signatur" oder "Kurz-Signatur"' }
										style={ validationErrors.name ? { borderColor: '#dc2626' } : {} }
									/>
									{ validationErrors.name && (
										<p style={ { color: '#dc2626', fontSize: '0.875rem', marginTop: '0.25rem' } }>
											{ validationErrors.name }
										</p>
									) }
								</div>
							) }

							{ ! isCompany && (
								<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '1rem' } }>
									<Switch
										id="signature-default"
										checked={ formData.is_default }
										onCheckedChange={ ( value ) => updateField( 'is_default', value ) }
									/>
									<Label htmlFor="signature-default" style={ { marginBottom: 0 } }>
										{ i18n.setAsDefault || 'Als Standard-Signatur verwenden' }
									</Label>
								</div>
							) }

							<div className="rp-signature-editor__body-row">
								<Label htmlFor="signature-body">
									{ isCompany
										? ( i18n.companySignatureContent || 'Firmen-Signatur Inhalt' )
										: ( i18n.signatureContent || 'Signatur-Inhalt' )
									}
								</Label>
								<p style={ { color: '#6b7280', fontSize: '0.875rem', marginBottom: '0.5rem' } }>
									{ isCompany
										? ( i18n.companySignatureHint || 'Die Firmen-Signatur wird verwendet, wenn ein Benutzer keine eigene Signatur hat.' )
										: ( i18n.signatureHint || 'Gestalten Sie Ihre E-Mail-Signatur mit Ihren Kontaktdaten.' )
									}
								</p>
								<RichTextEditor
									value={ formData.body }
									onChange={ ( value ) => updateField( 'body', value ) }
									placeholder={ i18n.signaturePlaceholder || 'Mit freundlichen Grüßen,\n\nMax Mustermann\nPersonalabteilung\nTel: +49 123 456789' }
									minHeight="200px"
									style={ validationErrors.body ? { borderColor: '#dc2626' } : {} }
								/>
								{ validationErrors.body && (
									<p style={ { color: '#dc2626', fontSize: '0.875rem', marginTop: '0.25rem' } }>
										{ validationErrors.body }
									</p>
								) }
							</div>
						</div>
					) : (
						<div className="rp-signature-editor__preview">
							{ previewLoading ? (
								<div style={ { display: 'flex', justifyContent: 'center', padding: '2rem' } }>
									<Spinner />
								</div>
							) : (
								<div
									className="rp-signature-preview"
									style={ {
										padding: '1.5rem',
										backgroundColor: '#f9fafb',
										borderRadius: '0.5rem',
										border: '1px solid #e5e7eb',
									} }
								>
									<p style={ { color: '#6b7280', marginBottom: '1rem', fontStyle: 'italic' } }>
										{ i18n.signaturePreviewHint || 'So wird Ihre Signatur in E-Mails aussehen:' }
									</p>
									<div
										style={ {
											padding: '1rem',
											backgroundColor: '#ffffff',
											borderRadius: '0.25rem',
											borderLeft: '3px solid #1d71b8',
										} }
										dangerouslySetInnerHTML={ { __html: previewHtml || formData.body } }
									/>
								</div>
							) }
						</div>
					) }

					<div
						className="rp-signature-editor__actions"
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

SignatureEditor.propTypes = {
	signature: PropTypes.shape( {
		id: PropTypes.number,
		name: PropTypes.string,
		body: PropTypes.string,
		is_default: PropTypes.bool,
	} ),
	isCompany: PropTypes.bool,
	saving: PropTypes.bool,
	error: PropTypes.string,
	onSave: PropTypes.func.isRequired,
	onCancel: PropTypes.func.isRequired,
	onPreview: PropTypes.func,
};

/**
 * SignatureEditor - Editor für E-Mail-Signaturen
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Button } from '../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Switch } from '../../components/ui/switch';
import { Label } from '../../components/ui/label';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Spinner } from '../../components/ui/spinner';
import { RichTextEditor } from '../../components/ui/rich-text-editor';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../../components/ui/tabs';

/**
 * SignatureEditor Komponente
 *
 * @param {Object}   props              Props
 * @param {Object}   props.signature    Signatur-Daten (null für neue Signatur)
 * @param {boolean}  props.saving       Speicher-Status
 * @param {string}   props.error        Fehlermeldung
 * @param {Function} props.onSave       Callback beim Speichern
 * @param {Function} props.onCancel     Callback beim Abbrechen
 * @param {Function} props.onPreview    Callback für Vorschau-Generierung
 * @return {JSX.Element} Komponente
 */
export function SignatureEditor( {
	signature = null,
	saving = false,
	error = null,
	onSave,
	onCancel,
	onPreview,
} ) {
	const [ formData, setFormData ] = useState( {
		name: '',
		content: '',
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
				content: signature.content || '',
				is_default: signature.is_default || false,
			} );
		} else {
			setFormData( {
				name: '',
				content: '',
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
		if ( field === 'content' ) {
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

		if ( ! formData.name.trim() ) {
			errors.name = i18n.nameRequired || 'Name ist erforderlich';
		} else if ( formData.name.length > MAX_NAME_LENGTH ) {
			errors.name = `${ i18n.nameTooLong || 'Name zu lang' } (max. ${ MAX_NAME_LENGTH })`;
		}

		if ( ! formData.content.trim() ) {
			errors.content = i18n.contentRequired || 'Signatur-Inhalt ist erforderlich';
		} else if ( formData.content.length > MAX_BODY_LENGTH ) {
			errors.content = `${ i18n.contentTooLong || 'Inhalt zu lang' } (max. ${ MAX_BODY_LENGTH })`;
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
	 * Vorschau generieren
	 */
	const generatePreview = useCallback( async () => {
		if ( ! onPreview || ! formData.content ) {
			return;
		}

		setPreviewLoading( true );
		try {
			const html = await onPreview( { content: formData.content } );
			setPreviewHtml( html || formData.content );
		} catch ( err ) {
			console.error( 'Preview error:', err );
			setPreviewHtml( formData.content );
		} finally {
			setPreviewLoading( false );
		}
	}, [ formData.content, onPreview ] );

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

	// Tab-Inhalt rendern
	const renderEditContent = () => (
		<div className="rp-signature-editor__form">
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

			<div className="rp-signature-editor__content-row">
				<Label htmlFor="signature-content">
					{ i18n.signatureContent || 'Signatur-Inhalt' }
				</Label>
				<p style={ { color: '#6b7280', fontSize: '0.875rem', marginBottom: '0.5rem' } }>
					{ i18n.signatureHint || 'Gestalten Sie Ihre E-Mail-Signatur mit Ihren Kontaktdaten.' }
				</p>
				<RichTextEditor
					value={ formData.content }
					onChange={ ( value ) => updateField( 'content', value ) }
					placeholder={ i18n.signaturePlaceholder || 'Mit freundlichen Grüßen,\n\nMax Mustermann\nPersonalabteilung\nTel: +49 123 456789' }
					minHeight="200px"
					style={ validationErrors.content ? { borderColor: '#dc2626' } : {} }
				/>
				{ validationErrors.content && (
					<p style={ { color: '#dc2626', fontSize: '0.875rem', marginTop: '0.25rem' } }>
						{ validationErrors.content }
					</p>
				) }
			</div>
		</div>
	);

	const renderPreviewContent = () => (
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
						dangerouslySetInnerHTML={ { __html: previewHtml || formData.content } }
					/>
				</div>
			) }
		</div>
	);

	return (
		<div className="rp-signature-editor">
			{ error && (
				<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			<Card>
				<CardHeader>
					<CardTitle>
						{ isNew
							? ( i18n.newSignature || 'Neue Signatur' )
							: ( i18n.editSignature || 'Signatur bearbeiten' )
						}
					</CardTitle>
				</CardHeader>

				<CardContent>
					<Tabs value={ activeTab } onValueChange={ handleTabChange }>
						<TabsList style={ { marginBottom: '1.5rem' } }>
							<TabsTrigger value="edit">
								{ i18n.edit || 'Bearbeiten' }
							</TabsTrigger>
							<TabsTrigger value="preview">
								{ i18n.preview || 'Vorschau' }
							</TabsTrigger>
						</TabsList>

						<TabsContent value="edit">
							{ renderEditContent() }
						</TabsContent>

						<TabsContent value="preview">
							{ renderPreviewContent() }
						</TabsContent>
					</Tabs>

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
		content: PropTypes.string,
		is_default: PropTypes.bool,
	} ),
	saving: PropTypes.bool,
	error: PropTypes.string,
	onSave: PropTypes.func.isRequired,
	onCancel: PropTypes.func.isRequired,
	onPreview: PropTypes.func,
};

/**
 * Company Settings Component
 *
 * Firmendaten und Standard-Absender
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Switch } from '../../components/ui/switch';
import { Button } from '../../components/ui/button';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { AlertCircle } from 'lucide-react';

/**
 * CompanySettings Component
 *
 * @param {Object}   props            Component props
 * @param {Object}   props.settings   Current settings
 * @param {boolean}  props.saving     Whether currently saving
 * @param {string}   props.error      Error message
 * @param {Function} props.onUpdate   Update single setting
 * @param {Function} props.onSave     Save settings
 * @return {JSX.Element} Component
 */
export function CompanySettings( { settings, saving, error, onUpdate, onSave } ) {
	const i18n = window.rpSettingsData?.i18n || {};
	const isPro = window.rpSettingsData?.isPro || false;

	const handleSubmit = ( e ) => {
		e.preventDefault();
		const data = {
			company_name: settings?.company_name,
			company_street: settings?.company_street,
			company_zip: settings?.company_zip,
			company_city: settings?.company_city,
			company_phone: settings?.company_phone,
			company_website: settings?.company_website,
			company_email: settings?.company_email,
			sender_name: settings?.sender_name,
			sender_email: settings?.sender_email,
		};

		// Pro-Einstellungen nur senden wenn Pro aktiv.
		if ( isPro ) {
			data.hide_email_branding = settings?.hide_email_branding;
		}

		onSave( data );
	};

	if ( ! settings ) {
		return null;
	}

	return (
		<form onSubmit={ handleSubmit }>
			{ error && (
				<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
					<AlertCircle style={ { width: '1rem', height: '1rem' } } />
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			<div style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
				{ /* Firmendaten */ }
				<Card>
					<CardHeader>
						<CardTitle>{ i18n.companyData || __( 'Firmendaten', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ i18n.companyDataDesc || __( 'Diese Daten werden in E-Mail-Signaturen und im Google for Jobs Schema verwendet.', 'recruiting-playbook' ) }
						</CardDescription>
					</CardHeader>
					<CardContent>
						<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
							<div>
								<Label htmlFor="company_name">
									{ i18n.companyName || __( 'Firmenname', 'recruiting-playbook' ) }
									<span style={ { color: '#ef4444' } }> *</span>
								</Label>
								<Input
									id="company_name"
									type="text"
									value={ settings.company_name || '' }
									onChange={ ( e ) => onUpdate( 'company_name', e.target.value ) }
									required
								/>
								<p style={ { marginTop: '0.25rem', fontSize: '0.75rem', color: '#6b7280' } }>
									{ i18n.companyNameDesc || __( 'Wird im Schema, E-Mails und auf der Karriereseite angezeigt.', 'recruiting-playbook' ) }
								</p>
							</div>

							<div>
								<Label htmlFor="company_street">
									{ i18n.street || __( 'Straße & Hausnummer', 'recruiting-playbook' ) }
								</Label>
								<Input
									id="company_street"
									type="text"
									value={ settings.company_street || '' }
									onChange={ ( e ) => onUpdate( 'company_street', e.target.value ) }
								/>
							</div>

							<div style={ { display: 'grid', gridTemplateColumns: '100px 1fr', gap: '1rem' } }>
								<div>
									<Label htmlFor="company_zip">
										{ i18n.zip || __( 'PLZ', 'recruiting-playbook' ) }
									</Label>
									<Input
										id="company_zip"
										type="text"
										value={ settings.company_zip || '' }
										onChange={ ( e ) => onUpdate( 'company_zip', e.target.value ) }
									/>
								</div>
								<div>
									<Label htmlFor="company_city">
										{ i18n.city || __( 'Stadt', 'recruiting-playbook' ) }
									</Label>
									<Input
										id="company_city"
										type="text"
										value={ settings.company_city || '' }
										onChange={ ( e ) => onUpdate( 'company_city', e.target.value ) }
									/>
								</div>
							</div>

							<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' } }>
								<div>
									<Label htmlFor="company_phone">
										{ i18n.phone || __( 'Telefon', 'recruiting-playbook' ) }
									</Label>
									<Input
										id="company_phone"
										type="tel"
										value={ settings.company_phone || '' }
										onChange={ ( e ) => onUpdate( 'company_phone', e.target.value ) }
										placeholder="+49 123 456789"
									/>
								</div>
								<div>
									<Label htmlFor="company_website">
										{ i18n.website || __( 'Website', 'recruiting-playbook' ) }
									</Label>
									<Input
										id="company_website"
										type="url"
										value={ settings.company_website || '' }
										onChange={ ( e ) => onUpdate( 'company_website', e.target.value ) }
										placeholder="https://www.example.com"
									/>
								</div>
							</div>

							<div>
								<Label htmlFor="company_email">
									{ i18n.contactEmail || __( 'Kontakt-E-Mail', 'recruiting-playbook' ) }
									<span style={ { color: '#ef4444' } }> *</span>
								</Label>
								<Input
									id="company_email"
									type="email"
									value={ settings.company_email || '' }
									onChange={ ( e ) => onUpdate( 'company_email', e.target.value ) }
									required
								/>
								<p style={ { marginTop: '0.25rem', fontSize: '0.75rem', color: '#6b7280' } }>
									{ i18n.contactEmailDesc || __( 'Allgemeine Kontakt-E-Mail der Firma (für E-Mail-Signaturen).', 'recruiting-playbook' ) }
								</p>
							</div>
						</div>
					</CardContent>
				</Card>

				{ /* Standard-Absender */ }
				<Card>
					<CardHeader>
						<CardTitle>{ i18n.defaultSender || __( 'Standard-Absender', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ i18n.defaultSenderDesc || __( 'Standard-Absenderdaten für automatische und manuelle E-Mails.', 'recruiting-playbook' ) }
						</CardDescription>
					</CardHeader>
					<CardContent>
						<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' } }>
							<div>
								<Label htmlFor="sender_name">
									{ i18n.senderName || __( 'Absender-Name', 'recruiting-playbook' ) }
								</Label>
								<Input
									id="sender_name"
									type="text"
									value={ settings.sender_name || '' }
									onChange={ ( e ) => onUpdate( 'sender_name', e.target.value ) }
									placeholder={ i18n.hrDepartment || __( 'Personalabteilung', 'recruiting-playbook' ) }
								/>
								<p style={ { marginTop: '0.25rem', fontSize: '0.75rem', color: '#6b7280' } }>
									{ i18n.senderNameDesc || __( 'Name, der als Absender in E-Mails angezeigt wird.', 'recruiting-playbook' ) }
								</p>
							</div>
							<div>
								<Label htmlFor="sender_email">
									{ i18n.senderEmail || __( 'Absender-E-Mail', 'recruiting-playbook' ) }
								</Label>
								<Input
									id="sender_email"
									type="email"
									value={ settings.sender_email || '' }
									onChange={ ( e ) => onUpdate( 'sender_email', e.target.value ) }
									placeholder="jobs@example.com"
								/>
								<p style={ { marginTop: '0.25rem', fontSize: '0.75rem', color: '#6b7280' } }>
									{ i18n.senderEmailDesc || __( 'E-Mail-Adresse, von der E-Mails gesendet werden.', 'recruiting-playbook' ) }
								</p>
							</div>
						</div>
					</CardContent>
				</Card>

				{ /* Pro-Einstellungen (nur wenn Pro aktiv) */ }
				{ isPro && (
					<Card>
						<CardHeader>
							<CardTitle>{ i18n.proSettings || __( 'Pro-Einstellungen', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>
								{ i18n.proSettingsDesc || __( 'Erweiterte Einstellungen für Pro-Nutzer.', 'recruiting-playbook' ) }
							</CardDescription>
						</CardHeader>
						<CardContent>
							<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
								<div>
									<Label htmlFor="hide_email_branding" style={ { marginBottom: 0 } }>
										{ i18n.whiteLabel || __( 'White-Label E-Mails', 'recruiting-playbook' ) }
									</Label>
									<p style={ { marginTop: '0.25rem', fontSize: '0.75rem', color: '#6b7280' } }>
										{ i18n.whiteLabelDesc || __( '"Versand über Recruiting Playbook"-Hinweis in E-Mails ausblenden', 'recruiting-playbook' ) }
									</p>
								</div>
								<Switch
									id="hide_email_branding"
									checked={ settings.hide_email_branding ?? false }
									onCheckedChange={ ( checked ) => onUpdate( 'hide_email_branding', checked ) }
								/>
							</div>
						</CardContent>
					</Card>
				) }

				{ /* Speichern Button */ }
				<div style={ { display: 'flex', justifyContent: 'flex-end' } }>
					<Button type="submit" disabled={ saving }>
						{ saving
							? ( i18n.saving || __( 'Speichern...', 'recruiting-playbook' ) )
							: ( i18n.saveSettings || __( 'Einstellungen speichern', 'recruiting-playbook' ) )
						}
					</Button>
				</div>
			</div>
		</form>
	);
}

export default CompanySettings;

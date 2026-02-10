/**
 * General Settings Component
 *
 * Allgemeine Einstellungen (Benachrichtigungen, Jobs, Schema)
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
 * GeneralSettings Component
 *
 * @param {Object}   props               Component props
 * @param {Object}   props.settings      Current settings
 * @param {Array}    props.pages         Available pages for dropdown
 * @param {boolean}  props.saving        Whether currently saving
 * @param {string}   props.error         Error message
 * @param {Function} props.onUpdate      Update single setting
 * @param {Function} props.onSave        Save settings
 * @return {JSX.Element} Component
 */
export function GeneralSettings( { settings, pages, saving, error, onUpdate, onSave } ) {
	const i18n = window.rpSettingsData?.i18n || {};
	const homeUrl = window.rpSettingsData?.homeUrl || '';

	const handleSubmit = ( e ) => {
		e.preventDefault();
		onSave( {
			notification_email: settings?.notification_email,
			privacy_page_id: settings?.privacy_page_id,
			jobs_per_page: settings?.jobs_per_page,
			jobs_slug: settings?.jobs_slug,
		} );
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
				{ /* Benachrichtigungen */ }
				<Card>
					<CardHeader>
						<CardTitle>{ i18n.notifications || __( 'Benachrichtigungen', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ i18n.notificationsDesc || __( 'E-Mail-Benachrichtigungen für neue Bewerbungen', 'recruiting-playbook' ) }
						</CardDescription>
					</CardHeader>
					<CardContent>
						<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
							<div>
								<Label htmlFor="notification_email">
									{ i18n.notificationEmail || __( 'Benachrichtigungs-E-Mail', 'recruiting-playbook' ) }
								</Label>
								<Input
									id="notification_email"
									type="email"
									value={ settings.notification_email || '' }
									onChange={ ( e ) => onUpdate( 'notification_email', e.target.value ) }
									placeholder="jobs@example.com"
								/>
								<p style={ { marginTop: '0.25rem', fontSize: '0.75rem', color: '#6b7280' } }>
									{ i18n.notificationEmailDesc || __( 'E-Mail-Adresse für neue Bewerbungen.', 'recruiting-playbook' ) }
								</p>
							</div>

							<div>
								<Label htmlFor="privacy_page_id">
									{ i18n.privacyPage || __( 'Datenschutz-Seite', 'recruiting-playbook' ) }
								</Label>
								<select
									id="privacy_page_id"
									value={ settings.privacy_page_id || '' }
									onChange={ ( e ) => onUpdate( 'privacy_page_id', parseInt( e.target.value, 10 ) || 0 ) }
									style={ {
										display: 'block',
										width: '100%',
										height: '40px',
										padding: '0.5rem 0.75rem',
										fontSize: '0.875rem',
										color: '#18181b',
										backgroundColor: '#ffffff',
										border: '1px solid #d1d5db',
										borderRadius: '6px',
									} }
								>
									<option value="">{ i18n.selectPage || __( '— Seite auswählen —', 'recruiting-playbook' ) }</option>
									{ pages?.map( ( page ) => (
										<option key={ page.id } value={ page.id }>
											{ page.title }
										</option>
									) ) }
								</select>
								<p style={ { marginTop: '0.25rem', fontSize: '0.75rem', color: '#6b7280' } }>
									{ i18n.privacyPageDesc || __( 'Seite mit Datenschutzerklärung für das Bewerbungsformular.', 'recruiting-playbook' ) }
								</p>
							</div>
						</div>
					</CardContent>
				</Card>

				{ /* Stellenanzeigen */ }
				<Card>
					<CardHeader>
						<CardTitle>{ i18n.jobListings || __( 'Stellenanzeigen', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ i18n.jobListingsDesc || __( 'Einstellungen für Stellenanzeigen und die Karriereseite', 'recruiting-playbook' ) }
						</CardDescription>
					</CardHeader>
					<CardContent>
						<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
							<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' } }>
								<div>
									<Label htmlFor="jobs_per_page">
										{ i18n.jobsPerPage || __( 'Stellen pro Seite', 'recruiting-playbook' ) }
									</Label>
									<Input
										id="jobs_per_page"
										type="number"
										min="1"
										max="50"
										value={ settings.jobs_per_page || 10 }
										onChange={ ( e ) => onUpdate( 'jobs_per_page', parseInt( e.target.value, 10 ) || 10 ) }
										style={ { width: '100px' } }
									/>
								</div>

								<div>
									<Label htmlFor="jobs_slug">
										{ i18n.urlSlug || __( 'URL-Slug', 'recruiting-playbook' ) }
									</Label>
									<div style={ { display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
										<code style={ { fontSize: '0.75rem', color: '#6b7280' } }>{ homeUrl }/</code>
										<Input
											id="jobs_slug"
											type="text"
											value={ settings.jobs_slug || 'jobs' }
											onChange={ ( e ) => onUpdate( 'jobs_slug', e.target.value ) }
											pattern="[a-z0-9-]+"
											style={ { width: '120px' } }
										/>
										<code style={ { fontSize: '0.75rem', color: '#6b7280' } }>/</code>
									</div>
									<p style={ { marginTop: '0.25rem', fontSize: '0.75rem', color: '#6b7280' } }>
										{ i18n.urlSlugDesc || __( 'URL-Pfad für die Stellenübersicht.', 'recruiting-playbook' ) }
									</p>
								</div>
							</div>

						</div>
					</CardContent>
				</Card>

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

export default GeneralSettings;

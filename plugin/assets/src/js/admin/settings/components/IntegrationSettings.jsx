/**
 * IntegrationSettings Component
 *
 * Settings-Tab "Integrationen" mit Google for Jobs, XML Feed,
 * Slack, Teams und Kalender-Einstellungen.
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Badge } from '../../components/ui/badge';
import { Switch } from '../../components/ui/switch';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Select } from '../../components/ui/select';
import { Spinner } from '../../components/ui/spinner';

import { useIntegrations } from '../hooks';

/**
 * Checkbox-Zeile
 */
function CheckboxRow( { label, checked, onChange, disabled } ) {
	return (
		<label style={ {
			display: 'flex',
			alignItems: 'center',
			gap: '8px',
			cursor: disabled ? 'not-allowed' : 'pointer',
			opacity: disabled ? 0.5 : 1,
			padding: '4px 0',
		} }>
			<input
				type="checkbox"
				checked={ checked }
				onChange={ ( e ) => onChange( e.target.checked ) }
				disabled={ disabled }
				style={ { margin: 0 } }
			/>
			<span style={ { fontSize: '14px', color: '#374151' } }>{ label }</span>
		</label>
	);
}

/**
 * Kopier-Button fur URLs
 */
function CopyButton( { text } ) {
	const [ copied, setCopied ] = useState( false );

	const handleCopy = useCallback( () => {
		navigator.clipboard.writeText( text ).then( () => {
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		} );
	}, [ text ] );

	return (
		<Button
			variant="outline"
			size="sm"
			onClick={ handleCopy }
			style={ { whiteSpace: 'nowrap' } }
		>
			{ copied
				? __( 'Kopiert!', 'recruiting-playbook' )
				: __( 'Kopieren', 'recruiting-playbook' )
			}
		</Button>
	);
}

/**
 * Section-Divider fur optionale Felder
 */
function SettingsSection( { title, children } ) {
	return (
		<div style={ {
			marginTop: '16px',
			padding: '16px',
			backgroundColor: '#f9fafb',
			borderRadius: '8px',
			border: '1px solid #e5e7eb',
		} }>
			{ title && (
				<div style={ { fontSize: '13px', fontWeight: 600, color: '#6b7280', marginBottom: '12px', textTransform: 'uppercase', letterSpacing: '0.05em' } }>
					{ title }
				</div>
			) }
			{ children }
		</div>
	);
}

/**
 * IntegrationSettings Component
 *
 * @return {JSX.Element} Component
 */
export function IntegrationSettings() {
	const {
		settings,
		loading,
		saving,
		testing,
		testResult,
		error,
		setError,
		setTestResult,
		updateSetting,
		saveSettings,
		sendTestMessage,
	} = useIntegrations();

	const config = window.rpSettingsData || {};
	const isPro = config.isPro || false;
	const homeUrl = config.homeUrl || '';

	/**
	 * Speichern
	 */
	const handleSave = useCallback( async () => {
		setError( null );
		await saveSettings( settings );
	}, [ settings, saveSettings, setError ] );

	if ( loading ) {
		return (
			<div style={ { display: 'flex', justifyContent: 'center', padding: '3rem' } }>
				<Spinner size="lg" />
			</div>
		);
	}

	return (
		<div style={ { display: 'flex', flexDirection: 'column', gap: '24px' } }>
			{ /* Fehler */ }
			{ error && (
				<Alert variant="destructive">
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			{ /* Test-Ergebnis */ }
			{ testResult && (
				<Alert
					variant={ testResult.success ? 'default' : 'destructive' }
					style={ testResult.success ? { backgroundColor: '#e6f5ec', borderColor: '#2fac66' } : undefined }
				>
					<AlertDescription>
						{ testResult.success
							? __( 'Test-Nachricht erfolgreich gesendet!', 'recruiting-playbook' )
							: testResult.message
						}
					</AlertDescription>
				</Alert>
			) }

			{ /* ═══════════ Google for Jobs ═══════════ */ }
			<Card>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<div>
							<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
								{ __( 'Google for Jobs', 'recruiting-playbook' ) }
								<Badge style={ { backgroundColor: '#dcfce7', color: '#166534', border: 'none', fontSize: '11px' } }>
									Free
								</Badge>
							</CardTitle>
							<CardDescription>
								{ __( 'Stellen erscheinen automatisch in der Google-Jobsuche durch strukturierte JSON-LD Daten.', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Switch
							checked={ settings.google_jobs_enabled }
							onCheckedChange={ ( val ) => updateSetting( 'google_jobs_enabled', val ) }
						/>
					</div>
				</CardHeader>
				{ settings.google_jobs_enabled && (
					<CardContent>
						<div style={ { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '12px' } }>
							<div style={ {
								width: '8px',
								height: '8px',
								borderRadius: '50%',
								backgroundColor: '#22c55e',
							} } />
							<span style={ { fontSize: '14px', color: '#374151' } }>
								{ __( 'Aktiv – Schema-Markup wird auf allen Stellenseiten ausgegeben', 'recruiting-playbook' ) }
							</span>
						</div>

						<SettingsSection title={ __( 'Optionale Felder', 'recruiting-playbook' ) }>
							<CheckboxRow
								label={ __( 'Gehalt anzeigen (wenn vorhanden)', 'recruiting-playbook' ) }
								checked={ settings.google_jobs_show_salary }
								onChange={ ( val ) => updateSetting( 'google_jobs_show_salary', val ) }
							/>
							<CheckboxRow
								label={ __( 'Remote-Option kennzeichnen', 'recruiting-playbook' ) }
								checked={ settings.google_jobs_show_remote }
								onChange={ ( val ) => updateSetting( 'google_jobs_show_remote', val ) }
							/>
							<CheckboxRow
								label={ __( 'Bewerbungsfrist als validThrough setzen', 'recruiting-playbook' ) }
								checked={ settings.google_jobs_show_deadline }
								onChange={ ( val ) => updateSetting( 'google_jobs_show_deadline', val ) }
							/>
						</SettingsSection>
					</CardContent>
				) }
			</Card>

			{ /* ═══════════ XML Job Feed ═══════════ */ }
			<Card>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<div>
							<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
								{ __( 'XML Job Feed', 'recruiting-playbook' ) }
								<Badge style={ { backgroundColor: '#dcfce7', color: '#166534', border: 'none', fontSize: '11px' } }>
									Free
								</Badge>
							</CardTitle>
							<CardDescription>
								{ __( 'Universeller Feed fur Jobborsen wie Jooble, Talent.com und weitere.', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Switch
							checked={ settings.xml_feed_enabled }
							onCheckedChange={ ( val ) => updateSetting( 'xml_feed_enabled', val ) }
						/>
					</div>
				</CardHeader>
				{ settings.xml_feed_enabled && (
					<CardContent>
						<Label style={ { marginBottom: '6px', display: 'block' } }>
							{ __( 'Feed-URL', 'recruiting-playbook' ) }
						</Label>
						<div style={ { display: 'flex', gap: '8px', marginBottom: '16px' } }>
							<Input
								value={ `${ homeUrl }/feed/jobs/` }
								readOnly
								style={ { fontFamily: 'monospace', fontSize: '13px', backgroundColor: '#f9fafb' } }
							/>
							<CopyButton text={ `${ homeUrl }/feed/jobs/` } />
						</div>

						<SettingsSection title={ __( 'Feed-Optionen', 'recruiting-playbook' ) }>
							<CheckboxRow
								label={ __( 'Gehalt im Feed anzeigen', 'recruiting-playbook' ) }
								checked={ settings.xml_feed_show_salary }
								onChange={ ( val ) => updateSetting( 'xml_feed_show_salary', val ) }
							/>
							<CheckboxRow
								label={ __( 'Beschreibung als HTML (statt Plain Text)', 'recruiting-playbook' ) }
								checked={ settings.xml_feed_html_description }
								onChange={ ( val ) => updateSetting( 'xml_feed_html_description', val ) }
							/>
							<div style={ { display: 'flex', alignItems: 'center', gap: '8px', marginTop: '8px' } }>
								<Label style={ { whiteSpace: 'nowrap' } }>
									{ __( 'Max. Stellen im Feed:', 'recruiting-playbook' ) }
								</Label>
								<Input
									type="number"
									min="1"
									max="500"
									value={ settings.xml_feed_max_items }
									onChange={ ( e ) => updateSetting( 'xml_feed_max_items', parseInt( e.target.value, 10 ) || 50 ) }
									style={ { width: '80px' } }
								/>
							</div>
						</SettingsSection>
					</CardContent>
				) }
			</Card>

			{ /* ═══════════ Slack ═══════════ */ }
			<Card style={ ! isPro ? { opacity: 0.7 } : undefined }>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<div>
							<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
								{ __( 'Slack', 'recruiting-playbook' ) }
								<Badge variant="outline" style={ { fontSize: '11px' } }>Pro</Badge>
							</CardTitle>
							<CardDescription>
								{ __( 'Benachrichtigungen bei neuen Bewerbungen und Statuswechseln in einem Slack-Channel.', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Switch
							checked={ settings.slack_enabled }
							onCheckedChange={ ( val ) => updateSetting( 'slack_enabled', val ) }
							disabled={ ! isPro }
						/>
					</div>
				</CardHeader>
				{ settings.slack_enabled && isPro && (
					<CardContent>
						<Label style={ { marginBottom: '6px', display: 'block' } }>
							{ __( 'Webhook-URL', 'recruiting-playbook' ) }
						</Label>
						<Input
							type="url"
							placeholder="https://hooks.slack.com/services/T.../B.../xxx"
							value={ settings.slack_webhook_url }
							onChange={ ( e ) => updateSetting( 'slack_webhook_url', e.target.value ) }
							style={ { marginBottom: '16px' } }
						/>

						<SettingsSection title={ __( 'Benachrichtigen bei', 'recruiting-playbook' ) }>
							<CheckboxRow
								label={ __( 'Neue Bewerbung eingegangen', 'recruiting-playbook' ) }
								checked={ settings.slack_event_new_application }
								onChange={ ( val ) => updateSetting( 'slack_event_new_application', val ) }
							/>
							<CheckboxRow
								label={ __( 'Bewerbungsstatus geandert', 'recruiting-playbook' ) }
								checked={ settings.slack_event_status_changed }
								onChange={ ( val ) => updateSetting( 'slack_event_status_changed', val ) }
							/>
							<CheckboxRow
								label={ __( 'Neue Stelle veroffentlicht', 'recruiting-playbook' ) }
								checked={ settings.slack_event_job_published }
								onChange={ ( val ) => updateSetting( 'slack_event_job_published', val ) }
							/>
							<CheckboxRow
								label={ __( 'Bewerbungsfrist lauft ab (3 Tage vorher)', 'recruiting-playbook' ) }
								checked={ settings.slack_event_deadline_reminder }
								onChange={ ( val ) => updateSetting( 'slack_event_deadline_reminder', val ) }
							/>
						</SettingsSection>

						<div style={ { marginTop: '16px' } }>
							<Button
								variant="outline"
								onClick={ () => sendTestMessage( 'slack' ) }
								disabled={ ! settings.slack_webhook_url || testing === 'slack' }
							>
								{ testing === 'slack'
									? __( 'Wird gesendet...', 'recruiting-playbook' )
									: __( 'Test-Nachricht senden', 'recruiting-playbook' )
								}
							</Button>
						</div>
					</CardContent>
				) }
				{ ! isPro && (
					<CardContent>
						<Alert>
							<AlertDescription>
								{ __( 'Slack-Benachrichtigungen sind ein Pro-Feature.', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>
					</CardContent>
				) }
			</Card>

			{ /* ═══════════ Microsoft Teams ═══════════ */ }
			<Card style={ ! isPro ? { opacity: 0.7 } : undefined }>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<div>
							<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
								{ __( 'Microsoft Teams', 'recruiting-playbook' ) }
								<Badge variant="outline" style={ { fontSize: '11px' } }>Pro</Badge>
							</CardTitle>
							<CardDescription>
								{ __( 'Benachrichtigungen in einem Microsoft Teams Channel.', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Switch
							checked={ settings.teams_enabled }
							onCheckedChange={ ( val ) => updateSetting( 'teams_enabled', val ) }
							disabled={ ! isPro }
						/>
					</div>
				</CardHeader>
				{ settings.teams_enabled && isPro && (
					<CardContent>
						<Label style={ { marginBottom: '6px', display: 'block' } }>
							{ __( 'Workflow-Webhook-URL', 'recruiting-playbook' ) }
						</Label>
						<Input
							type="url"
							placeholder="https://prod-xx.westeurope.logic.azure.com/workflows/..."
							value={ settings.teams_webhook_url }
							onChange={ ( e ) => updateSetting( 'teams_webhook_url', e.target.value ) }
							style={ { marginBottom: '8px' } }
						/>
						<Alert style={ { marginBottom: '16px' } }>
							<AlertDescription style={ { fontSize: '13px' } }>
								{ __( 'Teams → Channel → ... → Workflows → "Beim Empfang einer Teams-Webhookanforderung"', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>

						<SettingsSection title={ __( 'Benachrichtigen bei', 'recruiting-playbook' ) }>
							<CheckboxRow
								label={ __( 'Neue Bewerbung eingegangen', 'recruiting-playbook' ) }
								checked={ settings.teams_event_new_application }
								onChange={ ( val ) => updateSetting( 'teams_event_new_application', val ) }
							/>
							<CheckboxRow
								label={ __( 'Bewerbungsstatus geandert', 'recruiting-playbook' ) }
								checked={ settings.teams_event_status_changed }
								onChange={ ( val ) => updateSetting( 'teams_event_status_changed', val ) }
							/>
							<CheckboxRow
								label={ __( 'Neue Stelle veroffentlicht', 'recruiting-playbook' ) }
								checked={ settings.teams_event_job_published }
								onChange={ ( val ) => updateSetting( 'teams_event_job_published', val ) }
							/>
							<CheckboxRow
								label={ __( 'Bewerbungsfrist lauft ab (3 Tage vorher)', 'recruiting-playbook' ) }
								checked={ settings.teams_event_deadline_reminder }
								onChange={ ( val ) => updateSetting( 'teams_event_deadline_reminder', val ) }
							/>
						</SettingsSection>

						<div style={ { marginTop: '16px' } }>
							<Button
								variant="outline"
								onClick={ () => sendTestMessage( 'teams' ) }
								disabled={ ! settings.teams_webhook_url || testing === 'teams' }
							>
								{ testing === 'teams'
									? __( 'Wird gesendet...', 'recruiting-playbook' )
									: __( 'Test-Nachricht senden', 'recruiting-playbook' )
								}
							</Button>
						</div>
					</CardContent>
				) }
				{ ! isPro && (
					<CardContent>
						<Alert>
							<AlertDescription>
								{ __( 'Teams-Benachrichtigungen sind ein Pro-Feature.', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>
					</CardContent>
				) }
			</Card>

			{ /* ═══════════ Kalender (ICS) ═══════════ */ }
			<Card style={ ! isPro ? { opacity: 0.7 } : undefined }>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<div>
							<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
								{ __( 'Kalender (ICS)', 'recruiting-playbook' ) }
								<Badge variant="outline" style={ { fontSize: '11px' } }>Pro</Badge>
							</CardTitle>
							<CardDescription>
								{ __( 'Fugt Interview-Einladungen automatisch eine Kalender-Datei hinzu (Google Calendar, Outlook, Apple).', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Switch
							checked={ settings.ics_enabled }
							onCheckedChange={ ( val ) => updateSetting( 'ics_enabled', val ) }
							disabled={ ! isPro }
						/>
					</div>
				</CardHeader>
				{ settings.ics_enabled && isPro && (
					<CardContent>
						<SettingsSection title={ __( 'Optionen', 'recruiting-playbook' ) }>
							<div style={ { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '12px' } }>
								<Label style={ { whiteSpace: 'nowrap' } }>
									{ __( 'Standard-Dauer:', 'recruiting-playbook' ) }
								</Label>
								<select
									value={ settings.ics_default_duration }
									onChange={ ( e ) => updateSetting( 'ics_default_duration', parseInt( e.target.value, 10 ) ) }
									style={ {
										padding: '6px 12px',
										borderRadius: '6px',
										border: '1px solid #d1d5db',
										fontSize: '14px',
									} }
								>
									<option value={ 30 }>30 { __( 'Minuten', 'recruiting-playbook' ) }</option>
									<option value={ 45 }>45 { __( 'Minuten', 'recruiting-playbook' ) }</option>
									<option value={ 60 }>60 { __( 'Minuten', 'recruiting-playbook' ) }</option>
									<option value={ 90 }>90 { __( 'Minuten', 'recruiting-playbook' ) }</option>
									<option value={ 120 }>120 { __( 'Minuten', 'recruiting-playbook' ) }</option>
								</select>
							</div>
							<div>
								<Label style={ { marginBottom: '6px', display: 'block' } }>
									{ __( 'Standard-Ort', 'recruiting-playbook' ) }
								</Label>
								<Input
									placeholder={ __( 'z.B. "Zoom" oder Buroadresse', 'recruiting-playbook' ) }
									value={ settings.ics_default_location }
									onChange={ ( e ) => updateSetting( 'ics_default_location', e.target.value ) }
								/>
							</div>
						</SettingsSection>
					</CardContent>
				) }
				{ ! isPro && (
					<CardContent>
						<Alert>
							<AlertDescription>
								{ __( 'Kalender-Integration ist ein Pro-Feature.', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>
					</CardContent>
				) }
			</Card>

			{ /* ═══════════ Speichern ═══════════ */ }
			<div style={ { display: 'flex', justifyContent: 'flex-end' } }>
				<Button onClick={ handleSave } disabled={ saving }>
					{ saving
						? __( 'Wird gespeichert...', 'recruiting-playbook' )
						: __( 'Speichern', 'recruiting-playbook' )
					}
				</Button>
			</div>
		</div>
	);
}

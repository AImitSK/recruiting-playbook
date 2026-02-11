/**
 * IntegrationSettings Component
 *
 * Settings-Tab "Integrationen" mit Google for Jobs, XML Feed,
 * Slack und Teams Einstellungen.
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
				? __( 'Copied!', 'recruiting-playbook' )
				: __( 'Copy', 'recruiting-playbook' )
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
							? __( 'Test message sent successfully!', 'recruiting-playbook' )
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
								{ __( 'Jobs automatically appear in Google Job Search through structured JSON-LD data.', 'recruiting-playbook' ) }
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
								{ __( 'Active – Schema markup is output on all job pages', 'recruiting-playbook' ) }
							</span>
						</div>

						<SettingsSection title={ __( 'Optional Fields', 'recruiting-playbook' ) }>
							<CheckboxRow
								label={ __( 'Show salary (if available)', 'recruiting-playbook' ) }
								checked={ settings.google_jobs_show_salary }
								onChange={ ( val ) => updateSetting( 'google_jobs_show_salary', val ) }
							/>
							<CheckboxRow
								label={ __( 'Mark remote option', 'recruiting-playbook' ) }
								checked={ settings.google_jobs_show_remote }
								onChange={ ( val ) => updateSetting( 'google_jobs_show_remote', val ) }
							/>
							<CheckboxRow
								label={ __( 'Set application deadline as validThrough', 'recruiting-playbook' ) }
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
								{ __( 'Universal feed for job boards like Jooble, Talent.com and more.', 'recruiting-playbook' ) }
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
							{ __( 'Feed URL', 'recruiting-playbook' ) }
						</Label>
						<div style={ { display: 'flex', gap: '8px', marginBottom: '16px' } }>
							<Input
								value={ `${ homeUrl }/feed/jobs/` }
								readOnly
								style={ { fontFamily: 'monospace', fontSize: '13px', backgroundColor: '#f9fafb' } }
							/>
							<CopyButton text={ `${ homeUrl }/feed/jobs/` } />
						</div>

						<SettingsSection title={ __( 'Feed Options', 'recruiting-playbook' ) }>
							<CheckboxRow
								label={ __( 'Show salary in feed', 'recruiting-playbook' ) }
								checked={ settings.xml_feed_show_salary }
								onChange={ ( val ) => updateSetting( 'xml_feed_show_salary', val ) }
							/>
							<CheckboxRow
								label={ __( 'Description as HTML (instead of plain text)', 'recruiting-playbook' ) }
								checked={ settings.xml_feed_html_description }
								onChange={ ( val ) => updateSetting( 'xml_feed_html_description', val ) }
							/>
							<div style={ { display: 'flex', alignItems: 'center', gap: '8px', marginTop: '8px' } }>
								<Label style={ { whiteSpace: 'nowrap' } }>
									{ __( 'Max. jobs in feed:', 'recruiting-playbook' ) }
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
								<Badge style={ { backgroundColor: '#1d71b8', color: '#fff', border: 'none', fontSize: '11px' } }>Pro</Badge>
							</CardTitle>
							<CardDescription>
								{ __( 'Notifications for new applications and status changes in a Slack channel.', 'recruiting-playbook' ) }
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
							{ __( 'Webhook URL', 'recruiting-playbook' ) }
						</Label>
						<Input
							type="url"
							placeholder="https://hooks.slack.com/services/T.../B.../xxx"
							value={ settings.slack_webhook_url }
							onChange={ ( e ) => updateSetting( 'slack_webhook_url', e.target.value ) }
							style={ { marginBottom: '16px' } }
						/>

						<SettingsSection title={ __( 'Notify on', 'recruiting-playbook' ) }>
							<CheckboxRow
								label={ __( 'New application received', 'recruiting-playbook' ) }
								checked={ settings.slack_event_new_application }
								onChange={ ( val ) => updateSetting( 'slack_event_new_application', val ) }
							/>
							<CheckboxRow
								label={ __( 'Application status changed', 'recruiting-playbook' ) }
								checked={ settings.slack_event_status_changed }
								onChange={ ( val ) => updateSetting( 'slack_event_status_changed', val ) }
							/>
							<CheckboxRow
								label={ __( 'New job published', 'recruiting-playbook' ) }
								checked={ settings.slack_event_job_published }
								onChange={ ( val ) => updateSetting( 'slack_event_job_published', val ) }
							/>
							<CheckboxRow
								label={ __( 'Application deadline expiring (3 days before)', 'recruiting-playbook' ) }
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
									? __( 'Sending...', 'recruiting-playbook' )
									: __( 'Send test message', 'recruiting-playbook' )
								}
							</Button>
						</div>
					</CardContent>
				) }
				{ ! isPro && (
					<CardContent>
						<Alert>
							<AlertDescription>
								{ __( 'Slack notifications are a Pro feature.', 'recruiting-playbook' ) }
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
								<Badge style={ { backgroundColor: '#1d71b8', color: '#fff', border: 'none', fontSize: '11px' } }>Pro</Badge>
							</CardTitle>
							<CardDescription>
								{ __( 'Notifications in a Microsoft Teams channel.', 'recruiting-playbook' ) }
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
							{ __( 'Workflow Webhook URL', 'recruiting-playbook' ) }
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
								{ __( 'Teams → Channel → ... → Workflows → "When a Teams webhook request is received"', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>

						<SettingsSection title={ __( 'Notify on', 'recruiting-playbook' ) }>
							<CheckboxRow
								label={ __( 'New application received', 'recruiting-playbook' ) }
								checked={ settings.teams_event_new_application }
								onChange={ ( val ) => updateSetting( 'teams_event_new_application', val ) }
							/>
							<CheckboxRow
								label={ __( 'Application status changed', 'recruiting-playbook' ) }
								checked={ settings.teams_event_status_changed }
								onChange={ ( val ) => updateSetting( 'teams_event_status_changed', val ) }
							/>
							<CheckboxRow
								label={ __( 'New job published', 'recruiting-playbook' ) }
								checked={ settings.teams_event_job_published }
								onChange={ ( val ) => updateSetting( 'teams_event_job_published', val ) }
							/>
							<CheckboxRow
								label={ __( 'Application deadline expiring (3 days before)', 'recruiting-playbook' ) }
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
									? __( 'Sending...', 'recruiting-playbook' )
									: __( 'Send test message', 'recruiting-playbook' )
								}
							</Button>
						</div>
					</CardContent>
				) }
				{ ! isPro && (
					<CardContent>
						<Alert>
							<AlertDescription>
								{ __( 'Teams notifications are a Pro feature.', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>
					</CardContent>
				) }
			</Card>

			{ /* ═══════════ Google Ads Conversion ═══════════ */ }
			<Card style={ ! isPro ? { opacity: 0.7 } : undefined }>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<div>
							<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
								{ __( 'Google Ads Conversion', 'recruiting-playbook' ) }
								<Badge style={ { backgroundColor: '#1d71b8', color: '#fff', border: 'none', fontSize: '11px' } }>Pro</Badge>
							</CardTitle>
							<CardDescription>
								{ __( 'Conversion tracking directly with Google Ads – without Google Tag Manager.', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Switch
							checked={ settings.google_ads_enabled }
							onCheckedChange={ ( val ) => updateSetting( 'google_ads_enabled', val ) }
							disabled={ ! isPro }
						/>
					</div>
				</CardHeader>
				{ settings.google_ads_enabled && isPro && (
					<CardContent>
						<div style={ { display: 'flex', flexDirection: 'column', gap: '12px' } }>
							<div>
								<Label style={ { marginBottom: '6px', display: 'block' } }>
									{ __( 'Conversion ID', 'recruiting-playbook' ) }
								</Label>
								<Input
									placeholder="AW-XXXXXXXXX"
									value={ settings.google_ads_conversion_id }
									onChange={ ( e ) => updateSetting( 'google_ads_conversion_id', e.target.value ) }
								/>
							</div>
							<div>
								<Label style={ { marginBottom: '6px', display: 'block' } }>
									{ __( 'Conversion Label', 'recruiting-playbook' ) }
								</Label>
								<Input
									placeholder={ __( 'Copy from Google Ads', 'recruiting-playbook' ) }
									value={ settings.google_ads_conversion_label }
									onChange={ ( e ) => updateSetting( 'google_ads_conversion_label', e.target.value ) }
								/>
							</div>
							<div>
								<Label style={ { marginBottom: '6px', display: 'block' } }>
									{ __( 'Conversion Value (EUR)', 'recruiting-playbook' ) }
								</Label>
								<Input
									type="number"
									min="0"
									step="0.01"
									placeholder="0.00"
									value={ settings.google_ads_conversion_value }
									onChange={ ( e ) => updateSetting( 'google_ads_conversion_value', e.target.value ) }
									style={ { width: '150px' } }
								/>
							</div>
						</div>

						<Alert style={ { marginTop: '16px' } }>
							<AlertDescription style={ { fontSize: '13px' } }>
								{ __( 'Automatically reported to Google Ads as a conversion for every successful application.', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>
					</CardContent>
				) }
				{ ! isPro && (
					<CardContent>
						<Alert>
							<AlertDescription>
								{ __( 'Google Ads Conversion is a Pro feature.', 'recruiting-playbook' ) }
							</AlertDescription>
						</Alert>
					</CardContent>
				) }
			</Card>

			{ /* ═══════════ Speichern ═══════════ */ }
			<div style={ { display: 'flex', justifyContent: 'flex-end' } }>
				<Button onClick={ handleSave } disabled={ saving }>
					{ saving
						? __( 'Saving...', 'recruiting-playbook' )
						: __( 'Save', 'recruiting-playbook' )
					}
				</Button>
			</div>
		</div>
	);
}

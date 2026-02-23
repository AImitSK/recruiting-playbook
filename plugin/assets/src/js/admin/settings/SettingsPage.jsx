/**
 * Settings Page Component
 *
 * Main page for plugin settings with tabs
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Alert, AlertDescription } from '../components/ui/alert';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/ui/tabs';
import { Spinner } from '../components/ui/spinner';

import { GeneralSettings, CompanySettings, ExportSettings, RolesSettings, ApiKeySettings, IntegrationSettings, AiAnalysisSettings } from './components';
import { DesignTab } from './tabs/DesignTab';
import { useSettings } from './hooks';

/**
 * SettingsPage Component
 *
 * @return {JSX.Element} Component
 */
export function SettingsPage() {
	const [ activeTab, setActiveTab ] = useState( 'general' );
	const [ notification, setNotification ] = useState( null );

	const notificationTimeoutRef = useRef( null );

	const config = window.rpSettingsData || {};
	const i18n = config.i18n || {};
	const logoUrl = config.logoUrl || '';
	const pages = config.pages || [];

	const {
		settings,
		loading,
		saving,
		error,
		setError,
		saveSettings,
		updateSetting,
	} = useSettings();

	// Cleanup on unmount
	useEffect( () => {
		return () => {
			if ( notificationTimeoutRef.current ) {
				clearTimeout( notificationTimeoutRef.current );
			}
		};
	}, [] );

	/**
	 * Show notification
	 */
	const showNotification = useCallback( ( message, type = 'success' ) => {
		if ( notificationTimeoutRef.current ) {
			clearTimeout( notificationTimeoutRef.current );
		}

		setNotification( { message, type } );

		notificationTimeoutRef.current = setTimeout( () => {
			setNotification( null );
			notificationTimeoutRef.current = null;
		}, 3000 );
	}, [] );

	/**
	 * Save settings
	 */
	const handleSave = useCallback( async ( data ) => {
		setError( null );
		const success = await saveSettings( data );

		if ( success ) {
			showNotification( i18n.settingsSaved || __( 'Settings have been saved.', 'recruiting-playbook' ) );
		}
	}, [ saveSettings, showNotification, setError, i18n.settingsSaved ] );

	// Loading state
	if ( loading ) {
		return (
			<div className="rp-admin" style={ { padding: '20px 0' } }>
				<div style={ { maxWidth: '900px' } }>
					<div style={ { display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '3rem' } }>
						<Spinner size="lg" />
					</div>
				</div>
			</div>
		);
	}

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: ( activeTab === 'design' || activeTab === 'api' || activeTab === 'ai' || activeTab === 'integrations' ) ? '1100px' : '900px' } }>
				{ /* Header: Logo left, title right */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img src={ logoUrl } alt="Recruiting Playbook" style={ { width: '150px', height: 'auto' } } />
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ i18n.pageTitle || __( 'Settings', 'recruiting-playbook' ) }
					</h1>
				</div>

				{ /* Success notification */ }
				{ notification && (
					<Alert
						variant={ notification.type === 'error' ? 'destructive' : 'default' }
						style={ {
							marginBottom: '1rem',
							backgroundColor: notification.type === 'success' ? '#e6f5ec' : undefined,
							borderColor: notification.type === 'success' ? '#2fac66' : undefined,
						} }
					>
						<AlertDescription>{ notification.message }</AlertDescription>
					</Alert>
				) }

				{ /* Tabs */ }
				<Tabs value={ activeTab } onValueChange={ setActiveTab }>
					<TabsList>
						<TabsTrigger value="general">
							{ i18n.tabGeneral || __( 'General', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="company">
							{ i18n.tabCompany || __( 'Company Information', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="export">
							{ i18n.tabExport || __( 'Export & Import', 'recruiting-playbook' ) }
						</TabsTrigger>
						{ config.isPro && (
							<TabsTrigger value="roles">
								{ __( 'User Roles', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
						{ config.isPro && (
							<TabsTrigger value="design">
								{ __( 'Design & Branding', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
						<TabsTrigger value="integrations">
							{ __( 'Integrations', 'recruiting-playbook' ) }
						</TabsTrigger>
						{ config.isPro && (
							<TabsTrigger value="api">
								{ __( 'API', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
						{ config.isPro && (
							<TabsTrigger value="ai">
								{ __( 'AI Analysis', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
					</TabsList>

					<TabsContent value="general">
						<GeneralSettings
							settings={ settings }
							pages={ pages }
							saving={ saving }
							error={ error }
							onUpdate={ updateSetting }
							onSave={ handleSave }
						/>
					</TabsContent>

					<TabsContent value="company">
						<CompanySettings
							settings={ settings }
							saving={ saving }
							error={ error }
							onUpdate={ updateSetting }
							onSave={ handleSave }
						/>
					</TabsContent>

					<TabsContent value="export">
						<ExportSettings />
					</TabsContent>

					{ config.isPro && (
						<TabsContent value="roles">
							<RolesSettings />
						</TabsContent>
					) }

					{ config.isPro && (
						<TabsContent value="design">
							<DesignTab />
						</TabsContent>
					) }

					<TabsContent value="integrations">
						<IntegrationSettings />
					</TabsContent>

					{ config.isPro && (
						<TabsContent value="api">
							<ApiKeySettings />
						</TabsContent>
					) }

					{ config.isPro && (
						<TabsContent value="ai">
							<AiAnalysisSettings />
						</TabsContent>
					) }
				</Tabs>
			</div>
		</div>
	);
}

export default SettingsPage;

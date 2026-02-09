/**
 * Settings Page Component
 *
 * Hauptseite für Plugin-Einstellungen mit Tabs
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Alert, AlertDescription } from '../components/ui/alert';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/ui/tabs';
import { Spinner } from '../components/ui/spinner';

import { GeneralSettings, CompanySettings, ExportSettings, RolesSettings, ApiKeySettings } from './components';
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

	// Cleanup bei Unmount
	useEffect( () => {
		return () => {
			if ( notificationTimeoutRef.current ) {
				clearTimeout( notificationTimeoutRef.current );
			}
		};
	}, [] );

	/**
	 * Benachrichtigung anzeigen
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
	 * Settings speichern
	 */
	const handleSave = useCallback( async ( data ) => {
		setError( null );
		const success = await saveSettings( data );

		if ( success ) {
			showNotification( i18n.settingsSaved || __( 'Einstellungen wurden gespeichert.', 'recruiting-playbook' ) );
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
			<div style={ { maxWidth: ( activeTab === 'design' || activeTab === 'api' ) ? '1100px' : '900px' } }>
				{ /* Header: Logo links, Titel rechts */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img src={ logoUrl } alt="Recruiting Playbook" style={ { width: '150px', height: 'auto' } } />
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ i18n.pageTitle || __( 'Einstellungen', 'recruiting-playbook' ) }
					</h1>
				</div>

				{ /* Erfolgs-Notification */ }
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
							{ i18n.tabGeneral || __( 'Allgemein', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="company">
							{ i18n.tabCompany || __( 'Firmendaten', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="export">
							{ i18n.tabExport || __( 'Export', 'recruiting-playbook' ) }
						</TabsTrigger>
						{ config.isPro && (
							<TabsTrigger value="roles">
								{ __( 'Benutzerrollen', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
						{ config.isPro && (
							<TabsTrigger value="design">
								{ __( 'Design & Branding', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
						{ config.isPro && (
							<TabsTrigger value="api">
								{ __( 'API', 'recruiting-playbook' ) }
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

					{ config.isPro && (
						<TabsContent value="api">
							<ApiKeySettings />
						</TabsContent>
					) }
				</Tabs>
			</div>
		</div>
	);
}

export default SettingsPage;

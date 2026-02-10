/**
 * DesignTab Component
 *
 * Haupt-Container für Design & Branding Einstellungen.
 * Enthält Sub-Tabs für verschiedene Design-Bereiche.
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Alert, AlertDescription } from '../../components/ui/alert';
import { Button } from '../../components/ui/button';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../../components/ui/tabs';
import { AlertCircle } from 'lucide-react';

import { BrandingPanel } from '../components/design/BrandingPanel';
import { TypographyPanel } from '../components/design/TypographyPanel';
import { CardsPanel } from '../components/design/CardsPanel';
import { ButtonsPanel } from '../components/design/ButtonsPanel';
import { JobListPanel } from '../components/design/JobListPanel';
import { AiButtonPanel } from '../components/design/AiButtonPanel';
import { LivePreview } from '../components/design/LivePreview';
import { useDesignSettings } from '../hooks/useDesignSettings';

/**
 * DesignTab Component
 *
 * @return {JSX.Element} Component
 */
export function DesignTab() {
	const [ activeSubTab, setActiveSubTab ] = useState( 'branding' );
	const [ notification, setNotification ] = useState( null );

	const notificationTimeoutRef = useRef( null );

	const {
		settings,
		schema,
		defaults,
		meta,
		loading,
		saving,
		error,
		isDirty,
		computedPrimaryColor,
		setError,
		saveSettings,
		resetSettings,
		updateSetting,
		updateSettings,
		discardChanges,
	} = useDesignSettings();

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
	const handleSave = useCallback( async () => {
		setError( null );
		const success = await saveSettings();

		if ( success ) {
			showNotification( __( 'Design-Einstellungen wurden gespeichert.', 'recruiting-playbook' ) );
		}
	}, [ saveSettings, showNotification, setError ] );

	/**
	 * Settings zurücksetzen
	 */
	const handleReset = useCallback( async () => {
		if ( ! window.confirm( __( 'Alle Design-Einstellungen auf Standardwerte zurücksetzen?', 'recruiting-playbook' ) ) ) {
			return;
		}

		setError( null );
		const success = await resetSettings();

		if ( success ) {
			showNotification( __( 'Design-Einstellungen wurden zurückgesetzt.', 'recruiting-playbook' ) );
		}
	}, [ resetSettings, showNotification, setError ] );

	/**
	 * Änderungen verwerfen
	 */
	const handleDiscard = useCallback( () => {
		if ( isDirty && window.confirm( __( 'Ungespeicherte Änderungen verwerfen?', 'recruiting-playbook' ) ) ) {
			discardChanges();
		}
	}, [ isDirty, discardChanges ] );

	// Loading state
	if ( loading ) {
		return (
			<div className="rp-flex rp-items-center rp-justify-center rp-py-12">
				<div className="rp-animate-spin rp-rounded-full rp-h-8 rp-w-8 rp-border-b-2 rp-border-blue-600" />
			</div>
		);
	}

	// No settings
	if ( ! settings ) {
		return (
			<Alert variant="destructive">
				<AlertCircle className="rp-h-4 rp-w-4" />
				<AlertDescription>
					{ __( 'Design-Einstellungen konnten nicht geladen werden.', 'recruiting-playbook' ) }
				</AlertDescription>
			</Alert>
		);
	}

	return (
		<div className="rp-flex rp-gap-6">
			{ /* Settings-Panel (links) */ }
			<div className="rp-flex-1 rp-min-w-0">
				{ /* Error Alert */ }
				{ error && (
					<Alert variant="destructive" className="rp-mb-4">
						<AlertCircle className="rp-h-4 rp-w-4" />
						<AlertDescription>{ error }</AlertDescription>
					</Alert>
				) }

				{ /* Success Notification */ }
				{ notification && (
					<Alert
						variant={ notification.type === 'error' ? 'destructive' : 'default' }
						className="rp-mb-4"
						style={ {
							backgroundColor: notification.type === 'success' ? '#e6f5ec' : undefined,
							borderColor: notification.type === 'success' ? '#2fac66' : undefined,
						} }
					>
						<AlertDescription>{ notification.message }</AlertDescription>
					</Alert>
				) }

				{ /* Sub-Tabs */ }
				<Tabs value={ activeSubTab } onValueChange={ setActiveSubTab }>
					<TabsList className="rp-mb-4">
						<TabsTrigger value="branding">
							{ __( 'Branding', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="typography">
							{ __( 'Typografie', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="cards">
							{ __( 'Cards', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="buttons">
							{ __( 'Buttons', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="joblist">
							{ __( 'Job-Liste', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="aibutton">
							{ __( 'KI-Button', 'recruiting-playbook' ) }
						</TabsTrigger>
					</TabsList>

					<TabsContent value="branding">
						<BrandingPanel
							settings={ settings }
							meta={ meta }
							onUpdate={ updateSetting }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</TabsContent>

					<TabsContent value="typography">
						<TypographyPanel
							settings={ settings }
							onUpdate={ updateSetting }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</TabsContent>

					<TabsContent value="cards">
						<CardsPanel
							settings={ settings }
							onUpdate={ updateSetting }
						/>
					</TabsContent>

					<TabsContent value="buttons">
						<ButtonsPanel
							settings={ settings }
							onUpdate={ updateSetting }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</TabsContent>

					<TabsContent value="joblist">
						<JobListPanel
							settings={ settings }
							onUpdate={ updateSetting }
						/>
					</TabsContent>

					<TabsContent value="aibutton">
						<AiButtonPanel
							settings={ settings }
							onUpdate={ updateSetting }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</TabsContent>
				</Tabs>

				{ /* Action Buttons */ }
				<div className="rp-flex rp-items-center rp-justify-between rp-mt-6 rp-pt-4 rp-border-t rp-border-gray-200">
					<div className="rp-flex rp-gap-2">
						<Button
							variant="outline"
							onClick={ handleReset }
							disabled={ saving }
						>
							{ __( 'Zurücksetzen', 'recruiting-playbook' ) }
						</Button>
						{ isDirty && (
							<Button
								variant="ghost"
								onClick={ handleDiscard }
								disabled={ saving }
							>
								{ __( 'Verwerfen', 'recruiting-playbook' ) }
							</Button>
						) }
					</div>
					<Button
						onClick={ handleSave }
						disabled={ saving || ! isDirty }
					>
						{ saving
							? __( 'Speichern...', 'recruiting-playbook' )
							: __( 'Einstellungen speichern', 'recruiting-playbook' )
						}
					</Button>
				</div>
			</div>

			{ /* Live-Vorschau (rechts) */ }
			<div className="rp-w-80 rp-flex-shrink-0">
				<LivePreview
					settings={ settings }
					computedPrimaryColor={ computedPrimaryColor }
				/>
			</div>
		</div>
	);
}

export default DesignTab;

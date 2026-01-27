/**
 * License Page Component
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	CheckCircle,
	AlertCircle,
	Cloud,
	ExternalLink,
	Check,
	X,
	Sparkles,
} from 'lucide-react';
import { Button } from '../components/ui/button';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '../components/ui/card';
import { Input } from '../components/ui/input';
import { Alert, AlertDescription, AlertTitle } from '../components/ui/alert';
import { Badge } from '../components/ui/badge';
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '../components/ui/table';
import { cn } from '../lib/utils';

/**
 * Get badge variant based on license tier
 *
 * @param {string} tier - License tier
 * @return {string} Badge variant
 */
function getTierBadgeVariant( tier ) {
	switch ( tier ) {
		case 'PRO':
			return 'success';
		case 'AI_ADDON':
			return 'warning';
		case 'BUNDLE':
			return 'purple';
		default:
			return 'secondary';
	}
}

/**
 * Status Alert Component
 *
 * @param {Object}  props          - Component props
 * @param {Object}  props.status   - License status object
 * @param {boolean} props.isOffline - Whether in offline mode
 */
function StatusAlert( { status, isOffline } ) {
	const isActive = status.is_active;
	const bgColor = isActive ? '#e6f5ec' : '#edf4f9';
	const borderColor = isActive ? '#2fac66' : '#1d71b8';
	const textColor = isActive ? '#2fac66' : '#1d71b8';
	const badgeBg = isActive ? '#2fac66' : '#1d71b8';

	const boxStyle = {
		backgroundColor: bgColor,
		borderLeft: `4px solid ${borderColor}`,
		borderTop: `1px solid ${isActive ? '#c3e6d1' : '#d1e3f0'}`,
		borderRight: `1px solid ${isActive ? '#c3e6d1' : '#d1e3f0'}`,
		borderBottom: `1px solid ${isActive ? '#c3e6d1' : '#d1e3f0'}`,
		borderRadius: '0.375rem',
		padding: '1rem 1.25rem',
		marginBottom: '1.5rem',
		display: 'flex',
		alignItems: 'center',
		justifyContent: 'space-between',
		minHeight: '56px',
	};

	const badgeStyle = {
		backgroundColor: badgeBg,
		color: '#ffffff',
		padding: '0.25rem 0.75rem',
		borderRadius: '9999px',
		fontSize: '0.75rem',
		fontWeight: 600,
	};

	return (
		<div style={ boxStyle }>
			<span style={ { fontSize: '1rem', fontWeight: 600, color: textColor } }>
				{ status.message }
			</span>
			<span style={ badgeStyle }>
				{ status.tier }
			</span>
		</div>
	);
}

/**
 * Activation Form Component
 *
 * @param {Object}   props            - Component props
 * @param {Object}   props.status     - License status object
 * @param {Function} props.onActivate - Activation callback
 * @param {Function} props.onDeactivate - Deactivation callback
 * @param {boolean}  props.isLoading  - Loading state
 */
function ActivationForm( { status, onActivate, onDeactivate, isLoading } ) {
	const [ licenseKey, setLicenseKey ] = useState( '' );

	const handleSubmit = ( e ) => {
		e.preventDefault();
		onActivate( licenseKey );
	};

	return (
		<form onSubmit={ handleSubmit }>
			<div style={ { marginBottom: '1rem' } }>
				<label
					htmlFor="license-key"
					className="rp-text-sm rp-font-medium rp-leading-none"
					style={ { display: 'block', marginBottom: '0.5rem' } }
				>
					{ __( 'Lizenzschlüssel', 'recruiting-playbook' ) }
				</label>
				<Input
					id="license-key"
					type="text"
					placeholder="RP-PRO-XXXX-XXXX-XXXX-XXXX-XXXX"
					value={ licenseKey }
					onChange={ ( e ) =>
						setLicenseKey( e.target.value.toUpperCase() )
					}
					className="rp-font-mono rp-uppercase"
					disabled={ isLoading }
				/>
				<p className="rp-text-sm rp-text-muted-foreground" style={ { marginTop: '0.5rem' } }>
					{ __(
						'Geben Sie Ihren Lizenzschlüssel ein, um Pro-Features freizuschalten.',
						'recruiting-playbook'
					) }
				</p>
			</div>

			<div style={ { display: 'flex', gap: '0.75rem', marginTop: '1.5rem' } }>
				<Button type="submit" disabled={ isLoading || ! licenseKey }>
					{ isLoading
						? __( 'Wird aktiviert...', 'recruiting-playbook' )
						: __( 'Lizenz aktivieren', 'recruiting-playbook' ) }
				</Button>

				{ status.is_active && (
					<Button
						type="button"
						variant="outline"
						onClick={ onDeactivate }
						disabled={ isLoading }
					>
						{ __( 'Lizenz deaktivieren', 'recruiting-playbook' ) }
					</Button>
				) }
			</div>
		</form>
	);
}

/**
 * Upgrade Box Component
 *
 * @param {Object} props          - Component props
 * @param {string} props.upgradeUrl - URL to upgrade page
 */
function UpgradeBox( { upgradeUrl } ) {
	const features = [
		__( 'Kanban-Board für Bewerbungsmanagement', 'recruiting-playbook' ),
		__( 'REST API & Webhooks für Integrationen', 'recruiting-playbook' ),
		__( 'Anpassbare E-Mail-Templates', 'recruiting-playbook' ),
		__( 'Erweiterte Statistiken & Reports', 'recruiting-playbook' ),
		__( 'Design-Anpassungen & Custom Branding', 'recruiting-playbook' ),
		__( 'Benutzerrollen & Berechtigungen', 'recruiting-playbook' ),
	];

	const boxStyle = {
		marginTop: '1.5rem',
		borderRadius: '0.5rem',
		padding: '1.5rem',
		background: 'linear-gradient(to bottom right, #2fac66, #36a9e1)',
		color: '#ffffff',
	};

	return (
		<div style={ boxStyle }>
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' } }>
				<Sparkles style={ { width: '1.25rem', height: '1.25rem', color: '#ffffff' } } />
				<h3 style={ { fontSize: '1.125rem', fontWeight: 600, color: '#ffffff', margin: 0 } }>
					{ __( 'Upgrade auf Pro', 'recruiting-playbook' ) }
				</h3>
			</div>
			<p style={ { marginBottom: '1rem', color: '#ffffff' } }>
				{ __(
					'Schalten Sie alle Features frei und nutzen Sie das volle Potenzial:',
					'recruiting-playbook'
				) }
			</p>
			<ul style={ { marginBottom: '1.5rem', listStyle: 'none', padding: 0 } }>
				{ features.map( ( feature, index ) => (
					<li key={ index } style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.5rem', color: '#ffffff' } }>
						<Check style={ { width: '1rem', height: '1rem', color: '#ffffff' } } />
						<span>{ feature }</span>
					</li>
				) ) }
			</ul>
			<a
				href={ upgradeUrl }
				target="_blank"
				rel="noopener noreferrer"
				style={ {
					display: 'inline-flex',
					alignItems: 'center',
					gap: '0.5rem',
					padding: '0.5rem 1rem',
					backgroundColor: '#ffffff',
					color: '#1d71b8',
					borderRadius: '0.375rem',
					fontWeight: 500,
					textDecoration: 'none',
					fontSize: '0.875rem',
				} }
			>
				{ __( 'Jetzt upgraden', 'recruiting-playbook' ) }
				<ExternalLink style={ { width: '1rem', height: '1rem' } } />
			</a>
		</div>
	);
}

/**
 * Feature Comparison Table Component
 *
 * @param {Object} props          - Component props
 * @param {Object} props.features - Features by tier
 */
function FeatureComparison( { features } ) {
	const tiers = [
		{ key: 'FREE', label: 'Free' },
		{ key: 'PRO', label: 'Pro' },
		{ key: 'AI_ADDON', label: 'AI Addon' },
		{ key: 'BUNDLE', label: 'Bundle' },
	];

	const featureLabels = {
		unlimited_jobs: __( 'Unbegrenzte Stellenanzeigen', 'recruiting-playbook' ),
		application_list: __( 'Bewerberliste', 'recruiting-playbook' ),
		kanban_board: __( 'Kanban-Board', 'recruiting-playbook' ),
		advanced_applicant_management: __(
			'Erweitertes Bewerbermanagement',
			'recruiting-playbook'
		),
		email_templates: __( 'E-Mail-Templates', 'recruiting-playbook' ),
		api_access: __( 'REST API Zugang', 'recruiting-playbook' ),
		webhooks: __( 'Webhooks', 'recruiting-playbook' ),
		design_settings: __( 'Design-Einstellungen', 'recruiting-playbook' ),
		user_roles: __( 'Benutzerrollen', 'recruiting-playbook' ),
		ai_job_generation: __( 'KI-Stellenanzeigen', 'recruiting-playbook' ),
		ai_text_improvement: __( 'KI-Textverbesserung', 'recruiting-playbook' ),
	};

	return (
		<Card style={ { marginTop: '2rem' } }>
			<CardHeader>
				<CardTitle>
					{ __( 'Feature-Vergleich', 'recruiting-playbook' ) }
				</CardTitle>
				<CardDescription>
					{ __(
						'Vergleichen Sie die verfügbaren Features pro Lizenz-Stufe.',
						'recruiting-playbook'
					) }
				</CardDescription>
			</CardHeader>
			<CardContent>
				<Table>
					<TableHeader>
						<TableRow>
							<TableHead className="rp-w-[300px]">
								{ __( 'Feature', 'recruiting-playbook' ) }
							</TableHead>
							{ tiers.map( ( tier ) => (
								<TableHead
									key={ tier.key }
									className="rp-text-center"
								>
									{ tier.label }
								</TableHead>
							) ) }
						</TableRow>
					</TableHeader>
					<TableBody>
						{ Object.entries( featureLabels ).map(
							( [ featureKey, featureLabel ] ) => (
								<TableRow key={ featureKey }>
									<TableCell className="rp-font-medium">
										{ featureLabel }
									</TableCell>
									{ tiers.map( ( tier ) => {
										const hasFeature =
											features[ tier.key ]?.[ featureKey ];
										return (
											<TableCell
												key={ tier.key }
												className="rp-text-center"
											>
												{ hasFeature ? (
													<Check className="rp-mx-auto rp-h-5 rp-w-5" style={ { color: '#2fac66' } } />
												) : (
													<X className="rp-mx-auto rp-h-5 rp-w-5" style={ { color: '#d1d5db' } } />
												) }
											</TableCell>
										);
									} ) }
								</TableRow>
							)
						) }
					</TableBody>
				</Table>
			</CardContent>
		</Card>
	);
}

/**
 * Main License Page Component
 */
export function LicensePage() {
	const initialData = window.rpLicenseData || {};
	const [ status, setStatus ] = useState( initialData.status || {} );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ message, setMessage ] = useState( null );

	const handleActivate = async ( licenseKey ) => {
		setIsLoading( true );
		setMessage( null );

		try {
			const response = await apiFetch( {
				path: '/recruiting/v1/license/activate',
				method: 'POST',
				data: { license_key: licenseKey },
			} );

			if ( response.success ) {
				setStatus( response.status );
				setMessage( { type: 'success', text: response.message } );
			} else {
				setMessage( { type: 'error', text: response.message } );
			}
		} catch ( error ) {
			setMessage( {
				type: 'error',
				text:
					error.message ||
					__( 'Ein Fehler ist aufgetreten.', 'recruiting-playbook' ),
			} );
		} finally {
			setIsLoading( false );
		}
	};

	const handleDeactivate = async () => {
		setIsLoading( true );
		setMessage( null );

		try {
			const response = await apiFetch( {
				path: '/recruiting/v1/license/deactivate',
				method: 'POST',
			} );

			if ( response.success ) {
				setStatus( response.status );
				setMessage( { type: 'success', text: response.message } );
			} else {
				setMessage( { type: 'error', text: response.message } );
			}
		} catch ( error ) {
			setMessage( {
				type: 'error',
				text:
					error.message ||
					__( 'Ein Fehler ist aufgetreten.', 'recruiting-playbook' ),
			} );
		} finally {
			setIsLoading( false );
		}
	};

	const logoUrl = initialData.logoUrl;

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '900px' } }>
				{ logoUrl && (
					<div style={ { marginBottom: '1.5rem' } }>
						<img
							src={ logoUrl }
							alt="Recruiting Playbook"
							style={ { width: '150px', height: 'auto' } }
						/>
					</div>
				) }

				{ message && (
					<Alert
						variant={ message.type === 'success' ? 'success' : 'destructive' }
						style={ { marginBottom: '1.5rem' } }
					>
						<AlertDescription>{ message.text }</AlertDescription>
					</Alert>
				) }

				<Card>
					<CardHeader>
						<CardTitle>
							{ __( 'Lizenzstatus', 'recruiting-playbook' ) }
						</CardTitle>
					</CardHeader>
					<CardContent>
						<StatusAlert
							status={ status }
							isOffline={ status.is_offline }
						/>
						<ActivationForm
							status={ status }
							onActivate={ handleActivate }
							onDeactivate={ handleDeactivate }
							isLoading={ isLoading }
						/>

						{ status.tier === 'FREE' && (
							<UpgradeBox upgradeUrl={ status.upgrade_url } />
						) }
					</CardContent>
				</Card>

				<FeatureComparison features={ initialData.features || {} } />
			</div>
		</div>
	);
}

export default LicensePage;

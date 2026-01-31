/**
 * FreeVersionOverlay Component
 *
 * Displays an overlay over the Form Builder when user is on Free version.
 * The form is visible but grayed out with an upgrade prompt.
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { Button } from '../../components/ui/button';
import { Lock, Sparkles, Check } from 'lucide-react';

/**
 * FreeVersionOverlay component
 *
 * @param {Object} props              Component props
 * @param {string} props.upgradeUrl   URL to upgrade page
 * @param {Object} props.i18n         Translations
 */
export default function FreeVersionOverlay( { upgradeUrl, i18n = {} } ) {
	const features = [
		__( 'Formular-Schritte anpassen', 'recruiting-playbook' ),
		__( 'Felder hinzufügen und entfernen', 'recruiting-playbook' ),
		__( 'Eigene Felder erstellen', 'recruiting-playbook' ),
		__( 'Drag & Drop Sortierung', 'recruiting-playbook' ),
		__( 'System-Feld Einstellungen', 'recruiting-playbook' ),
	];

	return (
		<div
			style={ {
				position: 'absolute',
				inset: 0,
				backgroundColor: 'rgba(255, 255, 255, 0.85)',
				backdropFilter: 'blur(2px)',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				zIndex: 50,
				borderRadius: '0.5rem',
			} }
		>
			<div
				style={ {
					backgroundColor: 'white',
					borderRadius: '0.75rem',
					padding: '2rem',
					maxWidth: '400px',
					width: '100%',
					boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
					border: '1px solid #e5e7eb',
					textAlign: 'center',
				} }
			>
				{ /* Lock Icon */ }
				<div
					style={ {
						display: 'inline-flex',
						alignItems: 'center',
						justifyContent: 'center',
						width: '3.5rem',
						height: '3.5rem',
						backgroundColor: '#fef3c7',
						borderRadius: '50%',
						marginBottom: '1rem',
					} }
				>
					<Lock style={ { height: '1.75rem', width: '1.75rem', color: '#d97706' } } />
				</div>

				{ /* Title */ }
				<h3 style={ { fontSize: '1.25rem', fontWeight: 700, color: '#1f2937', margin: '0 0 0.5rem' } }>
					{ i18n?.proFeatureTitle || __( 'Pro-Feature', 'recruiting-playbook' ) }
				</h3>

				{ /* Description */ }
				<p style={ { fontSize: '0.875rem', color: '#6b7280', margin: '0 0 1.25rem', lineHeight: 1.5 } }>
					{ i18n?.proFeatureDescription || __( 'Der Formular-Builder ist ein Pro-Feature. Upgraden Sie, um Ihr Bewerbungsformular vollständig anzupassen.', 'recruiting-playbook' ) }
				</p>

				{ /* Feature List */ }
				<div style={ { textAlign: 'left', marginBottom: '1.5rem' } }>
					{ features.map( ( feature, index ) => (
						<div
							key={ index }
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '0.5rem',
								padding: '0.375rem 0',
								fontSize: '0.875rem',
								color: '#374151',
							} }
						>
							<Check style={ { height: '1rem', width: '1rem', color: '#10b981', flexShrink: 0 } } />
							<span>{ feature }</span>
						</div>
					) ) }
				</div>

				{ /* Upgrade Button */ }
				<Button
					onClick={ () => window.location.href = upgradeUrl }
					style={ {
						width: '100%',
						backgroundColor: '#7c3aed',
						color: 'white',
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						gap: '0.5rem',
					} }
				>
					<Sparkles style={ { height: '1rem', width: '1rem' } } />
					{ i18n?.upgradeToPro || __( 'Auf Pro upgraden', 'recruiting-playbook' ) }
				</Button>

				{ /* Subtext */ }
				<p style={ { fontSize: '0.75rem', color: '#9ca3af', margin: '0.75rem 0 0' } }>
					{ i18n?.standardFormInfo || __( 'In der Free Version wird ein Standard-Formular verwendet.', 'recruiting-playbook' ) }
				</p>
			</div>
		</div>
	);
}

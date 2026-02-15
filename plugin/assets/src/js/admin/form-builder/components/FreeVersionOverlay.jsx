/**
 * FreeVersionOverlay Component
 *
 * Displays an overlay over the Form Builder when user is on Free version.
 * Uses a consistent upgrade prompt style matching the PHP rp_require_feature() design.
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';

/**
 * FreeVersionOverlay component
 *
 * @param {Object} props              Component props
 * @param {string} props.upgradeUrl   URL to upgrade page
 */
export default function FreeVersionOverlay( { upgradeUrl } ) {
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
					display: 'flex',
					alignItems: 'flex-start',
					gap: '16px',
					padding: '24px',
					background: 'linear-gradient(135deg, #f0f6fc 0%, #fff 100%)',
					border: '1px solid #c3d9ed',
					borderRadius: '8px',
					maxWidth: '480px',
					width: '100%',
				} }
			>
				<div
					style={ {
						flexShrink: 0,
						width: '48px',
						height: '48px',
						background: '#2271b1',
						borderRadius: '50%',
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
					} }
				>
					<span
						className="dashicons dashicons-lock"
						style={ { fontSize: '24px', width: '24px', height: '24px', color: '#fff' } }
					/>
				</div>
				<div>
					<h3 style={ { margin: '0 0 8px 0', fontSize: '16px', color: '#1d2327' } }>
						{ __( 'Form Builder is a Pro feature', 'recruiting-playbook' ) }
					</h3>
					<p style={ { margin: '0 0 16px 0', color: '#50575e', fontSize: '14px', lineHeight: 1.5 } }>
						{ __( 'Upgrade to Pro to unlock this feature. You can compare plans and pricing on the upgrade page.', 'recruiting-playbook' ) }
					</p>
					<a
						href={ upgradeUrl }
						className="button button-primary button-hero"
					>
						{ __( 'Upgrade to Pro', 'recruiting-playbook' ) }
					</a>
				</div>
			</div>
		</div>
	);
}

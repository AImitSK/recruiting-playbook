/**
 * ProBadge Component
 *
 * Badge component to indicate Pro-only features in the block editor.
 *
 * @package
 */

import { __ } from '@wordpress/i18n';

/**
 * ProBadge - Visual indicator for Pro features
 *
 * @param {Object}  props           Component props.
 * @param {string}  props.size      Badge size: 'small', 'medium', 'large'.
 * @param {boolean} props.inline    Display inline with text.
 * @param {string}  props.className Additional CSS class.
 * @return {JSX.Element} The component.
 */
export function ProBadge( { size = 'small', inline = false, className = '' } ) {
	const sizeClass = `rp-pro-badge--${ size }`;
	const inlineClass = inline ? 'rp-pro-badge--inline' : '';

	return (
		<span
			className={ `rp-pro-badge ${ sizeClass } ${ inlineClass } ${ className }` }
		>
			{ __( 'PRO', 'recruiting-playbook' ) }
		</span>
	);
}

/**
 * FeatureGate - Wrapper that shows upgrade prompt if feature not available
 *
 * @param {Object}      props            Component props.
 * @param {string}      props.feature    Feature slug to check.
 * @param {string}      props.upgradeUrl URL to upgrade page.
 * @param {JSX.Element} props.children   Content to show if feature available.
 * @return {JSX.Element} The component.
 */
export function FeatureGate( { feature, upgradeUrl, children } ) {
	const config = window.rpBlocksConfig || {};

	// Pro features (including AI) require Pro plan.
	if ( ( feature === 'ai' || feature === 'pro' ) && ! config.isPro ) {
		return (
			<div className="rp-feature-gate rp-feature-gate--locked">
				<div className="rp-feature-gate__icon">
					<span className="dashicons dashicons-lock"></span>
				</div>
				<div className="rp-feature-gate__content">
					<h4>
						{ __( 'This feature requires Pro', 'recruiting-playbook' ) }
					</h4>
					<p>
						{ __(
							'Upgrade to Pro to unlock this feature.',
							'recruiting-playbook'
						) }
					</p>
					{ upgradeUrl && (
						<a
							href={ upgradeUrl }
							className="rp-feature-gate__button"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __(
								'Upgrade to Pro',
								'recruiting-playbook'
							) }
						</a>
					) }
				</div>
			</div>
		);
	}

	return children;
}

export default ProBadge;

/**
 * ProBadge Component
 *
 * Badge component to indicate Pro-only features in the block editor.
 *
 * @package RecruitingPlaybook
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
export function ProBadge( {
	size = 'small',
	inline = false,
	className = '',
} ) {
	const sizeClass = `rp-pro-badge--${ size }`;
	const inlineClass = inline ? 'rp-pro-badge--inline' : '';

	return (
		<span className={ `rp-pro-badge ${ sizeClass } ${ inlineClass } ${ className }` }>
			{ __( 'PRO', 'recruiting-playbook' ) }
		</span>
	);
}

/**
 * AiBadge - Visual indicator for AI-Addon features
 *
 * @param {Object}  props           Component props.
 * @param {string}  props.size      Badge size: 'small', 'medium', 'large'.
 * @param {boolean} props.inline    Display inline with text.
 * @param {string}  props.className Additional CSS class.
 * @return {JSX.Element} The component.
 */
export function AiBadge( {
	size = 'small',
	inline = false,
	className = '',
} ) {
	const sizeClass = `rp-ai-badge--${ size }`;
	const inlineClass = inline ? 'rp-ai-badge--inline' : '';

	return (
		<span className={ `rp-ai-badge ${ sizeClass } ${ inlineClass } ${ className }` }>
			{ __( 'AI', 'recruiting-playbook' ) }
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
export function FeatureGate( {
	feature,
	upgradeUrl,
	children,
} ) {
	// Check if feature is available via localized config.
	const config = window.rpBlocksConfig || {};

	// AI features require AI addon.
	if ( feature === 'ai' && ! config.hasAiAddon ) {
		return (
			<div className="rp-feature-gate rp-feature-gate--locked">
				<div className="rp-feature-gate__icon">
					<span className="dashicons dashicons-lock"></span>
				</div>
				<div className="rp-feature-gate__content">
					<h4>{ __( 'AI-Addon erforderlich', 'recruiting-playbook' ) }</h4>
					<p>
						{ __(
							'Diese Funktion erfordert das AI-Addon für automatische Kandidatenanalyse.',
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
							{ __( 'AI-Addon freischalten', 'recruiting-playbook' ) }
						</a>
					) }
				</div>
			</div>
		);
	}

	// Feature available, render children.
	return children;
}

export default ProBadge;

/**
 * Block Placeholder Component
 *
 * Styled placeholder for Recruiting Playbook blocks in the editor.
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';

/* global rpBlocksConfig */

/**
 * RP Logo Icon Component
 *
 * Uses the PNG logo from plugin assets.
 *
 * @return {JSX.Element} The RP icon.
 */
const RPIcon = () => (
	<img
		src={ `${ rpBlocksConfig.pluginUrl }assets/images/rp-icon.png` }
		alt="RP"
		width="24"
		height="24"
		className="rp-block-icon"
	/>
);

/**
 * Block Placeholder Component
 *
 * Renders a styled placeholder for Recruiting Playbook blocks.
 *
 * @param {Object} props            Component props.
 * @param {string} props.label      Block label/title.
 * @param {string} props.summary    Configuration summary.
 * @param {string} props.helpText   Help text description.
 * @param {string} props.docSlug    Documentation slug for link.
 * @param {string} props.shortcode  Shortcode alternative (optional).
 * @return {JSX.Element} The placeholder component.
 */
export function BlockPlaceholder( {
	label,
	summary,
	helpText,
	docAnchor = '',
	shortcode = '',
} ) {
	const baseUrl = 'https://developer.recruiting-playbook.de/docs/gutenberg-blocks';
	const docUrl = docAnchor ? `${ baseUrl }#${ docAnchor }` : baseUrl;

	return (
		<Placeholder
			icon={ <RPIcon /> }
			label={ label }
			instructions={ summary }
			className="rp-block-placeholder"
		>
			{ helpText && (
				<p className="components-placeholder__learn-more">{ helpText }</p>
			) }
			{ shortcode && (
				<p className="rp-block-placeholder__shortcode">
					<code>{ shortcode }</code>
				</p>
			) }
			<p className="rp-block-placeholder__link">
				<a href={ docUrl } target="_blank" rel="noopener noreferrer">
					{ __( 'Learn more', 'recruiting-playbook' ) }
					<svg
						width="14"
						height="14"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="2"
						strokeLinecap="round"
						strokeLinejoin="round"
						style={ { marginLeft: '4px', verticalAlign: 'middle' } }
					>
						<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
						<polyline points="15 3 21 3 21 9" />
						<line x1="10" y1="14" x2="21" y2="3" />
					</svg>
				</a>
			</p>
		</Placeholder>
	);
}

export { RPIcon };

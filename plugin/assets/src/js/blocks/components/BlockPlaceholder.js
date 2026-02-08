/**
 * Block Placeholder Component
 *
 * Styled placeholder for Recruiting Playbook blocks in the editor.
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';

/**
 * RP Logo Icon Component
 *
 * SVG version of the Recruiting Playbook logo.
 *
 * @return {JSX.Element} The RP icon.
 */
const RPIcon = () => (
	<svg
		width="24"
		height="24"
		viewBox="0 0 512 512"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		className="rp-block-icon"
	>
		<defs>
			<linearGradient
				id="rp-gradient"
				x1="0%"
				y1="100%"
				x2="100%"
				y2="0%"
			>
				<stop offset="0%" stopColor="#10B981" />
				<stop offset="100%" stopColor="#3B82F6" />
			</linearGradient>
		</defs>
		<path
			d="M0 32C0 14.3 14.3 0 32 0h448c17.7 0 32 14.3 32 32v384c0 17.7-14.3 32-32 32H384l128 64V32H0v448l128-64H32c-17.7 0-32-14.3-32-32V32z"
			fill="url(#rp-gradient)"
		/>
		<path
			d="M96 96h96c53 0 96 43 96 96s-43 96-96 96h-32v64H96V96zm64 128h32c17.7 0 32-14.3 32-32s-14.3-32-32-32h-32v64z"
			fill="white"
		/>
		<path
			d="M320 96h96c53 0 96 43 96 96s-43 96-96 96h-32v64h-64V96zm64 128h32c17.7 0 32-14.3 32-32s-14.3-32-32-32h-32v64z"
			fill="white"
		/>
	</svg>
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
	docSlug = 'blocks',
	shortcode = '',
} ) {
	const docUrl = `https://developer.recruiting-playbook.de/docs/${ docSlug }`;

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
					{ __( 'Mehr erfahren', 'recruiting-playbook' ) }
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

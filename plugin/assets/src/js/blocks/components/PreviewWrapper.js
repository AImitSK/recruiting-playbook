/**
 * PreviewWrapper Component
 *
 * Consistent wrapper for block previews in the editor.
 * Provides visual feedback and loading states.
 *
 * @package
 */

import { Spinner, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * PreviewWrapper - Wrapper component for block previews
 *
 * @param {Object}      props            Component props.
 * @param {boolean}     props.isLoading  Show loading spinner.
 * @param {boolean}     props.isEmpty    Show empty state.
 * @param {string}      props.emptyLabel Label for empty state.
 * @param {string}      props.icon       Dashicon name for empty state.
 * @param {JSX.Element} props.children   Content to render.
 * @param {string}      props.className  Additional CSS class.
 * @return {JSX.Element} The component.
 */
export function PreviewWrapper( {
	isLoading = false,
	isEmpty = false,
	emptyLabel,
	icon = 'businessman',
	children,
	className = '',
} ) {
	// Loading state.
	if ( isLoading ) {
		return (
			<div
				className={ `rp-block-preview rp-block-preview--loading ${ className }` }
			>
				<Spinner />
				<span className="rp-block-preview__loading-text">
					{ __( 'Loading preview…', 'recruiting-playbook' ) }
				</span>
			</div>
		);
	}

	// Empty state.
	if ( isEmpty ) {
		return (
			<Placeholder
				icon={ icon }
				label={
					emptyLabel || __( 'No content', 'recruiting-playbook' )
				}
				className={ `rp-block-preview rp-block-preview--empty ${ className }` }
			>
				<p>
					{ __(
						'Configure the settings in the sidebar.',
						'recruiting-playbook'
					) }
				</p>
			</Placeholder>
		);
	}

	// Content.
	return (
		<div className={ `rp-block-preview ${ className }` }>{ children }</div>
	);
}

/**
 * ServerSidePreview - Wrapper for server-side rendered blocks
 *
 * Shows a styled preview with optional overlay for editor interaction.
 *
 * @param {Object}  props             Component props.
 * @param {string}  props.html        Server-rendered HTML.
 * @param {boolean} props.isLoading   Show loading state.
 * @param {string}  props.className   Additional CSS class.
 * @param {boolean} props.showOverlay Show interaction overlay.
 * @return {JSX.Element} The component.
 */
export function ServerSidePreview( {
	html,
	isLoading = false,
	className = '',
	showOverlay = false,
} ) {
	if ( isLoading ) {
		return <PreviewWrapper isLoading={ true } className={ className } />;
	}

	if ( ! html ) {
		return (
			<PreviewWrapper
				isEmpty={ true }
				emptyLabel={ __(
					'No preview available',
					'recruiting-playbook'
				) }
				className={ className }
			/>
		);
	}

	return (
		<div
			className={ `rp-block-preview rp-block-preview--server-side ${ className }` }
		>
			{ showOverlay && (
				<div className="rp-block-preview__overlay">
					<span>
						{ __(
							'Click to edit',
							'recruiting-playbook'
						) }
					</span>
				</div>
			) }
			<div
				className="rp-block-preview__content"
				dangerouslySetInnerHTML={ { __html: html } }
			/>
		</div>
	);
}

export default PreviewWrapper;

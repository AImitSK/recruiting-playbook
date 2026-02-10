/**
 * Tooltip Component
 *
 * @package RecruitingPlaybook
 */

import { useState, forwardRef } from '@wordpress/element';
import { Info } from 'lucide-react';

/**
 * Tooltip Provider (wrapper, currently just passes children)
 */
const TooltipProvider = ( { children } ) => children;

/**
 * Tooltip Container
 */
const Tooltip = ( { children } ) => {
	return <div style={ { position: 'relative', display: 'inline-flex' } }>{ children }</div>;
};

/**
 * Tooltip Trigger - the element that triggers the tooltip
 */
const TooltipTrigger = forwardRef( ( { children, asChild, ...props }, ref ) => {
	return (
		<span ref={ ref } { ...props } style={ { cursor: 'help', display: 'inline-flex' } }>
			{ children }
		</span>
	);
} );
TooltipTrigger.displayName = 'TooltipTrigger';

/**
 * Tooltip Content - the tooltip popup
 */
const TooltipContent = forwardRef( ( { children, className, side = 'top', ...props }, ref ) => {
	return (
		<div
			ref={ ref }
			role="tooltip"
			className={ className }
			style={ {
				position: 'absolute',
				zIndex: 50,
				bottom: side === 'top' ? '100%' : 'auto',
				top: side === 'bottom' ? '100%' : 'auto',
				left: '50%',
				transform: 'translateX(-50%)',
				marginBottom: side === 'top' ? '0.5rem' : 0,
				marginTop: side === 'bottom' ? '0.5rem' : 0,
				padding: '0.5rem 0.75rem',
				backgroundColor: '#18181b',
				color: '#fafafa',
				fontSize: '0.75rem',
				lineHeight: '1.4',
				borderRadius: '0.375rem',
				whiteSpace: 'nowrap',
				boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
				pointerEvents: 'none',
			} }
			{ ...props }
		>
			{ children }
		</div>
	);
} );
TooltipContent.displayName = 'TooltipContent';

/**
 * Simple InfoTooltip - combines trigger + content with hover state
 */
const InfoTooltip = ( { content, size = 14 } ) => {
	const [ isVisible, setIsVisible ] = useState( false );

	return (
		<div
			style={ { position: 'relative', display: 'inline-flex', alignItems: 'center' } }
			onMouseEnter={ () => setIsVisible( true ) }
			onMouseLeave={ () => setIsVisible( false ) }
		>
			<Info
				style={ {
					width: size,
					height: size,
					color: '#9ca3af',
					cursor: 'help',
				} }
			/>
			{ isVisible && (
				<div
					role="tooltip"
					style={ {
						position: 'absolute',
						zIndex: 50,
						bottom: '100%',
						left: '50%',
						transform: 'translateX(-50%)',
						marginBottom: '0.5rem',
						padding: '0.5rem 0.75rem',
						backgroundColor: '#18181b',
						color: '#fafafa',
						fontSize: '0.75rem',
						lineHeight: '1.4',
						borderRadius: '0.375rem',
						whiteSpace: 'nowrap',
						boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
						maxWidth: '250px',
						whiteSpace: 'normal',
						textAlign: 'center',
					} }
				>
					{ content }
				</div>
			) }
		</div>
	);
};

export { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider, InfoTooltip };

/**
 * Switch Component (shadcn/ui style)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Switch = forwardRef( ( { className, checked, onCheckedChange, disabled, ...props }, ref ) => {
	const handleClick = () => {
		if ( ! disabled && onCheckedChange ) {
			onCheckedChange( ! checked );
		}
	};

	const handleKeyDown = ( e ) => {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			handleClick();
		}
	};

	return (
		<button
			type="button"
			role="switch"
			aria-checked={ checked }
			disabled={ disabled }
			className={ cn(
				'rp-peer rp-inline-flex rp-h-6 rp-w-11 rp-shrink-0 rp-cursor-pointer rp-items-center rp-rounded-full rp-border-2 rp-border-transparent rp-transition-colors focus-visible:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-ring focus-visible:rp-ring-offset-2 focus-visible:rp-ring-offset-background disabled:rp-cursor-not-allowed disabled:rp-opacity-50',
				className
			) }
			style={ {
				backgroundColor: checked ? '#1d71b8' : '#e5e7eb',
				width: '2.75rem',
				height: '1.5rem',
				borderRadius: '9999px',
				position: 'relative',
				cursor: disabled ? 'not-allowed' : 'pointer',
				opacity: disabled ? 0.5 : 1,
			} }
			onClick={ handleClick }
			onKeyDown={ handleKeyDown }
			ref={ ref }
			{ ...props }
		>
			<span
				style={ {
					display: 'block',
					width: '1.25rem',
					height: '1.25rem',
					borderRadius: '9999px',
					backgroundColor: '#ffffff',
					boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
					transition: 'transform 150ms',
					transform: checked ? 'translateX(1.25rem)' : 'translateX(0.125rem)',
				} }
			/>
		</button>
	);
} );
Switch.displayName = 'Switch';

export { Switch };

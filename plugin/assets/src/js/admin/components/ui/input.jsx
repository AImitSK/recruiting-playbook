/**
 * Input Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Input = forwardRef( ( { className, type, ...props }, ref ) => {
	return (
		<input
			type={ type }
			className={ cn(
				'rp-flex rp-h-10 rp-w-full rp-rounded-md rp-border rp-border-input rp-bg-background rp-px-3 rp-py-2 rp-text-sm rp-ring-offset-background file:rp-border-0 file:rp-bg-transparent file:rp-text-sm file:rp-font-medium file:rp-text-foreground placeholder:rp-text-muted-foreground focus-visible:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-ring focus-visible:rp-ring-offset-2 disabled:rp-cursor-not-allowed disabled:rp-opacity-50',
				className
			) }
			ref={ ref }
			{ ...props }
		/>
	);
} );
Input.displayName = 'Input';

export { Input };

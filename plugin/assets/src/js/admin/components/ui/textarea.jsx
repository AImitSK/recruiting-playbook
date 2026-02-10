/**
 * Textarea Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Textarea = forwardRef( ( { className, ...props }, ref ) => {
	return (
		<textarea
			className={ cn(
				'rp-flex rp-min-h-[80px] rp-w-full rp-rounded-md rp-border rp-border-input rp-bg-background rp-px-3 rp-py-2 rp-text-sm rp-ring-offset-background placeholder:rp-text-muted-foreground focus-visible:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-ring focus-visible:rp-ring-offset-2 disabled:rp-cursor-not-allowed disabled:rp-opacity-50',
				className
			) }
			ref={ ref }
			{ ...props }
		/>
	);
} );
Textarea.displayName = 'Textarea';

export { Textarea };

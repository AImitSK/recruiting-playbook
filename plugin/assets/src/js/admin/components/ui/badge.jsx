/**
 * Badge Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { cva } from 'class-variance-authority';
import { cn } from '../../lib/utils';

const badgeVariants = cva(
	'rp-inline-flex rp-items-center rp-rounded-full rp-border rp-px-2.5 rp-py-0.5 rp-text-xs rp-font-semibold rp-transition-colors focus:rp-outline-none focus:rp-ring-2 focus:rp-ring-ring focus:rp-ring-offset-2',
	{
		variants: {
			variant: {
				default:
					'rp-border-transparent rp-bg-primary rp-text-primary-foreground hover:rp-bg-primary/80',
				secondary:
					'rp-border-transparent rp-bg-secondary rp-text-secondary-foreground hover:rp-bg-secondary/80',
				destructive:
					'rp-border-transparent rp-bg-destructive rp-text-destructive-foreground hover:rp-bg-destructive/80',
				outline: 'rp-text-foreground',
				success:
					'rp-border-transparent rp-bg-green-100 rp-text-green-700',
				warning:
					'rp-border-transparent rp-bg-yellow-100 rp-text-yellow-700',
				info: 'rp-border-transparent rp-bg-blue-100 rp-text-blue-700',
				purple:
					'rp-border-transparent rp-bg-purple-100 rp-text-purple-700',
			},
		},
		defaultVariants: {
			variant: 'default',
		},
	}
);

function Badge( { className, variant, ...props } ) {
	return (
		<div className={ cn( badgeVariants( { variant } ), className ) } { ...props } />
	);
}

export { Badge, badgeVariants };

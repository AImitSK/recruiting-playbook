/**
 * Button Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { Slot } from '@radix-ui/react-slot';
import { cva } from 'class-variance-authority';
import { cn } from '../../lib/utils';

const buttonVariants = cva(
	'rp-inline-flex rp-items-center rp-justify-center rp-gap-2 rp-whitespace-nowrap rp-rounded-md rp-text-sm rp-font-medium rp-transition-colors focus-visible:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-ring focus-visible:rp-ring-offset-2 disabled:rp-pointer-events-none disabled:rp-opacity-50',
	{
		variants: {
			variant: {
				default: '',
				destructive:
					'rp-bg-destructive rp-text-destructive-foreground hover:rp-bg-destructive/90',
				outline: '',
				secondary:
					'rp-bg-secondary rp-text-secondary-foreground hover:rp-bg-secondary/80',
				ghost: 'hover:rp-bg-accent hover:rp-text-accent-foreground',
				link: 'rp-text-primary rp-underline-offset-4 hover:rp-underline',
			},
			size: {
				default: 'rp-h-10 rp-px-4 rp-py-2',
				sm: 'rp-h-9 rp-rounded-md rp-px-3',
				lg: 'rp-h-11 rp-rounded-md rp-px-8',
				icon: 'rp-h-10 rp-w-10',
			},
		},
		defaultVariants: {
			variant: 'default',
			size: 'default',
		},
	}
);

const Button = forwardRef(
	( { className, variant, size, asChild = false, style, ...props }, ref ) => {
		const Comp = asChild ? Slot : 'button';

		// Brand colors for variants
		const variantStyles = {
			default: {
				backgroundColor: '#1d71b8',
				color: '#ffffff',
				border: 'none',
			},
			outline: {
				backgroundColor: 'transparent',
				color: '#1d71b8',
				border: '1px solid #1d71b8',
			},
		};

		const hoverStyles = {
			default: { backgroundColor: '#36a9e1' },
			outline: { backgroundColor: '#f0f7fc' },
		};

		const baseStyle = variantStyles[ variant ] || variantStyles.default;

		return (
			<Comp
				className={ cn( buttonVariants( { variant, size, className } ) ) }
				ref={ ref }
				style={ { ...baseStyle, ...style } }
				onMouseEnter={ ( e ) => {
					const hover = hoverStyles[ variant ] || hoverStyles.default;
					Object.assign( e.currentTarget.style, hover );
				} }
				onMouseLeave={ ( e ) => {
					const base = variantStyles[ variant ] || variantStyles.default;
					Object.assign( e.currentTarget.style, base );
				} }
				{ ...props }
			/>
		);
	}
);
Button.displayName = 'Button';

export { Button, buttonVariants };

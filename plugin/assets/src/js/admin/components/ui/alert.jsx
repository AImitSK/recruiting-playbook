/**
 * Alert Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cva } from 'class-variance-authority';
import { cn } from '../../lib/utils';

const alertVariants = cva(
	'rp-relative rp-w-full rp-rounded-lg rp-border rp-p-4 [&>svg~*]:rp-pl-7 [&>svg+div]:rp-translate-y-[-3px] [&>svg]:rp-absolute [&>svg]:rp-left-4 [&>svg]:rp-top-4 [&>svg]:rp-text-foreground',
	{
		variants: {
			variant: {
				default: 'rp-bg-background rp-text-foreground',
				success: '',
				destructive:
					'rp-border-destructive/50 rp-text-destructive rp-bg-destructive/10 [&>svg]:rp-text-destructive',
				warning:
					'rp-border-yellow-500/50 rp-text-yellow-700 rp-bg-yellow-50 [&>svg]:rp-text-yellow-600',
				info: 'rp-border-blue-500/50 rp-text-blue-700 rp-bg-blue-50 [&>svg]:rp-text-blue-600',
			},
		},
		defaultVariants: {
			variant: 'default',
		},
	}
);

const Alert = forwardRef( ( { className, variant, style, ...props }, ref ) => {
	// Brand colors for variants
	const variantStyles = {
		success: {
			backgroundColor: '#e6f5ec',
			borderLeft: '4px solid #2fac66',
			borderTop: '1px solid #c3e6d1',
			borderRight: '1px solid #c3e6d1',
			borderBottom: '1px solid #c3e6d1',
			color: '#2fac66',
		},
		info: {
			backgroundColor: '#edf4f9',
			borderLeft: '4px solid #1d71b8',
			borderTop: '1px solid #d1e3f0',
			borderRight: '1px solid #d1e3f0',
			borderBottom: '1px solid #d1e3f0',
			color: '#1d71b8',
		},
	};

	const baseStyle = variantStyles[ variant ] || {};

	return (
		<div
			ref={ ref }
			role="alert"
			className={ cn( alertVariants( { variant } ), className ) }
			style={ {
				padding: '1rem 1rem 1rem 3rem',
				position: 'relative',
				borderRadius: '0.375rem',
				...baseStyle,
				...style,
			} }
			{ ...props }
		/>
	);
} );
Alert.displayName = 'Alert';

const AlertTitle = forwardRef( ( { className, ...props }, ref ) => (
	<h5
		ref={ ref }
		className={ cn(
			'rp-mb-1 rp-font-medium rp-leading-none rp-tracking-tight',
			className
		) }
		style={ { fontWeight: 500, marginBottom: '0.25rem' } }
		{ ...props }
	/>
) );
AlertTitle.displayName = 'AlertTitle';

const AlertDescription = forwardRef( ( { className, ...props }, ref ) => (
	<div
		ref={ ref }
		className={ cn( 'rp-text-sm [&_p]:rp-leading-relaxed', className ) }
		style={ { fontSize: '0.875rem' } }
		{ ...props }
	/>
) );
AlertDescription.displayName = 'AlertDescription';

export { Alert, AlertTitle, AlertDescription };

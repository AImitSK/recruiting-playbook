/**
 * Card Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Card = forwardRef( ( { className, ...props }, ref ) => (
	<div
		ref={ ref }
		className={ cn(
			'rp-rounded-lg rp-border rp-bg-card rp-text-card-foreground rp-shadow-sm',
			className
		) }
		style={ {
			backgroundColor: '#fff',
			border: '1px solid hsl(214.3, 31.8%, 91.4%)',
			borderRadius: '0.5rem',
			boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
		} }
		{ ...props }
	/>
) );
Card.displayName = 'Card';

const CardHeader = forwardRef( ( { className, ...props }, ref ) => (
	<div
		ref={ ref }
		className={ cn( 'rp-flex rp-flex-col rp-space-y-1.5 rp-p-6', className ) }
		style={ { padding: '1.5rem', paddingBottom: '0.75rem' } }
		{ ...props }
	/>
) );
CardHeader.displayName = 'CardHeader';

const CardTitle = forwardRef( ( { className, ...props }, ref ) => (
	<h3
		ref={ ref }
		className={ cn(
			'rp-text-lg rp-font-semibold rp-leading-none rp-tracking-tight',
			className
		) }
		style={ { fontSize: '1.125rem', fontWeight: 600, lineHeight: 1.2 } }
		{ ...props }
	/>
) );
CardTitle.displayName = 'CardTitle';

const CardDescription = forwardRef( ( { className, ...props }, ref ) => (
	<p
		ref={ ref }
		className={ cn( 'rp-text-sm rp-text-muted-foreground', className ) }
		style={ { fontSize: '0.875rem', color: 'hsl(215.4, 16.3%, 46.9%)', marginTop: '0.25rem' } }
		{ ...props }
	/>
) );
CardDescription.displayName = 'CardDescription';

const CardContent = forwardRef( ( { className, ...props }, ref ) => (
	<div
		ref={ ref }
		className={ cn( 'rp-p-6 rp-pt-0', className ) }
		style={ { padding: '1.5rem', paddingTop: '0.75rem' } }
		{ ...props }
	/>
) );
CardContent.displayName = 'CardContent';

const CardFooter = forwardRef( ( { className, ...props }, ref ) => (
	<div
		ref={ ref }
		className={ cn( 'rp-flex rp-items-center rp-p-6 rp-pt-0', className ) }
		{ ...props }
	/>
) );
CardFooter.displayName = 'CardFooter';

export {
	Card,
	CardHeader,
	CardFooter,
	CardTitle,
	CardDescription,
	CardContent,
};

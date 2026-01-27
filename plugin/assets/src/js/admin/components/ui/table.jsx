/**
 * Table Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Table = forwardRef( ( { className, ...props }, ref ) => (
	<div className="rp-relative rp-w-full rp-overflow-auto">
		<table
			ref={ ref }
			className={ cn( 'rp-w-full rp-caption-bottom rp-text-sm', className ) }
			{ ...props }
		/>
	</div>
) );
Table.displayName = 'Table';

const TableHeader = forwardRef( ( { className, ...props }, ref ) => (
	<thead
		ref={ ref }
		className={ cn( '[&_tr]:rp-border-b', className ) }
		{ ...props }
	/>
) );
TableHeader.displayName = 'TableHeader';

const TableBody = forwardRef( ( { className, ...props }, ref ) => (
	<tbody
		ref={ ref }
		className={ cn( '[&_tr:last-child]:rp-border-0', className ) }
		{ ...props }
	/>
) );
TableBody.displayName = 'TableBody';

const TableFooter = forwardRef( ( { className, ...props }, ref ) => (
	<tfoot
		ref={ ref }
		className={ cn(
			'rp-border-t rp-bg-muted/50 rp-font-medium [&>tr]:last:rp-border-b-0',
			className
		) }
		{ ...props }
	/>
) );
TableFooter.displayName = 'TableFooter';

const TableRow = forwardRef( ( { className, ...props }, ref ) => (
	<tr
		ref={ ref }
		className={ cn(
			'rp-border-b rp-transition-colors hover:rp-bg-muted/50 data-[state=selected]:rp-bg-muted',
			className
		) }
		{ ...props }
	/>
) );
TableRow.displayName = 'TableRow';

const TableHead = forwardRef( ( { className, ...props }, ref ) => (
	<th
		ref={ ref }
		className={ cn(
			'rp-h-12 rp-px-4 rp-text-left rp-align-middle rp-font-medium rp-text-muted-foreground [&:has([role=checkbox])]:rp-pr-0',
			className
		) }
		style={ { padding: '0.75rem 1rem', fontWeight: 500, color: 'hsl(215.4, 16.3%, 46.9%)' } }
		{ ...props }
	/>
) );
TableHead.displayName = 'TableHead';

const TableCell = forwardRef( ( { className, ...props }, ref ) => (
	<td
		ref={ ref }
		className={ cn(
			'rp-p-4 rp-align-middle [&:has([role=checkbox])]:rp-pr-0',
			className
		) }
		style={ { padding: '0.75rem 1rem' } }
		{ ...props }
	/>
) );
TableCell.displayName = 'TableCell';

const TableCaption = forwardRef( ( { className, ...props }, ref ) => (
	<caption
		ref={ ref }
		className={ cn( 'rp-mt-4 rp-text-sm rp-text-muted-foreground', className ) }
		{ ...props }
	/>
) );
TableCaption.displayName = 'TableCaption';

export {
	Table,
	TableHeader,
	TableBody,
	TableFooter,
	TableHead,
	TableRow,
	TableCell,
	TableCaption,
};

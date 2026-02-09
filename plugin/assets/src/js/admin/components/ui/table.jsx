/**
 * Table Component (shadcn/ui style)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Table = forwardRef( ( { className, style, ...props }, ref ) => (
	<div
		className={ cn( 'rp-table-wrapper', className ) }
		style={ {
			position: 'relative',
			width: '100%',
			overflow: 'auto',
		} }
	>
		<table
			ref={ ref }
			style={ {
				width: '100%',
				captionSide: 'bottom',
				fontSize: '0.875rem',
				borderCollapse: 'collapse',
				...style,
			} }
			{ ...props }
		/>
	</div>
) );
Table.displayName = 'Table';

const TableHeader = forwardRef( ( { className, ...props }, ref ) => (
	<thead
		ref={ ref }
		className={ cn( 'rp-table-header', className ) }
		style={ {
			backgroundColor: '#fafafa',
		} }
		{ ...props }
	/>
) );
TableHeader.displayName = 'TableHeader';

const TableBody = forwardRef( ( { className, ...props }, ref ) => (
	<tbody
		ref={ ref }
		className={ cn( 'rp-table-body', className ) }
		{ ...props }
	/>
) );
TableBody.displayName = 'TableBody';

const TableFooter = forwardRef( ( { className, ...props }, ref ) => (
	<tfoot
		ref={ ref }
		className={ cn( 'rp-table-footer', className ) }
		style={ {
			borderTop: '1px solid #e5e7eb',
			backgroundColor: 'rgba(250, 250, 250, 0.5)',
			fontWeight: 500,
		} }
		{ ...props }
	/>
) );
TableFooter.displayName = 'TableFooter';

const TableRow = forwardRef( ( { className, isHeader = false, ...props }, ref ) => (
	<tr
		ref={ ref }
		className={ cn( 'rp-table-row', className ) }
		style={ {
			borderBottom: '1px solid #e5e7eb',
			transition: 'background-color 150ms ease',
		} }
		onMouseEnter={ ( e ) => {
			if ( ! isHeader ) {
				e.currentTarget.style.backgroundColor = '#fafafa';
			}
		} }
		onMouseLeave={ ( e ) => {
			if ( ! isHeader ) {
				e.currentTarget.style.backgroundColor = 'transparent';
			}
		} }
		{ ...props }
	/>
) );
TableRow.displayName = 'TableRow';

const TableHead = forwardRef( ( { className, style, ...props }, ref ) => (
	<th
		ref={ ref }
		className={ cn( 'rp-table-head', className ) }
		style={ {
			height: '3rem',
			padding: '0.75rem 1rem',
			textAlign: 'left',
			verticalAlign: 'middle',
			fontWeight: 500,
			fontSize: '0.75rem',
			textTransform: 'uppercase',
			letterSpacing: '0.05em',
			color: '#71717a',
			whiteSpace: 'nowrap',
			...style,
		} }
		{ ...props }
	/>
) );
TableHead.displayName = 'TableHead';

const TableCell = forwardRef( ( { className, style, ...props }, ref ) => (
	<td
		ref={ ref }
		className={ cn( 'rp-table-cell', className ) }
		style={ {
			padding: '1rem',
			verticalAlign: 'middle',
			color: '#1f2937',
			...style,
		} }
		{ ...props }
	/>
) );
TableCell.displayName = 'TableCell';

const TableCaption = forwardRef( ( { className, ...props }, ref ) => (
	<caption
		ref={ ref }
		className={ cn( 'rp-table-caption', className ) }
		style={ {
			marginTop: '1rem',
			fontSize: '0.875rem',
			color: '#71717a',
		} }
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

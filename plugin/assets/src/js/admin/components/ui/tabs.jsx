/**
 * Tabs Component (shadcn/ui style)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef, createContext, useContext } from '@wordpress/element';
import { cn } from '../../lib/utils';

const TabsContext = createContext( {} );

const Tabs = forwardRef( ( { className, value, onValueChange, children, ...props }, ref ) => {
	return (
		<TabsContext.Provider value={ { value, onValueChange } }>
			<div
				ref={ ref }
				className={ cn( 'rp-tabs', className ) }
				{ ...props }
			>
				{ children }
			</div>
		</TabsContext.Provider>
	);
} );
Tabs.displayName = 'Tabs';

const TabsList = forwardRef( ( { className, ...props }, ref ) => (
	<div
		ref={ ref }
		className={ cn(
			'rp-inline-flex rp-h-10 rp-items-center rp-justify-start rp-rounded-md rp-bg-muted rp-p-1 rp-text-muted-foreground',
			className
		) }
		style={ {
			display: 'inline-flex',
			height: '2.5rem',
			alignItems: 'center',
			justifyContent: 'flex-start',
			borderRadius: '0.5rem',
			backgroundColor: '#f4f4f5',
			padding: '0.25rem',
			gap: '0.125rem',
		} }
		role="tablist"
		{ ...props }
	/>
) );
TabsList.displayName = 'TabsList';

const TabsTrigger = forwardRef( ( { className, value, children, count, ...props }, ref ) => {
	const { value: selectedValue, onValueChange } = useContext( TabsContext );
	const isSelected = selectedValue === value;

	return (
		<button
			ref={ ref }
			type="button"
			role="tab"
			aria-selected={ isSelected }
			className={ cn(
				'rp-inline-flex rp-items-center rp-justify-center rp-whitespace-nowrap rp-rounded-sm rp-px-3 rp-py-1.5 rp-text-sm rp-font-medium rp-ring-offset-background rp-transition-all focus-visible:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-ring focus-visible:rp-ring-offset-2 disabled:rp-pointer-events-none disabled:rp-opacity-50',
				className
			) }
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				justifyContent: 'center',
				whiteSpace: 'nowrap',
				borderRadius: '0.375rem',
				padding: '0.375rem 0.75rem',
				fontSize: '0.875rem',
				fontWeight: 500,
				transition: 'all 150ms',
				cursor: 'pointer',
				border: 'none',
				backgroundColor: isSelected ? '#ffffff' : 'transparent',
				color: isSelected ? '#1f2937' : '#71717a',
				boxShadow: isSelected ? '0 1px 2px 0 rgba(0, 0, 0, 0.05)' : 'none',
				gap: '0.375rem',
			} }
			onClick={ () => onValueChange && onValueChange( value ) }
			{ ...props }
		>
			{ children }
			{ count !== undefined && count > 0 && (
				<span
					style={ {
						display: 'inline-flex',
						alignItems: 'center',
						justifyContent: 'center',
						minWidth: '1.25rem',
						height: '1.25rem',
						padding: '0 0.375rem',
						borderRadius: '9999px',
						fontSize: '0.75rem',
						fontWeight: 500,
						backgroundColor: isSelected ? '#1d71b8' : '#e4e4e7',
						color: isSelected ? '#ffffff' : '#71717a',
					} }
				>
					{ count }
				</span>
			) }
		</button>
	);
} );
TabsTrigger.displayName = 'TabsTrigger';

const TabsContent = forwardRef( ( { className, value, children, ...props }, ref ) => {
	const { value: selectedValue } = useContext( TabsContext );
	const isSelected = selectedValue === value;

	if ( ! isSelected ) {
		return null;
	}

	return (
		<div
			ref={ ref }
			role="tabpanel"
			className={ cn(
				'rp-mt-2 rp-ring-offset-background focus-visible:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-ring focus-visible:rp-ring-offset-2',
				className
			) }
			style={ { marginTop: '0.5rem' } }
			{ ...props }
		>
			{ children }
		</div>
	);
} );
TabsContent.displayName = 'TabsContent';

export { Tabs, TabsList, TabsTrigger, TabsContent };

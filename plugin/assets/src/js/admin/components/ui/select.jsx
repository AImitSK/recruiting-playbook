/**
 * Select Component (shadcn/ui style)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { ChevronDown } from 'lucide-react';
import { cn } from '../../lib/utils';

const Select = forwardRef( ( { className, children, style, ...props }, ref ) => {
	return (
		<div className="rp-select-wrapper" style={ { position: 'relative' } }>
			<select
				className={ cn( 'rp-select', className ) }
				ref={ ref }
				style={ {
					display: 'block',
					height: '40px',
					borderRadius: '6px',
					border: '1px solid #e5e7eb',
					backgroundColor: '#fff',
					padding: '0 36px 0 12px',
					fontSize: '14px',
					lineHeight: '38px',
					color: '#18181b',
					cursor: 'pointer',
					outline: 'none',
					...style,
				} }
				{ ...props }
			>
				{ children }
			</select>
			<ChevronDown
				style={ {
					position: 'absolute',
					right: '12px',
					top: '50%',
					transform: 'translateY(-50%)',
					width: '16px',
					height: '16px',
					color: '#71717a',
					pointerEvents: 'none',
				} }
			/>
			<style>{ `
				.rp-select::-ms-expand {
					display: none !important;
				}
			` }</style>
		</div>
	);
} );
Select.displayName = 'Select';

const SelectOption = ( { children, ...props } ) => {
	return <option { ...props }>{ children }</option>;
};

export { Select, SelectOption };

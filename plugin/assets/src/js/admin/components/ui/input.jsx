/**
 * Input Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Input = forwardRef( ( { className, type, style, ...props }, ref ) => {
	return (
		<input
			type={ type }
			className={ cn( 'rp-input', className ) }
			ref={ ref }
			style={ {
				display: 'block',
				width: '100%',
				height: '40px',
				padding: '0.5rem 0.75rem',
				fontSize: '0.875rem',
				lineHeight: '1.5',
				color: '#18181b',
				backgroundColor: '#ffffff',
				border: '1px solid #d1d5db',
				borderRadius: '6px',
				outline: 'none',
				transition: 'border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out',
				...style,
			} }
			onFocus={ ( e ) => {
				e.target.style.borderColor = '#1d71b8';
				e.target.style.boxShadow = '0 0 0 3px rgba(29, 113, 184, 0.1)';
			} }
			onBlur={ ( e ) => {
				e.target.style.borderColor = style?.borderColor || '#d1d5db';
				e.target.style.boxShadow = 'none';
			} }
			{ ...props }
		/>
	);
} );
Input.displayName = 'Input';

export { Input };

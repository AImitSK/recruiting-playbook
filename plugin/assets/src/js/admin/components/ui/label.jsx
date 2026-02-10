/**
 * Label Component (shadcn/ui)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Label = forwardRef( ( { className, ...props }, ref ) => {
	return (
		<label
			className={ cn(
				'rp-text-sm rp-font-medium rp-leading-none peer-disabled:rp-cursor-not-allowed peer-disabled:rp-opacity-70',
				className
			) }
			ref={ ref }
			style={ {
				display: 'block',
				marginBottom: '0.5rem',
				fontSize: '0.875rem',
				fontWeight: 500,
				color: '#374151',
			} }
			{ ...props }
		/>
	);
} );
Label.displayName = 'Label';

export { Label };

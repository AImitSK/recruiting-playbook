/**
 * Spinner Component (shadcn/ui style)
 *
 * @package RecruitingPlaybook
 */

import { cn } from '../../lib/utils';

function Spinner( { className, size = 'default', ...props } ) {
	const sizeStyles = {
		sm: { width: '1rem', height: '1rem', borderWidth: '2px' },
		default: { width: '1.5rem', height: '1.5rem', borderWidth: '2px' },
		lg: { width: '2rem', height: '2rem', borderWidth: '3px' },
	};

	const style = sizeStyles[ size ] || sizeStyles.default;

	return (
		<div
			className={ cn( 'rp-animate-spin', className ) }
			style={ {
				...style,
				borderRadius: '9999px',
				borderColor: '#e5e7eb',
				borderTopColor: '#1d71b8',
				borderStyle: 'solid',
				animation: 'rp-spin 0.8s linear infinite',
			} }
			{ ...props }
		>
			<style>
				{ `@keyframes rp-spin { to { transform: rotate(360deg); } }` }
			</style>
		</div>
	);
}

export { Spinner };

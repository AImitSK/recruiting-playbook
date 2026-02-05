/**
 * Slider Component
 *
 * Range-Slider mit Label, Wertanzeige und optionaler Einheit.
 *
 * @package RecruitingPlaybook
 */

import { forwardRef, useCallback } from '@wordpress/element';
import { cn } from '../../lib/utils';

const Slider = forwardRef(
	(
		{
			className,
			value = 0,
			onChange,
			min = 0,
			max = 100,
			step = 1,
			disabled = false,
			label,
			unit = '',
			showValue = true,
			...props
		},
		ref
	) => {
		const handleChange = useCallback(
			( e ) => {
				const newValue = parseFloat( e.target.value );
				if ( onChange ) {
					onChange( newValue );
				}
			},
			[ onChange ]
		);

		// Calculate percentage for track fill
		const percentage = ( ( value - min ) / ( max - min ) ) * 100;

		// Format display value (avoid floating point display issues)
		const displayValue = Number.isInteger( step )
			? Math.round( value )
			: value.toFixed( step < 1 ? Math.ceil( -Math.log10( step ) ) : 0 );

		return (
			<div className={ cn( 'rp-flex rp-flex-col rp-gap-1.5', className ) } { ...props }>
				{ /* Label row with value */ }
				{ ( label || showValue ) && (
					<div className="rp-flex rp-items-center rp-justify-between">
						{ label && (
							<span className="rp-text-sm rp-font-medium rp-text-gray-700">
								{ label }
							</span>
						) }
						{ showValue && (
							<span className="rp-text-sm rp-font-mono rp-text-gray-500">
								{ displayValue }
								{ unit }
							</span>
						) }
					</div>
				) }

				{ /* Slider track */ }
				<div className="rp-relative rp-h-5 rp-flex rp-items-center">
					<input
						type="range"
						ref={ ref }
						value={ value }
						onChange={ handleChange }
						min={ min }
						max={ max }
						step={ step }
						disabled={ disabled }
						className={ cn(
							'rp-w-full rp-h-2 rp-rounded-full rp-appearance-none rp-cursor-pointer',
							'focus:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-blue-500',
							disabled && 'rp-opacity-50 rp-cursor-not-allowed'
						) }
						style={ {
							background: `linear-gradient(to right, #1d71b8 0%, #1d71b8 ${ percentage }%, #e5e7eb ${ percentage }%, #e5e7eb 100%)`,
						} }
						aria-label={ label }
						aria-valuemin={ min }
						aria-valuemax={ max }
						aria-valuenow={ value }
						aria-valuetext={ `${ displayValue }${ unit }` }
					/>
				</div>

				{ /* Min/Max labels (optional) */ }
				{ props.showMinMax && (
					<div className="rp-flex rp-justify-between rp-text-xs rp-text-gray-400">
						<span>
							{ min }
							{ unit }
						</span>
						<span>
							{ max }
							{ unit }
						</span>
					</div>
				) }
			</div>
		);
	}
);

Slider.displayName = 'Slider';

// Custom CSS for slider thumb (injected once)
const sliderStyles = `
	input[type="range"]::-webkit-slider-thumb {
		-webkit-appearance: none;
		appearance: none;
		width: 18px;
		height: 18px;
		border-radius: 50%;
		background: #ffffff;
		border: 2px solid #1d71b8;
		cursor: pointer;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
		transition: transform 150ms ease;
	}
	input[type="range"]::-webkit-slider-thumb:hover {
		transform: scale(1.1);
	}
	input[type="range"]::-moz-range-thumb {
		width: 18px;
		height: 18px;
		border-radius: 50%;
		background: #ffffff;
		border: 2px solid #1d71b8;
		cursor: pointer;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
	}
	input[type="range"]:disabled::-webkit-slider-thumb {
		cursor: not-allowed;
	}
	input[type="range"]:disabled::-moz-range-thumb {
		cursor: not-allowed;
	}
`;

// Inject styles once
if ( typeof document !== 'undefined' && ! document.getElementById( 'rp-slider-styles' ) ) {
	const styleEl = document.createElement( 'style' );
	styleEl.id = 'rp-slider-styles';
	styleEl.textContent = sliderStyles;
	document.head.appendChild( styleEl );
}

export { Slider };

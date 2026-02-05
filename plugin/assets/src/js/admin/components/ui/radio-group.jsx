/**
 * RadioGroup Component
 *
 * Horizontale oder vertikale Radio-Button-Gruppe.
 * Unterstützt verschiedene Layouts: buttons, cards, inline.
 *
 * @package RecruitingPlaybook
 */

import { forwardRef, createContext, useContext, useCallback } from '@wordpress/element';
import { cn } from '../../lib/utils';

// Context für Radio-Gruppe
const RadioGroupContext = createContext( null );

const RadioGroup = forwardRef(
	(
		{
			className,
			value,
			onValueChange,
			disabled = false,
			orientation = 'horizontal',
			variant = 'default',
			children,
			...props
		},
		ref
	) => {
		return (
			<RadioGroupContext.Provider value={ { value, onValueChange, disabled, variant } }>
				<div
					ref={ ref }
					role="radiogroup"
					aria-orientation={ orientation }
					className={ cn(
						'rp-flex rp-gap-2',
						orientation === 'vertical' && 'rp-flex-col',
						orientation === 'horizontal' && 'rp-flex-row rp-flex-wrap',
						className
					) }
					{ ...props }
				>
					{ children }
				</div>
			</RadioGroupContext.Provider>
		);
	}
);

RadioGroup.displayName = 'RadioGroup';

const RadioGroupItem = forwardRef(
	( { className, value, disabled: itemDisabled = false, children, ...props }, ref ) => {
		const context = useContext( RadioGroupContext );

		if ( ! context ) {
			throw new Error( 'RadioGroupItem must be used within a RadioGroup' );
		}

		const { value: groupValue, onValueChange, disabled: groupDisabled, variant } = context;
		const isDisabled = groupDisabled || itemDisabled;
		const isSelected = groupValue === value;

		const handleClick = useCallback( () => {
			if ( ! isDisabled && onValueChange ) {
				onValueChange( value );
			}
		}, [ isDisabled, onValueChange, value ] );

		const handleKeyDown = useCallback(
			( e ) => {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					handleClick();
				}
			},
			[ handleClick ]
		);

		// Different variant styles
		const variantStyles = {
			default: cn(
				'rp-flex rp-items-center rp-gap-2 rp-cursor-pointer',
				isDisabled && 'rp-opacity-50 rp-cursor-not-allowed'
			),
			buttons: cn(
				'rp-px-3 rp-py-1.5 rp-text-sm rp-font-medium rp-rounded-md rp-border rp-transition-colors',
				'focus:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-blue-500',
				isSelected
					? 'rp-bg-blue-600 rp-text-white rp-border-blue-600'
					: 'rp-bg-white rp-text-gray-700 rp-border-gray-300 hover:rp-bg-gray-50',
				isDisabled && 'rp-opacity-50 rp-cursor-not-allowed',
				! isDisabled && 'rp-cursor-pointer'
			),
			cards: cn(
				'rp-flex rp-flex-col rp-items-center rp-p-4 rp-rounded-lg rp-border-2 rp-transition-colors',
				'focus:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-blue-500',
				isSelected
					? 'rp-border-blue-600 rp-bg-blue-50'
					: 'rp-border-gray-200 rp-bg-white hover:rp-border-gray-300',
				isDisabled && 'rp-opacity-50 rp-cursor-not-allowed',
				! isDisabled && 'rp-cursor-pointer'
			),
		};

		if ( variant === 'buttons' || variant === 'cards' ) {
			return (
				<button
					ref={ ref }
					type="button"
					role="radio"
					aria-checked={ isSelected }
					disabled={ isDisabled }
					onClick={ handleClick }
					className={ cn( variantStyles[ variant ], className ) }
					{ ...props }
				>
					{ children }
				</button>
			);
		}

		// Default variant with radio circle
		return (
			<label
				ref={ ref }
				className={ cn( variantStyles.default, className ) }
				{ ...props }
			>
				<button
					type="button"
					role="radio"
					aria-checked={ isSelected }
					disabled={ isDisabled }
					onClick={ handleClick }
					onKeyDown={ handleKeyDown }
					className={ cn(
						'rp-w-4 rp-h-4 rp-rounded-full rp-border-2 rp-flex rp-items-center rp-justify-center rp-transition-colors',
						'focus:rp-outline-none focus-visible:rp-ring-2 focus-visible:rp-ring-blue-500 focus-visible:rp-ring-offset-2',
						isSelected
							? 'rp-border-blue-600 rp-bg-blue-600'
							: 'rp-border-gray-300 rp-bg-white',
						isDisabled && 'rp-cursor-not-allowed'
					) }
				>
					{ isSelected && (
						<span className="rp-w-1.5 rp-h-1.5 rp-rounded-full rp-bg-white" />
					) }
				</button>
				<span className="rp-text-sm rp-text-gray-700">{ children }</span>
			</label>
		);
	}
);

RadioGroupItem.displayName = 'RadioGroupItem';

export { RadioGroup, RadioGroupItem };

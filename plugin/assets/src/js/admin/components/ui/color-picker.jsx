/**
 * ColorPicker Component
 *
 * Hex-Farbwähler mit Vorschau und manuellem Input.
 * Verwendet native HTML5 color input mit zusätzlichem Hex-Input.
 *
 * @package RecruitingPlaybook
 */

import { forwardRef, useState, useEffect, useCallback } from '@wordpress/element';
import { cn } from '../../lib/utils';

const ColorPicker = forwardRef(
	( { className, value = '#2563eb', onChange, disabled = false, label, ...props }, ref ) => {
		const [ inputValue, setInputValue ] = useState( value );

		// Sync inputValue with value prop
		useEffect( () => {
			setInputValue( value );
		}, [ value ] );

		// Validate hex color
		const isValidHex = useCallback( ( hex ) => {
			return /^#([0-9A-Fa-f]{3}){1,2}$/.test( hex );
		}, [] );

		// Normalize 3-digit hex to 6-digit
		const normalizeHex = useCallback( ( hex ) => {
			if ( hex.length === 4 ) {
				return '#' + hex[ 1 ] + hex[ 1 ] + hex[ 2 ] + hex[ 2 ] + hex[ 3 ] + hex[ 3 ];
			}
			return hex;
		}, [] );

		// Handle color input change (native picker)
		const handleColorChange = useCallback(
			( e ) => {
				const newColor = e.target.value;
				setInputValue( newColor );
				if ( onChange ) {
					onChange( newColor );
				}
			},
			[ onChange ]
		);

		// Handle text input change
		const handleInputChange = useCallback( ( e ) => {
			let newValue = e.target.value;

			// Add # if missing
			if ( newValue && ! newValue.startsWith( '#' ) ) {
				newValue = '#' + newValue;
			}

			setInputValue( newValue );
		}, [] );

		// Handle text input blur (validate and apply)
		const handleInputBlur = useCallback( () => {
			if ( isValidHex( inputValue ) ) {
				const normalized = normalizeHex( inputValue );
				setInputValue( normalized );
				if ( onChange && normalized !== value ) {
					onChange( normalized );
				}
			} else {
				// Reset to previous valid value
				setInputValue( value );
			}
		}, [ inputValue, value, onChange, isValidHex, normalizeHex ] );

		// Handle Enter key in text input
		const handleKeyDown = useCallback(
			( e ) => {
				if ( e.key === 'Enter' ) {
					handleInputBlur();
				}
			},
			[ handleInputBlur ]
		);

		return (
			<div
				className={ cn( 'rp-flex rp-items-center rp-gap-2', className ) }
				ref={ ref }
				{ ...props }
			>
				{ label && (
					<span className="rp-text-sm rp-font-medium rp-text-gray-700">{ label }</span>
				) }

				{/* Color swatch with native picker */}
				<div className="rp-relative">
					<input
						type="color"
						value={ isValidHex( inputValue ) ? normalizeHex( inputValue ) : value }
						onChange={ handleColorChange }
						disabled={ disabled }
						className="rp-absolute rp-inset-0 rp-w-full rp-h-full rp-opacity-0 rp-cursor-pointer disabled:rp-cursor-not-allowed"
						aria-label={ label || 'Farbe wählen' }
					/>
					<div
						className={ cn(
							'rp-w-9 rp-h-9 rp-rounded-md rp-border rp-border-gray-300 rp-shadow-sm',
							disabled && 'rp-opacity-50 rp-cursor-not-allowed'
						) }
						style={ {
							backgroundColor: isValidHex( inputValue )
								? normalizeHex( inputValue )
								: value,
						} }
					/>
				</div>

				{/* Hex input */}
				<input
					type="text"
					value={ inputValue }
					onChange={ handleInputChange }
					onBlur={ handleInputBlur }
					onKeyDown={ handleKeyDown }
					disabled={ disabled }
					placeholder="#000000"
					maxLength={ 7 }
					className={ cn(
						'rp-w-24 rp-px-2 rp-py-1.5 rp-text-sm rp-font-mono',
						'rp-border rp-border-gray-300 rp-rounded-md',
						'focus:rp-outline-none focus:rp-ring-2 focus:rp-ring-blue-500 focus:rp-border-transparent',
						disabled && 'rp-opacity-50 rp-cursor-not-allowed rp-bg-gray-100'
					) }
					aria-label="Hex-Farbwert"
				/>
			</div>
		);
	}
);

ColorPicker.displayName = 'ColorPicker';

export { ColorPicker };

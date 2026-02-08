/**
 * ColumnsControl Component
 *
 * Slider control for selecting column layout (1-4 columns).
 * Used in job grid and card layouts.
 *
 * @package
 */

import { RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * ColumnsControl - Slider for column count selection
 *
 * @param {Object}   props          Component props.
 * @param {number}   props.value    Current column count.
 * @param {Function} props.onChange Callback when value changes.
 * @param {number}   props.min      Minimum columns (default: 1).
 * @param {number}   props.max      Maximum columns (default: 4).
 * @param {string}   props.label    Optional label override.
 * @param {string}   props.help     Optional help text.
 * @return {JSX.Element} The component.
 */
export function ColumnsControl( {
	value = 3,
	onChange,
	min = 1,
	max = 4,
	label,
	help,
} ) {
	return (
		<RangeControl
			label={ label || __( 'Spalten', 'recruiting-playbook' ) }
			value={ value }
			onChange={ onChange }
			min={ min }
			max={ max }
			marks={ [
				{ value: 1, label: '1' },
				{ value: 2, label: '2' },
				{ value: 3, label: '3' },
				{ value: 4, label: '4' },
			].filter( ( m ) => m.value >= min && m.value <= max ) }
			help={
				help ||
				__( 'Anzahl der Spalten im Grid-Layout', 'recruiting-playbook' )
			}
		/>
	);
}

export default ColumnsControl;

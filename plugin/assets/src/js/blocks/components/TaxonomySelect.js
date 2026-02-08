/**
 * TaxonomySelect Component
 *
 * Reusable dropdown component for WordPress taxonomies.
 * Supports single and multi-select modes.
 *
 * @package
 */

import { SelectControl, FormTokenField } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * TaxonomySelect - Single select dropdown for taxonomy terms
 *
 * @param {Object}   props            Component props.
 * @param {string}   props.taxonomy   Taxonomy slug (job_category, job_location, employment_type).
 * @param {string}   props.value      Selected term slug.
 * @param {Function} props.onChange   Callback when selection changes.
 * @param {string}   props.label      Optional label override.
 * @param {boolean}  props.showCount  Show term count in label.
 * @param {boolean}  props.allowEmpty Allow empty selection.
 * @return {JSX.Element} The component.
 */
export function TaxonomySelect( {
	taxonomy,
	value,
	onChange,
	label,
	showCount = false,
	allowEmpty = true,
} ) {
	// Get taxonomy data from localized config.
	const taxonomyData = window.rpBlocksConfig?.taxonomies?.[ taxonomy ] || [];

	// Build options array.
	const options = taxonomyData.map( ( term ) => ( {
		label: showCount ? `${ term.label } (${ term.count })` : term.label,
		value: term.value,
	} ) );

	// Add empty option if allowed.
	if ( allowEmpty ) {
		options.unshift( {
			label: __( 'Alle', 'recruiting-playbook' ),
			value: '',
		} );
	}

	// Get default label based on taxonomy.
	const getDefaultLabel = () => {
		switch ( taxonomy ) {
			case 'job_category':
				return __( 'Kategorie', 'recruiting-playbook' );
			case 'job_location':
				return __( 'Standort', 'recruiting-playbook' );
			case 'employment_type':
				return __( 'Beschäftigungsart', 'recruiting-playbook' );
			default:
				return __( 'Auswahl', 'recruiting-playbook' );
		}
	};

	return (
		<SelectControl
			label={ label || getDefaultLabel() }
			value={ value }
			options={ options }
			onChange={ onChange }
		/>
	);
}

/**
 * TaxonomyMultiSelect - Multi-select token field for taxonomy terms
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.taxonomy Taxonomy slug.
 * @param {Array}    props.value    Selected term slugs.
 * @param {Function} props.onChange Callback when selection changes.
 * @param {string}   props.label    Optional label override.
 * @return {JSX.Element} The component.
 */
export function TaxonomyMultiSelect( {
	taxonomy,
	value = [],
	onChange,
	label,
} ) {
	// Get taxonomy data from localized config.
	const taxonomyData = window.rpBlocksConfig?.taxonomies?.[ taxonomy ] || [];

	// Build suggestions array (term labels).
	const suggestions = taxonomyData.map( ( term ) => term.label );

	// Convert slugs to labels for display.
	const selectedLabels = value.map( ( slug ) => {
		const term = taxonomyData.find( ( t ) => t.value === slug );
		return term ? term.label : slug;
	} );

	// Handle token changes.
	const handleChange = ( tokens ) => {
		// Convert labels back to slugs.
		const slugs = tokens.map( ( token ) => {
			const term = taxonomyData.find( ( t ) => t.label === token );
			return term ? term.value : token;
		} );
		onChange( slugs );
	};

	// Get default label based on taxonomy.
	const getDefaultLabel = () => {
		switch ( taxonomy ) {
			case 'job_category':
				return __( 'Kategorien', 'recruiting-playbook' );
			case 'job_location':
				return __( 'Standorte', 'recruiting-playbook' );
			case 'employment_type':
				return __( 'Beschäftigungsarten', 'recruiting-playbook' );
			default:
				return __( 'Auswahl', 'recruiting-playbook' );
		}
	};

	return (
		<FormTokenField
			label={ label || getDefaultLabel() }
			value={ selectedLabels }
			suggestions={ suggestions }
			onChange={ handleChange }
			__experimentalExpandOnFocus
			__experimentalShowHowTo={ false }
		/>
	);
}

export default TaxonomySelect;

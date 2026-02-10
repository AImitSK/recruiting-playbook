/**
 * Job Search Block - Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';

import { ColumnsControl } from '../components/ColumnsControl';
import { BlockPlaceholder } from '../components/BlockPlaceholder';

/**
 * Edit component for the Job Search block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { showSearch, showCategory, showLocation, showType, limit, columns } =
		attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-job-search-editor',
	} );

	const getFilterSummary = () => {
		const filters = [];
		if ( showSearch ) filters.push( __( 'Suche', 'recruiting-playbook' ) );
		if ( showCategory )
			filters.push( __( 'Kategorie', 'recruiting-playbook' ) );
		if ( showLocation )
			filters.push( __( 'Standort', 'recruiting-playbook' ) );
		if ( showType ) filters.push( __( 'Art', 'recruiting-playbook' ) );
		return filters.length > 0
			? filters.join( ', ' )
			: __( 'Keine Filter', 'recruiting-playbook' );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Suchformular', 'recruiting-playbook' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __(
							'Suchfeld anzeigen',
							'recruiting-playbook'
						) }
						checked={ showSearch }
						onChange={ ( value ) =>
							setAttributes( { showSearch: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Kategorie-Filter anzeigen',
							'recruiting-playbook'
						) }
						checked={ showCategory }
						onChange={ ( value ) =>
							setAttributes( { showCategory: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Standort-Filter anzeigen',
							'recruiting-playbook'
						) }
						checked={ showLocation }
						onChange={ ( value ) =>
							setAttributes( { showLocation: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Beschäftigungsart-Filter anzeigen',
							'recruiting-playbook'
						) }
						checked={ showType }
						onChange={ ( value ) =>
							setAttributes( { showType: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Ergebnisse', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<RangeControl
						label={ __(
							'Stellen pro Seite',
							'recruiting-playbook'
						) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 50 }
						help={ __(
							'Anzahl der Stellen pro Seite.',
							'recruiting-playbook'
						) }
					/>
					<ColumnsControl
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 4 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockPlaceholder
					label={ __( 'Stellensuche', 'recruiting-playbook' ) }
					summary={ `${ __( 'Filter:', 'recruiting-playbook' ) } ${ getFilterSummary() }` }
					helpText={ __(
						'Suchformular mit Filtern und Ergebnisliste.',
						'recruiting-playbook'
					) }
					shortcode="[rp_job_search]"
					docAnchor="stellensuche"
				/>
			</div>
		</>
	);
}

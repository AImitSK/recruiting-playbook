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
		if ( showSearch ) filters.push( __( 'Search', 'recruiting-playbook' ) );
		if ( showCategory )
			filters.push( __( 'Category', 'recruiting-playbook' ) );
		if ( showLocation )
			filters.push( __( 'Location', 'recruiting-playbook' ) );
		if ( showType ) filters.push( __( 'Type', 'recruiting-playbook' ) );
		return filters.length > 0
			? filters.join( ', ' )
			: __( 'No filters', 'recruiting-playbook' );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Search Form', 'recruiting-playbook' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __(
							'Show search field',
							'recruiting-playbook'
						) }
						checked={ showSearch }
						onChange={ ( value ) =>
							setAttributes( { showSearch: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Show category filter',
							'recruiting-playbook'
						) }
						checked={ showCategory }
						onChange={ ( value ) =>
							setAttributes( { showCategory: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Show location filter',
							'recruiting-playbook'
						) }
						checked={ showLocation }
						onChange={ ( value ) =>
							setAttributes( { showLocation: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Show employment type filter',
							'recruiting-playbook'
						) }
						checked={ showType }
						onChange={ ( value ) =>
							setAttributes( { showType: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Results', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<RangeControl
						label={ __(
							'Jobs per page',
							'recruiting-playbook'
						) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 50 }
						help={ __(
							'Number of jobs per page.',
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
					label={ __( 'Job Search', 'recruiting-playbook' ) }
					summary={ `${ __( 'Filters:', 'recruiting-playbook' ) } ${ getFilterSummary() }` }
					helpText={ __(
						'Search form with filters and results list.',
						'recruiting-playbook'
					) }
					shortcode="[rp_job_search]"
					docAnchor="stellensuche"
				/>
			</div>
		</>
	);
}

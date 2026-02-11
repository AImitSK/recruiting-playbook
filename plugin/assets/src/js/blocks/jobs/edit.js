/**
 * Jobs Block - Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';

import { TaxonomySelect } from '../components/TaxonomySelect';
import { ColumnsControl } from '../components/ColumnsControl';
import { BlockPlaceholder } from '../components/BlockPlaceholder';

/**
 * Edit component for the Jobs block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		limit,
		columns,
		category,
		location,
		type,
		featured,
		orderby,
		order,
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-jobs-editor',
	} );

	// Build summary text for placeholder.
	const getSummary = () => {
		const parts = [];
		parts.push(
			limit === 1
				? __( '1 job', 'recruiting-playbook' )
				: `${ limit } ${ __( 'jobs', 'recruiting-playbook' ) }`
		);
		parts.push(
			columns === 1
				? __( '1 column', 'recruiting-playbook' )
				: `${ columns } ${ __( 'columns', 'recruiting-playbook' ) }`
		);
		if ( featured ) {
			parts.push( __( 'Featured only', 'recruiting-playbook' ) );
		}
		return parts.join( ' · ' );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Display', 'recruiting-playbook' ) }
					initialOpen={ true }
				>
					<RangeControl
						label={ __( 'Number of Jobs', 'recruiting-playbook' ) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 50 }
						help={ __(
							'Maximum number of jobs to display.',
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

				<PanelBody
					title={ __( 'Filter', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<TaxonomySelect
						taxonomy="job_category"
						value={ category }
						onChange={ ( value ) =>
							setAttributes( { category: value } )
						}
					/>
					<TaxonomySelect
						taxonomy="job_location"
						value={ location }
						onChange={ ( value ) =>
							setAttributes( { location: value } )
						}
					/>
					<TaxonomySelect
						taxonomy="employment_type"
						value={ type }
						onChange={ ( value ) =>
							setAttributes( { type: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Featured Jobs Only',
							'recruiting-playbook'
						) }
						checked={ featured }
						onChange={ ( value ) =>
							setAttributes( { featured: value } )
						}
						help={ __(
							'Display only featured jobs.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Sorting', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Sort By', 'recruiting-playbook' ) }
						value={ orderby }
						options={ [
							{
								label: __( 'Date', 'recruiting-playbook' ),
								value: 'date',
							},
							{
								label: __( 'Title', 'recruiting-playbook' ),
								value: 'title',
							},
							{
								label: __( 'Random', 'recruiting-playbook' ),
								value: 'rand',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderby: value } )
						}
					/>
					<SelectControl
						label={ __( 'Order', 'recruiting-playbook' ) }
						value={ order }
						options={ [
							{
								label: __(
									'Newest first',
									'recruiting-playbook'
								),
								value: 'DESC',
							},
							{
								label: __(
									'Oldest first',
									'recruiting-playbook'
								),
								value: 'ASC',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { order: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockPlaceholder
					label={ __( 'Job List', 'recruiting-playbook' ) }
					summary={ getSummary() }
					helpText={ __(
						'Configure the display in the sidebar.',
						'recruiting-playbook'
					) }
					shortcode="[rp_jobs]"
					docAnchor="stellenliste"
				/>
			</div>
		</>
	);
}

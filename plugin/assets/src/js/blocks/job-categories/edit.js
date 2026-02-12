/**
 * Job Categories Block - Editor Component
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

import { BlockPlaceholder } from '../components/BlockPlaceholder';

/**
 * Edit component for the Job Categories block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { columns, showCount, hideEmpty, orderby } = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-job-categories-editor',
	} );

	const getSummary = () => {
		const parts = [];
		parts.push(
			columns === 1
				? __( '1 column', 'recruiting-playbook' )
				: `${ columns } ${ __( 'columns', 'recruiting-playbook' ) }`
		);
		if ( showCount ) {
			parts.push( __( 'with counter', 'recruiting-playbook' ) );
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
						label={ __( 'Columns', 'recruiting-playbook' ) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 6 }
						help={ __(
							'Number of columns in the grid layout.',
							'recruiting-playbook'
						) }
					/>
					<ToggleControl
						label={ __(
							'Show job count',
							'recruiting-playbook'
						) }
						checked={ showCount }
						onChange={ ( value ) =>
							setAttributes( { showCount: value } )
						}
						help={ __(
							'Displays the number of jobs per category.',
							'recruiting-playbook'
						) }
					/>
					<ToggleControl
						label={ __(
							'Hide empty categories',
							'recruiting-playbook'
						) }
						checked={ hideEmpty }
						onChange={ ( value ) =>
							setAttributes( { hideEmpty: value } )
						}
						help={ __(
							'Hides categories without job listings.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Sorting', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Sort by', 'recruiting-playbook' ) }
						value={ orderby }
						options={ [
							{
								label: __(
									'Name (A-Z)',
									'recruiting-playbook'
								),
								value: 'name',
							},
							{
								label: __(
									'Job count',
									'recruiting-playbook'
								),
								value: 'count',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderby: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockPlaceholder
					label={ __( 'Job Categories', 'recruiting-playbook' ) }
					summary={ getSummary() }
					helpText={ __(
						'Displays all job categories as cards.',
						'recruiting-playbook'
					) }
					shortcode="[rp_job_categories]"
					docAnchor="job-kategorien"
				/>
			</div>
		</>
	);
}

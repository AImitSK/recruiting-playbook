/**
 * Job Count Block - Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

import { TaxonomySelect } from '../components/TaxonomySelect';
import { BlockPlaceholder } from '../components/BlockPlaceholder';

/**
 * Edit component for the Job Count block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { category, location, type, format, singular, zero } = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-job-count-editor',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Filter', 'recruiting-playbook' ) }
					initialOpen={ true }
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
				</PanelBody>

				<PanelBody
					title={ __( 'Text Format', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'Multiple Jobs', 'recruiting-playbook' ) }
						value={ format }
						onChange={ ( value ) =>
							setAttributes( { format: value } )
						}
						help={ __(
							'Use {count} as a placeholder for the number.',
							'recruiting-playbook'
						) }
					/>
					<TextControl
						label={ __( 'One Job', 'recruiting-playbook' ) }
						value={ singular }
						onChange={ ( value ) =>
							setAttributes( { singular: value } )
						}
						help={ __(
							'Text when exactly 1 job exists.',
							'recruiting-playbook'
						) }
					/>
					<TextControl
						label={ __( 'No Jobs', 'recruiting-playbook' ) }
						value={ zero }
						onChange={ ( value ) =>
							setAttributes( { zero: value } )
						}
						help={ __(
							'Text when no jobs are available.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockPlaceholder
					label={ __( 'Job Counter', 'recruiting-playbook' ) }
					summary={ format }
					helpText={ __(
						'Displays the number of open jobs.',
						'recruiting-playbook'
					) }
					shortcode="[rp_job_count]"
					docAnchor="stellen-zaehler"
				/>
			</div>
		</>
	);
}

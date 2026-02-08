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
					title={ __( 'Textformat', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<TextControl
						label={ __( 'Mehrere Stellen', 'recruiting-playbook' ) }
						value={ format }
						onChange={ ( value ) =>
							setAttributes( { format: value } )
						}
						help={ __(
							'Verwenden Sie {count} als Platzhalter für die Anzahl.',
							'recruiting-playbook'
						) }
					/>
					<TextControl
						label={ __( 'Eine Stelle', 'recruiting-playbook' ) }
						value={ singular }
						onChange={ ( value ) =>
							setAttributes( { singular: value } )
						}
						help={ __(
							'Text wenn genau 1 Stelle vorhanden ist.',
							'recruiting-playbook'
						) }
					/>
					<TextControl
						label={ __( 'Keine Stellen', 'recruiting-playbook' ) }
						value={ zero }
						onChange={ ( value ) =>
							setAttributes( { zero: value } )
						}
						help={ __(
							'Text wenn keine Stellen vorhanden sind.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockPlaceholder
					label={ __( 'Stellen-Zähler', 'recruiting-playbook' ) }
					summary={ format }
					helpText={ __(
						'Zeigt die Anzahl offener Stellen an.',
						'recruiting-playbook'
					) }
					shortcode="[rp_job_count]"
					docSlug="blocks/job-count"
				/>
			</div>
		</>
	);
}

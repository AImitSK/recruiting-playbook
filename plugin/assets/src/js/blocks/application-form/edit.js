/**
 * Application Form Block - Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

import { BlockPlaceholder } from '../components/BlockPlaceholder';

/**
 * Edit component for the Application Form block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { jobId, title, showJobTitle, showProgress } = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-application-form-editor',
	} );

	const getDescription = () => {
		if ( jobId > 0 ) {
			return `Job ID: ${ jobId }`;
		}
		return __( 'Automatic detection', 'recruiting-playbook' );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'recruiting-playbook' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Job ID', 'recruiting-playbook' ) }
						value={ jobId || '' }
						onChange={ ( value ) =>
							setAttributes( {
								jobId: parseInt( value, 10 ) || 0,
							} )
						}
						type="number"
						help={ __(
							'Leave empty for automatic detection on job pages.',
							'recruiting-playbook'
						) }
					/>
					<TextControl
						label={ __( 'Heading', 'recruiting-playbook' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Show job title',
							'recruiting-playbook'
						) }
						checked={ showJobTitle }
						onChange={ ( value ) =>
							setAttributes( { showJobTitle: value } )
						}
						help={ __(
							'Shows "Application for: [Job Title]".',
							'recruiting-playbook'
						) }
					/>
					<ToggleControl
						label={ __(
							'Progress indicator',
							'recruiting-playbook'
						) }
						checked={ showProgress }
						onChange={ ( value ) =>
							setAttributes( { showProgress: value } )
						}
						help={ __(
							'Shows progress for multi-step forms.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockPlaceholder
					label={ __( 'Application Form', 'recruiting-playbook' ) }
					summary={ getDescription() }
					helpText={ __(
						'The form will be displayed on the frontend.',
						'recruiting-playbook'
					) }
					shortcode="[rp_application_form]"
					docAnchor="bewerbungsformular"
				/>
			</div>
		</>
	);
}

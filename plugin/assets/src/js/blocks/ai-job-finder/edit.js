/**
 * AI Job Finder Block - Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	RangeControl,
	Placeholder,
} from '@wordpress/components';

import { ProBadge, FeatureGate } from '../components/ProBadge';

/**
 * Edit component for the AI Job Finder block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { title, subtitle, limit } = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-ai-job-finder-editor',
	} );

	// Check if Pro is available (includes AI features).
	const isPro = window.rpBlocksConfig?.isPro || false;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'recruiting-playbook' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Heading', 'recruiting-playbook' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<TextControl
						label={ __( 'Subtitle', 'recruiting-playbook' ) }
						value={ subtitle }
						onChange={ ( value ) =>
							setAttributes( { subtitle: value } )
						}
					/>
					<RangeControl
						label={ __(
							'Maximum Results',
							'recruiting-playbook'
						) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 10 }
						help={ __(
							'Number of suggested job listings.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<FeatureGate feature="ai">
					<div className="rp-ai-finder-preview">
						<div className="rp-ai-finder-preview__header">
							<ProBadge size="medium" />
						</div>
						<div className="rp-ai-finder-preview__icon">
							<span className="dashicons dashicons-superhero"></span>
						</div>
						<h3 className="rp-ai-finder-preview__title">
							{ title ||
								__(
									'Find Your Dream Job',
									'recruiting-playbook'
								) }
						</h3>
						<p className="rp-ai-finder-preview__description">
							{ subtitle ||
								__(
									'Upload your resume and discover matching job listings.',
									'recruiting-playbook'
								) }
						</p>
						{ ! isPro && (
							<Placeholder
								icon="lock"
								label={ __(
									'Pro Required',
									'recruiting-playbook'
								) }
							>
								<p>
									{ __(
										'The AI Job Finder requires Pro.',
										'recruiting-playbook'
									) }
								</p>
							</Placeholder>
						) }
					</div>
				</FeatureGate>
			</div>
		</>
	);
}

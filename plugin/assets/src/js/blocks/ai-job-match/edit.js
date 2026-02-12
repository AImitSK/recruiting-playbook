/**
 * AI Job Match Block - Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	Placeholder,
} from '@wordpress/components';

import { AiBadge, FeatureGate } from '../components/ProBadge';

/**
 * Edit component for the AI Job Match block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { jobId, title, style } = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-ai-job-match-editor',
	} );

	// Check if AI addon is available.
	const hasAiAddon = window.rpBlocksConfig?.hasAiAddon || false;

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
							'Leave blank for automatic detection on job pages.',
							'recruiting-playbook'
						) }
					/>
					<TextControl
						label={ __( 'Button Text', 'recruiting-playbook' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<SelectControl
						label={ __( 'Button Style', 'recruiting-playbook' ) }
						value={ style }
						options={ [
							{
								label: __( 'Default', 'recruiting-playbook' ),
								value: '',
							},
							{
								label: __( 'Outline', 'recruiting-playbook' ),
								value: 'outline',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { style: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<FeatureGate feature="ai">
					<div className="rp-ai-match-preview">
						<AiBadge size="small" inline />
						<button
							type="button"
							className={
								'wp-element-button' +
								( style === 'outline'
									? ' is-style-outline'
									: '' )
							}
							disabled
						>
							<span
								style={ {
									display: 'inline-flex',
									alignItems: 'center',
									gap: '0.5rem',
								} }
							>
								<svg
									style={ {
										width: '1.25rem',
										height: '1.25rem',
									} }
									fill="none"
									stroke="currentColor"
									viewBox="0 0 24 24"
								>
									<path
										strokeLinecap="round"
										strokeLinejoin="round"
										strokeWidth={ 2 }
										d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
									/>
								</svg>
								<span>
									{ title ||
										__(
											'Am I a match for this job?',
											'recruiting-playbook'
										) }
								</span>
							</span>
						</button>
						{ ! hasAiAddon && (
							<Placeholder
								icon="lock"
								label={ __(
									'AI Addon Required',
									'recruiting-playbook'
								) }
							>
								<p>
									{ __(
										'The AI Job Match button requires the AI Addon.',
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

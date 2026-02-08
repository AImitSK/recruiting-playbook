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

import { AiBadge, FeatureGate } from '../components/ProBadge';

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

	// Check if AI addon is available.
	const hasAiAddon = window.rpBlocksConfig?.hasAiAddon || false;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Einstellungen', 'recruiting-playbook' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Überschrift', 'recruiting-playbook' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<TextControl
						label={ __( 'Untertitel', 'recruiting-playbook' ) }
						value={ subtitle }
						onChange={ ( value ) =>
							setAttributes( { subtitle: value } )
						}
					/>
					<RangeControl
						label={ __(
							'Maximale Ergebnisse',
							'recruiting-playbook'
						) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 10 }
						help={ __(
							'Anzahl der vorgeschlagenen Stellen.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<FeatureGate feature="ai">
					<div className="rp-ai-finder-preview">
						<div className="rp-ai-finder-preview__header">
							<AiBadge size="medium" />
						</div>
						<div className="rp-ai-finder-preview__icon">
							<span className="dashicons dashicons-superhero"></span>
						</div>
						<h3 className="rp-ai-finder-preview__title">
							{ title ||
								__(
									'Finde deinen Traumjob',
									'recruiting-playbook'
								) }
						</h3>
						<p className="rp-ai-finder-preview__description">
							{ subtitle ||
								__(
									'Lade deinen Lebenslauf hoch und entdecke passende Stellen.',
									'recruiting-playbook'
								) }
						</p>
						{ ! hasAiAddon && (
							<Placeholder
								icon="lock"
								label={ __(
									'AI-Addon erforderlich',
									'recruiting-playbook'
								) }
							>
								<p>
									{ __(
										'Der KI-Job-Finder benötigt das AI-Addon.',
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

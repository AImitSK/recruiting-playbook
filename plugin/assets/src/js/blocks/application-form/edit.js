/**
 * Application Form Block - Editor Component
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	Placeholder,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

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

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Einstellungen', 'recruiting-playbook' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Job-ID', 'recruiting-playbook' ) }
						value={ jobId || '' }
						onChange={ ( value ) =>
							setAttributes( { jobId: parseInt( value, 10 ) || 0 } )
						}
						type="number"
						help={ __(
							'Leer lassen für automatische Erkennung auf Stellenseiten.',
							'recruiting-playbook'
						) }
					/>
					<TextControl
						label={ __( 'Überschrift', 'recruiting-playbook' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Stellentitel anzeigen',
							'recruiting-playbook'
						) }
						checked={ showJobTitle }
						onChange={ ( value ) =>
							setAttributes( { showJobTitle: value } )
						}
						help={ __(
							'Zeigt "Bewerbung für: [Stellentitel]" an.',
							'recruiting-playbook'
						) }
					/>
					<ToggleControl
						label={ __(
							'Fortschrittsanzeige',
							'recruiting-playbook'
						) }
						checked={ showProgress }
						onChange={ ( value ) =>
							setAttributes( { showProgress: value } )
						}
						help={ __(
							'Zeigt den Fortschritt bei Multi-Step-Formularen.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ jobId > 0 ? (
					<ServerSideRender
						block="rp/application-form"
						attributes={ attributes }
						EmptyResponsePlaceholder={ () => (
							<Placeholder
								icon="clipboard"
								label={ __(
									'Bewerbungsformular',
									'recruiting-playbook'
								) }
							>
								<p>
									{ __(
										'Das Formular wird auf der Seite angezeigt.',
										'recruiting-playbook'
									) }
								</p>
							</Placeholder>
						) }
					/>
				) : (
					<Placeholder
						icon="clipboard"
						label={ __( 'Bewerbungsformular', 'recruiting-playbook' ) }
					>
						<p>
							{ __(
								'Das Formular erkennt automatisch die aktuelle Stelle oder geben Sie eine Job-ID in den Einstellungen an.',
								'recruiting-playbook'
							) }
						</p>
					</Placeholder>
				) }
			</div>
		</>
	);
}

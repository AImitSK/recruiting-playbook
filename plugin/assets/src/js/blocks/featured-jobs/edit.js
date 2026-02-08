/**
 * Featured Jobs Block - Editor Component
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import { ColumnsControl } from '../components/ColumnsControl';

/**
 * Edit component for the Featured Jobs block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { limit, columns, title, showExcerpt } = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-featured-jobs-editor',
	} );

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
						help={ __(
							'Optionale Überschrift über den Featured Jobs.',
							'recruiting-playbook'
						) }
					/>
					<RangeControl
						label={ __( 'Anzahl Stellen', 'recruiting-playbook' ) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 12 }
					/>
					<ColumnsControl
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 4 }
					/>
					<ToggleControl
						label={ __( 'Auszug anzeigen', 'recruiting-playbook' ) }
						checked={ showExcerpt }
						onChange={ ( value ) =>
							setAttributes( { showExcerpt: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block="rp/featured-jobs"
					attributes={ attributes }
					EmptyResponsePlaceholder={ () => (
						<p className="rp-block-empty">
							{ __(
								'Keine Featured Jobs vorhanden. Markieren Sie Stellen als "Featured" in der Stellenverwaltung.',
								'recruiting-playbook'
							) }
						</p>
					) }
				/>
			</div>
		</>
	);
}

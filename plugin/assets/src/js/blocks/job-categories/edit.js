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
	Placeholder,
} from '@wordpress/components';

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
				? __( '1 Spalte', 'recruiting-playbook' )
				: `${ columns } ${ __( 'Spalten', 'recruiting-playbook' ) }`
		);
		if ( showCount ) {
			parts.push( __( 'mit Zähler', 'recruiting-playbook' ) );
		}
		return parts.join( ' · ' );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Anzeige', 'recruiting-playbook' ) }
					initialOpen={ true }
				>
					<RangeControl
						label={ __( 'Spalten', 'recruiting-playbook' ) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 6 }
						help={ __(
							'Anzahl der Spalten im Grid-Layout.',
							'recruiting-playbook'
						) }
					/>
					<ToggleControl
						label={ __(
							'Anzahl Stellen anzeigen',
							'recruiting-playbook'
						) }
						checked={ showCount }
						onChange={ ( value ) =>
							setAttributes( { showCount: value } )
						}
						help={ __(
							'Zeigt die Anzahl der Stellen pro Kategorie an.',
							'recruiting-playbook'
						) }
					/>
					<ToggleControl
						label={ __(
							'Leere Kategorien ausblenden',
							'recruiting-playbook'
						) }
						checked={ hideEmpty }
						onChange={ ( value ) =>
							setAttributes( { hideEmpty: value } )
						}
						help={ __(
							'Versteckt Kategorien ohne Stellenanzeigen.',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Sortierung', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Sortieren nach', 'recruiting-playbook' ) }
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
									'Anzahl Stellen',
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
				<Placeholder
					icon="category"
					label={ __( 'Job-Kategorien', 'recruiting-playbook' ) }
					instructions={ getSummary() }
				>
					<p className="components-placeholder__learn-more">
						{ __(
							'Zeigt alle Job-Kategorien als Karten an.',
							'recruiting-playbook'
						) }
					</p>
				</Placeholder>
			</div>
		</>
	);
}

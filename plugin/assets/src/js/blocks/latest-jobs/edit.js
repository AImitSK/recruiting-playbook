/**
 * Latest Jobs Block - Editor Component
 *
 * @package
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

import { TaxonomySelect } from '../components/TaxonomySelect';
import { ColumnsControl } from '../components/ColumnsControl';
import { BlockPlaceholder } from '../components/BlockPlaceholder';

/**
 * Edit component for the Latest Jobs block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { limit, columns, title, category, showExcerpt } = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-latest-jobs-editor',
	} );

	const getSummary = () => {
		const parts = [];
		parts.push(
			limit === 1
				? __( '1 Stelle', 'recruiting-playbook' )
				: `${ limit } ${ __( 'Stellen', 'recruiting-playbook' ) }`
		);
		parts.push(
			columns === 1
				? __( '1 Spalte', 'recruiting-playbook' )
				: `${ columns } ${ __( 'Spalten', 'recruiting-playbook' ) }`
		);
		return parts.join( ' · ' );
	};

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
							'Optionale Überschrift über den neuesten Stellen.',
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
						max={ 20 }
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

				<PanelBody
					title={ __( 'Filter', 'recruiting-playbook' ) }
					initialOpen={ false }
				>
					<TaxonomySelect
						taxonomy="job_category"
						value={ category }
						onChange={ ( value ) =>
							setAttributes( { category: value } )
						}
						label={ __(
							'Nur aus Kategorie',
							'recruiting-playbook'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockPlaceholder
					label={ __( 'Neueste Stellen', 'recruiting-playbook' ) }
					summary={ getSummary() }
					helpText={ __(
						'Zeigt die neuesten Stellenanzeigen an.',
						'recruiting-playbook'
					) }
					shortcode="[rp_latest_jobs]"
					docAnchor="neueste-stellen"
				/>
			</div>
		</>
	);
}

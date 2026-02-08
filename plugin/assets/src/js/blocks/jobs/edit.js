/**
 * Jobs Block - Editor Component
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
} from '@wordpress/components';

import { TaxonomySelect } from '../components/TaxonomySelect';
import { ColumnsControl } from '../components/ColumnsControl';
import { BlockPlaceholder } from '../components/BlockPlaceholder';

/**
 * Edit component for the Jobs block.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} The editor component.
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		limit,
		columns,
		category,
		location,
		type,
		featured,
		orderby,
		order,
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'rp-block-jobs-editor',
	} );

	// Build summary text for placeholder.
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
		if ( featured ) {
			parts.push( __( 'Nur Featured', 'recruiting-playbook' ) );
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
						label={ __( 'Anzahl Stellen', 'recruiting-playbook' ) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 50 }
						help={ __(
							'Maximale Anzahl der angezeigten Stellen.',
							'recruiting-playbook'
						) }
					/>
					<ColumnsControl
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 4 }
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
					<ToggleControl
						label={ __(
							'Nur Featured Jobs',
							'recruiting-playbook'
						) }
						checked={ featured }
						onChange={ ( value ) =>
							setAttributes( { featured: value } )
						}
						help={ __(
							'Zeigt nur hervorgehobene Stellen an.',
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
								label: __( 'Datum', 'recruiting-playbook' ),
								value: 'date',
							},
							{
								label: __( 'Titel', 'recruiting-playbook' ),
								value: 'title',
							},
							{
								label: __( 'Zufall', 'recruiting-playbook' ),
								value: 'rand',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderby: value } )
						}
					/>
					<SelectControl
						label={ __( 'Reihenfolge', 'recruiting-playbook' ) }
						value={ order }
						options={ [
							{
								label: __(
									'Neueste zuerst',
									'recruiting-playbook'
								),
								value: 'DESC',
							},
							{
								label: __(
									'Älteste zuerst',
									'recruiting-playbook'
								),
								value: 'ASC',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { order: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockPlaceholder
					label={ __( 'Stellenliste', 'recruiting-playbook' ) }
					summary={ getSummary() }
					helpText={ __(
						'Konfiguriere die Anzeige in der Seitenleiste.',
						'recruiting-playbook'
					) }
					shortcode="[rp_jobs]"
					docAnchor="stellenliste"
				/>
			</div>
		</>
	);
}

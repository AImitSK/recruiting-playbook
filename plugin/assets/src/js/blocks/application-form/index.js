/**
 * Application Form Block
 *
 * Bewerbungsformular für Stellenseiten.
 *
 * @package
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 */
registerBlockType( 'rp/application-form', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

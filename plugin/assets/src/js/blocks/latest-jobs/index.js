/**
 * Latest Jobs Block
 *
 * Zeigt die neuesten Stellenanzeigen an.
 *
 * @package
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 */
registerBlockType( 'rp/latest-jobs', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

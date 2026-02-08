/**
 * Featured Jobs Block
 *
 * Zeigt hervorgehobene Stellenanzeigen an.
 *
 * @package
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 */
registerBlockType( 'rp/featured-jobs', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

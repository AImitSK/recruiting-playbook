/**
 * Job Count Block
 *
 * Zeigt die Anzahl der verfügbaren Stellen an.
 *
 * @package
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 */
registerBlockType( 'rp/job-count', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

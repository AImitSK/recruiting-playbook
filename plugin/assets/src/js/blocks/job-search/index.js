/**
 * Job Search Block
 *
 * Suchformular mit Filtern und Ergebnisliste.
 *
 * @package
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 */
registerBlockType( 'rp/job-search', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

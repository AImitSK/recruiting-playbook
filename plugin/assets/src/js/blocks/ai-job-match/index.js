/**
 * AI Job Match Block
 *
 * Button für KI-gestütztes Job-Matching.
 * Pro Feature.
 *
 * @package
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 */
registerBlockType( 'rp/ai-job-match', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

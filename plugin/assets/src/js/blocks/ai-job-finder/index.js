/**
 * AI Job Finder Block
 *
 * Lebenslauf-Upload mit KI-gestützter Stellensuche.
 * AI-Addon Feature.
 *
 * @package RecruitingPlaybook
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 */
registerBlockType( 'rp/ai-job-finder', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

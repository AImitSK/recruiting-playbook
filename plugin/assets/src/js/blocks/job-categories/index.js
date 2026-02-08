/**
 * Job Categories Block
 *
 * Zeigt alle Job-Kategorien als klickbare Karten an.
 *
 * @package RecruitingPlaybook
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 */
registerBlockType( 'rp/job-categories', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

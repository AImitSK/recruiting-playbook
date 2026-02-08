/**
 * Jobs Block
 *
 * Zeigt eine Liste von Stellenanzeigen an.
 *
 * @package RecruitingPlaybook
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

import Edit from './edit';

/**
 * Block-Metadaten werden aus block.json geladen.
 * Hier registrieren wir nur die Editor-Komponente.
 */
registerBlockType( 'rp/jobs', {
	edit: Edit,
	save: () => null, // Dynamic Block - Server-Side Rendering
} );

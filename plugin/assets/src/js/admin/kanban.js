/**
 * Kanban-Board Entry Point
 *
 * @package RecruitingPlaybook
 */

import { createRoot } from '@wordpress/element';
import { KanbanBoard } from './kanban/KanbanBoard';

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'rp-kanban-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <KanbanBoard /> );
	}
} );

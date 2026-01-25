/**
 * Kanban-Board Entry Point
 *
 * @package RecruitingPlaybook
 */

import { createRoot } from '@wordpress/element';
import { KanbanBoard } from './kanban/KanbanBoard';

/**
 * Initialisiert das Kanban-Board
 */
function initKanban() {
	const container = document.getElementById( 'rp-kanban-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <KanbanBoard /> );
	}
}

// DOMContentLoaded könnte bereits gefeuert haben wenn Script im Footer lädt
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initKanban );
} else {
	// DOM ist bereits bereit
	initKanban();
}

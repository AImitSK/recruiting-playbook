/**
 * Admin Entry Point
 *
 * Initialisiert Kanban-Board, Bewerber-Detailseite und Talent-Pool
 *
 * @package RecruitingPlaybook
 */

import { createRoot } from '@wordpress/element';
import { KanbanBoard } from './kanban/KanbanBoard';
import { ApplicantDetail } from './applicant/ApplicantDetail';
import { TalentPoolList } from './talent-pool/TalentPoolList';

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

/**
 * Initialisiert die Bewerber-Detailseite
 */
function initApplicantDetail() {
	const container = document.getElementById( 'rp-applicant-detail-root' );

	if ( container ) {
		const applicationId = parseInt( container.dataset.applicationId, 10 );

		if ( applicationId ) {
			const root = createRoot( container );
			root.render( <ApplicantDetail applicationId={ applicationId } /> );
		}
	}
}

/**
 * Initialisiert die Talent-Pool Seite
 */
function initTalentPool() {
	const container = document.getElementById( 'rp-talent-pool-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <TalentPoolList /> );
	}
}

/**
 * Initialisiert alle Admin-Komponenten
 */
function initAdmin() {
	initKanban();
	initApplicantDetail();
	initTalentPool();
}

// DOMContentLoaded könnte bereits gefeuert haben wenn Script im Footer lädt
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initAdmin );
} else {
	// DOM ist bereits bereit
	initAdmin();
}

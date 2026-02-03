/**
 * Admin Entry Point
 *
 * Initialisiert Kanban-Board, Bewerber-Detailseite und Talent-Pool
 *
 * @package RecruitingPlaybook
 */

import { createRoot } from '@wordpress/element';
import { KanbanPage } from './kanban/KanbanPage';
import { ApplicantDetail } from './applicant/ApplicantDetail';
import { TalentPoolList } from './talent-pool/TalentPoolList';
// LicensePage entfernt - Freemius bietet eigene Account-Seite
import { DashboardPage } from './dashboard/DashboardPage';
import { ApplicationsPage } from './applications/ApplicationsPage';
import { SettingsPage } from './settings/SettingsPage';
import { ReportingPage } from './reporting/ReportingPage';

/**
 * Initialisiert das Kanban-Board
 */
function initKanban() {
	const container = document.getElementById( 'rp-kanban-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <KanbanPage /> );
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
 * Initialisiert das Dashboard
 */
function initDashboard() {
	const container = document.getElementById( 'rp-dashboard-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <DashboardPage /> );
	}
}

/**
 * Initialisiert die Bewerbungsliste
 */
function initApplications() {
	const container = document.getElementById( 'rp-applications-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <ApplicationsPage /> );
	}
}

/**
 * Initialisiert die Einstellungen-Seite
 */
function initSettings() {
	const container = document.getElementById( 'rp-settings-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <SettingsPage /> );
	}
}

/**
 * Initialisiert die Reporting-Seite
 */
function initReporting() {
	const container = document.getElementById( 'rp-reporting-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <ReportingPage /> );
	}
}

/**
 * Initialisiert alle Admin-Komponenten
 */
function initAdmin() {
	initDashboard();
	initApplications();
	initKanban();
	initApplicantDetail();
	initTalentPool();
	initSettings();
	initReporting();
}

// DOMContentLoaded könnte bereits gefeuert haben wenn Script im Footer lädt
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initAdmin );
} else {
	// DOM ist bereits bereit
	initAdmin();
}

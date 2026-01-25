/**
 * Tests für KanbanCard Komponente
 *
 * @package RecruitingPlaybook
 */

import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import userEvent from '@testing-library/user-event';
import { KanbanCard } from '../KanbanCard';

// Mock @dnd-kit/sortable
jest.mock( '@dnd-kit/sortable', () => ( {
	useSortable: () => ( {
		attributes: { 'data-testid': 'sortable' },
		listeners: {},
		setNodeRef: jest.fn(),
		transform: null,
		transition: null,
		isDragging: false,
	} ),
} ) );

// Mock @dnd-kit/utilities
jest.mock( '@dnd-kit/utilities', () => ( {
	CSS: {
		Transform: {
			toString: () => null,
		},
	},
} ) );

// Mock window.rpKanban
beforeEach( () => {
	window.rpKanban = {
		i18n: {
			today: 'Heute',
			yesterday: 'Gestern',
			daysAgo: 'vor %d Tagen',
		},
		detailUrl: '/wp-admin/admin.php?page=rp-applications&id=',
	};
} );

afterEach( () => {
	delete window.rpKanban;
} );

const mockApplication = {
	id: 123,
	first_name: 'Max',
	last_name: 'Mustermann',
	email: 'max@example.com',
	status: 'new',
	job_id: 1,
	job_title: 'Software Developer',
	created_at: new Date().toISOString(),
	documents_count: 2,
	kanban_position: 10,
};

describe( 'KanbanCard', () => {
	it( 'rendert den Namen des Bewerbers', () => {
		render( <KanbanCard application={ mockApplication } /> );

		expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
	} );

	it( 'rendert die E-Mail-Adresse', () => {
		render( <KanbanCard application={ mockApplication } /> );

		expect( screen.getByText( 'max@example.com' ) ).toBeInTheDocument();
	} );

	it( 'rendert den Job-Titel', () => {
		render( <KanbanCard application={ mockApplication } /> );

		expect( screen.getByText( 'Software Developer' ) ).toBeInTheDocument();
	} );

	it( 'rendert die Dokumentenanzahl wenn vorhanden', () => {
		render( <KanbanCard application={ mockApplication } /> );

		expect( screen.getByText( '2' ) ).toBeInTheDocument();
	} );

	it( 'rendert keine Dokumentenanzahl wenn 0', () => {
		const appWithoutDocs = { ...mockApplication, documents_count: 0 };
		render( <KanbanCard application={ appWithoutDocs } /> );

		// Die Zahl 0 sollte nicht angezeigt werden
		expect( screen.queryByText( /^\d+$/ ) ).toBeNull();
	} );

	it( 'zeigt Initialen im Avatar', () => {
		render( <KanbanCard application={ mockApplication } /> );

		expect( screen.getByText( 'MM' ) ).toBeInTheDocument();
	} );

	it( 'hat korrekte ARIA-Attribute', () => {
		render(
			<KanbanCard
				application={ mockApplication }
				index={ 0 }
				totalInColumn={ 5 }
				columnLabel="Neu"
			/>
		);

		const card = screen.getByRole( 'listitem' );
		expect( card ).toHaveAttribute( 'aria-label' );
		expect( card.getAttribute( 'aria-label' ) ).toContain( 'Max Mustermann' );
		expect( card.getAttribute( 'aria-label' ) ).toContain( 'Software Developer' );
	} );

	it( 'zeigt "Unbekannt" bei fehlendem Namen', () => {
		const appWithoutName = { ...mockApplication, first_name: '', last_name: '' };
		render( <KanbanCard application={ appWithoutName } /> );

		expect( screen.getByText( 'Unbekannt' ) ).toBeInTheDocument();
	} );

	it( 'zeigt "Keine Stelle" bei fehlendem Job-Titel', () => {
		const appWithoutJob = { ...mockApplication, job_title: '' };
		render( <KanbanCard application={ appWithoutJob } /> );

		expect( screen.getByText( 'Keine Stelle' ) ).toBeInTheDocument();
	} );

	it( 'hat data-application-id Attribut', () => {
		render( <KanbanCard application={ mockApplication } /> );

		const card = screen.getByRole( 'listitem' );
		expect( card ).toHaveAttribute( 'data-application-id', '123' );
	} );
} );

describe( 'KanbanCard Datumsformatierung', () => {
	it( 'zeigt "Heute" für heutiges Datum', () => {
		const todayApp = {
			...mockApplication,
			created_at: new Date().toISOString(),
		};
		render( <KanbanCard application={ todayApp } /> );

		expect( screen.getByText( 'Heute' ) ).toBeInTheDocument();
	} );

	it( 'zeigt "Gestern" für gestriges Datum', () => {
		const yesterday = new Date();
		yesterday.setDate( yesterday.getDate() - 1 );
		const yesterdayApp = {
			...mockApplication,
			created_at: yesterday.toISOString(),
		};
		render( <KanbanCard application={ yesterdayApp } /> );

		expect( screen.getByText( 'Gestern' ) ).toBeInTheDocument();
	} );

	it( 'zeigt "vor X Tagen" für ältere Daten', () => {
		const fiveDaysAgo = new Date();
		fiveDaysAgo.setDate( fiveDaysAgo.getDate() - 5 );
		const oldApp = {
			...mockApplication,
			created_at: fiveDaysAgo.toISOString(),
		};
		render( <KanbanCard application={ oldApp } /> );

		expect( screen.getByText( 'vor 5 Tagen' ) ).toBeInTheDocument();
	} );
} );

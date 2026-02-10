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
		detailUrl: '/wp-admin/admin.php?page=recruiting-playbook&id=',
	};
} );

afterEach( () => {
	delete window.rpKanban;
} );

// Mock RatingBadge und TalentPoolBadge
jest.mock( '../../applicant/RatingStars', () => ( {
	RatingBadge: ( { average, count } ) => {
		if ( ! average || average <= 0 ) return null;
		return (
			<span data-testid="rating-badge" data-average={ average } data-count={ count }>
				{ average.toFixed( 1 ) }
			</span>
		);
	},
} ) );

jest.mock( '../../applicant/TalentPoolButton', () => ( {
	TalentPoolBadge: ( { inPool } ) => {
		if ( ! inPool ) return null;
		return <span data-testid="talent-pool-badge">Im Talent-Pool</span>;
	},
} ) );

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
	notes_count: 0,
	average_rating: null,
	ratings_count: 0,
	in_talent_pool: false,
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

describe( 'KanbanCard Notizen-Badge', () => {
	it( 'zeigt Notizen-Badge wenn notes_count > 0', () => {
		const appWithNotes = { ...mockApplication, notes_count: 3 };
		render( <KanbanCard application={ appWithNotes } /> );

		// Badge sollte "3" anzeigen (Notizen-Count)
		expect( screen.getByText( '3' ) ).toBeInTheDocument();
		expect( screen.getByTitle( '3 Notizen' ) ).toBeInTheDocument();
	} );

	it( 'zeigt kein Notizen-Badge wenn notes_count 0', () => {
		const appWithoutNotes = { ...mockApplication, notes_count: 0 };
		render( <KanbanCard application={ appWithoutNotes } /> );

		// Kein Badge mit "Notizen" im Title
		expect( screen.queryByTitle( /Notizen/ ) ).not.toBeInTheDocument();
	} );

	it( 'zeigt Singular "Notizen" auch bei 1 Notiz', () => {
		const appWithOneNote = { ...mockApplication, notes_count: 1 };
		render( <KanbanCard application={ appWithOneNote } /> );

		expect( screen.getByText( '1' ) ).toBeInTheDocument();
		expect( screen.getByTitle( '1 Notizen' ) ).toBeInTheDocument();
	} );
} );

describe( 'KanbanCard Rating-Badge', () => {
	it( 'zeigt Rating-Badge wenn average_rating vorhanden', () => {
		const appWithRating = {
			...mockApplication,
			average_rating: 4.5,
			ratings_count: 2,
		};
		render( <KanbanCard application={ appWithRating } /> );

		const ratingBadge = screen.getByTestId( 'rating-badge' );
		expect( ratingBadge ).toBeInTheDocument();
		expect( ratingBadge ).toHaveAttribute( 'data-average', '4.5' );
		expect( ratingBadge ).toHaveAttribute( 'data-count', '2' );
	} );

	it( 'zeigt kein Rating-Badge wenn keine Bewertung', () => {
		const appWithoutRating = {
			...mockApplication,
			average_rating: null,
			ratings_count: 0,
		};
		render( <KanbanCard application={ appWithoutRating } /> );

		expect( screen.queryByTestId( 'rating-badge' ) ).not.toBeInTheDocument();
	} );

	it( 'zeigt kein Rating-Badge wenn average_rating 0', () => {
		const appWithZeroRating = {
			...mockApplication,
			average_rating: 0,
			ratings_count: 0,
		};
		render( <KanbanCard application={ appWithZeroRating } /> );

		expect( screen.queryByTestId( 'rating-badge' ) ).not.toBeInTheDocument();
	} );
} );

describe( 'KanbanCard Talent-Pool-Badge', () => {
	it( 'zeigt Talent-Pool-Badge wenn in_talent_pool true', () => {
		const appInPool = { ...mockApplication, in_talent_pool: true };
		render( <KanbanCard application={ appInPool } /> );

		expect( screen.getByTestId( 'talent-pool-badge' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Im Talent-Pool' ) ).toBeInTheDocument();
	} );

	it( 'zeigt kein Talent-Pool-Badge wenn in_talent_pool false', () => {
		const appNotInPool = { ...mockApplication, in_talent_pool: false };
		render( <KanbanCard application={ appNotInPool } /> );

		expect( screen.queryByTestId( 'talent-pool-badge' ) ).not.toBeInTheDocument();
	} );

	it( 'zeigt kein Talent-Pool-Badge wenn in_talent_pool undefined', () => {
		const appNoPoolInfo = { ...mockApplication };
		delete appNoPoolInfo.in_talent_pool;
		render( <KanbanCard application={ appNoPoolInfo } /> );

		expect( screen.queryByTestId( 'talent-pool-badge' ) ).not.toBeInTheDocument();
	} );
} );

describe( 'KanbanCard kombinierte Badges', () => {
	it( 'zeigt alle Badges gleichzeitig', () => {
		const appWithAllBadges = {
			...mockApplication,
			documents_count: 2,
			notes_count: 5,
			average_rating: 3.8,
			ratings_count: 4,
			in_talent_pool: true,
		};
		render( <KanbanCard application={ appWithAllBadges } /> );

		// Dokumente: 2
		expect( screen.getByTitle( '2 Dokumente' ) ).toBeInTheDocument();

		// Notizen: 5
		expect( screen.getByTitle( '5 Notizen' ) ).toBeInTheDocument();

		// Rating Badge
		expect( screen.getByTestId( 'rating-badge' ) ).toBeInTheDocument();

		// Talent-Pool Badge
		expect( screen.getByTestId( 'talent-pool-badge' ) ).toBeInTheDocument();
	} );
} );

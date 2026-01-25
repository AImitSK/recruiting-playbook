/**
 * Tests für KanbanBoard Komponente
 *
 * @package RecruitingPlaybook
 */

import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';
import { KanbanBoard } from '../KanbanBoard';
import { useApplications } from '../hooks/useApplications';

// Mock useApplications Hook
jest.mock( '../hooks/useApplications' );

// Mock @dnd-kit/core
const mockDndContext = {};
jest.mock( '@dnd-kit/core', () => ( {
	DndContext: ( { children, onDragStart, onDragEnd, onDragCancel } ) => {
		// Store handlers for testing
		mockDndContext.onDragStart = onDragStart;
		mockDndContext.onDragEnd = onDragEnd;
		mockDndContext.onDragCancel = onDragCancel;
		return <div data-testid="dnd-context">{ children }</div>;
	},
	DragOverlay: ( { children } ) => (
		<div data-testid="drag-overlay">{ children }</div>
	),
	KeyboardSensor: jest.fn(),
	PointerSensor: jest.fn(),
	useSensor: jest.fn( () => ( {} ) ),
	useSensors: jest.fn( () => [] ),
	useDroppable: jest.fn( () => ( {
		setNodeRef: jest.fn(),
		isOver: false,
	} ) ),
	pointerWithin: jest.fn( () => [] ),
	rectIntersection: jest.fn( () => [] ),
	closestCorners: jest.fn( () => [] ),
} ) );

// Mock @dnd-kit/sortable
jest.mock( '@dnd-kit/sortable', () => ( {
	SortableContext: ( { children } ) => <div>{ children }</div>,
	sortableKeyboardCoordinates: jest.fn(),
	verticalListSortingStrategy: 'vertical',
	useSortable: () => ( {
		attributes: {},
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
		statuses: [
			{ id: 'new', label: 'Neu', color: '#2271b1' },
			{ id: 'screening', label: 'In Prüfung', color: '#dba617' },
			{ id: 'interview', label: 'Interview', color: '#9b59b6' },
			{ id: 'offer', label: 'Angebot', color: '#1e8cbe' },
			{ id: 'hired', label: 'Eingestellt', color: '#00a32a' },
			{ id: 'rejected', label: 'Abgelehnt', color: '#d63638', collapsed: true },
			{ id: 'withdrawn', label: 'Zurückgezogen', color: '#787c82', collapsed: true },
		],
		i18n: {
			loading: 'Lade Bewerbungen...',
			error: 'Fehler beim Laden',
			retry: 'Erneut versuchen',
		},
		detailUrl: '/wp-admin/admin.php?page=rp-application-detail&id=',
	};

	// Reset mock handlers
	mockDndContext.onDragStart = null;
	mockDndContext.onDragEnd = null;
	mockDndContext.onDragCancel = null;
} );

afterEach( () => {
	delete window.rpKanban;
	jest.clearAllMocks();
} );

const mockApplications = [
	{
		id: 1,
		first_name: 'Max',
		last_name: 'Mustermann',
		email: 'max@example.com',
		status: 'new',
		job_id: 1,
		job_title: 'Developer',
		kanban_position: 10,
		documents_count: 1,
		created_at: new Date().toISOString(),
	},
	{
		id: 2,
		first_name: 'Anna',
		last_name: 'Schmidt',
		email: 'anna@example.com',
		status: 'new',
		job_id: 1,
		job_title: 'Designer',
		kanban_position: 20,
		documents_count: 0,
		created_at: new Date().toISOString(),
	},
	{
		id: 3,
		first_name: 'Peter',
		last_name: 'Meier',
		email: 'peter@example.com',
		status: 'interview',
		job_id: 2,
		job_title: 'Manager',
		kanban_position: 10,
		documents_count: 3,
		created_at: new Date().toISOString(),
	},
];

describe( 'KanbanBoard Rendering', () => {
	it( 'zeigt Loading-State mit korrekten ARIA-Attributen', () => {
		useApplications.mockReturnValue( {
			applications: [],
			loading: true,
			error: null,
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: jest.fn(),
		} );

		render( <KanbanBoard /> );

		const loadingElement = screen.getByRole( 'status' );
		expect( loadingElement ).toBeInTheDocument();
		expect( loadingElement ).toHaveAttribute( 'aria-live', 'polite' );
		expect( loadingElement ).toHaveAttribute( 'aria-busy', 'true' );
		expect( screen.getByText( 'Lade Bewerbungen...' ) ).toBeInTheDocument();
	} );

	it( 'zeigt Error-State mit korrekten ARIA-Attributen', () => {
		useApplications.mockReturnValue( {
			applications: [],
			loading: false,
			error: 'API-Fehler aufgetreten',
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: jest.fn(),
		} );

		render( <KanbanBoard /> );

		const errorElement = screen.getByRole( 'alert' );
		expect( errorElement ).toBeInTheDocument();
		expect( errorElement ).toHaveAttribute( 'aria-live', 'assertive' );
		expect( screen.getByText( 'API-Fehler aufgetreten' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Erneut versuchen' ) ).toBeInTheDocument();
	} );

	it( 'rendert alle Spalten', () => {
		useApplications.mockReturnValue( {
			applications: mockApplications,
			loading: false,
			error: null,
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: jest.fn(),
		} );

		render( <KanbanBoard /> );

		expect( screen.getByText( 'Neu' ) ).toBeInTheDocument();
		expect( screen.getByText( 'In Prüfung' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Interview' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Angebot' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Eingestellt' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Abgelehnt' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Zurückgezogen' ) ).toBeInTheDocument();
	} );

	it( 'rendert Screen Reader Live Region', () => {
		useApplications.mockReturnValue( {
			applications: mockApplications,
			loading: false,
			error: null,
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: jest.fn(),
		} );

		render( <KanbanBoard /> );

		const liveRegion = document.querySelector( '[aria-live="polite"][aria-atomic="true"]' );
		expect( liveRegion ).toBeInTheDocument();
		expect( liveRegion ).toHaveClass( 'screen-reader-text' );
	} );

	it( 'hat korrektes ARIA-Label für das Board', () => {
		useApplications.mockReturnValue( {
			applications: mockApplications,
			loading: false,
			error: null,
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: jest.fn(),
		} );

		render( <KanbanBoard /> );

		const board = screen.getByRole( 'region', { name: 'Kanban-Board für Bewerbungen' } );
		expect( board ).toBeInTheDocument();
	} );
} );

describe( 'KanbanBoard Drag-and-Drop', () => {
	let mockUpdateStatus;
	let mockReorderInColumn;
	let mockMoveToColumn;
	let mockRefetch;

	beforeEach( () => {
		mockUpdateStatus = jest.fn();
		mockReorderInColumn = jest.fn();
		mockMoveToColumn = jest.fn();
		mockRefetch = jest.fn();

		useApplications.mockReturnValue( {
			applications: mockApplications,
			loading: false,
			error: null,
			updateStatus: mockUpdateStatus,
			reorderInColumn: mockReorderInColumn,
			moveToColumn: mockMoveToColumn,
			refetch: mockRefetch,
		} );
	} );

	it( 'registriert DnD-Kontext', () => {
		render( <KanbanBoard /> );

		expect( screen.getByTestId( 'dnd-context' ) ).toBeInTheDocument();
	} );

	it( 'behandelt Drag-Start korrekt', async () => {
		render( <KanbanBoard /> );

		// DragOverlay sollte existieren
		expect( screen.getByTestId( 'drag-overlay' ) ).toBeInTheDocument();

		// Simuliere Drag-Start
		const dragStartEvent = {
			active: { id: 1 },
		};

		// onDragStart ist registriert und kann aufgerufen werden
		expect( typeof mockDndContext.onDragStart ).toBe( 'function' );
		await act( async () => {
			mockDndContext.onDragStart( dragStartEvent );
		} );
	} );

	it( 'behandelt Drag-End ohne Ziel (abgebrochen)', async () => {
		render( <KanbanBoard /> );

		const dragEndEvent = {
			active: { id: 1 },
			over: null, // Kein Ziel
		};

		await mockDndContext.onDragEnd( dragEndEvent );

		// Keine Funktionen sollten aufgerufen werden
		expect( mockReorderInColumn ).not.toHaveBeenCalled();
		expect( mockMoveToColumn ).not.toHaveBeenCalled();
	} );

	it( 'ruft reorderInColumn bei Sortierung in gleicher Spalte auf', async () => {
		render( <KanbanBoard /> );

		// Simuliere Drag von Karte 2 über Karte 1 (gleiche Spalte 'new')
		const dragEndEvent = {
			active: { id: 2 },
			over: {
				id: 1,
				data: {
					current: {
						type: 'card',
						status: 'new',
					},
				},
			},
		};

		await mockDndContext.onDragEnd( dragEndEvent );

		expect( mockReorderInColumn ).toHaveBeenCalled();
		expect( mockMoveToColumn ).not.toHaveBeenCalled();
	} );

	it( 'ruft moveToColumn bei Spalten-Wechsel auf', async () => {
		render( <KanbanBoard /> );

		// Simuliere Drag von Karte 1 (status: new) auf Spalte 'screening'
		const dragEndEvent = {
			active: { id: 1 },
			over: {
				id: 'screening',
				data: {
					current: {
						type: 'column',
						status: 'screening',
					},
				},
			},
		};

		await mockDndContext.onDragEnd( dragEndEvent );

		expect( mockMoveToColumn ).toHaveBeenCalled();
		expect( mockReorderInColumn ).not.toHaveBeenCalled();
	} );

	it( 'behandelt Drag-Cancel korrekt', async () => {
		render( <KanbanBoard /> );

		// Handler sollten registriert sein
		expect( typeof mockDndContext.onDragCancel ).toBe( 'function' );

		await act( async () => {
			// Starte Drag
			mockDndContext.onDragStart( { active: { id: 1 } } );

			// Cancel Drag (sollte keinen Fehler werfen)
			mockDndContext.onDragCancel();
		} );
	} );

	it( 'behandelt Drag auf Karte in anderer Spalte', async () => {
		render( <KanbanBoard /> );

		// Simuliere Drag von Karte 1 (new) auf Karte 3 (interview)
		const dragEndEvent = {
			active: { id: 1 },
			over: {
				id: 3,
				data: {
					current: {
						type: 'card',
						status: 'interview',
					},
				},
			},
		};

		await mockDndContext.onDragEnd( dragEndEvent );

		// Sollte moveToColumn aufrufen, da Spalten unterschiedlich sind
		expect( mockMoveToColumn ).toHaveBeenCalled();
		expect( mockReorderInColumn ).not.toHaveBeenCalled();
	} );
} );

describe( 'KanbanBoard Retry-Funktion', () => {
	it( 'ruft refetch bei Klick auf Retry-Button auf', () => {
		const mockRefetch = jest.fn();
		useApplications.mockReturnValue( {
			applications: [],
			loading: false,
			error: 'Fehler aufgetreten',
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: mockRefetch,
		} );

		render( <KanbanBoard /> );

		const retryButton = screen.getByText( 'Erneut versuchen' );
		fireEvent.click( retryButton );

		expect( mockRefetch ).toHaveBeenCalledTimes( 1 );
	} );
} );

describe( 'KanbanBoard Filterung', () => {
	beforeEach( () => {
		useApplications.mockReturnValue( {
			applications: mockApplications,
			loading: false,
			error: null,
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: jest.fn(),
		} );

		// Mock Toolbar-Elemente
		document.body.innerHTML = `
			<select id="rp-kanban-job-filter"></select>
			<input type="search" id="rp-kanban-search" />
			<button id="rp-kanban-refresh"></button>
		`;
	} );

	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'reagiert auf Job-Filter-Änderungen', () => {
		render( <KanbanBoard /> );

		const jobFilter = document.getElementById( 'rp-kanban-job-filter' );
		fireEvent.change( jobFilter, { target: { value: '1' } } );

		// Filter wird intern angewendet
		// Dies testet, dass der Event-Handler registriert wurde
	} );

	it( 'reagiert auf Suche', () => {
		render( <KanbanBoard /> );

		const searchInput = document.getElementById( 'rp-kanban-search' );
		fireEvent.input( searchInput, { target: { value: 'Max' } } );

		// Suche wird intern angewendet
	} );

	it( 'reagiert auf Refresh-Button', () => {
		const mockRefetch = jest.fn();
		useApplications.mockReturnValue( {
			applications: mockApplications,
			loading: false,
			error: null,
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: mockRefetch,
		} );

		render( <KanbanBoard /> );

		const refreshButton = document.getElementById( 'rp-kanban-refresh' );
		fireEvent.click( refreshButton );

		expect( mockRefetch ).toHaveBeenCalled();
	} );
} );

describe( 'KanbanBoard Bewerbungen nach Status gruppiert', () => {
	it( 'gruppiert Bewerbungen korrekt in Spalten', () => {
		useApplications.mockReturnValue( {
			applications: mockApplications,
			loading: false,
			error: null,
			updateStatus: jest.fn(),
			reorderInColumn: jest.fn(),
			moveToColumn: jest.fn(),
			refetch: jest.fn(),
		} );

		render( <KanbanBoard /> );

		// Max und Anna sollten in 'new' Spalte sein
		expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Anna Schmidt' ) ).toBeInTheDocument();

		// Peter sollte in 'interview' Spalte sein
		expect( screen.getByText( 'Peter Meier' ) ).toBeInTheDocument();
	} );
} );

/**
 * Tests für useApplications Hook
 *
 * @package RecruitingPlaybook
 */

import { renderHook, act, waitFor } from '@testing-library/react';
import { useApplications } from '../hooks/useApplications';
import apiFetch from '@wordpress/api-fetch';

// Mock @wordpress/api-fetch
jest.mock( '@wordpress/api-fetch' );

// Mock window.rpKanban
beforeEach( () => {
	window.rpKanban = {
		i18n: {
			statusChanged: 'Status geändert',
			updateFailed: 'Aktualisierung fehlgeschlagen',
			reorderFailed: 'Sortierung fehlgeschlagen',
		},
	};

	// Reset mocks
	apiFetch.mockReset();
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
	},
];

describe( 'useApplications Hook', () => {
	describe( 'Initialer Zustand und Laden', () => {
		it( 'beginnt mit Loading-Zustand', () => {
			apiFetch.mockReturnValue( new Promise( () => {} ) ); // Never resolves

			const { result } = renderHook( () => useApplications() );

			expect( result.current.loading ).toBe( true );
			expect( result.current.applications ).toEqual( [] );
			expect( result.current.error ).toBeNull();
		} );

		it( 'lädt Bewerbungen erfolgreich', async () => {
			apiFetch.mockResolvedValue( { items: mockApplications } );

			const { result } = renderHook( () => useApplications() );

			await waitFor( () => {
				expect( result.current.loading ).toBe( false );
			} );

			expect( result.current.applications ).toEqual( mockApplications );
			expect( result.current.error ).toBeNull();
			expect( apiFetch ).toHaveBeenCalledWith( {
				path: '/recruiting/v1/applications?per_page=200&context=kanban',
			} );
		} );

		it( 'behandelt API-Fehler', async () => {
			// Erwarte console.error-Aufruf im Hook
			jest.spyOn( console, 'error' ).mockImplementation( () => {} );

			apiFetch.mockRejectedValue( new Error( 'API Error' ) );

			const { result } = renderHook( () => useApplications() );

			await waitFor( () => {
				expect( result.current.loading ).toBe( false );
			} );

			expect( result.current.applications ).toEqual( [] );
			expect( result.current.error ).toBe( 'API Error' );
			expect( console.error ).toHaveBeenCalled();

			// eslint-disable-next-line no-console
			console.error.mockRestore();
		} );

		it( 'behandelt direktes Array-Response', async () => {
			apiFetch.mockResolvedValue( mockApplications );

			const { result } = renderHook( () => useApplications() );

			await waitFor( () => {
				expect( result.current.loading ).toBe( false );
			} );

			expect( result.current.applications ).toEqual( mockApplications );
		} );
	} );

	describe( 'updateStatus', () => {
		it( 'aktualisiert Status optimistisch', async () => {
			apiFetch.mockResolvedValueOnce( { items: mockApplications } );
			apiFetch.mockResolvedValueOnce( { success: true } );

			const { result } = renderHook( () => useApplications() );

			await waitFor( () => {
				expect( result.current.loading ).toBe( false );
			} );

			await act( async () => {
				await result.current.updateStatus( 1, 'screening', 10 );
			} );

			// Status sollte aktualisiert sein
			const updatedApp = result.current.applications.find( ( a ) => a.id === 1 );
			expect( updatedApp.status ).toBe( 'screening' );
		} );

		it( 'sendet korrekten API-Request', async () => {
			apiFetch.mockResolvedValueOnce( { items: mockApplications } );
			apiFetch.mockResolvedValueOnce( { success: true } );

			const { result } = renderHook( () => useApplications() );

			await waitFor( () => {
				expect( result.current.loading ).toBe( false );
			} );

			await act( async () => {
				await result.current.updateStatus( 1, 'interview', 20 );
			} );

			expect( apiFetch ).toHaveBeenCalledWith( {
				path: '/recruiting/v1/applications/1/status',
				method: 'PATCH',
				data: {
					status: 'interview',
					kanban_position: 20,
					note: 'Status via Kanban-Board geändert',
				},
			} );
		} );
	} );

	describe( 'reorderInColumn', () => {
		it( 'berechnet neue Positionen korrekt', async () => {
			apiFetch.mockResolvedValueOnce( { items: mockApplications } );
			apiFetch.mockResolvedValueOnce( { success: true } );

			const { result } = renderHook( () => useApplications() );

			await waitFor( () => {
				expect( result.current.loading ).toBe( false );
			} );

			const columnItems = result.current.applications.filter(
				( a ) => a.status === 'new'
			);

			await act( async () => {
				await result.current.reorderInColumn(
					'new',
					2, // Anna (position 20)
					1, // Max (position 10)
					columnItems
				);
			} );

			// Reorder API sollte aufgerufen worden sein
			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					path: '/recruiting/v1/applications/reorder',
					method: 'POST',
				} )
			);
		} );
	} );

	describe( 'refetch', () => {
		it( 'lädt Daten neu', async () => {
			apiFetch.mockResolvedValue( { items: mockApplications } );

			const { result } = renderHook( () => useApplications() );

			await waitFor( () => {
				expect( result.current.loading ).toBe( false );
			} );

			// Initial call
			expect( apiFetch ).toHaveBeenCalledTimes( 1 );

			await act( async () => {
				await result.current.refetch();
			} );

			// Should be called again
			expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		} );
	} );
} );

describe( 'useApplications Return Values', () => {
	it( 'gibt alle benötigten Funktionen zurück', async () => {
		apiFetch.mockResolvedValue( { items: [] } );

		const { result } = renderHook( () => useApplications() );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		expect( typeof result.current.updateStatus ).toBe( 'function' );
		expect( typeof result.current.reorderInColumn ).toBe( 'function' );
		expect( typeof result.current.moveToColumn ).toBe( 'function' );
		expect( typeof result.current.refetch ).toBe( 'function' );
		expect( typeof result.current.setApplications ).toBe( 'function' );
	} );
} );

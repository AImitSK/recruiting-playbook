/**
 * useActiveFields Hook Tests
 *
 * @package RecruitingPlaybook\Tests
 */

import { renderHook, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import { useActiveFields, invalidateActiveFieldsCache } from '../useActiveFields';

// Mock apiFetch
jest.mock( '@wordpress/api-fetch' );

describe( 'useActiveFields', () => {
	const mockFieldsResponse = {
		fields: [
			{
				field_key: 'first_name',
				field_type: 'text',
				label: 'Vorname',
				is_system: true,
				is_required: true,
			},
			{
				field_key: 'email',
				field_type: 'email',
				label: 'E-Mail',
				is_system: true,
				is_required: true,
			},
			{
				field_key: 'custom_field',
				field_type: 'text',
				label: 'Custom',
				is_system: false,
				is_required: false,
			},
		],
		system_fields: [
			{
				field_key: 'file_upload',
				type: 'file_upload',
				label: 'Dokumente',
			},
			{
				field_key: 'privacy_consent',
				type: 'privacy_consent',
				label: 'Datenschutz',
			},
		],
	};

	beforeEach( () => {
		jest.clearAllMocks();
		invalidateActiveFieldsCache();
		apiFetch.mockResolvedValue( mockFieldsResponse );
	} );

	test( 'lädt Felder von der API', async () => {
		const { result } = renderHook( () => useActiveFields() );

		// Initial loading
		expect( result.current.loading ).toBe( true );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		expect( result.current.fields ).toHaveLength( 3 );
		expect( result.current.systemFields ).toHaveLength( 2 );
		expect( result.current.error ).toBeNull();
	} );

	test( 'gibt korrekte Felder-Struktur zurück', async () => {
		const { result } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		// Prüfe erstes Feld
		const firstField = result.current.fields[ 0 ];
		expect( firstField.field_key ).toBe( 'first_name' );
		expect( firstField.field_type ).toBe( 'text' );
		expect( firstField.label ).toBe( 'Vorname' );
	} );

	test( 'gibt System-Felder separat zurück', async () => {
		const { result } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		expect( result.current.systemFields ).toContainEqual(
			expect.objectContaining( { field_key: 'file_upload' } )
		);
		expect( result.current.systemFields ).toContainEqual(
			expect.objectContaining( { field_key: 'privacy_consent' } )
		);
	} );

	test( 'allFields kombiniert fields und systemFields', async () => {
		const { result } = renderHook( () => useActiveFields( { includeSystem: true } ) );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		// 3 normale Felder + 2 System-Felder
		expect( result.current.allFields ).toHaveLength( 5 );
	} );

	test( 'allFields ohne System-Felder wenn includeSystem false', async () => {
		const { result } = renderHook( () => useActiveFields( { includeSystem: false } ) );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		// Nur 3 normale Felder
		expect( result.current.allFields ).toHaveLength( 3 );
	} );

	test( 'verwendet Cache bei wiederholten Aufrufen', async () => {
		const { result: result1 } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result1.current.loading ).toBe( false );
		} );

		// Zweiter Hook sollte Cache verwenden
		const { result: result2 } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result2.current.loading ).toBe( false );
		} );

		// API sollte nur einmal aufgerufen werden
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'refresh() lädt neu und ignoriert Cache', async () => {
		const { result } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		// Refresh aufrufen
		result.current.refresh();

		await waitFor( () => {
			expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		} );
	} );

	test( 'behandelt API-Fehler korrekt', async () => {
		apiFetch.mockRejectedValue( new Error( 'API Error' ) );

		const { result } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		expect( result.current.error ).toBe( 'API Error' );
		expect( result.current.fields ).toEqual( [] );
	} );

	test( 'forceRefresh ignoriert Cache beim initialen Laden', async () => {
		// Ersten Hook laden um Cache zu füllen
		const { result: result1 } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result1.current.loading ).toBe( false );
		} );

		// Zweiter Hook mit forceRefresh
		const { result: result2 } = renderHook( () => useActiveFields( { forceRefresh: true } ) );

		await waitFor( () => {
			expect( result2.current.loading ).toBe( false );
		} );

		// Sollte API zweimal aufgerufen haben
		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
	} );
} );

describe( 'invalidateActiveFieldsCache', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		invalidateActiveFieldsCache();
		apiFetch.mockResolvedValue( {
			fields: [],
			system_fields: [],
		} );
	} );

	test( 'invalidiert Cache und erzwingt neuen API-Aufruf', async () => {
		const { result: result1 } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result1.current.loading ).toBe( false );
		} );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		// Cache invalidieren
		invalidateActiveFieldsCache();

		// Neuer Hook sollte API erneut aufrufen
		const { result: result2 } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result2.current.loading ).toBe( false );
		} );

		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
	} );
} );

/**
 * Tests für useFormConfig Hook
 *
 * @package RecruitingPlaybook
 */

import { renderHook, act, waitFor } from '@testing-library/react';
import { useFormConfig } from '../hooks/useFormConfig';
import apiFetch from '@wordpress/api-fetch';

// Mock @wordpress/api-fetch
jest.mock( '@wordpress/api-fetch' );

// Mock window.rpFormBuilderData
beforeEach( () => {
	window.rpFormBuilderData = {
		restNamespace: 'recruiting/v1',
	};
	jest.clearAllMocks();
} );

afterEach( () => {
	delete window.rpFormBuilderData;
} );

const mockDraftConfig = {
	version: 2,
	settings: {
		submit_button_text: 'Bewerbung absenden',
	},
	steps: [
		{
			id: 'step_personal',
			title: 'Persönliche Daten',
			position: 1,
			deletable: false,
			is_finale: false,
			fields: [
				{ field_key: 'first_name', is_visible: true, is_required: true, is_removable: false },
				{ field_key: 'last_name', is_visible: true, is_required: true, is_removable: false },
				{ field_key: 'email', is_visible: true, is_required: true, is_removable: false },
			],
			system_fields: [],
		},
		{
			id: 'step_documents',
			title: 'Dokumente',
			position: 2,
			deletable: true,
			is_finale: false,
			fields: [],
			system_fields: [
				{
					field_key: 'file_upload',
					type: 'file_upload',
					settings: {
						label: 'Dokumente hochladen',
						allowed_types: [ 'pdf', 'doc', 'docx' ],
						max_file_size: 10,
						max_files: 5,
					},
				},
			],
		},
		{
			id: 'step_finale',
			title: 'Finale',
			position: 3,
			deletable: false,
			is_finale: true,
			fields: [],
			system_fields: [
				{
					field_key: 'summary',
					type: 'summary',
					settings: { label: 'Zusammenfassung' },
				},
				{
					field_key: 'privacy_consent',
					type: 'privacy_consent',
					is_removable: false,
					settings: { label: 'Datenschutz-Zustimmung' },
				},
			],
		},
	],
};

const mockApiResponse = {
	draft: mockDraftConfig,
	available_fields: [
		{ field_key: 'first_name', label: 'Vorname', field_type: 'text' },
		{ field_key: 'last_name', label: 'Nachname', field_type: 'text' },
		{ field_key: 'email', label: 'E-Mail', field_type: 'email' },
		{ field_key: 'phone', label: 'Telefon', field_type: 'tel' },
	],
	published_version: 1,
	has_changes: false,
};

describe( 'useFormConfig Hook', () => {
	describe( 'Initial State', () => {
		it( 'starts with loading state', () => {
			apiFetch.mockImplementation( () => new Promise( () => {} ) );

			const { result } = renderHook( () => useFormConfig() );

			expect( result.current.isLoading ).toBe( true );
			expect( result.current.draft ).toBe( null );
		} );

		it( 'loads config on mount', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			expect( result.current.draft ).toEqual( mockDraftConfig );
			expect( result.current.availableFields ).toHaveLength( 4 );
			expect( result.current.publishedVersion ).toBe( 1 );
		} );

		it( 'handles error during fetch', async () => {
			apiFetch.mockRejectedValueOnce( new Error( 'API-Fehler' ) );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			expect( result.current.error ).toBe( 'API-Fehler' );
		} );
	} );

	describe( 'Step Operations', () => {
		it( 'separates regular steps from finale step', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			expect( result.current.regularSteps ).toHaveLength( 2 );
			expect( result.current.finaleStep ).toBeDefined();
			expect( result.current.finaleStep.is_finale ).toBe( true );
		} );
	} );

	describe( 'Field Operations', () => {
		it( 'blocks removal of non-removable fields', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			// Try to remove first_name (is_removable: false)
			let removed;
			act( () => {
				removed = result.current.removeFieldFromStep( 'step_personal', 'first_name' );
			} );

			expect( removed ).toBe( false );
			expect( result.current.error ).toBe( 'Dieses Pflichtfeld kann nicht entfernt werden' );
		} );

		it( 'getUnusedFields returns fields not in any step', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			const unused = result.current.getUnusedFields();

			// phone is not used in any step
			expect( unused ).toHaveLength( 1 );
			expect( unused[ 0 ].field_key ).toBe( 'phone' );
		} );

		it( 'getFieldDefinition returns field definition by key', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			const fieldDef = result.current.getFieldDefinition( 'first_name' );

			expect( fieldDef ).toBeDefined();
			expect( fieldDef.label ).toBe( 'Vorname' );
		} );
	} );

	describe( 'System Field Operations', () => {
		it( 'updateSystemFieldInStep updates system field settings', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );
			// Mock the save call
			apiFetch.mockResolvedValueOnce( { has_changes: true } );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			act( () => {
				result.current.updateSystemFieldInStep( 'step_documents', 'file_upload', {
					label: 'Neue Bezeichnung',
					max_file_size: 20,
				} );
			} );

			// Wait for debounced save
			await waitFor( () => {
				const step = result.current.draft.steps.find( s => s.id === 'step_documents' );
				const systemField = step.system_fields.find( f => f.field_key === 'file_upload' );
				expect( systemField.settings.label ).toBe( 'Neue Bezeichnung' );
				expect( systemField.settings.max_file_size ).toBe( 20 );
			} );
		} );

		it( 'updateSystemFieldInStep merges with existing settings', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );
			apiFetch.mockResolvedValueOnce( { has_changes: true } );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			// Update only max_files, other settings should remain
			act( () => {
				result.current.updateSystemFieldInStep( 'step_documents', 'file_upload', {
					max_files: 10,
				} );
			} );

			await waitFor( () => {
				const step = result.current.draft.steps.find( s => s.id === 'step_documents' );
				const systemField = step.system_fields.find( f => f.field_key === 'file_upload' );

				// New value should be set
				expect( systemField.settings.max_files ).toBe( 10 );
				// Old values should remain
				expect( systemField.settings.label ).toBe( 'Dokumente hochladen' );
				expect( systemField.settings.max_file_size ).toBe( 10 );
			} );
		} );

		it( 'updateSystemFieldInStep does not affect other steps', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );
			apiFetch.mockResolvedValueOnce( { has_changes: true } );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			act( () => {
				result.current.updateSystemFieldInStep( 'step_finale', 'privacy_consent', {
					consent_text: 'Neuer Text',
				} );
			} );

			await waitFor( () => {
				// step_documents should not be affected
				const documentsStep = result.current.draft.steps.find( s => s.id === 'step_documents' );
				const fileUpload = documentsStep.system_fields.find( f => f.field_key === 'file_upload' );
				expect( fileUpload.settings.label ).toBe( 'Dokumente hochladen' );

				// step_finale should be updated
				const finaleStep = result.current.draft.steps.find( s => s.id === 'step_finale' );
				const privacyConsent = finaleStep.system_fields.find( f => f.field_key === 'privacy_consent' );
				expect( privacyConsent.settings.consent_text ).toBe( 'Neuer Text' );
			} );
		} );
	} );

	describe( 'Draft/Publish Workflow', () => {
		it( 'sets hasChanges to true when updating', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );
			apiFetch.mockResolvedValueOnce( { has_changes: true } );

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			expect( result.current.hasChanges ).toBe( false );

			act( () => {
				result.current.updateStep( 'step_personal', { title: 'Neuer Titel' } );
			} );

			expect( result.current.hasChanges ).toBe( true );
		} );

		it( 'publish calls API and updates state', async () => {
			apiFetch.mockResolvedValueOnce( mockApiResponse );
			apiFetch.mockResolvedValueOnce( { has_changes: true } ); // saveDraft
			apiFetch.mockResolvedValueOnce( { published_version: 2 } ); // publish

			const { result } = renderHook( () => useFormConfig() );

			await waitFor( () => {
				expect( result.current.isLoading ).toBe( false );
			} );

			let publishResult;
			await act( async () => {
				publishResult = await result.current.publish();
			} );

			expect( publishResult ).toBe( true );
			expect( result.current.publishedVersion ).toBe( 2 );
			expect( result.current.hasChanges ).toBe( false );
		} );
	} );
} );

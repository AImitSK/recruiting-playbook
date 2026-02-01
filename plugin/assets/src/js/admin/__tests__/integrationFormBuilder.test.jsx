/**
 * Form Builder Integration Tests
 *
 * Testet den kompletten Workflow des Form Builders im Frontend:
 * - DynamicFieldRenderer mit verschiedenen Feldtypen
 * - useActiveFields Hook mit API
 * - ApplicantDetail Integration
 *
 * @package RecruitingPlaybook\Tests
 */

import { render, screen, waitFor, within } from '@testing-library/react';
import '@testing-library/jest-dom';
import apiFetch from '@wordpress/api-fetch';
import { DynamicFieldRenderer, FieldDisplay } from '../components/shared/DynamicFieldRenderer';
import { useActiveFields, invalidateActiveFieldsCache } from '../hooks/useActiveFields';
import { renderHook } from '@testing-library/react';

// Mock apiFetch
jest.mock( '@wordpress/api-fetch' );

// Mock WordPress i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

describe( 'Form Builder Integration: DynamicFieldRenderer', () => {
	const completeFieldSet = [
		{ field_key: 'first_name', field_type: 'text', label: 'Vorname', is_required: true },
		{ field_key: 'last_name', field_type: 'text', label: 'Nachname', is_required: true },
		{ field_key: 'email', field_type: 'email', label: 'E-Mail', is_required: true },
		{ field_key: 'phone', field_type: 'phone', label: 'Telefon', is_required: false },
		{ field_key: 'website', field_type: 'url', label: 'Website', is_required: false },
		{ field_key: 'message', field_type: 'textarea', label: 'Nachricht', is_required: false },
		{
			field_key: 'experience',
			field_type: 'select',
			label: 'Berufserfahrung',
			is_required: false,
			options: [
				{ value: '0-2', label: '0-2 Jahre' },
				{ value: '3-5', label: '3-5 Jahre' },
				{ value: '5+', label: 'Mehr als 5 Jahre' },
			],
		},
		{ field_key: 'newsletter', field_type: 'checkbox', label: 'Newsletter', is_required: false },
	];

	const completeData = {
		first_name: 'Max',
		last_name: 'Mustermann',
		email: 'max@example.com',
		phone: '+49 123 456789',
		website: 'https://example.com',
		message: 'Meine Bewerbung\nmit Zeilenumbruch',
		experience: '3-5',
		newsletter: true,
	};

	test( 'rendert alle Feldtypen korrekt', () => {
		render( <DynamicFieldRenderer fields={ completeFieldSet } data={ completeData } /> );

		// Text-Felder
		expect( screen.getByText( 'Vorname' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Max' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Nachname' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Mustermann' ) ).toBeInTheDocument();

		// Email als Link
		const emailLink = screen.getByRole( 'link', { name: /max@example\.com/ } );
		expect( emailLink ).toHaveAttribute( 'href', 'mailto:max@example.com' );

		// Phone als Link
		const phoneLink = screen.getByRole( 'link', { name: /\+49 123 456789/ } );
		expect( phoneLink ).toHaveAttribute( 'href', 'tel:+49 123 456789' );

		// URL als externer Link
		const urlLink = screen.getByRole( 'link', { name: /example\.com/ } );
		expect( urlLink ).toHaveAttribute( 'href', 'https://example.com' );
		expect( urlLink ).toHaveAttribute( 'target', '_blank' );

		// Select zeigt Label statt Value
		expect( screen.getByText( '3-5 Jahre' ) ).toBeInTheDocument();

		// Checkbox zeigt Ja/Nein
		expect( screen.getByText( 'Ja' ) ).toBeInTheDocument();
	} );

	test( 'versteckt leere optionale Felder wenn hideEmptyOptional=true', () => {
		const dataWithMissing = {
			first_name: 'Max',
			last_name: 'Mustermann',
			email: 'max@example.com',
			phone: '', // Leer
			website: '', // Leer
			message: '', // Leer
			experience: '', // Leer
			newsletter: false,
		};

		render(
			<DynamicFieldRenderer
				fields={ completeFieldSet }
				data={ dataWithMissing }
				hideEmptyOptional={ true }
			/>
		);

		// Pflichtfelder immer sichtbar
		expect( screen.getByText( 'Vorname' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Nachname' ) ).toBeInTheDocument();
		expect( screen.getByText( 'E-Mail' ) ).toBeInTheDocument();

		// Optionale leere Felder versteckt
		expect( screen.queryByText( 'Telefon' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Website' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Nachricht' ) ).not.toBeInTheDocument();
	} );

	test( 'zeigt alle Felder wenn hideEmptyOptional=false', () => {
		const dataWithMissing = {
			first_name: 'Max',
			last_name: 'Mustermann',
			email: 'max@example.com',
			phone: '',
			website: '',
			message: '',
			experience: '',
			newsletter: false,
		};

		render(
			<DynamicFieldRenderer
				fields={ completeFieldSet }
				data={ dataWithMissing }
				hideEmptyOptional={ false }
			/>
		);

		// Alle Felder sichtbar (auch leere)
		expect( screen.getByText( 'Telefon' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Website' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Nachricht' ) ).toBeInTheDocument();
	} );

	test( 'rendert zwei-Spalten-Layout korrekt', () => {
		const { container } = render(
			<DynamicFieldRenderer
				fields={ completeFieldSet }
				data={ completeData }
				layout="two-column"
			/>
		);

		const grid = container.firstChild;
		expect( grid ).toHaveStyle( { display: 'grid' } );
	} );
} );

describe( 'Form Builder Integration: URL-Sicherheit', () => {
	test( 'blockiert javascript: URLs in URL-Feldern', () => {
		const maliciousData = {
			website: 'javascript:alert(1)',
		};

		const fields = [
			{ field_key: 'website', field_type: 'url', label: 'Website', is_required: false },
		];

		render( <DynamicFieldRenderer fields={ fields } data={ maliciousData } /> );

		// URL sollte als Text angezeigt werden, nicht als klickbarer Link
		const links = screen.queryAllByRole( 'link' );
		const jsLink = links.find( ( link ) => link.getAttribute( 'href' )?.startsWith( 'javascript:' ) );
		expect( jsLink ).toBeUndefined();
	} );

	test( 'erlaubt https: URLs', () => {
		const safeData = {
			website: 'https://example.com',
		};

		const fields = [
			{ field_key: 'website', field_type: 'url', label: 'Website', is_required: false },
		];

		render( <DynamicFieldRenderer fields={ fields } data={ safeData } /> );

		const link = screen.getByRole( 'link', { name: /example\.com/ } );
		expect( link ).toHaveAttribute( 'href', 'https://example.com' );
	} );

	test( 'erlaubt http: URLs', () => {
		const httpData = {
			website: 'http://example.com',
		};

		const fields = [
			{ field_key: 'website', field_type: 'url', label: 'Website', is_required: false },
		];

		render( <DynamicFieldRenderer fields={ fields } data={ httpData } /> );

		const link = screen.getByRole( 'link', { name: /example\.com/ } );
		expect( link ).toHaveAttribute( 'href', 'http://example.com' );
	} );
} );

describe( 'Form Builder Integration: useActiveFields Hook', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		invalidateActiveFieldsCache();
	} );

	const mockApiResponse = {
		fields: [
			{ field_key: 'first_name', field_type: 'text', label: 'Vorname', is_required: true },
			{ field_key: 'email', field_type: 'email', label: 'E-Mail', is_required: true },
		],
		system_fields: [
			{ field_key: 'file_upload', type: 'file_upload', label: 'Dokumente' },
			{ field_key: 'privacy_consent', type: 'privacy_consent', label: 'Datenschutz' },
		],
	};

	test( 'lädt Felder von der API und cached sie', async () => {
		apiFetch.mockResolvedValue( mockApiResponse );

		const { result } = renderHook( () => useActiveFields() );

		// Initial loading
		expect( result.current.loading ).toBe( true );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		// Felder geladen
		expect( result.current.fields ).toHaveLength( 2 );
		expect( result.current.systemFields ).toHaveLength( 2 );
		expect( result.current.error ).toBeNull();

		// Nur ein API-Call (Cache funktioniert)
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		// Zweiter Hook sollte Cache verwenden
		const { result: result2 } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result2.current.loading ).toBe( false );
		} );

		// Immer noch nur ein API-Call
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'forceRefresh ignoriert Cache', async () => {
		apiFetch.mockResolvedValue( mockApiResponse );

		// Ersten Hook laden
		const { result: result1 } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result1.current.loading ).toBe( false );
		} );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		// Zweiter Hook mit forceRefresh
		const { result: result2 } = renderHook( () => useActiveFields( { forceRefresh: true } ) );

		await waitFor( () => {
			expect( result2.current.loading ).toBe( false );
		} );

		// Sollte zweimal aufgerufen worden sein
		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
	} );

	test( 'behandelt API-Fehler korrekt', async () => {
		apiFetch.mockRejectedValue( new Error( 'Network Error' ) );

		const { result } = renderHook( () => useActiveFields() );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		expect( result.current.error ).toBe( 'Network Error' );
		expect( result.current.fields ).toEqual( [] );
	} );

	test( 'invalidateActiveFieldsCache erzwingt neuen API-Aufruf', async () => {
		apiFetch.mockResolvedValue( mockApiResponse );

		// Ersten Hook laden
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

	test( 'allFields kombiniert fields und systemFields', async () => {
		apiFetch.mockResolvedValue( mockApiResponse );

		const { result } = renderHook( () => useActiveFields( { includeSystem: true } ) );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		// 2 normale Felder + 2 System-Felder
		expect( result.current.allFields ).toHaveLength( 4 );
	} );

	test( 'allFields ohne System-Felder wenn includeSystem=false', async () => {
		apiFetch.mockResolvedValue( mockApiResponse );

		const { result } = renderHook( () => useActiveFields( { includeSystem: false } ) );

		await waitFor( () => {
			expect( result.current.loading ).toBe( false );
		} );

		// Nur 2 normale Felder
		expect( result.current.allFields ).toHaveLength( 2 );
	} );
} );

describe( 'Form Builder Integration: File Fields', () => {
	test( 'rendert einzelne Datei mit Download-Link', () => {
		const fields = [
			{ field_key: 'resume', field_type: 'file', label: 'Lebenslauf', is_required: false },
		];

		const data = {
			resume: {
				filename: 'lebenslauf.pdf',
				view_url: 'https://example.com/view/1',
				download_url: 'https://example.com/download/1',
			},
		};

		render( <DynamicFieldRenderer fields={ fields } data={ data } /> );

		expect( screen.getByText( 'lebenslauf.pdf' ) ).toBeInTheDocument();
	} );

	test( 'rendert mehrere Dateien', () => {
		const fields = [
			{ field_key: 'documents', field_type: 'file', label: 'Dokumente', is_required: false },
		];

		const data = {
			documents: [
				{ filename: 'lebenslauf.pdf' },
				{ filename: 'zeugnis.pdf' },
				{ filename: 'zertifikat.pdf' },
			],
		};

		render( <DynamicFieldRenderer fields={ fields } data={ data } /> );

		expect( screen.getByText( 'lebenslauf.pdf' ) ).toBeInTheDocument();
		expect( screen.getByText( 'zeugnis.pdf' ) ).toBeInTheDocument();
		expect( screen.getByText( 'zertifikat.pdf' ) ).toBeInTheDocument();
	} );

	test( 'zeigt Bindestrich für leeres Datei-Array', () => {
		const fields = [
			{ field_key: 'documents', field_type: 'file', label: 'Dokumente', is_required: true },
		];

		render( <FieldDisplay field={ fields[ 0 ] } value={ [] } /> );

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );
} );

describe( 'Form Builder Integration: Select mit Options', () => {
	test( 'zeigt Display-Label statt Value', () => {
		const fields = [
			{
				field_key: 'experience',
				field_type: 'select',
				label: 'Erfahrung',
				is_required: false,
				options: [
					{ value: 'junior', label: 'Junior (0-2 Jahre)' },
					{ value: 'mid', label: 'Mid-Level (3-5 Jahre)' },
					{ value: 'senior', label: 'Senior (5+ Jahre)' },
				],
			},
		];

		render( <DynamicFieldRenderer fields={ fields } data={ { experience: 'mid' } } /> );

		expect( screen.getByText( 'Mid-Level (3-5 Jahre)' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'mid' ) ).not.toBeInTheDocument();
	} );

	test( 'zeigt Value wenn Option nicht gefunden', () => {
		const fields = [
			{
				field_key: 'experience',
				field_type: 'select',
				label: 'Erfahrung',
				is_required: false,
				options: [
					{ value: 'junior', label: 'Junior' },
				],
			},
		];

		render( <DynamicFieldRenderer fields={ fields } data={ { experience: 'unknown_value' } } /> );

		// Fallback: zeigt den Value direkt
		expect( screen.getByText( 'unknown_value' ) ).toBeInTheDocument();
	} );
} );

describe( 'Form Builder Integration: Textarea mit Zeilenumbrüchen', () => {
	test( 'rendert Textarea mit white-space: pre-wrap', () => {
		const fields = [
			{ field_key: 'message', field_type: 'textarea', label: 'Nachricht', is_required: false },
		];

		render(
			<DynamicFieldRenderer
				fields={ fields }
				data={ { message: 'Zeile 1\nZeile 2\nZeile 3' } }
			/>
		);

		const text = screen.getByText( /Zeile 1/ );
		expect( text ).toHaveStyle( { whiteSpace: 'pre-wrap' } );
	} );
} );

/**
 * ApplicantDetail Tests
 *
 * @package RecruitingPlaybook\Tests
 */

import { render, screen, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom';
import apiFetch from '@wordpress/api-fetch';
import { ApplicantDetail } from '../ApplicantDetail';

// Mock apiFetch
jest.mock( '@wordpress/api-fetch' );

// Mock WordPress i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

// Mock useActiveFields
jest.mock( '../../hooks/useActiveFields', () => ( {
	useActiveFields: jest.fn( () => ( {
		fields: [],
		systemFields: [],
		allFields: [],
		loading: false,
		error: null,
		refresh: jest.fn(),
	} ) ),
} ) );

// Mock child components
jest.mock( '../NotesPanel', () => ( {
	NotesPanel: () => <div data-testid="notes-panel">NotesPanel</div>,
} ) );

jest.mock( '../RatingStars', () => ( {
	RatingDetailed: () => <div data-testid="rating-detailed">RatingDetailed</div>,
} ) );

jest.mock( '../Timeline', () => ( {
	Timeline: () => <div data-testid="timeline">Timeline</div>,
} ) );

jest.mock( '../TalentPoolButton', () => ( {
	TalentPoolButton: () => <div data-testid="talent-pool-button">TalentPoolButton</div>,
} ) );

jest.mock( '../EmailTab', () => ( {
	EmailTab: () => <div data-testid="email-tab">EmailTab</div>,
} ) );

jest.mock( '../CustomFieldsPanel', () => ( {
	CustomFieldsPanel: () => <div data-testid="custom-fields-panel">CustomFieldsPanel</div>,
} ) );

// Import after mocking
const { useActiveFields } = require( '../../hooks/useActiveFields' );

const mockApplication = {
	id: 123,
	status: 'new',
	created_at: '2025-01-15T10:30:00',
	candidate_id: 456,
	candidate: {
		first_name: 'Max',
		last_name: 'Mustermann',
		email: 'max@example.com',
		phone: '+49 123 456789',
		salutation: 'Herr',
	},
	job: {
		title: 'Software Developer',
	},
	documents: [
		{ id: 1, filename: 'lebenslauf.pdf', view_url: '/view/1', download_url: '/download/1' },
	],
	form_data: {
		experience: '3-5',
		start_date: '01.03.2025',
	},
	cover_letter: 'Meine Bewerbung...',
	in_talent_pool: false,
};

const mockActiveFields = [
	{ field_key: 'first_name', field_type: 'text', label: 'Vorname', is_required: true },
	{ field_key: 'last_name', field_type: 'text', label: 'Nachname', is_required: true },
	{ field_key: 'email', field_type: 'email', label: 'E-Mail', is_required: true },
	{ field_key: 'phone', field_type: 'phone', label: 'Telefon', is_required: false },
	{ field_key: 'experience', field_type: 'select', label: 'Erfahrung', is_required: false, options: [
		{ value: '0-2', label: '0-2 Jahre' },
		{ value: '3-5', label: '3-5 Jahre' },
		{ value: '5+', label: '5+ Jahre' },
	] },
];

describe( 'ApplicantDetail', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.rpApplicant = {
			listUrl: '/wp-admin/admin.php?page=recruiting-playbook',
			canSendEmails: true,
			logoUrl: '',
		};

		// Default API responses
		apiFetch.mockImplementation( ( { path } ) => {
			if ( path.includes( '/applications/' ) && ! path.includes( '/notes' ) && ! path.includes( '/timeline' ) && ! path.includes( '/emails' ) ) {
				return Promise.resolve( mockApplication );
			}
			if ( path.includes( '/notes' ) ) {
				return Promise.resolve( [] );
			}
			if ( path.includes( '/timeline' ) ) {
				return Promise.resolve( { headers: { get: () => '0' } } );
			}
			if ( path.includes( '/emails' ) ) {
				return Promise.resolve( { total: 0 } );
			}
			return Promise.resolve( {} );
		} );
	} );

	afterEach( () => {
		delete window.rpApplicant;
	} );

	test( 'zeigt Ladezustand initial', () => {
		apiFetch.mockImplementation( () => new Promise( () => {} ) ); // Never resolves

		render( <ApplicantDetail applicationId={ 123 } /> );

		expect( screen.getByText( 'Lade Bewerbung...' ) ).toBeInTheDocument();
	} );

	test( 'zeigt Bewerberdaten nach Laden', async () => {
		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		expect( screen.getByText( 'Software Developer' ) ).toBeInTheDocument();
	} );

	test( 'zeigt Fehlermeldung bei API-Fehler', async () => {
		apiFetch.mockRejectedValue( new Error( 'API Fehler' ) );

		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'API Fehler' ) ).toBeInTheDocument();
		} );

		expect( screen.getByText( 'Erneut versuchen' ) ).toBeInTheDocument();
	} );

	test( 'zeigt Bewerbung nicht gefunden wenn keine Daten', async () => {
		apiFetch.mockResolvedValue( null );

		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Bewerbung nicht gefunden.' ) ).toBeInTheDocument();
		} );
	} );
} );

describe( 'ApplicantDetail mit DynamicFieldRenderer', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.rpApplicant = {
			listUrl: '/wp-admin/admin.php?page=recruiting-playbook',
			canSendEmails: true,
			logoUrl: '',
		};

		apiFetch.mockImplementation( ( { path } ) => {
			if ( path.includes( '/applications/' ) && ! path.includes( '/notes' ) && ! path.includes( '/timeline' ) && ! path.includes( '/emails' ) ) {
				return Promise.resolve( mockApplication );
			}
			if ( path.includes( '/notes' ) ) {
				return Promise.resolve( [] );
			}
			return Promise.resolve( { total: 0 } );
		} );
	} );

	afterEach( () => {
		delete window.rpApplicant;
	} );

	test( 'verwendet dynamische Felder wenn activeFields vorhanden', async () => {
		useActiveFields.mockReturnValue( {
			fields: mockActiveFields,
			systemFields: [],
			allFields: mockActiveFields,
			loading: false,
			error: null,
			refresh: jest.fn(),
		} );

		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		// DynamicFieldRenderer sollte die Felder rendern
		expect( screen.getByText( 'Vorname' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Nachname' ) ).toBeInTheDocument();
	} );

	test( 'zeigt Fallback wenn keine activeFields', async () => {
		useActiveFields.mockReturnValue( {
			fields: [],
			systemFields: [],
			allFields: [],
			loading: false,
			error: null,
			refresh: jest.fn(),
		} );

		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		// Fallback zeigt Name, E-Mail, Telefon
		expect( screen.getByText( 'Name' ) ).toBeInTheDocument();
		expect( screen.getByText( 'E-Mail' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Telefon' ) ).toBeInTheDocument();
	} );

	test( 'rendert E-Mail als mailto Link', async () => {
		useActiveFields.mockReturnValue( {
			fields: [],
			systemFields: [],
			allFields: [],
			loading: false,
			error: null,
			refresh: jest.fn(),
		} );

		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		const emailLink = screen.getByRole( 'link', { name: /max@example\.com/ } );
		expect( emailLink ).toHaveAttribute( 'href', 'mailto:max@example.com' );
	} );

	test( 'rendert Telefon als tel Link', async () => {
		useActiveFields.mockReturnValue( {
			fields: [],
			systemFields: [],
			allFields: [],
			loading: false,
			error: null,
			refresh: jest.fn(),
		} );

		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		const phoneLink = screen.getByRole( 'link', { name: /\+49 123 456789/ } );
		expect( phoneLink ).toHaveAttribute( 'href', 'tel:+49 123 456789' );
	} );
} );

describe( 'ApplicantDetail mit Custom Fields', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.rpApplicant = {
			listUrl: '/wp-admin/admin.php?page=recruiting-playbook',
			canSendEmails: true,
			logoUrl: '',
		};

		const appWithCustomFields = {
			...mockApplication,
			custom_fields: [
				{ key: 'linkedin', label: 'LinkedIn', value: 'https://linkedin.com/in/max' },
			],
		};

		apiFetch.mockImplementation( ( { path } ) => {
			if ( path.includes( '/applications/' ) && ! path.includes( '/notes' ) && ! path.includes( '/timeline' ) && ! path.includes( '/emails' ) ) {
				return Promise.resolve( appWithCustomFields );
			}
			if ( path.includes( '/notes' ) ) {
				return Promise.resolve( [] );
			}
			return Promise.resolve( { total: 0 } );
		} );
	} );

	afterEach( () => {
		delete window.rpApplicant;
	} );

	test( 'zeigt CustomFieldsPanel nur wenn keine activeFields und custom_fields vorhanden', async () => {
		useActiveFields.mockReturnValue( {
			fields: [],
			systemFields: [],
			allFields: [],
			loading: false,
			error: null,
			refresh: jest.fn(),
		} );

		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		// CustomFieldsPanel sollte gerendert werden
		expect( screen.getByTestId( 'custom-fields-panel' ) ).toBeInTheDocument();
	} );

	test( 'versteckt CustomFieldsPanel wenn activeFields vorhanden', async () => {
		useActiveFields.mockReturnValue( {
			fields: mockActiveFields,
			systemFields: [],
			allFields: mockActiveFields,
			loading: false,
			error: null,
			refresh: jest.fn(),
		} );

		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		// CustomFieldsPanel sollte nicht gerendert werden
		expect( screen.queryByTestId( 'custom-fields-panel' ) ).not.toBeInTheDocument();
	} );
} );

describe( 'ApplicantDetail Status', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		window.rpApplicant = {
			listUrl: '/wp-admin/admin.php?page=recruiting-playbook',
			canSendEmails: true,
			logoUrl: '',
		};

		apiFetch.mockImplementation( ( { path } ) => {
			if ( path.includes( '/applications/' ) && ! path.includes( '/notes' ) && ! path.includes( '/timeline' ) && ! path.includes( '/emails' ) && ! path.includes( '/status' ) ) {
				return Promise.resolve( mockApplication );
			}
			if ( path.includes( '/notes' ) ) {
				return Promise.resolve( [] );
			}
			return Promise.resolve( { total: 0 } );
		} );

		useActiveFields.mockReturnValue( {
			fields: [],
			systemFields: [],
			allFields: [],
			loading: false,
			error: null,
			refresh: jest.fn(),
		} );
	} );

	afterEach( () => {
		delete window.rpApplicant;
	} );

	test( 'zeigt Status-Dropdown mit aktuellem Status', async () => {
		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		const statusSelect = document.getElementById( 'rp-status-select' );
		expect( statusSelect ).toBeInTheDocument();
		expect( statusSelect.value ).toBe( 'new' );
	} );

	test( 'zeigt alle Status-Optionen', async () => {
		render( <ApplicantDetail applicationId={ 123 } /> );

		await waitFor( () => {
			expect( screen.getByText( 'Max Mustermann' ) ).toBeInTheDocument();
		} );

		const statusSelect = document.getElementById( 'rp-status-select' );
		const options = statusSelect.querySelectorAll( 'option' );

		expect( options.length ).toBe( 7 ); // new, screening, interview, offer, hired, rejected, withdrawn
	} );
} );

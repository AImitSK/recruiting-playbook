/**
 * DynamicFieldRenderer Tests
 *
 * @package RecruitingPlaybook\Tests
 */

import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { DynamicFieldRenderer, FieldDisplay } from '../DynamicFieldRenderer';

// WordPress i18n Mock
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

describe( 'DynamicFieldRenderer', () => {
	const mockFields = [
		{
			field_key: 'first_name',
			field_type: 'text',
			label: 'Vorname',
			is_required: true,
		},
		{
			field_key: 'email',
			field_type: 'email',
			label: 'E-Mail',
			is_required: true,
		},
		{
			field_key: 'phone',
			field_type: 'phone',
			label: 'Telefon',
			is_required: false,
		},
		{
			field_key: 'message',
			field_type: 'textarea',
			label: 'Nachricht',
			is_required: false,
		},
	];

	const mockData = {
		first_name: 'Max',
		email: 'max@example.com',
		phone: '+49 123 456789',
		message: 'Test Nachricht',
	};

	test( 'rendert Text-Felder korrekt', () => {
		render( <DynamicFieldRenderer fields={ mockFields } data={ mockData } /> );

		expect( screen.getByText( 'Vorname' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Max' ) ).toBeInTheDocument();
	} );

	test( 'rendert Email als klickbaren Link', () => {
		render( <DynamicFieldRenderer fields={ mockFields } data={ mockData } /> );

		const emailLink = screen.getByRole( 'link', { name: /max@example\.com/ } );
		expect( emailLink ).toBeInTheDocument();
		expect( emailLink ).toHaveAttribute( 'href', 'mailto:max@example.com' );
	} );

	test( 'rendert Phone als klickbaren Link', () => {
		render( <DynamicFieldRenderer fields={ mockFields } data={ mockData } /> );

		const phoneLink = screen.getByRole( 'link', { name: /\+49 123 456789/ } );
		expect( phoneLink ).toBeInTheDocument();
		expect( phoneLink ).toHaveAttribute( 'href', 'tel:+49 123 456789' );
	} );

	test( 'versteckt leere optionale Felder', () => {
		const dataWithEmpty = {
			first_name: 'Max',
			email: 'max@example.com',
			phone: '', // Leer
			message: '', // Leer
		};

		render( <DynamicFieldRenderer fields={ mockFields } data={ dataWithEmpty } hideEmptyOptional={ true } /> );

		// Pflichtfelder immer sichtbar
		expect( screen.getByText( 'Vorname' ) ).toBeInTheDocument();
		expect( screen.getByText( 'E-Mail' ) ).toBeInTheDocument();

		// Optionale leere Felder versteckt
		expect( screen.queryByText( 'Telefon' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Nachricht' ) ).not.toBeInTheDocument();
	} );

	test( 'zeigt leere optionale Felder wenn hideEmptyOptional false', () => {
		const dataWithEmpty = {
			first_name: 'Max',
			email: 'max@example.com',
			phone: '',
			message: '',
		};

		render( <DynamicFieldRenderer fields={ mockFields } data={ dataWithEmpty } hideEmptyOptional={ false } /> );

		expect( screen.getByText( 'Telefon' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Nachricht' ) ).toBeInTheDocument();
	} );

	test( 'zeigt Meldung wenn keine Felder vorhanden', () => {
		render( <DynamicFieldRenderer fields={ [] } data={ {} } /> );

		expect( screen.queryByText( 'Keine Daten vorhanden' ) ).not.toBeInTheDocument();
	} );

	test( 'rendert null wenn fields leer', () => {
		const { container } = render( <DynamicFieldRenderer fields={ [] } data={ {} } /> );
		expect( container.firstChild ).toBeNull();
	} );

	test( 'rendert two-column Layout', () => {
		const { container } = render(
			<DynamicFieldRenderer fields={ mockFields } data={ mockData } layout="two-column" />
		);

		const grid = container.firstChild;
		expect( grid ).toHaveStyle( { display: 'grid' } );
	} );
} );

describe( 'FieldDisplay', () => {
	test( 'rendert URL als externen Link', () => {
		const field = {
			field_key: 'website',
			field_type: 'url',
			label: 'Website',
			is_required: false,
		};

		render( <FieldDisplay field={ field } value="https://example.com" /> );

		const link = screen.getByRole( 'link', { name: /example\.com/ } );
		expect( link ).toHaveAttribute( 'target', '_blank' );
		expect( link ).toHaveAttribute( 'rel', 'noopener noreferrer' );
	} );

	test( 'rendert Checkbox als Ja/Nein', () => {
		const field = {
			field_key: 'consent',
			field_type: 'checkbox',
			label: 'Zustimmung',
			is_required: true,
		};

		const { rerender } = render( <FieldDisplay field={ field } value={ true } /> );
		expect( screen.getByText( 'Ja' ) ).toBeInTheDocument();

		rerender( <FieldDisplay field={ field } value={ false } /> );
		expect( screen.getByText( 'Nein' ) ).toBeInTheDocument();
	} );

	test( 'rendert Select mit Display-Value aus Options', () => {
		const field = {
			field_key: 'experience',
			field_type: 'select',
			label: 'Erfahrung',
			is_required: false,
			options: [
				{ value: '0-2', label: '0-2 Jahre' },
				{ value: '3-5', label: '3-5 Jahre' },
				{ value: '5+', label: '5+ Jahre' },
			],
		};

		render( <FieldDisplay field={ field } value="3-5" /> );

		expect( screen.getByText( '3-5 Jahre' ) ).toBeInTheDocument();
	} );

	test( 'rendert Textarea mit pre-wrap', () => {
		const field = {
			field_key: 'message',
			field_type: 'textarea',
			label: 'Nachricht',
			is_required: false,
		};

		render( <FieldDisplay field={ field } value="Zeile 1\nZeile 2" /> );

		const text = screen.getByText( /Zeile 1/ );
		expect( text ).toHaveStyle( { whiteSpace: 'pre-wrap' } );
	} );

	test( 'zeigt Bindestrich für leere Pflichtfelder', () => {
		const field = {
			field_key: 'first_name',
			field_type: 'text',
			label: 'Vorname',
			is_required: true,
		};

		render( <FieldDisplay field={ field } value="" /> );

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );

	test( 'rendert null für leere optionale Felder', () => {
		const field = {
			field_key: 'phone',
			field_type: 'phone',
			label: 'Telefon',
			is_required: false,
		};

		const { container } = render( <FieldDisplay field={ field } value="" /> );
		expect( container.firstChild ).toBeNull();
	} );

	test( 'zeigt Label nicht wenn showLabel false', () => {
		const field = {
			field_key: 'first_name',
			field_type: 'text',
			label: 'Vorname',
			is_required: true,
		};

		render( <FieldDisplay field={ field } value="Max" showLabel={ false } /> );

		expect( screen.queryByText( 'Vorname' ) ).not.toBeInTheDocument();
		expect( screen.getByText( 'Max' ) ).toBeInTheDocument();
	} );
} );

describe( 'File Field', () => {
	test( 'rendert einzelne Datei', () => {
		const field = {
			field_key: 'resume',
			field_type: 'file',
			label: 'Lebenslauf',
			is_required: false,
		};

		const value = {
			filename: 'lebenslauf.pdf',
			view_url: 'https://example.com/view/1',
			download_url: 'https://example.com/download/1',
		};

		render( <FieldDisplay field={ field } value={ value } /> );

		expect( screen.getByText( 'lebenslauf.pdf' ) ).toBeInTheDocument();
	} );

	test( 'rendert mehrere Dateien', () => {
		const field = {
			field_key: 'documents',
			field_type: 'file',
			label: 'Dokumente',
			is_required: false,
		};

		const value = [
			{ filename: 'lebenslauf.pdf' },
			{ filename: 'zeugnis.pdf' },
		];

		render( <FieldDisplay field={ field } value={ value } /> );

		expect( screen.getByText( 'lebenslauf.pdf' ) ).toBeInTheDocument();
		expect( screen.getByText( 'zeugnis.pdf' ) ).toBeInTheDocument();
	} );

	test( 'zeigt Bindestrich für leeres Datei-Array', () => {
		const field = {
			field_key: 'documents',
			field_type: 'file',
			label: 'Dokumente',
			is_required: true,
		};

		render( <FieldDisplay field={ field } value={ [] } /> );

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );
} );

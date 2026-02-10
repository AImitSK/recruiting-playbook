/**
 * Tests für System Field Settings Komponenten
 *
 * @package RecruitingPlaybook
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import FileUploadSettings from '../components/SystemFieldSettings/FileUploadSettings';
import SummarySettings from '../components/SystemFieldSettings/SummarySettings';
import PrivacyConsentSettings from '../components/SystemFieldSettings/PrivacyConsentSettings';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

// Mock lucide-react
jest.mock( 'lucide-react', () => ( {
	X: () => <span data-testid="x-icon">X</span>,
	Upload: () => <span data-testid="upload-icon">Upload</span>,
	ListChecks: () => <span data-testid="list-checks-icon">ListChecks</span>,
	Shield: () => <span data-testid="shield-icon">Shield</span>,
	ExternalLink: () => <span data-testid="external-link-icon">ExternalLink</span>,
} ) );

describe( 'FileUploadSettings', () => {
	const defaultProps = {
		settings: {},
		onSave: jest.fn(),
		onClose: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders with default values', () => {
		render( <FileUploadSettings { ...defaultProps } /> );

		expect( screen.getByText( 'Datei-Upload Einstellungen' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Bezeichnung' ) ).toHaveValue( 'Dokumente hochladen' );
	} );

	it( 'renders with custom settings', () => {
		const customSettings = {
			label: 'Benutzerdefiniert',
			allowed_types: [ 'pdf' ],
			max_file_size: 20,
			max_files: 10,
			help_text: 'Hilfetext',
		};

		render( <FileUploadSettings { ...defaultProps } settings={ customSettings } /> );

		expect( screen.getByLabelText( 'Bezeichnung' ) ).toHaveValue( 'Benutzerdefiniert' );
		expect( screen.getByLabelText( 'Maximale Dateigröße (MB)' ) ).toHaveValue( 20 );
		expect( screen.getByLabelText( 'Maximale Anzahl Dateien' ) ).toHaveValue( 10 );
		expect( screen.getByLabelText( 'Hilfetext' ) ).toHaveValue( 'Hilfetext' );
	} );

	it( 'renders file type checkboxes', () => {
		render( <FileUploadSettings { ...defaultProps } /> );

		expect( screen.getByText( 'PDF' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Word (.doc)' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Word (.docx)' ) ).toBeInTheDocument();
	} );

	it( 'calls onSave with updated settings', () => {
		const onSave = jest.fn();
		render( <FileUploadSettings { ...defaultProps } onSave={ onSave } /> );

		// Change label
		const labelInput = screen.getByLabelText( 'Bezeichnung' );
		fireEvent.change( labelInput, { target: { value: 'Neuer Titel' } } );

		// Click save
		const saveButton = screen.getByText( 'Speichern' );
		fireEvent.click( saveButton );

		expect( onSave ).toHaveBeenCalledWith(
			expect.objectContaining( {
				label: 'Neuer Titel',
			} )
		);
	} );

	it( 'calls onClose when clicking cancel', () => {
		const onClose = jest.fn();
		render( <FileUploadSettings { ...defaultProps } onClose={ onClose } /> );

		const cancelButton = screen.getByText( 'Abbrechen' );
		fireEvent.click( cancelButton );

		expect( onClose ).toHaveBeenCalled();
	} );

	it( 'calls onClose when clicking backdrop', () => {
		const onClose = jest.fn();
		const { container } = render( <FileUploadSettings { ...defaultProps } onClose={ onClose } /> );

		// Click the backdrop (first div with fixed position)
		const backdrop = container.firstChild;
		fireEvent.click( backdrop );

		expect( onClose ).toHaveBeenCalled();
	} );

	it( 'does not call onClose when clicking modal content', () => {
		const onClose = jest.fn();
		render( <FileUploadSettings { ...defaultProps } onClose={ onClose } /> );

		// Click inside the modal
		const modalContent = screen.getByText( 'Datei-Upload Einstellungen' );
		fireEvent.click( modalContent );

		expect( onClose ).not.toHaveBeenCalled();
	} );
} );

describe( 'SummarySettings', () => {
	const defaultProps = {
		settings: {},
		onSave: jest.fn(),
		onClose: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders with default values', () => {
		render( <SummarySettings { ...defaultProps } /> );

		expect( screen.getByText( 'Zusammenfassung Einstellungen' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Bezeichnung' ) ).toHaveValue( 'Zusammenfassung' );
	} );

	it( 'renders toggle switches', () => {
		render( <SummarySettings { ...defaultProps } /> );

		expect( screen.getByText( 'Überschrift anzeigen' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Schritt-Titel anzeigen' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Bearbeiten-Buttons anzeigen' ) ).toBeInTheDocument();
	} );

	it( 'calls onSave with correct settings', () => {
		const onSave = jest.fn();
		render( <SummarySettings { ...defaultProps } onSave={ onSave } /> );

		// Change label
		const labelInput = screen.getByLabelText( 'Bezeichnung' );
		fireEvent.change( labelInput, { target: { value: 'Übersicht' } } );

		// Click save
		const saveButton = screen.getByText( 'Speichern' );
		fireEvent.click( saveButton );

		expect( onSave ).toHaveBeenCalledWith(
			expect.objectContaining( {
				label: 'Übersicht',
				show_header: true,
				show_step_titles: true,
				show_edit_buttons: true,
			} )
		);
	} );

	it( 'respects initial settings for toggles', () => {
		const customSettings = {
			show_header: false,
			show_step_titles: false,
			show_edit_buttons: false,
		};

		const onSave = jest.fn();
		render( <SummarySettings { ...defaultProps } settings={ customSettings } onSave={ onSave } /> );

		const saveButton = screen.getByText( 'Speichern' );
		fireEvent.click( saveButton );

		expect( onSave ).toHaveBeenCalledWith(
			expect.objectContaining( {
				show_header: false,
				show_step_titles: false,
				show_edit_buttons: false,
			} )
		);
	} );
} );

describe( 'PrivacyConsentSettings', () => {
	const defaultProps = {
		settings: {},
		onSave: jest.fn(),
		onClose: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders with default values', () => {
		render( <PrivacyConsentSettings { ...defaultProps } /> );

		expect( screen.getByText( 'Datenschutz-Zustimmung Einstellungen' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Bezeichnung (intern)' ) ).toHaveValue( 'Datenschutz-Zustimmung' );
	} );

	it( 'renders consent text field with placeholder info', () => {
		render( <PrivacyConsentSettings { ...defaultProps } /> );

		expect( screen.getByLabelText( 'Zustimmungstext' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Verwenden Sie {privacy_link} als Platzhalter für den Link.' ) ).toBeInTheDocument();
	} );

	it( 'renders link configuration fields', () => {
		render( <PrivacyConsentSettings { ...defaultProps } /> );

		expect( screen.getByLabelText( 'Link-Text' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Link-URL' ) ).toBeInTheDocument();
	} );

	it( 'renders preview section', () => {
		render( <PrivacyConsentSettings { ...defaultProps } /> );

		expect( screen.getByText( 'Vorschau:' ) ).toBeInTheDocument();
	} );

	it( 'renders error message field', () => {
		render( <PrivacyConsentSettings { ...defaultProps } /> );

		expect( screen.getByLabelText( 'Fehlermeldung' ) ).toBeInTheDocument();
	} );

	it( 'renders info box about required field', () => {
		render( <PrivacyConsentSettings { ...defaultProps } /> );

		expect( screen.getByText( /Pflichtfeld und kann nicht entfernt werden/ ) ).toBeInTheDocument();
	} );

	it( 'calls onSave with all settings', () => {
		const onSave = jest.fn();
		render( <PrivacyConsentSettings { ...defaultProps } onSave={ onSave } /> );

		// Fill in custom values
		const consentTextArea = screen.getByLabelText( 'Zustimmungstext' );
		fireEvent.change( consentTextArea, { target: { value: 'Ich stimme zu' } } );

		const linkTextInput = screen.getByLabelText( 'Link-Text' );
		fireEvent.change( linkTextInput, { target: { value: 'Datenschutz' } } );

		// Click save
		const saveButton = screen.getByText( 'Speichern' );
		fireEvent.click( saveButton );

		expect( onSave ).toHaveBeenCalledWith(
			expect.objectContaining( {
				consent_text: 'Ich stimme zu',
				privacy_link_text: 'Datenschutz',
			} )
		);
	} );

	it( 'loads custom settings', () => {
		const customSettings = {
			label: 'DSGVO',
			consent_text: 'Benutzerdefinierter Text',
			privacy_link_text: 'Datenschutzrichtlinie',
			privacy_url: 'https://example.com/privacy',
			error_message: 'Bitte akzeptieren',
			help_text: 'Weitere Infos',
		};

		render( <PrivacyConsentSettings { ...defaultProps } settings={ customSettings } /> );

		expect( screen.getByLabelText( 'Bezeichnung (intern)' ) ).toHaveValue( 'DSGVO' );
		expect( screen.getByLabelText( 'Zustimmungstext' ) ).toHaveValue( 'Benutzerdefinierter Text' );
		expect( screen.getByLabelText( 'Link-Text' ) ).toHaveValue( 'Datenschutzrichtlinie' );
		expect( screen.getByLabelText( 'Fehlermeldung' ) ).toHaveValue( 'Bitte akzeptieren' );
		expect( screen.getByLabelText( 'Hilfetext (optional)' ) ).toHaveValue( 'Weitere Infos' );
	} );
} );

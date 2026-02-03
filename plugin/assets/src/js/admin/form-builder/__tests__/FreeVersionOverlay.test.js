/**
 * Tests für FreeVersionOverlay Komponente
 *
 * @package RecruitingPlaybook
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import FreeVersionOverlay from '../components/FreeVersionOverlay';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

// Mock lucide-react
jest.mock( 'lucide-react', () => ( {
	Lock: () => <span data-testid="lock-icon">Lock</span>,
	Sparkles: () => <span data-testid="sparkles-icon">Sparkles</span>,
	Check: () => <span data-testid="check-icon">Check</span>,
} ) );

describe( 'FreeVersionOverlay', () => {
	const defaultProps = {
		upgradeUrl: 'https://example.com/upgrade',
		i18n: {},
	};

	beforeEach( () => {
		// Mock window.location
		delete window.location;
		window.location = { href: '' };
	} );

	it( 'renders overlay with title', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( screen.getByText( 'Pro-Feature' ) ).toBeInTheDocument();
	} );

	it( 'renders lock icon', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( screen.getByTestId( 'lock-icon' ) ).toBeInTheDocument();
	} );

	it( 'renders description text', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( screen.getByText( /Formular-Builder ist ein Pro-Feature/ ) ).toBeInTheDocument();
	} );

	it( 'renders feature list', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( screen.getByText( 'Formular-Schritte anpassen' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Felder hinzufügen und entfernen' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Eigene Felder erstellen' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Drag & Drop Sortierung' ) ).toBeInTheDocument();
		expect( screen.getByText( 'System-Feld Einstellungen' ) ).toBeInTheDocument();
	} );

	it( 'renders check icons for each feature', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		const checkIcons = screen.getAllByTestId( 'check-icon' );
		expect( checkIcons ).toHaveLength( 5 );
	} );

	it( 'renders upgrade button with sparkles icon', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( screen.getByText( 'Auf Pro upgraden' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'sparkles-icon' ) ).toBeInTheDocument();
	} );

	it( 'navigates to upgrade URL when button is clicked', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		const upgradeButton = screen.getByText( 'Auf Pro upgraden' );
		fireEvent.click( upgradeButton );

		expect( window.location.href ).toBe( 'https://example.com/upgrade' );
	} );

	it( 'renders subtext about standard form', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( screen.getByText( 'In der Free Version wird ein Standard-Formular verwendet.' ) ).toBeInTheDocument();
	} );

	it( 'uses custom i18n strings when provided', () => {
		const customI18n = {
			proFeatureTitle: 'Premium Feature',
			proFeatureDescription: 'Upgrade für mehr Funktionen',
			upgradeToPro: 'Jetzt upgraden',
			standardFormInfo: 'Basic Form wird verwendet',
		};

		render( <FreeVersionOverlay { ...defaultProps } i18n={ customI18n } /> );

		expect( screen.getByText( 'Premium Feature' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Upgrade für mehr Funktionen' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Jetzt upgraden' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Basic Form wird verwendet' ) ).toBeInTheDocument();
	} );

	it( 'has semi-transparent backdrop with blur', () => {
		const { container } = render( <FreeVersionOverlay { ...defaultProps } /> );

		const overlay = container.firstChild;
		expect( overlay ).toHaveStyle( 'background-color: rgba(255, 255, 255, 0.85)' );
		expect( overlay ).toHaveStyle( 'backdrop-filter: blur(2px)' );
	} );

	it( 'positions overlay absolutely', () => {
		const { container } = render( <FreeVersionOverlay { ...defaultProps } /> );

		const overlay = container.firstChild;
		expect( overlay ).toHaveStyle( 'position: absolute' );
		expect( overlay ).toHaveStyle( 'inset: 0' );
	} );

	it( 'has high z-index', () => {
		const { container } = render( <FreeVersionOverlay { ...defaultProps } /> );

		const overlay = container.firstChild;
		expect( overlay ).toHaveStyle( 'z-index: 50' );
	} );
} );

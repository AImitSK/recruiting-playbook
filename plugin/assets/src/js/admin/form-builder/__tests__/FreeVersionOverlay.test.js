/**
 * Tests für FreeVersionOverlay Komponente
 *
 * @package RecruitingPlaybook
 */

import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import FreeVersionOverlay from '../components/FreeVersionOverlay';

// Mock @wordpress/i18n
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

describe( 'FreeVersionOverlay', () => {
	const defaultProps = {
		upgradeUrl: 'https://example.com/upgrade',
	};

	it( 'renders overlay with title', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( screen.getByText( 'Form Builder is a Pro feature' ) ).toBeInTheDocument();
	} );

	it( 'renders lock icon', () => {
		const { container } = render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( container.querySelector( '.dashicons-lock' ) ).toBeInTheDocument();
	} );

	it( 'renders description text', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		expect( screen.getByText( /Upgrade to Pro to unlock this feature/ ) ).toBeInTheDocument();
	} );

	it( 'renders upgrade button with link', () => {
		render( <FreeVersionOverlay { ...defaultProps } /> );

		const link = screen.getByText( 'Upgrade to Pro' );
		expect( link ).toBeInTheDocument();
		expect( link.closest( 'a' ) ).toHaveAttribute( 'href', 'https://example.com/upgrade' );
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

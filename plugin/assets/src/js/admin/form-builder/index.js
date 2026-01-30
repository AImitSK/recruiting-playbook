/**
 * Form Builder Entry Point
 *
 * React application for managing form fields and templates.
 *
 * @package RecruitingPlaybook
 */

import { createRoot } from '@wordpress/element';
import FormBuilder from './FormBuilder';

// Wait for DOM ready.
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'rp-form-builder-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <FormBuilder /> );
	}
} );

/**
 * ErrorBoundary - Fängt React-Fehler ab
 *
 * @package RecruitingPlaybook
 */

import { Component } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';

/**
 * ErrorBoundary Komponente
 *
 * Fängt JavaScript-Fehler in untergeordneten Komponenten ab
 * und zeigt eine Fallback-UI statt eines weißen Bildschirms.
 */
export class ErrorBoundary extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			hasError: false,
			error: null,
			errorInfo: null,
		};
	}

	/**
	 * Fehler abfangen und State aktualisieren
	 *
	 * @param {Error} error Fehler
	 * @return {Object} Neuer State
	 */
	static getDerivedStateFromError( error ) {
		return { hasError: true, error };
	}

	/**
	 * Fehler-Details loggen
	 *
	 * @param {Error}  error     Fehler
	 * @param {Object} errorInfo Fehler-Info mit componentStack
	 */
	componentDidCatch( error, errorInfo ) {
		console.error( 'React Error Boundary caught an error:', error, errorInfo );

		this.setState( {
			error,
			errorInfo,
		} );
	}

	/**
	 * Seite neu laden
	 */
	handleReload = () => {
		window.location.reload();
	};

	/**
	 * Fehler-State zurücksetzen
	 */
	handleRetry = () => {
		this.setState( {
			hasError: false,
			error: null,
			errorInfo: null,
		} );
	};

	render() {
		const { hasError, error } = this.state;
		const { children, fallback } = this.props;

		const i18n = window.rpEmailData?.i18n || {};

		if ( hasError ) {
			// Custom Fallback wenn übergeben
			if ( fallback ) {
				return fallback;
			}

			// Standard Fallback UI
			return (
				<div className="rp-error-boundary">
					<Notice status="error" isDismissible={ false }>
						<p>
							<strong>
								{ i18n.errorOccurred || 'Ein Fehler ist aufgetreten' }
							</strong>
						</p>
						<p>
							{ i18n.errorDescription ||
								'Die Anwendung konnte nicht geladen werden. Bitte versuchen Sie es erneut.' }
						</p>
						{ error?.message && (
							<details className="rp-error-boundary__details">
								<summary>{ i18n.errorDetails || 'Fehler-Details' }</summary>
								<pre>{ error.message }</pre>
							</details>
						) }
						<div className="rp-error-boundary__actions">
							<Button variant="secondary" onClick={ this.handleRetry }>
								{ i18n.retry || 'Erneut versuchen' }
							</Button>
							<Button variant="primary" onClick={ this.handleReload }>
								{ i18n.reloadPage || 'Seite neu laden' }
							</Button>
						</div>
					</Notice>
				</div>
			);
		}

		return children;
	}
}

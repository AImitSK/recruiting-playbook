/**
 * Custom Hook für Bewerbungen
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook zum Laden und Aktualisieren von Bewerbungen
 *
 * @return {Object} Applications state und Funktionen
 */
export function useApplications() {
	const [ applications, setApplications ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );

	/**
	 * Bewerbungen vom Server laden
	 */
	const fetchApplications = useCallback( async () => {
		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: '/recruiting/v1/applications?per_page=200&context=kanban',
			} );

			// API gibt items Array oder direkt Array zurück
			const items = data.items || data;
			setApplications( Array.isArray( items ) ? items : [] );
		} catch ( err ) {
			console.error( 'Error fetching applications:', err );
			setError(
				err.message ||
				window.rpKanban?.i18n?.error ||
				'Fehler beim Laden'
			);
		} finally {
			setLoading( false );
		}
	}, [] );

	// Initial laden
	useEffect( () => {
		fetchApplications();
	}, [ fetchApplications ] );

	// Polling für Aktualisierungen (alle 60 Sekunden)
	useEffect( () => {
		const interval = setInterval( () => {
			// Nur aktualisieren wenn Tab aktiv
			if ( document.visibilityState === 'visible' ) {
				fetchApplications();
			}
		}, 60000 );

		return () => clearInterval( interval );
	}, [ fetchApplications ] );

	/**
	 * Status einer Bewerbung aktualisieren
	 *
	 * @param {number} id        Bewerbungs-ID
	 * @param {string} newStatus Neuer Status
	 * @param {number} position  Neue Position (optional)
	 */
	const updateStatus = useCallback(
		async ( id, newStatus, position = null ) => {
			// Optimistic Update
			setApplications( ( prev ) =>
				prev.map( ( app ) =>
					app.id === id
						? {
							...app,
							status: newStatus,
							kanban_position: position ?? app.kanban_position,
						}
						: app
				)
			);

			try {
				await apiFetch( {
					path: `/recruiting/v1/applications/${ id }/status`,
					method: 'PATCH',
					data: {
						status: newStatus,
						note: `Status via Kanban-Board geändert`,
					},
				} );

				// Optional: Erfolgsmeldung anzeigen
				const i18n = window.rpKanban?.i18n || {};
				showNotice(
					i18n.statusChanged || 'Status geändert',
					'success'
				);
			} catch ( err ) {
				console.error( 'Error updating status:', err );

				// Rollback bei Fehler
				fetchApplications();

				// Fehlermeldung anzeigen
				const i18n = window.rpKanban?.i18n || {};
				showNotice(
					i18n.updateFailed || 'Aktualisierung fehlgeschlagen',
					'error'
				);
			}
		},
		[ fetchApplications ]
	);

	return {
		applications,
		loading,
		error,
		updateStatus,
		refetch: fetchApplications,
	};
}

/**
 * WordPress Admin Notice anzeigen
 *
 * @param {string} message Nachricht
 * @param {string} type    Notice-Typ (success, error, warning, info)
 */
function showNotice( message, type = 'info' ) {
	// Bestehende Notices entfernen
	const existingNotices = document.querySelectorAll( '.rp-kanban-notice' );
	existingNotices.forEach( ( notice ) => notice.remove() );

	// Neue Notice erstellen
	const notice = document.createElement( 'div' );
	notice.className = `notice notice-${ type } is-dismissible rp-kanban-notice`;
	notice.style.cssText = 'position: fixed; top: 50px; right: 20px; z-index: 99999; max-width: 300px;';
	notice.innerHTML = `
		<p>${ escapeHtml( message ) }</p>
		<button type="button" class="notice-dismiss">
			<span class="screen-reader-text">Dismiss this notice.</span>
		</button>
	`;

	document.body.appendChild( notice );

	// Dismiss-Button
	notice.querySelector( '.notice-dismiss' ).addEventListener( 'click', () => {
		notice.remove();
	} );

	// Auto-dismiss nach 3 Sekunden
	setTimeout( () => {
		if ( notice.parentNode ) {
			notice.remove();
		}
	}, 3000 );
}

/**
 * HTML escapen
 *
 * @param {string} text Text zum Escapen
 * @return {string} Escapeter Text
 */
function escapeHtml( text ) {
	const div = document.createElement( 'div' );
	div.textContent = text;
	return div.innerHTML;
}

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
	 * Status einer Bewerbung aktualisieren (Spalten-Wechsel)
	 *
	 * @param {number} id          Bewerbungs-ID
	 * @param {string} newStatus   Neuer Status
	 * @param {number} newPosition Neue Position in der Zielspalte
	 */
	const updateStatus = useCallback(
		async ( id, newStatus, newPosition = 0 ) => {
			// Optimistic Update
			setApplications( ( prev ) =>
				prev.map( ( app ) =>
					app.id === id
						? {
							...app,
							status: newStatus,
							kanban_position: newPosition,
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
						kanban_position: newPosition,
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

	/**
	 * Bewerbung innerhalb einer Spalte verschieben
	 *
	 * @param {string} status      Status/Spalte
	 * @param {number} activeId    ID der gezogenen Karte
	 * @param {number} overId      ID der Zielkarte
	 * @param {Array}  columnItems Aktuelle Reihenfolge der Spalte
	 */
	const reorderInColumn = useCallback(
		async ( status, activeId, overId, columnItems ) => {
			// Alte und neue Position finden
			const oldIndex = columnItems.findIndex( ( item ) => item.id === activeId );
			const newIndex = columnItems.findIndex( ( item ) => item.id === overId );

			if ( oldIndex === -1 || newIndex === -1 || oldIndex === newIndex ) {
				return;
			}

			// Neue Reihenfolge berechnen (arrayMove-Logik)
			const newOrder = [ ...columnItems ];
			const [ movedItem ] = newOrder.splice( oldIndex, 1 );
			newOrder.splice( newIndex, 0, movedItem );

			// Positionen neu berechnen (10er-Schritte für späteres Einfügen)
			const updates = newOrder.map( ( item, index ) => ( {
				id: item.id,
				kanban_position: ( index + 1 ) * 10,
			} ) );

			// Optimistic Update
			setApplications( ( prev ) =>
				prev.map( ( app ) => {
					const update = updates.find( ( u ) => u.id === app.id );
					if ( update ) {
						return { ...app, kanban_position: update.kanban_position };
					}
					return app;
				} )
			);

			try {
				// Batch-Update an Server senden
				await apiFetch( {
					path: '/recruiting/v1/applications/reorder',
					method: 'POST',
					data: {
						status,
						positions: updates,
					},
				} );
			} catch ( err ) {
				console.error( 'Error reordering:', err );

				// Rollback bei Fehler
				fetchApplications();

				const i18n = window.rpKanban?.i18n || {};
				showNotice(
					i18n.reorderFailed || 'Sortierung fehlgeschlagen',
					'error'
				);
			}
		},
		[ fetchApplications ]
	);

	/**
	 * Bewerbung in andere Spalte verschieben
	 *
	 * @param {number} id             Bewerbungs-ID
	 * @param {string} newStatus      Neuer Status
	 * @param {number} targetPosition Position in der Zielspalte
	 * @param {Array}  targetItems    Items der Zielspalte (ohne die bewegte Karte)
	 */
	const moveToColumn = useCallback(
		async ( id, newStatus, targetPosition, targetItems ) => {
			// Positionen in Zielspalte neu berechnen
			const newItems = [ ...targetItems ];
			const app = applications.find( ( a ) => a.id === id );

			if ( ! app ) {
				return;
			}

			// An der richtigen Position einfügen
			newItems.splice( targetPosition, 0, { ...app, status: newStatus } );

			// Neue Positionen berechnen
			const updates = newItems.map( ( item, index ) => ( {
				id: item.id,
				kanban_position: ( index + 1 ) * 10,
			} ) );

			const movedItemPosition = updates.find( ( u ) => u.id === id )?.kanban_position || 10;

			// Optimistic Update für alle betroffenen Items
			setApplications( ( prev ) =>
				prev.map( ( a ) => {
					if ( a.id === id ) {
						return {
							...a,
							status: newStatus,
							kanban_position: movedItemPosition,
						};
					}
					const update = updates.find( ( u ) => u.id === a.id );
					if ( update && a.status === newStatus ) {
						return { ...a, kanban_position: update.kanban_position };
					}
					return a;
				} )
			);

			try {
				// Status-Update für die bewegte Karte
				await apiFetch( {
					path: `/recruiting/v1/applications/${ id }/status`,
					method: 'PATCH',
					data: {
						status: newStatus,
						kanban_position: movedItemPosition,
						note: `Status via Kanban-Board geändert`,
					},
				} );

				// Batch-Update für Positionen in Zielspalte
				if ( targetItems.length > 0 ) {
					await apiFetch( {
						path: '/recruiting/v1/applications/reorder',
						method: 'POST',
						data: {
							status: newStatus,
							positions: updates.filter( ( u ) => u.id !== id ),
						},
					} );
				}

				const i18n = window.rpKanban?.i18n || {};
				showNotice(
					i18n.statusChanged || 'Status geändert',
					'success'
				);
			} catch ( err ) {
				console.error( 'Error moving application:', err );

				// Rollback bei Fehler
				fetchApplications();

				const i18n = window.rpKanban?.i18n || {};
				showNotice(
					i18n.updateFailed || 'Verschieben fehlgeschlagen',
					'error'
				);
			}
		},
		[ applications, fetchApplications ]
	);

	return {
		applications,
		setApplications,
		loading,
		error,
		updateStatus,
		reorderInColumn,
		moveToColumn,
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

	// Type validieren (XSS-Schutz).
	const allowedTypes = [ 'success', 'error', 'warning', 'info' ];
	const safeType = allowedTypes.includes( type ) ? type : 'info';

	// Neue Notice erstellen
	const notice = document.createElement( 'div' );
	notice.className = `notice notice-${ safeType } is-dismissible rp-kanban-notice`;
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

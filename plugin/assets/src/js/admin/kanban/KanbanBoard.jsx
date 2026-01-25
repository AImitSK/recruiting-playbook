/**
 * Kanban-Board Hauptkomponente
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback, useMemo, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	DndContext,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
	DragOverlay,
	pointerWithin,
	rectIntersection,
	closestCorners,
} from '@dnd-kit/core';
import { sortableKeyboardCoordinates } from '@dnd-kit/sortable';
import { KanbanColumn } from './KanbanColumn';
import { KanbanCard } from './KanbanCard';
import { useApplications } from './hooks/useApplications';

/**
 * Benutzerdefinierte Kollisionserkennung
 * Prüft zuerst Pointer-Position, dann Rechteck-Überschneidung
 */
function customCollisionDetection( args ) {
	// Erst prüfen ob Pointer innerhalb eines Elements ist
	const pointerCollisions = pointerWithin( args );
	if ( pointerCollisions.length > 0 ) {
		return pointerCollisions;
	}

	// Fallback auf Rechteck-Überschneidung
	return rectIntersection( args );
}

export function KanbanBoard() {
	const {
		applications,
		loading,
		error,
		updateStatus,
		reorderInColumn,
		moveToColumn,
		refetch,
	} = useApplications();

	const [ jobFilter, setJobFilter ] = useState( '' );
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ activeId, setActiveId ] = useState( null );

	// Ref für Live-Region (Screen Reader Ankündigungen)
	const liveRegionRef = useRef( null );

	// Sensoren für Drag-and-Drop mit Keyboard-Support
	const sensors = useSensors(
		useSensor( PointerSensor, {
			activationConstraint: {
				distance: 8,
			},
		} ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	/**
	 * Screen Reader Ankündigung
	 *
	 * @param {string} message Nachricht für Screen Reader
	 */
	const announce = useCallback( ( message ) => {
		if ( liveRegionRef.current ) {
			liveRegionRef.current.textContent = message;
			// Nach kurzer Zeit leeren für nächste Ankündigung
			setTimeout( () => {
				if ( liveRegionRef.current ) {
					liveRegionRef.current.textContent = '';
				}
			}, 1000 );
		}
	}, [] );

	// Filter aus Toolbar synchronisieren
	useEffect( () => {
		const jobSelect = document.getElementById( 'rp-kanban-job-filter' );
		const searchInput = document.getElementById( 'rp-kanban-search' );
		const refreshBtn = document.getElementById( 'rp-kanban-refresh' );

		const handleJobChange = ( e ) => setJobFilter( e.target.value );
		const handleSearch = ( e ) => setSearchTerm( e.target.value );
		const handleRefresh = () => refetch();

		if ( jobSelect ) {
			jobSelect.addEventListener( 'change', handleJobChange );
		}
		if ( searchInput ) {
			searchInput.addEventListener( 'input', handleSearch );
		}
		if ( refreshBtn ) {
			refreshBtn.addEventListener( 'click', handleRefresh );
		}

		return () => {
			if ( jobSelect ) {
				jobSelect.removeEventListener( 'change', handleJobChange );
			}
			if ( searchInput ) {
				searchInput.removeEventListener( 'input', handleSearch );
			}
			if ( refreshBtn ) {
				refreshBtn.removeEventListener( 'click', handleRefresh );
			}
		};
	}, [ refetch ] );

	// Gefilterte Bewerbungen
	const filteredApplications = applications.filter( ( app ) => {
		if ( jobFilter && app.job_id !== parseInt( jobFilter, 10 ) ) {
			return false;
		}
		if ( searchTerm ) {
			const search = searchTerm.toLowerCase();
			const name = `${ app.first_name } ${ app.last_name }`.toLowerCase();
			const email = ( app.email || '' ).toLowerCase();
			if ( ! name.includes( search ) && ! email.includes( search ) ) {
				return false;
			}
		}
		return true;
	} );

	// Nach Status gruppieren
	const statuses = window.rpKanban?.statuses || [];
	const columns = useMemo( () =>
		statuses.map( ( status ) => ( {
			...status,
			applications: filteredApplications
				.filter( ( app ) => app.status === status.id )
				.sort( ( a, b ) => ( a.kanban_position || 0 ) - ( b.kanban_position || 0 ) ),
		} ) ),
		[ statuses, filteredApplications ]
	);

	// Helper: Finde Spalte für eine Karten-ID
	const findColumnByCardId = useCallback(
		( cardId ) => {
			for ( const column of columns ) {
				if ( column.applications.some( ( app ) => app.id === cardId ) ) {
					return column;
				}
			}
			return null;
		},
		[ columns ]
	);

	// Aktive Karte für DragOverlay
	const activeApplication = activeId
		? applications.find( ( app ) => app.id === activeId )
		: null;

	// Drag-Start Handler
	const handleDragStart = useCallback( ( event ) => {
		const { active } = event;
		setActiveId( active.id );

		// Screen Reader Ankündigung
		const app = applications.find( ( a ) => a.id === active.id );
		if ( app ) {
			const name = `${ app.first_name } ${ app.last_name }`.trim();
			const column = columns.find( ( c ) => c.id === app.status );
			const columnLabel = column?.label || app.status;
			announce(
				__( `${ name } aufgenommen aus ${ columnLabel }. Nutze Pfeiltasten zum Verschieben.`, 'recruiting-playbook' )
			);
		}
	}, [ applications, columns, announce ] );

	// Drag-End Handler
	const handleDragEnd = useCallback(
		async ( event ) => {
			const { active, over } = event;
			setActiveId( null );

			if ( ! over ) {
				return;
			}

			const activeId = active.id;
			const overId = over.id;

			// Aktive Karte und deren Spalte finden
			const activeApp = applications.find( ( a ) => a.id === activeId );
			if ( ! activeApp ) {
				return;
			}

			const activeColumn = findColumnByCardId( activeId );
			if ( ! activeColumn ) {
				return;
			}

			// Ziel-Status ermitteln
			let targetStatus = null;
			let targetColumn = null;
			let isOverCard = false;

			if ( over.data?.current?.type === 'column' ) {
				// Über einer Spalte
				targetStatus = over.data.current.status;
				targetColumn = columns.find( ( c ) => c.id === targetStatus );
			} else if ( over.data?.current?.type === 'card' ) {
				// Über einer Karte
				targetStatus = over.data.current.status;
				targetColumn = columns.find( ( c ) => c.id === targetStatus );
				isOverCard = true;
			} else {
				// Fallback: overId könnte der Status sein
				const statusIds = statuses.map( ( s ) => s.id );
				if ( statusIds.includes( overId ) ) {
					targetStatus = overId;
					targetColumn = columns.find( ( c ) => c.id === targetStatus );
				}
			}

			if ( ! targetStatus || ! targetColumn ) {
				return;
			}

			const sourceStatus = activeApp.status;

			// Fall 1: Gleiche Spalte - Sortierung ändern
			if ( sourceStatus === targetStatus && isOverCard && activeId !== overId ) {
				await reorderInColumn(
					sourceStatus,
					activeId,
					overId,
					activeColumn.applications
				);
				announceDropResult( activeApp, targetColumn, true );
				return;
			}

			// Fall 2: Andere Spalte - Karte verschieben
			if ( sourceStatus !== targetStatus ) {
				// Position in Zielspalte berechnen
				let targetPosition = 0;

				if ( isOverCard ) {
					// Position der Zielkarte finden
					const overIndex = targetColumn.applications.findIndex(
						( app ) => app.id === overId
					);
					targetPosition = overIndex >= 0 ? overIndex : targetColumn.applications.length;
				} else {
					// Am Ende der Spalte einfügen
					targetPosition = targetColumn.applications.length;
				}

				await moveToColumn(
					activeId,
					targetStatus,
					targetPosition,
					targetColumn.applications
				);
				announceDropResult( activeApp, targetColumn, false );
			}
		},
		[ applications, columns, statuses, findColumnByCardId, reorderInColumn, moveToColumn, announceDropResult ]
	);

	// Drag-Cancel Handler
	const handleDragCancel = useCallback( () => {
		setActiveId( null );
		announce( __( 'Verschieben abgebrochen.', 'recruiting-playbook' ) );
	}, [ announce ] );

	// Ankündigung nach erfolgreichem Drop
	const announceDropResult = useCallback(
		( app, targetColumn, isSameColumn ) => {
			const name = `${ app.first_name } ${ app.last_name }`.trim();
			const columnLabel = targetColumn?.label || '';

			if ( isSameColumn ) {
				announce(
					__( `${ name } neu sortiert in ${ columnLabel }.`, 'recruiting-playbook' )
				);
			} else {
				announce(
					__( `${ name } verschoben nach ${ columnLabel }.`, 'recruiting-playbook' )
				);
			}
		},
		[ announce ]
	);

	if ( loading ) {
		return (
			<div className="rp-kanban-loading">
				<span className="spinner is-active"></span>
				{ window.rpKanban?.i18n?.loading || __( 'Lade Bewerbungen...', 'recruiting-playbook' ) }
			</div>
		);
	}

	if ( error ) {
		return (
			<div className="rp-kanban-error notice notice-error">
				<p>{ error }</p>
				<button onClick={ refetch } className="button">
					{ window.rpKanban?.i18n?.retry || __( 'Erneut versuchen', 'recruiting-playbook' ) }
				</button>
			</div>
		);
	}

	return (
		<>
			{ /* Screen Reader Live Region */ }
			<div
				ref={ liveRegionRef }
				role="status"
				aria-live="polite"
				aria-atomic="true"
				className="screen-reader-text"
			/>

			<DndContext
				sensors={ sensors }
				collisionDetection={ customCollisionDetection }
				onDragStart={ handleDragStart }
				onDragEnd={ handleDragEnd }
				onDragCancel={ handleDragCancel }
			>
				<div
					className="rp-kanban-board"
					role="region"
					aria-label={ __( 'Kanban-Board für Bewerbungen', 'recruiting-playbook' ) }
				>
					{ columns.map( ( column, index ) => (
						<KanbanColumn
							key={ column.id }
							status={ column.id }
							label={ column.label }
							color={ column.color }
							collapsed={ column.collapsed }
							applications={ column.applications }
							columnIndex={ index }
							totalColumns={ columns.length }
						/>
					) ) }
				</div>

				<DragOverlay>
					{ activeApplication ? (
						<KanbanCard
							application={ activeApplication }
							isDragging={ true }
						/>
					) : null }
				</DragOverlay>
			</DndContext>
		</>
	);
}

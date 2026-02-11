/**
 * Kanban Board Main Component
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
 * Custom collision detection
 * Checks pointer position first, then rectangle intersection
 */
function customCollisionDetection( args ) {
	// First check if pointer is within an element
	const pointerCollisions = pointerWithin( args );
	if ( pointerCollisions.length > 0 ) {
		return pointerCollisions;
	}

	// Fallback to rectangle intersection
	return rectIntersection( args );
}

export function KanbanBoard( { jobFilter = '', searchTerm = '', refreshTrigger = 0 } ) {
	const {
		applications,
		loading,
		error,
		updateStatus,
		reorderInColumn,
		moveToColumn,
		refetch,
	} = useApplications();

	const [ activeId, setActiveId ] = useState( null );

	// Ref for live region (screen reader announcements)
	const liveRegionRef = useRef( null );

	// Sensors for drag-and-drop with keyboard support
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
	 * Screen reader announcement
	 *
	 * @param {string} message Message for screen reader
	 */
	const announce = useCallback( ( message ) => {
		if ( liveRegionRef.current ) {
			liveRegionRef.current.textContent = message;
			// Clear after short time for next announcement
			setTimeout( () => {
				if ( liveRegionRef.current ) {
					liveRegionRef.current.textContent = '';
				}
			}, 1000 );
		}
	}, [] );

	// Refresh trigger from parent
	useEffect( () => {
		if ( refreshTrigger > 0 ) {
			refetch();
		}
	}, [ refreshTrigger, refetch ] );

	// Filtered applications
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

	// Group by status
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

	// Helper: Find column for a card ID
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

	// Active card for DragOverlay
	const activeApplication = activeId
		? applications.find( ( app ) => app.id === activeId )
		: null;

	// Announcement after successful drop
	const announceDropResult = useCallback(
		( app, targetColumn, isSameColumn ) => {
			const name = `${ app.first_name } ${ app.last_name }`.trim();
			const columnLabel = targetColumn?.label || '';

			if ( isSameColumn ) {
				announce(
					__( `${ name } reordered in ${ columnLabel }.`, 'recruiting-playbook' )
				);
			} else {
				announce(
					__( `${ name } moved to ${ columnLabel }.`, 'recruiting-playbook' )
				);
			}
		},
		[ announce ]
	);

	// Drag-Start Handler
	const handleDragStart = useCallback( ( event ) => {
		const { active } = event;
		setActiveId( active.id );

		// Screen reader announcement
		const app = applications.find( ( a ) => a.id === active.id );
		if ( app ) {
			const name = `${ app.first_name } ${ app.last_name }`.trim();
			const column = columns.find( ( c ) => c.id === app.status );
			const columnLabel = column?.label || app.status;
			announce(
				__( `${ name } picked up from ${ columnLabel }. Use arrow keys to move.`, 'recruiting-playbook' )
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

			// Find active card and its column
			const activeApp = applications.find( ( a ) => a.id === activeId );
			if ( ! activeApp ) {
				return;
			}

			const activeColumn = findColumnByCardId( activeId );
			if ( ! activeColumn ) {
				return;
			}

			// Determine target status
			let targetStatus = null;
			let targetColumn = null;
			let isOverCard = false;

			if ( over.data?.current?.type === 'column' ) {
				// Over a column
				targetStatus = over.data.current.status;
				targetColumn = columns.find( ( c ) => c.id === targetStatus );
			} else if ( over.data?.current?.type === 'card' ) {
				// Over a card
				targetStatus = over.data.current.status;
				targetColumn = columns.find( ( c ) => c.id === targetStatus );
				isOverCard = true;
			} else {
				// Fallback: overId might be the status
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

			// Case 1: Same column - change sorting
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

			// Case 2: Different column - move card
			if ( sourceStatus !== targetStatus ) {
				// Calculate position in target column
				let targetPosition = 0;

				if ( isOverCard ) {
					// Find position of target card
					const overIndex = targetColumn.applications.findIndex(
						( app ) => app.id === overId
					);
					targetPosition = overIndex >= 0 ? overIndex : targetColumn.applications.length;
				} else {
					// Insert at end of column
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
		announce( __( 'Move cancelled.', 'recruiting-playbook' ) );
	}, [ announce ] );

	if ( loading ) {
		return (
			<div
				className="rp-kanban-loading"
				role="status"
				aria-live="polite"
				aria-busy="true"
			>
				<span className="spinner is-active" aria-hidden="true"></span>
				{ window.rpKanban?.i18n?.loading || __( 'Loading applications...', 'recruiting-playbook' ) }
			</div>
		);
	}

	if ( error ) {
		return (
			<div
				className="rp-kanban-error notice notice-error"
				role="alert"
				aria-live="assertive"
			>
				<p>{ error }</p>
				<button onClick={ refetch } className="button">
					{ window.rpKanban?.i18n?.retry || __( 'Try again', 'recruiting-playbook' ) }
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
					aria-label={ __( 'Kanban board for applications', 'recruiting-playbook' ) }
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

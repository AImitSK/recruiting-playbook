/**
 * Kanban-Spalte
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDroppable } from '@dnd-kit/core';
import {
	SortableContext,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { KanbanCard } from './KanbanCard';

export function KanbanColumn( {
	status,
	label,
	color,
	collapsed: initialCollapsed = false,
	applications,
} ) {
	const [ isCollapsed, setIsCollapsed ] = useState( initialCollapsed );

	const { setNodeRef, isOver } = useDroppable( {
		id: status,
		data: {
			type: 'column',
			status,
		},
	} );

	const count = applications.length;
	const i18n = window.rpKanban?.i18n || {};

	return (
		<div
			className={ `rp-kanban-column ${ isCollapsed ? 'is-collapsed' : '' } ${ isOver ? 'is-over' : '' }` }
			style={ { '--column-color': color } }
		>
			<div
				className="rp-kanban-column-header"
				onClick={ () => setIsCollapsed( ! isCollapsed ) }
				role="button"
				tabIndex={ 0 }
				onKeyDown={ ( e ) => {
					if ( e.key === 'Enter' || e.key === ' ' ) {
						setIsCollapsed( ! isCollapsed );
					}
				} }
				aria-expanded={ ! isCollapsed }
			>
				<span
					className="rp-kanban-column-color"
					style={ { backgroundColor: color } }
				/>
				<h3 className="rp-kanban-column-title">{ label }</h3>
				<span className="rp-kanban-column-count">{ count }</span>
				<button
					className="rp-kanban-collapse-btn"
					aria-label={
						isCollapsed
							? i18n.expand || __( 'Aufklappen', 'recruiting-playbook' )
							: i18n.collapse || __( 'Zuklappen', 'recruiting-playbook' )
					}
					onClick={ ( e ) => {
						e.stopPropagation();
						setIsCollapsed( ! isCollapsed );
					} }
				>
					<span
						className={ `dashicons dashicons-arrow-${ isCollapsed ? 'down' : 'up' }-alt2` }
					/>
				</button>
			</div>

			{ ! isCollapsed && (
				<div ref={ setNodeRef } className="rp-kanban-column-content">
					<SortableContext
						items={ applications.map( ( a ) => a.id ) }
						strategy={ verticalListSortingStrategy }
					>
						{ applications.length === 0 ? (
							<div className="rp-kanban-empty">
								{ i18n.noApplications || __( 'Keine Bewerbungen', 'recruiting-playbook' ) }
							</div>
						) : (
							applications.map( ( app ) => (
								<KanbanCard
									key={ app.id }
									application={ app }
								/>
							) )
						) }
					</SortableContext>
				</div>
			) }
		</div>
	);
}

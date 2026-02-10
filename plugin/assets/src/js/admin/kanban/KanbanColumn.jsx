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
	columnIndex = 0,
	totalColumns = 1,
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

	// Aria-Label für die Spalte
	const columnAriaLabel = `${ label }, ${ count } ${ count === 1
		? __( 'Bewerbung', 'recruiting-playbook' )
		: __( 'Bewerbungen', 'recruiting-playbook' )
	}, Spalte ${ columnIndex + 1 } von ${ totalColumns }`;

	return (
		<section
			className={ `rp-kanban-column ${ isCollapsed ? 'is-collapsed' : '' } ${ isOver ? 'is-over' : '' }` }
			style={ { '--column-color': color } }
			aria-label={ columnAriaLabel }
		>
			<div
				className="rp-kanban-column-header"
				onClick={ () => setIsCollapsed( ! isCollapsed ) }
				role="button"
				tabIndex={ 0 }
				onKeyDown={ ( e ) => {
					if ( e.key === 'Enter' || e.key === ' ' ) {
						e.preventDefault();
						setIsCollapsed( ! isCollapsed );
					}
				} }
				aria-expanded={ ! isCollapsed }
				aria-controls={ `rp-kanban-column-content-${ status }` }
			>
				<span
					className="rp-kanban-column-color"
					style={ { backgroundColor: color } }
					aria-hidden="true"
				/>
				<h3 className="rp-kanban-column-title" id={ `rp-kanban-column-title-${ status }` }>
					{ label }
				</h3>
				<span className="rp-kanban-column-count" aria-label={ `${ count } Bewerbungen` }>
					{ count }
				</span>
				<button
					type="button"
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
						aria-hidden="true"
					/>
				</button>
			</div>

			{ ! isCollapsed && (
				<div
					ref={ setNodeRef }
					className="rp-kanban-column-content"
					id={ `rp-kanban-column-content-${ status }` }
					role="list"
					aria-labelledby={ `rp-kanban-column-title-${ status }` }
				>
					<SortableContext
						items={ applications.map( ( a ) => a.id ) }
						strategy={ verticalListSortingStrategy }
					>
						{ applications.length === 0 ? (
							<div className="rp-kanban-empty" role="listitem">
								{ i18n.noApplications || __( 'Keine Bewerbungen', 'recruiting-playbook' ) }
							</div>
						) : (
							applications.map( ( app, index ) => (
								<KanbanCard
									key={ app.id }
									application={ app }
									index={ index }
									totalInColumn={ applications.length }
									columnLabel={ label }
								/>
							) )
						) }
					</SortableContext>
				</div>
			) }
		</section>
	);
}

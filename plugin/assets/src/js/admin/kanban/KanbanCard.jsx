/**
 * Kanban Card (Applicant)
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { RatingBadge } from '../applicant/RatingStars';
import { TalentPoolBadge } from '../applicant/TalentPoolButton';

export function KanbanCard( {
	application,
	isDragging: isDraggingOverlay = false,
	index = 0,
	totalInColumn = 1,
	columnLabel = '',
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( {
		id: application.id,
		data: {
			type: 'card',
			status: application.status,
			application,
		},
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
	};

	const fullName = `${ application.first_name || '' } ${ application.last_name || '' }`.trim() || __( 'Unknown', 'recruiting-playbook' );
	const initials = getInitials( application.first_name, application.last_name );
	const daysAgo = getDaysAgo( application.created_at );
	const i18n = window.rpKanban?.i18n || {};
	const detailUrl = window.rpKanban?.detailUrl || '';

	// Aria label for the card
	const jobTitle = application.job_title || __( 'No job', 'recruiting-playbook' );
	const dateText = formatDaysAgo( daysAgo, i18n );
	const positionText = `${ index + 1 } of ${ totalInColumn }`;
	const documentsText = application.documents_count > 0
		? `, ${ application.documents_count } ${ application.documents_count === 1 ? 'document' : 'documents' }`
		: '';

	const cardAriaLabel = `${ fullName }, ${ jobTitle }, ${ dateText }${ documentsText }. Position ${ positionText } in ${ columnLabel }. Press space to drag.`;

	const handleClick = ( e ) => {
		// Don't navigate while dragging
		if ( isDragging || isDraggingOverlay ) {
			return;
		}

		// Check if it was a drag event
		if ( e.defaultPrevented ) {
			return;
		}

		// Open detail page
		if ( detailUrl ) {
			window.location.href = `${ detailUrl }${ application.id }`;
		}
	};

	const handleKeyDown = ( e ) => {
		// Enter opens details (only when not dragging)
		if ( e.key === 'Enter' && ! isDragging && ! e.defaultPrevented ) {
			handleClick( e );
		}
	};

	return (
		<article
			ref={ setNodeRef }
			style={ style }
			className={ `rp-kanban-card ${ isDragging || isDraggingOverlay ? 'is-dragging' : '' }` }
			{ ...attributes }
			{ ...listeners }
			onClick={ handleClick }
			onKeyDown={ handleKeyDown }
			role="listitem"
			tabIndex={ 0 }
			aria-label={ cardAriaLabel }
			aria-grabbed={ isDragging || isDraggingOverlay }
			data-application-id={ application.id }
		>
			<div className="rp-kanban-card-header">
				<div
					className="rp-kanban-card-avatar"
					style={ { backgroundColor: getAvatarColor( application.id ) } }
					aria-hidden="true"
				>
					{ initials }
				</div>
				<div className="rp-kanban-card-info">
					<div className="rp-kanban-card-name">{ fullName }</div>
					<div className="rp-kanban-card-email">
						{ application.email || '' }
					</div>
				</div>
			</div>

			<div className="rp-kanban-card-meta">
				<span className="rp-kanban-card-job" title={ application.job_title }>
					<span className="dashicons dashicons-businessman" aria-hidden="true" />
					{ jobTitle }
				</span>
				<span className="rp-kanban-card-date">
					<span className="dashicons dashicons-calendar-alt" aria-hidden="true" />
					{ dateText }
				</span>
			</div>

			{ /* Footer with badges */ }
			<div className="rp-kanban-card-footer">
				{ /* Documents */ }
				{ application.documents_count > 0 && (
					<span className="rp-kanban-card-badge" title={ `${ application.documents_count } documents` }>
						<span className="dashicons dashicons-media-document" aria-hidden="true" />
						{ application.documents_count }
					</span>
				) }

				{ /* Notes */ }
				{ application.notes_count > 0 && (
					<span className="rp-kanban-card-badge" title={ `${ application.notes_count } notes` }>
						<span className="dashicons dashicons-edit" aria-hidden="true" />
						{ application.notes_count }
					</span>
				) }

				{ /* Spacer */ }
				<span className="rp-kanban-card-spacer" />

				{ /* Talent-Pool Badge */ }
				<TalentPoolBadge inPool={ application.in_talent_pool } />

				{ /* Rating Badge */ }
				<RatingBadge
					average={ application.average_rating }
					count={ application.ratings_count }
				/>
			</div>
		</article>
	);
}

/**
 * Generate initials from first and last name
 *
 * @param {string} firstName First name
 * @param {string} lastName  Last name
 * @return {string} Initials
 */
function getInitials( firstName, lastName ) {
	const first = ( firstName || '' ).charAt( 0 ).toUpperCase();
	const last = ( lastName || '' ).charAt( 0 ).toUpperCase();
	return `${ first }${ last }` || '?';
}

/**
 * Calculate days since creation
 *
 * @param {string} dateString Date as string
 * @return {number} Number of days
 */
function getDaysAgo( dateString ) {
	if ( ! dateString ) {
		return 0;
	}
	const date = new Date( dateString );
	const now = new Date();
	const diffTime = Math.abs( now - date );
	const diffDays = Math.floor( diffTime / ( 1000 * 60 * 60 * 24 ) );
	return diffDays;
}

/**
 * Format days
 *
 * @param {number} days Number of days
 * @param {Object} i18n Translations
 * @return {string} Formatted string
 */
function formatDaysAgo( days, i18n ) {
	if ( days === 0 ) {
		return i18n.today || __( 'Today', 'recruiting-playbook' );
	}
	if ( days === 1 ) {
		return i18n.yesterday || __( 'Yesterday', 'recruiting-playbook' );
	}
	const template = i18n.daysAgo || __( '%d days ago', 'recruiting-playbook' );
	return template.replace( '%d', days );
}

/**
 * Generate avatar color based on ID
 *
 * @param {number} id Application ID
 * @return {string} Hex color
 */
function getAvatarColor( id ) {
	const colors = [
		'#2271b1',
		'#00a32a',
		'#dba617',
		'#9b59b6',
		'#e74c3c',
		'#1abc9c',
		'#3498db',
		'#e67e22',
	];
	return colors[ id % colors.length ];
}

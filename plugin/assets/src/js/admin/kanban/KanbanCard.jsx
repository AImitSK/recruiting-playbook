/**
 * Kanban-Karte (Bewerber)
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

export function KanbanCard( { application, isDragging: isDraggingOverlay = false } ) {
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

	const fullName = `${ application.first_name || '' } ${ application.last_name || '' }`.trim() || __( 'Unbekannt', 'recruiting-playbook' );
	const initials = getInitials( application.first_name, application.last_name );
	const daysAgo = getDaysAgo( application.created_at );
	const i18n = window.rpKanban?.i18n || {};
	const detailUrl = window.rpKanban?.detailUrl || '';

	const handleClick = ( e ) => {
		// Nicht navigieren wenn wir draggen
		if ( isDragging || isDraggingOverlay ) {
			return;
		}

		// Prüfen ob es ein Drag-Event war
		if ( e.defaultPrevented ) {
			return;
		}

		// Detail-Seite öffnen
		if ( detailUrl ) {
			window.location.href = `${ detailUrl }${ application.id }`;
		}
	};

	const handleKeyDown = ( e ) => {
		if ( e.key === 'Enter' && ! isDragging ) {
			handleClick( e );
		}
	};

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			className={ `rp-kanban-card ${ isDragging || isDraggingOverlay ? 'is-dragging' : '' }` }
			{ ...attributes }
			{ ...listeners }
			onClick={ handleClick }
			onKeyDown={ handleKeyDown }
			role="button"
			tabIndex={ 0 }
		>
			<div className="rp-kanban-card-header">
				<div
					className="rp-kanban-card-avatar"
					style={ { backgroundColor: getAvatarColor( application.id ) } }
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
					<span className="dashicons dashicons-businessman" />
					{ application.job_title || __( 'Keine Stelle', 'recruiting-playbook' ) }
				</span>
				<span className="rp-kanban-card-date">
					<span className="dashicons dashicons-calendar-alt" />
					{ formatDaysAgo( daysAgo, i18n ) }
				</span>
			</div>

			{ application.documents_count > 0 && (
				<div className="rp-kanban-card-documents">
					<span className="dashicons dashicons-media-document" />
					{ application.documents_count }
				</div>
			) }
		</div>
	);
}

/**
 * Initialen aus Vor- und Nachname generieren
 *
 * @param {string} firstName Vorname
 * @param {string} lastName  Nachname
 * @return {string} Initialen
 */
function getInitials( firstName, lastName ) {
	const first = ( firstName || '' ).charAt( 0 ).toUpperCase();
	const last = ( lastName || '' ).charAt( 0 ).toUpperCase();
	return `${ first }${ last }` || '?';
}

/**
 * Tage seit Erstellung berechnen
 *
 * @param {string} dateString Datum als String
 * @return {number} Anzahl Tage
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
 * Tage formatieren
 *
 * @param {number} days Anzahl Tage
 * @param {Object} i18n Übersetzungen
 * @return {string} Formatierter String
 */
function formatDaysAgo( days, i18n ) {
	if ( days === 0 ) {
		return i18n.today || __( 'Heute', 'recruiting-playbook' );
	}
	if ( days === 1 ) {
		return i18n.yesterday || __( 'Gestern', 'recruiting-playbook' );
	}
	const template = i18n.daysAgo || __( 'vor %d Tagen', 'recruiting-playbook' );
	return template.replace( '%d', days );
}

/**
 * Avatar-Farbe basierend auf ID generieren
 *
 * @param {number} id Bewerbungs-ID
 * @return {string} Hex-Farbe
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

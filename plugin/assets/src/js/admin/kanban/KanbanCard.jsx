/**
 * Kanban-Karte (Bewerber)
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

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

	const fullName = `${ application.first_name || '' } ${ application.last_name || '' }`.trim() || __( 'Unbekannt', 'recruiting-playbook' );
	const initials = getInitials( application.first_name, application.last_name );
	const daysAgo = getDaysAgo( application.created_at );
	const i18n = window.rpKanban?.i18n || {};
	const detailUrl = window.rpKanban?.detailUrl || '';

	// Aria-Label für die Karte
	const jobTitle = application.job_title || __( 'Keine Stelle', 'recruiting-playbook' );
	const dateText = formatDaysAgo( daysAgo, i18n );
	const positionText = `${ index + 1 } von ${ totalInColumn }`;
	const documentsText = application.documents_count > 0
		? `, ${ application.documents_count } ${ application.documents_count === 1 ? 'Dokument' : 'Dokumente' }`
		: '';

	const cardAriaLabel = `${ fullName }, ${ jobTitle }, ${ dateText }${ documentsText }. Position ${ positionText } in ${ columnLabel }. Drücke Leertaste zum Ziehen.`;

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
		// Enter öffnet Details (nur wenn nicht am Draggen)
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

			{ application.documents_count > 0 && (
				<div className="rp-kanban-card-documents">
					<span className="dashicons dashicons-media-document" aria-hidden="true" />
					<span aria-label={ `${ application.documents_count } Dokumente` }>
						{ application.documents_count }
					</span>
				</div>
			) }
		</article>
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

/**
 * Timeline Component
 *
 * Activity Timeline mit Filterung und Gruppierung
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect } from '@wordpress/element';
import { useTimeline } from './hooks/useTimeline';
import { TimelineItem } from './TimelineItem';

/**
 * Kategorie-Filter
 */
const CATEGORY_FILTERS = [
	{ id: 'all', label: 'Alle' },
	{ id: 'status', label: 'Status' },
	{ id: 'note', label: 'Notizen' },
	{ id: 'rating', label: 'Bewertungen' },
	{ id: 'email', label: 'E-Mails' },
	{ id: 'document', label: 'Dokumente' },
];

/**
 * Items nach Datum gruppieren
 *
 * @param {Array} items Timeline-Items
 * @return {Object} Gruppierte Items { 'YYYY-MM-DD': [...items] }
 */
function groupByDate( items ) {
	return items.reduce( ( groups, item ) => {
		const date = item.created_at.split( 'T' )[ 0 ];
		if ( ! groups[ date ] ) {
			groups[ date ] = [];
		}
		groups[ date ].push( item );
		return groups;
	}, {} );
}

/**
 * Datum-Header formatieren
 *
 * @param {string} dateString ISO-Datum (YYYY-MM-DD)
 * @return {string} Formatiertes Datum
 */
function formatDateHeader( dateString ) {
	const i18n = window.rpApplicant?.i18n || {};
	const date = new Date( dateString );
	const today = new Date();
	const yesterday = new Date( today );
	yesterday.setDate( yesterday.getDate() - 1 );

	const todayString = today.toISOString().split( 'T' )[ 0 ];
	const yesterdayString = yesterday.toISOString().split( 'T' )[ 0 ];

	if ( dateString === todayString ) {
		return i18n.today || 'Heute';
	}
	if ( dateString === yesterdayString ) {
		return i18n.yesterday || 'Gestern';
	}

	return date.toLocaleDateString( 'de-DE', {
		weekday: 'long',
		day: 'numeric',
		month: 'long',
		year: 'numeric',
	} );
}

/**
 * Timeline Komponente
 *
 * @param {Object} props               Props
 * @param {number} props.applicationId Bewerbungs-ID
 * @return {JSX.Element} Komponente
 */
export function Timeline( { applicationId } ) {
	const [ filter, setFilter ] = useState( 'all' );
	const {
		items,
		loading,
		error,
		hasMore,
		loadMore,
		refresh,
	} = useTimeline( applicationId, filter );

	const i18n = window.rpApplicant?.i18n || {};

	// Lokalisierte Filter-Labels
	const getFilterLabel = ( filterId ) => {
		const labels = {
			all: i18n.filterAll || 'Alle',
			status: i18n.filterStatus || 'Status',
			note: i18n.filterNotes || 'Notizen',
			rating: i18n.filterRatings || 'Bewertungen',
			email: i18n.filterEmails || 'E-Mails',
			document: i18n.filterDocuments || 'Dokumente',
		};
		return labels[ filterId ] || filterId;
	};

	// Items nach Datum gruppieren
	const groupedItems = groupByDate( items );

	// Loading-Zustand (initial)
	if ( loading && items.length === 0 ) {
		return (
			<div className="rp-timeline rp-timeline--loading">
				<div className="rp-timeline__header">
					<h3>{ i18n.timeline || 'Verlauf' }</h3>
				</div>
				<div className="rp-timeline__loading">
					<span className="spinner is-active"></span>
					{ i18n.loading || 'Laden...' }
				</div>
			</div>
		);
	}

	return (
		<div className="rp-timeline">
			<div className="rp-timeline__header">
				<h3>{ i18n.timeline || 'Verlauf' }</h3>
				<button
					type="button"
					className="rp-timeline__refresh"
					onClick={ refresh }
					title={ i18n.refresh || 'Aktualisieren' }
					disabled={ loading }
				>
					<span className={ `dashicons dashicons-update${ loading ? ' is-spinning' : '' }` }></span>
				</button>
			</div>

			{ /* Filter-Tabs */ }
			<div className="rp-timeline__filters">
				{ CATEGORY_FILTERS.map( ( cat ) => (
					<button
						key={ cat.id }
						type="button"
						className={ `rp-timeline__filter${ filter === cat.id ? ' is-active' : '' }` }
						onClick={ () => setFilter( cat.id ) }
					>
						{ getFilterLabel( cat.id ) }
					</button>
				) ) }
			</div>

			{ /* Fehler-Anzeige */ }
			{ error && (
				<div className="notice notice-error">
					<p>{ error }</p>
				</div>
			) }

			{ /* Timeline-Inhalt */ }
			<div className="rp-timeline__content">
				{ Object.keys( groupedItems ).length === 0 ? (
					<div className="rp-timeline__empty">
						<span className="dashicons dashicons-clock"></span>
						<p>{ i18n.noActivities || 'Noch keine Aktivitäten' }</p>
					</div>
				) : (
					Object.entries( groupedItems ).map( ( [ date, dateItems ] ) => (
						<div key={ date } className="rp-timeline__group">
							<div className="rp-timeline__date">
								{ formatDateHeader( date ) }
							</div>
							<div className="rp-timeline__items">
								{ dateItems.map( ( item ) => (
									<TimelineItem key={ item.id } item={ item } />
								) ) }
							</div>
						</div>
					) )
				) }

				{ /* Mehr laden Button */ }
				{ hasMore && (
					<div className="rp-timeline__load-more">
						<button
							type="button"
							className="button"
							onClick={ loadMore }
							disabled={ loading }
						>
							{ loading ? (
								<>
									<span className="spinner is-active"></span>
									{ i18n.loading || 'Laden...' }
								</>
							) : (
								i18n.loadMore || 'Mehr laden'
							) }
						</button>
					</div>
				) }
			</div>
		</div>
	);
}

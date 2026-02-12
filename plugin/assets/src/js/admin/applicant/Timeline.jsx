/**
 * Timeline Component
 *
 * Activity Timeline - shadcn/ui Style
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { RefreshCw, Clock, MessageSquare, Star, Mail, FileText, UserCheck } from 'lucide-react';
import { Button } from '../components/ui/button';
import { useTimeline } from './hooks/useTimeline';

const CATEGORY_FILTERS = [
	{ id: 'all', label: 'All' },
	{ id: 'status', label: 'Status' },
	{ id: 'note', label: 'Notes' },
	{ id: 'rating', label: 'Ratings' },
	{ id: 'email', label: 'Emails' },
	{ id: 'document', label: 'Documents' },
];

const ACTIVITY_ICONS = {
	status_changed: UserCheck,
	note_added: MessageSquare,
	note_updated: MessageSquare,
	rating_added: Star,
	rating_updated: Star,
	email_sent: Mail,
	document_uploaded: FileText,
	created: Clock,
};

const ACTIVITY_COLORS = {
	status_changed: '#1d71b8',
	note_added: '#6b7280',
	note_updated: '#6b7280',
	rating_added: '#f59e0b',
	rating_updated: '#f59e0b',
	email_sent: '#2fac66',
	document_uploaded: '#8b5cf6',
	created: '#1d71b8',
};

function Spinner( { size = '1rem' } ) {
	return (
		<div
			style={ {
				width: size,
				height: size,
				border: '2px solid #e5e7eb',
				borderTopColor: '#1d71b8',
				borderRadius: '50%',
				animation: 'spin 0.8s linear infinite',
			} }
		/>
	);
}

function groupByDate( items ) {
	return items.reduce( ( groups, item ) => {
		const date = item.created_at.split( 'T' )[ 0 ];
		if ( ! groups[ date ] ) groups[ date ] = [];
		groups[ date ].push( item );
		return groups;
	}, {} );
}

function formatDateHeader( dateString ) {
	const date = new Date( dateString );
	const today = new Date();
	const yesterday = new Date( today );
	yesterday.setDate( yesterday.getDate() - 1 );

	const todayString = today.toISOString().split( 'T' )[ 0 ];
	const yesterdayString = yesterday.toISOString().split( 'T' )[ 0 ];

	if ( dateString === todayString ) return __( 'Today', 'recruiting-playbook' );
	if ( dateString === yesterdayString ) return __( 'Yesterday', 'recruiting-playbook' );

	return date.toLocaleDateString( 'de-DE', {
		weekday: 'long',
		day: 'numeric',
		month: 'long',
		year: 'numeric',
	} );
}

function formatTime( dateString ) {
	return new Date( dateString ).toLocaleTimeString( 'de-DE', {
		hour: '2-digit',
		minute: '2-digit',
	} );
}

function TimelineItem( { item } ) {
	const Icon = ACTIVITY_ICONS[ item.action ] || Clock;
	const color = ACTIVITY_COLORS[ item.action ] || '#6b7280';

	return (
		<div
			style={ {
				display: 'flex',
				gap: '0.75rem',
				padding: '0.75rem',
				backgroundColor: '#f9fafb',
				borderRadius: '0.375rem',
				borderLeft: `3px solid ${ color }`,
			} }
		>
			<div
				style={ {
					width: '1.75rem',
					height: '1.75rem',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
					backgroundColor: color,
					color: '#fff',
					borderRadius: '50%',
					flexShrink: 0,
				} }
			>
				<Icon style={ { width: '0.875rem', height: '0.875rem' } } />
			</div>

			<div style={ { flex: 1, minWidth: 0 } }>
				<div style={ { display: 'flex', alignItems: 'flex-start', gap: '0.5rem', flexWrap: 'wrap' } }>
					{ item.user?.avatar && (
						<img
							src={ item.user.avatar }
							alt={ item.user.name }
							style={ { width: '1.25rem', height: '1.25rem', borderRadius: '50%' } }
						/>
					) }
					<span style={ { flex: 1, fontSize: '0.8125rem', color: '#374151', lineHeight: 1.4 } }>
						{ item.message }
					</span>
					<span style={ { fontSize: '0.6875rem', color: '#9ca3af', whiteSpace: 'nowrap' } }>
						{ formatTime( item.created_at ) }
					</span>
				</div>

				{ item.detail && (
					<div
						style={ {
							marginTop: '0.5rem',
							padding: '0.5rem',
							backgroundColor: 'rgba(255,255,255,0.5)',
							borderRadius: '0.25rem',
							fontSize: '0.75rem',
							color: '#6b7280',
						} }
					>
						{ item.detail }
					</div>
				) }
			</div>
		</div>
	);
}

export function Timeline( { applicationId, compact = false, showHeader = true, maxItems = 0 } ) {
	const [ filter, setFilter ] = useState( 'all' );
	const {
		items,
		loading,
		error,
		hasMore,
		loadMore,
		refresh,
	} = useTimeline( applicationId, filter );

	// Apply maxItems limit if set
	const limitedItems = maxItems > 0 ? items.slice( 0, maxItems ) : items;
	const groupedItems = groupByDate( limitedItems );

	if ( loading && items.length === 0 ) {
		return (
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '2rem', gap: '0.5rem', color: '#6b7280' } }>
				<Spinner />
				{ __( 'Loading...', 'recruiting-playbook' ) }
			</div>
		);
	}

	return (
		<div>
			{ /* Header */ }
			{ showHeader && ! compact && (
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '1rem' } }>
					<h3 style={ { margin: 0, fontSize: '1rem', fontWeight: 600, color: '#1f2937' } }>
						{ __( 'Timeline', 'recruiting-playbook' ) }
					</h3>
					<button
						type="button"
						onClick={ refresh }
						disabled={ loading }
						title={ __( 'Refresh', 'recruiting-playbook' ) }
						style={ {
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							width: '2rem',
							height: '2rem',
							background: 'none',
							border: '1px solid #e5e7eb',
							borderRadius: '0.375rem',
							cursor: 'pointer',
							color: loading ? '#9ca3af' : '#6b7280',
						} }
					>
						<RefreshCw style={ { width: '1rem', height: '1rem', animation: loading ? 'spin 1s linear infinite' : 'none' } } />
					</button>
				</div>
			) }

			{ /* Filter Tabs */ }
			{ ! compact && (
				<div style={ { display: 'flex', flexWrap: 'wrap', gap: '0.25rem', marginBottom: '1rem' } }>
					{ CATEGORY_FILTERS.map( ( cat ) => (
						<button
							key={ cat.id }
							type="button"
							onClick={ () => setFilter( cat.id ) }
							style={ {
								padding: '0.375rem 0.75rem',
								backgroundColor: filter === cat.id ? '#1d71b8' : '#f3f4f6',
								color: filter === cat.id ? '#fff' : '#6b7280',
								border: 'none',
								borderRadius: '9999px',
								fontSize: '0.75rem',
								fontWeight: 500,
								cursor: 'pointer',
								transition: 'all 0.15s ease',
							} }
						>
							{ cat.label }
						</button>
					) ) }
				</div>
			) }

			{ /* Error */ }
			{ error && (
				<div style={ { padding: '0.75rem', backgroundColor: '#fee2e2', color: '#dc2626', borderRadius: '0.375rem', marginBottom: '1rem', fontSize: '0.875rem' } }>
					{ error }
				</div>
			) }

			{ /* Content */ }
			<div>
				{ Object.keys( groupedItems ).length === 0 ? (
					<div style={ { textAlign: 'center', padding: '2rem', color: '#6b7280' } }>
						<Clock style={ { width: '2.5rem', height: '2.5rem', marginBottom: '0.75rem', opacity: 0.3 } } />
						<p style={ { margin: 0 } }>{ __( 'No activities yet', 'recruiting-playbook' ) }</p>
					</div>
				) : (
					Object.entries( groupedItems ).map( ( [ date, dateItems ] ) => (
						<div key={ date } style={ { marginBottom: '1.5rem' } }>
							{ ! compact && (
								<div
									style={ {
										fontSize: '0.75rem',
										fontWeight: 600,
										color: '#6b7280',
										textTransform: 'uppercase',
										letterSpacing: '0.05em',
										marginBottom: '0.75rem',
										paddingBottom: '0.5rem',
										borderBottom: '1px solid #e5e7eb',
									} }
								>
									{ formatDateHeader( date ) }
								</div>
							) }
							<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
								{ dateItems.map( ( item ) => (
									<TimelineItem key={ item.id } item={ item } />
								) ) }
							</div>
						</div>
					) )
				) }

				{ hasMore && ! compact && (
					<div style={ { textAlign: 'center', marginTop: '1rem' } }>
						<Button variant="outline" onClick={ loadMore } disabled={ loading }>
							{ loading ? (
								<>
									<Spinner size="0.875rem" />
									{ __( 'Loading...', 'recruiting-playbook' ) }
								</>
							) : (
								__( 'Load more', 'recruiting-playbook' )
							) }
						</Button>
					</div>
				) }
			</div>

			<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
		</div>
	);
}

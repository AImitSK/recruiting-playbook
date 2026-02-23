/**
 * Rating Stars Component
 *
 * Star rating with categories - shadcn/ui Style
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Star, Briefcase, Users, TrendingUp } from 'lucide-react';
import { useRating } from './hooks/useRating';

/**
 * Rating Categories
 */
const CATEGORIES = {
	overall: {
		label: __( 'Overall Impression', 'recruiting-playbook' ),
		Icon: Star,
	},
	skills: {
		label: __( 'Professional Competence', 'recruiting-playbook' ),
		Icon: Briefcase,
	},
	culture_fit: {
		label: __( 'Cultural Fit', 'recruiting-playbook' ),
		Icon: Users,
	},
	experience: {
		label: __( 'Experience', 'recruiting-playbook' ),
		Icon: TrendingUp,
	},
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

/**
 * Star Display Component (reusable)
 */
function Stars( { rating = 0, average = 0, count = 0, readonly = false, onRate, saving = false } ) {
	const [ hoverRating, setHoverRating ] = useState( null );

	const displayRating = hoverRating ?? rating ?? 0;

	const handleClick = ( star ) => {
		if ( readonly || saving ) {
			return;
		}
		onRate( star );
	};

	return (
		<div
			style={ { display: 'flex', alignItems: 'center', gap: '0.25rem' } }
			onMouseLeave={ () => setHoverRating( null ) }
		>
			{ [ 1, 2, 3, 4, 5 ].map( ( star ) => {
				const isFilled = star <= displayRating;
				const isHover = ! readonly && star <= ( hoverRating || 0 );

				return (
					<button
						key={ star }
						type="button"
						onClick={ () => handleClick( star ) }
						onMouseEnter={ () => ! readonly && ! saving && setHoverRating( star ) }
						disabled={ readonly || saving }
						style={ {
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							width: '1.75rem',
							height: '1.75rem',
							padding: 0,
							background: 'none',
							border: 'none',
							cursor: readonly || saving ? 'default' : 'pointer',
							color: isFilled ? '#f59e0b' : isHover ? '#fbbf24' : '#d1d5db',
							transition: 'color 0.15s ease',
						} }
						aria-label={ `${ star } ${ __( 'out of 5 stars', 'recruiting-playbook' ) }` }
					>
						<Star
							style={ {
								width: '1.25rem',
								height: '1.25rem',
								fill: isFilled ? 'currentColor' : 'none',
							} }
						/>
					</button>
				);
			} ) }

			{ average > 0 && (
				<span style={ { marginLeft: '0.5rem', fontSize: '0.875rem', color: '#6b7280', display: 'flex', alignItems: 'center', gap: '0.25rem' } }>
					{ average.toFixed( 1 ) }
					{ count > 0 && (
						<span style={ { color: '#9ca3af' } }>
							({ count })
						</span>
					) }
				</span>
			) }
		</div>
	);
}

/**
 * Simple Rating View (overall stars only)
 */
export function RatingSimple( { applicationId, readonly = false } ) {
	const { summary, userRatings, loading, saving, rate } = useRating( applicationId );

	if ( loading ) {
		return (
			<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', color: '#6b7280' } }>
				<Spinner />
			</div>
		);
	}

	return (
		<Stars
			rating={ userRatings?.overall }
			average={ summary?.average }
			count={ summary?.count }
			readonly={ readonly }
			saving={ saving }
			onRate={ ( stars ) => rate( 'overall', stars ) }
		/>
	);
}

/**
 * Detailed Rating View with Categories
 */
export function RatingDetailed( { applicationId, readonly = false, showDistribution = true } ) {
	const { summary, userRatings, loading, error, saving, rate } = useRating( applicationId );
	const [ activeCategory, setActiveCategory ] = useState( 'overall' );

	if ( loading ) {
		return (
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '2rem', gap: '0.5rem', color: '#6b7280' } }>
				<Spinner />
				{ __( 'Loading...', 'recruiting-playbook' ) }
			</div>
		);
	}

	return (
		<div>
			{ error && (
				<div style={ { padding: '0.75rem', backgroundColor: '#fee2e2', color: '#dc2626', borderRadius: '0.375rem', marginBottom: '1rem', fontSize: '0.875rem' } }>
					{ error }
				</div>
			) }

			{ /* Category Tabs */ }
			<div style={ { display: 'flex', flexWrap: 'wrap', gap: '0.25rem', marginBottom: '1rem' } }>
				{ Object.entries( CATEGORIES ).map( ( [ key, config ] ) => {
					const isActive = activeCategory === key;
					const Icon = config.Icon;

					return (
						<button
							key={ key }
							type="button"
							onClick={ () => setActiveCategory( key ) }
							style={ {
								display: 'inline-flex',
								alignItems: 'center',
								gap: '0.375rem',
								padding: '0.5rem 0.75rem',
								backgroundColor: isActive ? '#1d71b8' : '#f3f4f6',
								color: isActive ? '#fff' : '#6b7280',
								border: 'none',
								borderRadius: '0.375rem',
								fontSize: '0.8125rem',
								fontWeight: 500,
								cursor: 'pointer',
								transition: 'all 0.15s ease',
							} }
						>
							<Icon style={ { width: '0.875rem', height: '0.875rem' } } />
							{ config.label }
							{ userRatings?.[ key ] && (
								<span
									style={ {
										backgroundColor: isActive ? 'rgba(255,255,255,0.2)' : '#e5e7eb',
										color: isActive ? '#fff' : '#374151',
										padding: '0.125rem 0.375rem',
										borderRadius: '0.25rem',
										fontSize: '0.75rem',
										fontWeight: 600,
									} }
								>
									{ userRatings[ key ] }
								</span>
							) }
						</button>
					);
				} ) }
			</div>

			{ /* Active Category */ }
			<div style={ { marginBottom: '1.5rem' } }>
				{ Object.entries( CATEGORIES ).map( ( [ key, config ] ) => {
					if ( activeCategory !== key ) return null;
					const Icon = config.Icon;

					return (
						<div
							key={ key }
							style={ {
								padding: '1rem',
								backgroundColor: '#f9fafb',
								borderRadius: '0.375rem',
								borderLeft: '3px solid #1d71b8',
							} }
						>
							<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: '0.75rem' } }>
								<div
									style={ {
										width: '1.75rem',
										height: '1.75rem',
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'center',
										backgroundColor: '#1d71b8',
										color: '#fff',
										borderRadius: '0.375rem',
									} }
								>
									<Icon style={ { width: '1rem', height: '1rem' } } />
								</div>
								<span style={ { fontWeight: 600, fontSize: '0.9375rem', color: '#1f2937' } }>
									{ config.label }
								</span>
							</div>
							<Stars
								rating={ userRatings?.[ key ] }
								average={ summary?.by_category?.[ key ]?.average }
								count={ summary?.by_category?.[ key ]?.count }
								readonly={ readonly }
								saving={ saving }
								onRate={ ( stars ) => rate( key, stars ) }
							/>
						</div>
					);
				} ) }
			</div>

			{ /* Distribution */ }
			{ showDistribution && summary?.distribution && summary.count > 0 && (
				<div>
					<h4 style={ { margin: '0 0 0.75rem 0', fontSize: '0.875rem', fontWeight: 600, color: '#374151' } }>
						{ __( 'Distribution', 'recruiting-playbook' ) }
					</h4>
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.375rem' } }>
						{ [ 5, 4, 3, 2, 1 ].map( ( star ) => {
							const count = summary.distribution[ star ] || 0;
							const percentage = summary.count > 0 ? ( count / summary.count ) * 100 : 0;

							return (
								<div key={ star } style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
									<span style={ { display: 'flex', alignItems: 'center', gap: '0.25rem', width: '2rem', fontSize: '0.8125rem', color: '#6b7280' } }>
										{ star }
										<Star style={ { width: '0.75rem', height: '0.75rem', fill: '#f59e0b', color: '#f59e0b' } } />
									</span>
									<div
										style={ {
											flex: 1,
											height: '0.5rem',
											backgroundColor: '#e5e7eb',
											borderRadius: '0.25rem',
											overflow: 'hidden',
										} }
									>
										<div
											style={ {
												width: `${ percentage }%`,
												height: '100%',
												backgroundColor: '#f59e0b',
												borderRadius: '0.25rem',
												transition: 'width 0.3s ease',
											} }
										/>
									</div>
									<span style={ { width: '1.5rem', fontSize: '0.75rem', color: '#9ca3af', textAlign: 'right' } }>
										{ count }
									</span>
								</div>
							);
						} ) }
					</div>
				</div>
			) }

			<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
		</div>
	);
}

/**
 * Compact Rating Badge for Kanban Cards
 */
export function RatingBadge( { average, count = 0 } ) {
	if ( ! average || average <= 0 ) {
		return null;
	}

	return (
		<div
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				gap: '0.25rem',
				padding: '0.25rem 0.5rem',
				backgroundColor: '#fef3c7',
				color: '#92400e',
				borderRadius: '0.25rem',
				fontSize: '0.75rem',
				fontWeight: 500,
			} }
			title={ sprintf( _n( '%d rating', '%d ratings', count, 'recruiting-playbook' ), count ) }
		>
			<Star style={ { width: '0.75rem', height: '0.75rem', fill: '#f59e0b', color: '#f59e0b' } } />
			<span>{ average.toFixed( 1 ) }</span>
		</div>
	);
}

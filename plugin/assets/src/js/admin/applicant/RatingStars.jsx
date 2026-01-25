/**
 * Rating Stars Component
 *
 * Sterne-Bewertung mit Kategorien
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback, useEffect } from '@wordpress/element';
import { useRating } from './hooks/useRating';

/**
 * Bewertungs-Kategorien
 */
const CATEGORIES = {
	overall: {
		label: 'Gesamteindruck',
		icon: 'star-filled',
	},
	skills: {
		label: 'Fachkompetenz',
		icon: 'portfolio',
	},
	culture_fit: {
		label: 'Kulturelle Passung',
		icon: 'groups',
	},
	experience: {
		label: 'Erfahrung',
		icon: 'chart-line',
	},
};

/**
 * Sterne-Anzeige Komponente (wiederverwendbar)
 *
 * @param {Object}   props             Props
 * @param {number}   props.rating      Aktuelle Bewertung
 * @param {number}   props.average     Durchschnittliche Bewertung
 * @param {number}   props.count       Anzahl Bewertungen
 * @param {boolean}  props.readonly    Nur-Lesen-Modus
 * @param {Function} props.onRate      Callback beim Bewerten
 * @param {boolean}  props.saving      Speichervorgang aktiv?
 * @return {JSX.Element} Sterne-Komponente
 */
function Stars( { rating = 0, average = 0, count = 0, readonly = false, onRate, saving = false } ) {
	const [ hoverRating, setHoverRating ] = useState( null );
	const i18n = window.rpApplicant?.i18n || {};

	const displayRating = hoverRating ?? rating ?? 0;

	const handleClick = ( star ) => {
		if ( readonly || saving ) {
			return;
		}
		onRate( star );
	};

	return (
		<div
			className="rp-rating-stars"
			onMouseLeave={ () => setHoverRating( null ) }
		>
			{ [ 1, 2, 3, 4, 5 ].map( ( star ) => (
				<button
					key={ star }
					type="button"
					className={ `rp-rating-star${ star <= displayRating ? ' is-filled' : '' }${ ! readonly && star <= ( hoverRating || 0 ) ? ' is-hover' : '' }` }
					onClick={ () => handleClick( star ) }
					onMouseEnter={ () => ! readonly && ! saving && setHoverRating( star ) }
					disabled={ readonly || saving }
					aria-label={ `${ star } ${ i18n.ofStars || 'von 5 Sternen' }` }
				>
					<span
						className={ `dashicons dashicons-star-${ star <= displayRating ? 'filled' : 'empty' }` }
					/>
				</button>
			) ) }

			{ average > 0 && (
				<span className="rp-rating-average">
					{ average.toFixed( 1 ) }
					{ count > 0 && (
						<span className="rp-rating-count">
							({ count })
						</span>
					) }
				</span>
			) }
		</div>
	);
}

/**
 * Einfache Rating-Ansicht (nur Gesamt-Sterne)
 *
 * @param {Object}  props               Props
 * @param {number}  props.applicationId Bewerbungs-ID
 * @param {boolean} props.readonly      Nur-Lesen-Modus
 * @return {JSX.Element} Komponente
 */
export function RatingSimple( { applicationId, readonly = false } ) {
	const { summary, userRatings, loading, saving, rate } = useRating( applicationId );

	if ( loading ) {
		return (
			<div className="rp-rating-simple rp-rating--loading">
				<span className="spinner is-active"></span>
			</div>
		);
	}

	return (
		<div className="rp-rating-simple">
			<Stars
				rating={ userRatings?.overall }
				average={ summary?.average }
				count={ summary?.count }
				readonly={ readonly }
				saving={ saving }
				onRate={ ( stars ) => rate( 'overall', stars ) }
			/>
		</div>
	);
}

/**
 * Detaillierte Rating-Ansicht mit Kategorien
 *
 * @param {Object}  props               Props
 * @param {number}  props.applicationId Bewerbungs-ID
 * @param {boolean} props.readonly      Nur-Lesen-Modus
 * @param {boolean} props.showDistribution Verteilung anzeigen?
 * @return {JSX.Element} Komponente
 */
export function RatingDetailed( { applicationId, readonly = false, showDistribution = true } ) {
	const { summary, userRatings, loading, error, saving, rate } = useRating( applicationId );
	const [ activeCategory, setActiveCategory ] = useState( 'overall' );

	const i18n = window.rpApplicant?.i18n || {};

	if ( loading ) {
		return (
			<div className="rp-rating-detailed rp-rating--loading">
				<span className="spinner is-active"></span>
				{ i18n.loading || 'Laden...' }
			</div>
		);
	}

	return (
		<div className="rp-rating-detailed">
			{ error && (
				<div className="notice notice-error">
					<p>{ error }</p>
				</div>
			) }

			{ /* Kategorie-Tabs */ }
			<div className="rp-rating-tabs">
				{ Object.entries( CATEGORIES ).map( ( [ key, config ] ) => (
					<button
						key={ key }
						type="button"
						className={ `rp-rating-tab${ activeCategory === key ? ' is-active' : '' }` }
						onClick={ () => setActiveCategory( key ) }
					>
						<span className={ `dashicons dashicons-${ config.icon }` }></span>
						{ config.label }
						{ userRatings?.[ key ] && (
							<span className="rp-rating-tab-value">{ userRatings[ key ] }</span>
						) }
					</button>
				) ) }
			</div>

			{ /* Aktive Kategorie */ }
			<div className="rp-rating-content">
				{ Object.entries( CATEGORIES ).map( ( [ key, config ] ) => (
					<div
						key={ key }
						className={ `rp-rating-category${ activeCategory === key ? ' is-visible' : '' }` }
					>
						<div className="rp-rating-category-header">
							<span className={ `dashicons dashicons-${ config.icon }` }></span>
							<span className="rp-rating-category-label">{ config.label }</span>
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
				) ) }
			</div>

			{ /* Verteilung */ }
			{ showDistribution && summary?.distribution && summary.count > 0 && (
				<div className="rp-rating-distribution">
					<h4>{ i18n.distribution || 'Verteilung' }</h4>
					{ [ 5, 4, 3, 2, 1 ].map( ( star ) => {
						const count = summary.distribution[ star ] || 0;
						const percentage =
							summary.count > 0 ? ( count / summary.count ) * 100 : 0;

						return (
							<div key={ star } className="rp-rating-bar">
								<span className="rp-rating-bar-label">
									{ star } <span className="dashicons dashicons-star-filled"></span>
								</span>
								<div className="rp-rating-bar-track">
									<div
										className="rp-rating-bar-fill"
										style={ { width: `${ percentage }%` } }
									></div>
								</div>
								<span className="rp-rating-bar-count">{ count }</span>
							</div>
						);
					} ) }
				</div>
			) }
		</div>
	);
}

/**
 * Kompakte Rating-Anzeige für Kanban-Karten
 *
 * @param {Object} props         Props
 * @param {number} props.average Durchschnittliche Bewertung
 * @param {number} props.count   Anzahl Bewertungen
 * @return {JSX.Element|null} Komponente oder null wenn kein Rating
 */
export function RatingBadge( { average, count = 0 } ) {
	if ( ! average || average <= 0 ) {
		return null;
	}

	return (
		<div className="rp-rating-badge" title={ `${ count } ${ count === 1 ? 'Bewertung' : 'Bewertungen' }` }>
			<span className="dashicons dashicons-star-filled"></span>
			<span className="rp-rating-badge-value">{ average.toFixed( 1 ) }</span>
		</div>
	);
}

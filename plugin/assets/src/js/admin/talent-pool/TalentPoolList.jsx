/**
 * Talent Pool List Component
 *
 * Hauptkomponente für die Talent-Pool Übersichtsseite
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { useTalentPoolList } from '../applicant/hooks/useTalentPool';
import { TalentPoolCard } from './TalentPoolCard';

/**
 * Talent-Pool Liste Komponente
 *
 * @return {JSX.Element} Komponente
 */
export function TalentPoolList() {
	const [ allTags, setAllTags ] = useState( [] );
	const [ selectedTag, setSelectedTag ] = useState( '' );
	const [ searchInput, setSearchInput ] = useState( '' );

	const config = window.rpTalentPool || {};
	const i18n = config.i18n || {};

	const {
		items,
		loading,
		error,
		total,
		totalPages,
		page,
		setSearch,
		setTags,
		setPage,
		refetch,
	} = useTalentPoolList();

	/**
	 * Verfügbare Tags laden
	 */
	const loadTags = useCallback( async () => {
		try {
			const response = await fetch(
				`${ config.apiUrl }talent-pool/tags`,
				{
					headers: {
						'X-WP-Nonce': config.nonce,
					},
				}
			);
			if ( response.ok ) {
				const tags = await response.json();
				setAllTags( tags || [] );
			}
		} catch ( err ) {
			console.error( 'Error loading tags:', err );
		}
	}, [ config.apiUrl, config.nonce ] );

	// Tags initial laden
	useEffect( () => {
		loadTags();
	}, [ loadTags ] );

	/**
	 * Suche mit Debounce
	 */
	useEffect( () => {
		const timeoutId = setTimeout( () => {
			setSearch( searchInput );
		}, 300 );

		return () => clearTimeout( timeoutId );
	}, [ searchInput, setSearch ] );

	/**
	 * Tag-Filter ändern
	 *
	 * @param {Event} e Change event
	 */
	const handleTagChange = ( e ) => {
		const tag = e.target.value;
		setSelectedTag( tag );
		setTags( tag );
	};

	/**
	 * Kandidat entfernt Handler
	 *
	 * @param {number} candidateId Entfernte Kandidaten-ID
	 */
	const handleCandidateRemoved = ( candidateId ) => {
		// Liste neu laden nach Entfernung
		refetch();
		loadTags(); // Tags auch aktualisieren
	};

	// Loading State
	if ( loading && items.length === 0 ) {
		return (
			<div className="rp-talent-pool rp-talent-pool--loading">
				<div className="rp-talent-pool__loading">
					<span className="spinner is-active"></span>
					{ i18n.loading || 'Lade Talent-Pool...' }
				</div>
			</div>
		);
	}

	// Error State
	if ( error && items.length === 0 ) {
		return (
			<div className="rp-talent-pool rp-talent-pool--error">
				<div className="notice notice-error">
					<p>{ error }</p>
				</div>
				<button type="button" className="button" onClick={ refetch }>
					{ i18n.retry || 'Erneut versuchen' }
				</button>
			</div>
		);
	}

	return (
		<div className="rp-talent-pool">
			{ /* Header */ }
			<div className="rp-talent-pool__header">
				<div className="rp-talent-pool__title-section">
					<h1 className="rp-talent-pool__title">
						<span className="dashicons dashicons-groups"></span>
						{ i18n.title || 'Talent-Pool' }
					</h1>
					<p className="rp-talent-pool__subtitle">
						{ i18n.subtitle || 'Vielversprechende Kandidaten für zukünftige Stellen' }
					</p>
				</div>

				<div className="rp-talent-pool__stats">
					<div className="rp-talent-pool__stat">
						<span className="rp-talent-pool__stat-value">{ total }</span>
						<span className="rp-talent-pool__stat-label">
							{ total === 1
								? ( i18n.candidate || 'Kandidat' )
								: ( i18n.candidates || 'Kandidaten' )
							}
						</span>
					</div>
				</div>
			</div>

			{ /* Filter-Leiste */ }
			<div className="rp-talent-pool__filters">
				<div className="rp-talent-pool__search">
					<span className="dashicons dashicons-search"></span>
					<input
						type="text"
						placeholder={ i18n.search || 'Kandidaten suchen...' }
						value={ searchInput }
						onChange={ ( e ) => setSearchInput( e.target.value ) }
						className="rp-talent-pool__search-input"
					/>
					{ searchInput && (
						<button
							type="button"
							className="rp-talent-pool__search-clear"
							onClick={ () => setSearchInput( '' ) }
							aria-label="Clear search"
						>
							<span className="dashicons dashicons-no-alt"></span>
						</button>
					) }
				</div>

				<div className="rp-talent-pool__filter-group">
					<label htmlFor="rp-tag-filter" className="screen-reader-text">
						{ i18n.filterByTags || 'Nach Tags filtern' }
					</label>
					<select
						id="rp-tag-filter"
						value={ selectedTag }
						onChange={ handleTagChange }
						className="rp-talent-pool__tag-filter"
					>
						<option value="">{ i18n.allTags || 'Alle Tags' }</option>
						{ allTags.map( ( tag ) => (
							<option key={ tag } value={ tag }>
								{ tag }
							</option>
						) ) }
					</select>
				</div>
			</div>

			{ /* Inhalt */ }
			{ items.length === 0 ? (
				<div className="rp-talent-pool__empty">
					<div className="rp-talent-pool__empty-icon">
						<span className="dashicons dashicons-groups"></span>
					</div>
					<h2>{ i18n.emptyPool || 'Der Talent-Pool ist noch leer.' }</h2>
					<p>
						{ i18n.emptyPoolHint ||
							'Fügen Sie vielversprechende Kandidaten aus der Bewerbungsdetailseite zum Talent-Pool hinzu.' }
					</p>
					<a
						href={ config.applicationsUrl || '#' }
						className="button button-primary"
					>
						{ i18n.goToApplications || 'Zu den Bewerbungen' }
					</a>
				</div>
			) : (
				<>
					{ /* Kandidaten-Grid */ }
					<div className="rp-talent-pool__grid">
						{ items.map( ( entry ) => (
							<TalentPoolCard
								key={ entry.id }
								entry={ entry }
								onRemoved={ handleCandidateRemoved }
							/>
						) ) }
					</div>

					{ /* Pagination */ }
					{ totalPages > 1 && (
						<div className="rp-talent-pool__pagination">
							<div className="rp-talent-pool__pagination-info">
								{ i18n.page || 'Seite' } { page } { i18n.of || 'von' } { totalPages }
								{ ' ' }({ i18n.total || 'Gesamt' }: { total })
							</div>

							<div className="rp-talent-pool__pagination-buttons">
								<button
									type="button"
									className="button"
									onClick={ () => setPage( page - 1 ) }
									disabled={ page <= 1 || loading }
								>
									<span className="dashicons dashicons-arrow-left-alt2"></span>
									{ i18n.previous || 'Vorherige' }
								</button>
								<button
									type="button"
									className="button"
									onClick={ () => setPage( page + 1 ) }
									disabled={ page >= totalPages || loading }
								>
									{ i18n.next || 'Nächste' }
									<span className="dashicons dashicons-arrow-right-alt2"></span>
								</button>
							</div>
						</div>
					) }

					{ /* DSGVO-Hinweis */ }
					<div className="rp-talent-pool__gdpr-notice">
						<span className="dashicons dashicons-info-outline"></span>
						{ i18n.gdprNotice || 'DSGVO-Hinweis: Kandidaten werden nach Ablauf automatisch aus dem Pool entfernt.' }
					</div>
				</>
			) }

			{ /* Loading Overlay */ }
			{ loading && items.length > 0 && (
				<div className="rp-talent-pool__loading-overlay">
					<span className="spinner is-active"></span>
				</div>
			) }
		</div>
	);
}

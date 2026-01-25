/**
 * Applicant Detail Component
 *
 * Hauptkomponente für die Bewerber-Detailseite
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { NotesPanel } from './NotesPanel';
import { RatingDetailed } from './RatingStars';
import { Timeline } from './Timeline';
import { TalentPoolButton } from './TalentPoolButton';
import { EmailTab } from './EmailTab';

/**
 * Status-Optionen
 */
const STATUS_OPTIONS = [
	{ value: 'new', label: 'Neu', color: '#2271b1' },
	{ value: 'screening', label: 'In Prüfung', color: '#dba617' },
	{ value: 'interview', label: 'Interview', color: '#9b59b6' },
	{ value: 'offer', label: 'Angebot', color: '#00a32a' },
	{ value: 'hired', label: 'Eingestellt', color: '#00a32a' },
	{ value: 'rejected', label: 'Abgelehnt', color: '#d63638' },
	{ value: 'withdrawn', label: 'Zurückgezogen', color: '#787c82' },
];

/**
 * Initialen aus Namen generieren
 *
 * @param {string} firstName Vorname
 * @param {string} lastName  Nachname
 * @return {string} Initialen
 */
function getInitials( firstName, lastName ) {
	const first = firstName?.charAt( 0 )?.toUpperCase() || '';
	const last = lastName?.charAt( 0 )?.toUpperCase() || '';
	return `${ first }${ last }` || '?';
}

/**
 * Datum formatieren
 *
 * @param {string} dateString ISO-Datum
 * @return {string} Formatiertes Datum
 */
function formatDate( dateString ) {
	if ( ! dateString ) {
		return '-';
	}
	const date = new Date( dateString );
	return date.toLocaleDateString( 'de-DE', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
	} );
}

/**
 * Bewerber-Detailseite Komponente
 *
 * @param {Object} props               Props
 * @param {number} props.applicationId Bewerbungs-ID
 * @return {JSX.Element} Komponente
 */
export function ApplicantDetail( { applicationId } ) {
	const [ application, setApplication ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ statusChanging, setStatusChanging ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( 'details' );

	const i18n = window.rpApplicant?.i18n || {};
	const config = window.rpApplicant || {};
	const canSendEmails = config.canSendEmails !== false;

	/**
	 * Bewerbung laden
	 */
	const loadApplication = useCallback( async () => {
		if ( ! applicationId ) {
			return;
		}

		try {
			setLoading( true );
			setError( null );

			const data = await apiFetch( {
				path: `/recruiting/v1/applications/${ applicationId }`,
			} );

			setApplication( data );
		} catch ( err ) {
			console.error( 'Error loading application:', err );
			setError(
				err.message ||
				i18n.errorLoadingApplication ||
				'Fehler beim Laden der Bewerbung'
			);
		} finally {
			setLoading( false );
		}
	}, [ applicationId, i18n.errorLoadingApplication ] );

	// Initial laden
	useEffect( () => {
		loadApplication();
	}, [ loadApplication ] );

	/**
	 * Status ändern
	 *
	 * @param {string} newStatus Neuer Status
	 */
	const handleStatusChange = async ( newStatus ) => {
		if ( ! application || statusChanging ) {
			return;
		}

		const previousStatus = application.status;

		try {
			setStatusChanging( true );

			// Optimistic Update
			setApplication( ( prev ) => ( { ...prev, status: newStatus } ) );

			await apiFetch( {
				path: `/recruiting/v1/applications/${ applicationId }/status`,
				method: 'PATCH',
				data: { status: newStatus },
			} );
		} catch ( err ) {
			console.error( 'Error changing status:', err );

			// Rollback
			setApplication( ( prev ) => ( { ...prev, status: previousStatus } ) );

			// Fehlermeldung
			alert( err.message || i18n.errorChangingStatus || 'Fehler beim Ändern des Status' );
		} finally {
			setStatusChanging( false );
		}
	};

	/**
	 * Talent-Pool Status geändert
	 *
	 * @param {boolean} inPool Im Pool?
	 */
	const handleTalentPoolChange = ( inPool ) => {
		setApplication( ( prev ) => ( { ...prev, in_talent_pool: inPool } ) );
	};

	// Loading
	if ( loading ) {
		return (
			<div className="rp-applicant-detail rp-applicant-detail--loading">
				<div className="rp-applicant-detail__loading">
					<span className="spinner is-active"></span>
					{ i18n.loadingApplication || 'Lade Bewerbung...' }
				</div>
			</div>
		);
	}

	// Error
	if ( error ) {
		return (
			<div className="rp-applicant-detail rp-applicant-detail--error">
				<div className="notice notice-error">
					<p>{ error }</p>
				</div>
				<button type="button" className="button" onClick={ loadApplication }>
					{ i18n.retry || 'Erneut versuchen' }
				</button>
			</div>
		);
	}

	// Nicht gefunden
	if ( ! application ) {
		return (
			<div className="rp-applicant-detail rp-applicant-detail--not-found">
				<div className="notice notice-warning">
					<p>{ i18n.applicationNotFound || 'Bewerbung nicht gefunden.' }</p>
				</div>
			</div>
		);
	}

	const currentStatus = STATUS_OPTIONS.find( ( s ) => s.value === application.status );

	return (
		<div className="rp-applicant-detail">
			{ /* Header */ }
			<div className="rp-applicant-detail__header">
				<a href={ config.listUrl || '#' } className="rp-back-link">
					<span className="dashicons dashicons-arrow-left-alt"></span>
					{ i18n.backToList || 'Zurück zur Liste' }
				</a>

				<h1 className="rp-applicant-detail__title">
					{ i18n.application || 'Bewerbung' } #{ applicationId }
				</h1>

				<div className="rp-applicant-detail__actions">
					<TalentPoolButton
						candidateId={ application.candidate_id }
						inPool={ application.in_talent_pool }
						onStatusChange={ handleTalentPoolChange }
					/>
				</div>
			</div>

			{ /* Tabs */ }
			{ canSendEmails && (
				<div className="rp-applicant-detail__tabs">
					<button
						type="button"
						className={ `rp-tab ${ activeTab === 'details' ? 'rp-tab--active' : '' }` }
						onClick={ () => setActiveTab( 'details' ) }
					>
						<span className="dashicons dashicons-id-alt"></span>
						{ i18n.details || 'Details' }
					</button>
					<button
						type="button"
						className={ `rp-tab ${ activeTab === 'email' ? 'rp-tab--active' : '' }` }
						onClick={ () => setActiveTab( 'email' ) }
					>
						<span className="dashicons dashicons-email-alt"></span>
						{ i18n.email || 'E-Mail' }
					</button>
				</div>
			) }

			{ /* Tab: Details */ }
			{ ( activeTab === 'details' || ! canSendEmails ) && (
				<div className="rp-applicant-detail__layout">
					{ /* Main Content */ }
					<div className="rp-applicant-detail__main">
						{ /* Kandidaten-Info Card */ }
						<div className="rp-card rp-candidate-info">
							<div className="rp-candidate-info__header">
								<div className="rp-candidate-info__avatar">
									{ getInitials( application.first_name, application.last_name ) }
								</div>
								<div className="rp-candidate-info__details">
									<h2 className="rp-candidate-info__name">
										{ application.first_name } { application.last_name }
									</h2>
									<div className="rp-candidate-info__contact">
										{ application.email && (
											<a href={ `mailto:${ application.email }` } className="rp-candidate-info__email">
												<span className="dashicons dashicons-email"></span>
												{ application.email }
											</a>
										) }
										{ application.phone && (
											<a href={ `tel:${ application.phone }` } className="rp-candidate-info__phone">
												<span className="dashicons dashicons-phone"></span>
												{ application.phone }
											</a>
										) }
									</div>
								</div>
							</div>

							{ /* Status-Auswahl */ }
							<div className="rp-candidate-info__status">
								<label htmlFor="rp-status-select">
									{ i18n.status || 'Status' }:
								</label>
								<select
									id="rp-status-select"
									value={ application.status }
									onChange={ ( e ) => handleStatusChange( e.target.value ) }
									disabled={ statusChanging }
									className="rp-status-select"
									style={ { borderColor: currentStatus?.color } }
								>
									{ STATUS_OPTIONS.map( ( option ) => (
										<option key={ option.value } value={ option.value }>
											{ option.label }
										</option>
									) ) }
								</select>
								{ statusChanging && <span className="spinner is-active"></span> }
							</div>

							{ /* Meta-Infos */ }
							<div className="rp-candidate-info__meta">
								{ application.job_title && (
									<div className="rp-candidate-info__meta-item">
										<span className="dashicons dashicons-businessman"></span>
										<span>{ application.job_title }</span>
									</div>
								) }
								<div className="rp-candidate-info__meta-item">
									<span className="dashicons dashicons-calendar-alt"></span>
									<span>
										{ i18n.appliedOn || 'Beworben am' }: { formatDate( application.created_at ) }
									</span>
								</div>
							</div>
						</div>

						{ /* Bewertung */ }
						<div className="rp-card">
							<h3 className="rp-card__title">
								<span className="dashicons dashicons-star-filled"></span>
								{ i18n.rating || 'Bewertung' }
							</h3>
							<RatingDetailed
								applicationId={ applicationId }
								showDistribution={ true }
							/>
						</div>

						{ /* Dokumente */ }
						{ application.documents && application.documents.length > 0 && (
							<div className="rp-card">
								<h3 className="rp-card__title">
									<span className="dashicons dashicons-media-document"></span>
									{ i18n.documents || 'Dokumente' }
								</h3>
								<div className="rp-documents-list">
									{ application.documents.map( ( doc ) => (
										<div key={ doc.id } className="rp-document">
											<span className="dashicons dashicons-media-default"></span>
											<span className="rp-document__name">{ doc.filename }</span>
											<div className="rp-document__actions">
												{ doc.view_url && (
													<a
														href={ doc.view_url }
														target="_blank"
														rel="noopener noreferrer"
														className="button button-small"
													>
														{ i18n.view || 'Ansehen' }
													</a>
												) }
												{ doc.download_url && (
													<a
														href={ doc.download_url }
														className="button button-small"
														download
													>
														{ i18n.download || 'Herunterladen' }
													</a>
												) }
											</div>
										</div>
									) ) }
								</div>
							</div>
						) }

						{ /* Notizen */ }
						<div className="rp-card">
							<NotesPanel applicationId={ applicationId } />
						</div>
					</div>

					{ /* Sidebar: Timeline */ }
					<div className="rp-applicant-detail__sidebar">
						<div className="rp-card">
							<Timeline applicationId={ applicationId } />
						</div>
					</div>
				</div>
			) }

			{ /* Tab: E-Mail */ }
			{ activeTab === 'email' && canSendEmails && (
				<div className="rp-applicant-detail__email-tab">
					<EmailTab
						applicationId={ applicationId }
						recipient={ {
							email: application.email,
							name: `${ application.first_name } ${ application.last_name }`,
						} }
					/>
				</div>
			) }
		</div>
	);
}

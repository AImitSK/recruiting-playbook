/**
 * Talent Pool Card Component
 *
 * Einzelne Kandidaten-Karte im Talent-Pool
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

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
 * Tage bis Ablauf berechnen
 *
 * @param {string} expiresAt Ablaufdatum
 * @return {number|null} Tage bis Ablauf
 */
function getDaysUntilExpiry( expiresAt ) {
	if ( ! expiresAt ) {
		return null;
	}
	const now = new Date();
	const expiry = new Date( expiresAt );
	const diffTime = expiry - now;
	const diffDays = Math.ceil( diffTime / ( 1000 * 60 * 60 * 24 ) );
	return diffDays;
}

/**
 * Tags in Array konvertieren
 *
 * @param {string} tagsString Komma-separierte Tags
 * @return {string[]} Tag-Array
 */
function parseTags( tagsString ) {
	if ( ! tagsString ) {
		return [];
	}
	return tagsString
		.split( ',' )
		.map( ( tag ) => tag.trim() )
		.filter( Boolean );
}

/**
 * Talent-Pool Karten-Komponente
 *
 * @param {Object}   props           Props
 * @param {Object}   props.entry     Talent-Pool Eintrag
 * @param {Function} props.onRemoved Callback wenn entfernt
 * @return {JSX.Element} Komponente
 */
export function TalentPoolCard( { entry, onRemoved } ) {
	const [ isEditing, setIsEditing ] = useState( false );
	const [ showConfirmRemove, setShowConfirmRemove ] = useState( false );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	// Edit state
	const [ editReason, setEditReason ] = useState( entry.reason || '' );
	const [ editTags, setEditTags ] = useState( entry.tags || '' );

	const config = window.rpTalentPool || {};
	const i18n = config.i18n || {};

	const daysUntilExpiry = getDaysUntilExpiry( entry.expires_at );
	const tags = parseTags( entry.tags );

	/**
	 * Expiry-Status ermitteln
	 *
	 * @return {string} Status-Klasse
	 */
	const getExpiryStatus = () => {
		if ( daysUntilExpiry === null ) {
			return '';
		}
		if ( daysUntilExpiry <= 0 ) {
			return 'expired';
		}
		if ( daysUntilExpiry <= 30 ) {
			return 'expires-soon';
		}
		return '';
	};

	/**
	 * Expiry-Label generieren
	 *
	 * @return {string} Label
	 */
	const getExpiryLabel = () => {
		if ( daysUntilExpiry === null ) {
			return '';
		}
		if ( daysUntilExpiry <= 0 ) {
			return i18n.expired || 'Abgelaufen';
		}
		if ( daysUntilExpiry <= 30 ) {
			return i18n.expiresSoon || 'Läuft bald ab';
		}
		return '';
	};

	/**
	 * Aus Pool entfernen
	 */
	const handleRemove = async () => {
		try {
			setLoading( true );
			setError( null );

			await apiFetch( {
				path: `/recruiting/v1/talent-pool/${ entry.candidate_id }`,
				method: 'DELETE',
			} );

			setShowConfirmRemove( false );

			if ( onRemoved ) {
				onRemoved( entry.candidate_id );
			}
		} catch ( err ) {
			console.error( 'Error removing from pool:', err );
			setError( err.message || ( i18n.errorRemoving || 'Fehler beim Entfernen.' ) );
		} finally {
			setLoading( false );
		}
	};

	/**
	 * Eintrag speichern
	 */
	const handleSave = async () => {
		try {
			setLoading( true );
			setError( null );

			await apiFetch( {
				path: `/recruiting/v1/talent-pool/${ entry.candidate_id }`,
				method: 'PATCH',
				data: {
					reason: editReason,
					tags: editTags,
				},
			} );

			// Lokalen State aktualisieren
			entry.reason = editReason;
			entry.tags = editTags;

			setIsEditing( false );
		} catch ( err ) {
			console.error( 'Error saving:', err );
			setError( err.message || ( i18n.errorSaving || 'Fehler beim Speichern.' ) );
		} finally {
			setLoading( false );
		}
	};

	/**
	 * Bearbeitung abbrechen
	 */
	const handleCancel = () => {
		setEditReason( entry.reason || '' );
		setEditTags( entry.tags || '' );
		setIsEditing( false );
		setError( null );
	};

	const expiryStatus = getExpiryStatus();

	return (
		<div className={ `rp-talent-card ${ expiryStatus ? `rp-talent-card--${ expiryStatus }` : '' }` }>
			{ /* Header mit Avatar und Name */ }
			<div className="rp-talent-card__header">
				<div className="rp-talent-card__avatar">
					{ getInitials( entry.first_name, entry.last_name ) }
				</div>
				<div className="rp-talent-card__info">
					<h3 className="rp-talent-card__name">
						{ entry.first_name } { entry.last_name }
					</h3>
					{ entry.email && (
						<a href={ `mailto:${ entry.email }` } className="rp-talent-card__email">
							{ entry.email }
						</a>
					) }
				</div>
				<div className="rp-talent-card__actions">
					{ ! isEditing && (
						<button
							type="button"
							className="rp-talent-card__action-btn"
							onClick={ () => setIsEditing( true ) }
							title={ i18n.edit || 'Bearbeiten' }
						>
							<span className="dashicons dashicons-edit"></span>
						</button>
					) }
					<button
						type="button"
						className="rp-talent-card__action-btn rp-talent-card__action-btn--delete"
						onClick={ () => setShowConfirmRemove( true ) }
						title={ i18n.removeFromPool || 'Aus Pool entfernen' }
					>
						<span className="dashicons dashicons-trash"></span>
					</button>
				</div>
			</div>

			{ /* Expiry-Badge */ }
			{ expiryStatus && (
				<div className={ `rp-talent-card__expiry-badge rp-talent-card__expiry-badge--${ expiryStatus }` }>
					<span className="dashicons dashicons-warning"></span>
					{ getExpiryLabel() }
				</div>
			) }

			{ /* Inhalt */ }
			<div className="rp-talent-card__content">
				{ /* Error */ }
				{ error && (
					<div className="notice notice-error notice-alt">
						<p>{ error }</p>
					</div>
				) }

				{ isEditing ? (
					// Bearbeitungsmodus
					<>
						<div className="rp-form-field">
							<label htmlFor={ `reason-${ entry.id }` }>
								{ i18n.reason || 'Begründung' }
							</label>
							<textarea
								id={ `reason-${ entry.id }` }
								value={ editReason }
								onChange={ ( e ) => setEditReason( e.target.value ) }
								rows={ 3 }
								disabled={ loading }
							/>
						</div>

						<div className="rp-form-field">
							<label htmlFor={ `tags-${ entry.id }` }>
								{ i18n.tags || 'Tags' }
							</label>
							<input
								type="text"
								id={ `tags-${ entry.id }` }
								value={ editTags }
								onChange={ ( e ) => setEditTags( e.target.value ) }
								placeholder="php, react, senior"
								disabled={ loading }
							/>
						</div>

						<div className="rp-talent-card__edit-actions">
							<button
								type="button"
								className="button"
								onClick={ handleCancel }
								disabled={ loading }
							>
								{ i18n.cancel || 'Abbrechen' }
							</button>
							<button
								type="button"
								className="button button-primary"
								onClick={ handleSave }
								disabled={ loading }
							>
								{ loading ? (
									<span className="spinner is-active"></span>
								) : (
									i18n.save || 'Speichern'
								) }
							</button>
						</div>
					</>
				) : (
					// Anzeigemodus
					<>
						{ /* Begründung */ }
						{ entry.reason && (
							<div className="rp-talent-card__reason">
								<h4>{ i18n.reason || 'Begründung' }</h4>
								<p>{ entry.reason }</p>
							</div>
						) }

						{ /* Tags */ }
						<div className="rp-talent-card__tags">
							{ tags.length > 0 ? (
								tags.map( ( tag ) => (
									<span key={ tag } className="rp-talent-card__tag">
										{ tag }
									</span>
								) )
							) : (
								<span className="rp-talent-card__no-tags">
									{ i18n.noTags || 'Keine Tags' }
								</span>
							) }
						</div>
					</>
				) }
			</div>

			{ /* Footer */ }
			<div className="rp-talent-card__footer">
				<div className="rp-talent-card__dates">
					<div className="rp-talent-card__date">
						<span className="dashicons dashicons-calendar-alt"></span>
						<span>
							{ i18n.addedOn || 'Hinzugefügt am' }: { formatDate( entry.created_at ) }
						</span>
					</div>
					{ entry.expires_at && (
						<div className={ `rp-talent-card__date ${ expiryStatus ? `rp-talent-card__date--${ expiryStatus }` : '' }` }>
							<span className="dashicons dashicons-clock"></span>
							<span>
								{ i18n.expiresOn || 'Läuft ab am' }: { formatDate( entry.expires_at ) }
							</span>
						</div>
					) }
				</div>

				{ config.applicationUrl && entry.application_id && (
					<a
						href={ `${ config.applicationUrl }${ entry.application_id }` }
						className="button button-small"
					>
						<span className="dashicons dashicons-visibility"></span>
						{ i18n.viewApplication || 'Bewerbung anzeigen' }
					</a>
				) }
			</div>

			{ /* Entfernen-Dialog */ }
			{ showConfirmRemove && (
				<div className="rp-modal-overlay" onClick={ () => setShowConfirmRemove( false ) }>
					<div className="rp-modal rp-modal--small" onClick={ ( e ) => e.stopPropagation() }>
						<div className="rp-modal__header">
							<h3>{ i18n.removeFromPool || 'Aus Pool entfernen' }</h3>
							<button
								type="button"
								className="rp-modal__close"
								onClick={ () => setShowConfirmRemove( false ) }
							>
								<span className="dashicons dashicons-no-alt"></span>
							</button>
						</div>

						<div className="rp-modal__body">
							<p>
								{ i18n.confirmRemove ||
									'Kandidat wirklich aus dem Talent-Pool entfernen?' }
							</p>
							<p className="rp-modal__candidate-name">
								<strong>{ entry.first_name } { entry.last_name }</strong>
							</p>
							{ error && (
								<div className="notice notice-error">
									<p>{ error }</p>
								</div>
							) }
						</div>

						<div className="rp-modal__footer">
							<button
								type="button"
								className="button"
								onClick={ () => setShowConfirmRemove( false ) }
								disabled={ loading }
							>
								{ i18n.cancel || 'Abbrechen' }
							</button>
							<button
								type="button"
								className="button button-primary button-link-delete"
								onClick={ handleRemove }
								disabled={ loading }
							>
								{ loading ? (
									<span className="spinner is-active"></span>
								) : (
									i18n.removeFromPool || 'Entfernen'
								) }
							</button>
						</div>
					</div>
				</div>
			) }
		</div>
	);
}

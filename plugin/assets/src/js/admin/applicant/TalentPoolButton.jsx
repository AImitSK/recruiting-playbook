/**
 * Talent Pool Button Component
 *
 * Toggle-Button zum Hinzufügen/Entfernen aus dem Talent-Pool
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { useTalentPool } from './hooks/useTalentPool';

/**
 * Talent-Pool Button Komponente
 *
 * @param {Object}  props               Props
 * @param {number}  props.candidateId   Kandidaten-ID
 * @param {boolean} props.inPool        Initial im Pool?
 * @param {Function} props.onStatusChange Callback bei Statusänderung
 * @return {JSX.Element} Komponente
 */
export function TalentPoolButton( { candidateId, inPool = false, onStatusChange } ) {
	const [ showModal, setShowModal ] = useState( false );
	const [ reason, setReason ] = useState( '' );
	const [ tags, setTags ] = useState( '' );
	const [ showConfirmRemove, setShowConfirmRemove ] = useState( false );

	const {
		isInPool,
		loading,
		error,
		addToPool,
		removeFromPool,
	} = useTalentPool( candidateId, inPool );

	const i18n = window.rpApplicant?.i18n || {};

	/**
	 * Zum Pool hinzufügen
	 */
	const handleAdd = async () => {
		const success = await addToPool( reason, tags );
		if ( success ) {
			setShowModal( false );
			setReason( '' );
			setTags( '' );
			if ( onStatusChange ) {
				onStatusChange( true );
			}
		}
	};

	/**
	 * Aus Pool entfernen
	 */
	const handleRemove = async () => {
		const success = await removeFromPool();
		if ( success ) {
			setShowConfirmRemove( false );
			if ( onStatusChange ) {
				onStatusChange( false );
			}
		}
	};

	// Im Pool: Button zum Entfernen
	if ( isInPool ) {
		return (
			<>
				<button
					type="button"
					className="rp-talent-pool-btn is-in-pool"
					onClick={ () => setShowConfirmRemove( true ) }
					disabled={ loading }
					title={ i18n.removeFromTalentPool || 'Aus Talent-Pool entfernen' }
				>
					<span className="dashicons dashicons-groups"></span>
					{ i18n.inTalentPool || 'Im Talent-Pool' }
					{ loading && <span className="spinner is-active"></span> }
				</button>

				{ /* Bestätigungs-Dialog */ }
				{ showConfirmRemove && (
					<div className="rp-modal-overlay" onClick={ () => setShowConfirmRemove( false ) }>
						<div className="rp-modal rp-modal--small" onClick={ ( e ) => e.stopPropagation() }>
							<div className="rp-modal__header">
								<h3>{ i18n.removeFromTalentPool || 'Aus Talent-Pool entfernen' }</h3>
								<button
									type="button"
									className="rp-modal__close"
									onClick={ () => setShowConfirmRemove( false ) }
								>
									<span className="dashicons dashicons-no-alt"></span>
								</button>
							</div>

							<div className="rp-modal__body">
								<p>{ i18n.confirmRemoveFromTalentPool || 'Kandidat wirklich aus dem Talent-Pool entfernen?' }</p>
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
										i18n.remove || 'Entfernen'
									) }
								</button>
							</div>
						</div>
					</div>
				) }
			</>
		);
	}

	// Nicht im Pool: Button zum Hinzufügen
	return (
		<>
			<button
				type="button"
				className="rp-talent-pool-btn"
				onClick={ () => setShowModal( true ) }
				disabled={ loading }
				title={ i18n.addToTalentPool || 'Zum Talent-Pool hinzufügen' }
			>
				<span className="dashicons dashicons-plus-alt"></span>
				{ i18n.talentPool || 'Talent-Pool' }
			</button>

			{ /* Modal zum Hinzufügen */ }
			{ showModal && (
				<div className="rp-modal-overlay" onClick={ () => setShowModal( false ) }>
					<div className="rp-modal" onClick={ ( e ) => e.stopPropagation() }>
						<div className="rp-modal__header">
							<h3>{ i18n.addToTalentPool || 'Zum Talent-Pool hinzufügen' }</h3>
							<button
								type="button"
								className="rp-modal__close"
								onClick={ () => setShowModal( false ) }
							>
								<span className="dashicons dashicons-no-alt"></span>
							</button>
						</div>

						<div className="rp-modal__body">
							{ error && (
								<div className="notice notice-error">
									<p>{ error }</p>
								</div>
							) }

							<div className="rp-form-field">
								<label htmlFor="rp-talent-reason">
									{ i18n.reasonForAdding || 'Grund für Aufnahme' }
								</label>
								<textarea
									id="rp-talent-reason"
									value={ reason }
									onChange={ ( e ) => setReason( e.target.value ) }
									placeholder={ i18n.reasonPlaceholder || 'z.B. Sehr guter Kandidat, aber aktuell keine passende Stelle...' }
									rows={ 3 }
									disabled={ loading }
								/>
							</div>

							<div className="rp-form-field">
								<label htmlFor="rp-talent-tags">
									{ i18n.tags || 'Tags (komma-separiert)' }
								</label>
								<input
									type="text"
									id="rp-talent-tags"
									value={ tags }
									onChange={ ( e ) => setTags( e.target.value ) }
									placeholder={ i18n.tagsPlaceholder || 'z.B. php, react, senior, remote' }
									disabled={ loading }
								/>
								<p className="rp-form-hint">
									{ i18n.tagsHint || 'Tags helfen, Kandidaten später schneller zu finden.' }
								</p>
							</div>

							<div className="rp-form-field">
								<p className="rp-gdpr-notice">
									<span className="dashicons dashicons-info"></span>
									{ i18n.gdprNotice || 'Der Eintrag wird nach 24 Monaten automatisch gelöscht (DSGVO).' }
								</p>
							</div>
						</div>

						<div className="rp-modal__footer">
							<button
								type="button"
								className="button"
								onClick={ () => setShowModal( false ) }
								disabled={ loading }
							>
								{ i18n.cancel || 'Abbrechen' }
							</button>
							<button
								type="button"
								className="button button-primary"
								onClick={ handleAdd }
								disabled={ loading }
							>
								{ loading ? (
									<>
										<span className="spinner is-active"></span>
										{ i18n.adding || 'Hinzufügen...' }
									</>
								) : (
									i18n.add || 'Hinzufügen'
								) }
							</button>
						</div>
					</div>
				</div>
			) }
		</>
	);
}

/**
 * Kompakte Talent-Pool Badge für Listen
 *
 * @param {Object}  props        Props
 * @param {boolean} props.inPool Im Pool?
 * @return {JSX.Element|null} Badge oder null
 */
export function TalentPoolBadge( { inPool } ) {
	if ( ! inPool ) {
		return null;
	}

	const i18n = window.rpApplicant?.i18n || {};

	return (
		<span
			className="rp-talent-pool-badge"
			title={ i18n.inTalentPool || 'Im Talent-Pool' }
		>
			<span className="dashicons dashicons-groups"></span>
		</span>
	);
}

/**
 * Talent Pool Card Component
 *
 * Einzelne Kandidaten-Karte im Talent-Pool
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	User,
	Mail,
	Calendar,
	Clock,
	AlertTriangle,
	ExternalLink,
	Pencil,
	Trash2,
	X,
	Check,
	Tag,
} from 'lucide-react';
import { Card, CardContent } from '../components/ui/card';
import { Button } from '../components/ui/button';

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
	 * @return {Object} Status und Styles
	 */
	const getExpiryInfo = () => {
		if ( daysUntilExpiry === null ) {
			return null;
		}
		if ( daysUntilExpiry <= 0 ) {
			return {
				label: i18n.expired || __( 'Abgelaufen', 'recruiting-playbook' ),
				bgColor: '#fef2f2',
				textColor: '#dc2626',
				borderColor: '#fecaca',
			};
		}
		if ( daysUntilExpiry <= 30 ) {
			return {
				label: i18n.expiresSoon || __( 'Läuft bald ab', 'recruiting-playbook' ),
				bgColor: '#fffbeb',
				textColor: '#d97706',
				borderColor: '#fde68a',
			};
		}
		return null;
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
			setError( err.message || ( i18n.errorRemoving || __( 'Fehler beim Entfernen.', 'recruiting-playbook' ) ) );
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
			setError( err.message || ( i18n.errorSaving || __( 'Fehler beim Speichern.', 'recruiting-playbook' ) ) );
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
		setShowConfirmRemove( false );
		setError( null );
	};

	const expiryInfo = getExpiryInfo();

	return (
		<Card
			style={ {
				borderColor: expiryInfo?.borderColor || '#e5e7eb',
				backgroundColor: expiryInfo?.bgColor || '#fff',
			} }
		>
			<CardContent style={ { padding: '1rem' } }>
				{ /* Header: Avatar, Name, Primary Action */ }
				<div style={ { display: 'flex', alignItems: 'flex-start', gap: '0.75rem', marginBottom: '0.75rem' } }>
					{ /* Avatar */ }
					<div
						style={ {
							width: '48px',
							height: '48px',
							borderRadius: '50%',
							backgroundColor: '#1d71b8',
							color: '#fff',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							fontWeight: 600,
							fontSize: '1rem',
							flexShrink: 0,
						} }
					>
						{ getInitials( entry.first_name, entry.last_name ) }
					</div>

					{ /* Name und E-Mail */ }
					<div style={ { flex: 1, minWidth: 0 } }>
						<h3
							style={ {
								margin: 0,
								fontSize: '1rem',
								fontWeight: 600,
								color: '#1f2937',
								whiteSpace: 'nowrap',
								overflow: 'hidden',
								textOverflow: 'ellipsis',
							} }
						>
							{ entry.first_name } { entry.last_name }
						</h3>
						{ entry.email && (
							<a
								href={ `mailto:${ entry.email }` }
								style={ {
									display: 'flex',
									alignItems: 'center',
									gap: '0.25rem',
									fontSize: '0.875rem',
									color: '#6b7280',
									textDecoration: 'none',
									marginTop: '0.125rem',
								} }
							>
								<Mail style={ { width: '0.75rem', height: '0.75rem' } } />
								<span
									style={ {
										whiteSpace: 'nowrap',
										overflow: 'hidden',
										textOverflow: 'ellipsis',
									} }
								>
									{ entry.email }
								</span>
							</a>
						) }
					</div>

					{ /* Primary Action: View Application */ }
					{ config.applicationUrl && entry.application_id && (
						<a
							href={ `${ config.applicationUrl }${ entry.application_id }` }
							title={ i18n.viewApplication || __( 'Bewerbung anzeigen', 'recruiting-playbook' ) }
							style={ {
								display: 'inline-flex',
								alignItems: 'center',
								justifyContent: 'center',
								width: '2rem',
								height: '2rem',
								backgroundColor: '#1d71b8',
								color: '#fff',
								borderRadius: '0.375rem',
								textDecoration: 'none',
								flexShrink: 0,
							} }
						>
							<ExternalLink style={ { width: '1rem', height: '1rem' } } />
						</a>
					) }
				</div>

				{ /* Expiry Badge */ }
				{ expiryInfo && (
					<div
						style={ {
							display: 'inline-flex',
							alignItems: 'center',
							gap: '0.375rem',
							padding: '0.25rem 0.5rem',
							backgroundColor: expiryInfo.bgColor,
							border: `1px solid ${ expiryInfo.borderColor }`,
							borderRadius: '0.25rem',
							fontSize: '0.75rem',
							fontWeight: 500,
							color: expiryInfo.textColor,
							marginBottom: '0.75rem',
						} }
					>
						<AlertTriangle style={ { width: '0.75rem', height: '0.75rem' } } />
						{ expiryInfo.label }
					</div>
				) }

				{ /* Error Message */ }
				{ error && (
					<div
						style={ {
							padding: '0.5rem 0.75rem',
							backgroundColor: '#fef2f2',
							border: '1px solid #fecaca',
							borderRadius: '0.375rem',
							color: '#dc2626',
							fontSize: '0.875rem',
							marginBottom: '0.75rem',
						} }
					>
						{ error }
					</div>
				) }

				{ /* Content Area */ }
				{ showConfirmRemove ? (
					// Delete Confirmation (inline)
					<div
						style={ {
							padding: '1rem',
							backgroundColor: '#fef2f2',
							border: '1px solid #fecaca',
							borderRadius: '0.375rem',
							marginBottom: '0.75rem',
						} }
					>
						<p style={ { margin: '0 0 0.75rem 0', color: '#1f2937', fontSize: '0.875rem' } }>
							{ i18n.confirmRemove || __( 'Kandidat wirklich aus dem Talent-Pool entfernen?', 'recruiting-playbook' ) }
						</p>
						<div style={ { display: 'flex', gap: '0.5rem' } }>
							<Button
								variant="outline"
								size="sm"
								onClick={ handleCancel }
								disabled={ loading }
								style={ { flex: 1 } }
							>
								<X style={ { width: '0.875rem', height: '0.875rem', marginRight: '0.25rem' } } />
								{ i18n.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
							</Button>
							<Button
								size="sm"
								onClick={ handleRemove }
								disabled={ loading }
								style={ {
									flex: 1,
									backgroundColor: '#dc2626',
									borderColor: '#dc2626',
								} }
							>
								{ loading ? (
									<div
										style={ {
											width: '0.875rem',
											height: '0.875rem',
											border: '2px solid rgba(255,255,255,0.3)',
											borderTopColor: '#fff',
											borderRadius: '50%',
											animation: 'spin 0.8s linear infinite',
										} }
									/>
								) : (
									<>
										<Trash2 style={ { width: '0.875rem', height: '0.875rem', marginRight: '0.25rem' } } />
										{ i18n.removeFromPool || __( 'Entfernen', 'recruiting-playbook' ) }
									</>
								) }
							</Button>
						</div>
					</div>
				) : isEditing ? (
					// Edit Mode
					<div style={ { marginBottom: '0.75rem' } }>
						<div style={ { marginBottom: '0.75rem' } }>
							<label
								htmlFor={ `reason-${ entry.id }` }
								style={ {
									display: 'block',
									fontSize: '0.75rem',
									fontWeight: 500,
									color: '#374151',
									marginBottom: '0.25rem',
								} }
							>
								{ i18n.reason || __( 'Begründung', 'recruiting-playbook' ) }
							</label>
							<textarea
								id={ `reason-${ entry.id }` }
								value={ editReason }
								onChange={ ( e ) => setEditReason( e.target.value ) }
								rows={ 2 }
								disabled={ loading }
								style={ {
									width: '100%',
									padding: '0.5rem',
									border: '1px solid #e5e7eb',
									borderRadius: '0.375rem',
									fontSize: '0.875rem',
									resize: 'vertical',
									minHeight: '60px',
								} }
							/>
						</div>

						<div style={ { marginBottom: '0.75rem' } }>
							<label
								htmlFor={ `tags-${ entry.id }` }
								style={ {
									display: 'block',
									fontSize: '0.75rem',
									fontWeight: 500,
									color: '#374151',
									marginBottom: '0.25rem',
								} }
							>
								{ i18n.tags || __( 'Tags', 'recruiting-playbook' ) }
							</label>
							<input
								type="text"
								id={ `tags-${ entry.id }` }
								value={ editTags }
								onChange={ ( e ) => setEditTags( e.target.value ) }
								placeholder="php, react, senior"
								disabled={ loading }
								style={ {
									width: '100%',
									padding: '0.5rem',
									border: '1px solid #e5e7eb',
									borderRadius: '0.375rem',
									fontSize: '0.875rem',
								} }
							/>
						</div>

						<div style={ { display: 'flex', gap: '0.5rem' } }>
							<Button
								variant="outline"
								size="sm"
								onClick={ handleCancel }
								disabled={ loading }
								style={ { flex: 1 } }
							>
								<X style={ { width: '0.875rem', height: '0.875rem', marginRight: '0.25rem' } } />
								{ i18n.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
							</Button>
							<Button
								size="sm"
								onClick={ handleSave }
								disabled={ loading }
								style={ { flex: 1 } }
							>
								{ loading ? (
									<div
										style={ {
											width: '0.875rem',
											height: '0.875rem',
											border: '2px solid rgba(255,255,255,0.3)',
											borderTopColor: '#fff',
											borderRadius: '50%',
											animation: 'spin 0.8s linear infinite',
										} }
									/>
								) : (
									<>
										<Check style={ { width: '0.875rem', height: '0.875rem', marginRight: '0.25rem' } } />
										{ i18n.save || __( 'Speichern', 'recruiting-playbook' ) }
									</>
								) }
							</Button>
						</div>
					</div>
				) : (
					// View Mode
					<>
						{ /* Reason */ }
						{ entry.reason && (
							<p
								style={ {
									margin: '0 0 0.75rem 0',
									fontSize: '0.875rem',
									color: '#4b5563',
									lineHeight: 1.5,
								} }
							>
								{ entry.reason }
							</p>
						) }

						{ /* Tags */ }
						<div style={ { display: 'flex', flexWrap: 'wrap', gap: '0.375rem', marginBottom: '0.75rem' } }>
							{ tags.length > 0 ? (
								tags.map( ( tag ) => (
									<span
										key={ tag }
										style={ {
											display: 'inline-flex',
											alignItems: 'center',
											gap: '0.25rem',
											padding: '0.125rem 0.5rem',
											backgroundColor: '#f3f4f6',
											borderRadius: '9999px',
											fontSize: '0.75rem',
											color: '#4b5563',
										} }
									>
										<Tag style={ { width: '0.625rem', height: '0.625rem' } } />
										{ tag }
									</span>
								) )
							) : (
								<span style={ { fontSize: '0.75rem', color: '#9ca3af', fontStyle: 'italic' } }>
									{ i18n.noTags || __( 'Keine Tags', 'recruiting-playbook' ) }
								</span>
							) }
						</div>
					</>
				) }

				{ /* Footer: Dates and Actions */ }
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						paddingTop: '0.75rem',
						borderTop: '1px solid #e5e7eb',
						gap: '0.5rem',
						flexWrap: 'wrap',
					} }
				>
					{ /* Dates */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.25rem' } }>
						<span
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '0.375rem',
								fontSize: '0.75rem',
								color: '#6b7280',
							} }
						>
							<Calendar style={ { width: '0.75rem', height: '0.75rem' } } />
							{ formatDate( entry.created_at ) }
						</span>
						{ entry.expires_at && (
							<span
								style={ {
									display: 'flex',
									alignItems: 'center',
									gap: '0.375rem',
									fontSize: '0.75rem',
									color: expiryInfo?.textColor || '#6b7280',
								} }
							>
								<Clock style={ { width: '0.75rem', height: '0.75rem' } } />
								{ formatDate( entry.expires_at ) }
							</span>
						) }
					</div>

					{ /* Action Buttons */ }
					{ ! isEditing && ! showConfirmRemove && (
						<div style={ { display: 'flex', gap: '0.25rem' } }>
							<button
								type="button"
								onClick={ () => setIsEditing( true ) }
								title={ i18n.edit || __( 'Bearbeiten', 'recruiting-playbook' ) }
								style={ {
									padding: '0.375rem',
									background: 'none',
									border: '1px solid #e5e7eb',
									borderRadius: '0.375rem',
									cursor: 'pointer',
									color: '#6b7280',
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
								} }
							>
								<Pencil style={ { width: '0.875rem', height: '0.875rem' } } />
							</button>
							<button
								type="button"
								onClick={ () => setShowConfirmRemove( true ) }
								title={ i18n.removeFromPool || __( 'Aus Pool entfernen', 'recruiting-playbook' ) }
								style={ {
									padding: '0.375rem',
									background: 'none',
									border: '1px solid #fecaca',
									borderRadius: '0.375rem',
									cursor: 'pointer',
									color: '#dc2626',
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
								} }
							>
								<Trash2 style={ { width: '0.875rem', height: '0.875rem' } } />
							</button>
						</div>
					) }
				</div>
			</CardContent>

			<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
		</Card>
	);
}

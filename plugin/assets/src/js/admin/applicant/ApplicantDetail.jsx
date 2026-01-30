/**
 * Applicant Detail Component
 *
 * Hauptkomponente für die Bewerber-Detailseite
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import {
	ArrowLeft,
	Mail,
	Phone,
	Briefcase,
	Calendar,
	FileText,
	Download,
	Eye,
	MessageSquare,
	Clock,
	Users,
	Copy,
} from 'lucide-react';
import {
	Card,
	CardContent,
	CardHeader,
	CardTitle,
} from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '../components/ui/tabs';
import { Spinner } from '../components/ui/spinner';
import { NotesPanel } from './NotesPanel';
import { RatingDetailed } from './RatingStars';
import { Timeline } from './Timeline';
import { TalentPoolButton } from './TalentPoolButton';
import { EmailTab } from './EmailTab';
import { CustomFieldsPanel } from './CustomFieldsPanel';

/**
 * Status-Konfiguration mit Farben
 */
const STATUS_CONFIG = {
	new: { label: 'Neu', color: '#2271b1', bg: '#e6f3ff' },
	screening: { label: 'In Prüfung', color: '#dba617', bg: '#fff8e6' },
	interview: { label: 'Interview', color: '#9b59b6', bg: '#f5e6ff' },
	offer: { label: 'Angebot', color: '#1e8cbe', bg: '#e6f5ff' },
	hired: { label: 'Eingestellt', color: '#2fac66', bg: '#e6f5ec' },
	rejected: { label: 'Abgelehnt', color: '#d63638', bg: '#ffe6e6' },
	withdrawn: { label: 'Zurückgezogen', color: '#787c82', bg: '#f0f0f0' },
};

const STATUS_OPTIONS = Object.entries( STATUS_CONFIG ).map( ( [ value, config ] ) => ( {
	value,
	label: config.label,
	color: config.color,
} ) );

function getInitials( firstName, lastName ) {
	const first = firstName?.charAt( 0 )?.toUpperCase() || '';
	const last = lastName?.charAt( 0 )?.toUpperCase() || '';
	return `${ first }${ last }` || '?';
}

function formatDate( dateString ) {
	if ( ! dateString ) return '-';
	return new Date( dateString ).toLocaleDateString( 'de-DE', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
	} );
}

function copyToClipboard( text ) {
	navigator.clipboard.writeText( text );
}

/**
 * Bewerber-Detailseite Komponente
 */
export function ApplicantDetail( { applicationId } ) {
	const [ application, setApplication ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ statusChanging, setStatusChanging ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( 'details' );
	const [ notesCount, setNotesCount ] = useState( 0 );
	const [ timelineCount, setTimelineCount ] = useState( 0 );
	const [ emailsCount, setEmailsCount ] = useState( 0 );

	const config = window.rpApplicant || {};
	const canSendEmails = config.canSendEmails !== false;
	const logoUrl = config.logoUrl || '';

	const loadApplication = useCallback( async () => {
		if ( ! applicationId ) return;

		try {
			setLoading( true );
			setError( null );
			const data = await apiFetch( {
				path: `/recruiting/v1/applications/${ applicationId }`,
			} );

			// Flatten candidate data for easier access
			const candidate = data.candidate || {};
			const flatData = {
				...data,
				first_name: candidate.first_name || '',
				last_name: candidate.last_name || '',
				email: candidate.email || '',
				phone: candidate.phone || '',
				salutation: candidate.salutation || '',
				job_title: data.job?.title || '',
			};

			setApplication( flatData );
		} catch ( err ) {
			console.error( 'Error loading application:', err );
			setError( err.message || __( 'Fehler beim Laden der Bewerbung', 'recruiting-playbook' ) );
		} finally {
			setLoading( false );
		}
	}, [ applicationId ] );

	useEffect( () => {
		loadApplication();
	}, [ loadApplication ] );

	// Fetch tab counts
	useEffect( () => {
		if ( ! applicationId ) return;

		// Fetch notes count (returns array directly)
		apiFetch( { path: `/recruiting/v1/applications/${ applicationId }/notes` } )
			.then( ( data ) => {
				setNotesCount( Array.isArray( data ) ? data.length : 0 );
			} )
			.catch( () => {} );

		// Fetch timeline count
		apiFetch( { path: `/recruiting/v1/applications/${ applicationId }/timeline?per_page=1`, parse: false } )
			.then( ( response ) => {
				const total = parseInt( response.headers.get( 'X-WP-Total' ) || '0', 10 );
				setTimelineCount( total );
			} )
			.catch( () => {} );

		// Fetch emails count
		apiFetch( { path: `/recruiting/v1/applications/${ applicationId }/emails?per_page=1` } )
			.then( ( data ) => {
				setEmailsCount( data.total || 0 );
			} )
			.catch( () => {} );
	}, [ applicationId ] );

	const handleStatusChange = async ( newStatus ) => {
		if ( ! application || statusChanging ) return;

		const previousStatus = application.status;

		try {
			setStatusChanging( true );
			setApplication( ( prev ) => ( { ...prev, status: newStatus } ) );

			await apiFetch( {
				path: `/recruiting/v1/applications/${ applicationId }/status`,
				method: 'PATCH',
				data: { status: newStatus },
			} );
		} catch ( err ) {
			console.error( 'Error changing status:', err );
			setApplication( ( prev ) => ( { ...prev, status: previousStatus } ) );
			alert( err.message || __( 'Fehler beim Ändern des Status', 'recruiting-playbook' ) );
		} finally {
			setStatusChanging( false );
		}
	};

	const handleTalentPoolChange = ( inPool ) => {
		setApplication( ( prev ) => ( { ...prev, in_talent_pool: inPool } ) );
	};

	// Loading
	if ( loading ) {
		return (
			<div className="rp-admin" style={ { padding: '20px 0' } }>
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '300px', gap: '0.75rem', color: '#6b7280' } }>
					<Spinner size="default" />
					<span>{ __( 'Lade Bewerbung...', 'recruiting-playbook' ) }</span>
				</div>
			</div>
		);
	}

	// Error
	if ( error ) {
		return (
			<div className="rp-admin" style={ { padding: '20px 0' } }>
				<Card>
					<CardContent style={ { padding: '3rem', textAlign: 'center' } }>
						<p style={ { color: '#d63638', marginBottom: '1.5rem' } }>{ error }</p>
						<Button onClick={ loadApplication }>
							{ __( 'Erneut versuchen', 'recruiting-playbook' ) }
						</Button>
					</CardContent>
				</Card>
			</div>
		);
	}

	if ( ! application ) {
		return (
			<div className="rp-admin" style={ { padding: '20px 0' } }>
				<Card>
					<CardContent style={ { padding: '3rem', textAlign: 'center', color: '#6b7280' } }>
						{ __( 'Bewerbung nicht gefunden.', 'recruiting-playbook' ) }
					</CardContent>
				</Card>
			</div>
		);
	}

	const currentStatus = STATUS_CONFIG[ application.status ];
	const documentsCount = application.documents?.length || 0;

	return (
		<div className="rp-admin" style={ { padding: '20px 0' } }>
			<div style={ { maxWidth: '1400px' } }>
				{ /* Header: Logo links, Titel rechts */ }
				<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '1.5rem' } }>
					{ logoUrl && (
						<img src={ logoUrl } alt="Recruiting Playbook" style={ { width: '150px', height: 'auto' } } />
					) }
					<h1 style={ { margin: 0, fontSize: '1.5rem', fontWeight: 700, color: '#1f2937' } }>
						{ __( 'Bewerbung', 'recruiting-playbook' ) } #{ applicationId }
					</h1>
				</div>

				{ /* Navigation */ }
				<div style={ { display: 'flex', alignItems: 'center', gap: '1rem', marginBottom: '1.5rem', flexWrap: 'wrap' } }>
					<a
						href={ config.listUrl || '#' }
						style={ { display: 'inline-flex', alignItems: 'center', gap: '0.25rem', color: '#1d71b8', textDecoration: 'none', fontSize: '0.875rem' } }
					>
						<ArrowLeft style={ { width: '1rem', height: '1rem' } } />
						{ __( 'Zurück zur Liste', 'recruiting-playbook' ) }
					</a>

					<div style={ { flex: 1 } } />

					<TalentPoolButton
						candidateId={ application.candidate_id }
						inPool={ application.in_talent_pool }
						onStatusChange={ handleTalentPoolChange }
					/>
				</div>

				{ /* Tabs - shadcn/ui Style */ }
				<Tabs value={ activeTab } onValueChange={ setActiveTab } style={ { marginBottom: '1.5rem' } }>
					<TabsList>
						<TabsTrigger value="details">
							{ __( 'Details', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="documents" count={ documentsCount }>
							{ __( 'Dokumente', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="notes" count={ notesCount }>
							{ __( 'Notizen', 'recruiting-playbook' ) }
						</TabsTrigger>
						<TabsTrigger value="timeline" count={ timelineCount }>
							{ __( 'Verlauf', 'recruiting-playbook' ) }
						</TabsTrigger>
						{ canSendEmails && (
							<TabsTrigger value="email" count={ emailsCount }>
								{ __( 'E-Mail', 'recruiting-playbook' ) }
							</TabsTrigger>
						) }
					</TabsList>
				</Tabs>

				{ /* Tab Content */ }
				<div style={ { display: 'grid', gridTemplateColumns: '1fr 380px', gap: '1.5rem' } }>
					{ /* Main Content */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
						{ /* Kandidaten-Info Card - kompakt */ }
						<Card>
							<CardContent style={ { padding: '1rem 1.5rem' } }>
								<div style={ { display: 'flex', gap: '1rem', alignItems: 'center' } }>
									{ /* Avatar */ }
									<div
										style={ {
											width: '56px',
											height: '56px',
											borderRadius: '50%',
											backgroundColor: '#1d71b8',
											color: '#fff',
											display: 'flex',
											alignItems: 'center',
											justifyContent: 'center',
											fontSize: '1.25rem',
											fontWeight: 500,
											flexShrink: 0,
										} }
									>
										{ getInitials( application.first_name, application.last_name ) }
									</div>

									{ /* Name und Meta */ }
									<div style={ { flex: 1, minWidth: 0 } }>
										<h2 style={ { margin: '0 0 0.25rem 0', fontSize: '1.125rem', fontWeight: 600, color: '#1f2937' } }>
											{ application.first_name } { application.last_name }
										</h2>
										<div style={ { display: 'flex', flexWrap: 'wrap', gap: '1rem', color: '#6b7280', fontSize: '0.8125rem' } }>
											{ application.job_title && (
												<span style={ { display: 'inline-flex', alignItems: 'center', gap: '0.375rem' } }>
													<Briefcase style={ { width: '0.875rem', height: '0.875rem' } } />
													{ application.job_title }
												</span>
											) }
											<span style={ { display: 'inline-flex', alignItems: 'center', gap: '0.375rem' } }>
												<Calendar style={ { width: '0.875rem', height: '0.875rem' } } />
												{ __( 'Beworben am', 'recruiting-playbook' ) }: { formatDate( application.created_at ) }
											</span>
										</div>
									</div>

									{ /* Status rechts */ }
									<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', flexShrink: 0 } }>
										<select
											id="rp-status-select"
											value={ application.status }
											onChange={ ( e ) => handleStatusChange( e.target.value ) }
											disabled={ statusChanging }
											style={ {
												padding: '0.5rem 2rem 0.5rem 0.75rem',
												border: `2px solid ${ currentStatus?.color || '#e5e7eb' }`,
												borderRadius: '0.375rem',
												fontSize: '0.875rem',
												backgroundColor: currentStatus?.bg || '#fff',
												color: currentStatus?.color || '#1f2937',
												fontWeight: 500,
												cursor: statusChanging ? 'not-allowed' : 'pointer',
												minWidth: '160px',
											} }
										>
											{ STATUS_OPTIONS.map( ( option ) => (
												<option key={ option.value } value={ option.value }>{ option.label }</option>
											) ) }
										</select>
										{ statusChanging && <Spinner size="sm" /> }
									</div>
								</div>
							</CardContent>
						</Card>

						{ /* Tab: Details */ }
						{ activeTab === 'details' && (
							<>
								{ /* Kandidaten-Details - 2-spaltig */ }
								<Card>
									<CardHeader style={ { paddingBottom: 0 } }>
										<CardTitle>{ __( 'Kandidaten-Details', 'recruiting-playbook' ) }</CardTitle>
									</CardHeader>
									<CardContent style={ { padding: '1rem 1.5rem' } }>
										<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem 2rem' } }>
											{ /* Zeile 1 */ }
											<div style={ { display: 'flex', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
												<span style={ { color: '#6b7280', fontSize: '0.875rem', width: '100px', flexShrink: 0 } }>{ __( 'Name', 'recruiting-playbook' ) }</span>
												<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>{ application.first_name } { application.last_name }</span>
											</div>
											<div style={ { display: 'flex', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
												<span style={ { color: '#6b7280', fontSize: '0.875rem', width: '100px', flexShrink: 0 } }>{ __( 'Anrede', 'recruiting-playbook' ) }</span>
												<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>{ application.salutation || '-' }</span>
											</div>
											{ /* Zeile 2 */ }
											<div style={ { display: 'flex', alignItems: 'center', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
												<span style={ { color: '#6b7280', fontSize: '0.875rem', width: '100px', flexShrink: 0 } }>{ __( 'E-Mail', 'recruiting-playbook' ) }</span>
												{ application.email ? (
													<>
														<a href={ `mailto:${ application.email }` } style={ { color: '#1d71b8', fontSize: '0.875rem', textDecoration: 'none' } }>{ application.email }</a>
														<button
															type="button"
															onClick={ () => copyToClipboard( application.email ) }
															title={ __( 'Kopieren', 'recruiting-playbook' ) }
															style={ { marginLeft: '0.5rem', padding: '0.25rem', background: 'none', border: 'none', cursor: 'pointer', color: '#9ca3af', borderRadius: '0.25rem' } }
															onMouseEnter={ ( e ) => e.currentTarget.style.color = '#6b7280' }
															onMouseLeave={ ( e ) => e.currentTarget.style.color = '#9ca3af' }
														>
															<Copy style={ { width: '0.875rem', height: '0.875rem' } } />
														</button>
													</>
												) : <span style={ { color: '#1f2937', fontSize: '0.875rem' } }>-</span> }
											</div>
											<div style={ { display: 'flex', alignItems: 'center', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
												<span style={ { color: '#6b7280', fontSize: '0.875rem', width: '100px', flexShrink: 0 } }>{ __( 'Telefon', 'recruiting-playbook' ) }</span>
												{ application.phone ? (
													<>
														<a href={ `tel:${ application.phone }` } style={ { color: '#1d71b8', fontSize: '0.875rem', textDecoration: 'none' } }>{ application.phone }</a>
														<button
															type="button"
															onClick={ () => copyToClipboard( application.phone ) }
															title={ __( 'Kopieren', 'recruiting-playbook' ) }
															style={ { marginLeft: '0.5rem', padding: '0.25rem', background: 'none', border: 'none', cursor: 'pointer', color: '#9ca3af', borderRadius: '0.25rem' } }
															onMouseEnter={ ( e ) => e.currentTarget.style.color = '#6b7280' }
															onMouseLeave={ ( e ) => e.currentTarget.style.color = '#9ca3af' }
														>
															<Copy style={ { width: '0.875rem', height: '0.875rem' } } />
														</button>
													</>
												) : <span style={ { color: '#1f2937', fontSize: '0.875rem' } }>-</span> }
											</div>
											{ /* Zeile 3 */ }
											<div style={ { display: 'flex', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
												<span style={ { color: '#6b7280', fontSize: '0.875rem', width: '100px', flexShrink: 0 } }>{ __( 'Stelle', 'recruiting-playbook' ) }</span>
												<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>{ application.job_title || '-' }</span>
											</div>
											<div style={ { display: 'flex', padding: '0.5rem 0', borderBottom: '1px solid #f3f4f6' } }>
												<span style={ { color: '#6b7280', fontSize: '0.875rem', width: '100px', flexShrink: 0 } }>{ __( 'Beworben am', 'recruiting-playbook' ) }</span>
												<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>{ formatDate( application.created_at ) }</span>
											</div>
											{ /* Zeile 4 */ }
											<div style={ { display: 'flex', padding: '0.5rem 0' } }>
												<span style={ { color: '#6b7280', fontSize: '0.875rem', width: '100px', flexShrink: 0 } }>{ __( 'Quelle', 'recruiting-playbook' ) }</span>
												<span style={ { color: '#1f2937', fontSize: '0.875rem' } }>{ application.source || 'Website' }</span>
											</div>
										</div>

										{ /* Anschreiben */ }
										{ application.cover_letter && (
											<div style={ { marginTop: '1.5rem', paddingTop: '1rem', borderTop: '1px solid #e5e7eb' } }>
												<div style={ { fontSize: '0.875rem', fontWeight: 500, color: '#1f2937', marginBottom: '0.75rem' } }>
													{ __( 'Anschreiben', 'recruiting-playbook' ) }
												</div>
												<div
													style={ {
														fontSize: '0.875rem',
														color: '#374151',
														lineHeight: 1.6,
														whiteSpace: 'pre-wrap',
													} }
													dangerouslySetInnerHTML={ { __html: application.cover_letter } }
												/>
											</div>
										) }
									</CardContent>
								</Card>

								{ /* Custom Fields (Pro) */ }
								{ application.custom_fields && application.custom_fields.length > 0 && (
									<CustomFieldsPanel customFields={ application.custom_fields } />
								) }

								{ /* Bewertung */ }
								<Card>
									<CardHeader>
										<CardTitle>{ __( 'Bewertung', 'recruiting-playbook' ) }</CardTitle>
									</CardHeader>
									<CardContent>
										<RatingDetailed applicationId={ applicationId } showDistribution={ true } />
									</CardContent>
								</Card>
							</>
						) }

						{ /* Tab: Dokumente */ }
						{ activeTab === 'documents' && (
							<Card>
								<CardHeader>
									<CardTitle>{ __( 'Dokumente', 'recruiting-playbook' ) } ({ documentsCount })</CardTitle>
								</CardHeader>
								<CardContent>
									{ documentsCount === 0 ? (
										<div style={ { textAlign: 'center', padding: '2rem', color: '#6b7280' } }>
											<FileText style={ { width: '3rem', height: '3rem', marginBottom: '0.75rem', opacity: 0.5 } } />
											<p>{ __( 'Keine Dokumente vorhanden', 'recruiting-playbook' ) }</p>
										</div>
									) : (
										<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
											{ application.documents.map( ( doc ) => (
												<div
													key={ doc.id }
													style={ {
														display: 'flex',
														alignItems: 'center',
														gap: '0.75rem',
														padding: '0.75rem 1rem',
														backgroundColor: '#f9fafb',
														borderRadius: '0.375rem',
													} }
												>
													<FileText style={ { width: '1.25rem', height: '1.25rem', color: '#6b7280', flexShrink: 0 } } />
													<span style={ { flex: 1, fontSize: '0.875rem', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }>
														{ doc.filename }
													</span>
													<div style={ { display: 'flex', gap: '0.5rem' } }>
														{ doc.view_url && (
															<Button variant="outline" size="sm" asChild>
																<a href={ doc.view_url } target="_blank" rel="noopener noreferrer">
																	<Eye style={ { width: '0.875rem', height: '0.875rem', marginRight: '0.25rem' } } />
																	{ __( 'Ansehen', 'recruiting-playbook' ) }
																</a>
															</Button>
														) }
														{ doc.download_url && (
															<Button size="sm" asChild>
																<a href={ doc.download_url } download>
																	<Download style={ { width: '0.875rem', height: '0.875rem', marginRight: '0.25rem' } } />
																	{ __( 'Download', 'recruiting-playbook' ) }
																</a>
															</Button>
														) }
													</div>
												</div>
											) ) }
										</div>
									) }
								</CardContent>
							</Card>
						) }

						{ /* Tab: Notizen */ }
						{ activeTab === 'notes' && (
							<Card>
								<CardHeader>
									<CardTitle>{ __( 'Notizen', 'recruiting-playbook' ) }</CardTitle>
								</CardHeader>
								<CardContent>
									<NotesPanel applicationId={ applicationId } showHeader={ false } />
								</CardContent>
							</Card>
						) }

						{ /* Tab: Timeline */ }
						{ activeTab === 'timeline' && (
							<Card>
								<CardHeader>
									<CardTitle>{ __( 'Verlauf', 'recruiting-playbook' ) }</CardTitle>
								</CardHeader>
								<CardContent>
									<Timeline applicationId={ applicationId } showHeader={ false } />
								</CardContent>
							</Card>
						) }

						{ /* Tab: E-Mail */ }
						{ activeTab === 'email' && canSendEmails && (
							<Card>
								<CardHeader>
									<CardTitle>{ __( 'E-Mail', 'recruiting-playbook' ) }</CardTitle>
								</CardHeader>
								<CardContent>
									<EmailTab
										applicationId={ applicationId }
										recipient={ {
											email: application.email,
											name: `${ application.first_name } ${ application.last_name }`,
										} }
									/>
								</CardContent>
							</Card>
						) }
					</div>

					{ /* Sidebar */ }
					<div>
						<Card>
							<CardHeader>
								<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
									<Clock style={ { width: '1rem', height: '1rem' } } />
									{ __( 'Aktivität', 'recruiting-playbook' ) }
								</CardTitle>
							</CardHeader>
							<CardContent>
								<Timeline applicationId={ applicationId } compact maxItems={ 10 } />
							</CardContent>
						</Card>
					</div>
				</div>
			</div>

			<style>{ `
				@media (max-width: 1200px) {
					.rp-admin [style*="grid-template-columns: 1fr 380px"] {
						grid-template-columns: 1fr !important;
					}
				}
			` }</style>
		</div>
	);
}

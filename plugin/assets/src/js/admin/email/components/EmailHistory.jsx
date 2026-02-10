/**
 * EmailHistory - Liste der versendeten E-Mails (shadcn/ui Dashboard Design)
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import PropTypes from 'prop-types';
import {
	Eye,
	RefreshCw,
	X,
	ChevronLeft,
	ChevronRight,
	Mail,
	AlertCircle,
	MoreHorizontal,
} from 'lucide-react';
import DOMPurify from 'dompurify';
import {
	Card,
	CardContent,
	CardHeader,
	CardTitle,
	CardDescription,
} from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Select, SelectOption } from '../../components/ui/select';
import { Spinner } from '../../components/ui/spinner';
import {
	AlertDialog,
	AlertDialogAction,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
} from '../../components/ui/alert-dialog';

// DOMPurify-Konfiguration: Nur sichere Tags für E-Mail-Inhalte
const DOMPURIFY_CONFIG = {
	ALLOWED_TAGS: [ 'p', 'br', 'strong', 'em', 'b', 'i', 'a', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'div', 'span' ],
	ALLOWED_ATTR: [ 'href', 'target', 'rel', 'class', 'style' ],
	FORBID_TAGS: [ 'script', 'iframe', 'object', 'embed', 'form', 'input' ],
};

/**
 * Status Badge Komponente - shadcn/ui style
 */
function StatusBadge( { status } ) {
	const config = {
		sent: { label: __( 'Gesendet', 'recruiting-playbook' ), variant: 'success' },
		failed: { label: __( 'Fehlgeschlagen', 'recruiting-playbook' ), variant: 'destructive' },
		pending: { label: __( 'Ausstehend', 'recruiting-playbook' ), variant: 'warning' },
		scheduled: { label: __( 'Geplant', 'recruiting-playbook' ), variant: 'secondary' },
		cancelled: { label: __( 'Storniert', 'recruiting-playbook' ), variant: 'outline' },
	};

	const statusConfig = config[ status ] || config.pending;

	const variantStyles = {
		success: { backgroundColor: '#dcfce7', color: '#166534', border: '1px solid #bbf7d0' },
		destructive: { backgroundColor: '#fef2f2', color: '#dc2626', border: '1px solid #fecaca' },
		warning: { backgroundColor: '#fefce8', color: '#ca8a04', border: '1px solid #fef08a' },
		secondary: { backgroundColor: '#f4f4f5', color: '#3f3f46', border: '1px solid #e4e4e7' },
		outline: { backgroundColor: 'transparent', color: '#71717a', border: '1px solid #e4e4e7' },
	};

	const style = variantStyles[ statusConfig.variant ] || variantStyles.secondary;

	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				padding: '0.25rem 0.625rem',
				borderRadius: '9999px',
				fontSize: '0.75rem',
				fontWeight: 500,
				lineHeight: 1,
				...style,
			} }
		>
			{ statusConfig.label }
		</span>
	);
}

/**
 * Modal Komponente - shadcn/ui style
 */
function Modal( { title, children, onClose } ) {
	return (
		<div
			style={ {
				position: 'fixed',
				top: 0,
				left: 0,
				right: 0,
				bottom: 0,
				backgroundColor: 'rgba(0, 0, 0, 0.5)',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				zIndex: 100000,
				padding: '1rem',
			} }
			onClick={ onClose }
		>
			<div
				style={ {
					backgroundColor: '#fff',
					borderRadius: '0.75rem',
					boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
					maxWidth: '700px',
					width: '100%',
					maxHeight: '90vh',
					overflow: 'hidden',
					display: 'flex',
					flexDirection: 'column',
				} }
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						padding: '1.25rem 1.5rem',
						borderBottom: '1px solid #e5e7eb',
					} }
				>
					<h3 style={ { margin: 0, fontSize: '1.125rem', fontWeight: 600, color: '#18181b' } }>
						{ title }
					</h3>
					<Button
						variant="ghost"
						size="icon"
						onClick={ onClose }
					>
						<X style={ { width: '1.25rem', height: '1.25rem' } } />
					</Button>
				</div>
				<div style={ { overflow: 'auto', flex: 1 } }>
					{ children }
				</div>
			</div>
		</div>
	);
}

/**
 * EmailHistory Komponente
 */
export function EmailHistory( {
	emails = [],
	loading = false,
	error = null,
	pagination = {},
	onResend,
	onCancel,
	onPageChange,
	action = null, // Button/Action für Header (z.B. "Neue E-Mail")
} ) {
	const [ statusFilter, setStatusFilter ] = useState( '' );
	const [ viewingEmail, setViewingEmail ] = useState( null );
	const [ viewingLoading, setViewingLoading ] = useState( false );
	const [ confirmResend, setConfirmResend ] = useState( null );
	const [ confirmCancel, setConfirmCancel ] = useState( null );

	/**
	 * E-Mail-Details laden und Modal öffnen
	 */
	const handleViewEmail = useCallback( async ( email ) => {
		setViewingLoading( true );
		setViewingEmail( email );

		try {
			const fullEmail = await apiFetch( {
				path: `/recruiting/v1/emails/log/${ email.id }`,
			} );
			setViewingEmail( fullEmail );
		} catch ( err ) {
			console.error( 'Error fetching email details:', err );
		} finally {
			setViewingLoading( false );
		}
	}, [] );

	// Gefilterte E-Mails
	const filteredEmails = useMemo( () => {
		if ( ! statusFilter ) {
			return emails;
		}
		return emails.filter( ( email ) => email.status === statusFilter );
	}, [ emails, statusFilter ] );

	// Status-Optionen
	const statusOptions = [
		{ value: '', label: __( 'Alle Status', 'recruiting-playbook' ) },
		{ value: 'sent', label: __( 'Gesendet', 'recruiting-playbook' ) },
		{ value: 'failed', label: __( 'Fehlgeschlagen', 'recruiting-playbook' ) },
		{ value: 'pending', label: __( 'Ausstehend', 'recruiting-playbook' ) },
		{ value: 'scheduled', label: __( 'Geplant', 'recruiting-playbook' ) },
		{ value: 'cancelled', label: __( 'Storniert', 'recruiting-playbook' ) },
	];

	/**
	 * Datum formatieren
	 */
	const formatDate = ( date ) => {
		if ( ! date ) {
			return '-';
		}
		return new Date( date ).toLocaleString( 'de-DE', {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
		} );
	};

	/**
	 * Erneut senden bestätigen
	 */
	const handleResendConfirm = () => {
		if ( confirmResend && onResend ) {
			onResend( confirmResend.id );
		}
		setConfirmResend( null );
	};

	/**
	 * Stornieren bestätigen
	 */
	const handleCancelConfirm = () => {
		if ( confirmCancel && onCancel ) {
			onCancel( confirmCancel.id );
		}
		setConfirmCancel( null );
	};

	if ( loading ) {
		return (
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '4rem', gap: '0.75rem', color: '#71717a' } }>
				<Spinner size="default" />
				<span>{ __( 'Lade E-Mail-Verlauf...', 'recruiting-playbook' ) }</span>
			</div>
		);
	}

	return (
		<div>
			{ error && (
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						gap: '0.75rem',
						padding: '1rem',
						backgroundColor: '#fef2f2',
						border: '1px solid #fecaca',
						borderRadius: '0.5rem',
						marginBottom: '1.5rem',
						color: '#dc2626',
						fontSize: '0.875rem',
					} }
				>
					<AlertCircle style={ { width: '1.25rem', height: '1.25rem', flexShrink: 0 } } />
					{ error }
				</div>
			) }

			<Card style={ { border: '1px solid #e5e7eb', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)' } }>
				<CardHeader style={ { padding: '20px 24px 16px 24px' } }>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '1rem' } }>
						<div style={ { display: 'flex', alignItems: 'center', gap: '1rem' } }>
							<div>
								<CardTitle style={ { fontSize: '1.125rem', fontWeight: 600, color: '#18181b', margin: 0 } }>
									{ __( 'E-Mail Verlauf', 'recruiting-playbook' ) }
								</CardTitle>
								<CardDescription style={ { marginTop: '4px', fontSize: '0.875rem', color: '#71717a' } }>
									{ emails.length > 0
										? `${ emails.length } ${ emails.length === 1 ? __( 'E-Mail', 'recruiting-playbook' ) : __( 'E-Mails', 'recruiting-playbook' ) }`
										: __( 'Keine E-Mails vorhanden', 'recruiting-playbook' )
									}
								</CardDescription>
							</div>
						</div>
						<div style={ { display: 'flex', alignItems: 'center', gap: '12px' } }>
							<Select
								value={ statusFilter }
								onChange={ ( e ) => setStatusFilter( e.target.value ) }
								style={ { minWidth: '160px' } }
							>
								{ statusOptions.map( ( option ) => (
									<SelectOption key={ option.value } value={ option.value }>
										{ option.label }
									</SelectOption>
								) ) }
							</Select>
							{ action }
						</div>
					</div>
				</CardHeader>

				<CardContent style={ { padding: 0 } }>
					{ filteredEmails.length === 0 ? (
						<div style={ { textAlign: 'center', padding: '4rem 2rem', color: '#71717a' } }>
							<Mail style={ { width: '3rem', height: '3rem', margin: '0 auto 1rem', opacity: 0.3 } } />
							<p style={ { margin: 0, fontSize: '0.875rem' } }>
								{ __( 'Keine E-Mails gefunden.', 'recruiting-playbook' ) }
							</p>
						</div>
					) : (
						<>
							<div style={ { overflowX: 'auto' } }>
								<table style={ { width: '100%', borderCollapse: 'collapse', fontSize: '0.875rem' } }>
									<thead>
										<tr style={ { backgroundColor: '#fafafa', borderBottom: '1px solid #e5e7eb' } }>
											<th style={ { padding: '12px 16px', paddingLeft: '24px', textAlign: 'left', fontWeight: 500, fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#71717a', whiteSpace: 'nowrap' } }>
												{ __( 'Datum', 'recruiting-playbook' ) }
											</th>
											<th style={ { padding: '12px 16px', textAlign: 'left', fontWeight: 500, fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#71717a', whiteSpace: 'nowrap' } }>
												{ __( 'Empfänger', 'recruiting-playbook' ) }
											</th>
											<th style={ { padding: '12px 16px', textAlign: 'left', fontWeight: 500, fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#71717a', whiteSpace: 'nowrap' } }>
												{ __( 'Betreff', 'recruiting-playbook' ) }
											</th>
											<th style={ { padding: '12px 16px', textAlign: 'left', fontWeight: 500, fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#71717a', whiteSpace: 'nowrap' } }>
												{ __( 'Status', 'recruiting-playbook' ) }
											</th>
											<th style={ { padding: '12px 16px', paddingRight: '24px', textAlign: 'left', fontWeight: 500, fontSize: '12px', textTransform: 'uppercase', letterSpacing: '0.05em', color: '#71717a', whiteSpace: 'nowrap', width: '100px' } }>
												{ __( 'Aktionen', 'recruiting-playbook' ) }
											</th>
										</tr>
									</thead>
									<tbody>
										{ filteredEmails.map( ( email, index ) => (
											<tr
												key={ email.id }
												style={ {
													borderBottom: index < filteredEmails.length - 1 ? '1px solid #e5e7eb' : 'none',
													transition: 'background-color 150ms',
												} }
												onMouseEnter={ ( e ) => { e.currentTarget.style.backgroundColor = '#fafafa'; } }
												onMouseLeave={ ( e ) => { e.currentTarget.style.backgroundColor = 'transparent'; } }
											>
												<td
													style={ { padding: '16px', paddingLeft: '24px', whiteSpace: 'nowrap', color: '#71717a' } }
													title={ email.status === 'scheduled' ? formatDate( email.scheduled_at ) : formatDate( email.sent_at || email.created_at ) }
												>
													{ email.status === 'scheduled'
														? formatDate( email.scheduled_at )
														: formatDate( email.sent_at || email.created_at )
													}
												</td>
												<td
													style={ { padding: '16px', fontWeight: 500, color: '#18181b', maxWidth: '200px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }
													title={ email.recipient?.email || email.recipient_email }
												>
													{ email.recipient?.email || email.recipient_email }
												</td>
												<td style={ { padding: '16px', maxWidth: '280px' } }>
													<button
														type="button"
														onClick={ () => handleViewEmail( email ) }
														title={ email.subject }
														style={ {
															background: 'none',
															border: 'none',
															padding: 0,
															color: '#18181b',
															cursor: 'pointer',
															fontSize: '0.875rem',
															textAlign: 'left',
															maxWidth: '100%',
															overflow: 'hidden',
															textOverflow: 'ellipsis',
															whiteSpace: 'nowrap',
															display: 'block',
														} }
														onMouseEnter={ ( e ) => { e.currentTarget.style.color = '#1d71b8'; } }
														onMouseLeave={ ( e ) => { e.currentTarget.style.color = '#18181b'; } }
													>
														{ email.subject }
													</button>
												</td>
												<td style={ { padding: '16px' } }>
													<StatusBadge status={ email.status } />
												</td>
												<td style={ { padding: '16px', paddingRight: '24px' } }>
													<div style={ { display: 'flex', gap: '4px' } }>
														<Button
															variant="ghost"
															size="icon"
															onClick={ () => handleViewEmail( email ) }
															title={ __( 'Anzeigen', 'recruiting-playbook' ) }
														>
															<Eye style={ { width: '1rem', height: '1rem' } } />
														</Button>
														{ email.status === 'failed' && (
															<Button
																variant="ghost"
																size="icon"
																onClick={ () => setConfirmResend( email ) }
																title={ __( 'Erneut senden', 'recruiting-playbook' ) }
															>
																<RefreshCw style={ { width: '1rem', height: '1rem' } } />
															</Button>
														) }
														{ email.can_cancel && (
															<Button
																variant="ghost"
																size="icon"
																onClick={ () => setConfirmCancel( email ) }
																title={ __( 'Stornieren', 'recruiting-playbook' ) }
															>
																<X style={ { width: '1rem', height: '1rem', color: '#dc2626' } } />
															</Button>
														) }
													</div>
												</td>
											</tr>
										) ) }
									</tbody>
								</table>
							</div>

							{ /* Pagination - shadcn/ui style */ }
							{ pagination.pages > 1 && (
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'space-between',
										padding: '1rem 1.5rem',
										borderTop: '1px solid #e5e7eb',
									} }
								>
									<span style={ { fontSize: '0.875rem', color: '#71717a' } }>
										{ __( 'Seite', 'recruiting-playbook' ) } { pagination.page } { __( 'von', 'recruiting-playbook' ) } { pagination.pages }
									</span>
									<div style={ { display: 'flex', gap: '0.5rem' } }>
										<Button
											variant="outline"
											size="sm"
											disabled={ pagination.page <= 1 }
											onClick={ () => onPageChange && onPageChange( pagination.page - 1 ) }
										>
											<ChevronLeft style={ { width: '1rem', height: '1rem', marginRight: '0.25rem' } } />
											{ __( 'Zurück', 'recruiting-playbook' ) }
										</Button>
										<Button
											variant="outline"
											size="sm"
											disabled={ pagination.page >= pagination.pages }
											onClick={ () => onPageChange && onPageChange( pagination.page + 1 ) }
										>
											{ __( 'Weiter', 'recruiting-playbook' ) }
											<ChevronRight style={ { width: '1rem', height: '1rem', marginLeft: '0.25rem' } } />
										</Button>
									</div>
								</div>
							) }
						</>
					) }
				</CardContent>
			</Card>

			{ /* E-Mail Vorschau Modal */ }
			{ viewingEmail && (
				<Modal title={ viewingEmail.subject } onClose={ () => setViewingEmail( null ) }>
					<div style={ { padding: '1.5rem' } }>
						{ /* Meta-Informationen */ }
						<div
							style={ {
								display: 'grid',
								gridTemplateColumns: 'auto 1fr',
								gap: '0.5rem 1rem',
								padding: '1rem',
								backgroundColor: '#fafafa',
								borderRadius: '0.5rem',
								fontSize: '0.875rem',
								marginBottom: '1.5rem',
							} }
						>
							<span style={ { color: '#71717a', fontWeight: 500 } }>{ __( 'Empfänger', 'recruiting-playbook' ) }:</span>
							<span style={ { color: '#18181b' } }>{ viewingEmail.recipient?.email || viewingEmail.recipient_email }</span>

							<span style={ { color: '#71717a', fontWeight: 500 } }>{ __( 'Datum', 'recruiting-playbook' ) }:</span>
							<span style={ { color: '#18181b' } }>{ formatDate( viewingEmail.sent_at || viewingEmail.created_at ) }</span>

							<span style={ { color: '#71717a', fontWeight: 500 } }>{ __( 'Status', 'recruiting-playbook' ) }:</span>
							<span><StatusBadge status={ viewingEmail.status } /></span>
						</div>

						{ viewingEmail.error_message && (
							<div
								style={ {
									display: 'flex',
									alignItems: 'center',
									gap: '0.75rem',
									padding: '1rem',
									backgroundColor: '#fef2f2',
									border: '1px solid #fecaca',
									borderRadius: '0.5rem',
									marginBottom: '1.5rem',
									color: '#dc2626',
									fontSize: '0.875rem',
								} }
							>
								<AlertCircle style={ { width: '1.25rem', height: '1.25rem', flexShrink: 0 } } />
								{ viewingEmail.error_message }
							</div>
						) }

						{ /* E-Mail-Inhalt */ }
						<div>
							<h4 style={ { margin: '0 0 0.75rem 0', fontSize: '0.75rem', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.05em', color: '#71717a' } }>
								{ __( 'Nachricht', 'recruiting-playbook' ) }
							</h4>
							{ viewingLoading ? (
								<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '3rem', color: '#71717a' } }>
									<Spinner size="default" />
									<span style={ { marginLeft: '0.75rem' } }>{ __( 'Lade...', 'recruiting-playbook' ) }</span>
								</div>
							) : (
								<div
									style={ {
										padding: '1.25rem',
										backgroundColor: '#fafafa',
										borderRadius: '0.5rem',
										border: '1px solid #e5e7eb',
										fontSize: '0.875rem',
										lineHeight: 1.7,
										color: '#18181b',
									} }
									dangerouslySetInnerHTML={ {
										__html: DOMPurify.sanitize( viewingEmail.body_html || viewingEmail.body || '', DOMPURIFY_CONFIG ),
									} }
								/>
							) }
						</div>
					</div>
				</Modal>
			) }

			{ /* Erneut senden Bestätigung */ }
			<AlertDialog open={ !! confirmResend } onOpenChange={ () => setConfirmResend( null ) }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>{ __( 'E-Mail erneut senden', 'recruiting-playbook' ) }</AlertDialogTitle>
						<AlertDialogDescription>
							{ __( 'Möchten Sie diese E-Mail erneut senden?', 'recruiting-playbook' ) }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>{ __( 'Abbrechen', 'recruiting-playbook' ) }</AlertDialogCancel>
						<AlertDialogAction onClick={ handleResendConfirm }>
							{ __( 'Erneut senden', 'recruiting-playbook' ) }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>

			{ /* Stornieren Bestätigung */ }
			<AlertDialog open={ !! confirmCancel } onOpenChange={ () => setConfirmCancel( null ) }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>{ __( 'E-Mail stornieren', 'recruiting-playbook' ) }</AlertDialogTitle>
						<AlertDialogDescription>
							{ __( 'Möchten Sie diese geplante E-Mail stornieren?', 'recruiting-playbook' ) }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>{ __( 'Abbrechen', 'recruiting-playbook' ) }</AlertDialogCancel>
						<AlertDialogAction onClick={ handleCancelConfirm } variant="destructive">
							{ __( 'Stornieren', 'recruiting-playbook' ) }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</div>
	);
}

EmailHistory.propTypes = {
	emails: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.number.isRequired,
			recipient_email: PropTypes.string,
			subject: PropTypes.string,
			status: PropTypes.string,
			sent_at: PropTypes.string,
			created_at: PropTypes.string,
			scheduled_at: PropTypes.string,
			can_cancel: PropTypes.bool,
			body: PropTypes.string,
			body_html: PropTypes.string,
			error_message: PropTypes.string,
		} )
	),
	loading: PropTypes.bool,
	error: PropTypes.string,
	pagination: PropTypes.shape( {
		total: PropTypes.number,
		pages: PropTypes.number,
		page: PropTypes.number,
		perPage: PropTypes.number,
	} ),
	onResend: PropTypes.func,
	onCancel: PropTypes.func,
	onPageChange: PropTypes.func,
	action: PropTypes.node,
};

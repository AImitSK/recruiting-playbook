/**
 * EmailHistory - Liste der versendeten E-Mails (shadcn/ui Design)
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import {
	Eye,
	RefreshCw,
	X,
	ChevronLeft,
	ChevronRight,
	Mail,
	AlertCircle,
} from 'lucide-react';
import DOMPurify from 'dompurify';
import {
	Card,
	CardContent,
	CardHeader,
	CardTitle,
} from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Badge } from '../../components/ui/badge';
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '../../components/ui/table';

// DOMPurify-Konfiguration: Nur sichere Tags für E-Mail-Inhalte
const DOMPURIFY_CONFIG = {
	ALLOWED_TAGS: [ 'p', 'br', 'strong', 'em', 'b', 'i', 'a', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'div', 'span' ],
	ALLOWED_ATTR: [ 'href', 'target', 'rel', 'class', 'style' ],
	FORBID_TAGS: [ 'script', 'iframe', 'object', 'embed', 'form', 'input' ],
};

/**
 * Status Badge Komponente
 */
function StatusBadge( { status } ) {
	const config = {
		sent: { label: __( 'Gesendet', 'recruiting-playbook' ), color: '#2fac66', bg: '#e6f5ec' },
		failed: { label: __( 'Fehlgeschlagen', 'recruiting-playbook' ), color: '#d63638', bg: '#ffe6e6' },
		pending: { label: __( 'Ausstehend', 'recruiting-playbook' ), color: '#dba617', bg: '#fff8e6' },
		scheduled: { label: __( 'Geplant', 'recruiting-playbook' ), color: '#2271b1', bg: '#e6f3ff' },
		cancelled: { label: __( 'Storniert', 'recruiting-playbook' ), color: '#787c82', bg: '#f0f0f0' },
	};

	const statusConfig = config[ status ] || config.pending;

	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				padding: '0.25rem 0.625rem',
				borderRadius: '9999px',
				fontSize: '0.75rem',
				fontWeight: 500,
				backgroundColor: statusConfig.bg,
				color: statusConfig.color,
			} }
		>
			{ statusConfig.label }
		</span>
	);
}

/**
 * Modal Komponente
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
					borderRadius: '0.5rem',
					boxShadow: '0 4px 20px rgba(0, 0, 0, 0.15)',
					maxWidth: '700px',
					width: '100%',
					maxHeight: '90vh',
					overflow: 'auto',
				} }
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						padding: '1rem 1.5rem',
						borderBottom: '1px solid #e5e7eb',
					} }
				>
					<h3 style={ { margin: 0, fontSize: '1rem', fontWeight: 600, color: '#1f2937' } }>
						{ title }
					</h3>
					<button
						type="button"
						onClick={ onClose }
						style={ {
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							width: '2rem',
							height: '2rem',
							background: 'none',
							border: 'none',
							borderRadius: '0.375rem',
							cursor: 'pointer',
							color: '#6b7280',
						} }
					>
						<X style={ { width: '1.25rem', height: '1.25rem' } } />
					</button>
				</div>
				{ children }
			</div>
		</div>
	);
}

/**
 * Confirm Dialog Komponente
 */
function ConfirmDialog( { title, message, onConfirm, onCancel, confirmLabel, isDestructive } ) {
	return (
		<Modal title={ title } onClose={ onCancel }>
			<div style={ { padding: '1.5rem' } }>
				<p style={ { margin: 0, color: '#374151' } }>{ message }</p>
			</div>
			<div
				style={ {
					display: 'flex',
					justifyContent: 'flex-end',
					gap: '0.5rem',
					padding: '1rem 1.5rem',
					borderTop: '1px solid #e5e7eb',
					backgroundColor: '#f9fafb',
				} }
			>
				<Button variant="outline" onClick={ onCancel }>
					{ __( 'Abbrechen', 'recruiting-playbook' ) }
				</Button>
				<Button
					onClick={ onConfirm }
					style={ isDestructive ? { backgroundColor: '#d63638' } : {} }
				>
					{ confirmLabel }
				</Button>
			</div>
		</Modal>
	);
}

/**
 * Spinner Komponente
 */
function Spinner() {
	return (
		<>
			<div
				style={ {
					width: '1.5rem',
					height: '1.5rem',
					border: '2px solid #e5e7eb',
					borderTopColor: '#1d71b8',
					borderRadius: '50%',
					animation: 'spin 0.8s linear infinite',
				} }
			/>
			<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
		</>
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
} ) {
	const [ statusFilter, setStatusFilter ] = useState( '' );
	const [ viewingEmail, setViewingEmail ] = useState( null );
	const [ confirmResend, setConfirmResend ] = useState( null );
	const [ confirmCancel, setConfirmCancel ] = useState( null );

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
			dateStyle: 'medium',
			timeStyle: 'short',
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
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '3rem', gap: '0.75rem', color: '#6b7280' } }>
				<Spinner />
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
						gap: '0.5rem',
						padding: '0.75rem 1rem',
						backgroundColor: '#ffe6e6',
						borderLeft: '4px solid #d63638',
						borderRadius: '0.375rem',
						marginBottom: '1rem',
						color: '#d63638',
						fontSize: '0.875rem',
					} }
				>
					<AlertCircle style={ { width: '1rem', height: '1rem', flexShrink: 0 } } />
					{ error }
				</div>
			) }

			<Card>
				<CardHeader>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '1rem' } }>
						<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
							{ __( 'E-Mails', 'recruiting-playbook' ) }
							{ emails.length > 0 && (
								<Badge variant="secondary">{ emails.length }</Badge>
							) }
						</CardTitle>
						<select
							value={ statusFilter }
							onChange={ ( e ) => setStatusFilter( e.target.value ) }
							style={ {
								padding: '0.5rem 2rem 0.5rem 0.75rem',
								border: '1px solid #e5e7eb',
								borderRadius: '0.375rem',
								fontSize: '0.875rem',
								backgroundColor: '#fff',
								cursor: 'pointer',
							} }
						>
							{ statusOptions.map( ( option ) => (
								<option key={ option.value } value={ option.value }>
									{ option.label }
								</option>
							) ) }
						</select>
					</div>
				</CardHeader>

				<CardContent>
					{ filteredEmails.length === 0 ? (
						<div style={ { textAlign: 'center', padding: '2rem', color: '#6b7280' } }>
							<Mail style={ { width: '3rem', height: '3rem', marginBottom: '0.75rem', opacity: 0.5 } } />
							<p>{ __( 'Keine E-Mails gefunden.', 'recruiting-playbook' ) }</p>
						</div>
					) : (
						<>
							<Table>
								<TableHeader>
									<TableRow>
										<TableHead>{ __( 'Datum', 'recruiting-playbook' ) }</TableHead>
										<TableHead>{ __( 'Empfänger', 'recruiting-playbook' ) }</TableHead>
										<TableHead>{ __( 'Betreff', 'recruiting-playbook' ) }</TableHead>
										<TableHead>{ __( 'Status', 'recruiting-playbook' ) }</TableHead>
										<TableHead style={ { textAlign: 'right' } }>{ __( 'Aktionen', 'recruiting-playbook' ) }</TableHead>
									</TableRow>
								</TableHeader>
								<TableBody>
									{ filteredEmails.map( ( email ) => (
										<TableRow key={ email.id }>
											<TableCell style={ { whiteSpace: 'nowrap' } }>
												{ email.status === 'scheduled'
													? formatDate( email.scheduled_at )
													: formatDate( email.sent_at || email.created_at )
												}
											</TableCell>
											<TableCell>{ email.recipient?.email || email.recipient_email }</TableCell>
											<TableCell>
												<button
													type="button"
													onClick={ () => setViewingEmail( email ) }
													style={ {
														background: 'none',
														border: 'none',
														padding: 0,
														color: '#1d71b8',
														cursor: 'pointer',
														textDecoration: 'none',
														fontSize: 'inherit',
														textAlign: 'left',
													} }
												>
													{ email.subject }
												</button>
											</TableCell>
											<TableCell>
												<StatusBadge status={ email.status } />
											</TableCell>
											<TableCell>
												<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.25rem' } }>
													<Button
														variant="ghost"
														size="icon"
														onClick={ () => setViewingEmail( email ) }
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
															style={ { color: '#d63638' } }
														>
															<X style={ { width: '1rem', height: '1rem' } } />
														</Button>
													) }
												</div>
											</TableCell>
										</TableRow>
									) ) }
								</TableBody>
							</Table>

							{ /* Pagination */ }
							{ pagination.pages > 1 && (
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'space-between',
										padding: '0.75rem 0',
										marginTop: '1rem',
										borderTop: '1px solid #e5e7eb',
									} }
								>
									<span style={ { fontSize: '0.875rem', color: '#6b7280' } }>
										{ __( 'Seite', 'recruiting-playbook' ) } { pagination.page } { __( 'von', 'recruiting-playbook' ) } { pagination.pages }
									</span>
									<div style={ { display: 'flex', gap: '0.5rem' } }>
										<Button
											variant="outline"
											size="sm"
											disabled={ pagination.page <= 1 }
											onClick={ () => onPageChange && onPageChange( pagination.page - 1 ) }
										>
											<ChevronLeft style={ { width: '1rem', height: '1rem' } } />
										</Button>
										<Button
											variant="outline"
											size="sm"
											disabled={ pagination.page >= pagination.pages }
											onClick={ () => onPageChange && onPageChange( pagination.page + 1 ) }
										>
											<ChevronRight style={ { width: '1rem', height: '1rem' } } />
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
						<div style={ { marginBottom: '1rem', display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<div style={ { display: 'flex', gap: '0.5rem', fontSize: '0.875rem' } }>
								<strong style={ { color: '#6b7280', minWidth: '80px' } }>{ __( 'Empfänger', 'recruiting-playbook' ) }:</strong>
								<span style={ { color: '#1f2937' } }>{ viewingEmail.recipient?.email || viewingEmail.recipient_email }</span>
							</div>
							<div style={ { display: 'flex', gap: '0.5rem', fontSize: '0.875rem' } }>
								<strong style={ { color: '#6b7280', minWidth: '80px' } }>{ __( 'Datum', 'recruiting-playbook' ) }:</strong>
								<span style={ { color: '#1f2937' } }>{ formatDate( viewingEmail.sent_at || viewingEmail.created_at ) }</span>
							</div>
							<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', fontSize: '0.875rem' } }>
								<strong style={ { color: '#6b7280', minWidth: '80px' } }>{ __( 'Status', 'recruiting-playbook' ) }:</strong>
								<StatusBadge status={ viewingEmail.status } />
							</div>
							{ viewingEmail.error_message && (
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										gap: '0.5rem',
										padding: '0.5rem 0.75rem',
										backgroundColor: '#ffe6e6',
										borderRadius: '0.375rem',
										marginTop: '0.5rem',
										color: '#d63638',
										fontSize: '0.875rem',
									} }
								>
									<AlertCircle style={ { width: '1rem', height: '1rem', flexShrink: 0 } } />
									{ viewingEmail.error_message }
								</div>
							) }
						</div>

						<div style={ { borderTop: '1px solid #e5e7eb', paddingTop: '1rem' } }>
							<h4 style={ { margin: '0 0 0.75rem 0', fontSize: '0.875rem', fontWeight: 600, color: '#374151' } }>
								{ __( 'Nachricht', 'recruiting-playbook' ) }
							</h4>
							<div
								style={ {
									padding: '1rem',
									backgroundColor: '#f9fafb',
									borderRadius: '0.375rem',
									fontSize: '0.875rem',
									lineHeight: 1.6,
								} }
								dangerouslySetInnerHTML={ {
									__html: DOMPurify.sanitize( viewingEmail.body_html || viewingEmail.body, DOMPURIFY_CONFIG ),
								} }
							/>
						</div>
					</div>
				</Modal>
			) }

			{ /* Erneut senden Bestätigung */ }
			{ confirmResend && (
				<ConfirmDialog
					title={ __( 'E-Mail erneut senden', 'recruiting-playbook' ) }
					message={ __( 'Möchten Sie diese E-Mail erneut senden?', 'recruiting-playbook' ) }
					confirmLabel={ __( 'Erneut senden', 'recruiting-playbook' ) }
					onConfirm={ handleResendConfirm }
					onCancel={ () => setConfirmResend( null ) }
				/>
			) }

			{ /* Stornieren Bestätigung */ }
			{ confirmCancel && (
				<ConfirmDialog
					title={ __( 'E-Mail stornieren', 'recruiting-playbook' ) }
					message={ __( 'Möchten Sie diese geplante E-Mail stornieren?', 'recruiting-playbook' ) }
					confirmLabel={ __( 'Stornieren', 'recruiting-playbook' ) }
					isDestructive
					onConfirm={ handleCancelConfirm }
					onCancel={ () => setConfirmCancel( null ) }
				/>
			) }
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
};

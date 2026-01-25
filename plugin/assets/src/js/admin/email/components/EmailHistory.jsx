/**
 * EmailHistory - Liste der versendeten E-Mails
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo } from '@wordpress/element';
import PropTypes from 'prop-types';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	SelectControl,
	Spinner,
	Notice,
	Modal,
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { seen, backup, close } from '@wordpress/icons';
import DOMPurify from 'dompurify';

/**
 * Status-Badge Komponente
 *
 * @param {Object} props        Props
 * @param {string} props.status Status
 * @return {JSX.Element} Badge
 */
function StatusBadge( { status } ) {
	const i18n = window.rpEmailData?.i18n || {};

	const statusLabels = {
		sent: i18n.statusSent || 'Gesendet',
		failed: i18n.statusFailed || 'Fehlgeschlagen',
		pending: i18n.statusPending || 'Ausstehend',
		scheduled: i18n.statusScheduled || 'Geplant',
		cancelled: i18n.statusCancelled || 'Storniert',
	};

	const statusClasses = {
		sent: 'rp-status--success',
		failed: 'rp-status--error',
		pending: 'rp-status--warning',
		scheduled: 'rp-status--info',
		cancelled: 'rp-status--neutral',
	};

	return (
		<span className={ `rp-status ${ statusClasses[ status ] || '' }` }>
			{ statusLabels[ status ] || status }
		</span>
	);
}

/**
 * EmailHistory Komponente
 *
 * @param {Object}   props            Props
 * @param {Array}    props.emails     Liste der E-Mails
 * @param {boolean}  props.loading    Lade-Status
 * @param {string}   props.error      Fehlermeldung
 * @param {Object}   props.pagination Pagination-Daten
 * @param {Function} props.onResend   Callback beim erneuten Senden
 * @param {Function} props.onCancel   Callback beim Stornieren
 * @param {Function} props.onPageChange Callback bei Seitenwechsel
 * @return {JSX.Element} Komponente
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

	const i18n = window.rpEmailData?.i18n || {};

	// Gefilterte E-Mails
	const filteredEmails = useMemo( () => {
		if ( ! statusFilter ) {
			return emails;
		}
		return emails.filter( ( email ) => email.status === statusFilter );
	}, [ emails, statusFilter ] );

	// Status-Optionen
	const statusOptions = [
		{ value: '', label: i18n.allStatuses || 'Alle Status' },
		{ value: 'sent', label: i18n.statusSent || 'Gesendet' },
		{ value: 'failed', label: i18n.statusFailed || 'Fehlgeschlagen' },
		{ value: 'pending', label: i18n.statusPending || 'Ausstehend' },
		{ value: 'scheduled', label: i18n.statusScheduled || 'Geplant' },
		{ value: 'cancelled', label: i18n.statusCancelled || 'Storniert' },
	];

	/**
	 * Datum formatieren
	 *
	 * @param {string} date ISO-Datum
	 * @return {string} Formatiertes Datum
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

	/**
	 * Pagination rendern
	 */
	const renderPagination = () => {
		if ( pagination.pages <= 1 ) {
			return null;
		}

		return (
			<div className="rp-email-history__pagination">
				<Button
					variant="secondary"
					disabled={ pagination.page <= 1 }
					onClick={ () => onPageChange && onPageChange( pagination.page - 1 ) }
				>
					{ i18n.previous || 'Zurück' }
				</Button>
				<span className="rp-email-history__page-info">
					{ `${ pagination.page } / ${ pagination.pages }` }
				</span>
				<Button
					variant="secondary"
					disabled={ pagination.page >= pagination.pages }
					onClick={ () => onPageChange && onPageChange( pagination.page + 1 ) }
				>
					{ i18n.next || 'Weiter' }
				</Button>
			</div>
		);
	};

	if ( loading ) {
		return (
			<div className="rp-email-history__loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="rp-email-history">
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<div className="rp-email-history__header">
						<h2>{ i18n.emailHistory || 'E-Mail-Verlauf' }</h2>
						<SelectControl
							value={ statusFilter }
							options={ statusOptions }
							onChange={ setStatusFilter }
						/>
					</div>
				</CardHeader>

				<CardBody>
					{ filteredEmails.length === 0 ? (
						<div className="rp-email-history__empty">
							<p>{ i18n.noEmails || 'Keine E-Mails gefunden.' }</p>
						</div>
					) : (
						<>
							<table className="rp-email-history__table widefat striped">
								<thead>
									<tr>
										<th>{ i18n.date || 'Datum' }</th>
										<th>{ i18n.recipient || 'Empfänger' }</th>
										<th>{ i18n.subject || 'Betreff' }</th>
										<th>{ i18n.status || 'Status' }</th>
										<th className="rp-email-history__actions-header">
											{ i18n.actions || 'Aktionen' }
										</th>
									</tr>
								</thead>
								<tbody>
									{ filteredEmails.map( ( email ) => (
										<tr key={ email.id }>
											<td>
												{ email.status === 'scheduled'
													? formatDate( email.scheduled_at )
													: formatDate( email.sent_at || email.created_at )
												}
											</td>
											<td>{ email.recipient_email }</td>
											<td>
												<button
													type="button"
													className="rp-email-history__subject-link"
													onClick={ () => setViewingEmail( email ) }
												>
													{ email.subject }
												</button>
											</td>
											<td>
												<StatusBadge status={ email.status } />
											</td>
											<td className="rp-email-history__actions">
												<Button
													icon={ seen }
													label={ i18n.view || 'Anzeigen' }
													onClick={ () => setViewingEmail( email ) }
												/>
												{ email.status === 'failed' && (
													<Button
														icon={ backup }
														label={ i18n.resend || 'Erneut senden' }
														onClick={ () => setConfirmResend( email ) }
													/>
												) }
												{ email.can_cancel && (
													<Button
														icon={ close }
														label={ i18n.cancelEmail || 'Stornieren' }
														isDestructive
														onClick={ () => setConfirmCancel( email ) }
													/>
												) }
											</td>
										</tr>
									) ) }
								</tbody>
							</table>

							{ renderPagination() }
						</>
					) }
				</CardBody>
			</Card>

			{ viewingEmail && (
				<Modal
					title={ viewingEmail.subject }
					onRequestClose={ () => setViewingEmail( null ) }
					className="rp-email-history__modal"
				>
					<div className="rp-email-history__view">
						<div className="rp-email-history__view-meta">
							<p>
								<strong>{ i18n.recipient || 'Empfänger' }:</strong>{ ' ' }
								{ viewingEmail.recipient_email }
							</p>
							<p>
								<strong>{ i18n.date || 'Datum' }:</strong>{ ' ' }
								{ formatDate( viewingEmail.sent_at || viewingEmail.created_at ) }
							</p>
							<p>
								<strong>{ i18n.status || 'Status' }:</strong>{ ' ' }
								<StatusBadge status={ viewingEmail.status } />
							</p>
							{ viewingEmail.error_message && (
								<p className="rp-email-history__error">
									<strong>{ i18n.error || 'Fehler' }:</strong>{ ' ' }
									{ viewingEmail.error_message }
								</p>
							) }
						</div>
						<div className="rp-email-history__view-body">
							<h4>{ i18n.message || 'Nachricht' }</h4>
							<div
								className="rp-email-history__view-content"
								dangerouslySetInnerHTML={ {
									__html: DOMPurify.sanitize( viewingEmail.body_html || viewingEmail.body ),
								} }
							/>
						</div>
					</div>
				</Modal>
			) }

			{ confirmResend && (
				<ConfirmDialog
					isOpen={ true }
					onConfirm={ handleResendConfirm }
					onCancel={ () => setConfirmResend( null ) }
				>
					{ i18n.confirmResend || 'Möchten Sie diese E-Mail erneut senden?' }
				</ConfirmDialog>
			) }

			{ confirmCancel && (
				<ConfirmDialog
					isOpen={ true }
					onConfirm={ handleCancelConfirm }
					onCancel={ () => setConfirmCancel( null ) }
				>
					{ i18n.confirmCancelEmail || 'Möchten Sie diese geplante E-Mail stornieren?' }
				</ConfirmDialog>
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

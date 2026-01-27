/**
 * SignatureList - Liste aller E-Mail-Signaturen
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Pencil, Trash2, Search, Plus, Star, Building2 } from 'lucide-react';
import { Button } from '../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Alert, AlertDescription } from '../../components/ui/alert';
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

/**
 * SignatureList Komponente
 *
 * @param {Object}   props                   Props
 * @param {Array}    props.signatures        Liste der Signaturen
 * @param {Object}   props.companySignature  Firmen-Signatur
 * @param {boolean}  props.loading           Lade-Status
 * @param {string}   props.error             Fehlermeldung
 * @param {boolean}  props.isAdmin           Ist Admin?
 * @param {Function} props.onSelect          Callback bei Auswahl
 * @param {Function} props.onDelete          Callback beim Löschen
 * @param {Function} props.onSetDefault      Callback beim Standard setzen
 * @param {Function} props.onCreate          Callback beim Erstellen
 * @param {Function} props.onEditCompany     Callback beim Bearbeiten der Firmen-Signatur
 * @return {JSX.Element} Komponente
 */
export function SignatureList( {
	signatures = [],
	companySignature = null,
	loading = false,
	error = null,
	isAdmin = false,
	onSelect,
	onDelete,
	onSetDefault,
	onCreate,
	onEditCompany,
} ) {
	const [ search, setSearch ] = useState( '' );
	const [ confirmDelete, setConfirmDelete ] = useState( null );

	const i18n = window.rpEmailData?.i18n || {};

	// Filter Signaturen
	const filteredSignatures = useMemo( () => {
		if ( ! search ) {
			return signatures;
		}

		const searchLower = search.toLowerCase();
		return signatures.filter( ( signature ) =>
			signature.name?.toLowerCase().includes( searchLower )
		);
	}, [ signatures, search ] );

	/**
	 * Löschen bestätigen
	 *
	 * @param {Object} signature Signatur
	 */
	const handleDeleteClick = ( signature ) => {
		setConfirmDelete( signature );
	};

	/**
	 * Löschen durchführen
	 */
	const handleDeleteConfirm = () => {
		if ( confirmDelete && onDelete ) {
			onDelete( confirmDelete.id );
		}
		setConfirmDelete( null );
	};

	/**
	 * Status-Badge rendern
	 *
	 * @param {Object} signature Signatur
	 * @return {JSX.Element} Badge
	 */
	const renderStatusBadge = ( signature ) => {
		const badges = [];

		const badgeBaseStyle = {
			display: 'inline-flex',
			alignItems: 'center',
			padding: '0.125rem 0.5rem',
			borderRadius: '9999px',
			fontSize: '0.75rem',
			fontWeight: 500,
			marginRight: '0.25rem',
		};

		if ( signature.is_default ) {
			badges.push(
				<span
					key="default"
					className="rp-badge rp-badge--default"
					style={ { ...badgeBaseStyle, backgroundColor: '#dcfce7', color: '#166534' } }
				>
					<Star style={ { width: '0.75rem', height: '0.75rem', marginRight: '0.25rem' } } />
					{ i18n.default || 'Standard' }
				</span>
			);
		}

		return badges.length > 0 ? <div className="rp-signature-badges">{ badges }</div> : null;
	};

	/**
	 * Vorschau-Text generieren
	 *
	 * @param {string} html HTML-Inhalt
	 * @return {string} Text-Vorschau
	 */
	const getPreviewText = ( html ) => {
		if ( ! html ) {
			return '';
		}
		// HTML-Tags entfernen und auf 100 Zeichen kürzen
		const text = html.replace( /<[^>]+>/g, ' ' ).replace( /\s+/g, ' ' ).trim();
		return text.length > 100 ? text.substring( 0, 100 ) + '...' : text;
	};

	if ( loading ) {
		return (
			<div
				className="rp-signature-list__loading"
				style={ { display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '3rem' } }
			>
				<Spinner size="lg" />
			</div>
		);
	}

	return (
		<div className="rp-signature-list">
			{ error && (
				<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			{ /* Firmen-Signatur (nur für Admins) */ }
			{ isAdmin && (
				<Card style={ { marginBottom: '1.5rem' } }>
					<CardHeader>
						<div
							className="rp-signature-list__header"
							style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }
						>
							<CardTitle style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
								<Building2 style={ { width: '1.25rem', height: '1.25rem' } } />
								{ i18n.companySignature || 'Firmen-Signatur' }
							</CardTitle>
							<Button variant="outline" onClick={ onEditCompany }>
								<Pencil style={ { width: '1rem', height: '1rem', marginRight: '0.5rem' } } />
								{ i18n.edit || 'Bearbeiten' }
							</Button>
						</div>
					</CardHeader>
					<CardContent>
						{ companySignature ? (
							<div className="rp-signature-list__company-preview">
								<p style={ { margin: 0, color: '#6b7280', fontSize: '0.875rem' } }>
									{ getPreviewText( companySignature.body ) || ( i18n.noContent || 'Kein Inhalt' ) }
								</p>
							</div>
						) : (
							<p style={ { margin: 0, color: '#6b7280', fontStyle: 'italic' } }>
								{ i18n.noCompanySignature || 'Keine Firmen-Signatur vorhanden. Klicken Sie auf "Bearbeiten", um eine zu erstellen.' }
							</p>
						) }
					</CardContent>
				</Card>
			) }

			{ /* Persönliche Signaturen */ }
			<Card>
				<CardHeader>
					<div
						className="rp-signature-list__header"
						style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }
					>
						<CardTitle>{ i18n.mySignatures || 'Meine Signaturen' }</CardTitle>
						<Button onClick={ onCreate }>
							<Plus style={ { width: '1rem', height: '1rem', marginRight: '0.5rem' } } />
							{ i18n.newSignature || 'Neue Signatur' }
						</Button>
					</div>
				</CardHeader>

				<CardContent>
					{ signatures.length > 3 && (
						<div
							className="rp-signature-list__filters"
							style={ { display: 'flex', gap: '1rem', marginBottom: '1rem' } }
						>
							<div style={ { position: 'relative', flex: 1, maxWidth: '400px' } }>
								<Search
									style={ {
										position: 'absolute',
										left: '0.75rem',
										top: '50%',
										transform: 'translateY(-50%)',
										width: '1rem',
										height: '1rem',
										color: '#9ca3af',
									} }
								/>
								<Input
									value={ search }
									onChange={ ( e ) => setSearch( e.target.value ) }
									placeholder={ i18n.searchSignatures || 'Signaturen durchsuchen...' }
									style={ { paddingLeft: '2.5rem' } }
								/>
							</div>
						</div>
					) }

					{ filteredSignatures.length === 0 ? (
						<div
							className="rp-signature-list__empty"
							style={ { textAlign: 'center', padding: '2rem', color: '#6b7280' } }
						>
							<p>{ i18n.noSignatures || 'Keine Signaturen vorhanden.' }</p>
							<p style={ { fontSize: '0.875rem' } }>
								{ i18n.createSignatureHint || 'Erstellen Sie Ihre erste Signatur, um E-Mails zu personalisieren.' }
							</p>
						</div>
					) : (
						<div style={ { overflowX: 'auto' } }>
							<table
								className="rp-signature-list__table"
								style={ {
									width: '100%',
									borderCollapse: 'collapse',
									fontSize: '0.875rem',
								} }
							>
								<thead>
									<tr style={ { borderBottom: '1px solid #e5e7eb' } }>
										<th style={ { padding: '0.75rem', textAlign: 'left', fontWeight: 500, color: '#6b7280' } }>
											{ i18n.name || 'Name' }
										</th>
										<th style={ { padding: '0.75rem', textAlign: 'left', fontWeight: 500, color: '#6b7280' } }>
											{ i18n.preview || 'Vorschau' }
										</th>
										<th style={ { padding: '0.75rem', textAlign: 'left', fontWeight: 500, color: '#6b7280' } }>
											{ i18n.status || 'Status' }
										</th>
										<th style={ { padding: '0.75rem', textAlign: 'right', fontWeight: 500, color: '#6b7280' } }>
											{ i18n.actions || 'Aktionen' }
										</th>
									</tr>
								</thead>
								<tbody>
									{ filteredSignatures.map( ( signature ) => (
										<tr
											key={ signature.id }
											style={ { borderBottom: '1px solid #e5e7eb' } }
										>
											<td style={ { padding: '0.75rem' } }>
												<button
													type="button"
													className="rp-signature-list__name-link"
													onClick={ () => onSelect && onSelect( signature ) }
													style={ {
														background: 'none',
														border: 'none',
														padding: 0,
														color: '#1d71b8',
														cursor: 'pointer',
														fontWeight: 500,
													} }
												>
													{ signature.name }
												</button>
											</td>
											<td style={ { padding: '0.75rem', color: '#6b7280', maxWidth: '300px' } }>
												{ getPreviewText( signature.body ) }
											</td>
											<td style={ { padding: '0.75rem' } }>{ renderStatusBadge( signature ) }</td>
											<td style={ { padding: '0.75rem' } }>
												<div
													className="rp-signature-list__actions"
													style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.25rem' } }
												>
													{ ! signature.is_default && (
														<Button
															variant="ghost"
															size="icon"
															onClick={ () => onSetDefault && onSetDefault( signature.id ) }
															title={ i18n.setAsDefault || 'Als Standard setzen' }
														>
															<Star style={ { width: '1rem', height: '1rem' } } />
														</Button>
													) }
													<Button
														variant="ghost"
														size="icon"
														onClick={ () => onSelect && onSelect( signature ) }
														title={ i18n.editSignature || 'Bearbeiten' }
													>
														<Pencil style={ { width: '1rem', height: '1rem' } } />
													</Button>
													<Button
														variant="ghost"
														size="icon"
														onClick={ () => handleDeleteClick( signature ) }
														title={ i18n.deleteSignature || 'Löschen' }
														className="rp-text-destructive"
													>
														<Trash2 style={ { width: '1rem', height: '1rem', color: '#dc2626' } } />
													</Button>
												</div>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>
					) }
				</CardContent>
			</Card>

			<AlertDialog open={ !! confirmDelete } onOpenChange={ () => setConfirmDelete( null ) }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>{ i18n.deleteSignature || 'Signatur löschen' }</AlertDialogTitle>
						<AlertDialogDescription>
							{ i18n.confirmDeleteSignature || 'Möchten Sie diese Signatur wirklich löschen?' }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>{ i18n.cancel || 'Abbrechen' }</AlertDialogCancel>
						<AlertDialogAction onClick={ handleDeleteConfirm } variant="destructive">
							{ i18n.delete || 'Löschen' }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</div>
	);
}

SignatureList.propTypes = {
	signatures: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.number.isRequired,
			name: PropTypes.string,
			body: PropTypes.string,
			is_default: PropTypes.bool,
		} )
	),
	companySignature: PropTypes.shape( {
		id: PropTypes.number,
		body: PropTypes.string,
	} ),
	loading: PropTypes.bool,
	error: PropTypes.string,
	isAdmin: PropTypes.bool,
	onSelect: PropTypes.func,
	onDelete: PropTypes.func,
	onSetDefault: PropTypes.func,
	onCreate: PropTypes.func,
	onEditCompany: PropTypes.func,
};

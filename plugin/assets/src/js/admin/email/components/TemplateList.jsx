/**
 * TemplateList - Liste aller E-Mail-Templates
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Pencil, Copy, Trash2, RotateCcw, Search, Plus } from 'lucide-react';
import { Button } from '../../components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Select, SelectOption } from '../../components/ui/select';
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
 * TemplateList Komponente
 *
 * @param {Object}   props                  Props
 * @param {Array}    props.templates        Liste der Templates
 * @param {boolean}  props.loading          Lade-Status
 * @param {string}   props.error            Fehlermeldung
 * @param {Function} props.onSelect         Callback bei Auswahl
 * @param {Function} props.onDelete         Callback beim Löschen
 * @param {Function} props.onDuplicate      Callback beim Duplizieren
 * @param {Function} props.onReset          Callback beim Zurücksetzen
 * @param {Function} props.onCreate         Callback beim Erstellen
 * @return {JSX.Element} Komponente
 */
export function TemplateList( {
	templates = [],
	loading = false,
	error = null,
	onSelect,
	onDelete,
	onDuplicate,
	onReset,
	onCreate,
} ) {
	const [ search, setSearch ] = useState( '' );
	const [ categoryFilter, setCategoryFilter ] = useState( '' );
	const [ confirmDelete, setConfirmDelete ] = useState( null );
	const [ confirmReset, setConfirmReset ] = useState( null );

	const i18n = window.rpEmailData?.i18n || {};
	const categories = i18n.categories || {};

	// Filter Templates
	const filteredTemplates = useMemo( () => {
		return templates.filter( ( template ) => {
			// Suchfilter
			if ( search ) {
				const searchLower = search.toLowerCase();
				const matchesSearch =
					template.name?.toLowerCase().includes( searchLower ) ||
					template.subject?.toLowerCase().includes( searchLower );
				if ( ! matchesSearch ) {
					return false;
				}
			}

			// Kategoriefilter
			if ( categoryFilter && template.category !== categoryFilter ) {
				return false;
			}

			return true;
		} );
	}, [ templates, search, categoryFilter ] );

	/**
	 * Löschen bestätigen
	 *
	 * @param {Object} template Template
	 */
	const handleDeleteClick = ( template ) => {
		setConfirmDelete( template );
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
	 * Zurücksetzen bestätigen
	 *
	 * @param {Object} template Template
	 */
	const handleResetClick = ( template ) => {
		setConfirmReset( template );
	};

	/**
	 * Zurücksetzen durchführen
	 */
	const handleResetConfirm = () => {
		if ( confirmReset && onReset ) {
			onReset( confirmReset.id );
		}
		setConfirmReset( null );
	};

	/**
	 * Status-Badge rendern
	 *
	 * @param {Object} template Template
	 * @return {JSX.Element} Badge
	 */
	const renderStatusBadge = ( template ) => {
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

		if ( template.is_system ) {
			badges.push(
				<span
					key="system"
					className="rp-badge rp-badge--system"
					style={ { ...badgeBaseStyle, backgroundColor: '#dbeafe', color: '#1d4ed8' } }
				>
					{ i18n.system || 'System' }
				</span>
			);
		}

		if ( template.is_default ) {
			badges.push(
				<span
					key="default"
					className="rp-badge rp-badge--default"
					style={ { ...badgeBaseStyle, backgroundColor: '#dcfce7', color: '#166534' } }
				>
					{ i18n.default || 'Standard' }
				</span>
			);
		}

		if ( ! template.is_active ) {
			badges.push(
				<span
					key="inactive"
					className="rp-badge rp-badge--inactive"
					style={ { ...badgeBaseStyle, backgroundColor: '#f3f4f6', color: '#6b7280' } }
				>
					{ i18n.inactive || 'Inaktiv' }
				</span>
			);
		}

		return badges.length > 0 ? <div className="rp-template-badges">{ badges }</div> : null;
	};

	if ( loading ) {
		return (
			<div
				className="rp-template-list__loading"
				style={ { display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '3rem' } }
			>
				<Spinner size="lg" />
			</div>
		);
	}

	return (
		<div className="rp-template-list">
			{ error && (
				<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			<Card>
				<CardHeader>
					<div
						className="rp-template-list__header"
						style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }
					>
						<CardTitle>{ i18n.templates || 'Templates' }</CardTitle>
						<Button onClick={ onCreate }>
							<Plus style={ { width: '1rem', height: '1rem', marginRight: '0.5rem' } } />
							{ i18n.newTemplate || 'Neues Template' }
						</Button>
					</div>
				</CardHeader>

				<CardContent>
					<div
						className="rp-template-list__filters"
						style={ { display: 'flex', gap: '1rem', marginBottom: '1rem' } }
					>
						<div style={ { position: 'relative', flex: 1 } }>
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
								placeholder={ i18n.searchTemplates || 'Templates durchsuchen...' }
								style={ { paddingLeft: '2.5rem' } }
							/>
						</div>
						<div style={ { minWidth: '200px' } }>
							<Select
								value={ categoryFilter }
								onChange={ ( e ) => setCategoryFilter( e.target.value ) }
							>
								<SelectOption value="">{ i18n.allCategories || 'Alle Kategorien' }</SelectOption>
								{ Object.entries( categories ).map( ( [ value, label ] ) => (
									<SelectOption key={ value } value={ value }>{ label }</SelectOption>
								) ) }
							</Select>
						</div>
					</div>

					{ filteredTemplates.length === 0 ? (
						<div
							className="rp-template-list__empty"
							style={ { textAlign: 'center', padding: '2rem', color: '#6b7280' } }
						>
							<p>{ i18n.noTemplates || 'Keine Templates gefunden.' }</p>
						</div>
					) : (
						<div style={ { overflowX: 'auto' } }>
							<table
								className="rp-template-list__table"
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
											{ i18n.subject || 'Betreff' }
										</th>
										<th style={ { padding: '0.75rem', textAlign: 'left', fontWeight: 500, color: '#6b7280' } }>
											{ i18n.category || 'Kategorie' }
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
									{ filteredTemplates.map( ( template ) => (
										<tr
											key={ template.id }
											style={ { borderBottom: '1px solid #e5e7eb' } }
										>
											<td style={ { padding: '0.75rem' } }>
												<button
													type="button"
													className="rp-template-list__name-link"
													onClick={ () => onSelect && onSelect( template ) }
													style={ {
														background: 'none',
														border: 'none',
														padding: 0,
														color: '#1d71b8',
														cursor: 'pointer',
														fontWeight: 500,
													} }
												>
													{ template.name }
												</button>
											</td>
											<td style={ { padding: '0.75rem', color: '#6b7280' } }>{ template.subject }</td>
											<td style={ { padding: '0.75rem', color: '#6b7280' } }>
												{ categories[ template.category ] || template.category }
											</td>
											<td style={ { padding: '0.75rem' } }>{ renderStatusBadge( template ) }</td>
											<td style={ { padding: '0.75rem' } }>
												<div
													className="rp-template-list__actions"
													style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.25rem' } }
												>
													<Button
														variant="ghost"
														size="icon"
														onClick={ () => onSelect && onSelect( template ) }
														title={ i18n.editTemplate || 'Bearbeiten' }
													>
														<Pencil style={ { width: '1rem', height: '1rem' } } />
													</Button>
													<Button
														variant="ghost"
														size="icon"
														onClick={ () => onDuplicate && onDuplicate( template.id ) }
														title={ i18n.duplicateTemplate || 'Duplizieren' }
													>
														<Copy style={ { width: '1rem', height: '1rem' } } />
													</Button>
													{ template.is_system && (
														<Button
															variant="ghost"
															size="icon"
															onClick={ () => handleResetClick( template ) }
															title={ i18n.resetTemplate || 'Zurücksetzen' }
														>
															<RotateCcw style={ { width: '1rem', height: '1rem' } } />
														</Button>
													) }
													{ ! template.is_system && (
														<Button
															variant="ghost"
															size="icon"
															onClick={ () => handleDeleteClick( template ) }
															title={ i18n.deleteTemplate || 'Löschen' }
															className="rp-text-destructive"
														>
															<Trash2 style={ { width: '1rem', height: '1rem', color: '#dc2626' } } />
														</Button>
													) }
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
						<AlertDialogTitle>{ i18n.deleteTemplate || 'Template löschen' }</AlertDialogTitle>
						<AlertDialogDescription>
							{ i18n.confirmDelete || 'Möchten Sie dieses Template wirklich löschen?' }
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

			<AlertDialog open={ !! confirmReset } onOpenChange={ () => setConfirmReset( null ) }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>{ i18n.resetTemplate || 'Template zurücksetzen' }</AlertDialogTitle>
						<AlertDialogDescription>
							{ i18n.confirmReset || 'Möchten Sie dieses Template auf den Standard zurücksetzen?' }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>{ i18n.cancel || 'Abbrechen' }</AlertDialogCancel>
						<AlertDialogAction onClick={ handleResetConfirm }>
							{ i18n.reset || 'Zurücksetzen' }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</div>
	);
}

TemplateList.propTypes = {
	templates: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.number.isRequired,
			name: PropTypes.string,
			subject: PropTypes.string,
			category: PropTypes.string,
			is_system: PropTypes.bool,
			is_default: PropTypes.bool,
			is_active: PropTypes.bool,
		} )
	),
	loading: PropTypes.bool,
	error: PropTypes.string,
	onSelect: PropTypes.func,
	onDelete: PropTypes.func,
	onDuplicate: PropTypes.func,
	onReset: PropTypes.func,
	onCreate: PropTypes.func,
};

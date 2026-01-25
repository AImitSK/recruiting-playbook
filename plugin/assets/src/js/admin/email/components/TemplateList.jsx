/**
 * TemplateList - Liste aller E-Mail-Templates
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
	SearchControl,
	Spinner,
	Notice,
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { edit, copy, trash, backup } from '@wordpress/icons';

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

	// Kategorie-Optionen für Select
	const categoryOptions = useMemo( () => {
		const options = [ { value: '', label: i18n.allCategories || 'Alle Kategorien' } ];

		Object.entries( categories ).forEach( ( [ value, label ] ) => {
			options.push( { value, label } );
		} );

		return options;
	}, [ categories, i18n.allCategories ] );

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

		if ( template.is_system ) {
			badges.push(
				<span key="system" className="rp-badge rp-badge--system">
					{ i18n.system || 'System' }
				</span>
			);
		}

		if ( template.is_default ) {
			badges.push(
				<span key="default" className="rp-badge rp-badge--default">
					{ i18n.default || 'Standard' }
				</span>
			);
		}

		if ( ! template.is_active ) {
			badges.push(
				<span key="inactive" className="rp-badge rp-badge--inactive">
					{ i18n.inactive || 'Inaktiv' }
				</span>
			);
		}

		return badges.length > 0 ? <div className="rp-template-badges">{ badges }</div> : null;
	};

	if ( loading ) {
		return (
			<div className="rp-template-list__loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="rp-template-list">
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<Card>
				<CardHeader>
					<div className="rp-template-list__header">
						<h2>{ i18n.templates || 'Templates' }</h2>
						<Button variant="primary" onClick={ onCreate }>
							{ i18n.newTemplate || 'Neues Template' }
						</Button>
					</div>
				</CardHeader>

				<CardBody>
					<div className="rp-template-list__filters">
						<SearchControl
							value={ search }
							onChange={ setSearch }
							placeholder={ i18n.searchTemplates || 'Templates durchsuchen...' }
						/>
						<SelectControl
							value={ categoryFilter }
							options={ categoryOptions }
							onChange={ setCategoryFilter }
						/>
					</div>

					{ filteredTemplates.length === 0 ? (
						<div className="rp-template-list__empty">
							<p>{ i18n.noTemplates || 'Keine Templates gefunden.' }</p>
						</div>
					) : (
						<table className="rp-template-list__table widefat striped">
							<thead>
								<tr>
									<th>{ i18n.name || 'Name' }</th>
									<th>{ i18n.subject || 'Betreff' }</th>
									<th>{ i18n.category || 'Kategorie' }</th>
									<th>{ i18n.status || 'Status' }</th>
									<th className="rp-template-list__actions-header">
										{ i18n.actions || 'Aktionen' }
									</th>
								</tr>
							</thead>
							<tbody>
								{ filteredTemplates.map( ( template ) => (
									<tr key={ template.id }>
										<td>
											<button
												type="button"
												className="rp-template-list__name-link"
												onClick={ () => onSelect && onSelect( template ) }
											>
												{ template.name }
											</button>
										</td>
										<td>{ template.subject }</td>
										<td>{ categories[ template.category ] || template.category }</td>
										<td>{ renderStatusBadge( template ) }</td>
										<td className="rp-template-list__actions">
											<Button
												icon={ edit }
												label={ i18n.editTemplate || 'Bearbeiten' }
												onClick={ () => onSelect && onSelect( template ) }
											/>
											<Button
												icon={ copy }
												label={ i18n.duplicateTemplate || 'Duplizieren' }
												onClick={ () => onDuplicate && onDuplicate( template.id ) }
											/>
											{ template.is_system && (
												<Button
													icon={ backup }
													label={ i18n.resetTemplate || 'Zurücksetzen' }
													onClick={ () => handleResetClick( template ) }
												/>
											) }
											{ ! template.is_system && (
												<Button
													icon={ trash }
													label={ i18n.deleteTemplate || 'Löschen' }
													isDestructive
													onClick={ () => handleDeleteClick( template ) }
												/>
											) }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					) }
				</CardBody>
			</Card>

			{ confirmDelete && (
				<ConfirmDialog
					isOpen={ true }
					onConfirm={ handleDeleteConfirm }
					onCancel={ () => setConfirmDelete( null ) }
				>
					{ i18n.confirmDelete || 'Möchten Sie dieses Template wirklich löschen?' }
				</ConfirmDialog>
			) }

			{ confirmReset && (
				<ConfirmDialog
					isOpen={ true }
					onConfirm={ handleResetConfirm }
					onCancel={ () => setConfirmReset( null ) }
				>
					{ i18n.confirmReset || 'Möchten Sie dieses Template auf den Standard zurücksetzen?' }
				</ConfirmDialog>
			) }
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

/**
 * PlaceholderPicker - Auswahl von E-Mail-Platzhaltern
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo } from '@wordpress/element';
import {
	Button,
	Popover,
	SearchControl,
	Card,
	CardBody,
	CardHeader,
} from '@wordpress/components';
import { plus } from '@wordpress/icons';

/**
 * PlaceholderPicker Komponente
 *
 * @param {Object}   props             Props
 * @param {Object}   props.placeholders Platzhalter-Gruppen
 * @param {Function} props.onSelect    Callback bei Auswahl
 * @param {string}   props.buttonLabel Button-Label
 * @param {boolean}  props.compact     Kompakte Darstellung
 * @param {boolean}  props.showSearch  Suchfeld anzeigen
 * @return {JSX.Element} Komponente
 */
export function PlaceholderPicker( {
	placeholders = {},
	onSelect,
	buttonLabel,
	compact = false,
	showSearch = false,
} ) {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ search, setSearch ] = useState( '' );

	const i18n = window.rpEmailData?.i18n || {};

	// Gefilterte Platzhalter
	const filteredGroups = useMemo( () => {
		if ( ! search ) {
			return placeholders;
		}

		const searchLower = search.toLowerCase();
		const filtered = {};

		Object.entries( placeholders ).forEach( ( [ groupKey, group ] ) => {
			if ( ! group.items ) {
				return;
			}

			const filteredItems = {};

			Object.entries( group.items ).forEach( ( [ key, item ] ) => {
				const matchesKey = key.toLowerCase().includes( searchLower );
				const matchesLabel = item.label?.toLowerCase().includes( searchLower );
				const matchesDescription = item.description?.toLowerCase().includes( searchLower );

				if ( matchesKey || matchesLabel || matchesDescription ) {
					filteredItems[ key ] = item;
				}
			} );

			if ( Object.keys( filteredItems ).length > 0 ) {
				filtered[ groupKey ] = {
					...group,
					items: filteredItems,
				};
			}
		} );

		return filtered;
	}, [ placeholders, search ] );

	/**
	 * Platzhalter auswählen
	 *
	 * @param {string} key Platzhalter-Key
	 */
	const handleSelect = ( key ) => {
		if ( onSelect ) {
			onSelect( key );
		}

		if ( compact ) {
			setIsOpen( false );
		}
	};

	/**
	 * Inline-Liste rendern (für Sidebar)
	 */
	const renderInlineList = () => (
		<Card className="rp-placeholder-picker">
			<CardHeader>
				<h3>{ i18n.placeholders || 'Platzhalter' }</h3>
			</CardHeader>
			<CardBody>
				{ showSearch && (
					<SearchControl
						value={ search }
						onChange={ setSearch }
						placeholder={ i18n.searchPlaceholders || 'Suchen...' }
					/>
				) }

				<div className="rp-placeholder-picker__groups">
					{ Object.entries( filteredGroups ).map( ( [ groupKey, group ] ) => (
						<div key={ groupKey } className="rp-placeholder-picker__group">
							<h4 className="rp-placeholder-picker__group-title">
								{ group.label || groupKey }
							</h4>
							<ul className="rp-placeholder-picker__items">
								{ Object.entries( group.items || {} ).map( ( [ key, item ] ) => (
									<li key={ key } className="rp-placeholder-picker__item">
										<button
											type="button"
											className="rp-placeholder-picker__button"
											onClick={ () => handleSelect( key ) }
											title={ item.description || '' }
										>
											<code className="rp-placeholder-picker__code">
												{ `{${ key }}` }
											</code>
											<span className="rp-placeholder-picker__label">
												{ item.label || key }
											</span>
										</button>
									</li>
								) ) }
							</ul>
						</div>
					) ) }

					{ Object.keys( filteredGroups ).length === 0 && (
						<p className="rp-placeholder-picker__empty">
							{ i18n.noPlaceholdersFound || 'Keine Platzhalter gefunden.' }
						</p>
					) }
				</div>
			</CardBody>
		</Card>
	);

	/**
	 * Popover rendern (für kompakte Darstellung)
	 */
	const renderPopover = () => (
		<>
			<Button
				icon={ plus }
				variant="secondary"
				onClick={ () => setIsOpen( ! isOpen ) }
				className="rp-placeholder-picker__trigger"
			>
				{ buttonLabel || i18n.insertPlaceholder || 'Platzhalter einfügen' }
			</Button>

			{ isOpen && (
				<Popover
					onClose={ () => setIsOpen( false ) }
					placement="bottom-start"
					className="rp-placeholder-picker__popover"
				>
					<div className="rp-placeholder-picker__popover-content">
						{ showSearch && (
							<SearchControl
								value={ search }
								onChange={ setSearch }
								placeholder={ i18n.searchPlaceholders || 'Suchen...' }
							/>
						) }

						<div className="rp-placeholder-picker__groups">
							{ Object.entries( filteredGroups ).map( ( [ groupKey, group ] ) => (
								<div key={ groupKey } className="rp-placeholder-picker__group">
									<h4 className="rp-placeholder-picker__group-title">
										{ group.label || groupKey }
									</h4>
									<ul className="rp-placeholder-picker__items">
										{ Object.entries( group.items || {} ).map( ( [ key, item ] ) => (
											<li key={ key } className="rp-placeholder-picker__item">
												<button
													type="button"
													className="rp-placeholder-picker__button"
													onClick={ () => handleSelect( key ) }
													title={ item.description || '' }
												>
													<code className="rp-placeholder-picker__code">
														{ `{${ key }}` }
													</code>
													<span className="rp-placeholder-picker__label">
														{ item.label || key }
													</span>
												</button>
											</li>
										) ) }
									</ul>
								</div>
							) ) }

							{ Object.keys( filteredGroups ).length === 0 && (
								<p className="rp-placeholder-picker__empty">
									{ i18n.noPlaceholdersFound || 'Keine Platzhalter gefunden.' }
								</p>
							) }
						</div>
					</div>
				</Popover>
			) }
		</>
	);

	return compact ? renderPopover() : renderInlineList();
}

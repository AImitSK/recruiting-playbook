/**
 * FieldTypeSelector Component
 *
 * Modal dialog for selecting a new field type to add.
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Badge } from '../../components/ui/badge';
import {
	X,
	Type,
	AlignLeft,
	Mail,
	Phone,
	Hash,
	List,
	Circle,
	CheckSquare,
	Calendar,
	Upload,
	Link,
	Heading,
	Search,
	Lock,
} from 'lucide-react';

/**
 * Icon mapping for field types
 */
const fieldTypeIcons = {
	text: Type,
	textarea: AlignLeft,
	email: Mail,
	phone: Phone,
	number: Hash,
	select: List,
	radio: Circle,
	checkbox: CheckSquare,
	date: Calendar,
	file: Upload,
	url: Link,
	heading: Heading,
};

/**
 * Category labels and order
 */
const CATEGORIES = {
	basic: { label: 'Basis-Felder', order: 1 },
	choice: { label: 'Auswahl-Felder', order: 2 },
	advanced: { label: 'Erweiterte Felder', order: 3 },
	layout: { label: 'Layout-Elemente', order: 4 },
};

/**
 * FieldTypeSelector component
 *
 * @param {Object} props Component props
 * @param {Object} props.fieldTypes Available field types
 * @param {Function} props.onSelect   Selection handler
 * @param {Function} props.onClose    Close handler
 * @param {boolean} props.isPro      Pro feature access
 * @param {Object} props.i18n        Translations
 */
export default function FieldTypeSelector( { fieldTypes, onSelect, onClose, isPro, i18n } ) {
	const [ searchQuery, setSearchQuery ] = useState( '' );

	// Group field types by category
	const groupedTypes = useMemo( () => {
		const groups = {};

		Object.entries( fieldTypes ).forEach( ( [ key, type ] ) => {
			const category = type.category || 'basic';

			if ( ! groups[ category ] ) {
				groups[ category ] = [];
			}

			groups[ category ].push( {
				key,
				...type,
			} );
		} );

		// Sort groups by category order
		const sortedGroups = Object.entries( groups ).sort( ( a, b ) => {
			const orderA = CATEGORIES[ a[ 0 ] ]?.order || 99;
			const orderB = CATEGORIES[ b[ 0 ] ]?.order || 99;
			return orderA - orderB;
		} );

		return sortedGroups;
	}, [ fieldTypes ] );

	// Filter by search query
	const filteredTypes = useMemo( () => {
		if ( ! searchQuery ) {
			return groupedTypes;
		}

		const query = searchQuery.toLowerCase();

		return groupedTypes
			.map( ( [ category, types ] ) => [
				category,
				types.filter(
					( type ) =>
						type.label?.toLowerCase().includes( query ) ||
						type.key.toLowerCase().includes( query )
				),
			] )
			.filter( ( [ _, types ] ) => types.length > 0 );
	}, [ groupedTypes, searchQuery ] );

	// Check if a field type requires Pro
	const requiresPro = ( typeKey ) => {
		const proTypes = [ 'file', 'date' ];
		return ! isPro && proTypes.includes( typeKey );
	};

	return (
		<div className="rp-field-type-selector" style={ { position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center' } }>
			{ /* Backdrop */ }
			<div
				style={ { position: 'absolute', inset: 0, backgroundColor: 'rgba(0, 0, 0, 0.5)' } }
				onClick={ onClose }
			/>

			{ /* Modal */ }
			<Card style={ { position: 'relative', zIndex: 10, width: '100%', maxWidth: '42rem', maxHeight: '80vh', overflow: 'hidden', display: 'flex', flexDirection: 'column' } }>
				<CardHeader style={ { borderBottom: '1px solid #e5e7eb' } }>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
						<div>
							<CardTitle>
								{ i18n?.selectFieldType || __( 'Feldtyp wählen', 'recruiting-playbook' ) }
							</CardTitle>
							<CardDescription>
								{ i18n?.selectFieldTypeDescription || __( 'Wählen Sie den Feldtyp für das neue Feld', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Button variant="ghost" size="sm" onClick={ onClose }>
							<X style={ { height: '1rem', width: '1rem' } } />
						</Button>
					</div>

					{ /* Search */ }
					<div style={ { position: 'relative', marginTop: '1rem' } }>
						<Search style={ { position: 'absolute', left: '0.75rem', top: '50%', transform: 'translateY(-50%)', height: '1rem', width: '1rem', color: '#9ca3af' } } />
						<Input
							value={ searchQuery }
							onChange={ ( e ) => setSearchQuery( e.target.value ) }
							placeholder={ i18n?.searchFieldTypes || __( 'Feldtyp suchen...', 'recruiting-playbook' ) }
							style={ { paddingLeft: '2.5rem' } }
						/>
					</div>
				</CardHeader>

				<CardContent style={ { overflowY: 'auto', padding: '1.5rem' } }>
					{ filteredTypes.length === 0 ? (
						<div style={ { textAlign: 'center', padding: '2rem 0', color: '#6b7280' } }>
							<p style={ { margin: 0 } }>{ i18n?.noFieldTypesFound || __( 'Keine Feldtypen gefunden', 'recruiting-playbook' ) }</p>
						</div>
					) : (
						<div style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
							{ filteredTypes.map( ( [ category, types ] ) => (
								<div key={ category }>
									<h3 style={ { fontSize: '0.875rem', fontWeight: 500, color: '#6b7280', marginBottom: '0.75rem' } }>
										{ i18n?.[ `category${ category.charAt( 0 ).toUpperCase() + category.slice( 1 ) }` ] ||
											CATEGORIES[ category ]?.label ||
											category }
									</h3>

									<div style={ { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '0.75rem' } }>
										{ types.map( ( type ) => {
											const IconComponent = fieldTypeIcons[ type.key ] || Type;
											const isProType = requiresPro( type.key );

											return (
												<button
													key={ type.key }
													style={ {
														display: 'flex',
														flexDirection: 'column',
														alignItems: 'center',
														gap: '0.5rem',
														padding: '1rem',
														borderRadius: '0.5rem',
														border: '2px solid #e5e7eb',
														transition: 'all 0.15s',
														backgroundColor: isProType ? '#f9fafb' : '#fff',
														cursor: isProType ? 'not-allowed' : 'pointer',
														opacity: isProType ? 0.6 : 1,
													} }
													onClick={ () => ! isProType && onSelect( type.key ) }
													disabled={ isProType }
												>
													<div style={ { position: 'relative' } }>
														<IconComponent style={ { height: '1.5rem', width: '1.5rem', color: '#374151' } } />
														{ isProType && (
															<Lock style={ { position: 'absolute', top: '-0.25rem', right: '-0.25rem', height: '0.75rem', width: '0.75rem', color: '#f59e0b' } } />
														) }
													</div>
													<span style={ { fontSize: '0.875rem', fontWeight: 500, textAlign: 'center' } }>
														{ type.label }
													</span>
													{ isProType && (
														<Badge variant="outline" style={ { fontSize: '0.75rem' } }>
															Pro
														</Badge>
													) }
												</button>
											);
										} ) }
									</div>
								</div>
							) ) }
						</div>
					) }
				</CardContent>

				{ /* Footer */ }
				<div style={ { borderTop: '1px solid #e5e7eb', padding: '1rem', backgroundColor: '#f9fafb' } }>
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
						<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
							{ Object.keys( fieldTypes ).length } { i18n?.fieldTypesAvailable || __( 'Feldtypen verfügbar', 'recruiting-playbook' ) }
						</p>
						<Button variant="outline" onClick={ onClose }>
							{ i18n?.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
						</Button>
					</div>
				</div>
			</Card>
		</div>
	);
}

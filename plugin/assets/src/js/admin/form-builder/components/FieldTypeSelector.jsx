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
		<div className="rp-field-type-selector fixed inset-0 z-50 flex items-center justify-center">
			{ /* Backdrop */ }
			<div
				className="absolute inset-0 bg-black/50"
				onClick={ onClose }
			/>

			{ /* Modal */ }
			<Card className="relative z-10 w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
				<CardHeader className="border-b">
					<div className="flex items-center justify-between">
						<div>
							<CardTitle>
								{ i18n?.selectFieldType || __( 'Feldtyp wählen', 'recruiting-playbook' ) }
							</CardTitle>
							<CardDescription>
								{ i18n?.selectFieldTypeDescription || __( 'Wählen Sie den Feldtyp für das neue Feld', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Button variant="ghost" size="sm" onClick={ onClose }>
							<X className="h-4 w-4" />
						</Button>
					</div>

					{ /* Search */ }
					<div className="relative mt-4">
						<Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
						<Input
							value={ searchQuery }
							onChange={ ( e ) => setSearchQuery( e.target.value ) }
							placeholder={ i18n?.searchFieldTypes || __( 'Feldtyp suchen...', 'recruiting-playbook' ) }
							className="pl-10"
						/>
					</div>
				</CardHeader>

				<CardContent className="overflow-y-auto p-6">
					{ filteredTypes.length === 0 ? (
						<div className="text-center py-8 text-gray-500">
							<p>{ i18n?.noFieldTypesFound || __( 'Keine Feldtypen gefunden', 'recruiting-playbook' ) }</p>
						</div>
					) : (
						<div className="space-y-6">
							{ filteredTypes.map( ( [ category, types ] ) => (
								<div key={ category }>
									<h3 className="text-sm font-medium text-gray-500 mb-3">
										{ i18n?.[ `category${ category.charAt( 0 ).toUpperCase() + category.slice( 1 ) }` ] ||
											CATEGORIES[ category ]?.label ||
											category }
									</h3>

									<div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
										{ types.map( ( type ) => {
											const IconComponent = fieldTypeIcons[ type.key ] || Type;
											const isProType = requiresPro( type.key );

											return (
												<button
													key={ type.key }
													className={ `
														flex flex-col items-center gap-2 p-4 rounded-lg border-2 transition-all
														${ isProType
		? 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-60'
		: 'border-gray-200 hover:border-blue-500 hover:bg-blue-50 cursor-pointer'
}
													` }
													onClick={ () => ! isProType && onSelect( type.key ) }
													disabled={ isProType }
												>
													<div className="relative">
														<IconComponent className="h-6 w-6 text-gray-700" />
														{ isProType && (
															<Lock className="absolute -top-1 -right-1 h-3 w-3 text-amber-500" />
														) }
													</div>
													<span className="text-sm font-medium text-center">
														{ type.label }
													</span>
													{ isProType && (
														<Badge variant="outline" className="text-xs">
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
				<div className="border-t p-4 bg-gray-50">
					<div className="flex items-center justify-between">
						<p className="text-xs text-gray-500">
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

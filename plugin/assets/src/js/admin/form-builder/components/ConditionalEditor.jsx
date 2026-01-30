/**
 * ConditionalEditor Component
 *
 * Editor for conditional logic rules (show/hide fields based on conditions).
 *
 * @package RecruitingPlaybook
 */

import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Switch } from '../../components/ui/switch';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '../../components/ui/select';
import { Trash2, Plus } from 'lucide-react';

/**
 * Available operators with labels
 */
const OPERATORS = [
	{ value: 'equals', label: 'ist gleich', needsValue: true },
	{ value: 'not_equals', label: 'ist nicht gleich', needsValue: true },
	{ value: 'contains', label: 'enthält', needsValue: true },
	{ value: 'not_contains', label: 'enthält nicht', needsValue: true },
	{ value: 'starts_with', label: 'beginnt mit', needsValue: true },
	{ value: 'ends_with', label: 'endet mit', needsValue: true },
	{ value: 'empty', label: 'ist leer', needsValue: false },
	{ value: 'not_empty', label: 'ist nicht leer', needsValue: false },
	{ value: 'checked', label: 'ist aktiviert', needsValue: false },
	{ value: 'not_checked', label: 'ist nicht aktiviert', needsValue: false },
	{ value: 'greater_than', label: 'größer als', needsValue: true },
	{ value: 'less_than', label: 'kleiner als', needsValue: true },
	{ value: 'in', label: 'ist in Liste', needsValue: true },
];

/**
 * Single condition row
 *
 * @param {Object} props Component props
 */
function ConditionRow( { condition, allFields, onChange, onRemove, i18n } ) {
	const selectedOperator = OPERATORS.find( ( op ) => op.value === condition.operator );
	const needsValue = selectedOperator?.needsValue ?? true;

	return (
		<div className="flex items-center gap-2 p-3 bg-gray-50 rounded border border-gray-200">
			{ /* Field selector */ }
			<Select
				value={ condition.field || '' }
				onValueChange={ ( value ) => onChange( { ...condition, field: value } ) }
			>
				<SelectTrigger className="w-[150px]">
					<SelectValue placeholder={ i18n?.selectField || __( 'Feld wählen', 'recruiting-playbook' ) } />
				</SelectTrigger>
				<SelectContent>
					{ allFields.map( ( field ) => (
						<SelectItem key={ field.id } value={ field.field_key }>
							{ field.label }
						</SelectItem>
					) ) }
				</SelectContent>
			</Select>

			{ /* Operator selector */ }
			<Select
				value={ condition.operator || 'equals' }
				onValueChange={ ( value ) => onChange( { ...condition, operator: value } ) }
			>
				<SelectTrigger className="w-[150px]">
					<SelectValue />
				</SelectTrigger>
				<SelectContent>
					{ OPERATORS.map( ( op ) => (
						<SelectItem key={ op.value } value={ op.value }>
							{ i18n?.[ `op${ op.value.charAt( 0 ).toUpperCase() + op.value.slice( 1 ).replace( /_/g, '' ) }` ] || op.label }
						</SelectItem>
					) ) }
				</SelectContent>
			</Select>

			{ /* Value input */ }
			{ needsValue && (
				<Input
					value={ condition.value || '' }
					onChange={ ( e ) => onChange( { ...condition, value: e.target.value } ) }
					placeholder={ i18n?.value || __( 'Wert', 'recruiting-playbook' ) }
					className="flex-1"
				/>
			) }

			{ /* Remove button */ }
			<Button
				variant="ghost"
				size="sm"
				onClick={ onRemove }
				className="text-red-500 hover:text-red-700 hover:bg-red-50"
			>
				<Trash2 className="h-4 w-4" />
			</Button>
		</div>
	);
}

/**
 * ConditionalEditor component
 *
 * @param {Object} props Component props
 * @param {Object} props.conditional Conditional logic configuration
 * @param {Function} props.onChange    Change handler
 * @param {Array}  props.allFields   All available fields for reference
 * @param {Object} props.i18n         Translations
 */
export default function ConditionalEditor( { conditional = {}, onChange, allFields = [], i18n } ) {
	const isEnabled = conditional.enabled || false;
	const conditions = conditional.conditions || [];
	const logic = conditional.logic || 'and';

	// Toggle conditional logic
	const handleToggle = useCallback(
		( enabled ) => {
			onChange( {
				...conditional,
				enabled,
				conditions: enabled && conditions.length === 0 ? [ { field: '', operator: 'equals', value: '' } ] : conditions,
			} );
		},
		[ conditional, conditions, onChange ]
	);

	// Update logic operator (and/or)
	const handleLogicChange = useCallback(
		( newLogic ) => {
			onChange( { ...conditional, logic: newLogic } );
		},
		[ conditional, onChange ]
	);

	// Update condition
	const handleConditionChange = useCallback(
		( index, updatedCondition ) => {
			const newConditions = conditions.map( ( c, idx ) =>
				idx === index ? updatedCondition : c
			);
			onChange( { ...conditional, conditions: newConditions } );
		},
		[ conditional, conditions, onChange ]
	);

	// Add condition
	const handleAddCondition = useCallback( () => {
		onChange( {
			...conditional,
			conditions: [ ...conditions, { field: '', operator: 'equals', value: '' } ],
		} );
	}, [ conditional, conditions, onChange ] );

	// Remove condition
	const handleRemoveCondition = useCallback(
		( index ) => {
			const newConditions = conditions.filter( ( _, idx ) => idx !== index );
			onChange( {
				...conditional,
				conditions: newConditions,
				enabled: newConditions.length > 0,
			} );
		},
		[ conditional, conditions, onChange ]
	);

	// Filter out fields that can't be used as source
	const sourceFields = allFields.filter( ( f ) => f.type !== 'heading' && f.type !== 'file' );

	return (
		<div className="rp-conditional-editor space-y-4">
			{ /* Enable toggle */ }
			<div className="flex items-center justify-between">
				<div className="space-y-1">
					<Label htmlFor="conditional_enabled">
						{ i18n?.conditionalEnable || __( 'Bedingte Anzeige aktivieren', 'recruiting-playbook' ) }
					</Label>
					<p className="text-xs text-gray-500">
						{ i18n?.conditionalHelp || __( 'Dieses Feld nur anzeigen, wenn bestimmte Bedingungen erfüllt sind', 'recruiting-playbook' ) }
					</p>
				</div>
				<Switch
					id="conditional_enabled"
					checked={ isEnabled }
					onCheckedChange={ handleToggle }
				/>
			</div>

			{ isEnabled && (
				<>
					{ /* Logic selector (AND/OR) */ }
					{ conditions.length > 1 && (
						<div className="flex items-center gap-2">
							<Label>{ i18n?.showWhen || __( 'Anzeigen wenn', 'recruiting-playbook' ) }</Label>
							<Select
								value={ logic }
								onValueChange={ handleLogicChange }
							>
								<SelectTrigger className="w-[120px]">
									<SelectValue />
								</SelectTrigger>
								<SelectContent>
									<SelectItem value="and">
										{ i18n?.conditionAllMatch || __( 'ALLE', 'recruiting-playbook' ) }
									</SelectItem>
									<SelectItem value="or">
										{ i18n?.conditionAnyMatch || __( 'EINE', 'recruiting-playbook' ) }
									</SelectItem>
								</SelectContent>
							</Select>
							<span className="text-sm text-gray-600">
								{ logic === 'and'
									? ( i18n?.conditionsMatch || __( 'Bedingungen zutreffen', 'recruiting-playbook' ) )
									: ( i18n?.conditionMatches || __( 'Bedingung zutrifft', 'recruiting-playbook' ) )
								}
							</span>
						</div>
					) }

					{ /* Conditions list */ }
					<div className="space-y-2">
						{ conditions.map( ( condition, index ) => (
							<div key={ index }>
								{ index > 0 && (
									<div className="text-center text-xs text-gray-500 py-1">
										{ logic === 'and'
											? ( i18n?.conditionAnd || __( 'UND', 'recruiting-playbook' ) )
											: ( i18n?.conditionOr || __( 'ODER', 'recruiting-playbook' ) )
										}
									</div>
								) }
								<ConditionRow
									condition={ condition }
									allFields={ sourceFields }
									onChange={ ( updated ) => handleConditionChange( index, updated ) }
									onRemove={ () => handleRemoveCondition( index ) }
									i18n={ i18n }
								/>
							</div>
						) ) }
					</div>

					{ /* Add condition button */ }
					<Button
						variant="outline"
						size="sm"
						onClick={ handleAddCondition }
					>
						<Plus className="h-4 w-4 mr-1" />
						{ i18n?.addCondition || __( 'Bedingung hinzufügen', 'recruiting-playbook' ) }
					</Button>

					{ /* Help text */ }
					<p className="text-xs text-gray-500 pt-2 border-t">
						{ i18n?.conditionalTip || __( 'Mit bedingter Anzeige können Sie Felder basierend auf anderen Feldwerten ein- oder ausblenden.', 'recruiting-playbook' ) }
					</p>
				</>
			) }

			{ allFields.length === 0 && isEnabled && (
				<div className="text-center py-4 text-gray-500 text-sm border-2 border-dashed rounded">
					{ i18n?.noFieldsForConditional || __( 'Keine anderen Felder vorhanden, die als Bedingung verwendet werden können.', 'recruiting-playbook' ) }
				</div>
			) }
		</div>
	);
}

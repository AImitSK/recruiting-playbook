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
import { Select, SelectOption } from '../../components/ui/select';
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
		<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem', padding: '0.75rem', backgroundColor: '#f9fafb', borderRadius: '0.25rem', border: '1px solid #e5e7eb' } }>
			{ /* Field selector */ }
			<Select
				value={ condition.field || '' }
				onChange={ ( e ) => onChange( { ...condition, field: e.target.value } ) }
				style={ { width: '150px' } }
			>
				<SelectOption value="">
					{ i18n?.selectField || __( 'Feld wählen', 'recruiting-playbook' ) }
				</SelectOption>
				{ allFields.map( ( field ) => (
					<SelectOption key={ field.id } value={ field.field_key }>
						{ field.label }
					</SelectOption>
				) ) }
			</Select>

			{ /* Operator selector */ }
			<Select
				value={ condition.operator || 'equals' }
				onChange={ ( e ) => onChange( { ...condition, operator: e.target.value } ) }
				style={ { width: '150px' } }
			>
				{ OPERATORS.map( ( op ) => (
					<SelectOption key={ op.value } value={ op.value }>
						{ i18n?.[ `op${ op.value.charAt( 0 ).toUpperCase() + op.value.slice( 1 ).replace( /_/g, '' ) }` ] || op.label }
					</SelectOption>
				) ) }
			</Select>

			{ /* Value input */ }
			{ needsValue && (
				<Input
					value={ condition.value || '' }
					onChange={ ( e ) => onChange( { ...condition, value: e.target.value } ) }
					placeholder={ i18n?.value || __( 'Wert', 'recruiting-playbook' ) }
					style={ { flex: 1 } }
				/>
			) }

			{ /* Remove button */ }
			<Button
				variant="ghost"
				size="sm"
				onClick={ onRemove }
				style={ { color: '#ef4444' } }
			>
				<Trash2 style={ { height: '1rem', width: '1rem' } } />
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
		( e ) => {
			onChange( { ...conditional, logic: e.target.value } );
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
		<div className="rp-conditional-editor" style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
			{ /* Enable toggle */ }
			<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
				<div style={ { display: 'flex', flexDirection: 'column', gap: '0.25rem' } }>
					<Label htmlFor="conditional_enabled">
						{ i18n?.conditionalEnable || __( 'Bedingte Anzeige aktivieren', 'recruiting-playbook' ) }
					</Label>
					<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
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
						<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
							<Label>{ i18n?.showWhen || __( 'Anzeigen wenn', 'recruiting-playbook' ) }</Label>
							<Select
								value={ logic }
								onChange={ handleLogicChange }
								style={ { width: '120px' } }
							>
								<SelectOption value="and">
									{ i18n?.conditionAllMatch || __( 'ALLE', 'recruiting-playbook' ) }
								</SelectOption>
								<SelectOption value="or">
									{ i18n?.conditionAnyMatch || __( 'EINE', 'recruiting-playbook' ) }
								</SelectOption>
							</Select>
							<span style={ { fontSize: '0.875rem', color: '#4b5563' } }>
								{ logic === 'and'
									? ( i18n?.conditionsMatch || __( 'Bedingungen zutreffen', 'recruiting-playbook' ) )
									: ( i18n?.conditionMatches || __( 'Bedingung zutrifft', 'recruiting-playbook' ) )
								}
							</span>
						</div>
					) }

					{ /* Conditions list */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						{ conditions.map( ( condition, index ) => (
							<div key={ index }>
								{ index > 0 && (
									<div style={ { textAlign: 'center', fontSize: '0.75rem', color: '#6b7280', padding: '0.25rem 0' } }>
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
						<Plus style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } />
						{ i18n?.addCondition || __( 'Bedingung hinzufügen', 'recruiting-playbook' ) }
					</Button>

					{ /* Help text */ }
					<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0, paddingTop: '0.5rem', borderTop: '1px solid #e5e7eb' } }>
						{ i18n?.conditionalTip || __( 'Mit bedingter Anzeige können Sie Felder basierend auf anderen Feldwerten ein- oder ausblenden.', 'recruiting-playbook' ) }
					</p>
				</>
			) }

			{ allFields.length === 0 && isEnabled && (
				<div style={ { textAlign: 'center', padding: '1rem 0', color: '#6b7280', fontSize: '0.875rem', border: '2px dashed #e5e7eb', borderRadius: '0.25rem' } }>
					{ i18n?.noFieldsForConditional || __( 'Keine anderen Felder vorhanden, die als Bedingung verwendet werden können.', 'recruiting-playbook' ) }
				</div>
			) }
		</div>
	);
}

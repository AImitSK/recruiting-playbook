/**
 * ValidationEditor Component
 *
 * Editor for field validation rules.
 *
 * @package RecruitingPlaybook
 */

import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Textarea } from '../../components/ui/textarea';

/**
 * ValidationEditor component
 *
 * @param {Object} props Component props
 * @param {Object} props.validation Validation rules object
 * @param {Function} props.onChange   Change handler
 * @param {string} props.fieldType  Field type
 * @param {Object} props.i18n        Translations
 */
export default function ValidationEditor( { validation = {}, onChange, fieldType, i18n } ) {
	// Update validation rule
	const updateRule = useCallback(
		( key, value ) => {
			onChange( {
				...validation,
				[ key ]: value === '' ? undefined : value,
			} );
		},
		[ validation, onChange ]
	);

	// Determine which rules to show based on field type
	const showMinMax = [ 'text', 'textarea', 'email', 'phone', 'url' ].includes( fieldType );
	const showMinMaxValue = [ 'number' ].includes( fieldType );
	const showPattern = [ 'text', 'phone' ].includes( fieldType );
	const showFileRules = [ 'file' ].includes( fieldType );
	const showDateRules = [ 'date' ].includes( fieldType );

	return (
		<div className="rp-validation-editor space-y-4">
			{ /* Min/Max Length for text fields */ }
			{ showMinMax && (
				<div className="grid grid-cols-2 gap-4">
					<div className="space-y-2">
						<Label htmlFor="min_length">
							{ i18n?.minLength || __( 'Minimale Länge', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="min_length"
							type="number"
							min="0"
							value={ validation.min_length || '' }
							onChange={ ( e ) => updateRule( 'min_length', e.target.value ? parseInt( e.target.value, 10 ) : '' ) }
							placeholder="0"
						/>
					</div>

					<div className="space-y-2">
						<Label htmlFor="max_length">
							{ i18n?.maxLength || __( 'Maximale Länge', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="max_length"
							type="number"
							min="0"
							value={ validation.max_length || '' }
							onChange={ ( e ) => updateRule( 'max_length', e.target.value ? parseInt( e.target.value, 10 ) : '' ) }
							placeholder={ i18n?.unlimited || __( 'Unbegrenzt', 'recruiting-playbook' ) }
						/>
					</div>
				</div>
			) }

			{ /* Min/Max Value for number fields */ }
			{ showMinMaxValue && (
				<div className="grid grid-cols-2 gap-4">
					<div className="space-y-2">
						<Label htmlFor="min_value">
							{ i18n?.minValue || __( 'Minimalwert', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="min_value"
							type="number"
							value={ validation.min_value ?? '' }
							onChange={ ( e ) => updateRule( 'min_value', e.target.value ? parseFloat( e.target.value ) : '' ) }
							placeholder={ i18n?.noLimit || __( 'Kein Limit', 'recruiting-playbook' ) }
						/>
					</div>

					<div className="space-y-2">
						<Label htmlFor="max_value">
							{ i18n?.maxValue || __( 'Maximalwert', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="max_value"
							type="number"
							value={ validation.max_value ?? '' }
							onChange={ ( e ) => updateRule( 'max_value', e.target.value ? parseFloat( e.target.value ) : '' ) }
							placeholder={ i18n?.noLimit || __( 'Kein Limit', 'recruiting-playbook' ) }
						/>
					</div>
				</div>
			) }

			{ /* Pattern for text/phone */ }
			{ showPattern && (
				<div className="space-y-2">
					<Label htmlFor="pattern">
						{ i18n?.pattern || __( 'Regex-Pattern', 'recruiting-playbook' ) }
					</Label>
					<Input
						id="pattern"
						value={ validation.pattern || '' }
						onChange={ ( e ) => updateRule( 'pattern', e.target.value ) }
						placeholder="^[A-Z][a-z]+$"
					/>
					<p className="text-xs text-gray-500">
						{ i18n?.patternHelp || __( 'Regulärer Ausdruck für die Validierung (z.B. ^[0-9]+$ für nur Zahlen)', 'recruiting-playbook' ) }
					</p>
				</div>
			) }

			{ /* Date rules */ }
			{ showDateRules && (
				<div className="grid grid-cols-2 gap-4">
					<div className="space-y-2">
						<Label htmlFor="min_date">
							{ i18n?.minDate || __( 'Frühestes Datum', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="min_date"
							type="date"
							value={ validation.min_date || '' }
							onChange={ ( e ) => updateRule( 'min_date', e.target.value ) }
						/>
					</div>

					<div className="space-y-2">
						<Label htmlFor="max_date">
							{ i18n?.maxDate || __( 'Spätestes Datum', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="max_date"
							type="date"
							value={ validation.max_date || '' }
							onChange={ ( e ) => updateRule( 'max_date', e.target.value ) }
						/>
					</div>
				</div>
			) }

			{ /* File rules */ }
			{ showFileRules && (
				<>
					<div className="space-y-2">
						<Label htmlFor="allowed_types">
							{ i18n?.allowedTypes || __( 'Erlaubte Dateitypen', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="allowed_types"
							value={ validation.allowed_types || '' }
							onChange={ ( e ) => updateRule( 'allowed_types', e.target.value ) }
							placeholder=".pdf,.doc,.docx"
						/>
						<p className="text-xs text-gray-500">
							{ i18n?.allowedTypesHelp || __( 'Kommagetrennte Liste von Dateiendungen', 'recruiting-playbook' ) }
						</p>
					</div>

					<div className="grid grid-cols-2 gap-4">
						<div className="space-y-2">
							<Label htmlFor="max_file_size">
								{ i18n?.maxFileSize || __( 'Max. Dateigröße (MB)', 'recruiting-playbook' ) }
							</Label>
							<Input
								id="max_file_size"
								type="number"
								min="1"
								value={ validation.max_file_size || '' }
								onChange={ ( e ) => updateRule( 'max_file_size', e.target.value ? parseInt( e.target.value, 10 ) : '' ) }
								placeholder="10"
							/>
						</div>

						<div className="space-y-2">
							<Label htmlFor="max_files">
								{ i18n?.maxFiles || __( 'Max. Anzahl Dateien', 'recruiting-playbook' ) }
							</Label>
							<Input
								id="max_files"
								type="number"
								min="1"
								value={ validation.max_files || '' }
								onChange={ ( e ) => updateRule( 'max_files', e.target.value ? parseInt( e.target.value, 10 ) : '' ) }
								placeholder="5"
							/>
						</div>
					</div>
				</>
			) }

			{ /* Custom error message */ }
			<div className="space-y-2">
				<Label htmlFor="custom_error">
					{ i18n?.customError || __( 'Eigene Fehlermeldung', 'recruiting-playbook' ) }
				</Label>
				<Textarea
					id="custom_error"
					value={ validation.custom_error || '' }
					onChange={ ( e ) => updateRule( 'custom_error', e.target.value ) }
					placeholder={ i18n?.customErrorPlaceholder || __( 'Wird bei Validierungsfehlern angezeigt', 'recruiting-playbook' ) }
					rows={ 2 }
				/>
			</div>

			{ /* Help text */ }
			<p className="text-xs text-gray-500 pt-2 border-t">
				{ i18n?.validationHelp || __( 'Validierungsregeln werden beim Absenden des Formulars geprüft. Leer lassen für keine Einschränkung.', 'recruiting-playbook' ) }
			</p>
		</div>
	);
}

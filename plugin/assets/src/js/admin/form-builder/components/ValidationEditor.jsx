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
		<div className="rp-validation-editor" style={ { display: 'flex', flexDirection: 'column', gap: '1rem' } }>
			{ /* Min/Max Length for text fields */ }
			{ showMinMax && (
				<div style={ { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '1rem' } }>
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="min_length">
							{ i18n?.minLength || __( 'Minimum Length', 'recruiting-playbook' ) }
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

					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="max_length">
							{ i18n?.maxLength || __( 'Maximum Length', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="max_length"
							type="number"
							min="0"
							value={ validation.max_length || '' }
							onChange={ ( e ) => updateRule( 'max_length', e.target.value ? parseInt( e.target.value, 10 ) : '' ) }
							placeholder={ i18n?.unlimited || __( 'Unlimited', 'recruiting-playbook' ) }
						/>
					</div>
				</div>
			) }

			{ /* Min/Max Value for number fields */ }
			{ showMinMaxValue && (
				<div style={ { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '1rem' } }>
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="min_value">
							{ i18n?.minValue || __( 'Minimum Value', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="min_value"
							type="number"
							value={ validation.min_value ?? '' }
							onChange={ ( e ) => updateRule( 'min_value', e.target.value ? parseFloat( e.target.value ) : '' ) }
							placeholder={ i18n?.noLimit || __( 'No Limit', 'recruiting-playbook' ) }
						/>
					</div>

					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="max_value">
							{ i18n?.maxValue || __( 'Maximum Value', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="max_value"
							type="number"
							value={ validation.max_value ?? '' }
							onChange={ ( e ) => updateRule( 'max_value', e.target.value ? parseFloat( e.target.value ) : '' ) }
							placeholder={ i18n?.noLimit || __( 'No Limit', 'recruiting-playbook' ) }
						/>
					</div>
				</div>
			) }

			{ /* Pattern for text/phone */ }
			{ showPattern && (
				<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
					<Label htmlFor="pattern">
						{ i18n?.pattern || __( 'Regex Pattern', 'recruiting-playbook' ) }
					</Label>
					<Input
						id="pattern"
						value={ validation.pattern || '' }
						onChange={ ( e ) => updateRule( 'pattern', e.target.value ) }
						placeholder="^[A-Z][a-z]+$"
					/>
					<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
						{ i18n?.patternHelp || __( 'Regular expression for validation (e.g., ^[0-9]+$ for numbers only)', 'recruiting-playbook' ) }
					</p>
				</div>
			) }

			{ /* Date rules */ }
			{ showDateRules && (
				<div style={ { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '1rem' } }>
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="min_date">
							{ i18n?.minDate || __( 'Earliest Date', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="min_date"
							type="date"
							value={ validation.min_date || '' }
							onChange={ ( e ) => updateRule( 'min_date', e.target.value ) }
						/>
					</div>

					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="max_date">
							{ i18n?.maxDate || __( 'Latest Date', 'recruiting-playbook' ) }
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
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="allowed_types">
							{ i18n?.allowedTypes || __( 'Allowed File Types', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="allowed_types"
							value={ validation.allowed_types || '' }
							onChange={ ( e ) => updateRule( 'allowed_types', e.target.value ) }
							placeholder=".pdf,.doc,.docx"
						/>
						<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
							{ i18n?.allowedTypesHelp || __( 'Comma-separated list of file extensions', 'recruiting-playbook' ) }
						</p>
					</div>

					<div style={ { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '1rem' } }>
						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="max_file_size">
								{ i18n?.maxFileSize || __( 'Max. File Size (MB)', 'recruiting-playbook' ) }
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

						<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
							<Label htmlFor="max_files">
								{ i18n?.maxFiles || __( 'Max. Number of Files', 'recruiting-playbook' ) }
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
			<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
				<Label htmlFor="custom_error">
					{ i18n?.customError || __( 'Custom Error Message', 'recruiting-playbook' ) }
				</Label>
				<Textarea
					id="custom_error"
					value={ validation.custom_error || '' }
					onChange={ ( e ) => updateRule( 'custom_error', e.target.value ) }
					placeholder={ i18n?.customErrorPlaceholder || __( 'Displayed when validation fails', 'recruiting-playbook' ) }
					rows={ 2 }
				/>
			</div>

			{ /* Help text */ }
			<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0, paddingTop: '0.5rem', borderTop: '1px solid #e5e7eb' } }>
				{ i18n?.validationHelp || __( 'Validation rules are checked when the form is submitted. Leave empty for no restrictions.', 'recruiting-playbook' ) }
			</p>
		</div>
	);
}

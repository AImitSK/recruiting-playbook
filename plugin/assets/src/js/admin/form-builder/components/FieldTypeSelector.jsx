/**
 * FieldTypeSelector Component
 *
 * Modal dialog for creating a new field with type selection and settings.
 * Redesigned with dropdown + inline settings form.
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Textarea } from '../../components/ui/textarea';
import { Switch } from '../../components/ui/switch';
import { Select, SelectOption } from '../../components/ui/select';
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
	Lock,
	ChevronDown,
} from 'lucide-react';
import OptionsEditor from './OptionsEditor';

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
 * Category labels for grouping in dropdown
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
 * @param {Object}   props            Component props
 * @param {Object}   props.fieldTypes Available field types
 * @param {Function} props.onSelect   Selection handler (receives type and settings)
 * @param {Function} props.onClose    Close handler
 * @param {boolean}  props.isPro      Pro feature access
 * @param {Object}   props.i18n       Translations
 */
export default function FieldTypeSelector( { fieldTypes, onSelect, onClose, isPro, i18n } ) {
	const [ selectedType, setSelectedType ] = useState( '' );
	const [ fieldSettings, setFieldSettings ] = useState( {
		label: '',
		placeholder: '',
		description: '',
		is_required: false,
		width: 'full',
		options: [],
	} );

	// Group and sort field types for dropdown
	const sortedFieldTypes = useMemo( () => {
		const entries = Object.entries( fieldTypes );

		// Sort by category order, then by label
		return entries.sort( ( a, b ) => {
			const catA = a[ 1 ].category || 'basic';
			const catB = b[ 1 ].category || 'basic';
			const orderA = CATEGORIES[ catA ]?.order || 99;
			const orderB = CATEGORIES[ catB ]?.order || 99;

			if ( orderA !== orderB ) {
				return orderA - orderB;
			}

			return ( a[ 1 ].label || a[ 0 ] ).localeCompare( b[ 1 ].label || b[ 0 ] );
		} );
	}, [ fieldTypes ] );

	// Check if a field type requires Pro
	const requiresPro = ( typeKey ) => {
		const proTypes = [ 'file', 'date' ];
		return ! isPro && proTypes.includes( typeKey );
	};

	// Check if selected type has options (select, radio, checkbox)
	const hasOptions = [ 'select', 'radio', 'checkbox' ].includes( selectedType );

	// Check if selected type supports placeholder
	const hasPlaceholder = [ 'text', 'textarea', 'email', 'phone', 'number', 'url', 'select' ].includes( selectedType );

	// Update field settings
	const updateSettings = ( key, value ) => {
		setFieldSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	// Handle type selection
	const handleTypeChange = ( e ) => {
		const newType = e.target.value;
		setSelectedType( newType );

		// Set default label based on type
		if ( newType && fieldTypes[ newType ] ) {
			setFieldSettings( ( prev ) => ( {
				...prev,
				label: prev.label || fieldTypes[ newType ].label || '',
				options: hasOptions ? prev.options : [],
			} ) );
		}
	};

	// Handle form submit
	const handleSubmit = () => {
		if ( ! selectedType || ! fieldSettings.label.trim() ) {
			return;
		}

		onSelect( selectedType, {
			...fieldSettings,
			label: fieldSettings.label.trim(),
		} );
	};

	// Close on Escape key
	const handleKeyDown = ( e ) => {
		if ( e.key === 'Escape' ) {
			onClose();
		}
	};

	// Check if form is valid
	const isValid = selectedType && fieldSettings.label.trim();

	return (
		<div
			className="rp-field-type-selector"
			style={ {
				position: 'fixed',
				inset: 0,
				zIndex: 100000,
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
			} }
			onKeyDown={ handleKeyDown }
		>
			{ /* Backdrop */ }
			<div
				style={ { position: 'absolute', inset: 0, backgroundColor: 'rgba(0, 0, 0, 0.5)' } }
				onClick={ onClose }
			/>

			{ /* Modal */ }
			<div
				style={ {
					position: 'relative',
					zIndex: 10,
					width: '100%',
					maxWidth: '32rem',
					maxHeight: '90vh',
					backgroundColor: 'white',
					borderRadius: '0.5rem',
					boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
					display: 'flex',
					flexDirection: 'column',
					overflow: 'hidden',
				} }
			>
				{ /* Header */ }
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						padding: '1rem 1.5rem',
						borderBottom: '1px solid #e5e7eb',
					} }
				>
					<h2 style={ { margin: 0, fontSize: '1.125rem', fontWeight: 600 } }>
						{ i18n?.createField || __( 'Neues Feld erstellen', 'recruiting-playbook' ) }
					</h2>
					<Button variant="ghost" size="sm" onClick={ onClose }>
						<X style={ { height: '1.25rem', width: '1.25rem' } } />
					</Button>
				</div>

				{ /* Content */ }
				<div
					style={ {
						padding: '1.5rem',
						overflowY: 'auto',
						flex: 1,
						display: 'flex',
						flexDirection: 'column',
						gap: '1.25rem',
					} }
				>
					{ /* Field Type Dropdown */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="field_type">
							{ i18n?.fieldType || __( 'Feldtyp', 'recruiting-playbook' ) }
							<span style={ { color: '#ef4444' } }> *</span>
						</Label>
						<div style={ { position: 'relative' } }>
							<select
								id="field_type"
								value={ selectedType }
								onChange={ handleTypeChange }
								style={ {
									width: '100%',
									padding: '0.5rem 2.5rem 0.5rem 2.5rem',
									fontSize: '0.875rem',
									borderRadius: '0.375rem',
									border: '1px solid #d1d5db',
									backgroundColor: 'white',
									appearance: 'none',
									cursor: 'pointer',
								} }
							>
								<option value="">
									{ i18n?.selectFieldType || __( 'Feldtyp wählen...', 'recruiting-playbook' ) }
								</option>
								{ sortedFieldTypes.map( ( [ key, type ] ) => {
									const isProType = requiresPro( key );
									return (
										<option
											key={ key }
											value={ key }
											disabled={ isProType }
										>
											{ type.label }{ isProType ? ' (Pro)' : '' }
										</option>
									);
								} ) }
							</select>

							{ /* Icon on left */ }
							<div
								style={ {
									position: 'absolute',
									left: '0.75rem',
									top: '50%',
									transform: 'translateY(-50%)',
									pointerEvents: 'none',
									color: '#6b7280',
								} }
							>
								{ selectedType && fieldTypeIcons[ selectedType ] ? (
									( () => {
										const IconComponent = fieldTypeIcons[ selectedType ];
										return <IconComponent style={ { height: '1rem', width: '1rem' } } />;
									} )()
								) : (
									<Type style={ { height: '1rem', width: '1rem' } } />
								) }
							</div>

							{ /* Chevron on right */ }
							<ChevronDown
								style={ {
									position: 'absolute',
									right: '0.75rem',
									top: '50%',
									transform: 'translateY(-50%)',
									height: '1rem',
									width: '1rem',
									color: '#6b7280',
									pointerEvents: 'none',
								} }
							/>
						</div>
					</div>

					{ /* Settings (only shown when type is selected) */ }
					{ selectedType && (
						<>
							{ /* Divider */ }
							<div style={ { borderTop: '1px solid #e5e7eb', margin: '0.25rem 0' } } />

							<h3 style={ { fontSize: '0.875rem', fontWeight: 600, color: '#374151', margin: 0 } }>
								{ i18n?.fieldSettings || __( 'Einstellungen', 'recruiting-playbook' ) }
							</h3>

							{ /* Label */ }
							<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
								<Label htmlFor="field_label">
									{ i18n?.fieldLabel || __( 'Bezeichnung', 'recruiting-playbook' ) }
									<span style={ { color: '#ef4444' } }> *</span>
								</Label>
								<Input
									id="field_label"
									value={ fieldSettings.label }
									onChange={ ( e ) => updateSettings( 'label', e.target.value ) }
									placeholder={ i18n?.labelPlaceholder || __( 'z.B. Anrede', 'recruiting-playbook' ) }
								/>
							</div>

							{ /* Placeholder (for applicable types) */ }
							{ hasPlaceholder && (
								<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									<Label htmlFor="field_placeholder">
										{ i18n?.fieldPlaceholder || __( 'Platzhalter', 'recruiting-playbook' ) }
									</Label>
									<Input
										id="field_placeholder"
										value={ fieldSettings.placeholder }
										onChange={ ( e ) => updateSettings( 'placeholder', e.target.value ) }
										placeholder={ i18n?.placeholderHelp || __( 'Platzhaltertext...', 'recruiting-playbook' ) }
									/>
								</div>
							) }

							{ /* Description */ }
							<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
								<Label htmlFor="field_description">
									{ i18n?.fieldDescription || __( 'Beschreibung', 'recruiting-playbook' ) }
								</Label>
								<Textarea
									id="field_description"
									value={ fieldSettings.description }
									onChange={ ( e ) => updateSettings( 'description', e.target.value ) }
									placeholder={ i18n?.descriptionHelp || __( 'Hilfetext für das Feld (optional)', 'recruiting-playbook' ) }
									rows={ 2 }
								/>
							</div>

							{ /* Width and Required in a row */ }
							<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' } }>
								{ /* Width */ }
								<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									<Label>{ i18n?.fieldWidth || __( 'Breite', 'recruiting-playbook' ) }</Label>
									<Select
										value={ fieldSettings.width }
										onChange={ ( e ) => updateSettings( 'width', e.target.value ) }
									>
										<SelectOption value="full">
											{ i18n?.widthFull || __( 'Volle Breite', 'recruiting-playbook' ) }
										</SelectOption>
										<SelectOption value="half">
											{ i18n?.widthHalf || __( 'Halbe Breite', 'recruiting-playbook' ) }
										</SelectOption>
									</Select>
								</div>

								{ /* Required */ }
								<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									<Label>{ i18n?.fieldRequired || __( 'Pflichtfeld', 'recruiting-playbook' ) }</Label>
									<div
										style={ {
											display: 'flex',
											alignItems: 'center',
											gap: '0.5rem',
											height: '2.25rem',
										} }
									>
										<Switch
											id="is_required"
											checked={ fieldSettings.is_required }
											onCheckedChange={ ( checked ) => updateSettings( 'is_required', checked ) }
										/>
										<Label htmlFor="is_required" style={ { cursor: 'pointer', fontWeight: 400 } }>
											{ fieldSettings.is_required
												? ( i18n?.yes || __( 'Ja', 'recruiting-playbook' ) )
												: ( i18n?.no || __( 'Nein', 'recruiting-playbook' ) )
											}
										</Label>
									</div>
								</div>
							</div>

							{ /* Options for select/radio/checkbox */ }
							{ hasOptions && (
								<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									<Label>{ i18n?.fieldOptions || __( 'Optionen', 'recruiting-playbook' ) }</Label>
									<OptionsEditor
										options={ fieldSettings.options }
										onChange={ ( options ) => updateSettings( 'options', options ) }
										fieldType={ selectedType }
										i18n={ i18n }
									/>
								</div>
							) }
						</>
					) }
				</div>

				{ /* Footer */ }
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'flex-end',
						gap: '0.5rem',
						padding: '1rem 1.5rem',
						borderTop: '1px solid #e5e7eb',
						backgroundColor: '#f9fafb',
					} }
				>
					<Button variant="outline" onClick={ onClose }>
						{ i18n?.cancel || __( 'Abbrechen', 'recruiting-playbook' ) }
					</Button>
					<Button
						onClick={ handleSubmit }
						disabled={ ! isValid }
					>
						{ i18n?.createField || __( 'Feld erstellen', 'recruiting-playbook' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}

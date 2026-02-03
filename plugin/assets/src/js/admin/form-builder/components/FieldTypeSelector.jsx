/**
 * FieldTypeSelector Component
 *
 * Modal dialog for creating a new field with type selection and settings.
 * Redesigned with dropdown + inline settings form.
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Textarea } from '../../components/ui/textarea';
import { Switch } from '../../components/ui/switch';
import { Select, SelectOption } from '../../components/ui/select';
import { RichTextEditor } from '../../components/ui/rich-text-editor';
import {
	X,
	Type,
	AlignLeft,
	Mail,
	Phone,
	List,
	Circle,
	CheckSquare,
	Calendar,
	Link,
	Lock,
	ChevronDown,
	Code,
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
	select: List,
	radio: Circle,
	checkbox: CheckSquare,
	date: Calendar,
	url: Link,
	html: Code,
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
		content: '', // For HTML field type
	} );
	const [ dropdownOpen, setDropdownOpen ] = useState( false );
	const [ dropdownPosition, setDropdownPosition ] = useState( { top: 0, left: 0, width: 0 } );
	const dropdownRef = useRef( null );
	const triggerRef = useRef( null );

	// Close dropdown when clicking outside
	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if ( dropdownRef.current && ! dropdownRef.current.contains( event.target ) &&
			     triggerRef.current && ! triggerRef.current.contains( event.target ) ) {
				setDropdownOpen( false );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return () => document.removeEventListener( 'mousedown', handleClickOutside );
	}, [] );

	// Calculate dropdown position when opening
	const openDropdown = () => {
		if ( triggerRef.current ) {
			const rect = triggerRef.current.getBoundingClientRect();
			setDropdownPosition( {
				top: rect.bottom + 4,
				left: rect.left,
				width: rect.width,
			} );
		}
		setDropdownOpen( true );
	};

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
		const proTypes = [ 'date' ];
		return ! isPro && proTypes.includes( typeKey );
	};

	// Check if selected type has options (select, radio, checkbox)
	const hasOptions = [ 'select', 'radio', 'checkbox' ].includes( selectedType );

	// Check if selected type supports placeholder
	const hasPlaceholder = [ 'text', 'textarea', 'email', 'phone', 'number', 'url', 'select' ].includes( selectedType );

	// Check if selected type is HTML (display-only content)
	const isHtmlField = selectedType === 'html';

	// Update field settings
	const updateSettings = ( key, value ) => {
		setFieldSettings( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	// Handle type selection
	const handleTypeSelect = ( newType ) => {
		setSelectedType( newType );
		setDropdownOpen( false );

		// Set default label based on type
		if ( newType && fieldTypes[ newType ] ) {
			setFieldSettings( ( prev ) => ( {
				...prev,
				label: newType === 'html'
					? ( prev.label || __( 'Hinweistext', 'recruiting-playbook' ) )
					: ( prev.label || fieldTypes[ newType ].label || '' ),
				options: [ 'select', 'radio', 'checkbox' ].includes( newType ) ? prev.options : [],
				content: newType === 'html' ? ( prev.content || '' ) : '',
			} ) );
		}
	};

	// Handle form submit
	const handleSubmit = () => {
		// For HTML fields, content is required; for others, label is required
		const isValidSubmit = selectedType && (
			selectedType === 'html'
				? fieldSettings.content.trim()
				: fieldSettings.label.trim()
		);

		if ( ! isValidSubmit ) {
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
	// For HTML fields, content is required; for others, label is required
	const isValid = selectedType && (
		isHtmlField
			? fieldSettings.content.trim()
			: fieldSettings.label.trim()
	);

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
							{ /* Dropdown trigger button */ }
							<button
								ref={ triggerRef }
								type="button"
								onClick={ () => dropdownOpen ? setDropdownOpen( false ) : openDropdown() }
								style={ {
									width: '100%',
									padding: '0.5rem 2.5rem 0.5rem 2.5rem',
									fontSize: '0.875rem',
									borderRadius: '0.375rem',
									border: '1px solid #d1d5db',
									backgroundColor: 'white',
									cursor: 'pointer',
									textAlign: 'left',
									display: 'flex',
									alignItems: 'center',
								} }
							>
								{ selectedType && fieldTypes[ selectedType ]
									? fieldTypes[ selectedType ].label
									: ( i18n?.selectFieldType || __( 'Feldtyp wählen...', 'recruiting-playbook' ) )
								}
							</button>

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
									transition: 'transform 0.2s',
									...(dropdownOpen ? { transform: 'translateY(-50%) rotate(180deg)' } : {}),
								} }
							/>

							{ /* Dropdown list - rendered with fixed position to escape overflow */ }
							{ dropdownOpen && (
								<div
									ref={ dropdownRef }
									style={ {
										position: 'fixed',
										top: dropdownPosition.top,
										left: dropdownPosition.left,
										width: dropdownPosition.width,
										backgroundColor: 'white',
										border: '1px solid #d1d5db',
										borderRadius: '0.375rem',
										boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
										zIndex: 100001,
										maxHeight: '15rem',
										overflowY: 'auto',
									} }
								>
									{ sortedFieldTypes.map( ( [ key, type ] ) => {
										const isProType = requiresPro( key );
										const IconComponent = fieldTypeIcons[ key ] || Type;
										const isSelected = selectedType === key;

										return (
											<button
												key={ key }
												type="button"
												onClick={ () => ! isProType && handleTypeSelect( key ) }
												disabled={ isProType }
												style={ {
													width: '100%',
													padding: '0.5rem 0.75rem',
													display: 'flex',
													alignItems: 'center',
													gap: '0.5rem',
													fontSize: '0.875rem',
													textAlign: 'left',
													border: 'none',
													backgroundColor: isSelected ? '#f3f4f6' : 'transparent',
													cursor: isProType ? 'not-allowed' : 'pointer',
													color: isProType ? '#9ca3af' : '#374151',
												} }
												className={ ! isProType ? 'hover:bg-gray-100' : '' }
											>
												<IconComponent style={ { height: '1rem', width: '1rem', flexShrink: 0, color: isProType ? '#9ca3af' : '#6b7280' } } />
												<span style={ { flex: 1 } }>{ type.label }</span>
												{ isProType && (
													<span style={ { display: 'flex', alignItems: 'center', gap: '0.25rem', fontSize: '0.75rem', color: '#9ca3af' } }>
														<Lock style={ { height: '0.75rem', width: '0.75rem' } } />
														Pro
													</span>
												) }
											</button>
										);
									} ) }
								</div>
							) }
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

							{ /* HTML Content Editor */ }
							{ isHtmlField && (
								<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
									<Label>
										{ __( 'Inhalt', 'recruiting-playbook' ) }
										<span style={ { color: '#ef4444' } }> *</span>
									</Label>
									<RichTextEditor
										value={ fieldSettings.content }
										onChange={ ( content ) => updateSettings( 'content', content ) }
										placeholder={ __( 'HTML-Inhalt eingeben...', 'recruiting-playbook' ) }
										minHeight="150px"
									/>
									<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
										{ __( 'Formatierter Text wird im Formular angezeigt (z.B. Hinweise, Erklärungen).', 'recruiting-playbook' ) }
									</p>
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

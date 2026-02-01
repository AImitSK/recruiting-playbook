/**
 * FormPreview Component
 *
 * Live preview of the step-based application form.
 *
 * @package RecruitingPlaybook
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Badge } from '../../components/ui/badge';
import { Monitor, Tablet, Smartphone, ChevronLeft, ChevronRight } from 'lucide-react';
import FieldPreview from './FieldPreview';
import SystemFieldPreview from './SystemFieldPreview';

/**
 * FormPreview component - Step-based preview
 *
 * @param {Object} props                  Component props
 * @param {Array}  props.steps            Form steps configuration
 * @param {Array}  props.availableFields  Available field definitions
 * @param {Object} props.settings         Form settings
 * @param {Object} props.fieldTypes       Available field types
 * @param {Object} props.i18n             Translations
 *
 * Also supports legacy props for backwards compatibility:
 * @param {Array}  props.fields           Legacy: Field definitions
 */
export default function FormPreview( {
	steps = [],
	availableFields = [],
	settings = {},
	fieldTypes = {},
	i18n = {},
	// Legacy support
	fields = [],
} ) {
	const [ viewMode, setViewMode ] = useState( 'desktop' );
	const [ currentStep, setCurrentStep ] = useState( 1 );

	// Build field lookup map from availableFields
	const fieldMap = useMemo( () => {
		const map = {};
		availableFields.forEach( ( field ) => {
			map[ field.field_key ] = field;
		} );
		// Legacy: also include fields array
		fields.forEach( ( field ) => {
			if ( field.field_key && ! map[ field.field_key ] ) {
				map[ field.field_key ] = field;
			}
		} );
		return map;
	}, [ availableFields, fields ] );

	// Get visible fields for a step
	const getStepFields = ( step ) => {
		if ( ! step?.fields ) {
			return [];
		}

		return step.fields
			.filter( ( fieldConfig ) => fieldConfig.is_visible )
			.map( ( fieldConfig ) => {
				const definition = fieldMap[ fieldConfig.field_key ];
				if ( ! definition ) {
					return null;
				}

				return {
					...definition,
					// Override with step-specific settings
					is_required: fieldConfig.is_required ?? definition.is_required,
				};
			} )
			.filter( Boolean );
	};

	// Get system fields for a step
	const getStepSystemFields = ( step ) => {
		if ( ! step?.system_fields || ! Array.isArray( step.system_fields ) ) {
			return [];
		}
		return step.system_fields;
	};

	// Total steps for navigation
	const totalSteps = steps.length;

	// Current step data
	const currentStepData = steps[ currentStep - 1 ] || null;
	const currentFields = currentStepData ? getStepFields( currentStepData ) : [];
	const currentSystemFields = currentStepData ? getStepSystemFields( currentStepData ) : [];

	// Count all visible fields across all steps (including system fields)
	const totalVisibleFields = useMemo( () => {
		let count = 0;
		steps.forEach( ( step ) => {
			count += getStepFields( step ).length;
			count += getStepSystemFields( step ).length;
		} );
		return count;
	}, [ steps, fieldMap ] );

	// Count required fields (including required system fields like privacy_consent)
	const totalRequiredFields = useMemo( () => {
		let count = 0;
		steps.forEach( ( step ) => {
			getStepFields( step ).forEach( ( field ) => {
				if ( field.is_required ) {
					count++;
				}
			} );
			// System fields like privacy_consent are always required
			getStepSystemFields( step ).forEach( ( sf ) => {
				if ( sf.type === 'privacy_consent' || sf.settings?.is_required ) {
					count++;
				}
			} );
		} );
		return count;
	}, [ steps, fieldMap ] );

	// Navigation handlers
	const goToPrevStep = () => {
		setCurrentStep( ( prev ) => Math.max( 1, prev - 1 ) );
	};

	const goToNextStep = () => {
		setCurrentStep( ( prev ) => Math.min( totalSteps, prev + 1 ) );
	};

	// Get container width based on view mode
	const getContainerStyle = () => {
		switch ( viewMode ) {
			case 'tablet':
				return { maxWidth: '768px', margin: '0 auto' };
			case 'mobile':
				return { maxWidth: '375px', margin: '0 auto' };
			default:
				return {};
		}
	};

	// Calculate progress percentage
	const progress = totalSteps > 0 ? Math.round( ( currentStep / totalSteps ) * 100 ) : 0;

	return (
		<Card className="rp-form-preview">
			<CardHeader>
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between' } }>
					<div>
						<CardTitle>
							{ i18n?.preview || __( 'Formular-Vorschau', 'recruiting-playbook' ) }
						</CardTitle>
						<CardDescription>
							{ i18n?.previewDescription || __( 'So wird das Bewerbungsformular aussehen', 'recruiting-playbook' ) }
						</CardDescription>
					</div>

					{ /* View mode selector */ }
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.25rem', backgroundColor: '#f3f4f6', borderRadius: '0.5rem', padding: '0.25rem' } }>
						<Button
							variant={ viewMode === 'desktop' ? 'default' : 'ghost' }
							size="sm"
							onClick={ () => setViewMode( 'desktop' ) }
							title={ i18n?.previewDesktop || __( 'Desktop', 'recruiting-playbook' ) }
						>
							<Monitor style={ { height: '1rem', width: '1rem' } } />
						</Button>
						<Button
							variant={ viewMode === 'tablet' ? 'default' : 'ghost' }
							size="sm"
							onClick={ () => setViewMode( 'tablet' ) }
							title={ i18n?.previewTablet || __( 'Tablet', 'recruiting-playbook' ) }
						>
							<Tablet style={ { height: '1rem', width: '1rem' } } />
						</Button>
						<Button
							variant={ viewMode === 'mobile' ? 'default' : 'ghost' }
							size="sm"
							onClick={ () => setViewMode( 'mobile' ) }
							title={ i18n?.previewMobile || __( 'Mobil', 'recruiting-playbook' ) }
						>
							<Smartphone style={ { height: '1rem', width: '1rem' } } />
						</Button>
					</div>
				</div>
			</CardHeader>

			<CardContent>
				<div
					className="rp-form-preview__container"
					style={ { backgroundColor: '#f9fafb', borderRadius: '0.5rem', padding: '1.5rem', border: '2px dashed #e5e7eb', transition: 'all 0.3s', ...getContainerStyle() } }
				>
					{ totalSteps === 0 ? (
						<div style={ { textAlign: 'center', padding: '3rem 0', color: '#6b7280' } }>
							<p style={ { margin: 0 } }>{ i18n?.noStepsToPreview || __( 'Keine Schritte zum Anzeigen vorhanden', 'recruiting-playbook' ) }</p>
							<p style={ { fontSize: '0.875rem', marginTop: '0.5rem' } }>
								{ i18n?.addStepsForPreview || __( 'Fügen Sie Schritte im Formular-Tab hinzu, um sie hier zu sehen', 'recruiting-playbook' ) }
							</p>
						</div>
					) : (
						<form className="rp-form-preview__form" style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } } onSubmit={ ( e ) => e.preventDefault() }>
							{ /* Progress bar */ }
							{ settings.showStepIndicator !== false && totalSteps > 1 && (
								<div style={ { marginBottom: '1rem' } }>
									<div style={ { display: 'flex', justifyContent: 'space-between', fontSize: '0.875rem', color: '#6b7280', marginBottom: '0.5rem' } }>
										<span>
											{ __( 'Schritt', 'recruiting-playbook' ) } { currentStep } { __( 'von', 'recruiting-playbook' ) } { totalSteps }
										</span>
										<span>{ progress }%</span>
									</div>
									<div style={ { height: '0.5rem', backgroundColor: '#e5e7eb', borderRadius: '0.25rem', overflow: 'hidden' } }>
										<div
											style={ {
												height: '100%',
												backgroundColor: '#2563eb',
												transition: 'width 0.3s',
												width: `${ progress }%`,
											} }
										/>
									</div>
								</div>
							) }

							{ /* Step title */ }
							{ currentStepData && settings.showStepTitles !== false && (
								<div style={ { textAlign: 'center', marginBottom: '1rem' } }>
									<h3 style={ { fontSize: '1.25rem', fontWeight: 600, margin: 0 } }>
										{ currentStepData.title }
									</h3>
									{ currentStepData.is_finale && (
										<Badge variant="outline" style={ { marginTop: '0.5rem' } }>
											{ __( 'Abschluss', 'recruiting-playbook' ) }
										</Badge>
									) }
								</div>
							) }

							{ /* Field grid */ }
							<div className="rp-form-preview__fields" style={ { display: 'grid', gridTemplateColumns: viewMode === 'mobile' ? '1fr' : 'repeat(2, 1fr)', gap: '1rem' } }>
								{ currentFields.length === 0 && currentSystemFields.length === 0 ? (
									<div style={ { textAlign: 'center', padding: '2rem 0', color: '#9ca3af', gridColumn: 'span 2 / span 2' } }>
										<p style={ { margin: 0, fontSize: '0.875rem' } }>
											{ __( 'Keine Felder in diesem Schritt', 'recruiting-playbook' ) }
										</p>
									</div>
								) : (
									<>
										{ /* Regular fields */ }
										{ currentFields.map( ( field, index ) => (
											<FieldPreview
												key={ `${ field.field_key }-${ index }` }
												field={ field }
												fieldType={ fieldTypes[ field.field_type ] || fieldTypes[ field.type ] }
												viewMode={ viewMode }
											/>
										) ) }

										{ /* System fields */ }
										{ currentSystemFields.map( ( systemField, index ) => (
											<SystemFieldPreview
												key={ `system-${ systemField.field_key }-${ index }` }
												systemField={ systemField }
												viewMode={ viewMode }
											/>
										) ) }
									</>
								) }
							</div>

							{ /* Navigation buttons */ }
							<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingTop: '1rem', borderTop: '1px solid #e5e7eb', marginTop: '1rem' } }>
								<Button
									type="button"
									variant="outline"
									onClick={ goToPrevStep }
									disabled={ currentStep === 1 }
									style={ { visibility: currentStep === 1 ? 'hidden' : 'visible' } }
								>
									<ChevronLeft style={ { height: '1rem', width: '1rem', marginRight: '0.25rem' } } />
									{ __( 'Zurück', 'recruiting-playbook' ) }
								</Button>

								{ currentStep < totalSteps ? (
									<Button type="button" onClick={ goToNextStep }>
										{ __( 'Weiter', 'recruiting-playbook' ) }
										<ChevronRight style={ { height: '1rem', width: '1rem', marginLeft: '0.25rem' } } />
									</Button>
								) : (
									<Button type="button" disabled>
										{ i18n?.submitApplication || __( 'Bewerbung absenden', 'recruiting-playbook' ) }
									</Button>
								) }
							</div>

							<p style={ { fontSize: '0.75rem', color: '#6b7280', textAlign: 'center', marginTop: '0' } }>
								{ i18n?.previewOnly || __( 'Dies ist nur eine Vorschau. Die Buttons sind deaktiviert.', 'recruiting-playbook' ) }
							</p>
						</form>
					) }
				</div>

				{ /* Step navigation dots */ }
				{ totalSteps > 1 && (
					<div style={ { display: 'flex', justifyContent: 'center', gap: '0.5rem', marginTop: '1rem' } }>
						{ steps.map( ( step, index ) => (
							<button
								key={ step.id }
								type="button"
								onClick={ () => setCurrentStep( index + 1 ) }
								style={ {
									width: '0.75rem',
									height: '0.75rem',
									borderRadius: '50%',
									border: 'none',
									cursor: 'pointer',
									backgroundColor: currentStep === index + 1 ? '#2563eb' : '#d1d5db',
									transition: 'background-color 0.2s',
								} }
								title={ step.title }
							/>
						) ) }
					</div>
				) }

				{ /* Field count info */ }
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '1rem', fontSize: '0.875rem', color: '#6b7280' } }>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<span>
							{ totalSteps } { totalSteps === 1 ? __( 'Schritt', 'recruiting-playbook' ) : __( 'Schritte', 'recruiting-playbook' ) }
							{ ' • ' }
							{ totalVisibleFields } { i18n?.activeFields || __( 'aktive Felder', 'recruiting-playbook' ) }
						</span>
						<Badge variant="outline">
							{ totalRequiredFields } { i18n?.required || __( 'Pflicht', 'recruiting-playbook' ) }
						</Badge>
					</div>
					<span style={ { textTransform: 'capitalize' } }>
						{ viewMode === 'desktop' ? 'Desktop' : viewMode === 'tablet' ? 'Tablet' : 'Mobil' }
					</span>
				</div>
			</CardContent>
		</Card>
	);
}

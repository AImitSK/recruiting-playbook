/**
 * FormPreview Component
 *
 * Live preview of the application form with all fields.
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Badge } from '../../components/ui/badge';
import { Monitor, Tablet, Smartphone } from 'lucide-react';
import FieldPreview from './FieldPreview';

/**
 * FormPreview component
 *
 * @param {Object} props Component props
 * @param {Array}  props.fields     Field definitions
 * @param {Object} props.fieldTypes Available field types
 * @param {Object} props.i18n        Translations
 */
export default function FormPreview( { fields = [], fieldTypes, i18n } ) {
	const [ viewMode, setViewMode ] = useState( 'desktop' );

	// Filter enabled fields only
	const enabledFields = fields.filter( ( f ) => f.is_enabled );

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
					{ enabledFields.length === 0 ? (
						<div style={ { textAlign: 'center', padding: '3rem 0', color: '#6b7280' } }>
							<p style={ { margin: 0 } }>{ i18n?.noFieldsToPreview || __( 'Keine Felder zum Anzeigen vorhanden', 'recruiting-playbook' ) }</p>
							<p style={ { fontSize: '0.875rem', marginTop: '0.5rem' } }>
								{ i18n?.enableFieldsForPreview || __( 'Aktivieren Sie Felder im Felder-Tab, um sie hier zu sehen', 'recruiting-playbook' ) }
							</p>
						</div>
					) : (
						<form className="rp-form-preview__form" style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } } onSubmit={ ( e ) => e.preventDefault() }>
							{ /* Preview header */ }
							<div style={ { textAlign: 'center', marginBottom: '2rem' } }>
								<h2 style={ { fontSize: '1.5rem', fontWeight: 600, margin: 0 } }>
									{ i18n?.applicationForm || __( 'Bewerbungsformular', 'recruiting-playbook' ) }
								</h2>
								<p style={ { color: '#4b5563', marginTop: '0.5rem' } }>
									{ i18n?.applicationFormSubtitle || __( 'Füllen Sie alle Felder aus und senden Sie Ihre Bewerbung ab', 'recruiting-playbook' ) }
								</p>
							</div>

							{ /* Field grid */ }
							<div className="rp-form-preview__fields" style={ { display: 'grid', gap: '1rem' } }>
								{ enabledFields.map( ( field ) => (
									<FieldPreview
										key={ field.id }
										field={ field }
										fieldType={ fieldTypes[ field.type ] }
										viewMode={ viewMode }
									/>
								) ) }
							</div>

							{ /* Submit button preview */ }
							<div style={ { paddingTop: '1rem', borderTop: '1px solid #e5e7eb' } }>
								<Button type="button" style={ { width: '100%' } } size="lg" disabled>
									{ i18n?.submitApplication || __( 'Bewerbung absenden', 'recruiting-playbook' ) }
								</Button>
								<p style={ { fontSize: '0.75rem', color: '#6b7280', textAlign: 'center', marginTop: '0.5rem' } }>
									{ i18n?.previewOnly || __( 'Dies ist nur eine Vorschau. Der Button ist deaktiviert.', 'recruiting-playbook' ) }
								</p>
							</div>
						</form>
					) }
				</div>

				{ /* Field count info */ }
				<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '1rem', fontSize: '0.875rem', color: '#6b7280' } }>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<span>
							{ enabledFields.length } { i18n?.activeFields || __( 'aktive Felder', 'recruiting-playbook' ) }
						</span>
						<Badge variant="outline">
							{ enabledFields.filter( ( f ) => f.is_required ).length } { i18n?.required || __( 'Pflicht', 'recruiting-playbook' ) }
						</Badge>
					</div>
					<span style={ { textTransform: 'capitalize' } }>
						{ i18n?.[ `preview${ viewMode.charAt( 0 ).toUpperCase() + viewMode.slice( 1 ) }` ] || viewMode }
					</span>
				</div>
			</CardContent>
		</Card>
	);
}

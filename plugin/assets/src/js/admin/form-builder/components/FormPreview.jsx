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
				<div className="flex items-center justify-between">
					<div>
						<CardTitle>
							{ i18n?.preview || __( 'Formular-Vorschau', 'recruiting-playbook' ) }
						</CardTitle>
						<CardDescription>
							{ i18n?.previewDescription || __( 'So wird das Bewerbungsformular aussehen', 'recruiting-playbook' ) }
						</CardDescription>
					</div>

					{ /* View mode selector */ }
					<div className="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
						<Button
							variant={ viewMode === 'desktop' ? 'default' : 'ghost' }
							size="sm"
							onClick={ () => setViewMode( 'desktop' ) }
							title={ i18n?.previewDesktop || __( 'Desktop', 'recruiting-playbook' ) }
						>
							<Monitor className="h-4 w-4" />
						</Button>
						<Button
							variant={ viewMode === 'tablet' ? 'default' : 'ghost' }
							size="sm"
							onClick={ () => setViewMode( 'tablet' ) }
							title={ i18n?.previewTablet || __( 'Tablet', 'recruiting-playbook' ) }
						>
							<Tablet className="h-4 w-4" />
						</Button>
						<Button
							variant={ viewMode === 'mobile' ? 'default' : 'ghost' }
							size="sm"
							onClick={ () => setViewMode( 'mobile' ) }
							title={ i18n?.previewMobile || __( 'Mobil', 'recruiting-playbook' ) }
						>
							<Smartphone className="h-4 w-4" />
						</Button>
					</div>
				</div>
			</CardHeader>

			<CardContent>
				<div
					className="rp-form-preview__container bg-gray-50 rounded-lg p-6 border-2 border-dashed transition-all duration-300"
					style={ getContainerStyle() }
				>
					{ enabledFields.length === 0 ? (
						<div className="text-center py-12 text-gray-500">
							<p>{ i18n?.noFieldsToPreview || __( 'Keine Felder zum Anzeigen vorhanden', 'recruiting-playbook' ) }</p>
							<p className="text-sm mt-2">
								{ i18n?.enableFieldsForPreview || __( 'Aktivieren Sie Felder im Felder-Tab, um sie hier zu sehen', 'recruiting-playbook' ) }
							</p>
						</div>
					) : (
						<form className="rp-form-preview__form space-y-6" onSubmit={ ( e ) => e.preventDefault() }>
							{ /* Preview header */ }
							<div className="text-center mb-8">
								<h2 className="text-2xl font-semibold">
									{ i18n?.applicationForm || __( 'Bewerbungsformular', 'recruiting-playbook' ) }
								</h2>
								<p className="text-gray-600 mt-2">
									{ i18n?.applicationFormSubtitle || __( 'Füllen Sie alle Felder aus und senden Sie Ihre Bewerbung ab', 'recruiting-playbook' ) }
								</p>
							</div>

							{ /* Field grid */ }
							<div className="rp-form-preview__fields grid gap-4">
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
							<div className="pt-4 border-t">
								<Button type="button" className="w-full" size="lg" disabled>
									{ i18n?.submitApplication || __( 'Bewerbung absenden', 'recruiting-playbook' ) }
								</Button>
								<p className="text-xs text-gray-500 text-center mt-2">
									{ i18n?.previewOnly || __( 'Dies ist nur eine Vorschau. Der Button ist deaktiviert.', 'recruiting-playbook' ) }
								</p>
							</div>
						</form>
					) }
				</div>

				{ /* Field count info */ }
				<div className="flex items-center justify-between mt-4 text-sm text-gray-500">
					<div className="flex items-center gap-2">
						<span>
							{ enabledFields.length } { i18n?.activeFields || __( 'aktive Felder', 'recruiting-playbook' ) }
						</span>
						<Badge variant="outline">
							{ enabledFields.filter( ( f ) => f.is_required ).length } { i18n?.required || __( 'Pflicht', 'recruiting-playbook' ) }
						</Badge>
					</div>
					<span className="capitalize">
						{ i18n?.[ `preview${ viewMode.charAt( 0 ).toUpperCase() + viewMode.slice( 1 ) }` ] || viewMode }
					</span>
				</div>
			</CardContent>
		</Card>
	);
}

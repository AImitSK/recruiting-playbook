/**
 * SummarySettings Component
 *
 * Settings panel for the summary system field.
 * Allows configuration of header display, step titles visibility, and help text.
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Textarea } from '../../../components/ui/textarea';
import { Switch } from '../../../components/ui/switch';
import { X, ListChecks } from 'lucide-react';

/**
 * SummarySettings component
 *
 * @param {Object}   props           Component props
 * @param {Object}   props.settings  Current settings
 * @param {Function} props.onSave    Save handler
 * @param {Function} props.onClose   Close handler
 */
export default function SummarySettings( { settings = {}, onSave, onClose } ) {
	// Local state for form values
	const [ label, setLabel ] = useState( settings.label || __( 'Zusammenfassung', 'recruiting-playbook' ) );
	const [ showHeader, setShowHeader ] = useState( settings.show_header !== false );
	const [ showStepTitles, setShowStepTitles ] = useState( settings.show_step_titles !== false );
	const [ showEditButtons, setShowEditButtons ] = useState( settings.show_edit_buttons !== false );
	const [ helpText, setHelpText ] = useState( settings.help_text || '' );

	// Handle save
	const handleSave = () => {
		onSave( {
			label,
			show_header: showHeader,
			show_step_titles: showStepTitles,
			show_edit_buttons: showEditButtons,
			help_text: helpText,
		} );
	};

	return (
		<div
			style={ {
				position: 'fixed',
				inset: 0,
				backgroundColor: 'rgba(0, 0, 0, 0.5)',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				zIndex: 100,
			} }
			onClick={ ( e ) => {
				if ( e.target === e.currentTarget ) {
					onClose();
				}
			} }
		>
			<Card style={ { width: '100%', maxWidth: '500px', maxHeight: '90vh', overflow: 'auto' } }>
				<CardHeader style={ { display: 'flex', flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' } }>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<ListChecks style={ { height: '1.25rem', width: '1.25rem', color: '#3b82f6' } } />
						<div>
							<CardTitle>{ __( 'Zusammenfassung Einstellungen', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>{ __( 'Konfigurieren Sie die Anzeige der Zusammenfassung', 'recruiting-playbook' ) }</CardDescription>
						</div>
					</div>
					<Button variant="ghost" size="sm" onClick={ onClose }>
						<X style={ { height: '1rem', width: '1rem' } } />
					</Button>
				</CardHeader>

				<CardContent style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
					{ /* Label */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="summary-label">{ __( 'Bezeichnung', 'recruiting-playbook' ) }</Label>
						<Input
							id="summary-label"
							value={ label }
							onChange={ ( e ) => setLabel( e.target.value ) }
							placeholder={ __( 'Zusammenfassung', 'recruiting-playbook' ) }
						/>
					</div>

					{ /* Show Header Toggle */ }
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0.75rem', backgroundColor: '#f9fafb', borderRadius: '0.5rem' } }>
						<div>
							<Label htmlFor="show-header" style={ { marginBottom: 0 } }>
								{ __( 'Überschrift anzeigen', 'recruiting-playbook' ) }
							</Label>
							<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: '0.25rem 0 0' } }>
								{ __( 'Zeigt die Bezeichnung als Überschrift an', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="show-header"
							checked={ showHeader }
							onCheckedChange={ setShowHeader }
						/>
					</div>

					{ /* Show Step Titles Toggle */ }
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0.75rem', backgroundColor: '#f9fafb', borderRadius: '0.5rem' } }>
						<div>
							<Label htmlFor="show-step-titles" style={ { marginBottom: 0 } }>
								{ __( 'Schritt-Titel anzeigen', 'recruiting-playbook' ) }
							</Label>
							<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: '0.25rem 0 0' } }>
								{ __( 'Gruppiert die Felder nach Schritten', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="show-step-titles"
							checked={ showStepTitles }
							onCheckedChange={ setShowStepTitles }
						/>
					</div>

					{ /* Show Edit Buttons Toggle */ }
					<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0.75rem', backgroundColor: '#f9fafb', borderRadius: '0.5rem' } }>
						<div>
							<Label htmlFor="show-edit-buttons" style={ { marginBottom: 0 } }>
								{ __( 'Bearbeiten-Buttons anzeigen', 'recruiting-playbook' ) }
							</Label>
							<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: '0.25rem 0 0' } }>
								{ __( 'Ermöglicht das Bearbeiten einzelner Schritte', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="show-edit-buttons"
							checked={ showEditButtons }
							onCheckedChange={ setShowEditButtons }
						/>
					</div>

					{ /* Help Text */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="help-text">{ __( 'Hilfetext', 'recruiting-playbook' ) }</Label>
						<Textarea
							id="help-text"
							value={ helpText }
							onChange={ ( e ) => setHelpText( e.target.value ) }
							placeholder={ __( 'z.B. "Bitte prüfen Sie Ihre Angaben vor dem Absenden."', 'recruiting-playbook' ) }
							rows={ 2 }
						/>
					</div>

					{ /* Action Buttons */ }
					<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', paddingTop: '0.5rem', borderTop: '1px solid #e5e7eb' } }>
						<Button variant="outline" onClick={ onClose }>
							{ __( 'Abbrechen', 'recruiting-playbook' ) }
						</Button>
						<Button onClick={ handleSave }>
							{ __( 'Speichern', 'recruiting-playbook' ) }
						</Button>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

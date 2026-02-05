/**
 * ButtonsPanel Component
 *
 * Tab: Buttons - Design-Modus und individuelle Einstellungen.
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Switch } from '../../../components/ui/switch';
import { Label } from '../../../components/ui/label';
import { Slider } from '../../../components/ui/slider';
import { ColorPicker } from '../../../components/ui/color-picker';
import { RadioGroup, RadioGroupItem } from '../../../components/ui/radio-group';
import { Select, SelectOption } from '../../../components/ui/select';

/**
 * ButtonsPanel Component
 *
 * @param {Object}   props                     Component props
 * @param {Object}   props.settings            Current design settings
 * @param {Function} props.onUpdate            Update single setting
 * @param {string}   props.computedPrimaryColor Computed primary color
 * @return {JSX.Element} Component
 */
export function ButtonsPanel( { settings, onUpdate, computedPrimaryColor } ) {
	const useCustomDesign = settings.button_use_custom_design ?? false;

	return (
		<div className="rp-space-y-4">
			{ /* Card: Design-Modus */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Design', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Standard: Buttons erben das komplette Aussehen des Themes', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Eigenes Button-Design */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="button_use_custom_design">
								{ __( 'Eigenes Button-Design', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Aktivieren für individuelle Einstellungen', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="button_use_custom_design"
							checked={ useCustomDesign }
							onCheckedChange={ ( checked ) => onUpdate( 'button_use_custom_design', checked ) }
						/>
					</div>

					{ /* Info-Box wenn Theme-Design aktiv */ }
					{ ! useCustomDesign && (
						<div className="rp-p-4 rp-bg-green-50 rp-border rp-border-green-200 rp-rounded-lg">
							<p className="rp-text-sm rp-text-green-800">
								<strong>{ __( 'Theme-Design aktiv', 'recruiting-playbook' ) }</strong>
								<br />
								{ __( 'Buttons übernehmen automatisch Farben, Radius, Padding und alle anderen Styles aus deinem WordPress-Theme.', 'recruiting-playbook' ) }
							</p>
							<p className="rp-text-xs rp-text-green-600 rp-mt-2">
								{ __( 'Hinweis: Die Vorschau zeigt nur die Primärfarbe. Das echte Button-Design siehst du im Frontend.', 'recruiting-playbook' ) }
							</p>
						</div>
					) }
				</CardContent>
			</Card>

			{ /* Alle weiteren Einstellungen nur wenn Custom Design aktiv */ }
			{ useCustomDesign && (
				<>
					{ /* Card: Farben */ }
					<Card>
						<CardHeader>
							<CardTitle>{ __( 'Farben', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>
								{ __( 'Hintergrund- und Textfarben', 'recruiting-playbook' ) }
							</CardDescription>
						</CardHeader>
						<CardContent className="rp-space-y-4">
							{ /* Hintergrund */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Hintergrund', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_bg_color || computedPrimaryColor }
									onChange={ ( color ) => onUpdate( 'button_bg_color', color ) }
								/>
							</div>

							{ /* Hintergrund (Hover) */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Hintergrund (Hover)', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_bg_color_hover || '#1d4ed8' }
									onChange={ ( color ) => onUpdate( 'button_bg_color_hover', color ) }
								/>
							</div>

							{ /* Text */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Textfarbe', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_text_color || '#ffffff' }
									onChange={ ( color ) => onUpdate( 'button_text_color', color ) }
								/>
							</div>

							{ /* Text (Hover) */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Textfarbe (Hover)', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_text_color_hover || '#ffffff' }
									onChange={ ( color ) => onUpdate( 'button_text_color_hover', color ) }
								/>
							</div>
						</CardContent>
					</Card>

					{ /* Card: Form & Effekte */ }
					<Card>
						<CardHeader>
							<CardTitle>{ __( 'Form & Effekte', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>
								{ __( 'Größe, Radius, Rahmen und Schatten', 'recruiting-playbook' ) }
							</CardDescription>
						</CardHeader>
						<CardContent className="rp-space-y-4">
							{ /* Größe */ }
							<div>
								<Label className="rp-mb-2 rp-block">
									{ __( 'Größe', 'recruiting-playbook' ) }
								</Label>
								<RadioGroup
									value={ settings.button_size || 'medium' }
									onValueChange={ ( value ) => onUpdate( 'button_size', value ) }
									variant="buttons"
								>
									<RadioGroupItem value="small">
										{ __( 'Klein', 'recruiting-playbook' ) }
									</RadioGroupItem>
									<RadioGroupItem value="medium">
										{ __( 'Mittel', 'recruiting-playbook' ) }
									</RadioGroupItem>
									<RadioGroupItem value="large">
										{ __( 'Groß', 'recruiting-playbook' ) }
									</RadioGroupItem>
								</RadioGroup>
							</div>

							{ /* Eckenradius */ }
							<Slider
								label={ __( 'Eckenradius', 'recruiting-playbook' ) }
								value={ settings.button_border_radius ?? 6 }
								onChange={ ( value ) => onUpdate( 'button_border_radius', value ) }
								min={ 0 }
								max={ 50 }
								step={ 1 }
								unit="px"
							/>

							{ /* Rahmen */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label htmlFor="button_border_show">
									{ __( 'Rahmen anzeigen', 'recruiting-playbook' ) }
								</Label>
								<Switch
									id="button_border_show"
									checked={ settings.button_border_show ?? false }
									onCheckedChange={ ( checked ) => onUpdate( 'button_border_show', checked ) }
								/>
							</div>

							{ /* Rahmen-Optionen (nur wenn aktiv) */ }
							{ settings.button_border_show && (
								<div className="rp-pl-4 rp-border-l-2 rp-border-gray-200 rp-space-y-3">
									<div className="rp-flex rp-items-center rp-justify-between">
										<Label>{ __( 'Rahmenfarbe', 'recruiting-playbook' ) }</Label>
										<ColorPicker
											value={ settings.button_border_color || settings.button_bg_color || computedPrimaryColor }
											onChange={ ( color ) => onUpdate( 'button_border_color', color ) }
										/>
									</div>

									<Slider
										label={ __( 'Rahmenbreite', 'recruiting-playbook' ) }
										value={ settings.button_border_width ?? 1 }
										onChange={ ( value ) => onUpdate( 'button_border_width', value ) }
										min={ 1 }
										max={ 5 }
										step={ 1 }
										unit="px"
									/>
								</div>
							) }

							{ /* Schatten */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Schatten', 'recruiting-playbook' ) }</Label>
								<Select
									value={ settings.button_shadow || 'none' }
									onChange={ ( e ) => onUpdate( 'button_shadow', e.target.value ) }
									style={ { width: '112px' } }
								>
									<SelectOption value="none">{ __( 'Keiner', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="light">{ __( 'Leicht', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="medium">{ __( 'Mittel', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="strong">{ __( 'Stark', 'recruiting-playbook' ) }</SelectOption>
								</Select>
							</div>

							{ /* Schatten (Hover) */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Schatten (Hover)', 'recruiting-playbook' ) }</Label>
								<Select
									value={ settings.button_shadow_hover || 'light' }
									onChange={ ( e ) => onUpdate( 'button_shadow_hover', e.target.value ) }
									style={ { width: '112px' } }
								>
									<SelectOption value="none">{ __( 'Keiner', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="light">{ __( 'Leicht', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="medium">{ __( 'Mittel', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="strong">{ __( 'Stark', 'recruiting-playbook' ) }</SelectOption>
								</Select>
							</div>
						</CardContent>
					</Card>
				</>
			) }
		</div>
	);
}

export default ButtonsPanel;

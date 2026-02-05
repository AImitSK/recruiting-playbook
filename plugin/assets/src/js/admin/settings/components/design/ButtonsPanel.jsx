/**
 * ButtonsPanel Component
 *
 * Tab: Buttons - Farben, Form und Effekte.
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
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '../../../components/ui/select';

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
	// Computed button colors based on primary color or custom
	const effectiveBgColor = settings.override_button_colors
		? ( settings.button_bg_color || '#2563eb' )
		: computedPrimaryColor;

	return (
		<div className="rp-space-y-4">
			{ /* Card: Farben */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Farben', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Standard: Buttons erben die Primärfarbe', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Eigene Button-Farben */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="override_button_colors">
								{ __( 'Eigene Button-Farben', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Aktivieren für individuelle Farbeinstellungen', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="override_button_colors"
							checked={ settings.override_button_colors ?? false }
							onCheckedChange={ ( checked ) => onUpdate( 'override_button_colors', checked ) }
						/>
					</div>

					{ /* Farbeinstellungen (nur wenn aktiv) */ }
					{ settings.override_button_colors ? (
						<div className="rp-pl-4 rp-border-l-2 rp-border-gray-200 rp-space-y-3">
							{ /* Hintergrund */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Hintergrund', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_bg_color || '#2563eb' }
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
								<Label>{ __( 'Text', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_text_color || '#ffffff' }
									onChange={ ( color ) => onUpdate( 'button_text_color', color ) }
								/>
							</div>

							{ /* Text (Hover) */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Text (Hover)', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_text_color_hover || '#ffffff' }
									onChange={ ( color ) => onUpdate( 'button_text_color_hover', color ) }
								/>
							</div>
						</div>
					) : (
						<div className="rp-flex rp-items-center rp-gap-2 rp-p-3 rp-bg-gray-50 rp-rounded-md">
							<div
								className="rp-w-6 rp-h-6 rp-rounded rp-border rp-border-gray-300"
								style={ { backgroundColor: computedPrimaryColor } }
							/>
							<span className="rp-text-sm rp-text-gray-600">
								{ __( 'Buttons verwenden Primärfarbe:', 'recruiting-playbook' ) }
								<code className="rp-ml-1 rp-font-mono rp-text-xs">{ computedPrimaryColor }</code>
							</span>
						</div>
					) }
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
									value={ settings.button_border_color || effectiveBgColor }
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
							onValueChange={ ( value ) => onUpdate( 'button_shadow', value ) }
						>
							<SelectTrigger className="rp-w-28">
								<SelectValue />
							</SelectTrigger>
							<SelectContent>
								<SelectItem value="none">{ __( 'Keiner', 'recruiting-playbook' ) }</SelectItem>
								<SelectItem value="light">{ __( 'Leicht', 'recruiting-playbook' ) }</SelectItem>
								<SelectItem value="medium">{ __( 'Mittel', 'recruiting-playbook' ) }</SelectItem>
								<SelectItem value="strong">{ __( 'Stark', 'recruiting-playbook' ) }</SelectItem>
							</SelectContent>
						</Select>
					</div>

					{ /* Schatten (Hover) */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<Label>{ __( 'Schatten (Hover)', 'recruiting-playbook' ) }</Label>
						<Select
							value={ settings.button_shadow_hover || 'light' }
							onValueChange={ ( value ) => onUpdate( 'button_shadow_hover', value ) }
						>
							<SelectTrigger className="rp-w-28">
								<SelectValue />
							</SelectTrigger>
							<SelectContent>
								<SelectItem value="none">{ __( 'Keiner', 'recruiting-playbook' ) }</SelectItem>
								<SelectItem value="light">{ __( 'Leicht', 'recruiting-playbook' ) }</SelectItem>
								<SelectItem value="medium">{ __( 'Mittel', 'recruiting-playbook' ) }</SelectItem>
								<SelectItem value="strong">{ __( 'Stark', 'recruiting-playbook' ) }</SelectItem>
							</SelectContent>
						</Select>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default ButtonsPanel;

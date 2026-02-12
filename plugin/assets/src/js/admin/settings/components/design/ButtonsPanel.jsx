/**
 * ButtonsPanel Component
 *
 * Tab: Buttons - Design mode and custom settings.
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
						{ __( 'Default: Buttons inherit the complete appearance of the theme', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Custom Button Design */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="button_use_custom_design">
								{ __( 'Custom Button Design', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Enable for custom settings', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="button_use_custom_design"
							checked={ useCustomDesign }
							onCheckedChange={ ( checked ) => onUpdate( 'button_use_custom_design', checked ) }
						/>
					</div>

					{ /* Info box when theme design is active */ }
					{ ! useCustomDesign && (
						<div className="rp-p-4 rp-bg-green-50 rp-border rp-border-green-200 rp-rounded-lg">
							<p className="rp-text-sm rp-text-green-800">
								<strong>{ __( 'Theme Design Active', 'recruiting-playbook' ) }</strong>
								<br />
								{ __( 'Buttons automatically inherit colors, radius, padding and all other styles from your WordPress theme.', 'recruiting-playbook' ) }
							</p>
							<p className="rp-text-xs rp-text-green-600 rp-mt-2">
								{ __( 'Note: The preview only shows the primary color. You will see the actual button design in the frontend.', 'recruiting-playbook' ) }
							</p>
						</div>
					) }
				</CardContent>
			</Card>

			{ /* All other settings only when custom design is active */ }
			{ useCustomDesign && (
				<>
					{ /* Card: Colors */ }
					<Card>
						<CardHeader>
							<CardTitle>{ __( 'Colors', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>
								{ __( 'Background and text colors', 'recruiting-playbook' ) }
							</CardDescription>
						</CardHeader>
						<CardContent className="rp-space-y-4">
							{ /* Background */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Background', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_bg_color || computedPrimaryColor }
									onChange={ ( color ) => onUpdate( 'button_bg_color', color ) }
								/>
							</div>

							{ /* Background (Hover) */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Background (Hover)', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_bg_color_hover || '#1d4ed8' }
									onChange={ ( color ) => onUpdate( 'button_bg_color_hover', color ) }
								/>
							</div>

							{ /* Text */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Text Color', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_text_color || '#ffffff' }
									onChange={ ( color ) => onUpdate( 'button_text_color', color ) }
								/>
							</div>

							{ /* Text (Hover) */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Text Color (Hover)', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.button_text_color_hover || '#ffffff' }
									onChange={ ( color ) => onUpdate( 'button_text_color_hover', color ) }
								/>
							</div>
						</CardContent>
					</Card>

					{ /* Card: Shape & Effects */ }
					<Card>
						<CardHeader>
							<CardTitle>{ __( 'Shape & Effects', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>
								{ __( 'Size, radius, border and shadow', 'recruiting-playbook' ) }
							</CardDescription>
						</CardHeader>
						<CardContent className="rp-space-y-4">
							{ /* Size */ }
							<div>
								<Label className="rp-mb-2 rp-block">
									{ __( 'Size', 'recruiting-playbook' ) }
								</Label>
								<RadioGroup
									value={ settings.button_size || 'medium' }
									onValueChange={ ( value ) => onUpdate( 'button_size', value ) }
									variant="buttons"
								>
									<RadioGroupItem value="small">
										{ __( 'Small', 'recruiting-playbook' ) }
									</RadioGroupItem>
									<RadioGroupItem value="medium">
										{ __( 'Medium', 'recruiting-playbook' ) }
									</RadioGroupItem>
									<RadioGroupItem value="large">
										{ __( 'Large', 'recruiting-playbook' ) }
									</RadioGroupItem>
								</RadioGroup>
							</div>

							{ /* Corner Radius */ }
							<Slider
								label={ __( 'Corner Radius', 'recruiting-playbook' ) }
								value={ settings.button_border_radius ?? 6 }
								onChange={ ( value ) => onUpdate( 'button_border_radius', value ) }
								min={ 0 }
								max={ 50 }
								step={ 1 }
								unit="px"
							/>

							{ /* Border */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label htmlFor="button_border_show">
									{ __( 'Show Border', 'recruiting-playbook' ) }
								</Label>
								<Switch
									id="button_border_show"
									checked={ settings.button_border_show ?? false }
									onCheckedChange={ ( checked ) => onUpdate( 'button_border_show', checked ) }
								/>
							</div>

							{ /* Border options (only when active) */ }
							{ settings.button_border_show && (
								<div className="rp-pl-4 rp-border-l-2 rp-border-gray-200 rp-space-y-3">
									<div className="rp-flex rp-items-center rp-justify-between">
										<Label>{ __( 'Border Color', 'recruiting-playbook' ) }</Label>
										<ColorPicker
											value={ settings.button_border_color || settings.button_bg_color || computedPrimaryColor }
											onChange={ ( color ) => onUpdate( 'button_border_color', color ) }
										/>
									</div>

									<Slider
										label={ __( 'Border Width', 'recruiting-playbook' ) }
										value={ settings.button_border_width ?? 1 }
										onChange={ ( value ) => onUpdate( 'button_border_width', value ) }
										min={ 1 }
										max={ 5 }
										step={ 1 }
										unit="px"
									/>
								</div>
							) }

							{ /* Shadow */ }
							<div>
								<Label className="rp-mb-2 rp-block">
									{ __( 'Shadow', 'recruiting-playbook' ) }
								</Label>
								<Select
									value={ settings.button_shadow || 'none' }
									onChange={ ( e ) => onUpdate( 'button_shadow', e.target.value ) }
								>
									<SelectOption value="none">{ __( 'None', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="light">{ __( 'Light', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="medium">{ __( 'Medium', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="strong">{ __( 'Strong', 'recruiting-playbook' ) }</SelectOption>
								</Select>
							</div>

							{ /* Shadow (Hover) */ }
							<div>
								<Label className="rp-mb-2 rp-block">
									{ __( 'Shadow (Hover)', 'recruiting-playbook' ) }
								</Label>
								<Select
									value={ settings.button_shadow_hover || 'light' }
									onChange={ ( e ) => onUpdate( 'button_shadow_hover', e.target.value ) }
								>
									<SelectOption value="none">{ __( 'None', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="light">{ __( 'Light', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="medium">{ __( 'Medium', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="strong">{ __( 'Strong', 'recruiting-playbook' ) }</SelectOption>
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

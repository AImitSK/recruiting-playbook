/**
 * CardsPanel Component
 *
 * Tab: Cards - Layout preset and appearance.
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

/**
 * CardsPanel Component
 *
 * @param {Object}   props          Component props
 * @param {Object}   props.settings Current design settings
 * @param {Function} props.onUpdate Update single setting
 * @return {JSX.Element} Component
 */
export function CardsPanel( { settings, onUpdate } ) {
	return (
		<div className="rp-space-y-4">
			{ /* Card: Layout Preset */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Layout Preset', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Predefined layouts for job cards', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent>
					<RadioGroup
						value={ settings.card_layout_preset || 'standard' }
						onValueChange={ ( value ) => onUpdate( 'card_layout_preset', value ) }
						variant="cards"
						orientation="horizontal"
						className="rp-grid rp-grid-cols-3 rp-gap-3"
					>
						<RadioGroupItem value="compact">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Compact', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Low padding, space-saving', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
						<RadioGroupItem value="standard">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Standard', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Balanced layout', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
						<RadioGroupItem value="spacious">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Spacious', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Plenty of whitespace', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
					</RadioGroup>
				</CardContent>
			</Card>

			{ /* Card: Appearance */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Appearance', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Visual properties of job cards and form box', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Border radius */ }
					<Slider
						label={ __( 'Border Radius', 'recruiting-playbook' ) }
						value={ settings.card_border_radius ?? 8 }
						onChange={ ( value ) => onUpdate( 'card_border_radius', value ) }
						min={ 0 }
						max={ 24 }
						step={ 1 }
						unit="px"
					/>

					{ /* Shadow */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Shadow', 'recruiting-playbook' ) }
						</Label>
						<RadioGroup
							value={ settings.card_shadow || 'light' }
							onValueChange={ ( value ) => onUpdate( 'card_shadow', value ) }
							variant="buttons"
						>
							<RadioGroupItem value="none">
								{ __( 'None', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="light">
								{ __( 'Light', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="medium">
								{ __( 'Medium', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="strong">
								{ __( 'Strong', 'recruiting-playbook' ) }
							</RadioGroupItem>
						</RadioGroup>
					</div>

					{ /* Border */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<Label htmlFor="card_border_show">
							{ __( 'Show Border', 'recruiting-playbook' ) }
						</Label>
						<Switch
							id="card_border_show"
							checked={ settings.card_border_show ?? true }
							onCheckedChange={ ( checked ) => onUpdate( 'card_border_show', checked ) }
						/>
					</div>

					{ /* Border color (only when border active) */ }
					{ settings.card_border_show && (
						<div className="rp-flex rp-items-center rp-justify-between rp-pl-4">
							<Label htmlFor="card_border_color">
								{ __( 'Border Color', 'recruiting-playbook' ) }
							</Label>
							<ColorPicker
								id="card_border_color"
								value={ settings.card_border_color || '#e5e7eb' }
								onChange={ ( color ) => onUpdate( 'card_border_color', color ) }
							/>
						</div>
					) }

					{ /* Border width (only when border active) */ }
					{ settings.card_border_show && (
						<div className="rp-pl-4">
							<Slider
								label={ __( 'Border Width', 'recruiting-playbook' ) }
								value={ settings.card_border_width ?? 1 }
								onChange={ ( value ) => onUpdate( 'card_border_width', value ) }
								min={ 1 }
								max={ 5 }
								step={ 1 }
								unit="px"
							/>
						</div>
					) }

					{ /* Background */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<Label htmlFor="card_background">
							{ __( 'Background', 'recruiting-playbook' ) }
						</Label>
						<ColorPicker
							id="card_background"
							value={ settings.card_background || '#ffffff' }
							onChange={ ( color ) => onUpdate( 'card_background', color ) }
						/>
					</div>

					{ /* Hover Effect */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Hover Effect', 'recruiting-playbook' ) }
						</Label>
						<RadioGroup
							value={ settings.card_hover_effect || 'lift' }
							onValueChange={ ( value ) => onUpdate( 'card_hover_effect', value ) }
							variant="buttons"
						>
							<RadioGroupItem value="none">
								{ __( 'None', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="lift">
								{ __( 'Lift', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="glow">
								{ __( 'Glow', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="border">
								{ __( 'Border', 'recruiting-playbook' ) }
							</RadioGroupItem>
						</RadioGroup>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default CardsPanel;

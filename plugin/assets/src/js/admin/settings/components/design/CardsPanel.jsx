/**
 * CardsPanel Component
 *
 * Tab: Cards - Layout-Preset und Erscheinungsbild.
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
			{ /* Card: Layout-Preset */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Layout-Preset', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Vordefinierte Layouts für Job-Cards', 'recruiting-playbook' ) }
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
									{ __( 'Kompakt', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Wenig Padding, platzsparend', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
						<RadioGroupItem value="standard">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Standard', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Ausgewogenes Layout', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
						<RadioGroupItem value="spacious">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Großzügig', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Viel Weißraum', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
					</RadioGroup>
				</CardContent>
			</Card>

			{ /* Card: Erscheinungsbild */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Erscheinungsbild', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Visuelle Eigenschaften der Job-Cards und Formularbox', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Eckenradius */ }
					<Slider
						label={ __( 'Eckenradius', 'recruiting-playbook' ) }
						value={ settings.card_border_radius ?? 8 }
						onChange={ ( value ) => onUpdate( 'card_border_radius', value ) }
						min={ 0 }
						max={ 24 }
						step={ 1 }
						unit="px"
					/>

					{ /* Schatten */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Schatten', 'recruiting-playbook' ) }
						</Label>
						<RadioGroup
							value={ settings.card_shadow || 'light' }
							onValueChange={ ( value ) => onUpdate( 'card_shadow', value ) }
							variant="buttons"
						>
							<RadioGroupItem value="none">
								{ __( 'Keiner', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="light">
								{ __( 'Leicht', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="medium">
								{ __( 'Mittel', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="strong">
								{ __( 'Stark', 'recruiting-playbook' ) }
							</RadioGroupItem>
						</RadioGroup>
					</div>

					{ /* Rahmen */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<Label htmlFor="card_border_show">
							{ __( 'Rahmen anzeigen', 'recruiting-playbook' ) }
						</Label>
						<Switch
							id="card_border_show"
							checked={ settings.card_border_show ?? true }
							onCheckedChange={ ( checked ) => onUpdate( 'card_border_show', checked ) }
						/>
					</div>

					{ /* Rahmenfarbe (nur wenn Rahmen aktiv) */ }
					{ settings.card_border_show && (
						<div className="rp-flex rp-items-center rp-justify-between rp-pl-4">
							<Label htmlFor="card_border_color">
								{ __( 'Rahmenfarbe', 'recruiting-playbook' ) }
							</Label>
							<ColorPicker
								id="card_border_color"
								value={ settings.card_border_color || '#e5e7eb' }
								onChange={ ( color ) => onUpdate( 'card_border_color', color ) }
							/>
						</div>
					) }

					{ /* Hintergrund */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<Label htmlFor="card_background">
							{ __( 'Hintergrund', 'recruiting-playbook' ) }
						</Label>
						<ColorPicker
							id="card_background"
							value={ settings.card_background || '#ffffff' }
							onChange={ ( color ) => onUpdate( 'card_background', color ) }
						/>
					</div>

					{ /* Hover-Effekt */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Hover-Effekt', 'recruiting-playbook' ) }
						</Label>
						<RadioGroup
							value={ settings.card_hover_effect || 'lift' }
							onValueChange={ ( value ) => onUpdate( 'card_hover_effect', value ) }
							variant="buttons"
						>
							<RadioGroupItem value="none">
								{ __( 'Keiner', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="lift">
								{ __( 'Hochheben', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="glow">
								{ __( 'Leuchten', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="border">
								{ __( 'Rahmen', 'recruiting-playbook' ) }
							</RadioGroupItem>
						</RadioGroup>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default CardsPanel;

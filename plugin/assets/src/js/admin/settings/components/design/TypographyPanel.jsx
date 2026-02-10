/**
 * TypographyPanel Component
 *
 * Tab: Typografie - Schriftgrößen, Zeilenabstände, Links.
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
 * TypographyPanel Component
 *
 * @param {Object}   props                     Component props
 * @param {Object}   props.settings            Current design settings
 * @param {Function} props.onUpdate            Update single setting
 * @param {string}   props.computedPrimaryColor Computed primary color
 * @return {JSX.Element} Component
 */
export function TypographyPanel( { settings, onUpdate, computedPrimaryColor } ) {
	return (
		<div className="rp-space-y-4">
			{ /* Card: Schriftgrößen */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Schriftgrößen', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Größen für Überschriften und Fließtext in rem', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					<Slider
						label="H1"
						value={ settings.font_size_h1 || 2.25 }
						onChange={ ( value ) => onUpdate( 'font_size_h1', value ) }
						min={ 1.5 }
						max={ 4 }
						step={ 0.125 }
						unit="rem"
					/>
					<Slider
						label="H2"
						value={ settings.font_size_h2 || 1.875 }
						onChange={ ( value ) => onUpdate( 'font_size_h2', value ) }
						min={ 1.25 }
						max={ 3 }
						step={ 0.125 }
						unit="rem"
					/>
					<Slider
						label="H3"
						value={ settings.font_size_h3 || 1.5 }
						onChange={ ( value ) => onUpdate( 'font_size_h3', value ) }
						min={ 1 }
						max={ 2.5 }
						step={ 0.125 }
						unit="rem"
					/>
					<Slider
						label={ __( 'Text', 'recruiting-playbook' ) }
						value={ settings.font_size_body || 1 }
						onChange={ ( value ) => onUpdate( 'font_size_body', value ) }
						min={ 0.875 }
						max={ 1.25 }
						step={ 0.0625 }
						unit="rem"
					/>
					<Slider
						label={ __( 'Klein', 'recruiting-playbook' ) }
						value={ settings.font_size_small || 0.875 }
						onChange={ ( value ) => onUpdate( 'font_size_small', value ) }
						min={ 0.625 }
						max={ 1 }
						step={ 0.0625 }
						unit="rem"
					/>
				</CardContent>
			</Card>

			{ /* Card: Zeilenabstand */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Zeilenabstand', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Vertikaler Abstand zwischen Textzeilen', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					<Slider
						label={ __( 'Überschriften', 'recruiting-playbook' ) }
						value={ settings.line_height_heading || 1.2 }
						onChange={ ( value ) => onUpdate( 'line_height_heading', value ) }
						min={ 1.0 }
						max={ 1.5 }
						step={ 0.05 }
					/>
					<Slider
						label={ __( 'Fließtext', 'recruiting-playbook' ) }
						value={ settings.line_height_body || 1.6 }
						onChange={ ( value ) => onUpdate( 'line_height_body', value ) }
						min={ 1.3 }
						max={ 2.0 }
						step={ 0.05 }
					/>
				</CardContent>
			</Card>

			{ /* Card: Abstände (Stellenausschreibung) */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Abstände', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Abstände in der Stellenausschreibung (em)', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					<Slider
						label={ __( 'Abstand über Überschriften', 'recruiting-playbook' ) }
						value={ settings.heading_margin_top || 1.5 }
						onChange={ ( value ) => onUpdate( 'heading_margin_top', value ) }
						min={ 0.5 }
						max={ 3 }
						step={ 0.25 }
						unit="em"
					/>
					<Slider
						label={ __( 'Abstand unter Überschriften', 'recruiting-playbook' ) }
						value={ settings.heading_margin_bottom || 0.5 }
						onChange={ ( value ) => onUpdate( 'heading_margin_bottom', value ) }
						min={ 0.25 }
						max={ 1.5 }
						step={ 0.125 }
						unit="em"
					/>
					<Slider
						label={ __( 'Absatz-Abstand', 'recruiting-playbook' ) }
						value={ settings.paragraph_spacing || 1 }
						onChange={ ( value ) => onUpdate( 'paragraph_spacing', value ) }
						min={ 0.5 }
						max={ 2 }
						step={ 0.125 }
						unit="em"
					/>
				</CardContent>
			</Card>

			{ /* Card: Links */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Links', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Farbe und Stil von Verlinkungen', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Primärfarbe verwenden */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="link_use_primary">
								{ __( 'Primärfarbe verwenden', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Links übernehmen die Primärfarbe', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="link_use_primary"
							checked={ settings.link_use_primary ?? true }
							onCheckedChange={ ( checked ) => onUpdate( 'link_use_primary', checked ) }
						/>
					</div>

					{ /* Link-Farbe */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<Label htmlFor="link_color">
							{ __( 'Link-Farbe', 'recruiting-playbook' ) }
						</Label>
						<ColorPicker
							id="link_color"
							value={ settings.link_use_primary ? computedPrimaryColor : ( settings.link_color || '#2563eb' ) }
							onChange={ ( color ) => onUpdate( 'link_color', color ) }
							disabled={ settings.link_use_primary }
						/>
					</div>

					{ /* Unterstreichung */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Unterstreichung', 'recruiting-playbook' ) }
						</Label>
						<RadioGroup
							value={ settings.link_decoration || 'underline' }
							onValueChange={ ( value ) => onUpdate( 'link_decoration', value ) }
							variant="buttons"
						>
							<RadioGroupItem value="none">
								{ __( 'Keine', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="underline">
								{ __( 'Immer', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="hover">
								{ __( 'Bei Hover', 'recruiting-playbook' ) }
							</RadioGroupItem>
						</RadioGroup>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default TypographyPanel;

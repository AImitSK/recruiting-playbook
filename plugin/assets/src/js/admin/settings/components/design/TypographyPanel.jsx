/**
 * TypographyPanel Component
 *
 * Tab: Typography - Font sizes, line heights, links.
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
			{ /* Card: Font Sizes */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Font Sizes', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Sizes for headings and body text in rem', 'recruiting-playbook' ) }
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
						label={ __( 'Small', 'recruiting-playbook' ) }
						value={ settings.font_size_small || 0.875 }
						onChange={ ( value ) => onUpdate( 'font_size_small', value ) }
						min={ 0.625 }
						max={ 1 }
						step={ 0.0625 }
						unit="rem"
					/>
				</CardContent>
			</Card>

			{ /* Card: Line Height */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Line Height', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Vertical spacing between lines of text', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					<Slider
						label={ __( 'Headings', 'recruiting-playbook' ) }
						value={ settings.line_height_heading || 1.2 }
						onChange={ ( value ) => onUpdate( 'line_height_heading', value ) }
						min={ 1.0 }
						max={ 1.5 }
						step={ 0.05 }
					/>
					<Slider
						label={ __( 'Body Text', 'recruiting-playbook' ) }
						value={ settings.line_height_body || 1.6 }
						onChange={ ( value ) => onUpdate( 'line_height_body', value ) }
						min={ 1.3 }
						max={ 2.0 }
						step={ 0.05 }
					/>
				</CardContent>
			</Card>

			{ /* Card: Spacing (Job Listing) */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Spacing', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Spacing in the job listing (em)', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					<Slider
						label={ __( 'Spacing Above Headings', 'recruiting-playbook' ) }
						value={ settings.heading_margin_top || 1.5 }
						onChange={ ( value ) => onUpdate( 'heading_margin_top', value ) }
						min={ 0.5 }
						max={ 3 }
						step={ 0.25 }
						unit="em"
					/>
					<Slider
						label={ __( 'Spacing Below Headings', 'recruiting-playbook' ) }
						value={ settings.heading_margin_bottom || 0.5 }
						onChange={ ( value ) => onUpdate( 'heading_margin_bottom', value ) }
						min={ 0.25 }
						max={ 1.5 }
						step={ 0.125 }
						unit="em"
					/>
					<Slider
						label={ __( 'Paragraph Spacing', 'recruiting-playbook' ) }
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
						{ __( 'Color and style of links', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Use Primary Color */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="link_use_primary">
								{ __( 'Use Primary Color', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Links inherit the primary color', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="link_use_primary"
							checked={ settings.link_use_primary ?? true }
							onCheckedChange={ ( checked ) => onUpdate( 'link_use_primary', checked ) }
						/>
					</div>

					{ /* Link Color */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<Label htmlFor="link_color">
							{ __( 'Link Color', 'recruiting-playbook' ) }
						</Label>
						<ColorPicker
							id="link_color"
							value={ settings.link_use_primary ? computedPrimaryColor : ( settings.link_color || '#2563eb' ) }
							onChange={ ( color ) => onUpdate( 'link_color', color ) }
							disabled={ settings.link_use_primary }
						/>
					</div>

					{ /* Underline */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Underline', 'recruiting-playbook' ) }
						</Label>
						<RadioGroup
							value={ settings.link_decoration || 'underline' }
							onValueChange={ ( value ) => onUpdate( 'link_decoration', value ) }
							variant="buttons"
						>
							<RadioGroupItem value="none">
								{ __( 'None', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="underline">
								{ __( 'Always', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="hover">
								{ __( 'On Hover', 'recruiting-playbook' ) }
							</RadioGroupItem>
						</RadioGroup>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default TypographyPanel;

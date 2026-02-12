/**
 * BrandingPanel Component
 *
 * Tab: Branding - Colors, Logo, White-Label Settings.
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Switch } from '../../../components/ui/switch';
import { Label } from '../../../components/ui/label';
import { ColorPicker } from '../../../components/ui/color-picker';
import { Slider } from '../../../components/ui/slider';
import { Select, SelectOption } from '../../../components/ui/select';

/**
 * BrandingPanel Component
 *
 * @param {Object}   props                     Component props
 * @param {Object}   props.settings            Current design settings
 * @param {Object}   props.meta                Meta info (computed values)
 * @param {Function} props.onUpdate            Update single setting
 * @param {string}   props.computedPrimaryColor Computed primary color
 * @return {JSX.Element} Component
 */
export function BrandingPanel( { settings, meta, onUpdate, computedPrimaryColor } ) {
	return (
		<div className="rp-space-y-4">
			{ /* Card: Colors */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Colors', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Primary color for buttons, links and accents', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Use theme colors */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="use_theme_colors">
								{ __( 'Use theme colors', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Inherit primary color from the active WordPress theme', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="use_theme_colors"
							checked={ settings.use_theme_colors ?? true }
							onCheckedChange={ ( checked ) => onUpdate( 'use_theme_colors', checked ) }
						/>
					</div>

					{ /* Primary color */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="primary_color">
								{ __( 'Primary color', 'recruiting-playbook' ) }
							</Label>
							{ settings.use_theme_colors && meta?.primary_color_computed && (
								<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
									{ __( 'From theme:', 'recruiting-playbook' ) } { meta.primary_color_computed }
								</p>
							) }
						</div>
						<ColorPicker
							id="primary_color"
							value={ settings.use_theme_colors ? computedPrimaryColor : ( settings.primary_color || '#2563eb' ) }
							onChange={ ( color ) => onUpdate( 'primary_color', color ) }
							disabled={ settings.use_theme_colors }
						/>
					</div>

					{ /* Current primary color display */ }
					<div className="rp-flex rp-items-center rp-gap-2 rp-p-3 rp-bg-gray-50 rp-rounded-md">
						<div
							className="rp-w-6 rp-h-6 rp-rounded rp-border rp-border-gray-300"
							style={ { backgroundColor: computedPrimaryColor } }
						/>
						<span className="rp-text-sm rp-text-gray-600">
							{ __( 'Active primary color:', 'recruiting-playbook' ) }
							<code className="rp-ml-1 rp-font-mono rp-text-xs">{ computedPrimaryColor }</code>
						</span>
					</div>
				</CardContent>
			</Card>

			{ /* Card: Logo */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Logo', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Logo for email signatures and document headers', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Use theme logo */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="use_theme_logo">
								{ __( 'Use theme logo', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Inherit custom logo from WordPress theme', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="use_theme_logo"
							checked={ settings.use_theme_logo ?? true }
							onCheckedChange={ ( checked ) => onUpdate( 'use_theme_logo', checked ) }
						/>
					</div>

					{ /* Show logo in signature */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="logo_in_signature">
								{ __( 'Logo in email signature', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Automatically insert logo in email signatures', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="logo_in_signature"
							checked={ settings.logo_in_signature ?? false }
							onCheckedChange={ ( checked ) => onUpdate( 'logo_in_signature', checked ) }
						/>
					</div>

					{ /* Signature logo options (only when active) */ }
					{ settings.logo_in_signature && (
						<div className="rp-pl-4 rp-border-l-2 rp-border-gray-200 rp-space-y-3">
							{ /* Position */ }
							<div>
								<Label htmlFor="signature_logo_position" className="rp-mb-2 rp-block">
									{ __( 'Position', 'recruiting-playbook' ) }
								</Label>
								<Select
									value={ settings.signature_logo_position || 'top' }
									onChange={ ( e ) => onUpdate( 'signature_logo_position', e.target.value ) }
								>
									<SelectOption value="top">{ __( 'Top', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="bottom">{ __( 'Bottom', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="left">{ __( 'Left', 'recruiting-playbook' ) }</SelectOption>
								</Select>
							</div>

							{ /* Max. height */ }
							<Slider
								label={ __( 'Max. height', 'recruiting-playbook' ) }
								value={ settings.signature_logo_max_height || 60 }
								onChange={ ( value ) => onUpdate( 'signature_logo_max_height', value ) }
								min={ 30 }
								max={ 120 }
								step={ 5 }
								unit="px"
							/>
						</div>
					) }
				</CardContent>
			</Card>

			{ /* Card: White-Label */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'White-Label', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Remove plugin branding', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Hide branding */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="hide_branding">
								{ __( 'Hide "Powered by"', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Removes "Powered by Recruiting Playbook" from frontend', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="hide_branding"
							checked={ settings.hide_branding ?? false }
							onCheckedChange={ ( checked ) => onUpdate( 'hide_branding', checked ) }
						/>
					</div>

					{ /* White-Label emails */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="hide_email_branding">
								{ __( 'White-label emails', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Removes plugin branding from email footers', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="hide_email_branding"
							checked={ settings.hide_email_branding ?? false }
							onCheckedChange={ ( checked ) => onUpdate( 'hide_email_branding', checked ) }
						/>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default BrandingPanel;

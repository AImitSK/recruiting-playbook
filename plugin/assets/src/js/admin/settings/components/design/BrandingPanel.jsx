/**
 * BrandingPanel Component
 *
 * Tab: Branding - Farben, Logo, White-Label Einstellungen.
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
			{ /* Card: Farben */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Farben', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Primärfarbe für Buttons, Links und Akzente', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Theme-Farben verwenden */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="use_theme_colors">
								{ __( 'Theme-Farben verwenden', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Primärfarbe aus dem aktiven WordPress-Theme übernehmen', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="use_theme_colors"
							checked={ settings.use_theme_colors ?? true }
							onCheckedChange={ ( checked ) => onUpdate( 'use_theme_colors', checked ) }
						/>
					</div>

					{ /* Primärfarbe */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="primary_color">
								{ __( 'Primärfarbe', 'recruiting-playbook' ) }
							</Label>
							{ settings.use_theme_colors && meta?.primary_color_computed && (
								<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
									{ __( 'Vom Theme:', 'recruiting-playbook' ) } { meta.primary_color_computed }
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

					{ /* Aktuelle Primärfarbe Anzeige */ }
					<div className="rp-flex rp-items-center rp-gap-2 rp-p-3 rp-bg-gray-50 rp-rounded-md">
						<div
							className="rp-w-6 rp-h-6 rp-rounded rp-border rp-border-gray-300"
							style={ { backgroundColor: computedPrimaryColor } }
						/>
						<span className="rp-text-sm rp-text-gray-600">
							{ __( 'Aktive Primärfarbe:', 'recruiting-playbook' ) }
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
						{ __( 'Logo für E-Mail-Signaturen und Dokumenten-Header', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Theme-Logo verwenden */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="use_theme_logo">
								{ __( 'Theme-Logo verwenden', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Custom Logo aus dem WordPress-Theme übernehmen', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="use_theme_logo"
							checked={ settings.use_theme_logo ?? true }
							onCheckedChange={ ( checked ) => onUpdate( 'use_theme_logo', checked ) }
						/>
					</div>

					{ /* Logo in Signatur anzeigen */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="logo_in_signature">
								{ __( 'Logo in E-Mail-Signatur', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Logo automatisch in E-Mail-Signaturen einfügen', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="logo_in_signature"
							checked={ settings.logo_in_signature ?? false }
							onCheckedChange={ ( checked ) => onUpdate( 'logo_in_signature', checked ) }
						/>
					</div>

					{ /* Signatur-Logo Optionen (nur wenn aktiv) */ }
					{ settings.logo_in_signature && (
						<div className="rp-pl-4 rp-border-l-2 rp-border-gray-200 rp-space-y-3">
							{ /* Position */ }
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label htmlFor="signature_logo_position">
									{ __( 'Position', 'recruiting-playbook' ) }
								</Label>
								<Select
									value={ settings.signature_logo_position || 'top' }
									onChange={ ( e ) => onUpdate( 'signature_logo_position', e.target.value ) }
									style={ { width: '128px' } }
								>
									<SelectOption value="top">{ __( 'Oben', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="bottom">{ __( 'Unten', 'recruiting-playbook' ) }</SelectOption>
									<SelectOption value="left">{ __( 'Links', 'recruiting-playbook' ) }</SelectOption>
								</Select>
							</div>

							{ /* Max. Höhe */ }
							<Slider
								label={ __( 'Max. Höhe', 'recruiting-playbook' ) }
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
						{ __( 'Plugin-Branding entfernen', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Branding ausblenden */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="hide_branding">
								{ __( '"Powered by" ausblenden', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Entfernt "Powered by Recruiting Playbook" im Frontend', 'recruiting-playbook' ) }
							</p>
						</div>
						<Switch
							id="hide_branding"
							checked={ settings.hide_branding ?? false }
							onCheckedChange={ ( checked ) => onUpdate( 'hide_branding', checked ) }
						/>
					</div>

					{ /* White-Label E-Mails */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<div>
							<Label htmlFor="hide_email_branding">
								{ __( 'White-Label E-Mails', 'recruiting-playbook' ) }
							</Label>
							<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
								{ __( 'Entfernt Plugin-Branding aus E-Mail-Footern', 'recruiting-playbook' ) }
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

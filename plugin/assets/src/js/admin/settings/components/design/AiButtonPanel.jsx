/**
 * AiButtonPanel Component
 *
 * Tab: KI-Button - Stil, Preset und Texte.
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Switch } from '../../../components/ui/switch';
import { Label } from '../../../components/ui/label';
import { Input } from '../../../components/ui/input';
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
import { Sparkles, Check, Star, Zap, Target, User } from 'lucide-react';

/**
 * Icon options for AI buttons
 */
const iconOptions = [
	{ value: 'sparkles', label: 'Sparkles', Icon: Sparkles },
	{ value: 'checkmark', label: 'Checkmark', Icon: Check },
	{ value: 'star', label: 'Star', Icon: Star },
	{ value: 'lightning', label: 'Lightning', Icon: Zap },
	{ value: 'target', label: 'Target', Icon: Target },
	{ value: 'user', label: 'User', Icon: User },
];

/**
 * Preset button styles
 */
const presetStyles = {
	gradient: {
		label: __( 'Farbverlauf', 'recruiting-playbook' ),
		description: __( 'Lila-Pink Verlauf', 'recruiting-playbook' ),
		style: {
			background: 'linear-gradient(135deg, #8b5cf6, #ec4899)',
			color: '#ffffff',
		},
	},
	outline: {
		label: __( 'Outline', 'recruiting-playbook' ),
		description: __( 'Transparenter Hintergrund', 'recruiting-playbook' ),
		style: {
			background: 'transparent',
			border: '2px solid #8b5cf6',
			color: '#8b5cf6',
		},
	},
	minimal: {
		label: __( 'Minimal', 'recruiting-playbook' ),
		description: __( 'Dezent und schlicht', 'recruiting-playbook' ),
		style: {
			background: '#f3f4f6',
			color: '#374151',
		},
	},
	glow: {
		label: __( 'Glow', 'recruiting-playbook' ),
		description: __( 'Mit Leuchteffekt', 'recruiting-playbook' ),
		style: {
			background: '#8b5cf6',
			color: '#ffffff',
			boxShadow: '0 0 20px rgba(139, 92, 246, 0.5)',
		},
	},
	soft: {
		label: __( 'Soft', 'recruiting-playbook' ),
		description: __( 'Heller Hintergrund', 'recruiting-playbook' ),
		style: {
			background: 'rgba(139, 92, 246, 0.1)',
			color: '#8b5cf6',
		},
	},
};

/**
 * AiButtonPanel Component
 *
 * @param {Object}   props                     Component props
 * @param {Object}   props.settings            Current design settings
 * @param {Function} props.onUpdate            Update single setting
 * @param {string}   props.computedPrimaryColor Computed primary color
 * @return {JSX.Element} Component
 */
export function AiButtonPanel( { settings, onUpdate, computedPrimaryColor } ) {
	const styleMode = settings.ai_button_style || 'preset';
	const preset = settings.ai_button_preset || 'gradient';
	const selectedIcon = iconOptions.find( ( opt ) => opt.value === ( settings.ai_match_button_icon || 'sparkles' ) );
	const IconComponent = selectedIcon?.Icon || Sparkles;

	// Compute preview style based on mode
	const getPreviewStyle = () => {
		if ( styleMode === 'theme' ) {
			return {
				background: computedPrimaryColor,
				color: '#ffffff',
			};
		}
		if ( styleMode === 'preset' ) {
			return presetStyles[ preset ]?.style || presetStyles.gradient.style;
		}
		// Manual mode
		if ( settings.ai_button_use_gradient ) {
			return {
				background: `linear-gradient(135deg, ${ settings.ai_button_color_1 || '#8b5cf6' }, ${ settings.ai_button_color_2 || '#ec4899' })`,
				color: settings.ai_button_text_color || '#ffffff',
			};
		}
		return {
			background: settings.ai_button_color_1 || '#8b5cf6',
			color: settings.ai_button_text_color || '#ffffff',
		};
	};

	return (
		<div className="rp-space-y-4">
			{ /* Card: Stil-Modus */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'KI-Button Stil', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Globaler Stil für alle KI-Buttons im Plugin', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Stil-Modus */ }
					<RadioGroup
						value={ styleMode }
						onValueChange={ ( value ) => onUpdate( 'ai_button_style', value ) }
						variant="cards"
						orientation="horizontal"
						className="rp-grid rp-grid-cols-3 rp-gap-3"
					>
						<RadioGroupItem value="theme">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Theme', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Primärfarbe', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
						<RadioGroupItem value="preset">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Preset', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Vordefiniert', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
						<RadioGroupItem value="manual">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Manuell', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Eigene Farben', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
					</RadioGroup>

					{ /* Vorschau */ }
					<div className="rp-p-4 rp-bg-gray-50 rp-rounded-lg rp-flex rp-items-center rp-justify-center">
						<button
							type="button"
							className="rp-px-4 rp-py-2 rp-rounded-lg rp-font-medium rp-text-sm rp-flex rp-items-center rp-gap-2 rp-transition-all"
							style={ {
								...getPreviewStyle(),
								borderRadius: `${ settings.ai_button_radius || 8 }px`,
							} }
						>
							<IconComponent className="rp-w-4 rp-h-4" />
							{ settings.ai_match_button_text || __( 'KI-Matching starten', 'recruiting-playbook' ) }
						</button>
					</div>
				</CardContent>
			</Card>

			{ /* Card: Preset-Auswahl (nur bei preset) */ }
			{ styleMode === 'preset' && (
				<Card>
					<CardHeader>
						<CardTitle>{ __( 'Preset-Auswahl', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ __( 'Vordefinierte KI-Button Designs', 'recruiting-playbook' ) }
						</CardDescription>
					</CardHeader>
					<CardContent>
						<div className="rp-grid rp-grid-cols-2 rp-gap-2">
							{ Object.entries( presetStyles ).map( ( [ key, presetData ] ) => (
								<button
									key={ key }
									type="button"
									onClick={ () => onUpdate( 'ai_button_preset', key ) }
									className={ `rp-p-3 rp-rounded-lg rp-border-2 rp-transition-all rp-text-left ${
										preset === key
											? 'rp-border-blue-500 rp-bg-blue-50'
											: 'rp-border-gray-200 hover:rp-border-gray-300'
									}` }
								>
									<div
										className="rp-px-3 rp-py-1.5 rp-rounded rp-text-xs rp-font-medium rp-inline-flex rp-items-center rp-gap-1.5 rp-mb-2"
										style={ presetData.style }
									>
										<Sparkles className="rp-w-3 rp-h-3" />
										KI
									</div>
									<div className="rp-text-sm rp-font-medium">{ presetData.label }</div>
									<div className="rp-text-xs rp-text-gray-500">{ presetData.description }</div>
								</button>
							) ) }
						</div>
					</CardContent>
				</Card>
			) }

			{ /* Card: Manuelle Farben (nur bei manual) */ }
			{ styleMode === 'manual' && (
				<Card>
					<CardHeader>
						<CardTitle>{ __( 'Manuelle Farben', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ __( 'Individuelle Farbeinstellungen', 'recruiting-playbook' ) }
						</CardDescription>
					</CardHeader>
					<CardContent className="rp-space-y-4">
						{ /* Farbverlauf */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<div>
								<Label htmlFor="ai_button_use_gradient">
									{ __( 'Farbverlauf', 'recruiting-playbook' ) }
								</Label>
								<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
									{ __( 'Zwei Farben als Verlauf mischen', 'recruiting-playbook' ) }
								</p>
							</div>
							<Switch
								id="ai_button_use_gradient"
								checked={ settings.ai_button_use_gradient ?? true }
								onCheckedChange={ ( checked ) => onUpdate( 'ai_button_use_gradient', checked ) }
							/>
						</div>

						{ /* Farbe 1 */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<Label>
								{ settings.ai_button_use_gradient
									? __( 'Farbe 1 (Start)', 'recruiting-playbook' )
									: __( 'Hintergrund', 'recruiting-playbook' )
								}
							</Label>
							<ColorPicker
								value={ settings.ai_button_color_1 || '#8b5cf6' }
								onChange={ ( color ) => onUpdate( 'ai_button_color_1', color ) }
							/>
						</div>

						{ /* Farbe 2 (nur bei Gradient) */ }
						{ settings.ai_button_use_gradient && (
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Farbe 2 (Ende)', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.ai_button_color_2 || '#ec4899' }
									onChange={ ( color ) => onUpdate( 'ai_button_color_2', color ) }
								/>
							</div>
						) }

						{ /* Textfarbe */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<Label>{ __( 'Textfarbe', 'recruiting-playbook' ) }</Label>
							<ColorPicker
								value={ settings.ai_button_text_color || '#ffffff' }
								onChange={ ( color ) => onUpdate( 'ai_button_text_color', color ) }
							/>
						</div>

						{ /* Radius */ }
						<Slider
							label={ __( 'Eckenradius', 'recruiting-playbook' ) }
							value={ settings.ai_button_radius ?? 8 }
							onChange={ ( value ) => onUpdate( 'ai_button_radius', value ) }
							min={ 0 }
							max={ 24 }
							step={ 1 }
							unit="px"
						/>
					</CardContent>
				</Card>
			) }

			{ /* Card: Button-Texte */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'KI-Matching Button', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Text und Icon für den KI-Matching Button', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Text */ }
					<div>
						<Label htmlFor="ai_match_button_text" className="rp-mb-1.5 rp-block">
							{ __( 'Button-Text', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="ai_match_button_text"
							value={ settings.ai_match_button_text || '' }
							onChange={ ( e ) => onUpdate( 'ai_match_button_text', e.target.value ) }
							placeholder={ __( 'KI-Matching starten', 'recruiting-playbook' ) }
						/>
					</div>

					{ /* Icon */ }
					<div className="rp-flex rp-items-center rp-justify-between">
						<Label>{ __( 'Icon', 'recruiting-playbook' ) }</Label>
						<Select
							value={ settings.ai_match_button_icon || 'sparkles' }
							onValueChange={ ( value ) => onUpdate( 'ai_match_button_icon', value ) }
						>
							<SelectTrigger className="rp-w-40">
								<SelectValue />
							</SelectTrigger>
							<SelectContent>
								{ iconOptions.map( ( option ) => (
									<SelectItem key={ option.value } value={ option.value }>
										<div className="rp-flex rp-items-center rp-gap-2">
											<option.Icon className="rp-w-4 rp-h-4" />
											{ option.label }
										</div>
									</SelectItem>
								) ) }
							</SelectContent>
						</Select>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default AiButtonPanel;

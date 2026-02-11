/**
 * AiButtonPanel Component
 *
 * Tab: AI Button - Style, Preset and Texts.
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
import { Select, SelectOption } from '../../../components/ui/select';
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
		label: __( 'Gradient', 'recruiting-playbook' ),
		description: __( 'Purple-Pink Gradient', 'recruiting-playbook' ),
		style: {
			background: 'linear-gradient(135deg, #8b5cf6, #ec4899)',
			color: '#ffffff',
		},
	},
	outline: {
		label: __( 'Outline', 'recruiting-playbook' ),
		description: __( 'Transparent Background', 'recruiting-playbook' ),
		style: {
			background: 'transparent',
			border: '2px solid #8b5cf6',
			color: '#8b5cf6',
		},
	},
	minimal: {
		label: __( 'Minimal', 'recruiting-playbook' ),
		description: __( 'Subtle and Simple', 'recruiting-playbook' ),
		style: {
			background: '#f3f4f6',
			color: '#374151',
		},
	},
	glow: {
		label: __( 'Glow', 'recruiting-playbook' ),
		description: __( 'With Glow Effect', 'recruiting-playbook' ),
		style: {
			background: '#8b5cf6',
			color: '#ffffff',
			boxShadow: '0 0 20px rgba(139, 92, 246, 0.5)',
		},
	},
	soft: {
		label: __( 'Soft', 'recruiting-playbook' ),
		description: __( 'Light Background', 'recruiting-playbook' ),
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
			{ /* Card: Style Mode */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'AI Button Style', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Global style for all AI buttons in the plugin', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Style Mode */ }
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
									{ __( 'Primary Color', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
						<RadioGroupItem value="preset">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Preset', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Predefined', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
						<RadioGroupItem value="manual">
							<div className="rp-text-center">
								<div className="rp-text-sm rp-font-medium">
									{ __( 'Manual', 'recruiting-playbook' ) }
								</div>
								<div className="rp-text-xs rp-text-gray-500 rp-mt-1">
									{ __( 'Custom Colors', 'recruiting-playbook' ) }
								</div>
							</div>
						</RadioGroupItem>
					</RadioGroup>

					{ /* Preview */ }
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
							{ settings.ai_match_button_text || __( 'Start AI Matching', 'recruiting-playbook' ) }
						</button>
					</div>
				</CardContent>
			</Card>

			{ /* Card: Preset Selection (only for preset) */ }
			{ styleMode === 'preset' && (
				<Card>
					<CardHeader>
						<CardTitle>{ __( 'Preset Selection', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ __( 'Predefined AI Button Designs', 'recruiting-playbook' ) }
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
										AI
									</div>
									<div className="rp-text-sm rp-font-medium">{ presetData.label }</div>
									<div className="rp-text-xs rp-text-gray-500">{ presetData.description }</div>
								</button>
							) ) }
						</div>
					</CardContent>
				</Card>
			) }

			{ /* Card: Manual Colors (only for manual) */ }
			{ styleMode === 'manual' && (
				<Card>
					<CardHeader>
						<CardTitle>{ __( 'Manual Colors', 'recruiting-playbook' ) }</CardTitle>
						<CardDescription>
							{ __( 'Individual Color Settings', 'recruiting-playbook' ) }
						</CardDescription>
					</CardHeader>
					<CardContent className="rp-space-y-4">
						{ /* Gradient */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<div>
								<Label htmlFor="ai_button_use_gradient">
									{ __( 'Gradient', 'recruiting-playbook' ) }
								</Label>
								<p className="rp-text-xs rp-text-gray-500 rp-mt-0.5">
									{ __( 'Mix two colors as gradient', 'recruiting-playbook' ) }
								</p>
							</div>
							<Switch
								id="ai_button_use_gradient"
								checked={ settings.ai_button_use_gradient ?? true }
								onCheckedChange={ ( checked ) => onUpdate( 'ai_button_use_gradient', checked ) }
							/>
						</div>

						{ /* Color 1 */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<Label>
								{ settings.ai_button_use_gradient
									? __( 'Color 1 (Start)', 'recruiting-playbook' )
									: __( 'Background', 'recruiting-playbook' )
								}
							</Label>
							<ColorPicker
								value={ settings.ai_button_color_1 || '#8b5cf6' }
								onChange={ ( color ) => onUpdate( 'ai_button_color_1', color ) }
							/>
						</div>

						{ /* Color 2 (only for Gradient) */ }
						{ settings.ai_button_use_gradient && (
							<div className="rp-flex rp-items-center rp-justify-between">
								<Label>{ __( 'Color 2 (End)', 'recruiting-playbook' ) }</Label>
								<ColorPicker
									value={ settings.ai_button_color_2 || '#ec4899' }
									onChange={ ( color ) => onUpdate( 'ai_button_color_2', color ) }
								/>
							</div>
						) }

						{ /* Text Color */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<Label>{ __( 'Text Color', 'recruiting-playbook' ) }</Label>
							<ColorPicker
								value={ settings.ai_button_text_color || '#ffffff' }
								onChange={ ( color ) => onUpdate( 'ai_button_text_color', color ) }
							/>
						</div>

						{ /* Radius */ }
						<Slider
							label={ __( 'Border Radius', 'recruiting-playbook' ) }
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

			{ /* Card: Button Texts */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'AI Matching Button', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Text and icon for the AI Matching button', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Text */ }
					<div>
						<Label htmlFor="ai_match_button_text" className="rp-mb-1.5 rp-block">
							{ __( 'Button Text', 'recruiting-playbook' ) }
						</Label>
						<Input
							id="ai_match_button_text"
							value={ settings.ai_match_button_text || '' }
							onChange={ ( e ) => onUpdate( 'ai_match_button_text', e.target.value ) }
							placeholder={ __( 'Start AI Matching', 'recruiting-playbook' ) }
						/>
					</div>

					{ /* Icon */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Icon', 'recruiting-playbook' ) }
						</Label>
						<Select
							value={ settings.ai_match_button_icon || 'sparkles' }
							onChange={ ( e ) => onUpdate( 'ai_match_button_icon', e.target.value ) }
						>
							{ iconOptions.map( ( option ) => (
								<SelectOption key={ option.value } value={ option.value }>
									{ option.label }
								</SelectOption>
							) ) }
						</Select>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default AiButtonPanel;

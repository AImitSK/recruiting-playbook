/**
 * LivePreview Component
 *
 * Sidebar-Komponente mit Live-Vorschau der Design-Einstellungen.
 *
 * @package RecruitingPlaybook
 */

import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { MapPin, Clock, Sparkles } from 'lucide-react';

/**
 * PreviewSection Component - Wrapper für Vorschau-Bereiche
 */
function PreviewSection( { title, children } ) {
	return (
		<div className="rp-space-y-2">
			<h4 className="rp-text-xs rp-font-semibold rp-text-gray-500 rp-uppercase rp-tracking-wide">
				{ title }
			</h4>
			{ children }
		</div>
	);
}

/**
 * Card-Preset-Definitionen (synchron mit CssGeneratorService)
 */
const CARD_PRESETS = {
	compact: {
		padding: '12px 16px',
		radius: 6,
		shadow: '0 1px 2px rgba(0,0,0,0.05)',
		border: '1px solid #e5e7eb',
		borderColor: '#e5e7eb',
		background: '#ffffff',
	},
	standard: {
		padding: '20px 24px',
		radius: 12,
		shadow: '0 4px 6px rgba(0,0,0,0.1)',
		border: 'none',
		borderColor: 'transparent',
		background: '#ffffff',
	},
	spacious: {
		padding: '32px 40px',
		radius: 16,
		shadow: '0 10px 25px rgba(0,0,0,0.1)',
		border: 'none',
		borderColor: 'transparent',
		background: '#ffffff',
	},
};

/**
 * JobCardPreview - Mini Job-Card Vorschau
 */
function JobCardPreview( { settings, computedPrimaryColor } ) {
	const cardStyle = useMemo( () => {
		// Preset als Basis laden.
		const preset = CARD_PRESETS[ settings.card_layout_preset ] || CARD_PRESETS.standard;

		// Schatten-Werte für individuelle Überschreibung.
		const shadowValues = {
			none: 'none',
			light: '0 1px 3px rgba(0,0,0,0.1)',
			medium: '0 4px 6px rgba(0,0,0,0.1)',
			strong: '0 10px 25px rgba(0,0,0,0.15)',
			preset: preset.shadow, // Preset-Schatten.
		};

		// Preset-Werte mit individuellen Überschreibungen.
		const background = settings.card_background && settings.card_background !== '#ffffff'
			? settings.card_background
			: preset.background;

		const radius = settings.card_border_radius !== undefined && settings.card_border_radius !== preset.radius
			? settings.card_border_radius
			: preset.radius;

		const shadow = settings.card_shadow && settings.card_shadow !== 'preset'
			? shadowValues[ settings.card_shadow ]
			: preset.shadow;

		let border = preset.border;
		if ( settings.card_border_show !== undefined ) {
			border = settings.card_border_show
				? `1px solid ${ settings.card_border_color || preset.borderColor }`
				: 'none';
		}

		return {
			backgroundColor: background,
			border,
			borderRadius: `${ radius }px`,
			boxShadow: shadow,
			padding: preset.padding,
		};
	}, [ settings ] );

	const badgeStyle = ( color ) => {
		const isSolid = settings.badge_style === 'solid';
		return {
			backgroundColor: isSolid ? color : `${ color }20`,
			color: isSolid ? '#ffffff' : color,
		};
	};

	const buttonStyle = useMemo( () => {
		// Custom Design: Custom-Farben mit Fallback auf Primärfarbe
		// Theme Design: Primärfarbe
		const bgColor = settings.button_use_custom_design
			? ( settings.button_bg_color || computedPrimaryColor )
			: computedPrimaryColor;

		const textColor = settings.button_use_custom_design
			? ( settings.button_text_color || '#ffffff' )
			: '#ffffff';

		return {
			backgroundColor: bgColor,
			color: textColor,
			borderRadius: `${ settings.button_border_radius ?? 6 }px`,
		};
	}, [ settings, computedPrimaryColor ] );

	return (
		<div style={ cardStyle }>
			{ /* Badges */ }
			{ settings.show_badges !== false && (
				<div className="rp-flex rp-gap-1 rp-mb-2">
					<span
						className="rp-px-1.5 rp-py-0.5 rp-text-[10px] rp-font-medium rp-rounded"
						style={ badgeStyle( settings.badge_color_new || '#22c55e' ) }
					>
						{ __( 'New', 'recruiting-playbook' ) }
					</span>
					<span
						className="rp-px-1.5 rp-py-0.5 rp-text-[10px] rp-font-medium rp-rounded"
						style={ badgeStyle( settings.badge_color_category || '#6b7280' ) }
					>
						IT
					</span>
				</div>
			) }

			{ /* Title */ }
			<h5
				className="rp-font-semibold rp-text-gray-900 rp-mb-1"
				style={ { fontSize: '0.8rem' } }
			>
				Senior Developer (m/w/d)
			</h5>

			{ /* Meta */ }
			<div className="rp-flex rp-items-center rp-gap-2 rp-text-[10px] rp-text-gray-500 rp-mb-2">
				{ settings.show_location !== false && (
					<span className="rp-flex rp-items-center rp-gap-0.5">
						<MapPin className="rp-w-2.5 rp-h-2.5" />
						Berlin
					</span>
				) }
				{ settings.show_employment_type !== false && (
					<span className="rp-flex rp-items-center rp-gap-0.5">
						<Clock className="rp-w-2.5 rp-h-2.5" />
						{ __( 'Full-time', 'recruiting-playbook' ) }
					</span>
				) }
			</div>

			{ /* Footer */ }
			<div className="rp-flex rp-items-center rp-justify-between">
				{ settings.show_salary !== false && (
					<span
						className="rp-px-1.5 rp-py-0.5 rp-text-[10px] rp-font-medium rp-rounded"
						style={ badgeStyle( settings.badge_color_salary || '#2563eb' ) }
					>
						60k-80k
					</span>
				) }
				<button
					type="button"
					className="rp-px-2 rp-py-1 rp-text-[10px] rp-font-medium"
					style={ buttonStyle }
				>
					{ __( 'Details', 'recruiting-playbook' ) }
				</button>
			</div>
		</div>
	);
}

/**
 * FormBoxPreview - Mini Formularbox Vorschau
 */
function FormBoxPreview( { settings, computedPrimaryColor } ) {
	const cardStyle = useMemo( () => {
		const shadowValues = {
			none: 'none',
			light: '0 1px 3px rgba(0,0,0,0.1)',
			medium: '0 4px 6px rgba(0,0,0,0.1)',
			strong: '0 10px 25px rgba(0,0,0,0.15)',
		};

		return {
			backgroundColor: settings.card_background || '#ffffff',
			border: settings.card_border_show
				? `1px solid ${ settings.card_border_color || '#e5e7eb' }`
				: 'none',
			borderRadius: `${ settings.card_border_radius ?? 8 }px`,
			boxShadow: shadowValues[ settings.card_shadow || 'light' ],
		};
	}, [ settings ] );

	const buttonStyle = useMemo( () => {
		// Custom Design: Custom-Farben mit Fallback auf Primärfarbe
		const bgColor = settings.button_use_custom_design
			? ( settings.button_bg_color || computedPrimaryColor )
			: computedPrimaryColor;

		const textColor = settings.button_use_custom_design
			? ( settings.button_text_color || '#ffffff' )
			: '#ffffff';

		return {
			backgroundColor: bgColor,
			color: textColor,
			borderRadius: `${ settings.button_border_radius ?? 6 }px`,
		};
	}, [ settings, computedPrimaryColor ] );

	return (
		<div className="rp-p-3" style={ cardStyle }>
			<h5
				className="rp-font-semibold rp-text-gray-900 rp-mb-2"
				style={ { fontSize: '0.75rem' } }
			>
				{ __( 'Apply now', 'recruiting-playbook' ) }
			</h5>

			{ /* Placeholder inputs */ }
			<div className="rp-space-y-1.5 rp-mb-2">
				<div className="rp-h-5 rp-bg-gray-100 rp-rounded rp-text-[9px] rp-text-gray-400 rp-flex rp-items-center rp-px-2">
					{ __( 'Name', 'recruiting-playbook' ) }
				</div>
				<div className="rp-h-5 rp-bg-gray-100 rp-rounded rp-text-[9px] rp-text-gray-400 rp-flex rp-items-center rp-px-2">
					{ __( 'Email', 'recruiting-playbook' ) }
				</div>
			</div>

			<button
				type="button"
				className="rp-w-full rp-py-1 rp-text-[10px] rp-font-medium"
				style={ buttonStyle }
			>
				{ __( 'Submit', 'recruiting-playbook' ) }
			</button>
		</div>
	);
}

/**
 * ButtonsPreview - Button Vorschau
 */
function ButtonsPreview( { settings, computedPrimaryColor } ) {
	const primaryStyle = useMemo( () => {
		// Custom Design: Custom-Farben mit Fallback auf Primärfarbe
		const bgColor = settings.button_use_custom_design
			? ( settings.button_bg_color || computedPrimaryColor )
			: computedPrimaryColor;

		const textColor = settings.button_use_custom_design
			? ( settings.button_text_color || '#ffffff' )
			: '#ffffff';

		const shadowValues = {
			none: 'none',
			light: '0 1px 2px rgba(0,0,0,0.1)',
			medium: '0 2px 4px rgba(0,0,0,0.15)',
			strong: '0 4px 8px rgba(0,0,0,0.2)',
		};

		const paddingValues = {
			small: '0.25rem 0.5rem',
			medium: '0.375rem 0.75rem',
			large: '0.5rem 1rem',
		};

		return {
			backgroundColor: bgColor,
			color: textColor,
			borderRadius: `${ settings.button_border_radius ?? 6 }px`,
			boxShadow: shadowValues[ settings.button_shadow || 'none' ],
			padding: paddingValues[ settings.button_size || 'medium' ],
			border: settings.button_border_show
				? `${ settings.button_border_width || 1 }px solid ${ settings.button_border_color || bgColor }`
				: 'none',
		};
	}, [ settings, computedPrimaryColor ] );

	const outlineStyle = useMemo( () => {
		// Custom Design: Custom-Farben mit Fallback auf Primärfarbe
		const color = settings.button_use_custom_design
			? ( settings.button_bg_color || computedPrimaryColor )
			: computedPrimaryColor;

		return {
			backgroundColor: 'transparent',
			color: color,
			borderRadius: `${ settings.button_border_radius ?? 6 }px`,
			border: `1px solid ${ color }`,
			padding: primaryStyle.padding,
		};
	}, [ settings, computedPrimaryColor, primaryStyle.padding ] );

	return (
		<div className="rp-space-y-2">
			{ /* Header "Jetzt bewerben" Button - fixed size */ }
			<button
				type="button"
				className="rp-w-full rp-py-1.5 rp-text-xs rp-font-medium"
				style={ {
					...primaryStyle,
					padding: '0.375rem 0.75rem', // Fixed size
				} }
			>
				{ __( 'Apply now', 'recruiting-playbook' ) }
			</button>

			<div className="rp-flex rp-gap-2">
				{ /* Primary Button */ }
				<button
					type="button"
					className="rp-text-[10px] rp-font-medium rp-flex-1"
					style={ primaryStyle }
				>
					{ __( 'Apply', 'recruiting-playbook' ) }
				</button>

				{ /* Outline Button */ }
				<button
					type="button"
					className="rp-text-[10px] rp-font-medium rp-flex-1"
					style={ outlineStyle }
				>
					{ __( 'Save', 'recruiting-playbook' ) }
				</button>
			</div>
		</div>
	);
}

/**
 * AiButtonPreview - KI-Button Vorschau
 */
function AiButtonPreview( { settings, computedPrimaryColor } ) {
	const presetStyles = {
		gradient: {
			background: 'linear-gradient(135deg, #8b5cf6, #ec4899)',
			color: '#ffffff',
		},
		outline: {
			background: 'transparent',
			border: '2px solid #8b5cf6',
			color: '#8b5cf6',
		},
		minimal: {
			background: '#f3f4f6',
			color: '#374151',
		},
		glow: {
			background: '#8b5cf6',
			color: '#ffffff',
			boxShadow: '0 0 15px rgba(139, 92, 246, 0.4)',
		},
		soft: {
			background: 'rgba(139, 92, 246, 0.1)',
			color: '#8b5cf6',
		},
	};

	const buttonStyle = useMemo( () => {
		const styleMode = settings.ai_button_style || 'preset';
		const preset = settings.ai_button_preset || 'gradient';

		let style = {};

		if ( styleMode === 'theme' ) {
			style = {
				background: computedPrimaryColor,
				color: '#ffffff',
			};
		} else if ( styleMode === 'preset' ) {
			style = presetStyles[ preset ] || presetStyles.gradient;
		} else {
			// Manual mode
			if ( settings.ai_button_use_gradient ) {
				style = {
					background: `linear-gradient(135deg, ${ settings.ai_button_color_1 || '#8b5cf6' }, ${ settings.ai_button_color_2 || '#ec4899' })`,
					color: settings.ai_button_text_color || '#ffffff',
				};
			} else {
				style = {
					background: settings.ai_button_color_1 || '#8b5cf6',
					color: settings.ai_button_text_color || '#ffffff',
				};
			}
		}

		return {
			...style,
			borderRadius: `${ settings.ai_button_radius || 8 }px`,
		};
	}, [ settings, computedPrimaryColor ] );

	return (
		<button
			type="button"
			className="rp-w-full rp-py-1.5 rp-text-xs rp-font-medium rp-flex rp-items-center rp-justify-center rp-gap-1.5"
			style={ buttonStyle }
		>
			<Sparkles className="rp-w-3 rp-h-3" />
			{ settings.ai_match_button_text || __( 'Start AI matching', 'recruiting-playbook' ) }
		</button>
	);
}

/**
 * TypographyPreview - Typografie Vorschau
 */
function TypographyPreview( { settings, computedPrimaryColor } ) {
	const linkColor = settings.link_use_primary !== false
		? computedPrimaryColor
		: ( settings.link_color || computedPrimaryColor );

	const linkDecoration = settings.link_decoration || 'underline';

	return (
		<div className="rp-space-y-1">
			<h2
				style={ {
					fontSize: `${ ( settings.font_size_h2 || 1.875 ) * 0.5 }rem`,
					lineHeight: settings.line_height_heading || 1.2,
					marginBottom: `${ ( settings.heading_margin_bottom || 0.5 ) * 0.5 }em`,
					fontWeight: 600,
					color: '#111827',
				} }
			>
				{ __( 'Your Responsibilities', 'recruiting-playbook' ) }
			</h2>
			<p
				style={ {
					fontSize: `${ ( settings.font_size_body || 1 ) * 0.6 }rem`,
					lineHeight: settings.line_height_body || 1.6,
					marginBottom: `${ ( settings.paragraph_spacing || 1 ) * 0.3 }em`,
					color: '#374151',
				} }
			>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit.
			</p>
			<a
				href="#"
				onClick={ ( e ) => e.preventDefault() }
				style={ {
					fontSize: `${ ( settings.font_size_body || 1 ) * 0.6 }rem`,
					color: linkColor,
					textDecoration: linkDecoration === 'underline' ? 'underline' : 'none',
				} }
				className={ linkDecoration === 'hover' ? 'hover:rp-underline' : '' }
			>
				{ __( 'Learn more', 'recruiting-playbook' ) }
			</a>
		</div>
	);
}

/**
 * ColorSwatchPreview - Primärfarbe Anzeige
 */
function ColorSwatchPreview( { color } ) {
	return (
		<div className="rp-flex rp-items-center rp-gap-2">
			<div
				className="rp-w-8 rp-h-8 rp-rounded-md rp-border rp-border-gray-200"
				style={ { backgroundColor: color } }
			/>
			<code className="rp-text-xs rp-font-mono rp-text-gray-600">{ color }</code>
		</div>
	);
}

/**
 * LivePreview Component
 *
 * @param {Object} props                     Component props
 * @param {Object} props.settings            Current design settings
 * @param {string} props.computedPrimaryColor Computed primary color
 * @return {JSX.Element} Component
 */
export function LivePreview( { settings, computedPrimaryColor } ) {
	if ( ! settings ) {
		return null;
	}

	return (
		<div className="rp-sticky rp-top-4">
			<Card>
				<CardHeader className="rp-pb-2">
					<CardTitle className="rp-text-sm">
						{ __( 'Live Preview', 'recruiting-playbook' ) }
					</CardTitle>
					<CardDescription className="rp-text-xs">
						{ __( 'Changes are displayed immediately', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					<PreviewSection title={ __( 'JOB CARD', 'recruiting-playbook' ) }>
						<JobCardPreview
							settings={ settings }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</PreviewSection>

					<PreviewSection title={ __( 'FORM BOX', 'recruiting-playbook' ) }>
						<FormBoxPreview
							settings={ settings }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</PreviewSection>

					<PreviewSection title={ __( 'BUTTONS', 'recruiting-playbook' ) }>
						<ButtonsPreview
							settings={ settings }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</PreviewSection>

					<PreviewSection title={ __( 'AI BUTTON', 'recruiting-playbook' ) }>
						<AiButtonPreview
							settings={ settings }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</PreviewSection>

					<PreviewSection title={ __( 'TYPOGRAPHY', 'recruiting-playbook' ) }>
						<TypographyPreview
							settings={ settings }
							computedPrimaryColor={ computedPrimaryColor }
						/>
					</PreviewSection>

					<PreviewSection title={ __( 'PRIMARY COLOR', 'recruiting-playbook' ) }>
						<ColorSwatchPreview color={ computedPrimaryColor } />
					</PreviewSection>
				</CardContent>
			</Card>
		</div>
	);
}

export default LivePreview;

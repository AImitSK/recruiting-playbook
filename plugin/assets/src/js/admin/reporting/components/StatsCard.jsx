/**
 * StatsCard Component
 *
 * Zeigt eine einzelne Statistik-Karte mit Wert, Trend und optionaler Icon
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { TrendingUp, TrendingDown, Minus, Info } from 'lucide-react';
import {
	Card,
	CardContent,
	CardHeader,
} from '../../components/ui/card';

/**
 * StatsCard Component
 *
 * @param {Object} props Component props
 * @param {string} props.title Titel der Karte
 * @param {string|number} props.value Hauptwert
 * @param {string} props.description Beschreibungstext
 * @param {string} props.tooltip Tooltip-Text für Info-Icon
 * @param {number} props.change Änderung in Prozent (optional)
 * @param {string} props.changeLabel Label für Änderung (optional)
 * @param {React.ReactNode} props.icon Icon (optional)
 * @param {string} props.suffix Suffix für den Wert (z.B. "Tage", "%")
 * @param {boolean} props.loading Ladezustand
 */
export function StatsCard( {
	title,
	value,
	description,
	tooltip,
	change,
	changeLabel,
	icon,
	suffix = '',
	loading = false,
} ) {
	const [ showTooltip, setShowTooltip ] = useState( false );

	const renderTrendIcon = () => {
		if ( change === undefined || change === null ) {
			return null;
		}

		if ( change > 0 ) {
			return (
				<TrendingUp
					style={ {
						width: '1rem',
						height: '1rem',
						color: '#22c55e',
					} }
				/>
			);
		}

		if ( change < 0 ) {
			return (
				<TrendingDown
					style={ {
						width: '1rem',
						height: '1rem',
						color: '#ef4444',
					} }
				/>
			);
		}

		return (
			<Minus
				style={ {
					width: '1rem',
					height: '1rem',
					color: '#6b7280',
				} }
			/>
		);
	};

	const getTrendColor = () => {
		if ( change > 0 ) {
			return '#22c55e';
		}
		if ( change < 0 ) {
			return '#ef4444';
		}
		return '#6b7280';
	};

	if ( loading ) {
		return (
			<Card>
				<CardHeader style={ { padding: '1.5rem', paddingBottom: '0.5rem' } }>
					<div
						style={ {
							width: '60%',
							height: '1rem',
							backgroundColor: '#e5e7eb',
							borderRadius: '0.25rem',
							animation: 'pulse 2s infinite',
						} }
					/>
				</CardHeader>
				<CardContent style={ { padding: '1.5rem', paddingTop: '0' } }>
					<div
						style={ {
							width: '40%',
							height: '2rem',
							backgroundColor: '#e5e7eb',
							borderRadius: '0.25rem',
							animation: 'pulse 2s infinite',
						} }
					/>
				</CardContent>
			</Card>
		);
	}

	return (
		<Card>
			<CardHeader
				style={ {
					padding: '1.5rem',
					paddingBottom: '0.5rem',
					display: 'flex',
					flexDirection: 'row',
					justifyContent: 'space-between',
					alignItems: 'center',
				} }
			>
				<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
					<span
						style={ {
							fontSize: '0.875rem',
							fontWeight: 500,
							color: '#6b7280',
						} }
					>
						{ title }
					</span>
					{ tooltip && (
						<div
							style={ { position: 'relative', display: 'inline-flex' } }
							onMouseEnter={ () => setShowTooltip( true ) }
							onMouseLeave={ () => setShowTooltip( false ) }
						>
							<Info
								style={ {
									width: '0.875rem',
									height: '0.875rem',
									color: '#9ca3af',
									cursor: 'help',
								} }
							/>
							{ showTooltip && (
								<div
									role="tooltip"
									style={ {
										position: 'absolute',
										zIndex: 50,
										bottom: '100%',
										left: '50%',
										transform: 'translateX(-50%)',
										marginBottom: '0.5rem',
										padding: '0.5rem 0.75rem',
										backgroundColor: '#18181b',
										color: '#fafafa',
										fontSize: '0.75rem',
										lineHeight: '1.4',
										borderRadius: '0.375rem',
										boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
										maxWidth: '200px',
										whiteSpace: 'normal',
										textAlign: 'center',
									} }
								>
									{ tooltip }
								</div>
							) }
						</div>
					) }
				</div>
				{ icon && (
					<span style={ { color: '#9ca3af' } }>
						{ icon }
					</span>
				) }
			</CardHeader>
			<CardContent style={ { padding: '1.5rem', paddingTop: '0' } }>
				<div
					style={ {
						display: 'flex',
						alignItems: 'baseline',
						gap: '0.5rem',
					} }
				>
					<span
						style={ {
							fontSize: '2rem',
							fontWeight: 700,
							color: '#1d71b8',
							lineHeight: 1.2,
						} }
					>
						{ value }
					</span>
					{ suffix && (
						<span
							style={ {
								fontSize: '0.875rem',
								color: '#6b7280',
							} }
						>
							{ suffix }
						</span>
					) }
				</div>

				{ /* Trend-Anzeige */ }
				{ change !== undefined && change !== null && (
					<div
						style={ {
							display: 'flex',
							alignItems: 'center',
							gap: '0.25rem',
							marginTop: '0.5rem',
						} }
					>
						{ renderTrendIcon() }
						<span
							style={ {
								fontSize: '0.75rem',
								fontWeight: 500,
								color: getTrendColor(),
							} }
						>
							{ change > 0 ? '+' : '' }
							{ change.toFixed( 1 ) }%
						</span>
						{ changeLabel && (
							<span
								style={ {
									fontSize: '0.75rem',
									color: '#6b7280',
									marginLeft: '0.25rem',
								} }
							>
								{ changeLabel }
							</span>
						) }
					</div>
				) }

				{ /* Beschreibung */ }
				{ description && ! change && (
					<p
						style={ {
							fontSize: '0.75rem',
							color: '#6b7280',
							marginTop: '0.25rem',
						} }
					>
						{ description }
					</p>
				) }
			</CardContent>
		</Card>
	);
}

export default StatsCard;

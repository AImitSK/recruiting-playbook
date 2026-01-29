/**
 * TrendChart Component
 *
 * Zeigt Zeitreihen-Daten als Linien- oder Flächendiagramm
 *
 * @package RecruitingPlaybook
 */

import {
	LineChart,
	Line,
	AreaChart,
	Area,
	XAxis,
	YAxis,
	CartesianGrid,
	Tooltip,
	ResponsiveContainer,
	Legend,
} from 'recharts';
import { format, parseISO } from 'date-fns';
import { de } from 'date-fns/locale';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '../../components/ui/card';

/**
 * TrendChart Component
 *
 * @param {Object} props Component props
 * @param {string} props.title Diagramm-Titel
 * @param {string} props.description Beschreibung
 * @param {Array} props.data Datenpunkte [{date, value, ...}]
 * @param {Array} props.series Datenreihen [{key, name, color}]
 * @param {string} props.type Chart-Typ: 'line' oder 'area'
 * @param {number} props.height Höhe des Charts
 * @param {boolean} props.loading Ladezustand
 * @param {string} props.dateFormat Datumsformat (default: 'dd.MM.')
 */
export function TrendChart( {
	title,
	description,
	data = [],
	series = [ { key: 'value', name: 'Wert', color: '#1d71b8' } ],
	type = 'area',
	height = 300,
	loading = false,
	dateFormat = 'dd.MM.',
} ) {
	const formatDate = ( dateStr ) => {
		try {
			const date = parseISO( dateStr );
			return format( date, dateFormat, { locale: de } );
		} catch {
			return dateStr;
		}
	};

	const formatTooltipDate = ( dateStr ) => {
		try {
			const date = parseISO( dateStr );
			return format( date, 'dd. MMMM yyyy', { locale: de } );
		} catch {
			return dateStr;
		}
	};

	const CustomTooltip = ( { active, payload, label } ) => {
		if ( active && payload && payload.length ) {
			return (
				<div
					style={ {
						backgroundColor: '#ffffff',
						padding: '0.75rem',
						border: '1px solid #e5e7eb',
						borderRadius: '0.375rem',
						boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
					} }
				>
					<p
						style={ {
							fontWeight: 600,
							marginBottom: '0.5rem',
							color: '#1f2937',
						} }
					>
						{ formatTooltipDate( label ) }
					</p>
					{ payload.map( ( entry, index ) => (
						<p
							key={ index }
							style={ {
								color: entry.color,
								fontSize: '0.875rem',
							} }
						>
							{ entry.name }: { entry.value }
						</p>
					) ) }
				</div>
			);
		}
		return null;
	};

	if ( loading ) {
		return (
			<Card>
				<CardHeader>
					<div
						style={ {
							width: '50%',
							height: '1.5rem',
							backgroundColor: '#e5e7eb',
							borderRadius: '0.25rem',
							animation: 'pulse 2s infinite',
						} }
					/>
				</CardHeader>
				<CardContent>
					<div
						style={ {
							width: '100%',
							height: `${ height }px`,
							backgroundColor: '#f3f4f6',
							borderRadius: '0.5rem',
							animation: 'pulse 2s infinite',
						} }
					/>
				</CardContent>
			</Card>
		);
	}

	const ChartComponent = type === 'line' ? LineChart : AreaChart;
	const DataComponent = type === 'line' ? Line : Area;

	return (
		<Card>
			<CardHeader>
				<CardTitle>{ title }</CardTitle>
				{ description && <CardDescription>{ description }</CardDescription> }
			</CardHeader>
			<CardContent>
				{ data.length === 0 ? (
					<div
						style={ {
							height: `${ height }px`,
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							color: '#6b7280',
						} }
					>
						Keine Daten verfügbar
					</div>
				) : (
					<ResponsiveContainer width="100%" height={ height }>
						<ChartComponent data={ data }>
							<CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
							<XAxis
								dataKey="date"
								tickFormatter={ formatDate }
								tick={ { fontSize: 12, fill: '#6b7280' } }
								axisLine={ { stroke: '#e5e7eb' } }
								tickLine={ { stroke: '#e5e7eb' } }
							/>
							<YAxis
								tick={ { fontSize: 12, fill: '#6b7280' } }
								axisLine={ { stroke: '#e5e7eb' } }
								tickLine={ { stroke: '#e5e7eb' } }
							/>
							<Tooltip content={ <CustomTooltip /> } />
							{ series.length > 1 && (
								<Legend
									wrapperStyle={ { paddingTop: '1rem' } }
								/>
							) }
							{ series.map( ( s ) => (
								<DataComponent
									key={ s.key }
									type="monotone"
									dataKey={ s.key }
									name={ s.name }
									stroke={ s.color }
									fill={ type === 'area' ? s.color : undefined }
									fillOpacity={ type === 'area' ? 0.2 : undefined }
									strokeWidth={ 2 }
									dot={ false }
									activeDot={ { r: 4, strokeWidth: 2 } }
								/>
							) ) }
						</ChartComponent>
					</ResponsiveContainer>
				) }
			</CardContent>
		</Card>
	);
}

export default TrendChart;

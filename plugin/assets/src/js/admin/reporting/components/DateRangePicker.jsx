/**
 * DateRangePicker Component
 *
 * Dropdown zur Auswahl von Zeiträumen für Statistiken
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { Calendar, ChevronDown } from 'lucide-react';
import { Button } from '../../components/ui/button';

/**
 * Vordefinierte Zeiträume
 */
const PERIODS = [
	{ key: 'today', label: 'Heute' },
	{ key: '7days', label: 'Letzte 7 Tage' },
	{ key: '30days', label: 'Letzte 30 Tage' },
	{ key: '90days', label: 'Letzte 90 Tage' },
	{ key: 'year', label: 'Dieses Jahr' },
	{ key: 'all', label: 'Gesamt' },
];

/**
 * DateRangePicker Component
 *
 * @param {Object} props Component props
 * @param {string} props.value Aktueller Zeitraum
 * @param {Function} props.onChange Callback bei Änderung
 * @param {Array} props.periods Verfügbare Zeiträume (optional)
 */
export function DateRangePicker( {
	value = '30days',
	onChange,
	periods = PERIODS,
} ) {
	const [ isOpen, setIsOpen ] = useState( false );

	const currentPeriod = periods.find( ( p ) => p.key === value );
	const i18n = window.rpReportingData?.i18n || {};

	// Übersetzte Labels verwenden falls verfügbar
	const getLabel = ( period ) => {
		const translationKey = {
			today: 'today',
			'7days': 'last7days',
			'30days': 'last30days',
			'90days': 'last90days',
			year: 'thisYear',
			all: 'allTime',
		}[ period.key ];

		return i18n[ translationKey ] || period.label;
	};

	const handleSelect = ( periodKey ) => {
		if ( onChange ) {
			onChange( periodKey );
		}
		setIsOpen( false );
	};

	return (
		<div style={ { position: 'relative', display: 'inline-block' } }>
			<Button
				variant="outline"
				onClick={ () => setIsOpen( ! isOpen ) }
				style={ {
					display: 'flex',
					alignItems: 'center',
					gap: '0.5rem',
					padding: '0.5rem 1rem',
					backgroundColor: '#ffffff',
					border: '1px solid #e5e7eb',
					borderRadius: '0.375rem',
					cursor: 'pointer',
					fontSize: '0.875rem',
				} }
			>
				<Calendar style={ { width: '1rem', height: '1rem', color: '#6b7280' } } />
				<span>{ getLabel( currentPeriod || periods[ 0 ] ) }</span>
				<ChevronDown
					style={ {
						width: '1rem',
						height: '1rem',
						color: '#6b7280',
						transform: isOpen ? 'rotate(180deg)' : 'rotate(0deg)',
						transition: 'transform 0.2s ease',
					} }
				/>
			</Button>

			{ isOpen && (
				<>
					{ /* Backdrop zum Schließen */ }
					<div
						style={ {
							position: 'fixed',
							inset: 0,
							zIndex: 40,
						} }
						onClick={ () => setIsOpen( false ) }
					/>

					{ /* Dropdown */ }
					<div
						style={ {
							position: 'absolute',
							top: '100%',
							left: 0,
							marginTop: '0.25rem',
							backgroundColor: '#ffffff',
							border: '1px solid #e5e7eb',
							borderRadius: '0.375rem',
							boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
							zIndex: 50,
							minWidth: '180px',
							overflow: 'hidden',
						} }
					>
						{ periods.map( ( period ) => (
							<button
								key={ period.key }
								onClick={ () => handleSelect( period.key ) }
								style={ {
									display: 'block',
									width: '100%',
									padding: '0.625rem 1rem',
									textAlign: 'left',
									fontSize: '0.875rem',
									backgroundColor: period.key === value ? '#f3f4f6' : '#ffffff',
									color: period.key === value ? '#1d71b8' : '#374151',
									fontWeight: period.key === value ? 500 : 400,
									border: 'none',
									cursor: 'pointer',
									transition: 'background-color 0.15s ease',
								} }
								onMouseEnter={ ( e ) => {
									if ( period.key !== value ) {
										e.currentTarget.style.backgroundColor = '#f9fafb';
									}
								} }
								onMouseLeave={ ( e ) => {
									if ( period.key !== value ) {
										e.currentTarget.style.backgroundColor = '#ffffff';
									}
								} }
							>
								{ getLabel( period ) }
							</button>
						) ) }
					</div>
				</>
			) }
		</div>
	);
}

export default DateRangePicker;

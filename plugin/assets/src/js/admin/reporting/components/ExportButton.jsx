/**
 * ExportButton Component
 *
 * Button mit Dropdown für CSV-Export-Optionen
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Download, ChevronDown, Lock } from 'lucide-react';
import { Button } from '../../components/ui/button';

/**
 * ExportButton Component
 *
 * @param {Object} props Component props
 * @param {Function} props.onExportApplications Callback für Bewerbungs-Export
 * @param {Function} props.onExportStats Callback für Statistik-Export
 * @param {string} props.period Aktueller Zeitraum für Export
 * @param {boolean} props.loading Ladezustand
 * @param {boolean} props.disabled Deaktiviert (z.B. Pro-Feature)
 * @param {string} props.upgradeUrl URL zur Upgrade-Seite
 */
export function ExportButton( {
	onExportApplications,
	onExportStats,
	period = '30days',
	loading = false,
	disabled = false,
	upgradeUrl,
} ) {
	const [ isOpen, setIsOpen ] = useState( false );
	const i18n = window.rpReportingData?.i18n || {};

	const handleExportApplications = () => {
		if ( onExportApplications ) {
			onExportApplications();
		}
		setIsOpen( false );
	};

	const handleExportStats = () => {
		if ( onExportStats ) {
			onExportStats( period );
		}
		setIsOpen( false );
	};

	if ( disabled ) {
		return (
			<a
				href={ upgradeUrl }
				style={ {
					display: 'inline-flex',
					alignItems: 'center',
					gap: '0.5rem',
					padding: '0.5rem 1rem',
					backgroundColor: '#f3f4f6',
					color: '#6b7280',
					borderRadius: '0.375rem',
					fontSize: '0.875rem',
					textDecoration: 'none',
					cursor: 'pointer',
				} }
			>
				<Lock style={ { width: '1rem', height: '1rem' } } />
				<span>{ i18n.csvExport || __( 'CSV Export', 'recruiting-playbook' ) }</span>
				<span
					style={ {
						fontSize: '0.625rem',
						backgroundColor: '#fef3c7',
						color: '#92400e',
						padding: '0.125rem 0.375rem',
						borderRadius: '0.25rem',
						fontWeight: 600,
					} }
				>
					PRO
				</span>
			</a>
		);
	}

	return (
		<div style={ { position: 'relative', display: 'inline-block' } }>
			<Button
				variant="outline"
				onClick={ () => setIsOpen( ! isOpen ) }
				disabled={ loading }
				style={ {
					display: 'flex',
					alignItems: 'center',
					gap: '0.5rem',
					padding: '0.5rem 1rem',
					backgroundColor: loading ? '#f3f4f6' : '#ffffff',
					border: '1px solid #e5e7eb',
					borderRadius: '0.375rem',
					cursor: loading ? 'not-allowed' : 'pointer',
					fontSize: '0.875rem',
				} }
			>
				<Download
					style={ {
						width: '1rem',
						height: '1rem',
						color: loading ? '#9ca3af' : '#1d71b8',
					} }
				/>
				<span>{ loading ? ( i18n.loading || __( 'Loading...', 'recruiting-playbook' ) ) : ( i18n.downloadCsv || __( 'Download CSV', 'recruiting-playbook' ) ) }</span>
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
					{ /* Backdrop */ }
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
							right: 0,
							marginTop: '0.25rem',
							backgroundColor: '#ffffff',
							border: '1px solid #e5e7eb',
							borderRadius: '0.375rem',
							boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
							zIndex: 50,
							minWidth: '220px',
							overflow: 'hidden',
						} }
					>
						<button
							onClick={ handleExportApplications }
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '0.75rem',
								width: '100%',
								padding: '0.75rem 1rem',
								textAlign: 'left',
								fontSize: '0.875rem',
								backgroundColor: '#ffffff',
								color: '#374151',
								border: 'none',
								cursor: 'pointer',
							} }
							onMouseEnter={ ( e ) => {
								e.currentTarget.style.backgroundColor = '#f9fafb';
							} }
							onMouseLeave={ ( e ) => {
								e.currentTarget.style.backgroundColor = '#ffffff';
							} }
						>
							<Download style={ { width: '1rem', height: '1rem', color: '#6b7280' } } />
							<div>
								<div style={ { fontWeight: 500 } }>
									{ i18n.exportApplications || __( 'Export applications', 'recruiting-playbook' ) }
								</div>
								<div style={ { fontSize: '0.75rem', color: '#6b7280' } }>
									{ __( 'CSV with applicant data', 'recruiting-playbook' ) }
								</div>
							</div>
						</button>

						<div style={ { borderTop: '1px solid #e5e7eb' } } />

						<button
							onClick={ handleExportStats }
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '0.75rem',
								width: '100%',
								padding: '0.75rem 1rem',
								textAlign: 'left',
								fontSize: '0.875rem',
								backgroundColor: '#ffffff',
								color: '#374151',
								border: 'none',
								cursor: 'pointer',
							} }
							onMouseEnter={ ( e ) => {
								e.currentTarget.style.backgroundColor = '#f9fafb';
							} }
							onMouseLeave={ ( e ) => {
								e.currentTarget.style.backgroundColor = '#ffffff';
							} }
						>
							<Download style={ { width: '1rem', height: '1rem', color: '#6b7280' } } />
							<div>
								<div style={ { fontWeight: 500 } }>
									{ i18n.exportStats || __( 'Export statistics report', 'recruiting-playbook' ) }
								</div>
								<div style={ { fontSize: '0.75rem', color: '#6b7280' } }>
									{ __( 'Summary as CSV', 'recruiting-playbook' ) }
								</div>
							</div>
						</button>
					</div>
				</>
			) }
		</div>
	);
}

export default ExportButton;

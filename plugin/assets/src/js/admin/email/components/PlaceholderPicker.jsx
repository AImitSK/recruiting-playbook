/**
 * PlaceholderPicker - Kompakte Sidebar für E-Mail-Platzhalter
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Copy, Check } from 'lucide-react';

/**
 * PlaceholderPicker Komponente (nur Sidebar)
 *
 * @param {Object} props              Props
 * @param {Object} props.placeholders Platzhalter-Gruppen
 * @param {Object} props.style        Zusätzliche Styles
 * @return {JSX.Element} Komponente
 */
export function PlaceholderPicker( { placeholders = {}, style = {} } ) {
	const [ copiedKey, setCopiedKey ] = useState( null );

	const i18n = window.rpEmailData?.i18n || {};

	/**
	 * Platzhalter in Zwischenablage kopieren
	 *
	 * @param {string} key Platzhalter-Key
	 */
	const handleCopy = useCallback( ( key ) => {
		const text = `{${ key }}`;
		navigator.clipboard.writeText( text ).then( () => {
			setCopiedKey( key );
			setTimeout( () => setCopiedKey( null ), 1500 );
		} );
	}, [] );

	return (
		<div
			style={ {
				backgroundColor: '#fafafa',
				border: '1px solid #e5e7eb',
				borderRadius: '8px',
				padding: '16px',
				...style,
			} }
		>
			<h3
				style={ {
					fontSize: '14px',
					fontWeight: 600,
					color: '#18181b',
					marginTop: 0,
					marginBottom: '16px',
				} }
			>
				{ i18n.placeholders || 'Platzhalter' }
			</h3>

			<div style={ { display: 'flex', flexDirection: 'column', gap: '16px' } }>
				{ Object.entries( placeholders ).map( ( [ groupKey, group ] ) => (
					<div key={ groupKey }>
						<h4
							style={ {
								fontSize: '11px',
								fontWeight: 600,
								textTransform: 'uppercase',
								letterSpacing: '0.05em',
								color: '#71717a',
								marginTop: 0,
								marginBottom: '8px',
							} }
						>
							{ group.label || groupKey }
						</h4>
						<ul style={ { listStyle: 'none', margin: 0, padding: 0 } }>
							{ Object.entries( group.placeholders || {} ).map( ( [ key ] ) => (
								<li
									key={ key }
									style={ {
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'space-between',
										height: '28px',
										padding: '0 4px',
										borderRadius: '4px',
										transition: 'background-color 150ms',
									} }
									onMouseEnter={ ( e ) => { e.currentTarget.style.backgroundColor = '#f0f0f0'; } }
									onMouseLeave={ ( e ) => { e.currentTarget.style.backgroundColor = 'transparent'; } }
								>
									<code
										style={ {
											fontSize: '12px',
											fontFamily: 'ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace',
											color: '#374151',
										} }
									>
										{ `{${ key }}` }
									</code>
									<button
										type="button"
										onClick={ () => handleCopy( key ) }
										title={ i18n.copyToClipboard || 'In Zwischenablage kopieren' }
										style={ {
											display: 'flex',
											alignItems: 'center',
											justifyContent: 'center',
											width: '24px',
											height: '24px',
											border: 'none',
											background: 'transparent',
											borderRadius: '4px',
											cursor: 'pointer',
											color: copiedKey === key ? '#22c55e' : '#9ca3af',
											transition: 'color 150ms, background-color 150ms',
										} }
										onMouseEnter={ ( e ) => {
											if ( copiedKey !== key ) {
												e.currentTarget.style.backgroundColor = '#e5e7eb';
												e.currentTarget.style.color = '#6b7280';
											}
										} }
										onMouseLeave={ ( e ) => {
											e.currentTarget.style.backgroundColor = 'transparent';
											if ( copiedKey !== key ) {
												e.currentTarget.style.color = '#9ca3af';
											}
										} }
									>
										{ copiedKey === key ? (
											<Check style={ { width: '14px', height: '14px' } } />
										) : (
											<Copy style={ { width: '14px', height: '14px' } } />
										) }
									</button>
								</li>
							) ) }
						</ul>
					</div>
				) ) }

				{ Object.keys( placeholders ).length === 0 && (
					<p
						style={ {
							color: '#9ca3af',
							fontStyle: 'italic',
							textAlign: 'center',
							margin: 0,
							fontSize: '13px',
						} }
					>
						{ i18n.noPlaceholders || 'Keine Platzhalter verfügbar.' }
					</p>
				) }
			</div>
		</div>
	);
}

PlaceholderPicker.propTypes = {
	placeholders: PropTypes.objectOf(
		PropTypes.shape( {
			label: PropTypes.string,
			items: PropTypes.objectOf(
				PropTypes.shape( {
					label: PropTypes.string,
					description: PropTypes.string,
				} )
			),
		} )
	),
	style: PropTypes.object,
};

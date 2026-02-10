/**
 * RichTextEditor Component (WYSIWYG)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef, useRef, useEffect, useCallback, useState } from '@wordpress/element';
import { Bold, Italic, Link, List, ListOrdered, Undo, Redo, RemoveFormatting, Code, Table, Plus, Minus, Trash2, Type } from 'lucide-react';
import { cn } from '../../lib/utils';
import { Button } from './button';

/**
 * Font size options
 */
const FONT_SIZES = [
	{ label: 'Klein', value: '12px' },
	{ label: 'Normal', value: '16px' },
	{ label: 'Groß', value: '20px' },
	{ label: 'Sehr groß', value: '24px' },
];

/**
 * Toolbar Button Component
 */
function ToolbarButton( { active, onClick, children, title, disabled } ) {
	return (
		<button
			type="button"
			onClick={ onClick }
			disabled={ disabled }
			title={ title }
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				justifyContent: 'center',
				width: '2rem',
				height: '2rem',
				padding: 0,
				border: 'none',
				borderRadius: '0.25rem',
				backgroundColor: active ? '#e5e7eb' : 'transparent',
				color: active ? '#1f2937' : '#6b7280',
				cursor: disabled ? 'not-allowed' : 'pointer',
				opacity: disabled ? 0.5 : 1,
				transition: 'all 150ms',
			} }
			onMouseEnter={ ( e ) => {
				if ( ! disabled && ! active ) {
					e.currentTarget.style.backgroundColor = '#f3f4f6';
				}
			} }
			onMouseLeave={ ( e ) => {
				if ( ! active ) {
					e.currentTarget.style.backgroundColor = 'transparent';
				}
			} }
		>
			{ children }
		</button>
	);
}

/**
 * RichTextEditor Component
 */
const RichTextEditor = forwardRef( ( {
	className,
	value = '',
	onChange,
	placeholder = '',
	disabled = false,
	minHeight = '200px',
	...props
}, ref ) => {
	const editorRef = useRef( null );
	const internalRef = ref || editorRef;
	const [ showSource, setShowSource ] = useState( false );
	const [ sourceCode, setSourceCode ] = useState( '' );
	const [ isInTable, setIsInTable ] = useState( false );
	const [ showTableMenu, setShowTableMenu ] = useState( false );
	const [ showFontSizeMenu, setShowFontSizeMenu ] = useState( false );

	// Track previous showSource state for transitions
	const prevShowSourceRef = useRef( showSource );

	// Initialize content when value changes from outside
	useEffect( () => {
		if ( ! showSource && internalRef.current && internalRef.current.innerHTML !== value ) {
			internalRef.current.innerHTML = value;
		}
	}, [ value, internalRef, showSource ] );

	// Handle transition from source view back to WYSIWYG
	useEffect( () => {
		// If we just switched from source to WYSIWYG
		if ( prevShowSourceRef.current === true && showSource === false ) {
			// Apply the source code to the editor
			if ( internalRef.current ) {
				internalRef.current.innerHTML = sourceCode;
			}
			// Also notify parent
			if ( onChange ) {
				onChange( sourceCode );
			}
		}
		prevShowSourceRef.current = showSource;
	}, [ showSource, sourceCode, onChange, internalRef ] );

	// Sync source code when switching to source view
	useEffect( () => {
		if ( showSource ) {
			setSourceCode( internalRef.current?.innerHTML || value );
		}
	}, [ showSource ] ); // Only run when showSource changes to true

	/**
	 * Execute command
	 */
	const execCommand = useCallback( ( command, value = null ) => {
		document.execCommand( command, false, value );
		internalRef.current?.focus();

		// Trigger onChange
		if ( onChange && internalRef.current ) {
			onChange( internalRef.current.innerHTML );
		}
	}, [ onChange, internalRef ] );

	/**
	 * Handle input
	 */
	const handleInput = useCallback( () => {
		if ( onChange && internalRef.current ) {
			onChange( internalRef.current.innerHTML );
		}
	}, [ onChange, internalRef ] );

	/**
	 * Handle paste - strip formatting but keep basic structure
	 */
	const handlePaste = useCallback( ( e ) => {
		e.preventDefault();
		const text = e.clipboardData.getData( 'text/plain' );
		document.execCommand( 'insertText', false, text );
	}, [] );

	/**
	 * Handle link insertion
	 */
	const handleLink = useCallback( () => {
		const selection = window.getSelection();
		const selectedText = selection?.toString() || '';
		const url = prompt( 'URL eingeben:', 'https://' );

		if ( url ) {
			if ( selectedText ) {
				execCommand( 'createLink', url );
			} else {
				const linkText = prompt( 'Link-Text eingeben:', '' );
				if ( linkText ) {
					document.execCommand( 'insertHTML', false, `<a href="${ url }" target="_blank" rel="noopener noreferrer">${ linkText }</a>` );
				}
			}
		}
	}, [ execCommand ] );

	/**
	 * Handle key commands
	 */
	const handleKeyDown = useCallback( ( e ) => {
		if ( e.ctrlKey || e.metaKey ) {
			switch ( e.key.toLowerCase() ) {
				case 'b':
					e.preventDefault();
					execCommand( 'bold' );
					break;
				case 'i':
					e.preventDefault();
					execCommand( 'italic' );
					break;
				case 'z':
					e.preventDefault();
					if ( e.shiftKey ) {
						execCommand( 'redo' );
					} else {
						execCommand( 'undo' );
					}
					break;
				case 'y':
					e.preventDefault();
					execCommand( 'redo' );
					break;
			}
		}
	}, [ execCommand ] );

	/**
	 * Handle clear formatting
	 */
	const handleClearFormatting = useCallback( () => {
		execCommand( 'removeFormat' );
	}, [ execCommand ] );

	/**
	 * Handle font size change
	 */
	const handleFontSize = useCallback( ( size ) => {
		// Use execCommand with fontSize (1-7) won't work well, use span with style instead
		const selection = window.getSelection();
		if ( selection.rangeCount > 0 && ! selection.isCollapsed ) {
			const range = selection.getRangeAt( 0 );
			const span = document.createElement( 'span' );
			span.style.fontSize = size;
			range.surroundContents( span );
			if ( onChange && internalRef.current ) {
				onChange( internalRef.current.innerHTML );
			}
		}
		setShowFontSizeMenu( false );
		internalRef.current?.focus();
	}, [ onChange, internalRef ] );

	/**
	 * Toggle source view
	 */
	const toggleSourceView = useCallback( () => {
		setShowSource( ( prev ) => ! prev );
	}, [] );

	/**
	 * Handle source code change
	 */
	const handleSourceChange = useCallback( ( e ) => {
		setSourceCode( e.target.value );
	}, [] );

	/**
	 * Get current table cell from selection
	 */
	const getCurrentCell = useCallback( () => {
		const selection = window.getSelection();
		if ( ! selection.rangeCount ) {
			return null;
		}
		let node = selection.anchorNode;
		while ( node && node !== internalRef.current ) {
			if ( node.nodeName === 'TD' || node.nodeName === 'TH' ) {
				return node;
			}
			node = node.parentNode;
		}
		return null;
	}, [ internalRef ] );

	/**
	 * Get current table from selection
	 */
	const getCurrentTable = useCallback( () => {
		const cell = getCurrentCell();
		if ( ! cell ) {
			return null;
		}
		let node = cell;
		while ( node && node !== internalRef.current ) {
			if ( node.nodeName === 'TABLE' ) {
				return node;
			}
			node = node.parentNode;
		}
		return null;
	}, [ getCurrentCell, internalRef ] );

	/**
	 * Check if cursor is in table on selection change
	 */
	const handleSelectionChange = useCallback( () => {
		const inTable = getCurrentCell() !== null;
		setIsInTable( inTable );
	}, [ getCurrentCell ] );

	// Listen for selection changes
	useEffect( () => {
		document.addEventListener( 'selectionchange', handleSelectionChange );
		return () => {
			document.removeEventListener( 'selectionchange', handleSelectionChange );
		};
	}, [ handleSelectionChange ] );

	// Close menus on click outside
	useEffect( () => {
		const handleClickOutside = ( e ) => {
			if ( ! e.target.closest( '.rp-rich-text-editor__toolbar' ) ) {
				setShowTableMenu( false );
				setShowFontSizeMenu( false );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ showTableMenu, showFontSizeMenu ] );

	/**
	 * Insert a new table
	 */
	const handleInsertTable = useCallback( () => {
		const rows = 2;
		const cols = 2;
		let html = '<table style="border-collapse: collapse; margin: 16px 0;">';
		for ( let r = 0; r < rows; r++ ) {
			html += '<tr>';
			for ( let c = 0; c < cols; c++ ) {
				html += '<td style="padding: 4px 12px 4px 0;">&nbsp;</td>';
			}
			html += '</tr>';
		}
		html += '</table><p>&nbsp;</p>';
		document.execCommand( 'insertHTML', false, html );
		if ( onChange && internalRef.current ) {
			onChange( internalRef.current.innerHTML );
		}
		setShowTableMenu( false );
	}, [ onChange, internalRef ] );

	/**
	 * Add a row to current table
	 */
	const handleAddRow = useCallback( () => {
		const cell = getCurrentCell();
		if ( ! cell ) {
			return;
		}
		const row = cell.parentNode;
		const table = getCurrentTable();
		if ( ! row || ! table ) {
			return;
		}
		const newRow = row.cloneNode( true );
		// Clear content in new row
		Array.from( newRow.cells ).forEach( ( c ) => {
			c.innerHTML = '&nbsp;';
		} );
		row.parentNode.insertBefore( newRow, row.nextSibling );
		if ( onChange && internalRef.current ) {
			onChange( internalRef.current.innerHTML );
		}
		setShowTableMenu( false );
	}, [ getCurrentCell, getCurrentTable, onChange, internalRef ] );

	/**
	 * Add a column to current table
	 */
	const handleAddColumn = useCallback( () => {
		const cell = getCurrentCell();
		if ( ! cell ) {
			return;
		}
		const table = getCurrentTable();
		if ( ! table ) {
			return;
		}
		const cellIndex = cell.cellIndex;
		const rows = table.querySelectorAll( 'tr' );
		rows.forEach( ( row ) => {
			const refCell = row.cells[ cellIndex ];
			if ( refCell ) {
				const newCell = refCell.cloneNode( true );
				newCell.innerHTML = '&nbsp;';
				refCell.parentNode.insertBefore( newCell, refCell.nextSibling );
			}
		} );
		if ( onChange && internalRef.current ) {
			onChange( internalRef.current.innerHTML );
		}
		setShowTableMenu( false );
	}, [ getCurrentCell, getCurrentTable, onChange, internalRef ] );

	/**
	 * Delete current row
	 */
	const handleDeleteRow = useCallback( () => {
		const cell = getCurrentCell();
		if ( ! cell ) {
			return;
		}
		const row = cell.parentNode;
		const table = getCurrentTable();
		if ( ! row || ! table ) {
			return;
		}
		// Don't delete if only one row
		if ( table.querySelectorAll( 'tr' ).length <= 1 ) {
			return;
		}
		row.remove();
		if ( onChange && internalRef.current ) {
			onChange( internalRef.current.innerHTML );
		}
		setShowTableMenu( false );
	}, [ getCurrentCell, getCurrentTable, onChange, internalRef ] );

	/**
	 * Delete current column
	 */
	const handleDeleteColumn = useCallback( () => {
		const cell = getCurrentCell();
		if ( ! cell ) {
			return;
		}
		const table = getCurrentTable();
		if ( ! table ) {
			return;
		}
		const cellIndex = cell.cellIndex;
		const rows = table.querySelectorAll( 'tr' );
		// Don't delete if only one column
		if ( rows[ 0 ] && rows[ 0 ].cells.length <= 1 ) {
			return;
		}
		rows.forEach( ( row ) => {
			if ( row.cells[ cellIndex ] ) {
				row.cells[ cellIndex ].remove();
			}
		} );
		if ( onChange && internalRef.current ) {
			onChange( internalRef.current.innerHTML );
		}
		setShowTableMenu( false );
	}, [ getCurrentCell, getCurrentTable, onChange, internalRef ] );

	/**
	 * Delete entire table
	 */
	const handleDeleteTable = useCallback( () => {
		const table = getCurrentTable();
		if ( ! table ) {
			return;
		}
		table.remove();
		if ( onChange && internalRef.current ) {
			onChange( internalRef.current.innerHTML );
		}
		setShowTableMenu( false );
		setIsInTable( false );
	}, [ getCurrentTable, onChange, internalRef ] );

	return (
		<div
			className={ cn( 'rp-rich-text-editor', className ) }
			style={ {
				border: '1px solid #e5e7eb',
				borderRadius: '0.5rem',
				overflow: 'hidden',
				backgroundColor: '#fff',
			} }
		>
			{ /* Toolbar */ }
			<div
				className="rp-rich-text-editor__toolbar"
				style={ {
					display: 'flex',
					alignItems: 'center',
					gap: '0.25rem',
					padding: '0.5rem',
					borderBottom: '1px solid #e5e7eb',
					backgroundColor: '#fafafa',
				} }
			>
				<ToolbarButton
					onClick={ () => execCommand( 'bold' ) }
					title="Fett (Strg+B)"
					disabled={ disabled || showSource }
				>
					<Bold style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<ToolbarButton
					onClick={ () => execCommand( 'italic' ) }
					title="Kursiv (Strg+I)"
					disabled={ disabled || showSource }
				>
					<Italic style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				{ /* Font size dropdown */ }
				<div style={ { position: 'relative' } }>
					<ToolbarButton
						onClick={ () => setShowFontSizeMenu( ! showFontSizeMenu ) }
						title="Schriftgröße"
						active={ showFontSizeMenu }
						disabled={ disabled || showSource }
					>
						<Type style={ { width: '1rem', height: '1rem' } } />
					</ToolbarButton>
					{ showFontSizeMenu && (
						<div
							style={ {
								position: 'absolute',
								top: '100%',
								left: 0,
								marginTop: '4px',
								backgroundColor: '#fff',
								border: '1px solid #e5e7eb',
								borderRadius: '0.375rem',
								boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
								zIndex: 50,
								minWidth: '120px',
								padding: '4px 0',
							} }
						>
							{ FONT_SIZES.map( ( { label, value } ) => (
								<button
									key={ value }
									type="button"
									onClick={ () => handleFontSize( value ) }
									style={ {
										display: 'block',
										width: '100%',
										padding: '8px 12px',
										border: 'none',
										background: 'none',
										textAlign: 'left',
										cursor: 'pointer',
										fontSize: value,
									} }
									onMouseEnter={ ( e ) => e.currentTarget.style.backgroundColor = '#f3f4f6' }
									onMouseLeave={ ( e ) => e.currentTarget.style.backgroundColor = 'transparent' }
								>
									{ label }
								</button>
							) ) }
						</div>
					) }
				</div>
				<div style={ { width: '1px', height: '1.5rem', backgroundColor: '#e5e7eb', margin: '0 0.25rem' } } />
				<ToolbarButton
					onClick={ () => execCommand( 'insertUnorderedList' ) }
					title="Aufzählung"
					disabled={ disabled || showSource }
				>
					<List style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<ToolbarButton
					onClick={ () => execCommand( 'insertOrderedList' ) }
					title="Nummerierte Liste"
					disabled={ disabled || showSource }
				>
					<ListOrdered style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<div style={ { width: '1px', height: '1.5rem', backgroundColor: '#e5e7eb', margin: '0 0.25rem' } } />
				<ToolbarButton
					onClick={ handleLink }
					title="Link einfügen"
					disabled={ disabled || showSource }
				>
					<Link style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<div style={ { width: '1px', height: '1.5rem', backgroundColor: '#e5e7eb', margin: '0 0.25rem' } } />
				{ /* Table dropdown */ }
				<div style={ { position: 'relative' } }>
					<ToolbarButton
						onClick={ () => setShowTableMenu( ! showTableMenu ) }
						title="Tabelle"
						active={ showTableMenu || isInTable }
						disabled={ disabled || showSource }
					>
						<Table style={ { width: '1rem', height: '1rem' } } />
					</ToolbarButton>
					{ showTableMenu && (
						<div
							style={ {
								position: 'absolute',
								top: '100%',
								left: 0,
								marginTop: '4px',
								backgroundColor: '#fff',
								border: '1px solid #e5e7eb',
								borderRadius: '0.375rem',
								boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
								zIndex: 50,
								minWidth: '160px',
								padding: '4px 0',
							} }
						>
							<button
								type="button"
								onClick={ handleInsertTable }
								style={ {
									display: 'flex',
									alignItems: 'center',
									gap: '8px',
									width: '100%',
									padding: '8px 12px',
									border: 'none',
									background: 'none',
									textAlign: 'left',
									cursor: 'pointer',
									fontSize: '14px',
								} }
								onMouseEnter={ ( e ) => e.currentTarget.style.backgroundColor = '#f3f4f6' }
								onMouseLeave={ ( e ) => e.currentTarget.style.backgroundColor = 'transparent' }
							>
								<Plus style={ { width: '14px', height: '14px' } } />
								Tabelle einfügen
							</button>
							{ isInTable && (
								<>
									<div style={ { height: '1px', backgroundColor: '#e5e7eb', margin: '4px 0' } } />
									<button
										type="button"
										onClick={ handleAddRow }
										style={ {
											display: 'flex',
											alignItems: 'center',
											gap: '8px',
											width: '100%',
											padding: '8px 12px',
											border: 'none',
											background: 'none',
											textAlign: 'left',
											cursor: 'pointer',
											fontSize: '14px',
										} }
										onMouseEnter={ ( e ) => e.currentTarget.style.backgroundColor = '#f3f4f6' }
										onMouseLeave={ ( e ) => e.currentTarget.style.backgroundColor = 'transparent' }
									>
										<Plus style={ { width: '14px', height: '14px' } } />
										Zeile hinzufügen
									</button>
									<button
										type="button"
										onClick={ handleAddColumn }
										style={ {
											display: 'flex',
											alignItems: 'center',
											gap: '8px',
											width: '100%',
											padding: '8px 12px',
											border: 'none',
											background: 'none',
											textAlign: 'left',
											cursor: 'pointer',
											fontSize: '14px',
										} }
										onMouseEnter={ ( e ) => e.currentTarget.style.backgroundColor = '#f3f4f6' }
										onMouseLeave={ ( e ) => e.currentTarget.style.backgroundColor = 'transparent' }
									>
										<Plus style={ { width: '14px', height: '14px' } } />
										Spalte hinzufügen
									</button>
									<div style={ { height: '1px', backgroundColor: '#e5e7eb', margin: '4px 0' } } />
									<button
										type="button"
										onClick={ handleDeleteRow }
										style={ {
											display: 'flex',
											alignItems: 'center',
											gap: '8px',
											width: '100%',
											padding: '8px 12px',
											border: 'none',
											background: 'none',
											textAlign: 'left',
											cursor: 'pointer',
											fontSize: '14px',
											color: '#dc2626',
										} }
										onMouseEnter={ ( e ) => e.currentTarget.style.backgroundColor = '#fef2f2' }
										onMouseLeave={ ( e ) => e.currentTarget.style.backgroundColor = 'transparent' }
									>
										<Minus style={ { width: '14px', height: '14px' } } />
										Zeile löschen
									</button>
									<button
										type="button"
										onClick={ handleDeleteColumn }
										style={ {
											display: 'flex',
											alignItems: 'center',
											gap: '8px',
											width: '100%',
											padding: '8px 12px',
											border: 'none',
											background: 'none',
											textAlign: 'left',
											cursor: 'pointer',
											fontSize: '14px',
											color: '#dc2626',
										} }
										onMouseEnter={ ( e ) => e.currentTarget.style.backgroundColor = '#fef2f2' }
										onMouseLeave={ ( e ) => e.currentTarget.style.backgroundColor = 'transparent' }
									>
										<Minus style={ { width: '14px', height: '14px' } } />
										Spalte löschen
									</button>
									<button
										type="button"
										onClick={ handleDeleteTable }
										style={ {
											display: 'flex',
											alignItems: 'center',
											gap: '8px',
											width: '100%',
											padding: '8px 12px',
											border: 'none',
											background: 'none',
											textAlign: 'left',
											cursor: 'pointer',
											fontSize: '14px',
											color: '#dc2626',
										} }
										onMouseEnter={ ( e ) => e.currentTarget.style.backgroundColor = '#fef2f2' }
										onMouseLeave={ ( e ) => e.currentTarget.style.backgroundColor = 'transparent' }
									>
										<Trash2 style={ { width: '14px', height: '14px' } } />
										Tabelle löschen
									</button>
								</>
							) }
						</div>
					) }
				</div>
				<div style={ { width: '1px', height: '1.5rem', backgroundColor: '#e5e7eb', margin: '0 0.25rem' } } />
				<ToolbarButton
					onClick={ handleClearFormatting }
					title="Formatierung entfernen"
					disabled={ disabled || showSource }
				>
					<RemoveFormatting style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<div style={ { flex: 1 } } />
				<ToolbarButton
					onClick={ toggleSourceView }
					title={ showSource ? 'Zur Vorschau' : 'HTML anzeigen' }
					active={ showSource }
					disabled={ disabled }
				>
					<Code style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<div style={ { width: '1px', height: '1.5rem', backgroundColor: '#e5e7eb', margin: '0 0.25rem' } } />
				<ToolbarButton
					onClick={ () => execCommand( 'undo' ) }
					title="Rückgängig (Strg+Z)"
					disabled={ disabled }
				>
					<Undo style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<ToolbarButton
					onClick={ () => execCommand( 'redo' ) }
					title="Wiederholen (Strg+Y)"
					disabled={ disabled }
				>
					<Redo style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
			</div>

			{ /* Editor Content - WYSIWYG View */ }
			{ ! showSource && (
				<div
					ref={ internalRef }
					contentEditable={ ! disabled }
					onInput={ handleInput }
					onPaste={ handlePaste }
					onKeyDown={ handleKeyDown }
					data-placeholder={ placeholder }
					className="rp-rich-text-editor__content"
					style={ {
						minHeight: minHeight || '300px',
						padding: '1.5rem',
						outline: 'none',
						fontSize: '16px',
						lineHeight: 1.9,
						color: '#1f2937',
						overflowY: 'auto',
					} }
					{ ...props }
				/>
			) }

			{ /* Editor Content - Source View */ }
			{ showSource && (
				<textarea
					value={ sourceCode }
					onChange={ handleSourceChange }
					disabled={ disabled }
					placeholder="HTML eingeben..."
					className="rp-rich-text-editor__source"
					style={ {
						width: '100%',
						minHeight: minHeight || '300px',
						padding: '1rem',
						border: 'none',
						outline: 'none',
						fontFamily: 'monospace',
						fontSize: '13px',
						lineHeight: 1.6,
						color: '#1f2937',
						backgroundColor: '#f8fafc',
						resize: 'vertical',
						boxSizing: 'border-box',
					} }
				/>
			) }

			<style>{ `
				.rp-rich-text-editor__content:empty:before {
					content: attr(data-placeholder);
					color: #9ca3af;
					pointer-events: none;
				}
				.rp-rich-text-editor__content p {
					margin: 0 0 0.75rem 0;
				}
				.rp-rich-text-editor__content ul,
				.rp-rich-text-editor__content ol {
					margin: 0 0 0.75rem 0 !important;
					padding-left: 1.75rem !important;
					list-style-position: outside !important;
				}
				.rp-rich-text-editor__content ul {
					list-style-type: disc !important;
				}
				.rp-rich-text-editor__content ol {
					list-style-type: decimal !important;
				}
				.rp-rich-text-editor__content li {
					margin-bottom: 0.25rem;
					display: list-item !important;
				}
				.rp-rich-text-editor__content a {
					color: #1d71b8;
					text-decoration: underline;
				}
				/* CSS Normalization - Keep consistent line-height and font-family */
				.rp-rich-text-editor__content * {
					line-height: inherit !important;
					font-family: inherit !important;
				}
				/* Allow explicit font-size in spans (user-set), normalize others */
				.rp-rich-text-editor__content td,
				.rp-rich-text-editor__content th,
				.rp-rich-text-editor__content p {
					font-size: inherit;
				}
				.rp-rich-text-editor__content table {
					border-collapse: collapse;
					margin: 0.75rem 0;
					border: 1px solid #e5e7eb;
				}
				.rp-rich-text-editor__content td,
				.rp-rich-text-editor__content th {
					padding: 0.25rem 0.75rem 0.25rem 0.25rem !important;
					vertical-align: top;
					text-align: left;
					border: 1px solid #e5e7eb;
					min-width: 40px;
				}
				.rp-rich-text-editor__content strong,
				.rp-rich-text-editor__content b {
					font-weight: 600;
				}
			` }</style>
		</div>
	);
} );

RichTextEditor.displayName = 'RichTextEditor';

export { RichTextEditor };

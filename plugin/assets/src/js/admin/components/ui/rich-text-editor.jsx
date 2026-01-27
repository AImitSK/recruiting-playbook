/**
 * RichTextEditor Component (WYSIWYG)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef, useRef, useEffect, useCallback } from '@wordpress/element';
import { Bold, Italic, Link, List, ListOrdered, Undo, Redo } from 'lucide-react';
import { cn } from '../../lib/utils';
import { Button } from './button';

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

	// Initialize content when value changes from outside
	useEffect( () => {
		if ( internalRef.current && internalRef.current.innerHTML !== value ) {
			internalRef.current.innerHTML = value;
		}
	}, [ value, internalRef ] );

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
					disabled={ disabled }
				>
					<Bold style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<ToolbarButton
					onClick={ () => execCommand( 'italic' ) }
					title="Kursiv (Strg+I)"
					disabled={ disabled }
				>
					<Italic style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<div style={ { width: '1px', height: '1.5rem', backgroundColor: '#e5e7eb', margin: '0 0.25rem' } } />
				<ToolbarButton
					onClick={ () => execCommand( 'insertUnorderedList' ) }
					title="Aufzählung"
					disabled={ disabled }
				>
					<List style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<ToolbarButton
					onClick={ () => execCommand( 'insertOrderedList' ) }
					title="Nummerierte Liste"
					disabled={ disabled }
				>
					<ListOrdered style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<div style={ { width: '1px', height: '1.5rem', backgroundColor: '#e5e7eb', margin: '0 0.25rem' } } />
				<ToolbarButton
					onClick={ handleLink }
					title="Link einfügen"
					disabled={ disabled }
				>
					<Link style={ { width: '1rem', height: '1rem' } } />
				</ToolbarButton>
				<div style={ { flex: 1 } } />
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

			{ /* Editor Content */ }
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
					margin: 0 0 0.75rem 0;
					padding-left: 1.75rem;
				}
				.rp-rich-text-editor__content li {
					margin-bottom: 0.25rem;
				}
				.rp-rich-text-editor__content a {
					color: #1d71b8;
					text-decoration: underline;
				}
			` }</style>
		</div>
	);
} );

RichTextEditor.displayName = 'RichTextEditor';

export { RichTextEditor };

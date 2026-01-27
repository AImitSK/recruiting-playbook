/**
 * AlertDialog Component (shadcn/ui style)
 *
 * @package RecruitingPlaybook
 */

import { forwardRef, createContext, useContext } from '@wordpress/element';
import { createPortal } from '@wordpress/element';
import { cn } from '../../lib/utils';
import { Button } from './button';

const AlertDialogContext = createContext( {} );

function AlertDialog( { open, onOpenChange, children } ) {
	if ( ! open ) {
		return null;
	}

	return (
		<AlertDialogContext.Provider value={ { onOpenChange } }>
			{ createPortal(
				<div className="rp-alert-dialog">
					{ children }
				</div>,
				document.body
			) }
		</AlertDialogContext.Provider>
	);
}

const AlertDialogContent = forwardRef( ( { className, children, ...props }, ref ) => {
	const { onOpenChange } = useContext( AlertDialogContext );

	return (
		<>
			{ /* Overlay */ }
			<div
				style={ {
					position: 'fixed',
					inset: 0,
					backgroundColor: 'rgba(0, 0, 0, 0.5)',
					zIndex: 99999,
				} }
				onClick={ () => onOpenChange && onOpenChange( false ) }
			/>
			{ /* Content */ }
			<div
				ref={ ref }
				className={ cn( 'rp-alert-dialog__content', className ) }
				style={ {
					position: 'fixed',
					left: '50%',
					top: '50%',
					transform: 'translate(-50%, -50%)',
					zIndex: 100000,
					width: '100%',
					maxWidth: '32rem',
					backgroundColor: '#ffffff',
					borderRadius: '0.5rem',
					boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
					padding: '1.5rem',
				} }
				{ ...props }
			>
				{ children }
			</div>
		</>
	);
} );
AlertDialogContent.displayName = 'AlertDialogContent';

const AlertDialogHeader = ( { className, ...props } ) => (
	<div
		className={ cn( 'rp-alert-dialog__header', className ) }
		style={ {
			display: 'flex',
			flexDirection: 'column',
			gap: '0.5rem',
			textAlign: 'center',
			marginBottom: '1rem',
		} }
		{ ...props }
	/>
);
AlertDialogHeader.displayName = 'AlertDialogHeader';

const AlertDialogFooter = ( { className, ...props } ) => (
	<div
		className={ cn( 'rp-alert-dialog__footer', className ) }
		style={ {
			display: 'flex',
			flexDirection: 'row',
			justifyContent: 'flex-end',
			gap: '0.5rem',
			marginTop: '1.5rem',
		} }
		{ ...props }
	/>
);
AlertDialogFooter.displayName = 'AlertDialogFooter';

const AlertDialogTitle = forwardRef( ( { className, ...props }, ref ) => (
	<h2
		ref={ ref }
		className={ cn( 'rp-alert-dialog__title', className ) }
		style={ {
			fontSize: '1.125rem',
			fontWeight: 600,
			color: '#111827',
			margin: 0,
		} }
		{ ...props }
	/>
) );
AlertDialogTitle.displayName = 'AlertDialogTitle';

const AlertDialogDescription = forwardRef( ( { className, ...props }, ref ) => (
	<p
		ref={ ref }
		className={ cn( 'rp-alert-dialog__description', className ) }
		style={ {
			fontSize: '0.875rem',
			color: '#6b7280',
			margin: 0,
		} }
		{ ...props }
	/>
) );
AlertDialogDescription.displayName = 'AlertDialogDescription';

const AlertDialogAction = forwardRef( ( { className, variant = 'default', ...props }, ref ) => {
	const { onOpenChange } = useContext( AlertDialogContext );

	const handleClick = ( e ) => {
		if ( props.onClick ) {
			props.onClick( e );
		}
		if ( onOpenChange ) {
			onOpenChange( false );
		}
	};

	return (
		<Button
			ref={ ref }
			className={ cn( 'rp-alert-dialog__action', className ) }
			variant={ variant }
			{ ...props }
			onClick={ handleClick }
		/>
	);
} );
AlertDialogAction.displayName = 'AlertDialogAction';

const AlertDialogCancel = forwardRef( ( { className, ...props }, ref ) => {
	const { onOpenChange } = useContext( AlertDialogContext );

	return (
		<Button
			ref={ ref }
			variant="outline"
			className={ cn( 'rp-alert-dialog__cancel', className ) }
			onClick={ () => onOpenChange && onOpenChange( false ) }
			{ ...props }
		/>
	);
} );
AlertDialogCancel.displayName = 'AlertDialogCancel';

export {
	AlertDialog,
	AlertDialogContent,
	AlertDialogHeader,
	AlertDialogFooter,
	AlertDialogTitle,
	AlertDialogDescription,
	AlertDialogAction,
	AlertDialogCancel,
};

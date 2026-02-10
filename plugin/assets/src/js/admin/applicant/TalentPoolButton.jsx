/**
 * Talent Pool Button Component
 *
 * Toggle-Button zum Hinzufügen/Entfernen aus dem Talent-Pool - shadcn/ui Style
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Users, Plus, X, Info } from 'lucide-react';
import { Button } from '../components/ui/button';
import { useTalentPool } from './hooks/useTalentPool';

function Spinner( { size = '1rem' } ) {
	return (
		<div
			style={ {
				width: size,
				height: size,
				border: '2px solid #e5e7eb',
				borderTopColor: '#1d71b8',
				borderRadius: '50%',
				animation: 'spin 0.8s linear infinite',
			} }
		/>
	);
}

function Modal( { title, children, onClose } ) {
	return (
		<div
			style={ {
				position: 'fixed',
				top: 0,
				left: 0,
				right: 0,
				bottom: 0,
				backgroundColor: 'rgba(0, 0, 0, 0.5)',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				zIndex: 100000,
				padding: '1rem',
			} }
			onClick={ onClose }
		>
			<div
				style={ {
					backgroundColor: '#fff',
					borderRadius: '0.5rem',
					boxShadow: '0 4px 20px rgba(0, 0, 0, 0.15)',
					maxWidth: '500px',
					width: '100%',
					maxHeight: '90vh',
					overflow: 'auto',
				} }
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						padding: '1rem 1.5rem',
						borderBottom: '1px solid #e5e7eb',
					} }
				>
					<h3 style={ { margin: 0, fontSize: '1rem', fontWeight: 600, color: '#1f2937' } }>
						{ title }
					</h3>
					<button
						type="button"
						onClick={ onClose }
						style={ {
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							width: '2rem',
							height: '2rem',
							background: 'none',
							border: 'none',
							borderRadius: '0.375rem',
							cursor: 'pointer',
							color: '#6b7280',
						} }
					>
						<X style={ { width: '1.25rem', height: '1.25rem' } } />
					</button>
				</div>
				{ children }
			</div>
		</div>
	);
}

export function TalentPoolButton( { candidateId, inPool = false, onStatusChange } ) {
	const [ showModal, setShowModal ] = useState( false );
	const [ reason, setReason ] = useState( '' );
	const [ tags, setTags ] = useState( '' );
	const [ showConfirmRemove, setShowConfirmRemove ] = useState( false );

	const {
		isInPool,
		loading,
		error,
		addToPool,
		removeFromPool,
	} = useTalentPool( candidateId, inPool );

	const handleAdd = async () => {
		const success = await addToPool( reason, tags );
		if ( success ) {
			setShowModal( false );
			setReason( '' );
			setTags( '' );
			if ( onStatusChange ) onStatusChange( true );
		}
	};

	const handleRemove = async () => {
		const success = await removeFromPool();
		if ( success ) {
			setShowConfirmRemove( false );
			if ( onStatusChange ) onStatusChange( false );
		}
	};

	// Im Pool
	if ( isInPool ) {
		return (
			<>
				<Button
					variant="outline"
					onClick={ () => setShowConfirmRemove( true ) }
					disabled={ loading }
					style={ {
						backgroundColor: '#e6f3ff',
						borderColor: '#1d71b8',
						color: '#1d71b8',
					} }
				>
					<Users style={ { width: '1rem', height: '1rem' } } />
					{ __( 'Im Talent-Pool', 'recruiting-playbook' ) }
					{ loading && <Spinner size="0.875rem" /> }
				</Button>

				{ showConfirmRemove && (
					<Modal
						title={ __( 'Aus Talent-Pool entfernen', 'recruiting-playbook' ) }
						onClose={ () => setShowConfirmRemove( false ) }
					>
						<div style={ { padding: '1.5rem' } }>
							<p style={ { margin: '0 0 1rem 0', color: '#374151' } }>
								{ __( 'Kandidat wirklich aus dem Talent-Pool entfernen?', 'recruiting-playbook' ) }
							</p>
							{ error && (
								<div style={ { padding: '0.75rem', backgroundColor: '#fee2e2', color: '#dc2626', borderRadius: '0.375rem', marginBottom: '1rem', fontSize: '0.875rem' } }>
									{ error }
								</div>
							) }
						</div>
						<div
							style={ {
								display: 'flex',
								justifyContent: 'flex-end',
								gap: '0.5rem',
								padding: '1rem 1.5rem',
								borderTop: '1px solid #e5e7eb',
								backgroundColor: '#f9fafb',
							} }
						>
							<Button variant="outline" onClick={ () => setShowConfirmRemove( false ) } disabled={ loading }>
								{ __( 'Abbrechen', 'recruiting-playbook' ) }
							</Button>
							<Button
								onClick={ handleRemove }
								disabled={ loading }
								style={ { backgroundColor: '#d63638' } }
							>
								{ loading ? <Spinner size="0.875rem" /> : __( 'Entfernen', 'recruiting-playbook' ) }
							</Button>
						</div>
					</Modal>
				) }

				<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
			</>
		);
	}

	// Nicht im Pool
	return (
		<>
			<Button
				variant="outline"
				onClick={ () => setShowModal( true ) }
				disabled={ loading }
			>
				<Plus style={ { width: '1rem', height: '1rem' } } />
				{ __( 'Talent-Pool', 'recruiting-playbook' ) }
			</Button>

			{ showModal && (
				<Modal
					title={ __( 'Zum Talent-Pool hinzufügen', 'recruiting-playbook' ) }
					onClose={ () => setShowModal( false ) }
				>
					<div style={ { padding: '1.5rem' } }>
						{ error && (
							<div style={ { padding: '0.75rem', backgroundColor: '#fee2e2', color: '#dc2626', borderRadius: '0.375rem', marginBottom: '1rem', fontSize: '0.875rem' } }>
								{ error }
							</div>
						) }

						<div style={ { marginBottom: '1rem' } }>
							<label
								htmlFor="rp-talent-reason"
								style={ { display: 'block', marginBottom: '0.375rem', fontSize: '0.875rem', fontWeight: 500, color: '#374151' } }
							>
								{ __( 'Grund für Aufnahme', 'recruiting-playbook' ) }
							</label>
							<textarea
								id="rp-talent-reason"
								value={ reason }
								onChange={ ( e ) => setReason( e.target.value ) }
								placeholder={ __( 'z.B. Sehr guter Kandidat, aber aktuell keine passende Stelle...', 'recruiting-playbook' ) }
								rows={ 3 }
								disabled={ loading }
								style={ {
									width: '100%',
									padding: '0.5rem 0.75rem',
									border: '1px solid #e5e7eb',
									borderRadius: '0.375rem',
									fontSize: '0.875rem',
									resize: 'vertical',
									fontFamily: 'inherit',
								} }
							/>
						</div>

						<div style={ { marginBottom: '1rem' } }>
							<label
								htmlFor="rp-talent-tags"
								style={ { display: 'block', marginBottom: '0.375rem', fontSize: '0.875rem', fontWeight: 500, color: '#374151' } }
							>
								{ __( 'Tags (komma-separiert)', 'recruiting-playbook' ) }
							</label>
							<input
								type="text"
								id="rp-talent-tags"
								value={ tags }
								onChange={ ( e ) => setTags( e.target.value ) }
								placeholder={ __( 'z.B. php, react, senior, remote', 'recruiting-playbook' ) }
								disabled={ loading }
								style={ {
									width: '100%',
									padding: '0.5rem 0.75rem',
									border: '1px solid #e5e7eb',
									borderRadius: '0.375rem',
									fontSize: '0.875rem',
								} }
							/>
							<p style={ { margin: '0.375rem 0 0 0', fontSize: '0.75rem', color: '#6b7280' } }>
								{ __( 'Tags helfen, Kandidaten später schneller zu finden.', 'recruiting-playbook' ) }
							</p>
						</div>

						<div
							style={ {
								display: 'flex',
								alignItems: 'flex-start',
								gap: '0.5rem',
								padding: '0.75rem',
								backgroundColor: '#fef3c7',
								borderRadius: '0.375rem',
								fontSize: '0.75rem',
								color: '#92400e',
							} }
						>
							<Info style={ { width: '1rem', height: '1rem', flexShrink: 0, marginTop: '0.125rem' } } />
							<span>{ __( 'Der Eintrag wird nach 24 Monaten automatisch gelöscht (DSGVO).', 'recruiting-playbook' ) }</span>
						</div>
					</div>

					<div
						style={ {
							display: 'flex',
							justifyContent: 'flex-end',
							gap: '0.5rem',
							padding: '1rem 1.5rem',
							borderTop: '1px solid #e5e7eb',
							backgroundColor: '#f9fafb',
						} }
					>
						<Button variant="outline" onClick={ () => setShowModal( false ) } disabled={ loading }>
							{ __( 'Abbrechen', 'recruiting-playbook' ) }
						</Button>
						<Button onClick={ handleAdd } disabled={ loading }>
							{ loading ? (
								<>
									<Spinner size="0.875rem" />
									{ __( 'Hinzufügen...', 'recruiting-playbook' ) }
								</>
							) : (
								__( 'Hinzufügen', 'recruiting-playbook' )
							) }
						</Button>
					</div>
				</Modal>
			) }

			<style>{ `@keyframes spin { to { transform: rotate(360deg); } }` }</style>
		</>
	);
}

export function TalentPoolBadge( { inPool } ) {
	if ( ! inPool ) return null;

	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				justifyContent: 'center',
				width: '1.5rem',
				height: '1.5rem',
				backgroundColor: '#e6f3ff',
				borderRadius: '50%',
				color: '#1d71b8',
			} }
			title={ __( 'Im Talent-Pool', 'recruiting-playbook' ) }
		>
			<Users style={ { width: '0.875rem', height: '0.875rem' } } />
		</span>
	);
}

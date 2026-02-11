/**
 * ApiKeySettings Component
 *
 * API key management in Settings tab "API".
 * Standalone component with its own hook.
 *
 * @package RecruitingPlaybook
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card';
import { Button } from '../../components/ui/button';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Badge } from '../../components/ui/badge';
import { Switch } from '../../components/ui/switch';
import { Alert, AlertDescription } from '../../components/ui/alert';
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '../../components/ui/table';
import {
	AlertDialog,
	AlertDialogAction,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
} from '../../components/ui/alert-dialog';
import { Spinner } from '../../components/ui/spinner';

import { useApiKeys } from '../hooks';

/**
 * ApiKeySettings Component
 *
 * @return {JSX.Element} Component
 */
export function ApiKeySettings() {
	const {
		keys,
		permissions,
		loading,
		saving,
		error,
		setError,
		createKey,
		updateKey,
		deleteKey,
	} = useApiKeys();

	// Dialog state.
	const [ showCreateDialog, setShowCreateDialog ] = useState( false );
	const [ showKeyDialog, setShowKeyDialog ] = useState( false );
	const [ showDeleteDialog, setShowDeleteDialog ] = useState( false );
	const [ createdPlainKey, setCreatedPlainKey ] = useState( '' );
	const [ deleteTargetId, setDeleteTargetId ] = useState( null );
	const [ copied, setCopied ] = useState( false );

	// Form state for creation.
	const [ newKeyName, setNewKeyName ] = useState( '' );
	const [ newKeyPermissions, setNewKeyPermissions ] = useState( [] );
	const [ newKeyRateLimit, setNewKeyRateLimit ] = useState( 1000 );

	/**
	 * Create key
	 */
	const handleCreate = useCallback( async () => {
		if ( ! newKeyName.trim() || newKeyPermissions.length === 0 ) {
			return;
		}

		const result = await createKey( {
			name: newKeyName.trim(),
			permissions: newKeyPermissions,
			rate_limit: newKeyRateLimit,
		} );

		if ( result?.plain_key ) {
			setCreatedPlainKey( result.plain_key );
			setShowCreateDialog( false );
			setShowKeyDialog( true );
			// Reset form.
			setNewKeyName( '' );
			setNewKeyPermissions( [] );
			setNewKeyRateLimit( 1000 );
		}
	}, [ newKeyName, newKeyPermissions, newKeyRateLimit, createKey ] );

	/**
	 * Toggle key active status
	 */
	const handleToggleActive = useCallback( async ( id, currentActive ) => {
		await updateKey( id, { is_active: ! currentActive } );
	}, [ updateKey ] );

	/**
	 * Delete key
	 */
	const handleDelete = useCallback( async () => {
		if ( ! deleteTargetId ) {
			return;
		}
		await deleteKey( deleteTargetId );
		setShowDeleteDialog( false );
		setDeleteTargetId( null );
	}, [ deleteTargetId, deleteKey ] );

	/**
	 * Copy to clipboard
	 */
	const handleCopyKey = useCallback( () => {
		navigator.clipboard.writeText( createdPlainKey ).then( () => {
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		} );
	}, [ createdPlainKey ] );

	/**
	 * Toggle permission checkbox
	 */
	const togglePermission = useCallback( ( permKey ) => {
		setNewKeyPermissions( ( prev ) =>
			prev.includes( permKey )
				? prev.filter( ( p ) => p !== permKey )
				: [ ...prev, permKey ]
		);
	}, [] );

	/**
	 * Format date
	 */
	const formatDate = ( dateString ) => {
		if ( ! dateString ) {
			return '-';
		}
		try {
			return new Date( dateString ).toLocaleDateString( 'en-US', {
				day: '2-digit',
				month: '2-digit',
				year: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
			} );
		} catch {
			return dateString;
		}
	};

	// Loading.
	if ( loading ) {
		return (
			<div style={ { display: 'flex', justifyContent: 'center', padding: '3rem' } }>
				<Spinner size="lg" />
			</div>
		);
	}

	return (
		<div>
			{ /* Error */ }
			{ error && (
				<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
					<AlertDescription>{ error }</AlertDescription>
				</Alert>
			) }

			<Card>
				<CardHeader>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }>
						<div>
							<CardTitle>{ __( 'API Keys', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>
								{ __( 'Create and manage API keys for external access to the REST API.', 'recruiting-playbook' ) }
							</CardDescription>
						</div>
						<Button
							onClick={ () => {
								setError( null );
								setShowCreateDialog( true );
							} }
						>
							{ __( 'Create New Key', 'recruiting-playbook' ) }
						</Button>
					</div>
				</CardHeader>
				<CardContent>
					{ keys.length === 0 ? (
						<div style={ {
							textAlign: 'center',
							padding: '2rem',
							color: '#6b7280',
						} }>
							<p>{ __( 'No API keys available.', 'recruiting-playbook' ) }</p>
							<p style={ { fontSize: '0.875rem' } }>
								{ __( 'Create a key to use the REST API.', 'recruiting-playbook' ) }
							</p>
						</div>
					) : (
						<Table style={ { tableLayout: 'fixed' } }>
							<TableHeader>
								<TableRow>
									<TableHead style={ { width: '15%' } }>{ __( 'Name', 'recruiting-playbook' ) }</TableHead>
									<TableHead style={ { width: '15%' } }>{ __( 'Key', 'recruiting-playbook' ) }</TableHead>
									<TableHead style={ { width: '22%' } }>{ __( 'Permissions', 'recruiting-playbook' ) }</TableHead>
									<TableHead style={ { width: '16%', textAlign: 'right' } }>{ __( 'Last Used', 'recruiting-playbook' ) }</TableHead>
									<TableHead style={ { width: '10%', textAlign: 'right' } }>{ __( 'Requests', 'recruiting-playbook' ) }</TableHead>
									<TableHead style={ { width: '8%', textAlign: 'center' } }>{ __( 'Active', 'recruiting-playbook' ) }</TableHead>
									<TableHead style={ { width: '14%' } }></TableHead>
								</TableRow>
							</TableHeader>
							<TableBody>
								{ keys.map( ( key ) => (
									<TableRow key={ key.id }>
										<TableCell style={ { fontWeight: 500 } }>
											{ key.name }
										</TableCell>
										<TableCell>
											<code style={ {
												fontSize: '0.8rem',
												backgroundColor: '#f3f4f6',
												padding: '2px 6px',
												borderRadius: '4px',
											} }>
												{ key.key_prefix }...{ key.key_hint }
											</code>
										</TableCell>
										<TableCell>
											<div style={ { display: 'flex', flexWrap: 'wrap', gap: '4px' } }>
												{ ( key.permissions || [] ).slice( 0, 3 ).map( ( perm ) => (
													<Badge key={ perm } variant="secondary" style={ { fontSize: '0.7rem' } }>
														{ perm }
													</Badge>
												) ) }
												{ ( key.permissions || [] ).length > 3 && (
													<Badge variant="outline" style={ { fontSize: '0.7rem' } }>
														+{ key.permissions.length - 3 }
													</Badge>
												) }
											</div>
										</TableCell>
										<TableCell style={ { fontSize: '0.85rem', color: '#6b7280', textAlign: 'right' } }>
											{ formatDate( key.last_used_at ) }
										</TableCell>
										<TableCell style={ { fontSize: '0.85rem', textAlign: 'right' } }>
											{ key.request_count.toLocaleString( 'en-US' ) }
										</TableCell>
										<TableCell style={ { textAlign: 'center' } }>
											<Switch
												checked={ key.is_active }
												onCheckedChange={ () => handleToggleActive( key.id, key.is_active ) }
												disabled={ saving }
											/>
										</TableCell>
										<TableCell style={ { textAlign: 'right' } }>
											<Button
												variant="ghost"
												size="sm"
												onClick={ () => {
													setDeleteTargetId( key.id );
													setShowDeleteDialog( true );
												} }
												style={ { color: '#ef4444' } }
											>
												{ __( 'Delete', 'recruiting-playbook' ) }
											</Button>
										</TableCell>
									</TableRow>
								) ) }
							</TableBody>
						</Table>
					) }
				</CardContent>
			</Card>

			{ /* Create dialog */ }
			<AlertDialog open={ showCreateDialog } onOpenChange={ setShowCreateDialog }>
				<AlertDialogContent style={ { maxWidth: '560px' } }>
					<AlertDialogHeader>
						<AlertDialogTitle>
							{ __( 'Create New API Key', 'recruiting-playbook' ) }
						</AlertDialogTitle>
						<AlertDialogDescription>
							{ __( 'Enter a name and select permissions.', 'recruiting-playbook' ) }
						</AlertDialogDescription>
					</AlertDialogHeader>

					<div style={ { display: 'flex', flexDirection: 'column', gap: '1rem', padding: '0.5rem 0' } }>
						{ /* Name */ }
						<div>
							<Label htmlFor="key-name">{ __( 'Name', 'recruiting-playbook' ) }</Label>
							<Input
								id="key-name"
								value={ newKeyName }
								onChange={ ( e ) => setNewKeyName( e.target.value ) }
								placeholder={ __( 'e.g. CRM Integration', 'recruiting-playbook' ) }
								style={ { marginTop: '0.25rem' } }
							/>
						</div>

						{ /* Rate Limit */ }
						<div>
							<Label htmlFor="key-rate-limit">{ __( 'Rate Limit (requests/hour)', 'recruiting-playbook' ) }</Label>
							<Input
								id="key-rate-limit"
								type="number"
								value={ newKeyRateLimit }
								onChange={ ( e ) => setNewKeyRateLimit( parseInt( e.target.value, 10 ) || 1000 ) }
								min={ 1 }
								max={ 100000 }
								style={ { marginTop: '0.25rem', width: '180px' } }
							/>
						</div>

						{ /* Permissions */ }
						<div>
							<Label>{ __( 'Permissions', 'recruiting-playbook' ) }</Label>
							<div style={ {
								display: 'grid',
								gridTemplateColumns: '1fr 1fr',
								gap: '0.5rem',
								marginTop: '0.5rem',
							} }>
								{ permissions.map( ( perm ) => (
									<label
										key={ perm.key }
										style={ {
											display: 'flex',
											alignItems: 'center',
											gap: '0.5rem',
											fontSize: '0.875rem',
											cursor: 'pointer',
										} }
									>
										<input
											type="checkbox"
											checked={ newKeyPermissions.includes( perm.key ) }
											onChange={ () => togglePermission( perm.key ) }
										/>
										{ perm.label }
									</label>
								) ) }
							</div>
						</div>
					</div>

					<AlertDialogFooter>
						<AlertDialogCancel>
							{ __( 'Cancel', 'recruiting-playbook' ) }
						</AlertDialogCancel>
						<AlertDialogAction
							onClick={ handleCreate }
							disabled={ saving || ! newKeyName.trim() || newKeyPermissions.length === 0 }
						>
							{ saving ? __( 'Creating...', 'recruiting-playbook' ) : __( 'Create Key', 'recruiting-playbook' ) }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>

			{ /* Key display dialog (after creation) */ }
			<AlertDialog open={ showKeyDialog } onOpenChange={ ( open ) => {
				if ( ! open ) {
					setShowKeyDialog( false );
					setCreatedPlainKey( '' );
					setCopied( false );
				}
			} }>
				<AlertDialogContent style={ { maxWidth: '520px' } }>
					<AlertDialogHeader>
						<AlertDialogTitle>
							{ __( 'API Key Created', 'recruiting-playbook' ) }
						</AlertDialogTitle>
						<AlertDialogDescription>
							{ /* Intentionally empty - warning below provides the description. */ }
						</AlertDialogDescription>
					</AlertDialogHeader>

					<Alert variant="destructive" style={ { marginBottom: '1rem' } }>
						<AlertDescription>
							{ __( 'Copy the key now! It will only be displayed once and cannot be recovered.', 'recruiting-playbook' ) }
						</AlertDescription>
					</Alert>

					<div style={ {
						display: 'flex',
						alignItems: 'center',
						gap: '0.5rem',
						padding: '0.75rem',
						backgroundColor: '#f3f4f6',
						borderRadius: '6px',
						fontFamily: 'monospace',
						fontSize: '0.85rem',
						wordBreak: 'break-all',
					} }>
						<span style={ { flex: 1 } }>{ createdPlainKey }</span>
						<Button
							variant="outline"
							size="sm"
							onClick={ handleCopyKey }
							style={ { whiteSpace: 'nowrap', flexShrink: 0 } }
						>
							{ copied
								? __( 'Copied!', 'recruiting-playbook' )
								: __( 'Copy', 'recruiting-playbook' )
							}
						</Button>
					</div>

					<AlertDialogFooter style={ { marginTop: '1rem' } }>
						<AlertDialogAction onClick={ () => {
							setShowKeyDialog( false );
							setCreatedPlainKey( '' );
							setCopied( false );
						} }>
							{ __( 'Done', 'recruiting-playbook' ) }
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>

			{ /* Delete confirmation */ }
			<AlertDialog open={ showDeleteDialog } onOpenChange={ setShowDeleteDialog }>
				<AlertDialogContent>
					<AlertDialogHeader>
						<AlertDialogTitle>
							{ __( 'Delete API Key?', 'recruiting-playbook' ) }
						</AlertDialogTitle>
						<AlertDialogDescription>
							{ __( 'The key will be permanently deleted. All integrations using this key will lose access.', 'recruiting-playbook' ) }
						</AlertDialogDescription>
					</AlertDialogHeader>
					<AlertDialogFooter>
						<AlertDialogCancel>
							{ __( 'Cancel', 'recruiting-playbook' ) }
						</AlertDialogCancel>
						<AlertDialogAction
							onClick={ handleDelete }
							disabled={ saving }
							style={ { backgroundColor: '#ef4444' } }
						>
							{ saving
								? __( 'Deleting...', 'recruiting-playbook' )
								: __( 'Permanently Delete', 'recruiting-playbook' )
							}
						</AlertDialogAction>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</div>
	);
}

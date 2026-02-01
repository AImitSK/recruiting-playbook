/**
 * FileUploadSettings Component
 *
 * Settings panel for the file_upload system field.
 * Allows configuration of allowed file types, max file size, max files, and help text.
 *
 * @package RecruitingPlaybook
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Button } from '../../../components/ui/button';
import { Input } from '../../../components/ui/input';
import { Label } from '../../../components/ui/label';
import { Textarea } from '../../../components/ui/textarea';
import { Switch } from '../../../components/ui/switch';
import { X, Upload } from 'lucide-react';

/**
 * Available file type options
 */
const FILE_TYPE_OPTIONS = [
	{ key: 'pdf', label: 'PDF', extension: '.pdf' },
	{ key: 'doc', label: 'Word (.doc)', extension: '.doc' },
	{ key: 'docx', label: 'Word (.docx)', extension: '.docx' },
	{ key: 'jpg', label: 'JPEG', extension: '.jpg,.jpeg' },
	{ key: 'png', label: 'PNG', extension: '.png' },
	{ key: 'gif', label: 'GIF', extension: '.gif' },
];

/**
 * FileUploadSettings component
 *
 * @param {Object}   props           Component props
 * @param {Object}   props.settings  Current settings
 * @param {Function} props.onSave    Save handler
 * @param {Function} props.onClose   Close handler
 */
export default function FileUploadSettings( { settings = {}, onSave, onClose } ) {
	// Local state for form values
	const [ label, setLabel ] = useState( settings.label || __( 'Dokumente hochladen', 'recruiting-playbook' ) );
	const [ helpText, setHelpText ] = useState( settings.help_text || '' );
	const [ allowedTypes, setAllowedTypes ] = useState( settings.allowed_types || [ 'pdf', 'doc', 'docx' ] );
	const [ maxFileSize, setMaxFileSize ] = useState( settings.max_file_size || 10 );
	const [ maxFiles, setMaxFiles ] = useState( settings.max_files || 5 );

	// Toggle file type
	const toggleFileType = ( fileType ) => {
		setAllowedTypes( ( prev ) => {
			if ( prev.includes( fileType ) ) {
				// Don't allow removing all types
				if ( prev.length <= 1 ) {
					return prev;
				}
				return prev.filter( ( t ) => t !== fileType );
			}
			return [ ...prev, fileType ];
		} );
	};

	// Handle save
	const handleSave = () => {
		onSave( {
			label,
			help_text: helpText,
			allowed_types: allowedTypes,
			max_file_size: Math.max( 1, Math.min( 100, maxFileSize ) ),
			max_files: Math.max( 1, Math.min( 20, maxFiles ) ),
		} );
	};

	return (
		<div
			style={ {
				position: 'fixed',
				inset: 0,
				backgroundColor: 'rgba(0, 0, 0, 0.5)',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				zIndex: 100,
			} }
			onClick={ ( e ) => {
				if ( e.target === e.currentTarget ) {
					onClose();
				}
			} }
		>
			<Card style={ { width: '100%', maxWidth: '500px', maxHeight: '90vh', overflow: 'auto' } }>
				<CardHeader style={ { display: 'flex', flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' } }>
					<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
						<Upload style={ { height: '1.25rem', width: '1.25rem', color: '#22c55e' } } />
						<div>
							<CardTitle>{ __( 'Datei-Upload Einstellungen', 'recruiting-playbook' ) }</CardTitle>
							<CardDescription>{ __( 'Konfigurieren Sie den Datei-Upload für Bewerbungen', 'recruiting-playbook' ) }</CardDescription>
						</div>
					</div>
					<Button variant="ghost" size="sm" onClick={ onClose }>
						<X style={ { height: '1rem', width: '1rem' } } />
					</Button>
				</CardHeader>

				<CardContent style={ { display: 'flex', flexDirection: 'column', gap: '1.5rem' } }>
					{ /* Label */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="upload-label">{ __( 'Bezeichnung', 'recruiting-playbook' ) }</Label>
						<Input
							id="upload-label"
							value={ label }
							onChange={ ( e ) => setLabel( e.target.value ) }
							placeholder={ __( 'Dokumente hochladen', 'recruiting-playbook' ) }
						/>
					</div>

					{ /* Allowed File Types */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.75rem' } }>
						<Label>{ __( 'Erlaubte Dateitypen', 'recruiting-playbook' ) }</Label>
						<div style={ { display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '0.5rem' } }>
							{ FILE_TYPE_OPTIONS.map( ( option ) => (
								<label
									key={ option.key }
									style={ {
										display: 'flex',
										alignItems: 'center',
										gap: '0.5rem',
										padding: '0.5rem',
										backgroundColor: allowedTypes.includes( option.key ) ? '#f0fdf4' : '#f9fafb',
										borderRadius: '0.375rem',
										border: allowedTypes.includes( option.key ) ? '1px solid #86efac' : '1px solid #e5e7eb',
										cursor: 'pointer',
									} }
								>
									<input
										type="checkbox"
										checked={ allowedTypes.includes( option.key ) }
										onChange={ () => toggleFileType( option.key ) }
										style={ { width: '1rem', height: '1rem' } }
									/>
									<span style={ { fontSize: '0.875rem' } }>{ option.label }</span>
								</label>
							) ) }
						</div>
						<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
							{ __( 'Mindestens ein Dateityp muss ausgewählt sein.', 'recruiting-playbook' ) }
						</p>
					</div>

					{ /* Max File Size */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="max-file-size">{ __( 'Maximale Dateigröße (MB)', 'recruiting-playbook' ) }</Label>
						<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
							<Input
								id="max-file-size"
								type="number"
								min={ 1 }
								max={ 100 }
								value={ maxFileSize }
								onChange={ ( e ) => setMaxFileSize( parseInt( e.target.value, 10 ) || 1 ) }
								style={ { width: '100px' } }
							/>
							<span style={ { fontSize: '0.875rem', color: '#6b7280' } }>MB</span>
						</div>
						<p style={ { fontSize: '0.75rem', color: '#6b7280', margin: 0 } }>
							{ __( 'Erlaubt: 1-100 MB. Server-Limit kann niedriger sein.', 'recruiting-playbook' ) }
						</p>
					</div>

					{ /* Max Files */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="max-files">{ __( 'Maximale Anzahl Dateien', 'recruiting-playbook' ) }</Label>
						<div style={ { display: 'flex', alignItems: 'center', gap: '0.5rem' } }>
							<Input
								id="max-files"
								type="number"
								min={ 1 }
								max={ 20 }
								value={ maxFiles }
								onChange={ ( e ) => setMaxFiles( parseInt( e.target.value, 10 ) || 1 ) }
								style={ { width: '100px' } }
							/>
							<span style={ { fontSize: '0.875rem', color: '#6b7280' } }>{ __( 'Dateien', 'recruiting-playbook' ) }</span>
						</div>
					</div>

					{ /* Help Text */ }
					<div style={ { display: 'flex', flexDirection: 'column', gap: '0.5rem' } }>
						<Label htmlFor="help-text">{ __( 'Hilfetext', 'recruiting-playbook' ) }</Label>
						<Textarea
							id="help-text"
							value={ helpText }
							onChange={ ( e ) => setHelpText( e.target.value ) }
							placeholder={ __( 'z.B. "Bitte laden Sie Ihren Lebenslauf und relevante Zeugnisse hoch."', 'recruiting-playbook' ) }
							rows={ 2 }
						/>
					</div>

					{ /* Action Buttons */ }
					<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', paddingTop: '0.5rem', borderTop: '1px solid #e5e7eb' } }>
						<Button variant="outline" onClick={ onClose }>
							{ __( 'Abbrechen', 'recruiting-playbook' ) }
						</Button>
						<Button onClick={ handleSave }>
							{ __( 'Speichern', 'recruiting-playbook' ) }
						</Button>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

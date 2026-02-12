/**
 * RolesSettings Component
 *
 * User roles tab with sub-navigation (Roles / Job Assignment)
 *
 * @package RecruitingPlaybook
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { RolesList } from './RolesList';
import { JobAssignments } from './JobAssignments';

/**
 * RolesSettings Component
 *
 * @return {JSX.Element} Component
 */
export function RolesSettings() {
	const [ activeSection, setActiveSection ] = useState( 'roles' );

	const sections = [
		{ id: 'roles', label: __( 'Roles & Permissions', 'recruiting-playbook' ) },
		{ id: 'assignments', label: __( 'Job Assignment', 'recruiting-playbook' ) },
	];

	return (
		<div>
			{ /* Sub-navigation */ }
			<div style={ {
				display: 'flex',
				gap: '0.25rem',
				marginBottom: '1.5rem',
				borderBottom: '1px solid #e5e7eb',
				paddingBottom: '0',
			} }>
				{ sections.map( ( section ) => (
					<button
						key={ section.id }
						type="button"
						onClick={ () => setActiveSection( section.id ) }
						style={ {
							padding: '0.5rem 1rem',
							fontSize: '0.875rem',
							fontWeight: activeSection === section.id ? 600 : 400,
							color: activeSection === section.id ? '#1d71b8' : '#6b7280',
							backgroundColor: 'transparent',
							border: 'none',
							borderBottom: activeSection === section.id
								? '2px solid #1d71b8'
								: '2px solid transparent',
							cursor: 'pointer',
							marginBottom: '-1px',
						} }
					>
						{ section.label }
					</button>
				) ) }
			</div>

			{ /* Content */ }
			{ activeSection === 'roles' && <RolesList /> }
			{ activeSection === 'assignments' && <JobAssignments /> }
		</div>
	);
}

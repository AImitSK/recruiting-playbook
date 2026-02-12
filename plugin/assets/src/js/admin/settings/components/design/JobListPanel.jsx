/**
 * JobListPanel Component
 *
 * Tab: Job List - Layout, Display and Badge Colors.
 *
 * @package RecruitingPlaybook
 */

import { __ } from '@wordpress/i18n';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../../components/ui/card';
import { Switch } from '../../../components/ui/switch';
import { Label } from '../../../components/ui/label';
import { ColorPicker } from '../../../components/ui/color-picker';
import { RadioGroup, RadioGroupItem } from '../../../components/ui/radio-group';

/**
 * JobListPanel Component
 *
 * @param {Object}   props          Component props
 * @param {Object}   props.settings Current design settings
 * @param {Function} props.onUpdate Update single setting
 * @return {JSX.Element} Component
 */
export function JobListPanel( { settings, onUpdate } ) {
	return (
		<div className="rp-space-y-4">
			{ /* Card: Layout & Display */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Layout & Display', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Display of job listing overview', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Display */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Display', 'recruiting-playbook' ) }
						</Label>
						<RadioGroup
							value={ settings.job_list_layout || 'grid' }
							onValueChange={ ( value ) => onUpdate( 'job_list_layout', value ) }
							variant="buttons"
						>
							<RadioGroupItem value="grid">
								{ __( 'Grid', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="list">
								{ __( 'List', 'recruiting-playbook' ) }
							</RadioGroupItem>
						</RadioGroup>
					</div>

					{ /* Column count (only for Grid) */ }
					{ settings.job_list_layout === 'grid' && (
						<div>
							<Label className="rp-mb-2 rp-block">
								{ __( 'Column Count', 'recruiting-playbook' ) }
							</Label>
							<RadioGroup
								value={ String( settings.job_list_columns || 3 ) }
								onValueChange={ ( value ) => onUpdate( 'job_list_columns', parseInt( value, 10 ) ) }
								variant="buttons"
							>
								<RadioGroupItem value="2">2</RadioGroupItem>
								<RadioGroupItem value="3">3</RadioGroupItem>
								<RadioGroupItem value="4">4</RadioGroupItem>
							</RadioGroup>
						</div>
					) }

					{ /* Divider */ }
					<div className="rp-border-t rp-border-gray-200 rp-pt-4">
						<Label className="rp-mb-3 rp-block rp-text-sm rp-font-medium">
							{ __( 'Displayed Information', 'recruiting-playbook' ) }
						</Label>

						<div className="rp-space-y-2">
							{ /* Show badges */ }
							<div className="rp-flex rp-items-center rp-justify-between rp-py-1">
								<Label htmlFor="show_badges" className="rp-font-normal">
									{ __( 'Badges', 'recruiting-playbook' ) }
								</Label>
								<Switch
									id="show_badges"
									checked={ settings.show_badges ?? true }
									onCheckedChange={ ( checked ) => onUpdate( 'show_badges', checked ) }
								/>
							</div>

							{ /* Show salary */ }
							<div className="rp-flex rp-items-center rp-justify-between rp-py-1">
								<Label htmlFor="show_salary" className="rp-font-normal">
									{ __( 'Salary', 'recruiting-playbook' ) }
								</Label>
								<Switch
									id="show_salary"
									checked={ settings.show_salary ?? true }
									onCheckedChange={ ( checked ) => onUpdate( 'show_salary', checked ) }
								/>
							</div>

							{ /* Show location */ }
							<div className="rp-flex rp-items-center rp-justify-between rp-py-1">
								<Label htmlFor="show_location" className="rp-font-normal">
									{ __( 'Location', 'recruiting-playbook' ) }
								</Label>
								<Switch
									id="show_location"
									checked={ settings.show_location ?? true }
									onCheckedChange={ ( checked ) => onUpdate( 'show_location', checked ) }
								/>
							</div>

							{ /* Employment type */ }
							<div className="rp-flex rp-items-center rp-justify-between rp-py-1">
								<Label htmlFor="show_employment_type" className="rp-font-normal">
									{ __( 'Employment Type', 'recruiting-playbook' ) }
								</Label>
								<Switch
									id="show_employment_type"
									checked={ settings.show_employment_type ?? true }
									onCheckedChange={ ( checked ) => onUpdate( 'show_employment_type', checked ) }
								/>
							</div>

							{ /* Application deadline */ }
							<div className="rp-flex rp-items-center rp-justify-between rp-py-1">
								<Label htmlFor="show_deadline" className="rp-font-normal">
									{ __( 'Application Deadline', 'recruiting-playbook' ) }
								</Label>
								<Switch
									id="show_deadline"
									checked={ settings.show_deadline ?? false }
									onCheckedChange={ ( checked ) => onUpdate( 'show_deadline', checked ) }
								/>
							</div>
						</div>
					</div>
				</CardContent>
			</Card>

			{ /* Card: Badge Colors */ }
			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Badge Colors', 'recruiting-playbook' ) }</CardTitle>
					<CardDescription>
						{ __( 'Colors for different badge types', 'recruiting-playbook' ) }
					</CardDescription>
				</CardHeader>
				<CardContent className="rp-space-y-4">
					{ /* Badge style */ }
					<div>
						<Label className="rp-mb-2 rp-block">
							{ __( 'Badge Style', 'recruiting-playbook' ) }
						</Label>
						<RadioGroup
							value={ settings.badge_style || 'light' }
							onValueChange={ ( value ) => onUpdate( 'badge_style', value ) }
							variant="buttons"
						>
							<RadioGroupItem value="light">
								{ __( 'Light', 'recruiting-playbook' ) }
							</RadioGroupItem>
							<RadioGroupItem value="solid">
								{ __( 'Solid', 'recruiting-playbook' ) }
							</RadioGroupItem>
						</RadioGroup>
						<p className="rp-text-xs rp-text-gray-500 rp-mt-1">
							{ settings.badge_style === 'light'
								? __( 'Transparent background, colored text', 'recruiting-playbook' )
								: __( 'Colored background, white text', 'recruiting-playbook' )
							}
						</p>
					</div>

					{ /* Divider */ }
					<div className="rp-border-t rp-border-gray-200 rp-pt-4 rp-space-y-3">
						{ /* New */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<div className="rp-flex rp-items-center rp-gap-2">
								<span
									className="rp-px-2 rp-py-0.5 rp-text-xs rp-font-medium rp-rounded"
									style={ {
										backgroundColor: settings.badge_style === 'solid'
											? ( settings.badge_color_new || '#22c55e' )
											: `${ settings.badge_color_new || '#22c55e' }20`,
										color: settings.badge_style === 'solid'
											? '#ffffff'
											: ( settings.badge_color_new || '#22c55e' ),
									} }
								>
									{ __( 'New', 'recruiting-playbook' ) }
								</span>
							</div>
							<ColorPicker
								value={ settings.badge_color_new || '#22c55e' }
								onChange={ ( color ) => onUpdate( 'badge_color_new', color ) }
							/>
						</div>

						{ /* Remote */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<div className="rp-flex rp-items-center rp-gap-2">
								<span
									className="rp-px-2 rp-py-0.5 rp-text-xs rp-font-medium rp-rounded"
									style={ {
										backgroundColor: settings.badge_style === 'solid'
											? ( settings.badge_color_remote || '#8b5cf6' )
											: `${ settings.badge_color_remote || '#8b5cf6' }20`,
										color: settings.badge_style === 'solid'
											? '#ffffff'
											: ( settings.badge_color_remote || '#8b5cf6' ),
									} }
								>
									{ __( 'Remote', 'recruiting-playbook' ) }
								</span>
							</div>
							<ColorPicker
								value={ settings.badge_color_remote || '#8b5cf6' }
								onChange={ ( color ) => onUpdate( 'badge_color_remote', color ) }
							/>
						</div>

						{ /* Category */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<div className="rp-flex rp-items-center rp-gap-2">
								<span
									className="rp-px-2 rp-py-0.5 rp-text-xs rp-font-medium rp-rounded"
									style={ {
										backgroundColor: settings.badge_style === 'solid'
											? ( settings.badge_color_category || '#6b7280' )
											: `${ settings.badge_color_category || '#6b7280' }20`,
										color: settings.badge_style === 'solid'
											? '#ffffff'
											: ( settings.badge_color_category || '#6b7280' ),
									} }
								>
									IT
								</span>
							</div>
							<ColorPicker
								value={ settings.badge_color_category || '#6b7280' }
								onChange={ ( color ) => onUpdate( 'badge_color_category', color ) }
							/>
						</div>

						{ /* Salary */ }
						<div className="rp-flex rp-items-center rp-justify-between">
							<div className="rp-flex rp-items-center rp-gap-2">
								<span
									className="rp-px-2 rp-py-0.5 rp-text-xs rp-font-medium rp-rounded"
									style={ {
										backgroundColor: settings.badge_style === 'solid'
											? ( settings.badge_color_salary || '#2563eb' )
											: `${ settings.badge_color_salary || '#2563eb' }20`,
										color: settings.badge_style === 'solid'
											? '#ffffff'
											: ( settings.badge_color_salary || '#2563eb' ),
									} }
								>
									60k-80k
								</span>
							</div>
							<ColorPicker
								value={ settings.badge_color_salary || '#2563eb' }
								onChange={ ( color ) => onUpdate( 'badge_color_salary', color ) }
							/>
						</div>
					</div>
				</CardContent>
			</Card>
		</div>
	);
}

export default JobListPanel;

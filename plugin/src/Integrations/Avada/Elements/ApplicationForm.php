<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Application Form Element for Fusion Builder
 *
 * Displays the multi-step application form.
 * Wrapper for the [rp_application_form] shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class ApplicationForm extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Application Form', 'recruiting-playbook' ),
			'shortcode'       => 'rp_application_form',
			'icon'            => 'fusiona-list-alt',
			'component'       => true,
			'help_url'        => $this->getHelpUrl( 'rp_application_form' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Job', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Empty = auto-detect (on job pages).', 'recruiting-playbook' ),
					'param_name'  => 'job_id',
					'value'       => $this->getJobOptions(),
					'default'     => '',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Heading', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Heading of the form.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => 'Apply Now',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Show job title', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Display job title above the form.', 'recruiting-playbook' ),
					'param_name'  => 'show_job_title',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Progress indicator', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Display progress bar for multi-step form.', 'recruiting-playbook' ),
					'param_name'  => 'show_progress',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
			],
		];
	}
}

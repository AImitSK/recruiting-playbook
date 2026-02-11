<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: AI Job Match Element for Fusion Builder
 *
 * "Do I match this job?" button with AI analysis.
 * Visitors can check if their profile matches the job.
 * Wrapper for the [rp_ai_job_match] shortcode.
 *
 * Requires the AI addon.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class AiJobMatch extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: AI Job Match', 'recruiting-playbook' ),
			'shortcode'       => 'rp_ai_job_match',
			'icon'            => 'fusiona-check_circle_outline',
			'component'       => true,
			'help_url'        => $this->getHelpUrl( 'rp_ai_job_match' ),
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
					'heading'     => esc_attr__( 'Button Text', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Text displayed on the button.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => 'Do I match this job?',
				],
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Button Style', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Visual style of the button.', 'recruiting-playbook' ),
					'param_name'  => 'style',
					'default'     => '',
					'value'       => [
						''        => esc_attr__( 'Default (filled)', 'recruiting-playbook' ),
						'outline' => esc_attr__( 'Outline (border only)', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'CSS Classes', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Additional CSS classes for custom styling.', 'recruiting-playbook' ),
					'param_name'  => 'class',
					'value'       => '',
				],
			],
		];
	}
}

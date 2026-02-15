<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: AI Job Finder Element for Fusion Builder
 *
 * AI-powered job finder: Visitors upload their resume
 * and receive matching job suggestions.
 * Wrapper for the [rp_ai_job_finder] shortcode.
 *
 * Requires the Pro plan.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class AiJobFinder extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: AI Job Finder', 'recruiting-playbook' ),
			'shortcode'       => 'rp_ai_job_finder',
			'icon'            => 'fusiona-avada-ai',
			'help_url'        => $this->getHelpUrl( 'rp_ai_job_finder' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Heading', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Heading of the job finder.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => 'Find Your Dream Job',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Subtitle', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Explanatory text below the heading.', 'recruiting-playbook' ),
					'param_name'  => 'subtitle',
					'value'       => 'Upload your resume and discover matching jobs.',
				],
				[
					'type'        => 'range',
					'heading'     => esc_attr__( 'Max. Suggestions', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Maximum number of AI suggestions.', 'recruiting-playbook' ),
					'param_name'  => 'limit',
					'value'       => '5',
					'min'         => '1',
					'max'         => '10',
					'step'        => '1',
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

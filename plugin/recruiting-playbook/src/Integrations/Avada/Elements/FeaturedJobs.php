<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Featured Jobs Element for Fusion Builder
 *
 * Displays featured job listings.
 * Wrapper for the [rp_featured_jobs] shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class FeaturedJobs extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Featured Jobs', 'recruiting-playbook' ),
			'shortcode'       => 'rp_featured_jobs',
			'icon'            => 'fusiona-star-empty',
			'help_url'        => $this->getHelpUrl( 'rp_featured_jobs' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'range',
					'heading'     => esc_attr__( 'Count', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Number of featured jobs.', 'recruiting-playbook' ),
					'param_name'  => 'limit',
					'value'       => '3',
					'min'         => '1',
					'max'         => '12',
					'step'        => '1',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Columns', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Number of columns in the grid.', 'recruiting-playbook' ),
					'param_name'  => 'columns',
					'default'     => '3',
					'value'       => [
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
					],
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Heading', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Heading above the featured jobs.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => '',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Show Excerpt', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Show job description as short text.', 'recruiting-playbook' ),
					'param_name'  => 'show_excerpt',
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

<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Latest Jobs Element for Fusion Builder
 *
 * Displays the latest job listings. Ideal for sidebars.
 * Wrapper for the [rp_latest_jobs] shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class LatestJobs extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Latest Jobs', 'recruiting-playbook' ),
			'shortcode'       => 'rp_latest_jobs',
			'icon'            => 'fusiona-clock',
			'help_url'        => $this->getHelpUrl( 'rp_latest_jobs' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'range',
					'heading'     => esc_attr__( 'Limit', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Number of latest jobs to display.', 'recruiting-playbook' ),
					'param_name'  => 'limit',
					'value'       => '5',
					'min'         => '1',
					'max'         => '20',
					'step'        => '1',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Columns', 'recruiting-playbook' ),
					'description' => esc_attr__( '0 = List view without grid.', 'recruiting-playbook' ),
					'param_name'  => 'columns',
					'default'     => '0',
					'value'       => [
						'0' => esc_attr__( 'List', 'recruiting-playbook' ),
						'1' => '1',
						'2' => '2',
						'3' => '3',
					],
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Title', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Heading above the latest jobs.', 'recruiting-playbook' ),
					'param_name'  => 'title',
					'value'       => '',
				],
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Category', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Show only jobs in this category.', 'recruiting-playbook' ),
					'param_name'  => 'category',
					'value'       => $this->getTaxonomyOptions( 'job_category' ),
					'default'     => '',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Show Date', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Display publication date.', 'recruiting-playbook' ),
					'param_name'  => 'show_date',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Show Excerpt', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Display job description as short text.', 'recruiting-playbook' ),
					'param_name'  => 'show_excerpt',
					'default'     => 'false',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
			],
		];
	}
}

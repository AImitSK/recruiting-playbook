<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Job Search Element for Fusion Builder
 *
 * Displays a search form with filters and results list.
 * Wrapper for the [rp_job_search] shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class JobSearch extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Job Search', 'recruiting-playbook' ),
			'shortcode'       => 'rp_job_search',
			'icon'            => 'fusiona-search',
			'help_url'        => $this->getHelpUrl( 'rp_job_search' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Show search field', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Text search for job title and description.', 'recruiting-playbook' ),
					'param_name'  => 'show_search',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Category filter', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Dropdown to select category.', 'recruiting-playbook' ),
					'param_name'  => 'show_category',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Location filter', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Dropdown to select location.', 'recruiting-playbook' ),
					'param_name'  => 'show_location',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Employment type filter', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Dropdown to select employment type.', 'recruiting-playbook' ),
					'param_name'  => 'show_type',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'range',
					'heading'     => esc_attr__( 'Jobs per page', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Number of jobs per page.', 'recruiting-playbook' ),
					'param_name'  => 'limit',
					'value'       => '10',
					'min'         => '1',
					'max'         => '50',
					'step'        => '1',
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Columns', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Number of columns for the results list.', 'recruiting-playbook' ),
					'param_name'  => 'columns',
					'default'     => '1',
					'value'       => [
						'1' => '1',
						'2' => '2',
						'3' => '3',
					],
				],
			],
		];
	}
}

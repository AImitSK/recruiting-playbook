<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Job List Element for Fusion Builder
 *
 * Displays a list of job listings in a grid layout.
 * Wrapper for the [rp_jobs] shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class JobGrid extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Job List', 'recruiting-playbook' ),
			'shortcode'       => 'rp_jobs',
			'icon'            => 'fusiona-sorting-boxes',
			'help_url'        => $this->getHelpUrl( 'rp_jobs' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => array_merge(
				$this->getGeneralParams(),
				$this->getFilterParams(),
				$this->getSortingParams()
			),
		];
	}

	/**
	 * General parameters
	 *
	 * @return array<array<string, mixed>>
	 */
	private function getGeneralParams(): array {
		return [
			[
				'type'        => 'range',
				'heading'     => esc_attr__( 'Number of Jobs', 'recruiting-playbook' ),
				'description' => esc_attr__( 'How many jobs should be displayed?', 'recruiting-playbook' ),
				'param_name'  => 'limit',
				'value'       => '10',
				'min'         => '1',
				'max'         => '50',
				'step'        => '1',
				],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Columns', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Number of columns in the grid.', 'recruiting-playbook' ),
				'param_name'  => 'columns',
				'default'     => '2',
				'value'       => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				],
			],
		];
	}

	/**
	 * Filter parameters
	 *
	 * @return array<array<string, mixed>>
	 */
	private function getFilterParams(): array {
		return [
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Category', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Filter by category.', 'recruiting-playbook' ),
				'param_name'  => 'category',
				'value'       => $this->getTaxonomyOptions( 'job_category' ),
				'default'     => '',
			],
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Location', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Filter by location.', 'recruiting-playbook' ),
				'param_name'  => 'location',
				'value'       => $this->getTaxonomyOptions( 'job_location' ),
				'default'     => '',
			],
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Employment Type', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Filter by employment type.', 'recruiting-playbook' ),
				'param_name'  => 'type',
				'value'       => $this->getTaxonomyOptions( 'employment_type' ),
				'default'     => '',
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Featured Only', 'recruiting-playbook' ),
				'description' => esc_attr__( 'Show only featured jobs.', 'recruiting-playbook' ),
				'param_name'  => 'featured',
				'default'     => 'false',
				'value'       => [
					'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
					'false' => esc_attr__( 'No', 'recruiting-playbook' ),
				],
			],
		];
	}

	/**
	 * Sorting parameters
	 *
	 * @return array<array<string, mixed>>
	 */
	private function getSortingParams(): array {
		return [
			[
				'type'        => 'select',
				'heading'     => esc_attr__( 'Sort By', 'recruiting-playbook' ),
				'param_name'  => 'orderby',
				'default'     => 'date',
				'value'       => [
					'date'  => esc_attr__( 'Date', 'recruiting-playbook' ),
					'title' => esc_attr__( 'Title', 'recruiting-playbook' ),
					'rand'  => esc_attr__( 'Random', 'recruiting-playbook' ),
				],
			],
			[
				'type'        => 'radio_button_set',
				'heading'     => esc_attr__( 'Order', 'recruiting-playbook' ),
				'param_name'  => 'order',
				'default'     => 'DESC',
				'value'       => [
					'DESC' => esc_attr__( 'Descending', 'recruiting-playbook' ),
					'ASC'  => esc_attr__( 'Ascending', 'recruiting-playbook' ),
				],
			],
		];
	}
}

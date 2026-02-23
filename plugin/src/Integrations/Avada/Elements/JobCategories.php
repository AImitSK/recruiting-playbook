<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Job Categories Element for Fusion Builder
 *
 * Displays all job categories as clickable cards.
 * Wrapper for the [rp_job_categories] shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class JobCategories extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Job Categories', 'recruiting-playbook' ),
			'shortcode'       => 'rp_job_categories',
			'icon'            => 'fusiona-folder',
			'help_url'        => $this->getHelpUrl( 'rp_job_categories' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Layout', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Grid shows cards in columns, List shows a vertical list with dividers.', 'recruiting-playbook' ),
					'param_name'  => 'layout',
					'default'     => 'grid',
					'value'       => [
						'grid' => esc_attr__( 'Grid', 'recruiting-playbook' ),
						'list' => esc_attr__( 'List', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Columns', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Number of columns in the grid.', 'recruiting-playbook' ),
					'param_name'  => 'columns',
					'default'     => '4',
					'value'       => [
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
						'6' => '6',
					],
					'dependency'  => [
						[
							'element'  => 'layout',
							'value'    => 'grid',
							'operator' => '==',
						],
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Show Count', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Display the number of jobs per category.', 'recruiting-playbook' ),
					'param_name'  => 'show_count',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'radio_button_set',
					'heading'     => esc_attr__( 'Hide Empty', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Hide categories without jobs.', 'recruiting-playbook' ),
					'param_name'  => 'hide_empty',
					'default'     => 'true',
					'value'       => [
						'true'  => esc_attr__( 'Yes', 'recruiting-playbook' ),
						'false' => esc_attr__( 'No', 'recruiting-playbook' ),
					],
				],
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Order By', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Sort order for categories.', 'recruiting-playbook' ),
					'param_name'  => 'orderby',
					'default'     => 'name',
					'value'       => [
						'name'  => esc_attr__( 'Name', 'recruiting-playbook' ),
						'count' => esc_attr__( 'Count', 'recruiting-playbook' ),
					],
				],
			],
		];
	}
}

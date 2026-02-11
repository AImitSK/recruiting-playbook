<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Avada\Elements;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Job Count Element for Fusion Builder
 *
 * Displays the number of available job listings.
 * Wrapper for the [rp_job_count] shortcode.
 *
 * @package RecruitingPlaybook
 * @since 1.2.0
 */
class JobCount extends AbstractElement {

	/**
	 * Element configuration for Fusion Builder
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfig(): array {
		return [
			'name'            => esc_attr__( 'RP: Job Count', 'recruiting-playbook' ),
			'shortcode'       => 'rp_job_count',
			'icon'            => 'fusiona-dashboard',
			'help_url'        => $this->getHelpUrl( 'rp_job_count' ),
			'inline_editor'   => false,
			'allow_generator' => true,

			'params' => [
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Category', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Count only jobs in this category.', 'recruiting-playbook' ),
					'param_name'  => 'category',
					'value'       => $this->getTaxonomyOptions( 'job_category' ),
					'default'     => '',
				],
				[
					'type'        => 'select',
					'heading'     => esc_attr__( 'Location', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Optional: Count only jobs at this location.', 'recruiting-playbook' ),
					'param_name'  => 'location',
					'value'       => $this->getTaxonomyOptions( 'job_location' ),
					'default'     => '',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Format (Plural)', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Empty = Default. Placeholder: {count}. Example: {count} open positions', 'recruiting-playbook' ),
					'param_name'  => 'format',
					'value'       => '',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Format (Singular)', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Empty = Default. Placeholder: {count}. Example: {count} open position', 'recruiting-playbook' ),
					'param_name'  => 'singular',
					'value'       => '',
				],
				[
					'type'        => 'textfield',
					'heading'     => esc_attr__( 'Format (Zero)', 'recruiting-playbook' ),
					'description' => esc_attr__( 'Empty = "No open positions".', 'recruiting-playbook' ),
					'param_name'  => 'zero',
					'value'       => '',
				],
			],
		];
	}
}

<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Job Categories — Elementor Widget
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class JobCategories extends AbstractWidget {

	public function get_name(): string {
		return 'rp-job-categories';
	}

	public function get_title(): string {
		return esc_html__( 'RP: Job Categories', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-folder';
	}

	public function get_keywords(): array {
		return [ 'kategorien', 'categories', 'jobs', 'bereiche' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_job_categories';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'layout'     => 'layout',
			'columns'    => 'columns',
			'show_count' => 'show_count',
			'hide_empty' => 'hide_empty',
			'orderby'    => 'orderby',
		];
	}

	protected function register_controls(): void {

		$this->start_controls_section(
			'section_general',
			[
				'label' => esc_html__( 'General', 'recruiting-playbook' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'layout',
			[
				'label'   => esc_html__( 'Layout', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => [
					'grid' => esc_html__( 'Grid', 'recruiting-playbook' ),
					'list' => esc_html__( 'List', 'recruiting-playbook' ),
				],
			]
		);

		$this->add_control(
			'columns',
			[
				'label'     => esc_html__( 'Columns', 'recruiting-playbook' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '4',
				'options'   => [
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
				'condition' => [
					'layout' => 'grid',
				],
			]
		);

		$this->add_control(
			'show_count',
			[
				'label'        => esc_html__( 'Show count', 'recruiting-playbook' ),
				'description'  => esc_html__( 'Display the number of jobs per category.', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'No', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => 'true',
			]
		);

		$this->add_control(
			'hide_empty',
			[
				'label'        => esc_html__( 'Hide empty', 'recruiting-playbook' ),
				'description'  => esc_html__( 'Hide categories without jobs.', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'No', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => 'true',
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => esc_html__( 'Sort by', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'name',
				'options' => [
					'name'  => esc_html__( 'Name', 'recruiting-playbook' ),
					'count' => esc_html__( 'Count', 'recruiting-playbook' ),
				],
			]
		);

		$this->end_controls_section();
	}
}

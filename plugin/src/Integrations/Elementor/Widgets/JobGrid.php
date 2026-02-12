<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Job Listing — Elementor Widget
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class JobGrid extends AbstractWidget {

	public function get_name(): string {
		return 'rp-job-grid';
	}

	public function get_title(): string {
		return esc_html__( 'RP: Job Listing', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	public function get_keywords(): array {
		return [ 'jobs', 'positions', 'grid', 'list', 'recruiting' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_jobs';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'limit'    => 'limit',
			'columns'  => 'columns',
			'category' => 'category',
			'location' => 'location',
			'type'     => 'type',
			'featured' => 'featured',
			'orderby'  => 'orderby',
			'order'    => 'order',
		];
	}

	protected function register_controls(): void {

		// --- Tab: General ---
		$this->start_controls_section(
			'section_general',
			[
				'label' => esc_html__( 'General', 'recruiting-playbook' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'limit',
			[
				'label'   => esc_html__( 'Number of Jobs', 'recruiting-playbook' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 50,
				'step'    => 1,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Columns', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '2',
				'options' => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				],
			]
		);

		$this->end_controls_section();

		// --- Tab: Filter ---
		$this->start_controls_section(
			'section_filter',
			[
				'label' => esc_html__( 'Filter', 'recruiting-playbook' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'category',
			[
				'label'   => esc_html__( 'Category', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->getTaxonomyOptions( 'job_category' ),
			]
		);

		$this->add_control(
			'location',
			[
				'label'   => esc_html__( 'Location', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->getTaxonomyOptions( 'job_location' ),
			]
		);

		$this->add_control(
			'type',
			[
				'label'   => esc_html__( 'Employment Type', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->getTaxonomyOptions( 'employment_type' ),
			]
		);

		$this->add_control(
			'featured',
			[
				'label'        => esc_html__( 'Featured Only', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'No', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => '',
			]
		);

		$this->end_controls_section();

		// --- Tab: Sorting ---
		$this->start_controls_section(
			'section_sorting',
			[
				'label' => esc_html__( 'Sorting', 'recruiting-playbook' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => esc_html__( 'Sort By', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'  => esc_html__( 'Date', 'recruiting-playbook' ),
					'title' => esc_html__( 'Title', 'recruiting-playbook' ),
					'rand'  => esc_html__( 'Random', 'recruiting-playbook' ),
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => esc_html__( 'Order', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'DESC' => esc_html__( 'Descending', 'recruiting-playbook' ),
					'ASC'  => esc_html__( 'Ascending', 'recruiting-playbook' ),
				],
			]
		);

		$this->end_controls_section();
	}
}

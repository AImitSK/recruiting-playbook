<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Stellenliste — Elementor Widget
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class JobGrid extends AbstractWidget {

	public function get_name(): string {
		return 'rp-job-grid';
	}

	public function get_title(): string {
		return esc_html__( 'RP: Stellenliste', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	public function get_keywords(): array {
		return [ 'jobs', 'stellen', 'grid', 'liste', 'recruiting' ];
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

		// --- Tab: Allgemein ---
		$this->start_controls_section(
			'section_general',
			[
				'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'limit',
			[
				'label'   => esc_html__( 'Anzahl Stellen', 'recruiting-playbook' ),
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
				'label'   => esc_html__( 'Spalten', 'recruiting-playbook' ),
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
				'label'   => esc_html__( 'Kategorie', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->getTaxonomyOptions( 'job_category' ),
			]
		);

		$this->add_control(
			'location',
			[
				'label'   => esc_html__( 'Standort', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->getTaxonomyOptions( 'job_location' ),
			]
		);

		$this->add_control(
			'type',
			[
				'label'   => esc_html__( 'Beschäftigungsart', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->getTaxonomyOptions( 'employment_type' ),
			]
		);

		$this->add_control(
			'featured',
			[
				'label'        => esc_html__( 'Nur Featured', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => '',
			]
		);

		$this->end_controls_section();

		// --- Tab: Sortierung ---
		$this->start_controls_section(
			'section_sorting',
			[
				'label' => esc_html__( 'Sortierung', 'recruiting-playbook' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => esc_html__( 'Sortieren nach', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'  => esc_html__( 'Datum', 'recruiting-playbook' ),
					'title' => esc_html__( 'Titel', 'recruiting-playbook' ),
					'rand'  => esc_html__( 'Zufällig', 'recruiting-playbook' ),
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => esc_html__( 'Reihenfolge', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'DESC' => esc_html__( 'Absteigend', 'recruiting-playbook' ),
					'ASC'  => esc_html__( 'Aufsteigend', 'recruiting-playbook' ),
				],
			]
		);

		$this->end_controls_section();
	}
}

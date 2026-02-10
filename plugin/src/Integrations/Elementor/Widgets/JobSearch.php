<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Stellensuche — Elementor Widget
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class JobSearch extends AbstractWidget {

	public function get_name(): string {
		return 'rp-job-search';
	}

	public function get_title(): string {
		return esc_html__( 'RP: Stellensuche', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-search';
	}

	public function get_keywords(): array {
		return [ 'suche', 'search', 'jobs', 'stellen', 'filter' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_job_search';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'show_search'   => 'show_search',
			'show_category' => 'show_category',
			'show_location' => 'show_location',
			'show_type'     => 'show_type',
			'limit'         => 'limit',
			'columns'       => 'columns',
		];
	}

	protected function register_controls(): void {

		$this->start_controls_section(
			'section_general',
			[
				'label' => esc_html__( 'Allgemein', 'recruiting-playbook' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_search',
			[
				'label'        => esc_html__( 'Suchfeld anzeigen', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => 'true',
			]
		);

		$this->add_control(
			'show_category',
			[
				'label'        => esc_html__( 'Kategorie-Filter', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => 'true',
			]
		);

		$this->add_control(
			'show_location',
			[
				'label'        => esc_html__( 'Standort-Filter', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => 'true',
			]
		);

		$this->add_control(
			'show_type',
			[
				'label'        => esc_html__( 'Beschäftigungsart-Filter', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => 'true',
			]
		);

		$this->add_control(
			'limit',
			[
				'label'     => esc_html__( 'Stellen pro Seite', 'recruiting-playbook' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 10,
				'min'       => 1,
				'max'       => 50,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Spalten', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
				],
			]
		);

		$this->end_controls_section();
	}
}

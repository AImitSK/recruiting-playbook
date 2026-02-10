<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Neueste Stellen — Elementor Widget
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class LatestJobs extends AbstractWidget {

	public function get_name(): string {
		return 'rp-latest-jobs';
	}

	public function get_title(): string {
		return esc_html__( 'RP: Neueste Stellen', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-clock-o';
	}

	public function get_keywords(): array {
		return [ 'neueste', 'latest', 'jobs', 'stellen', 'aktuell' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_latest_jobs';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'limit'        => 'limit',
			'columns'      => 'columns',
			'title'        => 'title',
			'category'     => 'category',
			'show_excerpt' => 'show_excerpt',
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
			'limit',
			[
				'label'   => esc_html__( 'Anzahl', 'recruiting-playbook' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
				'max'     => 20,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'       => esc_html__( 'Spalten', 'recruiting-playbook' ),
				'description' => esc_html__( '1 = Listendarstellung', 'recruiting-playbook' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '1',
				'options'     => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
				],
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => esc_html__( 'Überschrift', 'recruiting-playbook' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
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
			'show_excerpt',
			[
				'label'        => esc_html__( 'Auszug anzeigen', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => '',
			]
		);

		$this->end_controls_section();
	}
}

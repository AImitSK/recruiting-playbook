<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Stellen-Zähler — Elementor Widget
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class JobCount extends AbstractWidget {

	public function get_name(): string {
		return 'rp-job-count';
	}

	public function get_title(): string {
		return esc_html__( 'RP: Stellen-Zähler', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-counter';
	}

	public function get_keywords(): array {
		return [ 'zähler', 'counter', 'anzahl', 'jobs', 'stellen' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_job_count';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'category' => 'category',
			'location' => 'location',
			'format'   => 'format',
			'singular' => 'singular',
			'zero'     => 'zero',
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
			'format',
			[
				'label'       => esc_html__( 'Format (Mehrzahl)', 'recruiting-playbook' ),
				'description' => esc_html__( 'Verwende {count} als Platzhalter.', 'recruiting-playbook' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '{count} offene Stellen',
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'singular',
			[
				'label'   => esc_html__( 'Format (Einzahl)', 'recruiting-playbook' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '{count} offene Stelle',
			]
		);

		$this->add_control(
			'zero',
			[
				'label'   => esc_html__( 'Format (Null)', 'recruiting-playbook' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'Keine offenen Stellen',
			]
		);

		$this->end_controls_section();
	}
}

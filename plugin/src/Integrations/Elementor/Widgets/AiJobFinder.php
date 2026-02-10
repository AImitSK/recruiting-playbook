<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: KI-Job-Finder — Elementor Widget
 *
 * Benötigt das AI-Addon (wird vom WidgetLoader geprüft).
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class AiJobFinder extends AbstractWidget {

	public function get_name(): string {
		return 'rp-ai-job-finder';
	}

	public function get_title(): string {
		return esc_html__( 'RP: KI-Job-Finder', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-search-bold';
	}

	public function get_keywords(): array {
		return [ 'ki', 'ai', 'job', 'finder', 'lebenslauf', 'cv', 'matching' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_ai_job_finder';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'title'    => 'title',
			'subtitle' => 'subtitle',
			'limit'    => 'limit',
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
			'title',
			[
				'label'   => esc_html__( 'Überschrift', 'recruiting-playbook' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'Finde deinen Traumjob',
			]
		);

		$this->add_control(
			'subtitle',
			[
				'label'   => esc_html__( 'Untertitel', 'recruiting-playbook' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$this->add_control(
			'limit',
			[
				'label'       => esc_html__( 'Max. Vorschläge', 'recruiting-playbook' ),
				'description' => esc_html__( 'Maximale Anzahl der KI-Vorschläge.', 'recruiting-playbook' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 5,
				'min'         => 1,
				'max'         => 10,
			]
		);

		$this->end_controls_section();
	}
}

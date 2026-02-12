<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: AI Job Match — Elementor Widget
 *
 * Requires the AI addon (checked by WidgetLoader).
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class AiJobMatch extends AbstractWidget {

	public function get_name(): string {
		return 'rp-ai-job-match';
	}

	public function get_title(): string {
		return esc_html__( 'RP: AI Job Match', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-check-circle';
	}

	public function get_keywords(): array {
		return [ 'ai', 'match', 'job match', 'compatibility', 'fit' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_ai_job_match';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'job_id' => 'job_id',
			'title'  => 'title',
			'style'  => 'style',
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
			'job_id',
			[
				'label'       => esc_html__( 'Job', 'recruiting-playbook' ),
				'description' => esc_html__( 'Empty = auto-detect.', 'recruiting-playbook' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $this->getJobOptions(),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => esc_html__( 'Button Text', 'recruiting-playbook' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'Am I a fit for this job?',
			]
		);

		$this->add_control(
			'style',
			[
				'label'   => esc_html__( 'Button Style', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''        => esc_html__( 'Default', 'recruiting-playbook' ),
					'outline' => esc_html__( 'Outline', 'recruiting-playbook' ),
				],
			]
		);

		$this->end_controls_section();
	}
}

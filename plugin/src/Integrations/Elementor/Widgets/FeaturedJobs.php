<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Featured Jobs — Elementor Widget
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class FeaturedJobs extends AbstractWidget {

	public function get_name(): string {
		return 'rp-featured-jobs';
	}

	public function get_title(): string {
		return esc_html__( 'RP: Featured Jobs', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-star';
	}

	public function get_keywords(): array {
		return [ 'featured', 'hervorgehoben', 'jobs', 'stellen', 'highlight' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_featured_jobs';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'limit'        => 'limit',
			'columns'      => 'columns',
			'title'        => 'title',
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
				'default' => 3,
				'min'     => 1,
				'max'     => 12,
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Spalten', 'recruiting-playbook' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '3',
				'options' => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				],
			]
		);

		$this->add_control(
			'title',
			[
				'label'       => esc_html__( 'Überschrift', 'recruiting-playbook' ),
				'description' => esc_html__( 'Optional: Überschrift über den Featured Jobs.', 'recruiting-playbook' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
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
				'default'      => 'true',
			]
		);

		$this->end_controls_section();
	}
}

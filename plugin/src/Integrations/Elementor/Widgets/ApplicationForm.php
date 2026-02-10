<?php
declare(strict_types=1);

namespace RecruitingPlaybook\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * RP: Bewerbungsformular — Elementor Widget
 *
 * @package RecruitingPlaybook
 * @since 1.3.0
 */
class ApplicationForm extends AbstractWidget {

	public function get_name(): string {
		return 'rp-application-form';
	}

	public function get_title(): string {
		return esc_html__( 'RP: Bewerbungsformular', 'recruiting-playbook' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	public function get_keywords(): array {
		return [ 'bewerbung', 'formular', 'application', 'form', 'bewerben' ];
	}

	protected function get_shortcode_name(): string {
		return 'rp_application_form';
	}

	protected function get_shortcode_mapping(): array {
		return [
			'job_id'         => 'job_id',
			'title'          => 'title',
			'show_job_title' => 'show_job_title',
			'show_progress'  => 'show_progress',
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
			'job_id',
			[
				'label'       => esc_html__( 'Stelle', 'recruiting-playbook' ),
				'description' => esc_html__( 'Leer = automatisch erkennen (auf Stellenseiten).', 'recruiting-playbook' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $this->getJobOptions(),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => esc_html__( 'Überschrift', 'recruiting-playbook' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'Jetzt bewerben',
			]
		);

		$this->add_control(
			'show_job_title',
			[
				'label'        => esc_html__( 'Stellentitel anzeigen', 'recruiting-playbook' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Ja', 'recruiting-playbook' ),
				'label_off'    => esc_html__( 'Nein', 'recruiting-playbook' ),
				'return_value' => 'true',
				'default'      => 'true',
			]
		);

		$this->add_control(
			'show_progress',
			[
				'label'        => esc_html__( 'Fortschrittsanzeige', 'recruiting-playbook' ),
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
